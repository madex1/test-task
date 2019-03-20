<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter;

	/**
	 * Абстрактная фабрика параметра чека платежной системы
	 * @package UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter
	 */
	abstract class Factory implements iFactory {

		/**
		 * @inheritdoc
		 * @throws \wrongParamException
		 */
		abstract public function create(\iUmiObject $object);

		/** @inheritdoc */
		abstract public function getTypeGuid();

		/**
		 * Валидирует объект данных параметра чека
		 * @param \iUmiObject $object объект данных параметра чека
		 * @throws \wrongParamException
		 */
		protected function validate(\iUmiObject $object) {
			$typeGuid = $this->getTypeGuid();

			if ($object->getTypeGUID() !== $typeGuid) {
				$message = sprintf('Data object must have type "%s"', $typeGuid);
				throw new \wrongParamException($message);
			}
		}
	}