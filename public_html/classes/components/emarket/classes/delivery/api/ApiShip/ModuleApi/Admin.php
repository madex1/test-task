<?php
	/** Класс административного функционала UMI.CMS для способа доставки ApiShip */

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\ModuleApi;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums;

	class Admin implements \iModulePart {

		use \tModulePart;

		/** @const string CHECK_USER_MODE_NAME название параметра, который включает режим аутентификации пользователя */
		const AUTH_USER_MODE_NAME = 'auth-mode';

		/** @const string REGISTER_USER_MODE_NAME название параметра, который включает режим регистрации пользователя */
		const REGISTER_USER_MODE_NAME = 'reg-mode';

		/** @const string WAY_BILL_ITEMS_KEY ключ данных списка актов приема-передачи товаров с данными отдельнных актов */
		const WAY_BILL_ITEMS_KEY = 'waybillItems';

		/** @const string WAY_BILL_FILE_KEY ключ данных данных акта приема-передачи товара с путем до файла */
		const WAY_BILL_FILE_KEY = 'file';

		/** @const string TARIFFS_KEY ключ данных со списком тарифов */
		const TARIFFS_KEY = 'tariffs';

		/** @const string DELIVERY_TO_DOOR_KEY ключ данных с вариантами доставок "До двери" */
		const DELIVERY_TO_DOOR_KEY = 'deliveryToDoor';

		/** @const string DELIVERY_TO_POINT_KEY ключ данных с вариантами доставок "До пункта выдачи" */
		const DELIVERY_TO_POINT_KEY = 'deliveryToPoint';

		/** @const string PROVIDER_ID_KEY ключ данных с идентификатором провайдера (службы доставки) */
		const PROVIDER_ID_KEY = 'providerKey';

		/** @const string ROWS_KEY ключ данных с записями */
		const ROWS_KEY = 'rows';

		/** @var \EmarketAdmin|void $admin базовый класс административной панели модуля */
		public $admin;

		/**
		 * Конструктор
		 * @param \emarket $module
		 * @throws \coreException
		 * @throws \RequiredPropertyHasNoValueException
		 */
		public function __construct(\emarket $module) {
			if (!$module->isClassImplemented($module::ADMIN_CLASS)) {
				throw new \coreException(
					getLabel('label-module-admin-not-implemented', $this->getModuleName())
				);
			}

			$this->admin = $module->getImplementedInstance($module::ADMIN_CLASS);
		}

		/**
		 * Возвращает заказы в ApiShip
		 * @param int|null $limit ограничение на количество заказов
		 * @param int|null $offset смещение порядка заказов
		 * @throws \Exception
		 * @throws \publicAdminException
		 */
		public function getApiShipOrders($limit = null, $offset = null) {
			/** @var \iUmiConstantMapInjector|ApiShip\Orders\Collection $ordersCollection */
			$ordersCollection = $this->getApiShipOrdersCollection();
			$collectionConstants = $ordersCollection->getMap();
			$limit = ($limit === null) ? (int) $this->admin->getLimit() : $limit;
			$offset = ($offset === null) ? (int) $this->admin->getOffset($limit) : $offset;

			try {
				$queryParams = $this->admin->getPageNavigationQueryParams(
					$collectionConstants, $limit, $offset
				);

				$queryParams += $this->admin->getEntitiesFilterQueryParams($ordersCollection);

				$orderStatusFieldName = $collectionConstants->get('STATUS_FIELD_NAME');
				$queryParams = $this->decodeApiShipOrdersStatuses($queryParams, $orderStatusFieldName);
				$orders = $ordersCollection->export($queryParams);
				$orders = $this->encodeApiShipOrdersStatuses($orders, $orderStatusFieldName);

				$queryParams += $this->admin->getQueryParamsToCalcTotal($collectionConstants);

				$total = $ordersCollection->count($queryParams);
			} catch (\Exception $e) {
				$orders = $this->admin->getSimpleErrorMessage(
					$e->getMessage()
				);
				$total = 0;
			}

			$this->module->printJson(
				$this->admin->prepareTableControlEntities($orders, $total)
			);
		}

		/**
		 * Отправляет запрос на обновление статусов заказов в ApiShip
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @param bool|array $ordersIds идентификаторы обновляемых заказов
		 * @throws \Exception
		 * @throws \publicAdminException
		 */
		public function refreshApiShipOrdersStatuses($deliveryId = false, $ordersIds = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);
			$ordersIds = ($ordersIds === false) ? $this->admin->getNumberedParameter(1) : $ordersIds;
			$ordersIds = (array) $ordersIds;

			$updatedOrdersCounter = (umiCount($ordersIds) > 0) ? $delivery->refreshOrdersStatuses($ordersIds) : 0;

			$this->module->printJson([
				'updated' => $updatedOrdersCounter
			]);
		}

		/**
		 * Отправляет запрос на отмену заказа в ApiShip
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @param bool|int $orderId идентификатор отменяемого заказа
		 * @throws \publicAdminException
		 */
		public function cancelApiShipOrder($deliveryId = false, $orderId = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);
			$order = $this->getApiShipOrderById($orderId);
			$orderNumber = (int) $order->getNumber();

			try {
				$delivery->getRequestSender()
					->cancelOrder($orderNumber);
			} catch (\Exception $e) {
				throw new \publicAdminException($e->getMessage());
			}

			$order->setStatus(new ApiShip\Enums\OrderStatuses());
			$order->commit();

			$this->module->printJson(
				$this->admin->getSimpleSuccessMessage()
			);
		}

		/**
		 * Сохраняет параметры пользователя системы ApiShip.
		 * При необходимости выполняет аутентификацию или регистрацию пользователя.
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @param bool|string $login логин
		 * @param bool|string $password пароль
		 * @throws \publicAdminException
		 */
		public function saveApiShipUser($deliveryId = false, $login = false, $password = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);

			$loginFieldName = \ApiShipDelivery::DELIVERY_SERVICE_LOGIN;
			$login = ($login === false) ? getRequest($loginFieldName) : $login;

			$passwordFieldName = \ApiShipDelivery::DELIVERY_SERVICE_PASSWORD;
			$password = ($password === false) ? getRequest($passwordFieldName) : $password;

			if (isset($_REQUEST[self::AUTH_USER_MODE_NAME])) {
				$this->authApiShipUser($delivery, $login, $password);
			}

			if (isset($_REQUEST[self::REGISTER_USER_MODE_NAME])) {
				$this->registerApiShipUser($delivery, $login, $password);
			}

			$delivery->setValue($loginFieldName, $login);
			$delivery->setValue($passwordFieldName, $password);
			$delivery->commit();

			$this->admin->chooseRedirect();
		}

		/**
		 * Получает и выводит в буффер адрес этикетки для доставки в ApiShip
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @param bool|int $orderId идентификатор связи между заказами в UMI.CMS и ApiShip
		 * @throws \publicAdminException
		 */
		public function getApiShipLabel($deliveryId = false, $orderId = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);
			$order = $this->getApiShipOrderById($orderId);
			$orderNumber = (int) $order->getNumber();

			try {
				$labelData = $delivery->getRequestSender()
					->getOrdersLabels([$orderNumber]);
			} catch (\Exception $e) {
				throw new \publicAdminException($e->getMessage());
			}

			if (!isset($labelData['url']) || !$labelData['url']) {
				throw new \publicAdminException(
					getLabel('label-api-ship-labels-not-received', $this->getModuleName())
				);
			}

			$this->module->printJson($labelData);
		}

		/**
		 * Получает и выводит в буффер адрес акт приема-передачи товара для доставки в ApiShip
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @param bool|int $orderId идентификатор связи между заказами в UMI.CMS и ApiShip
		 * @throws \publicAdminException
		 */
		public function getApiShipWayBill($deliveryId = false, $orderId = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);
			$order = $this->getApiShipOrderById($orderId);
			$orderNumber = (int) $order->getNumber();

			try {
				$wayBillData = $delivery->getRequestSender()
					->getOrdersWaybills([$orderNumber]);
			} catch (\Exception $e) {
				throw new \publicAdminException($e->getMessage());
			}

			if (!arrayValueContainsNotEmptyArray($wayBillData, self::WAY_BILL_ITEMS_KEY)) {
				throw new \publicAdminException(
					getLabel('label-api-ship-bills-not-received', $this->getModuleName())
				);
			}

			$item = array_shift($wayBillData[self::WAY_BILL_ITEMS_KEY]);

			if (!isset($item[self::WAY_BILL_FILE_KEY])) {
				throw new \publicAdminException(
					getLabel('label-api-ship-bill-file-not-received', $this->getModuleName())
				);
			}

			$this->module->printJson($item);
		}

		/**
		 * Получает и выводит в буффер список вариантов доставки
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @param bool|int $orderId идентификатор заказа в UMI.CMS
		 * @throws \publicAdminException
		 */
		public function getApiShipDeliveryOptions($deliveryId = false, $orderId = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);
			$orderId = ($orderId === false) ? $this->admin->getNumberedParameter(1) : $orderId;
			$order = \order::get($orderId);

			$this->module->printJson(
				$delivery->getDeliveryOptionList($order)
			);
		}

		/**
		 * Отправляет заказ в ApiShip, в случае успеха выводит в буффер данные
		 * созданной связи между заказами в ApiShip и UMI.CMS
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @param bool|int $orderId идентификатор заказа в UMI.CMS
		 * @throws \publicAdminException
		 */
		public function sendApiShipOrderRequest($deliveryId = false, $orderId = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);
			$orderId = ($orderId === false) ? $this->admin->getNumberedParameter(1) : $orderId;

			$umiCmsOrder = \order::get($orderId);
			$settings = $this->module->getSettings();

			try {
				$orderRequest = ApiShip\RequestDataFactory::createSendOrder($umiCmsOrder, $delivery, $settings);
				$umiCmsOrderNumber = $orderRequest->getOrder()
					->getOrderNumber();
				$orderResponse = $delivery->getRequestSender()
					->order($orderRequest);

				$apiShipOrderNumber = $orderResponse['orderId'];
				$apiShipOrder = $this->getApiShipOrdersCollection()
					->createOrder($apiShipOrderNumber, $umiCmsOrderNumber);
			} catch (\Exception $e) {
				throw new \publicAdminException($e->getMessage());
			}

			if (!$apiShipOrder instanceof ApiShip\Orders\iEntity) {
				throw new \publicAdminException(
					getLabel('label-api-ship-wrong-order-ref-created', $this->getModuleName())
				);
			}

			$this->module->printJson(
				$apiShipOrder->export()
			);
		}

		/**
		 * Отправляет запрос на изменение заказа в ApiShip, выводит в буффер результат запроса
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @param bool|int $orderId идентификатор заказа в UMI.CMS
		 * @throws \publicAdminException
		 */
		public function sendApiShipUpdateOrderRequest($deliveryId = false, $orderId = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);
			$orderId = ($orderId === false) ? $this->admin->getNumberedParameter(1) : $orderId;

			$umiCmsOrder = \order::get($orderId);
			$settings = $this->module->getSettings();

			try {
				$orderRequest = ApiShip\RequestDataFactory::createSendOrder($umiCmsOrder, $delivery, $settings);
				$umiCmsOrderNumber = $orderRequest->getOrder()
					->getOrderNumber();
				$apiShipOrder = $delivery->getApiShipOrderByUmiOrderRefNumber($umiCmsOrderNumber);

				if (!$apiShipOrder instanceof ApiShip\Orders\iEntity) {
					$format = getLabel('label-api-ship-error-order-not-sent', $this->getModuleName());
					throw new \publicAdminException(
						sprintf($format, $umiCmsOrderNumber)
					);
				}

				$response = $delivery->getRequestSender()
					->orderModify($apiShipOrder->getNumber(), $orderRequest);

				$this->module->printJson($response);
			} catch (\Exception $e) {
				throw new \publicAdminException($e->getMessage());
			}
		}

		/**
		 * Получает и выводит в буффер тарифы провайдера
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @param string|bool $providerKey идентификатор провайдера
		 * @throws \publicAdminException
		 */
		public function getApiShipProviderTariffs($deliveryId = false, $providerKey = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);
			$providerKey = ($providerKey === false) ? $this->admin->getNumberedParameter(1) : $providerKey;

			$this->module->printJson(
				$this->getTariffsByProvider($delivery, $providerKey)
			);
		}

		/**
		 * Получает и выводит в буффер настройки выбранных провайдеров
		 * @param bool $deliveryId идентификатор способа доставки ApiShip
		 * @throws \publicAdminException
		 */
		public function getApiShipChosenProvidersSettings($deliveryId = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);

			$providersFactory = new ApiShip\ProvidersFactory();
			$providersSettings = new ApiShip\ProvidersSettings($delivery, $providersFactory);

			$chosenProviders = $delivery->getSavedProviderIdList();
			$providersWithSettings = array_keys($providersSettings->get());

			$chosenProvidersWithoutSettings = array_diff($chosenProviders, $providersWithSettings);
			$notChosenProvidersWithSettings = array_diff($providersWithSettings, $chosenProviders);

			$providersSettings->appendProvidersSettings($chosenProvidersWithoutSettings);
			$providersSettings->removeProvidersSettings($notChosenProvidersWithSettings);

			$this->module->printJson(
				$providersSettings->get()
			);
		}

		/** Получает и выводит в буффер поддерживаемые способы доставки клиенту */
		public function getApiShipSupportedDeliveryTypes() {
			$deliveryTypes = new Enums\DeliveryTypes();
			$deliveryTypesTitles = $deliveryTypes->getValuesTitles();

			$result = [];

			foreach ($deliveryTypesTitles as $deliveryTypeId => $deliveryTypeTitle) {
				$result[] = [
					'id' => $deliveryTypeId,
					'name' => $deliveryTypeTitle
				];
			}

			$this->module->printJson($result);
		}

		/** Получает и выводит в буффер поддерживаемые способы доставки провайдеру */
		public function getApiShipSupportedPickupTypes() {
			$pickupTypes = new Enums\PickupTypes();
			$pickupTypesTitles = $pickupTypes->getValuesTitles();

			$result = [];

			foreach ($pickupTypesTitles as $pickupTypeId => $pickupTypeTitle) {
				$result[] = [
					'id' => $pickupTypeId,
					'name' => $pickupTypeTitle
				];
			}

			$this->module->printJson($result);
		}

		/**
		 * Получает и выводит в буффер провайдеров, доступных для подключения
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @throws \publicAdminException
		 */
		public function getApiShipAllProviders($deliveryId = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);

			try {
				$providersData = $delivery->getRequestSender()
					->getDeliveryProvidersList();
			} catch (\Exception $e) {
				throw new \publicAdminException($e->getMessage());
			}

			if (!arrayValueContainsNotEmptyArray($providersData, self::ROWS_KEY)) {
				$this->module->printJson([]);
			}

			$supportedProvidersKeys = ApiShip\ProvidersFactory::getSupportedProviderKeyList();
			$result = [];

			foreach ($providersData[self::ROWS_KEY] as $providerData) {
				if (!isset($providerData['key'])) {
					continue;
				}

				$providerKey = $providerData['key'];

				if (!in_array($providerKey, $supportedProvidersKeys)) {
					continue;
				}

				$result[] = $providerData;
			}

			$this->module->printJson($result);
		}

		/**
		 * Отправляет запрос на подключение провайдера
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @param bool|string $providerKey строковой идентификатор провайдера
		 * @throws \publicAdminException
		 */
		public function connectToApiShipProvider($deliveryId = false, $providerKey = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);
			$providerKey = ($providerKey === false) ? $this->admin->getNumberedParameter(1) : $providerKey;

			$providerKey = new Enums\ProvidersKeys($providerKey);
			$provider = ApiShip\ProvidersFactory::create($providerKey);

			$this->module->printJson(
				$delivery->connectToProvider($provider)
			);
		}

		/**
		 * Отправляет запрос на обновление подключения провайдера
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @param bool|string $providerKey строковой идентификатор провайдера
		 * @throws \publicAdminException
		 */
		public function updateApiShipProviderConnection($deliveryId = false, $providerKey = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);
			$providerKey = ($providerKey === false) ? $this->admin->getNumberedParameter(1) : $providerKey;

			$providerKey = new Enums\ProvidersKeys($providerKey);
			$provider = ApiShip\ProvidersFactory::create($providerKey);

			$this->module->printJson(
				$delivery->updateProviderConnection($provider)
			);
		}

		/**
		 * Получает и выводит в буффер точки выдачи и приема товаров провайдера
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @param bool|string $providerKey строковой идентификатор провайдера
		 * @throws \publicAdminException
		 */
		public function getApiShipPointsByProvider($deliveryId = false, $providerKey = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);
			$providerKey = ($providerKey === false) ? $this->admin->getNumberedParameter(1) : $providerKey;

			try {
				$pointId = null;
				$points = $delivery->getRequestSender()
					->getDeliveryPointsList($pointId, $providerKey);
			} catch (\Exception $e) {
				throw new \publicAdminException($e->getMessage());
			}

			if (!arrayValueContainsNotEmptyArray($points, self::ROWS_KEY)) {
				$exceptionMessage = getLabel('label-api-ship-provider-points-not-received', $this->getModuleName());
				throw new \publicAdminException(
					sprintf($exceptionMessage, $providerKey)
				);
			}

			$this->module->printJson($points);
		}

		/**
		 * Возвращает конфигурацию табличного контрола для заказа ApiShip
		 * @return array
		 */
		public function getApiShipOrderConfig() {
			$moduleName = $this->getModuleName();
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'emarket',
						'type' => 'load',
						'name' => 'getApiShipOrders'
					]
				],
				'default' => 'number[250px]|umi_order_ref_number[250px]|status[500px]',
				'fields' => [
					[
						'name' => 'number',
						'title' => getLabel('label-field-api-ship-order-ref-external-id', $moduleName),
						'type' => 'number',
						'editable' => 'false',
						'show_edit_page_link' => 'false'
					],
					[
						'name' => 'umi_order_ref_number',
						'title' => getLabel('label-field-api-ship-order-ref-internal-id', $moduleName),
						'type' => 'number',
						'editable' => 'false'
					],
					[
						'name' => 'provider_order_ref_number',
						'title' => getLabel('label-field-api-ship-order-ref-provider-id', $moduleName),
						'type' => 'string',
						'editable' => 'false'
					],
					[
						'name' => 'status',
						'title' => getLabel('label-field-api-ship-order-ref-status-id', $moduleName),
						'type' => 'relation',
						'editable' => 'false',
						'multiple' => 'false',
						'options' => implode(',', $this->getApiShipOrderStatuses())
					]
				]
			];
		}

		/** Выводит в буффер конфигурацию табличного контрола заказов ApiShip в формате json */
		public function getApiShipDataSetConfiguration() {
			/** @var \emarket $module */
			$module = $this->getModule();
			$module->printJson(
				$this->getApiShipOrderConfig()
			);
		}

		/**
		 * Проверяет был заказ UMI.CMS отправлен в ApiShip, выводит в буффер результат проверки
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @param bool|int $orderId идентификатор заказа в UMI.CMS
		 * @throws \publicAdminException
		 */
		public function isOrderSentToApiShip($deliveryId = false, $orderId = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);
			$orderId = ($orderId === false) ? $this->admin->getNumberedParameter(1) : $orderId;

			try {
				$order = \order::get($orderId);

				$apiShipOrder = $delivery->getApiShipOrderByUmiOrderRefNumber($order->getNumber());
			} catch (\Exception $e) {
				throw new \publicAdminException($e->getMessage());
			}

			$umiOrderSent = (int) ($apiShipOrder instanceof ApiShip\Orders\Entity);
			$this->module->printJson(['result' => $umiOrderSent]);
		}

		/**
		 * Стирает данные для подключения к сервису ApiShip
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @throws \publicAdminException
		 */
		public function resetApiShipCredentials($deliveryId = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);
			$delivery->setValue($delivery::DELIVERY_SERVICE_LOGIN, '');
			$delivery->setValue($delivery::DELIVERY_SERVICE_PASSWORD, '');
			$delivery->commit();

			$this->module->printJson(
				$this->admin->getSimpleSuccessMessage()
			);
		}

		/**
		 * @return \UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders\iCollection
		 * @throws \Exception
		 */
		private function getApiShipOrdersCollection() {
			return \ServiceContainerFactory::create()
				->get('ApiShipOrders');
		}

		/**
		 * Произодит аутентификацию в системе ApiShip
		 * @param \ApiShipDelivery $delivery экземпляр способа доставки
		 * @param string $login логин
		 * @param string $password пароль
		 * @return bool
		 */
		private function authApiShipUser(\ApiShipDelivery $delivery, $login, $password) {
			try {
				$delivery->initRequestSender($login, $password);
			} catch (\Exception $e) {
				$this->module->errorNewMessage($e->getMessage());
				$this->module->errorPanic();
			}

			return true;
		}

		/**
		 * Регистрирует в системе ApiShip
		 * @param \ApiShipDelivery $delivery экземпляр способа доставки
		 * @param string $login логин
		 * @param string $password пароль
		 * @return bool
		 */
		private function registerApiShipUser(\ApiShipDelivery $delivery, $login, $password) {
			try {
				$devLogin = ApiShip\iRequestSender::DEV_MODE_LOGIN;
				$devPassword = ApiShip\iRequestSender::DEV_MODE_PASSWORD;
				$isDevMode = true;
				$keepLog = true;

				return $delivery->initRequestSender($devLogin, $devPassword, $isDevMode, $keepLog)
					->register($login, $password);
			} catch (\Exception $e) {
				$this->module->errorNewMessage($e->getMessage());
				$this->module->errorPanic();
			}

			return true;
		}

		/**
		 * Возвращает способ доставки ApiShip по его ид
		 * @param bool|int $deliveryId идентификатор способа доставки
		 * @return \ApiShipDelivery
		 * @throws \publicAdminException
		 */
		private function getApiShipDelivery($deliveryId = false) {
			$deliveryId = ($deliveryId === false) ? $this->admin->getNumberedParameter(0) : $deliveryId;

			try {
				$delivery = \delivery::get($deliveryId);
			} catch (\Exception $e) {
				throw new \publicAdminException($e->getMessage());
			}

			if (!$delivery instanceof \ApiShipDelivery) {
				$exceptionMessage = getLabel('label-api-ship-error-cant-get-delivery-by-id', $this->getModuleName());
				throw new \publicAdminException(
					sprintf($exceptionMessage, $deliveryId)
				);
			}

			return $delivery;
		}

		/**
		 * Возвращает заказ ApiShip по его идентификатору
		 * @param bool|int $orderId идентификатор заказа ApiShip
		 * @return ApiShip\Orders\iEntity
		 * @throws \Exception
		 * @throws \publicAdminException
		 */
		private function getApiShipOrderById($orderId = false) {
			$orderId = ($orderId === false) ? $this->admin->getNumberedParameter(1) : $orderId;

			$orders = $this->getApiShipOrdersCollection()
				->getOrdersByIds([$orderId]);

			if (umiCount($orders) == 0) {
				$exceptionMessage = getLabel('label-api-ship-error-cant-get-order-ref-by-id', $this->getModuleName());
				throw new \publicAdminException(
					sprintf($exceptionMessage, $orderId)
				);
			}

			$order = array_shift($orders);

			if (!$order instanceof ApiShip\Orders\iEntity) {
				$exceptionMessage = getLabel('label-api-ship-error-cant-get-order-ref-by-id', $this->getModuleName());
				throw new \publicAdminException(
					sprintf($exceptionMessage, $orderId)
				);
			}

			return $order;
		}

		/**
		 * Возвращает список статусов заказов в ApiShip с названиями
		 *
		 *    [
		 *        id => name
		 *    ]
		 *
		 * @return array
		 */
		private function getApiShipOrderStatuses() {
			$orderStatuses = new Enums\OrderStatuses();
			return $orderStatuses->getValuesTitles();
		}

		/**
		 * Преобразует название статуса заказа ApiShip в его идентификатор
		 * @param array $queryParamsWithStatusName параметры выборки, содержащие название статусов
		 *
		 * [
		 *        $statusIdFieldName => название статуса заказа
		 * ]
		 *
		 * @param string $statusIdFieldName строковой идентификатор поля статуса заказа
		 * @return array параметры выборки, содержащие идентифифкаторы статусов
		 *
		 * [
		 *        $statusIdFieldName => идентификатор статуса заказа
		 * ]
		 */
		private function decodeApiShipOrdersStatuses(array $queryParamsWithStatusName, $statusIdFieldName) {
			if (!isset($queryParamsWithStatusName[$statusIdFieldName])) {
				return $queryParamsWithStatusName;
			}

			$orderStatusesNames = $this->getApiShipOrderStatuses();
			$orderStatusName = $queryParamsWithStatusName[$statusIdFieldName];
			$orderStatusId = array_search($orderStatusName, $orderStatusesNames);

			$queryParamsWithStatusId = $queryParamsWithStatusName;
			$queryParamsWithStatusId[$statusIdFieldName] = $orderStatusId;

			return $queryParamsWithStatusId;
		}

		/**
		 * Преобразует идентификатор статуса заказа ApiShip в его название
		 * @param array $apiShipOrderDataList список данных заказов с идентификаторами статусов
		 *
		 * [
		 *        'data' => [
		 *            # => [
		 *                $statusIdFieldName => идентификатор статуса заказа
		 *            ]
		 *        ]
		 * ]
		 *
		 * @param string $statusFieldName строковой идентификатор поля статуса заказа
		 * @return array список данных заказов с названиями статусов
		 *
		 * [
		 *        'data' => [
		 *            # => [
		 *                $statusIdFieldName => название статуса заказа
		 *            ]
		 *        ]
		 * ]
		 */
		private function encodeApiShipOrdersStatuses(array $apiShipOrderDataList, $statusFieldName) {
			$orderStatusesNames = $this->getApiShipOrderStatuses();
			$processedApiShipOrderDataList = $apiShipOrderDataList;

			foreach ($processedApiShipOrderDataList as $key => $apiShipOrderData) {
				if (!(is_array($apiShipOrderData) && isset($apiShipOrderData[$statusFieldName]))) {
					continue;
				}

				$orderStatusId = $apiShipOrderData[$statusFieldName];
				$processedApiShipOrderData = $apiShipOrderData;
				$processedApiShipOrderData[$statusFieldName] = $orderStatusesNames[$orderStatusId];
				$processedApiShipOrderDataList[$key] = $processedApiShipOrderData;
			}

			return $processedApiShipOrderDataList;
		}

		/**
		 * Возвращает список тарифов провайдера
		 * @param \ApiShipDelivery $delivery способа доставки ApiShip
		 * @param string $providerKey идентификатор провайдера
		 * @return array
		 * @throws \publicAdminException
		 */
		private function getTariffsByProvider(\ApiShipDelivery $delivery, $providerKey) {
			$providerKey = new Enums\ProvidersKeys($providerKey);
			/** @var ApiShip\iProvider $provider */
			$provider = ApiShip\ProvidersFactory::create($providerKey);

			try {
				$tariffs = $delivery->getRequestSender()
					->getProviderTariffsList($provider->getKey());
			} catch (\Exception $e) {
				throw new \publicAdminException($e->getMessage());
			}

			if (!arrayValueContainsNotEmptyArray($tariffs, self::ROWS_KEY)) {
				$exceptionMessage = getLabel('label-api-ship-provider-tariffs-not-received', $this->getModuleName());
				throw new \publicAdminException(
					sprintf($exceptionMessage, $provider->getKey())
				);
			}

			return $tariffs[self::ROWS_KEY];
		}
	}
