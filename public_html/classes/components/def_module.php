<?php

	use UmiCms\Service;

	/** Базовый класс модулей системы */
	abstract class def_module {

		/** @const string имя файла с системными правилами для автозагрузки классов для модуля */
		const AUTOLOAD_FILE = 'autoload.php';

		/** @const string имя файла с пользовательскими правилами для автозагрузки классов для модуля */
		const CUSTOM_AUTOLOAD_FILE = 'customAutoload.php';

		/** @const string имя файла с подключением необходимых модулю внешних классов */
		const INCLUDES_FILE = 'includes.php';

		/** @const string имя файла с параметрами и правилами сервисов для модуля */
		const SERVICES_FILE = 'services.php';

		/** @const string имя файла с пользовательскими параметрами и правилами сервисов для модуля */
		const CUSTOM_SERVICES_FILE = 'customServices.php';

		/**
		 * @var bool, если true то при возникновении паники
		 * будет выброшено исключение вместо выполнения редиректа
		 */
		public static $noRedirectOnPanic = false;

		/** @var string имя шаблона по умолчанию */
		public static $defaultTemplateName = 'default';

		/** @var null|bool является ли текущий режим режимом xslt */
		public static $xsltResultMode;

		/** @var array список макросов с выводом расширенных полей для XSLT */
		protected static $macrosesXsltExtendedResult = [[], []];

		/** @var array установленные шаблоны системных методов [method => template_id] */
		private static $methodsTemplates = [];

		/** @var int количество элементов, отображаемых на странице */
		public $per_page = 20;

		/** @var string префикс языковой версии */
		public $pre_lang;

		/** @var mixed объект основных вкладок модуля */
		public $common_tabs;

		/** @var mixed объект вкладок настроек модуля */
		public $config_tabs;

		/** @var array список возникших ошибок */
		protected $errors = [];

		/** @var string адрес страницы, на которой возникла ошибка */
		protected $errorPage = '';

		/** @var array загруженные файлы классов */
		private $libs = [];

		/** @var array подключенные классы */
		private $classes = [];

		/** @var array подключенные методы */
		private $methods = [];

		/**
		 * Подключает класс к модулю
		 * @param string $className имя подключаемого класса
		 * @param bool $allowOverriding поддерживается ли перегрузка методов
		 * @return bool
		 */
		public function __implement($className, $allowOverriding = false) {
			try {
				$classReflection = new ReflectionClass($className);

				if (!$classReflection->isInstantiable()) {
					return false;
				}

				$methods = $classReflection->getMethods(ReflectionMethod::IS_PUBLIC);

				if (umiCount($methods) == 0) {
					return false;
				}

				$classInstance = new $className($this);

				if ($classInstance instanceof iModulePart) {
					$classInstance->setModule($this);
				} else {
					$classInstance->module = $this;
				}

				$this->classes[$className] = $classInstance;
				/** @var ReflectionMethod $method */
				foreach ($methods as $method) {
					if (isset($this->methods[$method->getName()]) && !$allowOverriding) {
						continue;
					}

					$this->methods[$method->getName()] = $classInstance;
				}
			} catch (ReflectionException $e) {
				$this->generateWarning($e->getMessage());
				return false;
			}

			return true;
		}

		/**
		 * Возвращает экземпляр подключенного класса
		 * @param string $class имя класса
		 * @return object
		 * @throws coreException
		 */
		public function getImplementedInstance($class) {
			if (!is_string($class) || !$this->isClassImplemented($class)) {
				throw new coreException('Class ' . $class . ' not implemented');
			}

			return $this->classes[$class];
		}

		/**
		 * Возвращает экземпляр основного класса административной панели
		 * @return object
		 * @throws coreException
		 */
		public function getAdminInstance() {
			return $this->getImplementedInstance($this->getAdminClassName());
		}

		/**
		 * Подключен ли класс
		 * @param string $class имя класса
		 * @return bool
		 */
		public function isClassImplemented($class) {
			return isset($this->classes[$class]);
		}

		/**
		 * Устанавливает список дополнительных полей и групп для результатов парсинга макросов
		 * @param array $extProps имена полей
		 * @param array $extGroups имена групп
		 */
		public static function setMacrosExtendedResult($extProps = [], $extGroups = []) {
			self::$macrosesXsltExtendedResult = [$extProps, $extGroups];
		}

		/**
		 * Возвращает список доп полей для результата макроса
		 * @return array массив имен полей
		 */
		public static function getMacrosExtendedProps() {
			return self::$macrosesXsltExtendedResult[0];
		}

		/**
		 * Возвращает список доп групп для результата макроса
		 * @return array массив имен групп
		 */
		public static function getMacrosExtendedGroups() {
			return self::$macrosesXsltExtendedResult[1];
		}

		/**
		 * Алиас def_module::setupTemplate();
		 * @param $method
		 * @return int|null
		 */
		public function getMethodTemplateId($method) {
			return self::setupTemplate($method);
		}

		/**
		 * Удаляет идентификатор шаблона, по которому нужно отрисовать страницу,
		 * данные которой возвращает указанный метод
		 * @param string $method имя метода
		 */
		public function flushMethodTemplateId($method) {
			self::$methodsTemplates[$method] = null;
		}

		/**
		 * Устанавливает идентификатор шаблона, по которому нужно отрисовать страницу,
		 * данные которой возвращает указанный метод.
		 * Возвращает результат операции
		 * @param string $method имя метода
		 * @param int $templateId идентификатор шаблона
		 * @return bool
		 */
		public function setMethodTemplateId($method, $templateId) {
			if (!$this->isMethodExists($method)) {
				return false;
			}

			$umiTemplates = templatesCollection::getInstance();

			if (!$umiTemplates->isExists($templateId)) {
				return false;
			}

			self::$methodsTemplates[$method] = $templateId;

			return true;
		}

		/**
		 * Пытается вызвать метод среди методов подключенных к модулю классов,
		 * @see def_module::__implement()
		 * @param string $method имя метода
		 * @param array $args аргументы вызова
		 * @return mixed|string
		 * @throws coreException
		 */
		public function __call($method, $args) {
			$args = (!is_array($args)) ? [] : $args;

			if (isset($this->methods[$method])) {
				$instance = $this->methods[$method];
				return $this->callIncludedClassMethod($instance, $method, $args);
			}

			$cmsController = cmsController::getInstance();
			$cmsController->setLangConstant(get_class($this), $method, getLabel('label-error'));

			if (Service::Request()->isAdmin()) {
				return getLabel('error-call-non-existent-method');
			}

			if ($cmsController->getCurrentModule() === get_class($this) && $cmsController->getCurrentMethod() === $method) {
				/** @var content|contentMacros $contentModule */
				$contentModule = $cmsController->getModule('content');

				if (!$contentModule instanceof def_module) {
					$this->generateWarning(getLabel('error-content-module-not-found'));
					return '';
				}

				return $contentModule->gen404();
			}

			$this->generateWarning(
				getLabel('error-module-method-not-found') . get_class($this) . '::' . $method
			);

			return '';
		}

		/**
		 * Вызывает метод подключенного @see def_module::__implement() к модулю класса,
		 * @see def_module::__implement().
		 * Вызывает событие до и после вызова подключенного метода.
		 * @param object $instance экземпляр подключенного класса
		 * @param string $method имя метода
		 * @param array $args аргументы вызова
		 * @return mixed
		 * @throws coreException
		 */
		private function callIncludedClassMethod($instance, $method, array $args) {
			$macroEvent = $this->createMacroEvent($instance, $method, $args);
			$macroEvent->call();

			$result = call_user_func_array(
				[
					$macroEvent->getParam('instance'),
					$macroEvent->getParam('method')
				],
				$macroEvent->getParam('args')
			);

			$macroEvent->setMode('after')
				->setParam('result', $result)
				->call();
			return $macroEvent->getParam('result');
		}

		/**
		 * Создает событие вызова метода
		 * @param object $instance экземпляр подключенного класса
		 * @param string $method имя метода
		 * @param array $args аргументы вызова
		 * @return iUmiEventPoint|umiEventPoint
		 */
		private function createMacroEvent($instance, $method, array $args) {
			$moduleName = get_class($this);
			$className = get_class($instance);
			$id = $this->generateMacroEventId($moduleName, $className, $method);
			return Service::EventPointFactory()
				->create($id, 'before', [$moduleName])
				->setParam('instance', $instance)
				->setParam('method', $method)
				->setParam('args', $args);
		}

		/**
		 * Создает идентификатор для события вызова метода
		 * @param string $moduleName имя класса модуля
		 * @param string $className имя подключенного класса
		 * @param string $method имя метода
		 * @return string
		 */
		private function generateMacroEventId($moduleName, $className, $method) {
			return sprintf('%s-%s-%s', $moduleName, $className, $method);
		}

		/**
		 * Возвращает идентификатор шаблона, по которому нужно отрисовать страницу,
		 * данные которой возвращает указанный метод
		 * @param string $method имя метода
		 * @return int|null
		 */
		public static function setupTemplate($method) {
			if (isset(self::$methodsTemplates[$method])) {
				return self::$methodsTemplates[$method];
			}

			return null;
		}

		/**
		 * Конструктор
		 * @throws coreException
		 */
		public function __construct() {
			$this->lang = Service::LanguageDetector()->detectPrefix();
			$class = get_class($this);
			$classFilesPath = SYS_MODULES_PATH . $class . DIRECTORY_SEPARATOR;

			$autoloadFile = $classFilesPath . self::AUTOLOAD_FILE;

			if (file_exists($autoloadFile)) {
				/** переменная наполняется в файле self::AUTOLOAD_FILE */
				$classes = [];
				/** @noinspection PhpIncludeInspection */
				require_once $autoloadFile;
				$classes = $this->prepareClassesForAutoload($classes);
				umiAutoload::addClassesToAutoload($classes, $class);
			}

			$autoloadCustomFile = $classFilesPath . self::CUSTOM_AUTOLOAD_FILE;

			if (file_exists($autoloadCustomFile)) {
				/** переменная наполняется в файле self::CUSTOM_AUTOLOAD_FILE */
				$classes = [];
				/** @noinspection PhpIncludeInspection */
				require_once $autoloadCustomFile;
				umiAutoload::addClassesToAutoload($classes, $class);
			}

			$includesFile = $classFilesPath . self::INCLUDES_FILE;

			if (file_exists($includesFile)) {
				require_once $includesFile;
			}

			$servicesFile = $classFilesPath . self::SERVICES_FILE;

			if (file_exists($servicesFile)) {
				/** переменные наполняются в файле self::SERVICES_FILE */
				$rules = [];
				$parameters = [];
				/** @noinspection PhpIncludeInspection */
				require_once $servicesFile;
				$serviceContainer = ServiceContainerFactory::create();
				$serviceContainer->addRules($rules);
				$serviceContainer->addParameters($parameters);
			}

			$customServicesFile = $classFilesPath . self::CUSTOM_SERVICES_FILE;

			if (file_exists($customServicesFile)) {
				/** переменные наполняются в файле self::CUSTOM_SERVICES_FILE */
				$rules = [];
				$parameters = [];
				/** @noinspection PhpIncludeInspection */
				require_once $customServicesFile;
				$serviceContainer = ServiceContainerFactory::create();
				$serviceContainer->addRules($rules);
				$serviceContainer->addParameters($parameters);
			}
		}

		/**
		 * Метод можно переопределить в конкретном модуле,
		 * чтобы изменить классы для подключения к автозагрузке
		 * @param array $classes классы для автозагрузки
		 * @return array
		 */
		protected function prepareClassesForAutoload($classes) {
			return $classes;
		}

		/**
		 * Возвращает список имен модулей системы
		 * с соответствующими значениями индексами их сортировки
		 * @return array
		 */
		public function getSortedModulesList() {
			$priorityList = [
				// user modules (priority < 100)
				'events' => 1,
				'content' => 2,
				'news' => 3,
				'menu' => 4,
				'forum' => 5,
				'blogs20' => 6,
				'vote' => 7,
				'comments' => 8,
				'photoalbum' => 9,
				'webforms' => 10,
				'dispatches' => 11,
				'faq' => 12,
				'eshop' => 13,
				'emarket' => 14,
				'catalog' => 15,
				'users' => 16,
				'banners' => 17,
				'seo' => 18,
				'stat' => 19,
				'social_networks' => 20,
				'exchange' => 21,
				// administrative modules (priority > 100)
				'data' => 101,
				'config' => 102,
				'backup' => 103,
				'autoupdate' => 104,
				'search' => 106,
				'filemanager' => 107,
				'trash' => 999,
			];

			$sysModules = ['config', 'filemanager', 'backup', 'autoupdate', 'data'];
			$utilModules = ['umiRedirects', 'umiNotifications', 'umiSettings', 'trash', 'search', 'umiStub'];

			$modulesList = Service::Registry()->getList('//modules');
			$permissions = permissionsCollection::getInstance();

			$result = [];
			foreach ($modulesList as $module) {
				list($module) = $module;
				if (!$permissions->isAllowedModule(false, $module)) {
					continue;
				}
				$priority = isset($priorityList[$module]) ? $priorityList[$module] : 99;
				$result[$module] = $priority;
			}

			natsort($result);

			foreach ($result as $module => $priority) {
				if (in_array($module, $sysModules)) {
					$type = 'system';
				} else {
					if (in_array($module, $utilModules)) {
						$type = 'util';
					} else {
						$type = null;
					}
				}

				$moduleInfo = [];
				$moduleInfo['name'] = $module;
				$moduleInfo['label'] = getLabel('module-' . $module);
				$moduleInfo['type'] = $type;

				$result[$module] = $moduleInfo;
			}

			return $result;
		}

		/**
		 * Возвращает объект основных вкладок модуля
		 * @return adminModuleTabs|bool|mixed
		 */
		public function getCommonTabs() {
			$cmsController = cmsController::getInstance();
			$currentModule = $cmsController->getCurrentModule();
			$selfModule = get_class($this);

			if (($currentModule != $selfModule) && ($currentModule != false && $selfModule != 'users')) {
				return false;
			}
			if (!$this->common_tabs instanceof adminModuleTabs) {
				$this->common_tabs = new adminModuleTabs();
			}
			return $this->common_tabs;
		}

		/**
		 * Возвращает объект вкладок настроек модуля
		 * @return adminModuleTabs|bool|mixed
		 */
		public function getConfigTabs() {
			$cmsController = cmsController::getInstance();

			if ($cmsController->getCurrentModule() != get_class($this)) {
				return false;
			}

			if (!$this->config_tabs instanceof adminModuleTabs) {
				$this->config_tabs = new adminModuleTabs();
			}

			return $this->config_tabs;
		}

		/**
		 * Возвращает имя первой доступной вкладки модуля
		 * @return string
		 * @throws publicAdminException
		 */
		public function getFirstAllowedTabName() {
			$moduleName = get_class($this);
			$firstTabName = null;
			$commonTabList = ($this->getCommonTabs() instanceof iAdminModuleTabs) ? $this->getCommonTabs()->getRealAll() : [];

			foreach ($commonTabList as $tabName => $methodAliases) {
				if (!system_is_allowed($moduleName, $tabName)) {
					continue;
				}

				$firstTabName = $tabName;
				break;
			}

			if ($firstTabName !== null) {
				return $firstTabName;
			}

			$configTabList = ($this->getConfigTabs() instanceof iAdminModuleTabs) ? $this->getConfigTabs()->getRealAll() : [];

			foreach ($configTabList as $tabName => $methodAliases) {
				if (!system_is_allowed($moduleName, $tabName)) {
					continue;
				}

				$firstTabName = $tabName;
				break;
			}

			if ($firstTabName !== null) {
				return $firstTabName;
			}

			throw new publicAdminException(getLabel('error-cannot-detect-allowed-tab', false, $moduleName));
		}

		/**
		 * Вызывает метод модуля
		 * @param string $method_name имя метода
		 * @param array $args аргументы для вызываемого метода
		 * @return mixed
		 */
		public function cms_callMethod($method_name, $args) {
			if (!$method_name) {
				return;
			}

			$aArguments = [];
			if (USE_REFLECTION_EXT && class_exists('ReflectionMethod')) {
				try {
					$oReflection = new ReflectionMethod($this, $method_name);
					$iNeedArgCount = max($oReflection->getNumberOfRequiredParameters(), umiCount($args));
					if ($iNeedArgCount) {
						$aArguments = array_fill(0, $iNeedArgCount, 0);
					}
				} catch (Exception $e) {
				}
			}

			for ($i = 0; $i < umiCount($args); $i++) {
				$aArguments[$i] = $args[$i];
			}

			if (umiCount($aArguments) && !(empty($args[0]) && umiCount($args) === 1)) {
				return call_user_func_array([$this, $method_name], $aArguments);
			}

			return $this->$method_name();
		}

		/** Подключает кастомы из шаблонов */
		public function loadTemplateCustoms() {
			$resourcesDir = cmsController::getInstance()
				->getResourcesDirectory();
			if (!$resourcesDir) {
				return;
			}

			$customDir = $resourcesDir . '/classes/modules';
			if (!is_dir($customDir)) {
				return;
			}

			$includesFile = realpath($customDir) . '/' . get_class($this) . '/class.php';

			if (!file_exists($includesFile)) {
				return;
			}

			require_once $includesFile;
			$className = get_class($this) . '_custom';

			if (!$this->isClassImplemented($className)) {
				$this->__implement($className, true);
			}
		}

		/**
		 * Производит загрузку общих файлов расширений.
		 * Загрузка производится из директории [имя_модуля]/ext с учётом префикса файлов.
		 */
		public function loadCommonExtension() {
			Service::ExtensionLoader()
				->setModule($this)
				->loadCommon();
		}

		/**
		 * Производит загрузку админских файлов расширений.
		 * Загрузка производится из директории [имя_модуля]/ext с учётом префикса файлов.
		 */
		public function loadAdminExtension() {
			if (Service::Request()->isNotAdmin()) {
				return;
			}

			Service::ExtensionLoader()
				->setModule($this)
				->loadAdmin();
		}

		/**
		 * Производит загрузку сайтовых файлов расширений и подключение событий.
		 * Загрузка производится из директории [имя_модуля]/ext с учётом префикса файлов.
		 */
		public function loadSiteExtension() {
			if (Service::Request()->isAdmin()) {
				return;
			}

			Service::ExtensionLoader()
				->setModule($this)
				->loadSite();
		}

		/**
		 * Устанавливает модуль
		 * @param array $INFO параметры установки модуля
		 */
		public static function install($INFO) {
			$xpath = '//modules/' . $INFO['name'];
			$regedit = Service::Registry();

			$regedit->set($xpath, $INFO['name']);

			if (is_array($INFO)) {
				foreach ($INFO as $var => $module_param) {
					$val = $module_param;
					$regedit->set($xpath . '/' . $var, $val);
				}
			}
		}

		/** Производит удаление текущего модуля */
		public function uninstall() {
			$className = get_class($this);
			$regedit = Service::Registry();
			$defaultModuleAdmin = $regedit->get('//settings/default_module_admin');

			if ($defaultModuleAdmin == $className) {
				$regedit->set('//settings/default_module_admin', 'content');
			}

			$regedit->delete('//modules/' . $className);
		}

		/**
		 * Алиас simpleRedirect()
		 * @param string $url адрес
		 * @param bool $ignoreErrorParam нужно ли удалить параметр с отсылкой на ошибку из адреса
		 * @throws coreException
		 */
		public function redirect($url, $ignoreErrorParam = true) {
			self::simpleRedirect($url, $ignoreErrorParam);
		}

		/**
		 * Производит перенаправление на указанный адрес
		 * @param string $url адрес
		 * @param bool $ignoreErrorParam нужно ли удалить параметр с отсылкой на ошибку из адреса
		 */
		public static function simpleRedirect($url, $ignoreErrorParam = true) {
			if (getRequest('redirect_disallow')) {
				return;
			}

			if (!$url) {
				$url = cmsController::getInstance()->getPreLang() . '/';
			}

			if ($ignoreErrorParam) {
				$url = self::removeErrorCodeFromUrl($url);
			}

			umiHierarchy::getInstance()->__destruct();
			Service::Response()
				->getCurrentBuffer()
				->redirect($url);
		}

		/**
		 * При необъходимости добавляет суффикс или слэш к url-адресу
		 * и производит редирект
		 * @throws coreException
		 */
		public static function requireSlashEnding() {
			if (getRequest('is_app_user') !== null) {
				return;
			}

			if (getRequest('xmlMode') === 'force' || umiCount($_POST) > 0) {
				return;
			}

			if (getRequest('jsonMode') === 'force' || umiCount($_POST) > 0) {
				return;
			}

			$uri = getServer('REQUEST_URI');

			if ($uri == '/') {
				return;
			}
			$uriInfo = parse_url($uri);

			if (Service::Request()->isAdmin()) {
				if (mb_substr($uriInfo['path'], -1, 1) != '/') {
					$uri = $uriInfo['path'] . '/';
					if (isset($uriInfo['query']) && $uriInfo['query']) {
						$uri .= '?' . $uriInfo['query'];
					}
					self::simpleRedirect($uri);
				}
				return;
			}

			$urlSuffix = mainConfiguration::getInstance()->get('seo', 'url-suffix');

			if ($urlSuffix) {
				$pos = mb_strrpos($uriInfo['path'], $urlSuffix);
				if ($pos === false || !($pos + mb_strlen($urlSuffix) == mb_strlen($uriInfo['path']))) {
					if ($uriInfo['path'] == '/') {
						return;
					}

					$uri = rtrim($uriInfo['path'], '/') . $urlSuffix;
					if (isset($uriInfo['query']) && $uriInfo['query']) {
						$uri .= '?' . $uriInfo['query'];
					}
					self::simpleRedirect($uri);
				}
			}
		}

		/**
		 * Изменяет текущий хедер модуля
		 * @param string $header новый хедер
		 */
		public function setHeader($header) {
			$cmsControllerInstance = cmsController::getInstance();
			$cmsControllerInstance->currentHeader = $header;
		}

		/**
		 * Загружает файла класса для последующей имлементации его в модуле
		 * @param string $file имя подключаемого файла
		 * @param string $path путь до файла
		 * @param string|null $parentClassName имя классса, в рамках которого загружается файл класса
		 * @return bool
		 */
		public function __loadLib($file, $path = '', $parentClassName = null) {
			$parentClassName = ($parentClassName === null) ? get_class($this) : $parentClassName;
			$filePath = $path ? $path . $file : SYS_MODULES_PATH . $parentClassName . '/' . $file;

			if (!file_exists($filePath)) {
				$this->generateWarning(getLabel('error-cannot-load-lib') . $filePath);
				return false;
			}

			if (!isset($this->libs[$filePath])) {
				require_once $filePath;
				$this->libs[$filePath] = true;
			}

			return true;
		}

		/**
		 * Генерирует php предупреждение с заданным текстом
		 * @param string $message текст предупреждения
		 * @return $this
		 */
		protected function generateWarning($message) {
			trigger_error($message, E_USER_WARNING);
			return $this;
		}

		/**
		 * Изменяет текущий заголовок модуля
		 * @param string $title новый заголовок
		 * @param int $mode , если не 0, то значение заголовка будет получено из реестра
		 */
		protected function setTitle($title = '', $mode = 0) {
			$cmsControllerInstance = cmsController::getInstance();
			$umiRegistry = Service::Registry();

			if ($title) {
				if ($mode) {
					$cmsControllerInstance->currentTitle =
						$umiRegistry->get('//domains/' . $_REQUEST['domain'] . '/title_pref_' . $_REQUEST['lang']) . $title;
				} else {
					$cmsControllerInstance->currentTitle = $title;
				}
			} else {
				$cmsControllerInstance->currentTitle = $cmsControllerInstance->currentHeader;
			}
		}

		/**
		 * Алиас setHeader()
		 * @param string $h1 новый хедер
		 */
		protected function setH1($h1) {
			$this->setHeader($h1);
		}

		/**
		 * Выводит сообщение и завершает выполнение скрипта
		 * @param string $output сообщение для вывода
		 * @param bool|string $contentType значение для заголовка Content-type
		 */
		public function flush($output = '', $contentType = false) {
			$buffer = Service::Response()
				->getCurrentBuffer();

			if (is_string($contentType) && !empty($contentType)) {
				$buffer->contentType($contentType);
			}

			$buffer->push($output);
			$buffer->end();
		}

		/**
		 * @static
		 * Загружает шаблоны, используя шаблонизатор в зависимости от режима работы макросов,
		 * возвращает запрошенные блоки
		 * @param string $filePath - путь к источнику шаблонов
		 * @return array
		 * @throws coreException
		 */
		public static function loadTemplates($filePath = '') {
			$args = func_get_args();

			$templater = self::isXSLTResultMode() ? 'umiTemplaterXSLT' : 'umiTemplaterTPL';

			if (!self::isXSLTResultMode() && !is_file($filePath)) {
				$cmsController = cmsController::getInstance();
				// получаем полный путь к tpl-шаблону
				$defaultLang = Service::LanguageCollection()->getDefaultLang();
				$currentLang = Service::LanguageDetector()->detect();
				$resourcesDir = $cmsController->getResourcesDirectory();

				$langPrefix = '';
				if ($defaultLang && $currentLang && ($defaultLang->getId() != $currentLang->getId())) {
					$langPrefix = (string) $currentLang->getPrefix();
				}

				if (mb_substr($filePath, -4) === '.tpl') {
					$filePath = mb_substr($filePath, 0, -4);
				}

				$files = [];
				if (Service::Request()->isMobile()) {
					$pathArray = explode('/', $filePath);
					$mobileFilePath = '/mobile/' . array_pop($pathArray);
					$mobileFilePath = implode('/', $pathArray) . $mobileFilePath;
					if ($langPrefix !== '') {
						$files[] = $mobileFilePath . '.' . $langPrefix;
					}
					$files[] = $mobileFilePath;
				}

				if ($langPrefix !== '') {
					$files[] = $filePath . '.' . $langPrefix;
				}
				$files[] = $filePath;

				$dir = rtrim($resourcesDir ?: CURRENT_WORKING_DIR, '/') . '/tpls/';

				foreach ($files as $filePath) {
					$filePath = $dir . $filePath . '.tpl';
					if (is_file($filePath)) {
						break;
					}
				}

				$args[0] = $filePath;
			}

			return call_user_func_array([$templater, 'getTemplates'], $args);
		}

		/**
		 * Загружает шаблоны для формирования писем.
		 *
		 * Сначала пытается загрузить XSLT-шаблон, если шаблон не найден, пытается загрузить TPL-шаблон.
		 * Для каждого вида шаблонов метод пытается сначала загрузить шаблон из директории готового решения,
		 * а если подходящего файла нет - то из системных директорий.
		 *
		 * @param string $filePath относительный путь до шаблона
		 * @return array - массив шаблонов
		 * @throws coreException
		 */
		public static function loadTemplatesForMail($filePath = '') {
			$xslPath = self::getXslMailTemplatePath($filePath);
			$tplPath = self::getTplMailTemplatePath($filePath);
			$templaterClass = null;

			if (is_file($xslPath)) {
				$templaterClass = 'umiTemplaterXSLT';
				$sourcePath = $xslPath;
			} elseif (is_file($tplPath)) {
				$templaterClass = 'umiTemplaterTPL';
				$sourcePath = $tplPath;
			} else {
				throw new coreException(getLabel('error-cannot-connect-mail-template') . ' ' . $filePath, 2);
			}

			$args = func_get_args();
			$args[0] = $sourcePath;

			return call_user_func_array([$templaterClass, 'getTemplates'], $args);
		}

		/**
		 * Возвращает полный путь до xsl-шаблона писем, если он существует
		 * @param string $filePath относительный путь до файла
		 * @return null|string
		 */
		private static function getXslMailTemplatePath($filePath) {
			$xslFilePath = $filePath;
			if (contains($xslFilePath, 'mail')) {
				$xslFilePath = str_replace(['mail/', 'mails/'], ['', ''], $xslFilePath);
			}

			$fullPath = null;
			$resourcesDir = cmsController::getInstance()->getResourcesDirectory();

			if ($resourcesDir) {
				$fullPath = "{$resourcesDir}/xslt/mail/{$xslFilePath}.xsl";
			}

			if (!is_file($fullPath)) {
				$fullPath = CURRENT_WORKING_DIR . "/xsltTpls/mail/{$xslFilePath}.xsl";
			}

			return $fullPath;
		}

		/**
		 * Возвращает полный путь до tpl-шаблона писем, если он существует
		 * @param string $filePath относительный путь до файла
		 * @return null|string
		 */
		private static function getTplMailTemplatePath($filePath) {
			$tplFilePath = $filePath;

			if (mb_substr($tplFilePath, -4) === '.tpl') {
				$tplFilePath = mb_substr($tplFilePath, 0, -4);
			}

			$fullPath = null;
			$resourcesDir = cmsController::getInstance()->getResourcesDirectory();

			if ($resourcesDir) {
				$fullPath = "{$resourcesDir}/tpls/{$tplFilePath}.tpl";
			}

			if (!is_file($fullPath)) {
				$fullPath = CURRENT_WORKING_DIR . "/tpls/{$tplFilePath}.tpl";
			}

			if (!is_file($fullPath)) {
				$fullPath = CURRENT_WORKING_DIR . "/tpls/mail/{$tplFilePath}.tpl";
			}

			return $fullPath;
		}

		/**
		 * @static
		 * Обрабатывает TPL - макросы в контенте, используя TPL-шаблонизатор
		 *
		 * @param string $content
		 * @param mixed $scopeElementId - id страницы в качестве области видимости блока
		 * @param mixed $scopeObjectId - id объекта в качестве области видимости блока
		 * @param array $parseVariables - переменные, для парсинга контента
		 * @return string
		 * @throws coreException
		 * @throws ErrorException
		 */
		public static function parseTPLMacroses(
			$content,
			$scopeElementId = false,
			$scopeObjectId = false,
			$parseVariables = []
		) {
			if (!contains($content, '%')) {
				return $content;
			}

			$tplTemplater = umiTemplater::create('TPL');
			$tplTemplater->setScope($scopeElementId, $scopeObjectId);
			return $tplTemplater->parse($parseVariables, $content);
		}

		/**
		 * @static
		 * Выполняет разбор шаблона, используя необходимый шаблонизатор в зависимости от режима работы макросов
		 *
		 * @param mixed $template - шаблон для разбора
		 * @param array $arr - массив переменнх
		 * @param bool|int $parseElementPropsId - установить id страницы в качестве области видимости блока
		 * @param bool|int $parseObjectPropsId - установить id объекта в качестве области видимости блока
		 * @param null|bool $xsltResultMode - принудительно устанавливает режим работы макросов перед разбором
		 * и восстанавливает предыдущий режим работы в конце работы
		 * @return mixed - результат разбора шаблона
		 * @throws coreException
		 * @throws ErrorException
		 */
		public static function parseTemplate(
			$template,
			$arr,
			$parseElementPropsId = false,
			$parseObjectPropsId = false,
			$xsltResultMode = null
		) {
			if (!is_array($arr)) {
				$arr = [];
			}

			$oldResultMode = null;
			if (is_bool($xsltResultMode)) {
				$oldResultMode = self::isXSLTResultMode($xsltResultMode);
			}
			if (self::isXSLTResultMode()) {
				$result = [];
				$extProps = self::getMacrosExtendedProps();
				$extGroups = self::getMacrosExtendedGroups();
				if ((!empty($extProps) || !empty($extGroups)) && ($parseElementPropsId || $parseObjectPropsId)) {
					if ($parseElementPropsId) {
						$entity = umiHierarchy::getInstance()->getElement($parseElementPropsId);
						if ($entity) {
							$entity = $entity->getObject();
						}
					} else {
						$entity = umiObjectsCollection::getInstance()->getObject($parseObjectPropsId);
					}
					/** @var iUmiObject $entity */
					if ($entity) {
						$extPropsInfo = [];
						foreach ($extProps as $fieldName) {
							if ($fieldName == 'name' && !isset($arr['attribute:name'], $arr['@name'])) {
								$arr['@name'] = $entity->getName();
							} elseif ($extProp = $entity->getPropByName($fieldName)) {
								$extPropsInfo[] = $extProp;
							}
						}
						if (umiCount($extPropsInfo)) {
							if (!isset($arr['extended'])) {
								$arr['extended'] = [];
							}
							$arr['extended']['properties'] = ['+property' => $extPropsInfo];
						}

						$extGroupsInfo = [];
						foreach ($extGroups as $groupName) {
							$group = $entity->getType()->getFieldsGroupByName($groupName);

							if ($group) {
								$groupWrapper = translatorWrapper::get($group);
								$extGroupsInfo[] = $groupWrapper->translateProperties($group, $entity);
							}
						}

						if (umiCount($extGroupsInfo)) {
							if (!isset($arr['extended'])) {
								$arr['extended'] = [];
							}
							$arr['extended']['groups'] = ['+group' => $extGroupsInfo];
						}
					}
				}
				$keysCache = &xmlTranslator::$keysCache;
				foreach ($arr as $key => $val) {
					if ($val === null || $val === false || $val === '') {
						continue;
					}
					if (is_array($val)) {
						$val = self::parseTemplate($template, $val);
					}
					if (!isset($keysCache[$key])) {
						$keysCache[$key] = xmlTranslator::getKey($key);
					}
					list($subKey, $realKey) = $keysCache[$key];
					if ($subKey === 'subnodes') {
						$result[$realKey] = [
							'nodes:item' => $val
						];
						continue;
					}

					$result[$key] = $val;
				}
				return $result;
			}

			$templater = umiTemplater::create('TPL');
			$variables = [];
			foreach ($arr as $m => $v) {
				$m = self::getRealKey($m);

				if (is_array($v)) {
					$res = '';
					$v = array_values($v);
					$sz = umiCount($v);
					for ($i = 0; $i < $sz; $i++) {
						$str = $v[$i];

						$listClassFirst = ($i == 0) ? 'first' : '';
						$listClassLast = ($i == $sz - 1) ? 'last' : '';
						$listClassOdd = (($i + 1) % 2 == 0) ? 'odd' : '';
						$listClassEven = $listClassOdd ? '' : 'even';
						$listPosition = ($i + 1);
						$listComma = $listClassLast ? '' : ', ';

						$from = [
							'%list-class-first%',
							'%list-class-last%',
							'%list-class-odd%',
							'%list-class-even%',
							'%list-position%',
							'%list-comma%'
						];
						$to = [
							$listClassFirst,
							$listClassLast,
							$listClassOdd,
							$listClassEven,
							$listPosition,
							$listComma
						];
						$t_res = str_replace($from, $to, $str);
						$res .= is_array($t_res) ? implodeRecursively('', $t_res) : $t_res;
					}
					$v = $res;
				}
				if (!is_object($v)) {
					$variables[$m] = $v;
				}
			}
			$arr = $variables;
			$templater->setScope($parseElementPropsId, $parseObjectPropsId);

			$result = $templater->parse($arr, $template);
			$result = $templater->replaceCommentsAfterParse($result);

			if ($oldResultMode !== null) {
				self::isXSLTResultMode($oldResultMode);
			}

			return $result;
		}

		/**
		 * Сокращенная запись loadTemplate/parseTemplate
		 *
		 * @param string $template Файл шаблона
		 * @param string $block Блок вывода
		 * @param array $blockArray Значения
		 * @param bool|int $elementId Элемент
		 *
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		public static function renderTemplate($template, $block, $blockArray = [], $elementId = false) {
			list($tpl) = def_module::loadTemplates($template, $block);
			return def_module::parseTemplate($tpl, $blockArray, $elementId);
		}

		/**
		 * @static
		 * Выполняет разбор шаблона для отправки письма
		 * Если в template пришел URI шаблона, для обработки используется umiTemplaterXSTL
		 * @param string $template - шаблон для разбора
		 * @param array $arr - массив переменных
		 * @param bool|int $parseElementPropsId - установить id страницы в качестве области видимости блока
		 * @param bool|int $parseObjectPropsId - установить id объекта в качестве области видимости блока
		 * @return mixed - результат разбора шаблона
		 * @throws publicException
		 * @throws coreException
		 * @throws ErrorException
		 */
		public static function parseTemplateForMail(
			$template,
			$arr = [],
			$parseElementPropsId = false,
			$parseObjectPropsId = false
		) {
			if (startsWith($template, 'file://')) {
				// Используем xslt-шаблонизатор
				$templateURL = @parse_url($template);

				if (!is_array($templateURL)) {
					throw new publicException(getLabel('error-cannot-process-template') . $template);
				}

				$templateSource = $templateURL['path'];
				$templateFragment = (isset($templateURL['fragment']) && mb_strlen($templateURL['fragment']))
					? $templateURL['fragment']
					: 'result';

				$templater = umiTemplater::create('XSLT', $templateSource);
				return $templater->parse([
					$templateFragment => $arr
				]);
			}

			// Используем tpl-шаблонизатор
			return def_module::parseTemplate($template, $arr, $parseElementPropsId, $parseObjectPropsId, false);
		}

		/**
		 * Возвращает часть строки до или после разделителя
		 * @param string $key исходная строка
		 * @param bool $reverse , если true, то будет возвращена строка перед разделителем,
		 * если false, то после разделителя
		 * @return string
		 */
		public static function getRealKey($key, $reverse = false) {
			$shortKeys = ['@', '#', '+', '%', '*'];

			if (in_array(mb_substr($key, 0, 1), $shortKeys)) {
				return mb_substr($key, 1);
			}

			$pos = mb_strpos($key, ':');

			if ($pos) {
				++$pos;
			} else {
				$pos = 0;
			}

			return $reverse ? mb_substr($key, 0, $pos - 1) : mb_substr($key, $pos);
		}

		/**
		 * Форматирует сообщение форума
		 * @param string $message исходное сообщение
		 * @param int $bSplitLongMode , если 0, то слишком длинные слова в сообщении
		 * будут разделены
		 * @return mixed|string
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function formatMessage($message, $bSplitLongMode = 0) {
			static $bb_from;
			static $bb_to;

			$oldResultTMode = $this->isXSLTResultMode(false);

			try {
				list($quote_begin, $quote_end) = $this->loadTemplates('quote/default', 'quote_begin', 'quote_end');
			} catch (publicException $e) {
				$quote_begin = "<div class='quote'>";
				$quote_end = '</div>';
			}

			if (self::isXSLTResultMode()) {
				$quote_begin = "<div class='quote'>";
				$quote_end = '</div>';
			}

			if (!(is_array($bb_from) && is_array($bb_to) && umiCount($bb_from) === umiCount($bb_to))) {
				try {
					list($bb_from, $bb_to) = $this->loadTemplates('bb/default', 'bb_from', 'bb_to');
					if (!(is_array($bb_from) && is_array($bb_to) && umiCount($bb_from) === umiCount($bb_to) &&
						umiCount($bb_to))) {
						$bb_from = [
							'[b]',
							'[i]',
							'[/b]',
							'[/i]',
							'[quote]',
							'[/quote]',
							'[u]',
							'[/u]',
							"\r\n"
						];

						$bb_to = [
							'<strong>',
							'<em>',
							'</strong>',
							'</em>',
							$quote_begin,
							$quote_end,
							'<u>',
							'</u>',
							'<br />'
						];
					}
				} catch (publicException $e) {
					$bb_from = [
						'[b]',
						'[i]',
						'[/b]',
						'[/i]',
						'[quote]',
						'[/quote]',
						'[u]',
						'[/u]',
						"\r\n"
					];

					$bb_to = [
						'<strong>',
						'<em>',
						'</strong>',
						'</em>',
						$quote_begin,
						$quote_end,
						'<u>',
						'</u>',
						'<br />'
					];
				}
			}

			$openQuoteCount = mb_substr_count(mb_strtolower($message), '[quote]');
			$closeQuoteCount = mb_substr_count(mb_strtolower($message), '[/quote]');

			if ($openQuoteCount > $closeQuoteCount) {
				$message .= str_repeat('[/quote]', $openQuoteCount - $closeQuoteCount);
			}
			if ($openQuoteCount < $closeQuoteCount) {
				$message = str_repeat('[quote]', $closeQuoteCount - $openQuoteCount) . $message;
			}

			$message = preg_replace(
				"`((http)+(s)?:(//)|(www\.))((\w|\.|\-|_)+)(/)?([/|#|?|&|=|\w|\.|\-|_]+)?`i",
				"[url]http\\3://\\5\\6\\8\\9[/url]",
				$message
			);

			$message = str_ireplace($bb_from, $bb_to, $message);
			$message = str_ireplace('</h4>', '</h4><p>', $message);
			$message = str_ireplace('</div>', '</p></div>', $message);

			$message = str_replace('.[/url]', '[/url].', $message);
			$message = str_replace(',[/url]', '[/url],', $message);

			$message = str_replace(['[url][url]', '[/url][/url]'], ['[url]', '[/url]'], $message);

			// split long words
			if ($bSplitLongMode === 0) { // default
				$arr_matches = [];
				$b_succ = preg_match_all("/[^\s^<^>]{70,}/u", $message, $arr_matches);
				if ($b_succ && isset($arr_matches[0]) && is_array($arr_matches[0])) {
					foreach ($arr_matches[0] as $str) {
						$s = '';
						if (!contains($str, '[url]')) {
							for ($i = 0; $i < mb_strlen($str); $i++) {
								$s .= mb_substr($str, $i, 1) . (($i % 30) === 0 ? ' ' : '');
							}
							$message = str_replace($str, $s, $message);
						}
					}
				}
			}

			if (preg_match_all("/\[url\]([^А-я^\r^\n^\t]*)\[\/url\]/U", $message, $matches, PREG_SET_ORDER)) {
				for ($i = 0; $i < umiCount($matches); $i++) {
					$s_url = $matches[$i][1];
					$i_length = mb_strlen($s_url);
					if ($i_length > 40) {
						$i_cutpart = ceil(($i_length - 40) / 2);
						$i_center = ceil($i_length / 2);

						$s_url = substr_replace($s_url, '...', $i_center - $i_cutpart, $i_cutpart * 2);
					}
					$message = str_replace(
						$matches[$i][0],
						"<a href='/go-out.php?url=" . $matches[$i][1] . "' target='_blank' title='" .
						getLabel('link-will-open-in-new-window') . "'>" . $s_url . '</a>',
						$message
					);
				}
			}

			$message = str_replace('&', '&amp;', $message);

			$message = str_ireplace('[QUOTE][QUOTE]', '', $message);

			if (preg_match_all("/\[smile:([^\]]+)\]/im", $message, $out)) {
				foreach ($out[1] as $smile_path) {
					$s = $smile_path;
					$smile_path = 'images/forum/smiles/' . $smile_path . '.gif';
					if (file_exists($smile_path)) {
						$message = str_replace('[smile:' . $s . ']', "<img src='/{$smile_path}' />", $message);
					}
				}
			}

			$message = preg_replace("/<p>(<br \/>)+/", '<p>', $message);
			$message = nl2br($message);
			$message = str_replace('<<br />br /><br />', '', $message);
			$message = str_replace('<p<br />>', '<p>', $message);

			$message = str_replace('&amp;quot;', '"', $message);
			$message = str_replace('&amp;quote;', '"', $message);
			$message = html_entity_decode($message);
			$message = str_replace('%', '&#37;', $message);

			$message = $this->parseTPLMacroses($message);

			$this->isXSLTResultMode($oldResultTMode);
			return $message;
		}

		/**
		 * Производит анализ переданного пути и возвращает ID соответствующей страницы,
		 * если такая существует
		 * @param int|string $pathOrId ID страницы или путь до нее
		 * @param bool|true $returnCurrentIfVoid , если true и не передан первый параметр,
		 * то будет возвращен ID текущей страницы
		 * @return bool|false|int|string|array
		 */
		public function analyzeRequiredPath($pathOrId, $returnCurrentIfVoid = true) {

			$umiHierarchy = umiHierarchy::getInstance();

			if (is_numeric($pathOrId)) {
				if ($pathOrId == 0) {
					return (int) $pathOrId;
				}

				if ($umiHierarchy->isLoaded($pathOrId)) {
					return (int) $pathOrId;
				}

				return $umiHierarchy->isExists($pathOrId) ? (int) $pathOrId : false;
			}

			$pathOrId = trim($pathOrId);

			if ($pathOrId) {
				if (!contains($pathOrId, ' ')) {
					return $umiHierarchy->getIdByPath($pathOrId);
				}

				$paths_arr = explode(' ', $pathOrId);

				$ids = [];

				foreach ($paths_arr as $subpath) {
					$id = $this->analyzeRequiredPath($subpath, false);

					if ($id === false) {
						continue;
					}

					$ids[] = $id;
				}

				if (umiCount($ids) > 0) {
					return $ids;
				}

				return false;
			}

			if ($returnCurrentIfVoid) {
				$cmsController = cmsController::getInstance();
				return $cmsController->getCurrentElementId();
			}

			return false;
		}

		/**
		 * Проверяет переданы ли POST-параметры
		 * @param bool|true $bRedirect , еслп true, то в случае успешной проверки
		 * будет произведен редирект в административную панель
		 * @return bool результат проверки
		 */
		public function checkPostIsEmpty($bRedirect = true) {
			$bResult = !is_array($_POST) || (is_array($_POST) && !umiCount($_POST));
			if ($bResult && $bRedirect) {
				$url = preg_replace("/(\r)|(\n)/", '', $_REQUEST['pre_lang']) . '/admin/';
				Service::Response()
					->getCurrentBuffer()
					->redirect($url);
			} else {
				return $bResult;
			}
		}

		/**
		 * @param umiEventPoint $eventPoint
		 * @throws Exception
		 * @throws baseException
		 */
		public static function setEventPoint(umiEventPoint $eventPoint) {
			umiEventsController::getInstance()->callEvent($eventPoint);
		}

		/**
		 * @deprecated
		 * @return bool
		 */
		public function breakMe() {
			return false;
		}

		/**
		 * Записывает адрес страницы, на которой произошла ошибка
		 * @param string $errorUrl url адрес страницы
		 */
		public function errorRegisterFailPage($errorUrl) {
			cmsController::getInstance()->errorUrl = $errorUrl;
		}

		/**
		 * Записывает сообщение об ошибке
		 * @param string $errorMessage сообщение об ошибке
		 * @param bool|true $causePanic , если true, то будет произведен редирект на текущую страницу,
		 * но с передачей параметра, сигнализирующего об ошибке
		 * @param bool|int $errorCode числовое представление кода ошибки
		 * @param bool|string $errorStrCode строковое представление кода ошибки
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 * @throws ErrorException
		 */
		public function errorNewMessage($errorMessage, $causePanic = true, $errorCode = false, $errorStrCode = false) {
			$controller = cmsController::getInstance();
			$requestId = 'errors_' . $controller->getRequestId();
			$errorMessage = def_module::parseTPLMacroses($errorMessage);

			$session = Service::Session();
			$requestErrors = $session->get($requestId);
			$requestErrors = is_array($requestErrors) ? $requestErrors : [];
			$requestErrors[] = [
				'message' => $errorMessage,
				'code' => $errorCode,
				'strcode' => $errorStrCode
			];

			$session->set($requestId, $requestErrors);

			if ($causePanic) {
				$this->errorPanic();
			}
		}

		/**
		 * Выполняет редирект, если ранее было записано хотя бы одно сообщение об ошибке.
		 * Редирект производится на текущую страницу с передачей параметра, сигнализирующего об ошибке.
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 */
		public function errorPanic() {
			$cmsController = cmsController::getInstance();

			if (self::$noRedirectOnPanic || !Service::Request()->isHtml()) {
				$requestId = 'errors_' . $cmsController->getRequestId();

				$session = Service::Session();
				$requestErrors = $session->get($requestId);
				$requestErrors = is_array($requestErrors) ? $requestErrors : [];
				$errorMessage = '';

				foreach ($requestErrors as $i => $errorInfo) {
					unset($requestErrors[$i]);
					$errorMessage .= $errorInfo['message'];
				}

				$session->set($requestId, $requestErrors);

				throw new errorPanicException($errorMessage);
			}

			$errorUrl = (string) $cmsController->errorUrl;

			if ($errorUrl) {
				// validate url
				$errorUrl = preg_replace("/_err=\d+/is", '', $errorUrl);
				while (contains($errorUrl, '&&') || contains($errorUrl, '??') || contains($errorUrl, '?&')) {
					$errorUrl = str_replace('&&', '&', $errorUrl);
					$errorUrl = str_replace('??', '?', $errorUrl);
					$errorUrl = str_replace('?&', '?', $errorUrl);
				}
				if ($errorUrl !== '' && (mb_substr($errorUrl, -1) === '?' || mb_substr($errorUrl, -1) === '&')) {
					$errorUrl = mb_substr($errorUrl, 0, mb_strlen($errorUrl) - 1);
				}
				// detect param concat
				$sUrlConcat = (!contains($errorUrl, '?') ? '?' : '&');
				//
				$errorUrl .= $sUrlConcat . '_err=' . $cmsController->getRequestId();
				$this->redirect($errorUrl, false);
			} else {
				throw new privateException("Can't find error redirect string");
			}
		}

		/**
		 * Пытается определить текущий домен
		 * @return bool|string возвращает хост домена в случае успеха или false в случае неудачи
		 */
		public function guessDomain() {
			$res = false;

			for ($i = 0; ($param = getRequest('param' . $i)) || $i <= 3; $i++) {
				if (is_numeric($param)) {
					$element = umiHierarchy::getInstance()->getElement($param);
					if ($element instanceof iUmiHierarchyElement) {
						$domain_id = $element->getDomainId();
						if ($domain_id) {
							$res = $domain_id;
						}
					} else {
						continue;
					}
				} else {
					continue;
				}
			}

			$domain = Service::DomainCollection()
				->getDomain($res);

			if ($domain instanceof iDomain) {
				return $domain->getHost();
			}

			return false;
		}

		/**
		 * Записывает данные редактируемой (с помощью EiP) страницы
		 * @param string $module имя модуля типа страницы
		 * @param string $method имя метода типа страницы
		 * @param int $id ID редактируемой страницы
		 */
		public static function pushEditable($module, $method, $id) {
			umiTemplater::pushEditable($module, $method, $id);
		}

		/**
		 * Проверяет существует ли метод текущего класса модуля
		 * @param string $method имя метода
		 * @return bool результат проверки
		 */
		public function isMethodExists($method) {
			if (isset($this->methods[$method])) {
				return true;
			}

			$methods = get_class_methods($this);

			if (in_array($method, $methods)) {
				return true;
			}

			return false;
		}

		/**
		 * Выполняет макрос текущего класса модуля, передает результат выполнения на буфер вывода и
		 * прекращает работу скрипта
		 * @param string $methodName имя метода макроса
		 * @throws coreException
		 */
		public function flushAsXML($methodName) {
			static $c = 0;
			if ($c++ == 0) {
				$xml = true;
				if (getRequest('jsonMode') == 'force') {
					$xml = false;
				}
				$buffer = Service::Response()
					->getCurrentBuffer();
				$buffer->contentType('text/' . ($xml ? 'xml' : 'javascript'));
				$buffer->charset('utf-8');
				$buffer->clear();
				$cmsController = cmsController::getInstance();
				$data = $cmsController->executeStream(
					'udata://' . get_class($this) . '/' . $methodName . ($xml ? '' : '.json')
				);
				$buffer->push($data);
				$buffer->end();
			}
		}

		/**
		 * Проверяет не является ли текущий режим XML-режимом
		 * @return bool возвращает true, если текущий режим не является XML-режимом
		 * и false в обратном случае
		 */
		public function ifNotXmlMode() {
			return (getRequest('xmlMode') != 'force');
		}

		/**
		 * Проверяет не является ли текущий режим JSON-режимом
		 * @return bool возвращает true, если текущий режим не является JSON-режимом
		 * и false в обратном случае
		 */
		public function ifNotJsonMode() {
			return (getRequest('jsonMode') != 'force');
		}

		/**
		 * Алиас метода removeErrorCodeFromUrl()
		 * @param string $url url адрес
		 * @return mixed
		 */
		public function removeErrorParam($url) {
			return self::removeErrorCodeFromUrl($url);
		}

		/**
		 * Возвращает url адрес без GET-параметра, сигнализирующего о наличии ошибки
		 * @param string $url url адрес
		 * @return mixed
		 */
		public static function removeErrorCodeFromUrl($url) {
			return preg_replace("/_err=\d+/", '', $url);
		}

		/**
		 * Возвращает ссылку на редактирование объекта
		 * @param int $objectId ID объектов
		 * @param bool|false $type
		 * @return bool всегда false
		 */
		public function getObjectEditLink($objectId, $type = false) {
			return false;
		}

		/**
		 * Производит проверку шаблона. В текущей реализации ничего не делает.
		 * @param string $templateName имя шаблона
		 */
		public static function validateTemplate(&$templateName) {
			if (!$templateName && $templateName == 'default' && self::$defaultTemplateName != 'default') {
				$templateName = self::$defaultTemplateName;
			}
		}

		/**
		 * Проверяет текущий режим
		 * @param string $mode проверяемый режим
		 * @throws tplOnlyException, если проверяемый режим tpl, но текущий режим отличен
		 * @throws xsltOnlyException, если проверяемый режим xslt, но текущий режим отличен
		 * @throws coreException
		 */
		public function templatesMode($mode) {
			$isXslt = self::isXSLTResultMode();
			if ($mode == 'xslt' && !$isXslt) {
				throw new xsltOnlyException;
			}

			if ($mode == 'tpl' && $isXslt) {
				throw new tplOnlyException;
			}
		}

		/**
		 * Устанавливает/возвращает режим работы макросов
		 * @param bool|null $newValue - если передан, то переопределяет режим работы
		 * @static
		 * @return bool - возвращает режим работы, если передан новый режим, возвращает прошлый режим работы макросов
		 * @throws coreException
		 */
		public static function isXSLTResultMode($newValue = null) {
			if (self::$xsltResultMode === null) {
				try {
					$xsltResultMode = cmsController::getInstance()->getCurrentTemplater() instanceof IFullResult;
				} catch (coreException $exception) {
					$xsltResultMode = false;
				}
				self::$xsltResultMode = $xsltResultMode;
			}

			if ($newValue !== null) {
				$oldValue = self::$xsltResultMode;
				self::$xsltResultMode = (bool) $newValue;
				return $oldValue;
			}

			return self::$xsltResultMode;
		}

		/**
		 * Проверяет соответствие типа сущности переданным типам
		 * @param iUmiHierarchyElement|iUmiObject $entity
		 * @param array $types список типов
		 * в формате ['module' => 'some_module', 'method' => 'some_method']
		 * @param bool|false $checkParentType , если true,
		 * то проверка будет производиться на родительском типе типа сущности
		 * @return bool
		 * @throws publicException
		 */
		public function validateEntityByTypes($entity, $types, $checkParentType = false) {
			if ($entity instanceof iUmiHierarchyElement) {
				$module = $entity->getModule();
				$method = $entity->getMethod();
			} else {
				if ($entity instanceof iUmiObject) {
					/** @var iUmiObjectType $objectType */
					$objectType = selector::get('object-type')->id($entity->getTypeId());

					if ($checkParentType) {
						$objectType = selector::get('object-type')->id($objectType->getParentId());
					}

					$hierarchyTypeId = $objectType->getHierarchyTypeId();

					if ($hierarchyTypeId) {
						$hierarchyType = selector::get('hierarchy-type')->id($hierarchyTypeId);
						$module = $hierarchyType->getModule();
						$method = $hierarchyType->getMethod();
					} else {
						$module = null;
						$method = null;
					}
				} else {
					throw new publicException('Page or object must be given');
				}
			}

			if ($module === null && $method === null && $types === null) {
				return true;
			}

			if ($module == 'content' && $method == '') {
				$method = 'page';
			}

			if (getArrayKey($types, 'module')) {
				$types = [$types];
			}

			foreach ($types as $type) {
				$typeModule = getArrayKey($type, 'module');
				$typeMethod = getArrayKey($type, 'method');

				if ($typeModule == 'content' && $typeMethod == '') {
					$typeMethod = 'page';
				}

				if ($typeModule === $module && ($typeMethod === null || $typeMethod === $method)) {
					return;
				}
			}
			throw new publicException(getLabel('error-common-type-mismatch'));
		}

		/**
		 * Добавляет данные об возникших ошибках
		 * @param string|array|Exception $errors данные ошибок
		 * @return array|bool|int
		 */
		public function errorAddErrors($errors) {
			$result = [];
			if ($errors instanceof Exception) {
				$error = [
					'message' => $errors->getMessage(),
					'code' => $errors->getCode(),
				];
				return array_push($this->errors, $error);
			}

			if (is_array($errors)) {
				if (array_key_exists('message', $errors)) {
					$error = array_intersect_key($errors, ['message' => '', 'code' => '', 'strcode' => '']);
					return array_push($this->errors, $error);
				}

				foreach ($errors as $error) {
					$result[] = $this->errorAddErrors($error);
				}
				return $result;
			}

			if (is_string($errors)) {
				return array_push($this->errors, ['message' => $errors]);
			}

			return false;
		}

		/** Устанавливет ошибки. В текущей реализации ничего не делает. */
		protected function errorSetErrors() {
		}

		/**
		 * Возвращает данные об ошибках
		 * @return array
		 */
		public function errorGetErrors() {
			return $this->errors;
		}

		/**
		 * Проверяет присутствуют ли ошибки
		 * @return bool результат проверки
		 */
		public function errorHasErrors() {
			return !empty($this->errors);
		}

		/**
		 * Устанавливает адрес страницы, на которой произошла ошибка
		 * @param string $errorPage url адрес страницы
		 * @return bool
		 */
		public function errorSetErrorPage($errorPage) {
			$errorPage = preg_replace('#http://[^/]+#', '', trim($errorPage));
			// validate url
			$errorPage = preg_replace("/_err=\d+/is", '', $errorPage);
			while (contains($errorPage, '&&') || contains($errorPage, '??') || contains($errorPage, '?&')) {
				$errorPage = str_replace('&&', '&', $errorPage);
				$errorPage = str_replace('??', '?', $errorPage);
				$errorPage = str_replace('?&', '?', $errorPage);
			}
			if ($errorPage !== '' && (mb_substr($errorPage, -1) === '?' || mb_substr($errorPage, -1) === '&')) {
				$errorPage = mb_substr($errorPage, 0, mb_strlen($errorPage) - 1);
			}
			$this->errorPage = $errorPage;
			return true;
		}

		/**
		 * Возвращает адрес страницы, на которой произошла ошибка
		 * @return string
		 */
		public function errorGetErrorPage() {
			return $this->errorPage;
		}

		/**
		 * Выбрасывает исключение в соответствии с записанными ошибками
		 * @param bool|string $mode тип выбрасываемого исключения
		 * @return bool
		 * @throws errorPanicException
		 * @throws wrongValueException
		 * @throws privateException
		 * @throws coreException
		 */
		public function errorThrow($mode = false) {
			if (!$this->errorHasErrors()) {
				return false;
			}

			if (self::$noRedirectOnPanic) {
				$errorMessage = '';
				foreach ($this->errors as $error) {
					$errorMessage .= getLabel($error['message']) . ' ';
				}
				$this->errors = [];
				throw new errorPanicException($errorMessage);
			}

			switch ($mode) {
				case 'public' : {
					$this->errorThrowPublic();
					break;
				}

				case 'admin' : {
					$this->errorThrowAdmin();
					break;
				}

				case 'xml' : {
					$errors = [];
					foreach ($this->errors as $error) {
						$errors[] = getLabel($error['message']);
					}
					$this->errors = [];
					throw new wrongValueException('<br/>' . implode('<br/><br/>', $errors));
				}
			}
		}

		/**
		 * Сортирует массив с объектам, по порядку идентификаторов
		 * в массиве с идентификаторами объектов.
		 * Возвращает результат сортировки.
		 * @param array $sortedIds массив с идентификаторами объектов
		 * @param array $objects массив с объектами
		 * @return array
		 */
		public static function sortObjects($sortedIds, $objects) {
			if (umiCount($sortedIds) == 0 || umiCount($objects) == 0) {
				return [];
			}

			$sortedObjects = [];

			foreach ($objects as $object) {
				$objectId = null;

				switch (true) {
					case ($object instanceof iUmiEntinty) : {
						$objectId = $object->getId();
						break;
					}
					case (is_array($object) && isset($object['id'])) : {
						$objectId = $object['id'];
						break;
					}
				}

				switch (true) {
					case $objectId === null : {
						$sortedObjects[] = $object;
						break;
					}
					case is_numeric($key = array_search($objectId, $sortedIds)) : {
						$sortedObjects[$key] = $object;
						break;
					}
					default: {
						$sortedObjects[] = $object;
					}
				}
			}
			ksort($sortedObjects);
			return $sortedObjects;
		}

		/**
		 * Алиас HTTPOutputBuffer::printJson()
		 * @param $data
		 */
		public function printJson($data) {
			Service::Response()
				->printJson($data);
		}

		/**
		 * Геттер необъявленных свойств
		 * @param string $name имя свойства
		 * @return mixed
		 * @throws coreException
		 */
		public function __get($name) {
			$adminClassName = $this->getAdminClassName();

			if (in_array($name, $this->getAdminProperties()) && $this->isClassImplemented($adminClassName)) {
				return $this->getImplementedInstance($adminClassName)->$name;
			}
		}

		/**
		 * Проверяет существование необъявленных свойств
		 * @param string $name свойства
		 * @return bool
		 */
		public function __isset($name) {
			return (in_array($name, $this->getAdminProperties()) && $this->isClassImplemented($this->getAdminClassName()));
		}

		/**
		 * Список гуидов типов данных для всех шаблонов уведомлений модуля.
		 * Метод переопределяется каждым модулем, у которого есть уведомления.
		 * @return string[]
		 */
		public function getMailObjectTypesGuidList() {
			return [];
		}

		/**
		 * Возвращает настройки почты
		 * @return \UmiCms\Classes\System\Utils\Mail\Settings
		 * @throws Exception
		 */
		public function getMailSettings() {
			return Service::get('MailSettings');
		}

		/**
		 * Отправляются ли системные уведомления модуля через модуль umiNotifications?
		 * Если нет - для отправки уведомлений будут использоваться tpl- и xslt-шаблоны.
		 * @return bool
		 */
		public function isUsingUmiNotifications() {
			$umiRegistry = Service::Registry();
			$className = get_class($this);
			return (bool) $umiRegistry->get("//modules/{$className}/use-umiNotifications");
		}

		/**
		 * Проверяет наличие прав на удаление элементов списка модуля
		 * @throws requreMoreAdminPermissionsException
		 */
		public function validateDeletingListPermissions() {
			if (!system_is_allowed(get_class($this),'delete') && isset($_REQUEST['dels'])) {
				throw new requreMoreAdminPermissionsException(getLabel('error-require-more-permissions'));
			}
		}

		/**
		 * Возвращает список атрибутов, присущих методам административной панели
		 * @return array
		 */
		protected function getAdminProperties() {
			return [
				'limit',
				'offset',
				'dataType',
				'actionType',
				'total',
				'data'
			];
		}

		/**
		 * Возвращает имя класса, содержащего административный функционал
		 * @return string
		 */
		protected function getAdminClassName() {
			return ucfirst(get_class($this)) . 'Admin';
		}

		/**
		 * Выбрасывает публичное исключение с сообщением записанных ошибок
		 * @throws privateException
		 * @throws coreException
		 */
		private function errorThrowPublic() {
			foreach ($this->errors as &$error) {
				$error['message'] = '%' . $error['message'] . '%';
			}
			$this->errorRedirect();
		}

		/**
		 * Выбрасывает исключение уровня административной панели с сообщением записанных ошибок
		 * @throws privateException
		 * @throws coreException
		 */
		private function errorThrowAdmin() {
			foreach ($this->errors as &$error) {
				$error['message'] = getLabel($error['message']);
			}
			$this->errorRedirect();
		}

		/**
		 * Производит редирект на страницу с ошибкой
		 * @throws privateException
		 * @throws coreException
		 */
		private function errorRedirect() {
			$cmsController = cmsController::getInstance();
			$requestId = 'errors_' . $cmsController->getRequestId();
			Service::Session()->set($requestId, $this->errors);

			$errorUrl = $this->errorPage;

			if ($errorUrl) {
				// detect param concat
				$sUrlConcat = (!contains($errorUrl, '?') ? '?' : '&');
				//
				$errorUrl .= $sUrlConcat . '_err=' . $cmsController->getRequestId();
				$this->errors = [];
				$this->redirect($errorUrl, false);
			} else {
				$this->errors = [];
				throw new privateException("Can't find error redirect string");
			}
		}

		/**
		 * @deprecated
		 * Используйте def_module::parseTemplateForMail
		 * @param $template
		 * @param $arr
		 * @param bool $parseElementPropsId
		 * @param bool $parseObjectPropsId
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 * @throws publicException
		 */
		public static function parseContent($template, $arr, $parseElementPropsId = false, $parseObjectPropsId = false) {
			return self::parseTemplateForMail($template, $arr, $parseElementPropsId, $parseObjectPropsId);
		}

		/**
		 * @deprecated
		 * @param string $filepath
		 * @return mixed
		 */
		public static function loadTemplatesMeta($filepath = '') {
			$arguments = func_get_args();
			$templates = call_user_func_array(['def_module', 'loadTemplates'], $arguments);

			for ($i = 1; $i < umiCount($arguments); $i++) {
				$templates[$i - 1] = $templates[$i - 1] ? [
					'#template' => $templates[$i - 1],
					'#meta' => ['name' => $arguments[$i], 'file' => $filepath]
				] : $templates[$i - 1];
			}

			return $templates;
		}

		/**
		 * @deprecated
		 * Устанавливает заголовок и хедер в соответствии с параметрами страницы
		 * @return bool
		 */
		public function autoDetectAttributes() {
			$cmsController = cmsController::getInstance();
			$element_id = $cmsController->getCurrentElementId();

			if ($element_id) {
				$element = umiHierarchy::getInstance()->getElement($element_id);

				if (!$element) {
					return false;
				}

				$h1 = $element->getValue('h1');

				if ($h1) {
					$this->setHeader($h1);
				} else {
					$this->setHeader($element->getName());
				}

				$title = $element->getValue('title');

				if ($title) {
					$this->setTitle($title);
				}
			}
		}

		/**
		 * Формирует объекты страниц (umiHierarchyElement) по идентификаторам
		 *
		 * @deprecated
		 * Рекомендуется пользоваться классами umiLinksHelper и umiHierarchy отдельно
		 *
		 * @param array|int $elementsIds массив с идентификаторами страниц
		 * @param bool $needProps нужно ли дополнительно загрузить свойства страниц
		 * @param bool $hierarchyTypeId оставлен для обратной совместимости
		 * @return bool
		 * @throws Exception
		 */
		protected function loadElements($elementsIds, $needProps = false, $hierarchyTypeId = false) {
			if (!is_array($elementsIds)) {
				$elementsIds = [$elementsIds];
			}

			$umiLinksHelper = umiLinksHelper::getInstance();
			$umiLinksHelper->loadLinkPartForPages($elementsIds);
			$hierarchy = umiHierarchy::getInstance();
			$elements = $hierarchy->loadElements($elementsIds);
			$objectsIds = [];

			/** @var iUmiHierarchyElement $element */
			foreach ($elements as $element) {
				if ($element instanceof iUmiHierarchyElement) {
					$objectsIds[] = $element->getObjectId();
				}
			}

			if ($needProps && umiCount($objectsIds) > 0) {
				umiObjectProperty::loadPropsData($objectsIds);
			}

			return true;
		}

		/**
		 * @deprecated
		 * Проверяет валидность HTTP_REFERER
		 * @return bool
		 */
		public static function checkHTTPReferer() {
			try {
				return Service::Protection()->checkReferrer();
			} catch (Exception $e) {
				return false;
			}
		}

		/**
		 * @deprecated
		 */
		public function getVariableNamesForMailTemplates() {
			return [];
		}
	}
