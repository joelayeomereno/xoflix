<div class="tv-page-header">
    <div>
        <h1>General Settings</h1>
        <p>Configure checkout flow, global pricing rules, and sign-up verification.</p>
    </div>
</div>

<form method="post" action="?page=tv-settings-general&tab=general">
<?php wp_nonce_field('tv_settings_verify'); ?>

<div class="tv-grid-2">
    <!-- Legacy Duration Discounts -->
    <div class="tv-card">
        <div class="tv-card-header">
            <h3>Global Duration Discounts</h3>
        </div>
        <div class="tv-card-body">
            <p style="font-size:12px; color:var(--tv-text-muted); margin-bottom:15px;">
                These discounts apply to ALL plans unless overridden by specific plan settings.
            </p>
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
                    <div class="tv-field" style="margin-bottom:0;">
                        <input type="number" name="discounts[days][]" class="tv-control tv-input" placeholder=" ">
                        <label class="tv-label">Min Days</label>
                        <div class="tv-help">e.g. 180</div>
                    </div>
                </div>
                <div class="tv-col">
                    <div class="tv-field" style="margin-bottom:0;">
                        <input type="number" step="0.1" name="discounts[percent][]" class="tv-control tv-input" placeholder=" ">
                        <label class="tv-label">Discount %</label>
                        <div class="tv-help">e.g. 10</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Checkout Flow -->
    <div class="tv-card">
        <div class="tv-card-header">
            <h3>Checkout Page Configuration</h3>
        </div>
        <div class="tv-card-body">
            <label style="display:flex; align-items:center; gap:10px; font-weight:600; margin-bottom:15px;">
                <input type="checkbox" name="tv_multi_step_checkout" value="1" <?php checked(get_option('tv_multi_step_checkout', 0), 1); ?>>
                Enable Multi-step Checkout Logic
            </label>

            <div class="tv-field" style="position:relative; z-index:14;">
                <?php wp_dropdown_pages(['name'=>'tv_plans_page_id', 'show_option_none'=>'-- Not set --', 'option_none_value'=>0, 'selected'=>intval(get_option('tv_plans_page_id', 0)), 'class'=>'tv-control tv-input']); ?>
                <label class="tv-label">Plans Page</label>
            </div>
            <div class="tv-field" style="position:relative; z-index:13;">
                <?php wp_dropdown_pages(['name'=>'tv_select_method_page_id', 'show_option_none'=>'-- Not set --', 'option_none_value'=>0, 'selected'=>intval(get_option('tv_select_method_page_id', 0)), 'class'=>'tv-control tv-input']); ?>
                <label class="tv-label">Method Select Page</label>
            </div>
            <div class="tv-field" style="position:relative; z-index:12;">
                <?php wp_dropdown_pages(['name'=>'tv_payment_page_id', 'show_option_none'=>'-- Not set --', 'option_none_value'=>0, 'selected'=>intval(get_option('tv_payment_page_id', 0)), 'class'=>'tv-control tv-input']); ?>
                <label class="tv-label">Payment Page</label>
            </div>
            <div class="tv-field" style="position:relative; z-index:11;">
                <?php wp_dropdown_pages(['name'=>'tv_upload_proof_page_id', 'show_option_none'=>'-- Not set --', 'option_none_value'=>0, 'selected'=>intval(get_option('tv_upload_proof_page_id', 0)), 'class'=>'tv-control tv-input']); ?>
                <label class="tv-label">Upload Proof Page</label>
            </div>
        </div>
    </div>
</div>

