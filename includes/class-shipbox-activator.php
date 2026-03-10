<?php
/**
 * Fired during plugin activation
 *
 * @link       https://box2pk.com
 * @since      1.0.0
 *
 * @package    ShipBox
 * @subpackage ShipBox/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    ShipBox
 * @subpackage ShipBox/includes
 * @author     Your Name <your-email@example.com>
 */
class ShipBox_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		if ( ! is_admin() ) return;
		// Load database class
		require_once plugin_dir_path(__FILE__) . 'core/class-shipbox-database.php';

		// Create database tables
		ShipBox_Database::create_tables();

		// Set plugin version
		update_option('shipbox_version', '1.0.0');

		// Initialize customer ID counter (starts from 1)
		if (!get_option('shipbox_customer_id_counter')) {
			add_option('shipbox_customer_id_counter', 1);
		}

		// Set default plugin options
		self::set_default_options();
		// Create default pages
		self::create_default_pages();


		// Set activation timestamp
		update_option('shipbox_activated_at', current_time('mysql'));
	}

	/**
	 * Set default plugin options
	 *
	 * @since    1.0.0
	 */
	private static function set_default_options() {
		

		if (!get_option('shipbox_add_kg_usa')) {
			add_option('shipbox_add_kg_usa', 8.00);
		}
		if (!get_option('shipbox_add_kg_uk')) {
			add_option('shipbox_add_kg_uk', 21.51); // GBP
		}
		if (!get_option('shipbox_add_kg_turkey')) {
			add_option('shipbox_add_kg_turkey', 15.00);
		}

		// --- Domestic Additional KG Rates (USD) ---
		if (!get_option('shipbox_add_kg_karachi')) {
			add_option('shipbox_add_kg_karachi', 1.00);
		}
		if (!get_option('shipbox_add_kg_other_cities')) {
			add_option('shipbox_add_kg_other_cities', 1.72);
		}

		// --- Unit Divisors & Exchange Rates ---
		add_option('shipbox_divisor_cm', 5000);
		add_option('shipbox_divisor_inch', 139);
		add_option('shipbox_usd_to_pkr', 278.50);
		add_option('shipbox_gbp_to_pkr', 355.00);




		// Warehouse settings
		$warehouse_settings = array(
			'usa' => array(
				
				'address_line1' => '6 SHEA WAY',
				'address_line2_prefix' => 'Suite',
				'city' => 'NEWARK',
				'state' => 'DELAWARE',
				'zip_code' => '19713',
				'phone' => '302 265 0777',
				'country_code' => 'USA',
				
				'warehouse_notes' => ''
			),
			'uk' => array(
				
				'address_line1' => 'UNIT 2 HORTON INDUSTRIAL PARK',
				'address_line2_prefix' => 'HORTON ROAD.',
				'city' => 'WEST DRAYTON',
				'state' => 'MIDDLESEX',
				'zip_code' => 'UB7 8JD',
				'phone' => '01895 437926',
				'country_code' => 'UK',
				
				'warehouse_notes' => ''
			),
			'turkey' => array(
				
				'address_line1' => 'KARGOMKOLAY YENISAHRA MAH',
				'address_line2_prefix' => 'INONU CAD. No: 9/3.',
				'city' => 'ATASEHIR',
				'state' => 'ISTANBUL',
				'zip_code' => '34746',
				'phone' => '1201 366 0 444',
				'country_code' => 'Turkey',
				
				'warehouse_notes' => ''
			)
		);
		add_option('shipbox_warehouse_settings', $warehouse_settings);

		// Email settings
		$email_settings = array(
			'from_name' => get_bloginfo('name'),
			'from_email' => get_bloginfo('admin_email'),
			'enable_notifications' => true
		);
		add_option('shipbox_email_settings', $email_settings);

		// General settings
		$general_settings = array(
			'free_storage_days' => 15,
			'customer_id_prefix' => 'B2P',
			'currency' => 'PKR',
			'enable_consolidation' => true,
			'auto_calculate_volumetric' => true
		);
		add_option('shipbox_general_settings', $general_settings);

		
		$payment_settings = array(
			'enable_online_payment' => false,
			'stripe_test_mode'      => true,
			'test_publishable_key'  => '',
			'test_secret_key'       => '',
			'live_publishable_key'  => '',
			'live_secret_key'       => ''
		);
		add_option('shipbox_payment_settings', $payment_settings);
	}

	/**
	 * Create default WordPress pages for the plugin
	 *
	 * @since    1.0.0
	 */
	private static function create_default_pages() {
		
		// Check if pages already exist
		$pages = array(
			'dashboard' => array(
				'title'       => 'My Dashboard',
				'content'     => '[shipbox_dashboard]', // This shortcode will now handle all tabs
				'option_name' => 'shipbox_dashboard_page_id'
			),
			'login' => array(
				'title'       => 'Login',
				'content'     => '[shipbox_login]',
				'option_name' => 'shipbox_login_page_id'
			),
			'register' => array(
				'title'       => 'Register',
				'content'     => '[shipbox_register]',
				'option_name' => 'shipbox_register_page_id'
			)
		);


			foreach ($pages as $slug => $page) {
				// Check if page already exists
				$page_id = get_option($page['option_name']);
				
				if (!$page_id || !get_post($page_id)) {
					// Create the page
					$new_page = array(
						'post_title'    => $page['title'],
						'post_content'  => $page['content'],
						'post_status'   => 'publish',
						'post_type'     => 'page',
						'post_name'     => $slug,
						'comment_status' => 'closed',
						'ping_status'   => 'closed'
					);

					$page_id = wp_insert_post($new_page);

					// Save page ID in options
					if ($page_id) {
						update_option($page['option_name'], $page_id);
					}
				}
			}
		}
}