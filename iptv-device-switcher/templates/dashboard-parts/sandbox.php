<?php
/**
 * Sandbox.php
 * Handles Admin Impersonation logic.
 * Allows an admin to "View as User" to debug or support customers.
 */

if (!defined('ABSPATH')) { exit; }

// Check if Sandbox Mode is requested
if ( isset($_GET['tv_sandbox']) && isset($_GET['tv_user']) ) {

    // 1. Security Check: Ensure the *actual* logged-in user is an Administrator
    // We check this BEFORE switching the user context.
    if ( current_user_can('manage_options') ) {
        
        $target_user_id = intval($_GET['tv_user']);
        $original_admin_id = get_current_user_id();

        // 2. Validate Target
        if ( $target_user_id > 0 && $target_user_id !== $original_admin_id ) {
            
            // 3. Switch User Context
            // This overrides the global $current_user for the duration of this page load only.
            wp_set_current_user($target_user_id);
            
            // 4. Update Global Variables
            // Ensure WordPress globals reflect the switch so logic.php fetches the correct data.
            global $current_user;
            $current_user = wp_get_current_user();
            
            // Optional: Define a constant to flag that we are in sandbox mode
            if (!defined('TV_SANDBOX_ACTIVE')) {
                define('TV_SANDBOX_ACTIVE', true);
            }
        }
    }
}
