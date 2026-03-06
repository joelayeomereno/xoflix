<?php
/* Template Name: Premium Tablet Home */
get_header(); 
?>

<!-- 1. TABLET HEADER (Sticky & Glass) -->
<header class="v12-tablet-header" style="position: fixed; top: 0; left: 0; right: 0; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; z-index: 100; backdrop-filter: blur(10px); background: rgba(255,255,255,0.8);">
    <!-- Logo -->
    <div class="logo-area" style="display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1.2rem;">
        <div style="width: 28px; height: 28px; background: var(--v12-primary); border-radius: 8px;"></div>
        <span>XOFLIX TV</span>
    </div>

    <!-- Right Side: Auth Actions -->
    <div class="auth-buttons" style="display: flex; gap: 15px;">
        <?php if ( is_user_logged_in() ) : ?>
             <?php $current_user = wp_get_current_user(); ?>
            <span style="display:flex; align-items:center; font-weight:600; color:var(--v12-text-main); font-size:0.9rem;">
                Hi, <?php echo esc_html( $current_user->first_name ?: 'User' ); ?>
            </span>
            <a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" class="v12-btn-primary" style="padding: 10px 24px; text-decoration: none;">
                <i class="fas fa-th-large" style="margin-right:6px;"></i> Dashboard
            </a>
        <?php else : ?>
            <a href="<?php echo esc_url( home_url( '/login' ) ); ?>" class="v12-btn-outline" style="padding: 10px 24px;">Log in</a>
            <!-- CTA Updated -->
            <a href="<?php echo esc_url( add_query_arg( 'tv_flow', 'subscription_plans', home_url( '/' ) ) ); ?>" class="v12-btn-primary" style="padding: 10px 24px; text-decoration: none;">Select Plan</a>
        <?php endif; ?>
    </div>
</header>

<!-- 2. TABLET HERO (Split Layout) -->
<div class="tablet-layout" style="display: grid; grid-template-columns: 1fr 1fr; min-height: 85vh; padding-top: 80px;">
    
    <!-- Left: Content & CTA -->
    <div class="tablet-left" style="display: flex; flex-direction: column; justify-content: center; padding: 40px 50px;">
        <div class="reveal-on-scroll">
            <span style="display: inline-block; padding: 6px 14px; border-radius: 99px; background: rgba(99,102,241,0.1); color: var(--v12-primary); font-size: 0.85rem; font-weight: 600; margin-bottom: 24px;">
                Optimized for Tablet
            </span>

            <h1 style="font-size: 3rem; line-height: 1.1; font-weight: 800; margin-bottom: 24px;">
                Studio Quality<br>Streaming.
            </h1>
            
            <p style="font-size: 1.1rem; color: var(--v12-text-muted); margin-bottom: 32px; line-height: 1.6;">
                Experience 20,000+ channels in stunning 4K HDR. Designed specifically for retina displays with zero buffering.
            </p>

            <div style="display: flex; gap: 16px;">
                <?php if ( is_user_logged_in() ) : ?>
                    <a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" class="v12-btn-primary" style="padding: 14px 32px; text-decoration: none;">Go to Dashboard</a>
                <?php else : ?>
                    <!-- CTA Updated: Select Plan for Trial -->
                    <a href="<?php echo esc_url( add_query_arg( 'tv_flow', 'subscription_plans', home_url( '/' ) ) ); ?>" class="v12-btn-primary" style="padding: 14px 32px; text-decoration: none;">Select a Plan for Trial</a>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 40px; display: flex; align-items: center; gap: 15px; color: var(--v12-text-muted); font-size: 0.9rem;">
                <i class="fab fa-apple" style="font-size: 1.5rem;"></i>
                <i class="fab fa-android" style="font-size: 1.5rem;"></i>
                <span>Native Player Support</span>
            </div>
        </div>
    </div>

    <!-- Right: Visual -->
    <div class="tablet-right" style="position: relative; overflow: hidden; background: #eee;">
        <img src="https://source.unsplash.com/random/1000x1200/?technology,screen,abstract" 
             style="width: 100%; height: 100%; object-fit: cover; opacity: 0.8;" 
             alt="Tablet Experience" 
             fetchpriority="high">
        <!-- Gradient Overlay -->
        <div style="position: absolute; inset: 0; background: linear-gradient(to right, #fff, transparent 20%);"></div>
    </div>
</div>

<!-- 3. FEATURE GRID (Tablet 2-col Layout) -->
<section class="v12-container" style="padding: 60px 24px; padding-bottom: 60px;">
    <div class="features-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
        <div class="v12-white-card reveal" style="padding: 30px; border: 1px solid var(--v12-border); border-radius: 12px;">
            <i class="fas fa-bolt" style="font-size: 2rem; color: var(--v12-primary); margin-bottom: 20px;"></i>
            <h3 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">Instant Activation</h3>
            <p style="color: var(--v12-text-muted);">Get your line credentials immediately via email after checkout.</p>
        </div>
        <div class="v12-white-card reveal" style="padding: 30px; border: 1px solid var(--v12-border); border-radius: 12px;">
            <i class="fas fa-shield-alt" style="font-size: 2rem; color: var(--v12-primary); margin-bottom: 20px;"></i>
            <h3 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">Secure & Private</h3>
            <p style="color: var(--v12-text-muted);">End-to-end encryption for complete anonymity while watching.</p>
        </div>
        <div class="v12-white-card reveal" style="padding: 30px; border: 1px solid var(--v12-border); border-radius: 12px;">
            <i class="fas fa-globe" style="font-size: 2rem; color: var(--v12-primary); margin-bottom: 20px;"></i>
            <h3 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">Global Content</h3>
            <p style="color: var(--v12-text-muted);">Channels from 190+ countries in one curated playlist.</p>
        </div>
        <div class="v12-white-card reveal" style="padding: 30px; border: 1px solid var(--v12-border); border-radius: 12px;">
            <i class="fas fa-headset" style="font-size: 2rem; color: var(--v12-primary); margin-bottom: 20px;"></i>
            <h3 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">24/7 Support</h3>
            <p style="color: var(--v12-text-muted);">Real humans ready to help you set up on any app.</p>
        </div>
    </div>
</section>

<!-- 4. UTILITY TOOLS (Relocated Tablet Section) -->
<section class="v12-container" style="padding: 0 24px 100px;">
    <div style="background: #f8fafc; border: 1px solid var(--v12-border); border-radius: 24px; padding: 40px; display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 30px;">
        <div style="max-width: 100%;">
            <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--v12-text-main); margin-bottom: 12px;">Need assistance or conversion?</h3>
            <p style="color: var(--v12-text-muted); font-size: 1rem; margin: 0;">Our smart parser allows you to convert standard M3U links into Xtream Codes credentials, and our step-by-step guide walks you through any device setup.</p>
        </div>
        <div style="display: flex; gap: 15px;">
            <a href="https://kuality1st.com/iptv-guide/" target="_blank" rel="noopener" class="v12-btn-outline" style="padding: 18px 30px; text-decoration: none; border-color: var(--v12-primary); color: var(--v12-primary); font-weight: 700;">
                Setup Guide <i class="fas fa-book" style="margin-left:8px;"></i>
            </a>
            <a href="<?php echo esc_url( home_url( '/m3u-parser' ) ); ?>" class="v12-btn-primary" style="padding: 18px 30px; text-decoration: none; white-space: nowrap;">
                Open Converter <i class="fas fa-magic" style="margin-left:8px;"></i>
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>