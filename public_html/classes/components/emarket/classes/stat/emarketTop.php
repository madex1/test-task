<?php

	/**
	 * Класс отвечает за сбор информации о самых продаваемых товарах магазина.
	 *
	 * По умолчанию содержит только один отчет getTop():
	 *
	 * 1) Топ самых продаваемых товаров за период.
	 */
	class emarketTop {

		/**
		 * Собирает и сохраняет данные о товарах заказа
		 * @param order $order заказ
		 */
		public function addOrder($order) {
			$objects = umiObjectsCollection::getInstance();
			$connection = ConnectionPool::getInstance()->getConnection();
			$orderItems = $order->order_items;

			foreach ($orderItems as $item) {
				$item = $objects->getObject($item);

				if (umiCount($item->item_link) == 0 || !isset($item->item_link[0]) || !$item->item_link[0] instanceof iUmiEntinty) {
					continue;
				}

				/** @var iUmiEntinty $itemLink ; */
				$itemLink = $item->item_link[0];
				$itemId = (int) $itemLink->getId();
				$date = explode(' ', $order->getValue('order_date'));
				$date = (int) strtotime($date[0]);
				$itemName = $connection->escape($item->getName());
				$itemAmount = (int) $item->getValue('item_amount');
				$itemTotalPrice = (double) $item->getValue('item_total_price');
				$updateQuery = <<<SQL
UPDATE `cms3_emarket_top`
SET `title` = '$itemName',
	`amount` = `amount` + $itemAmount,
	`total_price` = `total_price` + $itemTotalPrice
WHERE
`id` = $itemId AND
`date` = $date;
SQL;

				$insertQuery = <<<SQL
INSERT INTO `cms3_emarket_top`
	(`id`, `date`, `title`, `amount`, `total_price`)
VALUES
	($itemId, $date, '$itemName', $itemAmount, $itemTotalPrice)
SQL;

				if ($this->isItemEntryExists($itemId, $date)) {
					$connection->query($updateQuery);
				} else {
					$connection->query($insertQuery);
				}
			}
		}

		/**
		 * Удаляет статистику по товарам заказа
		 * @param order $order заказ
		 */
		public function delOrder($order) {
			$objects = umiObjectsCollection::getInstance();
			$connection = ConnectionPool::getInstance()->getConnection();
			$orderItems = $order->order_items;

			foreach ($orderItems as $item) {
				$item = $objects->getObject($item);

				if (umiCount($item->item_link) == 0 || !isset($item->item_link[0]) || !$item->item_link[0] instanceof iUmiEntinty) {
					continue;
				}

				/** @var iUmiEntinty $itemLink ; */
				$itemLink = $item->item_link[0];
				$itemId = (int) $itemLink->getId();
				$date = explode(' ', $order->getValue('order_date'));
				$date = (int) strtotime($date[0]);
				$itemName = $connection->escape($item->getName());
				$itemAmount = (int) $item->getValue('item_amount');
				$itemTotalPrice = (double) $item->getValue('item_total_price');

				$updateQuery = <<<SQL
UPDATE `cms3_emarket_top`
SET	`title` = '$itemName',
	`amount` = `amount` - $itemAmount,
	`total_price` = `total_price` - $itemTotalPrice
WHERE
`id` = $itemId AND
`date` = $date;
SQL;
				if ($this->isItemEntryExists($itemId, $date)) {
					$connection->query($updateQuery);
				}
			}
		}

		/**
		 * Возвращает список данные самых продаваемых товаров
		 * @param array $range диапазон дат
		 * @param int $numberItems количество выводимых товаров
		 * @param string $sort поле таблицы для сортировки
		 * @return array
		 * @throws Exception
		 */
		public function getTop($range, $numberItems, $sort) {
			if ($sort == 'price') {
				$sort = 'total_price';
			} else {
				$sort = 'amount';
			}
			$connection = ConnectionPool::getInstance()->getConnection();
			$selectTop =
				"SELECT `id`, `title`, SUM(`amount`) AS 'amount', SUM(`total_price`) AS 'total_price' FROM `cms3_emarket_top` WHERE `amount` != 0 AND `total_price` !=0 AND `date` BETWEEN " .
				$range['fromDate'] . ' AND ' . $range['toDate'] . ' GROUP BY `id` ORDER BY `' . $sort . '` DESC LIMIT 0,' .
				$numberItems;
			$result = $connection->queryResult($selectTop);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			$top = [];

			if ($result->length() > 0) {
				foreach ($result as $row) {
					$top[] = $row;
				}
			}

			return $top;
		}

		/**
		 * Переиндексирует товары ряда заказов.
		 * Используется итерационо.
		 * @param int $limit количество переиндексируемых заказов
		 * @param int $page прогресс переиндексации
		 * @return array
		 * @throws selectorException
		 */
		public function recalculation($limit, $page) {
			$offset = $page * $limit;

			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('status_id')->equals(order::getStatusByCode('ready'));
			$sel->where('order_date')->less(strtotime(date('Y-m-d')));
			$sel->limit($offset, $limit);

			foreach ($sel->result() as $order) {
				$this->addOrder($order);
			}

			return [
				'page' => $page + 1,
				'current' => $offset + $limit
			];
		}

		/**
		 * Очищает статистику
		 * @throws Exception
		 */
		public function clearTableTop() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->query('DELETE FROM `cms3_emarket_top` WHERE `date` < ' . strtotime(date('Y-m-d')));
			return;
		}

		/**
		 * Возвращает количество заказов, которые требуется
		 * переиндексировать
		 * @return int
		 * @throws selectorException
		 */
		public function allOrdersRecalculate() {
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('status_id')->equals(order::getStatusByCode('ready'));
			$sel->where('order_date')->less(strtotime(date('Y-m-d')));
			return $sel->length();
		}

		/**
		 * Определяет существует ли статистика по товару на заданное время
		 * @param int $id идентификатор товара
		 * @param int $date время (timestamp)
		 * @return bool
		 */
		private function isItemEntryExists($id, $date) {
			$id = (int) $id;
			$date = (int) $date;
			$sql = <<<SQL
SELECT `id` FROM `cms3_emarket_top` WHERE `id` = $id AND `date` = $date LIMIT 0,1
SQL;
			$result = ConnectionPool::getInstance()->getConnection()
				->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);
			return $result->length() > 0;
		}
	}
