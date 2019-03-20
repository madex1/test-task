<?php

	use UmiCms\Service;

	/**
	 * Класс правила скидки типа "Группа покупателя".
	 * Подходит для скидок на заказ и на товар.
	 * Содержит 1 настройку:
	 *
	 * 1) Список подходящих групп пользователей;
	 *
	 * Значение настройки хранится в объекте-источнике данных для правила скидки.
	 */
	class userGroupsDiscountRule extends discountRule implements orderDiscountRule, itemDiscountRule {

		/**
		 * @@inheritdoc
		 * @throws publicException
		 */
		public function validateOrder(order $order) {
			return $this->validate();
		}

		/**
		 * @@inheritdoc
		 * @throws publicException
		 */
		public function validateItem(iUmiHierarchyElement $element) {
			return $this->validate();
		}

		/**
		 * Запускает валидацию и возвращает результат
		 * @return bool
		 * @throws publicException
		 */
		public function validate() {

			if (!is_array($this->user_groups)) {
				return false;
			}

			if (Service::Request()->isAdmin()) {
				$orderId = $this->getOrderIdFromAdminRequestData();

				if ($orderId === false) {
					return false;
				}
			}

			$orderId = $this->getOrderIdFromSiteRequestData();

			if ($orderId !== null) {
				$customerObject = $this->getCustomerObjectFromOrder($orderId);
				return $this->validateCustomer($customerObject);
			}

			$customerObject = null;

			if (Service::Request()->isSite()) {
				$customerObject = $this->getCustomerObject();
			}

			return $this->validateCustomer($customerObject);
		}

		/**
		 * Валидирует покупателя
		 * @param iUmiObject|null|bool $object объект покупателя
		 * @return bool
		 */
		protected function validateCustomer($object) {

			if (!$object instanceof iUmiObject) {
				return false;
			}

			if ($object->getTypeGUID() == 'users-user') {
				return $this->assertUserGroupList($object->getValue('groups'));
			}

			$ownerId = $object->getOwnerId();
			$owner = umiObjectsCollection::getInstance()
				->getObject($ownerId);

			if ($owner instanceof iUmiObject) {
				return $this->assertUserGroupList($owner->getValue('groups'));
			}

			return false;
		}

		/**
		 * Проверяет, что группы пользователя входят в валидный список
		 * @param mixed $groupList список проверяемых групп
		 * @return bool
		 */
		private function assertUserGroupList($groupList){

			if (!is_array($groupList)) {
				return false;
			}

			return (bool) umiCount(array_intersect($groupList, $this->user_groups));
		}

		/** @deprecated  */
		public function validateOnAdminAndGatewayMode(iUmiObject $customerObject) {
			return $this->validateCustomer($customerObject);
		}

		/** @deprecated  */
		public function validateOnSiteMode(iUmiObject $customerObject) {
			return $this->validateCustomer($customerObject);
		}
	}
