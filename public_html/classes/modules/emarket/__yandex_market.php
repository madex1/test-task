<?php

	use Yandex\Market\MarketClient;

	abstract class __emarket_yandex_market {

	/** Точка входа яндекс.маркет */
	public function yandex_market() {
		$method = getRequest("param0");
		switch($method) {
			case 'cart': {
				$this->yandexCart();
			}
			case 'order': {
				$this->yandexOrder();
			}
		}
		$this->_sendForbidden('Incorrect command');
	}

	/**
	 * @param string $template
	 * @link http://api.yandex.ru/market/partner/doc/dg/reference/post-cart.xml
	*/
	public function yandexCart($template = "default") {
		$authorized = getRequest('umi_authorization');

		if (strlen($authorized) === 0) {
			$this->_sendForbidden('Auth param expected');
			exit();
		}

		$domainId = cmsController::getInstance()->getCurrentDomain()->getId();
		$settings = $this->getYandexMarketSettings($domainId);

		if (!$settings || $authorized !== $settings['marketToken']) {
			$this->_sendForbidden('Not authorized request');
			exit();
		}

		//POST /yandex_market/cart/
		if (strtolower(getServer('REQUEST_METHOD')) == 'post') {
			//Запрашивает у магазина информацию о товарах в корзине.
			$postData = file_get_contents("php://input");
			$request = json_decode($postData);
			$currency = $request->cart->currency;
			$items = array();

			foreach ($request->cart->items as $item) {
				$items[] = $this->_isValidItemFromPostCart( //@todo
					$item->feedId,
					$item->offerId,
					$item->feedCategoryId,
					$item->count,
					$currency
				);
			}

			$paymentMethods = $this->_getPaymentMethods($settings); //@todo

			$deliveryOptions = $this->_getDeliveryOptions($currency); //@todo

			$response = array(
				"cart" => array(
					"items" => $items,
					"deliveryOptions" => $deliveryOptions,
					"paymentMethods" => $paymentMethods
				)
			);

			$this->_outputJson($response);
		}

		$this->_sendBadRequest();
	}

	/**
	* @param int $feedId Идентификатор прайс-листа, в котором указан товар
	* @param int $itemId Идентификатор товара из прайс-листа.
	* @param int $categoryId Идентификатор товарной категории из прайс-листа.
	* @param int $count Количество товара, находящегося в корзине.
	* @param string $currency
	* @return array
	*/
	public function _isValidItemFromPostCart($feedId, $itemId, $categoryId, $count, $currency) {
		$price = umiHierarchy::getInstance()->getElement($itemId)->getValue('price');

		//Get currency
		$sel = new selector('objects');
		$sel->types('object-type')->name('emarket', 'currency');
		$sel->where('codename')->equals($currency);
		$sel->option('no-length')->value(true);
		$currencyList = $sel->result;

		//@todo get real count
		$availableCount = $count;
		//@todo check is allow delivering
		$delivery = true;
		$data = array(
			"feedId" => $feedId,
			"offerId" => $itemId,
			"price" => round($price / $currencyList[0]->rate),
			"count" => $availableCount,
			"delivery" => $delivery
		);

		return $data;
	}


	/**
	* Способ оплаты заказа.
	*
	* @return array
	*/
	public function _getPaymentMethods($settings) {
		$payments = array();
		if ($settings['cashOnDelivery']) {
			array_push($payments, "CASH_ON_DELIVERY");
		}
		if ($settings['cardOnDelivery']) {
			array_push($payments, "CARD_ON_DELIVERY");
		}
		if ($settings['shopPrepaid']) {
			array_push($payments, "SHOP_PREPAID");
		}
		return $payments;
	}

	 /**
	 * Get delivery options
	 *
	 * @param string $currency
	 * @return array
	 */
	public function _getDeliveryOptions($currency) {
		$response = array();

		//Get currency
		$sel = new selector('objects');
		$sel->types('object-type')->name('emarket', 'currency');
		$sel->where('codename')->equals($currency);
		$sel->option('no-length')->value(true);
		$sel->option('load-all-props')->value(true);
		$currencyList = $sel->result;

		//Create fake order
		$order = order::create();
		$deliveriesList = delivery::getList();
		foreach ($deliveriesList as $delivery) {

			$deliveryTypeObject = delivery::get($delivery->id);
			$deliveryPrice = (float)$deliveryTypeObject->getDeliveryPrice($order);

			$data = array(
				"id" => (string)$delivery->id,
				"serviceName" => $delivery->name, //Наименование службы доставки.
				//Стоимость доставки в валюте заказа. Для отделения целой части от дробной используется точка.
				"price" => round($deliveryPrice / $currencyList[0]->rate),
				//Диапазон дат доставки.
				"dates" => array(
					"fromDate" => date("d-m-Y"),
				)
			);

			if ($deliveryTypeObject instanceof selfDelivery) {
				$data['type'] = 'PICKUP'; //самовывоз
				//Информация о пункте самовывоза.
				// @todo need outlets
				$data['outlets'] = array(
					array('id' => 1)
				);
			} elseif ($deliveryTypeObject instanceof courierDelivery) {
				$data['type'] = 'DELIVERY'; //курьерская доставка
			} else {
				$data['type'] = 'POST'; //почта
			}
			$response[] = $data;
		}

		//Remove order
		$order->delete();

		return $response;
	}

	/**
	* Get country name by code
	*
	* @param int $code
	* @return string
	*/
	public function _getCountryNameByCode($code) {
		$objects = umiObjectsCollection::getInstance();
		$country = $objects->getObject($code);

		return $country instanceof iUmiObject ? $country->getName() : '';
	}


	/**
	* Get country code by name
	*
	* @param string $name
	* @return null|int
	*/
	public function _getCountryCodeByName($name) {
		$label = getI18n($name);
		$selector = new selector('objects');
		$selector->types('object-type')->guid('d69b923df6140a16aefc89546a384e0493641fbe');
		$selector->option('or-mode')->field('name');
		$selector->where('name')->equals($name);
		$selector->where('name')->equals($label);
		$selector->option('no-length')->value(true);
		$selector->option('return')->value('id');

		if ($selector->first) {
			return $selector->first['id'];
		}

		return null;
	}


	/**
	* POST /order/accept
	* POST /order/status
	* @param string $template
	*/
	public function yandexOrder($template = "default") {
		$authorized = getRequest('umi_authorization');
		if (strlen($authorized) == 0) {
			$this->_sendForbidden('Auth param expected');
		}

		$domainId = cmsController::getInstance()->getCurrentDomain()->getId();
		$settings = $this->getYandexMarketSettings($domainId);

		if (!$settings || $authorized !== $settings['marketToken']) {
			//Not found for not Authorized request
			$this->_sendForbidden('Not authorized request');
		}

		$requestType = getRequest('param1');

		if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
			if ($requestType == 'status') {
				//Уведомляет магазин о смене статуса заказа.
				//POST /yandex_market/order/status

				$orderStatus = file_get_contents("php://input");
				$request = json_decode($orderStatus);

				$status = $request->order->status;
				$marketOrderId = $request->order->id;
				$buyer = $request->order->buyer;
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('yandex_order_id')->equals($marketOrderId);
				$sel->option('no-length')->value(true);
				$sel->limit(0, 1);

				if ($sel->first) {
					$order = $sel->first;
				}

				/** @var order $order */
				$order->setValue('status_change_date', time());

				switch ($status) {
					case MarketClient::ORDER_STATUS_PROCESSING:
						//add info
						$order->setValue('status_id', order::getStatusByCode('waiting'));
						if ($request->order->paymentType === 'PREPAID' && $request->order->paymentMethod === 'YANDEX') {
							$order->setValue('payment_status_id', order::getStatusByCode('accepted', 'order_payment_status'));
							$order->commit();
						}

						$addressId = $order->getValue("delivery_address");
						$customerId = $order->getValue("customer_id");
						//Save Customer data
						$customer = umiObjectsCollection::getInstance()->getObject($customerId);
						$customer->setName($buyer->firstName . ' ' . $buyer->lastName);
						$customer->setValue('fname', $buyer->firstName);
						$customer->setValue('lname', $buyer->lastName);
						$customer->setValue('phone', $buyer->phone);
						$customer->setValue('email', $buyer->email);
						$customer->commit();

						//Save address
						$address = umiObjectsCollection::getInstance()->getObject($addressId);
						//Страна
						$address->setValue('country', $this->_getCountryCodeByName($request->order->delivery->address->country));
						//Регион
						$address->setValue('region', $request->order->delivery->region->parent->name);
						//Город
						$address->setValue('city', $request->order->delivery->address->city);
						//Улица
						$address->setValue('street', $request->order->delivery->address->street);
						//Дом
						$address->setValue('house', $request->order->delivery->address->house);
						//Квартира
						if (isset($request->order->delivery->address->apartment)) {
							$address->setValue('flat', $request->order->delivery->address->apartment);
						}

						if (isset($request->order->delivery->address->postcode)) {
							$address->setValue('index', $request->order->delivery->address->postcode);
						}

						$orderComment = '';
						//Комментарий к адресу
						if (isset($request->order->notes)) {
							$orderComment = $request->order->notes;
						}

						if (isset($request->order->delivery->address->block)) {
							$orderComment .= ', Корпус: ' . $request->order->delivery->address->block;
						}

						if (isset($request->order->delivery->address->entrance)) {
							$orderComment .= ', Подъезд: ' . $request->order->delivery->address->entrance;
						}
						if (isset($request->order->delivery->address->entryphone)) {
							$orderComment .= ', Домофон: ' . $request->order->delivery->address->entryphone;
						}
						if (isset($request->order->delivery->address->floor)) {
							$orderComment .= ', Этаж: ' . $request->order->delivery->address->floor;
						}
						if (isset($request->order->delivery->address->recipient)) {
							$orderComment .= ', Получатель: ' . $request->order->delivery->address->recipient;
						}
						if (isset($request->order->delivery->address->phone)) {
							$orderComment .= ', Телефон: ' . $request->order->delivery->address->phone;
						}
						$address->setValue('order_comments', $orderComment);
						$address->commit();

						$order->commit();
						break;
					case MarketClient::ORDER_STATUS_CANCELLED:
						//$request->order->substatus
						//change status
						$order->setValue('status_id', order::getStatusByCode('canceled'));
						$order->commit();
						break;
					case MarketClient::ORDER_STATUS_DELIVERED:
						//change status
						$order->setValue('status_id', order::getStatusByCode('ready'));
						$order->commit();
						break;
					case MarketClient::ORDER_STATUS_DELIVERY:
						//change status
						$order->setValue('status_id', order::getStatusByCode('delivery'));
						$order->commit();
						break;
					case MarketClient::ORDER_STATUS_PICKUP:
						//change status
						$order->setValue('status_id', order::getStatusByCode('ready'));
						$order->commit();
						break;
				}


				$buffer = \UmiCms\Service::Response()
					->getCurrentBuffer();
				$buffer->contentType('application/json');
				exit;

			} elseif ($requestType == 'accept') {
				//Передает заказ магазину и запрашивает подтверждение принятия заказа магазином.
				//POST /yandex_market/order/status

				$postData = file_get_contents("php://input");
				$request = json_decode($postData);
				$marketOrderId = $request->order->id;

				//Create user
				$user = customer::get();

				//Create address
				$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName("emarket", "delivery_address");
				$addressId = umiObjectsCollection::getInstance()->addObject("Address for customer #" . $user->getId(), $typeId);

				$address = umiObjectsCollection::getInstance()->getObject($addressId);
				//$address->setGUID("emarket-delivery_address-yandex" . $user->getId());

				//Страна
				$address->setValue('country', $this->_getCountryCodeByName($request->order->delivery->address->country));
				//Регион
				$address->setValue('region', $request->order->delivery->region->parent->name);
				//Город
				$address->setValue('city', $request->order->delivery->address->city);
				//Улица
				$address->setValue('street', $request->order->delivery->address->street);
				//Дом
				$address->setValue('house', $request->order->delivery->address->house);

				if (!in_array($addressId, $user->delivery_addresses)) {
					$user->setValue("delivery_addresses", array_merge($user->delivery_addresses, array($addressId)));
				}

				$orderId = $request->order->id;
				if ($orderId) {
					$sel = new selector('objects');
					$sel->types('object-type')->name('emarket', 'order');
					$sel->where('yandex_order_id')->equals($marketOrderId);
					$sel->option('no-length')->value(true);
					$sel->limit(0, 1);
					if ($sel->first) {
						$order = $sel->first;
					} else {
						//Create order
						$order = order::create();
						$order->setValue("yandex_order_id", $request->order->id);
						$order->setValue('order_date', time());
					}
				}

				$order->setValue("delivery_address", $addressId);

				$user->commit();
				$address->commit();
				$order->commit();

				//Create items
				foreach ($request->order->items as $item) {
					//Create item
					$orderItem = orderItem::create($item->offerId);
					//Set amount of item
					$orderItem->setAmount($item->count);
					//Add item
					$order->appendItem($orderItem);
				}

				//Delivery
				$deliveryId = $request->order->delivery->id;
				$delivery = delivery::get($deliveryId);
				$deliveryPrice = (float)$delivery->getDeliveryPrice($order);
				$order->setValue('delivery_id', $deliveryId);
				$order->setValue('delivery_price', $deliveryPrice);

				//Recalculate order
				$order->refresh();

				//Generate number
				$order->generateNumber();

				//Статус заказа
				//Set status 'waiting'
				$order->setOrderStatus(order::getStatusByCode('waiting'));

				$response = array(
					"order" => array(
						//Признак заказа.
						"accepted" => true,
						//Идентификатор заказа, присвоенный магазином. Указывается, если заказ принят.
						"id" => (string) $order->id
					)
				);

				$this->_outputJson($response);
			}
		}

		$this->_sendBadRequest();
	}

	/** Отправляет Яндекс.Маркет пустой ответ со статусом 400 */
	public function _sendBadRequest() {
		$buffer = \UmiCms\Service::Response()
			->getCurrentBuffer();
		$buffer->status('400 Bad Request');
		$buffer->end();
	}

	/**
	 * Отправляет Яндекс.Маркет ответ со статусом 403
	 * @param string $message сообщение ответа
	 */
	public function _sendForbidden($message) {
		$buffer = \UmiCms\Service::Response()
			->getCurrentBuffer();
		$buffer->status('403 Forbidden');
		$buffer->push($message);
		$buffer->end();
	}

	/** Отправляет Яндекс.Маркет пустой ответ со статусом 404 */
	public function _sendNotFound() {
		$buffer = \UmiCms\Service::Response()
			->getCurrentBuffer();
		$buffer->status('404 Not Found');
		$buffer->end();
	}

	/** Отправляет Яндекс.Маркет пустой ответ со статусом 500 */
	public function _sendServerError() {
		$buffer = \UmiCms\Service::Response()
			->getCurrentBuffer();
		$buffer->status('500 Internal Server Error');
		$buffer->end();
	}

	/** @param array $data */
	public function _outputJson($data) {
		\UmiCms\Service::Response()
			->printJson($data);
	}

	/**
	* If changed on /admin/emarket/orders/
	*
	* @param iUmiEventPoint $event
	* @throws Exception
	*/
	public function changedOrderEntity(iUmiEventPoint $event) {
		$entity = $event->getRef("entity");

		if ($entity instanceof iUmiObject) {
			$allowedProperties = array("status_id", "delivery_status_id");
			$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('emarket', 'order');

			if ( ($entity->getTypeId() == $typeId) && (in_array($event->getParam("property"), $allowedProperties)) && ($event->getParam("newValue") != $event->getParam("oldValue")) ) {
				$orderId = $entity->getPropByName('yandex_order_id')->getValue();

				if (!$orderId) {
					return;
				}

				if ($event->getParam("property") == 'status_id') {
					$status = order::getCodeByStatus($event->getParam("newValue"));
					$this->_sendStatusOnChangeOrderStatus($orderId, $status, $entity);
				} elseif ($event->getParam("property") == 'delivery_status_id') {
					$status = order::getCodeByStatus($event->getParam("newValue"));
					$this->_sendStatusOnChangeOrderStatus($orderId, $status, $entity);
				}

			} else {
				if ($event->getParam("property") == 'total_price') {
					$orderId = $entity->getPropByName('yandex_order_id')->getValue();

					if (!$orderId) {
						return;
					}

					throw new Exception('Нельзя изменить цену товара заказаного через Яндекс.Маркет');
				}
			}
		}
	}

	/**
	* If changed on /admin/emarket/order_edit/{$orderId}/
	*
	* @param iUmiEventPoint $event
	*/
	public function changedOrder(iUmiEventPoint $event) {
		static $modifiedCache = array();
		$object = $event->getRef("object");

		//Changed address
		$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('emarket-deliveryaddress');
		if ($object->getTypeId() == $typeId) {
			if ($event->getMode() == "after") {
				$data = getRequest("data");
				$id = $object->getId();

				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('delivery_address')->equals($object->id);
				$sel->option('no-length')->value(true);
				$orders = $sel->result;

				if (!isset($orders[0])) {
					return;
				}

				$order = $orders[0];

				if (!$order instanceof iUmiObject) {
					return;
				}

				$orderId = $order->getValue('yandex_order_id');

				if (!$orderId) {
					return;
				}

				$this->_updateDeliveryAddress($orderId, $data[$id], $order);
			}
			return;
		}

		if ($object instanceof iUmiObject) {
			$orderId = $object->getValue('yandex_order_id');

			if (!$orderId) {
				return;
			}

			//Disallow change count items
			if (getRequest("order-amount-item") || getRequest("order-del-item")) {
				unset($_REQUEST["order-amount-item"]);
				unset($_REQUEST["order-del-item"]);
			}

			$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('emarket', 'order');
			if ($object->getTypeId() != $typeId) return;
			if ($event->getMode() == "before") {
				$data = getRequest("data");
				$id = $object->getId();
				$newOrderStatus = getArrayKey($data[$id], 'status_id');
				$newDeliveryStatus = getArrayKey($data[$id], 'delivery_status_id');
				$newDeliveryPrice = getArrayKey($data[$id], 'delivery_price');
				$newDeliveryService = getArrayKey($data[$id], 'delivery_id');

				switch (true) {
					case ($newOrderStatus != $object->getValue("status_id")) :
						//Changed Order status
						$modifiedCache[$object->getId()] = array(
						'name' => 'status_id',
						'oldValue' => $object->getValue("status_id"),
						'newValue' => $newOrderStatus
						);
						break;
					case ($newDeliveryStatus != $object->getValue("delivery_status_id")) :
						//Changed Delivery status
						$modifiedCache[$object->getId()] = array(
						'name' => 'delivery_status_id',
						'oldValue' => $object->getValue("delivery_status_id"),
						'newValue' => $newDeliveryStatus
						);
						break;
					case ($newDeliveryPrice != $object->getValue("delivery_price")) :
						//Changed Delivery price
						$modifiedCache[$object->getId()] = array(
						'name' => 'delivery_price',
						'oldValue' => $object->getValue("delivery_price"),
						'newValue' => $newDeliveryPrice
						);
						break;
					case ($newDeliveryService != $object->getValue("delivery_id")) :
						//Changed Delivery price
						$modifiedCache[$object->getId()] = array(
						'name' => 'delivery_id',
						'oldValue' => $object->getValue("delivery_id"),
						'newValue' => $newDeliveryService
						);
						break;
						/*case ($newPaymentStatus != $object->getValue("payment_status_id")) :
						$modifiedCache[$object->getId()] = "payment_status_id";
						break;*/
				}
			} else {
				if (isset($modifiedCache[$object->getId()])) {

					if ($modifiedCache[$object->getId()]['name'] === 'status_id') {

						$status = order::getCodeByStatus($object->getValue("status_id"));
						try {
							$this->_sendStatusOnChangeOrderStatus($orderId, $status, $object);
						} catch (Exception $e) {
							$object->setValue("status_id", $modifiedCache[$object->getId()]['oldValue']);
						}

					} elseif ($modifiedCache[$object->getId()]['name'] === 'delivery_status_id') {

						$status = order::getCodeByStatus($object->getValue("delivery_status_id"));
						try {
							$this->_sendStatusOnChangeOrderStatus($orderId, $status, $object);
						} catch (Exception $e) {
							$object->setValue("delivery_status_id", $modifiedCache[$object->getId()]['oldValue']);
						}

					} elseif ($modifiedCache[$object->getId()]['name'] === 'delivery_id') {

						$objects = umiObjectsCollection::getInstance();
						$delivery = $objects->getObject($object->getValue("delivery_id"));

						$settings = $this->getYandexMarketSettings($object->domain_id);

						$market = new MarketClient($settings['token']);
						$market->setClientId($settings['clientId']);
						$market->setLogin($settings['login']);
						$market->setCampaignId($settings['marketCampaignId']);

						//Get order
						$marketOrder = $market->getOrder($orderId);
						$currency = $marketOrder['order']['currency'];
						//Get currency
						$sel = new selector('objects');
						$sel->types('object-type')->name('emarket', 'currency');
						$sel->where('codename')->equals($currency);
						$sel->option('load-all-props')->value(true);
						$sel->option('no-length')->value(true);
						$currencyList = $sel->result;

						$deliveryTypeObject = delivery::get($delivery->getId());
						//try {
						$deliveryPrice = (float)$deliveryTypeObject->getDeliveryPrice(order::get($object->getId()));
						/*} catch (Exception $e) {
						$deliveryPrice = 0;
						}*/

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

						//Configuring data
						$data = array(
							'delivery' => array(
							'id' => (string) $delivery->getId(),
							'price' => round($deliveryPrice / $currencyList[0]->rate),
							'serviceName' => $delivery->getName(),
							'address' => array(
								'country' => $marketOrder['order']['delivery']['address']['country'],
								'postcode' => $postcode,
								'city' => $marketOrder['order']['delivery']['address']['city'],
								'street' => $marketOrder['order']['delivery']['address']['street'],
								'house' => $marketOrder['order']['delivery']['address']['house'],
								'apartment' => $apartment,
								"recipient" => $recipient,
								"phone" => $phone
								)
							)
						);

						if ($deliveryTypeObject instanceof selfDelivery) {
							$data['delivery']['type'] = 'PICKUP'; //самовывоз
							//Информация о пункте самовывоза.
							// @todo need outlets
							$data['delivery']['outlets'] = array(
							array('id' => 1)
							);
							//Error changing to PICKUP
							return;
						} elseif ($deliveryTypeObject instanceof courierDelivery) {
							$data['delivery']['type'] = 'DELIVERY'; //курьерская доставка
						} else {
							$data['delivery']['type'] = 'POST'; //почта
						}

						//Update price
						$market->updateDelivery($orderId, $data);

					} elseif ($modifiedCache[$object->getId()]['name'] === 'delivery_price') {

						$price = $object->getValue("delivery_price");

						$settings = $this->getYandexMarketSettings($object->domain_id);

						$market = new MarketClient($settings['token']);
						$market->setClientId($settings['clientId']);
						$market->setLogin($settings['login']);
						$market->setCampaignId($settings['marketCampaignId']);

						//Get order
						$marketOrder = $market->getOrder($orderId);
						$currency = $marketOrder['order']['currency'];

						//Get currency
						$sel = new selector('objects');
						$sel->types('object-type')->name('emarket', 'currency');
						$sel->where('codename')->equals($currency);
						$sel->option('load-all-props')->value(true);
						$sel->option('no-length')->value(true);
						$currencyList = $sel->result;

						//Configuring data
						$data = array(
							'delivery' => array(
								'price' => round($price / $currencyList[0]->rate)
							)
						);

						//Update price
						$market->updateDelivery($orderId, $data);
					}

				}
			}

		}

	}


	/**
	* @param int $orderId
	* @param array $data
	*/
	public function _updateDeliveryAddress($orderId, $data, $order) {
		$domainName = $order->domain_id;
		$domainId = domainsCollection::getInstance()->getDomainId($domainName);
		$settings = $this->getYandexMarketSettings($domainId);

		$market = new MarketClient($settings['token']);
		$market->setClientId($settings['clientId']);
		$market->setLogin($settings['login']);
		$market->setCampaignId($settings['marketCampaignId']);

		$order = $market->getOrder($orderId);

		$recipient = '';
		if (isset($order['order']['delivery']['address']['recipient'])) {
			$recipient = $order['order']['delivery']['address']['recipient'];
		}
		$phone = '';
		if (isset($order['order']['delivery']['address']['phone'])) {
			$phone = $order['order']['delivery']['address']['phone'];
		}

		//Configuring data
		$data = array(
			'delivery' => array(
				'address' => array(
				'country' => $this->_getCountryNameByCode($data['country']),
				'postcode' => $data['index'],
				'city' => $data['city'],
				'street' => $data['street'],
				'house' => $data['house'],
				'apartment' => $data['flat'],
				"recipient" => $recipient,
				"phone" => $phone
				)
			)
		);

		//Update address
		$market->updateDelivery($orderId, $data);
	}

	/**
	* @param string $status
	* @return array
	*/
	public function _getMarketStatusByShopStatus($status) {
		$statuses = array(
			'canceled' => array(
				'status' => MarketClient::ORDER_STATUS_CANCELLED,
				'subStatus' => MarketClient::ORDER_SUBSTATUS_USER_CHANGED_MIND
			),
			'rejected' => array(
				'status' => MarketClient::ORDER_STATUS_CANCELLED,
				'subStatus' => MarketClient::ORDER_SUBSTATUS_SHOP_FAILED
			),
			'delivery' => array(
				'status' => MarketClient::ORDER_STATUS_DELIVERY,
				'subStatus' => null
			),
			'ready' => array(
				'status' => MarketClient::ORDER_STATUS_DELIVERED,
				'subStatus' => null
			),
			'waiting' => array(
				'status' => MarketClient::ORDER_STATUS_PROCESSING,
				'subStatus' => null
			),
			'payment' => array(
				'status' => MarketClient::ORDER_STATUS_PROCESSING,
				'subStatus' => null
			),
			'editing' => array(
				'status' => MarketClient::ORDER_STATUS_PROCESSING,
				'subStatus' => null
			),
			'accepted' => array(
				'status' => MarketClient::ORDER_STATUS_PROCESSING,
				'subStatus' => null
			),
			//Shipping statuses
			'waiting_shipping' => array(
				'status' => MarketClient::ORDER_STATUS_DELIVERY,
				'subStatus' => null
			),
			'shipping' => array(
				'status' => MarketClient::ORDER_STATUS_DELIVERY,
				'subStatus' => null
			),
			'not_defined' => array(
				'status' => MarketClient::ORDER_STATUS_PROCESSING,
				'subStatus' => null
			)
		);

		return $statuses[$status];
	}


	/**
	* @param string $oldStatus
	* @param string $newStatus
	* @return bool
	*/
	public function _isAllowChangeStatus($oldStatus, $newStatus) {

		if ($oldStatus === 'UNPAID') {
			if ($newStatus === MarketClient::ORDER_STATUS_DELIVERY || $newStatus === MarketClient::ORDER_STATUS_CANCELLED) {
				return true;
			}
		}

		if ($oldStatus === MarketClient::ORDER_STATUS_PROCESSING) {
			if ($newStatus === MarketClient::ORDER_STATUS_DELIVERY || $newStatus === MarketClient::ORDER_STATUS_CANCELLED) {
				return true;
			}
		}

		if ($oldStatus === MarketClient::ORDER_STATUS_DELIVERY) {
			if ($newStatus === MarketClient::ORDER_STATUS_PICKUP || $newStatus === MarketClient::ORDER_STATUS_DELIVERED || $newStatus === MarketClient::ORDER_STATUS_CANCELLED) {
				return true;
			}
		}

		if ($oldStatus === MarketClient::ORDER_STATUS_PICKUP) {
			if ($newStatus === MarketClient::ORDER_STATUS_DELIVERED || $newStatus === MarketClient::ORDER_STATUS_CANCELLED) {
				return true;
			}
		}

		return false;
	}


	/**
	* @param int $orderId
	* @param string $status
	* @throws Exception
	*/
	public function _sendStatusOnChangeOrderStatus($orderId, $status, $object = false) {
		$settings = $this->getYandexMarketSettings($object->domain_id);

		if (!$settings) {
			return false;
		}

		$market = new MarketClient($settings['token']);
		$market->setClientId($settings['clientId']);
		$market->setLogin($settings['login']);
		$market->setCampaignId($settings['marketCampaignId']);

		//get current market status
		$order = $market->getOrder($orderId);
		$currentMarketStatus = $order['order']['status'];

		$statusData = $this->_getMarketStatusByShopStatus($status);

		if ($currentMarketStatus === $statusData['status']) {
			return;
		}

		if ($this->_isAllowChangeStatus($currentMarketStatus, $statusData['status'])) {
			$market->setOrderStatus(
				$orderId,
				$statusData['status'],
				$statusData['subStatus']
			);
		} else {
			throw new Exception('Нельзя изменить на такой статус для заказа с Яндекс.Маркета');
		}

	}
	

	public function getYandexMarketSettings($domainId = false) {
		if (!$domainId) {
			return false;
		}

		if (!is_numeric($domainId)) {
			$domainId = domainsCollection::getInstance()->getDomainId($domainId);
		}

		if (!$domainId) {
			return false;
		}

		$regedit = regedit::getInstance();

		$settings = array();
		$settings['clientId'] = $regedit->getVal("//modules/emarket/yandex_market/{$domainId}/clientId");
		$settings['password'] = $regedit->getVal("//modules/emarket/yandex_market/{$domainId}/password");
		$settings['token'] = $regedit->getVal("//modules/emarket/yandex_market/{$domainId}/token");
		$settings['login'] = $regedit->getVal("//modules/emarket/yandex_market/{$domainId}/login");
		$settings['marketToken'] = $regedit->getVal("//modules/emarket/yandex_market/{$domainId}/marketToken");
		$settings['marketCampaignId'] = $regedit->getVal("//modules/emarket/yandex_market/{$domainId}/marketCampaignId");
		$settings["cashOnDelivery"] = $regedit->getVal("//modules/emarket/yandex_market/{$domainId}/cashOnDelivery");
		$settings["cardOnDelivery"] = $regedit->getVal("//modules/emarket/yandex_market/{$domainId}/cardOnDelivery");
		$settings["shopPrepaid"] = $regedit->getVal("//modules/emarket/yandex_market/{$domainId}/shopPrepaid");

		if (strlen($settings['clientId']) == 0) {
			return false;
		}

		return $settings;
	}
}
?>