<!-- -- Email + Phone Verification ------------------------------------------ -->
<div class="tv-card" style="margin-top:0;">
    <div class="tv-card-header" style="display:flex; align-items:center; gap:12px;">
        <span class="dashicons dashicons-shield-alt" style="font-size:20px; color:var(--tv-primary);"></span>
        <div>
            <h3 style="margin:0;">Sign-up Verification</h3>
            <p style="margin:4px 0 0; font-size:12px; color:var(--tv-text-muted);">
                When enabled, new users must provide a phone number and verify their email before accessing the dashboard.
            </p>
        </div>
    </div>
    <div class="tv-card-body">

        <?php $verif_on = (bool) get_option('streamos_require_email_verification', 0); ?>

        <!-- Toggle switch -->
        <label style="display:flex; align-items:flex-start; gap:14px; cursor:pointer; padding:16px; background:var(--tv-surface-active); border-radius:12px; border:1px solid var(--tv-border);">
            <div style="position:relative; flex-shrink:0; margin-top:2px;">
                <input type="checkbox"
                       name="streamos_require_email_verification"
                       id="verif_toggle"
                       value="1"
                       <?php checked($verif_on, true); ?>
                       style="opacity:0; width:0; height:0; position:absolute;"
                       onchange="document.getElementById('verif-toggle-track').style.background=this.checked?'var(--tv-primary)':'#cbd5e1'; document.getElementById('verif-toggle-thumb').style.transform=this.checked?'translateX(22px)':'translateX(2px)';">
                <!-- Visual toggle track -->
                <div id="verif-toggle-track"
                     onclick="document.getElementById('verif_toggle').click()"
                     style="width:46px; height:26px; border-radius:13px; cursor:pointer; transition:background .2s;
                            background:<?= $verif_on ? 'var(--tv-primary)' : '#cbd5e1' ?>;">
                    <div id="verif-toggle-thumb"
                         style="width:22px; height:22px; background:#fff; border-radius:50%;
                                margin-top:2px; box-shadow:0 1px 4px rgba(0,0,0,.2);
                                transition:transform .2s;
                                transform:<?= $verif_on ? 'translateX(22px)' : 'translateX(2px)' ?>;">
                    </div>
                </div>
            </div>
            <div>
                <strong style="font-size:14px; display:block; margin-bottom:4px;">
                    Require email verification + phone number during sign-up
                </strong>
                <span style="font-size:12px; color:var(--tv-text-muted); line-height:1.5;">
                    When <strong>ON</strong>: a phone field appears on the sign-up form, disposable emails are blocked,
                    and new users see a dashboard lockdown overlay until they enter the 6-digit code sent to their email.<br>
                    When <strong>OFF</strong>: sign-up behaves as normal — no phone field, no verification step.
                </span>
            </div>
        </label>

        <?php if ($verif_on): ?>
        <div style="margin-top:16px; padding:14px 16px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; font-size:13px; color:#15803d; display:flex; align-items:flex-start; gap:10px;">
            <span class="dashicons dashicons-yes-alt" style="flex-shrink:0; margin-top:1px;"></span>
            <span>
                Verification is <strong>active</strong>. New sign-ups will be prompted to verify their email.
                Existing users who have already verified are not affected.
                Admins are always exempt from the verification gate.
            </span>
        </div>
        <?php else: ?>
        <div style="margin-top:16px; padding:14px 16px; background:#f8fafc; border:1px solid var(--tv-border); border-radius:10px; font-size:13px; color:var(--tv-text-muted); display:flex; align-items:flex-start; gap:10px;">
            <span class="dashicons dashicons-info-outline" style="flex-shrink:0; margin-top:1px;"></span>
            <span>Verification is <strong>off</strong>. Sign-up works as normal.</span>
        </div>
        <?php endif; ?>

    </div>
</div>
<!-- -- / End Email + Phone Verification ----------------------------------- -->

<div class="tv-card" style="position:sticky; bottom:20px; z-index:99; border-top: 4px solid var(--tv-primary);">
    <div class="tv-card-body" style="display:flex; justify-content:flex-end; padding:15px 24px;">
        <button type="submit" name="save_settings" class="tv-btn tv-btn-primary" style="height:40px; padding:0 30px; font-weight:600;">
            Save General Settings
        </button>
    </div>
</div>

</form>