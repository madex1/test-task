<?php
	class selfDelivery extends delivery {
		/** Имя поля, в котором хранится стоимость самовывоза */
		const PRICE_FIELD = 'price';
		/** @var umiObject связанный объект */
		public $relatedObject;

		/**
		 * Конструктор
		 * @param umiObject $object
		 * @throws privateException
		 */
		public function __construct(umiObject $object) {
			parent::__construct($object);

			if (!$object instanceof iUmiObject) {
				throw new privateException('There is no source object for constructing delivery of class ' . get_class($this));
			}

			$this->relatedObject = $object;
		}

		public function validate(order $order = null) {
			return true;
		}

		public function getDeliveryPrice(order $order = null) {
			return floatval($this->relatedObject->getValue(self::PRICE_FIELD));
		}

	};
?>