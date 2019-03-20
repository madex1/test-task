<?php

	/** Класс получения отчета о переходах по рекламным кампаниям OpenStat */
	class openstatCampaigns extends simpleStat {

		/** @inheritdoc */
		protected $params = [
			'source_id' => 0
		];

		/** @inheritdoc */
		public function get() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->query('SET @cnt := (SELECT COUNT(*) FROM `cms_stat_sources_openstat` `os`
										 INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `os`.`path_id`
										   WHERE `p`.`date` BETWEEN ' . $this->getQueryInterval() . ' ' . $this->getHostSQL('p') .
				$this->getUserFilterWhere('p') .
				((int) $this->params['source_id'] > 0 ? ' AND `os`.`source_id` =  ' . (int) $this->params['source_id'] : '') . ')');

			$result = $this->simpleQuery('SELECT COUNT(*) AS `total` FROM `cms_stat_sources_openstat` `os`
										 INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `os`.`path_id`
										  INNER JOIN `cms_stat_sources_openstat_campaign` `c` ON `c`.`id` = `os`.`campaign_id`
										   WHERE `p`.`date` BETWEEN ' . $this->getQueryInterval() . ' ' . $this->getHostSQL('p') .
				$this->getUserFilterWhere('p') .
				((int) $this->params['source_id'] > 0 ? ' AND `os`.`source_id` =  ' . (int) $this->params['source_id'] : ''));
			$i_total = (int) $result[0]['total'];

			$res = $this->simpleQuery("SELECT SQL_CALC_FOUND_ROWS COUNT(*) AS `abs`, COUNT(*) / @cnt * 100 AS `rel`, `c`.`name`, `c`.`id` as 'campaign_id' FROM `cms_stat_sources_openstat` `os`
										 INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `os`.`path_id`
										  INNER JOIN `cms_stat_sources_openstat_campaign` `c` ON `c`.`id` = `os`.`campaign_id`
										   WHERE `p`.`date` BETWEEN " . $this->getQueryInterval() . ' ' . $this->getHostSQL('p') .
				$this->getUserFilterWhere('p') .
				((int) $this->params['source_id'] > 0 ? ' AND `os`.`source_id` =  ' . (int) $this->params['source_id'] : '') . '
											GROUP BY `c`.`id`
											 ORDER BY `abs` DESC
											  LIMIT ' . $this->offset . ', ' . $this->limit, true);

			return [
				'all' => $res['result'],
				'summ' => $i_total,
				'total' => $res['FOUND_ROWS'],
				'source_id' => isset($this->params['source_id']) ? (int) $this->params['source_id'] : 0
			];
		}
	}

