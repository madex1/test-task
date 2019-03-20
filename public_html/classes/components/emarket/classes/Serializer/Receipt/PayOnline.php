<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer\Receipt;

	use UmiCms\Classes\Components\Emarket\Serializer\Receipt;

	/**
	 * Класс сериализатора для чека по ФЗ-54 для api PayOnline
	 * @link https://payonline.ru/rel/doc/merchant/servis_onlajn-fiskalizacii_internet-platezhej.pdf
	 * @package UmiCms\Classes\Components\Emarket\Serializer
	 */
	class PayOnline extends Receipt {

		/** @const int MAX_ORDER_ITEM_NAME_LENGTH максимальная длина названия товара (0 - первый символ) */
		const MAX_ORDER_ITEM_NAME_LENGTH = 127;

		/** @const string DEFAULT_PROVIDER тип платежной системы по умолчанию */
		const DEFAULT_PROVIDER = 'Card';

		/**
		 * Возвращает данные для печати чека
		 * @param \order $order заказ
		 * @param string|null $provider тип платежной системы
		 * @return \stdClass
		 *
		 * {
		 *      'operation' => Тип операции (чека),
		 *      'transactionId' => Идентификатор транзакции,
		 *      'paymentSystemType' => Тип платежной системы,
		 *      'totalAmount' => Итоговая сумма чека в рублях,
		 *      'goods' => [
		 * @see PayOnline::getOrderItemInfo()
		 *      ],
		 *      'email' => Почтовый ящик клиента,
		 *      'typeOfProcessing' => Название процессинга
		 * }
		 * @throws \publicException
		 */
		public function getReceipt(\order $order, $provider = null) {
			$receipt = new \stdClass();
			$receipt->operation = 'Benefit';
			$receipt->transactionId = $order->getPaymentDocumentNumber();
			$receipt->paymentSystemType = $provider ?: self::DEFAULT_PROVIDER;
			$receipt->totalAmount = sprintf('%.2f', $order->getActualPrice());
			$receipt->goods = $this->getOrderItemInfoList($order);
			$receipt->email = $this->getContact($order);
			$receipt->typeOfProcessing = 'PayOnline';
			return $receipt;
		}

		/**
		 * @inheritdoc
		 * @param \order $order
		 * @return \stdClass
		 *
		 * {
		 *      'description' => Название товара,
		 *      'quantity' => Количество товара в заказе,
		 *      'amount' => Цена за единицу товара,
		 *      'tax' => id ставки НДС
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
			$info->description = $this->prepareItemName($delivery->getName());
			$info->quantity = '1';
			$info->amount = sprintf('%.2f', $order->getDeliveryPrice());
			$info->tax = $this->getVat($delivery)->getPayOnlineId();
			$info->paymentSubjectType = $this->getPaymentSubject($delivery)->getPayOnlineId();
			$info->paymentMethodType = $this->getPaymentMode($delivery)->getPayOnlineId();
			return $info;
		}

		/**
		 * @inheritdoc
		 * @param \order $order
		 * @param \orderItem $orderItem
		 * @return \stdClass
		 *
		 * {
		 *      'description' => Название товара,
		 *      'quantity' => Количество товара в заказе,
		 *      'amount' => Цена за единицу товара,
		 *      'tax' => id ставки НДС
		 * }
		 * @throws \publicException
		 * @throws \coreException
		 * @throws \privateException
		 */
		protected function getOrderItemInfo(\order $order, \orderItem $orderItem) {
			$info = new \stdClass();
			$info->description = $this->prepareItemName($orderItem->getName());
			$info->quantity = (int) $orderItem->getAmount();
			$info->amount = sprintf('%.2f', $this->getOrderItemPrice($order, $orderItem));
			$info->tax = $this->getVat($orderItem)->getPayOnlineId();
			$info->paymentSubjectType = $this->getPaymentSubject($orderItem)->getPayOnlineId();
			$info->paymentMethodType = $this->getPaymentMode($orderItem)->getPayOnlineId();
			return $info;
		}

		/** @inheritdoc */
		protected function fixItemPriceSummary(\order $order, array $orderItemList) {
			$calculatedOrderPrice = 0;

			foreach ($orderItemList as $orderItemData) {
				$calculatedOrderPrice += $orderItemData->amount;
			}

			$lastIndex = count($orderItemList) - 1;

			if ($order->getActualPrice() != $calculatedOrderPrice) {
				$priceDiff = $order->getActualPrice() - $calculatedOrderPrice;
				$orderItemList[$lastIndex]->amount += $priceDiff;
			}

			return $orderItemList;
		}

		/** @inheritdoc */
		protected function prepareItemName($name) {
			$name = parent::prepareItemName($name);
			return mb_substr($name, 0, self::MAX_ORDER_ITEM_NAME_LENGTH);
		}
	}
