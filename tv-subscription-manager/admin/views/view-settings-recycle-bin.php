<?php if (!defined('ABSPATH')) exit; ?>
<?php
/**
 * File: tv-subscription-manager/admin/views/view-settings-recycle-bin.php
 * Path: /tv-subscription-manager/admin/views/view-settings-recycle-bin.php
 */

$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Table may not exist on older installs.
if ($this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $this->table_recycle)) !== $this->table_recycle) {
    echo '<div class="notice notice-warning"><p>Recycle Bin table is not available yet. Please deactivate/reactivate the plugin once to apply DB patches.</p></div>';
    return;
}

$where = "WHERE status = 'deleted'";
$args = [];
if (!empty($search)) {
    $where .= " AND (entity_type LIKE %s OR entity_table LIKE %s OR CAST(entity_id AS CHAR) LIKE %s)";
    $term = '%' . $search . '%';
    $args = [$term, $term, $term];
}

$sql = "SELECT * FROM {$this->table_recycle} {$where} ORDER BY deleted_at DESC LIMIT 300";
$items = !empty($args) ? $this->wpdb->get_results($this->wpdb->prepare($sql, $args)) : $this->wpdb->get_results($sql);
?>

<div class="tv-card" style="padding:16px;">
    <div style="display:flex; gap:12px; justify-content:space-between; align-items:center; flex-wrap:wrap;">
        <div>
            <h3 style="margin:0; font-size:16px; font-weight:800;">Recycle Bin</h3>
            <div style="font-size:12px; color:var(--tv-text-muted); margin-top:4px;">
                Soft-deleted items are restorable for 7 days.
            </div>
        </div>

        <form method="get" style="display:flex; gap:8px; align-items:center;">
            <input type="hidden" name="page" value="tv-settings-general">
            <input type="hidden" name="tab" value="recycle-bin">
            <input class="tv-input" type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search type / table / ID" style="min-width:240px;">
            <button class="tv-btn tv-btn-secondary" type="submit">Search</button>
        </form>
    </div>

    <div class="tv-table-container" style="margin-top:14px;">
        <table class="tv-table">
            <thead>
                <tr>
                    <th width="15%">Type</th>
                    <th width="25%">Table</th>
                    <th width="10%">Entity ID</th>
                    <th width="20%">Deleted</th>
                    <th width="20%">Expires</th>
                    <th width="10%" style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)): foreach ($items as $it): ?>
                    <tr>
                        <td><b><?php echo esc_html($it->entity_type); ?></b></td>
                        <td style="font-family:monospace; font-size:12px;"><?php echo esc_html($it->entity_table); ?></td>
                        <td><?php echo (int)$it->entity_id; ?></td>
                        <td><?php echo esc_html($it->deleted_at); ?></td>
                        <td><?php echo esc_html($it->expires_at); ?></td>
                        <td style="text-align:right;">
                            <?php
                                $rid = (int)$it->id;
                                $restore_url = wp_nonce_url(
                                    admin_url('admin.php?page=tv-settings-general&tab=recycle-bin&tv_recycle_restore=1&rid=' . $rid),
                                    'tv_recycle_restore_' . $rid
                                );
                            ?>
                            <a class="tv-btn tv-btn-primary tv-btn-sm" href="<?php echo esc_url($restore_url); ?>" onclick="return confirm('Restore this item?')">Restore</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--tv-text-muted);">No deleted items found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>