<?php

	namespace UmiCms\Classes\Components\Emarket\Tax\Rate;

	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\iParameter;

	/**
	 * @inheritdoc
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate
	 */
	interface iVat extends iParameter {

		/** @const string TYPE_GUID гуид типа */
		const TYPE_GUID = 'tax-rate-guide';

		/**
		 * Возвращает ставку налога
		 * @return string
		 */
		public function getRate();
	}
