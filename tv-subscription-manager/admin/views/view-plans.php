<?php
/**
 * File: tv-subscription-manager/admin/views/view-plans.php
 * View for managing subscription plans (Add/Edit/Delete).
 * Upgrade: Modern Interface & Perfect Badge Placement.
 */

if (!defined('ABSPATH')) { exit; }

// Prepare Variables
$is_edit = !empty($edit_plan);
$val_name = $is_edit ? $edit_plan->name : '';
$val_price = $is_edit ? $edit_plan->price : '';
$val_dur = $is_edit ? $edit_plan->duration_days : '30';
$val_desc = $is_edit ? $edit_plan->description : '';
$val_multi = $is_edit ? $edit_plan->allow_multi_connections : 1;

// NEW: Subscription Class & Display Order
$val_cat = $is_edit && isset($edit_plan->category) ? $edit_plan->category : 'standard';
$val_order = $is_edit && isset($edit_plan->display_order) ? intval($edit_plan->display_order) : 0;

$val_tiers = ($is_edit && !empty($edit_plan->discount_tiers)) ? json_decode($edit_plan->discount_tiers, true) : [];
?>

<!-- Scoped Styles for Admin Plans -->
<style>
    .tv-plans-wrapper {
        --prem-bg: #ffffff;
        --prem-text: #0f172a;
        --prem-text-muted: #64748b;
        --prem-primary: #6366f1;
        --prem-primary-dark: #4f46e5;
        --prem-surface: #ffffff;
        --prem-surface-hover: #f8fafc;
        --prem-border: #e2e8f0;
        --prem-shadow: 0 10px 30px -5px rgba(0,0,0,0.08);
        --prem-radius: 20px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    /* Sortable Containers */
    .tv-plans-category-group {
        margin-bottom: 40px;
        padding: 24px;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 16px;
    }

    .tv-plans-grid {
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 30px; 
        align-items: start;
        margin-top: 20px;
        min-height: 100px; /* Target for drag */
    }
    
    /* Draggable Card */
    .tv-plan-card {
        background: white; 
        border: 1px solid var(--prem-border); 
        border-radius: var(--prem-radius);
        padding: 32px 24px 24px 24px; /* Top padding for content clearing */
        display: flex; 
        flex-direction: column;
        transition: transform 0.2s, box-shadow 0.2s; 
        position: relative; 
        cursor: grab;
        overflow: visible; /* CRITICAL: Allows badge to overlap border */
        min-height: 380px;
    }
    
    .tv-plan-card:hover { 
        transform: translateY(-4px); 
        box-shadow: var(--prem-shadow); 
        border-color: #c7d2fe; 
        z-index: 10;
    }
    
    .tv-plan-card:active {
        cursor: grabbing;
    }

    /* PREMIUM / FEATURED VARIANT */
    .tv-plan-card.featured {
        border: 2px solid var(--prem-primary);
        background: linear-gradient(180deg, #fbfbfe 0%, #ffffff 100%);
    }

    /* Dragging State */
    .tv-plan-card.ui-sortable-helper {
        box-shadow: 0 25px 50px rgba(0,0,0,0.25);
        transform: scale(1.03);
        opacity: 0.95;
        z-index: 100;
        cursor: grabbing;
    }
    .ui-sortable-placeholder {
        border: 2px dashed #cbd5e1;
        background: #f1f5f9;
        border-radius: var(--prem-radius);
        visibility: visible !important;
        height: 380px;
    }
    
    /* BADGE: Center Top Overlap */
    .tv-feat-badge {
        position: absolute; 
        top: -14px; /* Pull up half its height */
        left: 50%; 
        transform: translateX(-50%);
        background: linear-gradient(135deg, var(--prem-primary) 0%, var(--prem-primary-dark) 100%);
        color: white; 
        font-size: 11px; 
        font-weight: 800;
        text-transform: uppercase; 
        padding: 6px 18px; 
        border-radius: 99px;
        letter-spacing: 1px; 
        box-shadow: 0 4px 12px rgba(99,102,241,0.4);
        white-space: nowrap;
        border: 2px solid white; /* Cutout effect */
    }

    /* Order Badge (Top Right) */
    .tv-order-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        font-size: 10px;
        font-weight: 700;
        background: #f1f5f9;
        color: var(--prem-text-muted);
        padding: 4px 8px;
        border-radius: 6px;
        z-index: 2;
    }
    
    /* Drag Handle (Top Left) */
    .tv-handle-icon {
        position: absolute;
        top: 12px;
        left: 12px;
        color: #94a3b8;
        font-size: 18px;
        opacity: 0.5;
        transition: opacity 0.2s;
    }
    .tv-plan-card:hover .tv-handle-icon { opacity: 1; color: var(--prem-primary); }

    /* Typography */
    .tv-p-name { font-size: 1.3rem; font-weight: 800; margin-bottom: 8px; color: var(--prem-text); text-align: center; margin-top: 12px; letter-spacing: -0.02em; }
    .tv-p-price { font-size: 2.8rem; font-weight: 900; color: var(--prem-text); line-height: 1; margin-bottom: 6px; letter-spacing: -1.5px; text-align: center; }
    .tv-p-curr { font-size: 1.2rem; vertical-align: top; margin-right: 2px; opacity: 0.6; font-weight: 700; }
    .tv-p-dur { color: var(--prem-text-muted); font-size: 0.85rem; font-weight: 600; margin-bottom: 24px; text-align: center; display: flex; align-items: center; justify-content: center; gap: 6px; }
    .tv-p-dur::before, .tv-p-dur::after { content: ''; display: block; width: 20px; height: 1px; background: var(--prem-border); }
    
    /* Features List */
    .tv-p-feats { list-style: none; padding: 0; margin: 0 0 24px 0; border-top: 1px dashed var(--prem-border); padding-top: 20px; flex-grow: 1; }
    .tv-p-feats li { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 12px; color: #334155; font-size: 13px; line-height: 1.4; font-weight: 500; }
    .tv-check-icon { color: #10b981; background: #ecfdf5; border-radius: 50%; padding: 2px; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    
    /* Actions */
    .tv-action-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: auto; }
    .tv-action-btn { padding: 10px; border-radius: 10px; font-weight: 700; font-size: 12px; cursor: pointer; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s; }
    
    .tv-btn-edit { background: white; border: 1px solid var(--prem-border); color: var(--prem-text); }
    .tv-btn-edit:hover { border-color: var(--prem-primary); color: var(--prem-primary); background: #f8fafc; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    
    .tv-btn-delete { background: #fff1f2; border: 1px solid #ffe4e6; color: #e11d48; }
    .tv-btn-delete:hover { background: #ffe4e6; border-color: #fecdd3; color: #be123c; }

    /* SAVE BUTTON STATE */
    #tv-save-order-btn {
        transition: all 0.3s ease;
        opacity: 0.7;
    }
    #tv-save-order-btn.is-dirty {
        background-color: var(--prem-primary);
        border-color: var(--prem-primary);
        color: white;
        opacity: 1;
        box-shadow: 0 4px 12px rgba(99,102,241,0.4);
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(99, 102, 241, 0); }
        100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
    }
</style>

<div class="tv-content-area tv-plans-wrapper">
    <div class="tv-page-header">
        <div>
            <h1>Subscription Plans</h1>
            <p>Manage your pricing tiers. <strong>Drag and drop</strong> to reorder, then click <strong>Save Order</strong>.</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <!-- SAVE ORDER BUTTON (Manual Trigger) -->
            <button type="button" id="tv-save-order-btn" class="tv-btn tv-btn-secondary" style="font-weight: 700;" disabled>
                <span class="dashicons dashicons-sort" style="margin-right:6px;"></span> Save Display Order
            </button>

            <?php if($is_edit): ?>
                <a href="?page=tv-subs-manager&tab=plans" class="tv-btn tv-btn-secondary">
                    <span class="dashicons dashicons-no-alt" style="margin-right:6px;"></span> Cancel Edit
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- SAVE ORDER NOTIFICATION -->
    <div id="tv-sort-notice" class="notice notice-success is-dismissible" style="display:none; margin-left:0; margin-bottom:20px;">
        <p>Plan order updated successfully!</p>
    </div>

    <!-- Global WhatsApp Trial Number Configuration -->
    <?php $tv_whatsapp = get_option('tv_support_whatsapp', ''); ?>
    <div class="tv-card" style="margin:0 0 20px 0;">
        <div class="tv-card-header" style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
            <div>
                <h3 style="margin:0;">Trial & WhatsApp Destination</h3>
                <p style="margin:6px 0 0 0; color:var(--tv-text-muted); font-size:13px;">Set the global WhatsApp number used by <b>Request Trial</b> buttons across all plans.</p>
            </div>
            <button type="button" class="tv-btn tv-btn-secondary" onclick="window.TVOpenWhatsAppModal && window.TVOpenWhatsAppModal()">Configure</button>
        </div>
        <div class="tv-card-body">
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <div class="tv-badge pending" style="background:var(--tv-surface-active); border-color:var(--tv-border); color:var(--tv-text);">
                    Current: <span style="font-family:monospace; font-weight:800; margin-left:6px;"><?php echo esc_html(!empty($tv_whatsapp) ? $tv_whatsapp : '(not set)'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- WhatsApp Number Modal -->
    <div id="tv-whatsapp-modal" style="display:none; position:fixed; inset:0; z-index:100000; background:rgba(15,23,42,0.55); padding:24px;">
        <div style="max-width:520px; margin:8vh auto; background:var(--tv-surface); border:1px solid var(--tv-border); border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,0.35); overflow:hidden;">
            <div style="padding:18px 20px; border-bottom:1px solid var(--tv-border); display:flex; align-items:center; justify-content:space-between;">
                <div style="font-weight:900;">Configure WhatsApp Number</div>
                <button type="button" class="tv-btn tv-btn-secondary" style="height:34px; padding:0 12px;" onclick="window.TVCloseWhatsAppModal && window.TVCloseWhatsAppModal()">Close</button>
            </div>
            <div style="padding:20px;">
                <form method="post" action="?page=tv-subs-manager&tab=plans">
                    <?php wp_nonce_field('tv_save_whatsapp_number'); ?>
                    <div class="tv-form-group">
                        <label class="tv-label">WhatsApp Number</label>
                        <input type="text" name="tv_support_whatsapp" class="tv-input" value="<?php echo esc_attr($tv_whatsapp); ?>" placeholder="+3069XXXXXXXX">
                        <p style="margin:10px 0 0 0; color:var(--tv-text-muted); font-size:12px;">This single number is used for all plan trial requests.</p>
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:10px;">
                        <button type="button" class="tv-btn tv-btn-secondary" onclick="window.TVCloseWhatsAppModal && window.TVCloseWhatsAppModal()">Cancel</button>
                        <button type="submit" name="save_whatsapp_number" class="tv-btn tv-btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    window.TVOpenWhatsAppModal = function(){
        var m = document.getElementById('tv-whatsapp-modal');
        if(m) m.style.display = 'block';
    };
    window.TVCloseWhatsAppModal = function(){
        var m = document.getElementById('tv-whatsapp-modal');
        if(m) m.style.display = 'none';
    };
    document.addEventListener('keydown', function(e){
        if(e.key === 'Escape') window.TVCloseWhatsAppModal && window.TVCloseWhatsAppModal();
    });
    </script>

    <div class="tv-grid-2">
        <!-- Add/Edit Plan Form -->
        <div class="tv-card" style="height: fit-content;">
            <div class="tv-card-header">
                <h3><?php echo $is_edit ? 'Edit Plan' : 'Add New Plan'; ?></h3>
            </div>
            <div class="tv-card-body">
                <form method="post" action="?page=tv-subs-manager&tab=plans">
                    <?php wp_nonce_field('add_plan_verify'); ?>
                    <?php if($is_edit): ?><input type="hidden" name="plan_id" value="<?php echo $edit_plan->id; ?>"><?php endif; ?>
                    
                    <div class="tv-form-group">
                        <label class="tv-label">Plan Name</label>
                        <input type="text" name="plan_name" required class="tv-input" value="<?php echo esc_attr($val_name); ?>" placeholder="e.g. Premium Monthly">
                    </div>
                    
                    <div class="tv-row">
                        <div class="tv-col">
                            <label class="tv-label">Subscription Class</label>
                            <select name="plan_category" class="tv-input">
                                <option value="standard" <?php selected($val_cat, 'standard'); ?>>Standard Tier</option>
                                <option value="premium" <?php selected($val_cat, 'premium'); ?>>Premium Tier</option>
                            </select>
                        </div>
                        <div class="tv-col">
                            <label class="tv-label">Manual Order</label>
                            <input type="number" name="display_order" class="tv-input" value="<?php echo esc_attr($val_order); ?>" placeholder="0">
                        </div>
                    </div>

                    <div class="tv-row">
                        <div class="tv-col">
                            <label class="tv-label">Price (Base)</label>
                            <input type="number" step="0.01" name="plan_price" required class="tv-input" value="<?php echo esc_attr($val_price); ?>" placeholder="0.00">
                        </div>
                        <div class="tv-col">
                            <label class="tv-label">Cycle (Days)</label>
                            <input type="number" name="plan_duration" required class="tv-input" value="<?php echo esc_attr($val_dur); ?>" placeholder="30">
                        </div>
                    </div>

                    <div class="tv-form-group" style="background:#f8fafc; padding:15px; border-radius:8px; border:1px solid var(--tv-border);">
                        <label class="tv-label" style="display:flex; align-items:center; gap:10px; cursor:pointer; margin:0;">
                            <input type="checkbox" name="multi_conn" value="1" <?php checked($val_multi, 1); ?> style="width:18px; height:18px; accent-color:var(--tv-primary);">
                            <span>Allow Multiple Connections</span>
                        </label>
                    </div>

                    <div class="tv-form-group" style="background:#fff7ed; padding:20px; border-radius:12px; border:1px solid #ffedd5;">
                        <label class="tv-label" style="color:#9a3412; font-weight:800; font-size:14px; margin-bottom:12px;">
                            <span class="dashicons dashicons-tag" style="font-size:16px; width:16px; height:16px;"></span>
                            Quantity Discounts
                        </label>
                        <div id="tier-rows" style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                            <?php 
                            $display_tiers = $val_tiers;
                            while(count($display_tiers) < 4) { $display_tiers[] = ['months' => '', 'percent' => '']; }
                            $display_tiers = array_slice($display_tiers, 0, 4);

                            foreach($display_tiers as $idx => $t): 
                                $tier_num = $idx + 1;
                            ?>
                                <div style="background:white; border:1px solid #fed7aa; padding:10px; border-radius:8px;">
                                    <div style="font-size:10px; font-weight:700; color:#ea580c; text-transform:uppercase; margin-bottom:6px;">Tier <?php echo $tier_num; ?></div>
                                    <div style="display:flex; gap:8px;">
                                        <div style="flex:1;">
                                            <input type="number" name="tier_qty[]" value="<?php echo esc_attr($t['months']); ?>" class="tv-input" style="padding:6px; font-size:13px;" placeholder="Mo">
                                        </div>
                                        <div style="flex:1; position:relative;">
                                            <input type="number" step="0.1" name="tier_discount[]" value="<?php echo esc_attr($t['percent']); ?>" class="tv-input" style="padding:6px; font-size:13px;" placeholder="%">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="tv-form-group">
                        <label class="tv-label">Features (One per line)</label>
                        <textarea name="plan_desc" class="tv-textarea" rows="4"><?php echo esc_textarea($val_desc); ?></textarea>
                    </div>

                    <button type="submit" name="submit_plan" class="tv-btn tv-btn-primary w-full" style="height:48px; font-size:15px; font-weight:700;">
                        <?php echo $is_edit ? 'Update Plan' : 'Create Plan'; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- STORE PREVIEW / SORTABLE GRIDS -->
        <div>
            <?php 
            if($plans): 
                // Group plans by category
                $grouped_plans = [];
                foreach($plans as $p) {
                    $cat = isset($p->category) ? $p->category : 'standard';
                    $grouped_plans[$cat][] = $p;
                }

                // Define preferred order of categories
                $cat_order = ['premium', 'standard']; // Premium first, then Standard
                // Add any other categories found that aren't in the explicit list
                foreach(array_keys($grouped_plans) as $k) {
                    if(!in_array($k, $cat_order)) $cat_order[] = $k;
                }
            ?>

            <?php foreach($cat_order as $cat): 
                if(empty($grouped_plans[$cat])) continue;
                $plans_in_cat = $grouped_plans[$cat];
                $cat_label = ucfirst($cat) . ' Plans';
                $is_prem_cat = ($cat === 'premium');
            ?>
            <div class="tv-plans-category-group">
                <h3 style="margin:0; font-size:14px; text-transform:uppercase; color:var(--tv-text-muted); font-weight:700;">
                    <?php echo esc_html($cat_label); ?>
                </h3>
                
                <!-- Sortable Grid -->
                <div class="tv-plans-grid tv-sortable-plans" data-category="<?php echo esc_attr($cat); ?>">
                    <?php foreach($plans_in_cat as $p): ?>
                        <!-- Plan Card -->
                        <div class="tv-plan-card <?php echo $is_prem_cat ? 'featured' : ''; ?>" data-id="<?php echo $p->id; ?>">
                            
                            <!-- UI Controls -->
                            <div class="tv-handle-icon"><span class="dashicons dashicons-move"></span></div>
                            <div class="tv-order-badge">#<?php echo (int)$p->display_order; ?></div>

                            <!-- "Perfect" Premium Badge placement -->
                            <?php if($is_prem_cat): ?>
                                <div class="tv-feat-badge">Premium</div>
                            <?php endif; ?>

                            <div class="tv-p-name"><?php echo esc_html($p->name); ?></div>
                            <div class="tv-p-price">
                                <span class="tv-p-curr">$</span><?php echo esc_html($p->price); ?>
                            </div>
                            <div class="tv-p-dur">per <?php echo $p->duration_days; ?> days</div>

                            <ul class="tv-p-feats">
                                <?php 
                                $features = explode("\n", $p->description);
                                foreach(array_slice($features, 0, 3) as $f) {
                                    if(trim($f)) echo '<li><span class="tv-check-icon"><span class="dashicons dashicons-yes" style="font-size:12px; margin-top:-2px;"></span></span> '.esc_html($f).'</li>';
                                }
                                ?>
                            </ul>

                            <div class="tv-action-row">
                                <a href="?page=tv-subs-manager&tab=plans&action=edit&id=<?php echo $p->id; ?>" class="tv-action-btn tv-btn-edit">
                                   <span class="dashicons dashicons-edit"></span> Edit
                                </a>
                                <a href="<?php echo wp_nonce_url('?page=tv-subs-manager&tab=plans&action=delete_plan&id='.$p->id, 'delete_plan_'.$p->id); ?>" class="tv-action-btn tv-btn-delete" data-tv-delete="1">
                                   <span class="dashicons dashicons-trash"></span> Del
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <?php else: ?>
                <div class="tv-card" style="text-align:center; padding:60px;">
                    <h3 style="color:var(--tv-text-muted);">No plans found. Create one to get started.</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>