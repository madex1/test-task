<?php

	namespace UmiCms\Classes\Components\Emarket\Tax\Rate\Vat;

	use UmiCms\Classes\Components\Emarket\Tax\Rate\Vat;
	use UmiCms\Classes\Components\Emarket\Tax\Rate\iVat;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\Factory as ReceiptParameterFactory;

	/**
	 * Класс фабрики ставок НДС
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate\Vat
	 */
	class Factory extends ReceiptParameterFactory implements iFactory {

		/**
		 * @inheritdoc
		 * @throws \wrongParamException
		 */
		public function create(\iUmiObject $object) {
			$this->validate($object);
			return new Vat($object);
		}

		/** @inheritdoc */
		public function getTypeGuid() {
			return iVat::TYPE_GUID;
		}
	}
