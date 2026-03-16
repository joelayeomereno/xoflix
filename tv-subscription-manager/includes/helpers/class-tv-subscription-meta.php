<?php
if (!defined('ABSPATH')) { exit; }

/**
 * File: tv-subscription-manager/includes/helpers/class-tv-subscription-meta.php
 * Path: /tv-subscription-manager/includes/helpers/class-tv-subscription-meta.php
 */
class TV_Subscription_Meta {

    private const OPT_PREFIX = 'tv_sub_meta__';

    private static function opt_key(int $subscription_id, string $name) : string {
        $subscription_id = (int)$subscription_id;
        if ($subscription_id <= 0) {
            return '';
        }
        $name = sanitize_key($name);
        if (empty($name)) {
            return '';
        }
        return self::OPT_PREFIX . $name . '__' . $subscription_id;
    }

    /**
     * Primary meta: purchased months.
     */
    public static function get_months(int $subscription_id) : int {
        $key = self::opt_key($subscription_id, 'months');
        if (empty($key)) return 0;

        $v = get_option($key, 0);
        $m = (int)$v;
        return $m > 0 ? $m : 0;
    }

    public static function set_months(int $subscription_id, int $months) : bool {
        $key = self::opt_key($subscription_id, 'months');
        if (empty($key)) return false;

        $months = (int)$months;
        if ($months <= 0) {
            // Normalize: remove invalid/empty months.
            delete_option($key);
            return true;
        }

        // Autoload disabled to avoid loading many per-subscription options.
        if (get_option($key, null) === null) {
            return add_option($key, $months, '', 'no');
        }
        return update_option($key, $months, 'no');
    }

    public static function delete_months(int $subscription_id) : bool {
        $key = self::opt_key($subscription_id, 'months');
        if (empty($key)) return false;
        return (bool) delete_option($key);
    }
}

// Module alias to match full location path (non-breaking).
if (!class_exists('Tv_Subscription_Manager_Includes_Helpers_Class_Tv_Subscription_Meta', false)) {
    class_alias('TV_Subscription_Meta', 'Tv_Subscription_Manager_Includes_Helpers_Class_Tv_Subscription_Meta');
}
