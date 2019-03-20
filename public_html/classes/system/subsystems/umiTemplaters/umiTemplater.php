<?php

	use UmiCms\Service;

	interface iUmiTemplater {

		public function setScope($elementId, $objectId = false);

		/**
		 * Подключает и возвращает все шаблоны из файла-источника
		 * Должен быть реализован в конкретном шаблонизаторе
		 * Использует кэширование загруженных ранее источников
		 * @param string $templatesSource - файл с шаблонами
		 * @return array - все шаблоны из источника
		 * @throws publicException - если шаблон не удалось подключить
		 */
		public static function loadTemplates($templatesSource);

		/**
		 * Возвращает список запрошенных шаблонов
		 * Должен быть реализован в конкретном шаблонизаторе
		 * Должен уметь принимать любое кол-во имен шаблонов и возвращать
		 * массив в виде order => шаблон, где order - порядковый номер запрашиваемого шаблона
		 * @param string $templatesSource - источник шаблонов
		 * @return array
		 * @throws publicException
		 */
		public static function getTemplates($templatesSource);

	}

	abstract class umiTemplater implements iUmiTemplater {

		/** @var string источник шаблонов */
		protected $templatesSource;

		/** @var mixed идентификатор страницы в области видимости */
		protected $scopeElementId = false;

		/** @var mixed идентификатор объекта в области видимости */
		protected $scopeObjectId = false;

		/** @var mixed область видимости */
		protected $scopeObject = false;

		/** @var bool Запущена ли система с мобильного устройства? */
		private $isMobile;

		/** @var array данные редактируемых с помощью EiP страниц */
		public static $blocks = [];

		/** @var bool Включен ли вывод стека вызовов? */
		protected static $callStackEnabled = false;

		/** @var array Стэк с вырезанными комментариями */
		protected static $commentsStack = [];

		/**
		 * Парсит контент, используя переменные из $variables
		 * @param mixed $variables переменные для парсинга контента
		 * @param mixed $content контент для парсинга
		 * @return string
		 * @throws \ErrorException
		 * @throws \Exception
		 */
		abstract public function parse($variables, $content = null);

		/**
		 * Конструктор
		 * @param string $templatesSource источник шаблонов
		 */
		public function __construct($templatesSource) {
			$this->templatesSource = $templatesSource;
			$this->isMobile = Service::Request()->isMobile();
		}

		/**
		 * Временно заменить комментарии в контенте, чтобы в них не обрабатывались макросы
		 * @param string $content html-содержимое страницы
		 * @return string
		 */
		protected function replaceCommentsBeforeParse($content) {
			$parseComments = (int) mainConfiguration::getInstance()->get('system', 'parse-macroses-in-comments');

			if (!contains($content, '<!--') || $parseComments) {
				return $content;
			}

			if (preg_match_all('/<!--.*?-->/mu', $content, $matches)) {
				$comments = array_unique($matches[0]);

				foreach ($comments as $comment) {
					$commentId = '[hc_' . md5($comment) . ']';
					if (!isset(self::$commentsStack[$commentId])) {
						self::$commentsStack[$commentId] = $comment;
					}
					$content = str_replace($comment, $commentId, $content);
				}
			}
			return $content;
		}

		/**
		 * Вернуть комментарии в контент после обработки макросов
		 * @param string $content html-содержимое страницы
		 * @return string
		 */
		public function replaceCommentsAfterParse($content) {
			return str_replace(array_keys(self::$commentsStack), array_values(self::$commentsStack), $content);
		}

		/**
		 * Установить "область видимости" коротких макросов
		 * @param int $elementId id страницы
		 * @param mixed $objectId id объекта
		 */
		public function setScope($elementId, $objectId = false) {
			$this->scopeElementId = $elementId;
			$this->scopeObjectId = $objectId;
			$this->scopeObject = false;
		}

		/**
		 * Область видимости коротких макросов (контекстный umiObject)
		 * @return mixed
		 */
		public function getScopeObject() {
			if ($this->scopeObject !== false) {
				return $this->scopeObject;
			}

			if ($this->scopeElementId === false && $this->scopeObjectId === false) {
				return $this->scopeObject = null;
			}

			$hierarchy = umiHierarchy::getInstance();
			$objects = umiObjectsCollection::getInstance();

			if ($this->scopeElementId && ($element = $hierarchy->getElement($this->scopeElementId))) {
				return $this->scopeObject = $element->getObject();
			}

			if ($this->scopeObjectId && ($object = $objects->getObject($this->scopeObjectId))) {
				return $this->scopeObject = $object;
			}

			return $this->scopeObject = null;
		}

		/**
		 * Почистить контент от мусора
		 * @param string $content html-содержимое страницы
		 * @return string
		 */
		public function cleanup($content) {
			$permissions = permissionsCollection::getInstance();
			$config = mainConfiguration::getInstance();

			if (!$permissions->isAdmin() && (int) $config->get('system', 'clean-eip-attributes')) {
				$content = $this->cleanEIPAttributes($content);
			}

			if (!(int) $config->get('kernel', 'show-broken-macro')) {
				$content = $this->cleanBrokenMacro($content);
			}

			$content = $this->replaceCommentsAfterParse($content);
			$content = str_replace(['&amp;#37;'], '%', $content);

			return $content;
		}

		/**
		 * Создать экземпляр шаблонизатора указанного типа
		 * @param string $type тип шаблонизатора
		 * @param mixed $templatesSource источник шаблонов
		 * @return umiTemplater
		 * @throws coreException
		 */
		final public static function create($type, $templatesSource = null) {
			$type = mb_strtoupper($type);

			if ($type === '') {
				throw new coreException('Templater type required for create instance.');
			}

			$className = __CLASS__ . $type;

			if (!class_exists($className)) {
				$filePath = dirname(__FILE__) . '/types/' . $className . '.php';
				if (!is_file($filePath)) {
					throw new coreException("Can't load templater implemantation \"{$filePath}\".");
				}
				require_once $filePath;
			}

			if (!class_exists($className)) {
				throw new coreException("Templater class \"{$className}\" not found");
			}

			$templater = new $className($templatesSource);

			if (!$templater instanceof umiTemplater) {
				throw new coreException("Templater class \"{$className}\" should be instance of " . __CLASS__);
			}

			if ($templater instanceof umiTemplaterPHP) {
				$config = mainConfiguration::getInstance();
				$phpTemplaterExtensions = $config->get('php-templater', 'extensions');
				if (is_array($phpTemplaterExtensions)) {
					$templater->loadExtension($phpTemplaterExtensions);
				}
			}

			return $templater;
		}

		/**
		 * Источник шаблонов
		 * @return string
		 */
		public function getTemplatesSource() {
			return $this->templatesSource;
		}

		/**
		 * Запущена ли система с мобильного устройства?
		 * @return bool
		 */
		final public function isMobile() {
			return $this->isMobile;
		}

		/**
		 * Записать данные редактируемой с помощью EiP страницы
		 * @param string $module имя модуля типа страницы
		 * @param string $method имя метода типа страницы
		 * @param int $id ID редактируемой страницы
		 * @return bool
		 */
		public static function pushEditable($module, $method, $id) {
			if ($module === false && $method === false) {
				$element = umiHierarchy::getInstance()->getElement($id);

				if ($element) {
					$objectTypeId = $element->getObjectTypeId();
					$objectType = umiObjectTypesCollection::getInstance()
						->getType($objectTypeId);

					if ($objectType) {
						$hierarchyTypeId = $objectType->getHierarchyTypeId();
						$hierarchyType = umiHierarchyTypesCollection::getInstance()
							->getType($hierarchyTypeId);

						if ($hierarchyType) {
							$module = $hierarchyType->getName();
							$method = $hierarchyType->getExt();
						} else {
							return false;
						}
					}
				}
			}

			self::$blocks[] = [$module, $method, $id];
			return true;
		}

		/** Записать в сессию информацию о редактируемых через EIP страницах */
		public static function prepareQuickEdit() {
			$toFlush = self::$blocks;

			if (umiCount($toFlush) == 0) {
				return;
			}

			$key = md5(getServerProtocol() . '://' . getServer('HTTP_HOST') . getServer('REQUEST_URI'));
			Service::Session()->set($key, $toFlush);
		}

		final public static function getSomething($versionLine = 'pro', $host = null) {
			$serverAddress = getServer('SERVER_ADDR');

			if ($host === null) {
				$host = Service::DomainCollection()
					->getDefaultDomain()
					->getHost();
			}

			$cs2 = $serverAddress ? md5($serverAddress) : md5(str_replace("\\", '', getServer('DOCUMENT_ROOT')));
			$cs3 = '';

			switch ($versionLine) {
				case 'pro':
					$cs3 = md5(md5(md5(md5(md5(md5(md5(md5(md5(md5($host))))))))));
					break;

				case 'shop':
					$cs3 = md5(md5($host . 'shop'));
					break;

				case 'lite':
					$cs3 = md5(md5(md5(md5(md5($host)))));
					break;

				case 'start':
					$cs3 = md5(md5(md5($host)));
					break;

				case 'trial':
					$cs3 = md5(md5(md5(md5(md5(md5($host))))));
					break;
				case 'ultimate':
					$cs3 = md5($host . 'ultimate');
					break;
				case '1cfranchisee':
					$cs3 = md5($host . '1cfranchisee');
					break;
				case 'umifree':
					$cs3 = md5($host . 'umifree');
					break;
			}

			return mb_strtoupper(mb_substr($cs2, 0, 11) . '-' . mb_substr($cs3, 0, 11));
		}

		/**
		 * Удалить EIP атрибуты (umi:element-id, xmlns:umi и т.д.) из контента
		 * @param string $content html-содержимое страницы
		 * @return string
		 */
		protected function cleanEIPAttributes($content) {
			$attributeValueRegex = "[\"'][^\"']*[\"']";
			$content = preg_replace("/[\\s+]umi\\:[^='\"]+={$attributeValueRegex}/i", '', $content);
			return preg_replace("/[\\s+]xmlns:umi={$attributeValueRegex}/i", '', $content);
		}

		/**
		 * Удалить неотработанные макросы из контента
		 * @param string $content html-содержимое страницы
		 * @return string
		 */
		protected function cleanBrokenMacro($content) {
			$content = preg_replace('/%(?!cut%)([A-z_]{3,})%/m', '', $content);
			$content = preg_replace(
				"/%([A-zА-Яа-я0-9]+\s+[A-zА-Яа-я0-9_]+\([A-zА-Яа-я \/\._\-\(\)0-9%:<>,!@\|'&=;\?\+#]*\))%/mu",
				'',
				$content
			);
			return $content;
		}

		/**
		 * Включить/выключить сбор стека вызовов для шаблонизатора
		 * @param bool $enabled новое значение
		 * @return bool
		 */
		public static function setEnabledCallStack($enabled = true) {
			return self::$callStackEnabled = $enabled;
		}

		/**
		 * Включен ли сбор стека вызовов?
		 * @return bool
		 */
		public static function isEnabledCallStack() {
			return self::$callStackEnabled;
		}

		/**
		 * Стек вызовов в формате xml
		 * @return string
		 */
		public function getCallStackXML() {
			if (self::isEnabledCallStack()) {
				return umiBaseStream::getCalledStreams();
			}
			return $this->disabledCallStackError();
		}

		protected function disabledCallStackError() {
			$dom = new DOMDocument('1.0', 'utf-8');
			$dom->appendChild($dom->createElement('error', 'Call stack disabled.'));
			return $dom->saveXML();
		}

		/** @deprecated используйте метод parseTPLMacroses() */
		public function putLangs($content) {
			return def_module::parseTPLMacroses($content);
		}

		/** @deprecated используйте метод parseTPLMacroses() */
		public function parseInput($input, $level = 1) {
			return def_module::parseTPLMacroses($input);
		}

		/** @deprecated используйте метод def_module::isXSLTResultMode() */
		public function getIsInited() {
			return def_module::isXSLTResultMode();
		}

		/** @deprecated используйте метод def_module::isXSLTResultMode() */
		public function setIsInited($new) {
			return def_module::isXSLTResultMode($new);
		}

		/** @deprecated используйте метод $this->parse() */
		public function parseResult() {
			return $this->parse([]);
		}

		/** @deprecated */
		public function init() {
		}

		/** @deprecated */
		public function setFilePath($filePath) {
			$this->templatesSource = $filePath;
		}

	}


