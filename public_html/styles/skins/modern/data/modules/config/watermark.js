(function($) {
	'use strict';

	$(function() {
		/**
		 * Обработчик выбора водяного знака
		 * @param {String} filepath путь до выбранного файла - водяного знака
		 */
		window.onChooseWaterMark = function(filepath) {
			var $waterMarkSelect = jQuery('.watermark select');
			var currentFilePath = '';

			if ($waterMarkSelect.length > 0) {
				currentFilePath = $waterMarkSelect.val();
				currentFilePath = currentFilePath.replace(/^\./, '');
			}

			if (currentFilePath == filepath) {
				return;
			}

			jQuery.jGrowl(getLabel('js-current-watermark'), {
				'header': 'UMI.CMS',
				'life': 10000
			});
		};
	});
}(jQuery));
