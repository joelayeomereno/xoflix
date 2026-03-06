<?php
if (!defined('ABSPATH')) { exit; }

require_once __DIR__ . '/tv-admin-users/trait-tv-subscription-manager-admin-classes-tv-admin-users-trait-part-01.php';
require_once __DIR__ . '/tv-admin-users/trait-tv-subscription-manager-admin-classes-tv-admin-users-trait-part-02.php';

class TV_Admin_Users extends TV_Admin_Base {
    // Refactor note: methods extracted into traits to reduce monolithic file size.
    use TV_Subscription_Manager_Admin_Classes_TV_Admin_Users_Trait_Part_01, TV_Subscription_Manager_Admin_Classes_TV_Admin_Users_Trait_Part_02;



    public function __construct($wpdb) {
        parent::__construct($wpdb);
        // Register hooks specific to Users here or in the main class
        add_action('admin_init', array($this, 'handle_impersonation_start'));
        add_action('admin_init', array($this, 'handle_impersonation_close'));
        add_action('admin_post_tv_sub_export', array($this, 'handle_csv_export'));
    }
}
