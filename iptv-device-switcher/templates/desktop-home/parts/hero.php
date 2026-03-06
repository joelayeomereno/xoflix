<!-- HERO -->
<section class="v24-hero" id="hero">
    <div class="v24-hero-bg" id="parallax-bg"></div>
    
    <!-- OPTIMIZATION: High priority fetch for LCP -->
    <img src="https://images.unsplash.com/photo-1593784991095-a205069470b6?q=80&w=2070&auto=format&fit=crop" 
         alt="" 
         style="display:none;" 
         fetchpriority="high" />

    <div class="v24-hero-overlay"></div>
    
    <div class="v24-hero-content">
        <div class="v24-pill reveal stagger-1">
            <span class="v24-dot"></span> No Buffering Technology 3.0
        </div>
        
        <h1 class="v24-title reveal stagger-1">
            TV Beyond <br>
            <span class="v24-grad-text">Imagination.</span>
        </h1>
        
        <p class="v24-subtitle reveal stagger-2">
            Instant access to 25,000+ live channels and 120,000+ movies in stunning 4K HDR. 
            Compatible with all your devices. Cancel anytime.
        </p>
        
        <div class="v24-btn-group reveal stagger-3" style="display:flex; justify-content:center; gap:20px; flex-wrap:wrap;">
            <?php if ( is_user_logged_in() ) : ?>
                <a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" class="v24-btn-glass">
                    Go to Dashboard <i class="fas fa-th-large" style="margin-left:8px;"></i>
                </a>
            <?php endif; ?>

            <!-- PRIMARY CTA: Select Plan -->
            <a href="<?php echo esc_url( add_query_arg( 'tv_flow', 'subscription_plans', home_url( '/' ) ) ); ?>" 
               class="v24-btn-xl">
                Select a Plan for Trial <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <div class="reveal stagger-3" style="margin-top: 40px; color: rgba(255,255,255,0.5); font-size: 0.9rem; display: flex; gap: 30px; justify-content: center;">
            <span><i class="fas fa-check-circle" style="color:var(--v24-primary); margin-right:6px;"></i> No Credit Card</span>
            <span><i class="fas fa-check-circle" style="color:var(--v24-primary); margin-right:6px;"></i> Instant Setup</span>
        </div>
    </div>
</section>