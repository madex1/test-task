<?php
	abstract class delivery extends umiObjectProxy {
		final public static function create(umiObject $deliveryTypeObject) {
			$objects = umiObjectsCollection::getInstance();
			$deliveryTypeId = null;
			if(strlen($deliveryTypeObject->delivery_type_guid)) {
				$deliveryTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID($deliveryTypeObject->delivery_type_guid);
			} else {
				$deliveryTypeId = $deliveryTypeObject->delivery_type_id;
			}
			$objectId = $objects->addObject('', $deliveryTypeId);
			$object = $objects->getObject($objectId);
			if($object instanceof umiObject) {
				$object->setValue('delivery_type_id', $deliveryTypeObject->getId());
				$object->commit();

				return self::get($objectId);
			} else {
				return false;
			}
		}

		final public static function get($objectId) {
			if($objectId instanceof iUmiObject) {
				$object = $objectId;
			} else {
				$objects = umiObjectsCollection::getInstance();
				$object = $objects->getObject($objectId);

				if($object instanceof iUmiObject == false) {
					throw new coreException("Couldn't load delivery object #{$objectId}");
				}
			}

			$classPrefix = objectProxyHelper::getClassPrefixByType($object->delivery_type_id);

			objectProxyHelper::includeClass('emarket/classes/delivery/systems/', $classPrefix);
			$className = $classPrefix . 'Delivery';
			return new $className($object);
		}

		final public static function getList($selfDeliveryOnly = false) {
			$umiRegistry = regedit::getInstance();
			$deliveryWithAddress = (bool) $umiRegistry->getVal('//modules/emarket/delivery-with-address');

			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('emarket', 'delivery');

			if ($deliveryWithAddress) {
				$types = array();
				$typesSel = new selector('objects');
				$typesSel->types('object-type')->guid('emarket-deliverytype');
				$typesSel->where('class_name')->equals('self');
				$typesSel->option('load-all-props')->value(true);

				foreach($typesSel as $typeId){
					$types[] = $typeId->id;
				}

				if ($selfDeliveryOnly) {
					$sel->where('delivery_type_id')->equals($types);
				} else {
					$sel->where('delivery_type_id')->notequals($types);
				}
			}

			$sel->option('load-all-props')->value(true);
			return $sel->result();
		}

		abstract public function validate(order $order);
		abstract public function getDeliveryPrice(order $order);
	};
?>