<?php
/**
 * FILE PATH: tv-subscription-manager/admin/views/view-settings.php
 *
 * FIX: dropdown overlap in Checkout Flow section — added proper spacing,
 *      z-index stacking for wp_dropdown_pages, and separated the two-column
 *      layout to prevent select elements from being clipped.
 */
?>
<div class="tv-page-header">
    <div>
        <h1>System Settings</h1>
        <p>Configure global rules, panels, support channels, and notifications.</p>
    </div>
</div>

<form method="post" action="?page=tv-subs-manager&tab=settings">
<?php wp_nonce_field('tv_settings_verify'); ?>

<?php 
    // Fetch Saved Templates or use Defaults
    $templates = get_option('tv_notification_templates', []); 
    $tmpl_expiry_sub = isset($templates['expiry']['subject']) ? $templates['expiry']['subject'] : 'Urgent: Your plan expires in {{days_left}} days';
    $tmpl_expiry_body = isset($templates['expiry']['body']) ? $templates['expiry']['body'] : "Hello {{user_name}},\n\nYour subscription for {{plan_name}} is expiring in {{days_left}} days.\n\nRenew now: {{login_url}}";
    
    $tmpl_re_sub = isset($templates['reengage']['subject']) ? $templates['reengage']['subject'] : 'We miss you, {{user_name}}!';
    $tmpl_re_body = isset($templates['reengage']['body']) ? $templates['reengage']['body'] : "It's been {{days_passed}} days since your subscription ended.\n\nRenew here: {{login_url}}";

    // [FOX Integration] Currency Logic
    $woocs_available = false;
    $all_currencies = [];
    if (class_exists('WOOCS')) {
        global $WOOCS;
        $all_currencies = $WOOCS->get_currencies();
        $woocs_available = true;
    }
    $allowed_currencies = get_option('tv_allowed_currencies', []);
?>

<!-- [NEW] Currency & Plans Display -->
<div class="tv-card">
    <div class="tv-card-header">
        <h3><span class="dashicons dashicons-money-alt" style="margin-right:8px;"></span> Currency Control (FOX Integration)</h3>
    </div>
    <div class="tv-card-body">
        <?php if ($woocs_available && !empty($all_currencies)): ?>
            <p style="font-size:13px; color:var(--tv-text-muted); margin-bottom:15px;">
                Select which currencies are allowed on the <strong>Premium Plans Page</strong>. Visitors will be forced to select one of these.
            </p>
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap:10px;">
                <?php foreach ($all_currencies as $code => $data): ?>
                    <label style="display:flex; align-items:center; gap:6px; background:var(--tv-surface-active); padding:8px; border-radius:6px; border:1px solid var(--tv-border); cursor:pointer;">
                        <input type="checkbox" name="tv_allowed_currencies[]" value="<?php echo esc_attr($code); ?>" <?php checked(in_array($code, $allowed_currencies)); ?> style="accent-color:var(--tv-primary);">
                        <div style="line-height:1.2;">
                            <strong style="font-size:13px; display:block;"><?php echo esc_html($code); ?></strong>
                            <span style="font-size:11px; color:var(--tv-text-muted);"><?php echo esc_html($data['symbol']); ?></span>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="notice notice-warning inline" style="margin:0;">
                <p><strong>FOX Currency Converter (WOOCS) not detected.</strong> Please install/activate the plugin to enable multi-currency features.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- [NEW] WhatsApp Trial Request Config -->
<div class="tv-card">
    <div class="tv-card-header">
        <h3><span class="dashicons dashicons-whatsapp" style="margin-right:8px;"></span> Trial Request Settings (Visitor Flow)</h3>
    </div>
    <div class="tv-card-body">
        <div class="tv-form-group">
            <label class="tv-label">Custom WhatsApp Message</label>
            <textarea name="tv_whatsapp_custom_msg" class="tv-textarea" rows="2" placeholder="Leave empty for default: 'Hi, I want a trial for {plan_name}'"><?php echo esc_textarea(get_option('tv_whatsapp_custom_msg', '')); ?></textarea>
            <p style="font-size:11px; color:var(--tv-text-muted); margin-top:4px;">
                If set, this overrides the default message. Use <code>{plan_name}</code> to dynamically insert the plan.
            </p>
        </div>
        <div class="tv-form-group">
            <label class="tv-label">Price Overlay Duration (Seconds)</label>
            <input type="number" name="tv_trial_overlay_delay" value="<?php echo esc_attr(get_option('tv_trial_overlay_delay', 5)); ?>" class="tv-input" style="width:100px;">
            <p style="font-size:11px; color:var(--tv-text-muted); margin-top:4px;">How long the price popup shows before redirecting to WhatsApp.</p>
        </div>
    </div>
</div>

