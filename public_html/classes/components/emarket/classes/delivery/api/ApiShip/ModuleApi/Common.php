<?php
	/** Класс общего функционала UMI.CMS для способа доставки ApiShip */

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\ModuleApi;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\PointOperations;

	class Common implements \iModulePart {

		use \tModulePart;

		/** @const string ROWS_KEY ключ данных с записями */
		const ROWS_KEY = 'rows';

		/**
		 * Возвращает список пунктов выдачи и/или приема товаров из сервиса ApiShip
		 * @param int|bool $deliveryId идентификатор способа доставки типа ApiShip
		 * @param string|bool $cityName название города
		 * @param string|bool $providerKey идентификатор службы доставки
		 * @param int|bool $operationId идентификатор типа операции, которую предоставляет пункт
		 * @throws \publicAdminException
		 */
		public function getApiShipPointsByProviderAndCity(
			$deliveryId = false,
			$cityName = false,
			$providerKey = false,
			$operationId = false
		) {
			$delivery = $this->getApiShipDelivery($deliveryId);
			$cityName = ($cityName === false) ? getRequest('param1') : $cityName;
			$providerKey = ($providerKey === false) ? getRequest('param2') : $providerKey;
			$operationId = ($operationId === false) ? getRequest('param3') : $operationId;

			if (!is_numeric($operationId)) {
				$pointOperation = new PointOperations();
				$operationId = (int) $pointOperation->__toString();
			}

			$operation = (string) new PointOperations($operationId);

			try {
				$pointId = null;
				$points = $delivery->getRequestSender()
					->getDeliveryPointsList($pointId, $providerKey, $cityName, $operation);
			} catch (\Exception $e) {
				throw new \publicAdminException($e->getMessage());
			}

			if (!arrayValueContainsNotEmptyArray($points, self::ROWS_KEY)) {
				$exceptionMessage = getLabel('label-api-ship-error-point-not-found', $this->getModuleName());
				throw new \publicAdminException(sprintf($exceptionMessage, $providerKey, $cityName));
			}

			$this->module->printJson($points);
		}

		/**
		 * Получает и выводит в буффер список вариантов доставки
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @param bool|int $orderId идентификатор заказа в UMI.CMS
		 * @throws \publicAdminException
		 */
		public function getApiShipDeliveryOptions($deliveryId = false, $orderId = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);
			$orderId = ($orderId === false) ? getRequest('param1') : $orderId;
			$order = \order::get($orderId);

			$this->module->printJson(
				$delivery->getDeliveryOptionList($order)
			);
		}

		/** Получает и выводит в буффер типы точек выдачи заказов */
		public function getApiShipDeliveryPointsTypes() {
			$deliveryPointTypesIds = new Enums\PointTypes();
			$deliveryPointTypesTitles = $deliveryPointTypesIds->getValuesTitles();
			$result = [];

			foreach ($deliveryPointTypesTitles as $deliveryPointTypeId => $deliveryPointTypeTitle) {
				$result[] = [
					'id' => $deliveryPointTypeId,
					'name' => $deliveryPointTypeTitle,
				];
			}

			$this->module->printJson($result);
		}

		/**
		 * Получает и выводит в буффер информацию о тарифе по его id
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @param bool|string $providerKey строковой идентификатор провайдера
		 * @param bool|int $tariffId идентификатор тарифа провайдера
		 * @throws \publicAdminException
		 */
		public function getApiShipProviderTariffById($deliveryId = false, $providerKey = false, $tariffId = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);
			$providerKey = ($providerKey === false) ? getRequest('param1') : $providerKey;
			$tariffId = ($tariffId === false) ? getRequest('param2') : $tariffId;
			$tariffs = $this->getTariffsByProvider($delivery, $providerKey);

			foreach ($tariffs as $tariff) {
				if (!isset($tariff['id'])) {
					continue;
				}

				if ($tariff['id'] != $tariffId) {
					continue;
				}

				$this->module->printJson($tariff);
			}

			$exceptionFormat = getLabel('label-api-ship-provider-tariff-not-received', $this->getModuleName());
			$exceptionMessage = sprintf($exceptionFormat, $tariffId, $providerKey);

			throw new \publicAdminException($exceptionMessage);
		}

		/**
		 * Получает и выводит в буффер информацию о точке выдаче по ее id
		 * @param bool|int $deliveryId идентификатор способа доставки ApiShip
		 * @param bool|int $deliveryPointId идентификатор точки выдачи
		 * @throws \publicAdminException
		 */
		public function getApiShipDeliveryPointById($deliveryId = false, $deliveryPointId = false) {
			$delivery = $this->getApiShipDelivery($deliveryId);
			$deliveryPointId = ($deliveryPointId === false) ? getRequest('param1') : $deliveryPointId;

			if (!$deliveryPointId) {
				$this->module->printJson([]);
			}

			try {
				$deliveryPointData = $delivery->getRequestSender()
					->getDeliveryPointsList($deliveryPointId);
			} catch (\Exception $e) {
				throw new \publicAdminException($e->getMessage());
			}

			$this->module->printJson($deliveryPointData);
		}

		/**
		 * Возвращает список тарифов провайдера
		 * @param \ApiShipDelivery $delivery способа доставки ApiShip
		 * @param string $providerKey идентификатор провайдера
		 * @return array
		 * @throws \publicAdminException
		 */
		private function getTariffsByProvider(\ApiShipDelivery $delivery, $providerKey) {
			$providerKey = new Enums\ProvidersKeys($providerKey);
			/** @var ApiShip\iProvider $provider */
			$provider = ApiShip\ProvidersFactory::create($providerKey);

			try {
				$tariffs = $delivery->getRequestSender()
					->getProviderTariffsList($provider->getKey());
			} catch (\Exception $e) {
				throw new \publicAdminException($e->getMessage());
			}

			if (!arrayValueContainsNotEmptyArray($tariffs, self::ROWS_KEY)) {
				$exceptionMessage = getLabel('label-api-ship-provider-tariffs-not-received', $this->getModuleName());
				throw new \publicAdminException(
					sprintf($exceptionMessage, $provider->getKey())
				);
			}

			return $tariffs[self::ROWS_KEY];
		}

		/**
		 * Возвращает способ доставки ApiShip по его ид
		 * @param bool|int $deliveryId идентификатор способа доставки
		 * @return \ApiShipDelivery
		 * @throws \publicAdminException
		 */
		private function getApiShipDelivery($deliveryId = false) {
			$deliveryId = ($deliveryId === false) ? getRequest('param0') : $deliveryId;

			try {
				$delivery = \delivery::get($deliveryId);
			} catch (\Exception $e) {
				throw new \publicAdminException($e->getMessage());
			}

			if (!$delivery instanceof \ApiShipDelivery) {
				$exceptionMessage = getLabel('label-api-ship-error-cant-get-delivery-by-id', $this->getModuleName());
				throw new \publicAdminException(
					sprintf($exceptionMessage, $deliveryId)
				);
			}

			return $delivery;
		}
	}
