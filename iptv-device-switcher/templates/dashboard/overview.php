<?php
/**
 * Template Name: Dashboard - Overview
 * Path: /templates/dashboard/overview.php
 * Description: The main landing view showing hero slider, active stats, and billing summary.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Auth & Data Setup
if ( ! is_user_logged_in() ) return;

global $wpdb;
$current_user = wp_get_current_user();
$uid = $current_user->ID;

// 2. Data Fetching
// Fetch Active Subscriptions (Limit 3 for summary)
$subs_raw = $wpdb->get_results("
    SELECT s.*, p.name as plan_name, p.duration_days 
    FROM {$wpdb->prefix}tv_subscriptions s 
    LEFT JOIN {$wpdb->prefix}tv_plans p ON s.plan_id = p.id 
    WHERE s.user_id = $uid 
    AND s.status = 'active'
    ORDER BY s.end_date ASC
    LIMIT 3
");

// Count Total Active
$active_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tv_subscriptions WHERE user_id = $uid AND status = 'active'");

// Fetch Recent Invoices (Limit 3)
$payments_raw = $wpdb->get_results("
    SELECT p.*, pl.name as plan_name 
    FROM {$wpdb->prefix}tv_payments p
    LEFT JOIN {$wpdb->prefix}tv_subscriptions s ON p.subscription_id = s.id
    LEFT JOIN {$wpdb->prefix}tv_plans pl ON s.plan_id = pl.id
    WHERE p.user_id = $uid 
    ORDER BY p.date DESC 
    LIMIT 3
");

// Static News Data (Simulating the CMS feature)
$news_updates = [
    [
        'id' => 1,
        'title' => "New 4K Sports Channels",
        'description' => "Experience the thrill in Ultra HD. We've added 12 new premium sports channels to your lineup.",
        'button' => "Explore Channels",
        'color' => "from-indigo-600 to-violet-600"
    ],
    [
        'id' => 2,
        'title' => "Scheduled Maintenance",
        'description' => "We are optimizing our servers on Oct 30th (03:00 AM UTC). Brief interruptions may occur.",
        'button' => "Status Page",
        'color' => "from-rose-500 to-orange-500"
    ],
    [
        'id' => 3,
        'title' => "Referral Bonus",
        'description' => "Invite friends to NovaPanel and earn 1 month of free premium access for every annual subscription.",
        'button' => "Get Link",
        'color' => "from-emerald-500 to-teal-500"
    ]
];

// Helper for Badges
if (!function_exists('streamos_status_badge_sm')) {
    function streamos_status_badge_sm($status) {
        $colors = [
            'active' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
            'expired' => 'bg-slate-100 text-slate-500 border-slate-200',
        ];
        $cls = $colors[strtolower($status)] ?? 'bg-slate-100 text-slate-600';
        return '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border ' . $cls . '">' . ucfirst($status) . '</span>';
    }
}
?>

<div class="max-w-7xl mx-auto space-y-8 animate-in fade-in zoom-in duration-300">
    
    <!-- Hero Slider -->
    <div class="relative w-full overflow-hidden rounded-[2rem] shadow-xl shadow-slate-200/50 group bg-white border border-slate-100">
        <div id="hero-slider-track" class="flex transition-transform duration-700 ease-in-out h-72 lg:h-80" style="transform: translateX(0%);">
            <?php foreach($news_updates as $news): ?>
            <div class="w-full flex-shrink-0 h-full bg-gradient-to-br <?php echo esc_attr($news['color']); ?> p-10 lg:p-14 relative flex items-center">
                <!-- Decorative Elements -->
                <div class="absolute inset-0 bg-black/10 mix-blend-overlay"></div>
                <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>

                <div class="relative z-10 max-w-2xl text-white">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-bold uppercase tracking-wider border border-white/10">New Update</span>
                    </div>
                    <h2 class="text-3xl lg:text-5xl font-black mb-6 tracking-tight leading-tight"><?php echo esc_html($news['title']); ?></h2>
                    <p class="text-white/90 text-lg mb-8 leading-relaxed max-w-lg font-medium hidden md:block"><?php echo esc_html($news['description']); ?></p>
                    <button class="group px-7 py-3.5 bg-white text-slate-900 rounded-xl font-bold hover:bg-slate-50 transition-all flex items-center gap-2 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                        <?php echo esc_html($news['button']); ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-1 transition-transform"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Dots -->
        <div class="absolute bottom-8 left-10 flex gap-2.5 z-20">
            <?php foreach($news_updates as $i => $news): ?>
            <button onclick="goToSlide(<?php echo $i; ?>)" id="dot-<?php echo $i; ?>" class="h-2.5 rounded-full transition-all duration-300 bg-white/40 w-2.5 hover:bg-white/60"></button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Active Subscriptions Card -->
        <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 flex flex-col h-full hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Active Subscriptions</h3>
                        <p class="text-sm text-slate-500 font-medium"><?php echo intval($active_count); ?> plans active</p>
                    </div>
                </div>
                <a href="?view=subscription" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-50 text-slate-400 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            </div>

            <div class="space-y-4 mb-8 flex-1">
                <?php if($subs_raw): foreach($subs_raw as $sub): 
                    $days_left = ceil((strtotime($sub->end_date) - time()) / 86400);
                ?>
                <div class="group flex justify-between items-center p-4 bg-slate-50 rounded-2xl border border-transparent hover:border-slate-200 transition-all">
                    <div>
                        <p class="font-bold text-slate-900"><?php echo esc_html($sub->plan_name); ?></p>
                        <div class="flex items-center gap-2 mt-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span class="text-xs font-semibold text-slate-500"><?php echo $days_left; ?> days remaining</span>
                        </div>
                    </div>
                    <?php echo streamos_status_badge_sm($sub->status); ?>
                </div>
                <?php endforeach; else: ?>
                    <p class="text-slate-500 text-sm">No active subscriptions.</p>
                <?php endif; ?>
            </div>
            
            <a href="?view=shop" class="w-full py-3.5 border border-dashed border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 hover:border-blue-400 hover:text-blue-600 transition-all text-sm flex items-center justify-center gap-2">
                + Add Subscription
            </a>
        </div>

        <!-- Billing History Card -->
        <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 flex flex-col h-full hover:shadow-md transition-shadow">
            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-violet-50 flex items-center justify-center text-violet-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2" /><line x1="2" x2="22" y1="10" y2="10" /></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Billing History</h3>
                        <p class="text-sm text-slate-500 font-medium">Latest transactions</p>
                    </div>
                </div>
                <a href="?view=billing" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-50 text-slate-400 hover:bg-violet-50 hover:text-violet-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            </div>

            <div class="flex-1 space-y-3">
                <?php if($payments_raw): foreach($payments_raw as $pay): ?>
                <div class="flex justify-between items-center p-4 rounded-2xl hover:bg-slate-50 transition-colors cursor-pointer group">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-white group-hover:text-violet-600 group-hover:shadow-sm transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="9" y2="9"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900 group-hover:text-violet-700 transition-colors"><?php echo esc_html($pay->plan_name ?: 'Payment'); ?></p>
                            <p class="text-xs text-slate-500 font-medium"><?php echo date('M d, Y', strtotime($pay->date)); ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-slate-900 mb-1">$<?php echo esc_html($pay->amount); ?></p>
                        <?php echo streamos_status_badge_sm($pay->status); ?>
                    </div>
                </div>
                <?php endforeach; else: ?>
                    <p class="text-slate-500 text-sm">No recent transactions.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Simple Slider Logic
    let slideIndex = 0;
    const slides = document.querySelectorAll('#hero-slider-track > div');
    const track = document.getElementById('hero-slider-track');
    const totalSlides = slides.length;

    function goToSlide(index) {
        slideIndex = index;
        const percent = index * -100;
        track.style.transform = `translateX(${percent}%)`;
        
        // Update dots
        for(let i=0; i<totalSlides; i++) {
            const dot = document.getElementById('dot-'+i);
            if(i === index) {
                dot.classList.remove('bg-white/40', 'w-2.5');
                dot.classList.add('bg-white', 'w-8');
            } else {
                dot.classList.add('bg-white/40', 'w-2.5');
                dot.classList.remove('bg-white', 'w-8');
            }
        }
    }

    // Auto rotate
    setInterval(() => {
        let next = (slideIndex + 1) % totalSlides;
        goToSlide(next);
    }, 6000);

    // Init
    goToSlide(0);
</script>
