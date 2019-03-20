<?php

	/** Карта констант коллекции заявок записи на прием */
	class appointmentOrdersConstantMap extends baseUmiCollectionConstantMap {

		/** @const string TABLE_NAME имя таблицы, где хранятся заявки */
		const TABLE_NAME = 'cms3_appointment_orders';

		/** @const string EXCHANGE_RELATION_TABLE_NAME имя таблицы со связями импорта */
		const EXCHANGE_RELATION_TABLE_NAME = 'cms3_import_appointment_orders';

		/** @const string DATE_FIELD_NAME название столбца для даты оформления заявки */
		const ORDER_DATE_FIELD_NAME = 'create_date';

		/** @const int ORDER_STATUS_NOT_CONFIRMED код статуса "Не подтвержден" */
		const ORDER_STATUS_NOT_CONFIRMED = 1;

		/** @const int ORDER_STATUS_CONFIRMED код статуса "Подтвержден" */
		const ORDER_STATUS_CONFIRMED = 2;

		/** @const int ORDER_STATUS_DECLINED код статуса "Отклонен" */
		const ORDER_STATUS_DECLINED = 3;
	}

