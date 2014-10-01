<?php
/**
 * Stellar SDK in PHP
 *
 * @author Sebastien Dumont
 */
class Stellar {

	/**
	 * http build query for RFC 3986
	 * needed for PHP < 5.4 compatibility
	 */
	public function build_query(array $params, $sep = '&') {
		$parts = array();

		foreach( $params as $key => $value ) {
			$parts[] = sprintf( '%s=%s', $key, $value );
		}

		return implode($sep, $parts);
	}

	public function get_stellar_url( $order_id ) {
		global $woocommerce;

		$order = new WC_Order( $order_id );

		// Fetch Stellar Gateway settings.
		$stellar_settings = get_option( 'woocommerce_stellar_settings' );

		$urlFields = array();

		// Destination AccountID
		$urlFields['dest'] = $stellar_settings['account_address'];

		// Amount to send.
		$urlFields['amount'] = $order->get_total(); // Will need to be calculated into microstellars if the currency is 'STR'

		// Currency tag.
		$urlFields['currency'] = $order->get_order_currency(); // USD, EUR, STR etc.

		// Destination tag.
		// This value must be encoded in the payment for the user to be credited.
		$urlFields['dt'] = $order->id;

		// Stellar url.
		$url   = "https://launch.stellar.org/#/?action=send";
		$query = $this->build_query( $urlFields );

		return $url . '&' . $query;
	}

	/**
	 * This sends data to Stellar.
	 *
	 * @access public
	 */
	public function send_to( $url, $request ) {
		$headers = array( 'Accept: application/json','Content-Type: application/json' );

		$ch = curl_init( $url );

		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $request );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );

		if( curl_errno( $ch ) ) {
			throw new Exception('JSON-RPC Error: ' . curl_error( $ch ) );
		}

		$result = curl_exec( $ch );

		if( empty( $result ) ) {
			throw new Exception('JSON-RPC Error: no data. Please check your Stellar JSON-RPC settings.');
		}

		curl_close( $ch );

		return $result;
	}

	/**
	 * This gets a list of transactions that affected the shop owners account.
	 *
	 * @access public
	 */
	public function get_account_tx( $account, $min_ledger = 0, $max_ledger = -1, $limit = -1 ) {
		$data = array(
			'method' => 'account_tx',
			'params' => array(
				'account'          => $account,
				'ledger_index_min' => $min_ledger,
				'ledger_index_max' => $max_ledger,
				'limit'            => $limit
			)
		);

		return $data;
	}

	/**
	 * This returns the last X amount of transactions from the given index.
	 * Default: Returns last 20 transactions.
	 *
	 * @access public
	 */
	public function get_tx_history( $limit = 20 ) {
		$data = array(
			'method' => 'tx_history',
			'params' => array(
				'start' => $limit
			)
		);

		return $data;
	}

	/**
	 * This returns a single transaction details.
	 *
	 * @access public
	 */
	public function get_tx( $tx_hash = '' ) {
		$data = array(
			'method' => 'tx',
			'params' => array(
				'transaction' => $tx_hash
			)
		);

		return $data;
	}

} // end class.
