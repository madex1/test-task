<?php

	/** Класс получения отчета о "собранных" тегах за период */
	class allTags extends simpleStat {

		/** @inheritdoc */
		protected $params = [
			'user_id' => 0
		];

		/** @inheritdoc */
		public function get() {
			$result = [];
			$hids = [];
			$connection = ConnectionPool::getInstance()->getConnection();

			if (is_array($this->user_id) && !empty($this->user_id)) {
				$sql = 'SELECT hit.id as `hid` FROM cms_stat_hits hit,
						cms_stat_paths path WHERE
						hit.path_id=path.id AND path.user_id IN (' . implode(',', $this->user_id) . ') AND
						path.date BETWEEN ' . $this->getQueryInterval();
				$sql_result = $connection->queryResult($sql);
				$sql_result->setFetchType(IQueryResult::FETCH_ASSOC);

				foreach ($sql_result as $row) {
					$hids[] = $row['hid'];
				}
			} elseif (is_array($this->user_login) && !empty($this->user_login)) {
				$sql = 'SELECT hit.id as `hid` FROM cms_stat_hits hit,
						cms_stat_paths path, cms_stat_users user WHERE
						hit.path_id=path.id AND path.user_id=user.id AND
						path.date BETWEEN ' . $this->getQueryInterval() . ' AND
						user.login IN (' . implode(',', $this->user_login) . ')';
				$sql_result = $connection->queryResult($sql);
				$sql_result->setFetchType(IQueryResult::FETCH_ASSOC);

				foreach ($sql_result as $row) {
					$hids[] = $row['hid'];
				}
			} else {
				$sql = 'SELECT hit.id as `hid` FROM cms_stat_hits hit,
						cms_stat_paths path, cms_stat_users user WHERE
						hit.path_id=path.id AND path.user_id=user.id AND
						path.date BETWEEN ' . $this->getQueryInterval() . ' ORDER BY hid DESC LIMIT 300';
				$sql_result = $connection->queryResult($sql);
				$sql_result->setFetchType(IQueryResult::FETCH_ASSOC);

				foreach ($sql_result as $row) {
					$hids[] = $row['hid'];
				}
			}

			$result['labels'] = [];
			$max = 0;
			$sum = 0;

			if (umiCount($hids) > 0) {
				$sql = 'SELECT se.id as `id`, se.name as `tag`, COUNT(*) as `cnt` ' .
					'FROM cms_stat_events se, cms_stat_events_collected sec ' .
					'WHERE se.type = 2 AND sec.event_id = se.id ' .
					$this->getHostSQL('se') . ' ' .
					' AND sec.hit_id IN (' . implode(',', $hids) . ') ' .
					' GROUP BY sec.event_id LIMIT 50';
				$sql_result = $connection->queryResult($sql);
				$sql_result->setFetchType(IQueryResult::FETCH_ASSOC);

				foreach ($sql_result as $row) {
					$result['labels'][] = $row;

					if ($row['cnt'] > $max) {
						$max = $row['cnt'];
					}

					$sum += $row['cnt'];
				}
			}

			$result['max'] = $max;
			$result['sum'] = $sum;
			return $result;
		}
	}

