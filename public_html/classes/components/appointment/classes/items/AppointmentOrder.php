<?php

	/** Заявка на прием */
	class AppointmentOrder implements
		iUmiCollectionItem,
		iUmiDataBaseInjector,
		iAppointmentOrder,
		iUmiConstantMapInjector,
		iClassConfigManager {

		use tUmiDataBaseInjector;
		use tCommonCollectionItem;
		use tUmiConstantMapInjector;
		use tClassConfigManager;

		/** @var int $serviceId идентификатор услуги */
		private $serviceId;

		/** @var int $employeeId идентификатор сотрудника */
		private $employeeId;

		/** @var string $date возвращает дату записи на прием */
		private $date;

		/** @var string $time возвращает время записи на прием */
		private $time;

		/** @var int $createDate возвращает дату создания заявки */
		private $createDate;

		/** @var string|null $phone телефон оформителя заявки */
		private $phone;

		/** @var string|null $email почтовый ящик оформителя заявки */
		private $email;

		/** @var string|null $name имя оформителя заявки */
		private $name;

		/** @var string|null $comment комментарий оформителя заявки */
		private $comment;

		/** @var int $statusId код статуса заявки */
		private $statusId;

		/** @var array $statusesIds возможные коды статуса заявки */
		private $statusesIds = [];

		/** @var string $timePattern шаблон для валидации значения времени */
		private $timePattern = '/([0-9]{2}:[0-9]{2}:[0-9]{2})/';

		/** @var array конфигурация класса */
		private static $classConfig = [
			'constructor' => [
				'callback' => [
					'before' => 'constructorCallbackBefore',
					'after' => 'constructorCallbackAfter'
				]
			],
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'required' => true,
					'unchangeable' => true,
					'setter' => 'setId',
					'getter' => 'getId',
				],
				[
					'name' => 'SERVICE_ID_FIELD_NAME',
					'required' => true,
					'setter' => 'setServiceId',
					'getter' => 'getServiceId',
				],
				[
					'name' => 'EMPLOYEE_ID_FIELD_NAME',
					'required' => false,
					'setter' => 'setEmployeeId',
					'getter' => 'getEmployeeId',
				],
				[
					'name' => 'ORDER_DATE_FIELD_NAME',
					'required' => true,
					'setter' => 'setCreateDate',
					'getter' => 'getCreateDate',
				],
				[
					'name' => 'DATE_FIELD_NAME',
					'required' => true,
					'setter' => 'setDate',
					'getter' => 'getDate',
				],
				[
					'name' => 'TIME_FIELD_NAME',
					'required' => true,
					'setter' => 'setTime',
					'getter' => 'getTime',
				],
				[
					'name' => 'PHONE_FIELD_NAME',
					'required' => false,
					'setter' => 'setPhone',
					'getter' => 'getPhone',
				],
				[
					'name' => 'EMAIL_FIELD_NAME',
					'required' => false,
					'setter' => 'setEmail',
					'getter' => 'getEmail',
				],
				[
					'name' => 'NAME_FIELD_NAME',
					'required' => false,
					'setter' => 'setName',
					'getter' => 'getName',
				],
				[
					'name' => 'COMMENT_FIELD_NAME',
					'required' => false,
					'setter' => 'setComment',
					'getter' => 'getComment',
				],
				[
					'name' => 'STATUS_ID_FIELD_NAME',
					'required' => true,
					'setter' => 'setStatusId',
					'getter' => 'getStatusId',
				],
			]
		];

		/**
		 * Обработчик метода tCommonCollectionItem::__construct()#before.
		 * Устанавливает статусы заявки на запись и преобразует значения
		 * для полей типа дата.
		 * @param array $params параметры инициализации
		 * @return array
		 */
		public function constructorCallbackBefore(array $params) {
			$map = $this->getMap();

			$this->statusesIds = [
				$map->get('ORDER_STATUS_NOT_CONFIRMED'),
				$map->get('ORDER_STATUS_CONFIRMED'),
				$map->get('ORDER_STATUS_DECLINED')
			];

			$dateFieldsNames = [
				$map->get('ORDER_DATE_FIELD_NAME'),
				$map->get('DATE_FIELD_NAME')
			];

			foreach ($params as $fieldName => &$fieldValue) {
				if (in_array($fieldName, $dateFieldsNames)) {
					$fieldValue = ($fieldValue instanceof umiDate) ? $fieldValue : new umiDate($fieldValue);
				}
			}

			return $params;
		}

		/**
		 * Обработчик метода tCommonCollectionItem::__construct()#after.
		 * @param array $params параметры инициализации
		 * @throws Exception
		 */
		public function constructorCallbackAfter(array $params) {
			if ($this->getPhone() === null && $this->getEmail() === null) {
				throw new Exception('Phone and email cannot be empty both');
			}
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
		public function getEmployeeId() {
			return $this->employeeId;
		}

		/** @inheritdoc */
		public function setEmployeeId($employeeId) {
			if (!is_numeric($employeeId) && $employeeId !== null) {
				throw new Exception('Wrong value for employee id given');
			}

			if ($this->getEmployeeId() != $employeeId) {
				$this->setUpdatedStatus(true);
			}

			$this->employeeId = $employeeId;
			return true;
		}

		/** @inheritdoc */
		public function getCreateDate() {
			return $this->createDate;
		}

		/** @inheritdoc */
		public function setCreateDate($date) {
			$timestamp = ($date instanceof umiDate) ? $date->getDateTimeStamp() : (int) $date;

			if ($this->getCreateDate() != $timestamp) {
				$this->setUpdatedStatus(true);
			}

			$this->createDate = $timestamp;
			return true;
		}

		/** @inheritdoc */
		public function getDate() {
			return $this->date;
		}

		/** @inheritdoc */
		public function setDate($date) {
			$timestamp = ($date instanceof umiDate) ? $date->getDateTimeStamp() : (int) $date;

			if ($this->getDate() != $timestamp) {
				$this->setUpdatedStatus(true);
			}

			$this->date = $timestamp;
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
		public function getPhone() {
			return $this->phone;
		}

		/** @inheritdoc */
		public function setPhone($phone = null) {
			if ((!is_string($phone) || $phone === '') && $phone !== null) {
				throw new Exception('Wrong value for phone given');
			}

			if ($this->getPhone() != $phone) {
				$this->setUpdatedStatus(true);
			}

			$this->phone = $phone;
			return true;
		}

		/** @inheritdoc */
		public function getEmail() {
			return $this->email;
		}

		/** @inheritdoc */
		public function setEmail($email = null) {
			if (!umiMail::checkEmail($email)) {
				throw new Exception('Wrong value for email given');
			}

			$email = ($email === '') ? null : $email;

			if ($this->getEmail() != $email) {
				$this->setUpdatedStatus(true);
			}

			$this->email = $email;
			return true;
		}

		/** @inheritdoc */
		public function getName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function setName($name = null) {
			if ((!is_string($name) || $name === '') && $name !== null) {
				throw new Exception('Wrong value for name given');
			}

			if ($this->getName() != $name) {
				$this->setUpdatedStatus(true);
			}

			$this->name = $name;
			return true;
		}

		/** @inheritdoc */
		public function getComment() {
			return $this->comment;
		}

		/** @inheritdoc */
		public function setComment($comment = null) {
			if (!is_string($comment) && $comment !== null) {
				throw new Exception('Wrong value for comment given');
			}

			if ($this->getComment() != $comment) {
				$this->setUpdatedStatus(true);
			}

			$this->comment = $comment;
			return true;
		}

		/** @inheritdoc */
		public function getStatusId() {
			return $this->statusId;
		}

		/** @inheritdoc */
		public function setStatusId($statusId) {
			if (!in_array($statusId, $this->statusesIds)) {
				throw new Exception('Wrong value for status id given');
			}

			if ($this->getStatusId() != $statusId) {
				$this->setUpdatedStatus(true);
			}

			$this->statusId = $statusId;
			return true;
		}

		/** @inheritdoc */
		public function commit() {
			if (!$this->isUpdated()) {
				return false;
			}

			if ($this->getPhone() === null && $this->getEmail() === null) {
				throw new Exception('Phone and email cannot be empty both');
			}

			$map = $this->getMap();
			$connection = $this->getConnection();
			$tableName = $connection->escape($map->get('TABLE_NAME'));
			$idField = $connection->escape($map->get('ID_FIELD_NAME'));
			$serviceIdField = $connection->escape($map->get('SERVICE_ID_FIELD_NAME'));
			$employeeIdField = $connection->escape($map->get('EMPLOYEE_ID_FIELD_NAME'));
			$createDateField = $connection->escape($map->get('ORDER_DATE_FIELD_NAME'));
			$dateField = $connection->escape($map->get('DATE_FIELD_NAME'));
			$timeField = $connection->escape($map->get('TIME_FIELD_NAME'));
			$phoneField = $connection->escape($map->get('PHONE_FIELD_NAME'));
			$emailField = $connection->escape($map->get('EMAIL_FIELD_NAME'));
			$nameField = $connection->escape($map->get('NAME_FIELD_NAME'));
			$commentField = $connection->escape($map->get('COMMENT_FIELD_NAME'));
			$statusIdField = $connection->escape($map->get('STATUS_ID_FIELD_NAME'));

			$id = (int) $this->getId();
			$serviceId = (int) $this->getServiceId();
			$employeeId = ($this->getEmployeeId() === null) ? 'NULL' : (int) $this->getEmployeeId();
			$createDate = (int) $this->getCreateDate();
			$date = (int) $this->getDate();
			$time = "'" . $connection->escape($this->getTime()) . "'";
			$phone = ($this->getPhone() === null) ? 'NULL' : "'" . $connection->escape($this->getPhone()) . "'";
			$email = ($this->getEmail() === null) ? 'NULL' : "'" . $connection->escape($this->getEmail()) . "'";
			$name = ($this->getName() === null) ? 'NULL' : "'" . $connection->escape($this->getName()) . "'";
			$comment = ($this->getComment() === null) ? 'NULL' : "'" . $connection->escape($this->getComment()) . "'";
			$statusId = (int) $this->getStatusId();

			$sql = <<<SQL
UPDATE `$tableName`
	SET `$serviceIdField` = $serviceId, `$employeeIdField` = $employeeId, `$createDateField` = $createDate, `$dateField` = $date, `$timeField` = $time,
	 `$phoneField` = $phone, `$emailField` = $email, `$nameField` = $name, `$commentField` = $comment, `$statusIdField` = $statusId
		WHERE `$idField` = $id;
SQL;
			$connection->query($sql);

			return true;
		}
	}
