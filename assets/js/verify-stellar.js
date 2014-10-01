// Ajax confirm payment
jQuery(document).ready(function($){
	$(document).on( 'click', '.stellar-confirm', function( event ) {
		event.preventDefault();

		var time_window = wc_stellar_js.time_window;
		var retries     = wc_stellar_js.retries;
		var sec         = time_window;

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
				if( response.result === 'success' ) {
					$('.stellar-transaction.success').show();
					return;
				} else {
					this.tryCount++;

					if( this.tryCount <= this.retryLimit ) {
						$.ajax(this);
						$('.stellar-retries').show().text(this.tryCount);
					}

					if( this.tryCount > this.retryLimit ) {
						$('.stellar-transaction.failed').show();
					}

					$(timer);
					setInterval(ajaxCall, 1000 * time_window); //call itself

					return;
				}
			}
		});

	});
});