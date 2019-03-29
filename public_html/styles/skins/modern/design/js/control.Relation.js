/**
 * Контрол для полей типа "Выпадающий список" и "Выпадающий список со множественным выбором".
 * @param {Object} options опции контрола
 */
var ControlRelation = function(options) {

	/**
	 * Контейнер, в котором будет расположен контрол
	 * @type {jQuery}
	 * @private
	 */
	var _$container = options.container;

	/**
	 * Идентификатор справочника, к которому привязано поле
	 * @type {String}
	 * @private
	 */
	var _typeId = options.type;

	/**
	 * Адрес для загрузки элементов справочника
	 * @type {String}
	 * @private
	 */
	var _sourceUri = options.sourceUri || '/admin/data/guide_items_all/';

	/**
	 * Объект Selectize, через который выполняются все операции с селектом элементов справочника
	 * @link https://selectize.github.io/selectize.js/
	 * @type {Selectize}
	 * @public
	 */
	var _selectize;

	/**
	 * Текущее значение выпадающего списка
	 * @type {String}
	 * @private
	 */
	var _currentValue = '';

	/**
	 * Определяет, нужно ли загрузить элементы справочника с сервера
	 * @type {boolean}
	 * @private
	 */
	var _needLoad = true;

	/** Инициализирует контрол */
	(function initialize() {
		validate();

		$('.relation-add', _$container).bind('click', function() {
			openNewGuideItemPopup();
		});

		var preload = (options.preload == undefined ? true : options.preload);
		var selectize = $('select', _$container).selectize({
			plugins: ['remove_button', 'clear_selection'],
			allowEmptyOption: true,
			create: false,
			onType: function() {
				var input = $('.selectize-input input', _$container);
				_currentValue = input.val();
			},
			onFocus: function() {
				loadItems();
			},
			onInitialize: function() {
				if (preload) {
					_$container.one('mouseover', function() {
						loadItems();
					});
				}
			}
		});
		_selectize = selectize[0].selectize;
	})();

	return {
		selectize: _selectize,
		loadItemsAll: loadItemsAll,
		makeSearch: makeSearch,
		updateElements: updateElements,
	};

	/**
	 * Проверяет корректность переданных данных для инициализации контрола.
	 * @private
	 */
	function validate() {
		if (!_$container) {
			throw 'Container is not defined';
		}

		if (typeof _typeId !== 'string') {
			throw 'Type id is not defined';
		}
	}

	/**
	 * Загружает элементы справочника с сервера, если они присутствуют
	 * @private
	 */
	function loadItems() {
		if (!_needLoad) {
			return;
		}

		loadItemsAll(function(response) {
			var items = response.responseXML.getElementsByTagName('object');

			if (items.length) {
				updateElements(response);
			} else {
				items = response.responseXML.getElementsByTagName('empty');
				var result = String(items[0].getAttribute('result')).toLowerCase().replace(/[\s]/g, '');
				if (result === 'toomuchitems') {
					makeSearch();
				}
			}

			_needLoad = false;
		}, true);
	}

	/**
	 * Загружает все элементы справочника
	 * @param {Function} callback
	 * @param {boolean} override
	 * @public
	 */
	function loadItemsAll(callback, override) {
		$.ajax({
			url: _sourceUri + _typeId + ".xml?allow-empty",
			type: "get",
			complete: function(r) {
				if (override) {
					callback(r);
					return;
				}

				updateElements(r, callback);
			}
		});
	}

	/**
	 * Обновляет элементы DOM в соответствии с загруженными данными
	 * @param {Object} response ответ от сервера
	 * @param {Function} callback вызывается после обновления DOM
	 * @public
	 */
	function updateElements(response, callback) {
		callback = (typeof callback === 'function') ? callback : function(r) {};

		if (!_selectize) {
			return;
		}

		var items = response.responseXML.getElementsByTagName('object');
		_selectize.lock();
		var oldValue = _selectize.getValue();

		if (typeof oldValue === 'string') {
			oldValue = [oldValue];
		}

		_selectize.clearOptions(true);

		for (var i = 0, count = items.length; i < count; i++) {
			_selectize.addOption({
				text: items[i].getAttribute('name'),
				value: items[i].getAttribute('id')
			});
		}

		for (var j = 0, oldCount = oldValue.length; j < oldCount; j++) {
			_selectize.addItem(oldValue[j], true);
		}

		_selectize.unlock();
		_needLoad = false;
		callback(response);
	}

	/**
	 * Создает контрол с возможностью поиска
	 * @return {Selectize} новый объект Selectize
	 * @public
	 */
	function makeSearch() {
		$('.selectize-control', _$container).remove();

		var select = $('select', _$container);
		var id = select.attr('id');
		var name = select.attr('name');
		var umiGuide = select.attr('umi:guide');
		var umiName = select.attr('umi:name');
		var multiple = select.attr('multiple');
		var options = select[0].innerHTML;

		select.remove();

		var newSelect = $('<select>', {
			placeholder: getLabel('js-relation_search'),
			id: id,
			name: name
		});

		if (multiple) {
			newSelect.attr('multiple', 'multiple');
		}

		if (umiGuide) {
			newSelect.attr('umi:guide', umiGuide);
		}

		if (umiName) {
			newSelect.attr('umi:name', umiName);
		}

		newSelect[0].innerHTML = options;

		$('.selectize-container', _$container).append(newSelect);
		createSearchedSelectize();
		return _selectize;

		/** Создает объект Selectize с возможностью поиска */
		function createSearchedSelectize() {
			var selectize = $('select', _$container).selectize({
				plugins: ['remove_button', 'clear_selection'],
				allowEmptyOption: true,
				create: false,
				onType: function() {
					var input = $('.selectize-input input', _$container);
					_currentValue = input.val();
				},
				load: function(query, callback) {
					$.ajax({
						url: _sourceUri + _typeId + ".xml",
						data: {
							search: [query]
						},
						type: "get",
						complete: function(r) {
							if (callback) {
								var items = r.responseXML.getElementsByTagName('object'),
									result = [];
								for (var i = 0, cnt = items.length; i < cnt; i++) {
									result.push({text: items[i].getAttribute('name'), value: items[i].getAttribute('id')});
								}
								callback(result);
							}
						}
					});
				}
			});

			_selectize = selectize[0].selectize;
		}
	}

	/**
	 * Выводит всплывающее окно создания нового объекта справочника
	 * @private
	 */
	function openNewGuideItemPopup() {
		/** @var {editableCell} */
		var cell = _selectize.editableCell;
		if (cell) {
			cell.unregisterOutsideClick();
		}

		openDialog('', getLabel('js-new_guide_item_title'), {
			html: '<div class="title-edit">' + getLabel('js-new_guide_item_name') + '</div>' +
			'<input id="newRelationVal" type="text" class="default" value="' + String(_currentValue).trim() + '"/>',
			cancelButton: true,
			confirmText: getLabel('js-new_guide_item_add'),
			cancelText: getLabel('js-new_guide_item_cancel'),
			confirmOnEnterElement: '#newRelationVal',

			confirmCallback: function(popupName) {
				var name = $('#newRelationVal').val().trim();
				if (!name) {
					return;
				}

				addObjectToGuide(name, _typeId, function(newObjectName, newObjectId) {
					_selectize.addOption({text: newObjectName, value: newObjectId});
					_selectize.addItem(newObjectId, true);
					closeDialog(popupName);
					if (cell) {
						cell.registerOutsideClick();
					}
				});
			},

			cancelCallback: function(popupName) {
				closeDialog(popupName);
				if (cell) {
					cell.registerOutsideClick();
				}
			}
		});
	}

	/**
	 * Добавляет новый объект в справочник
	 * @param {String} name название объекта
	 * @param {Number} guideId идентификатор справочника
	 * @param {Function} callback вызывается после добавления объекта
	 * @private
	 */
	function addObjectToGuide(name, guideId, callback) {
		var data = {
			param0: name,
			param1: guideId
		};

		$.ajax({
			url: '/admin/udata://data/addObjectToGuide/.json',
			dataType: 'json',
			data: data,
			type: 'post',

			success: function(response) {
				if (!response.data || !response.data.object) {
					return;
				}

				var newObjectId = parseInt(response.data.object.id);
				if (isNaN(newObjectId) || newObjectId <= 0) {
					return;
				}

				var newObjectName = response.data.object.name;
				callback(newObjectName, newObjectId);
			}
		});
	}
};
