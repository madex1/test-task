<?php
	use UmiCms\Service;
	use UmiCms\System\Trade\iOffer;

	/**
	 * Класс товарного наименования заказа типа "Торговое предложение".
	 */
	class TradeOfferOrderItem extends orderItem {

		/** @var iOffer|null $offer торговое предложение */
		private $offer;

		/** @inheritdoc */
		public function __construct(iUmiObject $object) {
			$this->object = $object;
			$this->originalPrice = (float) $object->getValue('item_price');
			$this->totalOriginalPrice = (float) $object->getValue('item_total_original_price');
			$this->totalActualPrice = (float) $object->getValue('item_total_price');
			$this->actualPrice = ((float) $object->getValue('item_actual_price')) ?: (float) $this->calculateActualPrice();
			$this->amount = (int) $object->getValue('item_amount');
			$this->discount = itemDiscount::get($object->getValue('item_discount_id'));
			$this->itemElement = $object->getValue('item_link');
			$this->offer = Service::TradeOfferFacade()->get($object->getValue('trade_offer'));

			if ($this->originalPrice == 0 && $this->offer instanceof iOffer) {
				$this->setOfferProperties($this->offer);
			}

			$discountValue = $object->getValue('item_discount_value');

			if (!is_numeric($discountValue)) {
				$pricesDiff = ($this->totalOriginalPrice - $this->totalActualPrice);
				$discountValue = ($pricesDiff < 0) ? 0 : $pricesDiff;
			}

			$this->discountValue = (float) $discountValue;
		}

		/**
		 * Устанавливает идентификатор торгового предложения
		 * @param int $id идентификатор
		 * @return $this
		 * @throws ErrorException
		 * @throws ReflectionException
		 * @throws coreException
		 * @throws databaseException
		 * @throws privateException
		 * @throws selectorException
		 * @throws wrongParamException
		 */
		public function setOfferId($id) {
			$offer = Service::TradeOfferFacade()
				->get($id);

			if (!$offer instanceof iOffer) {
				throw new ErrorException('Incorrect trade offer id given');
			}

			$this->offer = $offer;
			$this->object->setValue('trade_offer', $offer->getId());
			$this->setOfferProperties($offer);
			return $this;
		}

		/**
		 * Возвращает идентификатор торгового предложения
		 * @return int|null
		 */
		public function getOfferId() {
			return $this->object->getValue('trade_offer');
		}

		/** Применяет изменения товарного наименования */
		public function commit() {
			$object = $this->object;
			$object->setValue('item_price', $this->originalPrice);
			$object->setValue('item_actual_price', $this->actualPrice);
			$object->setValue('item_total_original_price', $this->totalOriginalPrice);
			$object->setValue('item_total_price', $this->totalActualPrice);
			$object->setValue('item_amount', $this->amount);
			$object->setValue('item_discount_id', $this->discount instanceof discount ? $this->discount->getId() : null);
			$object->setValue('item_link', $this->itemElement);
			$object->setValue('item_discount_value', $this->discountValue);
			$object->setValue('trade_offer', $this->offer instanceof iOffer ? $this->offer->getId() : null);
			$object->commit();
		}

		/** @inheritdoc */
		public function containsAppliedModifier() {
			return (bool) $this->getOfferId() || parent::containsAppliedModifier();
		}

		/** @inheritdoc */
		public function getBasketPrice() {
			return $this->getOriginalPrice();
		}

		/**
		 * Устанавливает параметры предложения
		 * @param iOffer $offer
		 * @return $this
		 * @throws ErrorException
		 * @throws ReflectionException
		 * @throws coreException
		 * @throws privateException
		 * @throws selectorException
		 * @throws wrongParamException
		 */
		protected function setOfferProperties(iOffer $offer) {
			/** @var emarket $emarket */
			$emarket = cmsController::getInstance()->getModule('emarket');
			$settings = $emarket->getSettings();
			$object = $this->object;
			$object->setName($offer->getName());

			$weight = $offer->getWeight();
			if (!is_numeric($weight) || $weight == 0) {
				$weight = (float) $settings->get(EmarketSettings::ORDER_ITEM_SECTION, 'weight');
			}
			$object->setValue('weight', $weight);

			$width = $offer->getWidth();
			if (!is_numeric($width) || $width == 0) {
				$width = (float) $settings->get(EmarketSettings::ORDER_ITEM_SECTION, 'width');
			}
			$object->setValue('width', $width);

			$height = $offer->getHeight();
			if (!is_numeric($height) || $height == 0) {
				$height = (float) $settings->get(EmarketSettings::ORDER_ITEM_SECTION, 'height');
			}
			$object->setValue('height', $height);

			$length = $offer->getLength();
			if (!is_numeric($length) || $length == 0) {
				$length = (float) $settings->get(EmarketSettings::ORDER_ITEM_SECTION, 'length');
			}
			$object->setValue('length', $length);

			$this->setOriginalPrice($offer->getPriceCollection()->getMain()->getValue());
			return $this;
		}
	}