<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://club.wpeka.com
 * @since      1.0.0
 *
 * @package    WooCommerce_Newsletter_Subscription_Addon
 * @subpackage WooCommerce_Newsletter_Subscription_Addon/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WooCommerce_Newsletter_Subscription_Addon
 * @subpackage WooCommerce_Newsletter_Subscription_Addon/public
 * @author     WPEka Club
 */
class WNSMA_Public {

	public $service;
	public $chosen_service;
	public $checkbox_status;
	public $checkbox_label;
	public $mailchimp;
	public $api_key;
	public $list;
	public $mailchimp_store_id;
	public $double_opt;

	public function __construct() {

		$wnsma_settings    = get_option( 'wnsma_settings' );

				// Get settings
				$this->chosen_service    = $wnsma_settings['service_provider'];
				$this->checkbox_status   = $wnsma_settings['checkbox_status'];
				$this->checkbox_label    = $wnsma_settings['checkbox_label'];

				// Init chosen service
		if ( 'mailchimp' === $this->chosen_service ) {

			// Frontend
			add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'newsletter_field' ), 5 );
			add_action( 'woocommerce_ppe_checkout_order_review', array( $this, 'newsletter_field' ), 5 );
			add_action( 'woocommerce_register_form', array( $this, 'newsletter_field' ), 5 );
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'process_newsletter_field' ), 5, 2 );
			add_action( 'woocommerce_ppe_do_payaction', array( $this, 'process_ppe_newsletter_field' ), 5, 1 );
			add_action( 'woocommerce_register_post', array( $this, 'process_register_form' ), 5, 3 );

			// Order completed
			add_action( 'woocommerce_order_status_completed', array( $this, 'on_completed' ) );
			add_action( 'woocommerce_order_status_processing', array( $this, 'on_completed' ) );
			add_action( 'woocommerce_payment_complete', array( $this, 'on_completed' ) );

			$this->api_key = $wnsma_settings['mailchimp_api'];
			$this->list    = $wnsma_settings['mailchimp_list'];
			$this->double_opt = $wnsma_settings['double_opt'];
			$this->mailchimp_store_id = $wnsma_settings['store_id'];

			if ( $this->api_key ) {
				include_once( WNSMA_PLUGIN_PATH . 'admin/api/class-wc-mailchimp-newsletter-integration.php' );
				$this->service = new WC_Mailchimp_Newsletter_Integration( $this->api_key, $this->list );
			}
		}

	}


	/**
	 * newsletter_field function.
	 *
	 * @access public
	 * @param mixed $woocommerce_checkout
	 * @return void
	 */
	public function newsletter_field( $woocommerce_checkout ) {

		if ( is_user_logged_in() && get_user_meta( get_current_user_id(), '_wc_subscribed_to_newsletter', true ) ) {
			return;
		}

		if ( ! $this->service || ! $this->service->has_list() ) {
			return;
		}

					$value = ( 'checked' === $this->checkbox_status ) ? 1 : 0;

					woocommerce_form_field( 'subscribe_to_newsletter', array(
						'type' 			=> 'checkbox',
						'class'			=> array( 'form-row-wide' ),
						'label' 		=> $this->checkbox_label,
					), $value );

					echo '<div class="clear"></div>';
	}


			/**
			 * process_newsletter_field function.
			 *
			 * @access public
			 * @param mixed $order_id
			 * @param mixed $posted
			 * @return void
			 */
	public function process_newsletter_field( $order_id, $posted ) {

		if ( ! $this->service || ! $this->service->has_list() ) {
			return;
		}

		if ( ! isset( $_POST['subscribe_to_newsletter'] ) ) {
			return; // They don't want to subscribe
		}

		$order = new WC_Order( $order_id );

		$wnsma_settings = get_option( 'wnsma_settings' );

		$merger_tag_with_checkout_name = explode( ',',$wnsma_settings['merger_tag_with_checkout_name'] );

		$merge_data = array();
				$length_merger_tag_with_checkout_name = count( $merger_tag_with_checkout_name );
		for ( $i = 0; $i < $length_merger_tag_with_checkout_name; $i++ ) {
			if ( strpos( $merger_tag_with_checkout_name[ $i ], ':' ) !== false ) {
				$merge_checkout = explode( ':',$merger_tag_with_checkout_name[ $i ] );
				if ( 'order_id' === $merge_checkout[1] ) {
					$merge_data[ $merge_checkout[0] ] = $order_id;
				} elseif ( 'order_date' === $merge_checkout[1] ) {
					$merge_data[ $merge_checkout[0] ] = $order->order_date;
				} elseif ( 'order_amount' === $merge_checkout[1] ) {
					$merge_data[ $merge_checkout[0] ] = $order->get_total();
				} else {
									$merge_data[ $merge_checkout[0] ] = $posted[ $merge_checkout[1] ];
				}
			}
		}

		$this->service->subscribe( $posted['billing_email'],$merge_data );

		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), '_wc_subscribed_to_newsletter', 1 );
		}
	}


			 /**
			  * process_ppe_newsletter_field function.list
			  *
			  * @access public
			  * @param mixed $order
			  * @return void
			  */
	public function process_ppe_newsletter_field( $order ) {
		if ( ! $this->service || ! $this->service->has_list() ) {
			return;
		}

		if ( ! isset( $_REQUEST['subscribe_to_newsletter'] ) ) {
			return; // They don't want to subscribe
		}

		$this->service->subscribe( '', '', $order->billing_email );

		$order->add_order_note( esc_html__( 'User subscribed to newsletter via PayPal Express return page.', 'wc_subscribe_to_newsletter' ) );
	}


			/**
			 * process_register_form function.
			 *
			 * @access public
			 * @param mixed $sanitized_user_login
			 * @param mixed $user_email
			 * @param mixed $reg_errors
			 * @return void
			 */
	public function process_register_form( $sanitized_user_login, $user_email, $reg_errors ) {
		if ( ! $this->service || ! $this->service->has_list() ) {
			return;
		}

		if ( defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			return; // Ship checkout
		}

		if ( ! isset( $_REQUEST['subscribe_to_newsletter'] ) ) {
			return; // They don't want to subscribe
		}

		$this->service->subscribe( '', '', $user_email );
	}

	  /**
	   * Subscribe on order completed status and send E-Commerce data
	   *
	   * @access public
	   * @param int $order_id
	   * @return void
	   */
	public function on_completed( $order_id ) {

		// Check if WC order class is available and MailChimp is loaded
		if ( class_exists( 'WC_Order' ) && $this->load_mailchimp() ) {

					$wnsma_settings    = get_option( 'wnsma_settings' );
					$ecommerce = $wnsma_settings['ecommerce'];

			if ( 'no' === $ecommerce ) {
				return;
			}

			try {

				// Get order args
				$args = $this->prepare_order_data( $order_id );

				// Check those
				if ( false === $args ) {
					throw new Exception( __( 'Unable to proceed - order args was not created.', 'woochimp' ) );
				}

				// Check if order exists in MailChimp
				if ( $this->order_exists( $args['store_id'], $args['id'] ) === true ) {
					// $this->log_add(sprintf(__('Order %s already exists in Store %s, stopping.', 'woochimp'), $args['id'], $args['store_id']));
					return;
				}

				// Send order data
				$result = $this->mailchimp->create_order( $args['store_id'], $args );
				update_post_meta( $order_id, '_woochimp_ecomm_sent', 1 );

				// Add to log
				// $this->log_add(__('Ecommerce data sent successfully.', 'woochimp'));
				// $this->log_process_regular_data($args, $result);
			} catch ( Exception $e ) {

				// $this->log_add(__('Ecommerce data wasn\'t sent.', 'woochimp'));
				// Check message
				if ( preg_match( '/.+campaign with the provided ID does not exist in the account for this list+/', $e->getMessage() ) ) {

					// Remove campaign id from args
					unset( $args['campaign_id'] );

					// Try to send order data again
					try {
						$result = $this->mailchimp->create_order( $args['store_id'], $args );
						update_post_meta( $order_id, '_woochimp_ecomm_sent', 1 );

						// Add to log
						// $this->log_add(__('Ecommerce data sent successfully, but campaign id was omitted.', 'woochimp'));
						// $this->log_process_regular_data($args, $result);
					} catch ( Exception $ex ) {
						// $this->log_add(__('Ecommerce data wasn\'t sent even after omitting campaign id.', 'woochimp'));
						// $this->log_process_exception($ex);
						return;
					}
				}

				return;
			}// End try().
		}// End if().
	}

		/**
		 * E-Commerce - check if order exists in Mailchimp
		 *
		 * @access public
		 * @param int    $store_id
		 * @param string $mc_order_id
		 * @return void
		 */
	public function order_exists( $store_id, $mc_order_id ) {
		try {
			$this->mailchimp->get_order( $store_id, $mc_order_id );
			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}


		/**
		 * Load MailChimp object
		 *
		 * @access public
		 * @return mixed
		 */
	public function load_mailchimp() {

		if ( $this->mailchimp ) {
			return true;
		}

		// Load MailChimp class if not yet loaded
		if ( ! class_exists( 'WooChimp_Mailchimp' ) ) {
			require_once WNSMA_PLUGIN_PATH . 'admin/api/class-woochimp-mailchimp.php';
		}

		try {
			$this->mailchimp = new WooChimp_Mailchimp( $this->api_key );
			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}

	  /**
	   * Get WooCommerce order
	   *
	   * @access public
	   * @param int $order_id
	   * @return object
	   */
	public static function wc_get_order( $order_id ) {
		  return new WC_Order( $order_id );
	}

	  /**
	   * E-Commerce - get default store id
	   *
	   * @access public
	   * @return void
	   */
	public static function get_default_store_id() {
		$parsed_url = wp_parse_url( site_url() );
		$default_id = substr( preg_replace( '/[^a-zA-Z0-9]+/', '', $parsed_url['host'] ), 0, 32 );

		return $default_id;
	}

	  /**
	   * E-Commerce - get/create store in Mailchimp
	   *
	   * @access public
	   * @return void
	   */
	public function ecomm_get_store() {
		// Load MailChimp
		if ( ! $this->load_mailchimp() ) {
			return false;
		}

		// Get selected list for Store
		$list_id = $this->list;

		// Get defined name
		$store_id_set = $this->mailchimp_store_id;

		if ( empty( $list_id ) ) {
			return false;
		}

		// Try to find store associated with list
		try {
			$stores = $this->mailchimp->get_stores();
		} catch ( Exception $e ) {
			return false;
		}

		$store_id = null;

		if ( ! empty( $stores['stores'] ) ) {

			foreach ( $stores['stores'] as $store ) {

				if ( ( $list_id === $store['list_id'] ) && ( $store_id_set === $store['id'] ) ) {
					return $store['id'];
				}
			}
		}

		// If not found, create new
		if ( is_null( $store_id ) ) {

			// Get domain name from site url
			$parse = wp_parse_url( site_url() );

			// !empty($this->opt['woochimp_store_id']) ? $this->opt['woochimp_store_id'] : self::get_default_store_id(),
			// Define arguments
			$args = array(
				'id'      => $store_id_set,
				'list_id' => $list_id,
				'name'    => $parse['host'],
				'currency_code' => get_woocommerce_currency(),
			);

			try {
				$store = $this->mailchimp->create_store( $args );
				return $store['id'];
			} catch ( Exception $e ) {
				return false;
			}
		}
	}

	  /**
	   * E-Commerce - get id for Mailchimp
	   *
	   * @access public
	   * @param string $type
	   * @param int    $id
	   * @return void
	   */
	public static function ecomm_get_id( $type, $id ) {
		// Define prefixes
		$prefixes = apply_filters('woochimp_ecommerce_id_prefixes', array(
			'user'    => 'user_',
			'guest'   => 'guest_',
			'order'   => 'order_',
			'product' => 'product_',
			'item'    => 'item_',
		));

		// Combine and make sure it's a string
		if ( isset( $prefixes[ $type ] ) ) {
			return (string) $prefixes[ $type ] . $id;
		}

		return (string) $id;
	}


	  /**
	   * Get correct MC ID field data
	   *
	   * @access public
	   * @param string $meta_field
	   * @param int    $order_id
	   * @return void
	   */
	public static function get_mc_id( $meta_field, $order_id ) {
		if ( in_array( $meta_field, array( 'woochimp_mc_cid', 'woochimp_mc_eid' ), true ) ) {

			$old_mc_id = get_post_meta( $order_id, $meta_field, true );
			$new_mc_id = get_post_meta( $order_id, '_' . $meta_field, true );

			if ( ! empty( $old_mc_id ) ) {
				return $old_mc_id;
			} else {
				return $new_mc_id;
			}
		}
	}

		/**
		 * E-Commerce - check if product exists in Mailchimp
		 *
		 * @access public
		 * @param int    $store_id
		 * @param string $mc_product_id
		 * @return void
		 */
	public function product_exists( $store_id, $mc_product_id, $mc_variation_id = '' ) {
		try {
			$product = $this->mailchimp->get_product( $store_id, $mc_product_id );

			// Check variation if present
			if ( ! empty( $mc_variation_id ) && ( $mc_variation_id !== $mc_product_id ) ) {

				foreach ( $product['variants'] as $variant ) {
					if ( $mc_variation_id === $variant['id'] ) {
						return true;
					}
				}

				// No variation found
				return false;
			}

			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}

	  /**
	   * Prepare order data for E-Commerce
	   *
	   * @access public
	   * @param int $order_id
	   * @return array
	   */
	public function prepare_order_data( $order_id ) {

		// Initialize order object
		$order = self::wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		// Get store id (or create new)
		$store_id = $this->ecomm_get_store();

		if ( false === $store_id ) {
			return false;
		}

		// Get customer details
		$customer_email = $order->billing_email;

		// Regular user
		if ( $order->user_id > 0 ) {
			$customer_id = self::ecomm_get_id( 'user', $order->user_id );
		} // End if().
		else {

			// Create guest id based on email
			$customer_email_hash = WooChimp_Mailchimp::member_hash( $customer_email );
			$customer_id = self::ecomm_get_id( 'guest', $customer_email_hash );
		}

		$customer_details = array(
		  'id'            => $customer_id,
		  'email_address' => $customer_email,
		  'first_name'    => ! empty( $order->billing_first_name ) ? $order->billing_first_name :  $order->shipping_first_name,
		  'last_name'     => ! empty( $order->billing_last_name ) ? $order->billing_last_name :  $order->shipping_last_name,
		  'opt_in_status' => ( 'yes' === $this->double_opt ) ? true : false,
		);

		// Get order details
		$order_details = array(
		  'id'                   => self::ecomm_get_id( 'order', $order_id ),
		  'customer'             => $customer_details,
		  'financial_status'     => $order->get_status(),
		  'currency_code'        => $order->get_order_currency(),
		  'order_total'          => floatval( $order->order_total ),
		  'processed_at_foreign' => $order->order_date,
		  'updated_at_foreign'   => $order->modified_date,
		  'lines'                => array(),
		);

		// Check if we have campaign ID and email ID for this user/order
		$woochimp_mc_cid = self::get_mc_id( 'woochimp_mc_cid', $order->id );
		$woochimp_mc_eid = self::get_mc_id( 'woochimp_mc_eid', $order->id );

		// Pass campaign tracking properties to argument list
		if ( ! empty( $woochimp_mc_cid ) ) {
			$order_details['campaign_id'] = $woochimp_mc_cid;
		}

		// Get order items
		$items = $order->get_items();

		// Populate items
		foreach ( $items as $item_key => $item ) {

			// Load actual product
			$product = $order->get_product_from_item( $item );
			$variation_id = isset( $product->variation_id ) ? $product->variation_id : $product->id;

			$mc_product_id = self::ecomm_get_id( 'product', $product->id );
			$mc_variation_id = self::ecomm_get_id( 'product', $variation_id );

			// Need to create product, if not exists
			if ( $this->product_exists( $store_id, $mc_product_id ) === false ) {

				$product_details = array(
					'id'       => $mc_product_id,
					'title'    => $item['name'],
					'variants' => array(
							array(
								'id'    => $mc_variation_id,
								'title' => $item['name'],
								'sku'   => $product->get_sku(),
							),
						),
				);

				$this->mailchimp->create_product( $store_id, $product_details );
			} // End if().
			elseif ( $mc_variation_id !== $mc_product_id ) {

				// Add variation if not exists
				if ( $this->product_exists( $store_id, $mc_product_id, $mc_variation_id ) === false ) {

					$variant_details = array(
						'id'    => $mc_variation_id,
						'title' => $item['name'],
						'sku'   => $product->get_sku(),
					);

					$this->mailchimp->create_variant( $store_id, $mc_product_id, $variant_details );
				}
			}

			$order_details['lines'][] = array(
				'id'                 => self::ecomm_get_id( 'item', $item_key ),
				'product_id'         => $mc_product_id,
				'product_variant_id' => $mc_variation_id,
				'quantity'           => intval( $item['qty'] ),
				'price'              => $item['line_total'], // $product->get_price() doesn't fit here because of possible discounts/addons

			);
		}// End foreach().

		$order_details['store_id'] = $store_id;

		return $order_details;
	}


}
