<?php
if (!defined('ABSPATH')) { exit; }

trait TV_Admin_Users_Trait_Export {

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
