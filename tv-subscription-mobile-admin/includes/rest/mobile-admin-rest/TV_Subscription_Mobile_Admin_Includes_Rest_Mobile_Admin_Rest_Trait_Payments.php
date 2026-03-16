<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Trait: TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Payments
 * File: tv-subscription-mobile-admin/includes/rest/mobile-admin-rest/
 *       TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Payments.php
 *
 * FIXES & UPGRADES:
 * -----------------------------------------------------------------------
 * [BUG FIX] SQL JOIN order was broken. `tv_plans pl ON s.plan_id` referenced
 *           the `s` alias before it was defined. Fixed: tv_subscriptions joins
 *           first, then tv_plans.
 *
 * [BUG FIX] Amount display hardcoded '$' for all currencies. Now uses
 *           currency_symbol() with the stored `currency` field.
 *
 * [BUG FIX] currency_symbol() fallback now uses Unicode literals, not HTML
 *           entities. JSON.parse() never decodes HTML entities — they appeared
 *           as raw "&#8358;" text on screen.
 *
 * [NEW]     `search` param — filter by user_login, user_email, or payment ID.
 * [NEW]     `date_from` / `date_to` (YYYY-MM-DD) for date-range filtering.
 * [NEW]     Full financial fields returned: currency, amount_usd, gross_usd,
 *           gross_local, discount_usd, discount_local, amount_ngn, fx_rate,
 *           transaction_id, method, coupon_code, months, connections, date, sub_id.
 * [NEW]     `amount_display` pre-formatted field for the PAY page cards.
 * [FIX]     `handle_payment_action` — added `fulfill` as an explicit action
 *           (separate from `approve`) so IPTV credential delivery routes through
 *           fulfill_payment() correctly.
 * -----------------------------------------------------------------------
 */
trait TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Payments {

    /**
     * GET /payments
     *
     * Params:
     *   status    string  all | pending | completed
     *   search    string  user_login / user_email / payment ID
     *   date_from string  YYYY-MM-DD
     *   date_to   string  YYYY-MM-DD
     */
    public static function get_payments( WP_REST_Request $req ) : WP_REST_Response {
        global $wpdb;

        $status    = sanitize_text_field( (string) $req->get_param( 'status' ) );
        $search    = sanitize_text_field( (string) $req->get_param( 'search' ) );
        $date_from = sanitize_text_field( (string) $req->get_param( 'date_from' ) );
        $date_to   = sanitize_text_field( (string) $req->get_param( 'date_to' ) );

        $where = 'WHERE 1=1';
        $args  = [];

        // --- Status filter ---
        if ( $status === 'pending' ) {
            $where .= " AND p.status IN ('pending','AWAITING_PROOF','IN_PROGRESS','PENDING_ADMIN_REVIEW')";
        } elseif ( $status === 'completed' ) {
            $where .= " AND p.status IN ('completed','APPROVED')";
        }

        // --- Search filter ---
        if ( ! empty( $search ) ) {
            if ( is_numeric( $search ) ) {
                $where .= ' AND (u.user_login LIKE %s OR u.user_email LIKE %s OR p.id = %d)';
                $args[] = '%' . $wpdb->esc_like( $search ) . '%';
                $args[] = '%' . $wpdb->esc_like( $search ) . '%';
                $args[] = (int) $search;
            } else {
                $where .= ' AND (u.user_login LIKE %s OR u.user_email LIKE %s)';
                $args[] = '%' . $wpdb->esc_like( $search ) . '%';
                $args[] = '%' . $wpdb->esc_like( $search ) . '%';
            }
        }

        // --- Date range filter ---
        if ( ! empty( $date_from ) && strtotime( $date_from ) ) {
            $where .= ' AND DATE(p.date) >= %s';
            $args[] = $date_from;
        }
        if ( ! empty( $date_to ) && strtotime( $date_to ) ) {
            $where .= ' AND DATE(p.date) <= %s';
            $args[] = $date_to;
        }

        $p_tbl  = $wpdb->prefix . 'tv_payments';
        $s_tbl  = $wpdb->prefix . 'tv_subscriptions';
        $pl_tbl = $wpdb->prefix . 'tv_plans';
        $u_tbl  = $wpdb->users;

        // Full select — all financial fields for PAY page parity with desktop admin
        $select = "
            p.id,
            p.user_id,
            p.subscription_id       AS sub_id,
            p.amount,
            p.currency,
            p.amount_usd,
            p.gross_usd,
            p.gross_local,
            p.discount_usd,
            p.discount_local,
            p.amount_ngn,
            p.fx_rate,
            p.method,
            p.transaction_id,
            p.coupon_code,
            p.months,
            p.connections,
            p.status,
            p.proof_url,
            p.date,
            p.attempted_at,
            u.user_login,
            u.user_email,
            pl.name AS plan_name
        ";

        // [BUG FIX] tv_subscriptions (s) must be joined BEFORE tv_plans (pl)
        $sql = "SELECT {$select}
                FROM {$p_tbl} p
                LEFT JOIN {$u_tbl} u   ON p.user_id = u.ID
                LEFT JOIN {$s_tbl} s   ON p.subscription_id = s.id
                LEFT JOIN {$pl_tbl} pl ON s.plan_id = pl.id
                {$where}
                ORDER BY p.date DESC
                LIMIT 100";

        $payments = ! empty( $args )
            ? $wpdb->get_results( $wpdb->prepare( $sql, $args ) )
            : $wpdb->get_results( $sql );

        if ( ! is_array( $payments ) ) {
            $payments = [];
        }

        foreach ( $payments as $k => $v ) {
            $payments[$k]->proofs = self::normalize_proofs( $v->proof_url ?? '' );

            $payments[$k]->time_ago = ( isset( $v->date ) && $v->date )
                ? human_time_diff( strtotime( $v->date ), current_time( 'timestamp' ) ) . ' ago'
                : '';

            // [BUG FIX] was always '$'. currency_symbol() now returns plain Unicode.
            $cc = ! empty( $v->currency ) ? strtoupper( (string) $v->currency ) : 'USD';
            $payments[$k]->currency_symbol = self::currency_symbol( $cc );
            $payments[$k]->amount_display  = self::currency_symbol( $cc ) . number_format( (float) $v->amount, 0 );

            // Null-safe numeric casts
            $payments[$k]->amount_usd     = isset( $v->amount_usd )     ? (float) $v->amount_usd     : null;
            $payments[$k]->gross_usd      = isset( $v->gross_usd )      ? (float) $v->gross_usd      : null;
            $payments[$k]->gross_local    = isset( $v->gross_local )    ? (float) $v->gross_local    : null;
            $payments[$k]->discount_usd   = isset( $v->discount_usd )   ? (float) $v->discount_usd   : null;
            $payments[$k]->discount_local = isset( $v->discount_local ) ? (float) $v->discount_local : null;
            $payments[$k]->amount_ngn     = isset( $v->amount_ngn )     ? (float) $v->amount_ngn     : null;
            $payments[$k]->fx_rate        = isset( $v->fx_rate )        ? (float) $v->fx_rate        : null;
            $payments[$k]->months         = isset( $v->months )         ? (int)   $v->months         : null;
            $payments[$k]->connections    = isset( $v->connections )    ? (int)   $v->connections    : null;
        }

        return new WP_REST_Response( $payments, 200 );
    }

