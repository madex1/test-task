<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\Address;

	/**
	 * Интерфейс фабрики адресов доставки
	 * @package UmiCms\Classes\Components\Emarket\Delivery\Address
	 */
	interface iAddressFactory {

		/**
		 * Создает адрес доставки на основе объекта-источника данных адреса доставки
		 * @param \iUmiObject $object объект-источник данных
		 * @return iAddress
		 */
		public static function createByObject(\iUmiObject $object);

		/**
		 * Создает адрес доставки на основе идентификатора объекта-источника данных адреса доставки
		 * @param int $objectId идентификатор объекта-источника данных
		 * @return iAddress
		 * @throws \expectObjectException
		 */
		public static function createByObjectId($objectId);
	}
