<?php
/**
 * Logic.php
 * Handles server-side data fetching and hydration for the React Dashboard.
 * * FIX APPLIED: ISO 8601 Date Formatting for React & Strict Subscription Filtering
 */

if (!defined('ABSPATH')) { exit; }

if (!headers_sent()) {
    nocache_headers();
}

global $wpdb, $current_user;

// Robust Class Retrieval
$tv_instance = isset($GLOBALS['tv_manager_public_instance']) 
    ? $GLOBALS['tv_manager_public_instance'] 
    : (class_exists('TV_Manager_Public') ? new TV_Manager_Public($wpdb) : null);

$table_subs     = $wpdb->prefix . 'tv_subscriptions';
$table_plans    = $wpdb->prefix . 'tv_plans';
$table_payments = $wpdb->prefix . 'tv_payments';
$table_methods  = $wpdb->prefix . 'tv_payment_methods';
$table_sports   = $wpdb->prefix . 'tv_sports_events';
$table_news     = $wpdb->prefix . 'tv_announcements';

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// 1. LOCATION & CURRENCY ENGINE
$detected_country = 'US';
if ($tv_instance && method_exists($tv_instance, 'get_user_location')) {
    $detected_country = $tv_instance->get_user_location();
}

// Auth Signature
$auth_signature = hash_hmac('sha256', 'streamos_auth_' . $user_id, wp_salt('auth'));

// Load User Currency Preference (Priority over Geo-IP)
$user_currency = get_user_meta($user_id, 'tv_user_currency', true);
if (empty($user_currency)) {
    $user_currency = 'USD'; // Default
}

// Override Detected Country based on Currency Preference for Method Filtering
if (!empty($user_currency)) {
    $cur_to_country = [
        'NGN' => 'NG', 'GHS' => 'GH', 'KES' => 'KE', 'ZAR' => 'ZA',
        'GBP' => 'GB', 'USD' => 'US', 'EUR' => 'EU', 'CAD' => 'CA',
        'AUD' => 'AU', 'INR' => 'IN', 'BRL' => 'BR', 'AED' => 'AE'
    ];
    if (isset($cur_to_country[$user_currency])) {
        $detected_country = $cur_to_country[$user_currency];
    }
}

$user_data = [
    'id' => $user_id,
    'name' => $current_user->display_name ?: $current_user->user_login,
    'email' => $current_user->user_email,
    'avatar' => get_avatar_url($user_id, ['size' => 128]),
    'country' => strtoupper(get_user_meta($user_id, 'billing_country', true) ?: $detected_country),
    'currency' => $user_currency,
    'ip_country' => $detected_country,
    'phone' => get_user_meta($user_id, 'billing_phone', true) ?: 'Not set',
    'joined' => date('F Y', strtotime($current_user->user_registered)),
    'auth_sig' => $auth_signature
];

// 2. FLOW URL MAPPING
$tv_flow_urls = [
    'select_method' => '', 
    'payment' => '', 
    'upload_proof' => '', 
    'plans' => ''
];

$tv_flow_map = [
    'select_method' => intval(get_option('tv_select_method_page_id', 0)),
    'payment'       => intval(get_option('tv_payment_page_id', 0)),
    'upload_proof'  => intval(get_option('tv_upload_proof_page_id', 0)),
    'plans'         => intval(get_option('tv_plans_page_id', 0)),
];

foreach ($tv_flow_map as $k => $pid) {
    if ($pid > 0 && ($p = get_post($pid)) && $p->post_status === 'publish') {
        $tv_flow_urls[$k] = get_permalink($pid);
    }
}

// Fallbacks
if (empty($tv_flow_urls['select_method'])) $tv_flow_urls['select_method'] = add_query_arg('tv_flow', 'select_method', home_url('/'));
if (empty($tv_flow_urls['payment']))       $tv_flow_urls['payment']       = add_query_arg('tv_flow', 'payment', home_url('/'));
if (empty($tv_flow_urls['upload_proof']))  $tv_flow_urls['upload_proof']  = add_query_arg('tv_flow', 'upload_proof', home_url('/'));
if (empty($tv_flow_urls['plans']))         $tv_flow_urls['plans']         = home_url('/plans');

$user_data['tv_flow_urls'] = $tv_flow_urls;

// Support Config
$support_config = [
    'whatsapp' => get_option('tv_support_whatsapp', ''),
    'email' => get_option('tv_support_email', ''),
    'telegram' => get_option('tv_support_telegram', '')
];

