<?php
/**
 * FILE PATH: tv-subscription-manager/admin/views/view-sports.php
 *
 * Fixes applied:
 *  - Broken SVGs/emojis replaced with proper dashicons + inline SVGs.
 *  - Added 'multi' extractor: Multiple Channel Input with popup modal (Smart Extract Auto Run).
 *  - 'multi' can be toggled on/off via the Extractors settings modal just like smart/bulk.
 *  - Smart Extract popup now calls tv_sbe_extract (TV_Channel_Engine) which abbreviates
 *    country names correctly and applies all Channel Engine settings.
 *  - All original logic preserved verbatim.
 */
if (!defined('ABSPATH')) { exit; }

// Safety defaults if not passed from render()
if (!isset($enabled_extractors)) $enabled_extractors = ['manual', 'smart'];
if (!isset($edit_event))         $edit_event = null;
if (!isset($events))             $events     = [];

$is_edit    = !empty($edit_event);
$form_title = $is_edit ? 'Edit Event' : 'Add New Event';
$btn_label  = $is_edit ? 'Update Event' : 'Add to Schedule';

$val_date = date('Y-m-d');
$val_time = '';
if ($is_edit && !empty($edit_event->start_time)) {
    $ts       = strtotime($edit_event->start_time);
    $val_date = date('Y-m-d', $ts);
    $val_time = date('H:i', $ts);
}

$existing_channels = [];
if ($is_edit && !empty($edit_event->channels_json)) {
    $decoded = json_decode($edit_event->channels_json, true);
    if (is_array($decoded)) $existing_channels = $decoded;
}

$smart_on = in_array('smart', $enabled_extractors);
$bulk_on  = in_array('bulk',  $enabled_extractors);
$multi_on = in_array('multi', $enabled_extractors);

// Which tabs are enabled (determines tab bar entries)
$active_tabs = ['manual'];
if ($smart_on)  $active_tabs[] = 'smart';
if ($bulk_on)   $active_tabs[] = 'bulk';
// 'multi' is a standalone button in the staging area, not a tab

$sbe_nonce = wp_create_nonce('wp_rest');
?>

<div class="tv-page-header">
    <div>
        <h1>Sports Guide Manager</h1>
        <p>Manage the live sports schedule. Add channels via manual entry or smart extractors.</p>
    </div>
</div>

