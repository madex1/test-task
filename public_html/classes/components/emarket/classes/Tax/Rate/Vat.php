<?php

	namespace UmiCms\Classes\Components\Emarket\Tax\Rate;

	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter;

	/**
	 * @inheritdoc
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate
	 */
	class Vat extends Parameter implements iVat {

		/** @inheritdoc */
		public function getRate() {
			return $this->dataObject->getValue('tax');
		}

		/** @deprecated  */
		public function getPayOnline() {
			return $this->getPayOnlineId();
		}
	}
