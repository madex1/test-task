<?php

	namespace UmiCms\Classes\Components\Emarket\Payment\Subject;

	use UmiCms\Classes\Components\Emarket\Payment\iSubject;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\Facade as ReceiptParameterFacade;

	/**
	 * Класс фасада признака предмета расчета
	 * @package UmiCms\Classes\Components\Emarket\Payment\Subject
	 */
	class Facade extends ReceiptParameterFacade implements iFacade {

		/** @inheritdoc */
		protected function validateParameter($parameter) {
			if (!$parameter instanceof iSubject) {
				$this->throwNotFoundException();
			}
		}
	}