<div class="tv-grid-2" style="align-items:start;">

    <!-- ---------------------------------------------------------- -->
    <!-- LEFT: ADD / EDIT FORM                                      -->
    <!-- ---------------------------------------------------------- -->
    <div class="tv-card" style="position:sticky; top:80px;">
        <div class="tv-card-header">
            <h3><?php echo esc_html($form_title); ?></h3>
            <button type="button"
                    onclick="document.getElementById('tv-extractor-modal').style.display='flex'"
                    class="tv-btn tv-btn-sm tv-btn-secondary"
                    title="Configure which channel extractors are available">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
                Extractors
            </button>
        </div>
        <div class="tv-card-body">
            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=tv-sports')); ?>" id="tv-sports-form">
                <?php wp_nonce_field('save_event_verify'); ?>
                <?php if ($is_edit): ?>
                    <input type="hidden" name="event_id" value="<?php echo (int)$edit_event->id; ?>">
                <?php endif; ?>

                <!-- Event Title -->
                <div class="tv-form-group">
                    <label class="tv-label">Event Title *</label>
                    <input type="text" name="event_title" required class="tv-input"
                           value="<?php echo esc_attr($is_edit ? $edit_event->title : ''); ?>"
                           placeholder="e.g. Manchester United vs Chelsea">
                </div>

                <!-- --- CHANNEL STAGING --- -->
                <div style="background:var(--tv-surface-active); border:2px solid var(--tv-border); border-radius:12px; padding:14px; margin-bottom:18px;">

                    <!-- Tab Switcher -->
                    <?php if (count($active_tabs) > 1): ?>
                    <div style="display:flex; gap:3px; margin-bottom:14px; background:var(--tv-card); border:1px solid var(--tv-border); border-radius:8px; padding:3px;">
                        <?php foreach ($active_tabs as $_tab_key):
                            $tab_labels = [
                                'manual' => 'Manual',
                                'smart'  => 'Smart',
                                'bulk'   => 'Bulk',
                            ];
                            $tab_icons = [
                                'manual' => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>',
                                'smart'  => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
                                'bulk'   => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
                            ];
                            $tab_label = $tab_labels[$_tab_key] ?? ucfirst($_tab_key);
                            $tab_icon  = $tab_icons[$_tab_key] ?? '';
                        ?>
                        <button type="button"
                                id="tv-tab-btn-<?php echo esc_attr($_tab_key); ?>"
                                onclick="tvChannelTab('<?php echo esc_attr($_tab_key); ?>')"
                                style="flex:1; padding:7px 10px; border-radius:6px; border:none; font-size:12px; font-weight:700; cursor:pointer; transition:.15s; display:flex; align-items:center; justify-content:center; gap:5px;
                                       background:<?php echo ($_tab_key === 'manual') ? 'var(--tv-primary)' : 'transparent'; ?>;
                                       color:<?php echo ($_tab_key === 'manual') ? '#fff' : 'var(--tv-text-muted)'; ?>;">
                            <?php echo $tab_icon; ?>
                            <?php echo esc_html($tab_label); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- -- TAB: Manual Entry (always visible, default) -- -->
                    <div id="tv-channel-tab-manual">
                        <label class="tv-label" style="font-size:10px; margin-bottom:6px;">One channel per line</label>
                        <textarea id="tv-manual-entry"
                                  class="tv-textarea"
                                  rows="3"
                                  placeholder="Sky Sports Main Event&#10;TNT Sports 1&#10;beIN Sports HD 1"
                                  style="font-size:12px; background:var(--tv-card);"></textarea>
                        <button type="button" onclick="tvAddManual()"
                                class="tv-btn tv-btn-primary w-full"
                                style="height:36px; margin-top:8px; font-size:12px;">
                            + Add Channels
                        </button>
                    </div>

                    <!-- -- TAB: Smart Extractor (inline) -- -->
                    <?php if ($smart_on): ?>
                    <div id="tv-channel-tab-smart" style="display:none;">
                        <label class="tv-label" style="font-size:10px; margin-bottom:6px; color:var(--tv-primary); display:flex; align-items:center; gap:4px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                            Paste raw broadcaster text (e.g. "UK: Sky Sports | BT Sport | ...")
                        </label>
                        <textarea id="tv-smart-entry"
                                  class="tv-textarea"
                                  rows="3"
                                  placeholder="Country: Channel1 | Channel2 | Channel3&#10;UK: Sky Sports | TNT Sports&#10;US: ESPN | Fox Sports"
                                  style="font-size:12px; border-style:dashed; background:var(--tv-card);"></textarea>
                        <button type="button" id="tv-smart-extract-btn" onclick="tvRunSmartExtract()"
                                class="tv-btn tv-btn-primary w-full"
                                style="height:36px; margin-top:8px; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; gap:6px;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                            Auto-Extract Channels
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- -- TAB: Bulk Paste -- -->
                    <?php if ($bulk_on): ?>
                    <div id="tv-channel-tab-bulk" style="display:none;">
                        <label class="tv-label" style="font-size:10px; margin-bottom:6px;">Comma or newline separated</label>
                        <textarea id="tv-bulk-entry"
                                  class="tv-textarea"
                                  rows="3"
                                  placeholder="Sky Sports, BT Sport, TNT Sports, ESPN, beIN Sports..."
                                  style="font-size:12px; background:var(--tv-card);"></textarea>
                        <button type="button" onclick="tvAddBulk()"
                                class="tv-btn tv-btn-secondary w-full"
                                style="height:36px; margin-top:8px; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; gap:6px;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                            Parse &amp; Add
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- -- Multiple Channel Input: popup trigger (admin-toggleable) -- -->
                    <?php if ($multi_on): ?>
                    <div id="tv-channel-tab-multi-trigger" style="margin-top:<?php echo (count($active_tabs) > 1 || $smart_on || $bulk_on) ? '10px' : '0'; ?>;">
                        <div style="border:1px dashed var(--tv-primary); border-radius:10px; padding:10px 12px; background:var(--tv-primary-light, rgba(79,70,229,.05));">
                            <div style="font-size:10px; font-weight:800; color:var(--tv-primary); text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px; display:flex; align-items:center; gap:5px;">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                Multiple Channel Input
                            </div>
                            <button type="button"
                                    onclick="document.getElementById('tv-multi-extract-modal').style.display='flex'"
                                    class="tv-btn tv-btn-primary w-full"
                                    style="height:36px; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; gap:7px;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                                Smart Extract (Auto Run)
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- -- Staged Channel Pills -- -->
                    <div id="tv-staged-channels" style="margin-top:14px; display:<?php echo !empty($existing_channels) ? 'block' : 'none'; ?>;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <div style="font-size:10px; font-weight:800; color:var(--tv-text-muted); text-transform:uppercase; letter-spacing:.04em;">
                                Attached Channels (<span id="tv-channel-count">0</span>)
                            </div>
                            <button type="button" onclick="tvClearAllChannels()"
                                    style="font-size:10px; color:var(--tv-danger); background:var(--tv-danger-bg); border:1px solid rgba(239,68,68,.2); padding:3px 10px; border-radius:6px; cursor:pointer; font-weight:700;">
                                Clear All
                            </button>
                        </div>
                        <div id="tv-channel-pills" style="display:flex; flex-wrap:wrap; gap:5px;"></div>
                    </div>
                </div>
                <!-- --- END CHANNEL STAGING --- -->

                <!-- League & Sport Type -->
                <div class="tv-row">
                    <div class="tv-col">
                        <label class="tv-label">League *</label>
                        <input type="text" name="event_league" required class="tv-input"
                               value="<?php echo esc_attr($is_edit ? $edit_event->league : ''); ?>"
                               placeholder="e.g. Premier League">
                    </div>
                    <div class="tv-col">
                        <label class="tv-label">Sport Type</label>
                        <select name="event_type" class="tv-input">
                            <option value="soccer"     <?php selected($is_edit ? $edit_event->sport_type : 'soccer', 'soccer'); ?>>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                                Soccer
                            </option>
                            <option value="f1"         <?php selected($is_edit ? $edit_event->sport_type : '', 'f1'); ?>>Formula 1</option>
                            <option value="basketball" <?php selected($is_edit ? $edit_event->sport_type : '', 'basketball'); ?>>Basketball</option>
                            <option value="tennis"     <?php selected($is_edit ? $edit_event->sport_type : '', 'tennis'); ?>>Tennis</option>
                            <option value="rugby"      <?php selected($is_edit ? $edit_event->sport_type : '', 'rugby'); ?>>Rugby</option>
                        </select>
                    </div>
                </div>

                <!-- Date & Time -->
                <div class="tv-row">
                    <div class="tv-col">
                        <label class="tv-label">Date *</label>
                        <input type="date" name="event_start_date" required class="tv-input" value="<?php echo esc_attr($val_date); ?>">
                    </div>
                    <div class="tv-col">
                        <label class="tv-label">Kick-off (UTC) *</label>
                        <input type="time" name="event_start_time" required class="tv-input" value="<?php echo esc_attr($val_time); ?>">
                    </div>
                </div>

                <!-- Hidden fields synced by JS -->
                <input type="hidden" name="event_channel"       id="tv-hidden-channel-legacy" value="<?php echo esc_attr($is_edit ? $edit_event->channel : ''); ?>">
                <input type="hidden" name="event_channels_json" id="tv-hidden-channels-json"  value='<?php echo esc_attr($is_edit ? (string)$edit_event->channels_json : '[]'); ?>'>

                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button type="submit" name="save_event"
                            class="tv-btn tv-btn-primary w-full"
                            style="height:44px; font-weight:800;">
                        <?php echo esc_html($btn_label); ?>
                    </button>
                    <?php if ($is_edit): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=tv-sports')); ?>"
                       class="tv-btn tv-btn-secondary" style="height:44px;">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- ---------------------------------------------------------- -->
    <!-- RIGHT: EVENT SCHEDULE                                      -->
    <!-- ---------------------------------------------------------- -->
    <div class="tv-card">
        <div class="tv-card-header"><h3>Active Schedule (UTC)</h3></div>
        <div class="tv-table-container">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th width="80">Time</th>
                        <th>Event</th>
                        <th>Channels</th>
                        <th align="right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($events)): foreach ($events as $e):
                        $del_url = wp_nonce_url(
                            admin_url('admin.php?page=tv-sports&action=delete_event&id=' . (int)$e->id),
                            'delete_event_' . (int)$e->id
                        );
                        $edit_url = admin_url('admin.php?page=tv-sports&action=edit_event&id=' . (int)$e->id);
                        $ch_count = 0;
                        if (!empty($e->channels_json)) {
                            $ch_arr   = json_decode($e->channels_json, true);
                            $ch_count = is_array($ch_arr) ? count($ch_arr) : 0;
                        }
                    ?>
                    <tr>
                        <td>
                            <strong style="font-family:monospace; font-size:14px;">
                                <?php echo date('H:i', strtotime($e->start_time)); ?>
                            </strong>
                            <div style="font-size:10px; color:var(--tv-text-muted);">
                                <?php echo date('M j', strtotime($e->start_time)); ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:700; color:var(--tv-text);"><?php echo esc_html($e->title); ?></div>
                            <div style="font-size:11px; color:var(--tv-text-muted);"><?php echo esc_html($e->league); ?></div>
                        </td>
                        <td>
                            <span style="font-size:12px; color:var(--tv-text-muted);">
                                <?php echo $ch_count > 0 ? esc_html($ch_count . ' channel' . ($ch_count !== 1 ? 's' : '')) : '<em>None</em>'; ?>
                            </span>
                        </td>
                        <td align="right">
                            <div style="display:flex; gap:6px; justify-content:flex-end;">
                                <a href="<?php echo esc_url($edit_url); ?>" class="tv-btn tv-btn-sm tv-btn-secondary">Edit</a>
                                <a href="<?php echo esc_url($del_url); ?>" class="tv-btn tv-btn-sm tv-btn-danger" data-tv-delete="1">Del</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="4" style="text-align:center; padding:40px; color:var(--tv-text-muted);">
                            No events scheduled yet.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- -------------------------------------------------------------- -->
