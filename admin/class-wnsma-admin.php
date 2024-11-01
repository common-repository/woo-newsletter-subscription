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
class WNSMA_Admin {

	public $mailchimp;
	public $woo_checkout_fields;

	public function __construct() {

				add_action( 'init', array( $this, 'wnsma_register_session' ) );
		add_action( 'admin_menu', array( $this, 'wnsma_register_submenu_page' ),10 );
		add_action( 'wp_ajax_ns_save_settings', array( $this, 'wnsma_save_settings' ) );
		add_action( 'wp_ajax_ns_load_mail_chimp_list', array( $this, 'wnsma_load_mail_chimp_list' ) );
		add_action( 'wp_ajax_ns_add_mail_chimp_list', array( $this, 'wnsma_add_mail_chimp_list' ) );

		$wnsma_settings = get_option( 'wnsma_settings' );

		include_once( 'api/class-wc-mailchimp-newsletter-integration.php' );
		$this->mailchimp = new WC_Mailchimp_Newsletter_Integration( $wnsma_settings['mailchimp_api'] );

		add_action( 'wnsma_service_provider',  array( $this, 'wnsma_service_provider_hook' ), 1, 1 );
		add_action( 'wnsma_mailchimp_setting',  array( $this, 'wnsma_mailchimp_setting_hook' ), 1, 5 );
		add_action( 'wnsma_mailchimp_fields_setting',  array( $this, 'wnsma_mailchimp_fields_setting_hook' ), 1, 2 );

		if ( ! class_exists( 'WC_Session' ) ) {
			include_once( WP_PLUGIN_DIR . '/woocommerce/includes/abstracts/abstract-wc-session.php' );
		}

			add_action('admin_init', function() {
			    WC()->session = new WC_Session_Handler;
			    WC()->customer = new WC_Customer;
			    $this->woo_checkout_fields = WC()->checkout->checkout_fields;
			});

	}

	function wnsma_register_session() {
		if ( ! session_id() ) {
			session_start();
		}
	}

	function wnsma_service_provider_hook( $service_provider ) {
		?>
		<option value="mailchimp"  class="form-control" <?php echo ( 'mailchimp' === $service_provider ) ? 'selected' : ''; ?>>MailChimp</option>
		<?php
	}


	function wnsma_mailchimp_setting_hook( $mailchimp_api, $mailchimp_list, $double_opt, $ecommerce, $store_id ) {

		$mailchimp_lists = "<option value=''>Enter your key and save to see your lists</option>";

		if ( $this->mailchimp->has_api_key() ) {
			if ( is_array( $this->mailchimp->get_lists() ) ) {
				$mailchip_array_list = $this->mailchimp->get_lists();
				$mailchimp_lists = '<option value="">Select list...</option>';
				foreach ( $mailchip_array_list as $key => $value ) {
							  $select = ($mailchimp_list === $key) ? 'selected' : '';
							  $mailchimp_lists .= '<option value="' . $key . '" ' . $select . ' >' . $value . '</option>';
				}
			} else {
				$mailchimp_lists = "<option value=''>Please create a list in your MailChimp account</option>";
			}
		}

		?>

		<div class="form-group">
			<label class="control-label col-sm-3" for="email">MailChimp API Key</label>
			<div class="col-sm-6">
				<input type="text"  class="form-control" value="<?php echo esc_attr( $mailchimp_api ); ?>" name="ns_mailchimp_api" id="ns_mailchimp_api" >
			</div>
			<div class="col-sm-3">
				<a href="#" data-toggle="tooltip" data-placement="right" title="You can obtain your API key by logging in to your MailChimp account."><img src="<?php echo esc_url( WNSMA_PLUGIN_URL . '/admin/img/help.png' ); ?>"></a>
				<a target='_blank' href='https://us2.admin.mailchimp.com/account/api/'> MailChimp Login</a>
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-3" for="email">MailChimp List</label>
			<div class="col-sm-6">
				<select name="ns_mailchimp_list" class="form-control" id="ns_mailchimp_list" >
					<?php echo $mailchimp_lists;?>
				</select>
			</div>
			<div class="col-sm-3">
				<a href="#" data-toggle="tooltip"  data-placement="right" title="Choose a list customers can subscribe to (you must save your API key first)."><img src="<?php echo esc_url( WNSMA_PLUGIN_URL . '/admin/img/help.png' ) ?>"></a>
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-3" for="email"> Enable Double Opt-in ?</label>
			<div class="col-sm-6">
				<input type="checkbox" <?php echo ( 'yes' === $double_opt ) ? 'checked="checked"' : ''; ?> style="margin-top:8px;" name="ns_double_opt" id="ns_double_opt">
			</div>
			<div class="col-sm-3">
				<a href="#" data-toggle="tooltip" data-placement="right" title="Controls whether a double opt-in confirmation message is sent, defaults to true. Abusing this may cause your account to be suspended."><img src="<?php echo esc_url( WNSMA_PLUGIN_URL . '/admin/img/help.png' ) ?>"></a>
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-3" for="email"> Enable E-Commerce ?</label>
			<div class="col-sm-6">
				<input type="checkbox" <?php echo ( 'yes' === $ecommerce ) ? 'checked="checked"' : ''; ?> style="margin-top:8px;" name="ns_ecommerce" id="ns_ecommerce">
			</div>
			<div class="col-sm-3">
				<a href="#" data-toggle="tooltip" data-placement="right" title="If enabled, order data will be sent to MailChimp as soon as order is marked as completed."><img src="<?php echo esc_url( WNSMA_PLUGIN_URL . '/admin/img/help.png' ); ?>"></a>
			</div>
		</div>

		<div class="form-group" id="mailchimp_store_id_div" style="<?php echo ( 'yes' === $ecommerce ) ? '' : 'display:none'; ?>">
			<label class="control-label col-sm-3" for="email">Store ID</label>
			<div class="col-sm-6">
				<input type="text"  class="form-control" value="<?php echo esc_attr( $store_id ); ?>" name="ns_store_id" id="ns_store_id" >
			</div>
			<div class="col-sm-3">
				<a href="#" data-toggle="tooltip" data-placement="right" title="MailChimp E-Commerce functionality requires a Store to be configured. Store must have a unique ID and must be tied to a specific MailChimp list. "><img src="<?php echo esc_url( WNSMA_PLUGIN_URL . '/admin/img/help.png' ) ?>"></a>
			</div>
		</div>

		<?php

	}



