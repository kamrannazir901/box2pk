<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<style>
    /* 1. Isolation Wrapper - Full Width */
    #sb-reg-isolation {
        all: unset;
        display: block;
        width: 100% !important;
        margin: 40px 0 !important;
        font-family: 'Segoe UI', Roboto, Arial, sans-serif !important;
        box-sizing: border-box !important;
    }

    #sb-reg-isolation *, #sb-reg-isolation *::before, #sb-reg-isolation *::after {
        box-sizing: border-box !important;
    }

    /* 2. Grid System */
    .sb-grid {
        display: flex !important;
        flex-wrap: wrap !important;
        margin: 0 -15px !important;
    }

    .sb-col-6 { width: 50% !important; padding: 0 15px !important; }
    .sb-col-12 { width: 100% !important; padding: 0 15px !important; }

    @media (max-width: 768px) {
        .sb-col-6 { width: 100% !important; }
    }

    /* 3. Field & Label Styling */
    .sb-field-group {
        margin-bottom: 30px !important;
        text-align: left !important;
    }

    .sb-label {
        display: block !important;
        font-size: 18px !important; /* Requested size */
        font-weight: 500 !important;
        color: #000 !important;
        margin-bottom: 12px !important;
    }

    /* 4. Input & Select Styling */
    .sb-input, .sb-select {
        width: 100% !important;
        height: 55px !important;
        border: 1px solid #ced4da !important;
        border-radius: 8px !important;
        padding: 0 20px !important; /* Consistent left padding */
        font-size: 16px !important;
        color: #000 !important;
        background: #fff !important;
        outline: none !important;
    }

    /* Black Placeholders */
    .sb-input::placeholder {
        color: #000 !important;
        opacity: 1 !important;
    }

    .sb-select {
        appearance: none !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23000' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
        background-repeat: no-repeat !important;
        background-position: right 20px center !important;
        background-size: 14px !important;
        cursor: pointer !important;
    }

    .sb-input:focus, .sb-select:focus {
        border-color: #ff0000 !important;
        box-shadow: 0 0 0 3px rgba(255, 0, 0, 0.1) !important;
    }

    /* 5. Terms and Checkbox */
    .sb-terms-wrap {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 40px 0 !important;
    }

    .sb-checkbox {
        width: 22px !important;
        height: 22px !important;
        margin-right: 15px !important;
        cursor: pointer !important;
    }

    .sb-terms-text {
        font-size: 18px !important;
        color: #000 !important;
        cursor: pointer !important;
    }

    /* 6. Register Button */
    .sb-btn-submit {
        background: transparent !important;
        border: 2px solid #ff0000 !important;
        color: #ff0000 !important;
        border-radius: 50px !important;
        padding: 4px 20px !important;
        font-weight: 700 !important;
        font-size: 18px !important;
        text-transform: uppercase !important;
        cursor: pointer !important;
        transition: all 0.3s ease !important;
    }

    .sb-btn-submit:hover {
        background: #ff0000 !important;
        color: #fff !important;
    }
</style>

