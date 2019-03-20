<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	/**
	 * Служба доставки IML
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers
	 */
	class Iml extends ApiShip\Provider {

		/** @var string $user логин */
		private $user;

		/** @var string $password пароль */
		private $password;

		/** @var bool $test включен ли тестовый режим */
		private $test;

		/** @const string KEY идентификатор провайдера */
		const KEY = 'iml';

		/** @const string LOGIN_KEY ключ настроек провайдера с логином */
		const LOGIN_KEY = 'user';

		/** @const string LOGIN_KEY ключ настроек провайдера с паролем */
		const PASSWORD_KEY = 'pass';

		/** @const string TEST_MODE_KEY ключ настроек провайдера с тестовым режимом */
		const TEST_MODE_KEY = 'test';

		/** @const string LOGIN_TITLE расшифровка свойства $user */
		const LOGIN_TITLE = 'Логин от кабинета iml';

		/** @const string PASSWORD_TITLE расшифровка свойства $password */
		const PASSWORD_TITLE = 'Пароль';

		/** @const string TEST_MODE_TITLE расшифровка свойства $test */
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
				self::TEST_MODE_KEY => $this->getTestMode(),
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

		/**
		 * Устанавливает логин
		 * @param string $login логин
		 * @return Iml
		 */
		public function setLogin($login) {
			$this->validateStringField($login, self::LOGIN_TITLE);
			$this->user = $login;
			return $this;
		}

		/**
		 * Возвращает логин
		 * @return string
		 */
		public function getLogin() {
			return $this->user;
		}

		/**
		 * Устанавливает пароль
		 * @param string $password пароль
		 * @return Iml
		 */
		public function setPassword($password) {
			$this->validateStringField($password, self::PASSWORD_KEY);
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
		 * Устанавливает режим тестирования
		 * @param bool $mode режим тестирования
		 * @return Iml
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
