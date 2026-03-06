<?php
if ( ! defined( 'ABSPATH' ) ) exit;

trait TV_Admin_Users_Actions_Trait {

    public function handle_actions() {
    
            // --- USER PROFILE UPDATE (Manage) ---
            if (isset($_POST['tv_update_user_profile'])) {
                check_admin_referer('tv_update_user_profile_verify');
                if (!current_user_can('manage_options')) return;
    
                $user_id = intval($_POST['user_id']);
                $email   = sanitize_email($_POST['user_email']);
                $name    = sanitize_text_field($_POST['display_name']);
                $phone   = sanitize_text_field($_POST['phone']);
    
                $userdata = array(
                    'ID' => $user_id,
                    'user_email' => $email,
                    'display_name' => $name,
                );
    
                $res = wp_update_user($userdata);
                if (is_wp_error($res)) {
                    $this->show_notice('User update failed: ' . esc_html($res->get_error_message()), 'error');
                } else {
                    update_user_meta($user_id, 'phone', $phone);
    
                    $this->log_event('Admin Update User', 'Updated user ID: ' . $user_id);
    
                    if ($this->should_notify_user_from_post()) {
                        $this->notify_user_admin_action($user_id, 'admin_profile_updated', 'Your profile was updated by an administrator.', 0, true);
                    }
    
                    $this->show_notice('User profile updated.');
                }
            }
    
            // --- SUBSCRIPTION UPDATE (Manage) ---
            if (isset($_POST['tv_update_subscription'])) {
                check_admin_referer('tv_update_subscription_verify');
                if (!current_user_can('manage_options')) return;
    
                $sub_id = intval($_POST['sub_id']);
                $user_id = intval($_POST['user_id']);
                $plan_id = intval($_POST['plan_id']);
                $status  = sanitize_text_field($_POST['status']);
                $start   = sanitize_text_field($_POST['start_date']);
                $end     = sanitize_text_field($_POST['end_date']);
                $connections = isset($_POST['connections']) ? intval($_POST['connections']) : 1;
    
                // Credentials / URLs (additive; preserves existing behavior when fields omitted)
                $cred_user = isset($_POST['credential_user']) ? sanitize_text_field((string)$_POST['credential_user']) : '';
                $cred_pass = isset($_POST['credential_pass']) ? sanitize_text_field((string)$_POST['credential_pass']) : '';
                $cred_url  = isset($_POST['credential_url'])  ? esc_url_raw((string)$_POST['credential_url']) : '';
                $cred_m3u  = isset($_POST['credential_m3u'])  ? sanitize_textarea_field((string)$_POST['credential_m3u']) : '';
    
                // Panel/attachment URLs are stored as `[Panel: URL]` blocks appended to credential_m3u.
                $panel_urls_raw = isset($_POST['panel_attachment_urls']) ? (string)$_POST['panel_attachment_urls'] : '';
                $panel_urls = array();
                if ($panel_urls_raw !== '') {
                    foreach (preg_split('/\R+/', $panel_urls_raw) as $line) {
                        $line = trim((string)$line);
                        if ($line === '') continue;
                        $panel_urls[] = esc_url_raw($line);
                    }
                }
    
                $m3u_final = trim((string)$cred_m3u);
                if (!empty($panel_urls)) {
                    $suffix = '';
                    foreach ($panel_urls as $pu) {
                        if ($pu === '') continue;
                        $suffix .= "\n\n[Panel: {$pu}]";
                    }
                    $m3u_final = trim($m3u_final . $suffix);
                }
    
                $data = array(
                    'plan_id' => $plan_id,
                    'status' => $status,
                    'start_date' => $start,
                    'end_date' => $end,
                    'connections' => max(1, $connections),
                );
    
                // Only write credential fields if present in the POST to avoid unintended overwrites.
                if (array_key_exists('credential_user', $_POST)) $data['credential_user'] = $cred_user;
                if (array_key_exists('credential_pass', $_POST)) $data['credential_pass'] = $cred_pass;
                if (array_key_exists('credential_url', $_POST))  $data['credential_url']  = $cred_url;
                if (array_key_exists('credential_m3u', $_POST) || array_key_exists('panel_attachment_urls', $_POST)) {
                    $data['credential_m3u'] = $m3u_final;
                }
    
                $this->wpdb->update($this->table_subs, $data, array('id' => $sub_id));
    
                $this->log_event('Admin Update Subscription', 'Updated subscription ID: ' . $sub_id . ' (user ' . $user_id . ')');
    
                if ($this->should_notify_user_from_post()) {
                    $this->notify_user_admin_action($user_id, 'admin_subscription_updated', 'Your subscription was updated by an administrator.', $sub_id, true);
                }
    
                $this->show_notice('Subscription updated.');
            }
    
            // --- SUBSCRIPTION CREATE (Manage / Backdating Supported) ---
            if (isset($_POST['tv_create_subscription'])) {
                check_admin_referer('tv_create_subscription_verify');
                if (!current_user_can('manage_options')) return;
    
                $user_id = intval($_POST['user_id']);
                $plan_id = intval($_POST['plan_id']);
                $status  = sanitize_text_field($_POST['status']);
                $start   = sanitize_text_field($_POST['start_date']);
                $end     = sanitize_text_field($_POST['end_date']);
                $connections = isset($_POST['connections']) ? intval($_POST['connections']) : 1;
    
                $this->wpdb->insert($this->table_subs, array(
                    'user_id' => $user_id,
                    'plan_id' => $plan_id,
                    'start_date' => $start,
                    'end_date' => $end,
                    'status' => $status,
                    'connections' => max(1, $connections),
                ));
    
                $new_id = (int)$this->wpdb->insert_id;
    
                $this->log_event('Admin Create Subscription', 'Created subscription ID: ' . $new_id . ' (user ' . $user_id . ')');
    
                if ($this->should_notify_user_from_post()) {
                    $this->notify_user_admin_action($user_id, 'admin_subscription_created', 'A subscription was created for you by an administrator.', $new_id, true);
                }
    
                $this->show_notice('Subscription created.');
            }
    
            // --- MANUAL USER CREATE (Admin) ---
            if (isset($_POST['tv_create_user'])) {
                check_admin_referer('tv_create_user_verify');
                if (!current_user_can('manage_options')) return;
    
                $email   = sanitize_email($_POST['user_email']);
                $login   = sanitize_user($_POST['user_login'], true);
                $name    = sanitize_text_field($_POST['display_name']);
                $phone   = sanitize_text_field($_POST['phone']);
                $pass    = !empty($_POST['user_pass']) ? (string)$_POST['user_pass'] : wp_generate_password(12, true);
    
                $user_id = wp_create_user($login, $pass, $email);
                if (is_wp_error($user_id)) {
                    $this->show_notice('User creation failed: ' . esc_html($user_id->get_error_message()), 'error');
                } else {
                    wp_update_user(array('ID' => $user_id, 'display_name' => $name));
                    update_user_meta($user_id, 'phone', $phone);
    
                    $this->log_event('Admin Create User', 'Created user ID: ' . $user_id);
    
                    if ($this->should_notify_user_from_post()) {
                        $this->notify_user_admin_action($user_id, 'admin_user_created', 'An account was created for you. You can now log in using your email.', 0, true);
                    }
    
                    $this->show_notice('User created. Generated password: <code>' . esc_html($pass) . '</code>');
                }
            }
    
            // 3. Bulk Actions (Subscribers)
            if (isset($_POST['bulk_action']) && $_POST['bulk_action'] != '-1' && isset($_POST['sub_ids'])) {
                check_admin_referer('bulk_action_verify');
                $ids = array_map('intval', $_POST['sub_ids']);
                $action = $_POST['bulk_action'];
                
                if(!empty($ids)) {
                    $ids_sql = implode(',', $ids);
                    if ($action == 'delete') {
                        // Server-side enforcement: bulk delete verification.
                        if (!$this->tv_require_delete_verification_or_notice()) {
                            return;
                        }
                        // [UPGRADE] Soft delete (Recycle Bin) instead of hard delete
                        $deleted = 0;
                        foreach ($ids as $sid) {
                            if ($this->recycle_bin_soft_delete('subscription', $this->table_subs, (int)$sid, 'id')) {
                                $deleted++;
                            }
                        }
                        $this->show_notice($deleted . " subscriptions moved to Recycle Bin.");
                    } elseif ($action == 'activate') {
                        $this->wpdb->query("UPDATE $this->table_subs SET status = 'active' WHERE id IN ($ids_sql)");
                        $this->show_notice(count($ids) . " subscriptions activated.");
                    } elseif ($action == 'pending') {
                        $this->wpdb->query("UPDATE $this->table_subs SET status = 'pending' WHERE id IN ($ids_sql)");
                        $this->show_notice(count($ids) . " subscriptions set to pending.");
                    }
                    $this->log_event('Bulk Action', "Action: $action on " . count($ids) . " items.");
                }
            }
    
            // GET Actions
            if (isset($_GET['action'])) {
                if ($_GET['action'] == 'activate_sub' && isset($_GET['sub_id'])) {
                    check_admin_referer('activate_sub_' . $_GET['sub_id']);
                    $this->wpdb->update($this->table_subs, array('status' => 'active'), array('id' => intval($_GET['sub_id'])));
                    $this->log_event('Activate Subscription', "Activated sub ID: " . $_GET['sub_id']);
                    $this->show_notice("Subscription activated.");
                }
                if ($_GET['action'] == 'deactivate_sub' && isset($_GET['sub_id'])) {
                    check_admin_referer('deactivate_sub_' . $_GET['sub_id']);
                    $this->wpdb->update($this->table_subs, array('status' => 'pending'), array('id' => intval($_GET['sub_id'])));
                    $this->log_event('Deactivate Subscription', "Deactivated sub ID: " . $_GET['sub_id']);
                    $this->show_notice("Subscription paused.");
                }
    
                if ($_GET['action'] == 'soft_delete_sub' && isset($_GET['sub_id'])) {
                    check_admin_referer('soft_delete_sub_' . $_GET['sub_id']);
                    // Server-side enforcement: delete verification cannot be bypassed via direct URL.
                    if (!$this->tv_require_delete_verification_or_notice()) {
                        return;
                    }
                    $sid = (int)$_GET['sub_id'];
                    if ($this->recycle_bin_soft_delete('subscription', $this->table_subs, $sid, 'id')) {
                        $this->log_event('Soft Delete Subscription', "Moved subscription ID {$sid} to Recycle Bin");
                        $this->show_notice('Subscription moved to Recycle Bin.');
                    } else {
                        $this->show_notice('Unable to delete this subscription.', 'error');
                    }
                }
            }
        }

    public function handle_csv_export() {
            if (!current_user_can('manage_options')) return;
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="subscribers_export_' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, array('ID', 'Username', 'Email', 'Plan', 'Start Date', 'End Date', 'Status'));
            
            $subs = $this->wpdb->get_results("
                SELECT s.id, u.user_login, u.user_email, p.name as plan_name, s.start_date, s.end_date, s.status
                FROM $this->table_subs s
                LEFT JOIN {$this->wpdb->users} u ON s.user_id = u.ID
                LEFT JOIN $this->table_plans p ON s.plan_id = p.id
            ");
            
            foreach ($subs as $row) {
                fputcsv($output, (array)$row);
            }
            fclose($output);
            exit;
        }

}
