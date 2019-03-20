<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	use Guzzle\Http\Client;
	use Guzzle\Http\Exception\ClientErrorResponseException;
	use Guzzle\Http\Message\Request;
	use Guzzle\Http\Message\Response;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\ProvidersKeys;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils\ArgumentsValidator;

	/**
	 * Класс запросов к сервису ApiShip
	 * @todo: Задействовать класс UmiCms\Classes\System\Utils\Api\Http\Json\Client
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip
	 */
	class RequestSender implements iRequestSender {

		/** Название журнала запросов */
		const LOG_NAME = 'apiship.log';

		/** @var string $authToken авторизационный токен */
		private $authToken;

		/** @var int $tokenExpireTimestamp время когда авторизационный токен перестанет быть действительным */
		private $tokenExpireTimestamp;

		/** @var string $login логин для авторизации */
		private $login;

		/** @var string $login пароль для авторизации */
		private $password;

		/** @var \Guzzle\Http\Client $httpClient клиент для отправки http запросов */
		private $httpClient;

		/** @var bool $devModeStatus включен ли режим разработки */
		private $devModeStatus = false;

		/** @var bool $keepLogStatus включен ли режим ведения журнала запросов */
		private $keepLogStatus = false;

		/** @var array $lastRequestData данные последнего POST или PUT запроса */
		private $lastRequestData = [];

		/** @inheritdoc */
		public function __construct($login, $password, $devMode = false, $keepLog = false) {
			$this->setDevModeStatus($devMode)
				->setKeepLogStatus($keepLog)
				->setLogin($login)
				->setPassword($password)
				->initHttpClient();
		}

		/** @inheritdoc */
		public function requestAccessToken() {
			$requestData = [
				'login' => $this->getLogin(),
				'password' => $this->getPassword(),
			];

			$this->setLastRequestData($requestData);

			$request = $this->getHttpClient()->post(
				'login',
				$this->getPlatformHeader(),
				$requestData,
				$this->getMuteExceptionOption()
			);

			$result = $this->getResponse($request);

			if (isset($result['code'])) {
				$this->throwErrorResponse($result);
			}

			ArgumentsValidator::arrayContainsValue($result, 'accessToken', __METHOD__, 'accessToken');
			ArgumentsValidator::arrayContainsValue($result, 'expires', __METHOD__, 'expires');

			$date = \DateTime::createFromFormat(\DateTime::W3C, $result['expires']);
			$this->setTokenExpireTimestamp($date->getTimestamp());
			$this->setAuthToken($result['accessToken']);

			return $result;
		}

		/** @inheritdoc */
		public function getDeliveryProvidersList() {
			$request = $this->getHttpClient()->get(
				'lists/providers',
				$this->getDefaultGetHeaders()
			);

			return $this->getResponse($request);
		}

		/** @inheritdoc */
		public function getDeliveryPointsList($pointId = null, $providerKey = null, $cityName = null, $typeId = null) {
			$filter = [];

			if ($pointId !== null) {
				$filter[] = "id=$pointId";
			}

			if ($providerKey !== null) {
				$filter[] = "providerKey=$providerKey";
			}

			if ($cityName !== null) {
				$filter[] = "city=$cityName";
			}

			if ($typeId !== null) {
				$filter[] = "availableOperation=$typeId";
			}

			$args = [
				'query' => [
					'limit' => 1000
				]
			];

			if (umiCount($filter) > 0) {
				$args['query']['filter'] = implode(';', $filter);
			}

			$request = $this->getHttpClient()->get(
				'lists/points',
				$this->getDefaultGetHeaders(),
				$args
			);

			return $this->getResponse($request);
		}

		/** @inheritdoc */
		public function getProviderTariffsList($providerKey) {
			$args = [
				'query' => [
					'filter' => 'providerKey=' . $providerKey,
					'limit' => 100
				]
			];

			$request = $this->getHttpClient()->get(
				'lists/tariffs',
				$this->getDefaultGetHeaders(),
				$args
			);

			return $this->getResponse($request);
		}

		/** @inheritdoc */
		public function calculate(RequestData\iCalculateDeliveryCost $calculateRequest) {
			$requestData = $calculateRequest->export();
			$this->setLastRequestData($requestData);

			$request = $this->getHttpClient()->post(
				'calculator',
				$this->getDefaultPostHeaders(),
				json_encode($requestData)
			);

			return $this->getResponse($request);
		}

		/** @inheritdoc */
		public function order(RequestData\iSendOrder $orderRequest) {
			$requestData = $orderRequest->export();
			$this->setLastRequestData($requestData);

			$request = $this->getHttpClient()->post(
				'orders',
				$this->getDefaultPostHeaders(),
				json_encode($requestData),
				$this->getMuteExceptionOption()
			);

			$response = $this->getResponse($request);

			if (isset($response['code'])) {
				$this->throwErrorResponse($response);
			}

			return $response;
		}

		/** @inheritdoc */
		public function orderModify($apiShipOrderId, RequestData\iSendOrder $orderRequest) {
			$requestData = $orderRequest->export();
			$this->setLastRequestData($requestData);

			$request = $this->getHttpClient()->put(
				'orders/' . $apiShipOrderId,
				$this->getDefaultPostHeaders(),
				json_encode($requestData),
				$this->getMuteExceptionOption()
			);

			$response = $this->getResponse($request);

			if (isset($response['code'])) {
				$this->throwErrorResponse($response);
			}

			return $response;
		}

		/** @inheritdoc */
		public function cancelOrder($orderNumber) {
			$this->validateOrderNumber($orderNumber);

			$request = $this->getHttpClient()->get(
				'orders/' . $orderNumber . '/cancel',
				$this->getDefaultGetHeaders(),
				$this->getMuteExceptionOption()
			);

			return $this->getResponse($request);
		}

		/** @inheritdoc */
		public function getOrdersStatuses(array $ordersNumbers) {
			$requestData = [
				'orderIds' => array_map([$this, 'validateOrderNumber'], $ordersNumbers)
			];

			$this->setLastRequestData($requestData);

			$request = $this->getHttpClient()->post(
				'orders/statuses',
				$this->getDefaultPostHeaders(),
				json_encode($requestData),
				$this->getMuteExceptionOption()
			);

			$response = $this->getResponse($request);

			if (isset($response['code'])) {
				$this->throwErrorResponse($response);
			}

			return $response;
		}

		/** @inheritdoc */
		public function getOrdersLabels(array $ordersNumbers) {
			$requestData = [
				'orderIds' => array_map([$this, 'validateOrderNumber'], $ordersNumbers),
				'format' => 'pdf'
			];

			$this->setLastRequestData($requestData);

			$request = $this->getHttpClient()->post(
				'orders/labels',
				$this->getDefaultPostHeaders(),
				json_encode($requestData)
			);

			return $this->getResponse($request);
		}

		/** @inheritdoc */
		public function getOrdersWaybills(array $ordersNumbers) {
			$requestData = [
				'orderIds' => array_map([$this, 'validateOrderNumber'], $ordersNumbers)
			];

			$this->setLastRequestData($requestData);

			$request = $this->getHttpClient()->post(
				'orders/waybills',
				$this->getDefaultPostHeaders(),
				json_encode($requestData)
			);

			return $this->getResponse($request);
		}

		/** @inheritdoc */
		public function getCurrentUserData() {
			$request = $this->getHttpClient()->get(
				'frontend/users/me',
				$this->getDefaultGetHeaders()
			);

			return $this->getResponse($request);
		}

		/** @inheritdoc */
		public function connectToProvider(RequestData\iConnectProvider $providerConnectionRequest) {
			$requestData = $providerConnectionRequest->export();

			$this->setLastRequestData($requestData);

			$request = $this->getHttpClient()->post(
				'frontend/providers/params',
				$this->getDefaultPostHeaders(),
				json_encode($requestData),
				$this->getMuteExceptionOption()
			);

			return $this->getResponse($request);
		}

		/** @inheritdoc */
		public function updateProviderConnection(
			ProvidersKeys $key,
			RequestData\iConnectProvider $providerConnectionRequest
		) {
			$connectionId = $this->getConnectionIdByProvider($key);
			$requestData = $providerConnectionRequest->export();

			$this->setLastRequestData($requestData);

			$request = $this->getHttpClient()->put(
				'frontend/providers/params/' . $connectionId,
				$this->getDefaultPostHeaders(),
				json_encode($requestData),
				$this->getMuteExceptionOption()
			);

			$result = $this->getResponse($request);

			if (isset($result['code'])) {
				$this->throwErrorResponse($result);
			}

			return $result;
		}

		/**
		 * Возвращает идентификатор подключения провайдера
		 * @param ProvidersKeys $key ключ подключенного провайдера
		 * @return int
		 * @throws \publicAdminException
		 */
		private function getConnectionIdByProvider(ProvidersKeys $key) {
			$connectedProvidersData = $this->getConnectedProviders();
			$errorFormat = getLabel('label-api-ship-error-provider-not-connected', self::I18N_PATH);

			if (!arrayValueContainsNotEmptyArray($connectedProvidersData, 'rows')) {
				throw new \publicAdminException(
					sprintf($errorFormat, (string) $key)
				);
			}

			foreach ($connectedProvidersData['rows'] as $row) {
				if (!is_array($row) || !isset($row['providerKey'], $row['id']) || $row['providerKey'] != (string) $key) {
					continue;
				}

				return (int) $row['id'];
			}

			throw new \publicAdminException(
				sprintf($errorFormat, (string) $key)
			);
		}

		/**
		 * Возвращает данные подключенных провайдеров
		 * @return array|bool|float|int|string
		 */
		private function getConnectedProviders() {
			$request = $this->getHttpClient()->get(
				'frontend/providers/params',
				$this->getDefaultGetHeaders(),
				$this->getMuteExceptionOption()
			);

			return $this->getResponse($request);
		}

		/**
		 * Регистрирует пользователя в ApiShip
		 * @param string $login логин пользователя
		 * @param string $password пароль пользователя
		 * @return bool
		 * @throws \publicAdminException
		 */
		public function register($login, $password) {
			$this->validateUserLogin($login);
			$this->validateUserPassword($password);

			$httpClient = new Client(self::REGISTER_HOST);

			$requestURI = null;
			$requestHeaders = null;
			$requestData = [
				'login' => $login,
				'password' => $password
			];

			$request = $httpClient->post(
				$requestURI, $requestHeaders, $requestData
			);

			try {
				$response = $request->send();
				/** @var \SimpleXMLElement $result */
				$result = $response->xml();
				$resultNodes = $result->xpath('/response');

				if (!(is_array($resultNodes) && umiCount($resultNodes) > 0)) {
					throw new \publicAdminException(
						getLabel('label-api-ship-cant-register-user', self::I18N_PATH)
					);
				}
				/** @var \SimpleXMLElement $resultNode */
				$resultNode = array_shift($resultNodes);
				$resultStatuses = $resultNode->xpath('result');
				$resultStatus = (string) array_shift($resultStatuses);
				$isSuccessStatus = $resultStatus == 'success';
				$isErrorStatus = $resultStatus == 'error';

				if (!$isSuccessStatus && !$isErrorStatus) {
					$exceptionMessage = sprintf(getLabel('label-api-ship-error-unsupported-register-result'), $resultStatus);
					throw new \publicAdminException($exceptionMessage);
				}

				if ($isErrorStatus) {
					$resultMessages = $resultNode->xpath('message');
					$resultMessage = (string) array_shift($resultMessages);
					throw new \publicAdminException($resultMessage);
				}

				return true;
			} catch (\Exception $e) {
				throw new \publicAdminException($e->getMessage());
			}
		}

		/**
		 * Устанавливает логин для подключения к сервису ApiShip
		 * @param string $login логин
		 * @return RequestSender
		 * @throws \publicAdminException
		 */
		private function setLogin($login) {
			if ($this->getDevModeStatus()) {
				$this->login = self::DEV_MODE_LOGIN;
			} else {
				$this->validateUserLogin($login);
				$this->login = $login;
			}
			return $this;
		}

		/**
		 * Устанавливает пароль для подключения к сервису ApiShip
		 * @param string $password пароль
		 * @return RequestSender
		 * @throws \publicAdminException
		 */
		private function setPassword($password) {
			if ($this->getDevModeStatus()) {
				$this->password = self::DEV_MODE_PASSWORD;
			} else {
				$this->validateUserPassword($password);
				$this->password = $password;
			}
			return $this;
		}

		/**
		 * Устанавливает статус режима разработки
		 * @param bool $status вкл/выкл
		 * @return RequestSender
		 */
		private function setDevModeStatus($status) {
			$this->devModeStatus = (bool) $status;
			return $this;
		}

		/**
		 * Возвращает статус режима разработки
		 * @return bool
		 */
		private function getDevModeStatus() {
			return $this->devModeStatus;
		}

		/**
		 * Устанавливает статус режима разработки
		 * @param bool $keepLog вкл/выкл
		 * @return RequestSender
		 */
		private function setKeepLogStatus($keepLog) {
			$this->keepLogStatus = (bool) $keepLog;
			return $this;
		}

		/**
		 * Возвращает статус режима разработки
		 * @return bool
		 */
		private function getKeepLogStatus() {
			return $this->keepLogStatus;
		}

		/**
		 * Возвращает опцию http клиента, которая отключает бросание исключения при получении
		 * ответа на запрос со статусом, отличным от 200 ок
		 * @return array
		 */
		private function getMuteExceptionOption() {
			return [
				'exceptions' => false
			];
		}

		/**
		 * Возвращает авторизационные заголовки для http запроса
		 * @return array
		 */
		private function getAuthHeaders() {
			return [
				'Authorization' => $this->getAuthToken()
			];
		}

		/**
		 * Возвращает заголовок идентификации UMI.CMS
		 * @return array
		 */
		private function getPlatformHeader() {
			return [
				'platform' => 'umi'
			];
		}

		/**
		 * Возвращает заголовки по умолчанию для POST (и PUT) запросов к сервису ApiShip
		 * @return array
		 */
		private function getDefaultPostHeaders() {
			$headers = $this->getPostHeaders();
			$headers += $this->getDefaultGetHeaders();
			return $headers;
		}

		/**
		 * Возвращает заголовки по умолчанию для GET запросов к сервису ApiShip
		 * @return array
		 */
		private function getDefaultGetHeaders() {
			$headers = $this->getAuthHeaders();
			$headers += $this->getPlatformHeader();
			return $headers;
		}

		/**
		 * Возвращает заголовки для POST запроса
		 * @return array
		 */
		private function getPostHeaders() {
			return [
				'Content-Type' => 'application/json',
				'Accept' => 'application/json'
			];
		}

		/**
		 * Отправляет http-запрос
		 * @param Request $request http-запрос
		 * @return array|bool|float|int|string результат http-запроса
		 * @throws \publicAdminException
		 */
		private function getResponse(Request $request) {
			try {
				$response = $request->send();
			} catch (ClientErrorResponseException $mainException) {
				$response = $request->getResponse();
				$result = $this->getResponseBody($response);
				$this->log($request);

				try {
					ArgumentsValidator::arrayContainsValue($result, 'error', __METHOD__, 'error');
					$messageFormat = getLabel('label-api-ship-error-response-error-received', self::I18N_PATH);
					$message = sprintf($messageFormat, $result['error']);
					throw new \wrongParamException($message);
				} catch (\wrongParamException $e) {
					//nothing
				}

				throw $mainException;
			}

			$this->log($request);
			return $this->getResponseBody($response);
		}

		/**
		 * Возвращает содержимое тела ответа
		 * @param Response $response ответ
		 * @return array|bool|float|int|string
		 */
		private function getResponseBody(Response $response) {
			$body = $response->getBody(true);
			return empty($body) ? [] : $response->json();
		}

		/** Инициализирует http клиент */
		private function initHttpClient() {
			$this->httpClient = new Client(
				$this->getServiceUrl()
			);
		}

		/**
		 * Возвращает авторизационный токен
		 * @return string
		 * @throws \publicAdminException
		 */
		private function getAuthToken() {
			if ($this->authToken === null || time() > $this->getTokenExpireTimestamp()) {
				$this->requestAccessToken();
			}

			return $this->authToken;
		}

		/**
		 * Возвращает логин для авторизации
		 * @return string
		 */
		private function getLogin() {
			return $this->login;
		}

		/**
		 * Возвращает пароль для авторизации
		 * @return string
		 */
		private function getPassword() {
			return $this->password;
		}

		/**
		 * Возвращает http клиент
		 * @return Client
		 */
		private function getHttpClient() {
			return $this->httpClient;
		}

		/**
		 * Возвращает адрес сервиса ApiShip
		 * @return string
		 */
		private function getServiceUrl() {
			$host = $this->getDevModeStatus() ? self::DEV_SERVICE_HOST : self::SERVICE_HOST;
			return $host . '/' . self::SERVICE_VERSION . '/';
		}

		/**
		 * Устанавливает авторизационный токен
		 * @param string $authToken авторизационный токен
		 */
		private function setAuthToken($authToken) {
			$this->authToken = $authToken;
		}

		/**
		 * Устанавливает время инвалидации авторизационного токена
		 * @param int $timestamp время в формате unix timestamp
		 */
		private function setTokenExpireTimestamp($timestamp) {
			$this->tokenExpireTimestamp = $timestamp;
		}

		/**
		 * Возвращает время инвалидации авторизационного токена
		 * @return int
		 */
		private function getTokenExpireTimestamp() {
			return $this->tokenExpireTimestamp;
		}

		/**
		 * Формирует сообщение об ошибке на основе данных ответа на запрос к сервису ApiShip и бросает исключение
		 * @param array $result данные ответа на запрос к сервису ApiShip
		 * @throws \publicAdminException
		 */
		private function throwErrorResponse(array $result) {
			$errorMessage = '';
			$code = isset($result['code']) ? $result['code'] : null;
			$title = isset($result['message']) ? $result['message'] : null;
			$moreInfo = isset($result['errors']) ? $result['errors'] : null;

			if ($code !== null) {
				$errorMessage .= $code . ' ';
			}

			if ($title !== null) {
				$errorMessage .= $title . ': ';
			}

			$errorInfo = '';

			if (is_array($moreInfo)) {
				foreach ($moreInfo as $info) {
					if (!isset($info['message'])) {
						continue;
					}

					$errorInfo .= ' ' . $info['message'] . PHP_EOL;
				}
			}

			$errorMessage .= $errorInfo;

			throw new \publicAdminException($errorMessage);
		}

		/**
		 * Валидирует длину строки
		 * @param string $value строка
		 * @param int $minLength минимальная длина
		 * @param int $maxLength максимальная длина
		 * @param string $fieldName имя поля, которое хранит эту строку
		 * @return mixed
		 * @throws \publicAdminException
		 */
		private function validateString($value, $minLength, $maxLength, $fieldName) {
			ArgumentsValidator::stringWithLengthBetween($value, $fieldName, '', $minLength, $maxLength);
			return $value;
		}

		/**
		 * Валидирует логин пользователя для регистрации
		 * @param string $login логин
		 * @return mixed
		 * @throws \publicAdminException
		 */
		private function validateUserLogin($login) {
			return $this->validateString($login, 5, 30, 'login');
		}

		/**
		 * Валидирует пароль пользователя для регистрации
		 * @param string $password пароль
		 * @return mixed
		 * @throws \publicAdminException
		 */
		private function validateUserPassword($password) {
			return $this->validateString($password, 8, 30, 'password');
		}

		/**
		 * Валидирует номер заказа ApiShip
		 * @param int $orderId идентификатор заказа
		 * @return mixed
		 * @throws \publicAdminException
		 */
		private function validateOrderNumber($orderId) {
			ArgumentsValidator::notZeroInteger($orderId, 'orderId', __METHOD__);
			return $orderId;
		}

		/**
		 * Записывает информацию о запросе в лог-файл
		 * @param Request $request http-запрос
		 */
		private function log(Request $request) {
			if (!$this->getKeepLogStatus()) {
				return;
			}

			$logPath = $this->getLogPath();
			$message = $this->prepareLogMessage($request);
			file_put_contents($logPath, $message, FILE_APPEND);
		}

		/**
		 * Возвращает путь до журнала запросов
		 * @return string
		 */
		private function getLogPath() {
			$logPath = \mainConfiguration::getInstance()->includeParam('sys-log-path');
			\umiDirectory::requireFolder($logPath);
			return $logPath . '/' . self::LOG_NAME;
		}

		/**
		 * Возвращает данные последнего POST или PUT запроса
		 * @return array
		 */
		private function getLastRequestData() {
			return $this->lastRequestData;
		}

		/**
		 * Устанавливает данные последнего POST или PUT запроса
		 * @param array $requestData
		 * @return $this
		 */
		private function setLastRequestData(array $requestData = []) {
			$this->lastRequestData = $requestData;
			return $this;
		}

		/**
		 * Формирует сообщение для записи в журнал запросов
		 * @param Request $request http-запрос
		 * @return string
		 */
		private function prepareLogMessage(Request $request) {
			$response = $request->getResponse();
			$responseBody = $this->getResponseBody($response);
			$responseBody = 'Response Body: ' . print_r($responseBody, true);
			$time = strftime('%d/%b/%Y %H:%M:%S');
			$method = $request->getMethod();
			$requestData = '';

			if (($method === 'POST' || $method === 'PUT') && umiCount($this->getLastRequestData()) > 0) {
				$requestData = 'Request Data: ' . print_r($this->getLastRequestData(), true);
				$this->setLastRequestData();
			}

			$url = $request->getUrl();
			$statusCode = $response->getStatusCode();
			$requestHeaders = 'Request Headers: ' . print_r($request->getHeaderLines(), true);
			$separator = str_repeat('-', 80);

			return <<<MESSAGE
[$time] $method $url $statusCode

$requestHeaders
$requestData

$responseBody
$separator


MESSAGE;
		}
	}
