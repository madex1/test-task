<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer\Receipt;

	use UmiCms\Classes\Components\Emarket\Serializer\Receipt;

	/**
	 * Класс сериализатора для чека по ФЗ-54 для api Яндекс.Касса версии 3 (устарела)
	 * @package UmiCms\Classes\Components\Emarket\Serializer
	 */
	class YandexKassa3 extends Receipt {

		/** @const int MAX_CUSTOMER_CONTACT_LENGTH максимальная длина контакта покупателя (0 - первый символ) */
		const MAX_CUSTOMER_CONTACT_LENGTH = 63;

		/** @const int MAX_ORDER_ITEM_NAME_LENGTH максимальная длина названия товара (0 - первый символ) */
		const MAX_ORDER_ITEM_NAME_LENGTH = 127;

		/**
		 * @inheritdoc
		 * @param \order $order
		 * @return array
		 *
		 * [
		 *       'customerContact' => Почтовый ящик покупателя
		 * ]
		 *
		 * @throws \publicException
		 */
		public function getContact(\order $order) {
			return [
				'customerContact' => mb_substr(parent::getContact($order), 0, self::MAX_CUSTOMER_CONTACT_LENGTH)
			];
		}

		/**
		 * @inheritdoc
		 * @param \order $order
		 * @return array
		 *
		 * [
		 *      'quantity' => Количество товара
		 *      'price' => [
		 *          'amount' => Цена за единицу товара
		 *      ],
		 *      'tax' => id ставки НДС
		 *      'text' => Название товара
		 * ]
		 *
		 * @throws \expectObjectException
		 * @throws \publicException
		 * @throws \coreException
		 * @throws \privateException
		 */
		protected function getDeliveryInfo(\order $order) {
			$delivery = $this->getDelivery($order);

			return [
				'quantity' => sprintf('%.3f', 1),
				'price' => [
					'amount' => sprintf('%.2f', $order->getDeliveryPrice())
				],
				'tax' => (int) $this->getVat($delivery)->getYandexKassaId(),
				'paymentSubjectType' => $this->getPaymentSubject($delivery)->getYandexKassaId(),
				'paymentMethodType' => $this->getPaymentMode($delivery)->getYandexKassaId(),
				'text' => $this->prepareItemName($delivery->getName())
			];
		}

		/**
		 * @inheritdoc
		 * @param \order $order
		 * @param \orderItem $orderItem
		 * @return array
		 *
		 * [
		 *      'quantity' => Количество товара
		 *      'price' => [
		 *          'amount' => Цена за единицу товара
		 *      ],
		 *      'tax' => id ставки НДС
		 *      'text' => Название товара
		 * ]
		 * @throws \publicException
		 * @throws \coreException
		 * @throws \privateException
		 */
		protected function getOrderItemInfo(\order $order, \orderItem $orderItem) {
			return [
				'quantity' => sprintf('%.3f', $orderItem->getAmount()),
				'price' => [
					'amount' => sprintf('%.2f', $this->getOrderItemPrice($order, $orderItem))
				],
				'tax' => (int) $this->getVat($orderItem)->getYandexKassaId(),
				'paymentSubjectType' => $this->getPaymentSubject($orderItem)->getYandexKassaId(),
				'paymentMethodType' => $this->getPaymentMode($orderItem)->getYandexKassaId(),
				'text' => $this->prepareItemName($orderItem->getName())
			];
		}

		/** @inheritdoc */
		protected function fixItemPriceSummary(\order $order, array $orderItemList) {
			$calculatedOrderPrice = 0;

			foreach ($orderItemList as $orderItemData) {
				$calculatedOrderPrice += $orderItemData['price']['amount'];
			}

			$lastIndex = count($orderItemList) - 1;

			if ($order->getActualPrice() != $calculatedOrderPrice) {
				$priceDiff = $order->getActualPrice() - $calculatedOrderPrice;
				$orderItemList[$lastIndex]['price']['amount'] += $priceDiff;
			}

			return [
				'items' => $orderItemList
			];
		}

		/** @inheritdoc */
		protected function prepareItemName($name) {
			return mb_substr($name, 0, self::MAX_ORDER_ITEM_NAME_LENGTH);
		}
	}
