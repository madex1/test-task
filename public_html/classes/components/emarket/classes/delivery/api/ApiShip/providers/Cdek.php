<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	/**
	 * Служба доставки СДЭК
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers
	 */
	class Cdek extends ApiShip\Provider {

		/** @var string $login учетная запись */
		private $login;

		/** @var string $password пароль */
		private $password;

		/** @const string KEY идентификатор провайдера */
		const KEY = 'cdek';

		/** @const string TOKEN_KEY ключ настроек провайдера с учетной записью */
		const LOGIN_KEY = 'account';

		/** @const string PASSWORD_KEY ключ настроек провайдера с паролем */
		const PASSWORD_KEY = 'password';

		/** string LOGIN_TITLE расшифровка поля $login */
		const LOGIN_TITLE = 'Учетная запись';

		/** string PASSWORD_TITLE расшифровка поля $password */
		const PASSWORD_TITLE = 'Секретный код (secure_password)';

		/** @inheritdoc */
		public function import(array $data) {
			$valueRequired = true;

			try {
				$this->setLogin(
					$this->getPropertyValue($data, self::LOGIN_KEY, $valueRequired)
				);
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					$this->getEmptySettingParamErrorMessage(self::LOGIN_TITLE)
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
				self::LOGIN_KEY => [
					self::DESCRIPTION_KEY => self::LOGIN_TITLE,
					self::TYPE_KEY => self::STRING_TYPE_KEY,
					self::REQUIRED_KEY => true,
					self::VALUE_KEY => $this->getLogin()
				],
				self::PASSWORD_KEY => [
					self::DESCRIPTION_KEY => self::PASSWORD_TITLE,
					self::TYPE_KEY => self::STRING_TYPE_KEY,
					self::REQUIRED_KEY => true,
					self::PASSWORD_KEY => $this->getPassword()
				]
			];

			return array_merge($data, parent::export());
		}

		/** @inheritdoc */
		public function getConnectRequestData() {
			return [
				self::LOGIN_KEY => $this->getLogin(),
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
				$this->getPickupTypeIdFromDoor(),
				$this->getPickupTypeIdFromPoint()
			];
		}

		/** @inheritdoc */
		public function isOrderItemWeightRequired() {
			return true;
		}

		/** @inheritdoc */
		public function isDeliveryTimeIntervalRequired() {
			return true;
		}

		/**
		 * Устанавливает учетную запись
		 * @param string $login учетная запись
		 * @return Cdek
		 */
		public function setLogin($login) {
			$this->validateStringField($login, self::LOGIN_TITLE);
			$this->login = $login;
			return $this;
		}

		/**
		 * Возвращает учетную запись
		 * @return string
		 */
		public function getLogin() {
			return $this->login;
		}

		/**
		 * Устанавливает пароль
		 * @param string $password пароль
		 * @return Cdek
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
