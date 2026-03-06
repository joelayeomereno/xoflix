<?php if (!defined('ABSPATH')) exit; ?>
<?php
/**
 * File: tv-subscription-manager/admin/views/view-subscriber-detail.php
 * Path: /tv-subscription-manager/admin/views/view-subscriber-detail.php
 *
 * Variables (from class-tv-admin-users.php):
 * - $sub (object) subscription row with user + plan joins
 * - $ltv (string|float|null) lifetime value (SUM of completed payments)
 * - $history (array) payment history rows for the user
 */
?>
<div style="display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:18px;">
    <div>
        <h2 style="margin:0; font-size:22px; font-weight:800; color:var(--tv-text);">Subscriber Detail</h2>
        <div style="color:var(--tv-text-muted); font-size:12px; margin-top:4px;">
            Subscription ID: <?php echo (int)$sub->id; ?> • User ID: <?php echo (int)$sub->user_id; ?> • <?php echo esc_html($sub->user_email); ?>
        </div>
    </div>

    <div style="display:flex; gap:10px; align-items:center;">
        <a class="tv-btn tv-btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=tv-subs-manager&tab=users')); ?>">← Back to Users</a>
        <a class="tv-btn tv-btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=tv-subs-manager&tab=users&action=manage&user_id='.(int)$sub->user_id)); ?>">Manage User</a>
        <a class="tv-btn tv-btn-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=tv-subs-manager&tab=users&action=start_impersonation&user_id=' . (int)$sub->user_id), 'start_impersonation_' . (int)$sub->user_id)); ?>">Login as</a>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
    <div style="background:var(--tv-surface); border:1px solid var(--tv-border); border-radius:14px; padding:14px;">
        <h3 style="margin:0 0 10px; font-size:14px; font-weight:900;">User</h3>
        <div style="font-size:13px; color:var(--tv-text); line-height:1.6;">
            <div><strong>Name:</strong> <?php echo esc_html($sub->display_name ?: $sub->user_login); ?></div>
            <div><strong>Email:</strong> <?php echo esc_html($sub->user_email); ?></div>
        </div>
    </div>

    <div style="background:var(--tv-surface); border:1px solid var(--tv-border); border-radius:14px; padding:14px;">
        <h3 style="margin:0 0 10px; font-size:14px; font-weight:900;">Subscription</h3>
        <div style="font-size:13px; color:var(--tv-text); line-height:1.6;">
            <div><strong>Plan:</strong> <?php echo esc_html($sub->plan_name ?: 'Unknown'); ?></div>
            <div><strong>Status:</strong> <?php echo esc_html($sub->status); ?></div>
            <div><strong>Start:</strong> <?php echo esc_html($sub->start_date); ?></div>
            <div><strong>End:</strong> <?php echo esc_html($sub->end_date); ?></div>
            <div><strong>Connections:</strong> <?php echo (int)($sub->connections ?? 1); ?></div>
        </div>
    </div>
</div>

<div style="margin-top:14px; display:grid; grid-template-columns: 1fr; gap:14px;">
    <div style="background:var(--tv-surface); border:1px solid var(--tv-border); border-radius:14px; padding:14px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
            <h3 style="margin:0; font-size:14px; font-weight:900;">Lifetime Value</h3>
            <div style="font-weight:900; font-size:16px;">
                $<?php echo esc_html(number_format((float)$ltv, 2)); ?>
            </div>
        </div>
        <div style="color:var(--tv-text-muted); font-size:12px; margin-top:6px;">Completed payments sum for this user.</div>
    </div>

    <div style="background:var(--tv-surface); border:1px solid var(--tv-border); border-radius:14px; padding:14px;">
        <h3 style="margin:0 0 10px; font-size:14px; font-weight:900;">Payment History</h3>

        <?php if (!empty($history)): ?>
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Method</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $p): ?>
                        <tr>
                            <td><?php echo (int)$p->id; ?></td>
                            <td><?php echo esc_html($p->date); ?></td>
                            <td>$<?php echo esc_html(number_format((float)$p->amount, 2)); ?></td>
                            <td><?php echo esc_html($p->status); ?></td>
                            <td><?php echo esc_html($p->method); ?></td>
                            <td><?php echo esc_html($p->reference); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="font-size:13px; color:var(--tv-text-muted);">No payment records found for this user.</div>
        <?php endif; ?>
    </div>
</div>
