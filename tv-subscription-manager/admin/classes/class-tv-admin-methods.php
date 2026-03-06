<?php
if (!defined('ABSPATH')) { exit; }

class TV_Admin_Methods extends TV_Admin_Base {

    public function handle_actions() {
        // 1. Save/Update Method
        if (isset($_POST['save_method'])) {
            check_admin_referer('save_method_verify');
            
            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            // Handle inputs (sanitize comma-separated strings properly)
            $countries_input = isset($_POST['method_countries']) ? $_POST['method_countries'] : '';
            if (is_array($countries_input)) $countries_input = implode(',', $countries_input);

            $currencies_input = isset($_POST['method_currencies']) ? $_POST['method_currencies'] : '';
            if (is_array($currencies_input)) $currencies_input = implode(',', $currencies_input);

            $data = array(
                'name' => sanitize_text_field($_POST['method_name']),
                'slug' => sanitize_title($_POST['method_slug']),
                // Optional branding + structured bank fields
                'logo_url' => isset($_POST['method_logo_url']) ? esc_url_raw($_POST['method_logo_url']) : '',
                'bank_name' => isset($_POST['method_bank_name']) ? sanitize_text_field($_POST['method_bank_name']) : '',
                'account_name' => isset($_POST['method_account_name']) ? sanitize_text_field($_POST['method_account_name']) : '',
                'account_number' => isset($_POST['method_account_number']) ? sanitize_text_field($_POST['method_account_number']) : '',
                'countries' => sanitize_text_field($countries_input),
                'currencies' => sanitize_text_field($currencies_input),
                'instructions' => wp_kses_post($_POST['method_instructions']),
                // Link stored exactly as entered
                'link' => esc_url_raw($_POST['method_link']),
                // FORCE NORMAL BEHAVIOR (Removes support for 'iframe' or 'embedded')
                'open_behavior' => 'window', 
                // Flutterwave dynamic checkout fields
                'flutterwave_enabled'    => !empty($_POST['flutterwave_enabled']) ? 1 : 0,
                'flutterwave_secret_key' => isset($_POST['flutterwave_secret_key']) ? sanitize_text_field($_POST['flutterwave_secret_key']) : '',
                'flutterwave_public_key' => isset($_POST['flutterwave_public_key']) ? sanitize_text_field($_POST['flutterwave_public_key']) : '',
                'flutterwave_currency'   => isset($_POST['flutterwave_currency']) ? sanitize_text_field($_POST['flutterwave_currency']) : 'USD',
                'flutterwave_title'      => isset($_POST['flutterwave_title']) ? sanitize_text_field($_POST['flutterwave_title']) : '',
                'flutterwave_logo'       => isset($_POST['flutterwave_logo']) ? esc_url_raw($_POST['flutterwave_logo']) : '',
                'status' => sanitize_text_field($_POST['method_status']),
                'display_order' => intval($_POST['method_order']),
                'notes' => sanitize_textarea_field($_POST['method_notes'])
            );

            // Determine Update vs Insert
            if (!empty($_POST['method_id'])) {
                $id = intval($_POST['method_id']);
                $this->wpdb->update($this->table_methods, $data, array('id' => $id));
                $this->log_event('Update Payment Method', "Updated method ID: " . $id);
                $msg = 'updated';
            } else {
                $this->wpdb->insert($this->table_methods, $data);
                $this->log_event('Create Payment Method', "Created method: " . $data['name']);
                $msg = 'created';
            }

            $redirect_url = add_query_arg(['page' => 'tv-subs-manager', 'tab' => 'methods', 'msg' => $msg], $this->admin_base_url());
            wp_redirect($redirect_url);
            exit;
        }
        
        // 2. Delete Method
        if (isset($_GET['action']) && $_GET['action'] == 'delete_method' && isset($_GET['id'])) {
            check_admin_referer('delete_method_' . $_GET['id']);

            // Server-side enforcement: delete verification cannot be bypassed via direct URL.
            if (!$this->tv_require_delete_verification_or_notice()) {
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            $id = intval($_GET['id']);
            
            // Check result
            if ($this->recycle_bin_soft_delete('payment_method', $this->table_methods, (int)$id, 'id')) {
                $this->log_event('Delete Payment Method', "Soft-deleted method ID: " . $id);
                $redirect_url = add_query_arg(['page' => 'tv-subs-manager', 'tab' => 'methods', 'msg' => 'deleted'], $this->admin_base_url());
                wp_redirect($redirect_url);
                exit;
            } else {
                $redirect_url = add_query_arg(['page' => 'tv-subs-manager', 'tab' => 'methods', 'msg' => 'error'], $this->admin_base_url());
                wp_redirect($redirect_url);
                exit;
            }
        }
    }

    public function render() {
        if (isset($_GET['msg'])) {
            if ($_GET['msg'] === 'updated') $this->show_notice("Payment method updated successfully.");
            if ($_GET['msg'] === 'created') $this->show_notice("Payment method created successfully.");
            if ($_GET['msg'] === 'deleted') $this->show_notice("Payment method deleted.", 'warning');
            if ($_GET['msg'] === 'error') $this->show_notice("Delete failed. Please try again.", 'error');
        }

        $edit_method = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $edit_method = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM $this->table_methods WHERE id = %d", $id));
        }

        $methods = $this->wpdb->get_results("SELECT * FROM $this->table_methods ORDER BY display_order ASC");
        
        include TV_MANAGER_PATH . 'admin/views/view-payment-methods.php';
    }
}