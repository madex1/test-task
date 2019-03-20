<?php

	namespace UmiCms\Classes\Components\UmiSliders;

	/**
	 * Interface iSlidesCollection интерфейс коллекции слайдов
	 * @package UmiCms\Classes\Components\UmiSliders
	 */
	interface iSlidesCollection extends \iUmiCollection {

		/**
		 * Возвращает максимальный порядок вывода слайдов
		 * @return int
		 */
		public function getMaximumSlidesOrder();
	}
