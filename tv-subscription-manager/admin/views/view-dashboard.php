<?php
/**
 * FILE PATH: tv-subscription-manager/admin/views/view-dashboard.php
 *
 * ADMIN Dashboard   Restored & Enhanced.
 * Fixes: regression where admin dashboard was replaced with public dashboard.
 * Additions: ARPU card, conversion rate, new subs this month, monthly comparison,
 *            top plans table, quick-action buttons, improved chart styling.
 */
if (!defined('ABSPATH')) { exit; }

// Additional dashboard data
global $wpdb;
$prefix = $wpdb->prefix;

// New subs this month
$new_subs_month = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}tv_subscriptions WHERE status='active' AND start_date >= DATE_FORMAT(NOW(), '%Y-%m-01')");

// Expired this month
$expired_month = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}tv_subscriptions WHERE status='expired' AND end_date >= DATE_FORMAT(NOW(), '%Y-%m-01') AND end_date <= NOW()");

// Revenue this month
$revenue_this_month = (float) $wpdb->get_var("SELECT COALESCE(SUM(amount),0) FROM {$prefix}tv_payments WHERE UPPER(status) IN ('COMPLETED','APPROVED') AND date >= DATE_FORMAT(NOW(), '%Y-%m-01')");

// Revenue last month
$revenue_last_month = (float) $wpdb->get_var("SELECT COALESCE(SUM(amount),0) FROM {$prefix}tv_payments WHERE UPPER(status) IN ('COMPLETED','APPROVED') AND date >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') AND date < DATE_FORMAT(NOW(), '%Y-%m-01')");

$rev_change = ($revenue_last_month > 0) ? round((($revenue_this_month - $revenue_last_month) / $revenue_last_month) * 100, 1) : 0;

