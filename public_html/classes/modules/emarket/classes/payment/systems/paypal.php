<?php
	class paypalPayment extends payment {
		public function validate() { return true; }

		public static function getOrderId() {
			return (int) getRequest('shp_orderId');
		}

		public function process($template = null) {
			$this->order->order();

			$currency = strtoupper( mainConfiguration::getInstance()->get('system', 'default-currency') );
			if ($currency == 'RUR'){
				$currency = 'RUB';
			}

			$amount = number_format($this->order->getActualPrice(), 2, '.', '');

			$param = array();
			$param['formAction']	 = $this->object->test_mode ? "https://www.sandbox.paypal.com/cgi-bin/webscr" : "https://www.paypal.com/cgi-bin/webscr";
			$param['paypalemail']	 = $this->object->paypalemail; // e-mail продавца
			$param['currency']		 = $currency; // валюта
			$param['order_id']		 = $this->order->getId(); // идентификатор заказа
			$param['total']			 = $amount;
			$param['return_success'] = $this->object->return_success;
			$param['cancel_return']	 = $this->object->cancel_return;
			$httpScheme = 'http';
			if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) {
				$httpScheme = 'https';
			}
			$param['notify_url']	 = $httpScheme . '://' . $_SERVER['SERVER_NAME'] . '/emarket/gateway/';

			$this->order->setPaymentStatus('initialized');
			list($templateString) = def_module::loadTemplates("emarket/payment/paypal/".$template, "form_block");
			return def_module::parseTemplate($templateString, $param);
		}

		public function poll() {
			$amount			= getRequest("mc_gross");
			$mc_currency	= getRequest("mc_currency");
			$invoice		= getRequest("item_number");
			$paypalEmail	= getRequest("receiver_email");
			$txnType		= getRequest("txn_type");
			$paymentStatus	= getRequest("payment_status");

			$amount = (float) $amount;
			$orderActualPrice = (float) $this->order->getActualPrice();

			if (!$this->paypalIpn()) {
				return false;
			}

			if ($paypalEmail != $this->object->paypalemail || $txnType != "web_accept") {
				return false;
			}

			$currency = strtoupper( mainConfiguration::getInstance()->get('system', 'default-currency') );
			if ($currency == 'RUR'){
				$currency = 'RUB';
			}

			if ($mc_currency != $currency) {
				return false;
			}

			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->contentType("text/plain");
			if ($orderActualPrice == $amount && $paymentStatus === 'Completed') {
				$this->order->setPaymentStatus("accepted");
				$this->order->payment_document_num = $invoice;
				$buffer->push("OK{$invoice}");
			} else {
				$buffer->push("failed");
			}
			$buffer->end();
		}

		private function paypalIpn() {
			$raw_post_data = file_get_contents('php://input');
			$raw_post_array = explode('&', $raw_post_data);
			$myPost = array();
			foreach ($raw_post_array as $keyval) {
				$keyval = explode ('=', $keyval);
				if (count($keyval) == 2) {
					$myPost[$keyval[0]] = urldecode($keyval[1]);
				}
			}

			$req = 'cmd=_notify-validate';
			if (function_exists('get_magic_quotes_gpc')) {
				$get_magic_quotes_exists = true;
			}
			foreach ($myPost as $key => $value) {
				if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
					$value = urlencode(stripslashes($value));
				} else {
					$value = urlencode($value);
				}
				$req .= "&$key=$value";
			}

			$paypal_url = $this->object->test_mode ? "https://www.sandbox.paypal.com/cgi-bin/webscr" : "https://www.paypal.com/cgi-bin/webscr";

			$ch = curl_init($paypal_url);
			if ($ch == FALSE) {
				return FALSE;
			}

			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);

			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close', 'User-Agent: umicms'));

			$res = curl_exec($ch);
			if (curl_errno($ch) != 0) {
				curl_close($ch);
				return false;
			} else {
				curl_close($ch);
			}

			if (strcmp ($res, "VERIFIED") == 0) {
				return true;
			} else if (strcmp ($res, "INVALID") == 0) {
				return false;
			}
		}
	};
?>