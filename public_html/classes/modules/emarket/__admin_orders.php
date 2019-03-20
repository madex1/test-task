<?php
	abstract class __emarket_admin_orders extends baseModuleAdmin {
		public function orders () {
			$this->setDataType("list");
			$this->setActionType("view");

			if ($this->ifNotXmlMode()) {
				return $this->doData();
			}

			$limit = getRequest('per_page_limit');
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('number')->isnotnull();
			$sel->where('name')->notequals('dummy');
			$sel->limit($offset, $limit);

			if (!getRequest('order_filter')) {
				$sel->order('order_date')->desc();
			}

			selectorHelper::detectFilters($sel);
			$domains = getRequest('domain_id');

			if (is_array($domains) && count($domains)) {
				$domainsCollection = domainsCollection::getInstance();
				if (count($domainsCollection->getList()) > 1) {
					$sel->where('domain_id')->equals($domains[0]);
				}
			}

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "objects");
			$this->setData($data, $sel->length);

			return $this->doData();
		}


		public function order_edit() {
			$object = $this->expectObject("param0", true);
			$mode = (string) getRequest('param1');
			$objectId = $object->getId();

			$this->setHeaderLabel("header-users-edit-" . $this->getObjectTypeMethod($object));
			$this->checkSv($objectId);

			$inputData = array(
				"object" => $object,
				"allowed-element-types" => array('emarket', 'order')
			);

			if ($mode == "do") {
				$oldDeliveryPrice = $object->getValue('delivery_price');
				$object = $this->saveEditedObjectData($inputData);
				$newDeliveryPrice = $object->getValue('delivery_price');
				$order = order::get($object->getId());

				$itemsAmounts = getRequest('order-amount-item');
				$itemsToDelete = getRequest('order-del-item');
				$itemsDiscountValues =  getRequest('item-discount-value');
				$orderDiscountValue = getRequest('order-discount-value');

				$isChanged = false;

				if (is_array($itemsAmounts)) {
					foreach ($itemsAmounts as $itemId => $amount) {
						$item = $order->getItem($itemId);

						if (!$item instanceof orderItem) {
							continue;
						}

						$amount = (int) $amount;

						if ($item->getAmount() == $amount) {
							continue;
						}

						$item->setAmount($amount);
						$item->commit();
						$isChanged = true;
					}
				}

				if (is_array($itemsDiscountValues)) {
					foreach ($itemsDiscountValues as $itemId => $itemDiscountValue) {
						$item = $order->getItem($itemId);

						if (!$item instanceof orderItem) {
							continue;
						}

						$itemDiscountValue = (float) $itemDiscountValue;

						if ($item->getDiscountValue() == $itemDiscountValue) {
							continue;
						}

						$item->setDiscountValue($itemDiscountValue);
						$item->commit();
						$isChanged = true;
					}
				}

				if (isset($orderDiscountValue)){
					if ($order->getDiscountValue() != $orderDiscountValue) {
						$order->setDiscountValue($orderDiscountValue);
						$isChanged = true;
					}
				}

				if (is_array($itemsToDelete)) {
					foreach ($itemsToDelete as $itemId) {
						$item = orderItem::get($itemId);

						if (!$item instanceof orderItem) {
							continue;
						}

						$order->removeItem($item);
						$isChanged = true;
					}
				}

				if ($isChanged) {
					$order->refresh();
					$order->commit();
				}

				if ($oldDeliveryPrice != $newDeliveryPrice && !$isChanged) {
					$originalPrice = $object->getValue('total_original_price');
					$totalPrice = $originalPrice;

					$discount = $order->getDiscount();
					if ($discount instanceof discount) {
						$totalPrice = $discount->recalcPrice($originalPrice);
					}

					$totalPrice += $newDeliveryPrice;
					$object->setValue('total_price', $totalPrice);
					$object->commit();
				}

				$this->chooseRedirect();
			}

			$this->setDataType("form");
			$this->setActionType("modify");

			$data = $this->prepareData($inputData, "object");

			$order = order::get($object->getId());
			$paymentId = $order->getValue('payment_id');

			if ($paymentId) {
				$payment = payment::get($paymentId, $order);
				if (method_exists($payment, "admin" . $mode)) {
					$arrayBlock = call_user_func(array($payment, "admin" . $mode));
					$arrayBlock["@type"] = $payment->getCodeName();
					$data["payment"] = $arrayBlock;
				}
			}

			$this->setData($data);
			return $this->doData();
		}

		/**
		 * Реализация отображения настроек для выбранной в заказе системы оплаты
		 *
		 * Отображает набор настроек, уникальных для системы оплаты
		 *
		 * @throws publicAdminException
		 */
		public function order_payment() {
			$object = $this->expectObject("param0", true);
			$action = (string) getRequest('param1');

			if (!$action) {
				emarket::redirect("/admin/emarket/order_edit/" . $object->getId() . "/");
			}

			$order = order::get($object->getId());
			cmsController::getInstance()->errorUrl = "/admin/emarket/order_edit/" . $object->getId() . "/";
			$paymentId = $order->getValue('payment_id');

			if (!$paymentId) {
				$this->errorNewMessage(getLabel("error-no-payment"));
				$this->errorPanic();
			}

			$payment = payment::get($paymentId, $order);

			if (method_exists($payment, "admin_" . $action)) {
				$response = call_user_func(array($payment, "admin_" . $action));
				$this->setData($response);

				if ($response["status"] != "OK") {
					if (!getRequest("xmlMode") && !getRequest("jsonMode")) {
						$this->errorNewMessage(getLabel("error-payment-system", false, $response['result']));
						$this->errorPanic();
					}
				} else {
					if (!getRequest("xmlMode") && !getRequest("jsonMode")) {
						emarket::redirect("/admin/emarket/order_edit/" . $object->getId() . "/");
					}
				}
			}

			$this->doData();
		}

		public function order_printable() {
			$object = $this->expectObject("param0", true);
			$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('emarket', 'order');

			if ($object->getTypeId() != $typeId) {
				throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
			}

			$orderId = $object->getId();
			$uri = "uobject://{$orderId}/?transform=sys-tpls/emarket-order-printable.xsl";
			$result = file_get_contents($uri);

			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->charset('utf-8');
			$buffer->contentType('text/html');
			$buffer->clear();
			$buffer->push($result);
			$buffer->end();
		}
	};
?>
