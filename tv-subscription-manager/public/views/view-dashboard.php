<?php if (!defined('ABSPATH')) exit; ?>
<?php
// Dashboard is self-contained — all navigation uses home_url, NOT admin_url
// This prevents the /manage page from ever redirecting to WordPress admin
$current_user = wp_get_current_user();
$renew_url    = esc_url(add_query_arg('tv_flow', 'select_method', home_url('/')));
$signout_url  = esc_url(wp_logout_url(home_url('/')));
?>

<style>
/* -------------------------------------------
   TV MANAGER — Frontend Dashboard v9.0
   Matches admin UI 1:1. Dark/light sync.
   ------------------------------------------- */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

:root {
    --frd-primary:       #6366f1;
    --frd-primary-rgb:   99, 102, 241;
    --frd-accent:        #06b6d4;
    --frd-success:       #10b981;
    --frd-warning:       #f59e0b;
    --frd-danger:        #ef4444;
    --frd-purple:        #8b5cf6;
    /* Light */
    --frd-bg:            #f0f4ff;
    --frd-card:          #ffffff;
    --frd-text:          #0f172a;
    --frd-text-muted:    #64748b;
    --frd-border:        #e2e8f0;
    --frd-surface:       #ffffff;
    --frd-surface-active:#f1f5f9;
    --frd-glass:         rgba(255,255,255,0.92);
    --frd-shadow:        0 1px 4px rgba(99,102,241,0.08), 0 1px 2px rgba(0,0,0,0.05);
    --frd-shadow-lg:     0 8px 24px rgba(99,102,241,0.12);
    --frd-radius:        14px;
}

.frd-dark {
    --frd-bg:            #080e1a;
    --frd-card:          #0d1526;
    --frd-text:          #e2e8f0;
    --frd-text-muted:    #7c91af;
    --frd-border:        rgba(99,130,191,0.18);
    --frd-surface:       #0d1526;
    --frd-surface-active:rgba(99,130,191,0.1);
    --frd-glass:         rgba(13,21,38,0.94);
    --frd-shadow:        0 2px 8px rgba(0,0,0,0.5);
    --frd-shadow-lg:     0 8px 32px rgba(0,0,0,0.6);
}

.frd-wrap * { box-sizing: border-box; }
.frd-wrap {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    color: var(--frd-text);
    background: var(--frd-bg);
    min-height: 100vh;
    line-height: 1.5;
}

