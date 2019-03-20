<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer\Receipt;

	use UmiCms\Classes\Components\Emarket\Serializer\Receipt;

	/**
	 * Класс сериализатора для чека по ФЗ-54 для api Робокасса
	 * @package UmiCms\Classes\Components\Emarket\Serializer
	 */
	class RoboKassa extends Receipt {

		/** @const int MAX_ORDER_ITEM_NAME_LENGTH максимальная длина названия товара (0 - первый символ) */
		const MAX_ORDER_ITEM_NAME_LENGTH = 63;

		/**
		 * @inheritdoc
		 * @param \order $order
		 * @return array
		 *
		 * [
		 *      'name' => Название товара,
		 *      'quantity' => Количество товара,
		 *      'sum' => Цена за единицу товара,
		 *      'tax' => id ставки НДС
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
				'name' => $this->prepareItemName($delivery->getName()),
				'quantity' => sprintf('%.3f', 1),
				'sum' => sprintf('%.2f', $order->getDeliveryPrice()),
				'tax' => $this->getVat($delivery)->getRoboKassaId(),
				'payment_object' => $this->getPaymentSubject($delivery)->getRoboKassaId(),
				'payment_method' => $this->getPaymentMode($delivery)->getRoboKassaId()
			];
		}

		/**
		 * @inheritdoc
		 * @param \order $order
		 * @param \orderItem $orderItem
		 * @return array
		 *
		 * [
		 *      'name' => Название товара,
		 *      'quantity' => Количество товара,
		 *      'sum' => Цена за единицу товара,
		 *      'tax' => id ставки НДС
		 * ]
		 * @throws \publicException
		 * @throws \coreException
		 * @throws \privateException
		 */
		protected function getOrderItemInfo(\order $order, \orderItem $orderItem) {
			return [
				'name' => $this->prepareItemName($orderItem->getName()),
				'quantity' => sprintf('%.3f', $orderItem->getAmount()),
				'sum' => sprintf('%.2f', $this->getOrderItemPrice($order, $orderItem)),
				'tax' => $this->getVat($orderItem)->getRoboKassaId(),
				'payment_object' => $this->getPaymentSubject($orderItem)->getRoboKassaId(),
				'payment_method' => $this->getPaymentMode($orderItem)->getRoboKassaId()
			];
		}

		/** @inheritdoc */
		protected function fixItemPriceSummary(\order $order, array $orderItemList) {
			$calculatedOrderPrice = 0;

			foreach ($orderItemList as $orderItemData) {
				$calculatedOrderPrice += $orderItemData['sum'];
			}

			$lastIndex = count($orderItemList) - 1;

			if ($order->getActualPrice() != $calculatedOrderPrice) {
				$priceDiff = $order->getActualPrice() - $calculatedOrderPrice;
				$orderItemList[$lastIndex]['sum'] += $priceDiff;
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
