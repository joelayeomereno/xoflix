<?php if (!defined('ABSPATH')) exit; ?>
<?php
/**
 * File: tv-subscription-manager/admin/views/view-finance.php
 * Path: /tv-subscription-manager/admin/views/view-finance.php
 *
 * Variables expected:
 * - $range (string)
 * - $from (DateTime)
 * - $to (DateTime)
 * - $report (array)
 */
?>
<div style="display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:18px;">
    <div>
        <h2 style="margin:0; font-size:22px; font-weight:800; color:var(--tv-text);">Finance</h2>
        <div style="color:var(--tv-text-muted); font-size:12px; margin-top:4px;">
            Weekly grouping rule: Monday-Sunday (GMT+1). Range: <?php echo esc_html($from->format('Y-m-d')); ?> → <?php echo esc_html($to->format('Y-m-d')); ?>
        </div>
    </div>

    <div style="display:flex; gap:10px; align-items:center;">
        <form method="get" style="display:flex; gap:10px; align-items:center; margin:0;">
            <input type="hidden" name="page" value="tv-subs-manager">
            <input type="hidden" name="tab" value="finance">
            <select name="range" class="tv-input" style="width:140px;" onchange="this.form.submit()">
                <option value="4w" <?php selected($range, '4w'); ?>>Last 4 weeks</option>
                <option value="8w" <?php selected($range, '8w'); ?>>Last 8 weeks</option>
                <option value="12w" <?php selected($range, '12w'); ?>>Last 12 weeks</option>
            </select>
        </form>

        <a class="tv-btn tv-btn-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=tv_finance_export_csv&range=' . urlencode($range)), 'tv_finance_export_csv')); ?>">
            Export CSV
        </a>
    </div>
</div>

<div class="tv-table-container">
    <table class="tv-table">
        <thead>
            <tr>
                <th width="18%">Week</th>
                <th width="14%">Total</th>
                <th width="12%">Count</th>
                <th width="14%">New</th>
                <th width="12%">New Count</th>
                <th width="14%">Renewals</th>
                <th width="12%">Renew Count</th>
                <th>Breakdown</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($report)): ?>
            <?php foreach ($report as $wk => $w): ?>
                <tr>
                    <td>
                        <div style="font-weight:700;"><?php echo esc_html($w['week_start']->format('M d')); ?> - <?php echo esc_html($w['week_end']->format('M d, Y')); ?></div>
                        <div style="font-size:11px; color:var(--tv-text-muted);">Week start: <?php echo esc_html($w['week_start']->format('Y-m-d')); ?> (GMT+1)</div>
                    </td>
                    <td style="font-weight:700;">$<?php echo esc_html(number_format((float)$w['total'], 2)); ?></td>
                    <td><?php echo (int)$w['count']; ?></td>
                    <td>$<?php echo esc_html(number_format((float)$w['new_total'], 2)); ?></td>
                    <td><?php echo (int)$w['new_count']; ?></td>
                    <td>$<?php echo esc_html(number_format((float)$w['renew_total'], 2)); ?></td>
                    <td><?php echo (int)$w['renew_count']; ?></td>
                    <td>
                        <div style="display:flex; gap:14px; flex-wrap:wrap;">
                            <div>
                                <div style="font-size:11px; color:var(--tv-text-muted); font-weight:700; margin-bottom:4px;">Methods</div>
                                <?php if (!empty($w['by_method'])): ?>
                                    <?php foreach ($w['by_method'] as $m=>$amt): ?>
                                        <div style="font-size:12px;"><?php echo esc_html($m); ?>: <b>$<?php echo esc_html(number_format((float)$amt, 2)); ?></b></div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="font-size:12px; color:var(--tv-text-muted);">-</div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div style="font-size:11px; color:var(--tv-text-muted); font-weight:700; margin-bottom:4px;">Plans</div>
                                <?php if (!empty($w['by_plan'])): ?>
                                    <?php foreach ($w['by_plan'] as $pn=>$amt): ?>
                                        <div style="font-size:12px;"><?php echo esc_html($pn); ?>: <b>$<?php echo esc_html(number_format((float)$amt, 2)); ?></b></div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="font-size:12px; color:var(--tv-text-muted);">-</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8" style="text-align:center; padding:40px; color:var(--tv-text-muted);">No paid transactions found in this range.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
