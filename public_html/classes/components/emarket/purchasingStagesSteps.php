<?php

	use UmiCms\Service;

	/**
	 * Класс шагов этапов оформления заказа.
	 *
	 * У шагов этапов оформления заказа могут быть режимы.
	 * Режимы шагов этапов оформления заказа:
	 *
	 * 1) Автозаполнение адреса и данных покупателя через API Быстрого заказа от Яндекс;
	 * @link /emarket/purchase/autofill/yandex
	 *
	 * 2) Ввод обязательных данных пользователя;
	 * @link /emarket/purchase/required/personal
	 *
	 * 3) Сохранение обязательных данных пользователя;
	 * @link /emarket/purchase/required/personal/do
	 *
	 * 4) Выбор адреса доставки;
	 * @link /emarket/purchase/delivery/address
	 *
	 * 5) Сохранение выбора адреса доставки;
	 * @link /emarket/purchase/delivery/address/do
	 *
	 * 6) Выбор способа доставки;
	 * @link /emarket/purchase/delivery/choose
	 *
	 * 7) Сохранение выбора способа доставки;
	 * @link /emarket/purchase/delivery/choose/do
	 *
	 * 8) Выбор способа оплаты;
	 * @link /emarket/purchase/payment/choose
	 *
	 * 9) Сохранение выбора способа оплаты;
	 * @link /emarket/purchase/payment/choose/do
	 *
	 * 10) Выбор количества бонусов для оплаты;
	 * @link /emarket/purchase/payment/bonus
	 *
	 * 11) Сохранение выбора количества бонусов для оплаты;
	 * @link /emarket/purchase/payment/bonus/do
	 *
	 * 12) Оплата с помощью платежной системы;
	 * @link /emarket/purchase/payment/название_платежной системы
	 *
	 * 13) Удачное завершение оформления;
	 * @link /emarket/purchase/result/successful
	 *
	 * 14) Неудачное завершение оформления;
	 * @link /emarket/purchase/result/fail
	 *
	 * С этапами оформления можно ознакомиться в классе EmarketPurchasingStages.
	 */
	class EmarketPurchasingStagesSteps {

		/** @var emarket $module */
		public $module;

		/**
		 * Реализация автозаполнения данных покупателя через Яндекс.
		 * Работает с API Быстрого заказа от Яндекс.
		 * Сохраняет личные данные покупателя и адрес доставки.
		 * @link /emarket/purchase/autofill/yandex
		 * @see http://help.yandex.ru/partnermarket/?id=1121719
		 * @param order $order оформляемый заказ
		 * @return bool
		 * @throws coreException
		 */
		public function yandex(order $order) {
			if (!isset($_POST['operation_id']) || !isset($_POST['id'])) {
				return false;
			}

			$dataMapping = [
				'user' => [
					'fname' => 'firstname',
					'lname' => 'lastname',
					'father_name' => 'fathersname',
					'email' => 'email',
					'phone' => 'phone'
				],
				'delivery' => [
					'country' => 'country',
					'index' => 'zip',
					'city' => 'city',
					'street' => 'street',
					'house' => 'building',
					'flat' => 'flat',
					'order_comments' => 'comment'
				]
			];

			$user = customer::get();
			foreach ($dataMapping['user'] as $objectKey => $postKey) {
				$user->setValue($objectKey, getArrayKey($_POST, $postKey));
			}

			$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('emarket', 'delivery_address');
			$objectsCollection = umiObjectsCollection::getInstance();
			$address = $objectsCollection->getObjectByGUID('emarket-delivery_address-yandex' . getArrayKey($_POST, 'id'));

			if ($address instanceof iUmiObject) {
				/** @var iUmiObject $address */
				$addressId = $address->getId();
			} else {
				$addressId = $objectsCollection->addObject('Address for customer #' . $user->getId(), $typeId);
				$address = $objectsCollection->getObject($addressId);
				$address->setGUID('emarket-delivery_address-yandex' . getArrayKey($_POST, 'id'));
			}

			foreach ($dataMapping['delivery'] as $objectKey => $postKey) {
				$value = getArrayKey($_POST, $postKey);
				if ($value) {
					$address->setValue($objectKey, $value);
				}
			}

			if (!in_array($addressId, $user->getValue('delivery_addresses'))) {
				$user->setValue('delivery_addresses', array_merge($user->getValue('delivery_addresses'), [$addressId]));
			}

			$order->setValue('delivery_address', $addressId);
			$user->commit();
			$address->commit();
			$order->commit();
			return true;
		}

		/**
		 * Возвращает информацию для создания формы заполнения данных покупателя
		 * @link /emarket/purchase/autofill/personal
		 * @param order $order оформляемый заказ
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 */
		public function editPersonalInfo(order $order, $template) {
			list($tpl_block) = emarket::loadTemplates(
				'emarket/required/' . $template,
				'required_block'
			);

			$customerId = customer::get()->getId();

			return emarket::parseTemplate($tpl_block, [
				'customer_id' => $customerId,
				'customer-id' => $customerId
			]);
		}

		/**
		 * Сохраняет данные покупателя и перенаправляет на шаг "Доставка" и этап "Ввод адреса доставки"
		 * @link /emarket/purchase/autofill/personal/do
		 * @param order $order оформляемый заказ
		 * @throws coreException
		 * @throws privateException
		 * @throws errorPanicException
		 */
		public function savePersonalInfo($order) {
			$cmsController = cmsController::getInstance();
			/** @var DataForms $data */
			$data = $cmsController->getModule('data');
			$data->saveEditedObjectWithIgnorePermissions(customer::get()->getId(), false, true);
			$urlPrefix = $cmsController->getUrlPrefix() ? ($cmsController->getUrlPrefix() . '/') : '';

			$this->module->redirect($this->module->pre_lang . '/' . $urlPrefix . 'emarket/purchase/delivery/address/');
		}

		/**
		 * Сохраняет выбранный адрес либо создает новый
		 * и перенаправляет на этап выбора способа доставки
		 * @link /emarket/purchase/delivery/address/do
		 * @param order $order оформляемый заказ
		 * @throws coreException
		 * @throws privateException
		 * @throws errorPanicException
		 */
		public function chooseDeliveryAddress(order $order) {
			$addressId = getRequest('delivery-address');
			$cmsController = cmsController::getInstance();
			$urlPrefix = $cmsController->getUrlPrefix() ? ($cmsController->getUrlPrefix() . '/') : '';

			if (!$addressId) {
				$this->module->redirect($this->module->pre_lang . '/' . $urlPrefix . 'emarket/purchase/delivery/address/');
			}

			if (startsWith($addressId, 'delivery_')) {
				$order->delivery_address = false;
				$deliveryId = mb_substr($addressId, 9);
				$_REQUEST['delivery-id'] = $deliveryId;
				$this->chooseDelivery($order);
			}

			if ($addressId == 'new') {
				$collection = umiObjectsCollection::getInstance();
				$types = umiObjectTypesCollection::getInstance();
				$typeId = $types->getTypeIdByHierarchyTypeName('emarket', 'delivery_address');
				$customer = customer::get();
				$addressId = $collection->addObject('Address for customer #' . $customer->getId(), $typeId);
				/** @var DataForms $dataModule */
				$dataModule = $cmsController->getModule('data');

				if ($dataModule) {
					$dataModule->saveEditedObjectWithIgnorePermissions($addressId, true, true);
				}

				if ($customer->delivery_addresses) {
					$customer->delivery_addresses = array_merge($customer->delivery_addresses, [$addressId]);
				} else {
					$customer->delivery_addresses = [$addressId];
				}
			}

			$order->delivery_address = $addressId;
			$order->commit();

			$this->module->redirect($this->module->pre_lang . '/' . $urlPrefix . 'emarket/purchase/delivery/choose/');
		}

		/**
		 * Возвращает список доступных адресов доставки,
		 * в зависимости от настроек модуля может так же вернуть список
		 * способов доставки типа "Самовывоз".
		 * @link /emarket/purchase/delivery/address
		 * @param order $order оформляемый заказ
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws selectorException
		 * @throws coreException
		 */
		public function renderDeliveryAddressesList(order $order, $template = 'default') {
			list($tpl_block, $tpl_item) = emarket::loadTemplates(
				"emarket/delivery/{$template}",
				'delivery_address_block',
				'delivery_address_item'
			);

			$customer = customer::get();
			$addresses = $customer->getValue('delivery_addresses');
			$items_arr = [];
			$currentDeliveryId = $order->getValue('delivery_address');

			$collection = umiObjectsCollection::getInstance();

			if (is_array($addresses)) {
				foreach ($addresses as $address) {
					$addressObject = $collection->getObject($address);

					$item_arr = [
						'attribute:id' => $address,
						'attribute:name' => $addressObject->getName()
					];

					if ($address == $currentDeliveryId) {
						$item_arr['attribute:active'] = 'active';
						$item_arr['void:checked'] = 'checked="checked" ';
					} else {
						$item_arr['void:checked'] = '';
					}

					$items_arr[] = emarket::parseTemplate($tpl_item, $item_arr, false, $address);
				}
			}

			$types = umiObjectTypesCollection::getInstance();
			$typeId = $types->getTypeIdByHierarchyTypeName('emarket', 'delivery_address');

			$onlySelfDeliveryExist = $this->module->isOnlySelfDeliveryExist();

			if (!$onlySelfDeliveryExist) {
				$block_arr = [
					'attribute:type-id' => $typeId,
					'attribute:type_id' => $typeId,
					'xlink:href' => 'udata://data/getCreateForm/' . $typeId,
					'subnodes:items' => $items_arr
				];
			}

			$umiRegistry = Service::Registry();

			if ($umiRegistry->get('//modules/emarket/delivery-with-address')) {
				$block_arr['delivery'] = $this->renderDeliveryList($order, $template, true);
			} else {
				$block_arr['void:delivery'] = '';
			}

			$block_arr['only_self_delivery'] = $onlySelfDeliveryExist ? 1 : 0;
			$block_arr['self_delivery_exist'] = $this->module->isSelfDeliveryExist() ? 1 : 0;

			return emarket::parseTemplate($tpl_block, $block_arr);
		}

		/**
		 * Сохраняет выбранный способ доставки и его стоимость в заказ,
		 * и перенаправляет на этап выбора количества бонусов для оплаты
		 * @link /emarket/purchase/delivery/choose/do
		 * @param order $order оформляемый заказ
		 * @throws coreException
		 */
		public function chooseDelivery(order $order) {
			$cmsController = cmsController::getInstance();
			$urlPrefix = $cmsController->getUrlPrefix() ? ($cmsController->getUrlPrefix() . '/') : '';
			$deliveryId = getRequest('delivery-id');

			if (!$deliveryId) {
				$this->module->redirect($this->module->pre_lang . '/' . $urlPrefix . 'emarket/purchase/delivery/choose/');
			}

			/** @var delivery $delivery */
			$delivery = delivery::get($deliveryId);

			$order->setDelivery($delivery);
			$order->commit();

			$this->module->redirect($this->module->pre_lang . '/' . $urlPrefix . 'emarket/purchase/payment/bonus/');
		}

		/**
		 * Возвращает список способов доставки, доступных для заказа
		 * @link /emarket/purchase/delivery/choose/
		 * @param order $order оформляемый заказ
		 * @param string $template имя шаблона (для tpl)
		 * @param bool $selfDeliveryOnly выводить только способы доставки типа "Самовывоз"
		 * @return mixed
		 * @throws selectorException
		 * @throws coreException
		 */
		public function renderDeliveryList(order $order, $template, $selfDeliveryOnly = false) {
			$objects = umiObjectsCollection::getInstance();
			$tplPrefix = $selfDeliveryOnly ? 'self_' : '';

			list($tpl_block, $tpl_item_free, $tpl_item_priced) = emarket::loadTemplates(
				"emarket/delivery/{$template}",
				$tplPrefix . 'delivery_block',
				$tplPrefix . 'delivery_item_free',
				$tplPrefix . 'delivery_item_priced'
			);

			$deliveries = delivery::getList($selfDeliveryOnly);
			$items_arr = [];
			$currentDeliveryId = $order->getValue('delivery_id');

			foreach ($deliveries as $object) {
				/** @var delivery $delivery */
				$delivery = delivery::get($object);

				if (!$delivery->validate($order)) {
					continue;
				}

				$deliveryObject = $delivery->getObject();
				$deliveryPrice = $delivery->getDeliveryPrice($order);

				$item_arr = [
					'attribute:id' => $deliveryObject->getId(),
					'attribute:name' => $deliveryObject->getName(),
					'attribute:type-name' => $deliveryObject->getType()->getName(),
					'attribute:price' => $deliveryPrice . '',
					'xlink:href' => $deliveryObject->xlink
				];

				$deliveryType = $objects->getObject($deliveryObject->getValue('delivery_type_id'));

				if ($deliveryType instanceof iUmiObject) {
					$deliveryTypeGuid = $deliveryType->getValue('delivery_type_guid');
					$deliveryTypeClass = $deliveryType->getValue('class_name');

					if ($deliveryTypeClass) {
						$item_arr['attribute:type-class-name'] = $deliveryTypeClass;
					}

					if ($deliveryTypeGuid) {
						$item_arr['attribute:type-guid'] = $deliveryTypeGuid;
					}
				}

				if ($delivery->getId() == $currentDeliveryId) {
					$item_arr['attribute:active'] = 'active';
					$item_arr['void:checked'] = 'checked="checked" ';
				} else {
					$item_arr['void:checked'] = '';
				}

				$tpl_item = $deliveryPrice ? $tpl_item_priced : $tpl_item_free;
				$items_arr[] = emarket::parseTemplate($tpl_item, $item_arr, false, $deliveryObject->getId());
			}

			return emarket::parseTemplate($tpl_block, [
				'subnodes:items' => $items_arr
			]);
		}

		/**
		 * Сохраняет выбранный способ оплаты в заказ,
		 * и перенаправляет на этап оплаты выбранным способом
		 * @link /emarket/purchase/payment/choose/do
		 * @param order $order оформляемый заказ
		 * @throws errorPanicException
		 * @throws privateException
		 * @throws coreException
		 */
		public function choosePayment(order $order) {
			$paymentId = getRequest('payment-id');

			if (!$paymentId) {
				$this->module->errorNewMessage(getLabel('error-emarket-choose-payment'));
				$this->module->errorPanic();
			}

			$payment = payment::get($paymentId, $order);
			$controller = cmsController::getInstance();
			$urlPrefix = $controller->getUrlPrefix() ? ($controller->getUrlPrefix() . '/') : '';

			if ($payment instanceof payment) {
				$order->setPayment($payment);
				$order->commit();
				$paymentName = $payment->getCodeName();
				$url = "{$this->module->pre_lang}/" . $urlPrefix . "emarket/purchase/payment/{$paymentName}/";
			} else {
				$url = "{$this->module->pre_lang}/" . $urlPrefix . 'emarket/purchase/payment/choose/';
			}

			$this->module->redirect($url);
		}

		/**
		 * Возвращает список способов оплаты, доступных для заказа
		 * @link /emarket/purchase/payment/choose/
		 * @param order $order оформляемый заказ
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws selectorException
		 * @throws coreException
		 */
		public function renderPaymentsList(order $order, $template) {
			list($tpl_block, $tpl_item) = emarket::loadTemplates(
				'emarket/payment/' . $template,
				'payment_block',
				'payment_item'
			);

			$controller = cmsController::getInstance();
			$objects = umiObjectsCollection::getInstance();
			$paymentIds = payment::getList();

			$items_arr = [];
			$currentPaymentId = $order->getValue('payment_id');

			foreach ($paymentIds as $paymentId) {
				/** @var payment $payment */
				$payment = payment::get($paymentId, $order);

				/**
				 * @noinspection PhpMethodParametersCountMismatchInspection
				 * @see kupivkreditPayment
				 */
				if (!$payment->validate($order)) {
					continue;
				}

				$paymentObject = $payment->getObject();
				$paymentTypeId = $paymentObject->getValue('payment_type_id');
				$paymentTypeName = $objects->getObject($paymentTypeId)->getValue('class_name');

				if ($paymentTypeName == 'social') {
					continue;
				}

				$item_arr = [
					'attribute:id' => $paymentObject->getId(),
					'attribute:name' => $paymentObject->getName(),
					'attribute:type-name' => $paymentTypeName,
					'xlink:href' => $paymentObject->xlink
				];

				if ($paymentId == $currentPaymentId) {
					$item_arr['attribute:active'] = 'active';
				}

				$items_arr[] = emarket::parseTemplate($tpl_item, $item_arr, false, $paymentObject->getId());
			}

			$urlPrefix = $controller->getUrlPrefix() ? ($controller->getUrlPrefix() . '/') : '';
			$submitUrl = $this->module->pre_lang . '/' . $urlPrefix . 'emarket/purchase/payment/choose/do/';

			return emarket::parseTemplate($tpl_block, [
				'subnodes:items' => $items_arr,
				'submit_url' => $submitUrl
			]);
		}

		/**
		 * Применяет к заказу накопительную скидку в заданном размере.
		 * Если заказ был полностью оплачен по скидке - перенаправляет на страницу успешной оплаты,
		 * иначе на страницу выбора способа оплаты.
		 * @param order $order оплачиваемый заказ
		 * @throws coreException
		 */
		public function payByBonus(order $order) {
			$controller = cmsController::getInstance();
			$urlPrefix = $controller->getUrlPrefix() ? ($controller->getUrlPrefix() . '/') : '';
			$bonus = getRequest('bonus');

			if ($bonus || $bonus === 0 || $bonus === '0') {
				$order->setBonusDiscount($bonus);
				$order->refresh();
				if (!$order->getActualPrice()) {
					$order->setPaymentStatus('accepted');
					$order->order();
					$this->module->redirect($this->module->pre_lang . '/' . $urlPrefix . 'emarket/purchase/result/successful/');
				}
			}

			$this->module->redirect($this->module->pre_lang . '/' . $urlPrefix . 'emarket/purchase/payment/choose/');
		}

		/**
		 * Возвращает параметры оплаты заказа бонусами от накопительной скидки
		 * @param order $order оплачиваемый заказ
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws InvalidArgumentException
		 * @throws privateException
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function renderBonusPayment(order $order, $template) {
			list($tpl_block) = emarket::loadTemplates(
				'emarket/payment/' . $template,
				'bonus_block'
			);

			$customer = customer::get($order->getCustomerId());
			/** @var emarket|EmarketMacros $module */
			$module = $this->module;
			$block_arr = [
				'bonus' => $module->formatCurrencyPrice([
					'reserved_bonus' => $order->getBonusDiscount(),
					'available_bonus' => $customer->getValue('bonus'),
					'spent_bonus' => $customer->getValue('spent_bonus'),
					'actual_total_price' => $order->getActualPrice(),
				])
			];

			$block_arr['void:reserved_bonus'] = $module->parsePriceTpl($template,
				$module->formatCurrencyPrice(
					[
						'actual' => $order->getBonusDiscount()
					]
				)
			);

			$block_arr['void:available_bonus'] = $module->parsePriceTpl($template,
				$module->formatCurrencyPrice(
					[
						'actual' => $customer->getValue('bonus')
					]
				)
			);

			$block_arr['void:spent_bonus'] = $module->parsePriceTpl($template,
				$module->formatCurrencyPrice(
					[
						'actual' => $customer->getValue('spent_bonus')
					]
				)
			);

			$block_arr['void:actual_total_price'] = $module->parsePriceTpl($template,
				$module->formatCurrencyPrice(
					[
						'actual' => $order->getActualPrice()
					]
				)
			);

			return emarket::parseTemplate($tpl_block, $block_arr);
		}

		/**
		 * Выполняет шаг оформления заказа - вывод результата оформления заказа
		 * @link /emarket/purchase/result/$stage/
		 * @param order $order зазаз
		 * @param string $stage этап шага
		 * @param $mode
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws selectorException
		 * @throws publicException
		 * @throws coreException
		 */
		public function formResult(order $order, $stage, $mode, $template) {
			list($tpl_successful, $tpl_failed) = emarket::loadTemplates(
				'emarket/' . $template,
				'purchase_successful',
				'purchase_failed'
			);

			$tpl_block = ($stage == 'successful') ? $tpl_successful : $tpl_failed;

			$orderId = null;
			$customer = customer::get();

			if ($order->isEmpty()) {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('customer_id')->equals($customer->getId());
				$sel->where('domain_id')->equals(Service::DomainDetector()->detectId());
				$sel->option('no-length')->value(true);
				$sel->option('load-all-props')->value(true);
				$sel->order('id')->desc();
				if ($sel->first()) {
					$orderId = $sel->first()->id;
				}
			} else {
				$orderId = $order->getId();
			}

			$paymentId = order::get($orderId)->getValue('payment_id');
			$payment = payment::get($paymentId, order::get($orderId));
			$invoiceLink = '';

			if ($payment instanceof invoicePayment) {
				$invoiceLink = $payment->getInvoiceLink();
			}

			$result = [
				'status' => $stage,
				'order' => ['attribute:id' => $orderId],
				'void:order_id' => $orderId,
				'personal_params' => $this->module->getPersonalLinkParams($customer->getId()),
				'invoice_link' => $invoiceLink
			];

			return emarket::parseTemplate($tpl_block, $result);
		}
	}
