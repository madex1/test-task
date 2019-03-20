$(function() {
	window.is_page = (uAdmin.data && uAdmin.data.data && uAdmin.data.data.page) ? true : false;
	window.is_new = (uAdmin.data && uAdmin.data.data && uAdmin.data.data.action == "create");

	initSelectizer();
	initBasicModulesSlider();

	if (uAdmin.data && uAdmin.data.data && (uAdmin.data.data.page || uAdmin.data.data.object || uAdmin.data.data.type)) {
		$('form.form_modify').on('keypress', function(event) {
			var enterKey = 13;

			if (event.keyCode == enterKey && event.target.tagName.toLowerCase() != 'textarea') {
				event.preventDefault();
			}
		})
	}

	var outItem = null;
	$(".basic-modules").sortable({
			revert: false,
			placeholder: "ui-state-highlight",
			containment: 'document',
			out: function(event, ui) {
				outItem = ui.item;
			},
			over: function() {
				outItem = null;
			},
			beforeStop: function() {
				if (outItem) {
					jQuery(outItem).remove();
					outItem = null;
					saveMenuSettings();
				}
			},
			stop: function() {
			},
			update: function(event, ui) {
				saveMenuSettings();
			}

		}
	);

	/** Обработчик перемещения модулей из общего меню в левое */
	$('div.other-modules div.modules a.module').draggable({
		connectToSortable: ".basic-modules",
		helper: 'clone',
		revert: 'invalid',
		containment: 'document',
		start: function(event, ui) {
			var module = ui.helper.attr('umi-module');

			$(".basic-modules a.module").filter(function() {
				return $(this).attr('umi-module') === module;
			}).remove();
		},
		stop: function (event, ui) {
			ui.helper
				.removeClass('ui-draggable ui-draggable-handle')
				.addClass('ui-sortable-handle')
				.removeAttr('style');
		}
	});

	//Меню
	$('.menu').click(function(event) {
		event.stopPropagation();
		var $menu = $(this);
		var $siblingsMenus = $menu.siblings('.menu');
		hideMenu($siblingsMenus);
		showMenu($menu);

		$(document).one('click', function(event) {
			var inMenu = !!$menu.find(event.target).length;

			if (!inMenu) {
				hideMenu($menu);
			}
		});

		function showMenu(menu) {
			var $menu = $(menu);
			$menu.children('.selected').addClass('open');
			$menu.children('ul').slideToggle(200);
		}

		function hideMenu(menu) {
			var $menu = $(menu);
			$menu.children('.selected').removeClass('open');
			$menu.children('ul').hide();
		}
	});

	//Выбор строки в таблице
	$('.table-row').click(function() {
		$(this).not('.title').addClass('selected').siblings().removeClass('selected');
	});

	//Заголовок таблицы
	$('.table-title').click(function() {
		if ($(this).hasClass('disabled')) {
			return;
		}
		$(this).toggleClass('switch');
	});

	//Тулбар вкл/выкл
	$('.onoff input:checked').parent().addClass('switch');
	$('.onoff').click(function() {
		$(this).toggleClass('switch');
	});

	//Чекбокс
	$('.checkbox input:checked').parent().addClass('checked');
	$('.checkbox').click(function() {
		$(this).toggleClass('checked');
	});

	// Инициализирует визуальные редакторы tinyMCE во всех подходящих полях
	if (typeof uAdmin.wysiwyg != 'undefined') {
		uAdmin.wysiwyg.init();
	}

	// Обработчик сворачивания и разворачивания группы полей
	$('.field-group-toggle').click(function() {
		var $header = $(this);
		var $button = $('div.round-toggle', $header);
		$button.toggleClass('switch');
		var $headerWrapper = $header.parent();
		var $container = $headerWrapper.parents('.panel-settings');
		$container.toggleClass('has-border');
		var $groupTipButton = $('a.group-tip-show', $headerWrapper);
		var isCollapsed = $button.hasClass('switch');

		if (isCollapsed) {
			$groupTipButton.hide();
		} else {
			$groupTipButton.show();
		}

		var typeId = $container.parent().data('type-id');
		storeHiddenGroups(typeId);

		$headerWrapper.next('.content').slideToggle(300, function() {

			$(this).prev().children('.infoblock-show').toggleClass('hide');

			if ($(this).parents('.panel-settings').find('.layout').hasClass('infoblock-active')) {
				$(this).prev().children('.infoblock-show').addClass('hide');
			}
		});
	});

	/**
	 * Сохраняет скрытые группы полей типа данных
	 * @param {Integer} typeId идентификатор типа данных
	 */
	function storeHiddenGroups(typeId) {
		if (!typeId) {
			return;
		}

		var hiddenGroupNameList = [];

		$('div.panel-settings.has-border a[data-name]').each(function() {
			hiddenGroupNameList.push($(this).data('name'));
		});

		var settings = SettingsStore.getInstance();
		settings.set('hidden-groups', hiddenGroupNameList.join('|'), typeId.toString());
	};

	/**
	 * Скрывает информациионный блок
	 * @param {jQuery|String|Element} buttonHide кнопка скрытия инфоблока
	 * @param {jQuery|String|Element} buttonShow кнопка показа инфоблока
	 */
	function hideInfoBlock(buttonHide, buttonShow) {
		$(buttonHide).parent('.infoblock').fadeOut(300, function() {
			$(this).parents('.layout').removeClass('infoblock-active');
		});

		if ($(buttonHide).parents().eq(4).find(buttonShow).hasClass('hide')) {
			$(buttonHide).parents().eq(4).find(buttonShow).removeClass('hide');
		}
	}

	//Инфоблок
	$('.infoblock-hide').click(function() {
		hideInfoBlock(this, '.infoblock-show');
	});

	$('.infoblock-show').click(function() {
		var $layout = $(this).parents().eq(1).find('.layout');
		var $infoBlock = $(this).parents().eq(1).find('.infoblock');

		if ($layout.length > 0 && $infoBlock.length > 0) {
			$(".infoblock .content").trigger("helpopen");
			$(this).addClass('hide');
			$layout.addClass('infoblock-active');
			$infoBlock.fadeIn(300);
		}
	});

	$('.group-tip-hide').click(function() {
		hideInfoBlock(this, '.group-tip-show');
	});

	$('.group-tip-show').click(function() {
		var button = $(this);
		var groupWrapper = button.closest('.panel-settings');

		var tipElement = groupWrapper.find('summary.group-tip');

		if (tipElement.length) {
			showGroupTip(groupWrapper, tipElement.html());
			return;
		}

		var groupNameWithPrefix = groupWrapper.attr('name');

		if (!groupNameWithPrefix) {
			return;
		}

		var groupName = groupNameWithPrefix.replace(/^g_/, '');

		processEntityData(function(JSONPath, data) {
			var group = JSONPath({
				path: '$..group[?(@.name=="' + groupName + '")]',
				json: data
			});

			var groupTip = '';
			if (group && group[0]) {
				groupTip = group[0]['tip'];
			}

			showGroupTip(groupWrapper, groupTip);
		});

		function showGroupTip(groupWrapper, groupTip) {
			var infoBlock = groupWrapper.find('.infoblock');
			var layout = groupWrapper.find('.layout');
			var contentElement = infoBlock.children('.content');

			contentElement.html(groupTip);
			button.addClass('hide');
			layout.addClass('infoblock-active');
			infoBlock.fadeIn(300);
		}
	});

	$(".infoblock .content").one("helpopen", {}, function() {
		var e = jQuery(this);
		var url = e.attr("title").substr(1);
		e.attr("title", "");
		jQuery.get(url, {}, function(data) {
			data = data.substr(data.indexOf('<body>') + 6);
			data = data.substr(0, data.indexOf('</body>'));

			e.html(data);
		});
	});

	//Вызов меню модулей
	(function() {
		var modules = $('.other-modules');
		var selectModules = $('.select-modules');

		selectModules.on('click', function() {
			$(this).toggleClass('active');
			$(this).prev(modules).toggleClass('open');

			var menuModulesBackground = $('.menu-modules-bg');

			if (menuModulesBackground.length === 0) {
				$(this).parents('.main').append('<div class="menu-modules-bg"></div>');
			} else {
				menuModulesBackground.remove();
			}
		});

		var closeOtherModules = function() {
			selectModules.removeClass('active');
			modules.removeClass('open');
			$('.menu-modules-bg').remove();
		};

		$('body').on('click', '.menu-modules-bg', closeOtherModules);

		var ESCAPE_KEYCODE = 27;

		$(document).on('keyup.closeOtherModules', function(e) {
			if (e.which === ESCAPE_KEYCODE) {
				closeOtherModules();
			}
		});
	}());

	//Select

	//Главный контейнер
	var container = $('.select');

	container.each(function() {
		var current = $(this);
		buildSelect(current);
	});

	if ($("a.extended_fields_expander").length > 0) {
		markExtendedFields(function() {
			var extendedFieldsContainer = $("div.extended_fields"),
				panels = $('div.panel-settings'),
				extendedFieldToggler = $("a.extended_fields_expander");

			if (extendedFieldsContainer.length == 0) {
				var settingsMenu = new controlSettingsMenu({});
				settingsMenu.scrollToCurrentAnchor();
				extendedFieldToggler.hide();
				return false;
			}

			extendedFieldToggler.click(function() {
				if (!getCookie("expandExtendedFields") || extendedFieldsContainer.css("display") == "none") {
					extendedFieldsContainer.show();
					panels.show();
					$(this).attr('title', $(this).data('collapse-text'));
				} else {
					$(this).attr('title', $(this).data('expand-text'));
					extendedFieldsContainer.hide();
					hidePanels(panels);
				}

				if (extendedFieldsContainer.css("display") == "none") {
					setCookie("expandExtendedFields", null);
					$('i', extendedFieldToggler).attr('class', 'small-ico i-slidedown');
				} else {
					setCookie("expandExtendedFields", "1");
					$('i', extendedFieldToggler).attr('class', 'small-ico i-slideup');
				}
				var setMenu = $("div.tabs.editing");
				if (setMenu.length > 0) {
					setMenu.html('');
					new controlSettingsMenu({});
				}
			});

			if (getCookie("expandExtendedFields") || location.href.indexOf("/add/") != -1) {
				extendedFieldsContainer.show();
				$('i', extendedFieldToggler).attr('class', 'small-ico i-slideup');
			} else {
				$('i', extendedFieldToggler).attr('class', 'small-ico i-slidedown');
				extendedFieldsContainer.hide();
				hidePanels(panels);
			}

			if ($("div.tabs.editing").length > 0) {
				var settingsMenu = new controlSettingsMenu({});

				$(window).on('load', function() {
					settingsMenu.scrollToCurrentAnchor();
				});
			}

		});

	} else {
		if ($("div.tabs.editing").length > 0) {
			var settingsMenu = new controlSettingsMenu({});
			settingsMenu.scrollToCurrentAnchor();
		}
		$("div.extended_fields").show();
	}
	//----------------------------------------------------
	// Extended fields

	// Sharing
	$(".share-buttons").click(function() {
		var root = $(this).parent();
		root.children("#ya_share1").toggle(0);
		if (root.children("#ya_share1:visible").length) {
			setCookie(root.attr("class"), null);
			$(".share-buttons .switch").text('»');
		} else {
			setCookie(root.attr("class"), "1");
			$(".share-buttons .switch").text('«');
		}
	}).each(function() {
		var root = $(this).parent();
		if (getCookie(root.attr("class"))) {
			root.children("#ya_share1").attr('style', 'display:none');
			$(".share-buttons .switch").text('«');
		}
	});

	// Sync name/alt-name/H1 fields
	if (window.is_page) {
		var nameInput = $("input:text[name=name]");
		var altNameInput = $("input:text[name=alt-name]");
		var h1Input = $("input:text[name$='[h1]']");
		var callback = null;
		var changeAvailable = true;
		var separator = '';
		$.ajax({
			type: "POST",
			url: '/udata/system/getSeparator',
			async: false,
			success: function(data) {
				separator = $('separator', data).attr("value");
			}
		});
		if (window.is_new || altNameInput.val() == '') {
			callback = function() {
				if (changeAvailable) {
					var pattern = "[^A-Za-z0-9" + separator + "]+", reg = new RegExp(pattern, 'gi');
					altNameInput.val(transliterateRu(this.value.toLowerCase()).replace(/\s+/g, separator).replace(reg, ""));
				}

				h1Input.val(this.value);
			};
		} else {
			callback = function() {
				h1Input.val(this.value);
			};
		}
		nameInput.focus(function() {
			if (window.is_new || (h1Input.val() === nameInput.val()) || altNameInput.val() == '') {
				jQuery(this).bind("keyup", callback);
			}

		}).blur(function() {
			jQuery(this).unbind("keyup", callback);
		});

		altNameInput.change(function() {
			changeAvailable = (altNameInput.val() == '');
		});
	}

	jQuery("#permissionsContainer").each(function() {
		var e = jQuery(this);
		var p = new permissionsControl(e.attr("id"));
		jQuery("ul>li", e).each(function() {
			var li = jQuery(this);
			p.add(li.attr("umi:id"), li.text(), li.attr("umi:access"));
		});
		jQuery("ul", e).remove();
		jQuery("input:submit").removeAttr("disabled");
	});

	jQuery("a.tagPicker").each(function() {
		var e = jQuery(this);
		e.click(function() {
			jQuery.openPopupLayer({
				name: "TagsCloud",
				title: getLabel('js-tags-cloud'),
				width: 400,
				height: 200,
				url: window.pre_lang + "/admin/stat/get_tags_cloud/" + e.attr('id').replace(/^link/, "") + "/"
			});
		});
	});

	window.returnNewTag = function(inputId, tag, link) {
		var input = jQuery("#" + inputId);
		if (jQuery(link).hasClass('disabledTag')) {
			jQuery(link).removeClass('disabledTag');
			var tagList = input.val().split(",");
			var result = [];
			for (var i = 0; i < tagList.length; i++) {
				tagList[i] = tagList[i].replace(/^\s*/, "").replace(/\s*$/, "");
				if (tagList[i] !== tag) {
					result.push(tagList[i]);
				}
			}
			input.val(result.join(", "));
		} else {
			jQuery(link).addClass('disabledTag');
			var val = input.val(),
				str = val.length > 0 ? val + ', ' : '';
			input.val(str + tag);
		}
	};

	initDatepicker();
	initFileManager();
	initSymlinckField();
	initRelationsField();
	initDomainsField();
	InitColorFields();
	initOptionedFields();
	initTypeSelector();
	initCatalogControls();
	initCreateTypeSelector();

	function initCatalogControls() {
		jQuery('.field.changeParent').each(function() {
			var fieldElement = jQuery(this);
			var module = uAdmin.data ? uAdmin.data.module : 'catalog';
			var hierarchyType = fieldElement.attr('umi:type');
			var modeVirtual = fieldElement.hasClass('virtuals');
			var control = null;

			if (modeVirtual) {
				control = new CatalogModule.CopyCreatingControl(fieldElement.attr('id'), module, {}, hierarchyType);
			} else {
				control = new CatalogModule.ElementMovingControl(fieldElement.attr('id'), module, {}, hierarchyType);
			}

			CatalogModule.addControl(control);
		});
	}

	/**
	 * Устанавливает для расширенных (неважных) полей класс extended_fields
	 * @param {Function} complete выполняется по успешному завершению установки
	 */
	function markExtendedFields(complete) {
		processEntityData(function(JSONPath, data) {
			var fieldsData = JSONPath({
				path: '$..field.*',
				json: data
			});

			$.each(fieldsData, function() {
				var field = this;
				var inputName = field['input_name'];

				if (!inputName) {
					return;
				}

				var escapedInputName = inputName.replace(/([\[\]])/gi, '\\$1');
				var fieldsSelector = '*[name=' + escapedInputName + ']';
				var domElement = $(fieldsSelector);
				var domField = domElement.closest('.col-md-6, .col-md-12');
				var isRequired = field['required'] !== undefined && field['required'] == 'required' ? true : false;
				var isExtendedField = !field['is_important'];

				if (isExtendedField && !isRequired) {
					domField.addClass('extended_fields');
				}
			});

			var completeFunction = typeof complete == 'function' ? complete : function() {
			};
			completeFunction();
		});
	}

	$('div.img_file').each(function() {
		new ControlImageFile({container: $(this)});
	});

	$('div.multiimage').each(function() {
		new ControlMultiImage({container: $(this)});
	});

	if (uAdmin.data.module != 'events') {
		$.getJSON('/admin/events/feed/.json', {
			'filter': 'users-adv-message',
			'onlyUnread': 'true',
			'limit': 1
		}, function(r) {
			if (r.data && r.data.events && r.data.events.event) {
				jQuery.each(r.data.events.event, function(index, value) {
					jQuery.jGrowl(value.value, {
						'header': getLabel('js-users-adv-message'),
						'sticky': true,
						'close': function(e, m, o) {
							if (m) {
								jQuery.get('/admin/events/markReadEvents/.json', {'events[]': value.id});
							}
						}
					});
				});
			}
		});
	}

	$('#is-active-control').click(function() {
		var el = $('i.small-ico', $(this));

		if (el.hasClass('i-vision')) {
			el.removeClass('i-vision');
			el.addClass('i-hidden');
			$('#is-active').val(1);
			$(this).prop('title', getLabel('js-tip-is-active'));
		} else {
			el.removeClass('i-hidden');
			el.addClass('i-vision');
			$('#is-active').val(0);
			$(this).prop('title', getLabel('js-tip-is-noactive'));
		}
	});

});

