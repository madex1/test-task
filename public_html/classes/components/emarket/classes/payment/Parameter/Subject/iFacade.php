<?php

	namespace UmiCms\Classes\Components\Emarket\Payment\Subject;

	use UmiCms\Classes\Components\Emarket\Payment\iSubject;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\iFacade as iReceiptParameterFacade;

	/**
	 * Интерфейс фасада признака предмета расчета
	 * @package UmiCms\Classes\Components\Emarket\Payment\Subject
	 */
	interface iFacade extends iReceiptParameterFacade {

		/**
		 * Возвращает признак предмета расчета
		 * @param int $id идентификатор признака предмета расчета
		 * @return iSubject
		 */
		public function get($id);
	}
