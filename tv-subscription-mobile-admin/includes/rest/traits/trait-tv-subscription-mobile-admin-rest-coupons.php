<?php
if (!defined('ABSPATH')) { exit; }

trait TV_Subscription_Mobile_Admin_Rest_Coupons_Trait {

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

}
