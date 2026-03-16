<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Trait: TV_Subscription_Manager_Admin_Classes_TV_Admin_Users_Trait_Part_03
 * Description: Restores the View/Render logic for the Users module.
 */
trait TV_Subscription_Manager_Admin_Classes_TV_Admin_Users_Trait_Part_03 {

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
        $view_mode = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'all'; 
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
                SELECT user_id, status, end_date, MAX(id) as max_id
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

        $query = "SELECT 
                    u.ID, u.user_login, u.user_email, u.user_registered, u.display_name,
                    s_details.status as sub_status, 
                    s_details.end_date as sub_end,
                    s_details.start_date as sub_start,
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

        include TV_MANAGER_PATH . 'admin/views/view-users.php';
    }

    private function render_user_manage_view() {
        if (!current_user_can('manage_options')) {
            echo '<div class="notice notice-error"><p>Insufficient permissions.</p></div>'; return;
        }
        $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $user = get_userdata($user_id);
        if (!$user) { echo '<div class="notice notice-error"><p>User not found.</p></div>'; return; }
        
        $phone = (string) get_user_meta($user_id, 'phone', true);
        $manage_section = isset($_GET['manage_section']) ? sanitize_key((string)$_GET['manage_section']) : 'profile';
        if (!in_array($manage_section, array('profile', 'subscription', 'transactions'), true)) $manage_section = 'profile';

        $plans = []; $all_subs = []; $sub = null; $payments = [];

        if ($manage_section === 'subscription') {
            $plans = $this->wpdb->get_results("SELECT * FROM $this->table_plans ORDER BY name ASC");
            $all_subs = $this->wpdb->get_results($this->wpdb->prepare("SELECT s.*, p.name as plan_name FROM $this->table_subs s LEFT JOIN $this->table_plans p ON s.plan_id = p.id WHERE s.user_id = %d ORDER BY s.id DESC", $user_id));
            
            $edit_sub_id = isset($_GET['sub_id']) ? (int)$_GET['sub_id'] : 0;
            if (!empty($all_subs)) {
                if ($edit_sub_id > 0) {
                    foreach ($all_subs as $c) { if ((int)$c->id === $edit_sub_id) { $sub = $c; break; } }
                }
                if (!$sub) $sub = $all_subs[0];
            }
        } elseif ($manage_section === 'transactions') {
            $payments = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM $this->table_payments WHERE user_id = %d ORDER BY id DESC LIMIT 50", $user_id));
        }

        include TV_MANAGER_PATH . 'admin/views/view-user-manage.php';
    }

    private function render_user_create_view() {
        if (!current_user_can('manage_options')) {
            echo '<div class="notice notice-error"><p>Insufficient permissions.</p></div>'; return;
        }
        include TV_MANAGER_PATH . 'admin/views/view-user-create.php';
    }
}