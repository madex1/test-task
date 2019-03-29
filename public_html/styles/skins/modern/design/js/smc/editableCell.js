/**
 * Редактируемая ячейка в табличном контроле.
 *
 * @constructor
 * @param {TableItem} tableItem Ряд табличного контрола, в котором находится ячейка
 * @param {HTMLTableCellElement} htmlTableCell HTML-тег <td>, к которому привязана ячейка
 * @param {Object} propInfo Свойства поля ячейки
 * @param {HTMLElement} editButton HTML-тег <i> - кнопка "Быстрое редактирование" (иконка "Карандаш")
 */
var editableCell = function(tableItem, htmlTableCell, propInfo, editButton) {

	/**
	 * @type {Number} Код кнопки "Escape"
	 * @private
	 */
	var ESCAPE_KEY_CODE = 27;

	/**
	 * @type {Number} Код кнопки "Enter"
	 * @private
	 */
	var ENTER_KEY_CODE = 13;

	/**
	 * @type {editableCell} Ячейка
	 * @private
	 */
	var _self = this;

	/**
	 * @type {Object} Свойства поля ячейки
	 * @private
	 */
	var _propInfo = propInfo;

	/**
	 * @type {String} Название поля
	 * @private
	 */
	var _propName = propInfo.fieldName || propInfo.name;

	/**
	 * @type {TableItem} Ряд табличного контрола, в котором находится ячейка
	 * @private
	 */
	var _item = tableItem;

	/**
	 * @type {Boolean} Индикатор работы табличного контрола в объектном режиме
	 * @private
	 */
	var _isObjectMode = (_item.control.contentType !== 'pages');

	/**
	 * @type {Object} jQuery-объект элемента, в который вводится новое значение поля.
	 * Конкретный элемент зависит от типа поля: это может быть input, select, div и т.д.
	 * @private
	 */
	var _$editControl;

	/**
	 * @type {String} Предыдущее значение поля.
	 * Его нужно запоминать для того, чтобы не делать запрос
	 * на сохранение нового значения поля, если оно равно старому значению.
	 * @private
	 */
	var _oldValue = '';

	/**
	 * @type {String} Предыдущий HTML-текст поля.
	 * Если новое значение поля не было сохранено,
	 * то будет восстановлен старый контент поля.
	 * @private
	 */
	var _oldCellContent = '';

	/**
	 * @type {Boolean} Индикатор активности ячейки
	 * @public
	 */
	this.isActive = false;

	/**
	 * @type {HTMLTableCellElement} HTML-элемент <td>, к которому привязана ячейка
	 * @public
	 */
	this.element = htmlTableCell;

	/**
	 * Инициализирует новую ячейку
	 * @private
	 */
	(function initialize() {
		if (!isValid()) {
			return;
		}

		$(editButton).on('click', editButtonClickHandler);
		_self.isActive = true;

		/**
		 * Проверяет ячейку на валидность
		 * @private
		 * @returns {boolean}
		 */
		function isValid() {
			if (!editableCell.enableEdit) {
				return false;
			}

			if (_.contains(editableCell.ignoreDataTypes, propInfo['dataType'])) {
				return false;
			}

			if (_.contains(editableCell.ignorePropNames, _propName)) {
				return false;
			}

			return true;
		}

		/**
		 * Обработчик нажатия на кнопку "Быстрое редактирование" (иконка "Карандаш")
		 * @private
		 */
		function editButtonClickHandler() {
			if (editableCell.activeCell) {
				return;
			}

			_self.makeEditable();
		}
	})();

	/**
	 * Сохраняет новое значение поля
	 * @public
	 */
	this.prepareSaveData = function() {
		if (_$editControl) {
			_self.save(_$editControl.val(), false);
		}
	};

	/**
	 * Делает ячейку редактируемой.
	 * Одновременно в контроле может быть не больше одной редактируемой ячейки.
	 * @public
	 */
	this.makeEditable = function() {
		editableCell.activeCell = _self;
		getData();
	};

	/**
	 * Запрашивает данные из бекенда для редактирования ячейки.
	 * @private
	 */
	var getData = function() {
		$.ajax({
			url: getBackendUrl('get'),
			type: 'GET',
			dataType: 'xml',
			success: onGetData,
			error: function(rq, status, error) {
				onError(error, 'get');
			}
		});
	};

	/**
	 * Обработчик ошибки от сервера
	 * @param {String} error Сообщение об ошибке
	 * @param {String} type тип запроса - 'get' или 'save'
	 * @private
	 */
	var onError = function(error, type) {
		reportError(getLabel('js-edcell-' + type + '-error') + error);
	};

	/**
	 * Выводит пользователю сообщение об ошибке
	 * @param {String} error Сообщение об ошибке
	 * @private
	 */
	var reportError = function(error) {
		editableCell.activeCell = false;
		$.jGrowl(error, {header: getLabel('js-error-header')});
	};

	/**
	 * Возвращает ссылку для запроса в бекенд в зависимости от типа запроса.
	 *
	 * Есть два вида запросов:
	 *   - Получить значение поля
	 *   - Сохранить значение поля
	 *
	 * @param {String} mode тип запроса - 'get' или 'save'
	 * (для макросов get_editable_region / save_editable_region)
	 * @returns {String}
	 * @private
	 */
	var getBackendUrl = function(mode) {
		var url = window.pre_lang + '/admin/content/' + mode + '_editable_region/' + _item.id + '/' + _propName + '.xml';
		if (_isObjectMode) {
			url += '?is_object=1';
		}
		return url;
	};

	/**
	 * Обработчик получения значения поля от сервера
	 * @param {Document} data Данные поля в xml-формате
	 * @private
	 */
	var onGetData = function(data) {
		var errorList = data.getElementsByTagName('error');
		if (errorList.length > 0) {
			reportError(errorList[0].firstChild.nodeValue);
			return;
		}

		makeEditableField(data);
	};

	/**
	 * Создает контрол редактирования поля
	 * @param {Document} data Данные поля
	 * @private
	 */
	var makeEditableField = function(data) {
		var propertyList = data.getElementsByTagName('property');
		var type = getType(propertyList);
		var valueList = data.getElementsByTagName('value');
		var value = getStringValue(valueList);
		var isMultiple = false;
		var isPublicGuide = false;

		switch (type) {
			case 'wysiwyg':
			case 'text':
			case 'string':
			case 'int':
			case 'price':
			case 'float':
			case 'tags':
			case 'link_to_object_type':
			case 'counter': {
				makeEditableStringField(value);
				break;
			}

			case 'date': {
				value = getDateValue(valueList);
				makeEditableDateField(value);
				break;
			}

			case 'boolean': {
				makeEditableBooleanField(value);
				break;
			}

			case 'relation': {
				valueList = data.getElementsByTagName('item');
				isMultiple = (propertyList[0].getAttribute('multiple') === 'multiple');
				isPublicGuide = (propertyList[0].getAttribute('public-guide') === '1');
				makeEditableRelationField(valueList, isMultiple, isPublicGuide);
				break;
			}

			case 'name': {
				valueList = data.getElementsByTagName('name');
				value = getStringValue(valueList);
				makeEditableStringField(value);
				break;
			}

			case 'color': {
				makeEditableColorField(value);
				break;
			}

			case 'img_file':
			case 'swf_file':
			case 'video_file':
			case 'file': {
				makeEditableFileField(value);
				break;
			}

			case 'domain_id_list' :
			case 'domain_id' : {
				var domainList = data.getElementsByTagName('domain');

				$.each(domainList, function (i, domain) {
					var $domain = $(domain);
					$domain.attr('name', $domain.attr('host'));
				});

				isMultiple = (type === 'domain_id_list');
				makeEditableRelationField(domainList, isMultiple, isPublicGuide, {
					'type': '',
					'sourceUri': '/admin/data/getDomainList/'
				});
				break;
			}

			case 'optioned':
				makeEditableOptionedField(data);
				break;

			default: {
				reportError(getLabel('js-edcell-unsupported-type'));
				break;
			}
		}
	};

	/**
	 * Определяет и возвращает тип поля по списку свойств поля
	 * @param {NodeList} propertyList список свойств
	 * @returns {String}
	 * @private
	 */
	var getType = function(propertyList) {
		if (_propName === 'name') {
			return 'name';
		}

		var type = '';
		if (typeof(propertyList[0]) !== 'undefined') {
			type = propertyList[0].getAttribute('type') || '';
		}

		return type;
	};

	/**
	 * Определяет и возвращает строковое значение поля по списку значений поля
	 * @param {NodeList} valueList список значений
	 * @returns {String}
	 * @private
	 */
	var getStringValue = function(valueList) {
		var value;
		if (valueList.length > 0) {
			value = valueList[0].firstChild ? valueList[0].firstChild.nodeValue : '';
		}
		return value || '';
	};

	/**
	 * Определяет и возвращает значение поля типа "Дата" по списку значений поля
	 * @param {NodeList} valueList список значений
	 * @returns {String}
	 * @private
	 */
	var getDateValue = function(valueList) {
		var value;
		if (valueList.length > 0) {
			value = valueList[0].firstChild ? valueList[0].getAttribute('formatted-date') : '';
		}
		return value || '';
	};

	/** Включает обработчик нажатия мыши в окне браузера */
	this.registerOutsideClick = function() {
		$(window).on('click', _self.outSideClick);
	};

	/** Выключает обработчик нажатия мыши в окне браузера */
	this.unregisterOutsideClick = function() {
		$(window).off('click', _self.outSideClick);
	};

	/**
	 * Обработчик нажатия мыши в окне браузера.
	 * Пытается сохранить новое значение поля, если клик был за пределами ячейки.
	 * @param {Event} e
	 * @public
	 */
	this.outSideClick = function(e) {
		var isClickedOutsideOfCell = $(_self.element).prop('id') != $(e.target).closest('td').prop('id');
		if (isClickedOutsideOfCell) {
			_self.prepareSaveData();
		}
	};

	/**
	 * Создает контрол редактирования поля со строковым значением
	 * @param {String} value текущее значение поля
	 * @private
	 */
	var makeEditableStringField = function(value) {
		_$editControl = document.createElement('input');
		_$editControl.setAttribute('type', 'text');
		_$editControl.value = value;
		_$editControl.className = 'editableCtrl default';
		_$editControl = $(_$editControl);
		_$editControl.on('keyup', keyboardPressHandler);
		_$editControl.on('blur', function() {
			if (editableCell.activeCell) {
				_self.prepareSaveData();
			}
		});

		var $container = getContainer();
		_oldCellContent = $container.html();
		_oldValue = value;
		$container.html('');

		_$editControl.appendTo($container);
		_$editControl.focus();
	};

	/**
	 * Обработчик нажатия на кнопку во время редактирования ячейки
	 * @private
	 */
	var keyboardPressHandler = function(e) {
		var keyCode = Number(window.event ? window.event.keyCode : e.which);
		if (keyCode === ESCAPE_KEY_CODE) {
			_self.restore();
		}

		if (keyCode === ENTER_KEY_CODE && editableCell.activeCell) {
			_self.prepareSaveData();
		}
	};

	/**
	 * Возвращает контейнер, в котором хранится значение поля
	 * @returns {jQuery}
	 */
	var getContainer = function() {
		return $('div', _self.element);
	};

	/**
	 * Создает контрол редактирования поля с типом "Дата"
	 * @param {String} value текущее значение поля
	 * @private
	 */
	var makeEditableDateField = function(value) {
		_$editControl = document.createElement('input');
		_$editControl.setAttribute('type', 'text');
		_$editControl.value = value;
		_$editControl.className = 'default';
		_$editControl = $(_$editControl);
		_$editControl.on('keyup', keyboardPressHandler);
		_$editControl.on('blur', function(event) {
			var relatedTarget = event.relatedTarget || event.toElement;
			if (!relatedTarget) {
				return;
			}

			var isDatePickerClicked = $(event.relatedTarget).closest('table').hasClass('ui-datepicker-calendar');
			if (editableCell.activeCell && !isDatePickerClicked) {
				_self.prepareSaveData();
			}
		});

		var $container = getContainer();
		_oldCellContent = $container.html();
		_oldValue = value;
		$container.html('');

		_$editControl.appendTo($container);
		_$editControl.focus();
		_$editControl.datepicker({
			dateFormat: 'yy-mm-dd',
			onClose: function(dateText) {
				if (!/\d{1,2}:\d{1,2}(:\d{1,2})?$/.exec(dateText)) {
					dateText += ' 00:00:00';
				}

				_$editControl.val(dateText);

				if (editableCell.activeCell) {
					_self.prepareSaveData();
				}
			}
		});
		_$editControl.datepicker('show');
	};

	/**
	 * Инициализирует форму редактирования в табличном контроле для полей типов:
	 * "Файл", "Изображение", "Видео" и "swf"
	 * @param {String} value значения поля
	 * @private
	 */
	var makeEditableFileField = function(value) {
		var $container = getContainer();
		_oldCellContent = $container.html();
		_oldValue = value;

		var template = _.template($('#fast-edit-file-control').html());
		var editControlHtml = template({
			id: _self.element.id + '_input_id',
			value: value
		});

		$container.html('');
		$container = $(editControlHtml).appendTo($container);

		_$editControl = $('input', $container);
		_$editControl.on('keyup', keyboardPressHandler);
		_$editControl.on('change', function() {
			_$editControl.focus();
			return true;
		});
		_self.registerOutsideClick();
		var type = _propInfo['dataType'] || '';

		$('a.icon-action', $container).on('click', function() {
			var fileBrowserParam;

			switch (type) {
				case 'img_file': {
					fileBrowserParam = 'image=1';
					break;
				}

				case 'swf_file':
				case 'video_file': {
					fileBrowserParam = 'video=1';
					break;
				}

				default: {
					fileBrowserParam = '';
				}
			}

			showFileBrowser(_$editControl, fileBrowserParam);
		});

		_$editControl.focus();
	};

	/**
	 * Открывает файловый менеджер
	 * @param {Object} $input Jquery объект инпута, в который необходимо вставить выбранный файл в файловом менеджере
	 * @param {String} customParam кастомный параметр инициализации файлового менеджера
	 * @private
	 */
	var showFileBrowser = function($input, customParam) {
		var inputId = $input.attr('id');
		var filePath = $input.val();
		var matchResult = filePath.match(/(.*\/)/);
		var folderPath = (matchResult) ? matchResult[0] : '';
		var queryString = [];

		queryString.push('id=' + inputId);
		queryString.push('file=' + filePath);
		queryString.push('folder=' + folderPath);
		queryString.push('lang=' + window.lang_id);

		if (customParam) {
			queryString.push(customParam);
		}

		$.openPopupLayer({
			name: 'Filemanager',
			title: getLabel('js-file-manager'),
			width  : 1200,
			height : 600,
			url: '/styles/common/other/elfinder/umifilebrowser.html?' + queryString.join('&')
		});

		var $fileBrowser = $('div#popupLayer_Filemanager div.popupBody');

		var template = _.template($('#file-browser-options').html());
		var options = template({
			watermarkMessage: getLabel('js-water-mark'),
			rememberMessage: getLabel('js-remember-last-dir'),
			remember: getCookie('remember_last_folder', true)
		});

		$fileBrowser.append(options);
	};

	/**
	 * Создает контрол редактирования поля с типом "Цвет"
	 * @param {String} value текущее значения поля
	 * @private
	 */
	var makeEditableColorField = function(value) {
		_oldValue = value;
		var $container = getContainer();
		_oldCellContent = $container.html();
		$container.html('');

		var fieldHTML = '<div class="color table"><input type="text" class="default"></div>';
		$container = $(fieldHTML).appendTo($container);
		_$editControl = $('input', $container);

		if (value) {
			_$editControl.val(value);
		}

		_$editControl.on('keyup', keyboardPressHandler);
		_$editControl.bind('blur', function() {
			if (editableCell.activeCell) {
				_self.prepareSaveData();
			}
		});

		// noinspection ObjectAllocationIgnored
		new colorControl($container, {});

		$($container).bind('hidePicker', function() {
			if (editableCell.activeCell) {
				_self.prepareSaveData();
			}
		});
	};

	/**
	 * Создает контрол редактирования поля с типом "Выпадающий список" или
	 * "Выпадающий список с множественным выбором".
	 * @param {NodeList} valueList список значений поля
	 * @param {Boolean} isMultiple флаг поля с множественным выбором
	 * @param {Boolean} isPublicGuide поле использует публичный справочник (в него можно добавлять значения)
	 * @param {Object} [controlOptions] опции контрола "ControlRelation"
	 * @private
	 */
	var makeEditableRelationField = function(valueList, isMultiple, isPublicGuide, controlOptions) {
		controlOptions = controlOptions || {};

		var $valueList = $(valueList);
		$(_self.element).addClass('hide-editable');
		var container = getContainer();
		container.css('overflow', 'visible');
		_oldCellContent = container.html();

		var multipleValue = (isMultiple ? 'multiple' : '');
		var selectHtml = '<div class="layout-col-control quick selectize-container">' +
			'<select id="" class="default" ' + multipleValue + ' autocomplete="off"></select>' +
			'</div>';

		container.html('');
		var selectContainer = $(selectHtml).appendTo(container);

		_$editControl = $('select', selectContainer);

		$.each($valueList, function (i, item) {
			var $item = $(item);
			_$editControl.append($('<option>', {
				value: $item.attr('id'),
				text : $item.attr('name'),
				selected: true
			}));
		});

		if (isPublicGuide) {
			var addButtonHtml = '<div class="layout-col-icon">' +
				'<a id="" title="'+ getLabel('js-new_guide_item_title') + '" class="icon-action relation-add">' +
				'<i class="small-ico i-add"></i>' +
				'</a></div>';
			container.append(addButtonHtml);
		}

		controlOptions = $.extend({
			container: container,
			type: _propInfo.guideId,
			preload: false
		}, controlOptions);

		var control = new ControlRelation(controlOptions);
		control.loadItemsAll(selectItems, true);
		var selectizeObject;

		/**
		 * Обработчик загрузки элементов справочника в selectize
		 * @param response
		 */
		function selectItems(response) {
			selectizeObject = control.selectize;

			var items = response.responseXML.getElementsByTagName('object');
			if (items.length > 0) {
				control.updateElements(response);
			} else {
				items = response.responseXML.getElementsByTagName('empty');
				var result = String(items[0].getAttribute('result')).toLowerCase().replace(/[\s]/g, '');
				if (result === 'toomuchitems') {
					selectizeObject = control.makeSearch();
				}
			}

			selectizeObject.on('change', function() {
				_self.prepareSaveData();
			});

			selectizeObject.lock();
			$.each($valueList, function (i, item) {
				var $item = $(item);
				selectizeObject.addItem($item.attr('id'), true);
			});
			selectizeObject.unlock();

			selectizeObject.editableCell = _self;
		}

		_self.registerOutsideClick();
		container.on('keyup', keyboardPressHandler);

		_self.prepareSaveData = function() {
			$(_self.element).removeClass('hide-editable');
			if (!selectizeObject) {
				return;
			}

			var result = [];
			for (var i = 0; i < selectizeObject.items.length; i++) {
				var itemId = selectizeObject.items[i];
				result.push(itemId);
			}

			_self.save(result);
		};
	};

	/**
	 * Создает контрол редактирования поля с типом "Кнопка-флажок"
	 * @param {String} value текущее значение поля
	 * @private
	 */
	var makeEditableBooleanField = function(value) {
		var $container = getContainer();
		_oldCellContent = $container.html();

		$container.html('');
		var checkedValue = (value ? 'checked' : '');
		var checkBoxHtml = '<div class="checkbox ' + checkedValue + '"><input type="checkbox editableCtrl"></div>';
		_$editControl = $(checkBoxHtml).appendTo($container);

		_$editControl.on('click', function() {
			var $this = $(this);
			$this.toggleClass('checked');
			var newValue = $this.hasClass('checked') ? 1 : 0;
			_self.save(newValue, true);

			if (_.contains(['is_activated', 'is_active', 'active'], _self.element.name)) {
				_item.update({'is-active': newValue, 'id': _item.id});
			}

			return true;
		});
		_self.registerOutsideClick();

		var checkBox = _$editControl.children('input').eq(0);
		checkBox.focus();
		checkBox.bind('keydown', keyboardPressHandler);
		_$editControl.focus();
	};

	/**
	 * Создает контрол редактирования поля с типом "Составное"
	 * @param {Document} data Данные поля
	 * @private
	 */
	var makeEditableOptionedField = function(data) {
		$.ajax({
			url: '/styles/skins/modern/design/js/common/optioned_template.html',
			dataType: 'html',
			success: function(html) {
				var $templates = $($.parseHTML(html));
				var fieldTemplate = $templates.find('#field_template').html();
				var optionTemplate = $templates.find('#option_template').html();

				var property = data.getElementsByTagName('property')[0];
				var title = data.getElementsByTagName('title')[0].innerHTML;
				var guideId = property.getAttribute('guide-id');
				var objectId = property.getAttribute('object-id');
				var inputName = 'data[' + objectId + '][' + _propName + ']';
				var isPublic = (property.getAttribute('public-guide') === '1');
				var langPrefix = uAdmin.data["pre-lang"];
				var isStores = (_propName === 'stores_state');
				var type = isStores ? 'int' : 'float';

				var optionVariableList = [];
				$(data).find('option').each(function(i, option) {
					var $object = $(option).find('object');
					optionVariableList.push({
						label: $object.attr('name'),
						labelRemoveOption: getLabel('js-remove-option'),
						inputName: inputName,
						position: i,
						relationId: $object.attr('id'),
						value: $(option).attr(type),
						isStores: isStores
					});
				});

				var optionsHtml = '';
				$.each(optionVariableList, function(i, optionVariables) {
					optionsHtml += $.tmpl(optionTemplate, optionVariables)[0].outerHTML;
				});

				var fieldVariables = {
					'title': title,
					'guideId': guideId,
					'inputName': inputName,
					'isPublic': isPublic,
					'langPrefix': langPrefix,
					'type': type,
					'labelEditGuideItems': getLabel('js-edit-guide-items'),
					'labelAddRelationItem': getLabel('js-add-relation-item'),
					'labelAddOption': getLabel('js-add-option'),
					'optionsHtml': optionsHtml
				};
				var fieldHtml = $.tmpl(fieldTemplate, fieldVariables);

				openDialog('', getLabel('js-editable-optioned-field'), {
					width: 800,
					html: fieldHtml,
					cancelButton: true,
					confirmText: getLabel('js-save'),
					cancelText: getLabel('js-new_guide_item_cancel'),

					openCallback: function() {
						initOptionedFields();
					},

					confirmCallback: function(popupName) {
						var data = $('#optionedFieldForm').serialize();
						data += '&csrf=' + csrfProtection.getToken();

						$.ajax({
							type: 'POST',
							url: getBackendUrl('save'),
							dataType: 'xml',
							data: data,

							success: function() {
								$.jGrowl(getLabel('js-property-saved-success'));
							},

							error: function(rq, status, error) {
								onError(error, 'save');
							},

							complete: function() {
								closeDialog(popupName);
							}
						});
					},

					cancelCallback: function(popupName) {
						closeDialog(popupName);
					}
				});
			}
		});
	};

	/**
	 * Сохраняет измененное значение поля ячейки
	 * @param {String} content новое значение поля
	 * @private
	 */
	var setData = function(content) {
		$.ajax({
			type: 'POST',
			url: getBackendUrl('save'),
			dataType: 'xml',
			data: ({
				'data[]': content,
				'csrf': csrfProtection.getToken()
			}),

			success: onSetData,

			error: function(rq, status, error) {
				onError(error, 'save');
			},

			complete: function() {
				if (editableCell.activeCell === _self) {
					editableCell.activeCell = false;
				}
			}
		});
	};

	/**
	 * Обработчик получения значения сохраненного поля от сервера
	 * @param {Document} data Данные сохраненного поля в xml-формате
	 * @private
	 */
	var onSetData = function(data) {
		var errorList = data.getElementsByTagName('error');
		if (errorList.length > 0) {
			reportError(errorList[0].firstChild.nodeValue);
			_self.restore();
			return;
		}

		var $container = getContainer();
		var propertyList = data.getElementsByTagName('property');
		var valueList = data.getElementsByTagName('value');
		var value = getStringValue(valueList);

		setValue(getType(propertyList));
		afterSetValue();

		/**
		 * Устанавливает новое значение поля
		 * @param {String} type тип поля
		 */
		function setValue(type) {
			switch (type) {
				case 'wysiwyg':
				case 'text':
				case 'int':
				case 'price':
				case 'float':
				case 'tags':
				case 'counter':
				case 'link_to_object_type': {
					updateValue(value);
					return;
				}
				case 'date': {
					updateValue(getDateValue(valueList));
					return;
				}
				case 'string': {
					setStringValue();
					return;
				}
				case 'boolean': {
					setBooleanValue();
					return;
				}
				case 'relation': {
					setRelationValue();
					return;
				}
				case 'domain_id':
				case 'domain_id_list': {
					setDomainValue();
					return;
				}
				case 'name': {
					setNameValue();
					return;
				}
				case 'color': {
					setColorValue();
					return;
				}
				case 'video_file':
				case 'swf_file':
				case 'file':
				case 'img_file': {
					setFileValue();
					return;
				}
				default: {
					reportError(getLabel('js-edcell-unsupported-type'));
					return;
				}
			}

			/**
			 * Обновляет значение поля
			 * @param {String} value новое значение поля
			 */
			function updateValue(value) {
				$container.html(value);
				_oldValue = value;
			}

			function setStringValue() {
				var restriction = propertyList[0].getAttribute('restriction');
				if (value && restriction === 'email') {
					value = '<a href=\'mailto:' + value + '\' title=\'' + value + '\' class=\'link\'>' + value + '</a>';
				}
				updateValue(value);
			}

			function setBooleanValue() {
				value = Number(value);
				$container.html(value ? '<img alt="" style="width:13px;height:13px;" src="/images/cms/admin/mac/tree/checked.png" />' : '');
				_oldValue = value;
			}

			function setRelationValue() {
				valueList = data.getElementsByTagName('item');
				if (valueList.length === 1) {
					var name = valueList[0].getAttribute('name') || '';
					var guid = valueList[0].getAttribute('guid') || 'relation-value';
					value = '<span title="' + name + '" class="c-' + guid + '">' + name + '</span>';
				} else if (valueList.length > 1) {
					for (var i = 0; i < valueList.length; i++) {
						value += valueList[i].getAttribute('name') || '';
						if (i < valueList.length - 1) {
							value += ', ';
						}
					}
				}

				$container.css('overflow', 'hidden');
				updateValue(value);
			}

			function setDomainValue() {
				valueList = data.getElementsByTagName('domain');
				value = '';

				if (valueList.length === 1) {
					value = valueList[0].getAttribute('host') || '';
				} else if (valueList.length > 1) {
					for (var i = 0; i < valueList.length; i++) {
						var host = valueList[i].getAttribute('host') || '';
						value += host;

						if (i < valueList.length - 1 && host.length > 0) {
							value += ', ';
						}
					}
				}

				updateValue(value);
			}

			function setNameValue() {
				valueList = data.getElementsByTagName('name');
				value = getStringValue(valueList);
				$container.html(_oldCellContent);
				$container.find('.name_col').text(value);
				_item.checkBox = $('div.checkbox', $container).get(0);
				_oldValue = value;
			}

			function setColorValue() {
				var newContent = '<div class="color table"><span class="value">' + value +
					'</span><span class="color-box"><i style="background-color: ' + value +
					'"></i></span></div>';

				if (_oldValue.length === 0) {
					$container.html(newContent);
				} else {
					$container.html(_oldCellContent);
				}

				var $content = $('.value', $container);
				var colorBox = $('.color-box i', $container);

				$content.html(value);
				colorBox.css('background-color', value);
				_oldValue = value;
			}

			function setFileValue() {
				if (valueList.length > 0) {
					var $image = $(valueList[0]);
					var filePath = $image.attr('path');
					var filePathWithoutExt = filePath.substring(0, filePath.lastIndexOf('.'));
					var normalisedFilePath = filePath.substring(1);
					var ext = $image.attr('ext');
					var fileName = $image.attr('name') + '.' + ext;
					var isBroken = $image.attr('is_broken');

					var templateId = (type === 'img_file') ? 'fast-edit-image-preview' : 'fast-edit-file-preview';
					var template = _.template($('#' + templateId).html());
					value = template({
						filePath: normalisedFilePath,
						filePathWithoutExt: filePathWithoutExt,
						fileName: fileName,
						ext: ext,
						isBroken: isBroken
					});
				}

				updateValue(value);
			}
		}

		/** Завершает обработку */
		function afterSetValue() {
			if (window['onAfterSetProperty']) {
				onAfterSetProperty(_item.id, _propName, value);
			}

			$.jGrowl(getLabel('js-property-saved-success'));
			_oldCellContent = getContainer().html();
			Control.recalcItemsPosition();
		}
	};

	/**
	 * Проверяет введенное значение для поля элемента
	 * @param {String|Object|Number} value новое значение для поля
	 * @returns {boolean} результат проверки
	 * @private
	 */
	var validateValue = function(value) {
		var numberTypes = ['int', 'price', 'float', 'counter'];
		var type = _propInfo['dataType'] || '';

		if (numberTypes.indexOf(type) !== -1) {
			return validateNumberValue(value);
		}

		return true;
	};

	/**
	 * Проверяет введенное значение на соответствие числу
	 * @param {String|Object|Number} value проверяемое значение
	 * @returns {boolean} результат проверки
	 * @private
	 */
	var validateNumberValue = function(value) {
		if (value == '') {
			return true;
		}

		var isNumber = !isNaN(parseFloat(value)) && value.match(/^[0-9eE.,]+$/);

		if (!isNumber) {
			$.jGrowl(getLabel('js-error-validate-number'), {header: getLabel('js-error-header')});
			return false;
		}

		return true;
	};

	/**
	 * Сохраняет значение в поле элемента
	 * @param {String|Object|Number} newValue сохраняемое значение
	 * @param {Boolean} force сохранить при любых условиях
	 * @returns {boolean}
	 * @public
	 */
	this.save = function(newValue, force) {
		_self.unregisterOutsideClick();

		if (!_.isArray(newValue) && !force && (_oldValue == newValue || !validateValue(newValue))) {
			this.restore();
			return false;
		}

		setData(newValue);
		return true;
	};

	/**
	 * Восстанавливает предыдущее значение ячейки и убирает ее активность
	 * @public
	 */
	this.restore = function() {
		_self.unregisterOutsideClick();
		$(_self.element).removeClass('hide-editable');
		editableCell.activeCell = false;
		var $container = getContainer();
		$container.html(_oldCellContent);
		var checkboxContent = $('div.checkbox', $container)[0];

		if (_item.isCheckboxAvailable() && checkboxContent) {
			_item.checkBox = checkboxContent;
		}

		Control.recalcItemsPosition();
	};

	/**
	 * @public
	 * @deprecated
	 * @returns {Object}
	 */
	this.getEditControl = function() {
		return _$editControl;
	};
};

/**
 * @type {editableCell|Boolean}
 * Ссылка на текущую редактируемую ячейку или false
 */
editableCell.activeCell = false;

/**
 * @type {[String]}
 * Список типов полей, для которых не поддерживается быстрое редактирование,
 * и для которых не будут созданы ячейки.
 */
editableCell.ignoreDataTypes = ['wysiwyg'];

/**
 * @type {[String]}
 * Список названий полей, для которых не поддерживается быстрое редактирование,
 * и для которых не будут созданы ячейки.
 */
editableCell.ignorePropNames = [];
