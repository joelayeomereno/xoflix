<?php
if (!defined('ABSPATH')) { exit; }

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_safe_redirect(home_url('/admin/login'));
    exit;
}

$rest_nonce   = wp_create_nonce('wp_rest');
$current_user = wp_get_current_user();
$display_name = $current_user->display_name ?: $current_user->user_login;

$parts_dir = plugin_dir_url(__FILE__) . 'parts/';
?><!doctype html>
<html lang="en" style="height:100%;background:#09090b;color-scheme:dark;">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,maximum-scale=1,user-scalable=no">
  <title>XOFLIX Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;0,9..40,900&display=swap" rel="stylesheet">

  <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js" defer></script>
  <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js" defer></script>

  <?php
  // Load all part CSS files
  $css_parts = ['app-styles'];
  foreach ($css_parts as $part) {
      echo '<link rel="stylesheet" href="' . esc_url($parts_dir . $part . '.css') . '">' . "\n  ";
  }
  ?>

</head>
<body>
  <div id="root"></div>
  <div id="toast-root"></div>

  <script>
  window.TVMA = {
    api:       <?php echo wp_json_encode(rest_url('tv-admin/v2')); ?>,
    nonce:     <?php echo wp_json_encode($rest_nonce); ?>,
    user:      <?php echo wp_json_encode($display_name); ?>,
    logoutUrl: <?php echo wp_json_encode(home_url('/admin/logout')); ?>
  };
  window._XOFLIX = {};

  window.addEventListener('pageshow', e => { if (e && e.persisted) location.reload(); });

  document.addEventListener('DOMContentLoaded', () => {
    const iv = setInterval(() => {
      if (window.React && window.ReactDOM) { clearInterval(iv); initApp(); }
    }, 40);
  });
  </script>

  <?php
  // JS parts — order matters: each part registers on window._XOFLIX
  $js_parts = [
    'part-utils',
    'part-ui-primitives',
    'part-search',
    'part-dashboard',
    'part-finance',
    'part-payments',
    'part-users',
    'part-subs',
    'part-sports',
    'part-resource-manager',
    'part-settings',
    'part-menu',
    'part-app',
  ];
  foreach ($js_parts as $part) {
      echo '<script src="' . esc_url($parts_dir . $part . '.js') . '"></script>' . "\n  ";
  }
  ?>

</body>
</html>