jQuery(document).ready(function($){

	$(document).on( 'click', '.stellar-set-destination-tag-flag', function( event ) {
		var master_key = $('#stellar_secret_key').val();
		$('#stellar_secret_key').val("");
		// ensure the error message is hidden
		$('.stellar_set_account_flag_error').hide();

		$.ajax({
			type: "POST",
			url: 'https://live.stellar.org:9002',
			dataType : "text",
			data: '{ "method": "submit", "params": [{ "secret": "' + master_key + '", "tx_json": { "TransactionType": "AccountSet", "Account": "' + wc_stellar_admin_js.account_id + '", "SetFlag": "1" } } ]}',
			success: function( response ) {
				response = $.parseJSON( response );
				// redirect to success URL
				if( 'success' == response.result.status && 'tesSUCCESS' == response.result.engine_result ) {
					window.location.href = wc_stellar_admin_js.success_url;
				} else {
					// show the error message from stellar within the notice
					$('.stellar_set_account_flag_error').html( $('.stellar_set_account_flag_error').html() + response.result.error_message );
					$('.stellar_set_account_flag_error').show();
				}
			}
		});
	});
});