	function wnsma_mailchimp_fields_setting_hook( $mailchimp_list, $merger_tag_with_checkout_name ) {

		if ( isset( $mailchimp_list ) && ( '' !== $mailchimp_list ) ) {
			$fields	= $this->mailchimp->get_fields( $mailchimp_list );
		}

			$mailchim_field_count = 0;
			?>
			<table class="table table-striped" id="mc_fields_list">
				<thead>
					<tr>
						<th>MailChimp Merge Fields</th>
						<th>Woocommerce Checkout Fields</th>
					</tr>
				</thead>
				<tbody>


					<?php
					if ( isset( $merger_tag_with_checkout_name ) && ( '' !== $merger_tag_with_checkout_name ) ) {
												$length_merger_tag_with_checkout_name = count( $merger_tag_with_checkout_name );
						for ( $i = 0; $i < $length_merger_tag_with_checkout_name; $i++ ) {
							if ( strpos( $merger_tag_with_checkout_name[ $i ], ':' ) !== false ) {
									$merge_checkout = explode( ':',$merger_tag_with_checkout_name[ $i ] );
											$mailchim_field_count++;
											echo '<tr>';
						 				 		echo "<td><select name='merge_field_tags'>";
						 							echo '<option value="">-- Select --</option>';
								foreach ( $fields as $key => $value ) {
									$merge_field_select = '';
									if ( $key === $merge_checkout[0] ) {
										$merge_field_select = 'selected';
									}
									echo '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $merge_field_select ) . '>' . esc_attr( $value ) . ' (' . esc_attr( $key ) . ')</option>';
								}
						 						echo '</select></td>';
						 						echo "<td><select name='checkout_field_key'>";
						 							echo '<option value="">-- Select --</option>';
								foreach ( $this->woo_checkout_fields as $checkout_fields_category => $checkout_fields ) {
									if ( 'account' !== $checkout_fields_category ) {
										echo '<optgroup label="' . esc_attr( ucfirst( $checkout_fields_category ) ) . ' Fields">';
										foreach ( $this->woo_checkout_fields[ $checkout_fields_category ] as $field_value => $fields_tags ) {
											$checkout_field_select = '';
											if ( $field_value === $merge_checkout[1] ) {
												$checkout_field_select = 'selected';
											}
											if ( isset( $fields_tags['label'] ) ) {
												echo '<option value="' . esc_attr( $field_value ) . '" ' . esc_attr( $checkout_field_select ) . '>' . esc_attr( $fields_tags['label'] ) . '</option>';
											} else { echo '<option value="' . esc_attr( $field_value ) . '" ' . esc_attr( $checkout_field_select ) . '>Address 2</option>';
											}
										}
										if ( 'order' === $checkout_fields_category ) {
											$checkout_order_id_select = '';
											$checkout_order_date_select = '';
											$checkout_order_amount_select = '';
											if ( 'order_id' === $merge_checkout[1] ) {
												$checkout_order_id_select = 'selected';
											}
											if ( 'order_date' === $merge_checkout[1] ) {
												$checkout_order_date_select = 'selected';
											}
											if ( 'order_amount' === $merge_checkout[1] ) {
												$checkout_order_amount_select = 'selected';
											}
											echo '<option value="order_id" ' . esc_attr( $checkout_order_id_select ) . '>Order ID</option>
						 										<option value="order_date" ' . esc_attr( $checkout_order_date_select ) . '>Order Date</option>
						 										<option value="order_amount" ' . esc_attr( $checkout_order_amount_select ) . '>Order Amount</option>';
										}
										echo '</optgroup>';
									}
								}
						 						echo '</select></td>';
										 echo '<td class="mc_remove_list"><img src="' . esc_url( WNSMA_PLUGIN_URL ) . '/admin/img/cross.png"></td>';
										 echo '</tr>';
							}// End if().
						}// End for().
					}// End if().
						?>


				 </tbody>
				 <input type="hidden" name="ns_mailchimp_merge_fields_length" id="ns_mailchimp_merge_fields_length" value="<?php echo esc_attr( $mailchim_field_count ); ?>">
			</table>

			<div class="form-group">
				<div class="col-sm-2">
					<a class="btn btn-default btn-md" name="mc_add_field" id="mc_add_field"><img src="<?php echo esc_url( WNSMA_PLUGIN_URL . '/admin/img/plus.png' ); ?>"> Add Fields</a>
				</div>
			</div>

			<?php
	}

