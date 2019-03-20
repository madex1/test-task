<?php

	use UmiCms\Service;
	use UmiCms\Classes\Components\Stub\iAdminSettingsManager as iStubAdminSettingsManager;

	/** Класс модуля "Доступ к сайту" */
	class umiStub extends def_module {

		/**
		 * Конструктор
		 * @throws coreException
		 */
		public function __construct() {
			parent::__construct();

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
				$commonTabs->add('stub');
				$commonTabs->add('whiteList');
				$commonTabs->add('blackList');
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('UmiStubAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('UmiStubCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->loadSiteExtension();

			$this->__loadLib('handlers.php');
			$this->__implement('UmiStubHandlers');

			$this->__loadLib('customMacros.php');
			$this->__implement('UmiStubCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Добавляет пользователя в белый список и авторизует его
		 * @return bool
		 * @throws coreException
		 * @throws Exception
		 */
		public function addUserToWhiteList() {
			$login = htmlspecialchars(getRequest('login'));
			$password = htmlspecialchars(getRequest('password'));

			$userId = Service::Auth()->checkLogin($login, $password);

			if (!$userId || !permissionsCollection::getInstance()->isAdmin($userId)) {
				throw new Exception(getLabel('error-wrong-auth-params'));
			}

			$this->addIpInGuide(
				$this->getWhiteListGuideId(),
				Service::Request()->remoteAddress(),
				$this->getDomainId()
			);

			return true;
		}

		/**
		 * Добавляет ip адрес в справочник
		 * @param int $typeId идентификатор справочника
		 * @param string $value ip адрес
		 * @param int|string $domainId идентифкатор домена
		 * @return bool|array
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws selectorException
		 */
		public function addIpInGuide($typeId, $value, $domainId = '') {
			$stubSettingsFactory = Service::StubSettingsFactory();

			$this->validateIpAddress($value, $domainId, $this->getTypeGuidById($typeId));

			if (is_numeric($domainId)) {
				/** @var \UmiCms\Classes\System\Utils\Stub\Settings\Custom $settings */
				$settings = $stubSettingsFactory->createCustom($domainId);
			} else {
				/** @var \UmiCms\Classes\System\Utils\Stub\Settings\Common $settings */
				$settings = $stubSettingsFactory->createCommon();
			}

			$objectId = false;

			if ($typeId == $this->getWhiteListGuideId()) {
				$objectId = $settings->addToWhiteList($value);
			} elseif ($typeId == $this->getBlackListGuideId()) {
				$objectId = $settings->addToBlackList($value);
			}

			return ['objectId' => $objectId];
		}

		/**
		 * Валидирует IP адрес
		 * @param string $address IP адрес
		 * @param int $domainId идентифкатор домена
		 * @param string $guid гуид типа данных
		 * @param bool|int $objectId идентикатор объекта
		 * @throws publicAdminException
		 * @throws selectorException
		 */
		public function validateIpAddress($address, $domainId, $guid, $objectId = false) {
			if (!preg_match('|^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$|', $address)) {
				$exception = new publicAdminException(getLabel('error-wrong-format', 'umiStub'));
				$this->errorAddErrors($exception);
				throw $exception;
			}

			$selector = new selector('objects');
			$selector->types('object-type')->guid($guid);

			if ($domainId) {
				$selector->where('domain_id')->equals($domainId);
			} else {
				$selector->where('domain_id')->isnull();
			}

			$selector->where('name')->equals($address);

			if ($objectId) {
				$selector->where('id')->notequals($objectId);
			}

			$selector->limit(0, 1);

			if (!isEmptyArray($selector->result())) {
				$exception = new publicAdminException(getLabel('error-already-exist', 'umiStub'));
				$this->errorAddErrors($exception);
				throw $exception;
			}
		}

		/**
		 * Возвращает класс настроек административной панели модуля
		 * @return iStubAdminSettingsManager
		 * @throws Exception
		 */
		public function getAdminSettingsManager() {
			return Service::get('StubAdminSettingsManager');
		}

		/**
		 * Возвращает идентификатор справочника
		 * "Список ip-адресов, которым доступен сайт"
		 * @return bool|int
		 * @throws coreException
		 */
		private function getWhiteListGuideId() {
			return $this->getGuideId('ip-whitelist');
		}

		/**
		 * Возвращает идентификатор справочника
		 * "Список ip-адресов, которым недоступен сайт"
		 * @return bool|int
		 * @throws coreException
		 */
		private function getBlackListGuideId() {
			return $this->getGuideId('ip-blacklist');
		}

		/**
		 * Возвращает идентификатор типа по его гуиду
		 * @param string $guid гуид типа
		 * @return bool|int
		 * @throws coreException
		 */
		private function getGuideId($guid) {
			return umiObjectTypesCollection::getInstance()
				->getTypeIdByGUID($guid);
		}

		/**
		 * Возвращает гуид типа данных по его идентификатору
		 * @param int $typeId
		 * @return bool|string
		 * @throws coreException
		 */
		private function getTypeGuidById($typeId) {
			$type = umiObjectTypesCollection::getInstance()
				->getType($typeId);

			return ($type instanceof iUmiObjectType) ? $type->getGUID() : false;
		}

		/**
		 * Возвращает идентификатор домена, если у него включена страница заглушка
		 * @return int|string
		 * @throws coreException
		 * @throws Exception
		 */
		private function getDomainId() {
			$domainDetector = Service::DomainDetector();
			$domainId = $domainDetector->detectId();

			return $this->isDomainStub($domainId) ? $domainId : '';
		}

		/**
		 * Включена ли страница заглушка у домена
		 * @param $domainId
		 * @return bool
		 * @throws Exception
		 */
		private function isDomainStub($domainId) {
			/** @var \UmiCms\Classes\System\Utils\Stub\Settings\Custom $settings */
			$settings = Service::StubSettingsFactory()
				->createCustom($domainId);

			$isUse = $settings->shouldUseCustomSettings();
			$isStub = $settings->isIpStub();

			return ($isUse && $isStub);
		}
	}