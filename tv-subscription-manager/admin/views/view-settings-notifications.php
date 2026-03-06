<div class="tv-page-header">
    <div>
        <h1>Notification Settings</h1>
        <p>Manage automated emails and WhatsApp alerts.</p>
    </div>
</div>

<form method="post" action="?page=tv-settings-general&tab=notifications">
<?php wp_nonce_field('tv_settings_verify'); ?>

<?php 
    // Fetch Saved Templates or use Defaults
    $templates = get_option('tv_notification_templates', []); 
    $tmpl_expiry_sub = isset($templates['expiry']['subject']) ? $templates['expiry']['subject'] : 'Urgent: Your plan expires in {{days_left}} days';
    $tmpl_expiry_body = isset($templates['expiry']['body']) ? $templates['expiry']['body'] : "Hello {{user_name}},\n\nYour subscription for {{plan_name}} is expiring in {{days_left}} days.\n\nRenew now: {{login_url}}";
    
    $tmpl_re_sub = isset($templates['reengage']['subject']) ? $templates['reengage']['subject'] : 'We miss you, {{user_name}}!';
    $tmpl_re_body = isset($templates['reengage']['body']) ? $templates['reengage']['body'] : "It's been {{days_passed}} days since your subscription ended.\n\nRenew here: {{login_url}}";
?>

<div class="tv-card">
    <div class="tv-card-header">
        <h3><span class="dashicons dashicons-bell" style="margin-right:8px;"></span> Notification Engine</h3>
    </div>
    <div class="tv-card-body">
        
        <!-- Config Row -->
        <div class="tv-row" style="margin-bottom:24px;">
            <div class="tv-col">
                <div class="tv-field" style="margin-bottom:0;">
                    <input type="text" name="tv_notify_expiry_days" value="<?php echo esc_attr(get_option('tv_notify_expiry_days', '7,3,1')); ?>" class="tv-control tv-input" placeholder=" ">
                    <label class="tv-label">Expiry Reminders (Days)</label>
                    <div class="tv-help">Comma separated days before expiry (e.g. 7,3,1).</div>
                </div>
            </div>
            <div class="tv-col">
                <label class="tv-label">Re-engagement</label>
                <div style="margin-top:10px;">
                    <label style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="tv_notify_reengage_enabled" value="1" <?php checked(get_option('tv_notify_reengage_enabled', '0'), '1'); ?>>
                        Enable 14-Day Cycle (After Expiry)
                    </label>
                </div>
            </div>
        </div>

        <!-- Template Editor -->
        <div style="background:var(--tv-surface-active); border:1px solid var(--tv-border); border-radius:8px; overflow:hidden;">
            <div style="background:#e2e8f0; padding:10px 20px; font-weight:600; font-size:12px; text-transform:uppercase; color:#475569; display:flex; justify-content:space-between;">
                <span>Message Templates</span>
                <span style="font-weight:400; opacity:0.8;">Variables: {{user_name}}, {{plan_name}}, {{login_url}}, {{days_left}}</span>
            </div>
            
            <div style="padding:20px;">
                <div class="tv-row">
                    <div class="tv-col">
                        <label class="tv-label" style="color:var(--tv-primary);">Expiry Reminder Template</label>
                        <div class="tv-field" style="margin-bottom:12px;">
                            <input type="text" name="tmpl_expiry_subject" value="<?php echo esc_attr($tmpl_expiry_sub); ?>" class="tv-control tv-input" placeholder=" ">
                            <label class="tv-label">Subject</label>
                        </div>
                        <div class="tv-field" style="margin-bottom:0;">
                            <textarea name="tmpl_expiry_body" class="tv-control tv-textarea tv-input" rows="4" placeholder=" "><?php echo esc_textarea($tmpl_expiry_body); ?></textarea>
                            <label class="tv-label">Body</label>
                        </div>
                    </div>
                    <div class="tv-col">
                        <label class="tv-label" style="color:var(--tv-primary);">Re-engagement Template</label>
                        <div class="tv-field" style="margin-bottom:12px;">
                            <input type="text" name="tmpl_reengage_subject" value="<?php echo esc_attr($tmpl_re_sub); ?>" class="tv-control tv-input" placeholder=" ">
                            <label class="tv-label">Subject</label>
                        </div>
                        <div class="tv-field" style="margin-bottom:0;">
                            <textarea name="tmpl_reengage_body" class="tv-control tv-textarea tv-input" rows="4" placeholder=" "><?php echo esc_textarea($tmpl_re_body); ?></textarea>
                            <label class="tv-label">Body</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top:20px; padding-top:20px; border-top:1px dashed var(--tv-border);">
            <label class="tv-label" style="margin-bottom:10px;">WhatsApp Gateway (Optional)</label>
            <div class="tv-row">
                <div class="tv-col">
                    <div class="tv-field" style="margin-bottom:0;">
                        <input type="url" name="tv_notify_whatsapp_gateway" value="<?php echo esc_attr(get_option('tv_notify_whatsapp_gateway')); ?>" class="tv-control tv-input" placeholder=" ">
                        <label class="tv-label">Webhook URL</label>
                        <div class="tv-help">e.g. https://api.gateway.com/send</div>
                    </div>
                </div>
                <div class="tv-col">
                    <div class="tv-field" style="margin-bottom:0;">
                        <input type="password" name="tv_notify_whatsapp_key" value="<?php echo esc_attr(get_option('tv_notify_whatsapp_key')); ?>" class="tv-control tv-input" placeholder=" ">
                        <label class="tv-label">API Key / Token</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="tv-card" style="position:sticky; bottom:20px; z-index:99; border-top: 4px solid var(--tv-primary);">
    <div class="tv-card-body" style="display:flex; justify-content:flex-end; padding:15px 24px;">
        <button type="submit" name="save_settings" class="tv-btn tv-btn-primary" style="height:40px; padding:0 30px; font-weight:600;">
            Save Notification Settings
        </button>
    </div>
</div>

</form>