<script>
// -- CURRENCY TOGGLE --
function tvToggleCurrency(curr) {
    const usdBtn = document.getElementById('tv-curr-usd');
    const ngnBtn = document.getElementById('tv-curr-ngn');
    if (usdBtn) { usdBtn.style.background = (curr==='USD') ? 'var(--tv-card)' : 'transparent'; usdBtn.style.color = (curr==='USD') ? 'var(--tv-text)' : 'var(--tv-text-muted)'; }
    if (ngnBtn) { ngnBtn.style.background = (curr==='NGN') ? 'var(--tv-card)' : 'transparent'; ngnBtn.style.color = (curr==='NGN') ? 'var(--tv-text)' : 'var(--tv-text-muted)'; }
    document.querySelectorAll('.tv-val-usd').forEach(el => el.style.display = (curr==='USD') ? 'inline' : 'none');
    document.querySelectorAll('.tv-val-ngn').forEach(el => el.style.display = (curr==='NGN') ? 'inline' : 'none');
}

// -- FULFILLMENT MODAL --
function openFulfillModal(btn) {
    let id, user, cUser, cPass, cUrl, cM3u;
    if (typeof btn === 'object' && btn.dataset) {
        id    = btn.dataset.id;    user  = btn.dataset.user;
        cUser = btn.dataset.credUser || ''; cPass = btn.dataset.credPass || '';
        cUrl  = btn.dataset.credUrl  || ''; cM3u  = btn.dataset.credM3u  || '';
    } else {
        id = arguments[0]; user = arguments[1]; cUser=''; cPass=''; cUrl=''; cM3u='';
    }
    document.getElementById('modal-pay-id').value  = id;
    document.getElementById('modal-user').innerText = user;
    document.getElementById('raw_m3u').value  = '';
    document.getElementById('cred_user').value = cUser;
    document.getElementById('cred_pass').value = cPass;
    document.getElementById('cred_url').value  = cUrl;
    document.getElementById('cred_m3u').value  = cM3u;
    const panelSelect = document.getElementById('panel_select');
    panelSelect.selectedIndex = 0;
    let matchedPanel = false;
    if (cUrl) {
        const cleanCredUrl = cUrl.toLowerCase().replace(/\/$/, '');
        for (let i=0; i<panelSelect.options.length; i++) {
            const opt = panelSelect.options[i];
            const pUrl = (opt.dataset.xtream||'').toLowerCase().replace(/\/$/, '');
            if (pUrl && pUrl === cleanCredUrl) { panelSelect.selectedIndex = i; matchedPanel = true; break; }
        }
    }
    if (cM3u.includes('[Panel:')) {
        document.querySelector('input[name="panel_mode"][value="attach"]').checked = true;
    } else {
        document.querySelector('input[name="panel_mode"][value="override"]').checked = true;
    }
    const contextBox = document.getElementById('fulfillment-context-box');
    const submitBtn  = document.getElementById('fulfill-submit-btn');
    ['cred_user','cred_pass','cred_url'].forEach(fid => {
        const el = document.getElementById(fid);
        el.style.borderColor = el.value ? '#10b981' : '';
        el.style.backgroundColor = el.value ? '#ecfdf5' : '';
    });
    if (cUser && cPass) {
        const modeLabel = matchedPanel ? "Panel detected & selected." : "Using existing host.";
        contextBox.innerHTML = `<span class="dashicons dashicons-yes" style="margin-right:4px;"></span> <strong>Credentials Preserved.</strong> ${modeLabel} &mdash; This is an <strong>Extension/Renewal</strong>.`;
        contextBox.style.display = 'block'; contextBox.style.background = '#dcfce7'; contextBox.style.color = '#166534';
        submitBtn.innerText = "Confirm Extension";
        submitBtn.classList.remove('tv-btn-primary'); submitBtn.style.backgroundColor = '#059669'; submitBtn.style.color = '#fff';
    } else {
        contextBox.style.display = 'none';
        submitBtn.innerText = "Confirm & Fulfill";
        submitBtn.classList.add('tv-btn-primary'); submitBtn.style.backgroundColor = ''; submitBtn.style.color = '';
    }
    document.getElementById('fulfill-modal').style.display = 'flex';
}

function openRejectModal(id) {
    if (!window.confirm('Are you sure you want to reject this transaction?')) return;
    document.getElementById('reject-pay-id').value = id;
    document.getElementById('reject-modal').style.display = 'flex';
}

