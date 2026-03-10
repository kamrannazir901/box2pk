<?php

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

class ShipBox_Database {

    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        self::create_customers_table($charset_collate);
        self::create_orders_table($charset_collate);
        self::create_email_logs_table($charset_collate);
        self::create_shipping_rates_table($charset_collate);
        self::create_cities_table($charset_collate);

        update_option('shipbox_db_version', '1.0.0');
    }

    public static function create_shipping_rates_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shipbox_weight_slabs';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            service_type varchar(50) NOT NULL,
            weight_min float NOT NULL,
            weight_max float NOT NULL,
            price float NOT NULL,
            currency varchar(10) DEFAULT 'USD',
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private static function create_cities_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shipbox_cities';

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            city_name varchar(100) NOT NULL,
            weight_min float NOT NULL DEFAULT 0,
            weight_max float NOT NULL DEFAULT 0,
            price float NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY city_range (city_name, weight_min, weight_max)
        ) $charset_collate;";

        dbDelta($sql);
    }

    private static function create_customers_table($charset_collate) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'shipbox_customers';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            customer_id varchar(20) NOT NULL,
            cnic varchar(20) DEFAULT NULL,
            phone varchar(20) NOT NULL,
            alt_phone varchar(20) DEFAULT NULL,
            address text NOT NULL,
            city varchar(100) NOT NULL,
            province varchar(100) NOT NULL,
            postal_code varchar(10) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            referral_source varchar(100) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY customer_id (customer_id),
            UNIQUE KEY user_id (user_id),
            KEY status (status),
            KEY city (city)
        ) $charset_collate;";

        dbDelta($sql);
    }

    private static function create_orders_table($charset_collate) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'shipbox_orders';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) UNSIGNED NOT NULL,
            order_number varchar(50) NOT NULL,
            final_price DECIMAL(20, 2) DEFAULT 0.00,
            shipping_price DECIMAL(20, 2) DEFAULT 0.00,
            final_price_locked TINYINT(1) DEFAULT 0,
            shipping_price_locked TINYINT(1) DEFAULT 0,
            carrier_name varchar(100) DEFAULT NULL,
            billing_weight decimal(20, 2) DEFAULT NULL,
            service_fee decimal(20, 2) DEFAULT NULL,
            duties_levies decimal(20, 2) DEFAULT NULL,
            merchant varchar(100) NOT NULL,
            merchant_order_number varchar(100) NOT NULL,
            merchant_tracking_number varchar(100) DEFAULT NULL,
            warehouse_country varchar(20) NOT NULL,
            is_economy TINYINT(1) DEFAULT 0,
            product_value decimal(20,2) NOT NULL DEFAULT 0.00,
            currency varchar(5) NOT NULL DEFAULT 'USD',
            is_consolidated tinyint(1) NOT NULL DEFAULT 0,
            consolidation_notes text DEFAULT NULL,
            screenshot_url varchar(500) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'awaiting_arrival',
            payment_status varchar(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY order_number (order_number),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY warehouse_country (warehouse_country),
            KEY created_at (created_at)
        ) $charset_collate;";

        dbDelta($sql);
    }

    private static function create_email_logs_table($charset_collate) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'shipbox_email_logs';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id VARCHAR(20) NULL,
            order_id VARCHAR(20) NULL,
            email_type varchar(50) NOT NULL,
            email_to varchar(100) NOT NULL,
            email_subject varchar(200) NOT NULL,
            email_body text NOT NULL,
            sent_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) NOT NULL DEFAULT 'sent',
            PRIMARY KEY  (id),
            KEY customer_id (customer_id),
            KEY order_id (order_id),
            KEY email_type (email_type),
            KEY status (status),
            KEY sent_at (sent_at)
        ) $charset_collate;";

        dbDelta($sql);
    }

    

    public static function tables_exist() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'shipbox_customers',
            $wpdb->prefix . 'shipbox_orders',
            $wpdb->prefix . 'shipbox_email_logs',
            $wpdb->prefix . 'shipbox_weight_slabs',
            $wpdb->prefix . 'shipbox_cities',
        );

        foreach ($tables as $table) {
            $exists = $wpdb->get_var(
                $wpdb->prepare("SHOW TABLES LIKE %s", $table)
            );

            if ($exists !== $table) {
                return false;
            }
        }

        return true;
    }

    public static function get_db_version() {
        return get_option('shipbox_db_version', '0.0.0');
    }
}