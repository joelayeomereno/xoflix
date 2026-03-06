<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Trait: TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Part_05
 * Path: /tv-subscription-mobile-admin/includes/rest/traits/trait-tv-subscription-mobile-admin-includes-rest-mobile-admin-rest-trait-part-05.php
 */
trait TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Part_05 {


    public static function manage_user_subscription(WP_REST_Request $req) : WP_REST_Response {
        global $wpdb;
        $data = [
            'plan_id' => (int)$req['plan_id'],
            'status' => sanitize_text_field($req['status']),
            'start_date' => sanitize_text_field($req['start_date']),
            'end_date' => sanitize_text_field($req['end_date']),
            'connections' => (int)$req['connections'] ?: 1
        ];
        
        if($req['sub_id']) {
            $wpdb->update("{$wpdb->prefix}tv_subscriptions", $data, ['id'=>(int)$req['sub_id']]);
        } else {
            $data['user_id'] = (int)$req['id'];
            $wpdb->insert("{$wpdb->prefix}tv_subscriptions", $data);
        }
        
        self::log_event('Admin Manage Subscription', "Updated subscription for user: " . $req['id']);
        return new WP_REST_Response(['msg'=>'Saved'], 200);
    }

    public static function handle_bulk_users(WP_REST_Request $req) : WP_REST_Response {
        global $wpdb;
        $ids = array_map('intval', (array)$req->get_param('ids'));
        $action = $req->get_param('action');
        $count = 0;

        foreach($ids as $uid) {
            if($uid<=0) continue;
            if($action === 'delete_user') {
                $sub_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}tv_subscriptions WHERE user_id=%d", $uid));
                // Soft delete related subs
                foreach($sub_ids as $sid) self::soft_delete_entity('subscription', "{$wpdb->prefix}tv_subscriptions", $sid);
                // Delete user (WP handles user meta)
                wp_delete_user($uid);
                $count++;
            } elseif ($action === 'activate_sub') {
                $sub_id = $wpdb->get_var($wpdb->prepare("SELECT MAX(id) FROM {$wpdb->prefix}tv_subscriptions WHERE user_id=%d", $uid));
                if($sub_id) $wpdb->update("{$wpdb->prefix}tv_subscriptions", ['status'=>'active'], ['id'=>$sub_id]);
                $count++;
            }
        }
        self::log_event('Bulk User Action', "$action on $count users");
        return new WP_REST_Response(['msg'=>"$count processed"], 200);
    }

    // -----------------------
    // System Settings
    // -----------------------

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
