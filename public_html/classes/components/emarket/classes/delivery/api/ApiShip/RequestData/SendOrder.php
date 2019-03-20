<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils\ArgumentsValidator;

	/**
	 * Данные запроса на создание заказа в ApiShip
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData
	 */
	class SendOrder implements iSendOrder {

		/** @var RequestDataParts\iOrder $order часть данных запроса с общей информацией о заказе */
		private $order;

		/** @var RequestDataParts\iOrderCost $cost часть данных запроса с информацией о стоимости заказа */
		private $cost;

		/** @var RequestDataParts\iDeliveryAgent $sender часть данных запроса с информацией об отправителе заказа */
		private $sender;

		/** @var RequestDataParts\iDeliveryAgent $recipient часть данных запроса с информацией о получателе заказа */
		private $recipient;

		/** @var RequestDataParts\iOrderItem[] $items части данных запроса с информацией об отправляемых товарах */
		private $items = [];

		/** @inheritdoc */
		public function __construct(array $data) {
			$this->import($data);
		}

		/** @inheritdoc */
		public function import(array $data) {
			ArgumentsValidator::arrayContainsValue($data, self::ORDER_KEY, __METHOD__, self::ORDER_KEY);
			$this->setOrder($data[self::ORDER_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::COST_KEY, __METHOD__, self::COST_KEY);
			$this->setCost($data[self::COST_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::SENDER_KEY, __METHOD__, self::SENDER_KEY);
			$this->setSender($data[self::SENDER_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::RECIPIENT_KEY, __METHOD__, self::RECIPIENT_KEY);
			$this->setRecipient($data[self::RECIPIENT_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::ITEMS_KEY, __METHOD__, self::ITEMS_KEY);
			$this->setItems($data[self::ITEMS_KEY]);

			return $this;
		}

		/** @inheritdoc */
		public function export() {
			$data = [
				self::ORDER_KEY => $this->getOrder()
					->export(),
				self::COST_KEY => $this->getCost()
					->export(),
				self::SENDER_KEY => $this->getSender()
					->export(),
				self::RECIPIENT_KEY => $this->getRecipient()
					->export(),
			];

			foreach ($this->getItems() as $item) {
				$data[self::ITEMS_KEY][] = $item->export();
			}

			return $data;
		}

		/** @inheritdoc */
		public function setOrder(RequestDataParts\iOrder $order) {
			$this->order = $order;
			return $this;
		}

		/** @inheritdoc */
		public function getOrder() {
			return $this->order;
		}

		/** @inheritdoc */
		public function setCost(RequestDataParts\iOrderCost $cost) {
			$this->cost = $cost;
			return $this;
		}

		/** @inheritdoc */
		public function getCost() {
			return $this->cost;
		}

		/** @inheritdoc */
		public function setSender(RequestDataParts\iDeliveryAgent $sender) {
			$this->sender = $sender;
			return $this;
		}

		/** @inheritdoc */
		public function getSender() {
			return $this->sender;
		}

		/** @inheritdoc */
		public function setRecipient(RequestDataParts\iDeliveryAgent $recipient) {
			$this->recipient = $recipient;
			return $this;
		}

		/** @inheritdoc */
		public function getRecipient() {
			return $this->recipient;
		}

		/** @inheritdoc */
		public function setItems(array $items) {
			$this->validateOrderItems($items);
			$this->items = $items;
			return $this;
		}

		/** @inheritdoc */
		public function getItems() {
			return $this->items;
		}

		/**
		 * Валидирует части данных запроса с информацией о товарных наименования заказа
		 * @param array $items части данных запроса с информацией о товарных наименования заказа
		 * @return RequestDataParts\iOrderItem[]
		 */
		private function validateOrderItems(array $items) {
			return array_map(function (RequestDataParts\iOrderItem $item) {
				return $item;
			}, $items);
		}
	}
