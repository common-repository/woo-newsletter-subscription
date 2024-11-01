<?php
/**
 * @link              https://club.wpeka.com
 * @since             1.0.0
 * @package           WooCommerce_Newsletter_Subscription_Addon
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Newsletter Subscription
 * Plugin URI:        https://club.wpeka.com/product/woocommerce-newsletter-subscription-addon
 * Description:       Addon for WooCommerce to add newsletter subscription(MailChimp). To get started: Go to <strong>WooCommerce > Newsletter Subscription</strong> and save your MailChimp API key.
 * Version:           2.4
 * Author:            WPEka Club
 * Author URI:        https://club.wpeka.com/
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}



define( 'WNSMA_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WNSMA_PLUGIN_FILE', plugin_basename( __FILE__ ) );

$plugindir = explode( '/', dirname( __FILE__ ) );
$plugindir = $plugindir[ count( $plugindir ) -1 ];
if ( ! defined( 'WNSMA_PLUGIN_URL' ) ) { define( 'WNSMA_PLUGIN_URL', WP_PLUGIN_URL . '/' . $plugindir );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_wnsma() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wnsma-activator.php';
	WNSMA_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_wnsma() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wnsma-deactivator.php';
	WNSMA_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wnsma' );
register_deactivation_hook( __FILE__, 'deactivate_wnsma' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-newsletter-subscription-addon.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wnsma() {
	$wnsma = new WooCommerce_Newsletter_Subscription_Addon();
}
run_wnsma();
