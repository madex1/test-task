/** Виджет настроек провайдеров в ApiShip */
var ApiShipProvidersSettings = function (options) {
	var $el = options.el || null, // Элемент в котором будет отрисовываться виджет
		$settingsInput = options.input || null, // input в который будут сохраняться настройки
		deliveryID = options.deliveryID || 0, // Системный id объекта доставки ApiShip
		layoutsTpl = templateGetter('#layoutsTpl'),  // Шаблон разметки виджета
		connectDialogTpl = _.template(templateGetter('#connectDialogTpl')), // Шаблон разметки диалога подключения провайдеров
		tabTpl = _.template(templateGetter('#tabTpl')), // Шаблон разметки таба к форме настроек
		$tabsLayout = null, // Объект содержащий табы
		$formLayout = null, // Объект содержащий формы настроек
		providersSettings = {}, // Здесь хранятся настройки загруженные с бека
		formBuilder = null, // Класс генерирующий разметку форм по переданной конфигурации
		logLayout = null; // Объект лога куда выводятся сообщения о подключении провайдеров

	/**
	 * Запрос к api
	 * @param query - текст запроса в рамках api бекенда ApiShip
	 * @returns {*}
	 */
	function query(query) {
		return $.ajax({
			url: '//' + window.location.host + '/' + query,
			dataType: 'json'
		})
	}

	/**
	 * Инициализация виджета
	 * @returns {boolean}
	 */
	function init() {
		if ($el === null) {
			return false;
		}
		$el = $($el);
		$settingsInput = $($settingsInput);
		$el.html(layoutsTpl);
		$tabsLayout = $('#asps_tabs', $el);
		$formLayout = $('#asps_form_wrapper', $el);

		query('admin/emarket/getApiShipChosenProvidersSettings/' + deliveryID + '/.json').done(function (res) {
			providersSettings = res;
			initTabs();
		});
	}

	/** Инициализирует панель с табами служб доставки */
	function initTabs() {
		formBuilder = new apiShipConfigFormBuilder({deliveryID: deliveryID});

		if (_.keys(getConfiguredProviders(providersSettings)).length > 0) {
			var $button = $('.asps_buttons', $el);
			$button.show();
			$button.click(function () {
				showConnectConfiguredProvidersDialog();
			});
		}

		if (typeof providersSettings === 'object' && providersSettings.length === 0) {
			$('#asps_form_wrapper').html(getLabel('js-asw-label-empty-delivery-providers'));
		}

		_.each(providersSettings, function (item, key) {
			$tabsLayout.append($(tabTpl({title: key})));
		});

		showFirstForm();

		initSliderOnTabsLayout();

		$('.tab', $tabsLayout).on('click', function (e) {
			$('.active', $tabsLayout).removeClass('active');
			tabClickHandler($(e.currentTarget))
		});

		$('form').submit(function () {
			savePreparedSettings();
		});
	}

	/** Показываем первую форму */
	function showFirstForm() {
		var firstProvider = _.keys(providersSettings)[0];
		$formLayout.html('');
		showForm(firstProvider);
	}

	/** Инициализируем слайдер на табах провайдеров */
	function initSliderOnTabsLayout() {
		new Sly('.asps_tab_wrapper', {
			horizontal: true,
			itemNav: 'basic',
			smart: true,
			activateOn: 'click',
			mouseDragging: true,
			touchDragging: true,
			releaseSwing: true,
			startAt: 0,
			scrollBy: true,
			speed: 300,
			elasticBounds: true,
			prevPage: 'a.asps_tabs_nav.prev',
			nextPage: 'a.asps_tabs_nav.next'
		}).init();
	}

	/** Показываем окно с прогрессом подключения настроенных провайдеров */
	function showConnectConfiguredProvidersDialog() {
		updateProvidersConfig();
		var providers = getConfiguredProviders(providersSettings);
		if (_.keys(providers).length > 0) {
			openDialog('', getLabel('js-asps-connect-provider-dialog-title'), {
				stdButtons: false,
				html: connectDialogTpl({total: (_.keys(providers).length)}),
				width: 400,
				openCallback: function () {
					logLayout = $('#asps_dialog .log');
					startConnect(providers);
				},
				confirmCallback: function () {
					closeDialog();
				}
			});
		}
	}

	/**
	 * Запускаем серию запросов на подключение настроенных провайдеров
	 * @param providers - список id провайдеров которые будут проинициализированы
	 */
	function startConnect(providers) {
		toLog(getLabel('js-asps-connect-log-start-connect') + _.keys(providers).length);
		toLog('-----------');
		toLog('');
		_.each(providers, function (params, key	) {
			var provider = key,
				apiFunction = 'connectToApiShipProvider';
			if (!_.isUndefined(params['is_connected']) && params.is_connected.value) {
				apiFunction = 'updateApiShipProviderConnection'
			}

			query('admin/emarket/'+apiFunction+'/' + deliveryID + '/' + provider + '/.json')
				.done(function (res) {
					toLog(getLabel('js-asps-connect-log-server-answer') + provider);
					if (!_.isUndefined(res.data) && !_.isUndefined(res.data.error)) {
						toLog(getLabel('js-asps-connect-log-server-error') + res.data.error);
					} else {
						toLog(res.message || res.description);
					}
					changeProgress(1);
					toLog('-----------');
					toLog('');
				});
		});
	}

	/**
	 * Изменяем прогрессбар в диалоге подключение провайдеров
	 * @param delta - число на которое должно изменится текущее значение прогрессбара
	 */
	function changeProgress(delta) {
		var progressBar = $('#asps_dialog .progress-bar'),
			current = parseFloat(progressBar.attr('aria-valuenow')) + parseFloat(delta),
			total = progressBar.attr('aria-valuemax'),
			currentPercentValue = parseFloat(current / total * 100),
			currentPercent = currentPercentValue.toFixed(1) + '%';

		progressBar.attr('aria-valuemax', total);
		progressBar.attr('aria-valuenow', current);
		progressBar.css('width', currentPercent);
		$('span', progressBar).text(currentPercent);
	}

	/**
	 * Вывод сообщения в лог окна подключения провайдеров
	 * @param mess - текст сообщения
	 */
	function toLog(mess) {
		if (logLayout.length > 0 && (_.isString(mess) || _.isNumber(mess))) {
			logLayout.append('<p>' + mess + '</p>');
		}
	}

	/**
	 * Обработчик клика по табу провайдера
	 * @param $el - дом элемент
	 */
	function tabClickHandler($el) {
		var provider = $el.attr('data-provider');
		$el.addClass('active');
		showForm(provider);
	}

	/**
	 * Генерируем или просто показываем форму с настройками провайдера
	 * @param provider - id провайдера
	 */
	function showForm(provider) {
		var $form = $('#pr_' + provider, $formLayout);
		$('.asps_form', $formLayout).hide();
		if ($form.length) {
			$form.show();
		} else {
			$formLayout.append(formBuilder.getFormHTML(provider, providersSettings[provider]));
			formBuilder.initSelect(provider, providersSettings[provider]);
		}
	}

	/**
	 * Запоминаем введенные параметры настроек провайдеров
	 * @returns {boolean}
	 */
	function savePreparedSettings() {
		updateProvidersConfig();
		$settingsInput.val(JSON.stringify(providersSettings));
		return true;
	}

	/** Обновляем объект настроек данными из форм */
	function updateProvidersConfig() {
		_.each(providersSettings, function (config, provider) {
			providersSettings[provider] = getValuesFromForm(provider, config);
		});
	}

	/**
	 * Функция поиска введенных настроек на форме провайдера
	 * @param provider - id провайдера
	 * @param config - конфигурация данного провайдера
	 * @returns {*}
	 */
	function getValuesFromForm(provider, config) {
		var $form = $('#pr_' + provider, $formLayout);

		_.each(config, function (data, key) {
			var val = '',
				type = data['type'],
				$control = $('input[asps-id="' + key + '"]', $form);

			if ($control.length == 0) {
				return false;
			}
			switch (type) {
				case 'string':
					val = $control.val();
					if (val) {
						config[key]['value'] = val;
					} else {
						config[key] = _.omit(config[key], 'value');
					}
					break;
				case 'boolean':
					val = $control.is(':checked');
					config[key]['value'] = val;
					break;
				case 'tariffs':
				case 'point':
					val = $control.val();
					if (val) {
						config[key]['value'] = val;
					} else {
						config[key]['value'] = [];
					}
					break;
				default:
					break;
			}
		});

		return config;
	}


	/**
	 * Получаем данные только тех провайдеров, у которых введены обязательные поля конфига
	 * @param conf - конфигурация провайдеров
	 * @returns {Object}
	 */
	function getConfiguredProviders(conf) {
		var out = {};

		_.each(conf, function (data, key) {
			if (isProviderConfigured(data)) {
				out[key] = conf[key];
			}
		});
		return out;
	}

	/**
	 * Проверяет изменились ли настройки провайдера
	 * @param data - настройки провайдера
	 * @returns {boolean}
	 */
	function isProviderConfigured(data) {
		var isConfigured = true;
		_.each(data, function (item) {
			if (item.required) {
				if (item.type === 'string' && (_.isUndefined(item.value ) || (!_.isUndefined(item.value) && item.value === ''))) {
					isConfigured = false;
				} else if (item.type === 'boolean' && _.isUndefined(item.value)) {
					item.value = false;
				}
			}
		});
		return isConfigured;
	}

	init();
	return {}
};

