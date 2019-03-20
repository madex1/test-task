<?php
	abstract class __events extends baseModuleAdmin {
		
		public function last() {
			$this->setDataType("settings");
			$this->setActionType("view");
			$preParams = array();
			$data = $this->prepareData($preParams, 'settings');
			
			$pool = ConnectionPool::getInstance();
			$connection = $pool->getConnection();
			umiEventFeed::setConnection($connection);
			umiEventFeedType::setConnection($connection);
			
			$user = $this->getUser();
			$settings = umiEventFeedType::getAllowedList($user->getSettings());
			
			$newEvents = array('nodes:new-event' => array());
			foreach ($settings as $typeId) {
				
				$count = umiEventFeed::getListCount(array($typeId), $user->getId());
				if (!$count) continue;		
				
				$newEvents['nodes:new-event'][$typeId]['attribute:type-id'] = $typeId;
				$newEvents['nodes:new-event'][$typeId]['attribute:count'] = $count;
				$newEvents['nodes:new-event'][$typeId]['attribute:name'] = getLabel($typeId . '_new');
				$newEvents['nodes:new-event'][$typeId]['attribute:img'] = getLabel($typeId . '_img');
				$class = getLabel($typeId . '_img');
				$class = explode('/',$class);
				$class = $class[count($class)-1];
				$class = explode('.',$class);
				$class = $class[0];
				$newEvents['nodes:new-event'][$typeId]['attribute:class'] = $class;
				$newEvents['nodes:new-event'][$typeId]['events'] = array('nodes:events' => array());
				
				$typeEvents = umiEventFeed::getUnreadList($typeId, $user->getId(), 3);
				
				foreach ($typeEvents as $eventId => $event) {
					$newEvents['nodes:new-event'][$typeId]['events']['nodes:event'][$eventId] = self::renderEvent($event);
				}			
			}

			$data['new-events'] = $newEvents;
			
			$this->setData($data);
			return $this->doData();
			
		}

		public function feed() {
			$this->setDataType("settings");
			$this->setActionType("view");
			$preParams = array();
			$data = $this->prepareData($preParams, 'settings');

			$limit = ((int) getRequest('limit') > 0) ? ((int) getRequest('limit')) : 19;
			$p = (int) getRequest('p');
			$offset = $limit * $p;

			$maxDays = (int) regedit::getInstance()->getVal("//modules/banners/max-days-storing-events");
			$lastActualDate = $maxDays > 0 ? time() - ($maxDays * 24 * 60 * 60) : '';

			$startDate = getRequest('start_date') ? strtotime(getRequest('start_date')) : $lastActualDate;
			$endDate = getRequest('end_date') ? strtotime(getRequest('end_date')) : '';

			$filter = getRequest('filter');

			$onlyUnread = (bool) getRequest('onlyUnread');

			$pool = ConnectionPool::getInstance();
			$connection = $pool->getConnection();
			umiEventFeed::setConnection($connection);
			umiEventFeedType::setConnection($connection);

			$user = $this->getUser();

			$allowedList = umiEventFeedType::getAllowedList($user->getSettings());
			$settings = $filter ? array($filter) : $allowedList;

			$feed = array('nodes:event' => array());

			if ($filter && $onlyUnread) {
				if (in_array($filter, $allowedList)) {
					$typeEvents = umiEventFeed::getUnreadList($filter, $user->getId(), $limit, $offset, $startDate, $endDate);
					foreach ($typeEvents as $eventId => $event) {
						$feed['nodes:event'][] = self::renderEvent($event);
					}
					$data['attribute:total'] = umiEventFeed::getListCount($settings, $user->getId(), $startDate, $endDate);
				}
			} elseif (!empty($settings)) {
				$events = umiEventFeed::getList($settings, $user->getId(), $limit, $offset, $startDate, $endDate);	
				foreach($events as $eventId => $eventInfo) {
					$feed['nodes:event'][] = self::renderEvent($eventInfo['event'], $eventInfo['read']);
				}

				$data['attribute:total'] = umiEventFeed::getListCount($settings, false, $startDate, $endDate);	
			} else {
				$data['attribute:total'] = '0';
			}

			$data['attribute:offset'] = $offset;
			$data['attribute:limit'] = $limit;
			$data['events'] = $feed;

			$this->setData($data);
			return $this->doData();

		}

		private static function renderEvent($event, $read = 0) {
			$eventInfo = array();
			
			$eventInfo['attribute:id'] = $event->getId();
			$eventInfo['attribute:type-id'] = $event->getTypeId();
			$eventInfo['attribute:read'] = (int) $read;
			$eventDate = new umiDate($event->getDate());
			$eventInfo['attribute:timestamp'] = $eventDate->getDateTimeStamp();
			$eventInfo['attribute:date'] = $eventDate->getFormattedDate('d.m.Y H:i');
			$params = $event->getParams();
			$eventInfo['node:value'] = ulangStream::getLabelSimple($event->getTypeId() . "_msg", $params);
			
			return $eventInfo;
		}
		
		public function saveSettings($excludes = null) {
			$pool = ConnectionPool::getInstance();
			$connection = $pool->getConnection();
			umiEventFeedType::setConnection($connection);

			if (!is_array($excludes)) {
				$excludes = getRequest('settings');
			}

			$settings = array();

			if (is_null($excludes)) {
				$settings = umiEventFeedType::getAllowedList(array());
			}

			if (is_array($excludes) && count($excludes)) {
				$settings = umiEventFeedType::getAllowedList($excludes);
			}

			$user = $this->getUser();
			$user->setSettings($settings);
			$user->save();
		}

		public function markReadEvents($events = null) {
			if (!is_array($events)) $events = getRequest('events');
			
			if (is_array($events)) {
				$user = $this->getUser();
				$pool = ConnectionPool::getInstance();
				$connection = $pool->getConnection();
				umiEventFeed::setConnection($connection);
					
				foreach ($events as $eventId) {
					umiEventFeed::markReadEvent($eventId, $user->getId());
				}
			}
		}
		
		public function markUnreadEvents($events = null) {
			if (!is_array($events)) $events = getRequest('events');
			
			if (is_array($events)) {
				$user = $this->getUser();
				$pool = ConnectionPool::getInstance();
				$connection = $pool->getConnection();
				umiEventFeed::setConnection($connection);
					
				foreach ($events as $eventId) {
					umiEventFeed::markUnreadEvent($eventId, $user->getId());
				}
			}
		}
		
		public function addEventType() {
			$eventType = getRequest('event-type');
			$pool = ConnectionPool::getInstance();
			$connection = $pool->getConnection();
			umiEventFeedType::setConnection($connection);
			umiEventFeedType::create($eventType);
			$this->redirect(getServer("HTTP_REFERER"));	
		}
		
		public function config() {
			
			$regedit = regedit::getInstance();
			$params = Array (
				"config" => Array (
					"int:max-days-storing-events" => null,
					"boolean:collect-events" => null
				)
			);

			$mode = getRequest("param0");
			if ($mode == "do"){
				if (!isDemoMode()) {
					$params = $this->expectParams($params);
					$regedit->setVar("//modules/events/max-days-storing-events", (int) $params["config"]["int:max-days-storing-events"]);
					$regedit->setVar("//modules/events/collect-events", (int) $params["config"]["boolean:collect-events"]);
					$this->chooseRedirect();
				}
			}
			$params["config"]["int:max-days-storing-events"] = (int) $regedit->getVal("//modules/events/max-days-storing-events");
			$params["config"]["boolean:collect-events"] = (bool) $regedit->getVal("//modules/events/collect-events");

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");
			$this->setData($data);
			return $this->doData();

		}

	};
?>
