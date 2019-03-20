<?php

use UmiCms\Service;

/**
	 * API для мобильного приложения UMI.Manager.
	 *
	 * Список методов API:
	 *
	 * 1) /admin/emarket/getOrderStatuses/
	 * 2) /admin/emarket/getOrderStatusesList/123
	 * 3) /admin/emarket/getDeliveryStatuses/123
	 * 4) /admin/emarket/getPaymentStatuses/123
	 * 5) /admin/emarket/getOrdersByStatus/123
	 * 6) /admin/emarket/getOrder/703
	 * 7) /admin/emarket/setOrder/703
	 * 8) /admin/emarket/addToken/android/32feeeda75e98206f67c564bcbdc68e63a6cae9c347c2d054c4221c9159ac683/
	 * 9) /admin/emarket/removeToken/32feeeda75e98206f67c564bcbdc68e63a6cae9c347c2d054c4221c9159ac683/
	 */
	class EmarketUMIManagerAPI {

		/** @var emarket|EmarketUMIManagerAPI $module */
		public $module;

		/** @var EmarketAdmin|void $admin базовый класс административной панели модуля */
		public $admin;

		/**
		 * Конструктор
		 * @param emarket $module
		 * @throws coreException
		 */
		public function __construct(emarket $module) {
			$this->module = $this->module === null ? $module : $this->module;

			if (!$this->module->isClassImplemented($module::ADMIN_CLASS)) {
				throw new coreException('Class EmarketAdmin must be implemented');
			}

			$this->admin = $this->module->getImplementedInstance($module::ADMIN_CLASS);
		}

		/**
		 * Возвращает список статусов заказов и количеством заказов
		 * по каждому статусу.
		 * @example /admin/emarket/getOrderStatuses/
		 * @throws selectorException
		 * @throws coreException
		 */
		public function getOrderStatuses() {
			$statusSelector = new selector('objects');
			$statusSelector->types('object-type')->id('emarket-orderstatus');
			$statusSelector->order('priority')->desc();
			/** @var umiObject[] $result */
			$result = $statusSelector->result();

			$statuses = [];

			foreach ($result as $status) {
				$statusId = $status->getId();
				$statuses[] = [
					'@statusId' => $statusId,
					'@statusName' => $status->getName(),
					'@ordersAmount' => $this->getOrderSelector($statusId)->length()
				];
			}

			$this->admin->setData([
				'list:statuses' => $statuses
			]);
			$this->admin->doData();
		}

		/**
		 * Возвращает список статусов заказов
		 * @example /admin/emarket/getOrderStatusesList/123
		 * @param null|int $activeId идентификатор активного статуса
		 * @return array
		 * @throws selectorException
		 */
		public function getOrderStatusesList($activeId = null) {
			return $this->getStatusesListByObjectType('emarket-orderstatus', $activeId, 'priority', 'desc');
		}

		/**
		 * Возвращает список статусов доставки
		 * @example /admin/emarket/getDeliveryStatuses/123
		 * @param null|int $activeId идентификатор активного статуса
		 * @return array
		 * @throws selectorException
		 */
		public function getDeliveryStatuses($activeId = null) {
			return $this->getStatusesListByObjectType('emarket-orderdeliverystatus', $activeId, 'priority', 'asc');
		}

		/**
		 * Возвращает список статусов оплаты
		 * @example /admin/emarket/getPaymentStatuses/123
		 * @param null|int $activeId идентификатор активного статуса
		 * @return array
		 * @throws selectorException
		 */
		public function getPaymentStatuses($activeId = null) {
			return $this->getStatusesListByObjectType('emarket-orderpaymentstatus', $activeId, 'priority', 'asc');
		}

		/**
		 * Возвращает список заказов заданного статуса
		 * @example /admin/emarket/getOrdersByStatus/123
		 * @throws coreException
		 */
		public function getOrdersByStatus() {
			$statusId = (int) getRequest('param0');
			/** @var iUmiObject[] $result заказы */
			$result = $this->getOrderSelector($statusId)->result();
			$currency = mainConfiguration::getInstance()->get('system', 'default-currency');

			if (!$currency || $currency == 'RUR') {
				$currency = 'RUB';
			}

			$orders = [];
			foreach ($result as $order) {
				$orderId = $order->getId();

				$orderDate = $order->getValue('order_date');
				$orderDate = ($orderDate instanceof umiDate) ? $orderDate->getDateTimeStamp() : null;

				$orderInfo = [
					'@statusId' => $statusId,
					'@orderId' => $orderId,
					'@orderNumber' => $order->getValue('number'),
					'@orderDate' => $this->toDateFormat($orderDate),
					'@orderTotalPrice' => number_format($order->getValue('total_price'), 2, '.', ''),
					'@orderTotalOriginalPrice' => number_format($order->getValue('total_original_price'), 2, '.', ''),
					'@priceCurrency' => $currency
				];

				$orderInfo = array_merge(
					$orderInfo,
					$this->module->getOrderCustomerName($order)
				);

				$orders[] = $orderInfo;
			}

			$eventPoint = new umiEventPoint('sendOrdersByStatusToMobileApp');
			$eventPoint->setMode('before');
			$eventPoint->addRef('orders_info', $orders);
			$eventPoint->call();

			$this->admin->setData([
				'list:orders' => $orders
			]);

			$this->admin->doData();
		}

		/**
		 * Возвращает имя клиента заказа в виде массива с одним
		 * ключем или пустого при отсутствии данных
		 * @param iUmiObject $order объект заказа
		 * @return array
		 */
		public function getOrderCustomerName(iUmiObject $order) {
			$orderInfo = [];
			$objects = umiObjectsCollection::getInstance();
			$purchaserOneClickId = $order->getValue('purchaser_one_click');
			$purchaserOneClick = $objects->getObject($purchaserOneClickId);

			if ($purchaserOneClick instanceof iUmiObject) {
				$orderInfo['@customerName'] = $this->getCustomerName($purchaserOneClick);
			} else {
				$customerId = $order->getValue('customer_id');
				$customer = $objects->getObject($customerId);

				if ($customer instanceof iUmiObject) {
					$orderInfo['@customerName'] = $this->getCustomerName($customer);
				}
			}
			
			return $orderInfo;
		}

		/**
		 * Возвращает данные заказа по его id
		 * @example /admin/emarket/getOrder/703
		 * @throws coreException
		 */
		public function getOrder() {
			$orderId = (int) getRequest('param0');

			$objects = umiObjectsCollection::getInstance();
			$order = $objects->getObject($orderId);

			if (!$order instanceof iUmiObject || !$order->getTypeGUID() == 'emarket-order') {
				$this->admin->setData([]);
				$this->admin->doData();
				return;
			}

			$currency = mainConfiguration::getInstance()->get('system', 'default-currency');

			if (!$currency || $currency == 'RUR') {
				$currency = 'RUB';
			}

			$orderDate = $order->getValue('order_date');
			$orderDate = ($orderDate instanceof umiDate) ? $orderDate->getDateTimeStamp() : null;

			$orderData = [
				'@orderId' => (string) $orderId,
				'statuses' => [
					'@title' => $order->getPropByName('status_id')->getTitle(),
					'value' => ['list:statuses' => $this->getOrderStatusesList($order->getValue('status_id'))]
				],
				'orderNumber' => [
					'@title' => $order->getPropByName('number')->getTitle(),
					'@value' => (string) $order->getValue('number')
				],
				'orderDate' => [
					'@title' => $order->getPropByName('order_date')->getTitle(),
					'@value' => (string) $this->toDateFormat($orderDate),
				],
				'orderPrice' => [
					'@title' => $order->getPropByName('total_price')->getTitle(),
					'@value' => number_format($order->getValue('total_price'), 2, '.', '')
				],
				'priceCurrency' => [
					'@title' => getLabel('field-currency'),
					'@value' => (string) $currency
				]
			];

			$usedFields = [
				'order_items',
				'social_order_id',
				'customer_id',
				'domain_id',
				'total_original_price',
				'status_id',
				'number',
				'total_price',
				'total_amount',
				'status_change_date',
				'order_date'
			];

			$items = [];
			foreach ($order->getType()->getFieldsGroupByName('order_props')->getFields() as $field) {
				if (in_array($field->getName(), $usedFields)) {
					continue;
				}

				$value = $order->getValue($field->getName());

				if (!$value) {
					continue;
				}

				switch ($field->getFieldType()->getDataType()) {
					case 'relation': {
						$value = $objects->getObject($value)->getName();
						break;
					}
					case 'date': {
						$value = $this->toDateFormat($value->getDateTimeStamp());
						break;
					}
				}

				$items[] = [
					'@title' => $order->getPropByName($field->getName())->getTitle(),
					'@value' => (string) $value,
				];
			}
			if (umiCount($items) > 0) {
				$orderData['orderProperties'] = [
					'@title' => $order->getType()->getFieldsGroupByName('order_props')->getTitle(),
					'list:values' => $items
				];
			}

			$payment = $objects->getObject($order->getValue('payment_id'));
			if ($payment instanceof iUmiObject) {
				$orderData['payment'] = [
					'statuses' => [
						'@title' => $order->getPropByName('payment_status_id')->getTitle(),
						'value' => ['list:statuses' => $this->getPaymentStatuses($order->getValue('payment_status_id'))]
					],
					'method' => [
						'@title' => $order->getPropByName('payment_id')->getTitle(),
						'@value' => $payment->getName()
					],
					'documentNumber' => [
						'@title' => $order->getPropByName('payment_document_num')->getTitle(),
						'@value' => $order->getValue('payment_document_num')
					]
				];

				$usedFields = ['payment_id', 'payment_status_id', 'payment_document_num'];
				$items = [];

				foreach ($order->getType()->getFieldsGroupByName('order_payment_props')->getFields() as $field) {
					if (in_array($field->getName(), $usedFields)) {
						continue;
					}

					$value = $this->parseOrderValue(
						$field->getFieldType()->getDataType(),
						$order->getValue($field->getName())
					);

					if (!$value) {
						continue;
					}

					$items[] = [
						'@title' => $order->getPropByName($field->getName())->getTitle(),
						'@value' => (string) $value
					];
				}

				$orderData['payment']['list:values'] = $items;
			}

			$delivery = $objects->getObject($order->getValue('delivery_id'));

			if ($delivery instanceof iUmiObject) {
				$orderData['delivery'] = [
					'statuses' => [
						'@title' => $order->getPropByName('delivery_status_id')->getTitle(),
						'value' => ['list:statuses' => $this->getDeliveryStatuses($order->getValue('delivery_status_id'))]
					],
					'method' => [
						'@title' => $order->getPropByName('delivery_id')->getTitle(),
						'@value' => $this->module->getOrderDeliveryName($order)
					],
					'price' => [
						'@title' => $order->getPropByName('delivery_price')->getTitle(),
						'@value' => number_format($order->getValue('delivery_price'), 2, '.', '')
					]
				];

				$usedFields = $this->module->getDeliveryUsedFields();

				$items = [];

				foreach ($order->getType()->getFieldsGroupByName('order_delivery_props')->getFields() as $field) {
					if (in_array($field->getName(), $usedFields)) {
						continue;
					}

					$value = $this->parseOrderValue(
						$field->getFieldType()->getDataType(),
						$order->getValue($field->getName())
					);

					if (!$value) {
						continue;
					}

					$items[] = [
						'@title' => $order->getPropByName($field->getName())->getTitle(),
						'@value' => (string) $value
					];
				}
				$orderData['delivery']['list:values'] = $items;
			}

			$address = $objects->getObject($order->getValue('delivery_address'));

			if ($address instanceof iUmiObject) {
				$items = [];
				$addressType = $address->getType();
				/** @var iUmiField $field */
				foreach ($addressType->getAllFields() as $field) {
					$value = $address->getValue($field->getName());

					if (!$value) {
						continue;
					}

					/** @var iUmiFieldType $fieldType */
					$fieldType = $field->getFieldType();
					$value = $this->parseOrderValue($fieldType->getDataType(), $value);
					$items[] = [
						'@title' => $field->getTitle(),
						'@value' => (string) $value
					];
				}
				$orderData['list:address'] = $items;
			}

			$event = new umiEventPoint('orderCustomAddressInfoBuild');
			$event->setParam('order', $order);
			$event->setParam('address', $address);
			$event->addRef('orderData', $orderData);
			$event->call();

			$customer = $objects->getObject($order->getValue('customer_id'));
			if ($customer instanceof iUmiObject) {

				$items = [];
				$items[] = [
					'@title' => getLabel('field-login'),
					'@value' => $customer->getName(),
				];

				$items = array_merge(
					$items, 
					$this->module->getOrderCustomerBaseInfo($order)
				);

				$systemFields = [
					'login',
					'password',
					'groups',
					'activate_code',
					'loginza',
					'is_activated',
					'last_request_time',
					'subscribed_pages',
					'rated_pages',
					'is_online',
					'messages_count',
					'orders_refs',
					'delivery_addresses',
					'user_dock',
					'preffered_currency',
					'user_settings_data',
					'last_order',
					'bonus',
					'legal_persons',
					'spent_bonus',
					'filemanager',
					'filemanager_directory',
					'appended_file_extensions',
					'register_date',
					'referer',
					'target',
					'e-mail',
					'email',
					'lname',
					'fname',
					'father_name',
					'phone',
					'ip',
					'purchase_one_click'
				];

				$customerType = $customer->getType();
				foreach ($customerType->getAllFields() as $field) {

					if (in_array($field->getName(), $systemFields)) {
						continue;
					}

					/** @var iUmiFieldType $fieldType */
					$fieldType = $field->getFieldType();
					$value = $this->parseOrderValue(
						$fieldType->getDataType(),
						$customer->getPropByName($field->getName())->getValue()
					);

					$event = new umiEventPoint('orderCustomerPropertyGet');
					$event->setParam('order', $order);
					$event->setParam('field', $field);
					$event->addRef('value', $value);
					$event->call();

					if (!$value) {
						continue;
					}

					$items[] = [
						'@title' => $customer->getPropByName($field->getName())->getTitle(),
						'@value' => (string) $value,
					];
				}
				$orderData['list:customerInfo'] = $items;
			}

			$items = [];
			$hierarchy = umiHierarchy::getInstance();

			foreach ($order->getValue('order_items') as $itemId) {
				$item = $objects->getObject($itemId);
				$items[] = [
					'@name' => $item->getName(),
					'@amount' => (string) $item->getValue('item_amount'),
					'@price' => number_format($item->getValue('item_price'), 2, '.', ''),
					'@totalPrice' => number_format($item->getValue('item_total_price'), 2, '.', ''),
					'@totalOriganalPrice' => number_format($item->getValue('item_total_original_price'), 2, '.', ''),
					'@link' => $hierarchy->getPathById($item->getValue('item_link'))
				];
			}

			if ($items) {
				$orderData['list:orderItems'] = $items;
			}

			$appInfo = [];
			$systemGroups = [
				'order_props',
				'order_credit_props',
				'statistic_info',
				'order_payment_props',
				'order_delivery_props',
				'order_discount_props',
				'integration_date'
			];

			/** @var iUmiFieldsGroup $fieldsGroup */
			foreach ($order->getType()->getFieldsGroupsList() as $fieldsGroup) {
				if (in_array($fieldsGroup->getName(), $systemGroups)) {
					continue;
				}

				$items = [];

				foreach ($fieldsGroup->getFields() as $field) {
					$title = $field->getTitle();
					$value = $order->getValue($field->getName());

					if ($fieldsGroup->getName() == 'purchase_one_click') {
						if (!$value) {
							break;
						}
						$title = getLabel('message_to_manager');
						$value = getLabel('contact_purchaser');
					}

					$shouldSkipField = false;
					$event = new umiEventPoint('orderAdditionalGroupProcessing');
					$event->setParam('group', $fieldsGroup);
					$event->setParam('field', $field);
					$event->addRef('value', $value);
					$event->addRef('shouldSkipField', $shouldSkipField);
					$event->call();

					if ($shouldSkipField) {
						continue;
					}

					$item = [
						'@title' => $title,
						'@value' => (string) $value
					];
					$items[] = $item;
				}

				$appInfo[] = [
					'@title' => $fieldsGroup->getTitle(),
					'list:values' => $items
				];
			}

			$orderData['list:info'] = $appInfo;

			$eventPoint = new umiEventPoint('sendOrderByIdToMobileApp');
			$eventPoint->setMode('after');
			$eventPoint->addRef('order_info', $orderData);
			$eventPoint->call();

			$this->admin->setData($orderData);
			$this->admin->doData();
		}

		/**
		 * Возвращает массив с основными данными клиента 
		 * выполнившего заказ (имя, почта, телефон)
		 * @param iUmiObject $order объект заказа
		 * @return array массив с данными клиента
		 */
		public function getOrderCustomerBaseInfo(iUmiObject $order) {
			$objects = umiObjectsCollection::getInstance();
			$customer = $objects->getObject($order->getValue('customer_id'));

			if (!$customer instanceof iUmiObject) {
				return [];
			}

			$purchaserOneClickId = $order->getValue('purchaser_one_click');
			$purchaserOneClick = $objects->getObject($purchaserOneClickId);

			if ($purchaserOneClick instanceof iUmiObject) {
				$customer = $purchaserOneClick;
			}

			$items = [
				[
					'@title' => getLabel('label-emarket-mobile-fio'),
					'@value' => $this->getCustomerName($customer)
				]
			];

			if ($customer->getPropByName('email')) {
				$email = $customer->getValue('email');
				$emailTitle = $customer->getPropByName('email')->getTitle();
			} else {
				$email = $customer->getValue('e-mail');
				$emailTitle = $customer->getPropByName('e-mail')->getTitle();
			}

			$phone = $customer->getValue('phone');
			$phoneTitle = $customer->getPropByName('phone') ? 
				$customer->getPropByName('phone')->getTitle() : '';

			$items[] = [
				'@title' => $emailTitle,
				'@value' => $email
			];

			if ($phone) {
				$items[] = [
					'@title' => $phoneTitle,
					'@value' => $phone
				];
			}

			return $items;
		}

		/**
		 * Возвращает набор полей доставки 
		 * @return array
		 */
		public function getDeliveryUsedFields() {
			return [
				'delivery_id',
				'delivery_status_id',
				'delivery_address',
				'delivery_price'
			];
		}

		/**
		 * Возвращает наименование метода 
		 * доставки у заказа
		 * @param iUmiObject $order
		 * @return string
		 */
		public function getOrderDeliveryName(iUmiObject $order) {
			$delivery = umiObjectsCollection::getInstance()
				->getObject($order->getValue('delivery_id'));
				
			if ($delivery instanceOf iUmiObject) {
				return $delivery->getName();
			}
			
			return '';
		}

		/**
		 * Сохраняет изменения заказа из UMI.Manager в UMI.CMS
		 * @example /admin/emarket/setOrder/703
		 * @throws coreException
		 */
		public function setOrder() {
			$orderId = (int) getRequest('param0');
			$order = order::get($orderId);
			$object = $order->getObject();

			if (!$object instanceof iUmiObject || !$object->getTypeGUID() == 'emarket-order') {
				$this->admin->setData([]);
				$this->admin->doData();
				return;
			}

			$statusSetters = [
				'statusId' => 'setOrderStatus',
				'paymentStatusId' => 'setPaymentStatus',
				'deliveryStatusId' => 'setDeliveryStatus'
			];

			$status = 'not changed';

			foreach ($statusSetters as $requestKey => $setter) {
				if (!isset($_REQUEST[$requestKey])) {
					continue;
				}

				$v = (int) $_REQUEST[$requestKey];
				if ($v == 0) {
					$v = null;
				}

				$event = new umiEventPoint('systemModifyPropertyValueMobile');
				$event->setParam('entity', $order);
				$event->setParam('property', $requestKey);
				$event->setParam('newValue', $v);
				$event->setParam('oldValue', $order->getValue($requestKey));
				$event->setMode('before');
				$event->call();

				$order->$setter($v);
				$status = 'updated';

				$event->setMode('after');
				$event->call();
			}

			$objects = umiObjectsCollection::getInstance();
			$address = $objects->getObject($order->getValue('delivery_address'));

			if ($address instanceof iUmiObject) {
				$addressData = isset($_REQUEST['address']) ? $_REQUEST['address'] : [];

				if (is_array($addressData) && isset($addressData['comment'])) {
					$address->setValue('order_comments', $addressData['comment']);
					$status = 'updated';
					$address->commit();
				}
			}

			$this->admin->setData(['@status' => $status]);
			$this->admin->doData();
		}

		/**
		 * Добавляет токен и тип платформы для push уведомлений.
		 * Возвращает error, если:
		 *
		 * 1) Не был передан токен;
		 * 2) Не удалось связаться с сервером push сообщений;
		 * 3) лицензия данной cms не прошла проверку;
		 *
		 * Если все прошло нормально, возвратит ok и добавит данный токен и платформу
		 * в справочник мобильных устройств данной cms для текущего домена.
		 *
		 * @example /admin/emarket/addToken/android/32feeeda75e98206f67c564bcbdc68e63a6cae9c347c2d054c4221c9159ac683/
		 * @throws Exception
		 * @throws coreException
		 * @throws selectorException
		 * @throws umiRemoteFileGetterException
		 */
		public function addToken() {
			$platform = (string) getRequest('param0');
			$token = (string) getRequest('param1');

			if ($token === '' || $platform === '' || !in_array($platform, ['android', 'ios'])) {
				$this->admin->setData([
					'@result' => 'error'
				]);

				$this->admin->doData();
				return;
			}

			$selector = new selector('objects');
			$selector->types('object-type')->id('emarket-mobile-platform');
			$selector->where('platform_identificator')->equals($platform);
			$selector->limit(0, 1);

			if ($selector->length() != 1) {
				$this->admin->setData([
					'@result' => 'error'
				]);

				$this->admin->doData();
				return;
			}

			$objectPlatform = $selector->first();
			if (!$objectPlatform instanceOf umiObject) {
				$this->admin->setData([
					'@result' => 'error'
				]);

				$this->admin->doData();
				return;
			}

			$currentDomain = Service::DomainDetector()->detect();

			$selector = new selector('objects');
			$selector->types('object-type')->id('emarket-mobile-devices');
			$selector->where('domain_id')->equals($currentDomain->getId());
			$selector->where('token')->equals($token);
			$selector->where('platform')->equals($objectPlatform->getId());

			if ($selector->length() == 1) {
				$result = [
					'@token' => $token,
					'@platform' => $platform,
					'@result' => 'ok'
				];

				$this->admin->setData($result);
				$this->admin->doData();
				return;
			}

			$registrySettings = Service::RegistrySettings();

			$request = [
				'requestType' => 'addToken',
				'keycode' => $registrySettings->getLicense(),
				'build' => $registrySettings->getRevision(),
				'domain' => $currentDomain->getHost(),
				'token' => $token,
				'platform' => $platform
			];

			$response = umiRemoteFileGetter::get(PUSH_SERVER, false, false, $request, false, 'POST', 10);

			$xml = simplexml_load_string($response);
			if (!$xml) {
				$result = [
					'@token' => $token,
					'@platform' => $platform,
					'@result' => 'error'
				];

				$this->admin->setData($result);
				$this->admin->doData();
				return;
			}

			if ($xml->attributes()->count > 0) {
				$tokens = [];

				foreach ($xml->token as $node) {
					$tokens[] = (string) $node;
				}

				$selector = new selector('objects');
				$selector->types('object-type')->id('emarket-mobile-devices');
				$selector->where('domain_id')->equals($currentDomain->getId());
				$selector->where('token')->notequals($tokens);

				if ($selector->length()) {
					/** @var iUmiObject $object */
					foreach ($selector->result() as $object) {
						$object->delete();
					}
				}
			}

			$objectTypeId = umiObjectTypesCollection::getInstance()->getTypeByGUID('emarket-mobile-devices')->getId();
			$objectId = umiObjectsCollection::getInstance()
				->addObject(getLabel('label-emarket-mobile-device'), $objectTypeId);
			$object = umiObjectsCollection::getInstance()->getObject($objectId);
			$object->setValue('domain_id', $currentDomain->getId());
			$object->setValue('token', $token);
			$object->setValue('platform', $objectPlatform->getId());
			$object->setValue('active', 1);
			$object->commit();

			$result = [
				'@token' => $token,
				'@platform' => $platform,
				'@result' => 'ok'
			];

			$this->admin->setData($result);
			$this->admin->doData();
		}

		/**
		 * Удаляет токен для push уведомлений из системы.
		 * Пытается удалить на push сервере.
		 * Возвращает error, если токен передан не был или
		 * отсутствовал в справочнике мобильных устройств для текущего домена.
		 * В любом другом случае вернет ok, и удалит данный токен из справочника.
		 * @example /admin/emarket/removeToken/32feeeda75e98206f67c564bcbdc68e63a6cae9c347c2d054c4221c9159ac683/
		 * @throws Exception
		 * @throws coreException
		 * @throws selectorException
		 * @throws umiRemoteFileGetterException
		 */
		public function removeToken() {
			$token = (string) getRequest('param0');

			if ($token === '') {
				$this->admin->setData([
					'@result' => 'error'
				]);

				$this->admin->doData();
				return;
			}

			$currentDomain = Service::DomainDetector()->detect();

			$selector = new selector('objects');
			$selector->types('object-type')->id('emarket-mobile-devices');
			$selector->where('domain_id')->equals($currentDomain->getId());
			$selector->where('token')->equals($token);
			$selector->limit(0, 1);

			if ($selector->length() != 1) {
				$this->admin->setData([
					'@result' => 'error'
				]);

				$this->admin->doData();
				return;
			}

			$request = [
				'requestType' => 'removeToken',
				'domain' => $currentDomain->getHost(),
				'token' => $token,
			];

			umiRemoteFileGetter::get(PUSH_SERVER, false, false, $request, false, 'POST', 10);

			$object = $selector->first();
			umiObjectsCollection::getInstance()->delObject($object->getId());
			$this->admin->setData([
				'@result' => 'ok'
			]);

			$this->admin->doData();
		}

		/**
		 * Возвращает список статусов
		 * @param int $objectType идентификатор объектного типа статуса
		 * @param null|int $activeId идентификатор активного статуса
		 * @param bool|string $orderField сортируемое поле
		 * @param bool|string $orderType тип сортировки
		 * @return array
		 * @throws selectorException
		 */
		private function getStatusesListByObjectType(
			$objectType,
			$activeId = null,
			$orderField = false,
			$orderType = false
		) {
			if ($activeId === null) {
				$defaultObject = umiObjectsCollection::getInstance()->getObjectByGUID($objectType . '-default');
				if ($defaultObject instanceOf umiObject) {
					$activeId = $defaultObject->getId();
				}
			}

			$statusSelector = new selector('objects');
			$statusSelector->types('object-type')->id($objectType);

			if ($orderField && $orderType) {
				$statusSelector->order($orderField)->$orderType();
			}

			/** @var umiObject[] $result */
			$result = $statusSelector->result();

			$statuses = [];
			foreach ($result as $status) {
				$item = [
					'@statusId' => (string) $status->getId(),
					'@statusName' => $status->getName(),
				];

				if ($status->getId() == $activeId) {
					$item['@isActive'] = true;
				}

				$statuses[] = $item;
			}

			return $statuses;
		}

		/**
		 * Формирует значение поля и возвращает его
		 * @param string $type тип поля
		 * @param mixed $value значение поля
		 * @return bool|string
		 */
		private function parseOrderValue($type, $value) {

			if (!$value) {
				return '';
			}

			switch ($type) {
				case 'relation': {
					return umiObjectsCollection::getInstance()->getObject($value)->getName();
				}
				case 'date': {
					$timestamp = ($value instanceof umiDate) ? $value->getDateTimeStamp() : time();
					return $this->toDateFormat($timestamp);
				}
				default: {
					return $value;
				}
			}
		}

		/**
		 * Возвращает дату в формате d.m.Y H:i по timestamp
		 * @param int $timestamp
		 * @return bool|string
		 */
		private function toDateFormat($timestamp) {
			return date('d.m.Y H:i', $timestamp);
		}

		/**
		 * Получает selector заказа, по его статусу.
		 * @param int $statusId статус
		 * @return selector селектор
		 * @throws coreException
		 * @throws selectorException
		 */
		private function getOrderSelector($statusId) {
			$domainId = Service::DomainDetector()->detectId();

			$orderSelector = new selector('objects');
			$orderSelector->types('object-type')->id('emarket-order');
			$orderSelector->where('status_id')->equals($statusId);
			$orderSelector->where('name')->notequals(order::DUMMY_NAME);
			$orderSelector->where('domain_id')->equals($domainId);

			return $orderSelector;
		}

		/**
		 * Формирует и возвращает ФИО покупателя.
		 * @param customer|iUmiObject $customer покупатель
		 * @return string "фамилия имя"
		 */
		public function getCustomerName($customer) {

			$name = $customer->getValue('fname');
			if ($customer->getValue('father_name')) {
				$name .= ' ' . $customer->getValue('father_name');
			}

			if ($customer->getValue('lname')) {
				$name .= ' ' . $customer->getValue('lname');
			}

			return $name;
		}
	}

