<?php

	namespace UmiCms\Classes\Components\Emarket\Tax\Rate;

	/**
	 * Интерфейс калькулятора суммы налога от цены
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate
	 */
	interface iCalculator {

		/**
		 * Возвращает сумму налога от цены
		 * @param int|float $price цена
		 * @param int $rateBase налоговая база
		 * @param int $rate налоговая ставка
		 * @return int|float
		 */
		public function calculate($price, $rateBase, $rate);

	}