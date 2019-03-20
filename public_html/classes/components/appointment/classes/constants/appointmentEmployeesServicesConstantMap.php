<?php

	/** Карта констант коллекции связей между сотрудниками и услугами записи на прием */
	class appointmentEmployeesServicesConstantMap extends baseUmiCollectionConstantMap {

		/** @const string TABLE_NAME имя таблицы, где хранятся связи сотрудник-услуга */
		const TABLE_NAME = 'cms3_appointment_employees_services';

		/** @const string EXCHANGE_RELATION_TABLE_NAME имя таблицы со связями импорта */
		const EXCHANGE_RELATION_TABLE_NAME = 'cms3_import_appointment_employees_services';
	}
