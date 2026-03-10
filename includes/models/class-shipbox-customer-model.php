<?php
/**
 * Customer Model
 * * @package ShipBox
 */

if (!defined('ABSPATH')) {
    exit;
}

class ShipBox_Customer_Model {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'shipbox_customers';
    }

    /**
     * Create a new customer record in our custom table
     */
    public function add_customer($data) {
        global $wpdb;
        
        $inserted = $wpdb->insert($this->table_name, $data);
        
        if ($inserted) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Get the next available Customer ID (e.g., B2P-1001)
     */
    public function get_next_customer_id() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shipbox_customers';
        
        // 1. Get the prefix from settings
        $settings = get_option('shipbox_general_settings');
        $prefix = $settings['customer_id_prefix'] ?? 'B2P';

        // 2. Extract the highest number from the database column
        $last_id = $wpdb->get_var("SELECT customer_id FROM $table_name ORDER BY id DESC LIMIT 1");

        if ($last_id) {
            // Remove prefix and dash, then increment the number
            $number = (int) str_replace($prefix . '-', '', $last_id);
            $next_number = $number + 1;
        } else {
            $next_number = 1; // Start at 001 if empty
        }

        // 3. Return the prefixed, 3-digit padded ID
        return $prefix . '-' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
    }
    /**
     * Increment the global counter after successful registration
     */
    public function increment_counter() {
        $counter = get_option('shipbox_customer_id_counter', 1);
        update_option('shipbox_customer_id_counter', $counter + 1);
    }

    /**
     * Fetch customer by WP User ID
     */
    public function get_customer_by_user($user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $this->table_name WHERE user_id = %d",
            $user_id
        ));
    }
}