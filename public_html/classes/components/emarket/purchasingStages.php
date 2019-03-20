<?php

	use UmiCms\Service;

	/**
	 * Класс этапов оформления заказа.
	 * Содержит в себе логику обработки каждого этапа.
	 *
	 * Точка входа в процесс оформления заказа:
	 * @link /emarket/purchase/
	 *
	 * По умолчанию системное оформление заказа может содержать следующие этапы:
	 *
	 * 1) "Автозаполнение данных";
	 * @link /emarket/purchase/autofill/
	 *
	 * 2) "Ввод обязательных данных";
	 * @link /emarket/purchase/required/
	 *
	 * 3) "Доставка";
	 * @link /emarket/purchase/delivery/
	 *
	 * 4) "Оплата";
	 * @link /emarket/purchase/payment/
	 *
	 * 5) "Результат";
	 * @link /emarket/purchase/result/
	 * Каждый этап может содержать свои собственные шаги, либо варианты реализации.
	 *
	 * 1.1) API Быстрого заказа от Яндекс;
	 * @link /emarket/purchase/autofill/yandex
	 *
	 * 2.2) Ввод обязательных данных пользователя;
	 * @link /emarket/purchase/required/personal
	 *
	 * 3.1) Выбор|ввод адреса доставки:
	 * @link /emarket/purchase/delivery/address
	 *
	 * 3.2) Выбор способа доставки;
	 * @link /emarket/purchase/delivery/choose
	 *
	 * 4.1) Выбор способа оплаты;
	 * @link /emarket/purchase/payment/choose
	 *
	 * 4.2) Оплата бонусами;
	 * @link /emarket/purchase/payment/bonus
	 *
	 * 4.n) Оплата с помощью платежной системы;
	 * @link /emarket/purchase/payment/название_платежной системы
	 *
	 * 5.1) Удачное завершение оформления;
	 * @link /emarket/purchase/result/successful
	 *
	 * 5.2) Неудачное завершение оформления;
	 * @link /emarket/purchase/result/fail
	 *
	 * Реализации шагов этапов оформления расположена в классе EmarketPurchasingStagesSteps.
	 */
	class EmarketPurchasingStages {

		/** @var emarket $module */
		public $module;

		/** @var array $autoFillServices поддерживаемые сервисы этапа оформления "Автозаполнение данных" */
		public $autoFillServices = ['yandex'];

		/** @var array $requiredSteps шаги этапа оформления заказа "Ввод обязательных данных" */
		public $requiredSteps = ['personal'];

		/** @var array $deliverySteps шаги этапа оформления заказа "Доставка" */
		public $deliverySteps = ['address', 'choose'];

		/** @var array $paymentSteps шаги этапа оформления заказа "Оплата" */
		public $paymentSteps = ['choose', 'bonus'];

		/** @var array $paymentAllowedServices подключенные платежные системы */
		public $paymentAllowedServices = [];

		/** @var array $finishStageResults варианты этапа оформления заказа "Результат" */
		public $finishStageResults = ['fail', 'successful'];

		/**
		 * Конструктор.
		 * Добавляет в шаг оформления заказа "Оплата" этапы платежных систем.
		 * @param emarket $module базовый класс модуля
		 * @throws selectorException
		 */
		public function __construct(emarket $module) {
			$paymentsTypes = payment::getUsedPaymentsTypes();
			/** @var iUmiObject $paymentType */
			foreach ($paymentsTypes as $paymentType) {
				$this->paymentAllowedServices[] = $paymentType->getValue('class_name');
			}
		}

		/**
		 * Возвращает список этапов оформления заказа со статусами их завершения
		 * @param string $template имя шаблона (для tpl)
		 * @param string $stage идентификатор текущего шага оформления
		 * @return mixed
		 */
		public function getPurchaseSteps($template = 'default', $stage) {
			$activeStageNumber = array_search($stage, $this->module->purchaseStages);
			$activeStageNumber = $activeStageNumber === false ? -1 : $activeStageNumber;

			list(
				$completeStepTemplate,
				$activeStepTemplate,
				$incompleteStepTemplate,
				$purchaseStepsTemplate
				) = emarket::loadTemplates(
				'emarket/' . $template,
				'complete_step',
				'active_step',
				'incomplete_step',
				'purchase_steps'
			);

			$steps = [];

			foreach ($this->module->purchaseStages as $i => $stage) {

				if ($activeStageNumber == $i) {
					$template = $activeStepTemplate;
					$status = 'active';
				} else {
					$template = $activeStageNumber > $i ? $incompleteStepTemplate : $completeStepTemplate;
					$status = $activeStageNumber > $i ? 'complete' : 'incomplete';
				}

				$steps[] = emarket::parseTemplate($template, [
					'@name' => getLabel('header-emarket-' . $stage),
					'@link' => '/emarket/purchase/' . $stage,
					'@status' => $status,
				]);
			}

			return emarket::parseTemplate($purchaseStepsTemplate, [
				'+item' => $steps
			]);
		}

		/**
		 * Возвращает название этапа оформления заказа который нужно выполнить
		 * @param string $stage название этапа оформления заказа, который был запрошен
		 * @return bool|null|string
		 */
		public function getStage($stage) {
			$umiRegistry = Service::Registry();
			$hasDelivery = $umiRegistry->get('//modules/emarket/enable-delivery');
			$hasPayment = $umiRegistry->get('//modules/emarket/enable-payment');

			if ($stage == 'delivery' && !$hasDelivery) {
				$stage = 'payment';
			}

			if ($stage == 'payment' && !$hasPayment) {
				return null;
			}

			if (!$stage ||
				(!in_array($stage, $this->module->purchaseStages) && !in_array($stage, $this->module->availableStages))) {
				$customer = customer::get();

				if (!$customer->isUser() && !$customer->isFilled()) {
					return 'required';
				}

				return getArrayKey($this->module->purchaseStages, 1);
			}

			return $stage;
		}

		/**
		 * Обрабатывает заданный шаг этапа оформления заказа
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws publicException
		 * @throws coreException
		 */
		public function purchase($template = 'default') {
			/** @var emarket|EmarketPurchasingStages|EmarketPurchasingStagesSteps $module ; */
			$module = $this->module;
			list($purchaseTemplate) = emarket::loadTemplates(
				'emarket/' . $template,
				'purchase'
			);

			$stage = getRequest('param0');
			$step = getRequest('param1');
			$mode = getRequest('param2');

			$byUrl = $stage && $step;
			$order = $module->getBasketOrder();

			if ($order->isEmpty() && $stage != 'result') {
				throw new publicException('%error-market-empty-basket%');
			}

			$stage = $module->getStage($stage);

			if (umiCount($module->purchaseStages) == 2 && $stage == 'result' && !getRequest('param0')) {
				$stage = '';
			}

			$controller = cmsController::getInstance();

			if (!$stage) {
				$order->order();
				$urlPrefix = $controller->getUrlPrefix() ? ($controller->getUrlPrefix() . '/') : '';
				$module->redirect($module->pre_lang . '/' . $urlPrefix . 'emarket/purchase/result/successful/');
			}

			$checkStepMethod = $stage . 'CheckStep';
			$step = $this->$checkStepMethod($order, $step);

			if (!$step || !$byUrl) {
				$step = $step ?: 'choose';
				$urlPrefix = $controller->getUrlPrefix() ? ($controller->getUrlPrefix() . '/') : '';
				$module->redirect($module->pre_lang . "/{$urlPrefix}emarket/purchase/{$stage}/{$step}/");
			}

			$stageResult = $this->$stage($order, $step, $mode, $template);

			$result = [
				'order' => [
					'attribute:id' => $order->getId()
				],
				'purchasing' => [
					'attribute:stage' => $stage,
					'attribute:step' => $step,
					'steps' => $this->getPurchaseSteps($template, $stage)
				]
			];

			$module->setHeader("%header-{$stage}-{$step}%");

			if (is_array($stageResult)) {
				$result['purchasing'] = array_merge($result['purchasing'], $stageResult);
			} elseif (emarket::isXSLTResultMode()) {
				throw new publicException("Incorrect return value from {$stage}() purchasing method");
			} else {
				$result['purchasing'] = $stageResult;
			}

			return emarket::parseTemplate($purchaseTemplate, $result);
		}

		/**
		 * Валидирует сервис для этапа оформления "Автозаполнение данных покупателя"
		 * и возвращает имя сервиса, который должен быть применен
		 * @param order $order оформляемый заказ
		 * @param string $service имя сервиса для автозаполнения данных покупателя
		 * @return string
		 * @throws privateException
		 */
		public function autofillCheckStep(order $order, $service) {
			if (!$service || !in_array($service, $this->autoFillServices)) {
				throw new privateException('Unknown service');
			}

			return $service;
		}

		/**
		 * Выполняет этап оформления заказа - автозаполнения данных покупателя
		 * и перенаправляет на шаг оформления заказа - ввод данных покупателя
		 *
		 * Поддерживает одну реализацию:
		 *
		 * 1) API Быстрого заказа от Яндекс
		 * @link /emarket/purchase/autofill/yandex
		 *
		 * @link /emarket/purchase/autofill/$service
		 * @param order $order оформляемый заказ
		 * @param string $service имя сервиса для автозаполнения данных покупателя
		 * @throws coreException
		 */
		public function autofill(order $order, $service) {
			/** @var emarket|EmarketPurchasingStages|EmarketPurchasingStagesSteps $module ; */
			$module = $this->module;
			$module->$service($order);

			$cmsController = cmsController::getInstance();
			$urlPrefix = $cmsController->getUrlPrefix() ? ($cmsController->getUrlPrefix() . '/') : '';
			$this->module->redirect($this->module->pre_lang . '/' . $urlPrefix . 'emarket/purchase/required/');
		}

		/**
		 * Валидирует шаг этапа оформления заказа "Заполнение данных покупателя" и возвращает имя шага,
		 * который должен быть выполнен
		 * @param order $order оформляемый заказ
		 * @param string $step имя шага
		 * @return mixed
		 * @throws privateException
		 */
		public function requiredCheckStep(order $order, $step) {
			$steps = $this->requiredSteps;

			if (!$step) {
				return $steps[0];
			}

			if (in_array($step, $steps)) {
				return $step;
			}

			throw new privateException("Unknown order required check stage \"{$step}\"");
		}

		/**
		 * Выполняет шаг этапа оформления заказа "Заполнение обязательных данных".
		 * Если режим работы = "do", то сохраняет данные, иначе возвращает данные.
		 *
		 * Поддерживает один шаг:
		 *
		 * 1) Заполнение данных покупателя;
		 * @link /emarket/purchase/required/personal/;
		 *
		 * @link /emarket/purchase/required/$stage/
		 * @param order $order оформляемый заказ
		 * @param string $step имя шага
		 * @param string $mode режим работы
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 */
		public function required(order $order, $step, $mode, $template) {
			/** @var emarket|EmarketPurchasingStages|EmarketPurchasingStagesSteps $module ; */
			$module = $this->module;
			switch ($step) {
				case 'personal': {
					return ($mode == 'do')
						? $module->savePersonalInfo($order)
						: $module->editPersonalInfo($order, $template);
				}
			}
		}

		/**
		 * Валидирует шаг этапа оформления заказа "Доставка" и возвращает имя шага,
		 * который должен быть выполнен
		 * @param order $order оформляемый заказ
		 * @param string $step имя шага
		 * @return mixed
		 * @throws privateException
		 */
		public function deliveryCheckStep(order $order, $step) {
			$steps = $this->deliverySteps;

			if (!$step) {
				return $steps[0];
			}

			if (in_array($step, $steps)) {
				return $step;
			}

			throw new privateException("Unknown order delivery stage \"{$step}\"");
		}

		/**
		 * Выполняет шаг этапа оформления заказа "Доставка".
		 * Если режим работы = "do", то сохраняет данные, иначе возвращает данные.
		 *
		 * Поддерживает два шага:
		 *
		 * 1) Ввод адреса доставки;
		 * @link /emarket/purchase/delivery/address/
		 *
		 * 2) Выбор способа доставки;
		 * @link /emarket/purchase/delivery/choose/
		 *
		 * @link /emarket/purchase/delivery/$stage/
		 * @param order $order оформляемый заказ
		 * @param string $step имя шага
		 * @param string $mode режим работы
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws coreException
		 */
		public function delivery(order $order, $step, $mode, $template) {
			/** @var emarket|EmarketPurchasingStages|EmarketPurchasingStagesSteps $module ; */
			$module = $this->module;
			switch ($step) {
				case 'address' : {
					return ($mode == 'do')
						? $module->chooseDeliveryAddress($order)
						: $module->renderDeliveryAddressesList($order, $template);
				}
				case 'choose': {
					return ($mode == 'do')
						? $module->chooseDelivery($order)
						: $module->renderDeliveryList($order, $template);
				}
			}
		}

		/**
		 * Валидирует шаг этапа оформления заказа "Оплата" и возвращает имя шага,
		 * который должен быть выполнен
		 * @param order $order оформляемый заказ
		 * @param string $step имя шага
		 * @return bool|string
		 * @throws privateException
		 */
		public function paymentCheckStep(order $order, $step) {
			$steps = $this->paymentSteps;
			$services = $this->paymentAllowedServices;

			if (!$step) {
				return $steps[0];
			}

			if ($step == 'bonus' && !customer::get()->getValue('bonus')) {
				return false;
			}

			if (in_array($step, $steps) || in_array($step, $services)) {
				return $step;
			}

			throw new privateException("Unknown order payment stage \"{$step}\"");
		}

		/**
		 * Выполняет шаг этапа оформления заказа "Оплата".
		 * Если режим работы = "do", то сохраняет данные, иначе возвращает данные.
		 *
		 * Поддерживает переменное количество шагов:
		 *
		 * 1) Выбор способа оплаты;
		 * @link /emarket/purchase/payment/choose/
		 *
		 * 2) Оплата бонусами, если у пользователя они есть;
		 * @link /emarket/purchase/payment/bonus/
		 *
		 * n) Оплата для каждого добавленного типа способа оплаты, например;
		 * @link /emarket/purchase/payment/yandex30/
		 *
		 * @link /emarket/purchase/payment/$step/
		 * @param order $order оформляемый заказ
		 * @param string $step имя шага
		 * @param string $mode режим работы
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws coreException
		 */
		public function payment(order $order, $step, $mode, $template) {
			/** @var emarket|EmarketPurchasingStages|EmarketPurchasingStagesSteps $module ; */
			$module = $this->module;
			switch (true) {
				case $step == 'choose' : {
					return ($mode == 'do')
						? $module->choosePayment($order)
						: $module->renderPaymentsList($order, $template);
				}
				case $step == 'bonus' : {
					return ($mode == 'do') ? $module->payByBonus($order) : $module->renderBonusPayment($order, $template);
				}
				case in_array($step, $this->paymentAllowedServices) : {
					$paymentId = $order->getValue('payment_id');
					$payment = null;

					if ($paymentId) {
						$payment = payment::get($paymentId, $order);
					}

					if ($payment instanceof payment) {
						return $payment->process($template);
					}
				}
			}
		}

		/**
		 * Валидирует результат этапа оформления заказа "Результат" и возвращает имя результата,
		 * который должен быть показан
		 * @param order $order оформляемый заказ
		 * @param string $result имя результата
		 * @return string
		 * @throws privateException
		 */
		public function resultCheckStep(order $order, $result) {
			$results = $this->finishStageResults;

			if (!$result) {
				return $results[0];
			}

			if (in_array($result, $results)) {
				return $result;
			}

			throw new privateException("Unknown order result \"{$result}\"");
		}

		/**
		 * Возвращает результат этапа оформления заказа "Результат"
		 * Поддерживает два результата
		 *
		 * 1) Оформление заказа успешно завершено;
		 * @link /emarket/purchase/result/successful/
		 *
		 * 2) Оформление заказа завершено некорректно;
		 * @link /emarket/purchase/result/fail/
		 *
		 * @link /emarket/purchase/result/$stage/
		 * @param order $order оформляемый заказ
		 * @param string $result имя результата
		 * @param string $mode режим работы
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws selectorException
		 */
		public function result(order $order, $result, $mode, $template) {
			/** @var emarket|EmarketPurchasingStages|EmarketPurchasingStagesSteps $module ; */
			$module = $this->module;
			switch ($result) {
				case 'successful':
				case 'fail' : {
					return $module->formResult($order, $result, $mode, $template);
				}
			}
		}
	}

