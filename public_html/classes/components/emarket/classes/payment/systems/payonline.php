<?php

	use UmiCms\Classes\Components\Emarket\Payment\PayOnline\Client\iFiscal;
	use UmiCms\Classes\Components\Emarket\Serializer\iReceipt;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\PayOnline;
	use UmiCms\Service;

	/**
	 * Способ оплаты через платежную систему "PayOnline"
	 * @link https://payonline.ru/rel/doc/merchant/Instrukciya_po_technicheskoy_integracii_s_PayOnline.pdf
	 * @link https://payonline.ru/rel/doc/merchant/Spravochnik_API_PayOnline.pdf
	 */
	class payonlinePayment extends payment {

		/** @const string REQUEST_URL адрес запросов к PayOnline */
		const REQUEST_URL = 'https://secure.payonlinesystem.com/ru/payment/';

		/** @inheritdoc */
		public static function getOrderId() {
			return (int) Service::Request()
				->Get()
				->get('OrderId');
		}

		/** @inheritdoc Устанавливает заказу статус оплаты "Инициализирована" */
		public function process($template = null) {
			$order = $this->order;

			try {
				$this->getClient();

				if ($this->isNeedReceiptInfo()) {
					$this->getSerializer()->getReceipt($order);
				}
			} catch (Exception $exception) {
				throw $exception;
			}

			$signature = $this->getSignature([
				'MerchantId' => $this->getMerchantId(),
				'OrderId' => $order->getId(),
				'Amount' => $this->formatPrice($order->getActualPrice()),
				'Currency' => $this->getCurrencyCode(),
				'PrivateSecurityKey' => $this->getPrivateKey(),
			]);

			$templateData = [
				'formAction' => $this->getFormAction($signature),
				'MerchantId' => $this->getMerchantId(),
				'OrderId' => $order->getId(),
				'Amount' => $this->formatPrice($order->getActualPrice()),
				'Currency' => $this->getCurrencyCode(),
				'SecurityKey' => $signature,
				'ReturnUrl' => $this->getSuccessUrl(),
				'FailUrl' => $this->getFailUrl()
			];

			list($templateString) = emarket::loadTemplates(
				'emarket/payment/payonline/' . $template,
				'form_block'
			);

			$order->order();
			$order->setPaymentStatus('initialized');

			return emarket::parseTemplate($templateString, $templateData);
		}

		/** @inheritdoc */
		public function poll() {
			$order = $this->order;
			$status = $this->getStatus();
			$order->setPaymentStatus($status);
			$order->setPaymentDocumentNumber($this->getRequestTransactionId());
			$order->commit();

			$buffer = Service::Response()
				->getCurrentBuffer();

			if ($status != 'accepted') {
				$buffer->redirect(parent::getFailUrl());
			}

			if ($this->isNeedReceiptInfo()) {
				$receipt = $this->getSerializer()
					->getReceipt($order, $this->getRequestProvider());
				$this->getClient()
					->requestReceipt($receipt);
			}

			$buffer->redirect(parent::getSuccessUrl());
		}

		/**
		 * Возвращает статус оплаты заказа, который необходимо установить по результатам запроса сервиса
		 * @return string
		 */
		protected function getStatus() {
			return $this->isCorrectRequest() ? 'accepted' : 'declined';
		}

		/**
		 * Определяет корректен ли запрос
		 * @return bool
		 */
		protected function isCorrectRequest() {
			$signature = $this->getSignature([
				'DateTime' => $this->getRequestDateTime(),
				'TransactionID' => $this->getRequestTransactionId(),
				'OrderId' => self::getOrderId(),
				'Amount' => $this->getRequestAmount(),
				'Currency' => $this->getRequestCurrency(),
				'PrivateSecurityKey' => $this->getPrivateKey(),
			]);

			$signatureMatch = Service::Protection()->hashEquals($signature, $this->getRequestSignature());
			$amountMatch = (float) $this->order->getActualPrice() == (float) $this->getRequestAmount();
			$emptyErrorCode = empty($this->getRequestErrorCode());

			return ($signatureMatch && $amountMatch && $emptyErrorCode);
		}

		/**
		 * Возвращает дату платежа запроса
		 * @return string
		 */
		protected function getRequestDateTime() {
			return $this->getRequest('DateTime');
		}

		/**
		 * Возвращает идентификатор транзакции запроса
		 * @return string
		 */
		protected function getRequestTransactionId() {
			return $this->getRequest('TransactionID');
		}

		/**
		 * Возвращает сумму транзакции запроса
		 * @return string
		 */
		protected function getRequestAmount() {
			return $this->getRequest('Amount');
		}

		/**
		 * Возвращает валюту транзакции запроса
		 * @return string
		 */
		protected function getRequestCurrency() {
			return $this->getRequest('Currency');
		}

		/**
		 * Возвращает подпись запроса
		 * @return string
		 */
		protected function getRequestSignature() {
			return $this->getRequest('SecurityKey');
		}

		/**
		 * Возвращает код ошибки запроса
		 * @return string
		 */
		protected function getRequestErrorCode() {
			return $this->getRequest('ErrorCode');
		}

		/**
		 * Возвращает тип платежной системы
		 * @return string
		 */
		protected function getRequestProvider() {
			return $this->getRequest('Provider');
		}

		/**
		 * Возвращает значение параметра запроса
		 * @param string $name имя параметра
		 * @return string
		 */
		protected function getRequest($name) {
			return (string) Service::Request()
				->Get()
				->get($name);
		}

		/**
		 * Возвращает идентификатор в сервисе
		 * @return string
		 */
		protected function getMerchantId() {
			return (string) $this->object->getValue('merchant_id');
		}

		/**
		 * Возвращает приватный ключ безопасности сервиса
		 * @return string
		 */
		protected function getPrivateKey() {
			return (string) $this->object->getValue('private_key');
		}

		/**
		 * Возвращает адрес запроса к сервису
		 * @param string $signature подпись запроса
		 * @return string
		 */
		protected function getFormAction($signature) {
			$order = $this->order;
			$query = http_build_query([
				'MerchantId' => $this->getMerchantId(),
				'OrderId' => $order->getId(),
				'Amount' => $this->formatPrice($order->getActualPrice()),
				'Currency' => $this->getCurrencyCode(),
				'SecurityKey' => $signature,
			]);

			return sprintf('%s?%s', self::REQUEST_URL, $query);
		}

		/**
		 * Формирует подпись
		 * @param array $pieces части подписи
		 * @return string
		 */
		protected function getSignature(array $pieces) {
			$parts = [];

			foreach ($pieces as $key => $value) {
				$parts[] = sprintf('%s=%s', $key, $value);
			}

			return md5(implode('&', $parts));
		}

		/** @inheritdoc */
		protected function getCurrencyCode() {
			$code = parent::getCurrencyCode();
			return in_array($code, ['RUB', 'EUR', 'USD']) ? $code : 'RUB';
		}

		/** @inheritdoc */
		protected function getSuccessUrl() {
			return urlencode(parent::getSuccessUrl());
		}

		/** @inheritdoc */
		protected function getFailUrl() {
			return urlencode(parent::getFailUrl());
		}

		/**
		 * Возвращает клиента для интеграции с api печати чеков
		 * @return iFiscal
		 * @throws publicException
		 */
		protected function getClient() {
			$merchantId = (string) $this->getMerchantId();
			$privateSecretKey = (string) $this->getPrivateKey();

			if ($merchantId === '' || $privateSecretKey === '') {
				throw new publicException(getLabel('error-payment-wrong-settings'));
			}

			/** @var iFiscal $client */
			$client = Service::getNew('PayOnlineFiscalClient');
			return $client->setMerchantId($merchantId)
				->setPrivateSecurityKey($privateSecretKey)
				->setKeepLog($this->isNeedKeepLog());
		}

		/**
		 * Возвращает сериализатор
		 * @return PayOnline|iReceipt
		 */
		protected function getSerializer() {
			return $this->getSerializerReceiptFactory()
				->create('PayOnline');
		}
	}
