<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wecodely.com
 * @since      1.0.0
 *
 * @package    Shipbox
 * @subpackage Shipbox/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Shipbox
 * @subpackage Shipbox/public
 * @author     Kamran Nazir <kamrannazir901@gmail.com>
 */
class Shipbox_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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
		 wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
		'shipbox-bootstrap',
		'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
		array(),
		'5.3.2'
		);

		
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/shipbox-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		  wp_enqueue_script(
			'shipbox-bootstrap',
			'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
			array('jquery'),
			'5.3.2',
			true
		);

		

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/shipbox-public.js', array( 'jquery' ), $this->version, false );


		wp_localize_script( $this->plugin_name, 'shipbox_params', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'shipbox_shipment_action' ),
            'thank_you_url' => home_url( '/thank-you/' ),
        ));
	}

	/**
     * Registration Shortcode
     */

      public  function shipbox_hide_admin_bar() {
            if (!current_user_can('manage_options')) {
                show_admin_bar(false);
            }
        }

	public function handle_public_invoice_download() {
		if ( isset( $_GET['sb_invoice'] ) ) {
			if ( ! is_user_logged_in() ) {
				auth_redirect(); // Forces login if they click from email
			}
			
			$order_no = sanitize_text_field( $_GET['sb_invoice'] );
			require_once dirname( plugin_dir_path( __FILE__ ) ) . '/includes/class-shipbox-invoice.php';
			ShipBox_Invoice::generate_invoice( $order_no );
			exit;
		}
	}

        /**
 * Render the Thank You page after shipment submission.
 */
  public function thank_you_shortcode_handler() {
      ob_start();
      // Path to the partial file you created earlier
      include plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/shipbox-thank-you-display.php';
      return ob_get_clean();
  }
	public function register_shortcode_handler() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/controllers/class-shipbox-auth-controller.php';
		$auth = new ShipBox_Auth_Controller();
		
		$result = null; 
		if ( isset( $_POST['shipbox_register_submit'] ) ) {
			$result = $auth->handle_registration();
			if ( $result === true ) {
				wp_safe_redirect( get_permalink( get_option('shipbox_dashboard_page_id') ) );
				exit;
			}
		}

		ob_start();
		include plugin_dir_path( __FILE__ ) . 'partials/register-form.php';
		return ob_get_clean();
	}

  

    /**
     * Login Shortcode
     */
  public function login_shortcode_handler() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/controllers/class-shipbox-auth-controller.php';
		$auth = new ShipBox_Auth_Controller();
		$result = null;

		if ( isset( $_POST['shipbox_login_submit'] ) ) {
			$result = $auth->handle_login();

			if ( $result === true ) {
				$target = current_user_can('manage_options')
					? admin_url()
					: get_permalink( get_option('shipbox_dashboard_page_id') );

				wp_safe_redirect( $target );
				exit; // CRITICAL: Stop execution after redirect
			}
		}

		ob_start();
		// The $result variable is now available inside your login-form.php 
		// to display the WP_Error message automatically.
		include plugin_dir_path( __FILE__ ) . 'partials/login-form.php';
		return ob_get_clean();
	}


    /**
     * Dashboard Shortcode
     */
    public function dashboard_shortcode_handler() {

		if ( ! is_user_logged_in() || current_user_can('manage_options') ) {
			$login_url = get_permalink( get_option('shipbox_login_page_id') );
				return ' <div class="p-4 mb-4 text-dark bg-info bg-opacity-25 border-start border-info border-4 rounded-3 shadow-sm">
						<div class="d-flex align-items-center">
							<i class="dashicons dashicons-info text-info me-3" style="font-size: 30px; width: 30px; height: 30px;"></i>
							<p class="mb-0 fw-medium" style="font-size: 1.1rem;">
								Please login with your customer account to access this page.
							</p>
						</div>
						
					</div>';
		}

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/models/class-shipbox-customer-model.php';

		$customer_model = new ShipBox_Customer_Model();
		$customer = $customer_model->get_customer_by_user( get_current_user_id() );

		$warehouse_settings = get_option('shipbox_warehouse_settings');
		$user_name = wp_get_current_user()->display_name;

		ob_start();
		include plugin_dir_path(__FILE__) . 'partials/dashboard-view.php';
		return ob_get_clean();
	}

	public function shipment_submit_form_shortcode_handler() {
		// 1. Security Check: Only logged-in users can see the form
		if ( ! is_user_logged_in() || current_user_can('manage_options') ) {
			$login_url = get_permalink( get_option('shipbox_login_page_id') );
				return ' <div class="p-4 mb-4 text-dark bg-info bg-opacity-25 border-start border-info border-4 rounded-3 shadow-sm">
						<div class="d-flex align-items-center">
							<i class="dashicons dashicons-info text-info me-3" style="font-size: 30px; width: 30px; height: 30px;"></i>
							<p class="mb-0 fw-medium" style="font-size: 1.1rem;">
								Please login with your customer account to access this page.
							</p>
						</div>
						
					</div>';
		}

		// 2. Load necessary models to get user data
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/models/class-shipbox-customer-model.php';

		$customer_model = new ShipBox_Customer_Model();
		$customer = $customer_model->get_customer_by_user( get_current_user_id() );

		// 3. Prepare data for the view
		$user_name = wp_get_current_user()->display_name;
		$warehouse_settings = get_option('shipbox_warehouse_settings');

		// 4. Check if customer profile exists (to prevent errors if data is missing)
		if ( ! $customer ) {
			return '<div class="alert alert-danger">Customer profile not found. Please contact support.</div>';
		}

		// 5. Output buffering to include the template
		ob_start();
		// This points to public/partials/shipment-submission.php
		include plugin_dir_path( __FILE__ ) . 'partials/shipment-submission.php';
		return ob_get_clean();
	}

	public function render_order_history() {
		if ( ! is_user_logged_in() || current_user_can('manage_options') ) {
			$login_url = get_permalink( get_option('shipbox_login_page_id') );
				return ' <div class="p-4 mb-4 text-dark bg-info bg-opacity-25 border-start border-info border-4 rounded-3 shadow-sm">
						<div class="d-flex align-items-center">
							<i class="dashicons dashicons-info text-info me-3" style="font-size: 30px; width: 30px; height: 30px;"></i>
							<p class="mb-0 fw-medium" style="font-size: 1.1rem;">
								Please login with your customer account to access this page.
							</p>
						</div>
						
					</div>';
		}

		global $wpdb;
		$current_user_id = get_current_user_id();
		
		// Check for search input
		$search_query = isset($_GET['order_search']) ? sanitize_text_field($_GET['order_search']) : '';
		$params = [$current_user_id];
		// SQL query to get the latest 10 entries with optional search
		$sql = "SELECT o.id,o.payment_status, o.created_at, o.order_number, o.status, o.merchant_tracking_number, o.final_price,o.shipping_price, c.city 
				FROM {$wpdb->prefix}shipbox_orders o
				JOIN {$wpdb->prefix}shipbox_customers c ON o.customer_id = c.id
				WHERE c.user_id = %d";

		if ( ! empty($search_query) ) {
			$sql .= " AND (o.order_number LIKE %s OR o.merchant_tracking_number LIKE %s)";
			$search_term = '%' . $wpdb->esc_like($search_query) . '%';
			$params[] = $search_term;
			$params[] = $search_term;
		}

		$sql .= " ORDER BY o.created_at DESC LIMIT 10";

		$shipments = $wpdb->get_results($wpdb->prepare($sql, ...$params));

		ob_start();
		include plugin_dir_path(__FILE__) . 'partials/order-history-view.php';
		return ob_get_clean();
	}

	/**
 * Render the user header widget shortcode handler.
 */
	public function register_shipbox_header_shortcode() {
		// 1. Security & Role Check: Hide for logged-out users and Admins
		if ( ! is_user_logged_in() || current_user_can( 'manage_options' ) ) {
			return ''; 
		}

		// 2. Data Preparation
		$user = wp_get_current_user();
		global $wpdb;
		
		$customer_data = $wpdb->get_row( $wpdb->prepare(
			"SELECT customer_id FROM {$wpdb->prefix}shipbox_customers WHERE user_id = %d",
			$user->ID
		) );
		
		$cid = ( $customer_data && !empty($customer_data->customer_id) ) ? $customer_data->customer_id : 'N/A';
		$upload_dir = wp_get_upload_dir();
		$icon_url   = $upload_dir['baseurl'] . '/2026/01/user.png';
		// 3. Navigation Links (Matching your dashboard)
		$tabs = [
			'overview'   => ['label' => 'Addresses', 'icon' => 'dashicons-admin-home'],
			'calculator' => ['label' => 'Calculator', 'icon' => 'dashicons-calculator'],
			'shipment'   => ['label' => 'Order Confirmation', 'icon' => 'dashicons-plus-alt'],
			'history'    => ['label' => 'History', 'icon' => 'dashicons-list-view'],
			'profile'    => ['label' => 'Profile', 'icon' => 'dashicons-admin-users'],
		];

		$dashboard_url = site_url('/dashboard/');

		ob_start();
		?>
		<div class="shipbox-user-widget">
			<div class="shipbox-profile-trigger">
				<div class="shipbox-icon-wrap">
					<img src="<?php echo esc_url( $icon_url ); ?>" alt="User" class="shipbox-custom-icon">
				</div>
				<div class="shipbox-user-info">
					<span class="shipbox-name"><?php echo esc_html( $user->display_name ); ?></span>
					<span class="shipbox-cid">ID: <?php echo esc_html( $cid ); ?></span>
				</div>
				<span class="shipbox-arrow">▾</span>
			</div>
			
			<ul class="shipbox-dropdown">
				<?php foreach ( $tabs as $id => $info ) : ?>
					<li>
						<a href="<?php echo esc_url( add_query_arg( 'tab', $id, $dashboard_url ) ); ?>">
							<span class="dashicons <?php echo esc_attr( $info['icon'] ); ?>"></span>
							<span><?php echo esc_html( $info['label'] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
				
				<li class="divider"></li>
				
				<li>
					<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="logout">
						<span class="dashicons dashicons-signout"></span>
						<span>Logout</span>
					</a>
				</li>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}


 

  /**
 * 1. View Profile Method (image_07f037.png)
 * This is linked to [shipbox_profile]
 */
  public function profile_shortcode_handler() {
      // ... (Your login checks and data fetching code) ...

      ob_start();
      // Use the VIEW file here
      include plugin_dir_path( __FILE__ ) . 'partials/profile-view.php'; 
      return ob_get_clean();
  }

/**
 * 2. Edit Profile Method
 * This is linked to [shipbox_editprofile]
 */
public function edit_profile_shortcode_handler() {
    // 1. Initialize the controller where your update logic lives
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/controllers/class-shipbox-auth-controller.php';
    $auth = new ShipBox_Auth_Controller();
    
    // 2. Call the method that handles both the POST processing and the display
    // This runs the "if ( isset($_POST['shipbox_profile_save']) )" logic inside the controller
    return $auth->handle_profile_update();
}

}
