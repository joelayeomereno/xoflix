<?php if (!defined('ABSPATH')) { exit; } ?>

    <!-- Toolbar -->
    <div class="tv-toolbar" style="background:white; padding:16px 24px; border-bottom:1px solid var(--tv-border); display:flex; justify-content:space-between; align-items:center;">
        <div style="display:flex; gap:8px;">
            <a href="?page=tv-subs-manager&tab=payments&status=successful" class="tv-btn tv-btn-sm <?php echo ($filter_status === 'successful') ? 'tv-btn-primary' : 'tv-btn-secondary'; ?>">Successful</a>
            <a href="?page=tv-subs-manager&tab=payments&status=pending" class="tv-btn tv-btn-sm <?php echo ($filter_status === 'pending') ? 'tv-btn-primary' : 'tv-btn-secondary'; ?>">Pending</a>
            <a href="?page=tv-subs-manager&tab=payments&status=failed" class="tv-btn tv-btn-sm <?php echo ($filter_status === 'failed') ? 'tv-btn-primary' : 'tv-btn-secondary'; ?>">Failed</a>
            <a href="?page=tv-subs-manager&tab=payments&status=all" class="tv-btn tv-btn-sm <?php echo ($filter_status === 'all') ? 'tv-btn-primary' : 'tv-btn-secondary'; ?>">All</a>
        </div>

        <!-- CURRENCY TOGGLE (Controls Base & Locked Value columns only) -->
        <div style="display:flex; gap:0; background:var(--tv-surface-active); border-radius:8px; border:1px solid var(--tv-border); overflow:hidden;">
            <button type="button" onclick="tvToggleCurrency('USD')" id="tv-curr-usd" class="tv-btn-text" style="padding:6px 12px; font-weight:700; color:var(--tv-text); background:white;">USD</button>
            <button type="button" onclick="tvToggleCurrency('NGN')" id="tv-curr-ngn" class="tv-btn-text" style="padding:6px 12px; font-weight:700; color:var(--tv-text-muted);">NGN</button>
        </div>

        <div style="display:flex; gap:10px; align-items:center;">
            <button type="button" onclick="tvOpenManualTxn()" class="tv-btn tv-btn-primary tv-btn-sm" style="display:flex;align-items:center;gap:6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Add Transaction
            </button>
            <form method="get" style="display:flex; gap:10px;">
                <input type="hidden" name="page" value="tv-subs-manager">
                <input type="hidden" name="tab" value="payments">
                <input type="text" name="s" class="tv-input" placeholder="Search Ref or User..." value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
                <button type="submit" class="tv-btn tv-btn-secondary">Search</button>
            </form>
        </div>
    </div>
    <?php /* Nonce for manual transaction AJAX */ ?>
    <script>var tvManualNonce = '<?php echo esc_js(wp_create_nonce('tv_manual_sub_nonce')); ?>';</script>

    <!-- Date Filter Bar -->
    <?php
    $date_range  = isset($_GET['date_range'])  ? sanitize_text_field($_GET['date_range'])  : '';
    $date_from   = isset($_GET['date_from'])   ? sanitize_text_field($_GET['date_from'])   : '';
    $date_to     = isset($_GET['date_to'])     ? sanitize_text_field($_GET['date_to'])     : '';
    $search_term = isset($_GET['s'])           ? sanitize_text_field($_GET['s'])           : '';
    $df_base_url = '?page=tv-subs-manager&tab=payments'
        . (!empty($filter_status) ? '&status='.urlencode($filter_status) : '')
        . (!empty($search_term)   ? '&s='.urlencode($search_term)        : '');
    $custom_active = (!empty($date_from) || !empty($date_to));
    ?>
    <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;padding:11px 22px;background:var(--tv-surface);border-bottom:1px solid var(--tv-border);">
        <span style="font-size:10px;font-weight:800;color:var(--tv-text-muted);text-transform:uppercase;letter-spacing:.06em;">Date:</span>
        <?php
        $ranges = ['' => 'All Time', '7' => 'Last 7 Days', '14' => 'Last 14 Days', '30' => 'Last 30 Days'];
        foreach ($ranges as $val => $label):
            $is_active = !$custom_active && $date_range === (string)$val;
            $style = $is_active
                ? 'background:var(--tv-primary);color:#fff;border-color:var(--tv-primary);'
                : 'background:var(--tv-surface);color:var(--tv-text-muted);';
        ?>
            <a href="<?php echo esc_url($df_base_url.($val?'&date_range='.$val:'')); ?>"
               style="<?php echo $style; ?>padding:5px 12px;border-radius:7px;font-size:12px;font-weight:600;border:1.5px solid var(--tv-border);text-decoration:none;white-space:nowrap;display:inline-block;">
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
        <span style="width:1px;height:20px;background:var(--tv-border);display:inline-block;margin:0 2px;"></span>
        <div style="position:relative;display:inline-block;" id="tvDatePickerWrap">
            <?php
            $trig_label = $custom_active ? (($date_from ?: '\x85').' - '.($date_to ?: '\x85')) : 'Custom Range';
            $trig_style = $custom_active ? 'background:var(--tv-primary);color:#fff;border-color:var(--tv-primary);' : 'background:var(--tv-surface);color:var(--tv-text-muted);';
            ?>
            <button type="button" id="tvDateTrigger" onclick="tvToggleDatePicker()"
                    style="<?php echo $trig_style; ?>padding:5px 12px;border-radius:7px;font-size:12px;font-weight:600;border:1.5px solid var(--tv-border);cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <?php echo $trig_label; ?> 
                <span id="tvDateCaret"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg></span>
            </button>
            <div id="tvDatePanel" style="display:none;position:absolute;top:calc(100% + 8px);left:0;z-index:9999;background:var(--tv-surface);border:1px solid var(--tv-border);border-radius:12px;box-shadow:0 10px 36px rgba(0,0,0,.16);padding:16px 18px;min-width:290px;">
                <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--tv-text-muted);margin-bottom:10px;">Custom Date Range</div>
                <form method="get" id="tvDateForm">
                    <input type="hidden" name="page" value="tv-subs-manager">
                    <input type="hidden" name="tab"  value="payments">
                    <?php if(!empty($filter_status)):?><input type="hidden" name="status" value="<?php echo esc_attr($filter_status);?>"><?php endif;?>
                    <?php if(!empty($search_term)):?><input type="hidden" name="s" value="<?php echo esc_attr($search_term);?>"><?php endif;?>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
                        <div>
                            <div style="font-size:10px;font-weight:700;color:var(--tv-text-muted);text-transform:uppercase;margin-bottom:4px;">From</div>
                            <input type="date" name="date_from" id="tvDateFrom" value="<?php echo esc_attr($date_from);?>" max="<?php echo date('Y-m-d');?>"
                                   style="width:100%;padding:6px 9px;border:1.5px solid var(--tv-border);border-radius:7px;font-size:12px;background:var(--tv-surface);color:var(--tv-text);box-sizing:border-box;">
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:700;color:var(--tv-text-muted);text-transform:uppercase;margin-bottom:4px;">To</div>
                            <input type="date" name="date_to" id="tvDateTo" value="<?php echo esc_attr($date_to);?>" max="<?php echo date('Y-m-d');?>"
                                   style="width:100%;padding:6px 9px;border:1.5px solid var(--tv-border);border-radius:7px;font-size:12px;background:var(--tv-surface);color:var(--tv-text);box-sizing:border-box;">
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;justify-content:flex-end;border-top:1px solid var(--tv-border);padding-top:11px;">
                        <?php if($custom_active):?><a href="<?php echo esc_url($df_base_url);?>" style="padding:5px 11px;border-radius:7px;font-size:12px;font-weight:600;border:1.5px solid var(--tv-border);color:#ef4444;text-decoration:none;background:var(--tv-surface);">&#10005; Clear</a><?php endif;?>
                        <button type="button" onclick="tvCloseDatePicker()" style="padding:5px 11px;border-radius:7px;font-size:12px;font-weight:600;border:1.5px solid var(--tv-border);background:var(--tv-surface);color:var(--tv-text-muted);cursor:pointer;">Cancel</button>
                        <button type="submit" style="padding:5px 13px;border-radius:7px;font-size:12px;font-weight:700;border:none;background:var(--tv-primary);color:#fff;cursor:pointer;">Apply</button>
                    </div>
                </form>
            </div>
        </div>
        <?php if($custom_active):?><a href="<?php echo esc_url($df_base_url);?>" style="font-size:11px;color:#ef4444;font-weight:700;text-decoration:none;margin-left:4px;display:flex;align-items:center;gap:4px;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> Clear dates</a><?php endif;?>
    </div>
    <script>
    (function(){
        var panel=document.getElementById('tvDatePanel'),wrap=document.getElementById('tvDatePickerWrap'),caret=document.getElementById('tvDateCaret'),open=false;
        window.tvToggleDatePicker=function(){ open?tvCloseDatePicker():tvOpenDatePicker(); };
        function tvOpenDatePicker(){ panel.style.display='block'; open=true; caret.innerHTML='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>'; }
        window.tvCloseDatePicker=function(){ panel.style.display='none'; open=false; caret.innerHTML='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>'; };
        document.addEventListener('click',function(e){ if(open&&!wrap.contains(e.target)) tvCloseDatePicker(); });
        document.addEventListener('keydown',function(e){ if(e.key==='Escape') tvCloseDatePicker(); });
        var f=document.getElementById('tvDateFrom'),t=document.getElementById('tvDateTo');
        f&&f.addEventListener('change',function(){ if(t.value&&this.value>t.value) t.value=this.value; });
        t&&t.addEventListener('change',function(){ if(f.value&&this.value<f.value) f.value=this.value; });
    })();
    </script>
