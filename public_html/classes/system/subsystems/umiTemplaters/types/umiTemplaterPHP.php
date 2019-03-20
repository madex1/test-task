<?php

	use UmiCms\Service;
	use UmiCms\Classes\System\Translators\TranslatorFactory;

	/**
	 * PHP шаблонизатор.
	 *
	 * @method mixed macros($module, $method, $arguments = []) выполняет вызов макроса
	 * @method umiHierarchyElement getPageByPath($path) возвращает страницу по её url
	 * @method umiHierarchyElement getPageById($id) возвращает страницу по её Id
	 * @method string translate($label, $path = false) возвращает перевод метки
	 * @method umiObject getObjectById($id) возвращает объект по ID
	 * @method array usel($uselName, $params = null) выполняет usel
	 * @method mixed getParam($name, $default = null, $safe = true) возвращает GET-параметр
	 * @method mixed getCommonVar($name) возвращает запрошенную общую переменную
	 * @method mixed setCommonVar($name, $value) устанавливает общую переменную
	 * @method mixed isSetCommonVar($name) проверяет существование общей переменной
	 * @method DOMNodeList xpathToInnerResult(array $variables, $query) выполняет xpath запросов к внутреннему
	 * результату работы текущего метода
	 */
	class umiTemplaterPHP extends umiTemplater implements IFullResult {

		/** @var string Имя файла с шаблоном отображения ошибок */
		const ERROR_TEMPLATE = 'errors.phtml';

		/** @var PhpTemplateEngine движок шаблонизатора */
		protected $templateEngine;

		/** @var string текущая директория с шаблонами */
		protected $templatesDirectory;

		/** @var bool очищать ли входные данные для шаблонизатора */
		private $useDataCleaner;

		/** @inheritdoc */
		public function __construct($templatesSource) {
			$this->useDataCleaner = (bool) mainConfiguration::getInstance()
				->get('system', 'use-php-template-data-cleaning');

			$viewExtension = new ViewPhpExtension($this);
			$templateEngine = new PhpTemplateEngine();
			$templateEngine->addExtension($viewExtension);

			$this->templateEngine = $templateEngine;
			$this->templatesDirectory = (string) cmsController::getInstance()
				->getTemplatesDirectory();

			parent::__construct($templatesSource);
		}

		/**
		 * Возвращает путь до директории, в которой хранятся шаблоны.
		 * @return string
		 */
		public function getTemplatesDirectory() {
			return $this->templatesDirectory;
		}

		/**
		 * Загружает пользовательские расширения.
		 * @param array $extensionPathList список путей до расширений
		 * @throws coreException
		 */
		public function loadExtension(array $extensionPathList) {
			foreach ($extensionPathList as $extensionPath) {
				$filePath = CURRENT_WORKING_DIR . $extensionPath . '.php';

				if (!file_exists($filePath) && contains($filePath, 'templates')) {
					$filePath = $this->buildSolutionExtensionPath($filePath);
				}

				if (!file_exists($filePath)) {
					continue;
				}

				require_once $filePath;

				$pathList = explode('/', $extensionPath);
				$extensionName = array_pop($pathList);
				$extension = new $extensionName($this);

				$this->templateEngine->addExtension($extension);
			}
		}

		/**
		 * Формирует путь до расширения решения
		 * @param string $filePath путь до расширения
		 * @return string|null
		 * @throws \coreException
		 */
		private function buildSolutionExtensionPath($filePath) {
			preg_match('|templates\/([a-zA-Z0-9]{1,})\/|', $filePath, $matches);
			$solutionName = isset($matches[1]) ? $matches[1] : null;

			if ($solutionName === null) {
				return $solutionName;
			}

			$domainId = Service::DomainDetector()
				->detectId();
			$solutionNameWithSuffix = Service::UmiDumpSolutionPostfixBuilder()
				->run($solutionName, $domainId);
			return str_replace($solutionName, $solutionNameWithSuffix, $filePath);
		}

		/** @inheritdoc */
		public static function loadTemplates($templatesSource) {
			return [];
		}

		/** @inheritdoc */
		public static function getTemplates($templatesSource) {
			$result = [];
			$templates = func_get_args();
			unset($templates[0]);

			$allTemplates = self::loadTemplates($templatesSource);

			if (!umiCount($templates)) {
				return $allTemplates;
			}

			foreach ($templates as $name) {
				$result[] = isset($allTemplates[$name]) ? $allTemplates[$name] : '';
			}

			return $result;
		}

		/** @inheritdoc */
		public function parse($variables, $content = null) {
			$cleanVariables = $this->cleanData($variables);
			return $this->applyTemplate($cleanVariables, $this->templatesSource);
		}

		/**
		 * Очищает данные для шаблонизации от разметки для их преобразования в xml
		 * @param mixed $data данные для шаблонизации
		 * @return mixed
		 * @throws ErrorException
		 */
		public function cleanData($data) {
			if (!$this->useDataCleaner()) {
				return $data;
			}

			$allKeysAreUseless = !mainConfiguration::getInstance()
				->get('system', 'collapse-array-only-with-useless-key-in-data-cleaner');

			return TranslatorFactory::create(TranslatorFactory::PHP)
				->setAllKeysAreUseless($allKeysAreUseless)
				->translate($data);
		}

		/**
		 * Выполняет шаблонизацию по заданному шаблону.
		 * @param mixed $variables переменные передаваемые в шаблон
		 * @param string $template путь до шаблона
		 * @return string
		 * @throws Exception
		 */
		public function render($variables, $template) {
			$suffix = '';
			$mobileSuffix = 'mobile/';

			if ($this->isMobile() && is_dir($this->templatesDirectory . $mobileSuffix)) {
				$suffix = $mobileSuffix;
			}

			return $this->applyTemplate($variables, $this->templatesDirectory . $suffix . $template . '.phtml');
		}

		/**
		 * Magic method: вызывает помощник шаблонов.
		 * @param string $name имя помощника шаблонов
		 * @param array $arguments аргументы
		 * @throws RuntimeException если коллекция помощников вида не была внедрена
		 * @return string
		 */
		public function __call($name, array $arguments) {
			return $this->templateEngine->callHelper($name, $arguments);
		}

		/**
		 * Определяет нужно ли очищать входные данные для шаблонизатора
		 * @return bool
		 */
		private function useDataCleaner() {
			return $this->useDataCleaner;
		}

		/**
		 * Выполняет шаблонизацию.
		 * @param array $variables переменные передаваемые в шаблон
		 * @param string $template путь до шаблона
		 * @return string
		 * @throws Exception
		 */
		private function applyTemplate($variables, $template) {
			if (!is_readable($template)) {
				throw new RuntimeException(sprintf(
					'Cannot render template. PHP template file "%s" is not readable.',
					$template
				));
			}

			ob_start();
			try {
				/** @noinspection PhpIncludeInspection */
				require $template;
			} catch (\Exception $error) {
				ob_end_clean();

				if (file_exists($this->templatesDirectory . self::ERROR_TEMPLATE) && !$error instanceof coreException) {
					ob_start();
					/** @noinspection PhpIncludeInspection */
					require $this->templatesDirectory . self::ERROR_TEMPLATE;
				} else {
					throw $error;
				}
			}

			return $this->parseTplMacros(ob_get_clean());
		}

		/**
		 * Применяет tpl шаблонизатор к содержимому буфера
		 * @param string $buffer содержимое буфера
		 * @return string
		 * @throws coreException
		 * @throws ErrorException
		 */
		private function parseTplMacros($buffer) {
			if (!contains($buffer, '%')) {
				return $buffer;
			}

			$tplTemplate = umiTemplater::create('TPL');
			$elementId = cmsController::getInstance()
				->getCurrentElementId();
			$tplTemplate->setScope($elementId);
			return $tplTemplate->parse([], $buffer);
		}
	}
