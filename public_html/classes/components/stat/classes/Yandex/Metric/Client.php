<?php

	namespace UmiCms\Classes\Components\Stat\Yandex\Metric;

	use Guzzle\Http\Message\RequestInterface;
	use UmiCms\Classes\Components\Stat\iRegistry;
	use UmiCms\Classes\System\Entities\Date\iFactory as DateFactory;
	use UmiCms\Classes\System\Utils\Api\Http\Exception\BadRequest;
	use UmiCms\Classes\System\Utils\Api\Http\Json\Client as JsonClient;
	use UmiCms\Classes\System\Utils\Api\Http\Json\Yandex\Exception\BadToken;
	use UmiCms\System\Cache\iEngineFactory;

	/**
	 * Класс клиента API "Яндекс.Метрика".
	 * @see iMetric, в нем документация.
	 * @package UmiCms\Classes\Components\Stat\Yandex\Metric
	 */
	class Client extends JsonClient implements iClient {

		/** @var string $authToken авторизационный токен */
		private $authToken;

		/** @var DateFactory $dateFactory фабрика дат */
		private $dateFactory;

		/** @var \iCacheEngine $cacheStorage хранилище кеша */
		private $cacheStorage;

		/** @const string SERVICE_HOST адрес сервиса */
		const SERVICE_HOST = 'https://api-metrika.yandex.ru/';

		/** @const string SERVICE_VERSION версия API */
		const SERVICE_VERSION = 'v1';

		/** @const string DEFAULT_ERROR_MESSAGE сообщение об ошибке по умолчанию */
		const DEFAULT_ERROR_MESSAGE = 'Yandex.Metric client error';

		/** @const int CACHE_LIVE_TIME время хранения кеша в секундах (2 недели) */
		const CACHE_LIVE_TIME = 1209600;

		/** @const int ROW_LIMIT_FOR_TOP ограничение на количество строк в отчете-топе */
		const ROW_LIMIT_FOR_TOP = 19;

		/** @inheritdoc */
		public function __construct(iRegistry $registry, DateFactory $dateFactory, iEngineFactory $engineFactory) {
			$this->authToken = $registry->getYandexToken();
			$this->dateFactory = $dateFactory;
			$this->cacheStorage = $engineFactory->create();
			$this->initHttpClient();
		}

		/** @inheritdoc */
		public function getCounterList($offset = 0, $limit = 100) {
			$offset++;
			$request = $this->createGetRequest(
				['management', self::SERVICE_VERSION, 'counters'], ['offset' => $offset, 'per_page' => $limit]
			);
			$response = $this->getResponse($request);
			return $response['counters'];
		}

		/** @inheritdoc */
		public function addCounter(\stdClass $counter) {
			$request = $this->createPostRequest($counter, ['management', self::SERVICE_VERSION, 'counters']);
			$response = $this->getResponse($request);
			return $response['counter']['id'];
		}

		/** @inheritdoc */
		public function getCounter($id) {
			$request = $this->createGetRequest(['management', self::SERVICE_VERSION, 'counter', $id]);
			$response = $this->getResponse($request);
			return $response['counter'];
		}

		/** @inheritdoc */
		public function editCounter($id, \stdClass $counter) {
			$request = $this->createPutRequest($counter, ['management', self::SERVICE_VERSION, 'counter', $id]);
			$response = $this->getResponse($request);
			return is_array($response) && arrayValueContainsNotEmptyArray($response, 'counter');
		}

		/** @inheritdoc */
		public function deleteCounter($id) {
			$request = $this->createDeleteRequest(['management', self::SERVICE_VERSION, 'counter', $id]);
			$response = $this->getResponse($request);
			return isset($response['success']) ? $response['success'] : false;
		}

		/** @inheritdoc */
		public function getSourcesSummary($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			$limit = self::ROW_LIMIT_FOR_TOP;
			$report = $this->getTableReport($counterId, 'sources_summary', $from, $to, $limit);
			return $this->appendOtherRow($report, $limit);
		}

		/** @inheritdoc */
		public function getSourcesSearchEngines($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			$limit = self::ROW_LIMIT_FOR_TOP;
			$report = $this->getTableReport($counterId, 'search_engines', $from, $to, $limit);
			return $this->appendOtherRow($report, $limit);
		}

		/** @inheritdoc */
		public function getSourcesSearchPhrases($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			$limit = self::ROW_LIMIT_FOR_TOP;
			$report = $this->getTableReport($counterId, 'sources_search_phrases', $from, $to, $limit);
			return $this->appendOtherRow($report, $limit);
		}

		/** @inheritdoc */
		public function getSourcesSites($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			$limit = self::ROW_LIMIT_FOR_TOP;
			$report = $this->getTableReport($counterId, 'sources_sites', $from, $to, $limit);
			return $this->appendOtherRow($report, $limit);
		}

		/** @inheritdoc */
		public function getSourcesSocials($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'sources_social', $from, $to);
		}

		/** @inheritdoc */
		public function getTraffic($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'traffic', $from, $to);
		}

		/** @inheritdoc */
		public function getConversion($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'conversion', $from, $to);
		}

		/** @inheritdoc */
		public function getHourlyTraffic($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'hourly', $from, $to);
		}

		/** @inheritdoc */
		public function getPopularContent($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			$report = $this->getTableReport($counterId, 'popular', $from, $to);
			return $this->clearContentDimensions($report);
		}

		/** @inheritdoc */
		public function getContentEntrance($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			$report = $this->getTableReport($counterId, 'content_entrance', $from, $to);
			return $this->clearContentDimensions($report);
		}

		/** @inheritdoc */
		public function getContentExit($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			$report = $this->getTableReport($counterId, 'content_exit', $from, $to);
			return $this->clearContentDimensions($report);
		}

		/** @inheritdoc */
		public function getTitles($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'titles', $from, $to);
		}

		/** @inheritdoc */
		public function getUrlParams($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'url_params', $from, $to);
		}

		/** @inheritdoc */
		public function getContentUserParams($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'content_user_params', $from, $to);
		}

		/** @inheritdoc */
		public function getContentVisitParams($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'content_visit_params', $from, $to);
		}

		/** @inheritdoc */
		public function getResolutionMap($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			$limit = self::ROW_LIMIT_FOR_TOP;
			$report = $this->getTableReport($counterId, 'resolution_map', $from, $to, $limit);
			return $this->appendOtherRow($report, $limit);
		}

		/** @inheritdoc */
		public function getBrowsers($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			$limit = self::ROW_LIMIT_FOR_TOP;
			$report = $this->getTableReport($counterId, 'tech_browsers', $from, $to, $limit);
			return $this->appendOtherRow($report, $limit);
		}

		/** @inheritdoc */
		public function getCookies($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'tech_cookies', $from, $to);
		}

		/** @inheritdoc */
		public function getDisplay($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			$limit = self::ROW_LIMIT_FOR_TOP;
			$report = $this->getTableReport($counterId, 'tech_display', $from, $to, $limit);
			return $this->appendOtherRow($report, $limit);
		}

		/** @inheritdoc */
		public function getDisplayGroups($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'tech_display_groups', $from, $to);
		}

		/** @inheritdoc */
		public function getFlash($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			$limit = self::ROW_LIMIT_FOR_TOP;
			$report = $this->getTableReport($counterId, 'tech_flash', $from, $to, $limit);
			return $this->appendOtherRow($report, $limit);
		}

		/** @inheritdoc */
		public function getJava($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'tech_java', $from, $to);
		}

		/** @inheritdoc */
		public function getJavaScript($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'tech_java_script', $from, $to);
		}

		/** @inheritdoc */
		public function getPlatforms($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			$limit = self::ROW_LIMIT_FOR_TOP;
			$report = $this->getTableReport($counterId, 'tech_platforms', $from, $to, $limit);
			return $this->appendOtherRow($report, $limit);
		}

		/** @inheritdoc */
		public function getSilverlight($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			$limit = self::ROW_LIMIT_FOR_TOP;
			$report = $this->getTableReport($counterId, 'tech_silverlight', $from, $to, $limit);
			return $this->appendOtherRow($report, $limit);
		}

		/** @inheritdoc */
		public function getDevices($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			$limit = self::ROW_LIMIT_FOR_TOP;
			$report = $this->getTableReport($counterId, 'tech_devices', $from, $to, $limit);
			return $this->appendOtherRow($report, $limit);
		}

		/** @inheritdoc */
		public function getCountries($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			$limit = self::ROW_LIMIT_FOR_TOP;
			$report = $this->getTableReport($counterId, 'geo_country', $from, $to, $limit);
			return $this->appendOtherRow($report, $limit);
		}

		/** @inheritdoc */
		public function getInterests($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'interests', $from, $to);
		}

		/** @inheritdoc */
		public function getAge($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'age', $from, $to);
		}

		/** @inheritdoc */
		public function getGender($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'gender', $from, $to);
		}

		/** @inheritdoc */
		public function getLoyaltyNewness($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'loyalty_newness', $from, $to);
		}

		/** @inheritdoc */
		public function getLoyaltyPeriod($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'loyalty_period', $from, $to);
		}

		/** @inheritdoc */
		public function getLoyaltyRecency($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'loyalty_recency', $from, $to);
		}

		/** @inheritdoc */
		public function getLoyaltyVisits($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'loyalty_visits', $from, $to);
		}

		/** @inheritdoc */
		public function getDeepnessDepth($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'deepness_depth', $from, $to);
		}

		/** @inheritdoc */
		public function getDeepnessTime($counterId, \iUmiDate $from = null, \iUmiDate $to = null) {
			return $this->getTableReport($counterId, 'deepness_time', $from, $to);
		}

		/** @inheritdoc */
		protected function getDefaultHeaders() {
			$headerList = parent::getDefaultHeaders();
			return array_merge($headerList, [
				'Authorization' => 'OAuth ' . $this->getAuthToken()
			]);
		}

		/** @inheritdoc */
		protected function getServiceUrl() {
			return $this->buildPath([
				self::SERVICE_HOST
			]);
		}

		/** @inheritdoc */
		protected function getResponse(RequestInterface $request) {
			$body = parent::getResponse($request);

			if (isset($body['errors'])) {

				if (in_array($body['code'], [401, 403])) {
					throw new BadToken(self::DEFAULT_ERROR_MESSAGE, $body['code']);
				}

				throw new BadRequest($body['message'], $body['code']);
			}

			return $body;
		}

		/**
		 * Убирает из отчета по контенту (страницам) части адресов
		 * @param array $report отчет
		 *
		 * [
		 *      'data' => [
		 *          [
		 *              'dimensions' => [
		 *                  0 => 'http://site.com'
		 *                  1 => 'http://site.com/foo/'
		 *                  2 => 'http://site.com/foo/bar/'
		 *                  3 =>  null
		 *                  4 => 'http://site.com/foo/bar/'
		 *              ]
		 *          ]
		 *      ]
		 * ]
		 *
		 * @return array
		 *
		 * [
		 *      'data' => [
		 *          [
		 *              'dimensions' => [
		 *                  0 => 'http://site.com/foo/bar/'
		 *              ]
		 *          ]
		 *      ]
		 * ]
		 *
		 */
		private function clearContentDimensions(array $report) {
			$reportData = isset($report['data']) ? $report['data'] : [];

			foreach ($reportData as $index => $row) {
				$dimensions = isset($row['dimensions']) ? $row['dimensions'] : [];

				unset($dimensions[0]);
				unset($dimensions[1]);
				unset($dimensions[2]);
				unset($dimensions[3]);
				$dimensions[0] = $dimensions[4];
				unset($dimensions[4]);

				$row['dimensions'] = $dimensions;
				$reportData[$index] = $row;
			}

			$report['data'] = $reportData;
			return $report;
		}

		/**
		 * Добавляет в отчет строку "Прочие" с соответствующими вычислениями
		 * @param array $report отчет
		 * @param int $limit количество строк в отчете
		 * @return array
		 */
		private function appendOtherRow(array $report, $limit) {
			$reportData = isset($report['data']) ? $report['data'] : [];

			$visitCount = 0;
			$userCount = 0;

			foreach ($reportData as $row) {
				$visitCount += isset($row['metrics'][0]) ? $row['metrics'][0] : 0;
				$userCount += isset($row['metrics'][1]) ? $row['metrics'][1] : 0;
			}

			$totalVisitCount = isset($report['totals'][0]) ? $report['totals'][0] : 0;
			$totalUserCount = isset($report['totals'][1]) ? $report['totals'][1] : 0;
			$averageBounceRate = isset($report['totals'][2]) ? $report['totals'][2] : 0;
			$averagePageDepth = isset($report['totals'][3]) ? $report['totals'][3] : 0;
			$averageVisitDuration = isset($report['totals'][4]) ? $report['totals'][4] : 0;
			$sourceCount = isset($report['total_rows']) ? $report['total_rows'] : 0;

			$report['data'][$limit] = [
				'dimensions' => [
					[
						'name' => sprintf(getLabel('label-other-format'), $sourceCount - $limit)
					]
				],
				'metrics' => [
					$totalVisitCount - $visitCount,
					$totalUserCount - $userCount,
					$averageBounceRate,
					$averagePageDepth,
					$averageVisitDuration
				]
			];

			return $report;
		}

		/**
		 * Возвращает табличный отчет заданного типа по данным счетчика
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/data-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param string $type тип отчета
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @param int $limit ограничение на количество элементов выдачи
		 * @return array
		 */
		private function getTableReport($counterId, $type, \iUmiDate $from = null, \iUmiDate $to = null, $limit = 100) {
			$dateFactory = $this->getDateFactory();
			$from = $from ?: $dateFactory->createByDateString('-31 day');
			$to = $to ?: $dateFactory->createByDateString('-1 day');
			$current = $dateFactory->create()
				->getDateTimeStamp();
			$isNeedCache = $current > $to->getDateTimeStamp() && $current >= $from->getDateTimeStamp();
			$from = $from->getFormattedDate('Y-m-d');
			$to = $to->getFormattedDate('Y-m-d');
			$cacheKey = sprintf('%s-%s-%s-%s', $counterId, $type, $from, $to);
			$cacheStorage = $this->getCacheStorage();

			if ($isNeedCache) {
				$cache = $cacheStorage->loadRawData($cacheKey);

				if (is_array($cache) || !empty($cache)) {
					return $cache;
				}
			}

			$request = $this->createGetRequest(
				['stat', self::SERVICE_VERSION, 'data'],
				[
					'id' => $counterId,
					'preset' => $type,
					'date1' => $from,
					'date2' => $to,
					'limit' => $limit
				]
			);

			$response = $this->getResponse($request);

			if ($isNeedCache) {
				$cacheStorage->saveRawData($cacheKey, $response, self::CACHE_LIVE_TIME);
			}

			return $response;
		}

		/**
		 * Возвращает авторизационный токен
		 * @return string
		 */
		private function getAuthToken() {
			return $this->authToken;
		}

		/**
		 * Возвращает фабрику дат
		 * @return DateFactory
		 */
		private function getDateFactory() {
			return $this->dateFactory;
		}

		/**
		 * Возвращает хранилище кеша
		 * @return \iCacheEngine
		 */
		private function getCacheStorage() {
			return $this->cacheStorage;
		}
	}
