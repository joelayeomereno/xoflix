<?php
if (!defined('ABSPATH')) { exit; }

/**
 * File: tv-subscription-manager/admin/classes/class-tv-admin-finance.php
 * Path: /tv-subscription-manager/admin/classes/class-tv-admin-finance.php
 *
 * Finance module:
 * - Weekly grouping (Mon-Sun) in GMT+1 (fixed offset +01:00)
 * - New vs Renewal categorization
 * - CSV export (audit-friendly)
 *
 * Additive: does not alter existing payments/subscription flows.
 */
class TV_Admin_Finance extends TV_Admin_Base {

    private $tz;

    public function __construct($wpdb) {
        parent::__construct($wpdb);
        // Fixed requirement: Week starts Monday (GMT+1)
        $this->tz = new DateTimeZone('+01:00');

        add_action('admin_post_tv_finance_export_csv', array($this, 'handle_finance_export_csv'));
    }

    public function handle_actions() {
        // No inline actions here currently; exports are handled via admin_post.
    }

    public function render() {
        if (!(current_user_can('manage_options') || current_user_can('manage_tv_finance'))) {
            echo '<div class="notice notice-error"><p>You do not have permission to access Finance.</p></div>';
            return;
        }

        $range = isset($_GET['range']) ? sanitize_text_field($_GET['range']) : '8w';

        // Default date range: last 8 full weeks including current week
        $now = new DateTime('now', $this->tz);
        $week_start = $this->get_week_start($now);
        $weeks_back = 8;
        if ($range === '4w') $weeks_back = 4;
        if ($range === '12w') $weeks_back = 12;

        $from = clone $week_start;
        $from->modify('-' . ($weeks_back - 1) . ' weeks');
        $to = clone $week_start;
        $to->modify('+6 days 23 hours 59 minutes 59 seconds');

        $report = $this->build_weekly_report($from, $to);

        include TV_MANAGER_PATH . 'admin/views/view-finance.php';
    }

    private function get_week_start(DateTime $dt) {
        $d = clone $dt;
        $d->setTimezone($this->tz);
        // ISO-8601: Monday=1..Sunday=7
        $iso_day = (int)$d->format('N');
        $d->setTime(0,0,0);
        if ($iso_day > 1) {
            $d->modify('-' . ($iso_day - 1) . ' days');
        }
        return $d;
    }

    private function is_money_received_status($status) {
        $s = strtoupper(trim((string)$status));
        return ($s === 'APPROVED' || $s === 'COMPLETED');
    }

