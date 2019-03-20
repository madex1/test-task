<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders;

	/**
	 * Интерфейс коллекции заказов ApiShip
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders
	 */
	interface iCollection extends \iUmiCollection {

		/**
		 * Создает заказ ApiShip
		 * @param int $orderNumber номер заказа в ApiShip
		 * @param int $umiOrderRefNumber номер связанного заказа в UMI.CMS
		 * @return iEntity созданный заказ
		 * @throws \Exception
		 * @throws \RequiredPropertyHasNoValueException
		 */
		public function createOrder($orderNumber, $umiOrderRefNumber);

		/**
		 * Возвращает заказы ApiShip по их идентификаторам
		 * @param array $ordersIds идентификаторы
		 * @return iEntity[]
		 * @throws \RequiredPropertyHasNoValueException
		 */
		public function getOrdersByIds($ordersIds);

		/**
		 * Возвращает заказ ApiShip по номеру связанного заказа в UMI.CMS
		 * @param int $umiOrderRefNumber номер связанного заказа
		 * @return iEntity|null
		 */
		public function getByUmiOrderRefNumber($umiOrderRefNumber);
	}
