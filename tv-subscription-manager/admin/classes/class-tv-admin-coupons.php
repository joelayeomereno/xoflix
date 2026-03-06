<?php
if (!defined('ABSPATH')) { exit; }

class TV_Admin_Coupons extends TV_Admin_Base {

    public function handle_actions() {
        // Add Coupon
        if (isset($_POST['submit_coupon'])) {
            check_admin_referer('add_coupon_verify');
            $this->wpdb->insert($this->table_coupons, array(
                'code' => strtoupper(sanitize_text_field($_POST['coupon_code'])),
                'type' => sanitize_text_field($_POST['coupon_type']),
                'amount' => floatval($_POST['coupon_amount']),
                'expiry_date' => sanitize_text_field($_POST['coupon_expiry']),
                'usage_limit' => intval($_POST['coupon_limit']),
                'usage_count' => 0
            ));
            $this->log_event('Create Coupon', "Created: " . $_POST['coupon_code']);
            $this->show_notice("Coupon created successfully.");
        }

        // Delete Coupon (GET)
        if (isset($_GET['action']) && $_GET['action'] == 'delete_coupon' && isset($_GET['id'])) {
            check_admin_referer('delete_coupon_' . $_GET['id']);

            // Server-side enforcement: delete verification cannot be bypassed via direct URL.
            if (!$this->tv_require_delete_verification_or_notice()) {
                return;
            }

            $id = intval($_GET['id']);
            
            // Check success
            if ($this->recycle_bin_soft_delete('coupon', $this->table_coupons, (int)$id, 'id')) {
                $this->log_event('Delete Coupon', "Soft-deleted coupon ID: " . $id);
                $this->show_notice("Coupon deleted.");
            } else {
                $this->show_notice("Failed to delete coupon. Recycle bin error.", 'error');
            }
        }
    }

    public function render() {
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 10;
        $offset = ($paged - 1) * $per_page;

        $coupons = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM $this->table_coupons ORDER BY id DESC LIMIT %d, %d", $offset, $per_page));
        $total_coupons = $this->wpdb->get_var("SELECT COUNT(*) FROM $this->table_coupons");
        $total_pages = ceil($total_coupons / $per_page);

        include TV_MANAGER_PATH . 'admin/views/view-coupons.php';
    }
}