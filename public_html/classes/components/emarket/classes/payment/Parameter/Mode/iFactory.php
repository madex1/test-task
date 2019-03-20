<?php

	namespace UmiCms\Classes\Components\Emarket\Payment\Mode;

	use UmiCms\Classes\Components\Emarket\Payment\iMode;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\iFactory as iReceiptParameterFactory;

	/**
	 * Интерфейс фабрики признака способа расчета
	 * @package UmiCms\Classes\Components\Emarket\Payment\Mode
	 */
	interface iFactory extends iReceiptParameterFactory {

		/**
		 * Создает признак способа расчета
		 * @param \iUmiObject $object объект данных способа расчета
		 * @return iMode
		 */
		public function create(\iUmiObject $object);
	}
