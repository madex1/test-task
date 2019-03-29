(function($) {
	'use strict';

	$(function() {
		var $captchaList = $('select');

		/**
		 * Выводит настройки, актуальные для выбранной в данный момент капчи.
		 * Используется конвенция, что настройки для классической капчи начинаются со слова "captcha",
		 * а настройки для Google reCaptcha начинаются со слова "recaptcha".
		 * @param {jQuery} $select
		 */
		var refreshOptions = function($select) {
			var newValue = $select.val();
			var $container = $select.closest('.panel-settings');
			$('.row:has(input[name*="captcha"])', $container).hide();
			$('.row:has(input[name^="' + newValue + '"])', $container).show();
		};

		$captchaList.on('change', function() {
			refreshOptions($(this));
		});

		$captchaList.trigger('change');
	});

}(jQuery));
