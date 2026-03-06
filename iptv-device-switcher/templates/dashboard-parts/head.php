<?php
/**
 * Head Partial
 * Contains meta tags, libraries, and styles for the React Dashboard.
 */

if (!defined('ABSPATH')) { exit; }
?>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Dashboard - <?php bloginfo('name'); ?></title>

<!-- OPTIMIZATION: Resource Hints -->
<link rel="preconnect" href="[https://unpkg.com](https://unpkg.com)" crossorigin>
<link rel="preconnect" href="[https://cdn.tailwindcss.com](https://cdn.tailwindcss.com)" crossorigin>

<!-- React & Tailwind -->
<!-- OPTIMIZATION: 'defer' added to prevent render blocking -->
<script src="[https://unpkg.com/react@18/umd/react.production.min.js](https://unpkg.com/react@18/umd/react.production.min.js)" crossorigin defer></script>
<script src="[https://unpkg.com/react-dom@18/umd/react-dom.production.min.js](https://unpkg.com/react-dom@18/umd/react-dom.production.min.js)" crossorigin defer></script>
<script src="[https://unpkg.com/@babel/standalone/babel.min.js](https://unpkg.com/@babel/standalone/babel.min.js)" crossorigin defer></script>
<script src="[https://cdn.tailwindcss.com](https://cdn.tailwindcss.com)" crossorigin defer></script>

<!-- Tailwind Config -->
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    slate: { 850: '#151f32', 900: '#0f172a' },
                    primary: { 500: '#3b82f6', 600: '#2563eb' }
                },
                animation: {
                    'fade-in': 'fadeIn 0.3s ease-out',
                    'slide-up': 'slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1)',
                    'slide-in': 'slideIn 0.3s ease-out'
                },
                keyframes: {
                    fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                    slideUp: { '0%': { transform: 'translateY(10px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } },
                    slideIn: { '0%': { transform: 'translateX(100%)' }, '100%': { transform: 'translateX(0)' } }
                }
            }
        }
    }
</script>

<!-- Custom Styles -->
<style>
    /* Base Overrides */
    body { background-color: #f8fafc; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
    
    /* Scrollbar Utilities */
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
    
    /* Safe Area for Mobile */
    .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
    
    /* Cursors */
    .cursor-wait { cursor: wait; }
</style>
