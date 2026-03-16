<?php
if (!defined('ABSPATH')) { exit; }

trait TV_Subscription_Mobile_Admin_Rest_Settings_Logs_Trait {

    public static function get_settings() : WP_REST_Response {
            return new WP_REST_Response([
                'general' => [
                    'multi_step' => (int)get_option('tv_multi_step_checkout', 0),
                    'discounts' => get_option('tv_duration_discounts', []),
                ],
                'support' => [
                    'whatsapp' => get_option('tv_support_whatsapp', ''),
                    'email' => get_option('tv_support_email', ''),
                    'telegram' => get_option('tv_support_telegram', ''),
                ],
                'notifications' => [
                    'expiry_days' => get_option('tv_notify_expiry_days', '7,3,1'),
                    'reengage_enabled' => (int)get_option('tv_notify_reengage_enabled', 0),
                    'whatsapp_gateway' => get_option('tv_notify_whatsapp_gateway', ''),
                    'whatsapp_key' => get_option('tv_notify_whatsapp_key', ''),
                    'templates' => get_option('tv_notification_templates', []),
                ],
                'panels' => get_option('tv_panel_configs', []),
                'integrations' => [
                    'currencies' => get_option('tv_allowed_currencies', []),
                    'whatsapp_custom_msg' => get_option('tv_whatsapp_custom_msg', ''),
                    'trial_overlay_delay' => (int)get_option('tv_trial_overlay_delay', 5),
                ],
                'pages' => [
                    'plans' => (int)get_option('tv_plans_page_id', 0),
                    'method' => (int)get_option('tv_select_method_page_id', 0),
                    'payment' => (int)get_option('tv_payment_page_id', 0),
                    'proof' => (int)get_option('tv_upload_proof_page_id', 0),
                ],
            ], 200);
        }

    public static function update_settings(WP_REST_Request $req) : WP_REST_Response {
            // General
            if (isset($req['general'])) {
                update_option('tv_multi_step_checkout', (int)$req['general']['multi_step']);
                if (isset($req['general']['discounts'])) update_option('tv_duration_discounts', $req['general']['discounts']);
            }
            // Support
            if (isset($req['support'])) {
                update_option('tv_support_whatsapp', sanitize_text_field((string)$req['support']['whatsapp']));
                update_option('tv_support_email', sanitize_email((string)$req['support']['email']));
                update_option('tv_support_telegram', sanitize_text_field((string)$req['support']['telegram']));
            }
            // Notifications
            if (isset($req['notifications'])) {
                update_option('tv_notify_expiry_days', sanitize_text_field((string)$req['notifications']['expiry_days']));
                update_option('tv_notify_reengage_enabled', (int)$req['notifications']['reengage_enabled']);
                update_option('tv_notify_whatsapp_gateway', esc_url_raw((string)$req['notifications']['whatsapp_gateway']));
                update_option('tv_notify_whatsapp_key', sanitize_text_field((string)$req['notifications']['whatsapp_key']));
                if (isset($req['notifications']['templates'])) update_option('tv_notification_templates', $req['notifications']['templates']);
            }
            // Panels
            if (isset($req['panels'])) update_option('tv_panel_configs', $req['panels']);
            // Integrations
            if (isset($req['integrations'])) {
                if (isset($req['integrations']['currencies'])) update_option('tv_allowed_currencies', $req['integrations']['currencies']);
                update_option('tv_whatsapp_custom_msg', sanitize_textarea_field((string)$req['integrations']['whatsapp_custom_msg']));
                update_option('tv_trial_overlay_delay', (int)$req['integrations']['trial_overlay_delay']);
            }
            // Pages
            if (isset($req['pages'])) {
                update_option('tv_plans_page_id', (int)$req['pages']['plans']);
                update_option('tv_select_method_page_id', (int)$req['pages']['method']);
                update_option('tv_payment_page_id', (int)$req['pages']['payment']);
                update_option('tv_upload_proof_page_id', (int)$req['pages']['proof']);
            }
    
            self::log_event('Update Settings', 'Mobile admin saved settings.');
            return new WP_REST_Response(['msg' => 'Settings Saved'], 200);
        }

    public static function get_system_health() : WP_REST_Response {
            global $wpdb;
            return new WP_REST_Response([
                'status' => 'ok',
                'cron' => wp_next_scheduled('tv_daily_notification_check') ? 'OK' : 'Stopped',
                'db_ver' => $wpdb->db_version,
            ], 200);
        }

    public static function get_logs() : WP_REST_Response {
            global $wpdb;
            return new WP_REST_Response($wpdb->get_results("SELECT * FROM {$wpdb->prefix}tv_activity_logs ORDER BY date DESC LIMIT 50"), 200);
        }

}
