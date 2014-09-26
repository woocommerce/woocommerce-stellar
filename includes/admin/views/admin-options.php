<h3>Stellar</h3>

<div class="stellar-banner updated">
  <img src="<?php echo WC_Stellar()->plugin_url() . '/assets/images/logo.png'; ?>" />
  <p class="main"><strong><?php _e( 'Getting started', 'woocommerce-payment-stellar-boilerplate' ); ?></strong></p>
  <p><?php _e( 'Simply enter your Stellar account number and enable the gateway ready to recieve payment into your Stellar account.', 'woocommerce-payment-stellar-boilerplate' ); ?></p>

  <p class="main"><strong><?php _e( 'Stellar Status', 'woocommerce-payment-stellar-boilerplate' ); ?></strong></p>
  <ul>
    <li><?php echo __( 'Debug Enabled?', 'woocommerce-payment-stellar-boilerplate' ) . ' <strong>' . $this->debug . '</strong>'; ?></li>
  </ul>

  <?php if( empty( $this->account_address ) ) { ?>
  <p><a href="https://launch.stellar.org/#/register" target="_blank" class="button button-primary"><?php _e( 'Sign up for Stellar', 'woocommerce-payment-stellar-boilerplate' ); ?></a> <a href="https://www.stellar.org/about/" target="_blank" class="button"><?php _e( 'Learn more', 'woocommerce-payment-stellar-boilerplate' ); ?></a></p>
  <?php } ?>
</div>

<table class="form-table">
  <?php $this->generate_settings_html(); ?>
</table>
