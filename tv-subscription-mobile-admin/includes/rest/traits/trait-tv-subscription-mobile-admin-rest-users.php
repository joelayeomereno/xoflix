<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Trait: TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Users
 * Path: tv-subscription-mobile-admin/includes/rest/traits/trait-tv-subscription-mobile-admin-rest-users.php
 */
trait TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Users {

    /**
     * GET /users
     */
    public static function get_users( WP_REST_Request $req ) : WP_REST_Response {
        $q     = sanitize_text_field( (string) $req['search'] );
        $page  = max(1, (int)$req->get_param('page'));
        $limit = 20;
        
        $query_args = [
            'search'  => ! empty( $q ) ? "*{$q}*" : '',
            'number'  => $limit,
            'offset'  => ($page - 1) * $limit,
            'orderby' => 'registered',
            'order'   => 'DESC',
        ];
        
        $user_query = new WP_User_Query( $query_args );
        $users = $user_query->get_results();
        $total = $user_query->get_total();

        $res = [];
        foreach ( $users as $u ) {
            $res[] = [
                'id'    => $u->ID,
                'name'  => $u->display_name ?: $u->user_login,
                'email' => $u->user_email,
                'login' => $u->user_login,
            ];
        }
        
        return new WP_REST_Response( [
            'data'  => $res,
            'total' => (int)$total,
            'pages' => ceil($total / $limit)
        ], 200 );
    }

