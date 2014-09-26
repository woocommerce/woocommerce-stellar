<?php
/*
 * Plugin Name:       WooCommerce Stellar Gateway
 * Plugin URI:        http://prospress.com/
 * Description:       A way to pay in WooCommerce with Stellar currency and network.
 * Version:           1.0.0
 * Author:            Sebastien Dumont
 * Author URI:        http://www.sebastiendumont.com
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

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Required functions
 */
require_once('woo-includes/woo-functions.php');

if( !class_exists( 'WC_Stellar' ) ) {

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
      if( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
        return false;
      }
      else{

        // Check that we have CURL enabled on the site's server.
        if( !function_exists( 'curl_init' ) ) {
          deactivate_plugins( plugin_basename( __FILE__ ) );
          add_action( 'admin_notices', array( $this, 'woocommerce_stellar_check_curl' ) );
          return false;
        }

        // Check we have the minimum version of WooCommerce required before loading the gateway.
        if( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.2', '>=' ) ) {
          if( class_exists( 'WC_Payment_Gateway' ) ) {

            $this->includes();

            add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
            add_filter( 'woocommerce_currencies', array( $this, 'add_currency' ) );
            add_filter( 'woocommerce_currency_symbol', array( $this, 'add_currency_symbol' ), 10, 2 );
            add_action( 'init', array( $this, 'add_order_status' ) );
            add_action( 'init', array( $this, 'confirm_stellar_payment' ), 11);
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
          }
        }
        else {
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
       if( current_user_can( 'manage_woocommerce' ) ) {
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
       if( plugin_basename( __FILE__ ) !== $file ) {
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

      if( file_exists( $mofile_global ) ) {
        // Look in global /wp-content/languages/woocommerce-stellar-gateway/ folder
        load_textdomain( $this->text_domain, $mofile_global );
      }
      else if( file_exists( $mofile_local ) ) {
        // Look in local /wp-content/plugins/woocommerce-stellar-gateway/languages/ folder
        load_textdomain( $this->text_domain, $mofile_local );
      }
      else {
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
      include_once( 'includes/lib/stellar-sdk.php' ); // Load Stellar SDK.
      include_once( 'includes/admin/class-wc-stellar-admin-assets.php' ); // Style and script assets.
      include_once( 'includes/wc-gateway-stellar-cron-job.php' ); // Cron Job.
      include_once( 'includes/class-wc-gateway-' . str_replace( '_', '-', $this->gateway_slug ) . '.php' ); // Payment Gateway.
    }

    /**
     * This filters the gateway to only supported countries.
     *
     * @access public
     */
    public function gateway_country_base() {
      return apply_filters( 'woocommerce_gateway_country_base', '*' );
    }

    /**
     * Add the gateway.
     *
     * @access public
     * @param  array $methods WooCommerce payment methods.
     * @return array WooCommerce Stellar gateway.
     */
    public function add_gateway( $methods ) {
      // This checks if the gateway is supported for your country.
      if( '*' == $this->gateway_country_base() || in_array( WC()->countries->get_base_country(), $this->gateway_country_base() ) ) {
        $methods[] = 'WC_Gateway_' . str_replace( ' ', '_', $this->name );
      }

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
      switch( $currency ) {
        case 'STR':
          $currency_symbol = 'STR';
        break;
      }
      return $currency_symbol;
    }

    /**
     * This adds a new order status specifically for this gateway.
     *
     * @access public
     */
    public function add_order_status() {
      register_post_status( 'wc-verify-stellar', array(
        'label'                     => _x( 'Verify Stellar Transaction', 'Order status', 'woocommerce-stellar-gateway' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Needs Verifying with Stellar <span class="count">(%s)</span>', 'Needs Verifying with Stellar <span class="count">(%s)</span>', 'woocommerce-stellar-gateway' )
      ) );

      wp_insert_term( 
        __( 'Verify Stellar Transaction', 'woocommerce-stellar-gateway' ), 
        'shop_order_status'
      );

      if( is_admin() ) {
        add_filter( 'wc_order_statuses', array( $this, 'filter_order_statuses' ) );
        add_filter( 'woocommerce_admin_order_actions', array( $this, 'filter_wc_admin_order_statuses' ), 2 );
      }
    }

    /**
     * This filters the order statuses.
     *
     * @access public
     */
    public function filter_order_statuses( $order_statuses ) {
      $new_order_status = array(
        'wc-verify-stellar' => _x( 'Verify Stellar Transaction', 'Order status', 'woocommerce-stellar-gateway' )
      );
      return array_merge( $order_statuses, $new_order_status );
    }

    /**
     * This filters the action buttons on the order table 
     * and allows the shop owner to verify a Stellar transaction.
     *
     * @access  public
     * @returns array
     */
    public function filter_wc_admin_order_statuses( $actions, $the_order ) {
      global $woocommerce, $post, $the_order;

      if( $the_order->has_status( array( 'pending', 'on-hold', 'processing', 'verify-stellar' ) ) ) {
        if( $this->is_payment_method_stellar( $the_order->id ) ) {
          $actions['verify-stellar'] = array(
            'url'    => admin_url( 'post.php?post=' . $post->ID . '&action=verify_stellar' ),
            'name'   => __( 'Verify Transaction', 'woocommerce-stellar-gateway' ),
            'action' => "verify-steller"
          );
        }
      }

      return $actions;
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

      if( $payment_method == 'stellar' ) return true;
      return false;
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
      global $order_id;

      if( is_order_received_page() || $this->is_view_order_page() ) {

        // Fetch Stellar Gateway settings.
        $stellar_settings = get_option( 'woocommerce_stellar_settings' );

        wp_register_script( 'wc_stellar_script', $this->plugin_url() . '/assets/js/verify-stellar.js', array('jquery'), $this->version );
        wp_enqueue_script( 'wc_stellar_script' );

        wp_localize_script( 'wc_stellar_script', 'wc_stellar_js', array(
          'site_url'    => get_site_url(),
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
      $stellar = new Stellar();

      // Fetch Stellar Gateway settings.
      $stellar_settings = get_option( 'woocommerce_stellar_settings' );

      $wallet_address = $stellar_settings['account_address'];

      // Find initial ledger_index_min
      $ledger_min = get_option("woocommerce_stellar_ledger", -1);

      // -10 for deliberate overlap to avoid (possible?) gaps
      if( $ledger_min > 10 ) {
        $ledger_min -= 10;
      }

      // Find latest transactions from the ledger.
      $account_tx = $stellar->get_account_tx( $wallet_address, $ledger_min );
      $account_tx = $stellar->send_to( 'https://live.stellar.org:9002', $account_tx );

      if( !isset( $account_tx['status'] ) || $account_tx['status'] != 'success' ) {
        return false;
      }

      // Find transactions.
      $ledger_max = $account_tx['ledger_index_max'];

      // Match transaction with Hash.
      $txs = array();
      foreach( $account_tx['transactions'] as $key => $tx ) {
        if( isset( $tx['tx']['hash'] ) && $tx['tx']['hash'] > 0 ) {
          $dt = $tx['tx']['hash'];
          $txs[$dt] = $tx['tx'];
        }
      }

      // If transaction exists.
      if( isset( $txs[$order_id] ) ) {
        $transactionId = $txs[$order_id]['hash'];

        // Check if full amount was received for this order.
        if( is_array($txs[$order_id]['Amount'] ) && $txs[$order_id]['Amount']['value'] == round( $order->get_total(), 2 ) && $txs[$order_id]['Amount']['currency'] == $order->get_order_currency() ) {
          $order->payment_complete();

          $return = true;
        }

        // Manual review required.
        else {
          $order->update_status('on-hold');
          $return = false;
        }

      }

      return $return;
    }

    /**
     * If called, then the order is checked 
     * against the transactions to confirm payment.
     *
     * @access public
     */
    public function confirm_stellar_payment( $order_id ) {
      if( isset( $_GET['confirm_stellar_payment'] ) ) {
        $this->validate_stellar_payment( $order_id ); die();
      }
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

?>