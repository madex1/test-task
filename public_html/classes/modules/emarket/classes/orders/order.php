<?php
	/** Класс, предоставляющий заказ, либо корзину заказов в интернет-магазине */
	class order extends umiObjectProxy {
		protected $items = Array(), $actualPrice, $originalPrice, $totalAmount, $discount, $domainId;
		/** @var float $discountValue абсолютное значение скидки заказа */
		protected $discountValue;
		/** @var string ORDER_DISCOUNT_VALUE_FIELD_GUID гуид поля объекта-источника для заказа, в котором хранится абсолютное значение скидки заказа */
		const ORDER_DISCOUNT_VALUE_FIELD_GUID = 'order_discount_value';

		/** @var string DUMMY_NAME имя заказа заглушки */
		const DUMMY_NAME = 'dummy';

		/**
		 * Получить экземпляр заказа по его id. Если id заказа false, то метод вернет текущий объект со статусом "в корзине".
		 * Если такого объекта еще нет, то он его создаст
		 * @param bool|int $orderId = false id заказа
		 * @param bool $ignoreCache нужно ли игнорировать кэш при получении объекта заказа
		 * @return null|order
		 * @throws publicException
		 */
		public static function get($orderId = false, $ignoreCache = false) {
			static $cache = array();

			if(!$orderId) {
				return $object = self::create();
			}

			if(isset($cache[$orderId]) && !$ignoreCache) {
				return $cache[$orderId];
			}

			$objects = umiObjectsCollection::getInstance();
			$object = $objects->getObject($orderId);

			if(!$object instanceof iUmiObject) {
				return null;
			}

			return $cache[$orderId] = new order($object);
		}

		/**
		 * Создает новый заказ и возвращает его id
		 * @param bool $useDummyOrder использовать заказ-заглушку вместо создания заказа
		 * @param bool|int $domainId идентификатор домена, к которому будет относится заказ
		 * Если не передан - возьмет текущий домен
		 * @return null|order
		 * @throws coreException
		 * @throws publicException
		 * @throws selectorException
		 */
		public static function create($useDummyOrder = false, $domainId = false) {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$objects = umiObjectsCollection::getInstance();

			if ($domainId === false) {
				$domainId = UmiCms\Service::DomainDetector()->detectId();
			}

			$orderTypeId = $objectTypes->getTypeIdByGUID('emarket-order');

			if($useDummyOrder) {
				$sel = new selector('objects');
				$sel->types('object-type')->id($orderTypeId);
				$sel->where('name')->equals(order::DUMMY_NAME);
				$sel->where('domain_id')->equals($domainId);
				$sel->where('order_items')->isnull();
				$sel->option('no-length')->value(true);
				$sel->option('return')->value('id');
				$sel->limit(0, 1);
				$result = $sel->result();
				
				if(count($result) > 0 && isset($result[0]['id'])) {
					$orderId = $result[0]['id'];
				} else {
					$orderId = $objects->addObject('dummy', $orderTypeId);
					$order = $objects->getObject($orderId);
					if($order instanceof iUmiObject == false) {
						throw new publicException("Can't load dummy object for order #{$orderId}");
					} else {
						$order->setValue('domain_id', $domainId);
						$order->commit();
					}
				}
				return self::get($orderId);
			}

			$managerId = 0;
			$statusId = self::getStatusByCode('basket');
			$customer = customer::get();
			$createTime = time();

			$orderId = $objects->addObject('', $orderTypeId);
			$order = $objects->getObject($orderId);
			if($order instanceof iUmiObject == false) {
				throw new publicException("Can't load created object for order #{$orderId}");
			}
			$order->domain_id = $domainId;
			$order->manager_id = $managerId;
			$order->status_id = $statusId;
			$order->customer_id = $customer->getId();
			$order->order_create_date = $createTime;
			$order->commit();
			
			$customer->setLastOrder($orderId, $domainId);

			return self::get($orderId);
		}

		/**
		* Получить id объекта статуса заказа
		* @param String $codename код статуса заказа
		* @param String $statusClass = 'order_status' группа статуса
		* @return Integer id объекта статуса заказа
		*/
		public static function getStatusByCode($codename, $statusClass = 'order_status') {
			static $cache = array();

			if (isset($cache[$codename][$statusClass])) {
				return $cache[$codename][$statusClass];
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', $statusClass);
			$sel->where('codename')->equals($codename);
			$sel->option('no-length')->value(true);

			return $cache[$codename][$statusClass] = $sel->first ? $sel->first->id : false;
		}

		/**
		* Получить код статуса заказа
		* @param Integer $id идентификатор объекта статуса заказа
		* @return String код статуса заказа
		*/
		public static function getCodeByStatus($id) {
			static $cache = array();

			if (isset($cache[$id])) {
				return $cache[$id];
			}

			$status = selector::get('object')->id($id);
			return $cache[$id] = $status ? $status->codename : false ;
		}


		/**
		* Получить список наименований в заказе
		* @return Array массив, состоящий из экземпляров или класса или потомков класса orderItem
		*/
		public function getItems() {
			return $this->items;
		}


		/**
		* Добавить наименование в заказ
		* @param orderItem $orderItem наменование заказа (объект класса orderItem, либо его потомок)
		*/
		public function appendItem(orderItem $orderItem) {
			foreach($this->items as $item) {
				if($item->getId() == $orderItem->getId()) {
					return false;
				}
			}
			$orderItem->refresh();
			$this->items[] = $orderItem;
		}


		/**
		* Удалить наименование из заказа. После удаления из заказа объект orderItem будет уничтожен
		* @param orderItem $orderItem наименование в заказе  (объект класса orderItem, либо его потомок)
		*/
		public function removeItem(orderItem $orderItem) {
			foreach($this->items as $i => $item) {
				if($item->getId() == $orderItem->getId()) {
					unset($this->items[$i]);
					$orderItem->remove();
					return true;
				}
			}
			return false;
		}


		/**
		* Получить экземпляр наименования заказа по id
		* @param Integer $itemId
		* @return orderItem|Boolean
		*/
		public function getItem($itemId) {
			foreach($this->items as $item) {
				if($item->getId() == $itemId) return $item;
			}
			return false;
		}


		/**
		 * Проверяет, есть ли наименования в заказе
		 *
		 * @return bool true, если заказ пустой, иначе false
		 */
		public function isEmpty() {
			return (count($this->items) == 0);
		}

		/**
		 * Определяет является ли заказ заглушкой
		 * @return bool
		 */
		public function isDummy() {
			return $this->getName() === self::DUMMY_NAME;
		}


		/** Очистить список товаров в заказе. При этом будут уничтожены все orderItem'ы */
		public function earse() {
			$this->items = Array();
		}


		/**
		* Получить текущий статус заказа
		* @return Integer id объекта-статуса заказа
		*/
		public function getOrderStatus() {
			return $this->object->status_id;
		}


		/**
		* Изменить текущий статус заказа
		* @param Integer $statusId id объекта-статуса заказа
		*/
		public function setOrderStatus($newStatusId) {
			if($newStatusId && !is_numeric($newStatusId)) {
				$newStatusId = self::getStatusByCode($newStatusId, 'order_status');
				if(!$newStatusId) {
					return;
				}
			}
			$oldStatusId = $this->object->status_id;

			$event = new umiEventPoint('order-status-changed');
			$event->addRef('order', $this);
			$event->setParam('old-status-id', $oldStatusId);
			$event->setParam('new-status-id', $newStatusId);

			if($oldStatusId != $newStatusId) {
				$event->setMode('before');
				$event->call();
			}

			$this->object->status_id = $newStatusId;

			if($oldStatusId != $newStatusId) {
				$event->setMode('after');
				$event->call();

				$status = self::getCodeByStatus($newStatusId);
				switch($status) {
					case 'waiting': {
						$this->reserve();
						break;
					}

					case 'canceled': {
						$this->unreserve();
						break;
					}

					case 'ready': {
						$this->writeOff();
						break;
					}
				}
			}
		}


		/**
		* Получить текущий статус оплаты заказа
		* @return Integer id объекта-статуса оплаты
		*/
		public function getPaymentStatus() {
			return $this->object->payment_status_id;
		}


		/**
		* Изменить текущий статус оплаты заказа
		* @param Integer $statusId id объекта-статуса оплаты
		*/
		public function setPaymentStatus($newStatusId) {
			if($newStatusId && !is_numeric($newStatusId)) {
				$statusCode  = $newStatusId;
				$newStatusId = self::getStatusByCode($newStatusId, 'order_payment_status');
			} else {
				$statusCode  = self::getCodeByStatus($newStatusId);
			}
			$oldStatusId = $this->object->payment_status_id;

			$event = new umiEventPoint('order-payment-status-changed');
			$event->addRef('order', $this);
			$event->setParam('old-status-id', $oldStatusId);
			$event->setParam('new-status-id', $newStatusId);

			if($oldStatusId != $newStatusId) {
				$event->setMode('before');
				$event->call();
			}

			$this->object->payment_status_id = $newStatusId;

			if($oldStatusId != $newStatusId) {
				$event->setMode('after');
				$event->call();
			}

			switch($statusCode) {
				case 'initialized' : $this->setOrderStatus('payment');   break;
				case 'declined'    : $this->setOrderStatus('execution'); break;
				case 'accepted'    : {
					$this->object->payment_date = new umiDate();
					$this->order();
					break;
				}
			}
		}


		/**
		* Получить текущий статус доставки заказа
		* @return Integer id объекта-статуса доставки
		*/
		public function getDeliveryStatus() {
			return $this->object->order_delivery_props;
		}


		/**
		* Изменить текущй статус доставки заказа
		* @param Integer $statusId id объекта-статуса доставки
		*/
		public function setDeliveryStatus($newStatusId) {
			if($newStatusId && !is_numeric($newStatusId))
				$newStatusId = self::getStatusByCode($newStatusId, 'order_delivery_status');
			$oldStatusId = $this->object->delivery_status_id;

			$event = new umiEventPoint('order-delivery-status-changed');
			$event->addRef('order', $this);
			$event->setParam('old-status-id', $oldStatusId);
			$event->setParam('new-status-id', $newStatusId);

			if($oldStatusId != $newStatusId) {
				$event->setMode('before');
				$event->call();
			}

			$this->object->delivery_status_id = $newStatusId;

			if($oldStatusId != $newStatusId) {
				$event->setMode('after');
				$event->call();
			}
		}


		/**
		* Получить цену всего заказа с учетом скидки на этот заказ
		* @return Float цена с учетом скидки на заказ
		*/
		public function getActualPrice() {
			return $this->actualPrice;
		}

		/**
		* Получить цену всего заказа без учета скидки на этот заказ
		* @return Float цена без учета скидки на заказ
		*/
		public function getOriginalPrice() {
			return $this->originalPrice;
		}

		/**
		* Получить количество наименований в заказе
		* @return Integer количество наименований в заказе
		*/
		public function getTotalAmount() {
			return $this->totalAmount;
		}

		/**
		* Получить стоимость доставки
		* @return Integer стоимость доставки
		*/
		public function getDeliveryPrice() {
			return $this->delivery_price;
		}

		/** Пересчитать содержимое корзины */
		public function refresh() {
			$object = $this->object; $items = $this->getItems();
			$originalPrice = 0;
			$totalAmount = 0;

			$eventPoint = new umiEventPoint("order_refresh");
			$eventPoint->setMode('before');
			$eventPoint->addRef("order", $object);
			$eventPoint->setParam("items", $items);
			$eventPoint->call();

			$recalculateDiscount = emarket::isBasket($this);

			foreach($items as $item) {
				$succ = $item->refresh($recalculateDiscount);
				if ($succ === false) {
					$this->removeItem($item);
					continue;
				}
				$originalPrice += $item->getTotalActualPrice();
				$totalAmount += $item->getAmount();
			}

			if ($recalculateDiscount) {
				$discount = $this->searchDiscount();

				if ($discount instanceof orderDiscount) {
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
				$discountValue = $this->getDiscountValue();
				$actualPrice = $originalPrice - $discountValue;
			}

			$actualPrice += (float) $this->delivery_price;
			$actualPrice -= (float) $this->getBonusDiscount();

			$eventPoint->setMode('after');
			$eventPoint->setParam("originalPrice", $originalPrice);
			$eventPoint->setParam("totalAmount", $totalAmount);
			$eventPoint->addRef("actualPrice", $actualPrice);
			$eventPoint->call();

			$this->originalPrice = $originalPrice;
			$this->actualPrice = $actualPrice;
			$this->totalAmount = $totalAmount;

			$this->commit();
		}

		/**
		 * Возвращает абсолютное значение скидки заказа
		 * @return float
		 */
		public function getDiscountValue() {
			return $this->discountValue;
		}

		/**
		 * Устанавливает абсолютное значение скидки заказа
		 * @param float $value значение скидки
		 */
		public function setDiscountValue($value) {
			$value = (float) $value;
			$orderPrice = $this->getOriginalPrice();

			if ($value > $orderPrice) {
				$value = $orderPrice;
			}

			$this->discountValue = (float) $value;
		}

		/**
		 * Возвращает номер заказа
		 * @return int
		 */
		public function getNumber() {
			return $this->object->number;
		}


		/**
		* Получить id клиента. Это может быть как id пользователя, так и id временного покупателя
		* @return Integer id объекта-клиента
		*/
		public function getCustomerId() {
			return $this->object->customer_id;
		}

		/**
		* Получить домен, в котором производится заказ
		* @return domain домена
		*/
		public function getDomain() {
			return UmiCms\Service::DomainCollection()
				->getDomain($this->domainId);
		}


		/**
		* Изменить домен, в котором производится заказ
		* @param domain $domain домена
		*/
		public function setDomainId(domain $domain) {
			$this->domainId = $domain->getId();
		}

		/**
		* Получить текущую скидку на этот заказ
		* @return discount скидка на заказ
		*/
		public function getDiscount() {
			return $this->discount;
		}

		/**
		* Назначить скидку на заказ
		* @param discount $discount скидка на заказ
		*/
		public function setDiscount(discount $discount = null) {
			if($discount && ($discount->validate($this) == false)) {
				$discount = null;
			}
			$this->discount = $discount;
		}
		
		/**
		* Получить размер оплаты бонусами
		* @return float размер оплаты
		*/
		public function getBonusDiscount() {
			return $this->object->bonus;
		}

		/**
		* Установить оплату бонусом
		* @param float $bonus сумма списываемых баллов
		*/
		public function setBonusDiscount($bonus) {
			
			$bonus = $bonus > 0 ? $bonus : 0;
			
			$emarket = cmsController::getInstance()->getModule('emarket');
		
			$defaultCurrency = $emarket->getDefaultCurrency();
			$currency = $emarket->getCurrentCurrency();
			
			$bonus = $bonus * $currency->nominal * $currency->rate;
			$bonus = $bonus  / $defaultCurrency->rate / $defaultCurrency->nominal;
			$bonus = round($bonus, 2);
				
			$bonus = $bonus > $this->actualPrice ? $this->actualPrice : $bonus; 
			 
			$customerId = $this->getCustomerId();
			$customer = umiObjectsCollection::getInstance()->getObject($customerId);
							
			if ($this->object->bonus > 0) {
				$customer->bonus = $customer->bonus + $this->object->bonus;
				$customer->spent_bonus = $customer->spent_bonus - $this->object->bonus;
			}
			
			if ($customer->bonus < $bonus) $bonus = $customer->bonus; 
							
			$this->object->bonus = $bonus;
			$customer->bonus = $customer->bonus - $bonus;
			$customer->spent_bonus = $customer->spent_bonus + $bonus;
			$customer->commit();
		}

		/** Сгенерировать номер заказа */
		public function generateNumber() {
			$config = mainConfiguration::getInstance();
			$className = $config->get('modules', 'emarket.numbers') . 'OrderNumber';
			if(class_exists($className)) {
				$object = new $className($this);
				return $object->number();
			} else {
				throw new coreException("Can't load order numbers generator. Check modules.emarket.numbers config setting");
			}
		}

		public function order() {
			$status = $this->getOrderStatus();
			if(is_null($status) || self::getCodeByStatus($status) == 'payment' || self::getCodeByStatus($status) == 'editing') {
				if (!$this->object->number) $this->generateNumber();
				$this->object->order_date = time();
				$this->setOrderStatus('waiting');
				$this->object->commit();
				customer::get()->freeze();
				return true;
			} else return false;
		}

		public function commit() {
			$object = $this->object;

			$object->total_original_price = $this->originalPrice;
			$object->total_price = $this->actualPrice;
			$object->total_amount = $this->totalAmount;
			$object->domain_id = $this->domainId;
			$object->order_discount_id = ($this->discount ? $this->discount->getId() : false);
			$object->setValue(self::ORDER_DISCOUNT_VALUE_FIELD_GUID, $this->discountValue);
			$session = \UmiCms\Service::Session();
			$object->http_referer = strlen(trim($object->http_referer)) == 0 ? urldecode($session->get("http_referer")) : $object->http_referer;
			$object->http_target = strlen(trim($object->http_target)) == 0 ?  urldecode($session->get("http_target")) : $object->http_target;
			$adv = $this->getAdvParamFromUrl($object->http_target, $object->http_referer);
			$object->source_domain = array_key_exists('utm_source', $adv) ? $adv['utm_source'] : '';
			$object->utm_medium = array_key_exists('utm_medium', $adv) ? $adv['utm_medium'] : '';
			$object->utm_term = array_key_exists('utm_term', $adv) ? $adv['utm_term'] : '';
			$object->utm_campaign = array_key_exists('utm_campaign', $adv) ? $adv['utm_campaign'] : '';
			$object->utm_content = array_key_exists('utm_content', $adv) ? $adv['utm_content'] : '';

			$this->applyItems();

			parent::commit();
		}


		/**
		* Получить заказ по объекту заказа
		* @param Integer $object объект заказа
		*/
		protected function __construct(umiObject $object) {
			parent::__construct($object);

			$this->totalAmount = (int) $object->total_amount;
			$this->originalPrice = (float) $object->total_original_price;
			$this->actualPrice = (float) $object->total_price;
			$this->domainId = $object->domain_id;
			$this->discount = orderDiscount::get($object->order_discount_id);
			$discountValue = $object->getValue(self::ORDER_DISCOUNT_VALUE_FIELD_GUID);

			if (!is_numeric($discountValue)) {
				$pricesDiff = ($this->originalPrice - $this->actualPrice);
				$discountValue = ($pricesDiff < 0) ? 0 : $pricesDiff;
			}

			$this->discountValue = (float) $discountValue;
			$this->readItems();
		}

		/** Загрузить список наименований в заказе из объекта заказа */
		protected function readItems() {
			$objectItems = $this->object->order_items;
			$items = array();
			foreach($objectItems as $objectId) {
				try {
					$items[] = orderItem::get($objectId);
				} catch (privateException $e) {}
			}
			$this->items = $items;
		}

		/** Сохранить данные о наименованиях заказа в объект заказа */
		protected function applyItems() {
			$values = Array();
			foreach($this->items as $item) {
				$values[] = $item->getId();
			}
			$this->object->order_items = $values;
		}

		/**
		* Определить скидку для этого заказа
		* @param orderDiscount $discount скидка заказа
		*/
		public function searchDiscount() {
			$discount = orderDiscount::search($this);
			return ($discount instanceof orderDiscount) ? $discount : null;
		}


		public function reserve($reserve = true) {
			if($this->is_reserved == $reserve) {
				return false;
			}

			$primaryStore = $this->getPrimaryStore();
			if(!$primaryStore) return false;

			foreach($this->getItems() as $item) {
				if($element = $item->getItemElement()) {
					$amount = $item->getAmount();
					$storesState = $element->getValue('stores_state', array('filter' => array('rel' => $primaryStore->id)));
					if(count($storesState)) {
						$total = $storesState[0];
						$total = (int) getArrayKey($total, 'int');
					} else {
						$total = 0;
					}

					$reserved = (int) $element->reserved + $amount * ($reserve ? 1 : -1);
					$element->reserved = ($reserved > 0) ? ($reserved > $total ? $total : $reserved) : 0;
					$oldFilter = umiObjectProperty::$IGNORE_FILTER_INPUT_STRING;
					umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = true;
					$element->commit();
					umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = $oldFilter;
				}
			}
			$this->is_reserved = $reserve;
			$this->commit();
			return true;
		}

		public function unreserve() {
			return $this->reserve(false);
		}

		public function writeOff() {
			if(!$this->is_reserved) return false;

			$primaryStore = $this->getPrimaryStore();
			if(!$primaryStore) return false;

			foreach($this->getItems() as $item) {
				if($element = $item->getItemElement()) {
					$amount = $item->getAmount();
					$storesState = $element->getValue('stores_state');
					foreach($storesState as $i => $storeState) {
						$total = getArrayKey($storeState, 'int');
						$id = getArrayKey($storeState, 'rel');
						if($primaryStore->id == $id) {
							$storesState[$i]['int'] = $total - $amount;
							$element->setValue('stores_state', $storesState);
							break;
						}
					}

					$reserved = (int) $element->reserved - $amount;
					$element->reserved = ($reserved > 0) ? ($reserved) : 0;
					$element->commit();
				}
			}

			$this->is_reserved = false;
			$this->commit();
			return true;
		}


		private function getPrimaryStore() {
			$stores = new selector('objects');
			$stores->types('object-type')->name('emarket', 'store');
			$stores->where('primary')->equals(true);
			$stores->option('no-length')->value(true);
			$stores->option('load-all-props')->value(true);
			return $stores->first;
		}

		/**
		 * Разбор параметров рекламной компании, если рекламная компания не найдена - разбирается referer
		 * @param $url адрес на который пришёл пользователь
		 * @param null $refer адрес с которого пришёл пользователь
		 * @return array
		 */
		public function getAdvParamFromUrl($url, $refer=null) {
			$params = array();
			$parseUrl = parse_url(urldecode($url));
			if (isset($parseUrl['query'])) {
				$res = explode('&amp;', $parseUrl['query']);
				if (!empty($res[0])) {
					foreach($res as $r) {
						$param = explode('=', $r);
						if (count($param) > 1) {
							$params[$param[0]] = $param[1];
						}
					}
				}
			}
			if (count($params) <= 0 && !is_null($refer)) {
				$params = $this->parseUrlReferer($refer);
			}
			return $params;
		}

		/**
		 * Поиск в URL ключевых слов и источника перехода
		 * @param $url адрес с которого пришёл пользователь (referer)
		 * @return array
		 */
		public function parseUrlReferer($url) {
			$query = array();
			switch(true) {
				case (strpos($url, 'yandex') != 0) : {
					preg_match('"text=((.*?)[^&]*)"', $url, $arr);
					$query = array(
						'utm_source' => 'yandex',
						'utm_medium' => 'organic',
						'utm_term' => count($arr) > 0 ? $arr[1] : ''
					);
					break;
				}
				case (strpos($url, 'google') != 0) : {
					preg_match('"q=((.*?)[^&]*)"', $url, $arr);
					$query = array(
						'utm_source' => 'google',
						'utm_medium' => 'organic',
						'utm_term' => count($arr) > 0 ? $arr[1] : ''
					);
					break;
				}
				case (strpos($url, 'rambler') != 0) : {
					preg_match('"query=((.*?)[^&]*)"', $url, $arr);
					$query = array(
						'utm_source' => 'rambler',
						'utm_medium' => 'organic',
						'utm_term' => count($arr) > 0 ? $arr[1] : ''
					);
					break;
				}
				case (strpos($url, 'nigma') != 0) : {
					preg_match('"s=((.*?)[^&]*)"', $url, $arr);
					$query = array(
						'utm_source' => 'nigma',
						'utm_medium' => 'organic',
						'utm_term' => count($arr) > 0 ? $arr[1] : ''
					);
					break;
				}
				default: {
				$urlRes = parse_url($url);
				if (!empty($urlRes['host']) > 0) {
					$query = array(
						'utm_source' => $urlRes['host'],
						'utm_medium' => 'referal'
					);
				}
				}
			}
			return $query;
		}
	};
?>
