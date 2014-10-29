<?php
/**
 * Payment Instructions
 *
 * Displays instructions for paying with Stellar on the Review Order and View Order pages
 *
 * @author    Prospress
 * @package   WooCommerce_Stellar/Templates
 * @version   1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( $order->has_status( 'pending' ) ) : ?>

<h2><?php _e( 'Stellar Instructions', 'woocommerce-stellar-gateway' ); ?></h2>
<p class="stellar-order-status-pending">
	<?php _e( 'Thank you - your order is now pending payment.', 'woocommerce-stellar-gateway' ); ?>
</p>
<div class="stellar-status" style="display:none;">
	<p class="stellar-transaction pending woocommerce-info" style="display:none;">
		<?php _e( 'Checking the Stellar ledger for your payment.', 'woocommerce-stellar-gateway' ); ?>
	</p>
	<p class="stellar-transaction failed woocommerce-error" style="display:none;">
		<?php _e( 'Unable to find the transaction. Please check your transaction and try again. Contact us if you need assistance.', 'woocommerce-stellar-gateway' ); ?>
		<span class="stellar-transaction-error-message"></span>
	</p>
	<p class="stellar-transaction success woocommerce-message" style="display:none;">
		<?php _e( 'Your transaction was found and payment has been completed. Thank you!', 'woocommerce-stellar-gateway' ); ?>
	</p>
</div>
<div class="stellar-payment-instructions">
	<p>
		<?php printf( __( 'Send exactly %s %s with the destination tag <code>%s</code> to: %s', 'woocommerce-stellar-gateway' ), $order->get_total(), esc_html( $order->get_order_currency() ), '<code>' . $order->id . '</code>', '<code class="stellar-address">' . esc_html( $account_address ) . '</code>' ); ?>
		<button class="stellar-copy-button button" style="display:none;">
			<?php _e( 'Copy', 'woocommerce-stellar-gateway' ); ?>
			<span class="stellar-tooltip">
				<?php _e( 'Copy Address to Clipboard', 'woocommerce-stellar-gateway' ); ?>
			</span>
		</button>
	</p>
	<p><?php _e( 'After you have completed payment and the transaction has cleared, click the <em>Confirm Transaction</em> button.', 'woocommerce-stellar-gateway' ); ?></p>
</div>
<div>
	<a class="button alt stellar-pay-button" target="_blank" href="<?php echo esc_url( $stellar_payment_url ); ?>">
		<?php _e( 'Pay at Stellar.org' , 'woocommerce-stellar-gateway' ); ?>
	</a>
	<button class="button stellar-confirm" href="<?php echo esc_url( site_url() ); ?>">
		<?php _e( 'Confirm Payment', 'woocommerce-stellar-gateway' ); ?>
	</button>
</div>
<div class="clear"></div>

<?php elseif ( $order->has_status( array( 'completed', 'processing' ) ) ) : ?>

<p><?php _e( 'Thank you - your order has been successfully paid.', 'woocommerce-stellar-gateway' ); ?></p>

<?php endif;