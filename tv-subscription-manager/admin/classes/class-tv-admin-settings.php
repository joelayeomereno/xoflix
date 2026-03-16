<?php
/**
 * FILE PATH: tv-subscription-manager/admin/classes/class-tv-admin-settings.php
 *
 * Version: patched
 * FIX: ajax_send_test_email() — corrected require_once path for TV_Auth_Notifications.
 *      Original:  TV_MANAGER_PATH . 'includes/class-tv-auth-notifications.php'
 *      Correct:   TV_MANAGER_PATH . 'admin/classes/class-tv-auth-notifications.php'
 *
 *      The file lives in admin/classes/ (see directory tree).
 *      The wrong path caused PHP to fail silently, WordPress caught the fatal and
 *      returned an HTML error page instead of JSON — hence the client-side
 *      "Unexpected token '<'" error logged in the test panel.
 *
 * All other logic is IDENTICAL to the original.
 */

declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

class TV_Admin_Settings extends TV_Admin_Base {

    public function __construct($wpdb) {
        parent::__construct($wpdb);
        add_action('wp_ajax_tv_test_smtp',       [$this, 'ajax_test_smtp']);
        add_action('wp_ajax_tv_test_wassenger',  [$this, 'ajax_test_wassenger']);
        add_action('wp_ajax_tv_send_test_email', [$this, 'ajax_send_test_email']);
    }

