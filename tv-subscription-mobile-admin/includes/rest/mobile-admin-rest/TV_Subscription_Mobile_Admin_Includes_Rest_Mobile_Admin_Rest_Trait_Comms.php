<?php
if (!defined('ABSPATH')) { exit; }

trait TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Comms {
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

    // -----------------------
    // Messages
    // -----------------------

    public static function get_messages() : WP_REST_Response {
        global $wpdb;
        return new WP_REST_Response($wpdb->get_results("SELECT * FROM {$wpdb->prefix}tv_announcements ORDER BY id DESC"), 200);
    }

    public static function create_message(WP_REST_Request $req) : WP_REST_Response {
        global $wpdb;
        $data = [
            'title' => sanitize_text_field((string)$req['title']),
            'message' => wp_kses_post((string)$req['message']),
            'button_text' => sanitize_text_field((string)$req['button_text']),
            'button_action' => sanitize_text_field((string)$req['button_action']),
            'color_scheme' => sanitize_text_field((string)$req['color_scheme']),
            'status' => 'active'
        ];
        $wpdb->insert("{$wpdb->prefix}tv_announcements", $data);
        self::log_event('Create Announcement', "Created: " . $data['title']);
        return new WP_REST_Response(['ok' => true], 200);
    }

    public static function delete_message(WP_REST_Request $req) : WP_REST_Response {
        if (self::soft_delete_entity('hero_slide', $GLOBALS['wpdb']->prefix.'tv_announcements', (int)$req['id'])) {
            self::log_event('Delete Announcement', "Soft-deleted ID: {$req['id']}");
            return new WP_REST_Response(['ok' => true], 200);
        }
        return new WP_REST_Response(['error' => 'Delete failed'], 500);
    }

    public static function send_broadcast(WP_REST_Request $req) : WP_REST_Response {
        global $wpdb;
        $subject = sanitize_text_field((string)$req['subject']);
        $body = wp_kses_post((string)$req['body']);

        $users = $wpdb->get_results("SELECT DISTINCT u.user_email FROM {$wpdb->prefix}tv_subscriptions s JOIN {$wpdb->users} u ON s.user_id = u.ID WHERE s.status='active'");
        $count = 0;
        foreach ($users as $user) {
            wp_mail($user->user_email, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
            $count++;
        }
        self::log_event('Broadcast Message', "Sent to {$count} users.");
        return new WP_REST_Response(['msg' => "Sent to $count users"], 200);
    }

    // -----------------------
    // Users
    // -----------------------

}
