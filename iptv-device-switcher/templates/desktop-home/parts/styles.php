<style>
    /* --- CORE RESET & VARIABLES --- */
    :root {
        --v24-bg: #030712;
        --v24-surface: rgba(30, 41, 59, 0.4);
        --v24-glass: rgba(15, 23, 42, 0.6);
        --v24-glass-border: rgba(255, 255, 255, 0.08);
        --v24-primary: #6366f1;
        --v24-primary-glow: rgba(99, 102, 241, 0.4);
        --v24-accent: #06b6d4;
        --v24-text-main: #f8fafc;
        --v24-text-muted: #94a3b8;
        --v24-grad-brand: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        --v24-ease: cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* FIX: Scroll Offset for Anchors */
    html {
        scroll-padding-top: 100px; 
    }

    /* FIX: Global Box Sizing */
    *, *::before, *::after {
        box-sizing: border-box;
    }

    body {
        margin: 0; padding: 0; width: 100%;
        background-color: var(--v24-bg) !important;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: var(--v24-text-main);
        overflow-x: hidden;
    }
    
    body.iptv-v18-engine { margin-top: 0 !important; }

    h1, h2, h3 { font-family: 'Outfit', sans-serif; font-weight: 800; line-height: 1.1; margin: 0; }
    p { line-height: 1.6; color: var(--v24-text-muted); margin: 0; }

    /* --- ANIMATION ENGINE --- */
    .reveal { opacity: 0; transform: translateY(30px); transition: all 1s var(--v24-ease); }
    .reveal.active { opacity: 1; transform: translateY(0); }
    
    .fade-in { opacity: 0; transition: opacity 1.2s ease-out; }
    .fade-in.active { opacity: 1; }
    
    .stagger-1 { transition-delay: 0.1s; }
    .stagger-2 { transition-delay: 0.2s; }
    .stagger-3 { transition-delay: 0.3s; }

    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    /* --- OPTIMIZATION: Content Visibility --- */
    /* Skips rendering for off-screen heavy sections */
    #pricing, .v24-stats-bar, .v24-footer {
        content-visibility: auto;
        contain-intrinsic-size: 1px 1000px; /* Estimated height */
    }

    /* --- 1. NAVBAR (Glass) --- */
    .v24-nav {
        position: fixed; top: 0; left: 0; right: 0; height: 80px; z-index: 1000;
        display: flex; align-items: center; justify-content: space-between;
        padding: 0 5%;
        background: rgba(3, 7, 18, 0.1); 
        backdrop-filter: blur(0px);
        border-bottom: 1px solid transparent;
        transition: all 0.4s var(--v24-ease);
    }
    .v24-nav.scrolled {
        background: rgba(3, 7, 18, 0.85);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--v24-glass-border);
    }

    /* BURGER MENU TRIGGER */
    .v24-burger {
        display: flex; align-items: center; justify-content: center;
        width: 40px; height: 40px; border-radius: 8px;
        color: white; font-size: 20px; cursor: pointer;
        background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
        margin-right: 16px; transition: all 0.2s;
    }
    .v24-burger:hover { background: rgba(255,255,255,0.15); border-color: white; }

    .v24-logo-wrap { display: flex; align-items: center; }
    .v24-logo { font-size: 1.5rem; font-weight: 700; color: white; text-decoration: none; display: flex; align-items: center; gap: 12px; }
    .v24-logo-icon { width: 32px; height: 32px; background: var(--v24-grad-brand); border-radius: 8px; display: grid; place-items: center; box-shadow: 0 0 15px var(--v24-primary-glow); }

    .v24-menu { display: flex; gap: 32px; align-items: center; }
    .v24-link { color: #cbd5e1; text-decoration: none; font-weight: 500; font-size: 0.95rem; transition: 0.2s; position: relative; }
    .v24-link:hover { color: white; }
    .v24-link::after { content: ''; position: absolute; bottom: -4px; left: 0; width: 0; height: 2px; background: var(--v24-primary); transition: width 0.3s; }
    .v24-link:hover::after { width: 100%; }

    .v24-nav-btn {
        padding: 10px 24px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px; color: white; text-decoration: none; font-weight: 600; font-size: 0.9rem;
        transition: 0.3s;
    }
    .v24-nav-btn:hover { background: rgba(255,255,255,0.2); border-color: white; }
    .v24-nav-cta { background: var(--v24-primary); border:none; box-shadow: 0 4px 15px var(--v24-primary-glow); }
    .v24-nav-cta:hover { transform: translateY(-2px); box-shadow: 0 8px 25px var(--v24-primary-glow); }

    /* --- SIDEBAR DRAWER (The "Smart" Menu) --- */
    .v24-sidebar-backdrop {
        position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px);
        z-index: 1999; opacity: 0; pointer-events: none; transition: opacity 0.4s var(--v24-ease);
    }
    .v24-sidebar-backdrop.open { opacity: 1; pointer-events: all; }

    .v24-sidebar {
        position: fixed; top: 0; left: 0; bottom: 0; width: 320px;
        background: #0f172a; border-right: 1px solid var(--v24-glass-border);
        z-index: 2000; transform: translateX(-100%);
        transition: transform 0.4s var(--v24-ease);
        display: flex; flex-direction: column; padding: 30px;
        box-shadow: 10px 0 40px rgba(0,0,0,0.5);
    }
    .v24-sidebar.open { transform: translateX(0); }

    .v24-sidebar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
    .v24-close-btn { 
        background: transparent; border: none; color: #64748b; font-size: 24px; cursor: pointer; transition: 0.2s; 
    }
    .v24-close-btn:hover { color: white; transform: rotate(90deg); }

    .v24-side-section { margin-bottom: 30px; }
    .v24-side-title { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 2px; color: #64748b; font-weight: 700; margin-bottom: 16px; }
    
    .v24-side-link {
        display: flex; align-items: center; gap: 12px; padding: 12px;
        color: #cbd5e1; text-decoration: none; font-weight: 500; font-size: 1rem;
        border-radius: 8px; transition: 0.2s;
    }
    .v24-side-link:hover { background: rgba(255,255,255,0.05); color: white; transform: translateX(4px); }
    .v24-side-link.active { background: var(--v24-primary); color: white; }
    .v24-side-link i { width: 24px; text-align: center; color: var(--v24-primary); }

    /* PLAN CARDS IN MENU */
    .v24-menu-plan {
        background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px; padding: 16px; margin-bottom: 10px;
        display: flex; justify-content: space-between; align-items: center;
        transition: 0.2s; text-decoration: none;
    }
    .v24-menu-plan:hover { border-color: var(--v24-primary); background: rgba(99,102,241,0.1); }
    .v24-mp-name { font-weight: 700; color: white; font-size: 0.95rem; }
    .v24-mp-price { font-size: 0.85rem; color: #94a3b8; }
    .v24-mp-arrow { color: var(--v24-primary); opacity: 0; transform: translateX(-5px); transition: 0.2s; }
    .v24-menu-plan:hover .v24-mp-arrow { opacity: 1; transform: translateX(0); }


    /* --- 2. HERO SECTION (Immersive) --- */
    .v24-hero {
        position: relative; 
        height: 100vh;
        padding-top: 120px; /* Space for navbar */
        padding-bottom: 100px;
        display: flex; align-items: center; justify-content: center;
        text-align: center; overflow: hidden;
    }
    .v24-hero-bg {
        position: absolute; inset: 0; z-index: -1;
        background: url('https://images.unsplash.com/photo-1593784991095-a205069470b6?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat;
        /* Initial scale for parallax effect */
        transform: scale(1.1); 
        transition: transform 0.1s linear;
        will-change: transform;
    }
    .v24-hero-overlay {
        position: absolute; inset: 0; z-index: 0;
        background: linear-gradient(to bottom, rgba(3,7,18,0.7) 0%, rgba(3,7,18,0.8) 50%, #030712 100%);
    }
    
    .v24-hero-content { position: relative; z-index: 10; max-width: 1000px; padding: 0 24px; }

    .v24-pill {
        display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px;
        background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 100px; font-size: 0.85rem; font-weight: 600; color: #e2e8f0;
        margin-bottom: 24px; backdrop-filter: blur(8px);
    }
    .v24-dot { width: 8px; height: 8px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 10px #22c55e; }

    .v24-title { 
        font-size: 5rem; letter-spacing: -0.03em; margin-bottom: 24px; color: white; 
        text-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .v24-grad-text { background: var(--v24-grad-brand); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

    .v24-subtitle { font-size: 1.25rem; max-width: 700px; margin: 0 auto 40px; color: #cbd5e1; font-weight: 400; }

    .v24-btn-group { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
    
    /* MAIN CTA BUTTON */
    .v24-btn-xl {
        position: relative; overflow: hidden;
        padding: 18px 40px; font-size: 1.1rem; font-weight: 700; border-radius: 12px;
        background: var(--v24-primary); color: white; text-decoration: none;
        box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.7);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex; align-items: center; gap: 10px;
    }
    .v24-btn-xl:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 30px -5px var(--v24-primary-glow);
    }
    /* Button Shimmer Effect */
    .v24-btn-xl::before {
        content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transform: translateX(-100%);
        transition: 0.5s;
    }
    .v24-btn-xl:hover::before { animation: shimmer 1.5s infinite; }

    .v24-btn-glass {
        padding: 18px 40px; font-size: 1.1rem; font-weight: 700; border-radius: 12px;
        background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
        color: white; text-decoration: none; transition: 0.3s; backdrop-filter: blur(10px);
    }
    .v24-btn-glass:hover { background: rgba(255,255,255,0.15); transform: translateY(-4px); border-color: white; }


    /* --- 3. STATS STRIP (Floating) --- */
    .v24-stats-bar {
        margin-top: -60px; position: relative; z-index: 20;
        max-width: 1200px; margin-left: auto; margin-right: auto; padding: 0 24px;
    }
    .v24-stats-grid {
        display: grid; grid-template-columns: repeat(4, 1fr);
        background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(20px);
        border: 1px solid var(--v24-glass-border); border-radius: 20px;
        padding: 32px; box-shadow: 0 20px 50px -10px rgba(0,0,0,0.5);
    }
    .v24-stat { text-align: center; border-right: 1px solid rgba(255,255,255,0.05); }
    .v24-stat:last-child { border-right: none; }
    .v24-stat-val { font-size: 2.5rem; font-weight: 800; color: white; display: block; margin-bottom: 4px; }
    .v24-stat-lbl { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--v24-accent); font-weight: 700; }


    /* --- 4. PLANS (The Phase 2 Upgrade) --- */
    .v24-section { padding: 120px 5%; max-width: 1400px; margin: 0 auto; }
    .v24-header { text-align: center; margin-bottom: 80px; max-width: 700px; margin-left: auto; margin-right: auto; }
    .v24-header h2 { font-size: 3rem; margin-bottom: 16px; }

    .v24-pricing-grid {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px;
        align-items: stretch; /* Ensures all cards are same height */
    }

    .v24-card {
        background: var(--v24-surface); border: 1px solid var(--v24-glass-border);
        border-radius: 24px; overflow: hidden; position: relative;
        transition: all 0.4s var(--v24-ease);
        display: flex; flex-direction: column; /* CRITICAL FOR BUTTON ALIGNMENT */
        padding: 40px;
        min-height: 600px;
    }
    
    .v24-card:hover {
        transform: translateY(-10px);
        border-color: rgba(99, 102, 241, 0.3);
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.3);
    }

    /* FEATURED CARD */
    .v24-card.featured {
        background: linear-gradient(180deg, rgba(30, 41, 59, 0.6) 0%, rgba(15, 23, 42, 0.9) 100%);
        border: 1px solid var(--v24-primary);
        box-shadow: 0 0 30px rgba(99, 102, 241, 0.1);
        transform: scale(1.05);
        z-index: 10;
    }
    .v24-card.featured:hover { transform: scale(1.05) translateY(-10px); }

    .v24-badge {
        position: absolute; top: 0; left: 50%; transform: translateX(-50%);
        background: var(--v24-primary); color: white; font-size: 0.75rem; font-weight: 800;
        padding: 6px 16px; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;
        letter-spacing: 1px; box-shadow: 0 4px 12px rgba(99,102,241,0.5);
    }

    .v24-plan-name { font-size: 1.5rem; font-weight: 700; color: white; margin-bottom: 8px; }
    .v24-plan-desc { font-size: 0.95rem; color: #94a3b8; margin-bottom: 24px; min-height: 44px; }
    
    .v24-price { font-size: 4rem; font-weight: 800; color: white; line-height: 1; margin-bottom: 32px; letter-spacing: -2px; }
    .v24-price span { font-size: 1rem; color: #94a3b8; font-weight: 500; letter-spacing: normal; }

    .v24-features { list-style: none; padding: 0; margin: 0 0 40px; flex-grow: 1; /* Pushes button down */ }
    .v24-features li { 
        display: flex; align-items: center; gap: 12px; padding: 10px 0; 
        color: #e2e8f0; border-bottom: 1px solid rgba(255,255,255,0.05); 
        font-size: 1rem;
    }
    .v24-features li:last-child { border: none; }
    .v24-check { color: #22c55e; flex-shrink: 0; background: rgba(34, 197, 94, 0.1); width: 24px; height: 24px; border-radius: 50%; display: grid; place-items: center; font-size: 0.75rem; }

    /* BUTTON WRAPPER (Aligned to bottom) */
    .v24-card-action { margin-top: auto; width: 100%; }
    
    .v24-plan-btn {
        display: flex; align-items: center; justify-content: center; width: 100%;
        padding: 16px; border-radius: 12px; font-weight: 700; font-size: 1rem;
        text-decoration: none; transition: 0.3s; cursor: pointer;
    }
    
    .btn-outline { background: transparent; border: 1px solid var(--v24-glass-border); color: white; }
    .btn-outline:hover { background: rgba(255,255,255,0.05); border-color: white; }
    
    .btn-filled { 
        background: var(--v24-primary); color: white; border: none; 
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }
    .btn-filled:hover { background: #4f46e5; box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5); transform: scale(1.02); }


    /* --- 5. FOOTER --- */
    .v24-footer {
        padding: 80px 5%; background: #020617; border-top: 1px solid var(--v24-glass-border);
        text-align: center; color: var(--v24-text-muted); font-size: 0.9rem;
    }

    /* --- RESPONSIVE --- */
    @media (max-width: 1024px) {
        .v24-title { font-size: 3.5rem; }
        .v24-pricing-grid { grid-template-columns: 1fr; max-width: 500px; margin: 0 auto; gap: 60px; }
        .v24-card.featured { transform: scale(1); border: 2px solid var(--v24-primary); }
        .v24-card { min-height: auto; }
        .v24-stats-grid { grid-template-columns: 1fr 1fr; gap: 30px; }
        .v24-stat { border-right: none; }
        .v24-menu { display: none; }
        .v24-burger { display: flex; } /* Show on mobile automatically */
    }
</style>
