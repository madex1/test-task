<?php

	use UmiCms\Service;

	/**
	 * @method array formatCurrencyPrice(array $prices, iUmiObject $defaultCurrency = null, iUmiObject $defaultCurrency = null) Пересчитать цены в массиве $prices в валюту $currency
	 * @method array formatCurrencyPrices(array $prices, iUmiObject $defaultCurrency = null)
	 */
	abstract class __emarket_purchasing extends def_module {
		/**
		 * Список необходимых шагов,
		 * которые должен пройти пользователь для покупки товара
		 *
		 * @var array
		 */
		public static $purchaseSteps = array('required');

		/**
		 * Список возможных действий,
		 * которые может пройти пользователь
		 *
		 * @var array
		 */
		public static $availableSteps = array('autofill');

		/**
		 * Инициализация библиотеки модуля.
		 *
		 * В данном случае запускается getBasketOrder(), чтобы гарантировать
		 * существование корзины для пользователя.
		 */
		public function onInit() {
			$regedit = regedit::getInstance();
			
			if($regedit->getVal('//modules/emarket/enable-delivery')) {
				self::$purchaseSteps[] = 'delivery';
			}
			
			if($regedit->getVal('//modules/emarket/enable-payment')) {
				self::$purchaseSteps[] = 'payment';
			}

			self::$purchaseSteps[] = 'result';
			if(in_array(cmsController::getInstance()->getCurrentMethod(), array("gateway", "receipt") )) {
				$this->__loadLib("__payments.php");
				$this->__implement("__emarket_payment");
			}

			if(in_array(cmsController::getInstance()->getCurrentMethod(), array("removeDeliveryAddress") )) {
				$this->__loadLib("__delivery.php");
				$this->__implement("__emarket_delivery");
			}
		}

		/**
		 * Кнопка добавления товара в корзину
		 *
		 * Генерирует кнопку добавления товара в корзину
		 *
		 * @param $elementId Элемент, который требуется добавить в корзину
		 * @param string $template Шаблон(для TPL)
		 *
		 * @return mixed
		 */
		public function basketAddLink($elementId, $template = 'default') {
			list($tpl_block) = def_module::loadTemplates("emarket/".$template, 'basket_add_link');

			return def_module::parseTemplate($tpl_block, array(
				'link' => $this->pre_lang . '/emarket/basket/put/element/' . (int) $elementId . '/'
			));
		}

		/**
		 * Кнопка добавления товара в корзину с предвыбранным способом оплаты
		 *
		 * Генерирует кнопку быстрого добавления товара в корзину
		 *
		 * @param int $elementId элемент
		 * @param mixed $paymentIdOrGUID способ оплаты
		 * @param string $template шаблон
		 * @throws publicException если способ оплаты не найден
		 * @return mixed
		 */
		public function basketAddFastLink($elementId, $paymentIdOrGUID, $template = 'default'){
			if(is_null($elementId)) {
				$elementId = getRequest('param0');
			}
			if(is_null($paymentIdOrGUID)) {
				$paymentIdOrGUID = (int) getRequest('param1');
			}

			list($tplBlock, $tplError) = def_module::loadTemplates("emarket/" . $template, 'basket_add_fast_link', 'basket_add_fast_error');

			if(!is_numeric($paymentIdOrGUID)) {
				$payment = umiObjectsCollection::getInstance()->getObjectByGUID($paymentIdOrGUID);
			} else {
				$payment = umiObjectsCollection::getInstance()->getObject($paymentIdOrGUID);
			}

			if(!$payment || !in_array((string) $payment, payment::getList())) {
				return def_module::parseTemplate($tplError, array(
					'error' => getLabel('error-basket_fast_add-no_payment')
				));
			}

			return def_module::parseTemplate($tplBlock, array(
				'link' => $this->pre_lang . '/emarket/fastPurchase/' . (int) $elementId . '/' . $payment->getId() . '/'
			));
		}

		/**
		 * @internal
		 *
		 * @param int $elementId элемент
		 * @param null $paymentId способ оплаты
		 * @throws breakException если способ оплаты не найден
		 * @internal param int $paym entId способ оплаты
		 */
		public function fastPurchase($elementId = null, $paymentId = null) {
			if(is_null($elementId)) {
				$elementId = getRequest('param0');
			}
			if(is_null($paymentId)) {
				$paymentId = (int) getRequest('param1');
			}

			if(!$paymentId || !in_array((string) $paymentId, payment::getList())) {
				throw new breakException(getLabel('error-basket_fast_add-no_payment'));
			}

			$noRedirect = getRequest('no-redirect');
			if (!$noRedirect) {
				if (NULL == ($redirectUrl = getRequest('redirect-uri'))) {
					$redirectUrl = $this->pre_lang . '/emarket/cart/';
				}
			}
			$_REQUEST['no-redirect'] = 1;

			$this->basket('put', 'element', $elementId);
			$order = self::getBasketOrder();
			$order->setValue('payment_id', (int) $paymentId);
			$order->commit();

			if(!$noRedirect) {
				$this->redirect($redirectUrl);
			}
		}

		/**
		 * Получить стоимость товара $element с учетом скидки
		 *
		 * @param iUmiHierarchyElement $element
		 * @param bool $ignoreDiscounts игнорировать скидки
		 *
		 * @return Float стоимость товара
		 */
		public function getPrice(iUmiHierarchyElement $element, $ignoreDiscounts = false) {
			$discount = itemDiscount::search($element);
			$price = $element->price;

			if(!$ignoreDiscounts && $discount instanceof discount) {
				$price = $discount->recalcPrice($price);
			}

			return $price;
		}

		/**
		 * Получить стоимость товара $elementId (со скидкой и без одновременно)
		 *
		 * @param null|int $elementId
		 * @param string $template
		 * @param bool $showAllCurrency
		 *
		 * @return mixed
		 * @throws publicException если данный элемент не найден
		 */
		public function price($elementId = null, $template = 'default', $showAllCurrency = true) {
			if(!$elementId) return null;
			$hierarchy = umiHierarchy::getInstance();
			$elementId = $this->analyzeRequiredPath($elementId);

			if($elementId == false) {
				throw new publicException("Wrong element id given");
			}

			$element = $hierarchy->getElement($elementId);
			if($element instanceof iUmiHierarchyElement == false) {
				throw new publicException("Wrong element id given");
			}


			list($tpl_block) = def_module::loadTemplates("emarket/".$template, 'price_block');

			$originalPrice = $element->price;
			//Discounts
			$result = array(
				'attribute:element-id' => $elementId
			);

			$discount = itemDiscount::search($element);
			if($discount instanceof discount) {
				$result['discount'] = array(
					'attribute:id'		=> $discount->id,
					'attribute:name'	=> $discount->getName(),
					'description'		=> $discount->getValue('description')
				);
				$result['void:discount_id'] = $discount->id;
			}


			//Currency
			$price = self::formatPrice($element->price, $discount);
			if ($currencyPrice = $this->formatCurrencyPrice($price)) {
				$result['price'] = $currencyPrice;
			} else {
				$result['price'] = $price;
			}

			$result['price'] = $this->parsePriceTpl($template, $result['price']);
			$result['void:price-original'] = getArrayKey($result['price'], 'original');
			$result['void:price-actual'] = getArrayKey($result['price'], 'actual');

			if($showAllCurrency) {
				$result['currencies'] = $this->formatCurrencyPrices($price);
				$result['currency-prices'] = $this->parseCurrencyPricesTpl($template, $price);
			}

			return def_module::parseTemplate($tpl_block, $result);
		}


		/**
		 * TODO: Write documentation
		 *
		 * All these cases renders full basket order:
		 * /udata/emarket/basket/ - do nothing
		 * /udata/emarket/basket/add/element/9 - add element 9 into the basket
		 * /udata/emarket/basket/add/element/9?amount=5 - add element 9 into the basket + amount
		 * /udata/emarket/basket/add/element/9?option[option_name_1]=1&option=2&option[option_name_2]=3 - add element 9 using options
		 * /udata/emarket/basket/modify/element/9?option[option_name_1]=1&option=2&option[option_name_2]=3 - add element 9 using options
		 * /udata/emarket/basket/modify/item/9?option[option_name_1]=1&option=2&option[option_name_2]=3 - add element 9 using options
		 * /udata/emarket/basket/remove/element/9 - remove element 9 from the basket
		 * /udata/emarket/basket/remove/item/111 - remove orderItem 111 from the basket
		 * /udata/emarket/basket/remove_all/ - remove all orderItems from basket
		 */
		public function basket($mode = false, $itemType = false, $itemId = false) {
			$mode = $mode ? $mode : getRequest('param0');
			$order = self::getBasketOrder(!in_array($mode, array('put', 'remove')));
			$itemType = $itemType ? $itemType : getRequest('param1');
			$itemId = (int) ($itemId ? $itemId : getRequest('param2'));
			$amount = (int) getRequest('amount');
			$options = getRequest('options');

			if($mode == 'put') {
				$newElement = false;
				if ($itemType == 'element') {
					$orderItem = $this->getBasketItem($itemId, false);
					if (!$orderItem) {
						$orderItem = $this->getBasketItem($itemId);
						$newElement = true;
					}
				} else {
					$orderItem = $order->getItem($itemId);
				}

				if (!$orderItem) {
					throw new publicException("Order item is not defined");
				}

				if(is_array($options)) {
					if($itemType != 'element') {
						throw new publicException("Put basket method required element id of optionedOrderItem");
					}

					// Get all orderItems
					$orderItems = $order->getItems();

					foreach($orderItems as $tOrderItem) {
						if (!$tOrderItem instanceOf optionedOrderItem) {
							$itemOptions = null;
							$tOrderItem = null;
							continue;
						}

						$itemOptions = $tOrderItem->getOptions();

						if(count($itemOptions) != count($options)) {
							$itemOptions = null;
							$tOrderItem = null;
							continue;
						}

						if($tOrderItem->getItemElement()->id != $orderItem->getItemElement()->id) {
							$itemOptions = null;
							$tOrderItem = null;
							continue;
						}

						// Compare each tOrderItem with options list
						foreach($options as $optionName => $optionId) {
							$itemOption = getArrayKey($itemOptions, $optionName);

							if(getArrayKey($itemOption, 'option-id') != $optionId) {
								$tOrderItem = null;
								continue 2;		// If does not match, create new item using options specified
							}
						}

						break;	// If matches, stop loop and continue to amount change
					}

					if(!isset($tOrderItem) || is_null($tOrderItem)) {
						$tOrderItem = orderItem::create($itemId);
						$order->appendItem($tOrderItem);
						if ($newElement) {
							$orderItem->remove();
						}
					}

					if($tOrderItem instanceof optionedOrderItem) {
						foreach($options as $optionName => $optionId) {
							if($optionId) {
								$tOrderItem->appendOption($optionName, $optionId);
							} else {
								$tOrderItem->removeOption($optionName);
							}
						}
					}

					if($tOrderItem) {
						$orderItem = $tOrderItem;
					}
				}

				$amount = $amount ? $amount : ($orderItem->getAmount() + 1);
				$orderItem->setAmount($amount ? $amount : 1);
				$orderItem->refresh();

				if($itemType == 'element') {
					$order->appendItem($orderItem);
				}
			}

			if($mode == 'remove') {
				$orderItem = ($itemType == 'element') ? $this->getBasketItem($itemId, false) : orderItem::get($itemId);
				if($orderItem instanceof orderItem) {
					$order->removeItem($orderItem);
                }
			}

			if ($mode == 'remove_all') {
				foreach ($order->getItems() as $orderItem) {
					$order->removeItem($orderItem);
				}
			}

			$order->refresh();

			$referer = getServer('HTTP_REFERER');
			$noRedirect = getRequest('no-redirect');

			if($redirectUri = getRequest('redirect-uri')) {
				$this->redirect($redirectUri);
			} else if (!defined('VIA_HTTP_SCHEME') && !$noRedirect && $referer) {
				$current = $_SERVER['REQUEST_URI'];
				if(substr($referer, -strlen($current)) == $current) {
					if($itemType == 'element') {
						$referer = umiHierarchy::getInstance()->getPathById($itemId);
					} else {
						$referer = "/";
					}
				}
				$this->redirect($referer);
			}

			return $this->order($order->getId());
		}

		/**
		 * Вывести список покупок (содержимое корзины)
		 *
		 * @param string $template
		 *
		 * @return mixed
		 */
		public function cart($template = 'default') {
			$customer_id = (int) \UmiCms\Service::CookieJar()->getDecrypted('customer-id');

			if (!permissionsCollection::getInstance()->isAuth() && !$customer_id){
				list($tpl_block_empty) = def_module::loadTemplates("emarket/".$template, 'order_block_empty');
				$result = array(
					'attribute:id' => 'dummy',
					'summary' => array('amount' => 0),
					'steps' => $this->getPurchaseSteps($template, null)
				);

				return def_module::parseTemplate($tpl_block_empty, $result);
			}

			$order = self::getBasketOrder();
            $order->refresh();
			return $this->order($order->getId(), $template);
		}

		/**
		 * Вывести информацию о заказе $orderId
		 *
		 * @param bool $orderId Номер заказа
		 * @param string $template Шаблон(для tpl)
		 *
		 * @return mixed
		 * @throws publicException если не указан номер заказа или заказ не существует
		 * @throws publicException недостаточно прав
		 */
		public function order($orderId = false, $template = 'default') {
			if(!$template) $template = 'default';
			$permissions = permissionsCollection::getInstance();

			$orderId = (int) ($orderId ? $orderId : getRequest('param0'));
			if(!$orderId) {
				throw new publicException("You should specify order id");
			}

			$order = order::get($orderId);
			if($order instanceof order == false) {
				throw new publicException("Order #{$orderId} doesn't exist");
			}

			if(!$permissions->isSv() && ($order->getName() !== 'dummy') &&
			   (customer::get()->getId() != $order->customer_id) &&
			   !$permissions->isAllowedMethod($permissions->getUserId(), "emarket", "control")) {
				throw new publicException(getLabel('error-require-more-permissions'));
			}

			list($tpl_block, $tpl_block_empty) = def_module::loadTemplates("emarket/".$template,
				'order_block', 'order_block_empty');

			$discount = $order->getDiscount();

			$totalAmount = $order->getTotalAmount();
			$originalPrice = $order->getOriginalPrice();
			$actualPrice = $order->getActualPrice();
			$deliveryPrice = $order->getDeliveryPrice();
			$bonusDiscount = $order->getBonusDiscount();

			if($originalPrice == $actualPrice) {
				$originalPrice = null;
			}

			$discountAmount = ($originalPrice) ? $originalPrice + $deliveryPrice - $actualPrice - $bonusDiscount : 0;

			$result = array(
				'attribute:id'	=> ($orderId),
				'xlink:href'	=> ('uobject://' . $orderId),
				'customer'		=> ($order->getName() == 'dummy') ? null : $this->renderOrderCustomer($order, $template),
				'subnodes:items'=> ($order->getName() == 'dummy') ? null : $this->renderOrderItems($order, $template),
				'delivery'		=> $this->renderOrderDelivery($order, $template),
				'summary'		=> array(
					'amount'		=> $totalAmount,
					'price'			=> $this->formatCurrencyPrice(array(
						'original'		=> $originalPrice,
						'delivery'		=> $deliveryPrice,
						'actual'		=> $actualPrice,
						'discount'		=> $discountAmount,
						'bonus'			=> $bonusDiscount
					))
				),
				'discount_value' => $order->getDiscountValue(),
				'steps' => $this->getPurchaseSteps($template, null)
			);

			if ($order->number) {
				$result['number'] = $order->number;
				$result['status'] = selector::get('object')->id($order->status_id);
			}

			if(count($result['subnodes:items']) == 0) {
				$tpl_block = $tpl_block_empty;
			}

			$result['void:total-price'] = $this->parsePriceTpl($template, $result['summary']['price']);
			$result['void:delivery-price'] = $this->parsePriceTpl($template, $this->formatCurrencyPrice(array('actual' => $deliveryPrice)));
			$result['void:bonus'] = $this->parsePriceTpl($template, $this->formatCurrencyPrice(array('actual' => $bonusDiscount)));
			$result['void:total-amount'] = $totalAmount;

			$result['void:discount_id'] = false;
			if($discount instanceof discount) {
				$result['discount'] = array(
					'attribute:id'		=> $discount->id,
					'attribute:name'	=> $discount->getName(),
					'description'		=> $discount->getValue('description')
				);
				$result['void:discount_id'] = $discount->id;
			}
			return def_module::parseTemplate($tpl_block, $result, false, $order->id);
		}

		/**
		 * Получить заказ, который представляет текущую корзину товаров.
		 * Если такого заказа нет, то он будет создан
		 *
		 * @param bool $useDummyOrder
		 *
		 * @return int|order заказ, который представляет корзину товаров
		 */
		public function getBasketOrder($useDummyOrder = true) {
			static $cache;

			if($cache instanceof order) {
				//If order has order-status, that means it is not a basket any more, so we have to reset $cache
				if(($cache->getOrderStatus() && $cache->getOrderStatus() != order::getStatusByCode('editing')) || $useDummyOrder == false) {
					$cache = null;
				} else return $cache;
			}

			$customer = customer::get();

			$cmsController = cmsController::getInstance();
			$domain = $cmsController->getCurrentDomain();
			$domainId = $domain->getId();

			$orderId = $customer->getLastOrder($domainId);
			if ($orderId) return $cache = order::get($orderId);

			return $cache = order::create($useDummyOrder);
		}

		/**
		 * Возвращает элемент корзины по Id товара с которым связан элемент.
		 *
		 * @param $elementId Требуемый элемент
		 * @param bool $autoCreate Если true создаст элемент корзины, если он не существует.
		 *
		 * @return null|orderItem Элемент корзины
		 */
		public function getBasketItem($elementId, $autoCreate = true) {
			$order = self::getBasketOrder();

			$orderItems = $order->getItems();
			foreach($orderItems as $orderItem) {
				$element = $orderItem->getItemElement();
				if($element instanceof umiHierarchyElement) {
					if($element->getId() == $elementId) {
						return $orderItem;
					}
				}
			}

			return $autoCreate ? (orderItem::create($elementId)) : null;
		}

		/**
		 * Инициализирует шаги заказа
		 * @internal
		 */
		public function loadPurchaseSteps() {
			$this->__loadLib("__payments.php");
			$this->__implement("__emarket_payment");

			$this->__loadLib("__delivery.php");
			$this->__implement("__emarket_delivery");

			$this->__loadLib("__required.php");
			$this->__implement("__emarket_required");

			$this->__loadLib("__autofill.php");
			$this->__implement("__emarket_autofill");
		}

		/**
		 * Получение списка доступных шагов и их статусов
		 *
		 * @param string $template TPL Шаблон
		 * @param $stage Текущий шаг
		 *
		 * @return mixed
		 */
		protected function getPurchaseSteps($template = "default", $stage) {
			$activeStageNumber = array_search($stage, self::$purchaseSteps);
			$activeStageNumber = $activeStageNumber === false ? -1 : $activeStageNumber;

			list($completeStepTemplate, $activeStepTemplate, $incompleteStepTemplate, $purchaseStepsTemplate) = def_module::loadTemplates("emarket/" . $template, "complete_step", "active_step", "incomplete_step", "purchase_steps");

			$steps = array();
			foreach(self::$purchaseSteps as $i => $stage) {
				if ($activeStageNumber == $i) {
					$template = $activeStepTemplate;
					$status = "active";
				} else {
					$template = $activeStageNumber > $i ? $incompleteStepTemplate : $completeStepTemplate;
					$status = $activeStageNumber > $i ? "complete" : "incomplete";
				}

				$steps[] = def_module::parseTemplate($template, array(
					"@name" => getLabel("header-emarket-" . $stage),
					"@link" => "/emarket/purchase/" . $stage,
					"@status" => $status,
				));
			}

			return def_module::parseTemplate($purchaseStepsTemplate, array(
				"+item" => $steps
			));
		}

		/**
		 * Вызов необходимого шага оформления заказа
		 *
		 * @param string $template
		 *
		 * @return mixed
		 * @throws publicException корзина пуста
		 */
		public function purchase($template = 'default') {
			$this->loadPurchaseSteps();

			list($purchaseTemplate) = def_module::loadTemplates("emarket/" . $template, 'purchase');

			$stage = getRequest('param0');
			$step = getRequest('param1');
			$mode = getRequest('param2');

			$byUrl = $stage && $step;
			$order = $this->getBasketOrder();
			if($order->isEmpty() && $stage != 'result') {
				throw new publicException('%error-market-empty-basket%');
			}

			$stage = self::getStage($stage);
			if(count(self::$purchaseSteps) == 2 && $stage == 'result' && !getRequest('param0')) {
				$stage = '';
			}

			$controller = cmsController::getInstance();
			if(!$stage) {
				$order->order();
				$urlPrefix = $controller->getUrlPrefix() ? ($controller->getUrlPrefix() . '/') : '';
				$this->redirect($this->pre_lang . '/' . $urlPrefix . 'emarket/purchase/result/successful/');
			}

			$checkStepMethod = $stage . 'CheckStep';

			$step = $this->$checkStepMethod($order, $step);

			if(!$step || !$byUrl) {
				$step = $step ? $step : "choose";
				$urlPrefix = $controller->getUrlPrefix() ? ($controller->getUrlPrefix() . '/') : '';
				$this->redirect($this->pre_lang . "/{$urlPrefix}emarket/purchase/{$stage}/{$step}/");
			}

			$stageResult = $this->$stage($order, $step, $mode, $template);

			$result = array(
				'purchasing' => array(
					'attribute:stage'	=> $stage,
					'attribute:step'	=> $step,
					'steps' => $this->getPurchaseSteps($template, $stage)
				)
			);

			$this->setHeader("%header-{$stage}-{$step}%");
			if (is_array($stageResult)) {
				$result['purchasing'] = array_merge($result['purchasing'], $stageResult);
			} elseif (!def_module::isXSLTResultMode()) {
				$result['purchasing'] = $stageResult;
			} else {
				throw new publicException("Incorrect return value from {$stage}() purchasing method");
			}

			return def_module::parseTemplate($purchaseTemplate, $result);
		}

		public function resultCheckStep(order $order, $step) {
			return $step;
		}

		/**
		 * Возвращает страницу "Заказ оформлен/Неудалось оформить"
		 *
		 * @param order $order Заказ
		 * @param string $step результат оформления заказа (успешно/нет)
		 * @param $mode
		 * @param string $template Шаблон(для tpl)
		 *
		 * @return mixed
		 */
		public function result(order $order, $step, $mode, $template) {
			list($tpl_successful, $tpl_failed) = def_module::loadTemplates("emarket/" . $template,
				'purchase_successful', 'purchase_failed');
			$tpl_block = ($step == 'successful') ? $tpl_successful : $tpl_failed;

            $orderId = null;
			$customer = customer::get();
			if ($order->isEmpty()) {
				$domain = cmsController::getInstance()->getCurrentDomain();
				$domainId = $domain->getId();

				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('customer_id')->equals($customer->getId());
				$sel->where('domain_id')->equals($domainId);
				$sel->option('no-length')->value(true);
				$sel->option('load-all-props')->value(true);
				$sel->order('id')->desc();
				if ($sel->first) {
					$orderId = $sel->first->id;
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
			
			$result = array(
				'status' => $step,
				'order' => array('attribute:id' => $orderId),
				'void:order_id' => $orderId,
				'personal_params' => $this->getPersonalLinkParams($customer->getId()),
				'invoice_link' => $invoiceLink
			);

			return def_module::parseTemplate($tpl_block, $result);
		}

		public function getCustomerInfo($template = 'default') {
			$order = self::getBasketOrder();
			return $this->renderOrderCustomer($order, $template);
		}

		/**
		 * Отрисовать покупателя
		 * @param order $order
		 * @return Array
		 */
		public function renderOrderCustomer(order $order, $template = 'default') {
			$customer = selector::get('object')->id($order->customer_id);
			if($customer instanceof iUmiObject == false) {
				throw new publicException(getLabel('error-object-does-not-exist', null, $order->customer_id));
			}

			list($tpl_user, $tpl_guest) = def_module::loadTemplates("emarket/customer/".$template, "customer_user", "customer_guest");

			$objectType = selector::get('object-type')->id($customer->typeId);
			$tpl = ($objectType->getModule() == 'users') ? $tpl_user : $tpl_guest;
			return def_module::parseTemplate($tpl, array('full:object' => $customer), false, $customer->getId());
		}

		/**
		 * Отрисовать свойства доставки
		 * @param order $order
		 * @param String $template
		 * @return Array
		 */
		public function renderOrderDelivery(order $order, $template = 'default') {
			$objectsCollection = umiObjectsCollection::getInstance();
			list($tpl, $tplMethod, $tplAddress, $tplPrice) = def_module::loadTemplates('emarket/'.$template, 'order_delivery', 'delivery_method', 'delivery_address', 'delivery_price');
			$result = array();

			$method = $objectsCollection->getObject($order->delivery_id);
			if ($method instanceof iUmiObject == false) {
				return def_module::parseTemplate($tpl, $result);
			}
			$deliveryMethod = array(
				'attribute:id' => $method->getId(),
				'attribute:name' => $method->getName(),
				'xlink:href' => ('uobject://' . $method->getId()),
			);
			$result['method'] = def_module::parseTemplate($tplMethod, $deliveryMethod);

			/** @var umiObject $address */
			$address = $objectsCollection->getObject($order->delivery_address);
			if ($address instanceof iUmiObject) {
				$country = $objectsCollection->getObject($address->country);
				$countryName = $country instanceof iUmiObject ? $country->getName() : '';
				$deliveryAddress = array(
					'attribute:id' => $address->getId(),
					'attribute:name' => $address->getName(),
					'xlink:href' => ('uobject://' . $address->getId()),
					'country' => $countryName,
					'index' => $address->index,
					'region' => $address->region,
					'city' => $address->city,
					'street' => $address->street,
					'house' => $address->house,
					'flat' => $address->flat,
					'comment' => $address->order_comments,
				);
				$result['address'] = def_module::parseTemplate($tplAddress, $deliveryAddress);
			}
			$result['price'] = def_module::parseTemplate($tplPrice, $this->formatCurrencyPrice(array('delivery' => $order->delivery_price)));

			return def_module::parseTemplate($tpl, $result);
		}

		/**
			* Отрисовать наименование в заказе
			* @param order $order
			* @return Array
		*/
		public function renderOrderItems(order $order, $template = 'default') {
			$items_arr = array();
			$objects = umiObjectsCollection::getInstance();

			list($tpl_item, $tpl_options_block, $tpl_options_block_empty, $tpl_options_item) = def_module::loadTemplates("emarket/".$template,
				'order_item', 'options_block', 'options_block_empty', 'options_item');

			$orderItems = $order->getItems();
			$isBasket = emarket::isBasket($order);

			foreach($orderItems as $orderItem) {
				/** @var orderItem $orderItem */
				$orderItemId = $orderItem->getId();

				$item_arr = array(
					'attribute:id'		=> $orderItemId,
					'attribute:name'	=> htmlspecialchars($orderItem->getName()),
					'xlink:href'		=> ('uobject://' . $orderItemId),
					'amount'			=> $orderItem->getAmount(),
					'options'			=> null
				);

				$plainPriceOriginal = $orderItem->getItemPrice();

				if ($isBasket) {
					$itemDiscount = $orderItem->getDiscount();
					$plainPriceActual = ($itemDiscount instanceof itemDiscount) ? $itemDiscount->recalcPrice($plainPriceOriginal) : $plainPriceOriginal;
					$pricesDiff = ($plainPriceOriginal - $plainPriceActual);
					$discountValue = ($pricesDiff < 0) ? 0 : $pricesDiff;
				} else {
					$discountValue = $orderItem->getDiscountValue();
					$plainPriceActual = $plainPriceOriginal - $discountValue;
				}

				$totalPriceOriginal = $orderItem->getTotalOriginalPrice();
				$totalPriceActual = $orderItem->getTotalActualPrice();

				if($plainPriceOriginal == $plainPriceActual) {
					$plainPriceOriginal = null;
				}

				if($totalPriceOriginal == $totalPriceActual) {
					$totalPriceOriginal = null;
				}

				$item_arr['price'] = $this->formatCurrencyPrice(array(
					'original'	=> $plainPriceOriginal,
					'actual'	=> $plainPriceActual
				));

				$item_arr['total-price'] = $this->formatCurrencyPrice(array(
					'original'	=> $totalPriceOriginal,
					'actual'	=> $totalPriceActual
				));

				$item_arr['price'] = $this->parsePriceTpl($template, $item_arr['price']);
				$item_arr['total-price'] = $this->parsePriceTpl($template, $item_arr['total-price']);
				$item_arr['discount_value'] = (float) $discountValue;

				$element = false;
				$status = order::getCodeByStatus($order->getOrderStatus());
				if (!$status || $status == 'basket') {
					$element = $orderItem->getItemElement();
				} else {
					$symlink = $orderItem->getObject()->item_link;
					if(is_array($symlink) && count($symlink)) {
						list($item) = $symlink;
						$element = $item;
					} else {
						$element = null;
					}
				}
				if($element instanceof iUmiHierarchyElement) {
					$item_arr['page'] = $element;

					$item_arr['void:element_id'] = $element->id;
					$item_arr['void:link'] = $element->link;
				}

				$discountAmount = $totalPriceOriginal ? $totalPriceOriginal - $totalPriceActual : 0;

				$discount = $orderItem->getDiscount();
				if($discount instanceof itemDiscount) {
					$item_arr['discount'] = array(
						'attribute:id' => $discount->id,
						'attribute:name' => $discount->getName(),
						'description' => $discount->getValue('description'),
						'amount' => $discountAmount
					);
					$item_arr['void:discount_id'] = $discount->id;
				}

				$elementId = ($element instanceof iUmiHierarchyElement) ? $element->getId() : null;

				if($orderItem instanceof optionedOrderItem) {
					$options = $orderItem->getOptions(); $options_arr = array();

					foreach($options as $optionInfo) {
						$optionId = $optionInfo['option-id'];
						$price = $optionInfo['price'];
						$fieldName = $optionInfo['field-name'];

						$option = $objects->getObject($optionId);
						if($option instanceof iUmiObject) {
							$option_arr = array(
								'attribute:id'			=> $optionId,
								'attribute:name'		=> $option->getName(),
								'attribute:price'		=> $price,
								'attribute:field-name'	=> $fieldName,
								'attribute:element_id' 	=> $elementId,
								'xlink:href'			=> ('uobject://' . $optionId)
							);

							$options_arr[] = def_module::parseTemplate($tpl_options_item, $option_arr, false, $optionId);
						}
					}

					$item_arr['options'] = def_module::parseTemplate($tpl_options_block, array(
						'nodes:option' => $options_arr,
						'void:items' => $options_arr
					));
				}

				$items_arr[] = def_module::parseTemplate($tpl_item, $item_arr);
			}
			return $items_arr;
		}

		/**
		 * Получить список всех заказов текущего пользователя
		 *
		 * @param string $template Шаблон(для TPL)
		 * @param string $sort Сортировка
		 *
		 * @return mixed
		 */
		public function ordersList($template = 'default', $sort = "asc") {
			list($tplBlock, $tplBlockEmpty, $tplItem) = def_module::loadTemplates(
				"emarket/" . $template,
				'orders_block',
				'orders_block_empty',
				'orders_item'
			);

			$domainId = cmsController::getInstance()->getCurrentDomain()->getId();
			
			$select = new selector('objects');
			$select->types('object-type')->name('emarket', 'order');
			$select->where('customer_id')->equals(customer::get()->getId());
			$select->where('name')->isNull(false);
			$select->where('domain_id')->equals($domainId);
			$select->option('no-length')->value(true);
			$select->option('load-all-props')->value(true);
			
			if(in_array($sort, array("desc"))) {
				call_user_func(array($select->order('id'), $sort));
			}

			if(!$select->first) {
				$tplBlock = $tplBlockEmpty;
			}

			$itemsArray = array();
			foreach($select->result as $order) {
				$item = array(
					'attribute:id' => $order->id,
					'attribute:name' => $order->name,
					'attribute:type-id' => $order->typeId,
					'attribute:guid' => $order->GUID,
					'attribute:type-guid' => $order->typeGUID,
					'attribute:ownerId' => $order->ownerId,
					'xlink:href' => $order->xlink,
				);

				$itemsArray[] = def_module::parseTemplate($tplItem, $item, false, $order->id);
			}

			return def_module::parseTemplate($tplBlock, array('subnodes:items' => $itemsArray));
		}

		
		private static function formatPrice($originalPrice, itemDiscount $discount = null) {
			$actualPrice = ($discount instanceof itemDiscount) ? $discount->recalcPrice($originalPrice) : $originalPrice;
			if($originalPrice == $actualPrice) {
				$originalPrice = null;
			}

			return array(
				'original'	=> $originalPrice,
				'actual'	=> $actualPrice
			);
		}

		/**
		 * Получить валидный этап покупки
		 * @param String $stage этап покупки
		 * @return String валидизированный этап покупки
		 */
		private static function getStage($stage) {
			$regedit = regedit::getInstance();
			$hasDelivery = $regedit->getVal('//modules/emarket/enable-delivery');
			$hasPayment = $regedit->getVal('//modules/emarket/enable-payment');

			if($stage == 'delivery' && !$hasDelivery) {
				$stage = 'payment';
			}

			if($stage == 'payment' && !$hasPayment) {
				return null;
			}

			if(!$stage || (!in_array($stage, self::$purchaseSteps) && !in_array($stage, self::$availableSteps))) {
				$customer = customer::get();

				if(!$customer->isUser() && !$customer->isFilled()) {
					return "required";
				}

				return getArrayKey(self::$purchaseSteps, 1);
			} else {
				return $stage;
			}
		}

		public function parsePriceTpl($template = 'default', $priceData = array()) {
			if ($this->isXSLTResultMode()) return $priceData;
			list($tpl_original, $tpl_actual) = def_module::loadTemplates("emarket/".$template,
				'price_original', 'price_actual');

			$originalPrice = getArrayKey($priceData, 'original');
			$actualPrice = getArrayKey($priceData, 'actual');

			$result = array();
			$result['original'] = def_module::parseTemplate(($originalPrice?$tpl_original:''), $priceData);
			$result['actual'] = def_module::parseTemplate(($actualPrice?$tpl_actual:''), $priceData);

			return $result;
		}

		/**
		 * Оформление заказа в 1 клик
		 * @param bool $itemType
		 * @param bool $elementId
		 * @return array
		 * @throws publicException
		 */
		public function getOneClickOrder($itemType = false, $elementId = false) {
			$params = array();

			$types = umiObjectTypesCollection::getInstance();
			$form = $types->getTypeByGUID('emarket-purchase-oneclick');

			$dataModule = cmsController::getInstance()->getModule('data');
			$errors = $dataModule->checkRequiredFields($form->getId());
			if ($errors !== true) {
				throw new publicException(getLabel('error-required_one_click_list') . $dataModule->assembleErrorFields($errors));
			}

			$errors = $this->validOneClickInfo();
			if (count($errors) > 0) {
				return $errors;
			}

			if ($itemType && $elementId) {
				$_REQUEST['no-redirect'] = 1;
				$this->basket('put', 'element', $elementId);
			}
			$order = self::getBasketOrder();

			$this->saveOneClickInfo($order);

			if ($order->getTotalAmount() < 1) {
				throw new publicException('%error-market-empty-basket%');
			}

			$order->setValue('order_date', time());
			$numOrder = $order->generateNumber();
			$order->setOrderStatus('waiting');
			$order->commit();
			$params['orderId'] = $numOrder;

			return $params;
		}

		/**
		 * Валидация данных о покупателе в 1 клик
		 * @return array
		 */
		public function validOneClickInfo() {
			$dataForm = getRequest('data');
			$emarketOneClick = umiObjectTypesCollection::getInstance()->getTypeByGUID('emarket-purchase-oneclick');

			$errors = array();
			foreach ($emarketOneClick->getAllFields() as $field) {
				$value = $dataForm['new'][$field->getName()];

				if($restrictionId = $field->getRestrictionId()) {
					$restriction = baseRestriction::get($restrictionId);
					if($restriction instanceof baseRestriction) {
						if($restriction instanceof iNormalizeInRestriction) {
							$value = $restriction->normalizeIn($value);
						}

						if($restriction->validate($value) == false) {
							$fieldTitle = $field->getTitle();

							$errstr = getLabel('error-wrong-field-value');
							$errstr .=  " \"{$fieldTitle}\" - " . $restriction->getErrorMessage();

							$errors['nodes:error'][] = $errstr;
						}
					}
				}

				if (count($errors) > 0) {
					return $errors;
				}
			}
		}

		/**
		 * Сохранение информации о покупателе, совершившем заказ в 1 клик
		 * @param $order
		 * @return array
		 */
		public function saveOneClickInfo($order) {
			$dataForm = getRequest('data');
			$objects = umiObjectsCollection::getInstance();

			$emarketOneClick = umiObjectTypesCollection::getInstance()->getTypeByGUID('emarket-purchase-oneclick');
			$objectId = $objects->addObject($order->getName(), $emarketOneClick->getId());
			$object = $objects->getObject($objectId);

			foreach ($emarketOneClick->getAllFields() as $field) {
				$value = $dataForm['new'][$field->getName()];
				$object->setValue($field->getName(), $value);
			}
			$object->commit();

			if (!customer::get()->isFilled()) {
				foreach ($emarketOneClick->getAllFields() as $field) {
					$value = $dataForm['new'][$field->getName()];
					customer::get()->setValue($field->getName(), $value);
				}
			}

			$order->setValue('purchaser_one_click', $objectId);
			$order->commit();

			return;
		}

		public function createForm($objectType) {
			/** @var DataForms $data */
			$data = cmsController::getInstance()
				->getModule('data');
			$form = $data->getCreateForm($objectType);

			if (array_key_exists('nodes:group', $form) && umiCount($form['nodes:group']) > 0) {
				$form['nodes:group'][0]['attribute:lang'] = Service::LanguageDetector()->detectPrefix();
			}

			return $form;
		}
		
		/**
		 * Формирует счет для юр. лиц и выводит его
		 * @param int $orderId ID заказа с соответствующим способом оплаты
		 * @param string $checkSum контрольная сумма для проверки 
		 * @throws publicException
		 * @return null
		 */
		public function getInvoice($orderId = false, $checkSum = false) {
			if (defined('VIA_HTTP_SCHEME') && VIA_HTTP_SCHEME === true) {
				throw new publicException(getLabel('protocol-execution-not-allowed'));
			}

			if ($orderId === false) {
				$orderId = getRequest('param0');
			}

			if ($checkSum === false) {
				$checkSum = getRequest('param1');
			}

			$config = mainConfiguration::getInstance();
			$rightCheckSum = md5($orderId . $config->get('system', 'salt'));
			
			if ($rightCheckSum !== $checkSum) {
				$this->printDoc(getLabel('no-data-found'));
			}
			
			$order = order::get($orderId);
			
			if (false === $orderId || !$order instanceof order) {
				$this->printDoc(getLabel('no-data-found'));
			}
			$paymentId = $order->getValue('payment_id');
			$payment = payment::get($paymentId, $order);
			
			if (!$payment instanceof invoicePayment) {
				$this->printDoc(getLabel('no-data-found'));
			}
			
			$result = $payment->printInvoice($order);
			$this->printDoc($result);
		}
		
		/**
		 * Возвзращает ссылку, по которой выводится счет для юр. лиц
		 * @param int $orderId ID заказа со счетом
		 * @return string
		 */
		public function getInvoiceLink($orderId) {
			$emptyResult = '';
			$order = order::get($orderId);
			if (!$order instanceof order) {
				return $emptyResult;
			}
			$paymentId = $order->getValue('payment_id');
			$payment = payment::get($paymentId, $order);
			
			if ($payment instanceof invoicePayment) {
				return $payment->getInvoiceLink();
			}
			
			return $emptyResult;
		}

		/**
		 * Производит очистку буфера и помещает в него строку $data
		 * @param string $data
		 * @return null
		 */
		protected function printDoc($data) {
			$buffer = \UmiCms\Service::Response()
				->getHttpDocBuffer();
			$buffer->charset('utf-8');
			$buffer->contentType('text/html');
			$buffer->clear();
			$buffer->push($data);
			$buffer->end();
		}
		
		
	};
?>
