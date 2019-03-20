<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer\Receipt;

	use UmiCms\Classes\Components\Emarket\Serializer\Receipt;

	/**
	 * Класс сериализатора для эквайринга сбербанка
	 * @package UmiCms\Classes\Components\Emarket\Serializer
	 */
	class Sberbank extends Receipt {

		/**
		 * Возвращает данные для печати чека
		 * @param \order $order заказ
		 * @return array
		 */
		public function getReceipt(\order $order) {
			return [];
		}

		/** @inheritdoc */
		public function getContact(\order $order) {
			return [];
		}

		/** @inheritdoc */
		protected function getDeliveryInfo(\order $order) {
			return [];
		}

		/** @inheritdoc */
		protected function getOrderItemInfo(\order $order, \orderItem $orderItem) {
			return [];
		}

		/** @inheritdoc */
		protected function fixItemPriceSummary(\order $order, array $orderItemList) {
			return [];
		}
	}
