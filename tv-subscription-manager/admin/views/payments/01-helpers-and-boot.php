<?php
if (!defined('ABSPATH')) { exit; }

// Helper for Currency Symbols
if (!function_exists('tv_get_currency_symbol')) {
    function tv_get_currency_symbol($code) { 
        $code = strtoupper(trim((string)$code));
        if (class_exists('TV_Currency')) {
            return TV_Currency::symbol($code);
        }
        // Fallback (very old installs)
        $symbols = ['USD'=>'$','EUR'=>'&euro;','GBP'=>'&pound;','NGN'=>'&#8358;','GHS'=>'&#8373;','KES'=>'KSh','ZAR'=>'R','INR'=>'&#8377;'];
        return isset($symbols[$code]) ? $symbols[$code] : $code . ' '; 
    }
}

// Helper for Duration Calculation
if (!function_exists('tv_get_duration_months')) {
    function tv_get_duration_months($start, $end) {
        if (!$start || !$end || $start == '0000-00-00 00:00:00') return 1;
        $diff = strtotime($end) - strtotime($start);
        $days = floor($diff / (60 * 60 * 24));
        return max(1, round($days / 30));
    }
}

// Retrieve Saved Panels for Fulfillment
$panels = get_option('tv_panel_configs', []);
?>