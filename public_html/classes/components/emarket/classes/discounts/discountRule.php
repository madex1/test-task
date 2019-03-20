<?php

	use UmiCms\Service;

	/**
	 * Базовый класс правила скидки абстракного типа.
	 * Одновременно является родительским классом всех типов правил скидок
	 * и предоставляет интерфейс для создания и получения правила скидки конкретного типа.
	 *
	 * По умолчанию в системе существуют следующие типы правил скидок:
	 *
	 * 1) "Сумма покупок пользователя";
	 * 2) "Временной диапазон";
	 * 3) "Товары заказа";
	 * 4) "Сумма заказа";
	 * 5) "Связанные товары";
	 * 6) "Группа покупателя";
	 * 7) "Покупатель";
	 *
	 * Правило скидки отвечает за условие применения скидки
	 */
	abstract class discountRule extends umiObjectProxy {

		/**
		 * Создает и возвращает правило скидки
		 * @param discount $discount скидка
		 * @param iUmiObject $ruleTypeObject тип правила скидки
		 * @return bool|discountRule
		 * @throws coreException
		 */
		public static function create(discount $discount, iUmiObject $ruleTypeObject) {
			$objects = umiObjectsCollection::getInstance();
			$ruleTypeId = null;
			$ruleTypeGUID = (string) $ruleTypeObject->getValue('rule_type_guid');

			if ($ruleTypeGUID !== '') {
				$ruleTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID($ruleTypeGUID);
			} else {
				$ruleTypeId = $ruleTypeObject->getValue('rule_type_id');
			}

			$objectId = $objects->addObject('', $ruleTypeId);
			$object = $objects->getObject($objectId);

			if (!$object instanceof iUmiObject) {
				return false;
			}

			$object->setValue('rule_type_id', $ruleTypeObject->getId());
			$object->commit();
			return self::get($objectId, $discount);
		}

		/**
		 * Возвращает правило скидки
		 * @param int $modObjectId идентификатор правила скидки
		 * @param discount $discount скидка
		 * @return bool|discountRule
		 */
		public static function get($modObjectId, discount $discount) {
			$objects = umiObjectsCollection::getInstance();

			$modObject = $objects->getObject($modObjectId);
			if (!$modObject instanceof iUmiObject) {
				return false;
			}

			$ruleTypeId = $modObject->getValue('rule_type_id');
			umiObjectProperty::loadPropsData([$modObject->getId(), $ruleTypeId]);
			$codeName = self::getCodeName($ruleTypeId);

			if (!$codeName) {
				return false;
			}

			$className = $codeName . 'DiscountRule';

			if (!class_exists($className)) {
				self::includeRule($codeName);
			}

			if (!class_exists($className)) {
				return false;
			}

			$rule = new $className($modObject, $discount, $codeName);
			return ($rule instanceof discountRule) ? $rule : false;
		}

		/**
		 * Возвращает список правил скидки
		 * @param bool|int $discountTypeId идентификатор типа скидки
		 * @return iUmiObject[]
		 * @throws coreException
		 * @throws selectorException
		 */
		public static function getList($discountTypeId = false) {
			$objectTypeId = self::getRuleType()->getId();

			$sel = new selector('objects');
			$sel->types('object-type')->id($objectTypeId);

			if ($discountTypeId) {
				$sel->where('rule_discount_types')->equals($discountTypeId);
			}

			$sel->option('load-all-props')->value(true);
			$sel->option('no-length')->value(true);
			return $sel->result();
		}

		/**
		 * Конструктор
		 * @param iUmiObject $object объект-источник данных для правила скидки
		 * @throws Exception
		 */
		protected function __construct(iUmiObject $object) {
			$args = func_get_args();
			$rule = array_shift($args);

			if (!$rule instanceof iUmiObject) {
				throw new Exception('Rule expected for creating rule');
			}

			$discount = array_shift($args);

			if (!$discount instanceof discount) {
				throw new Exception('Discount expected for creating rule');
			}

			$discountName = array_shift($args);

			if (!is_string($discountName)) {
				throw new Exception('Discount name expected for creating modifier');
			}

			parent::__construct($rule);

			$this->name = $discountName;
			$this->discount = $discount;
		}

		/**
		 * Возвращает идентификатор заказа из запроса в режиме административной панели
		 * @return int|bool|null
		 */
		protected function getOrderIdFromAdminRequestData() {
			$requestData = getRequest('data');

			if (!is_array($requestData)) {
				return false;
			}

			$arrayKeys = array_keys($requestData);

			if (isset($arrayKeys[0])) {
				return $arrayKeys[0];
			}

			return null;
		}

		/**
		 * Возвращает идентификатор заказа из запроса в режиме сайта
		 * @return bool|int|mixed|null
		 */
		protected function getOrderIdFromSiteRequestData() {
			$cmsController = cmsController::getInstance();
			$currentModule = $cmsController->getCurrentModule();
			$currentMethod = $cmsController->getCurrentMethod();
			$orderId = null;

			if ($currentModule == 'content' && $currentMethod == 'save_editable_region') {
				$orderId = getRequest('param0');
			}

			if ($currentModule == 'emarket' && $currentMethod == 'gateway') {
				$orderId = payment::getResponseOrderId();
			}

			return $orderId;
		}

		/**
		 * Возвращает объект текущего покупателя
		 * @return bool|iUmiObject
		 */
		protected function getCustomerObject() {
			if (Service::CookieJar()->isExists('customer-id')) {
				$id = customer::get()->getId();
			} else {
				$id = Service::Auth()->getUserId();
			}

			return umiObjectsCollection::getInstance()->getObject($id);
		}

		/**
		 * Возвращает объект покупателя из заданного заказа
		 * @param int $id идентификатор заказа
		 * @return bool|iUmiObject
		 * @throws publicException
		 */
		protected function getCustomerObjectFromOrder($id) {
			$order = order::get($id);

			if (!$order instanceof order) {
				return false;
			}

			return umiObjectsCollection::getInstance()->getObject($order->getCustomerId());
		}

		/**
		 * Подключает файл с реализацией типа правила скидки и возвращает результат операции
		 * @param string $ruleName имя файла с реализацией правила
		 * @return bool
		 */
		private static function includeRule($ruleName) {
			static $included = [];

			if (isset($included[$ruleName])) {
				return $included[$ruleName];
			}

			$filePath = dirname(__FILE__) . '/rules/' . $ruleName . '.php';

			if (is_file($filePath)) {
				/** @noinspection PhpIncludeInspection */
				require_once $filePath;
				return $included[$ruleName] = true;
			}

			return $included[$ruleName] = false;
		}

		/**
		 * Возвращает объектный тип правила скидки
		 * @return iUmiObjectType
		 * @throws coreException
		 */
		private static function getRuleType() {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$objectTypeId = $objectTypes->getTypeIdByHierarchyTypeName('emarket', 'discount_rule_type');

			if (!$objectTypeId) {
				throw new coreException('Required data type (emarket::discount_rule_type) not found');
			}

			return $objectTypes->getType($objectTypeId);
		}

		/**
		 * Возвращает строковой идентификатор типа правила по его id
		 * @param int $modTypeObjectId идентификатор типа правила
		 * @return string|bool
		 */
		private static function getCodeName($modTypeObjectId) {
			static $cache = [];

			if (isset($cache[$modTypeObjectId])) {
				return $cache[$modTypeObjectId];
			}

			$objects = umiObjectsCollection::getInstance();
			$modTypeObject = $objects->getObject($modTypeObjectId);

			$cache[$modTypeObjectId] =
				($modTypeObject instanceof iUmiObject) ? trim($modTypeObject->getValue('rule_codename')) : false;
			return $cache[$modTypeObjectId];
		}
	}
