<?php
/**
 * File: tv-subscription-manager/admin/views/view-bulk-messages.php
 * Path: tv-subscription-manager/admin/views/view-bulk-messages.php
 * Version: 3.9.17 (Unabridged & Uncondensed)
 * * CORE PHILOSOPHY: "Stability over hype. Zero code reduction."
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Direct access prevention
}

// Determine if we are in Edit Mode for the Hero Slider
$is_edit = ! empty( $edit_slide );
$form_title = $is_edit ? 'Edit Hero Slide' : 'Add Hero Slide';
$btn_text   = $is_edit ? 'Update Slide' : 'Add to Carousel';

// Pre-fill Slide Data for the Editor
$val_title  = $is_edit ? $edit_slide->title : '';
$val_msg    = $is_edit ? $edit_slide->message : '';
$val_btn    = $is_edit ? $edit_slide->button_text : '';
$val_action = $is_edit ? $edit_slide->button_action : '';
$val_color  = $is_edit ? $edit_slide->color_scheme : '';

// Resolve visual selector state
$is_custom_hex = ( strpos( $val_color, '#' ) === 0 || strpos( $val_color, 'rgb' ) === 0 );
$dropdown_val  = $is_custom_hex ? 'custom' : $val_color;
?>

<div class="tv-page-header">
    <div>
        <h1>Hero Banner & Communications</h1>
        <p>Manage the sliding banner on the user dashboard and handle high-volume email broadcasts.</p>
    </div>
    <div style="display:flex; gap:12px; align-items:center;">
        <!-- API Integrator: Critical for high-volume bypass -->
        <button type="button" class="tv-btn tv-btn-primary" onclick="openApiModal()">
            <span class="dashicons dashicons-rest-api" style="margin-right:6px;"></span> 
            API Integrator
        </button>
        
        <?php if ( $is_edit ) : ?>
            <a href="?page=tv-subs-manager&tab=messages" class="tv-btn tv-btn-secondary">
                <span class="dashicons dashicons-no-alt" style="margin-right:6px;"></span> 
                Cancel Edit
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="tv-grid-2">
    
    <!-- =========================================================================
         SECTION 1: HERO SLIDE EDITOR (Dashboard Carousel Management)
         ========================================================================= -->
    <div class="tv-card">
        <div class="tv-card-header">
            <h3><span class="dashicons dashicons-slides" style="margin-right:8px;"></span> <?php echo esc_html( $form_title ); ?></h3>
        </div>
        <div class="tv-card-body">
            <p style="color:var(--tv-text-muted); font-size:13px; margin-bottom:20px; line-height:1.5;">
                Create dynamic announcement cards. Use internal tab names (<code>shop</code>, <code>billing</code>, <code>support</code>) or full <code>https://</code> URLs.
            </p>

            <form method="post" action="?page=tv-subs-manager&tab=messages">
                <?php wp_nonce_field( 'post_announcement_verify' ); ?>
                
                <?php if ( $is_edit ) : ?>
                    <input type="hidden" name="slide_id" value="<?php echo esc_attr( $edit_slide->id ); ?>">
                <?php endif; ?>
                
                <div class="tv-form-group">
                    <label class="tv-label">Main Headline</label>
                    <input type="text" name="news_title" required class="tv-input" value="<?php echo esc_attr( $val_title ); ?>" placeholder="e.g. Weekend Flash Sale!">
                </div>
                
                <div class="tv-form-group">
                    <label class="tv-label">Supporting Description</label>
                    <textarea name="news_message" class="tv-textarea" rows="3" placeholder="Explain the update to your users..."><?php echo esc_textarea( $val_msg ); ?></textarea>
                </div>

                <div class="tv-row">
                    <div class="tv-col">
                        <label class="tv-label">Button Text</label>
                        <input type="text" name="news_btn_text" class="tv-input" value="<?php echo esc_attr( $val_btn ); ?>" placeholder="e.g. Upgrade Now">
                    </div>
                    <div class="tv-col">
                        <label class="tv-label">Link Action</label>
                        <input type="text" name="news_btn_action" class="tv-input" value="<?php echo esc_attr( $val_action ); ?>" placeholder="shop">
                    </div>
                </div>
                
                <div class="tv-form-group">
                    <label class="tv-label">Visual Theme</label>
                    <div style="margin-bottom:8px;">
                        <select name="news_color" class="tv-input" id="color_selector" onchange="toggleCustomColor(this)">
                            <option value="from-indigo-600 to-violet-600" <?php selected( $dropdown_val, 'from-indigo-600 to-violet-600' ); ?>>Royal Indigo (Gradient)</option>
                            <option value="from-blue-600 to-cyan-500" <?php selected( $dropdown_val, 'from-blue-600 to-cyan-500' ); ?>>Oceanic (Gradient)</option>
                            <option value="from-emerald-500 to-teal-500" <?php selected( $dropdown_val, 'from-emerald-500 to-teal-500' ); ?>>Forest (Gradient)</option>
                            <option value="from-rose-500 to-orange-500" <?php selected( $dropdown_val, 'from-rose-500 to-orange-500' ); ?>>Solar (Gradient)</option>
                            <option value="custom" <?php selected( $dropdown_val, 'custom' ); ?>>Custom HEX/Color...</option>
                        </select>
                    </div>
                    <!-- Custom Color Toggle Field -->
                    <input type="text" name="news_color_custom" id="custom_color_input" class="tv-input" 
                           value="<?php echo $is_custom_hex ? esc_attr( $val_color) : ''; ?>" 
                           placeholder="#1e293b" 
                           style="display: <?php echo $is_custom_hex ? 'block' : 'none'; ?>; margin-top:10px;">
                </div>

                <button type="submit" name="post_announcement" class="tv-btn tv-btn-secondary w-full" style="height:44px; font-weight:700;">
                    <span class="dashicons dashicons-yes" style="margin-right:6px;"></span> 
                    <?php echo esc_html( $btn_text ); ?>
                </button>
            </form>

            <!-- Active Slide Registry -->
            <div style="margin-top:35px; border-top:1px solid var(--tv-border); padding-top:25px;">
                <label class="tv-label" style="margin-bottom:15px; display:block;">Active Carousel Items</label>
                <?php if ( ! empty( $news_items ) ) : ?>
                    <div style="max-height:350px; overflow-y:auto; padding-right:8px;" class="custom-scrollbar">
                        <?php foreach ( $news_items as $news ) : 
                            $is_current = ( $is_edit && $edit_slide->id == $news->id );
                        ?>
                        <div style="background:<?php echo $is_current ? 'var(--tv-success-bg)' : '#fff'; ?>; border:1px solid <?php echo $is_current ? 'var(--tv-success)' : 'var(--tv-border)'; ?>; padding:15px; border-radius:14px; margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; box-shadow:var(--tv-shadow);">
                            <div style="display:flex; align-items:center; gap:14px;">
                                <div style="width:40px; height:40px; border-radius:10px; background:var(--tv-surface-active); display:flex; align-items:center; justify-content:center; font-weight:900; color:var(--tv-primary); font-size:14px;">
                                    <?php echo strtoupper( substr( $news->title, 0, 1 ) ); ?>
                                </div>
                                <div>
                                    <div style="font-weight:800; font-size:14px; color:var(--tv-text);"><?php echo esc_html( $news->title ); ?></div>
                                    <div style="font-size:11px; color:var(--tv-text-muted); font-family:monospace;">Action: <?php echo esc_html( $news->button_action ); ?></div>
                                </div>
                            </div>
                            <div style="display:flex; gap:8px;">
                                <a href="?page=tv-subs-manager&tab=messages&action=edit_news&id=<?php echo $news->id; ?>" class="tv-btn tv-btn-sm tv-btn-secondary" title="Edit Slide"><span class="dashicons dashicons-edit"></span></a>
                                <a href="<?php echo wp_nonce_url( '?page=tv-subs-manager&tab=messages&action=delete_news&id=' . $news->id, 'delete_news_' . $news->id ); ?>" 
                                   class="tv-btn tv-btn-sm tv-btn-danger" data-tv-delete="1" title="Remove Slide">
                                    <span class="dashicons dashicons-trash"></span>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div style="text-align:center; padding:30px; background:var(--tv-surface-active); border-radius:16px; border:2px dashed var(--tv-border); color:var(--tv-text-muted);">
                        <span class="dashicons dashicons-info" style="font-size:32px; width:32px; height:32px; margin-bottom:10px;"></span>
                        <p style="margin:0; font-weight:600;">No active slides. Standard welcome will show.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         SECTION 2: BROADCAST ENGINE (The Professional Multi-Channel Blaster)
         ========================================================================= -->
    <div class="tv-card">
        <div class="tv-card-header">
            <h3><span class="dashicons dashicons-email-alt" style="margin-right:8px;"></span> Broadcast Engine</h3>
        </div>
        <div class="tv-card-body">
            
            <!-- REPUTATION GUARD NOTICE -->
            <div style="background:#fffbeb; border:1px solid #fde68a; padding:18px; border-radius:16px; margin-bottom:24px; display:flex; gap:15px; align-items:start;">
                <div style="width:36px; height:36px; background:#fef3c7; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <span class="dashicons dashicons-warning" style="color:#d97706; font-size:22px; width:22px; height:22px;"></span>
                </div>
                <div>
                    <strong style="color:#92400e; display:block; margin-bottom:4px; font-size:14px;">Reputation Guard Enabled</strong>
                    <p style="margin:0; font-size:12px; color:#92400e; line-height:1.6;">
                        SMTP-AUTH (info@xoflix.tv) is strictly limited to transactional mail. Mass broadcasts initiated here will bypass SMTP to prevent IP blacklisting. 
                        <strong>High-volume accounts should select an API Protocol.</strong>
                    </p>
                </div>
            </div>

            <form method="post" action="?page=tv-subs-manager&tab=messages" id="broadcast-form">
                <?php wp_nonce_field( 'bulk_email_verify' ); ?>
                
                <div class="tv-row">
                    <div class="tv-col">
                        <label class="tv-label">Target Subscribers</label>
                        <select name="filter_plan" class="tv-input">
                            <option value="0">Global (All Users)</option>
                            <?php foreach ( $plans as $p ) : ?>
                                <option value="<?php echo (int) $p->id; ?>"><?php echo esc_html( $p->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="tv-col">
                        <label class="tv-label">Account Status</label>
                        <select name="filter_status" class="tv-input">
                            <option value="all">Any Status</option>
                            <option value="active">Active Only</option>
                            <option value="pending">Pending Only</option>
                            <option value="expired">Expired Only</option>
                        </select>
                    </div>
                </div>
                
                <div class="tv-form-group">
                    <label class="tv-label">Sending Protocol</label>
                    <select name="broadcast_method" class="tv-input" style="font-weight:700; color:var(--tv-primary);">
                        <option value="wp_mail">Server Default (Unauthenticated PHPMail)</option>
                        <?php if ( ! empty( $api_integrations ) ) : foreach ( $api_integrations as $api ) : ?>
                            <option value="<?php echo esc_attr( $api['id'] ); ?>">REST API: <?php echo esc_html( $api['name'] ); ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>

                <div class="tv-form-group">
                    <label class="tv-label">Broadcast Subject</label>
                    <input type="text" name="email_subject" class="tv-input" placeholder="Urgent: System Optimization..." required>
                </div>

                <div class="tv-form-group">
                    <label class="tv-label">Message Body (HTML Supported)</label>
                    <textarea name="email_body" class="tv-textarea" rows="10" required placeholder="Hello {{name}}, we have an exciting update..."></textarea>
                    <div style="margin-top:8px; display:flex; gap:12px; flex-wrap:wrap;">
                        <code style="font-size:10px; padding:2px 6px; background:var(--tv-surface-active); border-radius:4px;">{{name}}</code>
                        <code style="font-size:10px; padding:2px 6px; background:var(--tv-surface-active); border-radius:4px;">{{email}}</code>
                        <code style="font-size:10px; padding:2px 6px; background:var(--tv-surface-active); border-radius:4px;">{{username}}</code>
                    </div>
                </div>

                <!-- SMTP & API FAST-TEST -->
                <div style="background:#f0f9ff; border:1px solid #bae6fd; padding:18px; border-radius:14px; margin-bottom:25px;">
                    <label class="tv-label" style="color:#0369a1; font-weight:800; margin-bottom:10px; display:flex; align-items:center; gap:8px;">
                        <span class="dashicons dashicons-admin-links" style="font-size:16px;"></span> 
                        Connectivity Diagnostic
                    </label>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input type="email" id="broadcast_test_to" class="tv-input" style="flex:1;" placeholder="Send test to..." value="<?php echo esc_attr( get_option('admin_email') ); ?>">
                        <button type="button" class="tv-btn tv-btn-secondary" onclick="runQuickSystemMailTest()">Verify Mail</button>
                    </div>
                    <div id="quick_test_status" style="margin-top:10px; font-size:12px; font-weight:700; display:none; padding:8px 12px; border-radius:8px; font-family:monospace;"></div>
                </div>

                <button type="submit" name="send_bulk_email" class="tv-btn tv-btn-primary w-full" style="height:52px; font-size:15px; font-weight:800;" onclick="return confirm('Launch high-volume broadcast? This action cannot be undone.')">
                    <span class="dashicons dashicons-paper-plane" style="margin-right:8px;"></span> 
                    Launch Global Broadcast
                </button>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================================
     SECTION 3: NOTIFICATION ENGINE AUDIT LOGS
     ========================================================================= -->
<div class="tv-card" style="margin-top:30px;">
    <div class="tv-card-header">
        <h3><span class="dashicons dashicons-list-view" style="margin-right:8px;"></span> Transmission Logs</h3>
        <span class="tv-badge active">Auto-Syncing</span>
    </div>
    <div class="tv-table-container">
        <table class="tv-table">
            <thead>
                <tr>
                    <th width="15%">Dispatch Time</th>
                    <th width="20%">Subscriber</th>
                    <th width="10%">Method</th>
                    <th width="15%">Classification</th>
                    <th width="10%">Status</th>
                    <th width="20%">Gateway Feedback</th>
                    <th width="10%" align="right">Retry</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $logs ) ) : foreach ( $logs as $log ) : ?>
                <tr>
                    <td class="tv-mono" style="font-size:12px;"><?php echo date( 'M d, H:i:s', strtotime( $log->sent_at ) ); ?></td>
                    <td>
                        <div style="font-weight:800; color:var(--tv-text);"><?php echo esc_html( $log->user_email ); ?></div>
                        <div style="font-size:10px; opacity:0.6;">UID: <?php echo (int) $log->user_id; ?></div>
                    </td>
                    <td>
                        <span class="tv-badge neutral"><?php echo esc_html( $log->channel ); ?></span>
                    </td>
                    <td>
                        <span style="font-size:11px; font-weight:900; text-transform:uppercase; letter-spacing:0.5px;"><?php echo esc_html( str_replace('_', ' ', $log->type) ); ?></span>
                    </td>
                    <td>
                        <?php if ( $log->status === 'sent' ) : ?>
                            <span class="tv-badge success">Accepted</span>
                        <?php else : ?>
                            <span class="tv-badge error">Rejected</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ( ! empty( $log->error_msg ) ) : ?>
                            <div style="color:var(--tv-danger); font-size:11px; font-family:monospace; max-width:280px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo esc_attr( $log->error_msg ); ?>">
                                <?php echo esc_html( $log->error_msg ); ?>
                            </div>
                        <?php else : ?>
                            <span class="dashicons dashicons-yes-alt" style="color:var(--tv-success); font-size:18px;"></span>
                        <?php endif; ?>
                    </td>
                    <td align="right">
                        <a href="<?php echo wp_nonce_url( '?page=tv-subs-manager&tab=messages&action=resend_notify&log_id=' . $log->id, 'resend_notify_' . $log->id ); ?>" 
                           class="tv-btn tv-btn-sm tv-btn-secondary" onclick="return confirm('Force individual re-transmission?')">
                            <span class="dashicons dashicons-update"></span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; else : ?>
                <tr><td colspan="7" style="text-align:center; padding:50px; color:var(--tv-text-muted);">No outgoing transmissions found in history.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- =========================================================================
     SECTION 4: UNIVERSAL API INTEGRATOR MODAL (REST ENGINE)
     ========================================================================= -->
<div id="tv-api-modal" class="tv-modal-overlay" style="display:none;" aria-hidden="true">
    <div class="tv-modal" style="max-width:950px; height:85vh; display:flex; flex-direction:column; border-radius:32px; overflow:hidden; border:none; box-shadow:0 50px 100px -20px rgba(15,23,42,0.5);">
        
        <div class="tv-modal-header" style="background:#fff; border-bottom:1px solid var(--tv-border); padding:28px; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2 style="margin:0; font-size:22px; font-weight:900; letter-spacing:-0.5px; color:var(--tv-text);">Advanced REST API Integrator</h2>
                <p style="margin:6px 0 0; font-size:13px; color:var(--tv-text-muted);">Bridge high-volume transactional services (Mailjet, SendGrid) to the XOFLIX engine.</p>
            </div>
            <button type="button" class="tv-btn tv-btn-ghost" onclick="closeApiModal()" style="width:44px; height:44px; border-radius:50%;"><span class="dashicons dashicons-no-alt" style="font-size:26px; width:26px; height:26px;"></span></button>
        </div>
        
        <div class="tv-modal-body" style="flex:1; display:flex; padding:0; overflow:hidden; background:#f8fafc;">
            
            <!-- Modal Sidebar: Service Inventory -->
            <div style="width:260px; background:#fff; border-right:1px solid var(--tv-border); overflow-y:auto; padding:24px;">
                <button type="button" class="tv-btn tv-btn-primary w-full" style="margin-bottom:24px; height:46px; font-weight:800;" onclick="resetApiForm()">
                    <span class="dashicons dashicons-plus" style="margin-right:6px;"></span> New Integration
                </button>
                <div id="api-list-sidebar">
                    <?php if ( ! empty( $api_integrations ) ) : foreach ( $api_integrations as $api ) : ?>
                        <div class="api-item" onclick='loadApiConfig(<?php echo json_encode( $api ); ?>)'>
                            <div style="font-weight:900; font-size:13px; color:var(--tv-text);"><?php echo esc_html( $api['name'] ); ?></div>
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:6px;">
                                <span style="font-size:10px; font-weight:900; color:var(--tv-primary); text-transform:uppercase; background:var(--tv-surface-active); padding:2px 6px; border-radius:4px;"><?php echo esc_html( $api['method'] ); ?></span>
                                <a href="<?php echo wp_nonce_url( '?page=tv-subs-manager&tab=messages&action=delete_api&api_id=' . $api['id'], 'delete_api_' . $api['id'] ); ?>" 
                                   style="color:var(--tv-danger); font-size:11px; font-weight:700; text-decoration:none;" data-tv-delete="1">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; else : ?>
                        <div style="text-align:center; padding:30px; color:var(--tv-text-muted); font-size:12px; border:2px dashed #e2e8f0; border-radius:16px;">No external APIs defined.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Modal Content: Endpoint & Payload Editor -->
            <div style="flex:1; padding:40px; overflow-y:auto;" class="custom-scrollbar">
                <form method="post" id="api-integration-form" action="?page=tv-subs-manager&tab=messages">
                    <?php wp_nonce_field( 'tv_save_api_config', '_tv_api_nonce' ); ?>
                    <input type="hidden" name="save_api_integration" value="1">
                    <input type="hidden" name="api_id" id="api_id_field">

                    <div class="tv-row">
                        <div class="tv-col">
                            <label class="tv-label">Friendly Service Name</label>
                            <input type="text" name="api_name" id="api_name_field" class="tv-input" placeholder="e.g. Mailjet Production v3" required>
                        </div>
                        <div class="tv-col" style="flex:0 0 140px;">
                            <label class="tv-label">HTTP Method</label>
                            <select name="api_method" id="api_method_field" class="tv-input">
                                <option value="POST">POST</option>
                                <option value="GET">GET</option>
                                <option value="PUT">PUT</option>
                            </select>
                        </div>
                    </div>

                    <div class="tv-form-group">
                        <label class="tv-label">Full Endpoint URL</label>
                        <input type="url" name="api_url" id="api_url_field" class="tv-input" placeholder="https://api.mailjet.com/v3.1/send" required>
                    </div>

                    <!-- Dynamic Header Management -->
                    <div class="tv-form-group" style="margin-top:30px;">
                        <label class="tv-label" style="display:flex; justify-content:space-between; align-items:center;">
                            Authentication & Headers
                            <button type="button" class="tv-btn tv-btn-sm tv-btn-secondary" onclick="addApiHeaderRow()">+ Add Header</button>
                        </label>
                        <div id="api-headers-container" style="background:#fff; border:1px solid var(--tv-border); border-radius:16px; padding:16px; margin-top:12px; box-shadow:inset 0 1px 2px rgba(0,0,0,0.02);">
                            <!-- Injected rows go here -->
                        </div>
                    </div>

                    <div class="tv-form-group" style="margin-top:30px;">
                        <label class="tv-label">
                            JSON Payload Body
                            <span style="font-weight:400; color:var(--tv-text-muted); float:right; font-size:11px;">Supports {{email}}, {{name}}, {{subject}}, {{message}}</span>
                        </label>
                        <textarea name="api_body" id="api_body_field" class="tv-textarea" rows="10" style="font-family:'SFMono-Regular', Consolas, monospace; font-size:12px; color:#2563eb; background:#f0f7ff; line-height:1.6; border-color:#dbeafe;"></textarea>
                    </div>

                    <!-- Connection Log Output -->
                    <div id="api-integration-test-log" style="display:none; background:#1e293b; color:#10b981; padding:20px; border-radius:16px; font-family:monospace; font-size:12px; margin-bottom:30px; line-height:1.6; border:1px solid #334155; white-space:pre-wrap; max-height:200px; overflow-y:auto;"></div>

                    <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--tv-border); padding-top:30px; margin-top:20px;">
                         <button type="button" class="tv-btn tv-btn-secondary" onclick="runApiIntegrationTest()" style="padding:0 25px;">
                            <span class="dashicons dashicons-performance" style="margin-right:6px;"></span> 
                            Run Connection Test
                         </button>
                         <button type="submit" class="tv-btn tv-btn-primary" style="padding:0 50px; font-size:14px; font-weight:900;">Save Global Configuration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     JAVASCRIPT SUITE (UNABRIDGED & DETAILED)
     ========================================================================= -->
<script>
    /**
     * 1. SLIDE EDITOR LOGIC
     */
    function toggleCustomColor(select) {
        const input = document.getElementById('custom_color_input');
        if (select.value === 'custom') {
            input.style.display = 'block';
            input.focus();
        } else {
            input.style.display = 'none';
        }
    }

    /**
     * 2. MODAL & API INTEGRATOR LOGIC
     */
    function openApiModal() {
        const modal = document.getElementById('tv-api-modal');
        modal.style.display = 'flex';
        // Auto-init if empty
        if(!document.getElementById('api_id_field').value) {
            resetApiForm();
        }
    }

    function closeApiModal() {
        document.getElementById('tv-api-modal').style.display = 'none';
    }

    function resetApiForm() {
        document.getElementById('api_id_field').value = 'api_' + Date.now();
        document.getElementById('api_name_field').value = '';
        document.getElementById('api_method_field').value = 'POST';
        document.getElementById('api_url_field').value = '';
        document.getElementById('api_body_field').value = '';
        document.getElementById('api-headers-container').innerHTML = '';
        document.getElementById('api-integration-test-log').style.display = 'none';
        
        // Add Default Header (JSON Standard)
        addApiHeaderRow('Content-Type', 'application/json');
    }

    function addApiHeaderRow(key = '', val = '') {
        const container = document.getElementById('api-headers-container');
        const row = document.createElement('div');
        row.className = 'tv-header-repeater-row';
        row.style.display = 'flex';
        row.style.gap = '10px';
        row.style.marginBottom = '10px';
        
        row.innerHTML = `
            <input type="text" name="header_keys[]" value="${key}" class="tv-input" style="flex:1;" placeholder="Header Key (e.g. Authorization)">
            <input type="text" name="header_values[]" value="${val}" class="tv-input" style="flex:1;" placeholder="Value">
            <button type="button" class="tv-btn-ghost" onclick="this.parentElement.remove()" style="color:var(--tv-danger); padding:4px;">
                <span class="dashicons dashicons-trash"></span>
            </button>
        `;
        container.appendChild(row);
    }

    function loadApiConfig(data) {
        document.getElementById('api_id_field').value = data.id;
        document.getElementById('api_name_field').value = data.name;
        document.getElementById('api_method_field').value = data.method;
        document.getElementById('api_url_field').value = data.url;
        document.getElementById('api_body_field').value = data.body;
        
        const container = document.getElementById('api-headers-container');
        container.innerHTML = '';
        
        if (data.headers && Array.isArray(data.headers)) {
            data.headers.forEach(function(h) {
                addApiHeaderRow(h.key, h.value);
            });
        } else {
            addApiHeaderRow();
        }
        
        document.getElementById('api-integration-test-log').style.display = 'none';
    }

    /**
     * 3. AJAX TESTING SUITE (STRICT ERROR HANDLING)
     */
    function runApiIntegrationTest() {
        const btn = event.target;
        const originalText = btn.innerHTML;
        const logArea = document.getElementById('api-integration-test-log');

        btn.disabled = true;
        btn.innerHTML = '<span class="dashicons dashicons-update" style="animation:spin 2s linear infinite;"></span> Contacting Endpoint...';
        
        logArea.style.display = 'block';
        logArea.style.color = '#94a3b8';
        logArea.innerText = 'Initializing handshake with external API...';

        const activeHeaders = [];
        document.querySelectorAll('#api-headers-container .tv-header-repeater-row').forEach(function(row) {
            const inputs = row.querySelectorAll('input');
            if(inputs[0].value) {
                activeHeaders.push({ key: inputs[0].value, value: inputs[1].value });
            }
        });

        const testPayload = {
            action: 'tv_test_api_integration',
            nonce: '<?php echo wp_create_nonce("tv_test_api_nonce"); ?>',
            method: document.getElementById('api_method_field').value,
            url: document.getElementById('api_url_field').value,
            body: document.getElementById('api_body_field').value,
            headers: activeHeaders
        };

        jQuery.post(ajaxurl, testPayload, function(response) {
            if (response.success) {
                logArea.style.color = '#10b981';
                logArea.innerText = "STATUS: SUCCESS (HTTP " + response.data.code + ")\n\nSERVER RESPONSE:\n" + response.data.body;
            } else {
                logArea.style.color = '#f43f5e';
                const errCode = response.data ? response.data.code : 'UNKNOWN';
                const errBody = response.data ? response.data.body : 'No response from server.';
                logArea.innerText = "STATUS: FAILED (HTTP " + errCode + ")\n\nERROR LOG:\n" + errBody;
            }
        }).fail(function() {
            logArea.style.color = '#f43f5e';
            logArea.innerText = 'CRITICAL ERROR: The local server was unable to initiate the request. Check your SSL certificates or Firewall rules.';
        }).always(function() {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }

    /**
     * 4. SMTP DIAGNOSTIC (WEBMAIL VERIFIER)
     */
    function runQuickSystemMailTest() {
        const toEmail = document.getElementById('broadcast_test_to').value;
        const statusBox = document.getElementById('quick_test_status');
        
        if (!toEmail) { 
            alert('Please provide a recipient email for the test.'); 
            return; 
        }

        statusBox.style.display = 'block';
        statusBox.style.background = '#f1f5f9';
        statusBox.style.color = '#64748b';
        statusBox.innerText = 'Attempting system dispatch...';

        const testData = {
            action: 'tv_test_smtp',
            _nonce: '<?php echo wp_create_nonce("tv_test_smtp"); ?>',
            test_email: toEmail,
            // These values are handled on the server side via existing options
            host: '<?php echo esc_js( get_option( "tv_smtp_host" ) ); ?>',
            user: '<?php echo esc_js( get_option( "tv_smtp_user" ) ); ?>',
            pass: 'AUTHENTICATED_SESSION', 
            port: '<?php echo esc_js( get_option( "tv_smtp_port", 465 ) ); ?>',
            enc: '<?php echo esc_js( get_option( "tv_smtp_enc", "ssl" ) ); ?>'
        };

        jQuery.post(ajaxurl, testData, function(response) {
            if (response.success) {
                statusBox.style.background = '#dcfce7';
                statusBox.style.color = '#15803d';
                statusBox.innerText = 'â   ' + response.data.message;
            } else {
                statusBox.style.background = '#fee2e2';
                statusBox.style.color = '#b91c1c';
                statusBox.innerText = 'â   ' + (response.data.message || 'SMTP Error.');
            }
        });
    }
</script>

<!-- STYLES (UNABRIDGED) -->
<style>
    .tv-mono { font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; }
    
    .api-item { 
        padding: 16px; 
        background: #fff; 
        border: 1px solid var(--tv-border); 
        border-radius: 14px; 
        margin-bottom: 12px; 
        cursor: pointer; 
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    
    .api-item:hover { 
        border-color: var(--tv-primary); 
        background: var(--tv-surface-active);
        transform: translateX(6px); 
        box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    }

    @keyframes spin { 
        100% { transform: rotate(360deg); } 
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
</style>