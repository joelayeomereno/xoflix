<?php
if (!defined('ABSPATH')) { exit; }

trait TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Catalog {
    public static function get_plans() : WP_REST_Response {
        global $wpdb;
        $plans = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tv_plans ORDER BY price ASC");
        foreach ($plans as $p) $p->tiers = !empty($p->discount_tiers) ? json_decode($p->discount_tiers) : [];
        return new WP_REST_Response($plans, 200);
    }

    public static function create_plan(WP_REST_Request $req) : WP_REST_Response {
        global $wpdb;
        $data = [
            'name' => sanitize_text_field((string)$req['name']),
            'price' => (float)$req['price'],
            'duration_days' => (int)$req['duration_days'] ?: 30,
            'allow_multi_connections' => !empty($req['multi']) ? 1 : 0,
            'description' => wp_kses_post((string)$req['description']),
            'discount_tiers' => $req->has_param('tiers') ? wp_json_encode($req['tiers']) : null,
        ];
        $wpdb->insert("{$wpdb->prefix}tv_plans", $data);
        self::log_event('Create Plan', "Created plan: " . $data['name']);
        return new WP_REST_Response(['ok' => true], 200);
    }

    public static function update_plan(WP_REST_Request $req) : WP_REST_Response {
        global $wpdb;
        $data = [
            'name' => sanitize_text_field((string)$req['name']),
            'price' => (float)$req['price'],
            'duration_days' => (int)$req['duration_days'],
            'allow_multi_connections' => !empty($req['multi']) ? 1 : 0,
            'description' => wp_kses_post((string)$req['description']),
            'discount_tiers' => $req->has_param('tiers') ? wp_json_encode($req['tiers']) : null,
        ];
        $wpdb->update("{$wpdb->prefix}tv_plans", $data, ['id' => (int)$req['id']]);
        self::log_event('Update Plan', "Updated plan ID: " . (int)$req['id']);
        return new WP_REST_Response(['ok' => true], 200);
    }

    public static function delete_plan(WP_REST_Request $req) : WP_REST_Response {
        if (self::soft_delete_entity('plan', $GLOBALS['wpdb']->prefix.'tv_plans', (int)$req['id'])) {
            self::log_event('Delete Plan', "Soft-deleted plan ID: {$req['id']}");
            return new WP_REST_Response(['ok' => true], 200);
        }
        return new WP_REST_Response(['error' => 'Delete failed'], 500);
    }

    // -----------------------
    // Coupons
    // -----------------------

    public static function get_coupons() : WP_REST_Response {
        global $wpdb;
        return new WP_REST_Response($wpdb->get_results("SELECT * FROM {$wpdb->prefix}tv_coupons ORDER BY id DESC"), 200);
    }

    public static function create_coupon(WP_REST_Request $req) : WP_REST_Response {
        global $wpdb;
        $data = [
            'code' => strtoupper(sanitize_text_field((string)$req['code'])),
            'amount' => (float)$req['amount'],
            'usage_limit' => (int)$req['limit'],
            'usage_count' => 0,
            'expiry_date' => !empty($req['expiry_date']) ? sanitize_text_field((string)$req['expiry_date']) : null,
            'type' => $req->get_param('type') === 'fixed' ? 'fixed' : 'percent'
        ];
        $wpdb->insert("{$wpdb->prefix}tv_coupons", $data);
        self::log_event('Create Coupon', "Created coupon: " . $data['code']);
        return new WP_REST_Response(['ok' => true], 200);
    }

    public static function delete_coupon(WP_REST_Request $req) : WP_REST_Response {
        if (self::soft_delete_entity('coupon', $GLOBALS['wpdb']->prefix.'tv_coupons', (int)$req['id'])) {
            self::log_event('Delete Coupon', "Soft-deleted coupon ID: {$req['id']}");
            return new WP_REST_Response(['ok' => true], 200);
        }
        return new WP_REST_Response(['error' => 'Delete failed'], 500);
    }

    // -----------------------
    // Methods
    // -----------------------

    public static function get_methods() : WP_REST_Response {
        global $wpdb;
        return new WP_REST_Response($wpdb->get_results("SELECT * FROM {$wpdb->prefix}tv_payment_methods ORDER BY display_order"), 200);
    }

    public static function create_method(WP_REST_Request $req) : WP_REST_Response {
        return self::handle_method_save($req);
    }

    public static function update_method(WP_REST_Request $req) : WP_REST_Response {
        return self::handle_method_save($req, (int)$req['id']);
    }

    private static function handle_method_save(WP_REST_Request $req, int $id = 0) {
        global $wpdb;
        
        $countries = $req->get_param('countries');
        if (is_array($countries)) $countries = implode(',', $countries);
        $currencies = $req->get_param('currencies');
        if (is_array($currencies)) $currencies = implode(',', $currencies);

        $data = [
            'name' => sanitize_text_field((string)$req['name']),
            'slug' => sanitize_title((string)$req['slug'] ?: $req['name']),
            'link' => esc_url_raw((string)$req['link']),
            'bank_name' => sanitize_text_field((string)$req['bank_name']),
            'account_name' => sanitize_text_field((string)$req['account_name']),
            'account_number' => sanitize_text_field((string)$req['account_number']),
            'instructions' => wp_kses_post((string)$req['instructions']),
            'countries' => sanitize_text_field($countries),
            'currencies' => sanitize_text_field($currencies),
            'flutterwave_enabled' => !empty($req['flutterwave_enabled']) ? 1 : 0,
            'flutterwave_secret_key' => sanitize_text_field((string)$req['flutterwave_secret_key']),
            'flutterwave_public_key' => sanitize_text_field((string)$req['flutterwave_public_key']),
            'status' => 'active',
            'open_behavior' => 'window',
            'display_order' => (int)$req['display_order']
        ];

        if ($id > 0) {
            $wpdb->update("{$wpdb->prefix}tv_payment_methods", $data, ['id' => $id]);
            self::log_event('Update Payment Method', "Updated ID: $id");
        } else {
            $wpdb->insert("{$wpdb->prefix}tv_payment_methods", $data);
            self::log_event('Create Payment Method', "Created: " . $data['name']);
        }
        return new WP_REST_Response(['ok' => true], 200);
    }

    public static function delete_method(WP_REST_Request $req) : WP_REST_Response {
        if (self::soft_delete_entity('payment_method', $GLOBALS['wpdb']->prefix.'tv_payment_methods', (int)$req['id'])) {
            self::log_event('Delete Method', "Soft-deleted method ID: {$req['id']}");
            return new WP_REST_Response(['ok' => true], 200);
        }
        return new WP_REST_Response(['error' => 'Delete failed'], 500);
    }

    // -----------------------
    // Sports
    // -----------------------

}
