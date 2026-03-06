<?php if (!defined('ABSPATH')) { exit; } ?>

<script>

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
        if (matchedPanel) {
            document.querySelector('input[name="panel_mode"][value="attach"]').checked = true;
        } else {
            document.querySelector('input[name="panel_mode"][value="override"]').checked = true;
        }

        const contextBox = document.getElementById('fulfillment-context-box');
        const submitBtn = document.getElementById('fulfill-submit-btn');
        
        ['cred_user', 'cred_pass', 'cred_url'].forEach(fid => {
            const el = document.getElementById(fid);
            if(el.value) {
                el.style.borderColor = '#10b981';
                el.style.backgroundColor = '#ecfdf5';
            } else {
                el.style.borderColor = ''; 
                el.style.backgroundColor = '';
            }
        });

        if (cUser && cPass) {
            const modeLabel = matchedPanel ? "Panel detected &amp; selected." : "Using existing host.";
            contextBox.innerHTML = '<span class="dashicons dashicons-yes" style="margin-right:4px;"></span> <strong>Credentials Preserved.</strong> ' + modeLabel + ' <br>This is an <strong>Extension/Renewal</strong>.';
            contextBox.style.display = 'block';
            contextBox.style.background = '#dcfce7'; 
            contextBox.style.color = '#166534';
            
            submitBtn.innerText = "Confirm Extension";
            submitBtn.classList.remove('tv-btn-primary');
            submitBtn.style.backgroundColor = '#059669'; 
            submitBtn.style.color = '#fff';
        } else {
            contextBox.style.display = 'none';
            submitBtn.innerText = "Confirm & Fulfill";
            submitBtn.classList.add('tv-btn-primary');
            submitBtn.style.backgroundColor = ''; 
            submitBtn.style.color = '';
        }
        
        document.getElementById('fulfill-modal').style.display = 'flex';
    }

    function openRejectModal(id){
        if(!window.confirm('Are you sure you want to reject this transaction?')){ return; }
        document.getElementById('reject-pay-id').value = id;
        document.getElementById('reject-modal').style.display = 'flex';
    }

    function parseM3U() {
        const raw = document.getElementById('raw_m3u').value;
        if(!raw) return;
        
        const userMatch = raw.match(/username=([^&]+)/);
        const passMatch = raw.match(/password=([^&]+)/);
        
        if(userMatch) document.getElementById('cred_user').value = userMatch[1];
        if(passMatch) document.getElementById('cred_pass').value = passMatch[1];
        
        try {
            const urlObj = new URL(raw);
            if(!document.getElementById('cred_url').value) {
                document.getElementById('cred_url').value = urlObj.origin;
            }
            if(!document.getElementById('cred_m3u').value) {
                document.getElementById('cred_m3u').value = raw;
            }
        } catch(e){}
        
        if(document.getElementById('panel_select').value) {
            applyPanel(); 
        }
    }

    function applyPanel() {
        const sel = document.getElementById('panel_select');
        const opt = sel.options[sel.selectedIndex];
        const user = document.getElementById('cred_user').value;
        const pass = document.getElementById('cred_pass').value;
        const mode = document.querySelector('input[name="panel_mode"]:checked').value;
        const hint = document.getElementById('panel-hint');

        if (mode === 'attach') {
            hint.innerHTML = '<strong>Attachment Mode:</strong> Panel URLs are appended to the M3U field. Base URL is preserved.';
        } else {
            hint.innerHTML = '<strong>Override Mode:</strong> Panel URLs will replace the fields below.';
        }
        
        if (opt.value) {
            let smart = opt.getAttribute('data-smart');
            let xtream = opt.getAttribute('data-xtream');
            
            if (mode === 'override') {
                document.getElementById('cred_url').value = xtream;
                
                if(smart.includes('get.php') || smart.includes('username=')) {
                     try {
                         let tempUrl = new URL(smart);
                         if(user) tempUrl.searchParams.set('username', user);
                         if(pass) tempUrl.searchParams.set('password', pass);
                         document.getElementById('cred_m3u').value = tempUrl.toString();
                     } catch(e) {
                         document.getElementById('cred_m3u').value = smart;
                     }
                } else {
                     document.getElementById('cred_m3u').value = smart;
                }
            } 
            else if (mode === 'attach') {
                let attachmentLink = smart;
                if((smart.includes('get.php') || smart.includes('username=')) && user && pass) {
                     try {
                         let tempUrl = new URL(smart);
                         tempUrl.searchParams.set('username', user);
                         tempUrl.searchParams.set('password', pass);
                         attachmentLink = tempUrl.toString();
                     } catch(e) {}
                }

                const currentM3u = document.getElementById('cred_m3u').value;
                if (!currentM3u.includes(attachmentLink)) {
                    if (currentM3u) {
                        document.getElementById('cred_m3u').value = currentM3u + '\n\n[Panel: ' + attachmentLink + ']';
                    } else {
                        document.getElementById('cred_m3u').value = attachmentLink;
                    }
                }
            }
        }
    }

    // -------------------------------------------------------
    // DELETE MANUAL TRANSACTION
    // -------------------------------------------------------
    function tvDeleteManualTxn(pid) {
        if (!confirm('Delete this manually-added transaction? This will also remove the linked subscription if it has not yet been fulfilled.')) return;
        var btn = event && event.currentTarget;
        if (btn) { btn.disabled = true; btn.textContent = '\x85'; }
        var fd = new FormData();
        fd.append('action', 'tv_delete_manual_transaction');
        fd.append('nonce', tvManualNonce);
        fd.append('payment_id', pid);
        fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d.success) {
                    var row = btn ? btn.closest('tr') : null;
                    if (row) {
                        row.style.transition = 'opacity 0.3s';
                        row.style.opacity = '0';
                        setTimeout(function(){ row.remove(); }, 320);
                    } else {
                        location.reload();
                    }
                } else {
                    alert('Error: ' + (d.data || 'Could not delete transaction'));
                    if (btn) { btn.disabled = false; btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>'; }
                }
            })
            .catch(function(){ alert('Network error'); if (btn) { btn.disabled = false; btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>'; } });
    }

    // -------------------------------------------------------
    // MANUAL TRANSACTION MODAL
    // -------------------------------------------------------
    var _mtxPlans  = [];
    var _mtxUserId = 0;
    var _mtxPlanId = 0;
    var _mtxPlanData = null;

    function tvOpenManualTxn() {
        _mtxUserId = 0; _mtxPlanId = 0; _mtxPlanData = null;
        tvMtxGoStep1();
        tvMtxLoadPlans();
        document.getElementById('mtx-user-search').value = '';
        document.getElementById('mtx-user-id').value = '';
        document.getElementById('mtx-user-selected').style.display = 'none';
        document.getElementById('mtx-plan-info').style.display = 'none';
        document.getElementById('mtx-next-btn').disabled = true;
        document.getElementById('mtx-amount').value = '';
        document.getElementById('mtx-discount').value = '0';
        document.getElementById('mtx-coupon').value = '';
        document.getElementById('mtx-quantity').value = '1';
        document.getElementById('tv-manual-txn-modal').style.display = 'flex';
        setTimeout(function(){ document.getElementById('mtx-user-search').focus(); }, 100);
    }
    function tvCloseManualTxn() {
        document.getElementById('tv-manual-txn-modal').style.display = 'none';
    }
    function tvMtxGoStep1() {
        document.getElementById('mtx-step-1').style.display = '';
        document.getElementById('mtx-step-2').style.display = 'none';
        document.getElementById('mtx-step-1-tab').style.background = '#6366f1';
        document.getElementById('mtx-step-1-tab').style.color = '#fff';
        document.getElementById('mtx-step-2-tab').style.background = '#f1f5f9';
        document.getElementById('mtx-step-2-tab').style.color = '#94a3b8';
    }
    function tvMtxGoStep2() {
        if (!_mtxUserId || !_mtxPlanId) return;
        tvMtxUpdateAmountPreview();
        document.getElementById('mtx-step-1').style.display = 'none';
        document.getElementById('mtx-step-2').style.display = '';
        document.getElementById('mtx-step-2-tab').style.background = '#6366f1';
        document.getElementById('mtx-step-2-tab').style.color = '#fff';
        document.getElementById('mtx-step-1-tab').style.background = '#f1f5f9';
        document.getElementById('mtx-step-1-tab').style.color = '#94a3b8';
        document.getElementById('mtx-error').style.display = 'none';
        // populate summary bar
        var p = _mtxPlanData;
        var qty = parseInt(document.getElementById('mtx-quantity').value) || 1;
        document.getElementById('mtx-summary-bar').innerHTML =
            '<strong>' + (p ? p.name : '') + '</strong>' +
            ' &bull; ' + qty + ' cycle' + (qty > 1 ? 's' : '') +
            ' &bull; Total: $' + (p ? (parseFloat(p.price) * qty).toFixed(2) : '');
    }
    function tvMtxLoadPlans() {
        var sel = document.getElementById('mtx-plan-select');
        sel.innerHTML = '<option value="">Loading...</option>';
        var fd = new FormData();
        fd.append('action', 'tv_get_plans_for_manual');
        fd.append('nonce', tvManualNonce);
        fetch(ajaxurl, { method:'POST', body:fd })
            .then(function(r){ return r.json(); })
            .then(function(d){
                _mtxPlans = d.success ? d.data : [];
                sel.innerHTML = '<option value="">-- Select Plan --</option>';
                _mtxPlans.forEach(function(p){
                    var opt = document.createElement('option');
                    opt.value = p.id;
                    opt.dataset.price = p.price;
                    opt.dataset.days  = p.duration_days;
                    opt.textContent   = p.name + ' \x97 $' + parseFloat(p.price).toFixed(2) + ' / ' + Math.ceil(p.duration_days/30) + ' mo';
                    sel.appendChild(opt);
                });
                // Restore selected if repopulating
                if (_mtxPlanId) sel.value = _mtxPlanId;
                tvMtxOnPlanChange();
            });
    }
    function tvMtxOnPlanChange() {
        var sel = document.getElementById('mtx-plan-select');
        var opt = sel.options[sel.selectedIndex];
        _mtxPlanId = parseInt(sel.value) || 0;
        var qty = parseInt(document.getElementById('mtx-quantity').value) || 1;
        
        _mtxPlanData = _mtxPlanId ? { id:_mtxPlanId, name:opt.textContent.split(' \x97 ')[0], price:opt.dataset.price, duration_days:parseInt(opt.dataset.days)||30 } : null;
        var infoBox = document.getElementById('mtx-plan-info');
        if (_mtxPlanData) {
            var unitPrice = parseFloat(_mtxPlanData.price);
            var totalDays = _mtxPlanData.duration_days * qty;
            var totalPrice = unitPrice * qty;
            
            var endDate = new Date();
            endDate.setDate(endDate.getDate() + totalDays);
            
            document.getElementById('mtx-plan-price').textContent = '$' + totalPrice.toFixed(2);
            document.getElementById('mtx-plan-duration').textContent = totalDays + ' days (' + qty + 'x)';
            document.getElementById('mtx-plan-enddate').textContent = endDate.toDateString();
            
            // Pre-fill amount with calculated price
            document.getElementById('mtx-amount').value = totalPrice.toFixed(2);
            
            infoBox.style.display = '';
        } else {
            infoBox.style.display = 'none';
        }
        tvMtxCheckNextBtn();
    }
    function tvMtxCheckNextBtn() {
        document.getElementById('mtx-next-btn').disabled = !(_mtxUserId && _mtxPlanId);
    }
    // User search
    var _mtxSearchTimer = null;
    function tvMtxSearchUsers(q) {
        clearTimeout(_mtxSearchTimer);
        var dd = document.getElementById('mtx-user-dropdown');
        if (!q || q.length < 2) { dd.style.display = 'none'; return; }
        dd.innerHTML = '<div style="padding:10px 14px; color:#64748b; font-size:13px;">Searching\x85</div>';
        dd.style.display = 'block';
        _mtxSearchTimer = setTimeout(function(){
            var fd = new FormData();
            fd.append('action', 'tv_get_users_for_manual');
            fd.append('nonce', tvManualNonce);
            fd.append('search', q);
            fetch(ajaxurl, { method:'POST', body:fd })
                .then(function(r){ return r.json(); })
                .then(function(d){
                    dd.innerHTML = '';
                    var users = d.success ? d.data : [];
                    if (!users.length) {
                        dd.innerHTML = '<div style="padding:10px 14px; color:#64748b; font-size:13px;">No users found</div>';
                        return;
                    }
                    users.forEach(function(u){
                        var item = document.createElement('div');
                        item.style.cssText = 'padding:10px 14px; cursor:pointer; font-size:13px; border-bottom:1px solid #f1f5f9;';
                        item.textContent = u.label;
                        item.onmouseenter = function(){ this.style.background = '#f0f9ff'; };
                        item.onmouseleave = function(){ this.style.background = ''; };
                        item.onclick = function(){
                            _mtxUserId = u.id;
                            document.getElementById('mtx-user-id').value = u.id;
                            document.getElementById('mtx-user-label').textContent = u.label;
                            document.getElementById('mtx-user-selected').style.display = 'flex';
                            document.getElementById('mtx-user-search').style.display = 'none';
                            dd.style.display = 'none';
                            tvMtxCheckNextBtn();
                        };
                        dd.appendChild(item);
                    });
                });
        }, 300);
    }
    function tvMtxClearUser() {
        _mtxUserId = 0;
        document.getElementById('mtx-user-id').value = '';
        document.getElementById('mtx-user-selected').style.display = 'none';
        document.getElementById('mtx-user-search').style.display = '';
        document.getElementById('mtx-user-search').value = '';
        document.getElementById('mtx-user-search').focus();
        tvMtxCheckNextBtn();
    }
    function tvMtxUpdateAmountPreview() {
        var amount   = parseFloat(document.getElementById('mtx-amount').value) || 0;
        var discount = parseFloat(document.getElementById('mtx-discount').value) || 0;
        var currency = document.getElementById('mtx-currency').value;
        var symbols  = { USD:'$', NGN:'&#8358;', GHS:'&#8373;', KES:'KSh ', ZAR:'R', GBP:'&pound;', EUR:'&euro;' };
        var sym = symbols[currency] || currency + ' ';
        var gross = amount + discount;
        document.getElementById('mtx-gross-preview').textContent = sym + gross.toFixed(2);
        document.getElementById('mtx-net-preview').textContent   = sym + amount.toFixed(2);
    }
    function tvMtxSave() {
        var userId   = _mtxUserId;
        var planId   = _mtxPlanId;
        var qty      = parseInt(document.getElementById('mtx-quantity').value) || 1;
        var amount   = parseFloat(document.getElementById('mtx-amount').value);
        var currency = document.getElementById('mtx-currency').value;
        var discount = parseFloat(document.getElementById('mtx-discount').value) || 0;
        var coupon   = document.getElementById('mtx-coupon').value.trim();
        var method   = document.getElementById('mtx-method').value;
        var txDate   = document.getElementById('mtx-date').value;
        
        var baseDays = _mtxPlanData ? parseInt(_mtxPlanData.duration_days) || 30 : 30;
        var totalDays = baseDays * qty;
        
        var errEl    = document.getElementById('mtx-error');
        errEl.style.display = 'none';

        if (!userId)           { errEl.textContent = 'Please select a user.'; errEl.style.display=''; return; }
        if (!planId)           { errEl.textContent = 'Please select a plan.'; errEl.style.display=''; return; }
        if (isNaN(amount) || amount <= 0) { errEl.textContent = 'Please enter a valid amount greater than 0.'; errEl.style.display=''; return; }

        var btn = document.getElementById('mtx-save-btn');
        btn.disabled = true;
        btn.textContent = 'Saving\x85';

        var fd = new FormData();
        fd.append('action',         'tv_add_manual_subscription');
        fd.append('nonce',          tvManualNonce);
        fd.append('user_id',        userId);
        fd.append('plan_id',        planId);
        fd.append('duration_days',  totalDays);
        fd.append('amount',         amount);
        fd.append('currency',       currency);
        fd.append('discount',       discount);
        fd.append('coupon',         coupon);
        fd.append('payment_method', method);
        fd.append('tx_date',        txDate);

        fetch(ajaxurl, { method:'POST', body:fd })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d.success) {
                    tvCloseManualTxn();
                    // Reload page to show new row in table
                    window.location.reload();
                } else {
                    errEl.textContent = 'Error: ' + (d.data || 'Unknown error');
                    errEl.style.display = '';
                    btn.disabled = false;
                    btn.textContent = 'Save Transaction';
                }
            })
            .catch(function(e){
                errEl.textContent = 'Network error. Please try again.';
                errEl.style.display = '';
                btn.disabled = false;
                btn.textContent = 'Save Transaction';
            });
    }
    // Close modal on overlay click
    document.getElementById('tv-manual-txn-modal').addEventListener('click', function(e){
        if (e.target === this) tvCloseManualTxn();
    });
    // Close on Escape
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape') {
            if (document.getElementById('tv-manual-txn-modal').style.display !== 'none') tvCloseManualTxn();
        }
    });
