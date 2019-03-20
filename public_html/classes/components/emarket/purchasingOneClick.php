<?php

	use UmiCms\Service;

	/** Класс функционала оформления заказа в 1 клик */
	class EmarketPurchasingOneClick {

		/** @var emarket|EmarketPurchasingOneClick|EmarketMacros $module */
		public $module;

		/**
		 * Клиентский метод.
		 * Выводит данные для построения формы создания заказа в клик
		 * @param int $objectType идентификатор типа данных заказа в клик
		 * @return mixed
		 */
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
		 * Клиентский метод.
		 * Принимает данные формы создания заказа в 1 клик и формирует на их основе заказ.
		 * Если указаны оба параметра - добавляет товар в корзину перед тем, как оформить заказ.
		 * Возвращает данные созданного заказа.
		 *
		 * @param mixed $addProductToCart Флаг добавления товара в корзину
		 * @param bool|int $elementId Идентификатор товара (объекта каталога)
		 * @return array
		 * @throws publicException
		 */
		public function getOneClickOrder($addProductToCart = false, $elementId = false) {
			$this->validateRequiredFields();
			$errors = $this->validateOneClickInfo();
			if (umiCount($errors) > 0) {
				return $errors;
			}

			$order = $this->processOrder($addProductToCart, $elementId);
			return ['orderId' => $order->getNumber()];
		}

		/** Проверяет заполненность обязательных полей в форме оформления заказа */
		private function validateRequiredFields() {
			/** @var data $data */
			$data = cmsController::getInstance()->getModule('data');
			$type = $this->getOneClickOrderType();

			$errors = $data->checkRequiredFields($type->getId());
			if (is_array($errors)) {
				$message = getLabel('error-required_one_click_list') . $data->assembleErrorFields($errors);
				throw new publicException($message);
			}
		}

		/**
		 * Валидирует данные формы создания заказа в один клик и возвращает полученные ошибки
		 * @return array
		 */
		public function validateOneClickInfo() {
			$dataForm = getRequest('data');
			$oneClickOrderType = $this->getOneClickOrderType();
			$errors = [];

			foreach ($oneClickOrderType->getAllFields() as $field) {
				$value = $dataForm['new'][$field->getName()];
				$restriction = baseRestriction::get($field->getRestrictionId());

				if (!$restriction) {
					continue;
				}

				if ($restriction instanceof iNormalizeInRestriction) {
					$value = $restriction->normalizeIn($value);
				}

				if ($restriction->validate($value)) {
					continue;
				}

				$fieldTitle = $field->getTitle();
				$errorMessage = getLabel('error-wrong-field-value');
				$errorMessage .= " \"{$fieldTitle}\" - " . $restriction->getErrorMessage();
				$errors['nodes:error'][] = $errorMessage;

				if (umiCount($errors) > 0) {
					return $errors;
				}
			}

			return $errors;
		}

		/**
		 * Оформляет заказ в один клик и возвращает объект заказа.
		 * @param mixed $addProductToCart Флаг добавления товара в корзину
		 * @param bool|int $elementId Идентификатор товара (объекта каталога)
		 * @return order
		 */
		private function processOrder($addProductToCart, $elementId) {
			$previousBasket = $this->module->getBasketOrder();
			$oneClickBasket = $previousBasket;

			if ($addProductToCart && $elementId) {
				$oneClickBasket = order::create();
				$this->module->setCurrentBasket($oneClickBasket);

				$_REQUEST['no-redirect'] = 1;
				$this->module->basket('put', 'element', $elementId);
			}

			$this->module->setCurrentBasket($previousBasket);

			$domainId = Service::DomainDetector()->detectId();
			customer::get()->setLastOrder($previousBasket->getId(), $domainId);

			$this->saveOneClickInfo($oneClickBasket);

			if ($oneClickBasket->getTotalAmount() < 1) {
				throw new publicException('%error-market-empty-basket%');
			}

			$oneClickBasket->order();
			return $oneClickBasket;
		}

		/**
		 * Создает заказ в один клик, заполняет его и покупателя данными из формы
		 * @param order $order текущая корзина
		 * @throws coreException
		 */
		public function saveOneClickInfo(order $order) {
			$umiObjects = umiObjectsCollection::getInstance();

			$oneClickOrderType = $this->getOneClickOrderType();
			$oneClickCustomerId = $umiObjects->addObject($order->getName(), $oneClickOrderType->getId());
			$oneClickCustomer = $umiObjects->getObject($oneClickCustomerId);

			$this->saveCustomer($oneClickCustomer);
			$oneClickCustomer->commit();

			$regularCustomer = customer::get();

			if (!$regularCustomer->isFilled()) {
				$this->saveCustomer($regularCustomer);
				$regularCustomer->commit();
			}

			$order->setValue('purchaser_one_click', $oneClickCustomerId);
			$order->commit();
		}

		/**
		 * Сохраняет информацию о покупателе из формы заказа в один клик
		 * @param iUmiObject|umiObjectProxy $customer объект покупателя
		 */
		private function saveCustomer($customer) {
			$oneClickOrderType = $this->getOneClickOrderType();
			$dataForm = getRequest('data');

			foreach ($oneClickOrderType->getAllFields() as $field) {
				$value = $dataForm['new'][$field->getName()];
				$customer->setValue($field->getName(), $value);
			}
		}

		/**
		 * Возвращает тип данных "Заказ в один клик"
		 * @return iUmiObjectType
		 */
		private function getOneClickOrderType() {
			return umiObjectTypesCollection::getInstance()
				->getTypeByGUID('emarket-purchase-oneclick');
		}

		/** @deprecated */
		public function validOneClickInfo() {
			return $this->validateOneClickInfo();
		}
	}
