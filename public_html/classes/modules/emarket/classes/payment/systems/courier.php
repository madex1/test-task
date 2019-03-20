<?php
	class courierPayment extends payment {

		public function validate() { return true; }

		public function process($template = null) {
			$order = $this->order;
			$order->order();
			$controller = cmsController::getInstance();
			$module = $controller->getModule("emarket");
			if($module) {
				$module->redirect($controller->getPreLang() . '/emarket/purchase/result/successful/');
			}
			return null;
		}

		public function poll() {
			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->contentType('text/plain');
			$buffer->push('Sorry, but this payment system doesn\'t support server polling.' . getRequest('param0'));
			$buffer->end();
		}

	};
?>
