<?php
if (!defined('ABSPATH')) { exit; }

/**
 * ABC Crypto (PayerURL) Provider Adapter
 * Updated: Strict User Setting Adherence & Anti-Bot Headers
 */
class TV_Crypto_Provider_ABC {

    private $public_key;
    private $secret_key;
    // Fallback default only if settings are empty
    private $default_endpoint = 'https://payerurl.com/api/payment'; 

    public function __construct($settings) {
        $this->public_key = isset($settings['crypto_public_key']) ? trim($settings['crypto_public_key']) : '';
        $this->secret_key = isset($settings['crypto_secret_key']) ? trim($settings['crypto_secret_key']) : '';
        
        // STRICT: Trust user input 100%. No "auto-fixing" or ignoring specific URLs.
        if (!empty($settings['crypto_endpoint'])) {
            $this->default_endpoint = trim($settings['crypto_endpoint']);
        }
    }

    /**
     * Create Invoice
     */
    public function create_invoice($order) {
        if (empty($this->public_key) || empty($this->secret_key)) {
            return new WP_Error('config_error', 'Missing Crypto API Keys.');
        }

        $endpoint = apply_filters('tv_crypto_abc_endpoint', $this->default_endpoint);

        if (empty($endpoint) || !filter_var($endpoint, FILTER_VALIDATE_URL)) {
            return new WP_Error('config_error', 'Invalid API Endpoint URL: ' . $endpoint);
        }

        // Standard JSON Payload
        $body = [
            'public_key'   => $this->public_key,
            'amount'       => $order['amount'],
            'currency'     => $order['currency'],
            'order_id'     => $order['transaction_id'],
            'trackId'      => $order['transaction_id'], 
            'email'        => $order['email'],
            'redirect_url' => $order['redirect_url'], 
            'success_url'  => $order['redirect_url'],
            'cancel_url'   => $order['redirect_url'],
            'callback_url' => $order['callback_url'],
            'webhook_url'  => $order['callback_url']
        ];

        // DEBUG: Log the attempt
        $this->log('Crypto Init', "Sending JSON to: $endpoint | Order: {$order['transaction_id']}");

        $response = wp_remote_post($endpoint, [
            'body'    => json_encode($body), 
            'headers' => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $this->secret_key,
                // [FIX] Add User-Agent to prevent 403/404 from firewalls
                'User-Agent'    => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ],
            'timeout' => 45,
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            $this->log('Crypto Error', 'WP HTTP Error: ' . $response->get_error_message());
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body_res = wp_remote_retrieve_body($response);
        
        // DEBUG: Log the RAW response
        $this->log('Crypto Response', "Code: $code | Body Sample: " . substr(strip_tags($body_res), 0, 300));

        $data = json_decode($body_res, true);

        // Success Check
        $checkout_url = null;
        if (!empty($data['payment_url'])) $checkout_url = $data['payment_url'];
        elseif (!empty($data['url'])) $checkout_url = $data['url'];
        elseif (!empty($data['checkoutUrl'])) $checkout_url = $data['checkoutUrl'];
        elseif (!empty($data['data']['url'])) $checkout_url = $data['data']['url'];
        elseif (!empty($data['invoice_url'])) $checkout_url = $data['invoice_url'];

        if (($code >= 200 && $code < 300) && $checkout_url) {
            return [
                'success' => true,
                'checkout_url' => $checkout_url,
                'provider_ref' => $data['payment_id'] ?? $order['transaction_id']
            ];
        }

        // IMPROVED ERROR PARSING
        if ($code === 404) {
             $err_details = "API 404 Not Found at $endpoint. Please verify the URL in TV Manager > Methods.";
        } elseif (json_last_error() !== JSON_ERROR_NONE) {
            $raw_text = strip_tags(substr($body_res, 0, 150));
            $err_details = "Provider Error ($code). Server returned non-JSON: $raw_text...";
        } else {
            $api_msg = isset($data['message']) ? $data['message'] : (isset($data['error']) ? $data['error'] : 'Unknown API Error');
            $err_details = "Provider Error ($code). Message: $api_msg";
        }
        
        return new WP_Error('provider_error', $err_details);
    }

    private function log($action, $details) {
        if (class_exists('TV_Domain_Audit_Service')) {
            $audit = new TV_Domain_Audit_Service();
            $audit->log_event(get_current_user_id(), $action, $details);
        }
    }
}