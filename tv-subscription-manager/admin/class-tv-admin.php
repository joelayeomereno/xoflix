<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once TV_MANAGER_PATH . 'admin/classes/class-tv-admin-base.php';
require_once TV_MANAGER_PATH . 'admin/classes/class-tv-admin-dashboard.php';
require_once TV_MANAGER_PATH . 'admin/classes/class-tv-admin-users.php';
require_once TV_MANAGER_PATH . 'admin/classes/class-tv-admin-plans.php';
require_once TV_MANAGER_PATH . 'admin/classes/class-tv-admin-payments.php';
require_once TV_MANAGER_PATH . 'admin/classes/class-tv-admin-finance.php';
require_once TV_MANAGER_PATH . 'admin/classes/class-tv-admin-coupons.php';
require_once TV_MANAGER_PATH . 'admin/classes/class-tv-admin-methods.php';
require_once TV_MANAGER_PATH . 'admin/classes/class-tv-admin-messages.php';
require_once TV_MANAGER_PATH . 'admin/classes/class-tv-admin-settings.php';
require_once TV_MANAGER_PATH . 'admin/classes/class-tv-admin-sports.php';

class TV_Manager_Admin {

    private $wpdb;
    public $dashboard;
    public $users;
    public $plans;
    public $payments;
    public $finance;
    public $coupons;
    public $methods;
    public $messages;
    public $settings;
    public $sports;

