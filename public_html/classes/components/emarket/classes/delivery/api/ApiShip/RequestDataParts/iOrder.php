<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\DeliveryTypes;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\PickupTypes;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\ProvidersKeys;

	/**
	 * Интерфейс части данных запроса с общей информацией о заказе
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts
	 */
	interface iOrder {

		/** @const string ORDER_NUMBER_KEY ключ данных части запроса с номером заказа */
		const ORDER_NUMBER_KEY = 'clientNumber';

		/** @const string HEIGHT_KEY ключ данных части запроса с высотой заказа */
		const HEIGHT_KEY = 'height';

		/** @const string LENGTH_KEY ключ данных части запроса с длиной заказа */
		const LENGTH_KEY = 'length';

		/** @const string WIDTH_KEY ключ данных части запроса с шириной заказа */
		const WIDTH_KEY = 'width';

		/** @const string WEIGHT_KEY ключ данных части запроса с весом заказа */
		const WEIGHT_KEY = 'weight';

		/** @const string PROVIDER_ID_KEY ключ данных части запроса с идентификатором провайдера */
		const PROVIDER_ID_KEY = 'providerKey';

		/** @const string PICKUP_TYPE_KEY ключ данных части запроса с идентификатором типа отгрузки */
		const PICKUP_TYPE_KEY = 'pickupType';

		/** @const string DELIVERY_TYPE_KEY ключ данных части запроса с идентификатором типа доставки */
		const DELIVERY_TYPE_KEY = 'deliveryType';

		/** @const string TARIFF_ID_KEY ключ данных части запроса с идентификатором тарифа */
		const TARIFF_ID_KEY = 'tariffId';

		/** @const string PICKUP_DATE_KEY ключ данных части запроса с датой отгрузки */
		const PICKUP_DATE_KEY = 'pickupDate';

		/** @const string DELIVERY_DATE_KEY ключ данных части запроса с датой доставки */
		const DELIVERY_DATE_KEY = 'deliveryDate';

		/** @const string POINT_IN_KEY ключ данных части запроса с идентификатором пункта приема товара */
		const POINT_IN_KEY = 'pointInId';

		/** @const string POINT_OUT_KEY ключ данных части запроса с идентификатором пункта выдачи товара */
		const POINT_OUT_KEY = 'pointOutId';

		/** @const string DELIVERY_TIME_START_KEY ключ данных части запроса с началом интервала времени доставки */
		const DELIVERY_TIME_START_KEY = 'deliveryTimeStart';

		/** @const string DELIVERY_TIME_END_KEY ключ данных части запроса с концом интервала времени доставки */
		const DELIVERY_TIME_END_KEY = 'deliveryTimeEnd';

		/** @const string DATE_FORMAT формат отображения даты */
		const DATE_FORMAT = 'Y-m-d';

		/** @const string DATE_TIMESTAMP обозначение формата отображения даты в виде unix timestamp */
		const DATE_TIMESTAMP = 'timestamp';

		/** @const string TIME_FORMAT формат времени */
		const TIME_FORMAT = '|^(\d{2,2}:\d{2,2})$|';

		/** @var string I18N_PATH группа используемых языковый меток */
		const I18N_PATH = 'emarket';

		/**
		 * Конструктор
		 * @param array $data данные части запроса
		 */
		public function __construct(array $data);

		/**
		 * Устанавливает данные части запроса
		 * @param array $data данные части запроса
		 * @return iOrder
		 */
		public function import(array $data);

		/**
		 * Возвращает данные запроса
		 * @return array
		 */
		public function export();

		/**
		 * Устанавливает номер заказа
		 * @param string $orderNumber номер заказа
		 * @return iOrder
		 */
		public function setOrderNumber($orderNumber);

		/**
		 * Возвращает номер заказа
		 * @return string
		 */
		public function getOrderNumber();

		/**
		 * Устанавливает высоту заказа
		 * @param float $height высота заказа
		 * @return iOrder
		 */
		public function setHeight($height);

		/**
		 * Возвращает высоту заказа
		 * @return float
		 */
		public function getHeight();

		/**
		 * Устанавливает длину заказа
		 * @param float $length длина заказа
		 * @return iOrder
		 */
		public function setLength($length);

		/**
		 * Возвращает длину заказа
		 * @return float
		 */
		public function getLength();

		/**
		 * Устанавливает ширину заказа
		 * @param float $width ширина заказа
		 * @return iOrder
		 */
		public function setWidth($width);

		/**
		 * Возвращает ширину заказа
		 * @return float
		 */
		public function getWidth();

		/**
		 * Устанавливает вес заказа
		 * @param float $weight вес заказа
		 * @return iOrder
		 */
		public function setWeight($weight);

		/**
		 * Возвращает вес заказа
		 * @return float
		 */
		public function getWeight();

		/**
		 * Устанавливает идентификатор провайдера (службы доставки)
		 * @param ProvidersKeys $providerKey идентификатор провайдера
		 * @return iOrder
		 */
		public function setProviderKey(ProvidersKeys $providerKey);

		/**
		 * Возвращает идентификатор провайдера службы доставки
		 * @return string
		 */
		public function getProviderKey();

		/**
		 * Устанавливает идентификатор типа отгрузки
		 * @param PickupTypes $pickupType тип отгрузки
		 * @return iOrder
		 */
		public function setPickupTypeId(PickupTypes $pickupType);

		/**
		 * Возвращает идентификатор типа отгрузки
		 * @return int
		 */
		public function getPickupTypeId();

		/**
		 * Устанавливает идентификатор типа доставки
		 * @param DeliveryTypes $deliveryType тип доставки
		 * @return iOrder
		 */
		public function setDeliveryTypeId(DeliveryTypes $deliveryType);

		/**
		 * Возвращает идентификатор типа доставки
		 * @return int
		 */
		public function getDeliveryTypeId();

		/**
		 * Устанавливает идентификатор тарифа доставки
		 * @param int $tariffId идентификатор тарифа доставки
		 * @return iOrder
		 */
		public function setTariffId($tariffId);

		/**
		 * Возвращает идентификатор тарифа
		 * @return int
		 */
		public function getTariffId();

		/**
		 * Устанавливает дату отгрузки
		 * @param \iUmiDate $date дата отгрузки
		 * @return iOrder
		 */
		public function setPickupDate(\iUmiDate $date);

		/**
		 * Возвращает дату отгрузки в определенном формате
		 * @param string|null $format формат даты
		 * @return \iUmiDate|int|string
		 */
		public function getPickupDate($format = null);

		/**
		 * Устанавливает дату доставки
		 * @param \iUmiDate $date дата доставки
		 * @return iOrder
		 */
		public function setDeliveryDate(\iUmiDate $date);

		/**
		 * Возвращает дату доставки в определенном формате
		 * @param string|null $format формат даты
		 * @return \iUmiDate|int|string
		 */
		public function getDeliveryDate($format = null);

		/**
		 * Устанавливает идентификатор точки приема товара
		 * @param int $pointId идентификатор точки приема товара
		 * @return iOrder
		 */
		public function setPointInId($pointId);

		/**
		 * Возвращает идентификатор точки приема товара
		 * @return int
		 */
		public function getPointInId();

		/**
		 * Устанавливает идентификатор точки выдачи товара
		 * @param int $pointId идентификатор точки выдачи товара
		 * @return iOrder
		 */
		public function setPointOutId($pointId);

		/**
		 * Возвращает идентификатор точки выдачи товара
		 * @return int
		 */
		public function getPointOutId();

		/**
		 * Возвращает начало интервала времени доставки заказа клиенту
		 * @return string|null
		 */
		public function getDeliveryTimeStart();

		/**
		 * Устанавливает начало интервала времени доставки заказа клиенту
		 * @param string $time время @see self::TIME_FORMAT
		 * @return iOrder
		 */
		public function setDeliveryTimeStart($time);

		/**
		 * Возвращает конец интервала времени доставки заказа клиенту
		 * @return string|null
		 */
		public function getDeliveryTimeEnd();

		/**
		 * Устанавливает конец интервала времени доставки заказа клиенту
		 * @param string $time время @see self::TIME_FORMAT
		 * @return iOrder
		 */
		public function setDeliveryTimeEnd($time);
	}
