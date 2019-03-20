<?php

	/** Класс модификатора цены скидки "Процентная скидка" */
	class procDiscountModificator extends discountModificator {

		/** @inheritdoc */
		public function recalcPrice($originalPrice) {
			return $originalPrice - ($originalPrice * $this->getValue('proc') / 100);
		}
	}
