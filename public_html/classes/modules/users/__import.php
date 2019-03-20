<?php
	/**
	 * Класс реализует работу с правами пользователя|группы в административной панели
	 * todo: refactoring
	 */
	abstract class __imp__users {
		/**
		 * Включена перезагрузка прав пользователя на страницы при изменении прав пользователя на методы модулей
		 * @var bool
		 */
		protected $changePagesPermissionsOnEdit = false;
		/**
		 * Включена установка прав пользователя на страницы при создании пользователя
		 * @var bool
		 */
		protected $changePagesPermissionsOnAdd = false;

		/** Кастомный конструктор */
		public function onImplement() {
			$umiRegistry = regedit::getInstance();
			$this->changePagesPermissionsOnEdit = (bool) $umiRegistry->getVal('//modules/users/pages_permissions_changing_enabled_on_edit');
			$this->changePagesPermissionsOnAdd = (bool) $umiRegistry->getVal('//modules/users/pages_permissions_changing_enabled_on_add');
		}

		/**
		 * Возвращает права пользователей|групп. Применяет в настройках прав
		 * страниц в административной панели.
		 * @param string $module имя модуля
		 * @param string $method имя метода
		 * @param bool $element_id id страницы
		 * @param bool $parent_id id родительской страницы
		 * @return mixed|string
		 */
		public function permissions($module = "", $method = "", $element_id = false, $parent_id = false) {
			if (!$module && !$method && !$element_id && !$parent_id) {
				return "";
			}

			$objectsCollection = umiObjectsCollection::getInstance();
			$permissions = permissionsCollection::getInstance();

			$perms_users = array();
			$perms_groups = array();
			if ($element_id || $parent_id) {
				$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName("users", "user");
				$records     = $permissions->getRecordedPermissions($element_id ? $element_id : $parent_id);
				foreach ($records as $id => $level) {
					$owner = $objectsCollection->getObject($id);
					if (!$owner) continue;
					if ($owner->getTypeId() == $typeId) {
						$ownerGroupsIds = $owner->getValue('groups');
						if (is_array($ownerGroupsIds)) {
							foreach ($ownerGroupsIds as $groupId) {
								$groupLevel = $permissions->isAllowedObject($groupId, $element_id ? $element_id : $parent_id);
								foreach ($groupLevel as $i => $l) {
									$level |= pow(2, $i) * (int) $l;
								}
							}
						}

						$perms_users[] = array(
							'attribute:id'		=> $owner->getGUID() ? $owner->getGUID() : $owner->getId(),
							'attribute:login'	=> $owner->getValue('login'),
							'attribute:access'	=> $level
						);
					} else {
						$perms_groups[] = array(
							'attribute:id'		=> $owner->getGUID() ? $owner->getGUID() : $owner->getId(),
							'attribute:title'	=> $owner->getValue('nazvanie'),
							'attribute:access'	=> $level
						);
					}
				}
			} else {
				$current_user_id = $permissions->getUserId();
				$current_user = $objectsCollection->getObject($current_user_id);
				$current_owners = $current_user->getValue("groups");
				if (!is_array($current_owners)) {
					$current_owners = array();
				}
				$current_owners[] = $current_user_id;

				if (!$method) $method = "page";
				$method_view = $method;
				$method_edit = $method . ".edit";

				$owners = $permissions->getPrivileged(array(array($module, $method_view), array($module, $method_edit)));
				$systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();

				$svUserAndGroupIds = [
					$systemUsersPermissions->getSvUserId(),
					$systemUsersPermissions->getSvGroupId()
				];

				foreach ($owners as $ownerId) {
					if (in_array($ownerId, $svUserAndGroupIds)) continue;
					/* @var umiObject $owner */
					$owner = selector::get('object')->id($ownerId);
					if (!$owner) continue;

					$r = $e = $c = $d = $m = 0;
					if (in_array($ownerId, $current_owners)) {
						$r = permissionsCollection::E_READ_ALLOWED_BIT;
						$e = permissionsCollection::E_EDIT_ALLOWED_BIT;
						$c = permissionsCollection::E_CREATE_ALLOWED_BIT;
						$d = permissionsCollection::E_DELETE_ALLOWED_BIT;
						$m = permissionsCollection::E_MOVE_ALLOWED_BIT;
					} else {
						$r = $this->isAllowedMethod($ownerId, $module, $method_view) ? permissionsCollection::E_READ_ALLOWED_BIT : 0;
						$e = $this->isAllowedMethod($ownerId, $module, $method_edit) ? permissionsCollection::E_EDIT_ALLOWED_BIT : 0;
						if ($e) {
							$c = permissionsCollection::E_CREATE_ALLOWED_BIT;
							$d = permissionsCollection::E_DELETE_ALLOWED_BIT;
							$m = permissionsCollection::E_MOVE_ALLOWED_BIT;
						}
					}

					$r = (int)$r & permissionsCollection::E_READ_ALLOWED_BIT;
					$e = (int)$e & permissionsCollection::E_EDIT_ALLOWED_BIT;
					$c = (int)$c & permissionsCollection::E_CREATE_ALLOWED_BIT;
					$d = (int)$d & permissionsCollection::E_DELETE_ALLOWED_BIT;
					$m = (int)$m & permissionsCollection::E_MOVE_ALLOWED_BIT;

					/* @var iUmiObjectType $ownerObjectType */
					$ownerObjectType = selector::get('object-type')->id($owner->getTypeId());
					$ownerType = $ownerObjectType->getMethod();

					if ($ownerType == 'user') {
						$perms_users[] = array(
							'attribute:id'		=> $owner->getGUID() ? $owner->getGUID() : $owner->getId(),
							'attribute:login'	=> $owner->getValue('login'),
							'attribute:access'	=> ($r + $e + $c + $d + $m)
						);
					} else {
						$perms_groups[] = array(
							'attribute:id'		=> $owner->getGUID() ? $owner->getGUID() : $owner->getId(),
							'attribute:title'	=> $owner->getName(),
							'attribute:access'	=> ($r + $e + $c + $d + $m)
						);
					}
				}
			}

			return def_module::parseTemplate('', array(
				'users'		=> array('nodes:user' => $perms_users),
				'groups'	=> array('nodes:group' => $perms_groups)
			));
		}

		/**
		 * Возвращает права пользователя|группы на страницу
		 * @param int $id id пользователя|группы
		 * @param int $element_id id страницы
		 * @return array
		 */
		public function getUserPermissions($id, $element_id) {
			$allow = permissionsCollection::getInstance()->isAllowedObject($id, $element_id);
			$permission = ((int)$allow[permissionsCollection::E_READ_ALLOWED]   * permissionsCollection::E_READ_ALLOWED_BIT) +
				((int)$allow[permissionsCollection::E_EDIT_ALLOWED]   * permissionsCollection::E_EDIT_ALLOWED_BIT) +
				((int)$allow[permissionsCollection::E_CREATE_ALLOWED] * permissionsCollection::E_CREATE_ALLOWED_BIT) +
				((int)$allow[permissionsCollection::E_DELETE_ALLOWED] * permissionsCollection::E_DELETE_ALLOWED_BIT) +
				((int)$allow[permissionsCollection::E_MOVE_ALLOWED]   * permissionsCollection::E_MOVE_ALLOWED_BIT);
			return array('user' => array('attribute:id' => $id, 'node:name' => $permission));
		}

		/**
		 * Сохраняет права пользователя на страницу
		 * @param int $element_id id страницы
		 * @return void
		 */
		public function setPerms($element_id) {
			$permissions = permissionsCollection::getInstance();

			if (!getRequest('perms_read') && !getRequest('perms_edit') && !getRequest('perms_create') &&
				!getRequest('perms_delete') && !getRequest('perms_move') &&
				/* Note this argument. It's important' */
				getRequest('default-permissions-set')) {

				$permissions->setDefaultPermissions($element_id);
				return;
			} elseif (!getRequest('perms_read') && !getRequest('perms_edit') && !getRequest('perms_create') &&
				!getRequest('perms_delete') && !getRequest('permissions-sent')) {
				return;
			}

			$perms_read   = ($t = getRequest('perms_read'))   ? $t : array();
			$perms_edit   = ($t = getRequest('perms_edit'))   ? $t : array();
			$perms_create = ($t = getRequest('perms_create')) ? $t : array();
			$perms_delete = ($t = getRequest('perms_delete')) ? $t : array();
			$perms_move   = ($t = getRequest('perms_move'))	  ? $t : array();

			$permissions->resetElementPermissions($element_id);

			$owners = array_keys($perms_read);
			$owners = array_merge($owners, array_keys($perms_edit));
			$owners = array_merge($owners, array_keys($perms_create));
			$owners = array_merge($owners, array_keys($perms_delete));
			$owners = array_merge($owners, array_keys($perms_move));
			$owners = array_unique($owners);

			foreach ($owners as $owner) {
				$level = 0;
				if (isset($perms_read[$owner]))   $level |= 1;
				if (isset($perms_edit[$owner]))   $level |= 2;
				if (isset($perms_create[$owner])) $level |= 4;
				if (isset($perms_delete[$owner])) $level |= 8;
				if (isset($perms_move[$owner]))   $level |= 16;

				if (is_string($owner)) $owner = umiObjectsCollection::getInstance()->getObjectIdByGUID($owner);

				$permissions->setElementPermissions($owner, $element_id, $level);
			}
		}

		/**
		 * Возвращает права пользователя|группы на модули, группы методов и домены системы
		 * @param int|bool $ownerId id пользователя|группы, если не передан - вернет права гостя
		 * @return array
		 */
		public function choose_perms($ownerId = false) {
			$regedit = regedit::getInstance();
			$domainsCollection = domainsCollection::getInstance();
			$permissions = permissionsCollection::getInstance();

			if ($ownerId === false) {
				$systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();
				$ownerId = (int) $systemUsersPermissions->getGuestUserId();
			}

			$restrictedModules = array('autoupdate', 'backup');

			$modules_arr = array();
			$modules_list = $regedit->getList("//modules");

			foreach ($modules_list as $md) {
				list ($module_name) = $md;

				if (in_array($module_name, $restrictedModules)) {
					continue;
				}

				$func_list = array_keys($permissions->getStaticPermissions($module_name));
				if (!system_is_allowed($module_name)) {
					continue;
				}

				$module_label = getLabel("module-" . $module_name);
				$is_allowed_module = $permissions->isAllowedModule($ownerId, $module_name);


				$options_arr = array();
				if (is_array($func_list)) {
					foreach ($func_list as $method_name) {
						if (!system_is_allowed($module_name, $method_name)) {
							continue;
						}

						$is_allowed_method = $permissions->isAllowedMethod($ownerId, $module_name, $method_name);

						$option_arr = array();
						$option_arr['attribute:name'] = $method_name;
						$option_arr['attribute:label'] = getLabel("perms-" . $module_name . "-" . $method_name, $module_name);
						$option_arr['attribute:access'] = (int) $is_allowed_method;
						$options_arr[] = $option_arr;
					}
				}

				$module_arr = array();
				$module_arr['attribute:name'] = $module_name;
				$module_arr['attribute:label'] = $module_label;
				$module_arr['attribute:access'] = (int) $is_allowed_module;
				$module_arr['nodes:option'] = $options_arr;
				$modules_arr[] = $module_arr;
			}

			$domains_arr = array();
			$domains = $domainsCollection->getList();
			/* @var domain $domain */
			foreach ($domains as $domain) {
				$domain_arr = array();
				$domain_arr['attribute:id'] = $domain->getId();
				$domain_arr['attribute:host'] = $domain->getHost();
				$domain_arr['attribute:access'] = $permissions->isAllowedDomain($ownerId, $domain->getId());
				$domains_arr[] = $domain_arr;
			}

			$result_arr = array();
			$result_arr['domains']['nodes:domain'] = $domains_arr;
			$result_arr['nodes:module'] = $modules_arr;

			return $result_arr;
		}

		/**
		 * Сохраняет права пользователя|группы при его|ее редактировании или добавлении
		 * через административную панель
		 * @param int $ownerId id пользователя|группы
		 * @param string $mode режим запуска модуля (edit/add)
		 * @return void
		 * @throws publicAdminException если передан некорректный $ownerId
		 */
		public function save_perms($ownerId, $mode = 'add') {
			$owner = umiObjectsCollection::getInstance()->getObject($ownerId);

			if (!$owner instanceof umiObject) {
				throw new publicAdminException(__METHOD__ . ': object id expected');
			}

			$ownerGUID = $owner->getTypeGUID();

			if ($ownerGUID !== 'users-user' && $ownerGUID !== 'users-users') {
				throw new publicAdminException(__METHOD__ . ': user or group id expected');
			}

			if (!is_string($mode)) {
				throw new publicAdminException(__METHOD__ . ': wrong mode given');
			}

			$umiPermissions = permissionsCollection::getInstance();
			$umiRegistry = regedit::getInstance();
			$systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();
			$guestId = (int) $systemUsersPermissions->getGuestUserId();
			$defGroupId = (int) $umiRegistry->getVal("//modules/users/def_group");

			if (is_array(getRequest('ps_m_perms'))) {
				$umiPermissions->resetModulesPermissions($ownerId, array_keys(getRequest('ps_m_perms')));
			} else {
				$umiPermissions->resetModulesPermissions($ownerId);
			}

			$groups = $owner->getValue("groups");

			if (is_array($groups)) {
				if (count($groups)) {
					list($nl) = $groups;
				} else {
					$nl = false;
				}
				if (!$nl) {
					$groups = false;
				}
			}

			if (!$groups) {
				$cnt = $umiPermissions->hasUserPermissions($ownerId);

				if (!$cnt) {
					$umiPermissions->copyHierarchyPermissions($guestId, $ownerId);
				}
			}

			$isOwnerGuestOrDefGroup = ($ownerId != $guestId && $ownerId != $defGroupId);

			foreach (getRequest('ps_m_perms') as $module => $value) {
				if (is_array(getRequest('m_perms')) && $isOwnerGuestOrDefGroup) {
					if (in_array($module, getRequest('m_perms'))) {
						$umiPermissions->setModulesPermissions($ownerId, $module, false, true);
					}
				}

				if (is_array($domains = getRequest('domain'))) {
					foreach ($domains as $id => $level) {
						$umiPermissions->setAllowedDomain($ownerId, $id, $level);
					}
				}

				if (!is_array(getRequest($module))) {
					continue;
				}

				foreach (getRequest($module) as $method => $is_allowed) {
					$umiPermissions->setModulesPermissions($ownerId, $module, $method, true);
					$methodsPermissions = $umiRegistry->getList("//modules/{$module}/func_perms/{$method}");
					if (is_array($methodsPermissions)) {
						foreach ($methodsPermissions as $method) {
							list($methodName) = $method;

							if (!$methodName || $methodName == 'NULL') {
								continue;
							}

							$umiPermissions->setModulesPermissions($ownerId, $module, $methodName, true);
						}
					}
				}
			}
			$umiPermissions->cleanupBasePermissions();
			switch (true) {
				case $mode == 'edit' && $this->changePagesPermissionsOnEdit: {
					$umiPermissions->deleteElementsPermissionsByOwnerId($ownerId);
					$umiPermissions->setAllElementsDefaultPermissions($ownerId);
					break;
				}
				case $mode == 'add' && $this->changePagesPermissionsOnAdd: {
					$umiPermissions->setAllElementsDefaultPermissions($ownerId);
					break;
				}
			}
		}

		/**
		 * Алиас permissionsCollection::isAllowedMethod().
		 * @param int $ownerId id пользователя|группы
		 * @param string $module имя модуля
		 * @param string $method имя метода
		 * @return bool
		 */
		public function isAllowedMethod($ownerId, $module, $method) {
			return permissionsCollection::getInstance()->isAllowedMethod($ownerId, $module, $method);
		}


		public function checkMessage(iUmiEventPoint $event) {
			$dummyId = $event->getRef('id');
			$dummy = new umiMessage($dummyId);
			$dummy = $dummy->getContent();
			$umiRegistry = regedit::getInstance();
			$umiRegistry->doTesting($dummy);
			$umiMessages = umiMessages::getInstance();
			$umiMessages->dropTestMessages();
		}
	};
?>