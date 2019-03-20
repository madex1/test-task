<?php

	/** Класс группы услуг для записи на прием */
	class AppointmentServiceGroup implements
		iUmiCollectionItem,
		iUmiDataBaseInjector,
		iAppointmentServiceGroup,
		iUmiConstantMapInjector,
		iClassConfigManager {

		use tUmiDataBaseInjector;
		use tCommonCollectionItem;
		use tUmiConstantMapInjector;
		use tClassConfigManager;

		/** @var string $name название группы услуг */
		private $name;

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
					'name' => 'NAME_FIELD_NAME',
					'required' => true,
					'setter' => 'setName',
					'getter' => 'getName',
				]
			]
		];

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
		public function commit() {
			if (!$this->isUpdated()) {
				return false;
			}

			$map = $this->getMap();
			$connection = $this->getConnection();
			$tableName = $connection->escape($map->get('TABLE_NAME'));
			$idField = $connection->escape($map->get('ID_FIELD_NAME'));
			$nameField = $connection->escape($map->get('NAME_FIELD_NAME'));

			$id = (int) $this->getId();
			$name = $connection->escape($this->getName());

			$sql = <<<SQL
UPDATE `$tableName`
	SET `$nameField` = '$name'
		WHERE `$idField` = $id;
SQL;
			$connection->query($sql);

			return true;
		}
	}

