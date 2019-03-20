<?php

	namespace UmiCms\Classes\Components\Emarket\Payment\Yandex\Client;

	use Guzzle\Http\Message\RequestInterface;
	use Guzzle\Http\Message\Response;
	use UmiCms\Classes\Components\Emarket\Payment\Yandex\Client\Exception\Response\Error as ErrorResponse;
	use UmiCms\Classes\Components\Emarket\Payment\Yandex\Client\Exception\Response\Incorrect;
	use UmiCms\Classes\Components\Emarket\Payment\Yandex\Client\Exception\Response\Incorrect as IncorrectResponse;
	use UmiCms\Classes\System\Utils\Api\Http\Json\Client;
	use UmiCms\Utils\Logger\iFactory as LoggerFactory;

	/**
	 * Класс клиента API Яндекс.Касса
	 * @link https://kassa.yandex.ru/docs/guides/
	 * @link https://yandex.ru/support/checkout/payments/api.html
	 * @link https://kassa.yandex.ru/docs/checkout-api/
	 * @package UmiCms\Classes\Components\Emarket\Payment\Yandex\Client
	 */
	class Kassa extends Client implements iKassa {

		/** @var string $shopId */
		private $shopId;

		/** @var string $secretKey */
		private $secretKey;

		/** @var string $idempotenceKey ключ идемпотентности */
		private $idempotenceKey;

		/** @var \iConfiguration $configuration конфигурация */
		private $configuration;

		/** @var LoggerFactory $loggerFactory фабрика логгеров */
		private $loggerFactory;

		/** @var \iUmiLogger $logger логгер */
		private $logger;

		/** @var bool $keepLog флаг ведения журнала */
		private $keepLog = true;

		/** @const string SERVICE_HOST адрес сервиса */
		const SERVICE_HOST = 'https://payment.yandex.net/api/';

		/** @const string SERVICE_VERSION версия API */
		const SERVICE_VERSION = 'v3';

		/**
		 * @const int DEFAULT_DELAY значение по умолчанию времени ожидания между запросами при отправке повторного
		 * запроса в случае получения ответа с HTTP статусом 202 в милисекундах
		 */
		const DEFAULT_DELAY = 1800;

		/** @const int ATTEMPTS_COUNT количество повторных запросов при ответе API со статусом 202 */
		const ATTEMPTS_COUNT = 3;

		/** @inheritdoc */
		public function __construct(LoggerFactory $loggerFactory, \iConfiguration $configuration) {
			$this->loggerFactory = $loggerFactory;
			$this->configuration = $configuration;

			$this->initHttpClient()
				->initLogger()
				->setIdempotenceKey();
		}

		/** @inheritdoc */
		public function setShopId($shopId) {
			$this->shopId = $shopId;
			return $this;
		}

		/** @inheritdoc */
		public function setSecretKey($secretKey) {
			$this->secretKey = $secretKey;
			return $this;
		}

		/** @inheritdoc */
		public function setKeepLog($flag = true) {
			$this->keepLog = (bool) $flag;
			return $this;
		}

		/** @inheritdoc */
		public function createPayment(array $request) {

			$request = $this->createPostRequest($request);
			$response = $this->getResponse($request);

			if (!isset($response['confirmation']['confirmation_url'], $response['id'])) {
				throw new IncorrectResponse('Confirmation url or id not received');
			}

			return $response;
		}

		/** @inheritdoc */
		public function approvePayment($id, array $request) {

			$request = $this->createPostRequest($request, [$id, 'capture']);
			$response = $this->getResponse($request);

			if (!isset($response['status'])) {
				throw new IncorrectResponse('Status not received');
			}

			return $response['status'] === 'succeeded';
		}

		/** @inheritdoc */
		public function cancelPayment($id) {
			$request = $this->createPostRequest([], [$id, 'cancel']);
			$response = $this->getResponse($request);

			if (!isset($response['status'])) {
				throw new IncorrectResponse('Status not received');
			}

			return $response['status'] === 'canceled';
		}

		/** @inheritdoc */
		protected function getServiceUrl() {
			return $this->buildPath([
				self::SERVICE_HOST,
				self::SERVICE_VERSION
			]);
		}

		/** @inheritdoc */
		protected function getPrefix() {
			return $this->buildPath(
				['payments']
			);
		}

		/** @inheritdoc */
		protected function getResponse(RequestInterface $request) {
			$request->getCurlOptions()
				->set(CURLOPT_USERPWD, sprintf('%s:%s', $this->getShopId(), $this->getSecretKey()));
			$response = $request->send();
			$this->log($request);

			$attempts = self::ATTEMPTS_COUNT;

			while ($response->getStatusCode() === 202 && $attempts > 0) {
				$this->delay($response);
				$attempts--;
				$response = $request->send();
				$this->log($request);
			}

			$body = $this->getResponseBody($response);

			if (isset($body['type']) && $body['type'] === 'error') {
				throw new ErrorResponse(sprintf('%s : %s', $body['code'], $body['description']));
			}

			return $body;
		}

		/**
		 * Возвращает список заголовков http запроса
		 * @return array
		 */
		protected function getDefaultHeaders() {
			$headerList = parent::getDefaultHeaders();
			return array_merge($headerList, [
				'Idempotence-Key' => $this->getIdempotenceKey()
			]);
		}

		/**
		 * Осуществляет задержку между повторными запросам
		 * @param Response $response ответ на запрос
		 */
		private function delay(Response $response) {
			$body = $this->getResponseBody($response);
			$delay = isset($body['retry_after']) ? $body['retry_after'] : self::DEFAULT_DELAY;
			usleep($delay * 1000);
		}

		/**
		 * Инициализирует логгер
		 * @return $this
		 */
		private function initLogger() {
			$sysLogPath = $this->getConfiguration()
				->includeParam('sys-log-path');
			$kassaLogPath = sprintf('%s/%s/', rtrim($sysLogPath, '/'), 'YandexKassaClient');

			$logger = $this->getLoggerFactory()
				->create($kassaLogPath);

			return $this->setLogger($logger);
		}

		/**
		 * Записывает информацию о запросе в лог-файл
		 * @param RequestInterface $request http-запрос
		 * @return $this
		 */
		private function log(RequestInterface $request) {
			if (!$this->getKeepLog()) {
				return $this;
			}

			$message = $this->prepareLogMessage($request);

			$logger = $this->getLogger();
			$logger->push($message);
			$logger->save();

			return $this;
		}

		/**
		 * Возвращает идентификатор магазина
		 * @return string
		 */
		private function getShopId() {
			return $this->shopId;
		}

		/**
		 * Возвращает секретный ключ
		 * @return string
		 */
		private function getSecretKey() {
			return $this->secretKey;
		}

		/**
		 * Устанавливает ключ идемпотентности
		 * @param string|null $key ключ
		 * @return $this
		 */
		private function setIdempotenceKey($key = null) {
			$key = $key ?: uniqid('', true);
			$this->idempotenceKey = $key;
			return $this;
		}

		/**
		 * Возвращает ключ идемпотентности
		 * @return string
		 */
		private function getIdempotenceKey() {
			return $this->idempotenceKey;
		}

		/**
		 * Возвращает конфигурация
		 * @return \iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
		}

		/**
		 * Возвращает фабрику логгеров
		 * @return LoggerFactory
		 */
		private function getLoggerFactory() {
			return $this->loggerFactory;
		}

		/**
		 * Возвращает логгер
		 * @return \iUmiLogger
		 */
		private function getLogger() {
			return $this->logger;
		}

		/**
		 * Устанавливает логгер
		 * @param \iUmiLogger $logger логгер
		 * @return $this
		 */
		private function setLogger(\iUmiLogger $logger) {
			$this->logger = $logger;
			return $this;
		}

		/**
		 * Возвращает значение флага ведения журнала
		 * @return bool
		 */
		private function getKeepLog() {
			return $this->keepLog;
		}
	}
