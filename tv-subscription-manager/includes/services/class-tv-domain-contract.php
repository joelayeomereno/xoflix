<?php
if (!defined('ABSPATH')) { exit; }

/**
 * File: tv-subscription-manager/includes/services/class-tv-domain-contract.php
 * Path: /tv-subscription-manager/includes/services/class-tv-domain-contract.php
 *
 * Lightweight contract assertions to prevent silent drift/regressions.
 *
 * This class is intentionally small and side-effect free.
 * It provides: allowed status sets, and helper assertions used by domain services.
 */
class TV_Domain_Contract {

    /**
     * Payment statuses observed in the codebase (desktop + mobile) and treated as valid.
     * IMPORTANT: This is a parity guard - do not remove statuses, only add if the system evolves.
     */
    public static function allowed_payment_statuses() : array {
        return array(
            'pending',
            'AWAITING_PROOF',
            'IN_PROGRESS',
            'PENDING_ADMIN_REVIEW',
            'APPROVED',
            'REJECTED',
            'completed',
        );
    }

    /**
     * Subscription statuses observed in the codebase.
     */
    public static function allowed_subscription_statuses() : array {
        return array(
            'active',
            'inactive',
            'pending',
        );
    }

    public static function assert_allowed_payment_status(string $status) : void {
        $status = (string)$status;
        if (!in_array($status, self::allowed_payment_statuses(), true)) {
            throw new InvalidArgumentException('Invalid payment status: ' . $status);
        }
    }

    public static function assert_allowed_subscription_status(string $status) : void {
        $status = (string)$status;
        if (!in_array($status, self::allowed_subscription_statuses(), true)) {
            throw new InvalidArgumentException('Invalid subscription status: ' . $status);
        }
    }

    public static function assert_positive_int($value, string $label = 'id') : void {
        $value = (int)$value;
        if ($value <= 0) {
            throw new InvalidArgumentException('Invalid ' . $label);
        }
    }

    /**
     * Guard: plan duration_days must be a positive integer; default to 30 if missing.
     */
    public static function normalize_plan_duration_days($duration_days) : int {
        $days = (int)$duration_days;
        if ($days <= 0) {
            $days = 30;
        }
        return $days;
    }
}

// Module alias to match full location path (non-breaking).
if (!class_exists('Tv_Subscription_Manager_Includes_Services_Class_Tv_Domain_Contract', false)) {
    class_alias('TV_Domain_Contract', 'Tv_Subscription_Manager_Includes_Services_Class_Tv_Domain_Contract');
}
