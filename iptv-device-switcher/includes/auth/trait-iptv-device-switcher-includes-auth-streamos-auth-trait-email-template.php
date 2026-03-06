<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Module: iptv-device-switcher/includes/auth/trait-iptv-device-switcher-includes-auth-streamos-auth-trait-email-template.php
 *
 * Surgical extraction from StreamOS_Auth (class-auth.php).
 * No behavior changed; only relocated to reduce file size.
 */
trait StreamOS_Auth_Trait_Email_Template {

    /* --- EMAIL TEMPLATE ENGINE --- */
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
}
