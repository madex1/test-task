<?php

	/** Класс получения отчета о теге */
	class tag extends simpleStat {

		/** @inheritdoc */
		protected $params = [
			'tag_id' => 0
		];

		/** @inheritdoc */
		public function get() {
			$aResult = [];
			$connection = ConnectionPool::getInstance()->getConnection();
			$sSQL = "SELECT e.name, p.uri, COUNT(*) as 'count'
						FROM `cms_stat_events` e, `cms_stat_events_collected` ec, `cms_stat_hits` h, `cms_stat_pages` p, `cms_stat_paths` pth
						WHERE e.id = " . $this->params['tag_id'] . '
						  AND ec.event_id = e.id
						  AND ec.hit_id = h.id
						  AND h.page_id = p.id
						  AND h.path_id = pth.id
						  AND pth.date BETWEEN ' . $this->getQueryInterval() . '
						  ' . $this->getHostSQL('e') . $this->getUserFilterWhere('pth') . '
						GROUP BY p.id
						ORDER BY count DESC';
			$queryResult = $connection->queryResult($sSQL);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);

			$iTotal = 0;

			foreach ($queryResult as $row) {
				$iTotal += $row['count'];
				$aResult[] = $row;
			}

			foreach ($aResult as &$row) {
				$row['rel'] = (float) $row['count'] / (float) $iTotal;
			}
			return $aResult;
		}
	}

