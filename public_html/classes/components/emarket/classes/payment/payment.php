<?php

	use UmiCms\Service;
	use UmiCms\Classes\Components\Emarket\Serializer\Receipt\iFactory as SerializerReceiptFactory;

	/**
	 * Базовый класс способа оплаты абстракного типа.
	 * Одновременно является родительским классом всех способов оплаты
	 * и предоставляет интерфейс для создания и получения конкретного способа оплаты.
	 *
	 * По умолчанию в системе существуют следующие реализации типов способов оплаты:
	 *
	 * 1) "AcquiroPay";
	 * 2) "Наличными курьеру";
	 * 3) "Деньги Online";
	 * 4) "Счет для юридических лиц";
	 * 5) "КупиВКредит";
	 * 6) "PayAnyWay" (С поддержкой ФЗ-54);
	 * 7) "PayOnline System";
	 * 8) "PayPal";
	 * 9) "RBK Money";
	 * 10) "Платежная квитанция";
	 * 11) "Robokassa" (С поддержкой ФЗ-54);
	 * 12) "Яндекс.Касса" (С поддержкой ФЗ-54);
	 *
	 * Пример добавления собственного способа оплаты описан в документации:
	 * @link http://api.docs.umi-cms.ru/razrabotka_nestandartnogo_funkcionala/integraciya_platzhnyh_sistem/
	 */
	abstract class payment extends umiObjectProxy {

		/** @var order $order заказ */
		protected $order;

		/**
		 * Создает способ оплаты заданного типа
		 * @param iUmiObject $paymentTypeObject объект типа способа оплаты
		 * @return payment|bool
		 * @throws coreException
		 */
		final public static function create(iUmiObject $paymentTypeObject) {
			/** @var iUmiObject $paymentTypeObject */
			$objects = umiObjectsCollection::getInstance();
			$paymentTypeId = null;
			$paymentTypeGUID = (string) $paymentTypeObject->getValue('payment_type_guid');

			if ($paymentTypeGUID !== '') {
				$paymentTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID($paymentTypeGUID);
			} else {
				$paymentTypeId = $paymentTypeObject->getValue('payment_type_id');
			}

			$objectId = $objects->addObject('', $paymentTypeId);
			$object = $objects->getObject($objectId);

			if ($object instanceof iUmiObject) {
				$object->setValue('payment_type_id', $paymentTypeObject->getId());
				$object->commit();
				return self::get($objectId);
			}

			return false;
		}

		/**
		 * Возвращает способ оплаты по идентификатору объекта-источника данных для способа оплаты
		 * @param int|iUmiObject $objectId идентификатор объекта-источника данных для способа оплаты или сам объект
		 * @param order|null $order заказ
		 * @return payment|bool|null
		 * @throws coreException
		 */
		final public static function get($objectId, order $order = null) {
			if ($objectId instanceof iUmiObject) {
				$object = $objectId;
			} else {
				$object = umiObjectsCollection::getInstance()->getObject($objectId);
			}

			if (!$object instanceof iUmiObject) {
				return null;
			}

			$paymentTypeId = $object->getValue('payment_type_id');

			if (!$paymentTypeId) {
				return null;
			}

			$classPrefix = objectProxyHelper::getClassPrefixByType($paymentTypeId);
			$className = $classPrefix . 'Payment';

			if (!class_exists($className)) {
				objectProxyHelper::includeClass('emarket/classes/payment/systems/', $classPrefix);
			}

			if ($order === null) {
				return new $className($object);
			}

			return new $className($object, $order);
		}

		/**
		 * Возвращает список способов оплаты
		 * @return iUmiObject[]
		 * @throws selectorException
		 * @throws coreException
		 */
		final public static function getList() {
			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('emarket', 'payment');
			$sel->option('load-all-props')->value(true);
			$sel->where('disabled')->notequals(true);
			$currentDomainId = Service::DomainDetector()->detectId();
			$sel->option('or-mode')->field('domain_id_list');
			$sel->where('domain_id_list')->equals($currentDomainId);
			$sel->where('domain_id_list')->isnull();
			$sel->option('load-all-props', true);
			$sel->option('no-length', true);
			return $sel->result();
		}

		/**
		 * Возвращает список типов способов оплаты, которые используются на сайте
		 * @return array
		 * @throws selectorException
		 * @throws coreException
		 */
		final public static function getUsedPaymentsTypes() {
			static $paymentsTypeList;

			if (is_array($paymentsTypeList)) {
				return $paymentsTypeList;
			}

			$paymentList = self::getList();
			$paymentTypeIdList = [];

			foreach ($paymentList as $payment) {
				$paymentTypeIdList[] = $payment->getValue('payment_type_id');
			}

			$paymentTypeIdList = array_unique($paymentTypeIdList);

			$queryBuilder = new selector('objects');
			$queryBuilder->types('object-type')->guid('emarket-paymenttype');
			$queryBuilder->where('id')->equals($paymentTypeIdList);
			$queryBuilder->option('load-all-props')->value(true);
			$queryBuilder->option('no-length')->value(true);

			return $paymentsTypeList = $queryBuilder->result();
		}

		/**
		 * Возвращает идентификатор заказа из ответа платежной системы.
		 * Сначала проверяются стандартные поля, потом опрашивается метод getOrderId
		 * каждой подключенной платежной системы.
		 * @return int|bool
		 * @throws selectorException
		 * @throws coreException
		 */
		final public static function getResponseOrderId() {

			foreach (self::getList() as $paymentSystem) {
				$classPrefix = objectProxyHelper::getClassPrefixByType($paymentSystem->getValue('payment_type_id'));
				objectProxyHelper::includeClass('emarket/classes/payment/systems/', $classPrefix);
				/** @var payment $className */
				$className = $classPrefix . 'Payment';
				$orderId = (int) $className::getOrderId();

				if ($orderId) {
					return $orderId;
				}
			}

			return (int) getRequest('param0');
		}

		/**
		 * Конструктор
		 * @param iUmiObject $object объект-источник данных для способа оплаты
		 * @throws Exception
		 */
		public function __construct(iUmiObject $object) {
			$args = func_get_args();
			$payment = array_shift($args);

			if (!$payment instanceof iUmiObject) {
				throw new Exception('Payment expected for creating payment');
			}

			$order = array_shift($args);

			if (!$order instanceof order && $order !== null) {
				throw new Exception('Incorrect order given for creating payment');
			}

			parent::__construct($payment);
			$this->order = $order;
		}

		/**
		 * Устанавливает заказ
		 * @param order $order заказ
		 * @return $this
		 */
		public function setOrder(order $order) {
			$this->order = $order;
			return $this;
		}

		/**
		 * Возвращает строковой идентификатор типа способа оплаты
		 * @return string|bool
		 */
		public function getCodeName() {
			$objects = umiObjectsCollection::getInstance();
			$paymentTypeId = $this->getValue('payment_type_id');
			$paymentType = $objects->getObject($paymentTypeId);
			return ($paymentType instanceof iUmiObject) ? $paymentType->getValue('class_name') : false;
		}

		/**
		 * Возвращает идентификатор заказа из запроса платежной системы.
		 * @return int|bool
		 */
		public static function getOrderId() {
			return false;
		}

		/**
		 * Применима ли платежная система.
		 * На основании этого метода принимается решение о добавлении способа оплаты в список доступных способов.
		 * @see EmarketPurchasingStagesSteps::renderPaymentsList()
		 * @return bool
		 */
		public function validate() {
			if (func_num_args() < 0) {
				return true;
			}

			$order = func_get_arg(0);

			if (!$order instanceof order) {
				return false;
			}

			return $this->isPaymentDisabledInOrder($order);
		}

		/**
		 * Возвращает данные для построения формы отправки
		 * данных заказа в платежную системы.
		 * Инициирует превращение корзины в заказ.
		 * @see EmarketPurchasingStages::payment()
		 * @param string|null $template имя шаблона (для tpl)
		 * @return mixed
		 */
		abstract public function process($template = null);

		/**
		 * Принимает запрос от платежной системы.
		 * Чащего всего просто валидирует заказ от платежной системы
		 * и ставит заказу в UMI.CMS статус "Принят".
		 * @see emarket::gateway()
		 * Выводит ответ в буффер.
		 */
		abstract public function poll();

		/**
		 * Возвращает код валюты
		 * @return string
		 * @throws coreException
		 * @throws privateException
		 */
		protected function getCurrencyCode() {
			return Service::CurrencyFacade()
				->getDefault()
				->getISOCode();
		}

		/**
		 * Форматирует цену
		 * @param float|string|int $price цена
		 * @return string
		 */
		protected function formatPrice($price) {
			return number_format($price, 2, '.', '');
		}

		/**
		 * Определяет нужны ли данные для печати чека
		 * @return bool
		 */
		protected function isNeedReceiptInfo() {
			return (bool) $this->object->getValue('receipt_data_send_enable');
		}

		/**
		 * Определяет нужно ли вести лог
		 * @return bool
		 */
		protected function isNeedKeepLog() {
			return (bool) $this->object->getValue('keep_log');
		}

		/**
		 * Возвращает фабрику сериализаторов данных чека по ФЗ-54
		 * @return SerializerReceiptFactory
		 * @throws Exception
		 */
		protected function getSerializerReceiptFactory() {
			return Service::get('ReceiptSerializerFactory');
		}

		/**
		 * Возвращает адрес страницы неудачного оформления заказа
		 * @return string
		 * @throws coreException
		 */
		protected function getFailUrl() {
			return sprintf('%s/emarket/purchase/result/fail/', $this->getUrl());
		}

		/**
		 * Возвращает адрес страницы успешного оформления заказа
		 * @return string
		 * @throws coreException
		 */
		protected function getSuccessUrl() {
			return sprintf('%s/emarket/purchase/result/successful/', $this->getUrl());
		}

		/**
		 * Возвращает адрес сайта
		 * @return string
		 * @throws coreException
		 */
		protected function getUrl() {
			return Service::DomainDetector()->detectUrl();
		}

		/**
		 * Проверяет отключен ли способ оплаты в заказе
		 * @param order $order заказ
		 * @return bool
		 */
		protected function isPaymentDisabledInOrder(order $order) {
			return !in_array($this->getId(), $this->getDisabledPaymentIdList($order->getDeliveryId()));
		}

		/**
		 * Возвращает список идентификаторов платежных систем запрещенных для данного способа доставки
		 * @param int $deliveryId идентификатор доставки
		 * @return array
		 */
		protected function getDisabledPaymentIdList($deliveryId) {
			try {
				$delivery = delivery::get($deliveryId);
			} catch (coreException $exception) {
				return [];
			}

			return $delivery->getDisabledPaymentIdList();
		}
	}
