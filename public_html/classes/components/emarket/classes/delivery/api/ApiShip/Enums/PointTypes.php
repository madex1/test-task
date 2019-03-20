<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums;

	use UmiCms\Classes\System\Enums\Enum;

	/**
	 * Перечисление типов ПВД (пунктов выдачи товара)
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums
	 */
	class PointTypes extends Enum {

		/** @const int COMMON идентификатор типа пункта "Пункт выдачи заказа" */
		const COMMON = 1;

		/** @const int AUTO_POST идентификатор типа пункта "Постомат" */
		const AUTO_POST = 2;

		/** @const int RUSSIAN_POST идентификатор типа пункта "Отделение Почты России" */
		const RUSSIAN_POST = 3;

		/** @const int TERMINAL идентификатор типа пункта "Терминал" */
		const TERMINAL = 4;

		/**
		 * Возвращает идентификаторы и названия типов ПВД
		 *
		 * [
		 *      id => title
		 * ]
		 *
		 * @return array
		 */
		public function getValuesTitles() {
			$allValues = $this->getAllValues();
			$result = [];

			foreach ($allValues as $value) {
				$result[$value] = $this->getTitleById($value);
			}

			return $result;
		}

		/**
		 * Возвращает наименование типа ПВД по его ид
		 * @param int $id типа ПВД
		 * @return null|string
		 */
		protected function getTitleById($id) {
			$i18GroupKey = 'emarket';
			return getLabel('label-api-ship-delivery-point-type-' . $id, $i18GroupKey);
		}

		/** @inheritdoc */
		protected function getDefaultValue() {
			return self::COMMON;
		}
	}
