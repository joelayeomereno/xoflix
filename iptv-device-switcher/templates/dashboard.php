<?php
/**
 * Dashboard.php
 * The master container for the frontend React application.
 */

if (!defined('ABSPATH')) { exit; }

// [SAFETY 1] Register Shutdown Function to catch Fatal Errors (White Screens)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        if (ob_get_level() > 0) ob_end_clean();
        echo '<div style="font-family:sans-serif;padding:30px;text-align:center;">';
        echo '<h2 style="color:#ef4444;">System Error</h2>';
        echo '<p>The dashboard encountered a critical error.</p>';
        if (current_user_can('manage_options')) {
            echo '<pre style="text-align:left;background:#f1f5f9;padding:15px;border-radius:8px;overflow:auto;">';
            echo esc_html($error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
            echo '</pre>';
        }
        echo '</div>';
    }
});

// [SAFETY 2] Force Login Check
if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/login?auth_error=Session%20Expired'));
    exit;
}

// Emergency Debugging (Only visible in source)
if (isset($_GET['debug_dashboard'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

global $wpdb, $current_user, $post, $tv_manager_public_instance;

// -- Email Verification Gate --------------------------------------------------
$verif_feature_on = (bool) get_option('streamos_require_email_verification', 0);
$current_uid      = get_current_user_id();

// Admins are never gated
$user_is_admin    = current_user_can('manage_options');

// Verified flag: '1' = done, '0' or missing = needs verification
$email_verified   = get_user_meta($current_uid, 'streamos_email_verified', true);
$needs_verif      = $verif_feature_on && !$user_is_admin && ($email_verified !== '1');

$current_user_email = wp_get_current_user()->user_email;

// Build nonce + config for JS
$verif_nonce    = wp_create_nonce('streamos_verif_nonce');
$ajax_url       = admin_url('admin-ajax.php');

// Check cooldown: if user has an active cooldown, compute remaining seconds
// We use a transient sev_cool_{uid} set to the unix expiry timestamp
$cool_exp       = (int) get_transient('sev_cool_exp_' . $current_uid);
$cool_remaining = max(0, $cool_exp - time());
// Fallback: just check if the simpler key exists
if ($cool_remaining === 0 && get_transient('sev_cool_' . $current_uid)) {
    $cool_remaining = 90; // safe fallback; JS will count down
}

// 3. Load Business Logic & Capture Script Output
ob_start();
try {
    $logic_path = plugin_dir_path(__FILE__) . 'dashboard-parts/logic.php';
    if (file_exists($logic_path)) {
        require $logic_path;
    } else {
        echo "<script>console.error('Logic file missing');</script>";
    }
} catch (Exception $e) {
    echo "<script>console.error('Logic Error: " . esc_js($e->getMessage()) . "');</script>";
}
$logic_output = ob_get_clean();

// 4. Fallback Safety
if (strpos($logic_output, 'const USER_DATA') === false) {
    $logic_output .= '<script>
        console.warn("Hydration Failed - Using Fallback");
        const USER_DATA = { name: "Guest", country: "US", tv_flow_urls: {} }; 
        const PLANS = []; 
        const ACTIVE_SUBSCRIPTIONS = []; 
        const INVOICES = []; 
        const SPORTS_RAW = []; 
        const PAYMENT_METHODS = []; 
        const IS_SANDBOX = false;
        window.USER_ALERTS = [{type:"error", message:"System error loading data. Please refresh."}];
    </script>';
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard - <?php bloginfo('name'); ?></title>
    
    <!-- Resource Hints -->
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel="preconnect" href="https://cdn.tailwindcss.com" crossorigin>
    
    <!-- React & Tailwind (Synchronous for stability) -->
    <script src="https://unpkg.com/react@18/umd/react.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js" crossorigin></script>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        slate: { 850: '#151f32', 900: '#0f172a' },
                        primary: { 500: '#3b82f6', 600: '#2563eb' }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.3s ease-out',
                        'slide-up': 'slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1)',
                        'slide-in': 'slideIn 0.3s ease-out'
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { transform: 'translateY(10px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } },
                        slideIn: { '0%': { transform: 'translateX(100%)' }, '100%': { transform: 'translateX(0)' } }
                    }
                }
            }
        }
    </script>
    
    <style>
        body { background-color: #f8fafc; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
        .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
        .cursor-wait { cursor: wait; }

        /* -- Verification Overlay ----------------------------------- */
        #verif-overlay {
            position: fixed; inset: 0; z-index: 99999;
            background: rgba(15,23,42,.85);
            backdrop-filter: blur(8px);
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        #verif-overlay.hidden { display: none; }

        .verif-card {
            background: #fff; border-radius: 24px;
            padding: 40px 36px 36px;
            width: 100%; max-width: 420px;
            box-shadow: 0 32px 64px -12px rgba(0,0,0,.4);
            animation: verifIn .4s cubic-bezier(.16,1,.3,1) both;
            position: relative;
        }
        @keyframes verifIn { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

        .verif-icon {
            width: 64px; height: 64px; border-radius: 20px;
            background: linear-gradient(135deg,#e0e7ff,#ede9fe);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; color: #4f46e5;
            margin: 0 auto 22px;
            box-shadow: 0 8px 20px -4px rgba(99,102,241,.28);
        }
        .verif-title {
            font-size: 1.45rem; font-weight: 800; letter-spacing: -.02em;
            text-align: center; margin: 0 0 10px; color: #0f172a;
        }
        .verif-sub {
            font-size: .9rem; color: #64748b; text-align: center;
            line-height: 1.6; margin: 0 0 28px;
        }
        .verif-email-chip {
            display: inline-block; background: #f1f5f9;
            border-radius: 8px; padding: 3px 10px;
            font-weight: 700; color: #4f46e5; font-size: .88rem;
            word-break: break-all;
        }

        /* Code input */
        .verif-code-wrap {
            display: flex; gap: 8px; justify-content: center;
            margin-bottom: 6px;
        }
        .verif-digit {
            width: 48px; height: 58px;
            border: 2px solid #e2e8f0; border-radius: 14px;
            font-size: 1.5rem; font-weight: 800;
            text-align: center; outline: none;
            transition: border-color .15s, box-shadow .15s;
            color: #0f172a; background: #f8fafc;
            -webkit-appearance: none;
        }
        .verif-digit:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79,70,229,.13);
            background: #fff;
        }
        .verif-digit.filled { border-color: #4f46e5; background: #f5f3ff; }
        .verif-digit.error  { border-color: #ef4444; background: #fef2f2; animation: verifShake .3s ease; }
        @keyframes verifShake { 0%,100%{transform:translateX(0)} 25%{transform:translateX(-4px)} 75%{transform:translateX(4px)} }

        /* Alert row */
        .verif-alert {
            font-size: .82rem; font-weight: 600; text-align: center;
            min-height: 20px; margin-bottom: 16px; padding: 0 4px;
        }
        .verif-alert.err  { color: #dc2626; }
        .verif-alert.ok   { color: #16a34a; }
        .verif-alert.info { color: #2563eb; }

        /* Verify button */
        .verif-btn {
            width: 100%; height: 52px; margin-top: 8px;
            background: #4f46e5; color: #fff; border: none; border-radius: 14px;
            font-size: .97rem; font-weight: 700; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: all .2s ease; box-shadow: 0 4px 14px -4px rgba(79,70,229,.42);
        }
        .verif-btn:hover:not(:disabled) { background: #4338ca; transform: translateY(-2px); }
        .verif-btn:disabled { background: #e2e8f0; color: #94a3b8; box-shadow: none; cursor: not-allowed; transform: none; }

        /* Secondary actions */
        .verif-actions {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 18px; gap: 8px;
        }
        .verif-link {
            font-size: .83rem; font-weight: 600; color: #4f46e5;
            background: none; border: none; cursor: pointer; padding: 0;
            text-decoration: none; transition: color .15s; white-space: nowrap;
        }
        .verif-link:hover { color: #4338ca; text-decoration: underline; }
        .verif-link:disabled { color: #94a3b8; cursor: not-allowed; text-decoration: none; }

        .verif-resend-timer {
            font-size: .82rem; color: #64748b; font-weight: 600;
        }

        /* Change email inline form */
        #verif-change-form {
            display: none; margin-top: 20px; padding-top: 18px;
            border-top: 1px solid #f1f5f9;
        }
        #verif-change-form.show { display: block; }
        .verif-change-input {
            width: 100%; height: 48px;
            border: 1.5px solid #e2e8f0; border-radius: 12px;
            padding: 0 14px; font-size: .93rem; font-weight: 500;
            outline: none; transition: border-color .15s; margin-bottom: 10px;
            background: #f8fafc; color: #0f172a;
        }
        .verif-change-input:focus { border-color: #4f46e5; background: #fff; box-shadow: 0 0 0 3px rgba(79,70,229,.10); }
        .verif-change-row { display: flex; gap: 8px; }
        .verif-change-btn {
            flex: 1; height: 42px; border: none; border-radius: 11px;
            font-size: .88rem; font-weight: 700; cursor: pointer; transition: .15s;
        }
        .verif-change-btn.primary { background: #4f46e5; color: #fff; }
        .verif-change-btn.primary:hover { background: #4338ca; }
        .verif-change-btn.secondary { background: #f1f5f9; color: #64748b; }
        .verif-change-btn.secondary:hover { background: #e2e8f0; }

        /* Spinner inside button */
        .verif-spin {
            width: 18px; height: 18px;
            border: 2.5px solid rgba(255,255,255,.3); border-top-color: #fff;
            border-radius: 50%; animation: spin .7s linear infinite; display: none;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 480px) {
            .verif-card { padding: 32px 22px 28px; border-radius: 20px; }
            .verif-digit { width: 40px; height: 52px; font-size: 1.3rem; }
        }
    </style>
</head>
<body>

    <?php if ($needs_verif): ?>
    <!-- -------------------------------------------------------
         EMAIL VERIFICATION OVERLAY
         Non-dismissible until verified. Dashboard renders
         underneath but is blurred & non-interactive.
    -------------------------------------------------------- -->
    <div id="verif-overlay">
        <div class="verif-card">
            <div class="verif-icon"><i class="fas fa-envelope-open-text"></i></div>
            <h1 class="verif-title">Verify your email</h1>
            <p class="verif-sub" id="verif-sub-text">
                We sent a 6-digit code to<br>
                <span class="verif-email-chip" id="verif-email-display"><?= esc_html($current_user_email) ?></span>
                <br><small style="font-size:.78rem; color:#94a3b8; display:block; margin-top:6px;">Check your inbox and spam/junk folder.</small>
            </p>

            <!-- 6 individual digit inputs -->
            <div class="verif-code-wrap" id="verif-code-wrap">
                <input class="verif-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" data-index="0">
                <input class="verif-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="1">
                <input class="verif-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="2">
                <input class="verif-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="3">
                <input class="verif-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="4">
                <input class="verif-digit" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="5">
            </div>

            <div class="verif-alert" id="verif-alert"></div>

            <button class="verif-btn" id="verif-submit-btn" disabled>
                <div class="verif-spin" id="verif-submit-spin"></div>
                <span id="verif-submit-label">Verify Email</span>
            </button>

            <div class="verif-actions">
                <button class="verif-link" id="verif-resend-btn">Resend code</button>
                <span class="verif-resend-timer" id="verif-timer" style="display:none;"></span>
                <button class="verif-link" id="verif-change-email-btn">Change email address</button>
            </div>

            <!-- Inline change-email form (hidden by default) -->
            <div id="verif-change-form">
                <input type="email" class="verif-change-input" id="verif-new-email"
                       placeholder="Enter new email address" inputmode="email" autocomplete="email">
                <div class="verif-change-row">
                    <button class="verif-change-btn secondary" id="verif-change-cancel">Cancel</button>
                    <button class="verif-change-btn primary" id="verif-change-confirm">Update &amp; Resend</button>
                </div>
                <div class="verif-alert info" id="verif-change-alert" style="margin-top:8px;"></div>
            </div>
        </div>
    </div>

    <script>
    /* --- Verification overlay controller --- */
    (function() {
        const AJAX  = <?= json_encode($ajax_url) ?>;
        const NONCE = <?= json_encode($verif_nonce) ?>;

        const overlay      = document.getElementById('verif-overlay');
        const digits       = Array.from(document.querySelectorAll('.verif-digit'));
        const submitBtn    = document.getElementById('verif-submit-btn');
        const submitSpin   = document.getElementById('verif-submit-spin');
        const submitLabel  = document.getElementById('verif-submit-label');
        const alertBox     = document.getElementById('verif-alert');
        const resendBtn    = document.getElementById('verif-resend-btn');
        const timerEl      = document.getElementById('verif-timer');
        const changeBtn    = document.getElementById('verif-change-email-btn');
        const changeForm   = document.getElementById('verif-change-form');
        const newEmailIn   = document.getElementById('verif-new-email');
        const changeCxlBtn = document.getElementById('verif-change-cancel');
        const changeConfBtn= document.getElementById('verif-change-confirm');
        const changeAlert  = document.getElementById('verif-change-alert');
        const emailDisplay = document.getElementById('verif-email-display');

        let cooldown   = <?= (int)$cool_remaining ?>;
        let timerInt   = null;

        /* -- Alert helper -- */
        function setAlert(msg, type='') {
            alertBox.textContent = msg;
            alertBox.className   = 'verif-alert' + (type ? ' '+type : '');
        }

        /* -- Cooldown timer -- */
        function startCooldown(seconds) {
            cooldown = seconds;
            resendBtn.disabled = true;
            timerEl.style.display = 'inline';
            clearInterval(timerInt);
            timerInt = setInterval(() => {
                cooldown--;
                timerEl.textContent = `Resend in ${cooldown}s`;
                if (cooldown <= 0) {
                    clearInterval(timerInt);
                    resendBtn.disabled = false;
                    timerEl.style.display = 'none';
                }
            }, 1000);
            timerEl.textContent = `Resend in ${cooldown}s`;
        }

        // Start cooldown if server says there's one active
        if (cooldown > 0) startCooldown(cooldown);

        /* -- Digit input logic -- */
        digits.forEach((d, i) => {
            d.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !d.value && i > 0) digits[i-1].focus();
            });
            d.addEventListener('input', e => {
                // Allow only digits
                d.value = d.value.replace(/\D/g, '').slice(-1);
                d.classList.toggle('filled', d.value !== '');
                d.classList.remove('error');
                if (d.value && i < digits.length - 1) digits[i+1].focus();
                checkComplete();
            });
            d.addEventListener('paste', e => {
                e.preventDefault();
                const pasted = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
                pasted.split('').forEach((ch,j) => {
                    if (digits[j]) { digits[j].value=ch; digits[j].classList.add('filled'); }
                });
                const last = Math.min(pasted.length, digits.length-1);
                digits[last].focus();
                checkComplete();
            });
        });

        function getCode() { return digits.map(d=>d.value).join(''); }
        function checkComplete() {
            const code = getCode();
            submitBtn.disabled = (code.length !== 6);
        }

        /* -- Verify code -- */
        submitBtn.addEventListener('click', async () => {
            const code = getCode();
            if (code.length !== 6) return;

            submitBtn.disabled   = true;
            submitSpin.style.display = 'block';
            submitLabel.style.opacity = '0';
            setAlert('');

            try {
                const fd = new FormData();
                fd.append('action','streamos_verif_check');
                fd.append('_nonce', NONCE);
                fd.append('code', code);

                const res  = await fetch(AJAX, {method:'POST', body:fd});
                const json = await res.json();

                if (json.success) {
                    // SUCCESS — dismiss overlay
                    setAlert('? Email verified! Loading dashboard…', 'ok');
                    setTimeout(() => {
                        overlay.classList.add('hidden');
                        // Optionally reload to refresh state
                        window.location.reload();
                    }, 800);
                } else {
                    // Error — shake digits
                    digits.forEach(d => {
                        d.classList.add('error');
                        setTimeout(() => d.classList.remove('error'), 500);
                    });
                    setAlert(json.data?.message || 'Incorrect code. Please try again.', 'err');
                    submitBtn.disabled   = false;
                    submitSpin.style.display  = 'none';
                    submitLabel.style.opacity = '1';
                    digits[0].focus();
                }
            } catch(e) {
                setAlert('Network error. Please try again.', 'err');
                submitBtn.disabled   = false;
                submitSpin.style.display  = 'none';
                submitLabel.style.opacity = '1';
            }
        });

        /* -- Resend code -- */
        resendBtn.addEventListener('click', async () => {
            resendBtn.disabled = true;
            setAlert('Sending…', 'info');

            try {
                const fd = new FormData();
                fd.append('action','streamos_verif_send');
                fd.append('_nonce', NONCE);
                const res  = await fetch(AJAX, {method:'POST', body:fd});
                const json = await res.json();

                if (json.success) {
                    setAlert('New code sent to your inbox.', 'info');
                    startCooldown(json.data?.cooldown || 90);
                } else {
                    const cd = json.data?.cooldown;
                    if (cd) {
                        startCooldown(cd);
                        setAlert('', '');
                    } else {
                        setAlert(json.data?.message || 'Could not send code. Try again.', 'err');
                        resendBtn.disabled = false;
                    }
                }
            } catch(e) {
                setAlert('Network error.', 'err');
                resendBtn.disabled = false;
            }
        });

        /* -- Change email -- */
        changeBtn.addEventListener('click', () => {
            changeForm.classList.add('show');
            changeBtn.style.display = 'none';
            newEmailIn.focus();
        });
        changeCxlBtn.addEventListener('click', () => {
            changeForm.classList.remove('show');
            changeBtn.style.display = '';
            changeAlert.textContent = '';
        });
        changeConfBtn.addEventListener('click', async () => {
            const newEmail = newEmailIn.value.trim();
            if (!newEmail || !newEmail.includes('@')) {
                changeAlert.textContent = 'Please enter a valid email address.';
                changeAlert.className   = 'verif-alert err';
                return;
            }

            changeConfBtn.disabled   = true;
            changeCxlBtn.disabled    = true;
            changeAlert.textContent  = 'Updating…';
            changeAlert.className    = 'verif-alert info';

            try {
                const fd = new FormData();
                fd.append('action','streamos_verif_email');
                fd.append('_nonce', NONCE);
                fd.append('email', newEmail);
                const res  = await fetch(AJAX, {method:'POST', body:fd});
                const json = await res.json();

                if (json.success) {
                    // Update displayed email
                    const displayEmail = json.data?.email || newEmail;
                    emailDisplay.textContent = displayEmail;
                    changeAlert.textContent  = 'Updated! Code sent to ' + displayEmail;
                    changeAlert.className    = 'verif-alert ok';
                    // Clear digits
                    digits.forEach(d => { d.value=''; d.classList.remove('filled'); });
                    submitBtn.disabled = true;
                    startCooldown(json.data?.cooldown || 90);
                    setTimeout(() => {
                        changeForm.classList.remove('show');
                        changeBtn.style.display = '';
                        changeAlert.textContent = '';
                    }, 2000);
                } else {
                    changeAlert.textContent = json.data?.message || 'Could not update email.';
                    changeAlert.className   = 'verif-alert err';
                    changeConfBtn.disabled  = false;
                    changeCxlBtn.disabled   = false;
                }
            } catch(e) {
                changeAlert.textContent = 'Network error. Please try again.';
                changeAlert.className   = 'verif-alert err';
                changeConfBtn.disabled  = false;
                changeCxlBtn.disabled   = false;
            }
        });

        // Auto-focus first digit
        setTimeout(() => digits[0].focus(), 200);
    })();
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js" crossorigin="anonymous"></script>
    <?php endif; // end $needs_verif overlay ?>

    <!-- MAIN DASHBOARD APP (always rendered; blurred by CSS overlay when verif needed) -->
    <div id="root" <?php if ($needs_verif): ?>style="filter:blur(3px) brightness(.75); pointer-events:none; user-select:none;" aria-hidden="true"<?php endif; ?>></div>

    <!-- DATA HYDRATION -->
    <?php echo $logic_output; ?>

    <!-- MAIN APP -->
    <?php require plugin_dir_path(__FILE__) . 'dashboard-parts/js-app.php'; ?>

</body>
</html>