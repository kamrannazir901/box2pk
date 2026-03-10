<?php
/**
 * ShipBox Frontend Shipping Calculator
 * File: includes/frontend-calculator.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ShipBox_Frontend_Calculator {

    public function __construct() {
        add_shortcode('shipbox_calculator', array($this, 'render_calculator'));
        add_action('wp_ajax_shipbox_calculate', array($this, 'ajax_calculate'));
        add_action('wp_ajax_nopriv_shipbox_calculate', array($this, 'ajax_calculate'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets() {
        wp_enqueue_style('bootstrap-5', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
        wp_enqueue_script('bootstrap-5', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.3.0', true);
        wp_enqueue_script('jquery');
        wp_enqueue_script('shipbox-calculator', plugin_dir_url(__FILE__) . '../public/js/shipbox-public.js', array('jquery'), '2.0.0', true);

        wp_localize_script('shipbox-calculator', 'shipboxAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('shipbox_calculate_nonce'),
        ));

        wp_add_inline_style('bootstrap-5', '
            :root {
                --plugin-red: #cd0613;
                --plugin-green: #1a9c38;
                --plugin-brown: #e0a954;
                --plugin-purple: #224080;
                --plugin-black: #050608;
            }
              
            select{
              padding:0 !important;
            }

            .shipbox-wrapper {
                max-width: 1140px;
                margin: 0 auto;
                font-size: 16px;
                line-height: 1.6;
                color: var(--plugin-black);
            }

            /* ── FORM AREA ── */
            .shipbox-form-section {
                background: #f5f5f5;
                padding: 40px 20px;
            }

            .shipbox-calc-title {
                font-size: 36px;
                font-weight: 900;
                text-transform: uppercase;
                text-align: center;
                letter-spacing: 1px;
                margin-bottom: 8px;
            }

            .shipbox-calc-subtitle {
                text-align: center;
                font-size: 15px;
                color: #444;
                max-width: 820px;
                margin: 0 auto 28px;
            }

            .shipbox-calc-subtitle a {
                color: var(--plugin-green);
                text-decoration: none;
            }

            /* Unit Toggle */
            .unit-toggle-wrap {
                display: flex;
                justify-content: center;
                gap: 16px;
                margin-bottom: 28px;
            }

            .unit-toggle-wrap label {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 16px;
                cursor: pointer;
            }

            .unit-toggle-wrap input[type="radio"] {
                accent-color: var(--plugin-green);
                width: 18px;
                height: 18px;
            }

            /* Fields Row */
            .shipbox-fields-row {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 12px;
                margin-bottom: 24px;
            }

            .shipbox-field-group {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .shipbox-field-group label {
                font-size: 15px;
                color: #333;
                white-space: nowrap;
            }

            .shipbox-select,
            .shipbox-input {
                border: 2px solid #494949 !important;
                border-radius: 6px !important;
                padding: 2px 14px !important;
                font-size: 15px !important;
                background: #fff !important;
                outline: none !important;
            }

         

            /* ── PACKAGE CARDS ── */
            #packagesContainer {
                max-width: 800px;
                margin: 0 auto;
            }

            /* Card shell */
            .package-item {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                margin-bottom: 12px;
                overflow: hidden;
            }

            /* Card header: title + remove button on one line */
            .package-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 16px;
                background: #f8f8f8;
                border-bottom: 1px solid #e5e5e5;
            }

            .package-label {
                font-size: 16px;
                font-weight: 700;
                text-transform: uppercase;
                color: var(--plugin-black);
            }

            .btn-remove-package {
                background: none;
                border: none;
                color: #aaa;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                padding: 2px 0;
                line-height: 1;
            }

            .btn-remove-package:hover { color: var(--plugin-red); }

            /* Card body */
            .package-body {
                padding: 14px 16px;
                display: flex;
                flex-wrap: wrap;
                gap: 14px;
                align-items: center;
                justify-content:space-between;
            }

            .package-field-group {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 14px;
            }

            /* Mobile: inputs go full-width stacked */
            .pkg-input {
                border: 1px solid #696969 !important;
                border-radius: 5px !important;
                padding: 4px 10px !important;
                font-size: 14px !important;
                background: #fff !important;
                width: 100% !important;
            }

            .pkg-input:focus {
                border-color: var(--plugin-red);
                outline: none;
            }

            /* Mobile: each field group takes full row */
            @media (max-width: 767px) {
                .package-body {
                    flex-direction: column;
                    align-items: stretch;
                    gap: 10px;
                }
                .package-field-group {
                    flex-direction: column;
                    align-items: flex-start;
                    width: 100%;
                }
                .package-field-group .pkg-input {
                    width: 100%;
                }
                /* The × separators inline on mobile dims */
                .package-field-group .dim-sep {
                    display: none;
                }
                .package-field-group.dims-group {
                    flex-direction: row;
                    flex-wrap: wrap;
                    align-items: center;
                    gap: 6px;
                }
                .package-field-group.dims-group .pkg-input {
                    width: calc(33% - 10px);
                    min-width: 60px;
                }
            }

            /* Desktop: restore fixed widths */
            @media (min-width: 768px) {
                .pkg-input { width: auto; }
                .weight-input { width: 80px !important; }
                .dim-input    { width: 70px !important; }
            }

            .add-pkg-row {
                display: flex;
                justify-content: flex-end;
                max-width: 800px;
                margin: 0 auto 20px;
            }

            #addPackageBtn {
                background: none;
                border: none;
                color: var(--plugin-black);
                font-size: 15px;
                font-weight: 700;
                text-transform: uppercase;
                cursor: pointer;
                padding: 6px 0;
            }

            #addPackageBtn:hover { color: var(--plugin-red); }

            /* Get Estimate Button */
            .get-estimate-wrap {
                text-align: center;
                margin: 10px 0 0;
            }

            .get-estimate-btn {
                background: var(--plugin-red) !important;
                color: #fff !important;
                border: none;
                border-radius: 100px !important;
                padding: 4px 36px !important;
                font-size: 20px !important;
                font-weight: 700 !important;
                text-transform: uppercase;
                letter-spacing: 2px;
                cursor: pointer;
                transition: background 0.2s;
            }

            .get-estimate-btn:hover { background: #a0040f !important; }
            .get-estimate-btn:disabled { opacity: 0.6; cursor: not-allowed; }

            .disclaimer-text {
                text-align: center;
                font-size: 14px;
                margin-top: 14px;
            }

            /* ── RESULTS AREA ── */
            .shipbox-results-section {
                // background: #fff;
                padding: 40px 20px;
            }

            .modify-btn {
                background: #D9AC6B;
                color: #fff;
                border: none;
                border-radius: 4px;
                padding: 4px 16px;
                font-size: 14px;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 1px;
                cursor: pointer;
            }

            .modify-btn:hover { background: #c4933e; }

            /* Summary header row */
            .result-summary-item {
                text-align: left;
                margin-bottom: 15px;
            }

            .result-header-label {
                font-size: 18px;
                font-weight: 600;
                text-transform: uppercase;
                color: #222;
                margin-bottom: 4px;
            }

            .result-value {
                font-size: 18px;
                font-weight:500;
                color: var(--plugin-green);
            }

            /* Total price cell gets extra emphasis on mobile */
            .result-value-price {
                font-size: 22px;
                font-weight: 800;
                color: var(--plugin-green);
            }

            @media (min-width: 768px) {
                .result-summary-item {
                    text-align: center;
                    margin-bottom: 0;
                }
                .result-value-price {
                    font-size: 18px;
                    font-weight: 400;
                }
            }

            /* Big price */
            .price-main {
                font-size: 32px;
                font-weight: 600;
                color: var(--plugin-green);
            }

            .days-badge {
                display: inline-block;
                background: #fffacd;
                color: #333;
                font-size: 17px;
                font-weight: 700;
                padding: 2px 16px;
                border-radius: 4px;
                vertical-align: middle;
            }

            /* Term rows */
            .term-row {
                display: flex;
                flex-wrap: wrap;
                padding: 14px 0;
                border-bottom: 1px solid #e0e0e0;
                font-size: 17px;
            }
                .duties-text{
                text-align:center;
                font-size: 24px !important;      /* Standard professional heading size */
    color: #000000 !important;       /* Pure black as requested */
    font-weight: 700 !important;      /* Bold for hierarchy */
    line-height: 1.2 !important;      /* Tight line height for titles */
    margin-bottom: 20px !important;   /* Spacing from content below */
    letter-spacing: -0.02em !important; /* Slight tightening for a premium look */
    text-transform: none !important;
                }

            .term-row:last-child { border-bottom: none; }
            .term-label { font-size: 20px; font-weight: 700; color: #111; width: 260px; flex-shrink: 0; }
            .term-value { font-size: 20px; line-height:1.3;  flex: 1; }

            /* Mobile globals */
            @media (max-width: 767px) {
                .shipbox-calc-title { font-size: 26px; }
                .price-main { font-size: 36px; }
                .term-label { width: 100%; margin-bottom: 4px; }
                .shipbox-fields-row { flex-direction: column; align-items: stretch; }
                .shipbox-field-group { flex-wrap: wrap; }
                .shipbox-select, .shipbox-input { width: 100%; }
            }
        ');
    }

    public function render_calculator() {
        global $wpdb;
        $cities = $wpdb->get_results("SELECT DISTINCT city_name FROM {$wpdb->prefix}shipbox_cities ORDER BY city_name ASC");
        $warehouse_settings = get_option('shipbox_warehouse_settings', array());
        $enabled_warehouses = array();

        if (is_array($warehouse_settings)) {
            foreach ($warehouse_settings as $country => $settings) {
                if (isset($settings['enabled']) && $settings['enabled'] === '1') {
                    $enabled_warehouses[] = $country;
                }
            }
        }

        if (empty($enabled_warehouses)) {
            $enabled_warehouses = array('USA', 'UK', 'Turkey');
        }

        ob_start();
        ?>
        <div class="shipbox-wrapper">

            <!-- ═══ FORM SECTION ═══ -->
            <div class="shipbox-form-section" id="shipboxFormSection">
                <h1 class="shipbox-page-title text-center">Shipping Cost Calculator</h1>
                <p class="shipbox-subtitle fw-normal lh-sm fs-6">
                    Height, length, and width are required to calculate volumetric weight, which shipping companies use to
                    charge based on parcel size. Most parcels are billed by volumetric weight unless they are unusually heavy.
                    Please review our <a href="https://box2pk.com/restricted/">prohibited and restricted items</a> page before shipping.
                </p>

                <form id="shipboxCalculatorForm">

                    <!-- Unit Toggle -->
                    <div class="unit-toggle-wrap">
                        <label>
                            <input type="radio" name="unit_system" value="metric" checked> kgs
                        </label>
                        <label>
                            <input type="radio" name="unit_system" value="imperial"> lbs
                        </label>
                    </div>

                    <!-- Country / Destination Row -->
                    <div class="shipbox-fields-row">
                        <div class="shipbox-field-group">
                            <label for="shoppingCountry">Country of shopping</label>
                            <select class="shipbox-select" id="shoppingCountry" name="shopping_country" required style="min-width:180px;">
                                <option value="">— Select —</option>
                                <?php if (in_array('USA', $enabled_warehouses)): ?>
                                    <option value="USA">United States</option>
                                <?php endif; ?>
                                <?php if (in_array('UK', $enabled_warehouses)): ?>
                                    <option value="UK">United Kingdom</option>
                                <?php endif; ?>
                                <?php if (in_array('Turkey', $enabled_warehouses)): ?>
                                    <option value="Turkey">Turkey</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="shipbox-field-group">
                            <label>Destination</label>
                            <input type="text" class="shipbox-input" value="Pakistan" readonly style="width:130px; background:#f0f0f0;">
                        </div>

                        <div class="shipbox-field-group">
                            <select class="shipbox-select" id="destinationCity" name="destination_city" required style="min-width:160px;">
                                <option value="">City</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo esc_attr($city->city_name); ?>">
                                        <?php echo esc_html($city->city_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Packages -->
                    <div id="packagesContainer">
                        <div class="package-item" data-package-id="1">
                            <div class="package-header">
                                <span class="package-label">Package 1</span>
                            </div>
                            <div class="package-body">
                                <div class="package-field-group">
                                    <span>Weight (<span class="weight-unit">kg</span>):</span>
                                    <input type="number" step="0.01" name="packages[0][weight]" class="pkg-input package-weight weight-input" placeholder="0.00">
                                </div>
                                <div class="package-field-group dims-group">
                                    <span>Dimensions (<span class="dim-unit">cm</span>):</span>
                                    <input type="number" step="0.1" name="packages[0][length]" class="pkg-input package-length dim-input" placeholder="L">
                                    <span class="dim-sep">×</span>
                                    <input type="number" step="0.1" name="packages[0][width]"  class="pkg-input package-width  dim-input" placeholder="W">
                                    <span class="dim-sep">×</span>
                                    <input type="number" step="0.1" name="packages[0][height]" class="pkg-input package-height dim-input" placeholder="H">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="add-pkg-row">
                        <button type="button" id="addPackageBtn">+ ADD PACKAGE</button>
                    </div>

                    <div class="get-estimate-wrap">
                        <button type="submit" class="get-estimate-btn">GET ESTIMATE</button>
                    </div>

                    <p class="disclaimer-text">
                        <strong>Disclaimer:</strong> Shipping charges are estimates and may change based on the merchant's final packaging. They do not include customs duty.
                    </p>
                    <h2 class="duties-text">
                      Stay informed — see the duties before you pay
                    </h2>
                </form>
            </div><!-- /form section -->

            <!-- ═══ RESULTS SECTION ═══ -->
            <div class="shipbox-results-section" id="resultsSection" style="display:none;">
                <div id="resultsContainer"></div>
            </div>

        </div><!-- /shipbox-wrapper -->
        <?php
        return ob_get_clean();
    }

    public function ajax_calculate() {
        check_ajax_referer('shipbox_calculate_nonce', 'nonce');

        global $wpdb;

        $shopping_country = sanitize_text_field($_POST['shopping_country']);
        $destination_city = sanitize_text_field($_POST['destination_city']);
        $unit_system      = sanitize_text_field($_POST['unit_system']);
        $packages         = isset($_POST['packages']) ? $_POST['packages'] : array();

        if (empty($packages)) {
            wp_send_json_error(array('message' => 'No packages provided'));
            return;
        }

        // Volumetric divisor
        $divisor = ($unit_system === 'metric')
            ? floatval(get_option('shipbox_divisor_cm', 5000))
            : floatval(get_option('shipbox_divisor_inch', 139));

        $total_actual_weight     = 0;
        $total_display_weight    = 0;
        $total_volumetric_weight = 0;
        $total_chargeable_weight = 0;
        $package_details         = array();

        foreach ($packages as $pkg) {
            $weight = floatval($pkg['weight'] ?? 0);
            $length = floatval($pkg['length'] ?? 0);
            $width  = floatval($pkg['width']  ?? 0);
            $height = floatval($pkg['height'] ?? 0);

            $total_display_weight += floatval($pkg['weight'] ?? 0);

            $has_weight = $weight > 0;
            $has_dims   = $length > 0 && $width > 0 && $height > 0;

            // Need at least one of weight or full dimensions
            if (!$has_weight && !$has_dims) {
                wp_send_json_error(array('message' => 'Each package must have at least a weight or full dimensions.'));
                return;
            }

            if ($unit_system === 'imperial') {
                $vol_weight_lbs = $has_dims ? ($length * $width * $height) / $divisor : 0;
                $actual_lbs     = $has_weight ? $weight : 0;
                $chargeable_lbs = max($actual_lbs, $vol_weight_lbs);

                $weight_kg     = $actual_lbs * 0.453592;
                $vol_weight_kg = $vol_weight_lbs * 0.453592;
                $chargeable_kg = $chargeable_lbs * 0.453592;
            } else {
                $vol_weight_kg = $has_dims ? ($length * $width * $height) / $divisor : 0;
                $weight_kg     = $has_weight ? $weight : 0;
                $chargeable_kg = max($weight_kg, $vol_weight_kg);
            }

            $total_actual_weight     += $weight_kg;
            $total_volumetric_weight += $vol_weight_kg;
            $total_chargeable_weight += $chargeable_kg;

            $package_details[] = array(
                'original_w'  => $pkg['weight'] ?? '',
                'original_l'  => $pkg['length'] ?? '',
                'original_wi' => $pkg['width']  ?? '',
                'original_h'  => $pkg['height'] ?? '',
                'actual'      => round($weight_kg, 2),
                'volumetric'  => round($vol_weight_kg, 2),
                'chargeable'  => round($chargeable_kg, 2),
            );
        }

        // Exchange rates & overflow rates
        $usd_to_pkr            = floatval(get_option('shipbox_usd_to_pkr', 278.50));
        $gbp_to_pkr            = floatval(get_option('shipbox_gbp_to_pkr', 355.00));
        $add_kg_usa            = floatval(get_option('shipbox_add_kg_usa', 8.00));
        $add_kg_uk             = floatval(get_option('shipbox_add_kg_uk', 21.51));
        $add_kg_turkey         = floatval(get_option('shipbox_add_kg_turkey', 15.00));
        $add_kg_karachi        = floatval(get_option('shipbox_add_kg_karachi', 1.00));
        $add_kg_other          = floatval(get_option('shipbox_add_kg_other_cities', 1.72));

        $is_karachi            = (strtolower($destination_city) === 'karachi');
        $domestic_overflow_rate = $is_karachi ? $add_kg_karachi : $add_kg_other;

        $services      = array();
        $service_types = $this->get_service_types($shopping_country, $total_chargeable_weight);

        foreach ($service_types as $service_type) {
            // International slab lookup
            $int_slab = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}shipbox_weight_slabs
                 WHERE service_type = %s AND weight_min <= %f AND weight_max >= %f
                 ORDER BY weight_max DESC LIMIT 1",
                $service_type,
                $total_chargeable_weight,
                $total_chargeable_weight
            ));

            $international_cost_usd = 0;
            $int_breakdown          = '';

            if ($int_slab) {
                if ($int_slab->currency === 'USD') {
                    $international_cost_usd = floatval($int_slab->price);
                } elseif ($int_slab->currency === 'GBP') {
                    $international_cost_usd = floatval($int_slab->price) * ($gbp_to_pkr / $usd_to_pkr);
                }
                $int_breakdown = "Slab Price: {$int_slab->currency} " . number_format($int_slab->price, 2);
            } else {
                // Overflow: weight exceeds max slab
                $max_slab = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}shipbox_weight_slabs
                     WHERE service_type = %s
                     ORDER BY weight_max DESC LIMIT 1",
                    $service_type
                ));

                if ($max_slab) {
                    $base_price = 0;
                    if ($max_slab->currency === 'USD') {
                        $base_price = floatval($max_slab->price);
                    } elseif ($max_slab->currency === 'GBP') {
                        $base_price = floatval($max_slab->price) * ($gbp_to_pkr / $usd_to_pkr);
                    }

                    $excess_weight = $total_chargeable_weight - floatval($max_slab->weight_max);
                    $overflow_rate = 0;

                    if (strpos($service_type, 'usa') !== false) {
                        $overflow_rate = $add_kg_usa;
                    } elseif (strpos($service_type, 'uk') !== false) {
                        $overflow_rate = $add_kg_uk;
                    } elseif (strpos($service_type, 'turkey') !== false) {
                        $overflow_rate = $add_kg_turkey;
                    }

                    $international_cost_usd = $base_price + ($excess_weight * $overflow_rate);
                    $int_breakdown = "Max Slab ({$max_slab->weight_max}kg): USD " . number_format($base_price, 2)
                        . " + Overflow (" . number_format($excess_weight, 2) . "kg × USD " . number_format($overflow_rate, 2) . ")";
                }
            }

            // Domestic slab lookup
            $dom_slab = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}shipbox_cities
                 WHERE city_name = %s AND weight_min <= %f AND weight_max >= %f
                 ORDER BY weight_max DESC LIMIT 1",
                $destination_city,
                $total_chargeable_weight,
                $total_chargeable_weight
            ));

            $domestic_cost_usd = 0;
            $dom_breakdown     = '';

            if ($dom_slab) {
                $domestic_cost_usd = floatval($dom_slab->price);
                $dom_breakdown     = "City Rate: USD " . number_format($dom_slab->price, 2);
            } else {
                $max_dom_slab = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}shipbox_cities
                     WHERE city_name = %s
                     ORDER BY weight_max DESC LIMIT 1",
                    $destination_city
                ));

                if ($max_dom_slab) {
                    $base_dom          = floatval($max_dom_slab->price);
                    $excess_dom        = $total_chargeable_weight - floatval($max_dom_slab->weight_max);
                    $domestic_cost_usd = $base_dom + ($excess_dom * $domestic_overflow_rate);
                    $dom_breakdown     = "Max Slab ({$max_dom_slab->weight_max}kg): USD " . number_format($base_dom, 2)
                        . " + Overflow (" . number_format($excess_dom, 2) . "kg × USD " . number_format($domestic_overflow_rate, 2) . ")";
                }
            }

            $total_usd = $international_cost_usd + $domestic_cost_usd;
            $total_pkr = $total_usd * $usd_to_pkr;

            $services[] = array(
                'service_name'           => $this->format_service_name($service_type),
                'service_type'           => $service_type,
                'international_cost_usd' => $international_cost_usd,
                'domestic_cost_usd'      => $domestic_cost_usd,
                'total_usd'              => $total_usd,
                'total_pkr'              => $total_pkr,
                'int_breakdown'          => $int_breakdown,
                'dom_breakdown'          => $dom_breakdown,
            );
        }

        if (empty($services)) {
            wp_send_json_error(array('message' => 'No services available for this configuration'));
            return;
        }

        wp_send_json_success(array(
            'services' => $services,
            'details'  => array(
                'country'                  => $shopping_country,
                'city'                     => $destination_city,
                'total_packages'           => count($packages),
                'total_display_weight'     => $total_display_weight,
                'total_actual_weight'      => round($total_actual_weight, 2),
                'total_volumetric_weight'  => round($total_volumetric_weight, 2),
                'total_chargeable_weight'  => round($total_chargeable_weight, 2),
                'package_details'          => $package_details,
                'unit_system'              => $unit_system,
            )
        ));
    }

    /**
     * Returns service types for a given country.
     * usa_economy has been intentionally removed.
     */
    private function get_service_types($country, $weight) {
        $services = array();

        if ($country === 'USA') {
            $services[] = 'usa_express';
        } elseif ($country === 'UK') {
            $services[] = 'uk';
        } elseif ($country === 'Turkey') {
            $services[] = 'turkey';
        }

        return $services;
    }

    private function format_service_name($service_type) {
        $names = array(
            'usa_express' => 'USA Express',
            'uk'          => 'UK Standard',
            'turkey'      => 'Turkey Standard',
        );
        return isset($names[$service_type]) ? $names[$service_type] : ucfirst(str_replace('_', ' ', $service_type));
    }
}

new ShipBox_Frontend_Calculator();