// Top 5 plans by active subscribers
$top_plans = $wpdb->get_results("
    SELECT p.name, COUNT(s.id) as sub_count, p.price
    FROM {$prefix}tv_subscriptions s
    JOIN {$prefix}tv_plans p ON s.plan_id = p.id
    WHERE s.status = 'active'
    GROUP BY s.plan_id
    ORDER BY sub_count DESC
    LIMIT 5
");

// Total all-time users (WP users)
$total_wp_users = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");

// Conversion rate: active subs / total WP users
$conversion_rate = ($total_wp_users > 0) ? round(($active_subs / $total_wp_users) * 100, 1) : 0;
?>

<div class="tv-content-area">
    <div class="tv-page-header">
        <div>
            <h1>Dashboard Overview</h1>
            <p>Real-time insights and system health at a glance.</p>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <span class="tv-badge approved" style="font-size:12px; padding:5px 12px;">
                <span class="dashicons dashicons-yes-alt" style="font-size:14px; width:14px; height:14px; margin-right:4px;"></span>
                System Online
            </span>
            <a href="<?php echo admin_url('admin.php?page=tv-subs-manager&tab=payments&status=needs_action'); ?>" class="tv-btn tv-btn-sm tv-btn-primary">
                Needs Action (<?php echo (int)$pending_payments; ?>)
            </a>
        </div>
    </div>

    <!-- PRIMARY STATS ROW -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:16px; margin-bottom:28px;">
        <div class="tv-stat-card color-blue">
            <div class="tv-stat-icon"><span class="dashicons dashicons-chart-area"></span></div>
            <div>
                <div class="tv-stat-label">Total Revenue</div>
                <div class="tv-stat-value">$<?php echo number_format((float)$total_revenue, 2); ?></div>
            </div>
        </div>
        <div class="tv-stat-card color-green">
            <div class="tv-stat-icon"><span class="dashicons dashicons-star-filled"></span></div>
            <div>
                <div class="tv-stat-label">Active Subs</div>
                <div class="tv-stat-value"><?php echo number_format($active_subs); ?></div>
            </div>
        </div>
        <div class="tv-stat-card color-orange">
            <div class="tv-stat-icon"><span class="dashicons dashicons-clock"></span></div>
            <div>
                <div class="tv-stat-label">Pending Payments</div>
                <div class="tv-stat-value"><?php echo number_format($pending_payments); ?></div>
            </div>
        </div>
        <div class="tv-stat-card color-purple">
            <div class="tv-stat-icon"><span class="dashicons dashicons-groups"></span></div>
            <div>
                <div class="tv-stat-label">Unique Subscribers</div>
                <div class="tv-stat-value"><?php echo number_format($total_users); ?></div>
            </div>
        </div>
        <div class="tv-stat-card color-cyan">
            <div class="tv-stat-icon"><span class="dashicons dashicons-money-alt"></span></div>
            <div>
                <div class="tv-stat-label">ARPU</div>
                <div class="tv-stat-value">$<?php echo number_format($arpu, 2); ?></div>
            </div>
        </div>
        <div class="tv-stat-card color-pink">
            <div class="tv-stat-icon"><span class="dashicons dashicons-chart-pie"></span></div>
            <div>
                <div class="tv-stat-label">Conversion Rate</div>
                <div class="tv-stat-value"><?php echo $conversion_rate; ?>%</div>
            </div>
        </div>
    </div>

    <!-- SECONDARY STATS - MONTH COMPARISON -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:28px;">
        <div class="tv-card" style="padding:20px; background:linear-gradient(135deg, rgba(99,102,241,0.08) 0%, rgba(6,182,212,0.04) 100%);">
            <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--tv-text-muted); margin-bottom:6px;">Revenue This Month</div>
            <div style="font-size:24px; font-weight:900; color:var(--tv-text);">$<?php echo number_format($revenue_this_month, 2); ?></div>
            <?php if ($rev_change != 0): ?>
            <div style="font-size:12px; font-weight:700; margin-top:6px; color:<?php echo $rev_change >= 0 ? 'var(--tv-success)' : 'var(--tv-danger)'; ?>;">
                <?php echo $rev_change >= 0 ? '?' : '?'; ?> <?php echo abs($rev_change); ?>% vs last month
            </div>
            <?php endif; ?>
        </div>
        <div class="tv-card" style="padding:20px;">
            <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--tv-text-muted); margin-bottom:6px;">New Subs This Month</div>
            <div style="font-size:24px; font-weight:900; color:var(--tv-success);"><?php echo number_format($new_subs_month); ?></div>
        </div>
        <div class="tv-card" style="padding:20px;">
            <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--tv-text-muted); margin-bottom:6px;">Expired This Month</div>
            <div style="font-size:24px; font-weight:900; color:var(--tv-danger);"><?php echo number_format($expired_month); ?></div>
        </div>
        <div class="tv-card" style="padding:20px;">
            <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--tv-text-muted); margin-bottom:6px;">Total WP Users</div>
            <div style="font-size:24px; font-weight:900; color:var(--tv-text);"><?php echo number_format($total_wp_users); ?></div>
        </div>
    </div>

    <div class="tv-grid-2">
        <div style="display:flex; flex-direction:column; gap:24px;">

            <!-- REVENUE CHART -->
            <div class="tv-card">
                <div class="tv-card-header">
                    <h3><span class="dashicons dashicons-chart-area" style="margin-right:8px; color:var(--tv-primary);"></span> Revenue Trends (Last 12 Months)</h3>
                </div>
                <div class="tv-card-body">
                    <canvas id="revenueChart" style="width:100%; height:300px;"></canvas>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var ctx = document.getElementById('revenueChart');
                        if (!ctx) return;
                        new Chart(ctx.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: [<?php foreach($chart_data as $d) echo "'" . esc_js($d->month_str) . "',"; ?>],
                                datasets: [{
                                    label: 'Revenue',
                                    data: [<?php foreach($chart_data as $d) echo floatval($d->revenue) . ","; ?>],
                                    borderColor: '#6366f1',
                                    backgroundColor: function(context) {
                                        var chart = context.chart;
                                        var ctx2 = chart.ctx, area = chart.chartArea;
                                        if (!area) return 'rgba(99,102,241,0.1)';
                                        var gradient = ctx2.createLinearGradient(0, area.bottom, 0, area.top);
                                        gradient.addColorStop(0, 'rgba(99,102,241,0.02)');
                                        gradient.addColorStop(1, 'rgba(99,102,241,0.18)');
                                        return gradient;
                                    },
                                    borderWidth: 3,
                                    fill: true,
                                    tension: 0.4,
                                    pointBackgroundColor: '#fff',
                                    pointBorderColor: '#6366f1',
                                    pointRadius: 5,
                                    pointHoverRadius: 7,
                                    pointBorderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: { borderDash: [5, 5], color: 'rgba(0,0,0,0.06)' },
                                        ticks: { font: { size: 11, weight: '600' }, callback: function(v) { return '$' + v.toLocaleString(); } }
                                    },
                                    x: {
                                        grid: { display: false },
                                        ticks: { font: { size: 11, weight: '600' } }
                                    }
                                }
                            }
                        });
                    });
                    </script>
                </div>
            </div>

            <!-- EXPIRING SOON -->
            <div class="tv-card">
                <div class="tv-card-header" style="background:linear-gradient(135deg, #fff7ed, #fffbeb); border-bottom:1px solid #ffedd5;">
                    <h3 style="color:#c2410c;">
                        <span class="dashicons dashicons-warning" style="margin-right:8px;"></span>
                        Expiring Soon (Next 3 Days)
                    </h3>
                    <span style="font-size:12px; font-weight:700; color:#ea580c; background:#fed7aa; padding:3px 10px; border-radius:99px;">
                        <?php echo count($expiring_soon ?: []); ?>
                    </span>
                </div>
                <div class="tv-card-body" style="padding:0;">
                    <?php if ($expiring_soon): ?>
                        <div class="tv-table-container" style="border:none;">
                            <table class="tv-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Plan</th>
                                        <th>Expires</th>
                                        <th align="right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expiring_soon as $sub):
                                        $hours_left = floor((strtotime($sub->end_date) - time()) / 3600);
                                    ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($sub->user_login); ?></strong></td>
                                        <td style="color:var(--tv-primary); font-weight:600;"><?php echo esc_html($sub->plan_name); ?></td>
                                        <td>
                                            <span class="tv-badge <?php echo ($hours_left < 24) ? 'rejected' : 'pending'; ?>">
                                                <?php echo ($hours_left < 24) ? $hours_left . ' hrs left' : floor($hours_left / 24) . ' days left'; ?>
                                            </span>
                                        </td>
                                        <td align="right">
                                            <a href="<?php echo admin_url('admin.php?page=tv-subs-manager&tab=users&action=manage&user_id=' . (int)$sub->user_id); ?>"
                                               class="tv-btn tv-btn-sm tv-btn-secondary">Manage</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="padding:40px; text-align:center; color:var(--tv-success);">
                            <span class="dashicons dashicons-yes-alt" style="font-size:36px; width:36px; height:36px;"></span>
                            <p style="margin-top:12px; font-weight:600; font-size:14px;">All clear   no subscriptions expiring soon.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div style="display:flex; flex-direction:column; gap:24px;">

            <!-- TOP PLANS -->
            <?php if (!empty($top_plans)): ?>
            <div class="tv-card">
                <div class="tv-card-header">
                    <h3><span class="dashicons dashicons-awards" style="margin-right:8px; color:var(--tv-purple);"></span> Top Plans by Active Subs</h3>
                </div>
                <div class="tv-card-body" style="padding:0;">
                    <?php foreach ($top_plans as $idx => $tp):
                        $colors = ['#6366f1', '#06b6d4', '#10b981', '#f59e0b', '#ec4899'];
                        $bar_color = $colors[$idx % count($colors)];
                        $max_count = $top_plans[0]->sub_count ?: 1;
                        $bar_width = round(($tp->sub_count / $max_count) * 100);
                    ?>
                    <div style="padding:14px 20px; border-bottom:1px solid var(--tv-border); display:flex; align-items:center; gap:14px;">
                        <div style="width:28px; height:28px; border-radius:8px; background:<?php echo $bar_color; ?>15; color:<?php echo $bar_color; ?>; font-weight:800; font-size:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <?php echo $idx + 1; ?>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:700; font-size:13px; color:var(--tv-text); margin-bottom:6px;"><?php echo esc_html($tp->name); ?></div>
                            <div style="height:6px; background:var(--tv-surface-active); border-radius:99px; overflow:hidden;">
                                <div style="height:100%; width:<?php echo $bar_width; ?>%; background:<?php echo $bar_color; ?>; border-radius:99px; transition:width 0.6s;"></div>
                            </div>
                        </div>
                        <div style="text-align:right; flex-shrink:0;">
                            <div style="font-weight:800; font-size:14px; color:var(--tv-text);"><?php echo (int)$tp->sub_count; ?></div>
                            <div style="font-size:11px; color:var(--tv-text-muted);">$<?php echo number_format((float)$tp->price, 2); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- RECENT ACTIVITY -->
            <div class="tv-card">
                <div class="tv-card-header">
                    <h3><span class="dashicons dashicons-backup" style="margin-right:8px; color:var(--tv-accent);"></span> Recent Activity</h3>
                </div>
                <div class="tv-card-body" style="padding:0;">
                    <?php if ($logs): ?>
                        <div style="padding:20px;">
                            <?php foreach ($logs as $log): ?>
                            <div style="display:flex; gap:15px; margin-bottom:18px; position:relative;">
                                <div style="width:10px; height:10px; border-radius:50%; background:var(--tv-primary); margin-top:5px; flex-shrink:0; box-shadow:0 0 0 3px rgba(99,102,241,0.15);"></div>
                                <div style="padding-bottom:16px; border-left:2px solid var(--tv-border); margin-left:-20px; padding-left:25px; flex:1;">
                                    <div style="font-size:10px; color:var(--tv-text-muted); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:3px;">
                                        <?php echo date('M j, H:i', strtotime($log->date)); ?>
                                    </div>
                                    <div style="font-weight:700; font-size:13px; color:var(--tv-text);">
                                        <?php echo esc_html($log->action); ?>
                                    </div>
                                    <?php if ($log->details): ?>
                                        <div style="font-size:12px; color:var(--tv-text-muted); margin-top:2px;">
                                            <?php echo esc_html(mb_strimwidth($log->details, 0, 80, '...')); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="padding:30px; color:var(--tv-text-muted); text-align:center;">No recent activity logged.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="tv-card">
                <div class="tv-card-header">
                    <h3><span class="dashicons dashicons-admin-tools" style="margin-right:8px; color:var(--tv-orange);"></span> Quick Actions</h3>
                </div>
                <div class="tv-card-body" style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <a href="<?php echo admin_url('admin.php?page=tv-subs-manager&tab=payments&status=needs_action'); ?>" class="tv-btn tv-btn-secondary" style="justify-content:center; height:40px; font-size:12px;">
                        <span class="dashicons dashicons-clock" style="font-size:14px; width:14px; height:14px; margin-right:6px;"></span> Pending Queue
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=tv-subs-manager&tab=plans'); ?>" class="tv-btn tv-btn-secondary" style="justify-content:center; height:40px; font-size:12px;">
                        <span class="dashicons dashicons-cart" style="font-size:14px; width:14px; height:14px; margin-right:6px;"></span> Manage Plans
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=tv-subscribers'); ?>" class="tv-btn tv-btn-secondary" style="justify-content:center; height:40px; font-size:12px;">
                        <span class="dashicons dashicons-groups" style="font-size:14px; width:14px; height:14px; margin-right:6px;"></span> Subscribers
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=tv-settings-general'); ?>" class="tv-btn tv-btn-secondary" style="justify-content:center; height:40px; font-size:12px;">
                        <span class="dashicons dashicons-admin-generic" style="font-size:14px; width:14px; height:14px; margin-right:6px;"></span> Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
