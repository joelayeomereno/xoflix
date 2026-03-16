<?php
/* Template Name: Manager Login (WP Style) */
if ( is_user_logged_in() && current_user_can('manage_options') ) {
    wp_redirect( home_url( '/manager' ) );
    exit;
}

// Handle Login POST
$error_msg = '';
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manager_login_action']) ) {
    $creds = array(
        'user_login'    => sanitize_text_field($_POST['log']),
        'user_password' => $_POST['pwd'],
        'remember'      => true
    );

    $user = wp_signon( $creds, is_ssl() );

    if ( is_wp_error( $user ) ) {
        $error_msg = $user->get_error_message();
    } else {
        if ( user_can( $user, 'manage_options' ) ) {
            wp_redirect( home_url( '/manager' ) );
            exit;
        } else {
            wp_logout();
            $error_msg = "Access Denied. Administrators only.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width" />
    <title>Log In &lsaquo; <?php bloginfo('name'); ?> &#8212; WordPress</title>
    <meta name='robots' content='max-image-preview:large, noindex, noarchive' />
    
    <!-- Load WP Login Styles -->
    <link rel='stylesheet' id='dashicons-css' href='<?php echo includes_url('css/dashicons.min.css'); ?>' media='all' />
    <link rel='stylesheet' id='buttons-css' href='<?php echo includes_url('css/buttons.min.css'); ?>' media='all' />
    <link rel='stylesheet' id='forms-css' href='<?php echo admin_url('css/forms.min.css'); ?>' media='all' />
    <link rel='stylesheet' id='l10n-css' href='<?php echo admin_url('css/l10n.min.css'); ?>' media='all' />
    <link rel='stylesheet' id='login-css' href='<?php echo admin_url('css/login.min.css'); ?>' media='all' />
</head>
<body class="login no-js login-action-login wp-core-ui  locale-en-us">
    <div id="login">
        <h1><a href="<?php echo home_url(); ?>"><?php bloginfo('name'); ?></a></h1>
        
        <?php if($error_msg): ?>
            <div id="login_error">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form name="loginform" id="loginform" method="post">
            <input type="hidden" name="manager_login_action" value="1">
            <p>
                <label for="user_login">Username or Email Address</label>
                <input type="text" name="log" id="user_login" class="input" value="" size="20" autocapitalize="off" autocomplete="username" required="required" />
            </p>

            <div class="user-pass-wrap">
                <label for="user_pass">Password</label>
                <div class="wp-pwd">
                    <input type="password" name="pwd" id="user_pass" class="input password-input" value="" size="20" autocomplete="current-password" spellcheck="false" required="required" />
                    <button type="button" class="button button-secondary wp-hide-pw hide-if-no-js" data-toggle="0" aria-label="Show password">
                        <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
            
            <p class="forgetmenot">
                <input name="rememberme" type="checkbox" id="rememberme" value="forever" checked /> 
                <label for="rememberme">Remember Me</label>
            </p>
            <p class="submit">
                <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Log In" />
            </p>
        </form>

        <p id="nav">
            <a href="<?php echo home_url('/forgot-password'); ?>">Lost your password?</a>
        </p>
        <p id="backtoblog">
            <a href="<?php echo home_url('/'); ?>">&larr; Go to <?php bloginfo('name'); ?></a>
        </p>
    </div>

    <script type="text/javascript">
    /* <![CDATA[ */
    function wp_attempt_focus(){
        setTimeout( function(){ try{ 
            d = document.getElementById('user_login'); 
            d.focus(); 
            d.select(); 
        } catch(e){} }, 200 );
    }

    if(typeof wpOnload==='function')wpOnload();
    
    // Password toggle
    document.querySelector('.wp-hide-pw').addEventListener('click', function(e){
        var inp = document.getElementById('user_pass');
        var btn = this;
        var icon = btn.querySelector('.dashicons');
        if (inp.type === 'password') {
            inp.type = 'text';
            icon.classList.remove('dashicons-visibility');
            icon.classList.add('dashicons-hidden');
        } else {
            inp.type = 'password';
            icon.classList.remove('dashicons-hidden');
            icon.classList.add('dashicons-visibility');
        }
    });

    wp_attempt_focus();
    /* ]]> */
    </script>
</body>
</html>