(function() {
	$(document).ready(function() {
		addIframe();
		onClick();

		/** Добавляет iframe c формой авторизации */
		function addIframe() {
			$('head').append('<iframe id="iframe" src="/errors/window.html"></iframe>');
		}

		/** Отображает окно с входом при клике */
		function onClick() {
			$('html').on('click', function() {
				if (!$('.modal-form').html()) {
					var template = _.template($('#iframe').contents().find('#modal-window').html());
					var content = template({
						login: getLabel('js-stub-login'),
						password: getLabel('js-stub-password'),
						enter: getLabel('js-stub-enter'),
					});

					$('#iframe').remove();
					$(content).prependTo('body');

					onFormConfirm();
				}
			});
		}

		/** Обработчик подтверждения формы авторизации */
		function onFormConfirm() {
			$('#confirm').on('click', function() {
				var login = $('#umi-login').val();
				var password = $('#umi-password').val();

				$.ajax({
					type: 'POST',
					url: '/udata/umiStub/addUserToWhiteList/',
					data: {
						login: login,
						password: password
					},
					success: function(data) {
						var errors = data.getElementsByTagName('error');
						if (errors.length) {
							var errorText = errors[0].firstChild.nodeValue;
							$('#error').css('color', 'red').html(errorText).show();
						} else {
							window.location.href = window.location.href
						}
					},
				});
			});
		}
	});
})();

