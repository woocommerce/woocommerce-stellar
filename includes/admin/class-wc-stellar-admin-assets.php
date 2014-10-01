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
      //add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
      //add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
    }
  }

}

return new WC_Stellar_Admin_Assets();
