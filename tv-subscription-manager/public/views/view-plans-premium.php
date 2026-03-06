<?php 
if (!defined('ABSPATH')) { exit; }

/**
 * Premium Subscription Plans Page
 * Fixes Applied: 
 * 1. CSS: Added default hidden state to currency overlay to prevent animation conflicts.
 * 2. JS: Added safety checks for PHP data.
 * 3. JS: Temporarily bypassed session storage so popup always shows for testing.
 * 4. Content: Updated header text to be more professional.
 * 5. UI: Completely redesigned Trial Processing Overlay for professional look.
 * 6. UI: Added "? Back to Home" button above the store header.
 */

// 1. Prepare Data for JS
$json_plans = [];
$json_currencies = [];

// Get Admin Allowed Currencies (and filter out empty values)
$allowed_currencies = get_option('tv_allowed_currencies', []);
if (is_array($allowed_currencies)) {
    $allowed_currencies = array_filter($allowed_currencies);
} else {
    $allowed_currencies = [];
}

// Define Standard Defaults (Used if WOOCS is missing or for fallback)
$currency_defaults = [
    'USD' => ['symbol'=>'$', 'rate'=>1, 'name'=>'US Dollar', 'flag'=>'us'],
    'EUR' => ['symbol'=>'€', 'rate'=>1, 'name'=>'Euro', 'flag'=>'eu'],
    'GBP' => ['symbol'=>'£', 'rate'=>1, 'name'=>'British Pound', 'flag'=>'gb'],
    'NGN' => ['symbol'=>'?', 'rate'=>1, 'name'=>'Nigerian Naira', 'flag'=>'ng'],
    'GHS' => ['symbol'=>'?', 'rate'=>1, 'name'=>'Ghanaian Cedi', 'flag'=>'gh'],
    'KES' => ['symbol'=>'KSh', 'rate'=>1, 'name'=>'Kenyan Shilling', 'flag'=>'ke'],
    'ZAR' => ['symbol'=>'R', 'rate'=>1, 'name'=>'South African Rand', 'flag'=>'za'],
    'CAD' => ['symbol'=>'C$', 'rate'=>1, 'name'=>'Canadian Dollar', 'flag'=>'ca'],
    'AUD' => ['symbol'=>'A$', 'rate'=>1, 'name'=>'Australian Dollar', 'flag'=>'au'],
    'INR' => ['symbol'=>'?', 'rate'=>1, 'name'=>'Indian Rupee', 'flag'=>'in'],
];

// FOX Integration Logic (WOOCS)
$woocs_active = class_exists('WOOCS');

if ($woocs_active) {
    global $WOOCS;
    $all_currencies = $WOOCS->get_currencies();
    
    // Filter by Admin Allowed
    if (!empty($allowed_currencies)) {
        foreach ($allowed_currencies as $code) {
            if (isset($all_currencies[$code])) {
                $json_currencies[$code] = [
                    'symbol' => $all_currencies[$code]['symbol'],
                    'rate' => floatval($all_currencies[$code]['rate']),
                    'name' => $all_currencies[$code]['name'],
                    'flag' => strtolower(substr($code, 0, 2)) 
                ];
            }
        }
    } else {
        // If allowed list is empty but WOOCS has data, use all from WOOCS
        if (!empty($all_currencies)) {
            foreach ($all_currencies as $code => $data) {
                $json_currencies[$code] = [
                    'symbol' => $data['symbol'],
                    'rate' => floatval($data['rate']),
                    'name' => $data['name'],
                    'flag' => strtolower(substr($code, 0, 2))
                ];
            }
        }
    }
}

// CRITICAL SAFETY NET: If currencies are empty (WOOCS disabled OR WOOCS returned no data)
if (empty($json_currencies)) {
    if (!empty($allowed_currencies)) {
        // Hydrate from defaults based on admin selection
        foreach ($allowed_currencies as $code) {
            if (isset($currency_defaults[$code])) {
                $json_currencies[$code] = $currency_defaults[$code];
            } else {
                // Generic fallback for unlisted allowed codes
                $json_currencies[$code] = ['symbol'=>$code, 'rate'=>1, 'name'=>$code, 'flag'=>strtolower(substr($code,0,2))];
            }
        }
    } else {
        // No filter and no WOOCS? Use all defaults
        $json_currencies = $currency_defaults;
    }
}

