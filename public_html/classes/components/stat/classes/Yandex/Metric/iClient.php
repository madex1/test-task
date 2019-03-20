<?php

	namespace UmiCms\Classes\Components\Stat\Yandex\Metric;

	use UmiCms\Classes\Components\Stat\iRegistry;
	use UmiCms\Classes\System\Entities\Date\iFactory as DateFactory;
	use UmiCms\Classes\System\Utils\Api\Http\Exception\BadRequest;
	use UmiCms\Classes\System\Utils\Api\Http\Json\Yandex\Exception\BadToken;
	use UmiCms\System\Cache\iEngineFactory;

	/**
	 * Интерфейс клиента API "Яндекс.Метрика"
	 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/intro-docpage/
	 * @package UmiCms\Classes\Components\Stat\Yandex\Metric;
	 */
	interface iClient {

		/**
		 * Конструктор
		 * @param iRegistry $registry реестр
		 * @param DateFactory $dateFactory фабрика дат
		 * @param iEngineFactory $engineFactory фабрика хранилищ кеша
		 */
		public function __construct(iRegistry $registry, DateFactory $dateFactory, iEngineFactory $engineFactory);

		/**
		 * Возвращает список счетчиков
		 * @param int $offset смещение выборки (отсчитывается от нуля)
		 * @param int $limit ограничение на количество результатов выборки
		 * @link https://tech.yandex.ru/metrika/doc/api2/management/counters/counters-docpage/
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getCounterList($offset = 0, $limit = 500);

		/**
		 * Добавляет счетчик и возвращает его идентификатор
		 * @link https://tech.yandex.ru/metrika/doc/api2/management/counters/addcounter-docpage/
		 * @param \stdClass $counter данные счетчика, @see Serializer\iRequest
		 * @return int
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function addCounter(\stdClass $counter);

		/**
		 * Возвращает счетчик по идентификатору
		 * @link https://tech.yandex.ru/metrika/doc/api2/management/counters/counter-docpage/
		 * @param int $id идентификатор счетчика
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getCounter($id);

		/**
		 * Изменяет счетчик
		 * @link https://tech.yandex.ru/metrika/doc/api2/management/counters/editcounter-docpage/
		 * @param int $id идентификатор счетчика
		 * @param \stdClass $counter данные счетчика, @see Serializer\iRequest
		 * @return bool
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function editCounter($id, \stdClass $counter);

		/**
		 * Удаляет счетчик
		 * @link https://tech.yandex.ru/metrika/doc/api2/management/counters/deletecounter-docpage/
		 * @param int $id идентификатор счетчика
		 * @return bool
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function deleteCounter($id);

		/**
		 * Возвращает сводку по источникам трафика
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_sources-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getSourcesSummary($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по источникам трафика поисковым система
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_sources-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getSourcesSearchEngines($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по источникам трафика поисковым запросам
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_sources-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getSourcesSearchPhrases($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по источникам сайтам
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_sources-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getSourcesSites($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по источникам социальным сетям
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_sources-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getSourcesSocials($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по посещаемости
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_traffic-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getTraffic($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по конверсии
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_traffic-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getConversion($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по посещаемости (по времени суток)
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_traffic-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getHourlyTraffic($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по популяным страницам
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_content-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getPopularContent($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по страницам входа
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_content-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getContentEntrance($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по страницам выхода
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_content-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getContentExit($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по заголовкам страниц
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_content-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getTitles($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по параметрам url
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_content-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getUrlParams($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по параметрам посетителей
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_content-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getContentUserParams($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по параметрам визитов
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_content-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getContentVisitParams($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по используемым размерам окна браузера
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_tech-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getResolutionMap($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по используемым браузерам
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_tech-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getBrowsers($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по наличию поддержки кук
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_tech-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getCookies($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по используемым разрешениям дисплея
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_tech-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getDisplay($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по используемым группам дисплеев
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_tech-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getDisplayGroups($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по версиям flash
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_tech-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getFlash($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по наличию java
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_tech-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getJava($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по наличию javascript
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_tech-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getJavaScript($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по операционным системам
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_tech-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getPlatforms($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по версиям silverlight
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_tech-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getSilverlight($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по устройствам
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/preset_tech-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getDevices($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по странам посетителей
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/visitors/preset_geo-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getCountries($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по интересам посетителей
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/visitors/preset_interests-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getInterests($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по возрасту посетителей
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/visitors/preset_socdem-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getAge($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по полу посетителей
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/visitors/preset_socdem-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getGender($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по времени с первого визита
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/visitors/preset_loyalty-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getLoyaltyNewness($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по периодичности визитов
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/visitors/preset_loyalty-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getLoyaltyPeriod($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по времени с последнего визита
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/visitors/preset_loyalty-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getLoyaltyRecency($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по общему числу визитов
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/visitors/preset_loyalty-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getLoyaltyVisits($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по глубине просмотра сайта
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/visitors/preset_deepness-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getDeepnessDepth($counterId, \iUmiDate $from = null, \iUmiDate $to = null);

		/**
		 * Возвращает отчет по времени, проведенном на сайте
		 * @link https://tech.yandex.ru/metrika/doc/api2/api_v1/presets/visitors/preset_deepness-docpage/
		 * @param int $counterId идентификатор счетчика
		 * @param \iUmiDate|null $from начало периода отчета (по умолчанию неделю назад)
		 * @param \iUmiDate|null $to конец периода отчета (по умолчанию на сегодня)
		 * @return array
		 * @throws BadRequest
		 * @throws BadToken
		 */
		public function getDeepnessTime($counterId, \iUmiDate $from = null, \iUmiDate $to = null);
	}
