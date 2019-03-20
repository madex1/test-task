<?php

	/**
	 * Способ доставки типа "Самовывоз".
	 * Подходит ко всем заказам.
	 * Стоимость доставки берет из объекта-источника, она может быть нулевой
	 */
	class selfDelivery extends delivery {

		/** @const string PRICE_FIELD имя поля объекта-источника, в котором хранится стоимость самовывоза */
		const PRICE_FIELD = 'price';

		/** @var iUmiObject $relatedObject объект-источник */
		public $relatedObject;

		/**
		 * Конструктор
		 * @param iUmiObject $object объект-источник
		 * @throws privateException
		 */
		public function __construct(iUmiObject $object) {
			parent::__construct($object);

			if (!$object instanceof iUmiObject) {
				throw new privateException('There is no source object for constructing delivery of class ' . get_class($this));
			}

			$this->relatedObject = $object;
		}

		/** @inheritdoc */
		public function validate(order $order = null) {
			return true;
		}

		/** @inheritdoc */
		public function getDeliveryPrice(order $order = null) {
			return (float) $this->relatedObject->getValue(self::PRICE_FIELD);
		}
	}
