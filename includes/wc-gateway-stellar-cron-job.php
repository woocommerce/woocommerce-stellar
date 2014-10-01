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
	$stellar = new Stellar();
	$orders = get_recent_stellar_orders();
	// Fetch Stellar Gateway settings.
	$stellar_settings = get_option( 'woocommerce_stellar_settings' );

	$wallet_address = $stellar_settings['account_address'];

	// Find initial ledger_index_min
	$ledger_min = get_option( 'woocommerce_stellar_ledger', -1 );

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
	if( !isset( $account_tx['transactions'] ) || empty( $account_tx['transactions'] ) ) {
		update_option( 'woocommerce_stellar_ledger', $ledger_max );
		return true;
	}

	// Match transactions with Hash.
	$txs = array();
	foreach( $account_tx['transactions'] as $key => $tx ) {
		if( isset( $tx['tx']['hash'] ) && $tx['tx']['hash'] > 0 ) {
			$dt = $tx['tx']['hash'];
			$txs[$dt] = $tx['tx'];
		}
	}

	// Update order statuses.
	foreach( $orders as $id ) {
		if( isset( $txs[$id] ) ) {
			$order = new WC_Order( $id );
			$transactionId = $txs[$id]['hash'];

			// Check if full amount was received for this order.
			if( is_array($txs[$id]['Amount'] ) && $txs[$id]['Amount']['value'] == round( $order->get_total(), 2 ) && $txs[$id]['Amount']['currency'] == $order->get_order_currency() ) {
				$order->payment_complete();
				//$order->add_order_note($note);
			} else {
				// Manual review required.
				$order->update_status('on-hold');
			}
		}
	}

	// Update the ledger.
	update_option("woocommerce_stellar_ledger", $ledger_max);
}

if( ! wp_next_scheduled( 'woocommerce_stellar_cron_job' ) ) {
	wp_schedule_event( time(), 'hourly', 'woocommerce_steller_cron_job' );
}
