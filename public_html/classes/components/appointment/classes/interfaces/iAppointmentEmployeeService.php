<?php

	/** Интерфейс связи сотрудник-услуга записи на прием */
	interface iAppointmentEmployeeService {

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
		 * Возвращает идентификатор услуги
		 * @return int
		 */
		public function getServiceId();

		/**
		 * Устанавливает идентификатор услуги
		 * @param int $serviceId идентификатор услуги
		 * @return mixed
		 */
		public function setServiceId($serviceId);
	}
