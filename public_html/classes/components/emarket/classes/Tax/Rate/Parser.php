<?php

	namespace UmiCms\Classes\Components\Emarket\Tax\Rate;

	/**
	 * Класс парсера ставки ндс
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate\Calculator
	 */
	class Parser implements iParser {

		/** @var int RATE_BASE_PERCENT процент полной базы ндс */
		const RATE_BASE_PERCENT = 100;

		/** @var string|int $rate ставка налога */
		private $rate;

		/** @inheritdoc */
		public function __construct($rate) {
			$this->rate = $rate;
		}

		/** @inheritdoc */
		public function getRateBase() {
			if ($this->isZeroRate()) {
				return 0;
			} else if ($this->isEstimatedRate()) {
				return $this->getEstimatedRateBase();
			}

			return (int) $this->rate + self::RATE_BASE_PERCENT;
		}

		/** @inheritdoc */
		public function getRate()	{
			if ($this->isZeroRate()) {
				return 0;
			} else if ($this->isEstimatedRate()) {
				return $this->getEstimatedRate();
			}

			return $this->rate;
		}

		/** @inheritdoc */
		public function isZeroRate() {
			return (bool) ($this->rate == 'none') || ($this->rate == 0);
		}

		/** @inheritdoc */
		public function isEstimatedRate() {
			return (bool) preg_match('|\d\/\d|', $this->rate);
		}

		/**
		 * Возвращает налоговую ставку расчетной ставки
		 * Например: у ставки 10/110 налоговая база будет 10
		 * @return int
		 */
		private function getEstimatedRate() {
			return $this->parseEstimatedRate()[0];
		}

		/**
		 * Возвращает налоговую базу расчетной ставки
		 * Например: у ставки 10/110 налоговая база будет 110
		 * @return int
		 */
		private function getEstimatedRateBase() {
			return $this->parseEstimatedRate()[1];
		}

		/**
		 * Парсит значение расчетной ставки
		 * @return int[]
		 */
		private function parseEstimatedRate() {
			preg_match_all('|[\d]{2,3}|', $this->rate, $matches);
			return $matches[0];
		}

	}