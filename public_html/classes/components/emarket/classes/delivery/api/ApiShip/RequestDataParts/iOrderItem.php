<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts;

	/**
	 * Интерфейс части данных запроса с информацией о товарном наименовании заказа заказа
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts
	 */
	interface iOrderItem {

		/** @const string ID_KEY ключ данных части запроса с идентификатором */
		const ID_KEY = 'articul';

		/** @const string DESCRIPTION_KEY ключ данных части запроса с названием */
		const DESCRIPTION_KEY = 'description';

		/** @const string QUANTITY_KEY ключ данных части запроса с количеством */
		const QUANTITY_KEY = 'quantity';

		/** @const string WEIGHT_KEY ключ данных части запроса с весом */
		const WEIGHT_KEY = 'weight';

		/** @const string COST_KEY ключ данных части запроса с суммой наложенного платежа */
		const COST_KEY = 'cost';

		/** @const string ASSESSED_COST_KEY ключ данных части запроса со стоимостью */
		const ASSESSED_COST_KEY = 'assessedCost';

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
		 * @return iOrderItem
		 */
		public function import(array $data);

		/**
		 * Возвращает данные запроса
		 * @return array
		 */
		public function export();

		/**
		 * Устанавливает идентификатор
		 * @param int $id идентификатор
		 * @return iOrderItem
		 */
		public function setId($id);

		/**
		 * Возвращает идентификатор
		 * @return int
		 */
		public function getId();

		/**
		 * Устанавливает название
		 * @param string $name название
		 * @return iOrderItem
		 */
		public function setName($name);

		/**
		 * Возаращает название
		 * @return string
		 */
		public function getName();

		/**
		 * Устанавливает количество
		 * @param int $quantity количество
		 * @return iOrderItem
		 */
		public function setQuantity($quantity);

		/**
		 * Возвращает количество
		 * @return int
		 */
		public function getQuantity();

		/**
		 * Устанавливает вес
		 * @param float $weight вес
		 * @return iOrderItem
		 */
		public function setWeight($weight);

		/**
		 * Возвращает вес
		 * @return float
		 */
		public function getWeight();

		/**
		 * Устанавливает сумму наложенного платежа
		 * @param float $codCost сумма наложенного платежа
		 * @return iOrderItem
		 */
		public function setCodCost($codCost);

		/**
		 * Возвращает сумму наложенного платежа
		 * @return float
		 */
		public function getCodCost();

		/**
		 * Устанавливает оченочную стоимость
		 * @param float $cost оченочная стоимость
		 * @return iOrderItem
		 */
		public function setAssessedCost($cost);

		/**
		 * Возвращает оченочную стоимость
		 * @return float
		 */
		public function getAssessedCost();
	}
