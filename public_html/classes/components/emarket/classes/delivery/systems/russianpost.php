<?php

	use UmiCms\Service;
	use UmiCms\Classes\Components\Emarket\Delivery\Russianpost\PackIdProvider;

	/**
	 * Способ доставки типа "Почта России".
	 * Подходит ко всем заказам.
	 *
	 * Стоимость доставки рассчитывается за счет интеграции с ресурсом:
	 * https://tariff.pochta.ru/tariff/v1/calculate
	 * @link http://tariff.pochta.ru/TariffAPI.pdf
	 */
	class russianpostDelivery extends delivery {

		/** @const string Адрес калькулятора стоимости доставки */
		const RUSSIANPOST_URL = 'https://tariff.pochta.ru/tariff/v1/calculate?json&';

		/** @var int Количество копеек в рубле */
		const KOPECKS_IN_RUBLE = 100;

		/** @inheritdoc */
		public function validate(order $order) {
			return true;
		}

		/**
		 * @inheritdoc
		 * @throws umiRemoteFileGetterException
		 * @throws Exception
		 */
		public function getDeliveryPrice(order $order) {
			/** @var PackIdProvider $packIdProvider */
			$packIdProvider = Service::get('PackIdProvider');

			try {
				$params = [
					'object' => $this->getPostTypeId(),
					'from' => $this->getValue('zip_code'),
					'to' => $this->getOrderAddress($order)->getValue('index'),
					'weight' => $this->getOrderWeight($order),
					'sumoc' => ceil($order->getActualPrice()),
					'pack' => $packIdProvider->getPackId($order),
				];
				return $this->getResponse(self::RUSSIANPOST_URL . http_build_query($params));

			} catch (privateException $e) {
				return $e->getMessage();
			}
		}

		/**
		 * @inheritdoc
		 * @throws privateException
		 * @throws Exception
		 */
		public function getTaxRateId() {
			/** @var \UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\Facade $vatFacade */
			$vatFacade = Service::get('TaxRateVat');
			return $vatFacade->getTwentyPercentVat()->getId();
		}

		/**
		 * Возвращает идентификатор вида отправления
		 * @return int
		 * @throws privateException
		 */
		private function getPostTypeId() {
			$postType = umiObjectsCollection::getInstance()->getObject($this->getValue('viewpost'));
			if (!$postType instanceof iUmiObject) {
				throw new privateException(getLabel('error-russianpost-no-post-type'));
			}
			return (int) $postType->getValue('identifier');
		}

		/**
		 * Возвращает адрес заказа.
		 * @param order $order заказ
		 * @return object $address адрес
		 * @throws privateException если в заказе не указан адрес
		 */
		protected function getOrderAddress($order) {
			$address = umiObjectsCollection::getInstance()->getObject($order->getValue('delivery_address'));
			if (!$address) {
				throw new privateException(getLabel('error-russianpost-no-address'));
			}
			return $address;
		}

		/**
		 * Возвращает вес заказа.
		 * @param order $order заказ
		 * @return int вес
		 * @throws privateException если в заказе нет товаров
		 */
		protected function getOrderWeight($order) {
			$items = $order->getItems();
			if (!$items) {
				throw new privateException(getLabel('error-russianpost-empty-order'));
			}
			return $order->getTotalWeight();
		}

		/**
		 * Выполняет запрос к калькулятору стоимости доставки и возвращает
		 * стоимость доставки в рублях или сообщение об ошибке.
		 * @param string $url Адрес запроса
		 * @return string|float
		 * @throws privateException
		 * @throws umiRemoteFileGetterException
		 */
		private function getResponse($url) {
			$response = json_decode(umiRemoteFileGetter::get($url), true);

			if (!$response) {
				throw new privateException(getLabel('error-russianpost-undefined'));
			}

			if (isset($response['errors'][0]['msg'])) {
				return $response['errors'][0]['msg'];
			}

			if (!isset($response['paynds'])) {
				throw new privateException(getLabel('error-russianpost-undefined'));
			}

			return ((int) $response['paynds']) / self::KOPECKS_IN_RUBLE;
		}
	}
