<?php

	/** Класс получения отчета о количестве просмотров страниц за период */
	class pagesHits extends simpleStat {

		/** @inheritdoc */
		public function get() {
			return $this->getAll();
		}

		/**
		 * Получает данные для отчета
		 * @return array
		 */
		private function getAll() {
			$result = $this->simpleQuery('SELECT   COUNT(*) as `total` FROM `cms_stat_hits` `h` FORCE INDEX(`date`)
										 INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
										 WHERE `h`.`date` BETWEEN ' . $this->getQueryInterval() . ' ' . $this->getHostSQL('p') .
				$this->getUserFilterWhere('p'));

			$i_total = (int) $result[0]['total'];

			$arrQr = $this->simpleQuery('SELECT   SQL_CALC_FOUND_ROWS COUNT(*) AS `abs`, COUNT(*) / ' . $i_total . ' * 100 AS `rel`, `h`.`page_id`, `p`.`uri` FROM `cms_stat_hits` `h`
										INNER JOIN `cms_stat_pages` `p` ON `p`.`id` = `h`.`page_id`
										' . $this->getUserFilterTable('id', 'h.path_id') . '
										 WHERE `h`.`date` BETWEEN ' . $this->getQueryInterval() . ' ' . $this->getHostSQL('p') .
				$this->getUserFilterWhere() . '
										  GROUP BY `page_id`
										   ORDER BY `abs` DESC
											LIMIT ' . $this->offset . ', ' . $this->limit, true);

			return [
				'all' => $arrQr['result'],
				'summ' => $i_total,
				'total' => $arrQr['FOUND_ROWS']
			];
		}
	}