	function wnsma_add_mail_chimp_list() {
		 $post_mailchimp_list = ( isset( $_POST['mailChimpList'] ) && is_string( $_POST['mailChimpList'] ) ) ? $_POST['mailChimpList'] : '';
		if ( isset( $post_mailchimp_list ) && ( '' !== $post_mailchimp_list ) ) {
			$fields	= $this->mailchimp->get_fields( $post_mailchimp_list );
		}

		if ( isset( $fields ) ) {
			   echo '<tr>';
					  echo "<td><select name='merge_field_tags'>";
						  echo '<option value="">-- Select --</option>';
			foreach ( $fields as $key => $value ) {
				echo '<option value="' . esc_attr( $key ) . '">' . esc_attr( $value ) . ' (' . esc_attr( $key ) . ')</option>';
			}
							echo '</select></td>';
							echo "<td><select name='checkout_field_key'>";
							echo '<option value="">-- Select --</option>';
			foreach ( $this->woo_checkout_fields as $checkout_fields_category => $checkout_fields ) {
				if ( 'account' !== $checkout_fields_category ) {
					echo '<optgroup label="' . esc_attr( ucfirst( $checkout_fields_category ) ) . ' Fields">';
					foreach ( $this->woo_checkout_fields[ $checkout_fields_category ] as $field_value => $fields_tags ) {
						if ( isset( $fields_tags['label'] ) ) {
							echo '<option value="' . esc_attr( $field_value ) . '" >' . esc_attr( $fields_tags['label'] ) . '</option>';
						} else { echo '<option value="' . esc_attr( $field_value ) . '" >Address 2</option>';
						}
					}
					if ( 'order' === $checkout_fields_category ) {
						echo '<option value="order_id" >Order ID</option>
										<option value="order_date">Order Date</option>
										<option value="order_amount">Order Amount</option>';
					}
					echo '</optgroup>';
				}
			}
							echo '</select></td>';
							echo '<td class="mc_remove_list"><img src="' . esc_url( WNSMA_PLUGIN_URL ) . '/admin/img/cross.png"></td>';
							echo '</tr>';
		}
		 wp_die();
	}

