<?php

	use UmiCms\Service;

	/**
	 * Класс для работы с языковыми константами
	 *
	 * Умеет:
	 *    - находить перевод языковой константы
	 *  - находить языковую константу по ее переводу
	 *  - возвращать префикс языковой версии сайта
	 *  - возвращать все языковые константы в форматах 'js' и 'dtd'
	 *
	 * @link http://dev.docs.umi-cms.ru/prakticheskie_primery/internacionalizaciya_sajta/internacionalizaciya_shablonov_dannyh/
	 */
	class ulangStream extends umiBaseStream {

		/** @var string префикс языковых констант */
		const i18nPrefix = 'i18n::';

		/** @var string текущий префикс языковой версии сайта */
		private static $langPrefix;

		/** @var string название текущего модуля */
		private static $currentModuleName;

		/** @var string путь до директории с шаблонами сайта */
		private static $templatesDirectoryPath;

		/**
		 * @var array[] кэш переводов языковых констант,
		 * сгруппированных по названию модулей
		 *
		 * [
		 *     название модуля => [
		 *         языковая константа => перевод
		 *     ],
		 *     ...
		 * ]
		 */
		protected static $i18nCache = [];

		/**
		 * @var array[] кэш переводов языковых констант,
		 * сгруппированных по строке ulang-запроса
		 *
		 * [
		 *     строка запроса => [
		 *         языковая константа => перевод
		 *     ],
		 *     ...
		 * ]
		 */
		protected static $i18nPathCache = [];

		/** @var string протокол потока */
		protected $scheme = 'ulang';

		/** @inheritdoc */
		public function stream_open($path, $mode, $options, $openedPath) {
			static $cache = [];
			$info = parse_url($path);
			$path = trim(getArrayKey($info, 'host') . getArrayKey($info, 'path'), '/');
			$this->path = $path;

			if (mb_substr($path, -5, 5) == ':file') {
				$dtdContent = $this->getExternalDTD(mb_substr($path, 0, mb_strlen($path) - 5));
				return $this->setData($dtdContent);
			}

			if (contains(getArrayKey($info, 'query'), 'js')) {
				$mode = 'js';
			} else {
				if (contains($path, 'js')) {
					$mode = 'js';
					$path = mb_substr($path, 0, mb_strlen($path) - 3);
					$this->path = $path;
				} else {
					$mode = 'dtd';
				}
			}

			if ($mode == 'js') {
				$buffer = Service::Response()
					->getCurrentBuffer();
				$buffer->contentType('text/javascript');
				$data = $this->generateJavaScriptLabels($path);
				return $this->setData($data);
			}

			if (isset($cache[$path])) {
				$data = $cache[$path];
			} else {
				$i18nMixed = self::loadI18NFiles($path);
				$data = $cache[$path] = $this->translateToDTD($i18nMixed);
			}

			return $this->setData($data);
		}

		/**
		 * Возвращает название текущего модуля.
		 * Если оно не установлено - пытается его определить.
		 * @return string
		 */
		private static function getCurrentModuleName() {
			if (self::$currentModuleName === null) {
				self::$currentModuleName = cmsController::getInstance()
					->getCurrentModule();
			}

			return self::$currentModuleName;
		}

		/**
		 * Устанавливает название текущего модуля
		 * @param string $name название текущего модуля
		 * @throws coreException
		 */
		public static function setCurrentModuleName($name) {
			if (!is_string($name) || $name === '') {
				throw new coreException('Incorrect module name given');
			}

			self::$currentModuleName = $name;
		}

		/**
		 * Возвращает путь до директории с шаблонами сайта.
		 * Если путь не задан, то пытается его определить.
		 * @return string
		 */
		private static function getTemplatesDirectoryPath() {
			if (self::$templatesDirectoryPath === null) {
				$path = (string) cmsController::getInstance()
					->getResourcesDirectory();
				self::$templatesDirectoryPath = rtrim($path, '/');
			}

			return self::$templatesDirectoryPath;
		}

		/**
		 * Устанавливает путь до директории с шаблонами сайта
		 * @param string $path путь до директории с шаблонами сайта
		 * @throws coreException
		 */
		public static function setTemplatesDirectoryPath($path) {
			if (!is_string($path) || $path === '') {
				throw new coreException('Incorrect directory path given');
			}

			self::$templatesDirectoryPath = $path;
		}

		/**
		 * Возвращает текущий префикс языковой версии сайта.
		 * Если префикс не задан - пытается его определить.
		 * @return bool|mixed|null|string
		 */
		public static function getLangPrefix() {
			if (self::$langPrefix !== null) {
				return self::$langPrefix;
			}

			$detectedPrefix = Service::LanguageDetector()
				->detectPrefix();
			self::$langPrefix = $detectedPrefix;

			if (!defined('VIA_HTTP_SCHEME') && Service::Request()->isNotAdmin()) {
				return self::$langPrefix;
			}

			if (defined('VIA_HTTP_SCHEME')) {
				$elementId = cmsController::getInstance()
					->getCurrentElementId();
				$element = umiHierarchy::getInstance()->getElement($elementId);

				if ($element instanceof iUmiHierarchyElement) {
					self::$langPrefix = Service::LanguageCollection()
						->getLang($element->getLangId())
						->getPrefix();
				}

				return self::$langPrefix;
			}

			$cookieJar = Service::CookieJar();

			self::$langPrefix = getArrayKey($_POST, 'ilang');
			if (self::$langPrefix !== null) {
				$cookieJar->set('ilang', self::$langPrefix, time() + 3600 * 24 * 31);
				return self::$langPrefix;
			}

			self::$langPrefix = getArrayKey($_GET, 'ilang');
			if (self::$langPrefix !== null) {
				$cookieJar->set('ilang', self::$langPrefix, time() + 3600 * 24 * 31);
				return self::$langPrefix;
			}

			self::$langPrefix = $cookieJar->get('ilang');

			if (self::$langPrefix !== null) {
				$cookieJar->set('ilang', self::$langPrefix, time() + 3600 * 24 * 31);
				return self::$langPrefix;
			}

			return self::$langPrefix = $detectedPrefix;
		}

		/**
		 * Устанавливает текущий префикс языковой версии сайта
		 * @param string $langPrefix префикс языковой версии сайта
		 * @throws coreException
		 */
		public static function setLangPrefix($langPrefix) {
			if (!is_string($langPrefix) || $langPrefix === '') {
				throw new coreException('Incorrect language prefix given');
			}

			self::$langPrefix = $langPrefix;
		}

		/**
		 * Преобразовывает переводы констант в формат Document type definition
		 * @param array $translationMap массив вида [label => translation, ...]
		 * @return string
		 */
		protected function translateToDTD(array $translationMap) {
			$dtd = "<!ENTITY quote '&#34;'>\n";
			$dtd .= "<!ENTITY nbsp '&#160;'>\n";
			$dtd .= "<!ENTITY middot '&#183;'>\n";
			$dtd .= "<!ENTITY reg '&#174;'>\n";
			$dtd .= "<!ENTITY copy '&#169;'>\n";
			$dtd .= "<!ENTITY raquo '&#187;'>\n";
			$dtd .= "<!ENTITY laquo '&#171;'>\n";

			foreach ($translationMap as $label => $translation) {
				$translation = $this->protectEntityValue($translation);
				$dtd .= "<!ENTITY {$label} \"{$translation}\">\n";
			}

			return $dtd;
		}

		/**
		 * Экранирует значения символов для выдачи в формате XML
		 * @param string $translation перевод константы
		 * @return string
		 */
		protected function protectEntityValue($translation) {
			$from = ['&', '"', '%'];
			$to = ['&amp;', '&quote;', '&#037;'];
			return str_replace($from, $to, $translation);
		}

		/**
		 * Загружает переводы языковых констант по строке запроса
		 * и возвращает их в формате [label => translation, ...].
		 *
		 * @param string $path строка с указанием модулей,
		 * для которых нужны константы (@example 'data/content/users')
		 *
		 * @return array
		 */
		protected static function loadI18NFiles($path) {
			if (array_key_exists($path, self::$i18nPathCache)) {
				return self::$i18nPathCache[$path];
			}

			$moduleList = self::getModuleList($path);
			self::loadI18nForModuleList($moduleList);

			foreach ($moduleList as $module) {
				foreach (self::$i18nCache[$module] as $label => $translation) {
					self::$i18nPathCache[$path][$label] = $translation;
				}
			}

			if (array_key_exists($path, self::$i18nPathCache)) {
				return self::$i18nPathCache[$path];
			}

			return [];
		}

		/**
		 * Возвращает список модулей
		 * @param string $path строка с указанием модулей, @example 'data/content/users'
		 * @return array
		 */
		private static function getModuleList($path) {
			$currentModule = self::getCurrentModuleName();
			$candidates = self::parseLangsPath($path);
			if (!in_array($currentModule, $candidates)) {
				$candidates[] = $currentModule;
			}

			$filtered = [];
			foreach ($candidates as $module) {
				if ($module) {
					$filtered[] = $module;
				}
			}

			return $filtered;
		}

		/**
		 * Возвращает список модулей из строки запроса.
		 * @param string $path строка с указанием модулей, @example 'data/content/users'
		 * @return array
		 */
		protected static function parseLangsPath($path) {
			$protocol = 'ulang://';

			if (startsWith($path, $protocol)) {
				$path = mb_substr($path, mb_strlen($protocol));
			}

			$path = trim($path, '/');
			return explode('/', $path);
		}

		/**
		 * Загружает в кэш переводы языковых констант для модулей
		 * @param array $moduleList список модулей
		 */
		private static function loadI18nForModuleList(array $moduleList) {
			foreach ($moduleList as $module) {
				if (!array_key_exists($module, self::$i18nCache)) {
					self::$i18nCache[$module] = self::loadI18nForModule($module);
				}
			}
		}

		/**
		 * Загружает переводы языковых констант для модуля и возвращает их.
		 * @param string $module название модуля
		 * @return array
		 */
		private static function loadI18nForModule($module) {
			$moduleDir = ($module === 'common') ? '' : "{$module}/";
			$moduleI18n = self::loadSystemI18n($moduleDir);
			$externalI18n = self::loadExtI18n($moduleDir);

			foreach ($externalI18n as $label => $translation) {
				if (!array_key_exists($label, $moduleI18n)) {
					$moduleI18n[$label] = $translation;
				}
			}

			return $moduleI18n;
		}

		/**
		 * Загружает переводы языковых констант для модуля
		 * из системных файлов и возвращает их.
		 * @param string $moduleDir директория модуля
		 * @return array
		 */
		private static function loadSystemI18n($moduleDir) {
			$moduleI18n = [];

			foreach (self::getI18nSources() as $source) {
				$i18nFile = $source['dir'] . $moduleDir . $source['file'];
				if (!file_exists($i18nFile)) {
					continue;
				}

				/** @var array $i18n наполняется константами в загружаемом файле */
				$i18n = [];
				include $i18nFile;

				foreach ($i18n as $label => $translation) {
					$moduleI18n[$label] = $translation;
				}
			}

			return $moduleI18n;
		}

		/**
		 * Возвращает список источников языковых констант
		 * @return array
		 *
		 * [
		 *     [
		 *         'dir' => путь до директории,
		 *         'file' => название файла
		 *     ],
		 *     ...
		 * ]
		 *
		 */
		private static function getI18nSources() {
			$langPrefix = self::getLangPrefix();
			$commonFileName = 'i18n.php';
			$localizedFileName = "i18n.{$langPrefix}.php";

			$sources = [
				[
					'dir' => SYS_MODULES_PATH,
					'file' => $commonFileName
				],
				[
					'dir' => SYS_MODULES_PATH,
					'file' => $localizedFileName
				],
			];

			$templateDir = self::getTemplatesDirectoryPath();

			if ($templateDir) {
				$templateDir .= '/classes/modules/';

				$sources[] = [
					'dir' => $templateDir,
					'file' => $commonFileName
				];
				$sources[] = [
					'dir' => $templateDir,
					'file' => $localizedFileName
				];
			}

			return $sources;
		}

		/**
		 * Загружает переводы языковых констант для модуля
		 * из файлов расширений и возвращает их.
		 * @param string $moduleDir директория модуля
		 * @return array
		 */
		public static function loadExtI18n($moduleDir) {
			$langPrefix = self::getLangPrefix();
			$pattern = SYS_MODULES_PATH . "{$moduleDir}ext/i18n.*.{$langPrefix}.php";

			/** @noinspection UnnecessaryCastingInspection */
			$fileList = (array) glob($pattern);
			$externalI18n = [];

			foreach ($fileList as $file) {
				if (!file_exists($file)) {
					continue;
				}

				/** @var array $i18n наполняется константами в загружаемом файле */
				$i18n = [];
				include $file;
				foreach ($i18n as $label => $translation) {
					$externalI18n[$label] = $translation;
				}
			}

			return $externalI18n;
		}

		public static function getLabelSimple($label, array $params = []) {
			return self::getLabel($label, false, array_merge([$label, false], $params));
		}

		/**
		 * Возвращает перевод языковой константы
		 * @param string $label языковая константа
		 * @param bool|string $path строка с указанием загружаемых файлов констант
		 * @param null|array $args дополнительные аргументы для форматирования перевода константы
		 * @return mixed|string
		 */
		public static function getLabel($label, $path = false, $args = null) {
			$langPath = $path ?: 'common/data';
			$translationMap = self::loadI18NFiles($langPath);

			if (isset($translationMap[$label])) {
				$translation = $translationMap[$label];
			} elseif (!$path && startsWith($label, 'module-')) {
				$moduleName = str_replace('module-', '', $label);
				$translation = self::getLabel($label, $moduleName, $args);
			} else {
				$translation = $label;
			}

			if (is_array($args) && umiCount($args) > 2) {
				$translation = vsprintf($translation, array_slice($args, 2));
			}

			return $translation;
		}

		/**
		 * Возвращает языковую константу по ее переводу
		 * @param string $translation перевод константы
		 * @param string $typePrefix префикс константы (например `fields-group`)
		 * @param bool $searchAllEntries найти все константы по вхождению строки в перевод
		 * @return array|null|string
		 */
		public static function getI18n($translation, $typePrefix = '', $searchAllEntries = false) {
			if (!$translation) {
				return $translation;
			}

			$translation = (string) $translation;
			$langPath = 'common/data';
			$translationMap = self::loadI18NFiles($langPath);

			if ($searchAllEntries) {
				$result = self::findAllTranslations($translationMap, $translation);
			} else {
				$result = self::findTranslation($translationMap, $translation, $typePrefix);
			}

			if ($result === null) {
				return $result;
			}

			$allowedPrefixes = [
				'object-type',
				'hierarchy-type',
				'field',
				'fields-group',
				'field-type',
				'object'
			];

			if (is_string($result)) {
				$isAllowed = false;
				$trimmedPrefix = str_replace(self::i18nPrefix, '', $typePrefix);

				if (!$trimmedPrefix) {
					return $result;
				}

				foreach ($allowedPrefixes as $allowedPrefix) {
					if (startsWith($trimmedPrefix, $allowedPrefix)) {
						$isAllowed = true;
						break;
					}
				}

				return $isAllowed ? $result : null;
			}

			$allowedResult = [];

			/** @var array $result */
			foreach ($result as $pendingTranslation) {
				$isAllowed = false;
				$trimmedPrefix = str_replace(self::i18nPrefix, '', $typePrefix);

				if (!$trimmedPrefix) {
					return $result;
				}

				foreach ($allowedPrefixes as $allowedPrefix) {
					if (startsWith($trimmedPrefix, $allowedPrefix)) {
						$isAllowed = true;
						break;
					}
				}

				if ($isAllowed) {
					$allowedResult[] = $pendingTranslation;
				}
			}

			if (umiCount($allowedResult) == 0) {
				return null;
			}

			return $allowedResult;
		}

		/**
		 * Возвращает все языковые константы по переводу
		 * @see $this->getI18n()
		 * @param array $translationMap
		 * @param string $key
		 * @return array
		 */
		private static function findAllTranslations(array $translationMap, $key) {
			$key = mb_convert_case($key, MB_CASE_LOWER);
			$result = [];

			foreach ($translationMap as $label => $translation) {
				$translation = mb_convert_case($translation, MB_CASE_LOWER);
				if (contains($translation, $key)) {
					$result[] = self::i18nPrefix . $label;
				}
			}

			return $result;
		}

		/**
		 * Возвращает языковую константу по переводу
		 * @see $this->getI18n()
		 * @param array $translationMap
		 * @param string $key
		 * @param string $typePrefix
		 * @return string
		 */
		private static function findTranslation(array $translationMap, $key, $typePrefix) {
			foreach ($translationMap as $label => $translation) {
				if ($translation == $key) {
					if ($typePrefix && (mb_strpos($label, $typePrefix) !== 0)) {
						continue;
					}

					return self::i18nPrefix . $label;
				}
			}
		}

		/**
		 * Возвращает js-код для работы с константами во фронтенде.
		 * Будут доступны константы с префиксами 'js', 'module' и 'error'.
		 * @param string $path строка с указанием загружаемых файлов констант
		 * @return string
		 */
		protected function generateJavaScriptLabels($path) {
			$translationMap = self::loadI18NFiles($path);
			$modulesList = Service::Registry()->getList('//modules');

			foreach ($modulesList as $moduleName) {
				list($moduleName) = $moduleName;
				if (!isset($translationMap['module-' . $moduleName])) {
					$translationMap['module-' . $moduleName] = self::getLabel('module-' . $moduleName, $moduleName);
				}
			}

			$result = <<<INITJS
function getLabel(key, str) {if(setLabel.langLabels[key]) {var res = setLabel.langLabels[key];if(str) {res = res.replace(/\%s/g, str);}return res;} else {return "[" + key + "]";}}
function setLabel(key, label) {setLabel.langLabels[key] = label;}setLabel.langLabels = new Array();


INITJS;
			foreach ($translationMap as $label => $translation) {
				if (startsWith($label, 'js-') || startsWith($label, 'module-') || startsWith($label, 'error-')) {
					$label = $this->filterOutputString($label);
					$translation = $this->filterOutputString($translation);
					$result .= "setLabel('{$label}', '{$translation}');\n";
				}
			}

			Service::Response()->getCurrentBuffer()
				->option('generation-time', false);
			return $result;
		}

		/**
		 * Экранирует строку
		 * @param string $string
		 * @return mixed
		 */
		protected function filterOutputString($string) {
			$from = ["\r\n", "\n", "'"];
			$to = ["\\r\\n", "\\n", "\\'"];
			return str_replace($from, $to, $string);
		}

		/**
		 * Загружает файл в формате Document type definition
		 * @param string $path путь до файла
		 * @return string
		 */
		protected function getExternalDTD($path) {
			$info = pathinfo(cmsController::getInstance()->getTemplatesDirectory() . $path);
			$left = getArrayKey($info, 'dirname') . '/' . getArrayKey($info, 'filename');
			$right = getArrayKey($info, 'extension');

			$prefix = Service::LanguageDetector()->detectPrefix();
			$primaryPath = $left . '.' . $prefix . '.' . $right;
			$secondaryPath = $left . '.' . $right;

			if (is_file($primaryPath)) {
				return file_get_contents($primaryPath);
			}

			if (is_file($secondaryPath)) {
				return file_get_contents($secondaryPath);
			}

			return '';
		}
	}
