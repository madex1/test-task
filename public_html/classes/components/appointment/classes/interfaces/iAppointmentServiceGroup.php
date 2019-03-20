<?php

	/** Интерфейс группы услуг для записи на прием */
	interface iAppointmentServiceGroup {

		/**
		 * Возвращает название группы услуг
		 * @return string
		 */
		public function getName();

		/**
		 * Устанавливает название группы услуг
		 * @param string $name название группы услуг
		 * @return bool
		 */
		public function setName($name);
	}
