<?php

	use UmiCms\Classes\Components\Emarket\Payment\Yandex\Client\iKassa;
	use UmiCms\Classes\Components\Emarket\Serializer\iReceipt;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\YandexKassa4 as Serializer;
	use UmiCms\Service;

	/**
	 * Способ оплаты через платежную систему "Яндекс.Касса" версии 4
	 * @link https://kassa.yandex.ru/docs/guides/
	 * @link https://yandex.ru/support/checkout/payments/api.html
	 * @link https://kassa.yandex.ru/docs/checkout-api/
	 */
	class YandexKassaPayment extends payment {

		/** @inheritdoc */
		public static function getOrderId() {
			$rawRequest = Service::Request()
				->getRawBody();
			$request = json_decode($rawRequest, true);

			if (!is_array($request) || !isset($request['object']['id'])) {
				return false;
			}

			$paymentId = $request['object']['id'];

			//todo: сделать репозиторий заказов и отрефакторить класс order
			$orderQuery = new selector('objects');
			$orderQuery->types('object-type')->guid('emarket-order');
			$orderQuery->where('payment_document_num')->equals($paymentId);
			$orderQuery->option('no-length', true);
			$orderQuery->option('return', 'id');
			$orderQuery->limit(0, 1);
			$orderId = $orderQuery->result();

			if (empty($orderId)) {
				return false;
			}

			return (int) $orderId[0]['id'];
		}

		/**
		 * @inheritdoc
		 * Создает платеж и возвращает адрес, куда необходимо отправиться пользователю.
		 * Устанавливает заказу статус оплаты "Инициализирована".
		 */
		public function process($template = null) {
			$order = $this->order;
			$request = $this->getPaymentData($order);
			$response = $this->getClient()
				->createPayment($request);

			$order->setPaymentDocumentNumber($response['id']);
			$order->order();
			$order->setPaymentStatus('initialized');

			list($templateString) = emarket::loadTemplates(
				'emarket/payment/yandex4/' . $template,
				'form_block'
			);

			$templateData = [
				'url' => $response['confirmation']['confirmation_url']
			];

			return emarket::parseTemplate($templateString, $templateData);
		}

		/**
		 * @inheritdoc
		 * Получает запрос от Яндекс.Касса и валидирует параметры платежа.
		 * В зависимости от результата валидации отправляет запрос на подтверждение или отклонение платежа.
		 * Устанавливает заказу статус оплаты "Проверена" или "Отклонена".
		 */
		public function poll() {
			$rawRequest = Service::Request()
				->getRawBody();
			$request = json_decode($rawRequest, true);

			if (!is_array($request) || !isset($request['object']['amount']['value'])) {
				throw new \expectObjectException(getLabel('error-unexpected-exception'));
			}

			$order = $this->order;
			$paymentId = $order->getPaymentDocumentNumber();
			$client = $this->getClient();

			if ($order->getActualPrice() != $request['object']['amount']['value']) {

				if ($client->cancelPayment($paymentId)) {
					$order->setPaymentStatus('declined');
				}
			} else {

				$request = $this->getPaymentData($order);

				if ($client->approvePayment($paymentId, $request)) {
					$order->setPaymentStatus('accepted');
				}
			}

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->contentType('text/plain');
			$buffer->push('200 OK for Yandex.Kassa');
			$buffer->end();
		}

		/**
		 * Возвращает данные платежа заказа
		 * @param order $order Заказ
		 * @return array
		 */
		protected function getPaymentData(order $order) {
			$serializer = $this->getSerializer();
			$data = $serializer->getPrice($order->getActualPrice());
			$data += $serializer->getConfirmation();

			if ($this->isNeedReceiptInfo()) {
				$data += $serializer->getReceipt($order);
			}

			return $data;
		}

		/**
		 * Возвращает клиента для интеграции
		 * @return iKassa
		 * @throws publicException
		 */
		protected function getClient() {
			$object = $this->object;
			$shopId = (string) $object->getValue('shop_id');
			$secretKey = (string) $object->getValue('secret_key');

			if ($shopId === '' || $secretKey === '') {
				throw new publicException(getLabel('error-payment-wrong-settings'));
			}

			/** @var iKassa $client */
			$client = Service::getNew('YandexKassaClient');
			return $client->setShopId($shopId)
				->setSecretKey($secretKey)
				->setKeepLog($this->isNeedKeepLog());
		}

		/**
		 * Возвращает сериализатор
		 * @return Serializer|iReceipt
		 */
		protected function getSerializer() {
			return $this->getSerializerReceiptFactory()
				->create('YandexKassa4');
		}
	}
