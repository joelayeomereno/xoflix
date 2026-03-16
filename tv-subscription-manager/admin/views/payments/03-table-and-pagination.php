<table class="tv-table">
            <thead>
                <tr>
                    <th width="12%">Ref / Date</th>
                    <th width="20%">Customer</th>
                    <th width="12%">Base Value</th>
                    <th width="12%">Locked Value</th>
                    <th width="11%">Paid (Local)</th>
                    <th width="10%">Method</th>
                    <th width="13%">Status / Proof</th>
                    <th width="10%" align="right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($payments): foreach ($payments as $pay):
                    $months = tv_get_duration_months($pay->start_date, $pay->end_date);
                    $amt_usd = isset($pay->amount_usd) ? floatval($pay->amount_usd) : 0;
                    $amt_ngn = isset($pay->amount_ngn) ? floatval($pay->amount_ngn) : 0;
                    if ($amt_usd == 0) $amt_usd = floatval($pay->amount);
                    $base_usd     = floatval($pay->base_plan_price) * $months;
                    $implied_rate = ($amt_usd > 0) ? ($amt_ngn / $amt_usd) : 0;
                    $base_ngn     = $base_usd * $implied_rate;
                    $local_currency = !empty($pay->currency) ? $pay->currency : 'USD';
                    $local_symbol   = tv_get_currency_symbol($local_currency);
                    $local_amount   = floatval($pay->amount);
                    $is_renewal = false;
                    if (!empty($pay->start_date) && !empty($pay->date)) {
                        $sub_start_ts = strtotime($pay->start_date);
                        $pay_date_ts  = strtotime($pay->date);
                        if ($sub_start_ts > 0 && ($pay_date_ts - $sub_start_ts) > 86400) $is_renewal = true;
                    }
                    $txn_display = !empty($pay->transaction_id) ? $pay->transaction_id : ('TMP-' . $pay->id);
                    $is_xpay     = (strpos((string)$txn_display, 'XPAY-') === 0);
                    $display_name = !empty($pay->display_name) ? $pay->display_name : $pay->user_login;
                    $phone        = (string) get_user_meta((int)$pay->user_id, 'phone', true);
                    $connections  = isset($pay->connections) ? (int)$pay->connections : 1;
                    $plan_name    = !empty($pay->plan_name) ? $pay->plan_name : ' ';
                    $is_manual    = !empty($pay->is_manual);
                    $status_lc    = strtolower($pay->status);
                ?>
                <tr>
                    <td>
                        <?php if ($is_xpay): ?>
                        <div style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:11px;font-weight:800;display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border-radius:6px;background:linear-gradient(135deg,#d1fae5,#a7f3d0);border:1.5px solid #34d399;color:#065f46;letter-spacing:0.03em;">
                            <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#10b981;box-shadow:0 0 0 2px #6ee7b7;flex-shrink:0;"></span>
                            <?php echo esc_html($txn_display); ?>
                        </div>
                        <?php else: ?>
                        <div style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:12px;font-weight:700;background:var(--tv-surface-active);display:inline-block;padding:2px 7px;border-radius:5px;border:1px solid var(--tv-border);">
                            <?php echo esc_html($txn_display); ?>
                        </div>
                        <?php endif; ?>
                        <div style="font-size:11px;color:var(--tv-text-muted);margin-top:3px;">
                            <?php echo date('M d, Y H:i', strtotime($pay->date)); ?>
                        </div>
                        <?php if ($is_manual): ?>
                            <span class="tv-badge manual" style="margin-top:4px;font-size:9px;">Manual</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;flex-direction:column;gap:3px;">
                            <a href="#" class="tv-user-popup-trigger"
                               data-tv-user-name="<?php echo esc_attr($display_name); ?>"
                               data-tv-user-email="<?php echo esc_attr($pay->user_email); ?>"
                               data-tv-user-phone="<?php echo esc_attr($phone); ?>"
                               data-tv-user-plan="<?php echo esc_attr($plan_name); ?>"
                               data-tv-user-connections="<?php echo esc_attr($connections); ?>"
                               style="font-weight:800;color:var(--tv-primary);text-decoration:none;font-size:13.5px;">
                                <?php echo esc_html($display_name); ?>
                            </a>
                            <div style="font-size:11px;color:var(--tv-text-muted);">
                                <?php echo esc_html($plan_name); ?>
                                <span style="opacity:0.6;">(<?php echo $months; ?> Mo)</span>
                            </div>
                            <?php if ($is_renewal): ?>
                                <span class="tv-badge renewal" style="align-self:flex-start;font-size:9px;">Renewal</span>
                            <?php else: ?>
                                <span class="tv-badge new" style="align-self:flex-start;font-size:9px;">New</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight:500;color:var(--tv-text-muted);">
                            <span class="tv-val-usd">$<?php echo number_format($base_usd, 0); ?></span>
                            <span class="tv-val-ngn" style="display:none;">&#8358;<?php echo number_format($base_ngn, 0); ?></span>
                        </div>
                        <div style="font-size:10px;color:var(--tv-text-muted);text-transform:uppercase;margin-top:2px;">Standard Rate</div>
                    </td>
                    <td>
                        <span style="font-weight:800;color:var(--tv-primary);">
                            <span class="tv-val-usd">$<?php echo number_format($amt_usd, 0); ?></span>
                            <span class="tv-val-ngn" style="display:none;">&#8358;<?php echo number_format($amt_ngn, 0); ?></span>
                        </span>
                        <div style="font-size:10px;color:var(--tv-text-muted);margin-top:2px;">Locked at Tx</div>
                    </td>
                    <td>
                        <div style="font-weight:700;color:var(--tv-text);">
                            <?php echo esc_html($local_symbol . number_format($local_amount, 2)); ?>
                        </div>
                        <div style="font-size:10px;color:var(--tv-text-muted);font-weight:600;"><?php echo esc_html($local_currency); ?></div>
                    </td>
                    <td>
                        <span style="font-size:12.5px;color:var(--tv-text);"><?php echo esc_html($pay->method); ?></span>
                    </td>
                    <td>
                        <span class="tv-badge <?php echo esc_attr($status_lc); ?>"><?php echo esc_html($pay->status); ?></span>
                        <?php if (!empty($pay->proof_url)):
                            $proofs  = [];
                            $decoded = json_decode($pay->proof_url, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) { $proofs = $decoded; }
                            else { $proofs = [$pay->proof_url]; }
                        ?>
                            <div style="margin-top:6px;display:flex;flex-direction:column;gap:3px;">
                                <?php foreach ($proofs as $idx => $url): ?>
                                    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener" 
                                       style="display:flex;align-items:center;gap:4px;font-size:11px;color:var(--tv-primary);text-decoration:none;font-weight:600;">
                                        <span class="dashicons dashicons-paperclip" style="font-size:12px;width:12px;height:12px;"></span>
                                        Proof<?php echo ($idx > 0) ? ' '.($idx+1) : ''; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td align="right">
                        <?php
                        $st           = strtoupper((string)$pay->status);
                        $needs_action = in_array($st, ['PENDING','AWAITING_PROOF','IN_PROGRESS','PENDING_ADMIN_REVIEW'], true);
                        ?>
                        <?php if ($needs_action): ?>
                            <div style="display:flex;justify-content:flex-end;gap:5px;flex-wrap:wrap;">
                                <a href="<?php echo wp_nonce_url('?page=tv-subs-manager&tab=payments&action=approve_pay&pid='.$pay->id, 'approve_pay_'.$pay->id); ?>" 
                                   class="tv-btn tv-btn-primary tv-btn-sm">Approve</a>
                                <button type="button" onclick="openRejectModal(<?php echo (int)$pay->id; ?>)" 
                                        class="tv-btn tv-btn-danger tv-btn-sm">Reject</button>
                            </div>
                        <?php elseif ($st === 'APPROVED'): ?>
                            <div style="display:flex;justify-content:flex-end;gap:5px;flex-wrap:wrap;">
                                <button type="button"
                                        onclick="openFulfillModal(this)"
                                        data-id="<?php echo $pay->id; ?>"
                                        data-user="<?php echo esc_attr($pay->user_login); ?>"
                                        data-cred-user="<?php echo esc_attr($pay->credential_user ?? ''); ?>"
                                        data-cred-pass="<?php echo esc_attr($pay->credential_pass ?? ''); ?>"
                                        data-cred-url="<?php echo esc_attr($pay->credential_url ?? ''); ?>"
                                        data-cred-m3u="<?php echo esc_attr($pay->credential_m3u ?? ''); ?>"
                                        class="tv-btn tv-btn-success tv-btn-sm">Fulfill</button>
                                <?php if ($is_manual): ?>
                                    <button type="button" class="tv-btn tv-btn-danger tv-btn-sm"
                                            onclick="tvDeleteManualTransaction(<?php echo (int)$pay->id; ?>)"
                                            title="Delete manual transaction">
                                        <span class="dashicons dashicons-trash" style="font-size:13px;width:13px;height:13px;"></span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($is_manual): ?>
                            <button type="button" class="tv-btn tv-btn-danger tv-btn-sm"
                                    onclick="tvDeleteManualTransaction(<?php echo (int)$pay->id; ?>)"
                                    title="Delete manual transaction">
                                <span class="dashicons dashicons-trash" style="font-size:13px;width:13px;height:13px;"></span>
                                Delete
                            </button>
                        <?php else: ?>
                            <span style="color:var(--tv-text-muted);font-size:13px;">&mdash;</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:40px;color:var(--tv-text-muted);">
                            <span class="dashicons dashicons-list-view" style="font-size:32px;width:32px;height:32px;display:block;margin:0 auto 10px;opacity:0.3;"></span>
                            No transactions found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
        <div class="tv-toolbar" style="justify-content:center;border-top:1px solid var(--tv-border);border-bottom:none;border-radius:0 0 var(--tv-radius) var(--tv-radius);gap:6px;padding:14px;">
            <?php
            $base_link = "?page=tv-subs-manager&tab=payments&s=" . esc_attr($search_term)
                       . "&status=" . esc_attr($filter_status)
                       . (!empty($date_range) ? "&date_range=" . esc_attr($date_range) : '')
                       . (!empty($date_from)  ? "&date_from="  . esc_attr($date_from)  : '')
                       . (!empty($date_to)    ? "&date_to="    . esc_attr($date_to)    : '');
            if ($paged > 1) echo '<a href="' . $base_link . '&paged=' . ($paged-1) . '" class="tv-btn tv-btn-sm tv-btn-secondary">&laquo; Prev</a>';
            for ($i = max(1, $paged-2); $i <= min($total_pages, $paged+2); $i++) {
                $cls = ($i == $paged) ? 'tv-btn-primary' : 'tv-btn-secondary';
                echo '<a href="' . $base_link . '&paged=' . $i . '" class="tv-btn tv-btn-sm ' . $cls . '">' . $i . '</a>';
            }
            if ($paged < $total_pages) echo '<a href="' . $base_link . '&paged=' . ($paged+1) . '" class="tv-btn tv-btn-sm tv-btn-secondary">Next &raquo;</a>';
            ?>
        </div>
    <?php endif; ?>
</div>

<script>
function tvDeleteManualTransaction(pid) {
    if (!confirm('Delete this manual transaction permanently?')) return;
    const fd = new FormData();
    fd.append('action',     'tv_delete_manual_transaction');
    fd.append('nonce',      typeof tvAdmin !== 'undefined' ? tvAdmin.manualSubNonce : '');
    fd.append('payment_id', pid);
    fetch(typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            if (res.success) { location.reload(); }
            else { alert('Error: ' + (res.data || 'Could not delete')); }
        });
}
</script>