// FINAL FAILSAFE: If logic still resulted in empty array, force USD
if (empty($json_currencies)) {
    $json_currencies['USD'] = $currency_defaults['USD'];
}

// Ensure $plans is available (assuming it comes from the parent template)
if (!isset($plans) || !is_array($plans)) {
    $plans = []; 
}

foreach ($plans as $p) {
    $is_premium = (strpos(strtolower($p->name), 'premium') !== false);
    
    $json_plans[] = [
        'id' => $p->id,
        'name' => $p->name,
        'base_price' => floatval($p->price), // In USD
        'duration' => $p->duration_days,
        'features' => explode("\n", $p->description),
        'is_featured' => $is_premium,
        'multi_device' => (bool)$p->allow_multi_connections,
        'tag' => $is_premium ? 'Best Value' : ($p->duration_days > 180 ? 'Max Savings' : '')
    ];
}

$is_logged_in = is_user_logged_in();
// Destination for logged-in users (Method Selection)
$subs_url = add_query_arg('tv_flow', 'select_method', home_url('/'));

// WhatsApp Logic for Visitors
// Ensure $config is available
$wa_number = isset($config['wa_number']) ? $config['wa_number'] : '';
$wa_base = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $wa_number);
$wa_template = (!empty($config['wa_msg'])) ? $config['wa_msg'] : "Hi, I'm interested in a trial for {plan_name}.";

