<?php
$warehouse_settings = get_option('shipbox_warehouse_settings', array());

$flags = [
    'USA'    => '🇺🇸',
    'UK'     => '🇬🇧',
    'TURKEY' => '🇹🇷',
];

if ( !isset($user_name) ) {
    echo '<div class="alert alert-warning">Account data is temporarily unavailable. Please contact support.</div>';
    return;
}
?>

<style>
    :root {
        --plugin-red: #CD0613;
        --plugin-green: #1A9C38;
        --plugin-brown: #E0A954;
        --plugin-purple: #224080;
    }

    .dashboard-card {
        background: #fff;
        border-radius: 8px !important;
        border: 1.5px solid #333 !important;
        overflow: hidden;
    }

    .copy-instruction {
        background: #FDF2E3; 
        font-size: 0.7rem; 
        font-weight: 700;
        letter-spacing: 0.5px;
        padding: 5px 0;
        margin-bottom: 15px;
        border-top: 1px solid #eee;
        border-bottom: 1px solid #eee;
        color: #856404;
    }

    /* Data Rows */
    .data-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        border-bottom: 1px solid #dee2e6;
        cursor: pointer;
    }
    .data-row:last-child { border-bottom: none; }

    .label-text {
        color: var(--plugin-green);
        font-weight: 700;
        font-size: 0.8rem;
    }

    .value-text {
        color: #333;
        font-size: 0.8rem;
        text-transform: uppercase;
        font-weight: 500;
        text-align:left;
    }

    /* Important Section */
    .important-container {
        max-width:600px;
        border-left: 1px solid #333;
        border-right: 1px solid #333;
        padding-left: 20px;
        padding-right: 10px;
        display: inline-block;
        text-align: left;
    }

    /* Dashboard Navigation Buttons */
    .dashboard-nav-btn {
        background: #fff;
        border: 2px solid var(--plugin-green);
        color: var(--plugin-green);
        font-weight: 800;
        font-size: 1.2rem;
        padding: 6px 40px;
        border-radius: 8px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.2s ease;
        text-transform: uppercase;
        margin: 5px;
        min-width: 200px;
    }

   

    .dashboard-nav-btn:hover {
        background: var(--plugin-green);
        color: #fff;
    }
    .my-padding {
        padding: 40px 20px;
    }

     /* make button full width on mobile */
    @media (max-width: 576px) {
        .dashboard-nav-btn {
            width: 100%;
            padding: 10px 0;
        }
        .my-padding {
        padding: 20px 10px;
    }
    }


</style>

<div class="">
    <h1 class="shipbox-page-title text-center">
        Dashboard
    </h1>

    <div class="container bg-white p-3 py-4 rounded-4 mb-4">
        <div style="background: #f3f2f2; border-radius: 8px; margin-bottom: 30px;" class="my-padding">
            <div class="row g-3 justify-content-center mb-5">
                        <?php 
                        foreach ($warehouse_settings as $key => $wh): 
                            
                            $country_code = strtoupper($wh['country_code'] ?? '');
                            $flag = $flags[$country_code] ?? '🌐';
                            
                            $display_label = ($country_code === 'USA') ? 'Tax Free Delaware' : 
                                            (($country_code === 'TURKEY') ? 'Turkey Istanbul' : 'United Kingdom');

                            $full_address_line_2 = trim($wh['address_line2_prefix'] ?? '');
                        ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card dashboard-card h-100">
                                <div class="card-body text-center p-0">
                                    <div class="pt-4">
                                        <span style="font-size: 2.5rem;"><?php echo $flag; ?></span>
                                        <h6 class="fw-bold mb-3"><?php echo esc_html($display_label); ?></h6>
                                    </div>
                                    
                                    <div class="copy-instruction">CLICK EACH LINE TO COPY</div>

                                    <div class="px-3 pb-3">
                                        <div class="data-row copyable" data-value="<?php echo esc_attr($user_name); ?>">
                                            <span class="value-text"><?php echo esc_html($user_name); ?></span>
                                            <span class="label-text">Full Name</span>
                                        </div>
                                        <div class="data-row copyable" data-value="<?php echo esc_attr($wh['address_line1'] ?? ''); ?>">
                                            <span class="value-text"><?php echo esc_html($wh['address_line1'] ?? ''); ?></span>
                                            <span class="label-text">Address line 1</span>
                                        </div>
                                        <div class="data-row copyable" data-value="<?php echo esc_attr($full_address_line_2); ?>">
                                            <span class="value-text"><?php echo esc_html($full_address_line_2); ?></span>
                                            <span class="label-text">Address line 2</span>
                                        </div>
                                        <div class="data-row copyable" data-value="<?php echo esc_attr($wh['city'] ?? ''); ?>">
                                            <span class="value-text"><?php echo esc_html($wh['city'] ?? ''); ?></span>
                                            <span class="label-text">City</span>
                                        </div>
                                        <div class="data-row copyable" data-value="<?php echo esc_attr($wh['state'] ?? ''); ?>">
                                            <span class="value-text"><?php echo esc_html($wh['state'] ?? ''); ?></span>
                                            <span class="label-text">State</span>
                                        </div>
                                        <div class="data-row copyable" data-value="<?php echo esc_attr($wh['zip_code'] ?? ''); ?>">
                                            <span class="value-text"><?php echo esc_html($wh['zip_code'] ?? ''); ?></span>
                                            <span class="label-text">Zip code</span>
                                        </div>
                                        <div class="data-row copyable" data-value="<?php echo esc_attr($wh['phone'] ?? ''); ?>">
                                            <span class="value-text"><?php echo esc_html($wh['phone'] ?? ''); ?></span>
                                            <span class="label-text">Phone</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
            </div>

            <div class="text-center mb-5">
                <div class="important-container">
                    <h6 class="fw-bold mb-0 pl-8" style="color: var(--plugin-red); padding-left:18px; font-size:1.1rem;">IMPORTANT:</h6>
                    <ol class="ps-3 my-0 fw-bold" style="font-size: 1.1rem; color: #000;">
                        <li>Make sure to enter the provided details in address line 2</li>
                        <li>Please fill and submit the Order Confirmation Form after placing your order with our address</li>
                    </ol>
                </div>
            </div>
        </div>
      

        <div class="text-center p-3" style="background: #E9E9E9; border-radius: 8px;">
            <a href="<?php echo home_url('/dashboard/?tab=shipment'); ?>" class="dashboard-nav-btn">ORDER CONFIRMATION</a>
            <a href="<?php echo home_url('/dashboard/?tab=profile'); ?>" class="dashboard-nav-btn">PROFILE</a>
            <a href="<?php echo home_url('/dashboard/?tab=history'); ?>" class="dashboard-nav-btn">ORDER HISTORY</a>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.copyable').forEach(item => {
    item.addEventListener('click', event => {
        const text = item.getAttribute('data-value');
        if(!text) return;
        navigator.clipboard.writeText(text).then(() => {
            const originalHtml = item.innerHTML;
            item.innerHTML = '<span style="color: var(--plugin-red); width: 100%; text-align: center; font-weight: bold; font-size: 0.8rem;">Copied!</span>';
            setTimeout(() => { item.innerHTML = originalHtml; }, 800);
        });
    });
});
</script>