<?php

	/** Интерфейс правила скидки на товар */
	interface itemDiscountRule {

		/**
		 * Удовлетворяет ли товар правилу скидки
		 * @param iUmiHierarchyElement $element товар
		 * @return bool
		 */
		public function validateItem(iUmiHierarchyElement $element);
	}
