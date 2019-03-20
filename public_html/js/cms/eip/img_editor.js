uAdmin('.ieditor', function (extend) {

	var settings = {

		backend_request_url: '/udata://content/ieditor/',
		backend_request_method: 'POST',
		image_data_url: '/admin/content/getImageData.json',
		image_data_request_method: 'POST',
		coockie_name: 'eip_ieditor_state',
		preloader_src: '/images/cms/eip/loader.gif',
		preloader_holder_css_class: 'eip-ieditor-preloader',

		collection: {
			editor_id_attribute: 'data-ieditor-id'
		},

		editor: {
			menu_wrapper_css_class: 'eip-ieditor-menu-wrapper',
			img_wrapper_css_class: 'eip-ieditor-img-wrapper',
			animation_speed: 100
		},

		layout: {
			extended: {
				css_class: 'eip-ieditor-layout-extended',
				right_margin: '5px',
				bottom_margin: '5px'
			},
			simple: {
				css_class: 'eip-ieditor-layout-simple',
				right_margin: '5px',
				bottom_margin: '5px'
			},
			bubble: {
				css_class: 'eip-ieditor-layout-bubble',
				arrow_css_class: 'eip-ieditor-layout-bubble-arrow',
				bottom_margin: -50,
				bottom_margin_delta: 0
			},
			big_img_min_width: 300,
			big_img_min_height: 350,
			small_img_max_width: 150,
			small_img_max_height: 150
		},

		module: {
			module_css_class: 'eip-ieditor-module',
			icon_holder_css_class: 'eip-ieditor-module-icon',
			title_holder_css_class: 'eip-ieditor-module-title',
			upload_module: {
				url: '/udata/content/ieditor/upload.json',
				iframe_name: 'eip-ieditor-upload-iframe',
				file_input_name: 'eip-ieditor-upload-fileinput[]',
				css_class: 'eip-ieditor-module-upload'
			},
			filemanager_module: {
				css_class: 'eip-ieditor-module-filemanager'
			},
			popup_module: {
				css_class: 'eip-ieditor-module-popup',
				fancybox_css_class: 'fancybox-group',
				wrapper_css_class: 'eip-ieditor-module-popup-wrapper',
				thumb_width: 100
			},
			delete_module: {
				css_class: 'eip-ieditor-module-delete'
			},
			apply_module: {
				css_class: 'eip-ieditor-module-apply'
			},
			cancel_module: {
				css_class: 'eip-ieditor-module-cancel'
			}
		},

		img_area_select: {
			css_class: 'eip-ieditor-imgareaselect-wrapper',
			plugin_options: {
				parent: null,
				hide: true,
				handles: true,
				instance: true,
				keys: true,
				movable: true,
				persistent: true,
				resizeMargin: 10,
				zIndex: 100001
			}
		},

		filemanager: {
			url: '/styles/common/other/elfinder/umifilebrowser.html?lang=ru',
			window_width: 1200,
			window_height: 600
		},

		browser_modules: {
			msie: [FilemanagerModule, SliderModule, PopupModule, ApplyModule, CancelModule, DeleteModule],
			mozilla: [FilemanagerModule, SliderModule, PopupModule, UploadModule, ApplyModule, CancelModule, DeleteModule],
			opera: [FilemanagerModule, SliderModule, PopupModule, UploadModule, ApplyModule, CancelModule, DeleteModule],
			webkit: [FilemanagerModule, SliderModule, PopupModule, UploadModule, ApplyModule, CancelModule, DeleteModule]
		}

	};

	uAdmin.inherit = function (Child, Parent) {
		var F = function() {};
		F.prototype = Parent.prototype;
		Child.prototype = new F();
		Child.prototype.constructor = Child;
		Child.superclass = Parent.prototype;
	};

	var uImageEditor = function () {

		if (!uAdmin || !uAdmin.eip) {
			throw "Edit-in-place is not initialized";
		}

		this.COOKIE_NAME = settings.coockie_name;
		this.COOKIE_ENABLED_VALUE = 'enabled';
		this.COOKIE_DISABLED_VALUE = 'disabled';

		this.jqToggleButton = null;
		this.bEnabled = true;
		this.skipDeactivateClick = false;

		var self = this;
		uAdmin.eip.bind('Enable', function(type){
			if (type == 'after') {
				self.addPanelButton();
			}
		});
		uAdmin.eip.bind('Disable', function(type){
			if (type == 'after') {
				self.removePanelButton();
				self.disable();
			}
		});
		uAdmin.eip.bind('ActiveEditorCommit', function(type){
			if (type == 'before' && uAdmin.eip.editor.info.field_type == 'wysiwyg' && uAdmin.eip.editor.info.old_value && uAdmin.eip.editor.info.new_value) {
				uAdmin.eip.editor.info.old_value = uAdmin.eip.editor.info.old_value.replace(/\sdata-ieditor-id=["']{1}[0-9\._]+["']{1}/gi, '');
				uAdmin.eip.editor.info.new_value = uAdmin.eip.editor.info.new_value.replace(/\sdata-ieditor-id=["']{1}[0-9\._]+["']{1}/gi, '');
				uAdmin.eip.editor.info.old_value = uAdmin.eip.editor.info.old_value.replace(/(src=".*)\?[0-9]+/gi, "$1");
				uAdmin.eip.editor.info.new_value = uAdmin.eip.editor.info.new_value.replace(/(src=".*)\?[0-9]+/gi, "$1");
			} else {
				uAdmin.eip.normalizeBoxes();
			}
		});
		uAdmin.eip.bind('AddPhotoToAlbum', function(type, options){
			if (type == 'after' && options && options.newNode && options.newNode.length) {
				var sAttr = ImageEditorsCollection.getInstance().EDITOR_ID_ATTRIBUTE,
					imageNodes = options.newNode.find("img[" + sAttr + "]");

				uAdmin.eip.addPrevOnStack(imageNodes).removeAttr(sAttr);

				if (!uAdmin.ieditor.isEnabled()) return;
				var imgNode = options.newNode.find('img')[0],
					jqImgNode = jQuery(imgNode),
					oEditor = ImageEditorsCollection.getInstance().initEditor(imgNode);
				if (oEditor instanceof ImageEditorBase) {
					jqImgNode.on('load', function(){
						oEditor.init(jqImgNode, jqImgNode.attr(sAttr));
					});
				}
			}
		});

		uAdmin.onLoad('wysiwyg', function(){
			tinymce.on("AddEditor", function(oEvent){
				oEvent.editor.on('SetAttrib', function(oEvent){
					jQuery(oEvent.attrElm).on('load', function(oEvent){
						if (!uAdmin.ieditor.isEnabled()) return;
						var oImgEditor = ImageEditorsCollection.getInstance().initEditor(this);
						if (oImgEditor.getEditState()) return;
						oImgEditor.destroy(true);
						oImgEditor.init(jQuery(this), jQuery(this).attr(ImageEditorsCollection.getInstance().EDITOR_ID_ATTRIBUTE));
						window.setTimeout(function(){
							tinymce.activeEditor.selection.collapse();
						}, 0);
					});
				});
				oEvent.editor.on('init', function(oEvent){
					var arImages = this.dom.select('img:not(.mce-object)');
					if (uAdmin.ieditor.isEnabled() && arImages && arImages.length) {
						uAdmin.ieditor.enable(arImages);
					}
				});

				var originalOpen = oEvent.editor.windowManager.open;
				oEvent.editor.windowManager.open = function() {
					uAdmin.ieditor.disable();

					var win = originalOpen.apply(this, arguments);
					win.on('close', function onClose() {
						setTimeout(function() {
							uAdmin.ieditor.enable()
						}, 500);
					});
					return win;
				}
			});
		});

		jQuery("body").on('click', function(oEvent){
			if (
				(jQuery(oEvent.target).closest("." + settings.collection.editor_id_attribute + ", ." + settings.img_area_select.css_class).length) ||
				(jQuery(oEvent.target).is(".mce-resizehandle, img"))
			) {
				return;
			}

			var oEditor = ImageEditorsCollection.getInstance().getActiveEditor();

			if ((oEditor instanceof ImageEditorBase === false) || (uAdmin.ieditor.skipDeactivateClick)) {
				return
			};

			var oEnabledModule = oEditor.getEnabledModule();
			oEnabledModule.cancel();
			oEnabledModule.deactivate();

			oEditor.hide();
			oEditor.switchOn();
			oEditor.redrawMenu();
			oEditor.drawDeleteButton();
		});

		if (uAdmin.eip.enabled) {
			this.addPanelButton();
		}

	};

	uImageEditor.prototype.isEnabled = function () {
		return this.bEnabled;
	};

	uImageEditor.prototype.getNodes = uImageEditor.getNodes = function (bReturnJquery) {
		jqNodes = jQuery("img[umi\\:field-name], .mceEditor img, [umi\\:field-type='wysiwyg'] img, img[umi\\:slider-id]");
		return bReturnJquery ? jqNodes : jqNodes.toArray();
	};

	uImageEditor.prototype.enable = function (arNodes) {
		if (!uAdmin.eip.enabled) return;
		if (!arNodes) arNodes = this.getNodes();
		var oEditorsCollection = ImageEditorsCollection.getInstance(),
			oActiveEditor = oEditorsCollection.getActiveEditor();
		for (var i = 0; i < arNodes.length; i++) {
			if (oEditorsCollection.getEditorByNode(arNodes[i])) {
				if (!oActiveEditor || oActiveEditor.jqImgNode[0] !== arNodes[i]) {
					oEditorsCollection.getEditorByNode(arNodes[i]).destroy();
					oEditorsCollection.initEditor(arNodes[i]);
				}
			} else {
				oEditorsCollection.initEditor(arNodes[i]);
			}
		}
		this.removeEipDeleteButtons();
		this.bEnabled = true;
		this.jqToggleButton.addClass('act');
	};

	uImageEditor.prototype.disable = function () {
		if (!this.isEnabled()) return;
		var oActiveEditor = ImageEditorsCollection.getInstance().getActiveEditor();
		if (oActiveEditor instanceof ImageEditorBase) {
			oActiveEditor.getEnabledModule() && oActiveEditor.getEnabledModule().cancel();
		}
		ImageEditorsCollection.getInstance().removeAllEditors();
		ImageEditorsCollection.getInstance().turnOffEditMode();
		this.addEipDeleteButtons();
		this.bEnabled = false;
		this.jqToggleButton.removeClass('act');
	};

	uImageEditor.prototype.reinit = function () {
		ImageEditorsCollection.getInstance().reinitActiveEditors();
	};

	uImageEditor.prototype.setSettings = function (oSettings) {
		var applySettings = function (oNewSettings, oOldSettings) {
			for (var param in oNewSettings) {
				if (!oNewSettings.hasOwnProperty(param) || !oOldSettings.hasOwnProperty(param)) continue;
				if (typeof oNewSettings[param] === 'object' && oNewSettings[param] instanceof Object) {
					applySettings(oNewSettings[param], oOldSettings[param]);
				} else {
					oOldSettings[param] = oNewSettings[param];
				}
			}
		};
		applySettings(oSettings, settings);
		ImageEditorsCollection.getInstance().reinitActiveEditors();
	};

	uImageEditor.prototype.getEditorsCollection = function () {
		return ImageEditorsCollection.getInstance();
	};

	uImageEditor.prototype.removeEipDeleteButtons = function () {
		this.jqEipDeleteButtonsNodes = this.getNodes(true);
		if (!this.jqEipDeleteButtonsNodes.length) return;
		var jqParentNodes = this.jqEipDeleteButtonsNodes.parents("[umi\\:delete]").filter(function(index, node){
			return !!jQuery(this).parents("[umi\\:module='photoalbum']").length;
		});
		this.jqEipDeleteButtonsNodes = this.jqEipDeleteButtonsNodes.filter('[umi\\:delete]').add(jqParentNodes);
		this.jqEipDeleteButtonsNodes.removeAttr('umi:delete');
		uAdmin.eip.dropDeleteButtons();
	};

	uImageEditor.prototype.addEipDeleteButtons = function () {
		if (!this.jqEipDeleteButtonsNodes || !this.jqEipDeleteButtonsNodes.length) return;
		this.jqEipDeleteButtonsNodes.attr('umi:delete', 'delete');
		uAdmin.eip.dropDeleteButtons();
	};

	uImageEditor.prototype.addPanelButton = function () {
		var self = this;
		this.jqToggleButton = jQuery("<div/>");
		this.jqToggleButton.attr('id', 'ieditor-switcher')
			.text(getLabel('js-ieditor-switcher'))
			.appendTo(uAdmin.panel.quickpanel)
			.on('click', function(){
				if (self.isEnabled()) {
					self.disable();
				} else {
					self.enable();
				}
				self.saveStateToCookie();
			});
		jQuery('<span class="in_ico_bg">').prependTo(this.jqToggleButton);
		this.applyStateFromCookie();
	};

	uImageEditor.prototype.removePanelButton = function () {
		this.jqToggleButton && this.jqToggleButton.length && this.jqToggleButton.remove();
	};

	uImageEditor.prototype.applyStateFromCookie = function () {
		if (!jQuery.cookie) return;
		if (jQuery.cookie(this.COOKIE_NAME) == this.COOKIE_ENABLED_VALUE) {
			this.enable();
		} else if (jQuery.cookie(this.COOKIE_NAME) == this.COOKIE_DISABLED_VALUE) {
			this.disable();
		} else {
			this.enable();
		}
	};

	uImageEditor.prototype.saveStateToCookie = function () {
		if (!jQuery.cookie) return;
		var state = this.isEnabled() ? this.COOKIE_ENABLED_VALUE : this.COOKIE_DISABLED_VALUE,
			date = new Date();
		date.setTime(date.getTime() + (3 * 24 * 60 * 60 * 1000));
		jQuery.cookie(this.COOKIE_NAME, state, { path: '/', expires: date});
	};


	/* ================================== ENTITY ================================= */

	var EntityBase = function () {

		this.oEventHandlers = {};

	};

	EntityBase.prototype.bindEvent = function (sEventName, fEventHandler) {
		if (typeof fEventHandler != 'function') return false;

		if (!this.oEventHandlers[sEventName]) {
			this.oEventHandlers[sEventName] = [];
		}
		this.oEventHandlers[sEventName].push({'context': this, 'handler': fEventHandler});
		return true;
	};

	EntityBase.prototype.triggerEvent = function (sEventName, arEventHandlerParams) {
		var arEventHandlers = this._getHandlersForObjectEvent(sEventName, this);
		if (!arEventHandlers || !arEventHandlers.length) {
			return false;
		}
		for (var i = 0; i < arEventHandlers.length; i++) {
			arEventHandlers[i].apply(this, arEventHandlerParams);
		}
		return true;
	};

	EntityBase.prototype._getHandlersForObjectEvent = function (sEventName, oContext) {
		var arEventListeners = this.oEventHandlers[sEventName];
		if (!arEventListeners) {
			return [];
		}
		var arEventHandlers = [];
		for (var i in arEventListeners) {
			if (arEventListeners.hasOwnProperty(i) && arEventListeners[i].context === oContext && typeof arEventListeners[i].handler == 'function') {
				arEventHandlers.push(arEventListeners[i].handler);
			}
		}
		return arEventHandlers;
	};


	/* ================================== COLLECTION ================================= */

	var ImageEditorsCollection = function () {

		this.settings = settings;

		this.EDITOR_ID_ATTRIBUTE = settings.collection.editor_id_attribute;
		this.BIG_IMG_MIN_WIDTH = settings.collection.big_img_min_width;
		this.BIG_IMG_MIN_HEIGHT = settings.collection.big_img_min_height;
		this.SMALL_IMG_MAX_WIDTH = settings.collection.small_img_max_width;
		this.SMALL_IMG_MAX_HEIGHT = settings.collection.small_img_max_height;

		this._arEditors = {};

		this.oActiveEditor = null;
		this.bEditMode = false;

		jQuery("body")
			.on("mouseenter", "img", function(oEvent){
				if (!uAdmin.eip.enabled || !uAdmin.ieditor.isEnabled()) return false;
				if (ImageEditorsCollection.getInstance().isInEditState()) return false;
				var jqImgNode = jQuery(this),
					sEditorIdAttribute = ImageEditorsCollection.getInstance().EDITOR_ID_ATTRIBUTE;
				if (!jqImgNode.attr(sEditorIdAttribute)) return false;
				var oEditor = ImageEditorsCollection.getInstance().initEditor(this);
				oEditor.init(jqImgNode, jqImgNode.attr(sEditorIdAttribute));
				oEditor.show();
				window.setTimeout(function(){
					if (!oEditor.isImageInFocus()) {
						oEditor.hide(function(){
							oEditor.destroy(true);
						});
					}
				}, 100);
				return true;
			})
			.on('mousemove', function(oEvent){
				window.mouseX = oEvent.pageX || oEvent.clientX + jQuery(document).scrollLeft();
				window.mouseY = oEvent.pageY || oEvent.clientY + jQuery(document).scrollTop();
			});

	};

	ImageEditorsCollection._instance = null;

	ImageEditorsCollection.getInstance = function () {
		if (!ImageEditorsCollection._instance) {
			ImageEditorsCollection._instance = new ImageEditorsCollection();
		}
		return ImageEditorsCollection._instance;
	};

	ImageEditorsCollection.cleanupHtml = function (sHtml) {
		return new ImageEditorBase.cleanupHtml(sHtml);
	};

	ImageEditorsCollection.prototype.initEditor = function (imgNode) {

		if (ImageEditorsCollection.getInstance().isInEditState()) {
			return new ImageEditorVoid();
		}

		if (this.getEditorByNode(imgNode)) {
			return this.getEditorByNode(imgNode);
		}

		var jqImgNode = jQuery(imgNode),
			sNodeUniqueId = this.getUniqueId();

		jqImgNode.attr(this.EDITOR_ID_ATTRIBUTE, sNodeUniqueId);

		var oEditor = new ImageEditor();

		this._arEditors[sNodeUniqueId] = oEditor;

		return oEditor;

	};

	ImageEditorsCollection.prototype.reinitActiveEditors = function () {
		var arActiveEditors = [];
		for (var i in this._arEditors) {
			if (!this._arEditors.hasOwnProperty(i) || this._arEditors[i] instanceof ImageEditorBase === false) continue;
			arActiveEditors.push(this._arEditors[i].jqImgNode[0]);
		}
		this.removeAllEditors();
		for (i = 0; i < arActiveEditors.length; i++) {
			this.initEditor(arActiveEditors[i]);
		}
	};

	ImageEditorsCollection.prototype.updateEditorsByNodes = function (context) {
		jQuery("img", context)
			.filter(function(){
				return !!jQuery(this).attr(ImageEditorsCollection.getInstance().EDITOR_ID_ATTRIBUTE);
			})
			.each(function(i, node){
				var oEditor = ImageEditorsCollection.getInstance().initEditor(node);
				oEditor.reinit();
			});
	};

	ImageEditorsCollection.prototype.repositionActiveEditors = function () {
		for (var i in this._arEditors) {
			if (!this._arEditors.hasOwnProperty(i) || this._arEditors[i] instanceof ImageEditorBase === false) continue;
			if (!this._arEditors[i].jqImgNode || !this._arEditors[i].jqImgNode.length || !this._arEditors[i].jqImgNode.is(":visible")) {
				this._arEditors[i].destroy();
				continue;
			}
			this._arEditors[i].reinit();
		}
	};

	ImageEditorsCollection.prototype.getEditorByNode = function (imgNode) {
		return this._arEditors[jQuery(imgNode).attr(this.EDITOR_ID_ATTRIBUTE)];
	};

	ImageEditorsCollection.prototype.getEditorByEditorId = function (sEditorId) {
		return this._arEditors[sEditorId];
	};

	ImageEditorsCollection.prototype.getAllEditors = function () {
		return this._arEditors;
	};

	ImageEditorsCollection.prototype.findEditors = function (context) {
		var jqImgNode = jQuery("img["+this.EDITOR_ID_ATTRIBUTE+"]", context),
			arEditors = [],
			self = this;
		jqImgNode.each(function(index, node){
			arEditors.push(self.getEditorByNode(node));
		});
		return arEditors;
	};

	ImageEditorsCollection.prototype.removeAllEditors = function () {
		for (var sEditorId in this._arEditors) {
			if (!this._arEditors.hasOwnProperty(sEditorId)) continue;
			this.removeEditor(sEditorId);
		}
		this.oActiveEditor = null;
		this._arEditors = {};
	};

	ImageEditorsCollection.prototype.removeEditor = function (sEditorId) {
		this._arEditors[sEditorId].destroy();
		delete this._arEditors[sEditorId];
	};

	ImageEditorsCollection.prototype.removeEmptyEditors = function () {
		var arImgEditors = this.getAllEditors();
		for (var i in arImgEditors) {
			if (!arImgEditors.hasOwnProperty(i)) continue;
			var oImgEditor = arImgEditors[i];
			if (oImgEditor instanceof ImageEditorBase === false) continue;
			if (!oImgEditor.getEditorId()) continue;
			if (!oImgEditor.jqImgNode || !oImgEditor.jqImgNode.length || !oImgEditor.jqImgNode.is(':visible')) {
				this.removeEditor(oImgEditor.getEditorId());
			}
		}
	};

	ImageEditorsCollection.prototype.getUniqueId = function () {
		var sId = '';
		do {
			sId = new Date().getTime() + '_' + Math.random();
		} while (sId in this._arEditors);
		return sId;
	};

	ImageEditorsCollection.prototype.setActiveEditor = function (oEditor) {
		if (this.oActiveEditor && this.oActiveEditor.deactivateEnabledModule) {
			if (this.oActiveEditor.deactivateEnabledModule(true)){
				this.oActiveEditor = oEditor;
				return true;
			} else {
				return false;
			}
		} else {
			this.oActiveEditor = oEditor;
			return true;
		}
	};

	ImageEditorsCollection.prototype.getActiveEditor = function () {
		return this.oActiveEditor;
	};

	ImageEditorsCollection.prototype.deactivateActiveEditor = function () {
		return this.setActiveEditor(null);
	};

	ImageEditorsCollection.prototype.turnOnEditMode = function () {
		this.bEditMode = true;
	};

	ImageEditorsCollection.prototype.turnOffEditMode = function () {
		this.bEditMode = false;
	};

	ImageEditorsCollection.prototype.isInEditState = function () {
		return this.bEditMode;
	};

	ImageEditorsCollection.prototype.each = function (callback) {
		if (typeof callback != 'function') return;
		for (var i = 0; i < this._arEditors; i++) {
			var oEditor = this._arEditors[i];
			if (oEditor instanceof ImageEditorBase === false) continue;
			callback.call(oEditor, i, oEditor);
		}
	};


	/* ================================== LAYOUTS ================================= */


	var LayoutBase = function (oEditor) {
		LayoutBase.superclass.constructor.call(this);

		this.CSS_CLASS_NAME = '';
		this.ANIMATIONS_SPEED = settings.editor.animation_speed;

		this.oEditor = oEditor;

	};
	uAdmin.inherit(LayoutBase, EntityBase);

	LayoutBase.prototype.init = function () {
		this.oEditor.jqMenuWrapper.addClass(this.CSS_CLASS_NAME);
		this.reposition();
	};

	LayoutBase.prototype.remove = function () {
		this.oEditor.jqMenuWrapper.removeClass(this.CSS_CLASS_NAME);
		this.resetPosition();
		this.oEditor.layout = null;
	};

	LayoutBase.prototype.reposition = function () {};

	LayoutBase.prototype.resetPosition = function () {
		var oMenuNode = this.oEditor.jqMenuWrapper[0];
		oMenuNode.style.top = '';
		oMenuNode.style.bottom = '';
		oMenuNode.style.left = '';
		oMenuNode.style.right = '';
	};

	LayoutBase.prototype.show = function () {
		var self = this;
		this.oEditor.jqMenuWrapper.stop(true, true).fadeIn(this.ANIMATIONS_SPEED, function(){
			self.oEditor.triggerEvent('onShow');
		});
	};

	LayoutBase.prototype.hide = function (callback) {
		var self = this;
		this.oEditor.jqMenuWrapper.add().stop(true, true).fadeOut(this.ANIMATIONS_SPEED, function(){
			if (typeof callback == 'function') {
				callback();
			}
			self.oEditor.triggerEvent('onHide');
		});
	};


	var LayoutExtended = function (oEditor) {
		LayoutExtended.superclass.constructor.call(this, oEditor);

		this.CSS_CLASS_NAME = settings.layout.extended.css_class;

		this.init();

	};
	uAdmin.inherit(LayoutExtended, LayoutBase);

	LayoutExtended.prototype.reposition = function () {
		this.oEditor.jqMenuWrapper.css({
			right: settings.layout.extended.right_margin,
			bottom: settings.layout.extended.bottom_margin
		});
	};


	var LayoutSimple = function (oEditor) {
		LayoutSimple.superclass.constructor.call(this, oEditor);

		this.CSS_CLASS_NAME = settings.layout.simple.css_class;

		this.init();

	};
	uAdmin.inherit(LayoutSimple, LayoutBase);

	LayoutSimple.prototype.reposition = function () {
		this.oEditor.jqMenuWrapper.css({
			right: settings.layout.simple.right_margin,
			bottom: settings.layout.simple.bottom_margin
		});
	};


	var LayoutBubble = function (oEditor) {
		LayoutBubble.superclass.constructor.call(this, oEditor);

		this.CSS_CLASS_NAME = settings.layout.bubble.css_class;
		this.BUBBLE_ARROW_CSS_CLASS = settings.layout.bubble.arrow_css_class;

		this.init();

	};
	uAdmin.inherit(LayoutBubble, LayoutBase);

	LayoutBubble.prototype.init = function () {
		LayoutBubble.superclass.init.call(this);
		this.oEditor.jqMenuWrapper.prepend("<div class='"+this.BUBBLE_ARROW_CSS_CLASS+"'/>");
	};

	LayoutBubble.prototype.remove = function () {
		this.oEditor.jqMenuWrapper.find("." + this.BUBBLE_ARROW_CSS_CLASS).remove();
		LayoutBubble.superclass.remove.call(this);
	};

	LayoutBubble.prototype.reposition = function () {
		var jqMeasurableNode = this.oEditor.jqMenuWrapper.clone().css('left', -10000).appendTo("body").show();
		var iLeft = (this.oEditor.jqImgWrapper.outerWidth() - jqMeasurableNode.outerWidth()) / 2;
		jqMeasurableNode.remove();
		this.oEditor.jqMenuWrapper.css({
			'left': iLeft + 'px',
			'bottom': settings.layout.bubble.bottom_margin - settings.layout.bubble.bottom_margin_delta + 'px'
		});
	};

	LayoutBubble.prototype.show = function () {
		var self = this;
		this.oEditor.jqMenuWrapper.stop(true, true).fadeIn(this.ANIMATIONS_SPEED).animate({'bottom': settings.layout.bubble.bottom_margin + 'px'}, this.ANIMATIONS_SPEED, function(){
			self.oEditor.triggerEvent('onShow');
		});
	};

	LayoutBubble.prototype.hide = function (callback) {
		var self = this;
		this.oEditor.jqMenuWrapper
			.stop(true, true)
			.animate({'bottom': settings.layout.bubble.bottom_margin - settings.layout.bubble.bottom_margin_delta + 'px'}, this.ANIMATIONS_SPEED)
			.fadeOut(this.ANIMATIONS_SPEED, function(){
				if (typeof callback == 'function') {
					callback();
				}
				self.oEditor.triggerEvent('onHide');
			});
	};


	/* ================================== EDITORS ================================= */

	var ImageEditorBase = function () {

		ImageEditorBase.superclass.constructor.call(this);

		this.MENU_WRAPPER_CLASS_NAME = settings.editor.menu_wrapper_css_class;
		this.IMG_WRAPPER_CLASS_NAME = settings.editor.img_wrapper_css_class;
		this.ANIMATIONS_SPEED = settings.editor.animation_speed;

		this.IMAGE_TYPE_UNKNOWN = 0;
		this.IMAGE_TYPE_EIP = 1;
		this.IMAGE_TYPE_WYSIWYG = 2;
		this.IMAGE_TYPE_PHOTOALBUM = 3;
		this.IMAGE_TYPE_SLIDER = 4;

		this._imageUrl = '';
		this.sEditorId = '';

		this.layout = ''; // must be redeclared in childrens
		this.jqImgNode = null;
		this.jqMenuWrapper = null;
		this.jqDeleteButton = null;
		this.jqCloseButton = null;
		this.jqImgWrapper = null;
		this.jqPreloader = null;

		this.iImageType = this.IMAGE_TYPE_UNKNOWN;
		this.bEditStateEnabled = false;

		this.arModulesSet = [];

		this.arDefaultModules = [
			UploadModule,
			FilemanagerModule,
			SliderModule,
			PopupModule
		];

		this.arActiveModules = [];

		this.oEnabledModule = null;

		this.oEventHandlers = {};

	};
	uAdmin.inherit(ImageEditorBase, EntityBase);

	ImageEditorBase.prototype.init = function (jqImgNode, sEditorId) {
		var self = this;

		if (!jqImgNode) {
			if (this.jqImgNode && this.jqImgNode.length) {
				jqImgNode = this.jqImgNode;
			} else {
				return false;
			}
		}

		if (!sEditorId && jqImgNode && jqImgNode.length) {
			sEditorId = jqImgNode.attr(ImageEditorsCollection.getInstance().EDITOR_ID_ATTRIBUTE);
		}

		this.sEditorId = sEditorId || jqImgNode.attr(ImageEditorsCollection.getInstance().EDITOR_ID_ATTRIBUTE);

		if (jqImgNode.parents(".mceEditor").length || jqImgNode.parents("[umi\\:field-type='wysiwyg']").length || jqImgNode.parents(".mce-content-body").length) {
			this.iImageType = this.IMAGE_TYPE_WYSIWYG;
		} else if (jqImgNode.parents("[umi\\:module='photoalbum']").length) {
			this.iImageType = this.IMAGE_TYPE_PHOTOALBUM;
		} else if (jqImgNode.is("[umi\\:field-name]")) {
			this.iImageType = this.IMAGE_TYPE_EIP;
		} else if (jqImgNode.is("[umi\\:slider-id]")) {
			this.iImageType = this.IMAGE_TYPE_SLIDER;
		}

		this.jqImgNode = jqImgNode;
		this.jqImgWrapper = jQuery("<div/>").addClass(this.IMG_WRAPPER_CLASS_NAME);
		this.hideTimer = null;
		this.jqImgWrapper
			.attr(ImageEditorsCollection.getInstance().EDITOR_ID_ATTRIBUTE, this.sEditorId)
			.on('click', function(oEvent){
				if (jQuery(oEvent.target).parents("." + self.MENU_WRAPPER_CLASS_NAME). length) return false;
				self.jqImgNode.trigger('click');
				return false;
			})
			.on('contextmenu', function(oEvent){
				if (!tinymce || !tinymce.activeEditor) return true;
				oEvent.preventDefault();
				oEvent.target = self.jqImgNode[0];
				oEvent.originalEvent.target = self.jqImgNode[0];
				oEvent.currentTarget = self.jqImgNode[0];
				oEvent.delegateTarget = self.jqImgNode[0];
				tinymce.activeEditor.selection.select(self.jqImgNode[0]);
				tinymce.activeEditor.dom.fire(self.jqImgNode[0], 'contextmenu', oEvent);
				return false;
			})
			.appendTo("body");
		this.applyImageSizes();
		this.drawMenu();
		this.switchOn();

		this.jqImgNode.on('mousemove mouseover mouseout mouseenter mouseleave', function(oEvent){
			oEvent.stopPropagation();
			oEvent.stopImmediatePropagation();
			oEvent.preventDefault();
			return false;
		});

		this.triggerEvent('onInit');

	};

	ImageEditorBase.prototype.reinit = function () {
		var sEditorIdAttribute = ImageEditorsCollection.getInstance().EDITOR_ID_ATTRIBUTE;
		this.jqImgNode = jQuery("img[" + sEditorIdAttribute + "='" + this.sEditorId + "']");
		this.jqImgWrapper = jQuery('.'+this.IMG_WRAPPER_CLASS_NAME).filter("[" + sEditorIdAttribute + "='" + this.sEditorId + "']").first();
		if (!this.jqImgWrapper.length) return;
		this.jqMenuWrapper = this.jqImgWrapper.find("."+this.MENU_WRAPPER_CLASS_NAME).first();
		if (!this.jqImgNode.length) return;
		this.applyImageSizes();
		this.layout.reposition();
		this.jqImgWrapper.offset(this.jqImgNode.offset());
	};

	ImageEditorBase.prototype.initLayout = function () {

		if (this.layout instanceof LayoutBase) {
			this.layout.remove();
		}

		var iImgWidth = this.jqImgNode.width(),
			iImgHeight = this.jqImgNode.height();

		if (iImgWidth >= settings.layout.big_img_min_width && iImgHeight >= settings.layout.big_img_min_height) {
			this.layout = new LayoutExtended(this);
		} else if (iImgWidth <= settings.layout.small_img_max_width || iImgHeight <= settings.layout.small_img_max_height) {
			this.layout = new LayoutBubble(this);
		} else {
			this.layout = new LayoutSimple(this);
		}
	};

	ImageEditorBase.prototype.drawMenu = function (arModules) {

		this.jqMenuWrapper = jQuery("<div/>").addClass(this.MENU_WRAPPER_CLASS_NAME);
		this.initLayout();

		this.drawModules(arModules);

		if (!this.isStub() || this.iImageType === this.IMAGE_TYPE_PHOTOALBUM) {
			this.drawDeleteButton();
		}

		this.reposition();

		this.jqMenuWrapper.appendTo(this.jqImgWrapper);

	};

	ImageEditorBase.prototype.applyImageBoxSizes = function (jqImageNode) {
		if (!jqImageNode || !jqImageNode.length) jqImageNode = this.jqImgNode;
		var self = this,
			getVal = function (sPropertyName) {
				return jqImageNode.css(sPropertyName);
			};

		this.jqImgWrapper.css({
			width: getVal('width'),
			height: getVal('height'),
			paddingTop: getVal('paddingTop'),
			paddingRight: getVal('paddingRight'),
			paddingBottom: getVal('paddingBottom'),
			paddingLeft: getVal('paddingLeft'),
			borderTop: getVal('borderTop'),
			borderRight: getVal('borderRight'),
			borderBottom: getVal('borderBottom'),
			borderLeft: getVal('borderLeft'),
			marginTop: getVal('marginTop'),
			marginRight: getVal('marginRight'),
			marginBottom: getVal('marginBottom'),
			marginLeft: getVal('marginLeft')
		});
	};

	ImageEditorBase.prototype.applyImageSizes = function (jqImageNode) {
		if (!jqImageNode || !jqImageNode.length) jqImageNode = this.jqImgNode;

		this.applyImageBoxSizes(jqImageNode);
		this.jqImgWrapper.offset(jqImageNode.offset());
	};

	ImageEditorBase.prototype.lockImageSize = function () {
		this.jqImgNode.css({
			maxWidth: this.jqImgNode.parent().width(),
			maxHeight: this.jqImgNode.parent().height()
		});
	};

	ImageEditorBase.prototype.redrawMenu = function (arModules) {
		for (var i in this.arActiveModules) {
			var oModule = this.arActiveModules[i];
			if (oModule instanceof ModuleBase) {
				oModule.remove();
			}
		}
		this.jqMenuWrapper.children().remove();
		this.removeCloseButton();
		this.drawModules(arModules);
		this.initLayout();
	};

	ImageEditorBase.prototype.drawModules = function (arModules) {
		if (this.isPopupThumb()) {
			arModules = [PopupModule];
		}
		this.resetModules();
		if (!arModules) {
			this.setDefaultModules();
		} else {
			for (var i in arModules) {
				this.addModule(arModules[i]);
			}
		}

		var bIsStub = this.isStub();
		this.arActiveModules = [];
		for (var i in this.arModulesSet) {
			if (!this.arModulesSet.hasOwnProperty(i)) continue;
			// disable some modules on IE
			if (jQuery.browser.msie && (this.arModulesSet[i] == UploadModule)) continue;
			// end disable
			var module = new this.arModulesSet[i](this);
			this.arActiveModules.push(module);
			this.jqMenuWrapper.append(module.getView(true));
		}

	};

	ImageEditorBase.prototype.drawDeleteButton = function (bShow) {
		if (this.iImageType == this.IMAGE_TYPE_SLIDER) {
			return false;
		}

		if (this.jqDeleteButton && this.jqDeleteButton.length) {
			this.jqDeleteButton.remove();
		}

		var module = DeleteModule,
			self = this;

		if (!this.checkModule(module)) {
			return false;
		}
		module = new module(this);
		this.jqDeleteButton = module.getView(true);
		this.jqDeleteButton.addClass(this.layout.CSS_CLASS_NAME);
		this.jqImgWrapper.append(this.jqDeleteButton);

		if (bShow) {
			this.jqDeleteButton.show();
		}
		return true;

	};

	ImageEditorBase.prototype.removeDeleteButton = function () {
		if (this.iImageType == this.IMAGE_TYPE_SLIDER) {
			return false;
		}

		this.jqDeleteButton && this.jqDeleteButton.length && this.jqDeleteButton.remove() && (this.jqDeleteButton = null);
	};

	ImageEditorBase.prototype.addCloseButton = function (closeCallback) {
		if (this.jqCloseButton && this.jqCloseButton.length) return;
		this.jqCloseButton = jQuery("<span/>").html("&times;");
		this.jqCloseButton
			.css({
				position: 'absolute',
				top: 0,
				right: 0,
				cursor: 'pointer',
				color: 'white',
				fontSize: '15px',
				lineHeight: '10px',
				fontWeight: 'bold',
				padding: '3px'
			})
			.on("click", function(oEvent){
				if (typeof closeCallback == 'function') {
					closeCallback(oEvent);
				}
			})
			.appendTo(this.jqMenuWrapper);
	};

	ImageEditorBase.prototype.removeCloseButton = function () {
		this.jqCloseButton && this.jqCloseButton.length && this.jqCloseButton.remove() && (this.jqCloseButton = null);
	};

	ImageEditorBase.prototype.showPreloader = function () {
		this.jqPreloader && this.jqPreloader.length && this.hidePreloader();
		this.jqPreloader = jQuery('<div/>')
			.addClass(settings.preloader_holder_css_class)
			.append("<img src='" + settings.preloader_src + "'>")
			.appendTo(this.jqImgWrapper);

		if (!this.jqImgWrapper.data('incrementedZIndex')) {
			this.jqImgWrapper.data('incrementedZIndex', true);
			this.jqImgWrapper.css('zIndex', parseInt(this.jqImgWrapper.css('zIndex')) * 10 || 999999);
		}

		this.switchOff();
	};

	ImageEditorBase.prototype.hidePreloader = function () {
		this.jqPreloader && this.jqPreloader.length && this.jqPreloader.remove() && (this.jqPreloader = null);

		if (this.jqImgWrapper.data('incrementedZIndex')) {
			this.jqImgWrapper.removeData();
			this.jqImgWrapper.css('zIndex', Math.floor(parseInt(this.jqImgWrapper.css('zIndex')) / 10) || 99999);
		}
	};

	ImageEditorBase.prototype.reposition = function () {
		this.layout.reposition();
	};

	ImageEditorBase.prototype.destroy = function (bIgnoreImageNode) {
		this.reinit();
		this.jqImgWrapper.remove();
		this.jqImgNode.off('mousemove mouseover mouseout mouseenter mouseleave');
		if (!bIgnoreImageNode) {
			this.jqImgNode.removeAttr(ImageEditorsCollection.getInstance().EDITOR_ID_ATTRIBUTE);
		}

		this.triggerEvent('onDestroy');
	};

	ImageEditorBase.prototype.show = function () {
		this.jqDeleteButton && this.jqDeleteButton.length && this.jqDeleteButton.show();
		this.layout.show();
	};

	ImageEditorBase.prototype.hide = function (callback) {
		this.jqDeleteButton && this.jqDeleteButton.length && this.jqDeleteButton.hide();
		this.layout.hide(callback);
	};

	/**
	 * @param skipDestroyOnMouseLeave bool - флаг для пропуска удаления обертки редактора картинки при событии 'mouseleave'
	 */
	ImageEditorBase.prototype.switchOn = function (skipDestroyOnMouseLeave) {
		var self = this;
		this.jqImgWrapper
			.css('zIndex', parseInt(this.jqImgWrapper.css('zIndex')) || 99999)
			.on('mouseenter', function() {
				// Don't hide menu on reenter
				if (self.hideTimer) {
					clearTimeout(self.hideTimer);
					self.hideTimer = null;
				}

				self.show();
				if (self.jqDeleteButton && self.jqDeleteButton.length) {
					self.jqDeleteButton.stop(true, true).show(self.ANIMATIONS_SPEED);
				}
			})
			.on('mouseleave', function() {
				// Schedule menu hide
				self.hideTimer = window.setTimeout(function() {
					self.hideTimer = null;
					if (self.jqDeleteButton && self.jqDeleteButton.length) {
						self.jqDeleteButton.stop(true, true).hide(self.ANIMATIONS_SPEED);
					}
					self.hide(function(){
						if (skipDestroyOnMouseLeave === true) {
							return;
						}
						self.destroy(true);
					});
				}, 200);
			});
	};

	ImageEditorBase.prototype.switchOff = function (bHide) {
		this.jqImgWrapper.off("mouseenter mouseleave");
		if (bHide) {
			this.hide();
			this.jqDeleteButton.hide();
		}
	};

	ImageEditorBase.prototype.checkModule = function (Module) {
		var enabledInBrowser = false;
		for (var i in settings.browser_modules) {
			if (!settings.browser_modules.hasOwnProperty(i) || !jQuery.browser[i]) continue;
			for (var m = 0; m < settings.browser_modules[i].length; m++) {
				if (settings.browser_modules[i][m] === Module) {
					enabledInBrowser = true;
					break;
				}
			}
		}
		return typeof Module === 'function' && !!Module.isImageEditorModule && enabledInBrowser;
	};

	ImageEditorBase.prototype.addModule = function (Module) {
		if (this.checkModule(Module)) {
			this.arModulesSet.push(Module);
			this.triggerEvent('onAddModule', [Module]);
		}
	};

	ImageEditorBase.prototype.resetModules = function () {
		this.arModulesSet = [];
	};

	ImageEditorBase.prototype.setDefaultModules = function () {
		this.resetModules();
		for (var i in this.arDefaultModules) {
			this.addModule(this.arDefaultModules[i]);
		}
	};

	ImageEditorBase.prototype.isThumb = function () {
		return (!!this.jqImgNode.attr('src').match(/\/thumbs\//gi) || !!this.jqImgNode.attr('src').match(/\/autothumbs\//gi));
	};

	ImageEditorBase.prototype.isPopupThumb = function() {
		if (typeof PopupModule == 'undefined') {
			return false;
		}
		return !!this.jqImgNode.parents('.' + PopupModule.WRAPPER_CSS_CLASS).length;
	};

	ImageEditorBase.prototype.isStub = function () {
		var bAttrCondition = this.jqImgNode.attr("umi:is-stub") === "true",
			stubImageMSIELow8Condition = uAdmin.eip.getMSIEStubImgCondition(),
			bImgCondition = stubImageMSIELow8Condition && this.jqImgNode[0] &&
				this.jqImgNode[0].complete && !this.jqImgNode[0].naturalWidth &&
				!this.jqImgNode[0].naturalHeight;
		return bAttrCondition || bImgCondition;
	};

	ImageEditorBase.prototype.getImageUrl = function () {
		if (!this._imageUrl) {
			if (this.iImageType === this.IMAGE_TYPE_WYSIWYG) {
				this._imageUrl = this.jqImgNode.attr('src');
			} else {
				var oImgElementInfo = uAdmin.eip.searchAttr(this.jqImgNode);
				var reqResult = jQuery.ajax({
					async: false,
					url: '/udata/content/getImageUrl/'+oImgElementInfo.id+"/"+oImgElementInfo.field_name+"/.json",
					type: 'GET'
				});
				var data = jQuery.parseJSON(reqResult.responseText);
				this._imageUrl = data.result;
			}
		}
		return this._imageUrl;
	};

	ImageEditorBase.prototype.getCurrentImageUrl = function () {
		return this.isThumb() ? this.getImageUrl() : this.jqImgNode.attr('src');
	};

	ImageEditorBase.prototype.getEipInfo = function (oNode) {
		oNode = oNode || this.jqImgNode[0];
		return uAdmin.eip.searchAttr(oNode);
	};

	ImageEditorBase.prototype.getEipNode = function () {
		var oInfo = this.getEipInfo();
		if (!oInfo || !oInfo.node) {
			return null;
		}
		return oInfo.node;
	};

	ImageEditorBase.prototype.getEditorId = function () {
		return this.sEditorId;
	};

	ImageEditorBase.prototype.checkImageIsEmpty = function () {
		return this.jqImgNode.attr('src') == this.jqImgNode.attr('umi:empty');
	};

	ImageEditorBase.prototype.setEnabledModule = function (oModule) {
		if (!oModule || !oModule.deactivate) {
			return false;
		}
		this.oEnabledModule = oModule;
		ImageEditorsCollection.getInstance().setActiveEditor(this);
		return true;
	};

	ImageEditorBase.prototype.getEnabledModule = function () {
		return this.oEnabledModule;
	};

	ImageEditorBase.prototype.deactivateEnabledModule = function (bExecuteRollback) {
		if (!this.oEnabledModule || !this.oEnabledModule.deactivate) {
			this.oEnabledModule = null;
			return true;
		}
		if (!bExecuteRollback) {
			this.oEnabledModule = null;
			return true;
		}
		this.oEnabledModule.cancel();
		if (this.oEnabledModule.deactivate()) {
			this.oEnabledModule = null;
			return true;
		}
		return false;
	};

	ImageEditorBase.prototype.cleanupHtml = function (sHtml) {
		return sHtml.replace(/\sdata-ieditor-id=["']{1}[0-9\._]+["']{1}/gi, '');
	};

	ImageEditorBase.prototype.isImageInFocus = function () {
		var iTop = this.jqImgNode.offset().top,
			iBottom = this.jqImgNode.offset().top + this.jqImgNode.height(),
			iLeft = this.jqImgNode.offset().left,
			iRight = this.jqImgNode.offset().left + this.jqImgNode.width();
		return window.mouseX > iLeft && window.mouseX < iRight && window.mouseY >iTop && window.mouseY < iBottom;
	};

	ImageEditorBase.prototype.getQueueValue = function (newImageSrc) {
		if (this.iImageType === this.IMAGE_TYPE_WYSIWYG) {
			var node = this.getEipNode();
			return node ? node.innerHTML : undefined;
		}

		return (typeof newImageSrc !== 'undefined') ? newImageSrc : this.jqImgNode.attr('src');
	};

	var ImageEditor = function () {

		ImageEditor.superclass.constructor.call(this);

	};
	uAdmin.inherit(ImageEditor, ImageEditorBase);

	var ImageEditorVoid = function () {};
	ImageEditorVoid.prototype.show = function () {};
	ImageEditorVoid.prototype.hide = function () {};
	ImageEditorVoid.prototype.reposition = function () {};
	ImageEditorVoid.prototype.initLayout = function () {};
	ImageEditorVoid.prototype.init = function () {};
	ImageEditorVoid.prototype.reinit = function () {};
	ImageEditorVoid.prototype.destroy = function () {};
	ImageEditorVoid.prototype.switchOn = function () {};
	ImageEditorVoid.prototype.switchOff = function () {};


	/* ================================== MODULES ================================= */

	var ModuleBase = function () {

		ModuleBase.superclass.constructor.call(this);

		this.MODULE_CSS_CLASS = settings.module.module_css_class;
		this.MODULE_ICON_HOLDER_CSS_CLASS = settings.module.icon_holder_css_class;
		this.MODULE_TITLE_HOLDER_CSS_CLASS = settings.module.title_holder_css_class;
		this.BACKEND_REQUEST_URL = settings.backend_request_url;
		this.BACKEND_REQUEST_METHOD = settings.backend_request_method;

		this.title = ""; // Must be rediclared in childrens
		this.cssClass = "";
		this.jqElement = null;

		this.oEditor = null;

	};
	uAdmin.inherit(ModuleBase, EntityBase);

	ModuleBase.prototype.init = function (oEditor) {

		this.oEditor = oEditor;
		this.jqElement = jQuery("<div/>");
		this.jqElement.addClass(this.cssClass);
		this.jqElement.addClass(this.MODULE_CSS_CLASS);
		this.jqElement.append(jQuery("<span/>"));
		jQuery("<span/>").addClass(this.MODULE_TITLE_HOLDER_CSS_CLASS).appendTo(this.jqElement);
		this.setIcon(this.MODULE_ICON_HOLDER_CSS_CLASS);
		this.setTitle(this.title);
		this.bindEvents();

		this.triggerEvent('onInit');

	};

	ModuleBase.prototype.setIcon = function (sIconCssClassName) {
		var jqIconNode = this.jqElement.find("span").first();
		jqIconNode.get(0).className = '';
		jqIconNode.addClass(sIconCssClassName);
	};

	ModuleBase.prototype.setTitle = function (sNewTitle) {
		if (!sNewTitle) sNewTitle = this.title;
		this.title = sNewTitle;
		this.jqElement.find("span").first().attr('title', sNewTitle);
		this.jqElement.find("span").last().text(sNewTitle);
	};

	ModuleBase.prototype.bindEvents = function () {

		var self = this;
		this.jqElement.on("click", function(event){
			event.stopPropagation();
			event.stopImmediatePropagation();
			event.preventDefault();
			self.activate();
			return false;
		});

	};

	ModuleBase.prototype.getView = function (returnJqueryObject) {

		if (returnJqueryObject === true) {
			return this.jqElement;
		}
		return this.jqElement[0].outerHTML;

	};

	ModuleBase.prototype.activate = function () {
		var self = this;

		ImageEditorsCollection.getInstance().deactivateActiveEditor();
		this.oEditor.setEnabledModule(this);
		this.triggerEvent('onActivate');
	};

	ModuleBase.prototype.deactivate = function () {
		this.triggerEvent('onDeactivate');
		return true;
	};

	ModuleBase.prototype.save = function () {
		this.triggerEvent('onSave');
	};

	ModuleBase.prototype.cancel = function () {
		this.triggerEvent('onCancel');
	};

	ModuleBase.prototype.remove = function () {
		this.jqElement.remove();
		this.triggerEvent('onRemove');
	};

	ModuleBase.prototype.process = function (sAction, oParams) {
		if (!sAction) {
			return {
				"result": false,
				"error": getLabel("js-ieditor-invalid-action")
			}
		}
		this.oEditor.showPreloader();
		if (!oParams) oParams = {};
		oParams['image_url'] = this.oEditor.jqImgNode.attr('src');
		oParams['empty_url'] = this.oEditor.jqImgNode.attr('umi:empty');
		oParams['action'] = sAction;
		var oEipNodeInfo = uAdmin.eip.searchAttr(this.oEditor.jqImgNode[0]);
		if (oEipNodeInfo) {
			oParams['element_id'] = oEipNodeInfo['id'];
			oParams['field_name'] = oEipNodeInfo['field_name'];
		}
		var reqResult = jQuery.ajax({
			async: false,
			url: this.BACKEND_REQUEST_URL + sAction + "/.json",
			type: this.BACKEND_REQUEST_METHOD,
			data: oParams
		});
		var oResult = jQuery.parseJSON(reqResult.responseText);
		this.processResult(oResult);
		return oResult;
	};

	ModuleBase.prototype.processResult = function (oResult) {
		if (!oResult) {
			uAdmin.eip.message(getLabel('js-ieditor-request-failed'));
			this.oEditor.hidePreloader();
		} else if (oResult.error && typeof oResult.error == 'string') {
			uAdmin.eip.message(oResult.error);
			this.oEditor.hidePreloader();
		} else if (oResult.error && oResult.error.message) {
			uAdmin.eip.message(oResult.error.message);
			this.oEditor.hidePreloader();
		} else if (oResult.result && oResult.result.length) {
			this.setNewImage(oResult.result);
		} else {
			uAdmin.eip.message(getLabel('js-ieditor-request-failed'));
			this.oEditor.hidePreloader();
		}
	};

	ModuleBase.prototype.addToEipQueue = function (sNewValue, sOldValue, oEipNode) {
		var oEipInfo = this.oEditor.getEipInfo(oEipNode);
		if (!oEipInfo || !oEipInfo.node) {
			return false;
		}
		if (this.oEditor.iImageType == this.oEditor.IMAGE_TYPE_WYSIWYG) {
			oEipInfo['old_value'] = sOldValue || oEipInfo.node.innerHTML;
			oEipInfo['new_value'] = sNewValue || oEipInfo.node.innerHTML.replace(this.oEditor.jqImgNode.attr('src'), sNewValue);
			oEipInfo['field_type'] = 'wysiwyg';
		} else {
			oEipInfo['old_value'] = {src: sOldValue || this.oEditor.getCurrentImageUrl()};
			oEipInfo['new_value'] = {src: sNewValue};
			oEipInfo['field_type'] = 'img_file';
		}
		uAdmin.eip.queue.add(oEipInfo);
		return true;
	};

	ModuleBase.prototype.getWysiwygContent = function (oWysiwygNode) {
		oWysiwygNode = oWysiwygNode || this.oEditor.getEipNode();
		if (!oWysiwygNode || !oWysiwygNode) return null;
		if (tinymce && tinymce.activeEditor) {
			return tinymce.activeEditor.getContent();
		} else {
			return oWysiwygNode.innerHTML;
		}
	};

	ModuleBase.prototype.setNewImage = function (sNewImageSrc, resetCache) {
		var self = this,
			sNewImageSrcNoCache = (sNewImageSrc || '');

		if (resetCache !== false) {
			this.oEditor.showPreloader();
			sNewImageSrcNoCache += "?" + new Date().getTime();
		}

		if (this.oEditor.iImageType == this.oEditor.IMAGE_TYPE_WYSIWYG) {
			if (PopupModule && PopupModule.changeImage) {
				PopupModule.changeImage(this.oEditor, !sNewImageSrc);
			}
			if (!sNewImageSrc) {
				this.oEditor.jqImgNode.remove();
				this.oEditor.jqImgWrapper.remove();
			} else {
				this.oEditor.jqImgNode.attr('src', sNewImageSrcNoCache);
				if (this.oEditor.jqImgNode.attr('data-mce-src')) {
					this.oEditor.jqImgNode.attr('data-mce-src', sNewImageSrcNoCache);
				}
			}
		} else {
			this.oEditor.jqImgNode.attr({
				'src': sNewImageSrcNoCache,
				'umi:is-stub': false
			});
		}
		this.oEditor.jqImgNode.on("load", function(){
			self.oEditor.hidePreloader();
			if (self.oEditor.jqImgNode) {
				self.oEditor.jqImgNode.removeAttr('width').removeAttr('height').css({width: '', height: ''});
			}
			self.oEditor.reinit();
			window.setTimeout(function(){
				uAdmin.eip.normalizeBoxes();
			}, 0);
		});
		this.oEditor.triggerEvent('onImageChange', {
			target: this,
			sNewSrc: sNewImageSrc,
			sNewSrcNoCache: sNewImageSrcNoCache
		});
	};

	ModuleBase.prototype.getImageFromFilemanager = function (fCallback) {
		if (typeof fCallback != 'function') return false;

		jQuery.openPopupLayer({
			name   : "Filemanager",
			title  : getLabel('js-file-manager'),
			width  : settings.filemanager.window_width,
			height : settings.filemanager.window_height,
			url    : settings.filemanager.url,
			afterClose : function (arFiles) {
				if (arFiles) {
					fCallback(arFiles);
				}
			},
			success : function () {
				var footer = '<div id="watermark_wrapper"><label for="add_watermark">';
				footer += window.parent.getLabel('js-water-mark');
				footer += '</label><input type="checkbox" name="add_watermark" id="add_watermark"/>';
				footer += '<label for="remember_last_folder">';
				footer += window.parent.getLabel('js-remember-last-dir');
				footer += '</label><input type="checkbox" name="remember_last_folder" id="remember_last_folder"'
				if (jQuery.cookie('remember_last_folder')) {
					footer += 'checked="checked"';
				}
				footer +='/></div>';
				window.parent.jQuery('#popupLayer_Filemanager .popupBody').append(footer);
			}
		});
		return true;
	};


	function UploadModule (oEditor) {

		UploadModule.superclass.constructor.call(this);

		this.UPLOAD_URL = settings.module.upload_module.url;
		this.IFRAME_NAME = settings.module.upload_module.iframe_name;
		this.FILE_INPUT_NAME = settings.module.upload_module.file_input_name;

		this.title = getLabel("js-ieditor-module-upload-title");

		if (oEditor.iImageType == oEditor.IMAGE_TYPE_SLIDER){
			this.cssClass = 'eip-ieditor-empty';
		} else {
			this.cssClass = settings.module.upload_module.css_class;
		}

		this.jqFileInput = null;
		this.jqForm = null;
		this.jqIframe = null;

		this.init(oEditor);

	};
	uAdmin.inherit(UploadModule, ModuleBase);

	UploadModule.isImageEditorModule = true;

	UploadModule.prototype.init = function (oEditor) {

		var self = this;

		UploadModule.superclass.init.call(this, oEditor);

		this.jqElement.css({
			overflow: 'hidden',
			position: 'relative'
		});

		this.jqFileInput = jQuery("<input/>")
			.attr({
				type: 'file',
				name: this.FILE_INPUT_NAME,
				title: this.title
			})
			.css({
				opacity: 0,
				cursor: 'pointer'
			})
			.on("click", function(e){
				e.stopPropagation();
				e.stopImmediatePropagation();
				self.activate();
			});

		this.jqForm = jQuery("<form/>")
			.attr({
				method: 'post',
				action: this.UPLOAD_URL,
				target: this.IFRAME_NAME,
				enctype: 'multipart/form-data'
			})
			.css({
				padding: 0,
				margin: 0,
				border: 'none',
				position: 'absolute',
				top: 0,
				right: 0
			})
			.appendTo(this.jqElement)
			.append(this.jqFileInput);

		this.jqForm.append('<input type="hidden" name="action" value="upload">');

	};

	UploadModule.prototype.activate = function () {
		UploadModule.superclass.activate.call(this);

		this.oEditor.switchOff();

		var self = this;

		jQuery('iframe[name=' + this.IFRAME_NAME + ']').remove();

		this.jqIframe = jQuery("<iframe/>")
			.attr({
				name: this.IFRAME_NAME
			})
			.css({
				display: 'none'
			})
			.appendTo("body")
			.on("load", function(){
				if (!self.bImageChanged) return;
				self.oEditor.hidePreloader();
				self.oEditor.switchOn();
				var sImgPath = jQuery(this).contents().find("body").text();
				if (!sImgPath) {
					uAdmin.eip.message(getLabel('js-ieditor-request-failed'));
				} else {
					var sOldValue = self.oEditor.getQueueValue();
					self.setNewImage(sImgPath);
					self.addToEipQueue(self.oEditor.getQueueValue(sImgPath), sOldValue);
				}
				self.cleanup();
			});

		this.jqFileInput.on("change", function(){
			self.oEditor.showPreloader();
			self.bImageChanged = true;
			self.jqForm.submit();
		});

		window.setTimeout(function(){
			self.oEditor.switchOn(true);
			if (jQuery.browser.webkit) {
				self.oEditor.hide();
			}
		}, 1000);

	};

	UploadModule.prototype.cleanup = function () {
		this.oEditor.switchOff();
		this.oEditor.switchOn();
		this.jqIframe.remove();
	};

	function SliderModule (oEditor) {

		SliderModule.superclass.constructor.call(this);

		this.title = getLabel('js-ieditor-module-slider-title');

		if (uAdmin.eip.isEditedImageTypeSlider(oEditor)){
			this.cssClass = 'eip-ieditor-module-slider';
		} else {
			this.cssClass = 'eip-ieditor-empty';
		}

		this.init(oEditor);
	};

	uAdmin.inherit(SliderModule, ModuleBase);

	SliderModule.isImageEditorModule = true;

	SliderModule.prototype.activate = function () {
		SliderModule.superclass.activate.call(this);

		$.openPopupLayer({
			'name':'SliderEditor',
			'title':getLabel('js-ieditor-module-slider-popup-title'),
			'url': uAdmin.eip.getSliderEditPopupLayerUrl(this.oEditor),
			'width':710,
			'height': 700,
			'success': function(){
				$('#popupLayer_SliderEditor .popupBody').css({'padding':'0'});
				$('#popupLayer_SliderEditor iframe.umiPopupFrame').css({'width':'100%'});
			}
		});
	};

	function FilemanagerModule (oEditor) {

		FilemanagerModule.superclass.constructor.call(this);

		this.title = getLabel('js-ieditor-module-filemanager-title');

		if (oEditor.iImageType == oEditor.IMAGE_TYPE_SLIDER){
			this.cssClass = 'eip-ieditor-empty';
		} else {
			this.cssClass = settings.module.filemanager_module.css_class;
		}

		this.init(oEditor);

	};
	uAdmin.inherit(FilemanagerModule, ModuleBase);

	FilemanagerModule.isImageEditorModule = true;

	FilemanagerModule.prototype.activate = function () {
		FilemanagerModule.superclass.activate.call(this);

		this.oEditor.switchOff();
		var self = this;
		this.getImageFromFilemanager(function (arFiles) {
			var sOldValue = self.oEditor.getQueueValue();
			self.setNewImage(arFiles[0]);
			self.addToEipQueue(self.oEditor.getQueueValue(arFiles[0]), sOldValue);
		});
		window.setTimeout(function(){
			self.oEditor.switchOn();
			if (jQuery.browser.webkit) {
				self.oEditor.hide();
			}
		}, 500);

	};

	function PopupModule(oEditor) {

		PopupModule.superclass.constructor.call(this);

		this.title = getLabel('js-ieditor-module-popup-title');

		if (oEditor.iImageType == oEditor.IMAGE_TYPE_WYSIWYG) {
			this.cssClass = settings.module.popup_module.css_class;
		} else {
			this.cssClass = 'eip-ieditor-empty';
		}

		this.jqWrapper = null;
		this.bEnabled = false;

		this.init(oEditor);

	};
	uAdmin.inherit(PopupModule, ModuleBase);

	PopupModule.isImageEditorModule = true;

	PopupModule.FANCYBOX_CSS_CLASS = settings.module.popup_module.fancybox_css_class;
	PopupModule.WRAPPER_CSS_CLASS = settings.module.popup_module.wrapper_css_class;
	PopupModule.THUMB_WIDTH = settings.module.popup_module.thumb_width;

	PopupModule.prototype.init = function(oEditor) {
		PopupModule.superclass.init.call(this, oEditor);

		this.jqWrapper = this.oEditor.jqImgNode.parents('.' + PopupModule.WRAPPER_CSS_CLASS);

		if (!this.jqWrapper.length) {
			this.bEnabled = false;
			this.setTitle(getLabel('js-ieditor-module-popup-title'));
			this.jqWrapper = jQuery('<a/>')
				.addClass(PopupModule.FANCYBOX_CSS_CLASS)
				.addClass(PopupModule.WRAPPER_CSS_CLASS)
				.attr('href', this.oEditor.jqImgNode.attr('src'));
		} else {
			this.bEnabled = true;
			this.setTitle(getLabel('js-ieditor-module-popup-title-active'));
		}

	};

	PopupModule.prototype.activate = function() {
		PopupModule.superclass.activate.call(this);

		if (this.bEnabled) {
			this.turnOff();
		} else {
			this.turnOn();
		}

		this.oEditor.reinit();
		this.oEditor.initLayout();

		uAdmin.ieditor.disable();
		uAdmin.ieditor.enable();

	};

	PopupModule.prototype.turnOn = function() {
		var oNode = this.oEditor.getEipNode(),
			sOldValue = '',
			sNewValue = '';
		if (oNode) {
			sOldValue = this.oEditor.cleanupHtml(oNode.innerHTML);
		}
		var flAspectRatio = this.oEditor.jqImgNode.width() / this.oEditor.jqImgNode.height();
		this.oEditor.jqImgNode
			.width(PopupModule.THUMB_WIDTH)
			.height(Math.round(PopupModule.THUMB_WIDTH / flAspectRatio))
			.attr({
				width: PopupModule.THUMB_WIDTH,
				height: Math.round(PopupModule.THUMB_WIDTH / flAspectRatio)
			})
			.wrap(this.jqWrapper)
			.removeAttr('data-mce-style');

		if (oNode) {
			sNewValue = this.oEditor.cleanupHtml(oNode.innerHTML);
		}
		if (tinymce && tinymce.activeEditor) {
			tinymce.activeEditor.on('remove', function() {
				window.setTimeout(fancybox_init, 100);
			});
		}
		uAdmin.eip.bind('Disable', function(type) {
			if (type == 'after') {
				window.setTimeout(fancybox_init, 500);
			}
		});
		this.addToEipQueue(sNewValue, sOldValue);
		uAdmin.eip.message(getLabel('js-ieditor-module-popup-title-msg'));
		uAdmin.eip.normalizeBoxes();
		this.bEnabled = true;
	};

	PopupModule.prototype.turnOff = function() {
		var oNode = this.oEditor.getEipNode(),
			sOldValue = '',
			sNewValue = '';
		if (oNode) {
			sOldValue = this.oEditor.cleanupHtml(oNode.innerHTML);
		}
		this.jqWrapper = this.oEditor.jqImgNode.parents('.' + PopupModule.WRAPPER_CSS_CLASS);
		this.oEditor.jqImgNode.insertAfter(this.jqWrapper);
		this.jqWrapper.remove();
		this.oEditor.jqImgNode[0].style.width = '';
		this.oEditor.jqImgNode[0].style.height = '';
		this.oEditor.jqImgNode.removeAttr('data-mce-style width height');
		if (oNode) {
			sNewValue = this.oEditor.cleanupHtml(oNode.innerHTML);
		}
		this.addToEipQueue(sNewValue, sOldValue);
		uAdmin.eip.message(getLabel('js-ieditor-module-popup-title-active-msg'));
		uAdmin.eip.normalizeBoxes();
		this.bEnabled = false;
	};

	PopupModule.changeImage = function(oEditor, isRemove) {
		if (oEditor instanceof ImageEditorBase === false) {
			return;
		}
		var oPopupModule = new PopupModule(oEditor);
		oPopupModule.init(oEditor);
		if (oPopupModule.bEnabled) {
			if (isRemove) {
				oPopupModule.jqWrapper.remove();
			} else {
				oPopupModule.turnOff();
				oPopupModule.turnOn();
			}
		}
		oPopupModule = null;
	};

	function DeleteModule (oEditor) {

		DeleteModule.superclass.constructor.call(this);

		this.title = getLabel("js-ieditor-module-delete-title");
		this.cssClass = settings.module.delete_module.css_class;

		this.init(oEditor);

	};
	uAdmin.inherit(DeleteModule, ModuleBase);

	DeleteModule.isImageEditorModule = true;

	DeleteModule.prototype.init = function (oEditor) {
		DeleteModule.superclass.init.call(this, oEditor);
		this.jqElement.find("." + this.MODULE_ICON_HOLDER_CSS_CLASS).html("&times;");
	};

	DeleteModule.prototype.activate = function () {
		DeleteModule.superclass.activate.call(this);

		var info = {};
		switch (this.oEditor.iImageType) {

			case this.oEditor.IMAGE_TYPE_EIP:
			case this.oEditor.IMAGE_TYPE_PHOTOALBUM:
				var sNewSrc = this.oEditor.jqImgNode.attr("umi:empty"),
					sOldSrc = this.oEditor.jqImgNode.attr('src');
				this.setNewImage(sNewSrc, false);
				this.oEditor.jqImgNode.attr("umi:is-stub", true);
				this.oEditor.redrawMenu();
				this.addToEipQueue(sNewSrc, sOldSrc);
				break;

			case this.oEditor.IMAGE_TYPE_WYSIWYG:
				var oEipNode = this.oEditor.getEipNode(),
					sOldValue = this.getWysiwygContent(oEipNode),
					sNewValue = '';
				this.setNewImage('');
				sNewValue = this.getWysiwygContent(oEipNode);
				this.addToEipQueue(sNewValue, sOldValue, oEipNode);
				break;

		}
		ImageEditorsCollection.getInstance().removeEmptyEditors();
		uAdmin.eip.normalizeBoxes();
		this.triggerEvent('onActivate');
	};


	function ApplyModule (oEditor) {
		CancelModule.superclass.constructor.call(this);

		this.title = getLabel("js-ieditor-module-apply-title");
		this.cssClass = settings.module.apply_module.css_class;
		this.oActiveModule = oEditor.getEnabledModule();

		this.init(oEditor);

	};
	uAdmin.inherit(ApplyModule, ModuleBase);
	ApplyModule.isImageEditorModule = true;

	ApplyModule.prototype.activate = function () {
		if (this.oActiveModule instanceof ModuleBase) {
			this.oActiveModule.save();
			this.oEditor.reinit();
		}
	};


	function CancelModule (oEditor) {
		CancelModule.superclass.constructor.call(this);

		this.title = getLabel("js-ieditor-module-cancel-title");
		this.cssClass = settings.module.cancel_module.css_class;
		this.oActiveModule = oEditor.getEnabledModule();

		this.init(oEditor);

	};
	uAdmin.inherit(CancelModule, ModuleBase);
	CancelModule.isImageEditorModule = true;

	CancelModule.prototype.activate = function () {
		if (this.oActiveModule instanceof ModuleBase) {
			this.oActiveModule.cancel();
		}
	};


	return extend(uImageEditor, this);

});
