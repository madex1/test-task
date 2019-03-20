<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\iProvider;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils\ArgumentsValidator;

	/**
	 * Данные запроса установления подключения к провайдеру (службе доставки)
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestData
	 */
	class ConnectProvider implements iConnectProvider {

		/** @var int $companyId идентификатор компании пользователя ApiShip */
		private $companyId;

		/** @var string $providerKey идентификатор подключаемого провайдера */
		private $providerKey;

		/** @var iProvider $provider подключаемый провайдер */
		private $provider;

		/** @inheritdoc */
		public function __construct(array $data) {
			$this->import($data);
		}

		/** @inheritdoc */
		public function import(array $data) {
			ArgumentsValidator::arrayContainsValue($data, self::COMPANY_ID_KEY, __METHOD__, self::COMPANY_ID_KEY);
			$this->setCompanyId($data[self::COMPANY_ID_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::PROVIDER_ID_KEY, __METHOD__, self::PROVIDER_ID_KEY);
			$this->setProviderKey($data[self::PROVIDER_ID_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::PROVIDER_PARAMS_KEY, __METHOD__, self::PROVIDER_PARAMS_KEY);
			$this->setProvider($data[self::PROVIDER_PARAMS_KEY]);

			return $this;
		}

		/** @inheritdoc */
		public function export() {
			return [
				self::COMPANY_ID_KEY => $this->getCompanyId(),
				self::PROVIDER_ID_KEY => $this->getProviderKey(),
				self::PROVIDER_PARAMS_KEY => $this->getProvider()
					->getConnectRequestData(),
			];
		}

		/** @inheritdoc */
		public function setCompanyId($companyId) {
			ArgumentsValidator::notZeroInteger($companyId, self::COMPANY_ID_KEY, __METHOD__);
			$this->companyId = $companyId;
			return $this;
		}

		/** @inheritdoc */
		public function getCompanyId() {
			return $this->companyId;
		}

		/** @inheritdoc */
		public function setProviderKey($providerKey) {
			ArgumentsValidator::notEmptyString($providerKey, self::PROVIDER_ID_KEY, __METHOD__);
			$this->providerKey = $providerKey;
			return $this;
		}

		/** @inheritdoc */
		public function getProviderKey() {
			return $this->providerKey;
		}

		/** @inheritdoc */
		public function setProvider(iProvider $provider = null) {
			$this->provider = $provider;
			return $this;
		}

		/** @inheritdoc */
		public function getProvider() {
			return $this->provider;
		}
	}