?>
<!-- Ultra-Modern Premium CSS -->
<style>
    :root {
        --v24-slate-900: #0f172a;
        --v24-slate-800: #1e293b;
        --v24-slate-500: #64748b;
        --v24-slate-400: #94a3b8;
        --v24-slate-200: #e2e8f0;
        --v24-slate-100: #f1f5f9;
        --v24-slate-50:  #f8fafc;
        --v24-primary:   #6366f1; /* Indigo 500 */
        --v24-violet:    #8b5cf6;
        --v24-white:     #ffffff;
        
        --v24-radius:    2rem; /* 32px matches rounded-[2rem] */
        --v24-radius-lg: 2.5rem;
        --v24-shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        --v24-font:      'Inter', system-ui, sans-serif;
        --v24-ease:      cubic-bezier(0.16, 1, 0.3, 1);
    }

    body { background-color: var(--v24-slate-50); font-family: var(--v24-font); -webkit-font-smoothing: antialiased; }
    
    .tv-store-wrap { max-width: 1200px; margin: 0 auto; padding: 60px 24px; }
    
    /* Header Area */
    .tv-store-header { text-align: center; margin-bottom: 60px; }
    .tv-store-pill { 
        display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; 
        background: #eff6ff; color: var(--v24-primary); border-radius: 100px; 
        font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; 
        margin-bottom: 24px;
    }
    .tv-store-title { 
        font-size: 3rem; font-weight: 900; color: var(--v24-slate-900); 
        letter-spacing: -0.03em; line-height: 1.1; margin: 0 0 16px 0; 
    }
    .tv-store-sub { font-size: 1.125rem; color: var(--v24-slate-500); max-width: 600px; margin: 0 auto; }

    /* Grid */
    .tv-store-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px;
        align-items: start; opacity: 0; transition: opacity 0.5s;
    }
    .tv-store-grid.visible { opacity: 1; }
    
    /* --- COMPACT CARD (Clickable) --- */
    .tv-compact-card {
        position: relative; display: flex; flex-direction: column;
        border-radius: var(--v24-radius); padding: 24px;
        transition: all 0.4s var(--v24-ease);
        cursor: pointer; overflow: hidden; min-height: 380px;
        border: 1px solid transparent;
    }
    
    /* Standard Variant */
    .tv-compact-card.standard {
        background: var(--v24-white);
        border-color: var(--v24-slate-200);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        color: var(--v24-slate-900);
    }
    .tv-compact-card.standard:hover {
        transform: translateY(-8px);
        box-shadow: var(--v24-shadow-xl);
        border-color: #cbd5e1;
    }
    
    /* Premium Variant (Dark) */
    .tv-compact-card.premium {
        background: var(--v24-slate-900);
        border-color: var(--v24-slate-800);
        box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.5);
        color: var(--v24-white);
    }
    .tv-compact-card.premium:hover {
        transform: translateY(-8px);
        box-shadow: 0 35px 60px -15px rgba(0,0,0,0.6);
    }

    /* Card Internals */
    .tv-cc-badge {
        position: absolute; top: 24px; right: 24px;
        background: linear-gradient(to right, #2563eb, #7c3aed);
        color: white; font-size: 0.65rem; font-weight: 800;
        padding: 6px 12px; border-radius: 100px; z-index: 2;
        text-transform: uppercase; letter-spacing: 0.05em;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
    }

    .tv-cc-name { font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 12px; opacity: 0.8; }
    .tv-cc-price { font-size: 3rem; font-weight: 900; letter-spacing: -0.04em; line-height: 1; margin-bottom: 8px; }
    .tv-cc-period { font-size: 0.75rem; font-weight: 700; opacity: 0.6; text-transform: uppercase; }
    
    .tv-cc-divider { height: 1px; width: 100%; margin: 1.5rem 0; opacity: 0.1; background: currentColor; }

    .tv-cc-feats { list-style: none; padding: 0; margin: 0 0 2rem 0; flex: 1; }
    .tv-cc-feats li { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 0.8rem; font-weight: 600; opacity: 0.8; }
    
    .tv-cc-action {
        width: 100%; padding: 14px; border-radius: 16px; margin-top: auto;
        font-weight: 800; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;
        text-align: center; transition: all 0.2s; pointer-events: none; /* Let card handle click */
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .tv-compact-card.premium .tv-cc-action { background: var(--v24-white); color: var(--v24-slate-900); }
    .tv-compact-card.standard .tv-cc-action { background: var(--v24-slate-900); color: var(--v24-white); }
    
    /* Hover gradient for premium */
    .tv-compact-card.premium::before {
        content: ''; position: absolute; inset: 0; 
        background: linear-gradient(to bottom, rgba(255,255,255,0.05) 0%, transparent 100%);
        opacity: 0; transition: opacity 0.4s; pointer-events: none;
    }
    .tv-compact-card.premium:hover::before { opacity: 1; }


    /* --- MODAL SYSTEM --- */
    #tv-modal-overlay {
        display: none; position: fixed; inset: 0; z-index: 10000;
        background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px);
        align-items: center; justify-content: center; padding: 20px;
        opacity: 0; transition: opacity 0.3s ease;
    }
    #tv-modal-overlay.visible { opacity: 1; }

    .tv-modal-card {
        background: white; width: 100%; max-width: 480px; max-height: 90vh;
        border-radius: var(--v24-radius-lg); box-shadow: 0 50px 100px -20px rgba(0,0,0,0.25);
        display: flex; flex-direction: column; overflow: hidden;
        transform: translateY(20px) scale(0.95); transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
    }
    #tv-modal-overlay.visible .tv-modal-card { transform: translateY(0) scale(1); }

    .tv-modal-header {
        padding: 40px 30px 30px; text-align: center; background: var(--v24-slate-50);
        border-bottom: 1px solid var(--v24-slate-200); position: relative;
    }
    .tv-modal-header.premium {
        background: linear-gradient(to bottom, #f5f3ff 0%, #ffffff 100%);
    }
    .tv-modal-header.premium::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
        background: linear-gradient(to right, var(--v24-primary), var(--v24-violet));
    }
    
    .tv-modal-close {
        position: absolute; top: 20px; right: 20px; width: 32px; height: 32px;
        border-radius: 50%; background: white; border: 1px solid var(--v24-slate-200);
        display: flex; align-items: center; justify-content: center; cursor: pointer;
        color: var(--v24-slate-400); transition: 0.2s; z-index: 10;
    }
    .tv-modal-close:hover { color: var(--v24-slate-900); border-color: var(--v24-slate-400); }

    .tv-modal-title { font-size: 2rem; font-weight: 900; color: var(--v24-slate-900); margin-bottom: 8px; line-height: 1.1; }
    .tv-modal-price { font-size: 3rem; font-weight: 900; color: var(--v24-slate-900); letter-spacing: -2px; }
    .tv-modal-period { font-size: 0.9rem; font-weight: 600; color: var(--v24-slate-500); text-transform: uppercase; letter-spacing: 1px; }

    .tv-modal-body { padding: 30px; overflow-y: auto; }
    .tv-modal-h4 { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--v24-slate-400); margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
    .tv-modal-h4::before { content: ''; width: 4px; height: 16px; background: var(--v24-slate-200); border-radius: 4px; }
    
    .tv-modal-feats { list-style: none; padding: 0; margin: 0; }
    .tv-modal-feats li { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; font-size: 0.95rem; color: var(--v24-slate-500); font-weight: 500; line-height: 1.5; }
    .tv-modal-icon { color: #10b981; background: #ecfdf5; border-radius: 50%; padding: 4px; flex-shrink: 0; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; }

    .tv-modal-footer { padding: 20px 30px; border-top: 1px solid var(--v24-slate-200); background: white; }
    .tv-modal-btn {
        width: 100%; padding: 18px; border-radius: 16px; font-weight: 800; font-size: 1rem;
        cursor: pointer; border: none; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 10px;
        box-shadow: 0 10px 30px -5px rgba(0,0,0,0.15); text-decoration: none;
    }
    .tv-modal-btn.premium { background: var(--v24-primary); color: white; box-shadow: 0 10px 25px -5px rgba(99,102,241,0.5); }
    .tv-modal-btn.premium:hover { background: #4f46e5; transform: translateY(-2px); }
    .tv-modal-btn.standard { background: var(--v24-slate-900); color: white; }
    .tv-modal-btn.standard:hover { background: #334155; transform: translateY(-2px); }

    /* Currency Gate */
    #tv-currency-overlay {
        position: fixed; inset: 0; z-index: 10000;
        background: rgba(255,255,255,0.9); backdrop-filter: blur(20px);
        display: none; 
        opacity: 0;
        flex-direction: column; align-items: center; justify-content: center;
        transition: opacity 0.4s;
    }
    #tv-currency-overlay.visible { opacity: 1; }

    .tv-gate-content { text-align: center; max-width: 400px; width: 90%; animation: premScaleIn 0.4s ease-out; }
    .tv-gate-grid { 
        display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 30px; 
        max-height: 60vh; overflow-y: auto; padding: 4px;
    }
    .tv-gate-btn {
        padding: 16px; border: 1px solid var(--v24-slate-100); background: white;
        border-radius: 16px; cursor: pointer; transition: 0.2s; font-weight: 700; color: var(--v24-slate-500);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .tv-gate-btn:hover { border-color: var(--v24-primary); color: var(--v24-primary); transform: translateY(-2px); }

    /* Currency Widget */
    .tv-curr-trigger {
        position: fixed; bottom: 30px; right: 30px; z-index: 900;
        background: white; border-radius: 99px; padding: 10px 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid var(--v24-slate-200);
        display: flex; align-items: center; gap: 10px; cursor: pointer;
        transition: all 0.3s var(--v24-ease); font-weight: 600; color: var(--v24-slate-900);
    }
    .tv-curr-trigger:hover { transform: translateY(-4px); box-shadow: 0 15px 40px rgba(0,0,0,0.2); }

    /* REDESIGNED TRIAL POPUP (Professional Grade) */
    #tv-trial-processing {
        position: fixed; inset: 0; z-index: 10005; 
        background: rgba(15, 23, 42, 0.9);
        backdrop-filter: blur(12px);
        display: none;
        align-items: center; justify-content: center;
        opacity: 0; transition: opacity 0.3s ease;
    }
    #tv-trial-processing.visible { opacity: 1; }

    .tv-trial-card {
        background: white; width: 90%; max-width: 420px;
        border-radius: 24px; padding: 48px 40px;
        text-align: center; 
        box-shadow: 0 50px 100px -20px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.1) inset;
        position: relative; overflow: hidden;
        transform: scale(0.95); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    #tv-trial-processing.visible .tv-trial-card { transform: scale(1); }
    
    /* Animated Icon Wrapper */
    .tv-trial-icon-wrap {
        width: 80px; height: 80px; margin: 0 auto 30px; position: relative;
        display: flex; align-items: center; justify-content: center;
    }
    .tv-trial-icon-bg {
        position: absolute; inset: 0; background: #25D366; opacity: 0.15; border-radius: 50%;
        animation: pulseRing 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
    }
    .tv-trial-svg {
        width: 40px; height: 40px; color: #25D366; z-index: 2; 
        filter: drop-shadow(0 4px 6px rgba(37, 211, 102, 0.4));
    }
    
    /* Typography */
    .tv-trial-title {
        font-size: 1.5rem; font-weight: 800; color: #0f172a; margin-bottom: 8px; letter-spacing: -0.02em;
    }
    .tv-trial-desc {
        font-size: 1rem; color: #64748b; margin-bottom: 32px; line-height: 1.5;
    }
    
    /* Plan Details Pill */
    .tv-trial-details {
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
        padding: 16px; margin-bottom: 32px; display: flex; align-items: center; justify-content: space-between;
    }
    .tv-td-label { font-size: 0.85rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
    .tv-td-val { font-size: 1rem; font-weight: 800; color: #0f172a; }

    /* Progress Bar */
    .tv-loader-track {
        height: 6px; background: #e2e8f0; border-radius: 10px; overflow: hidden; position: relative;
    }
    .tv-loader-fill {
        height: 100%; background: linear-gradient(90deg, #25D366, #128C7E);
        width: 0%; transition: width 0.2s linear;
        border-radius: 10px;
    }
    
    /* Status Text */
    .tv-status-text {
        margin-top: 16px; font-size: 0.85rem; font-weight: 600; color: #94a3b8;
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }

    @keyframes pulseRing {
        0% { transform: scale(0.8); opacity: 0.5; }
        100% { transform: scale(2); opacity: 0; }
    }
    @keyframes premFadeIn { from { opacity: 0; } to { opacity: 1; } }

    /* Mobile */
    @media(max-width: 768px) {
        .tv-prem-title { font-size: 2.5rem; }
    }
</style>

<!-- Currency Selection Gate -->
<div id="tv-currency-overlay">
    <div class="tv-gate-content">
        <div style="width:64px; height:64px; background:#eff6ff; border-radius:20px; display:flex; align-items:center; justify-content:center; margin:0 auto 24px; color:var(--v24-primary);">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
        </div>
        <h2 style="font-size:2rem; font-weight:900; color:#0f172a; margin:0 0 10px 0;">Select Currency</h2>
        <p style="color:#64748b; font-size:1rem;">Choose your local currency to see tailored plans.</p>
        <div class="tv-gate-grid" id="tv-currency-list"></div>
    </div>
</div>

<!-- Floating Currency Switcher -->
<div id="tv-curr-trigger" class="tv-curr-trigger" style="display:none;" onclick="showGate()">
    <span id="tv-active-curr-icon">??</span> <span id="tv-active-curr-code">USD</span>
</div>

<!-- Main Store -->
<div class="tv-store-wrap">

    <!-- Back to Home Button (FIX: Added) -->
    <div style="margin-bottom: 24px;">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>"
           style="
               display: inline-flex;
               align-items: center;
               gap: 8px;
               padding: 10px 20px;
               background: #ffffff;
               border: 1px solid #e2e8f0;
               border-radius: 100px;
               font-size: 0.85rem;
               font-weight: 700;
               color: #475569;
               text-decoration: none;
               box-shadow: 0 1px 4px rgba(0,0,0,0.06);
               transition: all 0.2s;
           "
           onmouseover="this.style.borderColor='#6366f1';this.style.color='#6366f1';this.style.boxShadow='0 4px 12px rgba(99,102,241,0.15)';"
           onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#475569';this.style.boxShadow='0 1px 4px rgba(0,0,0,0.06)';"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to Home
        </a>
    </div>

    <div class="tv-prem-header">
        <div class="tv-store-pill">
            <span style="width:8px; height:8px; background:currentColor; border-radius:50%;"></span> Official Access Portal
        </div>
        <h1 class="tv-prem-title">Start Your Premium Journey</h1>
        <p class="tv-store-sub">Experience the ultimate entertainment package. Unlock 25,000+ live channels and VOD in stunning 4K HDR.</p>
    </div>

    <!-- The Grid of Compact Cards -->
    <div class="tv-store-grid" id="tv-plans-container">
        <!-- JS Populated -->
    </div>
</div>

<!-- PLAN DETAIL MODAL -->
<div id="tv-modal-overlay">
    <div class="tv-modal-card">
        <div class="tv-modal-header" id="tv-modal-header">
            <div class="tv-modal-close" onclick="closePlanModal()"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></div>
            <div class="tv-modal-title" id="tv-modal-title"></div>
            <div class="tv-modal-price" id="tv-modal-price"></div>
            <div class="tv-modal-period" id="tv-modal-period"></div>
        </div>
        <div class="tv-modal-body">
            <div class="tv-modal-h4">Included Features</div>
            <ul class="tv-modal-feats" id="tv-modal-feats"></ul>
        </div>
        <div class="tv-modal-footer">
            <button id="tv-modal-action-btn" class="tv-modal-btn"></button>
        </div>
    </div>
</div>

<!-- PROFESSIONAL PROCESSING OVERLAY -->
<div id="tv-trial-processing">
    <div class="tv-trial-card">
        <div class="tv-trial-icon-wrap">
            <div class="tv-trial-icon-bg"></div>
            <!-- WhatsApp Logo -->
            <svg class="tv-trial-svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.536 0 1.52 1.115 2.988 1.264 3.186.149.198 2.19 3.361 5.27 5.013 2.19 1.174 2.19.782 2.587.73.992-.123 1.76-1.015 1.76-1.93 0-.916-.149-1.612-.298-1.687zM12.001 22A10.001 10.001 0 0 1 3.55 6.72L2.094 1.201l5.632 1.486A10.001 10.001 0 1 1 12 22zm0-22C6.477 0 2 4.477 2 10c0 1.83.468 3.548 1.284 5.04L2 22l7.086-1.254A9.957 9.957 0 0 0 12 20c5.523 0 10-4.477 10-10S17.523 0 12 0z"/>
            </svg>
        </div>
        
        <div class="tv-trial-title">Initializing Trial...</div>
        <div class="tv-trial-desc">Please wait while we prepare your secure WhatsApp connection.</div>
        
        <div class="tv-trial-details">
            <div>
                <div class="tv-td-label">Selected Plan</div>
                <div class="tv-td-val" id="tv-proc-plan">...</div>
            </div>
            <div style="text-align:right;">
                <div class="tv-td-label">Amount</div>
                <div class="tv-td-val" id="tv-proc-price">...</div>
            </div>
        </div>
        
        <div class="tv-loader-track">
            <div class="tv-loader-fill" id="tv-proc-fill"></div>
        </div>
        <div class="tv-status-text" id="tv-status-msg">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            Connecting to secure server...
        </div>
    </div>
</div>

<script>
    // --- DATA ---
    const PLANS = <?php echo !empty($json_plans) ? json_encode($json_plans) : '[]'; ?>;
    const CURRENCIES = <?php echo json_encode($json_currencies); ?>;
    const IS_LOGGED_IN = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
    const CONFIG = {
        trialDelay: <?php echo isset($config['trial_delay']) ? $config['trial_delay'] : 3; ?>,
        waNumber: "<?php echo isset($config['wa_number']) ? esc_js($config['wa_number']) : ''; ?>",
        waTemplate: "<?php echo isset($wa_template) ? esc_js($wa_template) : ''; ?>",
        subUrl: "<?php echo esc_js($subs_url); ?>"
    };

    let activeCurrency = 'USD';

    // --- LOGIC ---
    function init() {
        const saved = sessionStorage.getItem('tv_pref_currency');
        
        // --- DEVELOPMENT / FIX MODE ---
        // I have commented out the 'if(saved)' check below.
        // This ensures the popup ALWAYS shows for you to test.
        // UNCOMMENT the block below when you are ready to go live.
        
        /* if (saved && CURRENCIES[saved]) {
             setCurrency(saved);
        } else {
             renderGate();
             showGate();
        } 
        */

        // For now, ALWAYS show the gate:
        renderGate();
        showGate();
    }

    function renderGate() {
        const grid = document.getElementById('tv-currency-list');
        grid.innerHTML = '';
        
        const keys = Object.keys(CURRENCIES);
        if(keys.length === 0) {
            grid.innerHTML = '<p style="color:red; grid-column:span 2; text-align:center;">No currencies loaded. Please check admin settings.</p>';
            return;
        }

        keys.forEach(code => {
            const curr = CURRENCIES[code];
            const btn = document.createElement('div');
            btn.className = 'tv-gate-btn';
            
            // Flag Icon
            const flag = getFlagEmoji(curr.flag || code.substring(0,2));
            btn.innerHTML = `<span style="font-size:1.2rem; margin-right:8px;">${flag}</span> <strong>${code}</strong> <span style="margin-left:auto; opacity:0.5;">${curr.symbol}</span>`;
            
            btn.onclick = () => setCurrency(code);
            grid.appendChild(btn);
        });
    }

    function showGate() {
        const el = document.getElementById('tv-currency-overlay');
        // 1. Set display flex (it is currently none)
        el.style.display = 'flex';
        // 2. Small delay to allow browser to render 'flex' before applying opacity transition
        setTimeout(() => el.classList.add('visible'), 50);
    }

    function setCurrency(code) {
        activeCurrency = code;
        sessionStorage.setItem('tv_pref_currency', code);
        
        // Update Trigger
        document.getElementById('tv-active-curr-code').innerText = code;
        if(CURRENCIES[code]) {
            document.getElementById('tv-active-curr-icon').innerText = getFlagEmoji(CURRENCIES[code].flag);
        }
        document.getElementById('tv-curr-trigger').style.display = 'flex';

        // Hide Gate
        const gate = document.getElementById('tv-currency-overlay');
        gate.classList.remove('visible');
        setTimeout(() => gate.style.display = 'none', 400); // Wait for CSS transition (0.4s)

        renderPlans();
    }

    // Convert 2-letter code to Emoji Flag
    function getFlagEmoji(countryCode) {
        if (!countryCode) return '??';
        const codePoints = countryCode.toUpperCase().split('').map(char => 127397 + char.charCodeAt());
        return String.fromCodePoint(...codePoints);
    }

    function renderPlans() {
        const container = document.getElementById('tv-plans-container');
        container.innerHTML = '';
        
        // Safety check
        if(!CURRENCIES[activeCurrency]) {
            // Fallback if active currency is invalid
            activeCurrency = 'USD';
            if(!CURRENCIES['USD']) return; // Critical failure
        }

        const curr = CURRENCIES[activeCurrency];

        PLANS.forEach(plan => {
            const isPremium = plan.is_featured;
            const priceVal = Math.ceil(plan.base_price * curr.rate).toLocaleString();
            const priceStr = curr.symbol + priceVal;
            
            const card = document.createElement('div');
            card.className = `tv-compact-card ${isPremium ? 'premium' : 'standard'}`;
            card.onclick = () => openPlanModal(plan.id, priceStr);

            // Top Features (First 3)
            const featsHtml = plan.features.slice(0, 3).map(f => {
                if(!f.trim()) return '';
                return `<li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="${isPremium?'#cbd5e1':'#64748b'}" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> ${f}</li>`;
            }).join('');
            
            // Multi-Device Preview
            const multiHtml = plan.multi_device ? 
                `<div style="display:flex; align-items:center; gap:8px; font-size:0.75rem; font-weight:700; margin-bottom:12px; color:${isPremium ? '#34d399' : '#10b981'};">
                    <div style="padding:4px; border-radius:6px; background:${isPremium ? 'rgba(52,211,153,0.1)' : '#ecfdf5'}; display:flex;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></div>
                    Multi-Screen Supported
                </div>` : '';

            card.innerHTML = `
                ${isPremium ? `<div class="tv-cc-badge">${plan.tag || 'Best Value'}</div>` : ''}
                
                <div class="tv-cc-name">${plan.name}</div>
                <div class="tv-cc-price">${priceStr}</div>
                <div class="tv-cc-period">billed every ${plan.duration} days</div>
                
                <div class="tv-cc-divider"></div>
                
                ${multiHtml}
                <ul class="tv-cc-feats">
                    ${featsHtml}
                    ${plan.features.length > 3 ? `<li style="opacity:0.6; font-size:0.75rem;">+ ${(plan.features.length - 3)} more features</li>` : ''}
                </ul>
                
                <div class="tv-cc-action">
                    Select Plan <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </div>
            `;
            
            container.appendChild(card);
        });
        
        requestAnimationFrame(() => container.classList.add('visible'));
    }

    // --- MODAL LOGIC ---
    function openPlanModal(planId, priceStr) {
        const plan = PLANS.find(p => p.id === planId);
        if(!plan) return;
        
        const isPremium = plan.is_featured;
        
        // Populate
        document.getElementById('tv-modal-title').innerText = plan.name;
        document.getElementById('tv-modal-price').innerText = priceStr;
        document.getElementById('tv-modal-period').innerText = `Billed every ${plan.duration} days`;
        
        // Features
        const list = document.getElementById('tv-modal-feats');
        list.innerHTML = plan.features.map(f => `<li><div class="tv-modal-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4"><polyline points="20 6 9 17 4 12"/></svg></div>${f}</li>`).join('');
        if(plan.multi_device) list.innerHTML = `<li><div class="tv-modal-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></div><strong>Multi-Screen Supported</strong></li>` + list.innerHTML;
        
        // Header Style
        const header = document.getElementById('tv-modal-header');
        header.className = `tv-modal-header ${isPremium ? 'premium' : ''}`;
        
        // Button
        const btn = document.getElementById('tv-modal-action-btn');
        btn.className = `tv-modal-btn ${isPremium ? 'premium' : 'standard'}`;
        
        if (IS_LOGGED_IN) {
            btn.innerHTML = `Subscribe Now <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>`;
            btn.onclick = () => handleSubscribe(plan.id);
        } else {
            btn.innerHTML = `Request Trial <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>`;
            // Safely pass plan name and price string to handler
            btn.onclick = () => handleTrial(plan.name, priceStr);
        }
        
        // Show
        const overlay = document.getElementById('tv-modal-overlay');
        overlay.style.display = 'flex';
        requestAnimationFrame(() => overlay.classList.add('visible'));
        document.body.style.overflow = 'hidden'; // Lock scroll
    }

    window.closePlanModal = function() {
        const overlay = document.getElementById('tv-modal-overlay');
        overlay.classList.remove('visible');
        setTimeout(() => { 
            overlay.style.display = 'none'; 
            document.body.style.overflow = ''; 
        }, 300);
    }

    // --- EXECUTION HANDLERS ---
    window.handleSubscribe = function(planId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = CONFIG.subUrl;
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'plan_id'; input.value = planId;
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    };

    window.handleTrial = function(planName, priceStr) {
        // Close modal first
        closePlanModal();
        
        const overlay = document.getElementById('tv-trial-processing');
        const planEl = document.getElementById('tv-proc-plan');
        const priceEl = document.getElementById('tv-proc-price');
        const fill = document.getElementById('tv-proc-fill');
        const status = document.getElementById('tv-status-msg');
        
        // Reset state
        if(fill) fill.style.width = '0%';
        if(status) status.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Connecting to secure server...`;

        // Safety: ensure elements exist
        if(planEl) planEl.innerText = planName;
        if(priceEl) priceEl.innerText = priceStr;
        
        // Show overlay
        overlay.style.display = 'flex';
        requestAnimationFrame(() => {
            overlay.classList.add('visible');
            
            // Start Animation
            setTimeout(() => {
                if(fill) fill.style.width = '100%';
            }, 100);
            
            // Status Sequence
            setTimeout(() => {
                if(status) status.innerText = 'Verifying plan availability...';
            }, CONFIG.trialDelay * 300);
            
            setTimeout(() => {
                if(status) status.innerHTML = '<strong>Redirecting to WhatsApp...</strong>';
            }, CONFIG.trialDelay * 800);
        });
        
        const msg = CONFIG.waTemplate.replace('{plan_name}', planName);
        const url = `https://wa.me/${CONFIG.waNumber.replace(/[^0-9]/g, '')}?text=${encodeURIComponent(msg)}`;
        
        setTimeout(() => { window.location.href = url; }, CONFIG.trialDelay * 1000);
    };

    // Run Init
    document.addEventListener("DOMContentLoaded", init);
</script>