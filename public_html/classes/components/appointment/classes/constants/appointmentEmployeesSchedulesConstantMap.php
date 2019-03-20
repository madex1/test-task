<?php

	/** Карта констант коллекции расписаний сотрудников записи на прием */
	class appointmentEmployeesSchedulesConstantMap extends baseUmiCollectionConstantMap {

		/** @const string TABLE_NAME имя таблицы, где хранятся расписания */
		const TABLE_NAME = 'cms3_appointment_employee_schedule';

		/** @const string EXCHANGE_RELATION_TABLE_NAME имя таблицы со связями импорта */
		const EXCHANGE_RELATION_TABLE_NAME = 'cms3_import_appointment_employee_schedule';

		/** @const string DAY_NUMBER_FIELD_NAME название столбца для номера для недели */
		const DAY_NUMBER_FIELD_NAME = 'day';
	}