function hidePanels(panels) {
	$.each(panels, function(id, val) {
		if (!$(val).hasClass('extended_fields')) {
			var els = $('div.row > div[class^=col-md]', val),
				cnt = els.length,
				calc = 0;
			if (cnt > 0) {
				for (var i = 0; i < cnt; i++) {
					if ($(els[i]).css('display') == 'none') {
						calc++;
					}
				}
				if (calc == cnt) {
					$(val).hide();
				}
			}
		}
	});
}

function changeEditLink() {
	var link = jQuery("a#edit").attr('href');
	var value = jQuery("select.edit").val();
	var newlink = '/admin/data/type_edit/' + value + '/';
	jQuery("a#edit").attr('href', newlink);
}

function initSelectizer() {
	$('.default.newselect').each(function() {
		var $this = $(this);
		var plugins = ['remove_button'];
		if ($this.parent('.domains_selector').size() == 0) {
			plugins.push('clear_selection');
		}

		$this.selectize({
			plugins: plugins,
			allowEmptyOption: true,
			create: false
		});
	});
}

function buildSelect(current) {
	current = $(current);
	//Выраный пунк select'а
	var selected = current.find('.selected');
	//select
	var select = current.find('select');
	// option
	var option = current.find('option');
	// option:selected
	var optionSelected = current.find('option:selected');
	//Список
	var list = current.find('.list');

	//Выбраный пункт select'а передаем в div.selected
	selected.text(optionSelected.text());

	//Все пункты select'а передаем в список ul.select-list
	option.each(function(i, item) {
		list.append('<li>' + $(item).text() + '</li>');
	});

	//При клике на пункт списка очищаем div.selected, и перадаём в него и в select выбранное значение
	current.find('li').each(function(i, item) {
		$(item).click(function() {
			selected.text('');
			selected.append($(this).text());
			optionSelected.text(selected.text());
		});
	});

	//Открываем / Скрываем выпадающий список
	current.bind('click', function() {
		if (selected.hasClass('focus')) {
			list.slideUp(200, function() {
				selected.removeClass('focus');
			});
			return;
		}

		list.slideDown(200);
		selected.addClass('focus');
	});

	//Передаем параметры клика в event и ищем у него родителя .select
	$(document).click(function(event) {
		if ($(event.target).parents(".select").is(current)) {
			return;
		}
		list.slideUp(200, function() {
			selected.removeClass('focus');
		});
	});
}

