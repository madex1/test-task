<?php

	/**
	 * Класс разбора OpenStat метки.
	 *
	 * Формат метки выглядит следующим образом:
	 * http://www.site.ru/?_openstat=service-name;campaign-id;ad-id;source-id
	 *
	 * Где:
	 *
	 *    * www.site.ru — адрес сайта или раздела сайта рекламодателя;
	 * _openstat — идентификатор универсальной метки
	 * service-name  — название рекламного ресурса (например: direct.yandex.ru);
	 * campaign-id — идентификатор рекламной кампании (например: a8765b8);
	 * ad-id — идентификатор рекламного объявления (например: 991b8);
	 * source-id — идентификатор места, на котором было показано соответствующее рекламное объявление (например: mail.ru).
	 *
	 */
	class openstat {

		/** @var array $data параметры OpenStat */
		private $data = [];

		/**
		 * Конструктор
		 * @param string $str строка для разбора
		 * @throws Exception
		 */
		public function __construct($str) {
			if (!mb_strpos($str, ';')) {
				$str = str_replace(['*', '-'], ['+', '/'], $str);
				$str = base64_decode($str);
			}

			$openstat = explode(';', $str, 4);

			if (empty($openstat[0]) || empty($openstat[2])) {
				throw new Exception(getLabel('error-service-name-and-advertisement-required-parameters'));
			}

			if (umiCount($openstat) == 4) {
				$this->parse($openstat);
			} else {
				throw new Exception(getLabel('error-count-of-openstat-parameters'));
			}
		}

		/**
		 * Возвращает идентификатор рекламного сервиса
		 * @return mixed
		 */
		public function getServiceId() {
			return $this->data['service_id'];
		}

		/**
		 * Возвращает идентификатор рекламной кампании
		 * @return mixed
		 */
		public function getCampaignId() {
			return $this->data['campaign_id'];
		}

		/**
		 * Возвращает идентификатор рекламного объявления
		 * @return mixed
		 */
		public function getAdId() {
			return $this->data['ad_id'];
		}

		/**
		 * Возвращает идентификатор рекламного места
		 * @return mixed
		 */
		public function getSourceId() {
			return $this->data['source_id'];
		}

		/**
		 * Запукает установку параметров OpenStat
		 * @param array $openstat параметры OpenStat
		 */
		private function parse($openstat) {
			list($openstat_service, $openstat_campaign, $openstat_ad, $openstat_source) = $openstat;

			$this->appendData($openstat_service, 'service');
			$this->appendData($openstat_campaign, 'campaign');
			$this->appendData($openstat_ad, 'ad');
			$this->appendData($openstat_source, 'source');
		}

		/**
		 * Устанавливает идентификатор OpenStat параметра.
		 * Есди такого параметра еще не было - сохраняет в бд.
		 * @param string $data значение OpenStat параметра
		 * @param string $type тип OpenStat параметра
		 * @throws Exception
		 */
		private function appendData($data, $type) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$type = $connection->escape($type);

			$qry = 'SELECT `id` FROM `cms_stat_sources_openstat_' . $type . "` WHERE `name` = '" . $connection->escape($data) . "'";
			$result = $connection->queryResult($qry);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);
			$row = $result->fetch();

			if (isset($row['id'])) {
				$this->data[$type . '_id'] = $row['id'];
			} else {
				$qry = 'INSERT INTO `cms_stat_sources_openstat_' . $type . "` (`name`) VALUES ('" . $connection->escape($data) . "')";
				$connection->query($qry);
				$this->data[$type . '_id'] = $connection->insertId();
			}
		}
	}

