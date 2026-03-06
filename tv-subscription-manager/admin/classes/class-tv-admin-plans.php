<?php
if (!defined('ABSPATH')) { exit; }

class TV_Admin_Plans extends TV_Admin_Base {

    public function handle_actions() {
        // 1. Save global WhatsApp number
        if (isset($_POST['save_whatsapp_number'])) {
            check_admin_referer('tv_save_whatsapp_number');
            $num = isset($_POST['tv_support_whatsapp']) ? sanitize_text_field(wp_unslash($_POST['tv_support_whatsapp'])) : '';
            update_option('tv_support_whatsapp', $num);
            $this->log_event('Update WhatsApp Number', 'Updated global WhatsApp trial number.');
            wp_redirect(add_query_arg(['page' => 'tv-subs-manager', 'tab' => 'plans', 'msg' => 'whatsapp_saved', '_t' => time()], admin_url('admin.php')));
            exit;
        }

        // 2. Create or Update Plan
        if (isset($_POST['submit_plan'])) {
            check_admin_referer('add_plan_verify');
            
            // [FIX] Self-Healing Database: Ensure columns exist before saving
            $this->ensure_plan_columns();

            // A. Robust Tier Parsing
            $tiers = [];
            if (isset($_POST['tier_qty']) && is_array($_POST['tier_qty'])) {
                $raw_qtys = $_POST['tier_qty'];
                $raw_discs = isset($_POST['tier_discount']) ? $_POST['tier_discount'] : [];
                
                foreach ($raw_qtys as $i => $q) {
                    $qty_val = intval($q);
                    $disc_val_raw = isset($raw_discs[$i]) ? $raw_discs[$i] : 0;
                    $disc_val_raw = str_replace(',', '.', (string)$disc_val_raw);
                    $disc_val = floatval($disc_val_raw);

                    if ($qty_val > 0 && $disc_val > 0) {
                        $tiers[] = ['months' => $qty_val, 'percent' => $disc_val];
                    }
                }
            }

            // B. Strict Price Handling
            $raw_price = isset($_POST['plan_price']) ? sanitize_text_field(wp_unslash($_POST['plan_price'])) : '0';
            $price = floatval(str_replace(',', '.', $raw_price));

            // C. Prepare Data Payload
            $data = array(
                'name' => sanitize_text_field(wp_unslash($_POST['plan_name'])),
                'price' => $price,
                'duration_days' => intval($_POST['plan_duration']),
                'allow_multi_connections' => isset($_POST['multi_conn']) ? 1 : 0,
                'discount_tiers' => !empty($tiers) ? json_encode($tiers) : null,
                'description' => wp_kses_post(wp_unslash($_POST['plan_desc'])),
                // NEW: Subscription Class & Display Order
                'category' => isset($_POST['plan_category']) ? sanitize_text_field($_POST['plan_category']) : 'standard',
                'display_order' => isset($_POST['display_order']) ? intval($_POST['display_order']) : 0,
            );

            $formats = array('%s', '%f', '%d', '%d', '%s', '%s', '%s', '%d');

            // D. Execute DB Operation
            if (!empty($_POST['plan_id'])) {
                $plan_id = intval($_POST['plan_id']);
                
                $result = $this->wpdb->update(
                    $this->table_plans, 
                    $data, 
                    array('id' => $plan_id), 
                    $formats, 
                    array('%d')
                );
                
                if ($result === false) {
                    $db_error = base64_encode($this->wpdb->last_error);
                    wp_redirect(add_query_arg(['page' => 'tv-subs-manager', 'tab' => 'plans', 'action' => 'edit', 'id' => $plan_id, 'msg' => 'error', 'err' => $db_error], admin_url('admin.php')));
                    exit;
                } else {
                    $this->log_event('Update Plan', "Updated plan ID: " . $plan_id);
                    $this->clear_system_caches();
                    wp_redirect(add_query_arg(['page' => 'tv-subs-manager', 'tab' => 'plans', 'action' => 'edit', 'id' => $plan_id, 'msg' => 'updated', '_t' => time()], admin_url('admin.php')));
                    exit;
                }

            } else {
                $result = $this->wpdb->insert(
                    $this->table_plans, 
                    $data,
                    $formats
                );
                
                if ($result === false) {
                    $db_error = base64_encode($this->wpdb->last_error);
                    wp_redirect(add_query_arg(['page' => 'tv-subs-manager', 'tab' => 'plans', 'msg' => 'error', 'err' => $db_error], admin_url('admin.php')));
                    exit;
                } else {
                    $this->log_event('Create Plan', "Created: " . $data['name']);
                    $this->clear_system_caches();
                    wp_redirect(add_query_arg(['page' => 'tv-subs-manager', 'tab' => 'plans', 'msg' => 'created', '_t' => time()], admin_url('admin.php')));
                    exit;
                }
            }
        }

        // 3. Delete Plan
        if (isset($_GET['action']) && $_GET['action'] == 'delete_plan' && isset($_GET['id'])) {
            check_admin_referer('delete_plan_' . $_GET['id']);

            if (!$this->tv_require_delete_verification_or_notice()) {
                return;
            }
            $id = intval($_GET['id']);
            
            if ($this->recycle_bin_soft_delete('plan', $this->table_plans, (int)$id, 'id')) {
                $this->log_event('Delete Plan', "Soft-deleted plan ID: " . $id);
                $this->clear_system_caches();
                wp_redirect(add_query_arg(['page' => 'tv-subs-manager', 'tab' => 'plans', 'msg' => 'deleted', '_t' => time()], admin_url('admin.php')));
                exit;
            } else {
                wp_redirect(add_query_arg(['page' => 'tv-subs-manager', 'tab' => 'plans', 'msg' => 'error', 'err' => base64_encode('Delete failed.')], admin_url('admin.php')));
                exit;
            }
        }
    }

