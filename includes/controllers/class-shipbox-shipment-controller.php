<?php
class ShipBox_Shipment_Controller {
  
  public function ajax_handle_shipment_submission() {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // 1. Security & Permission Check
    check_ajax_referer('shipbox_shipment_action', 'security');

    if ( ! is_user_logged_in() ) {
        wp_send_json_error(['message' => 'Session expired. Please login again.']);
    }

    // 2. Handle File Upload (Screenshot) - Optional
    $screenshot_url = ''; 
    
    if ( ! empty( $_FILES['screenshot']['name'] ) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        $uploaded_file = wp_handle_upload( $_FILES['screenshot'], array( 'test_form' => false ) );

        if ( isset( $uploaded_file['error'] ) ) {
            wp_send_json_error(['message' => 'Upload Error: ' . $uploaded_file['error']]);
        }
        
        $screenshot_url = $uploaded_file['url'];
    }

    // 3. Prepare Data for Model
    global $wpdb;
    $user_id = get_current_user_id();
    $customer_db_id = $wpdb->get_var( $wpdb->prepare( 
        "SELECT id FROM {$wpdb->prefix}shipbox_customers WHERE user_id = %d", 
        $user_id 
    ) );

    // Combine repeater fields
    $merchants = isset($_POST['merchants']) ? implode(', ', array_map('sanitize_text_field', $_POST['merchants'])) : '';
    $orders    = isset($_POST['order_numbers']) ? implode(', ', array_map('sanitize_text_field', $_POST['order_numbers'])) : '';
    $tracking_numbers = isset($_POST['tracking_numbers']) ? implode(', ', array_map('sanitize_text_field', $_POST['tracking_numbers'])) : '';

    // Clean up Product Value
    $raw_value = $_POST['product_value'] ?? '0';
    $clean_value = str_replace(',', '', $raw_value);
    $clean_value = preg_replace('/[^0-9.]/', '', $clean_value);
    $final_value = (float)$clean_value;

    $is_consolidated = isset($_POST['is_consolidated']) ? 1 : 0;
    $consolidation_notes = isset($_POST['consolidation_notes']) ? sanitize_textarea_field($_POST['consolidation_notes']) : '';

    $final_data = array(
        'customer_db_id'        => $customer_db_id,
        'merchant'              => $merchants,
        'merchant_order_number' => $orders,
        'tracking_number'       => $tracking_numbers,
        'product_value'         => $final_value,
        'warehouse_country'     => sanitize_text_field($_POST['warehouse_country'] ?? ''),
        'is_economy'            => isset($_POST['is_economy']) ? 1 : 0,
        'screenshot_url'        => $screenshot_url,
        'status'                => 'awaiting_arrival',
        'is_consolidated'       => $is_consolidated,
        'consolidation_notes'   => $consolidation_notes,
    );

    // 4. Save to Database
    require_once plugin_dir_path( __FILE__ ) . '../models/class-shipbox-shipment-model.php';
    $model = new ShipBox_Shipment_Model();
    $order_id = $model->create_order($final_data);

    if ($order_id) {
        // FETCH ORDER DETAILS (This defines the missing $order variable)
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT o.*, c.customer_id as public_customer_id, u.user_email, u.display_name 
             FROM {$wpdb->prefix}shipbox_orders o 
             LEFT JOIN {$wpdb->prefix}shipbox_customers c ON o.customer_id = c.id 
             LEFT JOIN {$wpdb->prefix}users u ON c.user_id = u.ID 
             WHERE o.id = %d", 
            $order_id
        ));

        if ($order) {
            // Get Warehouse Text
            $warehouse_settings = get_option('shipbox_warehouse_settings', []);
            $selected_country = strtolower($_POST['warehouse_country'] ?? '');
            $warehouse_text = $warehouse_settings[$selected_country] ?? "Contact Support for Address";

            // Save the numeric ID before swapping for the email template
            $internal_customer_id = $order->customer_id; 
            $order->customer_id = $order->public_customer_id;
            
            $email_service_path = plugin_dir_path( __FILE__ ) . '../class-shipbox-email-service.php';
            
            if ( file_exists( $email_service_path ) ) {
                require_once $email_service_path;
                
                if ( class_exists( 'ShipBox_Email_Service' ) ) {
                    try {
                        // Pass the order, status, and warehouse text
                        ShipBox_Email_Service::send_status_change_email($order, 'address_usage_confirmation', $warehouse_text);
                    } catch ( Throwable $e ) {
                        // We don't necessarily want to stop success if just the email fails, 
                        // but since you are debugging, we'll catch it here.
                        error_log('ShipBox Email Error: ' . $e->getMessage());
                    }
                }
            }

            wp_send_json_success([
                'message' => 'Your shipment confirmation has been submitted successfully!',
                'order_id' => $order_id
            ]);
        } else {
            wp_send_json_error(['message' => 'Order created but failed to fetch details.']);
        }
    } else {
        wp_send_json_error(['message' => 'Failed to save order. Database error.']);
    }
  }

  public function render_tracking_view() {
    if ( ! is_user_logged_in() ) {
        return '<div class="alert alert-warning">Please login to view your shipments.</div>';
    }

    global $wpdb;
    $user_id = get_current_user_id();

    $customer_internal_id = $wpdb->get_var( $wpdb->prepare( 
        "SELECT id FROM {$wpdb->prefix}shipbox_customers WHERE user_id = %d", 
        $user_id 
    ) );

    if ( ! $customer_internal_id ) {
        return '<div class="alert alert-info">No customer profile found.</div>';
    }

    $table_name = $wpdb->prefix . 'shipbox_orders';
    $shipments = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM $table_name WHERE customer_id = %d ORDER BY created_at DESC",
        $customer_internal_id
    ) );

    ob_start();
    $template_path = plugin_dir_path( __FILE__ ) . '../../public/partials/tracking-view.php';

    if ( file_exists( $template_path ) ) {
        include $template_path;
    } else {
        echo '<div class="alert alert-danger">File not found: ' . esc_html($template_path) . '</div>';
    }

    return ob_get_clean();
  }
}