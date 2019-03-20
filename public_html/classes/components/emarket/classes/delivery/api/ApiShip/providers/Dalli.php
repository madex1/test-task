<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers;

	/**
	 * Служба доставки Dalli
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Providers
	 */
	class Dalli extends Boxberry {

		/** @const string KEY идентификатор провайдера */
		const KEY = 'dalli';

		/** @const string TOKEN_KEY ключ настроек провайдера с авторизационным токеном */
		const TOKEN_KEY = 'token';

		/** @const string TOKEN_TITLE расшифровка поля $token */
		const TOKEN_TITLE = 'Уникальный ключ (API-token) для авторизации';

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
	}
