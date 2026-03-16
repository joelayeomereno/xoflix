<?php
if (!defined('ABSPATH')) { exit; }

class TV_Manager_Activator {

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_plans = $wpdb->prefix . 'tv_plans';
        $table_subs = $wpdb->prefix . 'tv_subscriptions';
        $table_payments = $wpdb->prefix . 'tv_payments';
        $table_coupons = $wpdb->prefix . 'tv_coupons';
        $table_methods = $wpdb->prefix . 'tv_payment_methods';
        $table_logs = $wpdb->prefix . 'tv_activity_logs';
        $table_sports = $wpdb->prefix . 'tv_sports_events';
        $table_news = $wpdb->prefix . 'tv_announcements';
        $table_notify = $wpdb->prefix . 'tv_notification_logs';
        $table_recycle = $wpdb->prefix . 'tv_recycle_bin';

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // UPDATED: Added category and display_order
        $sql_plans = "CREATE TABLE $table_plans (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            price decimal(10,2) NOT NULL,
            duration_days int(5) NOT NULL,
            allow_multi_connections tinyint(1) DEFAULT 1,
            discount_tiers longtext,
            description text,
            category varchar(50) DEFAULT 'standard',
            display_order int(11) DEFAULT 0,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_subs = "CREATE TABLE $table_subs (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            plan_id mediumint(9) NOT NULL,
            start_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            end_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            status varchar(20) DEFAULT 'pending' NOT NULL,
            connections int(11) NOT NULL DEFAULT 1,
            credential_user varchar(100),
            credential_pass varchar(100),
            credential_url varchar(255),
            credential_m3u text,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_payments = "CREATE TABLE $table_payments (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            subscription_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            amount_usd decimal(10,2) DEFAULT NULL,
            amount_ngn decimal(10,2) DEFAULT NULL,
            fx_rate decimal(18,8) DEFAULT NULL,
            coupon_code varchar(50) DEFAULT NULL,
            discount_usd decimal(10,2) DEFAULT NULL,
            discount_local decimal(10,2) DEFAULT NULL,
            gross_usd decimal(10,2) DEFAULT NULL,
            gross_local decimal(10,2) DEFAULT NULL,
            currency varchar(10) DEFAULT 'USD',
            method varchar(50) DEFAULT 'manual',
            transaction_id varchar(100),
            proof_url text,
            status varchar(20) DEFAULT 'pending',
            date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            attempted_at datetime DEFAULT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_coupons = "CREATE TABLE $table_coupons (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            type varchar(20) DEFAULT 'percent' NOT NULL,
            amount decimal(10,2) NOT NULL,
            expiry_date datetime DEFAULT '0000-00-00 00:00:00',
            usage_limit int(11) DEFAULT 0,
            usage_count int(11) DEFAULT 0,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_methods = "CREATE TABLE $table_methods (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            logo_url varchar(255),
            bank_name varchar(150),
            account_name varchar(150),
            account_number varchar(80),
            countries text,
            currencies text,
            instructions text,
            link varchar(255),
            open_behavior varchar(20) DEFAULT 'window',
            flutterwave_enabled TINYINT(1) DEFAULT 0,
            flutterwave_secret_key text,
            flutterwave_public_key text,
            flutterwave_currency varchar(10) DEFAULT 'USD',
            flutterwave_title varchar(190),
            flutterwave_logo varchar(255),
            status varchar(20) DEFAULT 'active',
            display_order int(5) DEFAULT 0,
            notes text,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_logs = "CREATE TABLE $table_logs (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT 0,
            action varchar(100) NOT NULL,
            details text,
            ip_address varchar(50),
            date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            attempted_at datetime DEFAULT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_sports = "CREATE TABLE $table_sports (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            league varchar(100),
            start_time datetime NOT NULL,
            channel varchar(100),
            sport_type varchar(50) DEFAULT 'soccer',
            status varchar(20) DEFAULT 'scheduled',
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_news = "CREATE TABLE $table_news (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            button_text varchar(50),
            button_action varchar(50),
            color_scheme varchar(50) DEFAULT 'blue',
            start_date datetime DEFAULT CURRENT_TIMESTAMP,
            end_date datetime DEFAULT '0000-00-00 00:00:00',
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_notify = "CREATE TABLE $table_notify (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            subscription_id mediumint(9) NOT NULL,
            type varchar(50) NOT NULL,
            channel varchar(20) NOT NULL,
            status varchar(20) NOT NULL,
            message text,
            payload longtext,
            error_msg text,
            retry_count int(5) DEFAULT 0,
            next_retry datetime DEFAULT NULL,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            is_manual tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY type_status (type,status),
            KEY next_retry (next_retry)
        ) $charset_collate;";

        $sql_recycle = "CREATE TABLE $table_recycle (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            entity_type varchar(60) NOT NULL,
            entity_table varchar(191) NOT NULL,
            entity_pk varchar(32) NOT NULL DEFAULT 'id',
            entity_id bigint(20) NOT NULL,
            payload longtext NOT NULL,
            deleted_at datetime NOT NULL,
            deleted_by bigint(20) NOT NULL DEFAULT 0,
            expires_at datetime NOT NULL,
            restored_at datetime DEFAULT NULL,
            restored_by bigint(20) DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'deleted',
            PRIMARY KEY  (id),
            KEY entity_lookup (entity_type, entity_id),
            KEY status_expires (status, expires_at)
        ) $charset_collate;";

        dbDelta($sql_plans);
        dbDelta($sql_subs);
        dbDelta($sql_payments);
        dbDelta($sql_coupons);
        dbDelta($sql_methods);
        dbDelta($sql_logs);
        dbDelta($sql_sports);
        dbDelta($sql_news);
        dbDelta($sql_notify);
        dbDelta($sql_recycle);

        if (!get_role('tv_financial_analyst')) {
            add_role('tv_financial_analyst', 'Financial Analyst', array(
                'read' => true,
                'manage_tv_finance' => true,
            ));
        } else {
            $r = get_role('tv_financial_analyst');
            if ($r && !$r->has_cap('manage_tv_finance')) $r->add_cap('manage_tv_finance');
        }

        if (function_exists('tv_manager_register_flow_rewrite_rules')) {
            tv_manager_register_flow_rewrite_rules();
        }
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }
    }
}