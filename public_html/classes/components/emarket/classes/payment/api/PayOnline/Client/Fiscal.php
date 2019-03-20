<?php

	namespace UmiCms\Classes\Components\Emarket\Payment\PayOnline\Client;

	use Guzzle\Common\Exception\RuntimeException;
	use Guzzle\Http\Message\RequestInterface;
	use UmiCms\Classes\Components\Emarket\Payment\Yandex\Client\Exception\Response\Error as ErrorResponse;
	use UmiCms\Classes\Components\Emarket\Payment\Yandex\Client\Exception\Response\Incorrect as IncorrectResponse;
	use UmiCms\Classes\System\Utils\Api\Http\Json\Client;
	use UmiCms\Utils\Logger\iFactory as LoggerFactory;

	/**
	 * Класс клиента сервиса онлайн-фискализации интернет-платежей PayOnline
	 * @link https://payonline.ru/rel/doc/merchant/servis_onlajn-fiskalizacii_internet-platezhej.pdf
	 * @package UmiCms\Classes\Components\Emarket\Payment\PayOnline\Client
	 */
	class Fiscal extends Client implements iFiscal {

		/** @var string $merchantId идентификатор клиента сервиса */
		private $merchantId;

		/** @var string $privateSecurityKey приватный ключ безопасности сервиса */
		private $privateSecurityKey;

		/** @var \iConfiguration $configuration конфигурация */
		private $configuration;

		/** @var \iUmiLogger $logger логгер */
		private $logger;

		/** @var @var bool $keepLog флаг ведения журнала */
		private $keepLog = true;

		/** @const string SERVICE_HOST адрес сервиса */
		const SERVICE_HOST = 'https://secure.payonlinesystem.com/Services/Fiscal/Request.ashx';

		/** @inheritdoc */
		public function __construct(LoggerFactory $loggerFactory, \iConfiguration $configuration) {
			$this->configuration = $configuration;

			$this->initHttpClient()
				->initLogger($loggerFactory);
		}

		/** @inheritdoc */
		public function setMerchantId($id) {
			$this->merchantId = $id;
			return $this;
		}

		/** @inheritdoc */
		public function setPrivateSecurityKey($key) {
			$this->privateSecurityKey = $key;
			return $this;
		}

		/** @inheritdoc */
		public function setKeepLog($flag = true) {
			$this->keepLog = (bool) $flag;
			return $this;
		}

		/** @inheritdoc */
		public function requestReceipt(\stdClass $request) {
			$request = $this->createPostRequest($request, [], [
				'MerchantId' => $this->getMerchantId(),
				'SecurityKey' => $this->getPublicSecurityKey($request),
			]);

			return $this->getResponse($request);
		}

		/**
		 * Возвращает публичный ключ безопасноити
		 * @param \stdClass $request данные запроса чека
		 * @return string
		 */
		private function getPublicSecurityKey(\stdClass $request) {
			$body = json_encode($request);
			$id = $this->getMerchantId();
			$privateKey = $this->getPrivateSecurityKey();
			$publicKey = sprintf('RequestBody=%s&MerchantId=%s&PrivateSecurityKey=%s', $body, $id, $privateKey);
			return md5($publicKey);
		}

		/** @inheritdoc */
		protected function getServiceUrl() {
			return $this->buildPath([
				self::SERVICE_HOST
			]);
		}

		/** @inheritdoc */
		protected function buildUrl(array $pathParts = [], array $queryParts = []) {
			return $this->getServiceUrl() . $this->buildQuery($queryParts);
		}

		/** @inheritdoc */
		protected function getDefaultHeaders() {
			return [
				'Accept' => 'application/json',
				'Content-Type' => 'application/json'
			];
		}

		/** @inheritdoc */
		protected function getResponse(RequestInterface $request) {
			$response = $request->send();
			$this->log($request);

			try {
				$body = $this->getResponseBody($response);
			} catch (RuntimeException $exception) {
				throw new IncorrectResponse(sprintf('Incorrect response: %s', $response->getBody(true)));
			}

			if (!isset($body['status']['code'], $body['status']['text'])) {
				throw new IncorrectResponse(sprintf('Incorrect response: %s', var_export($body, true)));
			}

			if ($body['status']['text'] !== 'OK') {
				throw new ErrorResponse(sprintf('%s: %s', $body['status']['code'], $body['status']['text']));
			}

			return $body;
		}

		/**
		 * Инициализирует логгер
		 * @param LoggerFactory $loggerFactory фабрика логгеров
		 * @return $this
		 */
		private function initLogger(LoggerFactory $loggerFactory) {
			$sysLogPath = $this->getConfiguration()
				->includeParam('sys-log-path');
			$logPath = sprintf('%s/%s/', rtrim($sysLogPath, '/'), 'PayOnlineFiscalClient');
			$logger = $loggerFactory->create($logPath);
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
		 * Возвращает идентификатор клиента сервиса
		 * @return string
		 */
		private function getMerchantId() {
			return $this->merchantId;
		}

		/**
		 * Возвращает приватный ключ безопасности сервиса
		 * @return string
		 */
		private function getPrivateSecurityKey() {
			return $this->privateSecurityKey;
		}

		/**
		 * Возвращает конфигурация
		 * @return \iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
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
