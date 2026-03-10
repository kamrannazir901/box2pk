<?php 
if ( ! is_user_logged_in() || current_user_can('manage_options') ) : ?>
    <div class="p-4 mb-4 text-dark bg-info bg-opacity-25 border-start border-info border-4 rounded-3 shadow-sm">
        <div class="d-flex align-items-center">
            <i class="dashicons dashicons-info text-info me-3" style="font-size: 30px; width: 30px; height: 30px;"></i>
            <p class="mb-0 fw-medium" style="font-size: 1.1rem;">
                Please login with your customer account to access this page.
            </p>
        </div>
    </div>
<?php else : 

    // 1. SETUP PATHING & TAB LOGIC
    $partials_path = plugin_dir_path( __DIR__ ) . 'partials/'; 
    $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

    // 2. BREADCRUMB TITLES MAPPING
    $titles = [
        'overview'   => 'Warehouse Addresses',
        'calculator' => 'Cost Calculator',
        'shipment'   => 'Order Confirmation',
        'history'    => 'Order History',
        'profile'    => 'My Profile'
    ];
    $current_label = $titles[$tab] ?? 'Overview';
?>

<div class="container" >

   
    <div class="shipbox-content-area mt-4">
        <?php 
        switch ($tab) {
            case 'calculator':
                echo do_shortcode('[shipbox_calculator]');
                break;
            case 'shipment':
                echo do_shortcode('[shipbox_submit_shipment]');
                break;
            case 'billing':
                include $partials_path . 'billing.php';
                break;
            case 'history':
                echo do_shortcode('[shipbox_order_history]');
                break;
            case 'profile':
                echo do_shortcode('[shipbox_profile]');
                break;
            case 'editprofile':
                echo do_shortcode('[shipbox_editprofile]');
                break;
            case 'overview':
            default:
                echo '<style>body { background-color: #cfcece !important; } .elementor-element-d33277b{background-color: #cfcece !important; } </style>'; 
                include $partials_path . 'warehouse-overview.php';
                break;
        }
        ?>
    </div>
</div>
<?php endif; ?>