<?php
	/**
	 * Class kupivkreditPayment
	 * Система оплаты КупиВКредит
	 */
	class kupivkreditPayment extends payment {
		/** @var kvkAPI $api */
		private $api;

		/**
		 * Создание списка товаров для Widget'а
		 *
		 * @return array Список товаров
		 */
		protected function createWidgetOrder() {
			$items = array();
			foreach ($this->order->getItems() as $item) {
				/** @var orderItem $item */
				$categoryId = $item->getItemElement()->getParentId();
				$category = umiHierarchy::getInstance()->getElement($categoryId);
				/** @var orderItem $item */
				$items[] = array(
					"title" => $item->getName(),
					"category" => $category ? $category->getName() : '',
					"qty" => $item->getAmount(),
					"price" => $item->getItemPrice()
				);
			}

			$delivery = umiObjectsCollection::getInstance()->getObject($this->order->getValue("delivery_id"));

			if ($delivery) {
				$blockArray["deliveryType"] = $delivery->getName();
				$deliveryPrice = $this->order->getDeliveryPrice();
				if($deliveryPrice) {
					$items[] = array (
						"title" => $delivery->getName(),
						"category" => '',
						"qty" => 1,
						"price" => $deliveryPrice
					);
				}
			} else {
				$blockArray["deliveryType"] = "";
			}

			$customer = umiObjectsCollection::getInstance()->getObject($this->order->getCustomerId());

			$details = array(
				'firstname' => $customer->getValue("fname"),
				'lastname' => $customer->getValue("lname"),
				'middlename' => $customer->getValue("father_name"),
				'email' => $customer->getValue("e-mail"),
				'cellphone' => $customer->getValue("phone"),
			);

			if(!$details['email']) {
				$details['email'] = $customer->getValue("email");
			}

			$blockArray = array(
				"items" => $items,
				"details" => $details,
				"partnerId" => $this->getValue("partnerId"),
				"partnerOrderId" => $this->order->getValue('payment_document_num')
			);


			return $blockArray;
		}

		/**
		 * Преобразует данные, основываясь на их типе
		 *
		 * @param string $data Данные
		 * @param string $type Тип данных
		 *
		 * @return int|string
		 */
		protected function stringToType($data, $type) {
			switch($type) {
				case "date":
					return strtotime($data);

				case "bool":
					return (bool) $data;

				case "int":
					return (int) $data;

				case "currency":
					return $data ? $data . " " . getLabel("label-rubles") : null;

				case "date-str":
					$date = new umiDate(strtotime($data));
					return $date->getFormattedDate();

				default:
					return $data;
			}
		}

		/**
		 * Поиск объекта с GUID emarket-$field по полю codename
		 * @internal
		 *
		 * @param $field
		 * @param string $name
		 *
		 * @return bool
		 */
		protected function findFieldByCodename($field, $name) {
			if(!$name) {
				return null;
			}

			$sel = new selector('objects');
			$sel->types('object-type')->guid("emarket-$field");
			$sel->where('codename')->equals($name);

			return $sel->first ? $sel->first->id : null;
		}

		/** @inheritdoc */
		public function __construct(umiObject $object) {
			$args = func_get_args();
			$payment = array_shift($args);

			if (!$payment instanceof umiObject) {
				throw new Exception('Payment expected for creating payment');
			}

			$order = array_shift($args);

			if (!$order instanceof order && $order !== null) {
				throw new Exception('Incorrect order given for creating payment');
			}

			parent::__construct($payment, $order);
			objectProxyHelper::includeClass('emarket/classes/payment/api/', 'kupivkredit');
			$this->api = new kvkAPI($this->getValue("apiKey"), $this->getValue("partnerId"), $this->getValue("secretKey"), $this->object->demo_mode);
		}

		private function prepareActions($actions) {
			$items = array();
			foreach($actions as $action) {
				$items[] = array(
					'@name' => $action,
					'@title' => getLabel('label-kvk-' . $action)
				);
			}

			return array('+item' => $items);
		}

		/**
		 * Вывод для блок оплаты в заказе
		 * @return array
		 */
		public function admin() {
			$status = $this->order->getValue("credit-status");

			if($status) {
				$status = umiObjectsCollection::getInstance()->getObject($status);
				$status = $status->getValue('codename');
			}

			if(!$status) {
				return array(
					'@is-error' => 1,
					"extended-status" => getLabel("note-kvk-step-error")
				);
			}

			if($this->order->getValue("BeingProcessed")) {
				return array(
					"extended-status" => getLabel("note-kvk-being-processed")
				);
			}

			switch($status) {
				case "rej": case "can": case "ovr": {
					return array(
						'actions' => $this->prepareActions(array('takeover')),
						"extended-status" => getLabel("note-kvk-step-over")
					);
				}

				case "fap": {
					return array(
						'actions' => $this->prepareActions(array('takeover')),
						"extended-status" => getLabel("note-kvk-step-successful")
					);
				}

				case "pvr": case "app": case "prr": {
					$actions = array('takeover');
					if($status == 'app') {
						$actions[] = 'goods_form';
					}

					return array(
						'actions' => $this->prepareActions($actions),
						"extended-status" => getLabel("note-kvk-step-processing")
					);
				}

				case "agr": {
					if ($this->order->getValue("IsConfirmed")) {
						return array(
							'actions' => $this->prepareActions(array("cancel", "contract", "complete", 'takeover')),
							"extended-status" => getLabel("note-kvk-step-confirmed")
						);
					} else {
						return array(
							'actions' => $this->prepareActions(array("cancel", "confirm", 'takeover')),
							"extended-status" => getLabel("note-kvk-step-initialized")
						);
					}
				}

				default: {
					return array(
						'actions' => $this->prepareActions(array("cancel", 'takeover')),
						"extended-status" => getLabel("note-kvk-step-hold")
					);
				}
			}
		}

		/**
		 * Загрузить дополнительные поля
		 * @return array
		 */
		public function admin_moreInfo() {
			$response = $this->api->call("get_decision", array(
				"PartnerOrderId" => $this->order->getValue('payment_document_num')
			));

			if(!$response->isSuccess()) {
				return $response->toBlockArray();
			}

			$result = $response->getResult();
			$blockArray = $response->toBlockArray();

			$additionalFields = array(
				"LoanAmount" => 'currency',
				"MaxPossibleLoanAmount" => 'currency',
				"MonthlyPayment" => 'currency',
				"Downpayment" => 'currency',
				"Commission"  => 'currency',
				"PaymentCount" => 'int',
				"DecisionDate" => 'date-str'
			);

			$fieldsArray = array();
			foreach($additionalFields as $field => $type) {
				$data = $this->stringToType($result[$field], $type);
				if($data) {
					$fieldsArray[] = array(
						"@title" => getLabel("field-kvk-" . $field),
						"@name" => $field,
						"node:text" => $this->stringToType($result[$field], $type)
					);
				}
			}
			$blockArray["result"] = array("+field" => $fieldsArray);

			return $blockArray;
		}

		/**
		 * Скачать заявление о возврате товара
		 *
		 * Печать заявления о возврате товара возможна только для уже подписанных заявок.
		 *
		 * @return array
		 */
		public function admin_goodsForm() {
			$amount = (float) getRequest("amount");
			$cashReturned = (float) getRequest("cashReturned");

			$response = $this->api->call("get_return_goods_form", array(
				"PartnerOrderId" => $this->order->getValue('payment_document_num'),
				"ReturnedAmount" => $amount,
				"CashReturnedToCustomer" => $cashReturned
			));

			if(!$response->isSuccess()) {
				return $response->toBlockArray();
			}

			\UmiCms\Service::Response()
				->getCurrentBuffer()->clear();

			header('Content-type: application/pdf');
			header('Content-Disposition: attachment; filename="goods_return_form.pdf"');

			echo base64_decode($response->getResult());

			exit();
		}

		/**
		 * Скачать акт приёма-передачи документов
		 * @return array
		 */
		public function admin_takeoverDocuments() {
			$response = $this->api->call("get_takeover_documents", array(
				"PartnerOrderIds" => '<id>' . $this->order->getValue('payment_document_num') . '</id>',
			));

			if(!$response->isSuccess()) {
				return $response->toBlockArray();
			}

			\UmiCms\Service::Response()
				->getCurrentBuffer()->clear();

			header('Content-type: application/pdf');
			header('Content-Disposition: attachment; filename="takeover_documents.pdf"');

			echo base64_decode($response->getResult());

			exit();
		}

		private function responseCheckItems($response) {
			if(!$response) {
				return false;
			}

			$responsePrice = 0;
			if(!isset($response['OrderItems']['OrderItem'][0])){
				$response['OrderItems']['OrderItem'] = array($response['OrderItems']['OrderItem']);
			}

			foreach($response['OrderItems']['OrderItem'] as $orderItem) {
				$orderItem = (array) $orderItem;
				$responsePrice += $orderItem['ItemPrice'];
			}

			return $this->order->getActualPrice() == $responsePrice;
		}

		/**
		 * Преобразование кредитного статуса в статус оплаты
		 *
		 * @param string $creditStatus Кредитный статус
		 *
		 * @return null|string Статус оплаты
		 */
		private function toPaymentStatus($creditStatus) {
			switch($creditStatus) {
				case "rej": case "can": case "ovr": {
					return 'declined';
				}

				case "pvr": case "app": case "prr": case "agr": {
					return 'validated';
				}

				case "fap": {
					return 'accepted';
				}

				default: {
					return null;
				}
			}
		}

		/**
		 * Обновить значения полей заказа
		 *
		 * @param kvkAPIResponse $response Ответ банка
		 */
		protected function saveFieldsData($response) {
			$result = $response->getResult();

			if(isset($result['PartnerOrderId']) && $result['PartnerOrderId'] != $this->order->getValue('payment_document_num')) {
				return false;
			}

			if (!$this->responseCheckItems($result)) {
				// Суммы заказов не совпали
				$this->order->setValue("payment_status_id", null);
				$this->order->setValue("credit-status", null);
				$result = array();
			}

			if(is_array($result) && $result) {
				if($result["OrderStatus"] != 'agr' || $result["IsConfirmed"]) {
					$this->order->setPaymentStatus($this->toPaymentStatus($result["OrderStatus"]));
				}

				$statusId = $this->findFieldByCodename("order-credit-status", $result["OrderStatus"]);
				$this->order->setValue("credit-status", $statusId);
			} else {
				$result = array();
			}

			$fields = array(
				"IsConfirmed" => "bool",
				"SigningType" => "relation",
				"BankSigningAppointmentTime" => "date",
				"ContractSigningDeadline" => "date",
				"ContractDeliveryDeadline" => "date",
				"BeingProcessed" => "bool",
			);

			foreach($fields as $field => $type) {
				$data = isset($result[$field]) ? $result[$field] : null;
				$data = $this->stringToType($data, $type);

				if($field == "SigningType") {
					$data = $this->findFieldByCodename('payment-signing-types', $data);
				}

				$this->order->setValue($field, $data);
			}

			$this->order->commit();

			return true;
		}

		/**
		 * Обновить поля в заказе
		 * @return array
		 */
		public function admin_refresh() {
			$response = $this->api->call("get_decision", array(
				"PartnerOrderId" => $this->order->getValue('payment_document_num')
			));

			$this->saveFieldsData($response);

			if(!$response->isSuccess()) {
				$this->order->setValue("payment_status_id", null);
				$this->order->commit();
				return $response->toBlockArray();
			}

			return $response->toBlockArray();
		}

		/**
		 * Подтвердить заказ
		 * @return bool|string
		 */
		public function admin_confirm() {
			$signingType = (string) getRequest("type");

			$response = $this->api->call("confirm_order", array(
				"PartnerOrderId" => $this->order->getValue('payment_document_num'),
				"SigningType" => $signingType
			));

			if($response->isSuccess()) {
				$this->order->setValue("BeingProcessed", true);
			}

			return $response->toBlockArray();
		}

		/** Скачать оферту */
		public function admin_getContract() {
			$response = $this->api->call("get_contract", array("PartnerOrderId" => $this->order->getValue('payment_document_num')));

			if(!$response->isSuccess()) {
				return $response->toBlockArray();
			}

			\UmiCms\Service::Response()
				->getCurrentBuffer()->clear();

			header('Content-type: application/pdf');
			header('Content-Disposition: attachment; filename="contract.pdf"');

			echo base64_decode($response->getResult());

			exit();
		}

		/** Отменить оплату заказа */
		public function admin_cancel() {
			$response = $this->api->call("cancel_order", array(
				"PartnerOrderId" => $this->order->getValue('payment_document_num'),
				"Reason" => getRequest("reason")
			));

			if($response->isSuccess()) {
				$this->order->setValue("BeingProcessed", true);
			}
			return $response->toBlockArray();
		}

		/**
		 * Передать данные об успешном подписании документов
		 *
		 * Этот вызов можно использовать только когда магазину доступно подписание оферт своими силами (определяется договорённостями с Банком).
		 * При подписании оферты банком этот вызов использовать не надо.
		 *
		 * @return array
		 */
		public function admin_complete() {
			$response = $this->api->call("order_completed", array(
				"PartnerOrderId" => $this->order->getValue('payment_document_num')
			));

			if($response->isSuccess()) {
				$this->order->setValue("BeingProcessed", true);
			}
			return $response->toBlockArray();
		}

		/**
		 * Проверяет, подходит ли данный тип оплаты
		 *
		 * @return bool
		 */
		public function validate() {
            if(func_num_args() > 0){
                $args = func_get_args();
                $order = array_shift($args);

                if($order instanceof order){
                    return $order->getActualPrice() >= 3000;
                }
            }

			return true;
		}

		/**
		 * Получает Id заказа из запроса
		 *
		 * @return bool|int
		 */
		public static function getOrderId() {
			objectProxyHelper::includeClass('emarket/classes/payment/api/', 'kupivkredit');

			$request = kvkAPIRequest::decode(file_get_contents("php://input"));

			if(!$request) {
				return false;
			}

			$result = $request->getResult();

			if(isset($result['PartnerOrderId'])) {
				return intval($result['PartnerOrderId']);
			} else {
				return false;
			}

		}

		/** Callback для получения статусов */
		public function poll() {
			$request = file_get_contents("php://input");
			$response = kvkAPIRequest::decode($request, $this->getValue("secretKey"));

			if($response->isSuccess()) {
				$this->saveFieldsData($response);
			}

			exit();
		}

		/**
		 * Шаг оплаты с помощью сервиса КупиВКредит
		 *
		 * @param $template Шаблон
		 *
		 * @return mixed
		 */
		public function process($template = null) {
			list($tplBlock, $tplError, $tplNotExist) = def_module::loadTemplates("emarket/payment/kupivkredit/" . $template, "form_block", 'block_canceled', 'block_error', 'block_not_found');

			if(!getRequest('accepted')) {
				// Открыть widget
				$this->order->order();
				$this->order->setValue('payment_document_num', $this->order->getId() . '.' . time());

				$orderData = $this->createWidgetOrder($this->order);
				$blockArray = kvkAPIRequest::encodeWidget($orderData, $this->getValue("secretKey"));
				$blockArray["totalPrice"] = $this->order->getActualPrice();
				$blockArray["@test-mode"] = $this->object->demo_mode;
				$this->order->setPaymentStatus("initialized");

				return def_module::parseTemplate($tplBlock, $blockArray);
			} else {
				// узнаем решение банка
				$response = $this->api->call("get_decision", array(
					"PartnerOrderId" => $this->order->getValue('payment_document_num')
				));

				if (!$response->isSuccess()) {
					// заявка не существует
					return def_module::parseTemplate($tplNotExist, array(
						'@action' => 'error'
					));
				} else {
					$result = $response->getResult();

					if(!$this->responseCheckItems($result)) {
						// суммы не совпали, не тот заказ
						return def_module::parseTemplate($tplError, array(
							'@action' => 'error'
						));
					} else {
						/* Обновляем существующий новый заказ */
						$this->saveFieldsData($response);

						$result = $response->getResult();
						$step = in_array($result["OrderStatus"], array('rej', 'can', 'ovr')) ? 'failed' : 'successful';

						$controller = cmsController::getInstance();
						/** @var emarket $module */
						$module = $controller->getModule("emarket");
						if($module) {
							$module->redirect("{$controller->getPreLang()}/emarket/purchase/result/{$step}/");
						}

						return array();
					}
				}
			}
		}
	}
?>