<?php
if ( ! defined( 'ABSPATH' ) ) exit;

trait TV_Public_Shortcode_Payment_Lockdown_Trait {

    public function shortcode_payment_lockdown($atts) {
            if (!is_user_logged_in()) return 'Please log in.';
            $user_id = get_current_user_id();
            $pay_id = (int)get_user_meta($user_id, '_tv_active_pay_id', true);
    
            // Fallback: If no meta, check GET param
            if (!$pay_id && isset($_GET['pay_id'])) {
                $pay_id = intval($_GET['pay_id']);
            }
    
            if (!$pay_id) {
                return '<script>window.location.href="' . home_url('/dashboard') . '";</script>';
            }
    
            global $wpdb;
            $payment = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tv_payments WHERE id = %d AND user_id = %d", $pay_id, $user_id));
            
            // Allowed statuses for lockdown
            $locking_statuses = ['IN_PROGRESS', 'AWAITING_PROOF', 'pending', 'PENDING_ADMIN_REVIEW'];
            
            if (!$payment || !in_array($payment->status, $locking_statuses)) {
                 delete_user_meta($user_id, '_tv_active_pay_id');
                 return '<script>window.location.href="' . home_url('/dashboard') . '";</script>';
            }
    
            $method = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tv_payment_methods WHERE name = %s", $payment->method));
            $gateway_link = ($method && !empty($method->link)) ? $method->link : '';
            
            // Flutterwave specific handling
            if ($method && !empty($method->flutterwave_enabled)) {
                 $gateway_link = '#fw-trigger'; 
            }
    
            // [UPGRADE] Manual Bank Details Logic
            $bank_details = null;
            if ($method && empty($gateway_link)) {
                // Check if any bank fields are populated
                if (!empty($method->bank_name) || !empty($method->account_number) || !empty($method->instructions)) {
                    $bank_details = [
                        'bank_name' => $method->bank_name,
                        'account_name' => $method->account_name,
                        'account_number' => $method->account_number,
                        'instructions' => $method->instructions
                    ];
                }
            }
            $has_manual_details = !empty($bank_details);
    
            // [FOX] Format Amount
            $currency_code = isset($payment->currency) ? $payment->currency : 'USD';
            $display_amount = $payment->amount; 
            
            if (method_exists($this, 'get_currency_data')) {
                $cdata = $this->get_currency_data(0); 
                if ($cdata['code'] === $currency_code) {
                    // [FIX] Integer Format (1,000)
                    $display_amount = $cdata['symbol'] . number_format($payment->amount, 0);
                } else {
                    $display_amount = $payment->amount . ' ' . $currency_code;
                }
            } else {
                 $display_amount = $payment->amount . ' ' . $currency_code;
            }
    
            $upload_url = add_query_arg(['pay_id' => $pay_id], add_query_arg('tv_flow', 'upload_proof', home_url('/')));
            $fw_nonce = wp_create_nonce('tv_flutterwave_init_' . $pay_id);
    
            ob_start();
            ?>
            <!-- SCOPED LOCKDOWN STYLES -->
            <style>
                /* CRITICAL: Hard Reset & Layout Protection */
                html, body {
                    background: #f8fafc !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    height: 100% !important;
                    width: 100% !important;
                    overflow: hidden !important; 
                }
    
                @keyframes tvFadeIn {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
    
                @keyframes tvSpin { 
                    to { transform: rotate(360deg); } 
                }
    
                #tv-lockdown-root { 
                    position: fixed; 
                    top: 0; left: 0; right: 0; bottom: 0;
                    z-index: 2147483647; 
                    background: #f8fafc; 
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    color: #0f172a;
                    overflow-y: auto;
                    -webkit-overflow-scrolling: touch;
                }
    
                /* --- GLOBAL ACTION LOADER (Overlay) --- */
                #tv-action-loader {
                    position: fixed;
                    inset: 0;
                    z-index: 2147483648; /* Higher than root */
                    background: rgba(255, 255, 255, 0.85);
                    backdrop-filter: blur(6px);
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    opacity: 0;
                    pointer-events: none;
                    transition: opacity 0.3s ease;
                }
                #tv-action-loader.tv-loader-visible {
                    opacity: 1;
                    pointer-events: all;
                }
                .tv-loader-spinner {
                    width: 48px;
                    height: 48px;
                    border: 4px solid rgba(59, 130, 246, 0.15);
                    border-top-color: #3b82f6;
                    border-radius: 50%;
                    animation: tvSpin 0.8s linear infinite;
                    margin-bottom: 16px;
                }
                .tv-loader-text {
                    font-size: 15px;
                    font-weight: 600;
                    color: #334155;
                    letter-spacing: -0.01em;
                }
    
                /* Wrapper */
                .tv-ld-wrapper { 
                    min-height: 100%; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    padding: 20px; 
                    background: radial-gradient(circle at 50% 0%, #eef2ff 0%, #f8fafc 100%);
                }
    
                /* Card with CSS Animation */
                .tv-ld-card { 
                    background: #ffffff; 
                    width: 100%; 
                    max-width: 480px; 
                    border-radius: 24px; 
                    box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(0,0,0,0.03); 
                    overflow: hidden; 
                    position: relative; 
                    animation: tvFadeIn 0.5s ease-out forwards;
                    opacity: 0; 
                }
    
                .tv-ld-header { background: #0f172a; color: white; padding: 40px 32px; text-align: center; position: relative; overflow: hidden; }
                .tv-ld-header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 100%); pointer-events: none; }
                
                .tv-ld-icon-pulse { width: 64px; height: 64px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; position: relative; }
                .tv-ld-icon-pulse::after { content: ''; position: absolute; inset: -8px; border: 1px solid rgba(255,255,255,0.15); border-radius: 50%; animation: tvPulse 2s infinite; }
                
                .tv-ld-status { display: inline-block; padding: 6px 14px; background: rgba(255,255,255,0.15); border-radius: 99px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; backdrop-filter: blur(4px); }
                .tv-ld-price { font-size: 36px; font-weight: 800; letter-spacing: -1px; line-height: 1; margin-bottom: 8px; }
                .tv-ld-ref { font-family: monospace; opacity: 0.6; font-size: 13px; }
                
                .tv-ld-body { padding: 32px; }
                .tv-ld-tabs { display: flex; background: #f1f5f9; padding: 4px; border-radius: 12px; margin-bottom: 24px; }
                .tv-ld-tab { flex: 1; text-align: center; padding: 10px; font-size: 13px; font-weight: 600; border-radius: 8px; cursor: pointer; transition: all 0.2s; color: #64748b; }
                .tv-ld-tab.active { background: white; color: #0f172a; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
                
                .tv-btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 16px; border-radius: 14px; font-weight: 700; font-size: 15px; cursor: pointer; transition: all 0.2s; border: none; text-decoration: none; }
                .tv-btn-primary { background: #3b82f6; color: white; box-shadow: 0 8px 20px -6px rgba(59, 130, 246, 0.5); }
                .tv-btn-primary:hover { background: #2563eb; transform: translateY(-2px); }
                .tv-btn-outline { background: white; border: 2px solid #e2e8f0; color: #64748b; margin-top: 12px; }
                .tv-btn-outline:hover { border-color: #cbd5e1; background: #f8fafc; color: #334155; }
                
                .tv-upload-box { border: 2px dashed #cbd5e1; border-radius: 16px; padding: 30px 20px; text-align: center; cursor: pointer; transition: all 0.2s; background: #f8fafc; position: relative; }
                .tv-upload-box:hover { border-color: #3b82f6; background: #eff6ff; }
                .tv-upload-box input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
                .tv-file-list { margin-top: 16px; text-align: left; display: none; }
                .tv-file-item { display: flex; align-items: center; gap: 10px; background: #f1f5f9; padding: 10px; border-radius: 10px; margin-bottom: 8px; font-size: 13px; animation: slideIn 0.2s ease-out; }
                @keyframes slideIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
                
                .tv-spinner { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: tvSpin 1s linear infinite; display: none; }
            </style>
    
            <div id="tv-lockdown-root">
                
                <!-- Global Overlay Loader -->
                <div id="tv-action-loader">
                    <div class="tv-loader-spinner"></div>
                    <div class="tv-loader-text" id="tv-loader-msg">Processing...</div>
                </div>
    
                <div class="tv-ld-wrapper">
                    <div class="tv-ld-card">
                        <div class="tv-ld-header">
                            <div class="tv-ld-status">Payment Pending</div>
                            <div class="tv-ld-icon-pulse"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:white;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></div>
                            <div class="tv-ld-price"><?php echo esc_html($display_amount); ?></div>
                            <div class="tv-ld-ref">Ref: #INV-<?php echo str_pad($payment->id, 5, '0', STR_PAD_LEFT); ?></div>
                        </div>
                        <div class="tv-ld-body">
                            
                            <!-- TAB NAVIGATION (Dynamic Labels) -->
                            <div class="tv-ld-tabs">
                                <div class="tv-ld-tab <?php echo ($gateway_link || $has_manual_details) ? 'active' : ''; ?>" onclick="switchTab('pay')" id="tab-pay">
                                    <?php echo $gateway_link ? 'Pay Online' : 'Payment Details'; ?>
                                </div>
                                <div class="tv-ld-tab <?php echo (!$gateway_link && !$has_manual_details) ? 'active' : ''; ?>" onclick="switchTab('upload')" id="tab-upload">
                                    Upload Proof
                                </div>
                            </div>
    
                            <!-- 1. VIEW: PAYMENT / DETAILS -->
                            <div id="view-pay" style="display: <?php echo ($gateway_link || $has_manual_details) ? 'block' : 'none'; ?>;">
                                
                                <!-- A. GATEWAY LINK -->
                                <?php if($gateway_link): ?>
                                    <div style="text-align:center; color:#64748b; font-size:14px; margin-bottom:24px; line-height:1.5;">
                                        Click below to complete your secure payment. We will automatically detect when you are done.
                                    </div>
                                    <?php if($gateway_link === '#fw-trigger'): ?>
                                        <button id="tv-fw-btn" class="tv-btn tv-btn-primary"><span class="tv-spinner"></span><span>Proceed to Payment</span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></button>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url($gateway_link); ?>" target="_blank" class="tv-btn tv-btn-primary"><span>Open Payment Gateway</span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></a>
                                    <?php endif; ?>
                                
                                <!-- B. MANUAL BANK DETAILS -->
                                <?php elseif($has_manual_details): ?>
                                    <div style="background:#f8fafc; padding:20px; border-radius:12px; border:1px solid #e2e8f0; text-align:left; margin-bottom:20px;">
                                        <h4 style="margin:0 0 16px 0; font-size:13px; font-weight:700; color:#334155; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid #e2e8f0; padding-bottom:8px;">Bank Transfer Details</h4>
                                        
                                        <?php if(!empty($bank_details['bank_name'])): ?>
                                        <div style="margin-bottom:12px;">
                                            <span style="display:block; font-size:11px; color:#64748b; font-weight:600; margin-bottom:2px;">BANK NAME</span>
                                            <span style="font-size:15px; color:#0f172a; font-weight:700;"><?php echo esc_html($bank_details['bank_name']); ?></span>
                                        </div>
                                        <?php endif; ?>
    
                                        <?php if(!empty($bank_details['account_number'])): ?>
                                        <div style="margin-bottom:12px;">
                                            <span style="display:block; font-size:11px; color:#64748b; font-weight:600; margin-bottom:2px;">ACCOUNT NUMBER</span>
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <span style="font-size:20px; color:#0f172a; font-weight:700; font-family:monospace; background:#fff; padding:4px 8px; border-radius:6px; border:1px solid #cbd5e1;"><?php echo esc_html($bank_details['account_number']); ?></span>
                                                <button type="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($bank_details['account_number']); ?>'); alert('Account Number Copied!');" style="background:#dbeafe; border:none; color:#1e40af; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:11px; font-weight:700;">COPY</button>
                                            </div>
                                        </div>
                                        <?php endif; ?>
    
                                        <?php if(!empty($bank_details['account_name'])): ?>
                                        <div style="margin-bottom:12px;">
                                            <span style="display:block; font-size:11px; color:#64748b; font-weight:600; margin-bottom:2px;">ACCOUNT NAME</span>
                                            <span style="font-size:14px; color:#0f172a; font-weight:500;"><?php echo esc_html($bank_details['account_name']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if(!empty($bank_details['instructions'])): ?>
                                        <div style="margin-top:16px; padding-top:16px; border-top:1px dashed #cbd5e1; font-size:13px; color:#475569; line-height:1.5;">
                                            <?php echo wp_kses_post($bank_details['instructions']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <p style="text-align:center; color:#64748b; font-size:13px;">After transferring, please switch to the <strong>Upload Proof</strong> tab.</p>
                                <?php endif; ?>
                            </div>
    
                            <!-- 2. VIEW: UPLOAD PROOF -->
                            <div id="view-upload" style="display: <?php echo (!$gateway_link && !$has_manual_details) ? 'block' : 'none'; ?>;">
                                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url($upload_url); ?>" id="proof-form">
                                    <input type="hidden" name="payment_proof_submit" value="1">
                                    <input type="hidden" name="payment_id" value="<?php echo esc_attr($pay_id); ?>">
                                    <div class="tv-upload-box">
                                        <input type="file" name="payment_proof[]" id="file-input" multiple accept="image/*,.pdf" required onchange="handleFiles(this)">
                                        <div style="color:#3b82f6; margin-bottom:8px;"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin:0 auto;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div>
                                        <div style="font-weight:600; color:#0f172a; font-size:14px;">Click to Upload Proof</div>
                                        <div style="font-size:12px; color:#94a3b8; margin-top:4px;">Select one or more files (JPG, PNG, PDF)</div>
                                    </div>
                                    <div class="tv-file-list" id="file-list"></div>
                                    <button type="submit" class="tv-btn tv-btn-primary" style="margin-top:20px; display:none;" id="upload-btn">Submit Proofs</button>
                                </form>
                            </div>
                            <button id="tv-cancel-btn" class="tv-btn tv-btn-outline">Cancel Transaction</button>
                        </div>
                    </div>
                </div>
            </div>
    
            <script>
            // --- 1. LOADER CONTROLLER ---
            const overlayLoader = document.getElementById('tv-action-loader');
            const overlayText = document.getElementById('tv-loader-msg');
    
            window.showGlobalLoader = function(msg) {
                overlayText.textContent = msg || 'Processing...';
                overlayLoader.classList.add('tv-loader-visible');
            };
    
            window.hideGlobalLoader = function() {
                overlayLoader.classList.remove('tv-loader-visible');
            };
    
            // --- 2. TABS & FILES ---
            window.switchTab = function(tab) {
                document.querySelectorAll('.tv-ld-tab').forEach(el => el.classList.remove('active'));
                document.getElementById('tab-' + tab).classList.add('active');
                document.getElementById('view-pay').style.display = (tab === 'pay') ? 'block' : 'none';
                document.getElementById('view-upload').style.display = (tab === 'upload') ? 'block' : 'none';
            };
    
            window.handleFiles = function(input) {
                const list = document.getElementById('file-list');
                const btn = document.getElementById('upload-btn');
                list.innerHTML = '';
                if (input.files.length > 0) {
                    list.style.display = 'block';
                    btn.style.display = 'flex';
                    Array.from(input.files).forEach(file => {
                        const div = document.createElement('div');
                        div.className = 'tv-file-item';
                        div.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#64748b;"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg><span style="flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${file.name}</span><span style="color:#94a3b8; font-size:11px;">${(file.size/1024).toFixed(0)}KB</span>`;
                        list.appendChild(div);
                    });
                } else { list.style.display = 'none'; btn.style.display = 'none'; }
            };
    
            (function(){
                // --- 3. AUTO-POLL STATUS ---
                const poller = setInterval(async () => {
                    try {
                        const fd = new FormData();
                        fd.append('action', 'tv_check_transaction_status');
                        fd.append('pay_id', '<?php echo $pay_id; ?>');
                        const res = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd });
                        const json = await res.json();
                        if(json.success && json.data.is_completed) { 
                            clearInterval(poller);
                            window.showGlobalLoader('Payment Confirmed!');
                            setTimeout(() => window.location.href = json.data.redirect_url, 800);
                        }
                    } catch(e){}
                }, 4000);
    
                // --- 4. FLUTTERWAVE INIT ---
                const fwBtn = document.getElementById('tv-fw-btn');
                if(fwBtn) {
                    fwBtn.addEventListener('click', async () => {
                        window.showGlobalLoader('Initializing Gateway...');
                        try {
                            const fd = new FormData();
                            fd.append('action','tv_flutterwave_init_checkout');
                            fd.append('pay_id','<?php echo $pay_id; ?>');
                            fd.append('_wpnonce','<?php echo $fw_nonce; ?>');
                            const res = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd });
                            const json = await res.json();
                            
                            window.hideGlobalLoader(); // Hide to allow tab switch or other actions
                            
                            if(json.success && json.data.link) { 
                                window.open(json.data.link, '_blank'); 
                            } else { 
                                alert('Error initializing payment.'); 
                            }
                        } catch(e) { 
                            window.hideGlobalLoader();
                            alert('Connection error.'); 
                        }
                    });
                }
    
                // --- 5. CANCEL TRANSACTION ---
                const cancelBtn = document.getElementById('tv-cancel-btn');
                if(cancelBtn) {
                    cancelBtn.addEventListener('click', async () => {
                        if(!confirm('Are you sure you want to cancel?')) return;
                        
                        window.showGlobalLoader('Cancelling Transaction...');
                        
                        try {
                            const fd = new FormData();
                            fd.append('action','tv_cancel_payment');
                            fd.append('pay_id','<?php echo $pay_id; ?>');
                            await fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd });
                            window.location.href = '<?php echo home_url('/dashboard'); ?>';
                        } catch(e){
                            window.location.reload();
                        }
                    });
                }
    
                // --- 6. SUBMIT PROOF FORM ---
                const proofForm = document.getElementById('proof-form');
                if(proofForm) {
                    proofForm.addEventListener('submit', function() {
                        window.showGlobalLoader('Uploading Proof...');
                    });
                }
            })();
            </script>
            <?php
            return ob_get_clean();
        }

}
