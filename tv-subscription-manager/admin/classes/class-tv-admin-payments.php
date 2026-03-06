<?php
if (!defined('ABSPATH')) { exit; }

class TV_Admin_Payments extends TV_Admin_Base {

    private function get_transaction_summaries_gmt_plus_one() : array {
        $tz = new DateTimeZone('+01:00');
        $now = new DateTime('now', $tz);
        $from_24h       = (clone $now)->sub(new DateInterval('PT24H'));
        $dow            = (int)$now->format('N');
        $week_start     = (clone $now)->setTime(0,0,0)->sub(new DateInterval('P' . ($dow-1) . 'D'));
        $week_end       = (clone $week_start)->add(new DateInterval('P7D'));
        $last_week_start= (clone $week_start)->sub(new DateInterval('P7D'));
        $last_week_end  = (clone $week_start);
        $month_start    = (clone $now)->modify('first day of this month')->setTime(0,0,0);
        $month_end      = (clone $month_start)->modify('first day of next month');
        $last_month_start = (clone $month_start)->modify('first day of last month');
        $last_month_end = (clone $month_start);
        $year_start     = (clone $now)->setDate((int)$now->format('Y'), 1, 1)->setTime(0,0,0);
        $year_end       = (clone $year_start)->modify('+1 year');

        $periods = [
            'today'      => ['label' => 'Today (24h)',  'from' => $from_24h,        'to' => $now],
            'this_week'  => ['label' => 'This Week',    'from' => $week_start,      'to' => $week_end],
            'last_week'  => ['label' => 'Last Week',    'from' => $last_week_start, 'to' => $last_week_end],
            'this_month' => ['label' => 'This Month',   'from' => $month_start,     'to' => $month_end],
            'last_month' => ['label' => 'Last Month',   'from' => $last_month_start,'to' => $last_month_end],
            'this_year'  => ['label' => 'This Year',    'from' => $year_start,      'to' => $year_end],
        ];

        $out = [];
        foreach ($periods as $key => $p) {
            $out[$key] = [
                'label'  => $p['label'],
                'totals' => $this->sum_payments_by_currency_between($p['from'], $p['to'])
            ];
        }
        return $out;
    }

    private function sum_payments_by_currency_between(DateTime $from, DateTime $to) : array {
        $statuses = ['COMPLETED','APPROVED'];
        $placeholders = implode(',', array_fill(0, count($statuses), '%s'));
        $sql  = "SELECT 
                    SUM(CASE WHEN amount_usd IS NOT NULL AND amount_usd > 0 THEN amount_usd WHEN currency = 'USD' THEN amount ELSE 0 END) as total_usd,
                    SUM(CASE WHEN amount_ngn IS NOT NULL AND amount_ngn > 0 THEN amount_ngn WHEN currency = 'NGN' THEN amount ELSE 0 END) as total_ngn
                 FROM {$this->table_payments}
                 WHERE UPPER(status) IN ($placeholders)
                   AND date >= %s AND date < %s";
        $params = array_merge($statuses, [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]);
        $row = $this->wpdb->get_row($this->wpdb->prepare($sql, $params));
        return ['USD' => (float)($row->total_usd ?? 0), 'NGN' => (float)($row->total_ngn ?? 0)];
    }

