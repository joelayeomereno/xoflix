<?php
/**
 * Plugin Name: XOFLIX TV (Subscription Manager)
 * Plugin URI: https://xoflix.tv/
 * Description: XOFLIX TV subscription system: plans, users, payments, fulfillment, and customer dashboard.
 * Version: 3.9.27
 * Author: XOFLIX
 * Author URI: https://xoflix.tv/
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TV_MANAGER_VERSION', '3.9.27'); 
define('TV_MANAGER_PATH', plugin_dir_path(__FILE__));
define('TV_MANAGER_URL', plugin_dir_url(__FILE__));

require_once TV_MANAGER_PATH . 'includes/class-tv-activator.php';
require_once TV_MANAGER_PATH . 'admin/class-tv-admin.php';
require_once TV_MANAGER_PATH . 'public/class-tv-public.php';
require_once TV_MANAGER_PATH . 'includes/class-tv-notification-engine.php';

// Helpers
require_once TV_MANAGER_PATH . 'includes/helpers/class-tv-currency.php';
require_once TV_MANAGER_PATH . 'includes/helpers/class-tv-subscription-meta.php';

// Domain Service Layer
require_once TV_MANAGER_PATH . 'includes/services/class-tv-domain-contract.php';
require_once TV_MANAGER_PATH . 'includes/services/class-tv-domain-audit-service.php';
require_once TV_MANAGER_PATH . 'includes/services/class-tv-domain-notifications-service.php';
require_once TV_MANAGER_PATH . 'includes/services/class-tv-domain-subscriptions-service.php';
require_once TV_MANAGER_PATH . 'includes/services/class-tv-domain-payments-service.php';

// Channel Engine Service
$channel_engine_path = TV_MANAGER_PATH . 'includes/services/class-tv-channel-engine.php';
if (file_exists($channel_engine_path)) {
    require_once $channel_engine_path;
    add_action('init', function() {
        if (class_exists('TV_Channel_Engine')) {
            new TV_Channel_Engine();
        }
    });
}

/**
 * -------------------------------------------------------------------------
 * XSG SMTP & AMAZON SES CONNECTOR (STOCKHOLM REGION READY)
 * Forces all outbound system mail through authenticated SMTP.
 * Upgraded: Fully compatible with Amazon SES Stockholm (eu-north-1).
 * -------------------------------------------------------------------------
 */
