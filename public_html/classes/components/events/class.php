<?php

	use UmiCms\Service;

	/**
	 * Класс управления событиями
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_sobytiya/
	 */
	class events extends def_module {

		/** Конструктор */
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
			$configTabs = $this->getConfigTabs();

			if ($configTabs instanceof iAdminModuleTabs) {
				$configTabs->add('config');
			}

			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('last');
				$commonTabs->add('feed');
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('EventsAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('EventsCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->loadSiteExtension();

			$this->__loadLib('handlers.php');
			$this->__implement('EventsHandlers');

			$this->__loadLib('customMacros.php');
			$this->__implement('EventsCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Зарегистрировать событие в истории событий
		 * @param string $eventTypeId идентификатор типа события
		 * @param array $params массив параметров события
		 * @param int $elementId id связанной страницы
		 * @param int $objectId id связанного объекта
		 */
		public function registerEvent($eventTypeId, $params = [], $elementId = null, $objectId = null) {

			$pool = ConnectionPool::getInstance();
			$connection = $pool->getConnection();
			umiEventFeed::setConnection($connection);
			umiEventFeedType::setConnection($connection);

			try {
				$eventType = umiEventFeedType::get($eventTypeId);
			} catch (Exception $e) {
				$eventType = umiEventFeedType::create($eventTypeId);
			}

			$auth = Service::Auth();
			$userId = $auth->getUserId();
			$user = umiObjectsCollection::getInstance()->getObject($userId)->getName();
			/** @var users $module */
			$module = cmsController::getInstance()->getModule('users');
			$link = $module->getObjectEditLink($userId);

			array_unshift($params, $user);
			array_unshift($params, $link);
			umiEventFeed::create($eventType, $params, $elementId, $objectId);

			$maxDays = (int) Service::Registry()->get('//modules/events/max-days-storing-events');
			if ($maxDays > 0) {
				$lastDate = time() - ($maxDays * 24 * 60 * 60);
				umiEventFeed::deleteList([], $lastDate);
			}
		}

		/** Получить типы событий, отслеживаемые пользователем */
		public function getUserSettings() {

			$pool = ConnectionPool::getInstance();
			$connection = $pool->getConnection();
			umiEventFeedType::setConnection($connection);
			umiEventFeedUser::setConnection($connection);

			$user = $this->getUser();
			$settings = umiEventFeedType::getAllowedEvents($user->getSettings());
			$types = umiEventFeedType::getList();

			$result = ['nodes:type' => []];
			/** @var umiEventFeedType $type */
			foreach ($types as $type) {
				$typeId = $type->getId();
				$result['nodes:type'][$typeId]['attribute:id'] = $typeId;
				$result['nodes:type'][$typeId]['attribute:name'] = getLabel($typeId);
				$result['nodes:type'][$typeId]['attribute:checked'] = in_array($typeId, $settings) ? 1 : 0;
			}

			return events::parseTemplate('', $result);
		}

		/**
		 * Возвращает владельца события
		 * @return umiEventFeedUser
		 * @throws Exception
		 */
		public function getUser() {
			static $user = null;

			if ($user) {
				return $user;
			}

			$pool = ConnectionPool::getInstance();
			$connection = $pool->getConnection();

			umiEventFeedUser::setConnection($connection);
			umiEventFeedType::setConnection($connection);

			$auth = Service::Auth();
			$userId = $auth->getUserId();

			try {
				$user = umiEventFeedUser::get($userId);
			} catch (Exception $e) {
				$user = umiEventFeedUser::create($userId);
				$settings = [];
				$user->setSettings($settings);
				$user->save();
			}

			return $user;
		}
	}
