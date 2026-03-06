<?php
/**
 * Template Name: TV Manager Console (WP Admin Replica)
 * Description: Headless admin dashboard styled exactly like WP Admin.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Security Check
if ( ! is_user_logged_in() || ! current_user_can('manage_options') ) {
    wp_redirect( home_url( '/manager-login' ) );
    exit;
}

// 2. Setup Environment for Existing Admin Classes
// We mimic the exact query variables expected by class-tv-admin.php
$_GET['page'] = 'tv-subs-manager'; 
if ( ! isset( $_GET['tab'] ) ) {
    $_GET['tab'] = 'dashboard';
}

$tab = sanitize_key($_GET['tab']);
$current_user = wp_get_current_user();
$admin_url = admin_url(); 

// 3. FORCE ADMIN STYLES (Manual Enqueue)
function streamos_manager_force_admin_styles() {
    global $wp_styles, $wp_scripts;
    
    // A. AGGRESSIVELY DEQUEUE THEME ASSETS
    // This loops through everything enqueued by the theme and unsets it
    if ( $wp_styles && $wp_styles->queue ) {
        foreach ( $wp_styles->queue as $handle ) {
            // Keep only essential core WP styles
            if ( !in_array( $handle, ['admin-bar', 'dashicons', 'open-sans', 'buttons', 'tv-admin-ui', 'jquery-ui-sortable'] ) ) {
                wp_dequeue_style( $handle );
                wp_deregister_style( $handle );
            }
        }
    }
    
    // B. ENQUEUE CORE ADMIN STYLES
    $admin_css_url = includes_url( '../wp-admin/css/' );
    $suffix = is_rtl() ? '-rtl' : '';
    
    // Core Layout
    wp_enqueue_style( 'common', $admin_css_url . "common$suffix.min.css" );
    wp_enqueue_style( 'forms', $admin_css_url . "forms$suffix.min.css" );
    wp_enqueue_style( 'admin-menu', $admin_css_url . "admin-menu$suffix.min.css" );
    wp_enqueue_style( 'dashboard', $admin_css_url . "dashboard$suffix.min.css" );
    wp_enqueue_style( 'list-tables', $admin_css_url . "list-tables$suffix.min.css" );
    wp_enqueue_style( 'edit', $admin_css_url . "edit$suffix.min.css" );
    wp_enqueue_style( 'revisions', $admin_css_url . "revisions$suffix.min.css" );
    wp_enqueue_style( 'media', $admin_css_url . "media$suffix.min.css" );
    wp_enqueue_style( 'themes', $admin_css_url . "themes$suffix.min.css" );
    wp_enqueue_style( 'nav-menus', $admin_css_url . "nav-menus$suffix.min.css" );
    wp_enqueue_style( 'wp-admin', $admin_css_url . "wp-admin$suffix.min.css" );
    wp_enqueue_style( 'dashicons' );
    wp_enqueue_style( 'buttons' );
    
    // Admin Color Scheme (Fresh)
    wp_enqueue_style( 'admin-color', $admin_css_url . "colors/fresh/colors$suffix.min.css" );

    // C. ENQUEUE TV MANAGER STYLES
    if ( defined('TV_MANAGER_URL') ) {
        wp_enqueue_style('tv-admin-ui', TV_MANAGER_URL . 'admin/assets/tv-admin-ui.css', array(), '1.1');
        wp_enqueue_script('tv-admin-ui', TV_MANAGER_URL . 'admin/assets/tv-admin-ui.js', array('jquery', 'jquery-ui-sortable'), '1.1', true);
        
        wp_localize_script('tv-admin-ui', 'tvAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'deleteTokenNonce' => wp_create_nonce('tv_issue_delete_token'),
            'sortNonce' => wp_create_nonce('tv_plan_sort_nonce')
        ));
    }
}
// Hook immediately to ensure it runs before wp_head
add_action( 'wp_enqueue_scripts', 'streamos_manager_force_admin_styles', 1 );

// Disable Admin Bar to prevent double headers
add_filter('show_admin_bar', '__return_false');

?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="wp-toolbar">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TV Manager &lsaquo; <?php bloginfo('name'); ?></title>
    
    <script type="text/javascript">
        addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
        var ajaxurl = '<?php echo admin_url( 'admin-ajax.php', 'relative' ); ?>';
    </script>
    
    <?php 
    // This outputs the scripts/styles hooked above
    wp_head(); 
    ?>
    
    <style>
        /* 1. RESET TO STANDARD WP ADMIN SIZING */
        html { 
            font-size: 13px !important; /* Forces WP Admin standard size */
            height: 100%; 
            background: #f0f0f1; 
            overflow-x: hidden; /* Prevent oversize horizontal scroll */
        }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            color: #3c434a;
            background: #f0f0f1;
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        /* 2. LAYOUT FIXES */
        #wpwrap { height: auto; min-height: 100%; position: relative; }
        
        /* Adjust Content Area for Sidebar */
        #wpcontent { 
            margin-left: 160px; 
            padding-top: 32px; /* For top bar */
            height: 100%;
        }
        
        /* Adjust Sidebar to Fixed */
        #adminmenuback, #adminmenuwrap { 
            background-color: #1d2327; 
            width: 160px; 
            position: fixed; 
            top: 32px; /* Below top bar */
            bottom: 0; 
            left: 0; 
            z-index: 9990;
        }
        #adminmenu { margin-top: 0; }
        
        /* Top Bar Simulation (Matches #wpadminbar z-index) */
        .manager-top-bar {
            height: 32px;
            background: #1d2327;
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 99999;
            display: flex;
            align-items: center;
            padding: 0 20px;
            justify-content: space-between;
        }
        .manager-top-bar a { color: #fff; text-decoration: none; font-size: 13px; display: flex; align-items: center; height: 100%; }
        .manager-top-bar a:hover { color: #72aee6; }
        
        /* Hide any potential theme leaks explicitly */
        header, footer, .site-header, .site-footer, #colophon, #masthead { display: none !important; }
        
        /* Responsive Fold */
        @media only screen and (max-width: 960px) {
            #wpcontent { margin-left: 36px; }
            #adminmenuback, #adminmenuwrap { width: 36px; }
            .wp-menu-name { display: none; }
        }
    </style>
</head>
<body class="wp-admin wp-core-ui js index-php auto-fold admin-bar admin-color-fresh locale-en-us customize-support svg">

<div id="wpwrap">
    
    <!-- Top Bar -->
    <div class="manager-top-bar">
        <div style="display:flex; gap:20px; align-items:center;">
            <a href="<?php echo home_url(); ?>"><span class="dashicons dashicons-admin-home" style="margin-right:6px;"></span> <?php bloginfo('name'); ?></a>
            <span style="opacity:0.5; font-size:12px;">TV Manager Console</span>
        </div>
        <div style="display:flex; gap:20px; align-items:center;">
            <span>Howdy, <?php echo esc_html($current_user->display_name); ?></span>
            <a href="<?php echo wp_logout_url(home_url('/manager-login')); ?>">Log Out</a>
        </div>
    </div>

    <!-- Sidebar -->
    <div id="adminmenumain" role="navigation" aria-label="Main menu">
        <div id="adminmenuback"></div>
        <div id="adminmenuwrap">
            <ul id="adminmenu">
                <!-- Dashboard -->
                <li class="wp-first-item wp-has-submenu wp-not-current-submenu menu-top menu-top-first menu-icon-dashboard <?php echo $tab === 'dashboard' ? 'current menu-open' : ''; ?>">
                    <a href="?streamos_route=manager&tab=dashboard" class="wp-first-item wp-has-submenu wp-not-current-submenu menu-top menu-top-first menu-icon-dashboard">
                        <div class="wp-menu-arrow"><div></div></div>
                        <div class="wp-menu-image dashicons-before dashicons-dashboard"><br></div>
                        <div class="wp-menu-name">Dashboard</div>
                    </a>
                </li>
                
                <li class="wp-not-current-submenu menu-top menu-icon-users <?php echo $tab === 'users' ? 'current menu-open' : ''; ?>">
                    <a href="?streamos_route=manager&tab=users" class="wp-not-current-submenu menu-top menu-icon-users">
                        <div class="wp-menu-arrow"><div></div></div>
                        <div class="wp-menu-image dashicons-before dashicons-admin-users"><br></div>
                        <div class="wp-menu-name">Users</div>
                    </a>
                </li>

                <li class="wp-not-current-submenu menu-top menu-icon-post <?php echo $tab === 'plans' ? 'current menu-open' : ''; ?>">
                    <a href="?streamos_route=manager&tab=plans" class="wp-not-current-submenu menu-top menu-icon-post">
                        <div class="wp-menu-arrow"><div></div></div>
                        <div class="wp-menu-image dashicons-before dashicons-tag"><br></div>
                        <div class="wp-menu-name">Plans</div>
                    </a>
                </li>

                <li class="wp-not-current-submenu menu-top menu-icon-cart <?php echo $tab === 'payments' ? 'current menu-open' : ''; ?>">
                    <a href="?streamos_route=manager&tab=payments" class="wp-not-current-submenu menu-top menu-icon-cart">
                        <div class="wp-menu-arrow"><div></div></div>
                        <div class="wp-menu-image dashicons-before dashicons-cart"><br></div>
                        <div class="wp-menu-name">Transactions</div>
                    </a>
                </li>

                 <li class="wp-not-current-submenu menu-top menu-icon-tickets <?php echo $tab === 'sports' ? 'current menu-open' : ''; ?>">
                    <a href="?streamos_route=manager&tab=sports" class="wp-not-current-submenu menu-top menu-icon-tickets">
                        <div class="wp-menu-arrow"><div></div></div>
                        <div class="wp-menu-image dashicons-before dashicons-tickets-alt"><br></div>
                        <div class="wp-menu-name">Sports Guide</div>
                    </a>
                </li>

                <li class="wp-not-current-submenu menu-top menu-icon-chart <?php echo $tab === 'finance' ? 'current menu-open' : ''; ?>">
                    <a href="?streamos_route=manager&tab=finance" class="wp-not-current-submenu menu-top menu-icon-chart">
                        <div class="wp-menu-arrow"><div></div></div>
                        <div class="wp-menu-image dashicons-before dashicons-chart-bar"><br></div>
                        <div class="wp-menu-name">Finance</div>
                    </a>
                </li>
                
                <li class="wp-not-current-submenu menu-top menu-icon-settings <?php echo $tab === 'settings' ? 'current menu-open' : ''; ?>">
                    <a href="?streamos_route=manager&tab=settings" class="wp-not-current-submenu menu-top menu-icon-settings">
                        <div class="wp-menu-arrow"><div></div></div>
                        <div class="wp-menu-image dashicons-before dashicons-admin-settings"><br></div>
                        <div class="wp-menu-name">Settings</div>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Content -->
    <div id="wpcontent">
        <div id="wpbody" role="main">
            <div id="wpbody-content">
                <div class="wrap">
                    <?php
                    // INSTANTIATE AND RENDER
                    if ( class_exists('TV_Manager_Admin') ) {
                        global $wpdb;
                        // Create a clean instance
                        $admin_instance = new TV_Manager_Admin($wpdb);
                        
                        // Manually trigger the router logic
                        $admin_instance->render_admin_interface();
                    } else {
                        echo '<div class="notice notice-error"><p>System Error: Manager Class not loaded. Is the TV Subscription Manager plugin active?</p></div>';
                    }
                    ?>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>

<?php 
// Hidden footer logic
echo '<div style="display:none;">';
wp_footer();
echo '</div>';
?>
</body>
</html>