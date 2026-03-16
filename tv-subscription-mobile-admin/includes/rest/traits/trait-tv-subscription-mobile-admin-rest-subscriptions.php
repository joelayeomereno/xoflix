<?php
if (!defined('ABSPATH')) { exit; }

trait TV_Subscription_Mobile_Admin_Rest_Subscriptions_Trait {

    public static function get_subscriptions(WP_REST_Request $req) : WP_REST_Response {
            global $wpdb;
            $status = sanitize_text_field((string)$req->get_param('status'));
            $search = sanitize_text_field((string)$req->get_param('search'));
            
            $where = ["1=1"];
            $args = [];
    
            if (!empty($status) && $status !== 'all') {
                $where[] = "s.status = %s";
                $args[] = $status;
            }
            if (!empty($search)) {
                $where[] = "(u.user_login LIKE %s OR u.user_email LIKE %s OR s.id = %d)";
                $args[] = '%' . $search . '%';
                $args[] = '%' . $search . '%';
                $args[] = (int)$search;
            }
    
            $sql = "SELECT s.*, u.user_login, u.user_email, pl.name as plan_name FROM {$wpdb->prefix}tv_subscriptions s LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID LEFT JOIN {$wpdb->prefix}tv_plans pl ON s.plan_id = pl.id WHERE " . implode(' AND ', $where) . " ORDER BY s.id DESC LIMIT 50";
            return new WP_REST_Response(!empty($args) ? $wpdb->get_results($wpdb->prepare($sql, $args)) : $wpdb->get_results($sql), 200);
        }

    public static function handle_bulk_subscriptions(WP_REST_Request $req) : WP_REST_Response {
            $ids = array_map('intval', (array)$req->get_param('ids'));
            $action = sanitize_text_field((string)$req->get_param('action'));
            $count = 0;
    
            global $wpdb;
            $table = $wpdb->prefix . 'tv_subscriptions';
    
            foreach ($ids as $id) {
                if ($id <= 0) continue;
                if ($action === 'delete') {
                    if (self::soft_delete_entity('subscription', $table, $id)) $count++;
                } elseif ($action === 'activate') {
                    if ($wpdb->update($table, ['status'=>'active'], ['id'=>$id])) $count++;
                } elseif ($action === 'pending') {
                    if ($wpdb->update($table, ['status'=>'pending'], ['id'=>$id])) $count++;
                }
            }
            self::log_event('Bulk Action', "Action: {$action} on {$count} subscriptions.");
            return new WP_REST_Response(['msg' => "{$count} processed"], 200);
        }

    public static function get_subscriptions_export() : WP_REST_Response {
            return new WP_REST_Response(['url' => admin_url('admin-post.php?action=tv_sub_export')], 200);
        }

}