/* Top Bar */
.frd-topbar {
    background: var(--frd-glass);
    backdrop-filter: blur(16px) saturate(180%);
    border-bottom: 1px solid var(--frd-border);
    padding: 0 32px;
    height: 64px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 100;
    box-shadow: 0 1px 0 var(--frd-border), 0 2px 12px rgba(99,102,241,0.06);
}
.frd-brand {
    display: flex; align-items: center; gap: 10px;
    font-weight: 800; font-size: 17px; color: var(--frd-text); text-decoration: none;
}
.frd-brand-logo {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, var(--frd-primary), var(--frd-purple));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 18px;
    box-shadow: 0 2px 8px rgba(var(--frd-primary-rgb),0.4);
}
.frd-topbar-right { display: flex; align-items: center; gap: 10px; }
.frd-avatar {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, var(--frd-primary), var(--frd-purple));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 14px; flex-shrink: 0;
}
.frd-username { font-size: 13px; font-weight: 600; color: var(--frd-text); }
.frd-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 9px; font-size: 13px;
    font-weight: 600; cursor: pointer; transition: all 0.18s;
    text-decoration: none; border: 1px solid transparent; white-space: nowrap;
    font-family: inherit;
}
.frd-btn-primary {
    background: linear-gradient(135deg, var(--frd-primary), #4f46e5);
    color: #fff; box-shadow: 0 2px 8px rgba(var(--frd-primary-rgb),0.35);
}
.frd-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(var(--frd-primary-rgb),0.45); color: #fff; }
.frd-btn-secondary {
    background: var(--frd-surface); border-color: var(--frd-border); color: var(--frd-text);
}
.frd-btn-secondary:hover { background: var(--frd-surface-active); color: var(--frd-text); }
.frd-btn-icon {
    width: 36px; height: 36px; padding: 0; border-radius: 9px;
    border: 1px solid var(--frd-border); background: var(--frd-surface);
    color: var(--frd-text); display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all 0.18s;
}
.frd-btn-icon:hover { background: var(--frd-surface-active); transform: scale(1.05); }

/* Content */
.frd-content {
    max-width: 1100px; margin: 0 auto;
    padding: 32px 32px;
}
@media(max-width:768px) { .frd-content { padding: 20px 16px; } .frd-topbar { padding: 0 16px; } }

/* Page heading */
.frd-page-header {
    margin-bottom: 28px;
}
.frd-page-title { font-size: 24px; font-weight: 900; color: var(--frd-text); margin: 0 0 4px; letter-spacing: -0.03em; }
.frd-page-sub { color: var(--frd-text-muted); font-size: 14px; margin: 0; }

/* Stats row */
.frd-stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px; }
.frd-stat {
    background: var(--frd-card); border: 1px solid var(--frd-border);
    border-radius: var(--frd-radius); padding: 18px 20px;
    box-shadow: var(--frd-shadow); position: relative; overflow: hidden;
    transition: box-shadow 0.2s, transform 0.2s;
}
.frd-stat:hover { box-shadow: var(--frd-shadow-lg); transform: translateY(-1px); }
.frd-stat::before {
    content: ''; position: absolute; top: 0; left: 0;
    width: 100%; height: 3px;
    background: linear-gradient(90deg, var(--frd-primary), var(--frd-accent));
}
.frd-stat-label { font-size: 11px; font-weight: 700; color: var(--frd-text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
.frd-stat-value { font-size: 22px; font-weight: 900; color: var(--frd-text); letter-spacing: -0.03em; }

/* Grid */
.frd-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
@media(max-width:768px) { .frd-grid { grid-template-columns: 1fr; } }

/* Card */
.frd-card {
    background: var(--frd-card); border: 1px solid var(--frd-border);
    border-radius: var(--frd-radius); box-shadow: var(--frd-shadow);
    overflow: hidden;
}
.frd-card-head {
    padding: 16px 20px; border-bottom: 1px solid var(--frd-border);
    display: flex; align-items: center; justify-content: space-between;
    background: linear-gradient(135deg, rgba(var(--frd-primary-rgb),0.03), transparent);
}
.frd-card-title {
    font-size: 14px; font-weight: 700; color: var(--frd-text); margin: 0;
    display: flex; align-items: center; gap: 8px;
}
.frd-card-body { padding: 20px; }

/* Plan sub card */
.frd-plan-box {
    background: var(--frd-surface-active); border: 1px solid var(--frd-border);
    border-radius: 12px; padding: 16px; margin-bottom: 14px;
}
.frd-plan-name { font-size: 18px; font-weight: 800; color: var(--frd-primary); margin-bottom: 6px; }
.frd-plan-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; font-size: 12.5px; color: var(--frd-text-muted); }
.frd-badge {
    display: inline-flex; align-items: center; padding: 3px 10px;
    border-radius: 99px; font-size: 11px; font-weight: 700;
}
.frd-badge-active   { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
.frd-badge-expired  { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
.frd-badge-pending  { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }

/* Credential row */
.frd-cred-row {
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
    padding: 10px 12px; background: var(--frd-card); border: 1px solid var(--frd-border);
    border-radius: 10px; margin-bottom: 8px;
}
.frd-cred-row:last-child { margin-bottom: 0; }
.frd-cred-label { font-size: 10px; font-weight: 700; color: var(--frd-text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px; }
.frd-cred-value { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 13px; word-break: break-all; color: var(--frd-text); }
.frd-copy-btn {
    flex-shrink: 0; padding: 5px 12px; border-radius: 7px; font-size: 12px;
    font-weight: 600; cursor: pointer; transition: all 0.15s;
    background: rgba(var(--frd-primary-rgb),0.08); color: var(--frd-primary);
    border: 1px solid rgba(var(--frd-primary-rgb),0.2); font-family: inherit;
}
.frd-copy-btn:hover { background: var(--frd-primary); color: #fff; }

/* Transaction history */
.frd-tx-item {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 0; border-bottom: 1px solid var(--frd-border); font-size: 13px;
}
.frd-tx-item:last-child { border-bottom: none; }
.frd-tx-plan { font-weight: 700; color: var(--frd-text); margin-bottom: 2px; }
.frd-tx-date { font-size: 11px; color: var(--frd-text-muted); }
.frd-tx-amount { font-weight: 700; color: var(--frd-text); text-align: right; }
.frd-tx-status-completed { color: var(--frd-success); font-size: 11px; font-weight: 700; }
.frd-tx-status-pending   { color: var(--frd-warning); font-size: 11px; font-weight: 700; }
.frd-tx-status-rejected  { color: var(--frd-danger);  font-size: 11px; font-weight: 700; }

/* Empty state */
.frd-empty { text-align: center; padding: 36px 20px; color: var(--frd-text-muted); }
.frd-empty-icon { font-size: 40px; margin-bottom: 10px; opacity: 0.3; }
.frd-empty p { margin: 0 0 14px; font-size: 14px; }
</style>

<div class="frd-wrap" id="frd-root">

    <!-- TOP BAR -->
    <div class="frd-topbar">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="frd-brand">
            <div class="frd-brand-logo">&#128249;</div>
            XOFLIX
        </a>
        <div class="frd-topbar-right">
            <!-- Theme Toggle -->
            <button type="button" class="frd-btn-icon" id="frd-theme-btn" onclick="frdToggleTheme()" title="Toggle Dark/Light Mode">
                <span id="frd-theme-icon" style="font-size:16px;">&#9728;</span>
            </button>
            <!-- User info -->
            <div class="frd-avatar"><?php echo esc_html(mb_substr($current_user->display_name, 0, 1)); ?></div>
            <span class="frd-username"><?php echo esc_html($current_user->display_name); ?></span>
            <!-- Sign out -->
            <a href="<?php echo $signout_url; ?>" class="frd-btn frd-btn-secondary" style="font-size:12px;padding:6px 12px;">Sign Out</a>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="frd-content">

        <!-- Page Header -->
        <div class="frd-page-header">
            <h1 class="frd-page-title">My Dashboard</h1>
            <p class="frd-page-sub">Welcome back! Here's your subscription overview.</p>
        </div>

        <!-- Quick Stats -->
        <div class="frd-stats-row">
            <?php
            $sub_status_label = 'No Subscription';
            $sub_status_class = 'frd-badge-expired';
            $sub_expires      = '—';
            $total_paid       = 0;
            $total_tx         = is_array($payments) ? count($payments) : 0;

            if ($active_sub) {
                $sub_status_label = 'Active';
                $sub_status_class = 'frd-badge-active';
                $sub_expires      = date('M d, Y', strtotime($active_sub->end_date));
            }
            if (is_array($payments)) {
                foreach ($payments as $p) { $total_paid += floatval($p->amount); }
            }
            $currency_sym = '$';
            if (!empty($payments[0]->currency) && $payments[0]->currency !== 'USD') {
                $currency_sym = $payments[0]->currency . ' ';
            }
            ?>
            <div class="frd-stat">
                <div class="frd-stat-label">Subscription</div>
                <div class="frd-stat-value">
                    <span class="frd-badge <?php echo $sub_status_class; ?>"><?php echo esc_html($sub_status_label); ?></span>
                </div>
            </div>
            <div class="frd-stat">
                <div class="frd-stat-label">Expires</div>
                <div class="frd-stat-value"><?php echo esc_html($sub_expires); ?></div>
            </div>
            <div class="frd-stat">
                <div class="frd-stat-label">Total Paid</div>
                <div class="frd-stat-value"><?php echo esc_html($currency_sym . number_format($total_paid, 2)); ?></div>
            </div>
            <div class="frd-stat">
                <div class="frd-stat-label">Transactions</div>
                <div class="frd-stat-value"><?php echo esc_html($total_tx); ?></div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="frd-grid">

            <!-- Current Plan Card -->
            <div class="frd-card">
                <div class="frd-card-head">
                    <h3 class="frd-card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--frd-primary);flex-shrink:0;" aria-hidden="true"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                        Current Plan
                    </h3>
                    <a href="<?php echo $renew_url; ?>" class="frd-btn frd-btn-primary" style="font-size:12px;padding:6px 14px;">
                        + Extend
                    </a>
                </div>
                <div class="frd-card-body">
                    <?php if ($active_sub): ?>
                        <div class="frd-plan-box">
                            <div class="frd-plan-name"><?php echo esc_html($active_sub->plan_name); ?></div>
                            <div class="frd-plan-meta">
                                <span>Expires: <?php echo date('M d, Y', strtotime($active_sub->end_date)); ?></span>
                                <span class="frd-badge frd-badge-active">Active</span>
                            </div>
                        </div>

                        <?php if ($active_sub->credential_user): ?>
                            <!-- Credentials -->
                            <div class="frd-cred-row">
                                <div>
                                    <div class="frd-cred-label">Username</div>
                                    <div class="frd-cred-value" id="frd-cred-user"><?php echo esc_html($active_sub->credential_user); ?></div>
                                </div>
                                <button class="frd-copy-btn" onclick="frdCopy('frd-cred-user', this)">Copy</button>
                            </div>
                            <?php if ($active_sub->credential_url): ?>
                            <div class="frd-cred-row">
                                <div>
                                    <div class="frd-cred-label">Host URL</div>
                                    <div class="frd-cred-value" id="frd-cred-url"><?php echo esc_html($active_sub->credential_url); ?></div>
                                </div>
                                <button class="frd-copy-btn" onclick="frdCopy('frd-cred-url', this)">Copy</button>
                            </div>
                            <?php endif; ?>
                            <?php if ($active_sub->credential_m3u): ?>
                            <div class="frd-cred-row">
                                <div style="min-width:0;flex:1;">
                                    <div class="frd-cred-label">M3U / Smart TV URL</div>
                                    <div class="frd-cred-value" id="frd-cred-m3u" style="font-size:11px;opacity:0.85;"><?php echo esc_html($active_sub->credential_m3u); ?></div>
                                </div>
                                <button class="frd-copy-btn" onclick="frdCopy('frd-cred-m3u', this)">Copy</button>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="font-size:13px;color:var(--frd-text-muted);font-style:italic;text-align:center;padding:16px 0;">
                                Credentials pending approval.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="frd-empty">
                            <div class="frd-empty-icon">??</div>
                            <p>No active subscription found.</p>
                            <a href="<?php echo $renew_url; ?>" class="frd-btn frd-btn-primary">Subscribe Now</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Transactions Card -->
            <div class="frd-card">
                <div class="frd-card-head">
                    <h3 class="frd-card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--frd-primary);flex-shrink:0;" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        Recent Transactions
                    </h3>
                </div>
                <div class="frd-card-body" style="padding:0;">
                    <?php if ($payments): ?>
                        <div style="padding:0 20px;">
                            <?php foreach ($payments as $pay):
                                $pay_display = '$' . number_format(floatval($pay->amount), 2);
                                if (!empty($pay->currency) && $pay->currency !== 'USD') {
                                    $pay_display = number_format(floatval($pay->amount), 2) . ' ' . $pay->currency;
                                }
                                $stat = strtolower($pay->status);
                                if ($stat === 'awaiting_proof' || $stat === 'in_progress') $stat_class = 'frd-tx-status-pending';
                                elseif (in_array($stat, ['approved','completed'])) $stat_class = 'frd-tx-status-completed';
                                elseif ($stat === 'rejected') $stat_class = 'frd-tx-status-rejected';
                                else $stat_class = 'frd-tx-status-pending';
                                $stat_label = ucfirst(str_replace('_', ' ', $pay->status));
                                if ($pay->status === 'AWAITING_PROOF') $stat_label = 'Upload Proof';
                                elseif ($pay->status === 'IN_PROGRESS') $stat_label = 'Pending';
                            ?>
                            <div class="frd-tx-item">
                                <div>
                                    <div class="frd-tx-plan"><?php echo esc_html($pay->plan_name); ?></div>
                                    <div class="frd-tx-date"><?php echo date('M d, Y', strtotime($pay->date)); ?></div>
                                </div>
                                <div style="text-align:right;">
                                    <div class="frd-tx-amount"><?php echo esc_html($pay_display); ?></div>
                                    <div class="<?php echo $stat_class; ?>"><?php echo esc_html($stat_label); ?></div>
                                </div>
                                <?php if (in_array($pay->status, ['AWAITING_PROOF','IN_PROGRESS'])): ?>
                                    <a href="<?php echo esc_url(add_query_arg(['tv_flow'=>'payment_pending','pay_id'=>$pay->id], home_url('/'))); ?>"
                                       style="margin-left:10px;color:var(--frd-primary);text-decoration:none;font-size:18px;font-weight:700;" title="Continue Payment">?</a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="frd-empty">
                            <div class="frd-empty-icon">??</div>
                            <p>No payment history yet.</p>
                            <a href="<?php echo $renew_url; ?>" class="frd-btn frd-btn-primary">Make a Payment</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.frd-grid -->
    </div><!-- /.frd-content -->
</div><!-- /.frd-wrap -->

<script>
// -- THEME SYNC (uses same localStorage key as admin) --
(function(){
    const root = document.getElementById('frd-root');
    const icon = document.getElementById('frd-theme-icon');
    const saved = localStorage.getItem('tv_theme');
    if (saved === 'dark' && root) {
        root.classList.add('frd-dark');
        if (icon) icon.textContent = '?';
    }
})();
function frdToggleTheme() {
    const root = document.getElementById('frd-root');
    const icon = document.getElementById('frd-theme-icon');
    const isDark = root.classList.toggle('frd-dark');
    localStorage.setItem('tv_theme', isDark ? 'dark' : 'light');
    if (icon) icon.textContent = isDark ? '?' : '??';
}

// -- COPY TO CLIPBOARD --
function frdCopy(elId, btn) {
    const el = document.getElementById(elId);
    if (!el) return;
    const txt = el.textContent || '';
    if (navigator.clipboard) { navigator.clipboard.writeText(txt); }
    if (btn) {
        const orig = btn.textContent;
        btn.textContent = 'Copied!';
        btn.style.background = '#10b981'; btn.style.color = '#fff'; btn.style.borderColor = '#10b981';
        setTimeout(function() {
            btn.textContent = orig;
            btn.style.background = ''; btn.style.color = ''; btn.style.borderColor = '';
        }, 1800);
    }
}
</script>