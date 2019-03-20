<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	/**
	 * Коллекция заказов ApiShip
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders
	 */
	class Collection implements
		iCollection,
		\iUmiDataBaseInjector,
		\iUmiService,
		\iUmiConstantMapInjector,
		\iClassConfigManager {

		use \tUmiDataBaseInjector;
		use \tUmiService;
		use \tCommonCollection;
		use \tUmiConstantMapInjector;
		use \tClassConfigManager;

		/** @var string $collectionItemClass класс элемента коллекции, с которым она работает */
		private $collectionItemClass = 'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders\Entity';

		/** @var array конфигурация класса */
		private static $classConfig = [
			'service' => 'ApiShipOrders',
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'used-in-creation' => false
				],
				[
					'name' => 'NUMBER_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => true
				],
				[
					'name' => 'UMI_ORDER_REF_NUMBER_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => true
				],
				[
					'name' => 'PROVIDER_ORDER_REF_NUMBER_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE'
				],
				[
					'name' => 'STATUS_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true
				]
			],
			'create-prepare-instancing-callback' => 'convertStatusIdToEnum',
			'get-prepare-instancing-callback' => 'convertStatusIdToEnum'
		];

		/** @inheritdoc */
		public function createOrder($orderNumber, $umiOrderRefNumber) {
			$constants = $this->getMap();
			return $this->create([
				$constants->get('NUMBER_FIELD_NAME') => $orderNumber,
				$constants->get('UMI_ORDER_REF_NUMBER_FIELD_NAME') => $umiOrderRefNumber,
				$constants->get('STATUS_FIELD_NAME') => new ApiShip\Enums\OrderStatuses()
			]);
		}

		/** @inheritdoc */
		public function getByUmiOrderRefNumber($umiOrderRefNumber) {
			return $this->getBy($this->getMap()->get('UMI_ORDER_REF_NUMBER_FIELD_NAME'), $umiOrderRefNumber);
		}

		/** @inheritdoc */
		public function getOrdersByIds($ordersIds) {
			return $this->get([
				$this->getMap()->get('ID_FIELD_NAME') => $ordersIds
			]);
		}

		/** @inheritdoc */
		public function getCollectionItemClass() {
			return $this->collectionItemClass;
		}

		/** @inheritdoc */
		public function getTableName() {
			return $this->getMap()->get('TABLE_NAME');
		}

		/**
		 * Обработчик методов:
		 *
		 * tCommonCollection::create()#create-prepare-instancing-callback
		 * tCommonCollection::create()#get-prepare-instancing-callback
		 *
		 * Конвертирует строковое значение статуса заказа в экземпляр класса ApiShip\Enums\OrderStatuses.
		 * @param array $fields имена полей
		 * @param array $values значения полей
		 * @param array $fieldsConfig настройки полей
		 * @return array
		 */
		public function convertStatusIdToEnum(array $fields, array $values, array $fieldsConfig) {
			$statusFieldName = $this->getMap()
				->get('STATUS_FIELD_NAME');

			if (isset($values[$statusFieldName])) {
				$values[$statusFieldName] = new ApiShip\Enums\OrderStatuses($values[$statusFieldName]);
			}

			return $values;
		}
	}
