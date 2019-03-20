<?php
	namespace UmiCms\Classes\Components\Emarket\Orders\Items;

	/**
	 * Интерфейс фильтра списка товарных наименований
	 * @package UmiCms\Classes\Components\Emarket\Orders\Items
	 */
	interface iFilter {

		/**
		 * Возвращает список товарных наименований с заданным идентификатором товара
		 * @param \orderItem[] $orderItemList список товарных наименований
		 * @param int $productId идентификатор товара
		 * @return \orderItem[]
		 */
		public function getListByProduct(array $orderItemList, $productId);

		/**
		 * Возвращает первое товарное наименование с заданным идентификатором товара
		 * @param \orderItem[] $orderItemList список товарных наименований
		 * @param int $productId идентификатор товара
		 * @return \orderItem|null
		 */
		public function getFirstByProduct(array $orderItemList, $productId);

		/**
		 * Возвращает список товарных наименований с несуществующим товаром
		 * @param \orderItem[] $orderItemList список товарных наименований
		 * @return \orderItem[]
		 */
		public function getListWithoutProduct(array $orderItemList);

		/**
		 * Возвращает первое товарное наименование с несуществующим товаром
		 * @param \orderItem[] $orderItemList список товарных наименований
		 * @return \orderItem|null
		 */
		public function getFirstWithoutProduct(array $orderItemList);

		/**
		 * Возвращает список товарных наименований с заданным идентификатором торгового предложения
		 * @param \orderItem[] $orderItemList список товарных наименований
		 * @param int $tradeOfferId идентификатор торгового предложения
		 * @return \orderItem[]
		 */
		public function getListByTradeOffer(array $orderItemList, $tradeOfferId);

		/**
		 * Возвращает первое товарное наименование с заданным идентификатором торгового предложения
		 * @param \orderItem[] $orderItemList список товарных наименований
		 * @param int $tradeOfferId идентификатор торгового предложения
		 * @return \orderItem|null
		 */
		public function getFirstByTradeOffer(array $orderItemList, $tradeOfferId);

		/**
		 * Возвращает список товарных наименований с заданным набором опций
		 * @param \orderItem[] $orderItemList список товарных наименований
		 * @param array $optionList набор опций
		 * @return \orderItem[]
		 */
		public function getListByOptions(array $orderItemList, array $optionList);

		/**
		 * Возвращает первое товарное наименование с заданным набором опций
		 * @param \orderItem[] $orderItemList список товарных наименований
		 * @param array $optionList набор опций
		 * @return \orderItem|null
		 */
		public function getFirstByOptions(array $orderItemList, array $optionList);

		/**
		 * Возвращает список товарных наименований без модификаторов
		 * @param \orderItem[] $orderItemList
		 * @return \orderItem[]
		 */
		public function getListByEmptyModifier(array $orderItemList);

		/**
		 * Возвращает первое товарное наименование без модификаторов
		 * @param \orderItem[] $orderItemList
		 * @return \orderItem|null
		 */
		public function getFirstByEmptyModifier(array $orderItemList);
	}