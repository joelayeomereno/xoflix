<?php
/* Template Name: v18 Login Aurora */
get_header(); 
?>

<style>
    /* --- LOGIN INPUT UPGRADES (Scoped Fix) --- */
    
    /* 1. Base Input Styling - High Contrast */
    .v18-input {
        color: #ffffff !important; /* Force white text */
        background: rgba(255, 255, 255, 0.05) !important; /* Subtle dark glass */
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        padding-left: 55px !important; /* Space for icon */
        padding-right: 45px !important; /* Space for toggle/eye */
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

    /* 6. Eye Icon Specifics */
    .pw-toggle {
        transition: color 0.2s;
    }
    .pw-toggle:hover {
        color: #ffffff !important;
    }
</style>

<div class="v18-auth-container iptv-v18-engine">
    <!-- Aurora Background -->
    <div class="v18-aurora-bg">
        <div class="v18-aurora-blob blob-1"></div>
        <div class="v18-aurora-blob blob-2"></div>
    </div>
    
    <div class="v18-card">
        <!-- Logo -->
        <a href="<?php echo esc_url( home_url() ); ?>" style="text-decoration:none; display:flex; align-items:center; gap:12px; margin-bottom:32px;">
            <div style="width:36px; height:36px; background:var(--v18-primary); border-radius:10px; display:flex; align-items:center; justify-content:center; box-shadow:0 0 20px rgba(99,102,241,0.5);">
                <i class="fas fa-play" style="color:white; font-size:0.9rem; margin-left:2px;"></i>
            </div>
            <span style="font-family:'Outfit'; font-weight:700; font-size:1.5rem; color:white;">XOFLIX TV</span>
        </a>

        <div style="margin-bottom:32px;">
            <h1 style="font-size:2rem; margin:0 0 8px; background:var(--v18-grad-text); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">Welcome Back</h1>
            <p style="color:var(--v18-text-muted); margin:0; font-size:1rem;">Enter your credentials to access the portal.</p>
        </div>

        <?php if ( isset( $_GET['auth_error'] ) ) : ?>
            <div style="background:rgba(244,63,94,0.1); border:1px solid rgba(244,63,94,0.2); padding:12px 16px; border-radius:12px; margin-bottom:24px; color:#fda4af; font-size:0.9rem; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-circle-exclamation"></i> 
                <?php echo esc_html( urldecode( $_GET['auth_error'] ) ); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo esc_url( home_url( '/login' ) ); ?>" method="post">
            <?php wp_nonce_field( 'streamos_login_nonce' ); ?>
            <input type="hidden" name="streamos_action" value="login">
            
            <!-- EMAIL / USERNAME -->
            <div class="v18-input-group">
                <input type="text" name="log" class="v18-input" placeholder=" " required autofocus />
                <label class="v18-label">Email or Username</label>
                <i class="fas fa-user v18-icon"></i>
            </div>

            <!-- PASSWORD -->
            <div class="v18-input-group">
                <div style="position:relative;">
                    <input type="password" name="pwd" class="v18-input" placeholder=" " required />
                    <label class="v18-label">Password</label>
                    <i class="fas fa-lock v18-icon"></i>
                    <!-- Eye Toggle (Fixed Logic via JS below) -->
                    <i class="fas fa-eye pw-toggle" role="button" aria-label="Show password" style="position:absolute; right:16px; top:50%; transform:translateY(-50%); color:var(--v18-text-muted); cursor:pointer; z-index:20; padding: 10px;"></i>
                </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; color:var(--v18-text-muted); font-size:0.9rem;">
                    <input type="checkbox" name="rememberme" style="accent-color:var(--v18-primary);"> Remember me
                </label>
                <a href="<?php echo esc_url( home_url( '/forgot-password' ) ); ?>" class="v18-link">Forgot Password?</a>
            </div>

            <button type="submit" class="v18-btn">
                Log In <i class="fas fa-arrow-right" style="margin-left:8px; font-size:0.8rem;"></i>
            </button>
        </form>

        <div class="v18-divider"><span>OR CONTINUE WITH</span></div>

        <button class="v18-social-btn">
            <i class="fab fa-google"></i> Google Account
        </button>

        <p style="text-align:center; margin-top:32px; color:var(--v18-text-muted);">
            Don't have an account? <a href="<?php echo esc_url( home_url( '/signup' ) ); ?>" class="v18-link" style="color:var(--v18-primary); font-weight:600;">Sign Up</a>
        </p>
    </div>
</div>

<script>
    // --- ROBUST PASSWORD TOGGLE FIX ---
    document.addEventListener('DOMContentLoaded', function() {
        const toggles = document.querySelectorAll('.pw-toggle');
        toggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Find input relative to this icon (safest method)
                const wrapper = this.closest('div');
                const input = wrapper.querySelector('input');
                
                if (!input) return;

                if (input.type === 'password') {
                    input.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                    this.setAttribute('aria-label', 'Hide password');
                } else {
                    input.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                    this.setAttribute('aria-label', 'Show password');
                }
            });
        });
    });
</script>

<?php get_footer(); ?>
