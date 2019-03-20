<?php

	/** Класс получения отчета о посещениях за период */
	class hostsCommon extends simpleStat {

		/** @var integer число выходных за период */
		private $holidays_count = 0;

		/** @var integer число будней за период */
		private $routine_count = 0;

		/** @inheritdoc */
		public function __construct($finish = null, $interval = null) {
			$finish = time();
			parent::__construct($finish);
		}

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

			$sQr = '
				SELECT SQL_CALC_FOUND_ROWS DISTINCT u.ip
				FROM `cms_stat_paths` `p` , `cms_stat_users` `u`
				WHERE
					 `p`.user_id = u.id AND
					`date` BETWEEN ' . $sQrInterval . '
					' . $this->getHostSQL('p') . $this->getUserFilterWhere('p');

			$this->simpleQuery($sQr);
			$resSumm = $this->simpleQuery('SELECT FOUND_ROWS() as `cnt`');
			return (int) $resSumm[0]['cnt'];
		}

		/**
		 * Возвращает сводную статистику о числе посещений за каждый из дней выбранного интервала
		 * @return array
		 */
		private function getDetail() {
			$this->setUpVars();
			$all = $this->simpleQuery('SELECT SQL_CALC_FOUND_ROWS COUNT(*) AS `cnt`, UNIX_TIMESTAMP(`p`.`date`) AS `ts` FROM `cms_stat_paths` `p`
										 INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
										  INNER JOIN `cms_stat_sources_sites` `ss` ON `ss`.`id` = `s`.`concrete_src_id`
											WHERE `p`.`date` BETWEEN ' . $this->getQueryInterval() . ' ' .
				$this->getHostSQL('p') . $this->getUserFilterWhere('p') . " AND `s`.`src_type` = 1
											 GROUP BY DATE_FORMAT(`p`.`date`, '%d'), DATE_FORMAT(`p`.`date`, '%m')
											  ORDER BY `cnt` DESC");
			$res = $this->simpleQuery('SELECT FOUND_ROWS() as `total`');
			$i_total = (int) $res[0]['total'];

			return [
				'all' => $all,
				'total' => $i_total
			];
		}

		/**
		 * Возвращает среднее количество посещений за выходные и будние
		 * @return array
		 * @throws Exception
		 */
		private function getAvg() {
			$this->setUpVars();
			$connection = ConnectionPool::getInstance()->getConnection();
			$qry = "(SELECT 'routine' AS `type`, COUNT(*) / " . $this->routine_count . '.0 AS `avg` FROM `cms_stat_paths` `p`
					 INNER JOIN `cms_stat_hits` `h` ON `h`.`path_id` = `p`.`id` AND `h`.`number_in_path` = 1
					  INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
					   INNER JOIN `cms_stat_sources_sites` `ss` ON `ss`.`id` = `s`.`concrete_src_id`
						LEFT JOIN `cms_stat_holidays` `holidays` ON `h`.`day` = `holidays`.`day` AND `h`.`month` = `holidays`.`month`
						 WHERE `p`.`date` BETWEEN ' . $this->getQueryInterval() . ' ' . $this->getHostSQL('p') . " AND `s`.`src_type` = 1
						  AND `day_of_week` BETWEEN 1 AND 5 AND `holidays`.`id` IS NULL)
					UNION
					(SELECT 'weekend' AS `type`, COUNT(*) / " . $this->holidays_count . ' AS `avg` FROM `cms_stat_paths` `p`
					 INNER JOIN `cms_stat_hits` `h` ON `h`.`path_id` = `p`.`id` AND `h`.`number_in_path` = 1
					  INNER JOIN `cms_stat_sources` `s` ON `s`.`id` = `p`.`source_id`
					   INNER JOIN `cms_stat_sources_sites` `ss` ON `ss`.`id` = `s`.`concrete_src_id`
						LEFT JOIN `cms_stat_holidays` `holidays` ON `h`.`day` = `holidays`.`day` AND `h`.`month` = `holidays`.`month`
						 WHERE `p`.`date` BETWEEN ' . $this->getQueryInterval() . ' ' . $this->getHostSQL('p') . ' AND `s`.`src_type` = 1
						  AND (`day_of_week` NOT BETWEEN 1 AND 5 OR `holidays`.`id` IS NOT NULL))';

			$queryResult = $connection->queryResult($qry);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);

			$result = [];

			foreach ($queryResult as $row) {
				$result[$row['type']] = $row['avg'];
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

