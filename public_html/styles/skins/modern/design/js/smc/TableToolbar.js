/**
 * TableToolbar
 * use Prototype
 */

var TableToolbar = function(_oControl) {
	/** (Private properties) */
	var __self = this;
	var Control = _oControl;
	var HandleItem = null;
	var cDepth = parseInt(Control.container.style.zIndex) || 0;
	var IconsPath = Control.iconsPath;
	var DataSet = Control.dataSet;

	/** (Public properties) */
	this.TableTollbarFunctions = {
		delButton: {
			name: 'ico_del',
			className: 'i-remove',
			hint: getLabel('js-delete'),
			init: function(button) {
				var e = button.element;
				var hint = getLabel('js-delete');

				if (HandleItem !== undefined && (HandleItem.permissions && HandleItem.permissions & 8 || !HandleItem.permissions)) {
					var icon = IconsPath + 'ico_del.png';

					e.setAttribute('title', hint);
					e.setAttribute('href', '#');
					e.style.display = 'inline-block';
				} else {
					e.style.display = 'none';
				}
			},

			release: function(button) {
				var entitiesIdList = [];
				var selectedList = Control.selectedList;
				var selectedCount = Object.keys(selectedList).length;

				if (selectedCount > 0) {
					for (var id in selectedList) {
						if (!selectedList.hasOwnProperty(id)) {
							continue;
						}
						entitiesIdList.push(id);
					}
				}

				if (HandleItem !== undefined) {
					DataSet.execute('tree_delete_element', {
						'element': entitiesIdList,
						'selected_items': selectedList
					});
				}
				return false;
			}
		},
		editButton: {
			name: 'ico_edit',
			className: 'i-edit',
			hint: getLabel('js-edit-item'),
			init: function(button) {
				var e = button.element;
				if (HandleItem !== undefined && HandleItem.editLink !== false && (HandleItem.permissions && HandleItem.permissions & 2 || !HandleItem.permissions)) {
					var href = HandleItem.editLink;
					e.setAttribute('href', href);
					e.style.display = 'inline-block';
				} else {
					e.style.display = 'none';
				}
			},

			release: function(button) {
				return true;
			}
		},
		addButton: {
			name: 'ico_add',
			className: 'i-add',
			hint: getLabel('js-add-page'),
			init: function(button) {
				if (HandleItem !== undefined && HandleItem.createLink !== false) {
					button.element.setAttribute('href', HandleItem.createLink);
					button.element.style.display = 'inline-block';
				} else {
					button.element.style.display = 'none';
				}
			}
		},

		blockingButton: {
			name: 'blocking',
			hint: getLabel('js-disable-page'),
			className: '',
			init: function(button) {
				if (HandleItem == undefined) {
					button.element.style.display = 'none';
					return false;
				}
				var _allow_activity = HandleItem.allowActivity && (_oControl.contentType == 'pages' || (_oControl.contentType == 'objects' && _oControl.enableObjectsActivity));
				if (HandleItem !== undefined && _allow_activity) {
					var icon = HandleItem.isActive ? 'small-ico i-hidden' : 'small-ico i-vision';
					var hint = HandleItem.isActive ? getLabel('js-disable-page') : getLabel('js-enable-page');

					if (_oControl.contentType == 'objects') {
						hint = HandleItem.isActive ? getLabel('js-disable') : getLabel('js-enable');
					}

					$('i', $(button.element)).attr('class', icon);
					button.element.setAttribute('href', '#');
					button.element.setAttribute('title', hint);
					button.element.style.display = 'inline-block';
				} else {
					button.element.style.display = 'none';
				}
			},
			release: function(button) {
				if (HandleItem !== undefined) {
					var icon = HandleItem.isActive ? 'small-ico i-vision' : 'small-ico i-hidden';
					var hint = HandleItem.isActive ? getLabel('js-enable') : getLabel('js-disable');
					button.element.setAttribute('title', hint);
					$('i', $(button.element)).attr('class', icon);
					var entitiesIdList = [];
					var selectedList = Control.selectedList;
					var selectedCount = Object.keys(selectedList).length;

					if (selectedCount > 0) {
						for (var id in selectedList) {
							if (!selectedList.hasOwnProperty(id)) {
								continue;
							}
							entitiesIdList.push(id);
						}
					}
					DataSet.execute('tree_set_activity', {
						'element': entitiesIdList,
						'object': entitiesIdList,
						'active': HandleItem.isActive ? 0 : 1,
						'selected_items': HandleItem.control.selectedList,
						'viewMode': 'full'
					});
				}
				return false;
			}
		},

		viewButton: {
			name: 'view',
			hint: getLabel('js-view-page'),
			className: 'i-see',
			init: function(button) {
				if (HandleItem !== undefined && HandleItem.getData && HandleItem.getData().guide == 'guide') {
					button.element.setAttribute('href', window.pre_lang + '/admin/data/guide_items/' + HandleItem.id + '/');
					button.element.style.display = 'inline-block';
				} else if (HandleItem !== undefined && HandleItem.viewLink !== false) {
					button.element.setAttribute('href', HandleItem.viewLink);
					button.element.style.display = 'inline-block';
				} else {
					button.element.style.display = 'none';
				}
			}
		},

		csvExportButton: {
			name: 'csvExport',
			hint: getLabel('js-csv-export'),
			className: 'i-csv-export',
			alwaysActive: true,
			init: function(button) {
				if (!isExchangeableControl()) {
					__disableButtons(button);
					return;
				}

				for (var i in Control.selectedList) {
					if (Control.selectedList[i].hasChilds) {
						__enableButton(button);
						return;
					}
				}

				__disableButton(button);
			},
			release: function() {
				executeExchange('exportCallback');
				return false;
			}
		},

		csvImportButton: {
			name: 'csvImport',
			hint: getLabel('js-csv-import'),
			className: 'i-csv-import',
			alwaysActive: true,
			init: function(button) {
				if (!isExchangeableControl()) {
					__disableButtons(button);
					return;
				}
			},
			release: function() {
				executeExchange('importCallback');
				return false;
			}
		},

		restoreButton: {
			name: 'i-restore',
			hint: getLabel('js-trash-restore'),
			className: 'i-restore',
			init: function(button) {
				if (HandleItem !== undefined && (HandleItem.permissions && HandleItem.permissions & 8 || !HandleItem.permissions)) {
					button.element.style.display = 'inline-block';
				} else {
					button.element.style.display = 'none';
				}
			},
			release: function(button) {
				if (HandleItem !== undefined) {
					var items = Control.selectedList,
						ids = [];

					for (var i in items) {
						ids.push(i);
					}

					Control.dataSet.execute('restore_element', {
						'element': ids,
						'selected_items': items
					});

					return false;
				}
				return false;
			}
		},

		moveButton: {
			name: 'moveElement',
			hint: getLabel('js-change-parent'),
			className: 'i-move-other',
			init: function(button) {
				if (Control.flatMode || Control.contentType !== 'pages' || (uAdmin.data && uAdmin.data['module'] != 'catalog')) {
					$(button.element).hide();
				}
			},

			release: function() {
				CatalogModule.openCategoriesWindow(HandleItem);
			}
		},

		delIndex: {
			name: 'deleteIndex',
			hint: getLabel('js-indexing-deleting-confirmation'),
			className: 'i-remove',
			init: function(button) {
				if (_.keys(Control.selectedList).length === 0) {
					__disableButton(button);
				} else {
					__enableButton(button);
				}
			},

			release: function() {
				var elementsIds = _.keys(Control.selectedList);

				if (elementsIds.length > 0) {
					AdminIndexing.Controller._deleteIndexes(elementsIds);
				}
			}
		},

		selectAllButton: {
			name: 'selectAll',
			hint: getLabel('js-select-all'),
			className: 'i-select',
			alwaysActive: true,
			init: function(button) {

			},
			release: function() {
				Control.select(Control.items);
				return false;
			}
		},

		unSelectAllButton: {
			name: 'unSelectAll',
			hint: getLabel('js-un-select-all'),
			className: 'i-unselect',
			alwaysActive: true,
			init: function(button) {

			},
			release: function() {
				Control.unSelect(Control.items);
				__self.hide();
				return false;
			}
		},

		invertAllButton: {
			name: 'invertAll',
			hint: getLabel('js-invert-all'),
			className: 'i-invertselect',
			init: function(button) {
				if (HandleItem !== undefined) {
					__enableButton(button);
				} else {
					__disableButton(button);
				}
			},
			release: function() {
				Control.forEachItem(Control.items, function(item) {
					Control.toggleItemSelection(item);
				});

				if (_.size(Control.selectedList) === 0) {
					__self.hide();
				}

				return false;
			}
		}

	};

	this.highlight = null;
	this.element = null;
	this.buttons = [];
	this.menu = [
		this.TableTollbarFunctions.viewButton,
		this.TableTollbarFunctions.blockingButton,
		this.TableTollbarFunctions.addButton,
		this.TableTollbarFunctions.editButton,
		this.TableTollbarFunctions.delButton,
		this.TableTollbarFunctions.csvExportButton,
		this.TableTollbarFunctions.csvImportButton,
		this.TableTollbarFunctions.moveButton
	];

	this.__selectButtons = [
		this.TableTollbarFunctions.selectAllButton,
		this.TableTollbarFunctions.unSelectAllButton,
		this.TableTollbarFunctions.invertAllButton
	];

	this.__singleActionButtons = [
		this.TableTollbarFunctions.editButton,
		this.TableTollbarFunctions.addButton,
		this.TableTollbarFunctions.viewButton
	];

	this.__exchangeButtons = [
		this.TableTollbarFunctions.csvExportButton,
		this.TableTollbarFunctions.csvImportButton
	];

	/** (Private methods) */
	var __drawButtons = function() {
		if (Control.toolbarFunctions !== null) {
			var fkeys = Object.keys(Control.toolbarFunctions);
			for (var j = 0, cnt = fkeys.length; j < cnt; j++) {
				__self.TableTollbarFunctions[fkeys[j]] = Control.toolbarFunctions[fkeys[j]];
			}
		}
		if (Control.toolbarMenu !== null) {
			__self.menu = [];
			for (var k = 0, mcnt = Control.toolbarMenu.length; k < mcnt; k++) {
				__self.menu.push(__self.TableTollbarFunctions[Control.toolbarMenu[k]]);
			}
		}
		if (TTCustomizer.menu.length > 0 && _.keys(TTCustomizer.buttons).length > 0) {
			_.extend(__self.TableTollbarFunctions, TTCustomizer.buttons);
			if (!TTCustomizer.extendDefault) {
				__self.menu = [];
			}
			for (var t = 0, tcnt = TTCustomizer.menu.length; t < tcnt; t++) {
				__self.menu.push(__self.TableTollbarFunctions[TTCustomizer.menu[t]]);
			}
		}

		var btns = document.getElementById('tollbar_wrapper');

		for (var i = 0, cnt = __self.menu.length; i < cnt; i++) {
			if (__self.menu[i].name == 'ico_add') {
				if (!TableToolbar.disableAddButton) {
					__appendButton(btns, __self.menu[i]);
				}
			} else {
				__appendButton(btns, __self.menu[i]);
			}

		}

		for (var i = 0, cnt = __self.__selectButtons.length; i < cnt; i++) {
			__appendButton(btns, __self.__selectButtons[i]);
		}

		__self.element = btns;

		Control.dataSet.addEventHandler('onAfterLoad', __firstInitToolbar);
		__disableButtons(__self.menu);
		__disableButton(__self.__selectButtons[2]);

		if (isExchangeableControl()) {
			__enableButtons(__self.__exchangeButtons);
		}
	};

	/**
	 * Инициализация набора действий тулбара
	 * @private
	 */
	var __firstInitToolbar = function() {
		var keys = Object.keys(Control.items),
			id = keys.length > 1 ? 2 : 1;
		HandleItem = Control.items[keys[id]];
		__initButtons();
		__disableButtons(__self.menu);
		__disableButton(__self.__selectButtons[2]);

		if (isExchangeableControl()) {
			__enableButtons(__self.__exchangeButtons);
		}
	};

	/**
	 * Проверяет может ли быть выполнен импорт/экспорт списка в CSV
	 * @returns {*}
	 */
	var isExchangeable = function() {
		var control = Control;

		if (!isExchangeableControl()) {
			return false;
		}

		var selectedItems = control.selectedList;
		var itemList = (selectedItems && selectedItems.length > 0) ? selectedItems : control.items;
		return Object.keys(itemList).length > 0;
	};

	/**
	 * Определяет, поддерживает ли текущий контрол операции по импорту и экспорту в csv
	 * @returns {boolean}
	 */
	var isExchangeableControl = function() {

		if (!Control || Control.objectTypesMode || Control.flatMode || Control.disableCSVButtons) {
			return false;
		}

		return true;
	};

	/**
	 * Выполняет экспорт/импорт списка в формате CSV
	 * @param {String} callbackName callbackName имя функции, которая вызывается для непосредственного импорта/экспорта
	 */
	var executeExchange = function(callbackName) {
		var allowedCallbacks = ['importCallback', 'exportCallback'];

		if (!isExchangeable() || allowedCallbacks.indexOf(callbackName) === -1) {
			return;
		}

		var control = Control;
		var selectedItemList = control.selectedList ? control.selectedList : {};
		var itemList = (selectedItemList && Object.keys(selectedItemList).length > 0) ? selectedItemList : control.items;
		var firstItem = itemList[Object.keys(itemList)[0]];
		var selectedItemIdList = [];
		var isFilterApplied = Object.keys(control.getCurrentFilter().Props).length > 0;

		for (var key in itemList) {
			if (!itemList.hasOwnProperty(key)) {
				continue;
			}

			var item = itemList[key];

			if (item.hasChilds && !isFilterApplied) {
				selectedItemIdList.push(parseInt(key));
			}
		}

		var relIdOrIdList = (callbackName === 'importCallback') ? firstItem.id : selectedItemIdList;
		firstItem[callbackName](relIdOrIdList);
	};

	var __initButtons = function() {
		for (var i = 0; i < __self.buttons.length; i++) {
			__self.buttons[i].init(__self.buttons[i]);
		}
	};

	var __appendButton = function(container, options) {
		var b = document.createElement('a');
		b.className = 'icon-action';
		var name = options.name || 'toolbtn';
		var href = options.href || '#';
		var className = 'small-ico ' + (options.className || '');
		var init = options.init || function() {
		};
		var title = options.hint || '';
		var isAllwaysActive = options.alwaysActive || false;

		var i = document.createElement('i');
		i.className = className;
		b.appendChild(i);

		var el = container.appendChild(b);
		var button = {
			'name': name,
			'href': href,
			'className': className,
			'init': init,
			'element': el
		};

		options.element = el;
		__self.buttons.push(button);

		el.setAttribute('href', href);
		el.setAttribute('title', title);
		if (typeof(options.release) === 'function') {
			el.onclick = function() {
				if (!DataSet.isAvailable()) {
					return false;
				}

				if ((HandleItem || isAllwaysActive) && !$(this).hasClass('disabled')) {
					return options.release(button);
				} else {
					return false;
				}
			};
		} else {
			el.onclick = function() {
				if (!DataSet.isAvailable()) {
					return false;
				}

				if (!isAllwaysActive && $(this).hasClass('disabled')) {
					return false;
				}

				if (HandleItem && HandleItem.focus) {
					HandleItem.focus();
				}

				return true;
			};
		}

		el.name = name;
	};

	/**
	 * Возвращает элемент кнопки тулбара по его имени
	 * @param {Object} button объект кнопки
	 * @returns {*}
	 * @private
	 */
	var __getButtonElement = function(button) {
		var container = __self.element;
		var $button = jQuery(button.element, container);
		return ($button.length > 0 ? $button.eq(0) : null);
	};

	/**
	 * Скрывает кнопку тулбара
	 * @param {Object} button объект кнопки
	 * returns {jQuery}
	 * @private
	 */
	var __disableButton = function(button) {
		var $element = __getButtonElement(button);

		if ($element) {
			$element.addClass('disabled');
		}
	};

	/**
	 * Показывает кнопку тулбара
	 * @param {Object} button объект кнопки
	 * returns {jQuery}
	 * @private
	 */
	var __enableButton = function(button) {
		var $element = __getButtonElement(button);

		if ($element) {
			$element.removeClass('disabled');
		}
	};

	/**
	 * Выполняет для каждой кнопки функцию apply
	 * @param {Array.<Object>} buttons объекты кнопок
	 * @param {Function} apply функция, выполняющаяся для каждой кнопки
	 * @private
	 */
	var __processButtons = function(buttons, apply) {
		var apply = (typeof apply == 'function' ? apply : function() {
		});

		(function() {
			for (var i = 0; i < buttons.length; i++) {
				var button = buttons[i];
				apply(button);
			}
		})();
	};

	/**
	 * Скрывает кнопки тулбара
	 * @param {Array.<Object>} buttons объекты кнопок
	 * @private
	 */
	var __disableButtons = function(buttons) {
		__processButtons(buttons, __disableButton);
	};

	/**
	 * Показывает кнопки тулбара
	 * @param {Array.<Object>} buttons объекты кнопок
	 * @private
	 */
	var __enableButtons = function(buttons) {
		__processButtons(buttons, __enableButton);
	};

	var __draw = function() {
		var el = document.createElement('div');
		el.className = 'tree-highlight';
		el.style.display = 'none';
		el.style.position = 'absolute';
		el.style.zIndex = cDepth - 1;

		__self.highlight = Control.container.appendChild(el);

		__drawButtons();
	};

	/** (Public methods) */
	this.show = function(_HandleItem, bForce) {
		bForce = bForce || false;
		if (typeof(_HandleItem) === 'undefined' || (HandleItem === _HandleItem && !bForce)) {
			return false;
		}

		HandleItem = _HandleItem;

		var selectedItemsCount = Object.keys(HandleItem.control.selectedList).length;

		__enableButtons(this.menu);
		__enableButton(this.__selectButtons[2]);

		if (selectedItemsCount > 1) {
			__disableButtons(this.__singleActionButtons);
		}

		__initButtons();

		if (!isExchangeableControl()) {
			__disableButtons(__self.__exchangeButtons);
		}

		this.element.style.display = '';
	};

	this.hide = function() {
		if (HandleItem) {
			if (HandleItem.getSelected()) {
				HandleItem.labelControl.classList.add('selected');
			} else if (HandleItem.isVirtualCopy) {
				HandleItem.labelControl.classList.add('virtual');
			} else {
				HandleItem.labelControl.classList.remove('virtual');
				HandleItem.labelControl.classList.remove('selected');
			}

			__disableButtons(this.menu);
			__disableButton(this.__selectButtons[2]);
			HandleItem = null;
		}

		if (isExchangeableControl()) {
			__enableButtons(this.__exchangeButtons);
		}
	};

	if (typeof(Control) === 'object') {
		__draw();
	} else {
		alert('Can\'t create toolbar without control object');
	}
};

TableToolbar.disableAddButton = false;


