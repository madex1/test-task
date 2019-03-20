<?php

	use UmiCms\Service;

	/**
	 * Класс покупателя интернет магазина.
	 * Его источником данных может быть одна из сущностей системы:
	 * 1) Пользователь одноименного модуля;
	 * 2) Незарегистрированный покупатель модуля "Интернет магазин";
	 */
	class customer extends umiObjectProxy {

		/**
		 * @var int $defaultExpiration время жизни незарегистрированного покупателя по умолчанию, в секундах
		 * Напрямую не используется, @see customer::getExpirationTime()
		 */
		public static $defaultExpiration = 2678400;

		/** @const string FIRST_NAME_FIELD имя поля имя покупателя заказа */
		const FIRST_NAME_FIELD = 'fname';

		/** @const string LAST_NAME_FIELD имя поля фамилия покупателя заказа */
		const LAST_NAME_FIELD = 'lname';

		/** @const string FATHER_NAME_FIELD имя поля отчество покупателя заказа */
		const FATHER_NAME_FIELD = 'father_name';

		/** @const string GUEST_EMAIL_FIELD имя поля почтового ящика незарегистрированного покупателя заказа */
		const GUEST_EMAIL_FIELD = 'email';

		/** @const string REGISTERED_EMAIL_FIELD имя поля почтового ящика зарегистрированного покупателя заказа */
		const REGISTERED_EMAIL_FIELD = 'e-mail';

		/** @const string PHONE_NAME_FIELD имя поля телефон покупателя заказа */
		const PHONE_NAME_FIELD = 'phone';

		/** @const string FULL_NAME_SEPARATOR разделитель частей полного имени */
		const FULL_NAME_PARTS_SEPARATOR = ' ';

		/**
		 * Конструктор
		 * @param iUmiObject $object сущность-источник данных для покупателя
		 */
		public function __construct(iUmiObject $object) {
			parent::__construct($object);
		}

		/**
		 * Возвращает время жизни незарегистрированного покупателя по умолчанию, в секундах
		 * @return int
		 */
		public static function getExpirationTime() {
			$umiConfig = mainConfiguration::getInstance();

			$time = $umiConfig->get('modules', 'emarket.customer-expiration-time');
			if (!is_numeric($time)) {
				$time = self::$defaultExpiration;
			}

			return (int) $time;
		}

		/**
		 * Возвращает покупателя по id, если он не задан - пытается определить его самостоятельно
		 * @param bool $nocache не использовать внутренний кеш
		 * @param bool|int $customerId идентификатор сущности-источника данных для покупателя
		 * @return customer
		 */
		public static function get($nocache = false, $customerId = false) {
			static $customer;

			if (!$nocache && $customer instanceof customer) {
				$cachedId = $customer->getId();
				$objectCollection = umiObjectsCollection::getInstance();
				$correctCache = ($objectCollection->isLoaded($cachedId) || $objectCollection->isExists($cachedId));

				if ($correctCache) {
					return $customer;
				}

				$customer = null;
			}

			$auth = Service::Auth();

			if ($auth->isLoginAsGuest()) {
				$customerObject = self::getCustomerObject($customerId);
			} else {
				$objects = umiObjectsCollection::getInstance();
				$customerObjectId = ($customerId === false) ? $auth->getUserId() : $customerId;
				$customerObject = $objects->getObject($customerObjectId);
			}

			if ($customerObject instanceof iUmiObject) {
				$customer = new customer($customerObject);
				$customer->tryMerge();
				return $customer;
			}
		}

		/**
		 * Возвращает ранее сохраненный идентификатор покупателя
		 * @return int
		 */
		public static function getTransientCustomerId() {
			return (int) Service::CookieJar()->getDecrypted('customer-id');
		}

		/**
		 * @internal
		 * Определяет является ли покупатель авторизованным пользователем
		 * @return bool
		 */
		public function isUser() {
			return $this->getObject()->getTypeGUID() === 'users-user';
		}

		/**
		 * Осуществляет попытку перенести заказы незарегистрированного покупателя
		 * к заказам авторизованного пользователя
		 */
		public function tryMerge() {

			if (!$this->shouldMergeGuestOrders()) {
				return;
			}

			$this->merge(self::getCustomerObject());
		}

		/**
		 * Определяет, нужно ли переносить заказы незарегистрированного покупателя
		 * к заказам авторизованного пользователя.
		 * @return bool
		 */
		private function shouldMergeGuestOrders() {

			if (!Service::Auth()->isAuthorized()) {
				return false;
			}

			if (!self::getTransientCustomerId()) {
				return false;
			}

			$guestCustomer = self::getCustomerObject();
			if (!$guestCustomer instanceof iUmiObject) {
				return false;
			}

			if ($guestCustomer->getTypeGUID() !== 'emarket-customer') {
				return false;
			}

			if ($guestCustomer->getId() == $this->getId()) {
				return false;
			}

			if (permissionsCollection::getInstance()->isAdmin()) {
				return (bool) Service::Registry()->get('//modules/emarket/merge-guest-orders-for-admins');
			}

			return true;
		}

		/**
		 * @internal
		 * Внимание: не используйте эту функцию в прикладном коде,
		 * используйте @see customer::tryMerge().
		 *
		 * Переносит заказы незарегистрированного покупателя к заказам авторизованного пользователя.
		 * Объект незарегистрированного покупателя после этого будет уничтожен.
		 * @param iUmiObject $customer объект незарегистрированного покупателя
		 * @throws selectorException
		 * @throws coreException
		 */
		public function merge(iUmiObject $customer) {

			$domainId = Service::DomainDetector()->detectId();

			$query = new selector('objects');
			$query->types('object-type')->name('emarket', 'order');
			$query->where('customer_id')->equals($customer->getId());
			$query->where('domain_id')->equals($domainId);
			$query->option('load-all-props')->value(true);
			$query->order('id')->desc();
			$guestBasketList = $query->result();

			$userBasket = $this->getBasketByDomainId($domainId);

			/** @var iUmiObject $guestBasket */
			foreach ($guestBasketList as $guestBasket) {

				if (!$guestBasket->getValue('status_id')) {
					$this->mergeBasket($guestBasket, $userBasket);
					continue;
				}

				$guestBasket->setValue('customer_id', $this->getId());
				$guestBasket->commit();
			}

			if (!(defined('UMICMS_CLI_MODE') && UMICMS_CLI_MODE)) {
				Service::CookieJar()->remove('customer-id');
			}

			$customer->delete();
		}

		/**
		 * Возвращает текущую корзину пользователя для заданного домена (сайта).
		 * Если корзина еще не была создана - создаст ее.
		 * @param int $domainId идентификатор домена (сайта)
		 * @param bool $useDummyOrder использовать заказ-заглушку вместо создания заказа,
		 * если текущая корзина не была создана
		 * @return null|order
		 */
		public function getBasketByDomainId($domainId, $useDummyOrder = false) {
			$basketId = $this->getLastOrder($domainId);

			if ($basketId) {
				return order::get($basketId);
			}

			return order::create($useDummyOrder, $domainId);
		}

		/**
		 * Переносит товарные наименования из корзины незарегистрированного покупателя
		 * в заказ зарегистрированного пользователя.
		 * В конце операции удаляет корзину незарегистрированного покупателя.
		 * @param iUmiObject $guestBasket корзина незарегистрированного покупателя.
		 * @param order $userBasket корзина зарегистрированного пользователя
		 */
		protected function mergeBasket(iUmiObject $guestBasket, order $userBasket) {
			$orderItems = $guestBasket->getValue('order_items');

			if (!is_array($orderItems) || umiCount($orderItems) == 0) {
				return;
			}

			foreach ($orderItems as $orderItemId) {
				/** @var orderItem $orderItem */
				$orderItem = orderItem::get($orderItemId);

				if ($orderItem instanceof orderItem) {
					$userBasket->appendItem($orderItem);
				}
			}

			$userBasket->commit();
			$guestBasket->delete();
		}

		/** Удаляет время жизни покупателя. */
		public function freeze() {
			/** @var umiObjectsExpiration $expiration */
			$expiration = umiObjectsExpiration::getInstance();
			$expiration->clear($this->getId());
		}

		/**
		 * Возвращает идентификатор сущности-источника данных для покупателя
		 * @return string
		 */
		public function __toString() {
			return (string) $this->getId();
		}

		/**
		 * Возвращает полное имя
		 * @return string
		 */
		public function getFullName() {
			$fieldsNamesList = [
				self::LAST_NAME_FIELD,
				self::FIRST_NAME_FIELD,
				self::FATHER_NAME_FIELD
			];

			$nameParts = [];
			$customer = $this->getObject();

			foreach ($fieldsNamesList as $fieldName) {
				$nameParts[] = (string) $customer->getValue($fieldName);
			}

			return implode(self::FULL_NAME_PARTS_SEPARATOR, $nameParts);
		}

		/**
		 * Возвращает почтовый ящик
		 * @return string
		 */
		public function getEmail() {
			return (string) $this->getObject()
				->getValue($this->getEmailFieldName());
		}

		/**
		 * Устанавливает почтовый ящик
		 * @param string $email почтовый ящик
		 * @return $this
		 * @throws wrongParamException
		 */
		public function setEmail($email) {
			if (!umiMail::checkEmail($email)) {
				throw new wrongParamException('Incorrect email given');
			}

			$this->getObject()
				->setValue($this->getEmailFieldName(), $email);
			return $this;
		}

		/**
		 * Возвращает номер телефона
		 * @return string
		 */
		public function getPhone() {
			return (string) $this->getObject()
				->getValue(self::PHONE_NAME_FIELD);
		}

		/**
		 * @deprecated
		 * Возвращает идентификатор валюты, выбранной пользователем
		 * @return int
		 * @throws coreException
		 * @throws privateException
		 */
		public function getCurrencyId() {
			return Service::CurrencyFacade()
				->getCurrent()
				->getId();
		}

		/**
		 * @deprecated
		 * Устанавливает идентификатор валюты, выбранной пользователем
		 * @param int|null $id идентификатор валюты, выбранной пользователем
		 * @return $this
		 * @throws privateException
		 * @throws selectorException
		 * @throws wrongParamException
		 */
		public function setCurrencyId($id = null) {
			$currencyFacade = Service::CurrencyFacade();
			$currency = $currencyFacade->get($id);

			if ($currency) {
				$currencyFacade->setCurrent($currency);
			}

			return $this;
		}

		/**
		 * Возвращает имя поля, которое хранит почтовый ящик
		 * @return string
		 */
		protected function getEmailFieldName() {
			return $this->isUser() ? self::REGISTERED_EMAIL_FIELD : self::GUEST_EMAIL_FIELD;
		}

		/**
		 * Возвращает объект - источник данных покупателя.
		 * При необходимости создает нового незарегистрированного покупателя.
		 * Обновляет время жизни незарегистрированного покупателя.
		 * @param bool|int $customerId идентификатор незарегистрированного покупателя
		 * @return iUmiObject
		 */
		protected static function getCustomerObject($customerId = false) {
			if ($customerId === false) {
				$customerId = self::getTransientCustomerId();
			}

			$customer = selector::get('object')->id($customerId);
			$umiTypesHelper = umiTypesHelper::getInstance();
			$customerTypeId = $umiTypesHelper->getObjectTypeIdByGuid('emarket-customer');
			$userTypeId = $umiTypesHelper->getObjectTypeIdByGuid('users-user');
			$customerIsUser = ($customer instanceof iUmiObject) && ($customer->getTypeId() == $userTypeId);

			if ($customer instanceof iUmiObject) {
				if ($customer->getTypeId() != $customerTypeId && !$customerIsUser) {
					$customer = null;
				}
			} else {
				$customer = null;
			}

			if (!$customer) {
				$customerId = self::createGuestCustomer();
				$customer = selector::get('object')->id($customerId);
			}

			if (!$customerId) {
				$customerId = self::createGuestCustomer();
			}

			if (!(defined('UMICMS_CLI_MODE') && UMICMS_CLI_MODE) && !$customerIsUser) {
				Service::CookieJar()
					->setEncrypted('customer-id', $customerId, time() + self::getExpirationTime());
			}

			if (!$customerIsUser) {
				umiObjectsExpiration::getInstance()->update($customerId, self::getExpirationTime());
			}

			return $customer;
		}

		/**
		 * Создает незарегистрированного покупателя и возвращает его идентификатор
		 * @return int
		 * @throws coreException
		 */
		protected static function createGuestCustomer() {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$objects = umiObjectsCollection::getInstance();
			$objectTypeId = $objectTypes->getTypeIdByHierarchyTypeName('emarket', 'customer');
			$customerId = $objects->addObject(getServer('REMOTE_ADDR'), $objectTypeId);
			$customer = $objects->getObject($customerId);
			$systemUsersPermissions = Service::SystemUsersPermissions();
			$customer->setOwnerId($systemUsersPermissions->getGuestUserId());
			$customer->commit();

			$expiration = umiObjectsExpiration::getInstance();
			$expiration->add($customerId, self::getExpirationTime());

			return $customerId;
		}

		/**
		 * Возвращает идентификатор последнего незавершенного заказа пользователя на конкретном домене.
		 * @param int $domainId идентификатор домена искомого незавешенного заказа
		 * @return int|bool
		 */
		public function getLastOrder($domainId) {
			$orderId = Service::Session()->get('admin-editing-order');
			if ($orderId) {
				return $orderId;
			}

			$lastOrders = $this->getValue('last_order');

			if (!is_array($lastOrders) || umiCount($lastOrders) == 0) {
				return false;
			}

			foreach ($lastOrders as $lastOrder) {
				if (!isset($lastOrder['float']) || $lastOrder['float'] != $domainId) {
					continue;
				}

				$orderId = $lastOrder['rel'];
				$order = order::get($orderId);

				if (!$order instanceof order || $order->isDummy()) {
					continue;
				}

				$orderStatus = order::getCodeByStatus($order->getValue('status_id'));
				$paymentStatus = order::getCodeByStatus($order->getValue('payment_status_id'));

				if (
					!$orderStatus || $orderStatus == 'executing' ||
					(
						$orderStatus == 'payment' && $paymentStatus == 'initialized'
					)
				) {
					return $orderId;
				}
			}

			return false;
		}

		/**
		 * Добавляет заказ в список последних заказов покупателя
		 * @param int $orderId идентификатор заказа
		 * @param int $domainId идентификатор домена, на котором оформлялся заказ
		 * @return bool
		 */
		public function setLastOrder($orderId, $domainId) {
			$order = order::get($orderId);

			if (!$order instanceof order || $order->isDummy()) {
				return false;
			}

			$lastOrderList = $this->getValue('last_order');
			$umiObjects = umiObjectsCollection::getInstance();
			$matchDomain = false;

			foreach ($lastOrderList as $key => &$lastOrder) {
				if (!isset($lastOrder['rel']) || !$umiObjects->isExists($lastOrder['rel'])) {
					unset($lastOrderList[$key]);
					continue;
				}

				if (!isset($lastOrder['float']) || $lastOrder['float'] != $domainId) {
					continue;
				}

				$lastOrder['rel'] = $orderId;
				$matchDomain = true;
			}

			if (!$matchDomain) {
				$lastOrderList[] = [
					'rel' => $orderId,
					'float' => $domainId
				];
			}

			$this->object->setValue('last_order', $lastOrderList);
			$this->object->commit();
			return true;
		}

		/** @deprecated */
		protected static function getCustomerId($noCookie = false, $customerId = false) {
			return self::getCustomerObject($customerId);
		}
	}
