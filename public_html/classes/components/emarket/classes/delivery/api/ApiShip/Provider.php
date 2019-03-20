<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils\ArgumentsValidator;

	/**
	 * Класс провайдера (службы доставки, сд)
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip
	 */
	abstract class Provider implements iProvider {

		/** @var bool $isCODAllowed поддерживается ли работа с наложенными платежами */
		private $isCODAllowed = false;

		/** @var bool $isConnected был ли подключен провайдер */
		private $isConnected = false;

		/** @inheritdoc */
		public function import(array $data) {
			if (isset($data[self::IS_COD_ALLOWED_KEY][self::VALUE_KEY])) {
				$this->setCODAllowedStatus($data[self::IS_COD_ALLOWED_KEY][self::VALUE_KEY]);
			}

			if (isset($data[self::IS_CONNECTED_KEY][self::VALUE_KEY])) {
				$this->setIsConnectedFlag($data[self::IS_CONNECTED_KEY][self::VALUE_KEY]);
			}

			return $this;
		}

		/** @inheritdoc */
		public function export() {
			$data = [
				self::IS_COD_ALLOWED_KEY => [
					self::DESCRIPTION_KEY => self::IS_COD_ALLOWED_KEY_TITLE,
					self::TYPE_KEY => self::BOOL_TYPE_KEY,
					self::REQUIRED_KEY => false
				],
				self::IS_CONNECTED_KEY => [
					self::VALUE_KEY => $this->isConnected()
				]
			];

			if ($this->isCODAllowed() !== null) {
				$data[self::IS_COD_ALLOWED_KEY][self::VALUE_KEY] = $this->isCODAllowed();
			}

			return $data;
		}

		/** @inheritdoc */
		abstract public function getConnectRequestData();

		/** @inheritdoc */
		abstract public function getKey();

		/** @inheritdoc */
		abstract public function getAllowedDeliveryTypes();

		/** @inheritdoc */
		abstract public function getAllowedPickupTypes();

		/** @inheritdoc */
		public function isCODAllowed() {
			return $this->isCODAllowed;
		}

		/** @inheritdoc */
		public function setCODAllowedStatus($status) {
			$this->isCODAllowed = (bool) $status;
			return $this;
		}

		/** @inheritdoc */
		public function isConnected() {
			return $this->isConnected;
		}

		/** @inheritdoc */
		public function setIsConnectedFlag($flag) {
			$this->isConnected = (bool) $flag;
			return $this;
		}

		/** @inheritdoc */
		public function isPointIdRequiredForPickupFromPoint() {
			return true;
		}

		/** @inheritdoc */
		public function isRecipientCitySupported(RequestDataParts\iCity $city) {
			return true;
		}

		/** @inheritdoc */
		public function areOrderDimensionsRequired() {
			return false;
		}

		/** @inheritdoc */
		public function isDeliveryDateRequired() {
			return true;
		}

		/** @inheritdoc */
		public function isPickupDateRequired() {
			return true;
		}

		/** @inheritdoc */
		public function isOrderItemWeightRequired() {
			return false;
		}

		/** @inheritdoc */
		public function isDeliveryTimeIntervalRequired() {
			return false;
		}

		/**
		 * Проверяет задано ли значение свойства
		 * @param array $data данные провайдера
		 * @param string $propertyName название свойства провайдера
		 * @param bool $required обязательно ли свойство должно иметь значение
		 * @return bool результат проверки
		 * @throws \wrongParamException если передано $required = true и свойство не имеет значение
		 */
		protected function issetPropertyValue(array $data, $propertyName, $required = false) {
			try {
				ArgumentsValidator::arrayContainsValue($data, $propertyName, __METHOD__, $propertyName);
				ArgumentsValidator::arrayContainsValue($data[$propertyName], $propertyName, __METHOD__, self::VALUE_KEY);
			} catch (\wrongParamException $e) {
				if ($required) {
					throw $e;
				}

				return false;
			}

			return true;
		}

		/**
		 * Возвращает значени свойства
		 * @param array $data данные провайдера
		 * @param string $propertyName название свойства провайдера
		 * @param bool $required обязательно ли свойство должно иметь значение
		 * @return mixed
		 * @throws \publicAdminException если передано $required = true и свойство не имеет значение
		 */
		protected function getPropertyValue(array $data, $propertyName, $required = false) {
			if (!$this->issetPropertyValue($data, $propertyName, $required)) {
				return null;
			}

			return $data[$propertyName][self::VALUE_KEY];
		}

		/**
		 * Валидирует строковое значение свойства
		 * @param mixed $value значение свойства
		 * @param string $field имя свойства
		 */
		protected function validateStringField($value, $field) {
			ArgumentsValidator::notEmptyString($value, $field, __METHOD__);
		}

		/**
		 * Возвращает идентификатор типа доставки "До двери"
		 * @return string
		 */
		protected function getDeliveryTypeIdToDoor() {
			return (string) new Enums\DeliveryTypes(Enums\DeliveryTypes::TO_DOOR);
		}

		/**
		 * Возвращает идентификатор типа доставки "До пункта выдачи"
		 * @return string
		 */
		protected function getDeliveryTypeIdToPoint() {
			return (string) new Enums\DeliveryTypes(Enums\DeliveryTypes::TO_POINT);
		}

		/**
		 * Возвращает идентификатор типа отгрузки "От двери"
		 * @return string
		 */
		protected function getPickupTypeIdFromDoor() {
			return (string) new Enums\PickupTypes(Enums\PickupTypes::FROM_DOOR);
		}

		/**
		 * Возвращает идентификатор типа отгрузки "От пункта выдачи"
		 * @return string
		 */
		protected function getPickupTypeIdFromPoint() {
			return (string) new Enums\PickupTypes(Enums\PickupTypes::FROM_POINT);
		}

		/**
		 * Формирует сообщение об ошибке отсутствия значения для параметра настроек провайдера
		 * @param string $paramName название параметра
		 * @return string сформированное сообщение об ошибке
		 */
		protected function getEmptySettingParamErrorMessage($paramName) {
			$messageFormat = getLabel('label-api-ship-error-incorrect-provider-empty-parameter', self::I18N_PATH);
			return sprintf($messageFormat, $this->getKey(), $paramName);
		}
	}