add_action('phpmailer_init', 'xoflix_tv_smtp_connector', 999);
function xoflix_tv_smtp_connector($phpmailer) {
    // SECURITY GATE: Skip if bulk mailing is active to protect primary account reputation.
    if (defined('TV_IS_BULK_OPERATION') && TV_IS_BULK_OPERATION === true) {
        return;
    }

    // Check if user enabled the connector in Settings
    if ((int) get_option('tv_smtp_enabled', 0) !== 1) {
        return;
    }

    $host     = (string) get_option('tv_smtp_host', '');
    $user     = (string) get_option('tv_smtp_user', '');
    $pass     = (string) get_option('tv_smtp_pass');
    $port     = (int)    get_option('tv_smtp_port', 587);
    $enc      = (string) get_option('tv_smtp_enc', 'tls');
    $insecure = (int)    get_option('tv_smtp_insecure', 0);

    if (empty($host) || empty($user) || empty($pass)) {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host       = $host;
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = $port;
    $phpmailer->Username   = $user;
    $phpmailer->Password   = $pass;
    $phpmailer->SMTPSecure = $enc; 
    $phpmailer->Timeout    = 30; 
    
    // SES Requirement: Ensure specific encoding for reliability
    $phpmailer->CharSet = 'UTF-8';
    
    // --- HARDENED SSL BYPASS LOGIC ---
    if ($insecure === 1) {
        // Forcefully disable opportunistic TLS upgrades which trigger verification
        $phpmailer->SMTPAutoTLS = false; 
        
        $phpmailer->SMTPOptions = array(
            'ssl' => array(
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            )
        );
    } else {
        // Standard Amazon SES TLS enforcement
        $phpmailer->SMTPAutoTLS = true;
    }
    
    // Identity headers
    $from_email = get_option('tv_smtp_from_email', $user);
    $from_name  = get_option('tv_smtp_from_name', get_bloginfo('name'));
    
    $phpmailer->setFrom($from_email, $from_name);
    
    // CRITICAL: Force Envelope Sender (Return-Path) to match From User
    // Required for Stockholm region validation.
    $phpmailer->Sender = $from_email;
}

/**
 * -------------------------------------------------------------------------
 * GLOBAL MAIL ERROR LOGGER
 * Intercepts failed wp_mail attempts and logs the reason to TV Activity Logs.
 * -------------------------------------------------------------------------
 */
add_action('wp_mail_failed', 'xoflix_tv_log_mail_errors');
function xoflix_tv_log_mail_errors($wp_error) {
    if (class_exists('TV_Domain_Audit_Service')) {
        $audit = new TV_Domain_Audit_Service();
        $error_message = $wp_error->get_error_message();
        $audit->log_event(0, 'Mail Delivery Failed', 'SMTP Error Trace: ' . $error_message);
    }
}

/**
 * -------------------------------------------------------------------------
 * NUCLEAR DB PATCH & SELF-HEALING (UNABRIDGED FULL VERSION)
 * -------------------------------------------------------------------------
 */
function tv_manager_nuclear_db_patch() {
    global $wpdb;
    
    $table_plans = $wpdb->prefix . 'tv_plans';
    
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_plans)) !== $table_plans) {
        if (class_exists('TV_Manager_Activator')) {
            TV_Manager_Activator::activate();
        }
    }

    if (get_transient('tv_db_patch_run_v3_9_27_check')) {
        return;
    }

    $table_subs = $wpdb->prefix . 'tv_subscriptions';
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_subs)) === $table_subs) {
        $check_col = $wpdb->get_results("SHOW COLUMNS FROM {$table_subs} LIKE 'connections'");
        if (empty($check_col)) {
            $wpdb->query("ALTER TABLE {$table_subs} ADD COLUMN connections INT(11) NOT NULL DEFAULT 1 AFTER status");
        }
    }

    $table_payments = $wpdb->prefix . 'tv_payments';
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_payments)) === $table_payments) {
        $check_col = $wpdb->get_results("SHOW COLUMNS FROM {$table_payments} LIKE 'attempted_at'");
        if (empty($check_col)) {
            $wpdb->query("ALTER TABLE {$table_payments} ADD COLUMN attempted_at datetime DEFAULT NULL");
        }
        
        $check_cur = $wpdb->get_results("SHOW COLUMNS FROM {$table_payments} LIKE 'currency'");
        if (empty($check_cur)) {
            $wpdb->query("ALTER TABLE {$table_payments} ADD COLUMN currency VARCHAR(10) DEFAULT 'USD' AFTER amount");
        }

        // SURGICAL FIX: Ensure 'is_manual' column exists for deletion logic
        $check_man = $wpdb->get_results("SHOW COLUMNS FROM {$table_payments} LIKE 'is_manual'");
        if (empty($check_man)) {
            $wpdb->query("ALTER TABLE {$table_payments} ADD COLUMN is_manual TINYINT(1) DEFAULT 0");
        }
    }

    $table_methods = $wpdb->prefix . 'tv_payment_methods';
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_methods)) === $table_methods) {
        $cols = [
            'bank_name'              => "varchar(150) DEFAULT NULL",
            'account_name'           => "varchar(150) DEFAULT NULL",
            'account_number'         => "varchar(80) DEFAULT NULL",
            'logo_url'               => "varchar(255) DEFAULT NULL",
            'open_behavior'          => "varchar(30) DEFAULT 'window'",
            'flutterwave_enabled'    => "TINYINT(1) DEFAULT 0",
            'flutterwave_secret_key' => "TEXT DEFAULT NULL",
            'flutterwave_public_key' => "TEXT DEFAULT NULL",
            'flutterwave_currency'   => "VARCHAR(10) DEFAULT 'USD'",
            'flutterwave_title'      => "VARCHAR(190) DEFAULT NULL",
            'flutterwave_logo'       => "VARCHAR(255) DEFAULT NULL"
        ];
        foreach ($cols as $col => $def) {
            $check = $wpdb->get_results("SHOW COLUMNS FROM {$table_methods} LIKE '$col'");
            if (empty($check)) {
                $wpdb->query("ALTER TABLE {$table_methods} ADD COLUMN $col $def");
            }
        }
    }

    $table_news = $wpdb->prefix . 'tv_announcements';
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_news)) === $table_news) {
        $wpdb->query("ALTER TABLE {$table_news} MODIFY COLUMN button_action VARCHAR(255) DEFAULT 'dashboard'");
    }

    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_plans)) === $table_plans) {
        $check_cat = $wpdb->get_results("SHOW COLUMNS FROM {$table_plans} LIKE 'category'");
        if (empty($check_cat)) {
            $wpdb->query("ALTER TABLE {$table_plans} ADD COLUMN category VARCHAR(50) DEFAULT 'standard' AFTER name");
        }
        $check_order = $wpdb->get_results("SHOW COLUMNS FROM {$table_plans} LIKE 'display_order'");
        if (empty($check_order)) {
            $wpdb->query("ALTER TABLE {$table_plans} ADD COLUMN display_order INT(11) DEFAULT 0 AFTER price");
        }
    }
    
    $table_sports = $wpdb->prefix . 'tv_sports_events';
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_sports)) === $table_sports) {
        $check_home = $wpdb->get_results("SHOW COLUMNS FROM {$table_sports} LIKE 'home_score'");
        if (empty($check_home)) {
            $wpdb->query("ALTER TABLE {$table_sports} ADD COLUMN home_score INT(11) DEFAULT NULL AFTER status");
        }
        $check_away = $wpdb->get_results("SHOW COLUMNS FROM {$table_sports} LIKE 'away_score'");
        if (empty($check_away)) {
            $wpdb->query("ALTER TABLE {$table_sports} ADD COLUMN away_score INT(11) DEFAULT NULL AFTER home_score");
        }
    }

    $table_notify = $wpdb->prefix . 'tv_notification_logs';
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_notify));
    if ($exists !== $table_notify) {
        if (class_exists('TV_Manager_Activator')) { TV_Manager_Activator::activate(); }
    } else {
        $check_retry = $wpdb->get_results("SHOW COLUMNS FROM {$table_notify} LIKE 'retry_count'");
        if (empty($check_retry)) {
            $wpdb->query("ALTER TABLE {$table_notify} ADD COLUMN payload longtext AFTER message");
            $wpdb->query("ALTER TABLE {$table_notify} ADD COLUMN error_msg text AFTER payload");
            $wpdb->query("ALTER TABLE {$table_notify} ADD COLUMN retry_count int(5) DEFAULT 0 AFTER error_msg");
            $wpdb->query("ALTER TABLE {$table_notify} ADD COLUMN next_retry datetime DEFAULT NULL AFTER retry_count");
        }
    }

    $table_devices = $wpdb->prefix . 'streamos_devices';
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_devices)) !== $table_devices) {
        if (class_exists('TV_Manager_Activator')) { TV_Manager_Activator::activate(); }
    }

    set_transient('tv_db_patch_run_v3_9_27_check', true, DAY_IN_SECONDS);
}
add_action('admin_init', 'tv_manager_nuclear_db_patch');

