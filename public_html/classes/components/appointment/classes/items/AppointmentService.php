<?php

	/** Класс услуги для записи на прием */
	class AppointmentService implements
		iUmiCollectionItem,
		iUmiDataBaseInjector,
		iAppointmentService,
		iUmiConstantMapInjector,
		iClassConfigManager {

		use tUmiDataBaseInjector;
		use tCommonCollectionItem;
		use tUmiConstantMapInjector;
		use tClassConfigManager;

		/** @var int $groupId идентификатор группы услуг */
		private $groupId;

		/** @var string $name название услуги */
		private $name;

		/** @var string $time время выполнения услуги в формате H:i:s */
		private $time;

		/** @var float $price стоимость услуги */
		private $price;

		/** @var string $timePattern шаблон для валидации времени */
		private $timePattern = '/([0-9]{2}:[0-9]{2}:[0-9]{2})/';

		/** @var array конфигурация класса */
		private static $classConfig = [
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'required' => true,
					'unchangeable' => true,
					'setter' => 'setId',
					'getter' => 'getId',
				],
				[
					'name' => 'GROUP_ID_FIELD_NAME',
					'required' => true,
					'setter' => 'setGroupId',
					'getter' => 'getGroupId',
				],
				[
					'name' => 'NAME_FIELD_NAME',
					'required' => true,
					'setter' => 'setName',
					'getter' => 'getName',
				],
				[
					'name' => 'TIME_FIELD_NAME',
					'required' => true,
					'setter' => 'setTime',
					'getter' => 'getTime',
				],
				[
					'name' => 'PRICE_FIELD_NAME',
					'required' => true,
					'setter' => 'setPrice',
					'getter' => 'getPrice',
				]
			]
		];

		/** @inheritdoc */
		public function getGroupId() {
			return $this->groupId;
		}

		/** @inheritdoc */
		public function setGroupId($groupId) {
			if (!is_numeric($groupId)) {
				throw new Exception('Wrong value for group id given');
			}

			if ($this->getGroupId() != $groupId) {
				$this->setUpdatedStatus(true);
			}

			$this->groupId = $groupId;
			return true;
		}

		/** @inheritdoc */
		public function getName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function setName($name) {
			if (!is_string($name)) {
				throw new Exception('Wrong value for name given');
			}

			$name = trim($name);

			if ($name === '') {
				throw new Exception('Empty value for name given');
			}

			if ($this->getName() != $name) {
				$this->setUpdatedStatus(true);
			}

			$this->name = $name;
			return true;
		}

		/** @inheritdoc */
		public function getTime() {
			return $this->time;
		}

		/** @inheritdoc */
		public function setTime($time) {
			if (!is_string($time) || !preg_match($this->timePattern, $time)) {
				throw new Exception('Wrong value for time given');
			}

			if ($this->getTime() != $time) {
				$this->setUpdatedStatus(true);
			}

			$this->time = $time;
			return true;
		}

		/** @inheritdoc */
		public function getPrice() {
			return $this->price;
		}

		/** @inheritdoc */
		public function setPrice($price) {
			if (!is_numeric($price)) {
				throw new Exception('Wrong value for price given');
			}

			$price = (float) $price;

			if ($this->getPrice() != $price) {
				$this->setUpdatedStatus(true);
			}

			$this->price = $price;
			return true;
		}

		/** @inheritdoc */
		public function commit() {
			if (!$this->isUpdated()) {
				return false;
			}

			$map = $this->getMap();
			$connection = $this->getConnection();
			$tableName = $connection->escape($map->get('TABLE_NAME'));
			$idField = $connection->escape($map->get('ID_FIELD_NAME'));
			$groupIdField = $connection->escape($map->get('GROUP_ID_FIELD_NAME'));
			$nameField = $connection->escape($map->get('NAME_FIELD_NAME'));
			$timeField = $connection->escape($map->get('TIME_FIELD_NAME'));
			$priceField = $connection->escape($map->get('PRICE_FIELD_NAME'));

			$id = (int) $this->getId();
			$groupId = (int) $this->getGroupId();
			$name = $connection->escape($this->getName());
			$time = $connection->escape($this->getTime());
			$price = (float) $this->getPrice();

			$sql = <<<SQL
UPDATE `$tableName`
	SET `$groupIdField` = '$groupId', `$nameField` = '$name', `$timeField` = '$time', `$priceField` = $price
		WHERE `$idField` = $id;
SQL;
			$connection->query($sql);

			return true;
		}
	}

