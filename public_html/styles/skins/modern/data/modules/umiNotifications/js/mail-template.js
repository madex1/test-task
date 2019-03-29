(function($) {
	var defaultWrapperSymbol = '%';

	'use strict';

	/** Выполняется, когда все элементы DOM готовы */
	$(function() {
		initFieldsElements();
		initControlPopup();
	});

	/** @type {string} имя шаблона */
	var template = '';

	/** @type {Object} содержит информацию о идентикаторах и названиях полей */
	var fieldInfoList = {};

	/** @type {string} WHITE_CLASS класс белого цвета */
	var WHITE_CLASS = 'white_bg';

	/** @type {string} BLUE_CLASS класс синего цвета */
	var BLUE_CLASS = 'blue_bg';

	/** @type {string} HOVER_ARROW_HIDE_HTML html стрелки для скрытия элементов */
	var HOVER_ARROW_HIDE_HTML = '<div class="hover-arrow hover-arrow_hide"></div>';

	/** @type {string} HOVER_ARROW_SHOW_HTML html стрелки для отображения элементов */
	var HOVER_ARROW_SHOW_HTML = '<div class="hover-arrow hover-arrow_show"></div>';

	/**
	 * Возвращает имя шаблона
	 * @returns {string}
	 */
	function getTemplate() {
		return template;
	}

	/**
	 * Изменяет имя шаблона
	 * @param {string} templateName имя шаблона
	 */
	function setTemplate(templateName) {
		template = templateName;
	}

	/** Инициализирует нажатие кнопки "Управление переменными" */
	function initControlPopup() {
		$('.control-button').on('click', function() {
			var self = $(this);
			var template = self.attr('name');

			setTemplate(template);

			$.get('/styles/skins/modern/design/js/common/html/umiNotificationPopup.html', function(html) {
				openTypeChoose(html);
				initFieldList();
			});

			var openTypeChoose = function(html) {
				openDialog('',  getLabel('js-notifications-variable-control'), {
					stdButtons: false,
					html: html,
					width: 600,
					openCallback: function() {
						$('#stop_btn').bind('click', function() {
							closeDialog();
						});
					}
				});
			}

		});
	}

	/** Инициализирует создание дерева типов и их полей в popup окне */
	function initFieldList() {
		$.ajax({
			type: 'GET',
			url: '/admin/umiNotifications/getFieldListInfo/' + getTemplate() + '.xml',

			success: function(data) {
				createFieldsTree(data);
			}
		});
	}

	/**
	 * Создает дерево типов и их полей
	 * @param {HTMLElement} data данные о полях и типе
	 */
	function createFieldsTree(data) {
		getSearchButton().text(getLabel('js-label-search-button'));

		var typeList = data.getElementsByTagName('type');

		[].forEach.call(typeList, function(type) {
			var div = document.createElement('div');
			div.setAttribute('class', 'button-wrapper');
			var typeButton = getTypeButton(type);
			getContainer().append(div);
			div.append(typeButton);

			onTypeButtonClick(type.getAttribute('guid'));
			createFieldList(type);
		});

		prepareTree(getAllFieldList(), false);
		onSearchButtonClick();
	}

	/**
	 * Подготавливает дерево типов и их полей
	 * @param {jQuery[]} $fieldList список полей
	 * @param {boolean} isDontShow отображать ли поля
	 */
	function prepareTree($fieldList, isDontShow) {
		$fieldList.each(function() {
			var $field = $(this);
			var fieldVariable = $field.parents('.type').attr('id') + '.' + $field.attr('data');

			onFieldHover($field, fieldVariable);

			var $childFieldList = $field.children('.field');

			if (isDontShow) {
				$field.hide();
			}

			if ($childFieldList.length > 0) {
				prepareTree($childFieldList, true);
			}
		})
	}

	/**
	 * Обработчик нажатия на кнопку с типом данных
	 * @param {string} id идентификатор кнопки
	 */
	function onTypeButtonClick(id) {
		$('#' + id + '-button').on('click', function() {
			var $button = $(this);
			var $child = $button.children('div');

			if ($child.hasClass('hover-arrow_hide')) {
				$button.removeClass(WHITE_CLASS).addClass(BLUE_CLASS);
				$child.removeClass('hover-arrow_hide').addClass('hover-arrow_show');
			} else {
				$button.removeClass(BLUE_CLASS).addClass(WHITE_CLASS);
				$child.removeClass('hover-arrow_show').addClass('hover-arrow_hide');
			}

			switchFieldListVisibility(getFieldList(id));
		});
	}

	/**
	 * Обработчик нажатия на поле с вложенными полями
	 * @param {jQuery} $button поле с вложенными полями
	 */
	function onRelationFieldClick($button) {
		$button.on('click', function() {
			var $arrow = $button.children('.hover-arrow');

			setRelationFieldArrowColorClass($button);

			$arrow.hasClass('hover-arrow_hide')
				? $arrow.removeClass('hover-arrow_hide').addClass('hover-arrow_show')
				: $arrow.removeClass('hover-arrow_show').addClass('hover-arrow_hide');

			var $fieldList = $button.parents('.field:eq(0)').children('.field');

			switchFieldListVisibility($fieldList);
		});
	}

	/**
	 * Переключает видимость полей в списке
	 * @param {jQuery[]} $fieldList список полей
	 */
	function switchFieldListVisibility($fieldList) {
		$fieldList.is(':visible') ? $fieldList.hide() : $fieldList.show();
	}

	/**
	 * Возвращает кнопку с названием типа данных
	 * @param {HTMLElement} type тэг типа данных
	 * @returns {HTMLElement}
	 */
	function getTypeButton(type) {
		var typeButton = document.createElement('button');

		typeButton.setAttribute('id', getTypeGuid(type) + '-button');
		typeButton.setAttribute('class', 'type_button');
		typeButton.innerHTML = type.getAttribute('title') + HOVER_ARROW_HIDE_HTML;

		return typeButton;
	}

	/**
	 * Возвращает гуид типа данных
	 * @param {HTMLElement} type тэг типа данных
	 * @returns {string}
	 */
	function getTypeGuid(type) {
		return type.getAttribute('guid');
	}

	/**
	 * Возвращает элемент контейнера полей в popup
	 * @returns {*|jQuery|HTMLElement}
	 */
	function getContainer() {
		return $('#types_container');
	}

	/**
	 * Создает список полей
	 * @param {HTMLElement} type тэг типа данных
	 */
	function createFieldList(type) {
		var typeId = getTypeGuid(type);

		var list = document.createElement('div');
		list.setAttribute('class', 'type');
		list.setAttribute('id', typeId);

		getContainer().append(list);

		var fieldList = type.getElementsByTagName('field');

		prepareFieldList(fieldList, typeId, getActiveVariableList(), getFieldList(typeId));
	}

	/**
	 * Подготавливает список полей
	 * @param {HTMLElement[]} fieldList список полей
	 * @param {string} typeId идентификатор типа
	 * @param {string[]} activeVariableList список добавленных переменных
	 * @param {jQuery} $parent родитель элемента
	 */
	function prepareFieldList(fieldList, typeId, activeVariableList, $parent) {
		[].forEach.call(fieldList, function(field) {
			fieldInfoList[field.getAttribute('name')] = field.getAttribute('title');

			var $fieldLine = $(getFieldLine(field));
			var fieldName = typeId + '.' + $fieldLine.attr('data');

			setAddedFieldAttribute(fieldName, $fieldLine, activeVariableList);

			if (field.hasChildNodes()) {
				var $button = prepareRelationButton($fieldLine);
				onRelationFieldClick($button);
				prepareFieldList(field.childNodes, typeId, activeVariableList, $fieldLine);
			}

			$parent.append($fieldLine);
		});
	}

	/**
	 * Изменяет атрибуты добавленного поля
	 * @param {string} fieldName имя поля
	 * @param {jQuery} $fieldLine поле
	 * @param {string[]} activeVariableList список добавленных переменных
	 */
	function setAddedFieldAttribute(fieldName, $fieldLine, activeVariableList) {
		if ($.inArray(fieldName, activeVariableList) !== -1) {
			$fieldLine.attr('status', 'added');
			var $fieldTitle = $fieldLine.find('.field_title:eq(0)');
			$fieldTitle.addClass(BLUE_CLASS);
			$fieldTitle.children('.hover-arrow').removeClass(BLUE_CLASS).addClass(WHITE_CLASS);
		}
	}

	/**
	 * Подготавливает для отображения поле с вложенными полями
	 * @param {jQuery} $fieldLine
	 * @returns {jQuery}
	 */
	function prepareRelationButton($fieldLine) {
		var $button = $fieldLine.find('.field_title');
		var buttonHtml = $button.html();

		$button.addClass('field_relation_button');
		$button.html(buttonHtml + HOVER_ARROW_SHOW_HTML);

		setRelationFieldArrowColorClass($button);

		return $button;
	}

	/**
	 * Изменяет класс цвета стрелки для поля с вложенными полями
	 * @param {jQuery} $field поле
	 */
	function setRelationFieldArrowColorClass($field) {
		var $arrow = $field.find('.hover-arrow');

		$field.hasClass(BLUE_CLASS)
			? $arrow.removeClass(WHITE_CLASS).addClass(BLUE_CLASS)
			: $arrow.removeClass(BLUE_CLASS).addClass(WHITE_CLASS);
	}

	/**
	 * Возвращает элемент поля
	 * @param {HTMLElement} field элемент с названием поля
	 * @returns {HTMLElement}
	 */
	function getFieldLine(field) {
		var fieldLine = document.createElement('div');
		fieldLine.setAttribute('class', 'field');
		fieldLine.setAttribute('data', field.getAttribute('name'));

		var fieldTitleWrapper = document.createElement('div');
		fieldTitleWrapper.setAttribute('class', 'field_title_wrapper');

		var fieldText = document.createElement('span');
		fieldText.setAttribute('class', 'field_title');
		fieldText.innerHTML = field.getAttribute('title');

		fieldTitleWrapper.append(fieldText);
		fieldLine.append(fieldTitleWrapper);

		return fieldLine;
	}

	/**
	 * Обработчик наведения на поле
	 * @param {jQuery} $field поле
	 * @param {string} fieldVariable имя поля
	 */
	var onFieldHover = function($field, fieldVariable) {
		var $fieldTitleWrapper = $field.children('.field_title_wrapper');

		$fieldTitleWrapper.on('hover', function() {
			var $fieldLine = $(this);
			var fieldName = $fieldLine.children('span').text();

			var button = ($fieldLine.parent().attr('status') === 'added')
				? createControlButton('delete')
				: createControlButton('add');

			$fieldLine.append(button);

			onControlButtonClick(fieldVariable, fieldName, $field);
		});

		$fieldTitleWrapper.on('mouseleave', function() {
			$(this).children('#handle_button').remove();
		});
	};

	/**
	 * Обработчик нажатия на кнопку добавления/удаления поля
	 * @param {string} variable идентификатор переменной
	 * @param {string} name имя переменной
	 * @param {HTMLElement} field текущее поле
	 */
	function onControlButtonClick(variable, name, field) {
		var data = {
			'variable' : variable,
			'template' : getTemplate()
		};

		$('#handle_button').on('click', function() {
			var self = $(this);

			if (self.attr('status') === 'add') {
				onAddButtonClick(data, name, field, self);
			} else {
				onDeleteButtonClick(data, field, self);
			}
		});
	}

	/**
	 * Обработчик нажатия на кнопку добавления поля
	 * @param {Object} data данные о переменной
	 * @param {string} name имя переменной
	 * @param {HTMLElement} field текущее поле
	 * @param {HTMLElement} button кнопка удаления
	 */
	function onAddButtonClick(data, name, field, button) {
		$.ajax({
			type: 'POST',
			url: '/admin/umiNotifications/addVariable/',
			data: data,

			success: function() {
				var template = getTemplate();
				var variableWrapper = document.createElement('li');
				var variableField = document.createElement('a');

				variableField.setAttribute('class', 'insert-link');
				variableField.setAttribute('data-value', data['variable']);
				variableField.setAttribute('href', '');
				variableField.innerHTML = name;

				variableWrapper.append(variableField);

				$('#' + template + ' ul').append(variableWrapper);

				button.attr('status', 'delete');
				button.text(getLabel('js-handle-button-delete'));
				field.attr('status', 'added');

				var $fieldTitle = field.find('.field_title:eq(0)');
				$fieldTitle.removeClass(WHITE_CLASS).addClass(BLUE_CLASS);
				$fieldTitle.children('.hover-arrow').removeClass(WHITE_CLASS).addClass(BLUE_CLASS);

				initFieldsElements();
			}
		});
	}

	/**
	 * Обработчик нажатия на кнопку удаления поля
	 * @param {Object} data данные о переменной
	 * @param {HTMLElement} field текущее поле
	 * @param {HTMLElement} button кнопка удаления
	 */
	function onDeleteButtonClick(data, field, button) {
		$.ajax({
			type: 'POST',
			url: '/admin/umiNotifications/deleteVariable/',
			data: data,

			success: function() {
				$('*[data-value="' + data['variable'] + '"]').parent('li').remove();

				field.removeAttr('status');

				var $fieldTitle = field.find('.field_title:eq(0)');
				$fieldTitle.removeClass(BLUE_CLASS).addClass(WHITE_CLASS);
				$fieldTitle.children('.hover-arrow').removeClass(BLUE_CLASS).addClass(WHITE_CLASS);

				button.attr('status', 'add');
				button.text(getLabel('js-handle-button-add'));

				initFieldsElements();
			}
		});
	}

	/** Обработчик нажатия на кнопку поиска */
	function onSearchButtonClick() {
		getSearchButton().on('click', function() {
			var $searchValue = $('#type-search').val();
			var $buttonWrapper = $('.button-wrapper');
			var $fieldList = getAllFieldList();

			if ($searchValue === '') {
				showFields($fieldList);
				$buttonWrapper.show();
			} else {
				hideFields($fieldList);
				$buttonWrapper.hide();
			}

			for (var title in fieldInfoList) {
				if (fieldInfoList[title].toUpperCase().indexOf($searchValue.toUpperCase()) !== -1) {
					var $field = $('*[data="' + title + '"]');

					$field.show();
					$field.parents().show();
					$field.parents('div').prev('.button-wrapper:first').show();
				}
			}

			if ($('.field').not(':hidden').length === 0) {
				getContainer().prepend($('<div class="nothing-found">' + getLabel('js-nothing-found') + '</div>'));
			} else {
				$('.nothing-found').hide();
			}
		});
	}

	/**
	 * Показывает поля из списка
	 * @param $fieldList список полей
	 */
	function showFields($fieldList) {
		changeFieldListVisibility($fieldList, 'show');
	}

	/**
	 * Скрывает поля из списка
	 * @param $fieldList список полей
	 */
	function hideFields($fieldList) {
		changeFieldListVisibility($fieldList, 'hide');
	}

	/**
	 * Изменяет видимость у списка полей
	 * @param {jQuery[]} $fieldList список полей
	 * @param {string} visibility видимость (hide или show)
	 */
	function changeFieldListVisibility($fieldList, visibility) {
		$fieldList.each(function() {
			var $field = $(this);

			visibility === 'hide' ? $field.hide() : $field.show();
			var $childFieldList = $field.children('.field');

			if ($childFieldList.length > 0) {
				changeFieldListVisibility($childFieldList, visibility)
			}
		});
	}

	/**
	 * Возвращает кнопку поиска
	 * @returns {*|jQuery|HTMLElement}
	 */
	function getSearchButton() {
		return $('#search-button');
	}

	/**
	 * Создает кнопку добавления/удаления в зависимости от статуса
	 * @param status статус кнопки
	 * @returns {HTMLElement}
	 */
	function createControlButton(status) {
		var button = document.createElement('button');
		button.setAttribute('id', 'handle_button');
		button.setAttribute('status', status);
		button.innerHTML = getLabel('js-handle-button-' + status);

		return button;
	}

	/**
	 * Возвращает список добавленных переменных
	 * @returns {Array}
	 */
	function getActiveVariableList() {
		var linkList = $('#' + getTemplate() + ' ul li a');
		var variableList = [];

		linkList.each(function(){
			variableList.push($(this).attr('data-value'));
		});

		return variableList;
	}

	/**
	 * Возвращает элементы поля типов данных
	 * @returns {*|jQuery|HTMLElement}
	 */
	function getAllFieldList() {
		return $('#types_container > div > div');
	}

	/**
	 * Возвращает список полей
	 * @param id идентификатор типа
	 * @returns {*|jQuery|HTMLElement}
	 */
	function getFieldList(id) {
		return $('#' + id);
	}

	/** Инициализирует элементы вставки идентификаторов полей в соответствующие тестовые поля */
	function initFieldsElements() {
		$('.mail-template li a').unbind('click').bind('click', function(e) {
			e.preventDefault();

			var fieldValue = getInsertingField($(this).data('value'));
			var $textBox = getRelatedTextBox(this);

			if (!$textBox.length) {
				return;
			}

			switch ($textBox.prop('tagName').toLowerCase()) {
				case 'input':
					var caretPosition = $textBox.get(0).selectionStart;
					$textBox.val(insertSubString(fieldValue, $textBox.val(), caretPosition));
					$textBox.focus();
					setCaretPosition($textBox.get(0), caretPosition + fieldValue.length);
					break;
				case 'textarea':
					if (!window['tinyMCE']) {
						return;
					}

					insertField($(this), getRelatedEditor($textBox));
					break;

				//no default
			}
		});
	}

	/** Вставляет идентификатор поля в контент редактора */
	function insertField($field, editor) {
		insertText($field.data('value'), editor);
	}

	/** Вставляет строку в позицию курсора редактора */
	function insertText(text, editor, wrapperSymbol) {
		if (editor && typeof editor.execCommand != 'undefined') {
			editor.execCommand('mceInsertContent', true, getInsertingField(text, wrapperSymbol));
		}
	}

	/**
	 * Возвращает строковое представление поля для вставки
	 * @param {String} fieldName имя поля
	 * @param {String|undefined} wrapperSymbol символ, обрамляющий имя поля
	 * @returns {string}
	 */
	function getInsertingField(fieldName, wrapperSymbol) {
		wrapperSymbol = typeof wrapperSymbol == 'string' ? wrapperSymbol : defaultWrapperSymbol;

		return [wrapperSymbol, fieldName, wrapperSymbol].join('');
	}

	/**
	 * Вставляет подстроку в строку в указанной позиции
	 * @param {String} subString вставляемая строка
	 * @param {String} string строка, в которую будет вставлена под строка
	 * @param {Number} position позиция вставки подстроки
	 * @returns {string} новая строка с вставленной подстрокой
	 */
	function insertSubString(subString, string, position) {
		return [string.slice(0, position), subString, string.slice(position)].join('');
	}

	/**
	 * Возвращает связанное тестовое поле со ссылкой
	 * @param {HTMLElement} link элемент ссылки
	 * @returns {*}
	 */
	function getRelatedTextBox(link) {
		return $(link).closest('.mail-template').find('input, textarea').eq(0);
	}

	/**
	 * Возвращает редактор, связанный с текстовым полем
	 * @param textBox элемент текстового поля
	 * @returns {*}
	 */
	function getRelatedEditor(textBox) {
		return tinyMCE.get($(textBox).attr('id'));
	}

	/**
	 * Устанавливает позицию курсора в текстовом поле
	 * @param {HTMLElement} textBox элемент тестового поля
	 * @param {Number} caretPos новая позиция курсора
	 */
	function setCaretPosition(textBox, caretPos) {
		if(textBox != null) {
			if(textBox.createTextRange) {
				var range = textBox.createTextRange();
				range.move('character', caretPos);
				range.select();
			}
			else {
				if(textBox.selectionStart) {
					textBox.focus();
					textBox.setSelectionRange(caretPos, caretPos);
				}
				else
					textBox.focus();
			}
		}
	}

})(jQuery);