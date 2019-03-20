<?php

	/** Класс получения отчета о количестве просмотров разделов за период */
	class sectionHits extends simpleStat {

		/** @inheritdoc */
		public function get() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->query('SET @all = (SELECT COUNT(*) FROM `cms_stat_hits` `h`
										INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
										 WHERE `h`.`date` BETWEEN ' . $this->getQueryInterval() . ' ' . $this->getHostSQL('p') .
				$this->getUserFilterWhere('p') . ')');

			$sQueryInterval = $this->getQueryInterval();
			$sQr = '
			SELECT
				COUNT(*) AS `abs`
			FROM
				`cms_stat_pages` `p`
				INNER JOIN `cms_stat_hits` `h` ON `h`.`page_id` = `p`.`id`
				' . $this->getUserFilterTable('id', 'h.path_id') . '
			WHERE
				`h`.`date` BETWEEN ' . $sQueryInterval . '
				AND `p`.`host_id` = ' . $this->host_id . $this->getUserFilterWhere() . '';

			$result = $this->simpleQuery($sQr);
			$i_summ = isset($result[0]) ? (int) $result[0]['abs'] : 0;

			$arrQr = $this->simpleQuery('SELECT SQL_CALC_FOUND_ROWS COUNT(*) AS `abs`, COUNT(*) / @all * 100 AS `rel`, `p`.`section` FROM `cms_stat_pages` `p`
										 INNER JOIN `cms_stat_hits` `h` ON `h`.`page_id` = `p`.`id`
										 ' . $this->getUserFilterTable('id', 'h.path_id') . '
										  WHERE `h`.`date` BETWEEN ' . $this->getQueryInterval() . ' ' . $this->getHostSQL('p') .
				$this->getUserFilterWhere() . '
										   GROUP BY `p`.`section`
											ORDER BY `abs` DESC
											 LIMIT ' . $this->offset . ', ' . $this->limit, true);

			return [
				'all' => $arrQr['result'],
				'summ' => $i_summ,
				'total' => $arrQr['FOUND_ROWS']
			];
		}

		/**
		 * Возвращает статистику для подразделов
		 * @param string $section корневой раздел
		 * @return array
		 * @throws Exception
		 */
		public function getIncluded($section) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->query("SET @all = (SELECT COUNT(*) FROM `cms_stat_pages` `p`
						 INNER JOIN `cms_stat_hits` `h` ON `h`.`page_id` = `p`.`id`
						  WHERE `p`.`section` = '" . $connection->escape($section) . "' AND `h`.`date` BETWEEN " .
				$this->getQueryInterval() . ' ' . $this->getHostSQL('p') . ')');

			$result = $this->simpleQuery("SELECT COUNT(*) AS `summ` FROM `cms_stat_pages` `p`
										 INNER JOIN `cms_stat_hits` `h` ON `h`.`page_id` = `p`.`id`
										  WHERE `p`.`section` = '" . $connection->escape($section) . "' AND `h`.`date` BETWEEN " .
				$this->getQueryInterval() . ' ' . $this->getHostSQL('p'));
			$i_summ = (int) $result[0]['summ'];

			$arrQr = $this->simpleQuery("SELECT SQL_CALC_FOUND_ROWS COUNT(*) AS `abs`, COUNT(*) / @all * 100 AS `rel`, `p`.`uri`, `p`.`section`, UNIX_TIMESTAMP(`h`.`date`) AS `ts` FROM `cms_stat_pages` `p`
										 INNER JOIN `cms_stat_hits` `h` ON `h`.`page_id` = `p`.`id`
										  WHERE `p`.`section` = '" . $connection->escape($section) . "' AND `h`.`date` BETWEEN " .
				$this->getQueryInterval() . ' ' . $this->getHostSQL('p') . '
										   GROUP BY `p`.`id`
											ORDER BY `abs` DESC
											 LIMIT ' . $this->offset . ', ' . $this->limit, true);

			return [
				'all' => $arrQr['result'],
				'summ' => $i_summ,
				'total' => $arrQr['FOUND_ROWS']
			];
		}
	}

