(function(){
  // --- UTILITIES ---
  function syncFieldState(field){
    if(!field) return;
    var control = field.querySelector('.tv-control');
    if(!control) return;
    var v = (control.value || '').trim();
    if(control.tagName === 'SELECT'){
      if(v && v !== '0'){ field.classList.add('has-value'); }
      else { field.classList.remove('has-value'); }
    }else{
      if(v.length){ field.classList.add('has-value'); }
      else { field.classList.remove('has-value'); }
    }
  }

  function initFloatingLabels(root){
    (root || document).querySelectorAll('.tv-field').forEach(function(field){
      var control = field.querySelector('.tv-control');
      if(!control) return;
      syncFieldState(field);

      control.addEventListener('focus', function(){ field.classList.add('is-active'); });
      control.addEventListener('blur', function(){
        field.classList.remove('is-active');
        syncFieldState(field);
      });
      control.addEventListener('input', function(){ syncFieldState(field); });
      control.addEventListener('change', function(){ syncFieldState(field); });
    });
  }

  function initLoadingButtons(){
    document.querySelectorAll('.tv-app-container form').forEach(function(form){
      form.addEventListener('submit', function(e){
        if (form.checkValidity && !form.checkValidity()) return;
        var btn = form.querySelector('button[type="submit"].tv-btn, input[type="submit"].tv-btn');
        if(btn){ btn.classList.add('is-loading'); }
      });
    });

    var methodForm = document.getElementById('tv-method-form');
    if (methodForm) {
        methodForm.addEventListener('submit', function(e) {
            if (this.checkValidity && !this.checkValidity()) return;
            const btn = this.querySelector('button[type="submit"]');
            if (btn) {
                btn.innerHTML = '<span class="dashicons dashicons-update" style="animation:spin 2s infinite linear; margin-right:6px;"></span> Saving...';
                btn.style.opacity = '0.7';
                btn.style.pointerEvents = 'none';
            }
        });
    }
  }

  // --- SENSITIVE ACTIONS (Confirm/Delete) ---
  function tvOpenConfirm(opts){
    var ov = document.getElementById('tv-sensitive-confirm-modal');
    if(!ov) { return window.confirm(opts && opts.message ? opts.message : 'Are you sure?'); }
    var title = ov.querySelector('#tv-sensitive-confirm-title');
    var msg = ov.querySelector('#tv-sensitive-confirm-message');
    if(title) title.textContent = opts && opts.title ? opts.title : 'Confirm';
    if(msg) msg.textContent = opts && opts.message ? opts.message : 'Are you sure?';

    return new Promise(function(resolve){
      var onClose = function(val){
        ov.style.display = 'none';
        ov.setAttribute('aria-hidden', 'true');
        document.removeEventListener('keydown', onKey);
        resolve(!!val);
      };
      var onKey = function(e){ if(e.key === 'Escape') onClose(false); };
      document.addEventListener('keydown', onKey);

      var btnCancel = ov.querySelector('[data-tv-confirm-cancel]');
      var btnOk = ov.querySelector('[data-tv-confirm-ok]');
      if(btnCancel) btnCancel.onclick = function(e){ e.preventDefault(); onClose(false); };
      if(btnOk) btnOk.onclick = function(e){ e.preventDefault(); onClose(true); };

      ov.style.display = 'flex';
      ov.setAttribute('aria-hidden', 'false');
    });
  }

  function tvOpenDeleteVerify(){
    var ov = document.getElementById('tv-delete-verify-modal');
    if(!ov) { return Promise.resolve({ ok:false }); }
    var code = String(Math.floor(1000 + Math.random()*9000));
    var codeEl = ov.querySelector('[data-tv-code]');
    var input = ov.querySelector('input[data-tv-code-input]');
    if(codeEl) codeEl.textContent = code;
    if(input) input.value = '';

    return new Promise(function(resolve){
      var onClose = function(ok){
        ov.style.display = 'none';
        ov.setAttribute('aria-hidden', 'true');
        document.removeEventListener('keydown', onKey);
        resolve({ ok: !!ok });
      };
      var onKey = function(e){ if(e.key === 'Escape') onClose(false); };
      document.addEventListener('keydown', onKey);

      var btnCancel = ov.querySelector('[data-tv-delete-cancel]');
      var btnVerify = ov.querySelector('[data-tv-delete-verify]');
      if(btnCancel) btnCancel.onclick = function(e){ e.preventDefault(); onClose(false); };
      if(btnVerify) btnVerify.onclick = function(e){
        e.preventDefault();
        var val = input ? String(input.value||'').trim() : '';
        if(val === code) onClose(true);
        else {
          if(input) { input.focus(); input.select(); }
          var err = ov.querySelector('[data-tv-delete-error]');
          if(err) { err.textContent = 'Code does not match. Try again.'; err.style.display='block'; }
        }
      };
      var err = ov.querySelector('[data-tv-delete-error]');
      if(err){ err.textContent=''; err.style.display='none'; }

      ov.style.display = 'flex';
      ov.setAttribute('aria-hidden', 'false');
      setTimeout(function(){ if(input) input.focus(); }, 50);
    });
  }

  function initSensitiveActions(){
    var root = document.querySelector('.tv-app-container') || document;

    // Issue a short-lived, user-bound delete token after UI verification.
    function tvIssueDeleteToken(){
      try {
        if(!window.tvAdmin || !tvAdmin.ajaxUrl || !tvAdmin.deleteTokenNonce) {
          console.error("TV Admin global object missing");
          return Promise.resolve('');
        }
        var fd = new FormData();
        fd.append('action', 'tv_issue_delete_token');
        fd.append('nonce', tvAdmin.deleteTokenNonce);
        return fetch(tvAdmin.ajaxUrl, { method:'POST', credentials:'same-origin', body: fd })
          .then(function(r){ return r.json(); })
          .then(function(j){ return (j && j.success && j.data && j.data.token) ? String(j.data.token) : ''; })
          .catch(function(e){ console.error(e); return ''; });
      } catch(e) {
        return Promise.resolve('');
      }
    }

    function tvAddParam(url, key, val){
      if(!val) return url;
      try {
        var u = new URL(url, window.location.href);
        u.searchParams.set(key, val);
        return u.toString();
      } catch(e) {
        var sep = url.indexOf('?') === -1 ? '?' : '&';
        return url + sep + encodeURIComponent(key) + '=' + encodeURIComponent(val);
      }
    }

    // Approve / Reject confirmations
    root.addEventListener('click', function(e){
      var a = e.target && e.target.closest ? e.target.closest('a') : null;
      if(!a) return;
      var href = a.getAttribute('href') || '';
      if(!href) return;

      var actionVal = '';
      try {
        var uObj = new URL(href, window.location.href);
        actionVal = uObj.searchParams.get('action') || '';
      } catch(ex) { actionVal = ''; }

      var isApprove = (actionVal === 'approve' || actionVal === 'approve_pay' || actionVal.indexOf('approve') !== -1);
      var isReject = (actionVal === 'reject' || actionVal === 'reject_pay' || actionVal.indexOf('reject') !== -1);
      
      if(isApprove || isReject){
        e.preventDefault();
        e.stopPropagation();
        var msg = isApprove ? 'Are you sure you want to approve this transaction?' : 'Are you sure you want to reject this transaction?';
        var title = isApprove ? 'Confirm Approval' : 'Confirm Rejection';
        var go = function(){ window.location.href = href; };
        
        var res = tvOpenConfirm({ title:title, message:msg });
        if(res && typeof res.then === 'function'){
          res.then(function(ok){ if(ok) go(); });
        } else {
          if(res) go();
        }
        return;
      }

      // Deletes: confirm then 4-digit verification
      var looksDelete = href.indexOf('action=delete') !== -1 || 
                        href.indexOf('action=trash') !== -1 || 
                        href.indexOf('action=soft_delete_sub') !== -1 ||
                        a.classList.contains('tv-action-delete') || 
                        a.classList.contains('tv-btn-danger') || 
                        a.getAttribute('data-tv-delete') === '1';

      if(looksDelete){
        e.preventDefault();
        e.stopPropagation();
        
        var goDel = function(url){ window.location.href = url || href; };
        var conf = tvOpenConfirm({ title:'Confirm Delete', message:'Are you sure you want to delete this item?' });
        
        var afterConfirm = function(ok){
          if(!ok) return;
          tvOpenDeleteVerify().then(function(v){
            if(!(v && v.ok)) return;
            
            // Show Loading State
            if(a.classList.contains('tv-btn')) {
                a.classList.add('is-loading');
                a.textContent = 'Verifying...';
            }

            tvIssueDeleteToken().then(function(token){
              if (!token) {
                  alert("Security Error: Could not generate delete token. Please reload the page and try again.");
                  window.location.reload();
                  return;
              }
              var finalUrl = tvAddParam(href, 'tv_del_token', token);
              goDel(finalUrl);
            });
          });
        };
        
        if(conf && typeof conf.then === 'function') conf.then(afterConfirm);
        else afterConfirm(!!conf);
      }
    }, true); 

    // Deletes via Forms
    root.addEventListener('submit', function(e){
      var form = e.target;
      if(!form || !form.getAttribute) return;
      if(form.getAttribute('data-tv-delete-form') !== '1') return;
      
      e.preventDefault();
      var doSubmit = function(){ form.submit(); };
      
      var conf = tvOpenConfirm({ title:'Confirm Delete', message:'Are you sure you want to delete this item?' });
      var afterConfirm = function(ok){
        if(!ok) return;
        tvOpenDeleteVerify().then(function(v){
          if(!(v && v.ok)) return;
          tvIssueDeleteToken().then(function(token){
            if(token){
              var input = form.querySelector('input[name="tv_del_token"]');
              if(!input){
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tv_del_token';
                form.appendChild(input);
              }
              input.value = token;
              doSubmit();
            } else {
              alert("Security Error: Could not generate delete token.");
            }
          });
        });
      };
      
      if(conf && typeof conf.then === 'function') conf.then(afterConfirm);
      else afterConfirm(!!conf);
    }, true);
  }

  function applyTheme(theme){
    if(theme === 'dark') document.body.classList.add('tv-dark');
    else document.body.classList.remove('tv-dark');
  }

  function initThemeToggle(){
    var btn = document.getElementById('tv-theme-toggle');
    if(!btn) return;
    var stored = '';
    try { stored = window.localStorage.getItem('tv_theme') || ''; } catch(e) { stored = ''; }
    var prefersDark = false;
    try { prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches; } catch(e) { prefersDark = false; }
    var initial = stored || (prefersDark ? 'dark' : 'light');
    applyTheme(initial);
    btn.addEventListener('click', function(e){
      e.preventDefault();
      var isDark = document.body.classList.contains('tv-dark');
      var next = isDark ? 'light' : 'dark';
      applyTheme(next);
      try { window.localStorage.setItem('tv_theme', next); } catch(err) {}
    });
  }

  // ==========================================
  // [NEW] PLANS SORTING LOGIC (Manual Save)
  // ==========================================
  function initSortablePlans() {
    var $sortables = jQuery('.tv-sortable-plans');
    if (!$sortables.length) return;

    var saveBtn = jQuery('#tv-save-order-btn');

    $sortables.sortable({
      items: '.tv-plan-card',
      cursor: 'move',
      opacity: 0.9,
      placeholder: 'ui-sortable-placeholder',
      update: function(event, ui) {
        // UI FEEDBACK: Enable "Save Order" button & Highlight it
        // This confirms the user must click to persist changes
        saveBtn.prop('disabled', false)
               .addClass('is-dirty')
               .removeClass('tv-btn-secondary')
               .addClass('tv-btn-primary')
               .html('<span class="dashicons dashicons-yes" style="margin-right:6px;"></span> Save Display Order');
      }
    });
    
    // Enable text selection inside cards, disable selection while dragging
    $sortables.disableSelection();

    // HANDLE MANUAL SAVE CLICK
    saveBtn.on('click', function(e) {
        e.preventDefault();
        var btn = jQuery(this);
        var originalHtml = btn.html();
        
        btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation:spin 2s infinite linear; margin-right:6px;"></span> Saving...');

        var newOrder = [];
        // Strategy: Iterate through ALL categories in DOM order. 
        // This creates a single global index (0 to N) across all categories.
        jQuery('.tv-plan-card').each(function() {
             var id = jQuery(this).data('id');
             if(id) newOrder.push(id);
        });

        // Send to Server via AJAX
        jQuery.post(tvAdmin.ajaxUrl, {
            action: 'tv_update_plan_order',
            order: newOrder,
            _ajax_nonce: tvAdmin.sortNonce
        }, function(response) {
            if(response.success) {
                var notice = jQuery('#tv-sort-notice');
                notice.fadeIn();
                setTimeout(function(){ notice.fadeOut(); }, 3000);
                
                // Reset Button State
                btn.removeClass('is-dirty')
                   .removeClass('tv-btn-primary')
                   .addClass('tv-btn-secondary')
                   .html('<span class="dashicons dashicons-sort" style="margin-right:6px;"></span> Save Display Order');
                
                // Keep disabled until next change
                setTimeout(function(){ btn.prop('disabled', true); }, 500); 
            } else {
                alert('Save failed: ' + (response.data.message || 'Unknown error'));
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
  }

  // =========================
  // Initialization
  // =========================
  document.addEventListener('DOMContentLoaded', function(){
    initFloatingLabels(document);
    initLoadingButtons();
    initThemeToggle();
    initSensitiveActions();
    initSortablePlans(); // Initialize new sorting logic
  });
})();