/** Модуль визуального редактора на сайте. */
uAdmin('.wysiwyg', function (extend) {

	/**
	 * @constructor Конструктор модуля визуального редактора.
	 * Для корректной работы ожидает, что в него была передана опция `type`
	 * из клиентского кода, @example:
	 *   uAdmin('type', 'tinymce47', 'wysiwyg');
	 *
	 * После загрузки модуль становится доступен как выражение `uAdmin.wysiwyg`
	 */
	function WYSIWYG() {
		this.settings = jQuery.extend(this[this.type].settings, this.settings);
		this[this.type]();
		this.init = this[this.type].init;
	}

	/**
	 * Заглушка функции инициализации визуальных редакторов на странице.
	 * Переопределяется в конструкторе модуля WYSIWYG на функцию того типа tinyMCE,
	 * который используется на данной странице (@example WYSIWYG.prototype.tinymce47.init).
	 * @returns {boolean}
	 */
	WYSIWYG.prototype.init = function() {
		return false;
	};

	/**
	 * Заглушка настроек tinyMCE.
	 * Переопределяется в конструкторе модуля WYSIWYG на объект настроек того типа tinyMCE,
	 * который используется на данной странице (@example WYSIWYG.prototype.tinymce47.settings).
	 * @returns {boolean}
	 */
	WYSIWYG.prototype.settings = function() {
		return false;
	};

	/**
	 * Функция подключения Wysiwyg-редактора TinyMCE 4.7,
	 * вызывается в конструкторе модуля WYSIWYG.
	 */
	WYSIWYG.prototype.tinymce47 = function() {
		window.tinyMCEPreInit = {
			suffix : '.min',
			base : '/js/cms/wysiwyg/tinymce47'
		};

		if (!window.tinymce) {
			$('<script src="/js/cms/wysiwyg/tinymce47/tinymce.min.js" charset="utf-8"></script>')
				.appendTo('head');
		}

		if (!window.mceCustomSettings) {
			$('<script src="/js/cms/wysiwyg/tinymce47/tinymce_custom.js" charset="utf-8"></script>')
				.appendTo('head');
		}
	};

	/**
	 * Инициализирует wysiwyg-редакторы на странице
	 * @param {Object} options дополнительные опции для tinyMCE
	 * @returns {{}}
	 */
	WYSIWYG.prototype.tinymce47.init = function(options) {
		if (!tinyMCE) {
			throw 'tinyMCE is not defined';
		}

		options = options || {};
		var editor = {};
		var selector = 'textarea.wysiwyg';

		if (uAdmin.eip && uAdmin.eip.editor) {
			editor = {
				id : 'mceEditor-' + new Date().getTime(),
				destroy : function() {
					tinymce && tinymce.activeEditor && tinymce.activeEditor.destroy();
				}
			};

			selector = '#' + editor.id;
			options.id = editor.id;
			options.inline = true;

			tinymce.on("AddEditor", function(event) {
				event.editor.on('init', function() {
					this.fire("focus");
				});
			});
		}

		var settings = {
			language: uAdmin.data['interface-lang'] || uAdmin.data['lang']
		};

		$.extend(
			settings,
			this.settings,
			window.mceCustomSettings,
			options.settings || {}
		);

		tinyMCE.init(settings);

		if (typeof options.selector === 'string') {
			selector = options.selector;
		}

		jQuery(selector).each(function (i, n) {
			tinyMCE.execCommand('mceToggleEditor', false, n.id);
		});

		return editor;
	};

	/**
	 * Настройки по умолчанию для tinyMCE
	 * @type {Object}
	 */
	WYSIWYG.prototype.tinymce47.settings = {
		// Убирает надпись "Powered by TinyMCE"
		branding: false,

		// Тема редактора
		theme : "modern",

		// Скин редактора
		skin : 'lightgray',

		// По умолчанию вставлять текст как простой текст (без форматирования)
		paste_as_text: true,

		// Расширение разрешенных html-элементов
		extended_valid_elements : "script[src|*],style[*],map[*],area[*],umi:*[*],input[*],noindex[*],nofollow[*],iframe[frameborder|src|width|height|name|align],div[*],span[*],a[*],-p[*]",

		// Используемые плагины
		plugins: [
			"anchor",
			"advlist",
			"charmap",
			// "code",
			"codemirror",
			"contextmenu",
			// "directionality",
			// "emoticons",
			"fullscreen",
			// "hr",
			"image",
			// "insertdatetime",
			"link",
			"lists",
			"media",
			// "nonbreaking",
			// "noneditable",
			// "pagebreak",
			"paste",
			// "preview",
			// "print",
			// "save",
			"searchreplace",
			// "spellchecker",
			"table",
			// "template",
			"textcolor",
			"visualchars"
		],

		// Расширенные настройки для изображений
		image_advtab: true,

		// Подпись для изображений
		image_caption: true,

		// Тулбар
		toolbar: 'paste pastetext removeformat link unlink anchor image media table charmap code blockquote ' +
		'formatselect fontselect fontsizeselect bold italic strikethrough underline alignleft aligncenter alignright ' +
		'alignjustify bullist numlist outdent indent forecolor backcolor subscript superscript',

		// Убирает меню
		menubar: false,

		// Разрешает изменять вертикальный размер редактора (не меньше 300px)
		resize: true,
		min_height: 300,

		// Настройки плагина codemirror (html-редактор вместо стандартного плагина `code`)
		// @link https://github.com/christiaan/tinymce-codemirror
		codemirror: {
			// Форматирует текст сразу при открытии редактора
			indentOnInit: true,

			// название директории с библиотекой codemirror (./plugins/codemirror/codemirror)
			// @link https://github.com/codemirror/CodeMirror
			path: 'codemirror',

			// Ширина редактора
			width: 1000,

			// Высота редактора
			height: 500,

			// Настройки для библиотеки codemirror
			// @link http://codemirror.net/doc/manual.html
			config: {
				// Включает нумерацию строк
				lineNumbers: true,

				// Включает перенос длинных строк
				lineWrapping: true,

				// Включает автоматическую фокусировку редактора при открытии
				autofocus: true,
			}
		},

		// Никак специально не преобразовывает ссылки
		convert_urls : false,

		// Превращает все ссылки в абсолютные
		relative_urls : false,

		// Функция обратного вызова для открытия файлового менеджера изнутри tinyMCE
		file_browser_callback: function() {
			uAdmin.wysiwyg.umiFileBrowserCallback.apply(uAdmin.wysiwyg, arguments);
		},

		// Исправляет вставку ссылки
		urlconverter_callback: function(url) {
			var umiPageLink = url.match(/^%content%20get_page_url\((\d+)\)%$/);
			return (umiPageLink === null) ? url : '%content get_page_url(' + umiPageLink[1] + ')%';
		},
	};

	/**
	 * Функция обратного вызова для открытия файлового менеджера изнутри tinyMCE
	 * @param {String} fieldName ID поля
	 * @param {String} url текстовое содержимое поля
	 * @param {String} type тип поля
	 * @param {Window} win
	 * @returns {boolean}
	 */
	WYSIWYG.prototype.umiFileBrowserCallback = function(fieldName, url, type, win) {
		switch (type) {
			case "file"  :
				return uAdmin.wysiwyg.umiTreeLink(fieldName, url, type, win);

			case "image" :
			case "media" :
				var input = win.document.getElementById(fieldName);
				if (!input) {
					return false;
				}

				var folder = '';
				var file = '';
				if (input.value.length) {
					folder = input.value.substr(0, input.value.lastIndexOf('/'));
					file = input.value;
				}

				jQuery.ajax({
					url: "/admin/data/get_filemanager_info/",
					data: "folder=" + folder + '&file=' + file,
					dataType: 'json',
					complete: function(data) {
						data = jQuery.parseJSON(data.responseText);
						var folderHash = data.folder_hash;
						var fileHash = data.file_hash;
						var lang = data.lang;

						var fileManager = data.filemanager;
						if (fileManager === 'elfinder') {
							return uAdmin.wysiwyg.umielfinderFileManager(fieldName, url, type, win, lang, folderHash, fileHash);
						}

						// вызов deprecated файлового менеджера на flash (или другого кастомного файлового менеджера)
						// @see WYSIWYG.prototype.umiflashFileManager
						var functionName = 'uAdmin.wysiwyg.umi' + fileManager + 'FileManager';
						eval(functionName + '(fieldName, url, type, win, lang, folderHash, fileHash)');
					}
				});
				break;

			default:
				throw 'Invalid type';
		}

		return false;
	};

	/**
	 * Файловый менеджер elFinder
	 * @param {String} fieldName ID поля
	 * @param {String} url текстовое содержимое поля
	 * @param {String} type тип поля
	 * @param {Window} win
	 * @param {String} lang строковой идентификатор языковой версии сайта
	 * @param {String} folderHash хеш пути до директории
	 * @param {String} fileHash хеш пути до файла
	 * @returns {boolean}
	 */
	WYSIWYG.prototype.umielfinderFileManager = function(fieldName, url, type, win, lang, folderHash, fileHash) {
		var query = [];
		query.push("id=" + fieldName);

		switch (type) {
			case "image" :
				query.push("image=1");
				break;
			case "media" :
				query.push("media=1");
				break;
		}

		query.push("folder_hash=" + folderHash);
		query.push("file_hash=" + fileHash);
		query.push("lang=" + lang);

		$.openPopupLayer(jQuery.extend({
			name: 'Filemanager',
			title: getLabel('js-file-manager'),
			width: 1200,
			height: 600,
			url: "/styles/common/other/elfinder/umifilebrowser.html?" + query.join("&")
		}, uAdmin.wysiwyg.getOpenPopupLayerExtParams(fieldName, win)));

		var selector = '#popupLayer_Filemanager .popupBody',
			footerHtml = '<div id="watermark_wrapper"><label for="add_watermark">' + getLabel('js-water-mark') + '</label><input type="checkbox" name="add_watermark" id="add_watermark"></div>';

		if (tinymce && tinymce.activeEditor && tinymce.activeEditor.settings.inline) {
			jQuery(selector).append(footerHtml);
		} else {
			window.parent.jQuery(selector).append(footerHtml);
		}

		return false;
	};

	/**
	 * Возвращает дополнительные параметры открытия всплывающего окна
	 * @param {String} fieldName ID поля
	 * @param {Window} win
	 * @returns {object}
	 */
	WYSIWYG.prototype.getOpenPopupLayerExtParams = function (fieldName, win) {
		return {};
	};

	/**
	 * Обработчик действия "Вставить ссылку" в визуальном редакторе
	 * @see WYSIWYG.prototype.umiFileBrowserCallback
	 * @param {String} fieldName ID поля
	 * @param {String} url текстовое содержимое поля
	 * @param {String} type тип поля
	 * @param {Window} win
	 * @returns {boolean}
	 */
	WYSIWYG.prototype.umiTreeLink = function (fieldName, url, type, win) {
		var domainFloated;
		var domainFloatedId;
		var langId;

		if (window.pageData) {
			domainFloated = window.pageData.domain;
			domainFloatedId = window.pageData.domain_id;
			langId = window.pageData.lang_id;
		} else if (uAdmin && uAdmin.data) {
			domainFloated = uAdmin.data['domain-floated'];
			domainFloatedId = uAdmin.data['domain-id'];
			langId = uAdmin.data['lang-id'];
		} else {
			throw 'Page data is not defined';
		}

		var query = "?domain=" + domainFloated + "&domain_id=" + domainFloatedId + "&lang_id=" + langId;
		var treeLinkUrl = '/js/cms/wysiwyg/tinymce47/skins/lightgray/treelink/treelink.html' + query;
		var pageHeight = 320;

		// Поддержка @deprecated версии tinyMCE 3
		if (tinyMCE.majorVersion == 3) {
			treeLinkUrl = "/js/cms/wysiwyg/tinymce/jscripts/tiny_mce/themes/umi/treelink.html" + query;
			pageHeight = 308;
		}

		tinyMCE.activeEditor.windowManager.open({
			url: treeLinkUrl,
			title: getLabel('js-choose-page'),
			width: 525,
			height: pageHeight,
			inline: true,
			scrollbars: false,
			resizable: false,
			maximizable: false,
			close_previous: false
		}, {
			window: win,
			input: fieldName,
			editor_id: tinyMCE.activeEditor.id
		});

		return false;
	};

	/**
	 * Возвращает html-код нижней панели файлового менеджера
	 * TODO убрать html-код в отдельный шаблон
	 * @param {String} filemanager название файлового менеджера
	 * @returns {String}
	 */
	WYSIWYG.prototype.getFilemanagerFooter = function(filemanager) {
		var footer = "";

		if (filemanager === 'elfinder') {
			footer = '<div id="watermark_wrapper" class="ui-widget-header">';
			footer += '<label for="remember_last_folder">';
			footer += getLabel('js-remember-last-dir');
			footer += '</label><input type="checkbox" name="remember_last_folder" id="remember_last_folder"';
			if (jQuery.cookie('remember_last_folder')) {
				footer += 'checked="checked"';
			}
			footer += '/></div>';
		}

		return footer;
	};

	defineDeprecatedWysiwygFunctions(WYSIWYG);
	return extend(WYSIWYG, this);
});

