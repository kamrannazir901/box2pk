<div class="wrap">
    <h1>Shipping Rate Manager</h1>
    <p>Define shipping costs per city. These values will be used in the frontend [shipbox_calculator].</p>

    <div class="welcome-panel" style="padding: 20px;">
        <div class="welcome-panel-content">
            <h3>Add New City Rate</h3>
            <form method="post" action="">
                <?php wp_nonce_field('add_shipping_rate', 'shipbox_rate_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <td><input type="text" name="city_name" placeholder="City Name (e.g. Lahore)" class="regular-text" required></td>
                        <td><input type="number" step="0.01" name="base_rate" placeholder="Base Rate (1st KG)" class="small-text" required></td>
                        <td><input type="number" step="0.01" name="additional_kg_rate" placeholder="Add. KG Rate" class="small-text" required></td>
                        <td><input type="text" name="estimated_days" placeholder="Delivery Days (e.g. 2-3)" class="small-text"></td>
                        <td><input type="submit" name="submit_new_rate" class="button button-primary" value="Add City"></td>
                    </tr>
                </table>
            </form>
        </div>
    </div>

    <h2 class="title">Active Shipping Rates</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>City Name</th>
                <th>Base Rate (1st KG)</th>
                <th>Additional KG Rate</th>
                <th>Est. Delivery</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rates): foreach ($rates as $rate): ?>
                <tr>
                    <td><strong><?php echo esc_html($rate->city_name); ?></strong></td>
                    <td>Rs <?php echo number_format($rate->base_rate, 2); ?></td>
                    <td>Rs <?php echo number_format($rate->additional_kg_rate, 2); ?></td>
                    <td><?php echo esc_html($rate->estimated_days); ?> Days</td>
                    <td>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=shipbox-shipping-rates&action=delete&id=' . $rate->id), 'delete_rate_' . $rate->id); ?>" 
                           class="button button-link-delete" 
                           onclick="return confirm('Delete this city?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="5">No shipping rates defined yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>