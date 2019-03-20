<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils\ArgumentsValidator;

	/**
	 * Часть данных запроса с информацией о городе
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\RequestDataParts
	 */
	class City implements iCity {

		/** @var string $name название города */
		private $name;

		/** @inheritdoc */
		public function __construct(array $data) {
			$this->import($data);
		}

		/** @inheritdoc */
		public function import(array $data) {
			ArgumentsValidator::arrayContainsValue($data, self::NAME_KEY, __METHOD__, self::NAME_KEY);
			$this->setName($data[self::NAME_KEY]);
			return $this;
		}

		/** @inheritdoc */
		public function export() {
			return [
				self::NAME_KEY => $this->getName()
			];
		}

		/** @inheritdoc */
		public function getName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function setName($name) {
			try {
				ArgumentsValidator::notEmptyString($name, self::NAME_KEY, __METHOD__);
			} catch (\wrongParamException $e) {
				throw new \wrongParamException(getLabel('label-api-ship-error-incorrect-city-name'), $this->getName());
			}
			$this->name = $name;
			return $this;
		}
	}
