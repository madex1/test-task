<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts;

	/**
	 * Интерфейс части данных запроса с информацией о стоимости заказа
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts
	 */
	interface iOrderCost {

		/** @const string ASSESSED_COST_KEY ключ данных части запроса с оценочной стоимостью */
		const ASSESSED_COST_KEY = 'assessedCost';

		/** @const string DELIVERY_COST_KEY ключ данных части запроса со стоимость доставки */
		const DELIVERY_COST_KEY = 'deliveryCost';

		/** @const string COD_COST_KEY ключ данных части запроса с суммой наложенного платежа */
		const COD_COST_KEY = 'codCost';

		/** @const string PAYER_STATUS_KEY ключ данных части запроса с указанием, что доставка оплачивается получателем */
		const PAYER_STATUS_KEY = 'isDeliveryPayedByRecipient';

		/** @var string I18N_PATH группа используемых языковый меток */
		const I18N_PATH = 'emarket';

		/**
		 * Конструктор
		 * @param array $data данные части запроса
		 */
		public function __construct(array $data);

		/**
		 * Устанавливает данные части запроса
		 * @param array $data данные части запроса
		 * @return iOrderCost
		 */
		public function import(array $data);

		/**
		 * Возвращает данные запроса
		 * @return array
		 */
		public function export();

		/**
		 * Устанавливает оценочную стоимость заказа
		 * @param float $cost оценочная стоимость заказа
		 * @return iOrderCost
		 */
		public function setAssessedCost($cost);

		/**
		 * Возвращает оценочную стоимость заказа
		 * @return float
		 */
		public function getAssessedCost();

		/**
		 * Устанавливает стоимость доставки заказа
		 * @param float $cost стоимость доставки заказа
		 * @return iOrderCost
		 */
		public function setDeliveryCost($cost);

		/**
		 * Возвращает стоимость доставки заказа
		 * @return float
		 */
		public function getDeliveryCost();

		/**
		 * Устанавливает сумму наложенного платежа
		 * @param float $cost сумма наложенного платежа
		 * @return iOrderCost
		 */
		public function setCodCost($cost);

		/**
		 * Возвращает сумму наложенного платежа
		 * @return float
		 */
		public function getCodCost();
	}
