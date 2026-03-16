<?php
if (!defined('ABSPATH')) { exit; }

// Import Traits
require_once __DIR__ . '/traits/trait-tv-public-flow.php';
require_once __DIR__ . '/traits/trait-tv-public-shortcodes-basic.php';
require_once __DIR__ . '/traits/trait-tv-public-shortcode-payment-page.php';
require_once __DIR__ . '/traits/trait-tv-public-payment-window.php';
require_once __DIR__ . '/traits/trait-tv-public-actions.php';
require_once __DIR__ . '/traits/trait-tv-public-ajax.php';
require_once __DIR__ . '/traits/trait-tv-public-notice.php';
require_once __DIR__ . '/traits/trait-tv-public-shortcode-payment-control.php';

class TV_Manager_Public {

    private $wpdb;
    private $table_plans;
    private $table_subs;
    private $table_payments;
    private $table_coupons;
    private $table_methods;

    // Constants for Meta Keys
    private const USER_META_PENDING_CHECKOUT = '_tv_pending_checkout';
    private const USER_META_ACTIVE_PAY_ID = '_tv_active_pay_id';

    // Constants for Payment Status
    private const PAYMENT_STATUS_IN_PROGRESS = 'IN_PROGRESS';
    private const PAYMENT_STATUS_AWAITING_PROOF = 'AWAITING_PROOF';
    private const PAYMENT_STATUS_PENDING_REVIEW = 'PENDING_ADMIN_REVIEW';
    private const PAYMENT_STATUS_APPROVED = 'APPROVED';
    private const PAYMENT_STATUS_CANCELLED = 'CANCELLED';
    private const PAYMENT_STATUS_REJECTED = 'REJECTED';
    private const PAYMENT_STATUS_LEGACY_PENDING = 'pending';

    // Load Traits (This imports the logic from other files)
    use TV_Manager_Public_Trait_Flow;
    use TV_Manager_Public_Trait_Shortcodes_Basic;
    use TV_Manager_Public_Trait_Shortcode_Payment_Page;
    use TV_Manager_Public_Trait_Payment_Window;
    use TV_Manager_Public_Trait_Actions;
    use TV_Manager_Public_Trait_Ajax;
    use TV_Manager_Public_Trait_Notice;
    use TV_Manager_Public_Trait_Shortcode_Payment_Control;

    public function __construct($wpdb) {
        $this->wpdb = $wpdb;
        $this->table_plans = $wpdb->prefix . 'tv_plans';
        $this->table_subs = $wpdb->prefix . 'tv_subscriptions';
        $this->table_payments = $wpdb->prefix . 'tv_payments';
        $this->table_coupons = $wpdb->prefix . 'tv_coupons';
        $this->table_methods = $wpdb->prefix . 'tv_payment_methods';

        // Shortcodes (Logic handled in Traits)
        add_shortcode('tv_plans', array($this, 'shortcode_plans'));
        add_shortcode('tv_dashboard', array($this, 'shortcode_dashboard'));
        add_shortcode('tv_select_payment_method', array($this, 'shortcode_select_payment_method'));
        add_shortcode('tv_payment_page', array($this, 'shortcode_payment_page'));
        add_shortcode('tv_upload_payment_proof', array($this, 'shortcode_upload_payment_proof'));
        add_shortcode('tv_payment_control', array($this, 'shortcode_payment_control'));
        add_shortcode('tv_payment_lockdown', array($this, 'shortcode_payment_lockdown'));
        add_shortcode('tv_subscription_details', array($this, 'shortcode_subscription_details'));
        add_shortcode('tv_subscription_plans', array($this, 'shortcode_subscription_plans'));

        // Actions & Hooks
        add_action('init', array($this, 'handle_multi_step_actions'));
        add_action('wp_loaded', array($this, 'handle_subscription_request'));
        
        // AJAX Endpoints
        add_action('wp_ajax_tv_save_checkout_session', array($this, 'ajax_save_checkout_session'));
        add_action('wp_ajax_tv_mark_payment_attempt', array($this, 'ajax_mark_payment_attempt'));
        add_action('wp_ajax_tv_validate_coupon', array($this, 'ajax_validate_coupon'));
        add_action('wp_ajax_tv_flutterwave_init_checkout', array($this, 'ajax_flutterwave_init_checkout'));
        add_action('wp_ajax_tv_cancel_payment', array($this, 'ajax_cancel_payment'));
        add_action('wp_ajax_tv_check_transaction_status', array($this, 'ajax_check_transaction_status'));
        add_action('wp_ajax_tv_secure_proof_download', array($this, 'ajax_secure_proof_download'));

        // No-Priv AJAX
        add_action('wp_ajax_nopriv_tv_save_checkout_session', array($this, 'ajax_save_checkout_session'));
        add_action('wp_ajax_nopriv_tv_validate_coupon', array($this, 'ajax_validate_coupon'));

        // Routing & Logic
        add_action('template_redirect', array($this, 'maybe_render_flow_endpoint'), 0);
        add_action('template_redirect', array($this, 'handle_cancellation_request'), 1);
        add_action('template_redirect', array($this, 'enforce_payment_lockdown'), 5); 
        
        // Footer Notices
        add_action('wp_footer', array($this, 'render_payment_processing_notice'), 100);
    }