    /**
     * GET /users/{id}
     */
    public static function get_user_details( WP_REST_Request $req ) : WP_REST_Response {
        global $wpdb;
        $uid  = (int) $req['id'];
        $user = get_userdata( $uid );
        if ( ! $user ) {
            return new WP_REST_Response( [ 'error' => 'Not found' ], 404 );
        }

        $profile = [
            'id'               => $uid,
            'user_login'       => $user->user_login,
            'email'            => $user->user_email,
            'display_name'     => $user->display_name,
            'first_name'       => get_user_meta( $uid, 'first_name',       true ),
            'last_name'        => get_user_meta( $uid, 'last_name',        true ),
            'phone'            => get_user_meta( $uid, 'phone',            true ),
            'billing_country'  => get_user_meta( $uid, 'billing_country',  true ),
            'admin_notes'      => get_user_meta( $uid, 'tv_admin_notes',   true ),
            'user_registered'  => $user->user_registered,
        ];

        $subs = $wpdb->get_results( $wpdb->prepare(
            "SELECT s.*, pl.name AS plan_name
             FROM {$wpdb->prefix}tv_subscriptions s
             LEFT JOIN {$wpdb->prefix}tv_plans pl ON s.plan_id = pl.id
             WHERE s.user_id = %d
             ORDER BY s.id DESC",
            $uid
        ) );

        $raw_pays = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.id, p.date, p.amount, p.currency, p.status, p.method,
                    p.transaction_id, p.coupon_code, p.months, p.connections,
                    p.amount_usd, p.gross_local, p.discount_local, p.fx_rate,
                    p.proof_url, p.subscription_id,
                    pl.name AS plan_name
             FROM {$wpdb->prefix}tv_payments p
             LEFT JOIN {$wpdb->prefix}tv_subscriptions s ON p.subscription_id = s.id
             LEFT JOIN {$wpdb->prefix}tv_plans pl ON s.plan_id = pl.id
             WHERE p.user_id = %d
             ORDER BY p.date DESC
             LIMIT 100",
            $uid
        ) );

        $payments = [];
        foreach ( $raw_pays as $p ) {
            $cc             = ! empty( $p->currency ) ? strtoupper( (string) $p->currency ) : 'USD';
            $sym            = self::currency_symbol( $cc );
            $p->currency_symbol  = $sym;
            $p->amount_display   = $sym . number_format( (float) $p->amount, 0 );
            $p->proofs           = self::normalize_proofs( $p->proof_url ?? '' );
            $p->amount_usd       = isset( $p->amount_usd )    ? (float) $p->amount_usd    : null;
            $p->gross_local      = isset( $p->gross_local )   ? (float) $p->gross_local   : null;
            $p->discount_local   = isset( $p->discount_local )? (float) $p->discount_local: null;
            $p->fx_rate          = isset( $p->fx_rate )       ? (float) $p->fx_rate       : null;
            $payments[] = $p;
        }

        $ltv = (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(amount_usd), SUM(amount), 0)
             FROM {$wpdb->prefix}tv_payments
             WHERE user_id = %d
               AND status IN ('completed','APPROVED')",
            $uid
        ) );

        $logs = [];
        $log_table = $wpdb->prefix . 'tv_activity_logs';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$log_table}'" ) === $log_table ) {
            $logs = $wpdb->get_results( $wpdb->prepare(
                "SELECT date, action, details, ip_address
                 FROM {$log_table}
                 WHERE user_id = %d
                 ORDER BY date DESC
                 LIMIT 50",
                $uid
            ) );
        }

        $admin_id = get_current_user_id();
        $ts       = time();
        $payload  = $admin_id . '|' . $uid . '|' . $ts;
        $token    = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
        $imp_url  = add_query_arg(
            [ 'tv_sandbox' => 1, 'tv_user' => $uid, 'tv_admin' => $admin_id, 'tv_token' => $token, 'tv_time' => $ts ],
            home_url( '/dashboard' )
        );

        return new WP_REST_Response( [
            'profile'         => $profile,
            'subscriptions'   => $subs,
            'payments'        => $payments,
            'ltv'             => round( $ltv, 2 ),
            'logs'            => $logs,
            'impersonate_url' => $imp_url,
        ], 200 );
    }

    /**
     * POST /users/{id}/update
     */
    public static function update_user_profile( WP_REST_Request $req ) : WP_REST_Response {
        $uid  = (int) $req['id'];
        $args = [
            'ID'           => $uid,
            'user_email'   => sanitize_email( (string) $req['email'] ),
            'display_name' => sanitize_text_field( (string) $req['display_name'] ),
        ];
        if ( ! empty( $req['password'] ) ) {
            $args['user_pass'] = $req['password'];
        }

        $res = wp_update_user( $args );
        if ( is_wp_error( $res ) ) {
            return new WP_REST_Response( [ 'error' => $res->get_error_message() ], 400 );
        }

        update_user_meta( $uid, 'phone',           sanitize_text_field( (string) $req['phone'] ) );
        update_user_meta( $uid, 'first_name',      sanitize_text_field( (string) $req['first_name'] ) );
        update_user_meta( $uid, 'last_name',       sanitize_text_field( (string) $req['last_name'] ) );
        update_user_meta( $uid, 'billing_country', sanitize_text_field( (string) $req['billing_country'] ) );
        update_user_meta( $uid, 'tv_admin_notes',  sanitize_textarea_field( (string) $req['admin_notes'] ) );

        self::log_event( 'Admin Update User', "Updated user ID: {$uid}" );
        return new WP_REST_Response( [ 'msg' => 'Updated' ], 200 );
    }

    /**
     * POST /users/create
     */
    public static function create_user( WP_REST_Request $req ) : WP_REST_Response {
        $email = sanitize_email( (string) $req['email'] );
        $login = sanitize_user( (string) ( $req['login'] ?: $email ), true );
        $pass  = $req['password'] ?: wp_generate_password( 12, true );

        $uid = wp_create_user( $login, $pass, $email );
        if ( is_wp_error( $uid ) ) {
            return new WP_REST_Response( [ 'error' => $uid->get_error_message() ], 400 );
        }

        wp_update_user( [ 'ID' => $uid, 'display_name' => sanitize_text_field( (string) $req['display_name'] ) ] );
        update_user_meta( $uid, 'phone', sanitize_text_field( (string) $req['phone'] ) );

        self::log_event( 'Admin Create User', "Created user ID: {$uid}" );
        return new WP_REST_Response( [ 'msg' => 'Created', 'generated_password' => $pass ], 200 );
    }

    /**
     * POST /users/{id}/subscription
     */
    public static function manage_user_subscription( WP_REST_Request $req ) : WP_REST_Response {
        global $wpdb;
        $data = [
            'plan_id'         => (int)   $req['plan_id'],
            'status'          => sanitize_text_field( (string) $req['status'] ),
            'start_date'      => sanitize_text_field( (string) $req['start_date'] ),
            'end_date'        => sanitize_text_field( (string) $req['end_date'] ),
            'connections'     => (int)   $req['connections'] ?: 1,
            'credential_user' => sanitize_text_field( (string) ( $req['credential_user'] ?? '' ) ),
            'credential_pass' => sanitize_text_field( (string) ( $req['credential_pass'] ?? '' ) ),
            'credential_url'  => esc_url_raw( (string) ( $req['credential_url'] ?? '' ) ),
            'credential_m3u'  => sanitize_textarea_field( (string) ( $req['credential_m3u'] ?? '' ) ),
        ];

        if ( $req['sub_id'] ) {
            $wpdb->update( "{$wpdb->prefix}tv_subscriptions", $data, [ 'id' => (int) $req['sub_id'] ] );
        } else {
            $data['user_id'] = (int) $req['id'];
            $wpdb->insert( "{$wpdb->prefix}tv_subscriptions", $data );
        }

        self::log_event( 'Admin Manage Subscription', "Updated subscription for user: " . $req['id'] );
        return new WP_REST_Response( [ 'msg' => 'Saved' ], 200 );
    }

    /**
     * POST /users/bulk
     */
    public static function handle_bulk_users( WP_REST_Request $req ) : WP_REST_Response {
        global $wpdb;
        $ids    = array_map( 'intval', (array) $req->get_param( 'ids' ) );
        $action = sanitize_text_field( (string) $req->get_param( 'action' ) );
        $count  = 0;

        foreach ( $ids as $uid ) {
            if ( $uid <= 0 ) continue;
            if ( $action === 'delete_user' ) {
                $sub_ids = $wpdb->get_col( $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}tv_subscriptions WHERE user_id = %d", $uid
                ) );
                foreach ( $sub_ids as $sid ) {
                    self::soft_delete_entity( 'subscription', "{$wpdb->prefix}tv_subscriptions", (int) $sid );
                }
                wp_delete_user( $uid );
                $count++;
            } elseif ( $action === 'activate_sub' ) {
                $sub_id = $wpdb->get_var( $wpdb->prepare(
                    "SELECT MAX(id) FROM {$wpdb->prefix}tv_subscriptions WHERE user_id = %d", $uid
                ) );
                if ( $sub_id ) {
                    $wpdb->update( "{$wpdb->prefix}tv_subscriptions", [ 'status' => 'active' ], [ 'id' => $sub_id ] );
                }
                $count++;
            }
        }

        self::log_event( 'Bulk User Action', "{$action} on {$count} users" );
        return new WP_REST_Response( [ 'msg' => "{$count} processed" ], 200 );
    }
}