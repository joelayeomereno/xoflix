<?php
/**
 * FILE PATH: tv-subscription-manager/admin/views/view-subscribers.php
 *
 * Complete replacement.
 * - display_name as primary row text, email as secondary.
 * - 3 icon-only action buttons:
 *     1. ?? Impersonate   opens in NEW browser tab (target="_blank")
 *     2. ?? Manage
 *     3. ?? Delete Subscription (soft)
 * - Windowed pagination (prev / 1 2 3 / next) always shown when > 1 page.
 */
if (!defined('ABSPATH')) { exit; }

// Safety defaults passed from render_users_hub_view()
if (!isset($items))       $items       = [];
if (!isset($total_items)) $total_items = 0;
if (!isset($total_pages)) $total_pages = 1;
if (!isset($paged))       $paged       = 1;
if (!isset($search))      $search      = '';
?>

<div class="tv-page-header">
    <div>
        <h1>Subscribers</h1>
        <p>All accounts with at least one subscription record.</p>
    </div>
    <div>
        <form method="get" style="display:flex; gap:8px; align-items:center;">
            <input type="hidden" name="page" value="tv-subs-manager">
            <input type="hidden" name="tab"  value="users">
            <input type="hidden" name="view" value="subscribers">
            <div style="position:relative;">
                <span class="dashicons dashicons-search"
                      style="position:absolute; left:12px; top:13px; font-size:16px; width:16px; height:16px; color:var(--tv-text-muted);"></span>
                <input type="text" name="s" class="tv-input"
                       style="padding-left:38px; min-width:220px;"
                       placeholder="Search subscribers "
                       value="<?php echo esc_attr($search); ?>">
            </div>
            <button type="submit" class="tv-btn tv-btn-secondary">Search</button>
        </form>
    </div>
</div>

<!-- View switcher -->
<div style="display:flex; gap:4px; background:var(--tv-surface-active); border:1px solid var(--tv-border); border-radius:12px; padding:5px; margin-bottom:20px; width:fit-content;">
    <a href="<?php echo esc_url(admin_url('admin.php?page=tv-subs-manager&tab=users&view=all')); ?>"
       class="tv-btn tv-btn-sm tv-btn-secondary">All Users</a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=tv-subscribers')); ?>"
       class="tv-btn tv-btn-sm tv-btn-primary">Subscribers History</a>
</div>

<div class="tv-card">

    <div class="tv-card-header">
        <h3>Total Records: <?php echo number_format((int)$total_items); ?></h3>
    </div>

    <div class="tv-table-container">
        <table class="tv-table">
            <thead>
                <tr>
                    <th width="30%">Subscriber</th>
                    <th width="18%">Plan</th>
                    <th width="12%">Status</th>
                    <th width="22%">Dates</th>
                    <th width="18%" align="right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)): foreach ($items as $s):

                    $display_name = !empty($s->display_name) ? $s->display_name : $s->user_login;
                    $initial      = strtoupper(substr($s->user_login, 0, 1));
                    $status_val   = strtolower((string)$s->status);

                    // Build action URLs
                    $imp_url = wp_nonce_url(
                        admin_url('admin.php?page=tv-subs-manager&tab=users&action=start_impersonation&user_id=' . (int)$s->user_id),
                        'start_impersonation_' . (int)$s->user_id
                    );
                    $manage_url  = admin_url('admin.php?page=tv-subs-manager&tab=users&action=manage&user_id=' . (int)$s->user_id);
                    $del_sub_url = wp_nonce_url(
                        admin_url('admin.php?page=tv-subs-manager&tab=users&action=soft_delete_sub&sub_id=' . (int)$s->id),
                        'soft_delete_sub_' . (int)$s->id
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
                                    <?php echo esc_html($s->user_email); ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <strong style="color:var(--tv-primary);">
                            <?php echo esc_html(!empty($s->plan_name) ? $s->plan_name : 'No Plan'); ?>
                        </strong>
                    </td>
                    <td>
                        <span class="tv-badge <?php echo esc_attr($status_val); ?>">
                            <?php echo esc_html(ucfirst($status_val)); ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-size:13px; font-weight:600;">
                            <?php echo !empty($s->start_date) ? date('M j, Y', strtotime($s->start_date)) : ' '; ?>
                        </div>
                        <div style="font-size:11px; color:var(--tv-text-muted);">
                            to <?php echo !empty($s->end_date) ? date('M j, Y', strtotime($s->end_date)) : ' '; ?>
                        </div>
                    </td>
                    <td align="right">
                        <div style="display:flex; justify-content:flex-end; gap:6px; align-items:center;">

                            <!-- 1: Impersonate   opens in NEW browser tab -->
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

                            <!-- 3: Delete Subscription -->
                            <a href="<?php echo esc_url($del_sub_url); ?>"
                               class="tv-btn tv-btn-sm tv-btn-danger"
                               data-tv-delete="1"
                               title="Delete Subscription">
                                <span class="dashicons dashicons-trash"
                                      style="font-size:14px; width:14px; height:14px;"></span>
                            </a>

                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding:50px; color:var(--tv-text-muted);">
                        No subscription records found.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- --- PAGINATION   always renders when > 1 page --- -->
    <?php
    // Pagination always present. $total_pages comes from the controller.
    // Even if only 1 page, we show the record count summary below.
    ?>
    <div style="padding:14px 20px; border-top:1px solid var(--tv-border); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <div style="font-size:12px; color:var(--tv-text-muted);">
            Showing page <?php echo (int)$paged; ?> of <?php echo (int)$total_pages; ?>
            &nbsp;&bull;&nbsp;
            <?php echo number_format((int)$total_items); ?> record<?php echo $total_items !== 1 ? 's' : ''; ?>
        </div>

        <?php if ($total_pages > 1):
            $base_url = admin_url('admin.php?page=tv-subs-manager&tab=users&view=subscribers&s=' . urlencode($search));
        ?>
        <div style="display:flex; gap:4px; flex-wrap:wrap; align-items:center;">
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

</div>