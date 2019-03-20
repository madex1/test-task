<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip;

	/**
	 * Служба доставки Boxberry
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers
	 */
	class Boxberry extends ApiShip\Provider {

		/** @var string $token авторизационный токен */
		private $token;

		/** @const string KEY идентификатор провайдера */
		const KEY = 'boxberry';

		/** @const string TOKEN_KEY ключ настроек провайдера с авторизационным токеном */
		const TOKEN_KEY = 'token';

		/** @const string TOKEN_TITLE расшифровка поля $token */
		const TOKEN_TITLE = 'Уникальный ключ (API-token) для авторизации';

		/** @inheritdoc */
		public function import(array $data) {
			$valueRequired = true;

			try {
				$this->setToken(
					$this->getPropertyValue($data, self::TOKEN_KEY, $valueRequired)
				);
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					$this->getEmptySettingParamErrorMessage(self::TOKEN_TITLE)
				);
			}

			parent::import($data);
		}

		/** @inheritdoc */
		public function export() {
			$data = [
				self::TOKEN_KEY => [
					self::DESCRIPTION_KEY => self::TOKEN_TITLE,
					self::TYPE_KEY => self::STRING_TYPE_KEY,
					self::REQUIRED_KEY => true,
					self::VALUE_KEY => $this->getToken()
				]
			];

			return array_merge($data, parent::export());
		}

		/** @inheritdoc */
		public function getConnectRequestData() {
			return [
				self::TOKEN_KEY => $this->getToken()
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
		 * Устанавливает авторизационный токен
		 * @param string $token авторизационный токен
		 * @return Boxberry
		 */
		public function setToken($token) {
			$this->validateStringField($token, self::TOKEN_TITLE);
			$this->token = $token;
			return $this;
		}

		/**
		 * Возвращает авторизационный токен
		 * @return string
		 */
		public function getToken() {
			return $this->token;
		}
	}
