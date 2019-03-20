<?php

	use UmiCms\Service;
	use UmiCms\Classes\Components\Emarket\Delivery\Address;

	/** Класс функционала оформления заказа в 1 шаг */
	class EmarketPurchasingOneStep {

		/** @var emarket|EmarketPurchasingOneStep|EmarketPurchasingStagesSteps $module */
		public $module;

		/**
		 * Возвращает данные всех этапов оформления заказа для построения единой формы
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws ErrorException
		 * @throws coreException
		 * @throws publicException
		 * @throws selectorException
		 */
		public function purchasing_one_step($template = 'onestep') {
			$module = $this->module;
			$order = $module->getBasketOrder();

			if ($order->isEmpty()) {
				throw new publicException('%error-market-empty-basket%');
			}

			$result = [];

			if (Service::Auth()->isLoginAsGuest()) {
				$result['onestep']['customer'] = $module->personalInfo($template);

				if (emarket::isXSLTResultMode()) {
					$result['onestep']['customer']['@id'] = customer::get()->getId();
				}
			}

			if ($module->isDeliveryAvailable()) {
				$result['onestep']['delivery'] = $module->customerDeliveryList($template);
				$result['onestep']['delivery_choose'] = $module->renderDeliveryList($order, $template);
			}

			if ($module->isPaymentAvailable()) {
				$result['onestep']['payment'] = $module->paymentsList($template);
			}

			list($oneStepTemplate) = emarket::loadTemplates(
				"emarket/onestep/{$template}.tpl",
				'purchasing_one_step'
			);

			return emarket::parseTemplate($oneStepTemplate, $result);
		}

		/**
		 * Возвращает список адресов пользователя
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws coreException
		 */
		public function customerDeliveryList($template = 'default') {
			$module = $this->module;
			$order = $module->getBasketOrder();
			return $module->renderDeliveryAddressesList($order, $template);
		}

		/**
		 * Выводит информацию для построения формы заполнения данных покупателя
		 * @param string $template имя шаблона (для tpl)
		 * @return string
		 * @throws coreException
		 */
		public function personalInfo($template = 'onestep') {
			if (Service::Auth()->isAuthorized()) {
				return '';
			}

			$customerId = customer::get()->getId();
			$cmsController = cmsController::getInstance();
			/** @var DataForms $data */
			$data = $cmsController->getModule('data');
			return $data->getEditForm($customerId, '../../emarket/customer/' . $template);
		}

		/**
		 * Возвращает список способов оплаты
		 * @param string $template имя шаблона (для tpl)
		 * @return array|mixed
		 * @throws ErrorException
		 * @throws coreException
		 * @throws selectorException
		 */
		public function paymentsList($template = 'onestep') {
			$umiObjects = umiObjectsCollection::getInstance();

			list($blockTemplate, $itemTemplate) = emarket::loadTemplates(
				"emarket/payment/{$template}.tpl",
				'payment_block',
				'payment_item'
			);

			$paymentList = payment::getList();
			$order = $this->module->getBasketOrder(false);
			$currentPaymentId = $order->getValue('payment_id');
			$items = [];

			foreach ($paymentList as $paymentObject) {
				$payment = payment::get($paymentObject, $order);

				if (!$payment instanceof payment) {
					continue;
				}

				/** @noinspection PhpMethodParametersCountMismatchInspection */
				if (!$payment->validate($order)) {
					continue;
				}

				$paymentTypeId = $paymentObject->getValue('payment_type_id');
				$paymentTypeName = $umiObjects->getObject($paymentTypeId)->getValue('class_name');

				if ($paymentTypeName == 'social') {
					continue;
				}

				$item = [
					'attribute:id' => $paymentObject->getId(),
					'attribute:name' => $paymentObject->getName(),
					'attribute:type-name' => $paymentTypeName,
					'xlink:href' => $paymentObject->getXlink(),
				];

				if ($paymentObject->getId() == $currentPaymentId) {
					$item['attribute:active'] = 'active';
				}

				$items[] = emarket::parseTemplate($itemTemplate, $item, false, $paymentObject->getId());
			}

			if ($blockTemplate && !emarket::isXSLTResultMode()) {
				return emarket::parseTemplate($blockTemplate, [
					'items' => $items,
				]);
			}

			return [
				'items' => [
					'nodes:item' => $items,
				],
			];
		}

		/**
		 * Принимает данные от единой формы оформления заказа и оформляет заказ.
		 * Завершает работу перенаправлением на шаг оплаты или страницу успешного оформления.
		 * @param bool $addressRequired является ли выбор адреса обязательным
		 * @param bool $deliveryRequired является ли выбор способа доставки обязательным
		 * @param bool $paymentRequired является ли выбор способа оплаты обязательным
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 */
		public function saveInfo($addressRequired = false, $deliveryRequired = false, $paymentRequired = true) {
			$module = $this->module;
			$module->saveCustomer();
			$order = $module->getBasketOrder(false);
			$module->processOneStepAddress($order, $addressRequired);
			$module->processOneStepDelivery($order, $deliveryRequired);
			$payment = $module->processOneStepPayment($order, $paymentRequired);
			$module->processOneStepOrder($order, $payment);
		}

		/**
		 * Сохраняет данные текущего объекта покупателя, переданные в post-запросе
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 */
		public function saveCustomer() {
			/** @var DataForms $data */
			$data = cmsController::getInstance()->getModule('data');
			$data->saveEditedObject(customer::get()->getId());
		}

		/**
		 * Пытается сохранить выбранный адрес доставки, если это необходимо.
		 * В случае ошибки прерывает запрос.
		 * @param order $order оформляемый заказ
		 * @param bool $addressRequired является ли выбор адреса обязательным
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 */
		public function processOneStepAddress($order, $addressRequired) {
			$module = $this->module;

			if (!$module->isDeliveryAvailable()) {
				return;
			}

			$addressId = getRequest('delivery-address');

			try {
				$module->saveAddress($order, $addressId);
			} catch (publicException $exception) {
				if ($addressRequired) {
					$module->errorNewMessage($exception->getMessage());
					$module->errorPanic();
				}
			}
		}

		/**
		 * Сохраняет выбранный адрес доставки в заказ или создает новый
		 * @param order $order заказ
		 * @param int|string $addressId идентификатор заказа или ключевое слово "new", если нужно создать адрес
		 * @return Address\iAddress выбранный адрес
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 * @throws publicException
		 */
		public function saveAddress(order $order, $addressId) {
			$umiObjects = umiObjectsCollection::getInstance();
			$addressTypeId = umiObjectTypesCollection::getInstance()
				->getTypeIdByHierarchyTypeName('emarket', 'delivery_address');

			if ($addressId == 'new') {
				$customer = customer::get();
				$addressId = $umiObjects->addObject('Address for customer #' . $customer->getId(), $addressTypeId);
				$customerAddressList = (array) $customer->getValue('delivery_addresses');
				$customerAddressList[] = $addressId;
				$customer->setValue('delivery_addresses', array_unique($customerAddressList));
				$customer->commit();

				/** @var DataForms $data */
				$data = cmsController::getInstance()->getModule('data');
				$data->saveEditedObjectWithIgnorePermissions($addressId, true, true);
			}

			$address = null;

			try {
				$address = Address\AddressFactory::createByObjectId($addressId);
			} catch (\expectObjectException $e) {
				// nothing
			}

			if (!$address instanceof Address\iAddress) {
				throw new publicException(getLabel('error-emarket-choose-address'));
			}

			$order->setValue('delivery_address', $addressId);
			$order->commit();
			return $address;
		}

		/**
		 * Пытается сохранить выбранный способ доставки, если это необходимо.
		 * В случае ошибки прерывает запрос.
		 * @param order $order оформляемый заказ
		 * @param bool $deliveryRequired является ли выбор способа доставки обязательным
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 */
		public function processOneStepDelivery($order, $deliveryRequired) {
			$module = $this->module;

			if (!$module->isDeliveryAvailable()) {
				return;
			}

			$deliveryId = getRequest('delivery-id');

			try {
				$module->saveDelivery($order, $deliveryId);
			} catch (publicException $exception) {
				if ($deliveryRequired) {
					$module->errorNewMessage($exception->getMessage());
					$module->errorPanic();
				}
			}
		}

		/**
		 * Сохраняет выбранный способ доставки в заказ
		 * @param order $order заказ
		 * @param int $deliveryId идентификатор способа доставки
		 * @return delivery выбранный способ доставки
		 * @throws publicException если передан некорректный идентификатор
		 */
		public function saveDelivery(order $order, $deliveryId) {
			$delivery = null;

			try {
				$delivery = delivery::get($deliveryId);
			} catch (coreException $e) {
				// nothing
			}

			if (!$delivery instanceof delivery) {
				throw new publicException(getLabel('error-emarket-choose-delivery'));
			}

			$order->setDelivery($delivery);
			$order->commit();

			return $delivery;
		}

		/**
		 * Пытается сохранить выбранный способ оплаты, если это необходимо.
		 * В случае ошибки прерывает запрос.
		 * @param order $order оформляемый заказ
		 * @param bool $paymentRequired является ли выбор способа оплаты обязательным
		 * @return null|payment способ оплаты
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 */
		public function processOneStepPayment($order, $paymentRequired) {
			$module = $this->module;

			if (!$module->isPaymentAvailable()) {
				return null;
			}

			$paymentId = getRequest('payment-id');

			try {
				return $module->savePayment($order, $paymentId);
			} catch (publicException $exception) {
				if ($paymentRequired) {
					$module->errorNewMessage($exception->getMessage());
					$module->errorPanic();
				}
			}
		}

		/**
		 * Сохраняет выбранный способ оплаты в заказ
		 * @param order $order заказ
		 * @param int $paymentId идентификатор способа оплаты
		 * @return payment выбранный способ оплаты
		 * @throws publicException если передан некорректный идентификатор
		 */
		public function savePayment(order $order, $paymentId) {
			$payment = null;

			try {
				$payment = payment::get($paymentId, $order);
			} catch (coreException $e) {
				// nothing
			}

			if (!$payment instanceof payment) {
				throw new publicException(getLabel('error-emarket-choose-payment'));
			}

			$order->setPayment($payment);
			$order->commit();

			return $payment;
		}

		/**
		 * Перенаправляет на страницу способа оплаты, если он существует.
		 * Иначе, оформляет заказ и перенаправляет на страницу успешного оформления заказа.
		 * @param order $order оформляемый заказ
		 * @param null|payment $payment выбранный способ оплаты
		 * @throws coreException
		 */
		public function processOneStepOrder($order, $payment) {
			if ($payment instanceof payment) {
				$method = "/purchase/payment/{$payment->getCodeName()}/";
			} else {
				$method = '/purchase/result/successful/';
				$order->refresh();
				$order->order();
			}

			$this->module->oneStepRedirect($method);
		}

		/**
		 * Перенаправляет на системную страницу модуля "Интернет-магазин".
		 * @param string $method название метода системной страницы
		 * @throws coreException
		 */
		public function oneStepRedirect($method) {
			$cmsController = cmsController::getInstance();
			$module = $this->module;
			$prefix = "{$module->pre_lang}/{$cmsController->getUrlPrefix()}";
			$url = $prefix . get_class($module) . $method;
			$module->redirect($url);
		}
	}
