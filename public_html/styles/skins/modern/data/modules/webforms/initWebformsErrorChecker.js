/** Инициализирует скрипт error-checker.js в модуле "Конструктор форм" */

$(function() {
	var form = $('form').eq(0);
	$(form).submit(function() {
		return checkErrors({
			form: form,
			check: {
				empty: null
			}
		});
	});
});
