(function ($) {

	SS6 = window.SS6 || {};
	SS6.orderPreview = SS6.orderPreview || {};

	SS6.orderPreview.init = function ($container) {
		$container
			.filterAllNodes('.js-order-transport-input, .js-order-payment-input')
				.change(SS6.orderPreview.loadOrderPreview);
	};

	SS6.orderPreview.loadOrderPreview = function () {
		var $orderPreview = $('#js-order-preview');
		var $checkedTransport = $('.js-order-transport-input:checked');
		var $checkedPayment = $('.js-order-payment-input:checked');
		var data = {};

		if ($checkedTransport.length > 0) {
			data['transportId'] = $checkedTransport.data('id');
		}
		if ($checkedPayment.length > 0) {
			data['paymentId'] = $checkedPayment.data('id');
		}

		SS6.ajax({
			loaderElement: '#js-order-preview',
			url: $orderPreview.data('url'),
			type: 'get',
			data: data,
			success: function(data) {
				$orderPreview.html(data);
			}
		});
	};

	SS6.register.registerCallback(SS6.orderPreview.init);

})(jQuery);