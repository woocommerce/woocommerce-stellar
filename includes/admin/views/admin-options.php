<h3><?php _e( 'Stellar', 'woocommerce-stellar-gateway' ); ?></h3>

<div class="stellar-banner updated">
	<img src="<?php echo WC_Stellar()->plugin_url() . '/assets/images/logo.png'; ?>" />
	<p class="main"><strong><?php _e( 'Getting started', 'woocommerce-stellar-gateway' ); ?></strong></p>
	<p><?php _e( 'Enter your Stellar address and enable the gateway ready to recieve payment into your Stellar account.', 'woocommerce-stellar-gateway' ); ?></p>

	<p class="main"><strong><?php _e( 'Stellar Status', 'woocommerce-stellar-gateway' ); ?></strong></p>
	<ul>
		<li><?php echo __( 'Debug Enabled?', 'woocommerce-stellar-gateway' ) . ' <strong>' . $this->debug . '</strong>'; ?></li>
	</ul>

	<?php if ( empty( $this->account_address ) ) { ?>
		<p>
			<a href="https://launch.stellar.org/#/register" target="_blank" class="button button-primary"><?php _e( 'Sign up for Stellar', 'woocommerce-stellar-gateway' ); ?></a>
			<a href="https://www.stellar.org/about/" target="_blank" class="button"><?php _e( 'Learn more', 'woocommerce-stellar-gateway' ); ?></a>
		</p>
	<?php } ?>
</div>

<table class="form-table">
	<?php $this->generate_settings_html(); ?>
</table>
