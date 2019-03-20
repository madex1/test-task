<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer\Receipt;

	use UmiCms\Classes\Components\Emarket\Serializer\Receipt;

	/**
	 * Класс сериализатора для чека по ФЗ-54 для api Яндекс.Касса версии 4
	 * @link https://kassa.yandex.ru/docs/checkout-api/#sozdanie-platezha
	 * @package UmiCms\Classes\Components\Emarket\Serializer
	 */
	class YandexKassa4 extends Receipt {

		/**
		 * Возвращает данные для печати чека
		 * @param \order $order заказ
		 * @return array
		 *
		 * [
		 *      'receipt' => [
		 *          'items' => $this->getOrderItemInfoList(),
		 *          'email' => $this->getContact()
		 *      ]
		 * ]
		 * @throws \publicException
		 */
		public function getReceipt(\order $order) {
			return [
				'receipt' => array_merge(
					$this->getOrderItemInfoList($order),
					$this->getContact($order)
				)
			];
		}

		/**
		 * @inheritdoc
		 * @param \order $order
		 * @return array
		 *
		 * [
		 *       'email' => Почтовый ящик покупателя
		 * ]
		 *
		 * @throws \publicException
		 */
		public function getContact(\order $order) {
			return [
				'email' => parent::getContact($order)
			];
		}

		/**
		 * Формирует данные стоимости
		 * @param float $price стоимость
		 * @return array
		 *
		 * [
		 *      'amount' => [
		 *          'value' => Цена
		 *          'currency' => Код валюты в формате ISO
		 *      ]
		 * ]
		 */
		public function getPrice($price) {
			return [
				'amount' => [
					'value' => sprintf('%.2f', $price),
					'currency' => $this->getCurrencyCode()
				]
			];
		}

		/**
		 * Возвращает данные подтверждения платежа
		 * @return array
		 *
		 * [
		 *      'confirmation' => [
		 *          'type' => 'redirect',
		 *          'return_url' => 'Адрес, куда можно отправить пользователя'
		 *      ]
		 * ]
		 * @throws \coreException
		 */
		public function getConfirmation() {
			return [
				'confirmation' => [
					'type' => 'redirect',
					'return_url' => $this->getReturnUrl(),
				]
			];
		}

		/**
		 * Возвращает адрес, куда должен перейти пользователь
		 * @return string
		 * @throws \coreException
		 */
		protected function getReturnUrl() {
			return sprintf('%s/emarket/purchase/result/successful/', $this->getDomain());
		}

		/**
		 * @inheritdoc
		 * @param \order $order
		 * @return array
		 *
		 * [
		 *      'description' => Название товара,
		 *      'quantity' => Количество товара,
		 *      'vat_code' => id ставки НДС,
		 *      'amount' => $this->getPrice()
		 * ]
		 *
		 * @throws \expectObjectException
		 * @throws \Exception
		 */
		protected function getDeliveryInfo(\order $order) {
			$delivery = $this->getDelivery($order);

			return [
					'description' => $this->prepareItemName($delivery->getName()),
					'quantity' => sprintf('%.3f', 1),
					'vat_code' => (int) $this->getVat($delivery)->getYandexKassaId(),
					'payment_subject' => $this->getPaymentSubject($delivery)->getYandexKassaId(),
					'payment_mode' => $this->getPaymentMode($delivery)->getYandexKassaId()
				] + $this->getPrice($order->getDeliveryPrice());
		}

		/**
		 * @inheritdoc
		 * @param \order $order
		 * @param \orderItem $orderItem
		 * @return array
		 *
		 * [
		 *      'description' => Название товара,
		 *      'quantity' => Количество товара,
		 *      'vat_code' => id ставки НДС,
		 *      'amount' => $this->getPrice()
		 * ]
		 * @throws \Exception
		 */
		protected function getOrderItemInfo(\order $order, \orderItem $orderItem) {
			return [
					'description' => $this->prepareItemName($orderItem->getName()),
					'quantity' => sprintf('%.3f', $orderItem->getAmount()),
					'vat_code' => (int) $this->getVat($orderItem)->getYandexKassaId(),
					'payment_subject' => $this->getPaymentSubject($orderItem)->getYandexKassaId(),
					'payment_mode' => $this->getPaymentMode($orderItem)->getYandexKassaId()
				] + $this->getPrice($this->getOrderItemPrice($order, $orderItem));
		}

		/** @inheritdoc */
		protected function fixItemPriceSummary(\order $order, array $orderItemList) {
			$calculatedOrderPrice = 0;

			foreach ($orderItemList as $orderItemData) {
				$calculatedOrderPrice += $orderItemData['amount']['value'];
			}

			$lastIndex = count($orderItemList) - 1;

			if ($order->getActualPrice() != $calculatedOrderPrice) {
				$priceDiff = $order->getActualPrice() - $calculatedOrderPrice;
				$orderItemList[$lastIndex]['amount']['value'] += $priceDiff;
			}

			return [
				'items' => $orderItemList
			];
		}
	}
