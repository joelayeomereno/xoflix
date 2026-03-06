<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Trait: TV_Subscription_Mobile_Admin_Rest_Utils
 * Shared utilities for logging, permissions, soft-deletes, and currency.
 *
 * FIX: currency_symbol() fallback map now uses PHP Unicode escape sequences
 *      instead of HTML entities. HTML entities like &#8358; are only decoded
 *      by an HTML parser. When values are serialised to JSON via the REST API,
 *      JSON.parse() receives them as literal strings (e.g. "&#8358;8084.00")
 *      and the browser renders the raw entity text instead of the glyph.
 *      PHP Unicode escapes (\u{20A6}) produce the actual UTF-8 byte sequence
 *      which JSON encodes cleanly and the browser renders correctly.
 */
trait TV_Subscription_Mobile_Admin_Rest_Utils {

    public static function perm_manage_options( $req = null ) : bool {
        if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) return false;
        if ( $req instanceof WP_REST_Request ) {
            $method = strtoupper( (string) $req->get_method() );
            if ( in_array( $method, array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) ) {
                $nonce = (string) $req->get_header( 'X-WP-Nonce' );
                if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) return false;
            }
        }
        return true;
    }

    public static function perm_finance( $req = null ) : bool {
        if ( ! is_user_logged_in() ) return false;
        $ok = current_user_can( 'manage_tv_finance' ) || current_user_can( 'manage_options' );
        if ( ! $ok ) return false;
        if ( $req instanceof WP_REST_Request ) {
            $method = strtoupper( (string) $req->get_method() );
            if ( in_array( $method, array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) ) {
                $nonce = (string) $req->get_header( 'X-WP-Nonce' );
                if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) return false;
            }
        }
        return true;
    }

    protected static function log_event( string $action, string $details = '' ) : void {
        global $wpdb;
        $table_logs = $wpdb->prefix . 'tv_activity_logs';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_logs}'" ) !== $table_logs ) return;

        $wpdb->insert( $table_logs, array(
            'user_id'    => (int) get_current_user_id(),
            'action'     => $action,
            'details'    => $details,
            'ip_address' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '0.0.0.0',
            'date'       => current_time( 'mysql' ),
        ) );
    }

    protected static function soft_delete_entity( string $type, string $table, int $id, string $pk = 'id' ) : bool {
        global $wpdb;
        $table_recycle = $wpdb->prefix . 'tv_recycle_bin';

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$pk} = %d LIMIT 1", $id ), ARRAY_A );
        if ( ! $row ) return false;

        $bin_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_recycle}'" ) === $table_recycle;

        if ( $bin_exists ) {
            $wpdb->insert( $table_recycle, array(
                'entity_type'  => $type,
                'entity_table' => $table,
                'entity_pk'    => $pk,
                'entity_id'    => $id,
                'payload'      => wp_json_encode( $row ),
                'deleted_at'   => current_time( 'mysql' ),
                'deleted_by'   => (int) get_current_user_id(),
                'expires_at'   => gmdate( 'Y-m-d H:i:s', time() + 604800 ), // 7 days
                'status'       => 'deleted',
            ) );
        }

        $deleted = $wpdb->delete( $table, array( $pk => $id ) );
        return (bool) $deleted;
    }

    protected static function should_notify_from_request_default( WP_REST_Request $req, bool $default, string $key = 'notify_user' ) : bool {
        if ( ! $req->has_param( $key ) ) return $default;
        $val = $req->get_param( $key );
        if ( is_bool( $val ) ) return $val;
        $val = is_string( $val ) ? strtolower( trim( $val ) ) : $val;
        return in_array( $val, array( 1, '1', 'on', 'yes', 'true' ), true );
    }

    protected static function normalize_proofs( $proof_url ) : array {
        if ( empty( $proof_url ) ) return [];
        $decoded = json_decode( $proof_url, true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) return $decoded;
        return array( $proof_url );
    }

    /**
     * Return a plain Unicode currency symbol safe for JSON serialisation.
     *
     * NEVER use HTML entities here — this value goes into JSON REST responses.
     * TV_Currency::symbol() is the canonical source (also fixed to return Unicode).
     * The fallback below covers the case where the main plugin is not loaded.
     */
    protected static function currency_symbol( string $code ) : string {
        $code = strtoupper( trim( $code ) );
        if ( $code === '' ) return '';

        if ( class_exists( 'TV_Currency' ) ) {
            return TV_Currency::symbol( $code );
        }

        // Emergency fallback — all Unicode literals, zero HTML entities.
        $fallback = [
            'USD' => '$',
            'EUR' => "\u{20AC}",  // €
            'GBP' => "\u{00A3}",  // £
            'JPY' => "\u{00A5}",  // ¥
            'CNY' => "\u{00A5}",  // ¥
            'NGN' => "\u{20A6}",  // ?  ? was '?' / &#8358;
            'GHS' => "\u{20B5}",  // ?  ? was '?' / &#8373;
            'KES' => 'KSh',
            'UGX' => 'USh',
            'TZS' => 'TSh',
            'ZAR' => 'R',
            'INR' => "\u{20B9}",  // ?  ? was '?' / &#8377;
            'TRY' => "\u{20BA}",  // ?  ? was &#8378;
            'ILS' => "\u{20AA}",  // ?  ? was &#8362;
            'PHP' => "\u{20B1}",  // ?  ? was &#8369;
            'VND' => "\u{20AB}",  // ?  ? was &#8363;
            'KRW' => "\u{20A9}",  // ?  ? was &#8361;
            'THB' => "\u{0E3F}",  // ?  ? was &#3647;
            'HKD' => 'HK$',
            'SGD' => 'S$',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'NZD' => 'NZ$',
            'CHF' => 'CHF ',
            'MXN' => 'Mex$',
            'BRL' => 'R$',
            'ARS' => 'AR$',
            'CLP' => 'CLP$',
            'COP' => 'COL$',
            'PEN' => 'S/.',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
            'PLN' => 'zl',
            'CZK' => 'Kc',
            'HUF' => 'Ft',
            'RON' => 'lei',
            'BGN' => '??',
            'UAH' => '?',
            'RUB' => '?',
            'AED' => '?.?',
            'SAR' => '?.?',
            'QAR' => '?.?',
            'KWD' => '?.?',
            'BHD' => '?.?',
            'OMR' => '?.?.',
            'MAD' => 'DH',
            'EGP' => 'E£',
            'XOF' => 'CFA ',
            'XAF' => 'CFA ',
            'PKR' => '?',
            'BDT' => '?',
            'LKR' => 'Rs',
            'IDR' => 'Rp',
            'MYR' => 'RM',
            'USDT' => 'USDT ',
        ];

        return isset( $fallback[$code] ) ? $fallback[$code] : ( $code . ' ' );
    }
}