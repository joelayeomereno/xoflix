        const hasPanelTag = cM3u.includes('[Panel:');
        if (hasPanelTag) {
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
            const modeLabel = matchedPanel ? "Panel detected & selected." : "Using existing host.";
            contextBox.innerHTML = `<span class="dashicons dashicons-yes" style="margin-right:4px;"></span> <strong>Credentials Preserved.</strong> ${modeLabel} <br>This is an <strong>Extension/Renewal</strong>.`;
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
</script>

<!-- User Detail Popup -->
<div id="tv-user-popup" style="position:fixed; inset:0; background:rgba(0,0,0,0.45); display:none; align-items:center; justify-content:center; padding:20px; z-index:99999;">
    <div class="tv-card" style="max-width:560px; width:100%; padding:18px; border:1px solid var(--tv-border);">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
            <div>
                <div style="font-size:12px; color:var(--tv-text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">User Details</div>
                <div id="tv-user-popup-title" style="font-size:18px; font-weight:900; margin-top:4px;"></div>
            </div>
            <button type="button" class="tv-btn tv-btn-secondary" id="tv-user-popup-close">Close</button>
        </div>

        <div style="margin-top:14px; display:grid; gap:10px;">
            <?php
            $copy_row = function ($label, $id) {
                echo '<div style="display:flex; gap:10px; align-items:center; justify-content:space-between; padding:10px; border:1px solid var(--tv-border); border-radius:12px;">'
                    .'<div style="min-width:0;">'
                        .'<div style="font-size:11px; color:var(--tv-text-muted); font-weight:800; text-transform:uppercase;">'.esc_html($label).'</div>'
                        .'<div id="'.esc_attr($id).'" style="font-family:monospace; font-size:13px; word-break:break-all;"></div>'
                    .'</div>'
                    .'<button type="button" class="tv-btn tv-btn-primary tv-btn-sm" data-tv-copy-target="'.esc_attr($id).'">Copy</button>'
                .'</div>';
            };
            $copy_row('Full Name', 'tv-user-popup-name');
            $copy_row('Email', 'tv-user-popup-email');
            $copy_row('Phone', 'tv-user-popup-phone');
            $copy_row('Connections', 'tv-user-popup-connections');
            ?>
        </div>
    </div>
</div>

<script>
(function(){
    const overlay = document.getElementById('tv-user-popup');
    if(!overlay) return;
    const title = document.getElementById('tv-user-popup-title');
    const nameEl = document.getElementById('tv-user-popup-name');
    const emailEl = document.getElementById('tv-user-popup-email');
    const phoneEl = document.getElementById('tv-user-popup-phone');
    const connEl = document.getElementById('tv-user-popup-connections');
    const closeBtn = document.getElementById('tv-user-popup-close');

    function openPopup(data){
        const nm = data.name || '';
        title.textContent = nm;
        nameEl.textContent = nm;
        emailEl.textContent = data.email || '';
        phoneEl.textContent = data.phone || '';
        connEl.textContent = String(data.connections || '');
        overlay.style.display = 'flex';
    }
    function closePopup(){ overlay.style.display = 'none'; }

    document.addEventListener('click', function(e){
        const t = e.target;
        if(t && t.classList && t.classList.contains('tv-user-popup-trigger')){
            e.preventDefault();
            openPopup({
                name: t.getAttribute('data-tv-user-name'),
                email: t.getAttribute('data-tv-user-email'),
                phone: t.getAttribute('data-tv-user-phone'),
                connections: t.getAttribute('data-tv-user-connections')
            });
        }
        if(t && t.matches('[data-tv-copy-target]')){
            const id = t.getAttribute('data-tv-copy-target');
            const el = document.getElementById(id);
            if(!el) return;
            navigator.clipboard && navigator.clipboard.writeText(el.textContent || '');
        }
        if(t === overlay){ closePopup(); }
    });
    closeBtn && closeBtn.addEventListener('click', closePopup);
})();
</script>