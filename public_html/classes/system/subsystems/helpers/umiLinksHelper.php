<?php

	use UmiCms\Service;

	/** Класс для генерации адресов страниц */
	class umiLinksHelper {

		/** @var umiLinksHelper $instance экземпляр класса */
		private static $instance;

		/** @var array $links массив со сгенерированными адресами страниц */
		private $links;

		/** @var string $urlPrefix префикс адреса страницы */
		private $urlPrefix;

		/** @var int $defaultPageId идентификатор главной страницы */
		private $defaultPageId;

		/** @var bool $isPathAbsolute включен ли режим генерации абсолютных адресов? */
		private $isPathAbsolute;

		/** @var int $defaultLangId идентификатор языка домена по умолчанию */
		private $defaultLangId;

		/** @var int $currentDomainId идентификатор текущего домена */
		private $currentDomainId;

		/** @const string URL_SEPARATOR разделитель адресов страниц */
		const URL_SEPARATOR = '/';

		/** @const string PROTOCOL протокол (не используется, оставлено в целях обратной совместимости) */
		const PROTOCOL = 'http://';

		/** @const int ROOT_PAGE_ID идентификатор корневой страницы */
		const ROOT_PAGE_ID = 0;

		/**
		 * Возвращает экземпляр текущего класса
		 * @return umiLinksHelper
		 * @throws coreException
		 */
		public static function getInstance() {
			if (self::$instance === null) {
				self::$instance = new umiLinksHelper();
			}
			return self::$instance;
		}

		/**
		 * Возвращает ссылку на страницу.
		 * При необходимости, полностью ее формирует.
		 * Может использоваться независимо от других публичных методов класса
		 * @param iUmiHierarchyElement $element объект класса
		 * @return string
		 * @throws coreException
		 */
		public function getLink(iUmiHierarchyElement $element) {
			$pageId = (int) $element->getId();

			if ($this->isLoadedPage($pageId)) {
				return $this->createLink($pageId);
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT DISTINCT
  cms3_hierarchy_relations.rel_id AS page_id,
  cms3_hierarchy.alt_name,
  cms3_hierarchy.rel              AS parent_id
FROM cms3_hierarchy_relations
  LEFT JOIN cms3_hierarchy ON cms3_hierarchy_relations.rel_id = cms3_hierarchy.id
WHERE cms3_hierarchy_relations.child_id = {$pageId} AND cms3_hierarchy_relations.rel_id IS NOT NULL
ORDER BY cms3_hierarchy_relations.id;
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			$rows = [];
			foreach ($result as $row) {
				$rows[] = $row;
			}

			$this->generateFullLinks($rows, $pageId, $element->getAltName(), $element->getParentId());

			if (!$this->isLoadedPage($pageId)) {
				$this->links[$pageId] = self::URL_SEPARATOR . $element->getAltName();
			}

			return $this->createLink($pageId);
		}

		/**
		 * Возвращает ссылку на страницу, если ранее
		 * в класс были загружены данные ее родителей или она сама.
		 * (см. loadLinkPartForPages()).
		 * @param iUmiHierarchyElement $element объект страницы
		 * @return bool|string
		 */
		public function getLinkByParts(iUmiHierarchyElement $element) {
			$parentId = $element->getParentId();
			$pageId = $element->getId();
			$path = null;

			if ($this->isLoadedPage($pageId)) {
				return $this->createLink($pageId);
			}

			$elementLink = self::URL_SEPARATOR . $element->getAltName();

			if (!$this->isLoadedPage($parentId)) {

				if ($parentId !== self::ROOT_PAGE_ID) {
					return false;
				}

				$path = $elementLink;
			}

			if ($path === null) {
				$path = $this->links[$parentId] . $elementLink;
			}

			$this->links[$pageId] = $path;
			return $this->createLink($pageId);
		}

		/**
		 * Загружает данные для формирования адресов страниц.
		 * @param array $pageIds массив с идентификаторами страниц
		 * @return bool
		 * @throws databaseException
		 */
		public function loadLinkPartForPages(array $pageIds) {
			if (umiCount($pageIds) === 0) {
				return false;
			}

			$pageIdsToLoad = [];

			foreach ($pageIds as $key => $value) {
				$pageId = (int) $value;
				if (!$this->isLoadedPage($pageId)) {
					$pageIdsToLoad[$key] = $pageId;
				}
			}

			if (umiCount($pageIdsToLoad) === 0) {
				return true;
			}

			$pageIds = implode(',', $pageIdsToLoad);
			$connection = ConnectionPool::getInstance()->getConnection();
			$linksData = <<<SQL
SELECT DISTINCT
  cms3_hierarchy_relations.rel_id AS page_id,
  cms3_hierarchy.alt_name,
  cms3_hierarchy.rel              AS parent_id,
  cms3_hierarchy.ord
FROM cms3_hierarchy_relations
  LEFT JOIN cms3_hierarchy ON cms3_hierarchy_relations.rel_id = cms3_hierarchy.id
WHERE cms3_hierarchy_relations.child_id IN ({$pageIds}) AND cms3_hierarchy_relations.rel_id IS NOT NULL
SQL;
			$result = $connection->queryResult($linksData);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return false;
			}

			$linksData = [];

			foreach ($result as $row) {
				$linksData[$row['page_id']] = $row;
			}

			$parentIds = array_keys($linksData);
			$parentIds = implode(',', $parentIds);

			$minLimitSql = <<<SQL
SELECT `child_id`, min(`level`) as `min_level` FROM `cms3_hierarchy_relations`
WHERE cms3_hierarchy_relations.child_id IN ({$parentIds})
GROUP BY `child_id`
SQL;

			$result = $connection->queryResult($minLimitSql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return false;
			}

			foreach ($result as $row) {
				if (isset($linksData[$row['child_id']])) {
					$linksData[$row['child_id']]['min_level'] = $row['min_level'];
				}
			}

			$linksData = $this->sortLinksData($linksData);
			return $this->generatePartOfLinks($linksData);
		}

		/**
		 * Загружены ли данные страницы
		 * @param int $elementId ид страницы
		 * @return bool
		 */
		public function isLoadedPage($elementId) {
			return isset($this->links[(int) $elementId]);
		}

		/**
		 * Выгрузить данные страницы
		 * @param int $elementId ид страницы
		 * @return bool
		 */
		public function unloadPage($elementId) {
			if (!$this->isLoadedPage($elementId)) {
				return false;
			}

			unset($this->links[(int) $elementId]);
			return true;
		}

		/**
		 * Очистить все загруженные данные
		 */
		public function clearCache() {
			$this->links = [];
		}

		/**
		 * Сортирует массив данных страниц для генерации ссылок,
		 * возвращает отсортированный массив
		 * @param array $linksData данные страниц для генерации ссылок
		 * @return array
		 */
		private function sortLinksData(array $linksData) {
			usort(
				$linksData,
				function (array $first, array $second) {
					$firstItemLevel = $first['min_level'];
					$firstItemOrd = $first['ord'];
					$secondItemLevel = $second['min_level'];
					$secondItemOrd = $second['ord'];

					switch (true) {
						case ($firstItemLevel == $secondItemLevel) && ($firstItemOrd == $secondItemOrd): {
							return 0;
						}
						case ($firstItemLevel == $secondItemLevel) && ($firstItemOrd > $secondItemOrd): {
							return 1;
						}
						case ($firstItemLevel > $secondItemLevel): {
							return 1;
						}
						default: {
							return -1;
						}
					}
				}
			);

			return $linksData;
		}

		/**
		 * Конструктор
		 * @throws coreException
		 */
		private function __construct() {
			$this->urlPrefix = cmsController::getInstance()
				->getUrlPrefix();
			$currentDomain = Service::DomainDetector()->detect();
			$this->currentDomainId = $currentDomain->getId();
			$this->defaultLangId = $currentDomain->getDefaultLangId();
		}

		/**
		 * "Склеивает" конечную ссылку на страницу с учетом всех параметров
		 * @param int $pageId ид страницы
		 * @return bool|string
		 */
		private function createLink($pageId) {
			static $cache = [];

			$pageId = (int) $pageId;

			if (isset($cache[$pageId])) {
				return $cache[$pageId];
			}

			if (!$this->isLoadedPage($pageId)) {
				return false;
			}

			$pageLink = $this->links[$pageId];
			$pageLink = rtrim($pageLink, '/');

			if ($this->getDefaultElementId() === $pageId) {
				$pageLink = '';
			}

			$pageLink .= $this->getUrlSuffix();
			$umiHierarchy = umiHierarchy::getInstance();

			$page = $umiHierarchy->getElement($pageId);
			if (!$page instanceof iUmiHierarchyElement) {
				return false;
			}

			$elementDomainId = (int) $page->getDomainId();

			$domainPrefix = '';
			if ($elementDomainId !== $this->currentDomainId || $this->isPathAbsolute()) {
				$umiDomains = Service::DomainCollection();
				$elementDomain = $umiDomains->getDomain($elementDomainId);
				if ($elementDomain instanceof iDomain) {
					$domainPrefix = $elementDomain->getUrl();
				}
			}

			$elementLangId = (int) $page->getLangId();
			$langPrefix = '';
			if ($elementLangId !== $this->defaultLangId) {
				$umiLangs = Service::LanguageCollection();
				$elementLang = $umiLangs->getLang($elementLangId);
				if ($elementLang instanceof iLang) {
					$langPrefix = self::URL_SEPARATOR . (string) $elementLang->getPrefix();
				}
			}

			return $cache[$pageId] = $domainPrefix . $langPrefix . $this->urlPrefix . $pageLink;
		}

		/**
		 * Возвращает суффикс адреса страницы
		 * @return string
		 */
		private function getUrlSuffix() {
			$config = mainConfiguration::getInstance();
			$suffix = '';

			if ($config->get('seo', 'url-suffix.add')) {
				$suffix = (string) $config->get('seo', 'url-suffix');
			}

			return $suffix;
		}

		/**
		 * Включен ли абсолютный режим генерации адресов
		 * @return bool
		 */
		private function isPathAbsolute() {
			if ($this->isPathAbsolute === null) {
				$this->isPathAbsolute = umiHierarchy::getInstance()->isPathAbsolute();
			}
			return $this->isPathAbsolute;
		}

		/**
		 * Возвращает id главной страницы
		 * @return int
		 */
		private function getDefaultElementId() {
			if ($this->defaultPageId === null) {
				$this->defaultPageId = umiHierarchy::getInstance()->getDefaultElementId();
			}
			return (int) $this->defaultPageId;
		}

		/**
		 * Генерирует полные данные об адресе страниц.
		 * @param array $rows массив с данными о иерархических связях страницы
		 * @param int $pageId ид страницы
		 * @param string $pageAltName псевдостатических адрес страницы
		 * @param int $parentId ид родителя страницы
		 * @return bool
		 */
		private function generateFullLinks(array $rows, $pageId, $pageAltName, $parentId) {
			if (umiCount($rows) == 0) {
				return false;
			}

			$parents = [];

			foreach ($rows as $row) {
				if (isset($parents[$row['parent_id']])) {
					$this->links[$row['page_id']] = $parents[$row['parent_id']] . self::URL_SEPARATOR . $row['alt_name'];
					$parents[$row['page_id']] = $parents[$row['parent_id']] . self::URL_SEPARATOR . $row['alt_name'];
				} else {
					$this->links[$row['page_id']] = self::URL_SEPARATOR . $row['alt_name'];
					$parents[$row['page_id']] = self::URL_SEPARATOR . $row['alt_name'];
				}
			}

			if (isset($parents[$parentId])) {
				$this->links[$pageId] = $parents[$parentId] . self::URL_SEPARATOR . $pageAltName;
				return true;
			}

			return false;
		}

		/**
		 * Генерирует данные об адресах родителей страниц
		 * @param array $rows массив с данными о иерархических связях страниц
		 * @return bool
		 */
		private function generatePartOfLinks(array $rows) {
			if (umiCount($rows) == 0) {
				return false;
			}

			$links = &$this->links;
			$parents = [];

			foreach ($rows as $row) {
				if (isset($parents[$row['parent_id']])) {
					$links[$row['page_id']] = $parents[$row['parent_id']] . self::URL_SEPARATOR . $row['alt_name'];
					$parents[$row['page_id']] = $parents[$row['parent_id']] . self::URL_SEPARATOR . $row['alt_name'];
				} else {
					$links[$row['page_id']] = self::URL_SEPARATOR . $row['alt_name'];
					$parents[$row['page_id']] = self::URL_SEPARATOR . $row['alt_name'];
				}
			}

			return !(umiCount($parents) === 0);
		}
	}


