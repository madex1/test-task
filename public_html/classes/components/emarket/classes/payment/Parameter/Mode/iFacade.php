<?php

	namespace UmiCms\Classes\Components\Emarket\Payment\Mode;

	use UmiCms\Classes\Components\Emarket\Payment\iMode;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\iFacade as iReceiptParameterFacade;

	/**
	 * Интерфейс фасада признака способа расчета
	 * @package UmiCms\Classes\Components\Emarket\Payment\Mode
	 */
	interface iFacade extends iReceiptParameterFacade {

		/**
		 * Возвращает признак способа расчета
		 * @param int $id идентификатор способа расчета
		 * @return iMode
		 * @throws \privateException
		 */
		public function get($id);
	}
