<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\OrderStatuses;

	/**
	 * Интерфейс заказа ApiShip
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders
	 */
	interface iEntity extends \iUmiCollectionItem {

		/**
		 * Устанавливает номер
		 * @param int $number номер
		 * @return iEntity
		 */
		public function setNumber($number);

		/**
		 * Возвращает номер заказа
		 * @return int
		 */
		public function getNumber();

		/**
		 * Устанавливает номер связанного заказа UMI.CMS
		 * @param int $number номер связанного заказа UMI.CMS
		 * @return iEntity
		 */
		public function setUmiOrderRefNumber($number);

		/**
		 * Возвращает номер связанного заказа UMI.CMS
		 * @return int
		 */
		public function getUmiOrderRefNumber();

		/**
		 * Устанавливает номер связанного заказа службы доставки
		 * @param string $number номер связанного заказа службы доставки
		 * @return iEntity
		 */
		public function setProviderOrderRefNumber($number);

		/**
		 * Возвращает номер связанного заказа службы доставки
		 * @return string
		 */
		public function getProviderOrderRefNumber();

		/**
		 * Устанавливает статус
		 * @param OrderStatuses $orderStatusId идентификатор статуса
		 * @return iEntity
		 */
		public function setStatus(OrderStatuses $orderStatusId);

		/**
		 * Возвращает статус
		 * @return string
		 */
		public function getStatus();

		/** @inheritdoc */
		public function commit();
	}
