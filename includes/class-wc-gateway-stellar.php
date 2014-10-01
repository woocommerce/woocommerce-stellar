<?php
if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * WooCommerce Stellar Gateway.
 *
 * @class   WC_Gateway_Stellar
 * @extends WC_Payment_Gateway
 * @version 1.0.0
 * @package WooCommerce Stellar Gateway/Includes
 * @author  Sebastien Dumont
 */
class WC_Gateway_Stellar extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->id                 = 'stellar';
		$this->icon               = apply_filters( 'woocommerce_stellar_icon', plugins_url( '/assets/images/stellar_rocket.png', dirname( __FILE__ ) ) );
		$this->has_fields         = false;

		$this->order_button_text  = __( 'Place order', 'woocommerce-stellar-gateway' );

		$this->method_title       = 'Stellar';
		$this->method_description = __( 'Take payments via Stellar.', 'woocommerce-stellar-gateway' );

		$this->supports           = array(
			'products',
			'refunds',
		);

		$this->view_transaction_url = 'http://stellarchain.io/view/tx/%s';

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values.
		$this->enabled         = $this->get_option( 'enabled' );

		$this->title           = $this->get_option( 'title' );
		$this->description     = $this->get_option( 'description' );

		$this->account_address = $this->get_option( 'account_address' );
		$this->expiration      = $this->get_option( 'expiration' );
		$this->retries         = $this->get_option( 'retries' );

		$this->debug           = $this->get_option( 'debug' );

		// Logs.
		if( $this->debug == 'yes' ) {
			if( class_exists( 'WC_Logger' ) ) {
				$this->log = new WC_Logger();
			}
			else {
				$this->log = $woocommerce->logger();
			}
		}

		// Hooks.
		if( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'checks' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_view_order', array( $this, 'stellar_instructions' ), 11, 1 );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @access public
	 * @return void
	 */
	public function admin_options() {
		include_once( WC_Stellar()->plugin_path() . '/includes/admin/views/admin-options.php' );
	}

	/**
	 * Check if SSL is enabled and notify the user.
	 *
	 * @access public
	 */
	public function checks() {
		if( $this->enabled == 'no' ) {
			return;
		}

		// Check required fields.
		else if( !$this->account_address ) {
			echo '<div class="error"><p>' . __( 'Stellar Error: Please enter your favourite Stellar wallet account number.', 'woocommerce-stellar-gateway' ) . '</p></div>';
		}
	}

	/**
	 * Check if this gateway is enabled.
	 *
	 * @access public
	 */
	public function is_available() {
		if( $this->enabled == 'no' ) {
			return false;
		}

		if( !$this->account_address ) {
			return false;
		}

		return true;
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * The standard gateway options have already been applied.
	 * Change the fields to match what the payment gateway your building requires.
	 *
	 * @access public
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'       => __( 'Enable/Disable', 'woocommerce-stellar-gateway' ),
				'label'       => __( 'Enable Stellar', 'woocommerce-stellar-gateway' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-stellar-gateway' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-stellar-gateway' ),
				'default'     => 'Stellar',
				'desc_tip'    => true
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-stellar-gateway' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-stellar-gateway' ),
				'default'     => 'Pay with Stellar using your favourite Stellar wallet.',
				'desc_tip'    => true
			),
			'debug' => array(
				'title'       => __( 'Debug Log', 'woocommerce-stellar-gateway' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'woocommerce-stellar-gateway' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log Stellar events inside <code>%s</code>', 'woocommerce-stellar-gateway' ), wc_get_log_file_path( $this->id ) )
			),
			'account_address' => array(
				'title'       => __( 'Stellar Address', 'woocommerce-stellar-gateway' ),
				'type'        => 'text',
				'description' => __( 'Enter your Stellar Address from your Stellar account. This is where payments will be sent by customers paying with Stellar.', 'woocommerce-stellar-gateway' ),
				'default'     => '',
				'desc_tip'    => false
			),
			'expiration' => array(
				'title' => __( 'Payment Window', 'woocommerce-stellar-gateway' ),
				'type' => 'select',
				'description' => __( 'Amount of time before the payment window expires.', 'woocommerce-stellar-gateway' ),
				'default' => '600',
				'options' => array(
					'60'   => sprintf( __( '%s minute', 'woocommerce-stellar-gateway' ), 1 ),
					'300'  => sprintf( __( '%s minutes', 'woocommerce-stellar-gateway' ), 5 ),
					'600'  => sprintf( __( '%s minutes', 'woocommerce-stellar-gateway' ), 10 ),
					'900'  => sprintf( __( '%s minutes', 'woocommerce-stellar-gateway' ), 15 ),
					'1800' => sprintf( __( '%s minutes', 'woocommerce-stellar-gateway' ), 30 ),
					'3600' => sprintf( __( '%s hour', 'woocommerce-stellar-gateway' ), 1 ),
					)
				),
			'retries' => array(
				'title' => __( 'Retries', 'woocommerce-stellar-gateway' ),
				'type' => 'select',
				'description' => __( 'Amount of retries to check for a transaction.', 'woocommerce-stellar-gateway' ),
				'default' => '7',
				'options' => array(
					'1'  => __( 'One', 'woocommerce-stellar-gateway' ),
					'2'  => __( 'Two', 'woocommerce-stellar-gateway' ),
					'3'  => __( 'Three', 'woocommerce-stellar-gateway' ),
					'4'  => __( 'Four', 'woocommerce-stellar-gateway' ),
					'5'  => __( 'Five', 'woocommerce-stellar-gateway' ),
					'6'  => __( 'Six', 'woocommerce-stellar-gateway' ),
					'7'  => __( 'Seven', 'woocommerce-stellar-gateway' ),
					'8'  => __( 'Eight', 'woocommerce-stellar-gateway' ),
					'9'  => __( 'Nine', 'woocommerce-stellar-gateway' ),
					'10' => __( 'Ten', 'woocommerce-stellar-gateway' ),
				)
			),
		);
	}

	/**
	 * Payment form on checkout page.
	 *
	 * @access public
	 */
	public function payment_fields() {
		$description = $this->get_description();

		if( $this->debug == 'yes' ) {
			$description .= ' ' . __( 'DEBUG MODE ENABLED!' );
		}

		if( !empty( $description ) ) {
			echo wpautop( wptexturize( trim( $description ) ) );
		}
	}

	/**
	 * Output for the order received page.
	 *
	 * @access public
	 * @return void
	 */
	public function receipt_page( $order_id ) {
		$this->stellar_instructions( $order_id );
	}

	/**
	 * Output for the thank you page.
	 *
	 * @access public
	 */
	public function thankyou_page( $order_id ) {
		$this->stellar_instructions( $order_id );
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @access public
	 * @param  WC_Order $order
	 * @param  bool $sent_to_admin
	 * @param  bool $plain_text
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if( !$sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'pending' ) ) {
			$this->stellar_instructions( $order->id, 'email' );
		}
	}

	/**
	 * Gets the extra details you set here to be
	 * displayed on the 'Thank you' page.
	 *
	 * @access private
	 */
	private function stellar_instructions( $order_id, $reciept = '' ) {
		$stellar = new Stellar();

		$order = wc_get_order( $order_id );

		if( $order->get_status() == 'pending' ) {// If the order still needs verifying then display the instructions.
			echo '<h2>' . __( 'Stellar Instructions', 'woocommerce-stellar-gateway' ) . '</h2>' . PHP_EOL;
			echo '<p>' . __( 'Thank you - your order is now pending payment.', 'woocommerce-stellar-gateway' ) . '</p>';

			if( !empty( $reciept ) ) {
				echo '<p>' . __( 'After you have made your payment, return back to your receipt and press "Confirm Payment". You will have to wait several minutes before we can confirm your payment.', 'woocommerce-stellar-gateway' ) . '</p>';
			} else {
				echo '<p>' . __( 'After you have made your payment, you have to wait several minutes before we confirm your payment.', 'woocommerce-stellar-gateway' ) . '</p>';
			}

			echo '<p>' . __( 'We will send you an email after your payment is received. Please be patient.', 'woocommer-stellar-gateway' ) . '</p>';
			echo '<p>' . sprintf( __( 'Please make sure that you don\'t forget to add the destination tag <strong>?dt=%s</strong>', 'woocommerce-steller-gateway' ), $order_id ) . '</p>';
			$url = $stellar->get_stellar_url( $order_id );

			echo '<p><a class="button alt" target="_blank" href="' . htmlspecialchars( $url ) . '">' . __( 'Login to Stellar to Pay' , 'woocommerce-stellar-gatewaty' ) . '</a> ';
			// This section is only shown on the reciept and view order page.
			if( empty( $reciept ) ) {
				echo '<a class="button stellar-confirm" href="' . get_site_url() . '/?confirm_stellar_payment=' . $order_id . '">' . __( 'Confirm Payment', 'woocommerce-stellar-gateway' ) . '</a></p>' .
					'<div class="clear"></div>';

				echo '<div class="stellar-status" style="display:none;">' .
					'<h2>' . __( 'We\'re checking for your transaction now', 'woocommerce-stellar-gateway' ) . '</h2>' .
						'<span class="stellar-countdown"></span>' .
							'<span class="stellar-retries" style="display:none;">0</span>' .
								'<p class="stellar-transaction failed" style="display:none;">' . __( 'We we\'re unable to find the transaction at this time. Please check your Stellar account that a transaction was made and contact the store owner.', 'woocommerce-stellar-gateway' ) . '</p>' .
									'<p class="stellar-transaction success" style="display:none;">' . __( 'Your transaction was found and your order is now completed. Thank you.', 'woocommerce-stellar-gateway' ) . '</p>' .
										'</div>';
				echo '<div class="clear"></div>';
			} else {
				// This link is added to the email, so the customer can validate the transaction once payment has been made.
				echo '<a href="' . esc_url( $order->get_view_order_url() ) . '">' . __( 'Confirm Payment', 'woocommerce-stellar-gateway' ) . '</a>';
			}

			if( !empty( $reciept ) ) {
				echo '</p>';
			}
		}
	}

	/**
	 * Process the payment and redirect.
	 *
	 * @access public
	 * @param  int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );

		// Mark as 'pending' (we're awaiting the payment)
		$order->update_status( 'pending', __( 'Awaiting Stellar payment and verification.', 'woocommerce-stellar-gateway' ) );

		// Reduce stock levels.
		if( $this->debug != 'yes' ) {
			$order->reduce_order_stock();
		}

		// Remove cart, leave as is if debugging.
		if( $this->debug != 'yes' ) {
			WC()->cart->empty_cart();
		}

		// Return to reciept page redirect.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order )
		);
	}

}
