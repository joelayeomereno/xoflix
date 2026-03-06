<?php
/* Template Name: v18 Forgot Aurora */
get_header(); 
?>

<style>
    /* --- AUTH INPUT UPGRADES (Scoped) --- */
    
    /* 1. Base Input Styling - High Contrast */
    .v18-input {
        color: #ffffff !important; /* Force white text */
        background: rgba(255, 255, 255, 0.05) !important; /* Subtle dark glass */
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        padding-left: 55px !important; /* Space for icon */
        padding-right: 16px !important;
        font-weight: 500 !important;
        letter-spacing: 0.5px !important;
    }

    /* 2. Focus State - Premium Glow */
    .v18-input:focus {
        border-color: #6366f1 !important;
        background: rgba(255, 255, 255, 0.1) !important;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2) !important;
    }

    /* 3. Icon Positioning - Never Overlap */
    .v18-icon {
        left: 20px !important; /* Perfect left spacing */
        color: #94a3b8 !important; /* Muted icon color */
        z-index: 10;
        pointer-events: none;
    }
    
    /* Active Icon Color */
    .v18-input:focus ~ .v18-icon {
        color: #6366f1 !important;
    }

    /* 4. Floating Label Alignment */
    .v18-label {
        left: 55px !important; /* Align with text start */
        color: #64748b !important;
    }

    /* Label Float Animation Positions */
    .v18-input:focus ~ .v18-label,
    .v18-input:not(:placeholder-shown) ~ .v18-label {
        top: -12px !important;
        left: 0px !important;
        font-size: 0.85rem !important;
        color: #cbd5e1 !important;
        font-weight: 600 !important;
    }

    /* 5. Browser Autofill Fix */
    input:-webkit-autofill,
    input:-webkit-autofill:hover, 
    input:-webkit-autofill:focus, 
    input:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 30px #0f172a inset !important;
        -webkit-text-fill-color: #ffffff !important;
        transition: background-color 5000s ease-in-out 0s;
    }
</style>

<div class="v18-auth-container iptv-v18-engine">
    <div class="v18-aurora-bg">
        <div class="v18-aurora-blob blob-1"></div>
        <div class="v18-aurora-blob blob-2"></div>
    </div>
    
    <div class="v18-card">
        <div style="text-align:center; margin-bottom:32px;">
            <div style="width:48px; height:48px; background:rgba(99,102,241,0.1); border-radius:12px; display:inline-flex; align-items:center; justify-content:center; color:var(--v18-primary); font-size:1.2rem; margin-bottom:20px; box-shadow:0 0 15px rgba(99,102,241,0.2);">
                <i class="fas fa-key"></i>
            </div>
            <h1 style="font-size:2rem; margin:0 0 8px; color:white; font-weight:800;">Reset Password</h1>
            <p style="color:var(--v18-text-muted); margin:0; font-size:1rem;">Enter your email to receive a secure link.</p>
        </div>

        <?php if ( isset( $_GET['error'] ) ) : ?>
            <?php 
                $err_code = $_GET['error'];
                $msg = 'An error occurred. Please try again.';
                if ($err_code === 'invalid_user') $msg = 'No account found with that email.';
                elseif ($err_code === 'empty') $msg = 'Please enter your email address.';
                elseif ($err_code === 'email_failed') $msg = 'Could not send email. Contact support.';
                else $msg = esc_html(urldecode($err_code));
            ?>
            <div style="background:rgba(244,63,94,0.1); border:1px solid rgba(244,63,94,0.2); padding:12px 16px; border-radius:12px; margin-bottom:24px; color:#fda4af; font-size:0.9rem; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-circle-exclamation"></i>
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form action="" method="post">
            <?php wp_nonce_field( 'streamos_forgot_nonce' ); ?>
            <input type="hidden" name="streamos_action" value="forgot_password">
            
            <div class="v18-input-group">
                <input type="email" name="user_login" class="v18-input" placeholder=" " required autofocus />
                <label class="v18-label">Email Address</label>
                <i class="fas fa-envelope v18-icon"></i>
            </div>

            <button type="submit" class="v18-btn">
                Send Reset Link <i class="fas fa-paper-plane" style="margin-left:8px; font-size:0.9em;"></i>
            </button>
        </form>

        <div style="text-align:center; margin-top:32px;">
            <a href="<?php echo esc_url( home_url( '/login' ) ); ?>" class="v18-link" style="display:inline-flex; align-items:center; gap:6px;">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>
</div>

<?php get_footer(); ?>