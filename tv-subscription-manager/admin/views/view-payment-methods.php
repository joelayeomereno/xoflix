<?php
// Determine View State (Add vs Edit)
$is_edit = !empty($edit_method);
$form_title = $is_edit ? 'Edit Payment Method' : 'Add New Method';
$btn_text = $is_edit ? 'Update Method' : 'Save Method';

// Pre-fill values
$val_name = $is_edit ? $edit_method->name : '';
$val_slug = $is_edit ? $edit_method->slug : '';
$val_logo_url = $is_edit && isset($edit_method->logo_url) ? $edit_method->logo_url : '';
$val_bank_name = $is_edit && isset($edit_method->bank_name) ? $edit_method->bank_name : '';
$val_account_name = $is_edit && isset($edit_method->account_name) ? $edit_method->account_name : '';
$val_account_number = $is_edit && isset($edit_method->account_number) ? $edit_method->account_number : '';
$val_countries = $is_edit ? $edit_method->countries : '';
$val_currencies = $is_edit ? $edit_method->currencies : '';
$val_instructions = $is_edit ? $edit_method->instructions : '';
$val_link = $is_edit ? $edit_method->link : '';
// Behavior is now hardcoded to 'window' in backend, removed from UI
$val_status = $is_edit ? $edit_method->status : 'active';
$val_order = $is_edit ? $edit_method->display_order : 0;
$val_notes = $is_edit ? $edit_method->notes : '';

// Optional Flutterwave settings
$val_fw_enabled = ($is_edit && isset($edit_method->flutterwave_enabled)) ? intval($edit_method->flutterwave_enabled) : 0;
$val_fw_secret = ($is_edit && isset($edit_method->flutterwave_secret_key)) ? $edit_method->flutterwave_secret_key : '';
$val_fw_public = ($is_edit && isset($edit_method->flutterwave_public_key)) ? $edit_method->flutterwave_public_key : '';
$val_fw_currency = ($is_edit && isset($edit_method->flutterwave_currency) && !empty($edit_method->flutterwave_currency)) ? $edit_method->flutterwave_currency : 'USD';
$val_fw_title = ($is_edit && isset($edit_method->flutterwave_title)) ? $edit_method->flutterwave_title : '';
$val_fw_logo = ($is_edit && isset($edit_method->flutterwave_logo)) ? $edit_method->flutterwave_logo : '';
?>