    /**
     * POST /payments/{id}/action
     *
     * Params:
     *   action     string  approve | reject | fulfill
     *   creds      array   { user, pass, url } — for fulfill action
     *   reason_key string  — for reject action
     *   notify_user bool
     */
    public static function handle_payment_action( WP_REST_Request $req ) : WP_REST_Response {
        $pid    = (int) $req['id'];
        $action = sanitize_text_field( (string) $req->get_param( 'action' ) );
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
            $raw_creds = $req->get_param( 'creds' );
            $creds     = is_array( $raw_creds )
                ? array_map( 'sanitize_text_field', $raw_creds )
                : [];
            $svc->fulfill_payment( $pid, $creds, $notify );
            return new WP_REST_Response( [ 'msg' => 'Fulfilled' ], 200 );
        }

        return new WP_REST_Response( [ 'error' => 'Invalid action' ], 400 );
    }

    /**
     * POST /payments/bulk
     *
     * Params:
     *   ids         int[]   payment IDs
     *   action      string  approve | reject
     *   notify_user bool
     */
    public static function handle_bulk_payments( WP_REST_Request $req ) : WP_REST_Response {
        $ids    = array_map( 'intval', (array) $req->get_param( 'ids' ) );
        $action = sanitize_text_field( (string) $req->get_param( 'action' ) );

        if ( empty( $ids ) ) {
            return new WP_REST_Response( [ 'error' => 'No items selected' ], 400 );
        }
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
                $result = $svc->reject_payment( (int) $pid, $notify );
                if ( is_array( $result ) && ! empty( $result['ok'] ) ) {
                    $count++;
                }
            }
        }

        self::log_event( 'Bulk Payment Action', "Action: {$action} on {$count} payments." );
        return new WP_REST_Response( [ 'msg' => "{$count} processed" ], 200 );
    }

    // -----------------------
    // Plans (next section marker)
    // -----------------------

}