<?php

	/** Интерфейс генератора номера заказа */
	interface iOrderNumber {

		/**
		 * Конструктор
		 * @param order $order заказ
		 */
		public function __construct(order $order);

		/**
		 * Генерирует и устанавливает номер заказа.
		 * Возвращает установленный номер.
		 * @return int
		 */
		public function number();
	}