    /**
     * AJAX: Handle Drag & Drop Reordering
     */
    public function ajax_update_plan_order() {
        // Verify User
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        check_ajax_referer('tv_plan_sort_nonce');

        $order = isset($_POST['order']) ? $_POST['order'] : [];
        if (empty($order) || !is_array($order)) {
            wp_send_json_error(['message' => 'No data received']);
        }

        global $wpdb;
        $count = 0;

        foreach ($order as $index => $plan_id) {
            $plan_id = intval($plan_id);
            if ($plan_id > 0) {
                // Update display_order based on array index
                $wpdb->update(
                    $this->table_plans,
                    ['display_order' => $index],
                    ['id' => $plan_id],
                    ['%d'],
                    ['%d']
                );
                $count++;
            }
        }

        $this->clear_system_caches();
        wp_send_json_success(['message' => "Updated $count plans"]);
    }

    /**
     * Helper: Check and create columns just-in-time if missing.
     */
    private function ensure_plan_columns() {
        global $wpdb;
        $table = $this->table_plans;
        
        // Check for discount_tiers
        $row = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'discount_tiers'");
        if(empty($row)) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN discount_tiers LONGTEXT DEFAULT NULL");
        }

        // Check for category
        $row = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'category'");
        if(empty($row)) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN category VARCHAR(50) DEFAULT 'standard' AFTER name");
        }

        // Check for display_order
        $row = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'display_order'");
        if(empty($row)) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN display_order INT(11) DEFAULT 0 AFTER price");
        }
    }

    private function clear_system_caches() {
        wp_cache_flush();
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_streamos_plans%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_streamos_plans%'");
        if (function_exists('rocket_clean_domain')) { rocket_clean_domain(); }
        if (function_exists('w3tc_flush_all')) { w3tc_flush_all(); }
        if (class_exists('autoptimizeCache')) { autoptimizeCache::clearall(); }
        if (class_exists('LiteSpeed_Cache_API')) { LiteSpeed_Cache_API::purge_all(); }
        if (function_exists('wp_cache_clear_cache')) { wp_cache_clear_cache(); }
    }

    public function render() {
        if (isset($_GET['msg'])) {
            if ($_GET['msg'] === 'updated') $this->show_notice('Plan updated & Caches Purged.');
            if ($_GET['msg'] === 'created') $this->show_notice('Plan created & Caches Purged.');
            if ($_GET['msg'] === 'deleted') $this->show_notice('Plan deleted.');
            if ($_GET['msg'] === 'whatsapp_saved') $this->show_notice('WhatsApp number updated.');
            if ($_GET['msg'] === 'error') {
                $err = isset($_GET['err']) ? base64_decode($_GET['err']) : 'Unknown DB Error';
                $this->show_notice('Error: ' . esc_html($err), 'error');
            }
        }

        $edit_plan = null;
        if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
            $edit_plan = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM $this->table_plans WHERE id = %d", intval($_GET['id'])));
        }

        // Fetch All Plans sorted by Order then Price
        $plans = $this->wpdb->get_results("SELECT * FROM $this->table_plans ORDER BY display_order ASC, price ASC");
        
        include TV_MANAGER_PATH . 'admin/views/view-plans.php';
    }
}