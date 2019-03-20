<?php

	/**
	 * Фабрика отчетов по статистике.
	 * Возвращает декораторы результатов отчетов.
	 */
	class statisticFactory {

		/** @var string $libsPath полный путь до директории, содержащей реализации отчетов */
		private $libsPath;

		/** @var array $validReports имена существующих отчетов */
		private $validReports = [
			'auditoryActivity',
			'auditoryLoyality',
			'auditoryVolume',
			'auditoryVolumeGrowth',
			'entryPoints',
			'exitPoints',
			'hostsCommon',
			'pageNext',
			'pagesHits',
			'paths',
			'sectionHits',
			'sourcesDomains',
			'sourcesDomainsConcrete',
			'sourcesSEO',
			'sourcesSEOConcrete',
			'sourcesSEOKeywords',
			'sourcesSEOKeywordsConcrete',
			'sourcesTop',
			'userStat',
			'visitCommon',
			'visitCommonHours',
			'visitDeep',
			'visitersCommon',
			'visitersCommonHours',
			'visitTime',
			'entryByReferer',
			'refererByEntry',
			'cityStat',
			'tag'
		];

		/**
		 * Конструктор
		 * @param string $libsPath полный путь до директории, содержащей реализации отчетов
		 */
		public function __construct($libsPath) {
			$this->libsPath = $libsPath;
		}

		/**
		 * Доступен ли отчет с именем
		 * @param string $reportName имя отчета
		 * @return bool
		 */
		public function isValid($reportName) {
			return in_array($reportName, $this->validReports);
		}

		/**
		 * Возвращает декоратор результата отчета по имени отчета
		 * @param string $reportName имя отчета
		 * @return xmlDecorator
		 */
		public function get($reportName) {
			/** @noinspection PhpIncludeInspection */
			require_once $this->libsPath . '/' . $reportName . '.php';
			/** @noinspection PhpIncludeInspection */
			require_once $this->libsPath . '/../decorators/' . $reportName . 'Xml.php';

			$xml = $reportName . 'Xml';
			return new $xml(new $reportName);
		}
	}

