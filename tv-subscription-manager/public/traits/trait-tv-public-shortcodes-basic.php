<?php
if (!defined('ABSPATH')) { exit; }

trait TV_Manager_Public_Trait_Shortcodes_Basic {

    /**
     * Shortcode: [tv_plans]
     * Displays the standard pricing table.
     */
    public function shortcode_plans() {
        ob_start();
        $plans = [];
        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_plans}'") == $this->table_plans) {
            // UPDATED: Sort by display_order ASC, then price ASC
            $plans = $this->wpdb->get_results("SELECT * FROM {$this->table_plans} ORDER BY display_order ASC, price ASC");
        }
        include TV_MANAGER_PATH . 'public/views/view-plans.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: [tv_dashboard]
     * Displays the user dashboard (Active Sub + Recent Payments).
     */
    public function shortcode_dashboard() {
        if(!is_user_logged_in()) return 'Please login.';
        ob_start();
        $user_id = get_current_user_id();

        $active_sub = null;
        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_subs}'") == $this->table_subs) {
            $active_sub = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT s.*, p.name as plan_name FROM {$this->table_subs} s
                 LEFT JOIN {$this->table_plans} p ON s.plan_id = p.id
                 WHERE s.user_id = %d AND s.status = 'active' ORDER BY s.end_date DESC LIMIT 1",
                $user_id
            ));
        }

        $payments = [];
        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_payments}'") == $this->table_payments) {
            $payments = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT p.*, s.plan_id, pl.name as plan_name
                 FROM {$this->table_payments} p
                 LEFT JOIN {$this->table_subs} s ON p.subscription_id = s.id
                 LEFT JOIN {$this->table_plans} pl ON s.plan_id = pl.id
                 WHERE p.user_id = %d ORDER BY p.date DESC LIMIT 10",
                $user_id
            ));
        }

        include TV_MANAGER_PATH . 'public/views/view-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: [tv_subscription_details]
     * Displays detailed view of subscriptions with pagination.
     */
    public function shortcode_subscription_details($atts = array()) {
        if (!is_user_logged_in()) return 'Please login.';
        ob_start();

        $user_id = get_current_user_id();
        $page = isset($_GET['sub_page']) ? max(1, (int) $_GET['sub_page']) : 1;
        $per_page = 1;
        $offset = ($page - 1) * $per_page;

        $total_subs = 0;
        $sub = null;

        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_subs}'") == $this->table_subs) {
            $total_subs = (int) $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_subs} WHERE user_id = %d",
                $user_id
            ));

            if ($total_subs > 0) {
                $sub = $this->wpdb->get_row($this->wpdb->prepare(
                    "SELECT s.*, p.name as plan_name
                     FROM {$this->table_subs} s
                     LEFT JOIN {$this->table_plans} p ON s.plan_id = p.id
                     WHERE s.user_id = %d
                     ORDER BY s.id DESC
                     LIMIT %d OFFSET %d",
                    $user_id,
                    $per_page,
                    $offset
                ));
            }
        }

        $total_pages = ($total_subs > 0) ? (int) ceil($total_subs / $per_page) : 1;
        if ($page > $total_pages) {
            $page = $total_pages;
        }

        include TV_MANAGER_PATH . 'public/views/view-subscription-details.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: [tv_select_payment_method]
     * Displays the payment method selection grid.
     */
    public function shortcode_select_payment_method($atts) {
        if(!is_user_logged_in()) return 'Please login.';

        $user_id = get_current_user_id();
        $pending = get_user_meta($user_id, self::USER_META_PENDING_CHECKOUT, true);
        if (empty($pending) || !is_array($pending)) {
            return '<div style="padding:20px;">No pending checkout found. Please start again.</div>';
        }

        // [LOGIC] Detect User Locations (Dual Source: Profile + GeoIP)
        $user_locations = $this->get_all_user_locations($user_id);
        
        // [MODIFIED] Strict Currency-based Location Override
        $user_currency = get_user_meta($user_id, 'tv_user_currency', true);
        if (!empty($user_currency)) {
            $cur_map = [
                'NGN' => 'NG', 'GHS' => 'GH', 'KES' => 'KE', 'ZAR' => 'ZA',
                'GBP' => 'GB', 'USD' => 'US', 'CAD' => 'CA', 'AUD' => 'AU',
                'INR' => 'IN', 'BRL' => 'BR', 'AED' => 'AE', 'EUR' => 'EU'
            ];
            if (isset($cur_map[$user_currency])) {
                $user_locations = [$cur_map[$user_currency]];
            }
        }
        
        $user_display_region = !empty($user_locations) ? implode(' / ', $user_locations) : 'Global';

        $methods = [];
        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_methods}'") == $this->table_methods) {
            $all_methods = $this->wpdb->get_results("SELECT * FROM {$this->table_methods} WHERE status = 'active' ORDER BY display_order ASC, id DESC");
            
            foreach ($all_methods as $m) {
                if (empty($m->countries)) {
                    $methods[] = $m;
                    continue;
                }

                $allowed = array_map('trim', explode(',', strtoupper($m->countries)));
                $matches = array_intersect($user_locations, $allowed);
                
                if (!empty($matches)) {
                    $methods[] = $m;
                }
            }
        }

        $payment_page_url = add_query_arg('tv_flow', 'payment', home_url('/'));

        ob_start();
        ?>
        <style>
            .tv-methods-wrap { max-width: 800px; margin: 40px auto; padding: 20px; font-family: -apple-system, sans-serif; }
            .tv-method-card { 
                background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; 
                margin-bottom: 16px; cursor: pointer; transition: all 0.2s; 
                display: flex; align-items: center; gap: 20px; position: relative;
            }
            .tv-method-card:hover { border-color: #3b82f6; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transform: translateY(-2px); }
            .tv-method-card.selected { border-color: #3b82f6; background: #eff6ff; ring: 2px solid #3b82f6; }
            
            .tv-method-icon { width: 48px; height: 48px; background: #f8fafc; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #64748b; }
            .tv-method-icon img { max-width: 100%; max-height: 100%; object-fit: contain; }
            
            .tv-method-info { flex: 1; }
            .tv-method-name { font-weight: 700; color: #0f172a; font-size: 16px; margin-bottom: 4px; }
            .tv-method-desc { font-size: 13px; color: #64748b; }
            
            .tv-radio { width: 20px; height: 20px; border: 2px solid #cbd5e1; border-radius: 50%; position: relative; }
            .tv-method-card.selected .tv-radio { border-color: #3b82f6; background: #3b82f6; box-shadow: inset 0 0 0 4px #fff; }

            .tv-next-btn { 
                width: 100%; background: #0f172a; color: white; padding: 16px; 
                border-radius: 12px; font-weight: 700; font-size: 16px; border: none; 
                cursor: pointer; margin-top: 20px; transition: 0.2s; display: block; text-align: center; text-decoration: none;
            }
            .tv-next-btn:hover { background: #1e293b; transform: translateY(-1px); }
            .tv-next-btn.disabled { background: #cbd5e1; cursor: not-allowed; pointer-events: none; }

            /* METHOD LOADING OVERLAY (PREMIUM) */
            #tv-method-overlay {
                display: none; position: fixed; inset: 0; z-index: 99999;
                background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px);
                align-items: center; justify-content: center; flex-direction: column; color: white;
            }
            .tv-spinner { width: 40px; height: 40px; border: 4px solid rgba(255,255,255,0.1); border-left-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 16px; }
            @keyframes spin { 100% { transform: rotate(360deg); } }
        </style>

        <div id="tv-method-overlay">
            <div class="tv-spinner"></div>
            <div style="font-weight:700; font-size:18px;">Securing Config...</div>
            <div style="color:#94a3b8; font-size:14px; margin-top:4px;">Setting up your secure payment session</div>
        </div>

        <div class="tv-methods-wrap">
            <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 8px;">Select Payment Method</h2>
            <p style="color: #64748b; margin-bottom: 24px;">Secure payment options available in <strong><?php echo esc_html($user_display_region); ?></strong>.</p>

            <form id="tv-method-form" method="post" action="<?php echo esc_url($payment_page_url); ?>">
                <?php if($methods): foreach($methods as $m): ?>
                <div class="tv-method-card" onclick="selectMethod(<?php echo $m->id; ?>, this)">
                    <div class="tv-method-icon">
                        <?php if($m->logo_url): ?>
                            <img src="<?php echo esc_url($m->logo_url); ?>" alt="Icon">
                        <?php else: ?>
                            <span>??</span>
                        <?php endif; ?>
                    </div>
                    <div class="tv-method-info">
                        <div class="tv-method-name"><?php echo esc_html($m->name); ?></div>
                        <div class="tv-method-desc"><?php echo esc_html(wp_strip_all_tags($m->instructions)); ?></div>
                    </div>
                    <div class="tv-radio"></div>
                </div>
                <?php endforeach; else: ?>
                    <div style="padding: 40px; text-align: center; color: #64748b; background: #f8fafc; border-radius: 16px;">
                        No payment methods found for your region (<?php echo esc_html($user_display_region); ?>).
                    </div>
                <?php endif; ?>

                <input type="hidden" name="payment_method_id" id="selected_method_id" value="" required>
                <input type="hidden" name="plan_id" value="<?php echo esc_attr($pending['plan_id']); ?>">
                <input type="hidden" name="connections" value="<?php echo esc_attr($pending['connections']); ?>">
                
                <button type="submit" id="tv-next-btn" class="tv-next-btn disabled">Continue to Payment</button>
            </form>
        </div>

        <script>
        function selectMethod(id, el) {
            document.querySelectorAll('.tv-method-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            document.getElementById('selected_method_id').value = id;
            document.getElementById('tv-next-btn').classList.remove('disabled');
        }

        document.getElementById('tv-method-form').addEventListener('submit', function(e) {
            if(!document.getElementById('selected_method_id').value) {
                e.preventDefault();
                alert('Please select a method.');
                return;
            }
            // Show Overlay instantly
            const overlay = document.getElementById('tv-method-overlay');
            overlay.style.display = 'flex';
            // Disable button to prevent double-submit
            document.getElementById('tv-next-btn').classList.add('disabled');
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: [tv_upload_payment_proof]
     * Displays the file upload form for manual payments.
     */
    public function shortcode_upload_payment_proof($atts) {
        if(!is_user_logged_in()) return '<div class="tv-error">Please log in.</div>';
        
        $pay_id = isset($_GET['pay_id']) ? intval($_GET['pay_id']) : 0;
        if(!$pay_id) {
            // Attempt to recover from session if not in URL
            $pay_id = (int)get_user_meta(get_current_user_id(), '_tv_active_pay_id', true);
        }

        if(!$pay_id) return '<div class="tv-error">No active payment found.</div>';

        ob_start();
        ?>
        <style>
            .tv-proof-wrap { max-width: 500px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 20px; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.1); font-family: -apple-system, sans-serif; text-align: center; }
            .tv-proof-icon { width: 64px; height: 64px; background: #eff6ff; color: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
            .tv-proof-title { font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 10px; }
            .tv-proof-desc { color: #64748b; font-size: 14px; margin-bottom: 30px; }
            
            .tv-file-area { border: 2px dashed #cbd5e1; border-radius: 16px; padding: 40px 20px; cursor: pointer; transition: 0.2s; position: relative; background: #f8fafc; }
            .tv-file-area:hover { border-color: #3b82f6; background: #eff6ff; }
            .tv-file-input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
            
            .tv-btn-upload { width: 100%; padding: 16px; background: #0f172a; color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 16px; margin-top: 20px; cursor: pointer; transition: 0.2s; }
            .tv-btn-upload:hover { background: #1e293b; transform: translateY(-2px); }
        </style>

        <div class="tv-proof-wrap">
            <div class="tv-proof-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
            <div class="tv-proof-title">Upload Payment Proof</div>
            <div class="tv-proof-desc">Please upload a screenshot of your transfer for Invoice #<?php echo $pay_id; ?>.</div>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('tv_upload_proof_' . $pay_id, 'tv_proof_nonce'); ?>
                <input type="hidden" name="payment_proof_submit" value="1">
                <input type="hidden" name="payment_id" value="<?php echo esc_attr($pay_id); ?>">

                <div class="tv-file-area">
                    <input type="file" name="payment_proof[]" class="tv-file-input" required accept="image/*,.pdf">
                    <div style="font-weight:600; color:#334155;">Click to Select File</div>
                    <div style="font-size:12px; color:#94a3b8; margin-top:4px;">JPG, PNG or PDF</div>
                </div>

                <button type="submit" class="tv-btn-upload">Submit Proof</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

}