<?php

	use UmiCms\Service;

	/** Абстрактный класс отчета по статистике */
	abstract class simpleStat {

		/** @const string DATE_FORMAT формат для даты mysql */
		const DATE_FORMAT = 'Y-m-d';

		/** @var int $start стартовая дата для анализа */
		protected $start;

		/** @var int $finish конечная дата для анализа */
		protected $finish;

		/** @var int $host_id Id хоста, для которого производятся выборки */
		protected $host_id;

		/** @var int $user_id Id пользователей, для которого производятся выборки */
		protected $user_id;

		/** @var string $user_login логин пользователей, для которого производятся выборки */
		protected $user_login;

		/** @var string $interval интервал по умолчанию задаётся в наследниках, если нужно */
		protected $interval = '-10 days';

		/** @var array $params разрешенные параметры отчета */
		protected $params = [];

		/** @var int $limit ограничение на количество строк в результате выборки */
		protected $limit = 10;

		/** @var int $offset смешение выборки */
		protected $offset = 0;

		/**
		 * @abstract метод получения отчёта
		 * @return array
		 */
		abstract public function get();

		/**
		 * Конструктор
		 * @param int|null $finish конечная дата анализа
		 * @param string|null $interval анализируемый интервал
		 */
		public function __construct($finish = null, $interval = null) {
			if (empty($finish)) {
				$this->setFinish(time());
			} else {
				$this->setFinish($finish);
			}

			$this->setDomain($_SERVER['HTTP_HOST']);

			if (empty($interval)) {
				$interval = $this->interval;
			} else {
				$this->interval = $interval;
			}

			$this->setInterval($interval);
			$this->setUserIDs();
			$this->limit = 10;
			$this->offset = 0;
		}

		/**
		 * Устанавливает ID пользователей, для которых производится выборка
		 * @param array $user_id_ar Массив id пользователей
		 */
		public function setUserIDs($user_id_ar = []) {
			if (is_array($user_id_ar)) {
				$this->user_login = array_map('intval', $user_id_ar);
			} else {
				$this->user_login = [(int) $user_id_ar];
			}
		}

		/**
		 * Устанавливает id пользователя по его логину
		 * @param string $_sUserName логин пользователя
		 * @throws Exception
		 */
		public function setUser($_sUserName) {
			if ((int) $_sUserName == 0) {
				$this->user_id = [];
				return;
			}

			$this->user_id = [];
			$connection = ConnectionPool::getInstance()->getConnection();
			$sQuery = "SELECT `id` FROM `cms_stat_users` WHERE `login`='" . $connection->escape($_sUserName) . "'";
			$result = $connection->queryResult($sQuery);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				$this->user_id[] = $row[0];
			}

			if (empty($this->user_id)) {
				$this->user_id[] = 0;
			}
		}

		/**
		 * Устанавливает домен, для которого производятся выборки
		 * и возвращает его идентификатор
		 * @param string|int $v_domain идентификатор или имя домена
		 * @return int
		 * @throws Exception
		 */
		public function setDomain($v_domain) {
			$s_domain = (string) $v_domain;
			$i_domain = (int) $v_domain;
			$connection = ConnectionPool::getInstance()->getConnection();

			if ($i_domain === -1 || mb_strtoupper($s_domain) === getLabel('all')) {
				$this->host_id = -1;
			} elseif ($i_domain && ((string) $i_domain === (string) $v_domain)) {
				$o_dom = Service::DomainCollection()
					->getDomain($i_domain);
				if ($o_dom) {
					$s_query = "SELECT   `group_id` AS 'id' FROM `cms_stat_sites` WHERE `name` = '" .
						$connection->escape($o_dom->getHost()) . "'";
					$queryResult = $connection->queryResult($s_query);
					$queryResult->setFetchType(IQueryResult::FETCH_ROW);
					$row = $queryResult->fetch();
					$this->host_id = (int) $row['id'];
				}
			} else {
				$this->host_id = $this->searchHostIdByHostname($s_domain);
			}

			return $this->host_id;
		}

		/**
		 * Устанавливает список доменов, по которым производятся выборки
		 * и возвращает установленные идентификаторы доменов
		 * @param array $arr_domains массив идентификаторов доменов
		 * @return array|int
		 * @throws Exception
		 */
		public function setCmsDomainsArray($arr_domains = []) {
			$arr_tmp = [];
			$o_domains = Service::DomainCollection();
			$connection = ConnectionPool::getInstance()->getConnection();

			foreach ($arr_domains as $i_dom_id) {
				/** @var iDomain $o_dom */
				$o_dom = $o_domains->getDomain($i_dom_id);
				if ($o_dom) {
					$arr_tmp[] = $connection->escape($o_dom->getHost());
				}
			}

			if (umiCount($arr_tmp)) {
				$s_hosts = "'" . implode("','", $arr_tmp) . "'";
				$s_query = "SELECT   `group_id` AS 'id' FROM `cms_stat_sites` WHERE `name` IN (" . $s_hosts . ')';
				$queryResult = $connection->queryResult($s_query);
				$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);

				$arr_tmp = [];

				foreach ($queryResult as $row) {
					$arr_tmp[] = (int) $row['id'];
				}

				$this->host_id = $arr_tmp;
			} else {
				$this->host_id = 0;
			}

			return $this->host_id;
		}

		/**
		 * Генерирует часть запроса отчета, отвечающую
		 * за условие по доменам
		 * @param string $table имя таблицы
		 * @param string $field имя поля
		 * @return string
		 */
		protected function getHostSQL($table = '', $field = 'host_id') {
			if (!is_array($this->host_id) && ($this->host_id < 0)) {
				return '';
			}

			$sSQL = ' AND ' . (($table != '') ? '`' . $table . '`.' : '') . '`' . $field . '` ';

			if (is_array($this->host_id)) {
				$sSQL .= " IN ('" . implode("','", $this->host_id) . "') ";
			} else {
				$sSQL .= ' = ' . $this->host_id;
			}
			return $sSQL;
		}

		/**
		 * Устанавливает конечную дату анализа
		 * @param int $finish unix timestamp для конечной даты
		 * @throws wrongParamException
		 */
		public function setFinish($finish) {
			if (!is_int($finish)) {
				throw new wrongParamException(getLabel('error-property-finish-value'));
			}

			$this->finish = $finish + 86400;
		}

		/**
		 * Устанавливает начальную дату анализа
		 * @param int $start unix timestamp для начальной даты
		 * @throws wrongParamException
		 */
		public function setStart($start) {
			if (!is_int($start)) {
				throw new wrongParamException(getLabel('error-property-start-value'));
			}

			$this->start = $start;
		}

		/**
		 * Устанавливает анализируемый интервал времени
		 * @param string $interval интервал. значение должно быть корректным для передачи первым аргументом в функцию strtotime
		 * @throws wrongParamException
		 */
		public function setInterval($interval) {
			$start = strtotime($interval, $this->finish);

			if (!is_int($start)) {
				throw new wrongParamException(getLabel('error-property-interval-value'));
			}

			$this->start = $start;
		}

		/**
		 * Устанавливает параметры отчеты
		 * @param array $array
		 */
		public function setParams($array = []) {
			foreach ($this->params as $key => $val) {
				if (isset($array[$key])) {
					$this->params[$key] = $array[$key];
				}
			}
		}

		/**
		 * Устанавливает ограничение на количество
		 * результатов выборки
		 * @param int $limit ограничени на количество
		 */
		public function setLimit($limit) {
			if ((int) $limit > 0) {
				$this->limit = $limit;
			}
		}

		/**
		 * Устанавливает смещение выборки
		 * @param int $offset смещение выборки
		 */
		public function setOffset($offset) {
			if ((int) $offset > 0) {
				$this->offset = $offset;
			}
		}

		/**
		 * Возвращает идентификатор домена по его имени
		 * @param string $hostname имя домена
		 * @return int
		 * @throws Exception
		 */
		protected function searchHostIdByHostname($hostname) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$name = $connection->escape($hostname);

			$qry = "SELECT `rel` FROM `cms3_domain_mirrows` WHERE `host` = '" . $name . "'";
			$queryResult = $connection->queryResult($qry);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
			$row = $queryResult->fetch();

			if (isset($row['rel']) && ($row['rel'] > 0)) {
				$qry = "SELECT `host` FROM `cms3_domains` WHERE `id`='" . $row['rel'] . "'";
				$queryResult = $connection->queryResult($qry);
				$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
				$row = $queryResult->fetch();

				if (isset($row['host']) && ($row['host'] != '')) {
					$name = $row['host'];
				}
			} else {
				$qry = "SELECT `id` FROM `cms3_domains` WHERE `host`='" . $name . "'";
				$queryResult = $connection->queryResult($qry);
				$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
				$row = $queryResult->fetch();

				if (!isset($row['id']) || ($row['id'] == 0)) {
					$qry = "SELECT `host` FROM `cms3_domains` WHERE `is_default`='1'";
					$queryResult = $connection->queryResult($qry);
					$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
					$row = $queryResult->fetch();

					if (isset($row['host']) && ($row['host'] != '')) {
						$name = $row['host'];
					}
				}
			}

			$qry = "SELECT `group_id` FROM `cms_stat_sites` WHERE `name` = '" . $name . "'";
			$queryResult = $connection->queryResult($qry);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
			$row = $queryResult->fetch();

			if (isset($row['group_id'])) {
				return $row['group_id'];
			}

			$qry = "INSERT INTO `cms_stat_sites_groups` (`name`) VALUES ('" . $name . "')";
			$connection->query($qry);
			$id = $connection->insertId();
			$qry = "INSERT INTO `cms_stat_sites` (`name`, `group_id`) VALUES ('" . $name . "', " . $id . ')';
			$connection->query($qry);

			return $id;
		}

		/**
		 * Форматирует timestamp в mysql формат времени
		 * и возвращает результат
		 * @param integer $date искомый timestamp
		 * @return bool|string
		 */
		protected function formatDate($date) {
			return date(self::DATE_FORMAT, $date);
		}

		/**
		 * Возвращает часть запроса, ответственную
		 * за условие выборки по вхождению во временной интервал
		 * @return string
		 */
		protected function getQueryInterval() {
			return "'" . $this->formatDate($this->start) . "' AND '" . $this->formatDate($this->finish) . "'";
		}

		/**
		 * Возвращает часть запроса, ответственную
		 * за join таблицы cms_stat_paths
		 * @param string $_sPathField имя поля
		 * @param string $_sCompareTable имя таблицы + имя столбца
		 * @return string
		 */
		protected function getUserFilterTable($_sPathField, $_sCompareTable) {
			if (!is_array($this->user_id) || empty($this->user_id)) {
				return '';
			}
			return "\n INNER JOIN `cms_stat_paths` ON `cms_stat_paths`.`" . $_sPathField . '`=' . $_sCompareTable . " \n";
		}

		/**
		 * Возвращает часть запроса, ответственную за условие выборки
		 * по вхождению в список пользователей
		 * @param string $_sTable имя таблицы, содержащей поле user_id
		 * @return string
		 */
		protected function getUserFilterWhere($_sTable = 'cms_stat_paths') {
			if (!is_array($this->user_id) || empty($this->user_id)) {
				return '';
			}
			return "\n AND `" . $_sTable . '`.`user_id` IN (' . implode(', ', $this->user_id) . ") \n";
		}

		/**
		 * Делает запрос к базе данных и возвращает все данные
		 * @param string $query искомый запрос
		 * @param bool $bNeedFoundRows вернуть количество искомых строк, нужно использовать SQL_CALC_FOUND_ROWS в запросе
		 * @return array массив с результатами
		 */
		protected function simpleQuery($query, $bNeedFoundRows = false) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$vana = null;

			if ($bNeedFoundRows) {
				$vana = ini_set('mysql.trace_mode', 'Off');
			}

			$queryResult = $connection->queryResult($query);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);

			$result = [];

			foreach ($queryResult as $row) {
				$result[] = $row;
			}

			if ($bNeedFoundRows) {
				$foundRowsResult = $connection->queryResult("SELECT FOUND_ROWS() as 'cnt'");
				$foundRowsResult->setFetchType(IQueryResult::FETCH_ASSOC);
				$row = $foundRowsResult->fetch();
				$iFoundRows = $row['cnt'];
				$result = [
					'result' => $result,
					'FOUND_ROWS' => $iFoundRows
				];
				ini_set('mysql.trace_mode', $vana);
			}

			return $result;
		}
	}

