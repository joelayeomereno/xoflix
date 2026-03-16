<?php
if (!defined('ABSPATH')) { exit; }

trait TV_Subscription_Mobile_Admin_Rest_Dashboard_Search_Finance_Trait {

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

    public static function get_finance() : WP_REST_Response { return self::get_dashboard_ultra(); }

}
