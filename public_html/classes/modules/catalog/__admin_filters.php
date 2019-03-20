<?php
	/** Класс для функционала вкладки "Фильтры" модуля "Каталог" */
	abstract class AdminFilters extends baseModuleAdmin {
		/** @var int $limit сколько товаро индексировать за одну итерацию */
		protected static $limit;
		/** @var string $filterIndexGroup guid группы полей разделов каталога, содержащей служебные поля для индексации */
		protected static $filterIndexGroup;
		/** @var bool $isAdvancedModeEnabled разрешено ли указание уровня вложенности при индексации */
		protected static $isAdvancedModeEnabled;

		/** Производит инициализацию параметров индексации */
		public function onImplement() {
			$config = mainConfiguration::getInstance();
			$limit = (int) ($config->get('modules', 'catalog.index.limit'));
			$advancedMode = (boolean) $config->get('modules', 'catalog.index.advanced-mode');

			self::$limit = $limit > 0 ? $limit : 5;
			self::$filterIndexGroup = 'filter_index';
			self::$isAdvancedModeEnabled = $advancedMode;
		}

		/**
		 * Возвращает данные для вывода индексов
		 * @throws coreException
		 * @throws selectorException
		 */
		public function filters() {
			$this->setDataType("list");
			$this->setActionType("view");
			
			$requestedLimit = (int) getRequest('per_page_limit');
			$defaultLimit = 10;
			$limit = $requestedLimit > 0 ? $requestedLimit : $defaultLimit;
			
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$categories = new selector('pages');
			$categories->types('object-type')->name('catalog', 'category');
			$categories->where(__filter_catalog::FILTER_INDEX_INDEXATION_NEEDED)->equals(true);

			$categories->limit($offset, $limit);
			selectorHelper::detectOrderFilters($categories);

			$data = $this->prepareData($categories->result(), "pages");
			$this->setData($data, $categories->result());
			$this->setDataRangeByPerPage($limit, $curr_page);

			return $this->doData();
		}

		/**
		 * Индексирует раздел каталога
		 * @param bool|int $parentId ID индексируемого раздела
		 * @param bool|int $level уровень глубины индексации
		 * @throws publicAdminException
		 */
		public function indexPosition($parentId = false, $level = false) {
			$parentId = ($parentId === false) ? getRequest('param0') : $parentId;
			$level = ($level === false) ? getRequest('param1') : $level;

			if (is_null($parentId) || is_null($level)) {
				throw new publicAdminException(__METHOD__ . ': Parent ID and nesting level are required params');
			}
			/* @var AdminFilters|catalog $this */
			$indexGenerator = new FilterIndexGenerator($this->getProductHierarchyTypeId(), 'pages');
			$indexGenerator->setHierarchyCondition($parentId, $level);
			$indexGenerator->setLimit(self::$limit);

			$counter = 0;
			$error = '';
			$originalError = '';
			try {
				$counter = $indexGenerator->run();
			} catch (Exception $e) {
				$error = getLabel('indexing-uncaught-error');

				if ($e instanceof maxKeysCountExceedingException) {
					$error = getLabel('indexing-impossible-to-create-error');
				}
				if ($e instanceof noObjectsFoundForIndexingException) {
					$error = getLabel('indexing-items-not-found-error');
				}

				$originalError = $e->getMessage();
			}

			$data = array(
				'index' =>
					array(
						'indexed' => $counter,
						'isDone' => $indexGenerator->isDone(),
						'error' => $error,
						'originalError' => $originalError
					)
			);

			$this->setData($data);
			return $this->doData();
		}

		/** Возвращает настройки индексации */
		public function getSettings() {
			$data = array(
				'settings' => array(
					'limit' => self::$limit,
					'group' => self::$filterIndexGroup,
					'advancedMode' => self::$isAdvancedModeEnabled
				)
			);

			$this->setData($data);
			return $this->doData();
		}

		/**
		 * Перезаписывает значение в поле, которое принадлежит группе,
		 * содержащей поля, относящиеся к индексации self::getIndexGroup()
		 * @param bool|int $categoryId ID раздела
		 * @param bool|string $fieldName имя (идентификатор) поля
		 * @param mixed $value новое значение
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function setValueForIndexField($categoryId = false, $fieldName = false, $value = false) {
			$categoryId = ($categoryId === false) ? getRequest('param0') : $categoryId;
			$fieldName = ($fieldName === false) ? getRequest('param1') : $fieldName;
			$value = ($value === false) ? getRequest('param2') : $value;
			/* @var iUmiFieldsGroup $filterIndexGroup */
			$filterIndexGroup = $this->getIndexGroup();

			$hasField = false;
			/* @var iUmiField $field */
			foreach ($filterIndexGroup->getFields() as $field) {
				if ($field->getName() === $fieldName) {
					$hasField = true;
					break;
				}
			}

			if (!$hasField) {
				throw new publicAdminException(__METHOD__ . ': Group "' . self::$filterIndexGroup .
											   '" has no field "' . $fieldName . '"');
			}

			$element = umiHierarchy::getInstance()->getElement($categoryId);
			$this->checkElement($element);
			/* @var iUmiHierarchyElement|umiEntinty $element */
			$element->setValue($fieldName, $value);
			$element->commit();

			$data = array(
				'success' => true
			);

			$this->setData($data);
			return $this->doData();
			
		}

		/**
		 * Удаляет индексы
		 * @param bool|array $elements массив с ID элементов
		 * @throws publicAdminException
		 */
		public function deleteIndex($elements = false) {
			if (!$elements) {
				$elements = getRequest('elements');
			}

			if (!is_array($elements)) {
				$elements = array($elements);
			}

			foreach ($elements as $pageId) {
				$this->cleanGroupAllFields($pageId);
				/* @var AdminFilters|catalog $this */
				$indexGenerator = new FilterIndexGenerator($this->getProductHierarchyTypeId(), 'pages');
				$indexGenerator->setHierarchyCondition($pageId);
				$indexGenerator->dropTable();
				$indexGenerator->dropTable(true);
				$indexGenerator->deleteStoredOffset();
				$indexGenerator->deleteSavedFilteredFields();
				$indexGenerator = null;
			}

			$data = array(
				'success' => true
			);

			$this->setData($data);
			return $this->doData();
		}

		/**
		 * Очищает все значения полей группы, поля которой
		 * относятся к индексации self::getIndexGroup()
		 * @param bool|int $categoryId ID раздела каталога
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function cleanGroupAllFields($categoryId = false) {
			$categoryId = ($categoryId === false) ? getRequest('param0') : $categoryId;
			/* @var iUmiFieldsGroup $filterIndexGroup */
			$filterIndexGroup = $this->getIndexGroup();
			/* @var iUmiHierarchyElement|umiEntinty $element */
			$element = umiHierarchy::getInstance()->getElement($categoryId);
			$this->checkElement($element);
			/* @var iUmiField $field */
			foreach ($filterIndexGroup->getFields() as $field) {
				$element->setValue($field->getName(), '');
			}
			$element->commit();

			$data = array(
				'success' => true
			);

			$this->setData($data);
			return $this->doData();
		}

		/**
		 * Возвращает группу (с именем self::$filterIndexGroup),
		 * поля которой относятся к индексации
		 * @throws coreException
		 * @throws publicAdminException
		 * @return bool|umiFieldsGroup
		 */
		public function getIndexGroup() {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$categoryBaseTypeId = $objectTypes->getTypeIdByHierarchyTypeName('catalog', 'category');
			/* @var iUmiObjectType $objectTypes */
			$categoryBaseType = $objectTypes->getType($categoryBaseTypeId);
			$filterIndexGroup = $categoryBaseType->getFieldsGroupByName(self::$filterIndexGroup);

			/* @var iUmiFieldsGroup $filterIndexGroup */
			if (!$filterIndexGroup instanceof iUmiFieldsGroup) {
				throw new publicAdminException(__METHOD__ . ': Fields group '.
					self::$filterIndexGroup . ' with index data is not found');
			}

			return $filterIndexGroup;
		}

		/**
		 * Проверяет является ли элемент разделом каталога
		 * @param iUmiHierarchyElement $element
		 * @throws coreException
		 */
		public function checkElement($element) {

			if (!$element instanceof iUmiHierarchyElement) {
				throw new coreException(__METHOD__ . ': element is not instance of umiHierarchyElement');
			}
			/* @var umiHierarchyType $hierarchyType */
			$hierarchyType = $element->getHierarchyType();

			if ($hierarchyType->getModule() !== 'catalog' || $hierarchyType->getMethod() !== 'category') {
				throw new coreException(__METHOD__ . ': element is not catalog category');
			}
		}
	}
