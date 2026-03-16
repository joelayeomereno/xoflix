<?php
if (!defined('ABSPATH')) { exit; }

/**
 * File: tv-subscription-manager/includes/helpers/class-tv-currency.php
 * Path: /tv-subscription-manager/includes/helpers/class-tv-currency.php
 *
 * FIX: symbol_map() now returns raw Unicode characters instead of HTML entities.
 * HTML entities (&#8358;, &euro;, &pound;) are valid in PHP?HTML output (TV Manager
 * desktop), but when this class is called from the mobile admin REST API the values
 * are JSON-serialised — JSON.parse() never runs an HTML parser, so entities appear
 * as literal text like "&#8358;8084.00" on screen. Raw Unicode serialises cleanly
 * to JSON AND renders identically to the entity in HTML contexts.
 */
class TV_Currency {

    /**
     * Return a currency symbol for an ISO currency code.
     *
     * Notes:
     * - Server may not have ext/intl, so we use a curated ISO map.
     * - Map is filterable via `tv_currency_symbol_map`.
     * - Fallback returns the uppercased code with a trailing space.
     */
    public static function symbol(string $code) : string {
        $code = strtoupper(trim($code));
        if ($code === '') return '';

        $map = self::symbol_map();
        if (isset($map[$code]) && $map[$code] !== '') {
            return $map[$code];
        }

        return $code . ' ';
    }

    /**
     * @return array<string,string>
     */
    public static function symbol_map() : array {
        $symbols = [
            // Major
            'USD' => '$',
            'EUR' => "\u{20AC}",   // € — was &euro;  (HTML entity, breaks JSON API)
            'GBP' => "\u{00A3}",   // £ — was &pound;
            'JPY' => "\u{00A5}",   // ¥ — was &yen;
            'CNY' => "\u{00A5}",   // ¥
            'HKD' => 'HK$',
            'SGD' => 'S$',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'NZD' => 'NZ$',
            'CHF' => 'CHF ',

            // Africa
            'NGN' => "\u{20A6}",   // ? — was &#8358; (THE primary bug)
            'GHS' => "\u{20B5}",   // ? — was &#8373;
            'ZAR' => 'R',
            'KES' => 'KSh',
            'UGX' => 'USh',
            'TZS' => 'TSh',
            'RWF' => 'RF',
            'ETB' => 'Br',
            'MAD' => 'DH',
            'EGP' => 'E£',
            'XOF' => 'CFA ',
            'XAF' => 'CFA ',

            // Europe
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
            'PLN' => 'zl',
            'CZK' => 'Kc',
            'HUF' => 'Ft',
            'RON' => 'lei',
            'BGN' => '??',
            'TRY' => "\u{20BA}",   // ? — was &#8378;
            'UAH' => '?',
            'RUB' => '?',

            // Middle East
            'AED' => '?.?',
            'SAR' => '?.?',
            'QAR' => '?.?',
            'KWD' => '?.?',
            'BHD' => '?.?',
            'OMR' => '?.?.',
            'ILS' => "\u{20AA}",   // ? — was &#8362;

            // Americas
            'MXN' => 'Mex$',
            'BRL' => 'R$',
            'ARS' => 'AR$',
            'CLP' => 'CLP$',
            'COP' => 'COL$',
            'PEN' => 'S/.',

            // Asia
            'INR' => "\u{20B9}",   // ? — was &#8377;
            'PKR' => '?',
            'BDT' => '?',
            'LKR' => 'Rs',
            'IDR' => 'Rp',
            'MYR' => 'RM',
            'THB' => "\u{0E3F}",   // ? — was &#3647;
            'PHP' => "\u{20B1}",   // ? — was &#8369;
            'VND' => "\u{20AB}",   // ? — was &#8363;
            'KRW' => "\u{20A9}",   // ? — was &#8361;

            // Crypto/common pseudo
            'USDT' => 'USDT ',
        ];

        /**
         * Filter: allow extensions (e.g., FOX Currency Converter full map).
         *
         * @param array<string,string> $symbols
         */
        $symbols = apply_filters('tv_currency_symbol_map', $symbols);

        return $symbols;
    }
}

// Module alias to match full location path (non-breaking).
if (!class_exists('Tv_Subscription_Manager_Includes_Helpers_Class_Tv_Currency', false)) {
    class_alias('TV_Currency', 'Tv_Subscription_Manager_Includes_Helpers_Class_Tv_Currency');
}