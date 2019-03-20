<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts;

	/**
	 * Служба доставки MaxiPost
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers
	 */
	class Maxi extends ApiShip\Provider {

		/** @var string $login логин */
		private $login;

		/** @var string $password пароль */
		private $password;

		/** @var string $test включен ли тестовый режим */
		private $test;

		/** @const string KEY идентификатор провайдера */
		const KEY = 'maxi';

		/** @const string LOGIN_KEY ключ настроек провайдера с логином */
		const LOGIN_KEY = 'login';

		/** @const string PASSWORD_KEY ключ настроек провайдера с паролем */
		const PASSWORD_KEY = 'password';

		/** @const string TEST_MODE_KEY ключ настроек провайдера с тестовым режимом */
		const TEST_MODE_KEY = 'test';

		/** @const string MOSCOW название города Москва */
		const MOSCOW = 'Москва';

		/** @const string SPB название города Санкт-Петербург */
		const SPB = 'Санкт-Петербург';

		/** @const string LOGIN_TITLE расшифровка свойства $login */
		const LOGIN_TITLE = 'Логин';

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
				$this->getPickupTypeIdFromPoint()
			];
		}

		/** @inheritdoc */
		public function isPointIdRequiredForPickupFromPoint() {
			return false;
		}

		/** @inheritdoc */
		public function isRecipientCitySupported(RequestDataParts\iCity $city) {
			$cityName = $city->getName();
			return ($cityName == self::MOSCOW || $cityName == self::SPB);
		}

		/**
		 * Устанавливает логин
		 * @param string $login логин
		 * @return Maxi
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
		 * @return Maxi
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
		 * Устанавливает режим тестирования
		 * @param bool $mode режим тестирования
		 * @return Maxi
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