function parseM3U() {
    const raw = document.getElementById('raw_m3u').value;
    if (!raw) return;
    const userMatch = raw.match(/username=([^&]+)/);
    const passMatch = raw.match(/password=([^&]+)/);
    if (userMatch) document.getElementById('cred_user').value = userMatch[1];
    if (passMatch) document.getElementById('cred_pass').value = passMatch[1];
    try {
        const urlObj = new URL(raw);
        if (!document.getElementById('cred_url').value) document.getElementById('cred_url').value = urlObj.origin;
        if (!document.getElementById('cred_m3u').value) document.getElementById('cred_m3u').value = raw;
    } catch(e) {}
    if (document.getElementById('panel_select').value) applyPanel();
}

function applyPanel() {
    const sel  = document.getElementById('panel_select');
    const opt  = sel.options[sel.selectedIndex];
    const user = document.getElementById('cred_user').value;
    const pass = document.getElementById('cred_pass').value;
    const mode = document.querySelector('input[name="panel_mode"]:checked').value;
    const hint = document.getElementById('panel-hint');
    if (mode==='attach') { hint.innerHTML = '<strong>Attachment Mode:</strong> Panel URLs are appended to the M3U field.'; }
    else { hint.innerHTML = '<strong>Override Mode:</strong> Panel URLs will replace the fields below.'; }
    if (opt.value) {
        let smart  = opt.getAttribute('data-smart');
        let xtream = opt.getAttribute('data-xtream');
        if (mode==='override') {
            document.getElementById('cred_url').value = xtream;
            if (smart.includes('get.php') || smart.includes('username=')) {
                try {
                    let u = new URL(smart);
                    if (user) u.searchParams.set('username', user);
                    if (pass) u.searchParams.set('password', pass);
                    document.getElementById('cred_m3u').value = u.toString();
                } catch(e) { document.getElementById('cred_m3u').value = smart; }
            } else { document.getElementById('cred_m3u').value = smart; }
        } else if (mode==='attach') {
            let attachmentLink = smart;
            if ((smart.includes('get.php')||smart.includes('username=')) && user && pass) {
                try {
                    let u = new URL(smart);
                    u.searchParams.set('username', user); u.searchParams.set('password', pass);
                    attachmentLink = u.toString();
                } catch(e) {}
            }
            const currentM3u = document.getElementById('cred_m3u').value;
            if (!currentM3u.includes(attachmentLink)) {
                document.getElementById('cred_m3u').value = currentM3u ? currentM3u + '\n\n[Panel: ' + attachmentLink + ']' : attachmentLink;
            }
        }
    }
}
</script>

<!-- ================================================================
     USER DETAIL POPUP  —  CENTERING FIX
     Root cause: WordPress admin wraps page content inside #wpcontent
     which has margin-left equal to the sidebar width. A naive
     position:fixed;inset:0 element is placed relative to that column,
     not the true viewport, so it appears off-centre.
     Fix: force width:100vw; height:100vh; left:0; top:0 with
     !important, and use a CSS class toggle (.is-open) instead of
     toggling inline display, so the flex centring rule always wins.
     ================================================================ -->
<style>
#tv-user-popup {
    position: fixed !important;
    top:    0 !important;
    left:   0 !important;
    right:  0 !important;
    bottom: 0 !important;
    width:  100vw !important;
    height: 100vh !important;
    margin: 0 !important;
    padding: 20px;
    box-sizing: border-box;
    background: rgba(8, 14, 26, 0.72);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 999999 !important;
    backdrop-filter: blur(4px);
}
#tv-user-popup.is-open {
    display: flex !important;
}
#tv-user-popup-inner {
    background: var(--tv-surface);
    border: 1px solid var(--tv-border);
    border-radius: 20px;
    padding: 24px;
    width: 100%;
    max-width: 460px;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
    animation: tvModalIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    max-height: calc(100vh - 40px);
    overflow-y: auto;
}
</style>

