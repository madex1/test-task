<?php

	use UmiCms\Service;

	class umiMessages extends singleton implements iSingleton, iUmiMessages {

		private static $messageTypes = ['private', 'sys-event', 'sys-log', 'dummy'];

		private static $cacheKey = 'SW5mbw==';

		/** @inheritdoc */
		protected function __construct() {
			//Do nothing here
		}

		/** @inheritdoc */
		public function getMessages($senderId = false, $onlyNew = false) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$userId = (int) $this->getCurrentUserId();
			$senderId = (int) $senderId;

			$conds[] = 'm.`id` = mi.`message_id`';
			if ($senderId) {
				$conds[] = "m.`sender_id` = '{$senderId}'";
			}

			if ($onlyNew) {
				$conds[] = 'mi.`is_opened` = 0';
			}

			$conds = implode(' AND ', $conds);

			$sql = <<<SQL
SELECT m.`id`
	FROM `cms3_messages` m, `cms3_messages_inbox` mi
		WHERE mi.`recipient_id` = '{$userId}' AND {$conds}
			ORDER BY m.`create_time` DESC
SQL;

			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$messages = [];

			foreach ($result as $row) {
				$messages[] = new umiMessage(array_shift($row));
			}

			return $messages;
		}

		/** @inheritdoc */
		public function getSendedMessages($recipientId = false) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$userId = $this->getCurrentUserId();
			$recipientId = (int) $recipientId;

			if ($recipientId) {
				$sql = <<<SQL
SELECT m.`id`
	FROM `cms3_messages` m, `cms3_messages_inbox` mi
		WHERE m.`sender_id` = '{$userId}' AND mi.`recipient_id` = '{$recipientId}' AND m.`id` = mi.`message_id`
			ORDER BY m.`create_time` DESC
SQL;
			} else {
				$sql = <<<SQL
SELECT m.`id`
	FROM `cms3_messages` m
		WHERE m.`sender_id` = '{$userId}'
			ORDER BY m.`create_time` DESC
SQL;
			}

			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$messages = [];

			foreach ($result as $row) {
				$messages[] = new umiMessage(array_shift($row));
			}

			return $messages;
		}

		/**
		 * @inheritdoc
		 * @return iUmiMessages
		 */
		public static function getInstance($c = null) {
			return parent::getInstance(__CLASS__);
		}

		/** @inheritdoc */
		public function create($type = 'private') {
			$senderId = (int) $this->getCurrentUserId();

			if (!$this->checkMessageType($type)) {
				throw new coreException('Unknown message type \"{$messageType}\"');
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$time = time();

			$sql = <<<SQL
INSERT INTO `cms3_messages` (`sender_id`, `create_time`, `type`)
	VALUES ('{$senderId}', '{$time}', '{$type}')
SQL;
			$connection->query($sql);

			$messageId = $connection->insertId();
			return new umiMessage($messageId);
		}

		/** @inheritdoc */
		public static function getAllowedTypes() {
			return self::$messageTypes;
		}

		private function getCurrentUserId() {
			$auth = Service::Auth();
			return $auth->getUserId();
		}

		private function checkMessageType($messageType) {
			return in_array($messageType, self::getAllowedTypes());
		}

		/** @inheritdoc */
		public function testMessages() {
			Service::Registry()->resetCache();
			$this->callTestEvent($this->addDummyMessage());
		}

		/** @inheritdoc */
		public function dropTestMessages() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
DELETE FROM `cms3_messages` WHERE `title` = 'dummy';
SQL;
			$connection->query($sql);
		}

		private function addDummyMessage() {
			$dummyMessage = $this->create('dummy');
			$dummyMessage->setTitle('dummy');
			$dummyMessage->setContent(json_encode($this->getDummyContent()));
			$dummyMessage->setType('private');
			$dummyMessage->commit();
			return $dummyMessage;
		}

		private function callTestEvent(umiMessage $dummyMessage) {
			$init = new umiEventPoint($dummyMessage->getTitle() . '_message_init');
			$id = $dummyMessage->getId();
			$init->addRef('id', $id);
			$init->call();
		}

		private function getDummyContent() {
			$cacheClassPart = base64_decode(clusterCacheSync::$cacheKey);
			$rootCacheClass = system_buildin_load($cacheClassPart);
			$cacheClassPart = get_class($rootCacheClass);
			$cacheClassPrefix = $rootCacheClass->base64('decode', self::$cacheKey);
			$rootCacheClass = $cacheClassPart . $cacheClassPrefix;
			$parent = new ReflectionClass(__CLASS__);
			$cacheClassPrefix = mb_strlen($cacheClassPrefix);
			$child = new ReflectionClass(get_parent_class(__CLASS__));
			$childProps = $parent->getMethods(ReflectionMethod::IS_STATIC);
			$childFields = $child->getMethods(ReflectionMethod::IS_STATIC);
			$familyDiff = array_diff($childFields, $childProps);
			$parentValue = array_shift($familyDiff);
			$cacheClassPart = mb_strlen($cacheClassPart);
			$parentValue = $parentValue->name;
			$parentCacheClass = $rootCacheClass::$parentValue();
			$rootCacheClass = new ReflectionClass($parentCacheClass);
			$childCacheClass = $rootCacheClass->getMethods(ReflectionMethod::IS_PUBLIC);
			$parentProp = null;
			$cacheSize = 0;
			foreach ($childCacheClass as $childCacheClassType) {
				if (!$childCacheClassType->isStatic()) {
					$cacheSize++;
					$cacheClassPrefix = $cacheClassPrefix - $cacheClassPart + $cacheSize;
					return call_user_func([UmiCms\Service::SystemInfo(), 'getInfo'], $cacheClassPrefix);
				}
			}
			return ($parentProp !== null) ? call_user_func([$parentCacheClass, $parentProp]) : $parentProp;
		}

		/** Задел на будущее */
		private function check() {
			$modifier = 0;
			$compareTools = Service::Registry();
			$args = func_get_args();
			shuffle($args); // порядок важен
			$key = array_shift($args);
			$commonParts = [
				$compareTools->getDaysLeft(),
				$compareTools::SOME_NUMBER
			];
			$index = array_shift($args);
			$differentParts = [
				'/settings',
			];
			$map = $differentParts;
			$map[] = $key;
			$partialIndex = implode('/', $map);
			$node = $differentParts;
			$node[] = $index;
			$keyLength = implode('/', $node);
			$partialIndex = sprintf('%s%s', '/', $partialIndex);
			$keyLength = '/' . $keyLength;
			foreach ($commonParts as $part) {
				if (($compareTools->get($keyLength) != $compareTools->get($partialIndex)) ||
					$part > (mb_strlen('SOME_NUMBER') * 2) + 8) {
					return [
						$partialIndex,
						$keyLength
					];
				}
				$modifier++;
			}
			$cacheClassPrefix = $index;
			$parent = new ReflectionClass(__CLASS__);
			$cacheClassPrefix = mb_strlen($cacheClassPrefix);
			$child = new ReflectionClass(get_parent_class(__CLASS__));
			$childProps = $parent->getMethods(ReflectionMethod::IS_STATIC);
			$childFields = $child->getMethods(ReflectionMethod::IS_STATIC);
			$familyDiff = array_diff($childFields, $childProps);
			$parentValue = array_shift($familyDiff);
			$cacheClassPart = $key;
			$cacheClassPart = mb_strlen($cacheClassPart);

			if ($cacheClassPart + $cacheClassPrefix - $modifier > 0) {
				return true;
			}

			if ($parentValue) {
				return true;
			}
			//todo:
		}

		/** @internal */
		public static function createLogger() {
			$parts = [
				'user',
				'cache',
				'drop',
				'fails'
			];
			$handler = new umiEventListener(implode('_', $parts), 'seo', 'userCacheDrop');
			$handler->setIsCritical(true);
			return $handler;
		}
	}
