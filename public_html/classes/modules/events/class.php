<?php
	class events extends def_module {
		public function __construct() {
			parent::__construct();

			$this->loadCommonExtension();

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$this->__loadLib("__admin.php");
				$this->__implement("__events");

				$configTabs = $this->getConfigTabs();
				if ($configTabs) {
					$configTabs->add("config");
				}

				$commonTabs = $this->getCommonTabs();
				if($commonTabs) {
					$commonTabs->add('last');
					$commonTabs->add('feed');
				}

				$this->loadAdminExtension();

				$this->__loadLib("__custom_adm.php");
				$this->__implement("__events_custom_admin");
			}

			$this->loadSiteExtension();

			$this->__loadLib("__custom.php");
			$this->__implement("__custom_events");

			$this->__loadLib("__events_handlers.php");
			$this->__implement("__eventsHandlersEvents");
		}

		/**
		* Зарегистрировать событие в истории событий
		* @param string $eventTypeId идентификатор типа события
		* @param array $params массив параметров события
		* @param int $elementId id связанной страницы
		* @param int $objectId id связанного объекта
		*/
		public function registerEvent($eventTypeId, $params = array(), $elementId = null, $objectId = null) {
			
			$pool = ConnectionPool::getInstance();
			$connection = $pool->getConnection();
			umiEventFeed::setConnection($connection);
			umiEventFeedType::setConnection($connection);
					
			try {
				$eventType = umiEventFeedType::get($eventTypeId);
			} catch (Exception $e) {
				$eventType = umiEventFeedType::create($eventTypeId);
			}
			
			$userId = permissionsCollection::getInstance()->getUserId();
			$user = umiObjectsCollection::getInstance()->getObject($userId)->getName();
			
			$module = cmsController::getInstance()->getModule('users');
			$link = $module->getObjectEditLink($userId);
			
			array_unshift($params, $user);
			array_unshift($params, $link);
			umiEventFeed::create($eventType, $params, $elementId, $objectId);
			
			$maxDays = (int) regedit::getInstance()->getVal("//modules/events/max-days-storing-events");
			if ($maxDays > 0) {
				$lastDate = time() - ($maxDays * 24 * 60 * 60);
				umiEventFeed::deleteList(array(), $lastDate);
			}
			
		}
		
		/** Получить типы событий, отслеживаемые пользователем */
		public function getUserSettings() {
			
			$pool = ConnectionPool::getInstance();
			$connection = $pool->getConnection();
			umiEventFeedType::setConnection($connection);
			umiEventFeedUser::setConnection($connection);
			
			$user = $this->getUser();
			$settings = umiEventFeedType::getAllowedList($user->getSettings());
			$types = umiEventFeedType::getList();

			$result = array('nodes:type' => array());
			foreach ($types as $type) {
				$typeId = $type->getId();				
				$result['nodes:type'][$typeId]['attribute:id'] = $typeId;
				$result['nodes:type'][$typeId]['attribute:name'] = getLabel($typeId);
				$result['nodes:type'][$typeId]['attribute:checked'] = in_array($typeId, $settings) ? 1 : 0;
			}
			
			return def_module::parseTemplate('', $result);
		}
		
		public static function getUser() {
			
			static $user = null;
			if ($user) return $user;
			
			$pool = ConnectionPool::getInstance();
			$connection = $pool->getConnection();
			
			umiEventFeedUser::setConnection($connection);
			umiEventFeedType::setConnection($connection);
			
			$userId = permissionsCollection::getInstance()->getUserId();
			try {
				$user = umiEventFeedUser::get($userId);
			} catch (Exception $e) {
				$user = umiEventFeedUser::create($userId);
				$settings = array();
				$user->setSettings($settings);
				$user->save();
			}
			
			return $user;
		}
		
	};
?>
