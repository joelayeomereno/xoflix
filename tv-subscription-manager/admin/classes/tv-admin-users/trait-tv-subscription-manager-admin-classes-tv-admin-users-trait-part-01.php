<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Trait: TV_Subscription_Manager_Admin_Classes_TV_Admin_Users_Trait_Part_01
 * Path: /tv-subscription-manager/admin/classes/tv-admin-users/trait-tv-subscription-manager-admin-classes-tv-admin-users-trait-part-01.php
 * Hardened for XOFLIX TV v3: Fixed UI Reverting, Added Deletion Interceptors, and Password Persistence
 */
trait TV_Subscription_Manager_Admin_Classes_TV_Admin_Users_Trait_Part_01 {


    public function handle_actions() {

        // --- USER PROFILE UPDATE (Manage) ---
        if (isset($_POST['tv_update_user_profile'])) {
            check_admin_referer('tv_update_user_profile_verify');
            if (!current_user_can('manage_options')) return;

            $user_id    = intval($_POST['user_id']);
            $email      = sanitize_email($_POST['user_email']);
            $name       = sanitize_text_field($_POST['display_name']);
            $first_name = sanitize_text_field($_POST['first_name']);
            $last_name  = sanitize_text_field($_POST['last_name']);
            $phone      = sanitize_text_field($_POST['phone']);
            $country    = sanitize_text_field($_POST['billing_country']);
            $notes      = sanitize_textarea_field($_POST['admin_notes']);
            $new_pass   = !empty($_POST['user_pass']) ? (string)$_POST['user_pass'] : '';

            $userdata = array(
                'ID'           => $user_id,
                'user_email'   => $email,
                'display_name' => $name,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
            );

            // Logic: Only update password if string is provided
            if (!empty($new_pass)) {
                $userdata['user_pass'] = $new_pass;
            }

            $res = wp_update_user($userdata);
            
            if (is_wp_error($res)) {
                $this->show_notice('User update failed: ' . esc_html($res->get_error_message()), 'error');
            } else {
                update_user_meta($user_id, 'phone', $phone);
                update_user_meta($user_id, 'billing_country', $country);
                update_user_meta($user_id, 'tv_admin_notes', $notes);

                $log_msg = 'Updated user ID: ' . $user_id;
                if (!empty($new_pass)) { $log_msg .= ' (Password was reset)'; }
                $this->log_event('Admin Update User', $log_msg);

                if ($this->should_notify_user_from_post()) {
                    $notify_msg = 'Your profile was updated by an administrator.';
                    if (!empty($new_pass)) { $notify_msg .= ' Your account password has been reset.'; }
                    $this->notify_user_admin_action($user_id, 'admin_profile_updated', $notify_msg, 0, true);
                }

                $this->show_notice('User profile updated successfully.');
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

            $cred_user = isset($_POST['credential_user']) ? sanitize_text_field((string)$_POST['credential_user']) : '';
            $cred_pass = isset($_POST['credential_pass']) ? sanitize_text_field((string)$_POST['credential_pass']) : '';
            $cred_url  = isset($_POST['credential_url'])  ? esc_url_raw((string)$_POST['credential_url']) : '';
            $cred_m3u  = isset($_POST['credential_m3u'])  ? sanitize_textarea_field((string)$_POST['credential_m3u']) : '';

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

        // --- SUBSCRIPTION CREATE ---
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
            $this->log_event('Admin Create Subscription', 'Created subscription ID: ' . $new_id);

            if ($this->should_notify_user_from_post()) {
                $this->notify_user_admin_action($user_id, 'admin_subscription_created', 'A subscription was created for you by an administrator.', $new_id, true);
            }

            $this->show_notice('Subscription created.');
        }

        // --- MANUAL USER CREATE ---
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
                    $this->notify_user_admin_action($user_id, 'admin_user_created', 'An account was created for you.', 0, true);
                }
                $this->show_notice('User created. Password: <code>' . esc_html($pass) . '</code>');
            }
        }

        // Bulk Actions
        if (isset($_POST['bulk_action']) && $_POST['bulk_action'] != '-1' && isset($_POST['sub_ids'])) {
            check_admin_referer('bulk_action_verify');
            $ids = array_map('intval', $_POST['sub_ids']);
            $action = $_POST['bulk_action'];
            
            if(!empty($ids)) {
                $ids_sql = implode(',', $ids);
                if ($action == 'delete') {
                    if (!$this->tv_require_delete_verification_or_notice()) { return; }
                    $deleted = 0;
                    foreach ($ids as $sid) {
                        if ($this->recycle_bin_soft_delete('subscription', $this->table_subs, (int)$sid, 'id')) { $deleted++; }
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
                if (!$this->tv_require_delete_verification_or_notice()) { return; }
                $sid = (int)$_GET['sub_id'];
                if ($this->recycle_bin_soft_delete('subscription', $this->table_subs, $sid, 'id')) {
                    $this->log_event('Soft Delete Subscription', "Moved subscription ID {$sid} to Recycle Bin");
                    $this->show_notice('Subscription moved to Recycle Bin.');
                } else {
                    $this->show_notice('Unable to delete this subscription.', 'error');
                }
            }

            // SECURE USER DELETION (WordPress User Delete)
            if ($_GET['action'] == 'delete_user' && isset($_GET['user_id'])) {
                $target_id = intval($_GET['user_id']);
                check_admin_referer('delete_user_' . $target_id);
                
                // Nuclear Verification (4-digit code check)
                if (!$this->tv_require_delete_verification_or_notice()) { return; }

                if ($target_id === (int)get_current_user_id()) {
                    $this->show_notice("Security Violation: You cannot delete your own account while logged in.", 'error');
                    return;
                }

                require_once(ABSPATH . 'wp-admin/includes/user.php');
                $user_info = get_userdata($target_id);
                $this->log_event('Hard Delete User', "Deleted User ID: $target_id (" . ($user_info ? $user_info->user_login : 'Unknown') . ")");
                
                // Reassign all user-generated content to nobody (0)
                $deleted = wp_delete_user($target_id);
                
                if ($deleted) {
                    $redirect = add_query_arg(['page' => 'tv-subs-manager', 'tab' => 'users', 'msg' => 'user_deleted'], $this->admin_base_url());
                    wp_redirect($redirect);
                    exit;
                } else {
                    $this->show_notice("WordPress Error: Unable to delete user.", 'error');
                }
            }
        }
    }

    public function render() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        if ($action === 'detail') {
            $this->render_subscriber_detail_view();
        } elseif ($action === 'manage' && isset($_GET['user_id'])) {
            $this->render_user_manage_view();
        } elseif ($action === 'create_user') {
            $this->render_user_create_view();
        } else {
            $this->render_users_hub_view();
        }
    }

    private function render_subscriber_detail_view() {
        $sub_id = intval($_GET['id']);
        $sub = $this->wpdb->get_row($this->wpdb->prepare("SELECT s.*, p.name as plan_name, u.user_login, u.user_email, u.display_name FROM $this->table_subs s LEFT JOIN $this->table_plans p ON s.plan_id = p.id LEFT JOIN {$this->wpdb->users} u ON s.user_id = u.ID WHERE s.id = %d", $sub_id));
        if(!$sub) { echo '<div class="notice notice-error"><p>Record not found.</p></div>'; return; }
        $ltv = $this->wpdb->get_var($this->wpdb->prepare("SELECT SUM(amount) FROM $this->table_payments WHERE user_id = %d AND UPPER(status) IN ('COMPLETED','APPROVED')", $sub->user_id));
        $history = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM $this->table_payments WHERE user_id = %d ORDER BY date DESC", $sub->user_id));
        if (file_exists(TV_MANAGER_PATH . 'admin/views/view-subscriber-detail.php')) {
            include TV_MANAGER_PATH . 'admin/views/view-subscriber-detail.php';
        } else {
            $_GET['user_id'] = (int)$sub->user_id;
            $this->render_user_manage_view();
        }
    }

    private function render_users_hub_view() {
        // --- 1. RESOLVE VIEW CONTEXT ---
        $view_mode = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'all'; 
        if (isset($_GET['page']) && $_GET['page'] === 'tv-subscribers') {
            $view_mode = 'subscribers';
        }

        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20; 
        $offset = ($paged - 1) * $per_page;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
        $filter_plan = isset($_GET['plan_id']) ? intval($_GET['plan_id']) : 0;
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'registered';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

        $where_clauses = ["1=1"];
        $args = [];

        if (!empty($search)) {
            $where_clauses[] = "(u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)";
            $args[] = '%' . $search . '%';
            $args[] = '%' . $search . '%';
            $args[] = '%' . $search . '%';
        }

        $sql_base = "
            FROM {$this->wpdb->users} u
            LEFT JOIN (
                SELECT user_id, status, start_date, end_date, MAX(id) as max_id
                FROM $this->table_subs
                GROUP BY user_id
            ) s_latest ON u.ID = s_latest.user_id
            LEFT JOIN $this->table_subs s_details ON s_latest.max_id = s_details.id
            LEFT JOIN $this->table_plans p ON s_details.plan_id = p.id
        ";

        if ($filter_plan > 0) $where_clauses[] = 's_details.plan_id = ' . $filter_plan;

        if ($filter_status === 'subscribers') $where_clauses[] = "s_latest.user_id IS NOT NULL";
        elseif ($filter_status === 'active') $where_clauses[] = "s_details.status = 'active' AND s_details.end_date > NOW()";
        elseif ($filter_status === 'expired') $where_clauses[] = "(s_details.status = 'active' AND s_details.end_date <= NOW())";
        elseif ($filter_status === 'never') $where_clauses[] = "s_latest.user_id IS NULL";
        elseif ($filter_status === 'new') $where_clauses[] = "u.user_registered >= DATE_SUB(NOW(), INTERVAL 7 DAY)";

        $where_sql = implode(' AND ', $where_clauses);
        $order_sql = "u.user_registered DESC"; 
        if ($orderby === 'login') $order_sql = "u.user_login $order";
        if ($orderby === 'email') $order_sql = "u.user_email $order";
        if ($orderby === 'registered') $order_sql = "u.user_registered $order";
        if ($orderby === 'status') $order_sql = "s_details.status $order";
        if ($orderby === 'plan') $order_sql = "p.name $order";

        // --- 2. EXECUTE DATA QUERY ---
        $query = "SELECT 
                    u.ID, u.user_login, u.user_email, u.user_registered, u.display_name,
                    s_details.id as id,
                    s_details.user_id as user_id,
                    s_details.status as status, 
                    s_details.end_date as end_date,
                    s_details.start_date as start_date,
                    s_details.plan_id as plan_id,
                    p.name as plan_name,
                    (SELECT COUNT(*) FROM $this->table_subs WHERE user_id = u.ID) as sub_count,
                    (SELECT SUM(amount) FROM $this->table_payments WHERE user_id = u.ID AND UPPER(status) IN ('COMPLETED','APPROVED')) as total_spent
                  $sql_base
                  WHERE $where_sql
                  ORDER BY $order_sql
                  LIMIT %d, %d";

        $query_args = array_merge($args, [$offset, $per_page]);
        $users = $this->wpdb->get_results($this->wpdb->prepare($query, $query_args));

        $total_users_count = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->users}");
        $total_subscribers_count = $this->wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $this->table_subs");
        $active_now_count = $this->wpdb->get_var("SELECT COUNT(*) FROM $this->table_subs WHERE status = 'active' AND end_date > NOW()");

        $count_query = "SELECT COUNT(*) $sql_base WHERE $where_sql";
        $total_items = (!empty($args)) ? $this->wpdb->get_var($this->wpdb->prepare($count_query, $args)) : $this->wpdb->get_var($count_query);
        $total_pages = ceil($total_items / $per_page);
        $plans_filter = $this->wpdb->get_results("SELECT id, name FROM $this->table_plans ORDER BY name ASC");

        // --- 3. MAPPING & ROUTING ---
        $items = $users; // Map result to standard items variable

        if ($view_mode === 'subscribers') {
            include TV_MANAGER_PATH . 'admin/views/view-subscribers.php';
        } else {
            include TV_MANAGER_PATH . 'admin/views/view-users.php';
        }
    }

    private function render_user_manage_view() {
        if (!current_user_can('manage_options')) { echo '<div class="notice notice-error"><p>Insufficient permissions.</p></div>'; return; }
        $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $user = get_userdata($user_id);
        if (!$user) { echo '<div class="notice notice-error"><p>User not found.</p></div>'; return; }
        $phone = (string) get_user_meta($user_id, 'phone', true);
        $manage_section = isset($_GET['manage_section']) ? sanitize_key((string)$_GET['manage_section']) : 'profile';
        if (!in_array($manage_section, array('profile', 'subscription', 'transactions'), true)) { $manage_section = 'profile'; }
        $plans = array(); $all_subs = array(); $sub = null; $payments = array();
        if ($manage_section === 'subscription') {
            $plans = $this->wpdb->get_results("SELECT * FROM $this->table_plans ORDER BY name ASC");
            $all_subs = $this->wpdb->get_results($this->wpdb->prepare("SELECT s.*, p.name as plan_name FROM $this->table_subs s LEFT JOIN $this->table_plans p ON s.plan_id = p.id WHERE s.user_id = %d ORDER BY s.id DESC", $user_id));
            $edit_sub_id = isset($_GET['sub_id']) ? (int)$_GET['sub_id'] : 0;
            if (!empty($all_subs)) {
                if ($edit_sub_id > 0) { foreach ($all_subs as $candidate) { if ((int)$candidate->id === $edit_sub_id) { $sub = $candidate; break; } } }
                if (!$sub) { $sub = $all_subs[0]; }
            }
        } elseif ($manage_section === 'transactions') {
            $payments = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM $this->table_payments WHERE user_id = %d ORDER BY id DESC LIMIT 50", $user_id));
        }
        include TV_MANAGER_PATH . 'admin/views/view-user-manage.php';
    }

    private function render_user_create_view() {
        if (!current_user_can('manage_options')) { echo '<div class="notice notice-error"><p>Insufficient permissions.</p></div>'; return; }
        include TV_MANAGER_PATH . 'admin/views/view-user-create.php';
    }
}