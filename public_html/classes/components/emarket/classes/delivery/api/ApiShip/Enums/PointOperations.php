<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums;

	use UmiCms\Classes\System\Enums\Enum;

	/**
	 * Перечисление типов операции ПВД (пункта выдачи товара)
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums
	 */
	class PointOperations extends Enum {

		/** @const int RECEIVING идентификатор типа операции "Прием заказов" */
		const RECEIVING = 1;

		/** @const int DISTRIBUTING идентификатор типа операции "Выдача заказов" */
		const DISTRIBUTING = 2;

		/** @const int RECEIVING_AND_DISTRIBUTING идентификатор типа операции "Прием и выдача заказов" */
		const RECEIVING_AND_DISTRIBUTING = 3;

		/** @inheritdoc */
		protected function getDefaultValue() {
			return self::RECEIVING_AND_DISTRIBUTING;
		}
	}
