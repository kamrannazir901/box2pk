<?php
class ShipBox_Shipment_Model {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'shipbox_orders';
    }

    /**
     * Save the Address Usage Confirmation data
     */
    public function create_order($data) {
        global $wpdb;

       
       $inserted = $wpdb->insert(
        $this->table_name,
        array(
            'customer_id'           => $data['customer_db_id'],
            'order_number'          => $this->generate_unique_order_no(),
            'merchant'              => sanitize_text_field($data['merchant']),
            'merchant_order_number' => sanitize_text_field($data['merchant_order_number']),
            'warehouse_country'     => sanitize_text_field($data['warehouse_country']),
            'merchant_tracking_number' => sanitize_text_field($data['tracking_number']),
            'is_economy'            => $data['is_economy'],
            'product_value'         => floatval($data['product_value']),
            'currency'              => sanitize_text_field($_POST['currency']),
            'screenshot_url'        => esc_url_raw($data['screenshot_url']),
            'status'                => sanitize_text_field($data['status']),
            'is_consolidated'       => $data['is_consolidated'],
            'consolidation_notes'   => sanitize_text_field($data['consolidation_notes']),
            'created_at'            => current_time('mysql'),
            'updated_at'            => current_time('mysql')
        ),
        // Corrected Format Array:
        array(
            '%d', // customer_id
            '%s', // order_number
            '%s', // merchant
            '%s', // merchant_order_number
            '%s', // merchant_tracking_number
            '%s', // warehouse_country
            '%d', // is_economy (integer 0 or 1)
            '%f', // product_value (float/decimal)
            '%s', // currency
            '%s', // screenshot_url
            '%s', // status (STAY AS STRING)
            '%d', // is_consolidated (integer 0 or 1)
            '%s', // consolidation_notes
            '%s', // created_at
            '%s'  // updated_at
        )
    );

        return $inserted ? $wpdb->insert_id : false;
    }

    private function generate_unique_order_no() {
        return 'SB-' . strtoupper(wp_generate_password(8, false));
    }
}