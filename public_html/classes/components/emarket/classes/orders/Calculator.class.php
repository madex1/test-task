<?php

	namespace UmiCms\Classes\Components\Emarket\Orders;

	/**
	 * Калькулятор данных заказа
	 * Class Calculator
	 * @package UmiCms\Classes\Components\Emarket\Orders
	 */
	class Calculator {

		/**
		 * Конструктор
		 * @param \order $order заказ, для которого будут производиться расчеты
		 */
		public function __construct(\order $order) {
			$this->order = $order;
		}

		/**
		 * Возвращает общий вес заказа
		 * @return float
		 */
		public function getTotalWeight() {
			return $this->sumItemsProperty(function ($item) {
				/** @var $item \orderItem */
				return $item->getWeight();
			});
		}

		/**
		 * Возвращает общую ширину заказа
		 * @return float
		 */
		public function getTotalWidth() {
			return $this->sumItemsProperty(function ($item) {
				/** @var $item \orderItem */
				return $item->getWidth();
			});
		}

		/**
		 * Возвращает общую высоту заказа
		 * @return float
		 */
		public function getTotalHeight() {
			return $this->maxItemsProperty(function ($item) {
				/** @var $item \orderItem */
				return $item->getHeight();
			});
		}

		/**
		 * Возвращает общую длину заказа
		 * @return float
		 */
		public function getTotalLength() {
			return $this->maxItemsProperty(function ($item) {
				/** @var $item \orderItem */
				return $item->getLength();
			});
		}

		/**
		 * Возвращает максимальное значение свойства наименований заказа
		 * @param callable $callback функциия, в которой нужно вернуть значение необходимого свойства
		 * @return float
		 */
		protected function maxItemsProperty(Callable $callback) {
			$max = 0;

			$this->order->forEachItem(function ($item) use ($callback, &$max) {
				$value = $callback($item);

				if ($max < $value) {
					$max = $value;
				}
			});

			return $max;
		}

		/**
		 * Возвращает сумму значений свойства наименований заказа,
		 * учитывая количество каждого наименования
		 * @param callable $callback функциия, в которой нужно вернуть значение необходимого свойства
		 * @return float
		 */
		protected function sumItemsProperty(Callable $callback) {
			$sum = 0;

			$this->order->forEachItem(function ($item) use ($callback, &$sum) {
				/** @var $item \orderItem */
				$amount = ($item->getAmount() == 0) ? 1 : $item->getAmount();
				$sum += ($amount * $callback($item));
			});

			return $sum;
		}
	}
