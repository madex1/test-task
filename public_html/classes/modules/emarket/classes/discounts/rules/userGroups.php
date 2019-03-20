<?php
	class userGroupsDiscountRule extends discountRule implements orderDiscountRule, itemDiscountRule {
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

			if (!is_null($orderId) && is_array($this->user_groups)) {

				$order = order::get($orderId);

				if (!$order instanceof order) {
					return false;
				}

				$customerObject = $umiObjects->getObject($order->getCustomerId());

				if (!$customerObject instanceof umiObject) {
					return false;
				}

				return $this->validateOnAdminAndGatewayMode($customerObject);
			}

			if ($currentMode != 'admin' && is_array($this->user_groups)) {
				$customer = customer::get();
				$customerId = $customer->id;

				$customerObject = $umiObjects->getObject($customerId);

				if (!$customerObject instanceof umiObject) {
					return false;
				}

				return $this->validateOnSiteMode($customerObject, $customer);
			}

			return false;
		}

		/**
		 * Валидирует скидку при вызове в сайтовой части
		 * @param umiObject $customerObject
		 * @param customer $customer
		 * @return bool
		 */
		public function validateOnSiteMode(umiObject $customerObject, customer $customer) {

			$guid = $customerObject->getType()->getGUID();

			if ($guid == 'users-user' && is_array($customer->groups)) {
				return (bool) count(array_intersect($customer->groups, $this->user_groups));
			}

			$umiObjects = umiObjectsCollection::getInstance();
			$ownerId = $customerObject->getOwnerId();
			$userObject = $umiObjects->getObject($ownerId);

			if (!$userObject instanceof umiObject) {
				return false;
			}

			if (is_array($userObject->groups)) {
				return (bool) count(array_intersect($userObject->groups, $this->user_groups));
			}

			return false;
		}

		/**
		 * Валидирует скидку при вызове в административной части, либо
		 * при вызове в контексте обращения платежной системы
		 * @param umiObject $customerObject
		 * @return bool
		 */
		public function validateOnAdminAndGatewayMode(umiObject $customerObject) {

			$guid = $customerObject->getType()->getGUID();

			if ($guid == 'users-user' && is_array($customerObject->groups)) {
				return (bool) count(array_intersect($customerObject->groups, $this->user_groups));
			}

			$umiObjects = umiObjectsCollection::getInstance();
			$ownerId = $customerObject->getOwnerId();
			$userObject = $umiObjects->getObject($ownerId);

			if (!$userObject instanceof umiObject) {
				return false;
			}

			if (is_array($userObject->groups)) {
				return (bool) count(array_intersect($userObject->groups, $this->user_groups));
			}

			return false;
		}
	};
?>
