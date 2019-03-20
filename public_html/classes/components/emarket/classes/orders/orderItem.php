<?php

	use UmiCms\Service;

	/**
	 * Базовый класс товарного наименования заказа абстрактного типа.
	 * Одновременно является родительским классом всех типов товарных наименований
	 * и предоставляет интерфейс для создания и получения наименования.
	 *
	 * По умолчанию в системе существуют следующие типы товарных наименования:
	 *
	 * 1) "Опционное";
	 * 2) "Цифровое";
	 * 3) "Пользовательское";
	 *
	 * Товарное наименование - это представитель товара (объекта каталога) в процессе оформления заказа.
	 * Как и любому другому наследнику umiObjectProxy, товарному предложению необходим объект-источник данных (umiObject).
	 *
	 * Особенности наименования:
	 *
	 * 1) Оригинальной стоимостью считается стоимость без учета скидок;
	 * 2) Актуальной стоимостью считается стоимость с учетом скидки;
	 */
	class orderItem extends umiObjectProxy {

		/** @var float $originalPrice оригинальная стоимость одного товарного наименования */
		protected $originalPrice;

		/** @var float $actualPrice актуальная стоимость одного товарного наименования */
		protected $actualPrice;

		/** @var float $totalOriginalPrice оригинальная стоимость полного количества товарного наименования */
		protected $totalOriginalPrice;

		/** @var float $totalActualPrice актуальная стоимость полного количества товарного наименования */
		protected $totalActualPrice;

		/** @var int $amount количество товарного наименования */
		protected $amount;

		/** @var discount $discount примененная скидка */
		protected $discount;

		/**
		 * @var array|null Страница-источник данных (объект каталога) для товарного наименования
		 * [
		 *     0 => iUmiHierarchyElement
		 * ]
		 */
		protected $itemElement;

		/** @var bool $isDigital является ли товарное наименования цифровым товаром */
		protected $isDigital;

		/** @var float $discountValue абсолютное значение скидки наименования */
		protected $discountValue;

		/**
		 * @var string ITEM_DISCOUNT_VALUE_FIELD_GUID гуид поля объекта-источника для наименования, в котором хранится
		 *   абсолютное значение скидки наименования
		 */
		const ITEM_DISCOUNT_VALUE_FIELD_GUID = 'item_discount_value';

		/** @const имя поля веса */
		const WEIGHT_FIELD = 'weight';

		/** @const имя поля ширины */
		const WIDTH_FIELD = 'width';

		/** @const имя поля высоты */
		const HEIGHT_FIELD = 'height';

		/** @const имя поля длины */
		const LENGTH_FIELD = 'length';

		/** @const string TAX_RATE_ID_FIELD имя поля с идентификатором ставки ндс */
		const TAX_RATE_ID_FIELD = 'tax_rate_id';

		/** @var string OPTIONED_PREFIX префикс класса товарного наименования типа "Опционное" */
		const OPTIONED_PREFIX = 'optioned';

		/** @var string DIGITAL_PREFIX префикс класса товарного наименования типа "Цифровое" */
		const DIGITAL_PREFIX = 'digital';

		/** @var string CUSTOM_PREFIX префикс класса товарного наименования типа "Пользовательский" */
		const CUSTOM_PREFIX = 'custom';

		/** @var string TRADE_OFFER__PREFIX префикс класса товарного наименования типа "Торговое предложение" */
		const TRADE_OFFER_PREFIX = 'TradeOffer';

		/** @const string PAYMENT_SUBJECT_FIELD имя поля с признаком предмета расчета */
		const PAYMENT_SUBJECT_FIELD = 'payment_subject';

		/** @const string PAYMENT_MODE_FIELD имя поля с признаком способа расчета */
		const PAYMENT_MODE_FIELD = 'payment_mode';

		/**
		 * Возвращает товарное наименование конкретного типа.
		 * Тип зависит от текущих настроек интернет-магазина и страницы источника
		 * @param int $objectId идентификатор объекта типа товарного наименования
		 * @return customOrderItem|digitalOrderItem|optionedOrderItem
		 * @throws coreException
		 * @throws privateException
		 */
		public static function get($objectId) {
			$objects = umiObjectsCollection::getInstance();
			$object = $objects->getObject($objectId);

			if (!$object instanceof iUmiObject) {
				throw new privateException("Couldn't load order item object #{$objectId}");
			}

			$classPrefix = '';

			if ($object->getValue('item_type_id')) {
				$classPrefix = objectProxyHelper::getClassPrefixByType($object->getValue('item_type_id'));
			}

			$className = $classPrefix ? ($classPrefix . 'OrderItem') : 'orderItem';

			if (!class_exists($className)) {
				objectProxyHelper::includeClass('emarket/classes/orders/items/', $classPrefix);
			}

			return new $className($object);
		}

		/**
		 * Создает новое товарное наименования конкретного типа
		 * @see orderItem::getItemTypeId()
		 * @param int $elementId идентификатор страницы-источник данных (объекта каталога)
		 * @param int|bool $typeId идентификатор типа предложения, если не задан - будет вычислен автоматически
		 * @return customOrderItem|digitalOrderItem|optionedOrderItem|TradeOfferOrderItem
		 * @throws coreException
		 * @throws privateException
		 * @throws publicException
		 */
		public static function create($elementId, $typeId = false) {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$objects = umiObjectsCollection::getInstance();
			/** @var emarket $emarket */
			$emarket = cmsController::getInstance()->getModule('emarket');

			if (!$emarket instanceof emarket) {
				throw new coreException("Couldn't load module emarket");
			}

			$objectTypeId = $objectTypes->getTypeIdByHierarchyTypeName('emarket', 'order_item');
			$hierarchy = umiHierarchy::getInstance();

			$objectId = $objects->addObject('', $objectTypeId);
			$object = $objects->getObject($objectId);

			if (!$object instanceof iUmiObject) {
				throw new coreException("Couldn't load order item object #{$objectId}");
			}

			self::validateProductPage($elementId);

			$element = $hierarchy->getElement($elementId);
			$price = $emarket->getPrice($element, true);
			$object->item_price = $price;
			$object->item_actual_price = $price;
			$object->item_amount = 0;
			$object->item_type_id = $typeId ?: self::getItemTypeId($element->getObjectTypeId());
			$object->item_link = $element;
			$object->name = $element->getName();

			self::setProperties($object, $element);
			$object->commit();

			return self::get($object->getId());
		}

		/**
		 * Создает товарное наименование заказа типа "Опционное".
		 * @param int $elementId идентификатор страницы-источник данных (объекта каталога)
		 * @param array $optionList список опций
		 * @return optionedOrderItem
		 * @throws ErrorException
		 * @throws coreException
		 * @throws privateException
		 * @throws publicException
		 * @throws selectorException
		 */
		public static function createOptioned($elementId, array $optionList = []) {
			$orderItem = self::createByClassPrefix($elementId, self::OPTIONED_PREFIX);

			foreach ($optionList as $optionName => $optionId) {
				$orderItem->appendOption($optionName, $optionId);
			}

			$orderItem->commit();
			return $orderItem;
		}

		/**
		 * Создает товарное наименование заказа типа "Цифровое".
		 * @param int $elementId идентификатор страницы-источник данных (объекта каталога)
		 * @return digitalOrderItem
		 * @throws ErrorException
		 * @throws coreException
		 * @throws privateException
		 * @throws publicException
		 * @throws selectorException
		 */
		public static function createDigital($elementId) {
			return self::createByClassPrefix($elementId, self::DIGITAL_PREFIX);
		}

		/**
		 * Создает товарное наименование заказа типа "Пользовательское".
		 * @param int $elementId идентификатор страницы-источник данных (объекта каталога)
		 * @return customOrderItem
		 * @throws ErrorException
		 * @throws coreException
		 * @throws privateException
		 * @throws publicException
		 * @throws selectorException
		 */
		public static function createCustom($elementId) {
			return self::createByClassPrefix($elementId, self::CUSTOM_PREFIX);
		}

		/**
		 * Создает товарное наименование заказа типа "Торговое предложение".
		 * @param int $elementId идентификатор страницы-источник данных (объекта каталога)
		 * @param int|null $tradeOfferId идентификатор торгового предложения
		 * @return TradeOfferOrderItem
		 * @throws ErrorException
		 * @throws coreException
		 * @throws privateException
		 * @throws publicException
		 * @throws selectorException
		 * @throws ReflectionException
		 */
		public static function createTradeOffer($elementId, $tradeOfferId = null) {
			$orderItem = self::createByClassPrefix($elementId, self::TRADE_OFFER_PREFIX);

			if ($tradeOfferId !== null) {
				$orderItem->setOfferId($tradeOfferId);
				$orderItem->commit();
			}

			return $orderItem;
		}

		/**
		 * Создает новое товарное наименования по префиксу класса типа
		 * @param int $elementId идентификатор страницы-источник данных (объекта каталога)
		 * @param string $prefix префикс класса типа
		 * @return customOrderItem|digitalOrderItem|optionedOrderItem|TradeOfferOrderItem
		 * @throws ErrorException
		 * @throws coreException
		 * @throws privateException
		 * @throws publicException
		 * @throws selectorException
		 */
		public static function createByClassPrefix($elementId, $prefix) {
			$typeId = self::getItemTypeIdByClassPrefix($prefix);

			if (!is_numeric($typeId)) {
				throw new ErrorException(sprintf('Cannot get order item type by class "%s"', $prefix));
			}

			return self::create($elementId, $typeId);
		}

		/**
		 * Валидирует страницу - объект каталога
		 * @param int $id идентификатор страницы
		 * @throws publicException
		 */
		private static function validateProductPage($id) {
			$product = umiHierarchy::getInstance()->getElement($id);
			if (!$product instanceof iUmiHierarchyElement) {
				throw new publicException("Page #$id not found");
			}

			$baseType = $product->getHierarchyType();
			$typeName = $baseType->getName();
			$typeExt = $baseType->getExt();

			if (!($typeName === 'catalog' && $typeExt === 'object')) {
				throw new publicException("Page $id is of wrong type $typeName/$typeExt");
			}
		}

		/**
		 * Возвращает вес наименования
		 * @return float
		 */
		public function getWeight() {
			return $this->object->getValue(self::WEIGHT_FIELD);
		}

		/**
		 * Устанавливает вес наименования
		 * @param float $weight
		 * @return $this
		 */
		public function setWeight($weight) {
			$this->object->setValue(self::WEIGHT_FIELD, $weight);
			return $this;
		}

		/**
		 * Возвращает ширину наименования
		 * @return float
		 */
		public function getWidth() {
			return $this->object->getValue(self::WIDTH_FIELD);
		}

		/**
		 * Возвращает высоту наименования
		 * @return float
		 */
		public function getHeight() {
			return $this->object->getValue(self::HEIGHT_FIELD);
		}

		/**
		 * Возвращает длину наименования
		 * @return float
		 */
		public function getLength() {
			return $this->object->getValue(self::LENGTH_FIELD);
		}

		/**
		 * Возвращает идентификатор ставки НДС
		 * @return int|null
		 */
		public function getTaxRateId() {
			return $this->object->getValue(self::TAX_RATE_ID_FIELD);
		}

		/**
		 * Устанавливает идентификатор ставки НДС
		 * @param int $taxRateId
		 * @return $this
		 */
		public function setTaxRateId($taxRateId) {
			$this->object->setValue(self::TAX_RATE_ID_FIELD, $taxRateId);
			return $this;
		}

		/**
		 * Возвращает идентификатор признака предмета расчета
		 * @return int|null
		 */
		public function getPaymentSubjectId(){
			return $this->object->getValue(self::PAYMENT_SUBJECT_FIELD);
		}

		/**
		 * Устанавливает идентификатор признака предмета расчета
		 * @param int $paymentSubject
		 * @return $this
		 */
		public function setPaymentSubjectId($paymentSubject){
			$this->object->setValue(self::PAYMENT_SUBJECT_FIELD, $paymentSubject);
			return $this;
		}

		/**
		 * Возвращает идентификатор признака способа расчета
		 * @return int|null
		 */
		public function getPaymentModeId(){
			return $this->object->getValue(self::PAYMENT_MODE_FIELD);
		}

		/**
		 * Устанавливает идентификатор признака способа расчета
		 * @param int $paymentMode
		 * @return $this
		 */
		public function setPaymentModeId($paymentMode){
			$this->object->setValue(self::PAYMENT_MODE_FIELD, $paymentMode);
			return $this;
		}

		/**
		 * Определяет содержит ли товарное наименование примененный модификатор
		 * @return bool
		 */
		public function containsAppliedModifier() {
			return false;
		}

		/**
		 * Устанавливает значения для характеристик наименования
		 * @param iUmiObject $item объект наименования
		 * @param iUmiHierarchyElement $element связанный товар каталога
		 * @throws coreException
		 */
		protected static function setProperties($item, $element) {
			/** @var emarket $emarket */
			$emarket = cmsController::getInstance()->getModule('emarket');
			$settings = $emarket->getSettings();
			$object = $element->getObject();

			$weight = $object->getValueById($settings->get($settings::ORDER_ITEM_SECTION, 'weightField'));

			if (!is_numeric($weight) || $weight == 0) {
				$weight = (float) $settings->get(EmarketSettings::ORDER_ITEM_SECTION, 'weight');
			}

			$item->setValue(self::WEIGHT_FIELD, $weight);

			$width = $object->getValueById($settings->get($settings::ORDER_ITEM_SECTION, 'widthField'));

			if (!is_numeric($width) || $width == 0) {
				$width = (float) $settings->get(EmarketSettings::ORDER_ITEM_SECTION, 'width');
			}

			$item->setValue(self::WIDTH_FIELD, $width);

			$height = $object->getValueById($settings->get($settings::ORDER_ITEM_SECTION, 'heightField'));

			if (!is_numeric($height) || $height == 0) {
				$height = (float) $settings->get(EmarketSettings::ORDER_ITEM_SECTION, 'height');
			}

			$item->setValue(self::HEIGHT_FIELD, $height);

			$length = $object->getValueById($settings->get($settings::ORDER_ITEM_SECTION, 'lengthField'));

			if (!is_numeric($length) || $length == 0) {
				$length = (float) $settings->get(EmarketSettings::ORDER_ITEM_SECTION, 'length');
			}

			$item->setValue(self::LENGTH_FIELD, $length);

			$taxRateId = $object->getValue(self::TAX_RATE_ID_FIELD);

			if (!is_numeric($taxRateId) || $taxRateId == 0) {
				$taxRateId = (float) $settings->get(EmarketSettings::ORDER_ITEM_SECTION, 'taxRateId');
			}

			$item->setValue(self::TAX_RATE_ID_FIELD, $taxRateId);

			$item->setValue(self::PAYMENT_SUBJECT_FIELD, $object->getValue(self::PAYMENT_SUBJECT_FIELD));
			$item->setValue(self::PAYMENT_MODE_FIELD, $object->getValue(self::PAYMENT_MODE_FIELD));
			$item->commit();
		}

		/**
		 * Удаляет объект-источник данных для товарного наименования
		 * @throws coreException
		 */
		public function remove() {
			$objects = umiObjectsCollection::getInstance();
			if ($this->object instanceof iUmiObject) {
				$objects->delObject($this->getId());
			}
		}

		/**
		 * Возвращает название товарного наименования
		 * @return string
		 */
		public function getName() {
			return $this->object->getName();
		}

		/**
		 * Возвращает количество товарного наименования
		 * @return int
		 */
		public function getAmount() {
			return $this->amount;
		}

		/**
		 * Устанавливает количество товарного наименования
		 * @param int $amount количество
		 */
		public function setAmount($amount) {
			$this->amount = (int) $amount;
		}

		/**
		 * Возвращает оригинальную стоимость полного количества товарного наименования
		 * @return float
		 */
		public function getTotalOriginalPrice() {
			return $this->totalOriginalPrice;
		}

		/**
		 * Возвращает актуальную стоимость полного количества товарного наименования
		 * @return float
		 */
		public function getTotalActualPrice() {
			return $this->totalActualPrice;
		}

		/**
		 * Возвращает оригинальную стоимость одного товарного наименования
		 * @return float
		 */
		public function getOriginalPrice() {
			return $this->originalPrice;
		}

		/**
		 * Возвращает цену в корзине без учета скидок.
		 * Цена в корзине может быть различной из-за того, что в ней может понадобиться
		 * оригинальная стоимость товара, смотри настройку "Обновлять цены товаров в корзине".
		 * @return float
		 * @throws expectElementException
		 */
		public function getBasketPrice() {
			$updatePriceInBasket = Service::Registry()
				->get('//modules/emarket/update-order-item-price-in-basket');
			return $updatePriceInBasket ? $this->getElementPrice() : $this->getOriginalPrice();
		}

		/**
		 * Устанавливает оригинальную стоимость одного товарного наименования
		 * @param float $price стоимость
		 * @return $this
		 */
		public function setOriginalPrice($price) {
			$this->originalPrice = (float) $price;
			return $this;
		}

		/**
		 * Возвращает актуальную стоимость одного товарного наименования
		 * @return float
		 */
		public function getActualPrice() {
			return $this->actualPrice;
		}

		/**
		 * Устанавливает актуальную стоимость одного товарного наименования
		 * @param float $price стоимость
		 * @return $this
		 */
		public function setActualPrice($price) {
			$this->actualPrice = (float) $price;
			return $this;
		}

		/**
		 * Является ли товарное предложение цифровым
		 * @return bool
		 */
		public function getIsDigital() {
			return $this->isDigital;
		}

		/**
		 * Возвращает абсолютное значение скидки наименования
		 * @return float
		 */
		public function getDiscountValue() {
			return $this->discountValue;
		}

		/**
		 * Устанавливает абсолютное значение скидки наименования
		 * @param float $value значение скидки
		 */
		public function setDiscountValue($value) {
			$value = (float) $value;
			$originalPrice = $this->getOriginalPrice();

			if ($value > $originalPrice) {
				$value = $originalPrice;
			}

			$this->discountValue = (float) $value;
		}

		/**
		 * Возвращает страницу-источник данных для товарного наименования
		 * или `null`, если страницы уже не существует.
		 * @return null|iUmiHierarchyElement
		 * @throws coreException
		 */
		public function getRelatedProduct() {
			return $this->getItemElement(true);
		}

		/**
		 * Возвращает страницу-источник данных для товарного наименования.
		 * По умолчанию удаляет товарное наименование, если источник данных не найден.
		 * Если товарное наименование не нужно удалять, используйте метод
		 * @see orderItem::getRelatedProduct()
		 *
		 * @param bool $keepOrphanItem не удалять товарное наименование, если страница не найдена
		 * @return null|iUmiHierarchyElement
		 * @throws coreException
		 */
		public function getItemElement($keepOrphanItem = false) {
			$symlink = $this->getValue('item_link');
			$isFound = is_array($symlink) && umiCount($symlink) > 0;

			if ($isFound) {
				list($item) = $symlink;
				return $item;
			}

			if (!$keepOrphanItem) {
				$this->delete();
			}

			$resultItem = null;
			$emarket = cmsController::getInstance()->getModule('emarket');
			if ($emarket instanceof emarket) {
				$event = new umiEventPoint('orderItemGetElement');
				$event->setParam('orderItem', $this);
				$event->addRef('resultItem', $resultItem);
				$event->call();
			}

			return $resultItem;
		}

		/**
		 * Возвращает примененную скидку
		 * @return discount
		 */
		public function getDiscount() {
			return $this->discount;
		}

		/**
		 * Устанавливает скидку на товарное наименование
		 * @param itemDiscount $discount скидка
		 */
		public function setDiscount(itemDiscount $discount = null) {
			$this->discount = $discount;
		}

		/**
		 * Пересчитывает стоимость товарного наименования
		 * @param bool $recalculateDiscount нужно ли заново пересчитывать скидку
		 * @param bool $useAppliedDiscount нужно ли использовать уже примененную скидку
		 * или произвести поиск наиболее подходящей
		 * @return bool
		 * @throws coreException
		 * @throws privateException
		 */
		public function refresh($recalculateDiscount = true, $useAppliedDiscount = false) {
			$eventPoint = new umiEventPoint('orderItem_refresh');
			$eventPoint->setMode('before');
			$eventPoint->addRef('orderItem', $this);
			$eventPoint->call();

			$originalPrice = $this->getOriginalPrice();

			if ($recalculateDiscount) {
				$element = $this->getRelatedProduct();

				if (!$element) {
					return false;
				}

				if ($useAppliedDiscount && $this->getDiscount() instanceof itemDiscount) {
					$discount = $this->getDiscount();
				} else {
					$discount = itemDiscount::search($element);
				}

				if ($discount instanceof itemDiscount) {
					$actualPrice = $discount->recalcPrice($originalPrice);

					$pricesDiff = ($originalPrice - $actualPrice);
					$discountValue = ($pricesDiff < 0) ? 0 : $pricesDiff;

					$this->setDiscount($discount);
					$this->setDiscountValue($discountValue);
				} else {
					$actualPrice = $originalPrice;

					$this->setDiscount();
					$this->setDiscountValue(0);
				}
			} else {
				$actualPrice = $originalPrice - $this->getDiscountValue();
			}

			$totalActualPrice = $actualPrice * $this->amount;
			$totalOriginalPrice = $originalPrice * $this->amount;

			$eventPoint->setMode('after');
			$eventPoint->setParam('totalOriginalPrice', $totalOriginalPrice);
			$eventPoint->addRef('totalActualPrice', $totalActualPrice);
			$eventPoint->addRef('actualPrice', $actualPrice);
			$eventPoint->call();

			$this->actualPrice = $actualPrice;
			$this->totalOriginalPrice = $totalOriginalPrice;
			$this->totalActualPrice = $totalActualPrice;
			$this->commit();

			return true;
		}

		/** Применяет изменения товарного наименования */
		public function commit() {
			$object = $this->object;
			$object->item_price = $this->originalPrice;
			$object->item_actual_price = $this->actualPrice;
			$object->item_total_original_price = $this->totalOriginalPrice;
			$object->item_total_price = $this->totalActualPrice;
			$object->item_amount = $this->amount;
			$object->item_discount_id = ($this->discount instanceof discount ? $this->discount->getId() : false);
			$object->item_link = $this->itemElement;
			$object->setValue(self::ITEM_DISCOUNT_VALUE_FIELD_GUID, $this->discountValue);
			parent::commit();
		}

		/**
		 * Возвращает оригинальную стоимость товарного наименования
		 * @return float
		 * @throws expectElementException
		 */
		public function getElementPrice() {
			$element = $this->getRelatedProduct();

			if (!$element instanceof iUmiHierarchyElement) {
				throw new expectElementException(getLabel('error-expect-element'));
			}

			/** @var emarket $emarket */
			$emarket = cmsController::getInstance()->getModule('emarket');
			$price = $emarket->getPrice($element, true);

			if (Service::Registry()->get('//modules/emarket/update-order-item-price-in-basket')) {
				$this->setOriginalPrice($price);
			}

			return $price;
		}

		/**
		 * Конструктор.
		 * Косвенно вызывается через orderItem::get() и orderItem::create()
		 * @param iUmiObject $object объект-источник данных для товарного наименования
		 */
		protected function __construct(iUmiObject $object) {
			parent::__construct($object);

			$this->originalPrice = (float) $object->item_price;
			$this->totalOriginalPrice = (float) $object->item_total_original_price;
			$this->totalActualPrice = (float) $object->item_total_price;
			$this->amount = (int) $object->item_amount;
			$this->actualPrice = ((float) $object->item_actual_price) ?: (float) $this->calculateActualPrice();

			$this->discount = itemDiscount::get($object->item_discount_id);
			$this->itemElement = $object->item_link;
			$discountValue = $object->getValue(self::ITEM_DISCOUNT_VALUE_FIELD_GUID);

			if (!is_numeric($discountValue)) {
				$pricesDiff = ($this->totalOriginalPrice - $this->totalActualPrice);
				$discountValue = ($pricesDiff < 0) ? 0 : $pricesDiff;
			}

			$this->discountValue = (float) $discountValue;
		}

		/**
		 * Вычисляет актуальную стоимость одного товарного наименования
		 * @return float
		 */
		protected function calculateActualPrice() {
			return $this->getTotalActualPrice() / ($this->getAmount() ?: 1);
		}

		/**
		 * Возвращает наиболее выгодную скидку для покупателя на данное товарное предложение
		 * @return discount|null
		 * @throws privateException
		 */
		protected function searchDiscount() {
			$element = $this->getRelatedProduct();

			if ($element instanceof iUmiHierarchyElement) {
				$discount = itemDiscount::search($element);

				if ($discount instanceof discount) {
					return $discount;
				}
			}

			return null;
		}

		/**
		 * Возвращает идентификатор типа товарного наименования,
		 * подходящего для типа товара (объекта каталога)
		 * @param int $objectTypeId идентификатор объектного типа данных товара (объекта каталога)
		 * @return int|null
		 * @throws selectorException
		 */
		private static function getItemTypeId($objectTypeId) {
			static $cache = [];

			if (isset($cache[$objectTypeId])) {
				return $cache[$objectTypeId];
			}

			$prefix = self::getClassPrefix($objectTypeId);

			if ($prefix) {
				return $cache[$objectTypeId] = self::getItemTypeIdByClassPrefix($prefix);
			}

			return $cache[$objectTypeId] = null;
		}

		/**
		 * Возвращает идентификатор типа товарного наименования заказа по префиксу класса
		 * @param string $prefix префикс класса
		 * @return int|null
		 * @throws selectorException
		 */
		private static function getItemTypeIdByClassPrefix($prefix) {
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'item_type');
			$sel->where('class_name')->equals($prefix);
			$sel->option('load-all-props', false);
			$sel->option('no-length', true);
			$sel->limit(0, 1);
			return $sel->first() ? $sel->first()->id : null;
		}

		/**
		 * Возвращает префикс имени класса типа товарного наименования,
		 * подходящего для типа товара (объекта каталога)
		 * @param int $objectTypeId идентификатор объектного типа данных товара (объекта каталога)
		 * @return string
		 */
		private static function getClassPrefix($objectTypeId) {
			static $cache = [];

			if (isset($cache[$objectTypeId])) {
				return $cache[$objectTypeId];
			}

			/** @var iUmiObjectType $objectType */
			$objectType = selector::get('object-type')->id($objectTypeId);
			$prefixes = self::getClassPrefixes();

			foreach ($prefixes as $prefix => $conds) {
				foreach ($conds as $type => $values) {
					foreach ($values as $value) {
						if ($type == 'fields' && $objectType->getFieldId($value)) {
							return $cache[$objectTypeId] = $prefix;
						}

						if ($type == 'groups' && $objectType->getFieldsGroupByName($value)) {
							return $cache[$objectTypeId] = $prefix;
						}
					}
				}
			}

			return $cache[$objectTypeId] = '';
		}

		/**
		 * Возвращает список настроек типов товарных наименований
		 * из config.ini:
		 *
		 * Формат значения:
		 *
		 * array(
		 *    префикс класса типа товарного наименования => (
		 *      тип значения => значение
		 *    )
		 * )
		 *
		 * Пример значения:
		 *
		 * array(
		 *    'optioned' => (
		 *      'groups' => array(
		 *        'catalog_option_props'
		 *      )
		 *    )
		 * )
		 *
		 * В config.ini значение выглядит так:
		 *
		 * [modules]
		 * emarket.order-types.optioned.groups[] = "catalog_option_props"
		 *
		 * @return array
		 */
		private static function getClassPrefixes() {
			static $result = null;

			if (is_array($result)) {
				return $result;
			}

			$result = [];
			$req = 'emarket.order-types.';
			$l = mb_strlen($req);

			$config = mainConfiguration::getInstance();
			$options = $config->getList('modules');

			foreach ($options as $option) {
				if (mb_substr($option, 0, $l) != $req) {
					continue;
				}

				$optionArr = explode('.', mb_substr($option, $l));

				if (umiCount($optionArr) != 2) {
					continue;
				}

				list($classPrefix, $valueType) = $optionArr;

				$result[$classPrefix][$valueType] = $config->get('modules', $option);
			}
			return $result;
		}

		/** @deprecated */
		public function getItemPrice() {
			return $this->getOriginalPrice();
		}

		/** @deprecated */
		public function setItemPrice($price) {
			$this->setOriginalPrice($price);
		}
	}