</script>

<script>
(function(){
    var overlay  = document.getElementById('tv-user-popup');
    if(!overlay) return;
    var title    = document.getElementById('tv-user-popup-title');
    var nameEl   = document.getElementById('tv-user-popup-name');
    var emailEl  = document.getElementById('tv-user-popup-email');
    var phoneEl  = document.getElementById('tv-user-popup-phone');
    var connEl   = document.getElementById('tv-user-popup-connections');
    var closeBtn = document.getElementById('tv-user-popup-close');

    function openPopup(data){
        title.textContent   = data.name  || '\x97';
        nameEl.textContent  = data.name  || '\x97';
        emailEl.textContent = data.email || '\x97';
        phoneEl.textContent = data.phone || '\x97';
        connEl.textContent  = String(data.connections || '');
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closePopup(){
        overlay.style.display = 'none';
        document.body.style.overflow = '';
    }

    document.addEventListener('click', function(e){
        var trigger = e.target.closest ? e.target.closest('.tv-user-popup-trigger') : (e.target.classList.contains('tv-user-popup-trigger') ? e.target : null);
        if(trigger){
            e.preventDefault();
            openPopup({
                name:        trigger.getAttribute('data-tv-user-name'),
                email:       trigger.getAttribute('data-tv-user-email'),
                phone:       trigger.getAttribute('data-tv-user-phone'),
                connections: trigger.getAttribute('data-tv-user-connections')
            });
            return;
        }
        var copyBtn = e.target.closest ? e.target.closest('[data-tv-copy-target]') : (e.target.matches && e.target.matches('[data-tv-copy-target]') ? e.target : null);
        if(copyBtn){
            var cid = copyBtn.getAttribute('data-tv-copy-target');
            var cel = document.getElementById(cid);
            if(!cel) return;
            var txt = cel.textContent || '';
            if(navigator.clipboard){ navigator.clipboard.writeText(txt); }
            else { var ta=document.createElement('textarea'); ta.value=txt; ta.style.cssText='position:fixed;opacity:0;'; document.body.appendChild(ta); ta.select(); try{document.execCommand('copy');}catch(x){} document.body.removeChild(ta); }
            var orig=copyBtn.textContent; copyBtn.textContent='Copied!'; copyBtn.style.background='#10b981';
            setTimeout(function(){ copyBtn.textContent=orig; copyBtn.style.background=''; }, 1500);
            return;
        }
        if(e.target === overlay){ closePopup(); }
    });
    closeBtn && closeBtn.addEventListener('click', closePopup);
    document.addEventListener('keydown', function(e){ if(e.key==='Escape') closePopup(); });
})();
</script>
