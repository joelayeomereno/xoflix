<?php
if (!defined('ABSPATH')) { exit; }

class TV_Subscription_Mobile_Admin_Includes_Routing_Mobile_Admin_Router {

    const QUERY_VAR = 'tv_mobile_admin_route';

    public static function init() : void {
        // Remove WP default admin redirect immediately
        remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
        add_filter('redirect_canonical', array(__CLASS__, 'prevent_canonical_redirect'), 10, 2);
    }

    public static function activate() : void {
        flush_rewrite_rules();
    }
    
    public static function deactivate() : void {
        flush_rewrite_rules();
    }

    public static function register_query_vars(array $vars) : array {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    public static function prevent_canonical_redirect($redirect_url, $requested_url) {
        if (strpos($_SERVER['REQUEST_URI'], '/admin') !== false) {
            return false;
        }
        return $redirect_url;
    }

    /**
     * Interceptor - Runs on 'init' priority 1
     */
    public static function check_request_and_render() {
        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
        $path = rtrim($path, '/');

        // Route Detection
        $is_admin = ($path === '/admin');
        $is_login = ($path === '/admin/login');
        $is_logout = ($path === '/admin/logout');

        if (!$is_admin && !$is_login && !$is_logout) {
            return;
        }

        // --- TAKEOVER ---

        // Force 200 OK
        if (!headers_sent()) {
            header("HTTP/1.1 200 OK");
            status_header(200);
            header("Cache-Control: no-cache, no-store, must-revalidate");
            header("Expires: 0");
        }

        add_filter('show_admin_bar', '__return_false', 999);

        if ($is_logout) {
            wp_logout();
            wp_safe_redirect(home_url('/admin/login'));
            exit;
        }

        if ($is_login) {
            include TV_SUBSCRIPTION_MOBILE_ADMIN_PLUGIN_DIR . 'templates/tv-subscription-mobile-admin_templates_login.php';
            exit;
        }

        if ($is_admin) {
            // 1. Device Check
            if (!self::is_mobile_device()) {
                self::render_desktop_blocker();
                exit;
            }
            
            // 2. Auth Check
            if (!is_user_logged_in() || !current_user_can('manage_options')) {
                wp_safe_redirect(home_url('/admin/login'));
                exit;
            }

            // 3. Render App
            include TV_SUBSCRIPTION_MOBILE_ADMIN_PLUGIN_DIR . 'templates/tv-subscription-mobile-admin_templates_app.php';
            exit;
        }
    }

    private static function is_mobile_device() : bool {
        if (isset($_GET['dev_mode']) && current_user_can('manage_options')) return true;
        if (wp_is_mobile()) return true;
        
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $ua)) return true;
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|ipod|iphone|android|iemobile)/i', $ua)) return true;
        
        return false;
    }

    private static function render_desktop_blocker() {
        echo '<!DOCTYPE html><html style="height:100%;display:grid;place-items:center;background:#0f172a;color:white;font-family:sans-serif;">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <div style="text-align:center;padding:20px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom:20px;opacity:0.5;"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
            <h1 style="margin:0 0 10px;">Mobile Experience</h1>
            <p style="color:#94a3b8;max-width:300px;margin:0 auto 30px;">This admin panel is designed exclusively for mobile and tablet devices.</p>
            <div style="background:white;padding:10px;display:inline-block;border-radius:10px;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode(home_url('/admin')) . '" alt="Scan to Open" />
            </div>
            <p style="margin-top:20px;font-size:12px;color:#64748b;">Scan to open on your device</p>
            <a href="?dev_mode=1" style="display:block;margin-top:20px;color:#334155;text-decoration:none;font-size:10px;">Developer Bypass</a>
        </div></html>';
    }

    public static function template_include(string $template) : string { return $template; }
}