    public function handle_actions() {
        if (isset($_POST['confirm_approval_credentials'])) {
            check_admin_referer('approve_creds_verify');
            $pid   = intval($_POST['payment_id']);
            $creds = [
                'user' => sanitize_text_field($_POST['cred_user']),
                'pass' => sanitize_text_field($_POST['cred_pass']),
                'url'  => esc_url_raw($_POST['cred_url']),
                'm3u'  => sanitize_textarea_field($_POST['cred_m3u'])
            ];
            $notify  = $this->should_notify_user_from_post();
            $service = class_exists('TV_Domain_Payments_Service') ? new TV_Domain_Payments_Service($this->wpdb) : null;
            if ($service) {
                $res = $service->fulfill_payment($pid, $creds, (bool)$notify);
                if (!empty($res['ok'])) {
                    $this->show_notice("Subscription fulfilled successfully.");
                } else {
                    $this->show_notice("Fulfillment failed: " . esc_html($res['error'] ?? 'Unknown error'), 'error');
                }
            } else {
                $this->process_payment_approval($pid, $creds);
            }
        }

        if (isset($_GET['action']) && $_GET['action'] == 'approve_pay' && isset($_GET['pid'])) {
            check_admin_referer('approve_pay_' . $_GET['pid']);
            $pid     = intval($_GET['pid']);
            $service = class_exists('TV_Domain_Payments_Service') ? new TV_Domain_Payments_Service($this->wpdb) : null;
            if ($service) {
                $service->approve_payment($pid, [], true);
                $this->show_notice("Payment approved. You can now fulfill.");
            } else {
                $raw_txn_id = 'XPAY-' . date('Ymd') . '-' . $pid . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
                $this->wpdb->update($this->table_payments, ['status' => 'APPROVED', 'transaction_id' => $raw_txn_id], ['id' => $pid]);
                $this->show_notice("Payment approved.");
            }
        }

        if (isset($_POST['tv_reject_payment_with_reason'])) {
            check_admin_referer('tv_reject_payment_with_reason_verify');
            $pid        = intval($_POST['payment_id']);
            $reason_key = isset($_POST['reason_key']) ? sanitize_text_field($_POST['reason_key']) : '';
            $notify     = $this->should_notify_user_from_post('notify_user');
            $service    = class_exists('TV_Domain_Payments_Service') ? new TV_Domain_Payments_Service($this->wpdb) : null;
            if ($service) {
                $service->reject_payment($pid, (bool)$notify, $reason_key);
            } else {
                $this->process_rejection($pid);
            }
            $this->show_notice("Payment rejected." . ($notify ? " User notified." : ""), 'error');
        }

        if (isset($_GET['action']) && $_GET['action'] == 'reject_pay' && isset($_GET['pid'])) {
            check_admin_referer('reject_pay_' . $_GET['pid']);
            $pid     = intval($_GET['pid']);
            $service = class_exists('TV_Domain_Payments_Service') ? new TV_Domain_Payments_Service($this->wpdb) : null;
            if ($service) {
                $service->reject_payment($pid, false);
                $this->show_notice("Payment rejected.", 'error');
            } else {
                $this->process_rejection($pid);
            }
        }
    }

