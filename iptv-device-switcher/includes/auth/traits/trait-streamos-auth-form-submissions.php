<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/auth/trait-iptv-device-switcher-includes-auth-trait-part-01.php';
require_once __DIR__ . '/auth/trait-iptv-device-switcher-includes-auth-trait-part-02.php';
require_once __DIR__ . '/auth/traits/trait-streamos-email-verification.php';

class StreamOS_Auth {
    // Refactor note: methods extracted into traits to reduce monolithic file size.
    use IPTV_Device_Switcher_Includes_Auth_Trait_Part_01,
        IPTV_Device_Switcher_Includes_Auth_Trait_Part_02,
        StreamOS_Email_Verification_Trait;

    public function __construct() {
        // Start buffering immediately to catch any stray output
        add_action('init', function() { ob_start(); }, 1);
        
        // 1. Shadow Session Detector (The "Advanced Sandbox" Logic)
        // Must run early on init to setup user globals before logic.php runs
        add_action('init', array($this, 'detect_and_apply_shadow_session'), 5);

        // Form Handlers
        add_action( 'init', array( $this, 'handle_form_submissions' ), 20 );
        
        // Impersonation Handler (Priority 10)
        add_action( 'init', array( $this, 'handle_admin_impersonation' ), 10 );

        // Impersonation session bounds + cleanup (Priority 9)
        add_action( 'init', array( $this, 'enforce_impersonation_session_bounds' ), 9 );

        add_filter( 'login_url', array( $this, 'custom_login_url' ), 10, 3 );
        add_filter( 'lostpassword_url', array( $this, 'custom_lostpassword_url' ), 10, 0 );
        
        // Redirects
        add_action( 'wp_login_failed', array( $this, 'redirect_on_login_failed' ) );
        
        // AJAX: Profile Updates
        add_action( 'wp_ajax_streamos_update_profile', array( $this, 'ajax_update_profile' ) );
        add_action( 'wp_ajax_nopriv_streamos_update_profile', array( $this, 'ajax_update_profile' ) );

        // AJAX: Real-time Check for User Availability
        add_action( 'wp_ajax_streamos_check_user', array( $this, 'ajax_check_user_availability' ) );
        add_action( 'wp_ajax_nopriv_streamos_check_user', array( $this, 'ajax_check_user_availability' ) );

        // AJAX: Email Verification (all logged-in only)
        add_action( 'wp_ajax_streamos_verif_send',     array( $this, 'ajax_verif_send_code' ) );
        add_action( 'wp_ajax_streamos_verif_check',    array( $this, 'ajax_verif_check_code' ) );
        add_action( 'wp_ajax_streamos_verif_email',    array( $this, 'ajax_verif_change_email' ) );
        add_action( 'wp_ajax_streamos_verif_cooldown', array( $this, 'ajax_verif_get_cooldown' ) );
    }

    /**
     * [ADVANCED SANDBOX]
     * Detects the Shadow Cookie and temporarily switches user context for the duration of the request.
     * Only works if the *real* logged-in user is an Administrator.
     * Does NOT affect WP Admin Dashboard routes.
     */
    public function detect_and_apply_shadow_session() {
        if (!isset($_COOKIE['tv_shadow_token'])) return;

        // 1. Security: Verify REAL user is Admin
        // We use wp_get_current_user() which fetches from the actual session cookie
        $real_user = wp_get_current_user();
        if (!$real_user || !user_can($real_user, 'manage_options')) {
            return; // Security fail or not logged in
        }

        // 2. Decode Token
        $raw = base64_decode($_COOKIE['tv_shadow_token']);
        $parts = explode('|', $raw);
        if (count($parts) !== 4) return; // Invalid format

        list($admin_id, $target_id, $expiry, $sig) = $parts;

        // 3. Verify Integrity
        // Rebuild signature to check tampering
        $check_data = $admin_id . '|' . $target_id . '|' . $expiry;
        $expected_sig = hash_hmac('sha256', $check_data, wp_salt('auth'));

        if (!hash_equals($expected_sig, $sig)) return; // Signature mismatch
        if (time() > $expiry) return; // Expired
        if ((int)$admin_id !== (int)$real_user->ID) return; // Token belongs to another admin

        // 4. Apply Context Switch (Frontend & Dashboard AJAX Only)
        // We do NOT want to switch context if the admin is visiting /wp-admin/ or /manager/
        $is_backend = is_admin() && !defined('DOING_AJAX');
        
        // Exclude Manager Routes
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/manager') !== false || strpos($uri, '/wp-admin') !== false) {
            $is_backend = true;
        }

        // For AJAX, we assume it's frontend ajax if the referer is not admin
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $ref = $_SERVER['HTTP_REFERER'] ?? '';
            if (strpos($ref, '/wp-admin') !== false || strpos($ref, '/manager') !== false) {
                $is_backend = true;
            } else {
                $is_backend = false; // Allow swap for frontend AJAX
            }
        }

        if (!$is_backend) {
            // THE SWAP
            wp_set_current_user($target_id);
            // We do NOT set the auth cookie. The browser still has the Admin cookie.
            // WP Global $current_user now points to Target User for this request.
            
            // Define constant so templates know we are in shadow mode
            if (!defined('TV_SHADOW_MODE')) {
                define('TV_SHADOW_MODE', true);
            }
        }
    }
}