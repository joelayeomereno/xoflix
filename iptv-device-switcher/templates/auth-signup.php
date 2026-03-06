<?php
/* Template Name: Sign Up */
get_header();

$verif_on = (bool) get_option( 'streamos_require_email_verification', 0 );
?>
<style>
/* -- Reset theme interference -- */
body { margin:0; padding:0; background:#fff !important; font-family:'Inter',system-ui,-apple-system,sans-serif; color:#0f172a; -webkit-font-smoothing:antialiased; }
.site-header,.site-footer,header,footer,#wpadminbar { display:none !important; }
*,*::before,*::after { box-sizing:border-box; }

:root {
    --p:  #4f46e5; --ph: #4338ca; --pg: rgba(79,70,229,.13);
    --ok: #10b981; --err: #ef4444;
    --bd: #e2e8f0; --mu: #64748b;
    --ease: cubic-bezier(.16,1,.3,1);
}

/* -- Page wrapper -- */
.su-page {
    min-height:100vh; min-height:100dvh;
    display:flex; align-items:flex-start; justify-content:center;
    padding:40px 16px 60px;
    background:
        radial-gradient(ellipse 80% 50% at 12% -5%, rgba(99,102,241,.07) 0%, transparent 58%),
        radial-gradient(ellipse 65% 40% at 88% 108%, rgba(139,92,246,.05) 0%, transparent 58%),
        #fff;
}

/* -- Card -- */
.su-card {
    width:100%; max-width:480px;
    background:#fff;
    border:1px solid var(--bd);
    border-radius:28px;
    padding:44px 36px 40px;
    box-shadow:0 1px 3px rgba(0,0,0,.04), 0 8px 32px -8px rgba(0,0,0,.10);
    animation:suIn .45s var(--ease) both;
}
@keyframes suIn { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }

/* -- Logo mark -- */
.su-logo {
    width:60px; height:60px;
    background:linear-gradient(135deg,#e0e7ff,#ede9fe);
    border-radius:18px; color:var(--p);
    display:inline-flex; align-items:center; justify-content:center;
    font-size:1.4rem; margin-bottom:18px;
    box-shadow:0 6px 18px -4px rgba(99,102,241,.22);
    text-decoration:none; transition:transform .25s var(--ease);
}
.su-logo:hover { transform:scale(1.07) rotate(-4deg); }

/* -- Headings -- */
.su-title { font-size:1.7rem; font-weight:800; letter-spacing:-.025em; text-align:center; margin:0 0 8px; line-height:1.15; }
.su-sub   { font-size:.95rem; color:var(--mu); text-align:center; line-height:1.55; margin:0 auto 28px; max-width:320px; }

/* -- Error banner -- */
.su-err-banner {
    display:flex; align-items:flex-start; gap:10px;
    background:#fef2f2; border:1px solid #fecaca; color:#dc2626;
    padding:13px 15px; border-radius:13px;
    font-size:.88rem; font-weight:600; margin-bottom:24px;
}

/* -- Two-column name row -- */
.su-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }

/* -- Input group -- */
.su-group {
    position:relative;
    margin-bottom:28px;
    height:56px;
}

/* Input base */
.su-input {
    width:100%; height:56px;
    background:#f8fafc !important;
    border:1.5px solid var(--bd) !important;
    border-radius:14px;
    padding:20px 44px 4px 48px !important;
    font-size:.97rem; font-weight:500;
    color:#0f172a !important;
    outline:none; line-height:normal !important;
    transition:border-color .17s, box-shadow .17s, background .17s;
    -webkit-appearance:none;
}
.su-input::placeholder { color:transparent; }
.su-input:hover  { background:#f1f5f9 !important; border-color:#cbd5e1 !important; }
.su-input:focus  { background:#fff !important; border-color:var(--p) !important; box-shadow:0 0 0 4px var(--pg) !important; }
.su-input.valid   { border-color:var(--ok) !important; background:#f0fdf4 !important; }
.su-input.invalid { border-color:var(--err) !important; background:#fef2f2 !important; }
.su-input.loading { border-color:var(--bd)  !important; color:#94a3b8 !important; }

/* Floating label */
.su-label {
    position:absolute; left:48px; top:50%; transform:translateY(-50%);
    color:#94a3b8; pointer-events:none;
    font-size:.95rem; font-weight:400;
    transition:all .17s var(--ease);
    white-space:nowrap; z-index:4; line-height:1;
}
.su-input:focus ~ .su-label,
.su-input:not(:placeholder-shown) ~ .su-label,
.su-label.up {
    top:9px !important; transform:none !important;
    font-size:.72rem !important; font-weight:700 !important; color:var(--p) !important;
}

/* Left icon */
.su-iL {
    position:absolute; left:15px; top:0; bottom:0;
    display:flex; align-items:center;
    color:#94a3b8; font-size:.95rem; pointer-events:none; transition:color .17s; z-index:5;
}
.su-input:focus ~ .su-iL { color:var(--p); }

/* Right validation icon */
.su-iR {
    position:absolute; right:15px; top:0; bottom:0;
    display:flex; align-items:center;
    font-size:.95rem; pointer-events:none; opacity:0; transition:opacity .2s; z-index:5;
}
.su-input.valid   ~ .su-iR.chk { opacity:1; color:var(--ok); }
.su-input.invalid ~ .su-iR.alt { opacity:1; color:var(--err); }

/* Inline spinner */
.su-spin {
    position:absolute; right:15px; top:18px;
    width:20px; height:20px;
    border:2px solid #e2e8f0; border-top-color:var(--p);
    border-radius:50%; animation:suSpin .8s linear infinite;
    display:none; z-index:6;
}
.su-input.loading ~ .su-spin { display:block; }
.su-input.loading ~ .su-iR   { display:none; }
@keyframes suSpin { to{transform:rotate(360deg)} }

/* Field error message */
.su-fmsg {
    position:absolute; bottom:-19px; left:6px;
    font-size:.73rem; font-weight:600; color:var(--err);
    opacity:0; transition:opacity .17s; pointer-events:none;
}
.su-input.invalid ~ .su-fmsg { opacity:1; }

/* -- Phone field: country code prefix -- */
.su-phone-wrap {
    position:relative; margin-bottom:28px; height:56px; display:flex; gap:0;
}
.su-dial-box {
    flex-shrink:0;
    height:56px; min-width:86px;
    background:#f8fafc; border:1.5px solid var(--bd);
    border-right:none; border-radius:14px 0 0 14px;
    display:flex; align-items:center; gap:6px;
    padding:0 10px 0 12px;
    cursor:pointer; user-select:none; transition:border-color .17s;
    position:relative; z-index:10;
}
.su-dial-box:hover, .su-dial-box.open { border-color:var(--p); background:#fff; }
.su-dial-flag { width:20px; height:14px; border-radius:2px; object-fit:cover; }
.su-dial-code { font-size:.88rem; font-weight:700; color:#0f172a; }
.su-dial-caret { font-size:.65rem; color:#94a3b8; }

.su-phone-input {
    flex:1; height:56px;
    background:#f8fafc !important; border:1.5px solid var(--bd) !important;
    border-left:1px solid var(--bd) !important; border-radius:0 14px 14px 0;
    padding:20px 44px 4px 14px !important;
    font-size:.97rem; font-weight:500; color:#0f172a !important;
    outline:none; line-height:normal !important;
    transition:border-color .17s, box-shadow .17s, background .17s;
    -webkit-appearance:none;
    width:100%;
}
.su-phone-input::placeholder { color:transparent; }
.su-phone-input:focus { background:#fff !important; border-color:var(--p) !important; box-shadow:0 0 0 4px var(--pg) !important; }
.su-phone-input.valid   { border-color:var(--ok) !important; background:#f0fdf4 !important; }
.su-phone-input.invalid { border-color:var(--err) !important; background:#fef2f2 !important; }

.su-phone-label {
    position:absolute; left:102px; top:50%; transform:translateY(-50%);
    color:#94a3b8; pointer-events:none; font-size:.95rem; font-weight:400;
    transition:all .17s var(--ease); white-space:nowrap; z-index:4; line-height:1;
}
.su-phone-input:focus ~ .su-phone-label,
.su-phone-input:not(:placeholder-shown) ~ .su-phone-label,
.su-phone-label.up {
    top:9px !important; transform:none !important; left:102px;
    font-size:.72rem !important; font-weight:700 !important; color:var(--p) !important;
}
.su-phone-iR {
    position:absolute; right:15px; top:0; bottom:0;
    display:flex; align-items:center;
    font-size:.95rem; pointer-events:none; opacity:0; transition:opacity .2s; z-index:5;
}
.su-phone-input.valid   ~ .su-phone-iR.chk { opacity:1; color:var(--ok); }
.su-phone-input.invalid ~ .su-phone-iR.alt { opacity:1; color:var(--err); }

/* Dial dropdown */
.su-dial-dropdown {
    position:absolute; top:calc(100% + 6px); left:0; width:260px;
    background:#fff; border:1px solid var(--bd); border-radius:16px;
    box-shadow:0 12px 32px -8px rgba(0,0,0,.12);
    max-height:280px; overflow-y:auto; z-index:400; display:none; padding:6px 0;
}
.su-dial-search {
    margin:6px 8px 4px; width:calc(100% - 16px);
    border:1.5px solid var(--bd); border-radius:10px;
    padding:8px 12px; font-size:.85rem; outline:none; display:block;
    color:#0f172a;
}
.su-dial-search:focus { border-color:var(--p); }
/* FIX: Added color:#0f172a so country names are visible in the dial dropdown */
.su-dial-opt {
    padding:9px 14px; display:flex; align-items:center; gap:10px;
    cursor:pointer; font-size:.88rem; font-weight:500; transition:background .1s;
    color:#0f172a;
}
.su-dial-opt:hover { background:#f1f5f9; color:var(--p); }
.su-dial-opt img  { width:20px; border-radius:2px; }
.su-dial-opt .code { margin-left:auto; color:#64748b; font-weight:600; font-size:.8rem; }

/* US hint */
.su-phone-hint {
    font-size:.74rem; color:#64748b; padding:4px 4px 0; display:none;
}
.su-phone-hint.show { display:block; }

/* -- Country selector -- */
.su-flag {
    position:absolute; left:14px; top:50%; transform:translateY(-50%);
    width:26px; height:18px; border-radius:3px; object-fit:cover;
    box-shadow:0 1px 3px rgba(0,0,0,.12); z-index:6; display:none;
}
.su-dropdown {
    position:absolute; top:calc(100% + 6px); left:0; right:0;
    background:#fff; border:1px solid var(--bd); border-radius:16px;
    box-shadow:0 12px 32px -8px rgba(0,0,0,.12);
    max-height:230px; overflow-y:auto; z-index:300;
    display:none; padding:6px 0;
}
/* FIX: Added color:#0f172a so country names are visible in the country dropdown */
.su-opt {
    padding:10px 18px; display:flex; align-items:center; gap:11px;
    cursor:pointer; font-weight:500; font-size:.92rem; transition:background .1s; color:#0f172a;
}
.su-opt:hover { background:#f1f5f9; color:var(--p); }
.su-opt img   { width:22px; border-radius:3px; }

/* -- Password toggle -- */
.su-tog {
    position:absolute; right:4px; top:50%; transform:translateY(-50%);
    width:44px; height:44px;
    display:flex; align-items:center; justify-content:center;
    color:#94a3b8; cursor:pointer; border-radius:10px;
    transition:color .17s, background .17s; z-index:10;
}
.su-tog:hover { color:#0f172a; background:rgba(0,0,0,.04); }

/* -- Strength meter -- */
.su-meter {
    display:flex; gap:5px; height:5px;
    margin:-14px 0 16px; opacity:0; transition:opacity .3s;
}
.su-meter.show { opacity:1; }
.su-bar { flex:1; background:#e2e8f0; border-radius:99px; transition:background .3s; }

/* -- Password checklist -- */
.su-chklist {
    display:flex; gap:14px; flex-wrap:wrap;
    font-size:.76rem; color:var(--mu); font-weight:500;
    margin:-8px 0 18px; opacity:0; max-height:0; overflow:hidden;
    transition:opacity .3s, max-height .3s;
}
.su-chklist.show { opacity:1; max-height:30px; }
.su-chk-item { display:flex; align-items:center; gap:4px; }
.su-chk-item.ok { color:var(--ok); }
.su-chk-item i  { font-size:10px; }

/* -- Submit button -- */
.su-btn {
    width:100%; height:54px; margin-top:6px;
    background:var(--p); color:#fff; border:none; border-radius:14px;
    font-size:1rem; font-weight:700; cursor:pointer;
    display:flex; align-items:center; justify-content:center; gap:9px;
    transition:all .2s var(--ease);
    box-shadow:0 4px 14px -4px rgba(79,70,229,.42);
    -webkit-tap-highlight-color:transparent;
}
.su-btn:hover:not(:disabled):not(.dis)  { background:var(--ph); transform:translateY(-2px); }
.su-btn:active:not(:disabled):not(.dis) { transform:scale(.98); }
.su-btn.dis, .su-btn:disabled { background:#e2e8f0; color:#94a3b8; box-shadow:none; cursor:not-allowed; transform:none; }
.su-btn-spin {
    width:20px; height:20px;
    border:2.5px solid rgba(255,255,255,.3); border-top-color:#fff;
    border-radius:50%; animation:suSpin .75s linear infinite; display:none;
}

/* -- Footer -- */
.su-footer {
    text-align:center; margin-top:24px; padding-top:20px;
    border-top:1px solid #f1f5f9;
    font-size:.92rem; color:var(--mu);
}
.su-link { color:var(--p); text-decoration:none; font-weight:700; }
.su-link:hover { color:var(--ph); text-decoration:underline; }
.su-terms { text-align:center; font-size:.77rem; color:#94a3b8; margin-top:16px; line-height:1.5; }
.su-terms a { color:#64748b; font-weight:600; text-decoration:underline; }

/* -- Mobile -- */
@media(max-width:520px){
    .su-page { padding:0; align-items:flex-start; }
    .su-card { border:none; border-radius:0; box-shadow:none; min-height:100vh; padding:44px 20px 50px; max-width:100%; }
    .su-title { font-size:1.5rem; }
}
@media(max-width:360px){
    .su-row { grid-template-columns:1fr; gap:0; }
}
</style>

<div class="su-page">
<div class="su-card" id="signup-card">

    <div style="text-align:center; margin-bottom:16px;">
        <a href="<?= esc_url(home_url()) ?>" class="su-logo">
            <i class="fas fa-play" style="margin-left:3px"></i>
        </a>
    </div>

    <h1 class="su-title">Create Account</h1>
    <p class="su-sub">Join the platform to manage your subscriptions and access premium content.</p>

    <?php if (isset($_GET['auth_error'])): ?>
    <div class="su-err-banner">
        <i class="fas fa-circle-exclamation" style="flex-shrink:0;margin-top:1px"></i>
        <span><?= esc_html(urldecode($_GET['auth_error'])) ?></span>
    </div>
    <?php endif; ?>

    <form action="<?= esc_url(home_url('/signup')) ?>" method="post" autocomplete="off" id="signup-form">
        <?php wp_nonce_field('streamos_signup_nonce'); ?>
        <input type="hidden" name="streamos_action" value="signup">

        <!-- Name -->
        <div class="su-row">
            <div class="su-group">
                <input type="text" name="first_name" id="first_name" class="su-input name-input" placeholder=" " required autocomplete="given-name" />
                <label class="su-label">First Name</label>
                <i class="fas fa-user su-iL"></i>
                <i class="fas fa-check-circle su-iR chk"></i>
            </div>
            <div class="su-group">
                <input type="text" name="last_name" id="last_name" class="su-input name-input" placeholder=" " required autocomplete="family-name" />
                <label class="su-label">Last Name</label>
                <i class="fas fa-user su-iL"></i>
                <i class="fas fa-check-circle su-iR chk"></i>
            </div>
        </div>

        <!-- Email -->
        <div class="su-group">
            <input type="email" name="user_email" id="user_email" class="su-input" placeholder=" " required autocomplete="email" inputmode="email" />
            <label class="su-label">Email Address</label>
            <i class="fas fa-envelope su-iL"></i>
            <i class="fas fa-check-circle su-iR chk"></i>
            <i class="fas fa-exclamation-circle su-iR alt"></i>
            <div class="su-spin"></div>
            <div class="su-fmsg" id="email-msg"></div>
        </div>

        <!-- Country -->
        <div class="su-group" id="country-wrapper" style="position:relative;">
            <img id="selected-flag" class="su-flag" src="" alt="">
            <input type="text" id="country_search" class="su-input" placeholder=" " autocomplete="off" />
            <label class="su-label" id="country-label">Country</label>
            <i class="fas fa-globe su-iL" id="globe-icon"></i>
            <i class="fas fa-check-circle su-iR chk"></i>
            <input type="hidden" id="billing_country" name="billing_country" required />
            <div id="country-dropdown" class="su-dropdown"></div>
        </div>

        <?php if ( $verif_on ): ?>
        <!-- -- Phone Number (only when verification feature is ON) -- -->
        <div class="su-phone-wrap" id="phone-field-wrap">
            <!-- Dial code selector -->
            <div class="su-dial-box" id="dial-box">
                <img src="https://flagcdn.com/w40/us.png" id="dial-flag" class="su-dial-flag" alt="">
                <span class="su-dial-code" id="dial-code-display">+1</span>
                <i class="fas fa-chevron-down su-dial-caret" id="dial-caret"></i>
                <div id="dial-dropdown" class="su-dial-dropdown">
                    <input type="text" class="su-dial-search" id="dial-search" placeholder="Search country…" autocomplete="off">
                    <div id="dial-list"></div>
                </div>
            </div>
            <!-- Number input -->
            <input type="tel" name="user_phone_national" id="user_phone_national" class="su-phone-input" placeholder=" " inputmode="numeric" autocomplete="tel-national" />
            <label class="su-phone-label" id="phone-label">Phone number</label>
            <i class="fas fa-check-circle su-phone-iR chk"></i>
            <i class="fas fa-exclamation-circle su-phone-iR alt"></i>
            <!-- Hidden: full international number submitted -->
            <input type="hidden" name="user_phone" id="user_phone_full">
        </div>
        <p class="su-phone-hint" id="phone-hint">Enter your US phone number (10 digits, no +1 needed)</p>
        <?php endif; ?>

        <!-- Password -->
        <div class="su-group">
            <input type="password" name="user_password" id="user_password" class="su-input" placeholder=" " required minlength="6" autocomplete="new-password" style="padding-right:48px !important;" />
            <label class="su-label">Password</label>
            <i class="fas fa-lock su-iL"></i>
            <div class="su-tog" id="tog-pwd"><i class="fas fa-eye"></i></div>
        </div>

        <!-- Strength -->
        <div class="su-meter" id="meter-wrap">
            <div class="su-bar"></div><div class="su-bar"></div><div class="su-bar"></div><div class="su-bar"></div>
        </div>
        <div class="su-chklist" id="pwd-checklist">
            <div class="su-chk-item" id="chk-len">  <i class="fas fa-circle"></i> 6+ chars</div>
            <div class="su-chk-item" id="chk-upper"><i class="fas fa-circle"></i> Uppercase</div>
            <div class="su-chk-item" id="chk-num">  <i class="fas fa-circle"></i> Number</div>
        </div>

        <!-- Confirm password -->
        <div class="su-group">
            <input type="password" name="confirm_password" id="confirm_password" class="su-input" placeholder=" " required autocomplete="new-password" style="padding-right:48px !important;" />
            <label class="su-label">Confirm Password</label>
            <i class="fas fa-lock su-iL"></i>
            <div class="su-tog" id="tog-cfm"><i class="fas fa-eye"></i></div>
            <i class="fas fa-check-circle su-iR chk"></i>
            <i class="fas fa-exclamation-circle su-iR alt"></i>
        </div>

        <button type="submit" class="su-btn dis" id="signup-btn" disabled>
            <span class="btn-text">Create Account</span>
            <i class="fas fa-arrow-right btn-icon"></i>
            <div class="su-btn-spin"></div>
        </button>
    </form>

    <div class="su-terms">
        By creating an account you agree to our
        <a href="<?= esc_url(home_url('/terms')) ?>">Terms of Service</a> and
        <a href="<?= esc_url(home_url('/privacy')) ?>">Privacy Policy</a>.
    </div>

    <div class="su-footer">
        Already have an account? <a href="<?= esc_url(home_url('/login')) ?>" class="su-link">Sign in</a>
    </div>

</div>
</div>

<script>
const VERIF_ON = <?= $verif_on ? 'true' : 'false' ?>;

/* Password toggle */
function bindTog(btnId, inputId) {
    const btn = document.getElementById(btnId);
    const inp = document.getElementById(inputId);
    if (!btn || !inp) return;
    btn.addEventListener('click', () => {
        const show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        btn.querySelector('i').className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
    });
}
bindTog('tog-pwd','user_password');
bindTog('tog-cfm','confirm_password');

document.addEventListener('DOMContentLoaded', function() {

    /* ------- Auto-capitalise names ------- */
    document.querySelectorAll('.name-input').forEach(el => {
        el.addEventListener('input', e => {
            const w = e.target.value.split(' ');
            e.target.value = w.map(s => s.charAt(0).toUpperCase() + s.slice(1)).join(' ');
            validateField(e.target); validateForm();
        });
    });

    /* ------- Country engine ------- */
    /* ALL WORLD COUNTRIES — ISO2 + Name */
    const countries = [
        {c:'AF',n:'Afghanistan'},{c:'AX',n:'Åland Islands'},{c:'AL',n:'Albania'},
        {c:'DZ',n:'Algeria'},{c:'AS',n:'American Samoa'},{c:'AD',n:'Andorra'},
        {c:'AO',n:'Angola'},{c:'AI',n:'Anguilla'},{c:'AQ',n:'Antarctica'},
        {c:'AG',n:'Antigua and Barbuda'},{c:'AR',n:'Argentina'},{c:'AM',n:'Armenia'},
        {c:'AW',n:'Aruba'},{c:'AU',n:'Australia'},{c:'AT',n:'Austria'},
        {c:'AZ',n:'Azerbaijan'},{c:'BS',n:'Bahamas'},{c:'BH',n:'Bahrain'},
        {c:'BD',n:'Bangladesh'},{c:'BB',n:'Barbados'},{c:'BY',n:'Belarus'},
        {c:'BE',n:'Belgium'},{c:'BZ',n:'Belize'},{c:'BJ',n:'Benin'},
        {c:'BM',n:'Bermuda'},{c:'BT',n:'Bhutan'},{c:'BO',n:'Bolivia'},
        {c:'BQ',n:'Bonaire, Sint Eustatius and Saba'},{c:'BA',n:'Bosnia and Herzegovina'},
        {c:'BW',n:'Botswana'},{c:'BV',n:'Bouvet Island'},{c:'BR',n:'Brazil'},
        {c:'IO',n:'British Indian Ocean Territory'},{c:'BN',n:'Brunei'},
        {c:'BG',n:'Bulgaria'},{c:'BF',n:'Burkina Faso'},{c:'BI',n:'Burundi'},
        {c:'CV',n:'Cabo Verde'},{c:'KH',n:'Cambodia'},{c:'CM',n:'Cameroon'},
        {c:'CA',n:'Canada'},{c:'KY',n:'Cayman Islands'},{c:'CF',n:'Central African Republic'},
        {c:'TD',n:'Chad'},{c:'CL',n:'Chile'},{c:'CN',n:'China'},
        {c:'CX',n:'Christmas Island'},{c:'CC',n:'Cocos (Keeling) Islands'},
        {c:'CO',n:'Colombia'},{c:'KM',n:'Comoros'},{c:'CG',n:'Congo'},
        {c:'CD',n:'Congo (DRC)'},{c:'CK',n:'Cook Islands'},{c:'CR',n:'Costa Rica'},
        {c:'HR',n:'Croatia'},{c:'CU',n:'Cuba'},{c:'CW',n:'Curaçao'},
        {c:'CY',n:'Cyprus'},{c:'CZ',n:'Czechia'},{c:'DK',n:'Denmark'},
        {c:'DJ',n:'Djibouti'},{c:'DM',n:'Dominica'},{c:'DO',n:'Dominican Republic'},
        {c:'EC',n:'Ecuador'},{c:'EG',n:'Egypt'},{c:'SV',n:'El Salvador'},
        {c:'GQ',n:'Equatorial Guinea'},{c:'ER',n:'Eritrea'},{c:'EE',n:'Estonia'},
        {c:'SZ',n:'Eswatini'},{c:'ET',n:'Ethiopia'},{c:'FK',n:'Falkland Islands'},
        {c:'FO',n:'Faroe Islands'},{c:'FJ',n:'Fiji'},{c:'FI',n:'Finland'},
        {c:'FR',n:'France'},{c:'GF',n:'French Guiana'},{c:'PF',n:'French Polynesia'},
        {c:'TF',n:'French Southern Territories'},{c:'GA',n:'Gabon'},{c:'GM',n:'Gambia'},
        {c:'GE',n:'Georgia'},{c:'DE',n:'Germany'},{c:'GH',n:'Ghana'},
        {c:'GI',n:'Gibraltar'},{c:'GR',n:'Greece'},{c:'GL',n:'Greenland'},
        {c:'GD',n:'Grenada'},{c:'GP',n:'Guadeloupe'},{c:'GU',n:'Guam'},
        {c:'GT',n:'Guatemala'},{c:'GG',n:'Guernsey'},{c:'GN',n:'Guinea'},
        {c:'GW',n:'Guinea-Bissau'},{c:'GY',n:'Guyana'},{c:'HT',n:'Haiti'},
        {c:'HM',n:'Heard Island and McDonald Islands'},{c:'VA',n:'Holy See (Vatican)'},
        {c:'HN',n:'Honduras'},{c:'HK',n:'Hong Kong'},{c:'HU',n:'Hungary'},
        {c:'IS',n:'Iceland'},{c:'IN',n:'India'},{c:'ID',n:'Indonesia'},
        {c:'IR',n:'Iran'},{c:'IQ',n:'Iraq'},{c:'IE',n:'Ireland'},
        {c:'IM',n:'Isle of Man'},{c:'IL',n:'Israel'},{c:'IT',n:'Italy'},
        {c:'CI',n:'Ivory Coast'},{c:'JM',n:'Jamaica'},{c:'JP',n:'Japan'},
        {c:'JE',n:'Jersey'},{c:'JO',n:'Jordan'},{c:'KZ',n:'Kazakhstan'},
        {c:'KE',n:'Kenya'},{c:'KI',n:'Kiribati'},{c:'KP',n:'North Korea'},
        {c:'KR',n:'South Korea'},{c:'KW',n:'Kuwait'},{c:'KG',n:'Kyrgyzstan'},
        {c:'LA',n:'Laos'},{c:'LV',n:'Latvia'},{c:'LB',n:'Lebanon'},
        {c:'LS',n:'Lesotho'},{c:'LR',n:'Liberia'},{c:'LY',n:'Libya'},
        {c:'LI',n:'Liechtenstein'},{c:'LT',n:'Lithuania'},{c:'LU',n:'Luxembourg'},
        {c:'MO',n:'Macao'},{c:'MG',n:'Madagascar'},{c:'MW',n:'Malawi'},
        {c:'MY',n:'Malaysia'},{c:'MV',n:'Maldives'},{c:'ML',n:'Mali'},
        {c:'MT',n:'Malta'},{c:'MH',n:'Marshall Islands'},{c:'MQ',n:'Martinique'},
        {c:'MR',n:'Mauritania'},{c:'MU',n:'Mauritius'},{c:'YT',n:'Mayotte'},
        {c:'MX',n:'Mexico'},{c:'FM',n:'Micronesia'},{c:'MD',n:'Moldova'},
        {c:'MC',n:'Monaco'},{c:'MN',n:'Mongolia'},{c:'ME',n:'Montenegro'},
        {c:'MS',n:'Montserrat'},{c:'MA',n:'Morocco'},{c:'MZ',n:'Mozambique'},
        {c:'MM',n:'Myanmar'},{c:'NA',n:'Namibia'},{c:'NR',n:'Nauru'},
        {c:'NP',n:'Nepal'},{c:'NL',n:'Netherlands'},{c:'NC',n:'New Caledonia'},
        {c:'NZ',n:'New Zealand'},{c:'NI',n:'Nicaragua'},{c:'NE',n:'Niger'},
        {c:'NG',n:'Nigeria'},{c:'NU',n:'Niue'},{c:'NF',n:'Norfolk Island'},
        {c:'MK',n:'North Macedonia'},{c:'MP',n:'Northern Mariana Islands'},
        {c:'NO',n:'Norway'},{c:'OM',n:'Oman'},{c:'PK',n:'Pakistan'},
        {c:'PW',n:'Palau'},{c:'PS',n:'Palestine'},{c:'PA',n:'Panama'},
        {c:'PG',n:'Papua New Guinea'},{c:'PY',n:'Paraguay'},{c:'PE',n:'Peru'},
        {c:'PH',n:'Philippines'},{c:'PN',n:'Pitcairn'},{c:'PL',n:'Poland'},
        {c:'PT',n:'Portugal'},{c:'PR',n:'Puerto Rico'},{c:'QA',n:'Qatar'},
        {c:'RE',n:'Réunion'},{c:'RO',n:'Romania'},{c:'RU',n:'Russia'},
        {c:'RW',n:'Rwanda'},{c:'BL',n:'Saint Barthélemy'},{c:'SH',n:'Saint Helena'},
        {c:'KN',n:'Saint Kitts and Nevis'},{c:'LC',n:'Saint Lucia'},
        {c:'MF',n:'Saint Martin'},{c:'PM',n:'Saint Pierre and Miquelon'},
        {c:'VC',n:'Saint Vincent and the Grenadines'},{c:'WS',n:'Samoa'},
        {c:'SM',n:'San Marino'},{c:'ST',n:'São Tomé and Príncipe'},
        {c:'SA',n:'Saudi Arabia'},{c:'SN',n:'Senegal'},{c:'RS',n:'Serbia'},
        {c:'SC',n:'Seychelles'},{c:'SL',n:'Sierra Leone'},{c:'SG',n:'Singapore'},
        {c:'SX',n:'Sint Maarten'},{c:'SK',n:'Slovakia'},{c:'SI',n:'Slovenia'},
        {c:'SB',n:'Solomon Islands'},{c:'SO',n:'Somalia'},{c:'ZA',n:'South Africa'},
        {c:'GS',n:'South Georgia and the South Sandwich Islands'},
        {c:'SS',n:'South Sudan'},{c:'ES',n:'Spain'},{c:'LK',n:'Sri Lanka'},
        {c:'SD',n:'Sudan'},{c:'SR',n:'Suriname'},{c:'SJ',n:'Svalbard and Jan Mayen'},
        {c:'SE',n:'Sweden'},{c:'CH',n:'Switzerland'},{c:'SY',n:'Syria'},
        {c:'TW',n:'Taiwan'},{c:'TJ',n:'Tajikistan'},{c:'TZ',n:'Tanzania'},
        {c:'TH',n:'Thailand'},{c:'TL',n:'Timor-Leste'},{c:'TG',n:'Togo'},
        {c:'TK',n:'Tokelau'},{c:'TO',n:'Tonga'},{c:'TT',n:'Trinidad and Tobago'},
        {c:'TN',n:'Tunisia'},{c:'TR',n:'Turkey'},{c:'TM',n:'Turkmenistan'},
        {c:'TC',n:'Turks and Caicos Islands'},{c:'TV',n:'Tuvalu'},
        {c:'UG',n:'Uganda'},{c:'UA',n:'Ukraine'},{c:'AE',n:'United Arab Emirates'},
        {c:'GB',n:'United Kingdom'},{c:'US',n:'United States'},
        {c:'UM',n:'United States Minor Outlying Islands'},
        {c:'UY',n:'Uruguay'},{c:'UZ',n:'Uzbekistan'},{c:'VU',n:'Vanuatu'},
        {c:'VE',n:'Venezuela'},{c:'VN',n:'Vietnam'},{c:'VG',n:'Virgin Islands (British)'},
        {c:'VI',n:'Virgin Islands (US)'},{c:'WF',n:'Wallis and Futuna'},
        {c:'EH',n:'Western Sahara'},{c:'YE',n:'Yemen'},{c:'ZM',n:'Zambia'},
        {c:'ZW',n:'Zimbabwe'}
    ];

    const searchIn  = document.getElementById('country_search');
    const hiddenIn  = document.getElementById('billing_country');
    const dropdown  = document.getElementById('country-dropdown');
    const flagImg   = document.getElementById('selected-flag');
    const globeIcon = document.getElementById('globe-icon');
    const cLabel    = document.getElementById('country-label');

    function renderCountries(filter) {
        dropdown.innerHTML = '';
        const term  = (filter||'').toLowerCase();
        const hits  = countries.filter(c => c.n.toLowerCase().includes(term));
        if (!hits.length) { dropdown.style.display = 'none'; return; }
        hits.forEach(c => {
            const row = document.createElement('div');
            row.className = 'su-opt';
            row.innerHTML = `<img src="https://flagcdn.com/w40/${c.c.toLowerCase()}.png" loading="lazy"> ${c.n}`;
            row.addEventListener('click', () => selectCountry(c));
            dropdown.appendChild(row);
        });
        dropdown.style.display = 'block';
    }

    function selectCountry(c) {
        searchIn.value          = c.n;
        hiddenIn.value          = c.c;
        dropdown.style.display  = 'none';
        globeIcon.style.display = 'none';
        flagImg.src             = `https://flagcdn.com/w40/${c.c.toLowerCase()}.png`;
        flagImg.style.display   = 'block';
        cLabel.classList.add('up');
        setFieldState(searchIn, true);
        validateForm();
    }

    fetch('https://ipapi.co/json/').then(r=>r.json()).then(d => {
        if (d && d.country_code) {
            const m = countries.find(c => c.c === d.country_code);
            if (m) selectCountry(m);
        }
    }).catch(()=>{});

    searchIn.addEventListener('input', e => {
        renderCountries(e.target.value);
        if (!e.target.value) { hiddenIn.value=''; flagImg.style.display='none'; globeIcon.style.display='flex'; cLabel.classList.remove('up'); }
    });
    searchIn.addEventListener('focus', () => renderCountries(searchIn.value));
    document.addEventListener('click', e => {
        if (!e.target.closest('#country-wrapper')) dropdown.style.display = 'none';
    });

    /* ------- Phone / Dial code engine (only when VERIF_ON) ------- */
    if (VERIF_ON) {
        /* ALL WORLD COUNTRIES — dial codes */
        const DIAL_CODES = [
            {c:'AF',d:'+93',  n:'Afghanistan'},
            {c:'AL',d:'+355', n:'Albania'},
            {c:'DZ',d:'+213', n:'Algeria'},
            {c:'AS',d:'+1684',n:'American Samoa'},
            {c:'AD',d:'+376', n:'Andorra'},
            {c:'AO',d:'+244', n:'Angola'},
            {c:'AI',d:'+1264',n:'Anguilla'},
            {c:'AG',d:'+1268',n:'Antigua and Barbuda'},
            {c:'AR',d:'+54',  n:'Argentina'},
            {c:'AM',d:'+374', n:'Armenia'},
            {c:'AW',d:'+297', n:'Aruba'},
            {c:'AU',d:'+61',  n:'Australia'},
            {c:'AT',d:'+43',  n:'Austria'},
            {c:'AZ',d:'+994', n:'Azerbaijan'},
            {c:'BS',d:'+1242',n:'Bahamas'},
            {c:'BH',d:'+973', n:'Bahrain'},
            {c:'BD',d:'+880', n:'Bangladesh'},
            {c:'BB',d:'+1246',n:'Barbados'},
            {c:'BY',d:'+375', n:'Belarus'},
            {c:'BE',d:'+32',  n:'Belgium'},
            {c:'BZ',d:'+501', n:'Belize'},
            {c:'BJ',d:'+229', n:'Benin'},
            {c:'BM',d:'+1441',n:'Bermuda'},
            {c:'BT',d:'+975', n:'Bhutan'},
            {c:'BO',d:'+591', n:'Bolivia'},
            {c:'BA',d:'+387', n:'Bosnia and Herzegovina'},
            {c:'BW',d:'+267', n:'Botswana'},
            {c:'BR',d:'+55',  n:'Brazil'},
            {c:'BN',d:'+673', n:'Brunei'},
            {c:'BG',d:'+359', n:'Bulgaria'},
            {c:'BF',d:'+226', n:'Burkina Faso'},
            {c:'BI',d:'+257', n:'Burundi'},
            {c:'CV',d:'+238', n:'Cabo Verde'},
            {c:'KH',d:'+855', n:'Cambodia'},
            {c:'CM',d:'+237', n:'Cameroon'},
            {c:'CA',d:'+1',   n:'Canada'},
            {c:'KY',d:'+1345',n:'Cayman Islands'},
            {c:'CF',d:'+236', n:'Central African Republic'},
            {c:'TD',d:'+235', n:'Chad'},
            {c:'CL',d:'+56',  n:'Chile'},
            {c:'CN',d:'+86',  n:'China'},
            {c:'CO',d:'+57',  n:'Colombia'},
            {c:'KM',d:'+269', n:'Comoros'},
            {c:'CG',d:'+242', n:'Congo'},
            {c:'CD',d:'+243', n:'Congo (DRC)'},
            {c:'CK',d:'+682', n:'Cook Islands'},
            {c:'CR',d:'+506', n:'Costa Rica'},
            {c:'HR',d:'+385', n:'Croatia'},
            {c:'CU',d:'+53',  n:'Cuba'},
            {c:'CW',d:'+599', n:'Curaçao'},
            {c:'CY',d:'+357', n:'Cyprus'},
            {c:'CZ',d:'+420', n:'Czechia'},
            {c:'DK',d:'+45',  n:'Denmark'},
            {c:'DJ',d:'+253', n:'Djibouti'},
            {c:'DM',d:'+1767',n:'Dominica'},
            {c:'DO',d:'+1809',n:'Dominican Republic'},
            {c:'EC',d:'+593', n:'Ecuador'},
            {c:'EG',d:'+20',  n:'Egypt'},
            {c:'SV',d:'+503', n:'El Salvador'},
            {c:'GQ',d:'+240', n:'Equatorial Guinea'},
            {c:'ER',d:'+291', n:'Eritrea'},
            {c:'EE',d:'+372', n:'Estonia'},
            {c:'SZ',d:'+268', n:'Eswatini'},
            {c:'ET',d:'+251', n:'Ethiopia'},
            {c:'FK',d:'+500', n:'Falkland Islands'},
            {c:'FO',d:'+298', n:'Faroe Islands'},
            {c:'FJ',d:'+679', n:'Fiji'},
            {c:'FI',d:'+358', n:'Finland'},
            {c:'FR',d:'+33',  n:'France'},
            {c:'GF',d:'+594', n:'French Guiana'},
            {c:'PF',d:'+689', n:'French Polynesia'},
            {c:'GA',d:'+241', n:'Gabon'},
            {c:'GM',d:'+220', n:'Gambia'},
            {c:'GE',d:'+995', n:'Georgia'},
            {c:'DE',d:'+49',  n:'Germany'},
            {c:'GH',d:'+233', n:'Ghana'},
            {c:'GI',d:'+350', n:'Gibraltar'},
            {c:'GR',d:'+30',  n:'Greece'},
            {c:'GL',d:'+299', n:'Greenland'},
            {c:'GD',d:'+1473',n:'Grenada'},
            {c:'GP',d:'+590', n:'Guadeloupe'},
            {c:'GU',d:'+1671',n:'Guam'},
            {c:'GT',d:'+502', n:'Guatemala'},
            {c:'GN',d:'+224', n:'Guinea'},
            {c:'GW',d:'+245', n:'Guinea-Bissau'},
            {c:'GY',d:'+592', n:'Guyana'},
            {c:'HT',d:'+509', n:'Haiti'},
            {c:'VA',d:'+379', n:'Holy See (Vatican)'},
            {c:'HN',d:'+504', n:'Honduras'},
            {c:'HK',d:'+852', n:'Hong Kong'},
            {c:'HU',d:'+36',  n:'Hungary'},
            {c:'IS',d:'+354', n:'Iceland'},
            {c:'IN',d:'+91',  n:'India'},
            {c:'ID',d:'+62',  n:'Indonesia'},
            {c:'IR',d:'+98',  n:'Iran'},
            {c:'IQ',d:'+964', n:'Iraq'},
            {c:'IE',d:'+353', n:'Ireland'},
            {c:'IL',d:'+972', n:'Israel'},
            {c:'IT',d:'+39',  n:'Italy'},
            {c:'CI',d:'+225', n:'Ivory Coast'},
            {c:'JM',d:'+1876',n:'Jamaica'},
            {c:'JP',d:'+81',  n:'Japan'},
            {c:'JO',d:'+962', n:'Jordan'},
            {c:'KZ',d:'+7',   n:'Kazakhstan'},
            {c:'KE',d:'+254', n:'Kenya'},
            {c:'KI',d:'+686', n:'Kiribati'},
            {c:'KP',d:'+850', n:'North Korea'},
            {c:'KR',d:'+82',  n:'South Korea'},
            {c:'KW',d:'+965', n:'Kuwait'},
            {c:'KG',d:'+996', n:'Kyrgyzstan'},
            {c:'LA',d:'+856', n:'Laos'},
            {c:'LV',d:'+371', n:'Latvia'},
            {c:'LB',d:'+961', n:'Lebanon'},
            {c:'LS',d:'+266', n:'Lesotho'},
            {c:'LR',d:'+231', n:'Liberia'},
            {c:'LY',d:'+218', n:'Libya'},
            {c:'LI',d:'+423', n:'Liechtenstein'},
            {c:'LT',d:'+370', n:'Lithuania'},
            {c:'LU',d:'+352', n:'Luxembourg'},
            {c:'MO',d:'+853', n:'Macao'},
            {c:'MG',d:'+261', n:'Madagascar'},
            {c:'MW',d:'+265', n:'Malawi'},
            {c:'MY',d:'+60',  n:'Malaysia'},
            {c:'MV',d:'+960', n:'Maldives'},
            {c:'ML',d:'+223', n:'Mali'},
            {c:'MT',d:'+356', n:'Malta'},
            {c:'MH',d:'+692', n:'Marshall Islands'},
            {c:'MQ',d:'+596', n:'Martinique'},
            {c:'MR',d:'+222', n:'Mauritania'},
            {c:'MU',d:'+230', n:'Mauritius'},
            {c:'YT',d:'+262', n:'Mayotte'},
            {c:'MX',d:'+52',  n:'Mexico'},
            {c:'FM',d:'+691', n:'Micronesia'},
            {c:'MD',d:'+373', n:'Moldova'},
            {c:'MC',d:'+377', n:'Monaco'},
            {c:'MN',d:'+976', n:'Mongolia'},
            {c:'ME',d:'+382', n:'Montenegro'},
            {c:'MS',d:'+1664',n:'Montserrat'},
            {c:'MA',d:'+212', n:'Morocco'},
            {c:'MZ',d:'+258', n:'Mozambique'},
            {c:'MM',d:'+95',  n:'Myanmar'},
            {c:'NA',d:'+264', n:'Namibia'},
            {c:'NR',d:'+674', n:'Nauru'},
            {c:'NP',d:'+977', n:'Nepal'},
            {c:'NL',d:'+31',  n:'Netherlands'},
            {c:'NC',d:'+687', n:'New Caledonia'},
            {c:'NZ',d:'+64',  n:'New Zealand'},
            {c:'NI',d:'+505', n:'Nicaragua'},
            {c:'NE',d:'+227', n:'Niger'},
            {c:'NG',d:'+234', n:'Nigeria'},
            {c:'NU',d:'+683', n:'Niue'},
            {c:'MK',d:'+389', n:'North Macedonia'},
            {c:'NO',d:'+47',  n:'Norway'},
            {c:'OM',d:'+968', n:'Oman'},
            {c:'PK',d:'+92',  n:'Pakistan'},
            {c:'PW',d:'+680', n:'Palau'},
            {c:'PS',d:'+970', n:'Palestine'},
            {c:'PA',d:'+507', n:'Panama'},
            {c:'PG',d:'+675', n:'Papua New Guinea'},
            {c:'PY',d:'+595', n:'Paraguay'},
            {c:'PE',d:'+51',  n:'Peru'},
            {c:'PH',d:'+63',  n:'Philippines'},
            {c:'PL',d:'+48',  n:'Poland'},
            {c:'PT',d:'+351', n:'Portugal'},
            {c:'PR',d:'+1787',n:'Puerto Rico'},
            {c:'QA',d:'+974', n:'Qatar'},
            {c:'RE',d:'+262', n:'Réunion'},
            {c:'RO',d:'+40',  n:'Romania'},
            {c:'RU',d:'+7',   n:'Russia'},
            {c:'RW',d:'+250', n:'Rwanda'},
            {c:'KN',d:'+1869',n:'Saint Kitts and Nevis'},
            {c:'LC',d:'+1758',n:'Saint Lucia'},
            {c:'VC',d:'+1784',n:'Saint Vincent and the Grenadines'},
            {c:'WS',d:'+685', n:'Samoa'},
            {c:'SM',d:'+378', n:'San Marino'},
            {c:'ST',d:'+239', n:'São Tomé and Príncipe'},
            {c:'SA',d:'+966', n:'Saudi Arabia'},
            {c:'SN',d:'+221', n:'Senegal'},
            {c:'RS',d:'+381', n:'Serbia'},
            {c:'SC',d:'+248', n:'Seychelles'},
            {c:'SL',d:'+232', n:'Sierra Leone'},
            {c:'SG',d:'+65',  n:'Singapore'},
            {c:'SX',d:'+1721',n:'Sint Maarten'},
            {c:'SK',d:'+421', n:'Slovakia'},
            {c:'SI',d:'+386', n:'Slovenia'},
            {c:'SB',d:'+677', n:'Solomon Islands'},
            {c:'SO',d:'+252', n:'Somalia'},
            {c:'ZA',d:'+27',  n:'South Africa'},
            {c:'SS',d:'+211', n:'South Sudan'},
            {c:'ES',d:'+34',  n:'Spain'},
            {c:'LK',d:'+94',  n:'Sri Lanka'},
            {c:'SD',d:'+249', n:'Sudan'},
            {c:'SR',d:'+597', n:'Suriname'},
            {c:'SE',d:'+46',  n:'Sweden'},
            {c:'CH',d:'+41',  n:'Switzerland'},
            {c:'SY',d:'+963', n:'Syria'},
            {c:'TW',d:'+886', n:'Taiwan'},
            {c:'TJ',d:'+992', n:'Tajikistan'},
            {c:'TZ',d:'+255', n:'Tanzania'},
            {c:'TH',d:'+66',  n:'Thailand'},
            {c:'TL',d:'+670', n:'Timor-Leste'},
            {c:'TG',d:'+228', n:'Togo'},
            {c:'TO',d:'+676', n:'Tonga'},
            {c:'TT',d:'+1868',n:'Trinidad and Tobago'},
            {c:'TN',d:'+216', n:'Tunisia'},
            {c:'TR',d:'+90',  n:'Turkey'},
            {c:'TM',d:'+993', n:'Turkmenistan'},
            {c:'TC',d:'+1649',n:'Turks and Caicos Islands'},
            {c:'TV',d:'+688', n:'Tuvalu'},
            {c:'UG',d:'+256', n:'Uganda'},
            {c:'UA',d:'+380', n:'Ukraine'},
            {c:'AE',d:'+971', n:'United Arab Emirates'},
            {c:'GB',d:'+44',  n:'United Kingdom'},
            {c:'US',d:'+1',   n:'United States'},
            {c:'UY',d:'+598', n:'Uruguay'},
            {c:'UZ',d:'+998', n:'Uzbekistan'},
            {c:'VU',d:'+678', n:'Vanuatu'},
            {c:'VE',d:'+58',  n:'Venezuela'},
            {c:'VN',d:'+84',  n:'Vietnam'},
            {c:'VG',d:'+1284',n:'Virgin Islands (British)'},
            {c:'VI',d:'+1340',n:'Virgin Islands (US)'},
            {c:'WF',d:'+681', n:'Wallis and Futuna'},
            {c:'YE',d:'+967', n:'Yemen'},
            {c:'ZM',d:'+260', n:'Zambia'},
            {c:'ZW',d:'+263', n:'Zimbabwe'},
        ];

        let selectedDial = DIAL_CODES.find(x => x.c === 'US') || DIAL_CODES[0];

        const dialBox      = document.getElementById('dial-box');
        const dialDropdown = document.getElementById('dial-dropdown');
        const dialFlag     = document.getElementById('dial-flag');
        const dialCodeDisp = document.getElementById('dial-code-display');
        const dialSearch   = document.getElementById('dial-search');
        const dialList     = document.getElementById('dial-list');
        const phoneInput   = document.getElementById('user_phone_national');
        const phoneFull    = document.getElementById('user_phone_full');
        const phoneLabel   = document.getElementById('phone-label');
        const phoneHint    = document.getElementById('phone-hint');

        function renderDialList(filter) {
            dialList.innerHTML = '';
            const t = (filter||'').toLowerCase();
            const hits = DIAL_CODES.filter(x => x.n.toLowerCase().includes(t) || x.d.includes(t));
            hits.forEach(x => {
                const row = document.createElement('div');
                row.className = 'su-dial-opt';
                row.innerHTML = `<img src="https://flagcdn.com/w40/${x.c.toLowerCase()}.png" loading="lazy"><span>${x.n}</span><span class="code">${x.d}</span>`;
                row.addEventListener('click', () => { selectDial(x); closeDialDropdown(); });
                dialList.appendChild(row);
            });
        }

        function selectDial(x) {
            selectedDial  = x;
            dialFlag.src  = `https://flagcdn.com/w40/${x.c.toLowerCase()}.png`;
            dialCodeDisp.textContent = x.d;
            updatePhoneHint();
            updateFullPhone();
            validatePhone();
        }

        function updatePhoneHint() {
            if (selectedDial.c === 'US' || selectedDial.c === 'CA') {
                phoneHint.textContent = selectedDial.c === 'US'
                    ? 'Enter your US phone number (10 digits, no +1 needed)'
                    : 'Enter your Canadian phone number (10 digits, no +1 needed)';
                phoneHint.classList.add('show');
            } else {
                phoneHint.classList.remove('show');
            }
        }

        function openDialDropdown() {
            renderDialList('');
            dialDropdown.style.display = 'block';
            dialBox.classList.add('open');
            setTimeout(() => dialSearch.focus(), 50);
        }
        function closeDialDropdown() {
            dialDropdown.style.display = 'none';
            dialBox.classList.remove('open');
            dialSearch.value = '';
        }

        dialBox.addEventListener('click', e => {
            e.stopPropagation();
            dialDropdown.style.display === 'none' ? openDialDropdown() : closeDialDropdown();
        });
        dialSearch.addEventListener('input', e => renderDialList(e.target.value));
        dialSearch.addEventListener('click', e => e.stopPropagation());
        document.addEventListener('click', e => {
            if (!e.target.closest('#dial-box')) closeDialDropdown();
        });

        function updateFullPhone() {
            let national = (phoneInput.value || '').replace(/\D/g, '');
            // Strip leading zero for all countries
            national = national.replace(/^0+/, '');
            phoneFull.value = national ? selectedDial.d + national : '';
        }

        function validatePhone() {
            const national = (phoneInput.value || '').replace(/\D/g, '').replace(/^0+/, '');
            const minLen   = (selectedDial.c === 'US' || selectedDial.c === 'CA') ? 10 : 6;
            const isValid  = national.length >= minLen;
            phoneInput.classList.toggle('valid',   isValid && national.length > 0);
            phoneInput.classList.toggle('invalid', !isValid && national.length > 0);
            updateFullPhone();
            validateForm();
        }

        phoneInput.addEventListener('input',  validatePhone);
        phoneInput.addEventListener('blur',   validatePhone);
        phoneLabel.addEventListener('click',  () => phoneInput.focus());

        // Sync dial code with selected country
        document.addEventListener('countrySelected', e => {
            const match = DIAL_CODES.find(x => x.c === e.detail.code);
            if (match) selectDial(match);
        });

    } // end VERIF_ON

    /* ------- Password strength ------- */
    const pwd      = document.getElementById('user_password');
    const cfm      = document.getElementById('confirm_password');
    const meterWrap= document.getElementById('meter-wrap');
    const bars     = meterWrap.querySelectorAll('.su-bar');
    const chklist  = document.getElementById('pwd-checklist');
    const chkLen   = document.getElementById('chk-len');
    const chkUpper = document.getElementById('chk-upper');
    const chkNum   = document.getElementById('chk-num');
    const email    = document.getElementById('user_email');
    const emailMsg = document.getElementById('email-msg');
    const btn      = document.getElementById('signup-btn');
    let emailOK    = false;

    function setFieldState(inp, ok) {
        inp.classList.toggle('valid', ok);
        inp.classList.toggle('invalid', !ok && inp.value.length > 0);
    }
    function validateField(inp) {
        setFieldState(inp, inp.value.trim().length > 0);
    }

    pwd.addEventListener('input', () => {
        const v = pwd.value;
        const hasLen   = v.length >= 6;
        const hasUpper = /[A-Z]/.test(v);
        const hasNum   = /\d/.test(v);
        const score    = [hasLen, hasUpper, hasNum].filter(Boolean).length;
        const colors   = ['#ef4444','#f97316','#eab308','#10b981'];
        bars.forEach((b,i) => b.style.background = i < score ? colors[score-1] : '#e2e8f0');
        meterWrap.classList.toggle('show', v.length > 0);
        chklist.classList.toggle('show', v.length > 0);
        setChk(chkLen,   hasLen);
        setChk(chkUpper, hasUpper);
        setChk(chkNum,   hasNum);
        setFieldState(pwd, score >= 2 && v.length >= 6);
        if (cfm.value) setFieldState(cfm, cfm.value === v);
        validateForm();
    });

    function validateForm() {
        const country = document.getElementById('billing_country').value;
        const p = pwd.value;
        const c = cfm.value;
        const vEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value) && emailOK;
        const strong = p.length >= 6;
        const fn     = document.getElementById('first_name').value;
        const ln     = document.getElementById('last_name').value;

        let phoneOK = true;
        if (VERIF_ON) {
            const pInput = document.getElementById('user_phone_national');
            if (pInput) {
                const national = (pInput.value||'').replace(/\D/g,'').replace(/^0+/,'');
                const dialCode = document.getElementById('dial-code-display');
                const cc       = dialCode ? dialCode.textContent : '+1';
                const minLen   = (cc === '+1') ? 10 : 6;
                phoneOK        = national.length >= minLen;
            }
        }

        const ok = strong && p === c && country && fn && ln && vEmail && phoneOK;
        btn.disabled = !ok;
        ok ? btn.classList.remove('dis') : btn.classList.add('dis');
    }
    function setChk(el, ok) {
        const ic = el.querySelector('i');
        if (ok) { el.classList.add('ok'); ic.className='fas fa-check-circle'; }
        else    { el.classList.remove('ok'); ic.className='fas fa-circle'; }
    }

    /* Names */
    ['first_name','last_name'].forEach(id => {
        const el = document.getElementById(id);
        el.addEventListener('input',  () => { validateField(el); validateForm(); });
        el.addEventListener('blur',   () => { validateField(el); });
    });

    /* Email */
    email.addEventListener('input', () => {
        emailOK = false; emailMsg.innerText = '';
        email.classList.remove('valid','invalid','loading');

        if (email.value.includes('@')) {
            const domain = email.value.split('@')[1]||'';
            if (isDisposable(email.value)) {
                email.classList.add('invalid');
                emailMsg.innerText = 'Disposable/temporary email addresses are not allowed. Please use a personal or work email.';
                validateForm(); return;
            }
        }
        validateForm();
    });

    email.addEventListener('blur', async () => {
        const v = email.value;
        if (!v.includes('@') || v.length < 5) return;
        if (isDisposable(v)) {
            email.classList.add('invalid');
            emailMsg.innerText = 'Disposable/temporary email addresses are not allowed.';
            validateForm(); return;
        }
        email.classList.add('loading'); emailOK = false;
        try {
            const res  = await fetch(`/?streamos_action=check_email&email=${encodeURIComponent(v)}&_nonce=<?= wp_create_nonce('streamos_check_email') ?>`);
            const data = await res.json();
            email.classList.remove('loading');
            if (data.exists) {
                email.classList.add('invalid');
                emailMsg.innerText = 'This email is already registered.';
                emailOK = false;
            } else {
                email.classList.add('valid');
                emailOK = true;
            }
        } catch(e) {
            email.classList.remove('loading');
            emailOK = true;
        }
        validateForm();
    });

    /* Disposable email check */
    function isDisposable(email) {
        const disposable = ['mailinator.com','guerrillamail.com','temp-mail.org','throwaway.email',
            'fakeinbox.com','yopmail.com','trashmail.com','sharklasers.com','guerrillamailblock.com',
            'grr.la','guerrillamail.info','guerrillamail.biz','guerrillamail.de','guerrillamail.net',
            'guerrillamail.org','spam4.me','tempmail.com','dispostable.com','mailnull.com'];
        const domain = email.split('@')[1]||'';
        return disposable.includes(domain.toLowerCase());
    }

    /* Confirm password */
    cfm.addEventListener('input', () => {
        setFieldState(cfm, cfm.value === pwd.value && cfm.value.length > 0); validateForm();
    });

    /* Submit */
    document.getElementById('signup-form').addEventListener('submit', () => {
        const t  = btn.querySelector('.btn-text');
        const ic = btn.querySelector('.btn-icon');
        const sp = btn.querySelector('.su-btn-spin');
        if (t)  t.style.display  = 'none';
        if (ic) ic.style.display = 'none';
        if (sp) sp.style.display = 'block';
    });

    setTimeout(() => document.getElementById('first_name').focus(), 300);
});
</script>

<?php get_footer(); ?>