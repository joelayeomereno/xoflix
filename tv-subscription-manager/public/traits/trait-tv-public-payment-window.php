<?php
if (!defined('ABSPATH')) { exit; }

trait TV_Manager_Public_Trait_Payment_Window {

    public function maybe_render_payment_window() {
        if (!is_user_logged_in()) { return; }

        if (!isset($_GET['tv_payment_window']) || $_GET['tv_payment_window'] != '1') {
            return;
        }

        $pay_id = isset($_GET['pay_id']) ? intval($_GET['pay_id']) : 0;
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';

        if (!$pay_id || empty($nonce) || !wp_verify_nonce($nonce, 'tv_payment_window_' . $pay_id)) {
            status_header(403);
            exit('Forbidden');
        }

        $user_id = get_current_user_id();
        $payment = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM $this->table_payments WHERE id = %d AND user_id = %d", $pay_id, $user_id));
        if (!$payment) {
            status_header(404);
            exit('Not found');
        }

        $method = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM $this->table_methods WHERE name = %s", $payment->method));
        if (!$method || empty($method->link)) {
            status_header(400);
            exit('Payment method link not configured.');
        }

        // PAYLOAD ERADICATED: Using raw method link directly
        $external = esc_url_raw(trim($method->link));
        
        if (!headers_sent()) {
            wp_redirect($external);
            exit;
        }

        nocache_headers();
        header('Content-Type: text/html; charset=' . get_bloginfo('charset'), true);
        ?>
        <!doctype html>
        <html>
        <head>
            <meta charset="<?php echo esc_attr(get_bloginfo('charset')); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Redirecting...</title>
            <script>window.location.href = "<?php echo esc_js($external); ?>";</script>
        </head>
        <body>
            <p>Redirecting to payment...</p>
        </body>
        </html>
        <?php
        exit;
    }
}
