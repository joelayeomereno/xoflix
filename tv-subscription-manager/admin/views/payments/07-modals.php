<?php if (!defined('ABSPATH')) { exit; } ?>

<!-- SMART FULFILLMENT MODAL -->
<div id="fulfill-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(2px);">
    <div class="tv-card animate-fade-in" style="width:550px; max-width:95%; margin:0; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
        <div class="tv-card-header" style="justify-content:space-between; background:#fff; border-bottom:1px solid #e2e8f0;">
            <h3 style="font-size:16px;">Fulfill Subscription</h3>
            <button onclick="document.getElementById('fulfill-modal').style.display='none'" style="border:none; background:none; cursor:pointer; font-size:18px; color:#64748b;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        
        <!-- Context Bar -->
        <div id="fulfillment-context-box" style="display:none; padding:10px 24px; background:#dcfce7; color:#166534; font-size:12px; border-bottom:1px solid #bbf7d0;">
            <!-- JS populated -->
        </div>

        <div class="tv-card-body" style="padding:24px;">
            <div style="margin-bottom:20px; font-size:13px;">
                Fulfilling order for user: <strong id="modal-user" style="color:var(--tv-primary);"></strong>
            </div>
            
            <form method="post" action="?page=tv-subs-manager&tab=payments">
                <?php wp_nonce_field('approve_creds_verify'); ?>
                <input type="hidden" name="payment_id" id="modal-pay-id">
                
                <!-- 1. M3U Parser Area -->
                <div style="background:#f0f9ff; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #bae6fd;">
                    <label class="tv-label" style="color:#0369a1; margin-bottom:8px;">1. Paste XTREAM M3U Link (Auto-Parse)</label>
                    <div style="display:flex; gap:10px;">
                        <input type="text" id="raw_m3u" class="tv-input" placeholder="http://host.com/get.php?username=...&password=...">
                        <button type="button" onclick="parseM3U()" class="tv-btn tv-btn-secondary" style="border-color:#bae6fd; color:#0284c7;">Parse</button>
                    </div>
                    <p style="font-size:11px; color:#0c4a6e; margin-top:6px;">Paste a full line link here to auto-fill Username and Password.</p>
                </div>

                <!-- 2. Panel Selector with Smart Mode -->
                <div class="tv-form-group" style="background:#f8fafc; padding:15px; border-radius:8px; border:1px solid #e2e8f0;">
                    <label class="tv-label" style="margin-bottom:10px;">2. Select Panel Config (Optional)</label>
                    
                    <!-- Smart Mode Selector -->
                    <div style="display:flex; gap:15px; margin-bottom:12px; font-size:12px;">
                        <label style="cursor:pointer; display:flex; align-items:center; gap:6px;">
                            <input type="radio" name="panel_mode" value="override" checked onchange="applyPanel()"> 
                            <strong>Override</strong> <span style="color:#64748b;">(Replace Fields)</span>
                        </label>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:6px;">
                            <input type="radio" name="panel_mode" value="attach" onchange="applyPanel()"> 
                            <strong>Attachment</strong> <span style="color:#64748b;">(Add as Extra)</span>
                        </label>
                    </div>

                    <select id="panel_select" name="panel_id" class="tv-input" onchange="applyPanel()">
                        <option value="">-- Manual Entry / Custom --</option>
                        <?php foreach($panels as $p): ?>
                            <option value="<?php echo esc_attr($p['id']); ?>" 
                                    data-xtream="<?php echo esc_attr($p['xtream_url']); ?>" 
                                    data-smart="<?php echo esc_attr($p['smart_tv_url']); ?>">
                                <?php echo esc_html($p['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <p id="panel-hint" style="font-size:11px; color:var(--tv-text-muted); margin-top:6px;">
                        <strong>Override Mode:</strong> Panel URLs will replace the fields below.<br>
                    </p>
                </div>

                <!-- 3. Final Credentials -->
                <div class="tv-row">
                    <div class="tv-col">
                        <label class="tv-label">Username</label>
                        <input type="text" name="cred_user" id="cred_user" class="tv-input" required>
                    </div>
                    <div class="tv-col">
                        <label class="tv-label">Password</label>
                        <input type="text" name="cred_pass" id="cred_pass" class="tv-input" required>
                    </div>
                </div>
                
                <div class="tv-form-group">
                    <label class="tv-label">Smart TV URL (M3U / Portal)</label>
                    <textarea name="cred_m3u" id="cred_m3u" class="tv-input" required rows="4" placeholder="http://line.host.com/get.php?username=...&password=..."></textarea>
                </div>
                
                <div class="tv-form-group">
                    <label class="tv-label">XTREAM Base URL</label>
                    <input type="text" name="cred_url" id="cred_url" class="tv-input" required placeholder="http://line.host.com">
                </div>

                <div style="display:flex; align-items:center; justify-content:space-between; margin-top:24px; border-top:1px solid #e2e8f0; padding-top:16px;">
                    <label class="tv-switch"><input type="checkbox" name="notify_user" value="1" checked class="tv-toggle-input"><span class="tv-toggle-ui" aria-hidden="true"></span><span>Notify user
                    </span></label>

                    <button type="submit" name="confirm_approval_credentials" id="fulfill-submit-btn" class="tv-btn tv-btn-primary" style="padding:10px 24px;">Confirm & Fulfill</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal (Reasons) -->
<div id="reject-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:10000; align-items:center; justify-content:center; backdrop-filter:blur(2px);">
    <div class="tv-card animate-fade-in" style="width:520px; max-width:95%; margin:0; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
        <div class="tv-card-header" style="justify-content:space-between; background:#fff; border-bottom:1px solid #e2e8f0;">
            <h3 style="font-size:16px;">Reject Payment</h3>
            <button onclick="document.getElementById('reject-modal').style.display='none'" style="border:none; background:none; cursor:pointer; font-size:18px; color:#64748b;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="tv-card-body" style="padding:24px;">
            <form method="post" action="?page=tv-subs-manager&tab=payments">
                <?php wp_nonce_field('tv_reject_payment_with_reason_verify'); ?>
                <input type="hidden" name="tv_reject_payment_with_reason" value="1">
                <input type="hidden" name="payment_id" id="reject-pay-id">

                <div class="tv-form-group">
                    <label class="tv-label">Select a reason</label>
                    <select class="tv-input" name="reason_key" required>
                        <option value="unclear_proof">Proof image is unclear / unreadable</option>
                        <option value="wrong_amount">Amount paid does not match the required total</option>
                        <option value="wrong_account">Payment was sent to the wrong account</option>
                        <option value="duplicate_or_used">Proof appears to be duplicate or previously used</option>
                        <option value="invalid_reference">Transaction reference is missing or invalid</option>
                    </select>
                </div>

                <div style="display:flex; align-items:center; justify-content:space-between; margin-top:18px; border-top:1px solid #e2e8f0; padding-top:16px;">
                    <label class="tv-switch"><input type="checkbox" name="notify_user" value="1" checked class="tv-toggle-input"><span class="tv-toggle-ui" aria-hidden="true"></span><span>Notify user (send rejection email)
                    </span></label>

                    <button type="submit" class="tv-btn tv-btn-danger" style="padding:10px 24px;">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================
     MANUAL TRANSACTION MODAL
     ====================================================== -->
<div id="tv-manual-txn-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:10001; align-items:center; justify-content:center; backdrop-filter:blur(3px);">
    <div class="tv-card animate-fade-in" style="width:600px; max-width:96vw; max-height:92vh; overflow-y:auto; margin:0; box-shadow:0 25px 60px rgba(0,0,0,0.3);">
        <div class="tv-card-header" style="justify-content:space-between; background:#fff; border-bottom:1px solid #e2e8f0; position:sticky; top:0; z-index:1;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:40px; height:40px; border-radius:10px; background:rgba(var(--tv-primary-rgb), 0.1); display:flex; align-items:center; justify-content:center; color:var(--tv-primary);">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                </div>
                <div>
                    <h3 style="font-size:16px; margin:0;">Add Manual Transaction</h3>
                    <p style="font-size:11px; color:#64748b; margin:2px 0 0;">Manually record a payment and create a subscription.</p>
                </div>
            </div>
            <button type="button" onclick="tvCloseManualTxn()" style="border:none; background:none; cursor:pointer; font-size:20px; color:#64748b; line-height:1; padding: 10px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        <div style="padding:22px;">
            <!-- Step indicator -->
            <div style="display:flex; gap:0; margin-bottom:22px; border-radius:10px; overflow:hidden; border:1px solid #e2e8f0;">
                <div id="mtx-step-1-tab" style="flex:1; padding:10px; text-align:center; font-size:12px; font-weight:700; background:#6366f1; color:#fff; cursor:pointer;">1 &middot; Select User &amp; Plan</div>
                <div id="mtx-step-2-tab" style="flex:1; padding:10px; text-align:center; font-size:12px; font-weight:700; background:#f1f5f9; color:#94a3b8; cursor:not-allowed;">2 &middot; Payment Details</div>
            </div>

            <!-- STEP 1 -->
            <div id="mtx-step-1">
                <div class="tv-form-group" style="margin-bottom:16px;">
                    <label class="tv-label">User <span style="color:#ef4444;">*</span></label>
                    <div style="position:relative;">
                        <input type="text" id="mtx-user-search" class="tv-input" placeholder="Type name or email to search..." autocomplete="off"
                               oninput="tvMtxSearchUsers(this.value)">
                        <div id="mtx-user-dropdown" style="display:none; position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #e2e8f0; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,0.12); z-index:999; max-height:200px; overflow-y:auto;"></div>
                    </div>
                    <div id="mtx-user-selected" style="display:none; margin-top:8px; padding:10px 12px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; font-size:13px; color:#166534; display:flex; align-items:center; justify-content:space-between;">
                        <span id="mtx-user-label" style="font-weight:700;"></span>
                        <button type="button" onclick="tvMtxClearUser()" style="border:none; background:none; color:#ef4444; cursor:pointer; font-size:16px; padding: 4px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>
                    <input type="hidden" id="mtx-user-id">
                </div>

                <div class="tv-form-group" style="margin-bottom:16px;">
                    <label class="tv-label">Plan <span style="color:#ef4444;">*</span></label>
                    <select id="mtx-plan-select" class="tv-input" onchange="tvMtxOnPlanChange()">
                        <option value="">-- Loading plans... --</option>
                    </select>
                </div>

                <!-- MONTHS SELECTION -->
                <div class="tv-form-group" style="margin-bottom:16px;">
                    <label class="tv-label">Quantity (Months) <span style="color:#ef4444;">*</span></label>
                    <input type="number" id="mtx-quantity" class="tv-input" value="1" min="1" oninput="tvMtxOnPlanChange()">
                </div>

                <div id="mtx-plan-info" style="display:none; padding:12px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:16px;">
                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px;">
                        <div>
                            <div style="font-size:10px; font-weight:700; text-transform:uppercase; color:#64748b; margin-bottom:3px;">Unit Price</div>
                            <div id="mtx-plan-price" style="font-size:16px; font-weight:900; color:#0f172a;"></div>
                        </div>
                        <div>
                            <div style="font-size:10px; font-weight:700; text-transform:uppercase; color:#64748b; margin-bottom:3px;">Total Duration</div>
                            <div id="mtx-plan-duration" style="font-size:16px; font-weight:900; color:#0f172a;"></div>
                        </div>
                        <div>
                            <div style="font-size:10px; font-weight:700; text-transform:uppercase; color:#64748b; margin-bottom:3px;">Sub Ends</div>
                            <div id="mtx-plan-enddate" style="font-size:13px; font-weight:700; color:#6366f1;"></div>
                        </div>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; margin-top:8px;">
                    <button type="button" id="mtx-next-btn" onclick="tvMtxGoStep2()" class="tv-btn tv-btn-primary" disabled style="display:flex; align-items:center; gap:8px;">
                        Next: Payment Details 
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </button>
                </div>
            </div><!-- /step 1 -->

            <!-- STEP 2 -->
            <div id="mtx-step-2" style="display:none;">
                <!-- Selected plan summary bar -->
                <div id="mtx-summary-bar" style="padding:10px 14px; background:#f0f9ff; border:1px solid #bae6fd; border-radius:10px; margin-bottom:18px; font-size:12px; color:#0369a1; font-weight:600;"></div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                    <div>
                        <label class="tv-label">Amount Charged <span style="color:#ef4444;">*</span></label>
                        <input type="number" id="mtx-amount" class="tv-input" placeholder="0.00" step="0.01" min="0" oninput="tvMtxUpdateAmountPreview()">
                    </div>
                    <div>
                        <label class="tv-label">Currency</label>
                        <select id="mtx-currency" class="tv-input" onchange="tvMtxUpdateAmountPreview()">
                            <option value="USD">USD &ndash; US Dollar</option>
                            <option value="NGN">NGN &ndash; Nigerian Naira</option>
                            <option value="GHS">GHS &ndash; Ghanaian Cedi</option>
                            <option value="KES">KES &ndash; Kenyan Shilling</option>
                            <option value="ZAR">ZAR &ndash; South African Rand</option>
                            <option value="GBP">GBP &ndash; British Pound</option>
                            <option value="EUR">EUR &ndash; Euro</option>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                    <div>
                        <label class="tv-label">Discount Applied</label>
                        <input type="number" id="mtx-discount" class="tv-input" placeholder="0.00" step="0.01" min="0" value="0" oninput="tvMtxUpdateAmountPreview()">
                    </div>
                    <div>
                        <label class="tv-label">Coupon Code</label>
                        <input type="text" id="mtx-coupon" class="tv-input" placeholder="Optional">
                    </div>
                </div>

                <div style="padding:10px 14px; background:#fafafa; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:14px; font-size:13px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:#64748b; font-weight:600;">Gross (before discount)</span>
                        <span id="mtx-gross-preview" style="font-weight:800; color:#0f172a;"></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                        <span style="color:#64748b; font-weight:600;">You are recording</span>
                        <span id="mtx-net-preview" style="font-weight:900; color:#6366f1; font-size:15px;"></span>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                    <div>
                        <label class="tv-label">Payment Method</label>
                        <select id="mtx-method" class="tv-input">
                            <option value="Manual">Manual</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cash">Cash</option>
                            <option value="Card">Card</option>
                            <option value="Crypto">Crypto</option>
                            <option value="Mobile Money">Mobile Money</option>
                        </select>
                    </div>
                    <div>
                        <label class="tv-label">Transaction Date</label>
                        <input type="date" id="mtx-date" class="tv-input" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div id="mtx-error" style="display:none; padding:10px 14px; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; color:#dc2626; font-size:13px; margin-bottom:12px;"></div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px; border-top:1px solid #e2e8f0; padding-top:16px;">
                    <button type="button" onclick="tvMtxGoStep1()" class="tv-btn tv-btn-secondary" style="display:flex; align-items:center; gap:8px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        Back
                    </button>
                    <button type="button" id="mtx-save-btn" onclick="tvMtxSave()" class="tv-btn tv-btn-primary" style="padding:10px 28px; display:flex; align-items:center; gap:8px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Save Transaction
                    </button>
                </div>
            </div><!-- /step 2 -->
        </div>
    </div>
</div><!-- /tv-manual-txn-modal -->

<!-- User Detail Popup — overlay covers full viewport via 100vw/100vh to beat WP sidebar offset -->
<div id="tv-user-popup" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(8,14,26,0.6);display:none;align-items:center;justify-content:center;padding:24px;box-sizing:border-box;z-index:999999;backdrop-filter:blur(4px);">
    <div id="tv-user-popup-inner" style="width:100%;max-width:480px;max-height:calc(100vh - 48px);background:var(--tv-surface);border:1px solid var(--tv-border);border-radius:20px;box-shadow:0 24px 60px rgba(0,0,0,0.4);overflow-y:auto;padding:22px;box-sizing:border-box;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:16px;">
            <div>
                <div style="font-size:10px; color:var(--tv-text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:3px;">User Details</div>
                <div id="tv-user-popup-title" style="font-size:17px; font-weight:900; color:var(--tv-text);"></div>
            </div>
            <button type="button" class="tv-btn tv-btn-secondary" id="tv-user-popup-close" style="flex-shrink:0; display:flex; align-items:center; gap:6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                Close
            </button>
        </div>
        <div style="display:grid; gap:8px;">
            <?php
            $copy_row = function ($label, $id) {
                echo '<div style="display:flex; gap:10px; align-items:center; justify-content:space-between; padding:10px 12px; border:1px solid var(--tv-border); border-radius:10px; background:var(--tv-surface-active);">'
                    .'<div style="min-width:0; flex:1;">'
                        .'<div style="font-size:10px; color:var(--tv-text-muted); font-weight:700; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">'.esc_html($label).'</div>'
                        .'<div id="'.esc_attr($id).'" style="font-family:ui-monospace,monospace; font-size:13px; word-break:break-all; color:var(--tv-text);"></div>'
                    .'</div>'
                    .'<button type="button" class="tv-btn tv-btn-primary tv-btn-sm" data-tv-copy-target="'.esc_attr($id).'" style="flex-shrink:0;">Copy</button>'
                .'</div>';
            };
            $copy_row('Full Name',   'tv-user-popup-name');
            $copy_row('Email',       'tv-user-popup-email');
            $copy_row('Phone',       'tv-user-popup-phone');
            $copy_row('Connections', 'tv-user-popup-connections');
            ?>
        </div>
    </div>
</div>
