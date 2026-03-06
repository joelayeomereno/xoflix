<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Trait: StreamOS_Email_Verification_Trait
 * File: iptv-device-switcher/includes/auth/traits/trait-streamos-email-verification.php
 *
 * Handles all email verification logic:
 *  - Sending 6-digit codes (stored as transients)
 *  - Verifying submitted codes
 *  - Resend with 90-second cooldown
 *  - Change email + re-verify
 *
 * Option key: 'streamos_require_email_verification' (1 = on, 0 = off)
 * User meta:  'streamos_email_verified'  ? '1' = verified
 *             'streamos_phone'           ? normalized phone string
 *
 * Transient naming (no user_id in key to stay short):
 *   'sev_code_{hash}' ? 6-digit code + meta, expires 6 hours
 *   'sev_cool_{uid}'  ? resend cooldown flag, expires 90 seconds
 *
 * All codes for a user share the same hash bucket so multiple valid codes
 * coexist (user can enter any still-valid code from multiple resend attempts).
 */
trait StreamOS_Email_Verification_Trait {

    /* -- Helpers ------------------------------------------------------- */

    /**
     * Is the feature toggled ON by the admin?
     */
    private function verif_feature_enabled() : bool {
        return (bool) get_option( 'streamos_require_email_verification', 0 );
    }

    /**
     * Has this user already verified their email?
     */
    public static function is_email_verified( int $user_id ) : bool {
        return get_user_meta( $user_id, 'streamos_email_verified', true ) === '1';
    }

    /**
     * Build a deterministic transient prefix for a user+email pair.
     * We store multiple codes — each with its own expiry — under different
     * transient keys that all start with this prefix.
     * Key: sev_code_{uid}_{hash6}   (uid keeps it unique; hash is random per-send)
     */
    private function verif_code_key( int $uid, string $random_suffix ) : string {
        return 'sev_code_' . $uid . '_' . $random_suffix;
    }

    private function verif_cool_key( int $uid ) : string {
        return 'sev_cool_' . $uid;
    }

    /**
     * Generate + store a new code, send the email.
     * Returns ['success' => bool, 'error' => string, 'cooldown' => int]
     */
    private function verif_send_code( int $uid ) : array {
        // 1. Cooldown check
        $cool_key = $this->verif_cool_key( $uid );
        if ( $remaining = (int) get_transient( $cool_key ) ) {
            return [ 'success' => false, 'error' => 'cooldown', 'cooldown' => $remaining ];
        }

        $user = get_userdata( $uid );
        if ( ! $user ) {
            return [ 'success' => false, 'error' => 'no_user' ];
        }
        $email = $user->user_email;

        // 2. Generate code
        $code   = str_pad( (string) random_int( 0, 999999 ), 6, '0', STR_PAD_LEFT );
        $suffix = wp_generate_password( 8, false );
        $key    = $this->verif_code_key( $uid, $suffix );
        $ttl    = 6 * HOUR_IN_SECONDS;

        set_transient( $key, [
            'code'  => $code,
            'uid'   => $uid,
            'email' => $email,
        ], $ttl );

        // 3. Set cooldown (90 seconds)
        set_transient( $cool_key, 90, 90 );

        // 4. Send email using existing template engine
        $subject      = 'Your Verification Code';
        $message      = 'Use the code below to verify your email address. It expires in <strong>6 hours</strong>.<br><br>'
                      . '<div style="font-size:36px; font-weight:900; letter-spacing:10px; color:#4f46e5; text-align:center; padding:20px 0;">' . $code . '</div>'
                      . '<p style="text-align:center; color:#64748b; font-size:13px;">Didn\'t request this? You can safely ignore this email.</p>';
        $html         = $this->get_html_email_template( $subject, $message, 'Back to Dashboard', home_url('/dashboard') );
        $headers      = [ 'Content-Type: text/html; charset=UTF-8' ];

        $sent = wp_mail( $email, $subject, $html, $headers );

        if ( ! $sent ) {
            delete_transient( $key );
            return [ 'success' => false, 'error' => 'email_failed' ];
        }

        return [ 'success' => true, 'cooldown' => 90 ];
    }

