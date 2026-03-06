<?php
if (!defined('ABSPATH')) { exit; }

trait TV_Manager_Public_Trait_Flow {

    public function maybe_render_flow_endpoint() {
        $flow = get_query_var('tv_flow');
        if (empty($flow)) {
            return;
        }

        if ($flow === 'payment_return') {
            $this->handle_payment_return_endpoint();
            exit;
        }

        // Map flows to shortcodes
        $map = array(
            'select_method'      => 'tv_select_payment_method',
            'payment'            => 'tv_payment_page',
            'upload_proof'       => 'tv_upload_payment_proof',
            'payment_control'    => 'tv_payment_control',
            'payment_pending'    => 'tv_payment_lockdown', 
            'subscription_plans' => 'tv_subscription_plans', // [NEW] Premium Plans Flow
        );

        if (!isset($map[$flow])) {
            return;
        }

        // --- NUCLEAR ISOLATION START ---
        // Prevents theme/plugin styles from breaking our flow pages
        add_action('wp_enqueue_scripts', function() {
            global $wp_scripts, $wp_styles;
            // Allow jQuery only
            foreach ($wp_scripts->queue as $handle) {
                if ($handle !== 'jquery' && $handle !== 'jquery-core') {
                    wp_dequeue_script($handle);
                    wp_deregister_script($handle);
                }
            }
            // Remove styles
            foreach ($wp_styles->queue as $handle) {
                wp_dequeue_style($handle);
                wp_deregister_style($handle);
            }
        }, 9999);
        
        add_filter('show_admin_bar', '__return_false');
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        // --- NUCLEAR ISOLATION END ---

        nocache_headers();
        status_header(200);
        header('Content-Type: text/html; charset=' . get_bloginfo('charset'), true);

        $title = 'TV Manager';
        if ($flow === 'select_method') { $title = 'Select Payment Method'; }
        if ($flow === 'payment') { $title = 'Complete Your Payment'; }
        if ($flow === 'upload_proof') { $title = 'Upload Payment Proof'; }
        if ($flow === 'payment_pending') { $title = 'Action Required'; }
        if ($flow === 'subscription_plans') { $title = 'Plans & Pricing'; }

        // Execute shortcode to capture output
        $body = do_shortcode('[' . $map[$flow] . ']');

        ?>
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="<?php echo esc_attr(get_bloginfo('charset')); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
            <title><?php echo esc_html($title); ?></title>
            
            <style>
                html, body, div, span, applet, object, iframe, h1, h2, h3, h4, h5, h6, p, blockquote, pre, a, abbr, acronym, address, big, cite, code, del, dfn, em, img, ins, kbd, q, s, samp, small, strike, strong, sub, sup, tt, var, b, u, i, center, dl, dt, dd, ol, ul, li, fieldset, form, label, legend, table, caption, tbody, tfoot, thead, tr, th, td, article, aside, canvas, details, embed, figure, figcaption, footer, header, hgroup, menu, nav, output, ruby, section, summary, time, mark, audio, video {
                    margin: 0; padding: 0; border: 0; font-size: 100%; font: inherit; vertical-align: baseline;
                }
                html, body { width: 100%; height: 100%; background: #f8fafc; -webkit-font-smoothing: antialiased; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.5; color: #0f172a; }
                *, *:before, *:after { box-sizing: border-box; }
                body { opacity: 1 !important; visibility: visible !important; }
            </style>

            <?php wp_head(); ?>
        </head>
        <body class="tv-flow-page <?php echo esc_attr('tv-flow-' . $flow); ?>">
            <div id="tv-flow-app-root">
                <?php echo $body; ?>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }

    private function handle_payment_return_endpoint() {
        nocache_headers();

        $pay_id = isset($_GET['pay_id']) ? intval($_GET['pay_id']) : 0;
        $tx_ref = isset($_GET['tx_ref']) ? sanitize_text_field(wp_unslash($_GET['tx_ref'])) : '';
        if (empty($tx_ref) && isset($_GET['transaction_id'])) {
            $tx_ref = sanitize_text_field(wp_unslash($_GET['transaction_id']));
        }

        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        $payment = null;

        if ($pay_id && $user_id) {
            $payment = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->table_payments} WHERE id = %d AND user_id = %d",
                $pay_id,
                $user_id
            ));
        }

        if (!$payment && !empty($tx_ref)) {
            if ($user_id) {
                $payment = $this->wpdb->get_row($this->wpdb->prepare(
                    "SELECT * FROM {$this->table_payments} WHERE transaction_id = %s AND user_id = %d ORDER BY id DESC LIMIT 1",
                    $tx_ref,
                    $user_id
                ));
            } else {
                $payment = $this->wpdb->get_row($this->wpdb->prepare(
                    "SELECT * FROM {$this->table_payments} WHERE transaction_id = %s ORDER BY id DESC LIMIT 1",
                    $tx_ref
                ));
            }
        }

        if ($payment) {
            if (empty($payment->attempted_at)) {
                $this->wpdb->update($this->table_payments, [
                    'attempted_at' => current_time('mysql'),
                ], ['id' => (int) $payment->id], ['%s'], ['%d']);
            }
            if ($user_id) {
                update_user_meta($user_id, '_tv_active_pay_id', (int) $payment->id);
            }
            $pay_id = (int) $payment->id;
        }

        $lockdown_url = add_query_arg([
            'tv_flow' => 'payment_pending',
            'pay_id' => (int) $pay_id,
        ], home_url('/'));

        wp_safe_redirect($lockdown_url);
        exit;
    }

    private function get_flow_page_url($key) {
        $map = array(
            'select_method' => intval(get_option('tv_select_method_page_id', 0)),
            'payment'       => intval(get_option('tv_payment_page_id', 0)),
            'upload_proof'  => intval(get_option('tv_upload_proof_page_id', 0)),
            'payment_control' => intval(get_option('tv_payment_control_page_id', 0)),
            'payment_return' => intval(get_option('tv_payment_return_page_id', 0)),
            'plans'         => intval(get_option('tv_plans_page_id', 0)),
            'dashboard'     => intval(get_option('tv_dashboard_page_id', 0)),
        );

        if (empty($map[$key])) {
            return '';
        }

        $url = get_permalink($map[$key]);
        return $url ? $url : '';
    }

    private function get_payment_control_url($pay_id) {
        $url = $this->get_flow_page_url('payment_control');
        if (empty($url)) {
            $url = add_query_arg('tv_flow', 'payment_control', home_url('/'));
        }
        return add_query_arg(['pay_id' => intval($pay_id)], $url);
    }
}