// Cache Configuration
// IMPORTANT: We now cache ONLY the DB Query results, not the currency calculation
$cache_key_plans_raw = 'streamos_plans_raw_v5'; 
$cache_key_sports = 'streamos_sports_v2'; 
$cache_key_user_subs = 'streamos_subs_' . $user_id; 
$cache_time = 3600; 
$user_cache_time = 300; 

// 3. PLANS FETCHING (FIXED: Real-time Currency)
$plans_data = [];
$plans_raw_cached = get_transient($cache_key_plans_raw);

if (false === $plans_raw_cached) {
    if($wpdb->get_var("SHOW TABLES LIKE '$table_plans'") == $table_plans) {
        $has_order_col = $wpdb->get_results("SHOW COLUMNS FROM $table_plans LIKE 'display_order'");
        $order_sql = !empty($has_order_col) ? "display_order ASC, price ASC" : "price ASC";
        
        // Store raw DB results
        $plans_raw_cached = $wpdb->get_results("SELECT * FROM $table_plans ORDER BY $order_sql");
        set_transient($cache_key_plans_raw, $plans_raw_cached, $cache_time);
    }
}

// Perform Live Processing on Cached DB Data
if (!empty($plans_raw_cached)) {
    foreach($plans_raw_cached as $p) {
        // Default values (Base USD)
        $price_display = '$' . number_format($p->price, 0); 
        $local_raw_price = (float)$p->price;
        $currency_symbol = '$';
        $currency_code = 'USD';

        // LIVE CONVERSION: Run this on EVERY request
        if ($tv_instance && method_exists($tv_instance, 'get_currency_data')) {
            $cdata = $tv_instance->get_currency_data(floatval($p->price));
            if (is_array($cdata)) {
                $formatted = html_entity_decode($cdata['formatted'], ENT_QUOTES, 'UTF-8');
                $symbol = html_entity_decode($cdata['symbol'], ENT_QUOTES, 'UTF-8');
                $price_display = $formatted;
                $local_raw_price = $cdata['amount_local']; 
                $currency_symbol = $symbol;
                $currency_code = $cdata['code'];
            }
        }

        $discount_tiers = [];
        if (!empty($p->discount_tiers)) {
            $decoded_tiers = json_decode($p->discount_tiers, true);
            if (is_array($decoded_tiers)) $discount_tiers = $decoded_tiers;
        }

        $category = isset($p->category) && !empty($p->category) ? strtolower($p->category) : 'standard';

        $plans_data[] = [
            'id' => $p->id,
            'name' => $p->name,
            'price' => $price_display, // Live converted string
            'raw_price' => $p->price, // Base USD
            'local_price_raw' => $local_raw_price, // Live converted float
            'currency' => [ 'code' => $currency_code, 'symbol' => $currency_symbol ],
            'period' => ($p->duration_days == 30) ? 'month' : $p->duration_days . ' days',
            'duration_days' => $p->duration_days,
            'features' => explode("\n", $p->description),
            'recommended' => (strpos(strtolower($p->name), 'premium') !== false),
            'multi_device' => (bool)$p->allow_multi_connections,
            'discounts' => $discount_tiers,
            'category' => $category 
        ];
    }
}

