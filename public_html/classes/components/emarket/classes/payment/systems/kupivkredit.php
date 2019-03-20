<?php

	use UmiCms\Service;

	/**
	 * Способ оплаты через платежную систему "КупиВКредит".
	 * Подходит для заказов со стоимость большее 3000 рублей.
	 */
	class kupivkreditPayment extends payment {

		/** @const int MINIMUM_ORDER_PRICE минимальная стоимость заказа*/
		const MINIMUM_ORDER_PRICE = 3000;

		/** @var string адрес js-виджета в демо-режиме */
		private static $testWidgetUrl = 'https://kupivkredit-test-fe.tcsbank.ru/widget/vkredit.js';

		/** @var string адрес js-виджета в production-режиме */
		private static $productionWidgetUrl = 'https://www.kupivkredit.ru/widget/vkredit.js';

		/** @var kvkAPI $api */
		private $api;

		/**
		 * @inheritdoc
		 * Подключает и инициализирует kvkAPI
		 */
		public function __construct(iUmiObject $object) {
			$args = func_get_args();
			$payment = array_shift($args);

			if (!$payment instanceof iUmiObject) {
				throw new Exception('Payment expected for creating payment');
			}

			$order = array_shift($args);

			if (!$order instanceof order && $order !== null) {
				throw new Exception('Incorrect order given for creating payment');
			}

			parent::__construct($payment, $order);
			objectProxyHelper::includeClass('emarket/classes/payment/api/', 'kupivkredit');
			$this->api = new kvkAPI(
				$this->getValue('apiKey'),
				$this->getValue('partnerId'),
				$this->getValue('secretKey'),
				$this->object->demo_mode
			);
		}

		/**
		 * @inheritdoc
		 * Обновляет значение полей заказа.
		 * @throws coreException
		 */
		public static function getOrderId() {
			objectProxyHelper::includeClass('emarket/classes/payment/api/', 'kupivkredit');

			$request = kvkAPIRequest::decode(Service::Request()->getRawBody());

			if (!$request) {
				return false;
			}

			$result = $request->getResult();

			if (isset($result['PartnerOrderId'])) {
				return (int) $result['PartnerOrderId'];
			}

			return false;
		}

		/** @inheritdoc */
		public function validate() {
			if (func_num_args() < 0) {
				return true;
			}

			$order = func_get_arg(0);

			if (!parent::validate($order)) {
				return false;
			}

			return $order->getActualPrice() >= self::MINIMUM_ORDER_PRICE;
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function poll() {
			$request = Service::Request()->getRawBody();
			$response = kvkAPIRequest::decode($request, $this->getValue('secretKey'));

			if ($response->isSuccess()) {
				$this->saveFieldsData($response);
			}

			Service::Response()
				->getCurrentBuffer()
				->end();
		}

		/**
		 * @inheritdoc
		 * Устанавливает заказу статус оплаты "Инициализирована" и номер платежного документа.
		 * Уточняет у сервиса возможность оплаты в кредит и сохраняет данные заказа.
		 * @throws coreException
		 */
		public function process($template = null) {
			list($tplBlock, $tplError, $tplNotExist) = emarket::loadTemplates(
				'emarket/payment/kupivkredit/' . $template,
				'form_block',
				'block_canceled',
				'block_error',
				'block_not_found'
			);

			if (!getRequest('accepted')) {
				$this->order->order();
				$this->order->setPaymentDocumentNumber($this->order->getId() . '.' . time());
				$this->order->commit();

				$orderData = $this->createWidgetOrder();
				$blockArray = kvkAPIRequest::encodeWidget($orderData, $this->getValue('secretKey'));
				$blockArray['totalPrice'] = $this->order->getActualPrice();
				$blockArray['@test-mode'] = $this->object->demo_mode;
				$this->order->setPaymentStatus('initialized');

				return emarket::parseTemplate($tplBlock, $blockArray);
			}

			$response = $this->api->call('get_decision', [
				'PartnerOrderId' => $this->order->getPaymentDocumentNumber()
			]);

			if (!$response->isSuccess()) {
				return emarket::parseTemplate($tplNotExist, [
					'@action' => 'error'
				]);
			}

			$result = $response->getResult();

			if (!$this->responseCheckItems($result)) {
				return emarket::parseTemplate($tplError, [
					'@action' => 'error'
				]);
			}

			$this->saveFieldsData($response);

			$result = $response->getResult();
			$step = in_array($result['OrderStatus'], ['rej', 'can', 'ovr']) ? 'failed' : 'successful';

			$controller = cmsController::getInstance();
			/** @var emarket $module */
			$module = $controller->getModule('emarket');

			if ($module) {
				$module->redirect("{$controller->getPreLang()}/emarket/purchase/result/{$step}/");
			}

			return [];
		}

		/**
		 * Возвращает данные для оплаты в заказе
		 * @return array
		 */
		public function admin() {
			$status = $this->order->getValue('credit-status');

			if ($status) {
				$status = umiObjectsCollection::getInstance()->getObject($status);
				$status = $status->getValue('codename');
			}

			if (!$status) {
				return [
					'@is-error' => 1,
					'extended-status' => getLabel('note-kvk-step-error')
				];
			}

			if ($this->order->getValue('BeingProcessed')) {
				return [
					'extended-status' => getLabel('note-kvk-being-processed')
				];
			}
			switch ($status) {
				case 'rej':
				case 'can':
				case 'ovr': {
					return [
						'actions' => $this->prepareActions(['takeover']),
						'extended-status' => getLabel('note-kvk-step-over')
					];
				}
				case 'fap': {
					return [
						'actions' => $this->prepareActions(['takeover']),
						'extended-status' => getLabel('note-kvk-step-successful')
					];
				}
				case 'pvr':
				case 'app':
				case 'prr': {
					$actions = ['takeover'];

					if ($status == 'app') {
						$actions[] = 'goods_form';
					}

					return [
						'actions' => $this->prepareActions($actions),
						'extended-status' => getLabel('note-kvk-step-processing')
					];
				}
				case 'agr': {
					if ($this->order->getValue('IsConfirmed')) {
						return [
							'actions' => $this->prepareActions(['cancel', 'contract', 'complete', 'takeover']),
							'extended-status' => getLabel('note-kvk-step-confirmed')
						];
					}

					return [
						'actions' => $this->prepareActions(['cancel', 'confirm', 'takeover']),
						'extended-status' => getLabel('note-kvk-step-initialized')
					];
				}
				default: {
					return [
						'actions' => $this->prepareActions(['cancel', 'takeover']),
						'extended-status' => getLabel('note-kvk-step-hold')
					];
				}
			}
		}

		/**
		 * Возвращает дополнительную информацию о кредите
		 * @return array
		 */
		public function admin_moreInfo() {
			$response = $this->api->call('get_decision', [
				'PartnerOrderId' => $this->order->getPaymentDocumentNumber()
			]);

			if (!$response->isSuccess()) {
				return $response->toBlockArray();
			}

			$result = $response->getResult();
			$blockArray = $response->toBlockArray();

			$additionalFields = [
				'LoanAmount' => 'currency',
				'MaxPossibleLoanAmount' => 'currency',
				'MonthlyPayment' => 'currency',
				'Downpayment' => 'currency',
				'Commission' => 'currency',
				'PaymentCount' => 'int',
				'DecisionDate' => 'date-str'
			];

			$fieldsArray = [];
			foreach ($additionalFields as $field => $type) {
				$data = $this->stringToType($result[$field], $type);
				if ($data) {
					$fieldsArray[] = [
						'@title' => getLabel('field-kvk-' . $field),
						'@name' => $field,
						'node:text' => $this->stringToType($result[$field], $type)
					];
				}
			}

			$blockArray['result'] = ['+field' => $fieldsArray];
			return $blockArray;
		}

		/**
		 * Инициирует скачивание заявления о возврате товара
		 * Печать заявления о возврате товара возможна только для уже подписанных заявок.
		 * @return array
		 */
		public function admin_goodsForm() {
			$amount = (float) getRequest('amount');
			$cashReturned = (float) getRequest('cashReturned');

			$response = $this->api->call('get_return_goods_form', [
				'PartnerOrderId' => $this->order->getPaymentDocumentNumber(),
				'ReturnedAmount' => $amount,
				'CashReturnedToCustomer' => $cashReturned
			]);

			if (!$response->isSuccess()) {
				return $response->toBlockArray();
			}

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->contentType('application/pdf');
			$buffer->setHeader('Content-Disposition', 'attachment; filename="goods_return_form.pdf"');
			$buffer->push(base64_decode($response->getResult()));
			$buffer->end();
		}

		/**
		 * Инициирует скачивание акта приёма-передачи документов
		 * @return array
		 */
		public function admin_takeoverDocuments() {
			$response = $this->api->call('get_takeover_documents', [
				'PartnerOrderIds' => '<id>' . $this->order->getPaymentDocumentNumber() . '</id>',
			]);

			if (!$response->isSuccess()) {
				return $response->toBlockArray();
			}

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->contentType('application/pdf');
			$buffer->setHeader('Content-Disposition', 'attachment; filename="takeover_documents.pdf"');
			$buffer->push(base64_decode($response->getResult()));
			$buffer->end();
		}

		/**
		 * Запрашивает информацию о заявке на кредит и обновляет поля заказа,
		 * возвращает ответ от сервиса.
		 * @return array
		 * @throws coreException
		 */
		public function admin_refresh() {
			$response = $this->api->call('get_decision', [
				'PartnerOrderId' => $this->order->getPaymentDocumentNumber()
			]);

			$this->saveFieldsData($response);

			if (!$response->isSuccess()) {
				$this->order->payment_status_id = null;
				$this->order->commit();
				return $response->toBlockArray();
			}

			return $response->toBlockArray();
		}

		/**
		 * Инициирует подтверждение заказа
		 * @return array
		 */
		public function admin_confirm() {
			$signingType = (string) getRequest('type');

			$response = $this->api->call('confirm_order', [
				'PartnerOrderId' => $this->order->getPaymentDocumentNumber(),
				'SigningType' => $signingType
			]);

			if ($response->isSuccess()) {
				$this->order->BeingProcessed = true;
				$this->order->commit();
			}

			return $response->toBlockArray();
		}

		/**
		 * Инициирует скачивае оферты
		 * @return array
		 */
		public function admin_getContract() {
			$response = $this->api->call('get_contract', [
				'PartnerOrderId' => $this->order->getPaymentDocumentNumber()
			]);

			if (!$response->isSuccess()) {
				return $response->toBlockArray();
			}

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->contentType('application/pdf');
			$buffer->setHeader('Content-Disposition', 'attachment; filename="contract.pdf"');
			$buffer->push(base64_decode($response->getResult()));
			$buffer->end();
		}

		/** Инициирует отмену оплаты заказа */
		public function admin_cancel() {
			$response = $this->api->call('cancel_order', [
				'PartnerOrderId' => $this->order->getPaymentDocumentNumber(),
				'Reason' => getRequest('reason')
			]);

			if ($response->isSuccess()) {
				$this->order->BeingProcessed = true;
				$this->order->commit();
			}

			return $response->toBlockArray();
		}

		/**
		 * Передает данные об успешном подписании документов.
		 * Этот вызов можно использовать только когда магазину доступно подписание оферт своими силами
		 * (определяется договорённостями с Банком). При подписании оферты банком этот вызов использовать не надо.
		 * @return array
		 */
		public function admin_complete() {
			$response = $this->api->call('order_completed', [
				'PartnerOrderId' => $this->order->getPaymentDocumentNumber()
			]);

			if ($response->isSuccess()) {
				$this->order->BeingProcessed = true;
				$this->order->commit();
			}

			return $response->toBlockArray();
		}

		/**
		 * Создает и возвращает Список товаров для Widget'а
		 * @return array
		 * @throws coreException
		 */
		protected function createWidgetOrder() {
			$items = [];
			foreach ($this->order->getItems() as $item) {
				/** @var orderItem $item */
				$categoryId = $item->getItemElement()->getParentId();
				$category = umiHierarchy::getInstance()->getElement($categoryId);
				/** @var orderItem $item */
				$items[] = [
					'title' => $item->getName(),
					'category' => $category ? $category->getName() : '',
					'qty' => $item->getAmount(),
					'price' => $item->getOriginalPrice()
				];
			}

			$umiObjectsCollection = umiObjectsCollection::getInstance();
			$delivery = $umiObjectsCollection->getObject($this->order->getValue('delivery_id'));

			if ($delivery) {
				$blockArray['deliveryType'] = $delivery->getName();
				$deliveryPrice = $this->order->getDeliveryPrice();
				if ($deliveryPrice) {
					$items[] = [
						'title' => $delivery->getName(),
						'category' => '',
						'qty' => 1,
						'price' => $deliveryPrice
					];
				}
			} else {
				$blockArray['deliveryType'] = '';
			}

			$customer = $umiObjectsCollection->getObject($this->order->getCustomerId());

			$details = [
				'firstname' => $customer->getValue('fname'),
				'lastname' => $customer->getValue('lname'),
				'middlename' => $customer->getValue('father_name'),
				'email' => $customer->getValue('e-mail'),
				'cellphone' => $customer->getValue('phone'),
			];

			if (!$details['email']) {
				$details['email'] = $customer->getValue('email');
			}

			$blockArray = [
				'items' => $items,
				'details' => $details,
				'partnerId' => $this->getValue('partnerId'),
				'partnerOrderId' => $this->order->getPaymentDocumentNumber()
			];

			return $blockArray;
		}

		/**
		 * Преобразует данные, основываясь на их типе
		 * @param string $data Данные
		 * @param string $type Тип данных
		 * @return int|string
		 */
		protected function stringToType($data, $type) {
			switch ($type) {
				case 'date': {
					return strtotime($data);
				}
				case 'bool': {
					return (bool) $data;
				}
				case 'int': {
					return (int) $data;
				}
				case 'currency': {
					return $data ? $data . ' ' . getLabel('label-rubles') : null;
				}
				case 'date-str': {
					$date = new umiDate(strtotime($data));
					return $date->getFormattedDate();
				}
				default: {
					return $data;
				}
			}
		}

		/**
		 * Возвращает идентификатор объекта с GUID emarket-$field со значением поля codename
		 * @param string $field префикс гуида типа данных
		 * @param string $name значение поля codename
		 * @return int|bool
		 * @throws selectorException
		 */
		protected function findFieldByCodename($field, $name) {
			if (!$name) {
				return null;
			}

			$sel = new selector('objects');
			$sel->types('object-type')->guid("emarket-$field");
			$sel->where('codename')->equals($name);

			return $sel->first() ? $sel->first()->id : null;
		}

		/**
		 * Подготавливает список операций к шаблонизации
		 * @param array $actions список операций
		 * @return array
		 */
		protected function prepareActions($actions) {
			$items = [];

			foreach ($actions as $action) {
				$items[] = [
					'@name' => $action,
					'@title' => getLabel('label-kvk-' . $action)
				];
			}

			return ['+item' => $items];
		}

		/**
		 * Проверяет равна ли стоимость заказа в UMI.CMS
		 * суммарной стоимость товаров в сервисе
		 * @param array $response ответ о сервиса, содержащий товары
		 * @return bool
		 */
		protected function responseCheckItems($response) {
			if (!$response) {
				return false;
			}

			$responsePrice = 0;

			if (!isset($response['OrderItems']['OrderItem'][0])) {
				$response['OrderItems']['OrderItem'] = [$response['OrderItems']['OrderItem']];
			}

			foreach ($response['OrderItems']['OrderItem'] as $orderItem) {
				$orderItem = (array) $orderItem;
				$responsePrice += $orderItem['ItemPrice'];
			}

			return $this->order->getActualPrice() == $responsePrice;
		}

		/**
		 * Возвращает статус оплаты в UMI.CMS,
		 * соответствующий статусу кредита
		 * @param string $creditStatus статус кредита
		 * @return null|string
		 */
		protected function toPaymentStatus($creditStatus) {
			switch ($creditStatus) {
				case 'rej':
				case 'can':
				case 'ovr': {
					return 'declined';
				}
				case 'pvr':
				case 'app':
				case 'prr':
				case 'agr': {
					return 'validated';
				}
				case 'fap': {
					return 'accepted';
				}
				default: {
					return null;
				}
			}
		}

		/**
		 * Обновляет поля заказа значениями ответа банка
		 * @param kvkAPIResponse $response ответ банка
		 * @return bool
		 * @throws coreException
		 */
		protected function saveFieldsData($response) {
			$result = $response->getResult();

			if (isset($result['PartnerOrderId']) && $result['PartnerOrderId'] != $this->order->getPaymentDocumentNumber()) {
				return false;
			}

			if (!$this->responseCheckItems($result)) {
				$this->order->payment_status_id = null;
				$this->order->object->setValue('credit-status', null);
				$result = [];
			}

			if (is_array($result) && $result) {
				if ($result['OrderStatus'] != 'agr' || $result['IsConfirmed']) {
					$this->order->setPaymentStatus($this->toPaymentStatus($result['OrderStatus']));
				}

				$statusId = $this->findFieldByCodename('order-credit-status', $result['OrderStatus']);
				$this->order->object->setValue('credit-status', $statusId);
			} else {
				$result = [];
			}

			$fields = [
				'IsConfirmed' => 'bool',
				'SigningType' => 'relation',
				'BankSigningAppointmentTime' => 'date',
				'ContractSigningDeadline' => 'date',
				'ContractDeliveryDeadline' => 'date',
				'BeingProcessed' => 'bool',
			];

			foreach ($fields as $field => $type) {
				$data = isset($result[$field]) ? $result[$field] : null;
				$data = $this->stringToType($data, $type);

				if ($field == 'SigningType') {
					$data = $this->findFieldByCodename('payment-signing-types', $data);
				}

				$this->order->object->setValue($field, $data);
			}

			$this->order->object->commit();
			$this->order->commit();
			return true;
		}

		/**
		 * Возвращает адрес js-виджета в демо-режиме
		 * @return string
		 */
		public static function getTestWidgetUrl() {
			return self::$testWidgetUrl;
		}

		/**
		 * Возвращает адрес js-виджета в production-режиме
		 * @return string
		 */
		public static function getProductionWidgetUrl() {
			return self::$productionWidgetUrl;
		}
	}
