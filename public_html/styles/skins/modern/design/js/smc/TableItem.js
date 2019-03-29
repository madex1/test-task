/**
 * TableItem
 * Класс осуществляет визуализацию строки таблицы
 * @param {Control} _oControl - экземпляр класса Control
 * @param {TableItem} _oParent - экземпляр класса TableItem, указывающий на прямого предка элемента
 * @param {Object} _oData - информация о элементе
 * @param {TableItem} _oSiblingItem - экземпляр класса TableItem, указывающий на соседа элемента
 * @param {String} _sInsertMode - режим добавление элемента по отношению к соседу _oSiblingItem. Может быть "after" и "before"
 */
var TableItem = function(_oControl, _oParent, _oData, _oSiblingItem, _sInsertMode) {

	/** @type {Number} Идентификатор элемента */
	var _id = parseInt(_oData.id);

	var _self = this;

	/** @type {Object} Источник данных элемента */
	var _data = _oData;

	/** @type {TableItem} Родительский элемент */
	var _parent = _oParent;

	/** @type {TableItem|null} Соседний элемент */
	var _sibling = _oSiblingItem || null;

	/** @type {String} Режим вставки */
	var _insertMode = _sInsertMode || 'after';

	var _forceDrawing = typeof(_oData['force-draw']) !== 'undefined' ? parseInt(_oData['force-draw']) : 1;

	/** @type {Number} Количество дочерних элементов */
	var _childrenCount = typeof(_oData['childs']) !== 'undefined' ? parseInt(_oData['childs']) : 0;

	/** @type {String} Название модуля базового типа данных элемента */
	var _baseModule = (_oData['basetype'] && typeof(_oData['basetype']['module']) === 'string') ? _oData['basetype']['module'] : 'content';

	/** @type {String} Название метода базового типа данных элемента */
	var _baseMethod = (_oData['basetype'] && typeof(_oData['basetype']['method']) === 'string') ? _oData['basetype']['method'] : '';

	/** @type {String} Название базового типа данных элемента */
	var _typeName = (_oData['basetype'] && typeof(_oData['basetype']['_value']) === 'string') ? _oData['basetype']['_value'] : '';

	var _iconsPath = _oControl.iconsPath;

	var _iconSrc = typeof(_oData['iconbase']) !== 'undefined' ? _oData['iconbase'] : _iconsPath + 'ico_' + _baseModule + '_' + _baseMethod + '.png';

	// Контрол сворачивания/разворачивания
	var _toggleControl = null;

	// Контрол поля заголовка
	var _labelControl = null;

	// Заголовок текст
	var _labelText = null;

	// Иконка элемента заголовка
	var _itemIcon = null;

	/** @type {SettingsStore} объект для сохранения пользовательских настроек */
	var _settings = SettingsStore.getInstance();

	// Вроде как разрешение на авто разворачивание элементов дерева
	var _isAutoExpandAllowed = true;

	// старый класс для селекта строки
	var _oldClassName = null;

	// атрибут селекта строки
	var _selected = false;

	// отключение режима дерева
	var _flatMode = _oControl.flatMode;

	var _objectTypesMode = _oControl.objectTypesMode;

	var _pagesBar = null;

	// Строка с фильтрами
	var _filterRow = null;

	var _activeColumn = null;

	/** @var {Boolean} Нужно ли выводить чекбоксы для элементов */
	var _hasCheckbox = typeof _oControl.hasCheckboxes == 'boolean' ? _oControl.hasCheckboxes : true;

	this.checkBox = null;

	// Строка таблицы которую мы сейчас креайтим
	this.element = null;

	// Ссылка на общий объект контроллер Control
	this.control = _oControl;

	// Контейнер для дочернего элемента дерева
	this.childsContainer = null;

	// Поле ид объекта данных
	this.id = _id;

	// поле name объекта данных
	this.name = typeof(_oData['name']) !== 'undefined' ? _oData['name'] : getLabel('js-smc-noname-page');

	// Индикатор корневой объект или нет
	this.isRoot = _parent ? false : true;

	// Атрибут загруженности данных
	this.loaded = false;

	// Ссылка на просмотр страницы|объекта
	this.viewLink = typeof(_oData['link']) !== 'undefined' ? _oData['link'] : false;

	// Ссылка на форму редактирования страницы|объекта
	this.editLink = typeof(_oData['edit-link']) !== 'undefined' ? _oData['edit-link'] : false;

	// Ссылка на форму создания страницы|объекта
	this.createLink = typeof(_oData['create-link']) !== 'undefined' ? _oData['create-link'] : false;

	this.permissions = typeof(_oData['permissions']) !== 'undefined' ? parseInt(_oData['permissions']) : 0;

	this.isActive = typeof(_oData['is-active']) !== 'undefined' ? parseInt(_oData['is-active']) : 0;

	if (_objectTypesMode) {
		this.isActive = true;
	}

	//Атрибут виртуальной копии
	this.isVirtualCopy = typeof(_oData['has-virtual-copy']) !== 'undefined';

	this.isOriginal = typeof(_oData['is-original']) !== 'undefined';

	// Атрибут блокировки
	this.lockedBy = typeof(_oData['locked-by']) === 'object' ? _oData['locked-by'] : null;

	this.expiration = typeof(_oData['expiration']) === 'object' ? _oData['expiration'] : null;

	// ИД шаблона
	this.templateId = typeof(_oData['template-id']) !== 'undefined' ? _oData['template-id'] : null;

	// ИД языка
	this.langId = typeof(_oData['lang-id']) !== 'undefined' ? _oData['lang-id'] : null;

	// Ид домена
	this.domainId = typeof(_oData['domain-id']) !== 'undefined' ? _oData['domain-id'] : null;

	// Поддержка копирования
	this.allowCopy = typeof(_oData['allow-copy']) !== 'undefined' ? parseInt(_oData['allow-copy']) : true;

	// Поддержка управления активностью
	this.allowActivity = typeof(_oData['allow-activity']) !== 'undefined' ? parseInt(_oData['allow-activity']) : true;

	// Поддержка перетаскивания
	this.allowDrag = typeof(_oData['allow-drag']) !== 'undefined' ? _oData['allow-drag'] : true;

	// Ссылка на родителя из спрятанных свойств
	this.parent = _parent;

	// Дети
	this.childs = [];

	// Атрибут наличия детей
	this.hasChilds = (_childrenCount > 0 || _id == 0);

	// Дубль контрола с заголовком строки
	this.labelControl = null;

	// Позиция
	this.position = false;

	// атрибут развернут или нет
	this.isExpanded = false;

	// Фильтр
	this.filter = new filter;

	// Уровень вложенности
	this.level = _parent ? _parent.level + 1 : 0;

	this.ignoreEmptyFilter = false;

	// Количество записей на страницу
	this.pageLimits = this.control.PerPageLimits;

	this.nextSibling = typeof _oData['nextSibling'] == 'object' ? _oData['nextSibling'] : null;

	this.row = null;

	/** @type {Array} список типов полей, которые поддерживают быстрое редактирование */
	this.editableFieldTypeList = [
		'boolean',
		'color',
		'counter',
		'date',
		'domain_id',
		'domain_id_list',
		'file',
		'float',
		'img_file',
		'int',
		'link_to_object_type',
		'name',
		'price',
		'optioned',
		'relation',
		'string',
		'string',
		'swf_file',
		'tags',
		'text',
		'video_file'
	];

	// Данные пагинации
	this.pageing = {
		'total': 0,
		'limit': 0,
		'offset': 0
	};

	this.baseModule = _baseModule;

	this.baseMethod = _baseMethod;

	/**
	 * Конструктор класса
	 * @access private
	 */
	var constructor = function() {
		if (_oParent) {
			_oParent.childs.push(_id);
			if (_forceDrawing) {
				draw(_parent.childsContainer);
			}
		} else {
			if (_forceDrawing) {
				drawRoot();
			}
		}
	};

	/**
	 * Определяет активность элемента табличного контрола в режиме вывода объектов
	 * @param {TableItem} item элемент
	 * @param {Integer|String|Boolean} defaultActivity активность по умолчанию
	 * @returns {Boolean}
	 */
	var isObjectActivated = function(item, defaultActivity) {
		if (!_flatMode || _self.control.contentType === 'pages') {
			return defaultActivity;
		}

		if (!_self.control.enableObjectsActivity) {
			return true;
		}

		var $groupList = $(_self.control.dataSet.getCommonFields());
		var $disabledField = $('field[name = "disabled"]', $groupList);

		if ($disabledField.length > 0) {
			return item.getValue('disabled').length === 0;
		}

		return (defaultActivity
			|| item.getValue('is_activated')
			|| item.getValue('is_active')
			|| item.getValue('activated')) !== '';
	};

	/**
	 * Добавляет колонку в контейнер
	 * @param {HTMLElement} container элемент контейнера
	 * @param {String} size размер добавляемой колонки
	 * @private
	 */
	var appendColumn = function(container, size) {
		var column = document.createElement('col');
		column.style.width = size;
		$(container).append(column);
	};

	/**
	 * Обновляет данные элемента colgroup таблицы
	 * @param {HTMLElement} columnGroups контейнеры обновляемых данных
	 * @private
	 */
	var updateColumnGroup = function(columnGroups) {
		var $columnGroups = $(columnGroups);
		var usedColumns = getUsedColumns();
		var columnsTable = getColumnsTable();

		$columnGroups.html('');

		var nameColumnSize = parseInt(usedColumns['name']['params'][0]);
		appendColumn($columnGroups, nameColumnSize + 'px');
		var columnSize;

		for (var name in usedColumns) {
			if (!columnsTable.hasOwnProperty(name)) {
				continue;
			}

			columnSize = usedColumns[name]['params'][0];
			appendColumn($columnGroups, columnSize);
		}

		appendColumn($columnGroups, '100%');
	};

	/**
	 * "Рисует" элемент
	 * @access private
	 * @param {HTMLElement} _oContainerEl контейнер для добавления элемента
	 */
	var draw = function(_oContainerEl) {
		_self.isActive = isObjectActivated(_self, _self.isActive);

		var usedColumns = getUsedColumns();
		var columnsTable = getColumnsTable();

		var element = document.createElement('tr');
		element.setAttribute('rel', _id);
		element.className = 'table-row tollbar level-' + _self.level;
		element.rel = _id;
		_self.row = element;

		_labelControl = element;
		_self.labelControl = _labelControl;

		var nameData = usedColumns['name'];

		var labelCell = document.createElement('td');
		labelCell.classList.add('table-cell');
		labelCell.id = 'c_' + _self.control.id + '_' + _id + '_name';
		labelCell.width = parseInt(nameData['params'][0]);
		labelCell.name = 'name';

		if (editableCell.enableEdit !== false) {
			var edit = document.createElement('i');
			edit.className = 'small-ico i-change editable';
			edit.title = getLabel('js-table-control-fast-edit');
			edit.id = 'e_' + _self.control.id + '_' + _self.id + '_name';
			labelCell.appendChild(edit);
		}

		var labelWrapper = document.createElement('div');
		labelWrapper.style.owerflow = 'hidden';

		labelCell.appendChild(labelWrapper);

		var cell = null;

		if (!_objectTypesMode) {
			cell = new editableCell(_self, labelCell, nameData, edit);
		}

		if (cell instanceof editableCell && !cell.isActive) {
			$(edit).detach();
		}

		_toggleControl = document.createElement('span');
		_toggleControl.className = 'catalog-toggle';

		var toggleWrapper = document.createElement('span');
		toggleWrapper.classList.add('catalog-toggle-wrapper');
		toggleWrapper.onclick = function() {
			_self.toggle(this);
			return false;
		};

		if (_self.hasChilds) {
			toggleWrapper.appendChild(_toggleControl);
		} else {
			toggleWrapper.className = 'catalog-toggle-off';
		}

		if (!_self.control.flatMode) {
			labelWrapper.appendChild(toggleWrapper);
		}

		var checkWrapper = document.createElement('div');
		checkWrapper.classList.add('checkbox');

		var checkControl = document.createElement('input');
		checkControl.setAttribute('type', 'checkbox');
		checkControl.value = _id;
		checkControl.classList.add('row_selector');
		checkWrapper.appendChild(checkControl);

		var $element = jQuery(element);
		$element.bind('mousedown', function(event) {
			_self.control.handleMouseDown(event, _id);
		});

		$element.bind('mouseup', function(event) {
			_self.control.handleMouseUp(event, _self);
		});

		if (_hasCheckbox) {
			_self.checkBox = checkWrapper;
			labelWrapper.appendChild(checkWrapper);
		}

		_itemIcon = document.createElement('img');
		_itemIcon.style.border = '0px';
		_itemIcon.setAttribute('alt', _typeName);
		_itemIcon.setAttribute('title', _typeName);
		_itemIcon.setAttribute('src', _iconSrc);
		_itemIcon.className = 'ti-icon';
		_itemIcon.onmousedown = function() {
			return false;
		};

		if (_self.control.allowDrag && _self.allowDrag) {
			_itemIcon.style.cursor = 'move';
		}

		if (!_self.control.flatMode && !_self.control.objectTypesMode) {
			labelWrapper.appendChild(_itemIcon);
		} else {
			_itemIcon = null;
		}

		if (_self.expiration) {
			var oStatus = _self.expiration['status'];
			if (oStatus) {
				var statusSID = oStatus['id'];
				var statusName = oStatus['_value'];
				if (statusSID) {
					var expInd = document.createElement('img');
					var ico = _iconsPath + 'ico_' + statusSID + '.png';
					expInd.setAttribute('src', ico);
					expInd.setAttribute('alt', statusName);
					expInd.setAttribute('title', statusName);
					expInd.className = 'page-status';
					labelWrapper.appendChild(expInd);
				}
			}
		}

		_labelText = document.createElement('a');
		var itemName = _self.getValue('name');
		var val = String((typeof(itemName) === 'string' && itemName.length) ? itemName : '');

		if (!val.length) {
			val = getLabel('js-smc-noname-page');
		}

		labelWrapper.title = (_self.viewLink) ? _self.viewLink : val.replace(/<[^>]+>/g, '');
		_labelText.className = 'name_col';
		_labelText.href = _self.editLink;
		_labelText.innerHTML = val;

		if (!_self.editLink) {
			_labelText.className = 'name_col unactive';
		}

		$(_labelText).bind('mousedown click mouseup', function(event) {
			var middleMouseButton = 1;

			if (event.button !== middleMouseButton) {
				event.preventDefault();
			}

			return true;
		});

		labelWrapper.appendChild(_labelText);

		if (typeof _self.editLink === 'string') {
			var editControl = document.createElement('a');
			editControl.classList.add('small-ico');
			editControl.classList.add('i-edit');
			editControl.classList.add('stucktotext');
			editControl.classList.add('editable');
			editControl.setAttribute('href', _self.editLink);
			editControl.setAttribute('title', getLabel('js-goto-edit-page'));
			labelWrapper.appendChild(editControl);
		}

		if (_self.isVirtualCopy) {
			var virtualLabel = document.createElement('span');
			virtualLabel.className = 'label-virtual';
			virtualLabel.innerHTML = (_self.isOriginal) ? getLabel('js-smc-original') : getLabel('js-smc-virtual-copy');
			labelWrapper.appendChild(virtualLabel);
		}

		if (!_self.isActive && !_self.control.objectTypesMode) {
			element.classList.add('disabled');
		}

		element.appendChild(labelCell);

		var colSpan = 2;

		for (var name in usedColumns) {
			var column = columnsTable[name];
			var params = usedColumns[name]['params'];

			if (params[1] === 'static') {
				column = usedColumns[name];
			}
			if (!column) {
				continue;
			}

			colSpan++;

			var col = document.createElement('td');
			col.id = 'c_' + _self.control.id + '_' + _id + '_' + name;
			col.className = 'table-cell';
			col.style.width = params[0];
			col.style.maxWidth = params[0];
			col.name = name;

			val = _self.getValue(name);

			var valueContainer = document.createElement('div');
			valueContainer.style.cursor = 'text';
			valueContainer.style.width = '100%';
			valueContainer.classList.add('cell-item');
			valueContainer.innerHTML = val;

			cell = null;

			if (_self.editableFieldTypeList.indexOf(column.dataType) !== -1 && editableCell.enableEdit !== false) {
				var edit_col = document.createElement('i');
				edit_col.className = 'small-ico i-change editable';
				edit_col.title = getLabel('js-table-control-fast-edit');
				edit_col.id = 'e_' + _self.control.id + '_' + _self.id + '_' + name;
				col.appendChild(edit_col);
				cell = new editableCell(_self, col, column, edit_col);
			}

			col.appendChild(valueContainer);

			if (cell instanceof editableCell && !cell.isActive) {
				$(edit_col).detach();
			}

			if (name === 'is_activated' || name === 'active' || name === 'is_active') {
				_activeColumn = col;
			}

			element.appendChild(col);
		}

		var autoCol = document.createElement('td');
		autoCol.className = 'table-cell';
		autoCol.style.width = '100%';
		element.appendChild(autoCol);

		if (_sibling) {
			var prevEl = null;
			if (_insertMode.toLowerCase() === 'after') {
				prevEl = _sibling.element.nextSibling;
			} else {
				prevEl = _sibling.element;
			}

			if (prevEl) {
				_self.element = _oContainerEl.insertBefore(element, prevEl);
			} else {
				_self.element = _oContainerEl.appendChild(element);
			}
		} else {
			_self.element = _oContainerEl.appendChild(element);
		}

		if (_self.control.dragAllowed && _self.allowDrag) {
			var DropMode = 'child';
			var timeOutHandler;
			var mouseFlag = false;

			jQuery(_labelControl).draggable({
				appendTo: 'body',
				distance: Control.DragSensitivity,
				handle: '.ti-icon, a',
				cursorAt: {right: -2},
				helper: function() {
					var drag_el = document.createElement('div');

					if (_self.control.contentType === 'objects') {
						drag_el.innerHTML = '<div>' + _self.name + '</div>';
						drag_el.className = 'ti-draggable';

						jQuery(drag_el).css({
							'position': 'absolute',
							'background': 'url(' + _iconSrc + ') no-repeat 0 4px',
							'padding-left': '20px'
						});
					} else {
						_self.setSelected(true);
						_self.control.selectedList[_self.id] = _self;

						for (key in _self.control.selectedList) {
							drag_el.innerHTML += '<div>' + _self.control.selectedList[key].name + '</div>';
						}

						drag_el.className = 'ti-draggable';

						jQuery(drag_el).css({
							'position': 'absolute',
							'padding-left': '20px'
						});
					}

					return drag_el;
				},
				start: function() {
					Control.DraggableItem = _self;
					Control.DragMode = true;
					if (_self.control.toolbar) {
						_self.control.toolbar.hide();
					}
				},
				stop: function() {
					if (Control.HandleItem) {
						Control.HandleItem.deInitDroppable();
						if (Control.DraggableItem) {
							Control.DraggableItem.tryMoveTo(Control.HandleItem, DropMode);
						}
					}
					Control.DraggableItem = null;
					Control.DragMode = false;
					jQuery('.pages-bar a').off('mouseover');
					mouseFlag = false;
					window.clearTimeout(timeOutHandler);
				},
				drag: function(event) {
					var $pageBarLink = jQuery('.pages-bar a');

					$pageBarLink.on('mouseover', function() {
						if (!mouseFlag) {
							mouseFlag = true;
							var that = this;
							timeOutHandler = window.setTimeout(function() {
								that.click();
							}, 1000);
						}
					});

					$pageBarLink.on('mouseout', function() {
						if (mouseFlag) {
							mouseFlag = false;
							window.clearTimeout(timeOutHandler);
						}
					});

					var x = event.pageX;
					var y = event.pageY;
					var hItem = Control.detectItemByMousePointer(x, y);
					var oldHItem = Control.HandleItem;
					if (oldHItem) {
						oldHItem.deInitDroppable();
					}

					Control.HandleItem = hItem;
					if (hItem) {
						var cpos = jQuery(hItem.control.initContainer).position();
						// 310, 61, 55 - коэффициенты подобранные вручную для табличного контрола
						var itmDelta = (y - cpos.top - hItem.position.top - 301);

						if (itmDelta > 61) {
							DropMode = 'after';
						} else if (itmDelta > 55) {
							DropMode = 'child';
						} else {
							DropMode = 'before';
						}

						hItem.initDroppable(DropMode);
					}
				}
			});
		}

		var childsRow = document.createElement('tr');
		var childsBar = document.createElement('td');

		if (!_flatMode) {
			colSpan++;
		}

		childsBar.colSpan = colSpan;
		childsRow.appendChild(childsBar);

		var childsBody = document.createElement('table');
		childsBody.classList.add('table');
		childsBody.style.tableLayout = 'fixed';

		var columnGroup = document.createElement('colgroup');
		updateColumnGroup(columnGroup);
		childsBody.appendChild(columnGroup);

		var pagesWrapper = document.createElement('tr');
		pagesWrapper.classList.add('table-row');
		pagesWrapper.classList.add('level-' + _self.level);
		pagesWrapper.style.display = 'none';

		_pagesBar = document.createElement('td');
		_pagesBar.classList.add('pages-bar');
		_pagesBar.classList.add('table-cell');
		_pagesBar.id = 'pb_' + _self.control.id + '_' + _self.id;
		_pagesBar.colSpan = colSpan;

		pagesWrapper.appendChild(_pagesBar);
		childsBar.appendChild(childsBody);
		childsBody.appendChild(pagesWrapper);

		_self.childsContainer = childsBody;

		switch (true) {
			case !element.nextSibling: {
				_oContainerEl.appendChild(childsRow);
				break;
			}
			case _sInsertMode === 'before': {
				element.parentNode.insertBefore(childsRow, element.nextSibling);
				break;
			}
			case _sInsertMode === 'after': {
				element.parentNode.insertBefore(childsRow, element);
				break;
			}
		}
	};

	/**
	 * Пытается отправить запрос на перемещение элемента
	 * @access public
	 * @param {Object} Item - элемент в который (или после которого) пытаемся переместить текущий
	 * @param {Boolean} asSibling - если true, перемещаем после элемента Item, если false, то делаем элемент первым ребенком Item'a
	 * @return False если перемещение невозможно
	 */
	this.tryMoveTo = function(Item, MoveMode) {
		if (Item) {
			var before = this.control.getRootNodeId();
			var rel = Item.id;
			var asSibling = 1;
			var after = '';

			if (this.control.contentType == 'pages') {
				asSibling = MoveMode !== 'child' ? 1 : 0;
			}

			if (MoveMode == 'before') {
				if (this.control.contentType == 'pages') {
					before = Item.id;
					rel = Item.parent.id;
					after = rel;
				} else {
					before = Item.id;
					rel = Item.id;
				}
			}
			if (MoveMode == 'after') {
				if (this.control.contentType == 'pages') {
					var s = Item.getNextSibling();
					rel = Item.parent.id;
					before = s ? s.id : this.control.getRootNodeId();
					after = Item.id;
				} else {
					rel = Item.id;
					after = Item.id;
				}
			}

			if (this.control.contentType == 'objects') {
				after = rel;
			}

			if (Item === this) {
				return false;
			}
			if (Item.checkIsChild(this)) {
				return false;
			}
			if (before == this.id) {
				return false;
			}

			var receiver = Item;

			if (asSibling == 1 && this.control.contentType == 'pages') {
				receiver = Item.parent ? Item.parent : Item.control.getRoot();
			}

			var selectedIds = [];
			var counter = 0;

			for (i in this.control.selectedList) {
				selectedIds[counter++] = i;
			}

			this.control.dataSet.execute('move', {
				'element': this.id,
				'before': before,
				'after': after,
				'rel': rel,
				'as-sibling': asSibling,
				'domain': Item.control.getRoot().name,
				'childs': 1,
				'links': 1,
				'virtuals': 1,
				'permissions': 1,
				'templates': 1,
				'receiver_item': receiver,
				'handle_item': this,
				'viewMode': 'full',
				'moveMode': MoveMode,
				'selected_list': selectedIds
			});
		}
	};

	/**
	 * Пытается подготовить элемент, как контейнер для перемещаемого
	 * @access public
	 * @param {Boolean} asSibling - если true, готовим для перемещения после текущего элемента, если false, то готвоим для перемещения в качестве первого ребенка
	 * @return False ,в вслучае если перемещение в этот элемент не возможно
	 */
	this.initDroppable = function(DropMode) {
		var DropMode = DropMode || 'child';
		var di = Control.DraggableItem;
		var cpos = jQuery(this.control.initContainer).offset();
		if (di) {
			if (di === this) {
				return false;
			}
			if (this.checkIsChild(di)) {
				return false;
			}

			var ind = Control.dropIndicator;

			if (oTable) {
				var table_w = jQuery(oTable.container).width();
				if (table_w < this.position.right) {
					this.position.right = table_w;
				}
			}

			if (DropMode == 'after') {
				ind.style.top = this.position.bottom + cpos.top + 'px';
				ind.style.left = this.position.left + cpos.left + 'px';
				ind.style.width = this.position.right - this.position.left;
			}
			if (DropMode == 'before') {
				ind.style.top = this.position.top + cpos.top + ind.offsetHeight + 'px';
				ind.style.left = this.position.left + cpos.left + 'px';
				ind.style.width = this.position.right - this.position.left + 'px';
			}
			if (DropMode == 'child') {
				ind.style.top = this.position.bottom + cpos.top + 'px';
				ind.style.left = this.position.left + cpos.left + 20 + 'px';
				ind.style.width = this.position.right + cpos.left - 20 - this.position.left + 'px';
			}

			ind.style.display = '';

			setTimeout(autoExpandSelf, 3239);
		}
	};

	/**
	 * Восстанавливает состояние элемента из режима "контейнер для перемещаемого"
	 * @access public
	 */
	this.deInitDroppable = function() {
		if (!Control.dropIndicator) {
			return;
		}
		Control.dropIndicator.style.display = 'none';
	};

	var getUsedColumns = function() {
		if (_self.control.usedColumns) {
			return _self.control.usedColumns;
		}

		var usedColumns = {};

		var setColumns = _settings.get(_self.control.id, 'used-columns');
		if (setColumns === false) {
			setColumns = _self.control.visiblePropsMenu;
			if (setColumns.length == 0) {
				setColumns = _self.control.dataSet.getDefaultFields();
			}
			if (!setColumns) {
				setColumns = '';
			}
		}

		if (setColumns.length) {
			var arrCols = setColumns.split('|');
			for (var i = 0; i < arrCols.length; i++) {
				var info = arrCols[i];
				var colName = arrCols[i];
				var colParams = [];
				var offset = info.indexOf('[');
				if (offset) {
					colName = info.substring(0, offset);
					colParams = info.substring(offset + 1, info.length - 1).split(',');

				}
				usedColumns[colName] = {
					'name': colName,
					'params': colParams
				};
			}
		}

		if (!usedColumns['name']) {
			usedColumns['name'] = {
				'name': 'name',
				'params': ['250px']
			};
		}

		var sequenceProps = _self.control.sequencePropsMenu;

		usedColumns = objectSort(usedColumns, sequenceProps);

		_self.control.usedColumns = usedColumns;

		return usedColumns;
	};

	var objectSort = function(object, keys) {
		var property, sortedObject = {}, i = 0;

		for (property in object) {
			if (!object.hasOwnProperty(keys[i])) {
				delete keys[i];
				keys.splice(i);
			}
			if (object.hasOwnProperty(property)) {
				if (keys.indexOf(property) === -1) {
					keys.push(property);
				}
				sortedObject[keys[i]] = object[keys[i]] || '';
				i++;
			}
		}
		return sortedObject;
	};

	var setUsedColumns = function() {
		var usedColumns = getUsedColumns();
		var cols = [];
		for (name in usedColumns) {
			var col = usedColumns[name];
			if (!col) {
				continue;
			}
			cols[cols.length] = name + '[' + col.params.join(',') + ']';
		}

		_settings.set(_self.control.id, cols.join('|'), 'used-columns');
	};

	/**
	 * Определяет, нужно ли выводить меню полей в табличном контроле
	 * @returns {Boolean}
	 */
	var needMenu = function() {
		var menu = getColumnsMenu();
		return Object.keys(menu).length > 0;
	};

	/**
	 * Возвращает меню со всеми полями, которые можно показать/скрыть в таблице контрола
	 * (Кнопка `+` в заголовке таблицы)
	 * @returns {Object}
	 */
	var getColumnsMenu = function() {
		if (_self.control.columnsMenu) {
			return _self.control.columnsMenu;
		}

		var menu = {};
		$.each(getColumnsTable(), function(key, value) {
			if ($.inArray(value.fieldName, _self.control.requiredPropsMenu) === -1) {
				menu[key] = value;
			}
		});

		_self.control.columnsMenu = menu;
		return menu;
	};

	/**
	 * Устанавливает новое значение меню полей
	 * @param {Object} menu
	 */
	var setColumnsMenu = function(menu) {
		_self.control.columnsMenu = menu;
	};

	/**
	 * Возвращает все колонки табличного контрола
	 * @returns {Object}
	 */
	var getColumnsTable = function() {
		if (_self.control.columnsTable) {
			return _self.control.columnsTable;
		}

		var commonGroups = _self.control.dataSet.getCommonFields();
		var usedColumns = getUsedColumns();

		var num = 1;
		var table = {};

		for (var i = 0; i < commonGroups.length; i++) {
			num++;

			var groupFields = commonGroups[i].getElementsByTagName('field');
			var needSeparator = false;

			for (var j = 0; j < groupFields.length; j++) {
				var field = groupFields[j];
				var name = field.getAttribute('name');
				var type = field.getElementsByTagName('type')[0];
				var dataType = type.getAttribute('data-type');

				if (shouldSkipField(name, dataType)) {
					continue;
				}

				var isUsed = (name in usedColumns);
				var title = field.getAttribute('title');
				var fieldId = field.getAttribute('id');
				var guideId = field.getAttribute('guide-id');

				table[name] = {
					'caption': title,
					'icon': isUsed ? 'checked' : 'undefined',
					'id': fieldId,
					'title': title,
					'fieldName': name,
					'dataType': dataType,
					'guideId': guideId,
					'checked': isUsed,
					'execute': function(item) {
						item.checked ? _self.removeColumn(item.fieldName) : _self.appendColumn(item.fieldName);
						Control.recalcItemsPosition();
					}
				};

				num++;

				if (i < groupFields.length - 1) {
					needSeparator = true;
				}
			}

			if (needSeparator) {
				table[num + '-sep'] = '-';
			}
		}

		_self.control.columnsTable = table;
		return table;

		/**
		 * Определяет, нужно ли пропустить поле и не выводить его в табличном контроле
		 * @param {String} name название поля
		 * @param {String} dataType тип данных поля
		 * @returns {boolean}
		 */
		function shouldSkipField(name, dataType) {
			if ($.inArray(dataType, _self.editableFieldTypeList) === -1) {
				return true;
			}

			var ignoredFieldList = _self.control.dataSet.getFieldsStoplist();
			for (var i = 0; i < ignoredFieldList.length; i++) {
				if (ignoredFieldList[i] == name) {
					return true;
				}
			}

			return false;
		}
	};

	this.resizeColumn = function(fieldName, size) {
		var usedColumns = getUsedColumns();
		var column = usedColumns[fieldName];
		if (!column) {
			return false;
		}

		for (var j = 0; j < _self.childs.length; j++) {
			var ch = _self.control.items[_self.childs[j]];
			if (ch) {
				ch.resizeColumn(fieldName, size);
			}
		}

		if (_self.isRoot) {
			var el = document.getElementById('h_' + _self.control.id + '_' + fieldName);
			if (!el) {
				return false;
			}
			el.style.width = size + 'px';
			el.style.maxWidth = size + 'px';

			usedColumns[fieldName].params[0] = size + 'px';
			_self.control.usedColumns = usedColumns;
			setUsedColumns();

		} else {
			var el = document.getElementById('c_' + _self.control.id + '_' + _self.id + '_' + fieldName);
			if (!el) {
				return false;
			}

			el.style.width = size + 'px';
			el.style.maxWidth = size + 'px';
		}

		var $colGroups = $('colgroup', _self.control.initContainer);
		updateColumnGroup($colGroups);

		return true;
	};

	/**
	 * Обработчик события изменения размера колонки таблицы
	 * @param {String} fieldName имя поля, размер которого изменяется
	 * @param {Object} event
	 */
	this.startResizeColumn = function(fieldName, event) {
		var $floatResizing = $(document.createElement('div'));
		var $tableContainer = $(this.control.initContainer);
		var containerOffset = $tableContainer.offset();

		$floatResizing.addClass('resizer');

		$floatResizing.css({
			top: containerOffset.top,
			left: event.clientX,
			height: $tableContainer.outerHeight() + 'px'
		});

		/** Отключает выделение текста */
		var disableSelection = function() {
			try {
				document.body.style['-moz-user-select'] = 'none';
				document.body.style['-khtml-user-select'] = 'none';
				document.body.onselectstart = function() {
					return false;
				};
				document.selection.empty();
			} catch (err) {
			}
		};

		/**
		 * Обработчик события изменения размера
		 * @param {Object} event
		 */
		var onResize = function(event) {
			disableSelection();
			$floatResizing.css('left', event.clientX);
		};

		/** Обработчик события окончания изменения размера */
		var onStopResize = function() {
			$(document).unbind('mouseup');
			$(document).unbind('mousemove');

			var $columnElement = $('#h_' + _self.control.id + '_' + fieldName);
			var newColumnSize = $floatResizing.position().left - $columnElement.offset().left;

			newColumnSize = Math.min(newColumnSize, TableItem.maxColumnWidth);
			newColumnSize = Math.max(newColumnSize, TableItem.minColumnWidth);

			var oldColumnSize = $columnElement.outerWidth();

			if (newColumnSize !== oldColumnSize) {
				_self.resizeColumn(fieldName, newColumnSize);
				Control.recalcItemsPosition();
			}

			$floatResizing.detach();
		};

		$(document).bind('mouseup', onStopResize);
		$(document).bind('mousemove', onResize);

		$('body').append($floatResizing);
	};

	/**
	 * Добавляет колонку в таблицу
	 * @param {String} fieldName название поля
	 * @returns {Boolean}
	 */
	this.appendColumn = function(fieldName) {
		var usedColumns = getUsedColumns();
		var columnsMenu = getColumnsMenu();
		var column = columnsMenu[fieldName];
		if (!column || usedColumns[fieldName]) {
			return false;
		}

		for (var j = 0; j < _self.childs.length; j++) {
			var ch = _self.control.items[_self.childs[j]];
			if (ch) {
				ch.appendColumn(fieldName);
			}
		}

		if (_self.isRoot) {
			var col = document.createElement('th');
			col.className = 'table-cell';
			col.setAttribute('id', 'h_' + _self.control.id + '_' + fieldName);
			col.setAttribute('name', name);
			col.name = fieldName;
			col.style.width = '200px';

			var resizer = document.createElement('span');
			resizer.onmousedown = function(event) {
				if (!event) {
					event = window.event;
				}
				_self.startResizeColumn(fieldName, event);
			};

			col.appendChild(resizer);

			var header = document.createElement('div');
			header.classList.add('table-title');
			header.title = column.title;
			header.innerHTML = column.title;
			col.appendChild(header);

			_self.element.insertBefore(col, _self.element.lastChild);

			usedColumns[fieldName] = {
				'name': fieldName,
				'params': ['200px']
			};
			_self.control.usedColumns = usedColumns;

			columnsMenu[fieldName].checked = true;
			columnsMenu[fieldName].icon = 'checked';
			setColumnsMenu(columnsMenu);

			var colFltr = document.createElement('div');
			colFltr.setAttribute('id', 'f_' + _self.control.id + '_' + fieldName);
			colFltr.style.width = '100%';
			_self.control.onDrawFieldFilter(columnsMenu[fieldName], colFltr, usedColumns[fieldName].params);
			col.appendChild(colFltr);

			var pgBar = document.getElementById('pb_' + _self.control.id + '_' + _self.id);
			if (pgBar) {
				pgBar.colSpan += 1;
			}

			setUsedColumns();
			toggleFilterRow();
		} else {
			var col = document.createElement('td');
			col.id = 'c_' + _self.control.id + '_' + _self.id + '_' + fieldName;
			col.style.width = '280px';
			col.style.maxWidth = '200px';
			col.className = 'table-cell';
			col.name = fieldName;

			if (document.getElementById(col.id)) {
				return true;
			}

			var val = _self.getValue(fieldName);

			_self.element.insertBefore(col, _self.element.lastChild);
			_self.childsContainer.parentNode.colSpan += 1;

			var edit_col = document.createElement('i');
			edit_col.className = 'small-ico i-change editable';
			edit_col.title = getLabel('js-table-control-fast-edit');
			edit_col.id = 'e_' + _self.control.id + '_' + _self.id + '_' + fieldName;
			col.appendChild(edit_col);

			var content_col = document.createElement('div');
			content_col.style.cursor = 'text';
			content_col.style.width = '100%';
			content_col.classList.add('cell-item');
			content_col.innerHTML = val;

			col.appendChild(content_col);

			var cell = new editableCell(_self, col, column, edit_col);

			if (cell instanceof editableCell && !cell.isActive) {
				$(edit_col).detach();
			}
		}

		var $colGroups = $('colgroup', _self.control.initContainer);
		updateColumnGroup($colGroups);

		return true;
	};

	/**
	 * Убирает колонку из таблицы
	 * @param {String} fieldName название поля
	 * @returns {Boolean}
	 */
	this.removeColumn = function(fieldName) {
		var usedColumns = getUsedColumns();
		var columnsMenu = getColumnsMenu();
		var column = columnsMenu[fieldName];
		if (!column || !usedColumns[fieldName]) {
			return false;
		}

		for (var j = 0; j < _self.childs.length; j++) {
			var ch = _self.control.items[_self.childs[j]];
			if (ch) {
				ch.removeColumn(fieldName);
			}
		}

		if (_self.isRoot) {
			var el = document.getElementById('h_' + _self.control.id + '_' + fieldName);
			if (!el) {
				return false;
			}
			el.parentNode.removeChild(el);

			var fCell = document.getElementById('f_' + _self.control.id + '_' + fieldName);
			if (fCell) {
				fCell.parentNode.removeChild(fCell);
			}

			var usedCols = {};
			for (var name in usedColumns) {
				if (name == fieldName) {
					continue;
				}
				usedCols[name] = usedColumns[name];
			}
			_self.control.usedColumns = usedCols;

			columnsMenu[fieldName].checked = false;
			columnsMenu[fieldName].icon = 'undefined';
			setColumnsMenu(columnsMenu);

			_self.control.onRemoveColumn(column);

			_self.childsContainer.parentNode.parentNode.colSpan -= 1;
			var pgBar = document.getElementById('pb_' + _self.control.id + '_' + _self.id);
			if (pgBar) {
				pgBar.colSpan -= 1;
			}

			setUsedColumns();
			toggleFilterRow();
		} else {
			var el = document.getElementById('c_' + _self.control.id + '_' + _self.id + '_' + fieldName);
			if (!el) {
				return false;
			}
			el.parentNode.removeChild(el);
			_self.childsContainer.parentNode.parentNode.colSpan -= 1;
		}

		updateColumnGroup($('colgroup', _self.control.initContainer));

		return true;
	};

	/**
	 * Обработчик события выполнения экспорта списка в CSV
	 * @param {Array} relIdList список идентификаторов связанных сущностей, которые могут влиять на результат экспорта
	 */
	this.exportCallback = function(relIdList) {

		/**
		 * Запрашивает скачивание экспортированного файла
		 * @param {Array} relIdList список идентификаторов связанных сущностей, которые могут влиять на результат экспорта
		 * @param {String} encoding кодировка csv файла
		 * @param {String} popupName имя всплывающего окна
		 */
		var requestDownload = function(relIdList, encoding, popupName) {
			closeDialog(popupName);
			var request = _self.getExportLink(relIdList, encoding);
			window.location = request + '&download=1';
		};

		/**
		 * Запрашивает экспорт в csv файл по частям
		 * @param {Array} relIdList список идентификаторов связанных сущностей, которые могут влиять на результат экспорта
		 * @param {String} encoding кодировка csv файла
		 * @param {String} popupName имя всплывающего окна
		 * @param {Function} callback имя обработчика завершения экспорта
		 */
		var requestExport = function(relIdList, encoding, popupName, callback) {
			var request = _self.getExportLink(relIdList, encoding);

			$.ajax({
				url: request,
				dataType: 'json',
				method: 'get'
			}).done(function(response) {
				if (typeof response.is_complete === 'boolean') {
					if (response.is_complete === true) {
						return callback();
					}

					return requestExport(relIdList, encoding, popupName, callback);
				}

				handleError(popupName, 'Unknown');
			}).fail(function(jqXHR, textStatus) {
				handleError(popupName, textStatus);
			});
		};

		/** Показывает диалоговое окно с выбором параметров csv экспорта */
		$.get('/styles/skins/modern/design/js/smc/html/quickExportDialog.html', function(html) {
			html = html.replace(/{{label:(.+)}}/gi, function(match, label) {
				return getLabel(label);
			});

			openDialog('', getLabel('js-csv-export'), {
				confirmText: getLabel('js-csv-export-button'),
				html: html,

				confirmCallback: function(popupName) {
					var $popup = $('#popupLayer_' + popupName);

					selectEncoding(function(encoding) {
						$('#quick_csv_encoding', $popup).hide();
						$('div.eip_buttons', $popup).hide();
						$('div.exchange_container', $popup).show();
						requestExport(relIdList, encoding, popupName, function() {
							requestDownload(relIdList, encoding, popupName);
						});
					});
				},

				openCallback: function() {
					initSelectizer();
				}
			});
		});
	};

	/**
	 * Обработчик события выполнения импорта списка из CSV
	 * @param {Number|null} entityId идентификатор связанной сущности, которая может влиять на результат импорта
	 */
	this.importCallback = function(entityId) {
		entityId = (typeof entityId === 'number') ? entityId : null;

		/**
		 * Запрашивает скачивание экспортированного файла
		 * @param {Number|null} entityId идентификатор связанной сущности, которая может влиять на результат импорта
		 * @param {String} encoding кодировка csv файла
		 * @param {String} popupName имя всплывающего окна
		 * @param {Function} callback имя обработчика завершения экспорта
		 */
		var requestUpload = function(entityId, encoding, popupName, callback) {
			var request = _self.getImportLink(entityId, encoding);
			var $popup = $('#popupLayer_' + popupName);
			var $form = $('#import-csv-form', $popup);

			var formData = new FormData;
			formData.append('csv-file', $('input[name = "csv-file"]', $form).prop('files')[0]);
			formData.append('csrf', csrfProtection.getToken());
			formData.append('upload', 1);

			$.ajax({
				url: request,
				dataType: 'json',
				method: 'post',
				processData: false,
				contentType: false,
				data: formData
			}).done(function(response) {
				if (typeof response.error === 'string') {
					return handleError(popupName, response.error);
				}

				if (typeof response.file === 'string') {
					return callback(response.file, request, popupName);
				}

				handleError(popupName, 'Unknown');
			}).fail(function(jqXHR, textStatus) {
				handleError(popupName, textStatus);
			});
		};

		/**
		 * Запрашивает импорт csv файла по частям
		 * @param {String} file путь до импортированного файла
		 * @param {String} request запрос
		 * @param {String} popupName имя всплывающего окна
		 */
		var requestImport = function(file, request, popupName) {
			var formData = new FormData;
			formData.append('file', file);
			formData.append('csrf', csrfProtection.getToken());

			$.ajax({
				url: request,
				dataType: 'json',
				method: 'post',
				processData: false,
				contentType: false,
				data: formData
			}).done(function(response) {
				if (typeof response.error === 'string') {
					return handleError(popupName, response.error);
				}

				if (typeof response.is_complete === 'boolean') {
					if (response.is_complete === true) {
						return window.location.reload();
					}

					return requestImport(file, request, popupName);
				}

				handleError(popupName, 'Unknown');
			}).fail(function(jqXHR, textStatus) {
				handleError(popupName, textStatus);
			});
		};

		/** Показывает диалоговое окно с выбором параметров csv импорта */
		$.get('/styles/skins/modern/design/js/smc/html/quickImportDialog.html', function(html) {
			html = html.replace(/{{label:(.+)}}/gi, function(match, label) {
				return getLabel(label);
			});

			openDialog('', getLabel('js-csv-import'), {
				confirmText: getLabel('js-csv-import-button'),
				html: html,

				confirmCallback: function(popupName) {
					var $popup = $('#popupLayer_' + popupName);

					selectEncoding(function(encoding) {
						$('#import-csv-form', $popup).hide();
						$('div.eip_buttons', $popup).hide();
						$('div.exchange_container', $popup).show();
						requestUpload(entityId, encoding, popupName, requestImport);
					});
				},

				openCallback: function() {
					initSelectizer();
				}
			});
		});
	};

	/**
	 * Выбирает кодировку и выполняет обработчик
	 * @param {Function} callback обработчик
	 */
	var selectEncoding = function(callback) {
		var selectElement = document.querySelector('select[name=encoding]');
		var selectedEncoding = null;

		if (selectElement && typeof selectElement.selectedIndex === 'number') {
			selectedEncoding = selectElement.item(selectElement.selectedIndex).text;
		}

		callback(selectedEncoding);
	};

	/**
	 * Обрабатывает сообщение об ошибке
	 * @param {String} popupName имя всплывающего окна
	 * @param {String} message сообщение
	 */
	var handleError = function(popupName, message) {
		closeDialog(popupName);
		$.jGrowl(message, {header: getLabel('js-error-header')});
	};

	var toggleFilterRow = function() {
		var usedColumns = getUsedColumns();
		var columnsTable = getColumnsTable();

		var i = 0;
		for (var name in usedColumns) {
			var column = columnsTable[name];
			var params = usedColumns[name]['params'];

			if (params[1] == 'static') {
				column = usedColumns[name];
				column.title = getLabel('js-smc-' + name);
			}

			if (!column) {
				continue;
			}
			i++;
		}

		if (_filterRow) {
			_filterRow.style.display = (i > 0) ? '' : 'none';
		}
	};

	var resizeHandler = function(fieldName) {
		return function(event) {
			if (!event) {
				event = window.event;
			}
			_self.startResizeColumn(fieldName, event);
			return true;
		};
	};

	/**
	 * Рисует корневой элемент
	 * @private
	 */
	var drawRoot = function() {
		initDropIndicator();

		var usedColumns = getUsedColumns();
		var columnsTable = getColumnsTable();
		var usedColumnsCount = 0;

		var tHead = document.createElement('tr');
		tHead.setAttribute('name', _self.control.id);
		tHead.className = 'table-row title';

		var nameData = usedColumns['name'],
			nameCell = document.createElement('th');

		nameCell.className = 'table-cell';
		nameCell.id = 'h_' + _self.control.id + '_name';
		nameCell.setAttribute('name', 'name');
		nameCell.name = name;
		nameCell.width = parseInt(nameData['params'][0]);

		var header = document.createElement('div');
		header.classList.add('table-title');
		header.classList.add('disabled');

		var firstColumnTitle = getLabel('js-smc-name-column');
		if (_self.control.labelFirstColumn.length > 0) {
			firstColumnTitle = _self.control.labelFirstColumn;
		}

		header.innerHTML = firstColumnTitle;
		header.title = firstColumnTitle;

		var resizer = document.createElement('span');
		resizer.onmousedown = resizeHandler('name');

		nameCell.appendChild(resizer);
		nameCell.appendChild(header);

		if (_self.control.enableEdit) {
			var inputSearch = document.createElement('div');
			inputSearch.className = 'input-search';
			inputSearch.id = 'f_' + _self.control.id + '_name';
			_self.control.onDrawFieldFilter('name', inputSearch, nameData.params);
			nameCell.appendChild(inputSearch);
		}

		tHead.appendChild(nameCell);

		var colSpan = 2;

		for (var name in usedColumns) {
			var column = columnsTable[name];
			var params = usedColumns[name]['params'];
			if (params[1] == 'static') {
				column = usedColumns[name];
				column.title = getLabel('js-smc-' + name);
			}
			if (!column) {
				continue;
			}

			colSpan++;

			var col = document.createElement('th');
			col.classList.add('table-cell');
			col.id = 'h_' + _self.control.id + '_' + name;
			col.setAttribute('name', name);
			col.style.width = params[0];

			header = document.createElement('div');
			header.classList.add('table-title');
			header.classList.add('disabled');
			header.title = column.title;
			header.innerHTML = column.title;

			col.appendChild(header);

			var resizer = document.createElement('span');
			resizer.onmousedown = resizeHandler(name);
			col.appendChild(resizer);

			var filterField = document.createElement('div');
			filterField.id = 'f_' + _self.control.id + '_' + name;
			filterField.style.width = '100%';
			_self.control.onDrawFieldFilter(column, filterField, params);

			col.appendChild(filterField);

			tHead.appendChild(col);

			++usedColumnsCount;
		}

		var autocol = document.createElement('th');
		autocol.classList.add('table-cell');
		autocol.classList.add('plus');
		autocol.style.width = '100%';
		autocol.style.textAlign = 'left';
		autocol.style.verticalAlign = 'middle';

		if (!_self.control.objectTypesMode && needMenu()) {
			var invokeBtn = document.createElement('i');
			invokeBtn.classList.add('small-ico');
			invokeBtn.classList.add('i-add');
			invokeBtn.classList.add('pointer');
			jQuery(invokeBtn).bind('click', function(event) {
				jQuery.cmenu.hideAll();
				jQuery.cmenu.lockHiding = true;
				jQuery.cmenu.show(
					jQuery.cmenu.getMenu(getColumnsMenu()),
					_self.control.initContainer.offsetParent,
					event
				);
			});

			jQuery(invokeBtn).bind('mouseout', function() {
				jQuery.cmenu.lockHiding = false;
			});
			autocol.oncontextmenu = function() {
				return false;
			};
			autocol.appendChild(invokeBtn);
		}

		tHead.appendChild(autocol);

		jQuery(tHead).bind('click', function(event) {
			var el = event.target;

			if (el !== autocol) {
				TableItem.orderByColumn(el.parentNode.getAttribute('name'), _self.control, el);
			}
		});

		toggleFilterRow();

		var pagesWrapper = document.createElement('tBody');
		pagesWrapper.classList.add('level-' + _self.level);
		pagesWrapper.style.display = 'none';

		var pagesRow = document.createElement('tr');
		pagesRow.style.display = 'none';
		pagesRow.classList.add('table-row');

		_pagesBar = document.createElement('td');
		_pagesBar.classList.add('pages-bar');
		_pagesBar.classList.add('table-cell');
		_pagesBar.id = 'pb_' + _self.control.id + '_' + _self.id;
		if (!_flatMode) {
			colSpan++;
		}
		_pagesBar.colSpan = colSpan;

		pagesRow.appendChild(_pagesBar);

		pagesWrapper.appendChild(pagesRow);

		_self.element = _self.control.container.appendChild(tHead);
		_self.childsContainer = _self.control.container.appendChild(pagesWrapper);
		_self.control.initContainer = _self.control.container;
		_self.control.container = _self.childsContainer;

		if (_flatMode && !_objectTypesMode) {
			_self.showCsvButtons(_self.exportCallback, _self.importCallback);
		}

		_self.expand();

		/** Инициализирует индикатор перемещения элементов */
		function initDropIndicator() {
			if (!Control.dropIndicator) {
				var dropIndicator = document.createElement('div');
				dropIndicator.className = 'ti-drop';
				Control.dropIndicator = document.body.appendChild(dropIndicator);
			}
		}
	};

	/**
	 * Отображает кнопки обмена данными в формате csv
	 * @param {Function} exportCallback обработчик нажатия кнопки экспорта
	 * @param {Function} importCallback обработчик нажатия кнопки импорта
	 */
	this.showCsvButtons = function(exportCallback, importCallback) {
		if (this.control.disableCSVButtons) {
			return;
		}
		_self.hideCsvButtons();

		var csvButtons = document.createElement('div');
		csvButtons.id = 'csv-buttons';

		var aExportCsv = document.createElement('a');
		var exImg = document.createElement('i');
		exImg.className = 'small-ico i-csv-export';
		aExportCsv.appendChild(exImg);
		aExportCsv.appendChild(document.createTextNode(getLabel('js-csv-export')));
		aExportCsv.href = '#';
		aExportCsv.className = 'csvLink csvExport btn-action';

		aExportCsv.onclick = exportCallback;
		csvButtons.appendChild(aExportCsv);

		if (!this.control.hideCsvImportButton) {
			var aImportCsv = document.createElement('a');
			var imImg = document.createElement('i');
			imImg.className = 'small-ico i-csv-import';
			aImportCsv.appendChild(imImg);
			aImportCsv.appendChild(document.createTextNode(getLabel('js-csv-import')));
			aImportCsv.href = '#';
			aImportCsv.className = 'csvLink csvImport btn-action';

			aImportCsv.onclick = importCallback;
			csvButtons.appendChild(aImportCsv);
		}

		var parent = document.getElementById('csv-buttons-zone');
		parent.appendChild(csvButtons);
		_self.csvButtons = csvButtons;
	};

	/** Скрывает кнопки обмена данными в формате csv */
	this.hideCsvButtons = function() {
		if (_self.csvButtons) {
			jQuery(_self.csvButtons).remove();
			_self.csvButtons = false;
		}

		jQuery('#csv-buttons-zone').html('');
	};

	/**
	 * Возвращает ссылку на адрес бекенда, который отвечает за быстрый csv экспорт
	 * @param {Array} relIdList список идентификаторов связанных сущностей, которые могут влиять на результат экспорта
	 * @param {String} encoding кодировка csv файла
	 * @returns {String}
	 */
	this.getExportLink = function(relIdList, encoding) {
		var usedColumns = getUsedColumns();
		var filterQueryString = this.control.getCurrentFilter().getQueryString();

		var path = document.location['pathname'];
		try { // Обработка вариантов, в которых document.location не совпадает с заданным адресом
			var cleanPath = _self.control.dataSet.getPathModuleMethod();
		} catch (err) {
		}

		if (path !== cleanPath && path.indexOf(cleanPath) === 0) {
			var pathDifference = path.substring(cleanPath.length);
			var expr = new RegExp('/([\/0-9]*)/');
			path = (expr.test(pathDifference)) ? path : cleanPath;
		} else {
			path = cleanPath;
		}

		var link = path + filterQueryString + '&xmlMode=force&export=csv';
		for (var i in usedColumns) {
			if (!usedColumns.hasOwnProperty(i)) {
				continue;
			}

			var columnName = usedColumns[i]['name'];

			if (columnName !== 'name') {
				link += '&used-fields[]=' + columnName;
			}
		}

		if (Array.isArray(relIdList)) {
			relIdList.forEach(function(relId) {
				link += '&rel[]=' + relId;
			});
		}

		link += '&hierarchy-level=100';

		var filter = this.control.dataSet.getDefaultFilter();

		if (filter.Langs[0]) {
			link += '&lang_id[]=' + filter.Langs[0];
		}

		if (filter.Domains[0]) {
			link += '&domain_id[]=' + filter.Domains[0];
		}

		if (typeof encoding === 'string' && encoding.length > 0) {
			link += '&encoding=' + encoding;
		}

		return link;
	};

	/**
	 * Линк на импорт
	 * @param elementId
	 * @param encoding
	 * @returns {*}
	 */
	this.getImportLink = function(elementId, encoding) {
		var filterQueryString = this.control.getCurrentFilter().getQueryString();
		var link = document.location['pathname'];
		link += filterQueryString + '&xmlMode=force&import=csv';

		if (elementId) {
			link += '&rel[]=' + elementId;
		}

		if (typeof encoding === 'string' && encoding.length > 0) {
			link += '&encoding=' + encoding;
		}

		return link;
	};

	/**
	 * Разворачивает элемент, если он находится под курсором
	 * Используется в режиме drag&drop
	 * @access private
	 */
	var autoExpandSelf = function() {
		if (_isAutoExpandAllowed && _self === Control.HandleItem) {
			_self.expand();
		}
	};

	this.draw = function() {
		if (_oParent) {
			if (!_forceDrawing) {
				draw(_parent.childsContainer);
			}
		} else {
			if (!_forceDrawing) {
				drawRoot();
			}
		}
	};

	/**
	 * Добавляет дочерний элемент последним в списке
	 * @access public
	 * @param {Array} _oChildData - массив с информацией о новом элементе
	 * @return {Object} - новый элемент
	 */
	this.appendChild = function(_oChildData) {
		return new TableItem(this.control, _self, _oChildData);
	};

	/**
	 * Добавляет дочерний элемент после указанного элемента
	 * @access public
	 * @param {Array} _oChildData - массив с информацией о новом элементе
	 * @param {Object} oItem - элемент, после которого добавится новый
	 * @return {Object} - новый элемент
	 */
	this.appendAfter = function(_oChildData, oItem) {
		return new TableItem(this.control, _self, _oChildData, oItem, 'after');
	};

	/**
	 * Добавляет дочерний элемент перед указанным элементом
	 * @access public
	 * @param {Array} _oChildData - массив с информацией о новом элементе
	 * @param {Object} oItem - элемент, перед которым добавится новый
	 * @return {Object} - новый элемент
	 */
	this.appendBefore = function(_oChildData, oItem) {
		return new TableItem(this.control, _self, _oChildData, oItem, 'before');
	};

	/**
	 * Добавляет дочерний элемент в начало списка
	 * @access public
	 * @param {Array} _oChildData - массив с информацией о новом элементе
	 * @return {Object} - новый элемент
	 */
	this.appendFirst = function(_oChildData) {
		if (this.childsContainer.childNodes.length == 2) {
			return this.appendChild(_oChildData);
		} else if (typeof(this.childsContainer.childNodes[2].rel) != 'undefined') {
			return this.appendBefore(_oChildData, this.control.getItem(this.childsContainer.childNodes[1].rel));
		} else {
			return false;
		}
	};

	/**
	 * Возвращает предыдущего соседа элемента
	 * @access public
	 * @return {Object} предыдущий сосед, либо null
	 */
	this.getPreviousSibling = function() {
		var prevEl = this.element.previousSibling;
		if (prevEl && prevEl.rel) {
			return this.control.getItem(prevEl.rel);
		}
		return null;
	};

	/**
	 * Возвращает последующего соседа элемента
	 * @access public
	 * @return {Object} последующий сосед, либо null
	 */
	this.getNextSibling = function() {
		var prevEl = this.element.nextSibling;
		if (prevEl && prevEl.rel) {
			return this.control.getItem(prevEl.rel);
		}
		return null;
	};

	/**
	 * Удаляет элемент из DOM
	 * @access public
	 */
	this.clear = function() {
		if (this.isRoot) {
			var parent = this.element.parentNode;
			while (parent.firstChild) {
				parent.removeChild(parent.firstChild);
			}
		} else if (this.element.parentNode) {
			this.element.parentNode.removeChild(this.element.nextSibling);
			this.element.parentNode.removeChild(this.element);
		}
	};

	/**
	 * Проверяет, является ли текущий элемент потомком указанного (на всю глубину)
	 * @access public
	 * @param {Object} oItem - элемент
	 * @return {Boolean} true, если является
	 */
	this.checkIsChild = function(oItem) {
		var parent = this.parent;
		while (parent) {
			if (oItem === parent) {
				return true;
			}
			parent = parent.parent;
		}
		return false;
	};

	/**
	 * Возвращает координаты DOM-представления элемента
	 * Метод является обязательным, вызывается Control'ом.
	 * Служит для определения элемента под курсором мыши
	 * @access public
	 */
	this.recalcPosition = function() {
		if (_self.isRoot) {
			return false;
		}
		try {
			var parent = this.parent;
			while (parent) {
				if (!parent.isExpanded) {
					this.position = false;
					return false;
				}
				parent = parent.parent;
			}

			var container = this.control.getRoot().childsContainer;
			var pos = jQuery(_labelControl).position();

			this.position = {
				'left': pos.left,
				'top': pos.top,
				'right': pos.left + container.offsetWidth,
				'bottom': pos.top + jQuery(_labelControl).height()
			};

			return this.position;
		} catch (e) {
			this.position = false;
			return false;
		}
	};

	/**
	 * Выставляет статус загружены/не загружены дети элемента
	 * Метод является обязательным, вызывается Control'ом!
	 * @param {Boolean} loaded - статус
	 * @access public
	 */
	this.setLoaded = function(loaded) {
		this.loaded = loaded;
		if (_toggleControl) {
			_toggleControl.setAttribute('src', _iconsPath + 'collapse.png');
		}
	};

	/**
	 * Возвращает строковое значение поля для помещения его в колонку табличного контрола
	 * @param {Object} field параметры поля @see http://test.com/upage://112.field
	 * @returns {String}
	 * @private
	 */
	var renderField = function(field) {
		if (!field || !field['type']) {
			return '';
		}

		/**
		 * Возвращает значение поля или значение по умолчанию.
		 * @param {Number} [defaultValue] значение по умолчанию
		 */
		var getValueOrDefault = function(defaultValue) {
			return field.value._value || field.value || defaultValue || '';
		};

		switch (field.type) {
			case 'int':
			case 'float':
			case 'price':
			case 'link_to_object_type':
			case 'counter':
				return getValueOrDefault(0);
			case 'tags':
			case 'string':
				return getStringValue();
			case 'color':
				return getColorValue();
			case 'domain_id':
				return getDomainIdValue();
			case 'domain_id_list':
				return getDomainIdListValue();
			case 'text':
				return getValueOrDefault();
			case 'boolean':
				return getBooleanValue();
			case 'img_file':
				return getImageValue();
			case 'video_file':
			case 'swf_file':
			case 'file':
				return getFileValue();
			case 'relation':
				return getRelationValue();
			case 'date':
				return field['value']['formatted-date'];
			case 'optioned':
				return getLabel('js-optioned-value-hidden');
			default:
				return '';
		}

		/**
		 * Возвращает значение полей типа "Строка" или "Теги"
		 * @returns {String}
		 */
		function getStringValue() {
			var value = getValueOrDefault();

			if (field.restriction == 'email') {
				return '<a href="mailto:' + value + '" title="' + value + '" class="link">' + value + '</a>';
			}

			if (typeof value === 'object' && Object.prototype.toString.call(value) === '[object Array]') {
				value = value.join(', ');
			}

			if (/https?:\/\//.test(value)) {
				value = '<a href="' + value + '" title="' + value + '" class="link">' + value + '</a>';
			} else {
				value = '<span title="' + value + '">' + value + '</span>';
			}

			return value;
		}

		/**
		 * Возвращает значение полей типа "Цвет"
		 * @returns {String}
		 */
		function getColorValue() {
			var value = field.value ? field.value : '';
			if (value) {
				value = '<div class="color table"><span class="value">' + value +
					'</span><span class="color-box"><i style="background-color: ' + value +
					'"></i></span></div>';
			}

			return value;
		}

		/**
		 * Возвращает значение полей типа "Ссылка на домен"
		 * @returns {String}
		 */
		function getDomainIdValue() {
			var value = getValueOrDefault();
			if (typeof value === 'object' && typeof value.domain === 'object') {
				value = value.domain.host || '';
			}

			return value;
		}

		/**
		 * Возвращает значение полей типа "Ссылка на список доменов"
		 * @returns {String}
		 */
		function getDomainIdListValue() {
			var value = getValueOrDefault();
			if (typeof value !== 'object') {
				return value;
			}

			if (!value.domain[0]) {
				return value.domain.host || '';
			}

			var hostList = [];
			for (var i = 0; i < value.domain.length; i++) {
				var host = value.domain[i].host || '';
				if (!host) {
					continue;
				}

				hostList.push(host);
			}

			return hostList.join(', ');
		}

		/**
		 * Возвращает значение полей типа "Кнопка-флажок"
		 * @returns {String}
		 */
		function getBooleanValue() {
			if (getValueOrDefault()) {
				return '<img alt="" style="width:13px;height:13px;" src="/images/cms/admin/mac/tree/checked.png" />';
			}
			return '';
		}

		/**
		 * Возвращает значение полей типа "Изображение"
		 * @returns {String}
		 */
		function getImageValue() {
			var value = field.value;
			if (!value) {
				return '';
			}

			var src = value._value;
			var path = src.substring(0, src.lastIndexOf('.'));
			var filename = '';

			if (typeof src == 'string') {
				tmp = src.split(/\//);
				filename = tmp[tmp.length - 1];
			}

			if (value.is_broken == '1') {
				value = '<span title="404" style="color:red;font-weight: bold;cursor: pointer;">?</span>';
				value += '&nbsp;&nbsp;<span style="text-decoration: line-through;" title="' + src + '">' + filename + '</span>';
			} else {
				var ext = value.ext;
				var thumbSrc = '/autothumbs.php?img=' + path + '_sl_180_120.' + ext;
				value = '<img alt="" style="width:13px;height:13px;cursor: pointer;" src="/images/cms/image.png" onmouseover="TableItem.showPreviewImage(event, \'' + thumbSrc + '\')" />';
				value += '&nbsp;&nbsp;<span title="' + src + '">' + filename + '</span>';
			}

			return value;
		}

		/**
		 * Возвращает значение полей типа "Файл", "Видео-файл" и "Флэш-ролик"
		 * @returns {String}
		 */
		function getFileValue() {
			var value = field.value;
			if (!value) {
				return '';
			}

			src = value._value;
			if (value.is_broken == '1') {
				value = src ? ('<span style="text-decoration: line-through;" title="' + src + '">' + src + '</span>') : '';
			} else {
				value = src ? ('<span title="' + src + '">' + src + '</span>') : '';
			}

			return value;
		}

		/**
		 * Возвращает значение полей типа "Выпадающий список" и
		 * "Выпадающий список со множественным выбором"
		 * @returns {String}
		 */
		function getRelationValue() {
			var relation = getValueOrDefault();
			if (!relation) {
				return '';
			}

			var value = '';
			if (relation.item[0]) {
				for (var i = 0; i < relation.item.length; i++) {
					value += relation.item[i].name;

					if (i < relation.item.length - 1) {
						value += ', ';
					}
				}

				value = '<span title="' + value + '">' + value + '</span>';
			} else {
				value = relation.item.name;

				var guid = 'relation-value';
				if (relation.item.guid != undefined) {
					guid = relation.item.guid;
				}

				value = '<span title="' + value + '" class="c-' + guid + '">' + value + '</span>';
			}

			return value;
		}
	};

	/**
	 * Получает значение свойства c именем fieldName
	 * @param {String} fieldName - имя свойства
	 * @return {Mixed} значение свойства, либо false, в случае неудачи
	 */
	this.getValue = function(fieldName) {
		var usedColumns = getUsedColumns();

		var col = usedColumns[fieldName];

		if (fieldName == 'name') {
			return this.control.onGetValueCallback(this.name, fieldName, this);
		}

		if (col) {
			if (col.params[1] == 'static') {
				return this.control.onGetValueCallback(col, fieldName, this);
			}
		}

		if (!_data['properties']) {
			return '&nbsp;';
		}
		if (!_data['properties']['group']) {
			return '&nbsp;';
		}

		var Groups = typeof(_data['properties']['group'][0]) != 'undefined' ? _data['properties']['group'] : [_data['properties']['group']];

		for (var i = 0; i < Groups.length; i++) {
			if (!Groups[i]['property']) {
				continue;
			}
			var Props = typeof(Groups[i]['property'][0]) != 'undefined' ? Groups[i]['property'] : [Groups[i]['property']];

			for (var j = 0; j < Props.length; j++) {
				if (Props[j]['name'] == fieldName) {
					Props[j] = this.control.onGetValueCallback(Props[j], fieldName, this);
					return renderField(Props[j]);
				}
			}

		}

		return this.control.onGetValueCallback('', fieldName, this);
	};

	/**
	 * Получить данные, которые отдал DataSet
	 * @return Array объект со свойствами
	 */
	this.getData = function() {
		return _data;
	};

	/**
	 * Выставляет pageing у item'а и заполняет pagesBar страницами
	 * @param {Object} pageing объект с информацией о пагинации
	 */
	this.setPageing = function(pageing) {
		if (!pageing || !_pagesBar) {
			return false;
		}

		this.pageing = pageing;
		_pagesBar.parentNode.style.display = 'none';
		_pagesBar.classList.add('panel-sorting');
		_pagesBar.innerHTML = '';
		_pagesBar.style.textAlign = 'left';

		if (this.isRoot) {
			this.setPageingLimits(pageing);
		}

		if (pageing.total <= pageing.limit && pageing.offset <= 0) {
			return;
		}

		_pagesBar.parentNode.style.display = '';

		var getCallback = function(page) {
			return function() {
				_self.filter.setPage(page);
				_self.applyFilter(_self.filter, true);
				return false;
			};
		};

		var totalPages = Math.ceil(pageing.total / pageing.limit);
		var currentPage = Math.ceil(pageing.offset / pageing.limit);
		var nextPage;
		var drawEllipsis = true;

		var FIRST_PAGE = 0;
		var LAST_PAGE = (Math.ceil(pageing.total / pageing.limit) - 1);
		var NUMBER_OF_PAGES_SHOWN = 1;

		var isHiddenPage = function(page) {
			return (page != FIRST_PAGE && page != LAST_PAGE && (Math.abs(page - currentPage) > NUMBER_OF_PAGES_SHOWN));
		};

		for (var page = 0; page < totalPages; page += 1) {
			if (isHiddenPage(page)) {
				if (drawEllipsis) {
					nextPage = document.createElement('a');
					nextPage.href = '#';
					nextPage.innerHTML = '…';
					nextPage.className = 'pagination-ellipsis';
					nextPage.onclick = function() {
						return false;
					};
					_pagesBar.appendChild(nextPage);
				}

				drawEllipsis = false;
				continue;
			}

			drawEllipsis = true;
			nextPage = document.createElement('a');
			nextPage.href = '#';

			if (currentPage == page) {
				nextPage.className = 'current';
			}

			nextPage.innerHTML = page + 1;
			nextPage.onclick = getCallback(page);

			_pagesBar.appendChild(nextPage);
		}
	};

	/**
	 * Разворачивает элемент
	 * Метод является обязательным, вызывается Control'ом!
	 * @access public
	 */
	this.expand = function() {
		if (!this.hasChilds) {
			return false;
		}

		this.isExpanded = true;
		if (!this.loaded) {
			this.loaded = true;
			this.initFilter(!_id);
			this.control.load(this.filter);
		}
		if (_toggleControl) {
			_toggleControl.classList.add('switch');
		}

		this.childsContainer.style.display = '';
		$(this.childsContainer).find('.checkbox input:checked').parent().addClass('checked');
		$(this.childsContainer).find('.checkbox').click(function() {
			$(this).toggleClass('checked');
		});

		if (this.loaded) {
			Control.recalcItemsPosition();
		}

		this.control.saveItemState(this.id);
	};

	/**
	 * Сворачивает элемент
	 * Метод является обязательным, вызывается Control'ом!
	 * @access public
	 */
	this.collapse = function() {
		if (!this.hasChilds) {
			return false;
		}

		this.isExpanded = false;
		if (_toggleControl) {
			_toggleControl.classList.remove('switch');
		}
		this.childsContainer.style.display = 'none';
		Control.recalcItemsPosition();
		this.control.saveItemState(this.id);
	};

	/**
	 * Сворачивает/разворачивает элемент в зависимости от текущего состояния
	 * Метод является обязательным, вызывается Control'ом!
	 * @access public
	 */
	this.toggle = function(el) {
		if (this.hasChilds) {
			$(el).toggleClass('switch');
			this.isExpanded ? this.collapse() : this.expand();
		}
	};

	/**
	 * Обновляет элемент, используя новые данные о нем
	 * @param {Array} _oNewData - новые данные о элементе
	 * Метод является обязательным, вызывается Control'ом!
	 * @access public
	 */
	this.update = function(_oNewData) {
		if (_oNewData) {
			if (this.id != _oNewData.id) {
				return false;
			}

			_data = _oNewData;

			if (typeof(_oNewData['template-id']) !== 'undefined') {
				this.templateId = _oNewData['template-id'];
			}

			if (typeof(_oNewData['link']) !== 'undefined' && _oNewData['link'] != this.viewLink) {
				this.viewLink = _oNewData['link'];
				_labelControl.setAttribute('href', this.viewLink);
			}

			if (typeof(_oNewData['childs']) !== 'undefined' && parseInt(_oNewData['childs']) !== _childrenCount) {
				_childrenCount = parseInt(_oNewData['childs']);
				this.hasChilds = _childrenCount > 0 || _id == 0;
				var toggleWrapper = $('td.table-cell > div > span:first', _labelControl);

				if (_self.hasChilds) {
					toggleWrapper.removeClass('catalog-toggle-off');
					toggleWrapper.removeClass('switch');
					toggleWrapper.addClass('catalog-toggle-wrapper');
					toggleWrapper.empty();

					_toggleControl = document.createElement('span');
					_toggleControl.className = 'catalog-toggle switch';
					_toggleControl.src = '/images/cms/admin/mac/tree/collapse.png';
					toggleWrapper.append(_toggleControl);
				} else {
					toggleWrapper.removeClass('catalog-toggle-wrapper');
					toggleWrapper.removeClass('switch');
					toggleWrapper.addClass('catalog-toggle-off');
					toggleWrapper.empty();
				}
			}

			var newActive = typeof(_oNewData['is-active']) !== 'undefined' ? parseInt(_oNewData['is-active']) : 0;
			if (newActive !== this.isActive) {
				this.isActive = newActive;
				this.isActive = isObjectActivated(this, this.isActive);
				if (!this.isActive && !_self.control.objectTypesMode) {
					jQuery(_self.row).addClass('disabled');
				} else {
					jQuery(_self.row).removeClass('disabled');
				}

				if (_activeColumn) {
					jQuery('div', _activeColumn).html(renderField({'type': 'boolean', 'value': this.isActive}));
				}
			}

			if (typeof(_oNewData['name']) !== 'undefined' && _oNewData['name'] != this.name) {
				this.name = _oNewData['name'].length ? _oNewData['name'] : '';
				_labelText.innerHTML = this.name.length ? this.name : getLabel('js-smc-noname-page');
			}

		}
	};

	/**
	 * Устанавливает фильтр для детей элемента и обновляет содержимое, если потребуется
	 * @access public
	 * @param _Filter
	 * @param ignoreHierarchy
	 */
	this.applyFilter = function(_Filter, ignoreHierarchy) {

		if (_Filter instanceof Object) {
			this.filter = _Filter;
		} else {
			this.filter.clear();
		}

		this.initFilter(ignoreHierarchy);

		if (this.loaded) {
			this.control.removeItem(_id, true);
			this.loaded = false;
			if (this.isExpanded) {
				this.expand();
			}
		}
	};

	/**
	 * Инициализирует значение фильтра
	 * @param {Boolean} ignoreHierarchy нужно ли игнорировать иерархию при фильтрации
	 */
	this.initFilter = function(ignoreHierarchy) {
		if (!ignoreHierarchy) {
			this.filter.setParentElements(_id);
		}

		var searchStorage = new SearchAllTextStorage(this.control.id);
		var searchValueList = searchStorage.load();

		if (searchValueList.length > 0) {
			this.filter.setAllTextSearch(searchValueList);
		}
	};

	this.check = function() {
		if (_hasCheckbox) {
			$(_self.checkBox).toggleClass('checked');
		}
	};

	/**
	 * Определяет есть ли у элемента чекбокс
	 * @returns {Boolean}
	 */
	this.isCheckboxAvailable = function() {
		return _hasCheckbox;
	};

	/**
	 * Устанавливает или снимает выделение элемента
	 * @param {Boolean} selected если true, то элемент будет выделен, если false, то выделение
	 * будет снято
	 */
	this.setSelected = function(selected) {
		if (selected) {
			if (!_oldClassName) {

				_oldClassName = _labelControl.className;
				_labelControl.classList.add('selected');
			}

			if (_hasCheckbox) {
				$(this.checkBox).addClass('checked');
			}

			_selected = true;
		} else {
			if (_oldClassName) {
				_oldClassName = null;
			}
			$(_labelControl).removeClass('selected');
			if (_hasCheckbox) {
				$(this.checkBox).removeClass('checked');
			}

			_selected = false;
		}
	};

	this.getSelected = function() {
		return _selected;
	};

	/**
	 * Рисуем кнопочки с выбором количества элементов на страницу
	 * @param pageing
	 */
	this.setPageingLimits = function(pageing) {
		var getCallback = function(limit) {
			return function() {
				_self.filter.setLimit(limit);
				_self.applyFilter(_self.filter, true);
				return false;
			};
		};
		var root = document.getElementById('per_page_limit');
		root.innerHTML = '<span>' + getLabel('js-label-elements-per-page') + '</span>';
		for (var i in _self.pageLimits) {
			var link = document.createElement('a');
			link.className = 'per_page_limit';
			link.rel = '0';
			link.innerHTML = _self.pageLimits[i];
			link.onclick = getCallback(_self.pageLimits[i]);
			if (_self.pageLimits[i] == pageing.limit) {
				link.className += ' current';
			}
			root.appendChild(link);
		}
	};

	constructor();
};

TableItem.minColumnWidth = 150;
TableItem.maxColumnWidth = 800;

/**
 * Сортирвока по колонкам дефолт asc
 * asc -> ""
 * desc-> "switch"
 * не активная -> "disabled"
 * @param _FieldName
 * @param _Control
 * @param _ColumnEl
 * @returns {boolean}
 */
TableItem.orderByColumn = function(_FieldName, _Control, _ColumnEl) {
	if (!_Control || !_FieldName || !_ColumnEl) {
		return false;
	}

	var direction, className = '';
	if (_Control.orderColumn === _ColumnEl) {
		className = _ColumnEl.classList.contains('switch') ? '' : 'switch';
		if (className == '') {
			_ColumnEl.classList.remove('switch');
		}
		if (className !== '') {
			_ColumnEl.classList.add(className);
		}
	} else {
		if (_Control.orderColumn) {
			_Control.orderColumn.classList.remove('switch');
			_Control.orderColumn.classList.add('disabled');
		}
		_Control.orderColumn = _ColumnEl;
		_Control.orderColumn.classList.remove('disabled');
	}
	direction = (className == '' ? 'asc' : 'desc');
	var defFilter = _Control.dataSet.getDefaultFilter();
	defFilter.setOrder(_FieldName, direction);
	_Control.dataSet.setDefaultFilter(defFilter);

	_Control.applyFilter();
};

TableItem.showPreviewImage = function(event, src) {
	if (!event || !src) {
		return;
	}
	var el = event.target ? event.target : event.toElement;
	var x = event.pageX ? event.pageX : event.clientX;
	var y = event.pageY ? event.pageY : event.clientY;

	var img = document.createElement('img');
	img.setAttribute('src', src);
	img.src = src;
	img.className = 'img-fieled-preview';
	img.style.position = 'absolute';
	img.style.width = '180px';
	img.style.height = '120px';
	img.style.top = y + 10 + 'px';
	img.style.left = x + 10 + 'px';
	img.style.border = '1px solid #666';

	document.body.appendChild(img);

	el.onmouseout = function() {
		img.parentNode.removeChild(img);
	};

};