<div class="tv-page-header">
    <div>
        <h1>Payment Methods</h1>
        <p>Define manual payment links and instructions with smart geo-targeting.</p>
    </div>
    <?php if($is_edit): ?>
    <div>
        <a href="?page=tv-subs-manager&tab=methods" class="tv-btn tv-btn-secondary">
            <span class="dashicons dashicons-arrow-left-alt" style="margin-right:6px;"></span> Cancel Edit
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="tv-grid-2">
    <!-- Form -->
    <div class="tv-card" id="method-form-card">
        <div class="tv-card-header">
            <h3><?php echo esc_html($form_title); ?></h3>
        </div>
        <div class="tv-card-body">
            <form method="post" action="?page=tv-subs-manager&tab=methods" id="tv-method-form">
                <?php wp_nonce_field('save_method_verify'); ?>
                
                <?php if($is_edit): ?>
                    <input type="hidden" name="method_id" value="<?php echo esc_attr($edit_method->id); ?>">
                <?php endif; ?>
                
                <div class="tv-form-group">
                    <label class="tv-label">Method Name</label>
                    <input type="text" name="method_name" required class="tv-input" value="<?php echo esc_attr($val_name); ?>" placeholder="e.g. Pay with Naira (Bank Transfer)">
                </div>
                
                <div class="tv-form-group">
                    <label class="tv-label">Slug (Internal ID)</label>
                    <input type="text" name="method_slug" required class="tv-input" value="<?php echo esc_attr($val_slug); ?>" placeholder="e.g. pay-naira-bank">
                </div>

                <div class="tv-form-group">
                    <label class="tv-label">Logo URL (optional)</label>
                    <input type="url" name="method_logo_url" class="tv-input" value="<?php echo esc_attr($val_logo_url); ?>" placeholder="https://example.com/logo.png">
                    <small style="color:var(--tv-text-muted);">Used on the user checkout as a branded payment card.</small>
                </div>

                <!-- Structured Bank Details -->
                <div class="tv-form-group" style="background:var(--tv-surface-active); padding:15px; border-radius:8px; border:1px solid var(--tv-border);">
                    <label class="tv-label" style="margin-bottom:10px;">Bank / Transfer Details (Optional)</label>
                    <div class="tv-row">
                        <div class="tv-col">
                            <label class="tv-label">Bank Name</label>
                            <input type="text" name="method_bank_name" class="tv-input" value="<?php echo esc_attr($val_bank_name); ?>" placeholder="e.g. Access Bank">
                        </div>
                        <div class="tv-col">
                            <label class="tv-label">Account Name</label>
                            <input type="text" name="method_account_name" class="tv-input" value="<?php echo esc_attr($val_account_name); ?>" placeholder="e.g. XOFLIX LTD">
                        </div>
                    </div>
                    <div class="tv-form-group" style="margin-top:10px; position: relative;">
                        <label class="tv-label">Account Number</label>
                        <div style="display:flex; gap:8px;">
                            <input type="text" id="acc_num_field" name="method_account_number" class="tv-input" value="<?php echo esc_attr($val_account_number); ?>" placeholder="e.g. 0123456789">
                            <button type="button" class="tv-btn tv-btn-secondary" onclick="copyField('acc_num_field')" title="Test Copy"><span class="dashicons dashicons-admin-page"></span></button>
                        </div>
                    </div>
                    <p style="font-size:12px; color:var(--tv-text-muted); margin-top:6px;">Leave blank if this payment method is a gateway/link-only method.</p>
                </div>

                <!-- SMART COUNTRY SELECTOR -->
                <div class="tv-form-group" style="background:var(--tv-surface-active); padding:15px; border-radius:8px; border:1px solid var(--tv-border);">
                    <label class="tv-label">Target Countries</label>
                    
                    <!-- Hidden Real Input -->
                    <input type="hidden" name="method_countries" id="real_countries_input" value="<?php echo esc_attr($val_countries); ?>">
                    
                    <!-- Selected Tags Area -->
                    <div id="country-tags" style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:8px;"></div>
                    
                    <!-- Search Input -->
                    <div style="position:relative;">
                        <input type="text" id="country-search" class="tv-input" placeholder="Type a country name (e.g. Rwanda, USA)...">
                        <div id="country-dropdown" style="display:none; position:absolute; top:100%; left:0; width:100%; max-height:200px; overflow-y:auto; background:white; border:1px solid var(--tv-border); border-radius:8px; z-index:100; box-shadow:0 4px 12px rgba(0,0,0,0.1); margin-top:4px;"></div>
                    </div>

                    <p style="font-size:12px; color:var(--tv-text-muted); margin-top:6px;">
                        <span class="dashicons dashicons-globe" style="font-size:14px; margin-top:2px;"></span>
                        <strong>Global Mode:</strong> Leave empty to show this method to <u>everyone</u> worldwide.
                    </p>
                </div>

                <div class="tv-row">
                    <div class="tv-col">
                        <label class="tv-label">Accepted Currencies</label>
                        <input type="text" name="method_currencies" class="tv-input" value="<?php echo esc_attr($val_currencies); ?>" placeholder="USD, EUR, NGN">
                    </div>
                    <div class="tv-col">
                        <label class="tv-label">Display Order</label>
                        <input type="number" name="method_order" class="tv-input" value="<?php echo esc_attr($val_order); ?>">
                    </div>
                </div>

                <div class="tv-form-group">
                    <label class="tv-label">Payment Instructions (HTML allowed)</label>
                    <textarea name="method_instructions" class="tv-textarea" rows="4" placeholder="Transfer to: Access Bank..."><?php echo esc_textarea($val_instructions); ?></textarea>
                </div>

                <div class="tv-form-group">
                    <label class="tv-label">Payment Link / URL</label>
                    <input type="url" name="method_link" class="tv-input" value="<?php echo esc_attr($val_link); ?>" placeholder="https://paystack.com/pay/...">
                    <small style="color:var(--tv-text-muted);">User will be redirected to this link in a new tab.</small>
                </div>

                <!-- Flutterwave runtime checkout -->
                <div class="tv-form-group" style="background:var(--tv-surface-active); padding:15px; border-radius:8px; border:1px solid var(--tv-border);">
                    <label class="tv-label" style="margin-bottom:10px;">Flutterwave Runtime Checkout (Optional)</label>
                    <label class="tv-switch" style="font-weight:600;">
                        <input type="checkbox" id="fw_enabled" name="flutterwave_enabled" value="1" <?php checked($val_fw_enabled, 1); ?> class="tv-toggle-input" />
                        <span class="tv-toggle-ui" aria-hidden="true"></span>
                        <span>Enable Flutterwave dynamic checkout for this method</span>
                    </label>
                    <p style="font-size:12px; color:var(--tv-text-muted); margin-top:8px;">
                        When enabled, the system will generate a <strong>fresh</strong> hosted checkout link per attempt using Flutterwave v3 API.
                        The existing Payment Link field above is ignored for Flutterwave-enabled methods.
                    </p>

                    <div class="tv-row" style="margin-top:12px;">
                        <div class="tv-col">
                            <label class="tv-label">Secret Key</label>
                            <input type="password" name="flutterwave_secret_key" class="tv-input" value="<?php echo esc_attr($val_fw_secret); ?>" placeholder="FLWSECK-...">
                            <small style="color:var(--tv-text-muted);">Required when enabled.</small>
                        </div>
                        <div class="tv-col">
                            <label class="tv-label">Public Key (optional)</label>
                            <input type="text" name="flutterwave_public_key" class="tv-input" value="<?php echo esc_attr($val_fw_public); ?>" placeholder="FLWPUBK-...">
                        </div>
                    </div>

                    <div class="tv-row" style="margin-top:10px;">
                        <div class="tv-col">
                            <label class="tv-label">Currency</label>
                            <input type="text" name="flutterwave_currency" class="tv-input" value="<?php echo esc_attr($val_fw_currency ?: 'USD'); ?>" placeholder="USD">
                        </div>
                        <div class="tv-col">
                            <label class="tv-label">Checkout Title (optional)</label>
                            <input type="text" name="flutterwave_title" class="tv-input" value="<?php echo esc_attr($val_fw_title); ?>" placeholder="XOFLIX TV Subscription">
                        </div>
                    </div>

                    <div class="tv-form-group" style="margin-top:10px;">
                        <label class="tv-label">Checkout Logo URL (optional)</label>
                        <input type="url" name="flutterwave_logo" class="tv-input" value="<?php echo esc_attr($val_fw_logo); ?>" placeholder="https://example.com/logo.png">
                    </div>
                </div>

                <div class="tv-form-group">
                    <label class="tv-label">Status</label>
                    <select name="method_status" class="tv-input">
                        <option value="active" <?php selected($val_status, 'active'); ?>>Active</option>
                        <option value="inactive" <?php selected($val_status, 'inactive'); ?>>Inactive</option>
                    </select>
                </div>

                <div class="tv-form-group">
                    <label class="tv-label">Internal Notes</label>
                    <textarea name="method_notes" class="tv-textarea" rows="2"><?php echo esc_textarea($val_notes); ?></textarea>
                </div>

                <button type="submit" name="save_method" class="tv-btn tv-btn-primary w-full" style="height:38px; justify-content:center;">
                    <?php echo esc_html($btn_text); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- List -->
    <div class="tv-card">
        <div class="tv-card-header">
            <h3>Active Methods</h3>
        </div>
        <div class="tv-table-container">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Target Region</th>
                        <th>Status</th>
                        <th align="right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($methods): foreach($methods as $m): 
                        $edit_url = '?page=tv-subs-manager&tab=methods&action=edit&id='.$m->id;
                        $delete_url = wp_nonce_url('?page=tv-subs-manager&tab=methods&action=delete_method&id='.$m->id, 'delete_method_'.$m->id);
                        $is_active_row = ($is_edit && $edit_method->id == $m->id);
                        $row_style = $is_active_row ? 'background:var(--tv-surface-active);' : '';
                        
                        $country_display = '<span class="tv-badge free">Global</span>';
                        if (!empty($m->countries)) {
                            $codes = explode(',', $m->countries);
                            $count = count($codes);
                            if($count > 2) {
                                $country_display = '<span class="tv-badge" style="background:#dbeafe; color:#1e40af;">' . esc_html($codes[0] . ', ' . $codes[1]) . ' +'.($count-2).'</span>';
                            } else {
                                $country_display = '<span class="tv-badge" style="background:#dbeafe; color:#1e40af;">' . esc_html($m->countries) . '</span>';
                            }
                        }
                    ?>
                    <tr style="<?php echo $row_style; ?>">
                        <td>
                            <div style="font-weight:600;"><?php echo esc_html($m->name); ?></div>
                            <div style="font-size:11px; color:var(--tv-text-muted);"><?php echo esc_html($m->slug); ?></div>
                        </td>
                        <td>
                            <?php echo $country_display; ?>
                        </td>
                        <td>
                            <span class="tv-badge <?php echo $m->status; ?>"><?php echo ucfirst($m->status); ?></span>
                        </td>
                        <td align="right">
                            <div style="display:flex; justify-content:flex-end; gap:6px;">
                                <a href="<?php echo $edit_url; ?>" class="tv-btn tv-btn-sm tv-btn-secondary">Edit</a>
                                <!-- FIXED: Added data-tv-delete="1" to enforce JS interception -->
                                <a href="<?php echo $delete_url; ?>" class="tv-btn tv-btn-danger tv-btn-sm" data-tv-delete="1">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="4" style="text-align:center; padding:30px; color:var(--tv-text-muted);">No payment methods defined.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.getElementById('tv-method-form').addEventListener('submit', function() {
        const btn = this.querySelector('button[type="submit"]');
        btn.innerHTML = '<span class="dashicons dashicons-update" style="animation:spin 2s infinite linear; margin-right:6px;"></span> Saving...';
        btn.style.opacity = '0.7';
        btn.style.pointerEvents = 'none';
    });
    
    function copyField(id) {
        const el = document.getElementById(id);
        if(el && el.value) {
            navigator.clipboard.writeText(el.value).then(() => {
                alert('Copied: ' + el.value);
            });
        }
    }
    
    if (!document.getElementById('tv-spin-style')) {
        const style = document.createElement('style');
        style.id = 'tv-spin-style';
        style.innerHTML = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    }

    // --- SMART COUNTRY SELECTOR LOGIC ---
    (function(){
        const countries = [
            // Americas
            {c:'US',n:'United States'},{c:'CA',n:'Canada'},{c:'MX',n:'Mexico'},{c:'BR',n:'Brazil'},
            {c:'AR',n:'Argentina'},{c:'CO',n:'Colombia'},{c:'CL',n:'Chile'},{c:'PE',n:'Peru'},
            {c:'EC',n:'Ecuador'},{c:'VE',n:'Venezuela'},{c:'DO',n:'Dominican Republic'},
            
            // Europe
            {c:'GB',n:'United Kingdom'},{c:'DE',n:'Germany'},{c:'FR',n:'France'},{c:'ES',n:'Spain'},
            {c:'IT',n:'Italy'},{c:'NL',n:'Netherlands'},{c:'SE',n:'Sweden'},{c:'NO',n:'Norway'},
            {c:'DK',n:'Denmark'},{c:'FI',n:'Finland'},{c:'IE',n:'Ireland'},{c:'PL',n:'Poland'},
            {c:'PT',n:'Portugal'},{c:'GR',n:'Greece'},{c:'CH',n:'Switzerland'},{c:'BE',n:'Belgium'},
            {c:'AT',n:'Austria'},{c:'CZ',n:'Czechia'},{c:'HU',n:'Hungary'},{c:'RO',n:'Romania'},
            {c:'BG',n:'Bulgaria'},{c:'RS',n:'Serbia'},{c:'HR',n:'Croatia'},{c:'RU',n:'Russia'},
            {c:'TR',n:'Turkey'},{c:'UA',n:'Ukraine'},
            
            // Africa
            {c:'RW',n:'Rwanda'},{c:'NG',n:'Nigeria'},{c:'GH',n:'Ghana'},{c:'KE',n:'Kenya'},
            {c:'ZA',n:'South Africa'},{c:'EG',n:'Egypt'},{c:'MA',n:'Morocco'},{c:'DZ',n:'Algeria'},
            {c:'UG',n:'Uganda'},{c:'TZ',n:'Tanzania'},{c:'ET',n:'Ethiopia'},{c:'CM',n:'Cameroon'},
            {c:'CI',n:'Ivory Coast'},{c:'SN',n:'Senegal'},{c:'ZM',n:'Zambia'},{c:'ZW',n:'Zimbabwe'},
            {c:'AO',n:'Angola'},{c:'MZ',n:'Mozambique'},{c:'TN',n:'Tunisia'},{c:'MU',n:'Mauritius'},
            
            // Asia & Middle East
            {c:'IN',n:'India'},{c:'CN',n:'China'},{c:'JP',n:'Japan'},{c:'KR',n:'South Korea'},
            {c:'SG',n:'Singapore'},{c:'MY',n:'Malaysia'},{c:'ID',n:'Indonesia'},{c:'TH',n:'Thailand'},
            {c:'PH',n:'Philippines'},{c:'VN',n:'Vietnam'},{c:'PK',n:'Pakistan'},{c:'BD',n:'Bangladesh'},
            {c:'AE',n:'UAE'},{c:'SA',n:'Saudi Arabia'},{c:'QA',n:'Qatar'},{c:'KW',n:'Kuwait'},
            {c:'IL',n:'Israel'},{c:'OM',n:'Oman'},{c:'BH',n:'Bahrain'},{c:'LB',n:'Lebanon'},
            
            // Oceania
            {c:'AU',n:'Australia'},{c:'NZ',n:'New Zealand'}
        ];

        const searchInput = document.getElementById('country-search');
        const hiddenInput = document.getElementById('real_countries_input');
        const tagsContainer = document.getElementById('country-tags');
        const dropdown = document.getElementById('country-dropdown');

        let selected = hiddenInput.value ? hiddenInput.value.split(',').map(s=>s.trim()).filter(s=>s) : [];

        function renderTags() {
            tagsContainer.innerHTML = '';
            selected.forEach(code => {
                const country = countries.find(c => c.c === code) || {c: code, n: code};
                const tag = document.createElement('div');
                tag.style.cssText = 'background:#e0e7ff; color:#3730a3; padding:4px 10px; border-radius:12px; font-size:12px; font-weight:600; display:flex; align-items:center; gap:6px; border:1px solid #c7d2fe;';
                tag.innerHTML = `
                    <img src="https://flagcdn.com/w20/${country.c.toLowerCase()}.png" style="width:14px; height:auto; border-radius:2px;">
                    ${country.n}
                    <span style="cursor:pointer; margin-left:2px; opacity:0.6; font-size:14px;" onclick="removeCountry('${country.c}')">&times;</span>
                `;
                tagsContainer.appendChild(tag);
            });
            hiddenInput.value = selected.join(',');
        }

        window.removeCountry = function(code) {
            selected = selected.filter(c => c !== code);
            renderTags();
        };

        function addCountry(code) {
            if(!selected.includes(code)) {
                selected.push(code);
                renderTags();
            }
            searchInput.value = '';
            dropdown.style.display = 'none';
        }

        searchInput.addEventListener('input', function() {
            const term = this.value.toUpperCase();
            dropdown.innerHTML = '';
            
            if(term.length === 0) {
                dropdown.style.display = 'none';
                return;
            }

            const matches = countries.filter(c => (c.n.toUpperCase().includes(term) || c.c.includes(term)) && !selected.includes(c.c));
            
            if(matches.length > 0) {
                dropdown.style.display = 'block';
                matches.forEach(c => {
                    const item = document.createElement('div');
                    item.style.cssText = 'padding:10px 12px; cursor:pointer; display:flex; align-items:center; gap:10px; hover:bg-gray-100; font-size:13px; border-bottom:1px solid #f1f5f9;';
                    item.onmouseover = function(){ this.style.backgroundColor = '#f8fafc'; };
                    item.onmouseout = function(){ this.style.backgroundColor = 'white'; };
                    item.innerHTML = `<img src="https://flagcdn.com/w40/${c.c.toLowerCase()}.png" style="width:20px;"> <strong>${c.n}</strong> <span style="color:#94a3b8; margin-left:auto; font-size:11px;">${c.c}</span>`;
                    item.onclick = () => addCountry(c.c);
                    dropdown.appendChild(item);
                });
            } else {
                dropdown.style.display = 'none';
            }
        });

        // Close dropdown on click outside
        document.addEventListener('click', function(e) {
            if(e.target !== searchInput && e.target.closest('#country-dropdown') === null) {
                dropdown.style.display = 'none';
            }
        });

        // Initial render
        renderTags();
    })();
</script>