function createConfirm(dataSetObject) {
	return function(arrData) {
		var Method = arrData['method'];
		var Params = arrData['params'];
		var hItem = Params['handle_item'];
		if (Params['allow']) {
			return true;
		}
		var dlgTitle = "";
		var dlgContent = "";
		var dlgOk = "";
		var dlgCancel = getLabel('js-cancel');

		Control.enabled = false;
		ContextMenu.allowControlEnable = false;
		switch (Method) {
			case "tree_delete_element" :
				if (Control.HandleItem != null && Control.HandleItem.control.flatMode) {
					dlgTitle = getLabel('js-del-object-title-short');
					dlgContent = getLabel('js-del-shured');
				} else if (Control.HandleItem != null && Control.HandleItem.control.objectTypesMode) {
					dlgTitle = getLabel('js-del-object-type-title-short');
					dlgContent = getLabel('js-del-object-type-shured');
				} else {
					dlgTitle = getLabel('js-del-short-title');
					dlgContent = getLabel('js-del-shured');
				}

				dlgOk = getLabel('js-del-do');
				break;
			case "tree_copy_element" :

				if (hItem == undefined || (hItem != undefined && !hItem.hasChilds)) {
					return true;
				}

				if (Params['clone_mode']) {
					// real copy
					dlgTitle = getLabel('js-copy-title');
					dlgContent = getLabel('js-copy-shured');
					dlgOk = getLabel('js-copy-do');
				} else {
					// virtual copy
					dlgTitle = getLabel('js-vcopy-title');
					dlgContent = getLabel('js-vcopy-shured');
					dlgOk = getLabel('js-copy-do');
				}

				dlgContent += '<br/><br /><input type="checkbox" id="copy-all" />&nbsp;<label for="copy-all">' + getLabel('js-copy-all') + '</label>';
				break;

			case 'move':
				if (hItem && hItem.control && hItem.control.contentType == 'objects') {
					moveEntity();
					return;
				}
			case "tree_move_element" :
				dlgTitle = getLabel('js-move-title');
				dlgContent = getLabel('js-move-shured');
				dlgOk = getLabel('js-move-do');
				break;
			default:
				return true;
				break;
		}

		dlgContent = '<div class="confirm">' + dlgContent + '</div>';

		openDialog('', dlgTitle, {
			html: dlgContent,
			confirmText: dlgOk,
			cancelButton: true,
			cancelText: dlgCancel,
			confirmCallback: function(dialogName) {
				moveEntity();
				closeDialog(dialogName);
			},
			cancelCallback: function() {
				Control.enabled = true;
				ContextMenu.allowControlEnable = true;
			}
		});

		function moveEntity() {
			Params['allow'] = true;

			if (Method == 'tree_copy_element') {
				Params['copy_all'] = jQuery('#copy-all').attr("checked") ? 1 : 0;
			}

			dataSetObject.execute(Method, Params);
			Control.enabled = true;
			ContextMenu.allowControlEnable = true;
		}

		return false;
	};
}

