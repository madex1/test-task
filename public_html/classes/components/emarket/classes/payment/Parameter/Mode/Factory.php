<?php

	namespace UmiCms\Classes\Components\Emarket\Payment\Mode;

	use UmiCms\Classes\Components\Emarket\Payment\Mode;
	use UmiCms\Classes\Components\Emarket\Payment\iMode;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\Factory as ReceiptParameterFactory;

	/**
	 * Класс признака способа расчета
	 * @package UmiCms\Classes\Components\Emarket\Payment\Mode
	 */
	class Factory extends ReceiptParameterFactory implements iFactory {

		/** @inheritdoc */
		public function create(\iUmiObject $object) {
			$this->validate($object);
			return new Mode($object);
		}

		/** @inheritdoc */
		public function getTypeGuid() {
			return iMode::TYPE_GUID;
		}
	}
