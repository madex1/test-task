<?php

	/** Класс модификатора цены скидки "Абсолютная скидка" */
	class absoluteDiscountModificator extends discountModificator {

		/** @inheritdoc */
		public function recalcPrice($originalPrice) {
			return $originalPrice - $this->getValue('size');
		}
	}
