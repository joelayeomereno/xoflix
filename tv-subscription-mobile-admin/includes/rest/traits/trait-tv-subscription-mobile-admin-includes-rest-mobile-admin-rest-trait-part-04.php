<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Trait: TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Part_04
 * Path: /tv-subscription-mobile-admin/includes/rest/traits/trait-tv-subscription-mobile-admin-includes-rest-mobile-admin-rest-trait-part-04.php
 */
trait TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Part_04 {


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

    public static function get_users(WP_REST_Request $req) : WP_REST_Response {
        $q = sanitize_text_field((string)$req['search']);
        $page = max(1, (int)$req->get_param('page'));
        $limit = 20;
        
        $query_args = [
            'search'  => !empty($q) ? "*$q*" : '',
            'number'  => $limit,
            'offset'  => ($page - 1) * $limit,
            'orderby' => 'registered',
            'order'   => 'DESC'
        ];
        
        $user_query = new WP_User_Query($query_args);
        $users = $user_query->get_results();
        $total = $user_query->get_total();

        $res = []; 
        foreach($users as $u) {
            $res[] = ['id'=>$u->ID, 'name'=>$u->display_name, 'email'=>$u->user_email, 'login'=>$u->user_login];
        }
        return new WP_REST_Response(['data' => $res, 'total' => (int)$total, 'pages' => ceil($total / $limit)], 200);
    }

    public static function get_user_details(WP_REST_Request $req) : WP_REST_Response {
        global $wpdb;
        $uid = (int)$req['id'];
        $user = get_userdata($uid);
        if(!$user) return new WP_REST_Response(['error'=>'Not found'], 404);

        $subs = $wpdb->get_results($wpdb->prepare("SELECT s.*, p.name as plan_name FROM {$wpdb->prefix}tv_subscriptions s LEFT JOIN {$wpdb->prefix}tv_plans p ON s.plan_id = p.id WHERE s.user_id = %d ORDER BY s.id DESC", $uid));

        // Impersonation Token
        $admin_id = get_current_user_id();
        $ts = time();
        $payload = $admin_id . '|' . $uid . '|' . $ts;
        $token = hash_hmac('sha256', $payload, wp_salt('auth'));
        $link = add_query_arg(['tv_sandbox'=>1, 'tv_user'=>$uid, 'tv_admin'=>$admin_id, 'tv_token'=>$token, 'tv_time'=>$ts], home_url('/dashboard'));

        return new WP_REST_Response([
            'profile' => ['id'=>$user->ID, 'user_login'=>$user->user_login, 'email'=>$user->user_email, 'display_name'=>$user->display_name, 'phone'=>get_user_meta($uid, 'phone', true)],
            'subscriptions' => $subs,
            'impersonate_url' => $link
        ], 200);
    }

    public static function update_user_profile(WP_REST_Request $req) : WP_REST_Response {
        $uid = (int)$req['id'];
        $args = ['ID'=>$uid, 'user_email'=>sanitize_email($req['email']), 'display_name'=>sanitize_text_field($req['display_name'])];
        if(!empty($req['password'])) $args['user_pass'] = $req['password'];
        
        $res = wp_update_user($args);
        if(is_wp_error($res)) return new WP_REST_Response(['error'=>$res->get_error_message()], 400);
        
        update_user_meta($uid, 'phone', sanitize_text_field($req['phone']));
        self::log_event('Admin Update User', "Updated user ID: $uid");
        return new WP_REST_Response(['msg'=>'Updated'], 200);
    }

    public static function create_user(WP_REST_Request $req) : WP_REST_Response {
        $email = sanitize_email((string)$req['email']);
        $login = sanitize_user((string)$req['login'] ?: $email, true);
        $pass = $req['password'] ?: wp_generate_password(12, true);
        
        $uid = wp_create_user($login, $pass, $email);
        if(is_wp_error($uid)) return new WP_REST_Response(['error'=>$uid->get_error_message()], 400);
        
        wp_update_user(['ID'=>$uid, 'display_name'=>sanitize_text_field($req['display_name'])]);
        update_user_meta($uid, 'phone', sanitize_text_field($req['phone']));
        
        self::log_event('Admin Create User', "Created user ID: $uid");
        return new WP_REST_Response(['msg'=>'Created', 'generated_password'=>$pass], 200);
    }
}