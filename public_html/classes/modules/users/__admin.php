<?php
	abstract class __users extends baseModuleAdmin {

		public function users_list_all() {
			return $this->users_list(true);
		}

		public function users_list($group_id = false) {
			$this->setDataType('list');
			$this->setActionType('view');
			if ($this->ifNotXmlMode()) {
				return $this->doData();
			}

			$limit = getRequest('per_page_limit');
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'user');
			$sel->limit($offset, $limit);

			if (getRequest('param0') == 'outgroup') {
				$sel->where('groups')->isnull(true);
			} else {
				if ($groupId = $this->expectObjectId('param0')) {
					$sel->where('groups')->equals($groupId);
				}
			}

			$umiPermissions = permissionsCollection::getInstance();

			if (!$umiPermissions->isSv()) {
				$systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();
				$sel->where('id')->notequals($systemUsersPermissions->getSvUserId());
			}

			if ($loginSearch = getRequest('search')) {
				$sel->where('login')->like('%' . $loginSearch . '%');
			}

			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, 'objects');

			$this->setData($data, $sel->length);
			return $this->doData();
		}

		public function groups_list() {
			$this->setDataType('list');
			$this->setActionType('view');
			if ($this->ifNotXmlMode()) {
				return $this->doData();
			}

			$limit = getRequest('per_page_limit');
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'users');

			$umiPermissions = permissionsCollection::getInstance();

			if (!$umiPermissions->isSv()) {
				$systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();
				$sel->where('id')->notequals($systemUsersPermissions->getSvGroupId());
			}

			$sel->limit($offset, $limit);

			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);

			$data = $this->prepareData($sel->result, "objects");
			$this->setData($data, $sel->length);
			return $this->doData();
		}

		public function add() {
			$type = (string) getRequest('param0');
			$mode = (string) getRequest('param1');

			$this->setHeaderLabel('header-users-add-' . $type);
			$inputData = array(
				'type'					=> $type,
				'type-id' 				=> getRequest('type-id'),
				'aliases'				=> array('name' => 'login'),
				'allowed-element-types'	=> array('user', 'users')
			);

			if ($mode == "do") {
				$object = $this->saveAddedObjectData($inputData);
				$permissions = permissionsCollection::getInstance();
				$systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();
				$svGroupId = $systemUsersPermissions->getSvGroupId();

				if (!$permissions->isSv($permissions->getUserId())) {
					$groups = $object->getValue('groups');
					if (in_array($svGroupId, $groups)) {
						unset($groups[array_search($svGroupId, $groups)]);
						$object->setValue('groups', $groups);
					}
				}

				// fill userdock
				$object->setValue('user_dock', 'seo,content,news,blogs20,forum,comments,vote,webforms,photoalbum,dispatches,catalog,emarket,banners,users,stat,exchange,trash');
				$object->commit();

				$this->save_perms($object->getId(), __FUNCTION__);
				$this->chooseRedirect($this->pre_lang . '/admin/users/edit/' . $object->getId() . '/');
			}

			$this->setDataType('form');
			$this->setActionType('create');

			$data = $this->prepareData($inputData, 'object');

			$this->setData($data);
			return $this->doData();
		}

		public function edit() {
			$object = $this->expectObject('param0', true);
			$mode = (string) getRequest('param1');
			$objectId = $object->getId();

			$this->setHeaderLabel('header-users-edit-' . $this->getObjectTypeMethod($object));

			$this->checkSv($objectId);

			$inputData = Array(
				'object' => $object,
				'aliases' => Array('name' => 'login'),
				'allowed-element-types' => Array('users', 'user')
			);

			if ($mode == 'do') {
				if (!def_module::checkHTTPReferer()) {
					$this->errorNewMessage(getLabel('error-users-non-referer'));
					$this->errorPanic();
				}

				$object = $this->saveEditedObjectData($inputData);

				$objectId = $object->getId();

				if (isset($_REQUEST['data'][$objectId]['password'][0])) {
					$password = $_REQUEST['data'][$objectId]['password'][0];
				} else {
					$password = false;
				}

				$permissions = permissionsCollection::getInstance();
				$systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();
				$guestId = $systemUsersPermissions->getGuestUserId();
				$userId = $permissions->getUserId();

				$svUserId = $systemUsersPermissions->getSvUserId();

				if (in_array($object->getId(), array($userId, $guestId, $svUserId))) {
					if (!$object->is_activated) {
						$object->is_activated = true;
						$object->commit();
					}
				}

				$this->save_perms($objectId, __FUNCTION__);
				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('modify');

			$data = $this->prepareData($inputData, 'object');

			$this->setData($data);
			return $this->doData();
		}

		public function del() {
			$objects = getRequest('element');

			if (!is_array($objects)) {
				$objects = Array($objects);
			}

			$systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();
			$svUserId = $systemUsersPermissions->getSvUserId();
			$svGroupId = $systemUsersPermissions->getSvGroupId();

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

				$systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();

				if ($object_id == $systemUsersPermissions->getGuestUserId()) {
					throw new publicAdminException(getLabel('error-guest-user-delete'));
				}

				$regedit = regedit::getInstance();
				if ($object_id == $regedit->getVal('//modules/users/def_group')) {
					throw new publicAdminException(getLabel('error-sv-group-delete'));
				}

				if ($object_id == permissionsCollection::getInstance()->getUserId()) {
					throw new publicAdminException(getLabel('error-delete-yourself'));
				}

				$params = Array(
					'object'		=> $object,
					'allowed-element-types' => Array('user', 'users')
				);

				$this->deleteObject($params);
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($objects, 'objects');
			$this->setData($data);

			return $this->doData();
		}

		public function activity() {
			$objects = getRequest('object');
			if (!is_array($objects)) {
				$objects = Array($objects);
			}
			$is_active = (bool) getRequest('active');
			$systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();
			$svUserId = $systemUsersPermissions->getSvUserId();

			foreach($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);
				$this->checkSv($objectId);

				if (!$is_active) {
					if ($objectId == $svUserId) {
						throw new publicAdminException(getLabel('error-sv-user-activity'));
					}

					$regedit = regedit::getInstance();
					if ($objectId == $regedit->getVal('//modules/users/guest_id')) {
						throw new publicAdminException(getLabel('error-guest-user-activity'));
					}
				}

				$object->setValue('is_activated', $is_active);
				$object->commit();
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($objects, 'objects');
			$this->setData($data);

			return $this->doData();
		}

		public function getPermissionsOwners() {
			$this->flushAsXML('getPermissionsOwners');

			$objects = umiObjectsCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();
			$groupTypeId = $objectTypes->getTypeIdByHierarchyTypeName('users', 'users');

			$systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();
			$svGroupId = $systemUsersPermissions->getSvGroupId();
			$svId = $systemUsersPermissions->getSvUserId();

			$restrict = array($svId, $svGroupId);

			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('users', 'users');
			$sel->types('hierarchy-type')->name('users', 'user');
			$sel->limit(0, 15);
			selectorHelper::detectFilters($sel);

			$items = array();
			foreach ($sel as $object) {
				if (in_array($object->id, $restrict)) {
					continue;
				}
				$usersList = array();

				if ($object->getTypeId() == $groupTypeId) {
					$users = new selector('objects');
					$users->types('object-type')->name('users', 'user');
					$users->where('groups')->equals($object->id);
					$users->limit(0, 5);
					foreach ($users as $user) {
						$usersList[] = array(
							'attribute:id'		=> $user->id,
							'attribute:name'	=> $user->name,
							'xlink:href'		=> $user->xlink
						);
					}

					$type = 'group';
				} else {
					$type = 'user';
				}

				$items[] = array(
					'attribute:id'		=> $object->id,
					'attribute:name'	=> $object->name,
					'attribute:type'	=> $type,
					'xlink:href'		=> $object->xlink,
					'nodes:user'		=> $usersList
				);
			}

			return array(
				'list' => array(
					'nodes:owner' => $items
				)
			);
		}

		public function json_change_dock() {
			$s_dock_panel = getRequest('dock_panel');
			if ($o_users = cmsController::getInstance()->getModule("users")) {
				$i_user_id = $o_users->user_id;
				$o_user = umiObjectsCollection::getInstance()->getObject($i_user_id);
				if ($o_user) {
					$o_user->setValue('user_dock', $s_dock_panel);
					$o_user->commit();
				}
			}
			header('HTTP/1.1 200 OK');
			header('Cache-Control: public, must-revalidate');
			header('Pragma: no-cache');
			header('Date: ' . date('D M j G:i:s T Y'));
			header('Last-Modified: ' . date('D M j G:i:s T Y'));
			header('Content-type: text/javascript');
			exit();
		}

		public function checkSv ($objectId) {
			$object = $this->expectObject($objectId, true, true);
			$perms = permissionsCollection::getInstance();
			$userId = $perms->getUserId();
			$expectSv = $perms->isSv($object->getId());
			if ($perms->isSv ($object->getId()) && !$perms->isSv($userId))	{
				throw new publicAdminException (getLabel('error-break-action-with-sv'));
			}
		}

		public function getGroupUsersCount($groupId = false) {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$userObjectTypeId = $objectTypes->getTypeIdByHierarchyTypeName('users', 'user');
			$userObjectType = $objectTypes->getType($userObjectTypeId);

			if ($userObjectType instanceof umiObjectType == false) {
				throw new publicException("Can't load user object type");
			}

			$sel = new umiSelection;
			$sel->addObjectType($userObjectTypeId);

			if ($groupId !== false) {
				if ($groupId != 0) {
					$sel->addPropertyFilterEqual($userObjectType->getFieldId('groups'), $groupId);
				} else {
					$sel->addPropertyFilterIsNull($userObjectType->getFieldId('groups'));
				}
			}

			return umiSelectionsParser::runSelectionCounts($sel);
		}

		public function getDatasetConfiguration($param = '') {
			if ($param == 'groups' || $param === 'users') {
				$loadMethod = "groups_list";
				$type = 'users';
				$default = 'name[400px]';
			} else {
				$loadMethod = $param ? ('users_list/' . $param) : 'users_list_all';
				$type = 'user';
				$default = 'name[400px]|fname[250px]|lname[250px]|e-mail[250px]|groups[250px]|is_activated[250px]';
			}

			return array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'users', '#__name'=>$loadMethod),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'users', '#__name'=>'del', 'aliases' => 'tree_delete_element,delete,del'),
						array('title'=>getLabel('smc-activity'), 		 'module'=>'users', '#__name'=>'activity', 'aliases' => 'tree_set_activity,activity'),
						array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
						array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'move'),
						array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang')),
					'types' => array(
						array('common' => 'true', 'id' => $type)
					),
					'stoplist' => array('avatar', 'userpic', 'user_settings_data', 'user_dock', 'orders_refs', 'activate_code', 'password', 'last_request_time', 'login', 'is_online', 'delivery_addresses', 'messages_count'),
					'default' => $default
				);
		}

		public function onCreateObject($e) {
			$object = $e->getRef('object');
			$objectType = umiObjectTypesCollection::getInstance()->getType($object->getTypeId());
			if ($objectType->getModule() != 'users' || $objectType->getMethod() != 'user') {
				return;
			}

			if ($e->getMode() == "before") {

				if (!isset($_REQUEST['data']['new']['login'])) {
					$_REQUEST['data']['new']['login'] = $_REQUEST['name'];
				}

				$this->errorSetErrorPage($this->pre_lang . '/admin/users/add/user/');

				$_REQUEST['data']['new']['login'] = $this->validateLogin($_REQUEST['data']['new']['login']);
				$_REQUEST['data']['new']['password'][0] = $this->validatePassword($_REQUEST['data']['new']['password'][0], null, $_REQUEST['data']['new']['login']);
				$_REQUEST['data']['new']['e-mail'] = $this->validateEmail($_REQUEST['data']['new']['e-mail']);
				$object->setName($_REQUEST['data']['new']['login']);

				if ($this->errorHasErrors()) {
					if ($object instanceof umiObject) {
						umiObjectsCollection::getInstance()->delObject($object->getId());
					}
				}
				$this->errorThrow('admin');
			}
		}

		public function onModifyObject(umiEventPoint $e) {
			static $orig_groups = Array();

			$object = $e->getRef('object');
			$objectId = $object->getId();
			$objectType = umiObjectTypesCollection::getInstance()->getType($object->getTypeId());

			if ($objectType->getModule() != 'users' || $objectType->getMethod() != 'user') {
				return;
			}

			if ($e->getMode() == "before") {
				$orig_groups[$objectId] = $object->getValue('groups');

				if (!isset($_REQUEST['data'][$objectId]['login'])) {
					$_REQUEST['data'][$objectId]['login'] = $_REQUEST['name'];
				}

				$this->errorSetErrorPage($this->pre_lang . "/admin/users/edit/{$objectId}/");

				$_REQUEST['data'][$objectId]['login'] = $this->validateLogin($_REQUEST['data'][$objectId]['login'], $objectId);
				if (isset($_REQUEST['data'][$objectId]['password'][0]) && trim($_REQUEST['data'][$objectId]['password'][0])) {
					$_REQUEST['data'][$objectId]['password'][0] = $this->validatePassword($_REQUEST['data'][$objectId]['password'][0], null, $_REQUEST['data'][$objectId]['login']);
				}
				$_REQUEST['data'][$objectId]['e-mail'] = $this->validateEmail($_REQUEST['data'][$objectId]['e-mail'], $objectId);
				$object->setName($_REQUEST['data'][$objectId]['login']);

				$this->errorThrow('admin');
			}

			if ($e->getMode() == 'after') {
				$permissions = permissionsCollection::getInstance();
				$is_sv = $permissions->isSv($permissions->getUserId());
				$systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();
				$svUserId = $systemUsersPermissions->getSvUserId();
				$svGroupId = $systemUsersPermissions->getSvGroupId();

				if ($objectId == $svUserId) {
					$object->setValue('groups', [$svGroupId]);
				} else {
					$groups = $object->getValue('groups');
					if (!$is_sv) {
						if (in_array($svGroupId, $groups) && !in_array($svGroupId, $orig_groups[$objectId])) {
							unset($groups[array_search($svGroupId, $groups)]);
							$object->setValue('groups', $groups);
						} else if (!in_array($svGroupId, $groups) && in_array($svGroupId, $orig_groups[$objectId])){
							$groups[] = $svGroupId;
							$object->setValue('groups', $groups);
						}
					}
				}
				$object->commit();
			}
		}

		public function onModifyPropertyValue(umiEventPoint $e) {
			$object = $e->getRef('entity');

			if ($object instanceof umiHierarchyElement) {
				$object = $object->getObject();
			}

			$objectId = $object->getId();
			$objectType = umiObjectTypesCollection::getInstance()->getType($object->getTypeId());

			if (!$objectType || $objectType->getModule() != 'users' || $objectType->getMethod() != 'user') {
				return;
			}

			if ($e->getMode() == "before") {
				$newValue = &$e->getRef('newValue');

				switch ((string) $e->getParam('property')) {
					case 'name' : {
						$newValue = $this->validateLogin($newValue, $objectId);
						break;
					}

					case 'e-mail' : {
						$newValue = $this->validateEmail($newValue, $objectId);
						break;
					}

					default:
						return;
				}

				$this->errorThrow('xml');
			}

			if ($e->getMode() == "after") {
				switch ((string) $e->getParam('property')) {
					case 'login' : {
						$object->name = (string) $e->getParam('newValue');
						$object->commit();
						break;
					}

					case 'name' : {
						$object->login = (string) $e->getParam('newValue');
						$object->commit();
						break;
					}

					default:
						return;
				}
			}
		}
	};
?>