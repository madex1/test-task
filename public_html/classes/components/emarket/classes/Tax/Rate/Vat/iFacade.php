<?php

	namespace UmiCms\Classes\Components\Emarket\Tax\Rate\Vat;

	use UmiCms\Classes\Components\Emarket\Tax\Rate\iVat;
	use UmiCms\Classes\Components\Emarket\Tax\Rate\iCalculator;
	use UmiCms\Classes\Components\Emarket\Tax\Rate\Parser\iFactory as iParserFactory;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\iFacade as iReceiptParameterFacade;

	/**
	 * Интерфейс фасада ставок налога на добавленную стоимость (НДС)
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate\Vat
	 */
	interface iFacade extends iReceiptParameterFacade {

		/** @const string TWENTY_PERCENT_VAT_GUID guid ставки 20% НДС */
		const TWENTY_PERCENT_VAT_GUID = 'tax-rate-27964';

		/**
		 * Возвращает ставку налога на добавленную стоимость (НДС)
		 * @param int $id идентификатор ставки
		 * @return iVat
		 */
		public function get($id);

		/**
		 * Возвращает ставку налога на добавленную стоимость 20% (НДС)
		 * @return iVat
		 */
		public function getTwentyPercentVat();

		/**
		 * Изменяет калькулятор ставки НДС
		 * @param iCalculator $calculator калькулятор ставки НДС
		 */
		public function setCalculator(iCalculator $calculator);

		/**
		 * Изменяет парсер ставки НДС
		 * @param iParserFactory $parser парсер ставки НДС
		 */
		public function setParser(iParserFactory $parser);

		/**
		 * Возвращает сумму налогов от общей стоимости заказа
		 * @param \order $order объект заказа
		 * @return int|float
		 */
		public function getOrderTax(\order $order);

		/**
		 * Возвращает сумму налогов от стоимости товара в заказе
		 * @param \orderItem $orderItem объект товара в заказе
		 * @return int|float
		 */
		public function getOrderItemTax(\orderItem $orderItem);

		/**
		 * Возвращает сумму налогов от стоимости доставки в заказе
		 * @param \order $order
		 * @return int|float
		 */
		public function getOrderDeliveryTax(\order $order);

	}
