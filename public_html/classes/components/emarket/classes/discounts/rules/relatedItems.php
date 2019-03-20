<?php

	/**
	 * Класс правила скидки типа "Связанные товары".
	 * Подходит для скидок на товар.
	 *
	 * Содержит 1 настройку:
	 *
	 * 1) Список товаров, совместное нахождение в корзине с проверяемым товаров которых, дает скидку проверяемому товару
	 *
	 * Значение настройки хранится в объекте-источнике данных для правила скидки.
	 */
	class relatedItemsDiscountRule extends discountRule implements itemDiscountRule {

		/** @inheritdoc */
		public function validateItem(iUmiHierarchyElement $element) {
			/** @var iUmiHierarchyElement $element */
			$relatedItems = [];

			/** @var iUmiHierarchyElement $item */
			foreach ($this->related_items as $item) {
				if (!$item instanceof iUmiHierarchyElement) {
					continue;
				}

				$relatedItems[] = $item->getId();
			}

			if (!in_array($element->getId(), $relatedItems)) {
				return false;
			}

			/** @var emarket $emarket */
			$emarket = cmsController::getInstance()->getModule('emarket');

			if (!$emarket instanceof def_module) {
				throw new privateException('Emarket module must be installed');
			}

			$order = $emarket->getBasketOrder();

			$relatedItemsFound = 0;

			/** @var orderItem $orderItem */
			foreach ($order->getItems() as $orderItem) {
				/** @var iUmiHierarchyElement $item */
				$item = $orderItem->getItemElement();

				if (!$item instanceof iUmiHierarchyElement) {
					continue;
				}

				if (in_array($item->getId(), $relatedItems)) {
					$relatedItemsFound++;
				}
			}

			return umiCount($relatedItems) == $relatedItemsFound;
		}
	}

