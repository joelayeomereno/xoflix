<?php
if (!defined('ABSPATH')) { exit; }

/**
 * File: tv-subscription-manager/includes/services/class-tv-domain-audit-service.php
 * Path: /tv-subscription-manager/includes/services/class-tv-domain-audit-service.php
 */
class TV_Domain_Audit_Service {

    /** @var wpdb */
    private $wpdb;
    /** @var string */
    private $table_logs;

    public function __construct($wpdb = null) {
        global $wpdb;
        // Note: keep $wpdb parameter for API stability; fall back to the global wpdb.
        $this->wpdb = $wpdb instanceof wpdb ? $wpdb : $GLOBALS['wpdb'];
        $this->table_logs = $this->wpdb->prefix . 'tv_activity_logs';
    }

    /**
     * Owns activity logging.
     */
    public function log_event(int $user_id, string $action, string $details = '', string $ip = '') : void {
        $user_id = (int)$user_id;
        if ($user_id <= 0) {
            $user_id = (int)get_current_user_id();
        }

        $ip = (string)$ip;
        if ($ip === '') {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '0.0.0.0';
        }

        // Be conservative: don't attempt insert if table doesn't exist.
        $exists = $this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $this->table_logs));
        if ($exists !== $this->table_logs) {
            return;
        }

        $this->wpdb->insert($this->table_logs, array(
            'user_id' => $user_id,
            'action' => $action,
            'details' => $details,
            'ip_address' => $ip,
            'date' => current_time('mysql'),
        ));
    }
}

// Module alias to match full location path (non-breaking).
if (!class_exists('Tv_Subscription_Manager_Includes_Services_Class_Tv_Domain_Audit_Service', false)) {
    class_alias('TV_Domain_Audit_Service', 'Tv_Subscription_Manager_Includes_Services_Class_Tv_Domain_Audit_Service');
}
