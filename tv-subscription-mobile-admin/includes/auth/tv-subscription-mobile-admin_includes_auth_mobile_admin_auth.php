<?php
if (!defined('ABSPATH')) { exit; }

class TV_Subscription_Mobile_Admin_Includes_Auth_Mobile_Admin_Auth {

    /**
     * Capability required for core access to the mobile admin.
     * Mirrors the typical WP Admin access patterns.
     */
    public static function current_user_can_access() : bool {
        return is_user_logged_in() && current_user_can('manage_options');
    }

    public static function require_access_or_redirect() : void {
        if (self::current_user_can_access()) {
            return;
        }
        wp_safe_redirect(home_url('/admin/login'));
        exit;
    }

    public static function handle_login_post() : array {
        $errors = array();

        if (!isset($_POST['tv_mobile_admin_login_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tv_mobile_admin_login_nonce'])), 'tv_mobile_admin_login')) {
            $errors[] = 'Security check failed. Please refresh and try again.';
            return $errors;
        }

        $username = isset($_POST['log']) ? sanitize_text_field(wp_unslash($_POST['log'])) : '';
        $password = isset($_POST['pwd']) ? (string) wp_unslash($_POST['pwd']) : '';

        $creds = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true,
        );

        $user = wp_signon($creds, is_ssl());

        if (is_wp_error($user)) {
            $errors[] = $user->get_error_message();
            return $errors;
        }

        return $errors;
    }
}