    public function __construct($wpdb) {
        $this->wpdb = $wpdb;

        $this->dashboard = new TV_Admin_Dashboard($wpdb);
        $this->users     = new TV_Admin_Users($wpdb);
        $this->plans     = new TV_Admin_Plans($wpdb);
        $this->payments  = new TV_Admin_Payments($wpdb);
        $this->finance   = new TV_Admin_Finance($wpdb);
        $this->coupons   = new TV_Admin_Coupons($wpdb);
        $this->methods   = new TV_Admin_Methods($wpdb);
        $this->messages  = new TV_Admin_Messages($wpdb);
        $this->settings  = new TV_Admin_Settings($wpdb);
        $this->sports    = new TV_Admin_Sports($wpdb);

        add_action('admin_menu',             array($this, 'add_admin_menu'));
        add_action('admin_head',             array($this, 'render_admin_styles'));
        add_action('admin_enqueue_scripts',  array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_tv_issue_delete_token', array($this, 'ajax_issue_delete_token'));
        add_action('wp_ajax_tv_test_wassenger', array($this->settings, 'ajax_test_wassenger'));
        add_action('wp_ajax_tv_update_plan_order', array($this->plans, 'ajax_update_plan_order'));
        // Manual subscription AJAX
        add_action('wp_ajax_tv_get_users_for_manual', array($this, 'ajax_get_users_for_manual'));
        add_action('wp_ajax_tv_get_plans_for_manual', array($this, 'ajax_get_plans_for_manual'));
        add_action('wp_ajax_tv_add_manual_subscription', array($this, 'ajax_add_manual_subscription'));
        add_action('wp_ajax_tv_delete_manual_transaction', array($this, 'ajax_delete_manual_transaction'));
    }

    public function ajax_issue_delete_token() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Forbidden'), 403);
        }
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'tv_issue_delete_token')) {
            wp_send_json_error(array('message' => 'Security check failed'), 400);
        }
        $user_id = (int) get_current_user_id();
        $token   = wp_generate_password(24, false, false);
        set_transient('tv_delete_verify_' . $user_id, $token, 5 * MINUTE_IN_SECONDS);
        wp_send_json_success(array('token' => $token));
    }

    public function ajax_get_users_for_manual() {
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');
        check_ajax_referer('tv_manual_sub_nonce', 'nonce');
        $s = sanitize_text_field($_POST['search'] ?? '');
        $args = ['number' => 30, 'orderby' => 'display_name', 'order' => 'ASC'];
        if (!empty($s)) {
            $args['search'] = '*' . $s . '*';
            $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }
        $users = get_users($args);
        $result = [];
        foreach ($users as $u) {
            $result[] = ['id' => $u->ID, 'label' => $u->display_name . ' (' . $u->user_email . ')', 'email' => $u->user_email];
        }
        wp_send_json_success($result);
    }

    public function ajax_get_plans_for_manual() {
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');
        check_ajax_referer('tv_manual_sub_nonce', 'nonce');
        $plans = $this->wpdb->get_results("SELECT id, name, price, duration_days FROM {$this->wpdb->prefix}tv_plans ORDER BY name ASC");
        wp_send_json_success($plans ?: []);
    }

    /**
     * AJAX: Add Manual Subscription & Payment record
     * Surgical Fix: Added 'is_manual' flag to the payment insertion.
     */
    public function ajax_add_manual_subscription() {
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');
        check_ajax_referer('tv_manual_sub_nonce', 'nonce');

        $user_id    = intval($_POST['user_id']);
        $plan_id    = intval($_POST['plan_id']);
        $days       = intval($_POST['duration_days'] ?? 30);
        $method     = sanitize_text_field($_POST['payment_method'] ?? 'Manual');
        $coupon     = sanitize_text_field($_POST['coupon'] ?? '');
        $amount     = floatval($_POST['amount']);
        $currency   = sanitize_text_field($_POST['currency'] ?? 'USD');
        $notes      = sanitize_textarea_field($_POST['notes'] ?? '');

        $user = get_userdata($user_id);
        if (!$user) wp_send_json_error('User not found');

        $plan = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->wpdb->prefix}tv_plans WHERE id=%d", $plan_id));
        if (!$plan) wp_send_json_error('Plan not found');

        $start = !empty($_POST['tx_date']) ? sanitize_text_field($_POST['tx_date']) . ' ' . current_time('H:i:s') : current_time('mysql');
        $end   = date('Y-m-d H:i:s', strtotime("+{$days} days", strtotime($start)));
        $txn_id = 'MAN-' . strtoupper(substr(md5(uniqid()), 0, 8));

        // 1. Create the Subscription record first
        $sub_inserted = $this->wpdb->insert("{$this->wpdb->prefix}tv_subscriptions", [
            'user_id'     => $user_id,
            'plan_id'     => $plan_id,
            'start_date'  => $start,
            'end_date'    => $end,
            'status'      => 'active',
            'connections' => (int)($plan->allow_multi_connections ? 1 : 1)
        ]);

        if (!$sub_inserted) {
            wp_send_json_error('Subscription DB insert failed: ' . $this->wpdb->last_error);
        }

        $subscription_id = $this->wpdb->insert_id;

        // 2. Create the Payment record using the correct schema
        $inserted = $this->wpdb->insert("{$this->wpdb->prefix}tv_payments", [
            'subscription_id' => $subscription_id,
            'user_id'         => $user_id,
            'amount'          => $amount,
            'currency'        => $currency,
            'amount_usd'      => ($currency === 'USD') ? $amount : 0,
            'amount_ngn'      => ($currency === 'NGN') ? $amount : 0,
            'method'          => $method,
            'coupon_code'     => $coupon,
            'status'          => 'APPROVED',
            'transaction_id'  => $txn_id,
            'date'            => $start,
            'is_manual'       => 1 // Set flag to allow deletion
        ]);

        if (!$inserted) {
            wp_send_json_error('Payment DB insert failed: ' . $this->wpdb->last_error);
        }

        // Persist purchased duration metadata for future renewals
        if (class_exists('TV_Subscription_Meta')) {
            $months = max(1, round($days / 30));
            TV_Subscription_Meta::set_months((int)$subscription_id, (int)$months);
        }

        wp_send_json_success(['txn_id' => $txn_id, 'payment_id' => $this->wpdb->insert_id]);
    }

    /**
     * AJAX: Delete Manual Transaction
     * Surgical Fix: Combined check for column and prefix to bypass legacy data issues.
     */
    public function ajax_delete_manual_transaction() {
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');
        check_ajax_referer('tv_manual_sub_nonce', 'nonce');
        $pid = intval($_POST['payment_id']);
        
        // Fetch full row to avoid column missing errors
        $row = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->wpdb->prefix}tv_payments WHERE id=%d", $pid));
        
        if (!$row) {
            wp_send_json_error('Transaction not found');
        }

        // Resilient check: True if is_manual flag is 1 OR transaction ID starts with 'MAN-'
        $is_man = (!empty($row->is_manual)) || (isset($row->transaction_id) && strpos((string)$row->transaction_id, 'MAN-') === 0);

        if (!$is_man) {
            wp_send_json_error('Not a manual transaction');
        }

        $this->wpdb->delete("{$this->wpdb->prefix}tv_payments", ['id' => $pid]);
        wp_send_json_success();
    }

    public function add_admin_menu() {
        add_menu_page('TV Manager', 'TV Manager', 'manage_options', 'tv-subs-manager', array($this, 'render_admin_interface'), 'dashicons-groups', 99);
        add_submenu_page('tv-subs-manager', 'All Users', 'All Users', 'manage_options', 'tv-subs-manager', array($this, 'render_admin_interface'));
        add_submenu_page('tv-subs-manager', 'Subscribers History', 'Subscribers', 'manage_options', 'tv-subscribers', array($this, 'render_admin_interface'));
        add_submenu_page('tv-subs-manager', 'Dashboard', 'Dashboard', 'manage_options', 'tv-dashboard', array($this, 'render_admin_interface'));
        add_submenu_page('tv-subs-manager', 'Sports Guide', 'Sports Guide', 'manage_options', 'tv-sports', array($this, 'render_admin_interface'));
        // Settings submenus (all 7)
        add_submenu_page('tv-subs-manager', 'General Settings',     'Settings',       'manage_options', 'tv-settings-general',      array($this, 'render_admin_interface'));
        add_submenu_page('tv-subs-manager', 'Notifications',        'Notifications',  'manage_options', 'tv-settings-notifications', array($this, 'render_admin_interface'));
        add_submenu_page('tv-subs-manager', 'Panels Config',        'Panels',         'manage_options', 'tv-settings-panels',        array($this, 'render_admin_interface'));
        add_submenu_page('tv-subs-manager', 'Support Settings',     'Support',        'manage_options', 'tv-settings-support',       array($this, 'render_admin_interface'));
        add_submenu_page('tv-subs-manager', 'Integrations',         'Integrations',   'manage_options', 'tv-settings-integrations',  array($this, 'render_admin_interface'));
        add_submenu_page('tv-subs-manager', 'Channel Engine',       'Channel Engine', 'manage_options', 'tv-settings-channel',       array($this, 'render_admin_interface'));
        add_submenu_page('tv-subs-manager', 'Recycle Bin',          'Recycle Bin',    'manage_options', 'tv-settings-recycle',       array($this, 'render_admin_interface'));
    }

    public function render_admin_interface() {
        $page      = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $tab_param = isset($_GET['tab'])  ? sanitize_text_field($_GET['tab'])  : '';
        $tab       = 'users';

        if      ($page === 'tv-dashboard')              { $tab = 'dashboard'; }
        elseif  ($page === 'tv-subscribers')            { $tab = 'users'; $_GET['view'] = 'subscribers'; }
        elseif  ($page === 'tv-sports')                 { $tab = 'sports'; }
        elseif  (strpos($page, 'tv-settings-') === 0)  {
            $tab = 'settings';
            $sub = substr($page, strlen('tv-settings-'));
            // Map WP menu slugs to full tab names used by allowed_tabs + view filenames
            $slug_map = ['channel' => 'channel-engine', 'recycle' => 'recycle-bin'];
            if (isset($slug_map[$sub])) $sub = $slug_map[$sub];
            if (empty($tab_param)) $_GET['tab'] = $sub;
        }
        elseif  ($page === 'tv-subs-manager') {
            if (!empty($tab_param)) {
                $tab = ($tab_param === 'subscribers') ? 'users' : $tab_param;
                if ($tab_param === 'subscribers') $_GET['view'] = 'subscribers';
            }
        }

        if ($tab === 'finance') {
            if (!current_user_can('manage_options') && !current_user_can('manage_tv_finance')) {
                wp_die(esc_html__('You do not have permission to access this page.', 'tv-subscription-manager'));
            }
        } else {
            if (!current_user_can('manage_options')) {
                wp_die(esc_html__('You do not have permission to access this page.', 'tv-subscription-manager'));
            }
        }

        switch ($tab) {
            case 'users':    $this->users->handle_actions();    break;
            case 'plans':    $this->plans->handle_actions();    break;
            case 'payments': $this->payments->handle_actions(); break;
            case 'finance':  $this->finance->handle_actions();  break;
            case 'coupons':  $this->coupons->handle_actions();  break;
            case 'methods':  $this->methods->handle_actions();  break;
            case 'messages': $this->messages->handle_actions(); break;
            case 'sports':   $this->sports->handle_actions();   break;
            case 'settings': $this->settings->handle_actions(); break;
        }

        include TV_MANAGER_PATH . 'admin/views/header.php';
        $this->users->render_active_impersonations_bar();
        echo '<div class="tv-fade-in">';
        switch ($tab) {
            case 'users':    $this->users->render();    break;
            case 'plans':    $this->plans->render();    break;
            case 'payments': $this->payments->render(); break;
            case 'finance':  $this->finance->render();  break;
            case 'coupons':  $this->coupons->render();  break;
            case 'methods':  $this->methods->render();  break;
            case 'messages': $this->messages->render(); break;
            case 'sports':   $this->sports->render();   break;
            case 'settings': $this->settings->render(); break;
            case 'dashboard':$this->dashboard->render();break;
            default:         $this->users->render();    break;
        }
        echo '</div>';
        echo '</div></div>';
    }

    private function is_tv_admin_page() {
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        if (empty($page)) return false;
        $valid = ['tv-subs-manager','tv-subscribers','tv-dashboard','tv-sports',
                  'tv-settings-general','tv-settings-notifications','tv-settings-panels',
                  'tv-settings-support','tv-settings-integrations','tv-settings-channel','tv-settings-recycle'];
        return in_array($page, $valid, true);
    }

    public function enqueue_admin_assets($hook_suffix) {
        if (!$this->is_tv_admin_page()) return;
        wp_enqueue_script('tv-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.4.0', true);
        wp_enqueue_script('jquery-ui-sortable');
        $ver = time();
        wp_enqueue_style('tv-admin-ui',  TV_MANAGER_URL . 'admin/assets/tv-admin-ui.css',  [], $ver);
        wp_enqueue_script('tv-admin-ui', TV_MANAGER_URL . 'admin/assets/tv-admin-ui.js', ['jquery', 'jquery-ui-sortable'], $ver, true);
        wp_localize_script('tv-admin-ui', 'tvAdmin', [
            'ajaxUrl'          => admin_url('admin-ajax.php'),
            'deleteTokenNonce' => wp_create_nonce('tv_issue_delete_token'),
            'sortNonce'        => wp_create_nonce('tv_plan_sort_nonce'),
            'manualSubNonce'   => wp_create_nonce('tv_manual_sub_nonce'),
        ]);
    }

    public function render_admin_styles() {
        if (!$this->is_tv_admin_page()) return;
        ?>
        <style>
            /* ====================================================
               TV MANAGER   Vibrant Modern Admin UI v9.0
               ==================================================== */
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

            :root {
                /* Color Palette */
                --tv-primary:       #6366f1;
                --tv-primary-rgb:   99, 102, 241;
                --tv-primary-dark:  #4f46e5;
                --tv-accent:        #06b6d4;
                --tv-accent-rgb:    6, 182, 212;
                --tv-success:       #10b981;
                --tv-success-bg:    #ecfdf5;
                --tv-warning:       #f59e0b;
                --tv-warning-bg:    #fffbeb;
                --tv-danger:        #ef4444;
                --tv-danger-bg:     #fef2f2;
                --tv-purple:        #8b5cf6;
                --tv-pink:          #ec4899;
                --tv-orange:        #f97316;
                --tv-teal:          #14b8a6;

                /* Light Theme */
                --tv-bg:            #f0f4ff;
                --tv-card:          #ffffff;
                --tv-text:          #0f172a;
                --tv-text-muted:    #64748b;
                --tv-border:        #e2e8f0;
                --tv-surface:       #ffffff;
                --tv-surface-active:#f1f5f9;
                --tv-glass:         rgba(255,255,255,0.92);
                --tv-shadow:        0 1px 4px rgba(99,102,241,0.08), 0 1px 2px rgba(0,0,0,0.05);
                --tv-shadow-lg:     0 8px 24px rgba(99,102,241,0.12), 0 2px 6px rgba(0,0,0,0.06);
                --tv-radius:        14px;
                --tv-font:          'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }

            /* Dark mode overrides */
            body.tv-dark {
                --tv-bg:            #080e1a;
                --tv-card:          #0d1526;
                --tv-text:          #e2e8f0;
                --tv-text-muted:    #7c91af;
                --tv-border:        rgba(99,130,191,0.18);
                --tv-surface:       #0d1526;
                --tv-surface-active:rgba(99,130,191,0.1);
                --tv-glass:         rgba(13,21,38,0.94);
                --tv-shadow:        0 2px 8px rgba(0,0,0,0.5);
                --tv-shadow-lg:     0 8px 32px rgba(0,0,0,0.6);
            }

            /* Base */
            html, body, #wpcontent, #wpbody-content, .tv-app-container {
                background-color: var(--tv-bg) !important;
            }
            .tv-app-container {
                font-family: var(--tv-font);
                background-color: var(--tv-bg);
                color: var(--tv-text);
                min-height: calc(100vh - 32px);
                margin-left: -20px;
                padding: 0;
            }
            .tv-app-container * { box-sizing: border-box; }
            .tv-fade-in { animation: tvSlideUp 0.4s cubic-bezier(0.16,1,0.3,1); }
            @keyframes tvSlideUp { 0% { opacity:0; transform:translateY(8px); } 100% { opacity:1; transform:translateY(0); } }

            /* -- TOP BAR -- */
            .tv-top-bar {
                background: var(--tv-glass);
                backdrop-filter: blur(16px) saturate(180%);
                -webkit-backdrop-filter: blur(16px) saturate(180%);
                border-bottom: 1px solid var(--tv-border);
                height: 64px;
                display: flex; align-items: center; justify-content: space-between;
                padding: 0 32px;
                position: sticky; top: 0; z-index: 200;
                box-shadow: 0 1px 0 var(--tv-border), 0 2px 12px rgba(99,102,241,0.06);
            }
            .tv-brand {
                display: flex; align-items: center; gap: 10px;
                font-weight: 800; font-size: 17px; letter-spacing: -0.02em;
                color: var(--tv-text); text-decoration: none;
            }
            .tv-brand-logo {
                width: 36px; height: 36px; border-radius: 10px;
                background: linear-gradient(135deg, var(--tv-primary), var(--tv-purple));
                display: flex; align-items: center; justify-content: center;
                box-shadow: 0 2px 8px rgba(var(--tv-primary-rgb),0.4);
                color: #fff; font-size: 18px;
            }
            .tv-brand .tv-version {
                font-size: 10px; font-weight: 600; color: var(--tv-text-muted);
                background: var(--tv-surface-active); border: 1px solid var(--tv-border);
                padding: 2px 7px; border-radius: 99px;
            }
            .tv-nav { display: flex; gap: 2px; height: 100%; align-items: center; }
            .tv-nav-item {
                display: flex; align-items: center; padding: 7px 13px;
                color: var(--tv-text-muted); text-decoration: none;
                font-size: 13px; font-weight: 500; border-radius: 8px;
                transition: all 0.18s;
                white-space: nowrap;
            }
            .tv-nav-item:hover { color: var(--tv-text); background: var(--tv-surface-active); }
            .tv-nav-item.active {
                color: var(--tv-primary); background: rgba(var(--tv-primary-rgb),0.08);
                font-weight: 700;
                box-shadow: 0 0 0 1px rgba(var(--tv-primary-rgb),0.15);
            }

            /* -- CONTENT AREA   fixed width, no overflow -- */
            .tv-content-area {
                max-width: 1200px; /* fixed from 1280px, slightly narrower */
                width: 100%;
                margin: 32px auto;
                padding: 0 32px;
            }

            /* -- PAGE HEADER -- */
            .tv-page-header {
                display: flex; justify-content: space-between; align-items: flex-end;
                margin-bottom: 28px; padding-bottom: 20px;
                border-bottom: 1px solid var(--tv-border);
            }
            .tv-page-header h1 {
                font-size: 26px; font-weight: 800; color: var(--tv-text);
                margin: 0; letter-spacing: -0.03em; line-height: 1.2;
            }
            .tv-page-header p { color: var(--tv-text-muted); margin: 5px 0 0; font-size: 14px; }

            /* -- GRID -- */
            .tv-grid-2 { display: grid; grid-template-columns: 380px 1fr; gap: 28px; align-items: start; }
            @media(max-width:1024px) { .tv-grid-2 { grid-template-columns: 1fr; } }

            /* -- CARDS -- */
            .tv-card {
                background: var(--tv-card); border: 1px solid var(--tv-border);
                border-radius: var(--tv-radius); box-shadow: var(--tv-shadow);
                overflow: visible; margin-bottom: 24px;
                transition: box-shadow 0.2s;
            }
            .tv-card:hover { box-shadow: var(--tv-shadow-lg); }
            .tv-card-header {
                padding: 18px 22px; border-bottom: 1px solid var(--tv-border);
                display: flex; justify-content: space-between; align-items: center;
                background: linear-gradient(135deg, rgba(var(--tv-primary-rgb),0.03) 0%, transparent 100%);
                border-radius: var(--tv-radius) var(--tv-radius) 0 0;
            }
            .tv-card-header h3 { font-size: 14px; font-weight: 700; margin: 0; color: var(--tv-text); }
            .tv-card-body { padding: 22px; }

            /* -- FORMS -- */
            .tv-form-group { margin-bottom: 20px; }
            .tv-label { display: block; font-size: 12.5px; font-weight: 600; margin-bottom: 7px; color: var(--tv-text); letter-spacing: -0.01em; }
            .tv-input, .tv-textarea, .tv-filter-select, .tv-filter-input {
                width: 100%; padding: 10px 14px;
                border: 1px solid var(--tv-border); border-radius: 9px;
                font-size: 14px; color: var(--tv-text); background: var(--tv-surface);
                transition: border-color 0.15s, box-shadow 0.15s;
                font-family: var(--tv-font);
            }
            .tv-input:focus, .tv-textarea:focus {
                border-color: var(--tv-primary); outline: 0;
                box-shadow: 0 0 0 3px rgba(var(--tv-primary-rgb),0.15);
            }
            .tv-textarea { resize: vertical; }
            .tv-row { display: flex; gap: 16px; margin-bottom: 20px; }
            .tv-col { flex: 1; }

            /* -- BUTTONS -- */
            .tv-btn {
                display: inline-flex; align-items: center; justify-content: center;
                gap: 7px; padding: 10px 18px;
                font-size: 13.5px; font-weight: 600; border-radius: 9px;
                border: 1px solid transparent; cursor: pointer;
                transition: all 0.18s; text-decoration: none; line-height: 1.2;
                font-family: var(--tv-font); white-space: nowrap;
            }
            .tv-btn-primary {
                background: linear-gradient(135deg, var(--tv-primary), var(--tv-primary-dark));
                color: #fff;
                box-shadow: 0 2px 8px rgba(var(--tv-primary-rgb),0.35);
            }
            .tv-btn-primary:hover { 
                transform: translateY(-1px);
                box-shadow: 0 4px 14px rgba(var(--tv-primary-rgb),0.45);
                color: #fff;
            }
            .tv-btn-secondary {
                background: var(--tv-surface); border-color: var(--tv-border);
                color: var(--tv-text);
            }
            .tv-btn-secondary:hover { background: var(--tv-surface-active); border-color: rgba(var(--tv-primary-rgb),0.3); }
            .tv-btn-danger { background: var(--tv-danger-bg); color: var(--tv-danger); border-color: rgba(239,68,68,0.2); }
            .tv-btn-danger:hover { background: var(--tv-danger); color: #fff; }
            .tv-btn-success { background: var(--tv-success-bg); color: var(--tv-success); border-color: rgba(16,185,129,0.2); }
            .tv-btn-success:hover { background: var(--tv-success); color: #fff; }
            .tv-btn-accent { background: linear-gradient(135deg, var(--tv-accent), #0891b2); color: #fff; box-shadow: 0 2px 8px rgba(var(--tv-accent-rgb),0.3); }
            .tv-btn-accent:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(var(--tv-accent-rgb),0.4); color: #fff; }
            .tv-btn-sm { padding: 6px 12px; font-size: 12px; height: 30px; border-radius: 7px; }
            .tv-btn-lg { padding: 12px 24px; font-size: 15px; border-radius: 11px; }
            .tv-btn-text { background: transparent; color: var(--tv-text-muted); padding: 0 10px; border: none; }
            .tv-btn-text:hover { color: var(--tv-primary); }
            .w-full { width: 100%; }

            /* -- TABLE -- */
            .tv-table-container { overflow-x: auto; border-top: 1px solid var(--tv-border); }
            .tv-table { width: 100%; border-collapse: collapse; text-align: left; }
            .tv-table th {
                background: linear-gradient(135deg, var(--tv-surface-active), var(--tv-surface-active));
                padding: 12px 20px; font-size: 11px; font-weight: 700;
                text-transform: uppercase; letter-spacing: 0.06em;
                color: var(--tv-text-muted); border-bottom: 1px solid var(--tv-border);
            }
            .tv-table td {
                padding: 16px 20px; border-bottom: 1px solid var(--tv-border);
                vertical-align: middle; font-size: 13.5px; color: var(--tv-text);
            }
            .tv-table tbody tr { transition: background 0.1s; }
            .tv-table tbody tr:hover { background-color: rgba(var(--tv-primary-rgb),0.03); }
            .tv-table tbody tr:last-child td { border-bottom: none; }
            .tv-user-cell { display: flex; align-items: center; gap: 12px; }
            .tv-avatar {
                width: 38px; height: 38px; flex-shrink: 0;
                background: linear-gradient(135deg, var(--tv-primary), var(--tv-purple));
                color: #fff; border-radius: 10px;
                display: flex; align-items: center; justify-content: center;
                font-weight: 800; font-size: 14px;
                box-shadow: 0 2px 6px rgba(var(--tv-primary-rgb),0.3);
            }

            /* -- BADGES -- */
            .tv-badge {
                display: inline-flex; align-items: center;
                padding: 3px 10px; border-radius: 99px;
                font-size: 11px; font-weight: 700; letter-spacing: 0.02em;
            }
            .tv-badge.active, .tv-badge.premium, .tv-badge.completed, .tv-badge.approved {
                background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7;
            }
            .tv-badge.pending, .tv-badge.awaiting_proof, .tv-badge.in_progress, .tv-badge.expired {
                background: #fef3c7; color: #92400e; border: 1px solid #fcd34d;
            }
            .tv-badge.rejected { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
            .tv-badge.free, .tv-badge.manual, .tv-badge.new { background: #f1f5f9; color: #475569; border: 1px solid var(--tv-border); }
            .tv-badge.renewal { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }

            /* -- STATS GRID -- */
            .tv-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 28px; }
            .tv-stat-card {
                background: var(--tv-card); border: 1px solid var(--tv-border);
                border-radius: var(--tv-radius); padding: 22px;
                display: flex; align-items: center; gap: 18px;
                box-shadow: var(--tv-shadow);
                position: relative; overflow: hidden;
                transition: all 0.25s;
            }
            .tv-stat-card::before {
                content: ''; position: absolute; top: 0; left: 0;
                width: 100%; height: 3px;
                background: linear-gradient(90deg, var(--tv-primary), var(--tv-accent));
                opacity: 0; transition: opacity 0.25s;
            }
            .tv-stat-card:hover { box-shadow: var(--tv-shadow-lg); transform: translateY(-2px); }
            .tv-stat-card:hover::before { opacity: 1; }
            .tv-stat-icon {
                width: 50px; height: 50px; border-radius: 14px;
                display: flex; align-items: center; justify-content: center;
                flex-shrink: 0; transition: all 0.25s;
                background: rgba(var(--tv-primary-rgb),0.1); color: var(--tv-primary);
            }
            .tv-stat-icon .dashicons { font-size: 24px; width: 24px; height: 24px; }
            .tv-stat-card:hover .tv-stat-icon {
                background: var(--tv-primary); color: #fff;
                transform: scale(1.1) rotate(-5deg);
            }
            .tv-stat-label { font-size: 11.5px; font-weight: 700; color: var(--tv-text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 5px; }
            .tv-stat-value { font-size: 20px; font-weight: 900; color: var(--tv-text); letter-spacing: -0.02em; line-height: 1.2; word-break: break-word; }

            /* -- TOOLBAR -- */
            .tv-toolbar {
                padding: 14px 22px; border-bottom: 1px solid var(--tv-border);
                display: flex; align-items: center; gap: 10px;
                background: var(--tv-surface);
            }

            /* -- IMPERSONATION BAR -- */
            .tv-sandbox-bar { background: linear-gradient(135deg, #1e293b, #0f172a); color: #fff; padding: 10px 22px; margin-bottom: 18px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 16px rgba(0,0,0,0.2); }
            .tv-sandbox-title { font-weight: 700; display: flex; align-items: center; gap: 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; }
            .tv-sandbox-list { display: flex; gap: 10px; }
            .tv-sandbox-item { background: #334155; padding: 5px 10px; border-radius: 8px; display: flex; align-items: center; gap: 8px; transition: background 0.2s; }
            .tv-sandbox-item:hover { background: #475569; }
            .tv-sandbox-avatar { width: 22px; height: 22px; background: var(--tv-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 800; color: #fff; }
            .tv-sandbox-info { display: flex; flex-direction: column; line-height: 1.1; }
            .tv-sandbox-name { font-size: 12px; font-weight: 600; }
            .tv-sandbox-time { font-size: 10px; color: #94a3b8; }
            .tv-sandbox-actions { display: flex; gap: 4px; margin-left: 6px; padding-left: 6px; border-left: 1px solid #475569; }
            .tv-btn-icon { color: #cbd5e1; padding: 4px; border-radius: 4px; display: flex; align-items: center; justify-content: center; text-decoration: none; background: none; border: none; cursor: pointer; }
            .tv-btn-icon:hover { background: rgba(255,255,255,0.1); color: #fff; }
            .tv-btn-icon.delete:hover { background: rgba(239,68,68,0.2); color: #ef4444; }

            /* -- COLORFUL STAT ACCENTS -- */
            .tv-stat-card.color-blue .tv-stat-icon { background: rgba(99,102,241,0.1); color: #6366f1; }
            .tv-stat-card.color-blue:hover .tv-stat-icon { background: #6366f1; }
            .tv-stat-card.color-green .tv-stat-icon { background: rgba(16,185,129,0.1); color: #10b981; }
            .tv-stat-card.color-green:hover .tv-stat-icon { background: #10b981; }
            .tv-stat-card.color-orange .tv-stat-icon { background: rgba(249,115,22,0.1); color: #f97316; }
            .tv-stat-card.color-orange:hover .tv-stat-icon { background: #f97316; }
            .tv-stat-card.color-purple .tv-stat-icon { background: rgba(139,92,246,0.1); color: #8b5cf6; }
            .tv-stat-card.color-purple:hover .tv-stat-icon { background: #8b5cf6; }
            .tv-stat-card.color-cyan .tv-stat-icon { background: rgba(6,182,212,0.1); color: #06b6d4; }
            .tv-stat-card.color-cyan:hover .tv-stat-icon { background: #06b6d4; }
            .tv-stat-card.color-pink .tv-stat-icon { background: rgba(236,72,153,0.1); color: #ec4899; }
            .tv-stat-card.color-pink:hover .tv-stat-icon { background: #ec4899; }
        </style>
        <?php
    }
}