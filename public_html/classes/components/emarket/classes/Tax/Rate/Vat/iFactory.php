<?php

	namespace UmiCms\Classes\Components\Emarket\Tax\Rate\Vat;

	use UmiCms\Classes\Components\Emarket\Tax\Rate\iVat;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\iFactory as iReceiptParameterFactory;

	/**
	 * Интерфейс фабрики ставок налога на добавленную стоимость (НДС)
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate\Vat
	 */
	interface iFactory extends iReceiptParameterFactory {

		/**
		 * Создает ставку налога на добавленную стоимость (НДС)
		 * @param \iUmiObject $object объект данных ставки налога
		 * @return iVat
		 */
		public function create(\iUmiObject $object);
	}