<?php if (!defined('ABSPATH')) exit; ?>
<?php
/**
 * File: tv-subscription-manager/admin/views/view-user-manage.php
 * Path: /tv-subscription-manager/admin/views/view-user-manage.php
 * Description: Comprehensive User Management Interface
 * Hardened for XOFLIX TV v3: Fixed persistence and added Password Reset Section
 */

$user_id = (int)$user->ID;
$registered = date('F j, Y H:i', strtotime($user->user_registered));
$admin_url = admin_url('admin.php?page=tv-subs-manager&tab=users&action=manage&user_id=' . $user_id);
$impersonate_url = wp_nonce_url(admin_url('admin.php?page=tv-subs-manager&tab=users&action=start_impersonation&user_id=' . $user_id), 'start_impersonation_' . $user_id);

// Check for active section
$active_tab = isset($_GET['manage_section']) ? sanitize_key($_GET['manage_section']) : 'profile';
$tabs = [
    'profile' => 'Profile & Settings',
    'subscription' => 'Subscription Management',
    'transactions' => 'Payment History',
    'logs' => 'Activity Logs'
];

// Helper for tabs
function tv_manage_tab_url($tab, $base) {
    return add_query_arg('manage_section', $tab, $base);
}
?>

<div class="tv-content-area">

    <!-- HEADER SECTION -->
    <div class="tv-page-header" style="border-bottom: 1px solid var(--tv-border); padding-bottom: 20px; margin-bottom: 30px;">
        <div style="display:flex; align-items:center; gap:16px;">
            <div style="width:64px; height:64px; background:linear-gradient(135deg, var(--tv-primary), #818cf8); color:white; border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:700; box-shadow:0 10px 20px -5px rgba(79, 70, 229, 0.4);">
                <?php echo strtoupper(substr($user->user_login, 0, 1)); ?>
            </div>
            <div>
                <h1 style="margin:0; font-size:24px; font-weight:800; color:var(--tv-text); line-height:1.2;">
                    <?php echo esc_html($user->display_name ?: $user->user_login); ?>
                </h1>
                <div style="display:flex; gap:12px; font-size:13px; color:var(--tv-text-muted); margin-top:6px;">
                    <span><span class="dashicons dashicons-email-alt" style="font-size:14px; line-height:1.4; margin-right:4px;"></span> <?php echo esc_html($user->user_email); ?></span>
                    <span><span class="dashicons dashicons-calendar-alt" style="font-size:14px; line-height:1.4; margin-right:4px;"></span> Joined <?php echo esc_html($registered); ?></span>
                </div>
            </div>
        </div>

        <div style="display:flex; gap:10px;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=tv-subs-manager&tab=users')); ?>" class="tv-btn tv-btn-secondary">
                <span class="dashicons dashicons-arrow-left-alt" style="margin-right:6px;"></span> Back
            </a>
            <a href="<?php echo esc_url($impersonate_url); ?>" target="_blank" class="tv-btn tv-btn-secondary" title="Login as this user in a new tab">
                <span class="dashicons dashicons-external" style="margin-right:6px;"></span> Impersonate
            </a>
        </div>
    </div>

    <!-- TABS NAVIGATION -->
    <div class="tv-toolbar" style="margin-bottom:30px; padding:0; border:none; background:transparent; display:flex; gap:20px; border-bottom:1px solid var(--tv-border);">
        <?php foreach ($tabs as $key => $label): 
            $is_active = ($active_tab === $key);
            $style = $is_active ? 'border-bottom: 3px solid var(--tv-primary); color:var(--tv-primary); font-weight:700;' : 'color:var(--tv-text-muted); font-weight:500;';
        ?>
            <a href="<?php echo esc_url(tv_manage_tab_url($key, $admin_url)); ?>" 
               style="text-decoration:none; padding:15px 5px; font-size:14px; transition:all 0.2s; <?php echo $style; ?>">
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- ========================================== -->
    <!-- TAB: PROFILE -->
    <!-- ========================================== -->
    <?php if ($active_tab === 'profile'): ?>
        
        <form method="post">
            <?php wp_nonce_field('tv_update_user_profile_verify'); ?>
            <input type="hidden" name="tv_update_user_profile" value="1">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:30px;">
                
                <!-- Main Settings -->
                <div class="tv-card">
                    <div class="tv-card-header">
                        <h3>Account Information</h3>
                    </div>
                    <div class="tv-card-body">
                        <div class="tv-row">
                            <div class="tv-col">
                                <label class="tv-label">Username (Read-Only)</label>
                                <input type="text" class="tv-input" value="<?php echo esc_attr($user->user_login); ?>" disabled style="background:var(--tv-surface-active); opacity:0.8;">
                            </div>
                            <div class="tv-col">
                                <label class="tv-label">Email Address</label>
                                <input type="email" name="user_email" class="tv-input" value="<?php echo esc_attr($user->user_email); ?>" required>
                            </div>
                        </div>

                        <div class="tv-row">
                            <div class="tv-col">
                                <label class="tv-label">First Name</label>
                                <input type="text" name="first_name" class="tv-input" value="<?php echo esc_attr(get_user_meta($user_id, 'first_name', true)); ?>">
                            </div>
                            <div class="tv-col">
                                <label class="tv-label">Last Name</label>
                                <input type="text" name="last_name" class="tv-input" value="<?php echo esc_attr(get_user_meta($user_id, 'last_name', true)); ?>">
                            </div>
                        </div>
                        
                        <div class="tv-form-group">
                            <label class="tv-label">Display Name</label>
                            <input type="text" name="display_name" class="tv-input" value="<?php echo esc_attr($user->display_name); ?>">
                            <p style="font-size:12px; color:var(--tv-text-muted); margin-top:4px;">How the name appears in the dashboard header.</p>
                        </div>

                        <!-- SECURITY SECTION -->
                        <div style="margin-top:30px; padding-top:20px; border-top:1px dashed var(--tv-border);">
                            <h4 style="margin:0 0 15px 0; font-size:13px; font-weight:800; color:var(--tv-danger); text-transform:uppercase;">Security & Password</h4>
                            <div class="tv-form-group">
                                <label class="tv-label">Reset Password</label>
                                <div style="display:flex; gap:10px;">
                                    <input type="text" name="user_pass" id="tv_user_pass" class="tv-input" placeholder="Enter new password to override..." autocomplete="new-password">
                                    <button type="button" class="tv-btn tv-btn-secondary" onclick="document.getElementById('tv_user_pass').value = Math.random().toString(36).slice(-10)" style="height:44px; white-space:nowrap;">Generate</button>
                                </div>
                                <p style="font-size:12px; color:var(--tv-text-muted); margin-top:6px;">Leave blank if you do not wish to change the user's password.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact & Notes -->
                <div style="display:flex; flex-direction:column; gap:20px;">
                    <div class="tv-card">
                        <div class="tv-card-header">
                            <h3>Contact Details</h3>
                        </div>
                        <div class="tv-card-body">
                            <div class="tv-form-group">
                                <label class="tv-label">Phone Number</label>
                                <input type="text" name="phone" class="tv-input" value="<?php echo esc_attr($phone); ?>" placeholder="+1...">
                            </div>
                            <div class="tv-form-group">
                                <label class="tv-label">Country</label>
                                <input type="text" name="billing_country" class="tv-input" value="<?php echo esc_attr(get_user_meta($user_id, 'billing_country', true)); ?>" placeholder="US">
                            </div>
                        </div>
                    </div>

                    <div class="tv-card">
                        <div class="tv-card-header">
                            <h3>Admin Notes</h3>
                        </div>
                        <div class="tv-card-body">
                            <textarea name="admin_notes" class="tv-textarea" rows="4" placeholder="Internal notes about this user..."><?php echo esc_textarea(get_user_meta($user_id, 'tv_admin_notes', true)); ?></textarea>
                        </div>
                    </div>

                    <!-- [NEW] DANGER ZONE -->
                    <div class="tv-card" style="border:1px solid #fee2e2; background:#fef2f2;">
                        <div class="tv-card-header" style="background:#fecaca; border-bottom:1px solid #fee2e2;">
                            <h3 style="color:#b91c1c;"><span class="dashicons dashicons-warning" style="margin-right:8px;"></span> Danger Zone</h3>
                        </div>
                        <div class="tv-card-body" style="padding:20px;">
                            <p style="font-size:12px; color:#b91c1c; margin:0 0 15px 0; line-height:1.5;">This will permanently remove the WordPress user and all associated meta. This cannot be undone.</p>
                            <?php 
                                $del_user_url = wp_nonce_url(admin_url('admin.php?page=tv-subs-manager&tab=users&action=delete_user&user_id=' . $user_id), 'delete_user_' . $user_id);
                            ?>
                            <a href="<?php echo esc_url($del_user_url); ?>" class="tv-btn tv-btn-danger w-full" data-tv-delete="1" style="justify-content:center;">
                                <span class="dashicons dashicons-trash" style="margin-right:8px;"></span> Delete User Account
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer Actions -->
            <div class="tv-card" style="margin-top:20px; border-left:4px solid var(--tv-primary);">
                <div class="tv-card-body" style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <label class="tv-switch">
                            <input type="checkbox" name="notify_user" value="1" checked class="tv-toggle-input">
                            <span class="tv-toggle-ui" aria-hidden="true"></span>
                            <span style="font-weight:600;">Notify user of profile changes</span>
                        </label>
                    </div>
                    <button type="submit" class="tv-btn tv-btn-primary" style="padding:12px 32px; font-weight:700;">Save All Changes</button>
                </div>
            </div>

        </form>

    <!-- ========================================== -->
    <!-- TAB: SUBSCRIPTION -->
    <!-- ========================================== -->
    <?php elseif ($active_tab === 'subscription'): ?>

        <!-- Active Subscription Editor -->
        <div class="tv-card">
            <div class="tv-card-header" style="justify-content: space-between;">
                <h3>Active Subscription</h3>
                <button type="button" onclick="generateCreds()" class="tv-btn tv-btn-sm tv-btn-secondary">
                    <span class="dashicons dashicons-randomize" style="margin-right:4px;"></span> Magic Generate Creds
                </button>
            </div>
            <div class="tv-card-body">
                <?php if ($sub): 
                    // Parse Panel URLs for display
                    $current_m3u = (string)($sub->credential_m3u ?? '');
                    $panel_urls = array();
                    if ($current_m3u !== '') {
                        if (preg_match_all('/\[Panel:\s*[^\]]+\]/i', $current_m3u, $m)) { $panel_urls = array_map('trim', (array)$m[0]); }
                        $current_m3u = trim((string)preg_replace('/\n*\[Panel:\s*[^\]]+\]\s*/i', "\n", $current_m3u));
                    }
                    $panel_urls_text = trim(str_replace(['[Panel: ', ']'], '', implode("\n", $panel_urls)));
                ?>
                
                <form method="post">
                    <?php wp_nonce_field('tv_update_subscription_verify'); ?>
                    <input type="hidden" name="tv_update_subscription" value="1">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    <input type="hidden" name="sub_id" value="<?php echo (int)$sub->id; ?>">

                    <div style="background:var(--tv-surface-active); border:1px solid var(--tv-border); padding:16px; border-radius:8px; margin-bottom:24px;">
                        <div class="tv-row">
                            <div class="tv-col">
                                <label class="tv-label">Plan Configuration</label>
                                <select class="tv-control tv-input" name="plan_id">
                                    <?php foreach ($plans as $pl): ?>
                                        <option value="<?php echo (int)$pl->id; ?>" <?php selected((int)$sub->plan_id, (int)$pl->id); ?>>
                                            <?php echo esc_html($pl->name); ?> (<?php echo $pl->duration_days; ?> days)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="tv-col">
                                <label class="tv-label">Current Status</label>
                                <select class="tv-control tv-input" name="status">
                                    <option value="active" <?php selected($sub->status, 'active'); ?>>Active</option>
                                    <option value="pending" <?php selected($sub->status, 'pending'); ?>>Pending</option>
                                    <option value="expired" <?php selected($sub->status, 'expired'); ?>>Expired</option>
                                    <option value="inactive" <?php selected($sub->status, 'inactive'); ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="tv-col">
                                <label class="tv-label">Max Connections</label>
                                <input type="number" name="connections" class="tv-input" value="<?php echo (int)($sub->connections ?? 1); ?>" min="1">
                            </div>
                        </div>

                        <!-- Smart Date Controls -->
                        <div class="tv-row">
                            <div class="tv-col">
                                <label class="tv-label">Start Date</label>
                                <input type="text" name="start_date" class="tv-input" value="<?php echo esc_attr($sub->start_date); ?>" placeholder="YYYY-MM-DD HH:MM:SS">
                            </div>
                            <div class="tv-col" style="position:relative;">
                                <label class="tv-label">End Date (Expiry)</label>
                                <div style="display:flex; gap:8px;">
                                    <input type="text" id="edit_end_date" name="end_date" class="tv-input" value="<?php echo esc_attr($sub->end_date); ?>" placeholder="YYYY-MM-DD HH:MM:SS">
                                    <div style="display:flex; gap:4px;">
                                        <button type="button" onclick="addTime(30)" class="tv-btn tv-btn-secondary" title="Add 1 Month">+1M</button>
                                        <button type="button" onclick="addTime(90)" class="tv-btn tv-btn-secondary" title="Add 3 Months">+3M</button>
                                        <button type="button" onclick="addTime(365)" class="tv-btn tv-btn-secondary" title="Add 1 Year">+1Y</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h4 style="margin:0 0 12px 0; font-size:14px; text-transform:uppercase; color:var(--tv-text-muted); border-bottom:1px solid var(--tv-border); padding-bottom:8px;">Line Credentials</h4>
                    
                    <div class="tv-row">
                        <div class="tv-col">
                            <label class="tv-label">Username</label>
                            <input type="text" id="gen_user" name="credential_user" class="tv-input" value="<?php echo esc_attr($sub->credential_user ?? ''); ?>" autocomplete="off">
                        </div>
                        <div class="tv-col">
                            <label class="tv-label">Password</label>
                            <input type="text" id="gen_pass" name="credential_pass" class="tv-input" value="<?php echo esc_attr($sub->credential_pass ?? ''); ?>" autocomplete="off">
                        </div>
                    </div>

                    <div class="tv-form-group">
                        <label class="tv-label">Host / DNS URL</label>
                        <input type="url" name="credential_url" class="tv-input" value="<?php echo esc_attr($sub->credential_url ?? ''); ?>" placeholder="http://domain.com:8080">
                    </div>

                    <div class="tv-form-group">
                        <label class="tv-label">M3U Playlist / Smart TV URL</label>
                        <textarea name="credential_m3u" class="tv-textarea" rows="3" style="font-family:monospace; font-size:12px;"><?php echo esc_textarea($current_m3u); ?></textarea>
                    </div>

                    <div class="tv-form-group">
                        <label class="tv-label">Attached Panel URLs (One per line)</label>
                        <textarea name="panel_attachment_urls" class="tv-textarea" rows="2" style="font-family:monospace; font-size:12px; background:#f8fafc;" placeholder="Additional M3U links..."><?php echo esc_textarea($panel_urls_text); ?></textarea>
                        <p style="font-size:11px; color:var(--tv-text-muted); margin-top:4px;">These are appended to the user's M3U field internally for panel tracking.</p>
                    </div>

                    <div style="display:flex; justify-content:flex-end; margin-top:20px;">
                        <button type="submit" class="tv-btn tv-btn-primary" style="padding:10px 24px;">Update Subscription</button>
                    </div>
                </form>

                <?php else: ?>
                    <div style="text-align:center; padding:40px; color:var(--tv-text-muted);">
                        <span class="dashicons dashicons-warning" style="font-size:32px; height:32px; width:32px; margin-bottom:10px;"></span>
                        <p>No active subscription found for this user.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Subscription History Table -->
        <div class="tv-card">
            <div class="tv-card-header">
                <h3>Subscription History</h3>
            </div>
            <div class="tv-table-container">
                <table class="tv-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($all_subs)): foreach ($all_subs as $s): ?>
                        <tr>
                            <td>#<?php echo (int)$s->id; ?></td>
                            <td><?php echo esc_html($s->plan_name ?? 'Unknown'); ?></td>
                            <td><span class="tv-badge <?php echo $s->status; ?>"><?php echo ucfirst($s->status); ?></span></td>
                            <td><?php echo date('Y-m-d', strtotime($s->start_date)); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($s->end_date)); ?></td>
                            <td style="text-align:right;">
                                <?php 
                                    $edit_link = add_query_arg(['manage_section'=>'subscription', 'sub_id'=>$s->id], $admin_url);
                                    $del_link = wp_nonce_url(admin_url('admin.php?page=tv-subs-manager&tab=users&action=soft_delete_sub&sub_id='.$s->id), 'soft_delete_sub_'.$s->id);
                                ?>
                                <a href="<?php echo esc_url($edit_link); ?>" class="tv-btn tv-btn-sm tv-btn-secondary">Edit</a>
                                <a href="<?php echo esc_url($del_link); ?>" class="tv-btn tv-btn-sm tv-btn-danger" data-tv-delete="1">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px;">No history available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Manual Creation Form -->
        <div class="tv-card">
            <div class="tv-card-header">
                <h3>Manually Create Subscription</h3>
            </div>
            <div class="tv-card-body">
                <form method="post">
                    <?php wp_nonce_field('tv_create_subscription_verify'); ?>
                    <input type="hidden" name="tv_create_subscription" value="1">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                    <div class="tv-row">
                        <div class="tv-col">
                            <label class="tv-label">Plan</label>
                            <select class="tv-control tv-input" name="plan_id">
                                <?php foreach ($plans as $pl): ?>
                                    <option value="<?php echo (int)$pl->id; ?>"><?php echo esc_html($pl->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="tv-col">
                            <label class="tv-label">Connections</label>
                            <input type="number" name="connections" class="tv-input" value="1" min="1">
                        </div>
                    </div>

                    <div class="tv-row">
                        <div class="tv-col">
                            <label class="tv-label">Start Date</label>
                            <input type="text" name="start_date" class="tv-input" value="<?php echo current_time('mysql'); ?>">
                        </div>
                        <div class="tv-col">
                            <label class="tv-label">End Date</label>
                            <input type="text" name="end_date" class="tv-input" value="<?php echo date('Y-m-d H:i:s', strtotime('+30 days')); ?>">
                        </div>
                    </div>

                    <button type="submit" class="tv-btn tv-btn-primary">Create Subscription</button>
                </form>
            </div>
        </div>

        <script>
        function addTime(days) {
            const el = document.getElementById('edit_end_date');
            if(!el) return;
            let base = new Date();
            if(el.value) {
                const parts = el.value.split(/[- :]/);
                if(parts.length >= 3) base = new Date(parts[0], parts[1]-1, parts[2], parts[3]||0, parts[4]||0, parts[5]||0);
            }
            base.setDate(base.getDate() + days);
            const pad = (n) => n.toString().padStart(2, '0');
            el.value = `${base.getFullYear()}-${pad(base.getMonth()+1)}-${pad(base.getDate())} ${pad(base.getHours())}:${pad(base.getMinutes())}:${pad(base.getSeconds())}`;
        }
        function generateCreds() {
            const chars = "abcdefghjkmnpqrstuvwxyz23456789";
            let u = "user", p = "";
            for(let i=0; i<8; i++) u += chars.charAt(Math.floor(Math.random()*chars.length));
            for(let i=0; i<10; i++) p += chars.charAt(Math.floor(Math.random()*chars.length));
            if(document.getElementById('gen_user')) document.getElementById('gen_user').value = u;
            if(document.getElementById('gen_pass')) document.getElementById('gen_pass').value = p;
        }
        </script>

    <!-- ========================================== -->
    <!-- TAB: TRANSACTIONS -->
    <!-- ========================================== -->
    <?php elseif ($active_tab === 'transactions'): ?>
        
        <div class="tv-card">
            <div class="tv-card-header">
                <h3>Transaction History</h3>
            </div>
            <div class="tv-table-container">
                <table class="tv-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($payments)): foreach ($payments as $p): ?>
                        <tr>
                            <td>#<?php echo (int)$p->id; ?></td>
                            <td><?php echo esc_html($p->date); ?></td>
                            <td><strong><?php echo esc_html($p->currency . ' ' . number_format((float)$p->amount, 2)); ?></strong></td>
                            <td><?php echo esc_html($p->method); ?></td>
                            <td><span class="tv-badge <?php echo strtolower($p->status); ?>"><?php echo esc_html($p->status); ?></span></td>
                            <td><?php echo esc_html($p->transaction_id ?: '-'); ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:30px;">No transactions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- ========================================== -->
    <!-- TAB: ACTIVITY LOGS -->
    <!-- ========================================== -->
    <?php elseif ($active_tab === 'logs'): ?>
        
        <div class="tv-card">
            <div class="tv-card-header">
                <h3>User Activity Logs</h3>
            </div>
            <div class="tv-table-container">
                <table class="tv-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        global $wpdb;
                        $logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tv_activity_logs WHERE user_id = %d ORDER BY date DESC LIMIT 50", $user_id));
                        if (!empty($logs)): foreach ($logs as $l): ?>
                        <tr>
                            <td><?php echo esc_html($l->date); ?></td>
                            <td><span class="tv-badge free"><?php echo esc_html($l->action); ?></span></td>
                            <td><?php echo esc_html($l->details); ?></td>
                            <td style="font-family:monospace;"><?php echo esc_html($l->ip_address); ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="4" style="text-align:center; padding:30px;">No activity recorded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

</div>