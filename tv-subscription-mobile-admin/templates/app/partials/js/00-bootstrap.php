<?php if (!defined('ABSPATH')) { exit; } ?>
  <script>
    window.TVMA = {
      api: <?php echo wp_json_encode(rest_url('tv-admin/v2')); ?>,
      nonce: <?php echo wp_json_encode($rest_nonce); ?>,
      user: <?php echo wp_json_encode($display_name); ?>,
      logoutUrl: <?php echo wp_json_encode(home_url('/admin/logout')); ?>
    };

    // Defer Execution until React loads
    document.addEventListener('DOMContentLoaded', () => {
        const interval = setInterval(() => {
            if (window.React && window.ReactDOM) {
                clearInterval(interval);
                initApp();
            }
        }, 50);
    });

    function initApp() {
        const { useState, useEffect, useRef, createElement: el, Component } = React;

