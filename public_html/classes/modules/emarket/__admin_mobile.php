<?php

	use UmiCms\Service;

	/**
	 * Класс emarket mobile admin
	 * Реализует api для мобильного приложения umi.cms
	 */
	abstract class __emarket_admin_mobile extends baseModuleAdmin {
		/**
		 * Получает статусы заказа.
		 * Сортирует их по приоритету(поле priority),
		 * получает количество заказов с данным статусом.
		 * @api мобильное приложение
		 * @example /admin/emarket/getOrderStatuses
		 */
		public function getOrderStatuses() {
			$statusSelector = new selector('objects');
			$statusSelector->types('object-type')->id('emarket-orderstatus');
			$statusSelector->order('priority')->desc();
			/** @var umiObject[] $result */
			$result = $statusSelector->result();

			$statuses = array();
			foreach($result as $status) {
				$statusId = $status->getId();
				$statuses[] = array(
					'@statusId' => $statusId,
					'@statusName' => $status->getName(),
					'@ordersAmount' => $this->getOrderSelector($statusId)->length()
				);
			}

			$this->setData(array('list:statuses' => $statuses));
			$this->doData();
		}

		public function getStatusesListByObjectType($objectType, $activeId = null, $orderField = false, $orderType = false) {
			if (is_null($activeId)) {
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

			$result = $statusSelector->result();

			$statuses = array();
			foreach($result as $status) {
				$item = array(
					'@statusId' => (string) $status->getId(),
					'@statusName' => $status->getName(),
				);

				if ($status->getId() == $activeId) {
					$item['@isActive'] = true;
				}

				$statuses[] = $item;
			}

			return $statuses;
		}

		/**
		 * Получает статусы заказа для вывода в методе getOrder.
		 * @api мобильное приложение
		 * @example /admin/emarket/getOrderStatusesList
		 */
		public function getOrderStatusesList($activeId = null) {
			return $this->getStatusesListByObjectType('emarket-orderstatus', $activeId, 'priority', 'desc');
		}

		/**
		 * Получает статусы доставки.
		 * @api мобильное приложение
		 * @example /admin/emarket/getDeliveryStatuses
		 */
		public function getDeliveryStatuses($activeId = null) {
			return $this->getStatusesListByObjectType('emarket-orderdeliverystatus', $activeId, 'priority', 'asc');
		}

		/**
		 * Получает статусы оплаты.
		 * @api мобильное приложение
		 * @example /admin/emarket/getPaymentStatuses
		 */
		public function getPaymentStatuses($activeId = null) {
			return $this->getStatusesListByObjectType('emarket-orderpaymentstatus', $activeId, 'priority', 'asc');
		}

		/**
		 * Получает заказы с указанным статусом.
		 * @api мобильное приложение
		 * @example /admin/emarket/getOrdersByStatus/1
		 */
		public function getOrdersByStatus() {
			$statusId = (int) getRequest('param0');

			/** @var order[] $result заказы */
			$result = $this->getOrderSelector($statusId)->result();
			$objects = umiObjectsCollection::getInstance();
			$currency = mainConfiguration::getInstance()->get('system', 'default-currency');
			if (!$currency || $currency == 'RUR') {
				$currency = 'RUB';
			}

			$orders = array();

			foreach($result as $order) {
				$orderId = $order->getId();

				$orderDate = $order->getValue('order_date');
				$orderDate = ($orderDate instanceof umiDate) ? $orderDate->getDateTimeStamp() : null;

				$orderInfo = array (
					'@statusId' => $statusId,
					'@orderId' => $orderId,
					'@orderNumber' => $order->getValue('number'),
					'@orderDate' => $this->toDateFormat($orderDate),
					'@orderTotalPrice' => number_format($order->getValue('total_price'), 2, ".", ""),
					'@orderTotalOriginalPrice' => number_format($order->getValue('total_original_price'), 2, ".", ""),
					'@priceCurrency' => $currency
				);

				$purchaserOneClickId = $order->getValue('purchaser_one_click');
				$purchaserOneClick = $objects->getObject($purchaserOneClickId);

				if ($purchaserOneClick instanceof umiObject) {
					$orderInfo['@customerName'] = $this->getCustomerName($purchaserOneClick);
				} else {
					$customerId = $order->getValue('customer_id');
					$customer = $objects->getObject($customerId);

					if ($customer instanceof umiObject) {
						$orderInfo['@customerName'] = $this->getCustomerName($customer);
					}
				}
				$orders[] = $orderInfo;
			}

			$eventPoint = new umiEventPoint('sendOrdersByStatusToMobileApp');
			$eventPoint->setMode('before');
			$eventPoint->addRef('orders_info', $orders);
			$eventPoint->call();

			$this->setData(array(
				'list:orders' => $orders
			));

			$this->doData();
		}

		/**
		 * Получает информацию о заказе по его идентификатору.
		 * @api мобильное приложение
		 * @example /admin/emarket/getOrder/703
		 */
		public function getOrder() {
			$orderId = (int) getRequest('param0');

			$objects = umiObjectsCollection::getInstance();
			$order = $objects->getObject($orderId);

			if (!$order instanceof umiObject || !$order->getTypeGUID() == 'emarket-order') {
				$this->setData(array());
				$this->doData();
				return;
			}

			$currency = mainConfiguration::getInstance()->get('system', 'default-currency');
			if (!$currency || $currency == 'RUR') {
				$currency = 'RUB';
			}

			$orderDate = $order->getValue('order_date');
			$orderDate = ($orderDate instanceof umiDate) ? $orderDate->getDateTimeStamp() : null;

			$orderData = array(
				'@orderId' => (string) $orderId,
				'statuses' => array(
					'@title' => $order->getPropByName('status_id')->getTitle(),
					'value' => array('list:statuses' => $this->getOrderStatusesList($order->getValue('status_id')))
				),
				'orderNumber' => array(
					'@title' => $order->getPropByName('number')->getTitle(),
					'@value' => (string) $order->getValue('number')
				),
				'orderDate' => array(
					'@title' => $order->getPropByName('order_date')->getTitle(),
					'@value' => (string) $this->toDateFormat($orderDate),
				),
				'orderPrice' => array(
					'@title' => $order->getPropByName('total_price')->getTitle(),
					'@value' => (string) number_format($order->getValue('total_price'), 2, ".", "")
				),
				'priceCurrency' => array(
					'@title' => getLabel('field-currency'),
					'@value' => (string) $currency
				)
			);

			$usedFields = array('order_items', 'social_order_id', 'customer_id', 'domain_id', 'total_original_price', 'status_id', 'number', 'total_price', 'total_amount', 'status_change_date', 'order_date', );
			$items = array();
			foreach($order->getType()->getFieldsGroupByName('order_props')->getFields() as $field) {
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

				$items[] = array(
					'@title' => $order->getPropByName($field->getName())->getTitle(),
					'@value' => (string) $value,
				);
			}
			if (count($items) > 0) {
				$orderData['orderProperties'] = array(
					'@title' => $order->getType()->getFieldsGroupByName('order_props')->getTitle(),
					'list:values' => $items
				);
			}

			$payment = $objects->getObject($order->getValue('payment_id'));
			if ($payment instanceof umiObject) {
				$orderData['payment'] = array(
					'statuses' => array(
						'@title' => $order->getPropByName('payment_status_id')->getTitle(),
						'value' => array('list:statuses' => $this->getPaymentStatuses($order->getValue('payment_status_id')))
					),
					'method' => array(
						'@title' => $order->getPropByName('payment_id')->getTitle(),
						'@value' => $payment->getName()
					),
					'documentNumber' => array(
						'@title' => $order->getPropByName('payment_document_num')->getTitle(),
						'@value' => $order->getValue('payment_document_num')
					)
				);

				$usedFields = array('payment_id', 'payment_status_id', 'payment_document_num');
				$items = array();
				foreach($order->getType()->getFieldsGroupByName('order_payment_props')->getFields() as $field) {
					if (in_array($field->getName(), $usedFields)) {
						continue;
					}

					$value = $this->parseOrderValue($field->getFieldType()->getDataType(), $order->getValue($field->getName()));

					if ( !$value ) {
						continue;
					}

					$items[] = array(
						'@title' => $order->getPropByName($field->getName())->getTitle(),
						'@value' => (string) $value
					);
				}
				$orderData['payment']['list:values'] = $items;
			}


			/** Адрес доставки */
			$delivery = $objects->getObject($order->getValue('delivery_id'));
			if ($delivery instanceof umiObject) {
				$orderData['delivery'] = array(
					'statuses' => array(
						'@title' => $order->getPropByName('delivery_status_id')->getTitle(),
						'value' => array('list:statuses' => $this->getDeliveryStatuses($order->getValue('delivery_status_id')))
					),
					'method' => array(
						'@title' => $order->getPropByName('delivery_id')->getTitle(),
						'@value' => $delivery->getName()
					),
					'price' => array(
						'@title' => $order->getPropByName('delivery_price')->getTitle(),
						'@value' => (string) number_format($order->getValue('delivery_price'), 2, ".", "")
					)
				);

				$usedFields = array('delivery_id', 'delivery_status_id', 'delivery_address', 'delivery_price');
				$items = array();
				foreach($order->getType()->getFieldsGroupByName('order_delivery_props')->getFields() as $field) {
					if (in_array($field->getName(), $usedFields)) {
						continue;
					}

					$value = $this->parseOrderValue($field->getFieldType()->getDataType(), $order->getValue($field->getName()));
					if ( !$value ) {
						continue;
					}

					$items[] = array(
						'@title' => $order->getPropByName($field->getName())->getTitle(),
						'@value' => (string) $value
					);
				}
				$orderData['delivery']['list:values'] = $items;
			}

			$address = $objects->getObject($order->getValue('delivery_address'));

			if ($address instanceof umiObject) {
				$items = array();
				$addressType = $address->getType();
				foreach($addressType->getAllFields() as $field) {
					$value = $address->getValue($field->getName());
					if (!$value) {
						continue;
					}
					$value = $this->parseOrderValue($field->getFieldType()->getDataType(), $value);
					$items[] = array(
						'@title' => $field->getTitle(),
						'@value' => (string) $value
					);
				}
				$orderData['list:address'] = $items;
			}

			$customer = $objects->getObject($order->getValue('customer_id'));
			if ($customer instanceof umiObject) {

				$items = array();
				$items[] = array(
					'@title' => getLabel('field-login'),
					'@value' => $customer->name,
				);

				$purchaserOneClickId = $order->getValue('purchaser_one_click');
				$purchaserOneClick = $objects->getObject($purchaserOneClickId);
				$tmpCustomer = false;
				if ($purchaserOneClick instanceof umiObject) {
					$tmpCustomer = $customer;
					$customer = $purchaserOneClick;
				}

				$items[] = array(
					'@title' => getLabel('label-emarket-mobile-fio'),
					'@value' => $this->getCustomerName($customer)
				);

				$email = $customer->getValue('email') ?  $customer->getValue('email') : $customer->getValue('e-mail');
				$emailTitle = $customer->getPropByName('email') ?  $customer->getPropByName('email')->getTitle() : $customer->getPropByName('e-mail')->getTitle();
				$phone = $customer->getValue('phone');
				$phoneTitle = $customer->getPropByName('phone') ? $customer->getPropByName('phone')->getTitle() : '';

				if ($tmpCustomer) {
					$customer = $tmpCustomer;
				}

				$items[] = array(
					'@title' => $emailTitle,
					'@value' => $email
				);

				if ($phone) {
					$items[] = array(
						'@title' => $phoneTitle,
						'@value' => $phone
					);
				}

				$systemFields = array(
					'login', 'password', 'groups', 'activate_code', 'loginza', 'is_activated', 'last_request_time', 'subscribed_pages', 'rated_pages', 'is_online',
					'messages_count', 'orders_refs', 'delivery_addresses', 'user_dock', 'preffered_currency', 'user_settings_data', 'last_order', 'bonus', 'legal_persons', 'spent_bonus',
					'filemanager', 'filemanager_directory', 'appended_file_extensions', 'register_date', 'referer', 'target', 'e-mail', 'email', 'lname', 'fname', 'father_name', 'phone', 'ip', 'purchase_one_click'
				);

				$customerType = $customer->getType();
				foreach($customerType->getAllFields() as $field) {
					if (in_array($field->getName(), $systemFields)) {
						continue;
					}

					$value = $this->parseOrderValue($field->getFieldType()->getDataType(), $customer->getPropByName($field->getName())->getValue());

					if (!$value) {
						continue;
					}

					$items[] = array(
						'@title' => $customer->getPropByName($field->getName())->getTitle(),
						'@value' => (string) $value,
					);
				}
				$orderData['list:customerInfo'] = $items;
			}

			//TODO: подумать как передавать колонки
			$items = array();
			$hierarchy = umiHierarchy::getInstance();
			foreach ($order->getValue('order_items') as $itemId) {
				$item = $objects->getObject($itemId);
				$items[] = array(
					'@name' => $item->getName(),
					'@amount' => (string) $item->getValue('item_amount'),
					'@price' => (string) number_format($item->getValue('item_price'), 2, ".", ""),
					'@totalPrice' => (string) number_format($item->getValue('item_total_price'), 2, ".", ""),
					'@totalOriganalPrice' => (string) number_format($item->getValue('item_total_original_price'), 2, ".", ""),
					'@link' => $hierarchy->getPathById($item->getValue('item_link'))
				);
			}

			if ($items) {
				$orderData['list:orderItems'] = $items;
			}

			/** Дополнительные группы */
			$appInfo = array();
			$systemGroups = array('order_props', 'order_credit_props', 'statistic_info', 'order_payment_props', 'order_delivery_props', 'order_discount_props', 'integration_date');
			foreach($order->getType()->getFieldsGroupsList() as $fieldsGroup) {
				if (in_array($fieldsGroup->getName(), $systemGroups)) continue;
				$items = array();
				foreach($fieldsGroup->getFields() as $field) {
					$title = $field->getTitle();
					$value = $order->getValue($field->getName());
					if ($fieldsGroup->getName() == 'privilegii') {
						switch($field->getName()) {
							case 'status_privilegii': {
								$value = 'Не установлен';
								break;
							}
							case 'skidka_po_privilegii': {
								$value = 'Отсутствует';
								break;
							}
						}
					} elseif ($fieldsGroup->getName() == 'garantijnoe_obsluzhivanie') {
						switch($field->getName()) {
							case 'garantijnye_bonusy': {
								$value = 'Нет значения';
								break;
							}
						}
					} elseif ($fieldsGroup->getName() == 'puskonaladka') {
						switch($field->getName()) {
							case 'vklyuchena_v_zakaz': {
								$value = 'Да';
								break;
							}
							case 'stoimost': {
								$value = '100.00';
								break;
							}
							case 'srazu_posle_dostavki': {
								$value = 'Да';
								break;
							}
							case 'kommentarij_k_puskonaladke': {
								$value = 'Сложные технические условия. Местность заболоченая, доставка материалов затруднена.';
								break;
							}

						}
					} elseif ($fieldsGroup->getName() == 'purchase_one_click') {
						if (!$value) {
							break;
						}
						$title = getLabel('message_to_manager');
						$value = getLabel('contact_purchaser');
					}
					$item = array(
						'@title' => $title,
						'@value' => (string) $value
					);
					$items[] = $item;
				}

				$appInfo[] = array(
					'@title' => $fieldsGroup->getTitle(),
					'list:values' => $items
				);
			}

			$orderData['list:info'] = $appInfo;

			$eventPoint = new umiEventPoint('sendOrderByIdToMobileApp');
			$eventPoint->setMode('after');
			$eventPoint->addRef('order_info', $orderData);
			$eventPoint->call();

			$this->setData($orderData);
			$this->doData();
		}

		public function parseOrderValue($type, $value) {
			if (!$value) {
				return '';
			}
			switch($type) {
				case 'relation': {
					return umiObjectsCollection::getInstance()->getObject($value)->getName();
				}
				case 'date': {
					return $this->toDateFormat($value->getDateTimeStamp());
				}
				default: {
					return $value;
				}
			}
		}

		public function toDateFormat($timestamp) {
			return date('d.m.Y H:i', $timestamp);
		}
		
		/**
		 * Сохраняет информацию о заказе по его идентификатору.
		 * @api мобильное приложение
		 * @example /admin/emarket/setOrder/703
		 */
		public function setOrder() {
			$orderId = (int) getRequest('param0');

			$order = order::get($orderId);
			$object = $order->getObject();
			
			
			if (!$object instanceof umiObject || !$object->getTypeGUID() == 'emarket-order') {
				$this->setData(array());
				$this->doData();
				return;
			}

			$statusSetters = array(
				'statusId' => 'setOrderStatus',
				'paymentStatusId' => 'setPaymentStatus',
				'deliveryStatusId' => 'setDeliveryStatus'
			);

			$status = 'not changed';
			foreach ($statusSetters as $requestKey => $setter) {
				if (!isset($_REQUEST[$requestKey])) {
					continue;
				}

				$v = (int) $_REQUEST[$requestKey];
				if ($v == 0) {
					$v = null;
				}

				$order->$setter( $v );
				$status = 'updated';
			}
			
			$objects = umiObjectsCollection::getInstance();
			$address = $objects->getObject($order->getValue('delivery_address'));
			if ($address instanceof umiObject) {
				$addressData = isset($_REQUEST['address']) ? $_REQUEST['address'] : array();

				if (is_array($addressData) && isset($addressData['comment'])) {
					$address->setValue('order_comments', $addressData['comment']);
					$status = 'updated';
					$address->commit();
				}
			}

			$this->setData(array('@status' => $status));
			$this->doData();
		}

		/**
		 * Получает selector заказа, по его статусу.
		 * @param int $statusId статус
		 * @return selector селектор
		 * @internal
		 */
		public function getOrderSelector($statusId) {
			$domainId = cmsController::getInstance()->getCurrentDomain()->getId();

			$orderSelector = new selector('objects');
			$orderSelector->types('object-type')->id('emarket-order');
			$orderSelector->where('status_id')->equals($statusId);
			$orderSelector->where('name')->notequals('dummy');
			$orderSelector->where('domain_id')->equals($domainId);

			return $orderSelector;
		}

		/**
		 * Формирует ФИО покупателя.
		 * @param customer $customer покупатель
		 * @return string "фамилия имя"
		 * @internal
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

		/**
		* Добавляет токен и тип платформы для push уведомлений
		* Возвращает error, если не был передан токен, не удалось связаться с сервером push сообщений, лицензия данной cms не прошла проверку.
		* Если все прошло нормально, возвратит ok и добавит данный токен и платформу в справочник мобильных устройств данной cms для текущего домена.
		* @api мобильное приложение
		* @example /admin/emarket/addToken/android/32feeeda75e98206f67c564bcbdc68e63a6cae9c347c2d054c4221c9159ac683/
		*/
		public function addToken() {
			$platform = (string) getRequest('param0');
			$token = (string) getRequest('param1');

			if (strlen($token) == 0 || strlen($platform) == 0 || !in_array($platform, array('android', 'ios'))) {
				$this->setData(array('@result' => 'error'));
				$this->doData();
				return;
			}

			// Идентификатор платформы в справочнике
			$selector = new selector("objects");
			$selector->types('object-type')->id('emarket-mobile-platform');
			$selector->where('platform_identificator')->equals($platform);
			$selector->limit(0, 1);

			if ($selector->length() != 1) {
				$this->setData(array('@result' => 'error'));
				$this->doData();
				return;
			}

			$objectPlatform = $selector->first;
			if (!$objectPlatform instanceOf umiObject) {
				$this->setData(array('@result' => 'error'));
				$this->doData();
				return;
			}

			$currentDomain = cmsController::getInstance()->getCurrentDomain();

			$selector = new selector("objects");
			$selector->types('object-type')->id('emarket-mobile-devices');
			$selector->where('domain_id')->equals($currentDomain->getId());
			$selector->where('token')->equals($token);
			$selector->where('platform')->equals($objectPlatform->getId());

			if ($selector->length() == 1) {
				$result = array(
					'@token' => $token,
					'@platform' => $platform,
					'@result' => 'ok'
				);
				$this->setData($result);
				$this->doData();
				return;
			}

			$registrySettings = Service::RegistrySettings();
			$request = array(
				'requestType' => 'addToken',
				'keycode' => $registrySettings->getLicense(),
				'build' => $registrySettings->getRevision(),
				'domain' => $currentDomain->getHost(),
				'token' => $token,
				'platform' => $platform
			);

			$response = umiRemoteFileGetter::get(PUSH_SERVER, false, false, $request, false, 'POST', 10);

			$xml = simplexml_load_string($response);
			if (!$xml) {
				$result = array(
					'@token' => $token,
					'@platform' => $platform,
					'@result' => 'error'
				);
				$this->setData($result);
				$this->doData();
				return;
			}

			if ($xml->attributes()->count > 0) {
				$tokens = array();
				foreach($xml->token as $node) {
					$tokens[] = (string) $node;
				}

				$selector = new selector('objects');
				$selector->types('object-type')->id('emarket-mobile-devices');
				$selector->where('domain_id')->equals($currentDomain->getId());
				$selector->where('token')->notequals($tokens);

				if ($selector->length()) {
					foreach ($selector->result() as $object) {
						$object->delete();
					}
				}
			}

			$objectTypeId = umiObjectTypesCollection::getInstance()->getTypeByGUID('emarket-mobile-devices')->getId();
			$objectId = umiObjectsCollection::getInstance()->addObject(getLabel('label-emarket-mobile-device'), $objectTypeId);
			$object = umiObjectsCollection::getInstance()->getObject($objectId);
			$object->setValue('domain_id', $currentDomain->getId());
			$object->setValue('token', $token);
			$object->setValue('platform', $objectPlatform->getId());
			$object->setValue('active', 1);
			$object->commit();

			$result = array(
				'@token' => $token,
				'@platform' => $platform,
				'@result' => 'ok'
			);
			$this->setData($result);
			$this->doData();
			return;
		}

		/**
		* Удаляет токен для push уведомлений из системы. Пытается удалить на push сервере.
		* Возвращает error, если токен передан не был, или отсутствовал в справочнике мобильных устройств для текущего домена.
		* В любом другом случае вернет ok, и удалит данный токен из справочника.
		* @api мобильное приложение
		* @example /admin/emarket/removeToken/32feeeda75e98206f67c564bcbdc68e63a6cae9c347c2d054c4221c9159ac683/
		*/
		public function removeToken() {
			$token = (string) getRequest('param0');

			if (strlen($token) == 0) {
				$this->setData(array('@result' => 'error'));
				$this->doData();
				return;
			}

			$currentDomain = cmsController::getInstance()->getCurrentDomain();

			$selector = new selector("objects");
			$selector->types('object-type')->id('emarket-mobile-devices');
			$selector->where('domain_id')->equals($currentDomain->getId());
			$selector->where('token')->equals($token);
			$selector->limit(0, 1);

			if ($selector->length() != 1) {
				$this->setData(array('@result' => 'error'));
				$this->doData();
				return;
			}

			$request = array(
				'requestType' => 'removeToken',
				'domain' => $currentDomain->getHost(),
				'token' => $token,
			);
			$response = umiRemoteFileGetter::get(PUSH_SERVER, false, false, $request, false, 'POST', 10);
			/** Поскольку планируется отправлять токены со стороны cms каждый раз, результат мы анализировать не будем. Если токен не был удален на сервере, все равно push на него идти не будет*/

			$object = $selector->first;
			umiObjectsCollection::getInstance()->delObject($object->getId());
			$this->setData(array('@result' => 'ok'));
			$this->doData();
		}
		
	}

?>