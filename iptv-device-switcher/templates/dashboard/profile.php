<?php
/**
 * Template Name: Dashboard - Profile
 * Path: /templates/dashboard/profile.php
 * Description: Profile management settings, inputs populated with real user meta.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! is_user_logged_in() ) return;

$current_user = wp_get_current_user();
$uid = $current_user->ID;

// Fetch Real User Meta
$phone = get_user_meta($uid, 'billing_phone', true) ?: '';
$country = get_user_meta($uid, 'billing_country', true) ?: '';
$avatar_letter = strtoupper(substr($current_user->display_name, 0, 1));

// Check for Pro Status
global $wpdb;
$has_active_sub = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tv_subscriptions WHERE user_id = $uid AND status = 'active'");
?>

<div class="max-w-6xl mx-auto space-y-8 animate-in fade-in zoom-in duration-300">
    
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Account Settings</h1>
        <?php if($has_active_sub): ?>
        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold uppercase tracking-wide">Pro Member</span>
        <?php else: ?>
        <span class="px-3 py-1 bg-slate-100 text-slate-500 rounded-full text-xs font-bold uppercase tracking-wide">Free Account</span>
        <?php endif; ?>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <!-- Left Col: Profile Card -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-[2rem] border border-slate-100 p-8 flex flex-col items-center text-center h-full shadow-sm">
                <div class="relative mb-6 group cursor-pointer">
                    <div class="w-32 h-32 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-5xl font-bold border-4 border-white shadow-xl overflow-hidden">
                        <?php echo esc_html($avatar_letter); ?>
                    </div>
                    <div class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="text-white text-xs font-bold">Change</span>
                    </div>
                </div>
                <h2 class="text-2xl font-bold text-slate-900"><?php echo esc_html($current_user->display_name); ?></h2>
                <p class="text-slate-500 text-sm font-medium mb-8"><?php echo esc_html($current_user->user_email); ?></p>
                
                <div class="w-full space-y-4">
                    <div class="p-4 bg-slate-50 rounded-2xl flex items-center justify-between">
                        <div class="text-left">
                            <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Plan</p>
                            <p class="font-bold text-slate-900"><?php echo $has_active_sub ? 'Premium' : 'None'; ?></p>
                        </div>
                        <div class="h-8 w-px bg-slate-200"></div>
                        <div class="text-right">
                            <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Status</p>
                            <?php if($has_active_sub): ?>
                                <span class="text-emerald-600 font-bold text-sm">Active</span>
                            <?php else: ?>
                                <span class="text-slate-400 font-bold text-sm">Inactive</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Col: Edit Form -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-[2rem] border border-slate-100 p-8 h-full shadow-sm">
                
                <div class="flex items-center gap-4 mb-8 pb-4 border-b border-slate-50">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Personal Information</h3>
                        <p class="text-sm text-slate-500">Update your personal details here.</p>
                    </div>
                </div>

                <form method="post" action="">
                    <!-- Add hidden fields/nonces here if enabling actual saving -->
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Full Name</label>
                            <input type="text" name="display_name" value="<?php echo esc_attr($current_user->display_name); ?>" class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all">
                        </div>
                        
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Email</label>
                            <div class="relative">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-4 top-3.5 text-slate-400"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <input type="email" name="user_email" value="<?php echo esc_attr($current_user->user_email); ?>" class="w-full pl-11 pr-4 py-3 bg-slate-50 border-none rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all">
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Phone</label>
                            <div class="relative">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-4 top-3.5 text-slate-400"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                                <input type="tel" name="billing_phone" value="<?php echo esc_attr($phone); ?>" class="w-full pl-11 pr-4 py-3 bg-slate-50 border-none rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all">
                            </div>
                        </div>
                        
                        <!-- Country Input Locked (Visible but Read-Only) -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Location (Locked)</label>
                            <div class="relative">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-4 top-3.5 text-slate-400"><circle cx="12" cy="12" r="10"/><line x1="2" x2="22" y1="12" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                <input type="text" name="billing_country" value="<?php echo esc_attr($country); ?>" disabled readonly 
                                       style="background:#f1f5f9; cursor:not-allowed; opacity:0.8; color:#64748b;"
                                       class="w-full pl-11 pr-4 py-3 border-none rounded-xl text-sm font-bold">
                                <div style="position: absolute; right: 10px; top: 12px; color: #94a3b8;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                </div>
                                <div style="font-size:10px; color:#ef4444; margin-top:4px; font-weight:600;">Contact support to change country.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-slate-50 flex justify-end">
                        <button type="button" class="px-6 py-3 bg-slate-900 text-white font-bold rounded-xl text-sm hover:bg-slate-800 shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Security & Notifications Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Security -->
        <div class="bg-white rounded-[2rem] border border-slate-100 p-8 shadow-sm">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-10 h-10 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Security</h3>
                    <p class="text-sm text-slate-500">Manage your password.</p>
                </div>
            </div>
            <div class="space-y-5">
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Current Password</label>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-4 top-3.5 text-slate-400"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" placeholder="dz?dz?dz?dz?dz?dz?dz?dz?" class="w-full pl-11 pr-4 py-3 bg-slate-50 border-none rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-violet-500/20 focus:bg-white transition-all">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">New Password</label>
                    <div class="relative">
                         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-4 top-3.5 text-slate-400"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                         <input type="password" placeholder="New password" class="w-full pl-11 pr-4 py-3 bg-slate-50 border-none rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-violet-500/20 focus:bg-white transition-all">
                    </div>
                </div>
            </div>
            <div class="mt-8 flex justify-end">
                <button type="button" class="px-6 py-3 bg-white border border-slate-200 text-slate-700 font-bold rounded-xl text-sm hover:bg-slate-50 transition-all">Update Password</button>
            </div>
        </div>

        <!-- Notifications -->
        <div class="bg-white rounded-[2rem] border border-slate-100 p-8 shadow-sm">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Notifications</h3>
                    <p class="text-sm text-slate-500">Manage how we contact you.</p>
                </div>
            </div>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-transparent hover:border-slate-200 transition-colors">
                    <div>
                        <p class="font-bold text-sm text-slate-900">Email Notifications</p>
                        <p class="text-xs text-slate-500 font-medium">Subscription & feature updates</p>
                    </div>
                    <div class="w-12 h-7 bg-blue-600 rounded-full relative cursor-pointer transition-colors shadow-inner">
                        <div class="w-5 h-5 bg-white rounded-full absolute top-1 right-1 shadow-md"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-transparent hover:border-slate-200 transition-colors">
                    <div>
                        <p class="font-bold text-sm text-slate-900">WhatsApp Alerts</p>
                        <p class="text-xs text-slate-500 font-medium">Critical billing issues only</p>
                    </div>
                    <div class="w-12 h-7 bg-slate-200 rounded-full relative cursor-pointer transition-colors shadow-inner">
                        <div class="w-5 h-5 bg-white rounded-full absolute top-1 left-1 shadow-md"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>