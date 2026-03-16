<?php
if ( ! defined( 'ABSPATH' ) ) exit;

trait StreamOS_Auth_Trait_Ajax {

    public function ajax_update_profile() {
        $uid = 0;
        if ( is_user_logged_in() ) {
            $uid = get_current_user_id();
        } else {
            $auth_id = isset($_POST['auth_id']) ? intval($_POST['auth_id']) : 0;
            $auth_sig = isset($_POST['auth_sig']) ? sanitize_text_field($_POST['auth_sig']) : '';
            if ( $auth_id > 0 && ! empty($auth_sig) ) {
                $expected = hash_hmac('sha256', 'streamos_auth_' . $auth_id, wp_salt('auth'));
                if ( hash_equals($expected, $auth_sig) ) {
                    $uid = $auth_id;
                    wp_set_current_user($uid);
                }
            }
        }

        if ( $uid === 0 ) {
            wp_send_json_error( ['message' => 'Session expired. Please log in again.'] );
        }
        
        if ( isset($_POST['_wpnonce']) && ! wp_verify_nonce( sanitize_text_field($_POST['_wpnonce']), 'tv_checkout_nonce' ) ) {
            wp_send_json_error( ['message' => 'Security token invalid.'] );
        }

        $name = sanitize_text_field( $_POST['display_name'] );
        $phone = sanitize_text_field( $_POST['phone'] );
        
        // [MODIFIED] Country Update Restriction
        // Users cannot change their country for data integrity. Only admin logic handles this.
        // We do NOT update 'billing_country' here unless the user is an admin or it's empty.
        $country = sanitize_text_field( $_POST['country'] );
        if ( ! empty($country) ) {
            $existing = get_user_meta($uid, 'billing_country', true);
            if ( empty($existing) || current_user_can('manage_options') ) {
                update_user_meta( $uid, 'billing_country', strtoupper( $country ) );
            }
        }

        // [FIX] Update Currency Preference
        // This is allowed and triggers payment method recalculation
        $currency = isset($_POST['currency']) ? sanitize_text_field($_POST['currency']) : '';
        if (!empty($currency) && strlen($currency) === 3) {
            update_user_meta($uid, 'tv_user_currency', strtoupper($currency));
        }

        if ( ! empty( $name ) ) {
            $update = wp_update_user( [ 'ID' => $uid, 'display_name' => $name ] );
            if ( is_wp_error( $update ) ) {
                wp_send_json_error( ['message' => 'Could not update name: ' . $update->get_error_message()] );
            }
            $parts = explode( ' ', $name, 2 );
            update_user_meta( $uid, 'first_name', $parts[0] );
            if ( isset( $parts[1] ) ) update_user_meta( $uid, 'last_name', $parts[1] );
        }

        if ( isset( $_POST['phone'] ) ) { 
            update_user_meta( $uid, 'billing_phone', $phone );
        }

        wp_send_json_success( ['message' => 'Profile updated successfully.'] );
    }


    /**
     * Defensive proof upload validation (additive, non-breaking).
     */
    private function streamos_validate_proof_file(array $file) : array {
        $max_bytes = (int) apply_filters('streamos_proof_upload_max_bytes', 10 * 1024 * 1024); // 10MB
        $allowed_mimes = (array) apply_filters('streamos_proof_upload_allowed_mimes', array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
            'gif'          => 'image/gif',
            'webp'         => 'image/webp',
            'pdf'          => 'application/pdf',
        ));

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return array('ok' => false, 'error' => 'invalid_upload');
        }
        if (isset($file['size']) && (int)$file['size'] > $max_bytes) {
            return array('ok' => false, 'error' => 'file_too_large');
        }
        $check = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed_mimes);
        if (empty($check['ext']) || empty($check['type'])) {
            return array('ok' => false, 'error' => 'invalid_type');
        }
        $file['type'] = $check['type'];
        return array('ok' => true, 'file' => $file, 'mimes' => $allowed_mimes);
    }

}
