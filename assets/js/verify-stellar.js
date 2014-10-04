// Ajax confirm payment
jQuery(document).ready(function($){
	$(document).on( 'click', '.stellar-confirm', function( event ) {
		event.preventDefault();

		$('.stellar-confirm').attr('disabled','disabled');
		$('.stellar-transaction-error-message').empty();
		$('.stellar-payment-instructions, .stellar-transaction:not(".pending")').slideUp();
		$('.stellar-transaction.pending').slideDown();
		$('.stellar-status').show();

		$('.stellar-status').show();

		var timer = setInterval(function() {
			$('.stellar-countdown').text(sec--);
			if(sec == -1) {
				$('.stellar-countdown').fadeOut('fast');
				clearInterval(timer);
			}
		}, 1000);
		var ajaxCall = $.ajax({
			type: "POST",
			url: wc_stellar_js.ajax_url,
			data: {
				action: 'confirm_stellar_payment',
				order_id: wc_stellar_js.order_id
			},
			tryCount : 0,
			retryLimit : retries,
			success: function (response) {
				response = $.parseJSON( response );
				$('.stellar-transaction.pending').slideUp();
				if( response.result === 'success' ) {
					$('.stellar-transaction.success').show();
					$('.stellar-pay-button, .stellar-payment-instructions, .stellar-confirm, .stellar-registration').slideUp();
					return;
				} else {
					$('.stellar-confirm').removeAttr('disabled');
					if (response.error_message.length > 0) {
						$('.stellar-transaction-error-message').text(response.error_message);
					}
					$('.stellar-transaction.failed, .stellar-payment-instructions').slideDown();
					return;
				}
			}
		});
	});
});
