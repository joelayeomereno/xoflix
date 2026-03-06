<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait: TV_Manager_Public_Trait_Actions
 * Handles User-driven events like Subscription Requests and Proof Uploads.
 * Unabridged Hardening: Added Notification trigger for Proof Receipt.
 */
trait TV_Manager_Public_Trait_Actions {

    public function handle_subscription_request() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        if ( isset( $_POST['plan_id'] ) ) {
            $user_id = get_current_user_id();

            $target_sub_id = isset($_POST['target_subscription_id']) ? intval($_POST['target_subscription_id']) : 0;

            $pending = array(
                'plan_id'           => intval( $_POST['plan_id'] ),
                'payment_method_id' => isset( $_POST['payment_method_id'] ) ? intval( $_POST['payment_method_id'] ) : 0,
                'connections'       => isset( $_POST['connections'] ) ? intval( $_POST['connections'] ) : 1,
                'custom_months'     => isset( $_POST['custom_months'] ) ? intval( $_POST['custom_months'] ) : 1,
                'coupon_code'       => isset( $_POST['coupon_code'] ) ? sanitize_text_field( $_POST['coupon_code'] ) : '',
                'target_sub_id'     => $target_sub_id 
            );

            delete_user_meta( $user_id, self::USER_META_PENDING_CHECKOUT );
            update_user_meta( $user_id, self::USER_META_PENDING_CHECKOUT, $pending );
        }
    }

    public function handle_multi_step_actions() {
        if ( isset( $_POST['payment_proof_submit'] ) && is_user_logged_in() ) {
            $pay_id = isset( $_POST['payment_id'] ) ? intval( $_POST['payment_id'] ) : 0;
            if ( $pay_id ) {
                $this->handle_proof_upload( $pay_id, get_current_user_id() );
            }
        }
    }

    private function tv_validate_proof_upload_file( array $file ) : array {
        $max_bytes     = (int) apply_filters( 'tv_proof_upload_max_bytes', 10 * 1024 * 1024 );
        $allowed_mimes = (array) apply_filters( 'tv_proof_upload_allowed_mimes', array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
            'gif'          => 'image/gif',
            'webp'         => 'image/webp',
            'pdf'          => 'application/pdf',
        ) );

        if ( ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
            return array( 'ok' => false, 'error' => 'invalid_upload' );
        }
        if ( isset( $file['size'] ) && (int) $file['size'] > $max_bytes ) {
            return array( 'ok' => false, 'error' => 'file_too_large' );
        }
        $check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed_mimes );
        if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
            return array( 'ok' => false, 'error' => 'invalid_type' );
        }
        $file['type'] = $check['type'];
        return array( 'ok' => true, 'file' => $file, 'mimes' => $allowed_mimes );
    }

    private function tv_create_proof_attachment_from_upload( array $uploaded, int $pay_id, int $user_id ) : int {
        if ( empty( $uploaded['file'] ) || ! file_exists( $uploaded['file'] ) ) {
            return 0;
        }
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        $filetype   = wp_check_filetype( basename( $uploaded['file'] ), null );
        $attachment = array(
            'post_mime_type' => ! empty( $filetype['type'] ) ? $filetype['type'] : ( isset( $uploaded['type'] ) ? $uploaded['type'] : '' ),
            'post_title'     => sanitize_file_name( pathinfo( $uploaded['file'], PATHINFO_FILENAME ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'post_author'    => $user_id,
        );
        $attach_id = wp_insert_attachment( $attachment, $uploaded['file'], 0 );
        if ( is_wp_error( $attach_id ) || empty( $attach_id ) ) {
            return 0;
        }
        $attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded['file'] );
        if ( ! empty( $attach_data ) && ! is_wp_error( $attach_data ) ) {
            wp_update_attachment_metadata( $attach_id, $attach_data );
        }
        update_post_meta( $attach_id, 'tv_payment_id', (int) $pay_id );
        update_post_meta( $attach_id, 'tv_user_id', (int) $user_id );
        if ( ! empty( $uploaded['url'] ) ) {
            update_post_meta( $attach_id, 'tv_proof_public_url', esc_url_raw( $uploaded['url'] ) );
        }
        return (int) $attach_id;
    }

    /**
     * Core Transaction Creator
     * Supports creating NEW subscriptions OR extending EXISTING ones.
     */
    function create_subscription_and_payment( $user_id, $plan_id, $method, $amount, $total_duration_days, $connections = 1, $coupon_code = '', $currency = 'USD', $months = 0, $amount_usd = null, $fx_rate = null, $discount_usd = null, $discount_local = null, $gross_usd = null, $gross_local = null, $target_sub_id = 0, $amount_ngn = null, $txn_id = null ) {
        
        $sub_id = 0;

        // 1. Determine Subscription Strategy
        if ( $target_sub_id > 0 ) {
            $existing = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT id FROM {$this->table_subs} WHERE id = %d AND user_id = %d", $target_sub_id, $user_id ) );
            if ( $existing ) {
                $sub_id = (int) $existing->id;
            }
        }

        // If no valid target found, create NEW subscription
        if ( $sub_id === 0 ) {
            $start_date  = current_time( 'mysql' );
            $end_date    = date( 'Y-m-d H:i:s', strtotime( "+{$total_duration_days} days" ) );
            $connections = max( 1, (int) $connections );

            static $tv__has_duration_months_col = null;
            if ( $tv__has_duration_months_col === null ) {
                $tv__has_duration_months_col = ! empty( $this->wpdb->get_results( "SHOW COLUMNS FROM {$this->table_subs} LIKE 'duration_months'" ) );
            }

            $sub_insert = array(
                'user_id'     => $user_id,
                'plan_id'     => $plan_id,
                'start_date'  => $start_date,
                'end_date'    => $end_date,
                'status'      => 'pending',
                'connections' => $connections,
            );

            if ( $tv__has_duration_months_col ) {
                $sub_insert['duration_months'] = max( 0, (int) $months );
            }

            $this->wpdb->insert( $this->table_subs, $sub_insert );
            $sub_id = $this->wpdb->insert_id;
        }

        // 2. Persist Purchased Duration (Meta)
        if ( ! empty( $sub_id ) && (int) $months > 0 && class_exists( 'TV_Subscription_Meta' ) ) {
            TV_Subscription_Meta::set_months( (int) $sub_id, (int) $months );
        }

        // 3. Insert Payment Record
        static $tv__payments_cols_checked = false;
        if ( ! $tv__payments_cols_checked ) {
            $tv__payments_cols_checked = true;
            $cols   = $this->wpdb->get_col( "SHOW COLUMNS FROM {$this->table_payments}", 0 );
            $colset = array();
            if ( is_array( $cols ) ) foreach ( $cols as $c ) $colset[ $c ] = true;
            
            $adds = array();
            if ( empty( $colset['amount_usd'] ) ) $adds[] = "ADD COLUMN amount_usd decimal(10,2) DEFAULT NULL";
            if ( empty( $colset['amount_ngn'] ) ) $adds[] = "ADD COLUMN amount_ngn decimal(10,2) DEFAULT NULL";
            if ( empty( $colset['fx_rate'] ) ) $adds[] = "ADD COLUMN fx_rate decimal(18,8) DEFAULT NULL";
            if ( empty( $colset['coupon_code'] ) ) $adds[] = "ADD COLUMN coupon_code varchar(50) DEFAULT NULL";
            if ( empty( $colset['discount_usd'] ) ) $adds[] = "ADD COLUMN discount_usd decimal(10,2) DEFAULT NULL";
            if ( empty( $colset['discount_local'] ) ) $adds[] = "ADD COLUMN discount_local decimal(10,2) DEFAULT NULL";
            if ( empty( $colset['gross_usd'] ) ) $adds[] = "ADD COLUMN gross_usd decimal(10,2) DEFAULT NULL";
            if ( empty( $colset['gross_local'] ) ) $adds[] = "ADD COLUMN gross_local decimal(10,2) DEFAULT NULL";
            
            if ( ! empty( $adds ) ) @$this->wpdb->query( "ALTER TABLE {$this->table_payments} " . implode( ', ', $adds ) );
        }

        $pay_insert = array(
            'subscription_id' => $sub_id,
            'user_id'         => $user_id,
            'amount'          => $amount,
            'currency'        => $currency,
            'method'          => $method->name,
            'status'          => 'AWAITING_PROOF',
            'date'            => current_time( 'mysql' ),
        );

        if ( $amount_usd !== null ) $pay_insert['amount_usd'] = (float) $amount_usd;
        if ( $amount_ngn !== null ) $pay_insert['amount_ngn'] = (float) $amount_ngn;
        if ( $txn_id !== null ) $pay_insert['transaction_id'] = $txn_id;
        if ( $fx_rate !== null ) $pay_insert['fx_rate'] = (float) $fx_rate;
        if ( ! empty( $coupon_code ) ) $pay_insert['coupon_code'] = sanitize_text_field( (string) $coupon_code );
        if ( $discount_usd !== null ) $pay_insert['discount_usd'] = (float) $discount_usd;
        if ( $discount_local !== null ) $pay_insert['discount_local'] = (float) $discount_local;
        if ( $gross_usd !== null ) $pay_insert['gross_usd'] = (float) $gross_usd;
        if ( $gross_local !== null ) $pay_insert['gross_local'] = (float) $gross_local;

        $this->wpdb->insert( $this->table_payments, $pay_insert );
        $pay_id = $this->wpdb->insert_id;

        // 4. Update Coupon Usage
        if ( ! empty( $coupon_code ) ) {
            $table_coupons = $this->wpdb->prefix . 'tv_coupons';
            $this->wpdb->query( $this->wpdb->prepare( "UPDATE $table_coupons SET usage_count = usage_count + 1 WHERE code = %s", $coupon_code ) );
        }

        return array( 'sub_id' => $sub_id, 'pay_id' => $pay_id );
    }

    /**
     * Handle File Upload & Transition to Review
     * UNABRIDGED Expansion: Added Trigger for Proof Receipt Email.
     */
    private function handle_proof_upload( $pay_id, $user_id ) {
        $payment = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$this->table_payments} WHERE id = %d AND user_id = %d", $pay_id, $user_id ) );
        
        if ( empty($payment) === true ) {
            return;
        }

        $allowed = array( 'AWAITING_PROOF', 'PENDING_ADMIN_REVIEW', 'pending', 'IN_PROGRESS' );
        
        if ( in_array( $payment->status, $allowed, true ) === false ) {
            return;
        }

        if ( isset( $_POST['tv_proof_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['tv_proof_nonce'] ), 'tv_upload_proof_' . $pay_id ) ) {
            return;
        }
        
        if ( empty( $_FILES['payment_proof'] ) === true ) {
            return;
        }

        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        
        $uploaded_urls = array();
        $files         = $_FILES['payment_proof'];

        if ( is_array( $files['name'] ) === true ) {
            $count = count( $files['name'] );
            for ( $i = 0; $i < $count; $i++ ) {
                if ( (int)$files['error'][ $i ] === 0 ) {
                    $file = array(
                        'name'     => $files['name'][ $i ],
                        'type'     => $files['type'][ $i ],
                        'tmp_name' => $files['tmp_name'][ $i ],
                        'error'    => $files['error'][ $i ],
                        'size'     => $files['size'][ $i ]
                    );
                    $valid = $this->tv_validate_proof_upload_file( $file );
                    if ( empty( $valid['ok'] ) === false ) {
                        $uploaded = wp_handle_upload( $valid['file'], array( 'test_form' => false, 'mimes' => $valid['mimes'] ) );
                        if ( isset( $uploaded['error'] ) === false && isset( $uploaded['url'] ) === true ) {
                            $uploaded_urls[] = esc_url_raw( $uploaded['url'] );
                            $this->tv_create_proof_attachment_from_upload( $uploaded, (int) $pay_id, (int) $user_id );
                        }
                    }
                }
            }
        } else {
            $valid = $this->tv_validate_proof_upload_file( $files );
            if ( empty( $valid['ok'] ) === false ) {
                $uploaded = wp_handle_upload( $valid['file'], array( 'test_form' => false, 'mimes' => $valid['mimes'] ) );
                if ( isset( $uploaded['error'] ) === false && isset( $uploaded['url'] ) === true ) {
                    $uploaded_urls[] = esc_url_raw( $uploaded['url'] );
                    $this->tv_create_proof_attachment_from_upload( $uploaded, (int) $pay_id, (int) $user_id );
                }
            }
        }

        if ( empty( $uploaded_urls ) === true ) {
            wp_redirect( home_url( '/dashboard?upload=failed' ) );
            exit;
        }

        $final_proof_val = ( count( $uploaded_urls ) > 1 ) ? json_encode( $uploaded_urls ) : $uploaded_urls[0];

        $this->wpdb->update( $this->table_payments, array(
            'proof_url' => $final_proof_val,
            'status'    => 'PENDING_ADMIN_REVIEW',
        ), array( 'id' => $pay_id ) );

        $sub = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_subs} WHERE id = %d", (int)$payment->subscription_id));
        
        if ( empty($sub) === false ) {
            $is_active_extension = ($sub->status === 'active' && strtotime($sub->end_date) > time());
            if ($is_active_extension === false) {
                $this->wpdb->update( $this->table_subs, array( 'status' => 'pending' ), array( 'id' => (int) $payment->subscription_id ) );
            }
            
            // [TRIGGER] Dispatch Acknowledgment Email
            if (class_exists('TV_Notification_Engine') === true) {
                TV_Notification_Engine::send_notification($sub, 'payment_proof_uploaded', '', true);
            }
        }

        delete_user_meta( $user_id, '_tv_pending_checkout' );
        delete_user_meta( $user_id, '_tv_active_pay_id' );

        wp_redirect( home_url( '/dashboard?upload=success' ) );
        exit;
    }
}