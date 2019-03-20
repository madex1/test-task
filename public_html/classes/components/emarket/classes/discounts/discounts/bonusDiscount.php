<?php

	/**
	 * Класс скидок типа "Накопительная скидка".
	 * Данный тип скидок не уменьшает стоимость чего-либо, сумма скидки начисляется покупателям в качестве бонусов.
	 * Бонусами потом можно полностью или частично расплатиться за заказы.
	 * @see EmarketPurchasingStagesSteps::payByBonus(), EmarketPurchasingStagesSteps::renderBonusPayment()
	 */
	class bonusDiscount extends discount {

		/**
		 * Проверяет, требуется ли при оплате заказа начислять бонусы покупателю
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
		 * Возвращает самую выгодную для покупателя скидку на заказ с типом "Накопительная скидка"
		 * @param order $order заказ
		 * @return float|int|null
		 * @throws coreException
		 */
		public static function search(order $order) {
			$cmsController = cmsController::getInstance();
			/** @var emarket $emarket */
			$emarket = $cmsController->getModule('emarket');

			if (!$emarket instanceof def_module) {
				throw new coreException('Emarket module must be installed in order to calculate discounts');
			}

			$allDiscounts = $emarket->getAllDiscounts('bonus');
			$discounts = [];

			/** @var iUmiObject $discountObject */
			foreach ($allDiscounts as $discountObject) {
				$discount = discount::get($discountObject->getId());
				if (!$discount instanceof bonusDiscount) {
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

		/**
		 * Начисляет покупателю заказа бонусы за заказ
		 * @param int $orderId идентификатор заказа
		 * @return bool
		 */
		public static function addBonus($orderId) {
			$order = order::get($orderId);
			$discount = bonusDiscount::search($order);

			if (!$discount instanceof bonusDiscount) {
				return false;
			}

			$price = $order->getActualPrice();
			$bonus = $price - $discount->recalcPrice($price);
			$customerId = $order->getCustomerId();
			$customer = umiObjectsCollection::getInstance()->getObject($customerId);
			$customer->bonus = $customer->bonus + $bonus;
			$customer->commit();

			return true;
		}

		/**
		 * Убирает у покупателя заказа бонусы за заказ
		 * @param int $orderId идентификатор заказа
		 */
		public static function returnBonus($orderId) {
			$order = order::get($orderId);
			$customerId = $order->getCustomerId();
			$customer = umiObjectsCollection::getInstance()->getObject($customerId);
			$orderBonus = $order->getBonusDiscount();
			$customer->setValue('bonus', $customer->getValue('bonus') + $orderBonus);
			$customer->setValue('spent_bonus', $customer->getValue('spent_bonus') - $orderBonus);
			$customer->commit();
			$order->setBonusDiscount(0);
			$order->refresh();
		}
	}
