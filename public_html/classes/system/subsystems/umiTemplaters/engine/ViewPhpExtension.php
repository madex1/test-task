<?php

	use UmiCms\Service;

	/** Системное расширение PHP шаблонизатора. */
	class ViewPhpExtension implements IPhpExtension {

		/** @var array $systemModules список системных модулей */
		private $systemModules = ['core', 'system', 'custom'];

		/** @var array $cacheLabels кэш меток локализации */
		private $cacheLabels = [];

		/** @var array $commonVars хранит массив общих для шаблона переменных */
		private $commonVars = [];

		/** @var umiTemplaterPHP PHP шаблонизатор */
		private $umiTemplaterPHP;

		/**
		 * Конструктор
		 * @param umiTemplaterPHP $templater PHP шаблонизатор
		 */
		public function __construct(umiTemplaterPHP $templater) {
			$this->umiTemplaterPHP = $templater;
		}

		/**
		 * Возвращает общие для шаблонов переменные.
		 * @return array
		 */
		public function getCommonVars() {
			return $this->commonVars;
		}

		/**
		 * Устанавливает общие для шаблонов переменные.
		 * @param string $name имя устанавливаемой переменной
		 * @param array $commonVars
		 */
		public function setCommonVars($name, $commonVars) {
			$this->commonVars[$name] = $commonVars;
		}

		/**
		 * Возвращает перевод метки.
		 * @param string $label метка
		 * @param array|null $args дополнительные аргументы для форматирования перевода константы
		 * @return string
		 */
		public function getCacheLabel($label, $args = null) {
			if (is_array($args) && count($args) > 0) {
				return vsprintf($this->cacheLabels[$label], $args);
			}

			return $this->cacheLabels[$label];
		}

		/**
		 * Устанавливает список меток.
		 * @param array $cacheLabels
		 */
		public function setCacheLabels(array $cacheLabels) {
			$this->cacheLabels = $cacheLabels;
		}

		/**
		 * Проверяет существование метки перевода.
		 * @param string $label метка
		 * @return bool
		 */
		public function isSetLabel($label) {
			return isset($this->cacheLabels[$label]);
		}

		/**
		 * Проверяет не пустой ли массив с метками.
		 * @return bool
		 */
		public function isNotEmptyCacheLabels() {
			return empty($this->cacheLabels);
		}

		/**
		 * Возвращает список системных модулей.
		 * @return array
		 */
		public function getSystemModules() {
			return $this->systemModules;
		}

		/** @inheritdoc */
		public function getName() {
			return __CLASS__;
		}

		/**
		 * Возвращает каноническую ссылку для страницы
		 * @param array $variables глобальные переменные текущей страницы
		 * @return string
		 */
		public function getCanonicalLinkTag(array $variables) {
			if (!isset($variables['pageId']) || !cmsController::getInstance()->isModule('seo')) {
				return '';
			}

			$result = $this->umiTemplaterPHP->macros('seo', 'getRelCanonical', ['default', $variables['pageId']]);

			if (!isset($result['link']) || !$result['link']) {
				return '';
			}

			return '<link rel="canonical" href="' . $result['link'] . '" />';
		}

		/**
		 * Возвращает относительный (от корневой директории, доступной в WEB) путь до директории с ресурсами шаблона
		 * @param string $workingDir корневая директория, относительно которой строится путь
		 * @return string
		 */
		public function getResourceDirectory($workingDir = CURRENT_WORKING_DIR) {
			$resourceDirAbsolutePath = cmsController::getInstance()
				->getResourcesDirectory();
			return $this->removeSubPathAtStart($workingDir, $resourceDirAbsolutePath);
		}

		/**
		 * Выполняет xpath запрос к внутреннему результату работы текущего метода
		 * @param array $variables переменные текущего вызова
		 * @param string $query xpath запрос
		 * @return DOMNodeList
		 * @throws RuntimeException
		 */
		public function xpathToInnerResult(array $variables, $query) {
			if (!is_string($query) || $query === '') {
				throw new RuntimeException('Wrong xpath query given');
			}

			if (!isset($variables['xml:data'])) {
				throw new RuntimeException('Current method don\'t save any xml data');
			}

			$document = new DOMDocument();

			if (!$document->loadXML($variables['xml:data'])) {
				throw new RuntimeException('Current method create wrong xml data');
			}

			$xpath = new DOMXPath($document);
			/** @var DOMNodeList $nodes */
			return $xpath->evaluate($query);
		}

		/**
		 * Вызывает макрос и возвращает результат его выполнения
		 * @param string $moduleName модуль макроса
		 * @param string $method метод макроса
		 * @param array $arguments аргументы макроса
		 * @param array $extProps дополнительные поля, которые требуется получить в результате
		 * @param array $extGroups дополнительные группы полей, которые требуется получить в результате
		 * @param int $cacheLifetime время жизни кэша для вызова макроса
		 * @return mixed
		 * @throws Exception
		 */
		public function macros(
			$moduleName,
			$method,
			$arguments = [],
			$extProps = [],
			$extGroups = [],
			$cacheLifetime = 0
		) {
			$umiConfig = mainConfiguration::getInstance();

			try {
				$this->validateMacrosArguments($moduleName, $method, $arguments);
				if (!system_is_allowed($moduleName, $method)) {
					return null;
				}

				$module = $this->requireModule($moduleName);

				$cacheKey = '';
				$cacheFrontend = Service::CacheFrontend();
				$isCacheAllowed = $this->isCacheAllowed($cacheLifetime);

				if ($isCacheAllowed) {
					$cacheKey =
						"$moduleName/$method" .
						http_build_query(array_merge($arguments, $extProps, $extGroups)) .
						Service::Request()->queryHash();

					$result = $cacheFrontend->loadData($cacheKey);
					if ($result) {
						return $result;
					}
				}

				$previousExtProps = def_module::getMacrosExtendedProps();
				$previousExtGroups = def_module::getMacrosExtendedGroups();
				def_module::setMacrosExtendedResult($extProps, $extGroups);

				$result = call_user_func_array([$module, $method], $arguments);
				$cleanResult = $this->getTemplateEngine()
					->cleanData($result);

				def_module::setMacrosExtendedResult($previousExtProps, $previousExtGroups);

				if ($isCacheAllowed) {
					$cacheFrontend->saveData(
						$cacheKey,
						$cleanResult,
						$this->getCacheLifetime($cacheLifetime)
					);
				}

				return $cleanResult;
			} catch (Exception $e) {
				if (!(bool) $umiConfig->get('system', 'suppress-exceptions-in-php-macros')) {
					throw $e;
				}
			}
		}

		/**
		 * Валидирует аргументы для вызова макроса
		 * @param string $moduleName модуль макроса
		 * @param string $method метод макроса
		 * @param array $arguments аргументы макроса
		 */
		private function validateMacrosArguments($moduleName, $method, $arguments) {
			if (!isset($moduleName)) {
				throw new RuntimeException(__METHOD__ . ': не передано название модуля');
			}

			if (!isset($method)) {
				throw new RuntimeException(__METHOD__ . ': не передано название макроса');
			}

			if (!is_array($arguments)) {
				throw new RuntimeException(__METHOD__ . ': не переданы аргументы для макроса');
			}
		}

		/**
		 * Возвращает модуль по его названию
		 * @param string $moduleName название модуля
		 * @return def_module|core|system|custom
		 */
		private function requireModule($moduleName) {
			if (in_array($moduleName, $this->getSystemModules())) {
				$module = system_buildin_load($moduleName);
			} else {
				$module = cmsController::getInstance()->getModule($moduleName);
			}

			if (!$module) {
				throw new RuntimeException(__METHOD__ . ": не удалось загрузить модуль {$moduleName}");
			}

			return $module;
		}

		/**
		 * Определяет, нужно ли кэшировать результат вызова макроса
		 * @param int $lifetime время жизни кэша для вызова макроса
		 * @return bool
		 */
		private function isCacheAllowed($lifetime) {
			if ($lifetime > 0) {
				return true;
			}

			$umiConfig = mainConfiguration::getInstance();

			if (!$umiConfig->get('cache', 'streams.cache-enabled')) {
				return false;
			}

			return (int) $umiConfig->get('cache', 'streams.cache-lifetime') > 0;
		}

		/**
		 * Возвращает время жизни кэша для вызова макроса
		 * @param int $lifetime кастомное время жизни кэша для вызова макроса
		 * @return int
		 */
		private function getCacheLifetime($lifetime) {
			if ($lifetime > 0) {
				return $lifetime;
			}

			return (int) mainConfiguration::getInstance()
				->get('cache', 'streams.cache-lifetime');
		}

		/**
		 * Возвращает экземпляр страницы по ее адресу
		 * @param string $path адрес (ссылка) страницы
		 * @return iUmiHierarchyElement|bool
		 */
		public function getPageByPath($path) {
			return umiHierarchy::getInstance()->getElement(
				umiHierarchy::getInstance()->getIdByPath($path)
			);
		}

		/**
		 * Возвращает экземпляр страницы по ее id
		 * @param int $id идентификатор страницы
		 * @return iUmiHierarchyElement|bool
		 */
		public function getPageById($id) {
			return umiHierarchy::getInstance()
				->getElement($id);
		}

		/**
		 * Возвращает объект по id
		 * @param int $id идентификатор объекта
		 * @return iUmiObject|bool
		 */
		public function getObjectById($id) {
			return umiObjectsCollection::getInstance()
				->getObject($id);
		}

		/**
		 * Возвращает адрес (ссылку) страницы
		 * @param iUmiHierarchyElement $page страница
		 * @return string
		 * @throws coreException
		 */
		public function getPath(iUmiHierarchyElement $page) {
			$linkHelper = umiLinksHelper::getInstance();
			$link = $linkHelper->getLinkByParts($page);

			if ($link === false) {
				return $linkHelper->getLink($page);
			}

			return $link;
		}

		/**
		 * Возвращает страницу по умолчанию
		 * @return iUmiHierarchyElement|false
		 */
		public function getDefaultPage() {
			return umiHierarchy::getInstance()->getDefaultElement();
		}

		/**
		 * Возвращает перевод метки.
		 * @param string $label метка
		 * @param bool|string $path @see ulangStream::getLabel()
		 * @return string
		 * @throws coreException
		 */
		public function translate($label, $path = false) {
			$args = func_get_args();

			if ($this->isNotEmptyCacheLabels()) {
				$templateDirectory = cmsController::getInstance()->getTemplatesDirectory();
				$languagePrefix = Service::LanguageDetector()->detectPrefix();
				$fileI18N = $templateDirectory . 'i18n/i18n.' . $languagePrefix . '.php';

				if (file_exists($fileI18N)) {
					$this->setCacheLabels(require $fileI18N);
					if ($this->isSetLabel($label)) {
						return $this->getCacheLabel($label, array_slice($args, 2));
					}
				}
			} else {
				if ($this->isSetLabel($label)) {
					return $this->getCacheLabel($label, array_slice($args, 2));
				}
			}

			return ulangStream::getLabel($label, $path, $args);
		}

		/**
		 * Возвращает обработанный Request-параметр.
		 * @param string $name имя параметра
		 * @param mixed $default значений по умолчанию, если параметр не объявлен
		 * @return mixed
		 */
		public function getRequest($name, $default = null) {
			$param = getRequest($name);

			if (!$param) {
				return $default;
			}

			return htmlspecialchars($param);
		}

		/**
		 * Возвращает Request-параметр.
		 * @param string $name имя параметра
		 * @param mixed $default значений по умолчанию, если параметр не объявлен
		 * @return mixed
		 */
		public function getRawRequest($name, $default = null) {
			$param = getRequest($name);

			if (!$param) {
				return $default;
			}

			return $param;
		}

		/**
		 * Возвращает значение запрошенной общей переменной.
		 * @param string $name имя переменной
		 * @return mixed
		 */
		public function getCommonVar($name) {
			$commonVars = $this->getCommonVars();
			return isset($commonVars[$name]) ? $commonVars[$name] : null;
		}

		/**
		 * Устанавливает значение общей переменной.
		 * @param string $name имя переменной
		 * @param mixed $value значение переменной
		 */
		public function setCommonVar($name, $value) {
			$this->setCommonVars($name, $value);
		}

		/**
		 * Проверяет существование общей переменной.
		 * @param string $name имя переменной
		 * @return bool
		 */
		public function isSetCommonVar($name) {
			$commonVars = $this->getCommonVars();
			return isset($commonVars[$name]);
		}

		/**
		 * Выполняет usel запрос.
		 * @see uselStream::call()
		 * @param string $uselName название шаблона usel
		 * @param array|null $params параметры вызова
		 * @return array
		 * @throws publicException
		 */
		public function usel($uselName, $params = null) {
			$stream = new uselStream;
			return $stream->call($uselName, $params);
		}

		/**
		 * Определяет включен ли режим отладки
		 * @return bool
		 */
		public function isDebug() {
			return defined('DEBUG') && DEBUG;
		}

		/**
		 * Возвращает PHP шаблонизатор
		 * @return umiTemplaterPHP
		 */
		protected function getTemplateEngine() {
			return $this->umiTemplaterPHP;
		}

		/**
		 * Удаляет часть пути из его начала
		 * @param string $needle удаляемая часть пути
		 * @param string $haystack исходный путь
		 * @return string путь, полученный в результате удаления его части
		 */
		private function removeSubPathAtStart($needle, $haystack) {
			if (startsWith($haystack, $needle)) {
				return str_replace($needle, '', $haystack);
			}

			return $haystack;
		}

		/**
		 * Обертка над `htmlspecialchars` для экранирования строк, которые выводятся на сайте
		 * @param string $value строка
		 * @return string
		 */
		public function escape($value) {
			return str_replace('&amp;', '&', htmlspecialchars($value));
		}

		/** @deprecated */
		public function parseTplMacros($value, $elementId = false, $objectId = false) {
			return $value;
		}
	}
