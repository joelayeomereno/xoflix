<?php
if (!defined('ABSPATH')) { exit; }

trait TV_Manager_Public_Trait_Shortcode_Payment_Control {

    public function shortcode_payment_control($atts = []) {
        if (!is_user_logged_in()) return 'Please login.';
        $user_id = get_current_user_id();
        $pay_id = isset($_GET['pay_id']) ? intval($_GET['pay_id']) : 0;

        if (!$pay_id) {
            $pay_id = intval(get_user_meta($user_id, self::USER_META_ACTIVE_PAY_ID, true));
        }

        if (!$pay_id) {
            return '<div style="padding:40px; text-align:center; color:#64748b;">No active payment session found.</div>';
        }

        global $wpdb;
        $table_payments = $wpdb->prefix . 'tv_payments';
        $table_methods  = $wpdb->prefix . 'tv_payment_methods';

        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_payments} WHERE id = %d AND user_id = %d",
            $pay_id,
            $user_id
        ));
        
        if (!$payment) {
            delete_user_meta($user_id, self::USER_META_ACTIVE_PAY_ID);
            return '<div style="padding:40px; text-align:center; color:#64748b;">Payment not found.</div>';
        }

        $method = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_methods} WHERE name = %s LIMIT 1", $payment->method));

        $is_flutterwave = ($method && !empty($method->flutterwave_enabled));
        $has_link = ($method && (!empty($method->link) || $is_flutterwave));
        
        $fw_init_nonce = wp_create_nonce('tv_flutterwave_init_' . $pay_id);
        $upload_url = add_query_arg(['pay_id' => (int)$pay_id], add_query_arg('tv_flow', 'upload_proof', home_url('/')));
        $cancel_nonce = wp_create_nonce('tv_cancel_payment_' . $pay_id);

        ob_start();
        ?>
        <style>
            .tv-ctrl-wrap { max-width:600px; margin:40px auto; padding:0 20px; font-family:-apple-system,sans-serif; }
            .tv-status-card { background:#fff; border:1px solid #e2e8f0; border-radius:24px; padding:32px; text-align:center; box-shadow:0 20px 40px -10px rgba(0,0,0,0.08); }
            .tv-status-badge { display:inline-block; padding:6px 14px; background:#f1f5f9; color:#475569; border-radius:99px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-bottom:20px; }
            .tv-status-badge.pending { background:#fff7ed; color:#ea580c; }
            .tv-amount { font-size:48px; font-weight:900; color:#0f172a; margin-bottom:8px; letter-spacing:-1px; }
            .tv-ref { color:#64748b; font-size:14px; font-family:monospace; margin-bottom:32px; }
            
            .tv-actions { display:flex; flex-direction:column; gap:12px; }
            .tv-btn-action { width:100%; padding:16px; border-radius:14px; font-weight:700; cursor:pointer; transition:all 0.2s; font-size:15px; border:none; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px; }
            .tv-btn-pri { background:#0f172a; color:#fff; box-shadow:0 10px 20px -5px rgba(15,23,42,0.3); }
            .tv-btn-pri:hover { transform:translateY(-2px); box-shadow:0 15px 25px -5px rgba(15,23,42,0.4); }
            .tv-btn-sec { background:#fff; border:2px solid #e2e8f0; color:#0f172a; }
            .tv-btn-sec:hover { background:#f8fafc; border-color:#cbd5e1; }
            .tv-btn-dang { background:#fff; color:#ef4444; margin-top:12px; font-size:13px; }
        </style>

        <div class="tv-ctrl-wrap">
            <div class="tv-status-card">
                <span class="tv-status-badge pending">Payment In Progress</span>
                <div class="tv-amount">$<?php echo number_format($payment->amount, 0); ?></div>
                <div class="tv-ref">#INV-<?php echo str_pad($pay_id, 6, '0', STR_PAD_LEFT); ?></div>

                <div class="tv-actions">
                    <?php if ($has_link): ?>
                        <?php if ($is_flutterwave): ?>
                            <button type="button" id="tv-fw-reopen" class="tv-btn-action tv-btn-pri">
                                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                Continue to Gateway
                            </button>
                        <?php else: ?>
                            <!-- Generic Link Reopen logic would go here if needed, but usually we just re-direct -->
                            <a href="<?php echo esc_url(add_query_arg(['tv_flow'=>'payment', 'pay_id'=>$pay_id], home_url('/'))); ?>" class="tv-btn-action tv-btn-pri">
                                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                Continue to Gateway
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <a href="<?php echo esc_url($upload_url); ?>" class="tv-btn-action tv-btn-sec">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        I Have Paid - Upload Proof
                    </a>

                    <button type="button" id="tv-cancel-pay" class="tv-btn-action tv-btn-dang">Cancel Transaction</button>
                </div>
            </div>
        </div>

        <script>
        (function(){
            const fwBtn = document.getElementById('tv-fw-reopen');
            if (fwBtn) {
                fwBtn.addEventListener('click', async function(){
                    let link = '';
                    try {
                        const body = new URLSearchParams();
                        body.append('action','tv_flutterwave_init_checkout');
                        body.append('pay_id','<?php echo esc_js($pay_id); ?>');
                        body.append('_wpnonce','<?php echo esc_js($fw_init_nonce); ?>');
                        const res = await fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', { method: 'POST', credentials: 'same-origin', body: body });
                        const json = await res.json();
                        if (json.success) link = json.data.link;
                        else alert(json.data.message || 'Error');
                    } catch(e) { alert('Error'); }
                    if(link) window.open(link, 'tv_flutterwave_checkout', 'width=1100,height=800');
                });
            }

            document.getElementById('tv-cancel-pay').addEventListener('click', async function(){
                if(!confirm('Cancel this payment?')) return;
                try {
                    const body = new URLSearchParams();
                    body.append('action','tv_cancel_payment');
                    body.append('pay_id','<?php echo esc_js($pay_id); ?>');
                    body.append('_wpnonce','<?php echo esc_js($cancel_nonce); ?>');
                    await fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', { method: 'POST', credentials: 'same-origin', body: body });
                    alert('Cancelled.');
                    window.location.href = '<?php echo esc_url(home_url('/dashboard')); ?>';
                } catch(e) {}
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}
