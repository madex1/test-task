<?php
	/**
	 * Класс обновления индекса фильтров каталога по событиям.
	 * Содержит обработчики событий:
	 *  releaseFilterIndex
	 *  systemModifyElement
	 *  systemSwitchElementActivity
	 *  systemCreateElement
	 *  systemMoveElement
	 *  systemDeleteElement
	 *  systemVirtualCopyElement
	 *  systemModifyPropertyValue
	 *  systemKillElement
	 *  systemRestoreElement
	 *  cron
	 * Обработчики событий, начинающихся с префикса "system" и обработчик события "cron" можно отключить
	 * через config.ini, см. events.php модуля Каталог.
	 */
	abstract class __catalog_filter_events_handlers {
		/**
		 * Инициирует указание всем разделам, задействованным
		 * в переиндексации фильтров, раздела-источника индекса.
		 * @param iUmiEventPoint $event событие успешного завершения переиндексации фильтров
		 * @return bool
		 */
		public function setSourceIndex(iUmiEventPoint $event) {
			/* @var __catalog_filter_events_handlers|catalog|__filter_catalog $this*/
			if ($event->getMode() !== 'after') {
				return false;
			}

			if ($event->getParam('entity_type') !== 'pages') {
				return false;
			}

			$hierarchyTypeId = $this->getProductHierarchyTypeId();

			if ($hierarchyTypeId !== $event->getParam('hierarchy_type_id')) {
				return false;
			}

			$parentId = (int) $event->getParam('parent_id');
			$umiHierarchy = umiHierarchy::getInstance();
			$parent = $umiHierarchy->getElement($parentId);

			if (!$parent instanceof umiHierarchyElement) {
				return false;
			}

			$level = (int) $event->getParam('level');
			$this->setFilterSourceToCategory($parent, $parentId, $level);

			return $this->markChildrenCategories($parentId, $level-1);
		}

		/**
		 * Указывает у дочерних разделов каталога источник индекса фильтров
		 * @param int $parentId ид родительского раздела
		 * @param int $level уровень вложенности дочерних разделов
		 * @return bool
		 */
		public function markChildrenCategories($parentId, $level) {
			/* @var __catalog_filter_events_handlers|catalog|__filter_catalog $this */
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
		 * Инициирует обновление индекса фильтров при изменении контента страницы
		 * @param iUmiEventPoint $event событие изменения страницы
		 * @return bool
		 */
		public function updateIndexOnModify(iUmiEventPoint $event) {
			if ($event->getMode() !== 'after') {
				return false;
			}

			/* @var iUmiHierarchyElement $page */
			$page = $event->getRef('element');

			if (!$this->isValidPage($page)) {
				return false;
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$categoryId = $page->getRel();
			$category = $umiHierarchy->getElement($categoryId, true, true);

			if (!$category instanceof iUmiHierarchyElement) {
				return false;
			}

			if ($page->getIsActive()) {
				$this->processPage($page, $category, 'update');
			}
			return true;
		}

		/**
		 * Инициирует обновление индекса фильтров при изменении активности страницы
		 * @param iUmiEventPoint $event событие изменения активности страницы
		 * @return bool
		 */
		public function updateIndexOnSwitchActivity(iUmiEventPoint $event) {
			if ($event->getMode() !== 'after') {
				return false;
			}

			/* @var iUmiHierarchyElement $page */
			$page = $event->getRef('element');

			if (!$this->isValidPage($page)) {
				return false;
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$categoryId = $page->getRel();
			$category = $umiHierarchy->getElement($categoryId, true, true);

			if (!$category instanceof iUmiHierarchyElement) {
				return false;
			}

			if ($page->getIsActive()) {
				$this->processPage($page, $category, 'update');
			} else {
				$this->processPage($page, $category, 'delete');
			}

			return true;
		}

		/**
		 * Инициирует обновление индекса фильтров при создания страницы
		 * @param iUmiEventPoint $event событие создания страницы
		 * @return bool
		 */
		public function updateIndexOnCreate(iUmiEventPoint $event) {
			if ($event->getMode() !== 'after') {
				return false;
			}

			/* @var iUmiHierarchyElement $page */
			$page = $event->getRef('element');

			if (!$this->isValidPage($page)) {
				return false;
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$categoryId = $page->getRel();
			$category = $umiHierarchy->getElement($categoryId, true, true);

			if (!$category instanceof iUmiHierarchyElement) {
				return false;
			}

			if ($page->getIsActive()) {
				$this->processPage($page, $category, 'update');
			}
			return true;
		}

		/**
		 * Инициирует обновление индекса фильтров при перемещении страницы
		 * @param iUmiEventPoint $event событие перемещения страницы
		 * @return bool
		 */
		public function updateIndexOnMove(iUmiEventPoint $event) {
			if ($event->getMode() !== 'after') {
				return false;
			}

			/* @var iUmiHierarchyElement $page */
			$page = $event->getRef('element');

			if (!$this->isValidPage($page)) {
				return false;
			}

			$oldParentId = $event->getParam('old-parent-id');
			$newParentId = $event->getParam('parent-id');

			if ($oldParentId == $newParentId) {
				return false;
			}

			$umiHierarchy = umiHierarchy::getInstance();

			$oldParent = $umiHierarchy->getElement($oldParentId, true, true);
			if ($oldParent instanceof iUmiHierarchyElement) {
				$this->processPage($page, $oldParent, 'delete');
			}

			$newParent = $umiHierarchy->getElement($newParentId, true, true);
			if ($newParent instanceof iUmiHierarchyElement) {
				if ($page->getIsActive()) {
					$this->processPage($page, $newParent, 'update');
				}
			}

			return true;
		}

		/**
		 * Инициирует обновление индекса фильтров при удалении страницы в корзину
		 * @param iUmiEventPoint $event событие удаления страницы в корзину
		 * @return bool
		 */
		public function updateIndexOnDelete(iUmiEventPoint $event) {
			if ($event->getMode() !== 'after') {
				return false;
			}

			/* @var iUmiHierarchyElement $page */
			$page = $event->getRef('element');

			if (!$this->isValidPage($page)) {
				return false;
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$categoryId = $page->getRel();
			$category = $umiHierarchy->getElement($categoryId, true, true);

			if (!$category instanceof iUmiHierarchyElement) {
				return false;
			}

			$this->processPage($page, $category, 'delete');
			return true;
		}

		/**
		 * Инициирует обновление индекса фильтров при создании виртуальной копии страницы
		 * @param iUmiEventPoint $event событие создания виртуальной копии страницы
		 * @return bool
		 */
		public function updateIndexOnVirtualCopy(iUmiEventPoint $event) {
			if ($event->getMode() !== 'after') {
				return false;
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$pageId = $event->getParam('newElementId');
			/* @var iUmiHierarchyElement $page */
			$page = $umiHierarchy->getElement($pageId, true);

			if (!$this->isValidPage($page)) {
				return false;
			}

			$categoryId = $page->getRel();
			$category = $umiHierarchy->getElement($categoryId, true, true);

			if (!$category instanceof iUmiHierarchyElement) {
				return false;
			}

			$this->processPage($page, $category, 'update');
			return true;
		}

		/**
		 * Инициирует обновление индекса фильтров при изменения поля сущности
		 * @param iUmiEventPoint $event событие изменения поля сущности
		 * @return bool
		 */
		public function updateIndexOnModifyProperty(iUmiEventPoint $event) {
			if ($event->getMode() !== 'after') {
				return false;
			}

			/* @var iUmiHierarchyElement $entity */
			$entity = $event->getRef('entity');

			if (!$this->isValidPage($entity)) {
				return false;
			}

			$propertyName = $event->getParam('property');
			$objectId = $entity->getObjectId();
			$objectTypeId = $entity->getObjectTypeId();

			$umiPropertiesHelper = umiPropertiesHelper::getInstance();
			$property = $umiPropertiesHelper->getProperty($objectId, $propertyName, $objectTypeId);

			if (!$property instanceof iUmiObjectProperty) {
				return false;
			}

			$field = $property->getField();

			if (!$field instanceof iUmiField) {
				return false;
			}

			if (!$field->getIsInFilter()) {
				return false;
			}

			$categoryId = $entity->getRel();
			$umiHierarchy = umiHierarchy::getInstance();
			$category = $umiHierarchy->getElement($categoryId, true, true);

			if (!$category instanceof iUmiHierarchyElement) {
				return false;
			}

			if ($entity->getIsActive()) {
				$this->processPage($entity, $category, 'update');
			}
			return true;
		}

		/**
		 * Инициирует обновление индекса фильтров при полном удалении страницы
		 * @param iUmiEventPoint $event событие полного удаления страницы
		 * @return bool
		 */
		public function updateIndexOnKill(iUmiEventPoint $event) {
			if ($event->getMode() !== 'before') {
				return false;
			}

			/* @var iUmiHierarchyElement $page */
			$page = $event->getRef('element');

			if (!$this->isValidPage($page)) {
				return false;
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$categoryId = $page->getRel();
			$category = $umiHierarchy->getElement($categoryId, true, true);

			if (!$category instanceof iUmiHierarchyElement) {
				return false;
			}

			$this->processPage($page, $category, 'delete');
			return true;

		}

		/**
		 * Инициирует обновление индекса фильтров при восстановлении страницы из корзины
		 * @param iUmiEventPoint $event событие восстановления страницы из корзины
		 * @return bool
		 */
		public function updateIndexOnRestore(iUmiEventPoint $event) {
			if ($event->getMode() !== 'after') {
				return false;
			}

			/* @var iUmiHierarchyElement $page */
			$page = $event->getRef('element');

			if (!$this->isValidPage($page)) {
				return false;
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$categoryId = $page->getRel();
			$category = $umiHierarchy->getElement($categoryId, true, true);

			if (!$category instanceof iUmiHierarchyElement) {
				return false;
			}

			$this->processPage($page, $category, 'update');
			return true;
		}

		/**
		 * Содержит ли переменная страницу товара
		 * @param mixed $page переменная
		 * @return bool
		 */
		public function isValidPage($page) {
			if (!$page instanceof iUmiHierarchyElement) {
				return false;
			}

			$hierarchyTypeId = $this->getProductHierarchyTypeId();

			if ($page->getTypeId() != $hierarchyTypeId) {
				return false;
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
			/* @var iUmiHierarchyElement|umiEntinty $page */
			/* @var __catalog_filter_events_handlers|catalog|__filter_catalog $this*/
			try {
				/* @var iUmiHierarchyElement|umiEntinty $sourceCategory */
				$sourceCategory = $this->getCategoryFilterSource($category);
			} catch (publicException $exception) {
				return false;
			}

			if (!$sourceCategory instanceof iUmiHierarchyElement) {
				return false;
			}

			$hierarchyTypeId = $this->getProductHierarchyTypeId();
			$sourceHierarchyLevel = (int) $sourceCategory->getValue(__filter_catalog::FILTER_INDEX_NESTING_DEEP_FIELD_NAME);

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
		 * Инициирует переиндексацию фильтров разделов каталога
		 * @param iUmiEventPoint $event событие вызова системного крона
		 * @return bool
		 */
		public function reIndexOnCron(iUmiEventPoint $event) {
			$indexedCategoryIdList = $this->getIndexedCategoryIdList();

			if (empty($indexedCategoryIdList)) {
				return false;
			}

			$umiHierarchy = umiHierarchy::getInstance();
			/* @var array $indexedCategoryId */
			foreach ($indexedCategoryIdList as $indexedCategoryId) {
				if (!isset($indexedCategoryId['id'])) {
					continue;
				}

				$categoryId = $indexedCategoryId['id'];
				$category = $umiHierarchy->getElement($categoryId, true);

				if (!$category instanceof iUmiHierarchyElement) {
					continue;
				}

				try {
					$this->reIndexCategory($category);
				} catch (noObjectsFoundForIndexingException $e) {
					//nothing
				}

				$umiHierarchy->unloadElement($categoryId);
			}

			return true;
		}

		/**
		 * Возвращает список идентификаторов разделов каталога, нуждающихся в переиндексации фильтров
		 * @return array
		 *
		 * [
		 * 		[
		 * 			'id' => number
		 * 		]
		 * ]
		 */
		public function getIndexedCategoryIdList() {
			$query = new selector('pages');
			$query->types('object-type')->name('catalog', 'category');
			$query->where(__filter_catalog::FILTER_INDEX_INDEXATION_NEEDED)->equals(true);
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
			$query->where(__filter_catalog::FILTER_INDEX_INDEXATION_NEEDED)->equals(true);
			$query->where('domain')->isnotnull();
			$query->where('lang')->isnotnull();
			$query->option('no-length')->value(true);
			return $query->result();
		}

		/**
		 * Переиндексирует фильтры раздела каталога
		 * @param iUmiHierarchyElement $category объект раздела каталога
		 * @return bool
		 */
		public function reIndexCategory(iUmiHierarchyElement $category) {
			/* @var __catalog_filter_events_handlers|catalog|__filter_catalog $this*/
			/* @var iUmiHierarchyElement|umiEntinty $category */
			$level = (int) $category->getValue(__filter_catalog::FILTER_INDEX_NESTING_DEEP_FIELD_NAME);
			$parentId = $category->getId();
			$catalogObjectHierarchyTypeId = $this->getProductHierarchyTypeId();

			$indexGenerator = new FilterIndexGenerator($catalogObjectHierarchyTypeId, 'pages');
			$indexGenerator->setHierarchyCondition($parentId, $level);
			$indexGenerator->setLimit(__filter_catalog::CRON_FILTER_INDEXATION_LIMIT);

			for ($counter = 0; !$indexGenerator->isDone(); $counter++) {
				$indexGenerator->run();
			}

			$category->setValue(__filter_catalog::FILTER_INDEX_SOURCE_FIELD_NAME, $parentId);
			$category->setValue(__filter_catalog::FILTER_INDEX_INDEXATION_DATE, new umiDate());
			$category->setValue(__filter_catalog::FILTER_INDEX_INDEXATION_STATE, 100);
			$category->commit();

			$this->markChildrenCategories($parentId, $level - 1);
			return true;
		}
	}
