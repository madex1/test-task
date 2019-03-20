/** Функционал табличного контрола модуля Слайдеры */
(function ($) {
	"use strict";

	/** @type {String} rowIdKey название свойства сущности, в которой хранится ее ид */
	var rowIdKey = 'id';
	/** @type {string} typeKey название свойства сущности, в которой хранится ее тип */
	var rowTypeKey = '__type';
	/** @type {*|HTMLElement} $slideCreateButton кнопка создания слайдов */
	var $slideCreateButton = $('#addSlide');
	/** @type {String} hiddenClass класс элемента, который отключает его отображение */
	var hiddenClass = 'hidden';
	/** @type {String} sliderType тип сущности "Слайдер" */
	var sliderType = $slideCreateButton.parent().data('slider-type');
	/** @type {String} buttonAddressAttributeName название атрибута кнопки добавления слайдов с адресом запроса */
	var buttonAddressAttributeName = 'href';
	/** @type {String} slideCreateButtonAddress адрес запроса на добавление слайда */
	var slideCreateButtonAddress = $slideCreateButton.attr(buttonAddressAttributeName);
	/** @type {String} slideIdRequestKey ключ данных в запросе на создание слайда с идентификатором слайдера */
	var slideIdRequestKey = 'rel';
	/** @type {String} rowSelectEventName название события изменения строки в табличном контроле */
	var rowSelectEventName = 'row_select';

	/** Выполняется, когда все элементы DOM готовы */
	$(function () {
		dc_application.on(rowSelectEventName,function (e){
			onRowSelectCallback();
		});
	});

	/**
	 * Обрабатывает событие выделения строки в табличном контроле:
	 *
	 * 1) Переключает видимость кнопки создания слайдов;
	 * 2) Меняет адрес запрос на создание слайда;
	 *
	 * Строка представляет собой сущность.
	 */
	var onRowSelectCallback = function() {
		var slidersCount = getSelectedEntitiesCountByType(sliderType);

		toggleCreateSlideButtonVisibility(slidersCount);

		var selectedSliders = getSelectedEntitiesIdsByType(sliderType);
		var sliderId = (slidersCount > 0) ? selectedSliders.shift() : null;

		toggleCreateSlideButtonRequestAddress(sliderId);
	};

	/**
	 * Возвращает количество выделенных сущностей заданного типа
	 * @param {String} entityType идентификатор типа данных
	 * @returns {Number}
	 */
	var getSelectedEntitiesCountByType = function(entityType) {
		var selectedEntities = getSelectedEntities();
		var entitiesCounter = 0;

		for (var i = 0, cnt = selectedEntities.length; i < cnt; i++){
			if (selectedEntities[i].get(rowTypeKey) == entityType && selectedEntities[i].selected){
				entitiesCounter++;
			}
		}

		return entitiesCounter;
	};

	/**
	 * Возвращает идентификатор выделенных сущностей заданного типа
	 * @param {String} entityType идентификатор типа данных
	 * @returns {Array}
	 */
	var getSelectedEntitiesIdsByType = function(entityType) {
		var selectedEntities = getSelectedEntities();
		var entitiesIds = [];

		for (var i = 0, cnt = selectedEntities.length; i < cnt; i++){
			if (selectedEntities[i].get(rowTypeKey) == entityType && selectedEntities[i].selected){
				var entityId = dc_application.unPackId(selectedEntities[i].get(rowIdKey));
				entitiesIds.push(entityId);
			}
		}

		return entitiesIds;
	};

	/**
	 * Возвращает выделенные сущности
	 * @returns {Toolbar.selectedItems|*|n.selectedItems}
	 */
	var getSelectedEntities = function() {
		return dc_application.toolbar.selectedItems;
	};

	/**
	 * Переключает видимость кнопки создания слайдов, в зависимости от количества выделенных слайдеров
	 * @param {Number} slidersCount количество выделенных слайдеров
	 */
	var toggleCreateSlideButtonVisibility = function(slidersCount) {
		if (slidersCount == 1) {
			$slideCreateButton.removeClass(hiddenClass);
		} else {
			$slideCreateButton.addClass(hiddenClass);
		}
	};

	/**
	 * Переключает адрес запроса у кнопки создания слайда, в зависимости от слайдера
	 * @param {Number|Null} sliderId идентификатор выделенного слайдера или null, если выделено несколько слайдеров
	 */
	var toggleCreateSlideButtonRequestAddress = function(sliderId) {
		if (typeof sliderId != 'null'){
			$slideCreateButton.attr(
				buttonAddressAttributeName, slideCreateButtonAddress + '&' + slideIdRequestKey + '=' + sliderId
			);
		} else {
			$slideCreateButton.attr(
				buttonAddressAttributeName, slideCreateButtonAddress
			);
		}
	};

}(jQuery));