```

## File: tv-subscription-manager/admin/views/view-finance.php
```php:tv-subscription-manager/admin/views/view-finance.php
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
            Weekly grouping rule: Monday-Sunday (GMT+1). Range: <?php echo esc_html($from->format('Y-m-d')); ?> ? <?php echo esc_html($to->format('Y-m-d')); ?>
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

```

## File: tv-subscription-manager/admin/views/view-payment-methods.php
```php:tv-subscription-manager/admin/views/view-payment-methods.php
<?php
// Determine View State (Add vs Edit)
$is_edit = !empty($edit_method);
$form_title = $is_edit ? 'Edit Payment Method' : 'Add New Method';
$btn_text = $is_edit ? 'Update Method' : 'Save Method';

// Pre-fill values
$val_name = $is_edit ? $edit_method->name : '';
$val_slug = $is_edit ? $edit_method->slug : '';
$val_logo_url = $is_edit && isset($edit_method->logo_url) ? $edit_method->logo_url : '';
$val_bank_name = $is_edit && isset($edit_method->bank_name) ? $edit_method->bank_name : '';
$val_account_name = $is_edit && isset($edit_method->account_name) ? $edit_method->account_name : '';
$val_account_number = $is_edit && isset($edit_method->account_number) ? $edit_method->account_number : '';
$val_countries = $is_edit ? $edit_method->countries : '';
$val_currencies = $is_edit ? $edit_method->currencies : '';
$val_instructions = $is_edit ? $edit_method->instructions : '';
$val_link = $is_edit ? $edit_method->link : '';
// Behavior is now hardcoded to 'window' in backend, removed from UI
$val_status = $is_edit ? $edit_method->status : 'active';
$val_order = $is_edit ? $edit_method->display_order : 0;
$val_notes = $is_edit ? $edit_method->notes : '';

