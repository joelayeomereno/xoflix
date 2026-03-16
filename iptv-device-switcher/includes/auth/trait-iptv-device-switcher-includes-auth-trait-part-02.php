<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Trait: IPTV_Device_Switcher_Includes_Auth_Trait_Part_02
 * Path: /iptv-device-switcher/includes/auth/trait-iptv-device-switcher-includes-auth-trait-part-02.php
 */
trait IPTV_Device_Switcher_Includes_Auth_Trait_Part_02 {


    public function custom_lostpassword_url() {
        return home_url( '/forgot-password' );
    }

    public function redirect_on_login_failed() {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        
        if ( strstr( $ref, 'manager-login' ) ) {
             wp_redirect( home_url( '/manager-login?error=Invalid%20credentials' ) ); 
             exit;
        }

        if ( strstr( $ref, 'wp-login' ) || strstr( $ref, 'wp-admin' ) ) { 
            return;
        }

        if ( ! empty( $ref ) ) { 
            wp_redirect( home_url( '/login?auth_error=Invalid%20credentials' ) ); 
            exit; 
        }
    }

    public function ajax_update_profile() {
        $uid = 0;
        if ( is_user_logged_in() ) {
            $uid = get_current_user_id();
        } else {
            $auth_id  = isset($_POST['auth_id'])  ? intval($_POST['auth_id'])                          : 0;
            $auth_sig = isset($_POST['auth_sig']) ? sanitize_text_field($_POST['auth_sig'])             : '';
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

        $name  = sanitize_text_field( $_POST['display_name'] );
        $phone = sanitize_text_field( $_POST['phone'] );
        
        $country = sanitize_text_field( $_POST['country'] );
        if ( ! empty($country) ) {
            $existing = get_user_meta($uid, 'billing_country', true);
            if ( empty($existing) || current_user_can('manage_options') ) {
                update_user_meta( $uid, 'billing_country', strtoupper( $country ) );
            }
        }

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
     * Checks if a user already exists (by email).
     * Used for real-time validation on signup forms.
     */
    public function ajax_check_user_availability() {
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(['message' => 'Invalid email format']);
        }

        if (email_exists($email) || username_exists($email)) {
            wp_send_json_error(['message' => 'Email already registered. Please log in.']);
        }

        wp_send_json_success(['message' => 'Email available']);
    }

    /**
     * Defensive proof upload validation (additive, non-breaking).
     */
    private function streamos_validate_proof_file(array $file) : array {
        $max_bytes    = (int) apply_filters('streamos_proof_upload_max_bytes', 10 * 1024 * 1024);
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

    public function handle_form_submissions() {
        if ( ! isset( $_POST['streamos_action'] ) ) return;

        // -- 1. FORGOT PASSWORD --------------------------------------------
        if ( $_POST['streamos_action'] === 'forgot_password' ) {
            if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'streamos_forgot_nonce' ) ) {
                wp_die( 'Security check failed' );
            }

            $user_login = trim( sanitize_text_field( $_POST['user_login'] ) );
            if ( empty( $user_login ) ) {
                wp_redirect( home_url( '/forgot-password?error=empty' ) ); exit;
            }

            $user_data = get_user_by( 'email', $user_login );
            if ( ! $user_data ) $user_data = get_user_by( 'login', $user_login );

            if ( $user_data ) {
                $key = get_password_reset_key( $user_data );
                if ( is_wp_error( $key ) ) {
                    wp_redirect( home_url( '/forgot-password?error=' . urlencode( $key->get_error_message() ) ) ); exit;
                }
                $reset_url    = home_url( "/reset-password?key=$key&login=" . rawurlencode( $user_data->user_login ) );
                $subject      = "Reset Your Password";
                $message      = "We received a request to reset the password for your account. Click the button below to choose a new password.";
                $html_content = $this->get_html_email_template( $subject, $message, "Reset Password", $reset_url );
                $headers      = array( 'Content-Type: text/html; charset=UTF-8' );
                if ( wp_mail( $user_data->user_email, $subject, $html_content, $headers ) ) {
                    wp_redirect( home_url( '/forgot-password?check_email=1&sent_to=' . rawurlencode( $user_data->user_email ) ) ); exit;
                } else {
                    wp_redirect( home_url( '/forgot-password?error=email_failed' ) ); exit;
                }
            } else {
                wp_redirect( home_url( '/forgot-password?error=invalid_user' ) ); exit;
            }
        }

        // -- 2. RESET PASSWORD ---------------------------------------------
        if ( $_POST['streamos_action'] === 'reset_password' ) {
            $user_login = isset($_POST['login']) ? sanitize_text_field( $_POST['login'] ) : '';
            $key        = isset($_POST['key'])   ? sanitize_text_field( $_POST['key'] )   : '';
            $pass1      = isset($_POST['pass1']) ? $_POST['pass1'] : '';
            $pass2      = isset($_POST['pass2']) ? $_POST['pass2'] : '';

            if ( empty($user_login) || empty($key) || empty($pass1) ) {
                wp_redirect( home_url( "/reset-password?key=$key&login=" . rawurlencode($user_login) . "&error=empty" ) ); exit;
            }
            if ( $pass1 !== $pass2 ) {
                wp_redirect( home_url( "/reset-password?key=$key&login=" . rawurlencode($user_login) . "&error=mismatch" ) ); exit;
            }

            $user = check_password_reset_key( $key, $user_login );
            if ( is_wp_error( $user ) ) {
                $error_code = $user->get_error_code() === 'expired_key' ? 'expired' : 'invalid_key';
                wp_redirect( home_url( "/reset-password?error=" . $error_code ) ); exit;
            }

            reset_password( $user, $pass1 );
            wp_redirect( home_url( '/login?reset=success' ) ); exit;
        }

        // -- 3. UPLOAD PROOF -----------------------------------------------
        if ( $_POST['streamos_action'] === 'upload_proof' ) {
             if ( ! is_user_logged_in() ) wp_die('Unauthorized');
             if ( isset($_POST['tv_proof_nonce']) && ! wp_verify_nonce( sanitize_text_field($_POST['tv_proof_nonce']), 'tv_upload_proof_' . (int)($_POST['payment_id'] ?? 0) ) ) {
                 wp_redirect(home_url('/dashboard?error=invalid_nonce')); exit;
             }
             $payment_id     = intval($_POST['payment_id']);
             global $wpdb;
             $table_payments = $wpdb->prefix . 'tv_payments';
             $payment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_payments WHERE id = %d AND user_id = %d", $payment_id, get_current_user_id()));
             if (!$payment) { wp_redirect(home_url('/dashboard?error=invalid_payment')); exit; }
             if (!function_exists('wp_handle_upload')) require_once(ABSPATH . 'wp-admin/includes/file.php');
             $uploadedfile = $_FILES['proof_file'];
             $valid = $this->streamos_validate_proof_file($uploadedfile);
             if (empty($valid['ok'])) { wp_redirect(home_url('/dashboard?error=upload_failed')); exit; }
             $movefile = wp_handle_upload($valid['file'], ['test_form' => false, 'mimes' => $valid['mimes']]);
             if ($movefile && !isset($movefile['error'])) {
                 $wpdb->update($table_payments, array('proof_url' => $movefile['url'], 'status' => 'PENDING_ADMIN_REVIEW'), array('id' => $payment_id));
                 wp_redirect(home_url('/dashboard?success=proof_uploaded')); exit;
             } else { wp_redirect(home_url('/dashboard?error=upload_failed')); exit; }
        }

        // -- 4. SIGNUP -----------------------------------------------------
        if ( $_POST['streamos_action'] === 'signup' ) {
            if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'streamos_signup_nonce' ) ) wp_die( 'Security check failed' );

