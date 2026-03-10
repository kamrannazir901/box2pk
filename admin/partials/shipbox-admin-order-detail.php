<?php
/**
 * Order Detail View
 * Display complete shipment information with customer details
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$table_name = $wpdb->prefix . 'shipbox_orders';

if (!$order_id) {
    echo '<div class="notice notice-error"><p>Invalid order ID.</p></div>';
    return;
}

/**
 * HELPER: Fetch the complete order with all joins.
 * We use a function so we can refresh the $order object after every POST update.
 */
function fetch_shipbox_order_data($id) {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare(
        "SELECT o.*, 
                c.customer_id, c.phone, c.alt_phone, c.address, c.city, c.province, c.postal_code, c.cnic,
                u.user_email, u.display_name
         FROM {$wpdb->prefix}shipbox_orders o
         LEFT JOIN {$wpdb->prefix}shipbox_customers c ON o.customer_id = c.id
         LEFT JOIN {$wpdb->prefix}users u ON c.user_id = u.ID
         WHERE o.id = %d", $id
    ));
}

// Initial Data Load
$order = fetch_shipbox_order_data($order_id);

if (!$order) {
    echo '<div class="notice notice-error"><p>Order not found.</p></div>';
    return;
}

$warehouse_settings = get_option('shipbox_warehouse_settings', []);
$country_key = strtolower($order->warehouse_country);
$warehouse = isset($warehouse_settings[$country_key]) ? $warehouse_settings[$country_key] : null;


// Handle All Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 7. Manual Payment Status Toggle
    if (isset($_POST['toggle_payment_status']) && check_admin_referer('shipbox_payment_toggle_' . $order_id)) {
        $new_payment_status = ($_POST['payment_status_action'] === 'mark_paid') ? 'paid' : null;
        
        $wpdb->update($table_name, 
            ['payment_status' => $new_payment_status, 'updated_at' => current_time('mysql')],
            ['id' => $order_id]
        );

        $order = fetch_shipbox_order_data($order_id);
        echo '<div class="notice notice-success is-dismissible"><p>✅ Payment status updated to: <strong>' . ($new_payment_status ? 'Paid' : 'Unpaid') . '</strong></p></div>';
    }

    // 1. Final Price Update
    if (isset($_POST['update_final_price']) && check_admin_referer('shipbox_final_price_' . $order_id)) {
        $action = $_POST['price_action'];
        if ($action === 'finalize') {
            $wpdb->update($table_name, 
                ['final_price' => sanitize_text_field($_POST['final_price_input']), 'final_price_locked' => 1, 'updated_at' => current_time('mysql')],
                ['id' => $order_id]
            );
        } elseif ($action === 'unlock' && current_user_can('manage_options')) {
            $wpdb->update($table_name, ['final_price_locked' => 0], ['id' => $order_id]);
        }
    }

    // 2. Shipping Price Update
    if (isset($_POST['update_shipping_price']) && check_admin_referer('shipbox_shipping_price_' . $order_id)) {
        $action = $_POST['price_action'];
        if ($action === 'lock') {
            $wpdb->update($table_name, 
                ['shipping_price' => sanitize_text_field($_POST['shipping_price_input']), 'shipping_price_locked' => 1, 'updated_at' => current_time('mysql')],
                ['id' => $order_id]
            );
        } elseif ($action === 'unlock') {
            $wpdb->update($table_name, ['shipping_price_locked' => 0], ['id' => $order_id]);
        }
    }

    // 3. Status Update
    if (isset($_POST['update_status']) && check_admin_referer('update_order_status_' . $order_id)) {
        $new_status = sanitize_text_field($_POST['order_status']);
        
        // --- VALIDATION CHECK ---
        $shipping_cost = (float)$order->shipping_price;
        $final_price = (float)$order->final_price;
        $is_consolidated = (int)$order->is_consolidated === 1;
        $validation_error = '';

        if (in_array($new_status, ['received', 'in_transit']) && $shipping_cost <= 0 && !$is_consolidated) {            
            $validation_error = 'Update Failed: Shipping Price must be greater than 0 for this status.';
        } elseif (in_array($new_status, ['arrived_karachi', 'delivered']) && $final_price <= 0) {
            $validation_error = 'Update Failed: Final Payable Price must be greater than 0 for this status.';
        }

        if (!empty($validation_error)) {
            // Show error notice and STOP execution of the update
            echo '<div class="notice notice-error is-dismissible"><p><strong>❌ ' . esc_html($validation_error) . '</strong></p></div>';
        } else {
            // If validation passes, proceed with update
            $wpdb->update($table_name, ['status' => $new_status, 'updated_at' => current_time('mysql')], ['id' => $order_id]);
            
            // Trigger Email Service
            if (class_exists('ShipBox_Email_Service')) {
                ShipBox_Email_Service::send_status_change_email($order, $new_status, $warehouse);
            }
            
            // Refresh data and show success
            $order = fetch_shipbox_order_data($order_id);
            echo '<div class="notice notice-success is-dismissible"><p>✅ Order Status Updated Successfully.</p></div>';
        }
    }

    // 6. Update Logistics & Fee Details
    if (isset($_POST['update_logistics_fees']) && check_admin_referer('shipbox_logistics_fees_' . $order_id)) {
        $wpdb->update($table_name, 
            [
                'carrier_name'   => sanitize_text_field($_POST['carrier_name']),
                'billing_weight' => floatval($_POST['billing_weight']),
                'service_fee'    => floatval($_POST['service_fee']),
                'duties_levies'  => floatval($_POST['duties_levies']),
                'updated_at'     => current_time('mysql')
            ],
            ['id' => $order_id]
        );

        $order = fetch_shipbox_order_data($order_id);
        echo '<div class="notice notice-success is-dismissible"><p>✅ Logistics and Fees updated.</p></div>';
    }

    // Refresh data so HTML reflects changes
    $order = fetch_shipbox_order_data($order_id);
    echo '<div class="notice notice-success is-dismissible"><p>Order Updated Successfully.</p></div>';
}