<!-- EXTRACTOR PICKER MODAL                                         -->
<!-- -------------------------------------------------------------- -->
<div id="tv-extractor-modal"
     style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.6); backdrop-filter:blur(5px);
            z-index:99999; align-items:center; justify-content:center; padding:20px;">
    <div class="tv-card" style="max-width:480px; width:100%; margin:0; border-radius:20px; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,.4);">
        <div class="tv-card-header">
            <h3>Channel Extractor Settings</h3>
            <button type="button"
                    onclick="document.getElementById('tv-extractor-modal').style.display='none'"
                    class="tv-btn tv-btn-sm tv-btn-secondary"
                    style="display:flex; align-items:center; gap:5px;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Close
            </button>
        </div>
        <div class="tv-card-body">
            <p style="font-size:13px; color:var(--tv-text-muted); margin:0 0 16px;">
                Choose which channel-input methods appear on the event form.
                <strong>Manual Entry is always enabled</strong> and cannot be disabled.
            </p>

            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=tv-sports')); ?>">
                <?php wp_nonce_field('tv_save_extractor_settings'); ?>
                <input type="hidden" name="tv_save_extractor_settings" value="1">
                <!-- manual is always submitted -->
                <input type="hidden" name="tv_extractors[]" value="manual">

                <?php
                $extractor_defs = [
                    'manual' => [
                        'label' => 'Manual Entry',
                        'desc'  => 'Type channel names one per line. Always enabled, cannot be disabled.',
                        'icon'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>',
                    ],
                    'smart'  => [
                        'label' => 'Smart Extractor',
                        'desc'  => 'Inline tab: paste "Country: Channel | Channel" format directly in the form.',
                        'icon'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
                    ],
                    'bulk'   => [
                        'label' => 'Bulk Paste',
                        'desc'  => 'Paste comma or newline-separated channel names for fast mass-entry.',
                        'icon'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
                    ],
                    'multi'  => [
                        'label' => 'Multiple Channel Input',
                        'desc'  => 'Popup modal with large paste area. Uses Smart Extract (Auto Run) powered by the Channel Engine — applies all active countries, rules, and priority settings.',
                        'icon'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
                    ],
                ];
                foreach ($extractor_defs as $ext_key => $ext_info):
                    $is_manual  = ($ext_key === 'manual');
                    $is_checked = in_array($ext_key, $enabled_extractors, true);
                ?>
                <label style="display:flex; align-items:flex-start; gap:12px; padding:12px 14px;
                              border:1px solid var(--tv-border); border-radius:10px; margin-bottom:8px;
                              cursor:<?php echo $is_manual ? 'default' : 'pointer'; ?>;
                              background:var(--tv-surface-active);
                              opacity:<?php echo $is_manual ? '0.75' : '1'; ?>;">
                    <input type="checkbox"
                           name="tv_extractors[]"
                           value="<?php echo esc_attr($ext_key); ?>"
                           <?php checked($is_checked); ?>
                           <?php echo $is_manual ? 'disabled checked' : ''; ?>
                           style="margin-top:3px; width:16px; height:16px; accent-color:var(--tv-primary);">
                    <div style="display:flex; align-items:flex-start; gap:10px;">
                        <div style="margin-top:1px; color:var(--tv-primary); flex-shrink:0;"><?php echo $ext_info['icon']; ?></div>
                        <div>
                            <div style="font-weight:700; font-size:13px; color:var(--tv-text);">
                                <?php echo esc_html($ext_info['label']); ?>
                            </div>
                            <div style="font-size:12px; color:var(--tv-text-muted); margin-top:2px;">
                                <?php echo esc_html($ext_info['desc']); ?>
                            </div>
                        </div>
                    </div>
                </label>
                <?php endforeach; ?>

                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:18px;">
                    <button type="button"
                            onclick="document.getElementById('tv-extractor-modal').style.display='none'"
                            class="tv-btn tv-btn-secondary">Cancel</button>
                    <button type="submit" class="tv-btn tv-btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- -------------------------------------------------------------- -->
