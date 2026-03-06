<?php
/**
 * Template Name: XOFLIX - M3U / Website Parser
 * Path: iptv-device-switcher/templates/page-m3u-parser.php
 * Description: Parses M3U playlist URLs into Xtream Codes credentials with fixed SVG icons.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>M3U / Website Parser - XOFLIX</title>
    <?php wp_head(); ?>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0f4ff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }

        /* ---- Header ---- */
        .xp-header {
            background: #0f172a;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .xp-logo {
            font-size: 1.4rem;
            font-weight: 900;
            color: #fff;
            letter-spacing: -0.03em;
            text-decoration: none;
        }
        .xp-logo span { color: #6366f1; }
        .xp-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 100px;
            color: #cbd5e1;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .xp-back-btn:hover { background: rgba(255,255,255,0.15); color: #fff; }

        /* ---- Page Wrap ---- */
        .xp-wrap {
            max-width: 780px;
            width: 100%;
            margin: 48px auto;
            padding: 0 20px;
        }

        /* ---- Hero ---- */
        .xp-hero {
            text-align: center;
            margin-bottom: 40px;
        }
        .xp-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #eff6ff;
            color: #6366f1;
            border: 1px solid #c7d2fe;
            border-radius: 100px;
            padding: 6px 14px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 16px;
        }
        .xp-hero h1 {
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.03em;
            line-height: 1.15;
            margin-bottom: 12px;
        }
        .xp-hero p {
            color: #64748b;
            font-size: 1rem;
            max-width: 520px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* ---- Card ---- */
        .xp-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 4px 24px rgba(15,23,42,0.07);
            border: 1px solid #e2e8f0;
            padding: 32px;
            margin-bottom: 24px;
        }

        /* ---- Form ---- */
        .xp-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 8px;
        }
        .xp-label span {
            font-weight: 500;
            color: #94a3b8;
            font-size: 0.75rem;
            margin-left: 4px;
        }

        .xp-input-row {
            display: flex;
            gap: 10px;
        }

        .xp-input {
            flex: 1;
            padding: 13px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            font-size: 0.92rem;
            color: #0f172a;
            background: #f8fafc;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: 'SFMono-Regular', Consolas, monospace;
        }
        .xp-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
            background: #fff;
        }
        .xp-input::placeholder { color: #94a3b8; font-family: -apple-system, sans-serif; }

        .xp-parse-btn {
            padding: 13px 24px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            border: none;
            border-radius: 14px;
            font-weight: 800;
            font-size: 0.88rem;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(99,102,241,0.35);
        }
        .xp-parse-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(99,102,241,0.4); }
        .xp-parse-btn:active { transform: translateY(0); }
        .xp-parse-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        .xp-hint {
            margin-top: 15px;
            font-size: 0.75rem;
            color: #94a3b8;
            line-height: 1.5;
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        /* ---- Divider ---- */
        .xp-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
            color: #94a3b8;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .xp-divider::before, .xp-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        /* ---- Result Section ---- */
        #xp-result { display: none; }

        .xp-result-title {
            font-size: 0.85rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .xp-result-title .xp-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        /* Credentials Grid */
        .xp-creds-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .xp-cred-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 16px;
        }
        .xp-cred-label {
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .xp-cred-value {
            font-size: 0.92rem;
            font-weight: 700;
            color: #0f172a;
            font-family: 'SFMono-Regular', Consolas, monospace;
            word-break: break-all;
        }
        .xp-cred-value.name-field {
            color: #6366f1;
            font-size: 1rem;
            font-weight: 900;
            font-family: inherit;
        }

        /* Output Box */
        .xp-output-wrap {
            position: relative;
        }
        .xp-output-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .xp-output-box {
            width: 100%;
            padding: 16px;
            background: #0f172a;
            color: #a5f3fc;
            border: 1px solid #1e293b;
            border-radius: 14px;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', monospace;
            font-size: 0.8rem;
            line-height: 1.8;
            resize: none;
            outline: none;
            min-height: 140px;
        }

        .xp-copy-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            transition: all 0.18s;
        }
        .xp-copy-btn:hover { border-color: #6366f1; color: #6366f1; }
        .xp-copy-btn.copied { background: #f0fdf4; border-color: #86efac; color: #16a34a; }

        /* ---- Error ---- */
        #xp-error {
            display: none;
            margin-top: 12px;
            padding: 12px 16px;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 12px;
            color: #e11d48;
            font-size: 0.82rem;
            font-weight: 600;
        }

        /* ---- How it works ---- */
        .xp-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-top: 8px;
        }
        .xp-step {
            text-align: center;
            padding: 20px 16px;
        }
        .xp-step-icon {
            width: 52px;
            height: 52px;
            background: #eff6ff;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: #6366f1;
        }
        .xp-step h4 {
            font-size: 0.82rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }
        .xp-step p {
            font-size: 0.75rem;
            color: #64748b;
            line-height: 1.5;
        }

        @media (max-width: 540px) {
            .xp-card { padding: 22px 18px; }
            .xp-input-row { flex-direction: column; }
            .xp-parse-btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="xp-header">
    <a href="<?php echo esc_url( home_url('/') ); ?>" class="xp-logo">XO<span>FLIX</span>TV</a>
    <a href="<?php echo esc_url( home_url('/') ); ?>" class="xp-back-btn">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to Home
    </a>
</div>

<div class="xp-wrap">

    <!-- Hero -->
    <div class="xp-hero">
        <div class="xp-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            URL / M3U Converter
        </div>
        <h1>M3U &rarr; Xtream Codes<br>Smart Parser</h1>
        <p>Paste your M3U playlist URL below and instantly extract your Xtream Codes credentials - username, password, and host URL - ready for use in any IPTV application.</p>
    </div>

    <!-- Parser Card -->
    <div class="xp-card">
        <label class="xp-label" for="xp-m3u-input">
            Your M3U Playlist URL
            <span>e.g. http://host.tv:8080/get.php?username=abc&password=xyz&type=m3u</span>
        </label>

        <div class="xp-input-row">
            <input
                type="url"
                id="xp-m3u-input"
                class="xp-input"
                placeholder="http://yourserver.com:port/get.php?username=...&password=..."
                autocomplete="off"
                spellcheck="false"
            />
            <button class="xp-parse-btn" id="xp-parse-btn" onclick="xpParse()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                Parse URL
            </button>
        </div>

        <div id="xp-error"></div>
        <div class="xp-hint">
            <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="color:#10b981; margin-right:4px;"><polyline points="20 6 9 17 4 12"/></svg> Standard M3U Support</span>
            <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="color:#10b981; margin-right:4px;"><polyline points="20 6 9 17 4 12"/></svg> Xtream-Style Extraction</span>
            <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="color:#10b981; margin-right:4px;"><polyline points="20 6 9 17 4 12"/></svg> 100% Client-Side</span>
        </div>

        <!-- Result -->
        <div id="xp-result">
            <div class="xp-divider">Extracted Credentials</div>

            <div class="xp-result-title">
                <span class="xp-dot"></span>
                Xtream Codes Detected - Ready to use
            </div>

            <div class="xp-creds-grid">
                <div class="xp-cred-item" style="grid-column: 1 / -1;">
                    <div class="xp-cred-label">Playlist Name</div>
                    <div class="xp-cred-value name-field" id="xp-out-name">XOFLIX PORTAL</div>
                </div>
                <div class="xp-cred-item">
                    <div class="xp-cred-label">Username</div>
                    <div class="xp-cred-value" id="xp-out-user"></div>
                </div>
                <div class="xp-cred-item">
                    <div class="xp-cred-label">Password</div>
                    <div class="xp-cred-value" id="xp-out-pass"></div>
                </div>
                <div class="xp-cred-item" style="grid-column: 1 / -1;">
                    <div class="xp-cred-label">Host URL (Endpoint)</div>
                    <div class="xp-cred-value" id="xp-out-host"></div>
                </div>
            </div>

            <div class="xp-output-wrap">
                <div class="xp-output-label">
                    <label class="xp-label" style="margin:0;">Full Configuration Summary <span>Copy for your records</span></label>
                    <button class="xp-copy-btn" id="xp-copy-btn" onclick="xpCopy()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        Copy All
                    </button>
                </div>
                <textarea class="xp-output-box" id="xp-output-box" readonly rows="6"></textarea>
            </div>
        </div>
    </div>

    <!-- How it works -->
    <div class="xp-card">
        <div class="xp-result-title" style="margin-bottom:24px;">Simple Setup Process</div>
        <div class="xp-steps">
            <div class="xp-step">
                <div class="xp-step-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                </div>
                <h4>1. Paste Your URL</h4>
                <p>Paste the long M3U link provided by your service provider into the input box.</p>
            </div>
            <div class="xp-step">
                <div class="xp-step-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                </div>
                <h4>2. Run Analysis</h4>
                <p>Our smart engine extracts the embedded credentials and cleans the host address.</p>
            </div>
            <div class="xp-step">
                <div class="xp-step-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                </div>
                <h4>3. Direct Login</h4>
                <p>Copy the results and paste them into Smarters Pro, TiviMate, or any IPTV app.</p>
            </div>
        </div>
    </div>

</div>

<script>
function xpParse() {
    const input   = document.getElementById('xp-m3u-input');
    const errBox  = document.getElementById('xp-error');
    const result  = document.getElementById('xp-result');
    const btn     = document.getElementById('xp-parse-btn');
    const raw     = input.value.trim();

    errBox.style.display = 'none';
    result.style.display  = 'none';

    if (!raw) {
        errBox.textContent = 'Please paste your M3U URL first.';
        errBox.style.display = 'block';
        return;
    }

    let url;
    try { url = new URL(raw); } catch(e) {
        errBox.textContent = 'Invalid URL format. Ensure it begins with http:// or https://';
        errBox.style.display = 'block';
        return;
    }

    btn.disabled = true;
    const originalBtnHtml = btn.innerHTML;
    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Extracting...';

    setTimeout(() => {
        const params   = url.searchParams;
        const username = params.get('username') || params.get('user') || params.get('u') || '';
        const password = params.get('password') || params.get('pass') || params.get('p') || '';
        const host = url.protocol + '//' + url.host;

        if (!username && !password) {
            const parts = url.pathname.replace(/^\//, '').split('/');
            if (parts.length >= 2 && parts[0] && parts[1]) {
                renderResult('XOFLIX REBORN', parts[0], parts[1], host, raw);
            } else {
                errBox.textContent = 'No credentials detected in this URL. Please verify you are using a standard Xtream-compatible M3U link.';
                errBox.style.display = 'block';
            }
        } else {
            renderResult('XOFLIX REBORN', username, password, host, raw);
        }

        btn.disabled = false;
        btn.innerHTML = originalBtnHtml;
    }, 450);
}

function renderResult(name, user, pass, host, originalUrl) {
    document.getElementById('xp-out-name').textContent = name;
    document.getElementById('xp-out-user').textContent = user || ' ';
    document.getElementById('xp-out-pass').textContent = pass || ' ';
    document.getElementById('xp-out-host').textContent = host;

    const output = [
        '--- XOFLIX XTREAM CREDENTIALS ---',
        'NAME: ' + name,
        'USERNAME: ' + (user || ' '),
        'PASSWORD: ' + (pass || ' '),
        'HOST URL: ' + host,
        '',
        'SOURCE M3U:',
        originalUrl
    ].join('\n');

    document.getElementById('xp-output-box').value = output;
    document.getElementById('xp-result').style.display = 'block';
    document.getElementById('xp-result').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function xpCopy() {
    const box = document.getElementById('xp-output-box');
    const btn = document.getElementById('xp-copy-btn');

    navigator.clipboard.writeText(box.value).then(() => {
        btn.classList.add('copied');
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="color:#16a34a;"><polyline points="20 6 9 17 4 12"/></svg> Copied!';
        setTimeout(() => {
            btn.classList.remove('copied');
            btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Copy All';
        }, 2200);
    });
}

document.getElementById('xp-m3u-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') xpParse();
});
</script>

<?php wp_footer(); ?>
</body>
</html>