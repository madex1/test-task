<?php
	use UmiCms\Service;

	class customer extends umiObjectProxy {
		public static $defaultExpiration = 2678400;	// 31 days
		protected $isAuth;

		public static function get($nocache = false, $customerId = false) {
			static $customer;
			if(!$nocache && !is_null($customer)) {
				return $customer;
			}
			
			$objects = umiObjectsCollection::getInstance();
			$permissions = permissionsCollection::getInstance();

			if($permissions->isAuth()) {
				if (false !== $customerId) {
					$object = $objects->getObject($customerId);
				} else {
					$userId = $permissions->getUserId();
					$object = $objects->getObject($userId);
				}
			} else {
				$object = self::getCustomerId(false, $customerId);
				//Second try may be usefull to avoid server after-reboot conflicts
				if(false === $object) {
					$object = self::getCustomerId(true, $customerId);
				}
			}
			
			if($object instanceof iUmiObject) {
				$customer = new customer($object);
				$customer->tryMerge();
				return $customer;
			}
		}

		public function __construct(iUmiObject $object) {
			$permissions = permissionsCollection::getInstance();
			$userId = $permissions->getUserId();

			$systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();
			$guestId = $systemUsersPermissions->getGuestUserId();
			$this->isAuth = ($userId == $guestId) ? false : $userId;

			parent::__construct($object);
		}

		public function isUser() {
			return (bool) $this->isAuth;
		}

		public function tryMerge() {
			if($this->isUser() && \UmiCms\Service::CookieJar()->getDecrypted('customer-id')) {
				$guestCustomer = self::getCustomerId();
				if($guestCustomer instanceof iUmiObject) {
					$this->merge($guestCustomer);
				}
			}
		}


		/**
		 * Слить все заказы покупателя в профиль пользователя.
		 * Объект покупателя после этого будет уничтожен
		 * @param umiObject $customer объект покупателя-гостя
		 * @throws selectorException
		 */
		public function merge(umiObject $customer) {
			if ($customer->getTypeGUID() != 'emarket-customer') {
				return;
			}

			$permissions = permissionsCollection::getInstance();
			$userId = $permissions->getUserId();

			if ($customer->getId() === $userId) {
				return;
			}

			$cmsController = cmsController::getInstance();
			$domain = $cmsController->getCurrentDomain();
			$domainId = $domain->getId();

			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('customer_id')->equals($customer->id);
			$sel->where('domain_id')->equals($domainId);
			$sel->option('load-all-props')->value(true);
			$sel->order('id')->desc();

			$userBasket = $this->getBasketByDomainId($domainId);

			foreach($sel as $order) {
				if (!$order->status_id) {
					$this->mergeBasket($order, $userBasket);
					continue;
				}

				$order->customer_id = $userId;
				$order->commit();
			}
			if (!defined('UMICMS_CLI_MODE') || !UMICMS_CLI_MODE) {
				\UmiCms\Service::CookieJar()
					->remove('customer-id');
			}
			$customer->delete();
		}

		/**
		 * Переносит товарные наименования из корзины незарегистрированного покупателя
		 * в заказ зарегистрированного пользователя.
		 * В конце операции удаляет корзину незарегистрированного покупателя.
		 * @param umiObject $guestBasket корзина незарегистрированного покупателя.
		 * @param order $userBasket корзина зарегистрированного пользователя
		 * @throws coreException
		 * @throws privateException
		 */
		protected function mergeBasket(umiObject $guestBasket, order $userBasket) {
			$orderItems = $guestBasket->order_items;

			if (!is_array($orderItems) || umiCount($orderItems) == 0) {
				return;
			}

			foreach($orderItems as $orderItemId) {
				$orderItem = orderItem::get($orderItemId);

				if ($orderItem) {
					$userBasket->appendItem($orderItem);
				}
			}

			$userBasket->commit();
			$guestBasket->delete();
		}

		/**
		 * @internal
		 * Внимание: не используйте эту функцию в прикладном коде,
		 * используйте @see customer::tryMerge().
		 *
		 * Переносит заказы незарегистрированного покупателя к заказам авторизованного пользователя.
		 * Объект незарегистрированного покупателя после этого будет уничтожен.
		 * @param iUmiObject $customer объект незарегистрированного покупателя
		 * @return order|null
		 * @throws coreException
		 * @throws publicException
		 * @throws selectorException
		 */
		public function getBasketByDomainId($domainId, $useDummyOrder = false) {
			$basketId = $this->getLastOrder($domainId);

			if ($basketId) {
				return order::get($basketId);
			}

			return order::create($useDummyOrder, $domainId);
		}

		/** "Заморозить" покупателя (по умолчанию через 31 дней после последнего входа, объект будет удален) */
		public function freeze() {
			$expirations = umiObjectsExpiration::getInstance();
			$expirations->clear($this->id);
		}

		public function __toString() {
			return (string) $this->object->getId();
		}

		/**
			* Получить id покупателя-гостя, и, возможно, создать нового.
			* @param Boolean $noCookie = false не использовать данные кук
			* @return Integer id покупателя
		*/
		protected static function getCustomerId($noCookie = false, $customerId = false) {
			if (false === $customerId) {
				$customerId = (int) \UmiCms\Service::CookieJar()->getDecrypted('customer-id');
			}
			
			/* @var $customer umiObject */
			$customer = selector::get('object')->id($customerId);
			$umiTypesHelper = umiTypesHelper::getInstance();
			$customerTypeId = $umiTypesHelper->getObjectTypeIdByGuid('emarket-customer');
			$customerIsUser	= ($customer instanceof iUmiObject) && 
							  ($customer->getTypeId() === $umiTypesHelper->getObjectTypeIdByGuid('users-user'));

			if ($customer instanceof iUmiObject != false) {
				if ($customer->getTypeId() != $customerTypeId && !$customerIsUser) {
					$customer = null;
				}
			} else {
				$customer = null;
			}
			
			if(!$customer) {
				$customerId = self::createGuestCustomer();
				$customer = selector::get('object')->id($customerId);
			}

			if(!$customerId) {
				$customerId = self::createGuestCustomer();
			}

			if ((!defined('UMICMS_CLI_MODE') || !UMICMS_CLI_MODE) && (!$customerIsUser)) {
				\UmiCms\Service::CookieJar()
					->setEncrypted('customer-id', $customerId, time() + self::$defaultExpiration);
			}

			$expirations = umiObjectsExpiration::getInstance();
			if (!$customerIsUser) {
				$expirations->update($customerId, self::$defaultExpiration);
			}

			return $customer;
		}

		/**
			* Создать нового покупателя-гостя
			* @return Integer id нового покупателя
		*/
		protected static function createGuestCustomer() {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$objects = umiObjectsCollection::getInstance();
			$objectTypeId = $objectTypes->getTypeIdByHierarchyTypeName('emarket', 'customer');
			$customerId = $objects->addObject(getServer('REMOTE_ADDR'), $objectTypeId);
			$customer = $objects->getObject($customerId);
			$systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();
			$customer->setOwnerId($systemUsersPermissions->getGuestUserId());
			$customer->commit();
			$expirations = umiObjectsExpiration::getInstance();
			$expirations->add($customerId, self::$defaultExpiration);
			return $customerId;
		}

		/**
		* Получить id последнего заказа пользователя
		*
		* @param int $domainId id домена заказа
		* @return int $orderId | false
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
		* Установить последний заказ пользователя
		* @param int $orderId id заказа
		* @param int $domainId id домена заказа
		*/
		public function setLastOrder($orderId, $domainId) {
			$order = order::get($orderId);

			if (!$order instanceof order || $order->isDummy()) {
				return false;
			}

			$lastOrders = $this->last_order;
			$matchDomain = false;
			foreach($lastOrders as &$lastOrder) {
				if (isset($lastOrder['float']) && $lastOrder['float'] == $domainId) {
					$lastOrder['rel'] = $orderId;
					$matchDomain = true;
				}
			}
			if (!$matchDomain) $lastOrders[] = array("rel" => $orderId, "float" => $domainId);
			$this->last_order = $lastOrders;
			$this->commit();

		}
	};
?>
