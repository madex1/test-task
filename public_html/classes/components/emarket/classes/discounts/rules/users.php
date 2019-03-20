<?php

	use UmiCms\Service;

	/**
	 * Класс правила скидки типа "Покупатель".
	 * Подходит для скидок на заказ и на товар.
	 * Содержит 1 настройку:
	 *
	 * 1) Список подходящих пользователей;
	 *
	 * Значение настройки хранится в объекте-источнике данных для правила скидки.
	 */
	class usersDiscountRule extends discountRule implements orderDiscountRule, itemDiscountRule {

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

			if (!is_array($this->users)) {
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
				return $this->assertUser($object->getId());
			}

			return $this->assertUser($object->getOwnerId());
		}

		/**
		 * Проверяет, что пользователь входит в валидный список
		 * @param int $id идентификатор проверяемого пользователя
		 * @return bool
		 */
		protected function assertUser($id) {
			return in_array($id, $this->users);
		}
	}
