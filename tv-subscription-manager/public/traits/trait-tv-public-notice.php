<?php
if (!defined('ABSPATH')) { exit; }

trait TV_Manager_Public_Trait_Notice {

public function render_payment_processing_notice() {
    if (!is_user_logged_in()) { return; }

    $user_id = get_current_user_id();
    $pay_id = intval(get_user_meta($user_id, self::USER_META_ACTIVE_PAY_ID, true));
    if (!$pay_id) { return; }

    $payment = $this->wpdb->get_row($this->wpdb->prepare(
        "SELECT id, status, proof_url FROM {$this->table_payments} WHERE id = %d AND user_id = %d",
        $pay_id,
        $user_id
    ));
    if (!$payment) {
        delete_user_meta($user_id, self::USER_META_ACTIVE_PAY_ID);
        return;
    }

    // Hide notice if the payment is fully done or cancelled.
    $terminal = [
        self::PAYMENT_STATUS_APPROVED,
        self::PAYMENT_STATUS_REJECTED,
        self::PAYMENT_STATUS_CANCELLED,
        self::PAYMENT_STATUS_LEGACY_COMPLETED,
        self::PAYMENT_STATUS_LEGACY_REJECTED,
    ];
    if (in_array($payment->status, $terminal, true)) {
        delete_user_meta($user_id, self::USER_META_ACTIVE_PAY_ID);
        return;
    }

    $control_url = esc_url($this->get_payment_control_url($pay_id));
    $cancel_nonce = wp_create_nonce('tv_cancel_payment_' . $pay_id);

    ?>
    <style>
        .tv-pay-notice {
            position: fixed;
            left: 18px;
            right: 18px;
            bottom: 18px;
            z-index: 999999;
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.92);
            color: #fff;
            padding: 14px 14px 14px 16px;
            box-shadow: 0 18px 40px rgba(0,0,0,0.25);
            backdrop-filter: blur(10px);
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
        }
        .tv-pay-notice-left { display:flex; gap:12px; align-items:center; min-width:0; }
        .tv-pay-dot { width:10px; height:10px; border-radius:999px; background:#f59e0b; box-shadow: 0 0 0 6px rgba(245,158,11,0.18); flex: 0 0 auto; }
        .tv-pay-title { font-weight:700; font-size:13px; line-height:1.2; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .tv-pay-sub { font-size:12px; opacity:0.85; margin-top:2px; }
        .tv-pay-actions { display:flex; gap:10px; align-items:center; flex:0 0 auto; }
        .tv-pay-link { color:#cbd5e1; text-decoration:none; font-size:12px; font-weight:700; }
        .tv-pay-x { background: transparent; border:0; color:#94a3b8; cursor:pointer; padding:8px; border-radius:10px; }
        .tv-pay-x:hover { background: rgba(148,163,184,0.14); color:#fff; }
        @media (min-width: 768px) {
            .tv-pay-notice { left: 24px; right: 24px; bottom: 24px; }
        }
    </style>

    <div class="tv-pay-notice" id="tv-pay-notice" data-control-url="<?php echo esc_attr($control_url); ?>" data-pay-id="<?php echo esc_attr($pay_id); ?>" data-cancel-nonce="<?php echo esc_attr($cancel_nonce); ?>">
        <div class="tv-pay-notice-left">
            <div class="tv-pay-dot" aria-hidden="true"></div>
            <div style="min-width:0;">
                <div class="tv-pay-title">Payment processing  complete setup</div>
                <div class="tv-pay-sub">Tap to continue your payment flow (upload proof, reopen gateway, or cancel).</div>
            </div>
        </div>
        <div class="tv-pay-actions">
            <a class="tv-pay-link" href="<?php echo $control_url; ?>">Open</a>
            <button class="tv-pay-x" type="button" aria-label="Dismiss" title="Dismiss">?</button>
        </div>
    </div>

    <script>
    (function(){
        const bar = document.getElementById('tv-pay-notice');
        if(!bar) return;

        const open = () => {
            const url = bar.getAttribute('data-control-url');
            if (url) { window.location.href = url; }
        };

        // Click anywhere (except buttons) opens control page.
        bar.addEventListener('click', (e) => {
            const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
            if (tag === 'button' || tag === 'a') return;
            open();
        });

        // Dismiss (visual only). We keep the meta so it reappears after reload by design.
        const x = bar.querySelector('.tv-pay-x');
        if (x) {
            x.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                bar.style.display = 'none';
            });
        }
    })();
    </script>
    <?php
}

}
