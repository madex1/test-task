<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	/**
	 * Служба доставки A1
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers
	 */
	class A1 extends ApiShip\Provider {

		/** @var string $tin ИНН организации */
		private $tin;

		/** @var string $phone телефон контактного лица */
		private $phone;

		/** @var string $contactName ФИО контактного лица */
		private $contactName;

		/** @var string $organisationName Наименование организации */
		private $organisationName;

		/** @var bool $test включен испытательный режим */
		private $test;

		/** @const string KEY идентификатор провайдера */
		const KEY = 'a1';

		/** @const string TIN_KEY ключ настроек провайдера с ИНН */
		const TIN_KEY = 'tin';

		/** @const string PHONE_KEY ключ настроек провайдера с телефоном */
		const PHONE_KEY = 'phone';

		/** @const string CONTACT_NAME_KEY ключ настроек провайдера с ФИО контактного лица */
		const CONTACT_NAME_KEY = 'contactName';

		/** @const string CONTACT_NAME_KEY ключ настроек провайдера с название организации */
		const NAME_KEY = 'name';

		/** @const string TEST_KEY ключ настроек провайдера с тестовым режимом */
		const TEST_KEY = 'test';

		/** @const string TIN_TITLE расшифровка поля $tin */
		const TIN_TITLE = 'ИНН организации';

		/** @const string PHONE_TITLE расшифровка поля $phone */
		const PHONE_TITLE = 'Телефон контактного лица';

		/** @const string CONTACT_NAME_TITLE расшифровка поля $contactName */
		const CONTACT_NAME_TITLE = 'ФИО контактного лица';

		/** @const string ORGANISATION_NAME_TITLE расшифровка поля $organisationName */
		const ORGANISATION_NAME_TITLE = 'Наименование организации';

		/** @const string TEST_MODE_TITLE расшифровка поля $test */
		const TEST_MODE_TITLE = 'Тестовый доступ';

		/** @inheritdoc */
		public function import(array $data) {
			$valueRequired = true;

			try {
				$this->setTin(
					$this->getPropertyValue($data, self::TIN_KEY, $valueRequired)
				);
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					$this->getEmptySettingParamErrorMessage(self::TIN_TITLE)
				);
			}

			try {
				$this->setPhone(
					$this->getPropertyValue($data, self::PHONE_KEY, $valueRequired)
				);
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					$this->getEmptySettingParamErrorMessage(self::PHONE_TITLE)
				);
			}

			$this->setTestMode(
				$this->getPropertyValue($data, self::TEST_KEY, $valueRequired)
			);

			if ($this->issetPropertyValue($data, self::CONTACT_NAME_KEY)) {
				$this->setContactName(
					$this->getPropertyValue($data, self::CONTACT_NAME_KEY)
				);
			}

			if ($this->issetPropertyValue($data, self::NAME_KEY)) {
				$this->setOrganisationName(
					$this->getPropertyValue($data, self::NAME_KEY)
				);
			}

			parent::import($data);
		}

		/** @inheritdoc */
		public function export() {
			$data = [
				self::TIN_KEY => [
					self::DESCRIPTION_KEY => self::TIN_TITLE,
					self::TYPE_KEY => self::STRING_TYPE_KEY,
					self::REQUIRED_KEY => true,
					self::VALUE_KEY => $this->getTin()
				],
				self::PHONE_KEY => [
					self::DESCRIPTION_KEY => self::PHONE_TITLE,
					self::TYPE_KEY => self::STRING_TYPE_KEY,
					self::REQUIRED_KEY => true,
					self::VALUE_KEY => $this->getPhone()
				],
				self::CONTACT_NAME_KEY => [
					self::DESCRIPTION_KEY => self::CONTACT_NAME_TITLE,
					self::TYPE_KEY => self::STRING_TYPE_KEY,
					self::REQUIRED_KEY => false
				],
				self::NAME_KEY => [
					self::DESCRIPTION_KEY => self::ORGANISATION_NAME_TITLE,
					self::TYPE_KEY => self::STRING_TYPE_KEY,
					self::REQUIRED_KEY => false
				],
				self::TEST_KEY => [
					self::DESCRIPTION_KEY => self::TEST_MODE_TITLE,
					self::TYPE_KEY => self::BOOL_TYPE_KEY,
					self::REQUIRED_KEY => true,
					self::VALUE_KEY => $this->getTestMode()
				]
			];

			if ($this->getContactName() !== null) {
				$data[self::CONTACT_NAME_KEY][self::VALUE_KEY] = $this->getContactName();
			}

			if ($this->getOrganisationName() !== null) {
				$data[self::NAME_KEY][self::VALUE_KEY] = $this->getOrganisationName();
			}

			return array_merge($data, parent::export());
		}

		/** @inheritdoc */
		public function getConnectRequestData() {
			$data = [
				self::TIN_KEY => $this->getTin(),
				self::PHONE_KEY => $this->getPhone(),
				self::TEST_KEY => $this->getTestMode()
			];

			if ($this->getContactName() !== null) {
				$data[self::CONTACT_NAME_KEY] = $this->getContactName();
			}

			if ($this->getOrganisationName() !== null) {
				$data[self::NAME_KEY] = $this->getOrganisationName();
			}

			return $data;
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

		/** @inheritdoc */
		public function areOrderDimensionsRequired() {
			return true;
		}

		/** @inheritdoc */
		public function isOrderItemWeightRequired() {
			return true;
		}

		/**
		 * Устанавливает ИНН
		 * @param string $tin ИНН
		 * @return A1
		 */
		public function setTin($tin) {
			$this->validateStringField($tin, self::TIN_TITLE);
			$this->tin = $tin;
			return $this;
		}

		/**
		 * Возвращает ИНН
		 * @return string
		 */
		public function getTin() {
			return $this->tin;
		}

		/**
		 * Устанавливает телефон
		 * @param string $phone телефон
		 * @return A1
		 */
		public function setPhone($phone) {
			$this->validateStringField($phone, self::PHONE_TITLE);
			$this->phone = $phone;
			return $this;
		}

		/**
		 * Возвращает телефон
		 * @return string
		 */
		public function getPhone() {
			return $this->phone;
		}

		/**
		 * Устанавливает ФИО контактного лица
		 * @param string $name ФИО контактного лица
		 * @return A1
		 */
		public function setContactName($name) {
			$this->validateStringField($name, self::CONTACT_NAME_TITLE);
			$this->contactName = $name;
			return $this;
		}

		/**
		 * Возвращает ФИО контактного лица
		 * @return string
		 */
		public function getContactName() {
			return $this->contactName;
		}

		/**
		 * Устанавливает название организации
		 * @param string $name азвание организации
		 * @return A1
		 */
		public function setOrganisationName($name) {
			$this->validateStringField($name, self::ORGANISATION_NAME_TITLE);
			$this->organisationName = $name;
			return $this;
		}

		/**
		 * Возвращает название организации
		 * @return string
		 */
		public function getOrganisationName() {
			return $this->organisationName;
		}

		/**
		 * Устанавливает режим тестирования
		 * @param bool $mode режим тестирования
		 * @return A1
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