    /**
     * Shortcode: [tv_subscription_plans]
     * Displays the premium plans grid.
     */
    public function shortcode_subscription_plans($atts) {
        ob_start();
        $plans = [];
        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_plans}'") == $this->table_plans) {
            // UPDATED: Sort by display_order ASC first
            $plans = $this->wpdb->get_results("SELECT * FROM {$this->table_plans} ORDER BY display_order ASC, price ASC");
        }
        $allowed_currencies = get_option('tv_allowed_currencies', []);
        $config = [
            'trial_delay' => intval(get_option('tv_trial_overlay_delay', 5)),
            'wa_msg' => get_option('tv_whatsapp_custom_msg', ''),
            'wa_number' => get_option('tv_support_whatsapp', '')
        ];
        include TV_MANAGER_PATH . 'public/views/view-plans-premium.php';
        return ob_get_clean();
    }

    /**
     * Helper: Get User Country
     */
    public function get_user_location() {
        if (current_user_can('manage_options') && isset($_GET['test_country'])) {
            return strtoupper(sanitize_text_field($_GET['test_country']));
        }
        $headers = ['HTTP_CF_IPCOUNTRY','HTTP_X_COUNTRY_CODE','HTTP_GEOIP_COUNTRY_CODE','HTTP_X_REAL_IP'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header]) && strlen($_SERVER[$header]) === 2) {
                return strtoupper(sanitize_text_field($_SERVER[$header]));
            }
        }
        if (class_exists('WC_Geolocation')) {
            $location = \WC_Geolocation::geolocate_ip();
            if (isset($location['country']) && !empty($location['country'])) {
                return strtoupper($location['country']);
            }
        }
        return apply_filters('tv_default_country', 'US');
    }

    /**
     * Helper: Get All Potential Locations for User
     */
    public function get_all_user_locations($user_id) {
        $locations = [];
        $profile_country = get_user_meta($user_id, 'billing_country', true);
        if (!empty($profile_country)) $locations[] = strtoupper(trim($profile_country));
        $geo_country = $this->get_user_location();
        if (!empty($geo_country)) $locations[] = strtoupper(trim($geo_country));
        return array_unique(array_filter($locations));
    }

    /**
     * Helper: Calculate Currency Data (USD Base)
     */
    public function get_currency_data($amount_usd) {
        global $WOOCS;
        
        $user_id = get_current_user_id();
        $pref_currency = ($user_id > 0) ? get_user_meta($user_id, 'tv_user_currency', true) : '';
        
        $target_currency = 'USD';
        
        if (!empty($pref_currency)) {
            $target_currency = $pref_currency;
        } else {
            $country = $this->get_user_location();
            if (isset($WOOCS) && is_object($WOOCS) && method_exists($WOOCS, 'get_currency_by_country')) {
                $target_currency = $WOOCS->get_currency_by_country($country);
            }
            if (!$target_currency) {
                $target_currency = $this->get_currency_for_country($country);
            }
        }

        $data = [
            'code' => 'USD',
            'symbol' => '$',
            'rate' => 1,
            'amount_usd' => $amount_usd,
            'amount_local' => $amount_usd,
            'formatted' => '$' . number_format((float)$amount_usd, 0)
        ];

        if (isset($WOOCS) && is_object($WOOCS)) {
            $currencies = $WOOCS->get_currencies();
            if (isset($currencies[$target_currency])) {
                $rate = (float)$currencies[$target_currency]['rate'];
                $symbol = $currencies[$target_currency]['symbol'];
                $decimals = 0;
                $position = isset($currencies[$target_currency]['position']) ? $currencies[$target_currency]['position'] : 'left';
                
                $converted = (float)$amount_usd * $rate;
                $formatted_val = number_format($converted, $decimals);
                $formatted = ($position === 'right') ? $formatted_val . ' ' . $symbol : $symbol . $formatted_val;

                $data = [
                    'code' => $target_currency,
                    'symbol' => $symbol,
                    'rate' => $rate,
                    'amount_usd' => $amount_usd,
                    'amount_local' => round($converted, $decimals),
                    'formatted' => $formatted
                ];
            }
        }
        return $data;
    }

    /**
     * Helper to get the current NGN exchange rate for locking.
     */
    public function get_ngn_rate() {
        global $WOOCS;
        if (isset($WOOCS) && is_object($WOOCS)) {
            $currencies = $WOOCS->get_currencies();
            if (isset($currencies['NGN'])) {
                return (float)$currencies['NGN']['rate'];
            }
        }
        return 0;
    }

    private function get_currency_for_country($country_code) {
        $map = [
            'US'=>'USD','GB'=>'GBP','CA'=>'CAD','AU'=>'AUD','ZA'=>'ZAR','NG'=>'NGN','GH'=>'GHS','KE'=>'KES',
            'IN'=>'INR','BR'=>'BRL','JP'=>'JPY','CN'=>'CNY','AE'=>'AED','SA'=>'SAR','MX'=>'MXN','RU'=>'RUB',
            'DE'=>'EUR','FR'=>'EUR','IT'=>'EUR','ES'=>'EUR','NL'=>'EUR','BE'=>'EUR','AT'=>'EUR','PT'=>'EUR','FI'=>'EUR','IE'=>'EUR','GR'=>'EUR'
        ];
        return isset($map[$country_code]) ? $map[$country_code] : 'USD';
    }

    /**
     * Handle Cancellation Request (GET)
     */
    public function handle_cancellation_request() {
        if (isset($_GET['tv_cancel_lockdown']) && $_GET['tv_cancel_lockdown'] == '1') {
             $pay_id = isset($_GET['pay_id']) ? intval($_GET['pay_id']) : 0;
             $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';
             if ($pay_id && wp_verify_nonce($nonce, 'tv_cancel_payment_' . $pay_id)) {
                 if (!is_user_logged_in()) return;
                 $uid = get_current_user_id();
                 $this->wpdb->update($this->table_payments, 
                    array('status' => self::PAYMENT_STATUS_CANCELLED), 
                    array('id' => $pay_id, 'user_id' => $uid)
                 );
                 delete_user_meta($uid, self::USER_META_ACTIVE_PAY_ID);
                 delete_user_meta($uid, self::USER_META_PENDING_CHECKOUT);
                 wp_redirect(home_url('/dashboard'));
                 exit;
             }
        }
    }

    /**
     * Enforce Payment Lockdown (Redirect)
     */
    public function enforce_payment_lockdown() {
        if (is_admin() || !is_user_logged_in() || (defined('DOING_AJAX') && DOING_AJAX)) { 
            return; 
        }
        
        $user_id = get_current_user_id();
        $active_pay_id = (int)get_user_meta($user_id, self::USER_META_ACTIVE_PAY_ID, true);
        if (!$active_pay_id) { return; }
        
        $payment = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT status FROM {$this->table_payments} WHERE id = %d AND user_id = %d",
            $active_pay_id, $user_id
        ));

        $locking_statuses = [
            self::PAYMENT_STATUS_IN_PROGRESS,
            self::PAYMENT_STATUS_AWAITING_PROOF,
            self::PAYMENT_STATUS_LEGACY_PENDING
        ];

        if (!$payment || !in_array($payment->status, $locking_statuses, true)) {
            delete_user_meta($user_id, self::USER_META_ACTIVE_PAY_ID);
            return;
        }

        $flow = get_query_var('tv_flow');
        $allowed_flows = ['payment_pending', 'upload_proof', 'payment_control', 'payment_return'];
        
        if (in_array($flow, $allowed_flows)) return;

        $lockdown_url = add_query_arg(['tv_flow' => 'payment_pending', 'pay_id' => $active_pay_id], home_url('/'));
        
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        if (strpos($current_url, 'tv_flow=payment_pending') === false) {
            wp_safe_redirect($lockdown_url);
            exit;
        }
    }
}