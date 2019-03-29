/** Функционал виджета выбора устанавливаемого решения. Отображает список решений с фильтрами и поиском. */
(function($, _) {
	"use strict";

	/** @type {String} CATEGORY_FILTER_LIST_SELECTOR селектор списка фильтров решений по категориям */
	var CATEGORY_FILTER_LIST_SELECTOR = 'div.category a';
	/** @type {String} CATEGORY_ACTIVE_FILTER_SELECTOR селектор активного фильтра решений по категории */
	var CATEGORY_ACTIVE_FILTER_SELECTOR = 'div.category a.active';
	/** @type {String} TYPE_FILTER_LIST_SELECTOR селектор списка фильтров решений по типам */
	var TYPE_FILTER_LIST_SELECTOR = 'div.umiru_body td';
	/** @type {String} TYPE_ACTIVE_FILTER_SELECTOR селектор активного фильтра решений по типу */
	var TYPE_ACTIVE_FILTER_SELECTOR = 'div.umiru_body td.active';
	/** @type {String} ENABLED_SOLUTION_LIST_SELECTOR список решений, удовлетворяющих текущему фильтру */
	var ENABLED_SOLUTION_LIST_SELECTOR = 'div.site_holder div.enabled';
	/** @type {String} DISABLED_SOLUTION_LIST_SELECTOR список решений, неудовлетворяющих текущему фильтру */
	var DISABLED_SOLUTION_LIST_SELECTOR = 'div.site_holder div.disabled';
	/** @type {String} FULL_SOLUTION_LIST_SELECTOR селектор списка всех решений */
	var FULL_SOLUTION_LIST_SELECTOR = 'div.site_holder div.site';
	/** @type {String} RESET_FILTER_BUTTON_SELECTOR селектор кнопки сброса фильтров */
	var RESET_FILTER_BUTTON_SELECTOR = 'input.reset_filter';
	/** @type {String} SEARCH_BUTTON_SELECTOR селектор кнопки поиска решений */
	var SEARCH_BUTTON_SELECTOR = 'input.run_search';
	/** @type {String} SEARCH_QUERY_CONTAINER_SELECTOR селектор поля для ввода поисковой строки */
	var SEARCH_QUERY_CONTAINER_SELECTOR = 'input.search';
	/** @type {String} CHOOSE_BUTTON_LIST_SELECTOR селектор списка кнопок выбора решения */
	var CHOOSE_BUTTON_LIST_SELECTOR = 'a.choose';
	/** @type {String} SELECTED_SITE_SELECTOR селектор выбранного решения */
	var SELECTED_SITE_SELECTOR = 'div.site_holder div.selected';
	/** @type {String} EMPTY_SOLUTION_LIST_MESSAGE селектор сообщения пустого списка решений */
	var EMPTY_SOLUTION_LIST_MESSAGE = '#empty_solution_list';
	/** @type {Number|null} activeCategoryId идентификатор активного фильтра по категории */
	var activeCategoryId;
	/** @type {Number|null} activeTypeId идентификатор активного фильтра по типу */
	var activeTypeId;

	/** Конструктор */
	$(function() {
		bindCategoryFilter();
		bindTypeFilter();
		bindSearchButton();
		bindClickEnterButton();
		bindResetFilter();
		bindChooseButtons();
	});

	/**
	 * Устанавливает идентификатор активного фильтра по категории
	 * @param {Number} id идентификатор активного фильтра по категории
	 */
	var setActiveCategoryId = function(id) {
		activeCategoryId = id;
	};

	/**
	 * Возвращает идентификатор активного фильтра по категории
	 * @returns {Number|null}
	 */
	var getActiveCategoryId = function() {
		return activeCategoryId;
	};

	/**
	 * Устанавливает идентификатор активного фильтра по типу
	 * @param {Number} id идентификатор активного фильтра по типу
	 */
	var setActiveTypeId = function(id) {
		activeTypeId = id;
	};

	/**
	 * Возвращает идентификатор активного фильтра по типу
	 * @returns {Number|null}
	 */
	var getActiveTypeId = function() {
		return activeTypeId;
	};

	/** Прикрепляет действие к фильтрам по категории */
	var bindCategoryFilter = function() {
		getCategoryFilterList().on('click', function() {
			if (isSiteSelected()) {
				return notifyAboutSelectedSite();
			}
			filterByCategory($(this));
		});
	};

	/**
	 * Фильтрует список решений по категории
	 * @param {jQuery|HTMLElement} $category категория
	 */
	var filterByCategory = function($category) {
		resetActiveCategoryFilter();
		setActiveCategoryId($category.attr('rel'));
		highlightFilter($category);
		activateFilter();
	};

	/** Прикрепляет действие к фильтрам по типам */
	var bindTypeFilter = function() {
		getTypeFilterList().on('click', function() {
			if (isSiteSelected()) {
				return notifyAboutSelectedSite();
			}
			filterByType($(this));
		});
	};

	/**
	 * Фильтрует список решений по типу
	 * @param {jQuery|HTMLElement} $type тип
	 */
	var filterByType = function($type) {
		resetActiveTypeFilter();
		setActiveTypeId($type.attr('rel'));
		highlightFilter($type);
		activateFilter();
	};

	/** Активирует фильтацию решения по типу и категории */
	var activateFilter = function() {
		var categoryId = getActiveCategoryId();
		var typeId = getActiveTypeId();
		filterEnabledSolutionList(function($solution) {
			var solutionBelongsToCategory = (categoryId) ? isSolutionBelongsCategory($solution, categoryId) : true;
			var solutionBelongsToType = (typeId) ? isSolutionBelongsType($solution, typeId) : true;
			return solutionBelongsToCategory && solutionBelongsToType;
		});
	};

	/** Сбрасывает фильтра по активной категории */
	var resetActiveCategoryFilter = function() {
		if (isCategoryFilterApplied()) {
			var $activeFilter = getActiveCategoryFilter();
			resetFilterByCategory($activeFilter.attr('rel'));
			disableFilterHighlighting($activeFilter);
		}
		setActiveCategoryId(null);
	};

	/** Прикрепляет действие к кнопке сброса фильтров */
	var bindResetFilter = function() {
		getResetFilterButton().on('click', function() {
			if (isSiteSelected()) {
				return notifyAboutSelectedSite();
			}
			resetFilter();
		});
	};

	/** Сбрасывает фильтры и поиск */
	var resetFilter = function() {
		resetActiveCategoryFilter();
		resetActiveTypeFilter();
		clearSearchQueryContainer();
		showAllSolutions();
	};

	/** Прикрепляет действие к кнопкам выбора решений */
	var bindChooseButtons = function() {
		getChooseButtonList().on('click', function() {
			var $button = $(this);
			var text = $button.text();
			var altText = $button.data('alt-text');
			$button.text(altText);
			$button.data('alt-text', text);

			var $buttonWrapper = $button.parent().parent();
			var siteName = $buttonWrapper.data('name');

			if (!$buttonWrapper.hasClass('selected')) {
				$buttonWrapper.addClass('selected');
				filterBySearchQuery(siteName);
			} else {
				$buttonWrapper.removeClass('selected');
				showAllSolutions();
			}
		});
	};

	/**
	 * Возвращает список кнопок выбора решений
	 * @returns {jQuery|HTMLElement}
	 */
	var getChooseButtonList = function() {
		return $(CHOOSE_BUTTON_LIST_SELECTOR);
	};

	/**
	 * Возвращает выбранное решение
	 * @returns {jQuery|HTMLElement}
	 */
	var getSelectedSite = function() {
		return $(SELECTED_SITE_SELECTOR);
	};

	/**
	 * Определяет выбрано ли какое-нибудь решение
	 * @returns {boolean}
	 */
	var isSiteSelected = function() {
		return getSelectedSite().length !== 0;
	};

	/** Уведомляет о том, что решение уже было выбрано */
	var notifyAboutSelectedSite = function() {
		alert(getLabel('js-notify-about-selected-site'));
	};

	/** Прикрепляет поиск к нажатию кнопки enter */
	var bindClickEnterButton = function() {
		getSearchQueryContainer().keydown(function(event) {
			if (event.key === 'Enter') {
				getSearchButton().click();
			}
		})
	};

	/** Очищает поле для ввода поискового запроса */
	var clearSearchQueryContainer = function() {
		getSearchQueryContainer().val('');
	};

	/**
	 * Возвращает список фильтров по категориям
	 * @returns {jQuery|HTMLElement}
	 */
	var getCategoryFilterList = function() {
		return $(CATEGORY_FILTER_LIST_SELECTOR);
	};

	/**
	 * Возвращает активный фильтр по категории
	 * @returns {jQuery|HTMLElement}
	 */
	var getActiveCategoryFilter = function() {
		return $(CATEGORY_ACTIVE_FILTER_SELECTOR);
	};

	/**
	 * Возвращает список решений, удовлетворяющих фильтру
	 * @returns {jQuery|HTMLElement}
	 */
	var getEnabledSolutionList = function() {
		return $(ENABLED_SOLUTION_LIST_SELECTOR);
	};

	/**
	 * Опредяляет есть хотя бы одно решение, удовлетворяющиее фильтру
	 * @returns {boolean}
	 */
	var hasEnabledSolution = function() {
		return getEnabledSolutionList().length > 0;
	};

	/**
	 * Возвращает список решений, неудовлетворяющих фильтру
	 * @returns {jQuery|HTMLElement}
	 */
	var getDisabledSolutionList = function() {
		return $(DISABLED_SOLUTION_LIST_SELECTOR);
	};

	/**
	 * Возвращает список всех решений
	 * @returns {jQuery|HTMLElement}
	 */
	var getFullSolutionList = function() {
		return $(FULL_SOLUTION_LIST_SELECTOR);
	};

	/**
	 * Возвращает кнопку сброса фильтров
	 * @returns {jQuery|HTMLElement}
	 */
	var getResetFilterButton = function() {
		return $(RESET_FILTER_BUTTON_SELECTOR);
	};

	/**
	 * Сбрасывает фильтр по категории
	 * @param {String} category категория
	 */
	var resetFilterByCategory = function(category) {
		filterDisabledSolutionList(function($solution) {
			return !isSolutionBelongsCategory($solution, category);
		});
	};

	/**
	 * Определяет принадлежит ли решение к категории
	 * @param {jQuery|HTMLElement} $solution решение
	 * @param {String} category категория
	 */
	var isSolutionBelongsCategory = function($solution, category) {
		return _.contains($solution.data('category-id-list'), category);
	};

	/**
	 * Фильтрует список решений, удовлетворяющих текущему фильтру
	 * @param {Function} callback метод фильтрации
	 */
	var filterEnabledSolutionList = function(callback) {
		filterSolutionList(getEnabledSolutionList(), callback);
	};

	/**
	 * Фильтрует список решений, неудовлетворяющих текущему фильтру
	 * @param {Function} callback метод фильтрации
	 */
	var filterDisabledSolutionList = function(callback) {
		filterSolutionList(getDisabledSolutionList(), callback);
	};

	/**
	 * Фильтрует полный список решений
	 * @param {Function} callback метод фильтрации
	 */
	var filterFullSolutionList = function(callback) {
		filterSolutionList(getFullSolutionList(), callback);
	};

	/** Показывает сообщение о пустом списке решений */
	var showEmptySolutionListMessage = function() {
		enableSolutionListItem(getEmptySolutionMessageContainer());
	};

	/** Скрывает сообщение о пустом списке решений */
	var hideEmptySolutionListMessage = function() {
		disableSolutionListItem(getEmptySolutionMessageContainer());
	};

	/**
	 * Возвращает контейнер сообщения о пустом списке решений
	 * @returns {jQuery|HTMLElement}
	 */
	var getEmptySolutionMessageContainer = function() {
		return $(EMPTY_SOLUTION_LIST_MESSAGE);
	};

	/**
	 * Фильтрует произвольный список решений
	 * @param {jQuery|HTMLElement} $solutionList список решений
	 * @param {Function} callback метод фильтрации
	 */
	var filterSolutionList = function($solutionList, callback) {
		hideEmptySolutionListMessage();

		_.each($solutionList, function(solutionNode) {
			var $solution = $(solutionNode);
			callback($solution) ? enableSolutionListItem($solution) : disableSolutionListItem($solution);
		});

		if (!hasEnabledSolution()) {
			showEmptySolutionListMessage();
		}
	};

	/**
	 * Определяет примене ли фильтр по категориям
	 * @returns {boolean}
	 */
	var isCategoryFilterApplied = function() {
		return getActiveCategoryFilter().length !== 0;
	};

	/**
	 * Включает отображение всех решений
	 */
	var showAllSolutions = function() {
		_.each(getFullSolutionList(), function(solutionNode) {
			enableSolutionListItem($(solutionNode));
		});
	};

	/**
	 * Включает отображение элемента списка решений
	 * @param {jQuery|HTMLElement} $itemContainer контейнер элемента списка решений
	 */
	var enableSolutionListItem = function($itemContainer) {
		$itemContainer.removeClass('disabled');
		$itemContainer.addClass('enabled');
	};

	/**
	 * Отключает отображение элемента списка решений
	 * @param {jQuery|HTMLElement} $itemContainer контейнер элемента списка решений
	 */
	var disableSolutionListItem = function($itemContainer) {
		$itemContainer.removeClass('enabled');
		$itemContainer.addClass('disabled');
	};

	/**
	 * Помечает фильтр, как активный
	 * @param {jQuery|HTMLElement} $filter фильтр
	 */
	var highlightFilter = function($filter) {
		$filter.addClass('active');
	};

	/**
	 * Убирает отметку фильтра в качестве активного
	 * @param {jQuery|HTMLElement} $filter фильтр
	 */
	var disableFilterHighlighting = function($filter) {
		$filter.removeClass('active');
	};

	/**
	 * Возвращает список фильтров по типам
	 * @returns {jQuery|HTMLElement}
	 */
	var getTypeFilterList = function() {
		return $(TYPE_FILTER_LIST_SELECTOR);
	};

	/**
	 * Возвращает активный фильтр по типу
	 * @returns {jQuery|HTMLElement}
	 */
	var getActiveTypeFilter = function() {
		return $(TYPE_ACTIVE_FILTER_SELECTOR);
	};

	/**
	 * Определяет применен ли фильтр по типу
	 * @returns {boolean}
	 */
	var isTypeFilterApplied = function() {
		return getActiveTypeFilter().length !== 0;
	};

	/** Сбрасывает фильтр по активному типу */
	var resetActiveTypeFilter = function() {
		if (isTypeFilterApplied()) {
			var $activeFilter = getActiveTypeFilter();
			resetFilterByType($activeFilter.attr('rel'));
			disableFilterHighlighting($activeFilter);
		}
		setActiveTypeId(null);
	};

	/**
	 * Сбрасывает фильтр по типу
	 * @param {String} type тип
	 */
	var resetFilterByType = function(type) {
		filterDisabledSolutionList(function($solution) {
			return !isSolutionBelongsType($solution, type);
		});
	};

	/**
	 * Определяет принадлежит ли решение к типу
	 * @param {*|HTMLElement} $solution решение
	 * @param {String} type тип
	 * @returns {boolean}
	 */
	var isSolutionBelongsType = function($solution, type) {
		return $solution.data('type-id') == type;
	};

	/** Прикрепляет действие к кнопке поиска */
	var bindSearchButton = function() {
		getSearchButton().on('click', function() {
			if (isSiteSelected()) {
				return notifyAboutSelectedSite();
			}
			resetActiveCategoryFilter();
			resetActiveTypeFilter();
			filterBySearchQuery(getSearchQuery());
		});
	};

	/**
	 * Фильтрует список решений по поисковой строке
	 * @param {String} searchQuery поисковая строка
	 */
	var filterBySearchQuery = function(searchQuery) {
		var lowerCaseSearchQuery = searchQuery.toLowerCase();
		filterFullSolutionList(function($solution) {
			switch (true) {
				case lowerCaseSearchQuery.length < 3 :
				case $solution.data('id') == lowerCaseSearchQuery :
				case $solution.data('name') == lowerCaseSearchQuery :
				case $solution.data('title').indexOf(lowerCaseSearchQuery) != -1 :
				case $solution.data('keywords').indexOf(lowerCaseSearchQuery) != -1 : {
					return true;
				}
				default : {
					return false;
				}
			}
		});
	};

	/**
	 * Возвращает поисковую строку
	 * @returns {String}
	 */
	var getSearchQuery = function() {
		return getSearchQueryContainer().val();
	};

	/**
	 * Возвращает поля для ввода поисковой строки
	 * @returns {*|HTMLElement}
	 */
	var getSearchQueryContainer = function() {
		return $(SEARCH_QUERY_CONTAINER_SELECTOR);
	};

	/**
	 * Возвращает кнопку поиска
	 * @returns {*|HTMLElement}
	 */
	var getSearchButton = function() {
		return $(SEARCH_BUTTON_SELECTOR);
	};

})(jQuery, _);