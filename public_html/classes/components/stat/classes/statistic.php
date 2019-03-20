<?php
	/** @const int STAT_SITES идентификатор источника статистики типа "ссылающий сайт" */

	use UmiCms\Service;

	define('STAT_SITES', 1);
	/** @const int STAT_SEARCH идентификатор источника статистики типа "переход с поисковой системы" */
	define('STAT_SEARCH', 2);
	/** @const int STAT_PR идентификатор источника статистики типа "рекламная кампания" */
	define('STAT_PR', 3);
	/** @const int STAT_TICKET идентификатор источника статистики типа "входной билет" */
	define('STAT_TICKET', 4);
	/** @const int STAT_RETURN_INTERVAL время жизни сессии пользователя статистики в минутах */
	define('STAT_RETURN_INTERVAL', 15);

	/** Класс сбора статистики */
	class statistic {

		/** @var int $time текущее время */
		private $time;

		/** @var bool $noCount не собирать статистику */
		private $noCount;

		/** @var string $referer HTTP_REFERER */
		private $referer;

		/** @var string $uri REQUEST_URI */
		private $uri;

		/** @var string $serverName HTTP_HOST, если его нет - SERVER_NAME */
		private $serverName;

		/** @var string $remoteAddr REMOTE_ADDR */
		private $remoteAddr;

		/** @var string $_agent HTTP_USER_AGENT */
		private $_agent;

		/** @var array $_robots список юзер агентов различных ботов */
		private $_robots = [
			'Googlebot',
			'msnbot',
			'Slurp',
			'Yahoo',
			'Yandex',
			'StackRambler',
			'aport',
			'appie',
			'Arachnoidea',
			'ArchitextSpider',
			'Ask Jeeves',
			'B-l-i-t-z-Bot',
			'Baiduspider',
			'BecomeBot',
			'cfetch',
			'ConveraCrawler',
			'ExtractorPro',
			'FAST-WebCrawler',
			'FDSE robot',
			'fido',
			'findlinks',
			'Francis',
			'geckobot',
			'Gigabot',
			'Girafabot',
			'grub-client',
			'Gulliver',
			'HTTrack',
			'ia_archiver',
			'iCCrawler',
			'InfoSeek',
			'kinjabot',
			'KIT-Fireball',
			'larbin',
			'LEIA',
			'lmspider',
			'lwp-trivial',
			'Lycos_Spider',
			'Mediapartners-Google',
			'MuscatFerret',
			'NaverBot',
			'OmniExplorer_Bot',
			'polybot',
			'Pompos',
			'RufusBot',
			'Scooter',
			'Seekbot',
			'sproose',
			'Teoma',
			'TheSuBot',
			'TurnitinBot',
			'Ultraseek',
			'ViolaBot',
			'voyager',
			'webbandit',
			'www.almaden.ibm.com/cs/crawler',
			'yacy',
			'ZyBorg',
		];

		/**
		 * Конструктор
		 * @param null|int $time текущее время
		 */
		public function __construct($time = null) {
			if (empty($time)) {
				$this->time = time();
			} else {
				$this->time = $time;
			}

			if (!Service::CookieJar()->isExists('stat_id')) {
				$this->setStatIdCookie();
			}
		}

		/**
		 * Запускает сбор и сохранение статистики
		 * @return bool|null
		 * @throws Exception
		 */
		public function run() {
			if ($this->noCount) {
				return null;
			}

			// проверяем, является ли посетитель поисковым ботом
			if (!$this->issetSessionStatByKey('isSearchBot')) {
				$this->setSessionStatByKey('isSearchBot', $this->isSearchBot());
			}

			// если поисковый бот - заканчиваем работу
			if ($this->getSessionStatByKey('isSearchBot')) {
				return false;
			}

			$connection = ConnectionPool::getInstance()->getConnection();

			if ($this->issetSessionStatByKey('doLogin')) {
				$login = $this->getLogin();
				// если уже были залогинены
				if ($this->issetSessionStatByKey('loginId')) {
					// удаляем всю сессионную информацию и считаем заново
					$this->delSessionStatByKey();
				} else {
					// если не были залогинены
					$this->setUpHostId();
					$siteId = $this->getSessionStatByKey('site_id');
					$qry =
						"SELECT `id` FROM `cms_stat_users` WHERE `login` = '" . $connection->escape($login) . "' AND `host_id` = " .
						$siteId;
					$queryResult = $connection->queryResult($qry);
					$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
					$row = $queryResult->fetch();
					// если такой пользователь уже есть
					if (isset($row['id'])) {
						$user_id = $row['id'];
						// если такого пользователя не было - добавляем
					} else {
						$user_id = $this->addUser();
					}

					// если пользователь уже походил по сайту - заменяем ид пользователя на текущего
					if ($this->issetSessionStatByKey('path_id')) {
						$pathId = (int) $this->getSessionStatByKey('path_id');
						$qry = 'UPDATE `cms_stat_paths` SET `user_id` = ' . $user_id . ' WHERE `id` = ' . $pathId;
						$connection->query($qry);
					}

					$this->setSessionStatByKey('loginId', $user_id);
				}
			}

			// если пользователь только зашёл на сайт
			// устанавливаем необходимые для работы переменные и определяем, откуда пришёл пользователь
			if (!$this->issetSessionStatByKey('id')) {
				$cookieJar = Service::CookieJar();

				if ($cookieJar->isExists('stat_id')) {
					$login = $connection->escape($this->getLogin());
					$sessid = $connection->escape($cookieJar->get('stat_id'));
					$queryResult = $connection->queryResult("SELECT `id` FROM `cms_stat_users` WHERE `session_id` = '" . $sessid .
						"' AND `login`='{$login}'");
					$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
					$row = $queryResult->fetch();

					if (isset($row['id'])) {
						$this->setSessionStatByKey('id', $sessid);
						$this->setSessionStatByKey('user_id', $row['id']);
						$rowId = (int) $row['id'];
						// проверяем когда пользователь в последний раз был на сайте
						// если посещение было в течение 15 минут (STAT_RETURN_INTERVAL)
						// тогда сессия та же
						$qry = 'SELECT UNIX_TIMESTAMP(MAX(`h`.`date`)) AS `ts`, `p`.`id` FROM `cms_stat_hits` `h`
								 INNER JOIN `cms_stat_paths` `p` ON `p`.`id` = `h`.`path_id`
								  WHERE `p`.`user_id` = ' . $rowId . '
								   GROUP BY `p`.`user_id`';
						$queryResult = $connection->queryResult($qry);
						$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
						$row = $queryResult->fetch();

						if (isset($row['ts']) && ($this->time - $row['ts']) / 60 <= STAT_RETURN_INTERVAL) {
							// восстанавливаем прежний path_id
							$this->setSessionStatByKey('path_id', $row['id']);
							// и число посещённых за сессию страниц
							$rowId = (int) $row['id'];
							$queryResult =
								$connection->queryResult('SELECT COUNT(*) AS `cnt` FROM `cms_stat_hits` WHERE `path_id` = ' . $rowId);
							$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
							$row = $queryResult->fetch();
							$this->setSessionStatByKey('number_in_path', $row['cnt']);
						}
					} else {
						// устанавливаем куку на 10 лет
						$this->setStatIdCookie();
						// пишем этот же id в сессию
						$this->setSessionStatByKey('id', $this->getSessionId());
						$this->setSessionStatByKey('user_id', $this->addUser());
					}
				} else {
					// устанавливаем куку на 10 лет
					$this->setStatIdCookie();
					// пишем этот же id в сессию
					$this->setSessionStatByKey('id', $this->getSessionId());
					$this->setSessionStatByKey('user_id', $this->addUser());
				}

				$this->setUpHostId();
				$source_id = 0;

				// получаем адрес реферера
				$referer = $this->getReferer();
				$url_array = parse_url($referer);

				if (!isset($url_array['path'])) {
					$url_array['path'] = '';
				}

				if (!isset($url_array['host'])) {
					$url_array['host'] = '';
				}

				if (startsWith($url_array['host'], 'www.')) {
					$domain = mb_substr($url_array['host'], 4);
				} else {
					$domain = $url_array['host'];
				}

				// является ли источник - рекламной кампанией
				$qry = "SELECT `pr_id` FROM `cms_stat_sources_pr_sites`
						WHERE '" . $connection->escape($domain) . "' LIKE `url`";
				$queryResult = $connection->queryResult($qry);
				$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);

				$row = $queryResult->fetch();

				if ($row) {
					$source_id = $this->getSourceId($row['pr_id'], STAT_PR);
				}

				// случай когда источник является "входным билетом"
				if (!$source_id && $this->issetSessionStatByKey('ticket_id')) {
					$source_id = $this->getSessionStatByKey('ticket_id');
				}

				// результаты поиска
				if (!$source_id) {
					$qry = "SELECT `id`, `varname`, `url_mask` FROM `cms_stat_sources_search_engines`
							   WHERE '" . $connection->escape($domain) . "' LIKE `url_mask`";
					$queryResult = $connection->queryResult($qry);
					$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
					$row = $queryResult->fetch();

					if (isset($row['url_mask'])) {
						if ($row['url_mask'] == 'yandex.ru') {
							if ($row['url_mask'] != $domain) {
								unset($row['id']);
							}
						}
					}

					// если такая поисковая система существует
					if (isset($row['id'])) {
						$engine_id = $row['id'];
						// если в адресе содержится REQUEST_URI
						if (isset($url_array['query'])) {
							$qry = $url_array['query'];
							parse_str($qry, $arr);
							// если в REQUEST_URI есть переменная, в которой находится искомый текст
							if (isset($arr[$row['varname']])) {
								$text = $arr[$row['varname']];
								$text = $this->convertCharset($text);
								$engine_id = (int) $engine_id;
								// ищем нужную комбинацию поисковой системы и искомого слова
								$qry = "SELECT `s`.`id` FROM `cms_stat_sources_search_queries` `q`
										 INNER JOIN `cms_stat_sources_search` `s` ON `s`.`text_id` = `q`.`id`
										  WHERE `q`.`text` = '" . $connection->escape($text) . "' AND `engine_id` = " .
									$engine_id;
								$queryResult = $connection->queryResult($qry);
								$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
								$row = $queryResult->fetch();
								// если таковые есть
								if (isset($row['id'])) {
									// то это и есть источник
									$source_id = $row['id'];
								} else {
									// иначе - смотрим, есть ли искомая фраза в БД
									$qry = "SELECT `id` FROM `cms_stat_sources_search_queries` WHERE `text` = '" .
										$connection->escape($text) . "'";
									$queryResult = $connection->queryResult($qry);
									$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
									$row = $queryResult->fetch();
									if (isset($row['id'])) {
										$rowId = (int) $row['id'];
										$engine_id = (int) $engine_id;
										// если есть - то добавляем комбинацию поисковая система - фраза в таблицу
										$qry =
											'INSERT INTO `cms_stat_sources_search` (`engine_id`, `text_id`) VALUES (' . $engine_id .
											', ' . $rowId . ')';
										$connection->query($qry);
										$source_id = $connection->insertId();
									} else {
										// если нет - то добавляем искомую фразу
										$qry = "INSERT INTO `cms_stat_sources_search_queries` (`text`) VALUES ('" .
											$connection->escape($text) . "')";
										$connection->query($qry);
										$word_id = (int) $connection->insertId();
										$engine_id = (int) $engine_id;
										// и сопоставляем её с конкретной поисковой системой
										$qry =
											'INSERT INTO `cms_stat_sources_search` (`engine_id`, `text_id`) VALUES (' . $engine_id .
											', ' . $word_id . ')';
										$connection->query($qry);
										$source_id = $connection->insertId();
									}
								}

								$source_id = $this->getSourceId($source_id, STAT_SEARCH);
							}
						}
					}
				}

				// ссылающиеся сайты
				if (!$source_id) {
					if ($domain) {
						$qry = "SELECT `group_id` FROM `cms_stat_sites` WHERE `name` = '" . $connection->escape($domain) . "'";
						$queryResult = $connection->queryResult($qry);
						$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
						$row = $queryResult->fetch();

						if (!isset($row['group_id']) || $row['group_id'] != $this->getSessionStatByKey('site_id')) {

							$uri_ref = $url_array['path'] . (isset($url_array['query']) ? '?' . $url_array['query'] : '');
							$qry = "SELECT `s`.`id` FROM `cms_stat_sources_sites_domains` `d`
								 INNER JOIN `cms_stat_sources_sites` `s` ON `s`.`domain` = `d`.`id`
								  WHERE `d`.`name` = '" . $connection->escape($domain) . "' AND `s`.`uri`='" .
								$connection->escape($uri_ref) . "'";
							$queryResult = $connection->queryResult($qry);
							$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
							$row = $queryResult->fetch();
							// если ссылающаяся страница сайта найдена
							if (isset($row['id'])) {
								$source_id = $row['id'];
							} else {
								// если не найдена, то ищем - есть ли вообще такой ссылающийся домен
								$qry = "SELECT * FROM `cms_stat_sources_sites_domains` `d`
									  WHERE `d`.`name` = '" . $connection->escape($domain) . "'";
								$queryResult = $connection->queryResult($qry);
								$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
								$row = $queryResult->fetch();
								// если есть домен
								$uri_ref = $url_array['path'] . (isset($url_array['query']) ? '?' . $url_array['query'] : '');

								if (isset($row['id'])) {
									$rowId = (int) $row['id'];
									// то добавляем сылающуюся с этого домена страницу
									$qry = "INSERT INTO `cms_stat_sources_sites` (`uri`, `domain`) VALUES ('" .
										$connection->escape($uri_ref) . "', " . $rowId . ')';
									$connection->query($qry);
									$source_id = $connection->insertId();
								} else {
									// если домена нет - то добавляем домен
									$qry = "INSERT INTO `cms_stat_sources_sites_domains` (`name`) VALUES ('" .
										$connection->escape($domain) . "')";
									$connection->query($qry);
									$rel_site_id = (int) $connection->insertId();

									$qry = "INSERT INTO `cms_stat_sources_sites` (`uri`, `domain`) VALUES ('" .
										$connection->escape($uri_ref) . "', " . $rel_site_id . ')';
									$connection->query($qry);
									$source_id = $connection->insertId();
								}
							}

							$source_id = $this->getSourceId($source_id, STAT_SITES);
						}
					}
				}

				// если не создан path - создаём
				if (!$this->issetSessionStatByKey('path_id') || true) {
					$userId = (int) $this->getSessionStatByKey('user_id');
					$date = $connection->escape($this->getNow());
					$siteId = (int) $this->getSessionStatByKey('site_id');
					$sourceId = (int) isset($source_id) ? $source_id : false;
					// установка path_id
					$qry =
						"INSERT INTO `cms_stat_paths` (`user_id`, `date`, `host_id`, `source_id`) VALUES ($userId, '$date', $siteId, $sourceId)";
					$connection->query($qry);

					$this->setSessionStatByKey('path_id', $connection->insertId());
					$this->setSessionStatByKey('number_in_path', 0);
				}
			}

			if (!isset($source_id)) {
				$source_id = null;
			}

			if (!$source_id && isset($_GET['_openstat'])) {
				$openstat_str = $_GET['_openstat'];
				try {
					$openstat = new openstat($openstat_str);
					$serviceId = (int) $openstat->getServiceId();
					$campaignId = (int) $openstat->getCampaignId();
					$adId = (int) $openstat->getAdId();
					$sourceId = (int) $openstat->getSourceId();
					$pathId = (int) $this->getSessionStatByKey('path_id');

					$qry = 'INSERT INTO `cms_stat_sources_openstat` (`service_id`, `campaign_id`, `ad_id`, `source_id`, `path_id`)
							 VALUES (' . $serviceId . ', ' . $campaignId . ', ' . $adId . ', ' . $sourceId . ', ' . $pathId . ')';
					$connection->query($qry);
				} catch (Exception $e) {
					$buffer = Service::Response()
						->getCurrentBuffer();
					$buffer->push($e->getMessage());
				}
			}

			// фиксирование хитов
			// поиск необходимой страницы
			$uri = $connection->escape($this->getUri());
			$siteId = (int) $this->getSessionStatByKey('site_id');
			$qry = "SELECT `id` FROM `cms_stat_pages` WHERE `uri` = '" . $uri . "' AND `host_id` = " . $siteId;
			$queryResult = $connection->queryResult($qry);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
			$row = $queryResult->fetch();
			if (isset($row['id'])) {
				$page_id = $row['id'];
				// проверяем - не нажал ли пользователь f5 (не находится ли на той же странице)
				if ($this->issetSessionStatByKey('last_page_id') && $this->getSessionStatByKey('last_page_id') == $page_id) {
					return;
				}
			} else {
				if (mb_strrpos($this->getUri(), '/') === 0) {
					$section = 'index';
				} else {
					$section = $connection->escape(mb_substr($this->getUri(), 1, mb_strpos($this->getUri(), '/', 1) - 1));
				}

				$siteId = (int) $this->getSessionStatByKey('site_id');
				$qry = "INSERT INTO `cms_stat_pages` (`uri`, `host_id`, `section`) VALUES ('" . $uri . "', '" . $siteId . "', '" .
					$section . "')";
				$connection->query($qry);
				$page_id = $connection->insertId();
			}

			// запоминаем текущую страницу
			$this->setSessionStatByKey('last_page_id', $page_id);
			$oldNumberInPath = $this->getSessionStatByKey('number_in_path');
			$this->setSessionStatByKey('number_in_path', $oldNumberInPath + 1);
			$prevPageId = $this->issetSessionStatByKey('prev_page_id') ? ', `prev_page_id`' : '';
			$anotherPrevPageId =
				$this->issetSessionStatByKey('prev_page_id') ? ', ' . (int) $this->getSessionStatByKey('prev_page_id') : '';
			$page_id = (int) $page_id;
			$pathId = (int) $this->getSessionStatByKey('path_id');
			$numberInPath = (int) $this->getSessionStatByKey('number_in_path');
			$qry =
				'INSERT INTO `cms_stat_hits` (`page_id`, `date`, `hour`, `day_of_week`, `week`, `day`, `month`, `year`, `path_id`, `number_in_path`' .
				$prevPageId . ') VALUES
					 (' . $page_id . ", '" . $this->getNow() . "', HOUR('" . $this->getNow() . "'), DATE_FORMAT('" .
				$this->getNow() . "', '%w'), WEEK('" . $this->getNow() . "'), DAY('" . $this->getNow() . "'), MONTH('" .
				$this->getNow() . "'), YEAR('" . $this->getNow() . "'), " . $pathId . ', ' . $numberInPath . $anotherPrevPageId . ')';
			$connection->query($qry);
			$hit_id = $connection->insertId();
			$this->setSessionStatByKey('prev_page_id', $page_id);

			// срабатывание событий
			$qry = '(SELECT   `r`.`event_id` FROM `cms_stat_events_urls` `u`
					 INNER JOIN `cms_stat_events_rel` `r` ON `r`.`metaevent_id` = `u`.`event_id`
					  WHERE `u`.`page_id` = ' . $page_id . ')

					UNION DISTINCT

					(SELECT   `event_id` FROM `cms_stat_events_urls`
					  WHERE `page_id` = ' . $page_id . ')';

			$queryResult = $connection->queryResult($qry);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);

			// генерируем запрос для добавления событий
			$q = '';

			foreach ($queryResult as $row) {
				$q .= '(' . $row['event_id'] . ', ' . $hit_id . '), ';
			}

			// в массиве $_SESSION['entryQry'] сохраняются события, зафиксированные до запуска сбора статистики (например вызов установки события вручную)
			if ($this->issetSessionStatByKey('entryQry')) {
				foreach ($this->getSessionStatByKey('entryQry') as $val) {
					$q .= '(' . $val . ', ' . $hit_id . '), ';
				}

				$this->delSessionStatByKey('entryQry');
			}

			if ($this->issetSessionStatByKey('events')) {
				foreach ($this->getSessionStatByKey('events') as $val) {
					$q .= '(' . $val . ', ' . $hit_id . '), ';
				}

				$this->delSessionStatByKey('events');
			}

			if ($q) {
				$q = mb_substr($q, 0, -2);
				$qry = 'INSERT INTO `cms_stat_events_collected` (`event_id`, `hit_id`) VALUES ' . $q;
				$connection->query($qry);
			}

			return true;
		}

		/**
		 * Устанавливает свой HTTP_REFERER
		 * @param string $referer HTTP_REFERER
		 */
		public function setReferer($referer) {
			$this->referer = $referer;
		}

		/**
		 * Устанавливает свой REQUEST_URI
		 * @param string $uri REQUEST_URI
		 */
		public function setUri($uri) {
			$this->uri = $uri;
		}

		/**
		 * Устанавливает свое имя сервера
		 * @param string $serverName имя сервера
		 */
		public function setServerName($serverName) {
			$this->serverName = $serverName;
		}

		/**
		 * Устанавливает свой REMOTE_ADDR
		 * @param string $remoteAddr REMOTE_ADDR
		 */
		public function setRemoteAddr($remoteAddr) {
			$this->remoteAddr = $remoteAddr;
		}

		/**
		 * Устанавливает источник текущего посещения как "входной билет"
		 * вызывается как site/stat/ticket/ticketname
		 * @param string $name имя билета
		 */
		public function ticket($name) {
			$connection = ConnectionPool::getInstance()->getConnection();
			$qry = "SELECT `id` FROM `cms_stat_sources_ticket` WHERE `url` = '" . $connection->escape($name) . "'";
			$queryResult = $connection->queryResult($qry);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
			$row = $queryResult->fetch();

			if (is_array($row) && isset($row['id'])) {
				$source_id = $this->getSourceId($row['id'], STAT_TICKET);
				$this->setSessionStatByKey('ticket_id', $source_id);
			}
		}

		/**
		 * Устанавливает идентификатор точки входа.
		 *  Метод, вызывается ЦМС при входе на специальную "точку входа". Пример: «www.mysite.ru/entry/somename».
		 * @param string $name имя точки входа
		 */
		public function entry($name) {
			$this->noCount = true;
			$this->setUpHostId();
			$connection = ConnectionPool::getInstance()->getConnection();
			$siteId = (int) $this->getSessionStatByKey('site_id');
			$qry = "SELECT   `p`.`url`, `e`.`event_id` FROM `cms_stat_entry_points` `p`
					 LEFT JOIN `cms_stat_entry_points_events` `e` ON `e`.`entry_point_id` = `p`.`id`
					  WHERE `name` = '" . $connection->escape($name) . "' AND `host_id` = " . $siteId;

			$queryResult = $connection->queryResult($qry);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($this->issetSessionStatByKey('entryQry')) {
				$this->delSessionStatByKey('entryQry');
			}

			foreach ($queryResult as $row) {
				if (!isset($redirect)) {
					$redirect = $row['url'];
				}

				$entries = $this->getSessionStatByKey('entryQry');
				$entries = is_array($entries) ? $entries : [];
				$entries[] = $row['event_id'];
				$this->setSessionStatByKey('entryQry', $entries);
			}

			if (!isset($redirect)) {
				$redirect = '/';
			}

			Service::Response()
				->getCurrentBuffer()
				->redirect($redirect);
		}

		/**
		 * Устанавливает идентификатор события
		 * @param string $name имя события
		 */
		public function event($name) {
			$this->setUpHostId();
			$connection = ConnectionPool::getInstance()->getConnection();
			$name = $connection->escape($name);
			$siteId = (int) $this->getSessionStatByKey('site_id');

			$qry = "SELECT `id` FROM `cms_stat_events`
					 WHERE `name` = '" . $name . "' AND `host_id` = " . $siteId;

			$queryResult = $connection->queryResult($qry);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
			$row = $queryResult->fetch();

			if (is_array($row) && isset($row['id'])) {
				$id = $row['id'];
			} else {
				$qry =
					"INSERT INTO `cms_stat_events` (`description`, `name`, `type`, `profit`, `host_id`) VALUES ('" . $name . "', '" .
					$name . "', 2, 0, " . $siteId . ')';
				$connection->query($qry);
				$id = $connection->insertId();
			}

			$events = $this->getSessionStatByKey('events');
			$events = is_array($events) ? $events : [];
			$events[] = $id;
			$this->setSessionStatByKey('events', $events);
		}

		/**
		 * Возвращает идентификатор текущего пользователя статистики
		 * @return mixed
		 */
		public function getUserId() {
			$this->getSessionStatByKey('loginId');
		}

		/** Устанавливает флаг, что пользователь был авторизован */
		public function doLogin() {
			$this->setSessionStatByKey('doLogin', true);
		}

		/**
		 * Возвращает HTTP_REFERER
		 * @return string
		 */
		private function getReferer() {
			return !empty($this->referer) ? $this->referer : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
		}

		/**
		 * Возвращает REQUEST_URI
		 * @return string
		 */
		private function getUri() {
			return !empty($this->uri) ? $this->uri : $_SERVER['REQUEST_URI'];
		}

		/**
		 * Возвращает HTTP_HOST, если его нет - SERVER_NAME
		 * @return string
		 */
		private function getServerName() {
			$newServerName = $_SERVER['HTTP_HOST'] ?: $_SERVER['SERVER_NAME'];
			return !empty($this->serverName) ? $this->serverName : $newServerName;
		}

		/**
		 * Возвращает REMOTE_ADDR
		 * @return string
		 */
		private function getRemoteAddr() {
			return !empty($this->remoteAddr) ? $this->remoteAddr : $_SERVER['REMOTE_ADDR'];
		}

		/** Устанавливает сессионные переменные данных о том, на каком хосте запущен класс для сбора статистики */
		private function setUpHostId() {
			if ($this->issetSessionStatByKey('site_id')) {
				return;
			}

			$serverName = $this->getServerName();
			$umiDomains = Service::DomainCollection();
			$domain = $umiDomains->getDomainId($serverName);

			if (!$domain instanceof iDomain) {
				$domain = $umiDomains->getDefaultDomain();

				if (!$domain instanceof iDomain) {
					throw new coreException('Cannot detect default domain');
				}
			}

			$connection = ConnectionPool::getInstance()
				->getConnection();
			/** @var iDomain|iDomainMirror $domain */
			$domainName = $connection->escape($domain->getHost());

			$qry = "SELECT `group_id` FROM `cms_stat_sites` WHERE `name` = '" . $domainName . "'";
			$queryResult = $connection->queryResult($qry);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
			$row = $queryResult->fetch();

			if (isset($row['group_id'])) {
				$this->setSessionStatByKey('site_id', $row['group_id']);
				return;
			}

			$qry = "INSERT INTO `cms_stat_sites_groups` (`name`) VALUES ('" . $domainName . "')";
			$connection->query($qry);
			$id = (int) $connection->insertId();
			$qry = "INSERT INTO `cms_stat_sites` (`name`, `group_id`) VALUES ('" . $domainName . "', " . $id . ')';
			$connection->query($qry);
			$this->setSessionStatByKey('site_id', $id);
		}

		/**
		 * Метод, возвращающий id источника посещения
		 * Возвращает id источника посещения
		 * @param integer $concreteSourceId id конкретного источника
		 * @param integer $type тип источника (@see константы над объявления класса)
		 * @return integer
		 */
		private function getSourceId($concreteSourceId, $type) {

			$concreteSourceId = (int) $concreteSourceId;
			$type = (int) $type;
			$connection = ConnectionPool::getInstance()->getConnection();
			$qry =
				'SELECT `id` FROM `cms_stat_sources` WHERE `concrete_src_id` = ' . $concreteSourceId . ' AND `src_type` = ' . $type;

			$queryResult = $connection->queryResult($qry);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
			$row = $queryResult->fetch();

			if (is_array($row) && isset($row['id'])) {
				$source_id = $row['id'];
			} else {
				$qry = <<<SQL
INSERT INTO `cms_stat_sources` (`src_type`, `concrete_src_id`)
VALUES ($type, $concreteSourceId)
SQL;
				$connection->query($qry);
				$source_id = $connection->insertId();
			}

			return $source_id;
		}

		/**
		 * Добавляет нового пользователя в статистику и возвращает его идентификатор
		 * @return integer
		 */
		private function addUser() {
			$this->setUpHostId();
			$connection = ConnectionPool::getInstance()->getConnection();
			$login = $connection->escape($this->getLogin());
			$location = $connection->escape($this->getLocation($this->getRemoteAddr()));
			$jsVersion = $connection->escape($this->getJsVersion());
			$siteId = (int) $this->getSessionStatByKey('site_id');
			$qry = "INSERT INTO `cms_stat_users` (`session_id`, `first_visit`, `login`, `os_id`, `browser_id`, `ip`, `location`, `js_version`, `host_id`) VALUES
					('" . $this->getSessionId() . "', '" . $this->getNow() . "', '" . $login . "', '" . (int) $this->getOsId() .
				"', '" . (int) $this->getBrowserId() . "', '" . $connection->escape($this->getRemoteAddr()) . "', '" . $location .
				"', '" . $jsVersion . "', " . $siteId . ')';
			$connection->query($qry);
			return $connection->insertId();
		}

		/**
		 * Возвращает идентификатор текущего пользователя системы
		 * @return string
		 */
		private function getLogin() {
			$auth = Service::Auth();
			return $auth->getUserId();
		}

		/**
		 * Возвращает идентификатор браузера клиента
		 * @return integer
		 */
		private function getBrowserId() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$browser = $connection->escape(Service::Request()->getBrowser());
			$qry = "SELECT `id` FROM `cms_stat_users_browsers` WHERE `name` = '" . $browser . "'";
			$queryResult = $connection->queryResult($qry);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
			$row = $queryResult->fetch();
			if (is_array($row) && isset($row['id'])) {
				return $row['id'];
			}

			$qry = "INSERT INTO `cms_stat_users_browsers` (`name`) VALUES ('" . $browser . "')";
			$connection->query($qry);
			return $connection->insertId();
		}

		/**
		 * Возвращает идентификатор операционной системы клиента
		 * @return integer
		 */
		private function getOsId() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$os = $connection->escape(Service::Request()->getPlatform());
			$qry = "SELECT `id` FROM `cms_stat_users_os` WHERE `name` = '" . $os . "'";
			$queryResult = $connection->queryResult($qry);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
			$row = $queryResult->fetch();
			if (is_array($row) && isset($row['id'])) {
				return $row['id'];
			}

			$qry = "INSERT INTO `cms_stat_users_os` (`name`) VALUES ('" . $os . "')";
			$connection->query($qry);
			return $connection->insertId();
		}

		/**
		 * Возвращает версию javaScript
		 * @return string
		 */
		private function getJsVersion() {
			$connection = ConnectionPool::getInstance()->getConnection();
			return $connection->escape(1.5);
		}

		/**
		 * Возвращает данные о местонахождении клиента
		 * по его ip.
		 * Использует модуль "GeoIP".
		 * @param string $ip IP-клиента
		 * @return string
		 */
		private function getLocation($ip) {
			/** @var geoip $geoIp */
			$geoIp = cmsController::getInstance()
				->getModule('geoip');
			return ($geoIp instanceof geoip) ? $geoIp->getStatAddress($ip) : '';
		}

		/**
		 * Возвращает текущее время в формате mysql
		 * @return string
		 */
		private function getNow() {
			return date('Y-m-d H:i:s', $this->time);
		}

		/**
		 * Проверяет, является ли поисковым ботом текущий клиент
		 * @return boolean
		 */
		private function isSearchBot() {
			if (Service::Request()->isRobot()) {
				return true;
			}

			$this->_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

			foreach ($this->_robots as $robot) {
				if (contains(mb_strtolower($this->_agent), mb_strtolower($robot))) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Декодирует строку в utf-8
		 * @param string $text исходная строка
		 * @return string
		 */
		private function convertCharset($text) {
			$textConverted = rawurldecode($text);
			if ($textConverted) {
				$text = $textConverted;
			}

			$sCharset = $this->detectCharset($text);

			if (function_exists('iconv') && $sCharset !== 'UTF-8') {
				$textConverted = @iconv($sCharset, 'UTF-8', $text);
				if ($textConverted) {
					$text = $textConverted;
				}
			}

			return $text;
		}

		/**
		 * Возвращает кодировку строки
		 * @param string $sStr строка
		 * @return string
		 */
		private static function detectCharset($sStr) {
			if (preg_match("/[\x{0000}-\x{FFFF}]+/u", $sStr)) {
				return 'UTF-8';
			}
			$sAnswer = 'CP1251';

			if (!function_exists('iconv')) {
				return $sAnswer;
			}

			$arrCyrEncodings = [
				'CP1251',
				'KOI8-R',
				'UTF-8',
				'ISO-8859-5',
				'CP866'
			];

			if (function_exists('mb_detect_encoding')) {
				return mb_detect_encoding($sStr, implode(', ', $arrCyrEncodings));
			}

			return 'UTF-8';
		}

		/**
		 * Возвращает идентификатор сессии
		 * @return null|string
		 */
		private function getSessionId() {
			return Service::Session()->getId();
		}

		/**
		 * Возвращает сессионные данные статистики
		 * @param UmiCms\System\Session\iSession $session
		 * @return array
		 */
		private function getSessionStat(UmiCms\System\Session\iSession $session) {
			$statData = $session->get('stat');
			return is_array($statData) ? $statData : [];
		}

		/**
		 * Сохраняет сессионные данные статистики
		 * @param UmiCms\System\Session\iSession $session
		 * @param mixed $stat данные
		 */
		private function saveSessionStat(UmiCms\System\Session\iSession $session, $stat) {
			$session->set('stat', $stat);
		}

		/**
		 * Проверяет существуют ли сессионные данные по ключу
		 * @param string $key ключ
		 * @return bool
		 */
		private function issetSessionStatByKey($key) {
			$session = Service::Session();
			$statData = $this->getSessionStat($session);
			return isset($statData[$key]);
		}

		/**
		 * Возвращает сессионные данные по ключу
		 * @param string $key ключ
		 * @return mixed
		 */
		private function getSessionStatByKey($key) {
			$session = Service::Session();
			$statData = $this->getSessionStat($session);
			return isset($statData[$key]) ? $statData[$key] : null;
		}

		/**
		 * Устанавливает сессионные данные по ключу
		 * @param string $key ключ
		 * @param mixed $value данные
		 */
		private function setSessionStatByKey($key, $value) {
			$session = Service::Session();
			$statData = $this->getSessionStat($session);
			$statData[$key] = $value;
			$this->saveSessionStat($session, $statData);
		}

		/**
		 * Удаляет сессионные данные по ключу,
		 * если он не передан - удаляет все сессионные данные
		 * @param string|null $key ключ
		 */
		private function delSessionStatByKey($key = null) {
			$session = Service::Session();

			if ($key === null) {
				$session->del('stat');
			} else {
				$statData = $this->getSessionStat($session);
				unset($statData[$key]);
				$this->saveSessionStat($session, $statData);
			}
		}

		/** Устанавливает куку с идентификатором сессии на 10 лет */
		private function setStatIdCookie() {
			Service::CookieJar()
				->set('stat_id', $this->getSessionId(), strtotime('+10 years'));
		}
	}
