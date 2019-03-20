<?php

	/**
	 * Класс правила скидки типа "Сумма заказа".
	 * Подходит для скидок на заказ.
	 *
	 * Содержит 2 настройки:
	 *
	 * 1) Минимальная подходящая стоимость заказа;
	 * 2) Максимальная подходящая стоимость заказа;
	 *
	 * Значения настроек хранятся в объекте-источнике данных для правила скидки.
	 */
	class orderPriceDiscountRule extends discountRule implements orderDiscountRule {

		/** @inheritdoc */
		public function validateOrder(order $order) {
			$orderPrice = $order->getOriginalPrice();
			$minimum = $this->getValue('minimum');
			$maximum = $this->getValue('maximum');

			if ($minimum && ($orderPrice < $minimum)) {
				return false;
			}

			if ($maximum && ($orderPrice > $maximum)) {
				return false;
			}

			return true;
		}
	}
