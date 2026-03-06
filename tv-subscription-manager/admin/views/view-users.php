<?php
/**
 * FILE PATH: tv-subscription-manager/admin/views/view-users.php
 *
 * Complete replacement.
 * - display_name shown as primary line, user_email as secondary.
 * - 3 icon-only action buttons: Impersonate (new tab), Manage, Delete.
 * - Pagination (windowed, 5 pages around current).
 * - Status filter tabs, plan filter, search all preserved.
 */
if (!defined('ABSPATH')) { exit; }

// Safety defaults
if (!isset($users))                   $users = [];
if (!isset($total_users_count))       $total_users_count = 0;
if (!isset($active_now_count))        $active_now_count = 0;
if (!isset($total_subscribers_count)) $total_subscribers_count = 0;
if (!isset($total_items))             $total_items = 0;
if (!isset($total_pages))             $total_pages = 1;
if (!isset($paged))                   $paged = 1;
if (!isset($search))                  $search = '';
if (!isset($filter_status))           $filter_status = 'all';
if (!isset($filter_plan))             $filter_plan = 0;
if (!isset($plans_filter))            $plans_filter = [];
?>

<!-- --- STAT CARDS --- -->
<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:24px;">

    <div class="tv-card" style="padding:18px 20px; margin:0; display:flex; align-items:center; gap:14px;">
        <div style="width:44px; height:44px; flex-shrink:0; border-radius:12px; background:var(--tv-surface-active); display:flex; align-items:center; justify-content:center; color:var(--tv-text-muted);">
            <span class="dashicons dashicons-admin-users" style="font-size:20px; width:20px; height:20px;"></span>
        </div>
        <div>
            <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--tv-text-muted); letter-spacing:.04em;">Total Users</div>
            <div style="font-size:22px; font-weight:900; color:var(--tv-text);"><?php echo number_format((int)$total_users_count); ?></div>
        </div>
    </div>

    <div class="tv-card" style="padding:18px 20px; margin:0; display:flex; align-items:center; gap:14px;">
        <div style="width:44px; height:44px; flex-shrink:0; border-radius:12px; background:var(--tv-success-bg); display:flex; align-items:center; justify-content:center; color:var(--tv-success);">
            <span class="dashicons dashicons-yes-alt" style="font-size:20px; width:20px; height:20px;"></span>
        </div>
        <div>
            <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--tv-text-muted); letter-spacing:.04em;">Active Subs</div>
            <div style="font-size:22px; font-weight:900; color:var(--tv-text);"><?php echo number_format((int)$active_now_count); ?></div>
        </div>
    </div>

    <div class="tv-card" style="padding:18px 20px; margin:0; display:flex; align-items:center; gap:14px;">
        <div style="width:44px; height:44px; flex-shrink:0; border-radius:12px; background:var(--tv-primary-light); display:flex; align-items:center; justify-content:center; color:var(--tv-primary);">
            <span class="dashicons dashicons-star-filled" style="font-size:20px; width:20px; height:20px;"></span>
        </div>
        <div>
            <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--tv-text-muted); letter-spacing:.04em;">Total Subscribers</div>
            <div style="font-size:22px; font-weight:900; color:var(--tv-text);"><?php echo number_format((int)$total_subscribers_count); ?></div>
        </div>
    </div>

</div>

