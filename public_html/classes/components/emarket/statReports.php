<?php

	use UmiCms\Service;

	/**
	 * Класс статистических отчетов магазина.
	 *
	 * Доступные отчеты:
	 *
	 * 1) Количество зарегистрированных пользователей за заданный диапазон дат;
	 * 2) Количество недооформленных заказов (брошенных корзин) за заданный диапазон дат;
	 * 3) Количество заказов со статусом "Оплачивается" за заданный диапазон дат;
	 * 4) Количество заказов со статусом "Отменен" за заданный диапазон дат;
	 * 5) Количество заказов со статусом "Готов" за заданный диапазон дат;
	 * 6) Количесто товаров (объектов каталога), добавленных за заданный диапазон дат;
	 * 7) Сумма стоимости заказов со статусом "Готов" за заданный диапазон дат;
	 * 8) Средняя стоимости заказа со статусом "Готов" за заданный диапазон дат;
	 * 9) Топ самых продаваемых товаров за заданный период
	 */
	class EmarketStatReports {

		/** @var emarket $module */
		public $module;

		/**
		 * Запускает выполнение отчета и выводит его результат в буффер.
		 * @throws coreException
		 */
		public function statRun() {
			$statName = getRequest('param0');
			/** @var emarket $module */
			$module = $this->module;
			/** @var EmarketAdmin $admin */
			$admin = $module->getImplementedInstance($module::ADMIN_CLASS);

			if (!$admin instanceof EmarketAdmin) {
				throw new coreException('Class EmarketAdmin must be implemented');
			}

			$range = $admin->getDateRange(getRequest('param1'), getRequest('param2'));

			if (in_array($statName, $module->allowedReports)) {
				$statName = 'test' . $statName;
			} else {
				throw new coreException('Selected stat not allowed');
			}

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->push(
				json_encode([
						'result' => $this->$statName($range, getRequest('param3'))
					]
				)
			);
			$buffer->end();
		}

		/**
		 * Возвращает количество зарегистрированных пользователей за заданный диапазон дат
		 * @param array $range диапазон дат
		 * @param string $all если = 'all', то вернет количество за все время
		 * @return array
		 * @throws selectorException
		 */
		public function testRegUsers($range, $all) {
			if ($all != 'all') {
				$sel = new selector('objects');
				$sel->types('object-type')->name('users', 'user');
				$sel->where('register_date')->between($range['fromDate'], $range['toDate']);
				return [
					'status' => true,
					'value' => $sel->length(),
				];
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'user');
			return [
				'status' => true,
				'value' => $sel->length(),
			];
		}

		/**
		 * Возвращает количество недооформленных заказов (брошенных корзин) за заданный диапазон дат
		 * @param array $range диапазон дат
		 * @param string $all если = 'all', то вернет количество за все время
		 * @return array
		 * @throws selectorException
		 */
		public function testOrderStatusNull($range, $all) {
			if ($all != 'all') {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->isnull(true);
				$sel->where('order_items')->isnotnull();
				$sel->where('order_create_date')->between($range['fromDate'], $range['toDate']);
				$sel->group('id');
			} else {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->isnull(true);
				$sel->where('order_items')->isnotnull();
				$sel->group('id');
			}

			return [
				'status' => true,
				'value' => $sel->length(),
			];
		}

		/**
		 * Возвращает количество заказов со статусом "Оплачивается" за заданный диапазон дат
		 * @param array $range диапазон дат
		 * @param string $all если = 'all', то вернет количество за все время
		 * @return array
		 * @throws selectorException
		 */
		public function testOrderPayment($range, $all) {
			if ($all != 'all') {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->equals(order::getStatusByCode('payment'));
				$sel->where('order_date')->between($range['fromDate'], $range['toDate']);

				return [
					'status' => true,
					'value' => $sel->length(),
				];
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('status_id')->equals(order::getStatusByCode('payment'));

			return [
				'status' => true,
				'value' => $sel->length(),
			];
		}

		/**
		 * Возвращает количество заказов со статусом "Отменен" за заданный диапазон дат
		 * @param array $range диапазон дат
		 * @param string $all если = 'all', то вернет количество за все время
		 * @return array
		 * @throws selectorException
		 */
		public function testOrderCanceled($range, $all) {
			if ($all != 'all') {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->equals(order::getStatusByCode('canceled'));
				$sel->where('order_date')->between($range['fromDate'], $range['toDate']);

				return [
					'status' => true,
					'value' => $sel->length(),
				];
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('status_id')->equals(order::getStatusByCode('canceled'));

			return [
				'status' => true,
				'value' => $sel->length(),
			];
		}

		/**
		 * Возвращает количество заказов со статусом "Готов" за заданный диапазон дат
		 * @param array $range диапазон дат
		 * @param string $all если = 'all', то вернет количество за все время
		 * @return array
		 * @throws selectorException
		 */
		public function testOrderReady($range, $all) {
			if ($all != 'all') {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->equals(order::getStatusByCode('ready'));
				$sel->where('order_date')->between($range['fromDate'], $range['toDate']);

				return [
					'status' => true,
					'value' => $sel->length(),
				];
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('status_id')->equals(order::getStatusByCode('ready'));

			return [
				'status' => true,
				'value' => $sel->length(),
			];
		}

		/**
		 * Возвращает количество оформленных заказов за заданный диапазон дат
		 * @param array $range диапазон дат
		 * @param string $all если = 'all', то вернет количество за все время
		 * @return array
		 * @throws selectorException
		 */
		public function testOrderAll($range, $all) {
			if ($all != 'all') {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('order_date')->between($range['fromDate'], $range['toDate']);
				$sel->where('status_id')->isnull(false);

				return [
					'status' => true,
					'value' => $sel->length(),
				];
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('status_id')->isnull(false);

			return [
				'status' => true,
				'value' => $sel->length(),
			];
		}

		/**
		 * Возвращает сумму стоимости заказов со статусом "Готов" за заданный диапазон дат
		 * @param array $range диапазон дат
		 * @param string $all если = 'all', то вернет количество за все время
		 * @return array
		 * @throws selectorException
		 */
		public function testSumAll($range, $all) {
			if ($all != 'all') {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->equals(order::getStatusByCode('ready'));
				$sel->where('order_date')->between($range['fromDate'], $range['toDate']);
				$sum = 0;
				foreach ($sel->result() as $order) {
					$sum += $order->total_price;
				}

				return [
					'status' => true,
					'value' => $sum ? preg_replace('/\./', ',', round($sum, 2)) : 0,
				];
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('status_id')->equals(order::getStatusByCode('ready'));
			$sum = 0;
			foreach ($sel->result() as $order) {
				$sum += $order->total_price;
			}

			return [
				'status' => true,
				'value' => $sum ? preg_replace('/\./', ',', round($sum, 2)) : 0,
			];
		}

		/**
		 * Возвращает среднюю стоимости заказа со статусом "Готов" за заданный диапазон дат
		 * @param array $range диапазон дат
		 * @param string $all если = 'all', то вернет количество за все время
		 * @return array
		 * @throws selectorException
		 */
		public function testSumMiddle($range, $all) {
			if ($all != 'all') {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->equals(order::getStatusByCode('ready'));
				$sel->where('order_date')->between($range['fromDate'], $range['toDate']);
				$sum = 0;
				$total = $sel->total;
				foreach ($sel->result() as $order) {
					$sum += $order->total_price;
				}

				$value = 0;
				if ($total != 0) {
					$value = $sum / $total;
				}

				return [
					'status' => true,
					'value' => $value ? preg_replace('/\./', ',', round($value, 2)) : 0,
				];
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('status_id')->equals(order::getStatusByCode('ready'));
			$sum = 0;
			$total = $sel->total;
			foreach ($sel->result() as $order) {
				$sum += $order->total_price;
			}

			$value = $sum / $total;

			return [
				'status' => true,
				'value' => $value ? preg_replace('/\./', ',', round($value, 2)) : 0,
			];
		}

		/**
		 * Возвращает количесто товаров (объектов каталога), добавленных за заданный диапазон дат
		 * @param array $range диапазон дат
		 * @param string $all если = 'all', то вернет количество за все время
		 * @return array
		 * @throws selectorException
		 */
		public function testAddItems($range, $all) {
			if ($all != 'all') {
				$sel = new selector('objects');
				$sel->types('hierarchy-type')->name('catalog', 'object');
				$sel->where('date_create_object')->between($range['fromDate'], $range['toDate']);

				return [
					'status' => true,
					'value' => $sel->length(),
				];
			}

			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('catalog', 'object');

			return [
				'status' => true,
				'value' => $sel->length(),
			];
		}

		/**
		 * Выводит в буффер список самых продаваемых товаров за заданный период
		 * @throws coreException
		 */
		public function getMostPopularProduct() {
			/** @var emarket $module */
			$module = $this->module;
			/** @var EmarketAdmin $admin */
			$admin = $module->getImplementedInstance($module::ADMIN_CLASS);

			if (!$admin instanceof EmarketAdmin) {
				throw new coreException('Class EmarketAdmin must be implemented');
			}

			$range = $admin->getDateRange(getRequest('param0'), getRequest('param1'));
			$emarketTop = new emarketTop();
			$config = mainConfiguration::getInstance();
			$sort = getRequest('sort');
			$result = $emarketTop->getTop($range, $config->get('modules', 'emarket.popular.max-items'), $sort);

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->push(
				json_encode([
						'result' => $result
					]
				)
			);
			$buffer->end();
		}
	}