// 4. Custom Email Logic
if (isset($_POST['send_email']) && check_admin_referer('send_email_' . $order_id)) {
    $sent = ShipBox_Email_Service::send_custom_email($order, sanitize_text_field($_POST['email_subject']), wp_kses_post($_POST['email_body']));
    if ($sent) {
        echo '<div class="notice notice-success"><p>✅ Email sent successfully.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>❌ Failed to send email.</p></div>';
    }
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        Shipment Details: <?php echo esc_html($order->order_number); ?>
    </h1>
    <a href="?page=shipbox-all-shipments" class="page-title-action">← Back to Orders</a>
    
  
    
    <hr class="wp-header-end">

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            
            <!-- Sidebar (RIGHT SIDE) -->
            <div id="postbox-container-1" class="postbox-container">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2>INVOICE</h2>
                    </div>
                    <div class="inside">
                        <a href="<?php echo admin_url('admin-post.php?action=generate_shipbox_pdf&order_no=' . esc_attr($order->order_number)); ?>" 
                        class="button button-primary button-large widefat shipbox-bold-btn">
                        Generate PDF
                        </a>
                    </div>
                </div>

                <div class="postbox">
                    <div class="postbox-header">
                        <h2>💳 Payment Status</h2>
                    </div>
                    <div class="inside">
                        <form method="post">
                            <?php wp_nonce_field('shipbox_payment_toggle_' . $order_id); ?>
                            
                            <p><strong>Current Status:</strong> 
                                <span style="color: <?php echo ($order->payment_status === 'paid') ? '#00a32a' : '#d63638'; ?>; font-weight: bold;">
                                    <?php echo ($order->payment_status === 'paid') ? 'PAID' : 'UNPAID'; ?>
                                </span>
                            </p>

                            <input type="hidden" name="payment_status_action" value="<?php echo ($order->payment_status === 'paid') ? 'mark_unpaid' : 'mark_paid'; ?>">
                            
                            <button type="submit" name="toggle_payment_status" class="button widefat">
                                <?php echo ($order->payment_status === 'paid') ? '🔄 Mark as Unpaid' : '✅ Mark as Paid'; ?>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="postbox">
                    <div class="postbox-header">
                        <h2>💰 Order Finalization</h2>
                    </div>
                    <div class="inside">
                        <form method="post">
                            <?php wp_nonce_field('shipbox_final_price_' . $order_id); ?>
                            
                            <p>
                                <label><strong>Final Price (PKR):</strong></label>
                                <input type="number" step="0.01" name="final_price_input" 
                                    value="<?php echo esc_attr($order->final_price); ?>" 
                                    class="widefat" style="margin-top:5px; font-weight:bold; font-size:1.2em; border: 2px solid #2271b1;"
                                    <?php echo ($order->final_price_locked) ? 'readonly' : ''; ?>>
                            </p>

                            <?php if (!$order->final_price_locked) : ?>
                                <input type="hidden" name="price_action" value="finalize">
                                <button type="submit" name="update_final_price" class="button button-primary button-large widefat">
                                    Finalize & Send Invoice
                                </button>
                            <?php else : ?>
                                <div style="background: #dff0d8; color: #3c763d; padding: 10px; border-radius: 4px; border: 1px solid #d6e9c6; margin-bottom: 10px; text-align:center;">
                                    <strong>LOCKED</strong><br>PKR <?php echo number_format($order->final_price, 2); ?>
                                </div>
                                <?php if (current_user_can('manage_options')) : ?>
                                    <input type="hidden" name="price_action" value="unlock">
                                    <button type="submit" name="update_final_price" class="button widefat">
                                        🔓 Unlock Final Price
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>


                <!-- Add this RIGHT AFTER the Order Finalization postbox and BEFORE the Update Status postbox -->

                <!-- Shipping Price Management -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2>🚢 Shipping Price</h2>
                    </div>
                    <div class="inside">
                        <form method="post">
                            <?php wp_nonce_field('shipbox_shipping_price_' . $order_id); ?>
                            
                            <p>
                                <label><strong>Shipping Charges (PKR):</strong></label>
                                <input type="number" step="0.01" name="shipping_price_input" 
                                    value="<?php echo esc_attr($order->shipping_price); ?>" 
                                    class="widefat" style="margin-top:5px; font-weight:bold; font-size:1.2em; border: 2px solid #2271b1;"
                                    <?php echo ($order->shipping_price_locked) ? 'readonly' : ''; ?>>
                            </p>

                            <?php if (!$order->shipping_price_locked) : ?>
                                <input type="hidden" name="price_action" value="lock">
                                <button type="submit" name="update_shipping_price" class="button button-primary button-large widefat">
                                    💾 Save & Lock Price
                                </button>
                                <p style="font-size: 11px; color: #666; margin: 10px 0 0 0;">
                                    ℹ️ This will lock the price. No emails will be sent.
                                </p>
                            <?php else : ?>
                                <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; border: 1px solid #c3e6cb; margin-bottom: 10px; text-align:center;">
                                    <strong>🔒 LOCKED</strong><br>PKR <?php echo number_format($order->shipping_price, 2); ?>
                                </div>
                                <?php if (current_user_can('manage_options')) : ?>
                                    <input type="hidden" name="price_action" value="unlock">
                                    <button type="submit" name="update_shipping_price" class="button widefat">
                                        🔓 Unlock Shipping Price
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Update Status -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2>Update Status</h2>
                    </div>
                    <div class="inside">
                        <form method="post">
                            <?php wp_nonce_field('update_order_status_' . $order_id); ?>
                            <p>
                                <label><strong>Current Status:</strong></label><br>
                                <select name="order_status" class="widefat" style="margin-top: 5px;">
                                  <option value="awaiting_arrival" <?php selected($order->status, 'awaiting_arrival'); ?>>Awaiting Arrival</option>
                                  <option value="received" <?php selected($order->status, 'received'); ?>>Received at Warehouse</option>
                                  <option value="in_transit" <?php selected($order->status, 'in_transit'); ?>>In Transit to Pakistan</option>
                                  <option value="arrived_karachi" <?php selected($order->status, 'arrived_karachi'); ?>>Arrived in Karachi</option>
                                  <option value="delivered" <?php selected($order->status, 'delivered'); ?>>Delivered to Customer</option>
                              </select>
                            </p>
                            <p>
                                <button type="submit" name="update_status" class="button button-primary button-large widefat">
                                    Update Status
                                </button>
                            </p>
                            <p style="font-size: 11px; color: #666; margin: 10px 0 0 0;">
                                ℹ️ Status updates will automatically send emails to the customer.
                            </p>
                        </form>
                    </div>
                </div>

                <!-- Send Email -->
              <div class="postbox">
                    <div class="postbox-header">
                        <h2>📧 Send Email to Customer</h2>
                    </div>
                    <div class="inside">
                        <form method="post">
                            <?php wp_nonce_field('send_email_' . $order_id); ?>
                            
                           <label><strong>1. Select Quick Notice:</strong></label>
                            <div class="template-selection-wrapper" style="margin-top: 10px; margin-bottom: 15px;">
                                <select id="notice_templates" class="widefat" onchange="fillEmailTemplate(this.value); this.value='';" style="border: 1px solid #2271b1; height: 35px;">
                                    <option value="">-- Choose a Template --</option>
                                    <option value="all_arrived">✅ All Packages Arrived</option>
                                    <option value="delay_notice">⏳ Delay Notice</option>
                                    <option value="need_info">⚠️ Need Information</option>
                                    <option value="clear">🧹 Clear Fields</option>
                                </select>
                            </div>

                            <p>
                                <label><strong>2. Review Details:</strong></label><br>
                                <input type="text" name="email_subject" id="email_subject" class="widefat" placeholder="Subject" required style="margin-top:5px;">
                            </p>
                            
                            <p>
                                <textarea name="email_body" id="email_body" class="widefat" rows="10" placeholder="Message content..." required style="resize: vertical;"></textarea>
                            </p>
                            
                            <p>
                                <button type="submit" name="send_email" class="button button-primary button-large widefat" style="height: 45px; font-size: 1.1em;">
                                    🚀 Send Selection Now
                                </button>
                            </p>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Main Content (LEFT SIDE) -->
            <div id="post-body-content">
                
                <!-- Order Information -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2>📦 Order Information</h2>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th>Order Number:</th>
                                <td><strong style="font-size: 15px;"><?php echo esc_html($order->order_number); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Merchant Orders:</th>
                                <td>
                                    <?php
                                    // Parse merchants and order numbers
                                    $merchants = array_map('trim', explode(',', $order->merchant));
                                    $order_numbers = array_map('trim', explode(',', $order->merchant_order_number));
                                    $tracking_numbers = array_map('trim', explode(',', $order->merchant_tracking_number));
                                    
                                    if (count($merchants) > 0 && count($order_numbers) > 0):
                                    ?>
                                        <table style="width: 100%; border-collapse: collapse; margin: 5px 0;">
                                            <thead>
                                                <tr style="background: #f0f0f0;">
                                                    <th style="padding: 8px; text-align: left; border: 1px solid #ddd; font-weight: 600;">#</th>
                                                    <th style="padding: 8px; text-align: left; border: 1px solid #ddd; font-weight: 600;">Merchant</th>
                                                    <th style="padding: 8px; text-align: left; border: 1px solid #ddd; font-weight: 600;">Order Number</th>
                                                    <th style="padding: 8px; text-align: left; border: 1px solid #ddd; font-weight: 600;">Tracking Number</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $max = max(count($merchants), count($order_numbers));
                                                for ($i = 0; $i < $max; $i++):
                                                    $merchant = isset($merchants[$i]) ? esc_html($merchants[$i]) : '-';
                                                    $order_num = isset($order_numbers[$i]) ? esc_html($order_numbers[$i]) : '-';
                                                    $tracking_num = isset($tracking_numbers[$i]) ? esc_html($tracking_numbers[$i]) : '-';
                                                ?>
                                                    <tr>
                                                        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo ($i + 1); ?></td>
                                                        <td style="padding: 8px; border: 1px solid #ddd;"><strong><?php echo $merchant; ?></strong></td>
                                                        <td style="padding: 8px; border: 1px solid #ddd; font-family: monospace;"><?php echo $order_num; ?></td>
                                                        <td style="padding: 8px; border: 1px solid #ddd; font-family: monospace;"><?php echo $tracking_num; ?></td>
                                                    </tr>
                                                <?php endfor; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <span style="color: #999;">No merchant information</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Warehouse Country:</th>
                                <td>
                                    <?php 
                                    $country_names = ['usa' => '🇺🇸 USA (Delaware)', 'uk' => '🇬🇧 United Kingdom', 'turkey' => '🇹🇷 Turkey (Istanbul)'];
                                    echo $country_names[strtolower($order->warehouse_country)] ?? strtoupper($order->warehouse_country);
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Product Value:</th>
                                <td><strong style="font-size: 15px;"><?php echo esc_html($order->currency . ' ' . number_format($order->product_value, 2)); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Consolidated:</th>
                                <td>
                                    <?php if ($order->is_consolidated): ?>
                                        <span style="color:#009640; font-weight:bold;">✓ Yes - Multiple Packages</span>
                                        <?php if ($order->consolidation_notes): ?>
                                            <br><small style="color:#666; display: block; margin-top: 5px; padding: 8px; background: #f0f0f0; border-radius: 4px;">
                                                <strong>Notes:</strong> <?php echo nl2br(esc_html($order->consolidation_notes)); ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:#999;">✗ No - Single Package</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                           
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <?php
                                  $status_colors = [
                                    'awaiting_arrival' => '#dba617',
                                    'received'         => '#2271b1',
                                    'in_transit'       => '#8c65b2',
                                    'arrived_karachi'  => '#e65100', // Added this
                                    'delivered'        => '#00a32a'
                                ];
                                    $color = $status_colors[$order->status] ?? '#666';
                                    ?>
                                    <span class="status-badge" style="background:<?php echo $color; ?>; color:#fff; padding:8px 16px; border-radius:4px; font-weight:600; font-size: 13px;">
                                        <?php echo ucfirst(str_replace('_', ' ', esc_html($order->status))); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Screenshot:</th>
                                <td>
                                    <?php if ($order->screenshot_url): ?>
                                        <a href="<?php echo esc_url($order->screenshot_url); ?>" target="_blank" class="button">📸 View Screenshot</a>
                                    <?php else: ?>
                                        <span style="color:#999;">No screenshot uploaded</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Created:</th>
                                <td><?php echo date('d F Y, h:i A', strtotime($order->created_at)); ?></td>
                            </tr>
                            <tr>
                                <th>Last Updated:</th>
                                <td><?php echo date('d F Y, h:i A', strtotime($order->updated_at)); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="postbox">
                    <div class="postbox-header">
                        <h2>🚚 Logistics & Fee Breakdown</h2>
                    </div>
                    <div class="inside">
                        <form method="post">
                            <?php wp_nonce_field('shipbox_logistics_fees_' . $order_id); ?>
                            
                            <p>
                                <label><strong>Carrier Name:</strong></label>
                                <input type="text" name="carrier_name" value="<?php echo esc_attr($order->carrier_name); ?>" class="widefat" placeholder="e.g. DHL, FedEx, Local Courier">
                            </p>

                            <p>
                                <label><strong>Billing Weight (kg):</strong></label>
                                <input type="number" step="0.01" name="billing_weight" value="<?php echo esc_attr($order->billing_weight); ?>" class="widefat">
                            </p>

                            <p>
                                <label><strong>Service Fee (PKR):</strong></label>
                                <input type="number" step="0.01" name="service_fee" value="<?php echo esc_attr($order->service_fee); ?>" class="widefat">
                            </p>

                            <p>
                                <label><strong>Duties & Govt. Levies (PKR):</strong></label>
                                <input type="number" step="0.01" name="duties_levies" value="<?php echo esc_attr($order->duties_levies); ?>" class="widefat">
                            </p>

                            <hr>
                            
                            <button type="submit" name="update_logistics_fees" class="button button-primary button-large widefat">
                                Update Logistics & Fees
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2>👤 Customer Information</h2>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th>Customer ID:</th>
                                <td><strong style="font-size: 15px;"><?php echo esc_html($order->customer_id); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Name:</th>
                                <td><?php echo esc_html($order->display_name); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><a href="mailto:<?php echo esc_attr($order->user_email); ?>"><?php echo esc_html($order->user_email); ?></a></td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td><a href="tel:<?php echo esc_attr($order->phone); ?>"><?php echo esc_html($order->phone); ?></a></td>
                            </tr>
                            <?php if ($order->alt_phone): ?>
                            <tr>
                                <th>Alt Phone:</th>
                                <td><a href="tel:<?php echo esc_attr($order->alt_phone); ?>"><?php echo esc_html($order->alt_phone); ?></a></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Delivery Address:</th>
                                <td style="line-height: 1.6;"><?php echo nl2br(esc_html($order->address)); ?></td>
                            </tr>
                            <tr>
                                <th>City/Province:</th>
                                <td><?php echo esc_html($order->city . ', ' . $order->province); ?></td>
                            </tr>
                            <tr>
                                <th>Postal Code:</th>
                                <td><?php echo esc_html($order->postal_code); ?></td>
                            </tr>
                            <?php if ($order->cnic): ?>
                            <tr>
                                <th>CNIC:</th>
                                <td><?php echo esc_html($order->cnic); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<style>

    .shipbox-bold-btn {
    font-weight: 600 !important; 
    text-transform: uppercase;   /* Clean professional look */
    letter-spacing: 0.5px;       /* Better readability */
    height: 46px !important;     /* Slightly taller */
    line-height: 44px !important;
    font-size: 14px !important;
    text-align: center;
}



/* Fix Sidebar Layout */
#poststuff #post-body {
    margin-right: 300px;
}

