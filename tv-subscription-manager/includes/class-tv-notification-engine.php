<?php
declare(strict_types=1);
if (!defined('ABSPATH')) {
    exit;
}

/**
 * File: tv-subscription-manager/includes/class-tv-notification-engine.php
 * Path: tv-subscription-manager/includes/class-tv-notification-engine.php
 * Version: 4.2.0
 *
 * CHANGES FROM 4.1.3:
 *  - get_template(): FIXED empty test email shell bug.
 *    Previously, when $is_manual === true the method returned only the
 *    admin_message string as the entire body, bypassing the full template
 *    file. Test sends therefore arrived as a blank shell with one line of text.
 *    Now the full template file is always loaded first. A rich set of generic
 *    preview defaults fills every {{placeholder}} so test emails show the
 *    complete, fully-rendered email exactly as subscribers will see it.
 *    Real context values always override the preview defaults.
 *
 *  - render_wrapped_email(): Improved badge/accent mapping for rejected and
 *    reengage types. btn_url now falls back to home_url('/dashboard') if
 *    empty, preventing wrapper from rendering a buttonless email.
 *
 *  All other methods are IDENTICAL to version 4.1.3.
 */

class TV_Notification_Engine
{
    public const MAX_RETRIES = 3;
    public const BATCH_SIZE  = 50;

    // ---------------------------------------------------------------------
    // Public: daily cron entry point
    // ---------------------------------------------------------------------

    public static function run_daily_checks(): void
    {
        self::scheduler_expiry_checks();
        self::scheduler_reengagement_checks();
        self::process_retry_queue();
    }

    // ---------------------------------------------------------------------
    // Public: retry queue processor
    // ---------------------------------------------------------------------

