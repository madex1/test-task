<?php

	namespace UmiCms\Classes\Components\Seo\Yandex\WebMaster;

	use Guzzle\Http\Message\RequestInterface;
	use UmiCms\Classes\Components\Seo\iRegistry;
	use UmiCms\Classes\System\Utils\Api\Http\Exception\BadRequest;
	use UmiCms\Classes\System\Utils\Api\Http\Exception\BadResponse;
	use UmiCms\Classes\System\Utils\Api\Http\Json\Client as JsonClient;
	use UmiCms\Classes\System\Utils\Api\Http\Json\Yandex\Exception\BadToken;

	/**
	 * Класс клиента API Яндекс.Вебмастер.
	 * @see iClient, в нем документация.
	 * @package UmiCms\Classes\Components\Seo\Yandex\WebMaster
	 */
	class Client extends JsonClient implements iClient {

		/** @var  @var string $authToken авторизационный токен */
		private $authToken;

		/** @var mixed @var string $userId идентификатор пользователя */
		private $userId;

		/** @const string SERVICE_HOST адрес сервиса */
		const SERVICE_HOST = 'https://api.webmaster.yandex.net';

		/** @const string SERVICE_VERSION версия API */
		const SERVICE_VERSION = 'v3';

		/** @const string DEFAULT_ERROR_MESSAGE сообщение об ошибке по умолчанию */
		const DEFAULT_ERROR_MESSAGE = 'Yandex.WebMaster client error';

		/** @inheritdoc */
		public function __construct(iRegistry $registry) {
			$this->authToken = $registry->getYandexToken();
			$this->initHttpClient();
		}

		/** @inheritdoc */
		public function getSiteList() {
			$request = $this->createGetRequest();
			$response = $this->getResponse($request);

			if (!isset($response['hosts'])) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 2);
			}

			return $response['hosts'];
		}

		/** @inheritdoc */
		public function addSite($uri) {
			$request = $this->createPostRequest(
				['host_url' => $uri]
			);

			$response = $this->getResponse($request);

			if (!isset($response['host_id'])) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 3);
			}

			return $response['host_id'];
		}

		/** @inheritdoc */
		public function deleteSite($siteId) {
			$request = $this->createDeleteRequest(
				[$siteId]
			);

			$response = $this->getResponse($request);
			return empty($response);
		}

		/** @inheritdoc */
		public function getIndexationInfo($siteId) {
			$request = $this->createGetRequest(
				[$siteId]
			);

			$response = $this->getResponse($request);

			if (empty($response)) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 4);
			}

			return $response;
		}

		/** @inheritdoc */
		public function getStatistic($siteId) {
			$request = $this->createGetRequest(
				[$siteId, 'summary']
			);

			$response = $this->getResponse($request);

			if (empty($response)) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 5);
			}

			return $response;
		}

		/** @inheritdoc */
		public function getVerificationState($siteId) {
			$request = $this->createGetRequest(
				[$siteId, 'verification']
			);

			$response = $this->getResponse($request);

			if (empty($response)) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 6);
			}

			return $response;
		}

		/** @inheritdoc */
		public function verifySite($siteId, $type = self::VERIFICATION_TYPE_HTML_FILE) {
			if (!in_array($type, [
				self::VERIFICATION_TYPE_DNS,
				self::VERIFICATION_TYPE_HTML_FILE,
				self::VERIFICATION_TYPE_META_TAG,
				self::VERIFICATION_TYPE_META_TAG,
			])) {
				throw new BadRequest(self::DEFAULT_ERROR_MESSAGE, 7);
			}

			$request = $this->createPostRequest(
				[], [$siteId, 'verification'], ['verification_type' => $type]
			);

			$response = $this->getResponse($request);

			if (empty($response)) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 8);
			}

			return $response;
		}

		/** @inheritdoc */
		public function getOwnerList($siteId) {
			$request = $this->createGetRequest(
				[$siteId, 'owners']
			);

			$response = $this->getResponse($request);

			if (!isset($response['users'])) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 9);
			}

			return $response['users'];
		}

		/** @inheritdoc */
		public function getOriginalTextList($siteId, $offset = 0, $limit = 25) {
			$request = $this->createGetRequest(
				[$siteId, 'original-texts'], ['offset' => $offset, 'limit' => $limit]
			);

			$response = $this->getResponse($request);

			if (empty($response)) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 10);
			}

			return $response;
		}

		/** @inheritdoc */
		public function addOriginalText($siteId, $text) {
			$request = $this->createPostRequest(
				['content' => $text], [$siteId, 'original-texts']
			);

			$response = $this->getResponse($request);

			if (!isset($response['text_id'])) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 11);
			}

			return $response['text_id'];
		}

		/** @inheritdoc */
		public function deleteOriginalText($siteId, $textId) {
			$request = $this->createDeleteRequest(
				[$siteId, 'original-texts', $textId]
			);

			$response = $this->getResponse($request);
			return empty($response);
		}

		/** @inheritdoc */
		public function getFoundSiteMapList($siteId) {
			$request = $this->createGetRequest(
				[$siteId, 'sitemaps']
			);

			$response = $this->getResponse($request);

			if (!isset($response['sitemaps'])) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 12);
			}

			return $response['sitemaps'];
		}

		/** @inheritdoc */
		public function getAddedSiteMapList($siteId) {
			$request = $this->createGetRequest(
				[$siteId, 'user-added-sitemaps']
			);

			$response = $this->getResponse($request);

			if (!isset($response['sitemaps'])) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 13);
			}

			return $response['sitemaps'];
		}

		/** @inheritdoc */
		public function getSiteMapStat($siteId, $mapId) {
			$request = $this->createGetRequest(
				[$siteId, 'sitemaps'], ['sitemap_id' => $mapId]
			);

			$response = $this->getResponse($request);

			if (empty($response)) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 14);
			}

			return $response;
		}

		/** @inheritdoc */
		public function addSiteMap($siteId, $uri) {
			$request = $this->createPostRequest(
				['url' => $uri], [$siteId, 'user-added-sitemaps']
			);

			$response = $this->getResponse($request);

			if (!isset($response['sitemap_id'])) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 15);
			}

			return $response['sitemap_id'];
		}

		/** @inheritdoc */
		public function deleteSiteMap($siteId, $mapId) {
			$request = $this->createDeleteRequest(
				[$siteId, 'user-added-sitemaps', $mapId]
			);

			$response = $this->getResponse($request);
			return empty($response);
		}

		/** @inheritdoc */
		public function getIndexingHistory(
			$siteId,
			$indicator = self::INDEXING_INDICATOR_SEARCHABLE,
			$from = null,
			$to = null
		) {
			if (!$this->isValidIndexingIndicator($indicator)) {
				throw new BadRequest(self::DEFAULT_ERROR_MESSAGE, 16);
			}

			$from = $this->formatDate($from ?: strtotime('-2 month'));
			$to = $this->formatDate($to ?: time());

			$request = $this->createGetRequest(
				[$siteId, 'indexing-history'],
				['indexing_indicator' => $indicator, 'date_from' => $from, 'date_to' => $to]
			);

			$response = $this->getResponse($request);

			if (!isset($response['indicators'][$indicator])) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 17);
			}

			return $response['indicators'][$indicator];
		}

		/** @inheritdoc */
		public function getTicHistory($siteId, $from = null, $to = null) {
			$from = $this->formatDate($from ?: strtotime('-2 month'));
			$to = $this->formatDate($to ?: time());

			$request = $this->createGetRequest(
				[$siteId, 'tic-history'], ['date_from' => $from, 'date_to' => $to]
			);

			$response = $this->getResponse($request);

			if (!isset($response['points'])) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 18);
			}

			return $response['points'];
		}

		/** @inheritdoc */
		public function getPopularQueryList(
			$siteId,
			$order = self::QUERY_ORDER_FIELD_TOTAL_SHOWS,
			$indicator = self::QUERY_INDICATOR_TOTAL_SHOWS
		) {
			if (!$this->isValidQueryOrder($order)) {
				throw new BadRequest(self::DEFAULT_ERROR_MESSAGE, 19);
			}

			if (!$this->isValidQueryIndicator($indicator)) {
				throw new BadRequest(self::DEFAULT_ERROR_MESSAGE, 20);
			}

			$request = $this->createGetRequest(
				[$siteId, 'search-queries', 'popular'], ['order_by' => $order, 'query_indicator' => $indicator]
			);

			$response = $this->getResponse($request);

			if (empty($response)) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 21);
			}

			return $response;
		}

		/** @inheritdoc */
		public function getExternalLinkList($siteId, $offset = 0, $limit = 25) {
			$request = $this->createGetRequest(
				[$siteId, 'links', 'external', 'samples'], ['offset' => $offset, 'limit' => $limit]
			);

			$response = $this->getResponse($request);

			if (empty($response)) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 22);
			}

			return $response;
		}

		/** @inheritdoc */
		public function getExternalLinksCountHistory($siteId) {
			$indicator = self::EXTERNAL_LINKS_INDICATOR_LINKS_TOTAL_COUNT;

			$request = $this->createGetRequest(
				[$siteId, 'links', 'external', 'history'], ['indicator' => $indicator]
			);

			$response = $this->getResponse($request);

			if (!isset($response['indicators'][$indicator])) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 24);
			}

			return $response['indicators'][$indicator];
		}

		/** @inheritdoc */
		protected function getResponse(RequestInterface $request) {
			$body = parent::getResponse($request);

			if (isset($body['error_code'])) {
				throw new BadRequest($body['error_code'], 26);
			}

			return $body;
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
				self::SERVICE_HOST,
				self::SERVICE_VERSION
			]);
		}

		/** @inheritdoc */
		protected function getPrefix() {
			return $this->buildPath(
				['user', $this->getUserId(), 'hosts']
			);
		}

		/**
		 * Запрашивает идентификатор пользователя сервиса
		 * @return mixed
		 * @throws BadResponse
		 */
		private function requestUserId() {
			$request = $this->getHttpClient()->get(
				'user',
				$this->getDefaultHeaders(),
				$this->getMuteExceptionOption()
			);

			$response = $this->getResponse($request);

			if (!isset($response['user_id'])) {
				throw new BadResponse(self::DEFAULT_ERROR_MESSAGE, 25);
			}

			return $response['user_id'];
		}

		/**
		 * Возвращает идентификатор пользователя сервиса
		 * @return int
		 * @throws BadToken
		 */
		private function getUserId() {
			if (empty($this->userId)) {
				try {
					$this->userId = $this->requestUserId();
				} catch (BadRequest $exception) {
					throw new BadToken(self::DEFAULT_ERROR_MESSAGE, 1);
				}
			}

			return $this->userId;
		}

		/**
		 * Возвращает авторизационный токен
		 * @return string
		 */
		private function getAuthToken() {
			return $this->authToken;
		}

		/**
		 * Форматирует дату
		 * @param int $timestamp
		 * @return string
		 */
		private function formatDate($timestamp) {
			return date(DATE_ATOM, $timestamp);
		}

		/**
		 * Определяет корректен ли индикатор индексирования
		 * @param string $indicator проверяемый индикатор
		 * @return bool
		 */
		private function isValidIndexingIndicator($indicator) {
			return in_array($indicator, [
				self::INDEXING_INDICATOR_SEARCHABLE,
				self::INDEXING_INDICATOR_DOWNLOADED,
				self::INDEXING_INDICATOR_DOWNLOADED_2XX,
				self::INDEXING_INDICATOR_DOWNLOADED_3XX,
				self::INDEXING_INDICATOR_DOWNLOADED_4XX,
				self::INDEXING_INDICATOR_DOWNLOADED_5XX,
				self::INDEXING_INDICATOR_FAILED_TO_DOWNLOAD,
				self::INDEXING_INDICATOR_EXCLUDED,
				self::INDEXING_INDICATOR_EXCLUDED_DISALLOWED_BY_USER,
				self::INDEXING_INDICATOR_EXCLUDED_NOT_SUPPORTED,
				self::INDEXING_INDICATOR_EXCLUDED_SITE_ERROR,
			]);
		}

		/**
		 * Определяет корректна ли сортировка запросов
		 * @param string $order проверяемая сортировка
		 * @return bool
		 */
		private function isValidQueryOrder($order) {
			return in_array($order, [
				self::QUERY_ORDER_FIELD_TOTAL_SHOWS,
				self::QUERY_ORDER_FIELD_TOTAL_CLICKS
			]);
		}

		/**
		 * Определяет корректен ли индикатор запросов
		 * @param string $indicator проверяемый индикатор
		 * @return bool
		 */
		private function isValidQueryIndicator($indicator) {
			return in_array($indicator, [
				self::QUERY_INDICATOR_TOTAL_CLICKS,
				self::QUERY_ORDER_FIELD_TOTAL_SHOWS,
				self::QUERY_INDICATOR_AVG_SHOW_POSITION,
				self::QUERY_INDICATOR_AVG_CLICK_POSITION
			]);
		}
	}
