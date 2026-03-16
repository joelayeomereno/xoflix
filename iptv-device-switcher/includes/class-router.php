<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class StreamOS_Router {

    /**
     * Register rewrites without requiring an instance.
     * Used by activation hook to ensure rules exist before flushing.
     */
    public static function register_rewrites_static() {
        add_rewrite_rule( '^login/?$', 'index.php?streamos_route=login', 'top' );
        add_rewrite_rule( '^signup/?$', 'index.php?streamos_route=signup', 'top' );
        add_rewrite_rule( '^forgot-password/?$', 'index.php?streamos_route=forgot', 'top' );
        add_rewrite_rule( '^reset-password/?$', 'index.php?streamos_route=reset', 'top' ); // Added Reset Route
        add_rewrite_rule( '^dashboard/?$', 'index.php?streamos_route=dashboard', 'top' );
        
        // [NEW] Dedicated Admin Manager Routes
        add_rewrite_rule( '^manager/?$', 'index.php?streamos_route=manager', 'top' );
        add_rewrite_rule( '^manager-login/?$', 'index.php?streamos_route=manager_login', 'top' );
        add_rewrite_rule( '^m3u-parser/?$', 'index.php?streamos_route=m3u_parser', 'top' );
        add_rewrite_rule( '^iptv-guide/?$', 'index.php?streamos_route=iptv_guide', 'top' );
    }

    public function __construct() {
        // 1. Hook extremely early to prevent Caching Plugins from saving this page
        add_action( 'template_redirect', array( $this, 'prevent_caching_plugins' ), 1 );

        // 1b. Hard safety net: force-load our templates for owned routes.
        add_action( 'template_redirect', array( $this, 'maybe_force_route_template' ), 0 );
        
        // 2. Send HTTP Headers before any output
        add_action( 'send_headers', array( $this, 'send_no_cache_headers' ) );

        // 3. Template Loading (High Priority)
        add_filter( 'template_include', array( $this, 'load_device_template' ), PHP_INT_MAX );
        
        // 4. Rewrites
        add_action( 'init', array( $this, 'add_custom_rewrites' ) );
        add_filter( 'query_vars', array( $this, 'register_query_vars' ) );

        // 4b. Safety net: route detection without relying on flushed rewrite rules.
        add_action( 'parse_request', array( $this, 'maybe_infer_route_from_path' ), 0 );
        
        // 5. Diagnostic: Verify Router in Source
        add_action( 'wp_head', function() { echo "\n<!-- XOFLIX TV Router: LIVE & NO-CACHE -->\n"; }, 1 );
    }

    /**
     * Disable WP Caching Plugins (WP Rocket, Super Cache, W3TC, etc.)
     * for the Dynamic Homepage and Auth Routes.
     */
    public function prevent_caching_plugins() {
        if ( $this->is_dynamic_page() ) {
            if ( ! defined( 'DONOTCACHEPAGE' ) ) {
                define( 'DONOTCACHEPAGE', true );
            }
            if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
                define( 'DONOTCACHEOBJECT', true );
            }
            if ( ! defined( 'DONOTMINIFY' ) ) {
                define( 'DONOTMINIFY', true );
            }
        }
    }

    /**
     * Send Raw HTTP Headers to Browser/CDN
     */
    public function send_no_cache_headers() {
        if ( $this->is_dynamic_page() ) {
            if ( ! headers_sent() ) {
                header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
                header( 'Cache-Control: post-check=0, pre-check=0', false );
                header( 'Pragma: no-cache' );
                header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' ); // Date in the past
            }
        }
    }

    /**
     * Hard safety net: force-load our templates for owned routes.
     */
    public function maybe_force_route_template() {
        if ( is_admin() ) {
            return;
        }

        // Don't interfere with REST/AJAX.
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return;
        }
        
        // [LEGACY PRESERVATION] Do not intercept /player/ if it exists as a real page.
        if ( is_page('player') ) {
            return;
        }

        // If rewrites already matched, template_include will handle it.
        $route = get_query_var( 'streamos_route' );
        if ( ! empty( $route ) ) {
            return;
        }

        // Infer from request path.
        $uri  = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url( $uri, PHP_URL_PATH );
        $slug = is_string( $path ) ? trim( $path, '/' ) : '';
        // Support WordPress installed in a subdirectory by matching the last path segment.
        if ( $slug !== '' && strpos( $slug, '/' ) !== false ) {
            $parts = explode( '/', $slug );
            $slug  = (string) end( $parts );
        }
        if ( $slug === '' ) {
            return;
        }

        $map = array(
            'login'           => 'login',
            'signup'          => 'signup',
            'forgot-password' => 'forgot',
            'reset-password'  => 'reset',
            'dashboard'       => 'dashboard',
            'manager'         => 'manager',
            'manager-login'   => 'manager_login',
            'm3u-parser'      => 'm3u_parser',
            'iptv-guide'      => 'iptv_guide',
        );

        if ( ! isset( $map[ $slug ] ) ) {
            return;
        }

        $route = $map[ $slug ];
        $files = array(
            'login'         => 'auth-login.php',
            'signup'        => 'auth-signup.php',
            'forgot'        => 'auth-forgot.php',
            'reset'         => 'auth-reset.php',
            'dashboard'     => 'dashboard.php',
            'manager'       => 'manager.php',
            'manager_login' => 'manager-login.php',
            'm3u_parser'    => 'page-m3u-parser.php',
            'iptv_guide'    => 'page-iptv-guide.php',
        );
        if ( ! isset( $files[ $route ] ) ) {
            return;
        }

        $tpl = STREAMOS_PATH . 'templates/' . $files[ $route ];
        if ( ! file_exists( $tpl ) ) {
            return;
        }

        // Ensure this isn't treated as a 404.
        if ( function_exists( 'status_header' ) ) {
            status_header( 200 );
        }
        if ( isset( $GLOBALS['wp_query'] ) && is_object( $GLOBALS['wp_query'] ) ) {
            $GLOBALS['wp_query']->is_404 = false;
        }

        // Apply no-cache protection consistently.
        $this->prevent_caching_plugins();
        $this->send_no_cache_headers();

        // Render and stop WordPress from continuing into the theme.
        include $tpl;
        exit;
    }

    /**
     * Helper: Determine if current page is controlled by StreamOS
     */
    private function is_dynamic_page() {
        // Centralize ownership checks to prevent drift between router/asset/theme logic.
        if ( class_exists( 'IPTV_Device_Switcher_Includes_Class_Page_Ownership' ) ) {
            $owned = IPTV_Device_Switcher_Includes_Class_Page_Ownership::is_streamos_owned_request();
            // Routes are always dynamic (owned + not home handled below).
            if ( $owned && ! ( is_front_page() || is_home() ) ) {
                return true;
            }
        } else {
            // Custom Routes are always dynamic
            $route = get_query_var( 'streamos_route' );
            if ( ! empty( $route ) ) {
                return true;
            }
        }

        // Homepage behavior:
        // Historically the homepage was treated as fully dynamic (no-cache). For speed, we only force
        // no-cache for logged-in users by default. You can override via filter.
        if ( is_front_page() || is_home() ) {
            // Manual override: ?nocache=1
            if ( isset( $_GET['nocache'] ) && $_GET['nocache'] ) {
                return true;
            }

            /**
             * Filter: streamos_homepage_is_dynamic
             * Return true to force no-cache headers for the homepage.
             * Default: only logged-in sessions.
             */
            return (bool) apply_filters( 'streamos_homepage_is_dynamic', is_user_logged_in() );
        }

        return false;
    }

    public function add_custom_rewrites() {
        self::register_rewrites_static();
    }

    public function register_query_vars( $vars ) {
        $vars[] = 'streamos_route';
        return $vars;
    }

    /**
     * Safety net: infer streamos_route from the request path if rewrite rules aren't active yet.
     * This is additive and only sets the route when WordPress hasn't already.
     */
    public function maybe_infer_route_from_path( $wp ) {
        if ( ! is_object( $wp ) || ! isset( $wp->query_vars ) || ! is_array( $wp->query_vars ) ) {
            return;
        }

        // If rewrites already matched, don't interfere.
        if ( ! empty( $wp->query_vars['streamos_route'] ) ) {
            return;
        }

        // Prefer WP's parsed request (no query string) when available.
        $request = isset( $wp->request ) ? trim( (string) $wp->request, '/' ) : '';
        if ( $request === '' ) {
            // Fallback to REQUEST_URI if needed.
            $uri  = $_SERVER['REQUEST_URI'] ?? '';
            $path = parse_url( $uri, PHP_URL_PATH );
            $request = is_string( $path ) ? trim( $path, '/' ) : '';
        }

        if ( $request !== '' && strpos( $request, '/' ) !== false ) {
            $parts   = explode( '/', $request );
            $request = (string) end( $parts );
        }

        if ( $request === '' ) {
            return;
        }

        // Only infer routes we own.
        $map = array(
            'login'           => 'login',
            'signup'          => 'signup',
            'forgot-password' => 'forgot',
            'reset-password'  => 'reset',
            'dashboard'       => 'dashboard',
            'manager'         => 'manager',
            'manager-login'   => 'manager_login',
            'm3u-parser'      => 'm3u_parser',
            'iptv-guide'      => 'iptv_guide',
        );

        if ( isset( $map[ $request ] ) ) {
            $wp->query_vars['streamos_route'] = $map[ $request ];
        }
    }

    private function get_device_type() {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if ( preg_match( '/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $ua ) ) {
            return 'tablet';
        }
        if ( preg_match( '/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|ipod|iphone|android|iemobile)/i', $ua ) ) {
            return 'mobile';
        }
        return 'desktop';
    }

    public function load_device_template( $template ) {
        // A. Custom Plugin Routes
        $route = get_query_var( 'streamos_route' );
        if ( ! empty( $route ) ) {
            $files = [
                'login'         => 'auth-login.php',
                'signup'        => 'auth-signup.php',
                'forgot'        => 'auth-forgot.php',
                'reset'         => 'auth-reset.php',
                'dashboard'     => 'dashboard.php',
                'manager'       => 'manager.php',
                'manager_login' => 'manager-login.php',
                'm3u_parser'    => 'page-m3u-parser.php',
                'iptv_guide'    => 'page-iptv-guide.php',
            ];
            if ( isset( $files[$route] ) ) {
                return STREAMOS_PATH . 'templates/' . $files[$route];
            }
        }

        // B. Homepage Device Switching
        if ( is_front_page() || is_home() ) {
            $device = $this->get_device_type();
            $new_template = '';

            if ( $device === 'mobile' ) {
                $new_template = STREAMOS_PATH . 'templates/mobile-home.php';
            } elseif ( $device === 'tablet' ) {
                $new_template = STREAMOS_PATH . 'templates/tablet-home.php';
            } else {
                // FORCE DESKTOP (V19)
                $new_template = STREAMOS_PATH . 'templates/desktop-home.php';
            }

            // Safety Check
            if ( file_exists( $new_template ) ) {
                return $new_template;
            }
        }

        return $template;
    }
}