    private function build_weekly_report(DateTime $from, DateTime $to) {
        // Pull rows; categorize in PHP to guarantee timezone/week logic.
        $rows = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT p.*, s.plan_id as sub_plan_id, s.start_date as sub_start_date, s.end_date as sub_end_date
             FROM $this->table_payments p
             LEFT JOIN $this->table_subs s ON p.subscription_id = s.id
             WHERE p.date >= %s AND p.date <= %s
             ORDER BY p.date ASC",
            $from->format('Y-m-d H:i:s'),
            $to->format('Y-m-d H:i:s')
        ));

        // Preload plan names for display.
        $plans = $this->wpdb->get_results("SELECT id, name FROM $this->table_plans");
        $plan_map = array();
        if ($plans) {
            foreach ($plans as $pl) $plan_map[(int)$pl->id] = $pl->name;
        }

        $weeks = array();

        // Avoid N+1 lookups by precomputing the first money-received payment id per subscription.
        $sub_ids = array();
        if (!empty($rows)) {
            foreach ($rows as $r) {
                $sid = (int)($r->subscription_id ?? 0);
                if ($sid > 0) { $sub_ids[$sid] = true; }
            }
        }
        $first_paid_map = array();
        if (!empty($sub_ids)) {
            $ids = array_map('intval', array_keys($sub_ids));
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            // Note: we use MIN(id) as a stable proxy for the earliest paid payment.
            $sql_first = "SELECT subscription_id, MIN(id) AS first_id
                          FROM {$this->table_payments}
                          WHERE subscription_id IN ($placeholders)
                            AND UPPER(status) IN ('APPROVED','COMPLETED')
                          GROUP BY subscription_id";
            $first_rows = $this->wpdb->get_results($this->wpdb->prepare($sql_first, $ids));
            if (!empty($first_rows)) {
                foreach ($first_rows as $fr) {
                    $first_paid_map[(int)$fr->subscription_id] = (int)$fr->first_id;
                }
            }
        }

        foreach ($rows as $r) {
            if (!$this->is_money_received_status($r->status)) continue;

            $dt = new DateTime($r->date, new DateTimeZone('UTC'));
            $dt->setTimezone($this->tz);
            $ws = $this->get_week_start($dt);
            $key = $ws->format('Y-m-d');

            if (!isset($weeks[$key])) {
                $weeks[$key] = array(
                    'week_start' => $ws,
                    'week_end'   => (clone $ws)->modify('+6 days'),
                    'total'      => 0.0,
                    'count'      => 0,
                    'new_total'  => 0.0,
                    'new_count'  => 0,
                    'renew_total'=> 0.0,
                    'renew_count'=> 0,
                    'by_method'  => array(),
                    'by_plan'    => array(),
                );
            }

            $amount = (float)$r->amount;
            $weeks[$key]['total'] += $amount;
            $weeks[$key]['count']++;

            // New vs renewal: if this is the first money-received payment for this subscription_id.
            $sub_id = (int)($r->subscription_id ?? 0);
            if ($sub_id <= 0) {
                // No subscription_id: treat as new for reporting consistency
                $is_new = true;
            } else {
                $first_paid_id = $first_paid_map[$sub_id] ?? 0;
                $is_new = ((int)$first_paid_id === (int)$r->id);
            }

            if ($is_new) {
                $weeks[$key]['new_total'] += $amount;
                $weeks[$key]['new_count']++;
            } else {
                $weeks[$key]['renew_total'] += $amount;
                $weeks[$key]['renew_count']++;
            }

            $method = !empty($r->method) ? $r->method : 'unknown';
            if (!isset($weeks[$key]['by_method'][$method])) $weeks[$key]['by_method'][$method] = 0.0;
            $weeks[$key]['by_method'][$method] += $amount;

            $plan_id = (int)($r->sub_plan_id ?? 0);
            $plan_name = $plan_id && isset($plan_map[$plan_id]) ? $plan_map[$plan_id] : 'Unknown';
            if (!isset($weeks[$key]['by_plan'][$plan_name])) $weeks[$key]['by_plan'][$plan_name] = 0.0;
            $weeks[$key]['by_plan'][$plan_name] += $amount;
        }

        // Sort by week_start ASC
        ksort($weeks);
        return $weeks;
    }

    public function handle_finance_export_csv() {
        if (!(current_user_can('manage_options') || current_user_can('manage_tv_finance'))) {
            wp_die('Not allowed');
        }
        check_admin_referer('tv_finance_export_csv');

        $range = isset($_GET['range']) ? sanitize_text_field($_GET['range']) : '8w';

        $now = new DateTime('now', $this->tz);
        $week_start = $this->get_week_start($now);
        $weeks_back = 8;
        if ($range === '4w') $weeks_back = 4;
        if ($range === '12w') $weeks_back = 12;

        $from = clone $week_start;
        $from->modify('-' . ($weeks_back - 1) . ' weeks');
        $to = clone $week_start;
        $to->modify('+6 days 23 hours 59 minutes 59 seconds');

        $weeks = $this->build_weekly_report($from, $to);

        $filename = sanitize_file_name('tv-finance-weekly-' . $from->format('Ymd') . '-to-' . $to->format('Ymd') . '.csv');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, array(
            'week_start_gmt_plus_1',
            'week_end_gmt_plus_1',
            'total_amount',
            'total_count',
            'new_amount',
            'new_count',
            'renewal_amount',
            'renewal_count',
            'methods_breakdown',
            'plans_breakdown'
        ));

        foreach ($weeks as $w) {
            $methods = array();
            foreach ($w['by_method'] as $m => $amt) $methods[] = $m . ':' . number_format((float)$amt, 2, '.', '');
            $plans = array();
            foreach ($w['by_plan'] as $pn => $amt) $plans[] = $pn . ':' . number_format((float)$amt, 2, '.', '');

            fputcsv($out, array(
                $w['week_start']->format('Y-m-d'),
                $w['week_end']->format('Y-m-d'),
                number_format((float)$w['total'], 2, '.', ''),
                (int)$w['count'],
                number_format((float)$w['new_total'], 2, '.', ''),
                (int)$w['new_count'],
                number_format((float)$w['renew_total'], 2, '.', ''),
                (int)$w['renew_count'],
                implode(' | ', $methods),
                implode(' | ', $plans),
            ));
        }

        fclose($out);
        exit;
    }
}
