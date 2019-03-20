<?php
	namespace UmiCms\Classes\Components\Emarket\Orders\Items;

	/**
	 * Класс фильтра списка товарных наименований
	 * @package UmiCms\Classes\Components\Emarket\Orders\Items
	 */
	class Filter implements iFilter {

		/** @inheritdoc */
		public function getListByProduct(array $orderItemList, $productId) {
			return array_filter($orderItemList, function(\orderItem $orderItem) use ($productId) {
				$product = $orderItem->getRelatedProduct();
				return ($product instanceof \iUmiHierarchyElement && $product->getId() == $productId);
			});
		}

		/** @inheritdoc */
		public function getFirstByProduct(array $orderItemList, $productId) {
			$list = $this->getListByProduct($orderItemList, $productId);
			return array_shift($list);
		}

		/** @inheritdoc */
		public function getListWithoutProduct(array $orderItemList) {
			return array_filter($orderItemList, function(\orderItem $orderItem) {
				return (!$orderItem->getRelatedProduct() instanceof \iUmiHierarchyElement);
			});
		}

		/** @inheritdoc */
		public function getFirstWithoutProduct(array $orderItemList) {
			$list = $this->getListWithoutProduct($orderItemList);
			return array_shift($list);
		}

		/** @inheritdoc */
		public function getListByTradeOffer(array $orderItemList, $tradeOfferId) {
			return array_filter($orderItemList, function(\orderItem $orderItem) use ($tradeOfferId) {
				return ($orderItem instanceof \TradeOfferOrderItem && $orderItem->getOfferId() == $tradeOfferId);
			});
		}

		/** @inheritdoc */
		public function getFirstByTradeOffer(array $orderItemList, $tradeOfferId) {
			$list = $this->getListByTradeOffer($orderItemList, $tradeOfferId);
			return array_shift($list);
		}

		/** @inheritdoc */
		public function getListByOptions(array $orderItemList, array $optionList) {
			return array_filter($orderItemList, function(\orderItem $orderItem) use ($optionList) {
				return ($orderItem instanceof \optionedOrderItem && $orderItem->hasOptions($optionList));
			});
		}

		/** @inheritdoc */
		public function getFirstByOptions(array $orderItemList, array $optionList) {
			$list = $this->getListByOptions($orderItemList, $optionList);
			return array_shift($list);
		}

		/** @inheritdoc */
		public function getListByEmptyModifier(array $orderItemList) {
			return array_filter($orderItemList, function(\orderItem $orderItem) {
				return $orderItem->containsAppliedModifier() === false;
			});
		}

		/** @inheritdoc */
		public function getFirstByEmptyModifier(array $orderItemList) {
			$list = $this->getListByEmptyModifier($orderItemList);
			return array_shift($list);
		}
	}