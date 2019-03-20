/** Control Реализация абстрактного контрола, связанного с каким-либо DataSet'ом */

var Control = function(_oDataSet, _ItemClass, options) {
	/** (Private properties) */
	var __self = this;
	var DataSet = _oDataSet;
	var ItemClass = _ItemClass;
	var options = typeof (options) == 'object' ? options : {};
	var ForceDraw = typeof (options['draw']) == 'boolean' ? options['draw'] : false;
	var ExpandAll = typeof (options['expandAll']) == 'boolean' ? options['expandAll'] : false;
	var Toolbar = typeof (options['toolbar']) === 'function' ? options['toolbar'] : false;
	var RootNodeId = null;
	var RootData = null;
	var forceIgnoreHierarchy = typeof (options['ignoreHierarchy']) === 'boolean' ? options['ignoreHierarchy'] : false;
	var onRenderComplete = typeof (options['onRenderComplete']) === 'function' ? options['onRenderComplete'] : function() {
	};
	var CurrentFilter = new filter();
	var Settings = SettingsStore.getInstance();
	var SelectBehaviour = typeof (options['onItemClick']) === 'function' ? options['onItemClick'] : function() {
		return true;
	};
	var SelectCallback = typeof (options['onItemSelect']) === 'function' ? options['onItemSelect'] : function() {
		return true;
	};
	var TargetCallback = typeof (options['onTargetSelect']) === 'function' ? options['onTargetSelect'] : function() {
		return true;
	};
	/** (Static properties) */
	Control.instances[Control.instances.length] = this;

	/* Public properties */
	this.container = typeof (options['container']) == 'object' ? options['container'] : document.body;
	this.dataSet = DataSet;
	this.toolbar = null;
	this.items = {};
	this.id = options['id'] || Math.random();
	this.uid = options['uid'] || Math.round(Math.random() * 100000);
	this.iconsPath = options['iconsPath'] || '/images/cms/admin/mac/tree/';
	this.flatMode = options['flatMode'] || false;
	this.defaultRootMode = options['defaultRootMode'] || false;
	this.objectTypesMode = options['objectTypesMode'] || false;
	this.contentType = options['contentType'] || 'pages';
	this.enableObjectsActivity = options['enableObjectsActivity'] || false;
	this.lastClicked = null;
	this.selectedList = {};
	this.targetsList = {};
	this.dragAllowed = typeof (options['allowDrag']) == 'boolean' ? options['allowDrag'] : true;
	this.onGetValueCallback = typeof (options['onGetValueCallback']) === 'function' ? options['onGetValueCallback'] : function(value, name, item) {
		return value;
	};
	this.onDrawFieldFilter = typeof (options['onDrawFieldFilter']) === 'function' ? options['onDrawFieldFilter'] : function(field, th) {
		return false;
	};
	this.onRemoveColumn = typeof (options['onRemoveColumn']) === 'function' ? options['onRemoveColumn'] : function(field) {
		return false;
	};
	this.initContainer = this.container;
	this.disableCSVButtons = options['disableCSVButtons'] || false;
	this.hideCsvImportButton = options['hideCsvImportButton'] || false;
	this.disableTooManyChildsNotification = options['disableTooManyChildsNotification'] || false;
	this.labelFirstColumn = (typeof (options['label_first_column']) === 'string') ? options['label_first_column'] : '';
	this.PerPageLimits = typeof (options['perPageLimits']) == 'array' ? options['perPageLimits'] : [10, 20, 50, 100];
	this.enableEdit = options['enableEdit'];
	this.visiblePropsMenu = options['visiblePropsMenu'];

	/**
	 * Список названий полей, которые обязательно должны быть в табличном контроле.
	 * Это значит, что их нет в меню и их нельзя скрыть.
	 */
	this.requiredPropsMenu = options['requiredPropsMenu'];

	this.sequencePropsMenu = options['sequencePropsMenu'];
	this.toolbarFunctions = options['toolbarFunctions'] || null;
	this.toolbarMenu = options['toolbarMenu'] || null;
	this.disableNameFilter = options['disableNameFilter'] || false;
	this.hasCheckboxes = typeof options['hasCheckboxes'] == 'boolean' ? options['hasCheckboxes'] : true;
	this.isCanSelect = typeof options['isCanSelect'] == 'boolean' ? options['isCanSelect'] : true;
	this.onElementMouseOver = typeof options['onElementMouseOver'] == 'function' ? options['onElementMouseOver'] : function() {
	};
	this.onElementMouseOut = typeof options['onElementMouseOut'] == 'function' ? options['onElementMouseOut'] : function() {
	};
	this.onElementClick = typeof options['onElementClick'] == 'function' ? options['onElementClick'] : function() {
	};
	this.noCheckboxes = !!(parseInt(options['noCheckboxes']));
	/** (Private methods) */

	var __constructor = function() {
		if (ForceDraw) {
			__self.init();
		}
	};

	// event handlers
	var __DataSetInitComplete = function() {

		// create root node if does not exist
		if (typeof (__self.items[RootNodeId]) === 'undefined') {

			__self.items[RootNodeId] = new ItemClass(__self, null, {
				'id': (RootNodeId ? RootNodeId : 0),
				'iconbase': '/images/cms/admin/mac/tree/ico_domain.png'
			});
		}

		__self.items[RootNodeId].draw();

		if (Toolbar) {
			__self.toolbar = new Toolbar(__self);

		}

		if (__self.getItemState(RootNodeId) || RootNodeId !== 0) {
			__self.items[RootNodeId].expand();
		}

	};

	var __arraySearch = function(arrList, vVal) {
		for (var i = 0; i < arrList.length; i++) {
			if (arrList[i] === vVal) {
				return arrList[i];
			}
		}
		return null;
	};

	var __buildItems = function(arrNodes, oCurrItem, ignoreHierarchy) {

		if (typeof (oCurrItem) != 'object' || typeof (arrNodes) == 'undefined') {
			return false;
		}
		if (!ignoreHierarchy) {
			ignoreHierarchy = false;
		}
		var currId = oCurrItem.id;
		for (var i = 0; i < arrNodes.length; i++) {
			var parentId = (typeof (arrNodes[i]['parentId']) != 'undefined') ? parseInt(arrNodes[i]['parentId']) : 0;

			if (forceIgnoreHierarchy) {
				parentId = 0;
			}

			if (parentId == currId || ignoreHierarchy) {
				var newItemId = parseInt(arrNodes[i]['id']);
				if (__self.items[newItemId]) {
					__self.items[newItemId].update(arrNodes[i]);
				} else {

					__self.items[newItemId] = oCurrItem.appendChild(arrNodes[i]);
					if (ExpandAll || __self.getItemState(newItemId)) {
						__self.items[newItemId].expand();
					}
				}

				__buildItems(arrNodes, __self.items[newItemId]);
			}
		}

		(function() {
			var objectData = null;
			var item = null;
			var nextObjectData = null;

			for (var i = 0; i < arrNodes.length; i++) {
				objectData = typeof arrNodes[i] == 'object' ? arrNodes[i] : {};
				nextObjectData = typeof arrNodes[i + 1] == 'object' ? arrNodes[i + 1] : {};
				item = __self.getItem(objectData.id);
				item.nextSibling = __self.getItem(nextObjectData.id);
			}
		}());
	};

	var __DataSetAfterLoad = function(arrParams) {
		var arrObjs = arrParams['objects'];
		var oFilter = arrParams['filter'];
		var pageing = arrParams['paging'];
		var arrParents = oFilter.getParents();
		if (oFilter['Parents'] != null) {
			pageing['parent'] = oFilter['Parents'][0];
		}

		if (oFilter.AllText.length > 0 && pageing.total == 0) {
			openDialog(getLabel('js-smc-empty-result'), getLabel('js-smc-empty-title'));
		}

		var ignoreHierarchy = false;
		if (!arrParents.length) {
			arrParents = [(RootNodeId ? RootNodeId : 0)];
			ignoreHierarchy = true;
		}
		for (var i = 0; i < arrParents.length; i++) {
			var parentId = parseInt(arrParents[i]);
			var parent = (typeof (__self.items[parentId]) != 'undefined') ? __self.items[parentId] : __self.items[RootNodeId];

			__buildItems(arrObjs, parent, ignoreHierarchy);

			if (parent) {
				parent.setLoaded(true);
				parent.setPageing(pageing);
			}
		}

		onRenderComplete();

		Control.recalcItemsPosition();
		if (Control.HandleItem && __self.toolbar) {
			__self.toolbar.show(Control.HandleItem, true);
		}
	};

	var __DataSetBeforeRefresh = function() {
		__self.removeItem(RootNodeId);
		// restore root
		if (RootData) {
			if (__self.initContainer) {
				__self.container = __self.initContainer;
			}
			var root = __self.setRootNode(RootData, true);
			root.draw();
		}
		Control.recalcItemsPosition();
	};

	var __DataSetAfterExecute = function(arrData) {
		var arrObjs = arrData['objects'];
		var error = arrData['error'];

		var arrParams = arrData['params'];
		var pageing = arrParams['paging'];
		var hItem = arrParams['handle_item'];
		var selItems = arrParams['selected_items'];

		var items = {};
		if (selItems) {
			items = selItems;
		} else if (hItem) {
			items[hItem.id] = hItem;
		}

		var method = arrData['method'];

		if (!error) {
			// delete
			switch (method.toLowerCase()) {
				case 'restore_element' :
				case 'del' :
				case 'tree_delete_element' : {
					for (var id in items) {
						var itm = items[id];
						__self.removeItem(id);
						delete __self.selectedList[id];
						if (itm.parent && itm.parent.parent) {
							__buildItems(arrObjs, itm.parent.parent);
						}

					}
					Control.HandleItem = null;
					if (__self.toolbar) {
						__self.toolbar.hide();
					}
					break;
				}

				case 'export' : {
					var i, res = '';
					if (arrData.objects[0].file) {
						window.location = arrData.objects[0].file;
					}
					break;
				}

				case 'move':
				case 'tree_move_element' : {
					var receiver = arrParams['receiver_item'];
					var before = arrParams['before'] ? receiver.control.getItem(arrParams['before']) : null;
					var after = arrParams['after'] ? receiver.control.getItem(arrParams['after']) : null;

					if (!receiver) {
						break;
					}

					if (receiver.control.contentType === 'objects') {
						receiver = receiver.control.getRoot();

						if (arrObjs.length && hItem) {
							var itmData = arrObjs[0] || [];
							var newRelData = arrObjs[1] || [];
							var oldRelData = arrObjs[2] || [];

							if (hItem.parent) {
								hItem.parent.update(oldRelData);
							}

							receiver.update(newRelData);
							hItem.control.removeItem(hItem.id);
							var itm = null;

							if (arrParams['as-sibling']) {
								if (before) {
									itm = receiver.appendBefore(itmData, before);
								} else if (after) {
									itm = receiver.appendAfter(itmData, after);
								} else {
									itm = receiver.appendChild(itmData);
								}
							} else {
								if (receiver.loaded) {
									itm = receiver.appendFirst(itmData);
								}
							}

							if (itm) {
								receiver.control.items[itm.id] = itm;
							}
						}
						break;
					}

					if (arrObjs.length === 0) {
						break;
					}

					var receiverData = [];
					receiverData['id'] = receiver.id;
					receiverData['is-active'] = receiver.isActive;
					receiverData['childs'] = 0;
					var oldParents = [];

					for (var key in arrObjs) {
						var itemData = arrObjs[key];
						var itemId = itemData.id;
						var oldItem = __self.selectedList[itemId];

						if (oldItem) {
							if (!oldParents[oldItem.parent.id]) {
								oldParents[oldItem.parent.id] = 1;
							} else {
								oldParents[oldItem.parent.id]++;
							}
						}

						__self.removeItem(itemId);
						delete __self.selectedList[itemId];

						if (arrParams['as-sibling']) {
							if (before) {
								itm = receiver.appendBefore(itemData, before);
							} else if (after) {
								itm = receiver.appendAfter(itemData, after);
							} else {
								itm = receiver.appendChild(itemData);
							}
						} else {
							itm = receiver.appendFirst(itemData);
						}

						if (itm) {
							receiver.control.items[itm.id] = itm;
							receiverData['childs']++;
							itm.control.getSelectionCallback()(itm);
						}
					}

					for (id in oldParents) {
						var oldParent = __self.getItem(id);

						if (!oldParent) {
							continue;
						}

						var oldParentData = [];
						var oldParentChildrenLength = $('tr[rel=' + oldParent.id + '] + tr > td > table.table > tr[rel]').length;
						oldParentData['id'] = oldParent.id;
						oldParentData['is-active'] = oldParent.isActive;
						oldParentData['childs'] = oldParentChildrenLength - oldParents[id];
						oldParent.update(oldParentData);
					}

					receiver.update(receiverData);
					break;
				}

				case 'change_template': {
					(function() {
						if (!arrData.HandleItem || !arrData.HandleItem.control || !arrData.HandleItem.control.toolbar) {
							return;
						}

						var toolbar = arrData.HandleItem.control.toolbar;
						var buttonName = 'amend';

						if (typeof toolbar.getButton != 'function') {
							return;
						}

						var button = toolbar.getButton(buttonName);
						var buttonElement = button.element;
						var subMenuElement = $(buttonElement).next('ul');
						var subMenuLinks = subMenuElement.find('li a');
						var newTemplateId = parseInt(arrData.params['template-id']);

						subMenuLinks.removeClass('checked');

						subMenuLinks.each(function() {
							var templateId = $(this).data('info');

							if (templateId == newTemplateId) {
								$(this).addClass('checked');
							}
						});
					})();

					break;
				}
				case 'copyElementToSite': {
					(function() {
						for (var objectKey in arrObjs) {
							var copy = arrObjs[objectKey];
							var copyTreeId = 'tree-content-sitetree-' + copy['domain-id'];
							var copyControl = false;

							for (var controlKey in Control.instances) {
								var control = Control.instances[controlKey];

								if (control.id == copyTreeId) {
									copyControl = control;
								}
							}

							if (typeof copyControl != 'object') {
								continue;
							}

							var controlRoot = copyControl.getRoot();
							var newItemId = copy.id;

							if (copyControl.items[newItemId]) {
								copyControl.items[newItemId].update(copy);
							} else {
								copyControl.items[newItemId] = controlRoot.appendChild(copy);
							}
						}
					})();
					break;
				}
				default : {
					for (var id in items) {
						var parentId = __self.items[id].parent;
						ignoreHierarchy = (!__self.items[parentId]);

						__buildItems(arrObjs, parentId, ignoreHierarchy);
					}
				}
			}

			if (__self.toolbar && hItem === Control.HandleItem) {
				__self.toolbar.show(Control.HandleItem, true);
			}

			Control.recalcItemsPosition();
			Control.PrepareDrag = false;
			Control.startDragItem = null;
			Control.PrepareDrag = false;
			Control.DragMode = false;
		} else {
			// TODO: Make it beauty
			var error_type = jQuery(error).find('type').text();

			if (error_type == '__alias__') {

				var alias = {};
				var params = arrParams;
				var elems = jQuery(error).find('item');

				elems.each(function() {
					var url = jQuery(this).attr('path') + '' + jQuery(this).attr('alias');
					var html = "<form method='post' id='' enctype='multipart/form-data' ";
					html += " action=''>" + getLabel('js-smc-control-page-already-exist', url);
					html += "<br/><label for='new_alias'>" + getLabel('js-content-alias-copy') + "</label> <br/> ";
					html += jQuery(this).attr('path') + "<input type='text' class='default alt-name' name='new_alias" + jQuery(this).attr('id') + "'  value='" + jQuery(this)
									.attr('alt_name_normal') + "'  id='new_alias" + jQuery(this).attr('id') + "' size='15' />";
					html += "</form>";

					var t = this;

					openDialog('', getLabel('js-move-title'), {
						html: html,
						width: 400,
						confirmText: getLabel('js-content-alias-new'),
						cancelButton: true,
						cancelText: getLabel('js-content-alias-change'),
						cancelCallback: function(popupName) {
							params['move[' + jQuery(t).attr('id') + ']'] = 1;

							callback(function() {
								closeDialog(popupName);
							}, jQuery(t).attr('id'));
						},
						confirmCallback: function(popupName, scope) {
							params['alias[' + jQuery(t).attr('id') + ']'] = jQuery('#new_alias' + jQuery(t).attr('id'), scope).val();

							callback(function() {
								closeDialog(popupName);
							}, jQuery(t).attr('id'));
						}
					});
				});

				/**
				 * Обрабатывает нажатия на кнопки "Заменить" и "Переименовать" во всплывающем окне,
				 * которое возникает, если элемент c переданным псевдостатическим адресом уже существует
				 * в целевой языковой версии.
				 * @param {Function} complete функция, вызывающаяся при успешном завершении
				 * @param {Number} elementId ID обрабатываемого элемента
				 */
				var callback = function(complete, elementId) {
					var completeFunc = typeof complete == 'function' ? complete : function() {
					};
					var handleItem = arrData['HandleItem'];

					if (!handleItem) {
						completeFunc();
						return;
					}
					if (elementId) {
						params['element'] = [];
						params['element'].push(elementId);
					}

					handleItem.control.dataSet.execute('' + method.toLowerCase() + '', params);
					completeFunc();
				};

			} else if (error_type == '__template_not_exists__') {
				var text = jQuery(error).find('text').text();

				openDialog('', 'Ошибка', {
					html: text
				});
			} else {
				alert(error.firstChild.nodeValue);
			}
		}

	};

	/** (Set DataSet Event handlers) */
	DataSet.addEventHandler('onAfterLoad', __DataSetAfterLoad);
	DataSet.addEventHandler('onAfterPieceLoad', __DataSetAfterLoad);
	DataSet.addEventHandler('onInitComplete', __DataSetInitComplete);
	DataSet.addEventHandler('onBeforeRefresh', __DataSetBeforeRefresh);
	DataSet.addEventHandler('onAfterExecute', __DataSetAfterExecute);

	// forse container position
	this.initContainer.style.position = 'relative';

	/** Выполняет инициализацию */
	this.init = function() {
		DataSet.init();

	};

	this.load = function(_oFilter, _bNeedDraw) {
		DataSet.load(_oFilter);
	};

	this.getItemByPosition = function(mX, mY) {
		var hItem = null;
		for (var id in this.items) {
			if (!this.items[id]) {
				continue;
			}
			var pos = this.items[id].position;
			if (mY > pos.top) {
				if (mY <= pos.bottom) {
					if (mX > pos.left) {
						if (mX <= pos.right) {
							hItem = this.items[id];
							break;
						}
					}
				}
			}
		}

		return hItem;
	};

	this.getItem = function(_ID) {
		if (typeof (this.items[parseInt(_ID)]) == 'object') {
			return this.items[parseInt(_ID)];
		} else {
			return false;
		}
	};

	this.setRootNode = function(_RootData, _skipLoad) {
		RootData = _RootData;
		RootNodeId = _RootData['id'];
		this.items[RootNodeId] = new ItemClass(__self, null, _RootData);
		if (_skipLoad) {
			this.items[RootNodeId].loaded = true;
		}
		return this.items[RootNodeId];
	};

	this.getRootNodeId = function() {
		return RootNodeId;
	};

	this.getRoot = function() {
		return this.items[RootNodeId];
	};

	this.applyBehaviour = function(Item) {
		if (SelectBehaviour) {
			return SelectBehaviour(Item);
		}
		return true;
	};

	/**
	 * Сохраняет состояние элемента (свернут/развернут) в профиле пользователя
	 * @access public
	 */
	this.saveItemState = function(_ID) {
		var itemId = parseInt(_ID);
		if (this.items[itemId]) {
			var itm = this.items[itemId];
			var expanded = Settings.get(__self.id, 'expanded');
			var val = '{' + itm.id + '}';
			if (typeof (expanded) != 'string') {
				expanded = '';
			}
			if (expanded.indexOf(val) !== -1 && !itm.isExpanded) {
				expanded = expanded.replace(val, '');
				Settings.set(__self.id, expanded, 'expanded');
				return true;
			} else if (expanded.indexOf(val) === -1 && itm.isExpanded) {
				expanded += val;
				Settings.set(__self.id, expanded, 'expanded');
				return true;
			} else {
				return false;
			}
		}
	};

	/**
	 * Применяет новый фильтр для выбранных элементов
	 * @param _oFilter объект класса filter
	 * @access public
	 */
	this.applyFilter = function(_oFilter) {
		this.selectItems([]);
		if (_oFilter != undefined && (_oFilter instanceof Object)) {
			CurrentFilter = _oFilter;
		}
		if (!CurrentFilter) {
			return;
		}
		var hasTargets = false;
		for (var i in this.targetsList) {
			this.targetsList[i].applyFilter(CurrentFilter.clone());
			hasTargets = true;
		}
		if (!hasTargets) {
			__self.items[RootNodeId].applyFilter(CurrentFilter.clone(), true);
		}
	};

	/** Возвращает текущий фильтр */
	this.getCurrentFilter = function() {
		return CurrentFilter;
	};

	/**
	 * Устанавливае/снимает выделение переданного элемента
	 * @param _oItem выделяемый элемент
	 * @access public
	 */
	this.toggleItemSelection = function(_oItem) {
		if (_oItem) {
			if (_oItem.getSelected()) {
				_oItem.setSelected(false);
				delete this.selectedList[_oItem.id];
				SelectCallback(_oItem, false);
			} else {
				_oItem.setSelected(true);
				this.selectedList[_oItem.id] = _oItem;
				SelectCallback(_oItem, true);
			}
		}
	};

	/**
	 * Устанавливает или снимает (переключает) выделение списка элементов
	 * в диапазоне от выбранных двух на одном уровне вложенности
	 * @param {TreeItem|TableItem} handleItem
	 */
	this.toggleRangeSelection = function(handleItem) {
		if (!this.lastClicked) {
			return;
		}

		var sameLevelItemsIdList = handleItem.parent.childs;
		var firstBoundIndex = sameLevelItemsIdList.indexOf(handleItem.id);
		var secondBoundIndex = sameLevelItemsIdList.indexOf(this.lastClicked.id);
		var leftBoundIndex = Math.min(firstBoundIndex, secondBoundIndex);
		var rightBoundIndex = Math.max(firstBoundIndex, secondBoundIndex);
		var selectedItemsIdList = sameLevelItemsIdList.slice(leftBoundIndex, rightBoundIndex + 1);
		var sameLevelItems = [];

		for (var i = 0; i < sameLevelItemsIdList.length; i++) {
			sameLevelItems.push(this.getItem(sameLevelItemsIdList[i]));
		}

		this.unSelect(sameLevelItems);

		var item = null;
		var selectedItems = [];

		for (i = 0; i < selectedItemsIdList.length; i++) {
			item = this.getItem(selectedItemsIdList[i]);
			selectedItems.push(item);
		}

		this.select(selectedItems);
	};

	/**
	 * Выполняет функцию apply для каждого элемента
	 * @param {TableItem[]|TreeItem[]} items массив элементов
	 * @param {Function} apply
	 */
	this.forEachItem = function(items, apply) {
		var applyFunction = typeof apply == 'function' ? apply : function() {
		};
		var skipZeroElement = (!$.isArray(items));

		$.each(items, function(index, item) {
			if (skipZeroElement && index == 0) {
				return;
			}

			if (!item) {
				return;
			}

			applyFunction(item, index);
		});
	};

	/**
	 * Снимает выделение элементов
	 * @param {TableItem[]|TreeItem[]} items массив элементов
	 */
	this.unSelect = function(items) {
		var self = this;

		this.forEachItem(items, function(item) {
			item.setSelected(false);
			delete self.selectedList[item.id];
		});

		this.HandleItem = null;
		this.toolbar.hide();
	};

	/**
	 * Выделяет элементы
	 * @param {TableItem[]|TreeItem[]} items массив элементов
	 */
	this.select = function(items) {
		var self = this;

		this.forEachItem(items, function(item) {
			item.setSelected(true);
			self.selectedList[item.id] = item;
			__self.HandleItem = item;
		});

		this.toolbar.show(this.HandleItem);
	};

	this.setSelectionCallback = function(_Callback, _Replace) {
		if (typeof (_Callback) === 'function') {
			if (_Replace) {
				SelectCallback = _Callback;
			} else {
				var h = SelectCallback;
				SelectCallback = function(a, b) {
					h(a, b);
					_Callback(a, b);
				};
			}
		}
	};
	/**
	 * Some dark magic. Don't use it IRL
	 * @return Function callback on item selection
	 */
	this.getSelectionCallback = function() {
		return SelectCallback;
	};

	this.selectItems = function(Items) {
		if (!(Items instanceof Array)) {
			Items = [Items];
		}
		for (var i in this.selectedList) {
			this.toggleItemSelection(this.selectedList[i]);
		}
		this.selectedList = {};
		for (var i = 0; i < Items.length; i++) {
			this.toggleItemSelection(Items[i]);
		}
	};

	this.setTargetItems = function(Targets) {
		this.targetsList = {};
		for (var i in Targets) {
			this.targetsList[i] = Targets[i];
		}
		TargetCallback(this.targetsList);
	};

	this.isTarget = function(Item) {
		for (var i in this.targetsList) {
			if (this.targetsList[i] == Item) {
				return true;
			}
		}
		return false;
	};

	this.setTargetSelectCallback = function(_Callback) {
		if (typeof (_Callback) === 'function') {
			TargetCallback = _Callback;
		}
	};

	this.getItemState = function(_ID) {
		var itemId = parseInt(_ID);

		if (this.items[itemId]) {
			var itm = this.items[itemId];
			if (!itm.hasChilds) {
				return false;
			}
			var expanded = Settings.get(__self.id, 'expanded');
			var val = '{' + itm.id + '}';
			if (typeof (expanded) != 'string') {
				expanded = '';
			}
			return (expanded.indexOf(val) !== -1);
		}
		return false;
	};

	this.removeItem = function(_ID, keepSelf) {
		var itemId = parseInt(_ID);
		if (this.items[itemId]) {
			var itm = this.items[itemId];
			var parent = itm.parent;
			for (var j = 0; j < itm.childs.length; j++) {
				this.removeItem(itm.childs[j]);
			}
			itm.childs = [];
			if (!keepSelf) {
				this.items[itemId] = false;
				itm.clear();
			}
		}
	};

	this.expandAll = function() {
		ExpandAll = true;
		for (id in this.items) {
			this.items[id].expand();
		}
	};

	this.collapseAll = function() {
		ExpandAll = false;
		for (id in this.items) {
			this.items[id].collapse();
		}
	};

	/** Пересчитывает позицию для каждого элемента контрола (нужно для Drag&Drop) */
	this.recalcItemsPosition = function() {
		for (var id in this.items) {
			if (!this.items.hasOwnProperty(id)) {
				continue;
			}

			if (this.items[id] && this.items[id].recalcPosition) {
				this.items[id].recalcPosition();
			}

		}
	};
	/**
	 * Обработчик события нажатия левой кнопкой мыши по строке табличного контрола
	 * @param event
	 * @param {Number} itemId идентификатор элемента табличного контрола
	 */
	this.handleMouseDown = function(event, itemId) {
		var targetElement = event.target,
			currentItem = __self.getItem(itemId),
			checkbox = currentItem.checkBox,
			currentButton = event.button,
			handleElement = currentItem.element;

		var isCellClicked = (handleElement == targetElement || targetElement.parentNode.parentNode == handleElement);
		var isCheckboxClicked = (targetElement == checkbox || targetElement.parentNode == checkbox);

		if ($(targetElement).hasClass('name_col')) {
			var isCellClicked = (handleElement == targetElement || targetElement.parentNode.parentNode.parentNode == handleElement);
		}

		if (currentButton == 2 && currentItem.getSelected() && Object.keys(currentItem.control.selectedList).length == 1) {
			return true;
		}

		if (currentItem instanceof TreeItem && !$(targetElement).hasClass('catalog-toggle') && !$(targetElement).hasClass('catalog-toggle-wrapper')) {
			__self.toggleSelection(currentItem, event);
		}

		if ((isCellClicked || isCheckboxClicked) && currentItem instanceof TableItem) {
			__self.toggleSelection(currentItem, event);
		}

		if (event.shiftKey) {
			__self.toggleRangeSelection(currentItem);
		}

		this.saveSelectedItem(itemId);
		var el = event.target;

		if (el.className.toLowerCase() != 'ti-toggle') {
			Control.PrepareDrag = true;
			Control.startDragItem = Control.HandleItem;
			Control.startDragX = event.pageX;
			Control.startDragY = event.pageY;
		}
	};

	/**
	 * Обработчик события отжатия левой кнопки мыши по строке табличного контрола
	 * @param event
	 * @param {TreeItem|TableItem} item
	 */
	this.handleMouseUp = function(event, item) {
		var self = this;
		var leftMouseButtonCode = 0;
		var leftMouseButtonClicked = (event.button == leftMouseButtonCode);

		if (!Control.enabled || !leftMouseButtonClicked || !item) {
			return;
		}

		var handleItem = Control.HandleItem;
		var toolbar = self.toolbar;
		var selectedCount = Object.keys(self.selectedList).length;

		if (!Control.DragMode && handleItem) {
			if ((handleItem.getSelected() || selectedCount > 0) && toolbar) {
				toolbar.show(handleItem, true);
			}

			self.lastClicked = handleItem;
		}

		if (selectedCount === 0 && toolbar) {
			toolbar.hide();
		}

		Control.PrepareDrag = false;
		Control.startDragItem = null;
		Control.DragMode = false;
	};

	/**
	 * Сохраняет текущий обрабатываемый элемент
	 * @param {Number} id идентификатор элемента
	 * @returns {*}
	 */
	this.saveSelectedItem = function(id) {
		if (!Control.instances.length || Control.DragMode || !Control.enabled) {
			return;
		}

		var item = this.getItem(id);
		var selectedItems = this.selectedList;
		var selectedItemsIdList = Object.keys(selectedItems);
		var lastSelectedId = parseInt(selectedItemsIdList[selectedItemsIdList.length - 1]);
		var lastSelectedItem = selectedItems[lastSelectedId];

		Control.HandleItem = selectedItemsIdList.length > 0 ? lastSelectedItem : null;

		if (item.getSelected()) {
			Control.HandleItem = item;
		}

		return item;
	};

	/**
	 * Переключает выделение строк
	 * @param {TreeItem|TableItem} item объект элемента контрола
	 * @param event
	 */
	this.toggleSelection = function(item) {
		__self.toggleItemSelection(item);
	};

	__constructor();
};

