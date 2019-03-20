<?php

	namespace UmiCms\Classes\Components\Emarket\Tax\Rate\Parser;

	use UmiCms\Classes\Components\Emarket\Tax\Rate\iParser;

	/**
	 * Интерфейс фабрики парсера ставки ндс
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate\Parser
	 */
	interface iFactory {

		/**
		 * Создает парсер ставки ндс
		 * @param int|string $rate ставка ндс
		 * @return iParser
		 */
		public function create($rate);

	}