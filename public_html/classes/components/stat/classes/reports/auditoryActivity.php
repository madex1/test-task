<?php

	/** Класс получения отчета о активности аудитории за период */
	class auditoryActivity extends simpleStat {

		/** @var string $groupby Имя поля, по которому происходит группировка данных */
		private $groupby;

		/** @var string $groupby_key идентификатор типа группировки по дате */
		private $groupby_key;

		/** @inheritdoc */
		protected $interval = '-30 days';

		/** @inheritdoc */
		public function get() {
			$this->groupby = $this->calcGroupby($this->start, $this->finish);
			return [
				'detail' => $this->getDetail(),
				'dynamic' => $this->getDynamic(),
				'groupby' => $this->groupby
			];
		}

		/**
		 * Возвращает полный отчет
		 * @return array
		 * @throws Exception
		 */
		public function getDetail() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->query('DROP TEMPORARY TABLE IF EXISTS `tmp_activity`');
			$connection->query('CREATE TEMPORARY TABLE `tmp_activity` (`days` INT) ENGINE = MEMORY');
			$connection->query('INSERT INTO `tmp_activity` SELECT FLOOR( ( UNIX_TIMESTAMP(MAX(`date`)) - UNIX_TIMESTAMP(MIN(`date`)) ) / (COUNT(*) - 1) / 3600 / 24 ) AS `days` FROM `cms_stat_paths`
						 WHERE `date` BETWEEN ' . $this->getQueryInterval() . ' ' . $this->getHostSQL() .
				$this->getUserFilterWhere() . '
						  GROUP BY `user_id`');

			return $this->simpleQuery('SELECT COUNT(*) AS `cnt`, IF(`days` > 10, IF(`days` > 20, IF(`days` > 30, IF(`days` > 40, IF(`days` > 50, 51, 41), 31), 21), 11), `days`) AS `days` FROM `tmp_activity` GROUP BY `days`');
		}

		/**
		 * Возвращает сгруппированный отчет
		 * @return array
		 * @throws Exception
		 */
		public function getDynamic() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->query('DROP TEMPORARY TABLE IF EXISTS `tmp_activity`');
			$connection->query('CREATE TEMPORARY TABLE `tmp_activity` (`days` INT, `' . $this->groupby .
				'` INT, `year` INT, `date` DATETIME) ENGINE = MEMORY');
			$connection->query("INSERT INTO `tmp_activity` SELECT FLOOR( ( UNIX_TIMESTAMP(MAX(`date`)) - UNIX_TIMESTAMP(MIN(`date`)) ) / (COUNT(*) - 1) / 3600 / 24 ) AS `days`, DATE_FORMAT(`date`, '%" .
				$this->groupby_key . "') AS `" . $this->groupby . "`, DATE_FORMAT(`date`, '%Y') AS `year`, `date` FROM `cms_stat_paths`
						 WHERE `date` BETWEEN '" . $this->formatDate($this->start) . "' AND '" .
				$this->formatDate($this->finish) . "' " . $this->getHostSQL() . $this->getUserFilterWhere() . '
						  GROUP BY `user_id`');
			return $this->simpleQuery('SELECT AVG(`days`) AS `avg`, `' . $this->groupby . '` AS `period`, UNIX_TIMESTAMP(`date`) AS `ts` FROM `tmp_activity`
										 GROUP BY `' . $this->groupby . '`, `year` ORDER BY `date`');
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
				$this->groupby_key = 'u';
				return 'week';
			}

			if ($daysInterval > 7) {
				$this->groupby_key = 'j';
				return 'day';
			}

			$this->groupby_key = 'k';
			return 'hour';
		}
	}

