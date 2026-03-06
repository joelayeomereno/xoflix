<?php
if (!defined('ABSPATH')) { exit; }

class TV_Admin_Dashboard extends TV_Admin_Base {

    /**
     * Dashboard rendering can be DB-heavy. Cache computed datasets briefly to keep admin UI snappy
     * without sacrificing freshness.
     */
    private function get_dashboard_cache_ttl() {
        /**
         * Filter: tv_manager_dashboard_cache_ttl
         * Default 30 seconds.
         */
        return (int) apply_filters( 'tv_manager_dashboard_cache_ttl', 30 );
    }

    public function render() {

        $cache_key = 'tv_manager_dashboard_cache_' . get_current_blog_id();
        $ttl = $this->get_dashboard_cache_ttl();

        $cached = ( $ttl > 0 ) ? get_transient( $cache_key ) : false;
        if ( is_array( $cached ) ) {
            $total_revenue     = $cached['total_revenue'];
            $active_subs       = $cached['active_subs'];
            $pending_payments  = $cached['pending_payments'];
            $total_users       = $cached['total_users'];
            $arpu              = $cached['arpu'];
            $chart_data        = $cached['chart_data'];
            $expiring_soon     = $cached['expiring_soon'];
            $logs              = $cached['logs'];
        } else {
            // Stats
            // Revenue: treat COMPLETED/APPROVED as realized.
            $total_revenue = (float) $this->wpdb->get_var("SELECT SUM(amount) FROM $this->table_payments WHERE UPPER(status) IN ('COMPLETED','APPROVED')");
            $active_subs = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM $this->table_subs WHERE status = 'active'");
            $pending_payments = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM $this->table_payments WHERE status = 'pending'");
            $total_users = (int) $this->wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $this->table_subs");
            $arpu = ($total_users > 0) ? ($total_revenue / $total_users) : 0;

            // Chart Data (Last 12 Months)
            $chart_data = $this->wpdb->get_results("
                SELECT DATE_FORMAT(date, '%Y-%m') as month_str, SUM(amount) as revenue
                FROM $this->table_payments
                WHERE UPPER(status) IN ('COMPLETED','APPROVED') AND date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY month_str
                ORDER BY month_str ASC
            ");

            // [UPGRADE] Fetch Expiring Soon (Next 3 Days)
            $expiring_soon = $this->wpdb->get_results("
                SELECT s.*, u.user_login, p.name as plan_name
                FROM $this->table_subs s
                JOIN {$this->wpdb->users} u ON s.user_id = u.ID
                JOIN $this->table_plans p ON s.plan_id = p.id
                WHERE s.status = 'active'
                AND s.end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
                ORDER BY s.end_date ASC
                LIMIT 5
            ");

            // Recent Activity Logs
            $logs = $this->wpdb->get_results("SELECT * FROM $this->table_logs ORDER BY date DESC LIMIT 8");

            if ( $ttl > 0 ) {
                set_transient( $cache_key, array(
                    'total_revenue'    => $total_revenue,
                    'active_subs'      => $active_subs,
                    'pending_payments' => $pending_payments,
                    'total_users'      => $total_users,
                    'arpu'             => $arpu,
                    'chart_data'       => $chart_data,
                    'expiring_soon'    => $expiring_soon,
                    'logs'             => $logs,
                ), $ttl );
            }
        }

        include TV_MANAGER_PATH . 'admin/views/view-dashboard.php';
    }
}
