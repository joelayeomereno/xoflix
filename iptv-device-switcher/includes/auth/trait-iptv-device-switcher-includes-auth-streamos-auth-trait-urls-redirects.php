<?php
if ( ! defined( 'ABSPATH' ) ) exit;

trait StreamOS_Auth_Trait_Urls_Redirects {

    // [MODIFIED] Removed the filter that forces all logins to /login.
    // This allows wp-login.php to function normally for standard WordPress access.
    public function custom_login_url( $url, $r, $f ) { 
        return $url; 
    }

    public function custom_lostpassword_url() {
        return home_url( '/forgot-password' );
    }

    public function redirect_on_login_failed() {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        
        // [MODIFIED] Check referer to determine where to send the user back.
        // If they came from /manager-login, send them back there.
        if ( strstr( $ref, 'manager-login' ) ) {
             wp_redirect( home_url( '/manager-login?error=Invalid%20credentials' ) ); 
             exit;
        }

        // If they came from standard /login (subscriber), send them there.
        // If they came from wp-login.php (standard WP), let WP handle it (do nothing).
        if ( ! empty( $ref ) && ! strstr( $ref, 'wp-login' ) && ! strstr( $ref, 'wp-admin' ) ) { 
            wp_redirect( home_url( '/login?auth_error=Invalid%20credentials' ) ); 
            exit; 
        }
    }
}