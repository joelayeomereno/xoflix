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

<div class="tv-page-header">
    <div>
        <h1>Transactions & Fulfillment</h1>
        <p>Review payments, check proofs, and provision subscription credentials.</p>
    </div>
</div>

<div class="tv-card">
    
    <!-- Toolbar -->
    <div class="tv-toolbar" style="background:white; padding:16px 24px; border-bottom:1px solid var(--tv-border); display:flex; justify-content:space-between; align-items:center;">
        <div style="display:flex; gap:8px;">
            <a href="?page=tv-subs-manager&tab=payments" class="tv-btn tv-btn-sm <?php echo empty($filter_status) ? 'tv-btn-primary' : 'tv-btn-secondary'; ?>">All</a>
            <a href="?page=tv-subs-manager&tab=payments&status=needs_action" class="tv-btn tv-btn-sm <?php echo $filter_status === 'needs_action' ? 'tv-btn-primary' : 'tv-btn-secondary'; ?>">Needs Action</a>
        </div>

        <!-- CURRENCY TOGGLE (Controls Base & Locked Value columns only) -->
        <div style="display:flex; gap:0; background:var(--tv-surface-active); border-radius:8px; border:1px solid var(--tv-border); overflow:hidden;">
            <button type="button" onclick="tvToggleCurrency('USD')" id="tv-curr-usd" class="tv-btn-text" style="padding:6px 12px; font-weight:700; color:var(--tv-text); background:white;">USD</button>
            <button type="button" onclick="tvToggleCurrency('NGN')" id="tv-curr-ngn" class="tv-btn-text" style="padding:6px 12px; font-weight:700; color:var(--tv-text-muted);">NGN</button>
        </div>

        <form method="get" style="display:flex; gap:10px;">
            <input type="hidden" name="page" value="tv-subs-manager">
            <input type="hidden" name="tab" value="payments">
            <input type="text" name="s" class="tv-input" placeholder="Search Ref or User..." value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
            <button type="submit" class="tv-btn tv-btn-secondary">Search</button>
        </form>
    </div>

    <!-- Financial Summary (GMT+1, Week: Mon-Sun) -->
    <?php if (isset($tx_summaries) && is_array($tx_summaries)): ?>
        <div style="padding:16px 24px; border-bottom:1px solid var(--tv-border); background:var(--tv-surface);">
            <div style="display:grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap:12px;">
                <?php foreach ($tx_summaries as $k => $meta): ?>
                    <div class="tv-card" style="padding:12px; border:1px solid var(--tv-border);">
                        <div style="font-size:11px; color:var(--tv-text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">
                            <?php echo esc_html($meta['label']); ?>
                        </div>
                        <div style="margin-top:6px; font-size:16px; font-weight:900; color:var(--tv-text); line-height:1.2;">
                            <?php
                                $totals = isset($meta['totals']) ? (array)$meta['totals'] : [];
                                $usd_val = isset($totals['USD']) ? $totals['USD'] : 0;
                                $ngn_val = isset($totals['NGN']) ? $totals['NGN'] : 0;
                            ?>
                            <!-- Toggled Summary Values -->
                            <span class="tv-val-usd">$<?php echo number_format($usd_val, 2); ?></span>
                            <span class="tv-val-ngn" style="display:none;">&#8358;<?php echo number_format($ngn_val, 2); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:10px; font-size:11px; color:var(--tv-text-muted);">
                Week is calculated Monday   Sunday. Timezone used: GMT+1.
            </div>
        </div>
    <?php endif; ?>

    <div class="tv-table-container">
        <table class="tv-table">
            <thead>
                <tr>
                    <th width="12%">Ref</th>
                    <th width="18%">User / Type</th>
                    <th width="12%">Base Value</th>
                    <th width="12%">Locked Value</th>
                    <th width="12%">Paid (Local)</th> <!-- NEW COLUMN -->
                    <th width="10%">Method</th>
                    <th width="14%">Status / Proof</th>
                    <th width="10%" align="right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($payments): foreach($payments as $pay): 
                    $months = tv_get_duration_months($pay->start_date, $pay->end_date);
                    
                    // Locked System Values (Immutable after payment)
                    $amt_usd = isset($pay->amount_usd) ? floatval($pay->amount_usd) : 0;
                    $amt_ngn = isset($pay->amount_ngn) ? floatval($pay->amount_ngn) : 0;
                    
                    // Fallback to payment amount if locked values missing (Legacy data)
                    if ($amt_usd == 0) $amt_usd = floatval($pay->amount);
                    
                    // Derive Base NGN for Toggle Support using implied rate
                    $base_usd = floatval($pay->base_plan_price) * $months;
                    $implied_rate = ($amt_usd > 0) ? ($amt_ngn / $amt_usd) : 0;
                    $base_ngn = $base_usd * $implied_rate;

                    // NEW: Local Currency Data (What the user actually paid)
                    $local_currency = !empty($pay->currency) ? $pay->currency : 'USD';
                    $local_symbol = tv_get_currency_symbol($local_currency);
                    $local_amount = floatval($pay->amount);

                    $is_renewal = false;
                    if (!empty($pay->start_date) && !empty($pay->date)) {
                        $sub_start_ts = strtotime($pay->start_date);
                        $pay_date_ts = strtotime($pay->date);
                        if ($sub_start_ts > 0 && ($pay_date_ts - $sub_start_ts) > 86400) {
                            $is_renewal = true;
                        }
                    }
                    
                    // Transaction ID logic
                    $txn_display = $pay->transaction_id;
                    if (empty($txn_display)) {
                        $txn_display = 'TMP-' . $pay->id; 
                    }
                    $is_xpay = (strpos((string)$txn_display, 'XPAY-') === 0);
                ?>
                <tr>
                    <td>
                        <?php if ($is_xpay): ?>
                        <span style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:11px;font-weight:800;display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border-radius:6px;background:linear-gradient(135deg,#d1fae5,#a7f3d0);border:1.5px solid #34d399;color:#065f46;letter-spacing:0.03em;">
                            <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#10b981;box-shadow:0 0 0 2px #6ee7b7;flex-shrink:0;"></span>
                            <?php echo esc_html($txn_display); ?>
                        </span>
                        <?php else: ?>
                        <span style="font-family:monospace; background:var(--tv-surface-active); padding:2px 6px; border-radius:4px; font-weight:700;">
                            <?php echo esc_html($txn_display); ?>
                        </span>
                        <?php endif; ?>
                        <div style="font-size:11px; color:var(--tv-text-muted); margin-top:2px;">
                            <?php echo date('M d, H:i', strtotime($pay->date)); ?>
                        </div>
                    </td>
                    <td>
                        <?php
                            $display_name = !empty($pay->display_name) ? $pay->display_name : $pay->user_login;
                            $phone = (string) get_user_meta((int)$pay->user_id, 'phone', true);
                            $connections = isset($pay->connections) ? (int)$pay->connections : 1;
                        ?>
                        <div style="display:flex; flex-direction:column; gap:2px;">
                            <a href="#" class="tv-user-popup-trigger" data-tv-user-name="<?php echo esc_attr($display_name); ?>" data-tv-user-email="<?php echo esc_attr($pay->user_email); ?>" data-tv-user-phone="<?php echo esc_attr($phone); ?>" data-tv-user-connections="<?php echo esc_attr($connections); ?>" style="font-weight:800; color:var(--tv-primary); text-decoration:none;">
                                <?php echo esc_html($display_name); ?>
                            </a>
                            <div style="font-size:11px; color:var(--tv-text-muted);">
                                <?php echo esc_html($pay->plan_name); ?>
                                <span style="opacity:0.7;">(<?php echo $months; ?> Mo)</span>
                            </div>
                            <?php if($is_renewal): ?>
                                <span style="display:inline-block; align-self:flex-start; font-size:9px; font-weight:800; text-transform:uppercase; background:#dcfce7; color:#166534; padding:2px 6px; border-radius:4px; border:1px solid #bbf7d0;">
                                    Renewal
                                </span>
                            <?php else: ?>
                                <span style="display:inline-block; align-self:flex-start; font-size:9px; font-weight:800; text-transform:uppercase; background:#f1f5f9; color:#64748b; padding:2px 6px; border-radius:4px; border:1px solid #e2e8f0;">
                                    New
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    
                    <!-- Base Value (Toggles) -->
                    <td>
                        <div style="font-weight:500; color:var(--tv-text-muted);">
                            <span class="tv-val-usd">$<?php echo number_format($base_usd, 0); ?></span>
                            <span class="tv-val-ngn" style="display:none;">&#8358;<?php echo number_format($base_ngn, 0); ?></span>
                        </div>
                        <div style="font-size:10px; color:var(--tv-text-muted); text-transform:uppercase;">Standard Rate</div>
                    </td>
                    
                    <!-- Locked System Value (Toggles) -->
                    <td>
                        <span style="font-weight:700; color:var(--tv-primary);">
                            <span class="tv-val-usd">$<?php echo number_format($amt_usd, 0); ?></span>
                            <span class="tv-val-ngn" style="display:none;">&#8358;<?php echo number_format($amt_ngn, 0); ?></span>
                        </span>
                        <div style="font-size:10px; color:var(--tv-text-muted);">Locked at Transaction</div>
                    </td>

                    <!-- NEW: Paid (Local) - Immutable -->
                    <td>
                        <div style="font-weight:700; color:#0f172a;">
                            <?php echo esc_html($local_symbol . number_format($local_amount, 2)); ?>
                        </div>
                        <div style="font-size:10px; color:var(--tv-text-muted); font-weight:600;">
                            <?php echo esc_html($local_currency); ?>
                        </div>
                    </td>

                    <td>
                        <span style="font-size:13px;"><?php echo esc_html($pay->method); ?></span>
                    </td>
                    <td>
                        <span class="tv-badge <?php echo strtolower($pay->status); ?>"><?php echo $pay->status; ?></span>
                        
                        <?php if(!empty($pay->proof_url)): 
                            $proofs = [];
                            $decoded = json_decode($pay->proof_url, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $proofs = $decoded;
                            } else {
                                $proofs = [$pay->proof_url]; 
                            }
                        ?>
                            <div style="margin-top:8px; display:flex; flex-direction:column; gap:4px;">
                                <?php foreach($proofs as $idx => $url): ?>
                                    <a href="<?php echo esc_url($url); ?>" target="_blank" class="tv-btn-text" style="display:flex; align-items:center; gap:4px; font-size:11px; padding:0;">
                                        <span class="dashicons dashicons-paperclip"></span> Proof <?php echo ($idx > 0) ? ($idx+1) : ''; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td align="right">
                        <?php
                            $st = strtoupper((string)$pay->status);
                            $needs_action = in_array($st, array('PENDING','AWAITING_PROOF','IN_PROGRESS','PENDING_ADMIN_REVIEW'), true);
                        ?>
                        <?php if ($needs_action): ?>
                            <div style="display:flex; justify-content:flex-end; gap:6px; flex-wrap:wrap;">
                                <a href="<?php echo wp_nonce_url('?page=tv-subs-manager&tab=payments&action=approve_pay&pid='.$pay->id, 'approve_pay_'.$pay->id); ?>" class="tv-btn tv-btn-primary tv-btn-sm">Approve</a>
                                <button type="button" onclick="openRejectModal(<?php echo (int)$pay->id; ?>)" class="tv-btn tv-btn-danger tv-btn-sm">Reject</button>
                            </div>
                        <?php elseif ($st === 'APPROVED'): ?>
                            <button type="button" 
                                onclick="openFulfillModal(this)"
                                data-id="<?php echo $pay->id; ?>"
                                data-user="<?php echo esc_attr($pay->user_login); ?>"