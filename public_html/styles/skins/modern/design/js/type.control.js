/** Контрол редактирования типа данных */
var modernTypeController = (function(jQuery) {
	var $ = jQuery || {},
			typeId = null,
			editableGroupsModel = {},
			restrictionLoaded = false,
			typesList = [],
			guidesList = [],
			restrictionsList = [],
			groupsContainer,    // Линк на контейнер со списком групп
			self = this;

	this.fields = [];
	this.groups = [];

	/**
	 * Возвращает заголовок для окна редактирования поля/группы
	 * @param {String} title заголовок поля
	 * @param {String} name имя поля
	 * @returns {string}
	 */
	var getEditWindowTitle = function(title, name) {
		return [
			title, '[', name, ']'
		].join('');
	};

	/**
	 * Инициализирует элементы формы всплывающего окна
	 * @param {Element|jQuery|String} scope элемент окна
	 */
	var initElements = function(scope) {
		var nameInput = $("#newname", scope);
		var titleInput = $("#newtitle", scope);

		$('.checkbox input:checked', scope).parent().addClass('checked');
		$('.checkbox', scope).click(function() {
			$(this).toggleClass('checked');
		});

		titleInput.focus(function(event) {
			$(this).select();

			if (!nameInput.val().length) {
				$(event.currentTarget).bind('keyup', {nameField: nameInput}, universalTitleConvertCallback);
			}
		}).blur(function(event) {
			$(event.currentTarget).unbind('keyup', universalTitleConvertCallback);
		});

		titleInput.focus();
	};

	/**
	 * Отображает сообщение-уведомление
	 * @param {String} message текст сообщения
	 * @param {String} title заголовок сообщения
	 */
	var showMessage = function(message, title) {
		title = title || 'UMI.CMS';
		message = typeof message == 'string' ? message : '';

		if (!jQuery.jGrowl) {
			return;
		}

		jQuery.jGrowl(message, {header: title});
	};

	var isWysiwygLoaded = false;

	/** Инициализирует поля типа WYSIWYG */
	var initWysiwyg = function() {
		if (!isWysiwygLoaded) {
			uAdmin.reg = {};
			$('head').append('<script type="text/javascript" src="/js/cms/wysiwyg/wysiwyg.js" />');
			uAdmin('settings', {
				min_height: 100
			}, 'wysiwyg');
			uAdmin('type', 'tinymce47', 'wysiwyg');
			uAdmin.load(uAdmin);
		}

		if (!uAdmin.wysiwyg) {
			return;
		}

		isWysiwygLoaded = true;
		uAdmin.wysiwyg.init();
	};

	/**
	 * Возвращает элемент, в котором выводятся данные поля
	 * @param {Number} fieldId ID поля
	 * @param {String} elementType тип данных
	 * @returns {*|jQuery|HTMLElement}
	 */
	var getFieldElement = function(fieldId, elementType) {
		var prefix = '#headf';
		var elementId = prefix + fieldId + elementType;
		return $(elementId, groupsContainer);
	};

	/**
	 * Применяет изменения к верстке при изменении данных поля
	 * @param {JSON} response ответ от сервера при успешном изменении данных поля
	 */
	var applyFieldChanges = function(response) {
		var data = response['data'] || {};

		if (!data.field || !data.field.id) {
			return;
		}
		var field = data.field;
		var fieldId = field.id;
		var title = field['title'];
		var name = field['name'];
		var typeName = field['type']['name'];
		var required = !!field['required'] ? ' *' : '';

		var fieldElement = $('li[umifieldid=' + fieldId + ']', groupsContainer);
		var invisibleClass = 'finvisible';

		if (fieldElement.length) {
			fieldElement.removeClass(invisibleClass);

			if (!field['visible']) {
				fieldElement.addClass(invisibleClass);
			}
		}

		var titleElement = getFieldElement(fieldId, 'title');
		titleElement.text(title + required);
		titleElement.attr('title', title + required);

		var nameElement = getFieldElement(fieldId, 'name');
		nameElement.text('[' + name + ']');
		nameElement.attr('title', name);

		var typeNameElement = getFieldElement(fieldId, 'type');
		typeNameElement.text('(' + typeName + ')');
		typeNameElement.attr('title', typeName);
	};

	/**
	 * Применяет изменения к верстке при изменении данных группы полей
	 * @param {JSON} response ответ от сервера при успешном изменении данных группы
	 */
	var applyGroupChanges = function(response) {
		var data = response['data'] || {};

		if (!data.group || !data.group.id) {
			return;
		}
		var group = data.group;
		var groupId = group.id;
		var groupElement = $('div[umigroupid=' + groupId + ']', groupsContainer);

		if (!groupElement.length) {
			return;
		}

		var titleId = '#headg' + groupId + 'title';
		var titleElement = $(titleId, groupsContainer);
		titleElement.text(getEditWindowTitle(group['title'], group['name']));
	};

	/**
	 * Переназначает обработчик события нажатия по элементам
	 * @param {Element|jQuery|String} elements DOM-элементы
	 * @param {Function} handler обработчик события нажатия
	 */
	var rebindClickHandler = function(elements, handler) {
		var $elements = $(elements);

		if (!$elements.length) {
			return;
		}

		$elements.unbind('click');
		$elements.bind('click', handler);
	};

	/**
	 * Вовзвращает содержимое поля типа WYSIWYG
	 * @param {Element|jQuery|String} field исходный элемент поля (например, textarea)
	 * @returns {*}
	 */
	var getWysiwygContent = function(field) {
		if (typeof tinyMCE == 'undefined' || !tinyMCE) {
			return null;
		}

		field = $(field);

		if (!field.length) {
			return null;
		}

		var fieldId = field.attr('id');
		return tinyMCE.get(fieldId) ? tinyMCE.get(fieldId).getContent() : null;
	};

	/**
	 * Возвращает данные сущности
	 * @param {Number} entityId ID сущности
	 * @param {String} entityName имя сущности
	 * @param {Array} entityContainer массив, в котором хранятся данные сущностей
	 * @returns {*}
	 */
	var getEntityData = function(entityId, entityName, entityContainer) {
		return entityContainer[entityId] || processEntityData(function(jsonPath, data) {
			var entitiesList = jsonPath({
				path: '$..' + entityName + '[?(@.id=="' + entityId + '")]',
				json: data
			});

			return entitiesList.length > 0 ? entitiesList[0] : null;
		});
	};

	/**
	 * Возвращает данные поля
	 * @param {Number} fieldId ID поля
	 * @returns {*}
	 */
	var getFieldData = function(fieldId) {
		return getEntityData(fieldId, 'field', self.fields);
	};

	/**
	 * Сохраняет данные сущности
	 * @param {JSON} data данные от сервера при сохранении сущности
	 * @param {String} entityName имя сущности
	 * @param {Array} entityContainer массив, в который нужно сохранить данные сущности
	 */
	var saveEntityData = function(data, entityName, entityContainer) {
		data = data || {};
		if (!data['data'] || !data['data'][entityName]) {
			return;
		}

		var entity = data['data'][entityName]
		entityContainer[entity.id] = entity;
	};

	/**
	 * Сохраняет данные поля
	 * @param {JSON} json данные от сервера при сохранении поля
	 */
	var saveFieldData = function(json) {
		saveEntityData(json, 'field', self.fields);
	};

	/**
	 * Возвращает данные группы полей
	 * @param {Number} groupId ID группы полей
	 * @returns {*}
	 */
	var getGroupData = function(groupId) {
		var groupInfo = getEntityData(groupId, 'group', self.groups);

		if (groupInfo['tip']) {
			groupInfo['tip'] = groupInfo['tip'].replace(/\\"/gi, '"');
			groupInfo['tip'] = groupInfo['tip'].replace(/\\n/gi, '<br />');
		}

		return groupInfo;
	};

	/**
	 * Сохраняет данные группы полей
	 * @param {JSON} json данные от сервера при сохранении группы полей
	 */
	var saveGroupData = function(json) {
		saveEntityData(json, 'group', self.groups);
	};

	/**
	 * Сохранение изменений или добавление группы параметров
	 * @param id ид группы
	 * @param options параметры группы в формате { 'data[%param name%]' : %value%, ... }
	 */
	var saveGroup = function(id, options) {
		var param = options;
		param['csrf'] = csrfProtection.getToken();

		if (id === 'new') {
			$.post("/admin/data/type_group_add/" + currentTypeId + "/do/.json?noredirect=true",
				param,
				function(response) {
					showMessage(getLabel('js-group-creating-success'), getLabel('js-group-creating-title'));
					saveGroupData(response);
					if (response.data.group !== undefined) {
						addGroupToContainer(response.data.group);
						actor.initGroupsSorting();
					}
				},
				'json'
			);
		} else {
			$.post("/admin/data/type_group_edit/" + id + "/" + currentTypeId + "/do/.json?noredirect=true",
				param,
				function(data) {
					applyGroupChanges(data);
					saveGroupData(data);
					showMessage(getLabel('js-group-updating-success'), getLabel('js-group-updating-title'));
				},
				'json'
			);
		}
	};

	/**
	 * Сохраняет изменение существующего поля или добавляет новое поле
	 * @param {String|Integer} id идентификатор существующего поля или ключевое слово "new"
	 * @param {Array} fieldProperties параметры поля
	 * @param {Integer} groupId идентификатор группы поля
	 */
	var saveField = function(id, fieldProperties, groupId) {
		var requestData = fieldProperties;
		requestData['csrf'] = csrfProtection.getToken();

		if (id === 'new') {
			checkSameFieldExists(requestData, groupId);
			return;
		}

		$.post(
			"/admin/data/type_field_edit/" + id + "/" + typeId + "/do/.json?noredirect=true",
			requestData,
			function(data) {
				applyFieldChanges(data);
				saveFieldData(data);
				showMessage(getLabel('js-field-updating-success'), getLabel('js-field-updating-title'));
			},
			"json"
		).error(function(rq, status, err) {
			showMessage(getLabel('js-error-occurred') + ' <br />"' + err + '".', getLabel('js-error-header'));
		});
	};

	/**
	 * Отправляет запрос на проверку данных поля на предмет того, что среди связанных типов данных
	 * уже есть подходящее поле.
	 * Если похожих полей нет - запускает создание поле, иначе предлагает показывает окно с выбором операции
	 * (прикрепить найденное поле или все же создать новое).
	 * @param {Array} requestData параметры поля и запроса
	 * @param {Integer} groupId идентификатор группы поля
	 */
	var checkSameFieldExists = function(requestData, groupId) {
		$.post(
			"/admin/data/getSameFieldFromRelatedTypes/" + typeId + "/.json",
			requestData,
			function (response) {
				if (typeof response.data.fieldId === 'object') {
					return createField(requestData, groupId);
				}

				var foundFieldId = response.data.fieldId;
				var message = response.data.message;

				openDialog('', getLabel('js-label-found-similar-field'), {
					html: message,
					width: 360,
					cancelButton: true,
					confirmText: getLabel('js-label-attach-field'),
					cancelText: getLabel('js-label-create-new-field'),
					customClass: 'modalUp',
					confirmCallback: function(popupName) {
						attachField(foundFieldId, groupId);
						closeDialog(popupName);
					},
					cancelCallback: function (popupName) {
						createField(requestData, groupId);
						closeDialog(popupName);
					}
				});
			},
			'json'
		);
	};

	/**
	 * Отправляет запрос на создание нового поля.
	 * При успешном создании запускает callback.
	 * @param {Array} requestData параметры поля и запроса
	 * @param {Integer} groupId идентификатор группы поля
	 */
	var createField = function(requestData, groupId) {
		// Не прерывать добавление поля в дочерние типы данных, если встретился дочерний тип без группы для поля
		requestData['ignoreChildGroup'] = true;
		$.post(
			"/admin/data/type_field_add/" + groupId + "/" + typeId + "/do/.json?noredirect=true",
			requestData,
			function(data) {
				if (data.data && data.data.error === undefined) {
					saveFieldData(data);
					showMessage(getLabel('js-field-creating-success'), getLabel('js-field-creating-title'));
					drawFieldInsideGroup(groupId, data.data.field);
				} else {
					var errorText = getLabel('js-error-message') + ' "' + data.data.error + '".';
					var errorTitle = getLabel('js-field-creating-error');
					showMessage(errorText, errorTitle);
				}
			},
			'json'
		);
	};

	/**
	 * Отправляет запрос на прикрепление поля к заданной группе в текущем типе.
	 * При успешном создании запускает callback.
	 * @param {Integer} fieldId идентификатор прикрепляемого поля
	 * @param {Integer} groupId идентификатор группы поля
	 */
	var attachField = function (fieldId, groupId) {
		$.post(
			"/admin/data/attachField/" + typeId + "/" + groupId + "/" + fieldId + "/.json",
			{
				'csrf' : csrfProtection.getToken()
			},
			function(data) {
				if (data.data && data.data.error === undefined) {
					saveFieldData(data);
					showMessage(getLabel('js-field-attaching-success'), getLabel('js-field-attaching-title'));
					drawFieldInsideGroup(groupId, data.data.field);
				} else {
					var errorText = getLabel('js-error-message') + ' "' + data.data.error + '".';
					var errorTitle = getLabel('js-field-attaching-error');
					showMessage(errorText, errorTitle);
				}
			},
			'json'
		);
	};

	/** Обработчик события редактирования поля */
	var editFieldHandler = function() {
		var fieldId = $(this).attr('data');
		var fieldInfo = getFieldData(fieldId);
		var fieldTypeId = fieldInfo['field-type-id'];

		if (!fieldInfo) {
			throw 'Can\'t find data for field #' + fieldId;
		}

		if (isLoadGuides(fieldTypeId)) {
			clearLoadedGuidesList();
		}

		var restriction = fieldInfo['restriction'] || {};
		var options = {
			id: fieldId,
			title: fieldInfo['title'] || '',
			typeId: fieldTypeId || '',
			restriction: restriction.id || '',
			guideId: fieldInfo['guide-id'] || '',
			name: fieldInfo['name'] || '',
			tip: fieldInfo['tip'] || '',
			visible: !!fieldInfo['visible'],
			indexable: !!fieldInfo['indexable'],
			required: !!fieldInfo['required'],
			filterable: !!fieldInfo['filterable'],
			isImportant: !!fieldInfo['important'],
			isSystem: !!fieldInfo['system'],
			header: getEditWindowTitle(fieldInfo['title'], fieldInfo['name']),
			saveLabel: getLabel('js-save-button')
		};
		showFieldEditForm(options);
	};

	/** Очищает список загруженных справочников */
	var clearLoadedGuidesList = function() {
		guidesList = [];
	};

	/** Обработчик события редактирования группы полей */
	var editGroupHandler = function() {
		var id = $(this).attr('data');
		var groupInfo = getGroupData(id);
		var title = getEditWindowTitle(groupInfo['title'], groupInfo['name']);

		openDialog('', title, {
			html: getGroupForm(groupInfo),
			cancelButton: true,
			zIndex: 999,
			width: 700,
			confirmText: getLabel('js-save-button'),
			cancelText: getLabel('js-cancel'),
			openCallback: function(scope) {
				initWysiwyg();
				initElements(scope);
			},
			confirmCallback: function(popupName, scope) {
				var tip = getWysiwygContent($('.tip', scope));
				var params = {
					'data[title]': $('#newtitle').val(),
					'data[name]': $('#newname').val(),
					'data[tip]': tip,
					'data[is_visible]': $('#newvisible').is(':checked') ? 1 : 0
				};

				saveGroup(id, params);
				closeDialog(popupName);
			}
		});
	};

	/** Обработчик события удаления группы полей */
	var removeGroupHandler = function(event) {
		var id = $(event.target).parent().attr('data');
		removeGroup(id);
	};

	/**
	 * Рисует интерфейс редактирования поля в заданной группе полей
	 * @param {Integer} groupId идентификатор группы полей
	 * @param {Array} fieldProperties данные поля
	 */
	var drawFieldInsideGroup = function(groupId, fieldProperties) {
		var fieldLineView = _.template($('#field-line-view').html())({
			field: fieldProperties
		});
		fieldLineView = $(fieldLineView);
		fieldLineView.find('a.fremove').bind('click', function(e) {
			var id = $(e.target).parent().attr('data');
			removeField(id);
		});
		rebindClickHandler(fieldLineView.find('a.fedit'), editFieldHandler);
		$('ul[umigroupid=' + groupId + ']').append(fieldLineView);
	};

	var addGroupToContainer = function(_options) {
		if (_options.id === null) return false;

		editableGroupsModel[_options.id] = {
			id: _options.id,
			title: _options.title,
			tip: _options.tip,
			name: _options.name,
			visible: true
		};

		var gid = 'g' + _options.id;
		var groupContainer =
				$("<div class=\"fg_container\">\
					<div class=\"fg_container_header\">\
						<span id='headg" + gid + "title' class='left'>" + _options.title + " [" + _options.name + "]</span>\
						<span id='" + gid + "control'>\
                            <a class=\"gedit\"  data='" + _options.id + "' title='" + getLabel("js-type-edit-edit") + "' ><i class=\"small-ico i-edit\"></i></a>\
                            <a class=\"gremove\"  data='" + _options.id + "' title='" + getLabel("js-type-edit-remove") + "'><i class=\"small-ico i-remove\"></i></a>\
                        </span>\
					</div>\
					<div class=\"fg_container_body content\">\
                        <ul class=\"fg_container\">\
                        <div class='buttons'><a data='" + _options.id + "' class='fadd btn color-blue'>" + getLabel("js-type-edit-add_field") + "</a></div>\
                        </ul>\
                    </div>\
           </div>");
		if (_options.locked) {
			groupContainer.addClass('locked');
		}
		if (!_options.visible) {
			groupContainer.addClass('finvisible');
		}
		$("ul", groupContainer).addBack().attr("umigroupid", _options.id);

		groupsContainer.append(groupContainer);
		rebindClickHandler($('a.gedit'), editGroupHandler);
		rebindClickHandler($('a.gremove'), removeGroupHandler);
		rebindClickHandler($('a.fadd'), function(e) {
			var id = $(e.target).attr('data');
			addField(id);
		});
	};

	var removeGroup = function(id) {
		openDialog(getLabel('js-group-deleting-confirm'), getLabel('js-group-deleting-title'), {
			cancelButton: true,
			confirmText: getLabel('js-delete'),
			cancelText: getLabel('js-cancel'),
			confirmCallback: function(popupName) {
				$.ajax({
					url: "/admin/data/json_delete_group/" + id + "/" + currentTypeId + "/.json",
					dataType:'json',
					success: function () {
						showMessage(getLabel('js-group-deleting-success'));
						closeDialog(popupName);
						$('div[umigroupid=' + id + ']').remove();
					},
					error: function(response) {
						closeDialog(popupName);
						var message = getLabel('js-server_error');

						if (response.status === 403 && response.responseJSON && response.responseJSON.data && response.responseJSON.data.error) {
							message = response.responseJSON.data.error;
						}

						showMessage(message);
					}
				});
			}
		});
	};

	var removeField = function(id) {
		openDialog(getLabel('js-field-deleting-confirm'), getLabel('js-field-deleting-title'), {
			cancelButton: true,
			confirmText: getLabel('js-delete'),
			cancelText: getLabel('js-cancel'),
			confirmCallback: function(popupName) {
				$.ajax({
					url: "/admin/data/json_delete_field/" + id + "/" + currentTypeId + "/.json",
					dataType:'json',
					success: function () {
						showMessage(getLabel('js-field-deleting-success'));
						closeDialog(popupName);
						groupsContainer.find('li[umifieldid=' + id + ']').remove();
					},
					error: function(response) {
						closeDialog(popupName);
						var message = getLabel('js-server_error');

						if (response.status === 403 && response.responseJSON && response.responseJSON.data && response.responseJSON.data.error) {
							message = response.responseJSON.data.error;
						}

						showMessage(message);
					}
				});
			}
		});
	};

	/**
	 * Рисуем окно с формой добавления поля
	 * @param groupId
	 */
	var addField = function(groupId) {
		var stringFieldTypeId = 3;
		var options = {
			id: 'new',
			title: getLabel("js-type-edit-new_field"),
			typeId: stringFieldTypeId,
			typeName: '',
			name: '',
			tip: '',
			groupId: groupId,
			header: getLabel("js-type-edit-new_field"),
			saveLabel: getLabel('js-add-button')
		};
		showFieldEditForm(options);
	};

	/**
	 * Показывает форму редактирования поля
	 * @param {Object} options опции поля и формы
	 * @returns {{init: init, onSort: onSort, initGroupsSorting: initGroupsSorting}}
	 */
	var showFieldEditForm = function(options) {
		$.get('/styles/skins/modern/design/js/html/FieldEditForm.html', function(html) {
			openDialog('', options.header, {
				html: html,
				width: 360,
				cancelButton: true,
				confirmText: options.saveLabel,
				cancelText: getLabel('js-cancel'),
				customClass: 'modalUp',
				confirmCallback: function(popupName, scope) {
					var fieldId = options.id;
					var formData = parseFieldFormData(fieldId, scope);
					saveField(fieldId, formData, options.groupId);
					closeDialog(popupName);
				},
				openCallback: function(scope) {
					var variables = getFieldFormTemplateVariables(options);
					implementFieldFormTemplate(variables, scope);
					initFieldForm(options, scope);
					initElements(scope);
				}
			});
		});
	};

	/**
	 * Разбирает заполненные данные формы
	 * @param {int} fieldId идентификатор поля
	 * @param {jQuery} scope содержимое формы
	 * @returns {Object}
	 */
	var parseFieldFormData = function(fieldId, scope) {
		var formData = {
			'data[title]': $('#newtitle', scope).val(),
			'data[name]': $('#newname', scope).val(),
			'data[tip]': $('#newtip', scope).val(),
			'data[field_type_id]': $('#' + fieldId + 'type', scope).val(),
			'data[restriction_id]': $('#' + fieldId + 'restriction', scope).val(),
			'data[guide_id]': $('#' + fieldId + 'guide', scope).val()
		};
		if ($('#newvisible', scope).is(':checked')) {
			formData['data[is_visible]'] = 1;
		}
		if ($('#newindexable', scope).is(':checked')) {
			formData['data[in_search]'] = 1;
		}
		if ($('#newrequired', scope).is(':checked')) {
			formData['data[is_required]'] = 1;
		}
		if ($('#newfilterable', scope).is(':checked')) {
			formData['data[in_filter]'] = 1;
		}
		if ($('#newIsImportant', scope).is(':checked')) {
			formData['data[is_important]'] = 1;
		}
		if ($('#newsystem', scope).is(':checked')) {
			formData['data[is_system]'] = 1;
		}

		return formData;
	};

	/**
	 * Возвращает переменные шаблоны формы редактирования поля
	 * @param {Object} options опции поля
	 * @returns {{fieldList: *[]}}
	 */
	var getFieldFormTemplateVariables = function(options) {
		return {
			fieldList: [
				{
					id: 'newtitle',
					name: 'title',
					label: getLabel("js-type-edit-title"),
					value: options.title,
					type: 'input'
				},
				{
					id: 'newname',
					name: 'name',
					label: getLabel("js-type-edit-name"),
					value: options.name,
					type: 'input'
				},
				{
					id: 'newtip',
					name: 'tip',
					label: getLabel("js-type-edit-tip"),
					value: options.tip,
					type: 'input'
				},
				{
					id: options.id + 'type',
					name: 'field_type_id',
					label: getLabel("js-type-edit-type"),
					value: options.type,
					type: 'select'
				},
				{
					id: options.id + 'restriction',
					name: 'restriction_id',
					label: getLabel("js-type-edit-restriction"),
					value: options.restriction,
					type: 'select'
				},
				{
					id: options.id + 'guide',
					name: 'guide',
					label: getLabel("js-type-edit-guide"),
					value: options.guide,
					type: 'select'
				},
				{
					id: 'newvisible',
					name: 'is_visible',
					label: getLabel("js-type-edit-visible"),
					value: options.visible,
					type: 'checkbox'
				},
				{
					id: 'newindexable',
					name: 'in_search',
					label: getLabel("js-type-edit-indexable"),
					value: options.indexable,
					type: 'checkbox'
				},
				{
					id: 'newrequired',
					name: 'is_required',
					label: getLabel("js-type-edit-required"),
					value: options.required,
					type: 'checkbox'
				},
				{
					id: 'newfilterable',
					name: 'in_filter',
					label: getLabel("js-type-edit-filterable"),
					value: options.filterable,
					type: 'checkbox'
				},
				{
					id: 'newIsImportant',
					name: 'is_important',
					label: getLabel("js-type-edit-important"),
					value: options.isImportant,
					type: 'checkbox'
				},
				{
					id: 'newsystem',
					name: 'is_system',
					label: getLabel("js-type-edit-system"),
					value: options.isSystem,
					type: 'checkbox'
				}
			]
		};
	};

	/**
	 * Применяет шаблон формы редактирования поля
	 * @param {Object} variables переменные для шаблонизации
	 * @param {jQuery} scope содержимое формы
	 */
	var implementFieldFormTemplate = function(variables, scope) {
		var formContent = _.template($('#field-form', scope).html())(variables);
		$('div.field-form-wrapper', scope).html(formContent);
	};

	/**
	 * Инициализирует поля формы редактирования поля
	 * @param {Object} options опциии поля
	 * @param {jQuery} scope содержимое формы
	 */
	var initFieldForm = function(options, scope) {
		$('#newtype', scope).add('#' + options.id + 'type', scope).change(function() {
			var value = this.value;
			var $guideFieldContainer = jQuery("#" + options.id + "guide", scope).parent();

			if (isLoadGuides(value)) {
				$guideFieldContainer.show("normal", function() {
					loadGuidesInfo(options.id, scope, options.guideId);
				});
			} else {
				$guideFieldContainer.hide();
			}
			loadRestrictionsInfo(options.id, scope, options.restriction);
		});
		loadTypesInfo(options.id, scope, options.typeId);
	};

	/**
	 * Проверяет нужно ли загружать поле выбора справочника
	 * @param {int} typeId идентификатор типа поля
	 * @return {boolean}
	 */
	var isLoadGuides = function(typeId) {
		var objectType = jQuery.grep(typesList, function(o) {
			return o.id == typeId;
		});

		return objectType.length && (objectType[0].dataType == "relation" || objectType[0].dataType == "optioned");
	}

	var loadGuidesInfo = function(id, context, selectedGuideId) {
		var select = jQuery("#" + id + "guide", context);
		var selected = false;
		select.attr("disabled", true);
		if (guidesList.length) {
			var options = "<option value=''></option>";
			for (var i = 0; i < guidesList.length; i++) {
				selected = guidesList[i].id == selectedGuideId;
				options += "<option " + (selected ? 'selected' : '') + " value='" + guidesList[i].id + "' >" + guidesList[i].name + "</option>";
			}
			select.html(options);
			select.attr("disabled", false);
		} else {
			$.post("/udata/system/publicGuidesList/.json", {}, function(data) {
						var items = data.items.item,
								keys = Object.keys(items);
						for (var i = 0; i < keys.length; i++) {
							var itm = items[keys[i]];
							guidesList[guidesList.length] = {
								id: itm.id,
								name: itm.name
							};
						}
						loadGuidesInfo(id, context, selectedGuideId);
					}
					, 'json');
		}
	};

	var loadRestrictionsInfo = function(id, context, selectedId) {
		var select = jQuery("#" + id + "restriction", context);
		var typeId = jQuery("#" + id + "type", context).val();
		select.attr("disabled", true);

		if (!restrictionLoaded) {
			$.post("/udata/data/getRestrictionsList/.json", {}, function(data) {
						var items = data.items.item,
								keys = Object.keys(items);
						for (var i = 0; i < keys.length; i++) {
							var typeId = items[keys[i]]["field-type-id"];
							if (!restrictionsList[typeId]) restrictionsList[typeId] = [];
							restrictionsList[typeId].push(
									{
										id: items[keys[i]].id,
										name: items[keys[i]].name,
										title: items[keys[i]].title
									});
						}
						restrictionLoaded = true;
						loadRestrictionsInfo(id, context, selectedId);
					}
					, 'json');
		} else {
			var options = '<option value="0" selected> </option>';
			var selected = false;
			if (restrictionsList[typeId]) {
				for (var i = 0; i < restrictionsList[typeId].length; i++) {
					selected = restrictionsList[typeId][i].id == selectedId;
					options += "<option " + (selected ? 'selected' : '') + " value='" + restrictionsList[typeId][i].id + "'>" + restrictionsList[typeId][i].title + "</option>";
				}
			}

			select.html(options);
			if (restrictionsList[typeId] && restrictionsList[typeId].length)
				select.attr("disabled", false);
		}

	};

	var loadTypesInfo = function(id, context, typeId) {
		var select = jQuery("#" + id + "type", context);
		var defaultFieldType = 'string';
		var isNew = isNaN(parseInt(typeId));

		if (typesList.length) {
			if (select.get(0).options.length > 1) return;
			var options = '';
			var value = select.prop('value');
			var selected = '';
			for (var i = 0; i < typesList.length; i++) {
				if ((isNew && typesList[i]['dataType'] == defaultFieldType) || typesList[i].id == typeId) {
					selected = 'selected';
				}

				options += "<option data-type=" + typesList[i]['dataType'] + " value='" + typesList[i].id + "' " + selected + ">" + typesList[i].name + "</option>";
				selected = '';
			}
			select.html(options);
			select.attr("disabled", false);
			select.change();
		} else {

			select.attr("disabled", true);
			jQuery.post("/udata/system/fieldTypesList/.json", {}, function(data) {
				var items = data.items.item;
				var sortedItems = _.sortBy(items, 'name');
				var keys = Object.keys(sortedItems);
				var itm;

				for (var i = 0; i < keys.length; i++) {
					itm = sortedItems[keys[i]];
					typesList.push({
						id: itm.id,
						name: itm.name,
						dataType: itm['data-type'],
						multiple: itm['is-multiple'] != null
					});
				}
				loadTypesInfo(id, context, typeId);
			}, 'json');
		}
	};

	var transliterateTitle = function(title) {
		return transliterateRu(title).replace(/\s+/g, "_").replace(/[^A-z0-9_]+/g, "").toLowerCase();
	};

	/**
	 * Callback для транслитерации титульников в имя
	 * @param event
	 */
	var universalTitleConvertCallback = function(event) {
		event.data.nameField.val(transliterateTitle(event.currentTarget.value));
	};

	var getGroupForm = function(option) {
		var tipFieldId = 'newtip_' + Math.round(Math.random() * 100000);

		var str = [
			'<div>',
			'<div class="group-block">',
			'<div class="title-edit">' + getLabel("js-type-edit-title") + '</div>',
			'<input type="text" class="default" id="newtitle" value="' + option.title + '">',
			'</div>',
			'<div class="group-block">',
			'<div class="title-edit">' + getLabel("js-type-edit-name") + '</div>',
			'<input type="text" class="default" id="newname" value="' + option.name + '">',
			'</div>',
			'<div class="group-block">',
			'<div class="title-edit">' + getLabel("js-type-edit-tip") + '</div>',
			'<textarea class="default wysiwyg tip" id="' + tipFieldId + '">' + (option.tip || '') + '</textarea>',
			'</div>',
			'<div class="group-block">',
			'<label><div class="checkbox ' + (option.visible ? 'checked' : '') + '">',
			'<input type="checkbox" id="newvisible" name="data[in_search]" value="1" class="checkbox" ',
			(option.visible ? ' checked="" ' : ''),
			'/>',
			'</div>',
			'<span>' + getLabel("js-type-edit-visible") + '</span></label>',
			'</div>',
			'</div>'].join('');

		str = $(str);

		return str;
	};

	var actor = {

		/** Инициализация объекта. Цепляем всякие интересные события. */
		init: function(id, gmodel) {
			getJSONPathScript();
			var that = this;
			editableGroupsModel = gmodel;
			typeId = id;
			//контейнер групп
			groupsContainer = $('div#groupsContainer > .row:nth-child(2) > div');


			this.initGroupsSorting();

			groupsContainer.sortable({
				items: "div.fg_container:not(:first)",
				update: function(e, ui) {
					var groupId = ui.item.attr("umiGroupId");
					var nextGroupId = ui.item.next("div.fg_container").attr("umiGroupId") || "false";
					jQuery.get("/admin/data/json_move_group_after/" + groupId + "/" + nextGroupId + "/" + typeId + "/");
				}
			});

			//Цепляем события добавления группы
			$('a.add_group').bind('click', function(event) {
				event.preventDefault();
				openDialog('', getLabel("js-type-edit-new_group-title"), {
					html: getGroupForm({
						id: 'new',
						title: getLabel("js-type-edit-new_group"),
						name: '',
						tip: '',
						visible: true
					}),
					width: 750,
					zIndex: 999,
					cancelButton: true,
					confirmText: getLabel('js-add-button'),
					cancelText: getLabel('js-cancel'),
					confirmCallback: function(popupName, scope) {
						var tip = getWysiwygContent($('.tip', scope));

						var params = {
							'data[title]': $('#newtitle').val(),
							'data[name]': $('#newname').val(),
							'data[tip]': tip,
							'data[is_visible]': $('#newvisible').is(':checked') ? 1 : 0
						};

						saveGroup('new', params);
						closeDialog(popupName);
					},
					openCallback: function(scope) {
						initWysiwyg();
						initElements(scope);
					}
				});
			});

			$('a.gedit').bind('click', editGroupHandler);
			$('a.fedit').bind('click', editFieldHandler);
			$('a.gremove').bind('click', removeGroupHandler);

			$('a.fremove').bind('click', function(e) {
				var id = $(e.target).parent().attr('data');
				removeField(id);
			});

			$('a.fadd').bind('click', function(e) {
				var id = $(e.target).attr('data');
				addField(id);
			});

			$('input[name="data[title]"]').focus(function(event) {
				var nameInput = $(event.currentTarget).parent().parent().find('input[name="data[name]"]');
				$(this).bind('keyup', {nameField: nameInput}, function(e) {
					universalTitleConvertCallback(e);
				});

			}).blur(function(event) {
				$(event.currentTarget).unbind('keyup');
			});

		},

		/**
		 * Выполняет инициализацию сортировки
		 * (возможность drag&drop полей внутри группы полей и между ними) полей групп
		 */
		initGroupsSorting: function() {
			var self = this;
			$(".fg_container", groupsContainer).sortable({
				connectWith: "ul.fg_container",
				dropOnEmpty: true,
				items: "li",
				placeholder: "ui-sortable-field-placeholder",
				remove: self.onSort,
				stop: self.onSort
			});
		},

		/**
		 * Обработчик события drag&drop полей
		 * @param {Object} event
		 * @param {Object} ui
		 * @link http://api.jqueryui.com/sortable/
		 * @returns {boolean}
		 */
		onSort: function(event, ui) {
			if (!ui.item || !ui.item.parent() || !ui.item.parent().attr("umiGroupId")) {
				return false;
			}

			var destContainer = ui.item.parent().parent().parent();

			if (destContainer.hasClass('locked')) {
				return false;
			}

			var fieldId = ui.item.attr("umiFieldId");
			var nextFieldId = ui.item.next("li").attr("umiFieldId");
			var isLast = (nextFieldId != undefined) ? "false" : ui.item.parent().attr("umiGroupId");

			jQuery.get("/admin/data/json_move_field_after/" + fieldId + "/" + nextFieldId + "/" + isLast + "/" + typeId + "/");
		}
	};

	return actor;

})(jQuery);
