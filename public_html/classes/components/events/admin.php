<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class EventsAdmin {

		use baseModuleAdmin;

		/** @var events $module */
		public $module;

		/**
		 * Возвращает список последних непрочитанных событий
		 * @throws Exception
		 * @throws coreException
		 */
		public function last() {
			$this->setDataType('settings');
			$this->setActionType('view');
			$preParams = [];
			$data = $this->prepareData($preParams, 'settings');

			$pool = ConnectionPool::getInstance();
			$connection = $pool->getConnection();
			umiEventFeed::setConnection($connection);
			umiEventFeedType::setConnection($connection);

			$user = $this->module->getUser();
			$settings = umiEventFeedType::getAllowedEvents($user->getSettings());

			$newEvents = ['nodes:new-event' => []];
			foreach ($settings as $typeId) {
				$count = umiEventFeed::getListCount([$typeId], $user->getId());

				if (!$count) {
					continue;
				}

				$newEvents['nodes:new-event'][$typeId]['attribute:type-id'] = $typeId;
				$newEvents['nodes:new-event'][$typeId]['attribute:count'] = $count;
				$newEvents['nodes:new-event'][$typeId]['attribute:name'] = getLabel($typeId . '_new');
				$newEvents['nodes:new-event'][$typeId]['attribute:img'] = getLabel($typeId . '_img');
				$class = getLabel($typeId . '_img');
				$class = explode('/', $class);
				$class = $class[umiCount($class) - 1];
				$class = explode('.', $class);
				$class = $class[0];
				$newEvents['nodes:new-event'][$typeId]['attribute:class'] = $class;
				$newEvents['nodes:new-event'][$typeId]['events'] = ['nodes:events' => []];

				$typeEvents = umiEventFeed::getUnreadList($typeId, $user->getId(), 3);

				foreach ($typeEvents as $eventId => $event) {
					$newEvents['nodes:new-event'][$typeId]['events']['nodes:event'][$eventId] = self::renderEvent($event);
				}
			}

			$data['new-events'] = $newEvents;

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает список событий с заданным фильтром
		 * @throws Exception
		 * @throws coreException
		 */
		public function feed() {
			$this->setDataType('settings');
			$this->setActionType('view');
			$preParams = [];
			$data = $this->prepareData($preParams, 'settings');

			$limit = ((int) getRequest('limit') > 0) ? ((int) getRequest('limit')) : 19;
			$p = (int) getRequest('p');
			$offset = $limit * $p;

			$maxDays = (int) Service::Registry()->get('//modules/banners/max-days-storing-events');
			$lastActualDate = $maxDays > 0 ? time() - ($maxDays * 24 * 60 * 60) : '';

			$startDate = getRequest('start_date') ? strtotime(getRequest('start_date')) : $lastActualDate;
			$endDate = getRequest('end_date') ? strtotime(getRequest('end_date')) : '';

			$filter = getRequest('filter');

			$onlyUnread = (bool) getRequest('onlyUnread');

			$pool = ConnectionPool::getInstance();
			$connection = $pool->getConnection();
			umiEventFeed::setConnection($connection);
			umiEventFeedType::setConnection($connection);

			$user = $this->module->getUser();

			$allowedList = umiEventFeedType::getAllowedEvents($user->getSettings());
			$settings = $filter ? [$filter] : $allowedList;

			$feed = ['nodes:event' => []];

			if ($filter && $onlyUnread) {
				if (in_array($filter, $allowedList)) {
					$typeEvents = umiEventFeed::getUnreadList(
						$filter,
						$user->getId(),
						$limit,
						$offset,
						$startDate,
						$endDate
					);
					foreach ($typeEvents as $eventId => $event) {
						$feed['nodes:event'][] = self::renderEvent($event);
					}
					$data['attribute:total'] = umiEventFeed::getListCount($settings, $user->getId(), $startDate, $endDate);
				}
			} elseif (empty($settings)) {
				$data['attribute:total'] = '0';
			} else {
				$events = umiEventFeed::getList($settings, $user->getId(), $limit, $offset, $startDate, $endDate);
				foreach ($events as $eventId => $eventInfo) {
					$feed['nodes:event'][] = self::renderEvent($eventInfo['event'], $eventInfo['read']);
				}

				$data['attribute:total'] = umiEventFeed::getListCount($settings, false, $startDate, $endDate);
			}

			$data['attribute:offset'] = $offset;
			$data['attribute:limit'] = $limit;
			$data['events'] = $feed;

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Подтготавливает событие к шаблонизации
		 * @param umiEventFeed $event событие
		 * @param int $read было событие прочитано
		 * @return array
		 */
		private static function renderEvent($event, $read = 0) {
			/** @var umiEventFeed $event */
			$eventInfo = [];
			$eventInfo['attribute:id'] = $event->getId();
			$eventInfo['attribute:type-id'] = $event->getTypeId();
			$eventInfo['attribute:read'] = (int) $read;
			$eventDate = new umiDate($event->getDate());
			$eventInfo['attribute:timestamp'] = $eventDate->getDateTimeStamp();
			$eventInfo['attribute:date'] = $eventDate->getFormattedDate('d.m.Y H:i');
			$params = $event->getParams();
			$eventInfo['node:value'] = ulangStream::getLabelSimple($event->getTypeId() . '_msg', $params);
			return $eventInfo;
		}

		/**
		 * Сохраняет настройки отображения событий для пользователя
		 * @param null|array $excludes список исключений
		 * @throws Exception
		 */
		public function saveSettings($excludes = null) {
			$pool = ConnectionPool::getInstance();
			$connection = $pool->getConnection();
			umiEventFeedType::setConnection($connection);

			if (!is_array($excludes)) {
				$excludes = getRequest('settings');
			}

			$settings = [];

			if ($excludes === null) {
				$settings = umiEventFeedType::getAllowedEvents([]);
			}

			if (is_array($excludes) && umiCount($excludes)) {
				$settings = umiEventFeedType::getAllowedEvents($excludes);
			}

			$user = $this->module->getUser();
			$user->setSettings($settings);
			$user->save();
		}

		/**
		 * Помечает события как прочитанные
		 * @param null $events массив идентификаторов событий
		 * @throws Exception
		 */
		public function markReadEvents($events = null) {
			if (!is_array($events)) {
				$events = getRequest('events');
			}

			if (is_array($events)) {
				$user = $this->module->getUser();
				$pool = ConnectionPool::getInstance();
				$connection = $pool->getConnection();
				umiEventFeed::setConnection($connection);

				foreach ($events as $eventId) {
					umiEventFeed::markReadEvent($eventId, $user->getId());
				}
			}
		}

		/**
		 * Помечает события как не прочитанные
		 * @param null $events
		 * @throws Exception
		 */
		public function markUnreadEvents($events = null) {
			if (!is_array($events)) {
				$events = getRequest('events');
			}

			if (is_array($events)) {
				$user = $this->module->getUser();
				$pool = ConnectionPool::getInstance();
				$connection = $pool->getConnection();
				umiEventFeed::setConnection($connection);

				foreach ($events as $eventId) {
					umiEventFeed::markUnreadEvent($eventId, $user->getId());
				}
			}
		}

		/**
		 * Создает тип событий
		 * @throws Exception
		 */
		public function addEventType() {
			$eventType = getRequest('event-type');
			$pool = ConnectionPool::getInstance();
			$connection = $pool->getConnection();
			umiEventFeedType::setConnection($connection);
			umiEventFeedType::create($eventType);
			$this->module->redirect(getServer('HTTP_REFERER'));
		}

		/**
		 * Возвращает настройки модуля.
		 * Если передано ключевое слово "do" в $_REQUEST['param0'],
		 * то сохраняет переданные настройки.
		 */
		public function config() {
			$regedit = Service::Registry();
			$params = [
				'config' => [
					'int:max-days-storing-events' => null,
					'boolean:collect-events' => null
				]
			];

			if ($this->isSaveMode() && !isDemoMode()) {
				$params = $this->expectParams($params);
				$regedit->set('//modules/events/max-days-storing-events', (int) $params['config']['int:max-days-storing-events']);
				$regedit->set('//modules/events/collect-events', (int) $params['config']['boolean:collect-events']);
				$this->chooseRedirect();
			}

			$params['config']['int:max-days-storing-events'] =
				(int) $regedit->get('//modules/events/max-days-storing-events');
			$params['config']['boolean:collect-events'] = (bool) $regedit->get('//modules/events/collect-events');

			$this->setConfigResult($params);
		}
	}
