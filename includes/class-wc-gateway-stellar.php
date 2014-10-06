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
		$this->method_description = __( 'Accept payments in the Stellar cryptocurrency and via the Stellar protocol.', 'woocommerce-stellar-gateway' );

		$this->supports           = array(
			'products',
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

		$this->debug           = $this->get_option( 'debug' );

		// Logs.
		if( $this->debug == 'yes' ) {
			$this->log = new WC_Logger();
		}

		// Hooks.
		if( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'checks' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

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
		if ( 'no' !== $this->enabled && ! $this->account_address ) {
			echo '<div class="error"><p>' . __( 'Stellar Error: Please enter your favourite Stellar wallet account number.', 'woocommerce-stellar-gateway' ) . '</p></div>';
		}
	}

	/**
	 * Check if this gateway can be enabled on checkout.
	 *
	 * @access public
	 */
	public function is_available() {
		if ( 'no' == $this->enabled || ! $this->account_address ) {
			return false;
		}
		// Checks the currency is accepted by the Stellar Account
		$return = false;
		$store_currency = get_woocommerce_currency();
		// Stellar accounts can always receive Stellars by default
		if( $store_currency == 'STR' || in_array( $store_currency, $this->get_option( 'accepted_currencies' ) ) ) {
			$return = true;
		}

		return $return;
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
		// get the list of accepted currencies and include STR in that list
		$currencies = array_merge( array( 'STR' ), $this->get_option( 'accepted_currencies', array() ) );
		$accepted_currencies_string = sprintf( _x( '%s and %s', 'currency list', 'woocommerce-stellar-gateway' ), implode( _x( ', ', 'currency separator', 'woocommerce-stellar-gateway' ), array_slice( $currencies, 0, count( $currencies ) - 1 ) ), array_pop( $currencies ) );

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
				'description' => __( 'This controls the title which the customer sees during checkout.', 'woocommerce-stellar-gateway' ),
				'default'     => __( 'Stellar', 'woocommerce-stellar-gateway' ),
				'desc_tip'    => true
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-stellar-gateway' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the customer sees during checkout.', 'woocommerce-stellar-gateway' ),
				'default'     => __( 'Pay with Stellar using your favourite Stellar wallet.', 'woocommerce-stellar-gateway' ),
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
				'description' => __( 'Enter your Stellar address. This is where payments will be sent.', 'woocommerce-stellar-gateway' ),
				'default'     => '',
				'desc_tip'    => false
			),
			'account_accepted_currencies' => array(
				'title'       => __( 'Stellar Accepted Currencies', 'woocommerce-stellar-gateway' ),
				'type'		  => 'title',
				'description' => sprintf( __( "Your Stellar Account is currently set to accept the following currencies: %s If your store’s currency is not accepted by your stellar account, the Stellar gateway will not be displayed as a payment option on checkout.%s Your store's currency is currently set to %s and can be modified in %sWooCommerce Settings%s. More information on how to add more accepted currencies to your Stellar %saccount can be found at the following link: %s Adding More Currencies %s. Update these Stellar Settings to pull the new accepted currencies from your stellar Account.", 'woocommerce-stellar-gateway' ), '<strong>' . $accepted_currencies_string . '.</strong><br><br>', '<br>', '<strong>' . get_woocommerce_currency() . '</strong>', '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings' ) ) . '">', '</a>','<br>', '<a href="https://github.com/stellar/docs/blob/master/docs/Adding-Multiple-Currencies.md">', '</a>' ),
				'desc_tip'    => false
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

		if( ! empty( $description ) ) {
			echo wpautop( wptexturize( trim( $description ) ) );
			echo wc_get_template( 'checkout/stellar-registration.php', array(), '', WC_Stellar()->template_path() );
		}
	}

	/**
	 * Output for the order received page.
	 *
	 * @access public
	 * @return void
	 */
	public function receipt_page( $order_id ) {
		WC_Stellar()->stellar_instructions( $order_id );
	}

	/**
	 * Output for the thank you page.
	 *
	 * @access public
	 */
	public function thankyou_page( $order_id ) {
		WC_Stellar()->stellar_instructions( $order_id );
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
		if ( ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'pending' ) ) {
			WC_Stellar()->stellar_instructions( $order->id, 'email' );
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
