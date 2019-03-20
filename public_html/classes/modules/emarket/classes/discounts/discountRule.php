<?php
	abstract class discountRule extends umiObjectProxy {
		
		public static function create(discount $discount, umiObject $ruleTypeObject) {
			$objects = umiObjectsCollection::getInstance();
			$ruleTypeId = null;
			if(strlen($ruleTypeObject->rule_type_guid)) {
				$ruleTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID($ruleTypeObject->rule_type_guid);
			} else {
				$ruleTypeId = $ruleTypeObject->rule_type_id;
			}
			$objectId = $objects->addObject('', $ruleTypeId);
			$object = $objects->getObject($objectId);
			if($object instanceof umiObject) {
				$object->setValue('rule_type_id', $ruleTypeObject->getId());
				$object->commit();
				return self::get($objectId, $discount);
			} else {
				return false;
			}
		}


		public static function get($modObjectId, discount $discount) {
			$objects = umiObjectsCollection::getInstance();
			
			$modObject = $objects->getObject($modObjectId);
			if($modObject instanceof umiObject == false) return false;
			
			$codeName = self::getCodeName($modObject->rule_type_id);
			$className = $codeName . 'DiscountRule';
			
			if(!$codeName) return false;
			if(!self::includeRule($codeName)) return false;
			if(!class_exists($className)) return false;
			
			$rule = new $className($modObject, $discount, $codeName);
			return ($rule instanceof discountRule) ? $rule : false;
		}


		public static function getList($discountTypeId = false) {
			$objectTypeId = self::getRuleType()->getId();
			
			$sel = new selector('objects');
			$sel->types('object-type')->id($objectTypeId);

			if ($discountTypeId) {
				$sel->where('rule_discount_types')->equals($discountTypeId);
			}

			$sel->option('load-all-props')->value(true);
			return $sel->result();
		}


		protected function init() {}

		protected function __construct(umiObject $object) {
			$args = func_get_args();
			$rule = array_shift($args);

			if (!$rule instanceof umiObject) {
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
			$this->init();
		}


		private static function includeRule($ruleName) {
			static $included = Array();
			
			if(isset($included[$ruleName])) {
				return $included[$ruleName];
			}
			
			$filepath = SYS_MODULES_PATH . 'emarket/classes/discounts/rules/' . $ruleName . '.php';
			
			if(is_file($filepath)) {
				require $filepath;
				return $included[$ruleName] = true;
			}
			return $included[$ruleName] = false;
		}


		private static function getRuleType() {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$objectTypeId = $objectTypes->getTypeIdByHierarchyTypeName('emarket', 'discount_rule_type');
			if(!$objectTypeId) {
				throw new coreException("Required data type (emarket::discount_rule_type) not found");
			}
			return $objectTypes->getType($objectTypeId);
		}
		
		
		private static function getCodeName($modTypeObjectId) {
			static $cache = Array();
			
			if(isset($cache[$modTypeObjectId])) {
				return $cache[$modTypeObjectId];
			}
			
			$objects = umiObjectsCollection::getInstance();
			
			$modTypeObject = $objects->getObject($modTypeObjectId);
			$cache[$modTypeObjectId] = ($modTypeObject instanceof umiObject) ? trim($modTypeObject->rule_codename) : false;
			return $cache[$modTypeObjectId];
		}
	};


	interface orderDiscountRule {
		public function validateOrder(order $order);
	};
	
	interface itemDiscountRule {
		public function validateItem(iUmiHierarchyElement $element);
	}
?>