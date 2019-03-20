<?php

	use UmiCms\Service;

	abstract class umiBaseStream implements iUmiBaseStream {

		public static $allowTimeMark = true;

		public static $allowExtendedOptions = true;

		protected $position = 0;

		protected $length = 0;

		protected $data = '';

		/** @var int Срок жизни кэша в секундах для запроса протокола */
		protected $expire = 0;

		protected $transform = '';

		protected $path;

		/** @var array Параметры запроса протокола */
		protected $params = [];

		protected $isJson = false;

		protected $scheme;

		protected static $callLog = [];

		private $start_time;

		/** @var int $queryCountBeforeExecute количество запросов к базе данных, произведенных до выполнения протокола */
		private $queryCountBeforeExecute;

		public function __construct() {
			$this->start_time = microtime(true);
			$this->queryCountBeforeExecute = ConnectionPool::getInstance()
				->getConnection()
				->getQueriesCount();
			$this->tryLoadCustomConfig();
		}

		public function stream_flush() {
			return true;
		}

		public function stream_tell() {
			return $this->position;
		}

		public function stream_eof() {
			return $this->position >= $this->length;
		}

		public function stream_seek($offset, $whence) {
			switch ($whence) {
				case SEEK_SET:
					if ($this->isValidOffset($offset)) {
						$this->position = $offset;
						return true;
					}

					return false;

				case SEEK_CUR:
					if ($offset >= 0) {
						$this->position += $offset;
						return true;
					}

					return false;

				case SEEK_END:
					if ($this->isValidOffset($this->position + $offset)) {
						$this->position = $this->length + $offset;
						return true;
					}

					return false;

				default:
					return false;
			}
		}

		public function url_stat() {
			return [];
		}

		public function stream_stat() {
			return [];
		}

		public function stream_close() {
			return true;
		}

		public function stream_read($count) {
			$result = bytes_substr($this->data, $this->position, $count);
			$this->position += $count;
			return $result;
		}

		public function stream_write($inputData) {
			$inputDataLength = bytes_strlen($inputData);

			$dataLeft = bytes_substr($this->data, 0, $this->position);
			$dataRight = bytes_substr($this->data, $this->position + $inputDataLength);

			$this->data = $dataLeft . $inputData . $dataRight;

			$this->position += $inputData;
			return $inputDataLength;
		}

		public function getProtocol() {
			return $this->scheme . '://';
		}

		/**
		 * Регистрирует протокол (поток)
		 * @param string $scheme название протокола (потока)
		 * @throws coreException
		 */
		public static function registerStream($scheme) {
			$config = mainConfiguration::getInstance();
			$filePath = $config->includeParam('system.kernel.streams') . "{$scheme}/{$scheme}Stream.php";

			if (!file_exists($filePath)) {
				throw new coreException("Can't locate file \"{$filePath}\"");
			}

			/** @noinspection PhpIncludeInspection */
			require $filePath;

			if (!stream_wrapper_register($scheme, "{$scheme}Stream")) {
				throw new coreException("Failed to register stream \"{$scheme}\"");
			}
		}

		public static function protectParams($param) {
			return str_replace('/', '&#2F;', $param);
		}

		public static function unprotectParams($param) {
			return str_replace('&#2F;', '/', $param);
		}

		public static function getCalledStreams() {
			$lines_arr = [];
			$total_time = 0;
			$dbConnection = ConnectionPool::getInstance()->getConnection();
			$queriesCount = $dbConnection->getQueriesCount();

			foreach (self::$callLog as $callInfo) {
				list($url, $time) = $callInfo;
				$total_time += $time;
				$lines_arr[] = [
					'attribute:generation-time' => $time,
					'node:url' => $url,
				];
			}

			$block_arr = ['nodes:call' => $lines_arr];

			$dom = new DOMDocument('1.0', 'utf-8');
			$dom->formatOutput = XML_FORMAT_OUTPUT;
			$rootNode = $dom->createElement('streams-call');
			$rootNode->setAttribute('total-time', $total_time);

			if (isset($_GET['show-something'])) {
				$rootNode->setAttribute('queries-count', $queriesCount);
			}

			$dom->appendChild($rootNode);

			$xmlTranslator = new xmlTranslator($dom);
			$xmlTranslator->translateToXml($rootNode, $block_arr);

			return $dom->saveXML();
		}

		public static function reportCallTime($path, $time) {
			foreach (self::$callLog as &$callInfo) {
				$callInfoPath = explode('?', $callInfo[0]);
				if ($callInfoPath[0] == $path) {
					$callInfo[1] = $time;
				}
			}
		}

		/**
		 * Добавляет запись в лог вызовов.
		 * @param array $line информация о вызове array(callName, executionTime)
		 */
		public static function addLineCallLog(array $line) {
			self::$callLog[] = $line;
		}

		protected function isValidOffset($offset) {
			return ($offset >= 0) && ($offset < $this->length);
		}

		protected function translateToXml() {
			$args = func_get_args();
			$res = $args[0];

			if ($this->isJson) {
				return $this->translateToJSON($res);
			}

			if (isset($res['plain:result'])) {
				return $res['plain:result'];
			}

			$dom = new DOMDocument('1.0', 'utf-8');
			$dom->formatOutput = XML_FORMAT_OUTPUT;

			$rootNode = $dom->createElement('udata');
			$dom->appendChild($rootNode);

			$rootNode->setAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');

			$xslTranslator = new xmlTranslator($dom);
			$xslTranslator->translateToXml($rootNode, $res);

			$executionTime = number_format(microtime(true) - $this->start_time, 6);
			$rootNode->setAttribute('generation-time', $executionTime);
			self::reportCallTime($this->getProtocol() . $this->path, $executionTime);

			if ($this->transform) {
				return $this->applyXslTransformation($dom, $this->transform);
			}
			return $dom->saveXML();
		}

		/**
		 * Выполняет xsl преобразования, возвращает результат преобразований
		 * @param DOMDocument $dom преобразуемый документ
		 * @param string $xslFilePath относительный путь до xsl шаблона
		 * @return string
		 * @throws libXMLErrorException получить DOMDocument на основе шаблона
		 * @throws publicException если шаблона не существует
		 */
		protected function applyXslTransformation(DOMDocument $dom, $xslFilePath) {
			$config = mainConfiguration::getInstance();
			$cmsController = cmsController::getInstance();

			$filePath = null;
			$resourcesDir = $cmsController->getResourcesDirectory();

			if ($resourcesDir) {
				$filePath = "{$resourcesDir}/xslt/{$xslFilePath}";
			}

			if (!is_file($filePath)) {
				$filePath = $config->includeParam('templates.xsl') . $xslFilePath;
			}

			if (!is_file($filePath)) {
				throw new publicException("xsl-template was not found \"{$filePath}\"");
			}

			$xsltDom = new DOMDocument();
			$domLoaded = $xsltDom->load($filePath, DOM_LOAD_OPTIONS);

			if (!$domLoaded && !defined('DEBUG') && function_exists('libxml_get_last_error')) {
				throw new libXMLErrorException(libxml_get_last_error());
			}

			$xslt = new XSLTProcessor();
			$xslt->registerPHPFunctions();
			$xslt->importStylesheet($xsltDom);

			$currentTemplate = $cmsController->detectCurrentDesignTemplate();
			if ($currentTemplate instanceof iTemplate) {
				$this->applyParams(
					$xslt,
					[
						'template-name' => $currentTemplate->getName(),
						'template-resources' => $currentTemplate->getResourcesDirectory(true),
					]
				);
			}

			$cookies = Service::Request()
				->Cookies()
				->getArrayCopy();

			$this->applyParams($xslt, $cookies);
			$this->applyParams($xslt, $_REQUEST);
			$this->applyParams($xslt, $_SERVER, '_');
			return $xslt->transformToXml($dom);
		}

		/**
		 * Передает массив параметров в xslt шаблон
		 * @param xsltProcessor $xslt шаблон
		 * @param array $params массив параметров
		 * @param string $prefix префикс для имен параметров
		 */
		protected function applyParams(XSLTProcessor $xslt, array $params, $prefix = '') {
			if (!is_string($prefix)) {
				$prefix = '';
			}

			foreach ($params as $key => $value) {
				$key = mb_strtolower($key);

				if (is_array($value)) {
					$this->applyParams($xslt, $value, $prefix . $key . '.');
					continue;
				}

				if (contains($value, "'") && contains($value, '"')) {
					$value = str_replace("'", "\\\"", $value);
				}

				$key = str_replace([':'], [''], $key);
				$xslt->setParameter('', $prefix . $key, $value);
			}
		}

		protected function parsePath($path) {
			$protocol = $this->getProtocol();
			$path = mb_substr($path, mb_strlen($protocol));
			$parsedUrl = parse_url($path);
			$realPath = $parsedUrl['path'];

			if (mb_substr($realPath, -5) == '.json') {
				$realPath = mb_substr($realPath, 0, mb_strlen($realPath) - 5);
				$this->isJson = true;
			}

			$this->path = $realPath;
			self::$callLog[] = [$protocol . $path, false];

			$queryString = getArrayKey($parsedUrl, 'query');
			if ($queryString) {
				parse_str($queryString, $queryParams);
				$this->params = $queryParams;
				$_REQUEST = array_merge($_REQUEST, $queryParams);

				if (isset($queryParams['transform'])) {
					$this->transform = getArrayKey($queryParams, 'transform');
				}
			}

			$this->setCacheLifetime();
			$this->setMacrosExtendedResult();

			return $this->path;
		}

		/** Устанавливает срок жизни кэша для запроса протокола */
		private function setCacheLifetime() {
			$this->expire = (int) getArrayKey($this->params, 'expire');
			if ($this->expire) {
				return;
			}

			$config = mainConfiguration::getInstance();
			if (!$config->get('cache', 'streams.cache-enabled')) {
				return;
			}

			$expirationTime = (int) $config->get('cache', 'streams.cache-lifetime');
			if ($expirationTime > 0) {
				$this->expire = $expirationTime;
			}
		}

		/** Устанавливает список дополнительных полей и групп для результатов парсинга макроса */
		private function setMacrosExtendedResult() {
			if (!self::$allowExtendedOptions) {
				return;
			}

			$extendedGroups = [];
			if (!empty($this->params['extGroups'])) {
				$extendedGroups = explode(',', $this->params['extGroups']);
				$extendedGroups = array_unique(array_map('trim', $extendedGroups));
			}

			$extendedProps = [];
			if (!empty($this->params['extProps'])) {
				$extendedProps = explode(',', $this->params['extProps']);
				$extendedProps = array_unique(array_map('trim', $extendedProps));
			}

			def_module::setMacrosExtendedResult($extendedProps, $extendedGroups);
		}

		protected function normalizeString($str) {
			$str = urldecode($str);

			if (!preg_match("/[\x{0000}-\x{FFFF}]+/u", $str)) {
				$str = iconv('CP1251', 'UTF-8//IGNORE', $str);
			}

			return $str;
		}

		protected function setData($data) {
			if (!$data) {
				return false;
			}

			$event = new umiEventPoint('systemUmiBaseStreamSetData');
			$event->setParam('path', $this->path);
			$event->setParam('data', $data);
			$event->call();

			$this->data = $data;
			$this->length = bytes_strlen($data);
			return true;
		}

		protected function setDataError($errorCode) {
			$data = [
				'error' => [
					'attribute:code' => $errorCode,
					'node:message' => getLabel('error-' . $errorCode),
				],
			];
			$data = self::translateToXml($data);
			$this->setData($data);
			return true;
		}

		protected function translateToJSON($data) {
			$translator = new jsonTranslator;
			$translator->setCallback(getRequest('json-callback'));
			return $translator->translateToJson($data);
		}

		/**
		 * Удаляет из запроса протокола хеш, добавляемый в
		 * cmsController::executeStream().
		 * Необходим для более корректного кеширования
		 * протоколов.
		 * @param string $path запрос протокола
		 * @return mixed
		 */
		protected function removeHash($path) {
			if (!is_string($path)) {
				return $path;
			}

			$cleanPath = preg_replace('/([\?|\&]umiHash=\S{32})/', '', $path);
			if ($cleanPath === null) {
				return $path;
			}

			return $cleanPath;
		}

		/**
		 * Возвращает количество запросов к базе данных, произведенных за время выполнения протокола
		 * @return int
		 */
		protected function getQueryCount() {
			$queryCountAfterExecute = ConnectionPool::getInstance()
				->getConnection()
				->getQueriesCount();

			return $queryCountAfterExecute - $this->queryCountBeforeExecute;
		}

		/** Пытается загрузить кастомный конфигурационный файл */
		protected function tryLoadCustomConfig() {
			$templateId = $this->getRequestedTemplateId();
			$template = $this->getTemplateById($templateId);
			$autoloadEnabled = mainConfiguration::getInstance()
				->get('streams', 'udata.autoload.custom.config');

			if (!$template instanceof iTemplate && $autoloadEnabled) {
				$template = templatesCollection::getInstance()
					->getDefaultTemplate();
			}

			if ($template instanceof iTemplate) {
				$this->loadCustomConfig($template);
			}
		}

		/**
		 * Возвращает идентификатор запрошенного шаблона
		 * @return bool|mixed|null
		 */
		protected function getRequestedTemplateId() {
			return getRequest('template_id');
		}

		/**
		 * Возвращает шаблон по идентификатору
		 * @param int $id идентификатор шаблона
		 * @return bool|iTemplate
		 */
		protected function getTemplateById($id) {
			return templatesCollection::getInstance()
				->getTemplate($id);
		}

		/**
		 * Загружает кастомный конфигурационный файл
		 * @param iTemplate $template шаблон
		 * @return $this
		 */
		protected function loadCustomConfig(iTemplate $template) {
			$config = mainConfiguration::getInstance();

			try {
				$config->loadConfig($template->getConfigPath());
				$config->setReadOnlyConfig();
			} catch (Exception $exception)  {
				//nothing
			}

			return $this;
		}
	}
