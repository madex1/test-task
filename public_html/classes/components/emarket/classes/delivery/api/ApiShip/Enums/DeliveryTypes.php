<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums;

	use UmiCms\Classes\System\Enums\Enum;

	/**
	 * Перечисление типов доставки заказа клиенту
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums
	 */
	class DeliveryTypes extends Enum {

		/** @const int TO_DOOR идентификатор типа доставки заказа "До двери" */
		const TO_DOOR = 1;

		/** @const int TO_POINT идентификатор типа доставки заказа "До пункта выдачи" */
		const TO_POINT = 2;

		/**
		 * Возвращает идентификаторы и названия типов доставки
		 *
		 * [
		 *      id => title
		 * ]
		 *
		 * @return array
		 */
		public function getValuesTitles() {
			return [
				self::TO_DOOR => $this->getTitleById(self::TO_DOOR),
				self::TO_POINT => $this->getTitleById(self::TO_POINT)
			];
		}

		/**
		 * Возвращает наименование типа доставки по его ид
		 * @param int $id идентификатор типа доставки
		 * @return null|string
		 */
		protected function getTitleById($id) {
			$i18GroupKey = 'emarket';
			switch ($id) {
				case self::TO_DOOR : {
					return getLabel('label-to-door', $i18GroupKey);
				}
				case self::TO_POINT : {
					return getLabel('label-to-point', $i18GroupKey);
				}
				default : {
					return null;
				}
			}
		}

		/** @inheritdoc */
		protected function getDefaultValue() {
			return self::TO_DOOR;
		}
	}
