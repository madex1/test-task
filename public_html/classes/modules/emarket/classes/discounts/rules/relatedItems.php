<?php
	class relatedItemsDiscountRule extends discountRule implements itemDiscountRule {
		/**
		 * Проверяет, начислять ли скидку на заданный элемент
		 *
		 * Проверяет все ли связанные товары находятся в корзине.
		 * Если товары найдены в корзине, и переданный товар относится
		 * к списку связанных товар скидки, то возвращает true.
		 *
		 * @param iUmiHierarchyElement $element Элемент
		 *
		 * @return bool true если начислять
		 * @throws privateException если модуль emarket не найден
		 */
		public function validateItem(iUmiHierarchyElement $element) {
			/** @var umiHierarchyElement $element */
			// составляем список идентификаторов связанных товаров для скидки
			$relatedItems = array();
			foreach($this->related_items as $item) {
				if($item instanceof iUmiHierarchyElement) {
					/** @var umiHierarchyElement $item */
					$relatedItems[] = $item->getId();
				}
			}

			// Распростроняется ли скидка на данный товар?
			if (!in_array($element->getId(), $relatedItems)) {
				return false;
			}

			// Проверяем, все ли товары из списка присутствуют в корзине
			$emarket = cmsController::getInstance()->getModule('emarket');
			if($emarket instanceof def_module) {
				/** @var emarket|__emarket_purchasing $emarket модуль "Интернет магазин" */
				$order = $emarket->getBasketOrder();
				$relatedItemsFound = 0;
				foreach($order->getItems() as $orderItem) {
					/** @var orderItem $orderItem Элемент корзины */
					$item = $orderItem->getItemElement();
					if($item instanceof iUmiHierarchyElement) {
						/** @var umiHierarchyElement $item */
						if(in_array($item->getId(), $relatedItems)) { // считаем кол-во
							$relatedItemsFound++;
						}
					}
				}

				return count($relatedItems) == $relatedItemsFound;
			} else {
				throw new privateException('Emarket module must be installed');
			}
		}
	};
?>