<!-- MULTIPLE CHANNEL INPUT MODAL (Smart Extract Auto Run)          -->
<!-- -------------------------------------------------------------- -->
<?php if ($multi_on): ?>
<div id="tv-multi-extract-modal"
     style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.7); backdrop-filter:blur(6px);
            z-index:99998; align-items:center; justify-content:center; padding:20px;">
    <div class="tv-card" style="max-width:680px; width:100%; margin:0; border-radius:20px; overflow:hidden; box-shadow:0 30px 60px -12px rgba(0,0,0,.5);">

        <div class="tv-card-header" style="border-bottom:2px solid var(--tv-primary);">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:32px; height:32px; border-radius:8px; background:var(--tv-primary); display:flex; align-items:center; justify-content:center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                </div>
                <div>
                    <div style="font-size:15px; font-weight:800; color:var(--tv-text);">Multiple Channel Input</div>
                    <div style="font-size:11px; color:var(--tv-text-muted);">Powered by Channel Engine &mdash; applies your active countries, rules &amp; priority</div>
                </div>
            </div>
            <button type="button"
                    onclick="tvCloseMultiModal()"
                    class="tv-btn tv-btn-sm tv-btn-secondary"
                    style="display:flex; align-items:center; gap:5px;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Close
            </button>
        </div>

        <div class="tv-card-body" style="padding:24px;">

            <div style="background:var(--tv-surface-active); border:1px solid var(--tv-border); border-radius:10px; padding:12px 14px; margin-bottom:16px; font-size:12px; color:var(--tv-text-muted); display:flex; align-items:flex-start; gap:8px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--tv-primary)" stroke-width="2" style="flex-shrink:0; margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>Paste the full broadcaster listing below (e.g. from a fixture page). The engine will detect countries, strip duplicates, apply your standardization rules, and add only the channels matching your active country list.</span>
            </div>

            <textarea id="tv-multi-raw-text"
                      rows="12"
                      class="tv-textarea"
                      placeholder="United Kingdom: Sky Sports Main Event | Sky Sports Premier League | BT Sport 1 | BT Sport 2&#10;United States: ESPN | Fox Sports 1 | NBC Sports&#10;Nigeria: SuperSport Premier League | SuperSport Football Plus&#10;&#10;Paste any amount of broadcaster text here..."
                      style="font-size:12px; font-family:ui-monospace, monospace; line-height:1.6; resize:vertical; background:var(--tv-card);"></textarea>

            <div id="tv-multi-status" style="display:none; margin-top:10px; padding:10px 14px; border-radius:8px; font-size:12px; font-weight:600;"></div>
        </div>

        <div style="padding:16px 24px; border-top:1px solid var(--tv-border); display:flex; align-items:center; justify-content:space-between; gap:12px; background:var(--tv-surface-active);">
            <div style="font-size:11px; color:var(--tv-text-muted);">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:3px;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                Channel Engine settings apply automatically
            </div>
            <div style="display:flex; gap:10px;">
                <button type="button" onclick="tvCloseMultiModal()" class="tv-btn tv-btn-secondary">
                    Cancel
                </button>
                <button type="button" id="tv-multi-run-btn" onclick="tvRunMultiExtract()"
                        class="tv-btn tv-btn-primary"
                        style="display:flex; align-items:center; gap:8px; padding:0 24px; height:42px; font-weight:800; font-size:13px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    Smart Extract (Auto Run)
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<!-- -------------------------------------------------------------- -->
<!-- JAVASCRIPT                                                      -->
<!-- -------------------------------------------------------------- -->
<script>
(function(){
    /* -- Staged channel array -- */
    var _staged = JSON.parse(document.getElementById('tv-hidden-channels-json').value || '[]');
    if (!Array.isArray(_staged)) _staged = [];

    /* -- Tab switching -- */
    window.tvChannelTab = function(name) {
        ['manual','smart','bulk'].forEach(function(t) {
            var pane = document.getElementById('tv-channel-tab-' + t);
            var btn  = document.getElementById('tv-tab-btn-' + t);
            if (!pane) return;
            var on = (t === name);
            pane.style.display = on ? 'block' : 'none';
            if (btn) {
                btn.style.background = on ? 'var(--tv-primary)' : 'transparent';
                btn.style.color      = on ? '#fff' : 'var(--tv-text-muted)';
            }
        });
    };

    /* -- Manual: add each line -- */
    window.tvAddManual = function() {
        var box = document.getElementById('tv-manual-entry');
        if (!box) return;
        var added = 0;
        box.value.trim().split('\n').forEach(function(line) {
            var name = line.trim();
            if (!name) return;
            if (_staged.some(function(c){ return c.name.toLowerCase() === name.toLowerCase(); })) return;
            _staged.push({ name: name, region: 'INT' });
            added++;
        });
        if (added) { tvRenderPills(); box.value = ''; }
    };

    /* -- Bulk: split by commas or newlines -- */
    window.tvAddBulk = function() {
        var box = document.getElementById('tv-bulk-entry');
        if (!box) return;
        var added = 0;
        box.value.split(/[,\n]+/).forEach(function(part) {
            var name = part.trim();
            if (!name) return;
            if (_staged.some(function(c){ return c.name.toLowerCase() === name.toLowerCase(); })) return;
            _staged.push({ name: name, region: 'INT' });
            added++;
        });
        if (added) { tvRenderPills(); box.value = ''; }
    };

    /* -- Inline Smart AJAX extractor -- */
    window.tvRunSmartExtract = async function() {
        var text = (document.getElementById('tv-smart-entry') || {}).value || '';
        if (!text.trim()) { alert('Please paste some broadcaster text first.'); return; }
        var btn  = document.getElementById('tv-smart-extract-btn');
        var orig = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Scanning&hellip;'; }
        try {
            var result = await tvCallExtractorAjax(text);
            if (result.success && result.data && Array.isArray(result.data.channels) && result.data.channels.length) {
                tvMergeChannels(result.data.channels);
                tvRenderPills();
                var smart = document.getElementById('tv-smart-entry');
                if (smart) smart.value = '';
            } else {
                alert('No channels found. Check the format: "Country: Channel1 | Channel2"');
            }
        } catch(err) {
            console.error('Smart extract failed:', err);
            alert('Extraction failed. Please try again.');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = orig; }
        }
    };

    /* -- Multiple Channel Input: close modal -- */
    window.tvCloseMultiModal = function() {
        var modal = document.getElementById('tv-multi-extract-modal');
        if (modal) modal.style.display = 'none';
        var status = document.getElementById('tv-multi-status');
        if (status) { status.style.display = 'none'; status.textContent = ''; }
    };

    /* -- Multiple Channel Input: run extraction from popup -- */
    window.tvRunMultiExtract = async function() {
        var textEl = document.getElementById('tv-multi-raw-text');
        var text = textEl ? textEl.value : '';
        if (!text.trim()) { alert('Please paste some broadcaster text first.'); return; }

        var btn    = document.getElementById('tv-multi-run-btn');
        var status = document.getElementById('tv-multi-status');
        var orig   = btn ? btn.innerHTML : '';

        if (btn) { btn.disabled = true; btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Extracting&hellip;'; }
        if (status) { status.style.display = 'none'; }

        try {
            var result = await tvCallExtractorAjax(text);
            if (result.success && result.data && Array.isArray(result.data.channels) && result.data.channels.length) {
                var fresh = result.data.channels;
                var beforeCount = _staged.length;
                tvMergeChannels(fresh);
                tvRenderPills();
                var added = _staged.length - beforeCount;
                var total = fresh.length;

                if (status) {
                    status.style.display = 'block';
                    status.style.background = 'var(--tv-success-bg, #ecfdf5)';
                    status.style.color = 'var(--tv-success-text, #065f46)';
                    status.style.border = '1px solid rgba(16,185,129,.25)';
                    status.innerHTML =
                        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:5px;"><polyline points="20 6 9 17 4 12"/></svg>' +
                        'Extracted <strong>' + total + '</strong> channels' +
                        (added < total ? ' (' + added + ' new, ' + (total - added) + ' already staged)' : '') +
                        '. Close this panel and review the channel list.';
                }
                if (textEl) textEl.value = '';
            } else {
                if (status) {
                    status.style.display = 'block';
                    status.style.background = 'var(--tv-warning-bg, #fffbeb)';
                    status.style.color = 'var(--tv-warning-text, #92400e)';
                    status.style.border = '1px solid rgba(245,158,11,.25)';
                    status.textContent = 'No channels found. Check that your text uses the format: "Country: Channel1 | Channel2 | Channel3"';
                }
            }
        } catch(err) {
            console.error('Multi extract failed:', err);
            if (status) {
                status.style.display = 'block';
                status.style.background = 'var(--tv-danger-bg, #fef2f2)';
                status.style.color = 'var(--tv-danger-text, #991b1b)';
                status.style.border = '1px solid rgba(239,68,68,.2)';
                status.textContent = 'Extraction failed. Please try again.';
            }
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = orig; }
        }
    };

    /* -- Shared AJAX call to TV_Channel_Engine::handle_ajax_extraction -- */
    async function tvCallExtractorAjax(text) {
        var fd = new FormData();
        fd.append('action', 'tv_sbe_extract');
        fd.append('raw_text', text);
        fd.append('_nonce', '<?php echo esc_js($sbe_nonce); ?>');
        var res = await fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', { method: 'POST', body: fd });
        return await res.json();
    }

    /* -- Merge extracted channels into staged list (skip dupes) -- */
    function tvMergeChannels(fresh) {
        fresh.forEach(function(nc) {
            var exists = _staged.some(function(e) {
                return e.name.toLowerCase() === nc.name.toLowerCase() && e.region === nc.region;
            });
            if (!exists) _staged.push(nc);
        });
    }

    /* -- Remove one channel -- */
    window.tvRemoveChannel = function(idx) {
        _staged.splice(idx, 1);
        tvRenderPills();
    };

    /* -- Clear all -- */
    window.tvClearAllChannels = function() {
        if (_staged.length === 0) return;
        if (confirm('Remove all ' + _staged.length + ' channels from this event?')) {
            _staged = [];
            tvRenderPills();
        }
    };

    /* -- Render pills and sync hidden inputs -- */
    window.tvRenderPills = function() {
        var pillsEl  = document.getElementById('tv-channel-pills');
        var jsonEl   = document.getElementById('tv-hidden-channels-json');
        var legacyEl = document.getElementById('tv-hidden-channel-legacy');
        var countEl  = document.getElementById('tv-channel-count');
        var wrapEl   = document.getElementById('tv-staged-channels');

        if (!pillsEl) return;

        if (_staged.length === 0) {
            if (wrapEl) wrapEl.style.display = 'none';
            if (jsonEl)   jsonEl.value   = '[]';
            if (legacyEl) legacyEl.value = '';
            return;
        }

        if (wrapEl) wrapEl.style.display = 'block';
        if (countEl) countEl.textContent = _staged.length;

        pillsEl.innerHTML = '';
        _staged.forEach(function(ch, i) {
            var d = document.createElement('div');
            d.style.cssText = [
                'display:inline-flex', 'align-items:center', 'gap:7px',
                'background:var(--tv-card)', 'border:1px solid var(--tv-border)',
                'border-radius:8px', 'padding:4px 10px', 'font-size:12px'
            ].join(';');
            d.innerHTML =
                '<span style="font-weight:700;color:var(--tv-text);">' + escHtml(ch.name) + '</span>' +
                (ch.region && ch.region !== 'INT' ?
                    '<span style="font-size:9px;color:var(--tv-text-muted);background:var(--tv-surface-active);padding:1px 5px;border-radius:4px;">' + escHtml(ch.region) + '</span>'
                    : '') +
                '<span onclick="tvRemoveChannel(' + i + ')" ' +
                      'style="cursor:pointer;color:var(--tv-danger);font-weight:900;font-size:14px;line-height:1;margin-left:2px;">&times;</span>';
            pillsEl.appendChild(d);
        });

        if (jsonEl)   jsonEl.value   = JSON.stringify(_staged);
        if (legacyEl) legacyEl.value = _staged.map(function(c){ return c.name; }).join(', ').substring(0, 250);
    };

    function escHtml(str) {
        return String(str).replace(/[&<>"']/g, function(c){
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }

    /* -- Close modals on backdrop click -- */
    ['tv-extractor-modal', 'tv-multi-extract-modal'].forEach(function(id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    if (id === 'tv-multi-extract-modal') { tvCloseMultiModal(); }
                    else { modal.style.display = 'none'; }
                }
            });
        }
    });

    /* -- Initial render (edit mode: pre-populate pills) -- */
    if (_staged.length) tvRenderPills();

    /* -- FIX: Ensure hidden inputs are synced before form submission -- */
    var sportsForm = document.getElementById('tv-sports-form');
    if (sportsForm) {
        sportsForm.addEventListener('submit', function() {
            tvRenderPills(); // Guarantee hidden inputs have latest _staged state
        });
    }
})();
</script>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>