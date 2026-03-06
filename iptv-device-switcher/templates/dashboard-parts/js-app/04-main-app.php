<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * 04-main-app.php
 * ORCHESTRATOR: Loads the main dashboard logic.
 * * CRITICAL: We are now loading the CONSOLIDATED file (03-ip-tv-dashboard.php)
 * instead of the split parts (part-01, part-02...) to prevent white-screen errors.
 */

// 1. Constants & Helpers (Required for lookup)
require __DIR__ . '/04-main-app/01-constants-and-components.php';

// 2. Main Dashboard App (The file containing all your recent visual fixes)
// Ensure this file (03-ip-tv-dashboard.php) contains the full code provided in the previous step.
require __DIR__ . '/04-main-app/03-ip-tv-dashboard.php';

// 3. Bottom Nav & Bootstrap (Mounts the app to #root)
require __DIR__ . '/04-main-app/04-bottom-nav-and-bootstrap.php';
?>