register_activation_hook(__FILE__, 'tv_manager_activate_plugin');

add_filter('cron_schedules', function($schedules) {
    $schedules['tv_interval_5min'] = array(
        'interval' => 300, 
        'display'  => 'Every 5 Minutes (TV Manager Heartbeat)'
    );
    return $schedules;
});

function tv_manager_activate_plugin() {
    if (class_exists('TV_Manager_Activator')) {
        TV_Manager_Activator::activate();
    }
    if (!wp_next_scheduled('tv_daily_notification_check')) {
        wp_schedule_event(time(), 'daily', 'tv_daily_notification_check');
    }
    if (!wp_next_scheduled('tv_retry_queue_heartbeat')) {
        wp_schedule_event(time(), 'tv_interval_5min', 'tv_retry_queue_heartbeat');
    }
    if (!wp_next_scheduled('tv_recycle_bin_cleanup')) {
        wp_schedule_event(time(), 'daily', 'tv_recycle_bin_cleanup');
    }
    delete_transient('tv_db_patch_run_v3_9_27_check');
}

register_deactivation_hook(__FILE__, 'tv_manager_deactivate_cron');
function tv_manager_deactivate_cron() {
    wp_clear_scheduled_hook('tv_daily_notification_check');
    wp_clear_scheduled_hook('tv_retry_queue_heartbeat');
    wp_clear_scheduled_hook('tv_recycle_bin_cleanup');
}

