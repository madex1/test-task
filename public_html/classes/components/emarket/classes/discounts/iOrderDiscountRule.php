<?php

	/** Интерфейс правила скидки на заказ */
	interface orderDiscountRule {

		/**
		 * Удовлетворяет ли заказ правилу скидки
		 * @param order $order заказ
		 * @return bool
		 */
		public function validateOrder(order $order);
	}
