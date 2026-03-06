<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * File: iptv-device-switcher/includes/class-page-ownership.php
 * Path: /iptv-device-switcher/includes/class-page-ownership.php
 */
class IPTV_Device_Switcher_Includes_Class_Page_Ownership {

    /**
     * True when this request is intended to be owned/rendered by StreamOS.
     * This centralizes all previously duplicated heuristics.
     */
    public static function is_streamos_owned_request() : bool {
        $r = get_query_var( 'streamos_route' );
        $flow = get_query_var( 'tv_flow' );

        if ( ! empty( $r ) || ! empty( $flow ) ) {
            return true;
        }

        // Preserve historical behavior: homepage/front-page is treated as a StreamOS surface.
        return ( is_front_page() || is_home() );
    }

    public static function is_flow_page() : bool {
        return ! empty( get_query_var( 'tv_flow' ) );
    }

    public static function current_route() : string {
        $flow = get_query_var( 'tv_flow' );
        if ( ! empty( $flow ) ) {
            return 'flow-' . sanitize_key( (string) $flow );
        }

        $r = get_query_var( 'streamos_route' );
        if ( ! empty( $r ) ) {
            return sanitize_key( (string) $r );
        }

        if ( is_front_page() || is_home() ) {
            return 'home';
        }

        return '';
    }
}
