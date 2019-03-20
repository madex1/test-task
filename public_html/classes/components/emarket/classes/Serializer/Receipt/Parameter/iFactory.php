<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter;

	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\iParameter;

	/**
	 * Интерфейс абстрактной фабрики параметра чека платежной системы
	 * @package UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter
	 */
	interface iFactory {

		/**
		 * Создает параметр чека
		 * @param \iUmiObject $object объект данных параметра чека
		 * @return iParameter
		 */
		public function create(\iUmiObject $object);

		/**
		 * Возвращает guid типа данных
		 * @return string
		 */
		public function getTypeGuid();
	}