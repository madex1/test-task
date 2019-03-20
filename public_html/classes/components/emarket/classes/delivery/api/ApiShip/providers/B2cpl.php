<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	/**
	 * Служба доставки B2cpl
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers
	 */
	class B2cpl extends ApiShip\Provider {

		/** @var string $client идентификатор клиента */
		private $client;

		/** @var string $password пароль */
		private $password;

		/** @const string KEY идентификатор провайдера */
		const KEY = 'b2cpl';

		/** @const string CLIENT_ID_KEY ключ настроек провайдера с идентификатором клиента */
		const CLIENT_ID_KEY = 'client';

		/** @const string PASSWORD_KEY ключ настроек провайдера с паролем */
		const PASSWORD_KEY = 'key';

		/** @const string CLIENT_ID_TITLE расшифровка поля $client */
		const CLIENT_ID_TITLE = 'Id клиента';

		/** @const string PASSWORD_TITLE расшифровка поля $password */
		const PASSWORD_TITLE = 'Ключ/пароль для доступа к сервису';

		/** @inheritdoc */
		public function import(array $data) {
			$valueRequired = true;

			try {
				$this->setClientId(
					$this->getPropertyValue($data, self::CLIENT_ID_KEY, $valueRequired)
				);
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					$this->getEmptySettingParamErrorMessage(self::CLIENT_ID_TITLE)
				);
			}

			try {
				$this->setPassword(
					$this->getPropertyValue($data, self::PASSWORD_KEY, $valueRequired)
				);
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					$this->getEmptySettingParamErrorMessage(self::PASSWORD_TITLE)
				);
			}

			parent::import($data);
		}

		/** @inheritdoc */
		public function export() {
			$data = [
				self::CLIENT_ID_KEY => [
					self::DESCRIPTION_KEY => self::CLIENT_ID_TITLE,
					self::TYPE_KEY => self::STRING_TYPE_KEY,
					self::REQUIRED_KEY => true,
					self::VALUE_KEY => $this->getClientId()
				],
				self::PASSWORD_KEY => [
					self::DESCRIPTION_KEY => self::PASSWORD_TITLE,
					self::TYPE_KEY => self::STRING_TYPE_KEY,
					self::REQUIRED_KEY => true,
					self::VALUE_KEY => $this->getPassword()
				]
			];

			return array_merge($data, parent::export());
		}

		/** @inheritdoc */
		public function getConnectRequestData() {
			return [
				self::CLIENT_ID_KEY => $this->getClientId(),
				self::PASSWORD_KEY => $this->getPassword()
			];
		}

		/** @inheritdoc */
		public function getKey() {
			return self::KEY;
		}

		/** @inheritdoc */
		public function getAllowedDeliveryTypes() {
			return [
				$this->getDeliveryTypeIdToDoor(),
				$this->getDeliveryTypeIdToPoint()
			];
		}

		/** @inheritdoc */
		public function getAllowedPickupTypes() {
			return [
				$this->getPickupTypeIdFromPoint()
			];
		}

		/** @inheritdoc */
		public function isPointIdRequiredForPickupFromPoint() {
			return false;
		}

		/**
		 * Устанавливает идентификатор клиента
		 * @param string $id идентификатор клиента
		 * @return B2cpl
		 */
		public function setClientId($id) {
			$this->validateStringField($id, self::CLIENT_ID_TITLE);
			$this->client = $id;
			return $this;
		}

		/**
		 * Возвращает идентификатор клиента
		 * @return string
		 */
		public function getClientId() {
			return $this->client;
		}

		/**
		 * Устанавливает пароль
		 * @param string $password пароль
		 * @return B2cpl
		 */
		public function setPassword($password) {
			$this->validateStringField($password, self::PASSWORD_TITLE);
			$this->password = $password;
			return $this;
		}

		/**
		 * Возвращает пароль
		 * @return string
		 */
		public function getPassword() {
			return $this->password;
		}
	}