// static properties
Control.DragMode = false;
Control.PrepareDrag = false;
Control.startDragX = 0;
Control.startDragY = 0;
Control.startDragItem = null;

Control.DragSensitivity = 7; // SMF
Control.HandleItem = null;
Control.DraggableItem = null;
Control.instances = [];
Control.enabled = true;

Control.getInstanceById = function(sId) {
	for (var i = 0; i < Control.instances.length; i++) {
		if (Control.instances[i].id === sId) {
			return Control.instances[i];
		}
	}
	return null;
};

// static methods
Control.recalcItemsPosition = function() {
	for (var i = 0; i < Control.instances.length; i++) {
		Control.instances[i].recalcItemsPosition();
	}
};

// define common observers
Control.detectItemByMousePointer = function(x, y) {
	var HandleItem = Control.HandleItem;
	if (HandleItem) {
		var cpos = jQuery(HandleItem.control.initContainer).position();
		var pos = HandleItem.position;
		if (y > pos.top + cpos.top && y <= pos.bottom + cpos.top && x > pos.left + cpos.left && x <= pos.right + cpos.left) {
			return HandleItem;
		}
	}
	var hItem = null;
	for (var i = 0; i < Control.instances.length; i++) {
		var Inst = Control.instances[i];
		var cpos = jQuery(Inst.initContainer).offset();
		hItem = Inst.getItemByPosition(x - cpos.left, y - cpos.top);
		if (hItem) {
			break;
		}
	}

	return hItem;
};