	function wnsma_load_mail_chimp_list() {

		$post_mailchimp_list = $_POST['mailChimpList'];
		$wnsma_settings = get_option( 'wnsma_settings' );
		$mailchimp_list = $wnsma_settings['mailchimp_list'];
		$merger_tag_with_checkout_name = explode( ',',$wnsma_settings['merger_tag_with_checkout_name'] );

		if ( isset( $post_mailchimp_list ) && ( '' !== $post_mailchimp_list ) ) {
			$fields	= $this->mailchimp->get_fields( $post_mailchimp_list );
		}

	 	$result_data = '';

		 $mailchim_field_count = 0;

		 $result_data .= '<table class="table table-striped" id="mc_fields_list">
			 <thead>
				 <tr>
					 <th>MailChimp Merge Fields</th>
					 <th>Woocommerce Checkout Fields</th>
				 </tr>
			 </thead>
			 <tbody>';

		if ( isset( $fields ) ) {
			if ( $post_mailchimp_list === $mailchimp_list ) {
				if ( isset( $merger_tag_with_checkout_name ) && ( '' !== $merger_tag_with_checkout_name ) ) {
										$length_merger_tag_with_checkout_name = count( $merger_tag_with_checkout_name );
					for ( $i = 0; $i < $length_merger_tag_with_checkout_name; $i++ ) {
						if ( strpos( $merger_tag_with_checkout_name[ $i ], ':' ) !== false ) {
								$merge_checkout = explode( ':',$merger_tag_with_checkout_name[ $i ] );
										$mailchim_field_count++;
										$result_data .= '<tr>';
											$result_data .= "<td><select name='merge_field_tags'>";
												$result_data .= '<option value="">-- Select --</option>';
							foreach ( $fields as $key => $value ) {
								$merge_field_select = '';
								if ( $key === $merge_checkout[0] ) {
									$merge_field_select = 'selected';
								}
								$result_data .= '<option value="' . $key . '" ' . $merge_field_select . '>' . $value . ' (' . $key . ')</option>';
							}
											$result_data .= '</select></td>';
											$result_data .= "<td><select name='checkout_field_key'>";
												$result_data .= '<option value="">-- Select --</option>';
							foreach ( $this->woo_checkout_fields as $checkout_fields_category => $checkout_fields ) {
								if ( 'account' !== $checkout_fields_category ) {
									$result_data .= '<optgroup label="' . ucfirst( $checkout_fields_category ) . ' Fields">';
									foreach ( $this->woo_checkout_fields[ $checkout_fields_category ] as $field_value => $fields_tags ) {
										$checkout_field_select = '';
										if ( $field_value === $merge_checkout[1] ) {
											$checkout_field_select = 'selected';
										}
										if ( isset( $fields_tags['label'] ) ) {
											$result_data .= '<option value="' . $field_value . '" ' . $checkout_field_select . '>' . $fields_tags['label'] . '</option>';
										} else { $result_data .= '<option value="' . $field_value . '" ' . $checkout_field_select . '>Address 2</option>';
										}
									}
									if ( 'order' === $checkout_fields_category ) {
										$checkout_order_id_select = '';
										$checkout_order_date_select = '';
										$checkout_order_amount_select = '';
										if ( 'order_id' === $merge_checkout[1] ) {
											$checkout_order_id_select = 'selected';
										}
										if ( 'order_date' === $merge_checkout[1] ) {
											$checkout_order_date_select = 'selected';
										}
										if ( 'order_amount' === $merge_checkout[1] ) {
											$checkout_order_amount_select = 'selected';
										}
										$result_data .= '<option value="order_id" ' . $checkout_order_id_select . '>Order ID</option>
																			<option value="order_date" ' . $checkout_order_date_select . '>Order Date</option>
																			<option value="order_amount" ' . $checkout_order_amount_select . '>Order Amount</option>';
									}
									$result_data .= '</optgroup>';
								}
							}
											$result_data .= '</select></td>';
											$result_data .= '<td class="mc_remove_list"><img src="' . WNSMA_PLUGIN_URL . '/admin/img/cross.png"></td>';
									 $result_data .= '</tr>';
						}// End if().
					}// End for().
				}// End if().
			}// End if().
			if ( $post_mailchimp_list !== $mailchimp_list ) {
				$mailchim_field_count++;
				$result_data .= '<tr>';
				 $result_data .= "<td><select name='merge_field_tags'>";
				   $result_data .= '<option value="">-- Select --</option>';
				foreach ( $fields as $key => $value ) {
					$result_data .= '<option value="' . $key . '">' . $value . ' (' . $key . ')</option>';
				}
					$result_data .= '</select></td>';
					$result_data .= "<td><select name='checkout_field_key'>";
				   $result_data .= '<option value="">-- Select --</option>';
				foreach ( $this->woo_checkout_fields as $checkout_fields_category => $checkout_fields ) {
					if ( 'account' !== $checkout_fields_category ) {
						$result_data .= '<optgroup label="' . ucfirst( $checkout_fields_category ) . ' Fields">';
						foreach ( $this->woo_checkout_fields[ $checkout_fields_category ] as $field_value => $fields_tags ) {
							if ( isset( $fields_tags['label'] ) ) {
								$result_data .= '<option value="' . $field_value . '" >' . $fields_tags['label'] . '</option>';
							} else { $result_data .= '<option value="' . $field_value . '" >Address 2</option>';
							}
						}
						if ( 'order' === $checkout_fields_category ) {
							$result_data .= '<option value="order_id" >Order ID</option>
											<option value="order_date">Order Date</option>
											<option value="order_amount">Order Amount</option>';
						}
							$result_data .= '</optgroup>';
					}
				}
					$result_data .= '</select></td>';
					$result_data .= '<td class="mc_remove_list"><img src="' . WNSMA_PLUGIN_URL . '/admin/img/cross.png"></td>';
					$result_data .= '</tr>';
			}
		}// End if().

				$result_data .= '</tbody>
				<input type="hidden" name="ns_mailchimp_merge_fields_length" id="ns_mailchimp_merge_fields_length" value="' . $mailchim_field_count . '">
		 </table>';

		 $result_data .= '<div class="form-group">
			 <div class="col-sm-2">
				 <a class="btn btn-default btn-md" name="mc_add_field" id="mc_add_field"><img src="' . WNSMA_PLUGIN_URL . '/admin/img/plus.png"> Add Fields</a>
			 </div>
		 </div>';

		 echo $result_data;
		wp_die();
	}

