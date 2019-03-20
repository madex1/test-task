/** Функционал панели инструментов в форме редактирования сущности */
(function ($) {
	"use strict";

	/** Выполняется, когда все элементы DOM готовы */
	$(function () {
		$('#delete-entity').on('click', onClickDeleteEntity);
	});

	/**
	 * Обрабатывает клик на элемент: удаляет сущность
	 * @param event событие клика элемент
	 * @returns {Boolean}
	 */
	var onClickDeleteEntity = function (event) {
		event.preventDefault();

		var button = $(this);
		var module = button.data('delete-module');
		var moduleUri = '/admin/' + module + '/';
		var method = button.data('delete-method');
		var entityId = button.data('delete-entity-id');
		var entityType = button.data('delete-entity-type');
		var popupTitle = getLabel('js-edit-form-delete-popup-header');
		var popupContent = getLabel('js-edit-form-delete-popup-content');
		var confirmButtonText = getLabel('js-edit-form-delete-confirm-button');
		var cancelButtonText = getLabel('js-edit-form-delete-cancel-button');
		var dataType = 'json';
		var deleteRequestAddress = getDeleteRequestAddress(
			moduleUri, method, dataType, entityId, entityType
		);

		openDialog('', popupTitle, {
			cancelButton: true,
			html: popupContent,
			confirmText: confirmButtonText,
			cancelText: cancelButtonText,
			confirmCallback: function () {
				sendDeleteRequest(
					deleteRequestAddress, dataType,moduleUri
				)
			}
		});

		return false;
	};

	/**
	 * Возвращает адрес запроса на удаление сущности
	 * @param {String} moduleUri uri модуля, который отвечает за удаление
	 * @param {String} method название метода, который отвечает за удаление
	 * @param {String} dataType тип (формат) данных, в котором мы хотим получить ответ на запрос
	 * @param {Number} entityId идентификатор удаляемой сущности
	 * @param {String} entityType тип удаляемой сущности
	 * @returns {String}
	 */
	var getDeleteRequestAddress = function(moduleUri, method, dataType, entityId, entityType) {
		return moduleUri + method + '.' + dataType + '?element[0][id]=' + entityId + '&element[0][__type]=' + entityType;
	};

	/**
	 * Отправляет ajax запрос на удаление сущности
	 * @param {String} deleteRequestAddress адрес запроса на удаление сущности
	 * @param {String} dataType ип (формат) данных, в котором мы хотим получить ответ на запрос
	 * @param {String} moduleUri uri модуля, который отвечает за удаление
	 */
	var sendDeleteRequest = function(deleteRequestAddress, dataType, moduleUri) {
		var token = getCSRFToken();
		var errorMessageLifeTime = 10000;

		$.ajax({
			url: deleteRequestAddress + '&csrf=' + token,
			dataType: dataType,
			success: function (data) {
				if (typeof data.data == 'object' && typeof data.data.error == 'string') {
					onErrorDeleteRequest(
						data.data.error,
						getLabel('js-edit-form-delete-error-request-header'),
						errorMessageLifeTime
					)
				} else {
					onSuccessDeleteRequest(moduleUri)
				}
			},
			error: function () {
				onErrorDeleteRequest(
					getLabel('js-edit-form-delete-error-request-content'),
					getLabel('js-edit-form-delete-error-request-header'),
					errorMessageLifeTime
				)
			}
		});
	};

	/**
	 * Обрабатывает успешный ответ на запрос удаления сущности: закрывает диалоговок окно и перенаправляет на адрес
	 * @param {String} redirectUri адрес перенаправления
	 */
	var onSuccessDeleteRequest = function(redirectUri) {
		closeDialog();
		window.location = redirectUri;
	};

	/**
	 * Обрабатывает ошибочный ответ на запрос удаления сущности: показывает сообщение об ошибке
	 * @param {String} $messageContent сообщение об ошибке
	 * @param {String} $messageHeader заголовок сообщения об ошибке
	 * @param {String} messageLifeTime время показа сообщения в микросекундах
	 */
	var onErrorDeleteRequest = function($messageContent, $messageHeader, messageLifeTime) {
		$.jGrowl($messageContent, {
			'header': $messageHeader,
			'life': messageLifeTime
		});
	};

	/**
	 * Возвращает CSRF токен
	 * @returns {String}
	 */
	var getCSRFToken = function() {
		return csrfProtection.token;
	};

}(jQuery));