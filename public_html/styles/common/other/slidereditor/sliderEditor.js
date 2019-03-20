/**
 * Редактор слайдов.
 * Позволяет:
 *
 * 1) Получать слайды;
 * 2) Рисовать формы редактирования слайдов;
 * 3) Сохранять слайды;
 * 4) Добавлять слайды;
 * 5) Удалять слайды;
 */
sliderControl = {

	/** Конструктор */
	construct: function() {
		this.loadScripts();
		this.appendI18nConstants();
		this.loadSlides();
		this.bindCreateButton();
		this.bindNavigation();
		this.bindSaveButton();
		this.bindUploadForm();
		this.initUAdmin();
	},

	/** Загружает зависимости, необхимые для работы редактора */
	loadScripts: function() {
		var $head = $('head');
		var scriptsSources = [
			'/ulang/' + this.getRequestLanguageCodeName() + '/umiSliders/common.js',
			'/styles/common/js/utilities.js',
			'/js/cms/admin.js',
			'/js/cms/wysiwyg/wysiwyg.js'
		];

		$.each(scriptsSources, function(index, source) {
			var script = document.createElement('script');
			script.setAttribute('src', source);
			script.setAttribute('type', 'text/javascript');
			script.setAttribute('charset', 'utf-8');
			$head.append(script);
		});
	},

	/** Находит в шаблоне label'ы и вставляет в них языковые константы */
	appendI18nConstants: function() {
		var $labelList = $('label');

		$.each($labelList, function(index, label) {
			var $label = $(label);
			var key = $label.attr('data-key');
			$label.html(getLabel(key));
		});
	},

	/** Запрашивет загрузку списка слайдов */
	loadSlides: function() {
		var sliderId = this.getRequestSliderId();
		this.requestGetSlideList(sliderId);
	},

	/**
	 * Запускает формирование отображений слайдов
	 * @param {Object} slides данные слайдов
	 *
	 * {
	 * 		{
	 *			'id' => 1,
	 *			'name' => 'foo',
	 *			'slider_id' => 1,
	 *			'title' => 'bar',
	 *			'image' => '/baz.png',
	 *			'text' => '<p>foo</p>',
	 *			'link' => '/bar/baz',
	 * 		}
	 * }
	 */
	renderSlideList: function(slides) {
		var that = this;

		$.each(slides, function(index, slide) {
			that.renderSlide(slide);
		});

		if (this.getSlides().length == 0) {
			that.renderEmptySlide();
		}

		that.getActiveTab().click();
	},

	/**
	 * Формирует отображение слайда
	 * @param {Object} slideData данные слайда
	 * @see renderSlideList()
	 */
	renderSlide: function(slideData) {
		var slideView = this.createEmptySlideView();
		var $slideTab = slideView.tab;
		var $slideContent = slideView.content;

		$.each(slideData, function(field, value) {
			var $fieldInput = $slideContent.find('[data-id="' + field + '"]');

			switch (field) {
				case 'image' : {
					if (typeof value == 'string') {
						$fieldInput.attr('src', value);
						$fieldInput.toggleClass('empty', false);
					} else {
						$fieldInput.toggleClass('empty', true);
					}
					break;
				}
				default : {
					$fieldInput.val(value);
				}
			}
		});

		this.initWysiwyg($slideContent);
	},

	/** Формирует отображение слайда без данных */
	renderEmptySlide: function() {
		var slideView = this.createEmptySlideView();
		var $slideContent = $(slideView.content);
		this.initWysiwyg($slideContent);
		return slideView;
	},

	/**
	 * Привязывает переключение вкладок и удаление слайдов
	 * @param {*|jQuery|HTMLElement} $tabButton заголовок вкладки
	 */
	bindTabButton: function($tabButton) {
		var that = this;

		$tabButton.on('click', function(event) {
			var $target = $(event.target);
			var $tab = $(event.currentTarget);
			var $deleteButton = $('span.icon', $tab);
			var slideIndex = $tab.index() - 1;
			var $slidesList = that.getSlides();
			var $slide = $($slidesList[slideIndex]);

			if ($deleteButton.is($target)) {
				var slideId = $slide.find('[data-id="id"]').val();

				if (slideId) {
					that.requestDeleteSlide(slideId, function() {
						that.deleteSlideView($tab);
					});
				} else {
					that.deleteSlideView($tab);
				}

				return;
			}

			that.getTabs().toggleClass('active', false);
			$slidesList.toggleClass('active', false);
			$slide.toggleClass('active', true);
			$tab.toggleClass('active', true);
		});
	},

	/**
	 * Привязывает показ контрола "Дерево"
	 * @param {*|jQuery|HTMLElement} $treeButton кнопка вызова контрола "Дерево"
	 */
	bindTreeButton: function($treeButton) {
		var that = this;

		$treeButton.on('click', function() {
			that.showTreeControl();
		});
	},

	/**
	 * Привязывает загрузку изображений на сервер
	 * @param {*|jQuery|HTMLElement} $uploadButton кнопка вызова загрузки изображения
	 */
	bindUploadButton: function($uploadButton) {
		var that = this;

		$uploadButton.on('click', function() {
			that.getImageUploadInput().click();
		});
	},

	/**
	 * Привязывает показ файлового менеджера
	 * @param {*|jQuery|HTMLElement} $fileBrowserButton кнопка показа файлового менеджера
	 * @param {*|jQuery|HTMLElement} $imageContainer контейнер изображения (img)
	 */
	bindFileBrowserButton: function($fileBrowserButton, $imageContainer) {
		var that = this;

		$fileBrowserButton.on('click', function() {
			that.showFileBrowser($imageContainer);
		});
	},

	/** Привязывает создание слайда к соответствующей кнопке */
	bindCreateButton: function() {
		var that = this;

		that.getCreateButton().on('click', function() {
			var slideView = that.renderEmptySlide();

			that.getSlides().toggleClass('active', false);
			that.getTabs().toggleClass('active', false);

			$(slideView.content).toggleClass('active', true);
			$(slideView.tab).toggleClass('active', true);
		});
	},

	/** Привязывает перелистывание слайдов к кнопкам навигации */
	bindNavigation: function() {
		var that = this;

		that.getNextButton().on('click', function() {
			var visibleTabs = that.getVisibleTabs();

			if (visibleTabs.length > 1){
				visibleTabs.first().toggleClass('hiddenTab',true);
			}
		});

		that.getPreviousButton().on('click', function() {
			var hiddenTabs = that.getHiddenTabs();

			hiddenTabs.last().toggleClass('hiddenTab',false);
		});
	},

	/** Привязывает заполнение формы загрузки изображений к изменению контейнера для изображения */
	bindUploadForm: function() {
		var that = this;

		that.getImageUploadInput().on('change',function(event) {
			var $input = $(event.currentTarget);
			var formData = new FormData;

			formData.append('eip-ieditor-upload-fileinput[]', $input.prop('files')[0]);

			that.requestUploadImage(formData);
		})
	},

	/** Приязывает сохранение списка слайдов к соответствующей кнопке */
	bindSaveButton: function() {
		var that = this;

		that.getSaveButton().bind('click', function() {
			that.saveWysiwygState();
			var slideList = [];
			var slidesForms = that.getSlides();

			$.each(slidesForms, function(sliderIndex, slideForm) {
				var $inputList = $(slideForm).find('[data-id]');
				var slide = {};

				$.each($inputList, function(inputIndex, input) {
					var $input = $(input);
					var dataId = $input.attr('data-id');

					switch (dataId) {
						case 'image':
							slide[dataId] = ($input.hasClass('empty')) ? '' : $input.attr('src');
							break;
						case 'name':
							if (!$input.val()) {
								that.showMessage(getLabel('js-error-name-required'));
							} else {
								slide[dataId] = $input.val();
							}

							break;
						default:
							slide[dataId] = $input.val();
					}
				});

				if (typeof slide['name'] !== 'undefined') {
					slideList.push(slide);
				}
			});

			var allInputWasCorrect = slideList.length == slidesForms.length;

			if (allInputWasCorrect) {
				that.requestSaveSlideList(slideList);
			}
		});
	},

	/**
	 * Инициализирует работу системной библиотеки uAdmin.
	 * Она используется для wysiwyg редактора и автоматического добавления csrf токенов к POST запросам.
	 */
	initUAdmin: function() {
		var that = this;
		uAdmin('type', 'tinymce47', 'wysiwyg');
		uAdmin({
			'csrf': that.getCsrfToken()
		});
	},

	/**
	 * Инициализирует визуальный редактор
	 * @param {*|jQuery|HTMLElement} $slideContent контент отображения слайда
	 */
	initWysiwyg: function($slideContent) {
		if (typeof uAdmin.wysiwyg != 'object') {
			return;
		}

		var $textContainer = $slideContent.find('[data-id="text"]');

		uAdmin.wysiwyg.init({
			selector: '#' + $textContainer.attr('id')
		});
	},

	/**
	 * Запрашивает сохранение списка слайдов, в том числе новых.
	 * При успешном сохранении - запускает рисование списка слайдов.
	 * @param {Object} slideList
	 *
	 * {
	 * 		1 => {
	 * 			'id': 2
	 * 			'slider_id': 3
	 * 			'image': 'foo/bar/baz.png'
	 * 			'name': 'foo'
	 * 			'title': 'bar'
	 * 			'text': '<p>baz</p>'
	 * 			'link': '/foo/bar/baz
	 * 		}
	 * }
	 */
	requestSaveSlideList: function(slideList) {
		var that = this;

		$.ajax({
			url: that.getRequestUrlPrefix() + '/umiSliders/saveSlidesList/.json',
			type: 'POST',
			data: {
				'slide_list': slideList
			},
			dataType: 'json',
			success: function(response) {
				if (typeof response.data != 'object' || typeof response.data.errors == 'object') {
					return this.error();
				}

				var createdCount = response.data['created'] || 0;
				var updatedCount = response.data['updated'] || 0;
				var message = getLabel('js-label-slides-saved') + getLabel('js-label-created') + createdCount
				+ getLabel('js-label-updated') + updatedCount;

				that.showMessage(message);
				that.deleteSlideViewList();
				that.loadSlides();
				that.showMessage(getLabel('js-label-need-to-reset-window'));
			},
			error: function() {
				that.showMessage(getLabel('js-error-cannot-save'));
			}
		});
	},

	/**
	 * Запрашивает список слайдов заданного слайдера.
	 * При успешном запросе - запускает рисование списка слайдов.
	 * @param {Number} sliderId идентификатор слайдера
	 */
	requestGetSlideList: function(sliderId) {
		var that = this;

		$.ajax({
			url: that.getRequestUrlPrefix() + '/umiSliders/getSlidesList/.json',
			type: 'GET',
			data: {
				'slider_id': sliderId
			},
			dataType: 'json',
			success: function(response) {
				if (typeof response.data == 'undefined' || typeof response.data.error != 'undefined') {
					return this.error();
				}

				var slideList = response.data.list || {};
				that.renderSlideList(slideList);
			},
			error: function() {
				that.showMessage(getLabel('js-error-cannot-load'));
			}
		});
	},

	/**
	 * Запрашивает удаление слайда.
	 * При успешном запросе - запускает функцию обратного вызова.
	 * @param {Number} slideId идентификатор слайда
	 * @param {Function} callback функция обратного вызова
	 */
	requestDeleteSlide: function(slideId, callback) {
		var that = this;

		$.ajax({
			url: that.getRequestUrlPrefix() + '/umiSliders/deleteSlide/.json',
			type: 'POST',
			data: {
				'slide_id': slideId
			},
			dataType: 'json',
			success: function(response) {
				if (typeof response.data == 'undefined' || typeof response.data.error != 'undefined') {
					return this.error();
				}

				callback();
				that.showMessage(getLabel('js-label-delete-successful'));
				that.showMessage(getLabel('js-label-need-to-reset-window'));
			},
			error: function(response) {
				var message = getLabel('js-error-cannot-delete');

				if (response.status === 403 && response.responseJSON && response.responseJSON.data && response.responseJSON.data.error) {
					message = response.responseJSON.data.error;
				}

				that.showMessage(message);
			}
		});
	},

	/**
	 * Запрашивает сохранение загруженного изображения.
	 * При успехе - помещает загруженное изображение в контейнер изображения активного слайда.
	 * @param formData данные формы загрузки изображения.
	 */
	requestUploadImage: function(formData) {
		var that = this;

		$.ajax({
			url: that.getRequestLanguagePrefix() + '/udata/content/ieditor/upload.json',
			data: formData,
			processData: false,
			contentType: false,
			type: 'POST',
			complete: function (data) {
				if (typeof data.responseText != 'string' || data.responseText.length == 0) {
					that.showMessage(getLabel('js-error-cannot-upload'));
				} else {
					var $img = $('.imageEditor img', that.getActiveSlide());
					$img.toggleClass('empty',false);
					$img.attr('src', data.responseText);
				}
			}
		});
	},

	/**
	 * Показывает сообщение
	 * @param {String} message сообщение
	 */
	showMessage: function(message) {
		if (typeof window.parent.jQuery.jGrowl == 'undefined') {
			alert(message)
			return;
		}

		window.parent.jQuery.jGrowl(message, {
			'header': 'UMI.CMS',
			'life': 5000
		});
	},

	/** Показывает контрол "Дерево" для выбора ссылки активного слайда */
	showTreeControl: function() {
		var that = this;

		if (typeof window.parent.jQuery.openPopupLayer == 'undefined') {
			that.showMessage(getLabel('js-error-cannot-show-tree'))
			return;
		}

		window.parent.saveSlideLink = function(id) {
			$('[data-id="link"]', that.getActiveSlide()).val('%content get_page_url(' + id + ')%');
			window.parent.jQuery.closePopupLayer("tree");
		};

		window.parent.jQuery.openPopupLayer({
			name : "tree",
			title : getLabel('js-label-select-page'),
			width : 'auto',
			height : 340,
			url : "/styles/common/js/tree.html?callback=saveSlideLink"
		});
	},

	/**
	 * Показывает файловый менеджер для выбора изображения активного слайда
	 * @param {*|jQuery|HTMLElement} $imageContainer контейнер изображения
	 */
	showFileBrowser: function($imageContainer){
		var that = this;

		if (typeof window.parent.jQuery.openPopupLayer == 'undefined') {
			that.showMessage(getLabel('js-error-cannot-show-file-browser'))
			return;
		}

		var infoRequest = {};

		if (!$imageContainer.hasClass('empty')) {
			var imageSource = $imageContainer.attr('src');
			infoRequest.file = imageSource;
			infoRequest.folder = imageSource.substr(0, imageSource.lastIndexOf('/'));
		} else {
			infoRequest.folder = './images/cms/data/';
		}

		$.ajax({
			url: that.getRequestUrlPrefix() + '/data/get_filemanager_info/',
			data: infoRequest,
			dataType: 'json',
			type: 'GET',
			complete: function (data) {
				var response = eval('(' + data.responseText + ')');

				if (typeof response !== 'object') {
					return that.showMessage(getLabel('js-error-cannot-show-file-browser'));
				}

				var folderHash = (typeof response['folder_hash'] == 'string') ? response['folder_hash'] : '';
				var	fileHash = (typeof response['file_hash'] == 'string') ? response['file_hash'] : '';
				var	lang = (typeof response['lang'] == 'string') ? response['lang'] : that.getRequestLanguageCodeName();
				var fileBrowser = (typeof response['filemanager'] == 'string') ? response['filemanager'] : 'elfinder';

				var filesRequest = [];
				filesRequest.push('image=1');
				filesRequest.push('multiple=0');
				filesRequest.push('imagesOnly=1');
				filesRequest.push('noTumbs=1');
				filesRequest.push('lang=' + lang);
				filesRequest.push('folder_hash=' + folderHash);
				filesRequest.push('file_hash=' + fileHash);

				window.parent.jQuery.openPopupLayer({
					name: "Filemanager",
					title: window.parent.getLabel('js-file-manager'),
					width  : 1200,
					height : 600,
					url: "/styles/common/other/elfinder/umifilebrowser.html?" + filesRequest.join("&"),
					afterClose: function (selectedImage) {
						if (typeof selectedImage == 'object' && typeof selectedImage[0] == 'string'){
							$imageContainer.attr('src', selectedImage[0]);
							$imageContainer.toggleClass('empty', false);
						}
					},
					success: function() {
						var fileBrowserFooter = uAdmin.wysiwyg.getFilemanagerFooter(fileBrowser);
						window.parent.jQuery('#popupLayer_Filemanager').append(fileBrowserFooter);
					}
				});
			}
		});
	},

	/**
	 * Создает пустую форму отображени слайда, возвращает заголовок и контент вкладки
	 * @returns {{
	 * 		'tab': {*|jQuery|HTMLElement},
	 * 		'content': {*|jQuery|HTMLElement}
	 * }}
	 */
	createEmptySlideView: function() {
		var tabTemplate = this.getTabTemplate();
		var contentTemplate = this.getSlideTemplate();
		var firstNode = false;
		var nextTabNumber = 1;
		var tabs = this.getTabs();
		var lastTabNumber = tabs.last().find('.tab-number').text();

		if (typeof lastTabNumber == 'string' && lastTabNumber.length > 0){
			nextTabNumber = parseInt(lastTabNumber) + 1;
		} else {
			firstNode = true;
		}

		var $newTab = tabTemplate.clone().insertBefore(this.getCreateButton());
		$newTab.toggleClass('content', true);
		$newTab.toggleClass('template', false);
		$newTab.find('.tab-number').text(nextTabNumber);

		var $newContent = contentTemplate.clone().insertBefore('.slide.buttons');
		$newContent.toggleClass('content', true);
		$newContent.toggleClass('template', false);

		if (firstNode) {
			$newContent.toggleClass('active', true);
			$newTab.toggleClass('active', true);
		}

		var sliderId = this.getRequestSliderId();
		$newContent.find('[data-id="slider_id"]').val(sliderId);
		var $textContainer = $newContent.find('[data-id="text"]');
		$textContainer.attr('id', 'mceEditor-' + new Date().getTime());
		$textContainer.attr('class', 'wysiwyg');

		var $treeButton = $('img.treeButton', $newContent);
		var $uploadButton = $('div.uploadButton', $newContent);
		var $fileBrowserButton = $('div.fileBrowserButton', $newContent);
		var $imageContainer = $('div.imageEditor img', $newContent);

		this.bindTabButton($newTab);
		this.bindTreeButton($treeButton);
		this.bindUploadButton($uploadButton);
		this.bindFileBrowserButton($fileBrowserButton, $imageContainer);

		return {
			'tab': $newTab,
			'content': $newContent
		};
	},

	/**
	 * Удаляет отображение слайда
	 * @param {*|jQuery|HTMLElement} $tab заголовок вкладки слайда
	 */
	deleteSlideView: function($tab) {
		var slidesList = this.getSlides();
		var slideIndex = $tab.index() - 1;
		var $slide = $(slidesList[slideIndex]);

		var tabWasActive = $tab.hasClass('active');

		$slide.remove();
		$tab.remove();

		if (tabWasActive) {
			this.getSlides().first().toggleClass('active', true);
			this.getTabs().first().toggleClass('active', true);
		}

		if (this.getSlides().length == 0) {
			this.renderEmptySlide();
		}
	},

	/** Удаляет отображения всех слайдов */
	deleteSlideViewList: function() {
		this.removeWysiwygEditors();
		this.getSlides().remove();
		this.getTabs().remove();
	},

	/** Удаляет html редакторы */
	removeWysiwygEditors: function() {
		this.eachWysiwygEditor(
			function(editor) {
				tinymce.EditorManager.remove(editor);
			}
		);
	},

	/** Фиксирует контент html редакторов в контейнере текст слайда */
	saveWysiwygState: function() {
		this.eachWysiwygEditor(
			function(editor) {
				editor.save();
			}
		);
	},

	/**
	 * Обходит все визуальные редакторы и применяется к ним функцию обратного вызова
	 * @param {Function} callback функция обратного вызова
	 */
	eachWysiwygEditor: function(callback) {
		if (typeof tinymce == 'object' && typeof tinymce.EditorManager == 'object' && typeof callback == 'function') {
			$.each(tinymce.EditorManager.editors, function(index, editor) {
				if (typeof editor == 'object') {
					callback(editor);
				}
			});
		}
	},

	/**
	 * Возвращает csrf токен
	 * @returns {String}
	 */
	getCsrfToken: function() {
		return this.getQueryStringParamList()['token'];
	},

	/**
	 * Возвращает идентификатор слайдера
	 * @returns {Number}
	 */
	getRequestSliderId: function() {
		return this.getQueryStringParamList()['slider_id'];
	},

	/**
	 * Возвращает префикс для адрес запроса
	 * @returns {String}
	 */
	getRequestUrlPrefix: function() {
		var languagePrefix = this.getRequestLanguagePrefix();

		if (languagePrefix.length == 0) {
			return '/admin';
		}

		return languagePrefix + '/admin';
	},

	/**
	 * Возвращает языковый префикс для адреса запроса
	 * @returns {String}
	 */
	getRequestLanguagePrefix: function() {
		var prefix = this.getQueryStringParamList()['prefix'];

		if (prefix.length == 0) {
			return '';
		}

		return '/' + prefix;
	},

	/**
	 * Возвращает строковый идентификатор текущего языка
	 * @returns {String}
	 */
	getRequestLanguageCodeName: function() {
		var prefix = this.getRequestLanguagePrefix();

		if (prefix.length == 0) {
			return 'ru';
		}

		return prefix;
	},

	/**
	 * Возвращает список get параметров
	 * @returns {Array}
	 *
	 * [
	 * 		key: value
	 * ]
	 *
	 */
	getQueryStringParamList: function() {
		return window
			.location
			.search
			.replace('?','')
			.split('&')
			.reduce(
				function(paramList, query){
					var keyAndValue = query.split('=');
					paramList[decodeURIComponent(keyAndValue[0])] = decodeURIComponent(keyAndValue[1]);
					return paramList;
				},
			{}
		);
	},

	/**
	 * Возвращает видимые заголовки вкладок слайдов
	 * @returns {*|jQuery|HTMLElement}
	 */
	getVisibleTabs: function() {
		return $('.tab.content:not(.hiddenTab)');
	},

	/**
	 * Возвращает скрытые заголовки вкладок слайдов
	 * @returns {*|jQuery|HTMLElement}
	 */
	getHiddenTabs: function() {
		return $('.tab.content.hiddenTab');
	},

	/**
	 * Возвращает контент вкладок слайдов
	 * @returns {*|jQuery|HTMLElement}
	 */
	getSlides: function() {
		return $('.slide.content');
	},

	/**
	 * Возвращает контент активной вкладки слайда
	 * @returns {*|jQuery|HTMLElement}
	 */
	getActiveSlide: function() {
		return $('.slide.content.active');
	},

	/**
	 *  Возвращает заголовок активной вкладки слайда
	 * @returns {*|jQuery|HTMLElement}
	 */
	getActiveTab: function() {
		return $('.tab.content.active');
	},

	/**
	 * Возвращает заголовки вкладок слайдов
	 * @returns {*|jQuery|HTMLElement}
	 */
	getTabs: function() {
		return $('.tab.content');
	},

	/**
	 * Возвращает шаблон заголовка вкладки слайда
	 * @returns {*|jQuery|HTMLElement}
	 */
	getTabTemplate: function() {
		return $('.tab.template');
	},

	/**
	 * Возвращает шаблон контента вкладки слайда
	 * @returns {*|jQuery|HTMLElement}
	 */
	getSlideTemplate: function() {
		return $('.slide.template');
	},

	/**
	 * Возвращает кнопку сохранения списка слайдов
	 * @returns {*|jQuery|HTMLElement}
	 */
	getSaveButton: function() {
		return $('.button.save-button');
	},

	/**
	 * Возвращает кнопку навигации "Следующий"
	 * @returns {*|jQuery|HTMLElement}
	 */
	getNextButton: function() {
		return $('.tab.tab-nav.next');
	},

	/**
	 * Возвращает кнопку навигации "Предыдущий"
	 * @returns {*|jQuery|HTMLElement}
	 */
	getPreviousButton: function() {
		return $('.tab.tab-nav.prev');
	},

	/**
	 * Возвращает кнопку создания слайда
	 * @returns {*|jQuery|HTMLElement}
	 */
	getCreateButton: function() {
		return $('.tab.new');
	},

	/**
	 * Возвращает контейнер (input) для загружаемых изображений
	 * @returns {*|jQuery|HTMLElement}
	 */
	getImageUploadInput: function() {
		return $("#uploadimage");
	}
};

/** После полной загрузки страницы вызывает конструктор редактора слайдов */
$(document).ready(function() {
	sliderControl.construct();
});
