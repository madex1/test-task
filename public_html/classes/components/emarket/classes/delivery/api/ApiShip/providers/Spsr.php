<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	/**
	 * Служба доставки SPSR Express
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers
	 */
	class Spsr extends ApiShip\Provider {

		/** @var string $login логин */
		private $login;

		/** @var string $password пароль */
		private $password;

		/** @var string $ikn ИКН – индивидуальный клиентский номер */
		private $ikn;

		/** @var string $agent название компании */
		private $agent;

		/** @var bool $test включен испытательный режим */
		private $test;

		/** @var bool $useCommonCalculatorTariffs вести расчет по стандартным тарифам */
		private $useCommonCalculatorTariffs;

		/** @const string KEY идентификатор провайдера */
		const KEY = 'spsr';

		/** @const string LOGIN_KEY ключ настроек провайдера с логином */
		const LOGIN_KEY = 'login';

		/** @const string PASSWORD_KEY ключ настроек провайдера с паролем */
		const PASSWORD_KEY = 'pass';

		/** @const string IKN_KEY ключ настроек провайдера с ИКН */
		const IKN_KEY = 'IKN';

		/** @const string AGENT_KEY ключ настроек провайдера с названием компании */
		const AGENT_KEY = 'agent';

		/** @const string TEST_KEY ключ настроек провайдера с тестовым режимом */
		const TEST_MODE_KEY = 'test';

		/** @const string CALCULATOR_CUSTOM_TARIFFS_KEY ключ настроек провайдера с включением расчета по стандартным тарифам */
		const CALCULATOR_CUSTOM_TARIFFS_KEY = 'calculatorCustomTariffs';

		/** @const string LOGIN_TITLE расшифровка поля $login */
		const LOGIN_TITLE = 'Логин';

		/** @const string PASSWORD_TITLE расшифровка поля $password */
		const PASSWORD_TITLE = 'Пароль';

		/** @const string IKN_TITLE расшифровка поля $ikn */
		const IKN_TITLE = 'ИКН – индивидуальный клиентский номер';

		/** @const string AGENT_TITLE расшифровка поля $agent */
		const AGENT_TITLE = 'Произвольная строка, позволяющая отличить запросы конкретного клиента от общей массы.
	Рекомендуется указывать название вашей компании.';

		/** @const string TEST_MODE_TITLE расшифровка поля $test */
		const TEST_MODE_TITLE = 'Тестовый доступ';

		/** @const string CALC_CUSTOM_TARIFF_TITLE расшифровка поля $calculatorCustomTariffs */
		const CALC_CUSTOM_TARIFF_TITLE = 'Если false, то производится расчет по стандартным тарифам.
	Если true, то производится расчет индивидуального тарифа.';

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
				$this->setIkn(
					$this->getPropertyValue($data, self::IKN_KEY, $valueRequired)
				);
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					$this->getEmptySettingParamErrorMessage(self::IKN_TITLE)
				);
			}

			try {
				$this->setAgent(
					$this->getPropertyValue($data, self::AGENT_KEY, $valueRequired)
				);
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					$this->getEmptySettingParamErrorMessage(self::AGENT_TITLE)
				);
			}

			$this->setTestMode(
				$this->getPropertyValue($data, self::TEST_MODE_KEY, $valueRequired)
			);

			$this->setCalculatorMode(
				$this->getPropertyValue($data, self::CALCULATOR_CUSTOM_TARIFFS_KEY, $valueRequired)
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
				self::IKN_KEY => [
					self::DESCRIPTION_KEY => self::IKN_TITLE,
					self::TYPE_KEY => self::STRING_TYPE_KEY,
					self::REQUIRED_KEY => true,
					self::VALUE_KEY => $this->getIkn()
				],
				self::AGENT_KEY => [
					self::DESCRIPTION_KEY => self::AGENT_TITLE,
					self::TYPE_KEY => self::STRING_TYPE_KEY,
					self::REQUIRED_KEY => true,
					self::VALUE_KEY => $this->getAgent()
				],
				self::TEST_MODE_KEY => [
					self::DESCRIPTION_KEY => self::TEST_MODE_TITLE,
					self::TYPE_KEY => self::BOOL_TYPE_KEY,
					self::REQUIRED_KEY => true,
					self::VALUE_KEY => $this->getTestMode()
				],
				self::CALCULATOR_CUSTOM_TARIFFS_KEY => [
					self::DESCRIPTION_KEY => self::CALC_CUSTOM_TARIFF_TITLE,
					self::TYPE_KEY => self::BOOL_TYPE_KEY,
					self::REQUIRED_KEY => true,
					self::VALUE_KEY => $this->getCalculatorMode()
				]
			];

			return array_merge($data, parent::export());
		}

		/** @inheritdoc */
		public function getConnectRequestData() {
			return [
				self::LOGIN_KEY => $this->getLogin(),
				self::PASSWORD_KEY => $this->getPassword(),
				self::IKN_KEY => $this->getIkn(),
				self::AGENT_KEY => $this->getAgent(),
				self::TEST_MODE_KEY => $this->getTestMode(),
				self::CALCULATOR_CUSTOM_TARIFFS_KEY => $this->getCalculatorMode()
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
		 * Устанавливает логин
		 * @param string $login логин
		 * @return Spsr
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
		 * @param string $password логпарольин
		 * @return Spsr
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
		 * @return Spsr
		 */
		public function setIkn($ikn) {
			$this->validateStringField($ikn, self::IKN_TITLE);
			$this->ikn = $ikn;
			return $this;
		}

		/**
		 * Возвращает ИКН
		 * @return string
		 */
		public function getIkn() {
			return $this->ikn;
		}

		/**
		 * Устанавливает название компании
		 * @param string $agent название компании
		 * @return Spsr
		 */
		public function setAgent($agent) {
			$this->validateStringField($agent, self::AGENT_TITLE);
			$this->agent = $agent;
			return $this;
		}

		/**
		 * Возвращает название компании
		 * @return string
		 */
		public function getAgent() {
			return $this->agent;
		}

		/**
		 * Устанавливает режим тестирования
		 * @param bool $mode режим тестирования
		 * @return Spsr
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

		/**
		 * Устанавливает режим использования стандартных тарифов
		 * @param bool $mode использовать/не использовать
		 * @return Spsr
		 */
		public function setCalculatorMode($mode) {
			$this->useCommonCalculatorTariffs = (bool) $mode;
			return $this;
		}

		/**
		 * Возвращает режим использования стандартных тарифов
		 * @return bool
		 */
		public function getCalculatorMode() {
			return $this->useCommonCalculatorTariffs;
		}
	}