<!-- Notification Engine & Templates -->
<div class="tv-card">
    <div class="tv-card-header">
        <h3><span class="dashicons dashicons-bell" style="margin-right:8px;"></span> Notification Automation</h3>
    </div>
    <div class="tv-card-body">
        
        <!-- Config Row -->
        <div class="tv-row" style="margin-bottom:24px;">
            <div class="tv-col">
                <label class="tv-label">Expiry Reminders (Days)</label>
                <input type="text" name="tv_notify_expiry_days" value="<?php echo esc_attr(get_option('tv_notify_expiry_days', '7,3,1')); ?>" class="tv-input" placeholder="7,3,1">
                <p style="font-size:11px; color:var(--tv-text-muted); margin-top:4px;">Comma separated days before expiry.</p>
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
            <div style="background:var(--tv-surface-active); padding:10px 20px; font-weight:600; font-size:12px; text-transform:uppercase; color:var(--tv-text-muted); display:flex; justify-content:space-between; border-bottom:1px solid var(--tv-border);">
                <span>Message Templates</span>
                <span style="font-weight:400; opacity:0.8;">Variables: {{user_name}}, {{plan_name}}, {{login_url}}, {{days_left}}</span>
            </div>
            
            <div style="padding:20px;">
                <div class="tv-row">
                    <div class="tv-col">
                        <label class="tv-label" style="color:var(--tv-primary);">Expiry Reminder Template</label>
                        <input type="text" name="tmpl_expiry_subject" value="<?php echo esc_attr($tmpl_expiry_sub); ?>" class="tv-input" style="margin-bottom:10px;" placeholder="Subject Line">
                        <textarea name="tmpl_expiry_body" class="tv-textarea" rows="4"><?php echo esc_textarea($tmpl_expiry_body); ?></textarea>
                    </div>
                    <div class="tv-col">
                        <label class="tv-label" style="color:var(--tv-primary);">Re-engagement Template</label>
                        <input type="text" name="tmpl_reengage_subject" value="<?php echo esc_attr($tmpl_re_sub); ?>" class="tv-input" style="margin-bottom:10px;" placeholder="Subject Line">
                        <textarea name="tmpl_reengage_body" class="tv-textarea" rows="4"><?php echo esc_textarea($tmpl_re_body); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top:20px; padding-top:20px; border-top:1px dashed var(--tv-border);">
            <label class="tv-label" style="margin-bottom:10px;">WhatsApp Gateway (Optional)</label>
            <div class="tv-row">
                <div class="tv-col">
                    <label class="tv-label">Webhook URL</label>
                    <input type="url" name="tv_notify_whatsapp_gateway" value="<?php echo esc_attr(get_option('tv_notify_whatsapp_gateway')); ?>" class="tv-input" placeholder="https://api.gateway.com/send">
                </div>
                <div class="tv-col">
                    <label class="tv-label">API Key / Token</label>
                    <input type="password" name="tv_notify_whatsapp_key" value="<?php echo esc_attr(get_option('tv_notify_whatsapp_key')); ?>" class="tv-input">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Panel Configuration Section -->
