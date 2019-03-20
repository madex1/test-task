<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\OrderStatuses;
	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils\ArgumentsValidator;

	/**
	 * Заказ ApiShip
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders
	 */
	class Entity implements
		iEntity,
		\iUmiDataBaseInjector,
		\iUmiConstantMapInjector,
		\iClassConfigManager {

		use \tUmiDataBaseInjector;
		use \tCommonCollectionItem;
		use \tUmiConstantMapInjector;
		use \tClassConfigManager;

		/** @var int $number номер заказа */
		private $number;

		/** @var int $umiOrderRefNumber номер связанного заказа в UMI.CMS */
		private $umiOrderRefNumber;

		/** @var string $providerOrderRefNumber номер связанного заказа в службе доставки */
		private $providerOrderRefNumber;

		/** @var string $status статус заказа, @see OrderStatuses */
		private $status;

		/** @var array $classConfig конфигурация класса */
		private static $classConfig = [
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'required' => true,
					'unchangeable' => true,
					'setter' => 'setId',
					'getter' => 'getId'
				],
				[
					'name' => 'NUMBER_FIELD_NAME',
					'required' => true,
					'setter' => 'setNumber',
					'getter' => 'getNumber'
				],
				[
					'name' => 'UMI_ORDER_REF_NUMBER_FIELD_NAME',
					'required' => true,
					'setter' => 'setUmiOrderRefNumber',
					'getter' => 'getUmiOrderRefNumber'
				],
				[
					'name' => 'PROVIDER_ORDER_REF_NUMBER_FIELD_NAME',
					'setter' => 'setProviderOrderRefNumber',
					'getter' => 'getProviderOrderRefNumber'
				],
				[
					'name' => 'STATUS_FIELD_NAME',
					'required' => true,
					'setter' => 'setStatus',
					'getter' => 'getStatus'
				]
			]
		];

		/** @inheritdoc */
		public function setNumber($number) {
			ArgumentsValidator::notZeroNumber(
				$number, $this->getMap()->get('NUMBER_FIELD_NAME'), __METHOD__
			);

			if ($this->getNumber() != $number) {
				$this->setUpdatedStatus(true);
			}

			$this->number = $number;
			return $this;
		}

		/** @inheritdoc */
		public function getNumber() {
			return $this->number;
		}

		/** @inheritdoc */
		public function setUmiOrderRefNumber($number) {
			ArgumentsValidator::notZeroNumber(
				$number, $this->getMap()->get('UMI_ORDER_REF_NUMBER_FIELD_NAME'), __METHOD__
			);

			if ($this->getUmiOrderRefNumber() != $number) {
				$this->setUpdatedStatus(true);
			}

			$this->umiOrderRefNumber = $number;
			return $this;
		}

		/** @inheritdoc */
		public function getUmiOrderRefNumber() {
			return $this->umiOrderRefNumber;
		}

		/** @inheritdoc */
		public function setProviderOrderRefNumber($number) {
			ArgumentsValidator::stringOrNull(
				$number, $this->getMap()->get('PROVIDER_ORDER_REF_NUMBER_FIELD_NAME'), __METHOD__
			);

			if ($this->getProviderOrderRefNumber() != $number) {
				$this->setUpdatedStatus(true);
			}

			$this->providerOrderRefNumber = $number;
			return $this;
		}

		/** @inheritdoc */
		public function getProviderOrderRefNumber() {
			return $this->providerOrderRefNumber;
		}

		/** @inheritdoc */
		public function setStatus(OrderStatuses $orderStatusId) {
			$orderStatusId = (string) $orderStatusId;

			if ($this->getStatus() !== $orderStatusId) {
				$this->setUpdatedStatus(true);
			}

			$this->status = $orderStatusId;
			return $this;
		}

		/** @inheritdoc */
		public function getStatus() {
			$status = new OrderStatuses();

			if ($this->status !== null) {
				$status = new OrderStatuses($this->status);
			}

			return (string) $status;
		}

		/** @inheritdoc */
		public function commit() {
			if (!$this->isUpdated()) {
				return $this;
			}

			$map = $this->getMap();
			$connection = $this->getConnection();
			$tableName = $connection->escape($map->get('TABLE_NAME'));
			$idField = $connection->escape($map->get('ID_FIELD_NAME'));
			$numberField = $connection->escape($map->get('NUMBER_FIELD_NAME'));
			$umiOrderRefNumberField = $connection->escape($map->get('UMI_ORDER_REF_NUMBER_FIELD_NAME'));
			$providerOrderRefNumberField = $connection->escape($map->get('PROVIDER_ORDER_REF_NUMBER_FIELD_NAME'));
			$statusField = $connection->escape($map->get('STATUS_FIELD_NAME'));

			$id = (int) $this->getId();
			$number = (int) $this->getNumber();
			$umiOrderRefNumber = (int) $this->getUmiOrderRefNumber();
			$providerOrderRefNumber = $connection->escape($this->getProviderOrderRefNumber());
			$status = $connection->escape($this->getStatus());

			$sql = <<<SQL
UPDATE `$tableName`
	SET `$numberField` = $number, `$umiOrderRefNumberField` = $umiOrderRefNumber,
		`$providerOrderRefNumberField` = '$providerOrderRefNumber', `$statusField` = '$status'
			WHERE `$idField` = $id;
SQL;
			$connection->query($sql);
			return $this;
		}
	}
