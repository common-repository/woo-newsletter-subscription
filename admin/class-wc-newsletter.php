

<div class="container wc-newsletter">


<?php
global $wp_session;
$wnsma_settings = get_option( 'wnsma_settings' );
$double_opt = $wnsma_settings['double_opt'];
$ecommerce = $wnsma_settings['ecommerce'];

include_once( 'api/class-wc-mailchimp-newsletter-integration.php' );
$mailchimp = new WC_Mailchimp_Newsletter_Integration( $wnsma_settings['mailchimp_api'] );

$merger_tag_with_checkout_name = explode( ',',$wnsma_settings['merger_tag_with_checkout_name'] );


	?>

	<form id="wnsma_setting_form" class="form-horizontal" onsubmit="wnsma_save_settings(event);">

		<div class="panel panel-primary">
	  <div class="panel-heading"> Newsletter Subscription Configuration </div>
	  <div class="panel-body">

			<?php if ( isset( $wp_session['save-date'] ) && 'success' === $wp_session['save-date'] ) { ?>
				<div class="alert alert-success">
						<strong>Success!</strong> Your settings has been save successfully.
						<span id="alert-close">x</span>
				</div>
			<?php }
			unset( $wp_session['save-date'] );
			echo '<input type="hidden" name="nonce" id="nonce" value="' .
			esc_attr( wp_create_nonce( plugin_basename( __FILE__ ) ) ) . '" />';
			?>

			<div class="form-group WNSMA_form">
	      <label class="control-label col-sm-3" for="email">Service Provider</label>
	      <div class="col-sm-6">
					<select name="ns_service_provider"  class="form-control" id="ns_service_provider" >
						<?php do_action( 'wnsma_service_provider', $wnsma_settings['service_provider'] ); ?>
				  </select>
				</div>
				<div class="col-sm-3">
					<a href="#" data-toggle="tooltip" data-placement="right" title="Choose which service is handling your subscribers."><img src="<?php echo esc_url( WNSMA_PLUGIN_URL . '/admin/img/help.png' ) ?>">
					</a>
	      </div>
			</div>

				<div class="form-group WNSMA_form">
		      <label class="control-label col-sm-3" for="email">Default checkbox status</label>
		      <div class="col-sm-6">
						<select name="ns_checkbox_status" class="form-control" id="ns_checkbox_status" >
		          <option value="1"  <?php echo esc_attr( ( '1' === $wnsma_settings['checkbox_status'] ) ? 'selected' : '' ); ?>>checked</option>
		          <option value="0"  <?php echo esc_attr( ( '0' === $wnsma_settings['checkbox_status'] ) ? 'selected' : '' ); ?>>Un-checked</option>
		        </select>
					</div>
					<div class="col-sm-3">
						<a href="#" data-toggle="tooltip" data-placement="right" title="The default state of the subscribe checkbox. Be aware some countries have laws against using opt-out checkboxes."><img src="<?php echo esc_url( WNSMA_PLUGIN_URL . '/admin/img/help.png' ) ?>"></a>
		      </div>
			</div>


				<div class="form-group WNSMA_form">
		      <label class="control-label col-sm-3" for="email">Subscribe checkbox label</label>
		      <div class="col-sm-6">
						<input type="text"  class="form-control" value="<?php echo esc_attr( ( '' === $wnsma_settings['checkbox_label'] ) ? 'Subscribe to Newsletter?' : $wnsma_settings['checkbox_label'] ); ?>" name="ns_checkbox_label" id="ns_checkbox_label" >
					</div>
					<div class="col-sm-3">
						<a href="#" data-toggle="tooltip" data-placement="right" title="The text you want to display next to the 'subscribe to newsletter' checkboxes."><img src="<?php echo esc_url( WNSMA_PLUGIN_URL . '/admin/img/help.png' ) ?>"></a>
		      </div>
			</div>

					<div class="form-group">
					<div class="divider">
				      <span>API settings</span>
				    </div>
					</div>
			<div id="MailChimp_api_setting" class="<?php echo ( ( 'mailchimp' === $wnsma_settings['service_provider'] ) || ( ! isset( $wnsma_settings['service_provider'] ))) ? '' : 'display-none'; ?>">

							<?php do_action( 'wnsma_mailchimp_setting', $wnsma_settings['mailchimp_api'], $wnsma_settings['mailchimp_list'], $double_opt, $ecommerce, $wnsma_settings['store_id'] ); ?>

			</div>
			<div id="CMonitor_api_setting" class="<?php echo ( ( 'cmonitor' === $wnsma_settings['service_provider'] ) ) ? '' : 'display-none'; ?>">

			<?php

			$cmonitor_double_opt = (isset( $wnsma_settings['cmonitor_double_opt'] ))? $wnsma_settings['cmonitor_double_opt'] : 'no';
			$cmonitot_api = (isset( $wnsma_settings['cmonitor_api'] ))? $wnsma_settings['cmonitor_api'] : '';
			$cmonitot_list = (isset( $wnsma_settings['cmonitor_list'] ))? $wnsma_settings['cmonitor_list'] : '';
			 do_action( 'wnsma_cmonitor_setting', $cmonitot_api, $cmonitot_list, $cmonitor_double_opt );

			?>
			</div>

				<div class="form-group">
				<div class="divider">
						<span> Fields Settings</span>
					</div>
				</div>

				<div id="MailChimp_fields_setting" class="<?php echo ( 'mailchimp' === $wnsma_settings['service_provider'] || ( ! isset( $wnsma_settings['service_provider'] ))) ? '' : 'display-none'; ?>">

					<?php do_action( 'wnsma_mailchimp_fields_setting', $wnsma_settings['mailchimp_list'], $merger_tag_with_checkout_name ); ?>

				</div>

				<div id="CMonitor_fields_setting" class="<?php echo ( 'cmonitor' === $wnsma_settings['service_provider']) ? '' : 'display-none'; ?>">

					<?php
					$custom_key_with_checkout_name = (isset( $wnsma_settings['custom_key_with_checkout_name'] ))? explode( ',',$wnsma_settings['custom_key_with_checkout_name'] ) : '';
					$cmonitot_list = (isset( $wnsma_settings['cmonitor_list'] ))? $wnsma_settings['cmonitor_list'] : '';

					do_action( 'wnsma_cmonitor_fields_setting', $cmonitot_list, $custom_key_with_checkout_name );
			?>
				</div>

		<div class="form-group">
		<div class="divider">
		  </div>
		</div>

					<div class="form-group" align="center">
							<input type="submit" class="btn btn-primary" name="ns_submit_setting" id="ns_submit_setting" value="Save Settings">
				</div>

			</div>

	</div>


	</form>

</div>
