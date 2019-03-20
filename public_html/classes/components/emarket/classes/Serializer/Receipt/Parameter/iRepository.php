<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter;

	use UmiCms\System\Selector\iFactory as SelectorFactory;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\iParameter;

	/**
	 * Интерфейс абстрактного репозитория параметра чека платежной системы
	 * @package UmiCms\Classes\Components\Emarket\Serializer\Receipt\Parameter
	 */
	interface iRepository {

		/**
		 * Конструктор
		 * @param iFactory $parameterFactory фабрика параметров
		 * @param SelectorFactory $selectorFactory фабрика селекторов
		 */
		public function __construct(iFactory $parameterFactory, SelectorFactory $selectorFactory);

		/**
		 * Загружает параметр из репозитория
		 * @param int $id идентификатор параметра чека
		 * @return iParameter|null
		 */
		public function load($id);


		/**
		 * Загружает параметр из репозитория по его guid
		 * @param string $guid строковый идентификатор параметра чека
		 * @return iParameter|null
		 */
		public function loadByGuid($guid);
	}