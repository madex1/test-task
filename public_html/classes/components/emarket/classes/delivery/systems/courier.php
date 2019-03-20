<?php

	/**
	 * Способ доставки типа "Курьером".
	 * Подходит ко всем заказам.
	 * Стоимость доставки берет из объекта-источника.
	 * Доставка может быть бесплатной, если стоимость заказа превысила значение соответствующей настройки объекта-источника.
	 */
	class courierDelivery extends delivery {

		/** @inheritdoc */
		public function validate(order $order) {
			return true;
		}

		/** @inheritdoc */
		public function getDeliveryPrice(order $order) {
			$deliveryPrice = $this->getValue('price');
			$minOrderPrice = $this->getValue('order_min_price');

			if ($minOrderPrice === null) {
				return $deliveryPrice;
			}

			$orderPrice = $order->getActualPrice() - $order->getDeliveryPrice();
			return ($orderPrice < $minOrderPrice) ? $deliveryPrice : 0;
		}
	}
