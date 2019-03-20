<?php

/**
 * Class def_module
 * Базовый класс модулей системы
 */
abstract class def_module {

	/** @const string имя файла с системными правилами для автозагрузки классов для модуля */
	const AUTOLOAD_FILE = 'autoload.php';

	/** @const string имя файла с пользовательскими правилами для автозагрузки классов для модуля */
	const CUSTOM_AUTOLOAD_FILE = 'customAutoload.php';

	/** @const string имя файла с подключением необходимых модулю внешних классов */
	const INCLUDES_FILE = 'includes.php';

	/** @const string имя файла с параметрами и правилами сервисов для модуля */
	const SERVICES_FILE = 'services.php';

	/** @var array кэщ данных о шаблонах */
	public static $templates_cache = array();
	/**
	 * @var bool, если true то при возникновении паники
	 * будет выброшено исключение вместо выполнения редиректа
	 */
	public static $noRedirectOnPanic = false;
	/** @var string имя шаблона по учмолчанию */
	public static $defaultTemplateName = 'default';
	/** @var null|bool является ли текущий режим режимом xslt */
	public static $xsltResultMode = null;

	/**
	 * Список макросов с выводом расширенных полей для XSLT
	 * @var array
	 */
	protected static $macrosesXsltExtendedResult = array(array(), array());
	/** @var int максимальное количество страниц */
	public $max_pages = 10;
	/** @var bool была ли произведена фильтрация выборки */
	public $isSelectionFiltered = false;
	/** @var int ID текущей страницы */
	public $pid;
	/** @var array кэш данных загруженных файлов модуля */
	public $FORMS_CACHE = array();
	/** @var array данные загруженных файлов модуля */
	public $FORMS = array();
	/** @var int количество элементов, отображаемых на странице */
	public $per_page = 20;
	/** @var string текущий тип данных */
	public $dataType;
	/** @var string текущий тип действия */
	public $actionType;
	/** @var bool|int ID страницы, редактируемой в данный момент */
	public $currentEditedElementId = false;
	/** @var array $classes подключенные классы */
	public $__classes = array();
	/** @var array $methods подключенные методы */
	public $__methods = array();
	/** @var array $libs загруженные файлы классов */
	public $libsCalled = array();
	/** @var mixed объект основных вкладок модуля */
	public $common_tabs = null;
	/** @var mixed объект вкладок настроек модуля */
	public $config_tabs = null;
	/** @var array список возникших ошибок */
	protected $errors = array();
	/** @var string адрес страницы, на которой возникла ошибка */
	protected $errorPage = '';

	/** @var cmsController */
	protected $cmsController;
	/** @var regedit  */
	protected $regedit;
	/** @var umiHierarchyTypesCollection  */
	protected $umiHierarchyTypesCollection;
	/** @var umiObjectsCollection  */
	protected $umiObjectsCollection;
	/** @var umiObjectTypesCollection  */
	protected $umiObjectTypesCollection;
	/** @var domainsCollection  */
	protected $domainsCollection;
	/** @var iConfiguration|mainConfiguration|null  */
	protected $mainConfiguration;
	/** @var umiFieldsCollection  */
	protected $umiFieldsCollection;
	/** @var umiFieldTypesCollection  */
	protected $umiFieldTypesCollection;
	/** @var umiLinksHelper  */
	protected $umiLinksHelper;
	/** @var umiTypesHelper  */
	protected $umiTypesHelper;
	/** @var umiPropertiesHelper  */
	protected $umiPropertiesHelper;

	/**
	 * Подключает класс к модулю
	 * @param string $class_name имя подключаемого класса
	 * @return bool
	 */
	public function __implement($class_name) {
		$this->__classes[] = $class_name;

		$cm = get_class_methods($class_name);
		if ($cm === null) {
			return;
		}

		$methods = array_fill_keys($cm, $class_name);
		$this->__methods += $methods;

		if (isset($methods['onInit'])) {
			$this->onInit();
		}

		$fn = 'onImplement';
		if (isset($methods[$fn]) && class_exists('ReflectionClass', false) && class_exists('ReflectionMethod', false) && class_exists('ReflectionException', false)) {
			try {
				$oRfClass = new ReflectionClass($class_name);
				$oRfMethod = $oRfClass->getMethod($fn);
				if ($oRfMethod instanceof ReflectionMethod) {
					if ($oRfMethod->isPublic()) {
						eval('$res = ' . $class_name . '::' . $fn . '();');
					}
				}
			} catch (ReflectionException $e) {}
		}
	}

