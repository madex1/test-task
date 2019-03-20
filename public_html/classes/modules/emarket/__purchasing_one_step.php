<?php
	abstract class __emarket_purchasing_one_step extends def_module {

		public function purchasing_one_step($template = 'onestep'){
			list($purchasing_one_step) = def_module::loadTemplates("emarket/onestep/{$template}.tpl", 'purchasing_one_step');

			/** @var emarket $this */
			$order = $this->getBasketOrder();
			if ($order->isEmpty()) {
				throw new publicException('%error-market-empty-basket%');
			}
			$result = array();

			if(!permissionsCollection::getInstance()->isAuth()){
				$result['onestep']['customer'] = $this->personalInfo($template);
				if (def_module::isXSLTResultMode()) {
					$result['onestep']['customer']['@id'] = customer::get()->getId();
				}
			}

			$result['onestep']['delivery'] = $this->customerDeliveryList($template);
			$result['onestep']['delivery_choose'] = $this->renderDeliveryList($order, $template);
			$result['onestep']['payment'] = $this->paymentsList($template);

			return def_module::parseTemplate($purchasing_one_step, $result);
		}

		public function personalInfo($template = 'onestep') {
			if (!permissionsCollection::getInstance()->isAuth()){
				$customerId = customer::get()->getId();
				$cmsController = cmsController::getInstance();
				$data = $cmsController->getModule('data');

				return $data->getEditForm($customerId, '../../emarket/customer/' . $template);
			} else return '';
		}

		public function paymentsList($template = 'onestep') {
			$order = $this->getBasketOrder(false);
			list($tpl_block, $tpl_item) = def_module::loadTemplates("emarket/payment/{$template}.tpl", 'payment_block', 'payment_item');

			$payements = payment::getList();
			$items = array();
			$currentPaymentId = $order->getValue('payment_id');

			foreach($payements as $paymentId) {
				$payment = payment::get($paymentId, $order);
				if($payment->validate($order) == false) {
					continue;
				}
				$paymentObject = $payment->getObject();
				$paymentTypeId = $paymentObject->getValue('payment_type_id');
				$paymentTypeName = umiObjectsCollection::getInstance()->getObject($paymentTypeId)->getValue('class_name');

				if($paymentTypeName == 'social') {
					continue;
				}

				$item = array(
					'attribute:id'			=> $paymentObject->getId(),
					'attribute:name'		=> $paymentObject->name,
					'attribute:type-name'	=> $paymentTypeName,
					'xlink:href'			=> $paymentObject->xlink
				);

				if($paymentId == $currentPaymentId) {
					$item['attribute:active'] = 'active';
				}

				$items[] = def_module::parseTemplate($tpl_item, $item, false, $paymentObject->getId());
			}

			if($tpl_block && !def_module::isXSLTResultMode()) {
				return def_module::parseTemplate($tpl_block, array('items' => $items));
			} else {
				return array('items' => array('nodes:item'	=> $items));
			}
		}

		public function saveInfo() {
			$order = $this->getBasketOrder(false);
			//сохранение регистрационных данных
			$cmsController = cmsController::getInstance();
			$data = $cmsController->getModule('data');
			$data->saveEditedObject(customer::get()->getId(), false, true);

			//сохранение адреса доставки
			$addressId = getRequest('delivery-address');
			if($addressId == 'new') {
				$collection = umiObjectsCollection::getInstance();
				$types      = umiObjectTypesCollection::getInstance();
				$typeId     = $types->getTypeIdByHierarchyTypeName("emarket", "delivery_address");
				$customer   = customer::get();
				$addressId  = $collection->addObject("Address for customer #" . $customer->getId(), $typeId);
				$dataModule = $cmsController->getModule("data");
				if($dataModule) {
					if(!$dataModule->saveEditedObject($addressId, true, true))
						$dataModule->saveEditedObjectWithIgnorePermissions($addressId, true, true); // начиная с версии 2.9.5
				}
				$customer->delivery_addresses = array_merge( $customer->delivery_addresses, array($addressId) );
			}
			$order->delivery_address = $addressId;

			//сохранение способа доставки
			$deliveryId = getRequest('delivery-id');
			if($deliveryId){
				$delivery = delivery::get($deliveryId);
				$deliveryPrice = (float) $delivery->getDeliveryPrice($order);
				$order->setValue('delivery_id', $deliveryId);
				$order->setValue('delivery_price', $deliveryPrice);
			}

			//сохранение способа оплаты и редирект на итоговую страницу
			$order->setValue('payment_id', getRequest('payment-id'));

			$order->refresh();

			$paymentId = getRequest('payment-id');
			if(!$paymentId) {
				$this->errorNewMessage(getLabel('error-emarket-choose-payment'));
				$this->errorPanic();
			}
			$payment = payment::get($paymentId, $order);

			if($payment instanceof payment) {
				$paymentName = $payment->getCodeName();
				$url = "{$this->pre_lang}/" . cmsController::getInstance()->getUrlPrefix()."emarket/purchase/payment/{$paymentName}/";
			} else {
				$url = "{$this->pre_lang}/" . cmsController::getInstance()->getUrlPrefix()."emarket/cart/";
			}
			$this->redirect($url);
		}
	
		/**
		 *  Генерирует и возвращает ссылку на оформление заказа в соответствии с настройкой модуля магазина - "покупать в 1 шаг" 
		 *  @return string  
		 */
		public function getPurchaseLink()
		{
			$regedit = regedit::getInstance();
			return $this->pre_lang . "/" . cmsController::getInstance()->getUrlPrefix()
				. "emarket/"
				. ( $regedit->getVal('//modules/emarket/purchasing-one-step') ? 'purchasing_one_step' : 'purchase' ) ;
		}
	};
?>