<div id="sb-reg-isolation">
    <form method="post" id="shipbox-reg-form" class="bg-white py-2 px-4 p-md-5">
        <?php wp_nonce_field('shipbox_register_action', 'shipbox_reg_nonce'); ?>
        
        <?php if (isset($result) && is_wp_error( $result ) ) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $result->get_error_message(); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <p style="color: #000; font-size: 16px; margin-bottom: 35px; font-weight: 600;">* Mandatory Field</p>

        <div class="sb-grid">
            <div class="sb-col-6 sb-field-group">
                <label class="sb-label">First Name*</label>
                <input type="text" name="first_name" class="sb-input" placeholder="Please Enter Your First Name" 
                       value="<?php echo isset($_POST['first_name']) ? esc_attr($_POST['first_name']) : ''; ?>" required>
            </div>
            <div class="sb-col-6 sb-field-group">
                <label class="sb-label">Last Name*</label>
                <input type="text" name="last_name" class="sb-input" placeholder="Please Enter Your Last Name" 
                       value="<?php echo isset($_POST['last_name']) ? esc_attr($_POST['last_name']) : ''; ?>" required>
            </div>

            <div class="sb-col-6 sb-field-group">
                <label class="sb-label">C.N.I.C #*</label>
                <input type="text" name="cnic" class="sb-input" placeholder="Please Enter Your C.N.I.C #" 
                       value="<?php echo isset($_POST['cnic']) ? esc_attr($_POST['cnic']) : ''; ?>" required>
            </div>
            <div class="sb-col-6 sb-field-group">
                <label class="sb-label">Email Address*</label>
                <input type="email" name="email" class="sb-input" placeholder="Please Enter Your Email" 
                       value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>" required>
            </div>

            <div class="sb-col-12 sb-field-group">
                <label class="sb-label">Delivery Address*</label>
                <input type="text" name="address" class="sb-input" placeholder="Please Enter Your Street Address" 
                       value="<?php echo isset($_POST['address']) ? esc_attr($_POST['address']) : ''; ?>" required>
            </div>

            <div class="sb-col-6 sb-field-group">
                <label class="sb-label">City*</label>
                <input type="text" name="city" class="sb-input" placeholder="Please Enter Your City Name" 
                       value="<?php echo isset($_POST['city']) ? esc_attr($_POST['city']) : ''; ?>" required>
            </div>
            <div class="sb-col-6 sb-field-group">
                <label class="sb-label">Postal/ZIP Code (Optional)</label>
                <input type="text" name="zip_code" class="sb-input" placeholder="Please Enter Your Postal/ZIP Code"
                       value="<?php echo isset($_POST['zip_code']) ? esc_attr($_POST['zip_code']) : ''; ?>">
            </div>

            <div class="sb-col-6 sb-field-group">
                <label class="sb-label">Province*</label>
                <?php $sel_province = isset($_POST['province']) ? $_POST['province'] : ''; ?>
                <select name="province" class="sb-select" required>
                    <option value="" disabled <?php selected($sel_province, ''); ?>>Please Enter Your Province Name</option>
                    <option value="Punjab" <?php selected($sel_province, 'Punjab'); ?>>Punjab</option>
                    <option value="Sindh" <?php selected($sel_province, 'Sindh'); ?>>Sindh</option>
                    <option value="KPK" <?php selected($sel_province, 'KPK'); ?>>KPK</option>
                    <option value="Balochistan" <?php selected($sel_province, 'Balochistan'); ?>>Balochistan</option>
                </select>
            </div>
            <div class="sb-col-6 sb-field-group">
                <label class="sb-label">Country</label>
                <input type="text" class="sb-input" value="Pakistan" readonly style="background-color: #f9fafb !important; color: #666 !important;">
            </div>

            <div class="sb-col-6 sb-field-group">
                <label class="sb-label">Mobile #*</label>
                <input type="text" name="phone" class="sb-input" placeholder="Please Enter Your Mobile #" 
                       value="<?php echo isset($_POST['phone']) ? esc_attr($_POST['phone']) : ''; ?>" required>
            </div>
            <div class="sb-col-6 sb-field-group">
                <label class="sb-label">Phone # (Optional)</label>
                <input type="text" name="phone_alt" class="sb-input" placeholder="Please Enter Your Phone # Home/Work"
                       value="<?php echo isset($_POST['phone_alt']) ? esc_attr($_POST['phone_alt']) : ''; ?>">
            </div>

            <div class="sb-col-6 sb-field-group">
                <label class="sb-label">Create Password*</label>
                <input type="password" name="password" class="sb-input" placeholder="Please Create Your Password" required>
            </div>
            <div class="sb-col-6 sb-field-group">
                <label class="sb-label">Confirm Password*</label>
                <input type="password" name="confirm_password" class="sb-input" placeholder="Please Confirm Your Password" required>
            </div>

            <div class="sb-col-12 sb-field-group">
                <label class="sb-label">How Did You Hear About Us? (Optional)</label>
                <?php $sel_ref = isset($_POST['referral_source']) ? $_POST['referral_source'] : ''; ?>
                <select name="referral_source" class="sb-select">
                    <option value="" <?php selected($sel_ref, ''); ?>>Select An Option</option>
                    <option value="Facebook" <?php selected($sel_ref, 'Facebook'); ?>>Facebook</option>
                    <option value="Instagram" <?php selected($sel_ref, 'Instagram'); ?>>Instagram</option>
                    <option value="Google" <?php selected($sel_ref, 'Google'); ?>>Google</option>
                    <option value="Friend" <?php selected($sel_ref, 'Friend'); ?>>Friend</option>
                </select>
            </div>
        </div>

        <div class="sb-terms-wrap">
            <input type="checkbox" name="terms_accept" id="terms" class="sb-checkbox" <?php checked(isset($_POST['terms_accept'])); ?> required>
            <label for="terms" class="sb-terms-text">
                I agree that I have read and accepted Box2PK <strong>Terms of Services</strong> and <strong>Privacy Policy</strong>.
            </label>
        </div>

        <div style="text-align: center;">
            <button type="submit" name="shipbox_register_submit" class="sb-btn-submit">
                Register and Get Addresses
            </button>
        </div>
    </form>
</div>