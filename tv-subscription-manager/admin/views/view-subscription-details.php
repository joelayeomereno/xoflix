<?php
if (!defined('ABSPATH')) { exit; }

// Pagination Logic (Current Page & URL Helper)
$current_page = max(1, isset($_GET['sub_page']) ? (int)$_GET['sub_page'] : 1);
$base_url = get_permalink();

// Helper to generate pagination links while preserving other query args
$mk_url = function($p) use ($base_url) {
    $args = $_GET;
    $args['sub_page'] = $p;
    return add_query_arg($args, $base_url);
};

// Check bounds (handled in controller, but good for display)
$has_next = $current_page < $total_pages;
$has_prev = $current_page > 1;
?>

<style>
    .tv-sub-details-wrap{max-width:900px;margin:40px auto;padding:16px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}
    .tv-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.06);}
    .tv-h{font-size:22px;font-weight:800;margin:0 0 8px;color:#0f172a;}
    .tv-meta{color:#64748b;font-size:13px;margin-bottom:16px;}
    .tv-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    .tv-field{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px;}
    .tv-l{font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;}
    .tv-v{font-size:14px;color:#0f172a;font-weight:600;word-break:break-word;}
    .tv-wide{grid-column:1 / -1;}
    .tv-pager{display:flex;align-items:center;justify-content:space-between;margin-top:20px;padding-top:20px;border-top:1px solid #e2e8f0;}
    .tv-btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 18px;border-radius:12px;border:1px solid #e2e8f0;background:#fff;text-decoration:none;color:#0f172a;font-weight:700;font-size:13px;transition:0.2s;}
    .tv-btn:hover:not([disabled]){border-color:#cbd5e1;box-shadow:0 2px 4px rgba(0,0,0,0.05);transform:translateY(-1px);}
    .tv-btn[disabled]{opacity:0.5;pointer-events:none;background:#f1f5f9;}
    .tv-page-ind{color:#64748b;font-size:13px;font-weight:600;}
    @media (max-width:720px){.tv-grid{grid-template-columns:1fr;}}
</style>

<div class="tv-sub-details-wrap">
    <div class="tv-card">
        <div class="tv-h">Subscription Details</div>
        <div class="tv-meta">Viewing subscription <strong>#<?php echo $sub ? (int)$sub->id : '-'; ?></strong></div>

        <?php if (!$sub): ?>
            <div class="tv-field tv-wide">
                <div class="tv-v" style="font-weight:700; color:#64748b; text-align:center; padding:20px;">
                    No subscriptions found.
                </div>
            </div>
        <?php else: ?>
            <div class="tv-grid">
                <div class="tv-field">
                    <div class="tv-l">Status</div>
                    <div class="tv-v">
                        <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;text-transform:uppercase;background:<?php echo $sub->status==='active'?'#dcfce7':'#f1f5f9';?>;color:<?php echo $sub->status==='active'?'#166534':'#475569';?>;">
                            <?php echo esc_html($sub->status); ?>
                        </span>
                    </div>
                </div>
                <div class="tv-field">
                    <div class="tv-l">Plan</div>
                    <div class="tv-v"><?php echo esc_html(!empty($sub->plan_name) ? (string)$sub->plan_name : 'N/A'); ?></div>
                </div>
                <div class="tv-field">
                    <div class="tv-l">Start Date</div>
                    <div class="tv-v"><?php echo esc_html(!empty($sub->start_date) ? (string)$sub->start_date : '-'); ?></div>
                </div>
                <div class="tv-field">
                    <div class="tv-l">End Date</div>
                    <div class="tv-v"><?php echo esc_html(!empty($sub->end_date) ? (string)$sub->end_date : '-'); ?></div>
                </div>

                <div class="tv-field">
                    <div class="tv-l">Username</div>
                    <div class="tv-v"><?php echo esc_html(!empty($sub->credential_user) ? (string)$sub->credential_user : '-'); ?></div>
                </div>
                <div class="tv-field">
                    <div class="tv-l">Password</div>
                    <div class="tv-v"><?php echo esc_html(!empty($sub->credential_pass) ? (string)$sub->credential_pass : '-'); ?></div>
                </div>
                <div class="tv-field tv-wide">
                    <div class="tv-l">Xtream Base URL</div>
                    <div class="tv-v">
                        <?php if(!empty($sub->credential_url)): ?>
                            <a href="<?php echo esc_url($sub->credential_url); ?>" target="_blank" style="color:#0f172a;text-decoration:underline;"><?php echo esc_html($sub->credential_url); ?></a>
                        <?php else: echo '-'; endif; ?>
                    </div>
                </div>
                <div class="tv-field tv-wide">
                    <div class="tv-l">M3U / Smart TV URL</div>
                    <div class="tv-v" style="white-space:normal;overflow-wrap:anywhere;font-family:monospace;font-size:12px;">
                        <?php echo esc_html(!empty($sub->credential_m3u) ? (string)$sub->credential_m3u : '-'); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Pagination Controls -->
        <div class="tv-pager">
            <a class="tv-btn" href="<?php echo esc_url($mk_url($current_page - 1)); ?>" <?php if(!$has_prev) echo 'disabled'; ?>>
                &larr; Previous
            </a>
            <div class="tv-page-ind">
                <?php echo $total_pages > 0 ? "Page $current_page of $total_pages" : "0 of 0"; ?>
            </div>
            <a class="tv-btn" href="<?php echo esc_url($mk_url($current_page + 1)); ?>" <?php if(!$has_next) echo 'disabled'; ?>>
                Next &rarr;
            </a>
        </div>
    </div>
</div>