<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts;

	/**
	 * Интерфейс данных запроса вычисление вариантов стоимости доставки
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData
	 */
	interface iCalculateDeliveryCost {

		/** @const string FROM_CITY_KEY ключ данных запроса с городом отправления */
		const FROM_CITY_KEY = 'from';

		/** @const string TO_CITY_KEY ключ данных запроса с городом доставки */
		const TO_CITY_KEY = 'to';

		/** @const string WEIGHT_KEY ключ данных запроса с весом заказа */
		const WEIGHT_KEY = 'weight';

		/** @const string WIDTH_KEY ключ данных запроса с шириной заказа */
		const WIDTH_KEY = 'width';

		/** @const string HEIGHT_KEY ключ данных запроса с высотой заказа */
		const HEIGHT_KEY = 'height';

		/** @const string LENGTH_KEY ключ данных запроса с длиной заказа */
		const LENGTH_KEY = 'length';

		/** @const string ASSESSED_COST_KEY ключ данных запроса с оценочной стоимостью заказа */
		const ASSESSED_COST_KEY = 'assessedCost';

		/** @const string PICKUP_TYPES_KEY ключ данных запроса с поддерживаемыми типами отгрузки */
		const PICKUP_TYPES_KEY = 'pickupTypes';

		/** @const string DELIVERY_TYPES_KEY ключ данных запроса с поддерживаемыми типами доставки */
		const DELIVERY_TYPES_KEY = 'deliveryTypes';

		/** @const string PROVIDERS_KEY ключ данных запроса с идентификаторами поддерживаемых провайдеров */
		const PROVIDERS_KEY = 'providerKeys';

		/** @const string TIMEOUT_KEY ключ данных запроса с временем ожидания ответа сервиса ApiShip */
		const TIMEOUT_KEY = 'timeout';

		/** @const int DEFAULT_TIMEOUT время ожидания ответа сервиса ApiShip по умолчанию */
		const DEFAULT_TIMEOUT = 2000;

		/**
		 * Конструктор
		 * @param array $data данные запроса
		 */
		public function __construct(array $data);

		/**
		 * Устанавливает данные запроса
		 * @param array $data данные запроса
		 * @return iCalculateDeliveryCost
		 */
		public function import(array $data);

		/**
		 * Возвращает данные запроса
		 * @return array
		 */
		public function export();

		/**
		 * Устанавливает город отправления
		 * @param RequestDataParts\iCity $city город
		 * @return iCalculateDeliveryCost
		 */
		public function setCityFrom(RequestDataParts\iCity $city);

		/**
		 * Возвращает город отправления
		 * @return RequestDataParts\iCity
		 */
		public function getCityFrom();

		/**
		 * Устанавливает город отправки
		 * @param RequestDataParts\iCity $city город
		 * @return iCalculateDeliveryCost
		 */
		public function setCityTo(RequestDataParts\iCity $city);

		/**
		 * Возвращает город доставки
		 * @return RequestDataParts\iCity
		 */
		public function getCityTo();

		/**
		 * Устанавливает вес заказа
		 * @param float $weight вес
		 * @return iCalculateDeliveryCost
		 */
		public function setWeight($weight);

		/**
		 * Возвращает вес заказа
		 * @return float
		 */
		public function getWeight();

		/**
		 * Устанавливает ширину заказа
		 * @param float $width ширина
		 * @return iCalculateDeliveryCost
		 */
		public function setWidth($width);

		/**
		 * Возвращает ширину заказа
		 * @return float
		 */
		public function getWidth();

		/**
		 * Устанавливает высоту заказа
		 * @param float $height высота
		 * @return iCalculateDeliveryCost
		 */
		public function setHeight($height);

		/**
		 * Возвращает высоту заказа
		 * @return float
		 */
		public function getHeight();

		/**
		 * Устанавливает длина заказа
		 * @param float $length длина
		 * @return iCalculateDeliveryCost
		 */
		public function setLength($length);

		/**
		 * Возвращает длину заказа
		 * @return float
		 */
		public function getLength();

		/**
		 * Устанавливает оценочную стоимость заказа
		 * @param float $cost оценочная стоимость заказа
		 * @return iCalculateDeliveryCost
		 */
		public function setAssessedCost($cost);

		/**
		 * Возвращает оценочную стоимость заказа
		 * @return float
		 */
		public function getAssessedCost();

		/**
		 * Устанавливает поддерживаемые типы отгрузки
		 * @param array $typesIds типы отгрузки
		 * @return iCalculateDeliveryCost
		 */
		public function setPickupTypes(array $typesIds);

		/**
		 * Возвращает поддерживаемые типы отгрузки
		 * @return array
		 */
		public function getPickupTypes();

		/**
		 * Устанавливает поддерживаемые типы доставки
		 * @param array $typesIds типы доставки
		 * @return iCalculateDeliveryCost
		 */
		public function setDeliveryTypes(array $typesIds);

		/**
		 * Возвращает поддерживаемые типы доставки
		 * @return array
		 */
		public function getDeliveryTypes();

		/**
		 * Устанавливает идентификаторы поддерживаемых провайдеров
		 * @param array $providersIds идентификатор поддерживаемых провайдеров
		 * @return iCalculateDeliveryCost
		 */
		public function setProvidersKeys(array $providersIds);

		/**
		 * Возвращает идентификаторы поддерживаемых провайдеров
		 * @return array
		 */
		public function getProvidersKeys();

		/**
		 * Возвращает время ожидание ответа
		 * @return int
		 */
		public function getTimeout();
	}
