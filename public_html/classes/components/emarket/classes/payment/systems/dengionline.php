<?php

	use UmiCms\Service;

	/** Способ оплаты через платежную систему "Деньги Online" */
	class dengionlinePayment extends payment {

		/** @inheritdoc */
		public static function getOrderId() {
			$orderId = (int) getRequest('orderid');

			if (!$orderId && getRequest('userid')) {
				$orderId = (int) getRequest('userid');
			}

			return $orderId;
		}

		/**
		 * @inheritdoc
		 * Устанавливает заказу статус оплаты "Инициализирована"
		 */
		public function process($template = null) {
			$currency = $this->getCurrencyCode();

			list($templateString, $modeItem) = emarket::loadTemplates(
				'emarket/payment/dengionline/' . $template,
				'form_block',
				'mode_type_item'
			);

			$modeTypeItems = [];

			$xml = '<request>
				<action>get_project_paymodes</action>
				<projectId>' . $this->object->project . '</projectId>
			</request>';

			$headers = [
				'Content-type' => 'application/x-www-form-urlencoded'
			];

			$paymentsXML = umiRemoteFileGetter::get('http://www.onlinedengi.ru/dev/xmltalk.php', false, $headers, [
				'xml' => $xml
			]);

			/** @var DOMDocument $dom */
			secure_load_dom_document($paymentsXML, $dom);

			if ($dom->getElementsByTagName('paymentMode')->length) {
				/** @var DOMElement $payment */
				foreach ($dom->getElementsByTagName('paymentMode') as $payment) {
					$modeTypeItems[] = emarket::parseTemplate($modeItem, [
						'id' => $payment->getAttribute('id'),
						'label' => $payment->getAttribute('title'),
						'banner' => $payment->getAttribute('banner')
					]);
				}
			}

			$order = $this->order;
			$order->order();

			$orderId = $order->getId();

			$param = [];
			$param['formAction'] = 'http://www.onlinedengi.ru/wmpaycheck.php?priznak=UMI';
			$param['project'] = $this->object->project;
			$param['amount'] = $order->getActualPrice();
			$param['nickname'] = $orderId;
			$param['order_id'] = $orderId;
			$param['source'] = $this->object->source;
			$param['comment'] = 'Payment for order ' . $orderId;
			$param['paymentCurrency'] = $currency;
			$param['subnodes:items'] = $param['void:mode_type_list'] = $modeTypeItems;

			$order->setPaymentStatus('initialized');

			return emarket::parseTemplate($templateString, $param);
		}

		/**
		 * @inheritdoc
		 * Потверждает валидность заказа в платежной системе.
		 * Записывает в заказ в UMI.CMS номер платежного документа
		 * и меняет его статус оплаты, в зависимости от результата валидации:
		 *
		 * "Принята"/"Отклонена"
		 */
		public function poll() {
			$amount = getRequest('amount');
			$userId = (int) getRequest('userid');
			$paymentId = (int) getRequest('paymentid');
			$orderId = (int) getRequest('orderid');
			$key = (string) getRequest('key');

			$success = false;

			if (!$orderId && $userId) {
				$checkSign = md5('0' . $userId . '0' . $this->object->key);

				if (Service::Protection()->hashEquals($checkSign, $key)) {
					$success = true;
				}
			} elseif ($orderId && $paymentId) {
				$checkSign = md5($amount . $userId . $paymentId . $this->object->key);

				if (Service::Protection()->hashEquals($checkSign, $key) &&
					($this->order->getActualPrice() - $amount) < 0.001) {
					$this->order->setPaymentStatus('accepted');
					$this->order->setPaymentDocumentNumber($paymentId);
					$this->order->commit();
					$success = true;
				}
			}

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->contentType('text/xml');

			if ($success) {
				$buffer->push('<?xml version="1.0" encoding="UTF-8"?>
								<result>
									<id>' . $orderId . '</id>
									<code>YES</code>
								</result>');
			} else {
				$this->order->setPaymentStatus('declined');
				$buffer->push('<?xml version="1.0" encoding="UTF-8"?>
								<result>
									<id>' . $orderId . '</id>
									<code>NO</code>
								</result>');
			}

			$buffer->end();
		}
	}
