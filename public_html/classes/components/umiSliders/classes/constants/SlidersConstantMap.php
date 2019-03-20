<?php

	namespace UmiCms\Classes\Components\UmiSliders;

	/**
	 * Class SlidersConstantMap карта констант слайдеров
	 * @package UmiCms\Classes\Components\UmiSliders
	 */
	class SlidersConstantMap extends \baseUmiCollectionConstantMap {

		/** @const string TABLE_NAME название таблицы, где хранятся слайдеры */
		const TABLE_NAME = 'cms3_sliders';

		/** @const string EXCHANGE_RELATION_TABLE_NAME имя таблицы со связями импорта */
		const EXCHANGE_RELATION_TABLE_NAME = 'cms3_import_sliders';

		/** @const string SLIDING_SPEED_FIELD_NAME название столбца таблицы для скорости прокрути слайдера */
		const SLIDING_SPEED_FIELD_NAME = 'sliding_speed';

		/** @const string SLIDING_DELAY_FIELD_NAME название столбца таблицы для задержки перед прокруткой слайдера */
		const SLIDING_DELAY_FIELD_NAME = 'sliding_delay';

		/**
		 * @const string SLIDING_LOOP_ENABLE_FIELD_NAME название столбца таблицы для статуса включенности цикличности
		 * прокрутки слайдера
		 */
		const SLIDING_LOOP_ENABLE_FIELD_NAME = 'sliding_loop_enable';

		/**
		 * @const string SLIDING_AUTO_PLAY_ENABLE_FIELD_NAME название столбца таблицы для статуса включенности
		 * автоматической прокрутки слайдера
		 */
		const SLIDING_AUTO_PLAY_ENABLE_FIELD_NAME = 'sliding_auto_play_enable';

		/**
		 * @const string SLIDES_RANDOM_ORDER_ENABLE_FIELD_NAME название столбца таблицы для статуса включенности
		 * случайного порядка вывода слайдов в слайдере
		 */
		const SLIDES_RANDOM_ORDER_ENABLE_FIELD_NAME = 'sliders_random_order_enable';

		/** @const string SLIDES_COUNT_FIELD_NAME название столбца таблицы для количества отображаемых слайдов в слайдере */
		const SLIDES_COUNT_FIELD_NAME = 'slides_count';

		/** @const string CUSTOM_ID_FIELD_NAME название столбца таблицы для кастомного идентификатор слайдера */
		const CUSTOM_ID_FIELD_NAME = 'custom_id';
	}
