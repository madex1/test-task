<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	use UmiCms\Classes\Components\Emarket\Delivery\Address\AddressFactory;
	use UmiCms\Classes\Components\Emarket\Delivery\Address\iAddress;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\DeliveryTypes;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\PickupTypes;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\ProvidersKeys;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData\CalculateDeliveryCost;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData\ConnectProvider;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData\SendOrder;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts\DeliveryAgent;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts\Order;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts\OrderCost;

	/**
	 * Фабрика данных для запросов к сервису ApiShip
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip
	 */
	class RequestDataFactory implements iRequestDataFactory {

		/** @inheritdoc */
		public static function createCalculateDeliveryCost($senderCity, \order $order, \ApiShipDelivery $delivery) {
			$storeCity = self::createCity($senderCity);

			$deliveryAddress = AddressFactory::createByObjectId(
				$order->getDeliveryAddressId()
			);

			$customerCity = $deliveryAddress->getCity();
			$customerCity = self::createCity($customerCity);

			return new CalculateDeliveryCost(
				[
					CalculateDeliveryCost::FROM_CITY_KEY => $storeCity,
					CalculateDeliveryCost::TO_CITY_KEY => $customerCity,
					CalculateDeliveryCost::WEIGHT_KEY => $order->getTotalWeight(),
					CalculateDeliveryCost::WIDTH_KEY => $order->getTotalWidth(),
					CalculateDeliveryCost::HEIGHT_KEY => $order->getTotalHeight(),
					CalculateDeliveryCost::LENGTH_KEY => $order->getTotalLength(),
					CalculateDeliveryCost::ASSESSED_COST_KEY => $order->getActualPrice(),
					CalculateDeliveryCost::PICKUP_TYPES_KEY => $delivery->getSavedPickupTypeIdList(),
					CalculateDeliveryCost::DELIVERY_TYPES_KEY => $delivery->getSavedDeliveryTypeIdList(),
					CalculateDeliveryCost::PROVIDERS_KEY => $delivery->getSavedProviderIdList()
				]
			);
		}

		/** @inheritdoc */
		public static function createConnectProvider($companyId, iProvider $provider) {
			return new ConnectProvider([
				ConnectProvider::COMPANY_ID_KEY => (int) $companyId,
				ConnectProvider::PROVIDER_ID_KEY => (string) $provider->getKey(),
				ConnectProvider::PROVIDER_PARAMS_KEY => $provider
			]);
		}

		/** @inheritdoc */
		public static function createSendOrder(\order $order, \ApiShipDelivery $delivery, \EmarketSettings $settings) {
			$providerKey = new ProvidersKeys($order->getDeliveryProviderId());
			/** @var \UmiCms\Classes\Components\Emarket\Delivery\ApiShip\iProvider $provider */
			$provider = self::getProviderWithSettingsByKey($delivery, $providerKey);

			$customer = \customer::get(true, $order->getCustomerId());

			$deliveryAddress = AddressFactory::createByObjectId(
				$order->getDeliveryAddressId()
			);

			$customerCity = $deliveryAddress->getCity();
			$customerCity = self::createCity($customerCity);

			if (!$provider->isRecipientCitySupported($customerCity)) {
				$messageFormat = getLabel('label-api-ship-error-provider-not-support-city-to');
				$exceptionMessage = sprintf($messageFormat, $provider->getKey(), $customerCity->getName());
				throw new \publicAdminException($exceptionMessage);
			}

			$orderNumber = self::getOrderNumber($order, $delivery->getSavedDevModeStatus());
			$isOrderWillBePayByCOD = self::isOrderWillBePayByCOD($order, $provider);

			return new SendOrder([
				SendOrder::ORDER_KEY => self::createOrder($order, $orderNumber, $provider),
				SendOrder::COST_KEY => self::createOrderCost($order, $isOrderWillBePayByCOD),
				SendOrder::SENDER_KEY => self::createOrderSender($settings),
				SendOrder::RECIPIENT_KEY => self::createOrderRecipient($customer, $deliveryAddress),
				SendOrder::ITEMS_KEY => self::createOrderItems($order, $provider)
			]);
		}

		/**
		 * Формирует часть данных для запроса отправки заказа в ApiShip, отвечающую за общие характеристики заказа
		 * @param \order $order отправляемый заказ
		 * @param string $orderNumber номер заказа
		 * @param iProvider $provider провайдер доставки
		 * @return RequestDataParts\iOrder
		 * @throws \publicAdminException
		 */
		private static function createOrder(\order $order, $orderNumber, iProvider $provider) {
			$providerKey = new ProvidersKeys($order->getDeliveryProviderId());
			$pickupTypeId = $order->getPickupTypeId();
			$pickupType = new PickupTypes($pickupTypeId);
			$deliveryTypeId = $order->getDeliveryTypeId();
			$deliveryType = new DeliveryTypes($deliveryTypeId);

			$orderData = new Order([
				Order::ORDER_NUMBER_KEY => (string) $orderNumber,
				Order::WEIGHT_KEY => $order->getTotalWeight(),
				Order::PROVIDER_ID_KEY => $providerKey,
				Order::PICKUP_TYPE_KEY => $pickupType,
				Order::DELIVERY_TYPE_KEY => $deliveryType,
				Order::TARIFF_ID_KEY => (int) $order->getDeliveryTariffId()
			]);

			if ($provider->isDeliveryDateRequired()) {
				$orderData->setDeliveryDate(
					$order->getDeliveryDate()
				);
			}

			if ($provider->isPickupDateRequired()) {
				$orderData->setPickupDate(
					$order->getPickupDate()
				);
			}

			if ($provider->isDeliveryTimeIntervalRequired()) {
				$orderData->setDeliveryTimeStart(self::DEFAULT_DELIVERY_TIME_START);
				$orderData->setDeliveryTimeEnd(self::DEFAULT_DELIVERY_TIME_END);
			}

			if ($provider->areOrderDimensionsRequired()) {
				$orderData->setWidth(
					$order->getTotalWidth()
				);
				$orderData->setLength(
					$order->getTotalLength()
				);
				$orderData->setHeight(
					$order->getTotalHeight()
				);
			}

			if ($pickupTypeId == self::getPickupTypeIdFromPoint() && $provider->isPointIdRequiredForPickupFromPoint()) {
				$orderData->setPointInId(
					(int) $order->getDeliveryPointInId()
				);
			}

			if ($deliveryTypeId == self::getDeliveryTypeIdToPoint()) {
				$orderData->setPointOutId(
					(int) $order->getDeliveryPointOutId()
				);
			}

			return $orderData;
		}

		/**
		 * Формирует часть данных для запроса отправки заказа в ApiShip, отвечающую за ценовые характеристики заказа
		 * @param \order $order отправляемый заказ
		 * @param bool $willBePayedByCOD заказ будет оплачен наложенным платежом
		 * @return RequestDataParts\iOrderCost
		 */
		private static function createOrderCost(\order $order, $willBePayedByCOD) {
			$assessedCost = self::getOrderAssessedCost($order);
			$codCost = self::getOrderCODCost($order->getActualPrice(), $willBePayedByCOD);
			$deliveryCost = self::getOrderDeliveryCost($order->getDeliveryPrice(), $willBePayedByCOD);

			return new OrderCost([
				OrderCost::ASSESSED_COST_KEY => (float) $assessedCost,
				OrderCost::COD_COST_KEY => (float) $codCost,
				OrderCost::DELIVERY_COST_KEY => (float) $deliveryCost
			]);
		}

		/**
		 * Формирует часть данных для запроса отправки заказа в ApiShip, отвечающую за отправителя заказа
		 * @param \EmarketSettings $setting настройки доставки модуля "Интерет-магазин"
		 * @return RequestDataParts\iDeliveryAgent
		 */
		private static function createOrderSender(\EmarketSettings $setting) {
			return new DeliveryAgent([
				DeliveryAgent::COUNTRY_CODE_KEY => self::getStoreAttribute($setting, 'country-code'),
				DeliveryAgent::REGION_KEY => self::getStoreAttribute($setting, 'region'),
				DeliveryAgent::CITY_KEY => self::getStoreAttribute($setting, 'city'),
				DeliveryAgent::STREET_KEY => self::getStoreAttribute($setting, 'street'),
				DeliveryAgent::HOUSE_KEY => self::getStoreAttribute($setting, 'house-number'),
				DeliveryAgent::OFFICE_KEY => self::getStoreAttribute($setting, 'apartment'),
				DeliveryAgent::CONTACT_NAME_KEY => self::getStoreAttribute($setting, 'contact-full-name'),
				DeliveryAgent::PHONE_KEY => self::getStoreAttribute($setting, 'contact-phone'),
				DeliveryAgent::EMAIL_KEY => self::getStoreAttribute($setting, 'contact-email'),
			], self::SENDER_TITLE);
		}

		/**
		 * Формирует часть данных для запроса отправки заказа в ApiShip, отвечающую за получателя заказа
		 * @param \customer $customer покупатель заказа
		 * @param iAddress $address адрес доставки заказа
		 * @return RequestDataParts\iDeliveryAgent
		 */
		private static function createOrderRecipient(\customer $customer, iAddress $address) {
			return new DeliveryAgent([
				DeliveryAgent::COUNTRY_CODE_KEY => (string) $address->getCountryISOCode(),
				DeliveryAgent::REGION_KEY => (string) $address->getRegion(),
				DeliveryAgent::CITY_KEY => (string) $address->getCity(),
				DeliveryAgent::STREET_KEY => (string) $address->getStreet(),
				DeliveryAgent::HOUSE_KEY => (string) $address->getHouseNumber(),
				DeliveryAgent::OFFICE_KEY => (string) $address->getFlatNumber(),
				DeliveryAgent::CONTACT_NAME_KEY => (string) $customer->getFullName(),
				DeliveryAgent::PHONE_KEY => (string) $customer->getPhone(),
				DeliveryAgent::EMAIL_KEY => (string) $customer->getEmail()
			], self::RECIPIENT_TITLE);
		}

		/**
		 * Формирует часть данных для запроса отправки заказа в ApiShip, отвечающую за наименования заказа
		 * @param \order $order заказ
		 * @param iProvider $provider провайдер доставки
		 * @return RequestDataParts\iOrderItem[]
		 * @throws \publicAdminException
		 */
		private static function createOrderItems(\order $order, iProvider $provider) {
			$willBePayedByCOD = self::isOrderWillBePayByCOD($order, $provider);
			$weightRequired = $provider->isOrderItemWeightRequired();
			$orderItemsData = [];

			foreach ($order->getItems() as $orderItem) {
				$orderItemsData[] = self::createOrderItem($orderItem, $willBePayedByCOD, $weightRequired);
			}

			if (umiCount($orderItemsData) == 0) {
				$exceptionFormat = getLabel('label-api-ship-error-order-without-items', 'emarket');
				$exceptionMessage = sprintf($exceptionFormat, (int) $order->getNumber());
				throw new \publicAdminException($exceptionMessage);
			}

			return $orderItemsData;
		}

		/**
		 * Формирует данные наименования заказа
		 * @param \orderItem $orderItem наименование заказа
		 * @param bool $willBePayedByCOD заказ будет оплачен наложенным платежом
		 * @param bool $weightRequired вес обязательно должен быть заполнен
		 * @return RequestDataParts\OrderItem
		 * @throws \publicAdminException
		 */
		private static function createOrderItem(\orderItem $orderItem, $willBePayedByCOD, $weightRequired) {
			$itemWeight = (float) $orderItem->getWeight();

			if ($itemWeight == 0 && $weightRequired) {
				$exceptionMessage = sprintf(getLabel('label-api-ship-error-item-without-weight'), $orderItem->getName());
				throw new \publicAdminException($exceptionMessage);
			}

			return new RequestDataParts\OrderItem([
				RequestDataParts\OrderItem::ID_KEY => (string) $orderItem->getId(),
				RequestDataParts\OrderItem::DESCRIPTION_KEY => (string) $orderItem->getName(),
				RequestDataParts\OrderItem::QUANTITY_KEY => (int) $orderItem->getAmount(),
				RequestDataParts\OrderItem::WEIGHT_KEY => (float) $itemWeight,
				RequestDataParts\OrderItem::COST_KEY => (float) self::getOrderItemCost($orderItem->getActualPrice(), $willBePayedByCOD),
				RequestDataParts\OrderItem::ASSESSED_COST_KEY => (float) $orderItem->getActualPrice()
			]);
		}

		/**
		 * Формирует часть данных для запроса вычисления вариантов стоимости доставки заказа, отвечащую за город
		 * @param string $cityName имя города
		 * @return RequestDataParts\City
		 */
		private static function createCity($cityName) {
			return new RequestDataParts\City(
				[
					RequestDataParts\City::NAME_KEY => $cityName
				]
			);
		}

		/**
		 * Определяет будет ли заказ оплачиваться наложенным платежом
		 * @param \order $order заказ
		 * @param iProvider $provider провайдер доставки
		 * @return bool
		 */
		private static function isOrderWillBePayByCOD(\order $order, iProvider $provider) {
			return (!$order->isOrderPayed() && $provider->isCODAllowed());
		}

		/**
		 * Возвращает номер заказа в UMI.CMS
		 * @param \order $order заказ
		 * @param bool $isDevMode включен ли режим отладки интеграции
		 * @return int
		 */
		private static function getOrderNumber(\order $order, $isDevMode) {
			return $isDevMode ? time() : $order->getNumber();
		}

		/**
		 * Возвращает стоимость наименования заказа
		 * @param float $orderItemPrice стоимость наименования заказа
		 * @param bool $willBePayedByCOD заказ будет оплачен наложенным платежом
		 * @return int
		 */
		private static function getOrderItemCost($orderItemPrice, $willBePayedByCOD) {
			return $willBePayedByCOD ? $orderItemPrice : 0;
		}

		/**
		 * Возвращает оценочную стоимость заказа
		 * @param \order $order заказ
		 * @return Float
		 */
		private static function getOrderAssessedCost(\order $order) {
			return $order->getActualPrice() - $order->getDeliveryPrice();
		}

		/**
		 * Возвращает сумму наложенного платежа
		 * @param float $orderPrice стоимость заказа
		 * @param bool $willBePayedByCOD заказ будет оплачен наложенным платежом
		 * @return int
		 */
		private static function getOrderCODCost($orderPrice, $willBePayedByCOD) {
			return $willBePayedByCOD ? $orderPrice : 0;
		}

		/**
		 * Возвращает стоимость доставки заказа
		 * @param float $deliveryPrice стоимость доставки заказа
		 * @param bool $willBePayedByCOD заказ будет оплачен наложенным платежом
		 * @return int
		 */
		private static function getOrderDeliveryCost($deliveryPrice, $willBePayedByCOD) {
			return $willBePayedByCOD ? $deliveryPrice : 0;
		}

		/**
		 * Возвращает значение атрибута склада
		 * @param \EmarketSettings $setting настройки доставки модуля "Интернет-магазин"
		 * @param string $option название атрибута
		 * @return mixed
		 */
		private static function getStoreAttribute(\EmarketSettings $setting, $option) {
			return $setting->get(\EmarketSettings::DEFAULT_STORE_SECTION, $option);
		}

		/**
		 * Возвращает идентификатор тип отгрузки (доставки поставщику) "От пункта приема"
		 * @return string
		 */
		private static function getPickupTypeIdFromPoint() {
			return (string) new Enums\PickupTypes(
				Enums\PickupTypes::FROM_POINT
			);
		}

		/**
		 * Возвращает идентификатор тип доставки клиенту "До пункта приема"
		 * @return string
		 */
		private static function getDeliveryTypeIdToPoint() {
			return (string) new Enums\DeliveryTypes(
				Enums\DeliveryTypes::TO_POINT
			);
		}

		/**
		 * Возвращает провайдера с инициализированными настройками по его ключу
		 * @param \ApiShipDelivery $delivery экземпляр доставки через сервис ApiShip
		 * @param string $providerKey идентификатор провайдера
		 * @return iProvider
		 */
		private static function getProviderWithSettingsByKey(\ApiShipDelivery $delivery, $providerKey) {
			$providersFactory = new ProvidersFactory();
			$providersSettings = new ProvidersSettings($delivery, $providersFactory);
			return $providersFactory::createWithSettings($providerKey, $providersSettings);
		}
	}
