<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	/**
	 * Служба доставки Hermes Russia
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers
	 */
	class Hermes extends ApiShip\Provider {

		/** @var string $login логин */
		private $login;

		/** @var string $password Пароль */
		private $password;

		/** @var string $businessUnitCode код бизнес юнита */
		private $businessUnitCode;

		/** @var bool $test включен ли тестовый режим */
		private $test;

		/** @const string KEY идентификатор провайдера */
		const KEY = 'hermes';

		/** @const string LOGIN_KEY ключ настроек провайдера с логином */
		const LOGIN_KEY = 'login';

		/** @const string PASSWORD_KEY ключ настроек провайдера с паролем */
		const PASSWORD_KEY = 'password';

		/** @const string BUSINESS_UNIT_KEY ключ настроек провайдера с кодом бизнес юнита */
		const BUSINESS_UNIT_KEY = 'businessUnitCode';

		/** @const string TEST_MODE_KEY ключ настроек провайдера с тестовым режимом */
		const TEST_MODE_KEY = 'test';

		/** @const string LOGIN_TITLE расшифровка поля $login */
		const LOGIN_TITLE = 'Логин';

		/** @const string LOGIN_TITLE расшифровка поля $password */
		const PASSWORD_TITLE = 'Пароль';

		/** @const string LOGIN_TITLE расшифровка поля $businessUnitCode */
		const BUSINESS_UNIT_TITLE = 'Бизнес-юнит – структурное подразделение клиента';

		/** @const string LOGIN_TITLE расшифровка поля $test */
		const TEST_MODE_TITLE = 'Тестовый доступ';

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

			$this->setBusinessUnitCode(
				$this->getPropertyValue($data, self::BUSINESS_UNIT_KEY, $valueRequired)
			);

			$this->setTestMode(
				$this->getPropertyValue($data, self::TEST_MODE_KEY, $valueRequired)
			);

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
					self::VALUE_KEY => $this->getPassword()
				],
				self::BUSINESS_UNIT_KEY => [
					self::DESCRIPTION_KEY => self::BUSINESS_UNIT_TITLE,
					self::TYPE_KEY => self::STRING_TYPE_KEY,
					self::REQUIRED_KEY => true,
					self::VALUE_KEY => $this->getBusinessUnitCode()
				],
				self::TEST_MODE_KEY => [
					self::DESCRIPTION_KEY => self::TEST_MODE_TITLE,
					self::TYPE_KEY => self::BOOL_TYPE_KEY,
					self::REQUIRED_KEY => true,
					self::VALUE_KEY => $this->getTestMode()
				]
			];

			return array_merge($data, parent::export());
		}

		/** @inheritdoc */
		public function getConnectRequestData() {
			return [
				self::LOGIN_KEY => $this->getLogin(),
				self::PASSWORD_KEY => $this->getPassword(),
				self::BUSINESS_UNIT_KEY => $this->getBusinessUnitCode(),
				self::TEST_MODE_KEY => $this->getTestMode()
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
		 * Устанавливает логин
		 * @param string $login логин
		 * @return Hermes
		 */
		public function setLogin($login) {
			$this->validateStringField($login, self::LOGIN_TITLE);
			$this->login = $login;
			return $this;
		}

		/**
		 * Возвращает логин
		 * @return string
		 */
		public function getLogin() {
			return $this->login;
		}

		/**
		 * Устанавливает пароль
		 * @param string $password пароль
		 * @return Hermes
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

		/**
		 * Устанавливает код бизнес юнита
		 * @param string $code код бизнес юнита
		 * @return Hermes
		 */
		public function setBusinessUnitCode($code) {
			$this->validateStringField($code, self::BUSINESS_UNIT_TITLE);
			$this->businessUnitCode = $code;
			return $this;
		}

		/**
		 * Возвращает код бизнес юнита
		 * @return string
		 */
		public function getBusinessUnitCode() {
			return $this->businessUnitCode;
		}

		/**
		 * Устанавливает режим тестирования
		 * @param bool $mode режим тестирования
		 * @return Hermes
		 */
		public function setTestMode($mode) {
			$this->test = (bool) $mode;
			return $this;
		}

		/**
		 * Возвращает режим тестирования
		 * @return bool
		 */
		public function getTestMode() {
			return $this->test;
		}
	}
