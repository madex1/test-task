<?php

	use UmiCms\Service;

	/** Класс коллекции дневных расписаний сотрудников для записи на прием */
	class AppointmentEmployeesSchedulesCollection implements
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
		private $collectionItemClass = 'AppointmentEmployeeSchedule';

		/** @var array конфигурация класса */
		private static $classConfig = [
			'service' => 'AppointmentEmployeesSchedules',
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
					'name' => 'DAY_NUMBER_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'TIME_START_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'TIME_END_FIELD_NAME',
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

		/** @inheritdoc */
		public function updateRelatedId(array $properties, $sourceId) {

			if (isset($properties['employee_id'])) {
				$entityRelations = $this->getSourceIdBinderFactory()
					->create($sourceId);
				/** @var AppointmentEmployeesCollection $employeesCollection */
				$employeesCollection = Service::get('AppointmentEmployees');
				$table = $employeesCollection->getMap()
					->get('EXCHANGE_RELATION_TABLE_NAME');

				$oldEmployeeId = $properties['employee_id'];
				$newEmployeeId = $entityRelations->getInternalId($oldEmployeeId, $table);

				if ($newEmployeeId !== null) {
					$properties['employee_id'] = $newEmployeeId;
				}
			}

			return $properties;
		}
	}
