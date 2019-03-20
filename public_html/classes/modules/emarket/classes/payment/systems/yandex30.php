<?php
	class yandex30Payment extends payment {
		/** Статусные коды Яндекс.Денег (см. документацию Яндекс.Денег) */
		const STATUS_SUCCESS              = 0;
		const STATUS_AUTHERROR            = 1;
		const STATUS_SUCCESS_WITH_CHANGES = 2;
		const STATUS_DECLINE              = 100;
		const STATUS_REQUESTERROR         = 200;
		const STATUS_INTERNALERROR        = 1000;

		public function validate() { return true; }

		public function process($template = null) {
			$this->order->order();
			$shopId = $this->object->shop_id;
			$bankId = $this->object->bank_id;
			$scid   = $this->object->scid;
			if(!strlen($shopId) || !strlen($scid)) {
				throw new publicException(getLabel('error-payment-wrong-settings'));
			}
			$productPrice = (float) $this->order->getActualPrice();

			list($templateString, $modeItem) = def_module::loadTemplates("emarket/payment/yandex30/" . $template, 'form_block', 'mode_type_item');

			$modeTypeItems = array();
			foreach($this->getAvailablePaymentTypes() as $payment) {
				$modeTypeItems[] = def_module::parseTemplate($modeItem, $payment);
			}

			$param = array();
			$param['shopId']	= $shopId;
			$param['Sum']		= $productPrice;
			$param['BankId']	= $bankId;
			$param['scid']		= $scid;
			$param['CustomerNumber']	= $this->order->getId();
			$param['formAction']		= $this->object->demo_mode ? 'https://demomoney.yandex.ru/eshop.xml' : 'https://money.yandex.ru/eshop.xml';
			$param['orderId']			= $this->order->getId();
			$param['subnodes:items']	= $param['void:mode_type_list'] = $modeTypeItems;

			$this->order->setPaymentStatus('initialized');
			return def_module::parseTemplate($templateString, $param);
		}

		public function getAvailablePaymentTypes() {
			$payments = array();
			if ($this->object->yandex_pc) {
				$payment = array('id' => 0, 'type' => 'PC', 'subtype' => '', 'label' => getLabel('label-yandex-payment-pc'));
				array_push($payments, $payment);
			}
			if ($this->object->yandex_ac) {
				$payment = array('id' => 1, 'type' => 'AC', 'subtype' => '', 'label' => getLabel('label-yandex-payment-ac'));
				array_push($payments, $payment);
			}
			if ($this->object->yandex_mc) {
				$payment = array('id' => 2, 'type' => 'MC', 'subtype' => '', 'label' => getLabel('label-yandex-payment-mc'));
				array_push($payments, $payment);
			}
			if ($this->object->yandex_gp_svzny) {
				$payment = array('id' => 3, 'type' => 'GP', 'subtype' => 'SVZNY', 'label' => getLabel('label-yandex-payment-gp-svzny'));
				array_push($payments, $payment);
			}
			if ($this->object->yandex_gp_eurst) {
				$payment = array('id' => 4, 'type' => 'GP', 'subtype' => 'EURST', 'label' => getLabel('label-yandex-payment-gp-eurst'));
				array_push($payments, $payment);
			}
			if ($this->object->yandex_gp_other) {
				$payment = array('id' => 5, 'type' => 'GP', 'subtype' => 'OTHER', 'label' => getLabel('label-yandex-payment-gp-other'));
				array_push($payments, $payment);
			}
			if ($this->object->yandex_wm) {
				$payment = array('id' => 6, 'type' => 'WM', 'subtype' => '', 'label' => getLabel('label-yandex-payment-wm'));
				array_push($payments, $payment);
			}
			if ($this->object->yandex_sb) {
				$payment = array('id' => 7, 'type' => 'SB', 'subtype' => '', 'label' => getLabel('label-yandex-payment-sb'));
				array_push($payments, $payment);
			}
			if ($this->object->yandex_mp) {
				$payment = array('id' => 8, 'type' => 'MP', 'subtype' => '', 'label' => getLabel('label-yandex-payment-mp'));
				array_push($payments, $payment);
			}
			return $payments;
		}

		public function poll() {
			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->contentType('text/xml');
			$action    = getRequest('action');
			$shopId	   = getRequest('shopId');
			$invoiceId = getRequest('invoiceId');
			$responseCode = yandex30Payment::STATUS_SUCCESS;
			if(!$this->checkSignature()) {
				$responseCode = yandex30Payment::STATUS_AUTHERROR;
			} else if(is_null($shopId) || is_null($invoiceId)) {
				$responseCode = yandex30Payment::STATUS_REQUESTERROR;
			} else {
				switch(strtolower($action)) {
					case 'checkorder'		: $responseCode = $this->checkDetails(); break;
					case 'paymentaviso'		: $responseCode = $this->acceptPaymentResult(); break;
					default					: $responseCode = yandex30Payment::STATUS_REQUESTERROR;
				}
			}
			$this->order->payment_document_num = $invoiceId;

			$buffer->push($this->getResponseXML($action, $responseCode, $shopId, $invoiceId) );
			$buffer->end();
		}
		/**
		 * Производит проверку платежных данных
		 * @return Int статус проверки
		 */
		private function checkDetails() {
			$resultCode     = yandex30Payment::STATUS_SUCCESS;
			$orderSumAmount = (float) getRequest('orderSumAmount');
			try {
				$actualPrice = (float) $this->order->getActualPrice();
				if($orderSumAmount != $actualPrice) {
					$this->order->setPaymentStatus('declined');
					$resultCode = yandex30Payment::STATUS_DECLINE;
				} else {
					$this->order->setPaymentStatus('validated');
					$resultCode = yandex30Payment::STATUS_SUCCESS;
				}
			} catch (Exception $e) {
				$resultCode = yandex30Payment::STATUS_INTERNALERROR;
			}
			return $resultCode;
		}
		/**
		 * Принимает результат платежной транзакции
		 * @return Int статус
		 */
		private function acceptPaymentResult() {
			$resultCode = yandex30Payment::STATUS_SUCCESS;
			try {
				$this->order->setPaymentStatus('accepted');
			} catch(Exception $e) {
				$resultCode = yandex30Payment::STATUS_INTERNALERROR;
			}
			return $resultCode;
		}
		/**
		 * Проверяет подпись в запросе
		 * @return Boolean true - если запрос валиден, false в противном случае
		 */
		public function checkSignature() {
			$password = (string) $this->object->shop_password;
			if(!strlen($password)) return false;
			$hashPieces   = array();
			$hashPieces[] = getRequest('action');
			$hashPieces[] = getRequest('orderSumAmount');
			$hashPieces[] = getRequest('orderSumCurrencyPaycash');
			$hashPieces[] = getRequest('orderSumBankPaycash');
			$hashPieces[] = getRequest('shopId');
			$hashPieces[] = getRequest('invoiceId');
			$hashPieces[] = getRequest('customerNumber');
			$hashPieces[] = $password;
			$hashString   = md5(implode(';', $hashPieces));
			if(strcasecmp($hashString, getRequest('md5') ) == 0) {
				return true;
			}
			return false;
		}
		/**
		 * Формирует xml для ответа на сервер Яндекс денег
		 * @param String $action    Код запроса, на которое выполняется ответ
		 * @param Int    $code      Код результата
		 * @param Int    $shopId    Идентификатор магазина
		 * @param Int    $invoiceId Идентификатор транзакции
		 * @return String
		 */
		public function getResponseXML($action, $code, $shopId, $invoiceId) {
			$dateTime = date('c');
			$result   = "<"."?xml version=\"1.0\" encoding=\"windows-1251\" ?".">" . <<<XML
<{$action}Response performedDatetime="{$dateTime}" code="{$code}" shopId="{$shopId}" invoiceId="{$invoiceId}"/>
XML;
			return $result;
		}
	};
?>
