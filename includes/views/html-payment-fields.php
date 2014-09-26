<?php
if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.
?>

<fieldset id="stellar-form" class="stellar-account">
	<p class="form-row form-row-wide">
		<label for="stellar-account"><?php _e( 'Stellar Account Address', 'woocommerce-stellar-gateway' ); ?> <span class="required">*</span></label>
		<input id="stellar-account" class="input-text" name="customer_stellar_account" type="text" autocomplete="off" />
		<span><?php _e( 'Enter your Stellar account address shown in your Stellar dashboard.', 'woocommerce-stellar-gateway' ); ?></span>
	</p>

	<div class="clear"></div>

	<p class="form-row form-row-wide">
		<span class="stellar-signup"><?php _e( 'New to Stellar?', 'woocommerce-stellar-gateway' ); ?> <a href="https://launch.stellar.org/#/register" target="_blank"><?php _e( 'Sign up now', 'woocommerce-stellar-gateway' ); ?></a></span>
	</p>

</fieldset>
