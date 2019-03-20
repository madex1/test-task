<?php

	/** Класс связи сотрудник - услуга записи на прием */
	class AppointmentEmployeeService implements
		iUmiCollectionItem,
		iUmiDataBaseInjector,
		iAppointmentEmployeeService,
		iUmiConstantMapInjector,
		iClassConfigManager {

		use tUmiDataBaseInjector;
		use tCommonCollectionItem;
		use tUmiConstantMapInjector;
		use tClassConfigManager;

		/** @var int $employeeId идентификатор сотрудника */
		private $employeeId;

		/** @var int $serviceId идентификатор услуги */
		private $serviceId;

		/** @var array конфигурация класса */
		private static $classConfig = [
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'required' => true,
					'unchangeable' => true,
					'setter' => 'setId',
					'getter' => 'getId'
				],
				[
					'name' => 'EMPLOYEE_ID_FIELD_NAME',
					'required' => true,
					'setter' => 'setEmployeeId',
					'getter' => 'getEmployeeId'
				],
				[
					'name' => 'SERVICE_ID_FIELD_NAME',
					'required' => true,
					'setter' => 'setServiceId',
					'getter' => 'getServiceId'
				],
			]
		];

		/** @inheritdoc */
		public function getEmployeeId() {
			return $this->employeeId;
		}

		/** @inheritdoc */
		public function setEmployeeId($employeeId) {
			if (!is_numeric($employeeId)) {
				throw new Exception('Wrong value for employee id given');
			}

			if ($this->getEmployeeId() != $employeeId) {
				$this->setUpdatedStatus(true);
			}

			$this->employeeId = $employeeId;
			return true;
		}

		/** @inheritdoc */
		public function getServiceId() {
			return $this->serviceId;
		}

		/** @inheritdoc */
		public function setServiceId($serviceId) {
			if (!is_numeric($serviceId)) {
				throw new Exception('Wrong value for service id given');
			}

			if ($this->getServiceId() != $serviceId) {
				$this->setUpdatedStatus(true);
			}

			$this->serviceId = $serviceId;
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
			$employeeIdField = $connection->escape($map->get('EMPLOYEE_ID_FIELD_NAME'));
			$serviceIdField = $connection->escape($map->get('SERVICE_ID_FIELD_NAME'));

			$id = (int) $this->getId();
			$employeeId = (int) $this->getEmployeeId();
			$serviceId = (int) $this->getServiceId();

			$sql = <<<SQL
UPDATE `$tableName`
	SET `$employeeIdField` = $employeeId, `$serviceIdField` = $serviceId
		WHERE `$idField` = $id;
SQL;
			$connection->query($sql);

			return true;
		}
	}

