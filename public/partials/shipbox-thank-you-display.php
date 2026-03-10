<?php
/**
 * Thank You Page Partial - Left Aligned Client Design
 */
if ( ! isset( $_GET['order_id'] ) ) {
    echo '<div class="alert alert-warning">Invalid Request.</div>';
    return;
}

global $wpdb;
$order_id = intval( $_GET['order_id'] );

// Fetch Details
$order = $wpdb->get_row( $wpdb->prepare(
    "SELECT o.*, c.customer_id as public_cid, u.user_email, u.display_name 
     FROM {$wpdb->prefix}shipbox_orders o 
     LEFT JOIN {$wpdb->prefix}shipbox_customers c ON o.customer_id = c.id 
     LEFT JOIN {$wpdb->prefix}users u ON c.user_id = u.ID 
     WHERE o.id = %d", 
    $order_id
) );

if ( ! $order ) {
    echo '<div class="alert alert-danger">Order not found.</div>';
    return;
}

// Logic to show only the first word of the name
$full_name  = trim( $order->display_name );
$first_name = explode( ' ', $full_name )[0];

$has_image = ! empty( $order->screenshot_url );
?>

<div class="shipbox-thank-you-wrapper">
    <div class="shipbox-thank-you-container p-5 bg-white mx-auto shadow-sm" style="max-width: 1000px; border-radius: 10px;">
        
        <div class="text-center mb-5">
            <h1 class="thank-you-title fw-bold">Thank you for using our services <?php echo esc_html( $first_name ); ?>!</h1>
        </div>

        <div class="row align-items-start">
            <div class="<?php echo $has_image ? 'col-md-7' : 'col-md-10'; ?> order-details-col">
                <p class="customer-id-text mb-2">Customer ID: <?php echo esc_html( $order->public_cid ); ?></p>
                
                <p class="order-received-text mb-4">
                    Your order information has been received—rest easy, your parcel is in good hands!
                </p>
                
                <p class="order-number-display mb-5">Order # <?php echo esc_html( $order->order_number ); ?></p>

                <div class="d-flex align-items-center gap-2 flex-wrap mb-4">
                    <p class="contact-us-prompt mb-0 me-2">For any question or updates, please contact us.</p>
                    
                    <div class="d-flex gap-1">
                        <a href="mailto:info@box2pk.com" class="social-box email-blue">
                            <i class="dashicons dashicons-email"></i>
                        </a>
                        <a href="https://wa.me/923353387766" target="_blank" class="social-box whatsapp-green">
                            <i class="dashicons dashicons-whatsapp"></i>
                        </a>
                    </div>

                    <a href="<?php echo home_url('/dashboard/?tab=history'); ?>" class="btn-view-order ms-md-4">View Order</a>
                </div>
            </div>

            <?php if ( $has_image ) : ?>
            <div class="col-md-5 text-center">
                <div class="img-preview-frame">
                    <img src="<?php echo esc_url( $order->screenshot_url ); ?>" class="img-fluid rounded shadow-sm" alt="Order Screenshot">
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-5 pt-4 border-top">
            <p class="email-notice-text">
                <i class="dashicons dashicons-email-alt me-2" style="color: #007bff;"></i>
                We’ve sent you a confirmation email to <span style="color: #007bff; font-weight: 500;"><?php echo esc_html( $order->user_email ); ?></span> with the details of your order.
            </p>
        </div>
    </div>
</div>

<style>
    /* Client Design Styling */
    .thank-you-title { font-size: 38px; color: #000; margin-bottom: 50px; }
    
    /* Left Aligned Text Elements */
    .order-details-col { text-align: left; }
    .customer-id-text { font-size: 24px; color: #000; font-weight: 400; }
    .order-received-text { font-size: 22px; color: #333; }
    .order-number-display { font-size: 24px; color: #000; }
    .contact-us-prompt { font-size: 22px; color: #000; }

    /* Icons */
    .social-box {
        width: 36px; height: 36px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 4px; color: #fff !important;
    }
    .email-blue { background-color: #007bff; }
    .whatsapp-green { background-color: #28a745; }

    /* View Order Button - Green Outline */
    .btn-view-order {
        border: 1px solid #28a745;
        color: #28a745;
        padding: 8px 25px;
        border-radius: 8px;
        font-size: 18px;
        text-decoration: none;
        transition: 0.2s;
        font-weight: 500;
    }
    .btn-view-order:hover { background: #28a745; color: #fff !important; }

    .img-preview-frame { background: #fdfdfd; padding: 10px; border: 1px solid #eee; border-radius: 8px; }
    .email-notice-text { font-size: 16px; color: #555; }

    @media (max-width: 768px) {
        .thank-you-title { font-size: 28px; }
        .ms-md-4 { margin-left: 0 !important; margin-top: 15px; }
    }
</style>