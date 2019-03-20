<?php

	namespace UmiCms\Classes\Components\Emarket\Payment\Mode;

	use UmiCms\Classes\Components\Emarket\Payment\iMode;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\Facade as ReceiptParameterFacade;

	/**
	 * Класс фасада признака способа расчета
	 * @package UmiCms\Classes\Components\Emarket\Payment\Mode
	 */
	class Facade extends ReceiptParameterFacade implements iFacade {

		/** @inheritdoc */
		protected function validateParameter($parameter) {
			if (!$parameter instanceof iMode) {
				$this->throwNotFoundException();
			}
		}
	}
