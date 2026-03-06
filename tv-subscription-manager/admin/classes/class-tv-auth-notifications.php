<?php
/**
 * EXACT FILE PATH:
 *   tv-subscription-manager/includes/class-tv-auth-notifications.php
 *
 * NEW FILE — place alongside the existing class-tv-notification-engine.php.
 *
 * Bootstrap: In tv-subscription-manager/tv-subscription-manager.php, after the
 * existing require_once lines, add:
 *
 *   require_once TV_MANAGER_PATH . 'includes/class-tv-auth-notifications.php';
 *   TV_Auth_Notifications::init();
 *
 * TV_MANAGER_PATH is defined as plugin_dir_path(__FILE__) in the main plugin file,
 * which resolves to: .../wp-content/plugins/tv-subscription-manager/
 *
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TV_Auth_Notifications {

    // ─────────────────────────────────────────────
    //  Bootstrap — call once from main plugin file
    // ─────────────────────────────────────────────

    public static function init(): void {
        // Replace the WP default "new user" email sent to the registrant
        add_filter( 'wp_new_user_notification_email', [ __CLASS__, 'filter_welcome_email' ], 10, 3 );

        // Replace the WP password-reset link email
        add_filter( 'retrieve_password_message', [ __CLASS__, 'filter_password_reset_message' ], 10, 4 );

        // Send a branded password-changed confirmation (WP does NOT do this by default)
        add_action( 'after_password_reset', [ __CLASS__, 'send_password_changed' ], 10, 1 );
    }

    // ─────────────────────────────────────────────
    //  Filter: welcome email (new registration)
    // ─────────────────────────────────────────────

    public static function filter_welcome_email( array $email, WP_User $user, string $blogname ): array {
        $context = [
            'user_name'  => $user->display_name ?: $user->user_login,
            'user_email' => $user->user_email,
            'brand_name' => get_bloginfo( 'name' ),
            'login_url'  => home_url( '/login' ),
        ];

        $template = self::load_template( 'auth-welcome', $context );
        $html     = self::render( 'auth-welcome', $context, [
            'badge_label' => 'Welcome',
            'accent_hex'  => '#6366f1',
            'badge_bg'    => '#ede9fe',
            'btn_url'     => home_url( '/login' ),
            'btn_text'    => $template['btn_text'],
        ]);

        $email['subject'] = $template['subject'];
        $email['message'] = $html;
        $email['headers'] = [ 'Content-Type: text/html; charset=UTF-8' ];

        return $email;
    }

    // ─────────────────────────────────────────────
    //  Filter: password reset email
    // ─────────────────────────────────────────────

    public static function filter_password_reset_message( string $message, string $key, string $user_login, WP_User $user ): string {
        $reset_url = network_site_url(
            'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode( $user_login ),
            'login'
        );

        $context = [
            'user_name'   => $user->display_name ?: $user->user_login,
            'reset_url'   => $reset_url,
            'brand_name'  => get_bloginfo( 'name' ),
            'expiry_time' => '24 hours',
        ];

        // Force HTML content type for this specific WP send (it defaults to text/plain)
        add_action( 'phpmailer_init', [ __CLASS__, '_force_html_once' ], 999 );

        return self::render( 'auth-password-reset', $context, [
            'badge_label' => 'Security',
            'accent_hex'  => '#f59e0b',
            'badge_bg'    => '#fef3c7',
            'btn_url'     => $reset_url,
            'btn_text'    => 'Reset My Password',
        ]);
    }

    /** Forces HTML content type for the next send, then removes itself. */
    public static function _force_html_once( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
        $phpmailer->ContentType = 'text/html';
        remove_action( 'phpmailer_init', [ __CLASS__, '_force_html_once' ], 999 );
    }

    // ─────────────────────────────────────────────
    //  Action: password changed confirmation
    // ─────────────────────────────────────────────

    public static function send_password_changed( WP_User $user ): void {
        $context = [
            'user_name'  => $user->display_name ?: $user->user_login,
            'user_email' => $user->user_email,
            'brand_name' => get_bloginfo( 'name' ),
            'login_url'  => home_url( '/login' ),
            'changed_at' => current_time( 'd M Y, H:i' ) . ' UTC',
        ];

        $template = self::load_template( 'auth-password-changed', $context );
        $html     = self::render( 'auth-password-changed', $context, [
            'badge_label' => 'Security Alert',
            'accent_hex'  => '#ef4444',
            'badge_bg'    => '#fee2e2',
            'btn_url'     => home_url( '/login' ),
            'btn_text'    => $template['btn_text'],
        ]);

        $from_email = get_option( 'tv_smtp_from_email', get_option( 'admin_email' ) );
        $from_name  = get_option( 'tv_smtp_from_name',  get_bloginfo( 'name' ) );

        wp_mail(
            $user->user_email,
            $template['subject'],
            $html,
            [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>',
            ]
        );
    }

    // ─────────────────────────────────────────────
    //  Public: direct test send (used by test panel)
    // ─────────────────────────────────────────────

    /**
     * Send a dummy auth template directly to $to_email.
     * Called by ajax_send_test_email() in class-tv-admin-settings.php.
     *
     * @param string $template_key  auth-welcome | auth-password-reset | auth-password-changed
     * @param string $to_email
     * @return bool
     */
    public static function send_test( string $template_key, string $to_email ): bool {
        $cfg_map = [
            'auth-welcome'          => [ 'label' => 'Welcome',        'hex' => '#6366f1', 'bg' => '#ede9fe', 'btn' => home_url('/login') ],
            'auth-password-reset'   => [ 'label' => 'Security',       'hex' => '#f59e0b', 'bg' => '#fef3c7', 'btn' => home_url('/login') ],
            'auth-password-changed' => [ 'label' => 'Security Alert', 'hex' => '#ef4444', 'bg' => '#fee2e2', 'btn' => home_url('/login') ],
        ];

        if ( ! isset( $cfg_map[ $template_key ] ) ) return false;

        $context = [
            'user_name'   => 'Test User',
            'user_email'  => $to_email,
            'brand_name'  => get_bloginfo( 'name' ),
            'login_url'   => home_url( '/login' ),
            'reset_url'   => home_url( '/?action=rp&key=DEMO_TEST_KEY&login=testuser' ),
            'expiry_time' => '24 hours',
            'changed_at'  => date( 'd M Y, H:i' ) . ' UTC',
        ];

        $cfg      = $cfg_map[ $template_key ];
        $template = self::load_template( $template_key, $context );
        $html     = self::render( $template_key, $context, [
            'badge_label' => $cfg['label'],
            'accent_hex'  => $cfg['hex'],
            'badge_bg'    => $cfg['bg'],
            'btn_url'     => $cfg['btn'],
            'btn_text'    => $template['btn_text'],
        ]);

        $from_email = get_option( 'tv_smtp_from_email', get_option( 'admin_email' ) );
        $from_name  = get_option( 'tv_smtp_from_name',  get_bloginfo( 'name' ) );

        return wp_mail(
            $to_email,
            '[TEST] ' . $template['subject'],
            $html,
            [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>',
            ]
        );
    }

    // ─────────────────────────────────────────────
    //  Internals
    // ─────────────────────────────────────────────

    /**
     * Load and variable-substitute a template from:
     *   TV_MANAGER_PATH . 'includes/templates/emails/auth/' . $key . '.php'
     */
    private static function load_template( string $key, array $context ): array {
        $path = TV_MANAGER_PATH . 'includes/templates/emails/auth/' . $key . '.php';
        $data = [ 'subject' => 'Notification', 'body' => '', 'btn_text' => 'Visit Dashboard' ];

        if ( file_exists( $path ) ) {
            $file_data = include $path;
            if ( is_array( $file_data ) ) {
                $data = array_merge( $data, $file_data );
            }
        }

        foreach ( $context as $k => $v ) {
            $data['subject'] = str_replace( '{{{' . $k . '}}}', (string) $v, $data['subject'] );
            $data['body']    = str_replace( '{{{' . $k . '}}}', (string) $v, $data['body'] );
        }

        return $data;
    }

    /**
     * Render a template through the shared email wrapper at:
     *   TV_MANAGER_PATH . 'includes/templates/emails/email-wrapper.php'
     */
    private static function render( string $key, array $context, array $opts ): string {
        $template     = self::load_template( $key, $context );
        $wrapper_path = TV_MANAGER_PATH . 'includes/templates/emails/email-wrapper.php';

        if ( ! file_exists( $wrapper_path ) ) {
            return '<html><body>' . $template['body'] . '</body></html>';
        }

        extract([
            'body_content' => $template['body'],
            'title'        => $template['subject'],
            'badge_label'  => $opts['badge_label'] ?? 'Notification',
            'badge_bg'     => $opts['badge_bg']    ?? '#ede9fe',
            'accent_hex'   => $opts['accent_hex']  ?? '#6366f1',
            'btn_url'      => $opts['btn_url']      ?? '',
            'btn_text'     => $opts['btn_text']     ?? 'Go to Dashboard',
        ]);

        ob_start();
        include $wrapper_path;
        return ob_get_clean();
    }
}
