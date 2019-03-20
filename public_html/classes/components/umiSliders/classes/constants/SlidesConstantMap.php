<?php

	namespace UmiCms\Classes\Components\UmiSliders;

	/**
	 * Class SlidesConstantMap карта констант слайдов
	 * @package UmiCms\Classes\Components\UmiSliders
	 */
	class SlidesConstantMap extends \baseUmiCollectionConstantMap {

		/** @const string TABLE_NAME название таблицы, где хранятся слайды */
		const TABLE_NAME = 'cms3_slides';

		/** @const string EXCHANGE_RELATION_TABLE_NAME имя таблицы со связями импорта */
		const EXCHANGE_RELATION_TABLE_NAME = 'cms3_import_slides';

		/**
		 * @const string SLIDER_ID_FIELD_NAME название столбца таблицы для идентификатора слайдера, которому принадлежит
		 * слайд
		 */
		const SLIDER_ID_FIELD_NAME = 'slider_id';

		/**
		 * @const string OPEN_IN_NEW_TAB_FIELD_NAME название столбца таблицы для опции открывать ссылку в отдельной
		 * вкладке
		 */
		const OPEN_IN_NEW_TAB_FIELD_NAME = 'open_in_new_tab';
	}
