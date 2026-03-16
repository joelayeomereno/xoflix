<?php
/**
 * File: tv-subscription-manager/admin/views/view-settings-integrations.php
 * Path: tv-subscription-manager/admin/views/view-settings-integrations.php
 * Version: 3.9.26 (Amazon SES Stockholm eu-north-1 Optimized)
 */

defined('ABSPATH') or die(); ?>

<div class="tv-page-header">
    <div>
        <h1>Integrations & Protocols</h1>
        <p>Configure the authenticated mail transport, multi-currency gateways, and messaging endpoints.</p>
    </div>
</div>

<form method="post" action="?page=tv-settings-general&tab=integrations">
    <?php wp_nonce_field('tv_settings_verify'); ?>

    <?php 
        $woocs_available = class_exists('WOOCS');
        $allowed_currencies = get_option('tv_allowed_currencies', []);
        if (!is_array($allowed_currencies)) { $allowed_currencies = []; }
    ?>

    <!-- SECTION 1: SMTP & AMAZON SES AUTHENTICATED TRANSPORT -->
    <div class="tv-card">
        <div class="tv-card-header">
            <h3><span class="dashicons dashicons-email-alt" style="margin-right:8px;"></span> SMTP & Amazon SES Connector</h3>
        </div>
        <div class="tv-card-body">
            <p style="font-size:13px; color:var(--tv-text-muted); margin-bottom:20px;">
                Force the system to use an authenticated server. This engine is optimized for <b>Amazon SES</b>, ensuring your notifications land in the inbox and not the spam folder.
            </p>

            <div style="background:var(--tv-surface-active); border:1px solid var(--tv-border); border-radius:12px; padding:20px; margin-bottom:24px;">
                <label class="tv-switch" style="margin-bottom:20px; font-weight:700;">
                    <input type="checkbox" name="tv_smtp_enabled" value="1" <?php checked(get_option('tv_smtp_enabled', 0), 1); ?> class="tv-toggle-input">
                    <span class="tv-toggle-ui" aria-hidden="true"></span>
                    <span>Enable Authenticated SMTP Transport (Recommended)</span>
                </label>

                <!-- Amazon SES Region Selector / Presets -->
                <div style="margin-bottom: 20px; padding: 12px; background: #fff; border: 1px solid var(--tv-border); border-radius: 10px;">
                    <label class="tv-label" style="font-size:12px; color:var(--tv-primary);">Quick Config Presets</label>
                    <select id="tv_smtp_presets" class="tv-input" onchange="applySmtpPreset(this.value)">
                        <option value="">-- Manual Configuration --</option>
                        <option value="ses-eu-north-1">Amazon SES: Europe (Stockholm) [eu-north-1]</option>
                        <option value="ses-eu-west-1">Amazon SES: Europe (Ireland) [eu-west-1]</option>
                        <option value="ses-us-east-1">Amazon SES: US East (N. Virginia)</option>
                        <option value="ses-us-west-2">Amazon SES: US West (Oregon)</option>
                        <option value="ses-eu-central-1">Amazon SES: Europe (Frankfurt)</option>
                        <option value="google">Google Workspace / Gmail</option>
                        <option value="outlook">Outlook / Office 365</option>
                    </select>
                    <p style="font-size:11px; color:var(--tv-text-muted); margin-top:6px;">Selecting a preset will automatically fill Host, Port, and Encryption fields for your region.</p>
                </div>

                <!-- Server Connection Block -->
                <div class="tv-row">
                    <div class="tv-col">
                        <label class="tv-label">SMTP Host Address</label>
                        <input type="text" id="smtp_host" name="tv_smtp_host" value="<?php echo esc_attr(get_option('tv_smtp_host', '')); ?>" class="tv-input" placeholder="e.g. email-smtp.eu-north-1.amazonaws.com">
                    </div>
                    <div class="tv-col" style="flex:0 0 100px;">
                        <label class="tv-label">Port</label>
                        <input type="number" id="smtp_port" name="tv_smtp_port" value="<?php echo esc_attr(get_option('tv_smtp_port', 587)); ?>" class="tv-input">
                    </div>
                    <div class="tv-col" style="flex:0 0 120px;">
                        <label class="tv-label">Encryption</label>
                        <select id="smtp_enc" name="tv_smtp_enc" class="tv-input">
                            <option value="tls" <?php selected(get_option('tv_smtp_enc'), 'tls'); ?>>TLS (Recommended)</option>
                            <option value="ssl" <?php selected(get_option('tv_smtp_enc'), 'ssl'); ?>>SSL</option>
                            <option value="" <?php selected(get_option('tv_smtp_enc'), ''); ?>>None</option>
                        </select>
                    </div>
                </div>

                <!-- Credential Block -->
                <div class="tv-row">
                    <div class="tv-col">
                        <label class="tv-label">SMTP Username (IAM Access Key)</label>
                        <input type="text" id="smtp_user" name="tv_smtp_user" value="<?php echo esc_attr(get_option('tv_smtp_user', '')); ?>" class="tv-input">
                    </div>
                    <div class="tv-col">
                        <label class="tv-label">SMTP Password (IAM Secret Key)</label>
                        <input type="password" id="smtp_pass" name="tv_smtp_pass" value="<?php echo esc_attr(get_option('tv_smtp_pass')); ?>" class="tv-input" placeholder="Leave blank to keep current password">
                    </div>
                </div>

                <!-- Identity Block (Critical for SES Parity) -->
                <div class="tv-row" style="margin-top:10px; border-top:1px dashed var(--tv-border); padding-top:15px;">
                    <div class="tv-col">
                        <label class="tv-label">Verified "From" Email</label>
                        <input type="email" id="smtp_from_email" name="tv_smtp_from_email" value="<?php echo esc_attr(get_option('tv_smtp_from_email', 'info@xoflix.tv')); ?>" class="tv-input" placeholder="info@xoflix.tv">
                        <p style="font-size:11px; color:var(--tv-danger); margin-top:4px;"><b>SES REQUIREMENT:</b> This address must be a verified identity in your <u>Stockholm</u> AWS Console.</p>
                    </div>
                    <div class="tv-col">
                        <label class="tv-label">From Display Name</label>
                        <input type="text" id="smtp_from_name" name="tv_smtp_from_name" value="<?php echo esc_attr(get_option('tv_smtp_from_name', get_bloginfo('name'))); ?>" class="tv-input" placeholder="XOFLIX TV">
                    </div>
                </div>

                <!-- SSL Security Toggle -->
                <div style="margin-top:15px; border-top:1px dashed var(--tv-border); padding-top:15px;">
                    <label class="tv-switch" style="font-size:12px; color:var(--tv-danger);">
                        <input type="checkbox" id="smtp_insecure" name="tv_smtp_insecure" value="1" <?php checked(get_option('tv_smtp_insecure', 0), 1); ?> class="tv-toggle-input">
                        <span class="tv-toggle-ui" aria-hidden="true" style="background:#fee2e2; border-color:#fecaca;"></span>
                        <span style="font-weight:700;">Disable Certificate Verification (Fixes "Peer certificate cannot be authenticated" errors)</span>
                    </label>
                </div>

                <!-- LIVE SMTP DIAGNOSTIC TOOL -->
                <div style="background:#fff; border:1px solid #bae6fd; border-radius:10px; padding:15px; margin-top:20px;">
                    <label class="tv-label" style="color:#0369a1; font-weight:800; margin-bottom:10px; display:flex; align-items:center; gap:8px;">
                        <span class="dashicons dashicons-admin-links" style="font-size:16px;"></span> Stockholm SES Handshake Diagnostic
                    </label>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input type="email" id="tv_smtp_test_email" class="tv-input" placeholder="Enter recipient email..." value="<?php echo esc_attr(get_option('admin_email')); ?>" style="flex:1;">
                        <button type="button" id="tv_smtp_test_btn" class="tv-btn tv-btn-secondary" style="height:38px; min-width:150px;">
                            Run SMTP Test
                        </button>
                    </div>
                    <div id="tv_smtp_test_result" style="margin-top:12px; font-size:12px; padding:12px; border-radius:8px; display:none; white-space:pre-wrap; font-family:monospace; line-height:1.4;"></div>
                </div>
            </div>

            <!-- SECTION: INCOMING CONFIGURATION (Metadata) -->
            <div style="background:var(--tv-surface-active); border:1px solid var(--tv-border); border-radius:12px; padding:20px;">
                <h4 style="margin:0 0 12px 0; font-size:12px; font-weight:800; color:var(--tv-text-muted); text-transform:uppercase;">Incoming Metadata (IMAP/POP3)</h4>
                <div class="tv-row">
                    <div class="tv-col">
                        <label class="tv-label">Incoming Host</label>
                        <input type="text" name="tv_imap_host" value="<?php echo esc_attr(get_option('tv_imap_host', 'xoflix.tv')); ?>" class="tv-input" placeholder="e.g. imap.xoflix.tv">
                    </div>
                    <div class="tv-col" style="flex:0 0 100px;">
                        <label class="tv-label">IMAP Port</label>
                        <input type="number" name="tv_imap_port" value="<?php echo esc_attr(get_option('tv_imap_port', 993)); ?>" class="tv-input">
                    </div>
                    <div class="tv-col" style="flex:0 0 100px;">
                        <label class="tv-label">POP3 Port</label>
                        <input type="number" name="tv_pop3_port" value="<?php echo esc_attr(get_option('tv_pop3_port', 995)); ?>" class="tv-input">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2: CURRENCY CONTROL (FOX/WOOCS) -->
    <div class="tv-card">
        <div class="tv-card-header">
            <h3><span class="dashicons dashicons-money-alt" style="margin-right:8px;"></span> Currency Control (FOX Integration)</h3>
        </div>
        <div class="tv-card-body">
            <input type="hidden" name="tv_allowed_currencies_check" value="1">
            <?php if ($woocs_available): ?>
                <p style="font-size:13px; color:var(--tv-text-muted); margin-bottom:15px;">Enable specific currencies for the Premium Plans checkout portal:</p>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap:12px;">
                    <?php 
                    global $WOOCS;
                    $currencies = $WOOCS->get_currencies();
                    foreach ($currencies as $code => $data): ?>
                        <label style="display:flex; align-items:center; gap:10px; background:#f8fafc; padding:12px; border-radius:12px; border:1px solid #e2e8f0; cursor:pointer; transition:0.2s;">
                            <input type="checkbox" name="tv_allowed_currencies[]" value="<?php echo esc_attr($code); ?>" <?php checked(in_array($code, $allowed_currencies)); ?> style="accent-color:var(--tv-primary); width:18px; height:18px;">
                            <div style="line-height:1.2;">
                                <strong style="font-size:14px; color:var(--tv-text);"><?php echo esc_html($code); ?></strong>
                                <div style="font-size:10px; color:var(--tv-text-muted);"><?php echo esc_html($data['name']); ?></div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                 <div style="background:var(--tv-warning-bg); border:1px solid #fde68a; padding:15px; border-radius:12px; color:#92400e; font-size:13px;">
                    <strong>FOX Currency Converter Not Found:</strong> Install the WOOCS plugin to unlock multi-currency pricing controls.
                 </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECTION 3: WASSENGER WHATSAPP API -->
    <div class="tv-card">
        <div class="tv-card-header">
            <h3><span class="dashicons dashicons-smartphone" style="margin-right:8px;"></span> Wassenger WhatsApp API</h3>
        </div>
        <div class="tv-card-body">
            <p style="font-size:13px; color:var(--tv-text-muted); margin-bottom:20px;">Automate WhatsApp alerts for expiring plans and renewal confirmations via Wassenger V1 API.</p>
            <div class="tv-form-group">
                <label class="tv-label">API Key</label>
                <input type="password" id="tv_wassenger_api_key" name="tv_wassenger_api_key" value="<?php echo esc_attr(get_option('tv_wassenger_api_key', '')); ?>" class="tv-input" placeholder="Enter Wassenger Token">
            </div>
            
            <div style="background:#f0f9ff; padding:20px; border-radius:12px; border:1px solid #bae6fd; margin-top:20px;">
                <label class="tv-label" style="color:#0369a1; margin-bottom:12px; font-weight:800;">Real-Time API Diagnostic</label>
                <div style="display:flex; gap:10px; align-items:center;">
                    <input type="text" id="tv_wassenger_test_num" class="tv-input" placeholder="+123..." value="<?php echo esc_attr(get_option('tv_support_whatsapp')); ?>" style="max-width:250px;">
                    <button type="button" id="tv_wassenger_test_btn" class="tv-btn tv-btn-secondary">
                        Test Delivery
                    </button>
                </div>
                <div id="tv_wassenger_test_result" style="margin-top:12px; font-size:13px; font-weight:600; padding:12px; border-radius:8px; display:none;"></div>
            </div>
        </div>
    </div>

    <!-- PERSISTENCE CONTROLS -->
    <div class="tv-card" style="position:sticky; bottom:20px; z-index:99; border-top: 4px solid var(--tv-primary); box-shadow: 0 -10px 30px rgba(0,0,0,0.08);">
        <div class="tv-card-body" style="display:flex; justify-content:flex-end; padding:15px 24px;">
            <button type="submit" name="save_settings" class="tv-btn tv-btn-primary" style="height:46px; padding:0 50px; font-weight:800; font-size:15px;">
                Save All Integration Profiles
            </button>
        </div>
    </div>
