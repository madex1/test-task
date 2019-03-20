<?php

	/**
	 * Класс правила скидки типа "Товары заказа".
	 * Подходит для скидок на товар.
	 *
	 * Содержит 1 настройку:
	 *
	 * 1) Список подходящих товаров (объектов каталога) и разделов каталога, товары которых подходят;
	 *
	 * Значение настройки хранится в объекте-источнике данных для правила скидки.
	 */
	class itemsDiscountRule extends discountRule implements itemDiscountRule {

		/** @inheritdoc */
		public function validateItem(iUmiHierarchyElement $orderItem) {
			/** @var iUmiHierarchyElement $orderItem */
			$catalog = $this->getValue('catalog_items');

			if (!is_array($catalog)) {
				return false;
			}

			/** @var iUmiHierarchyElement $catalogItem */
			foreach ($catalog as $catalogItem) {
				if ($catalogItem->getId() == $orderItem->getId()) {
					return true;
				}
			}

			$parentId = $orderItem->getParentId();

			if (!$parentId) {
				return false;
			}

			$hierarchy = umiHierarchy::getInstance();
			$parents = $hierarchy->getAllParents($parentId, true);

			if (isset($parents[0])) {
				unset($parents[0]);
			}

			foreach ($catalog as $catalogItem) {
				if (in_array($catalogItem->getId(), $parents)) {
					return true;
				}
			}

			return false;
		}
	}

