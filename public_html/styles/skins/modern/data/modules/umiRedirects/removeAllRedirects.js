(function($, window) {
	'use strict';

	$(function() {
		$('#removeAllRedirectsButton').on('click', removeAllRedirectsClickHandler);
	});

	/**
	 * Обработчик нажатия на кнопку "Удалить все редиректы"
	 * @param {jQuery.Event} e
	 */
	var removeAllRedirectsClickHandler = function(e) {
		e.preventDefault();
		var csrf = window.csrfProtection.token;

		openDialog(getLabel('js-message-remove-all-redirects'), getLabel('js-label-remove-all-redirects'), {
			cancelButton: true,
			confirmText: getLabel('js-label-yes'),
			cancelText: getLabel('js-label-no'),

			confirmCallback: function(popupName) {
				$.ajax({
					type: 'POST',
					url: window.pre_lang + '/admin/umiRedirects/removeAllRedirects.json?csrf=' + csrf,
					dataType: 'json',

					success: function() {
						window.location.reload(true);
					},

					error: function(response) {
						closeDialog(popupName);
						var message = getLabel('js-server_error');

						if (response.status === 403 && response.responseJSON && response.responseJSON.data && response.responseJSON.data.error) {
							message = response.responseJSON.data.error;
						}

						$.jGrowl(message);
					}
				});
			},

			cancelCallback: function(popupName) {
				closeDialog(popupName);
			}
		});
	};

}(jQuery, window));
