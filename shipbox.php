<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wecodely.com
 * @since             1.0.0
 * @package           Shipbox
 *
 * @wordpress-plugin
 * Plugin Name:       ShipBox
 * Plugin URI:        https://box2pk.com/
 * Description:       This is a description of the plugin.
 * Version:           1.0.0
 * Author:            Kamran Nazir
 * Author URI:        https://wecodely.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       shipbox
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SHIPBOX_VERSION', '1.0.0' );


add_action('admin_init', function() {
    if (isset($_GET['generate_sb_invoice'])) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-shipbox-invoice.php';
        
        $order_no = sanitize_text_field($_GET['generate_sb_invoice']);
        ShipBox_Invoice::generate_invoice($order_no);
    }
});


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-shipbox-activator.php
 */
function activate_shipbox() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-shipbox-activator.php';
	Shipbox_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-shipbox-deactivator.php
 */
function deactivate_shipbox() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-shipbox-deactivator.php';
	Shipbox_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_shipbox' );
register_deactivation_hook( __FILE__, 'deactivate_shipbox' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-shipbox.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_shipbox() {

	$plugin = new Shipbox();
	$plugin->run();

}
run_shipbox();





/**
 * Custom Login Screen Branding for ShipBox - Red Theme
 */
add_action('login_enqueue_scripts', function() {
    $logo_url = 'https://box2pk.com/wp-content/uploads/2026/01/box2pk-logo-01-2048x880.jpg';
    $brand_color = '#1A9C38';
    ?>
    <style type="text/css">
        /* Main Logo Styling */
        #login h1 a, .login h1 a {
            background-image: url('<?php echo $logo_url; ?>');
            height: 100px;
            width: 220px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center bottom;
            padding-bottom: 20px;
        }

        /* Branding the Login Button */
        .wp-core-ui .button-primary {
            background: <?php echo $brand_color; ?> !important;
            border-color: <?php echo $brand_color; ?> !important;
            text-shadow: none !important;
 
        }
        
        .wp-core-ui .button-primary:hover,
        .wp-core-ui .button-primary:focus {
            background: #1A9C38 !important; /* Hover state */
            border-color: #1A9C38 !important;
	}

	.notice,.message{
		border-color:#1A9C38 !important;
	}

        /* Branding Input Field Focus */
        .login input:focus {
            border-color: <?php echo $brand_color; ?> !important;
            box-shadow: 0 0 0 1px <?php echo $brand_color; ?> !important;
        }

        /* Simple background adjustment */
        body.login {
            background: #fdfdfd;
        }
    </style>
    <?php
});

/**
 * Change Logo Link and Tooltip
 */
add_filter('login_headerurl', fn() => home_url());
add_filter('login_headertext', fn() => 'Powered by ShipBox');