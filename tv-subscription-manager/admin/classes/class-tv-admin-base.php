<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * File: tv-subscription-manager/admin/classes/class-tv-admin-base.php
 * Path: /tv-subscription-manager/admin/classes/class-tv-admin-base.php
 *
 * Base Admin Class
 * Handles shared resources, logging, and SECURITY (Delete Verification).
 */
class TV_Admin_Base {

    protected $wpdb;
    protected $table_plans;
    protected $table_subs;
    protected $table_payments;
    protected $table_coupons;
    protected $table_methods;
    protected $table_logs;
    protected $table_sports;
    protected $table_recycle;

    public function __construct( $wpdb ) {
        $this->wpdb           = $wpdb;
        $this->table_plans    = $wpdb->prefix . 'tv_plans';
        $this->table_subs     = $wpdb->prefix . 'tv_subscriptions';
        $this->table_payments = $wpdb->prefix . 'tv_payments';
        $this->table_coupons  = $wpdb->prefix . 'tv_coupons';
        $this->table_methods  = $wpdb->prefix . 'tv_payment_methods';
        $this->table_logs     = $wpdb->prefix . 'tv_activity_logs';
        $this->table_sports   = $wpdb->prefix . 'tv_sports_events';
        $this->table_recycle  = $wpdb->prefix . 'tv_recycle_bin';
    }

    /**
     * Server-side enforcement for delete verification.
     */
    protected function tv_verify_delete_token_from_request() : bool {
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }

        $user_id   = (int) get_current_user_id();
        $req_token = '';

        if ( isset( $_REQUEST['tv_del_token'] ) ) {
            $req_token = sanitize_text_field( wp_unslash( $_REQUEST['tv_del_token'] ) );
        }

        // If token missing, fail immediately
        if ( empty( $req_token ) ) {
            return false;
        }

        $key    = 'tv_delete_verify_' . $user_id;
        $stored = (string) get_transient( $key );

        if ( empty( $stored ) ) {
            return false;
        }

        $ok = hash_equals( $stored, $req_token );

        if ( $ok ) {
            delete_transient( $key );
        }

