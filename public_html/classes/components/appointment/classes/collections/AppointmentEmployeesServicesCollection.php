<?php

	use UmiCms\Service;

	/** Класс коллекции связей сотрудников с услугами для записи на прием */
	class AppointmentEmployeesServicesCollection implements
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
		private $collectionItemClass = 'AppointmentEmployeeService';

		/** @var array конфигурация класса */
		private static $classConfig = [
			'service' => 'AppointmentEmployeesServices',
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'used-in-creation' => false
				],
				[
					'name' => 'EMPLOYEE_ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'SERVICE_ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => true,
				],
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

		/** @inheritdoc */
		public function updateRelatedId(array $properties, $sourceId) {

			/** @var AppointmentEmployeesSchedulesCollection $employeesSchedulesCollection */
			$employeesSchedulesCollection = Service::get('AppointmentEmployeesSchedules');
			$properties = $employeesSchedulesCollection->updateRelatedId($properties, $sourceId);

			if (isset($properties['service_id'])) {
				$entityRelations = $this->getSourceIdBinderFactory()
					->create($sourceId);
				/** @var AppointmentServicesCollection $serviceCollection */
				$serviceCollection = Service::get('AppointmentServices');
				$table = $serviceCollection->getMap()
					->get('EXCHANGE_RELATION_TABLE_NAME');

				$oldServiceId = $properties['service_id'];
				$newServiceId = $entityRelations->getInternalId($oldServiceId, $table);

				if ($newServiceId !== null) {
					$properties['service_id'] = $newServiceId;
				}
			}

			return $properties;
		}
	}
