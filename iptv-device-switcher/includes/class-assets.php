<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class StreamOS_Assets {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 999 );
        add_action( 'wp_head', array( $this, 'inject_resource_hints' ), 0 ); 
        add_action( 'wp_head', array( $this, 'inject_theme_killer' ), 1 );
        add_action( 'wp_head', array( $this, 'inject_inline_styles' ), 999 );
        
        // [NEW] Sandbox UI Injection
        add_action( 'wp_footer', array( $this, 'inject_sandbox_ui' ), 9999 );
        add_action( 'wp_footer', array( $this, 'inject_auth_scripts' ), 20 );
        
        add_filter( 'body_class', array( $this, 'add_body_classes' ) );
        add_filter( 'script_loader_tag', array( $this, 'defer_scripts' ), 10, 2 );
    }

    private function is_plugin_page() {
        if ( class_exists( 'IPTV_Device_Switcher_Includes_Class_Page_Ownership' ) ) {
            // [MODIFIED] Exclude 'manager' route from standard frontend styling
            if ( get_query_var( 'streamos_route' ) === 'manager' || get_query_var( 'streamos_route' ) === 'manager_login' ) {
                return false;
            }
            return IPTV_Device_Switcher_Includes_Class_Page_Ownership::is_streamos_owned_request();
        }
        $r = get_query_var( 'streamos_route' );
        $flow = get_query_var( 'tv_flow' );
        return ( is_front_page() || is_home() || ! empty( $r ) || ! empty( $flow ) );
    }


    /**
     * Asset versioning:
     * - Remote assets: stable version (avoid cache-busting on every request).
     * - Local assets: filemtime (fast refresh on deploy, still cacheable).
     */
    private function get_asset_version( $relative_path = '' ) {
        // Default to plugin version if defined.
        $base = defined( 'STREAMOS_VERSION' ) ? STREAMOS_VERSION : '1.0.0';

        if ( empty( $relative_path ) ) {
            return $base;
        }

        // Use filemtime when we can resolve a local file. This keeps browsers caching but updates when file changes.
        if ( defined( 'STREAMOS_PATH' ) ) {
            $full = trailingslashit( STREAMOS_PATH ) . ltrim( $relative_path, '/' );
            if ( file_exists( $full ) ) {
                $mtime = @filemtime( $full );
                if ( $mtime ) {
                    return $base . '.' . $mtime;
                }
            }
        }

        return $base;
    }

    /**
     * [OPTIMIZATION] Resource Hints
     * Preconnects to critical CDNs to shave off 100-300ms of latency.
     */
    public function inject_resource_hints() {
        if ( $this->is_plugin_page() ) {
            echo '<link rel="preconnect" href="https://unpkg.com" crossorigin>';
            echo '<link rel="preconnect" href="https://cdn.tailwindcss.com" crossorigin>';
            echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
            echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
            echo '<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>';
            echo '<link rel="preconnect" href="https://images.unsplash.com">';
        }
    }

    public function inject_theme_killer() {
        // [MANAGER ISOLATION] Aggressively hide theme elements if they leak on manager routes
        $route = get_query_var('streamos_route');
        if ( $route === 'manager' || $route === 'manager_login' ) {
            echo '<style>
                header, footer, #masthead, #colophon, .site-header, .site-footer, .elementor-location-header, .elementor-location-footer { display: none !important; }
                html { margin-top: 0 !important; padding-top: 0 !important; }
            </style>';
            return;
        }

        // [FIX] Only apply dark theme killer if NOT on a flow page (Flow pages have their own styling)
        $flow = get_query_var( 'tv_flow' );
        if ( $this->is_plugin_page() && empty($flow) ) {
            // Force dark background immediately to prevent white flash
            echo '<style>body.streamos-app header,body.streamos-app #masthead,body.streamos-app .site-header,body.streamos-app #header,body.streamos-app #wpadminbar,body.streamos-app footer:not(.custom-footer),body.streamos-app #footer,body.streamos-app .site-footer{display:none!important}html{margin-top:0!important}body.streamos-app{margin:0!important;padding:0!important;background:#030712!important;}</style>';
        } elseif ( !empty($flow) ) {
            // [NUCLEAR RESET] For flow pages, use a stronger reset but allow custom bg
            // The trait-tv-public-flow.php handles the specific dequeuing, but this adds extra CSS safety
            echo '<style>body.streamos-app header,body.streamos-app #masthead,body.streamos-app .site-header,body.streamos-app #header,body.streamos-app #wpadminbar,body.streamos-app footer:not(.custom-footer),body.streamos-app #footer,body.streamos-app .site-footer, body.streamos-app .elementor-location-header, body.streamos-app .elementor-location-footer{display:none!important}html{margin-top:0!important}</style>';
        }
    }
    
    public function enqueue_assets() {
        if ( $this->is_plugin_page() ) {

            // IMPORTANT: do NOT use time() for versioning in production.
            // Using file-based versions keeps caching effective and pages loading fast.
            $remote_ver = $this->get_asset_version();
            $css_ver    = $this->get_asset_version( 'assets/style.css' );
            $js_ver     = $this->get_asset_version( 'assets/script.js' );

            // Remote assets (stable version)
            wp_enqueue_style( 'fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;600;700;800&display=swap', array(), $remote_ver );
            wp_enqueue_style( 'fa', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), $remote_ver );

            // Local assets
            // [FIX] Ensure base styles don't conflict with TV Flow
            $flow = get_query_var( 'tv_flow' );
            if ( empty($flow) ) {
                wp_enqueue_style( 'streamos-main', STREAMOS_URL . 'assets/style.css', array(), $css_ver );
                wp_enqueue_script( 'streamos-js', STREAMOS_URL . 'assets/script.js', array( 'jquery' ), $js_ver, true );
            }

            wp_enqueue_script( 'jquery' );
        }
    }

    /**
     * OPTIMIZATION: Defer Scripts
     * Adds 'defer' attribute to non-critical scripts to unblock rendering.
     */
    public function defer_scripts( $tag, $handle ) {
        if ( ! $this->is_plugin_page() ) return $tag;

        // List of scripts to defer
        $defer_handles = array( 'streamos-js', 'jquery-migrate' );

        if ( in_array( $handle, $defer_handles ) ) {
            return str_replace( ' src', ' defer="defer" src', $tag );
        }

        return $tag;
    }

    public function add_body_classes( $classes ) { 
        if ( $this->is_plugin_page() ) { 
            // Stable root scoping class to prevent cross-page CSS leaks.
            $classes[] = 'streamos-app';

            if ( class_exists( 'IPTV_Device_Switcher_Includes_Class_Page_Ownership' ) ) {
                $route = IPTV_Device_Switcher_Includes_Class_Page_Ownership::current_route();
                if ( $route !== '' ) {
                    $classes[] = 'streamos-route-' . sanitize_html_class( $route );
                }
            }

            // Only add global V18 engine class if NOT a flow page to avoid style leaks
            $flow = get_query_var( 'tv_flow' );
            if ( empty($flow) ) {
                $classes[] = 'iptv-v18-engine'; 
                if ( get_query_var( 'streamos_route' ) === 'dashboard' ) {
                    $classes[] = 'streamos-dashboard-v15';
                }
            }
        } 
        return $classes; 
    }

    // [NEW] Shadow Mode Exit UI
    public function inject_sandbox_ui() {
        if ( defined('TV_SHADOW_MODE') && TV_SHADOW_MODE ) {
            $exit_url = home_url('/?tv_end_impersonation=1');
            echo '<div style="position:fixed; bottom:20px; right:20px; z-index:999999; display:flex; align-items:center; gap:10px; background:#1e293b; color:white; padding:12px 20px; border-radius:100px; box-shadow:0 10px 30px rgba(0,0,0,0.5); font-family:sans-serif; border:1px solid rgba(255,255,255,0.1); animation:fadeInUp 0.5s ease-out;">
                <div style="width:10px; height:10px; background:#ef4444; border-radius:50%; box-shadow:0 0 0 4px rgba(239, 68, 68, 0.2); animation:pulse 2s infinite;"></div>
                <div style="font-size:13px; font-weight:600;">Viewing as User</div>
                <a href="'.esc_url($exit_url).'" style="background:white; color:#0f172a; text-decoration:none; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700; margin-left:8px;">Exit View</a>
            </div>
            <style>
                @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
                @keyframes fadeInUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
            </style>';
        }
    }

    public function inject_inline_styles() {
        // [FIX] Don't inject V18 global styles on flow pages to avoid conflict with Lockdown light theme
        if ( $this->is_plugin_page() && !get_query_var('tv_flow') ) {
            ?>
            <style>
                :root { 
                    /* V18 Aurora/Lumina Palette */
                    --v18-bg: #030712;
                    --v18-surface: rgba(17, 24, 39, 0.7);
                    --v18-surface-highlight: rgba(255, 255, 255, 0.05);
                    --v18-border: rgba(255, 255, 255, 0.08);
                    --v18-primary: #6366f1;
                    --v18-accent: #8b5cf6;
                    --v18-grad-btn: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                    --v18-grad-text: linear-gradient(to right, #fff, #cbd5e1);
                    --v18-text: #f8fafc;
                    --v18-text-muted: #94a3b8;
                    --v18-error: #f43f5e;
                    --v18-success: #10b981;

                    /* V15 Legacy Compat (For Dashboard) */
                    --v15-bg: #fff;
                    --v15-sidebar: #fff;
                    --v15-text-main: #0f172a;
                    --v15-text-sec: #64748b;
                    --v15-text-ter: #94a3b8;
                    --v15-border: #e2e8f0;
                    --v15-surface: #ffffff;
                    --v15-surface-hover: #f8fafc;
                    --v15-accent: #6366f1;
                    --v15-accent-light: #e0e7ff;
                    --v15-accent-hover: #4f46e5;
                    --v15-radius: 16px;
                    --v15-radius-sm: 8px;
                    --v15-shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
                }

                /* Dark Mode V15 Override */
                [data-theme="dark"] {
                    --v15-bg: #09090b; --v15-sidebar: #09090b;
                    --v15-text-main: #f8fafc; --v15-border: #27272a;
                    --v15-surface: #121214; --v15-surface-hover: #1c1c1f;
                }

                /* Base Reset */
                body.streamos-app.iptv-v18-engine { 
                    font-family: 'Inter', sans-serif !important; 
                    background-color: var(--v18-bg) !important;
                    color: var(--v18-text) !important; 
                    margin: 0; padding: 0;
                }
                h1, h2, h3, h4, h5, h6 { font-family: 'Outfit', sans-serif; letter-spacing: -0.01em; }

                /* Common V18 Components */
                .v18-auth-container { min-height: 100vh; display: grid; place-items: center; position: relative; overflow: hidden; z-index: 1; }
                .v18-aurora-bg { position: fixed; inset: 0; z-index: -1; background: var(--v18-bg); }
                .v18-aurora-blob { position: absolute; width: 50vw; height: 50vw; background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%); border-radius: 50%; animation: float 10s infinite alternate ease-in-out; }
                .blob-1 { top: -10%; left: -10%; animation-duration: 12s; } .blob-2 { bottom: -10%; right: -10%; animation-duration: 15s; }
                @keyframes float { 0% { transform: translate(0,0); } 100% { transform: translate(20px, 40px); } }

                .v18-card { width: 100%; max-width: 440px; background: var(--v18-surface); backdrop-filter: blur(20px); border: 1px solid var(--v18-border); border-radius: 24px; padding: 48px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); position: relative; overflow: hidden; }
                .v18-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); }

                /* Inputs */
                .v18-input-group { position: relative; margin-bottom: 24px; }
                .v18-input { width: 100%; height: 52px; background: rgba(0, 0, 0, 0.2); border: 1px solid var(--v18-border); border-radius: 12px; padding: 0 16px 0 48px; color: white; font-size: 1rem; transition: all 0.2s ease; outline: none; }
                .v18-input:focus { background: rgba(0, 0, 0, 0.4); border-color: var(--v18-primary); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
                .v18-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--v18-text-muted); pointer-events: none; transition: 0.2s; }
                .v18-input:focus ~ .v18-icon { color: var(--v18-primary); }
                .v18-label { position: absolute; left: 48px; top: 50%; transform: translateY(-50%); color: var(--v18-text-muted); pointer-events: none; font-size: 0.95rem; transition: 0.2s ease; }
                .v18-input:focus ~ .v18-label, .v18-input:not(:placeholder-shown) ~ .v18-label { top: -10px; left: 0px; font-size: 0.8rem; color: var(--v18-text-muted); }
                
                /* Flag Specifics */
                .v18-selected-flag-img { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 24px; height: auto; border-radius: 4px; z-index: 5; }

                /* Buttons */
                .v18-btn { width: 100%; height: 52px; background: var(--v18-grad-btn); color: white; border: none; border-radius: 12px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.3s; position: relative; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4); display: flex; align-items: center; justify-content: center; text-decoration: none;}
                .v18-btn:hover { transform: translateY(-2px); box-shadow: 0 20px 30px -10px rgba(99, 102, 241, 0.5); color:white; }
                .v18-btn.disabled { opacity: 0.6; cursor: not-allowed; filter: grayscale(1); }
                
                .v18-link { color: var(--v18-text-muted); text-decoration: none; transition: 0.2s; font-size: 0.9rem; }
                .v18-link:hover { color: white; }
                .v18-divider { display: flex; align-items: center; margin: 32px 0; color: var(--v18-text-muted); font-size: 0.85rem; }
                .v18-divider::before, .v18-divider::after { content: ''; flex: 1; height: 1px; background: var(--v18-border); }
                .v18-divider span { padding: 0 12px; }
                .v18-social-btn { width: 100%; height: 52px; background: rgba(255,255,255,0.03); border: 1px solid var(--v18-border); border-radius: 12px; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: 0.2s; font-weight: 500; }
                .v18-social-btn:hover { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.2); }
                .v18-combobox-list { position: absolute; width: 100%; max-height: 200px; overflow-y: auto; background: #1e293b; border: 1px solid var(--v18-border); border-radius: 12px; z-index: 50; margin-top: 4px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
                .v18-option { padding: 12px 16px; cursor: pointer; display: flex; align-items: center; gap: 10px; transition:0.2s; }
                .v18-option:hover { background: rgba(255,255,255,0.05); }
            </style>
            <?php
        }
    }

    public function inject_auth_scripts() {
        if ( $this->is_plugin_page() && !get_query_var('tv_flow') ) {
             // ... Auth scripts ...
        }
    }
}