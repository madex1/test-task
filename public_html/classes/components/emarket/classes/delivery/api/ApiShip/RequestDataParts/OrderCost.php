<?php
	/** Данные стоимости заказа */

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils\ArgumentsValidator;

	/**
	 * Часть данных запроса с информацией о стоимости заказа
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts
	 */
	class OrderCost implements iOrderCost {

		/** @var float $assessedCost оценочная стоимость заказа (стоимость заказа без доставки) */
		private $assessedCost;

		/** @var float $deliveryCost стоимость доставки заказа */
		private $deliveryCost;

		/** @var float $codCost сумма наложенного платежа */
		private $codCost;

		/** @inheritdoc */
		public function __construct(array $data) {
			$this->import($data);
		}

		/** @inheritdoc */
		public function import(array $data) {
			ArgumentsValidator::arrayContainsValue($data, self::ASSESSED_COST_KEY, __METHOD__, self::ASSESSED_COST_KEY);
			$this->setAssessedCost($data[self::ASSESSED_COST_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::DELIVERY_COST_KEY, __METHOD__, self::DELIVERY_COST_KEY);
			$this->setDeliveryCost($data[self::DELIVERY_COST_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::COD_COST_KEY, __METHOD__, self::COD_COST_KEY);
			$this->setCodCost($data[self::COD_COST_KEY]);

			return $this;
		}

		/** @inheritdoc */
		public function export() {
			return [
				self::ASSESSED_COST_KEY => $this->getAssessedCost(),
				self::DELIVERY_COST_KEY => $this->getDeliveryCost(),
				self::COD_COST_KEY => $this->getCodCost(),
				self::PAYER_STATUS_KEY => false
			];
		}

		/** @inheritdoc */
		public function setAssessedCost($cost) {
			try {
				ArgumentsValidator::float($cost, self::ASSESSED_COST_KEY, __METHOD__);
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(getLabel('label-api-ship-error-incorrect-assessed-cost', self::I18N_PATH));
			}
			$this->assessedCost = $cost;
			return $this;
		}

		/** @inheritdoc */
		public function getAssessedCost() {
			return $this->assessedCost;
		}

		/** @inheritdoc */
		public function setDeliveryCost($cost) {
			try {
				ArgumentsValidator::float($cost, self::DELIVERY_COST_KEY, __METHOD__);
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(getLabel('label-api-ship-error-incorrect-delivery-cost', self::I18N_PATH));
			}
			$this->deliveryCost = $cost;
			return $this;
		}

		/** @inheritdoc */
		public function getDeliveryCost() {
			return $this->deliveryCost;
		}

		/** @inheritdoc */
		public function setCodCost($cost) {
			try {
				ArgumentsValidator::float($cost, self::COD_COST_KEY, __METHOD__);
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(getLabel('label-api-ship-error-incorrect-cod-cost', self::I18N_PATH));
			}
			$this->codCost = $cost;
			return $this;
		}

		/** @inheritdoc */
		public function getCodCost() {
			return $this->codCost;
		}
	}
