<?php

	/** Класс получения отчета о переходах на другие страницы с конкретной страницы */
	class pageNext extends simpleStat {

		/** @inheritdoc */
		protected $params = [
			'page_id' => '',
			'page_uri' => ''
		];

		/** @inheritdoc */
		public function get() {
			if ($this->params['page_uri']) {
				$connection = ConnectionPool::getInstance()->getConnection();
				$sql = "SELECT id FROM cms_stat_pages WHERE uri = '" . $connection->escape($this->params['page_uri']) . "' " .
					$this->getHostSQL() . ' LIMIT 1';
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$page_id = null;

				if ($result->length() > 0) {
					$fetchResult = $result->fetch();
					$page_id = array_shift($fetchResult);
				}
			} else {
				$page_id = $this->params['page_id'];
			}

			return $this->simpleQuery('SELECT COUNT(*) AS `abs`, `p`.`uri`, `p`.`id` FROM `cms_stat_hits` `h`
															 INNER JOIN `cms_stat_hits` `h2` ON `h2`.`prev_page_id` = `h`.`page_id` AND `h2`.`number_in_path` = `h`.`number_in_path` + 1 AND `h2`.`path_id` = `h`.`path_id`
															  INNER JOIN `cms_stat_pages` `p` ON `p`.`id` = `h2`.`page_id`
															   WHERE `h`.`date` BETWEEN ' . $this->getQueryInterval() .
				' AND `h`.`page_id` = ' . (int) $page_id . ' ' . $this->getHostSQL('p') . '
																GROUP BY `h2`.`page_id`
																 ORDER BY `abs` DESC');
		}
	}