/**
 * Генератор формы по конфигу
 * @param options
 * @returns {{getFormHTML: getFormHTML, initSelect: initSelect}}
 */
var apiShipConfigFormBuilder = function (options) {
	var deliveryID = options.deliveryID || 0,
		fieldTemplates = {
			string: _.template(templateGetter('#stringFieldTpl')),
			boolean: _.template(templateGetter('#booleanFieldTpl')),
			tariffs: _.template(templateGetter('#tariffsFieldTpl')),
			point: _.template(templateGetter('#pointFieldTpl'))
		},
		tariffOptionTpl = _.template(templateGetter('#tariffOptionTpl')),
		pointOptionTpl = _.template(templateGetter('#pointOptionTpl'));

	/**
	 * Запрос к беку
	 * @param query
	 * @returns {*}
	 */
	function query(query) {
		return $.ajax({
			url: '//' + window.location.host + '/' + query,
			dataType: 'json'
		})
	}

	/**
	 * Генерируем html поля настроек для формы
	 * @param key - название поля
	 * @param conf - конфигурация поля
	 * @returns {*}
	 */
	function generateFieldHTML(key, conf) {
		if (!_.isUndefined(conf.type) && !_.isUndefined(fieldTemplates[conf.type])) {
			var data = {
				name: key
			};
			if (_.isUndefined(conf['value'])) {
				data['value'] = '';
			}
			return fieldTemplates[conf.type](_.extend(data, conf));
		}
	}

	/**
	 * Создаем html форму по переданной конфигурации
	 * @param provider - id провайдера
	 * @param conf - конфигурация провайдера
	 * @returns {string}
	 */
	function generateFormHTML(provider, conf) {
		var html = ['<div id="pr_' + provider + '" class="row asps_form">'];

		_.each(conf, function (item, key) {
			html.push(generateFieldHTML(key, item));
		});

		html.push('</div>');
		return html.join('');
	}

	/**
	 * Инициализируем select на форме настроек провайдера
	 * @param provider - id провайдера
	 * @param conf - конфиг провайдера
	 */
	function initSelect(provider, conf) {
		var tariffSelect = $('#pr_' + provider + ' .asps_select_tariffs').selectize({
				plugins: ['remove_button', 'clear_selection'],
				valueField: 'id',
				labelField: 'name',
				searchField: 'name',
				create: false,
				placeholder: getLabel('js-asps-connect-data-loading'),
				render: {
					option: function (item) {
						return tariffOptionTpl(item);
					}
				}
			}),
			pointSelect = $('#pr_' + provider + ' .asps_select_point').selectize({
				plugins: ['remove_button', 'clear_selection'],
				valueField: 'id',
				labelField: 'name',
				searchField: ['name', 'region', 'city', 'street'],
				create: false,
				placeholder: getLabel('js-asps-connect-data-loading'),
				render: {
					option: function (item) {
						return pointOptionTpl(item);
					}
				}
			});

		if (tariffSelect.length) {
			tariffSelect = tariffSelect[0].selectize;
			query('admin/emarket/getApiShipProviderTariffs/' + deliveryID + '/' + provider + '/.json')
				.done(function (res) {
					tariffSelect.lock();
					fillSelectOptions(tariffSelect, res);
					fillSelectItems(tariffSelect, conf);
					tariffSelect.unlock();
				});
		}

		if (pointSelect.length) {
			pointSelect = pointSelect[0].selectize;
			query('/admin/emarket/getApiShipPointsByProvider/' + deliveryID + '/' + provider + '/.json')
				.done(function (res) {
					pointSelect.lock();
					if (_.isUndefined(res['data'])) {
						fillSelectOptions(pointSelect, res);
						fillSelectItems(pointSelect, conf);
					} else {
						if (!_.isUndefined(res['data']['error'])) {
							pointSelect.settings.placeholder = 'У данной службы нет точек выдачи...';
							pointSelect.updatePlaceholder();
						}
					}
					pointSelect.unlock();
				})
		}
	}

	/**
	 * Заполняет options объекта селект
	 * @param selectObject
	 * @param data
	 */
	function fillSelectOptions(selectObject, data) {
		_.each(data, function (item) {
			selectObject.settings.placeholder = '  ';
			selectObject.updatePlaceholder();
			selectObject.addOption(item);
		});
	}

	/**
	 * Добавляет выбранные значения
	 * @param selectObject
	 * @param conf
	 */
	function fillSelectItems(selectObject, conf) {
		if (!_.isUndefined(conf['notAllowedPointsIds']['value'])
			&& _.isArray(conf['notAllowedPointsIds']['value'])) {
			selectObject.addItem(conf['notAllowedPointsIds']['value'], false);
		}
	}

	return {
		getFormHTML: generateFormHTML,
		initSelect: initSelect
	}
};