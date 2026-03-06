<?php
if (!defined('ABSPATH')) { exit; }

require_once __DIR__ . '/payment-page/trait-tv-subscription-manager-public-traits-payment-page-subtrait-part-01.php';

trait TV_Manager_Public_Trait_Shortcode_Payment_Page {

    // [NEW] The Dedicated Lockdown Page (Modern Secure UI)
    // Refactor note: methods extracted into subtraits to reduce monolithic trait size.
    use TV_Subscription_Manager_Public_Traits_Payment_Page_Subtrait_Part_01;

}
