<?php

	use UmiCms\Service;

	/** Класс коллекции услуг для записи на прием */
	class AppointmentServicesCollection implements
		iUmiCollection,
		iUmiDataBaseInjector,
		iUmiService,
		iUmiConstantMapInjector,
		iClassConfigManager {

		use tUmiDataBaseInjector;
		use tUmiService;
		use tCommonCollection;
		use tUmiConstantMapInjector;
		use tClassConfigManager;
		use UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder\Factory\Injector;

		/** @var string $collectionItemClass класс элемента коллекции, с которым она работает */
		private $collectionItemClass = 'AppointmentService';

		/** @var array конфигурация класса */
		private static $classConfig = [
			'service' => 'AppointmentServices',
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'used-in-creation' => false,
				],
				[
					'name' => 'GROUP_ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'NAME_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'TIME_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'PRICE_FIELD_NAME',
					'type' => 'FLOAT_FIELD_TYPE',
					'required' => true,
				]
			]
		];

		/** @inheritdoc */
		public function getCollectionItemClass() {
			return $this->collectionItemClass;
		}

		/** @inheritdoc */
		public function getTableName() {
			return $this->getMap()->get('TABLE_NAME');
		}

		/**
		 * Возвращает данные сущностей, соответствующих настройкам
		 * @param array $params настройки
		 * @return array
		 */
		public function export(array $params) {
			$items = $this->get($params);
			$result = [];

			if (umiCount($items) == 0) {
				return $result;
			}

			$itemClass = $this->getCollectionItemClass();
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentServicesCollection $servicesCollection */
			$servicesCollection = $serviceContainer->get('AppointmentServices');
			$serviceMap = $servicesCollection->getMap();
			$type = $serviceMap->get('ENTITY_TYPE_KEY');

			/** @var iUmiCollectionItem $item */
			foreach ($items as $key => $item) {
				$itemData = $item->export();
				$itemData[$type] = $itemClass;
				$result[] = $itemData;
			}

			return $result;
		}

		/** @inheritdoc */
		public function updateRelatedId(array $properties, $sourceId) {

			if (isset($properties['group_id'])) {
				$entityRelations = $this->getSourceIdBinderFactory()
					->create($sourceId);
				/** @var AppointmentServiceGroupsCollection $sericeGroupCollection */
				$sericeGroupCollection = Service::get('AppointmentServiceGroups');
				$table = $sericeGroupCollection->getMap()
					->get('EXCHANGE_RELATION_TABLE_NAME');

				$oldGroupId = $properties['group_id'];
				$newGroupId = $entityRelations->getInternalId($oldGroupId, $table);

				if ($newGroupId !== null) {
					$properties['group_id'] = $newGroupId;
				}
			}

			return $properties;
		}
	}
