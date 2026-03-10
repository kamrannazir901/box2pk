<div class="wrap">
    <h1 class="wp-heading-inline">ShipBox Customers</h1>
    <hr class="wp-header-end">

    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
        
        <?php
        // Display search box
        $customer_table->search_box('Search Customers', 'search_id');
        
        // Display the table with pagination and sorting
        $customer_table->display();
        ?>
    </form>
</div>