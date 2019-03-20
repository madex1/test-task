<?php

	/** Класс получения отчета о глубине просмотра за период */
	class visitDeep extends simpleStat {

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

		/** Возвращает сводную статистику */
		private function getDetail() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->query('DROP TEMPORARY TABLE IF EXISTS `tmp_visit_deep`');
			$connection->query('CREATE TEMPORARY TABLE `tmp_visit_deep` (`lvl` INT) ENGINE = MEMORY');
			$connection->query('INSERT INTO `tmp_visit_deep` SELECT MAX(`number_in_path`) AS `level` FROM `cms_stat_hits` `h`
							 INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
							  WHERE `h`.`date` BETWEEN ' . $this->getQueryInterval() . ' ' . $this->getHostSQL('p') .
				$this->getUserFilterWhere('p') . '
							   GROUP BY `h`.`path_id`');

			$sQr = <<<END
			SELECT
				COUNT(*) AS `cnt`,
				IF(`lvl` > 10, IF(`lvl` > 20, IF(`lvl` > 30, IF(`lvl` > 40, IF(`lvl` > 50, 51, 41), 31), 21), 11), `lvl`) AS `level`
			FROM
				`tmp_visit_deep`
			GROUP BY
				`level`
			ORDER BY `cnt` DESC
END;
			return $this->simpleQuery($sQr);
		}

		/** Возвращает сгуппированную статистику */
		private function getDynamic() {
			return $this->simpleQuery('SELECT AVG(`level`) AS `level_avg`, `' . $this->groupby . '` AS `period`, UNIX_TIMESTAMP(`date`) AS `ts` FROM

										(SELECT `h`.`date`, `h`.`week`, `h`.`month`, `h`.`year`, MAX(`number_in_path`) AS `level` FROM `cms_stat_hits` `h`
										 INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
										  WHERE `h`.`date` BETWEEN ' . $this->getQueryInterval() . ' ' . $this->getHostSQL('p') .
				$this->getUserFilterWhere('p') . '
										   GROUP BY `p`.`id`) `tmp`

										   GROUP BY `tmp`.`' . $this->groupby . '`, `tmp`.`year`
											ORDER BY `date` ASC');
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