            $email = sanitize_email( $_POST['user_email'] );
            $pass  = $_POST['user_password'];
            $conf  = $_POST['confirm_password'];

            if ( ! is_email( $email ) || email_exists( $email ) || strlen( $pass ) < 6 || $pass !== $conf ) {
                $err = ( $pass !== $conf ) ? 'Passwords do not match.' : 'Invalid details or email taken.';
                wp_redirect( home_url( '/signup?auth_error=' . urlencode( $err ) ) ); exit;
            }

            $uid = wp_insert_user( [
                'user_login'   => $email,
                'user_email'   => $email,
                'user_pass'    => $pass,
                'first_name'   => sanitize_text_field( $_POST['first_name'] ),
                'last_name'    => sanitize_text_field( $_POST['last_name'] ),
                'display_name' => sanitize_text_field( $_POST['first_name'] ) . ' ' . sanitize_text_field( $_POST['last_name'] ),
            ] );

            if ( is_wp_error( $uid ) ) {
                wp_redirect( home_url( '/signup?auth_error=' . urlencode( $uid->get_error_message() ) ) ); exit;
            }

            // Country & Currency
            $country_code     = strtoupper( sanitize_text_field( $_POST['billing_country'] ) );
            update_user_meta( $uid, 'billing_country', $country_code );
            $currency_map     = [
                'NG' => 'NGN', 'GH' => 'GHS', 'KE' => 'KES', 'ZA' => 'ZAR',
                'GB' => 'GBP', 'US' => 'USD', 'CA' => 'CAD', 'AU' => 'AUD',
                'IN' => 'INR', 'BR' => 'BRL', 'AE' => 'AED', 'EU' => 'EUR',
            ];
            $default_currency = $currency_map[$country_code] ?? 'USD';
            if ( empty( get_user_meta( $uid, 'tv_user_currency', true ) ) ) {
                update_user_meta( $uid, 'tv_user_currency', $default_currency );
            }