Control.handleMouseUp = function(event) {
	if (!Control.enabled) {
		return;
	}

	if (!Control.DragMode && Control.HandleItem) {
		if (event.altKey) {
			// Nothig to do with selection
		} else if ((event.ctrlKey || event.metaKey) && (event.button != 2)) {
			//Control.HandleItem.control.toggleItemSelection(Control.HandleItem);
		} else if ((event.shiftKey) && (event.button != 2)) {
			//Control.HandleItem.control.toggleRangeSelection(Control.HandleItem);
		} else {
			var el = event.target;
			//debugger;
			if (event.button === 0 && !el.classList.contains('editable') && !el.classList.contains('cmenuItem')
				&& !el.classList.contains('catalog-toggle') && !el.classList.contains('catalog-toggle-wrapper')
				&& (el.type === undefined || el.type.toLocaleLowerCase() === 'checkbox')) {
				if (el.type === undefined) {
					Control.HandleItem.check();
				}

				Control.HandleItem.control.toggleItemSelection(Control.HandleItem);

				if (!Control.HandleItem.getSelected()) {
					var keys = Object.keys(Control.HandleItem.control.selectedList);
					if (keys.length > 0) {
						Control.HandleItem.control.toggleItemSelection(Control.HandleItem.control.selectedList[keys.length - 1]);

						if (Control.HandleItem.control.toolbar) {
							Control.HandleItem.control.toolbar.show(Control.HandleItem);
						}
					} else {
						if (Control.HandleItem.control.toolbar) {
							Control.HandleItem.control.toolbar.hide();
						}
					}
				} else {
					if (Control.HandleItem.control.toolbar) {
						Control.HandleItem.control.toolbar.show(Control.HandleItem);
					}
				}

			} else {

			}
		}
		Control.HandleItem.control.lastClicked = Control.HandleItem;

	}
	Control.PrepareDrag = false;
	Control.startDragItem = null;
	Control.DragMode = false;
};

