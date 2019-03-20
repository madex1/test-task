<?php

	namespace UmiCms\Classes\Components\Data;

	/**
	 * Интерфейс для сохранения данных формы
	 * @package UmiCms\Classes\Components\Data
	 */
	interface iFormSaver {

		/** @const string MENU_IMAGE_PATH путь к папке с изображениями меню */
		const MENU_IMAGE_PATH = '/cms/menu/';

		/** @const string HEADERS_IMAGE_PATH путь к папке с изображениями */
		const HEADERS_IMAGE_PATH = '/cms/headers/';

		/** @var string IMAGE_PATH путь к папке загружаемых изображений */
		const IMAGE_PATH = '/cms/data/';

		/**
		 * Сохраняет изменения объекта
		 * @param int $objectId идентификатор объекта
		 * @param bool $isNew является ли объект новым
		 * @param bool $ignorePermissions игнорировать проверку прав объекта
		 * @param bool $all изменять все возможные группы полей
		 * @return mixed
		 */
		public function saveEditedObject($objectId, $isNew = false, $ignorePermissions = false, $all = false);

		/**
		 * Сохраняет изменения объекта с возможностью проигнорировать проверку прав
		 * @param int $objectId идентификатор объекта
		 * @param bool $isNew является ли объект новым
		 * @param bool $ignorePermissions игнорировать проверку прав объекта
		 * @param bool $all изменять все возможные группы полей @deprecated
		 * @return array|bool
		 */
		public function saveEditedObjectWithIgnorePermissions(
			$objectId,
			$isNew = false,
			$ignorePermissions = false,
			$all = false
		);

		/**
		 * Подготавливает информацию о полях редактируемого объекта
		 * @param int $objectId идентификатор объекта
		 * @param bool $isNew является ли объект новым
		 * @return array
		 */
		public function prepareEditedObjectRequestData($objectId, $isNew = false);

		/**
		 * Сохраняет изменения объекта на основе массива данных
		 * @param int $objectId идентификатор объекта
		 * @param array $data информация о сохраняемом объекте
		 * @param bool $isNew является ли объект новым
		 * @param bool $ignorePermissions игнорировать проверку прав объекта
		 * @return array|bool|mixed
		 */
		public function saveEditedObjectData($objectId, array $data, $isNew = false, $ignorePermissions = false);

		/**
		 * Проверяет, допустимы ли данные для сохранения
		 * @param \iUmiObjectType $objectType тип редактируемого объекта
		 * @param mixed $data входные данные
		 * @param mixed $objectId ID объекта, данные которого проверяются
		 * @return array
		 */
		public function checkAllowedData(\iUmiObjectType $objectType, array $data, $objectId = false);

		/**
		 * Проверяет все ли обязательные поля заполнены и
		 * корректно ли заполнены поля с правилами валидации
		 * @param \iUmiObjectType $objectType тип данных, поля которого нужно проверить
		 * @param array $data массив значения полей [имя поля => значение поля]
		 * @param int $objectId идентификатор объекта, которому принадлежат поля
		 * @param bool $isNew является ли объект новым
		 * @return mixed
		 */
		public function checkRequiredData(\iUmiObjectType $objectType, $data, $objectId, $isNew);
	}