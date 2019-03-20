<?php

	/** Класс расписания работы на день сотрудника записи на прием */
	class AppointmentEmployeeSchedule implements
		iUmiCollectionItem,
		iUmiDataBaseInjector,
		iAppointmentEmployeeSchedule,
		iUmiConstantMapInjector,
		iClassConfigManager {

		use tUmiDataBaseInjector;
		use tCommonCollectionItem;
		use tUmiConstantMapInjector;
		use tClassConfigManager;

		/** @var int $employeeId идентификатор сотрудника */
		private $employeeId;

		/** @var int $dayNumber номер дня (0-6) */
		private $dayNumber;

		/** @var string $timeStart время начала работы в формате H:i:s */
		private $timeStart;

		/** @var string $timeEnd время окончания работы в формате H:i:s */
		private $timeEnd;

		/** @var array $daysNumbers допустимые номера дней недели */
		private $daysNumbers = [
			0, 1, 2, 3, 4, 5, 6
		];

		/** @var string $timePattern шаблон для валидации значения времени */
		private $timePattern = '/([0-9]{2}:[0-9]{2}:[0-9]{2})/';

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
					'name' => 'DAY_NUMBER_FIELD_NAME',
					'required' => true,
					'setter' => 'setDayNumber',
					'getter' => 'getDayNumber'
				],
				[
					'name' => 'TIME_START_FIELD_NAME',
					'required' => true,
					'setter' => 'setTimeStart',
					'getter' => 'getTimeStart'
				],
				[
					'name' => 'TIME_END_FIELD_NAME',
					'required' => true,
					'setter' => 'setTimeEnd',
					'getter' => 'getTimeEnd'
				]
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
		public function getDayNumber() {
			return $this->dayNumber;
		}

		/** @inheritdoc */
		public function setDayNumber($number) {
			if (!in_array($number, $this->daysNumbers)) {
				throw new Exception('Wrong value for day number given');
			}

			if ($this->getDayNumber() != $number) {
				$this->setUpdatedStatus(true);
			}

			$this->dayNumber = $number;
			return true;
		}

		/** @inheritdoc */
		public function getTimeStart() {
			return $this->timeStart;
		}

		/** @inheritdoc */
		public function setTimeStart($time) {
			if (!is_string($time) || !preg_match($this->timePattern, $time)) {
				throw new Exception('Wrong value for time given');
			}

			if ($this->getTimeStart() != $time) {
				$this->setUpdatedStatus(true);
			}

			$this->timeStart = $time;
			return true;
		}

		/** @inheritdoc */
		public function getTimeEnd() {
			return $this->timeEnd;
		}

		/** @inheritdoc */
		public function setTimeEnd($time) {
			if (!is_string($time) || !preg_match($this->timePattern, $time)) {
				throw new Exception('Wrong value for time given');
			}

			if ($this->getTimeEnd() != $time) {
				$this->setUpdatedStatus(true);
			}

			$this->timeEnd = $time;
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
			$dayNumberField = $connection->escape($map->get('DAY_NUMBER_FIELD_NAME'));
			$timeStartField = $connection->escape($map->get('TIME_START_FIELD_NAME'));
			$timeEndField = $connection->escape($map->get('TIME_END_FIELD_NAME'));

			$id = (int) $this->getId();
			$employeeId = (int) $this->getEmployeeId();
			$dayNumber = (int) $this->getDayNumber();
			$timeStart = $connection->escape($this->getTimeStart());
			$timeEnd = $connection->escape($this->getTimeEnd());

			$sql = <<<SQL
UPDATE `$tableName`
	SET `$employeeIdField` = $employeeId, `$dayNumberField` = $dayNumber, `$timeStartField` = '$timeStart', `$timeEndField` = '$timeEnd'
		WHERE `$idField` = $id;
SQL;
			$connection->query($sql);

			return true;
		}
	}

