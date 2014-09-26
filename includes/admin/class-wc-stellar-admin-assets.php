<?php
/**
 * Load assets.
 *
 * @author   Sebastien Dumont
 * @category Admin
 * @package  WooCommerce Stellar Gateway/Admin
 * @version  1.0.0
 */

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists( 'WC_Stellar_Admin_Assets' ) ) {

  /**
   * WC_Stellar_Admin_Assets Class
   */
  class WC_Stellar_Admin_Assets {

    /**
     * Hook in tabs.
     */
    public function __construct() {
      add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
      //add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
    }

    /**
     * Enqueue styles
     */
    public function admin_styles() {
      $screen = get_current_screen();

      if( in_array( $screen->id, array( 'edit-shop_order' ) ) ) {
        // Admin styles for WooCommerce Orders only
        wp_enqueue_style( 'woocommerce_stellar_admin_shop_order_styles', WC_Stellar()->plugin_url() . '/assets/css/admin/shop_order.css', array(), WC_Stellar()->version );
      }

    }

  }

}

return new WC_Stellar_Admin_Assets();

?>