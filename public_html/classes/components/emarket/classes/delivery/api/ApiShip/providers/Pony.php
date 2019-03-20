<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	/**
	 * Служба доставки PONY EXPRESS
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers
	 */
	class Pony extends ApiShip\Provider {

		/** @var string $soapKey ключ для доступа */
		private $soapKey;

		/** @const string KEY идентификатор провайдера */
		const KEY = 'pony';

		/** @const string SOAP_KEY ключ настроек провайдера с ключом доступа */
		const SOAP_KEY = 'soapKey';

		/** @const string SOAP_KEY_TITLE расшифровка поля $soapKey */
		const SOAP_KEY_TITLE = 'Ключ доступа партнера к универсальному интерфейсу Пони Экспресс';

		/** @inheritdoc */
		public function import(array $data) {
			$valueRequired = true;

			try {
				$this->setSoapKey(
					$this->getPropertyValue($data, self::SOAP_KEY, $valueRequired)
				);
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					$this->getEmptySettingParamErrorMessage(self::SOAP_KEY_TITLE)
				);
			}

			parent::import($data);
		}

		/** @inheritdoc */
		public function export() {
			$data = [
				self::SOAP_KEY => [
					self::DESCRIPTION_KEY => self::SOAP_KEY_TITLE,
					self::TYPE_KEY => self::STRING_TYPE_KEY,
					self::REQUIRED_KEY => true,
					self::VALUE_KEY => $this->getSoapKey()
				]
			];

			return array_merge($data, parent::export());
		}

		/** @inheritdoc */
		public function getConnectRequestData() {
			return [
				self::SOAP_KEY => $this->getSoapKey()
			];
		}

		/** @inheritdoc */
		public function getKey() {
			return self::KEY;
		}

		/** @inheritdoc */
		public function getAllowedDeliveryTypes() {
			return [
				$this->getDeliveryTypeIdToDoor()
			];
		}

		/** @inheritdoc */
		public function getAllowedPickupTypes() {
			return [
				$this->getPickupTypeIdFromDoor()
			];
		}

		/**
		 * Устанавливает ключ доступа
		 * @param string $soapKey ключ доступа
		 * @return Pony
		 */
		public function setSoapKey($soapKey) {
			$this->validateStringField($soapKey, self::SOAP_KEY_TITLE);
			$this->soapKey = $soapKey;
			return $this;
		}

		/**
		 * Возвращает ключ доступа
		 * @return string
		 */
		public function getSoapKey() {
			return $this->soapKey;
		}
	}
