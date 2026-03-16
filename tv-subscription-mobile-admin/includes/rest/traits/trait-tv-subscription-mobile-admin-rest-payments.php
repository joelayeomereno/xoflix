<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Trait: TV_Subscription_Mobile_Admin_Rest_Payments_Trait
 * Path: tv-subscription-mobile-admin/includes/rest/traits/trait-tv-subscription-mobile-admin-rest-payments.php
 */
trait TV_Subscription_Mobile_Admin_Rest_Payments_Trait {

    public static function get_payments( WP_REST_Request $req ) : WP_REST_Response {
        global $wpdb;
        $status = $req->get_param( 'status' );
        $where  = 'WHERE 1=1';
        $args   = [];

        if ( $status === 'pending' ) {
            $where .= " AND p.status IN ('pending', 'AWAITING_PROOF', 'IN_PROGRESS', 'PENDING_ADMIN_REVIEW')";
        } elseif ( $status === 'completed' ) {
            $where .= " AND p.status IN ('completed', 'APPROVED')";
        }

        $limit = 20;
        $page = max(1, (int)$req->get_param('page'));
        $offset = ($page - 1) * $limit;

        $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}tv_payments p LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID {$where}";
        $total = !empty($args) ? $wpdb->get_var($wpdb->prepare($count_sql, $args)) : $wpdb->get_var($count_sql);

        $sql = "SELECT p.*, u.user_login, u.user_email, u.display_name,
                       s.connections, s.start_date AS sub_start, s.end_date AS sub_end,
                       s.credential_user, s.credential_pass, s.credential_url, s.credential_m3u,
                       pl.name AS plan_name, pl.price AS base_plan_price
                FROM {$wpdb->prefix}tv_payments p
                LEFT JOIN {$wpdb->users} u                    ON p.user_id        = u.ID
                LEFT JOIN {$wpdb->prefix}tv_subscriptions s   ON p.subscription_id = s.id
                LEFT JOIN {$wpdb->prefix}tv_plans pl          ON s.plan_id         = pl.id
                {$where}
                ORDER BY p.date DESC
                LIMIT %d OFFSET %d";

        $payments = $wpdb->get_results( $wpdb->prepare($sql, array_merge($args, [$limit, $offset])) );
        if ( ! is_array( $payments ) ) $payments = [];

        foreach ( $payments as $k => $v ) {
            $payments[$k]->proofs = self::normalize_proofs( $v->proof_url ?? '' );

            $payments[$k]->time_ago = isset( $v->date ) && $v->date
                ? human_time_diff( strtotime( $v->date ), current_time( 'timestamp' ) ) . ' ago'
                : '';

            $cc = ! empty( $v->currency ) ? strtoupper( (string) $v->currency ) : 'USD';
            $payments[$k]->currency_symbol = self::currency_symbol( $cc );
            $payments[$k]->amount_display  = self::currency_symbol( $cc ) . number_format( (float) $v->amount, 0 );

            $is_renewal = false;
            if ( ! empty( $v->sub_start ) && ! empty( $v->date ) ) {
                $sub_start_ts = strtotime( $v->sub_start );
                $pay_date_ts  = strtotime( $v->date );
                if ( $sub_start_ts > 0 && ( $pay_date_ts - $sub_start_ts ) > 86400 ) $is_renewal = true;
            }
            $payments[$k]->is_renewal = $is_renewal;
            $payments[$k]->user_phone = (string) get_user_meta( (int) $v->user_id, 'phone', true );

            $days = ( isset( $v->sub_start ) && isset( $v->sub_end ) )
                ? ( strtotime( $v->sub_end ) - strtotime( $v->sub_start ) ) / 86400
                : 30;
            $payments[$k]->months_derived = max( 1, round( $days / 30 ) );

            $base_usd = floatval( $v->base_plan_price ) * $payments[$k]->months_derived;
            $payments[$k]->base_total_usd = $base_usd;

            $locked_usd = isset( $v->amount_usd ) && floatval( $v->amount_usd ) > 0
                ? floatval( $v->amount_usd )
                : floatval( $v->amount );
            $payments[$k]->locked_usd = $locked_usd;
        }

        return new WP_REST_Response( [
            'data' => $payments,
            'total' => (int)$total,
            'pages' => ceil($total / $limit)
        ], 200 );
    }

    public static function handle_payment_action( WP_REST_Request $req ) : WP_REST_Response {
        $pid    = (int) $req['id'];
        $action = $req->get_param( 'action' );
        $notify = self::should_notify_from_request_default( $req, true );

        if ( ! class_exists( 'TV_Domain_Payments_Service' ) ) {
            return new WP_REST_Response( [ 'error' => 'Service missing' ], 500 );
        }
        $svc = new TV_Domain_Payments_Service();

        if ( $action === 'approve' ) {
            $svc->approve_payment( $pid, [], $notify );
            return new WP_REST_Response( [ 'msg' => 'Approved' ], 200 );
        }
        if ( $action === 'reject' ) {
            $reason = sanitize_text_field( (string) $req->get_param( 'reason_key' ) );
            $svc->reject_payment( $pid, $notify, $reason );
            return new WP_REST_Response( [ 'msg' => 'Rejected' ], 200 );
        }
        if ( $action === 'fulfill' ) {
            $svc->fulfill_payment( $pid, $req->get_param( 'creds' ) ?: [], $notify );
            return new WP_REST_Response( [ 'msg' => 'Fulfilled' ], 200 );
        }
        return new WP_REST_Response( [ 'error' => 'Invalid action' ], 400 );
    }

    public static function handle_bulk_payments( WP_REST_Request $req ) : WP_REST_Response {
        $ids    = array_map( 'intval', (array) $req->get_param( 'ids' ) );
        $action = $req->get_param( 'action' );

        if ( empty( $ids ) ) return new WP_REST_Response( [ 'error' => 'No items' ], 400 );
        if ( ! class_exists( 'TV_Domain_Payments_Service' ) ) {
            return new WP_REST_Response( [ 'error' => 'Service missing' ], 500 );
        }

        $svc    = new TV_Domain_Payments_Service();
        $notify = self::should_notify_from_request_default( $req, $action === 'approve' );
        $count  = 0;

        if ( $action === 'approve' ) {
            $count = $svc->approve_payments_bulk( $ids, $notify );
        } elseif ( $action === 'reject' ) {
            foreach ( $ids as $pid ) {
                if ( $svc->reject_payment( (int) $pid, $notify )['ok'] ) $count++;
            }
        }

        return new WP_REST_Response( [ 'msg' => "{$count} processed" ], 200 );
    }
}