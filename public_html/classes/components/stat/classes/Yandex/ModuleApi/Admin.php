<?php

	namespace UmiCms\Classes\Components\Stat\Yandex;

	use UmiCms\Classes\Components\Stat\Yandex\Metric\iClient;
	use UmiCms\Classes\Components\Stat\Yandex\Metric\iSerializer;
	use UmiCms\Classes\System\Utils\Api\Http\Exception\BadResponse;
	use UmiCms\Classes\System\Utils\Api\Http\Json\Yandex\Exception\BadToken;
	use UmiCms\Service;

	/**
	 * Класс административного функционала интеграции с "Яндекс.Метрика"
	 * @package UmiCms\Classes\Components\Stat\Yandex
	 */
	class Admin implements \iModulePart {

		use \tModulePart;

		/** @var \StatAdmin $admin экземпляр главного класса административного функционала */
		private $admin;

		/**
		 * Конструктор
		 * @param \stat $module экземпляр главного класса модуля
		 * @throws \coreException
		 */
		public function __construct(\stat $module) {
			if (!$module->isClassImplemented($module::ADMIN_CLASS)) {
				throw new \coreException(
					getLabel('label-error-stat-admin-not-implemented')
				);
			}

			$this->admin = $module->getImplementedInstance($module::ADMIN_CLASS);
		}

		/**
		 * Выводит данные для вкладки "Яндекс.Метрика": список счетчиков.
		 *
		 * [
		 *      # => @see Admin::getAddedCounterInfo() | $this::getAvailableCounterInfo()
		 * ]
		 *
		 * @throws \publicAdminException
		 */
		public function yandexMetric() {
			try {
				$counterList = $this->getYandexMetric()
					->getCounterList(); // вызывается сразу, чтобы видеть ошибки на странице вкладки
			} catch (BadToken $exception) {
				throw new \publicAdminException(
					getLabel('label-error-yandex-metric-invalid-token', false, $this->module->pre_lang)
				);
			}

			if ($this->module->ifNotJsonMode()) {
				$this->admin->setDataSetDirectCallMessage();
				return;
			}

			$addedCounterList = [];

			foreach ($counterList as $counter) {
				$domainId = $this->getDomainIdBySite($counter['site']);

				if ($domainId) {
					$addedCounterList[] = $this->getAddedCounterInfo($counter);
				}
			}

			$availableCounterList = [];

			foreach (Service::DomainCollection()->getList() as $id => $domain) {
				$availableCounterList[$id] = $this->getAvailableCounterInfo($domain);
			}

			$fullCounterList = array_merge($addedCounterList, $availableCounterList);

			$this->module->printJson(
				$this->admin->prepareTableControlEntities($fullCounterList, count($fullCounterList))
			);
		}

		/** Возвращает конфиг вкладки "Яндекс.Метрика" в формате JSON для табличного контрола */
		public function flushCounterListConfig() {
			$this->module->printJson($this->getCounterListConfig());
		}

		/**
		 * Добавляет счетчик в "Яндекс.Метрика"
		 * @param int|null $domainId идентификатор домена, на основе его данных будет добавлен счетчик
		 *
		 * [
		 *      'counter_id' => 'Идентификатор счетчика'
		 * ]
		 *
		 * @throws \publicAdminException
		 */
		public function addCounter($domainId = null) {
			$domainId = $domainId
				?: Service::Request()
					->Post()
					->get('domain_id');

			$domain = Service::DomainCollection()
				->getDomain($domainId);

			if (!$domain instanceof \iDomain) {
				throw new \publicAdminException(
					getLabel('label-error-domain-not-found-by-id', false, $domainId)
				);
			}

			$counterName = sprintf('%s-%d', $domain->getHost(true), time());
			$counterData = $this->getYandexMetricSerializer()
				->getCounter($counterName, $domainId);

			try {
				$counterId = $this->getYandexMetric()
					->addCounter($counterData);
			} catch (BadResponse $exception) {
				$code = $exception->getCode() ? ': ' . $exception->getCode() : '';
				throw new \publicAdminException($exception->getMessage() . $code);
			}

			$this->admin->setData(['counter_id' => $counterId]);
			$this->admin->doData();
		}

		/**
		 * Сохраняет код счетчика
		 * @param int|null $counterId идентификатор счетчика
		 *
		 * [
		 *      'success' => true|false
		 * ]
		 *
		 * @throws \publicAdminException
		 */
		public function saveCounterCode($counterId = null) {
			$counterId = $counterId
				?: Service::Request()
					->Get()
					->get('counter_id');

			try {
				$counter = $this->getYandexMetric()
					->getCounter($counterId);
				$code = $counter['code'];
			} catch (\Exception $exception) {
				$code = $exception->getCode() ? ': ' . $exception->getCode() : '';
				throw new \publicAdminException($exception->getMessage() . $code);
			}

			$numberOfBytes = $this->getCounterCodeFile($counterId)
				->putContent($code);

			$this->admin->setData(['success' => is_numeric($numberOfBytes)]);
			$this->admin->doData();
		}

		/**
		 * Инициирует скачивание кода счетчика
		 * @param int|null $counterId идентификатор счетчика
		 * @throws \publicAdminException
		 */
		public function downloadCounterCode($counterId = null) {
			$counterId = $counterId ?: $this->admin->getNumberedParameter(0);
			$file = $this->getCounterCodeFile($counterId);

			if (!$file->isExists()) {
				$this->admin->chooseRedirect('/admin/stat/yandexMetric');
			}

			$this->getCounterCodeFile($counterId)
				->download(true);
		}

		/**
		 * Меняет название счетчика
		 * @param int|null $counterId идентификатор счетчика
		 * @param string|null $name новое название
		 * @throws \publicAdminException
		 */
		public function editName($counterId = null, $name = null) {
			$client = $this->getYandexMetric();
			$counterId = $counterId ?: $this->admin->getNumberedParameter(0);

			try {
				$counter = $client->getCounter($counterId);
			} catch (\Exception $exception) {
				$code = $exception->getCode() ? ': ' . $exception->getCode() : '';
				throw new \publicAdminException($exception->getMessage() . $code);
			}

			$domainId = $this->getDomainIdBySite($counter['site']);

			if (!$domainId) {
				throw new \publicAdminException(
					getLabel('label-error-domain-not-found-by-host', false, $counter['site'])
				);
			}

			$name = $name
				?: Service::Request()
					->Post()
					->get('value');
			$counterData = $this->getYandexMetricSerializer()
				->getCounter($name, $domainId);

			try {
				$success = $client->editCounter($counterId, $counterData);
			} catch (\Exception $exception) {
				$code = $exception->getCode() ? ': ' . $exception->getCode() : '';
				throw new \publicAdminException($exception->getMessage() . $code);
			}

			$this->admin->setData(['success' => $success]);
			$this->admin->doData();
		}

		/**
		 * Возвращает статистику счетчика
		 * @param int|null $counterId идентификатор счетчика
		 * @param string|null $section идентификатор раздела
		 * @param string|null $subsection идентификатор подраздела
		 * @param string|null $from начало периода отчета
		 * @param string|null $to конец периода отчета
		 * @throws \publicAdminException
		 */
		public function getCounterStat($counterId = null, $section = null, $subsection = null, $from = null, $to = null) {
			$this->admin->setDataType('form');
			$this->admin->setActionType('modify');
			$counterId = $counterId ?: $this->admin->getNumberedParameter(0);
			$section = $section ?: $this->admin->getNumberedParameter(1);
			$section = $section ?: 'traffic';
			$subsection = $subsection ?: $this->admin->getNumberedParameter(2);

			$dateFactory = Service::DateFactory();
			$from = $from ?: $this->getPeriodDate($from, 3, 'yandex_metric_date_period_start');
			$from = $from ? $dateFactory->createByDateString($from) : $dateFactory->createByDateString('-31 day');
			$to = $to ?: $this->getPeriodDate($to, 4, 'yandex_metric_date_period_end');
			$to = $to ? $dateFactory->createByDateString($to) : $dateFactory->createByDateString('-1 day');

			try {
				$data = [
					'@counter_id' => $counterId,
					'@section' => $section,
					'@subsection' => $subsection,
					'@date_from' => $from->getFormattedDate('Y-m-d'),
					'@date_to' => $to->getFormattedDate('Y-m-d'),
					'nodes:section' => [
						$this->getTraffic($counterId, $section, $subsection, $from, $to),
						$this->getSources($counterId, $section, $subsection, $from, $to),
						$this->getContent($counterId, $section, $subsection, $from, $to),
						$this->getUsers($counterId, $section, $subsection, $from, $to),
						$this->getComputers($counterId, $section, $subsection, $from, $to)
					]
				];
			} catch (\Exception $e) {
				throw new \publicAdminException($e->getMessage());
			}

			$this->admin->setData(['data' => $data]);
			$this->admin->doData();
		}

		/**
		 * Возвращает дату периода
		 * @param string $date переданное значение даты
		 * @param integer $parameterNumber номер параметра с датой в get запросе
		 * @param string $cookieName имя куки для хранения даты
		 * @return string
		 */
		public function getPeriodDate($date, $parameterNumber, $cookieName) {
			$date = $date ?: $this->admin->getNumberedParameter($parameterNumber);
			$cookieJar = Service::CookieJar();

			if ($date) {
				$cookieJar->set($cookieName, $date);
			}

			return $date ?: $cookieJar->get($cookieName);
		}

		/**
		 * Удаляет счетчик из "Яндекс.Метрика"
		 * @param int|null $counterId идентификатор счетчика
		 *
		 * [
		 *      'success' => true|false
		 * ]
		 *
		 * @throws \publicAdminException
		 */
		public function deleteCounter($counterId = null) {
			$counterId = $counterId
				?: Service::Request()
					->Post()
					->get('counter_id');

			try {
				$success = $this->getYandexMetric()
					->deleteCounter($counterId);
			} catch (\Exception $exception) {
				$code = $exception->getCode() ? ': ' . $exception->getCode() : '';
				throw new \publicAdminException($exception->getMessage() . $code);
			}

			$this->admin->setData(['success' => $success]);
			$this->admin->doData();
		}

		/**
		 * Возвращает статистику по трафику:
		 *
		 *  1) Сводную линейную диаграмму посещаемости с привязкой ко времени;
		 *  2) Таблицу статистики конверсии;
		 *  3) Таблицу статистики посещаемости по времени суток;
		 *
		 * @param int $counterId идентификатор счетчика
		 * @param string $section выбранная группа статистики
		 * @param string $subsection выбранная подгруппа статистики
		 * @param \iUmiDate $from дата начала периода отчета
		 * @param \iUmiDate $to дата конца периода отчета
		 * @return array
		 */
		private function getTraffic($counterId, $section, $subsection, \iUmiDate $from, \iUmiDate $to) {
			$defaultSubsection = 'attendance';
			$subsection = $subsection ?: $defaultSubsection;
			$client = $this->getYandexMetric();
			$traffic = ($subsection === 'attendance') ? $client->getTraffic($counterId, $from, $to) : [];
			$conversion = ($subsection === 'conversion') ? $client->getConversion($counterId, $from, $to) : [];
			$hourlyTraffic = ($subsection === 'hourlyTraffic') ? $client->getHourlyTraffic($counterId, $from, $to) : [];
			return [
				'@id' => 'traffic',
				'@default-subsection' => $defaultSubsection,
				'@label' => getLabel('label-traffic'),
				'@selected' => ($section === 'traffic') ? '1' : '0',
				'nodes:history' => [
					$this->prepareHistory(
						'attendance',
						getLabel('label-attendance'),
						$this->prepareHistoryDataSetList($traffic),
						$subsection
					),
				],
				'nodes:table' => [
					$this->prepareTable(
						'conversion',
						getLabel('label-conversion'),
						$this->prepareTableDataSet($conversion, getLabel('label-target-name'), 1),
						$subsection
					),
					$this->prepareTable(
						'hourlyTraffic',
						getLabel('label-hourly-traffic'),
						$this->prepareTableDataSet($hourlyTraffic, getLabel('label-day-time'), 1),
						$subsection
					)
				]
			];
		}

		/**
		 * Возвращает статистику по источника:
		 *
		 *  1) Сводную статистическую таблицу источников;
		 *  2) Таблицу статистики по поисковым система;
		 *  3) Таблицу статистики по поисковым фразам;
		 *  4) Таблицу статистики по сайтам;
		 *  5) Список круговых диаграмм по социальным сетям;
		 *
		 * @param int $counterId идентификатор счетчика
		 * @param string $section выбранная группа статистики
		 * @param string $subsection выбранная подгруппа статистики
		 * @param \iUmiDate $from дата начала периода отчета
		 * @param \iUmiDate $to дата конца периода отчета
		 * @return array
		 */
		private function getSources($counterId, $section, $subsection, \iUmiDate $from, \iUmiDate $to) {
			$defaultSubsection = 'resume';
			$subsection = $subsection ?: $defaultSubsection;
			$client = $this->getYandexMetric();
			$sourcesSummary = ($subsection === 'resume') ? $client->getSourcesSummary($counterId, $from, $to) : [];
			$searchEngines = ($subsection === 'searchEngines') ? $client->getSourcesSearchEngines($counterId, $from, $to) : [];
			$searchPhrases = ($subsection === 'searchPhrases') ? $client->getSourcesSearchPhrases($counterId, $from, $to) : [];
			$sites = ($subsection === 'sites') ? $client->getSourcesSites($counterId, $from, $to) : [];
			$socialNetwork = ($subsection === 'socialNetworks') ? $client->getSourcesSocials($counterId, $from, $to) : [];
			return [
				'@id' => 'sources',
				'@default-subsection' => $defaultSubsection,
				'@label' => getLabel('label-source-list'),
				'@selected' => ($section == 'sources') ? '1' : '0',
				'nodes:table' => [
					$this->prepareTable(
						'resume',
						getLabel('label-resume'),
						$this->prepareTableDataSet($sourcesSummary, getLabel('label-source'), 3),
						$subsection
					),
					$this->prepareTable(
						'searchEngines',
						getLabel('label-search-engines'),
						$this->prepareTableDataSet($searchEngines, getLabel('label-search-engine'), 3),
						$subsection
					),
					$this->prepareTable(
						'searchPhrases',
						getLabel('label-search-phrases'),
						$this->prepareTableDataSet($searchPhrases, getLabel('label-search-phrase'), 1),
						$subsection
					),
					$this->prepareTable(
						'sites',
						getLabel('label-sites'),
						$this->prepareTableDataSet($sites, getLabel('label-site'), 1),
						$subsection
					)
				],
				'nodes:pie-chart' => [
					$this->preparePieChartList(
						'socialNetworks',
						getLabel('label-social-networks'),
						$this->preparePieChartDataSetList($socialNetwork),
						$subsection
					)
				]
			];
		}

		/**
		 * Возвращает статистику по содержанию сайта:
		 *
		 *  1) Таблицу статистики по популярному контенту;
		 *  2) Таблицу статистики по страницам входа;
		 *  3) Таблицу статистики по страницам выхода;
		 *  4) Таблицу статистики по заголовкам;
		 *  5) Таблицу статистики по параметрам url;
		 *  6) Таблицу статистики по параметрам посетителей;
		 *  7) Таблицу статистики по параметрам визитов;
		 *
		 * @param int $counterId идентификатор счетчика
		 * @param string $section выбранная группа статистики
		 * @param string $subsection выбранная подгруппа статистики
		 * @param \iUmiDate $from дата начала периода отчета
		 * @param \iUmiDate $to дата конца периода отчета
		 * @return array
		 */
		private function getContent($counterId, $section, $subsection, \iUmiDate $from, \iUmiDate $to) {
			$defaultSubsection = 'popular';
			$subsection = $subsection ?: $defaultSubsection;
			$client = $this->getYandexMetric();
			$popularContent = ($subsection === 'popular') ? $client->getPopularContent($counterId, $from, $to) : [];
			$contentEntrance = ($subsection === 'contentEntrance') ? $client->getContentEntrance($counterId, $from, $to) : [];
			$contentExit = ($subsection === 'contentExit') ? $client->getContentExit($counterId, $from, $to) : [];
			$titles = ($subsection === 'titles') ? $client->getTitles($counterId, $from, $to) : [];
			$urlParams = ($subsection === 'urlParams') ? $client->getUrlParams($counterId, $from, $to) : [];
			$userParams = ($subsection === 'userParams') ? $client->getContentUserParams($counterId, $from, $to) : [];
			$visitParams = ($subsection === 'visitParams') ? $client->getContentVisitParams($counterId, $from, $to) : [];
			return [
				'@id' => 'content',
				'@default-subsection' => $defaultSubsection,
				'@label' => getLabel('label-content'),
				'@selected' => ($section == 'content') ? '1' : '0',
				'nodes:table' => [
					$this->prepareTable(
						'popular',
						getLabel('label-popular'),
						$this->prepareTableDataSet($popularContent, getLabel('label-page'), 1),
						$subsection
					),
					$this->prepareTable(
						'contentEntrance',
						getLabel('label-content-entrance'),
						$this->prepareTableDataSet($contentEntrance, getLabel('label-page'), 1),
						$subsection
					),
					$this->prepareTable(
						'contentExit',
						getLabel('label-content-exit'),
						$this->prepareTableDataSet($contentExit, getLabel('label-page'), 1),
						$subsection
					),
					$this->prepareTable(
						'titles',
						getLabel('label-titles'),
						$this->prepareTableDataSet($titles, getLabel('label-header'), 1),
						$subsection
					),
					$this->prepareTable(
						'urlParams',
						getLabel('label-url-params'),
						$this->prepareTableDataSet($urlParams, getLabel('label-parameter'), 1),
						$subsection
					),
					$this->prepareTable(
						'userParams',
						getLabel('label-user-params'),
						$this->prepareTableDataSet($userParams, getLabel('label-parameter'), 1),
						$subsection
					),
					$this->prepareTable(
						'visitParams',
						getLabel('label-visit-params'),
						$this->prepareTableDataSet($visitParams, getLabel('label-parameter'), 1),
						$subsection
					)
				]
			];
		}

		/**
		 * Возвращает статистику по посетителям сайта:
		 *
		 *  1) Таблицу статистики по гео локациям;
		 *  2) Таблицу статистики по интересам;
		 *  3) Таблицу статистики по времени на сайте;
		 *  4) Список круговых диаграмм по возрастам;
		 *  5) Список круговых диаграмм по полам;
		 *  6) Список круговых диаграмм по времени с первого визита;
		 *  7) Список круговых диаграмм по периодичности визитов;
		 *  8) Список круговых диаграмм по времени с последнего визита;
		 *  9) Список круговых диаграмм по числу визитов;
		 *  10) Список круговых диаграмм по глубине просмотра;
		 *
		 * @param int $counterId идентификатор счетчика
		 * @param string $section выбранная группа статистики
		 * @param string $subsection выбранная подгруппа статистики
		 * @param \iUmiDate $from дата начала периода отчета
		 * @param \iUmiDate $to дата конца периода отчета
		 * @return array
		 */
		private function getUsers($counterId, $section, $subsection, \iUmiDate $from, \iUmiDate $to) {
			$defaultSubsection = 'geography';
			$subsection = $subsection ?: $defaultSubsection;
			$client = $this->getYandexMetric();
			$countries = ($subsection === 'geography') ? $client->getCountries($counterId, $from, $to) : [];
			$interests = ($subsection === 'interests') ? $client->getInterests($counterId, $from, $to) : [];
			$age = ($subsection === 'age') ? $client->getAge($counterId, $from, $to) : [];
			$gender = ($subsection === 'gender') ? $client->getGender($counterId, $from, $to) : [];
			$loyaltyNewness = ($subsection === 'loyaltyNewness') ? $client->getLoyaltyNewness($counterId, $from, $to) : [];
			$loyaltyPeriod = ($subsection === 'loyaltyPeriod') ? $client->getLoyaltyPeriod($counterId, $from, $to) : [];
			$loyaltyRecency = ($subsection === 'loyaltyRecency') ? $client->getLoyaltyRecency($counterId, $from, $to) : [];
			$loyaltyVisits = ($subsection === 'loyaltyVisits') ? $client->getLoyaltyVisits($counterId, $from, $to) : [];
			$deepnessDepth = ($subsection === 'deepnessDepth') ? $client->getDeepnessDepth($counterId, $from, $to) : [];
			$deepnessTime = ($subsection === 'deepnessTime') ? $client->getDeepnessTime($counterId, $from, $to) : [];
			return [
				'@id' => 'users',
				'@default-subsection' => $defaultSubsection,
				'@label' => getLabel('label-user-list'),
				'@selected' => ($section == 'users') ? '1' : '0',
				'nodes:pie-chart' => [
					$this->preparePieChartList(
						'age',
						getLabel('label-age'),
						$this->preparePieChartDataSetList($age),
						$subsection
					),
					$this->preparePieChartList(
						'gender',
						getLabel('label-gender'),
						$this->preparePieChartDataSetList($gender),
						$subsection
					),
					$this->preparePieChartList(
						'loyaltyNewness',
						getLabel('label-loyalty-newness'),
						$this->preparePieChartDataSetList($loyaltyNewness),
						$subsection
					),
					$this->preparePieChartList(
						'loyaltyPeriod',
						getLabel('label-loyalty-period'),
						$this->preparePieChartDataSetList($loyaltyPeriod),
						$subsection
					),
					$this->preparePieChartList(
						'loyaltyRecency',
						getLabel('label-loyalty-recency'),
						$this->preparePieChartDataSetList($loyaltyRecency),
						$subsection
					),
					$this->preparePieChartList(
						'loyaltyVisits',
						getLabel('label-loyalty-visits'),
						$this->preparePieChartDataSetList($loyaltyVisits),
						$subsection
					),
					$this->preparePieChartList(
						'deepnessDepth',
						getLabel('label-deepness-depth'),
						$this->preparePieChartDataSetList($deepnessDepth),
						$subsection
					)
				],
				'nodes:table' => [
					$this->prepareTable(
						'geography',
						getLabel('label-geography'),
						$this->prepareTableDataSet($countries, getLabel('label-location'), 3),
						$subsection
					),
					$this->prepareTable(
						'interests',
						getLabel('label-interests'),
						$this->prepareTableDataSet($interests, getLabel('label-interest'), 1),
						$subsection
					),
					$this->prepareTable(
						'deepnessTime',
						getLabel('label-deepness-time'),
						$this->prepareTableDataSet($deepnessTime, getLabel('label-time'), 3),
						$subsection
					)
				]
			];
		}

		/**
		 * Возвращает статистику по компьютерам:
		 *
		 *  1) Таблицу статистики по браузерам;
		 *  2) Таблицу статистики по группам дисплеев;
		 *  3) Таблицу статистики по размерам окна;
		 *  4) Таблицу статистики по дисплеям;
		 *  5) Таблицу статистики по используемым версиям Flash;
		 *  6) Таблицу статистики по используемым платформам;
		 *  7) Таблицу статистики по используемым версиям Silverlight;
		 *  8) Таблицу статистики по используемым устройствам;
		 *  9) Список круговых диаграмм по использованию Cookie;
		 *  10) Список круговых диаграмм по использованию JavaScript;
		 *  11) Список круговых диаграмм по использованию Java;
		 *
		 * @param int $counterId идентификатор счетчика
		 * @param string $section выбранная группа статистики
		 * @param string $subsection выбранная подгруппа статистики
		 * @param \iUmiDate $from дата начала периода отчета
		 * @param \iUmiDate $to дата конца периода отчета
		 * @return array
		 */
		private function getComputers($counterId, $section, $subsection, $from, $to) {
			$defaultSubsection = 'browsers';
			$subsection = $subsection ?: $defaultSubsection;
			$client = $this->getYandexMetric();
			$resolutionMap = ($subsection === 'resolutionMap') ? $client->getResolutionMap($counterId, $from, $to) : [];
			$browsers = ($subsection === 'browsers') ? $client->getBrowsers($counterId, $from, $to) : [];
			$cookie = ($subsection === 'cookie') ? $client->getCookies($counterId, $from, $to) : [];
			$display = ($subsection === 'displays') ? $client->getDisplay($counterId, $from, $to) : [];
			$displayGroups = ($subsection === 'displayGroups') ? $client->getDisplayGroups($counterId, $from, $to) : [];
			$flash = ($subsection === 'flash') ? $client->getFlash($counterId, $from, $to) : [];
			$java = ($subsection === 'java') ? $client->getJava($counterId, $from, $to) : [];
			$javaScript = ($subsection === 'javaScript') ? $client->getJavaScript($counterId, $from, $to) : [];
			$platforms = ($subsection === 'platforms') ? $client->getPlatforms($counterId, $from, $to) : [];
			$silverLight = ($subsection === 'silverlight') ? $client->getSilverlight($counterId, $from, $to) : [];
			$devices = ($subsection === 'devices') ? $client->getDevices($counterId, $from, $to) : [];
			return [
				'@id' => 'computers',
				'@default-subsection' => $defaultSubsection,
				'@label' => getLabel('label-computers'),
				'@selected' => ($section == 'computers') ? '1' : '0',
				'nodes:pie-chart' => [
					$this->preparePieChartList(
						'cookie',
						'Cookie',
						$this->preparePieChartDataSetList($cookie),
						$subsection
					),
					$this->preparePieChartList(
						'javaScript',
						'JavaScript',
						$this->preparePieChartDataSetList($javaScript),
						$subsection
					),
					$this->preparePieChartList(
						'java',
						'Java',
						$this->preparePieChartDataSetList($java),
						$subsection
					)
				],
				'nodes:table' => [
					$this->prepareTable(
						'browsers',
						getLabel('label-browsers'),
						$this->prepareTableDataSet($browsers, getLabel('label-browser'), 3),
						$subsection
					),
					$this->prepareTable(
						'displayGroups',
						getLabel('label-display-groups'),
						$this->prepareTableDataSet($displayGroups, getLabel('label-size'), 3),
						$subsection
					),
					$this->prepareTable(
						'resolutionMap',
						getLabel('label-resolution-map'),
						$this->prepareTableDataSet($resolutionMap, getLabel('label-size'), 3),
						$subsection
					),
					$this->prepareTable(
						'displays',
						getLabel('label-displays'),
						$this->prepareTableDataSet($display, getLabel('label-display'), 3),
						$subsection
					),
					$this->prepareTable(
						'flash',
						'Flash',
						$this->prepareTableDataSet($flash, getLabel('label-version'), 1),
						$subsection
					),
					$this->prepareTable(
						'platforms',
						getLabel('label-platforms'),
						$this->prepareTableDataSet($platforms, getLabel('label-platform'), 3),
						$subsection
					),
					$this->prepareTable(
						'silverlight',
						'Silverlight',
						$this->prepareTableDataSet($silverLight, getLabel('label-status'), 3),
						$subsection
					),
					$this->prepareTable(
						'devices',
						getLabel('label-devices'),
						$this->prepareTableDataSet($devices, getLabel('label-device'), 3),
						$subsection
					)
				]
			];
		}

		/**
		 * Возвращает информацию о добавленном счетчике
		 * @param array $counter
		 * @return array
		 *
		 * [
		 *      'id' => 'Идентификатор счетчика',
		 *      'name' => 'Название счетчика',
		 *      'status' => 'Статус счетчика',
		 *      'code_status' => 'Код статуса счетчика',
		 *      'site' =>  'Адрес сайта'
		 * ]
		 */
		private function getAddedCounterInfo(array $counter) {
			$statusCode = $counter['code_status'];
			return [
				'id' => $counter['id'],
				'name' => $counter['name'],
				'active' => contains($counter['status'], 'Active'),
				'status' => getLabel('label-counter-status-' . $statusCode),
				'status_code' => $statusCode,
				'site' => $counter['site']
			];
		}

		/**
		 * Возвращает информацию о счетчике, доступном для добавления
		 * @param \iDomain $domain домен счетчика
		 * @return array
		 *
		 * [
		 *      'id' => 'Идентификатор домена',
		 *      'name' => 'Адрес домена',
		 *      'status' => 'Статус счетчика',
		 *      'code_status' => 'Код статуса счетчика',
		 *      'site' =>  'Хост домена''
		 * ]
		 */
		private function getAvailableCounterInfo(\iDomain $domain) {
			$statusCode = 'CS_AVAILABLE';
			return [
				'id' => $domain->getId(),
				'name' => $domain->getUrl(),
				'active' => false,
				'status' => getLabel('label-counter-status-' . $statusCode),
				'status_code' => $statusCode,
				'site' => $domain->getHost()
			];
		}

		/**
		 * Возвращает конфиг вкладки "Яндекс.Метрика"
		 * @return array
		 */
		private function getCounterListConfig() {
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'stat',
						'type' => 'load',
						'name' => 'yandexMetric'
					],
					[
						'title' => '',
						'module' => 'stat',
						'type' => 'saveField',
						'name' => 'editName'
					]
				],
				'default' => implode('|', [
					'name[350px]',
					'site[350px]',
					'active[140px]',
					'status[350px]'
				]),
				'fields' => [
					[
						'name' => 'name',
						'title' => getLabel('label-counter-name'),
						'type' => 'string',
						'editable' => 'true',
						'filterable' => 'false',
						'sortable' => 'false',
						'show_edit_page_link' => 'false'
					],
					[
						'name' => 'site',
						'title' => getLabel('label-counter-site'),
						'type' => 'string',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false'
					],
					[
						'name' => 'active',
						'title' => getLabel('label-counter-active'),
						'type' => 'bool',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false'
					],
					[
						'name' => 'status',
						'title' => getLabel('label-counter-status'),
						'type' => 'string',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false'
					]
				]
			];
		}

		/**
		 * Возвращает клиента сервиса "Яндекс.Метрика"
		 * @return iClient
		 */
		private function getYandexMetric() {
			return Service::get('YandexMetricClient');
		}

		/**
		 * Возвращает сериализатор для сервиса "Яндекс.Метрика"
		 * @return iSerializer
		 */
		private function getYandexMetricSerializer() {
			return Service::get('YandexMetricSerializer');
		}

		/**
		 * Возвращает файл для кода счетчика
		 * @param int $counterId идентификатор кода счетчика
		 * @return \iUmiFile
		 */
		private function getCounterCodeFile($counterId) {
			$directory = Service::Configuration()
				->includeParam('sys-temp-path');
			$path = sprintf('%s/counter_%s.txt', $directory, $counterId);
			return Service::FileFactory()
				->create($path);
		}

		/**
		 * Возвращает идентификатор домена, соответствующий сайту в "Яндекс.Метрика"
		 * @param string $site сайт в "Яндекс.Метрика"
		 * @return bool|int
		 */
		private function getDomainIdBySite($site) {
			$domainCollection = Service::DomainCollection();

			$domainId = $domainCollection->getDomainId($site);

			if (!$domainId) {
				$domainId = $domainCollection->getDomainIdByUrl($site);
			}

			return $domainId;
		}

		/**
		 * Подготавливаниет данные для списка круговых диаграмм
		 * @param int $id идентификатор группы
		 * @param string $label название группы
		 * @param array $dataSet данные круговых диаграмм @see preparePieChartDataSetList()
		 * @param string $subsection выбранная подгруппа данных
		 * @return array
		 *
		 * [
		 *      '@id' => Идентификатор группы данных
		 *      '@selected' => Выбрана ли группа пользователем
		 *      '@need-to-show' =>  Есть ли данные
		 *      '@label' => Название группы
		 *      'nodes:chart' => @see $dataSetList
		 * ]
		 */
		private function preparePieChartList($id, $label, array $dataSet, $subsection) {
			$isEmptyDataSet = !isset($dataSet[1]);
			return [
				'@id' => $id,
				'@selected' => ($id == $subsection) ? '1' : '0',
				'@need-to-show' => (int) !$isEmptyDataSet,
				'@label' => $label,
				'nodes:chart' => $dataSet
			];
		}

		/**
		 * Подготавливает данные круговой диаграммы
		 * @param array $report статистический отчет
		 *
		 * [
		 *      'data' => [
		 *          [
		 *              'metrics' => [
		 *                  0 => 123
		 *              ]
		 *              'dimensions' => [
		 *                  0 => 'Визит пользователя'
		 *              ]
		 *          ]
		 *          'metrics' => [
		 *              0 => 'ym:s:visits'
		 *          ]
		 *      ]
		 * ]
		 *
		 * @return array
		 *
		 * [
		 *      'nodes:dataset' => [
		 *          [
		 *              '@header' => Заголовок диаграммы
		 *              '@label' => Название значения
		 *              '@value' => Показатель
		 *              '@color' => Цвет сектора диаграммы
		 *          ]
		 *      ],
		 *      '@header' => Заголовок диаграммы
		 * ]
		 */
		private function preparePieChartDataSetList(array $report) {
			if (isEmptyArray($report)) {
				return [];
			}

			$headerList = $this->collectHeaderList($report);
			$rawDataList = isset($report['data']) ? $report['data'] : [];
			$nameColorList = [];
			$dataSetList = [];

			foreach ($headerList as $index => $header) {
				foreach ($rawDataList as $rawData) {
					$dimensions = isset($rawData['dimensions']) ? $rawData['dimensions'] : [];
					$label = isset($dimensions[0]['name']) ? $dimensions[0]['name'] : '';

					if (isset($nameColorList[$label])) {
						$color = $nameColorList[$label];
					} else {
						$color = $nameColorList[$label] = $this->getColor();
					}

					$metrics = isset($rawData['metrics']) ? $rawData['metrics'] : [];
					$dataSetList[$index][] = [
						'@header' => $header,
						'@label' => $label,
						'@value' => $metrics[$index],
						'@color' => $color
					];
				}
			}

			$dataSetNodeList = [];

			foreach ($dataSetList as $dataSet) {

				$dataNodeList = [];

				foreach ($dataSet as $data) {
					$dataNodeList[] = $data;
				}

				$header = isset($dataNodeList[0]['@header']) ? $dataNodeList[0]['@header'] : '';
				$dataSetNodeList[] = [
					'nodes:dataset' => $dataNodeList,
					'@header' => $header
				];
			}

			return $dataSetNodeList;
		}

		/**
		 * Подготавливаниет данные для статистической таблицы
		 * @param int $id идентификатор группы
		 * @param string $label название группы
		 * @param array $dataSet данные статистических таблиц @see prepareTableDataSet()
		 * @param string $subsection выбранная подгруппа данных
		 * @return array
		 *
		 * [
		 *      '@id' => Идентификатор группы данных,
		 *      '@selected' => Выбрана ли группа пользователем,
		 *      '@need-to-show' => Есть ли данные,
		 *      '@label' => Название группы,
		 *      'nodes:row' => $dataSet
		 * ]
		 */
		private function prepareTable($id, $label, array $dataSet, $subsection) {
			$isEmptyDataSet = !isset($dataSet[1]);
			return [
				'@id' => $id,
				'@selected' => ($id == $subsection) ? '1' : '0',
				'@need-to-show' => (int) !$isEmptyDataSet,
				'@label' => $label,
				'nodes:row' => $dataSet
			];
		}

		/**
		 * Подготавливает данные круговой диаграммы
		 * @param array $report статистический отчет
		 *
		 * [
		 *      'data' => [
		 *          [
		 *              'metrics' => [
		 *                  0 => 123
		 *              ]
		 *              'dimensions' => [
		 *                  0 => 'Визит пользователя'
		 *              ]
		 *          ]
		 *          'metrics' => [
		 *              0 => 'ym:s:visits'
		 *          ]
		 *      ]
		 * ]
		 *
		 * @param string $indexName название первой ячейки таблицы
		 * @param string $namePartCount количество частей, из которых состоит первая ячейка строки таблицы
		 * @return array
		 *
		 * [
		 *      'nodes:cell' => [
		 *          0 => [
		 *              '@value' => 'Название показателя'
		 *          ]
		 *          ...
		 *          n => [
		 *              '@value' => 123
		 *          ]
		 *      ]
		 * ]
		 */
		private function prepareTableDataSet(array $report, $indexName, $namePartCount) {
			if (isEmptyArray($report)) {
				return [];
			}

			$headerList = $this->collectHeaderList($report);
			array_unshift($headerList, $indexName);
			$table = [$headerList];
			$rawDataList = isset($report['data']) ? $report['data'] : [];

			foreach ($rawDataList as $rawData) {
				$tableRow = isset($rawData['metrics']) ? $rawData['metrics'] : [];
				$dimension = isset($rawData['dimensions']) ? $rawData['dimensions'] : [];
				$name = $this->getDataSetValueName($dimension, $namePartCount);
				array_unshift($tableRow, $name);
				$table[] = $tableRow;
			}

			$rowNodeList = [];

			foreach ($table as $rowIndex => $row) {
				$cellNodeList = [];

				foreach ($row as $cellIndex => $cellValue) {
					$cellNodeList[] = [
						'@value' => $cellValue
					];
				}

				$rowNodeList[] = [
					'nodes:cell' => $cellNodeList
				];
			}

			return $rowNodeList;
		}

		/**
		 * Возвращает название строки данных для статистической таблицы
		 * @param array $dimension заголовки значений
		 *
		 * [
		 *      [
		 *          'name' => 'Название'
		 *      ],
		 *      [
		 *          'name' => 'Уточнение названия'
		 *      ],
		 *      [
		 *          'name' => 'Уточнение названия'
		 *      ]
		 * ]
		 *
		 * @param int $namePartCount количество частей, из которых состоит первая ячейка строки таблицы
		 * @return string
		 */
		private function getDataSetValueName(array $dimension, $namePartCount) {
			$firstNamePart = isset($dimension[0]['name']) ? $dimension[0]['name'] : '';
			$secondNamePart = null;

			if ($namePartCount > 1) {
				$secondNamePart = isset($dimension[1]['name']) ? $dimension[1]['name'] : null;
			}

			$thirdNamePart = null;

			if ($namePartCount > 2) {
				$thirdNamePart = isset($dimension[2]['name']) ? $dimension[2]['name'] : null;
			}

			if ($secondNamePart !== null && $thirdNamePart !== null) {
				return sprintf('%s: %s (%s)', $firstNamePart, $secondNamePart, $thirdNamePart);
			}

			if ($secondNamePart !== null) {
				return sprintf('%s: %s', $firstNamePart, $secondNamePart);
			}

			return $firstNamePart;
		}

		/**
		 * Собирает список дат из отчета
		 * @param array $report статистический отчет
		 *
		 * [
		 *      'data'  => [
		 *          [
		 *              'dimensions' => [
		 *                  [
		 *                      'id' => "2018-03-26"
		 *                  ]
		 *              ]
		 *          ]
		 *      ]
		 * ]
		 *
		 * @return array
		 *
		 * [
		 *      "2018-03-26"
		 * ]
		 */
		private function collectDateList(array $report) {
			$dateList = [];
			$dataSetList = isset($report['data']) ? $report['data'] : [];

			foreach ($dataSetList as $dataSet) {
				$dimensionList = isset($dataSet['dimensions']) ? $dataSet['dimensions'] : [];

				if (isEmptyArray($dimensionList)) {
					continue;
				}

				$dimension = getFirstValue($dimensionList);

				if (!is_array($dimension) || !isset($dimension['id'])) {
					continue;
				}

				$dateList[] = $dimension['id'];
			}

			return $dateList;
		}

		/**
		 * Собирает список значений из отчета
		 * @param array $report статистический отчет
		 *
		 * [
		 *      'data' => [
		 *          [
		 *              'metrics' => [
		 *                  "1930"
		 *              ]
		 *          ]
		 *      ]
		 * ]
		 *
		 * @return array
		 *
		 * [
		 *      "1930"
		 * ]
		 */
		private function collectValueList(array $report) {
			$valueList = [];
			$dataSetList = isset($report['data']) ? $report['data'] : [];

			foreach ($dataSetList as $dataSet) {
				$metricList = isset($dataSet['metrics']) ? $dataSet['metrics'] : [];
				foreach ($metricList as $index => $value) {
					$valueList[$index][] = $value;
				}
			}

			return $valueList;
		}

		/**
		 * Собирает список заголовков из отчета
		 * @param array $report статистический отчет
		 *
		 * [
		 *      'query' => [
		 *          [
		 *              'metrics' => [
		 *                  'ym:s:users'
		 *              ]
		 *          ]
		 *      ]
		 * ]
		 *
		 * @return array
		 *
		 * [
		 *      'Посетители'
		 * ]
		 */
		private function collectHeaderList(array $report) {
			$headerList = [];
			$metricList = isset($report['query']['metrics']) ? $report['query']['metrics'] : [];

			foreach ($metricList as $header) {
				$key = str_replace(':', '-', $header);
				$headerList[] = getLabel($key);
			}

			return $headerList;
		}

		/**
		 * Подготавливаниет группу данных для линейной диаграммы с привязкой ко времени
		 * @param int $id идентификатор группы
		 * @param string $label название группы
		 * @param array $dataSetList группа данных линейной диаграммы
		 *
		 * [
		 *      [
		 *          'date_list' => ["2018-03-26", "2018-03-19"]
		 *          'value_list' => ["1930", "14860"]
		 *      ]
		 * ]
		 *
		 * @param string $subsection выбранная подгруппа данных
		 * @return array
		 *
		 * [
		 *      '@id' => Идентификатор группы данных
		 *      '@selected' => Выбрана ли группа пользователем
		 *      '@need-to-show' =>  Есть ли данные
		 *      '@label' => Название группы
		 *      '@x-label' => Название оси x
		 *      '@y-label' => Название оси y
		 *      'nodes:dataset' => @see $dataSetList
		 * ]
		 */
		private function prepareHistory($id, $label, array $dataSetList, $subsection) {
			$firstDataSet = isset($dataSetList[0]) ? $dataSetList[0] : [];
			$isEmptyDataSet = (empty($firstDataSet['date_list']) || empty($firstDataSet['value_list']));

			return [
				'@id' => $id,
				'@selected' => ($id == $subsection) ? '1' : '0',
				'@need-to-show' => (int) !$isEmptyDataSet,
				'@label' => $label,
				'@x-label' => getLabel('label-yandex-date'),
				'@y-label' => getLabel('label-yandex-value'),
				'nodes:dataset' => $dataSetList
			];
		}

		/**
		 * Подготавливает список данных для линейной диаграммы с привязкой ко времени
		 * @param array $report статистический отчет
		 *
		 * [
		 *      'data' => [
		 *          [
		 *              'metrics' => [
		 *                  0 => 123
		 *              ]
		 *              'dimensions' => [
		 *                  0 => 'Визит пользователя'
		 *              ]
		 *          ]
		 *          'metrics' => [
		 *              0 => 'ym:s:visits'
		 *          ]
		 *      ]
		 * ]
		 *
		 * @return array @see prepareHistoryDataSet()
		 */
		private function prepareHistoryDataSetList(array $report) {
			if (isEmptyArray($report)) {
				return [];
			}

			$valueList = $this->collectValueList($report);
			$dateList = $this->collectDateList($report);
			$headerList = $this->collectHeaderList($report);
			$historyList = [];

			foreach ($valueList as $index => $valueSubList) {
				$header = $headerList[$index];
				$historyList[] = $this->prepareHistoryDataSet($header, $dateList, $valueSubList);
			}

			return $historyList;
		}

		/**
		 * Подготавливает данные для линии диаграммы с привязкой ко времени
		 * @param string $header заголовок линии
		 * @param array $dateList даты (для оси x)
		 *
		 * [
		 *      "2018-03-26",
		 *      "2018-03-19"
		 * ]
		 *
		 * @param array $valueList значения (для оси y)
		 *
		 * [
		 *      "1930",
		 *      "14860"
		 * ]
		 *
		 * @return array
		 *
		 * [
		 *      '@label' => @see $header,
		 *      '@color' => 'rgb(153, 102, 255)',
		 *      '@date_list' => ["2018-03-26", "2018-03-19"],
		 *      '@value_list' => ["1930", "14860"]
		 * ]
		 */
		private function prepareHistoryDataSet($header, array $dateList, array $valueList) {
			return [
				'@label' => $header,
				'@color' => $this->getColor(),
				'date_list' => '["' . implode('", "', $dateList) . '"]',
				'value_list' => '["' . implode('", "', $valueList) . '"]',
			];
		}

		/**
		 * Возвращает цвет линии или сектора диаграммы.
		 * Используется только для одной группы данных графиков за раз.
		 * @return string
		 */
		private function getColor() {
			static $colorList = [
				'rgb(153, 102, 255)',
				'rgb(224, 42, 22)',
				'rgb(75, 192, 192)',
				'rgb(54, 162, 235)',
				'rgb(255, 205, 86)',
				'rgb(255, 99, 132)',
				'rgb(78, 160, 81)',
				'rgb(13, 75, 227)',
				'rgb(227, 117, 13)',
				'rgb(68, 89, 29)',
			];

			if (empty($colorList)) {
				return 'rgb(255, 99, 132)';
			}

			return array_shift($colorList);
		}
	}
