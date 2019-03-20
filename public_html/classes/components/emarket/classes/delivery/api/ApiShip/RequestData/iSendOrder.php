<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts;

	/**
	 * Интерфейс данных запроса на создание заказа в ApiShip
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData
	 */
	interface iSendOrder {

		/** @const string ORDER_KEY ключ данных запроса с общей информацией о заказе */
		const ORDER_KEY = 'order';

		/** @const string COST_KEY ключ данных запроса с информацией о стоимости заказа */
		const COST_KEY = 'cost';

		/** @const string SENDER_KEY ключ данных запроса с информацией об отправителе заказа */
		const SENDER_KEY = 'sender';

		/** @const string RECIPIENT_KEY ключ данных запроса с информацией о получателе заказа */
		const RECIPIENT_KEY = 'recipient';

		/** @const string ITEMS_KEY ключ данных запроса с информацией о товарах в заказе */
		const ITEMS_KEY = 'items';

		/**
		 * Конструктор
		 * @param array $data данные запроса
		 */
		public function __construct(array $data);

		/**
		 * Устанавливает данные запроса
		 * @param array $data данные запроса
		 * @return iSendOrder
		 */
		public function import(array $data);

		/**
		 * Возвращает данные запроса
		 * @return array
		 */
		public function export();

		/**
		 * Устанавливает часть данных запроса с общей информацией о заказе
		 * @param RequestDataParts\iOrder $order
		 * @return iSendOrder
		 */
		public function setOrder(RequestDataParts\iOrder $order);

		/**
		 * Возвращает часть данных запроса с общей информацией о заказе
		 * @return RequestDataParts\iOrder
		 */
		public function getOrder();

		/**
		 * Устанавливает часть данных запроса с информацией о стоимости заказа
		 * @param RequestDataParts\iOrderCost $cost
		 * @return iSendOrder
		 */
		public function setCost(RequestDataParts\iOrderCost $cost);

		/**
		 * Возвращает часть данных запроса с информацией о стоимости заказа
		 * @return RequestDataParts\iOrderCost
		 */
		public function getCost();

		/**
		 * Устанавливает часть данных запроса с информацией об отправителе заказа
		 * @param RequestDataParts\iDeliveryAgent $sender
		 * @return iSendOrder
		 */
		public function setSender(RequestDataParts\iDeliveryAgent $sender);

		/**
		 * Возвращает часть данных запроса с информацией об отправителе заказа
		 * @return RequestDataParts\iDeliveryAgent
		 */
		public function getSender();

		/**
		 * Устанавливает часть данных запроса с информацией о получателе заказа
		 * @param RequestDataParts\iDeliveryAgent $recipient
		 * @return iSendOrder
		 */
		public function setRecipient(RequestDataParts\iDeliveryAgent $recipient);

		/**
		 * Возвращает часть данных запроса с информацией о получателе заказа
		 * @return RequestDataParts\iDeliveryAgent
		 */
		public function getRecipient();

		/**
		 * Устанавливает части данных запроса с информацией об отправляемых товарах
		 * @param RequestDataParts\iOrderItem[] $items
		 * @return iSendOrder
		 */
		public function setItems(array $items);

		/**
		 * Возвращает части данных запроса с информацией об отправляемых товарах
		 * @return RequestDataParts\iOrderItem[]
		 */
		public function getItems();
	}
