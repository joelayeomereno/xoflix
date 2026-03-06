<?php
/* Template Name: v24 Desktop Ultra-Cinematic */
// PHASE 2 UPGRADE: Hero, Pricing Alignment, Live Animations
// FIX: Hero Visibility (Tag Change) & Scroll Logic
// FIX: Nav Item "Channels" -> "Subscription"

// NOTE: Safely modularized: NO code logic refactored or rewritten.
// This file is now a small orchestrator that loads the original markup in parts,
// in the exact same order, to preserve identical output.

get_header();

$__streamos_parts_base = __DIR__ . '/desktop-home/parts';

require $__streamos_parts_base . '/styles.php';
require $__streamos_parts_base . '/nav.php';
require $__streamos_parts_base . '/hero.php';
require $__streamos_parts_base . '/stats.php';
require $__streamos_parts_base . '/pricing.php';
require $__streamos_parts_base . '/footer.php';
require $__streamos_parts_base . '/animation-script.php';

get_footer();
