<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Subtrait: TV_Subscription_Manager_Public_Traits_Payment_Page_Subtrait_Part_01
 * Path: /tv-subscription-manager/public/traits/payment-page/trait-tv-subscription-manager-public-traits-payment-page-subtrait-part-01.php
 */
trait TV_Subscription_Manager_Public_Traits_Payment_Page_Subtrait_Part_01 {

    public function shortcode_payment_lockdown($atts) {
        if (!is_user_logged_in()) return 'Please log in.';
        $user_id = get_current_user_id();
        $pay_id = (int)get_user_meta($user_id, '_tv_active_pay_id', true);

        // Fallback: If no meta, check GET param
        if (!$pay_id && isset($_GET['pay_id'])) {
            $pay_id = intval($_GET['pay_id']);
        }

        if (!$pay_id) {
            return '<script>window.location.href="' . home_url('/dashboard') . '";</script>';
        }

        global $wpdb;
        $payment = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tv_payments WHERE id = %d AND user_id = %d", $pay_id, $user_id));
        
        // Allowed statuses for lockdown
        $locking_statuses = ['IN_PROGRESS', 'AWAITING_PROOF', 'pending', 'PENDING_ADMIN_REVIEW'];
        
        if (!$payment || !in_array($payment->status, $locking_statuses)) {
             delete_user_meta($user_id, '_tv_active_pay_id');
             return '<script>window.location.href="' . home_url('/dashboard') . '";</script>';
        }

        $method = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tv_payment_methods WHERE name = %s", $payment->method));
        $gateway_link = ($method && !empty($method->link)) ? $method->link : '';
        
        // Flutterwave specific handling
        if ($method && !empty($method->flutterwave_enabled)) {
             $gateway_link = '#fw-trigger'; 
        }

        // [UPGRADE] Manual Bank Details Logic
        $bank_details = null;
        if ($method && empty($gateway_link)) {
            // Check if any bank fields are populated
            if (!empty($method->bank_name) || !empty($method->account_number) || !empty($method->instructions)) {
                $bank_details = [
                    'bank_name' => $method->bank_name,
                    'account_name' => $method->account_name,
                    'account_number' => $method->account_number,
                    'instructions' => $method->instructions
                ];
            }
        }
        $has_manual_details = !empty($bank_details);

        // [FOX] Format Amount
        $currency_code = isset($payment->currency) ? $payment->currency : 'USD';
        $display_amount = $payment->amount; 
        
        if (method_exists($this, 'get_currency_data')) {
            $cdata = $this->get_currency_data(0); 
            if (is_array($cdata) && isset($cdata['code']) && $cdata['code'] === $currency_code) {
                // [FIX] Integer Format (1,000)
                $display_amount = $cdata['symbol'] . number_format($payment->amount, 0);
            } else {
                $display_amount = $payment->amount . ' ' . $currency_code;
            }
        } else {
             $display_amount = $payment->amount . ' ' . $currency_code;
        }

        $upload_url = add_query_arg(['pay_id' => $pay_id], add_query_arg('tv_flow', 'upload_proof', home_url('/')));
        $fw_nonce = wp_create_nonce('tv_flutterwave_init_' . $pay_id);

        ob_start();
        // [FIX] Use absolute path constant to prevent relative path errors
        if (defined('TV_MANAGER_PATH')) {
            require TV_MANAGER_PATH . 'public/views/shortcode/payment-lockdown.php';
        } else {
            // Fallback if constant missing (should not happen if plugin loaded correctly)
            require __DIR__ . '/../../views/shortcode/payment-lockdown.php';
        }
        return ob_get_clean();
    }

    // ORIGINAL CHECKOUT PAGE REPLACED WITH AUTO-COMMIT
    // This logic now immediately creates the order and redirects to the lockdown page
    public function shortcode_payment_page($atts) {
        if (!headers_sent()) nocache_headers();
        if(!is_user_logged_in()) return '<div class="tv-error">Please log in.</div>';

        global $wpdb;
        $user_id = get_current_user_id();

        // 1. CHECK LOCKDOWN
        $active_pay_id = (int)get_user_meta($user_id, '_tv_active_pay_id', true);
        if ($active_pay_id) {
            $status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}tv_payments WHERE id=%d", $active_pay_id));
            if ($status && in_array($status, ['IN_PROGRESS', 'AWAITING_PROOF', 'pending', 'PENDING_ADMIN_REVIEW'])) {
                // If locked, redirect to new lockdown page immediately
                return '<script>window.location.href="' . add_query_arg(['tv_flow'=>'payment_pending'], home_url('/')) . '";</script>';
            }
        }

        // 2. NEW SESSION SETUP
        $pending = get_user_meta($user_id, '_tv_pending_checkout', true);
        
        // Handle POST entry from Method Selection
        if ((empty($pending) || !is_array($pending)) && isset($_POST['plan_id'])) {
            $this->handle_subscription_request();
            $pending = get_user_meta($user_id, '_tv_pending_checkout', true);
        }

