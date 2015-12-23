<?php
/*
 * Plugin Name:       WooCommerce Stellar Gateway
 * Plugin URI:        http://wordpress.org/extend/plugins/woocommerce-stellar/
 * Description:       Accept payment for WooCommerce orders via Stellar (both the currency and the protocol).
 * Version:           1.0.2
 * Author:            Prospress, Inc.
 * Author URI:        http://www.prospress.com
 * Text Domain:       woocommerce-stellar-gateway
 * Domain Path:       languages
 * Network:           false
 * GitHub Plugin URI: https://github.com/Prospress/woocommerce-stellar
 *
 * Copyright (C) 2014 Prospress, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WooCommerce Stellar Gateway. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package  WooCommerce Stellar
 * @author   Prospress
 * @category Core
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Required functions
 */
require_once('woo-includes/woo-functions.php');

if ( ! is_woocommerce_active() || version_compare( get_option( 'woocommerce_db_version' ), '2.1', '<' ) ) {
	add_action( 'admin_notices', 'WC_Stellar::woocommerce_missing_notice' );
	return;
}

if ( ! class_exists( 'WC_Stellar' ) ) {

/**
 * WooCommerce Stellar main class.
 *
 * @class   Stellar
 * @version 1.0
 */
final class WC_Stellar {

	/**
	 * Instance of this class.
	 *
	 * @access protected
	 * @access static
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Slug
	 *
	 * @access public
	 * @var    string
	 */
	public $gateway_slug = 'stellar';

	/**
	 * The Gateway Name.
	 *
	 * @access public
	 * @var    string
	 */
	public $name = "Stellar";

	/**
	 * Gateway version.
	 *
	 * @access public
	 * @var    string
	 */
	public $version = '1.0';

	/**
	 * The Gateway documentation URL.
	 *
	 * @access public
	 * @var    string
	 */
	public $doc_url = "http://wordpress.org/extend/plugins/woocommerce-stellar/";

	/**
	 * Gateway version.
	 *
	 * @access public
	 * @var    string
	 */
	public $gateway_settings;

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since  1.0
	 * @access public
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-stellar-gateway' ), $this->version );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @since  1.0
	 * @access public
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-stellar-gateway' ), $this->version );
	}

	/**
	 * Initialize the plugin public actions.
	 *
	 * @access private
	 */
	private function __construct() {
		// Settings
		$this->set_gateway_settings();

		// Hooks.
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'woocommerce_view_order', array( $this, 'stellar_instructions' ), 11, 1 );

		add_action( 'woocommerce_stellar_cron_job', array( $this, 'check_pending_payments' ), 10, 1 );

		add_action( 'woocommerce_update_options_payment_gateways_stellar', array( $this, 'set_gateway_settings' ), 10, 1 );

		add_filter( 'cron_schedules', array( $this, 'add_cron_schedule' ), 10, 1 );

		// Check we have the minimum version of WooCommerce required before loading the gateway.
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.2', '>=' ) ) {
			if ( class_exists( 'WC_Payment_Gateway' ) ) {

				$this->includes();

				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
				add_filter( 'woocommerce_currencies', array( $this, 'add_currency' ) );
				add_filter( 'woocommerce_currency_symbol', array( $this, 'add_currency_symbol' ), 10, 2 );
				add_action( 'wp_ajax_confirm_stellar_payment', array( $this, 'confirm_stellar_payment' ), 11);
				add_action( 'wp_ajax_nopriv_confirm_stellar_payment', array( $this, 'confirm_stellar_payment' ), 11);
				add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
				add_action( 'woocommerce_settings_api_sanitized_fields_stellar' , array( $this, 'stellar_accepted_currencies' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'stellar_admin_scripts' ) );
				add_action( 'admin_init', array( $this, 'stellar_destination_tag_check' ) );
				add_action( 'admin_notices', array( $this, 'missing_stellar_address_notice' ) );
			}
		} else {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			add_action( 'admin_notices', array( $this, 'upgrade_notice' ) );
			return false;
		}

		if ( ! wp_next_scheduled( 'woocommerce_stellar_cron_job' ) ) {
			wp_schedule_event( time(), 'every_ten_minutes', 'woocommerce_stellar_cron_job' );
		}
	}

	/**
	 * Checks whether or not the current page is the stellar settings page.
	 */
	public function set_gateway_settings() {
		$this->gateway_settings = get_option( 'woocommerce_stellar_settings', array( 'accepted_currencies' => array(), 'account_address' => '' ) );
	}

	public function stellar_admin_scripts() {
		wp_enqueue_script( 'wc_stellar_admin_script', $this->plugin_url() . '/assets/js/admin/stellar.js', array( 'jquery' ) );

		wp_localize_script( 'wc_stellar_admin_script', 'wc_stellar_admin_js', array(
			'success_url' => add_query_arg( 'stellar_check_destination_flag', 'true' )
		) );
	}

	/**
	 * Checks whether or not the current page is the stellar settings page.
	 */
	public function is_stellar_settings_page() {
		if ( is_admin() && isset( $_GET['tab'] ) && 'checkout' == $_GET['tab'] && isset( $_GET['section'] ) && 'wc_gateway_stellar' == $_GET['section'] ) {
			return true;
		}
		return false;
	}

	/**
	 * Returns true if a new Stellar Address is submittited. This will trigger a new Stellar API request
	 * to check whether the account has the correct flags set.
	 *
	 * @return boolean
	 */
	public function stellar_new_account_address_submitted() {

		if ( isset( $_POST['woocommerce_stellar_account_address'] ) && $_POST['woocommerce_stellar_account_address'] != $this->gateway_settings['account_address'] ) {
			return true;
		}
		return false;
	}

	/**
	 * Run through a group of checks to determine the notice to show the administrators upon navigating
	 * to the Stellar Settings Page.
	 */
	public function stellar_destination_tag_check() {

		if ( ! $this->is_stellar_settings_page() ) {
			return;
		}

		if ( isset( $_GET['stellar_hide_dest_tag_notice'] ) && 'true' == $_GET['stellar_hide_dest_tag_notice'] ) {
			update_option( 'stellar_destination_tag_requirement_checked', 'ignore' );
			wp_redirect( remove_query_arg( 'stellar_hide_dest_tag_notice' ) );
			exit;
		}

		if ( isset( $_GET['stellar_check_destination_flag'] ) || $this->stellar_new_account_address_submitted() ) {

			$result = $this->stellar_check_destination_tag_requirement();
			update_option( 'stellar_destination_tag_requirement_checked', $result );

			if ( isset( $_GET['stellar_check_destination_flag'] ) ) {
				wp_redirect( remove_query_arg( 'stellar_check_destination_flag' ) );
				exit;
			}
		}

		$destination_tag_requirement = get_option( 'stellar_destination_tag_requirement_checked', '' );

		if ( ! empty( $destination_tag_requirement ) && 'error' == $destination_tag_requirement ) {
			add_action( 'admin_notices', array( $this, 'stellar_invalid_account_notice' ) );
		} elseif ( ! empty( $destination_tag_requirement ) && 'checked' == $destination_tag_requirement ) {
			add_action( 'admin_notices', array( $this, 'stellar_show_destination_tag_notice' ) );
		}

	}

	/**
	 * Stellar API Request checking the flags set on the account address set in the stellar settings. Returns a string 
	 * representing the result of the api request which is later set in the stellar_destination_tag_requirement_checked 
	 * option to show specific notices to the admin.
	 *
	 * @return String 'success'|'checked'|'error'
	 */
	public function stellar_check_destination_tag_requirement() {
		$account_id = ( isset( $_POST['woocommerce_stellar_account_address'] ) ) ? $_POST['woocommerce_stellar_account_address'] : $this->gateway_settings['account_address'];
		$error = false;
		$result = '';

		if ( ! empty( $account_id ) ) {
			$result = 'checked';
			$url = 'https://live.stellar.org:9002';
			$stellar_request = '{
				"method": "account_info",
				"params": [{
					"account": "' . $account_id . '"
				}]
			}';

			$response = $this->send_to( $url, $stellar_request );
			if ( ! is_wp_error ( $response ) ) {
				$response = json_decode( $response['body'] );
				if ( ! empty( $response->result ) && isset( $response->result->account_data ) ) {
					if ( 131072 == $response->result->account_data->Flags ) {
						$result = 'success';
					}
				} else {
					$result = 'error';
				}
			}
		}

		return $result;
	}

	/**
	 * Missing stellar account address notice when the stellar payment gateway is enabled.
	 * Shows up on all admin pages.
	 *
	 * @access public
	 */
	public function missing_stellar_address_notice() {
		// get the most updated value for the gateway-enabled checkbox
		if ( empty ( $_POST ) ) {
			// stellar settings page reloaded - use the value stored in the settings
			$enabled = ( ! empty( $this->gateway_settings['enabled'] ) ) ? $this->gateway_settings['enabled'] : 'no';
		// check if the post data belongs to stellar
		} else if ( isset( $_POST['woocommerce_stellar_title'] ) ) {
			// stellar settings have been saved - use the value stored in the _POST data.
			$enabled = ( isset( $_POST['woocommerce_stellar_enabled'] ) ) ? $_POST['woocommerce_stellar_enabled'] : 'no';
		}
		// retrieve the most recent stellar account address value
		$account_id = ( isset( $_POST['woocommerce_stellar_account_address'] ) ) ? $_POST['woocommerce_stellar_account_address'] : $this->gateway_settings['account_address'];

		if ( $this->is_stellar_settings_page() && 'no' !== $enabled && empty( $account_id ) ) {
			echo '<div class="error"><p>' . sprintf( __( 'The Stellar Gateway is enabled without a Stellar Address. Please visit the %sstellar settings page%s and enter your Stellar address.', 'woocommerce-stellar-gateway' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_stellar' ) ) . '">', '</a>' ) . '</p></div>';
		}
	}

	public function stellar_invalid_account_notice() {
		echo '<div class="error"><p>' . __( 'The Stellar account address is invalid. ', 'woocommerce-stellar-gateway' ) . '</p></div>';
	}

	/**
	 * Show notice to admins when their stellar account is set to allow transactions without destination
	 * tags.
	 */
	public function stellar_show_destination_tag_notice() {

		if ( empty( $this->gateway_settings['account_address'] ) ) {
			return;
		} ?>
		<div class="error woocommerce-message">
			<p>
				<?php printf( __( 'Your Stellar account allows transactions without a %sDestination Tag%s. Without a destination tag, there is no way to match a payment with an order.', 'woocommerce-stellar-gateway' ), '<a href="' . esc_url( 'https://github.com/stellar/docs/blob/master/docs/Destination-Tags.md' ) . '" target="_blank">', '</a>' ); ?>
			</p>
			<p>
				<?php printf( __( 'To setup your account to require destination tags, enter your Stellar Account Secret Key below and click %sSet Flag%s. Find your Secret Key under your %sStellar Settings%s.', 'woocommerce-stellar-gateway' ), '<strong>', '</strong>', '<a href="' . esc_url( 'https://launch.stellar.org/#/settings' ) . '" target="_blank">', '</a>' ); ?>
			</p>
			<p style="display:none;">
				<?php printf( __( 'Your Request failed with the following error: %s', 'woocommerce-stellar-gateway' ), '<span class="stellar_set_account_flag_error"></span>' ); ?>
			</p>
			<p>
				<input type="text" id="stellar_secret_key" placeholder="<?php esc_attr_e( 'Stellar Secret Key', 'woocommerce-stellar-gateway' ); ?>">
				<img class="help_tip" data-tip="<?php esc_attr_e( 'Your Secret Key will be sent directly to Stellar.org to setup your account. It will not be sent or stored anywhere else.', 'woocommerce-stellar-gateway' ); ?>" src="<?php echo WC()->plugin_url() ?>/assets/images/help.png" height="16" width="16" style="margin: -2px 0 0 0;"/>
				<a href="#" class="button-primary stellar-set-destination-tag-flag">
					<?php _e( 'Set Flag', 'woocommerce-stellar-gateway' ); ?>
				</a>
				<a href="<?php esc_url( add_query_arg( 'stellar_check_destination_flag', 'true' ) ); ?>" class="button-primary stellar-set-destination-tag-flag">
					<?php _e( 'Check Again', 'woocommerce-stellar-gateway' ); ?>
				</a>
				<a href="<?php esc_url( add_query_arg( 'stellar_hide_dest_tag_notice', 'true' ) ); ?>" class="button-primary stellar-set-destination-tag-flag">
					<?php _e( 'Ignore Notice', 'woocommerce-stellar-gateway' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Store the Stellar Account's accepted currencies when the stellar settings have been udpated.
	 */
	public function stellar_accepted_currencies( $settings ) {

		// stellar request params
		$account_id = $settings['account_address'];
		$url = 'https://live.stellar.org:9002';

		$stellar_request = '{
				"method": "account_currencies",
				"params": [
					{
						"account": "' . $account_id . '"
					}
				]
			}';

		$response = $this->send_to( $url, $stellar_request );

		if ( ! is_wp_error ( $response ) ) {
			$response = json_decode( $response['body'] );
			if ( ! empty( $response->result ) && isset( $response->result->receive_currencies ) ) {
				$settings['accepted_currencies'] = $response->result->receive_currencies;
			}
		}

		return $settings;
	}

	/**
	 * Plugin action links.
	 *
	 * @access public
	 * @param  mixed $links
	 * @return void
	 */
	public function action_links( $links ) {

		if ( current_user_can( 'manage_woocommerce' ) ) {

			$plugin_links = array(
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_' . $this->gateway_slug ) . '">' . __( 'Payment Settings', 'woocommerce-stellar-gateway' ) . '</a>',
			);

			$links = array_merge( $plugin_links, $links );
		}

		return $links;
	}

	/**
	 * Plugin row meta links
	 *
	 * @access public
	 * @param  array $input already defined meta links
	 * @param  string $file plugin file path and name being processed
	 * @return array $input
	 */
	public function plugin_row_meta( $input, $file ) {

		if ( plugin_basename( __FILE__ ) !== $file ) {
			return $input;
		}

		$links = array(
			'<a href="' . esc_url( $this->doc_url ) . '">' . __( 'Documentation', 'woocommerce-stellar-gateway' ) . '</a>',
		);

		$input = array_merge( $input, $links );

		return $input;
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any
	 * following ones if the same translation is present.
	 *
	 * @access public
	 * @return void
	 */
	public function load_plugin_textdomain() {
		// Set filter for plugin's languages directory
		$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
		$lang_dir = apply_filters( 'woocommerce_' . $this->gateway_slug . '_languages_directory', $lang_dir );

		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale',  get_locale(), 'woocommerce-stellar-gateway' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'woocommerce-stellar-gateway', $locale );

		// Setup paths to current locale file
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/' . 'woocommerce-stellar-gateway' . '/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/woocommerce-stellar-gateway/ folder
			load_textdomain( 'woocommerce-stellar-gateway', $mofile_global );
		} else if ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/woocommerce-stellar-gateway/languages/ folder
			load_textdomain( 'woocommerce-stellar-gateway', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'woocommerce-stellar-gateway', false, $lang_dir );
		}
	}

	/**
	 * Include files.
	 *
	 * @access private
	 * @return void
	 */
	private function includes() {
		include_once( 'includes/class-wc-gateway-stellar.php' ); // Payment Gateway.
	}

	/**
	 * Add the gateway.
	 *
	 * @access public
	 * @param  array $methods WooCommerce payment methods.
	 * @return array WooCommerce Stellar gateway.
	 */
	public function add_gateway( $methods ) {
		$methods[] = 'WC_Gateway_' . str_replace( ' ', '_', $this->name );

		return $methods;
	}

	/**
	 * Add the currency.
	 *
	 * @access public
	 * @return array
	 */
	public function add_currency( $currencies ) {
		$currencies['STR'] = $this->name;
		return $currencies;
	}

	/**
	 * Add the currency symbol.
	 *
	 * @access public
	 * @return string
	 */
	public function add_currency_symbol( $currency_symbol, $currency ) {
		if ( 'STR' === $currency ) {
			$currency_symbol = 'STR';
		}
		return $currency_symbol;
	}

	/**
	 * This gets and returns true if the payment method
	 * used on the order was the Stellar gateway.
	 *
	 * @access  public
	 * @returns boolen
	 */
	public function is_payment_method_stellar( $order_id ) {

		$payment_method = get_post_meta( $order_id, '_payment_method' );

		if ( $payment_method == 'stellar' ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * is_view_order_page - Returns true when viewing the order received page.
	 *
	 * @access public
	 * @return bool
	 */
	public function is_view_order_page() {
		global $wp;

		return ( is_page( wc_get_page_id( 'myaccount' ) ) && isset( $wp->query_vars['view-order'] ) ) ? true : false;
	}

	/**
	 * Outputs scripts used for the payment gateway.
	 *
	 * @access public
	 */
	public function payment_scripts() {
		global $wp;

		if ( is_order_received_page() || $this->is_view_order_page() ) {

			if ( isset( $wp->query_vars['order-received'] ) ) {
				$order_id = $wp->query_vars['order-received'];
			} elseif ( isset( $wp->query_vars['view-order'] ) ) {
				$order_id = $wp->query_vars['view-order'];
			}

			wp_enqueue_script( 'wp_zeroclipboard', $this->plugin_url() . '/assets/js/ZeroClipboard.min.js', array(), $this->version );
			wp_enqueue_script( 'wc_stellar_script', $this->plugin_url() . '/assets/js/verify-stellar.js', array( 'jquery', 'wp_zeroclipboard' ), $this->version );

			wp_localize_script( 'wc_stellar_script', 'wc_stellar_js', array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'order_id'    => $order_id,
				'SWFPath'     => $this->plugin_url() . '/assets/js/ZeroClipboard.swf',
				'copy_confirmation' => __( 'Copied!', 'woocommerce-stellar-gateway' ),
			) );

			wp_enqueue_style( 'wc_stellar', $this->plugin_url() . '/assets/css/stellar.css' );
		}
	}

	/**
	 * Get transactions for a given Stellar wallet ordered by destination tag.
	 *
	 * Only those transactions with a destination tag will be returned.
	 *
	 * @access public
	 */
	public function get_stellar_transactions( $wallet_address = '', $ledger_min = 0 ) {

		// Fetch Stellar Gateway settings.
		if ( empty( $wallet_address ) && ! empty ( $this->gateway_settings['account_address'] ) ) {
			$wallet_address = $this->gateway_settings['account_address'];
		}

		if ( empty( $wallet_address ) ) {
			return new WP_Error( 'No Stellar Account Address', __( 'No Stellar Account Address has been set.', 'woocommerce-stellar-gateway' ) );
		}

		// Find latest transactions from the ledger.
		$account_tx = $this->send_to( 'https://live.stellar.org:9002', $this->get_account_tx( $wallet_address, $ledger_min ) );

		if ( is_wp_error( $account_tx ) ) {
			return $account_tx;
		}

		$account_tx = json_decode( $account_tx['body'] );
		$account_tx = $account_tx->result;

		if ( ! isset( $account_tx->status ) ) {
			return new WP_Error( 'Bad Stellar Request', __( 'Received Invalid Response from Stellar', 'woocommerce-stellar-gateway' ) );
		} elseif ( 'success' !== $account_tx->status ) {
			return new WP_Error( 'Bad Stellar Request', sprintf( __( 'Recieved Error Code %s: %s ', 'woocommerce-stellar-gateway' ), $account_tx->error_code, $account_tx->error_message ) );
		}

		// Match transaction with Hash
		$transactions = array();

		foreach ( $account_tx->transactions as $key => $transaction ) {
			if ( isset( $transaction->tx->hash ) && isset( $transaction->tx->DestinationTag ) && $transaction->tx->DestinationTag > 0 ) {
				$transactions[ $transaction->tx->DestinationTag ] = $transaction->tx;
			}
		}

		return $transactions;
	}

	/**
	 * Check if a given order has a match Stellar transaction.
	 *
	 * You pass in a set of transactions (in the form returned by @see $this->get_stellar_transactions())
	 * or let the method request a complete set of transactions up to the default limit to check against.
	 *
	 * @param int $order_id The ID of an order in the store
	 * @param array $transactions An array of transactions in the form returned by @see $this->get_stellar_transactions()
	 * @access public
	 */
	public function validate_stellar_payment( $order_id, $transactions = array() ) {

		// Match transaction with Hash
		if ( empty( $transactions ) ) {
			$transactions = $this->get_stellar_transactions();
		}

		$return = false;

		// If transaction exists.
		if ( isset( $transactions[ $order_id ] ) ) {

			// Check if full amount was received for this order.
			$order        = wc_get_order( $order_id );
			$order_amount = round( $order->get_total(), 2 );

			if ( 'STR' === $order->get_order_currency() ) {
				$order_amount *= 1000000;
			}

			if ( is_object( $transactions[ $order_id ]->Amount ) && $transactions[ $order_id ]->Amount->value == $order_amount && $transactions[ $order_id ]->Amount->currency == $order->get_order_currency() ) {
				$order->payment_complete( $transactions[ $order_id ]->hash );
				$return = true;
			} elseif ( ! is_object( $transactions[ $order_id ]->Amount ) && $transactions[ $order_id ]->Amount == $order_amount ) {
				$order->payment_complete( $transactions[ $order_id ]->hash );
				$return = true;
			}
		}

		return $return;
	}

	/**
	 * This sends data to Stellar.
	 *
	 * @access public
	 */
	public function send_to( $url, $request ) {
		$headers = array( 'Accept: application/json','Content-Type: application/json' );

		$response = wp_remote_post( $url, array(
				'method'  => 'POST',
				'headers' => $headers,
				'body'    => $request,
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response ) ) {
			return new WP_Error( 'Empty Response', __( 'Empty response from Stellar API request', 'woocommerce-stellar-gateway' ) );
		}
		return $response;
	}

	/**
	 * Build the JSON required to query the Stellar ledger for transactions on a given account
	 *
	 * Uses the `account_tx` API endpoint: https://www.stellar.org/api/#api-account_tx
	 *
	 * @access public
	 */
	public function get_account_tx( $account, $min_ledger = 0, $max_ledger = -1, $limit = -1 ) {

		if ( $min_ledger < 0 ) {
			$min_ledger = 0;
		}

		$data = '
		{
			"method": "account_tx",
			"params": [
			{
				"account": "' . $account. '",
				"ledger_index_min": ' . $min_ledger . '
			}
			]
		}';

		return $data;
	}

	/**
	 * If called, then the order is checked
	 * against the transactions to confirm payment.
	 *
	 * @access public
	 */
	public function confirm_stellar_payment( $order_id ) {

		$result = $this->validate_stellar_payment( absint( $_POST['order_id'] ) );

		if ( true === $result ) {

			$response = json_encode( array( 'result' => 'success' ) );

		} else {

			if ( is_wp_error( $result ) ) {
				$error_message = $result->get_error_message();
			} else {
				$error_message= '';
			}

			$response = json_encode( array( 'result' => 'failure', 'error_message' => $error_message ) );

		}

		echo apply_filters( 'woocommerce_stellar_confirm_payment_response', $response );

		die();
	}

	/**
	 * Gets the extra details you set here to be
	 * displayed on the 'Thank you' page.
	 *
	 * @access private
	 */
	public function stellar_instructions( $order_id, $reciept = '' ) {
		$order            = wc_get_order( $order_id );
		$template_params  = array(
			'order'               => $order,
			'stellar_payment_url' => $this->get_stellar_payment_url( $order_id ),
			'account_address'     => $this->gateway_settings['account_address'],
		);
		wc_get_template( 'checkout/stellar-instructions.php', $template_params, '', $this->template_path() );
		if ( $order->has_status( 'pending' ) ) {
			wc_get_template( 'checkout/stellar-registration.php', array(), '', $this->template_path() );
		}
	}

	/**
	 * Build a URL for making a payment for an order (with destination tag)
	 * via stellar.org.
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_stellar_payment_url( $order_id ) {

		$order = new WC_Order( $order_id );

		$params = array();

		// Destination AccountID
		$params['dest']     = $this->gateway_settings['account_address'];
		$params['amount']   = $order->get_total(); // Will need to be calculated into microstellars if the currency is 'STR'
		$params['currency'] = $order->get_order_currency(); // USD, EUR, STR etc.

		// Destination tag.
		// This value must be encoded in the payment for the user to be credited.
		$params['dt'] = $order->id;

		// Stellar url.
		$parts = array();

		foreach ( $params as $key => $value ) {
			$parts[] = sprintf( '%s=%s', $key, $value );
		}

		$query = implode( '&', $parts );

		return 'https://launch.stellar.org/#/?action=send&' . $query;
	}

	/**
	 * Return recent Stellar orders pending payment.
	 *
	 * @access public
	 * @return string
	 */
	protected function get_pending_orders() {
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'shop_order',
			'post_status' => 'wc-pending',
			'date_query'  => array( array( 'after' => '-7 days' ) ),
			'meta_key'     => '_payment_method',
			'meta_value'   => 'stellar',
		);

		$query = new WP_Query( $query_args );
		$orders = array();

		return $query->posts;
	}

	/**
	 * Check for payments on pending orders
	 *
	 * @access public
	 */
	public function check_pending_payments() {

		// Fetch recent orders.
		$order_ids    = $this->get_pending_orders();
		$transactions = $this->get_stellar_transactions( '', get_option( 'woocommerce_stellar_ledger', 0 ) );

		if ( ! is_wp_error( $transactions ) ) {
			foreach ( $order_ids as $order_id ) {
				$this->validate_stellar_payment( $order_id, $transactions );
			}
		}
	}

	/**
	 * Add a new schedule to WP Cron
	 *
	 * @access public
	 */
	public function add_cron_schedule( $schedules ) {

		$schedules['every_ten_minutes'] = array(
			'interval' => 60 * 10,
			'display'  => __( 'Every 10 Minutes', 'woocommerce-stellar-gateway' ),
		);

		return $schedules;
	}

	/**
	 * WooCommerce Fallback Notice.
	 *
	 * @access public
	 * @return string
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="error woocommerce-message wc-connect"><p>' . sprintf( __( 'Sorry, <strong>WooCommerce %s</strong> requires WooCommerce to be installed and activated first. Please install <a href="%s">WooCommerce</a> first.', 'woocommerce-stellar-gateway' ), $this->name, admin_url( 'plugin-install.php?tab=search&type=term&s=WooCommerce' ) ) . '</p></div>';
	}

	/**
	 * WooCommerce Stellar Gateway Upgrade Notice.
	 *
	 * @access public
	 * @return string
	 */
	public function upgrade_notice() {
		echo '<div class="updated woocommerce-message wc-connect"><p>' . sprintf( __( 'WooCommerce %s depends on version 2.2 and up of WooCommerce for this gateway to work! Please upgrade before activating.', 'woocommerce-stellar-gateway' ), $this->name ) . '</p></div>';
	}

	/** Helper functions ******************************************************/

	/**
	 * Get the plugin url.
	 *
	 * @access public
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @access public
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @access public
	 * @return string
	 */
	public function template_path() {
		return trailingslashit( plugin_dir_path( __FILE__ ) ) . 'templates/';
	}

} // end if class
add_action( 'plugins_loaded', array( 'WC_Stellar', 'get_instance' ), 0 );

} // end if class exists.

/**
 * Returns the main instance of WC_Stellar to prevent the need to use globals.
 *
 * @return WooCommerce Stellar Gateway
 */
function WC_Stellar() {
	return WC_Stellar::get_instance();
}