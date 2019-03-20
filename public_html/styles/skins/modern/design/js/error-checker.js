/**
 * Функция валидации значений полей и вывода соответветствующих ошибок в админке
 * @param options опции для валидации
 * @param options.form форма, поля которой будут провалидированы
 * @param options.check список имен валидаторов и селектор полей, которые будут провалидированы
 * @example Пример вызова функции с указанием формы и валидаторов на пустые поля и числовые значения.
 * checkErrors({
 *  form: $('form').eq(0),
 *  check: {
 *      empty: 'input.required',
 *      number: 'input[type=number]'
 *  }});
 */
var checkErrors = (function($, _) {
	var funcPrefix = 'check';
	var templateElement = null;
	var template = null;
	var errors = [];
	var elementsWithErrors = [];
	var inputSelector = ":input[name^='data[']";

	var allowedCheckingFunctions = {

		/**
		 * Валидирует поля на пустые значения
		 * @param {String|HTMLCollection|HTMLElement|jQuery} form форма, в которой находятся целевые поля
		 * @param {jQuery} $inputContainers поля, которые будут провалидированы
		 */
		checkEmpty: function(form, $inputContainers) {
			if ($inputContainers) {
				checkEmptyDefault($inputContainers);
				return;
			}

			var $asterisks = $('sup:contains(*)', form);

			var $defaultDivs = $asterisks.closest('.default-empty-validation');
			var $multipleImageDivs = $asterisks.closest('.multiimage');
			var $symlinkDivs = $asterisks.closest('.symlink');
			var $optionedDivs = $asterisks.closest('.optioned');

			checkEmptyDefault($defaultDivs);
			checkEmptyMultipleImage($multipleImageDivs);
			checkEmptySymlink($symlinkDivs);
			checkEmptyOptioned($optionedDivs);

			function checkEmptyDefault($defaultDivs) {
				$defaultDivs.each(function() {
					var $div = $(this);

					$div.find(inputSelector).each(function() {
						var editor = null;
						var editorValue = null;
						var isEmptyValue = false;

						var isTextArea = ( this.tagName && this.tagName.toLowerCase() === 'textarea' );
						var isTinymceTextArea = false;

						if (isTextArea) {
							if (typeof tinyMCE == 'object') {
								editor = tinyMCE.get(this.id);

								if (editor && typeof(editor.getContent) === 'function') {
									isTinymceTextArea = true;
								}
							}
						}

						if (isTinymceTextArea) {
							editorValue = editor.getContent({format: 'text'});
							isEmptyValue = (typeof(editorValue) === 'string' && editorValue.length === 0) ||
									(editorValue.length === 1 && editorValue.charCodeAt(0) === "\n".charCodeAt(0));
						} else {
							isEmptyValue = (this.value === '');
						}

						if ($div.hasClass('img_file') && !$(this).attr('id')) {
							return false;
						}

						if (isEmptyValue) {
							if (isTextArea && editor) {
								elementsWithErrors.push($('iframe', editor.contentAreaContainer).get(0));
							} else {
								elementsWithErrors.push(this);
							}

							var fieldName = $div.find('acronym').text();

							errors.push({
								title: getLabel('js-error-required-field'),
								text: fieldName
							});
						}
					});
				});

			}

			function checkEmptyMultipleImage($multiimageDivs) {
				$multiimageDivs.each(function() {
					var	isEmptyValue = ( $(this).find('.multi_image').length === 0 );

					if (isEmptyValue) {
						var fieldName = $(this).find('acronym').text();
						elementsWithErrors.push(this);
						errors.push({
							title: getLabel('js-error-required-field'),
							text: fieldName
						});
					}

				});
			}

			function checkEmptySymlink($symlinkDivs) {
				$symlinkDivs.each(function() {
					var valueCount = 0;

					$(this).find(inputSelector).each(function() {
						if ($(this).val()) {
							valueCount += 1;
						}
					});

					if (valueCount === 0) {
						var fieldName = $(this).find('acronym').text();
						elementsWithErrors.push(this);
						errors.push({
							title: getLabel('js-error-required-field'),
							text: fieldName
						});
					}

				});
			}

			function checkEmptyOptioned($optionedDivs) {
				var OPTIONED_ROW_VALUE_COUNT = 3;

				$optionedDivs.each(function() {
					var valueCount = 0;

					$(this).find(inputSelector).each(function() {
						if ($(this).val()) {
							valueCount += 1;
						}
					});

					if (valueCount < OPTIONED_ROW_VALUE_COUNT) {
						var fieldName = $(this).find('acronym').text();
						elementsWithErrors.push(this);
						errors.push({
							title: getLabel('js-error-required-field'),
							text: fieldName
						});
					}

				});
			}
		},

		/**
		 * Валидирует поля на числовые значения
		 * @param {String|HTMLCollection|HTMLElement|jQuery} form форма, в которой находятся целевые поля
		 * @param {String|HTMLCollection|HTMLElement|jQuery} fields поля, которые будут провалидированы
		 */
		checkNumber: function(form, fields) {
			var numberFields = fields || $('input.number-field', form);
			var numberRegex = /^[-+]?\d*\.?\d*$/;

			$(numberFields, form).each(function() {
				var value = $(this).val();
				var isNumber = value.match(numberRegex);

				if (!isNumber) {
					elementsWithErrors.push(this);
					var fieldName = $(this).parent().parent().find('acronym').eq(0).text();
					errors.push({
						title: getLabel('js-error-number-field'),
						text: fieldName
					});
				}
			});
		},

		/**
		 * Валидирует поля на значение времени в формате hh:mm
		 * @param {String|HTMLCollection|HTMLElement|jQuery} form форма, в которой находятся целевые поля
		 * @param {String|HTMLCollection|HTMLElement|jQuery} fields поля, которые будут провалидированы
		 */
		checkTime: function(form, fields) {
			fields = fields || $('input.time-field', form);
			var value;
			var fieldName = '';
			var isTime;

			$(fields, form).each(function() {

				var isEmpty = false;

				value = $(this).val();

				if (value === '') {
					return true;
				}

				isTime = !isEmpty && value.match(/^[0-9]{2}:[0-9]{2}$/);

				if (!isTime) {
					elementsWithErrors.push(this);
					fieldName = $(this).parent().parent().find('acronym').eq(0).text();
					errors.push({
						title: getLabel('js-error-time-field'),
						text: fieldName
					});
				}
			});
		},

		/**
		 * Валидирует поля на значение даты в формате d:m:Y
		 * @param {String|HTMLCollection|HTMLElement|jQuery} form форма, в которой находятся целевые поля
		 * @param {String|HTMLCollection|HTMLElement|jQuery} fields поля, которые будут провалидированы
		 */
		checkDate: function(form, fields) {
			fields = fields || $('input.date-field', form);
			var value;
			var fieldName = '';
			var isTime;

			$(fields, form).each(function() {

				var isEmpty = false;

				value = $(this).val();

				if (value === '') {
					return true;
				}

				isTime = !isEmpty && value.match(/^[0-9]{2}\.[0-9]{2}\.[0-9]{4}$/);

				if (!isTime) {
					elementsWithErrors.push(this);
					fieldName = $(this).parent().parent().find('acronym').eq(0).text();
					errors.push({
						title: getLabel('js-error-date-field'),
						text: fieldName
					});
				}
			});
		},

		/**
		 * Валидирует поля повторного ввода паролей
		 * @param {String|HTMLCollection|HTMLElement|jQuery} form форма, в которой находятся целевые поля
		 * @param {String|HTMLCollection|HTMLElement|jQuery} fields поля, которые будут провалидированы
		 */
		checkPasswordRepeat: function(form, fields) {
			fields = fields || $('div.password-repeat input[type="password"]', form);
			fields.each(function(){
				var $repeatPasswordInput = $(this);
				var $passwordInput = $('input[name="' + $repeatPasswordInput.data('password-input-name') + '"]');

				if ($repeatPasswordInput.val() !== $passwordInput.val()) {
					elementsWithErrors.push($repeatPasswordInput[0]);
					elementsWithErrors.push($passwordInput[0]);
					errors.push({
						title: getLabel('js-error-password-repeat')
					});
				}
			});
		}
	};

	return function(options) {
		templateElement = $('#error-checker-template');
		if (templateElement.length === 0) {
			return false;
		}

		options = options || {};
		var form = options.form || $('form.form_modify');
		var checkingFunctionsSuffixes = options.check;
		template = _.template(templateElement.html());

		_.each(checkingFunctionsSuffixes, function($inputContainers, funcName) {
			var camelFuncName = funcName.charAt(0).toUpperCase() + funcName.slice(1);
			var fullFuncName = funcPrefix + camelFuncName;

			if (typeof allowedCheckingFunctions[fullFuncName] === 'function') {
				allowedCheckingFunctions[fullFuncName](form, $inputContainers);
			}
		});

		if (errors.length === 0) {
			return true;
		}

		var errorsHTML = template({errors: errors});
		openDialog('', getLabel('js-label-errors-occurred'), {
			timer: 5000,
			width: 400,
			html: errorsHTML,
			closeCallback: function() {
				_.each(elementsWithErrors, function(element) {
					if (_.isElement(element)) {
						var style = window.getComputedStyle(element);

						if (style.display != 'none') {
							$(element).effect("highlight", {color: "#00a0dc"}, 5000);
						}
					}
				});

				elementsWithErrors = [];
				errors = [];
			}
		});

		return false;
	};

}(jQuery, _));
