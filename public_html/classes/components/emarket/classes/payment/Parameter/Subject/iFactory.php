<?php

	namespace UmiCms\Classes\Components\Emarket\Payment\Subject;

	use UmiCms\Classes\Components\Emarket\Payment\iSubject;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\iFactory as iReceiptParameterFactory;

	/**
	 * Интерфейс фабрики признака предмета расчета
	 * @package UmiCms\Classes\Components\Emarket\Payment\Subject
	 */
	interface iFactory extends iReceiptParameterFactory {

		/**
		 * Создает признак предмета расчета
		 * @param \iUmiObject $object объект признака предмета расчета
		 * @return iSubject
		 */
		public function create(\iUmiObject $object);
	}
