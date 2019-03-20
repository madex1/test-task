<?php

	namespace UmiCms\Classes\Components\Emarket\Payment\Subject;

	use UmiCms\Classes\Components\Emarket\Payment\Subject;
	use UmiCms\Classes\Components\Emarket\Payment\iSubject;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\Factory as ReceiptParameterFactory;

	/**
	 * Класс признака предмета расчета
	 * @package UmiCms\Classes\Components\Emarket\Payment\Subject
	 */
	class Factory extends ReceiptParameterFactory implements iFactory {

		/**
		 * @inheritdoc
		 * @throws \wrongParamException
		 */
		public function create(\iUmiObject $object) {
			$this->validate($object);
			return new Subject($object);
		}

		/** @inheritdoc */
		public function getTypeGuid() {
			return iSubject::TYPE_GUID;
		}
	}
