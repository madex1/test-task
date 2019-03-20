<?php

	use UmiCms\Service;

	/**
	 * Управляет правами доступа на страницы и ресурсы модулей.
	 * Синглтон. Экземпляр класса можно получить через статический метод getInstance.
	 */
	class permissionsCollection extends singleton implements iSingleton, iPermissionsCollection {

		protected $methodsPermissions = [], $tempElementPermissions = [];

		protected $elementsCache = [];

		const E_READ_ALLOWED = 0;

		const E_EDIT_ALLOWED = 1;

		const E_CREATE_ALLOWED = 2;

		const E_DELETE_ALLOWED = 3;

		const E_MOVE_ALLOWED = 4;

		const E_READ_ALLOWED_BIT = 1;

		const E_EDIT_ALLOWED_BIT = 2;

		const E_CREATE_ALLOWED_BIT = 4;

		const E_DELETE_ALLOWED_BIT = 8;

		const E_MOVE_ALLOWED_BIT = 16;

		/**
		 * @var array права на модули, загружаемые из файлов permissions.*.php
		 * [
		 *     <moduleName> => []
		 * ]
		 */
		private $modulePermissions = [];

		/** Алгоритм хеширования пароля SHA256 */
		const SHA256 = 0;

		/** Алгоритм хеширования пароля md5 */
		const MD5 = 1;

		/** Соль для хеширования пароля */
		const HASH_SALT = 'o95j43hiwjrthpoiwj45ihwpriobneop;jfgp3408ghqpqh5gpqoi4hgp9q85h';

		/** Конструктор */
		public function __construct() {
		}

		/**
		 * @inheritdoc
		 * @return iPermissionsCollection
		 */
		public static function getInstance($c = null) {
			return parent::getInstance(__CLASS__);
		}

		/** @inheritdoc */
		public function getOwnerType($ownerId) {
			static $cache = [];

			$ownerId = (int) $ownerId;

			if (isset($cache[$ownerId])) {
				return $cache[$ownerId];
			}

			$userTypeId = umiTypesHelper::getInstance()->getObjectTypeIdByGuid('users-user');

			$groups = umiPropertiesHelper::getInstance()->getProperty(
				$ownerId,
				'groups',
				$userTypeId
			);

			if ($groups instanceof iUmiObjectProperty) {
				$cache[$ownerId] = $groups->getValue();
				return $cache[$ownerId];
			}

			return $ownerId;
		}

		/** @inheritdoc */
		public function makeSqlWhere($ownerId, $ignoreSelf = false) {
			static $cache = [];

			if (isset($cache[$ownerId])) {
				return $cache[$ownerId];
			}

			$owner = $this->getOwnerType($ownerId);

			if (is_numeric($owner)) {
				$owner = [];
			}

			if ($ownerId) {
				$owner[] = $ownerId;
			}

			$owner[] = Service::SystemUsersPermissions()->getGuestUserId();
			$owner = array_unique($owner);

			if (umiCount($owner) > 2) {
				foreach ($owner as $i => $id) {
					if ($id == $ownerId && $ignoreSelf) {
						unset($owner[$i]);
					}
				}
				$owner = array_unique($owner);
				sort($owner);
			}

			$sql = '';
			$sz = umiCount($owner);

			for ($i = 0; $i < $sz; $i++) {
				$sql .= "cp.owner_id = '{$owner[$i]}'";
				if ($i < ($sz - 1)) {
					$sql .= ' OR ';
				}
			}

			return $cache[$ownerId] = "({$sql})";
		}

		/** @inheritdoc */
		public function isAllowedModule($ownerId, $module) {
			static $cache = [];

			if (!$ownerId) {
				$ownerId = Service::Auth()->getUserId();
			}

			if ($this->isSv($ownerId)) {
				return true;
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$module = $connection->escape($module);

			if (mb_substr($module, 0, 7) == 'macros_') {
				return false;
			}

			if (isset($cache[$ownerId][$module])) {
				return $cache[$ownerId][$module];
			}

			$sqlWhere = $this->makeSqlWhere($ownerId);
			$sql = "SELECT module, MAX(cp.allow) FROM cms_permissions cp WHERE method IS NULL AND {$sqlWhere} GROUP BY module";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				list($m, $allow) = $row;
				$cache[$ownerId][$m] = $allow;
			}

			return isset($cache[$ownerId][$module]) ? (bool) $cache[$ownerId][$module] : false;
		}

		/** @inheritdoc */
		public function isAllowedMethod($ownerId, $module, $method, $ignoreSelf = false) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$module = $connection->escape($module);
			$ownerId = (int) $ownerId;
			$method = (string) $method;

			if ($module == 'content' && $method === '') {
				return 1;
			}

			if ($module == 'eshop' && $method == 'makeRealDivide') {
				return 1;
			}

			if ($this->isAdmin($ownerId) && $this->isAdminAllowedMethod($module, $method)) {
				return 1;
			}

			if ($this->isSv($ownerId)) {
				return true;
			}

			if (!$module) {
				return false;
			}

			$method = $this->getBaseMethodName($module, $method);

			if ($module == 'autoupdate' && $method == 'service') {
				return true;
			}

			if ($module == 'users' && ($method == 'auth' || $method == 'login_do' || $method == 'login')) {
				return true;
			}

			$methodsPermissions = &$this->methodsPermissions;

			if (!isset($methodsPermissions[$ownerId]) || !is_array($methodsPermissions[$ownerId])) {
				$methodsPermissions[$ownerId] = [];
			}

			$cache = &$methodsPermissions[$ownerId];
			$sqlWhere = $this->makeSqlWhere($ownerId, $ignoreSelf);
			$cacheKey = $module;

			if (!array_key_exists($cacheKey, $cache)) {
				$sql = <<<SQL
SELECT cp.method, MAX(cp.allow) FROM cms_permissions cp
WHERE module = '{$module}' AND {$sqlWhere} GROUP BY module, method
SQL;
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);

				$cache[$module] = [];

				foreach ($result as $row) {
					list($cmethod) = $row;
					$cache[$cacheKey][] = $cmethod;
				}
			}

			return in_array($method, $cache[$cacheKey]) || in_array(mb_strtolower($method), $cache[$cacheKey]);
		}

		protected function isAdminAllowedMethod($module, $method) {
			$methods = [
				'content' => [
					'json_mini_browser',
					'old_json_load_files',
					'json_load_files',
					'json_load_zip_folder',
					'load_tree_node',
					'get_editable_region',
					'save_editable_region',
					'widget_create',
					'widget_delete',
					'getObjectsByTypeList',
					'getObjectsByBaseTypeList',
					'json_get_images_panel',
					'json_create_imanager_object',
					'domainTemplates',
					'json_unlock_page',
					'tree_unlock_page'
				],
				'backup' => [
					'backup_panel',
					'rollback'
				],
				'data' => [
					'guide_items',
					'guide_items_all',
					'json_load_hierarchy_level'
				],
				'users' => [
					'getFavourites',
					'json_change_dock',
					'saveUserSettings',
					'loadUserSettings'
				],
				'config' => [
					'menu'
				],
				'*' => [
					'dataset_config'
				]
			];

			if (isset($methods[$module])) {
				if (in_array($method, $methods[$module])) {
					return true;
				}
			}

			if (isset($methods['*'])) {
				if (in_array($method, $methods['*'])) {
					return true;
				}
			}

			return false;
		}

		/** @inheritdoc */
		public function isAllowedObject($ownerId, $objectId, $resetCache = false) {
			$objectId = (int) $objectId;
			if ($objectId == 0) {
				return [
					false,
					false,
					false,
					false,
					false
				];
			}

			if ($this->isSv($ownerId)) {
				return [
					true,
					true,
					true,
					true,
					true
				];
			}

			if (array_key_exists($objectId, $this->tempElementPermissions)) {
				$level = $this->tempElementPermissions[$objectId];
				return [
					(bool) ($level & 1),
					(bool) ($level & 2),
					(bool) ($level & 4),
					(bool) ($level & 8),
					(bool) ($level & 16)
				];
			}

			$cache = &$this->elementsCache;

			if (!$resetCache && isset($cache[$objectId]) && isset($cache[$objectId][$ownerId])) {
				return $cache[$objectId][$ownerId];
			}

			$sqlWhere = $this->makeSqlWhere($ownerId);
			$sql = "SELECT BIT_OR(cp.level) FROM cms3_permissions cp WHERE rel_id = '{$objectId}' AND {$sqlWhere}";
			$level = false;

			if (!$level || $resetCache) {
				$connection = ConnectionPool::getInstance()->getConnection();
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$level = 0;

				if ($result->length() > 0) {
					$fetchResult = $result->fetch();
					$level = (int) array_shift($fetchResult);
				}

				$level = [
					(bool) ($level & 1),
					(bool) ($level & 2),
					(bool) ($level & 4),
					(bool) ($level & 8),
					(bool) ($level & 16)
				];
			}

			if (!isset($cache[$objectId])) {
				$cache[$objectId] = [];
			}

			$cache[$objectId][$ownerId] = $level;
			return $level;
		}

		/** @inheritdoc */
		public function isAllowedEditInPlace() {
			$userId = Service::Auth()->getUserId();
			$isAllowedMethod = $this->isAllowedMethod($userId, 'content', 'sitetree');
			$isAllowedDomain = $this->isAllowedDomain($userId, Service::DomainDetector()->detectId());
			return $isAllowedMethod && $isAllowedDomain;
		}

		/** @inheritdoc */
		public function isPageCanBeViewed($ownerId, $pageId) {
			$ignoreCache = true;
			$permissions = $this->isAllowedObject($ownerId, $pageId, $ignoreCache);

			if (!is_array($permissions) || !isset($permissions[0])) {
				return false;
			}

			return (bool) $permissions[0];
		}

		/** @inheritdoc */
		public function isSv($userId = false) {
			static $isSv = [];

			if (!$userId) {
				$userId = Service::Auth()->getUserId();
			}

			if (isset($isSv[$userId])) {
				return $isSv[$userId];
			}

			if (getRequest('guest-mode') !== null) {
				return $isSv[$userId] = false;
			}

			$svGroupId = Service::SystemUsersPermissions()->getSvGroupId();
			$userTypeId = umiTypesHelper::getInstance()->getObjectTypeIdByGuid('users-user');
			$userGroups = umiPropertiesHelper::getInstance()->getPropertyValue($userId, 'groups', $userTypeId);

			if ((is_array($userGroups) && in_array($svGroupId, $userGroups)) || $userId == $svGroupId) {
				return $isSv[$userId] = true;
			}

			return $isSv[$userId] = false;
		}

		/** @inheritdoc */
		public function isAdmin($userId = false, $ignoreCache = false) {
			static $isAdmin = [];

			if ($userId === false) {
				$userId = Service::Auth()->getUserId();
			}

			if (isset($isAdmin[$userId])) {
				return $isAdmin[$userId];
			}

			if ($this->isSv($userId)) {
				return $isAdmin[$userId] = true;
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql_where = $this->makeSqlWhere($userId);
			$sql = <<<SQL
SELECT cp.allow
FROM cms_permissions cp
WHERE method IS NULL AND $sql_where AND cp.allow IN (1, 2) GROUP BY module LIMIT 0,1
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			return $isAdmin[$userId] = $result->length() > 0;
		}

		/** @inheritdoc */
		public function isOwnerOfObject($objectId, $userId = false) {
			if (!$userId) {
				$userId = Service::Auth()->getUserId();
			}

			if ($userId == $objectId) {
				return true;
			}

			$object = umiObjectsCollection::getInstance()->getObject($objectId);

			if ($object instanceof iUmiObject) {
				$ownerId = $object->getOwnerId();
			} else {
				$ownerId = false;
			}

			if ($ownerId === false || $ownerId == $userId) {
				return true;
			}

			$guestId = Service::SystemUsersPermissions()->getGuestUserId();

			if ($ownerId == $guestId && class_exists('customer')) {

				if (!Service::CookieJar()->isExists('customer-id')) {
					return false;
				}

				$customer = customer::get();

				if ($customer && ($customer->id == $ownerId)) {
					return true;
				}
			}

			return false;
		}

		/** @inheritdoc */
		public function setDefaultPermissions($elementId) {
			$element = umiHierarchy::getInstance()
				->getElement($elementId, true, true);

			if (!$element instanceof iUmiHierarchyElement) {
				return false;
			}

			$connection = ConnectionPool::getInstance()
				->getConnection();
			$connection->startTransaction();

			try {
				$elementId = (int) $element->getId();
				$sql = "DELETE FROM cms3_permissions WHERE rel_id = $elementId";
				$connection->query($sql);

				$sel = new selector('objects');
				$sel->types('object-type')->name('users', 'user');
				$sel->where('groups')->isnull();
				$sel->option('return')->value('id');
				$result = $sel->result();

				$userIds = array_map(function ($info) {
					return (int) $info['id'];
				}, $result);
				$systemUsersPermissions = Service::SystemUsersPermissions();
				$guestId = (int) $systemUsersPermissions->getGuestUserId();

				if ($guestId) {
					$userIds[] = $guestId;
					$userIds = array_unique($userIds);
				}

				$sel = new selector('objects');
				$sel->types('object-type')->name('users', 'users');
				$sel->option('return')->value('id');
				$result = $sel->result();

				$groupIds = array_map(function ($info) {
					return (int) $info['id'];
				}, $result);
				$objectIds = array_merge($userIds, $groupIds);

				$ownerId = $element->getObject()->getOwnerId();
				$owner = umiObjectsCollection::getInstance()
					->getObject($ownerId);

				if ($owner) {
					$ownerGroupIds = $owner->getValue('groups');
					if ($ownerGroupIds) {
						$ownerIds = $ownerGroupIds;
					} else {
						$ownerIds = [$ownerId];
					}
				} else {
					$ownerIds = [];
				}

				$hierarchyTypeId = $element->getTypeId();
				$hierarchyType = umiHierarchyTypesCollection::getInstance()
					->getType($hierarchyTypeId);

				$module = $hierarchyType->getName();
				$method = $hierarchyType->getExt();

				$svGroupId = $systemUsersPermissions->getSvGroupId();

				foreach ($objectIds as $id) {
					if ($id == $svGroupId) {
						continue;
					}

					if ($module === 'content') {
						$method = 'page';
					}

					if ($this->isAllowedMethod($id, $module, $method)) {
						if (in_array($id, $ownerIds) || $id == $svGroupId ||
							$this->isAllowedMethod($id, $module, $method . '.edit')) {
							$level =
								self::E_READ_ALLOWED_BIT +
								self::E_EDIT_ALLOWED_BIT +
								self::E_CREATE_ALLOWED_BIT +
								self::E_DELETE_ALLOWED_BIT +
								self::E_MOVE_ALLOWED_BIT;
						} else {
							$level = self::E_READ_ALLOWED_BIT;
						}

						$sql = "INSERT INTO cms3_permissions (rel_id, owner_id, level) VALUES('{$elementId}', '{$id}', '{$level}')";
						$connection->query($sql);
					}
				}
			} catch (Exception $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			$connection->commitTransaction();

			$this->cleanupElementPermissions($elementId);

			if (isset($this->elementsCache[$elementId])) {
				unset($this->elementsCache[$elementId]);
			}

			return true;
		}

		/** @inheritdoc */
		public function setInheritedPermissions($elementId) {
			$elementId = (int) $elementId;
			$hierarchy = umiHierarchy::getInstance();
			$parentId = false;
			$element = $hierarchy->getElement($elementId, true);

			if ($element instanceof iUmiHierarchyElement) {
				$parentId = $element->getParentId();
			}

			if (!$parentId) {
				return $this->setDefaultPermissions($elementId);
			}

			$records = $this->getRecordedPermissions($parentId);
			$values = [];

			foreach ($records as $ownerId => $level) {
				$values[] = "('{$elementId}', '{$ownerId}', '{$level}')";
			}

			if (empty($values)) {
				return false;
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->startTransaction();

			try {
				$sql = "DELETE FROM cms3_permissions WHERE rel_id = '{$elementId}'";
				$connection->query($sql);
				$sql = 'INSERT INTO cms3_permissions (rel_id, owner_id, level) VALUES ' . implode(', ', $values);
				$connection->query($sql);
			} catch (Exception $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			$connection->commitTransaction();
			$this->isAllowedObject(Service::Auth()->getUserId(), $elementId, true);
			return true;
		}

		/** @inheritdoc */
		public function resetElementPermissions($elementId, $ownerId = false) {
			$elementId = (int) $elementId;

			if ($ownerId === false) {
				$sql = "DELETE FROM cms3_permissions WHERE rel_id = '{$elementId}'";

				if (isset($this->elementsCache[$elementId])) {
					unset($this->elementsCache[$elementId]);
				}
			} else {
				$ownerId = (int) $ownerId;
				$sql = "DELETE FROM cms3_permissions WHERE owner_id = '{$ownerId}' AND rel_id = '{$elementId}'";

				if (isset($this->elementsCache[$elementId]) && isset($this->elementsCache[$elementId][$ownerId])) {
					unset($this->elementsCache[$elementId][$ownerId]);
				}
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->query($sql);
			return true;
		}

		/** @inheritdoc */
		public function deleteElementsPermissionsByOwnerId($ownerId) {
			$ownerId = (int) $ownerId;
			$connection = ConnectionPool::getInstance()->getConnection();
			$deletion = "DELETE FROM `cms3_permissions` WHERE `owner_id` = $ownerId";
			$connection->query($deletion);
		}

		/** @inheritdoc */
		public function resetModulesPermissions($ownerId, $modules = null) {
			$connection = ConnectionPool::getInstance()->getConnection();

			$ownerId = (int) $ownerId;
			$sql = "DELETE FROM cms_permissions WHERE owner_id = '{$ownerId}'";

			if (is_array($modules) && umiCount($modules) > 0) {
				$modules = array_map([$connection, 'escape'], $modules);
				$modules = implode("', '", $modules);
				$sql = "DELETE FROM cms_permissions WHERE owner_id = '{$ownerId}' AND module IN ('$modules')";
			}

			$connection->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function setElementPermissions($ownerId, $elementId, $level) {
			$ownerId = (int) $ownerId;
			$elementId = (int) $elementId;
			$level = (int) $level;

			if ($elementId == 0 || $ownerId == 0) {
				return false;
			}

			if (isset($this->elementsCache[$elementId]) && isset($this->elementsCache[$elementId][$ownerId])) {
				unset($this->elementsCache[$elementId][$ownerId]);
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql_reset = "DELETE FROM cms3_permissions WHERE owner_id = '" . $ownerId . "' AND rel_id = '" . $elementId . "'";
			$connection->query($sql_reset);

			$sql = "INSERT INTO cms3_permissions (owner_id, rel_id, level) VALUES('{$ownerId}', '{$elementId}', '{$level}')";
			$connection->query($sql);

			$this->cleanupElementPermissions($elementId);
			$this->isAllowedObject($ownerId, $elementId, true);

			return true;
		}

		/** @inheritdoc */
		public function setModulesPermissions(
			$ownerId,
			$module,
			$method = false,
			$cleanupPermissions = true
		) {
			$ownerId = (int) $ownerId;
			$connection = ConnectionPool::getInstance()->getConnection();
			$module = $connection->escape($module);

			if ($method !== false) {
				return $this->setMethodPermissions($ownerId, $module, $method, $cleanupPermissions);
			}

			$sql = "INSERT INTO cms_permissions (owner_id, module, method, allow) VALUES('{$ownerId}', '{$module}', NULL, '1')";
			$connection->query($sql);

			if ($cleanupPermissions) {
				$this->cleanupBasePermissions();
			}

			return true;
		}

		/** @inheritdoc */
		public function deleteModulePermission($ownerId, $module) {
			$ownerId = (int) $ownerId;
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$module = $connection->escape($module);

			$deleteSql = <<<SQL
DELETE FROM `cms_permissions` WHERE `owner_id` = $ownerId AND `module` = '$module' AND `method` IS NULL
SQL;
			$connection->query($deleteSql);
			return $this;
		}

		/** @inheritdoc */
		public function deleteMethodPermission($ownerId, $module, $method) {
			$ownerId = (int) $ownerId;
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$module = $connection->escape($module);
			$method = $connection->escape($method);

			$deleteSql = <<<SQL
DELETE FROM `cms_permissions` WHERE `owner_id` = $ownerId AND `module` = '$module' AND `method` = '$method'
SQL;
			$connection->query($deleteSql);
			return $this;
		}

		protected function setMethodPermissions(
			$ownerId,
			$module,
			$method,
			$cleanupPermissions = true
		) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$method = $connection->escape($method);
			$module = $connection->escape($module);
			$ownerId = (int) $ownerId;

			$sql = <<<SQL
INSERT INTO cms_permissions (owner_id, module, method, allow)
VALUES('{$ownerId}', '{$module}', '{$method}', '1')
SQL;
			$connection->query($sql);

			$this->methodsPermissions[$ownerId][$module][] = $method;

			if ($cleanupPermissions) {
				$this->cleanupBasePermissions();
			}

			return true;
		}

		/** @inheritdoc */
		public function hasUserPermissions($ownerId) {
			$ownerId = (int) $ownerId;
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT `rel_id` FROM `cms3_permissions` WHERE `owner_id` = '{$ownerId}' LIMIT 0,1";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			return $result->length() > 0;
		}

		/** @inheritdoc */
		public function hasUserModulesPermissions($ownerId) {
			$ownerId = (int) $ownerId;

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT `module` FROM `cms_permissions` WHERE `owner_id` = $ownerId AND `allow` = 1 LIMIT 0,1";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			return ($result->length() == 1);
		}

		/** @inheritdoc */
		public function copyHierarchyPermissions($fromUserId, $toUserId) {

			if ($fromUserId == Service::SystemUsersPermissions()->getGuestUserId()) {
				return false;    //No need in cloning guest permissions now
			}

			$fromUserId = (int) $fromUserId;
			$toUserId = (int) $toUserId;
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
INSERT INTO cms3_permissions (level, rel_id, owner_id)
SELECT level, rel_id, '{$toUserId}' FROM cms3_permissions WHERE owner_id = '{$fromUserId}'
SQL;
			$connection->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function getStaticPermissions($module, $skipCache = false) {
			if (isset($this->modulePermissions[$module]) && !$skipCache) {
				return $this->modulePermissions[$module];
			}

			$this->modulePermissions[$module] = Service::ModulePermissionLoader()
				->load($module);
			return $this->modulePermissions[$module];
		}

		/**
		 * Получить название корневого метода в системе приоритета прав для $module::$method
		 * @param string $module название модуля
		 * @param string $method название метода
		 * @return string название корневого метода
		 */
		protected function getBaseMethodName($module, $method) {
			$methods = $this->getStaticPermissions($module);

			if ($method && is_array($methods)) {

				if (array_key_exists($method, $methods)) {
					return $method;
				}

				foreach ($methods as $base_method => $sub_methods) {
					if (is_array($sub_methods)) {
						if (in_array($method, $sub_methods) || in_array(mb_strtolower($method), $sub_methods)) {
							return $base_method;
						}
					}
				}

				return $method;
			}

			return $method;
		}

		/** @inheritdoc */
		public function cleanupBasePermissions() {
			$guestId = Service::SystemUsersPermissions()->getGuestUserId();

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT module, method FROM cms_permissions WHERE owner_id = '{$guestId}' AND allow = 1";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$sql = [];

			foreach ($result as $row) {
				list($module, $method) = $row;
				$sql[] = $method
					? "(module = '{$module}' AND method = '{$method}')"
					: "(module = '{$module}' AND method IS NULL)";
			}

			if (!empty($sql)) {
				$sql = implode(' OR ', $sql);
				$connection->query("DELETE FROM cms_permissions WHERE owner_id != '{$guestId}' AND ($sql)");
			}
		}

		/**
		 * Удалить для страницы  с id $rel_id записи о правах пользователей, которые ниже, чем у гостя
		 * @param int $relId id страница (класс umiHierarchyElement)
		 */
		protected function cleanupElementPermissions($relId) {
			$relId = (int) $relId;
			$guestId = Service::SystemUsersPermissions()->getGuestUserId();

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT level FROM cms3_permissions WHERE owner_id = '{$guestId}' AND rel_id = {$relId}";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$maxLevel = 0;

			foreach ($result as $row) {
				$level = array_shift($row);

				if ($level > $maxLevel) {
					$maxLevel = $level;
				}
			}

			$connection->query(<<<SQL
DELETE FROM cms3_permissions
WHERE owner_id != '{$guestId}' AND level <= {$maxLevel} AND rel_id = {$relId}
SQL
			);
		}

		/** @inheritdoc */
		public function isAllowedDomain($ownerId, $domainId) {
			$ownerId = (int) $ownerId;
			$domainId = (int) $domainId;

			if ($this->isSv($ownerId)) {
				return 1;
			}

			$sqlWhereOwners = $this->makeSqlWhere($ownerId);
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT MAX(cp.allow) FROM cms_permissions cp
WHERE cp.module = 'domain' AND cp.method = '$domainId' AND $sqlWhereOwners
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$isAllowed = 0;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$isAllowed = (int) array_shift($fetchResult);
			}

			return $isAllowed;
		}

		/** @inheritdoc */
		public function setAllowedDomain($ownerId, $domainId, $allow = 1) {
			$ownerId = (int) $ownerId;
			$domainId = (int) $domainId;
			$allow = (int) $allow;

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
DELETE FROM cms_permissions
WHERE module = 'domain' AND method = '{$domainId}' AND owner_id = '{$ownerId}'
SQL;
			$connection->query($sql);

			$sql = <<<SQL
INSERT INTO cms_permissions (module, method, owner_id, allow)
VALUES('domain', '{$domainId}', '{$ownerId}', '{$allow}')
SQL;
			$connection->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function setDefaultElementPermissions(iUmiHierarchyElement $element, $ownerId) {
			$module = $element->getModule();
			$method = $element->getMethod();

			$level = 0;
			if ($this->isAllowedMethod($ownerId, $module, $method, true)) {
				$level = self::E_READ_ALLOWED_BIT;
			}

			if ($this->isAllowedMethod($ownerId, $module, $method . '.edit', true)) {
				$level =
					self::E_READ_ALLOWED_BIT +
					self::E_EDIT_ALLOWED_BIT +
					self::E_CREATE_ALLOWED_BIT +
					self::E_DELETE_ALLOWED_BIT +
					self::E_MOVE_ALLOWED_BIT;
			}

			$this->setElementPermissions($ownerId, $element->getId(), $level);

			return $level;
		}

		/** @inheritdoc */
		public function setAllElementsDefaultPermissions($ownerId) {
			$ownerId = (int) $ownerId;
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();

			$this->elementsCache = [];

			$owner = $this->getOwnerType($ownerId);
			if (is_numeric($owner)) {
				$owner = [];
			}

			$owner[] = Service::SystemUsersPermissions()->getGuestUserId();
			$owner = array_unique($owner);

			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->startTransaction();

			try {
				$read = [];
				$write = [];

				/** @var iUmiHierarchyType $hierarchyType */
				foreach ($hierarchyTypes->getTypesList() as $hierarchyType) {
					$module = $hierarchyType->getName();
					$method = $hierarchyType->getExt();

					if ($this->isAllowedMethod($ownerId, $module, $method . '.edit', true)) {
						foreach ($owner as $gid) {
							if ($gid == $ownerId) {
								continue;
							}

							if ($this->isAllowedMethod($gid, $module, $method . '.edit', true)) {
								continue 2;
							}
						}
						$write[] = $hierarchyType->getId();
					} else {
						if ($this->isAllowedMethod($ownerId, $module, $method, true)) {
							foreach ($owner as $gid) {
								if ($gid == $ownerId) {
									continue;
								}

								if ($this->isAllowedMethod($gid, $module, $method, true)) {
									continue 2;
								}
							}

							$read[] = $hierarchyType->getId();
						}
					}
				}

				if (umiCount($read)) {
					$types = implode(', ', $read);

					$sql = <<<SQL
	INSERT INTO cms3_permissions (level, owner_id, rel_id)
		SELECT 1, '{$ownerId}', id FROM cms3_hierarchy WHERE type_id IN ({$types})
SQL;
					$connection->query($sql);
				}

				if (umiCount($write)) {
					$types = implode(', ', $write);

					$sql = <<<SQL
	INSERT INTO cms3_permissions (level, owner_id, rel_id)
		SELECT 31, '{$ownerId}', id FROM cms3_hierarchy WHERE type_id IN ({$types})
SQL;
					$connection->query($sql);
				}
			} catch (Exception $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			$connection->commitTransaction();
		}

		/** @inheritdoc */
		public function getUsersByElementPermissions($elementId, $level = 1) {
			$elementId = (int) $elementId;
			$level = (int) $level;

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT owner_id FROM cms3_permissions WHERE rel_id = '{$elementId}' AND level >= '{$level}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$owners = [];

			foreach ($result as $row) {
				$owners[] = (int) array_shift($row);
			}

			return $owners;
		}

		/** @inheritdoc */
		public function getRecordedPermissions($elementId) {
			$elementId = (int) $elementId;

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT owner_id, level FROM cms3_permissions WHERE rel_id = '{$elementId}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$records = [];

			foreach ($result as $row) {
				list($ownerId, $level) = $row;
				$records[$ownerId] = (int) $level;
			}

			return $records;
		}

		/** @inheritdoc */
		public function getPrivileged($perms) {
			if (!umiCount($perms)) {
				return [];
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = 'SELECT owner_id FROM cms_permissions WHERE ';
			$sqls = [];

			foreach ($perms as $perm) {
				$module = $connection->escape(getArrayKey($perm, 0));
				$method = $connection->escape($this->getBaseMethodName($module, getArrayKey($perm, 1)));
				$sqls[] = "(module = '{$module}' AND method = '{$method}')";
			}

			$sql .= implode(' OR ', $sqls);
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$owners = [];

			foreach ($result as $row) {
				$owners[] = array_shift($row);
			}

			$owners = array_unique($owners);
			return $owners;
		}

		/** @inheritdoc */
		public function clearCache() {
			$this->elementsCache = [];
			$this->tempElementPermissions = [];
			$this->methodsPermissions = [];
		}

		/**
		 * @deprecated
		 * Узнать, авторизован ли текущий пользователь
		 * @return bool true, если авторизован
		 */
		public function isAuth() {
			return Service::Auth()->isAuthorized();
		}

		/**
		 * @deprecated
		 * Алиас для isAuth()
		 * @return bool
		 */
		public function is_auth() {
			return Service::Auth()->isAuthorized();
		}

		/** @deprecated */
		public function loadReadablePages() {
			return true;
		}

		/** @deprecated */
		public function isReadablePagesLoaded() {
			return false;
		}

		/** @deprecated */
		public function getReadablePagesIds() {
			return [];
		}

		/** @deprecated */
		public function isPageReadable($pageId, $resetCache = false) {
			return $this->isAllowedObject(Service::Auth()->getUserId(), $pageId, $resetCache);
		}

		/** @deprecated */
		public function hashPassword($password, $algorithm = self::SHA256) {
			return Service::PasswordHashAlgorithm()->hash($password, $algorithm);
		}

		/** @deprecated */
		public function isPasswordHashedWithMd5($hashedPassword, $rawPassword) {
			return Service::PasswordHashAlgorithm()->isHashedWithMd5($hashedPassword, $rawPassword);
		}

		/** @deprecated */
		public function getSvUserId() {
			return Service::SystemUsersPermissions()->getSvUserId();
		}

		/** @deprecated */
		public function getSvGroupId() {
			return Service::SystemUsersPermissions()->getSvGroupId();
		}

		/** @deprecated */
		public function getGuestUserId() {
			return Service::SystemUsersPermissions()->getGuestUserId();
		}

		/** @deprecated */
		public static function getGuestId() {
			return Service::SystemUsersPermissions()->getGuestUserId();
		}

		/** @deprecated */
		public function checkLogin($login, $password) {
			$userId = Service::Auth()->checkLogin($login, $password);
			return selector::get('object')->id($userId);
		}

		/** @deprecated */
		public function getUserId() {
			return Service::Auth()->getUserId();
		}

		/** @deprecated */
		public function loginAsUser($userId) {
			return Service::Auth()->loginUsingId($userId);
		}

		/**
		 * @deprecated
		 * Задает права на страницу.
		 * Влияет только на текущую сессию, данные в базе изменены не будут.
		 * @param int $elementId id страницы
		 * @param int $level = 1 уровень прав доступа (0-3).
		 */
		public function pushElementPermissions($elementId, $level = 1) {
			$this->tempElementPermissions[$elementId] = (int) $level;
		}
	}
