<?php

	/** Класс получения отчета о путях на сайте за период */
	class paths extends simpleStat {

		/** @inheritdoc */
		protected $params = ['path' => ''];

		/** @inheritdoc */
		public function get() {
			return [
				'detail' => $this->getDetail(),
				'path' => $this->getPath()
			];
		}

		/**
		 * Возвращает сводную статистику
		 * @return array
		 * @throws Exception
		 */
		public function getDetail() {
			$path = explode('/', $this->params['path']);
			$connection = ConnectionPool::getInstance()->getConnection();

			if (!isset($path[0]) || !$path[0]) {
				$connection->query('SET @all = (SELECT COUNT(*) FROM `cms_stat_paths`
							  WHERE `date` BETWEEN ' . $this->getQueryInterval() . ' AND `host_id` = ' . $this->host_id .
					$this->getUserFilterWhere() . ')');

				return $this->simpleQuery('SELECT COUNT(*) AS `abs`, COUNT(*) / @all * 100 AS `rel`, `p`.`uri`, `p`.`id` FROM `cms_stat_hits` `h`
											 INNER JOIN `cms_stat_pages` `p` ON `p`.`id` = `h`.`page_id`
											  WHERE `h`.`date` BETWEEN ' . $this->getQueryInterval() .
					' AND `h`.`number_in_path` = 1 ' . $this->getHostSQL('p') . '
											   GROUP BY `h`.`page_id`
												ORDER BY `abs` DESC
												 LIMIT ' . $this->offset . ', ' . $this->limit);
			}

			$level = umiCount($path);
			$qry = "SELECT COUNT(*) AS `abs`, COUNT(*) / @all * 100 AS `rel`, IFNULL(`p`.`uri`, '<exit>') AS `uri`, `p`.`id`";
			$common = ' FROM `cms_stat_hits` `h' . $level . '`';

			$last = array_pop($path);

			$key = 0;

			foreach ($path as $key => $val) {
				$current = $level - $key - 1;
				$common .= ' INNER JOIN `cms_stat_hits` `h' . $current . '` ON `h' . $current . '`.`path_id` = `h' . $level .
					'`.`path_id` AND `h' . $current . '`.`page_id` = ' . (int) $val . ' AND `h' . $current . '`.`number_in_path` = ' .
					($key + 1);
			}

			$common .= ' LEFT JOIN `cms_stat_hits` `n` ON `n`.`path_id` = `h' . $level . '`.`path_id` AND `n`.`prev_page_id` = `h' .
				$level . '`.`page_id` AND `n`.`number_in_path` = ' . ($level + 1) . '
					   LEFT JOIN `cms_stat_pages` `p` ON `n`.`page_id` = `p`.`id`
						WHERE `h' . ($key + 1) . '`.`date` BETWEEN ' . $this->getQueryInterval() . ' AND `h' . $level .
				'`.`page_id` = ' . (int) $last . ' AND `h' . $level . '`.`number_in_path` = ' . $level;

			$qry = $qry . $common . ' GROUP BY `p`.`id` ORDER BY `abs` DESC';

			$count_qry = 'SET @all = (SELECT COUNT(*) AS `cnt` ' . $common . ')';

			$connection->query($count_qry);
			return $this->simpleQuery($qry);
		}

		/**
		 * Возвращает пути на сайте
		 * @return array
		 * @throws Exception
		 */
		public function getPath() {
			$path = explode('/', $this->params['path']);
			$str = '';

			foreach ($path as $val) {
				$str .= (int) $val . ', ';
			}

			$str = mb_substr($str, 0, -2);
			$connection = ConnectionPool::getInstance()->getConnection();
			$qry = 'SELECT `id`, `uri` FROM `cms_stat_pages` WHERE `id` IN (' . $str . ')';
			$queryResult = $connection->queryResult($qry);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);

			$result = [];

			foreach ($queryResult as $row) {
				$result[$row['id']] = $row['uri'];
			}

			reset($path);

			$return = [];

			foreach ($path as $val) {
				if (isset($result[$val])) {
					$return[] = $result[$val];
				}
			}

			return $return;
		}
	}

