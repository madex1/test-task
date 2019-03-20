<?php

use UmiCms\Service;

/** Класс функционала административной панели */
	class UsersAdmin {

		use baseModuleAdmin;

		/** @var users $module */
		public $module;

		/**
		 * Возвращает список пользователей с учетом фильтров
		 * @throws coreException
		 * @throws expectObjectException
		 * @throws selectorException
		 */
		public function users_list() {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$limit = getRequest('per_page_limit');
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'user');
			$sel->limit($offset, $limit);

			if (getRequest('param0') == 'outgroup') {
				$sel->where('groups')->isnull();
			} else {
				$groupId = $this->expectObjectId('param0');

				if ($groupId) {
					$sel->where('groups')->equals($groupId);
				}
			}

			$umiPermissions = permissionsCollection::getInstance();

			if (!$umiPermissions->isSv()) {
				$sel->where('id')->notequals(Service::SystemUsersPermissions()->getSvUserId());
			}

			$loginSearch = getRequest('search');
			if ($loginSearch) {
				$sel->where('login')->like('%' . $loginSearch . '%');
			}

			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result(), 'objects');

			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Возвращает список групп с учетом фильтров
		 * @throws coreException
		 * @throws selectorException
		 */
		public function groups_list() {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$limit = getRequest('per_page_limit');
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'users');

			$umiPermissions = permissionsCollection::getInstance();

			if (!$umiPermissions->isSv()) {
				$sel->where('id')->notequals(Service::SystemUsersPermissions()->getSvGroupId());
			}

			$sel->limit($offset, $limit);

			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);

			$data = $this->prepareData($sel->result(), 'objects');
			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Возвращает форму создания объекта,
		 * если передан $_REQUEST['param1'] = do пытается создать объект
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws wrongElementTypeAdminException
		 */
		public function add() {
			$type = (string) getRequest('param0');
			$this->setHeaderLabel('header-users-add-' . $type);
			$inputData = [
				'type' => $type,
				'type-id' => getRequest('type-id'),
				'aliases' => ['name' => 'login'],
				'allowed-element-types' => ['user', 'users']
			];

			if ($this->isSaveMode('param1')) {
				$object = $this->saveAddedObjectData($inputData);

				$permissions = permissionsCollection::getInstance();
				$svGroupId = Service::SystemUsersPermissions()
					->getSvGroupId();
				$auth = Service::Auth();

				if (!$permissions->isSv($auth->getUserId())) {
					$groups = $object->getValue('groups');

					if (in_array($svGroupId, $groups)) {
						unset($groups[array_search($svGroupId, $groups)]);
						$object->setValue('groups', $groups);
					}
				}

				$object->setValue('user_dock', 'seo,content,news,blogs20,forum,comments,vote,webforms,photoalbum,dispatches,catalog,emarket,banners,users,stat,exchange,trash');
				$object->commit();

				$this->module->save_perms($object->getId(), __FUNCTION__);
				$this->chooseRedirect($this->module->pre_lang . '/admin/users/edit/' . $object->getId() . '/');
			}

			$this->setDataType('form');
			$this->setActionType('create');

			$data = $this->prepareData($inputData, 'object');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает форму редактирования объекта,
		 * если передан $_REQUEST['param1'] = do пытается отредактировать объект
		 * @throws coreException
		 * @throws expectObjectException
		 */
		public function edit() {
			$object = $this->expectObject('param0', true);
			$objectId = $object->getId();
			$this->setHeaderLabel('header-users-edit-' . $this->getObjectTypeMethod($object));
			$this->checkSv($objectId);
			$inputData = [
				'object' => $object,
				'aliases' => ['name' => 'login'],
				'allowed-element-types' => ['users', 'user']
			];

			if ($this->isSaveMode('param1')) {
				try {
					Service::Protection()->checkReferrer();
				} catch (Exception $e) {
					$this->module->errorNewMessage(getLabel('error-users-non-referer'));
					$this->module->errorPanic();
				}

				$this->validateRepeatPassword();
				/** @var iUmiObject $object */
				$object = $this->saveEditedObjectData($inputData);
				$objectId = $object->getId();
				$systemUsersPermissions = Service::SystemUsersPermissions();
				$guestId = $systemUsersPermissions->getGuestUserId();
				$auth = Service::Auth();
				$userId = $auth->getUserId();
				$svUserId = $systemUsersPermissions->getSvUserId();

				if (in_array($object->getId(), [$userId, $guestId, $svUserId])) {
					if (!$object->getValue('is_activated')) {
						$object->setValue('is_activated', true);
						$object->commit();
					}
				}

				$this->module->save_perms($objectId, __FUNCTION__);
				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('modify');

			$data = $this->prepareData($inputData, 'object');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Валидирует повторный ввод пароля.
		 * Ошибка валидации вызывает панику @see def_module::errorPanic()
		 * @return bool
		 */
		public function validateRepeatPassword() {
			$formData = Service::Request()
				->Post()
				->get('data');
			$fieldList = array_shift($formData);

			$password = isset($fieldList['password']) ? $fieldList['password'] : null;
			$passwordRepeat = isset($fieldList['password_repeat']) ? $fieldList['password_repeat'] : null;

			if ($password === $passwordRepeat) {
				return true;
			}

			$this->module->errorNewMessage(getLabel('js-error-password-repeat'));
			$this->module->errorPanic();
		}

		/**
		 * Удаляет пользователей
		 * @throws coreException
		 * @throws expectObjectException
		 * @throws publicAdminException
		 * @throws wrongElementTypeAdminException
		 */
		public function del() {
			$objects = getRequest('element');

			if (!is_array($objects)) {
				$objects = [$objects];
			}

			$systemUsersPermissions = Service::SystemUsersPermissions();
			$svGroupId = $systemUsersPermissions->getSvGroupId();
			$svUserId = $systemUsersPermissions->getSvUserId();
			$guestId = $systemUsersPermissions->getGuestUserId();
			$auth = Service::Auth();
			$userId = $auth->getUserId();
			$defaultGroupId = Service::Registry()->get('//modules/users/def_group');

			foreach ($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);
				if (!$object) {
					continue;
				}
				$this->checkSv($object->getId());

				$object_id = $object->getId();

				if ($object_id == $svGroupId) {
					throw new publicAdminException(getLabel('error-sv-group-delete'));
				}

				if ($object_id == $svUserId) {
					throw new publicAdminException(getLabel('error-sv-user-delete'));
				}

				if ($object_id == $guestId) {
					throw new publicAdminException(getLabel('error-guest-user-delete'));
				}

				if ($object_id == $defaultGroupId) {
					throw new publicAdminException(getLabel('error-sv-group-delete'));
				}

				if ($object_id == $userId) {
					throw new publicAdminException(getLabel('error-delete-yourself'));
				}

				$params = [
					'object' => $object,
					'allowed-element-types' => ['user', 'users']
				];

				$this->deleteObject($params);
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($objects, 'objects');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Меняет статус активности пользователей
		 * @throws coreException
		 * @throws expectObjectException
		 * @throws publicAdminException
		 */
		public function activity() {
			$this->changeActivityForObjects();
		}

		/** @inheritdoc */
		protected function validateObjectActivityChange($user, $isActive) {
			if (!$user instanceof iUmiObject) {
				throw new expectObjectException(getLabel('error-expect-object'));
			}

			$userId = $user->getId();
			$this->checkSv($userId);

			if ($isActive) {
				return;
			}

			$systemUsers = Service::SystemUsersPermissions();

			if ($userId == $systemUsers->getSvUserId()) {
				throw new publicAdminException(getLabel('error-sv-user-activity'));
			}

			if ($userId == $systemUsers->getGuestUserId()) {
				throw new publicAdminException(getLabel('error-guest-user-activity'));
			}
		}

		/** @inheritdoc */
		protected function setObjectActivity(iUmiObject $user, $isActive) {
			$user->setValue('is_activated', $isActive);
		}

		/**
		 * Возвращает настройки модуля "Пользователи".
		 * Если передано ключевое слово "do" в $_REQUEST['param0'],
		 * то сохраняет переданные настройки.
		 */
		public function config() {
			$umiRegistry = Service::Registry();
			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$umiNotificationInstalled = cmsController::getInstance()
				->isModule('umiNotifications');

			$params = [
				'config' => [
					'guide:def_group' => [
						'type-id' => $umiObjectTypes->getTypeIdByGUID('users-users'),
						'value' => null
					],

					'boolean:without_act' => null,
					'boolean:check_csrf_on_user_update' => null,
					'boolean:pages_permissions_changing_enabled_on_add' => null,
					'boolean:pages_permissions_changing_enabled_on_edit' => null,
					'boolean:require_current_password' => null
				]
			];

			if ($umiNotificationInstalled) {
				$params['config']['boolean:use-umiNotifications'] = null;
			}

			if ($this->isSaveMode()) {
				$params = $this->expectParams($params);
				$umiRegistry->set('//modules/users/def_group', $params['config']['guide:def_group']);
				$umiRegistry->set('//modules/users/without_act', $params['config']['boolean:without_act']);
				$umiRegistry->set(
					'//modules/users/check_csrf_on_user_update',
					$params['config']['boolean:check_csrf_on_user_update']
				);
				$umiRegistry->set(
					'//modules/users/pages_permissions_changing_enabled_on_add',
					$params['config']['boolean:pages_permissions_changing_enabled_on_add']
				);
				$umiRegistry->set(
					'//modules/users/pages_permissions_changing_enabled_on_edit',
					$params['config']['boolean:pages_permissions_changing_enabled_on_edit']
				);

				if ($umiNotificationInstalled) {
					$umiRegistry->set('//modules/users/use-umiNotifications', $params['config']['boolean:use-umiNotifications']);
				}

				$umiRegistry->set(
					'//modules/users/require_current_password',
					$params['config']['boolean:require_current_password']
				);
				$this->chooseRedirect();
			}

			$params['config']['guide:def_group']['value'] = $umiRegistry->get('//modules/users/def_group');
			$params['config']['boolean:without_act'] = $umiRegistry->get('//modules/users/without_act');
			$params['config']['boolean:check_csrf_on_user_update'] =
				$umiRegistry->get('//modules/users/check_csrf_on_user_update');
			$params['config']['boolean:pages_permissions_changing_enabled_on_add'] =
				$umiRegistry->get('//modules/users/pages_permissions_changing_enabled_on_add');
			$params['config']['boolean:pages_permissions_changing_enabled_on_edit'] =
				$umiRegistry->get('//modules/users/pages_permissions_changing_enabled_on_edit');

			if ($umiNotificationInstalled) {
				$params['config']['boolean:use-umiNotifications'] =
					$umiRegistry->get('//modules/users/use-umiNotifications');
			}

			$params['config']['boolean:require_current_password'] =
				$umiRegistry->get('//modules/users/require_current_password');

			$this->setConfigResult($params);
		}

		/**
		 * Возвращает список пользователей и групп,
		 * кроме супервайзера и его группы
		 * @return array
		 * @throws coreException
		 * @throws selectorException
		 */
		public function getPermissionsOwners() {
			$this->module->flushAsXML('getPermissionsOwners');

			$objectTypes = umiObjectTypesCollection::getInstance();
			$groupTypeId = $objectTypes->getTypeIdByHierarchyTypeName('users', 'users');

			$systemUsersPermissions = Service::SystemUsersPermissions();
			$svGroupId = $systemUsersPermissions->getSvGroupId();
			$svId = $systemUsersPermissions->getSvUserId();

			$restrict = [$svId, $svGroupId];

			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('users', 'users');
			$sel->types('hierarchy-type')->name('users', 'user');
			$sel->limit(0, 15);
			selectorHelper::detectFilters($sel);

			$items = [];
			foreach ($sel as $object) {
				if (in_array($object->id, $restrict)) {
					continue;
				}
				$usersList = [];

				/** @var iUmiObject $object */
				if ($object->getTypeId() == $groupTypeId) {
					$users = new selector('objects');
					$users->types('object-type')->name('users', 'user');
					$users->where('groups')->equals($object->id);
					$users->limit(0, 5);
					foreach ($users as $user) {
						$usersList[] = [
							'attribute:id' => $user->id,
							'attribute:name' => $user->name,
							'xlink:href' => $user->xlink
						];
					}

					$type = 'group';
				} else {
					$type = 'user';
				}

				$items[] = [
					'attribute:id' => $object->id,
					'attribute:name' => $object->name,
					'attribute:type' => $type,
					'xlink:href' => $object->xlink,
					'nodes:user' => $usersList
				];
			}

			return [
				'list' => [
					'nodes:owner' => $items
				]
			];
		}

		/**
		 * Проверяет, что действия над объектом
		 * супервайзера делает супервайзер
		 * @param int $userId проверяемый объект, предположительно супервайзер
		 * @throws expectObjectException
		 * @throws publicAdminException
		 */
		public function checkSv($userId) {
			$user = $this->expectObject($userId, true, true);
			$currentUserId = Service::Auth()->getUserId();
			$umiPermissions = permissionsCollection::getInstance();

			if ($umiPermissions->isSv($user->getId()) && !$umiPermissions->isSv($currentUserId)) {
				throw new publicAdminException(getLabel('error-break-action-with-sv'));
			}
		}

		/**
		 * Возвращает количество пользователей всего или в заданной группе
		 * @param bool|int $groupId идентификатор группы
		 * @return Int
		 * @throws coreException
		 * @throws publicException
		 */
		public function getGroupUsersCount($groupId = false) {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$userObjectTypeId = $objectTypes->getTypeIdByHierarchyTypeName('users', 'user');
			$userObjectType = $objectTypes->getType($userObjectTypeId);

			if (!$userObjectType instanceof iUmiObjectType) {
				throw new publicException("Can't load user object type");
			}

			$sel = new selector('objects');
			$sel->types('object-type')->id($userObjectTypeId);
			$sel->option('return')->value('count');

			if ($groupId !== false) {
				if ($groupId != 0) {
					$sel->where('groups')->equals($groupId);
				} else {
					$sel->where('groups')->isnull();
				}
			}

			return $sel->result();
		}

		/**
		 * Возвращает настройки для формирования табличного контрола
		 * @param string $param контрольный параметр
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {

			if ($param == 'groups' || $param === 'users') {
				$loadMethod = 'groups_list';
				$type = 'users';
				$default = 'name[400px]';
			} else {
				$loadMethod = 'users_list/' . $param;
				$type = 'user';
				$default = 'name[400px]|fname[250px]|lname[250px]|e-mail[250px]|groups[250px]|is_activated[250px]';
			}

			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'users',
						'#__name' => $loadMethod
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'users',
						'#__name' => 'del',
						'aliases' => 'tree_delete_element,delete,del'
					],
					[
						'title' => getLabel('smc-activity'),
						'module' => 'users',
						'#__name' => 'activity',
						'aliases' => 'tree_set_activity,activity'
					],
					[
						'title' => getLabel('smc-copy'),
						'module' => 'content',
						'#__name' => 'tree_copy_element'
					],
					[
						'title' => getLabel('smc-move'),
						'module' => 'content',
						'#__name' => 'move'
					],
					[
						'title' => getLabel('smc-change-template'),
						'module' => 'content',
						'#__name' => 'change_template'
					],
				],
				'types' => [
					[
						'common' => 'true',
						'id' => $type
					]
				],
				'stoplist' => [
					'avatar',
					'userpic',
					'user_settings_data',
					'user_dock',
					'orders_refs',
					'activate_code',
					'password',
					'last_request_time',
					'login', 'is_online',
					'delivery_addresses',
					'messages_count'
				],
				'default' => $default
			];
		}

		/**
		 * Получает сожержимое дока
		 * @param bool|int $userId идентификатор пользователя
		 * @return mixed|void
		 */
		public function getFavourites($userId = false) {
			if (!$userId) {
				$userId = getRequest('param0');
			}
			$objects = umiObjectsCollection::getInstance();
			$permissions = permissionsCollection::getInstance();
			$regedit = Service::Registry();
			$user = $objects->getObject($userId);

			if (!$user instanceof iUmiObject) {
				return;
			}

			$userDockModules = explode(',', $user->user_dock);

			//Получаем если есть содержимое дока из настроек пользователя
			$settings_data = $user->user_settings_data;
			$settings_data_arr = unserialize($settings_data);

			if (isset($settings_data_arr['dockItems']) && isset($settings_data_arr['dockItems']['common'])) {
				$userDockModules = explode(';', $settings_data_arr['dockItems']['common']);
			}

			$items = [];
			foreach ($userDockModules as $moduleName) {
				if ($moduleName == '') {
					continue;
				}
				if (!$regedit->get('/modules/' . $moduleName)) {
					continue;
				}

				if (!$permissions->isAllowedModule(false, $moduleName)) {
					continue;
				}

				$items[] = users::parseTemplate('', [
					'attribute:id' => $moduleName,
					'attribute:label' => getLabel('module-' . $moduleName)
				]);
			}

			return users::parseTemplate('', [
				'subnodes:items' => $items
			]);
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
		public function permissions($module = '', $method = '', $element_id = false, $parent_id = false) {
			if (!$module && !$method && !$element_id && !$parent_id) {
				return '';
			}

			$objectsCollection = umiObjectsCollection::getInstance();
			$permissions = permissionsCollection::getInstance();

			$perms_users = [];
			$perms_groups = [];
			if ($element_id || $parent_id) {
				$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('users', 'user');
				$records = $permissions->getRecordedPermissions($element_id ?: $parent_id);
				foreach ($records as $id => $level) {
					$owner = $objectsCollection->getObject($id);
					if (!$owner) {
						continue;
					}
					if ($owner->getTypeId() == $typeId) {
						$ownerGroupsIds = $owner->getValue('groups');
						if (is_array($ownerGroupsIds)) {
							foreach ($ownerGroupsIds as $groupId) {
								$groupLevel = $permissions->isAllowedObject($groupId, $element_id ?: $parent_id);
								foreach ($groupLevel as $i => $l) {
									$level |= pow(2, $i) * (int) $l;
								}
							}
						}

						$perms_users[] = [
							'attribute:id' => $owner->getGUID() ?: $owner->getId(),
							'attribute:login' => $owner->getValue('login'),
							'attribute:access' => $level
						];
					} else {
						$perms_groups[] = [
							'attribute:id' => $owner->getGUID() ?: $owner->getId(),
							'attribute:title' => $owner->getValue('nazvanie'),
							'attribute:access' => $level
						];
					}
				}
			} else {
				$auth = Service::Auth();
				$current_user_id = $auth->getUserId();
				$current_user = $objectsCollection->getObject($current_user_id);
				$current_owners = $current_user->getValue('groups');
				if (!is_array($current_owners)) {
					$current_owners = [];
				}
				$current_owners[] = $current_user_id;

				if (!$method) {
					$method = 'page';
				}
				$method_view = $method;
				$method_edit = $method . '.edit';

				$owners = $permissions->getPrivileged([[$module, $method_view], [$module, $method_edit]]);
				$systemUsersPermissions = Service::SystemUsersPermissions();

				$svUserAndGroupIds = [
					$systemUsersPermissions->getSvUserId(),
					$systemUsersPermissions->getSvGroupId()
				];

				foreach ($owners as $ownerId) {
					if (in_array($ownerId, $svUserAndGroupIds)) {
						continue;
					}
					/* @var iUmiObject $owner */
					$owner = selector::get('object')->id($ownerId);
					if (!$owner) {
						continue;
					}

					$r = $e = $c = $d = $m = 0;
					if (in_array($ownerId, $current_owners)) {
						$r = permissionsCollection::E_READ_ALLOWED_BIT;
						$e = permissionsCollection::E_EDIT_ALLOWED_BIT;
						$c = permissionsCollection::E_CREATE_ALLOWED_BIT;
						$d = permissionsCollection::E_DELETE_ALLOWED_BIT;
						$m = permissionsCollection::E_MOVE_ALLOWED_BIT;
					} else {
						$r = $permissions->isAllowedMethod($ownerId, $module, $method_view)
							? permissionsCollection::E_READ_ALLOWED_BIT
							: 0;
						$e = $permissions->isAllowedMethod($ownerId, $module, $method_edit)
							? permissionsCollection::E_EDIT_ALLOWED_BIT
							: 0;
						if ($e) {
							$c = permissionsCollection::E_CREATE_ALLOWED_BIT;
							$d = permissionsCollection::E_DELETE_ALLOWED_BIT;
							$m = permissionsCollection::E_MOVE_ALLOWED_BIT;
						}
					}

					$r = $r & permissionsCollection::E_READ_ALLOWED_BIT;
					$e = $e & permissionsCollection::E_EDIT_ALLOWED_BIT;
					$c = $c & permissionsCollection::E_CREATE_ALLOWED_BIT;
					$d = $d & permissionsCollection::E_DELETE_ALLOWED_BIT;
					$m = $m & permissionsCollection::E_MOVE_ALLOWED_BIT;

					/* @var iUmiObjectType $ownerObjectType */
					$ownerObjectType = selector::get('object-type')->id($owner->getTypeId());
					$ownerType = $ownerObjectType->getMethod();

					if ($ownerType == 'user') {
						$perms_users[] = [
							'attribute:id' => $owner->getGUID() ?: $owner->getId(),
							'attribute:login' => $owner->getValue('login'),
							'attribute:access' => $r + $e + $c + $d + $m
						];
					} else {
						$perms_groups[] = [
							'attribute:id' => $owner->getGUID() ?: $owner->getId(),
							'attribute:title' => $owner->getName(),
							'attribute:access' => $r + $e + $c + $d + $m
						];
					}
				}
			}

			return users::parseTemplate('', [
				'users' => ['nodes:user' => $perms_users],
				'groups' => ['nodes:group' => $perms_groups]
			]);
		}

		/**
		 * Возвращает права пользователя|группы на страницу
		 * @param int $id id пользователя|группы
		 * @param int $element_id id страницы
		 * @return array
		 */
		public function getUserPermissions($id, $element_id) {
			$allow = permissionsCollection::getInstance()->isAllowedObject($id, $element_id);
			$permission =
				((int) $allow[permissionsCollection::E_READ_ALLOWED] * permissionsCollection::E_READ_ALLOWED_BIT) +
				((int) $allow[permissionsCollection::E_EDIT_ALLOWED] * permissionsCollection::E_EDIT_ALLOWED_BIT) +
				((int) $allow[permissionsCollection::E_CREATE_ALLOWED] * permissionsCollection::E_CREATE_ALLOWED_BIT) +
				((int) $allow[permissionsCollection::E_DELETE_ALLOWED] * permissionsCollection::E_DELETE_ALLOWED_BIT) +
				((int) $allow[permissionsCollection::E_MOVE_ALLOWED] * permissionsCollection::E_MOVE_ALLOWED_BIT);
			return ['user' => ['attribute:id' => $id, 'node:name' => $permission]];
		}

		/**
		 * Возвращает права пользователя|группы на модули, группы методов и домены системы
		 * @param int|bool $ownerId id пользователя|группы, если не передан - вернет права гостя
		 * @return array
		 */
		public function choose_perms($ownerId = false) {
			$regedit = Service::Registry();
			$domainsCollection = Service::DomainCollection();
			$permissions = permissionsCollection::getInstance();

			if ($ownerId === false) {
				$ownerId = (int) Service::SystemUsersPermissions()
					->getGuestUserId();
			}

			$restrictedModules = ['autoupdate'];

			$modules_arr = [];
			$modules_list = $regedit->getList('//modules');

			foreach ($modules_list as $md) {
				list ($module_name) = $md;

				if (in_array($module_name, $restrictedModules)) {
					continue;
				}

				$func_list = array_keys($permissions->getStaticPermissions($module_name));
				if (!system_is_allowed($module_name)) {
					continue;
				}

				$module_label = getLabel('module-' . $module_name);
				$is_allowed_module = $permissions->isAllowedModule($ownerId, $module_name);

				$options_arr = [];
				if (is_array($func_list)) {
					foreach ($func_list as $method_name) {
						if (!system_is_allowed($module_name, $method_name)) {
							continue;
						}

						$is_allowed_method = $permissions->isAllowedMethod($ownerId, $module_name, $method_name);

						$option_arr = [];
						$option_arr['attribute:name'] = $method_name;
						$option_arr['attribute:label'] =
							getLabel('perms-' . $module_name . '-' . $method_name, $module_name);
						$option_arr['attribute:access'] = (int) $is_allowed_method;
						$options_arr[] = $option_arr;
					}
				}

				$module_arr = [];
				$module_arr['attribute:name'] = $module_name;
				$module_arr['attribute:label'] = $module_label;
				$module_arr['attribute:access'] = (int) $is_allowed_module;
				$module_arr['nodes:option'] = $options_arr;
				$modules_arr[] = $module_arr;
			}

			$domains_arr = [];
			$domains = $domainsCollection->getList();
			/* @var iDomain $domain */
			foreach ($domains as $domain) {
				$domain_arr = [];
				$domain_arr['attribute:id'] = $domain->getId();
				$domain_arr['attribute:host'] = $domain->getHost();
				$domain_arr['attribute:access'] = $permissions->isAllowedDomain($ownerId, $domain->getId());
				$domains_arr[] = $domain_arr;
			}

			$result_arr = [];
			$result_arr['domains']['nodes:domain'] = $domains_arr;
			$result_arr['nodes:module'] = $modules_arr;

			return $result_arr;
		}

		/** @deprecated */
		public function users_list_all() {
			$this->redirect($this->module->pre_lang . '/admin/users/users_list/');
		}
	}
