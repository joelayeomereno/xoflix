<?php
if ( ! defined( 'ABSPATH' ) ) exit;

trait StreamOS_Auth_Urls_Trait {

    public function custom_login_url( $url, $r, $f ) { 
            return home_url( '/login' ); 
        }

    public function custom_lostpassword_url() {
            return home_url( '/forgot-password' );
        }

    public function redirect_on_login_failed() {
            $ref = $_SERVER['HTTP_REFERER'] ?? '';
            if ( ! empty( $ref ) && ! strstr( $ref, 'wp-login' ) && ! strstr( $ref, 'wp-admin' ) ) { 
                wp_redirect( home_url( '/login?auth_error=Invalid%20credentials' ) ); 
                exit; 
            }
        }

}
