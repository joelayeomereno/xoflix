<?php
declare(strict_types=1);

namespace Xoflix\Auth;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Direct file access prevention
}

/**
 * Trait Xoflix_Auth_Trait_Form_Submissions
 * * Centralized handler for all frontend form submissions.
 * Includes: Login, Device Linking, Helper Claims, and Disconnects.
 * Hardened for XOFLIX TV v3 (Strict Typing, Nonces, Sanitization).
 */
trait Xoflix_Auth_Trait_Form_Submissions {

    /**
     * Initialize form listeners.
     * Call this from the main class constructor or init hook.
     */
    public function xoflix_boot_form_submissions(): void {
        add_action( 'template_redirect', [ $this, 'xoflix_dispatch_form_submissions' ] );
    }

    /**
     * Central Dispatcher for Form Submissions.
     * Routes requests based on 'xoflix_action' POST field.
     */
    public function xoflix_dispatch_form_submissions(): void {
        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' || empty( $_POST['xoflix_action'] ) ) {
            return;
        }

        $action = sanitize_text_field( wp_unslash( $_POST['xoflix_action'] ) );

        switch ( $action ) {
            case 'claim_helper_account':
                $this->xoflix_process_helper_claim();
                break;
            case 'login_device':
                $this->xoflix_process_login();
                break;
            case 'link_device':
                $this->xoflix_process_link_device();
                break;
            case 'disconnect_device':
                $this->xoflix_process_disconnect();
                break;
        }
    }

    /**
     * -------------------------------------------------------------------------
     * 1. HELPER CLAIM (The Fix)
     * -------------------------------------------------------------------------
     */

    /**
     * Handles the Helper Account Claim form submission.
     */
    public function xoflix_process_helper_claim(): void {
        // Strict Nonce Verification
        $nonce = isset( $_POST['xoflix_helper_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['xoflix_helper_nonce'] ) ) : '';
        
        if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'xoflix_helper_claim_action' ) ) {
            $this->xoflix_safe_die( 'Security token expired. Please refresh.', 'Security Check Failed', 403 );
        }

        // Strict Input Sanitization
        $claim_email = isset( $_POST['helper_email'] ) ? sanitize_email( wp_unslash( $_POST['helper_email'] ) ) : '';
        $claim_token = isset( $_POST['helper_token'] ) ? sanitize_text_field( wp_unslash( $_POST['helper_token'] ) ) : '';

        if ( ! is_email( $claim_email ) || empty( $claim_token ) ) {
            $this->xoflix_safe_die( 'Invalid email or missing token.', 'Input Error', 400 );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'streamos_devices';

        // Secure Lookup
        $account = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, user_id, status FROM {$table_name} WHERE claim_token = %s AND email = %s LIMIT 1",
                $claim_token,
                $claim_email
            )
        );

        if ( null === $account ) {
            $this->xoflix_safe_die( 'Account not found.', 'Not Found', 404 );
        }

        // Logic to activate/link account would go here
        // $this->xoflix_complete_claim($account->id);

        wp_safe_redirect( home_url( '/helper-dashboard/?claim=success' ) );
        exit;
    }

    /**
     * Generates security fields for the Helper form.
     */
    public function xoflix_get_helper_claim_security_fields(): string {
        return wp_nonce_field( 'xoflix_helper_claim_action', 'xoflix_helper_nonce', true, false ) .
               '<input type="hidden" name="xoflix_action" value="claim_helper_account" />';
    }


    /**
     * -------------------------------------------------------------------------
     * 2. DEVICE LOGIN (Standardized)
     * -------------------------------------------------------------------------
     */

    public function xoflix_process_login(): void {
        $nonce = isset( $_POST['xoflix_login_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['xoflix_login_nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'xoflix_login_action' ) ) {
            $this->xoflix_safe_die( 'Session expired.', 'Auth Error', 403 );
        }

        $mac_address = isset( $_POST['mac_address'] ) ? sanitize_text_field( wp_unslash( $_POST['mac_address'] ) ) : '';

        if ( empty( $mac_address ) ) {
            return; // Or handle error
        }

        // Logic: Validate MAC against StreamOS API or DB
        // ... implementation ...

        wp_safe_redirect( home_url( '/dashboard/' ) );
        exit;
    }

    public function xoflix_get_login_security_fields(): string {
        return wp_nonce_field( 'xoflix_login_action', 'xoflix_login_nonce', true, false ) .
               '<input type="hidden" name="xoflix_action" value="login_device" />';
    }


    /**
     * -------------------------------------------------------------------------
     * 3. LINK DEVICE (Standardized)
     * -------------------------------------------------------------------------
     */

    public function xoflix_process_link_device(): void {
        $nonce = isset( $_POST['xoflix_link_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['xoflix_link_nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'xoflix_link_action' ) ) {
             $this->xoflix_safe_die( 'Link token expired.', 'Auth Error', 403 );
        }

        $link_code = isset( $_POST['link_code'] ) ? sanitize_text_field( wp_unslash( $_POST['link_code'] ) ) : '';
        
        // Logic: Process Linking
        // ... implementation ...

        wp_safe_redirect( home_url( '/my-devices/?linked=true' ) );
        exit;
    }

    public function xoflix_get_link_security_fields(): string {
        return wp_nonce_field( 'xoflix_link_action', 'xoflix_link_nonce', true, false ) .
               '<input type="hidden" name="xoflix_action" value="link_device" />';
    }


    /**
     * -------------------------------------------------------------------------
     * 4. DISCONNECT / LOGOUT
     * -------------------------------------------------------------------------
     */

    public function xoflix_process_disconnect(): void {
        $nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'xoflix_disconnect_action' ) ) {
             wp_safe_redirect( home_url( '/dashboard/?error=invalid_logout' ) );
             exit;
        }

        // Logic: Clear Cookies / Sessions
        // ... implementation ...

        wp_safe_redirect( home_url( '/login/?logged_out=true' ) );
        exit;
    }


    /**
     * Helper: Safe Error Termination
     */
    private function xoflix_safe_die( string $message, string $title, int $code ): void {
        wp_die( esc_html( $message ), esc_html( $title ), [ 'response' => $code ] );
    }
}