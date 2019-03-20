<?php

	/**
	 * Класс скидок типа "Скидка на товары каталога".
	 * Скидки данного класса модифицируют стоимость товарных позиций в заказе.
	 */
	class itemDiscount extends discount {

		/**
		 * Подходит ли скидка к товару (объекту каталога)
		 * @param iUmiHierarchyElement $element товар
		 * @return bool
		 */
		public function validate(iUmiHierarchyElement $element) {
			$rules = $this->getDiscountRules();

			$validateCount = 0;
			/** @var itemDiscountRule $rule */
			foreach ($rules as $rule) {
				if (!$rule instanceof itemDiscountRule) {
					continue;
				}

				if (!$rule->validateItem($element)) {
					return false;
				}

				$validateCount++;
			}

			return $validateCount > 0;
		}

		/**
		 * Возвращает самую выгодную для покупателя скидку на товар с типом "Скидка на товары каталога"
		 * @param iUmiHierarchyElement $element товар (объект каталога)
		 * @return float|int|null
		 * @throws privateException
		 */
		final public static function search(iUmiHierarchyElement $element) {
			$cmsController = cmsController::getInstance();
			/** @var emarket $emarket */
			$emarket = $cmsController->getModule('emarket');

			if (!$emarket instanceof def_module) {
				throw new privateException('Emarket module must be installed in order to calculate discounts');
			}

			$allDiscounts = $emarket->getAllDiscounts('item');
			$discounts = [];

			/** @var iUmiObject $discountObject */
			foreach ($allDiscounts as $discountObject) {
				$discount = discount::get($discountObject->getId());

				if (!$discount instanceof itemDiscount) {
					continue;
				}

				if ($discount->validate($element)) {
					$discounts[] = $discount;
				}
			}

			if (umiCount($discounts) == 0) {
				return null;
			}

			$elementPrice = $element->getValue('price');
			$maxDiscount = null;
			$minPrice = null;

			/** @var discount $discount */
			foreach ($discounts as $i => $discount) {
				$price = $discount->recalcPrice($elementPrice);

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