function initFileManager() {
	$("div.file").each(function() {
		var e = $(this);
		var defaultFolder = './images/cms/data';
		var options = {
			inputName: e.attr("umi:input-name"),
			folderHash: e.attr("umi:folder-hash"),
			fileHash: e.attr("umi:file-hash"),
			lang: e.attr("umi:lang"),
			fm: e.attr("umi:filemanager"),
			onGetFileFunction: e.attr('umi:on_get_file_function')
		};

		switch (e.attr("umi:field-type")) {
			case "file"       :
			case "swf_file"   :
				defaultFolder = './files';
				break;
			case "video_file" :
				options.videosOnly = true;
				defaultFolder = './files/video';
				break;
			case "img_file"   : {
				options.imagesOnly = true;
				switch (e.attr("umi:name")) {
					case "header_pic" :
						defaultFolder = './images/cms/headers';
						break;
					case "menu_pic_a" :
					case "menu_pic_ua":
						defaultFolder = './images/cms/menu';
						break;
				}
			}
		}

		var c = new fileControl(e.attr("id"), options);
		c.setFolder(defaultFolder, true);

		if (e.attr("umi:folder")) {
			c.setFolder(e.attr("umi:folder"));
		}

		c.add(e.attr("umi:file"), true);
	});

	$("div#filemanager_upload_files a").click(function() {
		var lang = $(this).attr("umi:lang");
		var fm = $(this).attr("umi:filemanager");
		var folder = './files';
		var folderHash = '';

		var functionName = 'show' + fm + 'FileBrowser';
		eval(functionName + '(folder, folderHash, lang)');

	});

	// вызывается динамически через eval
	//noinspection JSUnusedLocalSymbols
	var showflashFileBrowser = function(folder, folder_hash, lang) {
		var qs = '';

		if (folder) {
			qs = qs + '&folder=' + folder;
		}

		$.openPopupLayer({
			name: "Filemanager",
			title: getLabel('js-file-manager'),
			width  : 1200,
			height : 600,
			url: "/styles/common/other/filebrowser/umifilebrowser.html?" + qs
		});
	};

	// вызывается динамически через eval
	//noinspection JSUnusedLocalSymbols
	var showelfinderFileBrowser = function(folder, folder_hash, lang) {
		var qs = '';

		if (typeof(folder_hash) != 'undefined') {
			qs = qs + '&folder_hash=' + folder_hash;
		}
		if (lang) {
			qs = qs + '&lang=' + lang;
		}
		$.openPopupLayer({
			name: "Filemanager",
			title: getLabel('js-file-manager'),
			width  : 1200,
			height : 600,
			url: "/styles/common/other/elfinder/umifilebrowser.html?" + qs
		});

		var options = '<div id="watermark_wrapper"><label for="add_watermark">';
		options += getLabel('js-water-mark');
		options += '</label><input type="checkbox" name="add_watermark" id="add_watermark"/>';
		options += '<label for="remember_last_folder">';
		options += getLabel('js-remember-last-dir');
		options += '</label><input type="checkbox" name="remember_last_folder" id="remember_last_folder"'
		if (getCookie('remember_last_folder', true)) {
			options += 'checked="checked"';
		}
		options += '/></div>';

		$('#popupLayer_Filemanager .popupBody').append(options);
	};
}

