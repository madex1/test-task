<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums;

	use UmiCms\Classes\System\Enums\Enum;

	/**
	 * Перечисление типов отгрузки (доставки провайдеру)
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums
	 */
	class PickupTypes extends Enum {

		/** @const int FROM_DOOR идентификатор типа отгрузки "От двери" */
		const FROM_DOOR = 1;

		/** @const int FROM_POINT идентификатор типа отгрузки "От пункта приема" */
		const FROM_POINT = 2;

		/**
		 * Возвращает идентификаторы и названия типов отгрузки
		 *
		 * [
		 *      id => title
		 * ]
		 *
		 * @return array
		 */
		public function getValuesTitles() {
			return [
				self::FROM_DOOR => $this->getTitleById(self::FROM_DOOR),
				self::FROM_POINT => $this->getTitleById(self::FROM_POINT)
			];
		}

		/**
		 * Возвращает наименование типа доставки по его ид
		 * @param int $id идентификатор типа отгрузки
		 * @return null|string
		 */
		protected function getTitleById($id) {
			$i18GroupKey = 'emarket';
			switch ($id) {
				case self::FROM_DOOR : {
					return getLabel('label-from-door', $i18GroupKey);
				}
				case self::FROM_POINT : {
					return getLabel('label-from-point', $i18GroupKey);
				}
				default : {
					return null;
				}
			}
		}

		/** @inheritdoc */
		protected function getDefaultValue() {
			return self::FROM_DOOR;
		}
	}
