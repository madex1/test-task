uAdmin('.eip', function(extend) {

	function uEditInPlace() {
		this.isMac = (navigator.userAgent.indexOf('Mac OS') != -1);
		this.drawControls();

		if (uAdmin.data && uAdmin.data.pageId) {
			this.metaInit();
		} else {
			uAdmin.onLoad('data', function() {
				uAdmin.eip.metaInit();
			});
		}

		this.bindEditorEvents();

		// переключаем состояние eip по куке, когда загружены все необходимые модули
		if (!uAdmin.tickets && uAdmin.panel) {
			uAdmin.onLoad('tickets', this.activateByCookie);
		} else if (uAdmin.tickets && !uAdmin.panel) {
			uAdmin.onLoad('panel', this.activateByCookie);
		} else {
			uAdmin.onLoad('eip', this.activateByCookie);
		}

		this.init();
		this.bindGlobalEvents();
	}

	uEditInPlace.prototype.init = function() {
		this.deleteButtonsTimeout = null;
		this.initEditBoxes();
	};

	uEditInPlace.prototype.initEditBoxes = function() {
		var self = this;

		jQuery(document).on('mouseover', '.u-eip-edit-box', function() {
			self.editBoxMouseoverHandler(this);
		});

		jQuery(document).on('mouseout', '.u-eip-edit-box-hover', function() {
			self.editBoxMouseoutHandler(this);
		});
	};

	uEditInPlace.prototype.editBoxMouseoverHandler = function(element) {

		var self = this,
			node = jQuery(element).addClass('u-eip-edit-box-hover'),
			info = self.searchAttr(element);

		if (node.attr('umi:delete') && info.id) {
			self.addDeleteButton(element, info);
		} else {
			self.dropDeleteButtons();
		}

		element.onclick = function(e) {
			element.onclick = function() {
				return true;
			};
			if (window.event) {
				return window.event.ctrlKey;
			} else {
				return e.ctrlKey;
			}
		};
	};

	uEditInPlace.prototype.editBoxMouseoutHandler = function(element) {
		var node = jQuery(element).removeClass('u-eip-edit-box-hover'),
			self = this;

		if (node.attr('umi:delete')) {
			self.deleteButtonsTimeout = setTimeout(self.dropDeleteButtons, 500);
		}

		node.click(function() {
			return true;
		});

	};

	uEditInPlace.prototype.addDeleteButton = function(element, info) {
		var self = this;

		if (this.deleteButtonsTimeout) {
			clearTimeout(this.deleteButtonsTimeout);
		}
		this.dropDeleteButtons();

		var deleteButton = document.createElement('div');
		jQuery(deleteButton).attr('class', 'eip-del-button');
		document.body.appendChild(deleteButton);
		self.placeWith(element, deleteButton, 'right', 'middle');

		jQuery(deleteButton)
			.bind('mouseover', function() {
				if (self.deleteButtonsTimeout) {
					clearTimeout(self.deleteButtonsTimeout);
				}
			})
			.bind('mouseout', function() {
				self.deleteButtonsTimeout = setTimeout(self.dropDeleteButtons, 500);
			})
			.bind('click', function() {
				info['delete'] = true;
				self.queue.add(info);
				uAdmin.eip.normalizeBoxes();
			});

		return deleteButton;

	};

	uEditInPlace.prototype.dropDeleteButtons = function() {
		jQuery('.eip-del-button').remove();
	};

	uEditInPlace.prototype.bindGlobalEvents = function() {
		this.bindKeyboardShortcut();
		window.onresize = function() {
			uAdmin.eip.normalizeBoxes();
		};
	};

	uEditInPlace.prototype.bindKeyboardShortcut = function() {
		jQuery(document).bind('keydown', function(e) {
			if (e.keyCode == 113) {
				uAdmin.eip.swapEditor();
			}
		});
	};

	uEditInPlace.prototype.activateByCookie = function() {
		if (jQuery.cookie('eip-editor-state')) {
			uAdmin.eip.swapEditor();
		} else {
			uAdmin.tickets.enable();
			uAdmin.panel.editInAdmin('enable');
		}
	};

	uEditInPlace.prototype.drawControls = function() {
		this.editorToggleButton = this.addEditorToggleButton();
		this.editorControlsHolder = this.addEditorControlsHolder();
		this.saveButton = this.addSaveButton();
		this.undoButton = this.addUndoButton();
		this.redoButton = this.addRedoButton();
	};

	uEditInPlace.prototype.addEditorControlsHolder = function() {
		return jQuery('\n\
			<div id="save_edit"></div>\n\
		').appendTo(uAdmin.panel.eipHolder);
	};

	uEditInPlace.prototype.addSaveButton = function() {
		var self = this;
		if (!this.editorControlsHolder) {
			return null;
		}
		return jQuery('\n\
			<div id="save" title="' + getLabel('js-panel-save') + ' (' + (this.isMac ? 'Cmd' : 'Ctrl') + '+S)">&#160;</div>\n\
		')
			.appendTo(this.editorControlsHolder)
			.click(function() {
				self.queue.save();
				return false;
			});
	};

	uEditInPlace.prototype.addUndoButton = function() {
		var self = this;
		if (!this.editorControlsHolder) {
			return null;
		}
		return jQuery('\n\
			<div id="edit_back" title="' + getLabel('js-panel-cancel') + ' (' + (this.isMac ? 'Cmd' : 'Ctrl') + '+Z)">&#160;</div>\n\
		')
			.appendTo(this.editorControlsHolder)
			.click(function() {
				self.queue.back();
				return false;
			});
	};

	uEditInPlace.prototype.addRedoButton = function() {
		var self = this;
		if (!this.editorControlsHolder) {
			return null;
		}
		return jQuery('\n\
			<div id="edit_next" title="' + getLabel('js-panel-repeat') + ' (' + (this.isMac ? 'Cmd+Shift+Z' : 'Ctrl+Y') + ')">&#160;</div>\n\
		')
			.appendTo(this.editorControlsHolder)
			.click(function() {
				self.queue.forward();
				return false;
			});
	};

	uEditInPlace.prototype.addEditorToggleButton = function() {
		var self = this,
			button = jQuery('\n\
				<div id="edit">\n\
					<span class="in_ico_bg">&#160;</span>\n\
					<span class="edit-button-label"></span> (F2)\
				</div>\n\
			');
		button
			.appendTo(uAdmin.panel.eipHolder)
			.click(function() {
				self.swapEditor();
				return false;
			});
		button.setLabelText = function(sText) {
			if (sText) {
				button.find('.edit-button-label').text(sText);
			}
		};
		button.setLabelText(getLabel('js-panel-edit'));
		return button;
	};

	uEditInPlace.prototype.metaInit = function() {
		var self = this;
		this.meta.element_id = uAdmin.data.pageId;

		/**
		 * Экранирует двойные кавычки в строке
		 * @param {String} str Строка
		 * @returns {String}
		 */
		function escapeQuotes(str) {
			return (str || '').replace(/"/g, '&quot;');
		}

		this.meta.old = {
			alt_name: escapeQuotes(uAdmin.data.page['alt-name']),
			title: escapeQuotes(uAdmin.data.title),
			keywords: escapeQuotes(uAdmin.data.meta.keywords),
			descriptions: escapeQuotes(uAdmin.data.meta.description)
		};

		this.meta['new'] = {};
		this.metaToggleButton = this.addMetaToggleButton();
		this.metaPanel = this.addMetaPanel();
		this.metaSaveButton = this.getMetaSaveButton();

		this.metaPanel
			.find('input[type!="submit"]').bind('blur', function(e) {
				var name = this.name.replace(/^meta_/g, '');
				self.meta['new'][name] = this.value;
			})
			.bind('keyup mousedown blur', function() {
				var l = this.value.length;
				if (l > 255) {
					this.value = this.value.substring(0, 255);
				} else {
					var id = this.id + '-count';
					jQuery('#' + id).html(l);
				}
			})
			.trigger('keyup');
	};

	/* Show block eip info */
	uEditInPlace.prototype.showBlockInfo = function() {
	};

	uEditInPlace.prototype.addMetaToggleButton = function() {
		var $element = jQuery('\n\
			<div id="meta">\n\
				<span class="in_ico_bg">&#160;</span>\n\
				' + getLabel('js-panel-meta') + '\n\
			</div>\n\
		')
			.appendTo(uAdmin.panel.metaHolder);
		if (this.addMetaToggleButtonClickHandler($element)) {
			return $element;
		}

		$element.click(function() {
			uAdmin.panel.changeAct(this);
			uAdmin.eip.metaPanel.toggle();
			uAdmin.eip.meta.enabled = uAdmin.eip.metaPanel.is(':visible');
			return false;
		});

		return $element;
	};

	/*
	 * Assign ClickHandler on MetaToggleButton
	 * @return bool
	 */
	uEditInPlace.prototype.addMetaToggleButtonClickHandler = function($element) {
		return false;
	};

	uEditInPlace.prototype.addMetaPanel = function() {
		if (!uAdmin.panel.panelHolder) {
			return null;
		}
		var self = this;
		return jQuery('\n\
				<div id="u-quickpanel-meta">\n\
					<table>\n\
						<tr>\n\
							<td width="100px">' + getLabel('js-panel-meta-altname') + ': </td>\n\
							<td>\n\
								<input type="text" name="alt_name" id="u-quickpanel-metaaltname" value="' + self.meta.old.alt_name + '"/>\n\
								<div class="meta_count" id="u-quickpanel-metaaltname-count"/>\n\
							</td>\n\
						</tr>\n\
						<tr>\n\
							<td width="100px">' + getLabel('js-panel-meta-title') + ': </td>\n\
							<td>\n\
								<input type="text" name="title" id="u-quickpanel-metatitle" value="' + self.meta.old.title + '"/>\n\
								<div class="meta_count" id="u-quickpanel-metatitle-count"/>\n\
							</td>\n\
						</tr>\n\
						<tr>\n\
							<td>' + getLabel('js-panel-meta-keywords') + ': </td>\n\
							<td>\n\
								<input type="text" name="meta_keywords" id="u-quickpanel-metakeywords" value="' + self.meta.old.keywords + '"/>\n\
								<div class="meta_count" id="u-quickpanel-metakeywords-count"/>\n\
								<div class="meta_buttons"><a href="/admin/seo/" style="color:white;">' + getLabel('js-panel-meta-analysis') + '</a></div>\n\
							</td>\n\
						</tr>\n\
						<tr>\n\
							<td>' + getLabel('js-panel-meta-descriptions') + ':</td>\n\
							<td>\n\
								<input type="text" name="meta_descriptions" id="u-quickpanel-metadescription" value="' + self.meta.old.descriptions + '"/>\n\
								<div class="meta_count" id="u-quickpanel-metadescription-count"/>\n\
								<div class="meta_buttons">\n\
									<input type="submit" id="save_meta_button" value="' + getLabel('js-panel-save') + '">\n\
								</div>\n\
							</td>\n\
						</tr>\n\
					</table>\n\
				</div>\n\
			').appendTo(uAdmin.panel.panelHolder);
	};

	uEditInPlace.prototype.getMetaSaveButton = function() {
		if (!this.metaPanel) {
			return null;
		}
		var self = this;
		return this.metaPanel
			.find('#save_meta_button')
			.click(function() {
				self.metaSave();
				return false;
			});
	};

	uEditInPlace.prototype.metaSave = function() {
		var params = {},
			i,
			self = this,
			sentRequests = 0,
			recievedRequests = 0;

		for (i in self.meta['new']) {
			if (self.meta['new'][i] != self.meta.old[i]) {
				params['field-name'] = ((i == 'keywords' || i == 'descriptions') ? 'meta_' + i : i);
				params['element-id'] = self.meta.element_id;
				params['value'] = self.meta['new'][i];
				sentRequests++;
				jQuery.post('/admin/content/editValue/save.json', params, function(data) {
					if (data.error) {
						self.message(data.error);
						return;
					}
					recievedRequests++;
					if (recievedRequests == sentRequests) {
						self.message(getLabel('js-panel-message-changes-saved'));
						uAdmin.eip.metaToggleButton.click();
						self.onMetaSaved();
					}
				}, 'json');
			}

			delete self.meta['new'][i];
		}
	};

	/**
	 * Обрабатывает событие успешного сохранения полей мета тегов
	 */
	uEditInPlace.prototype.onMetaSaved = function() {
	};

	uEditInPlace.prototype.bindEditorEvents = function() {
		this.bind('Enable', this.enableEventHandler);
		this.bind('Disable', this.disableEventHandler);
		this.bind('Repaint', this.repaintEventHandler);
		this.bind('Save', this.saveEventHandler);
	};

	uEditInPlace.prototype.enableEventHandler = function(type) {
		if (type == 'after') {
			uAdmin.panel.changeAct(uAdmin.eip.editorToggleButton.get(0));
		}
	};

	uEditInPlace.prototype.disableEventHandler = function(type) {
		if (type == 'after') {
			uAdmin.panel.changeAct(uAdmin.eip.editorToggleButton.get(0));
		}
	};

	uEditInPlace.prototype.repaintEventHandler = function(type) {
	};

	uEditInPlace.prototype.saveEventHandler = function(type) {
	};

	uEditInPlace.prototype.swapEditor = function() {
		this.onSwapEditor();

		if (this.enabled) {
			this.disable();
			this.editorToggleButton.setLabelText(getLabel('js-panel-edit'));
			jQuery('#on_edit_in_place').html(getLabel('js-on-eip'));
			uAdmin.tickets.enable();
			uAdmin.panel.editInAdmin('enable');
			this.onSwapEnabledEditor();
		}
		else {
			this.enable();
			this.editorToggleButton.setLabelText(getLabel('js-panel-view'));
			jQuery('#on_edit_in_place').html(getLabel('js-off-eip'));
			uAdmin.tickets.disable();
			uAdmin.panel.editInAdmin('disable');
			this.onSwapDisabledEditor();
		}
		this.bindEvents();
	};

	/* onSwapEditor event handler */
	uEditInPlace.prototype.onSwapEditor = function() {
	};

	/* onSwapEnabledEditor event handler */
	uEditInPlace.prototype.onSwapEnabledEditor = function() {
	};

	/* onSwapDisabledEditor event handler */
	uEditInPlace.prototype.onSwapDisabledEditor = function() {
	};

	uEditInPlace.prototype.enable = function() {
		var self = this;
		self.onEnable('before');
		self.finishLast();
		self.inspectDocument();
		self.highlight();
		self.normalizeBoxes();

		jQuery(window).on('load', function() {
			setTimeout(function() {
				self.normalizeBoxes();
			}, 250);
		});

		self.enabled = true;

		if (self.queue.current >= 0) {
			self.queue.setSaveStatus(true);
		}

		var date = new Date();
		date.setTime(date.getTime() + (3 * 24 * 60 * 60 * 1000));
		jQuery.cookie('eip-editor-state', 'enabled', {path: '/', expires: date});

		self.message(getLabel('js-panel-message-edit-on'));
		jQuery(document).bind('keydown', self.bindHotkeys);
		self.onEnable('after');
	};

	uEditInPlace.prototype.disable = function() {
		this.onDisable('before');
		this.finishLast();
		this.unhighlight();

		this.enabled = false;

		jQuery.cookie('eip-editor-state', '', {path: '/', expires: 0});
		this.queue.setSaveStatus(false);
		this.message(getLabel('js-panel-message-edit-off'));

		if (this.queue.current >= 0) {
			this.onDisableWithQueueSave();
		}
		jQuery(document).unbind('keydown', this.bindHotkeys);
		this.onDisable('after');
	};

	/* onDisableWithQueueSave event handler */
	uEditInPlace.prototype.onDisableWithQueueSave = function() {
		var self = this;

		jQuery.openPopupLayer({
			name: 'save',
			width: 200,
			height: 80,
			data: '\n\
            						<div class="eip_win_head popupHeader">\n\
            							<div class="eip_win_close popupClose">&#160;</div>\n\
            						</div>\n\
                                    <div class="eip_win_body popupBody">\n\
                                    <div class="popupText">' + getLabel('js-panel-message-save-confirm') + '</div>\n\
                                    <div class="eip_buttons">\n\
                                        <input type="button" class\
                                        ="primary ok" id="saveProgressBtn" value="OK" />\n\
                                        \<input type="button" id="closeProgressBtn"class\
                                        ="primary back" id="" value="Отмена"/>\n\
                                        <div style="clear: both;" />\n\
                                    </div>\n\
            					'
		});

		jQuery('#saveProgressBtn').click(function() {
			jQuery.closePopupLayer('save');
			uAdmin.eip.queue.save();
		});

		jQuery('#closeProgressBtn').click(function() {
			jQuery.closePopupLayer('save');
		});
	};

	uEditInPlace.prototype.bind = function(event, callback) {
		var self = this,
			f = (typeof self['on' + event] == 'function') ? self['on' + event] : function() {
			};

		self['on' + event] = function(type, options) {
			f(type, options);
			callback(type, options);
		};
	};

	uEditInPlace.prototype.trigger = function(event, type, options) {
		if (typeof this['on' + event] == 'function') {
			this['on' + event](type, options);
		}
	};

	uEditInPlace.prototype.bindHotkeys = function(e) {
		var self = uAdmin.eip.queue;
		if (navigator.userAgent.indexOf('Mac OS') != -1) {
			if (e.metaKey) {
				switch (e.keyCode) {
					case 83:
						self.save();
						break; // Cmd + S
					case 90:
						if (e.shiftKey) {
							self.forward();
						}// Cmd + Z
						else {
							self.back();
						} // Cmd + Shift + Z
						break;
					default:
						return true;
				}
				return false;
			}
		} else {
			if (e.ctrlKey) {
				switch (e.keyCode) {
					case 83:
						self.save();
						break; // Ctrl + S
					case 90:
						self.back();
						break; // Ctrl + Z
					case 89:
						self.forward();
						break; // Ctrl + Y
					default:
						return true;
				}
				return false;
			}
		}

		return true;
	};

	uEditInPlace.prototype.finishLast = function() {
		if (this.previousEditBox) {
			this.previousEditBox.finish(true);
			this.previousEditBox = null;
		}
	};

	uEditInPlace.prototype.normalizeBoxes = function() {
		var self = this;
		jQuery(self.listNodes).each(function(index, node) {
			if (!node.boxNode) {
				return;
			}

			var position = self.nodePositionInfo(node);
			jQuery(node.boxNode).css({
				'width': position.width,
				'height': position.height,
				'left': position.x,
				'top': position.y
			});

			var button = node.addButtonNode;
			var fDim = 'bottom', sDim = 'left';
			if (button) {
				var userPos;
				if ((userPos = jQuery(node).attr('umi:button-position'))) {
					var arr = userPos.split(/ /);
					if (arr.length == 2) {
						fDim = arr[0];
						sDim = arr[1];
					}
				}
				self.placeWith(node, button, fDim, sDim);
			}
		});
		self.onRepaint('after');
	};

	uEditInPlace.prototype.bindEvents = function() {
		var self = this,
			nodes = jQuery('.u-eip-edit-box');

		jQuery(document).off('click drop dragexit dragover', '.u-eip-edit-box');

		nodes.unbind('click');
		nodes.unbind('drop');
		nodes.unbind('dragexit');
		nodes.unbind('dragover');

		var eventString = 'click';

		if (navigator.userAgent.toLowerCase().indexOf('firefox') || navigator.userAgent.toLowerCase().indexOf('chrome')) {
			eventString = eventString + ' drop';

			jQuery(document).on('dragexit', '.u-eip-edit-box', function(e) {
				e.stopPropagation();
				e.preventDefault();
			});

			jQuery(document).on('dragover', '.u-eip-edit-box', function(e) {
				e.stopPropagation();
				e.preventDefault();
			});
		}

		jQuery(document).on(eventString, '.u-eip-edit-box', function(e) {
			var node = e.target;
			if (e.ctrlKey || (navigator.userAgent.indexOf('Mac OS') != -1 && e.metaKey)) {
				if (this.tagName == 'A') {
					location.href = this.href;
				}
				return true;
			}

			var handler = (typeof node.onclick == 'function') ? node.onclick : function() {
			};
			var nullHandler = function() {
				return false;
			};
			node.onclick = nullHandler;
			setTimeout(function() {
				if (node && handler != nullHandler) {
					node.onclick = handler;
				}
			}, 100);

			for (var i = 0; i < 25; i++) {
				if (!node) {
					return false;
				}
				if (node.tagName != 'TABLE' && jQuery(node).attr('umi:field-name')) {
					break;
				}
				node = node.parentNode;
			}
			if (!node) {
				return false;
			}
			e.stopPropagation();
			e.stopImmediatePropagation();
			e.preventDefault();
			self.edit(node, e && e.originalEvent && e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer.files : null);
			e.stopPropagation();
			e.stopImmediatePropagation();
			e.preventDefault();
			return false;
		});

		window.onbeforeunload = function() {
			if (uAdmin.eip.queue.current >= 0 || uAdmin.eip.queue.save.count > 0) {
				return getLabel('js-panel-message-save-before-exit');
			}
		};
	};

	/** Найти все помеченные области, пригодные для редактирования */
	uEditInPlace.prototype.inspectDocument = function() {
		var self = this;

		self.editNodes = [];
		self.listNodes = [];

		var regions = self.getRegions();
		regions.each(function(index, node) {
			if (jQuery(node).css('display') == 'none') {
				return;
			}
			self.inspectNode(node);
		});
	};

	/** Проверить и при необходимости занести в редактируемый список html-элемент */
	uEditInPlace.prototype.inspectNode = function(node) {
		if (node.tagName == 'TABLE') {
			return;
		}
		if (jQuery(node).attr('umi:field-name')) {
			this.editNodes.push(node);
		}
		if (jQuery(node).attr('umi:module')) {
			this.listNodes.push(node);
		}
		// Fix editing behaviour for links child elements in ie
		if (jQuery.browser.msie) {
			jQuery(node).parents('a:first').each(function() {
				var href = this.href;
				jQuery(this).click(function(e) {
					if (e.ctrlKey) {
						window.location.href = href;
					}
				});
				this.removeAttribute('href');
			});
		}
	};

	uEditInPlace.prototype.getRegions = function() {
		return jQuery('*[umi\\:field-name], *[umi\\:module]');
	};

	uEditInPlace.prototype.isParentOf = function(seekNode, excludeNode) {
		if (!excludeNode || !seekNode) {
			return false;
		}
		if (excludeNode == seekNode) {
			return true;
		}
		if (seekNode.parentNode) {
			return this.isParentOf(seekNode.parentNode, excludeNode);
		}
		return false;
	};

	/** Подсветить все редактируемые области */
	uEditInPlace.prototype.highlight = function(excludeNode, skipListNodes) {
		var self = this;
		if (self.highlighted) {
			self.unhighlight();
		}
		self.highlighted = true;

		jQuery(self.editNodes).each(function(index, node) {
			if (self.isParentOf(node, excludeNode) == false) {
				self.highlightNode(node);
			}
		});

		if (!skipListNodes) {
			jQuery(self.listNodes).each(function(index, node) {
				if (self.isParentOf(node, excludeNode) == false) {
					self.highlightListNode(node);
				}
			});
		}

		self.onRepaint('after');
		self.markInversedBoxes();
	};

	/** Снять подсветку с редактируемых блоков */
	uEditInPlace.prototype.unhighlight = function() {

		var n = jQuery('.u-eip-edit-box');

		n.each(function(index, node) {
			node = jQuery(node);
			var empty = node.attr('umi:empty');
			if (empty && (node.attr('tagName') != 'IMG') && (node.html() == empty)) {
				node.html('');
			}

			node.attr('title', '');
		});

		n.removeClass('u-eip-edit-box u-eip-edit-box-hover u-eip-modified u-eip-deleted u-eip-edit-box-inversed');

		n.unbind('click');
		n.unbind('mouseover');
		n.unbind('mouseout');
		n.unbind('mousedown');
		n.unbind('mouseup');

		jQuery('.u-eip-add-box, .u-eip-add-button, .u-eip-del-button').remove();

		jQuery('.u-eip-sortable').sortable('destroy');
		jQuery('.u-eip-sortable').removeClass('u-eip-sortable');
	};

	/** Подсветить редактируемый html-элемент */
	uEditInPlace.prototype.highlightNode = function(node) {
		if (!jQuery(node).attr('umi:field-name')) {
			return;
		}
		var info = this.searchAttr(node);
		if (!info) {
			return;
		}

		var empty = this.htmlTrim(jQuery(node).attr('umi:empty'));

		if (empty && this.htmlTrim(jQuery(node).html()) == '' && (jQuery(node).attr('tagName') != 'IMG')) {
			try {
				jQuery(node).html(empty);
			} catch (e) {
			}
			jQuery(node).addClass('u-eip-empty-field');
		}

		jQuery(node).addClass('u-eip-edit-box');

		if (this.queue.search(info)) {
			jQuery(node).addClass('u-eip-modified');
		}

		if (node.tagName == 'A' || node.parentNode.tagName == 'A' || jQuery('a', node).length > 0) {
			var label = getLabel('js-panel-link-to-go');
			if (navigator.userAgent.indexOf('Mac OS') != -1) {
				label = label.replace(/Ctrl/g, 'Cmd');
			}
			jQuery(node).attr('title', label);
			jQuery(node).bind('dblclick', function() {
				return false;
			});
		}
		else {
			jQuery(node).attr('title', '');
		}

		this.markInversedBoxes(jQuery(node));
	};

	uEditInPlace.prototype.searchAttr = function(node, callback, deep) {
		if (!node) {
			return false;
		}
		var info;
		deep = deep || 20;
		if (this.getAttrSearchReturnCondition(deep, node.tagName)) {
			return false;
		}

		if (!this.searchAttr.info.node && jQuery(node).attr('umi:field-name')) {
			this.searchAttr.info.node = node;
			var fieldName = jQuery(node).attr('umi:field-name');
			if (!fieldName && typeof callback != 'function') {
				this.message('You should specify umi:field-name attribute');
				return false;
			}
			if (!this.searchAttr.info.field_name) {
				this.searchAttr.info.field_name = fieldName;
			}
		}

		var region = jQuery(node);
		if (typeof callback != 'function' || callback(node)) {
			if (region.attr('umi:element-id')) {
				this.searchAttr.info.id = region.attr('umi:element-id');
				this.searchAttr.info.type = 'element';
				info = this.searchAttr.info;
				this.searchAttr.info = {};
				return info;
			}
			else if (region.attr('umi:object-id')) {
				this.searchAttr.info.id = region.attr('umi:object-id');
				this.searchAttr.info.type = 'object';
				info = this.searchAttr.info;
				this.searchAttr.info = {};
				return info;
			}
		}
		if (node.parentNode) {
			return this.searchAttr(node.parentNode, callback, --deep);
		}
		this.message('You should specify umi:element-id or umi:object attribute');
		return false;
	};

	/* Возвращает список тегов в которых
	 * не осуществляется поиск аттрибутов
	 * @param int deep
	 * @param string tagName
	 * @return boolean
	 */
	uEditInPlace.prototype.getAttrSearchReturnCondition = function(deep, tagName) {
		return jQuery.inArray(tagName, ['BODY', 'TABLE']) >= 0;
	};

	uEditInPlace.prototype.searchAttr.info = {};

	uEditInPlace.prototype.searchRow = function(node, parent) {
		if (parent) {
			if (node.tagName == 'BODY' || node.tagName == 'TABLE') {
				return false;
			}
			if (jQuery(node.parentNode).attr('umi:region')) {
				return node.parentNode;
			} else {
				return this.searchRow(node.parentNode, true);
			}
		}
		else {
			return jQuery('*[umi\\:region]', node).filter(function() {
				var selector = '[umi\\:element-id^="new"], [umi\\:object-id^="new"]';
				return !jQuery(this).find(selector).length && !jQuery(this).is(selector);
			}).get(0);
		}
	};

	uEditInPlace.prototype.searchRowId = function(node) {
		var elementId = jQuery(node).attr('umi:element-id');
		return elementId || (jQuery('*[umi\\:element-id]', node).length ? jQuery('*[umi\\:element-id]', node).get(0).attr('umi:element-id') : null);
	};

	uEditInPlace.prototype.inlineAddPage = function(node) {
		var self = this, originalRow = self.searchRow(node);
		if (!originalRow) {
			self.message('Error, umi:region=row is not defined');
			return false;
		}
		node = jQuery(node);
		var parentId = {
				element: node.attr('umi:element-id'),
				object: node.attr('umi:object-id')
			},
			typeId = node.attr('umi:type-id');

		var parentDel = false;
		jQuery('.u-eip-deleted').each(function(i, n) {
			if (self.searchAttr(n).id == (parentId.element || parentId.object)) {
				parentDel = true;
				return;
			}
		});
		if (parentDel) {
			self.message(getLabel('js-panel-message-cant-add'));
			return false;
		}

		if (!typeId && parentId.element) {
			if (parentId.element.match(/^new/g)) {
				var parent = self.queue.search({id: parentId.element});
				typeId = parent.type_id;
			}
			else {
				var data = jQuery.ajax({
					url: '/admin/content/getTypeAdding/' + parentId.element + '/.json',
					async: false,
					dataType: 'json'
				});
				data = JSON.parse(data.responseText);
				typeId = data.result;
			}
		}
		if (!typeId) {
			self.message('Error, umi:type-id is not defined');
			return false;
		}

		var prepend = (node.attr('umi:method') == 'lastlist' || jQuery(node).attr('umi:add-prepend') == 'prepend'),
			rowNode = jQuery(originalRow).clone(),
			newRowNode = (prepend) ? rowNode.prependTo(originalRow.parentNode) : rowNode.appendTo(originalRow.parentNode);

		newRowNode.removeClass('blank_item');

		var searchFieldName = function(node) {
			var item, i;
			if (jQuery(node).attr('umi:field-name')) {
				return node;
			}
			else if (node.children) {
				for (i = 0; i < node.children.length; i++) {
					item = searchFieldName(node.children[i]);
					if (item) {
						return item;
					}
				}
			}
			return false;
		};
		rowNode = searchFieldName(newRowNode.get(0));
		if (!rowNode) {
			self.message('Error, umi:field-name is not defined');
			return false;
		}
		var info = self.searchAttr(rowNode);

		info.id = 'new_' + new Date().getTime();
		info.type_id = typeId;
		info.add = true;
		info.node = newRowNode.get(0);
		if (parentId.object) {
			info.parent = parentId.object;
			info.type = 'object';
		}
		if (parentId.element) {
			info.parent = parentId.element;
			info.type = 'element';
		}
		delete info.field_name;

		if (jQuery(originalRow).attr('umi:' + info.type + '-id') == '') {
			jQuery(originalRow).remove();
			jQuery(newRowNode).attr('umi:' + info.type + '-id', info.id);
			jQuery('*', newRowNode).each(function(i, n) {
				if (!n.children.length) {
					n.style.display = '';
				}
			});
		}

		var typeFields = jQuery.ajax({
			url: '/admin/content/getTypeFields/' + typeId + '/.json',
			async: false,
			dataType: 'json'
		});
		typeFields = jQuery.parseJSON(typeFields.responseText);
		var arTypeFields = [];
		for (var i in typeFields) {
			if (parseInt(i) == i) {
				arTypeFields.push(typeFields[i]);
			}
		}
		arTypeFields.push('name');

		var cleanTags = function(node) {
			var _attr = 'umi:' + info.type + '-id';
			if (jQuery(node).attr('tagName') == 'TABLE') {
				return;
			}

			if (jQuery(node).attr('umi:field-name')) {
				if (jQuery.inArray(jQuery(node).attr('umi:field-name'), arTypeFields) == -1) {
					return false;
				}
				var empty = jQuery(node).attr('umi:empty');

				self.onClearTags(node);

				if (jQuery(node).is('img') && empty) {
					jQuery(node).attr('src', empty);
				} else {
					jQuery(node).html(empty ? empty : '');
				}

				jQuery(node).addClass('u-eip-empty-field');
				self.editNodes[self.editNodes.length] = node;
			}

			if (jQuery(node).attr(_attr)) {
				jQuery(node).attr('umi:clone-source-id', jQuery(node).attr(_attr));
				jQuery(node).attr(_attr, info.id);
			}
		};

		//Delete subregions
		var childRowNode = jQuery('*[umi\\:region="row"]', newRowNode).get(0);
		jQuery('*[umi\\:region="row"]', newRowNode).remove();

		cleanTags(newRowNode);
		newRowNode.addClass('u-eip-newitem');
		var subnodes = jQuery('*', newRowNode);
		subnodes.each(function(index, node) {
			self.inspectNode(node);
			self.highlightListNode(node);
			if (jQuery(node).attr('umi:region')) {
				jQuery(node).html(childRowNode);
				jQuery('*', node).each(function(i, n) {
					n = jQuery(n);
					if (!n.children().length) {
						n.text('');
						n.css('display', 'none');
					}
					if (n.attr('href')) {
						n.attr('href', '');
					}
					if (n.attr('umi:' + info.type + '-id')) {
						n.attr('umi:' + info.type + '-id', '');
					}
				});
			}
			cleanTags(node);
		});

		self.onAfterInlineAdd(newRowNode);

		self.queue.add(info);
		self.normalizeBoxes();
		return true;
	};

	/**
	 * Event "onClearTags" handler
	 */
	uEditInPlace.prototype.onClearTags = function(node) {
	};

	/**
	 * Event "onAfterInlineAdd" handler
	 */
	uEditInPlace.prototype.onAfterInlineAdd = function(newRowNode) {
	};

	/**
	 * Adds new page link after popup closed
	 * @param pageData
	 * @return {Boolean}
	 */
	uEditInPlace.prototype.onSuccessAddNewPage = function(pageData) {
		this.onSuccessAddNewPageBegin(pageData);

		if (!pageData || pageData.forceReload) {
			document.location.reload();
			return false;
		}

		var jqParents = jQuery('[umi\\:element-id=' + pageData.parentId + '][umi\\:region=list]');
		if (!jqParents.length) {
			return false;
		}

		var self = this;

		jqParents.each(function() {
			self.appendNewPageToDOM(pageData, jQuery(this));
		});

		jQuery.closePopupLayer(null, {});

	};

	/**
	 * @param {jQuery} jqElement
	 * @param {Object} data object with properties: {url: 'url', title: 'title', elementId: 'id'}
	 */
	uEditInPlace.prototype.replaceNewPageAttributes = function (jqElement, data) {
		jqElement.removeClass('current blank_item');
		if (jqElement.is('[umi\\:url-attribute]')) {
			jqElement.attr(jqElement.attr('umi:url-attribute'), data.url);
		} else if (jqElement.is('a')) {
			jqElement.attr('href', data.url);
		}

		if (jqElement.is('[umi\\:element-id]')) {
			jqElement.attr('umi:element-id', data.elementId);
		}

		if (jqElement.is('[umi\\:field-name=name]')) {
			jqElement.text(data.title);
		}

		var self = this;
		jqElement.children().each(function() {
			self.replaceNewPageAttributes(jQuery(this), data);
		});
	};

	/* Добавляет в DOM созданный элемент
	 * @param pageData
	 * @param parent
	 */
	uEditInPlace.prototype.appendNewPageToDOM = function (pageData, parent) {
		var newItem = parent.find('[umi\\:region=row]').first().clone();
		this.replaceNewPageAttributes(newItem, pageData);
		newItem.appendTo(parent);
	};

	/* Обрабатывает событие успешного добавления страницы */
	uEditInPlace.prototype.onSuccessAddNewPageBegin = function (pageData) {
	};

	uEditInPlace.prototype.htmlTrim = function(html) {
		html = jQuery.trim(html);
		return html.replace(/<br ?\/?>/g, '').replace(/(<p>)|(<\/p>)/g, '');
	};

	uEditInPlace.prototype.markInversedBoxes = function(nodes) {
		setTimeout(function() {
			if (!nodes) {
				nodes = jQuery('.u-eip-edit-box');
			}
			nodes.each(function(i, node) {
				var color = new RGBColor(jQuery(node).css('color'));
				var colorHash = color.toHash();
				var alpha = (colorHash['red'] / 255 + colorHash['green'] / 255 + colorHash['blue'] / 224) / 3;
				if (alpha >= 0.9) {
					jQuery(node).addClass('u-eip-edit-box-inversed');
				}
			});
		}, 500);
	};

	uEditInPlace.prototype.highlightListNode = function(node) {
		var self = this;
		if (!jQuery(node).attr('umi:module')) {
			return false;
		}

		var box = document.createElement('div');
		document.body.appendChild(box);
		node.boxNode = box;

		var position = self.nodePositionInfo(node);
		if (!position.x && !position.y) {
			return false;
		}

		jQuery(box).attr('class', 'u-eip-add-box');

		jQuery(box).css({
			'position': 'absolute',
			'width': position.width,
			'height': position.height,
			'left': position.x,
			'top': position.y
		});

		if (jQuery(node).attr('umi:add-method') != 'none') {
			this.addAddButton(node, box);
		}

		if (jQuery(node).attr('umi:sortable') == 'sortable') {
			this.setNodeSortable(node, box);
		}

		return box;
	};

	uEditInPlace.prototype.addAddButton = function(node, box) {

		var buttonTag = this.getEipAddButtonTagName(),
			button = document.createElement(buttonTag);

		node.addButtonNode = button;
		jQuery(button).attr({
			'class': 'u-eip-add-button'
		}).html(getLabel('js-panel-add'));

		this.onSetAddButtonText(node, button);
		jQuery(button).hover(function() {
			jQuery(this).addClass('u-eip-add-button-hover');
		}, function() {
			jQuery(this).removeClass('u-eip-add-button-hover');
		});

		this.onSetAddButtonHoverEvents(node, button);

		var fDim = 'bottom';
		var sDim = 'left';
		var userPos;
		if (userPos = jQuery(node).attr('umi:button-position')) {
			var arr = userPos.split(/ /);
			if (arr.length == 2) {
				fDim = arr[0];
				sDim = arr[1];
			}
		}

		this.placeWith(node, button, fDim, sDim);

		var self = this;
		jQuery(button).bind('mouseup', function() {
			self.onAddButtonMouseup(node);
		}).bind('mouseover', function() {
			self.onAddButtonMouseover(box);
		}).bind('mouseout', function() {
			self.onAddButtonMouseout(box);
		});

		document.body.appendChild(button);

	};

	/* onSetAddButtonText event handler */
	uEditInPlace.prototype.onSetAddButtonText = function(node, button) {
	};

	/* onSetAddButtonHoverEvents event handler */
	uEditInPlace.prototype.onSetAddButtonHoverEvents = function(node, button) {
	};

	uEditInPlace.prototype.onAddButtonMouseup = function(node) {
		var self = this;
		var regionType = jQuery(node).attr('umi:region');
		var rowNode = self.searchRow(node);
		var elementId = jQuery(node).attr('umi:element-id');
		var module = jQuery(node).attr('umi:module');
		var method = jQuery(node).attr('umi:method');
		var addMethod = jQuery(node).attr('umi:add-method');
		var typeId = jQuery(node).attr('umi:type-id');

		if (rowNode && (regionType == 'list') && (addMethod != 'popup')) {
			self.inlineAddPage(node);
			self.onListAddButtonMouseUp(jQuery(node));
		}
		else {
			if (self.queue.current >= 0) {
				self.message(getLabel('js-panel-message-save-first'));
				return;
			}

			var url = '/admin/content/eip_add_page/choose/' + parseInt(elementId) + '/' + module + '/' + method + '/',
				sCsrfToken = uAdmin.csrf ? '?csrf=' + uAdmin.csrf : '',
				typeIdPart = typeId ? '&type-id=' + typeId : '';
			jQuery.ajax({
				url: url + '.json' + sCsrfToken,
				dataType: 'json',
				success: function(data) {
					if (data.data.error) {
						uAdmin.eip.message(data.data.error);
						return;
					}
					jQuery.openPopupLayer({
						'name': 'CreatePage',
						'title': getLabel('js-eip-create-page'),
						'url': url + sCsrfToken + typeIdPart
					});

					self.onListAddButtonMouseUp(jQuery(node));
				},
				error: function() {
					uAdmin.eip.message(getLabel('error-require-more-permissions'));
					return;
				}
			});
		}
	};

	/* onListAddButtonMouseUp event handler */
	uEditInPlace.prototype.onListAddButtonMouseUp = function($node) {
	};

	/**
	 * Событие при открытии окна после нажатия кнопки "добавить"
	 */
	uEditInPlace.prototype.onAddButtonMouseupPopupOpened = function () {};

	uEditInPlace.prototype.onAddButtonMouseover = function(box) {
		jQuery(box).addClass('u-eip-add-box-hover');
	};

	uEditInPlace.prototype.onAddButtonMouseout = function(box) {
		jQuery(box).removeClass('u-eip-add-box-hover');
	};

	uEditInPlace.prototype.setNodeSortable = function(node, box) {

		var self = this;

		jQuery(node).addClass('u-eip-sortable');

		var oldNextItem = null, oldParent = null, movingItem,
			parentInfo, isSorting = false, connectedLists = [];
		jQuery('*').each(function(i, n) {
			if (n.tagName == 'TABLE') {
				return;
			}

			if (jQuery(n).attr('umi:sortable') != 'sortable') {
				return;
			}

			// Filter parent nodes
			var isParent = false;
			jQuery(n).parents().each(function(_i, _n) {
				if (_n == node) {
					isParent = true;
				}
			});

			if (isParent) {
				return;
			}

			// Filter child nodes
			var isChild = false;
			jQuery('*', n).each(function(_i, _n) {
				if (_n == node) {
					isChild = true;
				}
			});

			if (isChild) {
				return;
			}

			connectedLists.push(n);
		});

		jQuery(node).sortable({
			'items': '> [umi\\:region="row"]',
			'tolerance': 'pointer',
			'cursor': 'move',
			'dropOnEmpty': true,
			'revert': true,
			'forcePlaceholderSize': true,
			'placeholder': 'u-eip-sortable-placeholder',

			'update': function(event, ui) {
				movingItem = ui.item[0];
				if (!jQuery(movingItem).hasClass('u-eip-newitem') && !(window.cloudController && window.cloudController.onController)) {
					var checkEipMovePage = jQuery.ajax({
						url: '/admin/content/eip_move_page/' + jQuery(movingItem).attr('umi:element-id') + '/.json?check',
						async: false,
						dataType: 'json',
						type: 'GET'
					});
					var result = jQuery.parseJSON(checkEipMovePage.responseText);
					if (result.error) {
						jQuery(node).sortable('cancel');
						uAdmin.eip.message(result.error);
						return false;
					}
				}

				var nextItem = movingItem.nextSibling;

				do {
					if (!nextItem) {
						break;
					}
					if (nextItem.nodeType != 1) {
						continue;
					}
					if (self.searchRowId(nextItem)) {
						break;
					}
				}
				while (nextItem = nextItem.nextSibling);

				oldNextItem = nextItem;
				oldParent = movingItem.parentNode;

				var info = self.searchAttr(movingItem.parentNode, function(node) {
					return jQuery(node).attr('umi:sortable') == 'sortable';
				});
				var parentId = parseInt(info ? info.id : null);
				info.node = movingItem;
				info.move = true;
				info.moved = self.searchRowId(movingItem);
				info.next = nextItem;
				info.old_next = oldNextItem;
				info.parent_id = parentId;
				info.parent = movingItem.parentNode;
				info.old_parent = oldParent;

				delete info.field_name;

				oldNextItem = null;
				oldParent = null;
				self.normalizeBoxes();
				self.queue.add(info);
			}
		});

	};

	/** Получить позиционные параметры html-элемента */
	uEditInPlace.prototype.nodePositionInfo = function(node) {
		node = jQuery(node);

		return {
			'width': node.innerWidth(),
			'height': node.innerHeight(),
			'x': node.offset().left,
			'y': node.offset().top
		};
	};

	uEditInPlace.prototype.placeWith = function(placer, node, fDim, sDim) {
		if (!placer || !node) {
			return;
		}
		var position = this.nodePositionInfo(placer);
		var region = jQuery(node);

		var x, y;
		switch (fDim) {
			case 'top':
				y = position.y - parseInt(region.css('height'));
				break;

			case 'right':
				x = position.x + position.width;
				break;

			case 'left':
				x = position.x - region.width();
				break;

			default:
				y = position.y + position.height;
		}

		if (fDim == 'top' || fDim == 'bottom') {
			switch (sDim) {
				case 'right':
					x = position.x + position.width - region.width();
					break;

				case 'middle':
				case 'center':
					if (position.width - parseInt(region.css('width')) > 0) {
						x = position.x + Math.ceil((position.width - region.width()) / 2);
					} else {
						x = position.x;
					}
					break;

				default:
					x = position.x;
					x += parseInt(jQuery(placer).css('padding-left'));
			}
		}
		else {
			switch (sDim) {
				case 'top':
					y = position.y;
					break;

				case 'bottom':
					y = position.y + position.height - parseInt(region.css('height'));
					break;

				default:
					if (position.height - parseInt(region.css('height')) > 0) {
						y = position.y + Math.ceil((position.height - parseInt(region.css('height'))) / 2);
					} else {
						y = position.y;
					}
			}
		}

		var rightBound = region.width() + x;
		var jWindow = jQuery(window);
		if (rightBound > jWindow.width()) {
			x = jWindow.width() - region.width() - 30;
		}

		try {
			region.css({
				'position': 'absolute',
				'left': x + 'px',
				'top': y + 'px',
				'z-index': 560
			});
		} catch (e) {
		}
	};

	uEditInPlace.prototype.applyStyles = function(originalNode, targetNode, bApplyDimentions) {
		var styles = [
			'font-size', 'font-family', 'font-name',
			'margin-left', 'margin-right', 'margin-top', 'margin-bottom',
			'font-weight'
		], i;
		originalNode = jQuery(originalNode);
		targetNode = jQuery(targetNode);

		for (i in styles) {
			var ruleName = styles[i];
			targetNode.css(ruleName, originalNode.css(ruleName));
		}

		if (bApplyDimentions !== false) {
			targetNode.width(originalNode.outerWidth());
			targetNode.height(originalNode.outerHeight());
		}
	};

	uEditInPlace.prototype.message = function(msg) {
		jQuery.jGrowl('<p>' + msg + '</p>', {
			'header': 'UMI.CMS',
			'life': 10000
		});
	};

	/** Редактировать элемент */
	uEditInPlace.prototype.edit = function(node, files) {
		if (jQuery(node).hasClass('u-eip-deleted') || jQuery(node).parents().hasClass('u-eip-deleted')) {
			this.message(getLabel('js-panel-message-cant-edit'));
			return false;
		}

		this.finishLast();
		jQuery('.eip-del-button').remove();

		this.previousEditBox = this.editor.get(node, files);

		if (this.previousEditBox) {
			jQuery('.u-eip-add-button, .u-eip-add-box').css('display', 'none');
		}

		jQuery(node).removeClass('u-eip-edit-box u-eip-edit-box-hover u-eip-modified u-eip-deleted u-eip-empty-field u-eip-edit-box-inversed');

		if (this.previousEditBox) {
			jQuery(node).addClass('u-eip-editing');
		}
		var empty = this.htmlTrim(jQuery(node).attr('umi:empty'));
		if (empty && this.htmlTrim(jQuery(node).html()) == empty) {
			jQuery(node).html('&nbsp;');
			jQuery(node).removeClass('u-eip-empty-field');
		}
		return true;
	};

	uEditInPlace.prototype.queue = [];

	uEditInPlace.prototype.queue.add = function(rev) {
		if (this.current == -1) {
			this.setSaveStatus(true);
		}
		if (this.current < this.length - 1) {
			for (var i = this.length - 1; i > this.current; i--) {
				this.pop();
			}
			this.current = (this.length);
		} else {
			++this.current;
		}

		this.push(rev);
		this.step();
		if (rev.add) {
			uAdmin.eip.message(getLabel('js-panel-message-add-after-save'));
			jQuery(rev.node).css('display', '');
		}
		if (rev.move) {
			jQuery(rev.node.parentNode).addClass('u-eip-modified');
		}
		if (rev['delete']) {
			uAdmin.eip.message(getLabel('js-panel-message-delete-after-save'));
			jQuery(rev.node).addClass('u-eip-deleted');
		}
		jQuery(rev.node).addClass('u-eip-modified');
		return this.length;
	};

	uEditInPlace.prototype.queue.get = function(revision) {
		if (!parseInt(revision)) {
			revision = this.current;
		}
		return this[revision] || null;
	};

	uEditInPlace.prototype.queue._saveStatus = false;

	uEditInPlace.prototype.queue.setSaveStatus = function(bStatus) {
		var eip = uAdmin.eip;
		if (bStatus) {
			jQuery('#save').addClass('save_me');
			eip.editorToggleButton.setLabelText(getLabel('js-panel-edit-save'));
		} else {
			jQuery('#save').removeClass('save_me');
			eip.editorToggleButton.setLabelText(eip.enabled ? getLabel('js-panel-view') : getLabel('js-panel-edit'));
		}
		uAdmin.eip.queue._saveStatus = bStatus;
	};

	uEditInPlace.prototype.queue.getSaveStatus = function(bStatus) {
		return uAdmin.eip.queue._saveStatus;
	};

	uEditInPlace.prototype.queue.search = function(revision) {
		var i = this.current;
		while (i >= 0) {
			if (this[i].id == revision.id &&
				(
					this[i].field_name == revision.field_name ||
					(this[i].add && revision.add) ||
					(this[i].move && revision.move) ||
					(this[i]['delete'] && revision['delete'])
					|| (this[i].custom && revision.custom)
				)) {
				return this[i];
			}
			--i;
		}
		return false;
	};

	uEditInPlace.prototype.queue.back = function(steps) {
		steps = parseInt(steps) || 1;
		while (steps--) {
			if (this[this.current]) {
				this.cancel();
			}
		}
		uAdmin.eip.normalizeBoxes();
		this.step();
	};

	uEditInPlace.prototype.queue.forward = function(steps) {
		steps = parseInt(steps) || 1;
		while (steps--) {
			if (this[this.current + 1]) {
				this.apply();
			}
		}
		uAdmin.eip.normalizeBoxes();
		this.step();
	};

	uEditInPlace.prototype.queue.apply = function() {
		uAdmin.eip.finishLast();
		++this.current;
		var rev = this.get();
		if (!rev.add && !rev.move && !rev['delete'] && !rev['custom'] && !uAdmin.eip.editor.replace(rev, rev.new_value, rev.old_value)) {
			--this.current;
		}
		else {
			switch (true) {
				case rev.add:
					uAdmin.eip.message(getLabel('js-panel-message-add-after-save'));
					jQuery(rev.node).css('display', '');
					break;
				case rev['delete']:
					uAdmin.eip.message(getLabel('js-panel-message-delete-after-save'));
					jQuery(rev.node).addClass('u-eip-deleted');
					break;
				case rev.move:
					if (rev.next) {
						jQuery(rev.node).insertBefore(rev.next);
					} else {
						jQuery(rev.node).appendTo(rev.parent);
					}
					jQuery(rev.node).addClass('u-eip-modified');
					jQuery(rev.parent).addClass('u-eip-modified');
					break;
				case rev.custom:
					if (rev.target && rev.target.forward) {
						rev.target.forward();
					}
					break;
				default:
					jQuery(rev.node).addClass('u-eip-modified');
			}
		}
		if (this.current == 0) {
			this.setSaveStatus(true);
		}
	};

	uEditInPlace.prototype.queue.cancel = function() {
		uAdmin.eip.finishLast();
		var rev = this.get(), isModified = false;
		switch (true) {
			case rev.add:
				--this.current;
				jQuery(rev.node).css('display', 'none');
				break;
			case rev['delete']:
				--this.current;
				jQuery(rev.node).removeClass('u-eip-deleted');
				break;
			case rev.move:
				--this.current;
				if (rev.old_next) {
					jQuery(rev.node).insertBefore(rev.old_next);
				} else {
					jQuery(rev.node).appendTo(rev.old_parent);
				}
				jQuery(rev.node).removeClass('u-eip-modified');
				if (!this.search(rev)) {
					jQuery(rev.parent).removeClass('u-eip-modified');
				}
				break;
			case rev.custom:
				--this.current;
				if (rev.target && rev.target.back) {
					rev.target.back();
				}
				break;
			default:
				isModified = uAdmin.eip.editor.replace(rev, rev.old_value, rev.new_value);
				if (isModified) {
					--this.current;
					jQuery(rev.node).addClass('u-eip-modified');
				}
		}

		if (rev.add || rev.move || rev['delete'] || rev.custom || isModified) {
			if (!this.search(rev)) {
				jQuery(rev.node).removeClass('u-eip-modified');
			}
			if (this.current == -1) {
				this.setSaveStatus(false);
				uAdmin.eip.message(getLabel('js-panel-message-changes-revert'));
			}
		}
	};

	uEditInPlace.prototype.queue.step = function() {
		jQuery('#u-quickpanel #save_edit #edit_back').attr('class', (this.current == -1) ? '' : 'ac');
		jQuery('#u-quickpanel #save_edit #edit_next').attr('class', ((this.length - this.current) == 1) ? '' : 'ac');
	};

	/* onEipSaveQueue event handler */
	uEditInPlace.prototype.onEipSaveQueue = function(eip) {
	};

	/* onEipSaveQueueOnEdit event handler */
	uEditInPlace.prototype.onEipSaveQueueWithEdit = function(node) {
	};

	uEditInPlace.prototype.queue.save = function(action) {
		uAdmin.eip.finishLast();
		if (this.current == -1 && !action) {
			return false;
		}
		var self = this, node = false, params = {},
			progress = jQuery('div.popupText span', self.progress);

		uAdmin.eip.onEipSaveQueue(this);

		switch (action) {
			case 'add':
				for (i in self.save.add) {
					node = self.save.add[i];
					delete self.save.add[i];
					break;
				}
				if (node) {
					for (i in self.save.added) {
						if (node.parent == i) {
							node.parent = self.save.added[i];
						}
					}
					var uri = '/admin/content/eip_quick_add/' + node.parent + '.json?type-id=' + node.type_id;

					if (jQuery(node.node).parent().attr('umi:module') != 'data') {
						uri += '&force-hierarchy=1';
					}

					var saveCallback = function(data) {
						if (uAdmin.eip.performSaveError.call(self, data, node)) {
							return;
						}

						// Recieve new element id
						var elementId = parseInt(data.data['element-id']);
						var objectId = parseInt(data.data['object-id']);

						self.save.added[node.id] = elementId || objectId;
						jQuery(node.node).removeClass('u-eip-newitem u-eip-modified');
						jQuery(node.node).attr('umi:' + node.type + '-id', elementId || objectId);
						jQuery('*[umi\\:' + node.type + '-id="' + node.id + '"]', node.node).attr('umi:' + node.type + '-id', elementId || objectId);

						var addedNode = jQuery(node.node);
						var newId = elementId || objectId;
						var parentId = node.parent;
						var parentNode = addedNode.parents('[umi\\:region=list]').first();
						var prepend = parentNode.attr('umi:add-prepend') == 'prepend';

						/**
						 * @param {jQuery} target jQuery set with target element
						 * @param {jQuery} source jQuery set with source element
						 * @param {Boolean} withLinks Force replacing href attributes of all target links
						 */
						var replaceAttributes = function(target, source, withLinks) {
							withLinks = !!withLinks;
							if (target.attr('umi:element-id')) {
								target.attr('umi:element-id', source.attr('umi:element-id') || newId);
							}

							if (target.attr('umi:object-id')) {
								target.attr('umi:object-id', source.attr('umi:object-id') || newId);
							}

							if (target.is('img')) {
								target.attr('src', source.attr('src'));
							} else {
								if (withLinks && target.is('a')) {
									target.attr('href', source.attr('href'));
								}

								if (target.is('[umi\\:field-name]')) {
									if (source.attr('umi:empty')) {
										target.attr('umi:empty', source.attr('umi:empty'));
									}

									if (target.attr('umi:empty')) {
										target.addClass('u-eip-empty-field');
									} else {
										target.removeClass('u-eip-empty-field');
									}

									target.text(source.text() || source.attr('umi:empty') || '');
								}
							}
						};

						// Getting elements same as added element's parent
						var parentTypeId = parentNode.attr('umi:type-id');
						jQuery('[umi\\:' + node.type + '-id=' + parentId + '][umi\\:region=list]')
						// filtering added element's parent
							.filter(function() {
								return jQuery(this).find('[umi\\:' + node.type + '-id=' + newId + ']').length == 0;
							})
							// filtering parents by content type
							.filter(function() {
								var result = false,
									nodeTypeId = jQuery(this).attr('umi:type-id');
								if ((!nodeTypeId && !parentTypeId) || nodeTypeId == parentTypeId) {
									result = true;
								}
								return result;
							})
							.each(function() {

								var parentNode = jQuery(this),
									newItem = parentNode.children('[umi\\:region=row]:first').clone().removeClass('blank_item current'),
									linkItem = uAdmin.eip.addPrevOnStack(addedNode.find('*'))
										.filter('[umi\\:url-attribute]').first(); // element that contains 'umi:url-attribute'

								if (linkItem.length) {
									var urlAttribute = linkItem.attr('umi:url-attribute'),
										newLinkItem = uAdmin.eip.addPrevOnStack(newItem.find('*'));
									// if target element contains 'umi:url-attribute' then set it equals to source one
									if (newLinkItem.filter('[umi\\:url-attribute]').attr(urlAttribute, linkItem.attr(urlAttribute)).length == 0) {
										// if not, then apply 'umi:url-attribute' value of source element to target 'a' element
										newLinkItem.filter('a').attr('href', linkItem.attr(urlAttribute));
									}

									// replace other attributes
									replaceAttributes(newItem, addedNode);
								} else {
									replaceAttributes(newItem, addedNode, true);
								}

								// replace attributes for other children elements with umi:field-name attribute
								addedNode.find('[umi\\:field-name]').each(function() {
									var sourceField = jQuery(this);
									var sourceFieldValue = sourceField.attr('umi:field-name');
									newItem.find('[umi\\:field-name=' + sourceFieldValue + ']').each(function() {
										replaceAttributes(jQuery(this), sourceField);
									});
								});
								// insert new element into DOM
								if (prepend) {
									newItem.prependTo(parentNode);
								} else {
									newItem.appendTo(parentNode);
								}

								parentNode.find('[umi\\:field-name]').each(function() {
									uAdmin.eip.highlightNode(this);
								});

							});
						node = false;
						uAdmin.eip.normalizeBoxes();
						progress.text(parseInt(progress.text()) + 1);
						--self.save.count;
						self.save('add');
					};

					if (uAdmin.eip.getSaveAjaxMethod() === 'GET') {
						jQuery.get(uri, saveCallback, 'json');
					} else {
						jQuery.post(uri, saveCallback, 'json');
					}
				} else {
					self.save('move');
				}
				break;
			case 'move':
				for (i in self.save.move) {
					node = self.save.move[i];
					delete self.save.move[i];
					break;
				}
				if (node) {
					node.next = (node.next == null ? '' : uAdmin.eip.searchRowId(node.next));
					for (i in self.save.added) {
						if (node.parent_id == i) {
							node.parent_id = self.save.added[i];
						}
						if (node.moved == i) {
							node.moved = self.save.added[i];
						}
						if (node.next == i) {
							node.next = self.save.added[i];
						}
					}
					jQuery.post('/admin/content/eip_move_page/' + node.moved + '/' + node.next + '.json', {'parent-id': node.parent_id}, function(data) {
						if (data.error) {
							uAdmin.eip.message(data.error);
							return;
						}
						jQuery(node.node).removeClass('u-eip-modified');
						jQuery(node.node).parent().removeClass('u-eip-modified');

						jQuery('[umi\\:region=list]')
							.filter('[umi\\:element-id=' + node.parent_id + '], [umi\\:object-id=' + node.parent_id + ']')
							.filter(function() {
								return this != node.parent;
							})
							.each(function() {
								var newPosition = jQuery(node.node).index(),
									jqNewNode = jQuery(this).find('[umi\\:region=row][umi\\:' + node.type + '-id=' + node.moved + ']'),
									jqTargetNode = jQuery(this).children().eq(newPosition);
								if (jqNewNode.index() > newPosition) {
									jqNewNode.insertBefore(jqTargetNode);
								} else if (jqNewNode.index() < newPosition) {
									jqNewNode.insertAfter(jqTargetNode);
								}
							});
						uAdmin.eip.normalizeBoxes();
						if (uAdmin.eip.enabled) {
							uAdmin.eip.highlight(node.moved);
						}

						uAdmin.eip.message(getLabel('js-panel-message-page-moved'));
						--self.save.count;
						progress.text(parseInt(progress.text()) + 1);
						self.save('move');
					}, 'json');
				} else {
					self.save('edit');
				}
				break;
			case 'edit':
				for (i in self.save.edit) {
					node = self.save.edit[i];
					delete self.save.edit[i];
					break;
				}

				if (node) {
					for (i in self.save.added) {
						if (node.id == i) {
							node.id = self.save.added[i];
						}
					}

					uAdmin.eip.onEipSaveQueueWithEdit(node);

					if (uAdmin.eip.editor.equals(node.new_value, node.old_value)) {
						jQuery(node.node).removeClass('u-eip-modified');
						node = false;
						uAdmin.eip.normalizeBoxes();
						progress.text(parseInt(progress.text()) + 1);
						--self.save.count;
						self.save('edit');
					}
					else {
						params = {};
						params[node.type + '-id'] = node.id;
						params.qt = new Date().getTime();
						params['field-name'] = node.field_name;
						var value, i;
						switch (typeof node.new_value) {
							case 'object':
								if (node.new_value.src) {
									value = node.new_value.src;
								}
								else {
									value = [];
									for (i in node.new_value) {
										value.push(i);
									}
								}
								break;
							case 'string':
								if (jQuery.browser.mozilla && node.new_value.match(/="\.\.\//g)) {
									node.new_value = node.new_value.replace(/="[\.\.\/]+/g, '="/');
								}
								value = node.new_value.replace(/\sumi:[-a-z]+="[^"]*"/g, '');
								break;
							default:
								value = node.new_value;
						}
						params.value = value;

						jQuery.post('/admin/content/editValue/save.json', params, function(data) {
							if (data.error) {
								uAdmin.eip.message(data.error);
								return;
							}

							var newLink = data.property['new-link'],
								oldLink = data.property['old-link'];

							var parentNode = jQuery(node.node).parents('[umi\\:element-id], [umi\\:object-id]').first(),
								parentType = parentNode.is('[umi\\:object-id]') ? 'object' : 'element',
								parentId = parentNode.attr('umi:' + parentType + '-id'),
								rootNodes = jQuery('[umi\\:' + parentType + '-id=' + parentId + ']');

							if (!rootNodes.length) {
								rootNodes = jQuery(node.node).parent();
							}

							if (oldLink && newLink) {
								var iProcessedNodes = 0;
								rootNodes.each(function() {
									jQuery(this).find('[umi\\:url-attribute]').each(function() {
										jQuery(this).attr(jQuery(this).attr('umi:url-attribute'), newLink).bind('click mousedown mouseup', function() {
											return true;
										});
										iProcessedNodes++;
									});
								});
								if (!iProcessedNodes) {
									rootNodes.find('a').each(function(i, n) {
										jQuery(this).attr('href', newLink).bind('click mousedown mouseup', function() {
											return true;
										});
									});
								}
							}

							jQuery('[umi\\:' + node.type + '-id=\'' + node.id + '\']')
								.find('[umi\\:field-name=' + data.property['name'] + ']')
								.add('[umi\\:' + node.type + '-id=\'' + node.id + '\'][umi\\:field-name=' + data.property['name'] + ']')
								.not('[umi\\:' + node.type + '-id][umi\\:' + node.type + '-id!=\'' + node.id + '\']')
								.each(function() {
									var elem = jQuery(this);

									if (elem.is('img')) {
										var value = (data.property.value === null) ? '' : data.property['value']['src'];
										elem.attr('src', value);
									} else {
										elem.html(node.node.innerHTML);
									}

									elem.parents('.not_hidden').removeClass('not_hidden');
								});

							jQuery(node.node).removeClass('u-eip-modified');
							node = false;
							uAdmin.eip.normalizeBoxes();
							progress.text(parseInt(progress.text()) + 1);
							--self.save.count;
							self.save('edit');
						}, 'json');
					}
				} else {
					self.save('custom');
				}
				break;
			case 'custom':

				for (i in self.save.custom) {
					node = self.save.custom[i];
					delete self.save.custom[i];
					break;
				}

				if (node) {

					if (node.target && node.target.save && typeof node.target.save == 'function') {
						node.target.save();
					}

					progress.text(parseInt(progress.text()) + 1);
					--self.save.count;
					self.save('custom');
				} else {
					self.save('del');
				}
				break;
			case 'del':
				for (i in self.save.del) {
					node = self.save.del[i];
					delete self.save.del[i];
					break;
				}
				if (node) {
					for (i in self.save.added) {
						if (node.id == i) {
							node.id = self.save.added[i];
						}
					}
					params = {};
					params[node.type + '-id'] = node.id;
					params.qt = new Date().getTime();

					jQuery.ajax({
						type: 'POST',
						url: '/admin/content/eip_del_page.json',
						data: params,
						dataType: 'json',
						success: function(data) {
							if (data.error) {
								uAdmin.eip.message(data.error);
								return;
							}

							var rowNode = uAdmin.eip.searchRow(node.node, true);

							if (rowNode && jQuery(rowNode).attr('umi:region') != 'list') {
								jQuery(rowNode).remove();
							} else {
								jQuery(node.node).remove();
							}

							jQuery('[umi\\:element-id=' + node.id + '], [umi\\:object-id=' + node.id + ']').remove();
							uAdmin.eip.normalizeBoxes();
							node = false;
							uAdmin.eip.normalizeBoxes();
							progress.text(parseInt(progress.text()) + 1);
							--self.save.count;
							self.save('del');
						},
						error: function(response) {
							var message = getLabel('js-label-request-error');

							if (response.status === 403 && response.responseJSON && response.responseJSON.data && response.responseJSON.data.error) {
								message = response.responseJSON.data.error;
							}

							uAdmin.eip.message(message);
						}
					});
				} else {
					self.save.add = {};
					self.save.added = {};
					self.save.move = {};
					self.save.edit = {};
					self.save.del = {};
					self.save.custom = {};
					self.setSaveStatus(false);
					this.step();

					var message = uAdmin.eip.getDeleteMessageOfEipSave(parseInt(progress.text()) === 0);
					uAdmin.eip.message(message);
					jQuery('input:button', self.progress).removeClass('hidden');
					uAdmin.eip.onSave('after');
				}

				break;

			default:
				uAdmin.eip.onSave('before');
				while (0 <= this.current) {
					if (self[0].add) {
						self.save.add[self[0].id] = self[0];
						++self.save.count;
					}
					else if (this[0].move) {
						if (self.save.move[self[0].moved]) {
							delete self.save.move[self[0].moved];
							--self.save.count;
						}
						self.save.move[self[0].moved] = self[0];
						++self.save.count;
					}
					else if (this[0]['delete']) {
						self.save.del[self[0].id] = self[0];
						++self.save.count;
					}
					else if (this[0]['custom']) {
						self.save.custom[self[0].id] = self[0];
						++self.save.count;
					}
					else {
						if (self.save.edit[self[0].id + '_' + self[0].field_name]) {
							self.save.edit[self[0].id + '_' + self[0].field_name].new_value = self[0].new_value;
						}
						else {
							self.save.edit[self[0].id + '_' + self[0].field_name] = self[0];
							++self.save.count;
						}
					}
					self.shift();
					--self.current;
				}

				self.progress = jQuery.openPopupLayer({
					name: 'SaveProgress',
					width: 400,
					height: 80,
					data: '\n\
						<div class="eip_win_head popupHeader">\n\
							<div class="eip_win_close popupClose hidden">&#160;</div>\n\
							<div class="eip_win_title">' + getLabel('js-cms-eip-edit_in_place-save_processing') + '</div>\n\
						</div>\n\
						<div class="eip_win_body popupBody">\n\
							<div class="popupText">' + getLabel('js-cms-eip-edit_in_place-saved_count_modify', self.save.count) + '</div>\n\
							<div class="eip_buttons">\n\
								<input type="button" class="primary ok hidden" value="OK" />\n\
								<div style="clear: both;" />\n\
							</div>\n\
						</div>\n\
					'
				}).find('#popupLayer_SaveProgress');

				jQuery('input:button', self.progress).click(function() {
					jQuery.closePopupLayer('SaveProgress');

					uAdmin.eip.onSaveFinish();
				});

				self.save('add');
		}
		return false;
	};

	/**
	 * Возвращает тип ajax запроса eip при сохранении
	 * @returns string
	 */
	uEditInPlace.prototype.getSaveAjaxMethod = function() {
		return 'GET';
	};

	/**
	 * Обрабатывает ошибку запроса eip при сохранении
	 * @param object data
	 * @param object node
	 * @returns string
	 */
	uEditInPlace.prototype.performSaveError = function(data, node) {
		if (data && data.error) {
			uAdmin.eip.message(data.error);
			return true;
		}

		return false;
	};

	/**
	 * Удаляет из очереди на сохранение все действия связанные с элементом,
	 * который не удалось добавить.
	 * @param elementId string - идентифкатор элемента
	 */
	uEditInPlace.prototype.removeAddFail = function(elementId) {
	};

	/**
	 * Возвращает сообщение при сохранении операций удаления
	 * @param isNotStarted
	 * @returns string
	 */
	uEditInPlace.prototype.getDeleteMessageOfEipSave = function(isNotStarted) {
		return getLabel('js-panel-message-changes-saved');
	};

	/**
	 * Возвращает разрешение на инициализацию редактора
	 * изображений в процедуре активации модуля редактирования EditModule
	 * @returns boolean
	 */
	uEditInPlace.prototype.isImageEditorReinitEnabled = function(name) {
		return true;
	};

	/** onEditModuleActivate event handler */
	uEditInPlace.prototype.onEditModuleActivate = function() {
	};


	/** onSaveFinish event handler */
	uEditInPlace.prototype.onSaveFinish = function() {
	};

	/** Return base tag for eip add button
	 *  @returns string
	 */
	uEditInPlace.prototype.getEipAddButtonTagName = function() {
		return 'a';
	};

	/**
	 * Return relation draw apply dimentions param
	 * @returns boolean|null
	 */
	uEditInPlace.prototype.isRelationDrawApplyDimentions = function() {
		return null;
	};

	/**
	 * Add the previous set of elements on the stack to the current set.
	 * @param elements
	 * @returns {}
	 */
	uEditInPlace.prototype.addPrevOnStack = function(elements) {
		return elements.addBack();
	};

	/**
	 * Get true when has custom bind finish event return
	 * @returns boolean
	 */
	uEditInPlace.prototype.isBindFinishEventCustomReturn = function(target) {
		return false;
	};

	/**
	 * Get search field width or false if default
	 * @returns int
	 */
	uEditInPlace.prototype.getRelationSearchFieldWidth = function(box) {
		return 0;
	};

	/**
	 * Get flag of cleanup  html on wysiwyg ctrl shift
	 * @returns boolean
	 */
	uEditInPlace.prototype.isCleanupHtmlOnWysiwygCtrlShift = function() {
		return true;
	};

	/**
	 * Get MSIE stub image usage flag
	 * @returns boolean
	 */
	uEditInPlace.prototype.getMSIEStubImgCondition = function() {
		return true;
	};

	/* onTinymceInitEditorTune event handler */
	uEditInPlace.prototype.onTinymceInitEditorTune = function(settings) {
	};

	/**
	 * Запускает ознакомительные подсказки по сайту
	 */
	uEditInPlace.prototype.showWizard = function() {
	};

	/**
	 * Возвращает признак того, что редактируемое изображение является слайдером
	 * @param editor
	 * @return boolean
	 */
	uEditInPlace.prototype.isEditedImageTypeSlider = function(editor) {
		return editor && editor.iImageType && editor.iImageType == editor.IMAGE_TYPE_SLIDER;
	};

	/**
	 * Возвращает url всплывающего диалога редактирования слайдера
	 * @param editor
	 * @return string
	 */
	uEditInPlace.prototype.getSliderEditPopupLayerUrl = function(editor) {
		var queryParams = {
			'slider_id': $(editor.jqImgNode[0]).attr('umi:slider-id'),
			'token': uAdmin['csrf'],
			'prefix': uAdmin['lang_prefix']
		};

		return '/styles/common/other/slidereditor/slideEditor.html?' + $.param(queryParams);
	};

	uEditInPlace.prototype.queue.save.add = {};
	uEditInPlace.prototype.queue.save.added = {};
	uEditInPlace.prototype.queue.save.move = {};
	uEditInPlace.prototype.queue.save.edit = {};
	uEditInPlace.prototype.queue.save.del = {};
	uEditInPlace.prototype.queue.save.custom = {};
	uEditInPlace.prototype.queue.save.count = 0;

	uEditInPlace.prototype.queue.current = -1;

	uEditInPlace.prototype.enabled = false;
	uEditInPlace.prototype.previousEditBox = null;
	uEditInPlace.prototype.editNodes = [];
	uEditInPlace.prototype.listNodes = [];
	uEditInPlace.prototype.meta = {};

	return extend(uEditInPlace, this);
});
