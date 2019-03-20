<?php

	namespace UmiCms\Classes\Components\Emarket\Tax\Rate\Parser;

	use UmiCms\Classes\Components\Emarket\Tax\Rate\Parser;

	/**
	 * Класс фабрики парсера ставки ндс
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate\Parser
	 */
	class Factory implements iFactory {

		/** @inheritdoc */
		public function create($rate) {
			return new Parser($rate);
		}
	}