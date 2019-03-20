<?php

	namespace UmiCms\Classes\Components\Seo\Yandex\WebMaster;

	use UmiCms\Classes\Components\Seo\iRegistry;
	use UmiCms\Classes\System\Utils\Api\Http\Exception\BadRequest;
	use UmiCms\Classes\System\Utils\Api\Http\Exception\BadResponse;

	/**
	 * Интерфейс клиента API Яндекс.Вебмастер
	 * @link https://tech.yandex.ru/webmaster/doc/dg/concepts/getting-started-docpage/
	 * @package UmiCms\Classes\Components\Seo\Yandex\WebMaster
	 */
	interface iClient {

		/** @const string ERROR_CODE_HOST_NOT_INDEXED код ошибки: "Сайт еще не проиндексирован" */
		const ERROR_CODE_HOST_NOT_INDEXED = 'HOST_NOT_INDEXED';

		/** @const string ERROR_CODE_HOST_NOT_LOADED код ошибки: "Данные о сайте еще не загружены в Яндекс.Вебмастер." */
		const ERROR_CODE_HOST_NOT_LOADED = 'HOST_NOT_LOADED';

		/** @const string ERROR_CODE_HOST_NOT_LOADED код ошибки: "Сайт не добавлен в список или права не подтверждены. */
		const ERROR_CODE_HOST_NOT_VERIFIED = 'HOST_NOT_VERIFIED';

		/**
		 * Конструктор
		 * @param iRegistry $registry реестр модуля
		 */
		public function __construct(iRegistry $registry);

		/**
		 * Возвращает список сайтов со сводной информацией по каждому из них
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/hosts-docpage/
		 * @return array
		 * @throws BadResponse
		 */
		public function getSiteList();

		/**
		 * Добавляет сайт в список и возвращает его идентификатор
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/hosts-add-site-docpage/
		 * @param string $uri адрес добавляемого сайта
		 * @return string
		 * @throws BadResponse
		 */
		public function addSite($uri);

		/**
		 * Удаляет сайт из списка
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/hosts-delete-docpage/
		 * @param string $siteId идентификатор сайта
		 * @return bool
		 */
		public function deleteSite($siteId);

		/**
		 * Возвращет информацию о текущем состоянии индексирования сайта
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/hosts-id-docpage/
		 * @param string $siteId идентификатор сайта
		 * @return array
		 * @throws BadResponse
		 */
		public function getIndexationInfo($siteId);

		/**
		 * Возвращает сводную информацию о сайте
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/host-id-summary-docpage/
		 * @param string $siteId идентификатор сайта
		 * @return array
		 * @throws BadResponse
		 */
		public function getStatistic($siteId);

		/**
		 * Возвращает подробную информацию о текущем состоянии подтверждения сайта
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/host-verification-get-docpage/
		 * @param string $siteId идентификатор сайта
		 * @return array
		 * @throws BadResponse
		 */
		public function getVerificationState($siteId);

		/** @const string VERIFICATION_TYPE_HTML_FILE способ подтверждения: размещение HTML-файла в корневом каталоге */
		const VERIFICATION_TYPE_HTML_FILE = 'HTML_FILE';

		/** @const string VERIFICATION_TYPE_DNS способ подтверждения: добавление DNS-записи */
		const VERIFICATION_TYPE_DNS = 'DNS';

		/** @const string VERIFICATION_TYPE_META_TAG способ подтверждения: добавление мета-тега в заголовок */
		const VERIFICATION_TYPE_META_TAG = 'META_TAG';

		/** @const string VERIFICATION_TYPE_WHOIS способ подтверждения: сверка данных с информацией с WHOIS */
		const VERIFICATION_TYPE_WHOIS = 'WHOIS';

		/**
		 * Запускает процедуру подтверждения прав на управление сайтом
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/host-verification-post-docpage/
		 * @param string $siteId идентификатор сайта
		 * @param string $type способ подтверждения прав:
		 * https://tech.yandex.ru/webmaster/doc/dg/reference/host-verification-post-docpage/#verification_type
		 * @return array
		 * @throws BadRequest
		 * @throws BadResponse
		 */
		public function verifySite($siteId, $type = self::VERIFICATION_TYPE_HTML_FILE);

		/**
		 * Возвращает список пользователей, которые подтвердили права на управление сайтом
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/host-owners-get-docpage/
		 * @param string $siteId идентификатор сайта
		 * @return array
		 * @throws BadResponse
		 */
		public function getOwnerList($siteId);

		/**
		 * Возвращает список оригинальных текстов сайта
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/host-original-texts-get-docpage/
		 * @param string $siteId идентификатор сайта
		 * @param int $offset смещение списка
		 * @param int $limit ограничение на длину списка
		 * @return array
		 * @throws BadResponse
		 */
		public function getOriginalTextList($siteId, $offset = 0, $limit = 25);

		/**
		 * Добавляет оригинальный текст сайта и возвращает его идентификатор
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/host-original-texts-post-docpage/
		 * @param string $siteId идентификатор сайта
		 * @param string $text текст (от 500 до 32000 символов)
		 * @return string
		 * @throws BadResponse
		 */
		public function addOriginalText($siteId, $text);

		/**
		 * Удаляет оригинальный текст сайта
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/host-original-texts-delete-docpage/
		 * @param string $siteId идентификатор сайта
		 * @param string $textId идентификатор текста
		 * @return bool
		 */
		public function deleteOriginalText($siteId, $textId);

		/**
		 * Возвращает список найденных карт сайта
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/host-sitemaps-get-docpage/
		 * @param string $siteId идентификатор сайта
		 * @return array
		 * @throws BadResponse
		 */
		public function getFoundSiteMapList($siteId);

		/**
		 * Возвращает список добавленных карт сайта
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/host-user-added-sitemaps-get-docpage/
		 * @param string $siteId идентификатор сайта
		 * @return array
		 * @throws BadResponse
		 */
		public function getAddedSiteMapList($siteId);

		/**
		 * Возвращает подробную информацию о карте сайта
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/host-sitemaps-sitemap-id-get-docpage/
		 * @param string $siteId идентификатор сайта
		 * @param string $mapId идентификатор карты сайта
		 * @return array
		 * @throws BadResponse
		 */
		public function getSiteMapStat($siteId, $mapId);

		/**
		 * Добавляет карту сайта и возвращает ее идентификатор
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/host-user-added-sitemaps-post-docpage/
		 * @param string $siteId идентификатор сайта
		 * @param string $uri адрес карты сайта
		 * @return string
		 * @throws BadResponse
		 */
		public function addSiteMap($siteId, $uri);

		/**
		 * Удаляет карту сайта
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/host-user-added-sitemaps-sitemap-id-delete-docpage/
		 * @param string $siteId идентификатор сайта
		 * @param string $mapId идентификатор карты сайта
		 * @return bool
		 * @throws BadResponse
		 */
		public function deleteSiteMap($siteId, $mapId);

		/** @const string INDEXING_INDICATOR_SEARCHABLE индикатор индексирования: страницы в поиске */
		const INDEXING_INDICATOR_SEARCHABLE = 'SEARCHABLE';

		/** @const string INDEXING_INDICATOR_DOWNLOADED индикатор индексирования: загруженные страницы */
		const INDEXING_INDICATOR_DOWNLOADED = 'DOWNLOADED';

		/** @const string INDEXING_INDICATOR_DOWNLOADED_2XX индикатор индексирования: страницы, загруженные с кодом 2XX */
		const INDEXING_INDICATOR_DOWNLOADED_2XX = 'DOWNLOADED_2XX';

		/** @const string INDEXING_INDICATOR_DOWNLOADED_3XX индикатор индексирования: страницы, загруженные с кодом 3XX */
		const INDEXING_INDICATOR_DOWNLOADED_3XX = 'DOWNLOADED_3XX';

		/** @const string INDEXING_INDICATOR_DOWNLOADED_4XX индикатор индексирования: страницы, загруженные с кодом 4XX */
		const INDEXING_INDICATOR_DOWNLOADED_4XX = 'DOWNLOADED_4XX';

		/** @const string INDEXING_INDICATOR_DOWNLOADED_5XX индикатор индексирования: страницы, загруженные с кодом 5XX */
		const INDEXING_INDICATOR_DOWNLOADED_5XX = 'DOWNLOADED_5XX';

		/** @const string INDEXING_INDICATOR_FAILED_TO_DOWNLOAD индикатор индексирования: незагруженные страницы */
		const INDEXING_INDICATOR_FAILED_TO_DOWNLOAD = 'FAILED_TO_DOWNLOAD';

		/** @const string INDEXING_INDICATOR_EXCLUDED индикатор индексирования: исключенные страницы */
		const INDEXING_INDICATOR_EXCLUDED = 'EXCLUDED';

		/**
		 * @const string INDEXING_INDICATOR_EXCLUDED_DISALLOWED_BY_USER индикатор индексирования:
		 * исключенные по желанию владельца ресурса (4xx-коды, запрет в robots.txt)
		 */
		const INDEXING_INDICATOR_EXCLUDED_DISALLOWED_BY_USER = 'EXCLUDED_DISALLOWED_BY_USER';

		/**
		 * @const string INDEXING_INDICATOR_EXCLUDED_SITE_ERROR индикатор индексирования:
		 * исключенные из-за ошибки на стороне сайта
		 */
		const INDEXING_INDICATOR_EXCLUDED_SITE_ERROR = 'EXCLUDED_SITE_ERROR';

		/**
		 * @const string INDEXING_INDICATOR_EXCLUDED_NOT_SUPPORTED индикатор индексирования: и
		 * сключенные из-за отсутствия поддержки на стороне роботов Яндекса.
		 */
		const INDEXING_INDICATOR_EXCLUDED_NOT_SUPPORTED = 'EXCLUDED_NOT_SUPPORTED';

		/**
		 * Возвращает историю индексирования сайта
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/hosts-indexed-docpage/
		 * @param string $siteId идентификатор сайта
		 * @param string $indicator индикатор индексирования:
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/hosts-indexed-docpage/#indexing-indicators
		 * @param int|null $from начало диапазона дат
		 * @param int|null $to конец диапазона дат
		 * @return array
		 * @throws BadRequest
		 * @throws BadResponse
		 */
		public function getIndexingHistory(
			$siteId,
			$indicator = self::INDEXING_INDICATOR_SEARCHABLE,
			$from = null,
			$to = null
		);

		/**
		 * Возвращает историю изменения значений тИЦ сайта за заданный интервал
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/history-tic-docpage/
		 * @param string $siteId идентификатор сайта
		 * @param int|null $from начало диапазона дат
		 * @param int|null $to конец диапазона дат
		 * @return array
		 * @throws BadResponse
		 */
		public function getTicHistory($siteId, $from = null, $to = null);

		/** @const string QUERY_ORDER_FIELD_TOTAL_SHOWS порядок сортировки запросов: количество показов */
		const QUERY_ORDER_FIELD_TOTAL_SHOWS = 'TOTAL_SHOWS';

		/** @const string QUERY_ORDER_FIELD_TOTAL_CLICKS порядок сортировки запросов: количество кликов */
		const QUERY_ORDER_FIELD_TOTAL_CLICKS = 'TOTAL_CLICKS';

		/** @const string QUERY_INDICATOR_TOTAL_SHOWS индикатор запросов: количество показов */
		const QUERY_INDICATOR_TOTAL_SHOWS = 'TOTAL_SHOWS';

		/** @const string QUERY_INDICATOR_TOTAL_CLICKS индикатор запросов: количество кликов */
		const QUERY_INDICATOR_TOTAL_CLICKS = 'TOTAL_CLICKS';

		/** @const string QUERY_INDICATOR_AVG_SHOW_POSITION индикатор запросов: средняя позиция показа */
		const QUERY_INDICATOR_AVG_SHOW_POSITION = 'AVG_SHOW_POSITION';

		/** @const string QUERY_INDICATOR_AVG_CLICK_POSITION индикатор запросов: средняя позиция клика */
		const QUERY_INDICATOR_AVG_CLICK_POSITION = 'AVG_CLICK_POSITION';

		/**
		 * Возвращает топ популярных поисковых запросов
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/host-search-queries-popular-docpage/
		 * @param string $siteId идентификатор сайта
		 * @param string $order порядок сортировки запросов:
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/host-search-queries-popular-docpage/#query-order
		 * @param string $indicator индикатор запросов:
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/host-search-queries-popular-docpage/#query-indicators
		 * @return array
		 * @throws BadRequest
		 * @throws BadResponse
		 */
		public function getPopularQueryList(
			$siteId,
			$order = self::QUERY_ORDER_FIELD_TOTAL_SHOWS,
			$indicator = self::QUERY_INDICATOR_TOTAL_SHOWS
		);

		/**
		 * Возвращает список примеров внешних ссылок на сайт
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/host-links-external-samples-docpage/
		 * @param string $siteId идентификатор сайта
		 * @param int|null $offset смещение списка
		 * @param int|null $limit ограничение на длину списка
		 * @return array
		 * @throws BadResponse
		 */
		public function getExternalLinkList($siteId, $offset = 0, $limit = 25);

		/**
		 * @const string EXTERNAL_LINKS_INDICATOR_LINKS_TOTAL_COUNT индикатор внешних ссылок:
		 * общее количество известных внешних ссылок на хост
		 */
		const EXTERNAL_LINKS_INDICATOR_LINKS_TOTAL_COUNT = 'LINKS_TOTAL_COUNT';

		/**
		 * Возвращает историю изменение количества внешних ссылок на сайт
		 * @link https://tech.yandex.ru/webmaster/doc/dg/reference/host-links-external-history-docpage/
		 * @param string $siteId идентификатор сайта
		 * @return array
		 * @throws BadResponse
		 */
		public function getExternalLinksCountHistory($siteId);
	}
