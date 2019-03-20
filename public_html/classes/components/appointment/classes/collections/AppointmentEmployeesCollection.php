<?php

	/** Класс коллекции сотрудников записи на прием */
	class AppointmentEmployeesCollection implements
		iUmiCollection,
		iUmiDataBaseInjector,
		iUmiService,
		iUmiConstantMapInjector,
		iClassConfigManager,
		iUmiImageFileInjector {

		use tUmiDataBaseInjector;
		use tUmiService;
		use tCommonCollection;
		use tUmiConstantMapInjector;
		use tClassConfigManager;
		use tUmiImageFileInjector;

		/** @var string $collectionItemClass класс элемента коллекции, с которым она работает */
		private $collectionItemClass = 'AppointmentEmployee';

		/** @var array конфигурация класса */
		private static $classConfig = [
			'service' => 'AppointmentEmployees',
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'used-in-creation' => false
				],
				[
					'name' => 'NAME_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'PHOTO_FIELD_NAME',
					'type' => 'IMAGE_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'DESCRIPTION_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				],
			],
			'create-prepare-instancing-callback' => 'convertFilePathToUmiImage',
			'create-after-callback' => 'setImageFileHandlerToEntity',
			'get-after-callback' => 'setImageFileHandlerToEntity',
		];

		/** @inheritdoc */
		public function getCollectionItemClass() {
			return $this->collectionItemClass;
		}

		/** @inheritdoc */
		public function getTableName() {
			return $this->getMap()->get('TABLE_NAME');
		}
	}