/** Обработчик события загрузки модуля визуального редактора */
uAdmin.onLoad('wysiwyg', function() {
	uAdmin.wysiwyg.curr_mouse_position = {
		top: 0,
		left: 0
	};

	$('body').click(function(e) {
		uAdmin.wysiwyg.curr_mouse_position = {
			top : e.pageY - window.pageYOffset,
			left : e.pageX - window.pageXOffset
		};
	});
});

/**
 * Добавляет в объект WYSIWYG deprecated-функции
 * для работы с устаревшими версиями визуальных редакторов. Это необходимо,
 * потому что у клиентов могут все еще работать старые версии,
 * в том числе с кастомными плагинами, то есть этот функционал нельзя удалять.
 *
 * Примеры старых версий:
 *   js/cms/wysiwyg/inline
 *   js/cms/wysiwyg/tinymce
 *   js/cms/wysiwyg/tinymce4
 *
 * @param WYSIWYG
 */
function defineDeprecatedWysiwygFunctions(WYSIWYG) {
	/** @deprecated */
	WYSIWYG.prototype.inline = function() {
		jQuery('<script src="/js/cms/wysiwyg/inline/inlineWYSIWYG.js" type="text/javascript" charset="utf-8"></script>').appendTo('head');
	};

	/** @deprecated */
	WYSIWYG.prototype.inline.init = function(node) {
		return new inlineWYSIWYG(node);
	};

	/*
	* Return tinymce on destroy holder html content
	* @returns string
	*/
	WYSIWYG.prototype.getTinymceUmiruDestroyHolderContent = function(newNode) {
		var frame = jQuery('iframe', newNode)[0];
		return frame.contentDocument.body.innerHTML;
	};

	/* Return reposition toolbar width */
	WYSIWYG.prototype.getRepositionToolbarWidth = function() {
		return 1025;
	};

	/**
	 * @deprecated
	 * tinyMCE 3, который раньше использовался в административной панели.
	 * Этот код еще может использоваться у клиентов.
	 */
	WYSIWYG.prototype.tinymce = function() {
		window.tinyMCEPreInit = {
			suffix : '',
			base : '/js/cms/wysiwyg/tinymce/jscripts/tiny_mce'
		};

		jQuery('<script src="/js/cms/wysiwyg/tinymce/jscripts/tiny_mce/tiny_mce.js" type="text/javascript" charset="utf-8"></script>').appendTo('head');
		/* adding custom settings */
		jQuery('<script src="/js/cms/wysiwyg/tinymce/jscripts/tiny_mce/tinymce_custom.js" type="text/javascript" charset="utf-8"></script>').appendTo('head');
	};

	/** @deprecated */
	WYSIWYG.prototype.tinymce.init = function(options) {
		var editor = {}, selector = "textarea.wysiwyg", settings = {};
		if (uAdmin.eip && uAdmin.eip.editor) {
			uAdmin.eip.onTinymceInitEditorTune.call(this, settings);
			editor = {
				id : 'mceEditor-' + new Date().getTime(),
				destroy : function() {
					tinyMCE.execCommand('mceToggleEditor', false, editor.id);
				}
			};
			options.id = editor.id;
			selector = '#' + editor.id;
		}

		settings.language = uAdmin.data["interface-lang"] || uAdmin.data["lang"];
		settings = jQuery.extend(this.settings, settings);
		/* custom settings */

		settings = jQuery.extend(settings, window.mceCustomSettings);
		var customSettings = options ? (options.settings || {}) : {};
		settings = jQuery.extend(settings, customSettings);
		tinyMCE.init(settings);

		if (options && typeof options.selector === 'string') {
			selector = options.selector;
		}

		jQuery(selector).each(function (i, n) {
			tinyMCE.execCommand('mceToggleEditor', false, n.id);
		});

		return editor;
	};

	/** @deprecated */
	WYSIWYG.prototype.tinymce.settings = {
		// General options
		mode : "none",
		theme : "umi",
		width : "100%",
		language : typeof window.interfaceLang == 'string' ? interfaceLang : 'ru',
		plugins : "safari,spellchecker,pagebreak,style,layer,table,save,"
		+"advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,"
		+"preview,media,searchreplace,print,contextmenu,paste,directionality,"
		+"fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

		inlinepopups_skin : 'butterfly',

		toolbar_standart : "fontsettings,tablesettings,|,"
		+"cut,copy,paste,|,pastetext,pasteword,|,selectall,cleanup,|,"
		+ "undo,redo,|,link,unlink,anchor,image,media,|,charmap,code",

		toolbar_tables : "table,delete_table,|,col_after,col_before,"
		+"row_after,row_before,|,delete_col,delete_row,|,"
		+"split_cells,merge_cells,|,row_props,cell_props",

		toolbar_fonts: "formatselect,fontselect,fontsizeselect,|,"
		+ "bold,italic,underline,|,"
		+ "justifyleft,justifycenter,justifyright,justifyfull,|,"
		+ "bullist,numlist,outdent,indent,|,"
		+ "forecolor,backcolor,|,sub,sup",

		theme_umi_toolbar_location : "top",
		theme_umi_toolbar_align : "left",
		theme_umi_statusbar_location : "bottom",
		theme_umi_resize_horizontal : false,
		theme_umi_resizing : true,

		convert_urls : false,
		relative_urls : false,

		file_browser_callback : function(field_name, url, type, win) {
			if (type == 'file') {
				var sTreeLinkUrl = "/js/cms/wysiwyg/tinymce/jscripts/tiny_mce/themes/umi/treelink.html" + (window.lang_id ? "?lang_id=" + window.lang_id : '');
				tinyMCE.activeEditor.windowManager.open({
					url    : sTreeLinkUrl,
					width  : 525,
					height : 308,
					inline         : true,
					scrollbars	   : false,
					resizable      : false,
					maximizable    : false,
					close_previous : false
				}, {
					window    : win,
					input     : field_name,
					editor_id : tinyMCE.selectedInstance.editorId
				});
				return false;
			}
			else {
				var input = win.document.getElementById(field_name),
					params = {}, qs = [];
				if (!input) return false;
				if (input.value.length) {
					params.folder = input.value.substr(0, input.value.lastIndexOf('/'));
					params.file = input.value;
				}
				qs.push("id=" + field_name);
				switch(type) {
					case "image" : qs.push("image=1"); break;
					case "media" : qs.push("media=1"); break;
				}
				jQuery.ajax({
					url: "/admin/data/get_filemanager_info/",
					data: params,
					dataType: 'json',
					success: function(data){
						if (data.filemanager == 'flash') {
							if (input.value.length) {
								qs.push("folder=." + params.folder);
								qs.push("file=" + input.value);
							}
						}
						else {
							qs.push("folder_hash=" + data.folder_hash);
							qs.push("file_hash=" + data.file_hash);
							qs.push("lang=" + data.lang);
						}

						var fm = {
							flash :  {
								height : 460,
								url    : "/styles/common/other/filebrowser/umifilebrowser.html?" + qs.join("&")
							},
							elfinder : {
								height : 600,
								url    : "/styles/common/other/elfinder/umifilebrowser.html?" + qs.join("&")
							}
						};

						jQuery.openPopupLayer({
							name   : "Filemanager",
							title  : getLabel('js-file-manager'),
							width  : 1200,
							height : fm[data.filemanager].height,
							url    : fm[data.filemanager].url
						});

						if (data.filemanager == 'elfinder') {
							var footer = '<div id="watermark_wrapper"><label for="add_watermark">';
							footer += getLabel('js-water-mark');
							footer += '</label><input type="checkbox" name="add_watermark" id="add_watermark"/>';
							footer += '<label for="remember_last_folder">';
							footer += getLabel('js-remember-last-dir');
							footer += '</label><input type="checkbox" name="remember_last_folder" id="remember_last_folder"'
							if (getCookie('remember_last_folder', true) > 0) {
								footer += 'checked="checked"';
							}
							footer +='/></div>';

							window.parent.jQuery('#popupLayer_Filemanager .popupBody').append(footer);
						}
						return false;
					}
				});
			}
			return false;
		},// Callbacks

		extended_valid_elements : "script[type=text/javascript|src|languge|lang],map[*],area[*],umi:*[*],input[*],noindex[*],nofollow[*],iframe[frameborder|src|width|height|name|align]", // extend tags and atributes

		content_css : "/css/cms/style.css" // enable custom CSS
	};

	/**
	 * @deprecated
	 * tinyMCE 3, который раньше использовался в EIP.
	 * Этот код еще может использоваться у клиентов.
	 */
	/** @deprecated */
	WYSIWYG.prototype.tinymce_umiru = function() {
		window.tinyMCEPreInit = {
			suffix : '_src',
			base : '/js/cms/wysiwyg/tinymce/jscripts/tiny_mce'
		};

		if (!window.tinymce) {
			jQuery('<script src="/js/cms/wysiwyg/tinymce/jscripts/tiny_mce/tiny_mce_src.js" type="text/javascript" charset="utf-8"></script>').appendTo('head');
		}

		/* adding custom settings */
		jQuery('<script src="/js/cms/wysiwyg/tinymce/jscripts/tiny_mce/tinymce_src_custom.js" type="text/javascript" charset="utf-8"></script>').appendTo('head');
	};

	/** @deprecated */
	WYSIWYG.prototype.tinymce_umiru.init = function(node) {
		var editor, selector = "textarea.wysiwyg", settings = {};
		if (uAdmin.eip && uAdmin.eip.editor) {
			uAdmin.eip.onTinymceInitEditorTune.call(this, settings);
			editor = {
				id : 'mceEditor-' + new Date().getTime(),
				destroy : function() {
					var oldNode = jQuery('#' + editor.id),
						newNode = jQuery('#' + editor.id + '_parent'),
						content;

					content = uAdmin.wysiwyg.getTinymceUmiruDestroyHolderContent(newNode);
					oldNode.html(content);
					newNode.remove();
					oldNode.css('display','');
					oldNode[0].id = '';
				}
			};
			node.id = editor.id;
			selector = '#' + editor.id;
		}

		settings.language = uAdmin.data["interface-lang"] || uAdmin.data["lang"];
		settings = jQuery.extend(this.settings, settings);

		if (window.mceUmiRUCustomSettings) {
			settings = jQuery.extend(settings, window.mceUmiRUCustomSettings);
		}

		tinyMCE.init(settings);

		jQuery(selector).each(function (i, n) {
			tinyMCE.execCommand('mceAddControl', false, n.id);
		});
		return editor;
	};

	/** @deprecated */
	WYSIWYG.prototype.tinymce_umiru.settings = {

		// General options
		mode : "none",
		theme : "umiru",
		language : typeof window.interfaceLang == 'string' ? interfaceLang : 'ru',
		width : "100%",
		suffix : "_src",

		body_class : "text",

		theme_umi_resizing_use_cookie : false,
		init_instance_callback : "uAdmin.wysiwyg.initInstance", //trigger event on editor instance creation
		theme_umi_path : false, //dispable path control
		//constrain_menus : true,
		constrain_menus : false,
		extended_valid_elements : "script[src|*],style[*],map[*],area[*],umi:*[*],input[*],noindex[*],nofollow[*],iframe[frameborder|src|width|height|name|align],div[*],span[*],a[*]", // extend tags and atributes
		plugins : "safari,spellchecker,pagebreak,style,layer,table,save,advhr,umiimage,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

		inlinepopups_skin : 'butterfly',

		setup : function(ed) {
			function resize (ed, l) {
				jQuery(ed.getContainer()).children('table.mceLayout').eq(0).css('height', 'auto');
				//select iframe element
				var i = jQuery(ed.getContentAreaContainer()).children('iframe')[0];
				//select body of iframe
				var h = i.contentWindow.document.body;
				//.parent() doesn't work in IE properly
				iHeight = Math.max(jQuery(h).parent().outerHeight(), jQuery(h).outerHeight())
				//set iframe heigth to height of html inside
				i.style.height = iHeight + 'px';

				$('img', ed.getBody()).on('load', function() {
					resize(ed, l);
				});
			}

			ed.onChange.add(resize);
			ed.onKeyDown.add(resize);
			ed.onLoadContent.add(function(ed, o) {
				if(o.content == "&nbsp;" || o.content == " ") {
					ed.setContent("");
				}
				ed.focus();

				/** Поиск с возвращением: поиск первой ноды с текстом */
				function backTrackTextnode(node) {
					if(node.nodeType == 3) return node;

					var subnodes = $(node).contents();

					for(var bkt = 0; bkt < subnodes.length; bkt++) {
						var result = backTrackTextnode(subnodes[bkt]);
						if(result) return result;
					}

					return false;
				}

				var nodes_all = ed.dom.select('body');
				var node = backTrackTextnode(nodes_all[0]);
				if(!node) {
					if(ed.dom.select('body *').length > 0) {
						node = ed.dom.select('body *')[0];
					} else {
						node = nodes_all[0];
					}
				} else {
					node = node.parentNode;
				}

				var rng = ed.selection.getRng();
				if(!rng || typeof rng.selectNode == "undefined") return;
				var tn = ed.getDoc().createTextNode(".");
				node.insertBefore(tn, node.firstChild);


				rng.selectNode(tn);
				rng.setStartBefore(tn);
				rng.setStartAfter(tn);

				ed.selection.setRng(rng);

				node.removeChild(tn);

				//Передвигаем панельку с кнопками туда, куда кликнули
				var panel = $('#' + ed.editorContainer + ' .toolbarHolder');
				var panelWidth = 1110;
				var bodyWidth = $('body').width();
				panel.css('position', 'fixed');
				panel.css('top', 40);
				if(bodyWidth > panelWidth) {
					panel.css('left', (bodyWidth - panelWidth)/2);
				}else{
					panel.css('left', (bodyWidth - 800)/2);
				}
			});
		},


		toolbar_standart : "umiimage,tablesettings,|,"
		+ "pastetext,pasteword,|,cleanup,|,"
		+ "link,unlink,|,"
		+ "charmap,code",

		toolbar_tables : "table,delete_table,|,col_after,col_before,row_after,row_before,|,delete_col,delete_row,|,split_cells,merge_cells,|,row_props,cell_props",

		toolbar_fonts: "formatselect,fontselect,fontsizeselect,|,"
		+ "bold,italic,underline,|,"
		+ "justifyleft,justifycenter,justifyright,justifyfull,|,"
		+ "bullist,numlist,outdent,indent,|,"
		+ "forecolor,backcolor,|,"
		+ "sub,sup",


		theme_umi_toolbar_location : "top",
		theme_umi_toolbar_align : "left",
		theme_umi_statusbar_location : "bottom",
		theme_umi_resize_horizontal : false,
		theme_umi_resizing : false,

		convert_urls : false,
		relative_urls : false,

		// Example content CSS (should be your site CSS)
		//content_css : "css/example.css",

		// Callbacks
		file_browser_callback : "uAdmin.wysiwyg.umiFileBrowserCallback",


		// Drop lists for link/image/media/template dialogs
		template_external_list_url : "js/template_list.js",
		external_link_list_url : '',
		external_image_list_url : '',
		media_external_list_url : ''
	};

	/**
	 * @deprecated
	 * tinyMCE 4, который раньше использовался в EIP.
	 * Этот код еще может использоваться у клиентов.
	 */
	WYSIWYG.prototype.tinymce4 = function() {
		window.tinyMCEPreInit = {
			suffix : '.min',
			base : '/js/cms/wysiwyg/tinymce4'
		};

		/* adding custom settings */
		jQuery('<script src="/js/cms/wysiwyg/tinymce4/tinymce_custom.js" type="text/javascript" charset="utf-8"></script>').appendTo('head');

		if (window.customLinkTinymce4) {
			window.customLinkTinymce4();
		} else if (!window.tinymce) {
			jQuery(
				'<script src="/js/cms/wysiwyg/tinymce4/tinymce.min.js" type="text/javascript" charset="utf-8"></script>'
			).appendTo('head');
		}

		var jqToolbarHolder = jQuery('<div/>')
			.addClass('toolbarHolder')
			.css({
				position: 'fixed',
				top: '40px',
				display: 'none'
			})
			.appendTo("body");

		var repositionToolbarHolder = function (editor) {
			if (!editor || editor instanceof tinymce.Editor === false) editor = tinymce && tinymce.activeEditor;
			if (!editor) return false;
			var iDocWidth = jQuery(document).width(),
				iPanelWidth = Math.min(iDocWidth * 0.9, uAdmin.wysiwyg.getRepositionToolbarWidth()),
				iLeft = (iDocWidth - iPanelWidth) / 2;
			var jqPanel = jQuery(editor.theme.panel.getEl());
			jqToolbarHolder.width(iPanelWidth).offset({left: iLeft}).draggable().css('cursor', 'move');
			jqPanel.find(".mce-toolbar").css('display', 'inline-block').parent().css('white-space', 'normal');
			if (jQuery.draggable) {
				jqPanel.draggable();
			}
		};

		jQuery(document).add(window).on('resize', function(oEvent){
			if (tinymce && tinymce.activeEditor) {
				repositionToolbarHolder(tinymce.activeEditor);
			}
		});

		tinymce.on('AddEditor', function(oEvent){
			oEvent.editor.on('ShowPanel', function(oEvent){
				repositionToolbarHolder(oEvent.target);
				window.setTimeout(function(){ jQuery(".toolbarHolder").show() }, 0);
			});
		});

	};

	/** @deprecated */
	WYSIWYG.prototype.tinymce4.init = function(node) {
		if (window.tinymce4InitAbort && window.tinymce4InitAbort()) {
			return null;
		}
		var editor, selector = "textarea.wysiwyg", settings = {};
		if (uAdmin.eip && uAdmin.eip.editor) {
			editor = {
				id : 'mceEditor-' + new Date().getTime(),
				destroy : function() {
					tinymce && tinymce.activeEditor && tinymce.activeEditor.destroy();
				}
			};
			node.id = editor.id;
			selector = '#' + editor.id;
			settings.fixed_toolbar_container = ".toolbarHolder";
			tinymce.on("AddEditor", function(oEvent){
				oEvent.editor
					.on('init', function(oEvent){
						this.fire("focus");
					});
			});
		}

		settings.language = uAdmin.data["interface-lang"] || uAdmin.data["lang"];
		settings = jQuery.extend(this.settings, settings);
		/* custom settings */
		if (window.mceCustomSettings) {
			settings = jQuery.extend(settings, window.mceCustomSettings);
		}

		if (window.mce4CustomSettings) {
			settings = jQuery.extend(settings, window.mce4CustomSettings);
		}

		settings.selector = selector;
		tinymce.init(settings);
		return editor;
	};

	/** @deprecated */
	WYSIWYG.prototype.tinymce4.settings = {

		// General options
		inline : true,
		theme : "modern",
		skin : 'darkgray',
		language : typeof window.interfaceLang == 'string' ? interfaceLang : 'ru',
		suffix : ".min",
		schema: "html4",
		paste_as_text: true,
		convert_urls: false,
		toolbar_items_size: 'small',

		extended_valid_elements : "script[src|*],style[*],map[*],area[*],umi:*[*],input[*],noindex[*],nofollow[*],iframe[frameborder|src|width|height|name|align],div[*],span[*],a[*]", // extend tags and atributes
		plugins : "umiimage,spellchecker,pagebreak,layer,table,save,hr,image,link,emoticons,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,template,anchor,charmap,code,textcolor",

		inlinepopups_skin : 'butterfly',

		toolbar1 : "image table | paste pastetext | removeformat | link unlink | charmap code",
		toolbar2 : "formatselect fontselect fontsizeselect",
		toolbar3 : "bold italic underline",
		toolbar4 : "alignleft aligncenter alignright alignjustify",
		toolbar5 : "bullist numlist outdent indent",
		toolbar6 : "forecolor backcolor",
		toolbar7 : "subscript superscript",

		block_formats: getLabel("js-wysiwyg-paragraph")+"=p;Address=address;Pre=pre;Header 1=h1;Header 2=h2;Header 3=h3;Header 4=h4;Header 5=h5;Header 6=h6",

		menubar: false,
		statusbar: false,
		resize: false,
		object_resizing : false,

		convert_urls : false,
		relative_urls : false,

		// Callbacks
		file_browser_callback : function(){ uAdmin.wysiwyg.umiFileBrowserCallback.apply(uAdmin.wysiwyg, arguments) },


		// Drop lists for link/image/media/template dialogs
		template_external_list_url : "js/template_list.js",
		external_link_list_url : '',
		external_image_list_url : '',
		media_external_list_url : ''

	};

	/** @deprecated */
	WYSIWYG.prototype.initInstance = function (inst) {

		//Auto add styles into iframe document body, inherited from real element
		jQuery('div.toolbarHolder').draggable();
		var el = jQuery(inst.getElement());
		var iframeBody = jQuery(inst.getDoc()).find('body').eq(0);
		var attrArray = ['font-family','font-size','font-weight','font-style','color',
			'text-transform','text-decoration','letter-spacing','word-spacing',
			'line-height','text-align','vertical-align','direction','background-color',
			'background-image','background-repeat','background-position',
			'background-attachment','opacity','top','right','bottom',
			'left','padding-top','padding-right','padding-bottom','padding-left',
			'overflow-x','overflow-y','white-space',
			'clip','list-style-image','list-style-position',
			'list-style-type','marker-offset'];
		for (var i in attrArray) {
			iframeBody.css(attrArray[i], el.css(attrArray[i]));
		}

		function getInternetExplorerVersion() {
			// Returns the version of Internet Explorer or a -1
			// (indicating the use of another browser).
			var rv = -1; // Return value assumes failure.
			if (navigator.appName == 'Microsoft Internet Explorer') {
				var ua = navigator.userAgent;
				var re  = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
				if (re.exec(ua) != null)
					rv = parseFloat( RegExp.$1 );
			}
			return rv;
		}
		var ieVersion = getInternetExplorerVersion();
		if(ieVersion > -1 && ieVersion <= 8.0) {
			iframeBody.css('background-color', '');
		}
		iframeBody.css('height', 'auto');
		var containerAttrArray = ['margin-top','margin-right','margin-bottom','margin-left'];
		jQuery('#' + inst.editorContainer).css('display', 'block');
		for (var j in containerAttrArray) {
			jQuery('#' + inst.editorContainer).css(containerAttrArray[j], el.css(containerAttrArray[j]));
		}

		//Auto adding line-height when changing size of font
		inst.formatter.register({
			fontsize : {inline : 'span', styles : {fontSize : '%value', 'line-height' : '1.3em'}}
		});

		//Remove alert when toggling "Insert as text" button
		var cookie = tinymce.util.Cookie;
		cookie.set("tinymcePasteText", "1", new Date(new Date().getFullYear() + 1, 12, 31));

	};

	/**
	 * @deprecated
	 * Устаревший файловый менеджер на flash
	 */
	WYSIWYG.prototype.umiflashFileManager = function (field_name, url, type, win, lang, folder_hash, file_hash) {

		var input = win.document.getElementById(field_name);
		if(!input) return false;
		var qs    = [];
		qs.push("id=" + field_name);
		switch(type) {
			case "image" :qs.push("image=1");break;
			case "media" :qs.push("media=1");break;
		}
		if(input.value.length) {
			var folder = input.value.substr(0, input.value.lastIndexOf('/'));
			qs.push("folder=." + folder);
			qs.push("file=" + input.value);
		}
		$.openPopupLayer({
			name   : "Filemanager",
			title  : getLabel('js-file-manager'),
			width  : 1200,
			height : 600,
			url    : "/styles/common/other/filebrowser/umifilebrowser.html?" + qs.join("&")
		});
		return false;

	};
}
