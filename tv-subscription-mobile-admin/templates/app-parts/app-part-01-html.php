<?php
if (!defined('ABSPATH')) { exit; }

// 1. Strict Authentication Check
// This page is only accessible to Administrators
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_safe_redirect(home_url('/admin/login'));
    exit;
}

// 2. Prepare Data for React
$rest_nonce = wp_create_nonce('wp_rest');
$current_user = wp_get_current_user();
$display_name = $current_user->display_name ?: $current_user->user_login;

?><!doctype html>
<html lang="en" class="h-full bg-slate-50 antialiased">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1, user-scalable=no" />
  <title>AdminOS Ultra</title>
  
  <!-- PERFORMANCE: Resource Hints -->
  <link rel="preconnect" href="https://cdn.tailwindcss.com">
  <link rel="preconnect" href="https://unpkg.com">

  <!-- Tailwind CSS (Deferred) -->
  <script src="https://cdn.tailwindcss.com" defer></script>
  <script>
    // Mobile Safari/Chrome can restore pages from BFCache after login/navigation,
    // which can prevent deferred JS init from running consistently.
    // Reloading on BFCache restore guarantees full section initialization.
    window.addEventListener('pageshow', function (e) {
      if (e && e.persisted) { window.location.reload(); }
    });

    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'] },
          colors: { 
            primary: { 50: '#f0f9ff', 100: '#e0f2fe', 500: '#0ea5e9', 600: '#0284c7', 900: '#0c4a6e' },
            slate: { 850: '#151f32', 900: '#0f172a' } 
          },
          animation: {
            'in': 'in 0.2s cubic-bezier(0.16, 1, 0.3, 1)',
            'slide-up': 'slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1)',
          },
          keyframes: {
            in: { '0%': { opacity: '0', transform: 'scale(0.98)' }, '100%': { opacity: '1', transform: 'scale(1)' } },
            slideUp: { '0%': { transform: 'translateY(100%)' }, '100%': { transform: 'translateY(0)' } }
          }
        }
      }
    }
  </script>

  <!-- React & ReactDOM (Deferred for non-blocking rendering) -->
  <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js" defer></script>
  <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js" defer></script>
  
  <!-- App Styles -->
  <style>
    body { -webkit-tap-highlight-color: transparent; background: #f8fafc; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
    .pt-safe { padding-top: env(safe-area-inset-top); }
    .glass { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border-top: 1px solid rgba(226, 232, 240, 0.6); }
    .sheet-backdrop { background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); }
    .btn-press:active { transform: scale(0.96); transition: transform 0.1s; }
    input:focus, select:focus, textarea:focus { outline: 2px solid #0ea5e9; outline-offset: -1px; }
    /* Loading Placeholder */
    #root:empty::before { content: 'Loading...'; display: block; text-align: center; padding-top: 40vh; color: #94a3b8; font-family: sans-serif; font-weight: bold; }
  </style>
</head>
<body class="h-full flex flex-col overflow-hidden text-slate-900 bg-slate-50">
  
  <div id="root" class="flex-1 flex flex-col h-full relative"></div>

  <!-- Bootstrapper -->