add_action('tv_daily_notification_check', array('TV_Notification_Engine', 'run_daily_checks'));
add_action('tv_retry_queue_heartbeat', array('TV_Notification_Engine', 'process_retry_queue'));

add_action('tv_recycle_bin_cleanup', function () {
    global $wpdb;
    $table = $wpdb->prefix . 'tv_recycle_bin';
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) return;
    $wpdb->query("DELETE FROM {$table} WHERE status = 'deleted' AND expires_at < NOW()");
});

function tv_manager_register_flow_rewrite_rules() {
    if (!function_exists('add_rewrite_rule')) { return; }
    $routes = array(
        'tv-select-payment-method' => 'select_method',
        'tv-payment'               => 'payment',
        'tv-upload-proof'          => 'upload_proof',
        'tv-payment-control'       => 'payment_control',
        'tv-payment-return'        => 'payment_return',
        'tv-subscription-plans'    => 'subscription_plans', 
    );
    foreach ($routes as $slug => $endpoint) {
        $page = get_page_by_path($slug);
        if ($page && isset($page->post_status) && $page->post_status === 'publish') {
            continue;
        }
        add_rewrite_rule('^' . preg_quote($slug, '/') . '/?$', 'index.php?tv_flow=' . $endpoint, 'top');
    }
}
add_action('init', 'tv_manager_register_flow_rewrite_rules');

add_filter('query_vars', function($vars) {
    if (!in_array('tv_flow', $vars, true)) { $vars[] = 'tv_flow'; }
    return $vars;
});

function run_tv_subscription_manager() {
    add_action('init', 'tv_manager_bootstrap', 5);
    add_action('admin_menu', function() {
        if (!is_admin() || !current_user_can('manage_options')) { return; }
        global $wpdb;
        if (!isset($GLOBALS['tv_manager_admin_instance']) && class_exists('TV_Manager_Admin')) {
            $GLOBALS['tv_manager_admin_instance'] = new TV_Manager_Admin($wpdb);
        }
    }, 0);
}
run_tv_subscription_manager();

function tv_manager_bootstrap() {
    global $wpdb;
    if (!isset($GLOBALS['tv_manager_public_instance']) && class_exists('TV_Manager_Public')) {
        $GLOBALS['tv_manager_public_instance'] = new TV_Manager_Public($wpdb);
    }
    if (is_admin() && current_user_can('manage_options') && !isset($GLOBALS['tv_manager_admin_instance']) && class_exists('TV_Manager_Admin')) {
        $GLOBALS['tv_manager_admin_instance'] = new TV_Manager_Admin($wpdb);
    }
}