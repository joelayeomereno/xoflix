<?php
if (!defined('ABSPATH')) { exit; }

/**
 * File: tv-subscription-manager/includes/services/class-tv-domain-notifications-service.php
 * Path: /tv-subscription-manager/includes/services/class-tv-domain-notifications-service.php
 */
class TV_Domain_Notifications_Service {

    /**
     * Wraps notification engine calls + log writes.
     */
    public function notify_admin_action(int $user_id, string $type, string $message, int $subscription_id = 0, bool $force_send = false) : void {
        $user_id = (int)$user_id;
        $subscription_id = (int)$subscription_id;

        if ($user_id <= 0) {
            return;
        }

        if (!class_exists('TV_Notification_Engine')) {
            $engine_path = defined('TV_MANAGER_PATH') ? TV_MANAGER_PATH . 'includes/class-tv-notification-engine.php' : '';
            if ($engine_path && file_exists($engine_path)) {
                require_once $engine_path;
            }
        }

        if (!class_exists('TV_Notification_Engine')) {
            return;
        }

        $sub = (object) array(
            'id' => $subscription_id,
            'user_id' => $user_id,
        );

        // Signature: send_notification($sub, $type, $message, $force_send)
        TV_Notification_Engine::send_notification($sub, $type, $message, (bool)$force_send);
    }
}

// Module alias to match full location path (non-breaking).
if (!class_exists('Tv_Subscription_Manager_Includes_Services_Class_Tv_Domain_Notifications_Service', false)) {
    class_alias('TV_Domain_Notifications_Service', 'Tv_Subscription_Manager_Includes_Services_Class_Tv_Domain_Notifications_Service');
}
