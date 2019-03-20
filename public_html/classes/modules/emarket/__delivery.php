<?php
	abstract class __emarket_delivery {
		public static $steps = array('address', 'choose');

		public function delivery(order $order, $step, $mode, $template) {
			switch($step) {
				case 'address' : {
					return ($mode == 'do') ? $this->chooseDeliveryAddress($order) : $this->renderDeliveryAddressesList($order, $template);
				}
				case 'choose': {
					return ($mode == 'do') ? $this->chooseDelivery($order) : $this->renderDeliveryList($order, $template);
				}
			}
		}

		public function deliveryCheckStep(order $order, $step) {
			if(!$step) return self::$steps[0];
			if(in_array($step, self::$steps)) {
				return $step;
			} else {
				throw new privateException("Unknown order delivery step \"{$step}\"");
			}
		}

		public function renderDeliveryAddressesList(order $order, $template = 'default') {
			list($tpl_block, $tpl_item) = def_module::loadTemplates("emarket/delivery/{$template}",
				'delivery_address_block', 'delivery_address_item');

			$customer  = customer::get();
			$addresses = $customer->delivery_addresses;
			$items_arr = array();
			$currentDeliveryId = $order->getValue('delivery_address');

			$collection = umiObjectsCollection::getInstance();

			if(is_array($addresses)) foreach($addresses as $address) {
				$addressObject = $collection->getObject($address);

				$item_arr = array(
					'attribute:id'		=> $address,
					'attribute:name'	=> $addressObject->name
				);

				if($address == $currentDeliveryId) {
					$item_arr['attribute:active'] = 'active';
					$item_arr['void:checked'] = 'checked="checked" ';
				} else {
					$item_arr['void:checked'] = '';
				}

				$items_arr[] = def_module::parseTemplate($tpl_item, $item_arr, false, $address);
			}

			$types  = umiObjectTypesCollection::getInstance();
			$typeId = $types->getTypeIdByHierarchyTypeName("emarket", "delivery_address");
			
			$onlySelfDeliveryExist = self::isOnlySelfDeliveryExist();
			
			if (!$onlySelfDeliveryExist) {
				$block_arr = array(
					'attribute:type-id'	=> $typeId,
					'attribute:type_id'	=> $typeId,
					'xlink:href'		=> 'udata://data/getCreateForm/' . $typeId,
					'subnodes:items'	=> $items_arr
				);
			}

			$regedit = regedit::getInstance();
			if ((bool) $regedit->getVal('//modules/emarket/delivery-with-address')) {
				$block_arr['delivery'] = $this->renderDeliveryList($order, $template, true);
			} else {
				$block_arr['void:delivery'] = '';
			}
			$block_arr['only_self_delivery']  = $onlySelfDeliveryExist ? 1 : 0;
			$block_arr['self_delivery_exist'] = self::isSelfDeliveryExist() ? 1 : 0;
			
			return def_module::parseTemplate($tpl_block, $block_arr);
		}

		public function removeDeliveryAddress($addressId = false) {
			if(!$addressId) {
				$addressId = getRequest("param0");
			}
			$addressId = (int)$addressId;

			$collection = umiObjectsCollection::getInstance();
			if(!$collection->isExists($addressId)) {
				throw new publicException("Wrong address id passed");
			}
			$customer = customer::get();
			$customer->delivery_addresses = array_filter($customer->delivery_addresses, create_function("\$v","return (\$v != {$addressId});"));
			$sel = new selector("objects");
			$sel->types("hierarchy-type")->name("emarket", "order");
			$sel->where("delivery_address")->equals($addressId);
			$sel->option('no-length')->value(true);
			if(!$sel->first) {
				$collection->delObject($addressId);
			}
			$this->redirect(getServer("HTTP_REFERER"));
		}

		public function chooseDeliveryAddress(order $order) {
			$addressId = getRequest('delivery-address');
			$urlPrefix = cmsController::getInstance()->getUrlPrefix() ? (cmsController::getInstance()->getUrlPrefix() . '/') : '';
			
			if(!$addressId) {
				$this->redirect($this->pre_lang . '/'. $urlPrefix . 'emarket/purchase/delivery/address/');
			}
			
			if (strpos($addressId, 'delivery_') === 0) {
				$order->delivery_address = false;
				$deliveryId = substr($addressId, 9);
				$_REQUEST['delivery-id'] = $deliveryId;
				$this->chooseDelivery($order);
			}
			if($addressId == 'new') {
				$controller = cmsController::getInstance();
				$collection = umiObjectsCollection::getInstance();
				$types      = umiObjectTypesCollection::getInstance();
				$typeId     = $types->getTypeIdByHierarchyTypeName("emarket", "delivery_address");
				$customer   = customer::get();
				$addressId  = $collection->addObject("Address for customer #".$customer->id, $typeId);
				$dataModule = $controller->getModule("data");
				if($dataModule) {
					$dataModule->saveEditedObjectWithIgnorePermissions($addressId, true, true);
				}
				if (!$customer->delivery_addresses) {
					$customer->delivery_addresses = array($addressId);
				} else {
					$customer->delivery_addresses = array_merge( $customer->delivery_addresses, array($addressId) );
				}
			}
			$order->delivery_address = $addressId;
			$order->commit();

			$this->redirect($this->pre_lang . '/'. $urlPrefix . 'emarket/purchase/delivery/choose/');
		}

		public function renderDeliveryList(order $order, $template, $selfDeliveryOnly = false) {
			$objects = umiObjectsCollection::getInstance();
			$tplPrefix = $selfDeliveryOnly ? 'self_' : '';
			list($tpl_block, $tpl_item_free, $tpl_item_priced) = def_module::loadTemplates("emarket/delivery/{$template}",
				$tplPrefix . 'delivery_block', $tplPrefix . 'delivery_item_free', $tplPrefix . 'delivery_item_priced');

			$deliveryIds = delivery::getList($selfDeliveryOnly); $items_arr = array();
			$currentDeliveryId = $order->getValue('delivery_id');

			foreach($deliveryIds as $delivery) {
				/* @var delivery $delivery*/
				$delivery = delivery::get($delivery);
				if($delivery->validate($order) == false) {
					continue;
				}

				$deliveryObject = $delivery->getObject();
				$deliveryPrice  = $delivery->getDeliveryPrice($order);
				$item_arr = array(
					'attribute:id'          => $deliveryObject->getId(),
					'attribute:name'        => $deliveryObject->name,
					'attribute:type-name'   => $deliveryObject->getType()->getName(),
					'attribute:price'       => $deliveryPrice.'',
					'xlink:href'            => $deliveryObject->xlink
				);

				$deliveryType = $objects->getObject($deliveryObject->getValue('delivery_type_id'));
				if ($deliveryType instanceof umiObject) {
					$deliveryTypeGuid = $deliveryType->getValue('delivery_type_guid');
					$deliveryTypeClass = $deliveryType->getValue('class_name');
					
					if ($deliveryTypeClass) {
						$item_arr['attribute:type-class-name'] = $deliveryTypeClass;
					}

					if ($deliveryTypeGuid) {
						$item_arr['attribute:type-guid'] = $deliveryTypeGuid;
					}
				}

				if($delivery->id == $currentDeliveryId) {
					$item_arr['attribute:active'] = 'active';
					$item_arr['void:checked'] = 'checked="checked" ';
				} else {
					$item_arr['void:checked'] = '';
				}

				$tpl_item = $deliveryPrice ? $tpl_item_priced : $tpl_item_free;
				$items_arr[] = def_module::parseTemplate($tpl_item, $item_arr, false, $deliveryObject->getId());
			}

			return def_module::parseTemplate($tpl_block, array('subnodes:items' => $items_arr));
		}

		public function chooseDelivery(order $order) {
			$urlPrefix = cmsController::getInstance()->getUrlPrefix() ? (cmsController::getInstance()->getUrlPrefix() . '/') : '';
			$deliveryId = getRequest('delivery-id');
			if(!$deliveryId) {
				$this->redirect($this->pre_lang . '/'. $urlPrefix . 'emarket/purchase/delivery/choose/');
			}

			$delivery = delivery::get($deliveryId);
			$deliveryPrice = (float) $delivery->getDeliveryPrice($order);

			$order->setValue('delivery_id', $deliveryId);
			$order->setValue('delivery_price', $deliveryPrice);
			$order->refresh();
			$order->commit();
			$this->redirect($this->pre_lang .'/'. $urlPrefix . 'emarket/purchase/payment/bonus/');
		}
		
		/**
		 * Возвращает true в случае если, в системе присутствуют только способы доставки
		 * типа "Самовывоз" или способы доставки вообще отстутствуют.
		 * @return boolean
		 */
		protected function isOnlySelfDeliveryExist() {
			$selfDeliveryTypeId = self::getSelfDeliveryTypeId();
			
			$deliveries = new selector('objects');
			$deliveries->types('hierarchy-type')->name('emarket', 'delivery');
			$deliveries->option('return')->value('delivery_type_id');
			$result = $deliveries->result();
			
			if (count($result) > 0) {
				foreach ($result as $delivery) {
					$deliveryTypeId = (int) $delivery['delivery_type_id'];
					
					if ($deliveryTypeId !== $selfDeliveryTypeId) {
						return false;
					}
				}
			}
			
			return true;
		}
		/**
		 * Возвращает true, если среди способов доставки присутствует 
		 * хотя бы один типа "Самовывоз"
		 * @return boolean
		 */
		protected function isSelfDeliveryExist() {
			$selfDeliveryTypeId = self::getSelfDeliveryTypeId();
			
			$selfDeliveries = new selector('objects');
			$selfDeliveries->types('hierarchy-type')->name('emarket', 'delivery');
			$selfDeliveries->where('delivery_type_id')->equals($selfDeliveryTypeId);
			
			return $selfDeliveries->length() > 0 ? true : false;
		}
		
		/**
		 * Возвращает ID объекта "Самовывоз" из справочника "Типы доставки"
		 * @return int ID объекта "Самовывоз"
		 */
		protected function getSelfDeliveryTypeId() {
			$deliveryTypes = new selector('objects');
			$deliveryTypes->types('object-type')->guid('emarket-deliverytype');
			$deliveryTypes->where('class_name')->equals('self');

			if (!$deliveryTypes->first instanceof iUmiObject) {
				return false;
			}

			return $deliveryTypes->first->getId();
		}	
		
		
	};
?>