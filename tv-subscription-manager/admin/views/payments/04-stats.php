<?php if (!defined('ABSPATH')) { exit; } ?>

    <!-- Financial Summary (GMT+1, Week: Mon-Sun) -->
    <?php if (isset($tx_summaries) && is_array($tx_summaries)): ?>
        <div style="padding:16px 24px; border-bottom:1px solid var(--tv-border); background:var(--tv-surface);">
            <div style="display:grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap:12px;">
                <?php foreach ($tx_summaries as $k => $meta): ?>
                    <div class="tv-card" style="padding:12px; border:1px solid var(--tv-border); margin-bottom: 0;">
                        <div style="font-size:11px; color:var(--tv-text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">
                            <?php echo esc_html($meta['label']); ?>
                        </div>
                        <div style="margin-top:6px; font-size:16px; font-weight:900; color:var(--tv-text); line-height:1.2;">
                            <?php
                                $totals = isset($meta['totals']) ? (array)$meta['totals'] : [];
                                $usd_val = isset($totals['USD']) ? $totals['USD'] : 0;
                                $ngn_val = isset($totals['NGN']) ? $totals['NGN'] : 0;
                            ?>
                            <!-- Toggled Summary Values -->
                            <span class="tv-val-usd">$<?php echo number_format($usd_val, 2); ?></span>
                            <span class="tv-val-ngn" style="display:none;">&#8358;<?php echo number_format($ngn_val, 2); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:10px; font-size:11px; color:var(--tv-text-muted);">
                Week is calculated Monday - Sunday. Timezone used: GMT+1.
            </div>
        </div>
    <?php endif; ?>
