<?php

	use UmiCms\Classes\Components\Emarket\Serializer\iReceipt;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\PayAnyWay as Serializer;
	use UmiCms\Service;

	/**
	 * Способ оплаты через платежную систему "PayAnyWay"
	 * @link https://www.moneta.ru/doc/MONETA.Assistant.ru.pdf
	 * @link https://www.payanyway.ru/info/p/ru/public/merchants/Assistant54FZ.pdf
	 */
	class payanywayPayment extends payment {

		/**
		 * Заказ оплачен. Уведомление об оплате магазину доставлено
		 * @const int ready
		 * @const int delivery
		 */
		const ready = 200;

		const delivery = 200;

		/**
		 * Заказ находится в обработке. Точный статус оплаты заказа определить невозможно.
		 * @const int editing
		 * @const int waiting
		 */
		const editing = 302;

		const waiting = 302;

		/**
		 * Заказ создан и готов к оплате. Уведомление об оплате магазину не доставлено.
		 * @const int payment
		 * @const int accepted
		 */
		const payment = 402;

		const accepted = 402;

		/**
		 * Заказ не является актуальным в магазине (например, заказ отменен).
		 * @const int canceled
		 * @const int rejected
		 */
		const canceled = 500;

		const rejected = 500;

		/**
		 * Адреса для запросов к PayAnyWay
		 * @const string DEV_REQUEST_URL тестовый режим
		 * @const string PROD_REQUEST_URL боевой режим
		 */
		const DEV_REQUEST_URL = 'https://demo.moneta.ru/assistant.htm';

		const PROD_REQUEST_URL = 'https://www.payanyway.ru/assistant.htm';

		/** @inheritdoc */
		public static function getOrderId() {
			return (int) getRequest('MNT_TRANSACTION_ID');
		}

		/**
		 * @inheritdoc
		 * Устанавливает заказу статус оплаты "Инициализирована"
		 */
		public function process($template = null) {
			$order = $this->order;
			$object = $this->object;

			$merchantId = (string) $object->getValue('mnt_id');
			$secretCode = (string) $this->getSecretCode();
			$requestDomain = (string) $object->getValue('mnt_system_url');
			$requestUrl = "https://{$requestDomain}/assistant.htm";

			if ($merchantId === '' || $secretCode === '' || !$this->isValidRequestUrl($requestUrl)) {
				throw new publicException(getLabel('error-payment-wrong-settings'));
			}

			if ($this->isNeedReceiptInfo()) {
				try {
					$serializer = $this->getSerializer();
					$serializer->getContact($order);
					$serializer->getOrderItemInfoList($order);
				} catch (Exception $exception) {
					throw $exception;
				}
			}

			$testMode = (int) $object->getValue('mnt_test_mode');
			$orderId = $order->getId() . '.' . time();
			$currency = $this->getCurrencyCode();
			$amount = $this->formatPrice($order->getActualPrice());

			$signature = $this->getSignature([
				$merchantId,
				$orderId,
				$amount,
				$currency,
				$testMode,
				$secretCode
			]);

			$templateData = [
				'formAction' => $requestUrl,
				'mntId' => $merchantId,
				'mnTransactionId' => $orderId,
				'mntCurrencyCode' => $currency,
				'mntAmount' => $amount,
				'mntTestMode' => $testMode,
				'mntSignature' => $signature,
				'mntSuccessUrl' => (string) $object->getValue('mnt_success_url'),
				'mntFailUrl' => (string) $object->getValue('mnt_fail_url')
			];

			list($templateString) = emarket::loadTemplates(
				'emarket/payment/payanyway/' . $template,
				'form_block'
			);

			$order->order();
			$order->setPaymentStatus('initialized');

			return emarket::parseTemplate($templateString, $templateData);
		}

		/** @inheritdoc */
		public function poll() {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->contentType('text/xml');
			$responseCode = payanywayPayment::canceled;
			$order = $this->order;

			if ($this->checkSignature()) {
				if ($this->isCheckRequest()) {
					$statusCode = $order->getCodeByStatus($order->getOrderStatus());
					$responseCode = constant('payanywayPayment::' . $statusCode);
				} elseif ((float) $order->getActualPrice() === (float) $this->getRequestAmount()) {
					$order->setPaymentStatus('accepted');
					$responseCode = payanywayPayment::ready;
				}
			}

			if ($this->getRequestOperationId()) {
				$order->setPaymentDocumentNumber($this->getRequestOperationId());
				$order->commit();
			}

			$buffer->push($this->getResponse($responseCode));
			$buffer->end();
		}

		/**
		 * Определяет валиден ли адрес запроса
		 * @param string $requestUrl адрес запроса
		 * @return bool
		 */
		protected function isValidRequestUrl($requestUrl) {
			return in_array($requestUrl, [self::DEV_REQUEST_URL, self::PROD_REQUEST_URL]);
		}

		/**
		 * Возвращает секретный код
		 * @return string
		 */
		protected function getSecretCode() {
			return (string) $this->object->getValue('mnt_data_integrity_code');
		}

		/**
		 * Формирует подпись
		 * @param array $pieces части подписи
		 * @return string
		 */
		protected function getSignature(array $pieces) {
			return md5(implode('', $pieces));
		}

		/**
		 * Проверяет подпись заказа из платежной системы
		 * @return bool
		 */
		protected function checkSignature() {
			$signature = $this->getSignature([
				$this->getRequestCommand(),
				$this->getRequestMerchantId(),
				$this->getRequestOrderId(),
				$this->getRequestOperationId(),
				$this->getRequestAmount(),
				$this->getRequestCurrencyCode(),
				$this->getRequestTestMode(),
				$this->getSecretCode()
			]);

			return Service::Protection()->hashEquals($signature, $this->getRequestSignature());
		}

		/**
		 * Возвращает ответ для платежной системы
		 * @param string $resultCode код ответа
		 * @return string
		 */
		protected function getResponse($resultCode) {
			$shopId = $this->getRequestMerchantId();
			$orderId = $this->getRequestOrderId();

			$signature = $this->getSignature([
				$resultCode,
				$shopId,
				$orderId,
				$this->getSecretCode()
			]);

			$header = '<?xml version="1.0" encoding="UTF-8" ?>';
			$receiptInfo = $this->isNeedReceiptInfo() ? $this->getResponseReceiptInfo() : '';
			$result = $header . <<<XML
<MNT_RESPONSE>
	<MNT_ID>$shopId</MNT_ID>
	<MNT_TRANSACTION_ID>$orderId</MNT_TRANSACTION_ID>
	<MNT_RESULT_CODE>$resultCode</MNT_RESULT_CODE>
	<MNT_SIGNATURE>$signature</MNT_SIGNATURE>
	$receiptInfo
</MNT_RESPONSE>
XML;
			return $result;
		}

		/**
		 * Возвращает данные чека для ответа платежной системе
		 * @return string
		 */
		protected function getResponseReceiptInfo() {
			$inventory = json_encode($this->getInventory());
			$email = $this->getCustomerEmail();
			return <<<XML
	<MNT_ATTRIBUTES>
		<ATTRIBUTE>
			<KEY>INVENTORY</KEY>
			<VALUE>$inventory</VALUE>
		</ATTRIBUTE>
		<ATTRIBUTE>
			<KEY>CUSTOMER</KEY>
			<VALUE>$email</VALUE>
		</ATTRIBUTE>
	</MNT_ATTRIBUTES>
XML;
		}

		/**
		 * Возвращает список позиция заказа
		 * @return array
		 */
		protected function getInventory() {
			return $this->getSerializer()
				->getOrderItemInfoList($this->order);
		}

		/**
		 * Возвращает контактные данные покупателя (email)
		 * @return string
		 */
		protected function getCustomerEmail() {
			return $this->getSerializer()
				->getContact($this->order);
		}

		/**
		 * Возвращает сериализатор
		 * @return Serializer|iReceipt
		 */
		protected function getSerializer() {
			return $this->getSerializerReceiptFactory()
				->create('PayAnyWay');
		}

		/** @inheritdoc */
		protected function isNeedReceiptInfo() {
			return !$this->isCheckRequest() && parent::isNeedReceiptInfo();
		}

		/**
		 * Определяет является ли текущий запрос "Проверочным"
		 * @return bool
		 */
		protected function isCheckRequest() {
			return $this->getRequestCommand() == 'CHECK';
		}

		/**
		 * Возвращает команду запроса
		 * @return string
		 */
		protected function getRequestCommand() {
			return (string) getRequest('MNT_COMMAND');
		}

		/**
		 * Возвращает идентификатор магазина (счета) запроса
		 * @return string
		 */
		protected function getRequestMerchantId() {
			return (string) getRequest('MNT_ID');
		}

		/**
		 * Возвращает идентификатор операции запроса
		 * @return string
		 */
		protected function getRequestOperationId() {
			return (string) getRequest('MNT_OPERATION_ID');
		}

		/**
		 * Возвращает стоимость заказа запроса
		 * @return string
		 */
		protected function getRequestAmount() {
			return (string) getRequest('MNT_AMOUNT');
		}

		/**
		 * Возвращает код валюты запроса
		 * @return string
		 */
		protected function getRequestCurrencyCode() {
			return (string) getRequest('MNT_CURRENCY_CODE');
		}

		/**
		 * Возвращает тестовый режим запроса
		 * @return string
		 */
		protected function getRequestTestMode() {
			return (string) getRequest('MNT_TEST_MODE');
		}

		/**
		 * Возвращает подпись запроса
		 * @return string
		 */
		protected function getRequestSignature() {
			return (string) getRequest('MNT_SIGNATURE');
		}

		/**
		 * Возвращает идентификатор заказа запроса
		 * @return string
		 */
		protected function getRequestOrderId() {
			return (string) getRequest('MNT_TRANSACTION_ID');
		}
	}
