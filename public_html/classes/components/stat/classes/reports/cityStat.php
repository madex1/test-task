<?php

	/** Класс получения отчета о наиболее активных городах */
	class cityStat extends simpleStat {

		/** @inheritdoc */
		public function get() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$sQuery = 'SELECT COUNT(*) AS `count`, `location`
					   FROM `cms_stat_users`
					   WHERE 1 ' . $this->getHostSQL() .
				(!empty($this->user_id) ? ' AND id IN ' . implode(', ', $this->user_id) : '') . '
					   GROUP BY `location`
					   ORDER BY `count` DESC LIMIT 15';
			$queryResult = $connection->queryResult($sQuery);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);

			$result = [];

			foreach ($queryResult as $row) {
				$result[] = $row;
			}

			return $result;
		}
	}

