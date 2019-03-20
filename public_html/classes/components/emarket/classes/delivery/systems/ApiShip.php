<?php

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	/** Реализация способа доставки средствами сервиса ApiShip */
	class ApiShipDelivery extends delivery {

		/** @var ApiShip\iRequestSender|null $requestSender отправитель запросов к сервису */
		private $requestSender;

		/** @var float $cost стоимость доставки */
		private $cost = 0;

		/** @const string DELIVERY_SERVICE_LOGIN имя поля с логином для подключения к сервису */
		const DELIVERY_SERVICE_LOGIN = 'login';

		/** @const string DELIVERY_SERVICE_PASSWORD имя поля с паролем для подключения к сервису */
		const DELIVERY_SERVICE_PASSWORD = 'password';

		/** @const string DELIVERY_DEV_MODE_STATUS имя поля с режимом разработки */
		const DELIVERY_DEV_MODE_STATUS = 'dev_mode';

		/** @const string DELIVERY_KEEP_LOG_STATUS имя поля с режимом ведения запросов */
		const DELIVERY_KEEP_LOG_STATUS = 'keep_log';

		/** @const string DELIVERY_PROVIDERS имя поля с доступными службами доставки */
		const DELIVERY_PROVIDERS = 'providers';

		/** @const string DELIVERY_DELIVERY_TYPES имя поля с доступными способами доставки */
		const DELIVERY_DELIVERY_TYPES = 'delivery_types';

		/** @const string DELIVERY_PICKUP_TYPES имя поля с доступными способами отгрузки */
		const DELIVERY_PICKUP_TYPES = 'pickup_types';

		/** @const string DELIVERY_SETTINGS имя поля с настройками служб доставки */
		const DELIVERY_SETTINGS = 'settings';

		/** @const string I18N_PATH название группы используемых языковых констант */
		const I18N_PATH = 'emarket';

		/** @inheritdoc */
		public function validate(order $order) {
			return true;
		}

		/** @inheritdoc */
		public function getDeliveryPrice(order $order) {
			return $this->getCost();
		}

		/** @inheritdoc */
		public function saveDeliveryOptions(order $order) {
			if (!isset($_REQUEST['apiship'])) {
				throw new publicException(
					getLabel('label-api-ship-error-delivery-options-not-transferred', self::I18N_PATH)
				);
			}

			$options = getRequest('apiship');

			if (!is_array($options)) {
				throw new publicException(
					getLabel('label-api-ship-error-wrong-delivery-options-transferred', self::I18N_PATH)
				);
			}

			$requiredFields = $this->getDeliveryOptionsRequiredFields();

			foreach ($requiredFields as $field) {
				if (!isset($options[$field])) {
					$errorMessageTemplate = getLabel('label-api-ship-error-delivery-option-not-transferred', self::I18N_PATH);
					throw new publicException(sprintf($errorMessageTemplate, $field));
				}
			}

			$this->setCost($options[order::DELIVERY_PRICE_FIELD]);

			$orderObject = $order->getObject();
			$orderObject->setValue(order::DELIVERY_PROVIDER_FIELD, $options[order::DELIVERY_PROVIDER_FIELD]);
			$orderObject->setValue(order::DELIVERY_TARIFF_FIELD, $options[order::DELIVERY_TARIFF_FIELD]);

			if (isset($options[order::DELIVERY_POINT_OUT_FIELD]) && !empty($options[order::DELIVERY_POINT_OUT_FIELD])) {
				$orderObject->setValue(order::DELIVERY_POINT_OUT_FIELD, $options[order::DELIVERY_POINT_OUT_FIELD]);
				$deliveryTypeId = new ApiShip\Enums\DeliveryTypes(
					ApiShip\Enums\DeliveryTypes::TO_POINT
				);
			} else {
				$deliveryTypeId = new ApiShip\Enums\DeliveryTypes(
					ApiShip\Enums\DeliveryTypes::TO_DOOR
				);
			}

			$orderObject->setValue(order::DELIVERY_TYPE_FIELD, $deliveryTypeId);
			$orderObject->commit();

			return $order;
		}

		/**
		 * Возвращает идентификаторы доступных способов отгрузки
		 * @return array
		 */
		public function getSavedPickupTypeIdList() {
			$pickupTypes = (string) $this->getObject()
				->getValue(self::DELIVERY_PICKUP_TYPES);

			$pickupTypes = json_decode($pickupTypes, true);
			return is_array($pickupTypes) ? $pickupTypes : [];
		}

		/**
		 * Возвращает идентификаторы доступных способов доставки
		 * @return array
		 */
		public function getSavedDeliveryTypeIdList() {
			$deliveryTypes = (string) $this->getObject()
				->getValue(self::DELIVERY_DELIVERY_TYPES);

			$deliveryTypes = json_decode($deliveryTypes, true);
			return is_array($deliveryTypes) ? $deliveryTypes : [];
		}

		/**
		 * Возвращает режим разработки
		 * @return bool
		 */
		public function getSavedDevModeStatus() {
			return (bool) $this->getObject()
				->getValue(self::DELIVERY_DEV_MODE_STATUS);
		}

		/**
		 * Возвращает режим ведения журнала запросов
		 * @return bool
		 */
		public function getSavedKeepLogStatus() {
			return (bool) $this->getObject()
				->getValue(self::DELIVERY_KEEP_LOG_STATUS);
		}

		/**
		 * Возвращает идентификатор доступных служб доставки
		 * @return array
		 */
		public function getSavedProviderIdList() {
			$providers = (string) $this->getObject()
				->getValue(self::DELIVERY_PROVIDERS);

			$providers = json_decode($providers, true);
			return is_array($providers) ? $providers : [];
		}

		/**
		 * Возвращает логин для подключения к систему
		 * @return string логин
		 */
		public function getSavedLogin() {
			if ($this->getSavedDevModeStatus()) {
				return ApiShip\iRequestSender::DEV_MODE_LOGIN;
			}

			return (string) $this->getObject()
				->getValue(self::DELIVERY_SERVICE_LOGIN);
		}

		/**
		 * Возвращает пароль для подключения к систему
		 * @return string пароль
		 */
		public function getSavedPassword() {
			if ($this->getSavedDevModeStatus()) {
				return ApiShip\iRequestSender::DEV_MODE_PASSWORD;
			}

			return (string) $this->getObject()
				->getValue(self::DELIVERY_SERVICE_PASSWORD);
		}

		/**
		 * Актуализует статусы заказов ApiShip и возврашает количество обновленных заказов
		 * @param array $ordersIds идентификаторы статусов заказов ApiShip
		 * @return int
		 * @throws publicAdminException
		 */
		public function refreshOrdersStatuses(array $ordersIds) {
			if (umiCount($ordersIds) === 0) {
				throw new publicAdminException(
					getLabel('label-api-ship-error-orders-ids-list-not-transferred', self::I18N_PATH)
				);
			}

			$ordersIds = array_map('intval', $ordersIds);
			$orders = $this->getApiShipOrdersCollection()
				->getOrdersByIds($ordersIds);

			$ordersNumbersToOrders = [];
			$ordersNumbers = [];

			foreach ($orders as $key => $order) {
				$ordersNumbers[] = (int) $order->getNumber();
				$ordersNumbersToOrders[$order->getNumber()] = $order;
			}

			try {
				$ordersStatuses = $this->getRequestSender()
					->getOrdersStatuses($ordersNumbers);
			} catch (Exception $e) {
				throw new publicAdminException($e->getMessage());
			}

			$counter = 0;

			if (!arrayValueContainsNotEmptyArray($ordersStatuses, 'succeedOrders')) {
				return $counter;
			}

			foreach ($ordersStatuses['succeedOrders'] as $orderStatus) {
				if (!isset($orderStatus['orderInfo'])) {
					continue;
				}

				$orderInfo = $orderStatus['orderInfo'];

				if (!isset($orderInfo['orderId'])) {
					continue;
				}

				$orderNumber = $orderInfo['orderId'];

				if (!isset($ordersNumbersToOrders[$orderNumber])) {
					continue;
				}

				if (!isset($orderStatus['status']['key'])) {
					continue;
				}

				$order = $ordersNumbersToOrders[$orderNumber];
				$orderStatus = $orderStatus['status']['key'];
				$providerNumber = isset($orderInfo['providerNumber']) ? $orderInfo['providerNumber'] : null;
				$this->updateOrdersStatuses($order, $orderStatus, $providerNumber);
				$counter++;
			}

			return $counter;
		}

		/**
		 * Возвращает пункты выдачи службы доставки в заданном городе
		 * @param string $cityName название города
		 * @param ApiShip\iProvider $provider служба доставки
		 * @param int $typeId идентификатор типа пункта выдачи
		 * @return array
		 * @throws publicAdminException
		 */
		public function getDeliveryPointsByProviderAndCity($cityName, ApiShip\iProvider $provider, $typeId) {
			try {
				$pointId = null;
				return $this->getRequestSender()
					->getDeliveryPointsList($pointId, $provider->getKey(), $cityName, $typeId);
			} catch (Exception $e) {
				throw new publicAdminException($e->getMessage());
			}
		}

		/**
		 * Подключает службу доставки
		 * @param ApiShip\iProvider $provider служба доставки
		 * @return array
		 * @throws publicAdminException
		 */
		public function connectToProvider(ApiShip\iProvider $provider) {
			try {
				$providersFactory = new ApiShip\ProvidersFactory();
				$providersSettings = new ApiShip\ProvidersSettings($this, $providersFactory);
				$providerKey = new ApiShip\Enums\ProvidersKeys($provider->getKey());
				$provider = $providersFactory::createWithSettings($providerKey, $providersSettings);

				if ($provider->isConnected()) {
					return [];
				}

				$createProviderRequest = ApiShip\RequestDataFactory::createConnectProvider(
					$this->getApiShipCompanyId(), $provider
				);

				$result = $this->getRequestSender()
					->connectToProvider($createProviderRequest);

				$this->saveProviderIsConnectedFlag($providersSettings, $providerKey->__toString());
				return $result;
			} catch (Exception $e) {
				throw new publicAdminException($e->getMessage());
			}
		}

		/**
		 * Обновляет подключение службы доставки
		 * @param ApiShip\iProvider $provider служба доставки
		 * @return array
		 * @throws publicAdminException
		 */
		public function updateProviderConnection(ApiShip\iProvider $provider) {
			try {
				$providersFactory = new ApiShip\ProvidersFactory();
				$providersSettings = new ApiShip\ProvidersSettings($this, $providersFactory);
				$providerKey = new ApiShip\Enums\ProvidersKeys($provider->getKey());
				$provider = $providersFactory::createWithSettings($providerKey, $providersSettings);

				if (!$provider->isConnected()) {
					return [];
				}

				$providerRequest = ApiShip\RequestDataFactory::createConnectProvider(
					$this->getApiShipCompanyId(), $provider
				);

				return $this->getRequestSender()
					->updateProviderConnection($providerKey, $providerRequest);
			} catch (Exception $e) {
				throw new publicAdminException($e->getMessage());
			}
		}

		/**
		 * Возвращает варианты доставки (подходящие службы доставки их тарифы) заказа.
		 * @param order $order заказ
		 * @return array
		 * @throws publicAdminException
		 */
		public function getDeliveryOptionList(order $order) {
			try {
				$request = ApiShip\RequestDataFactory::createCalculateDeliveryCost(
					$this->getStoreCity(), $order, $this
				);

				return $this->getRequestSender()
					->calculate($request);
			} catch (Exception $e) {
				throw new publicAdminException($e->getMessage());
			}
		}

		/**
		 * Инициализирует отправителя запросов к сервису
		 * @param string $login логин
		 * @param string $password пароль
		 * @param bool $isDevMode включен ли режим разработки
		 * @param bool $keepLog нужно ли вести лог запросов
		 * @return ApiShip\RequestSender
		 */
		public function initRequestSender($login, $password, $isDevMode = false, $keepLog = false) {
			$requestSender = new ApiShip\RequestSender($login, $password, $isDevMode, $keepLog);
			$requestSender->requestAccessToken();
			return $requestSender;
		}

		/**
		 * Возвращает отправителя запросов к сервису
		 * @return ApiShip\iRequestSender
		 */
		public function getRequestSender() {
			if (!$this->requestSender instanceof ApiShip\iRequestSender) {
				$this->requestSender = $this->initRequestSender(
					$this->getSavedLogin(),
					$this->getSavedPassword(),
					$this->getSavedDevModeStatus(),
					$this->getSavedKeepLogStatus()
				);
			}

			return $this->requestSender;
		}

		/**
		 * Загружает настройки служб доставки сервиса
		 * @return array настройки служб доставки сервиса
		 */
		public function getSavedProviderSettingsList() {
			$settings = (string) $this->getValue(self::DELIVERY_SETTINGS);
			$settings = json_decode($settings, true);
			return is_array($settings) ? $settings : [];
		}

		/**
		 * Сохраняет настройки служб доставки сервиса
		 * @param array $settings настройки служб доставки сервиса
		 */
		public function saveProviderSettingsList(array $settings) {
			$settings = json_encode($settings);
			$object = $this->getObject();
			$object->setValue(self::DELIVERY_SETTINGS, $settings);
			$object->commit();
		}

		/**
		 * @param $umiOrderRefNumber
		 * @return null|ApiShip\Orders\iEntity
		 */
		public function getApiShipOrderByUmiOrderRefNumber($umiOrderRefNumber) {
			return $this->getApiShipOrdersCollection()
				->getByUmiOrderRefNumber($umiOrderRefNumber);
		}

		/**
		 * Возвращает название города склада
		 * @return string
		 * @throws publicAdminException
		 */
		protected function getStoreCity() {
			return (string) $this->getModule()
				->getSettings()
				->get(\EmarketSettings::DEFAULT_STORE_SECTION, 'city');
		}

		/**
		 * Возвращает идентификатор компании текущего пользователя, авторизованного в ApiShip
		 * @return int
		 * @throws publicAdminException
		 */
		protected function getApiShipCompanyId() {
			$data = $this->getCurrentApiShipUserData();

			if (!isset($data['companyId'])) {
				throw new publicAdminException(
					getLabel('label-api-ship-error-company-id-not-received', self::I18N_PATH)
				);
			}

			return (int) $data['companyId'];
		}

		/**
		 * Возвращает данные текущего пользователя, авторизованного в ApiShip
		 * @return array
		 * @throws publicAdminException
		 */
		protected function getCurrentApiShipUserData() {
			try {
				return $this->getRequestSender()
					->getCurrentUserData();
			} catch (Exception $e) {
				throw new publicAdminException($e->getMessage());
			}
		}

		/**
		 * Возвращает стоимость доставки
		 * @return float
		 */
		protected function getCost() {
			return $this->cost;
		}

		/**
		 * Устанавливает стоимость доставки
		 * @param float $cost стоимость доставки
		 * @return $this
		 */
		protected function setCost($cost) {
			$this->cost = (float) $cost;
			return $this;
		}

		/**
		 * Возвращает названия полей заказа, которые должны быть обязательно заполнены пользователем
		 * для использования способа доставки ApiShip
		 * @return array
		 */
		protected function getDeliveryOptionsRequiredFields() {
			return [
				order::DELIVERY_PROVIDER_FIELD,
				order::DELIVERY_TARIFF_FIELD,
				order::DELIVERY_PRICE_FIELD,
				order::DELIVERY_DATE_FIELD
			];
		}

		/**
		 * Возвращает коллекцию заказов ApiShip
		 * @return UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders\iCollection
		 * @throws Exception
		 */
		protected function getApiShipOrdersCollection() {
			return ServiceContainerFactory::create()
				->get('ApiShipOrders');
		}

		/**
		 * Возвращает экземпляр модуля "Интернет магазин"
		 * @return emarket
		 * @throws publicAdminException
		 */
		protected function getModule() {
			/** @var emarket $module */
			$module = cmsController::getInstance()->getModule('emarket');

			if (!$module instanceof emarket) {
				throw new publicAdminException(
					getLabel('label-api-ship-error-emarket-not-installed', self::I18N_PATH)
				);
			}

			return $module;
		}

		/**
		 * Обновляет статус заказа в ApiShip, и статус связанного заказа в UMI.CMS, если это необходимо
		 * @param ApiShip\Orders\iEntity $order заказ в ApiShip
		 * @param string $orderStatus статус заказа в ApiShip
		 * @param string|null $providerNumber номер заказа службы доставка, соответствующий заказу в ApiShip
		 */
		protected function updateOrdersStatuses(ApiShip\Orders\iEntity $order, $orderStatus, $providerNumber = null) {
			$order->setStatus(new ApiShip\Enums\OrderStatuses($orderStatus));

			if ($order->isUpdated()) {
				$this->updateUmiOrderDeliveryStatus(
					$order->getUmiOrderRefNumber(), $orderStatus
				);
			}

			if ($providerNumber !== null) {
				$order->setProviderOrderRefNumber($providerNumber);
			}

			$order->commit();
		}

		/**
		 * Изменяет статус доставки заказа UMI.CMS на статус, соответствующий статусу заказа в ApiShip
		 * @param int $umiOrderNumber номер заказа UMI.CMS
		 * @param string $apiShipOrderStatus статус заказа в ApiShip
		 * @return bool была ли выполнена операция
		 * @throws wrongParamException
		 */
		protected function updateUmiOrderDeliveryStatus($umiOrderNumber, $apiShipOrderStatus) {
			$umiOrder = order::getByNumber($umiOrderNumber);

			if (!$umiOrder instanceof order) {
				return false;
			}

			$umiOrderStatus = $umiOrder->getOrderStatus();
			$umiOrderStatus = order::getCodeByStatus($umiOrderStatus);
			$newUmiOrderStatus = ApiShip\Utils\OrderStatusConverter::convertApiShipToUmi($apiShipOrderStatus);

			if ($umiOrderStatus == $newUmiOrderStatus) {
				return false;
			}

			$newUmiOrderStatus = order::getStatusByCode($newUmiOrderStatus);
			$umiOrder->setDeliveryStatus($newUmiOrderStatus);

			return true;
		}

		/**
		 * Сохраняет флаг того, что провайдер был подключен
		 * @param ApiShip\iProvidersSettings $settings настройки провайдеров
		 * @param string $providerKey ключ подключенного провайдера
		 * @param bool $flag значение флага
		 * @return bool была ли выполнена операция
		 */
		protected function saveProviderIsConnectedFlag(ApiShip\iProvidersSettings $settings, $providerKey, $flag = true) {
			$providerSettings = $settings->get($providerKey);
			$providerSettings[ApiShip\iProvider::IS_CONNECTED_KEY] = [
				ApiShip\iProvider::VALUE_KEY => $flag
			];
			$settings->set($providerSettings, $providerKey);
			$settings->save();
			return true;
		}
	}
