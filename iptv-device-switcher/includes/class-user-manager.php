<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * StreamOS User Manager
 * Handles all business logic for user data, subscriptions, and wallet operations.
 * This is the "Brain" of the dashboard.
 */
class StreamOS_User_Manager {

    /**
     * Initialize a new user with default trial data.
     * UPGRADED: Now syncs directly with TV Manager SQL Tables.
     */
    public static function initialize_new_user( $user_id ) {
        global $wpdb;

        // 1. Set Default Balance (Meta)
        update_user_meta( $user_id, 'streamos_balance', '0.00' );

        // 2. Define Trial Parameters
        $trial_duration_hours = 24;
        $start_date = current_time( 'mysql' );
        $end_date   = date( 'Y-m-d H:i:s', strtotime( "+{$trial_duration_hours} hours" ) );

        // 3. LEGACY META SUPPORT (Keep for backward compatibility)
        update_user_meta( $user_id, 'streamos_plan_status', 'active' );
        update_user_meta( $user_id, 'streamos_plan_name', '24h Free Trial' );
        update_user_meta( $user_id, 'streamos_expiry', $end_date );
        update_user_meta( $user_id, 'streamos_quality', 'HD/4K' );

        // 4. CORE SQL SYNC (Fixes Admin Panel Visibility)
        $table_plans = $wpdb->prefix . 'tv_plans';
        $table_subs  = $wpdb->prefix . 'tv_subscriptions';
        $table_pay   = $wpdb->prefix . 'tv_payments';

        // Check if Tables Exist (Safety)
        if ( $wpdb->get_var("SHOW TABLES LIKE '$table_subs'") == $table_subs ) {
            
            // A. Find or Create a Trial Plan ID
            // We look for a plan with 'Trial' in the name or 1 day duration
            $plan_id = $wpdb->get_var("SELECT id FROM $table_plans WHERE name LIKE '%Trial%' OR duration_days = 1 LIMIT 1");
            
            // If no trial plan exists in DB, fallback to the first available plan or 0 (System Plan)
            if ( ! $plan_id ) {
                $plan_id = $wpdb->get_var("SELECT id FROM $table_plans ORDER BY price ASC LIMIT 1") ?: 0;
            }

            // B. Insert Subscription Record
            $wpdb->insert(
                $table_subs,
                array(
                    'user_id'    => $user_id,
                    'plan_id'    => $plan_id,
                    'start_date' => $start_date,
                    'end_date'   => $end_date,
                    'status'     => 'active' // Trial is auto-active
                ),
                array( '%d', '%d', '%s', '%s', '%s' )
            );
            $sub_id = $wpdb->insert_id;

            // C. Insert Payment Record (Zero Value Invoice)
            if ( $sub_id ) {
                $wpdb->insert(
                    $table_pay,
                    array(
                        'subscription_id' => $sub_id,
                        'user_id'         => $user_id,
                        'amount'          => 0.00,
                        'method'          => 'System Grant',
                        'transaction_id'  => 'TRIAL-' . strtoupper(uniqid()),
                        'status'          => 'completed',
                        'date'            => $start_date
                    ),
                    array( '%d', '%d', '%f', '%s', '%s', '%s', '%s' )
                );
            }
        }

        // 5. Create Initial Transaction Log (User Wallet History)
        self::add_transaction( $user_id, 0.00, 'activation', 'completed', '24h Free Trial Activated' );
    }

    /**
     * Get User Wallet Balance
     */
    public static function get_balance( $user_id ) {
        $bal = get_user_meta( $user_id, 'streamos_balance', true );
        return $bal ? number_format( (float)$bal, 2 ) : '0.00';
    }

    /**
     * Get Subscription Status & Details
     */
    public static function get_subscription_details( $user_id ) {
        $expiry = get_user_meta( $user_id, 'streamos_expiry', true );
        $status = get_user_meta( $user_id, 'streamos_plan_status', true );
        
        // Logic: Check if expired
        if ( $expiry && strtotime( $expiry ) < time() ) {
            $status = 'expired';
            // Auto-update meta if we catch them expired
            update_user_meta( $user_id, 'streamos_plan_status', 'expired' );
        }

        return [
            'status'    => ucfirst( $status ?: 'Inactive' ),
            'plan_name' => get_user_meta( $user_id, 'streamos_plan_name', true ) ?: 'No Plan',
            'expiry'    => $expiry ? date( 'M d, Y', strtotime( $expiry ) ) : 'N/A',
            'days_left' => $expiry ? self::calculate_days_left( $expiry ) : 0,
            'quality'   => get_user_meta( $user_id, 'streamos_quality', true ) ?: 'Standard'
        ];
    }

    /**
     * Helper: Calculate Human Readable Time Left
     */
    private static function calculate_days_left( $date_string ) {
        $target = strtotime( $date_string );
        $now    = time();
        $diff   = $target - $now;
        
        if ( $diff < 0 ) return 0;
        
        $days = floor( $diff / ( 60 * 60 * 24 ) );
        if ( $days < 1 ) {
            // Return hours if less than a day
            $hours = floor( $diff / ( 60 * 60 ) );
            return $hours . ' hours';
        }
        return $days . ' days';
    }

    /**
     * Add Transaction to History
     */
    public static function add_transaction( $user_id, $amount, $type, $status, $desc = '' ) {
        $history = get_user_meta( $user_id, 'streamos_transactions', true );
        if ( ! is_array( $history ) ) $history = [];

        // Prepend new transaction
        array_unshift( $history, [
            'id'     => 'TRX-' . strtoupper( substr( md5( uniqid() ), 0, 6 ) ),
            'date'   => current_time( 'mysql' ),
            'amount' => $amount,
            'type'   => $type, // 'deposit', 'purchase', 'activation'
            'status' => $status,
            'desc'   => $desc
        ] );

        // Keep last 50 only to save DB space
        $history = array_slice( $history, 0, 50 );
        
        update_user_meta( $user_id, 'streamos_transactions', $history );
    }

    /**
     * Get Transaction History
     */
    public static function get_transactions( $user_id, $limit = 5 ) {
        $history = get_user_meta( $user_id, 'streamos_transactions', true );
        if ( ! is_array( $history ) ) return [];
        return array_slice( $history, 0, $limit );
    }
}