	function wnsma_enqueue_style() {

			wp_enqueue_style( 'Bootsrap_min_style', WNSMA_PLUGIN_URL . '/admin/css/bootstrap.min.css' );

			wp_enqueue_script( 'bootstrap_min_js',  WNSMA_PLUGIN_URL . '/admin/js/bootstrap.min.js', array( 'jquery' ) );

			wp_enqueue_style( 'WNSMA_admin_style', WNSMA_PLUGIN_URL . '/admin/css/wnsma-admin.css' );
			wp_enqueue_script( 'WNSMA_admin_script', WNSMA_PLUGIN_URL . '/admin/js/wnsma-admin.js', array( 'jquery' ) );

	}


	function wnsma_register_submenu_page() {

				$wnsma_menu = add_submenu_page( 'woocommerce', 'Newsletter Subscription', 'Newsletter Subscription', 'manage_woocommerce', 'Newsletter_Subscription',  array( $this, 'wnsma_submenu_page_callback' ) );

				 add_action( 'admin_print_styles-' . $wnsma_menu, array( $this, 'wnsma_enqueue_style' ) );

	}


	function wnsma_submenu_page_callback() {
		include_once( WNSMA_PLUGIN_PATH . 'admin/class-wc-newsletter.php' );
	}


	function wnsma_save_settings() {

		if ( ! wp_verify_nonce( $_POST['newsletter_nonce'], plugin_basename( __FILE__ ) ) ) {
						global $wp_session;
			$wnsma_settings = array();

			$wnsma_settings['service_provider'] = sanitize_text_field( $_POST['service_provider'] );
			$wnsma_settings['checkbox_status'] = sanitize_text_field( $_POST['checkbox_status'] );
			$wnsma_settings['checkbox_label'] = sanitize_text_field( $_POST['checkbox_label'] );

			$wnsma_settings['mailchimp_api'] = sanitize_text_field( $_POST['mailchimp_api'] );
			$wnsma_settings['mailchimp_list'] = sanitize_text_field( $_POST['mailchimp_list'] );
			$wnsma_settings['double_opt'] = sanitize_text_field( $_POST['double_opt'] );
			$wnsma_settings['ecommerce'] = sanitize_text_field( $_POST['ecommerce'] );
			$wnsma_settings['store_id'] = sanitize_text_field( $_POST['store_id'] );
			$wnsma_settings['merger_tag_with_checkout_name'] = sanitize_text_field( $_POST['merger_tag_with_checkout_name'] );

			if ( isset( $_POST['cmonitor_api'] ) ) {
				$wnsma_settings['cmonitor_api'] = sanitize_text_field( $_POST['cmonitor_api'] );
				$wnsma_settings['cmonitor_list'] = sanitize_text_field( $_POST['cmonitor_list'] );
				$wnsma_settings['cmonitor_double_opt'] = sanitize_text_field( $_POST['cmonitor_double_opt'] );
				$wnsma_settings['custom_key_with_checkout_name'] = sanitize_text_field( $_POST['custom_key_with_checkout_name'] );
			}

	                update_option( 'wnsma_settings', $wnsma_settings );

										$wp_session['save-date'] = 'success';

		}

	}

}
