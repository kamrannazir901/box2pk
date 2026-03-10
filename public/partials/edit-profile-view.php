<?php 
if ( ! is_user_logged_in() || current_user_can('manage_options') ) : ?>
    <div class="shipbox-alert">
        Please login with your customer account to access this page.
    </div>
<?php else : 
    global $wpdb;
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $customer = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}shipbox_customers WHERE user_id = %d",
        $user_id
    ) );
?>

<div class="shipbox-edit-container">
    <h1 class="shipbox-page-title text-center">Edit Profile</h1>
    
    <p class="shipbox-subtitle">Submit this form to update your personal and shipping information</p>

    <form method="POST" class="shipbox-form-card">
        <?php wp_nonce_field('shipbox_profile_action', 'shipbox_profile_nonce'); ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="shipbox-group">
                    <label>First Name:</label>
                    <input type="text" name="first_name" value="<?php echo esc_attr($user->first_name); ?>" required>
                </div>

                <div class="shipbox-group">
                    <label>Last Name:</label>
                    <input type="text" name="last_name" value="<?php echo esc_attr($user->last_name); ?>" required>
                </div>

                <div class="shipbox-group">
                    <label>Customer ID:</label>
                    <input type="text" value="<?php echo esc_attr($customer->customer_id ?? 'N/A'); ?>" class="readonly-input" readonly>
                </div>

                <div class="shipbox-group">
                    <label>Phone No:</label>
                    <input type="text" name="phone" value="<?php echo esc_attr($customer->phone ?? ''); ?>" required>
                </div>

                <div class="shipbox-group">
                    <label>Alternate Phone:</label>
                    <input type="text" name="alt_phone" value="<?php echo esc_attr($customer->alt_phone ?? ''); ?>">
                </div>
            </div>

            <div class="col-md-6">
                <div class="shipbox-group">
                    <label>Email Address*</label>
                    <input type="email" value="<?php echo esc_attr($user->user_email); ?>" class="readonly-input" readonly>
                </div>

                <div class="shipbox-group">
                    <label>City:</label>
                    <input type="text" name="city" value="<?php echo esc_attr($customer->city ?? ''); ?>">
                </div>

                <div class="shipbox-group">
                    <label>Province:</label>
                    <input type="text" name="province" value="<?php echo esc_attr($customer->province ?? ''); ?>">
                </div>

                <div class="shipbox-group">
                    <label>Zip Code:</label>
                    <input type="text" name="zip_code" value="<?php echo esc_attr($customer->postal_code ?? ''); ?>">
                </div>

                <div class="shipbox-group">
                    <label>Country:</label>
                    <input type="text" value="Pakistan" class="readonly-input" readonly>
                </div>
            </div>
        </div>

        <hr class="my-4" style="border-color: #eee;">

        <div class="row">
            <div class="col-md-6">
                <div class="shipbox-group">
                    <label>New Password:</label>
                    <input type="password" name="new_password" placeholder="Leave blank to keep current">
                </div>
            </div>
            <div class="col-md-6">
                <div class="shipbox-group">
                    <label>Confirm Password:</label>
                    <input type="password" name="confirm_password">
                </div>
            </div>
        </div>

        <div class="shipbox-group mt-3">
            <label>Full Street Address:</label>
            <textarea name="address" rows="3"><?php echo esc_textarea($customer->address ?? ''); ?></textarea>
        </div>

        <div class="text-center mt-5">
            <button type="submit" name="shipbox_profile_save" class="shipbox-submit-btn">SUBMIT</button>
        </div>
    </form>
</div>
<?php endif; ?>

<style>
    /* 1. Global Typography & Colors */
    .shipbox-edit-container {
        font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        max-width: 1000px;
        margin: 40px auto;
        color: #000;
    }

 
   
    /* 2. Form Card Style */
    .shipbox-form-card {
        background: #f8f9fa; /* Matches the subtle grey in your screenshots */
        padding: 50px;
        border-radius: 12px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.02);
    }

    .shipbox-group {
        margin-bottom: 20px;
    }

    .shipbox-group label {
        display: block;
        font-size: 0.95rem;
        font-weight: 500;
        margin-bottom: 8px;
        color: #000;
    }

    /* 3. Input Styles (Pill-shaped from image_070782.png) */
    .shipbox-group input, 
    .shipbox-group textarea {
        width: 100%;
        padding: 12px 20px;
        border: 1px solid #e0e0e0;
        border-radius: 8px; 
        background-color: #fff;
        font-size: 1rem;
        color: #000;
        outline: none;
        transition: border-color 0.2s;
    }

    .shipbox-group input:focus, 
    .shipbox-group textarea:focus {
        border-color: #1a9c38;
    }

    .shipbox-group input.readonly-input {
        background-color: #ececec;
        color: #666;
        cursor: not-allowed;
    }

    /* 4. Button Style (Exact match to green SUBMIT button) */
    .shipbox-submit-btn {
        background-color: #1a9c38; 
        color: #fff;
        border: none;
        padding: 14px 80px;
        font-size: 1.1rem;
        font-weight: 700;
        border-radius: 8px;
        cursor: pointer;
        transition: transform 0.2s, background 0.2s;
        text-transform: uppercase;
    }

    .shipbox-submit-btn:hover {
        background-color: #147a2c;
        transform: translateY(-1px);
    }

    /* Alert Style */
    .shipbox-alert {
        padding: 20px;
        background: #f8f9fa;
        border: 2px solid #000;
        border-radius: 8px;
        font-weight: 600;
        text-align: center;
    }

    @media (max-width: 768px) {
        .shipbox-form-card { padding: 25px; }
        .shipbox-submit-btn { width: 100%; padding: 14px; }
    }
</style>