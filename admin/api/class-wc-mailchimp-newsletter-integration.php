<?php

/**
 * WC_Mailchimp_Integration class.
 *
 * http://apidocs.mailchimp.com/api/rtfm/campaignecommorderadd.func.php#campaignecommorderadd-v13
 */
if ( ! class_exists( 'WC_Mailchimp_Newsletter_Integration' ) ) {
	class WC_Mailchimp_Newsletter_Integration {
		private $api_key;
		private $api_endpoint = 'https://<dc>.api.mailchimp.com/2.0';
		private $list;
		private $fields;

		/**
		 * Constructor
		 *
		 * @param type $api_key
		 * @param type $list
		 */
		public function __construct( $api_key, $list = false ) {
			$this->api_key = $api_key;
			$this->list    = $list;
			$datacentre    = '';

			if ( $this->api_key ) {
				if ( strstr( $this->api_key, '-' ) ) {
					list( , $datacentre ) = explode( '-', $this->api_key );
				}
				if ( ! $datacentre ) {
					$datacentre = 'us2';
				}
				$this->api_endpoint   = str_replace( '<dc>', $datacentre, $this->api_endpoint );
				add_action( 'init', array( $this, 'ecommerce360_set_cookies' ) );
				add_action( 'woocommerce_thankyou', array( $this, 'ecommerce360_tracking' ) );
			}
		}

	    /**
	     * Performs the underlying HTTP request. Not very exciting
		 *
	     * @param  string $method The API method to be called
	     * @param  array  $args   Assoc array of parameters to be passed
	     * @return array          Assoc array of decoded result
	     */
	    private function api_request( $method, $args = array() ) {

	        $args['apikey'] = $this->api_key;

					$result = wp_remote_post(
						$this->api_endpoint . '/' . $method . '.json',
						array(
										'body' 			=> wp_json_encode( $args ),
										'sslverify' 	=> false,
										'timeout' 		=> 60,
										'httpversion'   => '1.1',
										'headers'       => array(
											'Content-Type' => 'application/json',
										),
										'user-agent'	=> 'PHP-MCAPI/2.0',
						)
					);

	        return ! is_wp_error( $result ) && isset( $result['body'] ) ? json_decode( $result['body'] ) : false;
	    }

				/**
				 *
				 * @param type  $method
				 * @param array $args
				 * @return type
				 */
		private function get_merge_feilds( $method, $args = array() ) {

			$dc = substr( $this->api_key,strpos( $this->api_key,'-' ) + 1 );

			$args['apikey'] = $this->api_key;

			$args_fields = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'user:' . $this->api_key ),
			),
			);

			$result = wp_remote_get( 'https://' . $dc . '.api.mailchimp.com/3.0/' . $method , $args_fields );

			return ! is_wp_error( $result ) && isset( $result['body'] ) ? json_decode( $result['body'] ) : false;

		}

		/**
		 * set_cookies function.
		 *
		 * @access public
		 * @return void
		 */
		public function ecommerce360_set_cookies() {
			$thirty_days = time() + 60 * 60 * 24 * 30;
			$mcid = ( isset( $_REQUEST['mc_cid'] ) && is_string( $_REQUEST['mc_cid'] ) ) ? sanitize_key( $_REQUEST['mc_cid'] ) : null  ;
			if ( isset( $mcid ) ) {
				setcookie( 'mailchimp_campaign_id', trim( $mcid ), $thirty_days, '/' );
			}

			if ( isset( $mcid ) ) {
								setcookie( 'mailchimp_email_id', trim( $mcid ), $thirty_days, '/' );
			} elseif ( is_user_logged_in() && get_user_meta( get_current_user_id(), 'mailchimp_email_id', true ) ) {
				// the main piece of information needed to track a user is their email id
				$list_eid = trim( get_user_meta( get_current_user_id(), 'mailchimp_email_id', true ) );
				setcookie( 'mailchimp_email_id', $list_eid, $thirty_days, '/' );
			}
		}

		/**
		 * ecommerce360_tracking function.
		 *
		 * @access public
		 * @param mixed $order_id
		 * @return void
		 */
		public function ecommerce360_tracking( $order_id ) {
						$mailchimp_email_id = sanitize_email( $_COOKIE['mailchimp_email_id'] );
			if ( empty( $mailchimp_email_id ) ) {
				return;
			}

			// Get the order and output tracking code
			$order = new WC_Order( $order_id );

			$items = array();

			if ( $order->get_items() ) {
				foreach ( $order->get_items() as $item ) {
					$_product = $order->get_product_from_item( $item );

					$cats = wp_get_post_terms( $_product->id, 'product_cat', array(
						'fields' => 'all',
					) );

					$category_id = 0;
										$category_name = 0;

					if ( $cats ) {
						foreach ( $cats as $cat ) {
							$category_id   = $cat->term_id;
							$category_name = $cat->name;
							break;
						}
					}

					$items[] = array(
						'product_id'    => $_product->id,
						'sku'           => $_product->get_sku(),
						'product_name'  => $_product->get_title(),
						'category_id'   => $category_id,
						'category_name' => $category_name,
						'qty'           => $item['qty'],
						'cost'          => $order->get_item_total( $item ),
					);
				}
			}

			$tracked_order = array(
				'id'          => $order_id,
				'email_id'    => $_COOKIE['mailchimp_email_id'],
				'email'       => $order->billing_email,
				'total'       => $order->get_total(),
				'shipping'    => $order->get_total_shipping(),
				'tax'         => $order->get_total_tax(),
				'store_id'    => substr( md5( site_url() ), 0, 20 ),
				'store_name'  => site_url(),
				'items'       => $items,
			);

			// in certain cases the API doesn't have a campaign ID so we only registeter a user
			// under a campaign if one exists.
			if ( ! empty( $mailchimp_email_id ) ) {
				$tracked_order['campaign_id'] = $mailchimp_email_id;
			}

			$this->api_request( 'ecomm/order-add', array(
				'order' => $tracked_order,
			) );
		}

		/**
		 * has_list function.
		 *
		 * @access public
		 * @return void
		 */
		public function has_list() {
			if ( $this->list ) {
				return true;
			}
		}

		/**
		 * has_api_key function.
		 *
		 * @access public
		 * @return void
		 */
		public function has_api_key() {
			if ( $this->api_key ) {
				return true;
			}
		}



		public function get_fields( $listid ) {

			if ( isset( $listid ) && ( '' !== $listid ) ) {
				$merge_fields = $this->get_merge_feilds( 'lists/' . $listid . '/merge-fields' );

				if ( $merge_fields ) {

					if ( isset( $merge_fields->status ) && ( 'error' === $merge_fields->status ) ) {
												/* translators:Megre Code */
						echo '<div class="error"><p>' . sprintf( esc_html__( 'Unable to load fields() from MailChimp: (%1$s) %2$s', 'wc_subscribe_to_newsletter' ), $merge_fields->code, $merge_fields->error ) . '</p></div>';

						return false;

					} else {
						foreach ( $merge_fields->merge_fields as $merge ) {
							$fields[ $merge->tag ] = $merge->name;
						}

						if ( sizeof( $fields ) > 0 ) {
							set_transient( 'wc_mc_fields_' . md5( $this->api_key ), $fields, 60 * 60 * 1 );
						}
					}
				} else {
					$fields = array();
				}

				return $fields;
			}

		}





		/**
		 * get_lists function.
		 *
		 * @access public
		 * @return void
		 */
		public function get_lists() {

						$mailchimp_lists = get_transient( 'wc_mc_list_' . md5( $this->api_key ) );

			if ( isset( $mailchimp_lists ) ) {

				$lists = $this->api_request( 'lists/list' );

				if ( $lists ) {

					if ( isset( $lists->status ) && ( 'error' === $lists->status ) ) {
												/* translators: Lists error & Error Code*/
						echo '<div class="error"><p>' . sprintf( esc_html__( 'Unable to load lists() from MailChimp: (%1$s) %2$s', 'wc_subscribe_to_newsletter' ), $lists->code, $lists->error ) . '</p></div>';

						return false;

					} else {
						foreach ( $lists->data as $list ) {
							$mailchimp_lists[ $list->id ] = $list->name;
						}

						if ( sizeof( $mailchimp_lists ) > 0 ) {
							set_transient( 'wc_mc_list_' . md5( $this->api_key ), $mailchimp_lists, 60 * 60 * 1 );
						}
					}
				} else {
					$mailchimp_lists = array();
				}
			}

			return $mailchimp_lists;
		}

		/**
		 * show_stats function.
		 *
		 * @access public
		 * @return void
		 */
		public function show_stats() {
						$stats = get_transient( 'woocommerce_mailchimp_stats' );
			if ( isset( $stats ) ) {

				$lists = $this->api_request( 'lists/list' );

				if ( isset( $lists->status ) && ( 'error' === $lists->status ) ) {

					echo '<div class="error inline"><p>' . esc_html__( 'Unable to load stats from MailChimp', 'wc_subscribe_to_newsletter' ) . '</p></div>';

				} else {

					foreach ( $lists->data as $list ) {

						if ( $list->id !== $this->list ) {
							continue;
						}

						$stats  = '<ul class="woocommerce_stats" style="word-wrap:break-word;">';
						$stats .= '<li><strong style="font-size:3em;">' . esc_html( $list->stats->member_count ) . '</strong> ' . esc_html__( 'Total subscribers', 'wc_subscribe_to_newsletter' ) . '</li>';
						$stats .= '<li><strong style="font-size:3em;">' . esc_html( $list->stats->unsubscribe_count ) . '</strong> ' . esc_html__( 'Unsubscribes', 'wc_subscribe_to_newsletter' ) . '</li>';
						$stats .= '<li><strong style="font-size:3em;">' . esc_html( $list->stats->member_count_since_send ) . '</strong> ' . esc_html__( 'Subscribers since last newsletter', 'wc_subscribe_to_newsletter' ) . '</li>';
						$stats .= '<li><strong style="font-size:3em;">' . esc_html( $list->stats->unsubscribe_count_since_send ) . '</strong> ' . esc_html__( 'Unsubscribes since last newsletter', 'wc_subscribe_to_newsletter' ) . '</li>';
						$stats .= '</ul>';

						break;
					}

					set_transient( 'woocommerce_mailchimp_stats', $stats, 60 * 60 * 1 );
				}
			}

			echo $stats;
		}

		/**
		 * subscribe function.
		 *
		 * @access public
		 * @param mixed  $first_name
		 * @param mixed  $last_name
		 * @param mixed  $email
		 * @param string $listid (default: 'false')
		 * @return void
		 */
		public function subscribe( $email, $merge_data ) {

			if ( ! $email ) {
				return; // Email is required
			}

			$listid = $this->list;

			$wnsma_settings = get_option( 'wnsma_settings' );

			$result = $this->api_request( 'lists/subscribe', array(
				'id'           => $listid,
				'email'        => array(
				'email' => $email,
				),
				'merge_vars'   => apply_filters( 'wc_mailchimp_subscribe_vars', $merge_data ),
				'double_optin' => ( 'yes' === $wnsma_settings['double_opt'] ),
			) );

			if ( isset( $result->status ) && ( 'error' === $result->status ) ) {

				// Already subscribed
				if ( 214 === $result->code ) {
					return;
				}
				// Email admin
				wp_mail( get_option( 'admin_email' ), esc_html__( 'Email subscription failed (Mailchimp)', 'wc_subscribe_to_newsletter' ), '(' . esc_html( $result->code ) . ') ' . esc_html( $result->error ) );
			} else {

				// Store user email id to be set in cookies at a later stage:
				// WC_Mailchimp_Newsletter_Integration::ecommerce360_set_cookies
				// https://github.com/woothemes/woocommerce-subscribe-to-newsletter/issues/18
				if ( isset( $result->euid ) ) {
					update_user_meta( get_current_user_id(), 'mailchimp_email_id', trim( $result->euid ) );
				}

				do_action( 'wc_subscribed_to_newsletter', $email );
			}
		}
	}
}// End if().
