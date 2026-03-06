<?php
/**
 * Template Name: Dashboard - Subscription Detail
 * Path: /templates/dashboard/subscription.php
 * Description: Server-side rendered view for user subscriptions.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Security & Auth Check
if ( ! is_user_logged_in() ) { 
    echo '<div class="p-6 text-center text-slate-500">Please log in to view subscriptions.</div>'; 
    return; 
}

global $wpdb;
$current_user = wp_get_current_user();
$uid = $current_user->ID;

// 2. Data Fetching (Surgical extraction from main logic)
$subs_raw = $wpdb->get_results("
    SELECT s.*, p.name as plan_name, p.price, p.duration_days 
    FROM {$wpdb->prefix}tv_subscriptions s 
    LEFT JOIN {$wpdb->prefix}tv_plans p ON s.plan_id = p.id 
    WHERE s.user_id = $uid 
    AND s.status IN ('active', 'expired')
    ORDER BY s.id DESC
");

// 2b. Pagination (one subscription per page)
$total_subs = is_array($subs_raw) ? count($subs_raw) : 0;
$sub_page = isset($_GET['sub_page']) ? max(1, intval($_GET['sub_page'])) : 1;
if ($total_subs > 0) {
    if ($sub_page > $total_subs) { $sub_page = $total_subs; }
    $subs_page = array( $subs_raw[$sub_page - 1] );
} else {
    $subs_page = array();
}

// 3. View Helpers
function streamos_status_badge($status) {
    $colors = [
        'active'  => 'bg-emerald-100/80 text-emerald-700 border-emerald-200',
        'pending' => 'bg-amber-100/80 text-amber-700 border-amber-200',
        'expired' => 'bg-slate-100 text-slate-500 border-slate-200',
    ];
    $cls = $colors[strtolower($status)] ?? 'bg-slate-100 text-slate-600';
    return '<span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border ' . $cls . '">' . ucfirst($status) . '</span>';
}

?>

<div class="max-w-5xl mx-auto space-y-8 animate-in fade-in zoom-in duration-300">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">My Subscriptions</h1>
            <p class="text-slate-500 font-medium mt-1">Manage your active plans and connections.</p>
        </div>
        <a href="?view=shop" class="px-6 py-3 bg-blue-600 text-white font-bold rounded-xl text-sm hover:bg-blue-700 shadow-lg shadow-blue-200 transition-colors flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            Purchase Plan
        </a>
    </div>

    <!-- Subscriptions Grid -->
    <div class="grid grid-cols-1 gap-6">
        
        <?php if ( empty( $subs_raw ) ) : ?>
            <div class="p-12 text-center bg-white rounded-[2rem] border border-slate-200 shadow-sm">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">No Active Plans</h3>
                <p class="text-slate-500 mb-6">You don't have any subscriptions yet.</p>
                <a href="?view=shop" class="text-blue-600 font-bold hover:underline">Browse Store &rarr;</a>
            </div>
        <?php else : ?>
            
            <?php foreach ( $subs_page as $s ) : 
                // Logic
                $end_ts = strtotime($s->end_date);
                $days_left = ceil(($end_ts - time()) / 86400);
                $start_nice = date('M d, Y', strtotime($s->start_date));
                $end_nice = date('M d, Y', strtotime($s->end_date));
                
                // Creds Generation (Consistent Mock)
                $username_gen = strtolower($current_user->user_login) . '_' . $s->id;
                $password_gen = substr(md5($current_user->user_pass . $s->id), 0, 10);
                $host_url = 'http://line.streamos.tv';
                $m3u_url = "{$host_url}/get.php?username={$username_gen}&password={$password_gen}&type=m3u_plus&output=ts";
            ?>
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden hover:shadow-md transition-shadow">
                
                <!-- Card Header -->
                <div class="p-8 border-b border-slate-50 bg-slate-50/30 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-4 mb-2">
                            <h2 class="text-2xl font-bold text-slate-900"><?php echo esc_html($s->plan_name); ?></h2>
                            <?php echo streamos_status_badge($s->status); ?>
                        </div>
                        <p class="text-slate-500 text-sm font-medium flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> 
                            Billed every <?php echo esc_html($s->duration_days); ?> Days
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-8 bg-white px-6 py-3 rounded-2xl border border-slate-100 shadow-sm">
                        <div class="text-right">
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Expires</p>
                            <p class="text-sm font-bold text-slate-900"><?php echo $end_nice; ?></p>
                        </div>
                        <div class="h-8 w-px bg-slate-100"></div>
                        <div class="text-right">
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Price</p>
                            <p class="text-lg font-black text-slate-900">$<?php echo esc_html($s->price); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Credentials Section -->
                <div class="px-8 py-8">
                    <?php if($s->status === 'active'): ?>
                    <div class="p-6 bg-slate-50/50 rounded-2xl border border-slate-100">
                        <h4 class="text-sm font-bold text-slate-900 mb-6 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-violet-500"><rect width="20" height="8" x="2" y="2" rx="2" ry="2"/><rect width="20" height="8" x="2" y="14" rx="2" ry="2"/><line x1="6" x2="6.01" y1="6" y2="6"/><line x1="6" x2="6.01" y1="18" y2="18"/></svg>
                            Connection Credentials
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Username -->
                            <div class="space-y-1">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider ml-1">Username</p>
                                <div class="flex items-center gap-2 bg-white p-3 rounded-xl border border-slate-200">
                                    <code class="flex-1 text-sm font-mono text-slate-700 truncate"><?php echo esc_html($username_gen); ?></code>
                                    <button onclick="copyToClipboard('<?php echo esc_js($username_gen); ?>', this)" class="p-2 text-slate-400 hover:text-emerald-600 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Password -->
                            <div class="space-y-1">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider ml-1">Password</p>
                                <div class="flex items-center gap-2 bg-white p-3 rounded-xl border border-slate-200">
                                    <input type="password" value="<?php echo esc_attr($password_gen); ?>" class="flex-1 text-sm font-mono text-slate-700 bg-transparent border-none focus:ring-0 p-0" readonly id="pass-<?php echo $s->id; ?>">
                                    <button onclick="togglePass('pass-<?php echo $s->id; ?>')" class="p-2 text-slate-400 hover:text-blue-600 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                    <button onclick="copyToClipboard('<?php echo esc_js($password_gen); ?>', this)" class="p-2 text-slate-400 hover:text-emerald-600 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- M3U -->
                        <div class="space-y-1">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider ml-1">M3U Playlist URL</p>
                            <div class="flex items-center gap-2 bg-white p-3 rounded-xl border border-slate-200">
                                <code class="flex-1 text-sm font-mono text-slate-700 truncate"><?php echo esc_html($m3u_url); ?></code>
                                <button onclick="copyToClipboard('<?php echo esc_js($m3u_url); ?>', this)" class="p-2 text-slate-400 hover:text-emerald-600 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <div class="p-6 bg-slate-50/50 rounded-2xl border border-slate-100 flex flex-col items-center justify-center text-center">
                            <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                            </div>
                            <h4 class="font-bold text-slate-900">Subscription Pending</h4>
                            <p class="text-slate-500 text-sm max-w-md mt-1">Your payment is being verified. Credentials will appear here automatically once approved by an administrator.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Footer Actions -->
                    <div class="pt-6 mt-6 border-t border-slate-100 flex justify-end gap-3">
                        <button class="px-5 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold rounded-xl text-sm transition-colors shadow-sm flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="16" y2="12"/><line x1="12" x2="12.01" y1="8" y2="8"/></svg> 
                            View Details
                        </button>
                        <a href="?view=shop" class="px-5 py-2.5 bg-slate-900 text-white hover:bg-slate-800 font-bold rounded-xl text-sm transition-colors shadow-lg hover:shadow-xl">
                            <?php echo ($s->status === 'active') ? 'Extend Now' : 'Renew Subscription'; ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
        <?php endif; ?>

        <?php if ( $total_subs > 1 ) : ?>
            <div class="flex items-center justify-center gap-2 pt-4">
                <?php
                    $base_url = remove_query_arg('sub_page');
                    $prev = max(1, $sub_page - 1);
                    $next = min($total_subs, $sub_page + 1);
                ?>
                <a href="<?php echo esc_url( add_query_arg('sub_page', $prev, $base_url) ); ?>" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 font-bold text-sm hover:bg-slate-50">&larr;</a>
                <div class="px-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-sm font-bold text-slate-700">
                    Subscription <?php echo (int)$sub_page; ?> of <?php echo (int)$total_subs; ?>
                </div>
                <a href="<?php echo esc_url( add_query_arg('sub_page', $next, $base_url) ); ?>" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 font-bold text-sm hover:bg-slate-50">&rarr;</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Inline utilities for this view
function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-600"><polyline points="20 6 9 17 4 12"/></svg>';
        setTimeout(() => { btn.innerHTML = originalHTML; }, 2000);
    });
}

function togglePass(id) {
    const el = document.getElementById(id);
    if(el) el.type = (el.type === 'password') ? 'text' : 'password';
}
</script>