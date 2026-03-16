<?php
/*
Plugin Name: XOFLIX TV (Portal Core)
Description: XOFLIX TV portal: device detection, custom authentication, and template routing.
Version: 15.4
Author: XOFLIX
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// 1. Constants
define( 'STREAMOS_VERSION', '15.4' ); // Bumped version
define( 'STREAMOS_PATH', plugin_dir_path( __FILE__ ) );
define( 'STREAMOS_URL', plugin_dir_url( __FILE__ ) );

// 2. Include Modules
require_once STREAMOS_PATH . 'includes/class-page-ownership.php';
require_once STREAMOS_PATH . 'includes/security/class-streamos-token-guard.php';
require_once STREAMOS_PATH . 'includes/class-router.php';
require_once STREAMOS_PATH . 'includes/class-user-manager.php'; // NEW: Business Logic
require_once STREAMOS_PATH . 'includes/class-auth.php';
require_once STREAMOS_PATH . 'includes/class-assets.php';

/**
 * Activation: ensure custom rewrites are registered and flushed.
 * This prevents "lost" routes after installs/updates (e.g., /dashboard, /login).
 */
register_activation_hook( __FILE__, function () {
    // Ensure the router's rewrite rules exist before flushing.
    if ( class_exists( 'StreamOS_Router' ) && method_exists( 'StreamOS_Router', 'register_rewrites_static' ) ) {
        StreamOS_Router::register_rewrites_static();
    }
    flush_rewrite_rules();
} );


// 3. Initialize
class StreamOS_IPTV_Core {
    public function __construct() {
        new StreamOS_Router();
        new StreamOS_Auth();
        new StreamOS_Assets();
        // User Manager is static, doesn't need instantiation
    }
}

new StreamOS_IPTV_Core();
