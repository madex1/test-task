<?php

	/** Класс вычисления числа будних и выходных дней */
	class holidayRoutineCounter {

		/** @var array $results кеш результатов */
		public static $results = [];

		/**
		 * Вычисляет число будних и выходных дней
		 * @param integer $start начальная дата в формате unix timestamp
		 * @param integer $finish конечная дата в формате unix timestamp
		 * @return array
		 */
		public static function count($start, $finish) {
			if (empty(self::$results[md5($start . $finish)])) {
				$st = $start;
				$res = ['holidays' => 0, 'routine' => 0];
				while ($st <= strtotime('-1 day', $finish)) {
					$weekday = date('w', $st);
					if ($weekday >= 1 && $weekday <= 5) {
						$res['routine']++;
					} else {
						$res['holidays']++;
					}
					$st = strtotime('+1 day', $st);
				}

				$connection = ConnectionPool::getInstance()->getConnection();
				$result = $connection->queryResult("SELECT DATE_FORMAT(CONCAT('" . date('Y', $start) .
					"-', `month`, '-', `day`), '%w') AS `day_of_week` FROM `cms_stat_holidays` WHERE (`day` >= " . date('d', $start) .
					' AND `month` = ' . date('m', $start) . ' AND `day` <= ' . date('d', $finish) . ' AND `month` = ' .
					date('m', $finish) . ') OR (`month` > ' . date('m', $start) . ' AND `month` < ' . date('m', $finish) .
					') HAVING `day_of_week` BETWEEN 1 AND 5');
				$holidays_in_routine = $result->length();

				self::$results[md5($start . $finish)] = [
					'holidays' => $res['holidays'] + $holidays_in_routine,
					'routine' => $res['routine'] - $holidays_in_routine
				];
			}

			return self::$results[md5($start . $finish)];
		}
	}

