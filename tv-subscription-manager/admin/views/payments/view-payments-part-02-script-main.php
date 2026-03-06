<?php if (!defined('ABSPATH')) exit; ?>
                                data-cred-user="<?php echo esc_attr($pay->credential_user ?? ''); ?>"
                                data-cred-pass="<?php echo esc_attr($pay->credential_pass ?? ''); ?>"
                                data-cred-url="<?php echo esc_attr($pay->credential_url ?? ''); ?>"
                                data-cred-m3u="<?php echo esc_attr($pay->credential_m3u ?? ''); ?>"
                                class="tv-btn tv-btn-primary tv-btn-sm">
                                Fulfill
                            </button>
                        <?php else: ?>
                            <span style="color:var(--tv-text-muted);">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="8" style="text-align:center; padding:30px; color:var(--tv-text-muted);">No transactions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
        <div class="tv-toolbar" style="justify-content:center; border-top:1px solid var(--tv-border); border-bottom:none; border-radius:0 0 12px 12px;">
            <?php 
                $base_link = "?page=tv-subs-manager&tab=payments&s=".esc_attr($search_term)."&status=".esc_attr($filter_status);
                
                if($paged > 1) echo '<a href="'.$base_link.'&paged='.($paged-1).'" class="tv-btn tv-btn-sm tv-btn-secondary">« Prev</a>';
                
                for($i=max(1, $paged-2); $i<=min($total_pages, $paged+2); $i++) {
                    $cls = ($i == $paged) ? 'tv-btn-primary' : 'tv-btn-secondary';
                    echo '<a href="'.$base_link.'&paged='.$i.'" class="tv-btn tv-btn-sm '.$cls.'">'.$i.'</a>';
                }

                if($paged < $total_pages) echo '<a href="'.$base_link.'&paged='.($paged+1).'" class="tv-btn tv-btn-sm tv-btn-secondary">Next »</a>';
            ?>
        </div>
    <?php endif; ?>
</div>

<!-- SMART FULFILLMENT MODAL -->
<div id="fulfill-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(2px);">
    <div class="tv-card animate-fade-in" style="width:550px; max-width:95%; margin:0; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
        <div class="tv-card-header" style="justify-content:space-between; background:#fff; border-bottom:1px solid #e2e8f0;">
            <h3 style="font-size:16px;">Fulfill Subscription</h3>
            <button onclick="document.getElementById('fulfill-modal').style.display='none'" style="border:none; background:none; cursor:pointer; font-size:18px; color:#64748b;">&times;</button>
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
            <button onclick="document.getElementById('reject-modal').style.display='none'" style="border:none; background:none; cursor:pointer; font-size:18px; color:#64748b;">&times;</button>
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

<script>
    // -----------------------------------------------------
    // CURRENCY TOGGLE LOGIC (USD <-> NGN)
    // -----------------------------------------------------
    function tvToggleCurrency(curr) {
        // Toggle Buttons UI
        document.getElementById('tv-curr-usd').style.background = (curr === 'USD') ? 'white' : 'transparent';
        document.getElementById('tv-curr-usd').style.color = (curr === 'USD') ? 'var(--tv-text)' : 'var(--tv-text-muted)';
        
        document.getElementById('tv-curr-ngn').style.background = (curr === 'NGN') ? 'white' : 'transparent';
        document.getElementById('tv-curr-ngn').style.color = (curr === 'NGN') ? 'var(--tv-text)' : 'var(--tv-text-muted)';

        // Toggle Column Values
        const usdEls = document.querySelectorAll('.tv-val-usd');
        const ngnEls = document.querySelectorAll('.tv-val-ngn');

        if (curr === 'USD') {
            usdEls.forEach(el => el.style.display = 'inline');
            ngnEls.forEach(el => el.style.display = 'none');
        } else {
            usdEls.forEach(el => el.style.display = 'none');
            ngnEls.forEach(el => el.style.display = 'inline');
        }
    }

    // -----------------------------------------------------
    // FULFILLMENT & PANEL LOGIC
    // -----------------------------------------------------
    function openFulfillModal(btn) {
        let id, user, cUser, cPass, cUrl, cM3u;
        
        if (typeof btn === 'object' && btn.dataset) {
            id = btn.dataset.id;
            user = btn.dataset.user;
            cUser = btn.dataset.credUser || '';
            cPass = btn.dataset.credPass || '';
            cUrl = btn.dataset.credUrl || '';
            cM3u = btn.dataset.credM3u || '';
        } else {
            id = arguments[0];
            user = arguments[1];
            cUser = ''; cPass = ''; cUrl = ''; cM3u = '';
        }

        document.getElementById('modal-pay-id').value = id;
        document.getElementById('modal-user').innerText = user;
        
        document.getElementById('raw_m3u').value = '';
        document.getElementById('cred_user').value = cUser;
        document.getElementById('cred_pass').value = cPass;
        document.getElementById('cred_url').value = cUrl;
        document.getElementById('cred_m3u').value = cM3u;
        
        const panelSelect = document.getElementById('panel_select');
        panelSelect.selectedIndex = 0; 
        
        let matchedPanel = false;
        if (cUrl) {
            const cleanCredUrl = cUrl.toLowerCase().replace(/\/$/, '');
            for (let i = 0; i < panelSelect.options.length; i++) {
                const opt = panelSelect.options[i];
                const pUrl = (opt.dataset.xtream || '').toLowerCase().replace(/\/$/, '');
                if (pUrl && pUrl === cleanCredUrl) {
                    panelSelect.selectedIndex = i;
                    matchedPanel = true;
                    break;
                }
            }
        }