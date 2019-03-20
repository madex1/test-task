<?php

	use UmiCms\Service;

	/** Способ оплаты через платежную систему "AcquiroPay" */
	class acquiropayPayment extends payment {

		/** @inheritdoc */
		public static function getOrderId() {
			return (int) getRequest('cf');
		}

		/**
		 * @inheritdoc
		 * Устанавливает заказу статус оплаты "Инициализирована".
		 * Устанавливает номер платежного документа у заказа.
		 */
		public function process($template = null) {
			$merchant_id = $this->merchant_id;
			$product_id = $this->product_id;
			$secret_word = $this->secret_word;

			$www = Service::DomainDetector()->detectUrl();
			$language = mb_strtolower(Service::LanguageDetector()->detectPrefix());
			$language = ($language == 'ru') ? 'ru' : 'en';

			$this->order->order();
			$amount = $this->formatPrice($this->order->getActualPrice());
			$token = md5($merchant_id . $product_id . $amount . $this->order->getId() . $secret_word);

			$successUrl = (!$this->ok_url) ? $www . '/emarket/purchase/result/successful/' : $this->_http($this->ok_url);
			$failUrl = (!$this->ko_url) ? $www . '/emarket/purchase/result/failed/' : $this->_http($this->ko_url);
			$answerUrl = $www . '/emarket/gateway/' . $this->order->getId();

			$param = [];
			$param['formAction'] = 'https://secure.acquiropay.com/';
			$param['product_id'] = $product_id;
			$param['amount'] = $amount;
			$param['language'] = $language;
			$param['order_id'] = $this->order->getId();
			$param['ok_url'] = $successUrl;
			$param['cb_url'] = $answerUrl;
			$param['ko_url'] = $failUrl;
			$param['token'] = $token;

			$this->order->setPaymentStatus('initialized');

			list($templateString) = emarket::loadTemplates(
				'emarket/payment/acquiropay/' . $template,
				'form_block'
			);

			return emarket::parseTemplate($templateString, $param);
		}

		/**
		 * @inheritdoc
		 * Подтверждает валидность заказа в платежной системе.
		 * Записывает в заказ в UMI.CMS номер платежного документа
		 * и меняет его статус оплаты, в зависимости от результата валидации:
		 *
		 * "Принята"/"Отклонена"
		 */
		public function poll() {
			if (!getRequest('payment_id')) {
				return false;
			}

			$merchant_id = $this->getValue('merchant_id');
			$secret_word = $this->getValue('secret_word');

			$payment_id = getRequest('payment_id');
			$status = getRequest('status');
			$cf = getRequest('cf');
			$amount = getRequest('amount');

			$hashString = md5($merchant_id . $payment_id . $status . $cf . $secret_word);

			if (!Service::Protection()->hashEquals($hashString, (string) getRequest('sign'))) {
				return false;
			}

			if (($this->order->getActualPrice() - $amount) != 0) {
				return false;
			}

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->contentType('text/plain');

			try {
				$this->order->setPaymentDocumentNumber($payment_id);
				$this->order->commit();

				if ($status == 'OK') {
					$this->order->setPaymentStatus('accepted');
					$buffer->push('success');
				} else {
					$this->order->setPaymentStatus('declined');
					$buffer->push('fail');
				}
			} catch (Exception $e) {
				$buffer->push('fail');
			}

			$buffer->end();
		}

		/**
		 * Добавляет к адресу страницы префикс с протоколом,
		 * если он не был добавлен ранее
		 * @param string $url адрес страницы
		 * @return string
		 */
		protected function _http($url) {
			return startsWith($url, 'http://') || startsWith($url, 'https://') ? $url : "http://$url";
		}
	}
