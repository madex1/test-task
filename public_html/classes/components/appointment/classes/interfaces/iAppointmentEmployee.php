<?php

	/** Интерфейс сотрудника записи на прием */
	interface iAppointmentEmployee {

		/**
		 * Возвращает имя сотрудника
		 * @return string
		 */
		public function getName();

		/**
		 * Устанавливает имя сотрудника
		 * @param string $name имя сотрудника
		 * @return bool
		 */
		public function setName($name);

		/**
		 * Возвращает путь до фотографии сотрудника
		 * @return string
		 */
		public function getPhoto();

		/**
		 * Устанавливает фотографию сотрудника
		 * @param mixed $photo фотография сотрудника
		 * @return bool
		 */
		public function setPhoto($photo);

		/**
		 * Возвращает описание сотрудника
		 * @return string
		 */
		public function getDescription();

		/**
		 * Устанавливает описание сотрудника
		 * @param string $description описание сотрудника
		 * @return mixed
		 */
		public function setDescription($description);
	}
