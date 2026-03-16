<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Mobile Admin REST Layer
 *
 * FULL VERSION
 * - Soft Delete (Recycle Bin) for all deletes.
 * - Full field parity with Desktop Admin for Create/Update actions.
 */
require_once __DIR__ . '/traits/trait-tv-subscription-mobile-admin-includes-rest-mobile-admin-rest-trait-part-01.php';
require_once __DIR__ . '/traits/trait-tv-subscription-mobile-admin-includes-rest-mobile-admin-rest-trait-part-02.php';
require_once __DIR__ . '/traits/trait-tv-subscription-mobile-admin-includes-rest-mobile-admin-rest-trait-part-03.php';
require_once __DIR__ . '/traits/trait-tv-subscription-mobile-admin-includes-rest-mobile-admin-rest-trait-part-04.php';
require_once __DIR__ . '/traits/trait-tv-subscription-mobile-admin-includes-rest-mobile-admin-rest-trait-part-05.php';

class TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest {

    const NS = 'tv-admin/v2';
    // Refactor note: methods extracted into traits to reduce monolithic file size.
    use TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Part_01, TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Part_02, TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Part_03, TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Part_04, TV_Subscription_Mobile_Admin_Includes_Rest_Mobile_Admin_Rest_Trait_Part_05;


}