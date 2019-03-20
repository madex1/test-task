<?php
	abstract class __emarket_events {

		public function onCronCheckExpiredCustomers(iUmiEventPoint $event) {
			$customerTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('emarket-customer');
			$expiration = umiObjectsExpiration::getInstance();
			$customers = $expiration->getExpiredObjectsByTypeId($customerTypeId, $expiration->getLimit());
			if (count($customers) > 0) {
				$objects = umiObjectsCollection::getInstance();
				foreach($customers as $customerId) {
					$selector = new selector('objects');
					$selector->types('object-type')->name('emarket', 'order');
					$selector->where('customer_id')->equals($customerId);
					$selector->option('no-length')->value(true);
					$customer = new customer($objects->getObject($customerId));
					if ($selector->first) {
						$customer->freeze();
						foreach($selector->result as $order) {
							if (is_null($order->status_id)) {
								if (!$expiration->isExpirationExists($order->getId())) {
									$expiration->add($order->getId());
								}
							}
						}
					} else {
						$deliveryAddresses = $customer->delivery_addresses;
						if (!is_null($deliveryAddresses) && is_array($deliveryAddresses) && count($deliveryAddresses) > 0) {
							foreach($deliveryAddresses as $addressId) {
								$objects->delObject($addressId);
							}
						}
						$customer->delete();
					}
				}
			}
		}

		public function onCronCheckExpiredCustomersOneClick(iUmiEventPoint $event) {
			$customerOneClickTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('emarket-purchase-oneclick');
			$expiration = umiObjectsExpiration::getInstance();
			$customers = $expiration->getExpiredObjectsByTypeId($customerOneClickTypeId, $expiration->getLimit());
			if (count($customers) > 0) {
				foreach($customers as $customerId) {
					$selector = new selector('objects');
					$selector->types('object-type')->name('emarket', 'order');
					$selector->where('purchaser_one_click')->equals($customerId);
					$selector->option('no-length')->value(true);
					if ($selector->first) {
						$expiration->clear($customerId);
						foreach($selector->result as $order) {
							if (is_null($order->status_id)) {
								if (!$expiration->isExpirationExists($order->getId())) {
									$expiration->add($order->getId());
								}
							}
						}
					} else {
						$objects = umiObjectsCollection::getInstance();
						$customer = $objects->getObject($customerId);
						$customer->delete();
					}
				}
			}
		}

		public function onCronCheckExpiredOrders(iUmiEventPoint $event) {
			$orderTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('emarket-order');
			$expiration = umiObjectsExpiration::getInstance();
			$orders = $expiration->getExpiredObjectsByTypeId($orderTypeId, $expiration->getLimit());
			if (count($orders) > 0) {
				$objects = umiObjectsCollection::getInstance();
				foreach($orders as $orderId) {
					$order = $objects->getObject($orderId);
					if (is_null($order->status_id)) {
						$order = order::get($orderId);
						$items = $order->getItems();
						foreach($items as $item) {
							$orderItem = orderItem::get($item->getId());
							$orderItem->remove();
						}
						$customerId = $order->customer_id;
						if (!$expiration->isExpirationExists($customerId)) {
							$expiration->add($customerId);
						}
						$order->delete();
					}
				}
			}
		}

		/**
		 * Обработчик срабатывания системного крона.
		 * Обновляет курсы валют.
		 * @param iUmiEventPoint $event событие срабатывания системного крона.
		 * @return bool
		 * @throws Exception
		 * @throws umiRemoteFileGetterException
		 */
		public function onCronSyncCurrency(iUmiEventPoint $event) {
			return $this->updateCurrenciesHandler();
		}

		/**
		 * Обновляет курсы валют.
		 * @return bool
		 * @throws Exception
		 * @throws umiRemoteFileGetterException
		 */
		public function updateCurrenciesHandler() {
			$regedit = regedit::getInstance();
			if(!$regedit->getVal('//modules/emarket/enable-currency')) return false;
			
			$config = mainConfiguration::getInstance();
			$sourceUrl = $config->get('modules', 'emarket.currency.sync.source');
			$xslPath = CURRENT_WORKING_DIR .'/xsl/currencies/' . $config->get('modules', 'emarket.currency.sync.xsl');
			
			$originalXml = umiRemoteFileGetter::get($sourceUrl);
			if(function_exists('mb_detect_encoding') && (mb_detect_encoding($originalXml, "UTF-8, ISO-8859-1, GBK, CP1251") != "UTF-8")) {
				$originalXml = iconv ("CP1251", "UTF-8", $originalXml);
				$originalXml = preg_replace("/(encoding=\"windows-1251\")/i", "encoding=\"UTF-8\"", $originalXml);
			}
			
			$xslt = new xsltProcessor;
			secure_load_dom_document($originalXml, $dom);
			$xslt->importStyleSheet(DomDocument::load($xslPath));
			$resultXml = $xslt->transformToXML($dom);
			$tmpPath = SYS_CACHE_RUNTIME . 'tmpcurrencies.xml';
			file_put_contents($tmpPath, $resultXml);
			
			$currenciesList = new baseXmlConfig($tmpPath);
			$currencies = $currenciesList->getList('/Exchange/Exchange_Rates', array (
				'code'		=> '/New_Country',
				'rate'		=> '/Rate',
				'nominal'	=> '/Nominal'
			));
			
			foreach($currencies as $currencyInfo) {
				$code = getArrayKey($currencyInfo, 'code');
				
				try {
					if($currency = $this->getCurrency($code)) {
						$currency->nominal = getArrayKey($currencyInfo, 'nominal');
						$currency->rate = getArrayKey($currencyInfo, 'rate');
						$currency->commit();
					}
				} catch(privateException $e) {}
			}
			
			unlink($tmpPath);
		}
		// Notification events listeners
		public function onModifyProperty(iUmiEventPoint $event) {
			$entity = $event->getRef("entity");
			if($entity instanceof iUmiObject) {
				$allowedProperties = array("status_id", "payment_status_id", "delivery_status_id");
				$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('emarket', 'order');
				if(($entity->getTypeId() == $typeId) &&
					(in_array($event->getParam("property"), $allowedProperties) ) &&
					($event->getParam("newValue") != $event->getParam("oldValue")) ) {
					if ($event->getParam("property") == 'payment_status_id' && $event->getParam("newValue") == order::getStatusByCode('accepted', 'order_payment_status')) {
						self::addBonus($entity->getId());
					}
					if ($event->getParam("property") == 'status_id' && ($event->getParam("newValue") == order::getStatusByCode('canceled') || $event->getParam("newValue") == order::getStatusByCode('rejected'))) {
						self::returnBonus($entity->getId());
					}
					if ($event->getParam('property') == 'status_id' && $event->getParam('oldValue') != $event->getParam('newValue')) {
						$object = $event->getRef('entity');
						if ($event->getParam('newValue') == order::getStatusByCode('ready')) {
							$emarketTop = new emarketTop();
							$emarketTop->addOrder($object);
						}
						if ($event->getParam('oldValue') == order::getStatusByCode('ready')) {
							$emarketTop = new emarketTop();
							$emarketTop->delOrder($object);
						}
					}
					$this->notifyOrderStatusChange(order::get($entity->getId()), $event->getParam("property"));
				}
			}
		}
		
		public function onModifyObject(iUmiEventPoint $event) {
			static $modifiedCache = array();
			static $orderStatus = array();
			$object = $event->getRef("object");
			$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('emarket', 'order');
			if($object->getTypeId() != $typeId) return;
			if($event->getMode() == "before") {
				$data = getRequest("data");
				$id   = $object->getId();
				$newOrderStatus    = getArrayKey($data[$id], 'status_id');
				$newPaymentStatus  = getArrayKey($data[$id], 'payment_status_id');
				$newDeliveryStatus = getArrayKey($data[$id], 'delivery_status_id');
				switch(true) {
				   case ($newOrderStatus != $object->getValue("status_id") ) : {
					   $modifiedCache[$object->getId()] = "status_id";
					   $orderStatus[$object->getId()]['old_status_id'] = $object->status_id;
					   break;
				   }
				   case ($newDeliveryStatus != $object->getValue("delivery_status_id")) : $modifiedCache[$object->getId()] = "delivery_status_id"; break;
				   case ($newPaymentStatus != $object->getValue("payment_status_id") ) : $modifiedCache[$object->getId()] = "payment_status_id"; break;				   
				}
			} else {
				if(isset($modifiedCache[$object->getId()])) {
					if ($modifiedCache[$object->getId()] == 'payment_status_id' && $object->getValue("payment_status_id") == order::getStatusByCode('accepted', 'order_payment_status')) {
						self::addBonus($object->getId());
					}
					if ($modifiedCache[$object->getId()] == 'status_id' && ($object->getValue("status_id") == order::getStatusByCode('canceled') || $object->getValue("status_id") == order::getStatusByCode('rejected'))) {
						self::returnBonus($object->getId());
					}
					if(array_key_exists($object->getId(), $orderStatus) && $orderStatus[$object->getId()]['old_status_id'] != $object->status_id) {
						if ($object->status_id == order::getStatusByCode('ready')) {
							$emarketTop = new emarketTop();
							$emarketTop->addOrder($object);
						}
						if ($orderStatus[$object->getId()]['old_status_id'] == order::getStatusByCode('ready')) {
							$emarketTop = new emarketTop();
							$emarketTop->delOrder($object);
						}
					}
					$this->notifyOrderStatusChange(order::get($object->getId()), $modifiedCache[$object->getId()]);
				}
			}
		}
		public function onStatusChanged(iUmiEventPoint $event) {
			if($event->getMode() == "after" &&
				$event->getParam("old-status-id") != $event->getParam("new-status-id")) {
				if ($event->getParam("new-status-id") == order::getStatusByCode('canceled') || $event->getParam("new-status-id") == order::getStatusByCode('rejected')) {
					self::returnBonus($event->getRef("order")->getId());
				}
				$order = $event->getRef("order");
				if($event->getParam("old-status-id") != $event->getParam("new-status-id")) {
					if ($event->getParam("new-status-id") == order::getStatusByCode('ready')) {
						$emarketTop = new emarketTop();
						$emarketTop->addOrder($order);
					}
					if ($event->getParam("old-status-id") == order::getStatusByCode('ready')) {
						$emarketTop = new emarketTop();
						$emarketTop->delOrder($order);
					}
				}
				$this->notifyOrderStatusChange($order, "status_id");
			}
		}
		public function onPaymentStatusChanged(iUmiEventPoint $event) {
			if($event->getMode() == "after" &&
				$event->getParam("old-status-id") != $event->getParam("new-status-id")) {
				$order = $event->getRef("order");					
				if ($event->getParam("new-status-id") == order::getStatusByCode('accepted', 'order_payment_status')) {
					self::addBonus($order->getId());
				}
				$this->notifyOrderStatusChange($order, "payment_status_id");
			}
		}
		public function onDeliveryStatusChanged(iUmiEventPoint $event) {
			if($event->getMode() == "after" &&
				$event->getParam("old-status-id") != $event->getParam("new-status-id")) {
				$order = $event->getRef("order");
				$this->notifyOrderStatusChange($order, "delivery_status_id");
			}
		}

		public function onOrderDeleteCleanRelations(iUmiEventPoint $e) {
			if($e->getMode() != 'before') return;
			$object = $e->getRef('object');
			if($object instanceof iUmiObject) {
				$type = selector::get('object-type')->id($object->getTypeId());
				if($type && $type->getMethod() == 'order') {
					$order = order::get($object->id);
					$orderItems = $order->getItems();
					if (count($orderItems) > 0) {
						foreach($orderItems as $item) {
							$orderItem = orderItem::get($item->getId());
							$orderItem->remove();
						}
					}
					$customerId = $order->getCustomerId();
					if (!is_null($customerId)) {
						$customer = selector::get('object')->id($customerId);
						$method = $customer->getMethod();
						if ($customer->getMethod() == 'customer') {
							umiObjectsExpiration::getInstance()->add($customerId);
						}
					}
					$customerOneClickId = $order->getValue('purchaser_one_click');
					if (!is_null($customerOneClickId)) {
						$customerOneClick = selector::get('object')->id($customerOneClickId);
						if ($customerOneClick instanceof umiObject && $customerOneClick->getTypeGUID() == 'emarket-purchase-oneclick') {
							umiObjectsExpiration::getInstance()->add($customerOneClickId);
						}
					}
					$order->commit();
				}
			}
		}

		protected function addBonus($orderId) {
			$order = order::get($orderId);
			if ($discount = bonusDiscount::search($order)) {
				$price = $order->getActualPrice();
				$bonus = $price - $discount->recalcPrice($price);
				$customerId = $order->getCustomerId();
				$customer = umiObjectsCollection::getInstance()->getObject($customerId);
				$customer->bonus = $customer->bonus + $bonus;
				$customer->commit();
			}			
		}
		
		protected function returnBonus($orderId) {
			$order = order::get($orderId);
			$customerId = $order->getCustomerId();
			$customer = umiObjectsCollection::getInstance()->getObject($customerId);	
			$order->setBonusDiscount(0);
			$order->refresh();	
		}
	};
?>
