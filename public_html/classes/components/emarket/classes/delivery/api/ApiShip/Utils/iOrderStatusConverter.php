<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils;

	/**
	 * Интерфейс конвертера статусов заказов
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils
	 */
	interface iOrderStatusConverter {

		/**
		 * Конвертирует статус заказа ApiShip в статус доставки заказа UMI.CMS
		 * @param string $status статус заказа ApiShip
		 * @return string
		 * @throws \wrongParamException
		 */
		public static function convertApiShipToUmi($status);
	}
