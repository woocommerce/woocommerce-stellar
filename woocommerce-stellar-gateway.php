<?php
/*
 * Plugin Name:       WooCommerce Stellar Gateway
 * Plugin URI:        http://wordpress.org/extend/plugins/woocommerce-stellar/
 * Description:       A way to pay in WooCommerce with Stellar currency and protocol.
 * Version:           1.0.0
 * Author:            Prospress Inc
 * Author URI:        http://www.prospress.com
 * Requires at least: 4.0
 * Tested up to:      4.0
 * Text Domain:       woocommerce-stellar-gateway
 * Domain Path:       languages
 * Network:           false
 * GitHub Plugin URI: https://github.com/Prospress/woocommerce-stellar
 *
 * WooCommerce Stellar Gateway is distributed under the terms of the
 * GNU General Public License as published by the Free Software Foundation,
 * either version 2 of the License, or any later version.
 *
 * WooCommerce Stellar Gateway is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WooCommerce Stellar Gateway. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package  WooCommerce Stellar
 * @author   Sebastien Dumont
 * @category Core
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Required functions
 */
require_once('woo-includes/woo-functions.php');

if ( ! class_exists( 'WC_Stellar' ) ) {

/**
 * WooCommerce Stellar main class.
 *
 * @class   Stellar
 * @version 1.0.0
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
	 * Text Domain
	 *
	 * @access public
	 * @var    string
	 */
	public $text_domain = 'woocommerce-stellar-gateway';

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
	public $version = '1.0.0';

	/**
	 * The Gateway documentation URL.
	 *
	 * @access public
	 * @var    string
	 */
	public $doc_url = "https://github.com/Prospress/woocommerce-stellar/wiki/";

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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
		// Hooks.
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Is WooCommerce activated?
		if( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return false;
		} else {

			// Check that we have CURL enabled on the site's server.
			if ( ! function_exists( 'curl_init' ) ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				add_action( 'admin_notices', array( $this, 'woocommerce_stellar_check_curl' ) );
				return false;
			}

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
				}
			} else {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				add_action( 'admin_notices', array( $this, 'upgrade_notice' ) );
				return false;
			}
		}
	}

	/**
	 * Plugin action links.
	 *
	 * @access public
	 * @param  mixed $links
	 * @return void
	 */
	public function action_links( $links ) {
		if(  current_user_can( 'manage_woocommerce' ) ) {
			$plugin_links = array(
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_' . $this->gateway_slug ) . '">' . __( 'Payment Settings', 'woocommerce-stellar-gateway' ) . '</a>',
			);
			return array_merge( $plugin_links, $links );
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
		$locale = apply_filters( 'plugin_locale',  get_locale(), $this->text_domain );
		$mofile = sprintf( '%1$s-%2$s.mo', $this->text_domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/' . $this->text_domain . '/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/woocommerce-stellar-gateway/ folder
			load_textdomain( $this->text_domain, $mofile_global );
		} else if( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/woocommerce-stellar-gateway/languages/ folder
			load_textdomain( $this->text_domain, $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( $this->text_domain, false, $lang_dir );
		}
	}

	/**
	 * Include files.
	 *
	 * @access private
	 * @return void
	 */
	private function includes() {
		include_once( 'includes/admin/class-wc-stellar-admin-assets.php' ); // Style and script assets.
		include_once( 'includes/wc-gateway-stellar-cron-job.php' ); // Cron Job.
		include_once( 'includes/class-wc-gateway-' . str_replace( '_', '-', $this->gateway_slug ) . '.php' ); // Payment Gateway.
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
		if( 'STR' === $currency ) {
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

			// Fetch Stellar Gateway settings.
			$stellar_settings = get_option( 'woocommerce_stellar_settings' );

			wp_register_script( 'wc_stellar_script', $this->plugin_url() . '/assets/js/verify-stellar.js', array('jquery'), $this->version );
			wp_enqueue_script( 'wc_stellar_script' );

			wp_localize_script( 'wc_stellar_script', 'wc_stellar_js', array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'time_window' => $stellar_settings['expiration'],
				'retries'     => $stellar_settings['retries'],
				'order_id'    => $order_id
			) );
		}
	}

	/**
	 * Validate the Steller transaction.
	 *
	 * @access public
	 */
	public function validate_stellar_payment( $order_id ) {
		// Fetch Stellar Gateway settings.
		$stellar_settings = get_option( 'woocommerce_stellar_settings' );

		$wallet_address = $stellar_settings['account_address'];

		// Find initial ledger_index_min
		$ledger_min = get_option( 'woocommerce_stellar_ledger', -1 );

		// -10 for deliberate overlap to avoid (possible?) gaps
		if ( $ledger_min > 10 ) {
			$ledger_min -= 10;
		}

		if ( $ledger_min < 0 ) {
			$ledger_min = 0;
		}

		// Find latest transactions from the ledger.
		$account_tx = $this->send_to( 'https://live.stellar.org:9002', $this->get_account_tx( $wallet_address, $ledger_min ) );
		// not doing anything with the wp error messages yet, probably not important
		if( is_wp_error( $account_tx ) ) {
			return false;
		}

		$account_tx = json_decode( $account_tx['body'] );
		$account_tx = $account_tx->result;

		if ( ! isset( $account_tx->status ) || 'success' !== $account_tx->status ) {
			return false;
		}

		// Match transaction with Hash
		$transactions = array();
		foreach ( $account_tx->transactions as $key => $transaction ) {
			if ( isset( $transaction->tx->hash ) && $transaction->tx->hash > 0 &&  isset( $transaction->tx->DestinationTag ) && $transaction->tx->DestinationTag > 0 ) {
				$transactions[ $transaction->tx->DestinationTag ] = $transaction->tx;
			}
		}

		// If transaction exists.
		if ( isset( $transactions[ $order_id ] ) ) {
			$transactionId = $transactions[ $order_id ]->hash;

			// Check if full amount was received for this order.
			$order        = wc_get_order( $order_id );
			$order_amount = round( $order->get_total(), 2 ) * 1000000;

			if ( is_array( $transactions[ $order_id ]->Amount ) && $transactions[ $order_id ]->Amount->value == $order_amount && $transactions[ $order_id ]->Amount->currency == $order->get_order_currency() ) {
				$order->payment_complete( $transactions[ $order_id ]->hash );
				return true;
			} elseif ( ! is_array( $transactions[ $order_id ]->Amount ) && $transactions[ $order_id ]->Amount == $order_amount ) {
				$order->payment_complete( $transactions[ $order_id ]->hash );
				return true;
			} else {
				$order->update_status( 'on-hold' );
				return false;
			}

		}
		return false;
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
			)
		);

		if( $response['response']['code'] == 400 ) {
			return new WP_Error( 'Bad Stellar Request', sprintf( __( 'Recieved Error 400: %s ', 'woocommerce-stellar' ), $response['response']['message'] ) );
		}

		if( empty( $response ) ) {
			return new WP_Error( 'Empty Response', __( 'Empty response from Stellar API request', 'woocommerce-stellar' ) );
		}
		return $response;
	}

	/**
	 * This gets a list of transactions that affected the shop owners account.
	 *
	 * @access public
	 */
	public function get_account_tx( $account, $min_ledger = 0, $max_ledger = -1, $limit = -1 ) {
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
		if ( true == $this->validate_stellar_payment( $_POST['order_id'] ) ) {
			$response = json_encode( array( 'result' => 'success' ) );
		} else {
			$response = json_encode( array( 'result' => 'failure' ) );
		}
		echo apply_filters( 'woocommerce_stellar_confirm_payment_response', $response );
		die();
	}

	/**
	 * WooCommerce Fallback Notice.
	 *
	 * @access public
	 * @return string
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="error woocommerce-message wc-connect"><p>' . sprintf( __( 'Sorry, <strong>WooCommerce %s</strong> requires WooCommerce to be installed and activated first. Please install <a href="%s">WooCommerce</a> first.', $this->text_domain), $this->name, admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce' ) ) . '</p></div>';
	}

	/**
	 * WooCommerce Stellar Gateway Upgrade Notice.
	 *
	 * @access public
	 * @return string
	 */
	public function upgrade_notice() {
		echo '<div class="updated woocommerce-message wc-connect"><p>' . sprintf( __( 'WooCommerce %s depends on version 2.2 and up of WooCommerce for this gateway to work! Please upgrade before activating.', 'payment-gateway-boilerplate' ), $this->name ) . '</p></div>';
	}

	/**
	 * WooCommerce Stellar Curl Check Notice.
	 *
	 * @access public
	 * @return string
	 */
	public function woocommerce_stellar_check_curl() {
		echo '<div class="error woocommerce-message wc-connect"><p>' . __( 'PHP CURL is required for <strong>WooCommerce %s</strong> to work!', 'woocommerce-stellar-gateway' ) . '</p></div>';
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