<?php
	class receiptPayment extends payment {
		public function validate() {
			return true;
		}

		public function process($template = null) {
			$order = $this->order;
			$order->order();
			$order->payment_document_num = $order->id;

			$result = $this->printReceipt($order);

			$buffer = \UmiCms\Service::Response()
				->getHttpDocBuffer();
			$buffer->charset('utf-8');
			$buffer->contentType('text/html');
			$buffer->clear();
			$buffer->push($result);
			$buffer->end();
		}

		public function poll() {
			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->contentType('text/plain');
			$buffer->push('Sorry, but this payment system doesn\'t support server polling.' . getRequest('param0'));
			$buffer->end();
		}

		protected function printReceipt(order $order) {
			$orderId = $order->getId();
			$uri = "uobject://{$orderId}/?transform=sys-tpls/emarket-receipt.xsl";
			return file_get_contents($uri);
		}
	}
?>
