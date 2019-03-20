<?php

	/** TPL шаблонизатор */
	class umiTemplaterTPL extends umiTemplater {

		/**
		 * Кэш загруженных шаблонов в памяти
		 * @static
		 * @var array
		 */
		protected static $templatesCache = []; // templates cache

		protected static $callStack;

		protected static $currentCallStackNode;

		protected static $callStackMaxStringLength = 100;

		protected $parseLevel = 0;

		protected static $maxParseLevel = 4;

		protected $executeOnlyAllowedMacroses;

		/**
		 * Стэк с результатами выполнения макросов
		 * @static
		 * @var array
		 */
		protected static $msResultStack = [];

		/**
		 * Короткие алиасы к макросам
		 * Эти макросы выполняются в момент выполнения коротких макросов
		 * @static
		 * @var array
		 */
		protected static $shortAliases = [
			'%menu%' => ['macros_menu'],
			'%header%' => ['macros_header'],
			'%pid%' => ['macros_returnPid'],
			'%parent_id%' => ['macros_returnParentId'],
			'%pre_lang%' => ['macros_returnPreLang'],
			'%curr_time%' => ['macros_curr_time'],
			'%domain%' => ['macros_returnDomain'],
			'%domain_floated%' => ['macros_returnDomainFloated'],
			'%system_build%' => ['macros_systemBuild'],
			'%title%' => ['macros_title'],
			'%sitename%' => ['macros_sitename'],
			'%keywords%' => ['macros_keywords'],
			'%describtion%' => ['macros_describtion'],
			'%description%' => ['macros_describtion'],
			'%adm_menu%' => ['macros_adm_menu'],
			'%adm_navibar%' => ['macros_adm_navibar'],
			'%skin_path%' => ['macros_skin_path'],
			'%ico_ext%' => ['macros_ico_ext'],
			'%current_user_id%' => ['macros_current_user_id'],
			'%current_version_line%' => ['macros_current_version_line'],
			'%context_help%' => ['macros_help'],
			'%current_alt_name%' => ['macros_current_alt_name'],
			'%csrf%' => ['macros_csrf'],
			'%page_num%' => ['macros_getPageNumber']
		];

		private function prepareVariableForCallStack($value) {
			if (!is_scalar($value)) {
				return gettype($value);
			}

			if (is_string($value) && mb_strlen($value) > self::$callStackMaxStringLength) {
				return mb_substr($value, 0, self::$callStackMaxStringLength) . '...';
			}
			return $value;
		}

		/**
		 * Записывает в стек лог начала парсинга
		 * @param $variables
		 * @param $content
		 * @return DOMElement
		 */
		private function startParseCallReport($variables, $content) {
			if (!self::isEnabledCallStack()) {
				return null;
			}

			$callStack = $this->getCallStack();

			$content = $this->prepareVariableForCallStack($content);
			$callPoint = $callStack->createElement('parse-block');

			$contentNode = $callStack->createElement('parsed-content');
			$contentNode->appendChild($callStack->createCDATASection($content));
			$callPoint->appendChild($contentNode);

			$scopeNode = $callStack->createElement('scope');
			foreach ($variables as $var => $val) {
				$val = $this->prepareVariableForCallStack($val);
				$varNode = $callStack->createElement('var');
				$varNode->setAttribute('name', $var);
				$varNode->setAttribute('type', gettype($val));
				$varNode->appendChild($callStack->createCDATASection($val));
				$scopeNode->appendChild($varNode);
			}
			$callPoint->appendChild($scopeNode);
			$callPoint->setAttribute('start', microtime(true));

			if (!$parentStackNode = self::$currentCallStackNode) {
				$parentStackNode = $callStack->documentElement;
			}
			return self::$currentCallStackNode = $parentStackNode->appendChild($callPoint);
		}

		/**
		 * Записывает в стек лог конца парсинга
		 * @param DOMElement|null $callPoint
		 * @return DOMElement
		 */
		private function stopCallReport($callPoint) {
			if (!self::isEnabledCallStack()) {
				return null;
			}

			if (!$callPoint instanceof DOMElement) {
				return null;
			}

			$startTime = $callPoint->getAttribute('start');
			if ($startTime) {
				$startTime = (float) $startTime;
				$callPoint->setAttribute('call-time', number_format(microtime(true) - $startTime, 5));
			}

			self::$currentCallStackNode = $callPoint->parentNode;
			return $callPoint;
		}

		/**
		 * Записывает в стек лог начала запуска макроса
		 * @param (string) $macros
		 * @return DOMElement
		 */
		private function startMacrosExecuteCallReport($macros) {
			if (!self::isEnabledCallStack()) {
				return null;
			}
			$callStack = $this->getCallStack();

			$callPoint = $callStack->createElement('macros');
			$callPoint->setAttribute('name', $macros);

			$callPoint->setAttribute('start', microtime(true));

			if (!$parentStackNode = self::$currentCallStackNode) {
				$parentStackNode = $callStack->documentElement;
			}
			return self::$currentCallStackNode = $parentStackNode->appendChild($callPoint);
		}

		/**
		 * Установить режим работы, когда запускаются только разрешенные макросы
		 * @param array|null $allowedMacroses список разрешенных макросов
		 * Пример списка array('menu', 'content/get_page_url', ...)
		 */
		public function executeOnlyAllowedMacroses($allowedMacroses) {
			$this->executeOnlyAllowedMacroses = $allowedMacroses;
		}

		/**
		 * Проверяет, можно ли запускать макрос
		 * Если не задан executeOnlyAllowedMacroses, всегда возвращает true
		 * @param $macrosURI
		 * @return bool
		 */
		private function isExecuteMacrosAllowed($macrosURI) {
			if (!is_array($this->executeOnlyAllowedMacroses) || !umiCount($this->executeOnlyAllowedMacroses)) {
				return true;
			}
			return in_array($macrosURI, $this->executeOnlyAllowedMacroses);
		}

		/**
		 * Парсит $content, используя $variables
		 * @param mixed $variables
		 * @param mixed $content
		 * @return string
		 */
		public function parse($variables, $content = null) {

			if (is_array($content) || is_object($content) || $content === null) {
				return '';
			}

			if (is_bool($content)) {
				$content = (int) $content;
			}

			if (is_int($content) || is_float($content)) {
				return $content;
			}

			$content = (string) $content;
			if (!$content) {
				return $content;
			}

			if (!contains($content, '%') && !contains($content, '[ms_')) {
				return $content;
			}

			$callPoint = $this->startParseCallReport($variables, $content);

			// прерываем глубокий рекурсивный парсинг
			if ($this->parseLevel > self::$maxParseLevel) {
				return $content;
			}

			// отключаем XSLT-режим работы макросов
			$oldResultMode = def_module::isXSLTResultMode(false);

			$oldContent = $content;

			// Если в контенте есть комментарии - временно заменяем их
			$content = $this->replaceCommentsBeforeParse($content);

			// парсим короткие макросы: переменные из $variables, макросы текущего скопа, глобальные макросы, короткие алиасы
			if ($this->scopeElementId) {
				$content = str_replace('%id%', $this->scopeElementId, $content);
			}

			$content = $this->parseShortMacroses($content, $variables);

			// парсим сложные макросы
			$content = $this->parseCompleteMacroses($content, $variables);

			// восстанавливаем старый режим работы макросов
			def_module::isXSLTResultMode($oldResultMode);

			// прерываем парсинг, если контент не изменился за итерацию
			if ($oldContent === $content) {
				$this->stopCallReport($callPoint);
				return $content;
			}

			// заменяем uid макросов на их результат
			if (contains($content, '[ms_')) {
				$content = str_replace(array_keys(self::$msResultStack), array_values(self::$msResultStack), $content);
			}

			if (contains($content, '%')) {
				$this->parseLevel++;
				$content = $this->parse($variables, $content);
			}

			$this->stopCallReport($callPoint);

			return $content;
		}

		/**
		 * @static
		 * Загружает все шаблоны из указанного источника и возвращает шаблоны с указанными именами
		 * @param string $templatesSource - источник шаблонов
		 * @return array - список запрошенных шаблонов
		 */
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

		/**
		 * @static
		 * Подключает и возвращает все шаблоны из файла-источника
		 * Использует кэширование загруженных ранее источников
		 *
		 * @param string $templatesSource - файл с шаблонами
		 * @return array - все шаблоны из источника в виде array('tpl_name' => tpl_content, ..)
		 *
		 * @throws publicException - если шаблон не найден
		 */
		public static function loadTemplates($templatesSource) {
			if (empty($templatesSource)) {
				return [];
			}

			$realPath = realpath($templatesSource);
			$hash = md5($realPath);
			if (isset(self::$templatesCache[$hash])) {
				return self::$templatesCache[$hash];
			}

			if (!is_file($realPath)) {
				throw new publicException(getLabel('error-cannot-connect-template') . " {$templatesSource}", 2);
			}

			$FORMS = [];

			ob_start();
			include $realPath;
			$templateContent = (string) ob_get_clean();

			if (!umiCount($FORMS) && $templateContent !== '') {
				$FORMS['common'] = $templateContent;
			}

			return self::$templatesCache[$hash] = $FORMS;
		}

		/**
		 * Парсит короткие макросы вида %macros%
		 * @param string $content
		 * @param array $variables - переменные для парсинга блока
		 * @return mixed
		 */
		protected function parseShortMacroses($content, array $variables) {
			if (!contains($content, '%')) {
				return $content;
			}

			if (preg_match_all('/%[A-z0-9][A-z0-9_-]{1,}%/m', $content, $matches)) {
				$macroses = array_unique($matches[0]);
				$fromReplace = [];
				$toReplace = [];
				foreach ($macroses as $macros) {
					if (!$this->isExecuteMacrosAllowed(trim($macros, '%'))) {
						continue;
					}

					// fix urlencoded entites (%f0,%67, ...)
					$entity = rtrim($macros, '%');

					if (mb_strlen($entity) == 3 && urldecode($entity) !== $entity) {
						continue;
					}

					$fromReplace[] = $macros;
					$toReplace[] = $this->executeShortMacros($macros, $variables);
				}

				$content = str_replace($fromReplace, $toReplace, $content);
			}

			return $content;
		}

		protected function generateMSResultUID() {
			static $nextNum = 0;
			return '[ms_' . ++$nextNum . ']';
		}

		protected function setMSResult($resultUID, $result) {
			self::$msResultStack[$resultUID] = $result;
		}

		/**
		 * Выводит переменные из текущего scope по системному шаблону
		 * @param array $variables - переменные для блока
		 * @return string
		 */
		protected function printScopeVariables(array $variables) {
			$scopeObject = $this->getScopeObject();

			if ($scopeObject) {
				$scopeFields = $scopeObject->getType()->getAllFields();
				foreach ($scopeFields as $field) {
					$name = $field->getName();
					if (!isset($variables[$name])) {
						$variables[$name] = $scopeObject->getValue($name);
					}
				}
			}

			// parse scope
			$resourcesDir = cmsController::getInstance()->getResourcesDirectory();
			if ($resourcesDir) {
				$templateSrc = $resourcesDir . 'tpls/system/reflection.tpl';
			}

			if (!isset($templateSrc) || !file_exists($templateSrc)) {
				$templateSrc = CURRENT_WORKING_DIR . '/tpls/system/reflection.tpl';
			}

			list(
				$block, $varLine, $macroLine
				) = $this->getTemplates($templateSrc, 'scope_dump_block', 'scope_dump_line_variable', 'scope_dump_line_macro');
			$assembledLines = '';

			foreach ($variables as $name => $value) {
				if ($name == '#meta') {
					continue;
				}
				if (is_array($value)) {
					$tmp = str_replace('%name%', $name, $macroLine);
				} else {
					$tmp = $varLine;
					$tmp = str_replace('%name%', $name, $tmp);
					$tmp = str_replace('%type%', gettype($value), $tmp);
					$tmp = str_replace('%value%', htmlspecialchars($value), $tmp);
				}
				$assembledLines .= $tmp;
			}

			if (isset($scopeVariables['#meta'])) {
				$scopeName = isset($scopeVariables['#meta']['name']) ? $scopeVariables['#meta']['name'] : '';
				$scopeFile = isset($scopeVariables['#meta']['file']) ? $scopeVariables['#meta']['file'] : '';
			} else {
				$scopeName = '';
				$scopeFile = '';
			}

			$block = str_replace('%lines%', $assembledLines, $block);
			$block = str_replace('%block_name%', $scopeName, $block);
			$block = str_replace('%block_file%', $scopeFile, $block);
			$block = preg_replace('/%[A-z0-9_]+%/i', '', $block);
			return $block;
		}

		/**
		 * Обрабатывает короткие макросы, возвращает result uid,
		 * либо результат работы макроса, если макрос не может вернуть вложенных макросов
		 * @param string $macros
		 * @param array $variables
		 * @return string
		 */
		protected function executeShortMacros($macros, array $variables) {
			$var = trim($macros, '%');
			$macrosResult = $macros;
			$cmsController = cmsController::getInstance();

			if ($macros == '%template_resources%') {
				return $cmsController->getResourcesDirectory(true);
			}

			if ($macros == '%template_name%') {
				$template = $cmsController->detectCurrentDesignTemplate();
				if ($template instanceof iTemplate) {
					return $template->getName();
				}

				return '';
			}

			if ($macros == '%scope%') {
				return $this->printScopeVariables($variables);
			}

			if (array_key_exists($var, $variables) && !is_array($variables[$var])) {
				// если это переменная из $variables
				$macrosResult = (string) $variables[$var];
			} elseif (array_key_exists($var, $cmsController->getLangConstantList()) &&
				!is_array($cmsController->getLangConstantList()[$var])) {
				// макрос из langs
				$macrosResult = $cmsController->getLangConstantList()[$var];
			} elseif (isset(self::$shortAliases[$macros])) {
				// если это короткий алиас
				$macrosInfo = self::$shortAliases[$macros];
				if (isset($macrosInfo[0])) {
					$module = $macrosInfo[0];
					$method = isset($macrosInfo[1]) ? $macrosInfo[1] : null;
					$macrosArgs = (isset($macrosInfo[2]) && is_array($macrosInfo[2])) ? $macrosInfo[2] : [];
					return $resultUID = $this->executeCompleteMacros($module, $method, $macrosArgs, $variables);
				}
			} elseif (($scopeObject = $this->getScopeObject()) instanceof iUmiObject) {
				//  специальные макросы для скопа
				if ($var == 'block-element-id') {
					$macrosResult = $this->scopeElementId > 0 ? $this->scopeElementId : $cmsController->getCurrentElementId();
				} elseif ($var == 'block-object-id') {
					$macrosResult = $this->scopeObjectId;
				} elseif ($scopeObject->getPropByName($var) instanceof iUmiObjectProperty) {
					// если это переменная из скопа
					$val = $scopeObject->getValue($var);
					if (is_object($val)) {
						switch (true) {
							case $val instanceof iUmiDate :
								$macrosResult = $val->getFormattedDate('U');
								break;
							case $val instanceof iUmiFile :
								$macrosResult = $val->getFilePath(true);
								break;
							case $val instanceof iUmiObject:
							case $val instanceof iUmiHierarchyElement:
								$macrosResult = $val->getName();
								break;
						}
					} elseif (is_array($val)) {
						$sz = umiCount($val);
						$macrosResult = '';
						for ($i = 0; $i < $sz; $i++) {
							$cval = $val[$i];

							if (is_numeric($cval)) {
								$obj = umiObjectsCollection::getInstance()->getObject($cval);

								if ($obj) {
									$cval = $obj->getName();
								} else {
									continue;
								}
							}

							if ($cval instanceof iUmiHierarchyElement) {
								$cval = $cval->getName();
							}

							$macrosResult .= $cval;
							if ($i < ($sz - 1)) {
								$macrosResult .= ', ';
							}
						}
					} else {
						$macrosResult = $val;
					}
				}
			}

			if ($macrosResult === $macros) {
				return $macros;
			}

			// запускаем рекурсивный парсинг вложенных макросов
			$this->parseLevel++;
			$macrosResult = $this->parse($variables, $macrosResult);
			$this->parseLevel--;

			$resultUID = $this->generateMSResultUID();
			$this->setMSResult($resultUID, $macrosResult);

			return $resultUID;
		}

		protected function executeCompleteMacros($module, $method = null, $args = [], array $variables) {
			$controller = cmsController::getInstance();
			$resultUID = $this->generateMSResultUID();
			$macrosResult = '%' . $module . ' ' . $method . '(' . implode(',', $args) . ')%';

			// заменяем macros uid на реальное значение в аргументах
			$countArgs = umiCount($args);
			for ($i = 0; $i < $countArgs; $i++) {
				if (isset(self::$msResultStack[$args[$i]])) {
					$args[$i] = self::$msResultStack[$args[$i]];
				} elseif (contains($args[$i], '[ms_')) {
					$args[$i] = str_replace(array_keys(self::$msResultStack), array_values(self::$msResultStack), $args[$i]);
				}
			}

			// если не пришел метод, пытаемся запустить $module как функцию из def_macroses
			if ($method === null && is_callable($module)) {
				// TODO: зарефакторить все макросы из def_macroses
				$macrosResult = $module($args);
			} else {
				$moduleInst = null;

				if ($module == 'core' || $module == 'system' || $module == 'custom') {
					$moduleInst = system_buildin_load($module);
				} elseif (system_is_allowed($module, $method)) {
					$moduleInst = $controller->getModule($module);
				} elseif (defined('DEBUG') && DEBUG) {
					$macrosResult = "You are not allowed to execute {$module}/{$method}";
				} else {
					$macrosResult = '';
				}

				if ($moduleInst) {
					try {
						$macrosResult = $moduleInst->cms_callMethod($method, $args);
					} catch (publicException $e) {
						$macrosResult = $e->getMessage();
					}
				}
			}

			// запускаем рекурсивный парсинг вложенных макросов
			$this->parseLevel++;
			$macrosResult = $this->parse($variables, $macrosResult);
			$this->parseLevel--;

			$this->setMSResult($resultUID, $macrosResult);

			return $resultUID;
		}

		protected function parseCompleteMacroses($content, array $variables) {
			if (!contains($content, '%')) {
				return $content;
			}

			if (preg_match_all("/%([A-z0-9]+)\s+([A-z0-9_]+)\s*\(([^%]*)\)%/mu", $content, $matches, PREG_SET_ORDER)) {
				$executed = [];

				foreach ($matches as $macrosInfo) {
					$macros = $macrosInfo[0];

					// фильтруем одинаковые макросы
					if (isset($executed[$macros])) {
						continue;
					}

					$module = $macrosInfo[1];
					$method = $macrosInfo[2];

					if (!$this->isExecuteMacrosAllowed($module . '/' . $method)) {
						continue;
					}

					$args = trim($macrosInfo[3]);
					$args = $args !== '' ? explode(',', $args) : [];

					$countArgs = umiCount($args);
					for ($i = 0; $i < $countArgs; $i++) {
						$args[$i] = trim($args[$i], "'\" ");
					}
					$callPoint = $this->startMacrosExecuteCallReport($macros);
					$resultUID = $this->executeCompleteMacros($module, $method, $args, $variables);
					$this->stopCallReport($callPoint);
					$content = str_replace($macros, $resultUID, $content);
					$executed[$macros] = 1;
				}
			}

			return $content;
		}

		/**
		 * Возвращает стек вызовов для TPL-шаблонизатора
		 * @return DOMDocument
		 */
		private function getCallStack() {
			if (!self::$callStack) {
				self::$callStack = new DOMDocument('1.0', 'utf-8');
				self::$callStack->formatOutput = XML_FORMAT_OUTPUT;
				self::$callStack->appendChild(self::$callStack->createElement('call-stack'));
			}
			return self::$callStack;
		}

		/**
		 * Возвращает стек вызовов для TPL-шаблонизатора в формате xml
		 * @return string
		 */
		public function getCallStackXML() {
			if (self::isEnabledCallStack()) {
				return $this->getCallStack()->saveXML();
			}

			return $this->disabledCallStackError();
		}
	}
