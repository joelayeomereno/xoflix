<?php
if (!defined('ABSPATH')) { exit; }

class TV_Domain_Payments_Service {

    /** @var wpdb */
    private $wpdb;
    /** @var string */
    private $table_payments;
    /** @var string */
    private $table_subs;
    /** @var TV_Domain_Subscriptions_Service */
    private $subs_service;
    /** @var TV_Domain_Audit_Service */
    private $audit;

    public function __construct($wpdb = null, $subs_service = null, $audit = null) {
        $this->wpdb = ($wpdb instanceof wpdb) ? $wpdb : $GLOBALS['wpdb'];
        $this->table_payments = $this->wpdb->prefix . 'tv_payments';
        $this->table_subs = $this->wpdb->prefix . 'tv_subscriptions';
        
        $this->ensure_dependencies();

        $this->audit = $audit instanceof TV_Domain_Audit_Service ? $audit : new TV_Domain_Audit_Service($this->wpdb);
        $this->subs_service = $subs_service instanceof TV_Domain_Subscriptions_Service ? $subs_service : new TV_Domain_Subscriptions_Service($this->wpdb, $this->audit, new TV_Domain_Notifications_Service());
    }

    private function ensure_dependencies() {
        if (!class_exists('TV_Domain_Notifications_Service')) {
            $path = defined('TV_MANAGER_PATH') ? TV_MANAGER_PATH : plugin_dir_path(dirname(dirname(dirname(__FILE__))));
            $file = $path . 'includes/services/class-tv-domain-notifications-service.php';
            if (file_exists($file)) require_once $file;
        }
    }

    /**
     * Owns payment approval and side effects.
     */
    public function approve_payment(int $payment_id, array $creds = [], bool $notify = true) : array {
        TV_Domain_Contract::assert_positive_int($payment_id, 'payment_id');

        // 1) Fetch the payment first so we can validate it before writing.
        //    Updating before reading means we may silently update a non-existent row.
        $pay = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_payments} WHERE id = %d", $payment_id));
        if (!$pay) {
            return array('ok' => false, 'error' => 'Payment not found');
        }

        $sub_id = (int)$pay->subscription_id;
        if ($sub_id <= 0) {
            return array('ok' => false, 'error' => 'Payment missing subscription_id');
        }

        // 2) Mark payment approved AND assign a unique permanent XPAY- transaction ID.
        //    Format: XPAY-{YYYYMMDD}-{PAYID}-{6 random hex chars}
        //    This prefix is exclusive to admin-approved transactions and
        //    visually/programmatically distinguishes them from TMP- (pending)
        //    and any rejected records which carry no permanent ID.
        $new_txn_id = 'XPAY-' . date('Ymd') . '-' . $payment_id . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $this->wpdb->update(
            $this->table_payments, 
            array(
                'status' => 'APPROVED',
                'transaction_id' => $new_txn_id
            ), 
            array('id' => $payment_id)
        );

        // 3) Optional acknowledgement email — reuse the injected subs_service's shared
        //    notifications instance instead of creating a new one each time.
        if ($notify && !empty($pay->user_id)) {
            $msg = 'Your payment has been approved. Your subscription will be fulfilled shortly.';
            if (class_exists('TV_Domain_Notifications_Service')) {
                (new TV_Domain_Notifications_Service())->notify_admin_action((int)$pay->user_id, 'payment_approved', $msg, (int)$sub_id, true);
            }
        }

        // 4) Audit log
        $this->audit->log_event((int)get_current_user_id(), 'Payment Approved', 'Transaction ID: ' . (int)$payment_id . ' approved. Permanent ID: ' . $new_txn_id);

        return array('ok' => true, 'payment_id' => (int)$payment_id);
    }

    public function fulfill_payment(int $payment_id, array $creds = [], bool $notify = true) : array {
        TV_Domain_Contract::assert_positive_int($payment_id, 'payment_id');

        $pay = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_payments} WHERE id = %d", $payment_id));
        if (!$pay) {
            return array('ok' => false, 'error' => 'Payment not found');
        }

        if (strtoupper((string)$pay->status) !== 'APPROVED') {
            return array('ok' => false, 'error' => 'Payment must be approved before fulfillment');
        }

        $sub_id = (int)$pay->subscription_id;
        if ($sub_id <= 0) {
            return array('ok' => false, 'error' => 'Payment missing subscription_id');
        }

        $result = $this->subs_service->activate_subscription($sub_id, $creds, $notify);
        if (!empty($result['ok'])) {
            $this->wpdb->update($this->table_payments, array('status' => 'COMPLETED'), array('id' => $payment_id));

            // activate_subscription() already returns user_id — no extra DB query needed.
            $uid = isset($result['user_id']) ? (int)$result['user_id'] : (int)$pay->user_id;
            $this->audit->log_event((int)get_current_user_id(), 'Payment Fulfilled', 'Transaction ID: ' . (int)$payment_id . ' fulfilled. Credentials provisioned for User ID: ' . $uid);
        } else {
            $this->audit->log_event((int)get_current_user_id(), 'Fulfillment Failed', 'Transaction ID: ' . (int)$payment_id . ' failed. Error: ' . ($result['error'] ?? 'Unknown'));
        }
        return $result;
    }

    public function reject_payment(int $payment_id, bool $notify = false, string $reason_key = '') : array {
        TV_Domain_Contract::assert_positive_int($payment_id, 'payment_id');

        $this->wpdb->update($this->table_payments, array('status' => 'REJECTED'), array('id' => $payment_id));

        $pay = $this->wpdb->get_row($this->wpdb->prepare("SELECT subscription_id, user_id FROM {$this->table_payments} WHERE id = %d", $payment_id));
        if ($pay && !empty($pay->subscription_id)) {
            $this->wpdb->update($this->table_subs, array('status' => 'inactive'), array('id' => (int)$pay->subscription_id));
        }

        if ($notify && $pay && !empty($pay->user_id) && class_exists('TV_Domain_Notifications_Service')) {
            $reasons = $this->get_rejection_reasons();
            $reason_txt = (!empty($reason_key) && isset($reasons[$reason_key])) ? $reasons[$reason_key] : 'Payment proof was not accepted.';
            (new TV_Domain_Notifications_Service())->notify_admin_action((int)$pay->user_id, 'payment_rejected', 'Your payment was rejected: ' . $reason_txt, (int)($pay->subscription_id ?? 0), true);
        }

        $this->audit->log_event((int)get_current_user_id(), 'Payment Rejected', 'Transaction ID: ' . (int)$payment_id . ' rejected.');

        return array('ok' => true, 'payment_id' => (int)$payment_id);
    }

    public function get_rejection_reasons() : array {
        return array(
            'unclear_proof' => 'Proof image is unclear / unreadable',
            'wrong_amount' => 'Amount paid does not match the required total',
            'wrong_account' => 'Payment was sent to the wrong account',
            'duplicate_or_used' => 'Proof appears to be duplicate or previously used',
            'invalid_reference' => 'Transaction reference is missing or invalid',
        );
    }

    public function approve_payments_bulk(array $payment_ids, bool $notify = true) : int {
        $payment_ids = array_values(array_filter(array_map('intval', $payment_ids), function($v) { return $v > 0; }));
        if (empty($payment_ids)) return 0;

        $count = 0;
        foreach ($payment_ids as $pid) {
            $res = $this->approve_payment((int)$pid, array(), $notify);
            if (!empty($res['ok'])) {
                $count++;
            }
        }
        return $count;
    }
}