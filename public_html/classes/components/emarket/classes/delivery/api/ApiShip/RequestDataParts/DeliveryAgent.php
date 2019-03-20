<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils\ArgumentsValidator;

	/**
	 * Часть данных запроса с информацией об агенте (отправителя/получателя) заказа
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts
	 */
	class DeliveryAgent implements iDeliveryAgent {

		/** @var string $countryCode код страны */
		private $countryCode;

		/** @var string $region название региона */
		private $region;

		/** @var string $city название города */
		private $city;

		/** @var string $street улица */
		private $street;

		/** @var string $house номер дома */
		private $house;

		/** @var string $office номер квартиры */
		private $office;

		/** @var string $contactName ФИО контактного лица */
		private $contactName;

		/** @var string $phone телефон контактного лица */
		private $phone;

		/** @var string $email почтовый ящик контактного лица */
		private $email;

		/** @var string $agentName имя агента */
		private $agentName;

		/** @inheritdoc */
		public function __construct(array $data, $agentName) {
			$this->setAgentName($agentName);
			$this->import($data);
		}

		/** @inheritdoc */
		public function import(array $data) {
			$agentName = $this->getAgentName();

			ArgumentsValidator::arrayContainsValue($data, self::COUNTRY_CODE_KEY, $agentName, self::COUNTRY_CODE_KEY);
			$this->setCountryCode($data[self::COUNTRY_CODE_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::REGION_KEY, $agentName, self::REGION_KEY);
			$this->setRegion($data[self::REGION_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::CITY_KEY, $agentName, self::CITY_KEY);
			$this->setCity($data[self::CITY_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::STREET_KEY, $agentName, self::STREET_KEY);
			$this->setStreet($data[self::STREET_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::HOUSE_KEY, $agentName, self::HOUSE_KEY);
			$this->setHouse($data[self::HOUSE_KEY]);

			try {
				ArgumentsValidator::arrayContainsValue($data, self::OFFICE_KEY, $agentName, self::OFFICE_KEY);
				$this->setOffice($data[self::OFFICE_KEY]);
			} catch (\wrongParamException $e) {
				//nothing
			}

			ArgumentsValidator::arrayContainsValue($data, self::CONTACT_NAME_KEY, $agentName, self::CONTACT_NAME_KEY);
			$this->setContactName($data[self::CONTACT_NAME_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::PHONE_KEY, $agentName, self::PHONE_KEY);
			$this->setPhone($data[self::PHONE_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::EMAIL_KEY, $agentName, self::EMAIL_KEY);
			$this->setEmail($data[self::EMAIL_KEY]);

			return $this;
		}

		/** @inheritdoc */
		public function export() {
			$data = [
				self::COUNTRY_CODE_KEY => $this->getCountryCode(),
				self::REGION_KEY => $this->getRegion(),
				self::CITY_KEY => $this->getCity(),
				self::STREET_KEY => $this->getStreet(),
				self::HOUSE_KEY => $this->getHouse(),
				self::CONTACT_NAME_KEY => $this->getContactName(),
				self::PHONE_KEY => $this->getPhone(),
				self::EMAIL_KEY => $this->getEmail()
			];

			if ($this->getOffice() !== null) {
				$data[self::OFFICE_KEY] = $this->getOffice();
			}

			return $data;
		}

		/** @inheritdoc */
		public function setCountryCode($countryCode) {
			try {
				ArgumentsValidator::notEmptyString($countryCode, self::COUNTRY_CODE_KEY, $this->getAgentName());
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					sprintf(getLabel('label-api-ship-error-incorrect-agent-country-code', self::I18N_PATH),
						$this->getAgentName())
				);
			}
			$this->countryCode = $countryCode;
			return $this;
		}

		/** @inheritdoc */
		public function getCountryCode() {
			return $this->countryCode;
		}

		/** @inheritdoc */
		public function setRegion($region) {
			try {
				ArgumentsValidator::notEmptyString($region, self::REGION_KEY, $this->getAgentName());
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					sprintf(getLabel('label-api-ship-error-incorrect-agent-region'), $this->getAgentName())
				);
			}
			$this->region = $region;
			return $this;
		}

		/** @inheritdoc */
		public function getRegion() {
			return $this->region;
		}

		/** @inheritdoc */
		public function setCity($city) {
			try {
				ArgumentsValidator::notEmptyStringWithLessLength(
					$city, self::CITY_KEY, $this->getFormattedAgentName(), self::CITY_MAX_LENGTH
				);
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					sprintf(getLabel('label-api-ship-error-incorrect-agent-city', self::I18N_PATH), $this->getAgentName())
				);
			}
			$this->city = $city;
			return $this;
		}

		/** @inheritdoc */
		public function getCity() {
			return $this->city;
		}

		/** @inheritdoc */
		public function setStreet($street) {
			try {
				ArgumentsValidator::notEmptyStringWithLessLength(
					$street, self::STREET_KEY, $this->getFormattedAgentName(), self::STREET_MAX_LENGTH
				);
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					sprintf(getLabel('label-api-ship-error-incorrect-agent-street', self::I18N_PATH), $this->getAgentName())
				);
			}
			$this->street = $street;
			return $this;
		}

		/** @inheritdoc */
		public function getStreet() {
			return $this->street;
		}

		/** @inheritdoc */
		public function setHouse($house) {
			try {
				ArgumentsValidator::notEmptyString($house, self::HOUSE_KEY, $this->getAgentName());
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					sprintf(getLabel('label-api-ship-error-incorrect-agent-house', self::I18N_PATH), $this->getAgentName())
				);
			}
			$this->house = $house;
			return $this;
		}

		/** @inheritdoc */
		public function getHouse() {
			return $this->house;
		}

		/** @inheritdoc */
		public function setOffice($office) {
			try {
				ArgumentsValidator::notEmptyString($office, self::OFFICE_KEY, $this->getAgentName());
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					sprintf(getLabel('label-api-ship-error-incorrect-agent-office', self::I18N_PATH), $this->getAgentName())
				);
			}
			$this->office = $office;
			return $this;
		}

		/** @inheritdoc */
		public function getOffice() {
			return $this->office;
		}

		/** @inheritdoc */
		public function setContactName($name) {
			try {
				ArgumentsValidator::notEmptyString($name, self::CONTACT_NAME_KEY, $this->getAgentName());
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					sprintf(getLabel('label-api-ship-error-incorrect-agent-contact', self::I18N_PATH), $this->getAgentName())
				);
			}
			$this->contactName = $name;
			return $this;
		}

		/** @inheritdoc */
		public function getContactName() {
			return $this->contactName;
		}

		/** @inheritdoc */
		public function setPhone($phone) {
			try {
				ArgumentsValidator::notEmptyStringWithLessLength(
					$phone, self::PHONE_KEY, $this->getFormattedAgentName(), self::PHONE_MAX_LENGTH
				);
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					sprintf(getLabel('label-api-ship-error-incorrect-agent-phone', self::I18N_PATH), $this->getAgentName())
				);
			}
			$this->phone = $phone;
			return $this;
		}

		/** @inheritdoc */
		public function getPhone() {
			return $this->phone;
		}

		/** @inheritdoc */
		public function setEmail($email) {
			try {
				ArgumentsValidator::notEmptyString($email, self::EMAIL_KEY, $this->getAgentName());
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					sprintf(getLabel('label-api-ship-error-incorrect-agent-email', self::I18N_PATH), $this->getAgentName())
				);
			}
			$this->email = $email;
			return $this;
		}

		/** @inheritdoc */
		public function getEmail() {
			return $this->email;
		}

		/**
		 * Устанавливает имя агента
		 * @param string $agentName имя агента
		 * @return iDeliveryAgent
		 */
		private function setAgentName($agentName) {
			ArgumentsValidator::notEmptyString(
				$agentName, self::CONTACT_NAME_KEY, $this->getAgentName()
			);
			$this->agentName = $agentName;
			return $this;
		}

		/**
		 * Возвращает имя агента
		 * @return string
		 */
		private function getAgentName() {
			return $this->agentName;
		}

		/**
		 * Возвращает название агента в специальном формате
		 * @return string
		 */
		private function getFormattedAgentName() {
			$agentName = $this->getAgentName();
			$messageFormat = getLabel('label-api-ship-agent-name-format', self::I18N_PATH);
			return sprintf($messageFormat, $agentName);
		}
	}
