<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\Address;

	/**
	 * Фабрика адресов доставки
	 * @package UmiCms\Classes\Components\Emarket\Delivery\Address
	 */
	class AddressFactory implements iAddressFactory {

		/** @inheritdoc */
		public static function createByObject(\iUmiObject $object) {
			return new Address($object);
		}

		/** @inheritdoc */
		public static function createByObjectId($objectId) {
			$objectId = (int) $objectId;
			$object = \umiObjectsCollection::getInstance()
				->getObject($objectId);

			if (!$object instanceof \iUmiObject) {
				$exceptionMessage = sprintf(getLabel('label-error-object-not-found'), $objectId);
				throw new \expectObjectException($exceptionMessage);
			}

			return self::createByObject($object);
		}
	}
