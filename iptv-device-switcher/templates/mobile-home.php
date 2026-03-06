<?php
/* Template Name: v13 Mobile Epic */
get_header(); 
?>

<style>
    /* Mobile Specific Overrides */
    .v12-mobile-hero {
        /* UPDATED: Compact height set to 67vh per request */
        min-height: 67vh !important; 
    }
    
    .v12-utility-banner {
        margin: 20px 24px 50px;
        padding: 30px 24px;
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 20px;
        text-align: center;
        border: 1px solid rgba(99, 102, 241, 0.2);
        box-shadow: 0 10px 30px -10px rgba(0,0,0,0.3);
    }
</style>

<main style="padding-bottom: 60px; background: #fff;">

    <!-- 1. HERO SECTION (Dark Immersive) -->
    <section class="v12-mobile-hero" style="background: #111; position: relative; display: flex; align-items: flex-end; overflow: hidden; border-bottom-left-radius: 30px; border-bottom-right-radius: 30px;">
        <div class="v12-mobile-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to top, #000 10%, #222 100%); opacity: 0.9;"></div>
        
        <!-- FLOATING MOBILE HEADER -->
        <div style="position: absolute; top: 24px; right: 24px; z-index: 20; display: flex; align-items: center; gap: 8px;">
            <?php if ( is_user_logged_in() ) : ?>
                <a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" style="background: #6366f1; color: white; padding: 10px 20px; border-radius: 99px; font-size: 0.85rem; font-weight: 700; text-decoration: none; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);">
                    Dashboard
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url( home_url( '/login' ) ); ?>" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(8px); color: white; padding: 10px 18px; border-radius: 99px; font-size: 0.85rem; font-weight: 600; text-decoration: none; border: 1px solid rgba(255,255,255,0.2);">Log in</a>
                <a href="<?php echo esc_url( home_url( '/signup' ) ); ?>" style="background: #6366f1; color: white; padding: 10px 18px; border-radius: 99px; font-size: 0.85rem; font-weight: 700; text-decoration: none; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.45);">Sign up</a>
            <?php endif; ?>
        </div>

        <div class="v12-mobile-content" style="position: relative; z-index: 2; padding: 30px; width: 100%; padding-bottom: 40px;">
            <div style="font-weight: 800; font-size: 1.2rem; color: white; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <div style="width: 20px; height: 20px; background: #6366f1; border-radius: 4px;"></div> XOFLIX TV
            </div>
            
            <h1 style="font-size: 3.5rem; line-height: 1; margin: 0 0 16px; color: white; font-weight: 800; letter-spacing: -2px;">
                TV without<br>Limits.
            </h1>
            <p style="color: rgba(255,255,255,0.9); font-size: 1.1rem; margin-bottom: 32px; line-height: 1.5;">
                25,000+ Channels. 4K Sports. <br>Zero Buffering.
            </p>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php if ( is_user_logged_in() ) : ?>
                    <a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" class="v12-btn-primary" style="width: 100%; text-align: center; background: #6366f1; color: white; padding: 16px; border-radius: 12px; font-weight: 700; text-decoration: none;">Go to Dashboard</a>
                <?php else : ?>
                    <!-- PRIMARY CTA -->
                    <a href="<?php echo esc_url( home_url( '/signup' ) ); ?>" class="v12-btn-primary" style="width: 100%; text-align: center; background: #6366f1; color: white; padding: 16px; border-radius: 12px; font-weight: 700; text-decoration: none; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);">
                        Create Free Account
                    </a>
                    <!-- SECONDARY CTA -->
                    <a href="<?php echo esc_url( add_query_arg( 'tv_flow', 'subscription_plans', home_url( '/' ) ) ); ?>" style="width: 100%; text-align: center; background: rgba(255,255,255,0.12); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.2); color: white; padding: 15px; border-radius: 12px; font-weight: 600; text-decoration: none; display: block;">
                        Browse Plans
                    </a>
                    <div style="text-align: center; font-size: 0.85rem; color: rgba(255,255,255,0.55); margin-top: 2px;">
                        No credit card required.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- 2. QUICK CATEGORY SCROLL (Horizontal) -->
    <section style="margin-top: 50px; margin-bottom: 50px;">
        <div style="padding: 0 24px 16px; font-weight: 700; color: #9ca3af; font-size: 0.8rem; letter-spacing: 1px; text-transform: uppercase;">
            Browse Content
        </div>
        <div class="v12-scroll-container" style="display: flex; overflow-x: auto; gap: 16px; padding: 0 24px 10px; scrollbar-width: none;">
            <div class="v12-feature-card-sm" style="min-width: 140px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 16px; padding: 20px; display: flex; flex-direction: column; align-items: center;">
                <i class="fas fa-futbol" style="font-size: 1.8rem; color: #3b82f6; margin-bottom: 10px;"></i>
                <span style="font-weight: 600; font-size: 0.9rem; color: #111;">Sports</span>
            </div>
            <div class="v12-feature-card-sm" style="min-width: 140px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 16px; padding: 20px; display: flex; flex-direction: column; align-items: center;">
                <i class="fas fa-film" style="font-size: 1.8rem; color: #a855f7; margin-bottom: 10px;"></i>
                <span style="font-weight: 600; font-size: 0.9rem; color: #111;">Movies</span>
            </div>
            <div class="v12-feature-card-sm" style="min-width: 140px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 16px; padding: 20px; display: flex; flex-direction: column; align-items: center;">
                <i class="fas fa-tv" style="font-size: 1.8rem; color: #10b981; margin-bottom: 10px;"></i>
                <span style="font-weight: 600; font-size: 0.9rem; color: #111;">Series</span>
            </div>
            <div class="v12-feature-card-sm" style="min-width: 140px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 16px; padding: 20px; display: flex; flex-direction: column; align-items: center;">
                <i class="fas fa-child" style="font-size: 1.8rem; color: #f59e0b; margin-bottom: 10px;"></i>
                <span style="font-weight: 600; font-size: 0.9rem; color: #111;">Kids</span>
            </div>
        </div>
    </section>

    <!-- 3. WHY CHOOSE US (Stacked) -->
    <section class="v12-container" style="padding: 0 24px; margin-bottom: 60px;">
        <h2 style="font-size: 2rem; margin: 0 0 30px; font-weight: 800; color: #111;">Why XOFLIX TV?</h2>
        
        <div class="v12-white-card" style="margin-bottom: 20px; display: flex; align-items: flex-start; gap: 20px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <div style="background: #f3f4f6; padding: 12px; border-radius: 12px; color: #6366f1; font-size: 1.5rem; flex-shrink: 0;">
                <i class="fas fa-bolt"></i>
            </div>
            <div>
                <h3 style="font-size: 1.2rem; margin: 0 0 8px; font-weight: 700; color: #111;">Anti-Freeze 3.0</h3>
                <p style="margin: 0; font-size: 0.95rem; color: #6b7280; line-height: 1.5;">Proprietary technology ensures your game never lags, even on match day.</p>
            </div>
        </div>

        <div class="v12-white-card" style="display: flex; align-items: flex-start; gap: 20px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <div style="background: #f3f4f6; padding: 12px; border-radius: 12px; color: #6366f1; font-size: 1.5rem; flex-shrink: 0;">
                <i class="fas fa-desktop"></i>
            </div>
            <div>
                <h3 style="font-size: 1.2rem; margin: 0 0 8px; font-weight: 700; color: #111;">Multi-Device</h3>
                <p style="margin: 0; font-size: 0.95rem; color: #6b7280; line-height: 1.5;">One account works on Smart TV, Firestick, iPhone, Android, and PC.</p>
            </div>
        </div>
    </section>

    <!-- 4. HOW IT WORKS (Timeline Fixed) -->
    <section class="v12-container" style="padding: 0 24px; margin-bottom: 60px;">
        <h2 style="font-size: 2rem; margin: 0 0 40px; font-weight: 800; color: #111;">Setup is easy.</h2>
        
        <div style="position: relative; padding-left: 30px; margin-left: 10px; border-left: 2px solid #e5e7eb;">
            <!-- Step 1 -->
            <div style="margin-bottom: 50px; position: relative;">
                <div style="position: absolute; left: -39px; top: 0; width: 16px; height: 16px; background: #6366f1; border-radius: 50%; box-shadow: 0 0 0 4px #fff;"></div>
                <h4 style="font-size: 1.1rem; margin: 0 0 8px; font-weight: 700; color: #111;">1. Create Account</h4>
                <p style="margin: 0; font-size: 0.95rem; color: #6b7280;">Sign up in 30 seconds. No credit card required for trial.</p>
            </div>
            
            <!-- Step 2 -->
            <div style="margin-bottom: 50px; position: relative;">
                <div style="position: absolute; left: -39px; top: 0; width: 16px; height: 16px; background: #e5e7eb; border-radius: 50%; box-shadow: 0 0 0 4px #fff;"></div>
                <h4 style="font-size: 1.1rem; margin: 0 0 8px; font-weight: 700; color: #111;">2. Get Playlist</h4>
                <p style="margin: 0; font-size: 0.95rem; color: #6b7280;">Receive M3U link and credentials instantly via email.</p>
            </div>
            
            <!-- Step 3 -->
            <div style="position: relative;">
                <div style="position: absolute; left: -39px; top: 0; width: 16px; height: 16px; background: #e5e7eb; border-radius: 50%; box-shadow: 0 0 0 4px #fff;"></div>
                <h4 style="font-size: 1.1rem; margin: 0 0 8px; font-weight: 700; color: #111;">3. Watch</h4>
                <p style="margin: 0; font-size: 0.95rem; color: #6b7280;">Login to Smarters, Tivimate, or our Web Player and enjoy.</p>
            </div>
        </div>
    </section>

    <!-- 5. COMPATIBLE DEVICES -->
    <section class="v12-container" style="padding: 0 24px; margin-bottom: 60px;">
        <h2 style="font-size: 2rem; margin: 0 0 30px; font-weight: 800; color: #111;">Works Everywhere</h2>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
            <div style="background: #f9fafb; border-radius: 16px; padding: 20px; display: flex; align-items: center; gap: 10px; font-weight: 600; color: #333;">
                <i class="fab fa-apple" style="font-size: 1.5rem;"></i> iOS / Apple TV
            </div>
            <div style="background: #f9fafb; border-radius: 16px; padding: 20px; display: flex; align-items: center; gap: 10px; font-weight: 600; color: #333;">
                <i class="fab fa-android" style="font-size: 1.5rem;"></i> Android TV
            </div>
            <div style="background: #f9fafb; border-radius: 16px; padding: 20px; display: flex; align-items: center; gap: 10px; font-weight: 600; color: #333;">
                <i class="fas fa-fire" style="font-size: 1.5rem;"></i> Firestick
            </div>
            <div style="background: #f9fafb; border-radius: 16px; padding: 20px; display: flex; align-items: center; gap: 10px; font-weight: 600; color: #333;">
                <i class="fas fa-desktop" style="font-size: 1.5rem;"></i> Windows / Mac
            </div>
        </div>
    </section>

    <!-- 6. FAQ ACCORDION -->
    <section class="v12-container" style="padding: 0 24px; margin-bottom: 60px;">
        <h2 style="font-size: 2rem; margin: 0 0 30px; font-weight: 800; color: #111;">Questions?</h2>
        
        <div class="faq-item" style="border-bottom: 1px solid #e5e7eb; padding-bottom: 15px; margin-bottom: 15px;">
            <div class="faq-header" style="font-weight: 600; margin-bottom: 10px; cursor: pointer; display: flex; justify-content: space-between; color: #111;">
                Do I need a VPN?
                <i class="fas fa-chevron-down faq-icon" style="color: #9ca3af;"></i>
            </div>
            <div class="faq-content" style="font-size: 0.95rem; color: #6b7280; line-height: 1.5;">No, our connections are secure and private. However, you are free to use one if you wish.</div>
        </div>
        
        <div class="faq-item" style="border-bottom: 1px solid #e5e7eb; padding-bottom: 15px; margin-bottom: 15px;">
            <div class="faq-header" style="font-weight: 600; margin-bottom: 10px; cursor: pointer; display: flex; justify-content: space-between; color: #111;">
                Can I watch on 2 TVs?
                <i class="fas fa-chevron-down faq-icon" style="color: #9ca3af;"></i>
            </div>
            <div class="faq-content" style="font-size: 0.95rem; color: #6b7280; line-height: 1.5;">Standard plans are for 1 connection. You can add extra connections at checkout.</div>
        </div>
    </section>

    <!-- 7. UTILITY BANNER (Relocated Mobile) -->
    <section class="v12-utility-banner">
        <h3 style="color:white; font-weight:800; font-size:1.3rem; margin-bottom:12px;">Tools & Guides</h3>
        <p style="color:rgba(255,255,255,0.7); font-size:0.9rem; margin-bottom:24px; line-height:1.6;">Need to convert a link or set up your app? Use our specialized tools for a seamless experience.</p>
        
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <!-- M3U CONVERTER -->
            <a href="<?php echo esc_url( home_url( '/m3u-parser' ) ); ?>" style="background: #6366f1; color: white; padding: 14px; border-radius: 12px; font-weight: 700; text-decoration: none; display: block; font-size: 0.9rem;">
                <i class="fas fa-magic" style="margin-right:8px;"></i> M3U Converter
            </a>
            <!-- SETUP GUIDE -->
            <a href="https://kuality1st.com/iptv-guide/" target="_blank" rel="noopener" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; padding: 14px; border-radius: 12px; font-weight: 700; text-decoration: none; display: block; font-size: 0.9rem;">
                <i class="fas fa-book" style="margin-right:8px;"></i> View Setup Guide
            </a>
        </div>
    </section>

    <!-- 8. FINAL CTA -->
    <section class="v12-container" style="padding: 0 24px; text-align: center; margin-bottom: 80px;">
        <div class="v12-white-card" style="border: 1px solid #6366f1; background: radial-gradient(circle at center, rgba(99,102,241,0.1), #fff); border-radius: 24px; padding: 40px 20px;">
            <h2 style="margin: 0 0 15px; font-weight: 800; color: #111;">Ready?</h2>
            <p style="margin: 0 0 30px; font-size: 1.1rem; color: #4b5563;">Join 50,000+ happy streamers.</p>
            <?php if ( is_user_logged_in() ) : ?>
                <a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" class="v12-btn-primary" style="width: 100%; display: inline-block; background: #6366f1; color: white; padding: 16px; border-radius: 12px; font-weight: 700; text-decoration: none;">Go to Dashboard</a>
            <?php else : ?>
                <a href="<?php echo esc_url( home_url( '/signup' ) ); ?>" class="v12-btn-primary" style="width: 100%; display: block; background: #6366f1; color: white; padding: 16px; border-radius: 12px; font-weight: 700; text-decoration: none; margin-bottom: 12px;">Create Free Account</a>
                <a href="<?php echo esc_url( home_url( '/login' ) ); ?>" style="width: 100%; display: block; background: #f9fafb; color: #374151; padding: 15px; border-radius: 12px; font-weight: 600; text-decoration: none; border: 1px solid #e5e7eb;">Already have an account? Log in</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer style="text-align: center; color: #9ca3af; padding: 40px 0; font-size: 0.8rem; border-top: 1px solid #e5e7eb;">
        &copy; <?php echo date('Y'); ?> XOFLIX TV. All rights reserved.
    </footer>
</main>

<script>
    // Simple FAQ Accordion Logic
    jQuery(document).ready(function($) {
        $('.faq-header').on('click', function() {
            $(this).next('.faq-content').slideToggle();
            $(this).find('.faq-icon').toggleClass('fa-chevron-up fa-chevron-down');
        });
    });
</script>

<?php get_footer(); ?>