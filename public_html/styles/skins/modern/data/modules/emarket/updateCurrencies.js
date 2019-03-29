(function($, window) {
	'use strict';

	$(function() {
		$('#updateCurrenciesButton').on('click', updateCurrenciesClickHandler);
	});

	/**
	 * Обработчик нажатия на кнопку "Обновить курс валют"
	 * @param {jQuery.Event} e
	 */
	var updateCurrenciesClickHandler = function(e) {
		e.preventDefault();
		var csrf = window.csrfProtection.token;

		$.ajax({
			type: 'POST',
			url: window.pre_lang + '/admin/emarket/updateCurrencies.json?csrf=' + csrf,
			dataType: 'json',

			success: function() {
				window.location.reload(true);
			},

			error: function() {
				$.jGrowl(getLabel('js-server_error'));
			}
		});
	};

}(jQuery, window));
