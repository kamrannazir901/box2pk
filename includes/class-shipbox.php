<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wecodely.com
 * @since      1.0.0
 *
 * @package    Shipbox
 * @subpackage Shipbox/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Shipbox
 * @subpackage Shipbox/includes
 * @author     Kamran Nazir <kamrannazir901@gmail.com>
 */
class Shipbox {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Shipbox_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'SHIPBOX_VERSION' ) ) {
			$this->version = SHIPBOX_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'shipbox';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Shipbox_Loader. Orchestrates the hooks of the plugin.
	 * - Shipbox_i18n. Defines internationalization functionality.
	 * - Shipbox_Admin. Defines all hooks for the admin area.
	 * - Shipbox_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-shipbox-loader.php';


		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-shipbox-i18n.php';

		// require email class
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-shipbox-email-service.php';
		
		
		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-shipbox-admin.php';


		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-shipbox-public.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/controllers/class-shipbox-auth-controller.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/controllers/class-shipbox-shipment-controller.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/frontend-calculator.php';

		require_once plugin_dir_path(dirname( __FILE__ )) . 'admin/class-shipbox-customer-table.php';

		require_once plugin_dir_path(dirname( __FILE__ )) . 'admin/class-shipbox-customer-shipments-table.php';

		

		$this->loader = new Shipbox_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Shipbox_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Shipbox_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Shipbox_Admin( $this->get_plugin_name(), $this->get_version() );

		
		$this->loader->add_action( 'admin_post_generate_shipbox_pdf', $plugin_admin, 'handle_admin_invoice_download' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_shipbox_menus' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'admin_init', $plugin_admin, 'shipbox_handle_delete_redirect' );
		

	}

	



	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$shipment_controller = new ShipBox_Shipment_Controller();
		$auth_controller = new ShipBox_Auth_Controller();
		$plugin_public = new Shipbox_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'template_redirect', $plugin_public, 'handle_public_invoice_download' );
        $this->loader->add_action( 'after_setup_theme', $plugin_public, 'shipbox_hide_admin_bar' );
		add_shortcode( 'shipbox_register', array( $plugin_public, 'register_shortcode_handler' ) );
		add_shortcode( 'shipbox_user_header', array( $plugin_public, 'register_shipbox_header_shortcode' ) );
    add_shortcode( 'shipbox_thank_you', array( $plugin_public, 'thank_you_shortcode_handler' ) );
    	add_shortcode( 'shipbox_login', array( $plugin_public, 'login_shortcode_handler' ) );
    	add_shortcode( 'shipbox_dashboard', array( $plugin_public, 'dashboard_shortcode_handler' ) );
    	add_shortcode( 'shipbox_submit_shipment', array( $plugin_public, 'shipment_submit_form_shortcode_handler' ) );
		
    // Linked to the Table view
    add_shortcode( 'shipbox_profile', array( $plugin_public, 'profile_shortcode_handler' ) );

    // Linked to the Form view
    add_shortcode( 'shipbox_editprofile', array( $plugin_public, 'edit_profile_shortcode_handler' ) );

		add_shortcode( 'shipbox_order_history', array( $plugin_public, 'render_order_history' ) );

		$this->loader->add_action( 'wp_ajax_shipbox_submit_shipment', $shipment_controller, 'ajax_handle_shipment_submission' );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );



		$this->loader->add_action( 'init', $auth_controller, 'process_auth_actions' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Shipbox_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
