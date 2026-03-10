<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once WP_PLUGIN_DIR . '/shipbox/includes/lib/stripe-php/init.php';
 
// 1. Fetch Settings & State
$settings = get_option('shipbox_payment_settings');
$payment_enabled = isset($settings['enable_online_payment']) && $settings['enable_online_payment'] == '1';
$error_message = '';
$order_info = null;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// 2. Logic: Handle Stripe Payment Request
// 2. Logic: Handle Stripe Payment Request
if ( $action == 'pay_now' && $order_id > 0 ) {
    if (!$payment_enabled) {
        $error_message = "Online payments are currently disabled.";
    } else {
        global $wpdb;
        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipbox_orders WHERE id = %d", $order_id));

        if (!$order) {
            $error_message = "Order #{$order_id} could not be found.";
        } 
        // --- NEW CHECK: IS ALREADY PAID? ---
        elseif (isset($order->payment_status) && $order->payment_status === 'paid') {
            $error_message = "Order #{$order->order_number} has already been paid.";
        } 
        // ------------------------------------
        else {
            $api_key = $settings['stripe_test_mode'] ? $settings['test_secret_key'] : $settings['live_secret_key'];
            if (empty($api_key)) {
                $error_message = "Payment configuration is incomplete.";
            } else {
                try {
                    \Stripe\Stripe::setApiKey($api_key);
                    $session = \Stripe\Checkout\Session::create([
                        'payment_method_types' => ['card'],
                        'customer_email' => wp_get_current_user()->user_email,
                        'line_items' => [[
                            'price_data' => [
                                'currency' => 'pkr',
                                'product_data' => ['name' => 'Order #' . $order->order_number],
                                'unit_amount' => intval($order->final_price * 100),
                            ],
                            'quantity' => 1,
                        ]],
                        'mode' => 'payment',
                        'success_url' => home_url('/dashboard/?tab=billing&action=success&order_id=' . $order_id),
                        'cancel_url' => home_url('/dashboard/?tab=history'),
                    ]);
                    if ( ! headers_sent() ) {
                        wp_redirect($session->url);
                        exit;
                    }
                    else {
                        // If Elementor or another plugin started output, use JS to redirect
                        ?>
                        <div style="text-align: center; margin-top: 50px; font-family: sans-serif;">
                            <p>Redirecting to secure checkout...</p>
                            <div class="spinner" style="border: 4px solid #f3f3f3; border-top: 4px solid #1a9c38; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 20px auto;"></div>
                            <script type="text/javascript">
                                window.location.href = "<?php echo esc_url_raw($session->url); ?>";
                            </script>
                            <style>
                                @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                            </style>
                            <p>If you are not redirected, <a href="<?php echo esc_url($session->url); ?>">click here</a>.</p>
                        </div>
                        <?php
                        exit;
                    }
                    
                } catch (Exception $e) {
                    $error_message = "Stripe Error: " . $e->getMessage();
                }
            }
        }
    }
}

// 3. Logic: Handle Success Update
if ( $action == 'success' && $order_id > 0 ) {
    global $wpdb;
    $wpdb->update($wpdb->prefix . 'shipbox_orders', ['payment_status' => 'paid'], ['id' => $order_id]);
    $order_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipbox_orders WHERE id = %d", $order_id));
}
?>

<div class="shipbox-billing-page">
    <h1 class="shipbox-page-title">Billing</h1>

    <?php if ($action == 'success' && $order_info): ?>
        <div class="status-card success">
            <h3>Payment Received!</h3>
            <p>Order <strong><?php echo esc_html($order_info->order_number); ?></strong> has been successfully processed and marked as <strong>Paid</strong>.</p>
            <a href="<?php echo home_url('/dashboard/?tab=history'); ?>" class="btn-primary" style="background: var(--plugin-green);">View History</a>
        </div>
    <?php elseif ($error_message): ?>
        <div class="status-card error">
            <h3>Payment Issue</h3>
            <p><?php echo esc_html($error_message); ?></p>
            <a href="<?php echo home_url('/dashboard/?tab=history'); ?>" class="btn-primary" style="background: var(--plugin-red);">Return to History</a>
        </div>
    <?php else: ?>
        <div class="status-card neutral">
            <h3>Manage Billing</h3>
            <p>Securely pay for your pending orders online. To proceed, please select an order from your History tab.</p>
            <a href="<?php echo home_url('/dashboard/?tab=history'); ?>" class="btn-primary" style="background: var(--plugin-purple);">Go to History</a>
        </div>
    <?php endif; ?>
</div>

<style>
    .status-card { background: #fff; border: 1px solid #ddd; border-radius: 12px; padding: 30px; margin-top: 20px; }
    .success { border-left: 8px solid var(--plugin-green); }
    .error { border-left: 8px solid var(--plugin-red); }
    .neutral { border-left: 8px solid var(--plugin-green); }
    
    .btn-primary { 
        display: inline-block; 
        padding: 12px 25px; 
        color: #fff; 
        border-radius: 8px; 
        background-color: var(--plugin-green) !important;
        text-decoration: none; 
        font-weight: 600; 
        margin-top: 15px;
    }
    .btn-primary:hover,.btn-primary:visited { color: #fff !important; opacity: 0.9; }
</style>