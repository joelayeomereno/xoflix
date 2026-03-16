<?php
if (!defined('ABSPATH')) { exit; }

/**
 * File: tv-subscription-manager/includes/services/class-tv-migration-service.php
 * Service to handle CSV imports and the "Claim Account" login flow.
 */
class TV_Migration_Service {

    const META_KEY_MIGRATED = '_tv_is_migrated';

    public function __construct() {
        // Hook into authentication: Priority 10 runs before standard WP password check (priority 20)
        add_filter('authenticate', array($this, 'handle_migrated_login_claim'), 10, 3);
    }

    /**
     * Intercept login to allow migrated users to set their password on first login.
     * * @param null|WP_User|WP_Error $user
     * @param string $username
     * @param string $password
     * @return null|WP_User|WP_Error
     */
    public function handle_migrated_login_claim($user, $username, $password) {
        // If previous filters already authenticated or failed, or if no password provided, skip.
        if ($user instanceof WP_User || is_wp_error($user) || empty($username) || empty($password)) {
            return $user;
        }

        // 1. Locate User by email or login
        $user_obj = get_user_by('email', $username);
        if (!$user_obj) {
            $user_obj = get_user_by('login', $username);
        }

        if (!$user_obj) {
            return $user; // User not found, pass to next filter
        }

        // 2. Check if this is a migrated, unclaimed account
        $is_migrated = get_user_meta($user_obj->ID, self::META_KEY_MIGRATED, true);

        if ($is_migrated) {
            // 3. CLAIM ACCOUNT LOGIC
            
            // Set the new password provided by the user immediately
            wp_set_password($password, $user_obj->ID);

            // Remove migration flag (Account is now claimed and standard security applies)
            delete_user_meta($user_obj->ID, self::META_KEY_MIGRATED);

            // Audit the claim
            if (class_exists('TV_Domain_Audit_Service')) {
                $audit = new TV_Domain_Audit_Service();
                $audit->log_event($user_obj->ID, 'Account Claimed', 'Migrated user claimed account via first login.');
            }

            // Return the user object, effectively logging them in
            return $user_obj;
        }

        return $user; // Normal user, pass to standard WP auth
    }

    /**
     * Process CSV Import
     * * @param string $file_path Path to the uploaded CSV file
     * @return array Import statistics and errors
     */
    public function process_csv_import($file_path) {
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return ['error' => 'File not found or unreadable.'];
        }

        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return ['error' => 'Could not open file.'];
        }

        // Read Header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return ['error' => 'Empty CSV file.'];
        }

        // Normalize Headers (remove BOM, lowercase, trim)
        $header = array_map(function($h) {
            return strtolower(trim(preg_replace('/[\xEF\xBB\xBF]/', '', $h)));
        }, $header);

        // Required column check
        if (!in_array('email', $header)) {
            fclose($handle);
            return ['error' => 'Missing required column: email'];
        }

        $idx = array_flip($header);
        $stats = ['imported' => 0, 'skipped' => 0, 'updated' => 0, 'errors' => []];

        while (($row = fgetcsv($handle)) !== false) {
            // Safety check for row length vs header length
            if (count($row) < 1) continue;

            $email = isset($idx['email']) && isset($row[$idx['email']]) ? sanitize_email($row[$idx['email']]) : '';
            
            if (empty($email) || !is_email($email)) {
                $stats['skipped']++;
                continue;
            }

            // Extract Fields safely
            $first_name = isset($idx['first_name']) && isset($row[$idx['first_name']]) ? sanitize_text_field($row[$idx['first_name']]) : '';
            $last_name  = isset($idx['last_name']) && isset($row[$idx['last_name']]) ? sanitize_text_field($row[$idx['last_name']]) : '';
            
            // Support 'name' or 'full name' column if split columns not provided
            if (empty($first_name) && isset($idx['name']) && isset($row[$idx['name']])) {
                $parts = explode(' ', sanitize_text_field($row[$idx['name']]), 2);
                $first_name = $parts[0];
                $last_name = isset($parts[1]) ? $parts[1] : '';
            }

            $phone   = isset($idx['phone']) && isset($row[$idx['phone']]) ? sanitize_text_field($row[$idx['phone']]) : '';
            $country = isset($idx['country']) && isset($row[$idx['country']]) ? strtoupper(sanitize_text_field($row[$idx['country']])) : '';
            $city    = isset($idx['city']) && isset($row[$idx['city']]) ? sanitize_text_field($row[$idx['city']]) : '';

            // Check if user exists
            $user_id = email_exists($email);
            
            if ($user_id) {
                // Update existing user meta only (safe update)
                // We DO NOT set the migration flag for existing users to avoid locking them out
                if ($phone) update_user_meta($user_id, 'billing_phone', $phone);
                if ($country) update_user_meta($user_id, 'billing_country', $country);
                if ($city) update_user_meta($user_id, 'billing_city', $city);
                
                $stats['updated']++;
            } else {
                // Create New Migrated User
                // Generate a random high-entropy password (user will overwrite this on first login via claim logic)
                $random_password = wp_generate_password(32, true);
                
                $userdata = [
                    'user_login' => $email, // Use email as username for consistency
                    'user_email' => $email,
                    'user_pass'  => $random_password,
                    'first_name' => $first_name,
                    'last_name'  => $last_name,
                    'display_name' => trim("$first_name $last_name") ?: $email,
                    'role'       => 'subscriber' // Default role
                ];

                $new_uid = wp_insert_user($userdata);

                if (is_wp_error($new_uid)) {
                    $stats['errors'][] = "Failed to create $email: " . $new_uid->get_error_message();
                    continue;
                }

                // Set Metadata
                if ($phone) update_user_meta($new_uid, 'billing_phone', $phone);
                if ($country) update_user_meta($new_uid, 'billing_country', $country);
                if ($city) update_user_meta($new_uid, 'billing_city', $city);
                
                // THE CRITICAL FLAG: This enables the "Claim Account" logic
                update_user_meta($new_uid, self::META_KEY_MIGRATED, 1);

                // Initialize wallet/sub logic if using StreamOS
                if (class_exists('StreamOS_User_Manager')) {
                    StreamOS_User_Manager::initialize_new_user($new_uid);
                }

                $stats['imported']++;
            }
        }

        fclose($handle);
        return $stats;
    }
}