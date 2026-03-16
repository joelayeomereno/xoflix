<?php if (!defined('ABSPATH')) { exit; } ?>
  <script>
  window.TVMA = {
    api:       <?php echo wp_json_encode(rest_url('tv-admin/v2')); ?>,
    nonce:     <?php echo wp_json_encode($rest_nonce); ?>,
    user:      <?php echo wp_json_encode($display_name); ?>,
    logoutUrl: <?php echo wp_json_encode(home_url('/admin/logout')); ?>
  };

  window.addEventListener('pageshow', e => { if (e && e.persisted) location.reload(); });

  document.addEventListener('DOMContentLoaded', () => {
    const iv = setInterval(() => {
      if (window.React && window.ReactDOM) { clearInterval(iv); initApp(); }
    }, 40);
  });

  function initApp() {
    const { useState, useEffect, useRef, useCallback, useMemo, createElement: el, Component } = React;