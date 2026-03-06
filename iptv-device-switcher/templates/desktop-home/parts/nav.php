<!-- NAV -->
<nav class="v24-nav" id="navbar">
    
    <div class="v24-logo-wrap">
        <!-- Burger Trigger -->
        <div class="v24-burger" id="sidebar-trigger">
            <i class="fas fa-bars"></i>
        </div>

        <a href="<?php echo home_url(); ?>" class="v24-logo">
            <div class="v24-logo-icon"><i class="fas fa-play"></i></div>
            XOFLIX TV
        </a>
    </div>

    <div class="v24-menu">
        <a href="#hero" class="v24-link">Home</a>
        <!-- UPDATED: Point to Premium Plans Page -->
        <a href="<?php echo esc_url(add_query_arg('tv_flow', 'subscription_plans', home_url('/'))); ?>" class="v24-link">Subscription</a>
        <a href="#pricing" class="v24-link">Pricing</a>
        <a href="#faq" class="v24-link">Help</a>
    </div>
    
    <div style="display:flex; gap:16px; align-items:center;">
        <?php if ( is_user_logged_in() ) : ?>
            <a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" class="v24-nav-btn v24-nav-cta">Dashboard</a>
        <?php else : ?>
            <a href="<?php echo esc_url( home_url( '/login' ) ); ?>" class="v24-nav-btn">Log In</a>
            <a href="<?php echo esc_url( home_url( '/signup' ) ); ?>" class="v24-nav-btn v24-nav-cta">Get Started</a>
        <?php endif; ?>
    </div>
</nav>

<!-- SIDEBAR OVERLAY -->
<div class="v24-sidebar-backdrop" id="sidebar-backdrop"></div>

<!-- SIDEBAR DRAWER -->
<div class="v24-sidebar" id="sidebar-drawer">
    <div class="v24-sidebar-header">
        <div class="v24-logo">
            <div class="v24-logo-icon"><i class="fas fa-play"></i></div>
            XOFLIX TV
        </div>
        <button class="v24-close-btn" id="sidebar-close"><i class="fas fa-times"></i></button>
    </div>

    <!-- Main Links -->
    <div class="v24-side-section">
        <div class="v24-side-title">Menu</div>
        <a href="<?php echo home_url(); ?>" class="v24-side-link"><i class="fas fa-home"></i> Home</a>
        <?php if ( is_user_logged_in() ) : ?>
            <a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" class="v24-side-link"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="<?php echo esc_url( home_url( '/dashboard?tab=profile' ) ); ?>" class="v24-side-link"><i class="fas fa-user"></i> Profile</a>
        <?php else: ?>
            <a href="<?php echo esc_url( home_url( '/login' ) ); ?>" class="v24-side-link"><i class="fas fa-sign-in-alt"></i> Login</a>
            <a href="<?php echo esc_url( home_url( '/signup' ) ); ?>" class="v24-side-link active"><i class="fas fa-rocket"></i> Get Started</a>
        <?php endif; ?>
    </div>

    <!-- DYNAMIC PLANS SECTION -->
    <div class="v24-side-section">
        <div class="v24-side-title">Our Plans</div>
        <?php 
            // Fetch Plans Dynamically
            global $wpdb;
            $table_plans = $wpdb->prefix . 'tv_plans';
            $side_plans = [];
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_plans'") == $table_plans) {
                $side_plans = $wpdb->get_results("SELECT id, name, price, duration_days FROM $table_plans ORDER BY price ASC LIMIT 5");
            }
            
            // Base URL for Premium Plans Page
            $plans_url = add_query_arg('tv_flow', 'subscription_plans', home_url('/'));
        ?>

        <?php if (!empty($side_plans)) : foreach ($side_plans as $p) : ?>
            <a href="<?php echo esc_url($plans_url); ?>" class="v24-menu-plan">
                <div>
                    <div class="v24-mp-name"><?php echo esc_html($p->name); ?></div>
                    <div class="v24-mp-price">$<?php echo esc_html($p->price); ?> / <?php echo $p->duration_days; ?>d</div>
                </div>
                <div class="v24-mp-arrow"><i class="fas fa-arrow-right"></i></div>
            </a>
        <?php endforeach; else: ?>
            <div style="font-size:13px; color:#64748b;">No plans available.</div>
        <?php endif; ?>
        
        <a href="<?php echo esc_url($plans_url); ?>" style="display:block; text-align:center; font-size:12px; color:#94a3b8; margin-top:10px; text-decoration:none;">View all plans &rarr;</a>
    </div>
</div>
