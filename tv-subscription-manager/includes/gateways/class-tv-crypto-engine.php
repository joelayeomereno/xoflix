<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Universal Crypto Engine
 * Abstraction layer for crypto payment providers.
 */
class TV_Crypto_Engine {

    private static $instance = null;
    
    // Supported Providers Registry
    private $providers = [
        'abc_crypto' => [
            'class' => 'TV_Crypto_Provider_ABC',
            'file_slug' => 'abc'
        ]
    ];

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_provider($method_settings) {
        $provider_slug = isset($method_settings['crypto_provider']) ? $method_settings['crypto_provider'] : 'abc_crypto';
        
        if (!isset($this->providers[$provider_slug])) {
            return new WP_Error('invalid_provider', 'Crypto provider not supported.');
        }

        $config = $this->providers[$provider_slug];
        $class_name = $config['class'];
        $file_slug = $config['file_slug'];
        
        $file_path = TV_MANAGER_PATH . 'includes/gateways/providers/class-tv-crypto-provider-' . $file_slug . '.php';
        
        if (!class_exists($class_name)) {
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                return new WP_Error('file_missing', 'Provider file not found: ' . $file_path);
            }
        }

        if (class_exists($class_name)) {
            return new $class_name($method_settings);
        }

        return new WP_Error('class_missing', 'Provider class not found.');
    }

    public function init_transaction($payment_id, $method_settings) {
        $provider = $this->get_provider($method_settings);
        if (is_wp_error($provider)) return $provider;

        global $wpdb;
        $payment = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tv_payments WHERE id = %d", $payment_id));
        
        if (!$payment) return new WP_Error('invalid_payment', 'Payment record not found.');

        // HARDENED URL GENERATION (Ensure Absolute)
        $base_url = get_site_url(); // More reliable for absolute base than home_url in some envs
        $redirect_url = add_query_arg(['tv_flow' => 'payment_return', 'pay_id' => $payment->id], $base_url . '/');
        $callback_url = admin_url('admin-ajax.php?action=tv_crypto_webhook&provider=' . $method_settings['crypto_provider']);

        // Prepare standardized order data
        $order_data = [
            'id' => $payment->id,
            'transaction_id' => $payment->transaction_id ?: 'TXN-' . $payment->id . '-' . time(),
            'amount' => $payment->amount,
            'currency' => $payment->currency ?: 'USD',
            'email' => $this->get_user_email($payment->user_id),
            'redirect_url' => $redirect_url,
            'callback_url' => $callback_url
        ];

        // Ensure transaction ID is saved if generated new
        if (empty($payment->transaction_id)) {
            $wpdb->update(
                "{$wpdb->prefix}tv_payments", 
                ['transaction_id' => $order_data['transaction_id']], 
                ['id' => $payment->id]
            );
        }

        return $provider->create_invoice($order_data);
    }

    private function get_user_email($user_id) {
        $u = get_userdata($user_id);
        return $u ? $u->user_email : '';
    }
}