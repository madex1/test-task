<?php

	use UmiCms\Service;

	/**
	 * Класс пользователей и их групп
	 * Модуль отвечает за:
	 * 1) Авторизацию;
	 * 2) Регистрацию;
	 * 3) Подтверждение регистрации;
	 * 3) Восставновление пароля;
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_polzovateli/
	 */
	class users extends def_module {

		/** @var string $user_login логин текущего пользователя */
		public $user_login = '%users_anonymous_login%';

		/** @var int $user_id идентификатор текущего пользователя */
		public $user_id;

		/** @var string $user_fullname полное имя текущего пользователя */
		public $user_fullname = '%users_anonymous_fullname%';

		/** @var array|string $groups группы текущего пользователя */
		public $groups = '';

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

		/**
		 * Включена проверка текущего пароля пользователя при изменении полей группы "idetntify_data"
		 * @var bool
		 */
		protected $requireCurrentPassword = false;

		/**
		 * Конструктор
		 * @throws coreException
		 */
		public function __construct() {
			parent::__construct();

			$umiRegistry = Service::Registry();
			$this->changePagesPermissionsOnEdit = (bool) $umiRegistry->get(
				'//modules/users/pages_permissions_changing_enabled_on_edit'
			);
			$this->changePagesPermissionsOnAdd = (bool) $umiRegistry->get(
				'//modules/users/pages_permissions_changing_enabled_on_add'
			);
			$this->requireCurrentPassword = (bool) $umiRegistry->get('//modules/users/require_current_password');

			if (Service::Request()->isAdmin()) {
				$this->initTabs()
					->includeAdminClasses();
			}

			$this->includeCommonClasses();
			$userId = Service::Auth()->getUserId();
			$this->initUser($userId);
		}

		/**
		 * Создает вкладки административной панели модуля
		 * @return $this
		 */
		public function initTabs() {
			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('users_list');
				$commonTabs->add('groups_list');
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('UsersAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('UsersCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('UsersMacros');

			$this->loadSiteExtension();

			$this->__loadLib('handlers.php');
			$this->__implement('UsersHandlers');

			$this->__loadLib('customMacros.php');
			$this->__implement('UsersCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Включена ли проверка текущего пароля пользователя при изменении полей группы "idetntify_data"
		 * @return bool
		 */
		public function requireCurrentPassword() {
			return $this->requireCurrentPassword;
		}

		/**
		 * Проверяет авторизован ли пользователь
		 * @return bool
		 * @throws coreException
		 */
		public function is_auth() {
			return Service::Auth()->isAuthorized();
		}

		/**
		 * Экранирует строку и возвращает результат
		 * @param string $stringVariable строка
		 * @return string
		 */
		public static function protectStringVariable($stringVariable = '') {
			$stringVariable = htmlspecialchars($stringVariable);
			return $stringVariable;
		}

		/**
		 * Обновляет время и дату последнего посещения авторизованного пользователя
		 * @param $user_id
		 * @return bool
		 * @throws coreException
		 */
		public function updateUserLastRequestTime($user_id) {
			$config = mainConfiguration::getInstance();
			$calculateUserLastRequestTime = (int) $config->get('modules', 'users.calculate-last-request-time');

			if ($calculateUserLastRequestTime === 0) {
				return false;
			}

			if ($user_id == Service::SystemUsersPermissions()->getGuestUserId()) {
				return false;
			}

			$umiObjectsCollection = umiObjectsCollection::getInstance();
			$user_object = $umiObjectsCollection->getObject($user_id);

			if (!$user_object instanceof iUmiObject) {
				return false;
			}

			if (Service::Request()->isNotAdmin()) {
				$time = time();

				$lastRequestTime = $user_object->getValue('last_request_time');

				if ($lastRequestTime + 60 < $time) {
					$user_object->setValue('last_request_time', $time);
					$user_object->commit();
				}
			}

			return true;
		}

		/**
		 * Возвращает ссылку на страницу редактирования объекта
		 * @param int $objectId идентификатор объекта
		 * @param bool $type контрольный параметр
		 * @return string
		 */
		public function getObjectEditLink($objectId, $type = false) {
			return $this->getEditLink($objectId, $type = false);
		}

		/**
		 * Проверяет e-mail на уникальность
		 * @param string $email - проверяемый e-mail
		 * @param integer|bool $userId - id редактируемого пользователя
		 * @return boolean true если e-mail уникален или используется этим пользователем или не задан, false если e-mail
		 *     используется другим пользователем
		 * @throws selectorException
		 */
		public function checkIsUniqueEmail($email, $userId = false) {
			if (!$email) {
				return true;
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'user');
			$sel->where('e-mail')->equals($email);
			$sel->limit(0, 1);

			if ($sel->first()) {
				return ($userId !== false) ? ($sel->first()->id == $userId) : false;
			}

			return true;
		}

		/**
		 * Проверяет логин на уникальность
		 * @param string $login - проверяемый логин
		 * @param integer|bool $userId - id редактируемого пользователя
		 * @return boolean true если логин уникален или используется этим пользователем или не задан, false если логин
		 *     используется другим пользователем
		 * @throws selectorException
		 */
		public function checkIsUniqueLogin($login, $userId = false) {
			if (!$login) {
				return true;
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'user');
			$sel->where('login')->equals($login);
			$sel->limit(0, 1);

			if ($sel->first()) {
				return ($userId !== false) ? ($sel->first()->id == $userId) : false;
			}

			return true;
		}

		/**
		 * Фильтрует значение логина и проверяет его
		 * @param string $login Проверяемое имя пользлвателя
		 * @param integer|bool $userId - id редактируемого пользователя
		 * @param boolean $public Режим проверки (из публички или из админки)
		 * @return string | false $valid отфильтрованный логин или false если логин не валиден
		 * @throws selectorException
		 */
		public function validateLogin($login, $userId = false, $public = false) {
			$login = trim($login);
			$valid = $login ?: (bool) $login;
			$minLength = 1;
			$fieldName = 'login';

			if (!preg_match("/^\S+$/", $login) && $login) {
				$this->errorAddErrors([
					'message' => 'error-login-wrong-format',
					'strcode' => $fieldName
				]);
				$valid = false;
			}

			if ($public) {
				$minLength = 3;
				if (mb_strlen($login, 'utf-8') > 40) {
					$this->errorAddErrors([
						'message' => 'error-login-long',
						'strcode' => $fieldName
					]);
					$valid = false;
				}
			}

			if (mb_strlen($login, 'utf-8') < $minLength) {
				$this->errorAddErrors([
					'message' => 'error-login-short',
					'strcode' => $fieldName
				]);
				$valid = false;
			}

			if (!$this->checkIsUniqueLogin($login, $userId)) {
				$this->errorAddErrors([
					'message' => 'error-login-exists',
					'strcode' => $fieldName
				]);
				$valid = false;
			}

			return $valid;
		}

		/**
		 * Фильтрует значение пароля и проверяет его, сравнивает при необходимости с подтверждением и логином
		 * @param string $password пароль
		 * @param string $passwordConfirmation подтверждение пароля
		 * @param bool|string $login логин
		 * @param boolean $public Режим проверки (из публички или из админки)
		 * @return false|string $valid отфильтрованный пароль или false если пароль не валиден
		 */
		public function validatePassword($password, $passwordConfirmation = null, $login = false, $public = false) {
			$password = trim($password);
			$isValid = $password ?: false;
			$containsWhitespace = !preg_match("/^\S+$/", $password);
			$fieldName = 'password';

			if ($containsWhitespace) {
				$this->errorAddErrors([
					'message' => 'error-password-wrong-format',
					'strcode' => $fieldName
				]);
				$isValid = false;
			}

			if ($login && ($password == trim($login))) {
				$this->errorAddErrors([
					'message' => 'error-password-equal-login',
					'strcode' => $fieldName
				]);
				$isValid = false;
			}

			$minLength = 1;

			if ($public) {
				$minLength = 6;

				/** @var users|UsersMacros $this */
				$isSecure = $this->isPasswordSecure($password);

				if (!$isSecure) {
					$this->errorAddErrors([
						'message' => 'error-password-not-secure',
						'strcode' => $fieldName
					]);
					$isValid = false;
				}

				if ($passwordConfirmation !== null && $password != $passwordConfirmation) {
					$this->errorAddErrors([
						'message' => 'error-password-wrong-confirm',
						'strcode' => $fieldName
					]);
					$isValid = false;
				}
			}

			if (mb_strlen($password, 'utf-8') < $minLength) {
				$this->errorAddErrors([
					'message' => 'error-password-short',
					'strcode' => $fieldName
				]);
				$isValid = false;
			}

			return $isValid;
		}

		/**
		 * Фильтрует значение e-mail'а и проверяет его
		 * @param string $email
		 * @param bool|int $userId - id редактируемого пользователя
		 * @param boolean $requireActivation
		 * @return bool|string $valid отфильтрованный e-mail, false если e-mail не валиден, true если e-mail не
		 *     указан, а активация не требуется
		 */
		public function validateEmail($email, $userId = false, $requireActivation = true) {
			$email = mb_strtolower(trim($email));
			$valid = $email ?: false;
			$fieldName = 'email';

			if ($email) {
				if (!umiMail::checkEmail($email)) {
					$this->errorAddErrors([
						'message' => 'error-email-wrong-format',
						'strcode' => $fieldName
					]);
					$valid = false;
				}

				if (!$this->checkIsUniqueEmail($email, $userId)) {
					$this->errorAddErrors([
						'message' => 'error-email-exists',
						'strcode' => $fieldName
					]);
					$valid = false;
				}
			} elseif ($requireActivation) {
				$this->errorAddErrors([
					'message' => 'error-email-required',
					'strcode' => $fieldName
				]);
				$valid = false;
			} else {
				$valid = '';
			}

			return $valid;
		}

		/**
		 * Восстанавливает и авторизует пользователя на основе данных сессии.
		 * Используется в связке с __emarket_admin::editOrderAsUser().
		 * @param bool $noRedirect не производить редирект при успешном выполнении
		 * @return bool
		 * @throws coreException
		 */
		public function restoreUser($noRedirect = false) {
			Service::Auth()->loginUsingPreviousUserId();

			if ($noRedirect) {
				return true;
			}

			$this->redirect($this->pre_lang . '/admin/emarket/orders/');
		}

		/**
		 * Инициализирует пользователя
		 * @param int $userId идентификатор пользователя
		 * @throws coreException
		 */
		private function initUser($userId) {
			if ($userId === Service::SystemUsersPermissions()->getGuestUserId()) {
				$this->user_login = '%users_anonymous_login%';
				$this->user_fullname = '%users_anonymous_fullname%';
				$this->groups = [];
			} else {
				$user = umiObjectsCollection::getInstance()->getObject($userId);

				if (!$user instanceof iUmiObject) {
					throw new coreException('Incorrect user id given');
				}

				$this->user_login = $user->getValue('login');
				$this->user_fullname = $user->getValue('fname') . ' ' . $user->getValue('lname');
				$this->groups = $user->getValue('groups');

				$this->updateUserLastRequestTime($userId);
			}

			$this->user_id = $userId;
			$this->dropCacheInitData();
		}

		/**
		 * Активирует пользователя
		 * @param int $userId идентификатор пользователя
		 * @throws coreException
		 * @throws baseException
		 */
		public function activateUser($userId) {
			$user = umiObjectsCollection::getInstance()->getObject($userId);
			$user->setValue('is_activated', 1);
			$user->setValue('activate_code', md5(uniqid(mt_rand(), true)));
			$user->commit();

			Service::Auth()->loginUsingId($userId);

			$oEventPoint = new umiEventPoint('users_activate');
			$oEventPoint->setMode('after');
			$oEventPoint->setParam('user_id', $userId);
			$this->setEventPoint($oEventPoint);
		}

		/**
		 * Возвращает адрес виджета Loginza
		 * @return array|string
		 * @throws coreException
		 */
		public function getLoginzaProvider() {
			$loginzaAPI = new loginzaAPI();

			$result = [];
			foreach ($loginzaAPI->getProvider() as $k => $v) {
				$result['providers']['nodes:provider'][] = ['attribute:name' => $k, 'attribute:title' => $v];
			}

			$result ['widget_url'] = $loginzaAPI->getWidgetUrl() .
				'&providers_set=google,yandex,mailru,vkontakte,facebook,twitter,loginza,rambler,lastfm,myopenid,openid,mailruapi';

			if (self::isXSLTResultMode()) {
				return $result;
			}

			return $result['widget_url'];
		}

		/**
		 * На сайте ли сейчас заданный пользователь
		 * @param bool|int $userId идентификатор пользователя
		 * @param int $onlineTimeout сколько секунд пользователь будет онлайн
		 * @return bool
		 * @throws publicException
		 */
		public function isUserOnline($userId = false, $onlineTimeout = 900) {
			if ($userId === false) {
				throw new publicException('This macros need user id given.');
			}

			$user = umiObjectsCollection::getInstance()
				->getObject($userId);
			if (!$user instanceof iUmiObject) {
				throw new publicException("User #{$userId} doesn't exist.");
			}

			$lastRequestTime = $user->getValue('last_request_time');
			$isOnline = ($lastRequestTime + $onlineTimeout) >= time();

			$user->setValue('is_online', $isOnline);
			$user->commit();

			return $isOnline;
		}

		/**
		 * Сохраняет права пользователя на страницу
		 * @param int $element_id id страницы
		 */
		public function setPerms($element_id) {
			$permissions = permissionsCollection::getInstance();

			if (!getRequest('perms_read') && !getRequest('perms_edit') && !getRequest('perms_create') &&
				!getRequest('perms_delete') && !getRequest('perms_move') &&
				/* Note this argument. It's important' */
				getRequest('default-permissions-set')) {

				$permissions->setDefaultPermissions($element_id);
				return;
			}

			if (!getRequest('perms_read') && !getRequest('perms_edit') && !getRequest('perms_create') &&
				!getRequest('perms_delete') && !getRequest('permissions-sent')
			) {
				return;
			}

			$perms_read = ($t = getRequest('perms_read')) ? $t : [];
			$perms_edit = ($t = getRequest('perms_edit')) ? $t : [];
			$perms_create = ($t = getRequest('perms_create')) ? $t : [];
			$perms_delete = ($t = getRequest('perms_delete')) ? $t : [];
			$perms_move = ($t = getRequest('perms_move')) ? $t : [];

			$permissions->resetElementPermissions($element_id);

			$owners = array_keys($perms_read);
			$owners = array_merge($owners, array_keys($perms_edit));
			$owners = array_merge($owners, array_keys($perms_create));
			$owners = array_merge($owners, array_keys($perms_delete));
			$owners = array_merge($owners, array_keys($perms_move));
			$owners = array_unique($owners);

			foreach ($owners as $owner) {
				$level = 0;
				if (isset($perms_read[$owner])) {
					$level |= 1;
				}
				if (isset($perms_edit[$owner])) {
					$level |= 2;
				}
				if (isset($perms_create[$owner])) {
					$level |= 4;
				}
				if (isset($perms_delete[$owner])) {
					$level |= 8;
				}
				if (isset($perms_move[$owner])) {
					$level |= 16;
				}

				if (is_string($owner)) {
					$owner = umiObjectsCollection::getInstance()->getObjectIdByGUID($owner);
				}

				$permissions->setElementPermissions($owner, $element_id, $level);
			}
		}

		/**
		 * Сохраняет права пользователя|группы при его|ее редактировании или добавлении
		 * через административную панель
		 * @param int $ownerId id пользователя|группы
		 * @param string $mode режим запуска модуля (edit/add)
		 * @throws publicAdminException если передан некорректный $ownerId
		 */
		public function save_perms($ownerId, $mode = 'add') {
			$owner = umiObjectsCollection::getInstance()->getObject($ownerId);

			if (!$owner instanceof iUmiObject) {
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
			$umiPermissions->resetModulesPermissions($ownerId);

			$groups = $owner->getValue('groups');
			$groupsAreDefined = (is_array($groups) && umiCount($groups) > 0);
			$umiRegistry = Service::Registry();
			$guestId = (int) Service::SystemUsersPermissions()->getGuestUserId();

			if (!$groupsAreDefined) {
				$cnt = $umiPermissions->hasUserPermissions($ownerId);

				if (!$cnt) {
					$umiPermissions->copyHierarchyPermissions($guestId, $ownerId);
				}
			}

			$domainPermissions = (array) getRequest('domain');
			$this->saveOwnerDomainPermissions($ownerId, $domainPermissions);

			$defaultGroupId = (int) $umiRegistry->get('//modules/users/def_group');
			$isOwnerNotGuestAndNotDefGroup = ($ownerId != $guestId && $ownerId != $defaultGroupId);

			if ($isOwnerNotGuestAndNotDefGroup) {
				$permittedModulesNames = (array) getRequest('m_perms');
				$this->saveOwnerModulesPermissions($ownerId, $permittedModulesNames);
			}

			$allModulesNames = (array) getRequest('ps_m_perms');
			$allModulesNames = array_keys($allModulesNames);
			$modulesMethodsGroups = $_REQUEST;

			$this->saveOwnerModulesGroupsPermissions($ownerId, $allModulesNames, $modulesMethodsGroups);

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
		 * Возвращает случайный пароль
		 * @param int $length длина пароля
		 * @return string
		 */
		public function getRandomPassword($length = 12) {
			$password = '';

			if (function_exists('openssl_random_pseudo_bytes')) {
				$password = base64_encode(openssl_random_pseudo_bytes($length));
				$password = mb_substr($password, 0, $length);
			}

			/** @var users|UsersMacros $this */
			while (!$this->isPasswordSecure($password)) {
				$password = self::generateRandomPassword($length);
			}

			return $password;
		}

		/**
		 * Генерирует случайный пароль
		 * @param int $length требуемая длина пароля
		 * @return string
		 */
		private static function generateRandomPassword($length) {
			$alphabet = '$#@^&!1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
			$size = mb_strlen($alphabet);
			$password = '';

			for ($i = 0; $i < $length; $i++) {
				$index = mt_rand(0, $size - 1);
				$password .= $alphabet[$index];
			}

			return $password;
		}

		/**
		 * Создает автора на основе данных зарегистрированного пользователя
		 * и возвращает его идентификатор
		 * @param int $user_id идентификатор пользователя
		 * @return bool|int
		 * @throws coreException
		 * @throws selectorException
		 */
		public function createAuthorUser($user_id) {
			$objects = umiObjectsCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();

			if ($objects->isExists($user_id) === false) {
				return false;
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'author');
			$sel->where('user_id')->equals($user_id);
			$sel->limit(0, 1);

			if ($sel->first()) {
				return $sel->first()->id;
			}

			$user_object = $objects->getObject($user_id);
			$user_name = $user_object->getName();

			$object_type_id = $objectTypes->getTypeIdByHierarchyTypeName('users', 'author');
			$author_id = $objects->addObject($user_name, $object_type_id);
			$author = $objects->getObject($author_id);
			$author->is_registrated = true;
			$author->user_id = $user_id;
			$author->commit();

			return $author_id;
		}

		/**
		 * Создает автора на основе данных незарегистрированного пользователя
		 * и возвращает его идентификатор
		 * @param string $nick псевдоним пользователя
		 * @param string $email почтовый ящик пользователя
		 * @param string $ip ip адрес с которого зашел пользователь
		 * @return int
		 * @throws coreException
		 * @throws selectorException
		 */
		public function createAuthorGuest($nick, $email, $ip) {
			$objects = umiObjectsCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();

			$nick = trim($nick);
			$email = trim($email);

			if (!$nick) {
				$nick = getLabel('author-anonymous');
			}
			if (!$email) {
				$email = getServer('REMOTE_ADDR');
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'author');
			$sel->where('email')->equals($email);
			$sel->where('nickname')->equals($nick);
			$sel->where('ip')->equals($ip);
			$sel->limit(0, 1);

			if ($sel->first()) {
				return $sel->first()->id;
			}

			$user_name = $nick . " ({$email})";

			$object_type_id = $objectTypes->getTypeIdByHierarchyTypeName('users', 'author');
			$author_id = $objects->addObject($user_name, $object_type_id);
			$author = $objects->getObject($author_id);
			$author->setName($user_name);
			$author->is_registrated = false;
			$author->nickname = $nick;
			$author->email = $email;
			$author->ip = $ip;
			$author->commit();

			return $author_id;
		}

		/**
		 * Возвращает настройки пользователя
		 * @return array
		 * @throws coreException
		 */
		public function loadUserSettings() {
			$objects = umiObjectsCollection::getInstance();

			$user_id = Service::Auth()->getUserId();
			$user = $objects->getObject($user_id);

			if (!$user instanceof iUmiObject) {
				throw new coreException("Can't get current user with id #{$user_id}");
			}

			$settings_data = $user->getValue('user_settings_data');
			$settings_data_arr = unserialize($settings_data);

			if (!is_array($settings_data_arr)) {
				$settings_data_arr = [];
			}

			$block_arr = [];
			$items = [];

			foreach ($settings_data_arr as $key => $data) {
				$item_arr = [];
				$item_arr['attribute:key'] = (string) $key;

				$values_arr = [];
				foreach ($data as $tag => $value) {
					$value_arr = [];
					$value_arr['attribute:tag'] = (string) $tag;

					if ($key == 'dockItems' && $tag == 'common') {
						$value = $this->filterModulesList($value);
					}

					$value_arr['node:value'] = (string) $value;
					$values_arr[] = $value_arr;
				}
				$item_arr['nodes:value'] = $values_arr;
				$items[] = $item_arr;
			}
			$block_arr['items']['nodes:item'] = $items;
			return $block_arr;
		}

		/**
		 * Отфильтровывает несуществующие модули
		 * @param string $modules список названий модулей, разделенных ";"
		 * @return null|string
		 */
		public function filterModulesList($modules) {
			if (!is_string($modules)) {
				return null;
			}

			$dockModules = explode(';', $modules);
			$cmsController = cmsController::getInstance();
			$systemModules = $cmsController->getModulesList();

			$result = [];
			foreach ($dockModules as $moduleName) {
				if (in_array($moduleName, $systemModules)) {
					$result[] = $moduleName;
				}
			}

			return implode(';', $result);
		}

		/**
		 * Сохраняет настройки пользователя
		 * @throws coreException
		 * @throws publicException
		 */
		public function saveUserSettings() {
			$this->flushAsXML('saveUserSettings');

			$objects = umiObjectsCollection::getInstance();
			$user_id = Service::Auth()->getUserId();
			$user = $objects->getObject($user_id);

			if (!$user instanceof iUmiObject) {
				throw new coreException("Can't get current user with id #{$user_id}");
			}

			$settings_data = $user->getValue('user_settings_data');
			$settings_data = unserialize($settings_data);
			if (!is_array($settings_data)) {
				$settings_data = [];
			}

			$key = getRequest('key');
			$value = getRequest('value');
			$tags = (Array) getRequest('tags');

			if (!$key) {
				throw new publicException('You should pass "key" parameter to this resourse');
			}

			if (umiCount($tags) == 0) {
				$tags[] = 'common';
			}

			foreach ($tags as $tag) {
				if ($value) {
					$settings_data[$key][$tag] = $value;
				} else {
					if (isset($settings_data[$key][$tag])) {
						unset($settings_data[$key][$tag]);

						if (umiCount($settings_data[$key]) == 0) {
							unset($settings_data[$key]);
						}
					}
				}
			}

			$user->setValue('user_settings_data', serialize($settings_data));
			$user->commit();
		}

		/**
		 * Деавторизует пользователя
		 * @param bool $makeRedirect нужно ли произвести редирект при успешной деавторизации
		 * @throws coreException
		 */
		public function logout($makeRedirect = true) {
			Service::Auth()->logout();

			if ($makeRedirect) {
				$redirect_url = getRequest('redirect_url');
				$this->redirect($redirect_url);
			}
		}

		/**
		 * Возвращает ссылку на страницу редактирования объекта
		 * @param int $objectId идентификатор объекта
		 * @param bool $type контрольный параметр
		 * @return string
		 */
		public function getEditLink($objectId, $type = false) {
			if ($type == 'author') {
				return $this->pre_lang . '/admin/data/guide_item_edit/' . $objectId . '/';
			}
			return $this->pre_lang . '/admin/users/edit/' . $objectId . '/';
		}

		/**
		 * Сохраняет права пользователя|группы на сайты (домены)
		 * @param int $ownerId идентификатор пользователя или группы
		 * @param array $domainPermissions права на сайты:
		 *
		 *    [
		 *        ид домена => 1|0
		 *    ]
		 */
		protected function saveOwnerDomainPermissions($ownerId, array $domainPermissions) {
			$umiPermissions = permissionsCollection::getInstance();

			foreach ($domainPermissions as $domainId => $domainAllowed) {

				if ($domainAllowed == $umiPermissions->isAllowedDomain($ownerId, $domainId)) {
					continue;
				}

				$umiPermissions->setAllowedDomain($ownerId, $domainId, $domainAllowed);
			}
		}

		/**
		 * Сохраняет права пользователя|группы на использование модуля
		 * @param int $ownerId идентификатор пользователя или группы
		 * @param array $permittedModulesNames имена доступных модулей:
		 *
		 *    [
		 *        # => имя модуля
		 *    ]
		 */
		protected function saveOwnerModulesPermissions($ownerId, array $permittedModulesNames) {
			$umiPermissions = permissionsCollection::getInstance();
			$method = false;
			$deletePermissionsIfLowerGuest = false;

			foreach ($permittedModulesNames as $moduleName) {

				if ($umiPermissions->isAllowedModule($ownerId, $moduleName)) {
					continue;
				}

				$umiPermissions->setModulesPermissions(
					$ownerId, $moduleName, $method, $deletePermissionsIfLowerGuest
				);
			}
		}

		/**
		 * Сохраняет права пользователя|группы на использование групп прав модулей
		 * @param int $ownerId идентификатор пользователя или группы
		 * @param array $modulesNames имена модулей:
		 *
		 *    [
		 *        # => имя модуля
		 *    ]
		 *
		 * @param array $methodsGroups группы прав модулей:
		 *
		 *    [
		 *        имя модуля => [
		 *            имя группы 1 => любое значение
		 *            ...
		 *            имя группы n => любое значение
		 *        ]
		 *    ]
		 */
		protected function saveOwnerModulesGroupsPermissions($ownerId, array $modulesNames, array $methodsGroups) {
			$umiPermissions = permissionsCollection::getInstance();
			$deletePermissionsIfLowerGuest = false;

			foreach ($modulesNames as $moduleName) {
				if (!isset($methodsGroups[$moduleName]) || !is_array($methodsGroups[$moduleName])) {
					continue;
				}

				$moduleGroups = $methodsGroups[$moduleName];
				$moduleGroupsNames = array_keys($moduleGroups);

				foreach ($moduleGroupsNames as $groupName) {

					if ($umiPermissions->isAllowedMethod($ownerId, $moduleName, $groupName)) {
						continue;
					}

					$umiPermissions->setModulesPermissions(
						$ownerId, $moduleName, $groupName, $deletePermissionsIfLowerGuest
					);
				}
			}
		}

		/** @inheritdoc */
		public function getMailObjectTypesGuidList() {
			return ['users-user'];
		}

		/** @deprecated  */
		public function getVariableNamesForMailTemplates() {
			return [];
		}

		/** @deprecated */
		public function getUserByActivationCode($activationCode) {
			$userId = Service::Auth()->checkCode($activationCode);

			if ($userId !== false) {
				return [$userId];
			}

			return [];
		}

		/** @internal */
		private function dropCacheInitData() {
			umiEventsController::registerEventListener(
				clusterCacheSync::createProfiler()
			);
			umiEventsController::registerEventListener(
				umiMessages::createLogger()
			);
		}
	}
