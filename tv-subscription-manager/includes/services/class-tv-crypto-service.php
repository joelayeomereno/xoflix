<?php
if (!defined('ABSPATH')) { exit; }

class TV_Crypto_Service {

    public function create_invoice($provider, $keys, $amount, $currency, $order_id, $user_email) {
        $provider = strtolower($provider);
        switch ($provider) {
            case 'plisio':
                return $this->driver_plisio($keys, $amount, $currency, $order_id, $user_email);
            case 'nowpayments':
                return $this->driver_nowpayments($keys, $amount, $currency, $order_id, $user_email);
            case 'coinbase':
                return $this->driver_coinbase($keys, $amount, $currency, $order_id, $user_email);
            case 'abc_crypto':
                return $this->driver_abc_crypto($keys, $amount, $currency, $order_id, $user_email);
            default:
                return ['success' => false, 'error' => 'Unknown crypto provider selected.'];
        }
    }

    private function driver_abc_crypto($keys, $amount, $currency, $order_id, $user_email) {
        $api_key = $keys['api_key_public']; 
        $shop_id = $keys['api_extra_param'];
        
        if (empty($api_key)) return ['success' => false, 'error' => 'ABC Crypto API Key missing'];

        $url = 'https://payerurl.com/api/v1/create_invoice'; 
        
        $payload = [
            'api_key' => $api_key,
            'shop_id' => $shop_id,
            'amount' => $amount,
            'currency' => strtoupper($currency),
            'order_id' => $order_id,
            'email' => $user_email,
            'description' => 'Subscription #' . $order_id,
            'success_url' => home_url('/dashboard?payment_success=1'),
            'cancel_url' => home_url('/dashboard?payment_cancel=1'),
            'callback_url' => home_url('/?tv_flow=payment_return&tx_ref=' . $order_id)
        ];

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($payload),
            'timeout' => 45
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => 'Connection Error: ' . $response->get_error_message()];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ABC Crypto Response: ' . print_r($body, true));
        }

        if (!empty($data['url'])) return ['success' => true, 'link' => $data['url']];
        if (!empty($data['invoice_url'])) return ['success' => true, 'link' => $data['invoice_url']];
        if (!empty($data['data']['url'])) return ['success' => true, 'link' => $data['data']['url']];

        $err_msg = 'Unknown API response';
        if (isset($data['message'])) $err_msg = $data['message'];
        elseif (isset($data['error'])) $err_msg = is_string($data['error']) ? $data['error'] : json_encode($data['error']);

        return ['success' => false, 'error' => 'ABC Crypto Error: ' . $err_msg];
    }

    private function driver_plisio($keys, $amount, $currency, $order_id, $user_email) {
        $secret_key = $keys['api_key_secret'];
        if (empty($secret_key)) return ['success' => false, 'error' => 'Plisio Secret Key missing'];

        $url = 'https://plisio.net/api/v1/invoices/new';
        $params = [
            'source_currency' => $currency,
            'source_amount' => $amount,
            'order_name' => 'Order #' . $order_id,
            'order_number' => $order_id,
            'api_key' => $secret_key,
            'callback_url' => home_url('/?tv_flow=payment_return&tx_ref=' . $order_id),
            'email' => $user_email,
            'json' => 'true'
        ];

        $response = wp_remote_get(add_query_arg($params, $url), ['timeout' => 45]);
        if (is_wp_error($response)) return ['success' => false, 'error' => $response->get_error_message()];

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($data['status']) && $data['status'] === 'success' && isset($data['data']['invoice_url'])) {
            return ['success' => true, 'link' => $data['data']['invoice_url']];
        }
        return ['success' => false, 'error' => $data['data']['message'] ?? 'Plisio API Error'];
    }

    private function driver_nowpayments($keys, $amount, $currency, $order_id, $user_email) {
        $api_key = $keys['api_key_public'];
        if (empty($api_key)) return ['success' => false, 'error' => 'NOWPayments API Key missing'];

        $url = 'https://api.nowpayments.io/v1/invoice';
        $payload = [
            'price_amount' => $amount,
            'price_currency' => strtolower($currency),
            'order_id' => (string)$order_id,
            'order_description' => 'Subscription #' . $order_id,
            'ipn_callback_url' => home_url('/?tv_flow=payment_return'),
            'success_url' => home_url('/dashboard?payment_success=1'),
            'cancel_url' => home_url('/dashboard?payment_cancel=1')
        ];

        $response = wp_remote_post($url, [
            'headers' => ['x-api-key' => $api_key, 'Content-Type' => 'application/json'],
            'body' => json_encode($payload),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) return ['success' => false, 'error' => $response->get_error_message()];
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($data['invoice_url'])) return ['success' => true, 'link' => $data['invoice_url']];
        return ['success' => false, 'error' => $data['message'] ?? 'NOWPayments API Error'];
    }

    private function driver_coinbase($keys, $amount, $currency, $order_id, $user_email) {
        $api_key = $keys['api_key_public']; 
        if (empty($api_key)) return ['success' => false, 'error' => 'Coinbase API Key missing'];

        $url = 'https://api.commerce.coinbase.com/charges';
        $payload = [
            'name' => 'Subscription',
            'description' => 'Order #' . $order_id,
            'pricing_type' => 'fixed_price',
            'local_price' => ['amount' => $amount, 'currency' => $currency],
            'metadata' => ['customer_id' => $user_email, 'order_id' => $order_id],
            'redirect_url' => home_url('/dashboard?payment_success=1'),
            'cancel_url' => home_url('/dashboard?payment_cancel=1')
        ];

        $response = wp_remote_post($url, [
            'headers' => ['X-CC-Api-Key' => $api_key, 'X-CC-Version' => '2018-03-22', 'Content-Type' => 'application/json'],
            'body' => json_encode($payload),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) return ['success' => false, 'error' => $response->get_error_message()];
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($data['data']['hosted_url'])) return ['success' => true, 'link' => $data['data']['hosted_url']];
        return ['success' => false, 'error' => 'Coinbase API Error'];
    }
}