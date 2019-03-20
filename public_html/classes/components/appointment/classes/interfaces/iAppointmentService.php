<?php

	/** Интерфейс услуги для записи на прием */
	interface iAppointmentService {

		/**
		 * Возвращает идентификатор группы услуг
		 * @return int
		 */
		public function getGroupId();

		/**
		 * Устанавливает идентификатор группы услуг
		 * @param int $groupId идентификатор группы услуг
		 * @return bool
		 */
		public function setGroupId($groupId);

		/**
		 * Возвращает название услуги
		 * @return string
		 */
		public function getName();

		/**
		 * Устанавливает название услуги
		 * @param string $name название
		 * @return bool
		 */
		public function setName($name);

		/**
		 * Возвращает время выполнения услуги в формате H:i:s
		 * @return string
		 */
		public function getTime();

		/**
		 * Устанавливает время выполнения услуги
		 * @param string $time время в формате H:i:s
		 * @return bool
		 */
		public function setTime($time);

		/**
		 * Возвращает стоимость услуги
		 * @return float
		 */
		public function getPrice();

		/**
		 * Устанавливает стоимость услуги
		 * @param float $price стоимость
		 * @return bool
		 */
		public function setPrice($price);
	}