<div class="tv-card">
    <div class="tv-card-header">
        <h3><span class="dashicons dashicons-desktop" style="margin-right:8px;"></span> Panel Configuration</h3>
    </div>
    <div class="tv-card-body">
        <div id="panel-rows">
            <?php 
            $saved_panels = get_option('tv_panel_configs', []);
            if (!empty($saved_panels) && is_array($saved_panels)): 
                foreach($saved_panels as $p): ?>
                <div class="tv-panel-row" style="background:var(--tv-surface-active); padding:15px; border-radius:8px; margin-bottom:15px; border:1px solid var(--tv-border);">
                    <div class="tv-row" style="margin-bottom:10px;">
                        <div class="tv-col">
                            <label class="tv-label">Panel Name</label>
                            <input type="text" name="panels[name][]" value="<?php echo esc_attr($p['name']); ?>" class="tv-input">
                        </div>
                    </div>
                    <div class="tv-row" style="margin-bottom:0;">
                        <div class="tv-col">
                            <label class="tv-label">Smart TV URL</label>
                            <input type="url" name="panels[smart_tv_url][]" value="<?php echo esc_attr($p['smart_tv_url']); ?>" class="tv-input">
                        </div>
                        <div class="tv-col">
                            <label class="tv-label">XTREAM Base URL</label>
                            <input type="url" name="panels[xtream_url][]" value="<?php echo esc_attr($p['xtream_url']); ?>" class="tv-input">
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
            
            <!-- Empty Row for New Panel -->
            <div class="tv-panel-row" style="background:var(--tv-surface); padding:15px; border-radius:8px; margin-bottom:15px; border:1px dashed var(--tv-primary);">
                <div style="font-size:11px; font-weight:700; color:var(--tv-primary); margin-bottom:10px; text-transform:uppercase;">+ Add New Panel</div>
                <div class="tv-row" style="margin-bottom:10px;">
                    <div class="tv-col">
                        <label class="tv-label">Panel Name</label>
                        <input type="text" name="panels[name][]" class="tv-input" placeholder="e.g. New Panel">
                    </div>
                </div>
                <div class="tv-row" style="margin-bottom:0;">
                    <div class="tv-col">
                        <label class="tv-label">Smart TV URL</label>
                        <input type="url" name="panels[smart_tv_url][]" class="tv-input">
                    </div>
                    <div class="tv-col">
                        <label class="tv-label">XTREAM Base URL</label>
                        <input type="url" name="panels[xtream_url][]" class="tv-input">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FIX: Changed from tv-grid-2 side-by-side layout to stacked layout 
     to prevent dropdown overlap between adjacent cards -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:28px; align-items:start;">
    <!-- Legacy Duration Discounts -->
    <div class="tv-card" style="overflow:visible;">
        <div class="tv-card-header">
            <h3>Global Duration Discounts</h3>
        </div>
        <div class="tv-card-body" style="overflow:visible;">
            <?php 
            $saved_discounts = get_option('tv_duration_discounts', []);
            if(!empty($saved_discounts) && is_array($saved_discounts)): 
                foreach($saved_discounts as $d): ?>
                <div class="tv-row" style="align-items:flex-end; border-bottom:1px dashed var(--tv-border); padding-bottom:10px; margin-bottom:10px;">
                    <div class="tv-col">
                        <label class="tv-label">Min Days</label>
                        <input type="number" name="discounts[days][]" value="<?php echo esc_attr($d['days']); ?>" class="tv-input">
                    </div>
                    <div class="tv-col">
                        <label class="tv-label">Discount %</label>
                        <input type="number" step="0.1" name="discounts[percent][]" value="<?php echo esc_attr($d['percent']); ?>" class="tv-input">
                    </div>
                </div>
            <?php endforeach; endif; ?>
            
            <div class="tv-row" style="align-items:flex-end;">
                <div class="tv-col">
                    <label class="tv-label">Min Days</label>
                    <input type="number" name="discounts[days][]" class="tv-input" placeholder="e.g. 180">
                </div>
                <div class="tv-col">
                    <label class="tv-label">Discount %</label>
                    <input type="number" step="0.1" name="discounts[percent][]" class="tv-input" placeholder="e.g. 10">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Checkout Flow — FIX: z-index and overflow for dropdowns -->
    <div class="tv-card" style="overflow:visible; position:relative; z-index:10;">
        <div class="tv-card-header">
            <h3>Checkout Flow</h3>
        </div>
        <div class="tv-card-body" style="overflow:visible;">
            <label style="display:flex; align-items:center; gap:10px; font-weight:600; margin-bottom:15px;">
                <input type="checkbox" name="tv_multi_step_checkout" value="1" <?php checked(get_option('tv_multi_step_checkout', 0), 1); ?>>
                Enable Multi-step Checkout
            </label>

            <div class="tv-form-group" style="position:relative; z-index:14;">
                <label class="tv-label">Plans Page</label>
                <?php wp_dropdown_pages(['name'=>'tv_plans_page_id', 'show_option_none'=>'-- Not set --', 'option_none_value'=>0, 'selected'=>intval(get_option('tv_plans_page_id', 0)), 'class'=>'tv-input']); ?>
            </div>
            <div class="tv-form-group" style="position:relative; z-index:13;">
                <label class="tv-label">Method Select Page</label>
                <?php wp_dropdown_pages(['name'=>'tv_select_method_page_id', 'show_option_none'=>'-- Not set --', 'option_none_value'=>0, 'selected'=>intval(get_option('tv_select_method_page_id', 0)), 'class'=>'tv-input']); ?>
            </div>
            <div class="tv-form-group" style="position:relative; z-index:12;">
                <label class="tv-label">Payment Page</label>
                <?php wp_dropdown_pages(['name'=>'tv_payment_page_id', 'show_option_none'=>'-- Not set --', 'option_none_value'=>0, 'selected'=>intval(get_option('tv_payment_page_id', 0)), 'class'=>'tv-input']); ?>
            </div>
            <div class="tv-form-group" style="position:relative; z-index:11;">
                <label class="tv-label">Upload Proof Page</label>
                <?php wp_dropdown_pages(['name'=>'tv_upload_proof_page_id', 'show_option_none'=>'-- Not set --', 'option_none_value'=>0, 'selected'=>intval(get_option('tv_upload_proof_page_id', 0)), 'class'=>'tv-input']); ?>
            </div>
        </div>
    </div>
</div>

<div class="tv-card" style="position:sticky; bottom:20px; z-index:99; border-top: 4px solid var(--tv-primary);">
    <div class="tv-card-body" style="display:flex; justify-content:flex-end; padding:15px 24px;">
        <button type="submit" name="save_settings" class="tv-btn tv-btn-primary" style="height:40px; padding:0 30px; font-weight:600;">
            Save All Settings
        </button>
    </div>
</div>

</form>