#poststuff #post-body.columns-2 #postbox-container-1 {
    float: right;
    margin-right: -300px;
    width: 280px;
}



/* Form Table Styling */
.form-table th {
    width: 200px;
    font-weight: 600;
    padding: 15px 10px 15px 0;
    vertical-align: top;
}

.form-table td {
    padding: 15px 10px;
}

/* Sidebar Forms */
.postbox .inside {
    padding: 12px;
}

.widefat {
    width: 100%;
    box-sizing: border-box;
}

.button-large {
    height: 40px;
    line-height: 38px;
    padding: 0 16px;
}

/* Email form */
.postbox textarea {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    font-size: 13px;
}


.template-button-grid .button {
    text-align: center;
    padding: 10px 5px !important;
    height: auto !important;
    line-height: 1.2 !important;
    font-size: 11px !important;
    font-weight: 600;
    border-radius: 4px;
    background: #f6f7f7;
    display: flex;
    align-items: center;
    justify-content: center;
}

.template-button-grid .button:hover {
    background: #2271b1 !important;
    color: white !important;
    border-color: #2271b1 !important;
}

#email_body {
    transition: background-color 0.3s ease;
    border: 1px solid #8c8f94;
    padding: 10px;
}


/* Responsive */
@media screen and (max-width: 850px) {
    #poststuff #post-body {
        margin-right: 0;
    }
    
    #poststuff #post-body.columns-2 #postbox-container-1 {
        float: none;
        margin-right: 0;
        width: 100%;
        margin-top: 20px;
    }
}
</style>