    /* --- HANDLE ALL SETTINGS FORM SAVES --- */
    public function handle_actions(): void {
        // Recycle bin restore
        if (isset($_GET['tv_recycle_restore']) && isset($_GET['rid'])) {
            $rid = (int) $_GET['rid'];
            check_admin_referer('tv_recycle_restore_' . $rid);
            if ($this->recycle_bin_restore($rid)) {
                $this->log_event('Recycle Bin Restore', 'Restored item ID: ' . $rid);
                $this->show_notice('Item restored successfully.');
            } else {
                $this->show_notice('Unable to restore this item.', 'error');
            }
        }

        // Extractor settings (Sports page modal)
        if (isset($_POST['tv_save_extractor_settings'])) {
            check_admin_referer('tv_save_extractor_settings');
            if (!current_user_can('manage_options')) return;
            $submitted = isset($_POST['tv_extractors']) ? (array)$_POST['tv_extractors'] : [];
            $allowed_ext = ['manual', 'smart', 'bulk'];
            $clean = array_values(array_intersect($submitted, $allowed_ext));
            if (!in_array('manual', $clean)) $clean[] = 'manual';
            update_option('tv_enabled_extractors', $clean);
            $this->show_notice('Extractor settings saved.');
            wp_redirect(admin_url('admin.php?page=tv-sports'));
            exit;
        }

        // Main settings save
        if (isset($_POST['save_settings'])) {
            check_admin_referer('tv_settings_verify');
            if (isset($_POST['discounts'])) {
                $raw_discounts = $_POST['discounts'];
                $clean_discounts = [];
                if (isset($raw_discounts['days']) && is_array($raw_discounts['days'])) {
                    for ($i = 0; $i < count($raw_discounts['days']); $i++) {
                        if (!empty($raw_discounts['days'][$i]) && !empty($raw_discounts['percent'][$i])) {
                            $clean_discounts[] = [
                                'days'    => intval($raw_discounts['days'][$i]),
                                'percent' => floatval($raw_discounts['percent'][$i])
                            ];
                        }
                    }
                }
                update_option('tv_duration_discounts', $clean_discounts);
            }
            if (isset($_POST['panels'])) {
                $raw_panels = $_POST['panels'];
                $clean_panels = [];
                if (isset($raw_panels['name']) && is_array($raw_panels['name'])) {
                    for ($i = 0; $i < count($raw_panels['name']); $i++) {
                        if (!empty($raw_panels['name'][$i])) {
                            $clean_panels[] = [
                                'id'           => sanitize_title($raw_panels['name'][$i]) . '_' . substr(md5((string)time() . $i), 0, 4),
                                'name'         => sanitize_text_field($raw_panels['name'][$i]),
                                'smart_tv_url' => esc_url_raw($raw_panels['smart_tv_url'][$i]),
                                'xtream_url'   => esc_url_raw($raw_panels['xtream_url'][$i])
                            ];
                        }
                    }
                }
                update_option('tv_panel_configs', $clean_panels);
            }
            update_option('tv_multi_step_checkout', isset($_POST['tv_multi_step_checkout']) ? 1 : 0);
            update_option('streamos_require_email_verification', isset($_POST['streamos_require_email_verification']) ? 1 : 0);
            if (isset($_POST['tv_plans_page_id'])) {
                update_option('tv_plans_page_id',           intval($_POST['tv_plans_page_id']));
                update_option('tv_select_method_page_id',  intval($_POST['tv_select_method_page_id']));
                update_option('tv_payment_page_id',        intval($_POST['tv_payment_page_id']));
                update_option('tv_upload_proof_page_id',   intval($_POST['tv_upload_proof_page_id']));
            }
            if (isset($_POST['tv_support_whatsapp'])) {
                update_option('tv_support_whatsapp', sanitize_text_field($_POST['tv_support_whatsapp']));
                update_option('tv_support_email',    sanitize_email($_POST['tv_support_email']));
                update_option('tv_support_telegram', sanitize_text_field($_POST['tv_support_telegram']));
            }
            if (isset($_POST['tv_notify_expiry_days'])) {
                update_option('tv_notify_expiry_days',          sanitize_text_field($_POST['tv_notify_expiry_days']));
                update_option('tv_notify_reengage_enabled',     isset($_POST['tv_notify_reengage_enabled']) ? '1' : '0');
                update_option('tv_notify_whatsapp_gateway',     esc_url_raw($_POST['tv_notify_whatsapp_gateway']));
                update_option('tv_notify_whatsapp_key',         sanitize_text_field($_POST['tv_notify_whatsapp_key']));
                $templates = [
                    'expiry'   => [
                        'subject' => sanitize_text_field($_POST['tmpl_expiry_subject']),
                        'body'    => wp_kses_post(wp_unslash($_POST['tmpl_expiry_body']))
                    ],
                    'reengage' => [
                        'subject' => sanitize_text_field($_POST['tmpl_reengage_subject']),
                        'body'    => wp_kses_post(wp_unslash($_POST['tmpl_reengage_body']))
                    ]
                ];
                update_option('tv_notification_templates', $templates);
            }
            if (isset($_POST['tv_allowed_currencies_check'])) {
                $allowed = isset($_POST['tv_allowed_currencies']) ? array_map('sanitize_text_field', $_POST['tv_allowed_currencies']) : [];
                update_option('tv_allowed_currencies', $allowed);
            }
            if (isset($_POST['tv_wassenger_api_key'])) {
                update_option('tv_wassenger_api_key', sanitize_text_field($_POST['tv_wassenger_api_key']));
            }
            if (isset($_POST['tv_whatsapp_custom_msg'])) {
                update_option('tv_whatsapp_custom_msg',   sanitize_textarea_field($_POST['tv_whatsapp_custom_msg']));
                update_option('tv_trial_overlay_delay',   intval($_POST['tv_trial_overlay_delay']));
            }
            if (isset($_POST['tv_sbe_exclusions'])) {
                update_option('tv_sbe_exclusions', sanitize_textarea_field($_POST['tv_sbe_exclusions']));
                $rules = [];
                if (isset($_POST['sbe_rule_wrong']) && is_array($_POST['sbe_rule_wrong'])) {
                    foreach ($_POST['sbe_rule_wrong'] as $idx => $wrong) {
                        $right = $_POST['sbe_rule_right'][$idx] ?? '';
                        if (!empty($wrong) && !empty($right)) {
                            $rules[sanitize_text_field($wrong)] = sanitize_text_field($right);
                        }
                    }
                }
                update_option('tv_sbe_transform_rules', $rules);
                if (isset($_POST['tv_sbe_priority_list'])) {
                    $p_list = explode(',', sanitize_text_field($_POST['tv_sbe_priority_list']));
                    update_option('tv_sbe_priority', array_filter(array_map('trim', $p_list)));
                }
                update_option('tv_sbe_strict_dedupe', isset($_POST['tv_sbe_strict_dedupe']) ? 1 : 0);
                if (isset($_POST['tv_sbe_countries_submit'])) {
                    $active_countries = isset($_POST['tv_sbe_active_countries']) ? array_map('sanitize_text_field', $_POST['tv_sbe_active_countries']) : [];
                    update_option('tv_sbe_active_countries', $active_countries);
                }
            }
            if (isset($_POST['tv_smtp_host'])) {
                update_option('tv_smtp_enabled',    isset($_POST['tv_smtp_enabled']) ? 1 : 0);
                update_option('tv_smtp_host',       sanitize_text_field($_POST['tv_smtp_host']));
                update_option('tv_smtp_user',       sanitize_text_field($_POST['tv_smtp_user']));
                if (!empty($_POST['tv_smtp_pass'])) {
                    update_option('tv_smtp_pass', (string)$_POST['tv_smtp_pass']);
                }
                update_option('tv_smtp_port',       intval($_POST['tv_smtp_port']));
                update_option('tv_smtp_enc',        sanitize_text_field($_POST['tv_smtp_enc']));
                update_option('tv_smtp_from_email', sanitize_email($_POST['tv_smtp_from_email']));
                update_option('tv_smtp_from_name',  sanitize_text_field($_POST['tv_smtp_from_name']));
                update_option('tv_imap_host',       sanitize_text_field($_POST['tv_imap_host']));
                update_option('tv_imap_port',       intval($_POST['tv_imap_port']));
                update_option('tv_pop3_port',       intval($_POST['tv_pop3_port']));
                update_option('tv_smtp_insecure',   isset($_POST['tv_smtp_insecure']) ? 1 : 0);
            }
            $this->log_event('Update Settings', 'System configuration synchronized.');
            $this->show_notice('Configuration saved successfully.');
        }
    }

