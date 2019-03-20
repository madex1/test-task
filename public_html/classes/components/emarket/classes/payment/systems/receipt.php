<?php

	use UmiCms\Service;

	/** Внутренний способ оплаты "Платежная квитанция" */
	class receiptPayment extends payment {

		/**
		 * @inheritdoc
		 * Устанавливает номер платежного документа и
		 * выводит в буффер платежную кватанцию
		 */
		public function process($template = null) {
			$order = $this->order;
			$order->order();
			$this->order->setPaymentDocumentNumber($order->getId());
			$this->order->commit();
			$result = $this->printReceipt($order);

			$buffer = Service::Response()
				->getHttpDocBuffer();
			$buffer->charset('utf-8');
			$buffer->contentType('text/html');
			$buffer->clear();
			$buffer->push($result);
			$buffer->end();
		}

		/** @inheritdoc */
		public function poll() {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->contentType('text/plain');
			$buffer->push('Sorry, but this payment system doesn\'t support server polling.' . getRequest('param0'));
			$buffer->end();
		}

		/**
		 * Возвращает платежную квитанцию для заказа
		 * @param order $order заказ
		 * @return string
		 */
		protected function printReceipt(order $order) {
			$orderId = $order->getId();
			$uri = "uobject://{$orderId}/?transform=sys-tpls/emarket-receipt.xsl";
			return file_get_contents($uri);
		}
	}
