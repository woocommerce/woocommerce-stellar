<?php
/**
 * Stellar SDK in PHP
 *
 * @author Sebastien Dumont
 */
class Stellar {

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
}