        return $ok;
    }

    protected function tv_require_delete_verification_or_notice() : bool {
        if ( $this->tv_verify_delete_token_from_request() ) {
            return true;
        }

        $this->show_notice( 'Delete verification required. Please retry the delete action and complete the 4-digit verification.', 'error' );
        return false;
    }

    /**
     * Soft-delete helper (Recycle Bin)
     * Includes Hard Delete Fallback if Recycle Bin fails.
     */
    protected function recycle_bin_soft_delete( string $entity_type, string $table, int $entity_id, string $pk = 'id', array $where_extra = array() ) : bool {
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }

        $entity_id = (int) $entity_id;
        if ( $entity_id <= 0 ) {
            return false;
        }

        // 1. Build Query safely
        $where           = array_merge( array( $pk => $entity_id ), $where_extra );
        $where_sql_parts = array();
        $params          = array();

        foreach ( $where as $col => $val ) {
            if ( ! is_string( $col ) || ! preg_match( '/^[a-zA-Z0-9_]+$/', $col ) ) {
                return false;
            }
            $where_sql_parts[] = "`{$col}` = %s";
            $params[]          = (string) $val;
        }

        if ( isset( $where[ $pk ] ) ) {
            $pk_index = array_search( "`{$pk}` = %s", $where_sql_parts, true );
            if ( $pk_index !== false ) {
                $where_sql_parts[ $pk_index ] = "`{$pk}` = %d";
                $params[ $pk_index ]          = (int) $entity_id;
            }
        }

        $where_sql = implode( ' AND ', $where_sql_parts );
        
        // 2. Fetch Data to backup
        $row = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$table} WHERE {$where_sql} LIMIT 1", $params ), ARRAY_A );

        if ( ! $row ) {
            return false;
        }

        $deleted_at = current_time( 'mysql' );
        $expires_at = gmdate( 'Y-m-d H:i:s', time() + ( 7 * DAY_IN_SECONDS ) );
        
        // 3. Serialize (Robust encoding)
        $payload = wp_json_encode( $row );
        if ( empty( $payload ) ) {
            $payload = json_encode( $row, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_IGNORE );
        }

        // 4. Try Insert into Recycle Bin
        $inserted = $this->wpdb->insert( $this->table_recycle, array(
            'entity_type'  => sanitize_text_field( $entity_type ),
            'entity_table' => sanitize_text_field( $table ),
            'entity_pk'    => sanitize_text_field( $pk ),
            'entity_id'    => $entity_id,
            'payload'      => $payload,
            'deleted_at'   => $deleted_at,
            'deleted_by'   => (int) get_current_user_id(),
            'expires_at'   => $expires_at,
            'status'       => 'deleted',
        ) );

        // 5. HARD DELETE FALLBACK
        // If insert failed (e.g. table missing), proceed with delete anyway to ensure "effectiveness".
        if ( ! $inserted ) {
            // Optional: Log failure internally
            error_log("TV Manager: Recycle bin insert failed for {$entity_type} ID {$entity_id}. Proceeding with hard delete.");
        }

        // 6. Perform Real Delete
        $deleted = $this->wpdb->delete( $table, $where );
        
        return (bool) $deleted;
    }

    /**
     * Restore item from Recycle Bin
     */
    protected function recycle_bin_restore( int $recycle_id ) : bool {
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }

        $recycle_id = (int) $recycle_id;
        if ( $recycle_id <= 0 ) {
            return false;
        }

        $item = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$this->table_recycle} WHERE id = %d AND status = 'deleted' LIMIT 1", $recycle_id ) );

        if ( ! $item ) {
            return false;
        }

        if ( ! empty( $item->expires_at ) && strtotime( $item->expires_at ) < time() ) {
            return false;
        }

        $payload = json_decode( (string) $item->payload, true );
        if ( ! is_array( $payload ) || empty( $payload ) ) {
            return false;
        }
        
        // Remove DB-auto fields that might conflict
        unset( $payload['attempted_at'] );

        $table     = (string) $item->entity_table;
        $pk        = ! empty( $item->entity_pk ) ? (string) $item->entity_pk : 'id';
        $entity_id = (int) $item->entity_id;
        
        $payload[ $pk ] = $entity_id;

        $exists = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT {$pk} FROM {$table} WHERE {$pk} = %d LIMIT 1", $entity_id ) );

        if ( ! $exists ) {
            $inserted = $this->wpdb->insert( $table, $payload );
            if ( ! $inserted ) {
                return false;
            }
        }

        $this->wpdb->update( $this->table_recycle, array(
            'status'      => 'restored',
            'restored_at' => current_time( 'mysql' ),
            'restored_by' => (int) get_current_user_id(),
        ), array( 'id' => $recycle_id ) );

        return true;
    }

    /**
     * Returns the correct base URL for admin page redirects and links.
     *
     * When rendering inside the standalone /manager route (STREAMOS_MANAGER_CONTEXT is defined),
     * returns the frontend manager page URL so redirects stay on that page instead of
     * sending the user to /wp-admin/admin.php.
     *
     * Usage (replaces `admin_url('admin.php')`):
     *   wp_redirect( add_query_arg( $args, $this->admin_base_url() ) );
     */
    protected function admin_base_url() : string {
        if ( defined( 'STREAMOS_MANAGER_CONTEXT' ) ) {
            return home_url( '/manager' );
        }
        return admin_url( 'admin.php' );
    }

    protected function log_event( $action, $details = '' ) {
        $this->wpdb->insert( $this->table_logs, array(
            'user_id'    => get_current_user_id(),
            'action'     => $action,
            'details'    => $details,
            'ip_address' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '0.0.0.0',
            'date'       => current_time( 'mysql' ),
        ) );
    }

    protected function show_notice( $msg, $type = 'success' ) {
        $type      = in_array( $type, array( 'success', 'error', 'warning', 'info' ), true ) ? $type : 'success';
        $color_var = ( $type === 'error' ) ? '--tv-danger' : '--tv-primary';
        
        echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible" style="margin-left:0; margin-top:20px; border-left: 4px solid var(' . esc_attr( $color_var ) . ');"><p>' . wp_kses_post( (string) $msg ) . '</p></div>';
    }

    protected function should_notify_user_from_post( $key = 'notify_user' ) {
        if ( ! isset( $_POST[ $key ] ) ) {
            return false;
        }
        $val = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
        return ( $val === '1' || $val === 'on' || $val === 'yes' || $val === 'true' );
    }

    protected function notify_user_admin_action( $user_id, $type, $message, $subscription_id = 0, $force_send = false ) {
        if ( ! class_exists( 'TV_Notification_Engine' ) ) {
            return;
        }
        $sub = (object) array(
            'id'      => (int) $subscription_id,
            'user_id' => (int) $user_id,
        );
        TV_Notification_Engine::send_notification( $sub, $type, $message, $force_send );
    }

    public function render() {}
    public function handle_actions() {}
}