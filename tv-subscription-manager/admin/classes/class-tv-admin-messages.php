<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit; // Direct access prevention
}

/**
 * Class TV_Admin_Messages
 * * Handles the Hero Banner (Dashboard Carousel) and the Bulk Messaging Engine.
 * Hardened for XOFLIX TV v3.9.15
 */
class TV_Admin_Messages extends TV_Admin_Base {

    protected $table_news;
    private string $last_api_response_debug = '';

    /**
     * Constructor: Register AJAX and setup tables.
     */
    public function __construct($wpdb) {
        parent::__construct($wpdb);
        $this->table_news = $wpdb->prefix . 'tv_announcements';
        
        // AJAX: Proxy for testing individual API configurations without saving.
        add_action('wp_ajax_tv_test_api_integration', [$this, 'ajax_test_api_integration']);
    }

    /**
     * Primary action router for Messaging & News.
     */
    public function handle_actions(): void {
        
        // --- 1. BULK EMAIL BROADCAST ---
        if (isset($_POST['send_bulk_email'])) {
            check_admin_referer('bulk_email_verify');

            // CRITICAL: Activate Bulk Mode to bypass SMTP authenticated connector.
            // This prevents info@xoflix.tv from being rate-limited or banned.
            if (!defined('TV_IS_BULK_OPERATION')) {
                define('TV_IS_BULK_OPERATION', true);
            }

            $filter_plan   = intval($_POST['filter_plan']);
            $filter_status = sanitize_text_field($_POST['filter_status']);
            $subject       = sanitize_text_field($_POST['email_subject']);
            $body          = wp_kses_post(wp_unslash($_POST['email_body']));
            $send_method   = isset($_POST['broadcast_method']) ? sanitize_text_field($_POST['broadcast_method']) : 'wp_mail';

            // Filter Validation
            $allowed_statuses = ['all', 'active', 'pending', 'expired'];
            if (!in_array($filter_status, $allowed_statuses, true)) {
                $filter_status = 'all';
            }

            $where = ['1=1'];
            $params = [];
            if ($filter_plan > 0) {
                $where[] = 's.plan_id = %d';
                $params[] = $filter_plan;
            }
            if ($filter_status !== 'all') {
                $where[] = 's.status = %s';
                $params[] = $filter_status;
            }

            $sql = "SELECT DISTINCT u.ID, u.user_email, u.display_name, u.user_login
                    FROM {$this->table_subs} s
                    JOIN {$this->wpdb->users} u ON s.user_id = u.ID
                    WHERE " . implode(' AND ', $where);

            $users = empty($params) ? $this->wpdb->get_results($sql) : $this->wpdb->get_results($this->wpdb->prepare($sql, $params));

            $count = 0;
            $fail_count = 0;
            $table_notify_logs = $this->wpdb->prefix . 'tv_notification_logs';

            if ($users) {
                $api_config = null;
                if ($send_method !== 'wp_mail') {
                    $all_apis = get_option('tv_mailing_apis', []);
                    if (isset($all_apis[$send_method])) {
                        $api_config = $all_apis[$send_method];
                    }
                }

                foreach ($users as $user) {
                    $sent = false;
                    $error_log = '';

                    if ($api_config) {
                        // ROUTE A: Third-party REST API (Mailjet, etc.)
                        $sent = $this->send_via_custom_api($api_config, $user, $subject, $body);
                        if (!$sent) { 
                            $error_log = substr($this->last_api_response_debug, 0, 250); 
                        }
                    } else {
                        // ROUTE B: Default Server Mail (SMTP Bypass Active)
                        $sent = wp_mail($user->user_email, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
                        if (!$sent) { 
                            $error_log = 'Default PHPMail failure (Bulk Mode).'; 
                        }
                    }

                    if ($sent) $count++; else $fail_count++;

                    // Write to Audit Logs
                    $this->wpdb->insert($table_notify_logs, [
                        'user_id' => $user->ID,
                        'subscription_id' => 0,
                        'type' => 'broadcast',
                        'channel' => 'email',
                        'status' => $sent ? 'sent' : 'failed',
                        'message' => substr(wp_strip_all_tags($body), 0, 100) . '...',
                        'error_msg' => $error_log,
                        'sent_at' => current_time('mysql'),
                        'is_manual' => 1
                    ]);
                }

                $method_name = $api_config ? 'API: ' . $api_config['name'] : 'Non-SMTP Server Mail';
                $this->log_event('Bulk Broadcast', "Sent: $count, Failed: $fail_count via $method_name.");
                
                if ($fail_count > 0) {
                    $this->show_notice("Broadcast complete. Sent: $count, Failed: $fail_count.", 'warning');
                } else {
                    $this->show_notice("Broadcast successfully delivered to $count subscribers.");
                }
            } else {
                $this->show_notice("No subscribers matched your criteria.", 'info');
            }
        }

        // --- 2. HERO SLIDE MANAGEMENT ---
        if (isset($_POST['post_announcement'])) {
            check_admin_referer('post_announcement_verify');
            
            $action_val = isset($_POST['news_btn_action']) ? trim($_POST['news_btn_action']) : 'dashboard';
            $color_val  = sanitize_text_field($_POST['news_color']);
            
            if ($color_val === 'custom' && !empty($_POST['news_color_custom'])) {
                $color_val = sanitize_text_field($_POST['news_color_custom']);
                if (ctype_xdigit(ltrim($color_val, '#')) && strlen(ltrim($color_val, '#')) <= 6) { 
                    $color_val = '#' . ltrim($color_val, '#'); 
                }
            }

            $data = [
                'title'         => sanitize_text_field($_POST['news_title']),
                'message'       => sanitize_textarea_field($_POST['news_message']),
                'button_text'   => sanitize_text_field($_POST['news_btn_text']),
                'button_action' => sanitize_text_field($action_val), 
                'color_scheme'  => $color_val,
                'status'        => 'active'
            ];

            if (!empty($_POST['slide_id'])) {
                $slide_id = intval($_POST['slide_id']);
                $this->wpdb->update($this->table_news, $data, ['id' => $slide_id]);
                $this->log_event('Update Hero Slide', "Updated ID: $slide_id");
                $this->show_notice("Hero slide updated.");
            } else {
                $data['start_date'] = current_time('mysql');
                $this->wpdb->insert($this->table_news, $data);
                $this->log_event('Post Hero Slide', "Created: " . $data['title']);
                $this->show_notice("Hero slide added to the Dashboard.");
            }
        }

        // Delete Slide
        if (isset($_GET['action']) && $_GET['action'] == 'delete_news' && isset($_GET['id'])) {
            check_admin_referer('delete_news_' . $_GET['id']);
            if (!$this->tv_require_delete_verification_or_notice()) return;
            $id = intval($_GET['id']);
            if ($this->recycle_bin_soft_delete('hero_slide', $this->table_news, (int)$id, 'id')) {
                $this->log_event('Delete Hero Slide', "Soft-deleted ID: $id");
                $this->show_notice("Slide removed.");
            }
        }

        // --- 3. LOG RETRY / RESEND ---
        if (isset($_GET['action']) && $_GET['action'] === 'resend_notify' && isset($_GET['log_id'])) {
            check_admin_referer('resend_notify_' . $_GET['log_id']);
            $log_id = intval($_GET['log_id']);
            $table_logs = $this->wpdb->prefix . 'tv_notification_logs';
            $log = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM $table_logs WHERE id = %d", $log_id));
            
            if ($log) {
                $all_apis = get_option('tv_mailing_apis', []);
                $api_config = !empty($all_apis) ? reset($all_apis) : null; 
                $sent_ok = false;

                if ($api_config && $log->channel === 'email') {
                    $user = get_userdata($log->user_id);
                    if ($user) {
                        $subject = "Notification Resend: " . ucfirst($log->type);
                        $sent_ok = $this->send_via_custom_api($api_config, $user, $subject, $log->message);
                        if ($sent_ok) {
                            $this->wpdb->update($table_logs, ['status' => 'sent', 'sent_at' => current_time('mysql')], ['id' => $log_id]);
                            $this->show_notice("Notification resent via API Integration.");
                        }
                    }
                } else {
                    $sub = (object) ['id' => $log->subscription_id, 'user_id' => $log->user_id];
                    if (class_exists('TV_Notification_Engine')) {
                        TV_Notification_Engine::send_notification($sub, $log->type, $log->message, true);
                        $this->show_notice("Queued for standard retry through Notification Engine.");
                    }
                }
            }
        }

        // --- 4. API INTEGRATION SETTINGS ---
        if (isset($_POST['save_api_integration'])) {
            check_admin_referer('tv_save_api_config', '_tv_api_nonce');
            $config = [
                'id'      => sanitize_text_field($_POST['api_id']),
                'name'    => sanitize_text_field($_POST['api_name']),
                'method'  => sanitize_text_field($_POST['api_method']),
                'url'     => esc_url_raw($_POST['api_url']),
                'headers' => [],
                'body'    => wp_unslash($_POST['api_body'])
            ];
            if (isset($_POST['header_keys']) && is_array($_POST['header_keys'])) {
                foreach ($_POST['header_keys'] as $index => $key) {
                    $key = sanitize_text_field($key); 
                    $val = sanitize_text_field($_POST['header_values'][$index] ?? '');
                    if (!empty($key)) { $config['headers'][] = ['key' => $key, 'value' => $val]; }
                }
            }
            $all_apis = get_option('tv_mailing_apis', []);
            $all_apis[$config['id']] = $config;
            update_option('tv_mailing_apis', $all_apis);
            $this->show_notice("API Integration '{$config['name']}' saved.");
        }

        if (isset($_GET['action']) && $_GET['action'] == 'delete_api' && isset($_GET['api_id'])) {
            check_admin_referer('delete_api_' . $_GET['api_id']);
            if (!$this->tv_require_delete_verification_or_notice()) return;
            $api_id = sanitize_text_field($_GET['api_id']);
            $all_apis = get_option('tv_mailing_apis', []);
            if (isset($all_apis[$api_id])) {
                unset($all_apis[$api_id]);
                update_option('tv_mailing_apis', $all_apis);
                $this->show_notice("API Integration deleted.");
            }
        }
    }

    /**
     * Executes an authenticated request to an external mail API.
     */
    private function send_via_custom_api(array $config, WP_User $user, string $subject, string $message): bool {
        $url = $config['url']; 
        $method = $config['method']; 
        $body_template = $config['body'];
        $headers = [];

        if (!empty($config['headers']) && is_array($config['headers'])) {
            foreach ($config['headers'] as $h) { 
                if (!empty($h['key'])) $headers[$h['key']] = $h['value']; 
            }
        }

        $vars = [
            '{{email}}'        => $user->user_email, 
            '{{name}}'         => $user->display_name ?: $user->user_login, 
            '{{username}}'     => $user->user_login, 
            '{{subject}}'      => $subject, 
            '{{message}}'      => $message, 
            '{{sender_email}}' => get_option('tv_support_email', get_option('admin_email'))
        ];

        $final_body = '';
        if ($method !== 'GET') {
            $json_template = json_decode($body_template, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json_template)) {
                array_walk_recursive($json_template, function(&$item) use ($vars) { 
                    if (is_string($item)) $item = str_replace(array_keys($vars), array_values($vars), $item); 
                });
                $final_body = json_encode($json_template);
            } else { 
                $final_body = str_replace(array_keys($vars), array_values($vars), $body_template); 
            }
        } else { 
            $url = str_replace(array_keys($vars), array_values($vars), $url); 
        }

        $args = [
            'method'    => $method, 
            'headers'   => $headers, 
            'body'      => $final_body, 
            'timeout'   => 20, 
            'sslverify' => false, 
            'blocking'  => true
        ];

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) { 
            $this->last_api_response_debug = 'WP Error: ' . $response->get_error_message(); 
            return false; 
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $this->last_api_response_debug = "HTTP $code: " . substr($body, 0, 300);

        if ($code >= 200 && $code < 300) {
            $json = json_decode($body, true);
            if ($json && isset($json['Messages'][0]['Status']) && $json['Messages'][0]['Status'] === 'error') {
                $errors = $json['Messages'][0]['Errors'] ?? [];
                foreach ($errors as $e) { if (($e['ErrorCode'] ?? '') === 'mj-0003') $this->last_api_response_debug = "Mailjet: Sender email not verified."; }
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * AJAX Helper to verify an API integration's reachability.
     */
    public function ajax_test_api_integration(): void {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('tv_test_api_nonce', 'nonce');

        $method      = sanitize_text_field($_POST['method']); 
        $url         = esc_url_raw($_POST['url']);
        $headers_raw = isset($_POST['headers']) ? $_POST['headers'] : []; 
        $body_raw    = isset($_POST['body']) ? wp_unslash($_POST['body']) : '';
        $headers     = [];

        if (is_array($headers_raw)) { 
            foreach ($headers_raw as $h) { if (!empty($h['key'])) $headers[$h['key']] = $h['value']; } 
        }

        $current_user = wp_get_current_user();
        $vars = [
            '{{email}}'    => $current_user->user_email, 
            '{{name}}'     => $current_user->display_name, 
            '{{username}}' => $current_user->user_login, 
            '{{subject}}'  => 'Test Notification', 
            '{{message}}'  => 'API Test Run: Success', 
            '{{sender_email}}' => get_option('tv_support_email')
        ];

        $body_processed = '';
        if ($method !== 'GET' && !empty($body_raw)) {
            $json_template = json_decode($body_raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json_template)) {
                array_walk_recursive($json_template, function(&$item) use ($vars) { if (is_string($item)) $item = str_replace(array_keys($vars), array_values($vars), $item); });
                $body_processed = json_encode($json_template);
            } else { 
                $body_processed = str_replace(array_keys($vars), array_values($vars), $body_raw); 
            }
        } else { 
            $url = str_replace(array_keys($vars), array_values($vars), $url); 
        }

        $args = ['method' => $method, 'headers' => $headers, 'timeout' => 15, 'sslverify' => false];
        if ($method !== 'GET' && !empty($body_processed)) $args['body'] = $body_processed;

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            wp_send_json_error(['body' => $response->get_error_message()]);
        }

        $code = wp_remote_retrieve_response_code($response); 
        $res_body = wp_remote_retrieve_body($response);
        
        if ($code >= 200 && $code < 300) {
            wp_send_json_success(['code' => $code, 'body' => substr($res_body, 0, 500)]);
        } else {
            wp_send_json_error(['code' => $code, 'body' => substr($res_body, 0, 500)]);
        }
    }

    /**
     * Render the Messaging Hub.
     */
    public function render(): void {
        $plans = $this->wpdb->get_results("SELECT * FROM $this->table_plans");
        $news_items = [];
        if ($this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $this->table_news)) === $this->table_news) {
            $news_items = $this->wpdb->get_results("SELECT * FROM $this->table_news ORDER BY start_date DESC");
        }

        $edit_slide = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit_news' && isset($_GET['id'])) {
            $edit_id = intval($_GET['id']);
            $edit_slide = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM $this->table_news WHERE id = %d", $edit_id));
        }

        $table_logs = $this->wpdb->prefix . 'tv_notification_logs';
        $logs = [];
        if ($this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $table_logs)) === $table_logs) {
            $logs = $this->wpdb->get_results("SELECT l.*, u.user_email FROM $table_logs l LEFT JOIN {$this->wpdb->users} u ON l.user_id = u.ID ORDER BY l.sent_at DESC LIMIT 50");
        }

        $api_integrations = get_option('tv_mailing_apis', []);
        include TV_MANAGER_PATH . 'admin/views/view-bulk-messages.php';
    }
}