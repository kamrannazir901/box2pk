<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wecodely.com
 * @since      1.0.0
 *
 * @package    Shipbox
 * @subpackage Shipbox/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Shipbox
 * @subpackage Shipbox/admin
 * @author     Kamran Nazir <kamrannazir901@gmail.com>
 */
class Shipbox_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Shipbox_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Shipbox_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/shipbox-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Shipbox_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Shipbox_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/shipbox-admin.js', array( 'jquery' ), $this->version, false );

	}
 
	/**
     * Register the menus for the admin area.
     */
	public function add_shipbox_menus() {
		// Main Parent Menu
		add_menu_page(
			'ShipBox Manager',
			'ShipBox',
			'manage_options',
			'shipbox-manager',
			array($this, 'route_all_shipments_page'), 
			'dashicons-groups',
			6
		);

		// Submenu: All Shipments
		add_submenu_page(
			'shipbox-manager',
			'All Shipments',
			'All Shipments',
			'manage_options',
			'shipbox-manager',
			array( $this, 'route_all_shipments_page' )
		);

		// Submenu: Customers
		add_submenu_page(
			'shipbox-manager',
			'Customers',
			'Customers',
			'manage_options',
			'shipbox-customers',
			array($this, 'route_customers_page')
		);

		// Hidden submenu for customer shipments
		add_submenu_page(
			null,                           // Parent slug (null = hidden)
			'Customer Shipments',           // Page title
			'Customer Shipments',           // Menu title
			'manage_options',               // Capability
			'shipbox-customer-shipments',   // Menu slug
			array($this, 'route_customer_shipments_page') // Fixed: use array callback
		);

		

		

		add_submenu_page(
			'shipbox-manager',
			'Rate Slabs',
			'Rate Slabs',
			'manage_options',
			'shipbox-slabs',
			array($this, 'manage_slabs_page')
		);

		add_submenu_page(
			'shipbox-manager',
			'Manage Cities',
			'Cities & Domestic',
			'manage_options',
			'shipbox-cities',
			array($this, 'manage_cities_page') // This calls your CRUD method
		);

		// Hidden submenu for order details
		add_submenu_page(
			null, // null = hidden from menu
			'Order Details',
			'Order Details',
			'manage_options',
			'shipbox-order-detail',
			array($this, 'route_order_detail_page')
		);

			add_submenu_page(
			'shipbox-manager',
			'ShipBox Settings',
			'Settings',
			'manage_options',
			'shipbox-settings',
			array($this, 'route_settings_page')
		);

		add_submenu_page(
			'shipbox-manager',
			'Email Templates',
			'Email Settings',
			'manage_options',
			'shipbox-emails',
			array($this, 'route_email_settings_page')
		);
	}
 

	public function route_email_settings_page() {
		// Save Logic (Includes Subjects)
		if (isset($_POST['shipbox_save_emails']) && check_admin_referer('shipbox_email_action', 'shipbox_email_nonce')) {
			update_option('shipbox_email_templates', $_POST['email_templates']);
			update_option('shipbox_email_subjects', $_POST['email_subjects']); 
			echo '<div class="updated"><p>Status Email Templates & Subjects Saved!</p></div>';
		}

		$templates = get_option('shipbox_email_templates', []);
		$subjects  = get_option('shipbox_email_subjects', []);
		
		// Only Automated Statuses
		$statuses = [
			'address_usage_confirmation' => 'Address Usage Confirmation',
			'package_received_partial'    => 'Package Received (Partial)',
			'package_received_single'     => 'Package Received (Single)',
			'in_transit'                  => 'In Transit / Ready to Dispatch',
			'arrived_karachi'             => 'Arrived in Karachi',
			'delivered'                   => 'Out for Delivery / Delivered'
		];
		?>
		<div class="wrap">
			<h1>ShipBox Email Settings</h1>
			<div style="background: #e7f5ec; border-left: 4px solid #009640; padding: 10px 15px; margin-bottom: 20px;">
				<p style="margin: 0;"><strong>Available Tags:</strong> <code>{customer_name}</code>, <code>{customer_id}</code>, <code>{order_number}</code>, <code>{shipping_price}</code>, <code>{final_price}</code>, <code>{invoice_link}</code>, <code>{warehouse_address}</code></p>
			</div>
			
			<form method="post">
				<?php wp_nonce_field('shipbox_email_action', 'shipbox_email_nonce'); ?>
				
				<table class="form-table">
					<?php foreach ($statuses as $key => $label) : 
						$content = isset($templates[$key]) ? $templates[$key] : '';
						$subject = isset($subjects[$key]) ? $subjects[$key] : '';
					?>
						<tr valign="top" style="border-bottom: 1px solid #ddd;">
							<th scope="row" style="width: 200px;">
								<strong><?php echo esc_html($label); ?></strong>
								<br><small style="color: #666;">Slug: <?php echo $key; ?></small>
							</th>
							<td>
								<label><strong>Subject Line:</strong></label><br>
								<input type="text" name="email_subjects[<?php echo $key; ?>]" value="<?php echo esc_attr($subject); ?>" class="large-text" style="margin-bottom:10px;" placeholder="Enter subject for <?php echo $label; ?>..."><br>
								
								<label><strong>Email Body:</strong></label>
								<?php 
								wp_editor($content, "email_templates_$key", array(
									'textarea_name' => "email_templates[$key]",
									'media_buttons' => false,
									'textarea_rows' => 6,
									'teeny'         => true,
									'quicktags'     => true
								)); 
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<p class="submit">
					<input type="submit" name="shipbox_save_emails" class="button button-primary button-large" value="Save All Email Templates">
				</p>
			</form>
		</div>
		<?php
	}


	public function handle_admin_invoice_download() {
		$order_no = isset($_GET['order_no']) ? sanitize_text_field($_GET['order_no']) : '';
		require_once dirname( plugin_dir_path( __FILE__ ) ) . '/includes/class-shipbox-invoice.php';
		ShipBox_Invoice::generate_invoice( $order_no );
		exit;
	}
	
	/**
 * Handles the deletion of a shipment via admin_init hook.
 * This runs before any HTML is sent to the browser.
 */
  public function shipbox_handle_delete_redirect() {
      // 1. Only run if we are on the specific shipments page and a delete action is requested
      if (!isset($_GET['page']) || $_GET['page'] !== 'shipbox-customer-shipments' || !isset($_GET['action']) || $_GET['action'] !== 'delete') {
          return;
      }

      // 2. Security Check (Nonce and ID)
      $shipment_id = isset($_GET['shipment_id']) ? intval($_GET['shipment_id']) : 0;
      
      if (!$shipment_id || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_shipment_' . $shipment_id)) {
          wp_die('Security check failed.');
      }

      global $wpdb;
      $table_name = $wpdb->prefix . 'shipbox_orders';

      // 3. Perform Deletion
      $deleted = $wpdb->delete(
          $table_name,
          array('id' => $shipment_id),
          array('%d')
      );

      if ($deleted !== false) {
          // 4. Redirect to the list view with a success message
          $redirect_url = add_query_arg([
              'page'        => 'shipbox-customer-shipments',
              'customer_id' => isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0,
              'message'     => 'deleted'
          ], admin_url('admin.php'));

          wp_redirect($redirect_url);
          exit;
      }
  }
	/**
	 * Route to Customers Page
	 */
	public function route_customers_page() {
		require_once plugin_dir_path(__FILE__) . 'class-shipbox-customer-table.php';
		$customer_table = new Shipbox_Customer_Table();
		$customer_table->render_page();
	}

	/**
	 * Route to Customer Shipments Page
	 */
	public function route_customer_shipments_page() {
		require_once plugin_dir_path(__FILE__) . 'class-shipbox-customer-shipments-table.php';
		$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
		$shipments_table = new Shipbox_Customer_Shipments_Table($customer_id);
		$shipments_table->render_page();
	}
	
	

	/**
	 * Route to All Shipments Page
	 */
	public function route_all_shipments_page() {
		require_once plugin_dir_path( __FILE__ ) . 'class-shipbox-admin-controller.php';
		$controller = new Shipbox_Admin_Controller();
		$controller->render_shipments_list();
	}

	/**
	 * Route to Order Detail Page
	 */
	public function route_order_detail_page() {
		require_once plugin_dir_path(__FILE__) . 'class-shipbox-admin-controller.php';
		$controller = new ShipBox_Admin_Controller();
		$controller->render_order_detail();
	}

	

	public function route_settings_page() {
		// 1. HANDLE SAVING ALL SETTINGS
		if ( isset($_POST['shipbox_save_settings']) && check_admin_referer('shipbox_settings_nonce') ) {
			
			$payment_settings = array(
				'enable_online_payment' => isset($_POST['enable_online_payment']) ? true : false,
				'stripe_test_mode'      => isset($_POST['stripe_test_mode']) ? true : false,
				'test_publishable_key'  => sanitize_text_field($_POST['stripe_test_pub']),
				'test_secret_key'       => sanitize_text_field($_POST['stripe_test_sec']),
				'live_publishable_key'  => sanitize_text_field($_POST['stripe_live_pub']),
				'live_secret_key'       => sanitize_text_field($_POST['stripe_live_sec']),
			);
			update_option('shipbox_payment_settings', $payment_settings);

			// Save Global Rates & Divisors
			update_option('shipbox_usd_to_pkr', sanitize_text_field($_POST['usd_to_pkr']));
			update_option('shipbox_gbp_to_pkr', sanitize_text_field($_POST['gbp_to_pkr']));
			update_option('shipbox_divisor_cm', intval($_POST['divisor_cm']));
			update_option('shipbox_divisor_inch', intval($_POST['divisor_inch']));

			// Save Additional KG Rates
			update_option('shipbox_add_kg_usa', floatval($_POST['add_kg_usa']));
			update_option('shipbox_add_kg_uk', floatval($_POST['add_kg_uk']));
			update_option('shipbox_add_kg_turkey', floatval($_POST['add_kg_turkey']));
			update_option('shipbox_add_kg_karachi', floatval($_POST['add_kg_karachi']));
			update_option('shipbox_add_kg_other_cities', floatval($_POST['add_kg_other_cities']));

			// --- DYNAMIC WAREHOUSE SAVING ---
			// We pull the keys currently in the database so we handle any number of warehouses (4, 5, etc.)
			$current_settings = get_option('shipbox_warehouse_settings', array());
			$updated_warehouses = array();

			foreach ( $current_settings as $key => $existing_data ) {
				if ( isset($_POST['warehouse'][$key]) ) {
					$post_data = $_POST['warehouse'][$key];
					$updated_warehouses[$key] = array(
						'address_line1'        => sanitize_text_field($post_data['address_line1']),
						'address_line2_prefix' => sanitize_text_field($post_data['address_line2_prefix']),
						'city'                 => sanitize_text_field($post_data['city']),
						'state'                => sanitize_text_field($post_data['state']),
						'zip_code'             => sanitize_text_field($post_data['zip_code']),
						'phone'                => sanitize_text_field($post_data['phone']),
						// We preserve internal flags/codes from the default array
						'country_code'         => $existing_data['country_code'],
						'warehouse_notes'      => sanitize_textarea_field($post_data['warehouse_notes'])
					);
				}
			}
			update_option('shipbox_warehouse_settings', $updated_warehouses);

			echo '<div class="updated"><p>All settings updated successfully.</p></div>';
    }

    // 2. GET CURRENT DATA
    $usd_pkr = get_option('shipbox_usd_to_pkr');
    $gbp_pkr = get_option('shipbox_gbp_to_pkr');
    $div_cm  = get_option('shipbox_divisor_cm');
    $div_in  = get_option('shipbox_divisor_inch');

    $add_usa = get_option('shipbox_add_kg_usa', 8.00);
    $add_uk  = get_option('shipbox_add_kg_uk', 21.51);
    $add_tr  = get_option('shipbox_add_kg_turkey', 15.00);
    $add_khi = get_option('shipbox_add_kg_karachi', 1.00);
    $add_oth = get_option('shipbox_add_kg_other_cities', 1.72);

    $warehouse_settings = get_option('shipbox_warehouse_settings', array());

    ?>
    <div class="wrap">
        <h1>ShipBox Global Settings</h1>
        <form method="post" action="">
            <?php wp_nonce_field('shipbox_settings_nonce'); ?>

            <h2 class="title">Exchange Rates & Calculation Rules</h2>
            <table class="form-table">
                <tr>
                    <th>USD to PKR</th>
                    <td><input type="number" step="0.01" name="usd_to_pkr" value="<?php echo $usd_pkr; ?>"></td>
                    <th>Divisor (CM)</th>
                    <td><input type="number" name="divisor_cm" value="<?php echo $div_cm; ?>"></td>
                </tr>
                <tr>
                    <th>GBP to PKR</th>
                    <td><input type="number" step="0.01" name="gbp_to_pkr" value="<?php echo $gbp_pkr; ?>"></td>
                    <th>Divisor (Inches)</th>
                    <td><input type="number" name="divisor_inch" value="<?php echo $div_in; ?>"></td>
                </tr>
            </table>

            <h2 class="title">Additional KG Rates (Above 1.0 KG)</h2>
            <table class="form-table">
                <tr>
                    <th>USA Add. KG ($)</th>
                    <td><input type="number" step="0.01" name="add_kg_usa" value="<?php echo $add_usa; ?>"></td>
                    <th>Karachi Add. KG ($)</th>
                    <td><input type="number" step="0.01" name="add_kg_karachi" value="<?php echo $add_khi; ?>"></td>
                </tr>
                <tr>
                    <th>UK Add. KG (£)</th>
                    <td><input type="number" step="0.01" name="add_kg_uk" value="<?php echo $add_uk; ?>"></td>
                    <th>Other Cities Add. KG ($)</th>
                    <td><input type="number" step="0.01" name="add_kg_other_cities" value="<?php echo $add_oth; ?>"></td>
                </tr>
                <tr>
                    <th>Turkey Add. KG ($)</th>
                    <td><input type="number" step="0.01" name="add_kg_turkey" value="<?php echo $add_tr; ?>"></td>
                    <td></td>
                </tr>
            </table>


		<?php

			// 1. UPDATED DATA RETRIEVAL
			$payment_settings = get_option('shipbox_payment_settings', array(
				'enable_online_payment' => false,
				'stripe_test_mode'      => true,
				'test_publishable_key'  => '',
				'test_secret_key'       => '',
				'live_publishable_key'  => '',
				'live_secret_key'       => ''
			));
		?>
			<h2 class="title">Payment Settings</h2>
			<table class="form-table">
				<tr>
				<th>Enable Online Payments</th>
				<td><input type="checkbox" name="enable_online_payment" value="1" <?php checked(true, $payment_settings['enable_online_payment']); ?>></td>
				</tr>
				<tr>
				<th>Stripe Test Mode</th>
				<td><input type="checkbox" name="stripe_test_mode" value="1" <?php checked(true, $payment_settings['stripe_test_mode']); ?>></td>
				</tr>
				<tr>
				<td colspan="2"><hr><h3>Test Credentials</h3></td>
				</tr>
				<tr>
				<th>Test Publishable Key</th>
				<td><input type="text" name="stripe_test_pub" value="<?php echo esc_attr($payment_settings['test_publishable_key']); ?>" class="large-text"></td>
				</tr>
				<tr>
				<th>Test Secret Key</th>
				<td><input type="password" name="stripe_test_sec" value="<?php echo esc_attr($payment_settings['test_secret_key']); ?>" class="large-text"></td>
				</tr>
				<tr>
				<td colspan="2"><hr><h3>Live Credentials</h3></td>
				</tr>
				<tr>
				<th>Live Publishable Key</th>
				<td><input type="text" name="stripe_live_pub" value="<?php echo esc_attr($payment_settings['live_publishable_key']); ?>" class="large-text"></td>
				</tr>
				<tr>
				<th>Live Secret Key</th>
				<td><input type="password" name="stripe_live_sec" value="<?php echo esc_attr($payment_settings['live_secret_key']); ?>" class="large-text"></td>
				</tr>
			</table>
			
		
            <hr>

            <?php foreach ( $warehouse_settings as $key => $data ) : ?>
                <div class="postbox" style="padding: 15px; margin-top: 20px;">
                    <h2 style="margin:0 0 15px 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                        <?php 
                            // Convert 'usa_economy' to 'USA ECONOMY' for display
                            echo str_replace('_', ' ', strtoupper($key)); 
                        ?> Warehouse
                       
                    </h2>
                    
                    <table class="form-table">
                        <tr>
                            <th>Address Line 1</th>
                            <td><input type="text" name="warehouse[<?php echo $key; ?>][address_line1]" value="<?php echo esc_attr($data['address_line1']); ?>" class="large-text"></td>
                        </tr>
                        <tr>
                            <th>Unit/Suite Prefix</th>
                            <td><input type="text" name="warehouse[<?php echo $key; ?>][address_line2_prefix]" value="<?php echo esc_attr($data['address_line2_prefix']); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th>City / State / Zip</th>
                            <td>
                                <input type="text" name="warehouse[<?php echo $key; ?>][city]" value="<?php echo esc_attr($data['city']); ?>" placeholder="City">
                                <input type="text" name="warehouse[<?php echo $key; ?>][state]" value="<?php echo esc_attr($data['state']); ?>" placeholder="State">
                                <input type="text" name="warehouse[<?php echo $key; ?>][zip_code]" value="<?php echo esc_attr($data['zip_code']); ?>" placeholder="Zip">
                            </td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td><input type="text" name="warehouse[<?php echo $key; ?>][phone]" value="<?php echo esc_attr($data['phone']); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th>Warehouse Notes</th>
                            <td>
                                <textarea name="warehouse[<?php echo $key; ?>][warehouse_notes]" rows="2" class="large-text"><?php echo esc_textarea($data['warehouse_notes']); ?></textarea>
									<p class="description">Visible to customers (e.g., restrictions or transit times).</p>
								</td>
							</tr>
						</table>
					</div>
				<?php endforeach; ?>

				<p class="submit">
					<input type="submit" name="shipbox_save_settings" class="button button-primary button-large" value="Save All Plugin Settings">
				</p>
			</form>
		</div>
		<?php
	}


	public function manage_cities_page() {
		// Ensure the Table Class is loaded
		if ( ! class_exists( 'Shipbox_City_Table' ) ) {
			require_once plugin_dir_path(__FILE__) . 'class-shipbox-city-table.php';
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'shipbox_cities';
		$message = '';
		$action = isset($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : 'list';

		// 1. HANDLE DELETE ACTION
		if ( $action === 'delete' && isset($_GET['id']) ) {
			check_admin_referer('delete_city_' . $_GET['id']);
			$wpdb->delete($table_name, ['id' => intval($_GET['id'])]);
			echo '<div class="updated"><p>Weight range deleted successfully.</p></div>';
			$action = 'list';
		}

		// 2. HANDLE SAVE ACTION (ADD OR EDIT)
		if ( isset($_POST['save_city']) ) {
			check_admin_referer('shipbox_city_form');

			$city_id    = !empty($_POST['city_id']) ? intval($_POST['city_id']) : 0;
			$city_name  = sanitize_text_field($_POST['city_name']);
			$weight_min = floatval($_POST['weight_min']);
			$weight_max = floatval($_POST['weight_max']);
			$price      = floatval($_POST['price']);

			// --- DYNAMIC OVERLAP CHECK ---
			// This query checks if the new range overlaps with ANY existing range for this specific city.
			$overlap_id = $wpdb->get_var($wpdb->prepare(
				"SELECT id FROM $table_name 
				WHERE city_name = %s 
				AND id != %d 
				AND (
					(%f BETWEEN weight_min AND weight_max) OR 
					(%f BETWEEN weight_min AND weight_max) OR
					(weight_min BETWEEN %f AND %f)
				) LIMIT 1",
				$city_name, $city_id, $weight_min, $weight_max, $weight_min, $weight_max
			));

			if ( $overlap_id ) {
				$message = '<div class="error"><p><strong>Error:</strong> The weight range ' . $weight_min . 'kg - ' . $weight_max . 'kg overlaps with an existing entry for ' . esc_html($city_name) . '.</p></div>';
				$action = $city_id ? 'edit' : 'add'; 
			} else {
				$data = [
					'city_name'  => $city_name,
					'weight_min' => $weight_min,
					'weight_max' => $weight_max,
					'price'      => $price
				];

				if ( $city_id ) {
					$wpdb->update($table_name, $data, ['id' => $city_id]);
					$message = '<div class="updated"><p>City range updated successfully!</p></div>';
				} else {
					$wpdb->insert($table_name, $data);
					$message = '<div class="updated"><p>New city range added successfully!</p></div>';
				}
				$action = 'list';
			}
		}

		// 3. RENDER THE INTERFACE
		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">Manage Domestic City Rates</h1>';
		
		if ( $action === 'list' ) {
			echo '<a href="' . esc_url(admin_url('admin.php?page=' . $_REQUEST['page'] . '&action=add')) . '" class="page-title-action">Add New Range</a>';
		}
		echo '<hr class="wp-header-end">';
		echo $message;

		if ( $action === 'edit' || $action === 'add' ) {
			// Fetch existing data for Edit mode
			$item = null;
			if ( $action === 'edit' && isset($_GET['id']) ) {
				$item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_GET['id']));
			}

			// If validation failed, repopulate the form with the user's input
			if ( isset($_POST['save_city']) && $overlap_id ) {
				$item = (object) [
					'id'         => $city_id,
					'city_name'  => $city_name,
					'weight_min' => $weight_min,
					'weight_max' => $weight_max,
					'price'      => $price
				];
			}
			?>
			<div class="card" style="max-width: 800px; margin-top: 20px;">
				<form method="post">
					<?php wp_nonce_field('shipbox_city_form'); ?>
					<input type="hidden" name="city_id" value="<?php echo esc_attr($item->id ?? ''); ?>">
					
					<table class="form-table">
						<tr>
							<th><label for="city_name">City Name</label></th>
							<td>
								<input type="text" name="city_name" id="city_name" value="<?php echo esc_attr($item->city_name ?? ''); ?>" class="regular-text" required placeholder="e.g. Karachi">
								<p class="description">Enter the city name (Exact match is used for calculation).</p>
							</td>
						</tr>
						<tr>
							<th><label>Weight Range (KG)</label></th>
							<td>
								<input type="number" step="0.01" name="weight_min" value="<?php echo esc_attr($item->weight_min ?? '0.00'); ?>" style="width:100px;"> 
								<span class="dashicons dashicons-arrow-right-alt" style="margin-top:5px;"></span>
								<input type="number" step="0.01" name="weight_max" value="<?php echo esc_attr($item->weight_max ?? '1.00'); ?>" style="width:100px;">
								<p class="description">Define the slab (e.g., 0.1 to 0.5).</p>
							</td>
						</tr>
						<tr>
							<th><label for="price">Slab Price (USD)</label></th>
							<td>
								<input type="number" step="0.01" name="price" id="price" value="<?php echo esc_attr($item->price ?? '0.00'); ?>" required>
								<p class="description">The flat price charged for this weight range.</p>
							</td>
						</tr>
					</table>
					
					<div style="padding: 15px 0;">
						<?php submit_button('Save Shipping Range', 'primary', 'save_city', false); ?>
						<a href="?page=<?php echo esc_attr($_GET['page']); ?>" class="button button-secondary" style="margin-left:10px;">Back to List</a>
					</div>
				</form>
			</div>
			<?php
		} else {
			// Render the List Table
			$cityTable = new Shipbox_City_Table();
			$cityTable->prepare_items();
			?>
			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
				<?php $cityTable->search_box('Search Cities', 'search_id'); ?>
			</form>
			
			<form method="post">
				<?php $cityTable->display(); ?>
			</form>
			<?php
		}
		echo '</div>'; // Close wrap
	}


	// slabs page
	public function manage_slabs_page() {

		require_once plugin_dir_path(__FILE__) . 'class-shipbox-slabs-table.php';

		global $wpdb;
		$table_name = $wpdb->prefix . 'shipbox_weight_slabs';

		$message = '';

		// Sanitize action & id
		$action  = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : 'list';
		$slab_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

		// Allowed service types (as per client)
		$allowed_services = [
			'usa_express',
			'uk',
			'turkey'
		];

		/* =========================
		* HANDLE DELETE
		* ========================= */
		if ($action === 'delete' && $slab_id) {
			check_admin_referer('delete_slab_' . $slab_id);

			$wpdb->delete($table_name, ['id' => $slab_id], ['%d']);

			$message = '<div class="updated notice"><p>Slab deleted successfully.</p></div>';
			$action = 'list';
		}

		/* =========================
		* HANDLE SAVE (ADD / EDIT)
		* ========================= */
		if (isset($_POST['submit']) && isset($_POST['service_type'])) {

			check_admin_referer('shipbox_slab_form');

			$slab_id = !empty($_POST['slab_id']) ? absint($_POST['slab_id']) : 0;

			// Validate service type
			$service_type = in_array($_POST['service_type'], $allowed_services, true)
				? $_POST['service_type']
				: 'usa_express';

			$weight_min = floatval($_POST['weight_min']);
			$weight_max = floatval($_POST['weight_max']);
			$price      = floatval($_POST['price']);
			$currency   = sanitize_text_field($_POST['currency']);

			// Basic validation
			if ($weight_min < 0 || $weight_max <= $weight_min || $price <= 0) {
				$message = '<div class="error notice"><p>Invalid weight or price values.</p></div>';
				$action  = $slab_id ? 'edit' : 'add';
			} else {

				// Prevent overlapping slabs (per service type)
				$overlap = $wpdb->get_var($wpdb->prepare(
					"SELECT COUNT(id) FROM $table_name
					WHERE service_type = %s
					AND id != %d
					AND (
						(%f BETWEEN weight_min AND weight_max)
						OR (%f BETWEEN weight_min AND weight_max)
					)",
					$service_type,
					$slab_id,
					$weight_min,
					$weight_max
				));

				if ($overlap > 0) {
					$message = '<div class="error notice"><p>Overlapping weight slab already exists for this service.</p></div>';
					$action  = $slab_id ? 'edit' : 'add';
				} else {

					$data = [
						'service_type' => $service_type,
						'weight_min'   => $weight_min,
						'weight_max'   => $weight_max,
						'price'        => $price,
						'currency'     => $currency
					];

					if ($slab_id) {
						$result = $wpdb->update($table_name, $data, ['id' => $slab_id], ['%s','%f','%f','%f','%s'], ['%d']);
					} else {
						$result = $wpdb->insert($table_name, $data, ['%s','%f','%f','%f','%s']);
					}

					if ($result === false) {
						$message = '<div class="error notice"><p>Database Error: ' . esc_html($wpdb->last_error) . '</p></div>';
						$action  = $slab_id ? 'edit' : 'add';
					} else {
						$message = '<div class="updated notice"><p>Slab saved successfully.</p></div>';
						$action  = 'list';
					}
				}
			}
		}

		echo '<div class="wrap"><h1>Weight-Based Shipping Slabs</h1>' . $message;

		/* =========================
		* ADD / EDIT FORM
		* ========================= */
		if ($action === 'add' || $action === 'edit') {

			$item = ($action === 'edit' && $slab_id)
				? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $slab_id))
				: null;
			?>
			<div class="card" style="max-width:800px;padding:20px;margin-top:20px;">
				<form method="post">
					<?php wp_nonce_field('shipbox_slab_form'); ?>
					<input type="hidden" name="slab_id" value="<?php echo esc_attr($item->id ?? ''); ?>">

					<table class="form-table">
						<tr>
							<th>Service Type</th>
							<td>
								<select name="service_type">
									<option value="usa_express" <?php selected($item->service_type ?? '', 'usa_express'); ?>>USA Express</option>
									<option value="uk" <?php selected($item->service_type ?? '', 'uk'); ?>>UK</option>
									<option value="turkey" <?php selected($item->service_type ?? '', 'turkey'); ?>>Turkey</option>
								</select>
							</td>
						</tr>

						<tr>
							<th>Weight Range (KG)</th>
							<td>
								<input type="number" step="0.01" name="weight_min" value="<?php echo esc_attr($item->weight_min ?? ''); ?>" required style="width:100px;">
								to
								<input type="number" step="0.01" name="weight_max" value="<?php echo esc_attr($item->weight_max ?? ''); ?>" required style="width:100px;">
							</td>
						</tr>

						<tr>
							<th>Price</th>
							<td>
								<input type="number" step="0.01" name="price" value="<?php echo esc_attr($item->price ?? ''); ?>" required style="width:100px;">
								<select name="currency">
									<option value="USD" <?php selected($item->currency ?? '', 'USD'); ?>>USD ($)</option>
									<option value="GBP" <?php selected($item->currency ?? '', 'GBP'); ?>>GBP (£)</option>
								</select>
							</td>
						</tr>
					</table>

					<?php submit_button('Save Weight Slab'); ?>
					<a href="?page=<?php echo esc_attr($_GET['page']); ?>" class="button">Cancel</a>
				</form>
			</div>
			<?php

		} else {

			/* =========================
			* LIST TABLE
			* ========================= */
			$table = new Shipbox_Slabs_Table();
			$table->prepare_items();
			?>
            <div class="tablenav top">
                <div class="alignleft actions">
                    <a href="?page=<?php echo esc_attr($_GET['page']); ?>&action=add" class="button button-primary">Add New Slab</a>
                </div>
                
                <form method="get" style="float:right;">
                    <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
                    <?php $table->search_box('Search Slabs', 'shipbox-slabs'); ?>
                </form>
                <div class="clear"></div>
            </div>

            <form method="post">
                <?php $table->display(); ?>
            </form>
            <?php
        }

		echo '</div>';
	}



}