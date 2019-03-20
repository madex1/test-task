/**
 * Функционал административной панели модуля Статистика:
 *
 * 1) CRUD действия над списком счетчиков из "Яндекс.Метрика";
 * 2) Фильтры по периоду времени для статистики счетчика из "Яндекс.Метрика";
 */
var StatModule = (function($, _) {
	"use strict";

	/** @type {String} MODULE_NAME системной имя модуля */
	var MODULE_NAME = 'stat';
	/** @type {String} REQUEST_PREFIX префикс запроса к api */
	var REQUEST_PREFIX = '/admin/';
	/** @type {String} REQUEST_ADD_COUNTER_METHOD метод, который добавляет счетчик в "Яндекс.Метрика" */
	var REQUEST_ADD_COUNTER_METHOD = 'addCounter';
	/** @type {String} REQUEST_DELETE_COUNTER_METHOD метод, который удаляет счетчик из "Яндекс.Метрика" */
	var REQUEST_DELETE_COUNTER_METHOD = 'deleteCounter';
	/** @type {String} REQUEST_SAVE_COUNTER_CODE_METHOD метод, инициирует сохранение кода счетчика */
	var REQUEST_SAVE_COUNTER_CODE_METHOD = 'saveCounterCode';
	/** @type {String} REQUEST_DOWNLOAD_COUNTER_CODE_METHOD метод, инициирует скачивание кода счетчика */
	var REQUEST_DOWNLOAD_COUNTER_CODE_METHOD = 'downloadCounterCode';
	/** @type {String} REQUEST_GET_COUNTER_STAT_METHOD метод, запрашивает статистику счетчика */
	var REQUEST_GET_COUNTER_STAT_METHOD = 'getCounterStat';
	/** @type {String} DEFAULT_STAT_PAGE страница статистики, по умолчанию */
	var DEFAULT_STAT_PAGE = '/traffic/attendance/';
	/** @type {String} DATE_FILTER_FORM_SELECTOR селектор формы фильтрации по периоду дат */
	var DATE_FILTER_FORM_SELECTOR = '#statdate_settings';
	/** @type {String} DATE_START_INPUT_SELECTOR поле для ввода начала периода времени фильтра */
	var DATE_START_INPUT_SELECTOR = 'input[name = "fromDate"]';
	/** @type {String} DATE_START_INPUT_SELECTOR поле для ввода конца периода времени фильтра */
	var DATE_END_INPUT_SELECTOR = 'input[name = "toDate"]';
	/** @type {String} ERROR_REQUEST_MESSAGE сообщение об ошибке, если запрос к серверу завершился неудачно */
	var ERROR_REQUEST_MESSAGE = getLabel('js-label-request-error');

	/** Выполняется, когда все элементы DOM готовы */
	$(function () {
		bindFilterButton();
	});

	/** Прикрепляет к кнопке фильтрации действие */
	var bindFilterButton = function() {
		getDateFilterForm().on('submit', redirectToUrlWithFilter);
	};

	/**
	 * Возвращает форму фильтрации статистики по периоду времени
	 * @returns {*|HTMLElement}
	 */
	var getDateFilterForm = function() {
		return $(DATE_FILTER_FORM_SELECTOR);
	};

	/**
	 * Перенаправляет на страницу с примененным фильтром
	 * @param {Object} event событие отправки формы фильтра по периоду времени
	 */
	var redirectToUrlWithFilter = function(event) {
		event.preventDefault();
		var startDateTime = getDateStart().val();
		var endDateTime = getEndStart().val();
		var pattern = /\d{4}-\d{2}-\d{2}\s?\d{0,2}\:?\d{0,2}\:?\d{0,2}\:?$/;

		if (!pattern.test(startDateTime) || !pattern.test(endDateTime)) {
			alert(getLabel('js-error-label-incorrect-date-period'));
			return;
		}

		var startDate = trimDateTime(startDateTime);
		var endDate = trimDateTime(endDateTime);
		window.location.href = buildFilterUrl(startDate, endDate);
	};

	/**
	 * Удаляет из даты время
	 * @param {String} dateWithTime дата со временем
	 * @return {String}
	 */
	var trimDateTime = function(dateWithTime) {
		return dateWithTime.replace(/\s\d{1,2}:\d{1,2}(:\d{1,2})?$/, '');
	};

	/**
	 * Удаляет из адреса страницы фильтр по периоду времени
	 * @param {String} url адрес страницы
	 * @return {String}
	 */
	var trimDateFilter = function(url) {
		return url.replace(/(\d{4}\-\d{2}\-\d{2}\/){2}$/, '');
	};

	/**
	 * Возвращает поле ввода начала периода времени фильтра
	 * @returns {*|HTMLElement}
	 */
	var getDateStart = function() {
		return $(DATE_START_INPUT_SELECTOR);
	};

	/**
	 * Возвращает поле ввода конца периода времени фильтра
	 * @returns {*|HTMLElement}
	 */
	var getEndStart = function() {
		return $(DATE_END_INPUT_SELECTOR);
	};

	/**
	 * Формирует адрес страницы с фильтром по периоду времени
	 * @param {String} startDate начало периода (в формате Y-m-d)
	 * @param {String} endDate конец периода (в формате Y-m-d)
	 * @returns {String}
	 */
	var buildFilterUrl = function(startDate, endDate) {
		var url = trimDateFilter(window.location.href);
		return url + startDate +  '/' + endDate + '/';
	};

	/**
	 * Возвращает описание функций тулбара табличного контрола списка счетчиков
	 * @return {Object}
	 */
	var getCounterListToolBarFunctions = function() {
		return {
			add: {
				name: 'add',
				className: 'i-add',
				hint: getLabel('js-label-yandex-metric-button-add'),
				init: function(button) {
					if (!isOneEntitySelected()) {
						return dc_application.toolbar.disableButtons(button);
					}

					var statusCode = getSelectedEntityValue('status_code');

					if (statusCode === 'CS_AVAILABLE') {
						dc_application.toolbar.enableButtons(button);
					} else {
						dc_application.toolbar.disableButtons(button);
					}
				},
				release: function() {
					requestAddCounter(getSelectedId());
					return false;
				}
			},
			view: {
				name: 'view',
				className: 'i-vision',
				hint: getLabel('js-label-yandex-metric-button-view'),
				init: function(button) {
					if (!isOneEntitySelected()) {
						return dc_application.toolbar.disableButtons(button);
					}

					var statusCode = getSelectedEntityValue('status_code');

					if (statusCode === 'CS_OK') {
						dc_application.toolbar.enableButtons(button);
					} else {
						dc_application.toolbar.disableButtons(button);
					}
				},
				release: function() {
					openCounterStatPage(getSelectedId());
					return false;
				}
			},
			code: {
				name: 'code',
				className: 'i-see',
				hint: getLabel('js-label-yandex-metric-button-code'),
				init: function(button) {
					if (!isOneEntitySelected()) {
						return dc_application.toolbar.disableButtons(button);
					}

					var statusCode = getSelectedEntityValue('status_code');

					if (statusCode !== 'CS_AVAILABLE') {
						dc_application.toolbar.enableButtons(button);
					} else {
						dc_application.toolbar.disableButtons(button);
					}
				},
				release: function() {
					requestDownloadCounterCode(getSelectedId());
					return false;
				}
			},
			remove: {
				name: 'delete',
				className: 'i-remove',
				hint: getLabel('js-label-yandex-metric-button-delete'),
				init: function(button) {
					if (!isOneEntitySelected()) {
						return dc_application.toolbar.disableButtons(button);
					}

					var statusCode = getSelectedEntityValue('status_code');

					if (statusCode !== 'CS_AVAILABLE') {
						dc_application.toolbar.enableButtons(button);
					} else {
						dc_application.toolbar.disableButtons(button);
					}
				},
				release: function() {
					requestDeleteCounter(getSelectedId());
					return false;
				}
			},
			refresh: {
				name: 'refresh',
				className: 'i-restore',
				hint: getLabel('js-label-yandex-metric-button-refresh'),
				init: function(button) {
					dc_application.toolbar.enableButtons(button);
				},
				release: function() {
					dc_application.refresh();
					return false;
				}
			}
		};
	};

	/**
	 * Возвращает список названий кнопок для формирования меню тулбара табличного контрола списка счетчиков
	 * @returns {String[]}
	 */
	var getCounterListToolBarMenu = function() {
		return ['add', 'view', 'code', 'remove', 'refresh'];
	};

	/**
	 * Возвращает список значений для переключение постраничного вывода (Элементов на странице: 10 20 50 100)
	 * @returns {Array}
	 */
	var getCounterListPageLimitList = function() {
		return [];
	};

	/**
	 * Возвращает идентификатор выбранной сущности в табличном контроле
	 * @returns {String|Integer}
	 */
	var getSelectedId = function() {
		return dc_application.unPackId(getSelectedEntity().attributes.id);
	};

	/**
	 * Возвращает выбранную сущность в табличном контроле
	 * @returns {Object}
	 */
	var getSelectedEntity = function() {
		return dc_application.toolbar.selectedItems[0];
	};

	/**
	 * Возвращает значение поля выбранной сущности табличного контрола
	 * @param {String} name название поля
	 * @returns {*}
	 */
	var getSelectedEntityValue = function(name) {
		var item = getSelectedEntity();
		return (typeof item === 'object') ? item.attributes[name] : '';
	};

	/**
	 * Определяет выбрана ли только одна сущность в табличном контроле
	 * @returns {Boolean}
	 */
	var isOneEntitySelected = function() {
		return dc_application.toolbar.selectedItemsCount === 1;
	};

	/**
	 * Открывает страницу со статистикой счетчика
	 * @param {Integer} counterId идентификатор счетчика
	 */
	var openCounterStatPage = function(counterId) {
		var url = REQUEST_PREFIX + MODULE_NAME + '/' + REQUEST_GET_COUNTER_STAT_METHOD + '/' + counterId +
			DEFAULT_STAT_PAGE;
		redirect(url);
	};

	/**
	 * Запрашивает добавление счетчика в "Яндекс.Метрика"
	 * @param {Integer} domainId идентификатор домена
	 */
	var requestAddCounter = function(domainId) {
		var requestParams = {
			type:		'POST',
			url:		REQUEST_PREFIX + MODULE_NAME + '/' + REQUEST_ADD_COUNTER_METHOD + '/.json',
			dataType:	'json',
			data: {
				csrf: getCSRFToken(),
				domain_id: domainId
			}
		};

		sendAjaxRequest(requestParams, function() {
			dc_application.refresh();
		}, showMessage);
	};

	/**
	 * Запрашивает удаление счетчика из "Яндекс.Метрика"
	 * @param {String} counterId идентификатор счетчика
	 */
	var requestDeleteCounter = function(counterId) {
		var requestParams = {
			type:		'POST',
			url:		REQUEST_PREFIX + MODULE_NAME + '/' + REQUEST_DELETE_COUNTER_METHOD + '/.json',
			dataType:	'json',
			data: {
				csrf: getCSRFToken(),
				counter_id: counterId
			}
		};

		sendAjaxRequest(requestParams, function() {
			dc_application.refresh();
		}, showMessage);
	};

	/**
	 * Запрашивает скачивание кода счетчика
	 * @param {String} counterId идентификатор счетчика
	 */
	var requestDownloadCounterCode = function(counterId) {
		var requestParams = {
			type:		'GET',
			url:		REQUEST_PREFIX + MODULE_NAME + '/' + REQUEST_SAVE_COUNTER_CODE_METHOD + '/.json',
			dataType:	'json',
			data: {
				csrf: getCSRFToken(),
				counter_id: counterId
			}
		};

		sendAjaxRequest(requestParams, function(response) {
			if (response.data && response.data.success) {
				var url = REQUEST_PREFIX + MODULE_NAME + '/' + REQUEST_DOWNLOAD_COUNTER_CODE_METHOD + '/' + counterId;
				return redirect(url);
			}

			dc_application.refresh();
		}, showMessage);
	};

	/**
	 * Перенаправляет на указанный адрес
	 * @param {String} url адрес
	 */
	var redirect = function(url) {
		document.location.href = url;
	};

	/**
	 * Отправляет ajax запрос
	 * @param {Object} requestParams параметры запроса
	 * @param {Function} successCallback обработчик успешного получения ответа
	 * @param {Function} errorCallback обработчик ошибочного получения ответа
	 */
	var sendAjaxRequest = function(requestParams, successCallback, errorCallback) {
		var response = $.ajax(requestParams);

		response.success(function(result){
			if (isRequestResultContainsErrorMessage(result)) {
				return errorCallback(result.data.error);
			}

			if (isRequestResultContainsException(result)) {
				return errorCallback(result.message);
			}

			successCallback(result);
		});

		response.error(function(){
			var message = ERROR_REQUEST_MESSAGE;

			if (response.status === 403 && response.responseJSON && response.responseJSON.data && response.responseJSON.data.error) {
				message = response.responseJSON.data.error;
			}

			errorCallback(message);
		});
	};

	/**
	 * Проверяет содержит ли результат запроса сообщение об ошибке и возвращает результат проверки
	 * @param {Object} result результат запроса
	 * @returns {Boolean}
	 */
	var isRequestResultContainsErrorMessage = function(result) {
		return !_.isUndefined(result.data) && !_.isUndefined(result.data.error);
	};

	/**
	 * Проверяет содержит ли результат запроса данные исключения и возвращает результат проверки
	 * @param {Object} result результат запроса
	 * @returns {Boolean}
	 */
	var isRequestResultContainsException = function(result) {
		return !_.isUndefined(result.code) && !_.isUndefined(result.trace) && !_.isUndefined(result.message);
	};

	/**
	 * Показывает сообщение
	 * @param {String} message сообщение
	 */
	var showMessage = function(message) {
		$.jGrowl(message);
	};

	/**
	 * Возвращает CSRF токен
	 * @returns {String}
	 */
	var getCSRFToken = function() {
		return csrfProtection.token;
	};

	return {
		getCounterListToolBarFunctions: getCounterListToolBarFunctions,
		getCounterListToolBarMenu: getCounterListToolBarMenu,
		getCounterListPageLimitList: getCounterListPageLimitList
	};
})(jQuery, _);