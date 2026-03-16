<?php
/**
 * Plugin Name: XOFLIX TV (Mobile Admin)
 * Description: XOFLIX TV dedicated mobile-only admin (/admin). Mobile + tablet UX shell with REST client endpoints.
 * Version: 0.1.5
 * Author: XOFLIX
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) { exit; }

define('TV_SUBSCRIPTION_MOBILE_ADMIN_VERSION', '0.1.5');
define('TV_SUBSCRIPTION_MOBILE_ADMIN_PLUGIN_FILE', __FILE__);
define('TV_SUBSCRIPTION_MOBILE_ADMIN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TV_SUBSCRIPTION_MOBILE_ADMIN_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * FIX 10: One-time cache bust — clears stale dashboard transients that
 * may have cached HTML entity strings (e.g. "&#8358;8,084") from before
 * the currency fix. 
 *
 * SAFE TO REMOVE after first deployment + cache refresh.
 */
add_action('init', function() {
    // Guard: only run once
    if (get_option('tv_mobile_currency_cache_busted_v2')) return;

    // Clear the dashboard stats transient
    delete_transient('tv_mobile_dash_stats');

    // Clear all plugin dashboard cache keys (pattern-based)
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_tv_mobile_%'
            OR option_name LIKE '_transient_timeout_tv_mobile_%'
            OR option_name LIKE '_transient_tv_manager_dashboard_cache_%'
            OR option_name LIKE '_transient_timeout_tv_manager_dashboard_cache_%'"
    );

    // Mark as done — won't run again
    update_option('tv_mobile_currency_cache_busted_v2', 1);
}, 5);

require_once TV_SUBSCRIPTION_MOBILE_ADMIN_PLUGIN_DIR . 'includes/routing/tv-subscription-mobile-admin_includes_routing_mobile_admin_router.php';
require_once TV_SUBSCRIPTION_MOBILE_ADMIN_PLUGIN_DIR . 'includes/auth/tv-subscription-mobile-admin_includes_auth_mobile_admin_auth.php';
require_once TV_SUBSCRIPTION_MOBILE_ADMIN_PLUGIN_DIR . 'includes/rest/tv-subscription-mobile-admin_includes_rest_mobile_admin_rest.php';

register_activation_hook(__FILE__, array('TV_Subscription_Mobile_Admin_Includes_Routing_Mobile_Admin_Router', 'activate'));
register_deactivation_hook(__FILE__, array('TV_Subscription_Mobile_Admin_Includes_Routing_Mobile_Admin_Router', 'deactivate'));

add_action('init', array('TV_Subscription_Mobile_Admin_Includes_Routing_Mobile_Admin_Router', 'init'));
add_filter('query_vars', array('TV_Subscription_Mobile_Admin_Includes_Routing_Mobile_Admin_Router', 'register_query_vars'));

// [FIX] Hook into 'init' at priority 1. 
// This allows WP to authenticate the user (cookie check) BUT runs before any other redirect logic.
add_action('init', array('TV_Subscription_Mobile_Admin_Includes_Routing_Mobile_Admin_Router', 'check_request_and_render'), 1);

add_action('rest_api_init', array('TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest', 'register_routes'));