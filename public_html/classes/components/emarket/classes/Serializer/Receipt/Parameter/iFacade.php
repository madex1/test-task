<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter;

	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\iParameter;

	/**
	 * Интерфейс абстрактного фасада параметра чека платежной системы
	 * @package UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter
	 */
	interface iFacade {

		/**
		 * Конструктор
		 * @param iRepository $repository репозиторий способа расчета
		 */
		public function __construct(iRepository $repository);

		/**
		 * Возвращает параметр чека
		 * @param int $id идентификатор параметра
		 * @return iParameter
		 */
		public function get($id);
	}