<div id="tv-user-popup">
    <div id="tv-user-popup-inner">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px;">
            <div>
                <div style="font-size:10px;color:var(--tv-text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">User Details</div>
                <div id="tv-user-popup-title" style="font-size:17px;font-weight:900;color:var(--tv-text);"></div>
            </div>
            <button type="button" id="tv-user-popup-close"
                    style="background:var(--tv-surface-active);border:1px solid var(--tv-border);border-radius:8px;padding:6px 14px;cursor:pointer;color:var(--tv-text);font-size:12px;font-weight:600;white-space:nowrap;">
                &#10005; Close
            </button>
        </div>
        <div style="display:grid;gap:8px;">
            <?php
            $copy_row = function($label, $id) {
                echo '<div style="display:flex;gap:10px;align-items:center;justify-content:space-between;padding:10px 12px;background:var(--tv-surface-active);border:1px solid var(--tv-border);border-radius:10px;">'
                    .'<div style="min-width:0;flex:1;">'
                        .'<div style="font-size:10px;color:var(--tv-text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:2px;">'.esc_html($label).'</div>'
                        .'<div id="'.esc_attr($id).'" style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:13px;word-break:break-all;color:var(--tv-text);"></div>'
                    .'</div>'
                    .'<button type="button" class="tv-btn tv-btn-primary tv-btn-sm" data-tv-copy-target="'.esc_attr($id).'" style="flex-shrink:0;">Copy</button>'
                .'</div>';
            };
            $copy_row('Full Name',   'tv-user-popup-name');
            $copy_row('Email',       'tv-user-popup-email');
            $copy_row('Phone',       'tv-user-popup-phone');
            $copy_row('Plan',        'tv-user-popup-plan');
            $copy_row('Connections', 'tv-user-popup-connections');
            ?>
        </div>
    </div>
</div>

<script>
(function(){
    var overlay  = document.getElementById('tv-user-popup');
    if (!overlay) return;
    var title    = document.getElementById('tv-user-popup-title');
    var nameEl   = document.getElementById('tv-user-popup-name');
    var emailEl  = document.getElementById('tv-user-popup-email');
    var phoneEl  = document.getElementById('tv-user-popup-phone');
    var planEl   = document.getElementById('tv-user-popup-plan');
    var connEl   = document.getElementById('tv-user-popup-connections');
    var closeBtn = document.getElementById('tv-user-popup-close');

    function openPopup(data) {
        title.textContent   = data.name  || '—';
        nameEl.textContent  = data.name  || '—';
        emailEl.textContent = data.email || '—';
        phoneEl.textContent = data.phone || '—';
        planEl.textContent  = data.plan  || '—';
        connEl.textContent  = String(data.connections || '1');
        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }
    function closePopup() {
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    document.addEventListener('click', function(e) {
        var trigger = e.target.closest('.tv-user-popup-trigger');
        if (trigger) {
            e.preventDefault();
            openPopup({
                name:        trigger.getAttribute('data-tv-user-name'),
                email:       trigger.getAttribute('data-tv-user-email'),
                phone:       trigger.getAttribute('data-tv-user-phone'),
                plan:        trigger.getAttribute('data-tv-user-plan'),
                connections: trigger.getAttribute('data-tv-user-connections'),
            });
            return;
        }
        var copyBtn = e.target.closest('[data-tv-copy-target]');
        if (copyBtn) {
            var id  = copyBtn.getAttribute('data-tv-copy-target');
            var el  = document.getElementById(id);
            if (!el) return;
            var txt = el.textContent || '';
            if (navigator.clipboard) {
                navigator.clipboard.writeText(txt);
            } else {
                var ta = document.createElement('textarea');
                ta.value = txt; ta.style.cssText = 'position:fixed;opacity:0;';
                document.body.appendChild(ta); ta.select();
                try { document.execCommand('copy'); } catch(x) {}
                document.body.removeChild(ta);
            }
            var orig = copyBtn.textContent;
            copyBtn.textContent = 'Copied!';
            copyBtn.style.background = '#10b981';
            setTimeout(function(){ copyBtn.textContent = orig; copyBtn.style.background = ''; }, 1500);
            return;
        }
        if (e.target === overlay) closePopup();
    });

    closeBtn && closeBtn.addEventListener('click', closePopup);
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closePopup(); });
})();
</script>

