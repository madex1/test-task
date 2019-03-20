<?php

	/**
	 * Класс скидок типа "Скидка на заказ".
	 * Скидки данного класса модифицируют стоимость заказа.
	 */
	class orderDiscount extends discount {

		/**
		 * Подходит ли скидка к заказу
		 * @param order $order заказ
		 * @return bool
		 */
		public function validate(order $order) {
			$rules = $this->getDiscountRules();

			$validateCount = 0;
			/** @var orderDiscountRule $rule */
			foreach ($rules as $rule) {
				if (!$rule instanceof orderDiscountRule) {
					continue;
				}

				if (!$rule->validateOrder($order)) {
					return false;
				}

				$validateCount++;
			}

			return $validateCount > 0;
		}

		/**
		 * Возвращает самую выгодную для покупателя скидку на заказ с типом "Скидка на заказ"
		 * @param order $order
		 * @return null
		 * @throws coreException
		 */
		public static function search(order $order) {
			$cmsController = cmsController::getInstance();
			/** @var emarket $emarket */
			$emarket = $cmsController->getModule('emarket');

			if (!$emarket instanceof def_module) {
				throw new coreException('Emarket module must be installed in order to calculate discounts');
			}

			$allDiscounts = $emarket->getAllDiscounts('order');
			$discounts = [];

			/** @var iUmiObject $discountObject */
			foreach ($allDiscounts as $discountObject) {
				$discount = discount::get($discountObject->getId());

				if (!$discount instanceof orderDiscount) {
					continue;
				}

				if ($discount->validate($order)) {
					$discounts[] = $discount;
				}
			}

			if (umiCount($discounts) == 0) {
				return null;
			}

			$orderPrice = $order->getOriginalPrice();
			$maxDiscount = null;
			$minPrice = null;

			/** @var discount $discount */
			foreach ($discounts as $i => $discount) {
				$price = $discount->recalcPrice($orderPrice);

				if ($price <= 0) {
					continue;
				}

				if ($minPrice === null || $minPrice > $price) {
					$minPrice = $price;
					$maxDiscount = $discount;
				}
			}

			return $maxDiscount;
		}
	}
