<?php if (!defined('ABSPATH')) { exit; } ?>

    <div class="tv-table-container">
        <table class="tv-table">
            <thead>
                <tr>
                    <th width="12%">Ref</th>
                    <th width="18%">User / Type</th>
                    <th width="12%">Base Value</th>
                    <th width="12%">Locked Value</th>
                    <th width="12%">Paid (Local)</th>
                    <th width="10%">Method</th>
                    <th width="14%">Status / Proof</th>
                    <th width="10%" align="right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($payments): foreach($payments as $pay): 

                    // -----------------------------------------------------------
                    // FIX: Base Value uses gross_usd (locked at payment creation).
                    // Previously used base_plan_price * months-from-dates, which
                    // inflated on renewals because the subscription dates grow
                    // with each extension (e.g. 1-month renewal on an already-
                    // active sub makes the date span 2 months ? price doubles).
                    // gross_usd is written once at create_subscription_and_payment
                    // and never changes.
                    // -----------------------------------------------------------

                    // Locked System Values
                    $amt_usd = isset($pay->amount_usd) && floatval($pay->amount_usd) > 0
                                ? floatval($pay->amount_usd)
                                : floatval($pay->amount);
                    $amt_ngn = isset($pay->amount_ngn) ? floatval($pay->amount_ngn) : 0;

                    // Base Value: gross_usd (pre-discount total, locked at tx time)
                    // Fallback to amount_usd for legacy rows without gross_usd
                    $base_usd = isset($pay->gross_usd) && floatval($pay->gross_usd) > 0
                                ? floatval($pay->gross_usd)
                                : $amt_usd;

                    $implied_rate = ($amt_usd > 0 && $amt_ngn > 0) ? ($amt_ngn / $amt_usd) : 0;
                    $base_ngn     = $base_usd * $implied_rate;

                    // Duration label (display only   no longer feeds into price)
                    $months = tv_get_duration_months($pay->start_date, $pay->end_date);

                    // Local Currency Data
                    $local_currency = !empty($pay->currency) ? $pay->currency : 'USD';
                    $local_symbol   = tv_get_currency_symbol($local_currency);
                    $local_amount   = floatval($pay->amount);

                    $is_renewal = false;
                    if (!empty($pay->start_date) && !empty($pay->date)) {
                        $sub_start_ts = strtotime($pay->start_date);
                        $pay_date_ts  = strtotime($pay->date);
                        if ($sub_start_ts > 0 && ($pay_date_ts - $sub_start_ts) > 86400) {
                            $is_renewal = true;
                        }
                    }
                    
                    $txn_display = $pay->transaction_id;
                    if (empty($txn_display)) {
                        $txn_display = 'TMP-' . $pay->id; 
                    }
                    $is_xpay = (strpos((string)$txn_display, 'XPAY-') === 0);

                    // -----------------------------------------------------------
                    // FIX: Approve/Reject require proof_url to be present.
                    // A payment with no uploaded proof must never receive status-
                    // changing options   the admin has nothing to review yet.
                    // -----------------------------------------------------------
                    $has_proof = !empty($pay->proof_url);
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
                            $phone        = (string) get_user_meta((int)$pay->user_id, 'phone', true);
                            $connections  = isset($pay->connections) ? (int)$pay->connections : 1;
                        ?>
                        <div style="display:flex; flex-direction:column; gap:2px;">
                            <a href="#" class="tv-user-popup-trigger"
                               data-tv-user-name="<?php echo esc_attr($display_name); ?>"
                               data-tv-user-email="<?php echo esc_attr($pay->user_email); ?>"
                               data-tv-user-phone="<?php echo esc_attr($phone); ?>"
                               data-tv-user-connections="<?php echo esc_attr($connections); ?>"
                               style="font-weight:800; color:var(--tv-primary); text-decoration:none;">
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
                    
                    <!-- Base Value -->
                    <td>
                        <div style="font-weight:500; color:var(--tv-text-muted);">
                            <span class="tv-val-usd">$<?php echo number_format($base_usd, 0); ?></span>
                            <span class="tv-val-ngn" style="display:none;">&#8358;<?php echo number_format($base_ngn, 0); ?></span>
                        </div>
                        <div style="font-size:10px; color:var(--tv-text-muted); text-transform:uppercase;">Standard Rate</div>
                    </td>
                    
                    <!-- Locked System Value -->
                    <td>
                        <span style="font-weight:700; color:var(--tv-primary);">
                            <span class="tv-val-usd">$<?php echo number_format($amt_usd, 0); ?></span>
                            <span class="tv-val-ngn" style="display:none;">&#8358;<?php echo number_format($amt_ngn, 0); ?></span>
                        </span>
                        <div style="font-size:10px; color:var(--tv-text-muted);">Locked at Transaction</div>
                    </td>

                    <!-- Paid (Local) -->
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

                    <!-- Status / Proof -->
                    <td>
                        <span class="tv-badge <?php echo strtolower($pay->status); ?>"><?php echo $pay->status; ?></span>
                        
                        <?php if($has_proof): 
                            $proofs  = [];
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

                    <!-- Action -->
                    <td align="right">
                        <?php
                            $st           = strtoupper((string)$pay->status);
                            // FIX: Approve/Reject are only available when proof has been uploaded.
                            // Without proof there is nothing for the admin to review.
                            $needs_action = $has_proof && in_array($st, array('PENDING','AWAITING_PROOF','IN_PROGRESS','PENDING_ADMIN_REVIEW'), true);
                        ?>
                        <?php if ($needs_action): ?>
                            <div style="display:flex; justify-content:flex-end; gap:6px; flex-wrap:wrap;">
                                <a href="<?php echo wp_nonce_url('?page=tv-subs-manager&tab=payments&action=approve_pay&pid='.$pay->id, 'approve_pay_'.$pay->id); ?>" class="tv-btn tv-btn-primary tv-btn-sm">Approve</a>
                                <button type="button" onclick="openRejectModal(<?php echo (int)$pay->id; ?>)" class="tv-btn tv-btn-danger tv-btn-sm">Reject</button>
                            </div>
                        <?php elseif ($st === 'APPROVED'): ?>
                            <div style="display:flex; justify-content:flex-end; gap:6px; flex-wrap:wrap;">
                                <button type="button" 
                                    onclick="openFulfillModal(this)"
                                    data-id="<?php echo $pay->id; ?>"
                                    data-user="<?php echo esc_attr($pay->user_login); ?>"
                                    data-cred-user="<?php echo esc_attr($pay->credential_user ?? ''); ?>"
                                    data-cred-pass="<?php echo esc_attr($pay->credential_pass ?? ''); ?>"
                                    data-cred-url="<?php echo esc_attr($pay->credential_url ?? ''); ?>"
                                    data-cred-m3u="<?php echo esc_attr($pay->credential_m3u ?? ''); ?>"
                                    class="tv-btn tv-btn-primary tv-btn-sm">
                                    Fulfill
                                </button>
                                <?php if (!empty($pay->is_manual) || strpos((string)($pay->transaction_id ?? ''), 'MAN-') === 0): ?>
                                    <button type="button" onclick="tvDeleteManualTxn(<?php echo (int)$pay->id; ?>)" class="tv-btn tv-btn-danger tv-btn-sm" title="Delete manual transaction">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php elseif (!$has_proof && in_array($st, array('PENDING','AWAITING_PROOF','IN_PROGRESS','PENDING_ADMIN_REVIEW'), true)): ?>
                            <!-- No proof uploaded yet   no admin action available -->
                            <span style="color:var(--tv-text-muted); font-size:11px; font-style:italic;">Awaiting proof</span>
                        <?php else: ?>
                            <div style="display:flex; justify-content:flex-end; gap:6px;">
                                <span style="color:var(--tv-text-muted);">&mdash;</span>
                                <?php if (!empty($pay->is_manual) || strpos((string)($pay->transaction_id ?? ''), 'MAN-') === 0): ?>
                                    <button type="button" onclick="tvDeleteManualTxn(<?php echo (int)$pay->id; ?>)" class="tv-btn tv-btn-danger tv-btn-sm" title="Delete manual transaction">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="8" style="text-align:center; padding:30px; color:var(--tv-text-muted);">No transactions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>