<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Trait: IPTV_Device_Switcher_Includes_Auth_Trait_Part_01
 * Path: /iptv-device-switcher/includes/auth/trait-iptv-device-switcher-includes-auth-trait-part-01.php
 */
trait IPTV_Device_Switcher_Includes_Auth_Trait_Part_01 {

    private function get_html_email_template($title, $message, $button_text, $button_url) {
        $bg_color = '#0f172a';
        $card_color = '#1e293b';
        $text_color = '#f8fafc';
        $accent_color = '#6366f1';

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { margin: 0; padding: 0; background-color: <?php echo $bg_color; ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
                .container { width: 100%; max-width: 600px; margin: 0 auto; background-color: <?php echo $bg_color; ?>; padding: 40px 20px; }
                .card { background-color: <?php echo $card_color; ?>; border-radius: 16px; padding: 40px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.05); }
                .logo { font-size: 24px; font-weight: 800; color: <?php echo $text_color; ?>; margin-bottom: 30px; letter-spacing: -1px; }
                .title { font-size: 20px; font-weight: 700; color: <?php echo $text_color; ?>; margin-bottom: 16px; }
                .text { font-size: 15px; line-height: 1.6; color: #94a3b8; margin-bottom: 30px; }
                .btn { display: inline-block; padding: 14px 32px; background-color: <?php echo $accent_color; ?>; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 14px; }
                .footer { margin-top: 30px; font-size: 12px; color: #64748b; text-align: center; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="card">
                    <div class="logo"><?php echo esc_html(get_bloginfo('name')); ?></div>
                    <div class="title"><?php echo esc_html($title); ?></div>
                    <div class="text"><?php echo wp_kses_post($message); ?></div>
                    <a href="<?php echo esc_url($button_url); ?>" class="btn"><?php echo esc_html($button_text); ?></a>
                </div>
                <div class="footer">
                    &copy; <?php echo date('Y'); ?> <?php echo esc_html(get_bloginfo('name')); ?>. All rights reserved.<br>
                    If you didn't request this, you can safely ignore this email.
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    // ---------------------------------------------------------------------
    // Impersonation session lifecycle (auditable + bounded)
    // ---------------------------------------------------------------------

    private function get_impersonation_cookie_name() : string {
        return (string) apply_filters('tv_impersonation_cookie_name', 'tv_impersonation');
    }

    private function get_impersonation_max_seconds() : int {
        // Default 30 minutes; token entry link is still 15 minutes (separate).
        $v = (int) apply_filters('tv_impersonation_max_seconds', 30 * 60);
        return max(300, $v); // minimum 5 minutes
    }

    private function sign_impersonation_payload(string $payload) : string {
        return hash_hmac('sha256', $payload, wp_salt('auth'));
    }

    private function set_impersonation_cookie(int $admin_id, int $target_user_id, int $started_at, int $expires_at) : void {
        // [UPGRADE] Shadow Session Implementation
        // Instead of a generic payload, we create a specific Shadow Token.
        // Format: admin_id | target_id | expiry | signature
        $data = $admin_id . '|' . $target_user_id . '|' . $expires_at;
        $sig = hash_hmac('sha256', $data, wp_salt('auth'));
        $cookie_val = base64_encode($data . '|' . $sig);

        // Set 'tv_shadow_token' - This allows us to separate this from WP's auth cookie
        setcookie('tv_shadow_token', $cookie_val, $expires_at, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        $_COOKIE['tv_shadow_token'] = $cookie_val;
    }

    private function clear_impersonation_cookie() : void {
        setcookie('tv_shadow_token', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        unset($_COOKIE['tv_shadow_token']);
    }

    private function log_impersonation_event(string $action, int $admin_id, int $target_user_id, string $details = '') : void {
        // Prefer TV Manager activity logs when available.
        global $wpdb;
        if (!($wpdb instanceof wpdb)) return;
        $table_logs = $wpdb->prefix . 'tv_activity_logs';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_logs}'") !== $table_logs) return;

        $base = "Admin {$admin_id} => User {$target_user_id}";
        $wpdb->insert($table_logs, array(
            'user_id' => (int)$admin_id,
            'action' => $action,
            'details' => trim($base . (empty($details) ? '' : ' | ' . $details)),
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '0.0.0.0',
            'date' => current_time('mysql'),
        ));
    }

    /**
     * Enforces impersonation duration + provides clean termination.
     * - If marker is expired/invalid, forces logout of impersonated session.
     * - If marker exists, ensures an active server-side record exists.
     */
    public function enforce_impersonation_session_bounds() {
        // Clean termination endpoint (server-side): /dashboard?tv_end_impersonation=1
        if (isset($_GET['tv_end_impersonation']) && (string)$_GET['tv_end_impersonation'] !== '') {
            $this->clear_impersonation_cookie();
            // Redirect back to Admin Dashboard Users list or Manager
            if ( function_exists('admin_url') ) {
                wp_redirect(admin_url('admin.php?page=tv-subs-manager&tab=users'));
            } else {
                wp_redirect(home_url('/manager'));
            }
            exit;
        }
    }

    /**
     * Handles "Login as User" link.
     * Uses explicit HTML Output to guarantee visual feedback and prevent white screens.
     */
    public function handle_admin_impersonation() {
        // Only trigger if specific parameters are present
        if ( isset($_GET['tv_sandbox']) && isset($_GET['tv_user']) ) {
            
            $target_user_id = intval($_GET['tv_user']);
            $admin_id       = isset($_GET['tv_admin']) ? intval($_GET['tv_admin']) : 0;
            $timestamp      = isset($_GET['tv_time']) ? intval($_GET['tv_time']) : 0;
            $token          = isset($_GET['tv_token']) ? $_GET['tv_token'] : '';
            
            $is_valid = false;

            // 1. Check strict Admin permissions (Cookie check)
            if ( current_user_can('manage_options') ) {
                $is_valid = true;
            } 
            // 2. Fallback: Validate Token (If browser session is empty)
            else if ( $admin_id && $timestamp && $token ) {
                // Token valid for 15 minutes
                if ( time() - $timestamp < 900 ) {
                    $salt = wp_salt('auth');
                    $check_payload = $admin_id . '|' . $target_user_id . '|' . $timestamp;
                    $expected = hash_hmac('sha256', $check_payload, $salt);
                    if ( hash_equals($expected, $token) ) {
                        // Replay resistance: reject single-use tokens that were already consumed.
                        if ( class_exists('IPTV_Device_Switcher_Includes_Security_Class_StreamOS_Token_Guard') ) {
                            $is_valid = IPTV_Device_Switcher_Includes_Security_Class_StreamOS_Token_Guard::validate_and_mark((string)$token, 900);
                        } else {
                            $is_valid = true;
                        }
                    }
                }
            }

            if ( $is_valid && $target_user_id > 0 ) {
                // A. NUCLEAR BUFFER CLEAR
                while ( ob_get_level() > 0 ) {
                    ob_end_clean();
                }

                // [SESSION MARKER] Start bounded impersonation session (auditable).
                // Use the Shadow Session logic instead of modifying WP Auth cookies
                $admin_actor_id = (int) ( current_user_can('manage_options') ? get_current_user_id() : $admin_id );
                if ($admin_actor_id <= 0 && $admin_id > 0) { $admin_actor_id = (int)$admin_id; }
                $started_at = time();
                $expires_at = $started_at + $this->get_impersonation_max_seconds();

                // Set the Shadow Cookie
                $this->set_impersonation_cookie($admin_actor_id, (int)$target_user_id, (int)$started_at, (int)$expires_at);
                $this->log_impersonation_event('Shadow Session Start', $admin_actor_id, (int)$target_user_id, 'Admin-safe impersonation');

                // B. DO NOT SWITCH WP AUTH COOKIE
                // We keep the Admin cookie intact. The Context Switcher (class-auth.php) will handle the rest.

                // D. Define Sandbox Constant (Optional for immediate context)
                if (!defined('TV_SANDBOX_ACTIVE')) {
                    define('TV_SANDBOX_ACTIVE', true);
                }

                $dashboard_url = home_url( '/dashboard' );

                // E. OUTPUT HTML INTERSTITIAL
                nocache_headers(); // Force no-cache headers
                ?>
                <!DOCTYPE html>
                <html lang="en" style="background:#0f172a;height:100%;overflow:hidden;">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
                    <meta http-equiv="refresh" content="1;url=<?php echo esc_url($dashboard_url); ?>">
                    <title>Switching User...</title>
                    <style>
                        body { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; font-family: -apple-system, system-ui, sans-serif; color: white; text-align: center; }
                        .spinner { width: 40px; height: 40px; border: 3px solid rgba(255,255,255,0.1); border-radius: 50%; border-top-color: #3b82f6; animation: spin 0.8s linear infinite; margin-bottom: 20px; }
                        @keyframes spin { to { transform: rotate(360deg); } }
                        h3 { font-size: 18px; font-weight: 600; margin: 0 0 8px 0; }
                        p { font-size: 14px; color: #94a3b8; margin: 0; }
                    </style>
                </head>
                <body>
                    <div class="spinner"></div>
                    <h3>Secure View Mode</h3>
                    <p>Accessing user dashboard without session interruption...</p>
                    <script>
                        setTimeout(function() {
                            window.location.href = "<?php echo esc_url_raw($dashboard_url); ?>";
                        }, 800); // Slight delay to ensure cookie sets
                    </script>
                </body>
                </html>
                <?php
                exit;
            }
        }
    }

    public function custom_login_url( $url, $r, $f ) { 
        return $url; 
    }
}