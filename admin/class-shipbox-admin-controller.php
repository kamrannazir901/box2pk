<?php
class ShipBox_Admin_Controller {

    public function render_customers_list() {
        // Load the Table Class
        require_once plugin_dir_path( __FILE__ ) . 'class-shipbox-customer-table.php';
        
        $customer_table = new Shipbox_Customer_Table();
        $customer_table->prepare_items();

        // Load the UI Partial
        include_once plugin_dir_path( __FILE__ ) . 'partials/shipbox-admin-customers-display.php';
    }

    public function render_shipments_list() {
        require_once plugin_dir_path( __FILE__ ) . 'class-shipbox-order-table.php';
        
        $order_table = new Shipbox_Order_Table();
        $order_table->prepare_items();

        // Re-use your partial system
        include_once plugin_dir_path( __FILE__ ) . 'partials/shipbox-admin-orders-display.php';
    }

     public function render_order_detail() {
        include_once plugin_dir_path( __FILE__ ) . 'partials/shipbox-admin-order-detail.php';
    }

    public function render_shipping_rates_manager() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'shipbox_shipping_rates';

    // Handle Deletion
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        check_admin_referer('delete_rate_' . $_GET['id']);
        $wpdb->delete($table_name, array('id' => intval($_GET['id'])));
        echo '<div class="updated"><p>Rate deleted successfully.</p></div>';
    }

    // Handle Adding New City
    if (isset($_POST['submit_new_rate'])) {
        check_admin_referer('add_shipping_rate', 'shipbox_rate_nonce');
        
        $wpdb->insert($table_name, array(
            'city_name'          => sanitize_text_field($_POST['city_name']),
            'base_rate'          => floatval($_POST['base_rate']),
            'additional_kg_rate' => floatval($_POST['additional_kg_rate']),
            'estimated_days'     => sanitize_text_field($_POST['estimated_days'])
        ));
        echo '<div class="updated"><p>New city added successfully.</p></div>';
    }

    $rates = $wpdb->get_results("SELECT * FROM $table_name ORDER BY city_name ASC");
    include_once plugin_dir_path(__FILE__) . 'partials/shipbox-admin-rates-display.php';
}
}