<?php 
if ( ! is_user_logged_in() || current_user_can('manage_options') ) : ?>
    <div class="p-4 mb-4 text-dark bg-opacity-10 border-start border-4 rounded-3 shadow-sm" style="background-color: #1a9c38; border-color: #1a9c38 !important;">
        <div class="d-flex align-items-center">
            <i class="dashicons dashicons-info me-3" style="font-size: 30px; width: 30px; height: 30px; color: #1a9c38;"></i>
            <p class="mb-0 fw-medium" style="font-size: 1.1rem;">
                Please login with your customer account to access this page.
            </p>
        </div>
    </div>
<?php else : ?>

<div class="shipbox-container py-4" style="max-width: 1100px; margin: 0 auto;">

    <div class="text-center mb-4">
        <h1 class="shipbox-page-title">Order Confirmation</h1>
        <p class="shipbox-subtitle ">Submit this form to inform us about your order delivery to our warehouse</p>
    </div>

    <form id="shipbox-submission-form" class="p-4 p-md-5 rounded-4" style="background-color: #f2f2f2;">
        <?php wp_nonce_field('shipbox_shipment_action', 'shipbox_shipment_nonce'); ?>
        
        <div class="row g-5">
            <div class="col-md-7">
                <div class="mb-3">
                    <label class="form-label fw-bold small mb-1">Name:</label>
                    <input type="text" class="form-control shipbox-input-styled" value="<?php echo esc_attr($user_name); ?>" readonly placeholder="Autofill">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small mb-1">Customer ID:</label>
                    <input type="text" id="customer-id-ref" class="form-control shipbox-input-styled" value="<?php echo esc_attr($customer->customer_id); ?>" readonly placeholder="Autofill">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small mb-1">Phone No:</label>
                    <input type="text" name="phone" class="form-control shipbox-input-styled" value="<?php echo esc_attr($customer->phone); ?>" readonly placeholder="Autofill">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small mb-1">Email Address*</label>
                    <input type="email" class="form-control shipbox-input-styled" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" readonly placeholder="Autofill">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small mb-1">Total product(s) value*</label>
                    <div class="input-group">
                        <select name="currency" class="form-select shipbox-input-white" style="max-width: 100px !important;">
                            <option value="USD">USD</option>
                            <option value="GBP">GBP</option>
                            <option value="TRY">TRY</option>
                            <option value="PKR">PKR</option>
                        </select>
                        <input type="number" step="0.01" name="product_value" class="form-control shipbox-input-white" placeholder="0.00" required>
                    </div>
                </div>

                <div id="merchant-order-container">
                    <div class="row mb-1 d-flex custom-label-size">
                        <div class="col-4"><label class="form-label fw-bold small mb-1">Ordered from</label></div>
                        <div class="col-4"><label class="form-label fw-bold small mb-1">Order #</label></div>
                        <div class="col-4"><label class="form-label fw-bold small mb-1">Tracking #</label></div>
                    </div>

                    <div id="merchant-rows-wrapper">
                        <div class="row g-2 mb-2 merchant-order-row align-items-center">
                            <div class="col-4">
                                <input type="text" name="merchants[]" class="form-control shipbox-input-white" placeholder="e.g: Amazon" required>
                            </div>
                            <div class="col-4">
                                <input type="text" name="order_numbers[]" class="form-control shipbox-input-white" placeholder="e.g: 001001002" required>
                            </div>
                            <div class="col-4">
                                <input type="text" name="tracking_numbers[]" class="form-control shipbox-input-white" placeholder="e.g: 9400110200881234567890">
                            </div>
                        </div>
                    </div>

                    <!-- i want button to go at left side and no border or outline, only text add new item little bold with internal css class - no inline css-->
                    <button type="button" id="add-merchant-row" class="btn btn-link fw-bold p-0 add-merchant-row">+ Add Another Item</button>

                </div>

                <div class="mb-3 mt-4">
                    <label class="form-label fw-bold small mb-1">Warehouse address:</label>
                    <select name="warehouse_country" id="warehouse-select" class="form-select shipbox-input-white mb-2" required>
                        <option value="">Select Warehouse</option>
                        <?php 
                        $labels = [
                            'usa'    => 'Tax Free Delaware', 
                            'uk'     => 'United Kingdom', 
                            'turkey' => 'Turkey Istanbul'
                        ];
                        foreach ($warehouse_settings as $key => $wh): 
                            if ($key === 'usa_economy') continue;
                        ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($labels[$key] ?? ucfirst($key)); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div id="warehouse-display" class="p-3 border rounded-3 bg-white small shadow-sm" style="display: none; border-left: 4px solid #1a9c38 !important;">
                    </div>
                </div> 
            </div>

            <div class="col-md-5">
                <label class="form-label fw-bold small mb-1">Merchant checkout page screenshot(s):</label>
                <div class="upload-box border rounded-3 p-2 mb-3 bg-white d-flex align-items-center" style="cursor: pointer; border-style: solid !important; border-color: #ced4da !important;" onclick="document.getElementById('screenshot_input').click();">
                    <span class="text-success fw-bold ms-2" style="color: #1a9c38 !important;">Upload File</span>
                    <input type="file" id="screenshot_input" name="screenshot" hidden accept="image/*">
                </div>

                <div id="preview-container" class="border rounded-4 d-flex align-items-center justify-content-center bg-white" style="height: 350px; position: relative; overflow: hidden; border-color: #ddd !important;">
                    <img id="image-preview" src="" style="max-width: 95%; max-height: 95%; display: none;">
                    <div id="placeholder-text" class="text-muted">
                        <i class="dashicons dashicons-format-image" style="font-size: 100px; width: 100px; height: 100px; color: #eee;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="shipbox-instruction-section mt-4">
            <div class="consolidation-card border bg-white rounded-4 p-3 mb-3 shadow-sm">
                <div class="d-flex align-items-start">
                    <div class="form-check p-0 m-0 d-flex align-items-center">
                        <input class="form-check-input m-0" type="checkbox" name="is_consolidated" id="is_consolidated" value="1" style="flex-shrink: 0; width: 20px !important; height: 20px !important;">
                    </div>
                    <div class="ms-3">
                        <label class="form-label fw-bold mb-0 d-block" for="is_consolidated" style="cursor: pointer; line-height: 1.2 !important;">
                            Consolidate My Packages
                        </label>
                        <small class="text-muted consolidation-subtext">Combine multiple orders into one box to save up to 20% on shipping fees.</small>
                    </div>
                </div>
            </div>

            <div class="instruction-box">
                <label class="form-label fw-bold small mb-2">Consolidation & Special Handling Instructions</label>
                <textarea name="consolidation_notes" class="form-control shipbox-input-white" rows="4" placeholder="Example: Remove shoe boxes to save weight, or pack fragile items with extra bubble wrap..."></textarea>
            </div>
        </div>

        <div id="shipbox-form-message" class="mt-3"></div>
        <div class="text-center mt-5">
            <button type="submit" id="shipbox-submit-btn" class="btn text-white btn-lg px-5 py-2 fw-bold" style="background-color: #1a9c38 !important; border-radius: 8px !important; min-width: 200px !important; border: transparent !important; outline: transparent !important; box-shadow: none !important;">SUBMIT</button>
        </div>
    </form>
