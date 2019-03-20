<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts;

	/**
	 * Интерфейс части данных запроса с информацией о городе
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts
	 */
	interface iCity {

		/** @const string NAME_KEY ключ данных части запроса с названием города */
		const NAME_KEY = 'city';

		/** @var string I18N_PATH группа используемых языковый меток */
		const I18N_PATH = 'emarket';

		/**
		 * Конструктор
		 * @param array $data данные части запроса
		 */
		public function __construct(array $data);

		/**
		 * Устанавливает данные части запроса
		 * @param array $data данные части запроса
		 * @return iCity
		 */
		public function import(array $data);

		/**
		 * Возвращает данные запроса
		 * @return array
		 */
		public function export();

		/**
		 * Возвращает название города
		 * @return string
		 */
		public function getName();

		/**
		 * Устанавливает название города
		 * @param string $name название города
		 * @return iCity
		 */
		public function setName($name);
	}
