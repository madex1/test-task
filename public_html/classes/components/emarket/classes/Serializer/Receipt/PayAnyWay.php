<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer\Receipt;

	use UmiCms\Classes\Components\Emarket\Serializer\Receipt;

	/**
	 * Класс сериализатора для чека по ФЗ-54 для api PayAnyWay
	 * @package UmiCms\Classes\Components\Emarket\Serializer
	 */
	class PayAnyWay extends Receipt {

		/**
		 * @inheritdoc
		 * @param \order $order
		 * @return \stdClass
		 *
		 * {
		 *      'name' => Название товара,
		 *      'price' => Цена за единицу товара,
		 *      'quantity' => Количество товара в заказе,
		 *      'vatTag' => id ставки НДС
		 * }
		 *
		 * @throws \expectObjectException
		 * @throws \publicException
		 * @throws \coreException
		 * @throws \privateException
		 */
		protected function getDeliveryInfo(\order $order) {
			$delivery = $this->getDelivery($order);

			$info = new \stdClass();
			$info->name = $this->prepareItemName($delivery->getName());
			$info->price = sprintf('%.2f', $order->getDeliveryPrice());
			$info->quantity = '1';
			$info->vatTag = (int) $this->getVat($delivery)->getPayAnyWayId();
			$info->po = $this->getPaymentSubject($delivery)->getPayAnyWayId();
			$info->pm = $this->getPaymentMode($delivery)->getPayAnyWayId();
			return $info;
		}

		/**
		 * @inheritdoc
		 * @param \order $order
		 * @param \orderItem $orderItem
		 * @return \stdClass
		 *
		 * {
		 *      'name' => Название товара,
		 *      'price' => Цена за единицу товара,
		 *      'quantity' => Количество товара в заказе,
		 *      'vatTag' => id ставки НДС
		 * }
		 * @throws \publicException
		 * @throws \coreException
		 * @throws \privateException
		 */
		protected function getOrderItemInfo(\order $order, \orderItem $orderItem) {
			$info = new \stdClass();
			$info->name = $this->prepareItemName($orderItem->getName());
			$info->price = sprintf('%.2f', $this->getOrderItemPrice($order, $orderItem));
			$info->quantity = (string) $orderItem->getAmount();
			$info->vatTag = (int) $this->getVat($orderItem)->getPayAnyWayId();
			$info->po = $this->getPaymentSubject($orderItem)->getPayAnyWayId();
			$info->pm = $this->getPaymentMode($orderItem)->getPayAnyWayId();
			return $info;
		}

		/** @inheritdoc */
		protected function fixItemPriceSummary(\order $order, array $orderItemList) {
			$calculatedOrderPrice = 0;

			foreach ($orderItemList as $orderItemData) {
				$calculatedOrderPrice += $orderItemData->price;
			}

			$lastIndex = count($orderItemList) - 1;

			if ($order->getActualPrice() != $calculatedOrderPrice) {
				$priceDiff = $order->getActualPrice() - $calculatedOrderPrice;
				$orderItemList[$lastIndex]->price += $priceDiff;
			}

			return $orderItemList;
		}

		/** @inheritdoc */
		protected function prepareItemName($name) {
			return trim(preg_replace('/&?[a-z0-9]+;/i', '', htmlspecialchars($name)));
		}
	}
