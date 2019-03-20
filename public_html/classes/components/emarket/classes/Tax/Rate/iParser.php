<?php

	namespace UmiCms\Classes\Components\Emarket\Tax\Rate;

	/**
	 * Интерфейса парсера ставки налога
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate\Calculator
	 */
	interface iParser {

		/**
		 * Конструктор
		 * @param int|string $rate
		 */
		public function __construct($rate);

		/**
		 * Возвращает налоговую базу для формулы расчета налога
		 * @return int
		 */
		public function getRateBase();

		/**
		 * Возвращает налоговую ставку для формулы расчета налога
		 * @return int
		 */
		public function getRate();

		/**
		 * Определяет является ли ставка нулевой
		 * @return bool
		 */
		public function isZeroRate();

		/**
		 * Определяет является ли ставка расчетной
		 * @return bool
		 */
		public function isEstimatedRate();
	}