<!-- --- TABLE CARD --- -->
<div class="tv-card">

    <!-- Filters toolbar -->
    <div style="padding:16px 20px; border-bottom:1px solid var(--tv-border);">
        <form method="get" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin:0;">
            <input type="hidden" name="page" value="tv-subs-manager">

            <!-- Search -->
            <div style="flex:1; min-width:200px; position:relative;">
                <span class="dashicons dashicons-search"
                      style="position:absolute; left:12px; top:13px; font-size:16px; width:16px; height:16px; color:var(--tv-text-muted);"></span>
                <input type="text" name="s" class="tv-input"
                       style="padding-left:38px;"
                       placeholder="Search name, email…"
                       value="<?php echo esc_attr($search); ?>">
            </div>

            <!-- Plan filter -->
            <div style="min-width:160px;">
                <select name="plan_id" class="tv-input" onchange="this.form.submit()">
                    <option value="0">All Plans</option>
                    <?php foreach ((array)$plans_filter as $pl): ?>
                    <option value="<?php echo (int)$pl->id; ?>" <?php selected((int)$filter_plan, (int)$pl->id); ?>>
                        <?php echo esc_html($pl->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status pill toggle -->
            <div class="tv-toggle-group">
                <?php
                $statuses = [
                    'all'         => 'All',
                    'subscribers' => 'Subscribers',
                    'active'      => 'Active',
                    'expired'     => 'Expired',
                    'never'       => 'Never Subbed',
                    'new'         => 'New (7d)',
                ];
                foreach ($statuses as $s_key => $s_label):
                ?>
                <button type="submit" name="status" value="<?php echo esc_attr($s_key); ?>"
                        class="tv-toggle-btn <?php echo ($filter_status === $s_key) ? 'active' : ''; ?>">
                    <?php echo esc_html($s_label); ?>
                </button>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="tv-btn tv-btn-primary" style="height:44px;">Search</button>
        </form>
    </div>

    <!-- Table -->
    <div class="tv-table-container">
        <table class="tv-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Status</th>
                    <th>Plan</th>
                    <th>Total Spent</th>
                    <th>Registered</th>
                    <th align="right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): foreach ($users as $u):

                    // Determine sub status label + badge class
                    $badge_class  = 'never';
                    $badge_label  = 'No Sub';

                    if ((int)$u->sub_count > 0) {
                        $badge_class = 'expired';
                        $badge_label = 'History: ' . $u->sub_count;

                        if ($u->sub_status === 'active'
                            && !empty($u->sub_end)
                            && strtotime($u->sub_end) > time()) {
                            $badge_class = 'active';
                            $badge_label = 'Active';
                        }
                    }

                    $display_name = !empty($u->display_name) ? $u->display_name : $u->user_login;
                    $initial      = strtoupper(substr($u->user_login, 0, 1));

                    // Build action URLs
                    $imp_url = wp_nonce_url(
                        admin_url('admin.php?page=tv-subs-manager&tab=users&action=start_impersonation&user_id=' . (int)$u->ID),
                        'start_impersonation_' . (int)$u->ID
                    );
                    $manage_url = admin_url('admin.php?page=tv-subs-manager&tab=users&action=manage&user_id=' . (int)$u->ID);
                    $del_url    = wp_nonce_url(
                        admin_url('admin.php?page=tv-subs-manager&tab=users&action=delete_user&user_id=' . (int)$u->ID),
                        'delete_user_' . (int)$u->ID
                    );
                ?>
                <tr>
                    <td>
                        <div class="tv-user-cell">
                            <div class="tv-avatar"><?php echo esc_html($initial); ?></div>
                            <div>
                                <div style="font-weight:700; color:var(--tv-text);">
                                    <?php echo esc_html($display_name); ?>
                                </div>
                                <div style="font-size:11px; color:var(--tv-text-muted);">
                                    <?php echo esc_html($u->user_email); ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="tv-badge <?php echo esc_attr($badge_class); ?>">
                            <?php echo esc_html($badge_label); ?>
                        </span>
                    </td>
                    <td>
                        <strong><?php echo !empty($u->plan_name) ? esc_html($u->plan_name) : '—'; ?></strong>
                    </td>
                    <td>
                        <strong>$<?php echo number_format((float)$u->total_spent, 2); ?></strong>
                    </td>
                    <td>
                        <div style="font-size:12px; color:var(--tv-text-muted);">
                            <?php echo date('M d, Y', strtotime($u->user_registered)); ?>
                        </div>
                    </td>
                    <td align="right">
                        <div style="display:flex; justify-content:flex-end; gap:6px; align-items:center;">

                            <!-- 1: Impersonate (opens new tab) -->
                            <a href="<?php echo esc_url($imp_url); ?>"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="tv-btn tv-btn-sm tv-btn-secondary"
                               title="Login as this user (opens in a new tab)">
                                <span class="dashicons dashicons-visibility"
                                      style="font-size:14px; width:14px; height:14px;"></span>
                            </a>

                            <!-- 2: Manage -->
                            <a href="<?php echo esc_url($manage_url); ?>"
                               class="tv-btn tv-btn-sm tv-btn-secondary"
                               title="Manage User">
                                <span class="dashicons dashicons-admin-users"
                                      style="font-size:14px; width:14px; height:14px;"></span>
                            </a>

                            <!-- 3: Delete -->
                            <a href="<?php echo esc_url($del_url); ?>"
                               class="tv-btn tv-btn-sm tv-btn-danger"
                               data-tv-delete="1"
                               title="Delete User">
                                <span class="dashicons dashicons-trash"
                                      style="font-size:14px; width:14px; height:14px;"></span>
                            </a>

                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:50px; color:var(--tv-text-muted);">
                        No users found.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- --- PAGINATION --- -->
    <?php if ($total_pages > 1):
        $base_query = http_build_query([
            'page'     => 'tv-subs-manager',
            's'        => $search,
            'status'   => $filter_status,
            'plan_id'  => $filter_plan,
        ]);
        $base_url = admin_url('admin.php?' . $base_query);
    ?>
    <div style="padding:16px 20px; border-top:1px solid var(--tv-border); display:flex; justify-content:center; align-items:center; gap:5px; flex-wrap:wrap;">
        <?php
        if ($paged > 1) {
            echo '<a href="' . esc_url($base_url . '&paged=' . ($paged - 1)) . '" class="tv-btn tv-btn-sm tv-btn-secondary">&laquo; Prev</a>';
        }
        $start = max(1, $paged - 2);
        $end   = min($total_pages, $paged + 2);
        for ($i = $start; $i <= $end; $i++) {
            $cls = ($i === $paged) ? 'tv-btn-primary' : 'tv-btn-secondary';
            echo '<a href="' . esc_url($base_url . '&paged=' . $i) . '" class="tv-btn tv-btn-sm ' . $cls . '">' . $i . '</a>';
        }
        if ($paged < $total_pages) {
            echo '<a href="' . esc_url($base_url . '&paged=' . ($paged + 1)) . '" class="tv-btn tv-btn-sm tv-btn-secondary">Next &raquo;</a>';
        }
        ?>
    </div>
    <?php endif; ?>

</div>