    /* --- RENDER: inject shared tab nav then load sub-view --- */
    public function render(): void {
        $allowed_tabs = [
            'general', 'notifications', 'panels', 'support',
            'integrations', 'channel-engine', 'recycle-bin',
            'email-test',
        ];
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        if (!in_array($tab, $allowed_tabs, true)) {
            $tab = 'general';
        }
        include TV_MANAGER_PATH . 'admin/views/view-settings-tabs.php';
        $sub_view = TV_MANAGER_PATH . 'admin/views/view-settings-' . $tab . '.php';
        if (file_exists($sub_view)) {
            include $sub_view;
        } else {
            echo '<div class="notice notice-warning"><p>Settings view not found: <code>' . esc_html($tab) . '</code></p></div>';
        }
    }

    /* --- AJAX: Test SMTP --- */
    public function ajax_test_smtp(): void {
        check_ajax_referer('tv_test_smtp', '_nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized Access.']);
        }
        $to         = sanitize_email($_POST['test_email']);
        $host       = sanitize_text_field($_POST['host']);
        $user       = sanitize_text_field($_POST['user']);
        $pass       = (string)$_POST['pass'];
        $port       = intval($_POST['port']);
        $enc        = sanitize_text_field($_POST['enc']);
        $from_email = sanitize_email($_POST['from_email']);
        $from_name  = sanitize_text_field($_POST['from_name']);
        $insecure   = !empty($_POST['insecure']) && $_POST['insecure'] === 'true';
        if (empty($pass)) {
            $pass = (string) get_option('tv_smtp_pass');
        }
        $captured_error = '';
        add_action('wp_mail_failed', function($wp_error) use (&$captured_error) {
            $captured_error = $wp_error->get_error_message();
        });
        $tester_callback = function($phpmailer) use ($host, $user, $pass, $port, $enc, $from_email, $from_name, $insecure) {
            $phpmailer->isSMTP();
            $phpmailer->Host       = $host;
            $phpmailer->SMTPAuth   = true;
            $phpmailer->Port       = $port;
            $phpmailer->Username   = $user;
            $phpmailer->Password   = $pass;
            $phpmailer->SMTPSecure = $enc;
            $phpmailer->Timeout    = 20;
            if ($insecure) {
                $phpmailer->SMTPAutoTLS = false;
                $phpmailer->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
            }
            $sender_email = !empty($from_email) ? $from_email : $user;
            $sender_name  = !empty($from_name)  ? $from_name  : get_bloginfo('name');
            $phpmailer->setFrom($sender_email, $sender_name . ' (SES Test)');
            $phpmailer->Sender = $sender_email;
        };
        add_action('phpmailer_init', $tester_callback, 999);
        $result = wp_mail($to, 'XOFLIX TV - SMTP Test', "Success! SMTP Handshake completed.\n\nEndpoint: $host\nFrom: $from_email");
        remove_action('phpmailer_init', $tester_callback, 999);
        if ($result) {
            wp_send_json_success(['message' => 'Success! Test email dispatched.']);
        } else {
            if (stripos($captured_error, 'not verified') !== false || stripos($captured_error, 'Data not accepted') !== false) {
                $captured_error = "AMAZON SES ERROR: The 'From' identity ($from_email) is not verified, or the recipient ($to) is unverified in sandbox.";
            }
            wp_send_json_error(['message' => 'Failed: ' . $captured_error]);
        }
    }

    /* --- AJAX: Test Wassenger --- */
    public function ajax_test_wassenger(): void {
        check_ajax_referer('tv_test_wassenger', '_nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized.']);
        }
        $api_key = sanitize_text_field($_POST['api_key']);
        $phone   = sanitize_text_field($_POST['phone']);
        if (!class_exists('TV_Notification_Engine')) {
            require_once TV_MANAGER_PATH . 'includes/class-tv-notification-engine.php';
        }
        if (class_exists('TV_Notification_Engine')) {
            $res = TV_Notification_Engine::send_wassenger_direct($api_key, $phone, 'Integration Active.');
            if ($res['success']) wp_send_json_success(['message' => 'Dispatched.']);
            else wp_send_json_error(['message' => 'Error: ' . $res['error']]);
        }
    }

    /* --- AJAX: Send Test Email --- */
    public function ajax_send_test_email(): void {
        check_ajax_referer('tv_send_test_email', '_nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized.']);
        }
        $key   = sanitize_text_field($_POST['template_key']   ?? '');
        $group = sanitize_text_field($_POST['template_group'] ?? 'subscription');
        $to    = sanitize_email($_POST['to_email']            ?? '');
        if (!is_email($to)) {
            wp_send_json_error(['message' => 'Invalid email address.']);
        }
        if ($group === 'auth') {
            // ─────────────────────────────────────────────────────────────
            // FIX: corrected path from 'includes/' to 'admin/classes/'
            // The file is at: admin/classes/class-tv-auth-notifications.php
            // The wrong path caused a PHP fatal → WordPress returned HTML
            // instead of JSON → client got "Unexpected token '<'" error.
            // ─────────────────────────────────────────────────────────────
            if (!class_exists('TV_Auth_Notifications')) {
                require_once TV_MANAGER_PATH . 'admin/classes/class-tv-auth-notifications.php';
            }
            $ok = TV_Auth_Notifications::send_test($key, $to);
        } else {
            // Subscription / payment templates — use TV_Notification_Engine
            if (!class_exists('TV_Notification_Engine')) {
                require_once TV_MANAGER_PATH . 'includes/class-tv-notification-engine.php';
            }
            $dummy = (object)[
                'id'      => 0,
                'user_id' => get_current_user_id(),
                'plan_id' => 0,
            ];
            $ctx = [
                'plan_name'     => 'Test Plan (Demo)',
                'days_left'     => 5,
                'days_passed'   => 21,
                'admin_message' => 'This is a sample admin note included for test purposes.',
                'user_name'     => 'Test User',
                'brand_name'    => get_bloginfo('name'),
                'login_url'     => home_url('/login'),
            ];
            $override = function($args) use ($to) {
                $args['to']      = $to;
                $args['subject'] = '[TEST] ' . $args['subject'];
                return $args;
            };
            add_filter('wp_mail', $override);
            TV_Notification_Engine::send_notification($dummy, $key, $ctx['admin_message'], true);
            remove_filter('wp_mail', $override);
            $ok = true;
        }
        if ($ok) {
            wp_send_json_success(['message' => 'Test email dispatched successfully.']);
        } else {
            wp_send_json_error(['message' => 'wp_mail() returned false. Check your SMTP settings under the Integrations tab.']);
        }
    }
}
