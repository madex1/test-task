<?php
	abstract class payment extends umiObjectProxy {
		protected $order;

		final public static function create(iUmiObject $paymentTypeObject) {
			$objects = umiObjectsCollection::getInstance();
			$paymentTypeId = null;
			if(strlen($paymentTypeObject->payment_type_guid)) {
				$paymentTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID($paymentTypeObject->payment_type_guid);
			} else {
				$paymentTypeId = $paymentTypeObject->payment_type_id;
			}
			$objectId = $objects->addObject('', $paymentTypeId);
			$object = $objects->getObject($objectId);
			if($object instanceof umiObject) {
				$object->payment_type_id = $paymentTypeObject->id;
				$object->commit();

				return self::get($objectId);
			} else {
				return false;
			}
		}

		final public static function get($objectId, order $order = null) {
			if($objectId instanceof iUmiObject) {
				$object = $objectId;
			} else {
				$object = umiObjectsCollection::getInstance()->getObject($objectId);

				if($object instanceof iUmiObject == false || !$object->payment_type_id) {
					return null;
				}
			}

			$classPrefix = objectProxyHelper::getClassPrefixByType($object->payment_type_id);

			objectProxyHelper::includeClass('emarket/classes/payment/systems/', $classPrefix);
			$className = $classPrefix . 'Payment';

			if(is_null($order)){
				return new $className($object);
			}
			return new $className($object, $order);
		}

		final public static function getList() {
			static $paymentsList = null;

			if (!is_null($paymentsList)) {
				return $paymentsList;
			}

			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('emarket', 'payment');
			$sel->option('load-all-props')->value(true);
			return $paymentsList = $sel->result();
		}

		/**
		 * Ищет идентификатор заказа в ответе платежной системы.
		 * Сначала проверяются стандартные поля, потом опрашивается метод getOrderId
		 * каждой подключенной платежной системы
		 * @return Integer | boolean false
		 */
		final public static function getResponseOrderId() {
			$orderId = (int) getRequest('param0');
			if(!$orderId) $orderId = (int) getRequest('orderid');
			if(!$orderId) $orderId = (int) getRequest('orderId');	// RBK
			if(!$orderId) $orderId = (int) getRequest('order-id');	// Chronopay
			if(!$orderId) $orderId = (int) getRequest('order_id');
			if(!$orderId) $orderId = (int) getRequest('item_number');	// PayPal
			if(!$orderId) {
				$paymentSystems = self::getList();
				foreach ($paymentSystems as $paymentSystem) {
					$classPrefix = objectProxyHelper::getClassPrefixByType($paymentSystem->payment_type_id);
					objectProxyHelper::includeClass('emarket/classes/payment/systems/', $classPrefix);
					$className = $classPrefix . 'Payment';
					//TODO: change to $className::getOrderId() after minimum requirements for UMI changes to PHP 5.3
					$orderId = (int) call_user_func("$className::getOrderId");
					if ($orderId) {
						break;
					}
				}
			}
			return $orderId;
		}

		public function __construct(umiObject $object) {
			$args = func_get_args();
			$payment = array_shift($args);

			if (!$payment instanceof umiObject) {
				throw new Exception('Payment expected for creating payment');
			}

			$order = array_shift($args);

			if (!$order instanceof order && $order !== null) {
				throw new Exception('Incorrect order given for creating payment');
			}

			parent::__construct($payment);
			$this->order = $order;
		}

		public function getCodeName() {
			$objects = umiObjectsCollection::getInstance();
			$paymentTypeId = $this->object->payment_type_id;
			$paymentType = $objects->getObject($paymentTypeId);
			return ($paymentType instanceof iUmiObject) ? $paymentType->class_name : false;
		}

		/**
		 * Ищет идентификатор заказа в параметре специфичном для платежной системы.
		 * Если платежная системы использует один из предопределенных параметров
		 * (orderid, orderId, order-id, order_id) возвращает false, в противном случае
		 * необходимо переопредилить функцию в файле платежной системы.
		 * @return Integer | boolean false
		 */
		public static function getOrderId() {
			return false;
		}

		abstract function validate();
		abstract function process($template = null);
		abstract function poll();
	};
?>
