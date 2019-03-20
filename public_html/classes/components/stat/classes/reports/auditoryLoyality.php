<?php

	/** Класс получения информации о лояльности аудитории за период */
	class auditoryLoyality extends simpleStat {

		/** @var string $groupby Имя поля, по которому происходит группировка данных */
		private $groupby;

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
		private function getDetail() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->query('DROP TEMPORARY TABLE IF EXISTS `tmp_users_loyality`');
			$connection->query('CREATE TEMPORARY TABLE `tmp_users_loyality` (`count` INT) ENGINE = MEMORY');
			$connection->query('INSERT INTO `tmp_users_loyality` SELECT COUNT(*) AS `cnt` FROM `cms_stat_paths` `p`
						  INNER JOIN `cms_stat_users` `u` ON `u`.`id` = `p`.`user_id` AND `u`.`first_visit` < `p`.`date`
						   WHERE `p`.`date` BETWEEN ' . $this->getQueryInterval() . ' ' . $this->getHostSQL('p') .
				$this->getUserFilterWhere('p') . '
							GROUP BY `p`.`user_id`');
			return $this->simpleQuery('SELECT COUNT(*) AS `cnt`, IF(`count` > 10, IF(`count` > 20, IF(`count` > 30, IF(`count` > 40, IF(`count` > 50, 51, 41), 31), 21), 11), `count`) AS `visits_count` FROM `tmp_users_loyality`
								 GROUP BY `visits_count`');
		}

		/**
		 * Возвращает сгруппированный отчет
		 * @return array
		 * @throws Exception
		 */
		private function getDynamic() {
			return $this->simpleQuery('SELECT COUNT(*) / COUNT(DISTINCT(`p`.`user_id`)) AS `avg`, `h`.`' . $this->groupby . '` AS `period`, UNIX_TIMESTAMP(`h`.`date`) AS `ts` FROM `cms_stat_hits` `h`
										INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
										 WHERE `h`.`date` BETWEEN ' . $this->getQueryInterval() .
				' AND `h`.`number_in_path` = 1 ' . $this->getHostSQL('p') . $this->getUserFilterWhere('p') . '
										  GROUP BY `h`.`' . $this->groupby . '`, `h`.`year` ORDER BY `ts` ASC');
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

			if ($daysInterval > 180) {
				return 'month';
			}
			return 'week';
		}
	}

