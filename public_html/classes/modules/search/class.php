<?php
	class search extends def_module {

		public function __construct() {
			parent::__construct();
			$configTabs = $this->getConfigTabs();

			$this->loadCommonExtension();

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$commonTabs = $this->getCommonTabs();

				$this->__loadLib("__admin.php");
				$this->__implement("__search");

				$umiConfig = mainConfiguration::getInstance();
				$controlTabName = ($umiConfig->get('modules', 'search.using-sphinx')) ? 'sphinx_control' : 'index_control';

				if ($commonTabs) {
					$commonTabs->add($controlTabName);
					$commonTabs->add('search_replace');
				}

				$configTabs = $this->getConfigTabs();

				if ($configTabs) {
					$configTabs->add("config");
				}

				$this->__loadLib("sphinx/SphinxIndexGenerator.php");

				$this->loadAdminExtension();

				$this->__loadLib("__custom_adm.php");
				$this->__implement("__search_custom_admin");
			} else {
				$this->per_page = regedit::getInstance()->getVal("//modules/search/per_page");
			}

			$this->__loadLib("sphinx/sphinxapi.php");

			$this->loadSiteExtension();

			$this->__loadLib("__custom.php");
			$this->__implement("__custom_search");
		}

		public function search_do($template = "default", $search_string = "", $search_types = "", $search_branches = "", $per_page = 0) {
			// поисковая фраза :
			if (!$search_string) {
				$search_string = (string) getRequest('search_string');
			}

			$p = (int) getRequest('p');
			// если запрошена нетипичная постраничка
			if (!$per_page) {
				$per_page = intval(getRequest('per_page'));
			}
			if (!$per_page) {
				$per_page = $this->per_page;
			}

			$config = mainConfiguration::getInstance();
			$searchEngine = $config->get('modules', 'search.using-sphinx');
			if ($searchEngine){
				return $this->sphinxSearch($template, $search_string, $per_page, $p);
			}

			list(
				$template_block, $template_line, $template_empty_result, $template_line_quant
			) = self::loadTemplates("search/".$template,
				"search_block", "search_block_line", "search_empty_result", "search_block_line_quant"
			);

			$block_arr = Array();
			$block_arr['last_search_string'] = htmlspecialchars($search_string);

			$search_string = urldecode($search_string);
			$search_string = htmlspecialchars($search_string);
			$search_string = str_replace(". ", " ", $search_string);
			$search_string = trim($search_string, " \t\r\n%");
			$search_string = str_replace(array('"', "'"), "", $search_string);

			$orMode = (bool) getRequest('search-or-mode');

			if (!$search_string) return $this->insert_form($template);

			// если запрошен поиск только по определенным веткам :
			$arr_search_by_rels = array();
			if (!$search_branches) $search_branches = (string) getRequest('search_branches');
			$search_branches = trim(rawurldecode($search_branches));
			if (strlen($search_branches)) {
				$arr_branches = preg_split("/[\s,]+/", $search_branches);
				foreach ($arr_branches as $i_branch => $v_branch) {
					$arr_branches[$i_branch] = $this->analyzeRequiredPath($v_branch);
				}
				$arr_branches = array_map('intval', $arr_branches);
				$arr_search_by_rels = array_merge($arr_search_by_rels, $arr_branches);
				$o_selection = new umiSelection;
				$o_selection->addHierarchyFilter($arr_branches, 100, true);
				$o_result = umiSelectionsParser::runSelection($o_selection);
				$sz = umiCount($o_result);
				for ($i = 0; $i < $sz; $i++) $arr_search_by_rels[] = intval($o_result[$i]);
			}

			$search_types = $this->getSearchTypes($search_types);

			$lines = Array();
			$result = searchModel::getInstance()->runSearch($search_string, $search_types, $arr_search_by_rels, $orMode);
			$total = umiCount($result);

			$result = array_slice($result, $per_page * $p, $per_page);
			$this->loadElements($result);
			$i = $per_page * $p;
			$umiHierarchy = umiHierarchy::getInstance();

			foreach($result as $num => $element_id) {
				$line_arr = Array();

				$element = $umiHierarchy->getElement($element_id);

				if(!$element) {
					continue;
				}

				$line_arr['type'] = $this->getTypeInfo($element);
				$line_arr['void:num'] = ++$i;
				$line_arr['attribute:id'] = $element_id;
				$line_arr['attribute:name'] = $element->getName();
				$line_arr['attribute:link'] = $this->umiLinksHelper->getLinkByParts($element);
				$line_arr['xlink:href'] = "upage://" . $element_id;
				$line_arr['node:context'] = searchModel::getInstance()->getContext($element_id, $search_string);
				$line_arr['void:quant'] = ($num < umiCount($result)-1? self::parseTemplate($template_line_quant, array()) : "");
				$lines[] = self::parseTemplate($template_line, $line_arr, $element_id);

				$this->pushEditable(false, false, $element_id);

				umiHierarchy::getInstance()->unloadElement($element_id);
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;

			return self::parseTemplate(($total > 0 ? $template_block : $template_empty_result), $block_arr);
		}

		/**
		 * Возвращает данные о иерархическом типе найденного элемента
		 * @param iUmiHierarchyElement $element объект найденного элемента
		 * @return array
		 */
		public function getTypeInfo(iUmiHierarchyElement $element) {
			$type = $element->getHierarchyType();

			if (!$type instanceof iUmiHierarchyType) {
				return array();
			}

			$labelPrefix = 'module-';
			$moduleTitle = getLabel($labelPrefix . $type->getModule());

			return array(
				'@name' => $type->getTitle(),
				'@module' => $moduleTitle,
				'@id'	=> $type->getId()
			);
		}

		/**
		 * Возвращает список ID иерархических типов, по которым будет осуществляться поиск
		 * @param string $typesFromMacro иерархические типы, которые были переданы в макрос
		 * @return array|mixed
		 */
		public function getSearchTypes($typesFromMacro = '') {
			$types = array();

			if (!$typesFromMacro)  {
				$types = (string) getRequest('search_types');
			}

			if (!$types)  {
				$types = regedit::getInstance()->getVal('//modules/search/search-types');
			}

			$types = rawurldecode($types);

			if (strlen($types)) {
				$types = preg_split("/[\s,]+/", $types);
				$types = array_map('intval', $types);
			}

			return $types;
		}


		public function insert_form($template = "default") {
			list($template_block) = self::loadTemplates("search/".$template, "search_form");

			$search_string = (string) getRequest('search_string');
			$search_string = strip_tags($search_string);
			$search_string = trim($search_string, " \t\r\n%");
			$search_string = htmlspecialchars(urldecode($search_string));
			$search_string = str_replace(array('"', "'"), "", $search_string);

			$orMode = (bool) getRequest('search-or-mode');

			$block_arr = Array();
			$block_arr['last_search_string'] = ($search_string) ? $search_string : "%search_input_text%";

			if($orMode) {
				$block_arr['void:search_mode_and_checked'] = "";
				$block_arr['void:search_mode_or_checked'] = " checked";
			} else {
				$block_arr['void:search_mode_and_checked'] = " checked";
				$block_arr['void:search_mode_or_checked'] = "";
			}
			return self::parseTemplate($template_block, $block_arr);
		}

		public function suggestions($template = 'default', $string = false, $limit = 10) {
			if($string == false) $string = getRequest('suggest-string');

			list($template_block, $template_line, $template_block_empty) = self::loadTemplates(
				"tpls/search/".$template, "suggestion_block", "suggestion_block_line", "suggestion_block_empty"
			);

			$search = searchModel::getInstance();
			$words = $search->suggestions($string, $limit);
			$total = umiCount($words);

			if($total == 0) {
				return self::parseTemplate($template_block_empty, array());
			}

			$items_arr = array();
			foreach($words as $word) {
				$item_arr = array(
					'attribute:count'	=> $word['cnt'],
					'node:word'			=> $word['word']
				);

				$items_arr[] = self::parseTemplate($template_line, $item_arr);
			}

			$block_arr = array(
				'words'	=> array('nodes:word' => $items_arr),
				'total'	=> $total
			);

			return self::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Отображение результатов поиска
		 * @param string $template шаблон
		 * @param string $searchString поисковая фраза
		 * @param int $perPage количество элементов на странице
		 * @param $p номер страницы
		 * @return mixed|string
		 */
		protected function sphinxSearch($template = 'default', $searchString = '', $perPage = 0, $p) {
			list(
				$template_block, $template_line, $template_empty_result, $template_line_quant
				) = self::loadTemplates("search/".$template,
				"search_block", "search_block_line", "search_empty_result", "search_block_line_quant"
			);

			if (!$searchString) {
				return $this->insert_form($template);
			}

			$result = Array();
			$items = Array();

			$index = '*';
			$limitResult = 1000;
			$config = mainConfiguration::getInstance();
			$sphinxHost = $config->get('sphinx', 'sphinx.host');
			$sphinxPort = (int) $config->get('sphinx', 'sphinx.port');


			$sphinx = new SphinxClient;
			$sphinx->SetServer($sphinxHost, $sphinxPort);
			if (!$sphinx->open()) {
				return;
			}

			$resultSphinx = $this->findResult($searchString, $limitResult, $index, $sphinx);
			if (empty($resultSphinx) || !array_key_exists('matches', $resultSphinx)) {
				return;
			}
			$resultMatches = $resultSphinx['matches'];
			$total = umiCount($resultMatches);
			$resultMatches = array_slice($resultMatches, $perPage * $p, $perPage);

			$i = $perPage * $p;
			foreach($resultMatches as $num => $element) {
				$item = Array();
				/** @var umiHierarchyElement $element */
				$page_weight = $element['weight'];
				$element = $element['page'];

				if(!$element) {
					continue;
				}

				$content = $element->getValue('content');
				$pattern = '/%[^\s](.*?)[^\s]%/i';
				$content = preg_replace($pattern, '', $content);

				$item['type'] = $this->getTypeInfo($element);
				$item['void:num'] = ++$i;
				$item['attribute:id'] = $element->getId();
				$item['attribute:name'] = $element->getName();
				$item['attribute:weight'] = $page_weight;
				$item['attribute:link'] = $this->umiLinksHelper->getLinkByParts($element);
				$item['xlink:href'] = "upage://" . $element->getId();
				$item['node:context'] = '<p>' . $this->highlighter(array($content), $searchString, $sphinx) . '</p>';
				$item['void:quant'] = ($num < umiCount($resultMatches)-1? self::parseTemplate($template_line_quant, array()) : "");
				$items[] = self::parseTemplate($template_line, $item, $element->getId());

				templater::pushEditable(false, false, $element->getId());

				umiHierarchy::getInstance()->unloadElement($element->getId());
			}

			$result['subnodes:items'] = $result['void:lines'] = $items;
			$result['total'] = $total;
			$result['per_page'] = $perPage;
			$result['last_search_string'] = "";

			return self::parseTemplate(($total > 0 ? $template_block : $template_empty_result), $result);
		}

		/**
		 * Отправляет на Sphinx запрос для поиска данных
		 * @param $phrase поисковая фраза
		 * @param $limit
		 * @param $index
		 * @param $sphinx SphinxClient
		 * @internal param \SphinxClient $MAX_MATCHES Максимальное кол-во документов в результате, которое сфинкс держит в памяти
		 * @internal param $fieldWeights Веса полей в ранжировании результатов
		 * @return array
		 */
		protected function findResult($phrase, $limit, $index, $sphinx) {
			$config = mainConfiguration::getInstance();
			define('MAX_MATCHES' , 1000);
			define('HIGHLIGHT_INDEX' , 'content');

			$fieldWeights = array(
				'title' => (int) ($config->get('sphinx', 'sphinx.title') ? $config->get('sphinx', 'sphinx.title') : 10),
				'h1' => (int) ($config->get('sphinx', 'sphinx.h1') ? $config->get('sphinx', 'sphinx.h1') : 10),
				'meta_keywords' => (int) ($config->get('sphinx', 'sphinx.meta_keywords') ? $config->get('sphinx', 'sphinx.meta_keywords') : 5),
				'meta_descriptions' => (int) ($config->get('sphinx', 'sphinx.meta_descriptions') ? $config->get('sphinx', 'sphinx.meta_descriptions') : 3),
				'content' => (int) ($config->get('sphinx', 'sphinx.field_content') ? $config->get('sphinx', 'sphinx.field_content') : 1),
				'tags' => (int) ($config->get('sphinx', 'sphinx.tags') ? $config->get('sphinx', 'sphinx.tags') : 50)
			);

			if ($phrase) {
				$sphinx->open();

				$sphinx->SetSortMode(SPH_SORT_RELEVANCE);
				$sphinx->setFieldWeights($fieldWeights);

				$sphinx->setLimits(0, $limit, 1000);

				$event = new umiEventPoint("sphinxExecute");
				$event->setParam("sphinx", $sphinx);
				$event->setMode("before");
				$event->call();

				$sphinx->ResetGroupBy();
				$sphinx->SetFilter(
					'domain_id',
					array(
						cmsController::getInstance()->getCurrentDomain()->getId()
					)
				);

				$results = $sphinx->query($sphinx->escapeString($phrase), $index);

				$pages = umiHierarchy::getInstance();
				if (is_array($results) && array_key_exists('matches', $results)) {
					$ids = array();
					foreach ($results['matches'] as $id => $document) {
						$ids[] = $id;
					}
					$this->loadElements($ids);
					foreach ($results['matches'] as $id => $document) {
						if ($page = $pages->getElement($id)) {
							$results['matches'][$id]['page'] = $page;
						} else {
							unset($results['matches'][$id]);
						}
					}
				}

				return $results;
			}
		}

		/**
		 * Подсветка текста в сниппите
		 * @param $var массив текстов для подсветки
		 * @param $phrase поисковая фраза
		 * @param $sphinx SphinxClient
		 * @return mixed
		 */
		protected function highlighter($var, $phrase, $sphinx) {
			$res = $sphinx->buildExcerpts($var, HIGHLIGHT_INDEX, $phrase, array(
				'before_match' => '<strong>',
				'after_match' => '</strong>'
			));
			return $res[0];
		}

		/**
		 * Производит замену вхождений строки
		 * @param string $content область поиска
		 * @param string $search искомое значение
		 * @param string $replace значение, на которое нужно заменить найденные вхождения искомой строки
		 * @param int $mode режим работы:
		 * 		1 - замена в url'ах, 2 - замена только в тексте, все остальное - замена везде
		 * @return string
		 */
		public function replaceText($content, $search, $replace, $mode){
			switch ($mode) {
				case 1: {
					preg_match_all('#href=[\"| \'](.*?)[\"| \']#', $content, $matches);
					$urls = array();

					foreach ($matches[0] as $url) {
						$urls[] = array(
							'source' => $url,
							'result' => str_replace($search, $replace, $url)
						);
					}

					foreach ($urls as $url) {
						$content = str_replace($url['source'], $url['result'], $content);
					}

					return $content;
				}
				case 2: {
					preg_match_all('#href=[\"| \'](.*?)[\"| \']#', $content, $matches);

					foreach ($matches[1] as $key => $url){
						$content = str_replace($url, '[URL-' . $key . ']', $content);
					}

					$content = str_replace($search, $replace, $content);

					foreach ($matches[1] as $key => $url) {
						$content = preg_replace('/(\[URL\-' . $key . '+\])/', $url, $content);
					}

					return $content;
				}
				default: {
					return str_replace($search, $replace, $content);
				}
			}
		}

		/**
		 * Оформляет вхождение строки в контент поля
		 * @param string $content область поиска
		 * @param string $searchString искомое значение
		 * @param string $type название поля
		 * @param int $mode режим работы:
		 * 		1 - вхождения в url'ах, 2 - вхождения только в тексте, все остальное - вхождения везде
		 * @param string $modeLabel идентификатор типа вхождения (link|text)
		 * @return string
		 */
		public function searchWithSnippet($content, $searchString, $type, $mode, $modeLabel){
			$result = false;

			switch ($mode) {
				case 1: {
					preg_match_all('#href=[\"| \'](.*?)[\"| \']#', $content, $matches);

					foreach($matches[1] as $url) {
						$result .= $this->searchWithSnippet($url, $searchString, $type, 0, $modeLabel);
					}

					return $result;
				}
				case 2: {
					$content = preg_replace('#<a[^>]+>#i', '', $content);
					break;
				}
			}

			while (strpos($content, $searchString) !== false){
				$position = mb_strpos($content, $searchString);
				$snippet = mb_substr($content, ($position - 50 < 0) ? 0 : $position - 50, mb_strlen($searchString) + 100);
				$snippet = htmlspecialchars($snippet);
				$searchString = htmlspecialchars($searchString);
				$result .= $type .'(' . $modeLabel .')' .': ...' . str_replace($searchString,'<span style="background: yellow;">' . $searchString .'</span>', $snippet) . '... <br />';
				$content = substr_replace($content,'', strpos($content, $searchString), strlen($searchString));
			}

			return $result;
		}
	};
?>