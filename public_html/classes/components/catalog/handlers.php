<?php

	/** Класс, содержащий обработчики событий */
	class CatalogHandlers {

		/** @var catalog $module */
		public $module;

		/**
		 * Обработчик события завершения индексации.
		 * Инициирует указание всем разделам, задействованным
		 * в переиндексации фильтров, раздела-источника индекса.
		 * @param iUmiEventPoint $event событие успешного завершения переиндексации фильтров
		 * @return bool
		 */
		public function setSourceIndex(iUmiEventPoint $event) {
			if ($event->getMode() !== 'after') {
				return false;
			}

			if ($event->getParam('entity_type') !== 'pages') {
				return false;
			}

			$hierarchyTypeId = $this->module->getProductHierarchyTypeId();

			if ($hierarchyTypeId !== $event->getParam('hierarchy_type_id')) {
				return false;
			}

			$parentId = (int) $event->getParam('parent_id');
			$umiHierarchy = umiHierarchy::getInstance();
			$parent = $umiHierarchy->getElement($parentId);

			if (!$parent instanceof iUmiHierarchyElement) {
				return false;
			}

			$level = (int) $event->getParam('level');
			$this->module->setFilterSourceToCategory($parent, $parentId, $level);

			return $this->module->markChildrenCategories($parentId, $level - 1);
		}

		/**
		 * Обработчик события изменения страницы через форму редактирования
		 * в административной панели.
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

			if (!$this->module->isCatalogObject($page)) {
				return false;
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$categoryId = $page->getParentId();
			$category = $umiHierarchy->getElement($categoryId, true, true);

			if (!$category instanceof iUmiHierarchyElement) {
				return false;
			}

			if ($page->getIsActive()) {
				$this->module->processPage($page, $category, 'update');
			}
			return true;
		}

		/**
		 * Обработчик изменения активности страницы
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

			if (!$this->module->isCatalogObject($page)) {
				return false;
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$categoryId = $page->getParentId();
			$category = $umiHierarchy->getElement($categoryId, true, true);

			if (!$category instanceof iUmiHierarchyElement) {
				return false;
			}

			if ($page->getIsActive()) {
				$this->module->processPage($page, $category, 'update');
			} else {
				$this->module->processPage($page, $category, 'delete');
			}

			return true;
		}

		/**
		 * Обработчик события создания страницы через форму редактирования
		 * в административной панели.
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

			if (!$this->module->isCatalogObject($page)) {
				return false;
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$categoryId = $page->getParentId();
			$category = $umiHierarchy->getElement($categoryId, true, true);

			if (!$category instanceof iUmiHierarchyElement) {
				return false;
			}

			if ($page->getIsActive()) {
				$this->module->processPage($page, $category, 'update');
			}
			return true;
		}

		/**
		 * Обработчик событие изменения иерархии страницы.
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

			if (!$this->module->isCatalogObject($page)) {
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
				$this->module->processPage($page, $oldParent, 'delete');
			}

			$newParent = $umiHierarchy->getElement($newParentId, true, true);

			if ($newParent instanceof iUmiHierarchyElement && $page->getIsActive()) {
				$this->module->processPage($page, $newParent, 'update');
			}

			return true;
		}

		/**
		 * Обработчик помещения страницы в корзину.
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

			if (!$this->module->isCatalogObject($page)) {
				return false;
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$categoryId = $page->getParentId();
			$category = $umiHierarchy->getElement($categoryId, true, true);

			if (!$category instanceof iUmiHierarchyElement) {
				return false;
			}

			$this->module->processPage($page, $category, 'delete');
			return true;
		}

		/**
		 * Обработчик создания виртуальной копии страницы.
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

			if (!$this->module->isCatalogObject($page)) {
				return false;
			}

			$categoryId = $page->getParentId();
			$category = $umiHierarchy->getElement($categoryId, true, true);

			if (!$category instanceof iUmiHierarchyElement) {
				return false;
			}

			$this->module->processPage($page, $category, 'update');
			return true;
		}

		/**
		 * Обработчик изменения значение поля сущности через быстрое редактирование
		 * в табличном контроле.
		 * Инициирует обновление индекса фильтров при изменения поля сущности
		 * @param iUmiEventPoint $event событие изменения поля сущности через быстрое редактирование
		 * @return bool
		 */
		public function updateIndexOnModifyPropertyByFastEdit(iUmiEventPoint $event) {
			if ($event->getMode() !== 'after') {
				return false;
			}

			/* @var iUmiHierarchyElement $entity */
			$entity = $event->getRef('entity');

			if (!$this->module->isCatalogObject($entity)) {
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

			$categoryId = $entity->getParentId();
			$umiHierarchy = umiHierarchy::getInstance();
			$category = $umiHierarchy->getElement($categoryId, true, true);

			if (!$category instanceof iUmiHierarchyElement) {
				return false;
			}

			if ($entity->getIsActive()) {
				$this->module->processPage($entity, $category, 'update');
			}

			return true;
		}

		/**
		 * Обработчик изменения значение поля сущности через eip.
		 * Инициирует обновление индекса фильтров при изменения поля сущности
		 * @param iUmiEventPoint $event событие изменения поля сущности через eip
		 * @return bool
		 * @throws publicException
		 */
		public function updateIndexOnModifyPropertyByEIP(iUmiEventPoint $event) {
			if ($event->getMode() !== 'after') {
				return false;
			}

			/* @var iUmiHierarchyElement $entity */
			$entity = $event->getRef('obj');

			if (!$this->module->isCatalogObject($entity)) {
				return false;
			}

			$propertyName = $event->getParam('field_name');
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

			$categoryId = $entity->getParentId();
			$umiHierarchy = umiHierarchy::getInstance();
			$category = $umiHierarchy->getElement($categoryId, true, true);

			if (!$category instanceof iUmiHierarchyElement) {
				return false;
			}

			if ($entity->getIsActive()) {
				$this->module->processPage($entity, $category, 'update');
			}

			return true;
		}

		/**
		 * Обработчик окончательного удаления страницы.
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

			if (!$this->module->isCatalogObject($page)) {
				return false;
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$categoryId = $page->getParentId();
			$category = $umiHierarchy->getElement($categoryId, true, true);

			if (!$category instanceof iUmiHierarchyElement) {
				return false;
			}

			$this->module->processPage($page, $category, 'delete');
			return true;
		}

		/**
		 * Обработчик восстановления страницы из корзины.
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

			if (!$this->module->isCatalogObject($page)) {
				return false;
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$categoryId = $page->getParentId();
			$category = $umiHierarchy->getElement($categoryId, true, true);

			if (!$category instanceof iUmiHierarchyElement) {
				return false;
			}

			$this->module->processPage($page, $category, 'update');
			return true;
		}

		/**
		 * Обработчик запуска системного крона.
		 * Инициирует переиндексацию фильтров разделов каталога
		 * @param iUmiEventPoint $event событие вызова системного крона
		 * @return bool
		 */
		public function reIndexOnCron(iUmiEventPoint $event) {
			$indexedCategoryIdList = $this->module->getIndexedCategoryIdList();

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
					$this->module->reIndexCategory($category);
				} catch (noObjectsFoundForIndexingException $e) {
					//nothing
				}

				$umiHierarchy->unloadElement($categoryId);
			}

			return true;
		}
	}

