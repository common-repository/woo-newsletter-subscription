<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://club.wpeka.com
 * @since      1.0.0
 *
 * @package    WooCommerce_Newsletter_Subscription_Addon
 * @subpackage WooCommerce_Newsletter_Subscription_Addon/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WooCommerce_Newsletter_Subscription_Addon
 * @subpackage WooCommerce_Newsletter_Subscription_Addon/admin
 * @author     WPEka Club
 */
class WNSMA_Dependancy {

	public function __construct() {

		add_action( 'admin_init', array( $this, 'wnsma_woo_dependancy_check' ) );

	}

	function wnsma_woo_dependancy_check() {
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

			add_action( 'admin_notices', array( $this, 'wnsma_deactivation_admin_notice' ) );

		}
	}

	function wnsma_deactivation_admin_notice() {
		?>
		  <div class="error">
			  <p><?php echo 'WooCommerce Newsletter Subscription Plugin requires WooCommerce plugin installed and Active !'; ?></p>
		  </div>

		<?php
		deactivate_plugins( WNSMA_PLUGIN_FILE );
	}


}
