<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

trait TV_Manager_Public_Trait_Ajax {

    public function ajax_save_checkout_session() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Session expired. Please login again.' ), 401 );
        }

        if ( isset( $_POST['_wpnonce'] ) && ! wp_verify_nonce( $_POST['_wpnonce'], 'tv_checkout_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed. Please refresh.' ), 400 );
        }

        $plan_id       = isset( $_POST['plan_id'] ) ? intval( $_POST['plan_id'] ) : 0;
        $method_id     = isset( $_POST['payment_method_id'] ) ? intval( $_POST['payment_method_id'] ) : 0;
        $connections   = isset( $_POST['connections'] ) ? intval( $_POST['connections'] ) : 1;
        $custom_months = isset( $_POST['custom_months'] ) ? intval( $_POST['custom_months'] ) : 1;
        $coupon_code   = isset( $_POST['coupon_code'] ) ? sanitize_text_field( $_POST['coupon_code'] ) : '';
        
        $target_sub_id = isset( $_POST['target_subscription_id'] ) ? intval( $_POST['target_subscription_id'] ) : 0;

        global $wpdb;

        // PROFESSIONAL FIX: Prevent duplicate submissions for requests currently in the administrative queue.
        if ( $target_sub_id > 0 ) {
            $pending_exists = $wpdb->get_var( $wpdb->prepare( 
                "SELECT id FROM {$wpdb->prefix}tv_payments 
                 WHERE subscription_id = %d AND user_id = %d 
                 AND status IN ('IN_PROGRESS','AWAITING_PROOF','pending','PENDING_ADMIN_REVIEW','APPROVED') 
                 LIMIT 1", 
                $target_sub_id, 
                get_current_user_id() 
            ) );

            if ( $pending_exists ) {
                wp_send_json_error( array( 
                    'message' => 'Our billing department is currently reviewing an existing request for this subscription. To ensure account accuracy and prevent duplicate processing, please allow the current transaction to be finalized before submitting a new one. Thank you for your patience.' 
                ), 400 );
            }
        }

        $plan   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tv_plans WHERE id = %d", $plan_id ) );
        $method = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tv_payment_methods WHERE id = %d", $method_id ) );

        if ( ! $plan || ! $method ) {
            wp_send_json_error( array( 'message' => 'Invalid plan or method.' ), 400 );
        }

        // 1. CANONICAL USD CALCULATION
        $base_price_usd = (float) $plan->price;
        $connections    = max( 1, (int) $connections );
        $months         = max( 1, (int) $custom_months );
        $gross_usd      = (float) ( $base_price_usd * $months * $connections );

        // 2. DISCOUNT LOGIC
        $discount_percent = 0;
        if ( ! empty( $plan->discount_tiers ) ) {
            $tiers = json_decode( $plan->discount_tiers, true );
            if ( is_array( $tiers ) ) {
                foreach ( $tiers as $tier ) {
                    if ( $months >= intval( $tier['months'] ) ) {
                        $discount_percent = max( $discount_percent, floatval( $tier['percent'] ) );
                    }
                }
            }
        }
        if ( $discount_percent == 0 ) {
            $global_discounts = get_option( 'tv_duration_discounts', array() );
            foreach ( $global_discounts as $d ) {
                if ( $months >= intval( $d['days'] / 30 ) ) {
                    $discount_percent = max( $discount_percent, floatval( $d['percent'] ) );
                }
            }
        }

        $quantity_discount_usd = ( $discount_percent > 0 ) ? ( $gross_usd * ( $discount_percent / 100 ) ) : 0;
        $subtotal_usd          = $gross_usd - $quantity_discount_usd;

        $coupon_discount_usd = 0;
        if ( $coupon_code ) {
            $coupon = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tv_coupons WHERE code = %s", $coupon_code ) );
            if ( $coupon ) {
                if ( $coupon->type === 'percent' ) {
                    $coupon_discount_usd = ( $subtotal_usd * ( (float) $coupon->amount / 100 ) );
                } elseif ( $coupon->type === 'fixed' ) {
                    $coupon_discount_usd = (float) $coupon->amount;
                }
            }
        }

        $total_discount_usd = $quantity_discount_usd + $coupon_discount_usd;
        $net_usd            = max( 0, $gross_usd - $total_discount_usd );

        // 3. CURRENCY CONVERSION (LOCKING)
        $currency_data = array(
            'code'         => 'USD',
            'rate'         => 1,
            'amount_local' => $net_usd,
        );

        if ( method_exists( $this, 'get_currency_data' ) ) {
            $currency_data = (array) $this->get_currency_data( $net_usd );
        }

        $currency_code = ! empty( $currency_data['code'] ) ? (string) $currency_data['code'] : 'USD';
        $fx_rate       = (float) ( ! empty( $currency_data['rate'] ) ? $currency_data['rate'] : 1 );
        $net_local     = (float) ( ! empty( $currency_data['amount_local'] ) ? $currency_data['amount_local'] : $net_usd );
        $gross_local    = round( $gross_usd * $fx_rate, 2 );
        $discount_local = max( 0, $gross_local - $net_local );

        // CALCULATE AND LOCK NGN VALUE SPECIFICALLY
        $ngn_rate = 0;
        $amount_ngn = 0;
        if (method_exists($this, 'get_ngn_rate')) {
            $ngn_rate = $this->get_ngn_rate();
            if ($ngn_rate > 0) {
                $amount_ngn = $net_usd * $ngn_rate;
            }
        }

        // GENERATE TEMP TRANSACTION ID
        $temp_txn_id = 'TMP-' . strtoupper(uniqid());

        // 4. PERSISTENCE
        $cycle_days = $plan ? max( 1, (int) $plan->duration_days ) : 1;
        $total_days = max( 1, (int) $months ) * $cycle_days;

        if ( method_exists( $this, 'create_subscription_and_payment' ) ) {
            $created = $this->create_subscription_and_payment(
                get_current_user_id(),
                $plan_id,
                $method,
                $net_local,
                $total_days,
                $connections,
                $coupon_code,
                $currency_code,
                $months,
                $net_usd,
                $fx_rate,
                $total_discount_usd,
                $discount_local,
                $gross_usd,
                $gross_local,
                $target_sub_id,
                $amount_ngn, // Pass NGN
                $temp_txn_id // Pass Temp TXN
            );

            if ( isset( $created['pay_id'] ) ) {
                $pay_id = $created['pay_id'];
                update_user_meta( get_current_user_id(), '_tv_active_pay_id', $pay_id );

                $wpdb->update(
                    "{$wpdb->prefix}tv_payments",
                    array(
                        'attempted_at' => current_time( 'mysql' ),
                        'status'       => 'IN_PROGRESS',
                    ),
                    array( 'id' => $pay_id )
                );

                $redirect_url = add_query_arg( array(
                    'tv_flow' => 'payment_pending',
                    'pay_id'  => $pay_id,
                ), home_url( '/' ) );

                wp_send_json_success( array(
                    'message'      => 'Session created',
                    'pay_id'       => $pay_id,
                    'redirect_url' => $redirect_url,
                ) );
            } else {
                wp_send_json_error( array( 'message' => 'Could not create record.' ), 500 );
            }
        } else {
            wp_send_json_error( array( 'message' => 'Internal error: persistence method missing.' ), 500 );
        }
    }

    public function ajax_validate_coupon() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Please login.' ), 401 );
        }

        $code   = isset( $_POST['code'] ) ? sanitize_text_field( $_POST['code'] ) : '';
        $amount = isset( $_POST['current_total'] ) ? floatval( $_POST['current_total'] ) : 0;

        global $wpdb;
        $coupon = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tv_coupons WHERE code = %s", $code ) );

        if ( ! $coupon ) {
            wp_send_json_error( array( 'message' => 'Invalid code' ) );
        }

        $discount_val = 0;
        if ( $coupon->type === 'percent' ) {
            $discount_val = ( $amount * ( $coupon->amount / 100 ) );
        } else {
            $coupon_db_amount = floatval( $coupon->amount );
            if ( method_exists( $this, 'get_currency_data' ) ) {
                $c_data       = $this->get_currency_data( $coupon_db_amount );
                $discount_val = $c_data['amount_local'];
            } else {
                $discount_val = $coupon_db_amount;
            }
        }

        wp_send_json_success( array(
            'message'         => 'Coupon valid!',
            'discount_amount' => $discount_val,
            'new_total'       => max( 0, $amount - $discount_val ),
            'code'            => $coupon->code,
        ) );
    }

    public function ajax_mark_payment_attempt() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Auth failed' ), 401 );
        }

        $pay_id = isset( $_POST['pay_id'] ) ? intval( $_POST['pay_id'] ) : 0;
        $nonce  = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( $_POST['_wpnonce'] ) : '';

        if ( ! $pay_id || ! wp_verify_nonce( $nonce, 'tv_mark_attempt_' . $pay_id ) ) {
            wp_send_json_error( array( 'message' => 'Security validation failed' ), 400 );
        }

        $user_id = get_current_user_id();
        global $wpdb;

        $wpdb->update(
            "{$wpdb->prefix}tv_payments",
            array(
                'attempted_at' => current_time( 'mysql' ),
                'status'       => 'IN_PROGRESS',
            ),
            array(
                'id'      => $pay_id,
                'user_id' => $user_id,
            )
        );

        update_user_meta( $user_id, '_tv_active_pay_id', $pay_id );

        wp_send_json_success( array( 'status' => 'marked' ) );
    }

    public function ajax_flutterwave_init_checkout() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not logged in' ), 401 );
        }

        $pay_id = isset( $_POST['pay_id'] ) ? intval( $_POST['pay_id'] ) : 0;
        $nonce  = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( $_POST['_wpnonce'] ) : '';

        if ( ! $pay_id || empty( $nonce ) || ! wp_verify_nonce( $nonce, 'tv_flutterwave_init_' . $pay_id ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ), 400 );
        }

        $user_id = get_current_user_id();
        global $wpdb;

        $payment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tv_payments WHERE id = %d AND user_id = %d", $pay_id, $user_id ) );

        if ( ! $payment ) {
            wp_send_json_error( array( 'message' => 'Payment record not found' ), 404 );
        }

        $method = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tv_payment_methods WHERE name = %s LIMIT 1", $payment->method ) );
        
        $base_url = ( ! empty( $method ) && ! empty( $method->link ) ) ? trim( $method->link ) : '';

        if ( empty( $base_url ) && empty( $method->flutterwave_enabled ) ) {
            wp_send_json_error( array( 'message' => 'Configuration error.' ), 400 );
        }

        $tx_ref = 'TV-' . $pay_id . '-' . time();

        $wpdb->update(
            "{$wpdb->prefix}tv_payments",
            array(
                'transaction_id' => $tx_ref,
                'status'         => 'IN_PROGRESS',
                'attempted_at'   => current_time( 'mysql' ),
            ),
            array( 'id' => $pay_id )
        );

        update_user_meta( $user_id, '_tv_active_pay_id', $pay_id );

        $user = get_userdata( $user_id );
        $final_link = add_query_arg( array(
            'tx_ref'   => $tx_ref,
            'email'    => $user->user_email,
            'amount'   => $payment->amount,
            'currency' => $payment->currency,
        ), $base_url );

        wp_send_json_success( array( 'link' => $final_link ) );
    }

    public function ajax_cancel_payment() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Auth failed' ), 401 );
        }

        $pay_id = intval( $_POST['pay_id'] );
        if ( ! $pay_id ) {
            wp_send_json_error( array( 'message' => 'Invalid ID' ), 400 );
        }

        global $wpdb;
        $wpdb->update(
            "{$wpdb->prefix}tv_payments",
            array( 'status' => 'CANCELLED' ),
            array(
                'id'      => $pay_id,
                'user_id' => get_current_user_id(),
            )
        );

        delete_user_meta( get_current_user_id(), '_tv_active_pay_id' );
        wp_send_json_success();
    }

    public function ajax_check_transaction_status() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Auth failed' ), 401 );
        }

        $pay_id = isset( $_POST['pay_id'] ) ? intval( $_POST['pay_id'] ) : 0;
        global $wpdb;

        $status = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$wpdb->prefix}tv_payments WHERE id = %d", $pay_id ) );

        wp_send_json_success( array(
            'is_completed' => in_array( $status, array( 'APPROVED', 'completed', 'active' ), true ),
            'is_cancelled' => in_array( $status, array( 'CANCELLED', 'REJECTED' ), true ),
            'redirect_url' => add_query_arg( array( 'finish_payment' => 1 ), home_url( '/dashboard' ) ),
        ) );
    }

    public function ajax_secure_proof_download() {
        if ( ! is_user_logged_in() ) {
            status_header( 401 );
            wp_die( 'Unauthorized' );
        }

        $pay_id = isset( $_REQUEST['pay_id'] ) ? intval( $_REQUEST['pay_id'] ) : 0;
        if ( ! $pay_id ) {
            status_header( 400 );
            wp_die( 'Invalid payment.' );
        }

        global $wpdb;
        $payment = $wpdb->get_row( $wpdb->prepare( "SELECT id, user_id, proof_url FROM {$wpdb->prefix}tv_payments WHERE id = %d", $pay_id ) );
        
        if ( ! $payment ) {
            status_header( 404 );
            wp_die( 'Not found.' );
        }

        $current  = get_current_user_id();
        $is_admin = current_user_can( 'manage_options' );

        if ( (int) $payment->user_id !== (int) $current && ! $is_admin ) {
            status_header( 403 );
            wp_die( 'Forbidden' );
        }

        $attachments = get_posts( array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'orderby'        => 'ID',
            'order'          => 'DESC',
            'meta_key'       => 'tv_payment_id',
            'meta_value'     => (int) $pay_id,
        ) );

        if ( empty( $attachments ) ) {
            if ( $is_admin && ! empty( $payment->proof_url ) ) {
                $url = $payment->proof_url;
                if ( is_string( $url ) && strlen( $url ) > 0 && $url[0] === '[' ) {
                    $decoded = json_decode( $url, true );
                    if ( is_array( $decoded ) && ! empty( $decoded[0] ) ) {
                        $url = $decoded[0];
                    }
                }
                wp_redirect( esc_url_raw( $url ) );
                exit;
            }
            status_header( 404 );
            wp_die( 'No proof found.' );
        }

        $att_id    = (int) $attachments[0]->ID;
        $file_path = get_attached_file( $att_id );

        if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
            status_header( 404 );
            wp_die( 'File missing.' );
        }

        $mime = get_post_mime_type( $att_id );
        if ( empty( $mime ) ) $mime = 'application/octet-stream';
        $filename = basename( $file_path );

        if ( ob_get_level() ) @ob_end_clean();

        nocache_headers();
        header( 'Content-Type: ' . $mime );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . filesize( $file_path ) );
        readfile( $file_path );
        exit;
    }
}