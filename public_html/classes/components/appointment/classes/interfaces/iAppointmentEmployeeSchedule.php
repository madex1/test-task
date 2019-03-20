<?php

	/** Интерфейс расписания сотрудника на день */
	interface iAppointmentEmployeeSchedule {

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
		 * Возвращает номер дня недели (0-6)
		 * @return int
		 */
		public function getDayNumber();

		/**
		 * Устанавливает номер дня недели
		 * @param int $number номер дня недели (0-6)
		 * @return bool
		 */
		public function setDayNumber($number);

		/**
		 * Возвращает время начала работы в формате H:i:s
		 * @return string
		 */
		public function getTimeStart();

		/**
		 * Устанавливает время начала работы
		 * @param string $time время в формате H:i:s
		 * @return mixed
		 */
		public function setTimeStart($time);

		/**
		 * Возвращает время окончания работы в формате H:i:s
		 * @return string
		 */
		public function getTimeEnd();

		/**
		 * Устанавливает время окончания работы
		 * @param string $time время в формате H:i:s
		 * @return mixed
		 */
		public function setTimeEnd($time);
	}
