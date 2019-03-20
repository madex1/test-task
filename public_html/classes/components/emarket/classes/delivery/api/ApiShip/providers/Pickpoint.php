<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	/**
	 * Служба доставки PickPoint
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers
	 */
	class Pickpoint extends ApiShip\Provider {

		/** @var string $login логин */
		private $login;

		/** @var string $password пароль */
		private $password;

		/** @var string $ikn ИКН – номер договора */
		private $ikn;

		/** @var bool $test включен ли тестовый режим */
		private $test;

		/** @const string KEY идентификатор провайдера */
		const KEY = 'pickpoint';

		/** @const string LOGIN_KEY ключ настроек провайдера с логином */
		const LOGIN_KEY = 'login';

		/** @const string PASSWORD_KEY ключ настроек провайдера с паролем */
		const PASSWORD_KEY = 'password';

		/** @const string DOCUMENT_NUMBER_KEY ключ настроек провайдера с ikn */
		const DOCUMENT_NUMBER_KEY = 'ikn';

		/** @const string TEST_MODE_KEY ключ настроек провайдера с тестовым режимом */
		const TEST_MODE_KEY = 'test';

		/** @const string LOGIN_TITLE расшифровка поля $login */
		const LOGIN_TITLE = 'Логин';

		/** @const string PASSWORD_TITLE расшифровка поля $password */
		const PASSWORD_TITLE = 'Пароль';

		/** @const string DOCUMENT_NUMBER_TITLE расшифровка поля $ikn */
		const DOCUMENT_NUMBER_TITLE = 'ИКН – номер договора';

		/** @const string TEST_MODE_TITLE расшифровка поля $test */
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

			try {
				$this->setDocumentNumber(
					$this->getPropertyValue($data, self::DOCUMENT_NUMBER_KEY, $valueRequired)
				);
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					$this->getEmptySettingParamErrorMessage(self::DOCUMENT_NUMBER_TITLE)
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
				self::DOCUMENT_NUMBER_KEY => [
					self::DESCRIPTION_KEY => self::DOCUMENT_NUMBER_TITLE,
					self::TYPE_KEY => self::STRING_TYPE_KEY,
					self::REQUIRED_KEY => true,
					self::VALUE_KEY => $this->getDocumentNumber()
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
				self::DOCUMENT_NUMBER_KEY => $this->getDocumentNumber(),
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
				$this->getPickupTypeIdFromPoint()
			];
		}

		/**
		 * Устанавливает логин
		 * @param string $login логин
		 * @return Pickpoint
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
		 * @return Pickpoint
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
		 * Устанавливает ИКН
		 * @param string $ikn ИКН
		 * @return Pickpoint
		 */
		public function setDocumentNumber($ikn) {
			$this->validateStringField($ikn, self::DOCUMENT_NUMBER_TITLE);
			$this->ikn = $ikn;
			return $this;
		}

		/**
		 * Возвращает ИКН
		 * @return string
		 */
		public function getDocumentNumber() {
			return $this->ikn;
		}

		/**
		 * Устанавливает режим тестирования
		 * @param bool $mode режим тестирования
		 * @return Pickpoint
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
