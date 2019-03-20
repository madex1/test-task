<?php
	class usersDiscountRule extends discountRule implements orderDiscountRule, itemDiscountRule {
		public function validateOrder(order $order) {
			return $this->validate();
		}
		
		public function validateItem(iUmiHierarchyElement $element) {
			return $this->validate();
		}
		
		public function validate() {
			$cmsController = cmsController::getInstance();
			$currentMode = $cmsController->getCurrentMode();
			
			$orderId = null;
			
			if ($currentMode == 'admin') {
				$requestData = getRequest('data');
				
				if (!is_array($requestData)) {
					return false;
				}
				
				$arrayKeys = array_keys($requestData);
				
				if (isset($arrayKeys[0])) {
					$orderId = $arrayKeys[0];
				} 
			}
			
			$currentModule = $cmsController->getCurrentModule();    
			$currentMethod = $cmsController->getCurrentMethod();
			$umiObjects = umiObjectsCollection::getInstance();

			if ($currentModule == 'content' && $currentMethod == 'save_editable_region') {
				$orderId = getRequest('param0');
			}
			
			if ($currentModule == 'emarket' && $currentMethod == 'gateway') {
				$orderId = payment::getResponseOrderId();
			}

			if (!is_null($orderId) && is_array($this->users)) {
				$order = order::get($orderId);

				if (!$order instanceof order) {
					return false;
				}
				
				$customer = $umiObjects->getObject($order->getCustomerId());

				if (!$customer instanceof umiObject) {
					return false;
				}

				return in_array($customer->getId(), $this->users);
			}
			
			if ($currentMode != 'admin' && is_array($this->users)) {

				$customer = customer::get();
				$customerId = $customer->id;

				$customerObject = $umiObjects->getObject($customerId);

				if (!$customerObject instanceof umiObject) {
					return false;
				}

				$guid = $customerObject->getType()->getGUID();

				if ($guid == 'users-user') {
					return in_array($customer->id, $this->users);
				}

				$ownerId = $customerObject->getOwnerId();

				return in_array($ownerId, $this->users);
			}
			
			return false;
		}
	};
?>
