<?php

	use UmiCms\Service;

	/** Класс коллекции заявок для записи на прием */
	class AppointmentOrdersCollection implements
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
		private $collectionItemClass = 'AppointmentOrder';

		/** @var array конфигурация класса */
		private static $classConfig = [
			'service' => 'AppointmentOrders',
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'used-in-creation' => false
				],
				[
					'name' => 'SERVICE_ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE'
				],
				[
					'name' => 'EMPLOYEE_ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'ORDER_DATE_FIELD_NAME',
					'type' => 'DATE_FIELD_TYPE',
					'required' => true,
					'comparable' => true
				],
				[
					'name' => 'DATE_FIELD_NAME',
					'type' => 'DATE_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'TIME_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'required' => true,
				],
				[
					'name' => 'PHONE_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'append-field-value-callback' => 'convertEmptyStringToNull'
				],
				[
					'name' => 'EMAIL_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'append-field-value-callback' => 'convertEmptyStringToNull'
				],
				[
					'name' => 'NAME_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'append-field-value-callback' => 'convertEmptyStringToNull'
				],
				[
					'name' => 'COMMENT_FIELD_NAME',
					'type' => 'STRING_FIELD_TYPE',
					'append-field-value-callback' => 'convertEmptyStringToNull'
				],
				[
					'name' => 'STATUS_ID_FIELD_NAME',
					'type' => 'INTEGER_FIELD_TYPE',
					'required' => true,
				]
			],
			'create-validate-callback' => 'validateEmailAndPhoneEmptyBoth',
			'create-prepare-instancing-callback' => 'convertTimestampsToUmiDate'
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
		 * Обработчик метода tCommonCollection::appendFieldValue()#append-field-value-callback.
		 * Возвращает null, если экранированное значение строкового поля равно пустой строке,
		 * иначе вернет экранированное значение.
		 * @param string $fieldName имя поля
		 * @param string $fieldType тип поля
		 * @param mixed $fieldValue оригинальное значение поля
		 * @param mixed $escapedValue экранированное значение поля
		 * @return null|mixed
		 * @throws Exception
		 */
		public function convertEmptyStringToNull($fieldName, $fieldType, $fieldValue, $escapedValue) {
			if ($fieldType === $this->getMap()->get('STRING_FIELD_TYPE') && $escapedValue === '') {
				return null;
			}

			return $escapedValue;
		}

		/**
		 * Обработчик метода tCommonCollection::create()#create-validate-callback.
		 * Проверяет, что хотя бы одно из полей телефон и почтовый ящик имеет значение.
		 * В случае ошибки кидает исключение.
		 * @param array $fields имена полей
		 * @param array $values значения полей
		 * @param array $fieldsConfig настройки полей
		 * @return bool
		 * @throws Exception
		 */
		public function validateEmailAndPhoneEmptyBoth(array $fields, array $values, array $fieldsConfig) {
			$map = $this->getMap();

			if ($values[$map->get('EMAIL_FIELD_NAME')] === null && $values[$map->get('PHONE_FIELD_NAME')] === null) {
				throw new Exception('Phone and email cannot be empty both');
			}

			return true;
		}

		/**
		 * Обработчик метода tCommonCollection::create()#create-prepare-instancing-callback.
		 * Конвертирует timestamp в объекты umiDate в списке параметров для инициализации объекта
		 * класса элемента колллекции.
		 * @param array $fields имена полей
		 * @param array $values значения полей
		 * @param array $fieldsConfig настройки полей
		 * @return array
		 */
		public function convertTimestampsToUmiDate(array $fields, array $values, array $fieldsConfig) {
			$map = $this->getMap();

			foreach ($fieldsConfig as $fieldConfig) {
				$fieldType = $map->get($fieldConfig['type']);
				$fieldName = $map->get($fieldConfig['name']);

				if ($fieldType === $map->get('DATE_FIELD_TYPE') && isset($values[$fieldName])) {
					$values[$fieldName] = new umiDate($values[$fieldName]);
				}
			}

			return $values;
		}

		/** @inheritdoc */
		public function updateRelatedId(array $properties, $sourceId) {
			/** @var AppointmentEmployeesServicesCollection $employeesServicesCollection */
			$employeesServicesCollection = Service::get('AppointmentEmployeesServices');
			return $employeesServicesCollection->updateRelatedId($properties, $sourceId);
		}
	}
