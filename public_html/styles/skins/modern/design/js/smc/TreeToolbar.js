/**
 * TreeToolbar
 * use Prototype
 */

var TreeToolbar = function(_oControl) {
	/** (Private properties) */
	var __self = this;
	var Control = _oControl;
	var HandleItem = null;
	var cDepth = parseInt(Control.container.style.zIndex) || 0;
	var DataSet = Control.dataSet;
	var selected_count = 0;
	/** (Public properties) */
	this.highlight = null;
	this.element = null;
	this.buttons = [];

	/** (Private methods) */
	var __drawButtons = function() {
		var btns = document.getElementById('tree_toolbar_' + Control.uid);

		// create
		__appendButton(btns, {
			name: 'ico_add',
			className: 'i-add',
			hint: getLabel('js-add-subpage'),
			init: function(button) {
				var isHandleItemCorrect = (HandleItem !== null && HandleItem.createLink !== false);
				var isOperationPermitted = false;

				if (isHandleItemCorrect) {
					isOperationPermitted = (
						HandleItem.permissions && HandleItem.permissions & 4 || !HandleItem.permissions
					);
				}

				if (isHandleItemCorrect && isOperationPermitted && __isOnlyOneItemSelected) {
					button.element.setAttribute('href', HandleItem.createLink);
					button.element.classList.remove('disabled');
				} else {
					button.element.classList.add('disabled');
				}
			}
		});
		// edit
		__appendButton(btns, {
			name: 'ico_edit',
			className: 'i-edit',
			hint: getLabel('js-edit-page'),
			init: function(button) {
				var isHandleItemCorrect = (HandleItem !== null && HandleItem.editLink !== false);
				var isOperationPermitted = false;

				if (isHandleItemCorrect) {
					isOperationPermitted = HandleItem.permissions & 2;
				}

				if (isHandleItemCorrect && isOperationPermitted && __isOnlyOneItemSelected) {
					var hint = getLabel('js-edit-page');
					var href = HandleItem.editLink;
					button.element.classList.remove('disabled');

					if (HandleItem.lockedBy) {
						hint = getLabel('js-page-is-locked');
						href = '#';
						button.element.classList.add('disabled');
					}

					button.element.setAttribute('title', hint);
					button.element.setAttribute('href', href);
				} else {
					button.element.classList.add('disabled');
				}
			},
			release: function() {
				if (HandleItem !== null) {
					if (HandleItem.lockedBy) {
						alert(getLabel('js-page-is-locked'));
						return false;
					}
				}
				return true;
			}
		});
		// blocking
		__appendButton(btns, {
			name: 'blocking',
			className: 'i-hidden',
			hint: getLabel('js-disable-page'),
			init: function(button) {
				if (HandleItem !== null && HandleItem.allowActivity) {
					var className = __getCssClassForActivityIcon(HandleItem.isActive);
					var hint = __getTitleForActivityIcon(HandleItem.isActive);
					button.element.firstChild.className = className;
					button.element.setAttribute('href', '#');
					button.element.setAttribute('title', hint);
					button.element.classList.remove('disabled');
				} else {
					button.element.classList.add('disabled');
				}
			},
			release: function(button) {
				if (HandleItem !== null) {
					var className = '';
					var hint = '';

					if (__isOnlyOneItemSelected()) {
						className = __getCssClassForActivityIcon(!HandleItem.isActive);
						hint = __getTitleForActivityIcon(!HandleItem.isActive);
						button.element.setAttribute('title', hint);
						button.element.firstChild.className = className;

						DataSet.execute('tree_set_activity', {
							'element': HandleItem.id,
							'active': HandleItem.isActive ? 0 : 1,
							'handle_item': HandleItem
						});
					} else {
						var selectedItemsList = __getSelectedItemsList();
						var hasTrue = false, hasFalse = false;

						for (var index in selectedItemsList) {
							if (!selectedItemsList.hasOwnProperty(index)) {
								continue;
							}

							var item = selectedItemsList[index];

							if (item.isActive) {
								hasTrue = true;
							} else {
								hasFalse = true;
							}
						}

						var checked = (!hasTrue && hasFalse);

						className = __getCssClassForActivityIcon(checked);
						hint = __getTitleForActivityIcon(checked);
						button.element.setAttribute('title', hint);
						button.element.firstChild.className = className;

						DataSet.execute('tree_set_activity', {
							'element': __getSelectedItemsIdList(),
							'selected_items': selectedItemsList,
							'active': (checked ? 1 : 0)
						});
					}
				}
				return false;
			}
		});
		// virtual copy
		__appendButton(btns, {
			name: 'copy',
			className: 'i-copy-virtual',
			hint: getLabel('js-vcopy-str'),
			init: function(button) {
				if (HandleItem !== null && HandleItem.allowCopy && __isOnlyOneItemSelected()) {
					button.element.classList.remove('disabled');
				} else {
					button.element.classList.add('disabled');
				}
			},
			release: function() {
				if (HandleItem !== null) {
					HandleItem.className = 'ti virtual';

					if (HandleItem.isDefault) {
						HandleItem.labelControl.className += ' main-page';
					}

					HandleItem.isVirtualCopy = true;
					DataSet.execute('tree_copy_element', {
						'element': HandleItem.id,
						'childs': 1,
						'links': 1,
						'virtuals': 1,
						'permissions': 1,
						'handle_item': HandleItem,
						'copy_all': 0
					});
				}
				return false;
			}
		});
		// real copy
		__appendButton(btns, {
			name: 'clone',
			className: 'i-copy',
			hint: getLabel('js-copy-str'),
			init: function(button) {
				if (HandleItem !== null && HandleItem.allowCopy && __isOnlyOneItemSelected()) {
					button.element.classList.remove('disabled');
				} else {
					button.element.classList.add('disabled');
				}
			},
			release: function() {
				if (HandleItem !== null) {
					DataSet.execute('tree_copy_element', {
						'element': HandleItem.id,
						'childs': 1,
						'links': 1,
						'permissions': 1,
						'handle_item': HandleItem,
						'copy_all': 0,
						'clone_mode': 1
					});
				}
				return false;
			}
		});
		// view
		__appendButton(btns, {
			name: 'view',
			className: 'i-see',
			hint: getLabel('js-view-page'),
			init: function(button) {
				if (HandleItem !== null && HandleItem.viewLink !== false && __isOnlyOneItemSelected()) {
					button.element.setAttribute('href', HandleItem.viewLink);
					button.element.setAttribute('target', '_blank');
					button.element.classList.remove('disabled');
				} else {
					button.element.classList.add('disabled');
				}
			}
		});
		// delete
		__appendButton(btns, {
			name: 'ico_del',
			className: 'i-remove',
			hint: getLabel('js-del-str'),
			init: function(button) {
				var hint = getLabel('js-delete');
				var isHandleItemCorrect = (HandleItem !== null && HandleItem.id > 0);
				var isOperationPermitted = false;

				if (isHandleItemCorrect) {
					isOperationPermitted = (
						HandleItem.permissions && HandleItem.permissions & 8 || !HandleItem.permissions
					);
				}

				if (isHandleItemCorrect && isOperationPermitted) {
					button.element.setAttribute('title', hint);
					button.element.setAttribute('href', '#');
					button.element.classList.remove('disabled');
				} else {
					button.element.classList.add('disabled');
				}
			},
			release: function() {
				if (HandleItem !== null) {
					if (HandleItem.lockedBy) {
						alert(getLabel('js-page-is-locked'));
					} else {
						if (__isOnlyOneItemSelected()) {
							DataSet.execute('tree_delete_element', {
								'childs': 1,
								'element': HandleItem.id,
								'handle_item': HandleItem
							});
						} else {
							var selectedItemsList = __getSelectedItemsList();

							for (var i = 0, cnt = selectedItemsList.length; i < cnt; i++) {
								var item = selectedItemsList[i];

								if (item.lockedBy) {
									alert(getLabel('js-page-is-locked') + '\n' + getLabel('js-steal-lock-question'));
									ContextMenu.getInstance().terminate();
									return false;
								}
							}
							DataSet.execute('tree_delete_element', {
								'element': __getSelectedItemsIdList(),
								'selected_items': selectedItemsList
							});
						}
					}
				}
				return false;
			}
		});

		//Создаем кнопки с выпадающими списками

		//Шаблоны
		var wr1 = document.createElement('ul');
		__appendDropdownButton(wr1, {
			name: 'amend',
			className: 'i-amend',
			hint: getLabel('js-change-template'),
			chEl: true,
			init: function(button) {
				var templButtons = __getTemplatesFunctions();
				button.chEl.innerHTML = '';
				for (var i = 0, cnt = templButtons.length; i < cnt; i++) {
					__appendDropdownButton(button.chEl, templButtons[i]);
				}

				if (HandleItem !== null) {
					button.element.classList.remove('disabled');
				} else {
					button.element.classList.add('disabled');
				}
			}
		});

		btns.appendChild(wr1);

		//домены
		var wr2 = document.createElement('ul');
		__appendDropdownButton(wr1, {
			name: 'copy-other',
			className: 'i-copy-other',
			hint: getLabel('js-crossdomain-copy'),
			chEl: true,
			init: function(button) {
				var templButtons = __getDomainsLang();
				button.chEl.innerHTML = '';
				for (var i = 0, cnt = templButtons.length; i < cnt; i++) {
					__appendDropdownButton(button.chEl, templButtons[i]);
				}

				if (HandleItem !== null) {
					button.element.classList.remove('disabled');
				} else {
					button.element.classList.add('disabled');
				}
			}
		});

		btns.appendChild(wr2);

		btns.onclick = function(e) {
			e.stopPropagation();
		};

		//Выделить все
		__appendButton(btns, {
			name: 'selectAll',
			className: 'i-select',
			hint: getLabel('js-select-all'),
			alwaysActive: true,
			init: function(button) {
				button.element.classList.remove('disabled');
			},
			release: function() {
				Control.select(Control.items);
			}
		});

		__appendButton(btns, {
			name: 'unSelectAll',
			className: 'i-unselect',
			hint: getLabel('js-un-select-all'),
			alwaysActive: true,
			init: function(button) {
				button.element.classList.remove('disabled');
			},
			release: function() {
				Control.unSelect(Control.items);
				__self.hide();
			}
		});

		__appendButton(btns, {
			name: 'invertAll',
			className: 'i-invertselect',
			hint: getLabel('js-invert-all'),
			init: function(button) {
				if (HandleItem !== null) {
					button.element.classList.remove('disabled');
				} else {
					button.element.classList.add('disabled');
				}
			},
			release: function() {
				Control.forEachItem(Control.items, function(item) {
					Control.toggleItemSelection(item);
				});
			}
		});

		__self.element = btns;
		__disableButtons();
	};

	var __initButtons = function() {
		selected_count = __getSelectedItemsListCount();
		for (var i = 0; i < __self.buttons.length; i++) {
			__self.buttons[i].init(__self.buttons[i]);
		}
	};

	var __disableButtons = function() {
		selected_count = __getSelectedItemsListCount();
		for (var i = 0; i < __self.buttons.length; i++) {
			if (!__self.buttons[i].isAllwaisActive) {
				__self.buttons[i].element.classList.add('disabled');
			}
		}
	};

	var __getTemplatesFunctions = function() {
		var items = __getSelectedItemsList();
		var i, langId = false, domainId = false, templateId = false, multipleTemplates = false;
		for (i in items) {
			var item = items[i];

			if (!langId || !domainId) {
				langId = item.langId;
				domainId = item.domainId;
			}

			if (templateId && multipleTemplates) {
				if (templateId != item.templateId) {
					templateId = false;
				}
			}

			if (!templateId && !multipleTemplates) {
				templateId = item.templateId;
				multipleTemplates = true;
			}
		}

		//Process templates list
		var tds = TemplatesDataSet.getInstance();
		var templateItems = [], templates = tds.getTemplatesList(domainId, langId);

		var getClickCallback = function(button) {
			if (!HandleItem) {
				return false;
			}

			var control = HandleItem.control, i, ids = [];
			for (i in items) {
				ids.push(i);
			}

			DataSet.execute('change_template', {
				'element': ids,
				'template-id': button.data,
				'templates': 1,
				'childs': 1,
				'permissions': 1,
				'virtuals': 1,
				'links': 1
			});

			return false;
		};

		for (i in templates) {
			var template = templates[i];
			var id = template['id'], title = template['title'], checked = false;

			if (!id) {
				continue;
			} //TODO: Fix it in TemplatesDataSet class

			if (templateId && templateId == id) {
				checked = true;
			}

			templateItems.push({
				className: checked ? 'checked' : 'undefined',
				caption: title,
				name: title,
				release: getClickCallback,
				data: id
			});
		}
		return templateItems;
	};

	var __getDomainsLang = function() {
		var control = HandleItem.control;
		var items = __getSelectedItemsList();
		var i, j, item, domainId = false, langId = false;
		for (i in items) {
			item = items[i];
			domainId = item.domainId;
			langId = item.langId;
		}

		var tds = TemplatesDataSet.getInstance();
		var langsList = tds.getLangsList();
		var domainList = tds.getDomainsList();

		var getClickCallback = function(button) {
			var i, ids = [];
			for (i in items) {
				ids.push(i);
			}

			DataSet.addEventHandler('onBeforeExecute', createConfirm(control.dataSet));
			DataSet.execute('copyElementToSite', {
				'element': ids,
				'lang-id': button.data.langId,
				'domain-id': button.data.domainId,
				'templates': 1,
				'childs': 1,
				'permissions': 1,
				'virtuals': 1,
				'links': 1
			});

			return false;
		};

		var menuItems = [];
		for (i = 0; i < domainList.length; i++) {
			var d = domainList[i];

			var checked = (domainId == d['id']);

			var smenuItems = [];
			for (j = 0; j < langsList.length; j++) {
				var lang = langsList[j];

				var schecked = checked && (langId == lang['id']);

				smenuItems.push({
					className: schecked ? 'checked' : 'undefined',
					caption: lang['nodeValue'],
					data: {domainId: d['id'], langId: lang['id']},
					release: getClickCallback
				});
			}

			menuItems.push({
				className: checked ? 'checked' : 'undefined',
				caption: d['host'],
				chEl: true,
				submenu: smenuItems
			});
		}

		return menuItems;
	};

	var __appendDropdownButton = function(container, options) {
		var el = document.createElement('li');
		container.appendChild(el);
		if (options.chEl) {
			options.chEl = document.createElement('ul');
		}
		__appendButton(el, options);
	};

	/**
	 * Проверяет, что в контроле выделен только один элемент
	 * @returns {Boolean} результат проверки
	 * @private
	 */
	var __isOnlyOneItemSelected = function() {
		return (__getSelectedItemsListCount() == 1);
	};

	/**
	 * Возвращает список выделенных элементов
	 * @returns {TableItem[]|TreeItem[]}
	 * @private
	 */
	var __getSelectedItemsList = function() {
		return Control.selectedList;
	};

	/**
	 * Возвращает список идентификатор выделенных элементов
	 * @returns {Array|*}
	 * @private
	 */
	var __getSelectedItemsIdList = function() {
		return Object.keys(__getSelectedItemsList());
	};

	/**
	 * Возвращает количество выделенных элементов
	 * @returns {Number}
	 * @private
	 */
	var __getSelectedItemsListCount = function() {
		var selectedIdList = __getSelectedItemsIdList();
		return selectedIdList.length;
	};

	/**
	 * Возвращает CSS класс иконки активности элемента
	 * @param {bool} isActive активность элемента
	 * @returns {string}
	 * @private
	 */
	var __getCssClassForActivityIcon = function(isActive) {
		return isActive ? 'small-ico i-hidden' : 'small-ico i-vision';
	};
	
	/**
	 * Возвращает описание иконки активности элемента
	 * @param {bool} isActive активность элемента
	 * @returns {string}
	 * @private
	 */
	var __getTitleForActivityIcon = function(isActive) {
		return isActive ? getLabel('js-disable-page') : getLabel('js-enable-page');
	};

	var __appendButton = function(container, options) {
		var b = document.createElement('a');
		b.className = 'icon-action';
		var name = options.name || 'toolbtn';
		var caption = options.caption || '';
		var href = options.href || null;
		var className = 'small-ico ' + (options.className || '');
		var init = options.init || function() {
		};
		var title = options.hint || '';
		var data = options.data || null;
		var chEl = options.chEl || null;
		var isAllwaisActive = options.alwaysActive || false;

		var i = document.createElement('i');
		i.className = className;
		b.appendChild(i);

		var el = container.appendChild(b);
		if (chEl !== null) {
			container.appendChild(chEl);
		}

		var button = {
			'name': name,
			'href': href,
			'className': className,
			'init': init,
			'element': el,
			'data': data,
			'caption': caption,
			'chEl': chEl,
			'isAllwaisActive': isAllwaisActive
		};

		__self.buttons.push(button);

		if (href !== null) {
			el.setAttribute('href', href);
		}

		el.setAttribute('title', title);
		$(el).data('info', data);

		if (caption !== '') {
			el.innerHTML = caption;
			el.className = (options.className && options.className != 'undefined' ? options.className : '');
		}

		if (typeof(options.release) === 'function') {
			el.onclick = function(e) {
				if (!DataSet.isAvailable()) {
					return false;
				}

				e.stopPropagation();
				if (HandleItem !== null && !$(this).hasClass('disabled') || isAllwaisActive) {
					return options.release(button);
				}
			};
			el.onmouseup = function(e) {
				e.stopPropagation();
			};
		} else {
			el.onclick = function(e) {
				e.stopPropagation();
				if (!DataSet.isAvailable()) {
					return false;
				}

				if (HandleItem !== null && HandleItem.focus || isAllwaisActive) {
					HandleItem.focus();
				}

				return true;
			};
			el.onmouseup = function(e) {
				e.stopPropagation();
			};
		}

		el.name = name;

		if (chEl !== null && options.submenu != undefined && options.submenu.length > 0) {
			for (var i = 0, cnt = options.submenu.length; i < cnt; i++) {
				__appendDropdownButton(chEl, options.submenu[i]);
			}
		}
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

		if (HandleItem.isDefault) {
			HandleItem.labelControl.className += ' main-page';
		}

		__initButtons();
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

			if (HandleItem.isDefault) {
				HandleItem.labelControl.className += ' main-page';
			}

			HandleItem = null;
		}
		__disableButtons();
	};

	if (typeof(Control) === 'object') {
		__draw();
	} else {
		alert('Can\'t create toolbar without control object');
	}

	/**
	 * Возвращает кнопку тулбара по ее имени
	 * @param {String} name имя кнопки
	 * @returns {*}
	 */
	this.getButton = function(name) {
		return _.findWhere(this.buttons, {name: name});
	};
};

