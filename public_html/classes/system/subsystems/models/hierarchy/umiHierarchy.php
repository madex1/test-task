<?php

	use UmiCms\Service;

	/**
	 * Предоставляет доступ к страницам сайта (класс umiHierarchyElement) и методы для управления структурой сайта.
	 * Синглтон, экземпляр коллекции можно получить через статический метод getInstance().
	 */
	class umiHierarchy extends singleton implements iSingleton, iUmiHierarchy {

		/**
		 * @var bool не обновлять карту сайта при сохранении страниц
		 * @see umiHierarchyElement::save()
		 */
		public static $ignoreSiteMap = false;

		/** @var array(id => umiHierarchyElement, ...) загруженные страницы */
		private $loadedElements = [];

		/** @var int[] идентификаторы измененных страниц */
		private $updatedElements = [];

		/** @var int[] идентификаторы запрошенных страниц */
		private $collectedElementIds = [];

		/** @var int максимальное значение атрибута "дата последней модификации" среди запрошенных страниц */
		private $elementsLastUpdateTime = 0;

		/**
		 * @var bool генерировать абсолютные url для страниц
		 * @see $this->forceAbsolutePath()
		 */
		private $isAbsolutePathForced = false;

		/**
		 * @var array Список пар [elementId, parentSymlinkId], для которых будет сделана виртуальная копия
		 * @see $this->addElement()
		 * @see $this->__destruct()
		 */
		private $pendingSymlinkPairs = [];

		/**
		 * @var array [pseudoHash => elementPath, ...] кэш с полными адресами страниц
		 * @see $this->getPathById()
		 */
		private $pathCache = [];

		/**
		 * @var array [elementId => elementAltName, ...] кэш с псевдостатическими адресами страниц
		 * @see $this->getPathById()
		 */
		private $altNameCache = [];

		/**
		 * @var array [
		 *   langId => [
		 *     domainId => defaultElementId,
		 *   ],
		 * ]
		 * кэш с идентификаторами страниц по умолчанию для связки langId/domainId
		 * @see $this->getDefaultElementId()
		 */
		private $defaultIdCache = [];

		/**
		 * @var array [childId => parentIds[], ...] кэш с идентификаторами всех предков для дочерних страниц
		 * @see $this->getAllParents()
		 */
		private $parentIdCache = [];

		/**
		 * @var array [pathHash => elementId|false, ...] кэш с идентификаторами страниц, вычисленных по адресу
		 * @see $this->getIdByPath()
		 */
		private $idByPathCache = [];

		/** Конструктор */
		protected function __construct() {
		}

		/**
		 * @inheritdoc
		 * @return iUmiHierarchy
		 */
		public static function getInstance($c = null) {
			return parent::getInstance(__CLASS__);
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 */
		public function isExists($id) {
			if (!is_numeric($id)) {
				return false;
			}

			$id = (int) $id;
			$query = <<<SQL
SELECT `id` FROM `cms3_hierarchy` WHERE `id` = $id LIMIT 0,1
SQL;
			return ConnectionPool::getInstance()
					->getConnection()
					->queryResult($query)
					->length() === 1;
		}

		/** @inheritdoc */
		public function isLoaded($id) {
			if ($id === false) {
				return false;
			}

			if (!is_array($id)) {
				return array_key_exists($id, $this->loadedElements);
			}

			$idList = $id;
			$isLoaded = true;

			foreach ($idList as $pageId) {
				if (!array_key_exists($pageId, $this->loadedElements)) {
					$isLoaded = false;
					break;
				}
			}

			return $isLoaded;
		}

		/** @inheritdoc */
		public function getElement($id, $ignorePermissions = false, $ignoreDeleted = false, $data = false) {
			if (!$id) {
				return false;
			}

			if ($this->isLoaded($id)) {
				return $this->loadedElements[$id];
			}

			if (!$ignorePermissions && !$this->isAllowed($id)) {
				return false;
			}

			$factory = Service::HierarchyElementFactory();

			try {
				if (is_array($data)) {
					array_unshift($data, $id);
					$element = $factory->createByData($data);
				} else {
					$element = $factory->createById($id);
				}
			} catch (privateException $e) {
				$e->unregister();
				return false;
			}

			$this->collectedElementIds[] = $id;

			if ($element->getIsDeleted() && !$ignoreDeleted) {
				return false;
			}

			$this->pushElementsLastUpdateTime($element->getUpdateTime());
			$this->loadedElements[$id] = $element;
			return $element;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws coreException
		 */
		public function getList($limit = 15, $offset = 0) {
			$escapedLimit = (int) $limit;
			$escapedOffset = (int) $offset;
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$sql = <<<SQL
SELECT
	h.id,
	h.rel,
	h.type_id,
	h.lang_id,
	h.domain_id,
	h.tpl_id,
	h.obj_id,
	h.ord,
	h.alt_name,
	h.is_active,
	h.is_visible,
	h.is_deleted,
	h.updatetime,
	h.is_default,
	o.name,
	o.type_id,
	o.is_locked,
	o.owner_id,
	o.guid,
	t.guid,
	o.updatetime,
	o.ord
FROM cms3_hierarchy h, cms3_objects o, cms3_object_types t
WHERE 
	o.id = h.obj_id
	AND o.type_id = t.id
ORDER BY h.id
LIMIT $escapedOffset, $escapedLimit;
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$elementList = [];

			if ($result->length() == 0) {
				return $elementList;
			}

			$ignorePermissions = true;
			$ignoreDeleted = true;

			foreach ($result as $row) {
				$id = array_shift($row);
				$element = $this->getElement($id, $ignorePermissions, $ignoreDeleted, $row);

				if ($element instanceof iUmiHierarchyElement) {
					$elementList[] = $element;
				}
			}

			return $elementList;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws coreException
		 */
		public function loadElements($idList) {
			$idList = array_filter((array) $idList, function ($id) {
				return is_numeric($id);
			});

			if (count($idList) == 0) {
				return [];
			}

			$idList = array_map(function ($id) {
				return (int) $id;
			}, $idList);
			$idList = array_unique($idList);

			$idList = implode(',', $idList);
			$permissionTable = '';
			$permissionCondition = '';
			$auth = Service::Auth();

			if (!$auth->isLoginAsSv()) {
				$permissionTable = ', cms3_permissions cp';
				$userId = $auth->getUserId();
				$ownerWhere = permissionsCollection::getInstance()
					->makeSqlWhere($userId);
				$permissionCondition = sprintf('AND (cp.rel_id = h.id AND cp.level & 1 AND %s)', $ownerWhere);
			}

			$sql = <<<SQL
SELECT
	h.id,
	h.rel,
	h.type_id,
	h.lang_id,
	h.domain_id,
	h.tpl_id,
	h.obj_id,
	h.ord,
	h.alt_name,
	h.is_active,
	h.is_visible,
	h.is_deleted,
	h.updatetime,
	h.is_default,
	o.name,
	o.type_id,
	o.is_locked,
	o.owner_id,
	o.guid,
	t.guid,
	o.updatetime,
	o.ord
FROM cms3_hierarchy h, cms3_objects o, cms3_object_types t $permissionTable
WHERE 
	h.id IN ($idList) 
	AND o.id = h.obj_id 
  	AND o.type_id = t.id
	$permissionCondition
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() == 0) {
				return [];
			}

			$elements = [];

			foreach ($result as $row) {
				$elementId = array_shift($row);
				$element = $this->getElement($elementId, true, false, $row);

				if ($element instanceof iUmiHierarchyElement) {
					$elements[] = $element;
				}
			}

			return $elements;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws Exception
		 */
		public function delElement($id) {
			$id = (int) $id;
			$permissions = permissionsCollection::getInstance();
			$auth = Service::Auth();

			if (!$permissions->isAllowedObject($auth->getUserId(), $id)) {
				return false;
			}

			$element = $this->getElement($id);

			if (!$element instanceof iUmiHierarchyElement) {
				return false;
			}

			if ($element->hasVirtualCopy()) {
				return $this->killElement($element->getId());
			}

			$this->addUpdatedElementId($id);
			$this->forceCacheCleanup();

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT id FROM cms3_hierarchy FORCE INDEX(rel) WHERE rel = '{$id}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				list($childId) = $row;
				$this->delElement($childId);
			}

			$event = new umiEventPoint('hierarchyDeleteElement');
			$event->setParam('element_id', $id);
			$event->setParam('user_id', $auth->getUserId());
			$event->setMode('after');
			$event->call();

			$element->setDeleted();
			$element->commit();

			unset($this->loadedElements[$id]);

			return true;
		}

		/** @inheritdoc */
		public function getOriginalPage($objectId) {
			if (!is_numeric($objectId)) {
				return false;
			}

			$normalisedObjectId = (int) $objectId;

			$query = <<<SQL
SELECT `id` FROM `cms3_hierarchy` WHERE `obj_id` = $normalisedObjectId LIMIT 0, 1
SQL;
			$queryResult = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($query)
				->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($queryResult->length() === 0) {
				return false;
			}

			$queryResultRow = $queryResult->fetch();
			$originalPageId = array_shift($queryResultRow);

			return $this->getElement($originalPageId);
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function copyElement($id, $parentId, $copyChildren = false) {
			$id = (int) $id;
			$this->collectedElementIds[] = $parentId;
			$this->collectedElementIds[] = $id;

			$this->forceCacheCleanup();

			if ($parentId !== 0 && !$this->isExists($parentId)) {
				return false;
			}

			$newElement = $this->getElement($id);

			if (!$newElement instanceof iUmiHierarchyElement) {
				return false;
			}

			$this->collectedElementIds[] = $newElement->getParentId();

			$parentId = (int) $parentId;
			$timestamp = self::getTimeStamp();
			$connection = ConnectionPool::getInstance()->getConnection();
			$newPageOrd = (int) $this->getNewPageOrd($parentId);

			$sql = <<<SQL
INSERT INTO cms3_hierarchy
(rel, type_id, lang_id, domain_id, tpl_id, obj_id, alt_name, is_active, is_visible, is_deleted, updatetime, ord)
	SELECT '{$parentId}', type_id, lang_id, domain_id, tpl_id, obj_id,
	alt_name, is_active, is_visible, is_deleted, '{$timestamp}', $newPageOrd
	FROM cms3_hierarchy WHERE id = '{$id}' LIMIT 1
SQL;
			$connection->query($sql);
			$newElementId = $connection->insertId();

			$sql = <<<SQL
INSERT INTO cms3_permissions
(level, owner_id, rel_id)
	SELECT level, owner_id, '{$newElementId}' FROM cms3_permissions WHERE rel_id = '{$id}'

SQL;
			$connection->query($sql);
			$newElement = $this->getElement($newElementId, true);

			if (!$newElement instanceof iUmiHierarchyElement) {
				return false;
			}

			$domainId = $this->getDomainIdByElementId($parentId) ?: $newElement->getDomainId();
			$newElement->setDomainId($domainId);
			$newElement->setAltName($newElement->getAltName());
			$newElement->commit();
			$this->buildRelationNewNodes($newElementId);

			if ($copyChildren) {
				$domainId = $newElement->getDomainId();
				$children = $this->getChildrenTree($id, true, true, 0, false, $domainId);

				foreach ($children as $childId => $_dummy) {
					$this->getElement($childId, true, true);
					$this->copyElement($childId, $newElementId, true);
				}
			}

			$this->collectedElementIds[] = $newElementId;
			return $newElementId;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 */
		public function createVirtualCopyOnRootLevel($originalPageId, $domainId = null, $copyChildren = false) {
			$copyId = null;
			$connection = ConnectionPool::getInstance()->getConnection();

			try {
				$message = sprintf('Create virtual copy of page with id = "%d" on zero level', $originalPageId);
				$connection->startTransaction($message);
				$parentId = 0;
				$copyId = $this->copyElement($originalPageId, $parentId, $copyChildren);
				$copy = $this->getElement($copyId);

				if (!$copy instanceof iUmiHierarchyElement) {
					throw new expectElementException('Cannot get instance of created virtual copy');
				}

				if ($domainId !== null) {
					$copy->setDomainId($domainId);
					$copy->commit();
					$this->buildRelationNewNodes($copyId);
				}

				$connection->commitTransaction();
			} catch (Exception $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			return $copy;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function cloneElement($id, $parentId, $copySubPages = false) {
			$this->collectedElementIds[] = $parentId;
			$this->collectedElementIds[] = $id;
			$this->forceCacheCleanup();

			if ($parentId !== 0 && !$this->isExists($parentId)) {
				return false;
			}

			$newElement = $this->getElement($id);

			if (!$newElement instanceof iUmiHierarchyElement) {
				return false;
			}

			$this->collectedElementIds[] = $newElement->getParentId();
			$connection = ConnectionPool::getInstance()->getConnection();

			$timestamp = (int) self::getTimeStamp();
			$objectId = $newElement->getObjectId();

			$sql = <<<SQL
INSERT INTO cms3_objects
(name, is_locked, type_id, owner_id, ord, updatetime)
	SELECT name, is_locked, type_id, owner_id, ord, $timestamp
		FROM cms3_objects
			WHERE id = '{$objectId}'
SQL;
			$connection->query($sql);
			$newObjectId = $connection->insertId();

			$schema = Service::ObjectPropertyValueTableSchema();
			$contentTable = $schema->getDefaultTable();
			$sql = <<<SQL
INSERT INTO {$contentTable}
(field_id, int_val, varchar_val, text_val, rel_val, float_val, tree_val, obj_id)
	SELECT field_id, int_val, varchar_val, text_val, rel_val, float_val, tree_val, '{$newObjectId}'
		FROM {$contentTable}
			WHERE obj_id = '{$objectId}'
SQL;
			$connection->query($sql);

			$imagesTable = $schema->getImagesTable();
			$sql = <<<SQL
INSERT INTO {$imagesTable}
(`obj_id`, `field_id`, `src`, `alt`, `ord`)
	SELECT '{$newObjectId}', `field_id`, `src`, `alt`, `ord`
		FROM {$imagesTable}
			WHERE obj_id = '{$objectId}'
SQL;
			$connection->query($sql);

			$counterTable = $schema->getCounterTable();
			$sql = <<<SQL
INSERT INTO {$counterTable}
(`obj_id`, `field_id`, `cnt`)
	SELECT '{$newObjectId}', `field_id`, `cnt`
		FROM {$counterTable}
			WHERE obj_id = '{$objectId}'
SQL;
			$connection->query($sql);

			$domainIdTable = $schema->getDomainIdTable();
			$sql = <<<SQL
INSERT INTO {$domainIdTable}
(`obj_id`, `field_id`, `domain_id`)
	SELECT '{$newObjectId}', `field_id`, `domain_id`
		FROM {$domainIdTable}
			WHERE obj_id = '{$objectId}'
SQL;
			$connection->query($sql);

			$newPageOrd = (int) $this->getNewPageOrd($parentId);

			$sql = <<<SQL
INSERT INTO cms3_hierarchy
(rel, type_id, lang_id, domain_id, tpl_id, obj_id, alt_name, is_active, is_visible, is_deleted, updatetime, ord)
	SELECT '{$parentId}', type_id, lang_id, domain_id, tpl_id, '{$newObjectId}', alt_name, is_active,
		is_visible, is_deleted, '{$timestamp}', $newPageOrd
			FROM cms3_hierarchy WHERE id = '{$id}' LIMIT 1
SQL;
			$connection->query($sql);
			$newElementId = $connection->insertId();

			$sql = <<<SQL
INSERT INTO cms3_permissions
(level, owner_id, rel_id)
	SELECT level, owner_id, '{$newElementId}' FROM cms3_permissions WHERE rel_id = '{$id}'
SQL;
			$connection->query($sql);
			$newElement = $this->getElement($newElementId, true);

			if (!$newElement instanceof iUmiHierarchyElement) {
				return false;
			}

			$domainId = $this->getDomainIdByElementId($parentId) ?: $newElement->getDomainId();
			$newElement->setDomainId($domainId);
			$newElement->setAltName($newElement->getAltName());
			$newElement->commit();

			$this->buildRelationNewNodes($newElementId);

			if ($copySubPages) {
				$domainId = $newElement->getDomainId();
				$children = $this->getChildrenTree($id, true, true, 0, false, $domainId);

				foreach ($children as $childId => $_dummy) {
					$this->cloneElement($childId, $newElementId, true);
				}
			}

			$this->collectedElementIds[] = $newElementId;
			return $newElementId;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws coreException
		 */
		public function getDeletedList(
			&$total = 0,
			$limit = 20,
			$page = 0,
			$searchName = '',
			$domainId = null,
			$languageId = null
		) {
			$limit = (int) $limit;
			$offset = (int) $page * $limit;
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$searchName = $connection->escape($searchName);
			$domainId = (int) $domainId;
			$languageId = (int) $languageId;

			$joinCondition = $searchName ? 'LEFT JOIN cms3_objects as objects ON hierarchy.obj_id = objects.id' : '';
			$searchCondition = $searchName ? "AND objects.name LIKE '%{$searchName}%'" : '';
			$domainCondition = $domainId ? "AND hierarchy.domain_id = $domainId" : '';
			$languageCondition = $domainId ? "AND hierarchy.lang_id = $languageId" : '';

			$sql = <<<SQL
				SELECT SQL_CALC_FOUND_ROWS hierarchy.id
				FROM
					cms3_hierarchy hierarchy
				{$joinCondition}
				WHERE
					hierarchy.is_deleted = '1' {$searchCondition} 
					{$domainCondition} {$languageCondition}
				AND
					hierarchy.rel NOT IN (
						SELECT 
							id
						FROM
							cms3_hierarchy
						WHERE 
							is_deleted = '1'
						)
				ORDER BY
					hierarchy.updatetime DESC
				LIMIT 
					{$offset}, {$limit}
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$deletedPageIds = [];

			foreach ($result as $row) {
				$deletedPageIds[] = array_shift($row);
			}

			$totalSql = <<<TOTALSQL
				SELECT FOUND_ROWS()
TOTALSQL;

			$totalResult = $connection->queryResult($totalSql);
			$totalResult->setFetchType(IQueryResult::FETCH_ROW);
			$totalRow = 0;

			if ($totalResult->length() > 0) {
				$fetchResult = $totalResult->fetch();
				$totalRow = (int) array_shift($fetchResult);
			}

			if ($totalRow) {
				$total = is_array($totalRow) ? (int) $totalRow[0] : $totalRow;
			}

			return $deletedPageIds;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function restoreElement($id) {
			$id = (int) $id;

			$element = $this->getElement($id, false, true);
			if (!$element instanceof iUmiHierarchyElement) {
				return false;
			}

			$event = new umiEventPoint('systemRestoreElement');
			$event->addRef('element', $element);
			$event->setParam('user_id', Service::Auth()->getUserId());
			$event->setMode('before');
			$event->call();

			$element->setDeleted(false);
			$element->setAltName($element->getAltName());
			$element->commit();

			$event->setMode('after');
			$event->call();

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT id FROM cms3_hierarchy WHERE rel = '{$id}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				list($childId) = $row;
				$this->getElement($childId, true, true);
				$this->restoreElement($childId);
			}

			return true;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws coreException
		 */
		public function killElement($parentId) {
			$ignorePermissions = true;
			$ignoreDeletedStatus = true;
			$parent = $this->getElement($parentId, $ignorePermissions, $ignoreDeletedStatus);

			if (!$parent instanceof iUmiHierarchyElement) {
				return false;
			}

			$childrenIdList = $this->getChildrenList($parentId);

			array_map(function ($childId) {
				$this->killElement($childId);
			}, $childrenIdList);

			$this->deleteElement($parent);
			return true;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function removeDeletedElement($parentId, &$removedSoFar = 0) {
			$ignorePermissions = true;
			$ignoreDeletedStatus = true;
			$parent = $this->getElement($parentId, $ignorePermissions, $ignoreDeletedStatus);

			if (!$parent instanceof iUmiHierarchyElement || !$parent->getIsDeleted()) {
				return false;
			}

			$childrenIdList = $this->getChildrenList($parentId);

			foreach ($childrenIdList as $childId) {
				$child = $this->getElement($childId, $ignorePermissions, $ignoreDeletedStatus);

				if (!$child instanceof iUmiHierarchyElement) {
					continue;
				}

				if (!$child->getIsDeleted()) {
					$child->setDeleted();
					$child->commit();
				}

				$this->removeDeletedElement($childId, $removedSoFar);
			}

			$this->deleteElement($parent);
			$removedSoFar += 1;

			return true;
		}

		/**
		 * Удаляет из корзины (и из БД) страницу $elementId со всеми дочерними
		 * страницами, за один вызов функция удаляет порцию страниц
		 * размером не более указанного, либо все если он указан как 0.
		 * @param Integer $elementId id страницы, которую будем удалять
		 * @param Integer $deleteLimit максимальное число удаляемых элементов,
		 * опциональный, необходим при удалении порциями
		 * @param Integer $deletedCount количество удаленных элементов
		 * @param Integer $depth глубина залегания удаляемого элемента относительно корневого
		 * @param array|bool $childrenTree дерево подчиненных элементов для разбора
		 * @return Boolean true в случае успеха
		 * @throws databaseException
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function killElementInParts($elementId, $deleteLimit, &$deletedCount = 0, $depth = 0, $childrenTree = false) {
			$elementId = (int) $elementId;
			$permissions = permissionsCollection::getInstance();
			$userId = Service::Auth()->getUserId();

			$element = $this->getElement($elementId, true, true);
			if (!($element && $permissions->isAllowedObject($userId, $elementId))) {
				return false;
			}

			if (!$element->getIsDeleted()) {
				$this->addUpdatedElementId($elementId);

				$event = new umiEventPoint('hierarchyDeleteElement');
				$event->setParam('element_id', $elementId);
				$event->setParam('user_id', $userId);
				$event->setMode('after');
				$event->call();

				$element->setIsDeleted(true);
				$element->commit();
			}

			$success = true;
			$childrenTree = is_array($childrenTree) ? $childrenTree : $this->getChildrenTree($elementId);

			foreach ($childrenTree as $childId => $childItems) {
				if ($deleteLimit && $deletedCount >= $deleteLimit) {
					break;
				}

				$success &= $this->killElementInParts(
					$childId,
					$deleteLimit,
					$deletedCount,
					$depth + 1,
					$childItems
				);
			}

			if ($deleteLimit && $deletedCount >= $deleteLimit) {
				$this->forceCacheCleanup();
				return $success;
			}

			if ($depth == 0) {
				$this->forceCacheCleanup();
			}

			$this->deleteElement($element);
			$deletedCount++;

			return $success;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws Exception
		 */
		public function removeDeletedAll() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->startTransaction('umiHierarchy::removeDeletedAll()');
			$sql = "SELECT id FROM cms3_hierarchy WHERE is_deleted = '1'";

			try {
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);

				foreach ($result as $row) {
					list($elementId) = $row;
					$this->removeDeletedElement($elementId);
				}
			} catch (\Exception $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			$connection->commitTransaction();
			return true;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws Exception
		 */
		public function removeDeletedWithLimit($limit = false) {
			if (!$limit) {
				$limit = 100;
			}

			$limit = (int) $limit;
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->startTransaction('umiHierarchy::removeDeletedWithLimit()');
			$sql = "SELECT id FROM cms3_hierarchy WHERE is_deleted = '1' LIMIT {$limit}";

			try {
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);

				$removedSoFar = 0;

				foreach ($result as $row) {
					list($elementId) = $row;
					$this->removeDeletedElement($elementId, $removedSoFar);
				}
			} catch (Exception $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			$connection->commitTransaction();
			return $removedSoFar;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 */
		public function getParent($id) {
			$id = (int) $id;

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT rel FROM cms3_hierarchy WHERE id = '{$id}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() == 0) {
				return false;
			}

			$fetchResult = $result->fetch();
			$parentId = (int) array_shift($fetchResult);
			$this->collectedElementIds[] = $parentId;
			return $parentId;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 */
		public function getAllParents($id, $includeSelf = false, $ignoreCache = false) {
			$id = (int) $id;
			$parents = [];

			if (!$ignoreCache && isset($this->parentIdCache[$id])) {
				$parents = $this->parentIdCache[$id];
			} else {
				$connection = ConnectionPool::getInstance()->getConnection();
				$sql = "SELECT rel_id FROM cms3_hierarchy_relations WHERE child_id = '{$id}' ORDER BY id";
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);

				foreach ($result as $row) {
					list($parentId) = $row;
					$parents[] = (int) $parentId;
				}

				$this->parentIdCache[$id] = $parents;
			}

			if ($includeSelf) {
				$parents[] = (int) $id;
			}

			return $parents;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function getChildrenTree(
			$rootPageId,
			$allowInactive = true,
			$allowInvisible = true,
			$depth = 0,
			$hierarchyTypeId = false,
			$domainId = false,
			$languageId = false
		) {

			$disallowPermissions = permissionsCollection::getInstance()
				->isSv();

			$selectExpression = 'relations.`child_id`, relations.`level`, hierarchy.`ord`, hierarchy.`rel`';
			$whereExpression = $this->getChildrenWhereCondition(
				$rootPageId,
				$hierarchyTypeId,
				$allowInactive,
				$allowInvisible,
				$domainId,
				$languageId
			);

			$maxDepth = (int) $this->getMaxDepth($rootPageId, $depth);

			if ($maxDepth > 0 && $depth > 0) {
				$whereExpression .= " AND relations.`level` <= $maxDepth ";
			}

			$result = $this->runChildrenQuery($selectExpression, $whereExpression, $disallowPermissions, true);

			if ($result->length() == 0) {
				return [];
			}

			$rows = [];
			foreach ($result as $row) {
				$rows[$row['child_id']] = $row;
			}

			$childrenTree = [];
			$tempContainer = [];

			foreach ($rows as $row) {
				$pageId = $row['child_id'];
				$parentIds = $row['rel'];
				$tempContainer[$pageId] = [];

				if ($parentIds == $rootPageId) {
					$childrenTree[$pageId] = &$tempContainer[$pageId];
				}
				if (isset($tempContainer[$parentIds])) {
					$tempContainer[$parentIds][$pageId] = &$tempContainer[$pageId];
				}
			}

			return $childrenTree;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws publicAdminException
		 */
		public function getMaxDepth($rootPageId, $maxDepth) {
			if (!is_numeric($rootPageId)) {
				throw new publicAdminException('Incorrect page id given');
			}

			$maxDepth = (int) $maxDepth;
			$maxDepth = ($maxDepth === 0) ? 1 : $maxDepth;

			$rootPageId = (int) $rootPageId;

			if ($rootPageId == 0) {
				return $maxDepth;
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT `level` FROM `cms3_hierarchy_relations` WHERE `child_id` = $rootPageId;
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ARRAY);

			if ($result->length() == 0) {
				$level = 0;
			} else {
				$row = $result->fetch();
				$level = isset($row['level']) ? (int) $row['level'] : 0;
			}

			return $level + $maxDepth;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws coreException
		 */
		public function getChildrenList(
			$rootPageId,
			$allowInactive = true,
			$allowInvisible = true,
			$hierarchyTypeId = false,
			$domainId = false,
			$includeSelf = false,
			$languageId = false
		) {

			$disallowPermissions = permissionsCollection::getInstance()
				->isSv();

			$selectExpression = 'relations.`child_id`, relations.`level`, hierarchy.`ord`';
			$whereExpression = $this->getChildrenWhereCondition(
				$rootPageId,
				$hierarchyTypeId,
				$allowInactive,
				$allowInvisible,
				$domainId,
				$languageId
			);

			$result = $this->runChildrenQuery($selectExpression, $whereExpression, $disallowPermissions, true);

			if ($result->length() == 0) {
				return [];
			}

			$rows = [];

			foreach ($result as $row) {
				$rows[$row['child_id']] = $row;
			}

			$pageIds = [];

			foreach ($rows as $row) {
				$pageIds[] = $row['child_id'];
			}

			if ($includeSelf) {
				array_unshift($pageIds, $rootPageId);
			}

			return $pageIds;
		}

		/** @inheritdoc */
		public function sortByHierarchy(array $pageIds) {
			usort($pageIds, function (array $first, array $second) {
				$firstItemLevel = $first['level'];
				$firstItemOrd = $first['ord'];
				$secondItemLevel = $second['level'];
				$secondItemOrd = $second['ord'];

				switch (true) {
					case ($firstItemLevel == $secondItemLevel) && ($firstItemOrd == $secondItemOrd): {
						return 0;
					}
					case ($firstItemLevel == $secondItemLevel) && ($firstItemOrd > $secondItemOrd): {
						return 1;
					}
					case ($firstItemLevel > $secondItemLevel): {
						return 1;
					}
					default: {
						return -1;
					}
				}
			});

			return $pageIds;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function getChildrenCount(
			$rootPageId,
			$allowInactive = true,
			$allowInvisible = true,
			$depth = 0,
			$hierarchyTypeId = false,
			$domainId = false,
			$languageId = false,
			$allowPermissions = false
		) {

			$selectExpression = 'count(relations.`child_id`) as count';
			$whereExpression = $this->getChildrenWhereCondition(
				$rootPageId,
				$hierarchyTypeId,
				$allowInactive,
				$allowInvisible,
				$domainId,
				$languageId
			);

			$maxDepth = (int) $this->getMaxDepth($rootPageId, $depth);

			if ($maxDepth > 0 && $depth > 0) {
				$whereExpression .= " AND relations.`level` <= $maxDepth ";
			}

			$result = $this->runChildrenQuery($selectExpression, $whereExpression, !$allowPermissions);

			if ($result->length() == 0) {
				return false;
			}

			$row = $result->fetch();
			return isset($row['count']) ? (int) $row['count'] : 0;
		}

		/** @inheritdoc */
		public function forceAbsolutePath($isForced = true) {
			$oldForced = $this->isAbsolutePathForced;
			$this->isAbsolutePathForced = (bool) $isForced;
			return $oldForced;
		}

		/**
		 * @inheritdoc
		 * TODO: рефакторинг, разбить на части
		 * @throws databaseException
		 * @throws coreException
		 */
		public function getPathById(
			$elementId,
			$ignoreLang = false,
			$ignoreIsDefaultStatus = false,
			$ignoreCache = false,
			$ignoreUrlSuffix = false
		) {
			$elementId = (int) $elementId;

			$cachePath = $elementId . '#' . (int) $ignoreLang . (int) $ignoreIsDefaultStatus .
				(int) $ignoreUrlSuffix . '#' . (int) $this->isAbsolutePathForced;

			if (!$ignoreCache && isset($this->pathCache[$cachePath])) {
				return $this->pathCache[$cachePath];
			}

			$cmsController = cmsController::getInstance();
			$urlPrefix = $cmsController->getUrlPrefix();
			$element = $this->getElement($elementId, true);

			if (!$element) {
				return $this->pathCache[$cachePath] = '';
			}

			$currentDomain = Service::DomainDetector()->detect();
			$elementDomainId = $element->getDomainId();

			if (!$this->isAbsolutePathForced && $currentDomain->getId() == $elementDomainId) {
				$domainStr = '';
			} else {
				$domain = Service::DomainCollection()
					->getDomain($elementDomainId);
				$domainStr = $domain->getUrl();
			}

			$elementLangId = (int) $element->getLangId();
			$elementLang = Service::LanguageCollection()
				->getLang($elementLangId);
			$isLangDefault = ($elementLangId === (int) $currentDomain->getDefaultLangId());
			$langStr = (!$elementLang || $isLangDefault || $ignoreLang) ? '' : '/' . $elementLang->getPrefix();

			if ($element->getIsDefault() && !$ignoreIsDefaultStatus) {
				return $this->pathCache[$cachePath] = $domainStr . $langStr . $urlPrefix . '/';
			}

			if (!$parents = $this->getAllParents($elementId, false, $ignoreCache)) {
				return $this->pathCache[$cachePath] = false;
			}

			$path = $domainStr . $langStr . $urlPrefix;
			$parents[] = $elementId;
			$toLoad = [];

			foreach ($parents as $parentId) {
				if ($parentId == 0) {
					continue;
				}
				if (!$ignoreCache && isset($this->altNameCache[$parentId])) {
					continue;
				}
				if ($this->isLoaded($parentId) && $parent = $this->getElement($parentId, true)) {
					$this->altNameCache[$parentId] = $parent->getAltName();
				} else {
					$toLoad[] = $parentId;
				}
			}

			if (umiCount($toLoad)) {
				$sql = 'SELECT id, alt_name FROM cms3_hierarchy WHERE id IN (' . implode(', ', $toLoad) . ')';
				$connection = ConnectionPool::getInstance()->getConnection();
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);

				$altNames = [];

				foreach ($result as $row) {
					list($id, $altName) = $row;
					$altNames[$id] = $altName;
					$this->altNameCache[$id] = $altName;
				}
			}

			$parentCount = umiCount($parents);

			for ($i = 0; $i < $parentCount; $i++) {
				if (!$parents[$i]) {
					continue;
				}

				if (isset($this->altNameCache[$parents[$i]])) {
					$path .= '/' . $this->altNameCache[$parents[$i]];
				}
			}

			$umiConfig = mainConfiguration::getInstance();

			if ($umiConfig->get('seo', 'url-suffix.add') && !$ignoreUrlSuffix) {
				$path .= $umiConfig->get('seo', 'url-suffix');
			}

			return $this->pathCache[$cachePath] = $path;
		}

		/**
		 * @inheritdoc
		 * TODO: рефакторинг, разбить на части
		 * @throws databaseException
		 * @throws coreException
		 */
		public function getIdByPath(
			$elementPath,
			$showDisabled = false,
			&$errorsCount = 0,
			$domainId = false,
			$langId = false
		) {
			$urlSuffix = mainConfiguration::getInstance()
				->get('seo', 'url-suffix');

			if ($urlSuffix) {
				$pos = mb_strrpos($elementPath, $urlSuffix);
				if ($pos && ($pos + mb_strlen($urlSuffix) == mb_strlen($elementPath))) {
					$elementPath = mb_substr($elementPath, 0, $pos);
				}
			}

			$langId = (int) $langId;
			$langId = $langId ?: Service::LanguageDetector()->detectId();
			$domainId = (int) $domainId;
			$domainId = $domainId ?: Service::DomainDetector()->detectId();

			$elementPath = trim($elementPath, "\/ \n");
			$elementHash = md5($domainId . ':' . $langId . ':' . $elementPath);

			if (isset($this->idByPathCache[$elementHash])) {
				return $this->idByPathCache[$elementHash];
			}

			if ($elementPath == '') {
				return $this->idByPathCache[$elementHash] = $this->getDefaultElementId($langId, $domainId);
			}

			$paths = explode('/', $elementPath);
			$pathCount = umiCount($paths);
			$id = 0;
			$connection = ConnectionPool::getInstance()->getConnection();
			$umiDomains = Service::DomainCollection();

			for ($i = 0; $i < $pathCount; $i++) {
				$altName = $paths[$i];
				$altName = $connection->escape($altName);

				if ($i == 0) {
					$elementDomainId = $umiDomains->getDomainId($altName);

					if ($elementDomainId) {
						$domainId = $elementDomainId;
						continue;
					}
				}

				$activeCondition = $showDisabled ? '' : "AND is_active = '1' AND is_deleted = '0'";
				$sql = <<<SQL
SELECT id FROM cms3_hierarchy
WHERE rel = '{$id}' AND alt_name = '{$altName}' {$activeCondition}
AND lang_id = '{$langId}' AND domain_id = '{$domainId}'
SQL;

				$result = $connection->queryResult($sql);

				if ($result->length()) {
					$fetchResult = $result->fetch();
					if (!$id = array_shift($fetchResult)) {
						return $this->idByPathCache[$elementHash] = false;
					}
				} else {
					$sql = <<<SQL
SELECT id, alt_name FROM cms3_hierarchy
WHERE rel = '{$id}' {$activeCondition} AND lang_id = '{$langId}' AND domain_id = '{$domainId}'
SQL;
					$result = $connection->queryResult($sql);
					$result->setFetchType(IQueryResult::FETCH_ROW);

					$max = 0;
					$currentId = 0;

					foreach ($result as $row) {
						list($nextId, $nextAltName) = $row;

						if ($this->isAutoCorrectionDisabled()) {
							if ($altName == $nextAltName) {
								$currentId = $nextId;
							}
						} else {
							$similarity = self::compareStrings($altName, $nextAltName);
							if ($similarity > $max) {
								$max = $similarity;
								$currentId = $nextId;
								$errorsCount += 1;
							}
						}
					}

					if ($max > 75) {
						$id = $currentId;
					} else {
						return $this->idByPathCache[$elementHash] = false;
					}
				}
			}

			return $this->idByPathCache[$elementHash] = $id;
		}

		/**
		 * @inheritdoc
		 * TODO: рефакторинг, разбить на части
		 * @throws Exception
		 * @throws coreException
		 */
		public function addElement(
			$parentId,
			$hierarchyTypeId,
			$name,
			$altName,
			$objectTypeId = false,
			$domainId = false,
			$langId = false,
			$templateId = false
		) {

			$parentId = (int) $parentId;
			$domainId = (int) $domainId;
			$langId = (int) $langId;
			$templateId = (int) $templateId;
			$hierarchyType = null;
			$umiHierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$umiObjectTypes = umiObjectTypesCollection::getInstance();

			if ($objectTypeId === false) {
				$hierarchyType = $umiHierarchyTypes->getType($hierarchyTypeId);

				if ($hierarchyType instanceof iUmiHierarchyType) {
					$objectTypeId = $umiObjectTypes->getTypeIdByHierarchyTypeName(
						$hierarchyType->getName(),
						$hierarchyType->getExt()
					);

					if (!$objectTypeId) {
						throw new coreException("There is no base object type for hierarchy type #{$hierarchyTypeId}");
					}
				}
			} else {
				$objectType = $umiObjectTypes->getType($objectTypeId);

				if (!$objectType) {
					throw new coreException('Wrong object type id given');
				}

				$hierarchyTypeId = $objectType->getHierarchyTypeId();
				$hierarchyType = $umiHierarchyTypes->getType($hierarchyTypeId);
			}

			if (!$hierarchyType instanceof iUmiHierarchyType) {
				throw new coreException('Cannot detect hierarchy type');
			}

			$parent = null;

			if (!$domainId) {
				if ($parentId == 0) {
					$domainId = Service::DomainDetector()->detectId();
				} else {
					$parent = $this->getElement($parentId, true, true);
					$domainId = $parent->getDomainId();
				}
			}

			if (!$langId) {
				if ($parentId == 0) {
					$langId = Service::LanguageDetector()->detectId();
				} else {
					if (!$parent) {
						$parent = $this->getElement($parentId, true, true);
					}
					$langId = $parent->getLangId();
				}
			}

			if (!$templateId) {
				$umiTemplates = templatesCollection::getInstance();

				$templateId = $umiTemplates->getHierarchyTypeTemplate(
					$hierarchyType->getName(),
					$hierarchyType->getExt()
				);

				if ($templateId === false) {
					$templateId = $this->getDominantTplId($parentId);

					if (!$templateId) {
						$template = $umiTemplates->getDefaultTemplate($domainId, $langId);

						if (!$template instanceof iTemplate) {
							throw new coreException('Failed to detect default template');
						}
						$templateId = $template->getId();
					}
				}
			}

			if ($parentId) {
				$this->addUpdatedElementId($parentId);
			} else {
				$this->addUpdatedElementId($this->getDefaultElementId());
			}

			$connection = ConnectionPool::getInstance()
				->getConnection();
			$connection->startTransaction(sprintf('Create hierarchy element with type %d', $hierarchyType->getId()));

			try {
				$objectId = umiObjectsCollection::getInstance()
					->addObject($name, $objectTypeId);

				if (!$objectId) {
					throw new coreException('Failed to create new object for hierarchy element');
				}

				$sql = <<<SQL
INSERT INTO cms3_hierarchy (rel, type_id, domain_id, lang_id, tpl_id, obj_id) VALUES(
	'{$parentId}', '{$hierarchyTypeId}', '{$domainId}', '{$langId}', '{$templateId}', '{$objectId}'
)
SQL;
				$connection->query($sql);

				$element = Service::HierarchyElementFactory()
					->createById($connection->insertId());
				$element->setAltName($altName);
				$element->setOrd(
					$this->getNewPageOrd($parentId)
				);
				$element->commit();

				$this->buildRelationNewNodes($element->getId());
			} catch (Exception $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			$connection->commitTransaction();

			$this->loadedElements[$element->getId()] = $element;
			$this->addUpdatedElementId($parentId);
			$this->addUpdatedElementId($element->getId());

			if ($parentId) {
				$parentElement = $this->getElement($parentId);

				if ($parentElement instanceof iUmiHierarchyElement) {
					$symlinks = $this->getObjectInstances($parentElement->getObject()->getId());

					if (umiCount($symlinks) > 1) {
						foreach ($symlinks as $symlinkId) {
							if ($symlinkId == $parentId) {
								continue;
							}
							$this->pendingSymlinkPairs[] = [$element->getId(), $symlinkId];
						}
					}
				}
			}

			$this->collectedElementIds[] = $element->getId();
			return $element->getId();
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function getDefaultElement($langId = null, $domainId = null) {
			$id = $this->getDefaultElementId($langId, $domainId);
			return $this->getElement($id);
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function getDefaultElementId($langId = false, $domainId = false) {
			$langId = (int) $langId;
			$langId = $langId ?: Service::LanguageDetector()->detectId();
			$domainId = (int) $domainId;
			$domainId = $domainId ?: Service::DomainDetector()->detectId();

			if (isset($this->defaultIdCache[$langId][$domainId])) {
				return $this->defaultIdCache[$langId][$domainId];
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT id FROM cms3_hierarchy
WHERE is_default = '1' AND is_deleted='0' AND is_active='1' AND lang_id = '{$langId}' AND domain_id = '{$domainId}'
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() == 0) {
				return false;
			}

			$fetchResult = $result->fetch();
			$elementId = (int) array_shift($fetchResult);
			return $this->defaultIdCache[$langId][$domainId] = $elementId;
		}

		/** @inheritdoc */
		public function isPathAbsolute() {
			return $this->isAbsolutePathForced;
		}

		/** @inheritdoc */
		public static function compareStrings($first, $second) {
			$averageLength = (mb_strlen($first) + mb_strlen($second)) / 2;
			$similarity = similar_text($first, $second) / $averageLength;
			return 100 * $similarity;
		}

		/** @inheritdoc */
		public static function convertAltName($altName, $separator = false) {
			$config = mainConfiguration::getInstance();

			if (!$separator) {
				$separator = $config->get('seo', 'alt-name-separator');
				$separator = $separator ?: '_';
			}

			$altName = translit::convert($altName, $separator);
			$altName = preg_replace("/[\?\\\\&=]+/", '_', $altName);
			$altName = preg_replace("/[_\/]+/", '_', $altName);

			return $altName;
		}

		/** @inheritdoc */
		public static function getTimeStamp() {
			return time();
		}

		/**
		 * @inheritdoc
		 * TODO: рефакторинг, разбить на части
		 * @throws databaseException
		 * @throws selectorException
		 */
		public function moveBefore($id, $parentId, $previousElementId = false) {
			$element = $this->getElement($id);

			if (!$element instanceof iUmiHierarchyElement) {
				return false;
			}

			$id = (int) $id;
			$parentId = (int) $parentId;
			$langId = $element->getLangId();
			$domainId = $element->getDomainId();

			try {
				$element->setRel($parentId);
			} catch (coreException $e) {
				return false;
			}

			$currentTemplateId = $element->getTplId();
			$umiTemplates = templatesCollection::getInstance();
			$availableTemplates = $umiTemplates->getTemplatesList($domainId, $langId);
			$templateWillChange = true;

			/** @var iTemplate $template */
			foreach ($availableTemplates as $template) {
				if ($template->getId() == $currentTemplateId) {
					$templateWillChange = false;
					break;
				}
			}

			$connection = ConnectionPool::getInstance()->getConnection();

			if ($templateWillChange) {
				$defaultTemplate = $umiTemplates->getDefaultTemplate($domainId, $langId);

				if ($defaultTemplate) {
					$defaultTemplateId = $defaultTemplate->getId();

					$sel = new selector('pages');
					$sel->where('hierarchy')->page($id)->level(100);
					$sel->option('return')->value('id');
					$result = $sel->result();

					$descendantIds = array_map(function ($info) {
						return $info['id'];
					}, $result);
					$descendantIds[] = $id;

					$idCondition = implode(',', $descendantIds);
					$sql = "UPDATE cms3_hierarchy SET tpl_id = '{$defaultTemplateId}' WHERE id IN ({$idCondition})";
					$connection->query($sql);
				}
			}

			if ($previousElementId) {
				$previousElementId = (int) $previousElementId;
				$sql = "SELECT ord FROM cms3_hierarchy WHERE id = '{$previousElementId}'";
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);

				if ($result->length() == 0) {
					return false;
				}

				$fetchResult = $result->fetch();
				$ord = (int) array_shift($fetchResult);
				$sql = <<<SQL
UPDATE cms3_hierarchy
SET ord = (ord + 1)
WHERE rel = '{$parentId}' AND lang_id = '{$langId}' AND domain_id = '{$domainId}' AND ord >= {$ord}
SQL;
				$connection->query($sql);

				$element->setOrd($ord);
				$this->rewriteElementAltName($id);
				$this->rebuildRelationNodes($id);
				$this->addUpdatedElementId($id);
				$element->commit();
				return true;
			}

			$sql = <<<SQL
SELECT MAX(ord)
FROM cms3_hierarchy
WHERE rel = '{$parentId}' AND lang_id = '{$langId}' AND domain_id = '{$domainId}'
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$ord = 1;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$ord = 1 + (int) array_shift($fetchResult);
			}

			$element->setOrd($ord);
			$this->rewriteElementAltName($id);
			$this->rebuildRelationNodes($id);
			$this->addUpdatedElementId($id);
			$element->commit();

			return true;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws selectorException
		 */
		public function moveFirst($id, $parentId) {
			$id = (int) $id;
			$parentId = (int) $parentId;

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT id FROM cms3_hierarchy WHERE rel = '{$parentId}' ORDER BY ord ASC";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$previousElementId = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$previousElementId = (int) array_shift($fetchResult);
			}

			return $this->moveBefore($id, $parentId, $previousElementId);
		}

		/** @inheritdoc */
		public function isAllowed($id) {
			if (!is_numeric($id)) {
				return false;
			}
			$permissions = permissionsCollection::getInstance();
			$readStatus = $permissions->isAllowedObject(Service::Auth()->getUserId(), $id);
			return $readStatus[0];
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws coreException
		 */
		public function getDominantTypeId($id, $depth = 1, $hierarchyTypeId = null, $excludeHierarchyTypeIds = []) {
			if ($id === 0) {
				$langId = Service::LanguageDetector()->detectId();
				$domainId = Service::DomainDetector()->detectId();
			} else {
				$id = (int) $id;
				$element = $this->getElement($id, true);

				if (!$element instanceof iUmiHierarchyElement) {
					return false;
				}

				$langId = $element->getLangId();
				$domainId = $element->getDomainId();
			}

			$depth = (int) $depth;
			$hierarchyTypeId = (int) $hierarchyTypeId;
			$typeCondition = $hierarchyTypeId ? "AND h.type_id = '{$hierarchyTypeId}'" : '';

			if (!empty($excludeHierarchyTypeIds)) {
				$excludeHierarchyTypeIds = array_map(
					function ($typeId) {
						return (int) $typeId;
					}
				);

				$typeCondition .= " AND h.type_id NOT IN ('" .
					join("','", $excludeHierarchyTypeIds) . "')";
			}

			if ($depth > 1) {
				$sql = <<<SQL
SELECT o.type_id, COUNT(*) AS c
FROM cms3_hierarchy h, cms3_objects o, cms3_hierarchy_relations hr
WHERE hr.rel_id = '{$id}' AND h.id = hr.child_id AND h.is_deleted = '0'
	AND o.id = h.obj_id AND h.lang_id = '{$langId}' AND h.domain_id = '{$domainId}'
	{$typeCondition}
GROUP BY o.type_id
ORDER BY c DESC
LIMIT 1
SQL;
			} else {
				$sql = <<<SQL
SELECT o.type_id, COUNT(*) AS c
FROM cms3_hierarchy h, cms3_objects o
WHERE h.rel = '{$id}' AND h.is_deleted = '0' AND o.id = h.obj_id
	AND h.lang_id = '{$langId}' AND h.domain_id = '{$domainId}'
	{$typeCondition}
GROUP BY o.type_id
ORDER BY c DESC
LIMIT 1
SQL;
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() == 0) {
				return null;
			}

			$fetchResult = $result->fetch();
			return (int) array_shift($fetchResult);
		}

		/** @inheritdoc */
		public function addUpdatedElementId($id) {
			if (!in_array($id, $this->updatedElements)) {
				$this->updatedElements[] = $id;
			}
		}

		/** @inheritdoc */
		public function getUpdatedElements() {
			return $this->updatedElements;
		}

		/** Очистить кэш измененных страниц */
		protected function forceCacheCleanup() {
			$updatedPageIdList = $this->getUpdatedElements();

			if (count($updatedPageIdList) > 0) {
				Service::StaticCache()
					->deletePageListCache($updatedPageIdList);
			}
		}

		/**
		 * Деструктор
		 * @throws databaseException
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function __destruct() {
			if (defined('SMU_PROCESS') && SMU_PROCESS) {
				return;
			}

			$this->forceCacheCleanup();

			if (umiCount($this->pendingSymlinkPairs)) {
				foreach ($this->pendingSymlinkPairs as $pair) {
					list($elementId, $symlinkId) = $pair;
					$this->copyElement($elementId, $symlinkId);
				}
				$this->pendingSymlinkPairs = [];
			}
		}

		/** @inheritdoc */
		public function getCollectedElements() {
			return array_merge(array_keys($this->loadedElements), $this->collectedElementIds);
		}

		/** @inheritdoc */
		public function unloadElement($id) {
			static $currentElementId;

			if ($currentElementId === null) {
				$currentElementId = cmsController::getInstance()->getCurrentElementId();
			}

			if ($currentElementId == $id) {
				return false;
			}

			if (array_key_exists($id, $this->loadedElements)) {
				unset($this->loadedElements[$id]);
			} else {
				return false;
			}
		}

		/** @inheritdoc */
		public function unloadAllElements() {
			static $currentElementId;

			if ($currentElementId === null) {
				$currentElementId = cmsController::getInstance()->getCurrentElementId();
			}

			foreach ($this->loadedElements as $elementId => $_dummy) {
				if ($currentElementId == $elementId) {
					continue;
				}
				unset($this->loadedElements[$elementId]);
			}
		}

		/**
		 * Добавить время последней модификации страницы максимальное для текущей сессии
		 * @param int $updateTime = 0 время в формате UNIX TIMESTAMP
		 */
		private function pushElementsLastUpdateTime($updateTime = 0) {
			if ($updateTime > $this->elementsLastUpdateTime) {
				$this->elementsLastUpdateTime = $updateTime;
			}
		}

		/** @inheritdoc */
		public function getElementsLastUpdateTime() {
			return $this->elementsLastUpdateTime;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 */
		public function getMaxNestingLevel($id) {
			$id = (int) $id;

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT max(level) FROM `cms3_hierarchy_relations` WHERE rel_id = '{$id}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$level = false;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$level = (int) array_shift($fetchResult);
			}

			return $level;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws coreException
		 */
		public function getObjectInstances(
			$objectId,
			$ignoreDomain = false,
			$ignoreLang = false,
			$ignoreDeleted = false
		) {
			$objectId = (int) $objectId;
			$sql = "SELECT id FROM cms3_hierarchy WHERE obj_id = '{$objectId}'";

			if (!$ignoreDomain) {
				$domainId = Service::DomainDetector()->detectId();
				$sql .= " AND domain_id = '{$domainId}'";
			}

			if (!$ignoreLang) {
				$langId = Service::LanguageDetector()->detectId();
				$sql .= " AND lang_id = '{$langId}'";
			}

			if ($ignoreDeleted) {
				$sql .= ' AND is_deleted = 0';
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$instanceIds = [];

			foreach ($result as $row) {
				$instanceIds[] = array_shift($row);
			}

			return $instanceIds;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 */
		public function getDominantTplId($parentId) {
			$parentId = (int) $parentId;
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT `tpl_id`, COUNT(*) AS `cnt`
FROM cms3_hierarchy
WHERE rel = '{$parentId}' AND is_deleted = '0' GROUP BY tpl_id ORDER BY `cnt` DESC
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				return (int) array_shift($fetchResult);
			}

			$element = $this->getElement($parentId);

			if ($element instanceof iUmiHierarchyElement) {
				return $element->getTplId();
			}

			return false;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 */
		public function getLastUpdatedElements($limit, $timestamp = 0) {
			$limit = (int) $limit;
			$timestamp = (int) $timestamp;

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT id FROM cms3_hierarchy WHERE updatetime >= {$timestamp} LIMIT {$limit}";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$ids = [];

			foreach ($result as $row) {
				$ids[] = array_shift($row);
			}

			return $ids;
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 */
		public function checkIsVirtual($elements, $includeDeleted = false) {
			if (umiCount($elements) == 0) {
				return $elements;
			}

			foreach ($elements as $elementId => $_dummy) {
				$elementId = (int) $elementId;
				$element = $this->getElement($elementId);
				$elements[$elementId] = (string) $element->getObjectId();
			}

			$deletedCondition = $includeDeleted ? '' : "AND is_deleted = '0'";
			$objectsCondition = implode(', ', $elements);

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT obj_id, COUNT(*) FROM cms3_hierarchy
WHERE obj_id IN ({$objectsCondition}) {$deletedCondition}
GROUP BY obj_id
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				list($objectId, $count) = $row;
				$isVirtual = $count > 1;

				foreach ($elements as $elementId => $originalObjectId) {
					if ($originalObjectId === $objectId) {
						$elements[$elementId] = $isVirtual;
					}
				}
			}

			return $elements;
		}

		/**
		 * Перепроверить псевдостатический URL страницы $elementId на предмет коллизий
		 * @param int $elementId идентификатор страницы
		 * @return bool
		 */
		protected function rewriteElementAltName($elementId) {
			$element = $this->getElement($elementId, true, true);

			if (!$element instanceof iUmiHierarchyElement) {
				return false;
			}

			$element->setAltName($element->getAltName());
			$element->commit();
			return true;
		}

		/**
		 * Стереть все записи, связанные со страницой $elementId из таблицы cms3_hierarchy_relations
		 * @param int $elementId идентификатор страницы
		 * @throws databaseException
		 */
		protected function eraseRelationNodes($elementId) {
			$elementId = (int) $elementId;
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "DELETE FROM cms3_hierarchy_relations WHERE rel_id = '{$elementId}' OR child_id = '{$elementId}'";
			$connection->query($sql);
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 */
		public function rebuildRelationNodes($id) {
			$id = (int) $id;

			$this->eraseRelationNodes($id);
			$this->buildRelationNewNodes($id);
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT id FROM cms3_hierarchy WHERE rel = '{$id}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				$this->rebuildRelationNodes(array_shift($row));
			}
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 */
		public function buildRelationNewNodes($id) {
			$id = (int) $id;
			$this->eraseRelationNodes($id);
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT rel FROM cms3_hierarchy WHERE id = '{$id}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() == 0) {
				return false;
			}

			$fetchResult = $result->fetch();
			$parentId = array_shift($fetchResult);
			$parentIdCondition = ($parentId > 0) ? " = '{$parentId}'" : ' IS NULL';

			$sql = <<<SQL
INSERT INTO cms3_hierarchy_relations (rel_id, child_id, level)
	SELECT rel_id, '{$id}', (level + 1)
	FROM cms3_hierarchy_relations
	WHERE child_id {$parentIdCondition}
SQL;
			$connection->query($sql);
			$parents = $this->getAllParents($parentId, true, true);

			$parents = array_extract_values($parents);
			$level = umiCount($parents);
			$parentIdValue = ($parentId > 0) ? "'{$parentId}'" : 'NULL';

			$sql = <<<SQL
INSERT INTO cms3_hierarchy_relations (rel_id, child_id, level)
VALUES ({$parentIdValue}, '{$id}', '{$level}')
SQL;
			$connection->query($sql);
			return true;
		}

		/** @inheritdoc */
		public function hasParent($child, $parent) {
			if (!$child) {
				return false;
			}

			if (is_numeric($child)) {
				$child = $this->getElement($child);
			}

			if (is_numeric($parent)) {
				$parent = $this->getElement($parent);
			}

			if (!$child instanceof iUmiHierarchyElement) {
				return false;
			}

			if (!$parent instanceof iUmiHierarchyElement) {
				return false;
			}

			if ($child->getParentId() == $parent->getId()) {
				return true;
			}

			return $this->hasParent($child->getParentId(), $parent);
		}

		/** @inheritdoc */
		public function clearCache() {
			$this->loadedElements = [];
			$this->pendingSymlinkPairs = [];
			$this->collectedElementIds = [];
			$this->pathCache = [];
			$this->altNameCache = [];
			$this->defaultIdCache = [];
			$this->parentIdCache = [];
			$this->idByPathCache = [];
		}

		/** @inheritdoc */
		public function clearDefaultElementCache() {
			$this->defaultIdCache = [];
		}

		/**
		 * @inheritdoc
		 * @throws databaseException
		 * @throws coreException
		 */
		public function getRightAltName(
			$altName,
			$element,
			$denseNumbering = false,
			$ignoreCurrentElement = false
		) {
			if (empty($altName)) {
				$altName = '1';
			}

			if ($element->getParentId() == 0 && !IGNORE_MODULE_NAMES_OVERWRITE) {
				$moduleNames = Service::Registry()
					->getList('//modules');

				foreach ($moduleNames as $moduleName) {
					if ($altName == $moduleName[0]) {
						$altName .= '1';
						break;
					}
				}

				if (Service::LanguageCollection()->getLangId($altName)) {
					$altName .= '1';
				}
			}

			$altString = $this->getAltString($altName);
			$altDigit = $this->getAltDigit($altName);

			if ($this->isAddElementIdInAltName()) {
				$isEqualAltExist = $this->isPageWithEqualAltNameExist(
					$element,
					$altName,
					$ignoreCurrentElement
				);

				return $isEqualAltExist
					? $altName . chooseSeparator() . $element->getId()
					: $altName;
			}

			$existingAltNames = $this->getExistingAltNameList($element, $altString, $ignoreCurrentElement);

			if (in_array($altName, $existingAltNames)) {
				foreach ($existingAltNames as $nextAltName) {
					$nextAltDigit = $this->getAltDigit($nextAltName);

					if ($nextAltDigit) {
						$altDigit = max($altDigit, $nextAltDigit);
					}
				}

				$altDigit += 1;

				if ($denseNumbering) {
					for ($nextDigit = 1; $nextDigit < $altDigit; $nextDigit += 1) {
						if (!in_array($altString . $nextDigit, $existingAltNames)) {
							$altDigit = $nextDigit;
							break;
						}
					}
				}
			}

			return $altDigit > 0 ? $altString . $altDigit : $altString;
		}

		/**
		 * Возвращает список псевдостатических адресов похожих или идентичных переданному alt-name
		 * @param iUmiHierarchyElement $element страница
		 * @param string $altString литеральная часть псевдостатического адреса
		 * @param bool $ignoreCurrentElement не учитывать адрес страницы $element как конфликт
		 * @return array
		 * @throws coreException
		 * @throws databaseException
		 */
		private function getExistingAltNameList(
			iUmiHierarchyElement $element,
			$altString,
			$ignoreCurrentElement = false
		) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$altString = $connection->escape($altString);

			$sql = $this->getAltNameSelectSqlQuery($element, $ignoreCurrentElement)
				. " AND alt_name LIKE '{$altString}%';";

			return $this->prepareAltNameList($sql, $connection);
		}

		/**
		 * Определяет существует ли страница с одинаковым псевдостатическим адресом
		 * @param iUmiHierarchyElement $element страница
		 * @param string $altName псевдостатический адрес
		 * @param bool $ignoreCurrentElement не учитывать адрес страницы $element как конфликт
		 * @return bool
		 * @throws databaseException
		 */
		private function isPageWithEqualAltNameExist(
			iUmiHierarchyElement $element,
			$altName,
			$ignoreCurrentElement = false
		) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$altName = $connection->escape($altName);

			$sql = $this->getAltNameSelectSqlQuery($element, $ignoreCurrentElement)
				. " AND alt_name = '{$altName}' LIMIT 0,1;";

			$existingAltNameList = $this->prepareAltNameList($sql, $connection);

			return isset($existingAltNameList[0]);
		}

		/**
		 * Возвращает массив с результатами выборки из базы данных
		 * @param string $query sql-запрос
		 * @param IConnection $connection соединение с БД
		 * @return array
		 * @throws databaseException
		 */
		private function prepareAltNameList($query, IConnection $connection) {
			$result = $connection->queryResult($query);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);
			$existingAltNameList = [];

			foreach ($result as $row) {
				$existingAltNameList[] = array_shift($row);
			}

			return $existingAltNameList;
		}

		/**
		 * Возвращает идентификатор домена или false
		 * @param int $id идентификатор элемента
		 * @return false|int
		 */
		private function getDomainIdByElementId($id) {
			$element = $this->getElement($id);
			return ($element instanceof iUmiHierarchyElement) ? $element->getDomainId() : false;
		}

		/**
		 * Возвращает часть sql запроса для выборки псевдостатических адресов
		 * @param iUmiHierarchyElement $element страница
		 * @param bool $ignoreCurrentElement не учитывать адрес страницы $element как конфликт
		 * @return string
		 * @throws databaseException
		 */
		private function getAltNameSelectSqlQuery(iUmiHierarchyElement $element, $ignoreCurrentElement = false) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$parentId = $connection->escape($element->getParentId());
			$elementId = $connection->escape($element->getId());
			$langId = $connection->escape($element->getLangId());
			$domainId = $connection->escape($element->getDomainId());

			$idCondition = $ignoreCurrentElement ? '' : "AND id <> {$elementId}";

			return <<<SQL
SELECT alt_name
FROM cms3_hierarchy
WHERE rel = {$parentId} {$idCondition} AND is_deleted = '0' AND lang_id = '{$langId}'
	AND domain_id = '{$domainId}'
SQL;
		}

		/**
		 * Возвращает литеральную часть псевдостатического адреса
		 * @param $altName
		 * @return string|null
		 */
		private function getAltString($altName) {
			$matches = $this->parseAltName($altName);
			return isset($matches[1]) ? $matches[1] : null;
		}

		/**
		 * Возвращает числовую часть псевдостатического адреса
		 * @param $altName
		 * @return int
		 */
		private function getAltDigit($altName) {
			$matches = $this->parseAltName($altName);
			return isset($matches[2]) ? (int) $matches[2] : 0;
		}

		/**
		 * Возвращает массив с литеральной и числовой частью
		 * псевдостатического адреса
		 * @param string $altName
		 * @return array
		 */
		private function parseAltName($altName) {
			preg_match("/^([a-z0-9_.-]*)(\d*?)$/U", $altName, $matches);

			return $matches;
		}

		/**
		 * Возвращает максимальное значений порядка вывода среди страниц, дочерних родительской
		 * @param int $parentId идентификатор родителькой страницы
		 * @return int
		 * @throws databaseException
		 */
		protected function getMaxOrdAmongChildren($parentId) {
			$parentId = (int) $parentId;
			$sql = "SELECT MAX(ord) FROM cms3_hierarchy WHERE rel = $parentId";

			$connection = ConnectionPool::getInstance()
				->getConnection();
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				return (int) array_shift($fetchResult);
			}

			return 0;
		}

		/**
		 * Возвращает порядок вывода для новой страницы
		 * @param int $parentId идентификатор страницы, родительской для новой
		 * @return int
		 * @throws databaseException
		 */
		protected function getNewPageOrd($parentId) {
			return (int) $this->getMaxOrdAmongChildren($parentId) + self::INCREMENT_NEW_PAGE_ORDER;
		}

		/**
		 * Проверяет установлена ли настройка
		 * "Добавлять идентификатор страницы к alt-name дублирующей страницы"
		 * @return bool
		 * @throws coreException
		 */
		private function isAddElementIdInAltName() {
			$domainId = Service::DomainDetector()->detectId();
			$languageId = Service::LanguageDetector()->detectId();

			return Service::Registry()->get("//settings/seo/$domainId/$languageId/add-id-to-alt-name");
		}

		/**
		 * Собрать запрос на получение дочерних элементов, выполнить запрос и вернуть результат
		 * @param string $selectCondition что выбирается из базы данных
		 * @param string $whereCondition условия выборки
		 * @param bool $disallowPermissions не учитывать права дочерних элементов в выборке
		 * @param bool $useHierarchySort использовать сортировку по иерархии
		 * @return IQueryResult
		 * @throws databaseException
		 */
		private function runChildrenQuery(
			$selectCondition,
			$whereCondition,
			$disallowPermissions,
			$useHierarchySort = false
		) {
			if ($disallowPermissions) {
				$permissionsJoin = '';
				$permissionsCondition = '';
			} else {
				$umiPermissions = permissionsCollection::getInstance();
				$userId = Service::Auth()->getUserId();
				$permissionsJoin = 'LEFT JOIN `cms3_permissions` as cp ON cp.`rel_id` = hierarchy.`id`';
				$permissionsCondition = 'AND ' . $umiPermissions->makeSqlWhere($userId) . ' AND cp.`level`&1 = 1';
			}

			$orderCondition = $useHierarchySort ? 'ORDER BY relations.`level`, hierarchy.`ord`' : '';
			$connection = ConnectionPool::getInstance()->getConnection();
			$getChildrenIds = <<<SQL
SELECT
$selectCondition
FROM `cms3_hierarchy_relations` as relations
LEFT JOIN `cms3_hierarchy` as hierarchy ON relations.`child_id` = hierarchy.`id`
$permissionsJoin
WHERE
$whereCondition
AND hierarchy.`is_deleted` = 0
$permissionsCondition
$orderCondition
SQL;
			$result = $connection->queryResult($getChildrenIds);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			return $result;
		}

		/**
		 * Собрать и вернуть условие на получение дочерних элементов
		 * @param int $rootPageId идентификатор родительской страницы
		 * @param int $hierarchyTypeId идентификатор иерархического типа данных,
		 * к которому должны принадлежать дочерние страницы
		 * @param bool $allowInactive включить в результат неактивные дочерние страницы
		 * @param bool $allowInvisible включить в результат невидимые в меню дочерние страницы
		 * @param int $domainId включить в результат только страницы из этого домена (работает если ищем от корня)
		 * @param int $languageId включить в результат только страницы из этого языка (работает если ищем от корня)
		 * @return string
		 * @throws coreException
		 */
		private function getChildrenWhereCondition(
			$rootPageId,
			$hierarchyTypeId,
			$allowInactive,
			$allowInvisible,
			$domainId,
			$languageId
		) {
			$rootPageId = (int) $rootPageId;
			$relationCondition = ($rootPageId === 0)
				? ' relations.`rel_id` IS NULL '
				: " relations.`rel_id` = {$rootPageId}";

			$hierarchyTypeId = (int) $hierarchyTypeId;
			$hierarchyTypeCondition = ($hierarchyTypeId === 0) ? '' : " AND hierarchy.`type_id` = {$hierarchyTypeId} ";

			$activeCondition = $allowInactive ? '' : ' AND hierarchy.`is_active` = 1 ';
			$visibleCondition = $allowInvisible ? '' : ' AND hierarchy.`is_visible` = 1 ';

			$languageId = (!is_numeric($languageId)) ? (int) Service::LanguageDetector()->detectId() : (int) $languageId;
			$languageCondition = ($rootPageId !== 0) ? '' : " AND hierarchy.`lang_id` = {$languageId} ";

			$domainId = (!is_numeric($domainId)) ? (int) Service::DomainDetector()->detectId() : (int) $domainId;
			$domainCondition = ($rootPageId !== 0) ? '' : " AND hierarchy.`domain_id` = {$domainId} ";

			return ($relationCondition . $hierarchyTypeCondition . $activeCondition .
				$visibleCondition . $languageCondition . $domainCondition);
		}

		/**
		 * Удаляет страницу из системы.
		 * Если страница не имеет виртуальных копий - так же будет
		 * вызвано удаления объекта, который является источником данных для страницы
		 * @param iUmiHierarchyElement $element удаляемая страница
		 * @return $this
		 * @throws coreException|databaseException|Exception
		 */
		private function deleteElement(iUmiHierarchyElement $element) {
			$element->setIsUpdated(false);
			$userId = Service::Auth()->getUserId();

			$event = new umiEventPoint('systemKillElement');
			$event->addRef('element', $element);
			$event->setParam('user_id', $userId);
			$event->setMode('before');
			$event->call();

			$elementHasVirtualCopies = $element->hasVirtualCopy();
			$elementId = (int) $element->getId();
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$connection->startTransaction(sprintf('Delete hierarchy element with id %d', $elementId));

			try {
				$sql = "DELETE FROM cms3_hierarchy WHERE id = $elementId";
				$connection->query($sql);

				if (!$elementHasVirtualCopies) {
					$objectId = (int) $element->getObjectId();
					umiObjectsCollection::getInstance()
						->delObject($objectId);
				}

				backupModel::getInstance()
					->deletePageChanges($elementId);

			} catch (Exception $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			$connection->commitTransaction();

			$event->setMode('after');
			$event->call();

			unset($element);
			$this->unloadElement($elementId);

			return $this;
		}

		/**
		 * Определяет отключена ли автокоррекция url
		 * @return bool
		 */
		private function isAutoCorrectionDisabled() {
			return (bool) Service::Registry()
				->get('//settings/disable_url_autocorrection');
		}

		/** @deprecated */
		public function getChilds(
			$element_id,
			$allow_unactive = true,
			$allow_unvisible = true,
			$depth = 0,
			$hierarchy_type_id = false,
			$domainId = false,
			$langId = false
		) {
			return $this->getChildrenTree(
				$element_id,
				$allow_unactive,
				$allow_unvisible,
				$depth,
				$hierarchy_type_id,
				$domainId,
				$langId
			);
		}

		/** @deprecated */
		public function getChildIds(
			$element_id,
			$allow_unactive = true,
			$allow_unvisible = true,
			$hierarchy_type_id = false,
			$domainId = false,
			$include_self = false,
			$langId = false
		) {
			return $this->getChildrenList(
				$element_id,
				$allow_unactive,
				$allow_unvisible,
				$hierarchy_type_id,
				$domainId,
				$include_self,
				$langId
			);
		}

		/** @deprecated */
		public function getChildsCount(
			$element_id,
			$allow_unactive = true,
			$allow_unvisible = true,
			$depth = 0,
			$hierarchy_type_id = false,
			$domainId = false
		) {
			return $this->getChildrenCount(
				$element_id,
				$allow_unactive,
				$allow_unvisible,
				$depth,
				$hierarchy_type_id,
				$domainId
			);
		}

		/** @deprecated */
		public function getElementsCount($module, $method = '') {
			$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()
				->getTypeByName($module, $method)
				->getId();

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT COUNT(`id`) FROM cms3_hierarchy WHERE type_id = '{$hierarchy_type_id}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$count = false;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$count = (int) array_shift($fetchResult);
			}

			return $count;
		}

		/** @deprecated */
		public function getCurrentLanguageId() {
			return Service::LanguageDetector()->detectId();
		}

		/** @deprecated */
		public function getCurrentDomainId() {
			return Service::DomainDetector()->detectId();
		}
	}