// Optional Flutterwave settings
$val_fw_enabled = ($is_edit && isset($edit_method->flutterwave_enabled)) ? intval($edit_method->flutterwave_enabled) : 0;
$val_fw_secret = ($is_edit && isset($edit_method->flutterwave_secret_key)) ? $edit_method->flutterwave_secret_key : '';
$val_fw_public = ($is_edit && isset($edit_method->flutterwave_public_key)) ? $edit_method->flutterwave_public_key : '';
$val_fw_currency = ($is_edit && isset($edit_method->flutterwave_currency) && !empty($edit_method->flutterwave_currency)) ? $edit_method->flutterwave_currency : 'USD';
$val_fw_title = ($is_edit && isset($edit_method->flutterwave_title)) ? $edit_method->flutterwave_title : '';
$val_fw_logo = ($is_edit && isset($edit_method->flutterwave_logo)) ? $edit_method->flutterwave_logo : '';
?>

<div class="tv-page-header">
    <div>
        <h1>Payment Methods</h1>
        <p>Define manual payment links and instructions with smart geo-targeting.</p>
    </div>
    <?php if($is_edit): ?>
    <div>
        <a href="?page=tv-subs-manager&tab=methods" class="tv-btn tv-btn-secondary">
            <span class="dashicons dashicons-arrow-left-alt" style="margin-right:6px;"></span> Cancel Edit
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="tv-grid-2">
    <!-- Form -->
    <div class="tv-card" id="method-form-card">
        <div class="tv-card-header">
            <h3><?php echo esc_html($form_title); ?></h3>
        </div>
        <div class="tv-card-body">
            <form method="post" action="?page=tv-subs-manager&tab=methods" id="tv-method-form">
                <?php wp_nonce_field('save_method_verify'); ?>
                
                <?php if($is_edit): ?>
                    <input type="hidden" name="method_id" value="<?php echo esc_attr($edit_method->id); ?>">
                <?php endif; ?>
                
                <div class="tv-form-group">
                    <label class="tv-label">Method Name</label>
                    <input type="text" name="method_name" required class="tv-input" value="<?php echo esc_attr($val_name); ?>" placeholder="e.g. Pay with Naira (Bank Transfer)">
                </div>
                
                <div class="tv-form-group">
                    <label class="tv-label">Slug (Internal ID)</label>
                    <input type="text" name="method_slug" required class="tv-input" value="<?php echo esc_attr($val_slug); ?>" placeholder="e.g. pay-naira-bank">
                </div>

                <div class="tv-form-group">
                    <label class="tv-label">Logo URL (optional)</label>
                    <input type="url" name="method_logo_url" class="tv-input" value="<?php echo esc_attr($val_logo_url); ?>" placeholder="https://example.com/logo.png">
                    <small style="color:var(--tv-text-muted);">Used on the user checkout as a branded payment card.</small>
                </div>

                <!-- Structured Bank Details -->
                <div class="tv-form-group" style="background:var(--tv-surface-active); padding:15px; border-radius:8px; border:1px solid var(--tv-border);">
                    <label class="tv-label" style="margin-bottom:10px;">Bank / Transfer Details (Optional)</label>
                    <div class="tv-row">
                        <div class="tv-col">
                            <label class="tv-label">Bank Name</label>
                            <input type="text" name="method_bank_name" class="tv-input" value="<?php echo esc_attr($val_bank_name); ?>" placeholder="e.g. Access Bank">
                        </div>
                        <div class="tv-col">
                            <label class="tv-label">Account Name</label>
                            <input type="text" name="method_account_name" class="tv-input" value="<?php echo esc_attr($val_account_name); ?>" placeholder="e.g. XOFLIX LTD">
                        </div>
                    </div>
                    <div class="tv-form-group" style="margin-top:10px; position: relative;">
                        <label class="tv-label">Account Number</label>
                        <div style="display:flex; gap:8px;">
                            <input type="text" id="acc_num_field" name="method_account_number" class="tv-input" value="<?php echo esc_attr($val_account_number); ?>" placeholder="e.g. 0123456789">
                            <button type="button" class="tv-btn tv-btn-secondary" onclick="copyField('acc_num_field')" title="Test Copy"><span class="dashicons dashicons-admin-page"></span></button>
                        </div>
                    </div>
                    <p style="font-size:12px; color:var(--tv-text-muted); margin-top:6px;">Leave blank if this payment method is a gateway/link-only method.</p>
                </div>

                <!-- SMART COUNTRY SELECTOR -->
                <div class="tv-form-group" style="background:var(--tv-surface-active); padding:15px; border-radius:8px; border:1px solid var(--tv-border);">
                    <label class="tv-label">Target Countries</label>
                    
                    <!-- Hidden Real Input -->
                    <input type="hidden" name="method_countries" id="real_countries_input" value="<?php echo esc_attr($val_countries); ?>">
                    
                    <!-- Selected Tags Area -->
                    <div id="country-tags" style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:8px;"></div>
                    
                    <!-- Search Input -->
                    <div style="position:relative;">
                        <input type="text" id="country-search" class="tv-input" placeholder="Type a country name (e.g. Rwanda, USA)...">
                        <div id="country-dropdown" style="display:none; position:absolute; top:100%; left:0; width:100%; max-height:200px; overflow-y:auto; background:white; border:1px solid var(--tv-border); border-radius:8px; z-index:100; box-shadow:0 4px 12px rgba(0,0,0,0.1); margin-top:4px;"></div>
                    </div>

                    <p style="font-size:12px; color:var(--tv-text-muted); margin-top:6px;">
                        <span class="dashicons dashicons-globe" style="font-size:14px; margin-top:2px;"></span>
                        <strong>Global Mode:</strong> Leave empty to show this method to <u>everyone</u> worldwide.
                    </p>
                </div>

                <div class="tv-row">
                    <div class="tv-col">
                        <label class="tv-label">Accepted Currencies</label>
                        <input type="text" name="method_currencies" class="tv-input" value="<?php echo esc_attr($val_currencies); ?>" placeholder="USD, EUR, NGN">
                    </div>
                    <div class="tv-col">
                        <label class="tv-label">Display Order</label>
                        <input type="number" name="method_order" class="tv-input" value="<?php echo esc_attr($val_order); ?>">
                    </div>
                </div>

                <div class="tv-form-group">
                    <label class="tv-label">Payment Instructions (HTML allowed)</label>
                    <textarea name="method_instructions" class="tv-textarea" rows="4" placeholder="Transfer to: Access Bank..."><?php echo esc_textarea($val_instructions); ?></textarea>
                </div>

                <div class="tv-form-group">
                    <label class="tv-label">Payment Link / URL</label>
                    <input type="url" name="method_link" class="tv-input" value="<?php echo esc_attr($val_link); ?>" placeholder="https://paystack.com/pay/...">
                    <small style="color:var(--tv-text-muted);">User will be redirected to this link in a new tab.</small>
                </div>

                <!-- Flutterwave runtime checkout -->
                <div class="tv-form-group" style="background:var(--tv-surface-active); padding:15px; border-radius:8px; border:1px solid var(--tv-border);">
                    <label class="tv-label" style="margin-bottom:10px;">Flutterwave Runtime Checkout (Optional)</label>
                    <label class="tv-switch" style="font-weight:600;">
                        <input type="checkbox" id="fw_enabled" name="flutterwave_enabled" value="1" <?php checked($val_fw_enabled, 1); ?> class="tv-toggle-input" />
                        <span class="tv-toggle-ui" aria-hidden="true"></span>
                        <span>Enable Flutterwave dynamic checkout for this method</span>
                    </label>
                    <p style="font-size:12px; color:var(--tv-text-muted); margin-top:8px;">
                        When enabled, the system will generate a <strong>fresh</strong> hosted checkout link per attempt using Flutterwave v3 API.
                        The existing Payment Link field above is ignored for Flutterwave-enabled methods.
                    </p>

                    <div class="tv-row" style="margin-top:12px;">
                        <div class="tv-col">
                            <label class="tv-label">Secret Key</label>
                            <input type="password" name="flutterwave_secret_key" class="tv-input" value="<?php echo esc_attr($val_fw_secret); ?>" placeholder="FLWSECK-...">
                            <small style="color:var(--tv-text-muted);">Required when enabled.</small>
                        </div>
                        <div class="tv-col">
                            <label class="tv-label">Public Key (optional)</label>
                            <input type="text" name="flutterwave_public_key" class="tv-input" value="<?php echo esc_attr($val_fw_public); ?>" placeholder="FLWPUBK-...">
                        </div>
                    </div>

                    <div class="tv-row" style="margin-top:10px;">
                        <div class="tv-col">
                            <label class="tv-label">Currency</label>
                            <input type="text" name="flutterwave_currency" class="tv-input" value="<?php echo esc_attr($val_fw_currency ?: 'USD'); ?>" placeholder="USD">
                        </div>
                        <div class="tv-col">
                            <label class="tv-label">Checkout Title (optional)</label>
                            <input type="text" name="flutterwave_title" class="tv-input" value="<?php echo esc_attr($val_fw_title); ?>" placeholder="XOFLIX TV Subscription">
                        </div>
                    </div>

                    <div class="tv-form-group" style="margin-top:10px;">
                        <label class="tv-label">Checkout Logo URL (optional)</label>
                        <input type="url" name="flutterwave_logo" class="tv-input" value="<?php echo esc_attr($val_fw_logo); ?>" placeholder="https://example.com/logo.png">
                    </div>
                </div>

                <div class="tv-form-group">
                    <label class="tv-label">Status</label>
                    <select name="method_status" class="tv-input">
                        <option value="active" <?php selected($val_status, 'active'); ?>>Active</option>
                        <option value="inactive" <?php selected($val_status, 'inactive'); ?>>Inactive</option>
                    </select>
                </div>

                <div class="tv-form-group">
                    <label class="tv-label">Internal Notes</label>
                    <textarea name="method_notes" class="tv-textarea" rows="2"><?php echo esc_textarea($val_notes); ?></textarea>
                </div>

                <button type="submit" name="save_method" class="tv-btn tv-btn-primary w-full" style="height:38px; justify-content:center;">
                    <?php echo esc_html($btn_text); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- List -->
    <div class="tv-card">
        <div class="tv-card-header">
            <h3>Active Methods</h3>
        </div>
        <div class="tv-table-container">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Target Region</th>
                        <th>Status</th>
                        <th align="right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($methods): foreach($methods as $m): 
                        $edit_url = '?page=tv-subs-manager&tab=methods&action=edit&id='.$m->id;
                        $delete_url = wp_nonce_url('?page=tv-subs-manager&tab=methods&action=delete_method&id='.$m->id, 'delete_method_'.$m->id);
                        $is_active_row = ($is_edit && $edit_method->id == $m->id);
                        $row_style = $is_active_row ? 'background:var(--tv-surface-active);' : '';
                        
                        $country_display = '<span class="tv-badge free">Global</span>';
                        if (!empty($m->countries)) {
                            $codes = explode(',', $m->countries);
                            $count = count($codes);
                            if($count > 2) {
                                $country_display = '<span class="tv-badge" style="background:#dbeafe; color:#1e40af;">' . esc_html($codes[0] . ', ' . $codes[1]) . ' +'.($count-2).'</span>';
                            } else {
                                $country_display = '<span class="tv-badge" style="background:#dbeafe; color:#1e40af;">' . esc_html($m->countries) . '</span>';
                            }
                        }
                    ?>
                    <tr style="<?php echo $row_style; ?>">
                        <td>
                            <div style="font-weight:600;"><?php echo esc_html($m->name); ?></div>
                            <div style="font-size:11px; color:var(--tv-text-muted);"><?php echo esc_html($m->slug); ?></div>
                        </td>
                        <td>
                            <?php echo $country_display; ?>
                        </td>
                        <td>
                            <span class="tv-badge <?php echo $m->status; ?>"><?php echo ucfirst($m->status); ?></span>
                        </td>
                        <td align="right">
                            <div style="display:flex; justify-content:flex-end; gap:6px;">
                                <a href="<?php echo $edit_url; ?>" class="tv-btn tv-btn-sm tv-btn-secondary">Edit</a>
                                <!-- FIXED: Added data-tv-delete="1" to enforce JS interception -->
                                <a href="<?php echo $delete_url; ?>" class="tv-btn tv-btn-danger tv-btn-sm" data-tv-delete="1">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="4" style="text-align:center; padding:30px; color:var(--tv-text-muted);">No payment methods defined.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.getElementById('tv-method-form').addEventListener('submit', function() {
        const btn = this.querySelector('button[type="submit"]');
        btn.innerHTML = '<span class="dashicons dashicons-update" style="animation:spin 2s infinite linear; margin-right:6px;"></span> Saving...';
        btn.style.opacity = '0.7';
        btn.style.pointerEvents = 'none';
    });
    
    function copyField(id) {
        const el = document.getElementById(id);
        if(el && el.value) {
            navigator.clipboard.writeText(el.value).then(() => {
                alert('Copied: ' + el.value);
            });
        }
    }
    
    if (!document.getElementById('tv-spin-style')) {
        const style = document.createElement('style');
        style.id = 'tv-spin-style';
        style.innerHTML = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    }

    // --- SMART COUNTRY SELECTOR LOGIC ---
    (function(){
        const countries = [
            // Americas
            {c:'US',n:'United States'},{c:'CA',n:'Canada'},{c:'MX',n:'Mexico'},{c:'BR',n:'Brazil'},
            {c:'AR',n:'Argentina'},{c:'CO',n:'Colombia'},{c:'CL',n:'Chile'},{c:'PE',n:'Peru'},
            {c:'EC',n:'Ecuador'},{c:'VE',n:'Venezuela'},{c:'DO',n:'Dominican Republic'},
            
            // Europe
            {c:'GB',n:'United Kingdom'},{c:'DE',n:'Germany'},{c:'FR',n:'France'},{c:'ES',n:'Spain'},
            {c:'IT',n:'Italy'},{c:'NL',n:'Netherlands'},{c:'SE',n:'Sweden'},{c:'NO',n:'Norway'},
            {c:'DK',n:'Denmark'},{c:'FI',n:'Finland'},{c:'IE',n:'Ireland'},{c:'PL',n:'Poland'},
            {c:'PT',n:'Portugal'},{c:'GR',n:'Greece'},{c:'CH',n:'Switzerland'},{c:'BE',n:'Belgium'},
            {c:'AT',n:'Austria'},{c:'CZ',n:'Czechia'},{c:'HU',n:'Hungary'},{c:'RO',n:'Romania'},
            {c:'BG',n:'Bulgaria'},{c:'RS',n:'Serbia'},{c:'HR',n:'Croatia'},{c:'RU',n:'Russia'},
            {c:'TR',n:'Turkey'},{c:'UA',n:'Ukraine'},
            
            // Africa
            {c:'RW',n:'Rwanda'},{c:'NG',n:'Nigeria'},{c:'GH',n:'Ghana'},{c:'KE',n:'Kenya'},
            {c:'ZA',n:'South Africa'},{c:'EG',n:'Egypt'},{c:'MA',n:'Morocco'},{c:'DZ',n:'Algeria'},
            {c:'UG',n:'Uganda'},{c:'TZ',n:'Tanzania'},{c:'ET',n:'Ethiopia'},{c:'CM',n:'Cameroon'},
            {c:'CI',n:'Ivory Coast'},{c:'SN',n:'Senegal'},{c:'ZM',n:'Zambia'},{c:'ZW',n:'Zimbabwe'},
            {c:'AO',n:'Angola'},{c:'MZ',n:'Mozambique'},{c:'TN',n:'Tunisia'},{c:'MU',n:'Mauritius'},
            
            // Asia & Middle East
            {c:'IN',n:'India'},{c:'CN',n:'China'},{c:'JP',n:'Japan'},{c:'KR',n:'South Korea'},
            {c:'SG',n:'Singapore'},{c:'MY',n:'Malaysia'},{c:'ID',n:'Indonesia'},{c:'TH',n:'Thailand'},
            {c:'PH',n:'Philippines'},{c:'VN',n:'Vietnam'},{c:'PK',n:'Pakistan'},{c:'BD',n:'Bangladesh'},
            {c:'AE',n:'UAE'},{c:'SA',n:'Saudi Arabia'},{c:'QA',n:'Qatar'},{c:'KW',n:'Kuwait'},
            {c:'IL',n:'Israel'},{c:'OM',n:'Oman'},{c:'BH',n:'Bahrain'},{c:'LB',n:'Lebanon'},
            
            // Oceania
            {c:'AU',n:'Australia'},{c:'NZ',n:'New Zealand'}
        ];

        const searchInput = document.getElementById('country-search');
        const hiddenInput = document.getElementById('real_countries_input');
        const tagsContainer = document.getElementById('country-tags');
        const dropdown = document.getElementById('country-dropdown');

        let selected = hiddenInput.value ? hiddenInput.value.split(',').map(s=>s.trim()).filter(s=>s) : [];

        function renderTags() {
            tagsContainer.innerHTML = '';
            selected.forEach(code => {
                const country = countries.find(c => c.c === code) || {c: code, n: code};
                const tag = document.createElement('div');
                tag.style.cssText = 'background:#e0e7ff; color:#3730a3; padding:4px 10px; border-radius:12px; font-size:12px; font-weight:600; display:flex; align-items:center; gap:6px; border:1px solid #c7d2fe;';
                tag.innerHTML = `
                    <img src="https://flagcdn.com/w20/${country.c.toLowerCase()}.png" style="width:14px; height:auto; border-radius:2px;">
                    ${country.n}
                    <span style="cursor:pointer; margin-left:2px; opacity:0.6; font-size:14px;" onclick="removeCountry('${country.c}')">&times;</span>
                `;
                tagsContainer.appendChild(tag);
            });
            hiddenInput.value = selected.join(',');
        }

        window.removeCountry = function(code) {
            selected = selected.filter(c => c !== code);
            renderTags();
        };

        function addCountry(code) {
            if(!selected.includes(code)) {
                selected.push(code);
                renderTags();
            }
            searchInput.value = '';
            dropdown.style.display = 'none';
        }

        searchInput.addEventListener('input', function() {
            const term = this.value.toUpperCase();
            dropdown.innerHTML = '';
            
            if(term.length === 0) {
                dropdown.style.display = 'none';
                return;
            }

            const matches = countries.filter(c => (c.n.toUpperCase().includes(term) || c.c.includes(term)) && !selected.includes(c.c));
            
            if(matches.length > 0) {
                dropdown.style.display = 'block';
                matches.forEach(c => {
                    const item = document.createElement('div');
                    item.style.cssText = 'padding:10px 12px; cursor:pointer; display:flex; align-items:center; gap:10px; hover:bg-gray-100; font-size:13px; border-bottom:1px solid #f1f5f9;';
                    item.onmouseover = function(){ this.style.backgroundColor = '#f8fafc'; };
                    item.onmouseout = function(){ this.style.backgroundColor = 'white'; };
                    item.innerHTML = `<img src="https://flagcdn.com/w40/${c.c.toLowerCase()}.png" style="width:20px;"> <strong>${c.n}</strong> <span style="color:#94a3b8; margin-left:auto; font-size:11px;">${c.c}</span>`;
                    item.onclick = () => addCountry(c.c);
                    dropdown.appendChild(item);
                });
            } else {
                dropdown.style.display = 'none';
            }
        });

        // Close dropdown on click outside
        document.addEventListener('click', function(e) {
            if(e.target !== searchInput && e.target.closest('#country-dropdown') === null) {
                dropdown.style.display = 'none';
            }
        });

        // Initial render
        renderTags();
    })();
</script>