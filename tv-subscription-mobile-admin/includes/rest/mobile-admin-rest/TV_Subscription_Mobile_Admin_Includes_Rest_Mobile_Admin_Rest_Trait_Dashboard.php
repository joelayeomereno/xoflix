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
            'KRW' => "\u{20A9}",   // ? <?php
if (!defined('ABSPATH')) { exit; }

trait TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Dashboard {

    public static function get_dashboard_ultra() : WP_REST_Response {
        $cache_key = 'tv_mobile_dash_stats';
        $cached = get_transient($cache_key);
        if ($cached !== false) return new WP_REST_Response($cached, 200);

        global $wpdb;
        $p = $wpdb->prefix;

        $rev     = (float)$wpdb->get_var("SELECT SUM(amount) FROM {$p}tv_payments WHERE status IN ('completed','APPROVED')");
        $active  = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$p}tv_subscriptions WHERE status = 'active'");
        $pending = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$p}tv_payments WHERE status IN ('pending','AWAITING_PROOF','IN_PROGRESS','PENDING_ADMIN_REVIEW')");

        // Build per-currency revenue breakdown.
        // currency_symbol() now returns plain Unicode — safe to embed in JSON.
        $rev_rows  = $wpdb->get_results("SELECT currency, SUM(amount) as total FROM {$p}tv_payments WHERE status IN ('completed','APPROVED') GROUP BY currency");
        $rev_value = '$' . number_format($rev, 0);
        if (!empty($rev_rows)) {
            $parts = [];
            foreach ($rev_rows as $r) {
                $cc      = !empty($r->currency) ? strtoupper((string)$r->currency) : 'USD';
                $parts[] = self::currency_symbol($cc) . number_format((float)$r->total, 0);
            }
            $rev_value = implode("\n", $parts);
        }

        $data = [
            'stats' => [
                'revenue'       => ['value' => $rev_value, 'trend' => 100],
                'active_subs'   => $active,
                'pending_tasks' => $pending,
                'users'         => (int)count_users()['total_users'],
            ],
            'recent_activity' => $wpdb->get_results("SELECT * FROM {$p}tv_activity_logs ORDER BY date DESC LIMIT 10"),
            'csv_url'         => admin_url('admin-post.php?action=tv_finance_export_csv'),
        ];

        set_transient($cache_key, $data, 60);
        return new WP_REST_Response($data, 200);
    }

    public static function global_search(WP_REST_Request $req) : WP_REST_Response {
        global $wpdb;
        $q = sanitize_text_field($req['q']);
        if (strlen($q) < 2) return new WP_REST_Response([], 200);

        $res = [];

        $users = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, user_login, user_email FROM {$wpdb->users} WHERE user_login LIKE %s OR user_email LIKE %s LIMIT 5",
            "%$q%", "%$q%"
        ));
        foreach ($users as $u) {
            $res[] = [
                'type'     => 'user',
                'id'       => (int)$u->ID,
                'title'    => $u->user_login,
                'subtitle' => $u->user_email,
            ];
        }

        // FIX: was hardcoded '$' for all currencies regardless of payment currency.
        $pays = $wpdb->get_results($wpdb->prepare(
            "SELECT id, amount, currency, status FROM {$wpdb->prefix}tv_payments WHERE id LIKE %s LIMIT 5",
            "%$q%"
        ));
        foreach ($pays as $pay) {
            $cc  = !empty($pay->currency) ? strtoupper((string)$pay->currency) : 'USD';
            $sym = self::currency_symbol($cc);
            $res[] = [
                'type'     => 'payment',
                'id'       => (int)$pay->id,
                'title'    => "Invoice #{$pay->id}",
                'subtitle' => $sym . number_format((float)$pay->amount, 2) . ' - ' . $pay->status,
            ];
        }

        return new WP_REST_Response($res, 200);
    }

    public static function get_finance() : WP_REST_Response { return self::get_dashboard_ultra(); }

    // -----------------------
    // Subscriptions
    // -----------------------

}