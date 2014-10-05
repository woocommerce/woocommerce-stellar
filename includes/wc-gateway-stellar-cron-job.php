<?php

function woocommerce_stellar_cron_job() {

	// Fetch recent orders.
	$order_ids = get_recent_stellar_orders();
	// Fetch Stellar Gateway settings.
	$stellar_settings = get_option( 'woocommerce_stellar_settings' );

	$wallet_address = $stellar_settings['account_address'];

	foreach ( $order_ids as $order_id ) {
		WC_Stellar()->validate_stellar_payment( $order_id );
	}

}
add_action( 'woocommerce_stellar_cron_job', 'woocommerce_stellar_cron_job' );

