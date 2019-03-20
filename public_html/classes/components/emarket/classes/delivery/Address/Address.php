<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\Address;

	use UmiCms\Classes\System\Entities\Country;

	/**
	 * Класс адреса доставки, замещает объект соответствующего типа
	 * @package UmiCms\Classes\Components\Emarket\Delivery\Address
	 */
	class Address extends \umiObjectProxy implements iAddress {

		/** @var Country\iCountry|null $country страна */
		private $country;

		/** @const string DELIVERY_ADDRESS_TYPE_GUID гуид типа данных объекта-источника данных */
		const DELIVERY_ADDRESS_TYPE_GUID = 'emarket-deliveryaddress';

		/** @const string COUNTRY_FIELD имя поля страны */
		const COUNTRY_FIELD = 'country';

		/** @const string POST_INDEX_FIELD имя поля почтовый индекс */
		const POST_INDEX_FIELD = 'index';

		/** @const string REGION_FIELD имя поля региона */
		const REGION_FIELD = 'region';

		/** @const string CITY_FIELD имя поля города */
		const CITY_FIELD = 'city';

		/** @const string STREET_FIELD имя поля улицы */
		const STREET_FIELD = 'street';

		/** @const string HOUSE_FIELD имя поля номера дома */
		const HOUSE_NUMBER_FIELD = 'house';

		/** @const string HOUSE_FIELD имя поля номера квартиры */
		const FLAT_NUMBER_FIELD = 'flat';

		/** @const string COMMENT_FIELD имя поля комментария */
		const COMMENT_FIELD = 'order_comments';

		/** @const string DEFAULT_COUNTRY_VALUE значение страны по умолчанию */
		const DEFAULT_COUNTRY_VALUE = '';

		/** @const string DEFAULT_CITY_VALUE значение города по умолчанию */
		const DEFAULT_CITY_VALUE = '';

		/** @const string DEFAULT_COUNTRY_ISO_CODE_VALUE значение ISO кода страны по умолчанию */
		const DEFAULT_COUNTRY_ISO_CODE_VALUE = '';

		/** @inheritdoc */
		public function __construct(\iUmiObject $object) {
			parent::__construct($object);
			$this->validateObjectTypeGUID(self::DELIVERY_ADDRESS_TYPE_GUID);
			$this->initCountry();
		}

		/** @inheritdoc */
		public function getCountry() {
			if (!$this->country instanceof Country\iCountry) {
				return self::DEFAULT_COUNTRY_VALUE;
			}

			return (string) $this->country->getName();
		}

		/** @inheritdoc */
		public function getCountryISOCode() {
			if (!$this->country instanceof Country\iCountry) {
				return self::DEFAULT_COUNTRY_ISO_CODE_VALUE;
			}

			return (string) $this->country->getISOCode();
		}

		/** @inheritdoc */
		public function getCity() {
			return (string) $this->getObject()
				->getValue(self::CITY_FIELD);
		}

		/** @inheritdoc */
		public function getPostIndex() {
			return (int) $this->getObject()
				->getValue(self::POST_INDEX_FIELD);
		}

		/** @inheritdoc */
		public function getRegion() {
			return (string) $this->getObject()
				->getValue(self::REGION_FIELD);
		}

		/** @inheritdoc */
		public function getStreet() {
			return (string) $this->getObject()
				->getValue(self::STREET_FIELD);
		}

		/** @inheritdoc */
		public function getHouseNumber() {
			return (string) $this->getObject()
				->getValue(self::HOUSE_NUMBER_FIELD);
		}

		/** @inheritdoc */
		public function getFlatNumber() {
			return (string) $this->getObject()
				->getValue(self::FLAT_NUMBER_FIELD);
		}

		/** @inheritdoc */
		public function getComment() {
			return (string) $this->getObject()
				->getValue(self::COMMENT_FIELD);
		}

		/**
		 * Возвращает идентификатор страны если задан, иначе - 0.
		 * @return int
		 */
		private function getCountryId() {
			return (int) $this->getObject()
				->getValue(self::COUNTRY_FIELD);
		}

		/** Инициализирует данные страны */
		private function initCountry() {
			$countryId = $this->getCountryId();

			try {
				$country = Country\CountriesFactory::createByObjectId($countryId);
			} catch (\expectObjectException $e) {
				$country = null;
			}

			$this->setCountry($country);
		}

		/**
		 * Устанавливает страну
		 * @param Country\iCountry $country страна
		 * @return $this
		 */
		private function setCountry(Country\iCountry $country = null) {
			$this->country = $country;
			return $this;
		}
	}
