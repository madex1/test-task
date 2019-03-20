<?php

	/** Интерфейс заявки на прием */
	interface iAppointmentOrder {

		/**
		 * Возвращает идентификатор услуги
		 * @return int
		 */
		public function getServiceId();

		/**
		 * Устанавливает идентификатор услуги
		 * @param int $serviceId идентификатор услуги
		 * @return bool
		 */
		public function setServiceId($serviceId);

		/**
		 * Возвращает идентификатор сотрудника
		 * @return int
		 */
		public function getEmployeeId();

		/**
		 * Устанавливает идентификатор сотрудника
		 * @param int $employeeId идентификатор сотрудника
		 * @return bool
		 */
		public function setEmployeeId($employeeId);

		/**
		 * Возвращает дату создания заявки
		 * @return int timestamp
		 */
		public function getCreateDate();

		/**
		 * Устанавливает дату создания заявки
		 * @param mixed $date дата
		 * @return bool
		 */
		public function setCreateDate($date);

		/**
		 * Возвращает дату записи на прием
		 * @return int timestamp
		 */
		public function getDate();

		/**
		 * Устанавливает дату записи на прием
		 * @param mixed $date дата
		 * @return bool
		 */
		public function setDate($date);

		/**
		 * Возвращает время записи на прием в формате H:i:s
		 * @return string
		 */
		public function getTime();

		/**
		 * Устанавливает время записи на прием
		 * @param string $time время в формате H:i:s
		 * @return mixed
		 */
		public function setTime($time);

		/**
		 * Возвращает телефон оформителя заявки
		 * @return string|null
		 */
		public function getPhone();

		/**
		 * Устанавливает телефон оформителя заявки
		 * @param null|string $phone телефон
		 * @return bool
		 */
		public function setPhone($phone = null);

		/**
		 * Возвращает почтовый ящик оформителя заявки
		 * @return string|null
		 */
		public function getEmail();

		/**
		 * Устанавливает  почтовый ящик оформителя заявки
		 * @param null|string $email почтовый ящик
		 * @return bool
		 */
		public function setEmail($email = null);

		/**
		 * Возвращает имя отправителя
		 * @return string|null
		 */
		public function getName();

		/**
		 * Устанавливает имя отправителя
		 * @param string|null $name имя отправителя
		 * @return bool
		 */
		public function setName($name = null);

		/**
		 * Возвращает имя отправителя
		 * @return string|null
		 */
		public function getComment();

		/**
		 * Устанавливает комментарий отправителя
		 * @param null|string $comment комментарий
		 * @return bool
		 */
		public function setComment($comment = null);

		/**
		 * Возвращает код статуса заявки
		 * @return int
		 */
		public function getStatusId();

		/**
		 * Устанавливает код статуса заявки
		 * @param int $statusId код статуса заявки
		 * @return bool
		 */
		public function setStatusId($statusId);
	}