	/**
	 * Устанавливает список дополнительных полей и групп для результатов парсинга макросов
	 * @param array $extProps имена полей
	 * @param array $extGroups имена групп
	 */
	public static function setMacrosExtendedResult($extProps = array(), $extGroups = array()) {
		self::$macrosesXsltExtendedResult = array($extProps, $extGroups);
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

	/** Подключает скрипт и создает экземпляр основного класса административной панели */
	public function __admin() {
		if($this->cmsController->getCurrentMode() == "admin" && !class_exists("__" . get_class($this))) {
			$this->__loadLib("__admin.php");
			$this->__implement("__" . get_class($this));
		}
	}

	/**
	 * Магический метод, пытается найти переданный метод
	 * среди методов подключенных классов,
	 * если метод найден - вызывает его
	 * @param string $method имя метода
	 * @param array $args аргументы вызова
	 * @return mixed|string
	 */
	public function __call($method, $args) {
		if (isset($this->__methods[$method])) {
			$className = $this->__methods[$method];
			$params = '';
			if(is_array($args)) {
				$sz = count($args);
				for($i = 0; $i < $sz; $i++) {
					$params .= '$args[' . $i . ']';
					if($i != $sz-1) {
						$params .= ', ';
					}
				}
			}
			$result = false;
			eval('$result = ' . $className . '::' . $method . '(' . $params . ');');
			return $result;
		}

		$this->cmsController->setLangConstant(get_class($this), $method, 'Ошибка');

		$contentModule = $this->cmsController->getModule('content');
		if (!$contentModule) {
			return '';
		}

		if ($this->cmsController->getCurrentMode() === 'admin') {
			return 'Вызов несуществующего метода.';
		}

		if ($this->cmsController->getCurrentModule() === get_class($this) && $this->cmsController->getCurrentMethod() === $method) {
			return $contentModule->gen404();
		}

		return '';
	}

	/** Конструктор */
	public function __construct() {
        $this->cmsController = cmsController::getInstance();
        $this->regedit = regedit::getInstance();
        $this->umiHierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();
        $this->umiObjectsCollection = umiObjectsCollection::getInstance();
        $this->umiObjectTypesCollection = umiObjectTypesCollection::getInstance();
        $this->domainsCollection = domainsCollection::getInstance();
        $this->mainConfiguration = mainConfiguration::getInstance();
        $this->umiFieldsCollection = umiFieldsCollection::getInstance();
        $this->umiFieldTypesCollection = umiFieldTypesCollection::getInstance();
        $this->umiLinksHelper = umiLinksHelper::getInstance();
		$this->umiTypesHelper = umiTypesHelper::getInstance();
		$this->umiPropertiesHelper = umiPropertiesHelper::getInstance();
		$this->lang = $this->cmsController->getCurrentLang()->getPrefix();
		$this->init();
	}

	/**
	 * Возвращает список имен модулей системы
	 * с соответствующими значениями индексами их сортировки
	 * @return array
	 */
	public function getSortedModulesList() {
		$priorityList = Array(
			// user modules (priority < 100)
			'events'		=> 1,
				'content'		=> 2,
			'news'			=> 3,
				'menu'		=> 4,
			'forum'			=> 5,
				'blogs20'		=> 6,
			'vote'			=> 7,
				'comments'		=> 8,
			'photoalbum'	=> 9,
				'webforms'		=> 10,
			'dispatches'	=> 11,
				'faq'			=> 12,
			'eshop'			=> 13,
			'emarket'		=> 14,
				'catalog'		=> 15,
			'users'			=> 16,
				'banners'		=> 17,
			'seo'			=> 18,
				'stat'			=> 19,
			'social_networks'	=> 20,
				'exchange'		=> 21,
			// administrative modules (priority > 100)
			'data'			=> 101,
			'config'		=> 102,
			'backup'		=> 103,
			'autoupdate'	=> 104,
			'webo'			=> 105,
			'search'		=> 106,
			'filemanager'	=> 107,
			'trash'	=> 999,
		);

		$sysModules = array('config', 'trash' ,'search', 'autoupdate');
		$utilModules = array('data', 'backup', 'umiRedirects', 'umiNotifications', 'umiSettings', 'filemanager', 'umiStub');

		$modulesList = $this->regedit->getList('//modules');
		$permissions = permissionsCollection::getInstance();

		$result = array();
		foreach($modulesList as $module) {
			list( $module ) = $module;
			if ( $permissions->isAllowedModule(false, $module) == false ) {
				continue;
			}
			$priority = isset($priorityList[$module]) ? $priorityList[$module] : 99;
			$result[$module] = $priority;
		}

		natsort($result);

		foreach($result as $module => $priority) {
			if(in_array($module, $sysModules))
				$type = 'system';
			else if(in_array($module, $utilModules))
				$type = 'util';
			else $type = null;

			$moduleInfo = array();
			$moduleInfo['name'] = $module;
			$moduleInfo['label'] = getLabel("module-" . $module);
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
		$cmsController = $this->cmsController;
		$currentModule = $cmsController->getCurrentModule();
		$selfModule = get_class($this);

		if (($currentModule != $selfModule) && ($currentModule != false && $selfModule != 'users')) return false;
		if (!$this->common_tabs instanceof adminModuleTabs) {
			$this->common_tabs = new adminModuleTabs("common");
		}
		return $this->common_tabs;
	}

	/**
	 * Возвращает объект вкладок настроек модуля
	 * @return adminModuleTabs|bool|mixed
	 */
	public function getConfigTabs() {
		if ($this->cmsController->getCurrentModule() != get_class($this)) return false;

		if (!$this->config_tabs instanceof adminModuleTabs) {
			$this->config_tabs = new adminModuleTabs("config");
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
	 * @return mixed|void
	 */
	public function cms_callMethod($method_name, $args) {
		if(!$method_name) return;

		$aArguments = array();
		if(USE_REFLECTION_EXT && class_exists('ReflectionMethod')) {
			try {
				$oReflection   = new ReflectionMethod($this, $method_name);
				$iNeedArgCount = max($oReflection->getNumberOfRequiredParameters(), count($args));
				if($iNeedArgCount) $aArguments = array_fill(0, $iNeedArgCount, 0);
			} catch(Exception $e) {}
		}

		for($i=0; $i<count($args); $i++) $aArguments[$i] = $args[$i];

		if(count($aArguments) && !(empty($args[0]) && count($args) === 1)) {
			return call_user_func_array(array($this, $method_name), $aArguments);
		} else {
			return $this->$method_name();
		}
	}

	/** Производит инициализацию модуля */
	public function init() {
		// подключаем кастомы из ресурсов шаблона
		// TODO: refactoring
		if ($resourcesDir = $this->cmsController->getResourcesDirectory()) {
			$includesFile = realpath($resourcesDir . '/classes/modules') . '/' . get_class($this) . '/class.php';
			if (file_exists($includesFile)) {
				require_once $includesFile;
				$className = get_class($this) . '_custom';
				if (!in_array($className, $this->__methods)) {
					$this->__implement($className);
					new $className($this);
				}
			}
		}

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
	 * Производит загрузку общих файлов расширений.
	 * Загрузка производится из директории [имя_модуля]/ext с учётом префикса файлов.
	 */
	public function loadCommonExtension() {
		\UmiCms\Service::ExtensionLoader()
			->setModule($this)
			->loadCommon();
	}

	/**
	 * Производит загрузку админских файлов расширений.
	 * Загрузка производится из директории [имя_модуля]/ext с учётом префикса файлов.
	 */
	public function loadAdminExtension() {
		if (!cmsController::getInstance()->isCurrentModeAdmin()) {
			return;
		}

		\UmiCms\Service::ExtensionLoader()
			->setModule($this)
			->loadAdmin();
	}

	/**
	 * Производит загрузку сайтовых файлов расширений и подключение событий.
	 * Загрузка производится из директории [имя_модуля]/ext с учётом префикса файлов.
	 */
	public function loadSiteExtension() {
		if (cmsController::getInstance()->isCurrentModeAdmin()) {
			return;
		}

		\UmiCms\Service::ExtensionLoader()
			->setModule($this)
			->loadSite();
	}

	/**
	 * Проверяет валидность HTTP_REFERER
	 * @internal
	 * @return bool
	 */
	public static function checkHTTPReferer() {
		preg_match('|^http(?:s)?:\/\/(?:www\.)?([^\/]+)|ui', getServer('HTTP_REFERER'), $matches);

		if (!isset($matches[1]) || count($matches[1])!=1) {
			return false;
		}

		$originalDomain = $matches[1];

		$domainNames = array();
		$domainNames[] = $originalDomain;
		$domainNames[] = 'www.' . $originalDomain;
		$domainNames[] = (string) preg_replace('/(:\d+)/', '', $originalDomain);

		$domainsCollection = domainsCollection::getInstance();

		foreach ($domainNames as $domainName) {
			if (is_numeric($domainsCollection->getDomainId($domainName))) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Устанавливает модуль
	 * @param array $INFO параметры установки модуля
	 */
	public static function install($INFO) {
		$xpath = '//modules/' . $INFO['name'];
		$regedit = regedit::getInstance();

		$regedit->setVar($xpath, $INFO['name']);

		if(is_array($INFO)) {
				foreach($INFO as $var => $module_param) {
						$val = $module_param;
						$regedit->setVar($xpath . "/" . $var, $val);
				}
		}
	}

	/** Производит удаление текущего модуля */
	public function uninstall() {
		$className = get_class($this);
		$regedit = $this->regedit;
		$defaultModuleAdmin = $regedit->getVal('//settings/default_module_admin');

		if ($defaultModuleAdmin == $className) {
			$regedit->setVal('//settings/default_module_admin', 'content');
		}

		$regedit->getKey('//modules/' . $className);
		$regedit->delVar('//modules/' . $className);

	}

	/**
	 * Производит перенаправление на указанный адрес
	 * @param string $url адрес
	 * @param bool $ignoreErrorParam нужно ли удалить параметр с отсылкой на ошибку из адреса
	 * @throws coreException
	 */
	public function redirect($url, $ignoreErrorParam = true) {
		if(getRequest('redirect_disallow')) return;
		if(!$url) $url = $this->pre_lang . "/";
		if($ignoreErrorParam && (isset($this) && $this instanceof def_module)) $url = $this->removeErrorParam($url);

        umiHierarchy::getInstance()->__destruct();
		\UmiCms\Service::Response()
			->getCurrentBuffer()
			->redirect($url);
	}

	/**
	 * При необъходимости добавляет суффикс или слэш к url-адресу
	 * и производит редирект
	 */
	public function requireSlashEnding() {
		if(getRequest('is_app_user') !== null) {
			return;
		}

		if(getRequest('xmlMode') === 'force' || count($_POST) > 0) {
			return;
		}

		if (getRequest('jsonMode') === 'force' || count($_POST) > 0) {
			return;
		}

		$uri = getServer('REQUEST_URI');

		if($uri == '/') {
			return;
		}
		$uriInfo = parse_url($uri);

		if (cmsController::getInstance()->getCurrentMode() === 'admin') {
			if(substr($uriInfo['path'], -1, 1) != "/") {
				$uri = $uriInfo['path'] . "/";
				if(isset($uriInfo['query']) && $uriInfo['query']) {
					$uri .= "?" . $uriInfo['query'];
				}
				self::redirect($uri);
			}
			return;
		}

		if ($urlSuffix = mainConfiguration::getInstance()->get('seo', 'url-suffix')){
			$pos = strrpos($uriInfo['path'], $urlSuffix);
			if ($pos === false || !($pos + strlen($urlSuffix) == strlen($uriInfo['path']))) {
				if ($uriInfo['path'] == '/') {
					return;
				} else {
					$uri = rtrim($uriInfo['path'], '/') . $urlSuffix;
				}
				if(isset($uriInfo['query']) && $uriInfo['query']) {
					$uri .= "?" . $uriInfo['query'];
				}
				self::redirect($uri);
			}
		}
	}

	/**
	 * Загружает файла класса для последующей имлементации его в модуле
	 * @param string $lib имя подключаемого файла
	 * @param string $path путь до файла
	 * @param bool $remember нужно ли сохранять информацию о загруженных файлах
	 * @return bool
	 */
	public function __loadLib($lib, $path = "", $remember = false) {
		$lib_path = ($path) ? $path . $lib : SYS_MODULES_PATH . get_class($this) . "/" . $lib;

		if (isset($this->FORMS_CACHE[$lib_path])) {
			$FORMS = $this->FORMS_CACHE[$lib_path];
		} else
		if (file_exists($lib_path)) {
			require_once $lib_path;
		}

		if($remember) {
			$this->FORMS = $FORMS;
			$this->FORMS_CACHE[$lib_path] = $FORMS;
		}
		return true;
	}

	/**
	 * Изменяет текущий хедер модуля
	 * @param string $header новый хедер
	 */
	public function setHeader($header) {
		$cmsControllerInstance = $this->cmsController;
		$cmsControllerInstance->currentHeader = $header;
	}

	/**
	 * Изменяет текущий заголовок модуля
	 * @param string $title новый заголовок
	 * @param int $mode, если не 0, то значение заголовка будет получено из реестра
	 */
	protected function setTitle($title = "", $mode = 0) {
		$cmsControllerInstance = $this->cmsController;
		if($title) {
			if($mode)
				$cmsControllerInstance->currentTitle = $this->regedit->getVal('//domains/' . $_REQUEST['domain'] . '/title_pref_' . $_REQUEST['lang']) . $title;
			else
				$cmsControllerInstance->currentTitle = $title;
		}
		else
			$cmsControllerInstance->currentTitle = $this->cmsController->currentHeader;

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
	 * @param bool|string $ctype значение для заголовка Content-type
	 */
	public function flush($output = "", $ctype = false) {
		if($ctype !== false) {
			header("Content-type: " . $ctype);
		}

		echo $output;
		exit();
	}

	/**
	 * @deprecated
	 * Больше не успользуется
	 */
	public static function loadTemplatesMeta($filepath = "") {
		$arguments = func_get_args();
		$templates = call_user_func_array(array('def_module', "loadTemplates"), $arguments);

		for($i=1; $i < count($arguments); $i++) {
			$templates[$i-1] = $templates[$i-1] ? array("#template" => $templates[$i-1], "#meta" => array("name" => $arguments[$i], "file" => $filepath)) : $templates[$i-1];
		}

		return $templates;
	}

	/**
	 * @static
	 * Загружает шаблоны, используя шаблонизатор в зависимости от режима работы макросов, возвращает запрошенные блоки
	 * @param string $filePath - путь к источнику шаблонов
	 * @return array
	 */
	public static function loadTemplates($filePath = "") {
		$args = func_get_args();

		$templater = self::isXSLTResultMode() ? 'umiTemplaterXSLT' : 'umiTemplaterTPL';

		if (!self::isXSLTResultMode() && !is_file($filePath)) {
			$cmsController = cmsController::getInstance();
			// получаем полный путь к tpl-шаблону
			$defaultLang = langsCollection::getInstance()->getDefaultLang();
			$currentLang = $cmsController->getCurrentLang();
			$resourcesDir = $cmsController->getResourcesDirectory();

			$langPrefix = '';
			if ($defaultLang && $currentLang && ($defaultLang->getId() != $currentLang->getId())) {
				$langPrefix = $currentLang->getPrefix();
			}

			if (substr($filePath, -4) === '.tpl') {
				$filePath = substr($filePath, 0, -4);
			}

			$files = array();
			if ( \UmiCms\Service::Request()->isMobile() ) {
				$pathArray = explode('/', $filePath);
				$mobileFilePath = '/mobile/' . array_pop($pathArray);
				$mobileFilePath = implode('/', $pathArray) . $mobileFilePath;
				if ( strlen($langPrefix) ) {
					$files[] = $mobileFilePath . '.' . $langPrefix;
				}
				$files[] = $mobileFilePath;
			}

			if ( strlen($langPrefix) ) {
				$files[] = $filePath . '.' . $langPrefix;
			}
			$files[] = $filePath;

			$dir = rtrim(($resourcesDir ? $resourcesDir : CURRENT_WORKING_DIR), '/') . '/tpls/';

			foreach($files as $filePath) {
				$filePath = $dir . $filePath. '.tpl';
				if ( is_file($filePath) ) {
					break;
				}
			}

			$args[0] = $filePath;
		}

		$result = call_user_func_array(array(
			$templater, 'getTemplates'
		), $args);

		return $result;
	}

	/**
	 * @static
	 * Загружает шаблоны для формирования писем
	 * Сначала пытаемся загрузить XSLT-шаблон, если шаблон не найден, пытаемся загрузить TPL-шаблон
	 *
	 * @param string $filePath путь до шаблона
	 * @return array - массив шаблонов
	 * @throws coreException
	 */
	public static function loadTemplatesForMail($filePath = "") {
		if (substr($filePath, -4) === '.tpl') {
			$filePath = substr($filePath, 0, -4);
		}
		// fix for mail / mails paths for xslt
		$xslFilePath = $filePath;
		if (strpos($xslFilePath, "mail") !== false) {
			$xslFilePath = str_replace(array("mail/", "mails/"), array('', ''), $xslFilePath);
		}

		if ($resourcesDir = cmsController::getInstance()->getResourcesDirectory()) {
			$xslSourcePath = $resourcesDir . "/xslt/mail/" . $xslFilePath . ".xsl";
			$tplSourcePath = $resourcesDir . "/tpls/" . $filePath . ".tpl";
		} else {
			$xslSourcePath = CURRENT_WORKING_DIR . "/xsltTpls/mail/" . $xslFilePath . ".xsl";
			$tplSourcePath = CURRENT_WORKING_DIR . "/tpls/" . $filePath . ".tpl";
		}

		$templaterClass = null;
		if (is_file($xslSourcePath)) {
			$templaterClass = 'umiTemplaterXSLT';
			$sourcePath = $xslSourcePath;
		} elseif (is_file($tplSourcePath)) {
			$templaterClass = 'umiTemplaterTPL';
			$sourcePath = $tplSourcePath;
		} else {
			throw new coreException("Невозможно подключить шаблон \"{$filePath}\" для отправки письма", 2);
		}

		$args = func_get_args();
		$args[0] = $sourcePath;

		$result = call_user_func_array(array(
			$templaterClass, 'getTemplates'
		), $args);

		return $result;
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
	 */
	public static function parseTPLMacroses($content, $scopeElementId = false, $scopeObjectId = false, $parseVariables = array()) {
		if (strpos($content, '%') === false) return $content;

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
	 * @param bool|int $parseObjectPropsId  - установить id объекта в качестве области видимости блока
	 * @param null|bool $xsltResultMode - принудительно устанавливает режим работы макросов перед разбором
	 * и восстанавливает предыдущий режим работы в конце работы
	 * @return mixed - результат разбора шаблона
	 */
	public static function parseTemplate($template, $arr, $parseElementPropsId = false, $parseObjectPropsId = false, $xsltResultMode = null) {
		if (!is_array($arr)) $arr = array();

		$oldResultMode = null;
		if (is_bool($xsltResultMode)) {
			$oldResultMode = self::isXSLTResultMode($xsltResultMode);
		}
		if (self::isXSLTResultMode()) {
			$result = array();
			$extProps = self::getMacrosExtendedProps();
			$extGroups = self::getMacrosExtendedGroups();
			if ((!empty($extProps) || !empty($extGroups)) && ($parseElementPropsId || $parseObjectPropsId)) {
				if ($parseElementPropsId) {
					$entity = umiHierarchy::getInstance()->getElement($parseElementPropsId);
					if ($entity) $entity = $entity->getObject();
				} else {
					$entity = umiObjectsCollection::getInstance()->getObject($parseObjectPropsId);
				}
				/** @var umiObject $entity */
				if ($entity) {
					$extPropsInfo = array();
					foreach ($extProps as $fieldName) {
						if ($fieldName == 'name' && !isset($arr['attribute:name'], $arr['@name'])) {
							$arr['@name'] = $entity->getName();
						} elseif ($extProp = $entity->getPropByName($fieldName)) {
							$extPropsInfo[] = $extProp;
						}
					}
					if (count($extPropsInfo)) {
						if (!isset($arr['extended'])) $arr['extended'] = array();
						$arr['extended']['properties'] = array('+property' => $extPropsInfo);
					}

					$extGroupsInfo = array();
					foreach ($extGroups as $groupName) {
						if ($group = $entity->getType()->getFieldsGroupByName($groupName)) {
							$groupWrapper = translatorWrapper::get($group);
							$extGroupsInfo[] = $groupWrapper->translateProperties($group, $entity);
						}
					}

					if (count($extGroupsInfo)) {
						if (!isset($arr['extended'])) $arr['extended'] = array();
						$arr['extended']['groups'] = array('+group' => $extGroupsInfo);
					}
				}
			}
			$keysCache = &xmlTranslator::$keysCache;
			foreach($arr as $key => $val) {
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
				if($subKey === 'subnodes') {
					$result[$realKey] = array(
						'nodes:item' => $val
					);
					continue;
				}

				$result[$key] = $val;
			}
			return $result;
		} else {
			$templater = umiTemplater::create('TPL');
			$variables = array();
			foreach($arr as $m => $v) {
				$m = self::getRealKey($m);

				if(is_array($v)) {
					$res = "";
					$v = array_values($v);
					$sz = count($v);
					for($i = 0; $i < $sz; $i++) {
						$str = $v[$i];

						$listClassFirst = ($i == 0) ? "first" : "";
						$listClassLast = ($i == $sz-1) ? "last" : "";
						$listClassOdd = (($i+1) % 2 == 0) ? "odd" : "";
						$listClassEven = $listClassOdd ? "" : "even";
						$listPosition = ($i + 1);
						$listComma = $listClassLast ? '' : ', ';

						$from = Array(
							'%list-class-first%', '%list-class-last%', '%list-class-odd%', '%list-class-even%', '%list-position%',
							'%list-comma%'
						);
						$to = Array(
							$listClassFirst, $listClassLast, $listClassOdd, $listClassEven, $listPosition, $listComma
						);
						$t_res = str_replace($from, $to, $str);
						$res .= is_array($t_res) ? implode('',$t_res) : $t_res;
					}
					$v = $res;
				}
				if(!is_object($v)) {
					$variables[$m] = $v;
				}
			}
			$arr = $variables;
		}
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
	 */
	public static function renderTemplate($template, $block, $blockArray = array(), $elementId = false) {
		list($tpl) = def_module::loadTemplates($template, $block);
		return def_module::parseTemplate($tpl, $blockArray, $elementId);
	}

	/**
	 * @static
	 * Выполняет разбор шаблона для отправки письма
	 * Если в template пришел URI шаблона, для обработки используется umiTemplaterXSTL
	 * @param string $template - шаблон для разбора
	 * @param array $arr - массив переменнх
	 * @param bool|int $parseElementPropsId - установить id страницы в качестве области видимости блока
	 * @param bool|int $parseObjectPropsId  - установить id объекта в качестве области видимости блока
	 * @return mixed - результат разбора шаблона
	 * @throws publicException
	 */
	public static function parseTemplateForMail($template, $arr, $parseElementPropsId = false, $parseObjectPropsId = false) {
		if (strpos($template, 'file://') === 0) {
			// Используем xslt-шаблонизатор
			$templateURL = @parse_url($template);
			if (!is_array($templateURL)) {
				throw new publicException('Невозможно обработать шаблон "' . $template . '"');
			}
			$templateSource = $templateURL['path'];
			$templateFragment = (isset($templateURL['fragment']) && strlen($templateURL['fragment'])) ? $templateURL['fragment'] : 'result';

			$templater = umiTemplater::create('XSLT', $templateSource);
			return $templater->parse(array(
				$templateFragment => $arr
			));
		} else {
			// Используем tpl-шаблонизатор
			return def_module::parseTemplate($template, $arr, $parseElementPropsId, $parseObjectPropsId, false);
		}
	}
	/**
	 * @deprecated
	 * Используйте def_module::parseTemplateForMail
	 */
	public static function parseContent($template, $arr, $parseElementPropsId = false, $parseObjectPropsId = false) {
		return self::parseTemplateForMail($template, $arr, $parseElementPropsId, $parseObjectPropsId);
	}

	/**
	 * Возвращает часть строки до или после разделителя
	 * @param string $key исходная строка
	 * @param bool $reverse, если true, то будет возвращена строка перед разделителем,
	 * если false, то после резделителя
	 * @return string
	 */
	static public function getRealKey($key, $reverse = false) {
		$shortKeys = array('@', '#', '+', '%', '*');

		if(in_array(substr($key, 0, 1), $shortKeys)) {
			return substr($key, 1);
		}

		if($pos = strpos($key, ":")) {
			++$pos;
		} else {
			$pos = 0;
		}

		return $reverse ? substr($key, 0, $pos - 1) : substr($key, $pos);
	}

	/**
	 * Форматирует сообщение форума
	 * @param string $message исходное сообщение
	 * @param int $b_split_long_mode, если 0, то слишком длинные слова в сообщении
	 * будут разделены
	 * @return mixed|string
	 */
	public function formatMessage($message, $b_split_long_mode = 0) {
		static $bb_from;
		static $bb_to;

		$oldResultTMode = $this->isXSLTResultMode(false);

		try {
			list($quote_begin, $quote_end) = $this->loadTemplates('quote/default', 'quote_begin', 'quote_end');
		} catch (publicException $e) {
			$quote_begin = "<div class='quote'>";
			$quote_end = "</div>";
		}

		if (self::isXSLTResultMode()) {
			$quote_begin = "<div class='quote'>";
			$quote_end = "</div>";
		}

		if (!(is_array($bb_from) && is_array($bb_to) && count($bb_from) === count($bb_to))) {
			try {
				list($bb_from, $bb_to) = $this->loadTemplates('bb/default', 'bb_from', 'bb_to');
				if (!(is_array($bb_from) && is_array($bb_to) && count($bb_from) === count($bb_to) && count($bb_to))) {
					$bb_from = Array("[b]", "[i]", "[/b]", "[/i]",
						"[quote]", "[/quote]", "[u]", "[/u]", "\r\n"
					);

					$bb_to   = Array("<strong>", "<em>", "</strong>", "</em>",
						$quote_begin, $quote_end, "<u>", "</u>", "<br />"
					);
				}
			} catch (publicException $e) {
				$bb_from = Array("[b]", "[i]", "[/b]", "[/i]",
					"[quote]", "[/quote]", "[u]", "[/u]", "\r\n"
				);

				$bb_to   = Array("<strong>", "<em>", "</strong>", "</em>",
					$quote_begin, $quote_end, "<u>", "</u>", "<br />"
				);
			}
		}

		$openQuoteCount = substr_count(wa_strtolower($message), "[quote]");
		$closeQuoteCount = substr_count(wa_strtolower($message), "[/quote]");

		if($openQuoteCount > $closeQuoteCount) {
			$message .= str_repeat("[/quote]", $openQuoteCount - $closeQuoteCount);
		}
		if($openQuoteCount < $closeQuoteCount) {
			$message = str_repeat("[quote]", $closeQuoteCount - $openQuoteCount) . $message;
		}

		$message = preg_replace("`((http)+(s)?:(//)|(www\.))((\w|\.|\-|_)+)(/)?([/|#|?|&|=|\w|\.|\-|_]+)?`i", "[url]http\\3://\\5\\6\\8\\9[/url]", $message);

		$message = str_ireplace($bb_from, $bb_to, $message);
		$message = str_ireplace("</h4>", "</h4><p>", $message);
		$message = str_ireplace("</div>", "</p></div>", $message);

		$message = str_replace(".[/url]", "[/url].", $message);
		$message = str_replace(",[/url]", "[/url],", $message);

		$message = str_replace(Array("[url][url]", "[/url][/url]"), Array("[url]", "[/url]"), $message);

		// split long words
		if ($b_split_long_mode === 0) { // default
			$arr_matches = array();
			$b_succ = preg_match_all("/[^\s^<^>]{70,}/u", $message, $arr_matches);
			if ($b_succ && isset($arr_matches[0]) && is_array($arr_matches[0])) {
				foreach ($arr_matches[0] as $str) {
					$s = "";
					if (strpos($str, "[url]") === false) {
						for ($i = 0; $i<wa_strlen($str); $i++) $s .= wa_substr($str, $i, 1).(($i % 30) === 0 ? " " : "");
						$message = str_replace($str, $s, $message);
					}
				}
			}
		} elseif ($b_split_long_mode === 1) {
			// TODU abcdef...asdf
		}

		if (preg_match_all("/\[url\]([^А-я^\r^\n^\t]*)\[\/url\]/U", $message, $matches, PREG_SET_ORDER)) {
			for ($i=0; $i<count($matches); $i++) {
				$s_url = $matches[$i][1];
				$i_length = strlen($s_url);
				if ($i_length>40) {
					$i_cutpart = ceil(($i_length-40)/2);
					$i_center = ceil($i_length/2);

					$s_url = substr_replace($s_url, "...", $i_center-$i_cutpart, $i_cutpart*2);
				}
				$message = str_replace($matches[$i][0], "<a href='/go-out.php?url=".$matches[$i][1]."' target='_blank' title='Ссылка откроется в новом окне'>".$s_url."</a>", $message);
			}
		}

		$message = str_replace("&", "&amp;", $message);

		$message = str_ireplace("[QUOTE][QUOTE]", "", $message);

		if(preg_match_all("/\[smile:([^\]]+)\]/im", $message, $out)) {
			foreach($out[1] as $smile_path) {
				$s = $smile_path;
				$smile_path = "images/forum/smiles/" . $smile_path . ".gif";
				if(file_exists($smile_path)) {
					$message = str_replace("[smile:" . $s . "]", "<img src='/{$smile_path}' />", $message);
				}
			}
		}

		$message = preg_replace("/<p>(<br \/>)+/", "<p>", $message);
		$message = nl2br($message);
		$message = str_replace("<<br />br /><br />", "", $message);
		$message = str_replace("<p<br />>", "<p>", $message);

		$message = str_replace("&amp;quot;", "\"", $message);
		$message = str_replace("&amp;quote;", "\"", $message);
		$message = html_entity_decode($message);
		$message = str_replace("%", "&#37;", $message);

		$message = $this->parseTPLMacroses($message);

		$this->isXSLTResultMode($oldResultTMode);
		return $message;
	}

	/**
	 * Устанавливает заголовок и хедер в соответствии с параметрами страницы
	 * @return bool
	 */
	public function autoDetectAttributes() {
		if($element_id = $this->cmsController->getCurrentElementId()) {
			$element = umiHierarchy::getInstance()->getElement($element_id);

			if(!$element) return false;

			if($h1 = $element->getValue("h1")) {
				$this->setHeader($h1);
			} else {
				$this->setHeader($element->getName());
			}

			if($title = $element->getValue("title")) {
				$this->setTitle($title);
			}

		}
	}

	/**
	 * Производит определение параметров сортировки и применяет их к переданной выборке
	 * @param umiSelection $sel выборка, к которой будет применена сортировка
	 * @param int $object_type_id ID типа данных,
	 * в котором находится поле по которому будет произведена сортировка
	 * @return bool
	 */
	public function autoDetectOrders(umiSelection $sel, $object_type_id) {
		if(array_key_exists("order_filter", $_REQUEST)) {
			$sel->setOrderFilter();

			$type = $this->umiObjectTypesCollection->getType($object_type_id);

			$order_filter = getRequest('order_filter');
			foreach($order_filter as $field_name => $direction) {
				if($direction === "asc") $direction = true;
				if($direction === "desc") $direction = false;

				if($field_name == "name") {
					$sel->setOrderByName((bool) $direction);
					continue;
				}

				if($field_name == "ord") {
					$sel->setOrderByOrd((bool) $direction);
					continue;
				}

				if($type) {
					if($field_id = $type->getFieldId($field_name)) {
						$sel->setOrderByProperty($field_id, (bool) $direction);
					} else {
						continue;
					}
				}
			}
		} else {
			return false;
		}
	}

	/**
	 * Производит определение параметров фильтрации
	 * @param umiSelection $sel выборка, к которой будет применена фильтрация
	 * @param int $object_type_id ID типа данных, в котором находятся поля, по которым будет
	 * произведена фильтрация
	 * @return bool
	 * @throws coreException
	 * @throws publicException
	 */
	public function autoDetectFilters(umiSelection $sel, $object_type_id) {
		if(getRequest('search-all-text') !== null) {
			$searchStrings = getRequest('search-all-text');
			if(is_array($searchStrings)) {
				foreach($searchStrings as $searchString) {
					if($searchString) {
						$sel->searchText($searchString);
					}
				}
			}
		}

		if(array_key_exists("fields_filter", $_REQUEST)) {
			$cmsController = $this->cmsController;
			$data_module = $cmsController->getModule("data");
			if(!$data_module) {
				throw new publicException("Need data module installed to use dynamic filters");
			}
			$sel->setPropertyFilter();

			$type = $this->umiObjectTypesCollection->getType($object_type_id);

			$order_filter = getRequest('fields_filter');
			if(!is_array($order_filter)) {
				return false;
			}

			foreach($order_filter as $field_name => $value) {
				if($field_name == "name") {
					$data_module->applyFilterName($sel, $value);
					continue;
				}

				if($field_id = $type->getFieldId($field_name)) {
					$this->isSelectionFiltered = true;
					$field = $this->umiFieldsCollection->getField($field_id);

					$field_type_id = $field->getFieldTypeId();
					$field_type = $this->umiFieldTypesCollection->getFieldType($field_type_id);

					$data_type = $field_type->getDataType();

					switch($data_type) {
						case "text": {
							$data_module->applyFilterText($sel, $field, $value);
							break;
						}

						case "wysiwyg": {
							$data_module->applyFilterText($sel, $field, $value);
							break;
						}

						case "string": {
							$data_module->applyFilterText($sel, $field, $value);
							break;
						}

						case "tags": {
							$tmp = array_extract_values($value);
							if(empty($tmp)) {
								break;
							}
						}
						case "boolean": {
							$data_module->applyFilterBoolean($sel, $field, $value);
							break;
						}

						case "int": {
							$data_module->applyFilterInt($sel, $field, $value);
							break;
						}

						case "symlink":
						case "relation": {
							$data_module->applyFilterRelation($sel, $field, $value);
							break;
						}

						case "float": {
							$data_module->applyFilterFloat($sel, $field, $value);
							break;
						}

						case "price": {
							$emarket = $cmsController->getModule('emarket');
							if($emarket instanceof def_module) {
								$defaultCurrency = $emarket->getDefaultCurrency();
								$currentCurrency = $emarket->getCurrentCurrency();
								$prices = $emarket->formatCurrencyPrice($value, $defaultCurrency, $currentCurrency);
								foreach($value as $index => &$void) {
									$void = getArrayKey($prices, $index);
								}
								unset($void);
							}

							$data_module->applyFilterPrice($sel, $field, $value);
							break;
						}

						case "file":
						case "img_file":
						case "swf_file":
						case "video_file": {
							$data_module->applyFilterInt($sel, $field, $value);
							break;
						}

						case "date": {
							$data_module->applyFilterDate($sel, $field, $value);
							break;
						}

						default: {
							break;
						}
					}
				} else {
					continue;
				}
			}
		} else {
			return false;
		}
	}

	/**
	 * Производит анализ переданного пути и возвращает ID соответствующей страницы,
	 * если такая существует
	 * @param int|string $pathOrId ID страницы или путь до нее
	 * @param bool|true $returnCurrentIfVoid, если true и не передан первый параметр,
	 * то будет возвращен ID текущей страницы
	 * @return bool|false|int|string
	 */
	public function analyzeRequiredPath($pathOrId, $returnCurrentIfVoid = true) {

        $umiHierarchy = umiHierarchy::getInstance();

		if(is_numeric($pathOrId)) {
			return ($umiHierarchy->isExists((int) $pathOrId) || $pathOrId == 0) ? (int) $pathOrId : false;
		} else {
			$pathOrId = trim($pathOrId);

			if($pathOrId) {
				if(strpos($pathOrId, " ") === false) {
					return $umiHierarchy->getIdByPath($pathOrId);
				} else {
					$paths_arr = explode(" ", $pathOrId);

					$ids = Array();

					foreach($paths_arr as $subpath) {
						$id = $this->analyzeRequiredPath($subpath, false);

						if($id === false) {
							continue;
						} else {
							$ids[] = $id;
						}
					}

					if(count($ids) > 0) {
						return $ids;
					} else {
						return false;
					}
				}
			} else {
				if($returnCurrentIfVoid) {
					return $this->cmsController->getCurrentElementId();
				} else {
					return false;
				}
			}
		}
	}

	/**
	 * Проверяет переданы ли POST-параметры
	 * @param bool|true $bRedirect, еслп true, то в случае успешной проверки
	 * будет произведен редирект в административную панель
	 * @return bool результат проверки
	 */
	public function checkPostIsEmpty($bRedirect = true) {
		$bResult = !is_array($_POST) || (is_array($_POST) && !count($_POST));
		if ($bResult && $bRedirect) {
			$url = preg_replace("/(\r)|(\n)/", "", $_REQUEST['pre_lang'])."/admin/";
			header("Location: ".$url);
			exit();
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
		$this->cmsController->errorUrl = $errorUrl;
	}

	/**
	 * Записывает сообщение об ошибке
	 * @param string $errorMessage сообщение об ошибке
	 * @param bool|true $causePanic, если true, то будет произведен редирект на текущую страницу,
	 * но с передачей параметра, сигнализируещего об ошибке
	 * @param bool|int $errorCode числовое представление кода ошибки
	 * @param bool|string $errorStrCode строковое представление кода ошибки
	 * @throws coreException
	 * @throws errorPanicException
	 * @throws privateException
	 */
	public function errorNewMessage($errorMessage, $causePanic = true, $errorCode = false, $errorStrCode = false) {
		$controller = cmsController::getInstance();
		$requestId = 'errors_' . $controller->getRequestId();
		$errorMessage = def_module::parseTPLMacroses($errorMessage);

		$session = \UmiCms\Service::Session();
		$requestErrors = $session->get($requestId);
		$requestErrors = (is_array($requestErrors)) ? $requestErrors : [];
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
	 * Вызывает панику. Выполняет редирект, если ранее было записано хотя бы одно сообщение об ошибке.
	 * Редирект производится на текущую страницу с передачей параметра, сигнализируещего об ошибке.
	 * @return bool
	 * @throws errorPanicException
	 * @throws privateException
	 * @throws coreException
	 */
	public function errorPanic() {
		if (getRequest('_err') !== null) {
			return false;
		}

		$cmsController = cmsController::getInstance();

		if (self::$noRedirectOnPanic) {
			$requestId = 'errors_' . $cmsController->getRequestId();

			$session = \UmiCms\Service::Session();
			$requestErrors = $session->get($requestId);
			$requestErrors = (is_array($requestErrors)) ? $requestErrors : [];
			$errorMessage = '';

			foreach ($requestErrors as $i => $errorInfo) {
				unset($requestErrors[$i]);
				$errorMessage .= $errorInfo['message'];
			}

			$session->set($requestId, $requestErrors);

			throw new errorPanicException($errorMessage);
		}

		if ($errorUrl = $cmsController->errorUrl) {
			// validate url
			$errorUrl = preg_replace("/_err=\d+/is", '', $errorUrl);
			while (strpos($errorUrl, '&&') !== false || strpos($errorUrl, '??') !== false || strpos($errorUrl, '?&') !== false) {
				$errorUrl = str_replace('&&', '&', $errorUrl);
				$errorUrl = str_replace('??', '?', $errorUrl);
				$errorUrl = str_replace('?&', '?', $errorUrl);
			}
			if (strlen($errorUrl) && (substr($errorUrl, -1) === '?' || substr($errorUrl, -1) === '&')) {
				$errorUrl = substr($errorUrl, 0, strlen($errorUrl) - 1);
			}
			// detect param concat
			$sUrlConcat = (strpos($errorUrl, '?') === false ? '?' : '&');
			//
			$errorUrl .= $sUrlConcat . '_err=' . $cmsController->getRequestId();
			$this->redirect($errorUrl, false);
		} else {
			throw new privateException("Can't find error redirect string");
		}
	}

	/**
	 * Не используется
	 * @deprecated
	 * @return string
	 */
	public function importDataTypes() {
		$sDTXmlPath = dirname(__FILE__)."/".get_class($this)."/types.xml";
		$oDTImporter = new umiModuleDataImporter();
		$bSucc = $oDTImporter->loadXmlFile($sDTXmlPath);
		if ($bSucc) {
			$oDTImporter->import();
			return "data types imported ok";
		} else {
			return "can not import data from file '".$sDTXmlPath."'";
		}
	}

	/**
	 * Не используется
	 * @deprecated
	 * @return string
	 */
	public function exportDataTypes() {
		$sDTXmlPath = dirname(__FILE__)."/".get_class($this)."/types.xml";
		$oDTExporter = new umiModuleDataExporter(get_class($this));
		$sDTXmlData = $oDTExporter->getXml();
		$vSucc = file_put_contents($sDTXmlPath, $sDTXmlData);
		if ($vSucc === false) {
			return "can not write to file '".$sDTXmlPath."'";
		} else {
			@chmod($sDTXmlPath, 0777);
			return $vSucc." bytes exported to the file '".$sDTXmlPath."' successfully";
		}
	}

	/**
	 * Пытается определить текущий домен
	 * @return bool|string возвращает хост домена в случае успеха или false в случае неудачи
	 */
	public function guessDomain() {
		$res = false;

		for($i = 0; ($param = getRequest("param" . $i)) || $i <= 3; $i++) {
			if(is_numeric($param)) {
				$element = umiHierarchy::getInstance()->getElement($param);
				if($element instanceof umiHierarchyElement) {
					$domain_id = $element->getDomainId();
					if($domain_id) $res = $domain_id;
				} else {
					continue;
				}
			} else {
				continue;
			}
		}

		$domain = $this->domainsCollection->getDomain($res);
		if($domain instanceof iDomain) {
			return $domain->getHost();
		} else {
			return false;
		}
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
	* @desc Checks for method existance
	* @param String $method Name of the method
	* @return Boolean
	*/
	public function isMethodExists($method) {
		if (isset($this->__methods[$method])) {
			return true;
		}

		$methods = get_class_methods($this);
		if(in_array($method, $methods)) {
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
		if($c++ == 0) {
			$xml = true;
			if (getRequest('jsonMode') == 'force') {
				$xml = false;
			}

			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/' . ($xml ? 'xml' : 'javascript'));
			$buffer->charset('utf-8');
			$buffer->clear();
			$data = $this->cmsController->executeStream("udata://" . get_class($this) . "/" . $methodName . ($xml ? '' : '.json'));
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
		if(getRequest('xmlMode') != 'force') {
			$this->setData(array('message' => 'This method returns result only by direct xml call'));
			return true;
		}
	}

	/**
	 * Проверяет не является ли текущий режим JSON-режимом
	 * @return bool возвращает true, если текущий режим не является JSON-режимом
	 * и false в обратном случае
	 */
	public function ifNotJsonMode() {
		if(getRequest('jsonMode') != 'force') {
			$this->setData(array('message' => 'This method returns result only by direct json call'));
			return true;
		}
	}

	/**
	 * Возвращает url адрес без GET-параметра, сигнализируещго о наличии ошибки
	 * @param string $url url адрес
	 * @return mixed
	 */
	public function removeErrorParam($url) {
		return preg_replace("/_err=\d+/", "", $url);
	}

	/**
	 * Возвращает ссылку на редактирование объекта
	 * @param int $objectId ID объектв
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
		if(!$templateName && $templateName == 'default' && self::$defaultTemplateName != 'default') {
			$templateName = self::$defaultTemplateName;
		}
	}

	/**
	 * Проверяет текущий режим
	 * @param string $mode проверяемый режим
	 * @throws tplOnlyException, если проверяемый режим tpl, но текущий режим отличен
	 * @throws xsltOnlyException, если проверяемый режим xslt, но текущий режим отличен
	 */
	public function templatesMode($mode) {
		$isXslt = self::isXSLTResultMode();
		if($mode == 'xslt' && !$isXslt) {
			throw new xsltOnlyException;
		}

		if($mode == 'tpl' && $isXslt) {
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
			self::$xsltResultMode = cmsController::getInstance()->getCurrentTemplater() instanceof IFullResult;
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
	 * @param bool|false $checkParentType, если true,
	 * то проверка будет производиться на родительском типе типа сущности
	 * @return bool|void
	 * @throws publicException
	 */
	public function validateEntityByTypes($entity, $types, $checkParentType = false) {
		if($entity instanceof iUmiHierarchyElement) {
			$module = $entity->getModule();
			$method = $entity->getMethod();
		} else if($entity instanceof iUmiObject) {
			/** @var umiObjectType */
			$objectType = selector::get('object-type')->id($entity->getTypeId());
			if($checkParentType) {
				$objectType = selector::get('object-type')->id($objectType->getParentId());
			}
			if($hierarchyTypeId = $objectType->getHierarchyTypeId()) {
				$hierarchyType = selector::get('hierarchy-type')->id($hierarchyTypeId);
				$module = $hierarchyType->getModule();
				$method = $hierarchyType->getMethod();
			} else {
				$module = null;
				$method = null;
			}
		} else {
			throw new publicException("Page or object must be given");
		}

		if($module === null && $method === null && $types === null) {
			return true;
		}

		if($module == 'content' && $method == '') {
			$method = 'page';
		}

		if(getArrayKey($types, 'module')) {
			$types = array($types);
		}

		foreach($types as $type) {
			$typeModule = getArrayKey($type, 'module');
			$typeMethod = getArrayKey($type, 'method');

			if($typeModule == 'content' && $typeMethod == '') {
				$typeMethod = 'page';
			}

			if($typeModule === $module && ($typeMethod === null || $typeMethod === $method)) {
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
		$result = array();
		if ($errors instanceof Exception) {
			$error = array(
				'message' => $errors->getMessage(),
				'code' => $errors->getCode(),
				);
			return array_push($this->errors, $error);
		} elseif (is_array($errors)) {
			if (array_key_exists('message', $errors)) {
				$error = array_intersect_key($errors, array('message'=>'', 'code'=>''));
				return array_push($this->errors, $error);
			} else {
				foreach ($errors as $error) {
					$result[] = $this->errorAddErrors($error);
				}
				return $result;
			}
		} elseif (is_string($errors)) {
			return array_push($this->errors, array('message' => $errors));
		}
		return false;
	}

	/** Устанавливет ошибки. В текущей реализации ничего не делает. */
	protected function errorSetErrors () {

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
		return (!empty($this->errors));
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
		while (strpos($errorPage, '&&') !== false || strpos($errorPage, '??') !== false || strpos($errorPage, '?&') !== false) {
			$errorPage = str_replace('&&', '&', $errorPage);
			$errorPage = str_replace('??', '?', $errorPage);
			$errorPage = str_replace('?&', '?', $errorPage);
		}
		if (strlen($errorPage) && (substr($errorPage, -1) === '?' || substr($errorPage, -1) === '&')) {
			$errorPage = substr($errorPage, 0, strlen($errorPage)-1);
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
	 */
	public function errorThrow($mode = false) {
		if (!$this->errorHasErrors()) {
			return false;
		}

		if(self::$noRedirectOnPanic) {
			$errorMessage = '';
			foreach ($this->errors as $error) {
				$errorMessage .= getLabel($error['message']) . ' ';
			}
			$this->errors = array();
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
				$errors = array();
				foreach ($this->errors as $error) {
					$errors[] = getLabel($error['message']);
				}
				$this->errors = array();
				throw new wrongValueException('<br/>' . implode("<br/><br/>", $errors));
			}
		}
	}

	/**
	 * Сортирует массив с объектам, по порядку идентификаторов
	 * в массиве с идентификаторами объектов.
	 * Возращает результат сортировки.
	 * @param array $sortedIds массив с идентификаторами объектов
	 * @param array $objects массив с объектами
	 * @return array
	 */
	public static function sortObjects($sortedIds, $objects) {
		if (count($sortedIds) == 0 || count($objects) == 0) {
			return array();
		}

		$sortedObjects = array();

		foreach ($objects as $object) {
			$objectId = null;

			switch (true) {
				case ($object instanceof umiEntinty) : {
					$objectId = $object->getId();
					break;
				}
				case (is_array($object) && isset($object['id'])) : {
					$objectId = $object['id'];
					break;
				}
			}

			switch (true) {
				case is_null($objectId) : {
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
	 * Заплатка для устранения рекурсии при установке системы
	 * @link http://youtrack.umisoft.ru/issue/cms2-1293
	 */
	public function loadTemplateCustoms() {
		//nothing
	}

	/**
	 * Производит перенаправление на указанный адрес
	 * @param string $url адрес
	 * @param bool $ignoreErrorParam нужно ли удалить параметр с отсылкой на ошибку из адреса
	 * @throws coreException
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
		\UmiCms\Service::Response()
			->getCurrentBuffer()
			->redirect($url);
	}

	/**
	 * Возвращает url адрес без GET-параметра, сигнализируещго о наличии ошибки
	 * @param string $url url адрес
	 * @return mixed
	 */
	public static function removeErrorCodeFromUrl($url) {
		return preg_replace("/_err=\d+/", '', $url);
	}

	/**
	 * Формирует объекты страниц (umiHierarchyElement) по идентификаторам
	 * @param array $elementsIds массив с идентификаторами страниц
	 * @param bool $needProps нужно ли дополнительно загрузить свойства страниц
	 * @param bool $hierarchyTypeId оставлен для обратной совместимости
	 * @return void
	 */
	protected function loadElements($elementsIds, $needProps = false, $hierarchyTypeId = false) {
		if (!is_array($elementsIds)) {
			$elementsIds = array($elementsIds);
		}

		$this->umiLinksHelper->loadLinkPartForPages($elementsIds);
		$hierarchy = umiHierarchy::getInstance();
		$elements = $hierarchy->loadElements($elementsIds);
		$objectsIds = array();

		/** @var iUmiHierarchyElement $element */
		foreach ($elements as $element) {
			if ($element instanceof umiHierarchyElement) {
				$objectsIds[] = $element->getObjectId();
			}
		}

		if ($needProps && umiCount($objectsIds) > 0) {
			umiObjectProperty::loadPropsData($objectsIds);
		}

		return true;
	}

	/**
	 * Выбрасывает публичное исключение с сообщением записанных ошибок
	 * @throws privateException
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
	 */
	private function errorThrowAdmin () {
		foreach ($this->errors as &$error) {
			$error['message'] = getLabel($error['message']);
		}
		$this->errorRedirect();
	}

	/**
	 * Производит редирект на страницу с ошибкой
	 * @throws privateException
	 */
	private function errorRedirect () {
		$cmsController = cmsController::getInstance();
		$requestId = 'errors_' . $cmsController->getRequestId();

		$session = \UmiCms\Service::Session();
		$session->set($requestId, $this->errors);

		if ($errorUrl = $this->errorPage) {
			// detect param concat
			$sUrlConcat = (strpos($errorUrl, '?') === false ? '?' : '&');
			//
			$errorUrl .= $sUrlConcat . '_err=' . $cmsController->getRequestId();
			$this->errors = [];
			$this->redirect($errorUrl, false);
		} else {
			$this->errors = [];
			throw new privateException("Can't find error redirect string");
		}
	}
};

?>
