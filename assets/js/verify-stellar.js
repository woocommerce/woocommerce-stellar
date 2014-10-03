// Ajax confirm payment
jQuery(document).ready(function($){
	$(document).on( 'click', '.stellar-confirm', function( event ) {
		event.preventDefault();

		$('.stellar-confirm').attr('disabled','disabled');
		$('.stellar-payment-instructions, .stellar-transaction').slideUp();
		$('.stellar-status').slideDown();

		var ajaxCall = $.ajax({
			type: "POST",
			url: wc_stellar_js.ajax_url,
			data: {
				action: 'confirm_stellar_payment',
				order_id: wc_stellar_js.order_id
			},
			success: function (response) {
				response = $.parseJSON( response );
				if( response.result === 'success' ) {
					$('.stellar-transaction.success').show();
					$('.stellar-transaction-pending').slideUp();
					$('.stellar-pay-button, .stellar-payment-instructions').hide();
					$('.stellar-confirm').hide();
					return;
				} else {
					$('.stellar-confirm').removeAttr('disabled');
					$('.stellar-transaction.failed').show();
					$('.stellar-payment-instructions').slideDown();
					return;
				}
			}
		});
	});
});
