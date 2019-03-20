<?php

	/** Класс получения обобщённого отчета о посещаемости в разрезе часов */
	class visitCommonHours extends simpleStat {

		/** @var integer число выходных за период */
		private $holidays_count = 0;

		/** @var integer число будней за период */
		private $routine_count = 0;

		/** @inheritdoc */
		public function get() {
			$arrDetail = $this->getDetail();
			return [
				'detail' => $arrDetail['all'],
				'avg' => $this->getAvg(),
				'summ' => $this->getSumm(),
				'total' => $arrDetail['total']
			];
		}

		/**
		 * Возвращает количество посещений за выбранный интервал
		 * @return int
		 */
		private function getSumm() {
			$this->setUpVars();
			$sQrInterval = $this->getQueryInterval();
			$sQrHost = $this->getHostSQL('p');
			$sQrUsr = $this->getUserFilterWhere('pth');

			$sQr = <<<END
				SELECT
					COUNT(*) AS `cnt`
				FROM
					`cms_stat_hits` `h`
									INNER JOIN
						`cms_stat_pages` `p` ON `p`.`id` = `h`.`page_id`
						INNER JOIN `cms_stat_paths` `pth` ON `pth`.`id` = `h`.`path_id`
				WHERE
					h.`date` BETWEEN {$sQrInterval}
					 {$sQrHost}
					 {$sQrUsr}
				ORDER BY
					h.`date` ASC
END;

			$resSumm = $this->simpleQuery($sQr);
			return isset($resSumm[0]) ? (int) $resSumm[0]['cnt'] : 0;
		}

		/**
		 * Возвращает почасовую статистику о числе посещений в выбранном интервале
		 * @return array
		 */
		private function getDetail() {
			$this->setUpVars();
			$all = $this->simpleQuery('SELECT SQL_CALC_FOUND_ROWS COUNT(*) AS `cnt`, `hour`, UNIX_TIMESTAMP(h.`date`) AS `ts` FROM `cms_stat_hits` `h`
								 INNER JOIN `cms_stat_pages` `p` ON `p`.`id` = `h`.`page_id`
								 INNER JOIN `cms_stat_paths` `pth` ON `pth`.`id` = `h`.`path_id`
								  WHERE h.`date` BETWEEN ' . $this->getQueryInterval() . ' ' . $this->getHostSQL('p') . '
								   GROUP BY `hour`
									 ORDER BY `ts` ASC');

			$res = $this->simpleQuery('SELECT FOUND_ROWS() as `total`');
			$i_total = (int) $res[0]['total'];

			$all2 = [];

			foreach ($all as $iRec => $arrRec) {
				$all2[(int) date('G', $arrRec['ts'])] = $arrRec;
			}

			ksort($all2);

			return [
				'all' => $all2,
				'total' => $i_total
			];
		}

		/**
		 * Получает почасовой отчет за выходные и будни
		 * @return array
		 * @throws Exception
		 */
		private function getAvg() {
			$this->setUpVars();
			$connection = ConnectionPool::getInstance()->getConnection();
			$qry = "(SELECT 'routine' AS `type`, COUNT(*) / " . $this->routine_count . '.0 AS `avg`, `hour` FROM `cms_stat_hits` `h`
					 LEFT JOIN `cms_stat_holidays` `holidays` ON `h`.`day` = `holidays`.`day` AND `h`.`month` = `holidays`.`month`
					  INNER JOIN `cms_stat_pages` `p` ON `p`.`id` = `h`.`page_id`
					  INNER JOIN `cms_stat_paths` `pth` ON `pth`.`id` = `h`.`path_id`
					   WHERE `h`.`date` BETWEEN ' . $this->getQueryInterval() . ' ' . $this->getHostSQL('p') .
				$this->getUserFilterWhere('pth') . "
						AND `h`.`day_of_week` BETWEEN 1 AND 5 AND `holidays`.`id` IS NULL
						 GROUP BY `hour`)
					UNION
					(SELECT 'weekend' AS `type`, COUNT(*) / " . $this->holidays_count . '.0 AS `avg`, `hour` FROM `cms_stat_hits` `h`
					 LEFT JOIN `cms_stat_holidays` `holidays` ON `h`.`day` = `holidays`.`day` AND `h`.`month` = `holidays`.`month`
					  INNER JOIN `cms_stat_pages` `p` ON `p`.`id` = `h`.`page_id`
					  INNER JOIN `cms_stat_paths` `pth` ON `pth`.`id` = `h`.`path_id`
					   WHERE `h`.`date` BETWEEN ' . $this->getQueryInterval() . ' ' . $this->getHostSQL('p') .
				$this->getUserFilterWhere('pth') . '
						AND `h`.`day_of_week` NOT BETWEEN 1 AND 5 OR `holidays`.`id` IS NOT NULL
						 GROUP BY `hour`)';

			$queryResult = $connection->queryResult($qry);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);

			$result = [];

			foreach ($queryResult as $row) {
				$result[$row['type']][$row['hour']] = $row['avg'];
			}

			return $result;
		}

		/** Устанавливает количество выходных и будней */
		private function setUpVars() {
			$res = holidayRoutineCounter::count($this->start, $this->finish);
			$this->holidays_count = $res['holidays'];
			$this->routine_count = $res['routine'];
		}
	}

