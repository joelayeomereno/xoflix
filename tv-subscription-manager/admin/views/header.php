<div class="tv-app-container">
    <div class="tv-top-bar">
        <!-- Brand -->
        <div class="tv-brand">
            <div class="tv-brand-logo">
                <span class="dashicons dashicons-desktop" style="font-size:18px;width:18px;height:18px;"></span>
            </div>
            XOFLIX
            <span class="tv-version">v3.7</span>
        </div>

        <!-- Navigation -->
        <?php
        // Context-aware base URL: use /manager when running standalone, WP admin otherwise.
        $_tv_nav_base = defined('STREAMOS_MANAGER_CONTEXT') ? home_url('/manager') : admin_url('admin.php');
        ?>
        <nav class="tv-nav">
            <a href="<?php echo esc_url(add_query_arg(['page' => 'tv-dashboard'], $_tv_nav_base)); ?>" 
               class="tv-nav-item <?php echo ($tab === 'dashboard') ? 'active' : ''; ?>">
                Dashboard
            </a>
            <a href="<?php echo esc_url(add_query_arg(['page' => 'tv-subscribers'], $_tv_nav_base)); ?>" 
               class="tv-nav-item <?php echo (isset($_GET['page']) && $_GET['page'] === 'tv-subscribers') ? 'active' : ''; ?>">
                Subscribers
            </a>
            <a href="<?php echo esc_url(add_query_arg(['page' => 'tv-subs-manager', 'tab' => 'plans'], $_tv_nav_base)); ?>" 
               class="tv-nav-item <?php echo ($tab === 'plans') ? 'active' : ''; ?>">
                Plans
            </a>
            <a href="<?php echo esc_url(add_query_arg(['page' => 'tv-subs-manager', 'tab' => 'payments'], $_tv_nav_base)); ?>" 
               class="tv-nav-item <?php echo ($tab === 'payments') ? 'active' : ''; ?>">
                Transactions
            </a>
            <a href="<?php echo esc_url(add_query_arg(['page' => 'tv-sports'], $_tv_nav_base)); ?>" 
               class="tv-nav-item <?php echo ($tab === 'sports') ? 'active' : ''; ?>">
                Sports
            </a>
            <?php if (current_user_can('manage_options') || current_user_can('manage_tv_finance')): ?>
            <a href="<?php echo esc_url(add_query_arg(['page' => 'tv-subs-manager', 'tab' => 'finance'], $_tv_nav_base)); ?>" 
               class="tv-nav-item <?php echo ($tab === 'finance') ? 'active' : ''; ?>">
                Finance
            </a>
            <?php endif; ?>
            <a href="<?php echo esc_url(add_query_arg(['page' => 'tv-subs-manager', 'tab' => 'coupons'], $_tv_nav_base)); ?>" 
               class="tv-nav-item <?php echo ($tab === 'coupons') ? 'active' : ''; ?>">
                Coupons
            </a>
            <a href="<?php echo esc_url(add_query_arg(['page' => 'tv-subs-manager', 'tab' => 'methods'], $_tv_nav_base)); ?>" 
               class="tv-nav-item <?php echo ($tab === 'methods') ? 'active' : ''; ?>">
                Methods
            </a>
            <a href="<?php echo esc_url(add_query_arg(['page' => 'tv-subs-manager', 'tab' => 'messages'], $_tv_nav_base)); ?>" 
               class="tv-nav-item <?php echo ($tab === 'messages') ? 'active' : ''; ?>">
                Messages
            </a>
            <a href="<?php echo esc_url(add_query_arg(['page' => 'tv-settings-general'], $_tv_nav_base)); ?>" 
               class="tv-nav-item <?php echo ($tab === 'settings') ? 'active' : ''; ?>">
                Settings
            </a>
        </nav>

        <!-- Tools -->
        <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
            <?php if (defined('STREAMOS_MANAGER_CONTEXT')): ?>
            <a href="<?php echo esc_url(wp_logout_url(home_url('/manager-login'))); ?>"
               style="font-size:12px;color:var(--tv-text-muted);text-decoration:none;display:flex;align-items:center;gap:4px;"
               title="Log Out">
                <span class="dashicons dashicons-exit" style="font-size:16px;width:16px;height:16px;"></span>
            </a>
            <?php endif; ?>
            <button id="tv-theme-toggle" class="tv-theme-toggle" title="Toggle Dark/Light Mode" onclick="tvToggleTheme()">
                <span class="dashicons dashicons-lightbulb" id="tv-theme-icon"></span>
            </button>
        </div>
    </div>

    <!-- Confirm Modal -->
    <div id="tv-sensitive-confirm-modal" class="tv-modal-overlay" style="display:none" aria-hidden="true">
        <div class="tv-modal" role="dialog" aria-modal="true" aria-labelledby="tv-sensitive-confirm-title">
            <div class="tv-modal-header">
                <div id="tv-sensitive-confirm-title" class="tv-modal-title" style="font-weight:700;font-size:15px;">Confirm</div>
                <button type="button" class="tv-btn tv-btn-secondary tv-btn-sm" data-tv-confirm-cancel>Close</button>
            </div>
            <div class="tv-modal-body">
                <p id="tv-sensitive-confirm-message" style="margin:0;color:var(--tv-text);"></p>
            </div>
            <div class="tv-modal-footer">
                <button type="button" class="tv-btn tv-btn-secondary" data-tv-confirm-cancel>Cancel</button>
                <button type="button" class="tv-btn tv-btn-primary" data-tv-confirm-ok>Confirm</button>
            </div>
        </div>
    </div>

    <!-- Delete Verify Modal -->
    <div id="tv-delete-verify-modal" class="tv-modal-overlay" style="display:none" aria-hidden="true">
        <div class="tv-modal" role="dialog" aria-modal="true" aria-labelledby="tv-delete-verify-title">
            <div class="tv-modal-header">
                <div id="tv-delete-verify-title" style="font-weight:700;font-size:15px;color:var(--tv-danger);">
                    <span class="dashicons dashicons-warning" style="margin-right:6px;"></span>Delete Verification
                </div>
                <button type="button" class="tv-btn tv-btn-secondary tv-btn-sm tv-modal-close" data-tv-modal-close="1">Close</button>
            </div>
            <div class="tv-modal-body">
                <p style="margin:0 0 14px;color:var(--tv-text-muted);">To authorize this delete, type the 4-digit code shown below:</p>
                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <div id="tv-delete-verify-code" data-tv-code class="tv-code-pill" style="font-size:22px;letter-spacing:8px;">0000</div>
                    <input id="tv-delete-verify-input" type="text" data-tv-code-input inputmode="numeric" maxlength="4" 
                           class="tv-input" style="max-width:120px;text-align:center;font-family:monospace;font-size:18px;font-weight:800;letter-spacing:6px;" 
                           placeholder="____">
                </div>
                <div id="tv-delete-verify-error" data-tv-delete-error style="display:none;margin-top:10px;color:var(--tv-danger);font-weight:700;font-size:13px;">
                    Code mismatch. Try again.
                </div>
            </div>
            <div class="tv-modal-footer">
                <button type="button" class="tv-btn tv-btn-secondary" data-tv-delete-cancel="1">Cancel</button>
                <button type="button" class="tv-btn tv-btn-danger" id="tv-delete-verify-confirm" data-tv-delete-verify>
                    <span class="dashicons dashicons-trash" style="font-size:16px;width:16px;height:16px;"></span>
                    Confirm Delete
                </button>
            </div>
        </div>
    </div>

    <script>
    // -- DARK/LIGHT THEME TOGGLE --
    (function(){
        const saved = localStorage.getItem('tv_theme');
        if(saved === 'dark') {
            document.body.classList.add('tv-dark');
            const icon = document.getElementById('tv-theme-icon');
            if(icon) { icon.classList.remove('dashicons-lightbulb'); icon.classList.add('dashicons-sun'); }
        }
    })();
    function tvToggleTheme(){
        const isDark = document.body.classList.toggle('tv-dark');
        localStorage.setItem('tv_theme', isDark ? 'dark' : 'light');
        const icon = document.getElementById('tv-theme-icon');
        if(icon){
            if(isDark){ icon.classList.remove('dashicons-lightbulb'); icon.classList.add('dashicons-sun'); }
            else       { icon.classList.remove('dashicons-sun'); icon.classList.add('dashicons-lightbulb'); }
        }
    }
    </script>

    <div class="tv-content-area">
```