jQuery(window).bind('resize', function(event) {
	Control.recalcItemsPosition();
});

function createAddButton(oButton, oControl, sAddLink, aTypes) {
	oButton.ControlInstance = oControl;
	var _SelectionCallback = function() {
		var Count = 0;
		var id = null;
		var Allow = false;
		var i;
		for (i in oControl.selectedList) {
			Count++;
			id = i;
		}
		for (i = 0; i < aTypes.length; i++) {
			if ((id == null && aTypes[i] === true) || (id != null && (aTypes[i] === oControl.selectedList[id].baseMethod || aTypes[i] === '*'))) {
				Allow = true;
				break;
			}
		}
		var _sAddLink = sAddLink.replace(/\{id\}/, (id || '0'));
		_sAddLink = _sAddLink.replace(/\{\$param0\}/, (id || '0'));
		_sAddLink = _sAddLink.replace(/\{\$pre_lang\}/, window.pre_lang);
		var domainSelect = document.querySelector('.domains_selector > select');
		if (domainSelect) {
			var domain = domainSelect.options[domainSelect.selectedIndex].text;
			if (_sAddLink.indexOf('?') != -1) {
				_sAddLink = _sAddLink + '&domain=' + domain;
			} else {
				_sAddLink = _sAddLink + '?domain=' + domain;
			}
		}
		oButton.addLink = _sAddLink;
		for (var i = 0; i < oButton.linkCache.length; i++) {
			oButton.linkCache[i].href = oButton.addLink;
			oButton.linkCache[i]['param0'] = (id || '0');
		}
		if (oButton.tagName.toLowerCase() == 'a') {
			oButton.href = oButton.addLink;
			oButton.param0 = (id || '0');
		}
		var needPositionRecalc = false;
		if (Count > 1 || !Allow) {
			if (oButton.style.display != 'none') {
				oButton.style.display = 'none';
				needPositionRecalc = true;
			}
		} else {
			if (oButton.style.display == 'none') {
				oButton.style.display = '';
				needPositionRecalc = true;
			}
		}
		if (needPositionRecalc) {
			Control.recalcItemsPosition();
		}
	};
	oControl.setSelectionCallback(function(a, b) {
		setTimeout(_SelectionCallback, 100);
	});

	var _sAddLink = sAddLink.replace(/\{id\}/, '0');
	_sAddLink = _sAddLink.replace(/\{\$param0\}/, '0');
	_sAddLink = _sAddLink.replace(/\{\$pre_lang\}/, window.pre_lang);
	oButton.addLink = _sAddLink;
	oButton.linkCache = oButton.getElementsByTagName('a');
	_SelectionCallback();
}