function initDatepicker() {
	jQuery.datepicker.setDefaults(jQuery.extend({
		showOn: 'focus',
		duration: 0,
		constrainInput: false,
		dateFormat: 'yy-mm-dd'
	}, jQuery.datepicker.regional["ru"]));

	jQuery("div.datePicker").each(function() {
		var input = jQuery("input", jQuery(this));
		var dateOnly = jQuery(this).attr('umi:date-only');
		var dateFormat = jQuery(this).attr('umi:date-format') || 'yy-mm-dd';

		input.datepicker({
			dateFormat: dateFormat,
			onClose: function(dateText) {
				var valueContainsDate = /^\d{4}-\d{2}-\d{2}/.exec(dateText);
				var valueContainsTime = /\d{1,2}:\d{1,2}(:\d{1,2})?$/.exec(dateText);

				if (valueContainsDate && !valueContainsTime && !dateOnly) {
					dateText = dateText + " 00:00:00";
				}

				input.val(dateText);
			}
		});
	});
}

/** Инициализирует поля типа "Выпадающий список" и "Выпадающий список со множественным выбором" */
function initRelationsField() {
	jQuery("div.relation").each(function() {
		var e = jQuery(this);
		new ControlRelation({
			container: e,
			type: e.attr("umi:type"),
			id: e.attr("id"),
			empty: (e.attr("umi:empty") === "empty")
		});
	});

	appendEmptyInputFieldsForEmptySelects('div.relation select[multiple="multiple"]');
}

/**
 * Создает обработчик события отправки формы.
 * Обработчик добавляет в форму пустые <input>-теги для переданных множественных селектов,
 * если у этих селектов не выбрано ни одного значения.
 * @param {String} selector jQuery-селектор для множественных селектов
 */
function appendEmptyInputFieldsForEmptySelects(selector) {
	$('form').submit(function() {
		var selectList = $(selector);
		var form = this;

		selectList.each(function() {
			var name = $(this).prop('name');
			if ($(this).val() == null) {
				$(form).append($('<input type="hidden" name="' + name + '" value=""/>'));
			}
		});

		return true;
	});
}

/** Инициализирует поля типа "Ссылка на список доменов" */
function initDomainsField() {
	appendEmptyInputFieldsForEmptySelects('div.domain_field select[multiple="multiple"]');
}

/** Инициализирует контролы полей типа "Составное" на странице редактирования страницы. */
function initOptionedFields() {
	var optionTemplate = '';
	var addOptionButtonSelector = 'a.add-option';

	jQuery("div.optioned").each(function() {
		var $container = $(this);
		var $select = $('select', $container);
		var isStores = $container.hasClass('stores');

		// noinspection ObjectAllocationIgnored
		new ControlRelation({
			container: $container,
			type: $select.attr("umi:guide")
		});

		bindRemoveHandler($container);

		$(addOptionButtonSelector, $container).on('click', function() {
			var $select = $('select', $container);
			var chosenRelationId = $select.get(0).selectize.getValue();
			var number = $(this).parent().parent().find('.edit').val();

			if (_.isEmpty(chosenRelationId) || _.isEmpty(number)) {
				return;
			}

			var label = $('.selectize-input .item', $container).text();
			var position = $('.values .layout-row-icon.row', $container).length + 1;
			var inputName = $select.attr("umi:name");
			var options = {
				label: label,
				labelRemoveOption: getLabel('js-remove-option'),
				inputName: inputName,
				position: position,
				relationId: chosenRelationId,
				value: number,
				isStores: isStores
			};

			if (optionTemplate) {
				appendData($container, optionTemplate, options);
			} else {
				$.ajax({
					url: '/styles/skins/modern/design/js/common/optioned_template.html',
					dataType: 'html',
					success: function(html) {
						optionTemplate = $($.parseHTML(html)).find('#option_template').html();
						appendData($container, optionTemplate, options);
					}
				});
			}
		});

		bindEnter($('input.number', $container), function(input) {
			var addButton = $(input).parent().parent().find(addOptionButtonSelector);
			if (!addButton) {
				return;
			}

			addButton.click();
		});
	});

	/**
	 * Назначает обработчики события нажатия на кнопки удаления
	 * @param {jQuery|Element} container элемент-контейнер для поля
	 */
	function bindRemoveHandler(container) {
		$('a.remove-option', container).bind('click', function() {
			if ($('div.values > div', container).length == 1) {
				var field = $(this).parent().parent().find('input.default');
				field = field.attr('name');
				field = field.replace('data[', '');
				field = field.substr(0, field.length - 2);
				field = field.split('][');
				$('div.values').append('<input type="hidden" umi:type="int" name="data[' + field[0] + '][' + field[1] + '][1][int]" value="">');
			}
			$(this).parent().parent().parent().remove();
		});
	}

	/**
	 * Назначает обработчик события нажатия клавиши <ENTER> в текстовом поле
	 * @param {HTMLCollection|jQuery|String} elements элементы, для которых нужно назначить обработчики
	 * @param {Function} handler непосредствнный обработчик события
	 */
	function bindEnter(elements, handler) {
		var handlerFunc = typeof handler == 'function' ? handler : function() {
		};

		$(elements).unbind('keypress');
		$(elements).bind('keypress', function(event) {
			var enterKey = 13;

			if (event.keyCode == enterKey) {
				event.preventDefault();
				handlerFunc(this);
			}
		});
	}

	/**
	 * Добавляет DOM-элементы для заполнения составной опции
	 * @param {jQuery|Element} container элемент-контейнер для поля
	 * @param {String} template шаблон для обработки
	 * @param {Object} options объект с данными, передаваемый в шаблон
	 */
	function appendData(container, template, options) {
		var valuesDivSelector = 'div#' + container.attr('id') + ' > div > div.values';

		if ($(valuesDivSelector + ' > input').length > 0) {
			$(valuesDivSelector).html('');
		}
		$('.values', container).append(jQuery.tmpl(template, options));
		bindRemoveHandler(container);
	}
}

