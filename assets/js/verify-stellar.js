// Ajax confirm payment
jQuery(document).ready(function($){

	// Setup copy button
	var stellarClipboardClient = new ZeroClipboard($('.stellar-copy-button'), {
	  moviePath: wc_stellar_js.SWFPath
	} );

	stellarClipboardClient.on('ready',function(readyEvent) {
		$('.stellar-copy-button').show();
		stellarClipboardClient.on('copy', function (event) {
			event.clipboardData.setData('text/plain', $('code.stellar-address').text());
		});
		stellarClipboardClient.on('aftercopy', function(event) {
			$('.stellar-tooltip').addClass('copied').text(wc_stellar_js.copy_confirmation);
		});
	} );

	// Handle transaction confirmation button
	$(document).on( 'click', '.stellar-confirm', function( event ) {
		event.preventDefault();

		$('.stellar-confirm').attr('disabled','disabled');
		$('.stellar-transaction-error-message').empty();
		$('.stellar-payment-instructions, .stellar-transaction:not(".pending")').slideUp();
		$('.stellar-transaction.pending').slideDown();
		$('.stellar-status').show();

		var ajaxCall = $.ajax({
			type: "POST",
			url: wc_stellar_js.ajax_url,
			data: {
				action: 'confirm_stellar_payment',
				order_id: wc_stellar_js.order_id
			},
			success: function (response) {
				response = $.parseJSON( response );
				$('.stellar-transaction.pending').slideUp();
				if( response.result === 'success' ) {
					$('.stellar-transaction.success').show();
					$('.stellar-pay-button, .stellar-payment-instructions, .stellar-confirm, .stellar-registration, .stellar-order-status-pending').slideUp();
					$('body').trigger('wc_stellar_payment_confirmed');
					return;
				} else {
					$('.stellar-confirm').removeAttr('disabled');
					if (response.error_message.length > 0) {
						$('.stellar-transaction-error-message').text(response.error_message);
					}
					$('.stellar-transaction.failed, .stellar-payment-instructions').slideDown();
					$('body').trigger('wc_stellar_payment_confirmation_failed');
					return;
				}
			}
		});
	});
});
