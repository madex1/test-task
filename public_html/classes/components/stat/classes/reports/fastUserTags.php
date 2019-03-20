<?php

	/** Класс получения отчета о тега пользователя статистики */
	class fastUserTags extends simpleStat {

		/** @inheritdoc */
		protected $params = [
			'user_id' => 0
		];

		/** @inheritdoc */
		public function get() {
			$result = [];

			$user_id = (int) $this->params['user_id'];
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
	SELECT DISTINCT
	STRAIGHT_JOIN se.name AS `tag` , COUNT( se.name ) AS `cnt` , sec.hit_id
	FROM cms_stat_paths sp, cms_stat_hits sh, cms_stat_events_collected sec, cms_stat_events se
	WHERE sp.user_id = '{$user_id}'
	AND sh.path_id = sp.id
	AND sec.hit_id = sh.id
	AND se.id = sec.event_id
	AND se.type =2
	GROUP BY se.name
	ORDER BY cnt DESC
SQL;
			$sql_result = $connection->queryResult($sql);
			$sql_result->setFetchType(IQueryResult::FETCH_ASSOC);

			$result['labels'] = [];

			foreach ($sql_result as $row) {
				$result['labels'][] = $row;
			}

			return $result;
		}
	}