/**
 * Инициализирует кнопку добавления сущности с выбором типа
 * @param {Number} domainId идентификатор домена
 */
function initTypeSelector(domainId) {
	var addButtons = jQuery("div.imgButtonWrapper a").filter(function() {
		return jQuery(this).attr("umi:type");
	});

	setInterval(function() {
		var tableControl = window['oTable'];
		if (!tableControl) {
			return;
		}

		if (_.size(tableControl['selectedList']) > 1) {
			addButtons.closest('.imgButtonWrapper').hide();
		} else {
			addButtons.closest('.imgButtonWrapper').show();
		}
	}, 200);

	domainId = domainId || getCookie('control-domain-id');
	// Type Selector
	addButtons.each(function() {
		var e = jQuery(this);
		var link = e.attr('href');
		var ul = e.closest('.btn-select').find('ul.list').eq(0);

		jQuery.get("/utype/child/" + e.attr("umi:type") + '/' + domainId, {}, function(response) {
			var linkAddress;
			jQuery("type", response).each(function() {
				var type = jQuery(this);
				linkAddress = link + '?type-id=' + type.attr("id");
				jQuery(ul).append("<li><a href='" + linkAddress + "' umi:type-id='" + type.attr("id") + "' title='" + type.attr("title") + "'>" + type.attr("title") + "</a></li>");
			});
		});
	});
}

function initSymlinckField() {
	jQuery("div.symlink").each(function() {
		var e = jQuery(this);
		var l = jQuery("ul", e);
		var label = $(e).find('div.title-edit');
		var shTypes = label.prop('className').split(' ');
		var hTypes = [];

		for (var o = 0; o < shTypes.length; o++) {
			if (shTypes[o] != 'title-edit') {
				hTypes.push(shTypes[o]);
			}
		}

		var mode = e.hasClass('onlyOne');
		var s = new symlinkControl(e.attr("id"), "content", [],
			{
				inputName: e.attr("name"),
				fadeColorStart: [255, 255, 225],
				fadeColorEnd: [255, 255, 255]
			},
			hTypes, mode);

		jQuery("li", e).each(function() {
			var li = jQuery(this);
			s.addItem(li.attr("umi:id"), li.text(), [li.attr("umi:module"), li.attr("umi:method")], li.attr("umi:href"));
		});

		l.remove();
	});
}

function InitColorFields() {
	if (typeof colorControl == 'undefined') {
		jQuery('<script src="/styles/common/js/color.control.js" type="text/javascript" charset="utf-8"></script>').appendTo('head');
	}

	jQuery("div.color").each(function() {
		var e = jQuery(this);
		new colorControl(e, {});
	});
}

