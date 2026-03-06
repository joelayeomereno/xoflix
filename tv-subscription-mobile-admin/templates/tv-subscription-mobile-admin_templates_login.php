<?php
if (!defined('ABSPATH')) { exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['tv_mobile_admin_login_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tv_mobile_admin_login_nonce'])), 'tv_mobile_admin_login')) {
        $errors[] = 'Security token expired. Please refresh and try again.';
    } else {
        $username = isset($_POST['log']) ? sanitize_text_field(wp_unslash($_POST['log'])) : '';
        $password = isset($_POST['pwd']) ? (string) wp_unslash($_POST['pwd']) : '';

        $user = wp_signon(['user_login'=>$username,'user_password'=>$password,'remember'=>true], is_ssl());

        if (is_wp_error($user)) {
            $errors[] = $user->get_error_message();
        } else {
            wp_set_current_user($user->ID);
            if (user_can($user, 'manage_options')) {
                wp_safe_redirect(home_url('/admin?t=' . time()));
                exit;
            } else {
                $errors[] = 'Access denied. Administrator permissions required.';
                wp_logout();
            }
        }
    }
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title>Admin Login · XOFLIX</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body {
      height: 100%; font-family: 'DM Sans', system-ui, sans-serif;
      background: #09090b; color: #fafafa;
      -webkit-tap-highlight-color: transparent;
    }
    body {
      display: flex; align-items: center; justify-content: center;
      padding: 24px; padding-top: env(safe-area-inset-top,24px); padding-bottom: env(safe-area-inset-bottom,24px);
      min-height: 100%;
    }
    @keyframes fadeUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
    .card { animation: fadeUp .4s cubic-bezier(.16,1,.3,1) both; background: #111113; border: 1px solid rgba(255,255,255,.07); border-radius: 24px; padding: 36px 28px; width: 100%; max-width: 380px; }
    .logo { display:flex; align-items:center; gap:10; margin-bottom:32px; }
    .logo-mark { width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg,#6366f1,#8b5cf6); display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:900; color:#fff; }
    .logo-text { font-size:20px; font-weight:900; letter-spacing:-.02em; }
    h1 { font-size:26px; font-weight:900; margin-bottom:6px; }
    .sub { font-size:14px; color:#71717a; margin-bottom:28px; }
    .error { background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.2); border-radius:12px; padding:12px 16px; margin-bottom:20px; color:#f87171; font-size:14px; font-weight:600; }
    label { display:block; font-size:11px; font-weight:700; color:#71717a; letter-spacing:.06em; text-transform:uppercase; margin-bottom:6px; }
    .field-wrap { margin-bottom:16px; }
    input { width:100%; padding:14px 16px; background:#18181b; border:1px solid rgba(255,255,255,.08); border-radius:12px; color:#fafafa; font-family:inherit; font-size:16px; font-weight:500; outline:none; transition:.15s; -webkit-appearance:none; }
    input::placeholder { color:#52525b; }
    input:focus { border-color:#6366f1; background:#1c1c1f; }
    button[type=submit] { width:100%; padding:16px; background:#6366f1; color:#fff; border:none; border-radius:12px; font-family:inherit; font-size:16px; font-weight:800; cursor:pointer; margin-top:8px; transition:.15s; letter-spacing:.01em; }
    button[type=submit]:active { background:#4f46e5; transform:scale(.98); }
    .footer { text-align:center; margin-top:24px; font-size:12px; color:#3f3f46; }
    .bg-orb { position:fixed; border-radius:50%; pointer-events:none; filter:blur(80px); opacity:.15; }
    .orb1 { width:300px; height:300px; background:#6366f1; top:-100px; right:-80px; }
    .orb2 { width:200px; height:200px; background:#8b5cf6; bottom:-60px; left:-60px; }
  </style>
</head>
<body>
  <div class="bg-orb orb1"></div>
  <div class="bg-orb orb2"></div>
  <div class="card">
    <div class="logo">
      <div class="logo-mark">X</div>
      <span class="logo-text">XOFLIX</span>
    </div>
    <h1>Welcome back</h1>
    <p class="sub">Sign in to your admin panel</p>

    <?php if (!empty($errors)) : ?>
      <div class="error">
        <?php foreach ($errors as $e) : ?>
          <div><?php echo wp_kses_post($e); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="on">
      <?php wp_nonce_field('tv_mobile_admin_login', 'tv_mobile_admin_login_nonce'); ?>
      <div class="field-wrap">
        <label for="log">Username or Email</label>
        <input id="log" name="log" type="text" inputmode="email" autocomplete="username" required placeholder="admin@example.com">
      </div>
      <div class="field-wrap">
        <label for="pwd">Password</label>
        <input id="pwd" name="pwd" type="password" autocomplete="current-password" required placeholder="••••••••">
      </div>
      <button type="submit">Sign In ?</button>
    </form>

    <p class="footer">Mobile &amp; tablet only · XOFLIX Admin</p>
  </div>
</body>
</html>