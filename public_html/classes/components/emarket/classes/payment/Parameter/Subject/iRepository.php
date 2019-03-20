<?php

	namespace UmiCms\Classes\Components\Emarket\Payment\Subject;

	use UmiCms\Classes\Components\Emarket\Payment\iSubject;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter\iRepository as iReceiptParameterRepository;

	/**
	 * Интерфейс репозитория признака предмета расчета.
	 * @package UmiCms\Classes\Components\Emarket\Payment\Subject
	 */
	interface iRepository extends iReceiptParameterRepository {

		/**
		 * Загружает признак предмета расчета из репозитория
		 * @param int $id идентификатор признака предмета расчета
		 * @return iSubject|null
		 */
		public function load($id);
	}
