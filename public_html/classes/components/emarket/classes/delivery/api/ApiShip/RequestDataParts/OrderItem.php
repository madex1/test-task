<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils\ArgumentsValidator;

	/**
	 * Часть данных запроса с информацией о товарном наименовании заказа заказа
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts
	 */
	class OrderItem implements iOrderItem {

		/** @var int $id идентификатор */
		private $id;

		/** @var string $name название */
		private $name;

		/** @var int $quantity количество */
		private $quantity;

		/** @var float $weight вес */
		private $weight;

		/** @var float $codCost сумма наложеного платежа */
		private $codCost;

		/** @var float $assessedCost оченочная стоимость */
		private $assessedCost;

		/** @inheritdoc */
		public function __construct(array $data) {
			$this->import($data);
		}

		/** @inheritdoc */
		public function import(array $data) {
			ArgumentsValidator::arrayContainsValue($data, self::DESCRIPTION_KEY, __METHOD__, self::DESCRIPTION_KEY);
			$this->setName($data[self::DESCRIPTION_KEY]);

			$name = $this->getName();

			ArgumentsValidator::arrayContainsValue($data, self::QUANTITY_KEY, $name, self::QUANTITY_KEY);
			$this->setQuantity($data[self::QUANTITY_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::ID_KEY, $name, self::ID_KEY);
			$this->setId($data[self::ID_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::WEIGHT_KEY, $name, self::WEIGHT_KEY);
			$this->setWeight($data[self::WEIGHT_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::COST_KEY, $name, self::COST_KEY);
			$this->setCodCost($data[self::COST_KEY]);

			ArgumentsValidator::arrayContainsValue($data, self::ASSESSED_COST_KEY, $name, self::ASSESSED_COST_KEY);
			$this->setAssessedCost($data[self::ASSESSED_COST_KEY]);

			return $this;
		}

		/** @inheritdoc */
		public function export() {
			return [
				self::ID_KEY => $this->getId(),
				self::DESCRIPTION_KEY => $this->getName(),
				self::QUANTITY_KEY => $this->getQuantity(),
				self::WEIGHT_KEY => $this->getWeight(),
				self::COST_KEY => $this->getCodCost(),
				self::ASSESSED_COST_KEY => $this->getAssessedCost()
			];
		}

		/** @inheritdoc */
		public function setId($id) {
			try {
				ArgumentsValidator::notEmptyString($id, self::ID_KEY, $this->getName());
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					sprintf(getLabel('label-api-ship-error-incorrect-order-item-id'), $this->getName())
				);
			}
			$this->id = $id;
			return $this;
		}

		/** @inheritdoc */
		public function getId() {
			return $this->id;
		}

		/** @inheritdoc */
		public function setName($name) {
			try {
				ArgumentsValidator::notEmptyString($name, self::DESCRIPTION_KEY, $this->getName());
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					sprintf(getLabel('label-api-ship-error-incorrect-order-item-name'), $this->getName())
				);
			}
			$this->name = $name;
			return $this;
		}

		/** @inheritdoc */
		public function getName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function setQuantity($quantity) {
			try {
				ArgumentsValidator::notZeroInteger($quantity, self::QUANTITY_KEY, $this->getName());
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					sprintf(getLabel('label-api-ship-error-incorrect-order-item-count'), $this->getName())
				);
			}
			$this->quantity = $quantity;
			return $this;
		}

		/** @inheritdoc */
		public function getQuantity() {
			return $this->quantity;
		}

		/** @inheritdoc */
		public function setWeight($weight) {
			try {
				ArgumentsValidator::notZeroFloat($weight, self::WEIGHT_KEY, $this->getName());
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					sprintf(getLabel('label-api-ship-error-incorrect-order-item-weight'), $this->getName())
				);
			}
			$this->weight = $weight;
			return $this;
		}

		/** @inheritdoc */
		public function getWeight() {
			return $this->weight;
		}

		/** @inheritdoc */
		public function setCodCost($codCost) {
			try {
				ArgumentsValidator::float($codCost, self::COST_KEY, $this->getName());
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					sprintf(getLabel('label-api-ship-error-incorrect-order-item-cod-cost'), $this->getName())
				);
			}
			$this->codCost = $codCost;
			return $this;
		}

		/** @inheritdoc */
		public function getCodCost() {
			return $this->codCost;
		}

		/** @inheritdoc */
		public function setAssessedCost($cost) {
			try {
				ArgumentsValidator::float($cost, self::ASSESSED_COST_KEY, $this->getName());
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(
					sprintf(getLabel('label-api-ship-error-incorrect-order-item-assessed-cost'), $this->getName())
				);
			}
			$this->assessedCost = $cost;
			return $this;
		}

		/** @inheritdoc */
		public function getAssessedCost() {
			return $this->assessedCost;
		}
	}