    /**
     * Attempt to verify a submitted code.
     * Returns ['success' => bool, 'error' => string]
     */
    private function verif_check_code( int $uid, string $submitted_code ) : array {
        $submitted_code = trim( preg_replace( '/\D/', '', $submitted_code ) );
        if ( strlen( $submitted_code ) !== 6 ) {
            return [ 'success' => false, 'error' => 'invalid_format' ];
        }

        // Scan all transients matching this user's code pattern
        // We iterate via a small DB query (transients live in wp_options)
        global $wpdb;
        $prefix  = '_transient_sev_code_' . $uid . '_';
        $rows    = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options}
                 WHERE option_name LIKE %s",
                $wpdb->esc_like( $prefix ) . '%'
            )
        );

        foreach ( $rows as $row ) {
            $data = maybe_unserialize( $row->option_value );
            if ( ! is_array( $data ) || ! isset( $data['code'], $data['uid'] ) ) continue;
            if ( (int) $data['uid'] !== $uid ) continue;
            if ( ! hash_equals( (string) $data['code'], $submitted_code ) ) continue;

            // Match! Mark verified.
            update_user_meta( $uid, 'streamos_email_verified', '1' );

            // Clean up all this user's code transients
            foreach ( $rows as $cleanup ) {
                $option_key = str_replace( '_transient_', '', $cleanup->option_name );
                delete_transient( $option_key );
            }

            return [ 'success' => true ];
        }

        return [ 'success' => false, 'error' => 'wrong_code' ];
    }

    /* -- AJAX: Send / Resend Code -------------------------------------- */

    public function ajax_verif_send_code() {
        check_ajax_referer( 'streamos_verif_nonce', '_nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => 'Not authenticated.' ] );
        }

        $uid    = get_current_user_id();
        $result = $this->verif_send_code( $uid );

        if ( $result['success'] ) {
            wp_send_json_success( [
                'message'  => 'Verification code sent.',
                'cooldown' => 90,
            ] );
        } else {
            if ( $result['error'] === 'cooldown' ) {
                wp_send_json_error( [
                    'message'  => 'Please wait before requesting another code.',
                    'cooldown' => $result['cooldown'] ?? 90,
                ] );
            } else {
                wp_send_json_error( [
                    'message' => 'Failed to send code. Please try again.',
                ] );
            }
        }
    }

    /* -- AJAX: Verify Code --------------------------------------------- */

    public function ajax_verif_check_code() {
        check_ajax_referer( 'streamos_verif_nonce', '_nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => 'Not authenticated.' ] );
        }

        $uid  = get_current_user_id();
        $code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';

        $result = $this->verif_check_code( $uid, $code );

        if ( $result['success'] ) {
            wp_send_json_success( [ 'message' => 'Email verified successfully.' ] );
        } else {
            $msg = match ( $result['error'] ?? '' ) {
                'invalid_format' => 'Please enter a 6-digit code.',
                'wrong_code'     => 'Incorrect code. Please check and try again.',
                default          => 'Could not verify. Please try again.',
            };
            wp_send_json_error( [ 'message' => $msg ] );
        }
    }

    /* -- AJAX: Change Email (mid-verification) ------------------------- */

    public function ajax_verif_change_email() {
        check_ajax_referer( 'streamos_verif_nonce', '_nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => 'Not authenticated.' ] );
        }

        $uid       = get_current_user_id();
        $new_email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

        if ( ! is_email( $new_email ) ) {
            wp_send_json_error( [ 'message' => 'Please enter a valid email address.' ] );
        }
        if ( email_exists( $new_email ) && email_exists( $new_email ) !== $uid ) {
            wp_send_json_error( [ 'message' => 'That email address is already registered to another account.' ] );
        }

        // Update user email
        wp_update_user( [ 'ID' => $uid, 'user_email' => $new_email ] );

        // Reset verification status
        delete_user_meta( $uid, 'streamos_email_verified' );

        // Clear any existing codes for this user
        global $wpdb;
        $prefix = '_transient_sev_code_' . $uid . '_';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like( $prefix ) . '%'
            )
        );
        // Also clean timeout transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like( '_transient_timeout_sev_code_' . $uid . '_' ) . '%'
            )
        );

        // Send new code to new address
        $result = $this->verif_send_code( $uid );

        if ( $result['success'] ) {
            wp_send_json_success( [
                'message'  => 'Code sent to ' . $new_email,
                'email'    => $new_email,
                'cooldown' => 90,
            ] );
        } else {
            wp_send_json_error( [ 'message' => 'Email updated but could not send code. Please try again.' ] );
        }
    }

    /* -- AJAX: Get cooldown remaining --------------------------------- */

    public function ajax_verif_get_cooldown() {
        check_ajax_referer( 'streamos_verif_nonce', '_nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error();
        }

        $uid       = get_current_user_id();
        $cool_key  = $this->verif_cool_key( $uid );
        $remaining = get_transient( $cool_key );

        // WP transients don't give time-remaining directly — we store the value as seconds
        // but it counts down in real time. We'll compare stored vs time set.
        // Simpler: store the absolute expiry timestamp.
        $expiry_key = 'sev_cool_exp_' . $uid;
        $expiry     = (int) get_transient( $expiry_key );
        $now        = time();
        $remaining  = max( 0, $expiry - $now );

        wp_send_json_success( [ 'cooldown' => $remaining ] );
    }

    /* -- Helper: Send Initial Code on Signup -------------------------- */

    /**
     * Called from the signup handler right after user creation.
     * Sends the first verification code silently.
     */
    public function verif_send_initial_code( int $uid ) : void {
        if ( ! $this->verif_feature_enabled() ) return;
        $this->verif_send_code( $uid );
    }
}