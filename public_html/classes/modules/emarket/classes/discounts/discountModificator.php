<?php
	abstract class discountModificator extends umiObjectProxy {
		protected $name, $discount, $object;

		public static function create(discount $discount, umiObject $modTypeObject) {
			$objects = umiObjectsCollection::getInstance();
			$modificatorTypeId = null;
			if(strlen($modTypeObject->modificator_type_guid)) {
				$modificatorTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID($modTypeObject->modificator_type_guid);
			} else {
				$modificatorTypeId = $modTypeObject->modificator_type_id;
			}
			$objectId = $objects->addObject('',$modificatorTypeId );
			$object = $objects->getObject($objectId);
			if($object instanceof umiObject) {
				$object->setValue('modificator_type_id', $modTypeObject->getId());
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
			
			$codeName = self::getCodeName($modObject->modificator_type_id);
			if(!$codeName) return false;
			if(!self::includeModificator($codeName)) return false;
			
			$className = $codeName . 'DiscountModificator';
			if(!class_exists($className)) return false;
			
			$modificator = new $className($modObject, $discount, $codeName);
			return ($modificator instanceof discountModificator) ? $modificator : false;
		}
		
		public static function getList($discountTypeId = false) {
			$objectTypeId = self::getModificatorType()->getId();
			
			$sel = new selector('objects');
			$sel->types('object-type')->id($objectTypeId);
			if ($discountTypeId) {
				$sel->where('modificator_discount_types')->equals($discountTypeId);
			}
			$sel->option('load-all-props')->value(true);
			return $sel->result();
		}
		
		abstract public function recalcPrice($price);

		protected function __construct(umiObject $object) {
			$args = func_get_args();

			$modifier = array_shift($args);

			if (!$modifier instanceof umiObject) {
				throw new Exception('Modifier expected for creating modifier');
			}

			$discount = array_shift($args);

			if (!$discount instanceof discount) {
				throw new Exception('Discount expected for creating modifier');
			}

			$discountName = array_shift($args);

			if (!is_string($discountName)) {
				throw new Exception('Discount name expected for creating modifier');
			}

			parent::__construct($modifier);
			
			$this->name = $discountName;
			$this->discount = $discount;
			$this->init();
		}
		
		protected function init() {}
		
		private static function getModificatorType() {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$objectTypeId = $objectTypes->getTypeIdByHierarchyTypeName('emarket', 'discount_modificator_type');
			if(!$objectTypeId) {
				throw new coreException("Required data type (emarket::discount_modificator_type) not found");
			}
			return $objectTypes->getType($objectTypeId);
		}
		
		private static function includeModificator($modificatorName) {
			static $included = Array();
			
			if(isset($included[$modificatorName])) {
				return $included[$modificatorName];
			}
			
			$filepath = SYS_MODULES_PATH . 'emarket/classes/discounts/modificators/' . $modificatorName . '.php';
			if(is_file($filepath)) {
				require $filepath;
				return $included[$modificatorName] = true;
			}
			
			return $included[$modificatorName] = false;
		}
		
		private static function getCodeName($modTypeObjectId) {
			static $cache = Array();
			if(isset($cache[$modTypeObjectId])) {
				return $cache[$modTypeObjectId];
			}
			
			$objects = umiObjectsCollection::getInstance();
			$modTypeObject = $objects->getObject($modTypeObjectId);
			$cache[$modTypeObjectId] = ($modTypeObject instanceof umiObject) ? trim($modTypeObject->modificator_codename) : false;
			return $cache[$modTypeObjectId];
		}
	};
?>