var openDialog = (function($) {
	var _dialogTemplate = '';

	/**
	 * Открывает плавающее диалоговое окно
	 * @param {String} text основной текст окна
	 * @param {String} title заголовок окна
	 *
	 * @param {Object} options опции окна
	 * @param {String} options.name имя окна
	 * @param {Number} options.width ширина окна в px
	 * @param {Boolean} options.stdButtons отображать стандартные кнопки
	 * @param {Boolean} options.confirmButton показывать кнопку подтвержения
	 * @param {Boolean} options.cancelButton показывать кнопку отмены
	 * @param {Boolean} options.confirmText надпись на кнопке подтверждения
	 * @param {Boolean} options.cancelText надпись на кнопке отмены
	 * @param {String|jQuery} options.html HTML содержимое окна
	 * @param {Number} options.timer количество милисекунд, через которое окно будет автоматически закрыто
	 * @param {String} options.customClass класс, который будет добавлен к родительскому элементу окна
	 * @param {String|jQuery|HTMLElement} options.confirmOnEnterElement элемент, при нажатии клавиши <ENTER>
	 * в котором будет вызываться функция options.confirmCallback
	 *
	 * @param {Function} options.confirmCallback выполняется при нажатии на кнопку подтвержения
	 * @param {String} name имя окна
	 * @param {String} popupId id родительского элемента окна
	 *
	 * @param {Function} options.cancelCallback выполняется при нажатии на кнопку отмены
	 * @param {String} name имя окна
	 * @param {String} popupId id родительского элемента окна
	 *
	 * @param {Function} options.closeCallback выполняется при закрытии окна
	 *
	 * @param {Function} options.openCallBack выполняется сразу после открытия окна
	 * @param {String} popupId id родительского элемента окна
	 *
	 * @example
	 * openDialog('Text');
	 * // Окно c текстом 'Text' без заголовка
	 * @example
	 * openDialog('Text', 'Title');
	 * // Окно c текстом 'Text' и заголовком 'Title'
	 * @example
	 * openDialog('Text', 'Title', {timer: 3000});
	 * // Окно будет автоматически закрыто через 3 секунды
	 * @example
	 * openDialog('Text', 'Title', {
     *   cancelButton: true,
     *   confirmButton: false
     * });
	 * // Окно с единственной кнопкой отмены
	 * @example
	 * openDialog('Text', 'Title', {
     *   html: '<div>Hello</div>'
     * });
	 * // Окно с вставленным HTML-кодом
	 */
	function openDialog(text, title, options) {
		var confirmId = Math.round(Math.random() * 100000);
		var namePrefix = 'ModernDialog';
		var name = namePrefix + confirmId;
		var popupPrefix = 'popupLayer_';
		options = options || {};
		options.closeCallback = typeof options.closeCallback == 'function' ? options.closeCallback : function() {
		};
		text = text || '';
		title = title || '';

		var defaultOptions = {
			name: name,
			title: title,
			data: '',
			width: 500,
			stdButtons: true,
			confirmButton: true,
			confirmText: "OK",
			cancelButton: false,
			cancelText: getLabel("js-cancel"),
			html: '',
			timer: 0,
			zIndex: 400000,
			customClass: '',
			closeButton: true,
			confirmOnEnterElement: null,
			confirmCallback: function() {
				closeDialog(options.name || name);
			},
			cancelCallback: function() {
			},
			closeCallback: function() {
			},
			openCallback: function() {
			}
		};

		options = jQuery.extend(defaultOptions, options);
		var resultHTML = '';

		if (options.html instanceof $) {
			$(options.html).each(function() {
				resultHTML += this.outerHTML;
			});

			options.html = resultHTML;
		}

		var popupId = '#' + popupPrefix + options.name;
		var popupParams = {
			name: options.name,
			width: options.width,
			text: text,
			title: title,
			buttons: options.stdButtons,
			cancelButton: options.cancelButton,
			confirmButton: options.confirmButton,
			confirmText: options.confirmText,
			cancelText: options.cancelText,
			afterClose: options.closeCallback,
			zIndex: options.zIndex,
			html: options.html,
			closeButton: options.closeButton,
			id: popupId,
			success: function() {
				if (options.customClass) {
					$(popupId).addClass(options.customClass);
				}

				if (options.timer > 0) {
					setTimeout(function() {
						closeDialog(options.name);
					}, options.timer);
				}

				$('#confirm-button', popupId).click(function() {
					options.confirmCallback(options.name, popupId);
				});
				$('#cancel-button', popupId).click(function() {
					options.cancelCallback(options.name, popupId);
					closeDialog(options.name);
				});
				$('#close-button', popupId).click(function() {
					closeDialog(options.name);
				});

				if (options['confirmOnEnterElement']) {
					var $element = null;

					if (typeof options['confirmOnEnterElement'] == 'boolean') {
						$element = $(popupId);
					} else {
						$element = $(options['confirmOnEnterElement'], popupId);
					}

					$element.bind('keypress', function(event) {
						var enterKey = 13;

						if (event.which == enterKey) {
							options.confirmCallback(options.name, popupId);
						}
					});
				}

				options.openCallback(popupId);
			}
		};

		openPopup(popupParams);

		function openPopup(options) {
			if (_dialogTemplate) {
				options['data'] = processTemplate(_dialogTemplate, options);
				jQuery.openPopupLayer(options);
				return true;
			}

			var dialogSource = '/styles/skins/modern/design/js/common/dialog.html';

			jQuery.get(dialogSource, function(response) {
				_dialogTemplate = response;
				options['data'] = processTemplate(_dialogTemplate, options);
				jQuery.openPopupLayer(options);
			});

			function processTemplate(template, data) {
				var _dialogElements = jQuery.tmpl(template, data);
				var result = '';

				_dialogElements.each(function() {
					if (this.outerHTML) {
						result += this.outerHTML;
					}
				});

				return result;
			}
		}
	}

	return openDialog;

})(jQuery);

var processEntityData = (function($) {
	/**
	 * Производит обработку данных страницы/объекта в формате JSON
	 * @param {Function} process({JSONPath} JSONPath, {JSON} entityData) функция обработки данных
	 * JSONPath - аналог XPath для JSON (https://github.com/s3u/JSONPath)
	 * entityData - данные текущей страницы/объекта/типа в формате JSON
	 */
	function processEntityData(process) {
		if (!uAdmin || !uAdmin.data || !uAdmin.data.data) {
			return;
		}

		var adminData = uAdmin.data.data;
		var entityData = adminData.page || adminData.object || adminData.type;

		if (!entityData) {
			return;
		}

		var processFunction = typeof process == 'function' ? process : function() {
		};

		if (typeof JSONPath == 'undefined') {
			getJSONPathScript(function() {
				return processFunction(JSONPath, entityData);
			});
		} else {
			return processFunction(JSONPath, entityData);
		}
	}

	return processEntityData;

})(jQuery);

function getJSONPathScript(callback) {
	var callbackFunc = typeof callback == 'function' ? callback : function() {
	};

	$.getScript('/styles/skins/modern/design/js/jsonpath.js', callbackFunc);
}

/**
 * Закрывает плавающее диалоговое окно
 * @param {String} [name] имя окна
 * @param {Function} [callback] выполняется после закрытия окна
 */
function closeDialog(name, callback) {
	callback = typeof callback == 'function' ? callback : function() {
	};

	if (name) {
		jQuery.closePopupLayer(name);
	} else {
		jQuery.closePopupLayer();
	}

	callback();
}

/**
 * Возвращает popup окно по его имени
 * @param {String} popupName имя искомого окна
 * @returns {*}
 */
function getPopupByName(popupName) {
	return _.find($.getOpenedPopups(), function(popup) {
		return popup.name == popupName;
	});
}

function confirmButtonOkClick(confirmName, confirmId) {
	var closeAllow = true;
	var callback = window['macConfirm' + confirmId + 'OKC'];
	if (callback) {
		closeAllow = callback();
	}
	if (closeAllow !== false) {
		jQuery.closePopupLayer(confirmName);
	}
}

function confirmButtonCancelClick(confirmName, confirmId) {
	var closeAllow = true;
	var callback = window['macConfirm' + confirmId + 'CancelC'];
	if (callback) {
		closeAllow = callback();
	}
	if (closeAllow !== false) {
		jQuery.closePopupLayer(confirmName);
	}
}

