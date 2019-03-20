<?php

	use UmiCms\Service;

	/**
	 * Класс правила скидки типа "Сумма покупок пользователя".
	 * Подходит для скидок на заказ и на товар.
	 * Содержит 2 настройки:
	 *
	 * 1) Минимальная подходящая сумма заказов пользователя;
	 * 2) Максимальная подходящая сумма заказов пользователя;
	 *
	 * Значения настроек хранятся в объекте-источнике данных для правила скидки.
	 */
	class allOrdersPricesDiscountRule extends discountRule implements orderDiscountRule, itemDiscountRule {

		/**
		 * @const int CUSTOMER_ORDERS_LIMIT максимальная длина выборки заказов при подсчете
		 */
		const CUSTOMER_ORDERS_LIMIT = 100;

		/** @inheritdoc */
		public function validateOrder(order $order) {
			return $this->validate($order);
		}

		/** @inheritdoc */
		public function validateItem(iUmiHierarchyElement $element) {
			return $this->validate();
		}

		/**
		 * Определяет применимость скидки
		 * @param order|null $excludedOrder заказ, который не нужно учитывать
		 * @return bool
		 */
		private function validate(order $excludedOrder = null) {
			$offset = 0;
			$totalPriceForAllOrders = 0;
			$maximum = $this->getValue('maximum');
			$maxOffsetValue = self::CUSTOMER_ORDERS_LIMIT * 1000;

			do {
				$pricesSumm = $this->getPricesSum($offset, $excludedOrder);
				if ($pricesSumm === false) {
					break;
				}

				$totalPriceForAllOrders += $pricesSumm;
				if ($maximum && $totalPriceForAllOrders > $maximum) {
					return false;
				}

				$offset += self::CUSTOMER_ORDERS_LIMIT;
			} while ($offset < $maxOffsetValue);

			$minimum = $this->getValue('minimal');
			if ($minimum && $totalPriceForAllOrders < $minimum) {
				return false;
			}

			return true;
		}

		/**
		 * Возвращает сумму стоимостей порции заказов покупателя
		 * @param int $offset смещение выборки
		 * @param order|null $excludedOrder заказ, который не нужно учитывать
		 * @return float|bool, false - в случае пустой выборки
		 */
		protected function getPricesSum($offset, order $excludedOrder = null) {
			$price = 0;
			$customerOrdersList = $this->getCustomerOrders($offset, $excludedOrder);

			if (empty($customerOrdersList)) {
				return false;
			}

			foreach ($customerOrdersList as $customerOrder) {
				$order = order::get($customerOrder->getId());
				$price += (float) $order->getActualPrice();
			}

			return $price;
		}

		/**
		 * Возвращает список всех заказов пользователя в статусе "Готов"
		 * @param int $offset смещение выборки
		 * @param order|null $excludedOrder заказ, которые нужно не учитывать
		 * @return iUmiObject[]
		 * @throws selectorException
		 */
		protected function getCustomerOrders($offset, order $excludedOrder = null) {
			$domainId = Service::DomainDetector()->detectId();
			$excludedOrderId = null;

			if ($excludedOrder instanceof order) {
				$customer = customer::get(true, $excludedOrder->getCustomerId());
				$excludedOrderId = $excludedOrder->getId();
			} else {
				$customer = customer::get(true);
			}

			if (!$customer instanceof customer) {
				return [];
			}

			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('emarket', 'order');
			$sel->where('customer_id')->equals($customer->getId());

			if ($excludedOrderId !== null) {
				$sel->where('id')->notequals($excludedOrderId);
			}

			$sel->where('domain_id')->equals($domainId);
			$sel->where('status_id')->equals(order::getStatusByCode('ready'));
			$sel->option('load-all-props')->value(true);
			$sel->limit($offset, self::CUSTOMER_ORDERS_LIMIT);
			$customerOrders = $sel->result();

			return $customerOrders;
		}
	}
