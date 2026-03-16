<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Trait: TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Part_01
 * Path: /tv-subscription-mobile-admin/includes/rest/traits/trait-tv-subscription-mobile-admin-includes-rest-mobile-admin-rest-trait-part-01.php
 */
trait TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Part_01 {


    public static function register_routes() : void {
        $routes = [
            // Core
            'dashboard' => ['GET',  'get_dashboard_ultra', 'perm_manage_options'],
            'search'    => ['GET',  'global_search',      'perm_manage_options'],
            'finance'   => ['GET',  'get_finance',        'perm_finance'],

            // Subscriptions
            'subscriptions'           => ['GET',    'get_subscriptions',        'perm_manage_options'],
            'subscriptions/bulk'      => ['POST',   'handle_bulk_subscriptions','perm_manage_options'],
            'subscriptions/export'    => ['GET',    'get_subscriptions_export', 'perm_manage_options'],

            // Payments
            'payments'                => ['GET',    'get_payments',             'perm_manage_options'],
            'payments/(?P<id>\d+)/action' => ['POST','handle_payment_action',   'perm_manage_options'],
            'payments/bulk'           => ['POST',   'handle_bulk_payments',     'perm_manage_options'],

            // Users
            'users'                   => ['GET',    'get_users',                'perm_manage_options'],
            'users/(?P<id>\d+)'       => ['GET',    'get_user_details',         'perm_manage_options'],
            'users/(?P<id>\d+)/update' => ['POST',  'update_user_profile',      'perm_manage_options'],
            'users/(?P<id>\d+)/subscription' => ['POST', 'manage_user_subscription', 'perm_manage_options'],
            'users/create'            => ['POST',   'create_user',              'perm_manage_options'],
            'users/bulk'              => ['POST',   'handle_bulk_users',        'perm_manage_options'],

            // Plans
            'plans'                   => ['GET',    'get_plans',                'perm_manage_options'],
            'plans/new'               => ['POST',   'create_plan',              'perm_manage_options'],
            'plans/(?P<id>\d+)'       => ['POST',   'update_plan',              'perm_manage_options'],
            'plans/(?P<id>\d+)/delete'=> ['DELETE', 'delete_plan',              'perm_manage_options'],

            // Coupons
            'coupons'                 => ['GET',    'get_coupons',              'perm_manage_options'],
            'coupons/new'             => ['POST',   'create_coupon',            'perm_manage_options'],
            'coupons/(?P<id>\d+)'     => ['DELETE', 'delete_coupon',            'perm_manage_options'],

            // Sports
            'sports'                  => ['GET',    'get_sports',               'perm_manage_options'],
            'sports/new'              => ['POST',   'create_event',             'perm_manage_options'],
            'sports/(?P<id>\d+)'      => ['DELETE', 'delete_event',             'perm_manage_options'],

            // Methods
            'methods'                 => ['GET',    'get_methods',              'perm_manage_options'],
            'methods/new'             => ['POST',   'create_method',            'perm_manage_options'],
            'methods/(?P<id>\d+)'     => ['POST',   'update_method',            'perm_manage_options'],
            'methods/(?P<id>\d+)/delete' => ['DELETE','delete_method',          'perm_manage_options'],

            // Messages
            'messages'                => ['GET',    'get_messages',             'perm_manage_options'],
            'messages/new'            => ['POST',   'create_message',           'perm_manage_options'],
            'messages/broadcast'      => ['POST',   'send_broadcast',           'perm_manage_options'],
            'messages/(?P<id>\d+)'    => ['DELETE', 'delete_message',           'perm_manage_options'],

            // System
            'settings'                => ['GET',    'get_settings',             'perm_manage_options'],
            'settings/update'         => ['POST',   'update_settings',          'perm_manage_options'],
            'health'                  => ['GET',    'get_system_health',        'perm_manage_options'],
            'logs'                    => ['GET',    'get_logs',                 'perm_manage_options'],
        ];

        foreach ($routes as $route => $config) {
            register_rest_route(self::NS, '/' . $route, array(
                'methods'  => $config[0],
                'permission_callback' => array(__CLASS__, $config[2]),
                'callback' => array(__CLASS__, $config[1]),
            ));
        }
    }

    // -----------------------
    // Permissions & Helpers
    // -----------------------

    public static function perm_manage_options($req = null) : bool {
        if (!is_user_logged_in() || !current_user_can('manage_options')) return false;
        if ($req instanceof WP_REST_Request) {
            $method = strtoupper((string)$req->get_method());
            if (in_array($method, array('POST','PUT','PATCH','DELETE'), true)) {
                $nonce = (string)$req->get_header('X-WP-Nonce');
                if (empty($nonce) || !wp_verify_nonce($nonce, 'wp_rest')) return false;
            }
        }
        return true;
    }

    public static function perm_finance($req = null) : bool {
        if (!is_user_logged_in()) return false;
        $ok = current_user_can('manage_tv_finance') || current_user_can('manage_options');
        if (!$ok) return false;
        if ($req instanceof WP_REST_Request) {
            $method = strtoupper((string)$req->get_method());
            if (in_array($method, array('POST','PUT','PATCH','DELETE'), true)) {
                $nonce = (string)$req->get_header('X-WP-Nonce');
                if (empty($nonce) || !wp_verify_nonce($nonce, 'wp_rest')) return false;
            }
        }
        return true;
    }

