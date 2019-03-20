<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\ProvidersKeys;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData;

	/**
	 * Интерфейс запросов к сервису ApiShip
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip
	 */
	interface iRequestSender {

		/** @const string SERVICE_HOST "боевой" адрес сервиса */
		const SERVICE_HOST = 'https://api.apiship.ru';

		/** @const string DEV_SERVICE_HOST адрес сервиса для разработки */
		const DEV_SERVICE_HOST = 'http://api.dev.apiship.ru';

		/** @const string REGISTER_HOST адрес регистрации */
		const REGISTER_HOST = 'http://updates.umi-cms.ru/updateserver/?type=api-ship-register';

		/** @const string SERVICE_VERSION обозначение версии сервиса */
		const SERVICE_VERSION = 'v1';

		/** @const string DEV_MODE_LOGIN логин тестового пользователя */
		const DEV_MODE_LOGIN = 'test';

		/** @const string DEV_MODE_PASSWORD пароль тестового пользователя */
		const DEV_MODE_PASSWORD = 'test';

		/** @var string I18N_PATH группа используемых языковый меток */
		const I18N_PATH = 'emarket';

		/**
		 * Конструктор
		 * @param string $login логин подключения
		 * @param string $password логин подключения
		 * @param bool $devMode включен ли режим отладки
		 * @param bool $keepLog включен ли режим ведения журнала запросов
		 */
		public function __construct($login, $password, $devMode = false, $keepLog = false);

		/**
		 * Запрашивает новый авторизационный токен
		 * @throws \publicAdminException
		 */
		public function requestAccessToken();

		/**
		 * Возвращает список интегрированных служб доставки
		 * @return array|bool|float|int|string
		 * @throws \publicAdminException
		 */
		public function getDeliveryProvidersList();

		/**
		 * Возвращает пункты приема/выдачи по заданным параметрам
		 * @param int $pointId идентификатор пункта выдачи
		 * @param string $providerKey идентификатор провайдера, которому принадлежат пункты выдачи
		 * @param string $cityName город, где находятся пункты выдачи
		 * @param int $typeId идентификатор типа пункта выдачи
		 * @return array|bool|float|int|string
		 * @throws \publicAdminException
		 */
		public function getDeliveryPointsList($pointId = null, $providerKey = null, $cityName = null, $typeId = null);

		/**
		 * Возвращает список актуальных тарифов заданного провайдера
		 * @param string $providerKey идентификатор провадера
		 * @return array|bool|float|int|string
		 * @throws \publicAdminException
		 */
		public function getProviderTariffsList($providerKey);

		/**
		 * Расчитывает стоимость доставки и возвращает варианты
		 * @param RequestData\iCalculateDeliveryCost $calculateRequest
		 * @return array|bool|float|int|string
		 * @throws \publicAdminException
		 */
		public function calculate(RequestData\iCalculateDeliveryCost $calculateRequest);

		/**
		 * Создает заказ в ApiShip
		 * @param RequestData\iSendOrder $orderRequest
		 * @return array|bool|float|int|string
		 * @throws \publicAdminException
		 */
		public function order(RequestData\iSendOrder $orderRequest);

		/**
		 * Обновляет заказ в ApiShip
		 * @param int $apiShipOrderId идентификатор заказа ApiShip
		 * @param RequestData\iSendOrder $orderRequest
		 * @return array|bool|float|int|string
		 * @throws \publicAdminException
		 */
		public function orderModify($apiShipOrderId, RequestData\iSendOrder $orderRequest);

		/**
		 * Отменяет заказ ApiShip
		 * @param int $orderNumber номер заказа
		 * @return array|bool|float|int|string
		 * @throws \publicAdminException
		 */
		public function cancelOrder($orderNumber);

		/**
		 * Возвращает статусы по нескольким заказам
		 * @param array $ordersNumbers номера заказов
		 * @return array|bool|float|int|string
		 * @throws \publicAdminException
		 */
		public function getOrdersStatuses(array $ordersNumbers);

		/**
		 * Возвращает ссылки на ярлыки для заказов
		 * @param array $ordersNumbers номера заказов
		 * @return array|bool|float|int|string
		 * @throws \publicAdminException
		 */
		public function getOrdersLabels(array $ordersNumbers);

		/**
		 * Возвращает ссылки на акты приема-передачи заказов
		 * @param array $ordersNumbers номера заказов
		 * @return array|bool|float|int|string
		 * @throws \publicAdminException
		 */
		public function getOrdersWaybills(array $ordersNumbers);

		/**
		 * Возвращает данные текущего авторизованного пользователя
		 * @return array|bool|float|int|string
		 * @throws \publicAdminException
		 */
		public function getCurrentUserData();

		/**
		 * Устанавливает подключение к службе доставки
		 * @param RequestData\iConnectProvider $createProviderConnectionRequest
		 * @return array|bool|float|int|string
		 * @throws \publicAdminException
		 */
		public function connectToProvider(RequestData\iConnectProvider $createProviderConnectionRequest);

		/**
		 * Обновляет подключение к службе доставки
		 * @param ProvidersKeys $key ключ подключенного провайдера
		 * @param RequestData\iConnectProvider $providerConnectionRequest
		 * @throws \publicAdminException
		 */
		public function updateProviderConnection(
			ProvidersKeys $key,
			RequestData\iConnectProvider $providerConnectionRequest
		);

		/**
		 * Создает новую учетную запись пользователя в системе
		 * @param string $login логин пользователя
		 * @param string $password пароль пользователя
		 * @return bool
		 * @throws \publicAdminException
		 */
		public function register($login, $password);
	}
