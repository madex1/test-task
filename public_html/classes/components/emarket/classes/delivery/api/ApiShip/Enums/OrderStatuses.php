<?php

	namespace UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums;

	use UmiCms\Classes\System\Enums\Enum;

	/**
	 * Перечисление статусов заказа в ApiShip
	 * @package UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Enums
	 */
	class OrderStatuses extends Enum {

		/** @const string PENDING статус заказа "Ожидает обновления статуса" */
		const PENDING = 'pending';

		/** @const string DELIVERED статус заказа "Доставлен получателю" */
		const DELIVERED = 'delivered';

		/** @const string DELIVERED статус заказа "Передан на доставку в пункте назначения" */
		const DELIVERING = 'delivering';

		/** @const string CANCELED статус заказа "Доставка отменена" */
		const CANCELED = 'deliveryCanceled';

		/** @const string LOST статус заказа "Утерян" */
		const LOST = 'lost';

		/** @const string NOT_APPLICABLE статус заказа "Невозможно обработать" */
		const NOT_APPLICABLE = 'notApplicable';

		/** @const string ON_POINT_IN статус заказа "Принят на склад в пункте отправления" */
		const ON_POINT_IN = 'onPointIn';

		/** @const string ON_POINT_OUT статус заказа "Прибыл на склад в пункте назначения" */
		const ON_POINT_OUT = 'onPointOut';

		/** @const string ON_WAY статус заказа "В пути" */
		const ON_WAY = 'onWay';

		/** @const string PARTIAL_RETURN статус заказа "Частичный возврат" */
		const PARTIAL_RETURN = 'partialReturn';

		/** @const string PROBLEM статус заказа "Возникла проблема" */
		const PROBLEM = 'problem';

		/** @const string READY_FOR_RECIPIENT статус заказа "Готов к выдаче в пункте назначения" */
		const READY_FOR_RECIPIENT = 'readyForRecipient';

		/** @const string RETURNED статус заказа "Возвращен отправителю" */
		const RETURNED = 'returned';

		/** @const string RETURNED_FROM_DELIVERY статус заказа "Возвращен с доставки" */
		const RETURNED_FROM_DELIVERY = 'returnedFromDelivery';

		/** @const string RETURNING статус заказа "Возвращается отправителю" */
		const RETURNING = 'returning';

		/** @const string RETURN_READY статус заказа "Подготовлен возврат" */
		const RETURN_READY = 'returnReady';

		/** @const string UNKNOWN статус заказа "Неизвестный статус" */
		const UNKNOWN = 'unknown';

		/** @const string UPLOADED статус заказа "Информация успешно загружена в систему перевозщика" */
		const UPLOADED = 'uploaded';

		/** @const string UPLOADING статус заказа "Загрузка информации в систему перевозщика" */
		const UPLOADING = 'uploading';

		/** @const string UPLOADING_ERROR статус заказа "Ошибка передачи информации в систему перевозщика" */
		const UPLOADING_ERROR = 'uploadingError';

		/**
		 * Возвращает идентификаторы и названия статусов
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
		 * Возвращает наименование статуса по его ид
		 * @param int $id идентификатор заказа
		 * @return null|string
		 */
		protected function getTitleById($id) {
			$i18GroupKey = 'emarket';
			return getLabel('label-api-ship-order-status-' . $id, $i18GroupKey);
		}

		/** @inheritdoc */
		protected function getDefaultValue() {
			return self::PENDING;
		}
	}
