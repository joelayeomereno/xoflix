<?php
if (!defined('ABSPATH')) { exit; }
?>
<div class="tv-page-header">
    <div>
        <h1>Transactions &amp; Fulfillment</h1>
        <p>Review payments, check proofs, and provision subscription credentials.</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
        <!-- Manual Add Subscription Button -->
        <button type="button" class="tv-btn tv-btn-accent" onclick="tvOpenManualAddModal()">
            <span class="dashicons dashicons-plus-alt2" style="font-size:16px;width:16px;height:16px;margin-right:4px;"></span>
            Manual Add
        </button>
    </div>
</div>

<div class="tv-card">

    <!-- Toolbar: Status filters + Currency + Search -->
    <div class="tv-toolbar" style="justify-content:space-between;flex-wrap:wrap;gap:12px;background:var(--tv-card);padding:16px 22px;">
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <a href="?page=tv-subs-manager&tab=payments" class="tv-btn tv-btn-sm <?php echo empty($filter_status) && empty($_GET['date_range']) ? 'tv-btn-primary' : 'tv-btn-secondary'; ?>">All</a>
            <a href="?page=tv-subs-manager&tab=payments&status=needs_action" class="tv-btn tv-btn-sm <?php echo $filter_status === 'needs_action' ? 'tv-btn-primary' : 'tv-btn-secondary'; ?>">Needs Action</a>
        </div>

        <!-- Currency Toggle -->
        <div style="display:flex;gap:0;background:var(--tv-surface-active);border-radius:8px;border:1px solid var(--tv-border);overflow:hidden;">
            <button type="button" onclick="tvToggleCurrency('USD')" id="tv-curr-usd" class="tv-btn-text" style="padding:6px 14px;font-weight:700;font-size:12px;color:var(--tv-text);background:var(--tv-card);border:none;cursor:pointer;">USD</button>
            <button type="button" onclick="tvToggleCurrency('NGN')" id="tv-curr-ngn" class="tv-btn-text" style="padding:6px 14px;font-weight:700;font-size:12px;color:var(--tv-text-muted);background:transparent;border:none;cursor:pointer;">NGN</button>
        </div>

        <form method="get" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="page" value="tv-subs-manager">
            <input type="hidden" name="tab" value="payments">
            <?php if(!empty($filter_status)): ?><input type="hidden" name="status" value="<?php echo esc_attr($filter_status); ?>"><?php endif; ?>
            <input type="text" name="s" class="tv-input" style="min-width:200px;height:36px;font-size:13px;" placeholder="Search Ref or User..." value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
            <button type="submit" class="tv-btn tv-btn-secondary tv-btn-sm">
                <span class="dashicons dashicons-search" style="font-size:14px;width:14px;height:14px;"></span>
            </button>
        </form>
    </div>

    <!-- ============================================================
         DATE FILTER BAR — Quick ranges + custom calendar picker
         ============================================================ -->
    <style>
    .tv-date-filter-bar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        padding: 14px 22px;
        background: var(--tv-surface);
        border-bottom: 1px solid var(--tv-border);
    }
    .tv-date-filter-label {
        font-size: 11px;
        font-weight: 700;
        color: var(--tv-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-right: 2px;
        white-space: nowrap;
    }
    .tv-date-filter-group {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-wrap: wrap;
    }
    .tv-date-filter-btn {
        padding: 6px 15px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid var(--tv-border);
        background: var(--tv-surface);
        color: var(--tv-text-muted);
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
        white-space: nowrap;
        line-height: 1.4;
    }
    .tv-date-filter-btn:hover {
        background: rgba(var(--tv-primary-rgb), 0.07);
        border-color: var(--tv-primary);
        color: var(--tv-primary);
    }
    .tv-date-filter-btn.tv-active {
        background: var(--tv-primary);
        color: #fff;
        border-color: var(--tv-primary);
        box-shadow: 0 2px 8px rgba(var(--tv-primary-rgb), 0.35);
    }
    .tv-date-divider {
        width: 1px;
        height: 24px;
        background: var(--tv-border);
        margin: 0 4px;
    }
    /* Custom calendar picker panel */
    .tv-custom-date-wrap {
        position: relative;
        display: inline-flex;
        align-items: center;
    }
    .tv-custom-date-trigger {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid var(--tv-border);
        background: var(--tv-surface);
        color: var(--tv-text-muted);
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .tv-custom-date-trigger:hover,
    .tv-custom-date-trigger.is-active {
        background: rgba(var(--tv-primary-rgb), 0.07);
        border-color: var(--tv-primary);
        color: var(--tv-primary);
    }
    .tv-custom-date-trigger.has-value {
        background: var(--tv-primary);
        color: #fff;
        border-color: var(--tv-primary);
        box-shadow: 0 2px 8px rgba(var(--tv-primary-rgb), 0.35);
    }
    .tv-custom-date-panel {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        z-index: 9999;
        background: var(--tv-surface);
        border: 1px solid var(--tv-border);
        border-radius: 14px;
        box-shadow: 0 12px 40px rgba(0,0,0,0.18);
        padding: 18px 20px 16px;
        min-width: 300px;
        display: none;
        animation: tvDatePanelIn 0.18s cubic-bezier(0.16,1,0.3,1);
    }
    .tv-custom-date-panel.is-open { display: block; }
    @keyframes tvDatePanelIn {
        from { opacity: 0; transform: translateY(6px) scale(0.97); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .tv-custom-date-panel h4 {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: var(--tv-text-muted);
        margin: 0 0 12px;
    }
    .tv-custom-date-panel .tv-date-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 12px;
    }
    .tv-custom-date-panel .tv-date-row label {
        display: block;
        font-size: 10px;
        font-weight: 700;
        color: var(--tv-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 4px;
    }
    .tv-date-input {
        width: 100%;
        padding: 7px 10px;
        border: 1.5px solid var(--tv-border);
        border-radius: 8px;
        font-size: 12px;
        background: var(--tv-surface-active);
        color: var(--tv-text);
        transition: border-color 0.15s;
        box-sizing: border-box;
    }
    .tv-date-input:focus { outline: none; border-color: var(--tv-primary); }
    .tv-custom-date-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        margin-top: 4px;
        border-top: 1px solid var(--tv-border);
        padding-top: 12px;
    }
    </style>

    <div class="tv-date-filter-bar">
        <span class="tv-date-filter-label">Filter by:</span>

        <?php
        $date_range = isset($_GET['date_range']) ? sanitize_text_field($_GET['date_range']) : '';
        $date_from  = isset($_GET['date_from'])  ? sanitize_text_field($_GET['date_from'])  : '';
        $date_to    = isset($_GET['date_to'])    ? sanitize_text_field($_GET['date_to'])    : '';
        $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $base_filter_url = '?page=tv-subs-manager&tab=payments'
            . (!empty($filter_status)  ? '&status='  . urlencode($filter_status)  : '')
            . (!empty($search_term)    ? '&s='        . urlencode($search_term)    : '');

        $ranges = [
            ''   => 'All Time',
            '7'  => 'Last 7 Days',
            '14' => 'Last 14 Days',
            '30' => 'Last 30 Days',
        ];
        ?>

        <div class="tv-date-filter-group">
            <?php foreach($ranges as $val => $label):
                $is_active = ($date_range === (string)$val) && empty($date_from) && empty($date_to);
            ?>
                <a href="<?php echo esc_url($base_filter_url . ($val ? '&date_range=' . $val : '')); ?>"
                   class="tv-date-filter-btn <?php echo $is_active ? 'tv-active' : ''; ?>">
                    <?php echo esc_html($label); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="tv-date-divider"></div>

        <!-- Custom date range picker -->
        <div class="tv-custom-date-wrap" id="tvCustomDateWrap">

            <?php
            /* Show whether a custom range is currently active */
            $custom_active = (!empty($date_from) || !empty($date_to));
            $trigger_label = 'Custom Range';
            if ($custom_active) {
                $trigger_label = ($date_from ? esc_html($date_from) : '…')
                               . ' ? '
                               . ($date_to ? esc_html($date_to) : '…');
            }
            ?>
            <button type="button"
                    id="tvCustomDateTrigger"
                    class="tv-custom-date-trigger <?php echo $custom_active ? 'has-value' : ''; ?>"
                    aria-expanded="false"
                    onclick="tvToggleDatePanel()">
                <span class="dashicons dashicons-calendar-alt" style="font-size:14px;width:14px;height:14px;line-height:14px;"></span>
                <span id="tvCustomDateLabel"><?php echo $trigger_label; ?></span>
                <span class="dashicons dashicons-arrow-down-alt2" style="font-size:11px;width:11px;height:11px;line-height:11px;margin-left:2px;" id="tvDateCaret"></span>
            </button>

            <div class="tv-custom-date-panel" id="tvCustomDatePanel">
                <h4>&#128197; Custom Date Range</h4>
                <form method="get" id="tvCustomDateForm">
                    <input type="hidden" name="page"  value="tv-subs-manager">
                    <input type="hidden" name="tab"   value="payments">
                    <?php if(!empty($filter_status)):  ?><input type="hidden" name="status" value="<?php echo esc_attr($filter_status); ?>"><?php endif; ?>
                    <?php if(!empty($search_term)):    ?><input type="hidden" name="s"      value="<?php echo esc_attr($search_term); ?>"><?php endif; ?>

                    <div class="tv-date-row">
                        <div>
                            <label for="tvDateFrom">From</label>
                            <input type="date" name="date_from" id="tvDateFrom" class="tv-date-input"
                                   value="<?php echo esc_attr($date_from); ?>"
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div>
                            <label for="tvDateTo">To</label>
                            <input type="date" name="date_to" id="tvDateTo" class="tv-date-input"
                                   value="<?php echo esc_attr($date_to); ?>"
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="tv-custom-date-actions">
                        <?php if ($custom_active): ?>
                            <a href="<?php echo esc_url($base_filter_url); ?>"
                               class="tv-btn tv-btn-sm tv-btn-secondary"
                               style="color:var(--tv-danger);border-color:var(--tv-danger);">
                               &#10005; Clear
                            </a>
                        <?php endif; ?>
                        <button type="button" class="tv-btn tv-btn-sm tv-btn-secondary" onclick="tvCloseDatePanel()">Cancel</button>
                        <button type="submit" class="tv-btn tv-btn-sm tv-btn-primary">Apply Range</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    <!-- / date filter bar -->

    <script>
    (function(){
        var panel   = document.getElementById('tvCustomDatePanel');
        var trigger = document.getElementById('tvCustomDateTrigger');
        var caret   = document.getElementById('tvDateCaret');

        function tvToggleDatePanel() {
            var open = panel.classList.contains('is-open');
            open ? tvCloseDatePanel() : tvOpenDatePanel();
        }
        window.tvToggleDatePanel = tvToggleDatePanel;

        function tvOpenDatePanel() {
            panel.classList.add('is-open');
            trigger.classList.add('is-active');
            trigger.setAttribute('aria-expanded','true');
            caret.classList.replace('dashicons-arrow-down-alt2','dashicons-arrow-up-alt2');
        }
        function tvCloseDatePanel() {
            panel.classList.remove('is-open');
            trigger.classList.remove('is-active');
            trigger.setAttribute('aria-expanded','false');
            caret.classList.replace('dashicons-arrow-up-alt2','dashicons-arrow-down-alt2');
        }
        window.tvCloseDatePanel = tvCloseDatePanel;

        /* Close when clicking outside */
        document.addEventListener('click', function(e) {
            if (!document.getElementById('tvCustomDateWrap').contains(e.target)) {
                tvCloseDatePanel();
            }
        });

        /* Validate: from can't be after to */
        document.getElementById('tvDateFrom').addEventListener('change', function(){
            var to = document.getElementById('tvDateTo');
            if (to.value && this.value > to.value) to.value = this.value;
            if (this.value) to.min = this.value;
        });
        document.getElementById('tvDateTo').addEventListener('change', function(){
            var from = document.getElementById('tvDateFrom');
            if (from.value && this.value < from.value) from.value = this.value;
        });

        /* Keyboard: Escape closes */
        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape') tvCloseDatePanel();
        });
    })();
    </script>

    <!-- Financial Summary -->
    <?php if (isset($tx_summaries) && is_array($tx_summaries)): ?>
        <div style="padding:16px 22px;border-bottom:1px solid var(--tv-border);background:linear-gradient(135deg,rgba(var(--tv-primary-rgb),0.02) 0%,transparent 100%);">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;">
                <?php
                $accent_colors = ['color-blue','color-cyan','color-purple','color-green','color-orange','color-pink'];
                $idx = 0;
                foreach ($tx_summaries as $k => $meta):
                    $totals  = isset($meta['totals']) ? (array)$meta['totals'] : [];
                    $usd_val = isset($totals['USD']) ? $totals['USD'] : 0;
                    $ngn_val = isset($totals['NGN']) ? $totals['NGN'] : 0;
                    $color   = $accent_colors[$idx % count($accent_colors)];
                    $idx++;
                ?>
                    <div class="tv-stat-card <?php echo $color; ?>" style="padding:14px;gap:10px;margin-bottom:0;">
                        <div>
                            <div style="font-size:10px;color:var(--tv-text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">
                                <?php echo esc_html($meta['label']); ?>
                            </div>
                            <div style="font-size:18px;font-weight:900;color:var(--tv-text);line-height:1.1;">
                                <span class="tv-val-usd">$<?php echo number_format($usd_val, 2); ?></span>
                                <span class="tv-val-ngn" style="display:none;">&#8358;<?php echo number_format($ngn_val, 2); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:8px;font-size:11px;color:var(--tv-text-muted);">
                Week: Monday–Sunday. Timezone: GMT+1.
            </div>
        </div>
    <?php endif; ?>

    <div class="tv-table-container">