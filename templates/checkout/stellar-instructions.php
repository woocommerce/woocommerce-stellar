<?php
/**
 * Payment Instructions
 *
 * Displays instructions for paying with Stellar on the Review Order and View Order pages
 *
 * @author    Prospresss
 * @package   WooCommerce_Stellar/Templates
 * @version   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( $order->has_status( 'pending' ) ) : ?>

<h2><?php _e( 'Stellar Instructions', 'woocommerce-stellar-gateway' ); ?></h2>
<p class="stellar-transaction-pending">
	<?php _e( 'Thank you - your order is now pending payment.', 'woocommerce-stellar-gateway' ); ?>
</p>
<div class="stellar-status" style="display:none;">
	<p class="woocommerce-info">
		<?php _e( "We're checking for your transaction now", 'woocommerce-stellar-gateway' ); ?>
	</p>
	<p class="stellar-transaction failed woocommerce-error" style="display:none;">
		<?php _e( 'Unable to find the transaction. Please check your Stellar account to confirm that a transaction has been made completed with the correct destination tag. Contact us if you need assistance.', 'woocommerce-stellar-gateway' ); ?>
	</p>
	<p class="stellar-transaction success woocommerce-message" style="display:none;">
		<?php _e( 'Your transaction was found and your order is now completed. Thank you.', 'woocommerce-stellar-gateway' ); ?>
	</p>
</div>
<div class="stellar-payment-instructions">
	<p><?php _e( 'After you have made your payment, click the "Confirm Transaction" button and we\'ll check the status of the payment.', 'woocommerce-stellar-gateway' ); ?></p>
</div>
<div>
	<a class="button alt stellar-pay-button" target="_blank" href="' . htmlspecialchars( $stellar_payment_url ) . '">
		<?php _e( 'Pay at Stellar.org' , 'woocommerce-stellar-gatewaty' ); ?>
	</a>
	<button class="button stellar-confirm" href="<?php echo site_url(); ?>">
		<?php _e( 'Confirm Payment', 'woocommerce-stellar-gateway' ); ?>
	</button>
</div>
<div class="clear"></div>

<?php elseif ( $order->has_status( array( 'completed', 'processing' ) ) ) : ?>

<p><?php _e( 'Thank you - your order has been successfully paid.', 'woocommerce-stellar-gateway' ); ?></p>

<?php endif; ?>