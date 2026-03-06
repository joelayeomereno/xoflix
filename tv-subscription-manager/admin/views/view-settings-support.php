<div class="tv-page-header">
    <div>
        <h1>Support Channels</h1>
        <p>Configure contact methods displayed to users.</p>
    </div>
</div>

<form method="post" action="?page=tv-settings-general&tab=support">
<?php wp_nonce_field('tv_settings_verify'); ?>

<div class="tv-card">
    <div class="tv-card-header">
        <h3><span class="dashicons dashicons-sos" style="margin-right:8px;"></span> Contact Details</h3>
    </div>
    <div class="tv-card-body">
        <div class="tv-row">
            <div class="tv-col">
                <label class="tv-label">WhatsApp Number</label>
                <input type="text" name="tv_support_whatsapp" value="<?php echo esc_attr(get_option('tv_support_whatsapp')); ?>" class="tv-input" placeholder="e.g. 15551234567">
            </div>
            <div class="tv-col">
                <label class="tv-label">Support Email</label>
                <input type="email" name="tv_support_email" value="<?php echo esc_attr(get_option('tv_support_email')); ?>" class="tv-input" placeholder="support@domain.com">
            </div>
        </div>
        
        <div class="tv-form-group">
            <label class="tv-label">Telegram / Extra Channel (Optional)</label>
            <input type="text" name="tv_support_telegram" value="<?php echo esc_attr(get_option('tv_support_telegram')); ?>" class="tv-input" placeholder="e.g. https://t.me/yourchannel">
        </div>
    </div>
</div>

<div class="tv-card" style="position:sticky; bottom:20px; z-index:99; border-top: 4px solid var(--tv-primary);">
    <div class="tv-card-body" style="display:flex; justify-content:flex-end; padding:15px 24px;">
        <button type="submit" name="save_settings" class="tv-btn tv-btn-primary" style="height:40px; padding:0 30px; font-weight:600;">
            Save Support Settings
        </button>
    </div>
</div>

</form>