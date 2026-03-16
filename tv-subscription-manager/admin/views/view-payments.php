<?php
/**
 * File: tv-subscription-manager/admin/views/view-payments.php
 * Path: tv-subscription-manager/admin/views/view-payments.php
 * REFACTORED: Entry point for the modularized Payments view.
 * Ensures 100% feature parity with the original monolithic version.
 */

if (!defined('ABSPATH')) { exit; }

$payments_dir = TV_MANAGER_PATH . 'admin/views/payments/';

// 1. PHP Functions & Core Dependencies
require_once $payments_dir . '01-helpers.php';

// 2. Page Header
include $payments_dir . '02-header.php';

echo '<div class="tv-card">';

    // 3. Toolbar (Search, Filter, Currency, Date & Add Transaction button)
    include $payments_dir . '03-toolbar.php';

    // 4. Financial Statistics (USD / NGN)
    include $payments_dir . '04-stats.php';

    // 5. Main Transactions Table Grid
    include $payments_dir . '05-table.php';

    // 6. Navigation / Pagination
    include $payments_dir . '06-pagination.php';

echo '</div>'; // End container

// 7. All Modals (Fulfillment, Rejection, Manual Add, User Profile)
include $payments_dir . '07-modals.php';

// 8. View JavaScript (AJAX, Validation, Clipboard)
include $payments_dir . '08-scripts.php';