<script>
function fillEmailTemplate(type) {
    if (!type || type === '') return;
    if (type === 'clear') { 
        document.getElementById('email_subject').value = '';
        document.getElementById('email_body').value = '';
        return; 
    }

    const orderNo = '<?php echo esc_js($order->order_number); ?>';

    const templates = {
        'all_arrived': {
            subject: `Consolidation Complete: All Packages Received (${orderNo})`,
            body: `All packages for your consolidated order have been received at our warehouse. We are currently finalizing the packing and weight calculations.\n\nYou will receive a notification once the final shipping charges are updated and ready for payment.`
        },
        'delay_notice': {
            subject: `Shipment Update: Delay Notice (${orderNo})`,
            body: `Your shipment is currently experiencing a slight delay due to [Flight Schedule / Customs Clearance].\n\nWe are monitoring the progress closely and will update your dashboard as soon as the package moves to the next stage. We apologize for any inconvenience.`
        },
        'need_info': {
            subject: `Action Required: Information Needed (${orderNo})`,
            body: `To proceed with the shipment of your order, we require the following details:\n\n- [Please specify: e.g. Purchase Invoice / CNIC Copy]\n\nPlease reply to this email with the requested information so we can avoid further delays in dispatching your items.`
        }
    };
    
    if (templates[type]) {
        document.getElementById('email_subject').value = templates[type].subject;
        document.getElementById('email_body').value = templates[type].body;
        
        // Visual cue that text has changed
        const textarea = document.getElementById('email_body');
        textarea.style.backgroundColor = '#f0f6fb';
        setTimeout(() => { textarea.style.backgroundColor = '#fff'; }, 400);
    }
}

function clearTemplate() {
    document.getElementById('email_subject').value = '';
    document.getElementById('email_body').value = '';
}
</script>