</form>

<script>
/**
 * Applies presets for popular SMTP services
 */
function applySmtpPreset(type) {
    const host = document.getElementById('smtp_host');
    const port = document.getElementById('smtp_port');
    const enc  = document.getElementById('smtp_enc');
    
    switch(type) {
        case 'ses-eu-north-1':
            host.value = 'email-smtp.eu-north-1.amazonaws.com';
            port.value = 587;
            enc.value  = 'tls';
            break;
        case 'ses-eu-west-1':
            host.value = 'email-smtp.eu-west-1.amazonaws.com';
            port.value = 587;
            enc.value  = 'tls';
            break;
        case 'ses-us-east-1':
            host.value = 'email-smtp.us-east-1.amazonaws.com';
            port.value = 587;
            enc.value  = 'tls';
            break;
        case 'ses-us-west-2':
            host.value = 'email-smtp.us-west-2.amazonaws.com';
            port.value = 587;
            enc.value  = 'tls';
            break;
        case 'ses-eu-central-1':
            host.value = 'email-smtp.eu-central-1.amazonaws.com';
            port.value = 587;
            enc.value  = 'tls';
            break;
        case 'google':
            host.value = 'smtp.gmail.com';
            port.value = 587;
            enc.value  = 'tls';
            break;
        case 'outlook':
            host.value = 'smtp.office365.com';
            port.value = 587;
            enc.value  = 'tls';
            break;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. SMTP DIAGNOSTIC LOGIC ---
    const smtpBtn = document.getElementById('tv_smtp_test_btn');
    if (smtpBtn) {
        smtpBtn.addEventListener('click', function() {
            const btn = this;
            const resDiv = document.getElementById('tv_smtp_test_result');
            
            btn.textContent = 'Probing Stockholm...';
            btn.disabled = true;
            resDiv.style.display = 'none';

            const formData = new FormData();
            formData.append('action', 'tv_test_smtp');
            formData.append('_nonce', '<?php echo wp_create_nonce("tv_test_smtp"); ?>');
            formData.append('test_email', document.getElementById('tv_smtp_test_email').value);
            formData.append('host', document.getElementById('smtp_host').value);
            formData.append('user', document.getElementById('smtp_user').value);
            formData.append('pass', document.getElementById('smtp_pass').value);
            formData.append('port', document.getElementById('smtp_port').value);
            formData.append('enc', document.getElementById('smtp_enc').value);
            formData.append('from_email', document.getElementById('smtp_from_email').value);
            formData.append('from_name', document.getElementById('smtp_from_name').value);
            formData.append('insecure', document.getElementById('smtp_insecure').checked ? 'true' : 'false');

            fetch(ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    resDiv.style.display = 'block';
                    if(data.success) {
                        resDiv.style.background = '#dcfce7';
                        resDiv.style.color = '#166534';
                        resDiv.style.border = '1px solid #bbf7d0';
                        resDiv.innerText = "RESULT: " + data.data.message;
                    } else {
                        resDiv.style.background = '#fee2e2';
                        resDiv.style.color = '#b91c1c';
                        resDiv.style.border = '1px solid #fecaca';
                        resDiv.innerText = data.data.message;
                    }
                })
                .catch(e => {
                    resDiv.style.display = 'block';
                    resDiv.style.background = '#fee2e2';
                    resDiv.innerText = "CRITICAL: The Stockholm AWS Handshake was blocked or timed out.";
                })
                .finally(() => {
                    btn.textContent = 'Run SMTP Test';
                    btn.disabled = false;
                });
        });
    }

    // --- 2. WASSENGER DIAGNOSTIC LOGIC ---
    const wassBtn = document.getElementById('tv_wassenger_test_btn');
    if (wassBtn) {
        wassBtn.addEventListener('click', function() {
            const apiKey = document.getElementById('tv_wassenger_api_key').value.trim();
            const phone = document.getElementById('tv_wassenger_test_num').value.trim();
            const resDiv = document.getElementById('tv_wassenger_test_result');
            
            if(!apiKey) { alert('Configuration Error: API Key missing.'); return; }
            
            this.textContent = 'Contacting Gateway...';
            this.disabled = true;
            resDiv.style.display = 'none';

            const formData = new FormData();
            formData.append('action', 'tv_test_wassenger');
            formData.append('api_key', apiKey);
            formData.append('phone', phone);
            formData.append('_nonce', '<?php echo wp_create_nonce("tv_test_wassenger"); ?>');

            fetch(ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    resDiv.style.display = 'block';
                    if(data.success) {
                        resDiv.style.background = '#dcfce7';
                        resDiv.style.color = '#166534';
                        resDiv.style.border = '1px solid #bbf7d0';
                        resDiv.innerText = "SUCCESS: " + data.data.message;
                    } else {
                        resDiv.style.background = '#fee2e2';
                        resDiv.style.color = '#b91c1c';
                        resDiv.style.border = '1px solid #fecaca';
                        resDiv.innerText = "GATEWAY ERROR: " + data.data.message;
                    }
                })
                .finally(() => { 
                    this.textContent = 'Test Delivery'; 
                    this.disabled = false;
                });
        });
    }
});
</script>