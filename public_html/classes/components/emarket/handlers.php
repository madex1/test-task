<?php

	use UmiCms\Service;

	/** Класс обработчиков событий */
	class EmarketHandlers {

		/** @var emarket|EmarketHandlers $module */
		public $module;

		/**
		 * Обработчик изменения значения поля заказа через быстрое редактирование
		 * в табличном контроле.
		 *
		 * Выполняет ряд действий:
		 *
		 * 1) Если изменился статус заказа, доставки или оплаты - иницирует отправку почтовых
		 * и push уведомлений.
		 * 2) Если изменился статус заказа, доставки или оплаты - запускает соответствующие обработчики
		 * 3) Если изменился способ доставки или способ оплаты - запускает соответствующие обработчики
		 *
		 * @param iUmiEventPoint $event событие изменения поля сущности через быстрое редактирование
		 * @return bool
		 */
		public function onModifyProperty(iUmiEventPoint $event) {
			/** @var iUmiObject $entity */
			$entity = $event->getRef('entity');

			if (!$entity instanceof iUmiObject) {
				return false;
			}

			$allowedStatus = [
				'status_id',
				'payment_status_id',
				'delivery_status_id'
			];

			$allowedProperties = [
				'delivery_id',
				'payment_id'
			];

			$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('emarket', 'order');
			$isOrder = ($entity->getTypeId() == $typeId);
			$isStatus = in_array($event->getParam('property'), $allowedStatus);
			$isAllowed = in_array($event->getParam('property'), $allowedProperties);
			$newValue = $event->getParam('newValue');
			$oldValue = $event->getParam('oldValue');
			$isChanged = ($newValue != $oldValue);
			$isEmpty = empty($newValue);

			if (!$isOrder || !($isStatus || $isAllowed) || !$isChanged) {
				return false;
			}

			if ($isStatus) {
				$this->statusChangeHandler(
					$event->getParam('property'),
					order::get($entity->getId()),
					$event->getParam('newValue'),
					$event->getParam('oldValue')
				);
			}

			if ($isAllowed && !$isEmpty) {
				$this->propertyChangeHandler(
					$event->getParam('property'),
					order::get($entity->getId()),
					$event->getParam('newValue'),
					$event->getParam('oldValue')
				);
			}
		}

		/**
		 * Обработчик события изменения заказа через форму редактирования
		 * в административной панели.
		 *
		 * Выполняет ряд действий:
		 *
		 * 1) Если изменился статус заказа, доставки или оплаты - иницирует отправку почтовых
		 * и push уведомлений.
		 * 2) Если изменился статус заказа, доставки или оплаты - запускает соответствующие обработчики
		 *
		 * @param iUmiEventPoint $event событие изменения заказа
		 * @return bool
		 */
		public function onModifyObject(iUmiEventPoint $event) {
			static $modifiedCache = [];
			static $orderStatus = [];
			static $deliveryStatus = [];
			static $paymentStatus = [];

			/** @var iUmiObject $object */
			$object = $event->getRef('object');
			$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('emarket', 'order');

			if ($object->getTypeId() != $typeId) {
				return false;
			}

			if ($event->getMode() == 'before') {
				$data = getRequest('data');
				$id = $object->getId();
				$newOrderStatus = getArrayKey($data[$id], 'status_id');
				$newPaymentStatus = getArrayKey($data[$id], 'payment_status_id');
				$newDeliveryStatus = getArrayKey($data[$id], 'delivery_status_id');

				switch (true) {
					case ($newOrderStatus != $object->getValue('status_id')) : {
						$modifiedCache[$object->getId()] = 'status_id';
						$orderStatus[$object->getId()]['old_status_id'] = $object->getValue('status_id');
						break;
					}
					case ($newDeliveryStatus != $object->getValue('delivery_status_id')) : {
						$modifiedCache[$object->getId()] = 'delivery_status_id';
						$deliveryStatus[$object->getId()]['old_status_id'] = $object->getValue('delivery_status_id');
						break;
					}
					case ($newPaymentStatus != $object->getValue('payment_status_id')) : {
						$modifiedCache[$object->getId()] = 'payment_status_id';
						$paymentStatus[$object->getId()]['old_status_id'] = $object->getValue('payment_status_id');
						break;
					}
					default : {
						return false;
					}
				}
			}

			if ($event->getMode() == 'after' && isset($modifiedCache[$object->getId()])) {
				switch ($modifiedCache[$object->getId()]) {
					case 'status_id' : {
						$this->orderStatusChangeHandler(
							order::get($object->getId()),
							$object->getValue('status_id'),
							$orderStatus[$object->getId()]['old_status_id']
						);
						break;
					}
					case 'payment_status_id' : {
						$this->paymentStatusChangeHandler(
							order::get($object->getId()),
							$object->getValue('payment_status_id'),
							$paymentStatus[$object->getId()]['old_status_id']
						);
						break;
					}
					case 'delivery_status_id' : {
						$this->deliveryStatusChangeHandler(
							order::get($object->getId()),
							$object->getValue('delivery_status_id'),
							$deliveryStatus[$object->getId()]['old_status_id']
						);
						break;
					}
					default : {
						return false;
					}
				}

				/** @var emarket|EmarketNotification $module */
				$module = $this->module;
				$module->notifyOrderStatusChange(order::get($object->getId()), $modifiedCache[$object->getId()]);
			}

			return true;
		}

		/**
		 * Обработчик события изменения статуса заказа.
		 *
		 * Выполняет ряд действий:
		 *
		 * 1) Если изменился статус заказа - иницирует отправку почтовых и push уведомлений.
		 * 2) Если статус заказа изменился - запускает обработчик изменения статуса заказа.
		 *
		 * @param iUmiEventPoint $event событие изменения заказа
		 * @return bool
		 */
		public function onStatusChanged(iUmiEventPoint $event) {
			if ($event->getMode() != 'after') {
				return false;
			}

			if ($event->getParam('old-status-id') == $event->getParam('new-status-id')) {
				return false;
			}

			/** @var order $order */
			$order = $event->getRef('order');
			$this->orderStatusChangeHandler(
				$order,
				$event->getParam('new-status-id'),
				$event->getParam('old-status-id')
			);

			/** @var emarket|EmarketNotification $module */
			$module = $this->module;
			$module->notifyOrderStatusChange($order, 'status_id');

			return true;
		}

		/**
		 * Обработчик события изменения статуса оплаты.
		 *
		 * Выполняет ряд действий:
		 *
		 * 1) Если изменился статус оплаты - иницирует отправку почтовых и push уведомлений.
		 * 2) Если оплата перешла в статус "Принят" - инициирует начисление бонусов.
		 *
		 * @param iUmiEventPoint $event событие изменения статуса оплаты.
		 * @return bool
		 */
		public function onPaymentStatusChanged(iUmiEventPoint $event) {
			if ($event->getMode() != 'after') {
				return false;
			}

			if ($event->getParam('old-status-id') == $event->getParam('new-status-id')) {
				return false;
			}

			/** @var order $order */
			$order = $event->getRef('order');
			$this->paymentStatusChangeHandler(
				$order,
				$event->getParam('new-status-id'),
				$event->getParam('old-status-id')
			);

			/** @var emarket|EmarketNotification $module */
			$module = $this->module;
			$module->notifyOrderStatusChange($order, 'payment_status_id');

			return true;
		}

		/**
		 * Обработчик события изменения статуса доставки.
		 * Если изменился статус доставки - иницирует отправку почтовых и push уведомлений.
		 * @param iUmiEventPoint $event событие изменения статуса доставки.
		 * @return bool
		 */
		public function onDeliveryStatusChanged(iUmiEventPoint $event) {
			if ($event->getMode() != 'after') {
				return false;
			}

			if ($event->getParam('old-status-id') == $event->getParam('new-status-id')) {
				return false;
			}

			/** @var order $order */
			$order = $event->getRef('order');
			$this->deliveryStatusChangeHandler(
				$order,
				$event->getParam('new-status-id'),
				$event->getParam('old-status-id')
			);

			/** @var emarket|EmarketNotification $module */
			$module = $this->module;
			$module->notifyOrderStatusChange($order, 'delivery_status_id');

			return true;
		}

		/**
		 * Обработчик изменения значение поля заказа через быстрое редактирование
		 * в табличном контроле.
		 * Следит за тем, чтобы только у одного склада стоял флаг "Основной".
		 * @param iUmiEventPoint $event событие изменения поля сущности через быстрое редактирование
		 * @return bool
		 */
		public function onStorePropChange(iUmiEventPoint $event) {
			if ($event->getMode() != 'after') {
				return false;
			}

			$propName = $event->getParam('property');
			$value = $event->getParam('newValue');
			/** @var iUmiObject $entity */
			$entity = $event->getRef('entity');

			if ($entity instanceof iUmiObject && $propName == 'primary' && $value == 1) {
				$this->module->clearPrimary($entity->getId());
			}

			return true;
		}

		/**
		 * Обработчик удаления объекта через административную панель.
		 * Удаляет объекты, связанные с заказом:
		 *
		 * 1) Товарные наименования заказа;
		 * 2) Незарегистрированного покупателя заказа, если он был "просрочен";
		 * 3) Покупателя заказа "В 1 клик";
		 *
		 * @param iUmiEventPoint $event событие удаления объекта
		 * @return bool
		 */
		public function onOrderDeleteCleanRelations(iUmiEventPoint $event) {
			if ($event->getMode() != 'before') {
				return false;
			}

			/** @var iUmiObject $object */
			$object = $event->getRef('object');

			if (!$object instanceof iUmiObject) {
				return false;
			}

			/** @var iUmiObjectType $type */
			$type = selector::get('object-type')->id($object->getTypeId());

			if (!$type instanceof iUmiObjectType || $type->getMethod() != 'order') {
				return false;
			}

			$order = order::get($object->getId());
			$orderItems = $order->getItems();

			if (umiCount($orderItems) > 0) {
				/** @var orderItem $item */
				foreach ($orderItems as $item) {
					$orderItem = orderItem::get($item->getId());
					$orderItem->remove();
				}
			}

			$customerId = $order->getCustomerId();
			/** @var umiObjectsExpiration $umiObjectsExpiration */
			$umiObjectsExpiration = umiObjectsExpiration::getInstance();

			if ($customerId !== null) {
				/** @var iUmiObject $customer */
				$customer = selector::get('object')->id($customerId);

				if ($customer->getMethod() == 'customer') {
					$umiObjectsExpiration->add($customerId);
				}
			}
			$customerOneClickId = $order->getValue('purchaser_one_click');

			if ($customerOneClickId !== null) {
				$customerOneClick = selector::get('object')->id($customerOneClickId);

				if ($customerOneClick instanceof iUmiObject &&
					$customerOneClick->getTypeGUID() == 'emarket-purchase-oneclick') {
					$umiObjectsExpiration->add($customerOneClickId);
				}
			}

			$order->commit();

			return true;
		}

		/**
		 * Обработчик события изменения заказа через форму редактирования
		 * в административной панели.
		 *
		 * Выполняет ряд действий:
		 *
		 * 1) Если изменился адрес доставки - обновляет адрес доставки связанного заказа в Яндекc.Маркет;
		 * 2) Если изменился статус заказа или статус доставки - обновляет статус связанного заказа в Яндекc.Маркет;
		 * 3) Если изменился способ доставки - меняет способ доставки связанного заказа в Яндекc.Маркет;
		 * 4) Если изменилась стоимость доставки - меняет стоимость доставки связанного заказа в Яндекc.Маркет;
		 * 5) Отключает возможность сохранить изменения состава заказа;
		 *
		 * @param iUmiEventPoint $event событие изменения заказа
		 * @throws coreException
		 * @throws selectorException
		 * @return bool
		 */
		public function changedOrder(iUmiEventPoint $event) {
			static $modifiedCache = [];
			/** @var EmarketYandexMarketClient $yandexMarketClient */
			$yandexMarketClient = $this->module->getImplementedInstance('EmarketYandexMarketClient');
			/** @var iUmiObject $object */
			$object = $event->getRef('object');

			$umiObjectTypesCollection = umiObjectTypesCollection::getInstance();
			$deliveryAddressTypeId = $umiObjectTypesCollection->getTypeIdByGUID('emarket-deliveryaddress');

			if ($object->getTypeId() == $deliveryAddressTypeId && $event->getMode() == 'after') {
				$data = getRequest('data');
				$id = $object->getId();

				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('delivery_address')->equals($object->getId());
				$sel->option('no-length')->value(true);
				$orders = $sel->result();

				if (!isset($orders[0])) {
					return false;
				}

				$order = $orders[0];

				if (!$order instanceof iUmiObject) {
					return false;
				}

				$orderId = $order->getValue('yandex_order_id');

				if (!$orderId) {
					return false;
				}

				$yandexMarketClient->_updateDeliveryAddress($orderId, $data[$id], $order);
			}

			$orderTypeId = $umiObjectTypesCollection->getTypeIdByGUID('emarket-order');

			if ($object->getTypeId() == $orderTypeId && $orderId = $object->getValue('yandex_order_id')) {

				if (getRequest('order-amount-item') || getRequest('order-del-item')) {
					unset($_REQUEST['order-amount-item']);
					unset($_REQUEST['order-del-item']);
				}

				$typeId = $umiObjectTypesCollection->getTypeIdByHierarchyTypeName('emarket', 'order');

				if ($object->getTypeId() != $typeId) {
					return false;
				}

				if ($event->getMode() == 'before') {
					$data = getRequest('data');
					$id = $object->getId();
					$newOrderStatus = getArrayKey($data[$id], 'status_id');
					$newDeliveryStatus = getArrayKey($data[$id], 'delivery_status_id');
					$newDeliveryPrice = getArrayKey($data[$id], 'delivery_price');
					$newDeliveryService = getArrayKey($data[$id], 'delivery_id');

					switch (true) {
						case ($newOrderStatus != $object->getValue('status_id')) : {
							$modifiedCache[$object->getId()] = [
								'name' => 'status_id',
								'oldValue' => $object->getValue('status_id'),
								'newValue' => $newOrderStatus
							];
							break;
						}
						case ($newDeliveryStatus != $object->getValue('delivery_status_id')) : {
							$modifiedCache[$object->getId()] = [
								'name' => 'delivery_status_id',
								'oldValue' => $object->getValue('delivery_status_id'),
								'newValue' => $newDeliveryStatus
							];
							break;
						}
						case ($newDeliveryPrice != $object->getValue('delivery_price')) : {
							$modifiedCache[$object->getId()] = [
								'name' => 'delivery_price',
								'oldValue' => $object->getValue('delivery_price'),
								'newValue' => $newDeliveryPrice
							];
							break;
						}
						case ($newDeliveryService != $object->getValue('delivery_id')) : {
							$modifiedCache[$object->getId()] = [
								'name' => 'delivery_id',
								'oldValue' => $object->getValue('delivery_id'),
								'newValue' => $newDeliveryService
							];
							break;
						}
					}
				} else {
					if (isset($modifiedCache[$object->getId()])) {

						if ($modifiedCache[$object->getId()]['name'] === 'status_id') {

							$status = order::getCodeByStatus($object->getValue('status_id'));

							try {
								$yandexMarketClient->_sendStatusOnChangeOrderStatus($orderId, $status, $object);
							} catch (Exception $e) {
								$object->setValue('status_id', $modifiedCache[$object->getId()]['oldValue']);
							}
						} elseif ($modifiedCache[$object->getId()]['name'] === 'delivery_status_id') {

							$status = order::getCodeByStatus($object->getValue('delivery_status_id'));

							try {
								$yandexMarketClient->_sendStatusOnChangeOrderStatus($orderId, $status, $object);
							} catch (Exception $e) {
								$object->setValue('delivery_status_id', $modifiedCache[$object->getId()]['oldValue']);
							}
						} elseif ($modifiedCache[$object->getId()]['name'] === 'delivery_id') {

							$objects = umiObjectsCollection::getInstance();
							$delivery = $objects->getObject($object->getValue('delivery_id'));
							$marketOrder = $yandexMarketClient->getYandexMarketOrderById($object);
							$currency = $marketOrder['order']['currency'];

							$sel = new selector('objects');
							$sel->types('object-type')->name('emarket', 'currency');
							$sel->where('codename')->equals($currency);
							$sel->option('load-all-props')->value(true);
							$sel->option('no-length')->value(true);
							$currencyList = $sel->result();

							/** @var delivery $deliveryTypeObject */
							$deliveryTypeObject = delivery::get($delivery->getId());
							$deliveryPrice = (float) $deliveryTypeObject->getDeliveryPrice(order::get($object->getId()));

							$recipient = '';

							if (isset($marketOrder['order']['delivery']['address']['recipient'])) {
								$recipient = $marketOrder['order']['delivery']['address']['recipient'];
							}
							$phone = '';

							if (isset($marketOrder['order']['delivery']['address']['phone'])) {
								$phone = $marketOrder['order']['delivery']['address']['phone'];
							}
							$postcode = '';

							if (isset($marketOrder['order']['delivery']['address']['postcode'])) {
								$postcode = $marketOrder['order']['delivery']['address']['postcode'];
							}

							$apartment = '';

							if (isset($marketOrder['order']['delivery']['address']['apartment'])) {
								$apartment = $marketOrder['order']['delivery']['address']['apartment'];
							}

							$data = [
								'delivery' => [
									'id' => (string) $delivery->getId(),
									'price' => round($deliveryPrice / $currencyList[0]->rate),
									'serviceName' => $delivery->getName(),
									'address' => [
										'country' => $marketOrder['order']['delivery']['address']['country'],
										'postcode' => $postcode,
										'city' => $marketOrder['order']['delivery']['address']['city'],
										'street' => $marketOrder['order']['delivery']['address']['street'],
										'house' => $marketOrder['order']['delivery']['address']['house'],
										'apartment' => $apartment,
										'recipient' => $recipient,
										'phone' => $phone
									]
								]
							];

							if ($deliveryTypeObject instanceof selfDelivery) {
								$data['delivery']['type'] = 'PICKUP';
								$data['delivery']['outlets'] = [
									['id' => 1]
								];
								return false;
							}

							if ($deliveryTypeObject instanceof courierDelivery) {
								$data['delivery']['type'] = 'DELIVERY';
							} else {
								$data['delivery']['type'] = 'POST';
							}

							$yandexMarketClient->updateYandexMarketDelivery($object, $data);
						} elseif ($modifiedCache[$object->getId()]['name'] === 'delivery_price') {
							$price = $object->getValue('delivery_price');
							$marketOrder = $yandexMarketClient->getYandexMarketOrderById($object);
							$currency = $marketOrder['order']['currency'];

							$sel = new selector('objects');
							$sel->types('object-type')->name('emarket', 'currency');
							$sel->where('codename')->equals($currency);
							$sel->option('load-all-props')->value(true);
							$sel->option('no-length')->value(true);
							$currencyList = $sel->result();

							$data = [
								'delivery' => [
									'price' => round($price / $currencyList[0]->rate)
								]
							];

							$yandexMarketClient->updateYandexMarketDelivery($object, $data);
						}
					}
				}
			}

			return true;
		}

		/**
		 * Обработчик изменения значение поля заказа через быстрое редактирование
		 * в табличном контроле.
		 *
		 * Выполняет ряд действий:
		 *
		 * 1) Если изменился статус заказа или доставки - инициирует обновления заказа в Яндекс.Маркет.
		 * 2) Если заказ в UMI.CMS связан с заказом в Яндекс.Маркет - запрещает менять стоимость заказа.
		 *
		 * @param iUmiEventPoint $event событие изменения поля сущности через быстрое редактирование
		 * @throws Exception
		 * @throws coreException
		 * @return bool
		 */
		public function changedOrderEntity(iUmiEventPoint $event) {
			/** @var EmarketYandexMarketClient $yandexMarketClient */
			$yandexMarketClient = $this->module->getImplementedInstance('EmarketYandexMarketClient');
			$entity = $event->getRef('entity');

			if (!$entity instanceof iUmiObject) {
				return false;
			}

			$allowedProperties = [
				'status_id',
				'delivery_status_id'
			];

			$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('emarket', 'order');

			if (
				($entity->getTypeId() == $typeId) &&
				in_array($event->getParam('property'), $allowedProperties) &&
				($event->getParam('newValue') != $event->getParam('oldValue'))
			) {
				$orderId = $entity->getValue('yandex_order_id');

				if (!$orderId) {
					return false;
				}

				if ($event->getParam('property') == 'status_id') {
					$status = order::getCodeByStatus($event->getParam('newValue'));
					$yandexMarketClient->_sendStatusOnChangeOrderStatus($orderId, $status, $entity);
				} elseif ($event->getParam('property') == 'delivery_status_id') {
					$status = order::getCodeByStatus($event->getParam('newValue'));
					$yandexMarketClient->_sendStatusOnChangeOrderStatus($orderId, $status, $entity);
				}
			} else {
				if ($event->getParam('property') == 'total_price') {
					$orderId = $entity->getValue('yandex_order_id');

					if (!$orderId) {
						return false;
					}

					throw new coreException(getLabel('error-cannot-change-price-for-order-from-yandex-market'));
				}
			}

			return true;
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
			return $this->module->updateCurrenciesHandler();
		}

		/**
		 * Обновляет курсы валют.
		 * @return bool
		 * @throws Exception
		 * @throws umiRemoteFileGetterException
		 */
		public function updateCurrenciesHandler() {
			if (!Service::Registry()->get('//modules/emarket/enable-currency')) {
				return false;
			}

			$currencyInfoPath = SYS_CACHE_RUNTIME . 'tmpcurrencies.xml';
			$this->module->onCronLoadCurrencyInfo($currencyInfoPath);

			try {
				$currencyList = new baseXmlConfig($currencyInfoPath);
				$currencyList = $currencyList->getList('/Exchange/Exchange_Rates', [
					'code' => '/New_Country',
					'rate' => '/Rate',
					'nominal' => '/Nominal'
				]);
			} catch (Exception $e) {
				return false;
			}

			foreach ($currencyList as $currencyInfo) {
				$this->module->onCronUpdateCurrency($currencyInfo);
			}

			unlink($currencyInfoPath);
			return true;
		}

		/**
		 * Загружает из удаленного ресурса актуальную информацию о валютах
		 * @param string $filePath Путь до файла, в который нужно загрузить информацию
		 */
		public function onCronLoadCurrencyInfo($filePath) {
			if (is_file($filePath)) {
				unlink($filePath);
			}

			$config = mainConfiguration::getInstance();
			$sourceUrl = $config->get('modules', 'emarket.currency.sync.source');
			$xslPath = CURRENT_WORKING_DIR . '/xsl/currencies/' . $config->get('modules', 'emarket.currency.sync.xsl');

			$originalXml = umiRemoteFileGetter::get($sourceUrl);

			if (function_exists('mb_detect_encoding') &&
				(mb_detect_encoding($originalXml, 'UTF-8, ISO-8859-1, GBK, CP1251') != 'UTF-8')) {
				$originalXml = iconv('CP1251', 'UTF-8', $originalXml);
				$originalXml = preg_replace('/(encoding="windows-1251")/i', 'encoding="UTF-8"', $originalXml);
			}

			$xslt = new XSLTProcessor();
			$dom = null;
			secure_load_dom_document($originalXml, $dom);
			$styleSheet = new DomDocument();
			$styleSheet->load($xslPath);

			$xslt->importStylesheet($styleSheet);

			$resultXml = $xslt->transformToXml($dom);
			file_put_contents($filePath, $resultXml);
		}

		/**
		 * Обновляет валюту
		 * @param array $currencyInfo Новые параметры валюты
		 */
		public function onCronUpdateCurrency($currencyInfo) {
			$currencyFacade = $this->module->getCurrencyFacade();
			$code = getArrayKey($currencyInfo, 'code');

			try {
				$currency = $currencyFacade->getByCode($code);
			} catch (privateException $e) {
				return;
			}

			$event = Service::EventPointFactory()->create('systemSyncCurrency', 'before');
			$event->addRef('currency', $currency)
				->addRef('currencyInfo', $currencyInfo)
				->call();
			
			$rate = str_replace(',', '.', getArrayKey($currencyInfo, 'rate'));
			$currency->setDenomination(getArrayKey($currencyInfo, 'nominal'));
			$currency->setRate($rate);
			$currencyFacade->save($currency);

			$event->setMode('after')->call();
		}

		/**
		 * Обработчик срабатывания системного крона.
		 * Удаляет 50 незарегистрированных покупателей у которых закончилось время жизни и
		 * нет заказов.
		 * Если у покупателей были адреса - они удаляются.
		 * Если у покупателя были заказы, то покупатель удаляет из системы учета
		 * времени жизни.
		 * Если у покупателей были незавершенные заказы - им устанавливается время жизни.
		 * @param iUmiEventPoint $event событие срабатывания системного крона.
		 * @return bool
		 * @throws coreException
		 * @throws selectorException
		 */
		public function onCronCheckExpiredCustomers(iUmiEventPoint $event) {
			$customerTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('emarket-customer');
			/** @var umiObjectsExpiration $expiration */
			$expiration = umiObjectsExpiration::getInstance();
			$customers = $expiration->getExpiredObjectsByTypeId($customerTypeId, $expiration->getLimit());

			if (umiCount($customers) == 0) {
				return false;
			}

			$objects = umiObjectsCollection::getInstance();

			foreach ($customers as $customerId) {
				$selector = new selector('objects');
				$selector->types('object-type')->name('emarket', 'order');
				$selector->where('customer_id')->equals($customerId);
				$selector->option('no-length')->value(true);
				$customer = new customer($objects->getObject($customerId));

				if ($selector->first()) {
					$customer->freeze();

					/** @var iUmiObject $order */
					foreach ($selector->result() as $order) {
						if ($order->getValue('status_id') === null) {
							if (!$expiration->isExpirationExists($order->getId())) {
								$expiration->add($order->getId());
							}
						}
					}
				} else {
					$deliveryAddresses = $customer->getValue('delivery_addresses');

					if ($deliveryAddresses !== null && is_array($deliveryAddresses) && umiCount($deliveryAddresses) > 0) {
						foreach ($deliveryAddresses as $addressId) {
							$objects->delObject($addressId);
						}
					}

					$customer->delete();
				}
			}

			return true;
		}

		/**
		 * Обработчик срабатывания системного крона.
		 * Удаляет 50 незавершенных заказов у которых закончилось время жизни.
		 * Если в заказе были товарные предложения - они удаляются.
		 * Если у заказа был покупатель - ему устанавливается время жизни.
		 * @param iUmiEventPoint $event событие срабатывания системного крона.
		 * @return bool
		 * @throws coreException
		 */
		public function onCronCheckExpiredOrders(iUmiEventPoint $event) {
			$orderTypeId = umiObjectTypesCollection::getInstance()
				->getTypeIdByGUID('emarket-order');
			$expiration = umiObjectsExpiration::getInstance();
			/** @var umiObjectsExpiration $expiration */
			$orders = $expiration->getExpiredObjectsByTypeId($orderTypeId, $expiration->getLimit());

			if (umiCount($orders) == 0) {
				return false;
			}

			$objects = umiObjectsCollection::getInstance();

			foreach ($orders as $orderId) {
				/** @var iUmiObject $order */
				$order = $objects->getObject($orderId);

				if ($order->getValue('status_id') === null) {
					/** @var order $order */
					$order = order::get($orderId);
					$items = $order->getItems();
					/** @var orderItem $item */
					foreach ($items as $item) {
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

			return true;
		}

		/**
		 * Обработчик срабатывания системного крона.
		 * Удаляет 50 покупателей "В 1 клик" у которых закончилось время жизни и
		 * не было заказов.
		 * Если у покупателя были заказы, то покупатель удаляет из системы учета
		 * времени жизни.
		 * Если у покупателя были незавершенные заказы, то им назначается время жизни.
		 * @param iUmiEventPoint $event событие срабатывания системного крона.
		 * @return bool
		 * @throws coreException
		 * @throws selectorException
		 */
		public function onCronCheckExpiredCustomersOneClick(iUmiEventPoint $event) {
			$customerOneClickTypeId = umiObjectTypesCollection::getInstance()
				->getTypeIdByGUID('emarket-purchase-oneclick');
			$expiration = umiObjectsExpiration::getInstance();
			/** @var umiObjectsExpiration $expiration */
			$customers = $expiration->getExpiredObjectsByTypeId($customerOneClickTypeId, $expiration->getLimit());

			if (umiCount($customers) == 0) {
				return false;
			}

			foreach ($customers as $customerId) {
				$selector = new selector('objects');
				$selector->types('object-type')->name('emarket', 'order');
				$selector->where('purchaser_one_click')->equals($customerId);
				$selector->option('no-length')->value(true);

				if ($selector->first()) {
					$expiration->clear($customerId);

					/** @var iUmiObject $order */
					foreach ($selector->result() as $order) {
						if ($order->getValue('status_id') === null) {
							if (!$expiration->isExpirationExists($order->getId())) {
								$expiration->add($order->getId());
							}
						}
					}
				} else {
					$objects = umiObjectsCollection::getInstance();
					$objects->delObject($customerId);
				}
			}

			return true;
		}

		/**
		 * Обработчик изменений статусов в заказе
		 * 1) Если изменился статус заказа, доставки или оплаты - иницирует отправку почтовых
		 * и push уведомлений.
		 * 2) Если изменился статус заказа, доставки или оплаты - запускает соответствующие обработчики
		 * @param string $property имя поля
		 * @param order $order заказ
		 * @param int $newStatusId идентификатор нового статуса
		 * @param int $oldStatusId идентификатор старого статуса
		 * @return bool
		 */
		private function statusChangeHandler($property, order $order, $newStatusId, $oldStatusId) {
			switch ($property) {
				case 'status_id' : {
					$this->orderStatusChangeHandler(
						$order,
						$newStatusId,
						$oldStatusId
					);
					break;
				}
				case 'payment_status_id' : {
					$this->paymentStatusChangeHandler(
						$order,
						$newStatusId,
						$oldStatusId
					);
					break;
				}
				case 'delivery_status_id' : {
					$this->deliveryStatusChangeHandler(
						$order,
						$newStatusId,
						$oldStatusId
					);
					break;
				}

				default : {
					return false;
				}
			}

			/** @var emarket|EmarketNotification $module */
			$module = $this->module;
			$module->notifyOrderStatusChange($order, $property);
		}

		/**
		 * Обработчик изменений полей в заказе
		 * 1) Если изменилось поле способ оплаты или способ доставки запускает соответствующие обработчики
		 * @param $property поле заказа
		 * @param order $order заказ
		 * @param $newValue новое значение поля
		 * @param $oldValue старое значение поля
		 * @throws coreException
		 * @throws publicException
		 * @return bool
		 */
		private function propertyChangeHandler($property, order $order, $newValue, $oldValue) {
			switch ($property) {
				case 'delivery_id' : {
					$this->deliveryIdChangeHandler(
						$order,
						$newValue
					);
					break;
				}

				case 'payment_id' : {
					$this->paymentIdChangeHandler(
						$order,
						$newValue
					);
					break;
				}

				default : {
					return false;
				}
			}
		}

		/**
		 * Обработчик изменения статуса заказа
		 * 1) Если заказ перешел в статус "Готов" - добавляет заказ в статистику
		 * 2) Если заказ перешел в статус "Отменен" - инициирует возврат бонусов и запускает обработчик доставки
		 * 3) Если заказ перешел в статус "Отклонен" - инициирует возврат бонусов и запускает обработчик доставки
		 * 4) Если заказ вышел из статуса "Готов" - убирает заказ из статистики
		 * @param order $order заказ
		 * @param int $newStatusId идентификатор нового статуса
		 * @param int $oldStatusId идентификатор старого статуса
		 */
		private function orderStatusChangeHandler(order $order, $newStatusId, $oldStatusId) {
			$stat = new emarketTop();

			switch ($newStatusId) {
				case order::getStatusByCode('ready') : {
					$stat->addOrder($order);
					break;
				}
				case order::getStatusByCode('canceled') : {
					bonusDiscount::returnBonus($order->getId());
					break;
				}
				case order::getStatusByCode('rejected') : {
					bonusDiscount::returnBonus($order->getId());
					break;
				}
				case order::getStatusByCode('waiting') :
				case order::getStatusByCode('delivery') :
				case order::getStatusByCode('editing') :
				case order::getStatusByCode('payment') :
				case order::getStatusByCode('accepted') : {
					break;
				}
			}

			switch ($oldStatusId) {
				case order::getStatusByCode('ready') : {
					$stat->delOrder($order);
					break;
				}
				case order::getStatusByCode('canceled') :
				case order::getStatusByCode('rejected') :
				case order::getStatusByCode('waiting') :
				case order::getStatusByCode('delivery') :
				case order::getStatusByCode('editing') :
				case order::getStatusByCode('payment') :
				case order::getStatusByCode('accepted') : {
					break;
				}
			}
		}

		/**
		 * Обработчик изменения статуса оплаты заказа
		 * 1) Если оплата заказа перешла в статус "Принята" - инициирует добавление бонусов
		 * @param order $order заказ
		 * @param int $newStatusId идентификатор нового статуса оплаты
		 * @param int $oldStatusId идентификатор старого статуса оплаты
		 */
		private function paymentStatusChangeHandler(order $order, $newStatusId, $oldStatusId) {
			$type = 'order_payment_status';

			switch ($newStatusId) {
				case order::getStatusByCode('accepted', $type) : {
					bonusDiscount::addBonus($order->getId());
					break;
				}
				case order::getStatusByCode('initialized', $type) :
				case order::getStatusByCode('declined', $type) :
				case order::getStatusByCode('validated', $type) :
				case order::getStatusByCode('not_defined', $type) : {
					break;
				}
			}

			switch ($oldStatusId) {
				case order::getStatusByCode('accepted', $type) :
				case order::getStatusByCode('initialized', $type) :
				case order::getStatusByCode('declined', $type) :
				case order::getStatusByCode('validated', $type) :
				case order::getStatusByCode('not_defined', $type) : {
					break;
				}
			}
		}

		/**
		 * Обработчик изменения статуса доставки заказа
		 * 1) Если доставка заказа перешла в статус "Ожидает отгрузки" - запускает обработчик доставки
		 * @param order $order заказ
		 * @param int $newStatusId идентификатор нового статуса доставки
		 * @param int $oldStatusId идентификатор старого статуса доставки
		 */
		private function deliveryStatusChangeHandler(order $order, $newStatusId, $oldStatusId) {
			$type = 'order_delivery_status';

			switch ($newStatusId) {
				case order::getStatusByCode('waiting_shipping', $type) :
				case order::getStatusByCode('shipping', $type) :
				case order::getStatusByCode('canceled', $type) :
				case order::getStatusByCode('not_defined', $type) :
				case order::getStatusByCode('return', $type) :
				case order::getStatusByCode('ready', $type) : {
					break;
				}
			}

			switch ($oldStatusId) {
				case order::getStatusByCode('waiting_shipping', $type) :
				case order::getStatusByCode('shipping', $type) :
				case order::getStatusByCode('canceled', $type) :
				case order::getStatusByCode('not_defined', $type) :
				case order::getStatusByCode('return', $type) :
				case order::getStatusByCode('ready', $type) : {
					break;
				}
			}
		}

		/**
		 * Обновляет поля способ доставки, стоимость доставки и название доставки
		 * @param order $order идентификатор заказа
		 * @param int $deliveryId идентификатор доставки
		 * @throws coreException
		 * @throws publicException
		 */
		private function deliveryIdChangeHandler($order, $deliveryId) {
			$delivery = delivery::get($deliveryId);
			$order->setDelivery($delivery);
		}

		/**
		 * Обновляет поля способ оплаты и название спсоба оплаты
		 * @param order $order идентификатор заказа
		 * @param int $paymentId идентификатор способа оплаты
		 * @throws coreException
		 * @throws publicException
		 */
		private function paymentIdChangeHandler($order, $paymentId) {
			$payment = payment::get($paymentId, $order);
			$order->setPayment($payment);
		}
	}