    public static function process_retry_queue(): void
    {
        global $wpdb;
        $table_logs = $wpdb->prefix . 'tv_notification_logs';

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_logs)) !== $table_logs) {
            return;
        }

        $pending_retries = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_logs
            WHERE status = 'failed'
            AND retry_count < %d
            AND (next_retry IS NULL OR next_retry <= NOW())
            LIMIT %d",
            self::MAX_RETRIES,
            self::BATCH_SIZE
        ));

        if (empty($pending_retries)) {
            return;
        }

        foreach ($pending_retries as $log) {
            $sub_object = (object) [
                'id'      => $log->subscription_id,
                'user_id' => $log->user_id
            ];

            $dispatch_context = [];
            if (!empty($log->payload)) {
                $decoded = json_decode((string)$log->payload, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $dispatch_context = $decoded;
                }
            }

            self::attempt_delivery(
                (string)$log->channel,
                $sub_object,
                get_userdata((int)$log->user_id),
                (string)$log->type,
                $dispatch_context,
                true,
                (int)$log->id
            );
        }
    }

    // ---------------------------------------------------------------------
    // Private: scheduler — expiry checks
    // ---------------------------------------------------------------------

    private static function scheduler_expiry_checks(): void
    {
        global $wpdb;
        $table_subs = $wpdb->prefix . 'tv_subscriptions';
        $setting    = get_option('tv_notify_expiry_days', '7,3,1');

        $raw_config_parts = explode(',', $setting);
        $active_days_list = [];
        foreach ($raw_config_parts as $raw_part) {
            $numeric_val = (int) trim($raw_part);
            if ($numeric_val > 0) $active_days_list[] = $numeric_val;
        }

        foreach ($active_days_list as $day) {
            $start_window = date('Y-m-d 00:00:00', strtotime("+$day days"));
            $end_window   = date('Y-m-d 23:59:59', strtotime("+$day days"));

            $offset = 0;
            while (true) {
                $subs = $wpdb->get_results($wpdb->prepare(
                    "SELECT s.*, p.name as plan_name FROM $table_subs s
                    LEFT JOIN {$wpdb->prefix}tv_plans p ON s.plan_id = p.id
                    WHERE s.status = 'active' AND s.end_date BETWEEN %s AND %s LIMIT %d OFFSET %d",
                    $start_window, $end_window, self::BATCH_SIZE, $offset
                ));
                if (empty($subs)) break;
                foreach ($subs as $sub) {
                    self::queue_notification($sub, "expiry", ['plan_name' => (string)$sub->plan_name, 'days_left' => (int)$day]);
                }
                $offset += self::BATCH_SIZE;
            }
        }
    }

    // ---------------------------------------------------------------------
    // Private: scheduler — reengagement checks
    // ---------------------------------------------------------------------

    private static function scheduler_reengagement_checks(): void
    {
        if (get_option('tv_notify_reengage_enabled', '0') !== '1') return;

        global $wpdb;
        $table_subs    = $wpdb->prefix . 'tv_subscriptions';
        $lookback_date = date('Y-m-d', strtotime('-60 days'));

        $offset = 0;
        while (true) {
            $expired_subs = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*, p.name as plan_name FROM $table_subs s
                LEFT JOIN {$wpdb->prefix}tv_plans p ON s.plan_id = p.id
                WHERE s.status != 'cancelled' AND s.end_date < NOW() AND s.end_date > %s
                LIMIT %d OFFSET %d",
                $lookback_date, self::BATCH_SIZE, $offset
            ));
            if (empty($expired_subs)) break;
            foreach ($expired_subs as $sub) {
                $days_gone = (int) floor((time() - strtotime($sub->end_date)) / 86400);
                if ($days_gone > 0 && ($days_gone % 14 === 0)) {
                    self::queue_notification($sub, "reengage", ['plan_name' => (string)$sub->plan_name, 'days_passed' => $days_gone]);
                }
            }
            $offset += self::BATCH_SIZE;
        }
    }

    // ---------------------------------------------------------------------
    // Private: idempotency-guarded queue
    // ---------------------------------------------------------------------

    private static function queue_notification($sub, string $type, array $context = []): void
    {
        global $wpdb;
        $table_logs = $wpdb->prefix . 'tv_notification_logs';

        $idempotency_check = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_logs WHERE subscription_id = %d AND type = %s AND status = 'sent' AND sent_at > DATE_SUB(NOW(), INTERVAL 20 HOUR)",
            $sub->id, $type
        ));

        if (!$idempotency_check) {
            self::dispatch_to_channels($sub, $type, $context, false);
        }
    }

    // ---------------------------------------------------------------------
    // Private: fan-out to all enabled channels
    // ---------------------------------------------------------------------

    private static function dispatch_to_channels($sub, string $type, array $context, bool $is_manual = false): void
    {
        $user_data = get_userdata((int) $sub->user_id);
        if (!$user_data) return;

        $context['user_name']  = $user_data->display_name ?: $user_data->user_login;
        $context['brand_name'] = get_bloginfo('name');
        $context['login_url']  = home_url('/login');

        self::attempt_delivery('email', $sub, $user_data, $type, $context, false, null, $is_manual);

        if (get_option('tv_wassenger_api_key') || get_option('tv_notify_whatsapp_gateway')) {
            self::attempt_delivery('whatsapp', $sub, $user_data, $type, $context, false, null, $is_manual);
        }
    }

    // ---------------------------------------------------------------------
    // Private: single delivery attempt + audit log
    // ---------------------------------------------------------------------

    private static function attempt_delivery(
        string $channel, $sub, $user, string $type, array $context,
        bool $is_retry = false, ?int $existing_log_id = null, bool $is_manual = false
    ): void {
        global $wpdb;
        $table_logs = $wpdb->prefix . 'tv_notification_logs';

        if ($is_retry && $existing_log_id !== null) {
            $target_channel = $wpdb->get_var($wpdb->prepare("SELECT channel FROM $table_logs WHERE id=%d", $existing_log_id));
            if ($target_channel !== $channel) return;
        }

        $template_data = self::get_template($type, $context, $is_manual);
        $send_success  = false;
        $error_details = '';

        if ($channel === 'email') {
            $from_email    = get_option('tv_smtp_from_email', get_option('tv_smtp_user'));
            $from_name     = get_option('tv_smtp_from_name', get_bloginfo('name'));
            $support_email = get_option('tv_support_email', $from_email);

            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>',
                'Reply-To: ' . $from_name . ' <' . $support_email . '>'
            ];

            $html_output  = self::render_wrapped_email($template_data, $type);
            $send_success = wp_mail($user->user_email, $template_data['subject'], $html_output, $headers);

            if (!$send_success) $error_details = 'SMTP Rejection. Ensure Stockholm identity is verified.';

        } elseif ($channel === 'whatsapp') {
            $phone = get_user_meta($user->ID, 'phone', true) ?: get_user_meta($user->ID, 'billing_phone', true);
            if (!empty($phone)) {
                $wassenger_token = get_option('tv_wassenger_api_key');
                $api_res = $wassenger_token
                    ? self::send_wassenger_direct((string)$wassenger_token, (string)$phone, (string)$template_data['body'])
                    : self::send_whatsapp_api((string)$phone, (string)$template_data['body']);
                $send_success  = (bool)$api_res['success'];
                $error_details = (string)($api_res['error'] ?? '');
            } else {
                $error_details = 'Aborted: No phone found.';
            }
        }

        $final_status  = ($send_success) ? 'sent' : 'failed';
        $next_run_time = null;
        $attempt_count = 0;

        if (!$send_success) {
            $old_val       = ($is_retry && $existing_log_id) ? (int)$wpdb->get_var($wpdb->prepare("SELECT retry_count FROM $table_logs WHERE id=%d", $existing_log_id)) : 0;
            $attempt_count = $old_val + 1;
            $next_run_time = date('Y-m-d H:i:s', time() + (int)(pow(2, $attempt_count) * 600));
        }

        $audit_data = [
            'user_id'         => (int)$user->ID,
            'subscription_id' => (int)$sub->id,
            'type'            => $type,
            'channel'         => $channel,
            'status'          => $final_status,
            'message'         => (string)$template_data['body'],
            'payload'         => json_encode($context),
            'error_msg'       => $error_details,
            'retry_count'     => $attempt_count,
            'next_retry'      => $next_run_time,
            'sent_at'         => current_time('mysql'),
            'is_manual'       => $is_manual ? 1 : 0
        ];

        if ($is_retry && $existing_log_id) {
            $wpdb->update($table_logs, $audit_data, ['id' => $existing_log_id]);
        } else {
            $wpdb->insert($table_logs, $audit_data);
        }
    }

    // ---------------------------------------------------------------------
    // Public: Wassenger direct send
    // ---------------------------------------------------------------------

    public static function send_wassenger_direct(string $api_key, string $phone, string $message): array
    {
        $cleaned = '+' . preg_replace('/[^0-9]/', '', $phone);
        $res = wp_remote_post('https://api.wassenger.com/v1/messages', [
            'body'    => json_encode(['phone' => $cleaned, 'message' => $message]),
            'headers' => ['Content-Type: application/json', 'Token' => $api_key],
            'timeout' => 25
        ]);
        if (is_wp_error($res)) return ['success' => false, 'error' => $res->get_error_message()];
        $code = wp_remote_retrieve_response_code($res);
        if ($code >= 200 && $code < 300) return ['success' => true];
        $json = json_decode(wp_remote_retrieve_body($res), true);
        return ['success' => false, 'error' => $json['message'] ?? "HTTP $code"];
    }

    // ---------------------------------------------------------------------
    // Private: generic WhatsApp gateway send
    // ---------------------------------------------------------------------

    private static function send_whatsapp_api(string $phone, string $msg): array
    {
        $url = get_option('tv_notify_whatsapp_gateway');
        if (!$url) return ['success' => false, 'error' => 'Gateway URL empty'];
        $res = wp_remote_post((string)$url, [
            'body'    => json_encode(['phone' => $phone, 'message' => $msg, 'key' => get_option('tv_notify_whatsapp_key')]),
            'headers' => ['Content-Type: application/json'],
            'timeout' => 15
        ]);
        if (is_wp_error($res)) return ['success' => false, 'error' => $res->get_error_message()];
        return wp_remote_retrieve_response_code($res) < 300
            ? ['success' => true]
            : ['success' => false, 'error' => 'Gateway error'];
    }

    // ---------------------------------------------------------------------
    // Private: get_template
    //
    // FIXED in v4.2.0:
    //   Previously when $is_manual === true AND admin_message was present,
    //   the method returned immediately with only the admin_message as the body,
    //   skipping the entire template file. Test emails arrived as a one-line shell.
    //
    //   Now: the full template file is always loaded. A $preview_defaults array
    //   fills every {{placeholder}} that real context did not supply, so test
    //   sends render the complete, fully-styled email. Real context values always
    //   override preview defaults via array_merge ordering.
    // ---------------------------------------------------------------------

    private static function get_template(string $type, array $context, bool $is_manual = false): array
    {
        $file_map = [
            'payment_proof_uploaded' => 'payment-proof',
            'payment_approved'       => 'payment-approved',
            'payment_rejected'       => 'payment-rejected',
            'expiry'                 => 'expiry-alert',
            'reengage'               => 'reengage',
        ];

        $clean_type = $type;
        if (strpos($type, 'expiry')   === 0) $clean_type = 'expiry';
        if (strpos($type, 'reengage') === 0) $clean_type = 'reengage';

        // Default skeleton — overwritten by the template file below
        $template = [
            'subject'  => 'Account Notification',
            'body'     => '<p>Please log in to your dashboard to view your account details.</p>',
            'btn_text' => 'Go to Dashboard',
            'btn_url'  => home_url('/dashboard'),
        ];

        // Generic preview data: fills every {{placeholder}} when there is no
        // real subscription context (e.g. admin test send from the settings panel).
        // Real context values win because array_merge puts $context last.
        $preview_defaults = [
            'user_name'     => 'John Smith',
            'user_email'    => 'john.smith@example.com',
            'brand_name'    => get_bloginfo('name'),
            'plan_name'     => 'Premium 12-Month Plan',
            'days_left'     => '5',
            'days_passed'   => '21',
            'login_url'     => home_url('/login'),
            'reset_url'     => home_url('/?action=rp&key=DEMO_KEY&login=demo'),
            'expiry_time'   => '24 hours',
            'changed_at'    => date('d M Y, H:i') . ' UTC',
            'admin_message' => 'Your payment proof was clear and your subscription has been fully activated. Enjoy the service!',
        ];

        // Merge: real context values override preview defaults
        $merged_context = array_merge($preview_defaults, $context);

        // Load template file
        if (isset($file_map[$clean_type])) {
            $path = TV_MANAGER_PATH . 'includes/templates/emails/' . $file_map[$clean_type] . '.php';
            if (file_exists($path)) {
                $file_data = include $path;
                if (is_array($file_data)) {
                    $template = array_merge($template, $file_data);
                }
            }
        }

        // Admin override from saved notification settings
        $saved = get_option('tv_notification_templates', []);
        if (isset($saved[$clean_type])) {
            if (!empty($saved[$clean_type]['subject'])) $template['subject'] = (string)$saved[$clean_type]['subject'];
            if (!empty($saved[$clean_type]['body']))    $template['body']    = (string)$saved[$clean_type]['body'];
        }

        // Variable substitution using merged context
        foreach ($merged_context as $k => $v) {
            $placeholder         = '{{{' . $k . '}}}';
            $template['subject'] = str_replace($placeholder, (string)$v, $template['subject']);
            $template['body']    = str_replace($placeholder, (string)$v, $template['body']);
        }

        // Ensure btn_url is always set
        if (empty($template['btn_url'])) {
            $template['btn_url'] = home_url('/dashboard');
        }

        return $template;
    }

    // ---------------------------------------------------------------------
    // Private: render_wrapped_email
    //
    // Improved in v4.2.0:
    //   - Distinct badge/accent for rejected, reengage types
    //   - btn_url falls back to dashboard URL if empty
    // ---------------------------------------------------------------------

    private static function render_wrapped_email(array $template, string $type): string
    {
        $badge_label = 'Notification';
        $accent      = '#4f46e5';
        $bg          = '#ede9fe';

        if (strpos($type, 'approved') !== false) {
            $badge_label = 'Confirmed';
            $accent      = '#10b981';
            $bg          = '#ecfdf5';
        } elseif (strpos($type, 'rejected') !== false) {
            $badge_label = 'Action Required';
            $accent      = '#ef4444';
            $bg          = '#fee2e2';
        } elseif (strpos($type, 'expiry') !== false) {
            $badge_label = 'Expiring Soon';
            $accent      = '#f59e0b';
            $bg          = '#fef3c7';
        } elseif (strpos($type, 'proof') !== false) {
            $badge_label = 'Received';
            $accent      = '#3b82f6';
            $bg          = '#eff6ff';
        } elseif (strpos($type, 'reengage') !== false) {
            $badge_label = 'We Miss You';
            $accent      = '#8b5cf6';
            $bg          = '#f5f3ff';
        }

        $wrapper_path = TV_MANAGER_PATH . 'includes/templates/emails/email-wrapper.php';

        if (file_exists($wrapper_path)) {
            extract([
                'body_content' => $template['body'],
                'title'        => $template['subject'],
                'badge_label'  => $badge_label,
                'badge_bg'     => $bg,
                'accent_hex'   => $accent,
                'btn_url'      => $template['btn_url'] ?? home_url('/dashboard'),
                'btn_text'     => $template['btn_text'] ?? 'Go to Dashboard',
            ]);
            ob_start();
            include $wrapper_path;
            return ob_get_clean();
        }

        // Bare fallback if wrapper file is missing
        return "<html><body style='font-family:Arial,sans-serif;padding:40px;'>
            <h2>{$template['subject']}</h2>
            <div>{$template['body']}</div>
            <p><a href='" . home_url('/dashboard') . "'>Go to Dashboard</a></p>
        </body></html>";
    }

    // ---------------------------------------------------------------------
    // Public: send_notification — called by admin actions and AJAX
    // ---------------------------------------------------------------------

    public static function send_notification($sub, string $type, string $msg, bool $is_manual = false): void
    {
        $sub_id  = (int)($sub->id ?? 0);
        $plan_id = (int)($sub->plan_id ?? 0);

        if ($plan_id === 0 && $sub_id > 0) {
            global $wpdb;
            $plan_id = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT plan_id FROM {$wpdb->prefix}tv_subscriptions WHERE id = %d", $sub_id
            ));
        }

        $plan_name = 'Premium Service';
        if ($plan_id > 0) {
            global $wpdb;
            $name = $wpdb->get_var($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}tv_plans WHERE id = %d", $plan_id
            ));
            if ($name) $plan_name = (string)$name;
        }

        $ctx = [
            'plan_name'     => $plan_name,
            'admin_message' => $msg,
            'login_url'     => home_url('/login'),
        ];

        if ($is_manual) {
            self::attempt_delivery(
                (string)get_option('tv_default_channel', 'email'),
                $sub,
                get_userdata((int)$sub->user_id),
                $type,
                $ctx,
                false,
                null,
                true
            );
        } else {
            self::queue_notification($sub, $type, $ctx);
        }
    }
}