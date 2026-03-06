<?php
if ( ! defined( 'ABSPATH' ) ) exit;

trait StreamOS_Auth_Impersonation_Trait {

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
            $payload = $admin_id . '|' . $target_user_id . '|' . $started_at . '|' . $expires_at;
            $sig = $this->sign_impersonation_payload($payload);
            $raw = base64_encode($payload . '|' . $sig);
    
            // Secure, server-readable marker; does not replace WP auth.
            setcookie($this->get_impersonation_cookie_name(), $raw, $expires_at, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            $_COOKIE[$this->get_impersonation_cookie_name()] = $raw;
        }

    private function clear_impersonation_cookie() : void {
            $name = $this->get_impersonation_cookie_name();
            setcookie($name, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            unset($_COOKIE[$name]);
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

    public function enforce_impersonation_session_bounds() {
            // Clean termination endpoint (server-side): /dashboard?tv_end_impersonation=1
            if (isset($_GET['tv_end_impersonation']) && (string)$_GET['tv_end_impersonation'] !== '') {
                $name = $this->get_impersonation_cookie_name();
                $raw = isset($_COOKIE[$name]) ? (string)$_COOKIE[$name] : '';
    
                if (!empty($raw)) {
                    $decoded = base64_decode($raw, true);
                    if (is_string($decoded) && strpos($decoded, '|') !== false) {
                        $parts = explode('|', $decoded);
                        $admin_id = isset($parts[0]) ? (int)$parts[0] : 0;
                        $target_user_id = isset($parts[1]) ? (int)$parts[1] : 0;
                        if ($admin_id > 0 && $target_user_id > 0) {
                            delete_transient('tv_impersonation_admin_' . $admin_id);
                            $this->log_impersonation_event('Impersonation End', $admin_id, $target_user_id, 'Ended by request');
                        }
                    }
                }
    
                $this->clear_impersonation_cookie();
                wp_clear_auth_cookie();
                wp_set_current_user(0);
    
                wp_redirect(home_url('/login'));
                exit;
            }
    
            $name = $this->get_impersonation_cookie_name();
            if (empty($_COOKIE[$name])) return;
    
            $raw = (string) $_COOKIE[$name];
            $decoded = base64_decode($raw, true);
            if (!is_string($decoded) || strpos($decoded, '|') === false) {
                $this->clear_impersonation_cookie();
                return;
            }
    
            $parts = explode('|', $decoded);
            if (count($parts) < 5) {
                $this->clear_impersonation_cookie();
                return;
            }
    
            $admin_id = (int)$parts[0];
            $target_user_id = (int)$parts[1];
            $started_at = (int)$parts[2];
            $expires_at = (int)$parts[3];
            $sig = (string)$parts[4];
    
            $payload = $admin_id . '|' . $target_user_id . '|' . $started_at . '|' . $expires_at;
            $expected = $this->sign_impersonation_payload($payload);
    
            // If invalid/expired, force logout and clear marker.
            if ($admin_id <= 0 || $target_user_id <= 0 || $expires_at <= 0 || !hash_equals($expected, $sig) || time() > $expires_at) {
                delete_transient('tv_impersonation_admin_' . $admin_id);
                $this->clear_impersonation_cookie();
                wp_clear_auth_cookie();
                wp_set_current_user(0);
                return;
            }
    
            // Ensure a server-side record exists and matches this marker.
            $tkey = 'tv_impersonation_admin_' . $admin_id;
            $state = get_transient($tkey);
            if (!is_array($state) || (int)($state['target_user_id'] ?? 0) !== $target_user_id) {
                // Marker without state (or mismatched) is not allowed.
                $this->clear_impersonation_cookie();
                wp_clear_auth_cookie();
                wp_set_current_user(0);
                return;
            }
        }

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
                    $admin_actor_id = (int) ( current_user_can('manage_options') ? get_current_user_id() : $admin_id );
                    if ($admin_actor_id <= 0 && $admin_id > 0) { $admin_actor_id = (int)$admin_id; }
                    $started_at = time();
                    $expires_at = $started_at + $this->get_impersonation_max_seconds();
    
                    // Single active impersonation per admin (latest wins).
                    set_transient('tv_impersonation_admin_' . $admin_actor_id, array(
                        'admin_id' => $admin_actor_id,
                        'target_user_id' => (int)$target_user_id,
                        'started_at' => (int)$started_at,
                        'expires_at' => (int)$expires_at,
                    ), (int)$this->get_impersonation_max_seconds());
    
                    $this->set_impersonation_cookie($admin_actor_id, (int)$target_user_id, (int)$started_at, (int)$expires_at);
                    $this->log_impersonation_event('Impersonation Start', $admin_actor_id, (int)$target_user_id, 'Bounded session started');
    
                    // B. Switch User Context
                    wp_set_current_user($target_user_id);
                    
                    // C. Set Auth Cookie (FORCED FULL USER SESSION PARITY)
                    // Clear any existing auth cookies to prevent mixed-context sessions.
                    wp_clear_auth_cookie();
    
                    // Ensure WP globals fully reflect the impersonated user on this request.
                    global $current_user, $user_ID;
                    $user_ID = $target_user_id;
                    $current_user = get_userdata($target_user_id);
    
                    // Hard-clear user caches/object cache so no admin-context residue leaks.
                    clean_user_cache($target_user_id);
                    if (function_exists('wp_cache_flush')) { wp_cache_flush(); }
    
                    // Persistent cookie is critical on mobile browsers (tab restores, BFCache, etc.).
                    wp_set_auth_cookie($target_user_id, true, is_ssl());
                    
                    // [FORCE] Trigger standard login hook to ensure other plugins verify session
                    $user = get_userdata($target_user_id);
                    do_action('wp_login', $user->user_login, $user);
    
                    // D. Define Sandbox Constant
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
                        <h3>Accessing Dashboard</h3>
                        <p>Logging you in securely...</p>
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

}
