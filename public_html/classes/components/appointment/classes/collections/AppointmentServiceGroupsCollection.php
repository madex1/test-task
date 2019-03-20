<?php

	/** Класс коллекции групп услуг для записи на прием */
	class AppointmentServiceGroupsCollection implements
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

		/** @var string $collectionItemClass класс элемента коллекции, с которым она работает */
		private $collectionItemClass = 'AppointmentServiceGroup';

		/** @var array конфигурация класса */
		private static $classConfig = [
			'service' => 'AppointmentServiceGroups',
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'used-in-creation' => false,
				],
				[
					'name' => 'NAME_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
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
			/** @var iUmiCollectionItem $item */

			$type = $serviceMap->get('ENTITY_TYPE_KEY');
			$count = $serviceMap->get('CHILDREN_COUNT_KEY');
			$group = $serviceMap->get('GROUP_ID_FIELD_NAME');
			$calculate = $serviceMap->get('CALCULATE_ONLY_KEY');

			foreach ($items as $key => $item) {
				$itemData = $item->export();
				$itemData[$type] = $itemClass;
				$itemData[$count] = $servicesCollection->count([
					$group => $item->getId(),
					$calculate => true,
				]);
				$result[] = $itemData;
			}

			return $result;
		}

		/**
		 * Возвращает группу по ее имени
		 * @param string $name имя группы
		 * @return iUmiCollectionItem|null
		 */
		public function getByName($name) {
			return $this->getBy($this->getMap()->get('NAME_FIELD_NAME'), $name);
		}
	}
