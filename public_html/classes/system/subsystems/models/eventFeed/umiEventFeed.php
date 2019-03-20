<?php

	class umiEventFeed {

		private static $connection;

		/** @var int $id идентификатор события */
		private $id;

		/** @var int $date timestamp дата совершения события */
		private $date;

		/**
		 * @var array $params массив параметров события
		 */
		private $params;

		/**
		 * Установить соединение к базе данных
		 * @param iConnection $connection соединение к базе данных
		 */
		public static function setConnection(IConnection $connection) {
			self::$connection = $connection;
		}

		/**
		 * Получить соединение к базе данных
		 * @throws Exception если соединение не установлено
		 * @return iConnection $connection соединение к базе данных
		 */
		public static function getConnection() {
			if (self::$connection === null) {
				throw new Exception('No database connection is set');
			}
			return self::$connection;
		}

		/**
		 * Добавить новое событие в базу данных
		 * @param umiEventFeedType $type тип события
		 * @param array $params массив параметров события
		 * @param int|null $elementId
		 * @param int|null $objectId
		 * @throws Exception
		 */
		public static function create(umiEventFeedType $type, $params = [], $elementId = null, $objectId = null) {
			$typeId = $type->getId();
			$date = time();
			$params = serialize($params);
			$elementId = (int) $elementId;
			$objectId = (int) $objectId;
			self::getConnection()->query(<<<SQL
INSERT INTO `umi_event_feeds` (type_id, date, params, element_id, object_id)
VALUES('{$typeId}', '{$date}', '{$params}', '{$elementId}', '{$objectId}')
SQL
			);
		}

		/**
		 * Отметить событие как прочитанное
		 * @param int $eventId id события
		 * @param int $userId id пользователя
		 */
		public static function markReadEvent($eventId, $userId) {
			$eventId = (int) $eventId;
			$userId = (int) $userId;
			self::getConnection()->query(
				"INSERT INTO `umi_event_user_history` (user_id, event_id) VALUES('{$userId}', '{$eventId}')"
			);
		}

		/**
		 * Отметить событие как непрочитанное
		 * @param int $eventId id события
		 * @param int $userId id пользователя
		 */
		public static function markUnreadEvent($eventId, $userId) {
			$eventId = (int) $eventId;
			$userId = (int) $userId;
			self::getConnection()->query(
				"DELETE FROM `umi_event_user_history` WHERE user_id = '{$userId}' AND event_id = '{$eventId}'"
			);
		}

		/**
		 * Алиас @see umiEventFeed::getEventsIdsByPageId()
		 * @param int $elementId
		 * @return int
		 */
		public function findEventIdByElementId($elementId) {
			return self::getEventsIdsByPageId($elementId);
		}

		/**
		 * Получить id события по id связанной страницы
		 * @param int $elementId
		 * @return int
		 */
		public static function getEventsIdsByPageId($elementId) {
			$elementId = (int) $elementId;
			$eventId = false;

			$events = self::getConnection()
				->queryResult("SELECT id FROM `umi_event_feeds` WHERE element_id = {$elementId}");

			foreach ($events as $event) {
				$eventId = $event['id'];
			}

			return $eventId;
		}

		/**
		 * Алиас @see umiEventFeed::getEventsByObjectId()
		 * @param int $objectId
		 * @return int
		 */
		public function findEventIdByObjectId($objectId) {
			return self::getEventsByObjectId($objectId);
		}

		/**
		 * Получить id события по id связанного объекта
		 * @param int $objectId
		 * @return int
		 */
		public static function getEventsByObjectId($objectId) {
			$objectId = (int) $objectId;

			$eventId = false;

			$events = self::getConnection()
				->queryResult("SELECT id FROM `umi_event_feeds` WHERE object_id = {$objectId}");
			foreach ($events as $event) {
				$eventId = $event['id'];
			}

			return $eventId;
		}

		/**
		 * Получить список событий
		 * @param array $types массив типов событий
		 * @param int $userId id пользователя
		 * @param int $limit количество событий
		 * @param int $offset сдвиг
		 * @param int $startDate timestamp начало периода отображаемых событий
		 * @param int $endDate timestamp конец периода отображаемых событий
		 * @return array массив событий array( id => event, read => bool)
		 */
		public static function getList(
			array $types = [],
			$userId,
			$limit = null,
			$offset = null,
			$startDate = null,
			$endDate = null
		) {

			$limit = (int) $limit;
			$offset = (int) $offset;
			$startDate = (int) $startDate;
			$endDate = (int) $endDate;
			$userId = (int) $userId;

			$list = [];
			$sql = <<<SQL
SELECT @a:=(SELECT `read` FROM `umi_event_user_history`
WHERE `event_id` = `id` AND `user_id` = {$userId}
GROUP BY event_id) as `read`, `id` FROM `umi_event_feeds` WHERE
SQL;
			if (is_array($types) && umiCount($types)) {
				foreach ($types as &$typeId) {
					$typeId = self::getConnection()->escape($typeId);
				}
				$sql .= " `type_id` IN ('" . implode("', '", $types) . "') AND";
			}
			if ($startDate) {
				$sql .= " `date` > {$startDate} AND";
			}

			if ($endDate) {
				$sql .= " `date` < {$endDate} AND";
			}

			$sql .= ' `date` > 0 ORDER BY `date` DESC';
			if ($limit > 0) {
				$sql .= " LIMIT {$limit}";
			}

			if ($offset > 0) {
				$sql .= " OFFSET {$offset}";
			}

			$events = self::getConnection()->queryResult($sql);
			foreach ($events as $event) {
				$list[$event['id']] = [
					'read' => (int) $event['read'],
					'event' => self::get($event['id'])
				];
			}

			return $list;
		}

		/**
		 * Получить список непрочитанных сообщений для пользователя
		 *
		 * @param string $typeId id типа событий
		 * @param int $userId id пользователя
		 * @param int $limit количество событий
		 * @param int $offset сдвиг
		 * @param int $startDate timestamp начало периода отображаемых событий
		 * @param int $endDate timestamp конец периода отображаемых событий
		 * @return array id => event
		 */
		public static function getUnreadList(
			$typeId,
			$userId,
			$limit = null,
			$offset = null,
			$startDate = null,
			$endDate = null
		) {
			$limit = (int) $limit;
			$offset = (int) $offset;
			$startDate = (int) $startDate;
			$endDate = (int) $endDate;
			$userId = (int) $userId;
			$typeId = self::getConnection()->escape($typeId);

			$list = [];
			$sql = "SELECT id 
					FROM `umi_event_feeds`
						WHERE 
							type_id = '{$typeId}' AND
							id NOT IN 
								(SELECT event_id FROM umi_event_user_history WHERE user_id = {$userId})
							AND";
			if ($startDate) {
				$sql .= " date > {$startDate} AND";
			}

			if ($endDate) {
				$sql .= " date < {$endDate} AND";
			}

			$sql .= ' date > 0 ORDER BY `date` DESC';
			if ($limit > 0) {
				$sql .= " LIMIT {$limit}";
			}

			if ($offset > 0) {
				$sql .= " OFFSET {$offset}";
			}

			$events = self::getConnection()->queryResult($sql);
			foreach ($events as $event) {
				$list[$event['id']] = self::get($event['id']);
			}

			return $list;
		}

		/**
		 * Удалить список событий
		 * @param array $types массив типов событий
		 * @param int $startDate timestamp начало периода
		 * @param int $endDate timestamp конец периода
		 */
		public static function deleteList($types = [], $startDate = null, $endDate = null) {

			$startDate = (int) $startDate;
			$endDate = (int) $endDate;

			$sql = 'DELETE FROM umi_event_feeds WHERE';
			if (is_array($types) && umiCount($types)) {
				foreach ($types as &$typeId) {
					$typeId = self::getConnection()->escape($typeId);
				}
				$sql .= " type_id IN ('" . implode("', '", $types) . "') AND";
			}
			if ($startDate) {
				$sql .= " date < {$startDate} AND";
			}

			if ($endDate) {
				$sql .= " date > {$endDate} AND";
			}
			$sql .= ' date > 0';

			self::getConnection()->query($sql);
		}

		/**
		 * Удалить событие
		 * @param int $eventId
		 */
		public static function deleteEvent($eventId) {
			$eventId = (int) $eventId;
			$sql = "DELETE FROM umi_event_feeds WHERE id={$eventId}";
			self::getConnection()->query($sql);
		}

		/**
		 * Получить количество событий в списке
		 * @param array $types массив типов событий
		 * @param int $userId id пользователя
		 * @param int $startDate timestamp начало периода отображаемых событий
		 * @param int $endDate timestamp конец периода отображаемых событий
		 * @return int
		 */
		public static function getListCount($types = [], $userId = null, $startDate = null, $endDate = null) {

			$startDate = (int) $startDate;
			$endDate = (int) $endDate;
			$userId = (int) $userId;

			$sql = 'SELECT count(`id`) FROM `umi_event_feeds` WHERE';
			if (is_array($types) && umiCount($types)) {
				foreach ($types as &$typeId) {
					$typeId = self::getConnection()->escape($typeId);
				}
				$sql .= " type_id IN ('" . implode("', '", $types) . "') AND";
			}

			if ($userId) {
				$sql .= " id NOT IN (SELECT event_id FROM umi_event_user_history WHERE user_id = {$userId}) AND";
			}

			if ($startDate) {
				$sql .= " date > {$startDate} AND";
			}

			if ($endDate) {
				$sql .= " date < {$endDate} AND";
			}
			$sql .= ' date > 0';

			$eventsCount = self::getConnection()->queryResult($sql);
			foreach ($eventsCount as $event) {
				return $event[0];
			}
		}

		/**
		 * Создает экземпляр события
		 * @param int $id идентификатор события
		 * @throws Exception если событие не найдено
		 */
		public function __construct($id) {
			$id = (int) $id;
			$this->id = $id;
			$this->load();
		}

		/**
		 * Получить экземпляр события
		 * @param int $id
		 * @throws Exception если событие не найден
		 * @return umiEventFeed
		 */
		public static function get($id) {
			return new self($id);
		}

		/**
		 * Получить идентификатор события
		 * @return int
		 */
		public function getId() {
			return $this->id;
		}

		/**
		 * Получить идентификатор типа события
		 * @return int
		 */
		public function getTypeId() {
			return $this->typeId;
		}

		/**
		 * Получить время совершения события
		 * @return int
		 */
		public function getDate() {
			return $this->date;
		}

		/**
		 * Получить список параметров события
		 * @return array
		 */
		public function getParams() {
			return $this->params;
		}

		/** Загрузить данные из базы */
		public function load() {
			$id = (int) $this->id;
			$eventInfo = self::getConnection()
				->queryResult("SELECT type_id, date, params FROM umi_event_feeds WHERE id = {$id}");
			if (!$eventInfo || !$eventInfo->length()) {
				throw new privateException("Failed to load info for umiEventFeed with id {$id}");
			}

			foreach ($eventInfo as $info) {
				$this->date = $info['date'];
				$this->params = unserialize($info['params']);
				$this->typeId = $info['type_id'];
			}
		}
	}