function saveMenuSettings() {
	var keys = ['dockItems'],
		container = null;
	for (var i = 0, cnt = keys.length; i < cnt; i++) {
		container = $('div[umi-key="' + keys[i] + '"]');
		var modules = {};
		$('a', $(container)).each(function() {
			var val = $(this).attr('umi-module');
			modules[val] = val;
		});
		modules = Object.keys(modules).map(function(k) {
			return modules[k]
		});
		SettingsStore.getInstance().set(keys[i], modules.join(";"));
	}
	basicSly.reload();
	checkSlideButtons();
}

function changeSkin(theme) {
	setCookie('skin_sel', theme);
	window.location.reload();
}

/** инициализация слайдера для дашборда модулей */
function initBasicModulesSlider() {
	var height = window.innerHeight,
		container = $('#basic-modules-scroll-container');

	if (container.length === 0) {
		return;
	}

	container.height(height * 0.8);
	window.basicSly = new Sly($('#basic-modules-scroll-container'), {
		speed: 500,
		easing: 'easeOutExpo',
		activatePageOn: 'click',
		scrollBy: 100,
		dynamicHandle: 1
	}).init();

	$(window).resize(function() {
		var height = window.innerHeight,
			container = $('#basic-modules-scroll-container');
		height = height - 140 >= 150 ? height - 140 : 150;
		container.height(height);
		basicSly.reload();
	});

	$('#basic-modules-up').on('mouseover', function() {
		basicSly.moveBy(500);
	});
	$('#basic-modules-down').on('mouseover', function() {
		basicSly.moveBy(-500);
	});

	$('.basic-modules-btn').on('mouseout', function() {
		basicSly.stop();
	});
	checkSlideButtons();
}

function checkSlideButtons() {
	var rel = basicSly.rel;
	if (rel.frameSize < rel.slideeSize) {
		$('.basic-modules-btn').removeClass('hidden');
	} else {
		$('.basic-modules-btn').addClass('hidden');
	}
}

/**
 * Инициализирует кнопки для добавления страниц/объектов с возможностью выбора типа и учетом текущего домена.
 * @param {Integer} domainId идентификатор текущего языка.
 */
function initCreateTypeSelector(domainId) {
	jQuery("div.imgButtonWrapper a:not(.type_select)").filter(function() {
		return jQuery(this).attr("umi:type");
	}).each(function() {
		var e = jQuery(this);
		if (e.attr("umi:prevent-default") == "true") {
			var f = function() {
				return false;
			};
			e.bind({click: f, mousedown: f, mouseup: f, mouseover: f});
		}

		domainId = domainId || getCookie('control-domain-id');
		jQuery.get("/utype/child/" + e.attr("umi:type") + '/' + domainId, {}, function(response) {
			var types = jQuery("type", response);
			if (types.length > 0) {
				var listSelector = "<ul xmlns:umi=\"http://www.umi-cms.ru/TR/umi\" class=\"type_select\"></ul>";
				var newList = jQuery(listSelector);
				var existList = jQuery('ul.type_select');

				if (existList.length > 0) {
					existList.remove();
				}

				newList.css({display: "none", position: "absolute", "z-index": 10050000});
				types.each(function() {
					var type = jQuery(this);
					jQuery(newList).append("<li><a href='#' umi:type-id='" + type.attr("id") + "' title='" + type.attr("title") + "'>" + type.attr("title") + "</a></li>");
				});
				newList.appendTo("body");
				e.add(newList).bind({
					mouseover: function() {
						Control.enabled = false;
						var offset = e.offset();
						newList.css({
							display: "block",
							top: offset.top + e.height() - 2,
							left: offset.left,
							width: e.innerWidth()
						});
						e.addClass("type_select_active");
						jQuery("a", newList).each(function() {
							var basehref = e.attr("href");
							var a = jQuery(this);
							var li = a.parent();
							var width = parseInt(li.innerWidth()) - parseInt(li.css("padding-left")) - parseInt(li.css("padding-right"));
							a.attr("href", basehref + (basehref.indexOf("?") >= 0 ? "&" : "?") + "type-id=" + a.attr("umi:type-id"));
							a.css("width", width);
						});
					},
					mouseout: function() {
						newList.css("display", "none");
						e.removeClass("type_select_active");
						Control.enabled = true;
					}
				});
			}
		});

	});
}

/**
 * Функция получающая строку шаблона из html тега template
 * @returns {Function}
 */
templateGetter = function (selector) {

	function htmlDecode(settingsInput) {
		var e = document.createElement('div');
		e.innerHTML = settingsInput;
		return e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue;
	}

	return htmlDecode($(selector)[0].innerHTML);
};

/**
 * Инициализация слайдера, для навигации по группам страницы, при их большом количестве
 */
var sliderForNavigation = function() {
	var x = 0;
	var sliderTimer;
	var quantity = $('.tabs.editing').children('.section').length;
	var widthBlocks = $('.container_tabs .tabs.editing').width();
	var widthContainer = $('.container_tabs').width();
	var width = widthBlocks - widthContainer;

	if (widthBlocks > widthContainer) {
		$('.slider_container .left_button').addClass('active');
		$('.slider_container .right_button').addClass('active');
	}

	if (quantity == 0) {
		$('.slider_container').css({
			'display': 'none'
		});
	}

	$('.slider_container .right_button').hover(function() {
		sliderTimer = setInterval(nextSlide, 2);
	}, function() {
		clearInterval(sliderTimer);
	});

	$('.slider_container .left_button').hover(function() {
		sliderTimer = setInterval(prevSlide, 2);
	}, function() {
		clearInterval(sliderTimer);
	});

	function nextSlide() {
		if (x > -width) {
			$('.container_tabs .tabs.editing').css({left: x});
			x--;
		} else {
			clearInterval(sliderTimer);
		}
	}

	function prevSlide() {
		if (x < 0) {
			$('.container_tabs .tabs.editing').css({left: x});
			x++;
		} else {
			clearInterval(sliderTimer);
		}
	}
};
$(document).ready(function() {
	var widthBlocks = $('.container_tabs .tabs.editing').width();
	var widthContainer = $('.container_tabs').width();
	$('.icon-action.extended_fields_expander').click(function() {
		if (widthBlocks > widthContainer) {
			$('.slider_container .left_button').addClass('active');
			$('.slider_container .right_button').addClass('active');
		} else {
			$('.slider_container .left_button').removeClass('active');
			$('.slider_container .right_button').removeClass('active');
		}
	});
});
