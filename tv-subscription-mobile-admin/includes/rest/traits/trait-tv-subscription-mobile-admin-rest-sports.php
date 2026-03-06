<?php
if (!defined('ABSPATH')) { exit; }

trait TV_Subscription_Mobile_Admin_Rest_Sports_Trait {

    public static function get_sports() : WP_REST_Response {
            global $wpdb;
            return new WP_REST_Response($wpdb->get_results("SELECT * FROM {$wpdb->prefix}tv_sports_events WHERE start_time > NOW() ORDER BY start_time ASC"), 200);
        }

    public static function create_event(WP_REST_Request $req) : WP_REST_Response {
            global $wpdb;
            $data = [
                'title' => sanitize_text_field((string)$req['title']),
                'league' => sanitize_text_field((string)$req['league']),
                'start_time' => sanitize_text_field($req['date'] . ' ' . $req['time']),
                'channel' => sanitize_text_field((string)$req['channel']),
                'sport_type' => sanitize_text_field((string)$req['sport_type']),
                'status' => 'scheduled'
            ];
            $wpdb->insert("{$wpdb->prefix}tv_sports_events", $data);
            self::log_event('Create Sports Event', "Created event: " . $data['title']);
            return new WP_REST_Response(['ok' => true], 200);
        }

    public static function delete_event(WP_REST_Request $req) : WP_REST_Response {
            if (self::soft_delete_entity('sport_event', $GLOBALS['wpdb']->prefix.'tv_sports_events', (int)$req['id'])) {
                self::log_event('Delete Event', "Soft-deleted event ID: {$req['id']}");
                return new WP_REST_Response(['ok' => true], 200);
            }
            return new WP_REST_Response(['error' => 'Delete failed'], 500);
        }

}
