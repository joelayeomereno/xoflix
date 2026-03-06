<?php
if (!defined('ABSPATH')) { exit; }

trait TV_Admin_Users_Trait_Impersonation {

    public function handle_impersonation_start() {
        if (isset($_GET['action']) && $_GET['action'] === 'start_impersonation' && isset($_GET['user_id'])) {
            check_admin_referer('start_impersonation_' . $_GET['user_id']);
            if (!current_user_can('manage_options')) return;

            $target_user_id = intval($_GET['user_id']);
            $admin_id = get_current_user_id();
            $user = get_userdata($target_user_id);

            if (!$user) return;

            $timestamp = time();
            $salt = wp_salt('auth');
            $token_payload = $admin_id . '|' . $target_user_id . '|' . $timestamp;
            $token = hash_hmac('sha256', $token_payload, $salt);

            $sessions = get_transient('tv_admin_sessions_' . $admin_id) ?: [];
            if (count($sessions) >= 5) array_shift($sessions);
            
            $sessions[$target_user_id] = [
                'id' => $target_user_id,
                'name' => $user->display_name ?: $user->user_login,
                'email' => $user->user_email,
                'start_time' => $timestamp,
                'token' => $token
            ];
            set_transient('tv_admin_sessions_' . $admin_id, $sessions, 12 * HOUR_IN_SECONDS);

            $this->log_event('Impersonation Started', "Started session for User ID: $target_user_id");

            $sandbox_url = add_query_arg([
                'tv_sandbox' => 1,
                'tv_user' => $target_user_id,
                'tv_admin' => $admin_id,
                'tv_token' => $token,
                'tv_time' => $timestamp
            ], home_url('/dashboard'));

            wp_redirect($sandbox_url);
            exit;
        }
    }

    public function handle_impersonation_close() {
        if (isset($_GET['action']) && $_GET['action'] === 'close_session' && isset($_GET['target_id'])) {
            check_admin_referer('close_session_' . $_GET['target_id']);
            
            $admin_id = get_current_user_id();
            $target_id = intval($_GET['target_id']);
            
            $sessions = get_transient('tv_admin_sessions_' . $admin_id) ?: [];
            if (isset($sessions[$target_id])) {
                unset($sessions[$target_id]);
                set_transient('tv_admin_sessions_' . $admin_id, $sessions, 12 * HOUR_IN_SECONDS);
            }
            
            wp_redirect(remove_query_arg(['action', 'target_id', '_wpnonce']));
            exit;
        }
    }

    public function render_active_impersonations_bar() {
        $admin_id = get_current_user_id();
        $sessions = get_transient('tv_admin_sessions_' . $admin_id);

        if (!empty($sessions)) {
            echo '<div class="tv-sandbox-bar">';
            echo '<div class="tv-sandbox-title"><span class="dashicons dashicons-admin-users"></span> Active Sandbox Sessions</div>';
            echo '<div class="tv-sandbox-list">';
            foreach ($sessions as $uid => $sess) {
                $token = $sess['token'];
                $sandbox_url = add_query_arg([
                    'tv_sandbox' => 1,
                    'tv_user' => $uid,
                    'tv_admin' => $admin_id,
                    'tv_token' => $token,
                    'tv_time' => $sess['start_time']
                ], home_url('/dashboard'));

                $close_url = wp_nonce_url(add_query_arg(['action' => 'close_session', 'target_id' => $uid]), 'close_session_' . $uid);

                echo '<div class="tv-sandbox-item">';
                echo '<div class="tv-sandbox-avatar">' . strtoupper(substr($sess['name'], 0, 1)) . '</div>';
                echo '<div class="tv-sandbox-info">';
                echo '<span class="tv-sandbox-name">' . esc_html($sess['name']) . '</span>';
                echo '<span class="tv-sandbox-time">Started ' . human_time_diff($sess['start_time']) . ' ago</span>';
                echo '</div>';
                echo '<div class="tv-sandbox-actions">';
                echo '<a href="' . esc_url($sandbox_url) . '" target="_blank" class="tv-btn-icon" title="Switch to Tab"><span class="dashicons dashicons-external"></span></a>';
                echo '<a href="' . esc_url($close_url) . '" class="tv-btn-icon delete" title="Close Session"><span class="dashicons dashicons-no"></span></a>';
                echo '</div>';
                echo '</div>';
            }
            echo '</div></div>';
        }
    }

}
