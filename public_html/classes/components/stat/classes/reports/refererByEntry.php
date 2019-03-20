<?php

	/** Класс получения отчета о связях точек входа и рефереров */
	class refererByEntry extends simpleStat {

		/** @inheritdoc */
		protected $params = [
			'page_id' => 0
		];

		/** @inheritdoc */
		public function get() {
			$result = [];
			$connection = ConnectionPool::getInstance()->getConnection();
			$sQuery = 'SELECT COUNT( * ) AS `count` , `domain`.`name` , `site`.`uri`
						FROM `cms_stat_sources_sites` AS `site` ,
							 `cms_stat_sources_sites_domains` AS `domain` ,
							 `cms_stat_hits` AS `hit` ,
							 `cms_stat_paths` AS `path` ,
							 `cms_stat_sources` AS `source`
						WHERE `domain`.`id` = `site`.`domain`
						  AND `source`.`concrete_src_id` = `site`.`id`
						  AND `source`.`src_type` =1
						  AND `path`.`source_id` = `source`.`id`
						  AND `hit`.`path_id` = `path`.`id`
						  AND `hit`.`number_in_path` =1
						  AND `hit`.`page_id`=' . $this->params['page_id'] . '
						  AND `hit`.`date` BETWEEN ' . $this->getQueryInterval() .
				$this->getHostSQL('page') . $this->getUserFilterWhere('path') .
				'GROUP BY `site`.`id`';
			$queryResult = $connection->queryResult($sQuery);
			$queryResult->setFetchType(IQueryResult::FETCH_ARRAY);

			foreach ($queryResult as $row) {
				$result[] = $row;
			}

			return $result;
		}
	}

