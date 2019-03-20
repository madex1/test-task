<?php
	/**
	 * Реализация авто-заполнения формы заказа
	 *
	 * Реализация поддержки сервисов, для авто-заполнения данных о пользователе:
	 * информация, адрес доставки итп
	 */
	abstract class __emarket_autofill extends def_module {
		/** @var array Поддерживаемые сервисы */
		protected static $services = array("yandex");

		/**
		 * Проверяет, реалиализована ли поддержка заданного сервиса
		 *
		 * @param order $order Заказ
		 * @param string $service [step] Сервис, для запроса данных
		 *
		 * @return string Шаг
		 * @throws privateException Сервис не передан или не поддерживается
		 */
		public function autofillCheckStep(order $order, $service) {
			if (!$service || !in_array($service, self::$services)) {
				throw new privateException("Unknown service");
			} else {
				return $service;
			}
		}

		/**
		 * Проверяет, реалиализована ли поддержка заданного сервиса
		 *
		 * @param order $order Заказ
		 * @param string $service [step] Сервис, для запроса данных
		 *
		 * @return string Шаг
		 * @throws privateException Сервис не передан или не поддерживается
		 */
		public function autofill(order $order, $service) {
			$this->$service($order);

			$cmsController = cmsController::getInstance();
			$urlPrefix = $cmsController->getUrlPrefix() ? ($cmsController->getUrlPrefix() . '/') : '';
			$this->redirect($this->pre_lang . '/' . $urlPrefix . 'emarket/purchase/required/');
		}

		/**
		 * Callback для Yandex. Сервис "Быстрый заказ"
		 *
		 * @see http://help.yandex.ru/partnermarket/?id=1121719
		 *
		 * @param order $order Заказ
		 *
		 * @return bool
		 */
		public function yandex(order $order) {
			if(!isset($_POST["operation_id"]) || !isset($_POST["id"])) {
				return false;
			}

			$dataMapping = array(
				'user' => array(
					'fname' => 'firstname',
					'lname' => 'lastname',
					'father_name' => 'fathersname',
					'email' => 'email',
					'phone' => 'phone'
				),

				'delivery' => array(
					'country' => 'country',
					'index' => 'zip',
					'city' => 'city',
					'street' => 'street',
					'house' => 'building',
					'flat' => 'flat',
					'order_comments' => 'comment'
				)
			);

			$user = customer::get();
			foreach($dataMapping['user'] as $objectKey=>$postKey) {
				$user->setValue($objectKey, getArrayKey($_POST, $postKey));
			}

			$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName("emarket", "delivery_address");
			$objectsCollection = umiObjectsCollection::getInstance();
			$address = $objectsCollection->getObjectByGUID("emarket-delivery_address-yandex" . getArrayKey($_POST, 'id'));
			if ($address) {// адрес найден
				$addressId = $address->getId();
			} else {
				$addressId  = umiObjectsCollection::getInstance()->addObject("Address for customer #" . $user->getId(), $typeId);

				$address = umiObjectsCollection::getInstance()->getObject($addressId);
				$address->setGUID("emarket-delivery_address-yandex" . getArrayKey($_POST, 'id'));
			}

			foreach($dataMapping['delivery'] as $objectKey=>$postKey) {
				$value = getArrayKey($_POST, $postKey);
				if ($value) {
					$address->setValue($objectKey, $value);
				}
			}

			if (!in_array($addressId, $user->delivery_addresses)) {
				$user->setValue("delivery_addresses", array_merge($user->delivery_addresses, array($addressId)));
			}

			$order->setValue("delivery_address", $addressId);

			$user->commit();
			$address->commit();
			$order->commit();
			return true;
		}
	}
?>