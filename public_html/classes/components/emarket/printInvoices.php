<?php

	use UmiCms\Service;

	/** Класс формирования и вывода платежных документов */
	class EmarketPrintInvoices {

		/** @var emarket $module */
		public $module;

		/**
		 * Формирует платежную квитанцию для оплаты заказа
		 * физическим лицом и выводит ее буффер
		 * @throws publicException
		 * @throws coreException
		 * @throws wrongParamException
		 */
		public function receipt() {
			$orderId = (int) (getRequest('param0') ?: getRequest('order-id'));
			$order = order::get($orderId);

			if (!$orderId || !($order instanceof order)) {
				throw new publicException("Order #{$orderId} doesn't exist");
			}

			$sign = (string) (getRequest('param1') ?: getRequest('signature'));

			/** @var customer $customer */
			$customer = customer::get($order->getCustomerId());

			if (!$customer->isUser()) {
				$securityHash = sha1(
					"{$customer->getId()}:{$customer->getValue('email')}:{$order->getValue('order_date')}"
				);

				if (strcasecmp($sign, $securityHash) !== 0) {
					throw new publicException('Access denied');
				}
			}

			$auth = Service::Auth();
			$userId = $auth->getUserId();

			if ($userId != $customer->id) {
				throw new publicException('Access denied');
			}

			$permissions = permissionsCollection::getInstance();
			$object = umiObjectsCollection::getInstance()->getObject($orderId);

			if ($object->getOwnerId() != $userId && !$permissions->isSv($userId)) {
				throw new publicException('Access denied');
			}

			$uri = "uobject://{$orderId}/?transform=sys-tpls/emarket-receipt.xsl";
			$result = file_get_contents($uri);
			$this->printDoc($result);
		}

		/**
		 * Формирует счет оплаты заказа для юридического лица и выводит его в буффер
		 * @param int|bool $orderId ID заказа с соответствующим способом оплаты
		 * @param string|bool $checkSum контрольная сумма для проверки
		 * @throws publicException
		 * @throws coreException
		 * @throws wrongParamException
		 */
		public function getInvoice($orderId = false, $checkSum = false) {
			if (defined('VIA_HTTP_SCHEME') && VIA_HTTP_SCHEME === true) {
				throw new publicException(getLabel('protocol-execution-not-allowed'));
			}

			if ($orderId === false) {
				$orderId = getRequest('param0');
			}

			if ($checkSum === false) {
				$checkSum = getRequest('param1');
			}

			$config = mainConfiguration::getInstance();
			$rightCheckSum = md5($orderId . $config->get('system', 'salt'));

			if (!Service::Protection()->hashEquals($rightCheckSum, $checkSum)) {
				$this->printDoc(getLabel('no-data-found'));
			}

			$order = order::get($orderId);

			if ($orderId === false || !$order instanceof order) {
				$this->printDoc(getLabel('no-data-found'));
			}

			$paymentId = $order->getValue('payment_id');
			$payment = payment::get($paymentId, $order);

			if (!$payment instanceof invoicePayment) {
				$this->printDoc(getLabel('no-data-found'));
			}

			$result = $payment->printInvoice($order);
			$this->printDoc($result);
		}

		/**
		 * Возвращает ссылку, по которой можно получить счет на оплату заказа для
		 * юридического лица
		 * @param int $orderId ID заказа со счетом
		 * @return string
		 * @throws coreException
		 * @throws publicException
		 */
		public function getInvoiceLink($orderId) {
			$emptyResult = '';
			$order = order::get($orderId);
			if (!$order instanceof order) {
				return $emptyResult;
			}
			$paymentId = $order->getValue('payment_id');
			$payment = payment::get($paymentId, $order);

			if ($payment instanceof invoicePayment) {
				return $payment->getInvoiceLink();
			}

			return $emptyResult;
		}

		/**
		 * Производит очистку буфера и помещает в него строку $data
		 * @param string $data
		 * @throws coreException
		 * @throws wrongParamException
		 */
		protected function printDoc($data) {
			$buffer = Service::Response()
				->getHttpDocBuffer();
			$buffer->charset('utf-8');
			$buffer->contentType('text/html');
			$buffer->clear();
			$buffer->push($data);
			$buffer->end();
		}
	}
