<div class="tv-page-header">
    <div>
        <h1>Panel Configuration</h1>
        <p>Manage XTREAM panels and their connection details.</p>
    </div>
</div>

<form method="post" action="?page=tv-settings-general&tab=panels">
<?php wp_nonce_field('tv_settings_verify'); ?>

<div class="tv-card">
    <div class="tv-card-header">
        <h3><span class="dashicons dashicons-desktop" style="margin-right:8px;"></span> Connected Panels</h3>
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
            <div class="tv-panel-row" style="background:#fff; padding:15px; border-radius:8px; margin-bottom:15px; border:1px dashed var(--tv-primary);">
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

<div class="tv-card" style="position:sticky; bottom:20px; z-index:99; border-top: 4px solid var(--tv-primary);">
    <div class="tv-card-body" style="display:flex; justify-content:flex-end; padding:15px 24px;">
        <button type="submit" name="save_settings" class="tv-btn tv-btn-primary" style="height:40px; padding:0 30px; font-weight:600;">
            Save Panels
        </button>
    </div>
</div>

</form>