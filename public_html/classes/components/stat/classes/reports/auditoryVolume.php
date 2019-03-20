<?php

	/** Класс получения информации об объёме аудитории за период */
	class auditoryVolume extends simpleStat {

		/** @var string $groupby Имя поля, по которому происходит группировка данных */
		private $groupby;

		/** @inheritdoc */
		protected $interval = '-1 year';

		/** @inheritdoc */
		public function get() {
			$this->groupby = $this->calcGroupby($this->start, $this->finish);
			$connection = ConnectionPool::getInstance()->getConnection();
			$qry = 'SELECT COUNT(DISTINCT(`p`.`user_id`)) AS `cnt`, UNIX_TIMESTAMP(`h`.`date`) AS `ts`, `h`.`date`, `h`.`' .
				$this->groupby . '` AS `period` FROM `cms_stat_hits` `h`
						 INNER JOIN `cms_stat_pages` `pg` ON `pg`.`id` = `h`.`page_id`
						  INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
						   WHERE `h`.`date` BETWEEN ' . $this->getQueryInterval() . ' ' . $this->getHostSQL('p') .
				$this->getUserFilterWhere('p') . '
							GROUP BY `h`.`' . $this->groupby . '`
							 ORDER BY `h`.`date` ASC';
			$queryResult = $connection->queryResult($qry);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);

			$result = [];

			foreach ($queryResult as $row) {
				$result[] = ['ts' => $row['ts'], 'cnt' => $row['cnt'], 'period' => $row['period']];
			}

			return ['detail' => $result, 'groupby' => $this->groupby];
		}

		/**
		 * Возвращает имя поля по которому будет производиться группировка,
		 * в зависимости от величины интервала
		 * @param integer $start дата начала интервара
		 * @param integer $finish дата конца интервара
		 * @return string
		 */
		private function calcGroupby($start, $finish) {
			$daysInterval = ceil(($finish - $start) / (3600 * 24));

			if ($daysInterval > 30) {
				return 'week';
			}

			if ($daysInterval > 7) {
				return 'day';
			}
			return 'hour';
		}
	}

