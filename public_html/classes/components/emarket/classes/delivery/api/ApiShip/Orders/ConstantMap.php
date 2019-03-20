<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders;

	/**
	 * Карта констант заказов ApiShip
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders
	 */
	class ConstantMap extends \baseUmiCollectionConstantMap {

		/** @const string TABLE_NAME имя таблицы, где хранятся сотрудники */
		const TABLE_NAME = 'cms3_apiship_orders';

		/** @const string EXCHANGE_RELATION_TABLE_NAME имя таблицы со связями импорта */
		const EXCHANGE_RELATION_TABLE_NAME = 'cms3_import_apiship_orders';

		/** @const string NUMBER_FIELD_NAME название столбца для номера сущности */
		const NUMBER_FIELD_NAME = 'number';

		/** @const string UMI_ORDER_REF_NUMBER_FIELD_NAME название столбца для номера связанного заказа в UMI.CMS */
		const UMI_ORDER_REF_NUMBER_FIELD_NAME = 'umi_order_ref_number';

		/**
		 * @const string PROVIDER_ORDER_REF_NUMBER_FIELD_NAME название столбца для номера связанного заказа в службы
		 *   доставки
		 */
		const PROVIDER_ORDER_REF_NUMBER_FIELD_NAME = 'provider_order_ref_number';
	}