    private static function log_event(string $action, string $details = '') : void {
        global $wpdb;
        $table_logs = $wpdb->prefix . 'tv_activity_logs';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_logs}'") !== $table_logs) return;

        $wpdb->insert($table_logs, array(
            'user_id' => (int)get_current_user_id(),
            'action' => $action,
            'details' => $details,
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '0.0.0.0',
            'date' => current_time('mysql'),
        ));
    }

    private static function soft_delete_entity(string $type, string $table, int $id, string $pk = 'id') : bool {
        global $wpdb;
        $table_recycle = $wpdb->prefix . 'tv_recycle_bin';
        
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE {$pk} = %d LIMIT 1", $id), ARRAY_A);
        if (!$row) return false;

        $inserted = $wpdb->insert($table_recycle, array(
            'entity_type'  => $type,
            'entity_table' => $table,
            'entity_pk'    => $pk,
            'entity_id'    => $id,
            'payload'      => wp_json_encode($row),
            'deleted_at'   => current_time('mysql'),
            'deleted_by'   => (int)get_current_user_id(),
            'expires_at'   => gmdate('Y-m-d H:i:s', time() + (7 * DAY_IN_SECONDS)),
            'status'       => 'deleted',
        ));

        if (!$inserted) return false;

        $deleted = $wpdb->delete($table, array($pk => $id));
        return (bool)$deleted;
    }

    private static function should_notify_from_request_default(WP_REST_Request $req, bool $default, string $key = 'notify_user') : bool {
        if (!$req->has_param($key)) return $default;
        $val = $req->get_param($key);
        if (is_bool($val)) return $val;
        $val = is_string($val) ? strtolower(trim($val)) : $val;
        return in_array($val, array(1, '1', 'on', 'yes', 'true'), true);
    }

    private static function currency_symbol(string $code) : string {
        $code = strtoupper(trim($code));
        if ($code === '') return '';
        if (class_exists('TV_Currency')) return TV_Currency::symbol($code);
        $fallback = ['USD'=>'$','EUR'=>'','GBP'=>'','NGN'=>'?','GHS'=>'?','KES'=>'KSh','ZAR'=>'R','INR'=>'?'];
        return isset($fallback[$code]) ? $fallback[$code] : ($code . ' ');
    }

    private static function normalize_proofs($proof_url) : array {
        if (empty($proof_url)) return [];
        $decoded = json_decode($proof_url, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) return $decoded;
        return array($proof_url);
    }

    // -----------------------
    // Core Endpoints
    // -----------------------

    public static function get_dashboard_ultra() : WP_REST_Response {
        $cache_key = 'tv_mobile_dash_stats';
        $cached = get_transient($cache_key);
        if ($cached !== false) return new WP_REST_Response($cached, 200);

        global $wpdb;
        $p = $wpdb->prefix;

        $rev = (float)$wpdb->get_var("SELECT SUM(amount) FROM {$p}tv_payments WHERE status IN ('completed','APPROVED')");
        $active = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$p}tv_subscriptions WHERE status = 'active'");
        $pending = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$p}tv_payments WHERE status IN ('pending','AWAITING_PROOF','IN_PROGRESS','PENDING_ADMIN_REVIEW')");

        $rev_rows = $wpdb->get_results("SELECT currency, SUM(amount) as total FROM {$p}tv_payments WHERE status IN ('completed','APPROVED') GROUP BY currency");
        $rev_value = '$' . number_format($rev, 0);
        if (!empty($rev_rows)) {
            $parts = [];
            foreach ($rev_rows as $r) {
                $cc = !empty($r->currency) ? strtoupper((string)$r->currency) : 'USD';
                $parts[] = self::currency_symbol($cc) . number_format((float)$r->total, 0);
            }
            $rev_value = implode("\n", $parts);
        }

        $data = [
            'stats' => ['revenue' => ['value' => $rev_value, 'trend' => 100], 'active_subs' => $active, 'pending_tasks' => $pending, 'users' => (int)count_users()['total_users']],
            'recent_activity' => $wpdb->get_results("SELECT * FROM {$p}tv_activity_logs ORDER BY date DESC LIMIT 10"),
            'csv_url' => admin_url('admin-post.php?action=tv_finance_export_csv'),
        ];

        set_transient($cache_key, $data, 60);
        return new WP_REST_Response($data, 200);
    }

    public static function global_search(WP_REST_Request $req) : WP_REST_Response {
        global $wpdb;
        $q = sanitize_text_field($req['q']);
        if (strlen($q) < 2) return new WP_REST_Response([], 200);

        $res = [];
        $users = $wpdb->get_results($wpdb->prepare("SELECT ID, user_login, user_email FROM {$wpdb->users} WHERE user_login LIKE %s OR user_email LIKE %s LIMIT 5", "%$q%", "%$q%"));
        foreach ($users as $u) $res[] = ['type' => 'user', 'id' => (int)$u->ID, 'title' => $u->user_login, 'subtitle' => $u->user_email];

        $pays = $wpdb->get_results($wpdb->prepare("SELECT id, amount, status FROM {$wpdb->prefix}tv_payments WHERE id LIKE %s LIMIT 5", "%$q%"));
        foreach ($pays as $p) $res[] = ['type' => 'payment', 'id' => (int)$p->id, 'title' => "Invoice #{$p->id}", 'subtitle' => "\${$p->amount} - {$p->status}"];

        return new WP_REST_Response($res, 200);
    }
}