    private function process_payment_approval($pid, $creds) {
        $legacy_txn_id = 'XPAY-' . date('Ymd') . '-' . $pid . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $this->wpdb->update($this->table_payments, ['status' => 'APPROVED', 'transaction_id' => $legacy_txn_id], ['id' => $pid]);
        $pay = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_payments} WHERE id = %d", $pid));
        if ($pay) {
            $sub = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_subs} WHERE id = %d", $pay->subscription_id));
            if ($sub) {
                $plan  = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_plans} WHERE id = %d", $sub->plan_id));
                $start = current_time('mysql');
                $days  = 0;
                $months_meta = 0;
                if (class_exists('TV_Subscription_Meta')) {
                    $months_meta = (int) TV_Subscription_Meta::get_months((int)$sub->id);
                }
                if ($months_meta > 0 && $plan) { $days = max(1, (int)$plan->duration_days) * $months_meta; }
                if ($days <= 0) { $days = $plan ? max(1, (int)$plan->duration_days) : 30; }
                $end = date('Y-m-d H:i:s', strtotime($start . ' + ' . $days . ' days'));
                $this->wpdb->update($this->table_subs, [
                    'status'          => 'active',
                    'start_date'      => $start,
                    'end_date'        => $end,
                    'credential_user' => $creds['user'],
                    'credential_pass' => $creds['pass'],
                    'credential_url'  => $creds['url'],
                    'credential_m3u'  => $creds['m3u'],
                ], ['id' => $pay->subscription_id]);
                do_action('tv_subscription_activated', $sub->user_id, $creds);
                if ($this->should_notify_user_from_post()) {
                    $this->notify_user_admin_action($sub->user_id, 'payment_approved', 'Your payment was approved and your subscription is now active.', (int)$sub->id, true);
                }
                $this->log_event('Payment Approved', "Transaction ID: $pid approved.");
            }
        }
        $this->show_notice("Subscription activated successfully.");
    }

    private function process_rejection($pid) {
        $this->wpdb->update($this->table_payments, ['status' => 'REJECTED'], ['id' => $pid]);
        $pay = $this->wpdb->get_row($this->wpdb->prepare("SELECT subscription_id FROM {$this->table_payments} WHERE id = %d", $pid));
        if ($pay) {
            $this->wpdb->update($this->table_subs, ['status' => 'inactive'], ['id' => $pay->subscription_id]);
        }
        $this->log_event('Payment Rejected', "Transaction ID: $pid rejected.");
        $this->show_notice("Payment rejected.", 'error');
    }

    public function render() {
        $search_term   = isset($_GET['s'])      ? sanitize_text_field($_GET['s'])      : '';
        $filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'successful';
        $date_range    = isset($_GET['date_range']) ? sanitize_text_field($_GET['date_range']) : '';
        $date_from     = isset($_GET['date_from'])  ? sanitize_text_field($_GET['date_from'])  : '';
        $date_to       = isset($_GET['date_to'])    ? sanitize_text_field($_GET['date_to'])    : '';

        $query_append = "";
        $args = [];

        if (!empty($search_term)) {
            $query_append .= " AND (u.user_login LIKE %s OR p.transaction_id LIKE %s OR p.method LIKE %s OR u.display_name LIKE %s)";
            $term = '%' . $this->wpdb->esc_like($search_term) . '%';
            $args[] = $term; $args[] = $term; $args[] = $term; $args[] = $term;
        }

        if (!empty($filter_status)) {
            if ($filter_status === 'pending') {
                $query_append .= " AND p.status IN ('pending', 'AWAITING_PROOF', 'IN_PROGRESS', 'PENDING_ADMIN_REVIEW')";
            } elseif ($filter_status === 'failed') {
                $query_append .= " AND p.status IN ('REJECTED', 'CANCELLED', 'failed')";
            } elseif ($filter_status === 'successful') {
                $query_append .= " AND p.status IN ('APPROVED', 'COMPLETED') AND p.method != 'System Grant'";
            } elseif ($filter_status === 'needs_action') {
                $query_append .= " AND p.status IN ('PENDING', 'AWAITING_PROOF', 'IN_PROGRESS', 'PENDING_ADMIN_REVIEW')";
            } elseif ($filter_status !== 'all') {
                $query_append .= " AND p.status = %s";
                $args[] = strtoupper($filter_status);
            }
        }

        // Date range filters
        if (!empty($date_range) && is_numeric($date_range)) {
            $days_ago = intval($date_range);
            $query_append .= " AND p.date >= %s";
            $args[] = date('Y-m-d H:i:s', strtotime("-{$days_ago} days"));
        } elseif (!empty($date_from) || !empty($date_to)) {
            if (!empty($date_from)) {
                $query_append .= " AND p.date >= %s";
                $args[] = sanitize_text_field($date_from) . ' 00:00:00';
            }
            if (!empty($date_to)) {
                $query_append .= " AND p.date <= %s";
                $args[] = sanitize_text_field($date_to) . ' 23:59:59';
            }
        }

        $sql = "SELECT p.*, u.user_login, u.user_email, u.display_name, s.plan_id, s.start_date, s.end_date, s.connections,
                       s.credential_user, s.credential_pass, s.credential_url, s.credential_m3u,
                       pl.name as plan_name, pl.price as base_plan_price, pl.duration_days as base_duration
                FROM {$this->table_payments} p
                LEFT JOIN {$this->wpdb->users} u ON p.user_id = u.ID
                LEFT JOIN {$this->table_subs} s ON p.subscription_id = s.id
                LEFT JOIN {$this->table_plans} pl ON s.plan_id = pl.id
                WHERE 1=1 {$query_append}
                ORDER BY p.date DESC";

        $paged      = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page   = 20;
        $offset     = ($paged - 1) * $per_page;

        $count_sql   = "SELECT COUNT(*) FROM ({$sql}) as count_table";
        $total_items = !empty($args)
            ? $this->wpdb->get_var($this->wpdb->prepare($count_sql, $args))
            : $this->wpdb->get_var($count_sql);
        $total_pages = ceil($total_items / $per_page);

        $sum_sql = "SELECT 
                SUM(CASE WHEN amount_usd IS NOT NULL AND amount_usd > 0 THEN amount_usd WHEN currency = 'USD' THEN amount ELSE 0 END) as total_usd,
                SUM(CASE WHEN amount_ngn IS NOT NULL AND amount_ngn > 0 THEN amount_ngn WHEN currency = 'NGN' THEN amount ELSE 0 END) as total_ngn
             FROM {$this->table_payments} p
             LEFT JOIN {$this->wpdb->users} u ON p.user_id = u.ID
             WHERE 1=1 {$query_append}";
        $sum_row = !empty($args) ? $this->wpdb->get_row($this->wpdb->prepare($sum_sql, $args)) : $this->wpdb->get_row($sum_sql);

        $sql   .= " LIMIT %d, %d";
        $args[] = $offset;
        $args[] = $per_page;

        $payments    = !empty($args)
            ? $this->wpdb->get_results($this->wpdb->prepare($sql, $args))
            : $this->wpdb->get_results($sql);

        $tx_summaries = $this->get_transaction_summaries_gmt_plus_one();

        if (!empty($search_term) || !empty($date_range) || !empty($date_from) || !empty($date_to) || ($filter_status !== 'successful' && $filter_status !== 'all')) {
            $tx_summaries = ['filtered' => [
                'label'  => 'Filtered Duration Total',
                'totals' => ['USD' => (float)($sum_row->total_usd ?? 0), 'NGN' => (float)($sum_row->total_ngn ?? 0)]
            ]];
        }

        include TV_MANAGER_PATH . 'admin/views/view-payments.php';
    }
}