<?php
/* Template Name: v18 Reset Password (White) */
get_header(); 

// Grab query params
$key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
$login = isset($_GET['login']) ? sanitize_text_field($_GET['login']) : '';
$error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';

// Validate params immediately
$valid_link = ($key && $login);
$error_msg = '';

if ($error === 'mismatch') $error_msg = 'Passwords do not match.';
elseif ($error === 'expired') $error_msg = 'This link has expired. Please request a new one.';
elseif ($error === 'invalid_key') $error_msg = 'Invalid reset link. Please try again.';
elseif ($error === 'empty') $error_msg = 'Please enter a new password.';
elseif (!$valid_link && !$error) $error_msg = 'Invalid link parameters.';
?>

<style>
    /* --- V19 ULTRA-PREMIUM WHITE THEME --- */
    :root {
        --v19-bg: #f8fafc;
        --v19-surface: rgba(255, 255, 255, 0.95);
        --v19-border: rgba(226, 232, 240, 0.8);
        --v19-primary: #4f46e5;
        --v19-primary-hover: #4338ca;
        --v19-primary-glow: rgba(79, 70, 229, 0.15);
        --v19-text-main: #0f172a;
        --v19-text-sub: #64748b;
        --v19-input-bg: #f8fafc;
        --v19-success: #10b981;
        --v19-error: #ef4444;
        --v19-ease: cubic-bezier(0.16, 1, 0.3, 1);
    }

    body {
        background-color: var(--v19-bg) !important;
        color: var(--v19-text-main) !important;
        margin: 0;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .v19-auth-viewport {
        min-height: 100vh;
        min-height: 100dvh; 
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow-y: auto; 
        padding: 20px;
    }

    /* Aurora Background Effects */
    .v19-bg-layer {
        position: fixed; inset: 0; z-index: -1; pointer-events: none; overflow: hidden;
    }
    .v19-orb {
        position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.5;
        animation: v19Float 20s infinite alternate ease-in-out;
    }
    .orb-1 { width: 600px; height: 600px; background: #c7d2fe; top: -10%; left: -10%; animation-delay: 0s; }
    .orb-2 { width: 500px; height: 500px; background: #ddd6fe; bottom: -10%; right: -10%; animation-delay: -5s; }
    
    @keyframes v19Float { 0% { transform: translate(0,0); } 100% { transform: translate(30px, 50px); } }

    /* Glass Card */
    .v19-card {
        width: 100%;
        max-width: 440px;
        background: var(--v19-surface);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid #fff;
        border-radius: 32px;
        padding: 48px 40px;
        box-shadow: 
            0 25px 50px -12px rgba(0, 0, 0, 0.1), 
            0 0 0 1px rgba(255, 255, 255, 0.5) inset;
        transform: translateY(0);
        transition: transform 0.4s var(--v19-ease), box-shadow 0.4s var(--v19-ease);
        z-index: 10;
        margin: auto;
    }
    .v19-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 35px 60px -15px rgba(0, 0, 0, 0.15);
    }

    /* Header */
    .v19-icon-box {
        width: 72px; height: 72px;
        background: linear-gradient(135deg, #e0e7ff 0%, #f3e8ff 100%);
        color: var(--v19-primary);
        border-radius: 24px;
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem; margin: 0 auto 32px;
        box-shadow: 0 12px 24px -6px rgba(99, 102, 241, 0.25);
    }
    .v19-title { font-size: 1.85rem; font-weight: 800; color: var(--v19-text-main); margin: 0 0 10px; letter-spacing: -0.02em; text-align: center; }
    .v19-subtitle { font-size: 1rem; color: var(--v19-text-sub); text-align: center; line-height: 1.5; margin-bottom: 40px; }

    /* Modern Inputs */
    .v19-input-wrap { position: relative; margin-bottom: 24px; }
    
    .v19-input {
        width: 100%;
        height: 64px; /* INCREASED HEIGHT for comfort */
        /* FIXED: Increased left padding to 72px to clear the icon completely */
        padding: 0 56px 0 72px !important; 
        background: var(--v19-input-bg) !important;
        border: 2px solid transparent !important;
        border-radius: 18px;
        font-size: 1.05rem;
        font-weight: 600;
        color: var(--v19-text-main) !important;
        transition: all 0.2s var(--v19-ease);
        outline: none;
        letter-spacing: 0.5px;
        box-sizing: border-box; /* Ensure padding doesn't break layout */
    }
    .v19-input:focus {
        background: #fff !important;
        border-color: var(--v19-primary) !important;
        box-shadow: 0 0 0 4px var(--v19-primary-glow) !important;
    }
    
    .v19-icon-left {
        position: absolute; 
        left: 24px; 
        top: 50%; 
        transform: translateY(-50%);
        color: var(--v19-text-sub); 
        pointer-events: none; 
        transition: 0.2s; 
        font-size: 1.3rem;
        z-index: 5;
        width: 24px; /* Fixed width for alignment */
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .v19-input:focus ~ .v19-icon-left { color: var(--v19-primary); }

    .v19-label {
        position: absolute; 
        left: 72px; /* FIXED: Aligned with new input padding */
        top: 50%; transform: translateY(-50%);
        color: var(--v19-text-sub); pointer-events: none; transition: 0.2s; 
        font-size: 1rem; font-weight: 500;
        padding: 0 4px; z-index: 5;
    }
    .v19-input:focus ~ .v19-label,
    .v19-input:not(:placeholder-shown) ~ .v19-label {
        top: -12px; left: 14px; font-size: 0.8rem; font-weight: 700;
        color: var(--v19-primary); background: transparent; 
    }

    /* Eye Toggle */
    .v19-toggle {
        position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
        width: 48px; height: 48px;
        display: flex; align-items: center; justify-content: center;
        color: var(--v19-text-sub); cursor: pointer;
        border-radius: 14px; transition: 0.2s;
        z-index: 10;
    }
    .v19-toggle:hover { background: rgba(0,0,0,0.05); color: var(--v19-text-main); }

    /* Button */
    .v19-btn {
        width: 100%; height: 60px; margin-top: 16px;
        background: var(--v19-primary); color: white; border: none; border-radius: 18px;
        font-size: 1.1rem; font-weight: 700; cursor: pointer;
        transition: all 0.2s var(--v19-ease);
        box-shadow: 0 10px 25px -5px var(--v19-primary-glow);
        display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .v19-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 35px -5px var(--v19-primary-glow); background: var(--v19-primary-hover); }
    .v19-btn:disabled { opacity: 0.7; cursor: not-allowed; background: var(--v19-text-sub); box-shadow: none; filter: grayscale(1); }

    /* Strength & Match */
    .v19-meta {
        display: flex; justify-content: space-between; align-items: center;
        margin-top: 12px; min-height: 24px;
    }
    .match-tag { font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; gap: 6px; opacity: 0; transition: 0.2s; }
    .match-tag.show { opacity: 1; }
    .match-tag.valid { color: var(--v19-success); }
    .match-tag.invalid { color: var(--v19-error); }

    .strength-dots { display: flex; gap: 6px; }
    .dot { width: 32px; height: 5px; background: #e2e8f0; border-radius: 3px; transition: 0.3s; }
    
    .back-link {
        display: block; text-align: center; margin-top: 36px;
        color: var(--v19-text-sub); text-decoration: none; font-weight: 600; font-size: 0.95rem;
        transition: 0.2s;
    }
    .back-link:hover { color: var(--v19-primary); }

    /* Mobile Tweaks */
    @media (max-width: 480px) {
        .v19-card { padding: 40px 24px; border-radius: 28px; }
        .v19-title { font-size: 1.6rem; }
        .v19-input { height: 58px; font-size: 1rem; }
    }
</style>

<div class="v19-auth-viewport">
    <!-- Background Effects -->
    <div class="v19-bg-layer">
        <div class="v19-orb orb-1"></div>
        <div class="v19-orb orb-2"></div>
    </div>
    
    <div class="v19-card">
        <div class="v19-icon-box">
            <i class="fas fa-lock-open"></i>
        </div>
        <h1 class="v19-title">Secure Account</h1>
        <p class="v19-subtitle">Create a new, strong password to regain access to your dashboard.</p>

        <?php if ( $error_msg ) : ?>
            <div style="background:#fef2f2; color:#ef4444; padding:12px; border-radius:12px; font-size:0.9rem; font-weight:600; margin-bottom:24px; border:1px solid #fee2e2; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
            </div>
            <?php if(!$valid_link): ?>
                <div style="text-align:center;">
                    <a href="<?php echo esc_url( home_url( '/forgot-password' ) ); ?>" class="v19-btn">Request New Link</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($valid_link): ?>
        <form action="" method="post" autocomplete="off" id="reset-form">
            <input type="hidden" name="streamos_action" value="reset_password">
            <input type="hidden" name="key" value="<?php echo esc_attr($key); ?>">
            <input type="hidden" name="login" value="<?php echo esc_attr($login); ?>">
            
            <!-- New Password -->
            <div class="v19-input-wrap">
                <input type="password" name="pass1" id="pass1" class="v19-input" placeholder=" " required autofocus />
                <label class="v19-label">New Password</label>
                <i class="fas fa-key v19-icon-left"></i>
                <div class="v19-toggle" onclick="togglePw('pass1', this)"><i class="fas fa-eye"></i></div>
            </div>

            <!-- Strength Dots -->
            <div class="strength-dots" id="s-dots" style="margin-bottom:24px; margin-top:-14px;">
                <div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div>
            </div>

            <!-- Confirm Password -->
            <div class="v19-input-wrap">
                <input type="password" name="pass2" id="pass2" class="v19-input" placeholder=" " required />
                <label class="v19-label">Confirm Password</label>
                <i class="fas fa-check-circle v19-icon-left"></i>
                <div class="v19-toggle" onclick="togglePw('pass2', this)"><i class="fas fa-eye"></i></div>
            </div>
            
            <!-- Live Feedback -->
            <div class="v19-meta">
                <div id="match-tag" class="match-tag">
                    <i class="fas fa-circle"></i> <span>Check</span>
                </div>
                <span style="font-size:0.75rem; color:#94a3b8; font-weight:600;">Min 8 chars</span>
            </div>

            <button type="submit" class="v19-btn" id="submit-btn" disabled>
                Update Password <i class="fas fa-arrow-right"></i>
            </button>
        </form>
        <?php endif; ?>

        <a href="<?php echo esc_url( home_url( '/login' ) ); ?>" class="back-link">
            <i class="fas fa-arrow-left" style="margin-right:6px;"></i> Back to Login
        </a>
    </div>
</div>

<script>
    // 1. Password Visibility Toggle
    function togglePw(id, el) {
        const input = document.getElementById(id);
        const icon = el.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            el.style.color = 'var(--v19-primary)';
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            el.style.color = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const p1 = document.getElementById('pass1');
        const p2 = document.getElementById('pass2');
        const dots = document.querySelectorAll('.dot');
        const matchTag = document.getElementById('match-tag');
        const btn = document.getElementById('submit-btn');

        if(!p1 || !p2) return;

        function updateState() {
            const v1 = p1.value;
            const v2 = p2.value;

            // 1. Strength Logic
            let score = 0;
            if(v1.length > 5) score++;
            if(v1.length > 8) score++;
            if(/[A-Z]/.test(v1) && /[0-9]/.test(v1)) score++;
            if(/[^A-Za-z0-9]/.test(v1)) score++;

            // Update Dots
            const colors = ['#ef4444', '#f59e0b', '#10b981', '#059669'];
            dots.forEach((d, i) => {
                if(i < score) {
                    d.style.backgroundColor = colors[score-1] || colors[0];
                    d.style.width = '100%'; 
                } else {
                    d.style.backgroundColor = '#e2e8f0';
                }
            });

            // 2. Match Logic
            if (v2.length === 0) {
                matchTag.className = 'match-tag'; // hide
                btn.disabled = true;
                return;
            }

            matchTag.classList.add('show');
            
            if (v1 === v2 && v1.length >= 8) {
                matchTag.className = 'match-tag show valid';
                matchTag.innerHTML = '<i class="fas fa-check-circle"></i> <span>Passwords match</span>';
                p2.style.borderColor = 'var(--v19-success)';
                btn.disabled = false;
            } else {
                matchTag.className = 'match-tag show invalid';
                if(v1.length < 8) {
                    matchTag.innerHTML = '<i class="fas fa-exclamation-circle"></i> <span>Too short</span>';
                } else {
                    matchTag.innerHTML = '<i class="fas fa-times-circle"></i> <span>Passwords do not match</span>';
                }
                p2.style.borderColor = 'var(--v19-error)';
                btn.disabled = true;
            }
        }

        p1.addEventListener('input', updateState);
        p2.addEventListener('input', updateState);
    });
</script>

<?php get_footer(); ?>