<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts;

	/**
	 * Интерфейс провайдера (службы доставки, сд)
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip
	 */
	interface iProvider {

		/** @const string VALUE_KEY ключ данных со значением свойства */
		const VALUE_KEY = 'value';

		/** @const string IS_COD_ALLOWED_KEY ключ данных со значением свойства $isCODAllowed */
		const IS_COD_ALLOWED_KEY = 'isCodAllowed';

		/** @const string IS_COD_ALLOWED_KEY_TITLE наименование свойства $isCODAllowed */
		const IS_COD_ALLOWED_KEY_TITLE = 'Поддержка наложенного платежа';

		/** @const string DESCRIPTION_KEY ключ данных со описанием/наименованием свойства */
		const DESCRIPTION_KEY = 'description';

		/** @const string TYPE_KEY ключ данных с типом данных значения свойства */
		const TYPE_KEY = 'type';

		/** @const string REQUIRED_KEY ключ данных с обязательностью содержания значения свойства */
		const REQUIRED_KEY = 'required';

		/** @const string BOOL_TYPE_KEY обозначение типа данных "boolean" */
		const BOOL_TYPE_KEY = 'boolean';

		/** @const string STRING_TYPE_KEY обозначение типа данных "string" */
		const STRING_TYPE_KEY = 'string';

		/** @const string IS_CONNECTED_KEY ключ данных с флагом, который указывается на то, что провайдер был подключен */
		const IS_CONNECTED_KEY = 'is_connected';

		/** @var string I18N_PATH группа используемых языковый меток */
		const I18N_PATH = 'emarket';

		/**
		 * Устанавливает настройки провайдера
		 * @param array $data настройки
		 * @return iProvider;
		 */
		public function import(array $data);

		/**
		 * Возвращает настройки провайдера в виде массива
		 * @return array
		 */
		public function export();

		/**
		 * Возвращает данные для запроса на подключение провайдера
		 * @return array
		 */
		public function getConnectRequestData();

		/**
		 * Возвращает строковой идентификатор провайдера
		 * @return string
		 */
		public function getKey();

		/**
		 * Возвращает список идентификатор поддерживаемых типов доставки
		 * @return array
		 */
		public function getAllowedDeliveryTypes();

		/**
		 * Возвращает список идентификатор поддерживаемых типов отгрузки
		 * @return array
		 */
		public function getAllowedPickupTypes();

		/**
		 * Проверяет поддерживается ли работа с наложенными платежами
		 * @return bool результат проверки
		 */
		public function isCODAllowed();

		/**
		 * Устанавливает поддерживается ли работа с наложенными платежами
		 * @param bool $status поддерживается/не поддерживается
		 * @return $this
		 */
		public function setCODAllowedStatus($status);

		/**
		 * Проверяет был ли подключен провайдер
		 * @return bool результат проверки
		 */
		public function isConnected();

		/**
		 * Устанавливает флаг, что провайдер был подключен
		 * @param bool $flag значение флага
		 * @return $this
		 */
		public function setIsConnectedFlag($flag);

		/**
		 * Проверяет необходимо ли указывать пункт приема товара, если используется отгрузки "от пункта выдачи"
		 * @return bool результат проверки
		 */
		public function isPointIdRequiredForPickupFromPoint();

		/**
		 * Проверяет поддерживается ли доставка в заданный город
		 * @param RequestDataParts\iCity $city город
		 * @return bool результат проверки
		 */
		public function isRecipientCitySupported(RequestDataParts\iCity $city);

		/**
		 * Проверяет должны ли ширина, высота и длина быть заполнены у заказа
		 * @return bool результат проверки
		 */
		public function areOrderDimensionsRequired();

		/**
		 * Проверяет должна ли быть заполнена дата доставки
		 * @return bool результат проверки
		 */
		public function isDeliveryDateRequired();

		/**
		 * Проверяет должна ли быть заполнена дата отгрузки
		 * @return bool результат проверки
		 */
		public function isPickupDateRequired();

		/**
		 * Проверяет должен ли быть заполнен вес у товаров
		 * @return bool результат проверки
		 */
		public function isOrderItemWeightRequired();

		/**
		 * Проверяет должен ли быть заполнен интервал времени доставки заказа клиенту
		 * @return bool результат проверки
		 */
		public function isDeliveryTimeIntervalRequired();
	}
