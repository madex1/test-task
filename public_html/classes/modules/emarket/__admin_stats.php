<?php

	abstract class __emarket_admin_stats extends baseModuleAdmin {

		protected static $allowedStats = array(
			'RegUsers', 'OrderAll', 'OrderStatusNull', 'OrderPayment', 'OrderCanceled', 'OrderReady', 'SumAll', 'SumMiddle', 'AddItems'
		);

		public function stats() {
			$this->setDataType("list");
			$this->setActionType("view");
			$range = $this->getDateRange(getRequest('fromDate'), getRequest('toDate'));

			$params = array(
				"stats"=>array(),
				"popular"=>array(
					'@max-popular' => mainConfiguration::getInstance()->get('modules', 'emarket.popular.max-items'),
					'@currency' => mainConfiguration::getInstance()->get('system', 'default-currency'),
				)
			);
			foreach (self::$allowedStats as $stat) {
				$params['stats'][$stat.":stat-".$stat] = NULL;
			}

			$data = $this->prepareData($params, "settings");
			$data = array_merge($data, array(
				'@fromDate' => $range['fromDate'],
				'@toDate' => $range['toDate'],
			));

			$this->setData($data);
			return $this->doData();
		}

		/** Запускает сбор статистики */
		public function statRun() {
			$statName = getRequest('param0');
			$range = $this->getDateRange(getRequest('param1'), getRequest('param2'));

			if ( array_search($statName, self::$allowedStats) !== FALSE) {
				$statName = 'test' . $statName;
			} else {
				throw new coreException("Selected stat not allowed");
			}
			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->push(
				json_encode(array(
						'result' => $this->$statName($range,getRequest('param3'))
					)
				)
			);
			$buffer->end();
		}

		public function testRegUsers($range, $all) {
			if ($all != 'all') {
				$sel = new selector('objects');
				$sel->types('object-type')->name('users', 'user');
				$sel->where('register_date')->between($range['fromDate'], $range['toDate']);
				return array(
					'status' => true,
					'value' => $sel->length,
				);
			} else {
				$sel = new selector('objects');
				$sel->types('object-type')->name('users', 'user');
				return array(
					'status' => true,
					'value' => $sel->length,
				);
			}

			return false;
		}

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

            return array(
                'status' => true,
                'value' => $sel->length,
            );
		}

		public function testOrderPayment($range, $all) {
			if ($all != 'all') {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->equals(order::getStatusByCode('payment'));
				$sel->where('order_date')->between($range['fromDate'], $range['toDate']);

				return array(
					'status' => true,
					'value' => $sel->length,
				);
			} else {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->equals(order::getStatusByCode('payment'));

				return array(
					'status' => true,
					'value' => $sel->length,
				);
			}
		}

		public function testOrderCanceled($range, $all) {
			if ($all != 'all') {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->equals(order::getStatusByCode('canceled'));
				$sel->where('order_date')->between($range['fromDate'], $range['toDate']);

				return array(
					'status' => true,
					'value' => $sel->length,
				);
			} else {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->equals(order::getStatusByCode('canceled'));

				return array(
					'status' => true,
					'value' => $sel->length,
				);
			}
		}

		public function testOrderReady($range, $all) {
			if ($all != 'all') {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->equals(order::getStatusByCode('ready'));
				$sel->where('order_date')->between($range['fromDate'], $range['toDate']);

				return array(
					'status' => true,
					'value' => $sel->length,
				);
			} else {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->equals(order::getStatusByCode('ready'));

				return array(
					'status' => true,
					'value' => $sel->length,
				);
			}
		}

		public function testOrderAll($range, $all) {
			if ($all != 'all') {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('order_date')->between($range['fromDate'], $range['toDate']);
				$sel->where('status_id')->isnull(false);

				return array(
					'status' => true,
					'value' => $sel->length,
				);
			} else {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->isnull(false);

				return array(
					'status' => true,
					'value' => $sel->length,
				);
			}
		}

		public function testSumAll($range, $all) {
			if ($all != 'all') {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->equals(order::getStatusByCode('ready'));
				$sel->where('order_date')->between($range['fromDate'], $range['toDate']);
				$sum = 0;
				foreach($sel->result as $order) {
					$sum += $order->total_price;
				}

				return array(
					'status' => true,
					'value' => $sum ? preg_replace('/\./', ',', round($sum, 2)) : 0,
				);
			} else {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->equals(order::getStatusByCode('ready'));
				$sum = 0;
				foreach($sel->result as $order) {
					$sum += $order->total_price;
				}

				return array(
					'status' => true,
					'value' => $sum ? preg_replace('/\./', ',', round($sum, 2)) : 0,
				);
			}
		}

		public function testSumMiddle($range, $all) {
			if ($all != 'all') {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->equals(order::getStatusByCode('ready'));
				$sel->where('order_date')->between($range['fromDate'], $range['toDate']);
				$sum = 0;
				$total = $sel->total;
				foreach($sel->result as $order) {
					$sum += $order->total_price;
				}

				$value = 0;
				if ($total != 0 ) {
					$value = $sum/$total;
				}

				return array(
					'status' => true,
					'value' => $value ? preg_replace('/\./', ',', round($value, 2)) : 0,
				);
			} else {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'order');
				$sel->where('status_id')->equals(order::getStatusByCode('ready'));
				$sum = 0;
				$total = $sel->total;
				foreach($sel->result as $order) {
					$sum += $order->total_price;
				}

				$value = $sum/$total;

				return array(
					'status' => true,
					'value' => $value ? preg_replace('/\./', ',', round($value, 2)) : 0,
				);
			}
		}

		public function testAddItems($range, $all) {
			if ($all != 'all') {
				$sel = new selector('objects');
				$sel->types('hierarchy-type')->name('catalog', 'object');
				$sel->where('date_create_object')->between($range['fromDate'], $range['toDate']);

				return array(
					'status' => true,
					'value' => $sel->length,
				);
			} else {
				$sel = new selector('objects');
				$sel->types('hierarchy-type')->name('catalog', 'object');

				return array(
					'status' => true,
					'value' => $sel->length,
				);
			}
		}

		public function getMostPopularProduct() {
			$range = $this->getDateRange(getRequest('param0'), getRequest('param1'));
			$emarketTop = new emarketTop();
			$config = mainConfiguration::getInstance();
			$sort = getRequest('sort');
			$result = $emarketTop->getTop($range, $config->get('modules', 'emarket.popular.max-items'), $sort);
			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->push(
				json_encode(array(
						'result' => $result
					)
				)
			);
			$buffer->end();
		}

		public function partialRecalc() {
			if (isDemoMode()) {
				return false;
			}

			$this->setDataType("settings");
			$this->setActionType("view");

			$page = (int) getRequest("page");

			$emarketTop = new emarketTop();
			if ($page == 0) {
				$emarketTop->clearTableTop();
				regedit::getInstance()->setVal('//modules/emarket/last-reindex-result', false);
				regedit::getInstance()->setVal('//modules/emarket/last-reindex-date', date('Y-m-d'));
			}
			$config = mainConfiguration::getInstance();
			$total = (int) $emarketTop->allOrdersRecalculate();
			$limit = $config->get('modules', 'emarket.reindex.max-items');
			if ($limit == 0) {
				$limit = 5;
			}
			$result = $emarketTop->recalculation($limit, $page);

			if ($result['current'] >= $total) {
				regedit::getInstance()->setVal('//modules/emarket/last-reindex-result', true);
			}

			$data = Array(
				'index-items' => Array(
					'attribute:current' => $result['current'],
					'attribute:total' => $total,
					'attribute:page' => $result['page']
				)
			);

			$this->setData($data);
			return $this->doData();
		}

		public function getLastReindexDate() {
			$this->setDataType("list");
			$this->setActionType("view");

			$reindexDate = regedit::getInstance()->getVal('//modules/emarket/last-reindex-date');
			$reindexResult = (bool) regedit::getInstance()->getVal('//modules/emarket/last-reindex-result');

			$this->setData(array(
				'reindexDate' => $reindexDate,
				'reindexResult' => $reindexResult
			), 2);

			return $this->doData();
		}
	};
?>