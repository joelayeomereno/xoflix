<?php
if (!defined('ABSPATH')) { exit; }

trait TV_Subscription_Mobile_Admin_Rest_Plans_Trait {

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

}
