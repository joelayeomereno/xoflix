<?php
/**
 * Template: TV Manager Console (Standalone)
 * Route:     /manager
 *
 * Renders the full TV Manager UI as a self-contained page — no WP Admin CSS,
 * no WP Admin sidebar. Looks and operates identically to the TV Manager plugin
 * inside WordPress admin, but lives at website.com/manager and keeps all
 * navigation within that route.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── 1. Auth guard ─────────────────────────────────────────────────────────────
if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
    wp_redirect( home_url( '/manager-login' ) );
    exit;
}

// ── 2. Context flag (detected by header.php, view-settings-tabs.php, etc.) ───
define( 'STREAMOS_MANAGER_CONTEXT', true );

// Disable the WP admin bar — it has no place in the standalone UI.
add_filter( 'show_admin_bar', '__return_false' );

// ── 3. Route: map URL params to the values render_admin_interface() expects ──
//
// We mirror the WP Admin URL scheme: ?page=tv-dashboard, ?page=tv-subs-manager&tab=plans …
// This means all form action attributes (e.g. action="?page=tv-subs-manager&tab=settings")
// already submit to the correct URL automatically, with no changes to any view file.
//
// If $_GET['page'] is absent (bare /manager visit, or ?tab=xxx shorthand), set a sensible
// default so render_admin_interface() can determine the current tab.
if ( empty( $_GET['page'] ) ) {
    $tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';
    $tab_page_defaults = [
        'dashboard' => 'tv-dashboard',
        'sports'    => 'tv-sports',
        'settings'  => 'tv-settings-general',
    ];
    // Everything else (users / plans / payments / finance / coupons / methods / messages)
    // lives under tv-subs-manager with its own &tab= param.
    $_GET['page'] = $tab_page_defaults[ $tab ] ?? 'tv-subs-manager';
    if ( ! isset( $_GET['tab'] ) ) {
        $_GET['tab'] = $tab;
    }
}

// ── 4. Pre-build admin instance so we can call render_admin_styles() in <head> ──
global $wpdb;
$_mgr_admin = null;
if ( class_exists( 'TV_Manager_Admin' ) ) {
    $_mgr_admin = new TV_Manager_Admin( $wpdb );
}

$_mgr_ver  = defined( 'TV_MANAGER_VERSION' ) ? TV_MANAGER_VERSION : '1.0';
$_mgr_ajax = admin_url( 'admin-ajax.php' );   // AJAX endpoint is always the WP one
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TV Manager &lsaquo; <?php bloginfo( 'name' ); ?></title>
    <meta name="robots" content="noindex, noarchive">

    <!-- Inter – same font used by the TV Manager inside WP Admin -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Dashicons (required for all icons in the TV Manager UI) -->
    <link rel="stylesheet" href="<?php echo esc_url( includes_url( 'css/dashicons.min.css' ) ); ?>">

<?php if ( defined( 'TV_MANAGER_URL' ) ) : ?>
    <!-- TV Manager stylesheet -->
    <link rel="stylesheet" href="<?php echo esc_url( TV_MANAGER_URL . 'admin/assets/tv-admin-ui.css' ); ?>?v=<?php echo esc_attr( $_mgr_ver ); ?>">
<?php endif; ?>

    <?php
    // Output the same inline <style> block that WP Admin would inject via admin_head.
    // render_admin_styles() guards itself with is_tv_admin_page(), which checks $_GET['page'].
    if ( $_mgr_admin ) {
        $_mgr_admin->render_admin_styles();
    }
    ?>

    <script>
    /* AJAX globals — identical to what WP Admin injects */
    var ajaxurl = <?php echo wp_json_encode( $_mgr_ajax ); ?>;
    var tvAdmin  = {
        ajaxUrl:          <?php echo wp_json_encode( $_mgr_ajax ); ?>,
        deleteTokenNonce: <?php echo wp_json_encode( wp_create_nonce( 'tv_issue_delete_token' ) ); ?>,
        sortNonce:        <?php echo wp_json_encode( wp_create_nonce( 'tv_plan_sort_nonce' ) ); ?>,
        manualSubNonce:   <?php echo wp_json_encode( wp_create_nonce( 'tv_manual_sub_nonce' ) ); ?>
    };
    </script>

    <style>
    /* Ensure the standalone page fills the viewport cleanly */
    html, body { margin: 0; padding: 0; background: var(--tv-bg, #f1f5f9); min-height: 100%; }
    /* tv-app-container is opened by header.php and closed below */
    .tv-app-container { min-height: 100vh; }
    </style>
</head>
<body class="wp-core-ui">
<?php if ( $_mgr_admin ) : ?>

    <?php $_mgr_admin->render_admin_interface(); ?>
    </div><!-- /.tv-app-container (opened by header.php) -->

<?php else : ?>
    <div style="padding:60px 40px;font-family:sans-serif;text-align:center;">
        <h2 style="color:#c0392b;">System Error</h2>
        <p>The TV Subscription Manager plugin is not active or not loaded correctly.</p>
        <p><a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" style="color:#0073aa;">Go to Plugins &rarr;</a></p>
    </div>
<?php endif; ?>

<!-- Scripts (loaded after body so they don't block rendering) -->

<!-- jQuery: WordPress-bundled, no external dependency -->
<script src="<?php echo esc_url( includes_url( 'js/jquery/jquery.min.js' ) ); ?>"></script>
<script src="<?php echo esc_url( includes_url( 'js/jquery/ui/sortable.min.js' ) ); ?>"></script>

<!-- Chart.js (used by Finance / Dashboard charts) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>

<?php if ( defined( 'TV_MANAGER_URL' ) ) : ?>
<!-- TV Manager interaction script -->
<script src="<?php echo esc_url( TV_MANAGER_URL . 'admin/assets/tv-admin-ui.js' ); ?>?v=<?php echo esc_attr( $_mgr_ver ); ?>"></script>
<?php endif; ?>

</body>
</html>
