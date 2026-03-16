<?php
/**
 * File: tv-subscription-manager/admin/views/view-settings-channel-engine.php
 * Path: tv-subscription-manager/admin/views/view-settings-channel-engine.php
 * Version: 7.6.1 (Unabridged Full-Scale Source)
 * * DESCRIPTION:
 * Comprehensive configuration interface for the Smart Broadcaster Extractor (SBE).
 * Manages active countries, standardization renames, subtractive word stripping, 
 * output logic controls (Strict Dedupe), and drag-and-drop regional priority.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Fetch System Configurations
$exclusions    = get_option( 'tv_sbe_exclusions', '' );
$rules         = get_option( 'tv_sbe_transform_rules', array() );
$priority      = get_option( 'tv_sbe_priority', array() );
$strict_dedupe = get_option( 'tv_sbe_strict_dedupe', 0 );

// 2. Resolve Active Country List
$all_countries = TV_Channel_Engine::get_all_countries();
$saved_active  = get_option( 'tv_sbe_active_countries' );

// If nothing saved, default to ALL countries (Global Mode)
$active_list = ( is_array( $saved_active ) && ! empty( $saved_active ) ) ? $saved_active : $all_countries;

// 3. Prepare Priority Display Order
$priority_valid    = array_intersect( $priority, $active_list );
$priority_new      = array_diff( $active_list, $priority );
$display_countries = array_merge( $priority_valid, $priority_new );
$display_countries = array_values( array_filter( $display_countries ) );
?>

<div class="tv-page-header">
    <div>
        <h1>Channel Engine Configuration</h1>
        <p>Fine-tune the extraction intelligence, word-stripping logic, and regional output priority.</p>
    </div>
</div>

<form method="post" action="?page=tv-settings-general&tab=channel-engine">
    <?php wp_nonce_field( 'tv_settings_verify' ); ?>

    <!-- SECTION 1: OUTPUT LOGIC CONTROLS (THE STRATEGIST) -->
    <div class="tv-card" style="border-left: 4px solid var(--tv-primary); margin-bottom: 32px; box-shadow: var(--tv-shadow);">
        <div class="tv-card-header">
            <h3><span class="dashicons dashicons-admin-settings" style="margin-right:8px;"></span> Global Extraction Intelligence</h3>
        </div>
        <div class="tv-card-body" style="display:flex; align-items:center; justify-content:space-between; padding: 24px;">
            <div style="max-width: 70%;">
                <strong style="display:block; font-size:15px; color:var(--tv-text); margin-bottom: 4px;">Strict Global Deduplication</strong>
                <p style="margin:0; font-size:13px; color:var(--tv-text-muted); line-height: 1.5;">
                    When enabled, the extractor performs a recursive scan across all regions. If a channel name (e.g., "Sky Sports") is found in multiple countries, it will only be outputted once. This prevents cluttered sports guides.
                </p>
            </div>
            <label class="tv-switch">
                <input type="checkbox" name="tv_sbe_strict_dedupe" value="1" <?php checked( $strict_dedupe, 1 ); ?> class="tv-toggle-input">
                <span class="tv-toggle-ui" aria-hidden="true"></span>
            </label>
        </div>
    </div>

    <!-- SECTION 2: GEO-FENCING (ACTIVE COUNTRIES) -->
    <div class="tv-card" style="margin-bottom: 32px;">
        <div class="tv-card-header" style="justify-content: space-between;">
            <h3><span class="dashicons dashicons-globe" style="margin-right:8px;"></span> Extraction Context (Active Countries)</h3>
            <div style="display:flex; gap:10px;">
                <button type="button" class="tv-btn tv-btn-secondary tv-btn-sm" onclick="toggleCountries(true)">Select All</button>
                <button type="button" class="tv-btn tv-btn-secondary tv-btn-sm" onclick="toggleCountries(false)">Deselect All</button>
            </div>
        </div>
        <div class="tv-card-body">
            <p style="font-size:13px; color:var(--tv-text-muted); margin-bottom:20px;">
                Select specific countries to enable the <strong>Context-Guard</strong>. The engine will ignore any text segments belonging to unselected countries.
            </p>

            <input type="hidden" name="tv_sbe_countries_submit" value="1">

            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap:12px; max-height:450px; overflow-y:auto; padding:20px; border:1px solid var(--tv-border); border-radius:16px; background:var(--tv-surface-active);" class="custom-scrollbar">
                <?php foreach ( $all_countries as $country ) : ?>
                    <label style="display:flex; align-items:center; gap:10px; font-size:13px; cursor:pointer; padding: 6px; border-radius: 8px; transition: background 0.2s;" class="hover-surface">
                        <input type="checkbox" name="tv_sbe_active_countries[]" value="<?php echo esc_attr( $country ); ?>" 
                               class="sbe-country-cb" <?php checked( in_array( $country, $active_list ) ); ?>
                               style="accent-color:var(--tv-primary); width: 18px; height: 18px;">
                        <span style="font-weight: 500;"><?php echo esc_html( $country ); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="tv-grid-2">

        <!-- SECTION 3: STANDARDIZATION RULES (THE RENAMER) -->
        <div class="tv-card">
            <div class="tv-card-header">
                <h3><span class="dashicons dashicons-randomize" style="margin-right:8px;"></span> Standardization Rules</h3>
            </div>
            <div class="tv-card-body">
                <p style="font-size:13px; color:var(--tv-text-muted); margin-bottom:20px;">
                    Rules are applied as a post-parse "Final Polish." This allows the engine to find channels using its internal dictionary first, then rename them to your preference.
                </p>
                
                <div id="sbe-rules-wrapper">
                    <?php if ( empty( $rules ) ) : ?>
                        <div class="tv-row sbe-rule-row" style="margin-bottom:12px; align-items:center; gap: 10px;">
                            <div class="tv-col"><input type="text" name="sbe_rule_wrong[]" class="tv-input" placeholder="Detected Name (e.g. beIN sport)"></div>
                            <span class="dashicons dashicons-arrow-right-alt" style="color: var(--tv-text-muted);"></span>
                            <div class="tv-col"><input type="text" name="sbe_rule_right[]" class="tv-input" placeholder="Display Name (e.g. beIN Sports)"></div>
                            <button type="button" class="tv-btn-icon delete" onclick="this.parentElement.remove()" style="color: var(--tv-danger); padding: 8px;"><span class="dashicons dashicons-trash"></span></button>
                        </div>
                    <?php else : ?>
                        <?php foreach ( $rules as $wrong => $right ) : ?>
                            <div class="tv-row sbe-rule-row" style="margin-bottom:12px; align-items:center; gap: 10px;">
                                <div class="tv-col">
                                    <input type="text" name="sbe_rule_wrong[]" class="tv-input" value="<?php echo esc_attr( $wrong ); ?>" placeholder="Wrong Name">
                                </div>
                                <span class="dashicons dashicons-arrow-right-alt" style="color:var(--tv-text-muted);"></span>
                                <div class="tv-col">
                                    <input type="text" name="sbe_rule_right[]" class="tv-input" value="<?php echo esc_attr( $right ); ?>" placeholder="Correct Name">
                                </div>
                                <button type="button" class="tv-btn-icon delete" style="color:var(--tv-danger); padding: 8px;" onclick="this.parentElement.remove()">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <button type="button" class="tv-btn tv-btn-secondary w-full" style="height: 40px; margin-top: 10px;" onclick="addSbeRule()">
                    <span class="dashicons dashicons-plus" style="margin-right: 6px;"></span> Add New Rule
                </button>
            </div>
        </div>

        <!-- RIGHT COLUMN: WORD STRIPPING & PRIORITY -->
        <div style="display:flex; flex-direction:column; gap:32px;">
            
            <!-- SECTION 4: SUBTRACTIVE WORD STRIPPING (THE CLEANER) -->
            <div class="tv-card" style="border-left: 4px solid var(--tv-danger);">
                <div class="tv-card-header">
                    <h3><span class="dashicons dashicons-no" style="margin-right:8px; color:var(--tv-danger);"></span> Word Exclusion & Stripping</h3>
                </div>
                <div class="tv-card-body">
                    <p style="font-size:13px; color:var(--tv-text-muted); margin-bottom:15px;">
                        <strong>Subtractive Mode:</strong> Any words or phrases entered here will be <u>erased</u> from the channel name during output. The channel itself is <strong>not</strong> removed.
                    </p>
                    <div style="background: #fff5f5; border: 1px solid #fed7d7; padding: 12px; border-radius: 10px; margin-bottom: 15px; font-size: 12px; color: #c53030;">
                        <span class="dashicons dashicons-info" style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px;"></span>
                        <em>Example: entering <strong>sky</strong> turns <strong>"Sky Sports Premier League"</strong> into <strong>"Sports Premier League"</strong>.</em>
                    </div>
                    <textarea name="tv_sbe_exclusions" class="tv-textarea" rows="7" style="font-family: ui-monospace, SFMono-Regular, monospace; font-size: 13px; line-height: 1.6;" placeholder="sky&#10;hd only&#10;live stream&#10;4k ultra"><?php echo esc_textarea( $exclusions ); ?></textarea>
                    <p style="font-size: 11px; color: var(--tv-text-muted); margin-top: 8px;">Place one word or phrase per line. Case-insensitive.</p>
                </div>
            </div>

            <!-- SECTION 5: REGIONAL PRIORITY (THE DIRECTOR) -->
            <div class="tv-card">
                <div class="tv-card-header">
                    <h3><span class="dashicons dashicons-sort" style="margin-right:8px;"></span> Regional Output Priority</h3>
                </div>
                <div class="tv-card-body">
                    <p style="font-size:13px; color:var(--tv-text-muted); margin-bottom:15px;">
                        Drag and drop countries to set their display priority. Channels will be grouped and ordered in the Sports Guide based on this list.
                    </p>
                    
                    <input type="hidden" name="tv_sbe_priority_list" id="tv_sbe_priority_input" value="<?php echo esc_attr( implode( ',', $display_countries ) ); ?>">
                    
                    <div style="max-height: 400px; overflow-y: auto; border:1px solid var(--tv-border); border-radius:16px; background: #fff;" class="custom-scrollbar">
                        <ul id="sbe-sortable-list" style="margin:0; padding:0; list-style:none;">
                            <?php if ( ! empty( $display_countries ) ) : ?>
                                <?php foreach ( $display_countries as $c ) : ?>
                                    <li class="sbe-sort-item" data-country="<?php echo esc_attr( $c ); ?>" style="padding:14px 18px; background:white; border-bottom:1px solid #f1f5f9; cursor:move; display:flex; justify-content:space-between; align-items:center; transition: background 0.2s;">
                                        <div style="display:flex; align-items:center; gap:12px;">
                                            <span class="dashicons dashicons-menu" style="color:var(--tv-text-muted); font-size: 16px;"></span>
                                            <span style="font-weight:600; font-size:14px; color: var(--tv-text);"><?php echo esc_html( $c ); ?></span>
                                        </div>
                                        <span style="font-size: 10px; font-weight: 800; color: var(--tv-text-muted); text-transform: uppercase; background: var(--tv-slate-100); padding: 2px 8px; border-radius: 4px;">Active</span>
                                    </li>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <li style="padding:40px; text-align:center; color:var(--tv-text-muted);">
                                    <span class="dashicons dashicons-warning" style="font-size: 32px; width: 32px; height: 32px; margin-bottom: 10px;"></span>
                                    <p style="margin:0;">No active countries selected.</p>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- STICKY GLOBAL FOOTER -->
    <div class="tv-card" style="position:sticky; bottom:20px; z-index:100; border-top: 4px solid var(--tv-primary); margin-top: 40px; box-shadow: 0 -10px 30px rgba(0,0,0,0.1);">
        <div class="tv-card-body" style="display:flex; justify-content:flex-end; align-items: center; gap: 20px; padding:20px 32px;">
            <span style="font-size: 12px; color: var(--tv-text-muted); font-weight: 500;">
                <span class="dashicons dashicons-saved" style="font-size: 16px; line-height: 1.4; color: var(--tv-success);"></span>
                Settings auto-apply to the next extraction run.
            </span>
            <button type="submit" name="save_settings" class="tv-btn tv-btn-primary" style="height:48px; padding:0 50px; font-weight:700; font-size: 15px; border-radius: 14px;">
                Save Engine Configuration
            </button>
        </div>
    </div>

</form>

<style>
    .hover-surface:hover { background: var(--tv-surface-active); }
    
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    .sbe-sort-item:hover { background: #fcfcfd !important; }
    .sbe-sort-item.ui-sortable-helper {
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        border: 1px solid var(--tv-primary);
        background: white !important;
        opacity: 0.9;
    }
</style>

<script>
    /**
     * Toggles all country checkboxes
     */
    function toggleCountries(check) {
        document.querySelectorAll('.sbe-country-cb').forEach(cb => cb.checked = check);
    }

    /**
     * Injects a new rule row into the repeater
     */
    function addSbeRule() {
        const wrapper = document.getElementById('sbe-rules-wrapper');
        const div = document.createElement('div');
        div.className = 'tv-row sbe-rule-row';
        div.style.marginBottom = '12px';
        div.style.alignItems = 'center';
        div.style.gap = '10px';
        div.innerHTML = `
            <div class="tv-col"><input type="text" name="sbe_rule_wrong[]" class="tv-input" placeholder="Wrong Name"></div>
            <span class="dashicons dashicons-arrow-right-alt" style="color:var(--tv-text-muted);"></span>
            <div class="tv-col"><input type="text" name="sbe_rule_right[]" class="tv-input" placeholder="Correct Name"></div>
            <button type="button" class="tv-btn-icon delete" style="color:var(--tv-danger); padding: 8px;" onclick="this.parentElement.remove()">
                <span class="dashicons dashicons-trash"></span>
            </button>
        `;
        wrapper.appendChild(div);
        
        // Auto-focus the first input of the new row
        div.querySelector('input').focus();
    }

    /**
     * Native Drag-and-Drop Implementation for Priority Sorting
     */
    document.addEventListener('DOMContentLoaded', () => {
        const list = document.getElementById('sbe-sortable-list');
        if ( ! list ) return;
        
        let draggedItem = null;

        list.querySelectorAll('li.sbe-sort-item').forEach(item => {
            item.draggable = true;
            
            item.addEventListener('dragstart', function(e) { 
                draggedItem = this; 
                this.style.opacity = '0.4';
                this.style.background = '#f8fafc';
                e.dataTransfer.effectAllowed = 'move';
            });
            
            item.addEventListener('dragend', function() { 
                this.style.opacity = '1';
                this.style.background = 'white';
                draggedItem = null; 
                updatePriorityInput(); 
            });
            
            item.addEventListener('dragover', function(e) { 
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                return false;
            });
            
            item.addEventListener('dragenter', function(e) {
                if (this !== draggedItem) {
                    this.style.background = '#f1f5f9';
                    this.style.borderLeft = '4px solid var(--tv-primary)';
                }
            });

            item.addEventListener('dragleave', function() {
                this.style.background = 'white';
                this.style.borderLeft = 'none';
            });

            item.addEventListener('drop', function(e) {
                e.stopPropagation();
                this.style.background = 'white';
                this.style.borderLeft = 'none';
                
                if (this !== draggedItem) {
                    let allNodes = Array.from(list.children);
                    let indexA = allNodes.indexOf(draggedItem);
                    let indexB = allNodes.indexOf(this);
                    
                    if (indexA < indexB) {
                        list.insertBefore(draggedItem, this.nextSibling);
                    } else {
                        list.insertBefore(draggedItem, this);
                    }
                    updatePriorityInput();
                }
                return false;
            });
        });

        /**
         * Serializes the DOM order into the hidden CSV input
         */
        function updatePriorityInput() {
            const items = Array.from(list.querySelectorAll('li.sbe-sort-item'))
                               .map(li => li.getAttribute('data-country'));
            const input = document.getElementById('tv_sbe_priority_input');
            if (input) {
                input.value = items.join(',');
            }
        }
    });
</script>