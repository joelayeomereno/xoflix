<?php
if (!defined('ABSPATH')) { exit; }

/**
 * File: tv-subscription-manager/includes/services/class-tv-domain-subscriptions-service.php
 * Path: /tv-subscription-manager/includes/services/class-tv-domain-subscriptions-service.php
 */
class TV_Domain_Subscriptions_Service {

    /** @var wpdb */
    private $wpdb;
    /** @var string */
    private $table_subs;
    /** @var string */
    private $table_plans;
    /** @var TV_Domain_Audit_Service */
    private $audit;
    /** @var TV_Domain_Notifications_Service */
    private $notify;

    public function __construct($wpdb = null, $audit = null, $notify = null) {
        $this->wpdb       = ($wpdb instanceof wpdb) ? $wpdb : $GLOBALS['wpdb'];
        $this->table_subs  = $this->wpdb->prefix . 'tv_subscriptions';
        $this->table_plans = $this->wpdb->prefix . 'tv_plans';
        $this->audit  = $audit instanceof TV_Domain_Audit_Service ? $audit : new TV_Domain_Audit_Service($this->wpdb);
        $this->notify = $notify instanceof TV_Domain_Notifications_Service ? $notify : new TV_Domain_Notifications_Service();
    }

    /**
     * Owns subscription state transitions and date calculations.
     *
     * Activates subscription and optionally stores credentials.
     * Handles Extend (Active) vs Renew (Expired) vs New logic.
     */
    public function activate_subscription(int $subscription_id, array $creds = [], bool $notify = true) : array {
        TV_Domain_Contract::assert_positive_int($subscription_id, 'subscription_id');

        $sub = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_subs} WHERE id = %d", $subscription_id));
        if (!$sub) {
            return array('ok' => false, 'error' => 'Subscription not found');
        }

        $plan = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_plans} WHERE id = %d", (int)$sub->plan_id));

        // 1. Resolve Duration (Days to Add)
        $purchased_months = 0;
        if (class_exists('TV_Subscription_Meta')) {
            $purchased_months = (int) TV_Subscription_Meta::get_months((int)$subscription_id);
        }
        // Fallback for legacy
        if ($purchased_months <= 0 && isset($sub->duration_months)) {
            $purchased_months = max(0, (int) $sub->duration_months);
        }

        $resolved_days = 0;
        if ($purchased_months > 0 && $plan) {
            $cycle_days    = TV_Domain_Contract::normalize_plan_duration_days((int)$plan->duration_days);
            $resolved_days = max(1, (int)$cycle_days) * $purchased_months;
        }
        
        // Safety Fallback if no meta found (e.g. manual admin activation without meta)
        if ($resolved_days <= 0) {
            $resolved_days = $plan ? TV_Domain_Contract::normalize_plan_duration_days((int)$plan->duration_days) : 30;
        }

        // 2. Date Calculation Engine (Extend vs Renew vs New)
        $current_end_ts = !empty($sub->end_date) && $sub->end_date !== '0000-00-00 00:00:00' ? strtotime($sub->end_date) : 0;
        $now = time();
        
        // Definition of "Active Extension": Subscription is marked active AND has not yet expired.
        // In this case, we ADD time to the existing end date.
        $is_extension = ($sub->status === 'active' && $current_end_ts > $now);

        if ($is_extension) {
            // EXTEND: Keep original start, push end date forward
            $start                 = $sub->start_date; // Preserve history
            $base_calculation_date = $current_end_ts;
            $action_type           = 'Extended';
        } else {
            // RENEW / NEW: Start fresh from NOW.
            $start                 = current_time('mysql');
            $base_calculation_date = strtotime($start);
            $action_type           = 'Renewed/Activated';
        }

        $end = date('Y-m-d H:i:s', strtotime('+' . (int)$resolved_days . ' days', $base_calculation_date));

        $update = array(
            'status'     => 'active',
            'start_date' => $start,
            'end_date'   => $end,
        );

        // Only update credentials if provided (don't wipe existing ones on extension)
        if (!empty($creds)) {
            if (isset($creds['user'])) $update['credential_user'] = sanitize_text_field($creds['user']);
            if (isset($creds['pass'])) $update['credential_pass'] = sanitize_text_field($creds['pass']);
            if (isset($creds['url']))  $update['credential_url']  = esc_url_raw($creds['url']);
            if (isset($creds['m3u']))  $update['credential_m3u']  = sanitize_textarea_field($creds['m3u']);
        }

        $this->wpdb->update($this->table_subs, $update, array('id' => $subscription_id));

        // FIX: Immediately invalidate the frontend subscription cache for this user.
        // The dashboard caches active subscriptions for up to 5 minutes via a transient
        // (key: streamos_subs_<user_id>). Without clearing it here, the user's frontend
        // subscription tab could still show the pre-fulfillment "pending" state for up
        // to 5 minutes — making it appear as if the subscription was never activated.
        delete_transient('streamos_subs_' . (int)$sub->user_id);

        do_action('tv_subscription_activated', (int)$sub->user_id, $creds);

        if ($notify) {
            $msg = ($is_extension) 
                ? "Your subscription has been extended by {$resolved_days} days."
                : "Your subscription is now active.";
                
            $this->notify->notify_admin_action((int)$sub->user_id, 'payment_approved', $msg, (int)$subscription_id, true);
        }

        $this->audit->log_event(
            (int)get_current_user_id(),
            'Subscription ' . $action_type,
            "Subscription ID: {$subscription_id} {$action_type}. Added {$resolved_days} days. New expiry: {$end}."
        );

        return array(
            'ok'              => true,
            'subscription_id' => (int)$subscription_id,
            'user_id'         => (int)$sub->user_id,
            'start_date'      => $start,
            'end_date'        => $end,
        );
    }

    /**
     * Bulk status set.
     */
    public function set_subscription_status_bulk(array $sub_ids, string $status) : int {
        $status  = sanitize_text_field($status);
        TV_Domain_Contract::assert_allowed_subscription_status($status);

        $sub_ids = array_values(array_filter(array_map('intval', $sub_ids), function($v) { return $v > 0; }));
        if (empty($sub_ids)) return 0;

        $placeholders = implode(',', array_fill(0, count($sub_ids), '%d'));
        $sql    = "UPDATE {$this->table_subs} SET status=%s WHERE id IN ({$placeholders})";
        $params = array_merge(array($status), $sub_ids);
        $affected = (int)$this->wpdb->query($this->wpdb->prepare($sql, $params));

        $this->audit->log_event((int)get_current_user_id(), 'Bulk Action', 'Action: set_status(' . $status . ') on ' . count($sub_ids) . ' subscriptions.');

        return $affected;
    }
}