            // -- Verification feature (phone + email gate) -----------------
            $verif_enabled = (bool) get_option( 'streamos_require_email_verification', 0 );
            if ( $verif_enabled ) {
                // Full international phone number is assembled by the JS before submission
                $phone_raw = sanitize_text_field( wp_unslash( $_POST['user_phone'] ?? '' ) );
                if ( ! empty( $phone_raw ) ) {
                    $phone_clean = '+' . preg_replace( '/\D/', '', ltrim( $phone_raw, '+' ) );
                    update_user_meta( $uid, 'billing_phone', $phone_clean );
                }
                // Mark unverified so the dashboard overlay fires
                update_user_meta( $uid, 'streamos_email_verified', '0' );
                // Send the first 6-digit code via the Email Verification trait
                if ( method_exists( $this, 'verif_send_initial_code' ) ) {
                    $this->verif_send_initial_code( (int) $uid );
                }
            }

            if ( class_exists( 'StreamOS_User_Manager' ) ) {
                StreamOS_User_Manager::initialize_new_user( $uid );
            }

            wp_set_current_user( $uid );
            wp_set_auth_cookie( $uid );

            $redirect = home_url( '/dashboard' );
            if ( isset( $_GET['plan'] ) ) {
                $redirect = add_query_arg( ['view' => 'shop', 'select_plan' => $_GET['plan']], $redirect );
            }
            wp_redirect( $redirect );
            exit;
        }

        // -- 5. LOGIN ------------------------------------------------------
        if ( $_POST['streamos_action'] === 'login' ) {
            if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'streamos_login_nonce' ) ) wp_die( 'Security check failed' );
            $u = wp_signon( [
                'user_login'    => sanitize_text_field( $_POST['log'] ),
                'user_password' => $_POST['pwd'],
                'remember'      => isset( $_POST['rememberme'] ),
            ], is_ssl() );
            if ( is_wp_error( $u ) ) {
                wp_redirect( home_url( '/login?auth_error=Invalid%20username%20or%20password' ) ); exit;
            }
            wp_redirect( home_url( '/dashboard' ) ); exit;
        }
    }

}