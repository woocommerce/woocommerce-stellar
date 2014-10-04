<?php

function get_recent_stellar_orders() {
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

function woocommerce_stellar_cron_job() {

	// Fetch recent orders.
	$order_ids = get_recent_stellar_orders();
	// Fetch Stellar Gateway settings.
	$stellar_settings = get_option( 'woocommerce_stellar_settings' );

	$wallet_address = $stellar_settings['account_address'];

	// Find initial ledger_index_min
	$ledger_min = get_option( 'woocommerce_stellar_ledger', -1 );

	foreach ( $order_ids as $order_id ) {
		WC_Stellar()->validate_stellar_payment( $order_id );
	}

	// Update the ledger.
	update_option( 'woocommerce_stellar_ledger', $ledger_max );
}
add_action( 'woocommerce_stellar_cron_job', 'woocommerce_stellar_cron_job' );

if( ! wp_next_scheduled( 'woocommerce_stellar_cron_job' ) ) {
	wp_schedule_event( time(), 'hourly', 'woocommerce_stellar_cron_job' );
}
