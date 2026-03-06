<?php
/**
 * Template Name: XOFLIX - IPTV Installation Guide
 * Path: iptv-device-switcher/templates/page-iptv-guide.php
 * Description: Interactive two-phase setup guide with device selection, HOT IPTV instructions, and MAC scanner with auto-formatting.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IPTV App Setup Guide - XOFLIX</title>
    <?php wp_head(); ?>
    <!-- OCR Library for MAC Scanning -->
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --slate-900: #0f172a;
            --slate-700: #334155;
            --slate-50: #f8fafc;
            --slate-500: #64748b;
            --slate-300: #cbd5e1;
            --slate-100: #f1f5f9;
            --white: #ffffff;
            --radius: 20px;
            --v24-ease: cubic-bezier(0.16, 1, 0.3, 1);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8fafc;
            color: var(--slate-700);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* ======= HEADER ======= */
        .ig-header {
            background: var(--slate-900);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(0,0,0,0.3);
        }
        .ig-logo { font-size: 1.4rem; font-weight: 900; color: #fff; text-decoration: none; letter-spacing: -0.03em; }
        .ig-logo span { color: var(--primary); }
        .ig-back {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15); border-radius: 100px;
            color: #cbd5e1; font-size: 0.8rem; font-weight: 600; text-decoration: none;
            transition: all 0.2s;
        }
        .ig-back:hover { background: rgba(255,255,255,0.15); color: #fff; }

        /* ======= HERO ======= */
        .ig-hero {
            background: linear-gradient(135deg, var(--slate-900) 0%, #1e1b4b 100%);
            color: #fff;
            text-align: center;
            padding: 72px 24px 60px;
            position: relative;
            overflow: hidden;
        }
        .ig-hero::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse at 50% 0%, rgba(99,102,241,0.3) 0%, transparent 70%);
        }
        .ig-hero-inner { position: relative; z-index: 1; max-width: 680px; margin: 0 auto; }
        .ig-hero h1 { font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 900; letter-spacing: -0.03em; line-height: 1.15; margin-bottom: 16px; }
        .ig-hero h1 span { color: #a5b4fc; }
        .ig-hero p { font-size: 1rem; color: #94a3b8; max-width: 520px; margin: 0 auto 28px; }

        /* ======= SELECTION GRID ======= */
        .ig-selector-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }
        .ig-selector-card {
            background: #fff;
            border: 1px solid var(--slate-200);
            border-radius: 24px;
            padding: 40px 32px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s var(--v24-ease);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }
        .ig-selector-card:hover {
            transform: translateY(-8px);
            border-color: var(--primary);
            box-shadow: 0 30px 60px -15px rgba(99,102,241,0.15);
        }
        .ig-selector-card .icon-box {
            width: 80px;
            height: 80px;
            background: var(--slate-50);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--slate-900);
            transition: all 0.3s;
        }
        .ig-selector-card:hover .icon-box {
            background: var(--primary);
            color: #fff;
            transform: scale(1.1) rotate(-3deg);
            box-shadow: 0 10px 20px -5px rgba(99,102,241,0.4);
        }
        .ig-selector-card span {
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--slate-900);
            letter-spacing: -0.01em;
        }

        /* ======= PHASE TOGGLES ======= */
        .phase-content { display: none; animation: igFadeIn 0.4s var(--v24-ease) forwards; }
        .phase-content.active { display: block; }
        @keyframes igFadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* ======= INSTRUCTIONS UI ======= */
        .ig-wrap { max-width: 1100px; margin: 0 auto; padding: 40px 20px 100px; }
        
        .ig-instruction-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 40px;
            gap: 20px;
        }
        .ig-btn-back-devices {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px; background: #fff; border: 2px solid var(--slate-200);
            border-radius: 100px; color: var(--slate-700); font-size: 0.9rem; font-weight: 800;
            text-decoration: none; transition: all 0.2s; cursor: pointer;
        }
        .ig-btn-back-devices:hover { border-color: var(--primary); color: var(--primary); transform: translateX(-4px); }

        .ig-device-card-standalone {
            background: #fff; border: 1px solid var(--slate-200); border-radius: var(--radius);
            overflow: hidden; box-shadow: 0 10px 40px -15px rgba(0,0,0,0.05);
            max-width: 800px; margin: 0 auto 48px;
        }
        .ig-device-header { padding: 40px; display: flex; align-items: center; gap: 24px; border-bottom: 1px solid var(--slate-100); }
        .ig-device-icon-box { width: 72px; height: 72px; border-radius: 20px; display: flex; align-items: center; justify-content: center; }
        .ig-device-body { padding: 40px; }
        
        .ig-paid-notice {
            background: #fef2f2; border: 1px solid #fee2e2; border-radius: 14px;
            padding: 18px; margin-bottom: 32px; display: flex; gap: 14px; align-items: center;
            color: #b91c1c; font-size: 0.9rem; font-weight: 700;
        }
        .ig-paid-notice svg { flex-shrink: 0; color: #ef4444; }

        .ig-steps-list { list-style: none; }
        .ig-step-item { display: flex; gap: 20px; align-items: flex-start; margin-bottom: 32px; }
        .ig-step-num { 
            width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 0.85rem; font-weight: 800; background: var(--primary); color: #fff; 
            box-shadow: 0 4px 10px rgba(99,102,241,0.3);
        }
        .ig-step-text { font-size: 1rem; color: var(--slate-700); font-weight: 500; }
        .ig-step-text strong { color: var(--slate-900); font-weight: 800; }

        /* ======= MAC SCANNER SECTION ======= */
        .ig-mac-section {
            background: #f8fafc; border: 1px solid var(--slate-200); border-radius: var(--radius);
            padding: 48px 40px; margin-top: 40px; text-align: center;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }
        .ig-mac-title { font-size: 1.4rem; font-weight: 900; color: var(--slate-900); margin-bottom: 12px; }
        .ig-mac-desc { font-size: 1rem; color: var(--slate-500); margin-bottom: 32px; max-width: 500px; margin-left: auto; margin-right: auto; }
        
        .ig-mac-input-wrap {
            display: flex; gap: 12px; max-width: 500px; margin: 0 auto;
        }
        .ig-mac-input {
            flex: 1; padding: 18px 24px; border-radius: 16px; border: 2px solid var(--slate-200);
            font-family: ui-monospace, SFMono-Regular, monospace; font-size: 1.2rem; font-weight: 800; text-align: center;
            text-transform: uppercase; outline: none; transition: 0.2s; background: #fff;
            color: var(--primary);
        }
        .ig-mac-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99,102,241,0.1); }
        
        .ig-btn-scan {
            width: 60px; height: 60px; background: white; border: 2px solid var(--slate-200);
            border-radius: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center;
            color: var(--slate-700); transition: 0.2s;
        }
        .ig-btn-scan:hover { border-color: var(--primary); color: var(--primary); background: #f5f3ff; }

        .ig-btn-send {
            margin-top: 24px; padding: 18px 48px; background: #25D366; color: white;
            border-radius: 16px; font-weight: 800; border: none; cursor: pointer;
            display: inline-flex; align-items: center; gap: 12px; transition: 0.3s;
            box-shadow: 0 10px 20px -5px rgba(37, 211, 102, 0.4);
            font-size: 1rem;
        }
        .ig-btn-send:hover { transform: translateY(-3px); box-shadow: 0 15px 30px -5px rgba(37, 211, 102, 0.5); background: #21ba58; }

        /* ======= FAQ DESIGN FIX ======= */
        .ig-section-title { font-size: 1.3rem; font-weight: 900; color: var(--slate-900); margin: 60px 0 24px; border-left: 5px solid var(--primary); padding-left: 15px; }
        .ig-faq-item { background: #fff; border: 1px solid var(--slate-200); border-radius: 18px; margin-bottom: 16px; overflow: hidden; transition: 0.3s; }
        .ig-faq-item:hover { border-color: var(--primary); box-shadow: 0 10px 20px -10px rgba(0,0,0,0.05); }
        .ig-faq-q { padding: 22px 28px; font-weight: 800; font-size: 1rem; color: var(--slate-900); cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .ig-faq-a { max-height: 0; overflow: hidden; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); font-size: 0.95rem; color: var(--slate-500); padding: 0 28px; font-weight: 500; line-height: 1.7; }
        .ig-faq-item.open .ig-faq-a { max-height: 500px; padding: 0 28px 28px; }
        .ig-faq-item.open .ig-faq-q { color: var(--primary); }
        .ig-faq-item.open .ig-faq-q svg { transform: rotate(180deg); color: var(--primary); }

        /* ======= SUPPORT ======= */
        .ig-support { background: var(--slate-900); border-radius: 2.5rem; padding: 60px 40px; text-align: center; color: #fff; position: relative; overflow: hidden; }
        .ig-support::after { content: ''; position: absolute; bottom: 0; right: 0; width: 300px; height: 300px; background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 70%); pointer-events: none; }
        .ig-support h3 { font-size: 1.6rem; font-weight: 900; margin-bottom: 12px; letter-spacing: -0.02em; }
        .ig-support p { font-size: 1.05rem; opacity: 0.8; margin-bottom: 36px; max-width: 600px; margin-left: auto; margin-right: auto; }
        
        /* Scanner Modal */
        #scanner-modal { position: fixed; inset: 0; z-index: 10005; background: #000; display: none; flex-direction: column; align-items: center; justify-content: center; }
        #scanner-video { width: 100%; max-width: 600px; height: auto; border-radius: 12px; }
        .scanner-controls { position: absolute; bottom: 40px; display: flex; gap: 20px; }

        @media (max-width: 640px) {
            .ig-hero { padding: 48px 24px; }
            .ig-selector-grid { grid-template-columns: 1fr; }
            .ig-device-header { padding: 32px 24px; flex-direction: column; text-align: center; }
            .ig-mac-input-wrap { flex-direction: column; }
            .ig-btn-scan { width: 100%; height: 50px; }
            .ig-support { padding: 40px 24px; border-radius: 2rem; }
        }
    </style>
</head>
<body>

<header class="ig-header">
    <a href="<?php echo esc_url( home_url('/') ); ?>" class="ig-logo">XO<span>FLIX</span>TV</a>
    <a href="<?php echo esc_url( home_url('/') ); ?>" class="ig-back">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to Home
    </a>
</header>

<section class="ig-hero">
    <div class="ig-hero-inner">
        <div class="ig-hero-badge" id="phase-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            <span id="badge-text">Step 1: Hardware Selection</span>
        </div>
        <h1 id="hero-title">Installation Guide</h1>
        <p id="hero-desc">Choose your streaming hardware to access specific activation instructions.</p>
    </div>
</section>

<div class="ig-wrap">

    <!-- PHASE 1: DEVICE SELECTION -->
    <div id="phase-selection" class="phase-content active">
        <div class="ig-selector-grid">
            <div class="ig-selector-card" onclick="showGuide('smart-tv')">
                <div class="icon-box">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
                </div>
                <span>Smart TV (LG/Samsung)</span>
            </div>
            <div class="ig-selector-card" onclick="showGuide('firestick')">
                <div class="icon-box">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.448-3.321.5-4.5.385.503.743 1.143.5 2.5a2.5 2.5 0 0 1 5 0c0 3.866-3 7-3 7s3-3.134 3-7c0-2-1.5-3.5-3.5-3.5S8 3.5 8 5.5c0 1.38.5 2 1 3 .5 1 .5 1.5.5 3a2.5 2.5 0 0 1-2.5 2.5c-.5 0-1.5-.5-1.5-2.5 0-2.5 2.5-4.5 2.5-4.5"/></svg>
                </div>
                <span>Amazon Firestick</span>
            </div>
            <div class="ig-selector-card" onclick="showGuide('android')">
                <div class="icon-box">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                </div>
                <span>Android Device</span>
            </div>
            <div class="ig-selector-card" onclick="showGuide('ios')">
                <div class="icon-box">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20.94c1.97 0 3.07-1.74 3.07-3.07 0-1.03-.51-1.71-1.37-2.12.86-.41 1.37-1.09 1.37-2.12 0-1.33-1.1-3.07-3.07-3.07s-3.07 1.74-3.07 3.07c0 1.03.51 1.71 1.37 2.12-.86.41-1.37 1.09-1.37 2.12 0 1.33 1.1 3.07 3.07 3.07z"/><path d="M12 10.5c0-1.33 1.1-3.07 3.07-3.07"/><path d="M12 10.5c0-1.33-1.1-3.07-3.07-3.07"/></svg>
                </div>
                <span>Apple iOS / TV</span>
            </div>
            <div class="ig-selector-card" onclick="showGuide('pc-mac')">
                <div class="icon-box">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <span>Windows / Mac</span>
            </div>
        </div>
    </div>

    <!-- PHASE 2: INSTRUCTIONS -->
    <div id="phase-instructions" class="phase-content">
        <div class="ig-instruction-header">
            <button class="ig-btn-back-devices" onclick="showSelection()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back to DEVICES
            </button>
            <div style="font-weight: 800; color: var(--slate-900); font-size: 1.1rem; text-transform: uppercase; letter-spacing: 0.05em;" id="active-device-name">Device Name</div>
        </div>

        <!-- Dynamic Device Container -->
        <div id="device-details-container"></div>

        <!-- MAC ADDRESS SCANNER SECTION -->
        <div id="mac-scanner-wrap" style="display:none;">
            <div class="ig-mac-section">
                <div style="width:72px; height:72px; background:rgba(37, 211, 102, 0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 24px; color:#25D366;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <h3 class="ig-mac-title">Remote Line Activation</h3>
                <p class="ig-mac-desc">Submit your TV's MAC Address to our technical desk. An administrator will manually provision the playlist to your hardware for instant access.</p>
                
                <div class="ig-mac-input-wrap">
                    <input type="text" id="mac-address-input" class="ig-mac-input" placeholder="00:00:00:00:00:00" maxlength="17">
                    <button class="ig-btn-scan" onclick="startScanner()" title="Scan using Camera">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    </button>
                </div>
                
                <button class="ig-btn-send" onclick="sendMacToWA()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.536 0 1.52 1.115 2.988 1.264 3.186.149.198 2.19 3.361 5.27 5.013 2.19 1.174 2.19.782 2.587.73.992-.123 1.76-1.015 1.76-1.93 0-.916-.149-1.612-.298-1.687zM12.001 22A10.001 10.001 0 0 1 3.55 6.72L2.094 1.201l5.632 1.486A10.001 10.001 0 1 1 12 22zm0-22C6.477 0 2 4.477 2 10c0 1.83.468 3.548 1.284 5.04L2 22l7.086-1.254A9.957 9.957 0 0 0 12 20c5.523 0 10-4.477 10-10S17.523 0 12 0z"/></svg>
                    Send to Technical Activation
                </button>
            </div>
        </div>

        <!-- FAQ -->
        <div class="ig-section-title">Technical Support FAQ</div>
        <div class="ig-faq">
            <div class="ig-faq-item">
                <div class="ig-faq-q" onclick="igToggle(this)">
                    What happens after I send my MAC address?
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="ig-faq-a">Our system routes your MAC to an administrator. We then link our server-side playlist to your hardware ID. Once done, you simply reload the app on your TV to begin streaming. No login keys required.</div>
            </div>
            <div class="ig-faq-item">
                <div class="ig-faq-q" onclick="igToggle(this)">
                    Activation Timeframe & Confirmation
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="ig-faq-a">Admin-led activations are typically finalized within 15-20 minutes during operational hours. You will receive a direct WhatsApp notification as soon as the playlist injection is complete.</div>
            </div>
            <div class="ig-faq-item">
                <div class="ig-faq-q" onclick="igToggle(this)">
                    Separation of App vs Service
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="ig-faq-a">Please note that **HOT IPTV** is a media player shell. Our service provides the content. The app requires a one-time activation fee to the developers after its trial - this is separate from your streaming subscription.</div>
            </div>
        </div>

        <!-- Support -->
        <div class="ig-support">
            <h3>Direct Assistance</h3>
            <p>If you're facing hardware limitations or network issues, our technical team can perform a remote setup via WhatsApp.</p>
            <div class="ig-support-btns">
                <a href="#" id="main-wa-link" target="_blank" rel="noopener" class="ig-support-btn ig-btn-white">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.536 0 1.52 1.115 2.988 1.264 3.186.149.198 2.19 3.361 5.27 5.013 2.19 1.174 2.19.782 2.587.73.992-.123 1.76-1.015 1.76-1.93 0-.916-.149-1.612-.298-1.687zM12.001 22A10.001 10.001 0 0 1 3.55 6.72L2.094 1.201l5.632 1.486A10.001 10.001 0 1 1 12 22zm0-22C6.477 0 2 4.477 2 10c0 1.83.468 3.548 1.284 5.04L2 22l7.086-1.254A9.957 9.957 0 0 0 12 20c5.523 0 10-4.477 10-10S17.523 0 12 0z"/></svg>
                    WhatsApp Technical Desk
                </a>
            </div>
        </div>
    </div>

</div>

<!-- SCANNER OVERLAY -->
<div id="scanner-modal">
    <video id="scanner-video" autoplay playsinline></video>
    <div style="color:white; margin-top:20px; font-weight:700;" id="scanner-status">Align MAC address in frame...</div>
    <div class="scanner-controls">
        <button class="ig-btn-back-devices" onclick="stopScanner()">Cancel</button>
        <button class="ig-btn-back-devices" id="snap-btn" style="background:var(--primary); color:white; border:none;">Capture MAC</button>
    </div>
    <canvas id="scanner-canvas" style="display:none;"></canvas>
</div>

<!-- DATA STORE -->
<script id="device-data" type="application/json">
{
    "smart-tv": {
        "name": "LG / Samsung / HiSense TV",
        "app": "HOT IPTV",
        "paid": true,
        "showMac": true,
        "icon": "tv",
        "steps": [
            "Launch your TV's App Store (LG Content Store, Samsung Apps, or Vidaa).",
            "Search for <strong>HOT IPTV</strong> and select 'Install'.",
            "Launch the application. Your unique <strong>MAC Address</strong> will be displayed prominently on the home screen.",
            "Copy this MAC address carefully or use the camera scanner tool provided below."
        ]
    },
    "firestick": {
        "name": "Amazon Firestick",
        "app": "IPTV Smarters Pro",
        "icon": "flame",
        "steps": [
            "Navigate to Firestick Settings &rarr; My Fire TV &rarr; Developer Options &rarr; Enable 'Apps from Unknown Sources'.",
            "Download the <strong>Downloader</strong> application from the official Amazon Store.",
            "Open Downloader and enter the source code: <code>smarters.pro</code>.",
            "Once installed, login using <strong>Xtream Codes API</strong> with your dashboard credentials."
        ]
    },
    "android": {
        "name": "Android Device",
        "app": "IPTV Smarters Pro",
        "icon": "android",
        "steps": [
            "Download <strong>IPTV Smarters Pro</strong> from the Google Play Store.",
            "Select <strong>Login with Xtream Codes API</strong> upon launch.",
            "Input your credentials (User, Pass, Host URL) found in your member area.",
            "Initiate the user sync to begin streaming."
        ]
    },
    "ios": {
        "name": "Apple iOS / TV",
        "app": "IPTV Smarters Pro",
        "icon": "smartphone",
        "steps": [
            "Get <strong>IPTV Smarters Pro</strong> from the Apple App Store.",
            "Choose <strong>Xtream Codes API</strong> as your connection protocol.",
            "Enter your secure keys precisely as shown in your dashboard.",
            "Allow the content library to initialize."
        ]
    },
    "pc-mac": {
        "name": "PC / Mac",
        "app": "VLC / Smarters",
        "icon": "monitor",
        "steps": [
            "Download <strong>IPTV Smarters Desktop</strong> or <strong>VLC Media Player</strong>.",
            "Navigate to the connection settings and select Xtream API.",
            "Fill in your assigned credentials.",
            "Access your 4K content library instantly."
        ]
    }
}
</script>

<script>
const deviceData = JSON.parse(document.getElementById('device-data').textContent);
const waNumber = "<?php echo esc_js(get_option('tv_support_whatsapp', '')); ?>".replace(/[^0-9]/g, '');
let stream = null;

// Initial Setup
document.getElementById('main-wa-link').href = `https://wa.me/${waNumber}?text=${encodeURIComponent('Hi, I need assistance with my IPTV device setup.')}`;

// --- MAC ADDRESS AUTO-FORMATTER ---
document.getElementById('mac-address-input').addEventListener('input', function(e) {
    let value = e.target.value.toUpperCase().replace(/[^0-9A-F]/g, '');
    let parts = value.match(/.{1,2}/g);
    if (parts) { e.target.value = parts.join(':').slice(0, 17); }
    else { e.target.value = value; }
});

function showGuide(id) {
    const data = deviceData[id];
    if(!data) return;

    document.getElementById('phase-selection').classList.remove('active');
    document.getElementById('phase-instructions').classList.add('active');
    
    document.getElementById('badge-text').innerText = "Step 2: Device Setup";
    document.getElementById('hero-title').innerHTML = `Setup for <span>${data.name}</span>`;
    document.getElementById('hero-desc').innerText = `Follow the verified process to activate ${data.app} on your hardware.`;
    document.getElementById('active-device-name').innerText = data.name;

    let stepsHtml = data.steps.map((step, i) => `
        <li class="ig-step-item">
            <span class="ig-step-num">${i+1}</span>
            <span class="ig-step-text">${step}</span>
        </li>
    `).join('');

    let paidBadge = data.paid ? `<div class="ig-paid-notice">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        <span>Important: <strong>${data.app}</strong> is a premium third-party application. Licensing is independent of your subscription.</span>
    </div>` : '';

    document.getElementById('device-details-container').innerHTML = `
        <div class="ig-device-card-standalone animate-in">
            <div class="ig-device-header">
                <div class="ig-device-icon-box" style="background:var(--slate-100); color:var(--primary);">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">${getIconSvg(data.icon)}</svg>
                </div>
                <div>
                    <div style="font-weight:900; font-size:1.2rem; color:var(--slate-900); letter-spacing:-0.01em;">${data.name}</div>
                    <div style="font-size:0.85rem; color:var(--slate-500); font-weight:600;">Technical Implementation Guide</div>
                </div>
            </div>
            <div class="ig-device-body">
                ${paidBadge}
                <ul class="ig-steps-list">${stepsHtml}</ul>
            </div>
        </div>
    `;

    document.getElementById('mac-scanner-wrap').style.display = data.showMac ? 'block' : 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showSelection() {
    document.getElementById('phase-instructions').classList.remove('active');
    document.getElementById('phase-selection').classList.add('active');
    document.getElementById('badge-text').innerText = "Step 1: Hardware Selection";
    document.getElementById('hero-title').innerText = "Installation Guide";
    document.getElementById('hero-desc').innerText = "Choose your streaming hardware to access specific activation instructions.";
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function getIconSvg(type) {
    const icons = {
        'android': '<rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>',
        'smartphone': '<path d="M12 20.94c1.97 0 3.07-1.74 3.07-3.07 0-1.03-.51-1.71-1.37-2.12.86-.41 1.37-1.09 1.37-2.12 0-1.33-1.1-3.07-3.07-3.07s-3.07 1.74-3.07 3.07c0 1.03.51 1.71 1.37 2.12-.86.41-1.37 1.09-1.37 2.12 0 1.33 1.1 3.07 3.07 3.07z"/><path d="M12 10.5c0-1.33 1.1-3.07 3.07-3.07"/><path d="M12 10.5c0-1.33-1.1-3.07-3.07-3.07"/>',
        'tv': '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/>',
        'flame': '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.448-3.321.5-4.5.385.503.743 1.143.5 2.5a2.5 2.5 0 0 1 5 0c0 3.866-3 7-3 7s3-3.134 3-7c0-2-1.5-3.5-3.5-3.5S8 3.5 8 5.5c0 1.38.5 2 1 3 .5 1 .5 1.5.5 3a2.5 2.5 0 0 1-2.5 2.5c-.5 0-1.5-.5-1.5-2.5 0-2.5 2.5-4.5 2.5-4.5"/>',
        'monitor': '<rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>'
    };
    return icons[type] || icons['tv'];
}

/* SCANNER LOGIC */
async function startScanner() {
    const modal = document.getElementById('scanner-modal');
    const video = document.getElementById('scanner-video');
    const status = document.getElementById('scanner-status');
    const snapBtn = document.getElementById('snap-btn');
    modal.style.display = 'flex';
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        video.srcObject = stream;
        snapBtn.onclick = async () => {
            status.innerText = "Processing Data...";
            const canvas = document.getElementById('scanner-canvas');
            canvas.width = video.videoWidth; canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            const { data: { text } } = await Tesseract.recognize(canvas.toDataURL(), 'eng');
            const macRegex = /([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})/;
            const match = text.match(macRegex);
            if(match) { document.getElementById('mac-address-input').value = match[0].toUpperCase(); stopScanner(); }
            else { status.innerText = "MAC not recognized. Please align and capture again."; }
        };
    } catch(e) { alert("Camera permissions required for scanning."); stopScanner(); }
}

function stopScanner() { if(stream) stream.getTracks().forEach(t => t.stop()); document.getElementById('scanner-modal').style.display = 'none'; }

function sendMacToWA() {
    const mac = document.getElementById('mac-address-input').value.trim();
    if(!mac) { alert("Please provide a valid MAC address."); return; }
    const msg = `Hello, I would like to set up my Smart TV access.\n\nMAC ADDRESS: ${mac}\n\nPlease add the playlist to this MAC address for my trial or active subscription.`;
    window.open(`https://wa.me/${waNumber}?text=${encodeURIComponent(msg)}`, '_blank');
}

function igToggle(el) { el.closest('.ig-faq-item').classList.toggle('open'); }
</script>

<?php wp_footer(); ?>
</body>
</html>