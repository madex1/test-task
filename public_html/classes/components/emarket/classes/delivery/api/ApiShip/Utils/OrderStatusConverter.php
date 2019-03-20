<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils;

	use UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums\OrderStatuses;

	/**
	 * Конвертер статусов заказов
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Utils
	 */
	class OrderStatusConverter implements iOrderStatusConverter {

		/** @inheritdoc */
		public static function convertApiShipToUmi($status) {
			$status = new OrderStatuses($status);

			switch ($status) {
				case OrderStatuses::UPLOADED :
				case OrderStatuses::UPLOADING :
				case OrderStatuses::PENDING : {
					return \delivery::STATUS_WAIT_SHIPPING;
				}
				case OrderStatuses::READY_FOR_RECIPIENT :
				case OrderStatuses::DELIVERED : {
					return \delivery::STATUS_DELIVERED;
				}
				case OrderStatuses::ON_POINT_IN :
				case OrderStatuses::ON_POINT_OUT :
				case OrderStatuses::ON_WAY :
				case OrderStatuses::DELIVERING : {
					return \delivery::STATUS_DELIVERING;
				}
				case OrderStatuses::LOST :
				case OrderStatuses::PROBLEM :
				case OrderStatuses::UPLOADING_ERROR :
				case OrderStatuses::UNKNOWN :
				case OrderStatuses::NOT_APPLICABLE : {
					return \delivery::STATUS_UNKNOWN;
				}
				case OrderStatuses::CANCELED : {
					return \delivery::STATUS_CANCELED;
				}
				case OrderStatuses::PARTIAL_RETURN :
				case OrderStatuses::RETURNED :
				case OrderStatuses::RETURNED_FROM_DELIVERY :
				case OrderStatuses::RETURNING :
				case OrderStatuses::RETURN_READY : {
					return \delivery::STATUS_RETURN;
				}
				default : {
					throw new \wrongParamException('Incorrect ApiShip Order status given');
				}
			}
		}
	}
