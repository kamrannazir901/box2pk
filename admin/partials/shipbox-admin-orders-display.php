<?php
/**
 * Admin Orders Display
 * 
 * @package ShipBox
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">ShipBox Shipments</h1>
    <a href="<?php echo admin_url('admin.php?page=shipbox-manager'); ?>" class="page-title-action">View Customers</a>
    <hr class="wp-header-end">

    <?php
    // Show active filters
    $active_filters = [];
    if (!empty($_GET['filter_country'])) {
        $active_filters[] = 'Country: ' . strtoupper($_GET['filter_country']);
    }
    if (isset($_GET['filter_cons']) && $_GET['filter_cons'] !== '') {
        $active_filters[] = 'Consolidated: ' . ($_GET['filter_cons'] == '1' ? 'Yes' : 'No');
    }
    if (!empty($_GET['filter_status'])) {
        $active_filters[] = 'Status: ' . ucfirst(str_replace('_', ' ', $_GET['filter_status']));
    }
    if (!empty($_GET['view_shipments'])) {
        global $wpdb;
        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT customer_id FROM {$wpdb->prefix}shipbox_customers WHERE id = %d",
            intval($_GET['view_shipments'])
        ));
        if ($customer) {
            $active_filters[] = 'Customer: ' . $customer->customer_id;
        }
    }
    
    if (!empty($active_filters)) {
        echo '<div class="notice notice-info"><p><strong>Active Filters:</strong> ' . implode(' | ', $active_filters) . '</p></div>';
    }
    ?>

    <?php
    // Debug: Show if items exist
    if (empty($order_table->items)) {
        echo '<div class="notice notice-warning"><p>No shipment data found.</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>Found ' . count($order_table->items) . ' shipment(s)</p></div>';
    }
    ?>

    <form method="get" id="shipbox-orders-filter">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
        
        <?php
        // Display search box
        $order_table->search_box('Search Orders', 'search_id');
        
        // Display the table with pagination and sorting
        $order_table->display();
        ?>
    </form>
</div>

<style>
/* Table Styling */
.wp-list-table {
    width: 100%;
    background: #fff;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin-top: 20px;
}

.wp-list-table thead th,
.wp-list-table thead td {
    padding: 8px 10px;
    border-bottom: 1px solid #c3c4c7;
    font-weight: 600;
}

.wp-list-table tbody tr {
    background: #fff;
}

.wp-list-table tbody tr:nth-child(odd) {
    background: #f6f7f7;
}

.wp-list-table tbody tr:hover {
    background: #f0f0f1;
}

.wp-list-table tbody td {
    padding: 10px;
    border-bottom: 1px solid #c3c4c7;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

/* Filter dropdowns */
.tablenav select {
    margin-right: 5px;
}

/* Responsive */
@media screen and (max-width: 782px) {
    .wp-list-table td {
        display: block;
        width: 100%;
        text-align: left;
    }
}
</style>