        if (empty($pending) || !is_array($pending)) {
            return '<script>window.location.href="' . add_query_arg('tv_flow', 'select_method', home_url('/')) . '";</script>';
        }

        // 3. RETRIEVE DATA
        $plan_id = intval($pending['plan_id']);
        $method_id = intval($pending['payment_method_id']);
        $connections = isset($pending['connections']) ? intval($pending['connections']) : 1;
        $custom_months = isset($pending['custom_months']) ? intval($pending['custom_months']) : 0;

        $plan = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tv_plans WHERE id = %d", $plan_id));
        $method = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tv_payment_methods WHERE id = %d", $method_id));
        
        if (!$plan || !$method) {
            delete_user_meta($user_id, '_tv_pending_checkout');
            return '<script>window.location.href="' . home_url('/dashboard') . '";</script>';
        }

        // 4. CALCULATE FINALS
        $base_price_usd = floatval($plan->price);
        $months = ($custom_months > 0) ? $custom_months : 1;
        $connections = max(1, (int) $connections);
        
        // Local Currency logic
        $local_unit_price = $base_price_usd;
        $final_currency = 'USD';
        $currency_data = []; // Initialize to prevent notice
        
        if (method_exists($this, 'get_currency_data')) {
            $currency_data = $this->get_currency_data($base_price_usd);
            if (is_array($currency_data)) {
                $local_unit_price = $currency_data['amount_local']; 
                $final_currency = $currency_data['code'];
            }
        }

        $final_amount = $local_unit_price * $months * $connections;
        
        // Coupon Logic
        $coupon_code = isset($pending['coupon_code']) ? sanitize_text_field($pending['coupon_code']) : '';
        if ($coupon_code) {
            $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tv_coupons WHERE code = %s", $coupon_code));
            if ($coupon) {
                if ($coupon->type === 'percent') {
                    $discount_val = ($final_amount * ($coupon->amount / 100));
                    $final_amount = $final_amount - $discount_val;
                } 
                elseif ($coupon->type === 'fixed') {
                    $coupon_val_usd = floatval($coupon->amount);
                    $discount_val = $coupon_val_usd;
                    
                    if (method_exists($this, 'get_currency_data')) {
                        $coupon_data = $this->get_currency_data($coupon_val_usd);
                        $discount_val = $coupon_data['amount_local'];
                    }
                    
                    if ($discount_val > $final_amount) $discount_val = $final_amount;
                    $final_amount = $final_amount - $discount_val;
                }
            }
        }

        // 5. IMMEDIATE BYPASS EXECUTION
        // Instead of showing the UI, we create the order right now.
        // Plan Cycle Duration Rule: total days = plan cycle (duration_days) × selected months.
        // No hardcoded month lengths: if plan missing, use a safe minimal default (1 day).
        $cycle_days = $plan ? max(1, (int)$plan->duration_days) : 1;
        $total_days = max(1, (int)$months) * $cycle_days;
        // Discount-aware breakdown for authoritative USD + local recording
        $fx_rate = 1;
        $gross_usd = (float) $amount_usd; // Was defined as null, needs definition
        
        // RE-CALC USD for storage
        $gross_local = (float) ($currency_data['amount_local'] ?? $final_amount);
        $fx_rate = (float) ($currency_data['rate'] ?? 1);
        $amount_usd = ($fx_rate > 0) ? ($final_amount / $fx_rate) : $final_amount;
        $gross_usd = ($fx_rate > 0) ? ($gross_local / $fx_rate) : $gross_local;
        
        $net_local = (float) $final_amount;
        $net_usd = $amount_usd;
        
        $discount_local = max(0, $gross_local - $net_local);
        $discount_usd = max(0, $gross_usd - $net_usd);

        // Persist purchased months alongside subscription when supported.
        $created = $this->create_subscription_and_payment($user_id, $plan_id, $method, $final_amount, $total_days, $connections, $coupon_code, $final_currency, $months, $net_usd, $fx_rate, $discount_usd, $discount_local, $gross_usd, $gross_local);
        
        if ($created && isset($created['pay_id'])) {
            // Lock session
            update_user_meta($user_id, '_tv_active_pay_id', $created['pay_id']);
            
            // Set status to IN_PROGRESS so it is picked up by lockdown immediately
            $wpdb->update("{$wpdb->prefix}tv_payments", ['attempted_at' => current_time('mysql'), 'status' => 'IN_PROGRESS'], ['id' => $created['pay_id']]);

            // Redirect to lockdown immediately via Server Header if possible, else JS
            $lockdown_url = add_query_arg('tv_flow', 'payment_pending', home_url('/'));
            
            wp_redirect($lockdown_url);
            exit;
        } else {
             return '<div class="tv-error">Error creating payment session. Please try again.</div>';
        }
    }
}