// 4. SUBSCRIPTIONS (With Credential Parsing)
$active_subs = get_transient($cache_key_user_subs);
$user_alerts = []; 
if (false === $active_subs) {
    $active_subs = [];
    if($wpdb->get_var("SHOW TABLES LIKE '$table_subs'") == $table_subs) {
        // [FIX] Strictly filter out 'pending' status so they don't show on dashboard
        // Only Active or Expired (Completed lifecycles) are shown.
        $subs_raw = $wpdb->get_results($wpdb->prepare("
            SELECT s.*, p.name as plan_name, p.price 
            FROM $table_subs s 
            LEFT JOIN $table_plans p ON s.plan_id = p.id 
            WHERE s.user_id = %d AND s.status IN ('active', 'expired') 
            ORDER BY s.end_date DESC
        ", $user_id));

        if($subs_raw) {
            foreach($subs_raw as $s) {
                // Days left
                $days_left = ceil((strtotime($s->end_date) - time()) / 86400);
                
                // Price formatting (Live conversion also applied here for consistency)
                $price_display = '$' . number_format((float)($s->price ?: 0), 0); 
                if ($tv_instance && method_exists($tv_instance, 'get_currency_data')) {
                    $cdata = $tv_instance->get_currency_data(floatval($s->price));
                    $price_display = html_entity_decode($cdata['formatted'], ENT_QUOTES, 'UTF-8');
                }

                // Credential Parsing
                $raw_m3u = is_string($s->credential_m3u ?? null) ? (string)$s->credential_m3u : '';
                $lines = preg_split("/\r\n|\r|\n/", trim($raw_m3u));
                $lines = is_array($lines) ? array_values(array_filter(array_map('trim', $lines))) : [];
                $base_m3u = '';
                $attachments = [];
                $host_alt = '';

                foreach ($lines as $ln) {
                    if ($ln === '') continue;
                    if (preg_match('/^\[\s*Panel\s*:\s*(.+)\s*\]$/i', $ln)) continue;
                    $base_m3u = $ln;
                    break;
                }
                foreach ($lines as $ln) {
                    $match = [];
                    if (preg_match('/^\[\s*Panel\s*:\s*(.+)\s*\]$/i', $ln, $match)) {
                        $maybe = trim($match[1]);
                        if (!empty($maybe)) $attachments[] = $maybe;
                        continue;
                    }
                    if (!empty($base_m3u) && $ln !== $base_m3u && preg_match('~^https?://~i', $ln)) {
                        $attachments[] = $ln;
                    }
                }
                
                if (!empty($base_m3u) && preg_match('~^https?://~i', $base_m3u)) {
                    $host_candidate = $base_m3u;
                } elseif (!empty($attachments[0]) && preg_match('~^https?://~i', (string)$attachments[0])) {
                    $host_candidate = (string)$attachments[0];
                }
                if (!empty($host_candidate)) {
                    $pu = @parse_url($host_candidate);
                    if (is_array($pu) && !empty($pu['scheme']) && !empty($pu['host'])) {
                        $host_alt = $pu['scheme'] . '://' . $pu['host'] . (!empty($pu['port']) ? ':' . $pu['port'] : '');
                    }
                }

                $active_subs[] = [
                    'id' => $s->id, 
                    'plan_id' => $s->plan_id,
                    'planName' => $s->plan_name ?: 'Standard Plan', 
                    'status' => ucfirst($s->status),
                    'daysLeft' => max(0, $days_left), 
                    'nextBillingDate' => date('M d, Y', strtotime($s->end_date)),
                    'price' => $price_display, 
                    'credentials' => [
                        'username' => $s->credential_user ?: 'Pending...',
                        'password' => $s->credential_pass ?: '',
                        'url' => $s->credential_url ?: 'http://line.streamos.tv',
                        'hostAlt' => $host_alt,
                        'm3uUrl' => !empty($base_m3u) ? $base_m3u : ($s->credential_m3u ?: '#'),
                        'attachments' => $attachments
                    ]
                ];
            }
        }
    }
    set_transient($cache_key_user_subs, $active_subs, $user_cache_time);
}

// Calculate alerts from the final active_subs list so they persist during cache hits
if (is_array($active_subs)) {
    foreach ($active_subs as $s) {
        $s = (array) $s;
        $status = isset($s['status']) ? strtolower((string)$s['status']) : '';
        $days = isset($s['daysLeft']) ? (int)$s['daysLeft'] : 99;

        if ($status === 'active' && $days <= 3) {
            $user_alerts[] = [
                'type' => 'warning', 
                'message' => "Your subscription '{$s['planName']}' expires in {$days} days.", 
                'action' => 'shop',
                'sub_id' => $s['id'],
                'plan_id' => $s['plan_id']
            ];
        }
    }
}

// 5. INVOICES
$invoices = [];
if($wpdb->get_var("SHOW TABLES LIKE '$table_payments'") == $table_payments) {
    $payments_raw = $wpdb->get_results($wpdb->prepare("
        SELECT pay.*, pl.name as plan_name 
        FROM $table_payments pay
        LEFT JOIN $table_subs s ON pay.subscription_id = s.id
        LEFT JOIN $table_plans pl ON s.plan_id = pl.id
        WHERE pay.user_id = %d 
        AND pay.status NOT IN ('CANCELLED', 'REJECTED', 'failed')
        ORDER BY pay.date DESC LIMIT 100
    ", $user_id));

    foreach($payments_raw as $pay) {
        $needs_proof = false;
        $attempt_recent = false;
        $resolved_payment_link = '';
        $resolved_bank_details = [];
        
        if($wpdb->get_var("SHOW TABLES LIKE '$table_methods'") == $table_methods) {
            $method_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_methods WHERE name = %s LIMIT 1", $pay->method));
            if ($method_row) {
                $resolved_payment_link = $method_row->link;
                if (!empty($resolved_payment_link) && is_string($resolved_payment_link)) {
                    $resolved_payment_link = str_replace(['amount%27=', "amount'=", "amount="], 'amount=', $resolved_payment_link);
                }
                if(!empty($method_row->bank_name) || !empty($method_row->account_number) || !empty($method_row->instructions)) {
                    $resolved_bank_details = [
                        'bank_name' => $method_row->bank_name,
                        'account_number' => $method_row->account_number,
                        'instructions' => $method_row->instructions
                    ];
                }
            }
        }

        $status_allows_proof = in_array($pay->status, array('AWAITING_PROOF', 'pending'), true) && empty($pay->proof_url) && $pay->method !== 'System Grant';
        $has_link = !empty($resolved_payment_link);
        if (!empty($pay->attempted_at)) {
            $attempt_ts = strtotime($pay->attempted_at);
            if ($attempt_ts && (time() - $attempt_ts) <= HOUR_IN_SECONDS) {
                $attempt_recent = true;
            }
        }

        if ($status_allows_proof) {
            if (!$has_link) { $needs_proof = true; } else { $needs_proof = $attempt_recent; }
        }
        
        // Display locked historical currency, but format nicely
        $amount_display = '$' . number_format($pay->amount, 0); 
        if (!empty($pay->currency) && $pay->currency !== 'USD') {
            $amount_display = number_format($pay->amount, 0) . ' ' . $pay->currency;
            if ($tv_instance && method_exists($tv_instance, 'get_currency_data')) {
                 $cdata = $tv_instance->get_currency_data(0);
                 // Only format if code matches, otherwise show historical
                 if ($cdata['code'] === $pay->currency) {
                     $sym = html_entity_decode($cdata['symbol'], ENT_QUOTES, 'UTF-8');
                     $amount_display = $sym . number_format($pay->amount, 0);
                 }
            }
        }

        $invoices[] = [
            'id' => '#INV-' . str_pad($pay->id, 5, '0', STR_PAD_LEFT),
            'raw_id' => $pay->id,
            'raw_status' => $pay->status,
            'date' => date('M d, Y', strtotime($pay->date)),
            'plan' => $pay->plan_name ?: 'Subscription',
            'amount' => $amount_display,
            'status' => ucfirst($pay->status),
            'needs_proof' => $needs_proof,
            'attempt_recent' => $attempt_recent,
            'has_gateway_link' => !empty($resolved_payment_link),
            'bank_details' => $resolved_bank_details,
            'payment_link' => $resolved_payment_link,
            'mark_attempt_nonce' => wp_create_nonce('tv_mark_attempt_' . (int)$pay->id)
        ];
    }
}

// 6. SPORTS (Fix: Force UTC for React)
$sports_data = get_transient($cache_key_sports);
if (false === $sports_data) {
    $sports_data = [];
    if($wpdb->get_var("SHOW TABLES LIKE '$table_sports'") == $table_sports) {
        $events_raw = $wpdb->get_results("
            SELECT * FROM $table_sports 
            WHERE start_time > DATE_SUB(NOW(), INTERVAL 5 HOUR) 
            ORDER BY start_time ASC 
            LIMIT 40
        ");
        
        foreach($events_raw as $e) {
            $type = !empty($e->sport_type) ? $e->sport_type : ($e->type ?? 'other');
            
            $channels = [];
            if (!empty($e->channels_json)) {
                $decoded = json_decode($e->channels_json, true);
                if (is_array($decoded)) $channels = $decoded;
            }
            if (empty($channels) && !empty($e->channel)) {
                $parts = explode(',', $e->channel);
                foreach ($parts as $p) {
                    $channels[] = ['name' => trim($p), 'region' => 'HD'];
                }
            }

            $home_score = ($e->home_score !== null && $e->home_score !== '') ? (int)$e->home_score : null;
            $away_score = ($e->away_score !== null && $e->away_score !== '') ? (int)$e->away_score : null;

            // NUCLEAR TIME FIX: Force ISO 8601 UTC ('Z') for React
            // DB Time (e.g. 2025-02-18 18:00:00) -> UTC Integer -> ISO String
            $ts = strtotime($e->start_time . ' UTC'); 
            $iso_time = gmdate('Y-m-d\TH:i:s\Z', $ts);

            $sports_data[] = [
                'id' => $e->id, 
                'title' => $e->title, 
                'league' => $e->league, 
                'type' => $type,
                'startTime' => $iso_time, // Sending clean ISO string
                'channel' => $e->channel,
                'home_team' => $e->home_team_name ?? '',
                'away_team' => $e->away_team_name ?? '',
                'home_logo' => $e->badge_home ?? '',
                'away_logo' => $e->badge_away ?? '',
                'status' => $e->api_status ?? 'Scheduled',
                'home_score' => $home_score,
                'away_score' => $away_score,
                'channels' => $channels
            ];
        }
        set_transient($cache_key_sports, $sports_data, $cache_time);
    }
}

// 7. METHODS
$payment_methods = [];
if($wpdb->get_var("SHOW TABLES LIKE '$table_methods'") == $table_methods) {
    $methods_raw = $wpdb->get_results("SELECT * FROM $table_methods WHERE status = 'active' ORDER BY display_order ASC");
    foreach($methods_raw as $m) {
        $check_country = $detected_country;
        if (!empty($m->countries)) {
            $allowed = array_map('trim', explode(',', strtoupper($m->countries)));
            if (!in_array($check_country, $allowed)) {
                continue;
            }
        }
        $method_link = $m->link;
        if (!empty($method_link) && is_string($method_link)) {
            $method_link = str_replace(['amount%27=', "amount'=", "amount="], 'amount=', $method_link);
        }
        $is_recommended = false;
        if (stripos($m->name, $check_country) !== false) {
            $is_recommended = true;
        }
        $tags = [];
        if ($is_recommended) $tags[] = "Recommended";
        if (!empty($m->link)) $tags[] = "Instant";
        else $tags[] = "Manual";

        $payment_methods[] = [
            'id' => $m->id, 'name' => $m->name, 'slug' => $m->slug, 'description' => $m->instructions, 
            'is_gateway' => (!empty($m->link) || !empty($m->flutterwave_enabled)), 'payment_link' => $m->link, 'logo_url' => $m->logo_url,
            'is_recommended' => $is_recommended, 'tags' => $tags
        ];
    }
    usort($payment_methods, function($a, $b) {
        return ($b['is_recommended'] ? 1 : 0) - ($a['is_recommended'] ? 1 : 0);
    });
}

// 8. NEWS
$news_data = [];
if($wpdb->get_var("SHOW TABLES LIKE '$table_news'") == $table_news) {
    $news_raw = $wpdb->get_results("SELECT * FROM $table_news WHERE status='active' ORDER BY start_date DESC LIMIT 5");
    foreach($news_raw as $n) {
        $action = $n->button_action ?: 'dashboard';
        $link = '';
        if (strpos($action, 'http') === 0 || strpos($action, '//') === 0) {
            $link = $action;
            $action = 'external';
        }
        $color_val = $n->color_scheme ?: 'from-indigo-600 to-violet-600';
        $is_custom_hex = (strpos($color_val, '#') === 0);

        $news_data[] = [
            'id' => $n->id,
            'title' => $n->title,
            'description' => $n->message,
            'buttonText' => $n->button_text ?: 'Read More',
            'action' => $action,
            'link' => $link,
            'color' => $color_val,
            'isHex' => $is_custom_hex
        ];
    }
}

$json_opts = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
$ajax_url = admin_url('admin-ajax.php');
$checkout_nonce = wp_create_nonce('tv_checkout_nonce');

?>
<script>
    const USER_DATA = <?php echo json_encode($user_data, $json_opts); ?>;
    const PLANS = <?php echo json_encode($plans_data, $json_opts); ?>;
    const ACTIVE_SUBSCRIPTIONS = <?php echo json_encode($active_subs, $json_opts); ?>;
    const INVOICES = <?php echo json_encode($invoices, $json_opts); ?>;
    const SPORTS_RAW = <?php echo json_encode($sports_data, $json_opts); ?>;
    const PAYMENT_METHODS = <?php echo json_encode($payment_methods, $json_opts); ?>;
    const IS_SANDBOX = <?php echo (defined('TV_SANDBOX_ACTIVE')) ? 'true' : 'false'; ?>;
    window.TV_AJAX_URL = <?php echo json_encode($ajax_url, $json_opts); ?>;
    window.TV_CHECKOUT_NONCE = <?php echo json_encode($checkout_nonce, $json_opts); ?>; 
    window.SERVER_NEWS = <?php echo json_encode($news_data, $json_opts); ?>;
    window.USER_ALERTS = <?php echo json_encode($user_alerts, $json_opts); ?>;
    window.SUPPORT_CONFIG = <?php echo json_encode($support_config, $json_opts); ?>;
</script>