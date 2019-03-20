var uAdmin = (function() {

	/**
	 * @type {Object} Коллекция обработчиков события загрузки модулей.
	 * Для каждого модуля указан список обработчиков:
	 *
	 * {
	 *   'module1': [handler1, handler2, ...],
	 *   'module2': [handler3]
	 * }
	 */
	var onLoadEventHandlers = {};

	function uAdmin() {
		if (uAdmin.prototype.instance == null) {
			uAdmin.prototype.instance = new uAdmin.prototype.get();
		}
		register(arguments);
		return uAdmin.prototype.instance;
	}

	function register() {
		var checkClass = function(parent, parent_str) {
			for (var module in parent.reg) {
				if (parent.reg[parent_str]) {
					return parent.reg[parent_str];
				} else {
					return checkClass(parent.reg[module], parent_str);
				}
			}

			return false;
		};

		var name;
		var value;
		var parent;
		var params = arguments[0];

		if (!params.length) {
			return false;
		}

		if (typeof params[0] === 'object') {
			if (Object.prototype.toString.call(params[0]) === '[object Array]') {
				return false;
			}

			for (var i in params[0]) {
				register([i, params[0][i], params[1] || null]);
			}

			return true;
		} else if (typeof params[0] === 'string') {
			if (typeof params[1] === 'undefined') {
				return false;
			}

			name = params[0];
			value = params[1];
			parent = params[2] || null;
		} else {
			return false;
		}

		if (!parent) {
			parent = uAdmin;
		}

		if (typeof parent === 'string') {
			parent = checkClass(uAdmin, parent);
		}

		if (typeof parent !== 'function') {
			return false;
		}

		var isClass = false;
		name = name.split('.');
		if (name.length > 1) {
			isClass = true;
		}

		name = name.pop();

		var reg = function() {
			parent.reg[name] = value;
			if (isClass) {
				parent.reg[name].isclass = true;
			}
		};

		if (!parent.reg) {
			parent.reg = reg;
		}

		reg();
		return true;
	}

	uAdmin.prototype.get = function() {
		return this;
	};

	uAdmin.prototype.get.prototype = uAdmin.prototype;

	uAdmin.prototype.init = function() {
		uAdmin.load(uAdmin);
		delete uAdmin.reg;

		//apply CSRF protection
		if (uAdmin.csrf) {
			jQuery(document).ajaxSend(function(event, jqXhr, settings) {
				if (settings.data instanceof FormData) {
					settings.data.append('csrf', uAdmin.csrf);
					return;
				}

				if (!settings || !settings.type || (typeof settings.data !== 'string' && typeof settings.data !== 'undefined')) {
					return true;
				}

				switch (settings.type) {
					case 'POST':
						if (typeof settings.data == 'undefined') {
							settings.data = '';
						}

						settings.data += settings.data.length ? '&csrf=' + uAdmin.csrf : 'csrf=' + uAdmin.csrf;
						break;

					case 'GET':
						settings.url += ~settings.url.indexOf('?') ? '&csrf=' + uAdmin.csrf : '?csrf=' + uAdmin.csrf;
						break;
				}
			});
		}

		var images = document.getElementsByTagName('img');
		for (var i = 0; i < images.length; i++) {
			if (images[i].getAttribute('umi:field-name') == 'photo') {
				if (!!images[i].width) {
					images[i].style.maxWidth = images[i].width + 'px';
				}
				if (!!images[i].height) {
					images[i].style.maxHeight = images[i].height + 'px';
				}
			}
		}
	};

	if (typeof JSON == 'undefined') {
		JSON = {
			parse: function(str) {
				try {
					if (str.match(/^{/g)) {
						var val = eval('(' + str + ')');
						if (Object.prototype.toString.call(val) == '[object Object]') {
							return val;
						}
					}
					throw new SyntaxError('JSON.parse: unexpected end of data');
				}
				catch (e) {
					throw new SyntaxError('JSON.parse: unexpected end of data');
				}
			}
		};
	}

	/**
	 * Возвращает признак наличия кастомной функции
	 * с указанным именем
	 * @param name
	 * @returns boolean
	 */
	uAdmin.prototype.isCustomFunction = function (name) {
		return typeof cmsCustoms === 'object' && cmsCustoms !== null &&
			name && typeof cmsCustoms[name] === 'function';
	};

	/**
	 * Подписывает handler на событие загрузки определенного модуля
	 * @param {String} module имя загруженного модуля
	 * @param {Function} handler функция-обработчик
	 * @return {Boolean} результат
	 */
	uAdmin.onLoad = function(module, handler) {
		if (typeof handler !== 'function') {
			return false;
		}

		if (module in onLoadEventHandlers === false) {
			onLoadEventHandlers[module] = [];
		}

		onLoadEventHandlers[module].push(handler);
		return handler in onLoadEventHandlers[module];
	};

	uAdmin.load = function(parent) {
		for (var module in parent.reg) {
			if (parent.reg[module].reg) {
				uAdmin.load(parent.reg[module]);
			}

			if (parent.reg[module].isclass) {
				var extend = function(usedClass, extendClass) {
					for (var i in extendClass) {
						usedClass.prototype[i] = extendClass[i];
					}
					return new usedClass();
				};

				if (parent == uAdmin) {
					parent[module] = new parent.reg[module](extend);
				} else {
					parent.prototype[module] = new parent.reg[module](extend);
				}
			} else if (parent == uAdmin) {
				parent[module] = parent.reg[module];
			} else {
				parent.prototype[module] = parent.reg[module];
			}

			// если есть обработчики события onLoad для модуля, они исполняются
			var moduleOnLoadEventHandlers = onLoadEventHandlers[module];
			if (moduleOnLoadEventHandlers != undefined && moduleOnLoadEventHandlers.length > 0) {
				for (var j = 0; j < moduleOnLoadEventHandlers.length; j++) {
					if (typeof moduleOnLoadEventHandlers[j] === 'function') {
						moduleOnLoadEventHandlers[j]();
					}
				}
			}
		}
	};

	var pageData;
	if (window.pageData) {
		pageData = window.pageData;
	} else {
		var data = jQuery.ajax({
			url: location.pathname + '.json' + location.search,
			dataType: 'json',
			async: false
		});
		pageData = JSON.parse(data.responseText);
	}

	uAdmin('data', pageData);
	return uAdmin;
})();

jQuery(document).ready(function () {
	var uAdminObject = uAdmin();

	uAdminObject.init();
	if (uAdminObject.isCustomFunction('uAdminInit')) {
		cmsCustoms.uAdminInit();
	}
});
