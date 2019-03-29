/**
 * Инициализируем контрол для редактирования json настроек в полях
 * Службы доставки
 * Типы доставки
 * Типы приема
 * Настройки
 * на странице настроек доставки в модуле emarket
 */
jQuery(document).ready(function(){
	/** @type {String} CHANGE_USER_BUTTON_SELECTOR селектор кнопки смены пользователя в ApiShip */
	var CHANGE_USER_BUTTON_SELECTOR = '#change-api-ship-user';

	var apiShipDeliveryId = uAdmin.data.data.object.id,
		apiShipSettingsInputDataFunctions = {
			'providers': {
				apiFunc:'getApiShipAllProviders',
				textField:'name',
				valueField:'key'
			},
			'pickup_types': {
				apiFunc:'getApiShipSupportedPickupTypes',
				textField:'name',
				valueField:'id'
			},
			'delivery_types': {
				apiFunc:'getApiShipSupportedDeliveryTypes',
				textField:'name',
				valueField:'id'
			}
		},
		apiShipProvidersSettingsInput = 'input[name="data['+apiShipDeliveryId+'][settings]"]';

	_.each(apiShipSettingsInputDataFunctions, function (item, key) {
		initControl(key, item);
	});

	new ApiShipProvidersSettings({
		el: '#asps_wrapper',
		input:apiShipProvidersSettingsInput,
		deliveryID: apiShipDeliveryId
	});

	/** Обработчик нажатия на кнопку "Сменить пользователя" */
	$(CHANGE_USER_BUTTON_SELECTOR).click(function() {
		var requestParams = {
			type: 'POST',
			url: getRequestPrefix() + 'resetApiShipCredentials/.json',
			dataType: 'json',
			data: 	{
				param0: apiShipDeliveryId,
				csrf: getCSRFToken()
			}
		};

		$.ajax(requestParams).success(function(){
			location.reload()
		});
	});

	/**
	 * Возвращает префикс запроса к бекенду
	 * @returns {String}
	 */
	function getRequestPrefix() {
		return '//' + window.location.host + '/admin/emarket/';
	}

	/**
	 * Возвращает CSRF токен
	 * @returns {String}
	 */
	function getCSRFToken() {
		return csrfProtection.token;
	}

	/**
	 * Инициализируем JsonSelect контрол
	 * @param fieldName - имя поля
	 * @param config - конфигурация для контрола
	 */
	function initControl(fieldName, config) {
		var settingInputSearchString = 'input[name="data['+apiShipDeliveryId+'][' + fieldName + ']"]',
			control = new JsonSelectControl({
				el: settingInputSearchString,
				isMultiple: true
			}),
			url = getRequestPrefix() + config.apiFunc + '/' + apiShipDeliveryId + '/.json';

		control.query(url).done(function (response) {
			var savedValues = $(settingInputSearchString).val();
			response= JSON.parse(response);

			if (_.isUndefined(response.data) && _.isArray(response)) {
				_.each(response,function (item){
					control.addOption({text: item[config.textField], value: item[config.valueField]})
				});
			}

			if (_.isString(savedValues) && savedValues.length > 0) {
				savedValues = JSON.parse(savedValues);

				if (savedValues.length > 0) {
					control.addItem(savedValues);
				}
			}
		});
	}
});