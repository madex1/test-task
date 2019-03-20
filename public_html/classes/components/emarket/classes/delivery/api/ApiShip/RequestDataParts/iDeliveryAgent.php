<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts;

	/**
	 * Интерфейс части данных запроса с информацией об агенте (отправителя/получателя) заказа
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts
	 */
	interface iDeliveryAgent {

		/** @const string COUNTRY_CODE_KEY ключ данных части запроса с кодом страны */
		const COUNTRY_CODE_KEY = 'countryCode';

		/** @const string REGION_KEY ключ данных части запроса с регионом */
		const REGION_KEY = 'region';

		/** @const string CITY_KEY ключ данных части запроса с городом */
		const CITY_KEY = 'city';

		/** @const string STREET_KEY ключ данных части запроса с улицей */
		const STREET_KEY = 'street';

		/** @const string HOUSE_KEY ключ данных части запроса с домом */
		const HOUSE_KEY = 'house';

		/** @const string OFFICE_KEY ключ данных части запроса с номером квартиры */
		const OFFICE_KEY = 'office';

		/** @const string CONTACT_NAME_KEY ключ данных части запроса с ФИО контактного лица */
		const CONTACT_NAME_KEY = 'contactName';

		/** @const string PHONE_KEY ключ данных части запроса с телефоном контактного лица */
		const PHONE_KEY = 'phone';

		/** @const string EMAIL_KEY ключ данных части запроса с почтовым ящиком контактного лица */
		const EMAIL_KEY = 'email';

		/** @const int CITY_MAX_LENGTH максимальная длина названия города */
		const CITY_MAX_LENGTH = 50;

		/** @const int CITY_MAX_LENGTH максимальная длина улицы */
		const STREET_MAX_LENGTH = 50;

		/** @const int PHONE_MAX_LENGTH максимальная длина номера телефона */
		const PHONE_MAX_LENGTH = 15;

		/** @var string I18N_PATH группа используемых языковый меток */
		const I18N_PATH = 'emarket';

		/**
		 * Конструктор
		 * @param array $data данные части запроса
		 * @param string $agentName имя агента
		 */
		public function __construct(array $data, $agentName);

		/**
		 * Устанавливает данные части запроса
		 * @param array $data
		 * @return iDeliveryAgent
		 */
		public function import(array $data);

		/**
		 * Возвращает данные запроса
		 * @return array
		 */
		public function export();

		/**
		 * Устанавливает код страны
		 * @param string $countyCode код страны
		 * @return iDeliveryAgent
		 */
		public function setCountryCode($countyCode);

		/**
		 * Возвращает код страны
		 * @return string
		 */
		public function getCountryCode();

		/**
		 * Устанавливает регион
		 * @param string $region регион
		 * @return iDeliveryAgent
		 */
		public function setRegion($region);

		/**
		 * Возвращает регион
		 * @return string
		 */
		public function getRegion();

		/**
		 * Устанавливает город
		 * @param string $city город
		 * @return iDeliveryAgent
		 */
		public function setCity($city);

		/**
		 * Возвращает город
		 * @return string
		 */
		public function getCity();

		/**
		 * Устанавливает улицу
		 * @param string $street улица
		 * @return iDeliveryAgent
		 */
		public function setStreet($street);

		/**
		 * Возвращает улицу
		 * @return string
		 */
		public function getStreet();

		/**
		 * Устанавливает номер дома
		 * @param string $house номер дома
		 * @return iDeliveryAgent
		 */
		public function setHouse($house);

		/**
		 * Возвращает номер дома
		 * @return string
		 */
		public function getHouse();

		/**
		 * Устанавливает номер квартиры
		 * @param string $office номер квартиры
		 * @return iDeliveryAgent
		 */
		public function setOffice($office);

		/**
		 * Возвращает номер квартиры
		 * @return string
		 */
		public function getOffice();

		/**
		 * Устанавливает ФИО контактного лица
		 * @param string $name ФИО контактного лица
		 * @return iDeliveryAgent
		 */
		public function setContactName($name);

		/**
		 * Возвращает ФИО контактного лица
		 * @return string
		 */
		public function getContactName();

		/**
		 * Устанавливает телефон контактного лица
		 * @param string $phone телефон контактного лица
		 * @return iDeliveryAgent
		 */
		public function setPhone($phone);

		/**
		 * Возвращает телефон контактного лица
		 * @return string
		 */
		public function getPhone();

		/**
		 * Устанавливает почтовый ящик контакного лица
		 * @param string $email почтовый ящик контакного лица
		 * @return iDeliveryAgent
		 */
		public function setEmail($email);

		/**
		 * Возвращает почтовый ящик контакного лица
		 * @return string
		 */
		public function getEmail();
	}
