<?php

	use UmiCms\Service;

	/**
	 * Базовый класс модуля "Каталог".
	 *
	 * Модуль управляет следующими сущностями:
	 *
	 * 1) Разделы каталога;
	 * 2) Объекты каталога;
	 *
	 * Модуль умеет генерировать поисковый индекс для объектов каталога
	 * и искать по нему.
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_katalog/
	 */
	class catalog extends def_module {

		/** @var int $per_page ограничение на количество выводимых страницы */
		public $per_page;

		/* @const строковой идентификатор поля раздела каталога, в котором хранится id проиндексированного раздела каталога */
		const FILTER_INDEX_SOURCE_FIELD_NAME = 'index_source';

		/* @const строковой идентификатор поля раздела каталога, в котором хранится уровень вложенности создания индекса */
		const FILTER_INDEX_NESTING_DEEP_FIELD_NAME = 'index_level';

		/* @const строковой идентификатор поля раздела каталога, в котором хранится дата переиндексации раздела */
		const FILTER_INDEX_INDEXATION_DATE = 'index_date';

		/* @const строковой идентификатор поля раздела каталога, в котором хранится состояние переиндексации раздела */
		const FILTER_INDEX_INDEXATION_STATE = 'index_state';

		/* @const строковой идентификатор поля раздела каталога, в котором хранится флаг необходимости индексации */
		const FILTER_INDEX_INDEXATION_NEEDED = 'index_choose';

		/**
		 * @const int CRON_FILTER_INDEXATION_LIMIT количество объектов каталога,
		 * обрабатываемых при индексации фильтров по крону за одну итерацию
		 */
		const CRON_FILTER_INDEXATION_LIMIT = 25;

		/** @const string ADMIN_CLASS имя класса административного функционала */
		const ADMIN_CLASS = 'CatalogAdmin';

		/**
		 * Конструктор
		 * @throws coreException
		 */
		public function __construct() {
			parent::__construct();

			$this->per_page = Service::Registry()
				->get('//modules/catalog/per_page');

			if (Service::Request()->isAdmin()) {
				$this->initTabs()
					->includeAdminClasses();
			}

			$this->includeCommonClasses();
		}

		/**
		 * Создает вкладки административной панели модуля
		 * @return $this
		 */
		public function initTabs() {
			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('tree', ['tree']);
				$commonTabs->add('filters', ['filters']);
			}

			$configTabs = $this->getConfigTabs();

			if ($configTabs instanceof iAdminModuleTabs) {
				$configTabs->add('config');
				$configTabs->add('tradeOfferPriceTypes');
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('CatalogAdmin');

			$this->__loadLib('Classes/Trade/Offer/Admin.php');
			$this->__implement('UmiCms\Classes\Components\Catalog\Trade\Offer\Admin');

			$this->__loadLib('Classes/Trade/Offer/Price/Type/Admin.php');
			$this->__implement('UmiCms\Classes\Components\Catalog\Trade\Offer\Price\Type\Admin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('CatalogCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('CatalogMacros');

			$this->loadSiteExtension();

			$this->__loadLib('handlers.php');
			$this->__implement('CatalogHandlers');

			$this->__loadLib('customMacros.php');
			$this->__implement('CatalogCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Возвращает идентификатор иерархического типа объекта каталога
		 * @return int
		 */
		public function getProductHierarchyTypeId() {
			static $cache;

			if (is_numeric($cache)) {
				return $cache;
			}

			$umiTypesHelper = umiTypesHelper::getInstance();
			return $cache = $umiTypesHelper->getHierarchyTypeIdByName('catalog', 'object');
		}

		/**
		 * Возвращает идентификатор иерархического типа раздела каталога
		 * @return int
		 */
		public function getCategoryHierarchyTypeId() {
			static $cache;

			if (is_numeric($cache)) {
				return $cache;
			}

			$umiTypesHelper = umiTypesHelper::getInstance();
			return $cache = $umiTypesHelper->getHierarchyTypeIdByName('catalog', 'category');
		}

		/**
		 * Является ли параметр объектом каталога
		 * @param mixed $page проверяемый параметр
		 * @return bool
		 */
		public function isCatalogObject($page) {
			if (!$page instanceof iUmiHierarchyElement) {
				return false;
			}

			$hierarchyTypeId = $this->getProductHierarchyTypeId();

			return $page->getTypeId() == $hierarchyTypeId;
		}

		/**
		 * Является ли параметр разделом каталога
		 * @param mixed $page проверяемый параметр
		 * @return bool
		 */
		public function isCatalogCategory($page) {
			if (!$page instanceof iUmiHierarchyElement) {
				return false;
			}

			$hierarchyTypeId = $this->getCategoryHierarchyTypeId();

			return $page->getTypeId() == $hierarchyTypeId;
		}

		/**
		 * Указывает у дочерних разделов каталога источник индекса фильтров
		 * @param int $parentId ид родительского раздела
		 * @param int $level уровень вложенности дочерних разделов
		 * @return bool
		 * @throws selectorException
		 */
		public function markChildrenCategories($parentId, $level) {
			$query = new selector('pages');
			$query->types('object-type')->name('catalog', 'category');
			$query->where('hierarchy')->page($parentId)->level($level);
			$query->option('no-length')->value(true);
			$query->option('no-permissions')->value(true);
			$query->option('return')->value('id');
			$childrenCategoryIdList = $query->result();

			if (empty($childrenCategoryIdList)) {
				return true;
			}

			$umiHierarchy = umiHierarchy::getInstance();

			/* @var array $childrenCategoryId */
			foreach ($childrenCategoryIdList as $childrenCategoryId) {
				if (!isset($childrenCategoryId['id'])) {
					continue;
				}

				$categoryId = $childrenCategoryId['id'];
				$category = $umiHierarchy->getElement($categoryId);

				if (!$category instanceof iUmiHierarchyElement) {
					continue;
				}

				$this->setFilterSourceToCategory($category, $parentId, $level);
				$umiHierarchy->unloadElement($category->getId());
			}

			return true;
		}

		/**
		 * Обновляет индекс страницы
		 * @param iUmiHierarchyElement $page объект страницы
		 * @param iUmiHierarchyElement $category объект родительской страницы для $page
		 * @param string $operation какую операцию нужно произвести с индексом (delete/update)
		 * @return bool
		 * @throws publicException если передано неправильное название операции
		 */
		public function processPage(iUmiHierarchyElement $page, iUmiHierarchyElement $category, $operation) {
			/* @var iUmiHierarchyElement $page */
			try {
				/* @var iUmiHierarchyElement $sourceCategory */
				$sourceCategory = $this->getCategoryFilterSource($category);
			} catch (publicException $exception) {
				return false;
			}

			if (!$sourceCategory instanceof iUmiHierarchyElement) {
				return false;
			}

			$hierarchyTypeId = $this->getProductHierarchyTypeId();
			$sourceHierarchyLevel = (int) $sourceCategory->getValue(self::FILTER_INDEX_NESTING_DEEP_FIELD_NAME);

			$indexGenerator = new FilterIndexGenerator($hierarchyTypeId, 'pages');
			$indexGenerator->setHierarchyCondition($sourceCategory->getId(), $sourceHierarchyLevel);

			switch ($operation) {
				case 'update': {
					return $indexGenerator->updateEntityIndex($page);
				}
				case 'delete': {
					return $indexGenerator->dropEntityIndex($page->getId());
				}
				default: {
					throw new publicException(__METHOD__ . ': wrong operation given: ' . $operation);
				}
			}
		}

		/**
		 * Возвращает гуиды полей типа данных раздел каталога и его дочерних типов
		 * @return array
		 * @throws databaseException
		 */
		public function getAllCatalogCategoriesFieldsGUIDs() {
			$umiTypesHelper = umiTypesHelper::getInstance();
			$catalogCategoryHierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName('catalog', 'category');
			$objectTypesFieldsData = $umiTypesHelper->getFieldsByHierarchyTypeId($catalogCategoryHierarchyTypeId);

			if (umiCount($objectTypesFieldsData) == 0) {
				return [];
			}

			$hierarchyTypesFieldsData = call_user_func_array('array_merge', $objectTypesFieldsData);
			$hierarchyTypesFieldsData = array_unique($hierarchyTypesFieldsData);

			return is_array($hierarchyTypesFieldsData) ? $hierarchyTypesFieldsData : [];
		}

		/**
		 * Возвращает список идентификаторов разделов каталога, нуждающихся в переиндексации фильтров
		 * @return array
		 *
		 * [
		 *        [
		 *            'id' => number
		 *        ]
		 * ]
		 * @throws selectorException
		 */
		public function getIndexedCategoryIdList() {
			$query = new selector('pages');
			$query->types('object-type')->name('catalog', 'category');
			$query->where(self::FILTER_INDEX_INDEXATION_NEEDED)->equals(true);
			$query->where('domain')->isnotnull();
			$query->where('lang')->isnotnull();
			$query->option('no-length')->value(true);
			$query->option('no-permissions')->value(true);
			$query->option('return')->value('id');
			return $query->result();
		}

		/**
		 * Возвращает список разделов каталога, нуждающихся в переиндексации фильтров
		 * @return iUmiHierarchyElement[]
		 * @throws selectorException
		 */
		public function getIndexedCategories() {
			$query = new selector('pages');
			$query->types('object-type')->name('catalog', 'category');
			$query->where(self::FILTER_INDEX_INDEXATION_NEEDED)->equals(true);
			$query->where('domain')->isnotnull();
			$query->where('lang')->isnotnull();
			$query->option('no-length')->value(true);
			return $query->result();
		}

		/**
		 * Переиндексирует фильтры раздела каталога
		 * @param iUmiHierarchyElement $category объект раздела каталога
		 * @return bool
		 * @throws publicAdminException
		 * @throws publicException
		 * @throws Exception
		 */
		public function reIndexCategory(iUmiHierarchyElement $category) {
			/* @var iUmiHierarchyElement $category */
			$level = (int) $category->getValue(self::FILTER_INDEX_NESTING_DEEP_FIELD_NAME);
			$parentId = $category->getId();
			$catalogObjectHierarchyTypeId = $this->getProductHierarchyTypeId();

			$indexGenerator = new FilterIndexGenerator($catalogObjectHierarchyTypeId, 'pages');
			$indexGenerator->setHierarchyCondition($parentId, $level);
			$indexGenerator->setLimit(self::CRON_FILTER_INDEXATION_LIMIT);

			for ($counter = 0; !$indexGenerator->isDone(); $counter++) {
				$indexGenerator->run();
			}

			$category->setValue(self::FILTER_INDEX_SOURCE_FIELD_NAME, $parentId);
			$category->setValue(self::FILTER_INDEX_INDEXATION_DATE, new umiDate());
			$category->setValue(self::FILTER_INDEX_INDEXATION_STATE, 100);
			$category->commit();

			$this->markChildrenCategories($parentId, $level - 1);
			return true;
		}

		/**
		 * Записывает ид раздела-источника индекса в раздел каталога.
		 * Для раздела-источника дополнительно сохраняется уровень вложенности.
		 * @param iUmiHierarchyElement $category объект категории
		 * @param int $sourceCategoryId ид раздела-источника
		 * @param int $level уровень вложенности
		 */
		public function setFilterSourceToCategory(iUmiHierarchyElement $category, $sourceCategoryId, $level) {
			/* @var iUmiHierarchyElement $category */
			if ($category->getId() == $sourceCategoryId) {
				$category->setValue(self::FILTER_INDEX_NESTING_DEEP_FIELD_NAME, $level);
			}

			$category->setValue(self::FILTER_INDEX_SOURCE_FIELD_NAME, $sourceCategoryId);
			$category->commit();
		}

		/**
		 * Возвращает строковые идентификаторы групп, фильтруемые поля которых должны
		 * присутствовать в форме фильтрации.
		 * @param string $groupNames строковые идентификаторы групп разделенные символом ";"
		 * @param iUmiObjectType $type объект типа данных
		 * @return array
		 */
		public function getGroupsNames($groupNames, iUmiObjectType $type) {
			$allGroupsNames = [];
			/* @var iUmiFieldsGroup $group */
			foreach ($type->getFieldsGroupsList() as $group) {
				$allGroupsNames[] = $group->getName();
			}

			$groups = [];

			if (is_string($groupNames)) {
				$groupNames = explode(';', trim($groupNames));
				foreach ($groupNames as $groupName) {
					if (in_array($groupName, $allGroupsNames)) {
						$groups[] = $groupName;
					}
				}
			} else {
				$groups = $allGroupsNames;
			}

			return $groups;
		}

		/**
		 * Возвращает объект страница раздела-источника индекса для переданного раздела
		 * @param iUmiHierarchyElement $category объект раздела, для которого нужно определить источник индекса
		 * @return iUmiHierarchyElement
		 * @throws publicException если не удалось определить раздел-источник или он некорректен
		 */
		public function getCategoryFilterSource(iUmiHierarchyElement $category) {
			$sourceCategoryId = $category->getValue(self::FILTER_INDEX_SOURCE_FIELD_NAME);

			if (!is_numeric($sourceCategoryId)) {
				/* @var iUmiHierarchyElement $category */
				throw new publicException(
					__METHOD__ . ': cant take index source from category with id = ' . $category->getId()
				);
			}

			/* @var iUmiHierarchyElement $sourceCategory */
			$sourceCategory = umiHierarchy::getInstance()->getElement($sourceCategoryId);

			if (!$sourceCategory instanceof iUmiHierarchyElement) {
				throw new publicException(__METHOD__ . ': index source not found');
			}

			$umiTypesHelper = umiTypesHelper::getInstance();
			$hierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName('catalog', 'category');

			if ($hierarchyTypeId !== $sourceCategory->getTypeId()) {
				throw new publicException(__METHOD__ . ': wrong index source given');
			}

			return $sourceCategory;
		}

		/**
		 * Возвращает строковые идентификаторы фильтруемых полей, привязанные к строковым идентификаторам групп
		 * @param iUmiObjectType $type объектный тип данных, содержащий группы
		 * @param array $groups строковые идентификаторы групп
		 * @return array
		 * @throws publicAdminException если запрос к бд завершился с ошибкой
		 * @throws databaseException
		 */
		public function getFilteredFieldsNamesByGroups(iUmiObjectType $type, array $groups) {
			/* @var iUmiObjectType $type */
			$objectTypeId = $type->getId();
			$connection = ConnectionPool::getInstance()->getConnection();
			$groups = array_map([$connection, 'escape'], $groups);
			$groups = "'" . implode("', '", $groups) . "'";
			$sql = <<<SQL
	SELECT
	  cms3_object_field_groups.name as group_name,
	  cms3_object_field_groups.title as group_title,
	  cms3_object_field_groups.tip as group_tip,
	  cms3_object_fields.name as field_name,
	  cms3_fields_controller.ord as field_ord
	FROM
	  cms3_object_fields
	  LEFT JOIN cms3_fields_controller ON cms3_object_fields.id = cms3_fields_controller.field_id
	  LEFT JOIN cms3_object_field_groups ON cms3_fields_controller.group_id = cms3_object_field_groups.id
	  LEFT JOIN cms3_object_types ON cms3_object_field_groups.type_id = cms3_object_types.id
	WHERE
	 cms3_object_types.id = $objectTypeId AND
	 cms3_object_field_groups.name IN ($groups) AND
	 cms3_object_field_groups.is_active = 1 AND
	 cms3_object_field_groups.is_visible = 1 AND
	 cms3_object_fields.in_filter = 1 AND
	 cms3_object_fields.is_visible = 1
SQL;

			try {
				$result = $connection->queryResult($sql);
			} catch (databaseException $e) {
				throw new publicAdminException(
					__METHOD__ . ': MySQL exception has occurred:' . $e->getCode() . ' ' . $e->getMessage()
				);
			}

			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return [];
			}

			$groupsToFields = [];

			foreach ($result as $row) {
				$order = (int) $row['field_ord'];

				while (isset($groupsToFields[$row['group_name']]['fields'][$order])) {
					$order++;
				}

				$groupsToFields[$row['group_name']]['fields'][$order] = $row['field_name'];
				$groupsToFields[$row['group_name']]['title'] = $type->translateLabel($row['group_title']);
				$groupsToFields[$row['group_name']]['tip'] = $type->translateLabel($row['group_tip']);
			}

			foreach ($groupsToFields as $index => $groupInfo) {
				if (!isset($groupInfo['fields'])) {
					continue;
				}

				$fieldList = $groupInfo['fields'];
				ksort($fieldList);
				$groupsToFields[$index]['fields'] = $fieldList;
			}

			return $groupsToFields;
		}

		/**
		 * Возвращает идентификаторы разделов каталога, включая переданный, дочерние переданному разделу
		 * каталога на переданном уровне вложенности.
		 * @param int $categoryId ид раздела каталога
		 * @param int $level уровень вложенности
		 * @return array
		 * @throws selectorException
		 */
		public function getCategoriesIds($categoryId, $level) {
			$categoriesIds = [$categoryId];

			if ($level > 1) {
				$childrenCategoriesIds = $this->getChildrenCategories($categoryId, $level);
				$categoriesIds = array_merge($categoriesIds, $childrenCategoriesIds);
			}

			return $categoriesIds;
		}

		/**
		 * Возвращает категории, дочерние переданному разделу
		 * @param int $parentId ид раздела каталога
		 * @param int $level уровень вложенности
		 * @return array
		 * @throws selectorException
		 */
		public function getChildrenCategories($parentId, $level) {
			$categories = new selector('pages');
			$categories->types('object-type')->name('catalog', 'category');
			$categories->where('hierarchy')->page($parentId)->childs($level);
			$categories->option('return')->value('id');
			$categories = $categories->result();

			if (umiCount($categories) == 0) {
				return [];
			}

			$categoriesIds = [];

			foreach ($categories as $categoryId) {
				$categoriesIds[] = $categoryId['id'];
			}

			return $categoriesIds;
		}

		/**
		 * Возвращает объект генератора запросов к индексу фильтров, либо false, если
		 * объект не удалось получить.
		 * @param iUmiHierarchyElement $category объект страницы раздела каталога, объекты которого требуется вывести
		 * @param int $level уровень вложенности раздела каталога $categoryId, на котором размещены необходимые объекты
		 *   каталога
		 * @return bool|FilterQueriesMaker
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function getCatalogQueriesMaker(iUmiHierarchyElement $category, $level) {
			/* @var iUmiHierarchyElement $category */
			$categoryId = $category->getId();
			try {
				$filterIndexSource = $this->getCategoryFilterSource($category);
			} catch (publicException $exception) {
				return false;
			}

			$filterIndexSourceLevel = (int) $filterIndexSource->getValue(self::FILTER_INDEX_NESTING_DEEP_FIELD_NAME);

			$umiTypesHelper = umiTypesHelper::getInstance();
			$hierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName('catalog', 'object');

			$indexGenerator = new FilterIndexGenerator($hierarchyTypeId, 'pages');
			$indexGenerator->setHierarchyCondition($filterIndexSource->getId(), $filterIndexSourceLevel);

			$domainId = Service::DomainDetector()->detectId();
			$langId = Service::LanguageDetector()->detectId();

			$queriesMaker = new FilterQueriesMaker($indexGenerator);
			$queriesMaker->setDomainIds([$domainId]);
			$queriesMaker->setLangIds([$langId]);

			if (!$this->isFilterVirtualCopies()) {
				$queriesMaker->ignoreVirtualCopies();
			}

			$categoriesIds = $this->getCategoriesIds($categoryId, $level);

			$queriesMaker->setParentIds($categoriesIds);
			$queriesMaker->parseFilters();

			return $queriesMaker;
		}

		/**
		 * Возвращает объект создателя запросов к индексу для форм фильтрации
		 * @param FilterIndexGenerator $indexGenerator объект генератора индекса
		 * @param array $categoriesIds идентификаторы разделов каталога, в которых лежат искомые объекты
		 * @param array $fieldsNames строковые идентификаторы поле, необходимых в форме фильтрации
		 * @return FilterQueriesMaker
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function getFilterQueriesMaker(
			FilterIndexGenerator $indexGenerator,
			array $categoriesIds,
			array $fieldsNames
		) {
			$domainId = Service::DomainDetector()->detectId();
			$langId = Service::LanguageDetector()->detectId();

			$queriesMaker = new FilterQueriesMaker($indexGenerator);
			$queriesMaker->setDomainIds([$domainId]);
			$queriesMaker->setLangIds([$langId]);

			if (!$this->isFilterVirtualCopies()) {
				$queriesMaker->ignoreVirtualCopies();
			}

			$queriesMaker->setParentIds($categoriesIds);
			$queriesMaker->setFilteredFieldsNames($fieldsNames);
			$queriesMaker->parseFilters();

			return $queriesMaker;
		}

		/**
		 * Возвращает ссылки на страницу редактирование сущности и
		 * страницу добавления дочерней сущности
		 * @param int|bool $element_id идентификатор сущности
		 * @param string|bool $element_type тип сущности
		 * @return array|bool
		 */
		public function getEditLink($element_id = false, $element_type = false) {
			$element = umiHierarchy::getInstance()->getElement($element_id);

			if (!$element instanceof iUmiHierarchyElement) {
				return false;
			}

			$prefix = $this->pre_lang;

			switch ($element_type) {
				case 'category': {
					$link_add = $prefix . "/admin/catalog/add/{$element_id}/object/";
					$link_edit = $prefix . "/admin/catalog/edit/{$element_id}/";
					return [$link_add, $link_edit];
				}
				case 'object': {
					$link_edit = $prefix . "/admin/catalog/edit/{$element_id}/";
					return [false, $link_edit];
				}
				default: {
					return false;
				}
			}
		}

		/**
		 * Определяет нужно ли включить в фильтрацию виртуальные копии
		 * @return bool
		 */
		private function isFilterVirtualCopies() {
			return (bool) Service::Registry()->get('//modules/catalog/is_filter_virtual_copies');
		}
	}
