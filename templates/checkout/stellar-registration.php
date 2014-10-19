<?php
/**
 * Stellar Registration Link
 *
 * Display a registration link for new Stellar users.
 *
 * @author    Prospress
 * @package   WooCommerce_Stellar/Templates
 * @version   1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>

<div class="stellar-registration">
	<?php _e( 'New to Stellar?', 'woocommerce-stellar-gateway' ); ?>
	<a href="https://launch.stellar.org/#/register" target="_blank">
		<?php _e( 'Sign up now', 'woocommerce-stellar-gateway' ); ?>
	</a>
</div>
