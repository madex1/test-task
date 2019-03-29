function CSRF(token) {
	this.token = token;

	this.protectForms = function() {
		jQuery('form').append('<input type="hidden" name="csrf" value="' + this.token + '" />');
		return true;
	};

	this.getToken = function() {
		return this.token;
	}

	this.changeToken = function(token) {
		this.token = token;
		jQuery('form input[name="csrf"]').val(token);
	}

	this.setAjaxSettings = function() {
		jQuery.ajaxSetup({
			beforeSend: function(jqXHR, settings) {
				if (settings.type == 'POST' && settings.data) {
					if (settings.data instanceof FormData) {
						settings.data.append('csrf', csrfProtection.token);
						return;
					}

					if (typeof settings.data !== 'string') {
						return;
					}

					var data = settings.data.match(/csrf=([^&]*)/);
					if (!data) { // Добавляем
						settings.data += '&csrf=' + csrfProtection.token;
					} else if (data[1] == '') { // Заменяем
						settings.data = settings.data.replace(/csrf=[^&]*/, 'csrf=' + csrfProtection.token);
					}
				}
			}
		});
	}
}