<!-- MANUAL ADD SUBSCRIPTION MODAL -->
<div id="tv-manual-add-modal" style="position:fixed;inset:0;background:rgba(8,14,26,0.7);display:none;align-items:center;justify-content:center;padding:20px;z-index:99998;backdrop-filter:blur(4px);">
    <div style="background:var(--tv-surface);border:1px solid var(--tv-border);border-radius:20px;width:100%;max-width:560px;box-shadow:0 24px 60px rgba(0,0,0,0.4);animation:tvModalIn 0.25s cubic-bezier(0.16,1,0.3,1);overflow:hidden;">
        <div style="padding:18px 22px;border-bottom:1px solid var(--tv-border);display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,rgba(99,102,241,0.05),transparent);">
            <div>
                <div style="font-size:16px;font-weight:800;color:var(--tv-text);">
                    <span class="dashicons dashicons-plus-alt2" style="margin-right:6px;color:var(--tv-primary);"></span>Add Manual Subscription
                </div>
                <div style="font-size:12px;color:var(--tv-text-muted);margin-top:2px;">Create a subscription manually for any user</div>
            </div>
            <button type="button" onclick="tvCloseManualAddModal()" style="background:var(--tv-surface-active);border:1px solid var(--tv-border);border-radius:8px;padding:6px 14px;cursor:pointer;color:var(--tv-text);font-size:12px;font-weight:600;">&#10005; Close</button>
        </div>
        <div style="padding:22px;max-height:70vh;overflow-y:auto;">
            <div style="margin-bottom:16px;">
                <label class="tv-label"><span style="color:var(--tv-primary);font-weight:800;">&#9312;</span> Select User</label>
                <input type="text" id="man-user-search" class="tv-input" placeholder="Type name or email to search..." oninput="tvSearchManualUsers(this.value)">
                <div id="man-user-results" style="margin-top:4px;border:1px solid var(--tv-border);border-radius:10px;overflow:hidden;display:none;max-height:150px;overflow-y:auto;background:var(--tv-surface);"></div>
                <input type="hidden" id="man-user-id" value="">
                <div id="man-user-selected" style="display:none;margin-top:8px;padding:8px 12px;background:rgba(var(--tv-primary-rgb),0.08);border:1px solid rgba(var(--tv-primary-rgb),0.2);border-radius:8px;font-size:13px;color:var(--tv-primary);font-weight:600;"></div>
            </div>
            <div style="margin-bottom:16px;">
                <label class="tv-label"><span style="color:var(--tv-primary);font-weight:800;">&#9313;</span> Select Plan</label>
                <select id="man-plan-select" class="tv-input" onchange="tvUpdateManualTotal()">
                    <option value="">-- Loading plans... --</option>
                </select>
            </div>
            <div style="margin-bottom:16px;">
                <label class="tv-label"><span style="color:var(--tv-primary);font-weight:800;">&#9314;</span> Duration</label>
                <select id="man-duration" class="tv-input" onchange="tvUpdateManualTotal()">
                    <option value="1">1 Month</option>
                    <option value="2">2 Months</option>
                    <option value="3">3 Months</option>
                    <option value="6">6 Months</option>
                    <option value="12">12 Months (1 Year)</option>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                <div>
                    <label class="tv-label"><span style="color:var(--tv-primary);font-weight:800;">&#9315;</span> Payment Method</label>
                    <select id="man-method" class="tv-input">
                        <option value="Manual">Manual Entry</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cash">Cash</option>
                        <option value="Crypto">Crypto</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="tv-label">Currency</label>
                    <select id="man-currency" class="tv-input" onchange="tvUpdateManualTotal()">
                        <option value="USD">USD ($)</option>
                        <option value="NGN">NGN (&#8358;)</option>
                        <option value="GBP">GBP (&pound;)</option>
                        <option value="EUR">EUR (&euro;)</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom:16px;">
                <label class="tv-label">Coupon Code (optional)</label>
                <div style="display:flex;gap:8px;">
                    <input type="text" id="man-coupon" class="tv-input" placeholder="Enter coupon code...">
                    <button type="button" class="tv-btn tv-btn-secondary" onclick="tvApplyManualCoupon()" style="flex-shrink:0;">Apply</button>
                </div>
                <div id="man-coupon-msg" style="font-size:12px;margin-top:4px;"></div>
            </div>
            <div style="padding:16px;background:linear-gradient(135deg,rgba(var(--tv-primary-rgb),0.06),rgba(var(--tv-primary-rgb),0.02));border:1px solid rgba(var(--tv-primary-rgb),0.15);border-radius:12px;margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <div style="font-size:11px;font-weight:700;color:var(--tv-text-muted);text-transform:uppercase;letter-spacing:0.05em;">Total Amount</div>
                        <div id="man-total-display" style="font-size:24px;font-weight:900;color:var(--tv-primary);">$0.00</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:11px;color:var(--tv-text-muted);">Override amount:</div>
                        <input type="number" id="man-amount" class="tv-input" style="width:130px;margin-top:4px;font-weight:700;text-align:right;font-size:15px;" step="0.01" min="0" placeholder="0.00" oninput="tvManualAmountChanged()">
                    </div>
                </div>
            </div>
            <div style="margin-bottom:6px;">
                <label class="tv-label">Internal Notes (optional)</label>
                <textarea id="man-notes" class="tv-input" rows="2" placeholder="Admin notes for this transaction..."></textarea>
            </div>
        </div>
        <div style="padding:16px 22px;border-top:1px solid var(--tv-border);display:flex;justify-content:space-between;align-items:center;gap:12px;background:var(--tv-surface-active);">
            <div id="man-result-msg" style="font-size:13px;"></div>
            <div style="display:flex;gap:10px;">
                <button type="button" class="tv-btn tv-btn-secondary" onclick="tvCloseManualAddModal()">Cancel</button>
                <button type="button" class="tv-btn tv-btn-primary" id="man-submit-btn" onclick="tvSubmitManualSubscription()">
                    <span class="dashicons dashicons-yes-alt" style="font-size:16px;width:16px;height:16px;margin-right:4px;"></span>
                    Create Subscription
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var tvManualPlans = [];
var tvManualUserData = null;
var tvManualCouponDiscount = 0;

function tvOpenManualAddModal() {
    document.getElementById('tv-manual-add-modal').style.display = 'flex';
    if (tvManualPlans.length === 0) tvLoadManualPlans();
}
function tvCloseManualAddModal() {
    document.getElementById('tv-manual-add-modal').style.display = 'none';
    document.getElementById('man-result-msg').innerHTML = '';
}
function tvLoadManualPlans() {
    const select = document.getElementById('man-plan-select');
    const fd = new FormData();
    fd.append('action', 'tv_get_plans_for_manual');
    fd.append('nonce',  typeof tvAdmin !== 'undefined' ? tvAdmin.manualSubNonce : '');
    fetch(typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            if (res.success) {
                tvManualPlans = res.data;
                select.innerHTML = '<option value="">-- Select a plan --</option>';
                res.data.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.name + ' ($' + parseFloat(p.price).toFixed(2) + '/mo)';
                    opt.dataset.price = p.price;
                    select.appendChild(opt);
                });
            }
        }).catch(() => { select.innerHTML = '<option value="">Error loading plans</option>'; });
}
var tvUserSearchTimer = null;
function tvSearchManualUsers(q) {
    clearTimeout(tvUserSearchTimer);
    const results = document.getElementById('man-user-results');
    if (q.length < 2) { results.style.display = 'none'; return; }
    tvUserSearchTimer = setTimeout(function() {
        const fd = new FormData();
        fd.append('action', 'tv_get_users_for_manual');
        fd.append('nonce',  typeof tvAdmin !== 'undefined' ? tvAdmin.manualSubNonce : '');
        fd.append('search', q);
        fetch(typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php', { method: 'POST', body: fd })
            .then(r => r.json()).then(res => {
                if (res.success && res.data.length) {
                    results.innerHTML = '';
                    res.data.forEach(u => {
                        const div = document.createElement('div');
                        div.style.cssText = 'padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--tv-border);';
                        div.textContent = u.label;
                        div.addEventListener('mouseenter', function() { this.style.background = 'rgba(var(--tv-primary-rgb),0.05)'; });
                        div.addEventListener('mouseleave', function() { this.style.background = ''; });
                        div.addEventListener('click', function() {
                            document.getElementById('man-user-id').value = u.id;
                            document.getElementById('man-user-search').value = '';
                            document.getElementById('man-user-selected').textContent = '\u2713 ' + u.label;
                            document.getElementById('man-user-selected').style.display = 'block';
                            results.style.display = 'none';
                            tvManualUserData = u;
                        });
                        results.appendChild(div);
                    });
                    results.style.display = 'block';
                } else {
                    results.innerHTML = '<div style="padding:10px 14px;font-size:13px;color:var(--tv-text-muted);">No users found.</div>';
                    results.style.display = 'block';
                }
            });
    }, 300);
}
function tvUpdateManualTotal() {
    const planSel  = document.getElementById('man-plan-select');
    const opt      = planSel.options[planSel.selectedIndex];
    const price    = parseFloat(opt ? opt.dataset.price || 0 : 0);
    const months   = parseInt(document.getElementById('man-duration').value || 1);
    const currency = document.getElementById('man-currency').value;
    let total = Math.max(0, price * months - tvManualCouponDiscount);
    const sym = currency==='NGN' ? '\u20a6' : currency==='GBP' ? '\u00a3' : currency==='EUR' ? '\u20ac' : '$';
    document.getElementById('man-total-display').textContent = sym + total.toFixed(2);
    document.getElementById('man-amount').value = total.toFixed(2);
}
function tvManualAmountChanged() {
    const currency = document.getElementById('man-currency').value;
    const amount   = parseFloat(document.getElementById('man-amount').value || 0);
    const sym = currency==='NGN' ? '\u20a6' : currency==='GBP' ? '\u00a3' : currency==='EUR' ? '\u20ac' : '$';
    document.getElementById('man-total-display').textContent = sym + amount.toFixed(2);
}
function tvApplyManualCoupon() {
    const code  = document.getElementById('man-coupon').value.trim();
    const msgEl = document.getElementById('man-coupon-msg');
    if (!code) { msgEl.textContent = ''; return; }
    msgEl.innerHTML = '<span style="color:var(--tv-text-muted);font-style:italic;">Coupon "' + code + '" noted. Manual discount not auto-applied \u2014 adjust the amount field.</span>';
}
function tvSubmitManualSubscription() {
    const userId   = document.getElementById('man-user-id').value;
    const planId   = document.getElementById('man-plan-select').value;
    const duration = document.getElementById('man-duration').value;
    const method   = document.getElementById('man-method').value;
    const currency = document.getElementById('man-currency').value;
    const amount   = document.getElementById('man-amount').value;
    const coupon   = document.getElementById('man-coupon').value;
    const notes    = document.getElementById('man-notes').value;
    const msgEl    = document.getElementById('man-result-msg');
    if (!userId) { msgEl.innerHTML = '<span style="color:var(--tv-danger);">\u26a0 Please select a user.</span>'; return; }
    if (!planId) { msgEl.innerHTML = '<span style="color:var(--tv-danger);">\u26a0 Please select a plan.</span>'; return; }
    if (!amount || parseFloat(amount) < 0) { msgEl.innerHTML = '<span style="color:var(--tv-danger);">\u26a0 Please enter a valid amount.</span>'; return; }
    const btn = document.getElementById('man-submit-btn');
    btn.disabled = true; btn.innerHTML = '<span style="opacity:0.7;">Creating...</span>'; msgEl.innerHTML = '';
    const fd = new FormData();
    fd.append('action','tv_add_manual_subscription');
    fd.append('nonce', typeof tvAdmin !== 'undefined' ? tvAdmin.manualSubNonce : '');
    fd.append('user_id', userId); fd.append('plan_id', planId);
    fd.append('duration_months', duration); fd.append('payment_method', method);
    fd.append('currency', currency); fd.append('amount', amount);
    fd.append('coupon', coupon); fd.append('notes', notes);
    fetch(typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            if (res.success) {
                msgEl.innerHTML = '<span style="color:var(--tv-success);font-weight:700;">\u2713 Subscription created! Ref: ' + res.data.txn_id + '</span>';
                btn.disabled = false; btn.innerHTML = '\u2713 Created!'; btn.style.background = '#10b981';
                setTimeout(() => { location.reload(); }, 2000);
            } else {
                msgEl.innerHTML = '<span style="color:var(--tv-danger);">\u26a0 Error: ' + (res.data || 'Unknown error') + '</span>';
                btn.disabled = false; btn.innerHTML = 'Create Subscription'; btn.style.background = '';
            }
        }).catch(() => {
            msgEl.innerHTML = '<span style="color:var(--tv-danger);">\u26a0 Network error.</span>';
            btn.disabled = false; btn.innerHTML = 'Create Subscription';
        });
}
document.getElementById('tv-manual-add-modal').addEventListener('click', function(e) {
    if (e.target === this) tvCloseManualAddModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') tvCloseManualAddModal();
});
</script>