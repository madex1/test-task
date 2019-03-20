<?php

use UmiCms\Service;

/** Способ оплаты через платежную систему "PayPal" */
	class paypalPayment extends payment {

		/** @inheritdoc */
		public static function getOrderId() {
			return (int) getRequest('item_number');
		}

		/**
		 * @inheritdoc
		 * Устанавливает заказу статус оплаты "Инициализирована"
		 */
		public function process($template = null) {
			$this->order->order();

			$currency = $this->getCurrencyCode();
			$amount = $this->formatPrice($this->order->getActualPrice());

			$param = [];
			$param['formAction'] = $this->object->getValue('test_mode')
				? 'https://www.sandbox.paypal.com/cgi-bin/webscr'
				: 'https://www.paypal.com/cgi-bin/webscr';
			$param['paypalemail'] = $this->object->getValue('paypalemail');
			$param['currency'] = $currency;
			$param['order_id'] = $this->order->getId();
			$param['total'] = $amount;
			$param['return_success'] = $this->object->getValue('return_success');
			$param['cancel_return'] = $this->object->getValue('cancel_return');
			$param['notify_url'] = Service::DomainDetector()->detectUrl() . '/emarket/gateway/';

			$this->order->setPaymentStatus('initialized');

			list($templateString) = emarket::loadTemplates(
				'emarket/payment/paypal/' . $template,
				'form_block'
			);

			return emarket::parseTemplate($templateString, $param);
		}

		/** @inheritdoc */
		public function poll() {
			$amount = getRequest('mc_gross');
			$mc_currency = getRequest('mc_currency');
			$invoice = getRequest('item_number');
			$paypalEmail = getRequest('receiver_email');
			$txnType = getRequest('txn_type');
			$paymentStatus = getRequest('payment_status');

			$amount = (float) $amount;
			$orderActualPrice = (float) $this->order->getActualPrice();

			if (!$this->paypalIpn()) {
				return false;
			}

			if ($paypalEmail != $this->object->getValue('paypalemail') || $txnType != 'web_accept') {
				return false;
			}

			$currency = $this->getCurrencyCode();

			if ($mc_currency != $currency) {
				return false;
			}

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->contentType('text/plain');

			if ($orderActualPrice == $amount && $paymentStatus === 'Completed') {
				$this->order->setPaymentStatus('accepted');
				$this->order->setPaymentDocumentNumber($invoice);
				$this->order->commit();
				$buffer->push("OK{$invoice}");
			} else {
				$buffer->push('failed');
			}

			$buffer->end();
		}

		/**
		 * Валидирует обмен данными с сервивом
		 * @return bool
		 */
		protected function paypalIpn() {
			$raw_post_data = Service::Request()->getRawBody();
			$raw_post_array = explode('&', $raw_post_data);
			$myPost = [];

			foreach ($raw_post_array as $keyval) {
				$keyval = explode('=', $keyval);
				if (umiCount($keyval) == 2) {
					$myPost[$keyval[0]] = urldecode($keyval[1]);
				}
			}

			$req = 'cmd=_notify-validate';
			$get_magic_quotes_exists = false;

			if (function_exists('get_magic_quotes_gpc')) {
				$get_magic_quotes_exists = true;
			}

			foreach ($myPost as $key => $value) {
				if ($get_magic_quotes_exists && get_magic_quotes_gpc() == 1) {
					$value = urlencode(stripslashes($value));
				} else {
					$value = urlencode($value);
				}
				$req .= "&$key=$value";
			}

			$paypal_url = $this->object->getValue('test_mode')
				? 'https://www.sandbox.paypal.com/cgi-bin/webscr'
				: 'https://www.paypal.com/cgi-bin/webscr';

			$ch = curl_init($paypal_url);

			if (!$ch) {
				return false;
			}

			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: Close', 'User-Agent: umicms']);

			$res = curl_exec($ch);

			if (curl_errno($ch) != 0) {
				curl_close($ch);
				return false;
			}

			curl_close($ch);

			if (strcmp($res, 'VERIFIED') == 0) {
				return true;
			}

			if (strcmp($res, 'INVALID') == 0) {
				return false;
			}
		}
	}