</div>

<script>
    const shipbox_wh_data = <?php echo json_encode($warehouse_settings); ?>;
</script>

<style>
    .add-merchant-row {
        color: #1a9c38 !important;
        background-color: transparent !important;
        border: none !important;
        text-decoration: none !important;
    }

    .remove-merchant-row, 
    .remove-merchant-row:hover, 
    .remove-merchant-row:focus, 
    .remove-merchant-row:active {
        background: transparent !important;
        background-color: transparent !important;
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        text-decoration: none !important;
        padding: 0 !important;
    }
    
    /* Control Line-Height and Alignment for the consolidation sub-text */
    label{
      padding-left:0 !important;
    }
    .consolidation-subtext {
        display: block !important;
        line-height: 1.4 !important;
        margin-top: 4px !important;
        padding-left: 0 !important;
        margin-left: 0 !important;
    }

    /* Fix for select padding and appearance on mobile/tablet */
    .form-select.shipbox-input-white {
        padding: 10px 35px 10px 15px !important;
    }

    /* Remove Bootstrap default indentation and float for checkboxes */
    .form-check-input {
        float: none !important;
        margin-top: 0 !important;
        border: 1px solid #ced4da !important;
    }

    .shipbox-input-styled {
        background-color: #ffffff !important;
        border: 1px solid #ced4da !important;
        border-radius: 8px !important;
        padding: 10px 15px !important;
        color: #6c757d !important;
    }

    .shipbox-input-white {
        background-color: #ffffff !important;
        border: 1px solid #ced4da !important;
        border-radius: 8px !important;
        padding: 10px 15px !important;
    }

    .form-label {
        color: #000 !important;
        font-size: 18px !important;
        font-weight: 400 !important;
    }

    .shipbox-container h1 {
        color: #000 !important;
    }

    .add-merchant-row:hover {
        color: #1a9c38 !important;
    }
    
    /* General button clean up and transparent states */
    button:focus, .btn:focus {
        outline: transparent !important;
        box-shadow: none !important;
        border-color: transparent !important;
    }

    #shipbox-submit-btn:hover {
        opacity: 0.9;
        color: #fff !important;
    }

    /* Mobile Specific spacing fix */
    @media (max-width: 768px) {
        .ms-3 {
            margin-left: 1rem !important;
        }
        .custom-label-size .form-label {
            font-size: 14px !important;
        }
    }
</style>
<?php endif; ?>