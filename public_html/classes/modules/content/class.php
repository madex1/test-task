<?php

	use \UmiCms\Service;

	class content extends def_module {
		/** @var int $perPage количество элементов на странице */
		public $perPage;

		public function __construct() {
			parent::__construct();

			$this->loadCommonExtension();

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				cmsController::getInstance()->getModule('users');

				$configTabs = $this->getConfigTabs();
				if ($configTabs) {
					$configTabs->add("config");
					$configTabs->add("content_control");
				}

				$commonTabs = $this->getCommonTabs();

				if($commonTabs instanceof iAdminModuleTabs) {
					$commonTabs->add('sitetree', array('sitetree'));
					$commonTabs->add("tree", array('tree'));
				}

				$this->__loadLib("__admin.php");
				$this->__implement("__content");

				$this->loadAdminExtension();

				// custom admin methods
				$this->__loadLib("__custom_adm.php");
				$this->__implement("__content_custom_admin");
			} else {
				$this->perPage = intval(regedit::getInstance()->getVal("//settings/elements_count_per_page"));
			}

			$this->__loadLib("__json.php");
			$this->__implement("__json_content");

			$this->__loadLib("__lib.php");
			$this->__implement("__lib_content");

			$this->__loadLib("__events.php");
			$this->__implement("__content_events");

			$this->__loadLib("__editor.php");
			$this->__implement("__editor_content");

			$this->loadSiteExtension();

			$this->__loadLib("__custom.php");
			$this->__implement("__custom_content");

		}


		public function isMethodExists($method) {
			/** @TODO: temporary fix for some methods */
			if (in_array($method, array(
					'pages_mklist_by_tags',
					'pagesByAccountTags',
					'pagesByDomainTags',
					'tags_mk_cloud',
					'tags_mk_eff_cloud',
					'tagsAccountCloud',
					'tagsAccountEfficiencyCloud',
					'tagsAccountUsageCloud',
					'tagsDomainCloud',
					'tagsDomainEfficiencyCloud',
					'tagsDomainUsageCloud'
				))) {
				return true;
			}
			return parent::isMethodExists($method);
		}

		function __call($method, $args) {
			static $cache = array();

			if (!isset($cache[$method]) && !method_exists($this, $method)) { // если еще не имлементировали
				$cache[$method] = true;
				$method_prefix = '';
				$matches = array();
				$bSucc = preg_match('/[A-Z_]/', $method, $matches);

				if ($bSucc) {
					$match = $matches[0];
					$match_pos = strpos($method, $match);
					$method_prefix = substr($method, 0, $match_pos) . '/';
				}

				$currdir = __DIR__ . '/';
				$class_enter = '__' . $method;
				$entermethod_lib = 'methods/' . $method_prefix . '__' . $method . '.lib.php';

				if (file_exists($currdir . $entermethod_lib) && !class_exists($class_enter)) { // метод, общий для всех режимов
					$this->__loadLib($entermethod_lib);
					$this->__implement($class_enter);
				}

				$class_mode = '__' . $method . '_'; // метод, выбираемый в зависимости от режима
				$modemethod_lib = 'methods/' . $method_prefix . '__' . $method . '_' . cmsController::getInstance()->getCurrentMode() . '.lib.php';

				if (file_exists($currdir . $modemethod_lib) && !class_exists($class_mode)) {
					$this->__loadLib($modemethod_lib);
					$this->__implement($class_mode);
				}
			}

			return parent::__call($method, $args);
		}


		public function content($elementId = false) {
			$cmsController = cmsController::getInstance();
			if(!$elementId) $elementId = $cmsController->getCurrentElementId();

			$hierarchy = umiHierarchy::getInstance();
			$element = $hierarchy->getElement($elementId);

			if($element instanceof iUmiHierarchyElement) {
				$this->pushEditable("content", "", $elementId);
				return $element->content;
			} else {
				return $this->gen404();
			}
		}


		public function gen404($template = 'default') {
			if(!$template) $template = 'default';

			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->status('404 Not Found');

			$this->setHeader('%content_error_404_header%');
			list($tpl_block) = def_module::loadTemplates("content/not_found/".$template, 'block');
			$template = $tpl_block ? $tpl_block : '%content_usesitemap%';
			return def_module::parseTemplate($template, array());
		}

		/**
		 * Выводит список элементов типа "Страница контента"
		 * @param string $template имя шаблона (для TPL-шаблонизатора)
		 * @param int|string $path ID элемента или его адрес
		 * @param int $maxDepth максимальная глубина вложенности иерархии поиска элементов (во вложенных подразделах)
		 * @param int $perPage количество элементов на странице (при постраничной навигации)
		 * @param bool $ignorePaging игнорировать постраничную навигацию
		 * @param string $sortField имя поля, по которому нужно произвести сортировку элементов
		 * @param string $sortDirection направление сортировки ('asc' или 'desc')
		 * @return array
		 * @throws publicException
		 * @throws selectorException
		 */
		public function getList($template = 'default', $path = 0, $maxDepth = 1, $perPage = 0,
								$ignorePaging = false, $sortField = '', $sortDirection = 'asc') {

			$elements = new selector('pages');
			$elements->types('hierarchy-type')->name('content', 'page');

			$parentId = $this->analyzeRequiredPath($path);

			if (!$parentId && $parentId !== 0 && $path !== KEYWORD_GRAB_ALL) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $path));
			}

			if ($path !== KEYWORD_GRAB_ALL) {
				$maxDepthNum = intval($maxDepth) > 0 ? intval($maxDepth) : 1;
				$elements->where('hierarchy')->page($parentId)->childs($maxDepthNum);
			}

			$perPageNumber = intval($perPage);
			$limit = $perPageNumber > 0 ? $perPageNumber : $this->perPage;

			if (!$ignorePaging) {
				$currentPage = intval(getRequest('p'));
				$offset = $currentPage * $limit;
				$elements->limit($offset, $limit);
			}

			if ($sortField) {
				$direction = 'asc';
				if (in_array($sortDirection, array('asc', 'desc', 'rand'))) {
					$direction = $sortDirection;
				}

				try {
					$elements->order($sortField)->$direction();
				} catch (selectorException $e) {
					throw new publicException(getLabel('error-prop-not-found', null, $sortField));
				}
			}

			selectorHelper::detectFilters($elements);

			$elements->option('load-all-props')->value(true);
			$elements->option('exclude-nested', false);

			$result = $elements->result();

			list($templateBlock, $templateBlockEmpty, $templateItem) =
				def_module::loadTemplates('content/' . $template, 'get_list_block', 'get_list_block_empty', 'get_list_item');

			$total = $elements->length();

			$data = array(
				'items' => array(
					'nodes:item' => null
				),
				'total' => $total,
				'per_page' => $limit,
				'parent_id' => $parentId
			);

			if ($total === 0) {
				return def_module::parseTemplate($templateBlockEmpty, $data, $parentId);
			}

			$linksHelper = $this->umiLinksHelper;
			$umiHierarchy = umiHierarchy::getInstance();

			$items = array();
			/** @var iUmiHierarchyElement $page */
			foreach ($result as $page) {
				if (!$page instanceof iUmiHierarchyElement) {
					continue;
				}

				$itemData = array();

				$itemData['@id'] = $page->getId();
				$itemData['name'] = $page->getName();
				$itemData['@link'] = $linksHelper->getLinkByParts($page);
				$itemData['@xlink:href'] = 'upage://' . $page->getId();
				$itemData['@visible_in_menu'] = $page->getIsVisible();

				$items[] = def_module::parseTemplate($templateItem, $itemData, $page->getId());
				$umiHierarchy->unloadElement($page->getId());
			}

			$data['items']['nodes:item'] = def_module::parseTemplate($templateBlock, $items);

			return def_module::parseTemplate($templateBlock, $data, $parentId);
		}

		/**
		 * Генерирует и возвращает иерархическое меню
		 * @param string $template имя шаблона (для tpl шаблонизатора)
		 * @param int $maxDepth уровень вложенности меню, относительно корневого элемента
		 * @param bool|int $rootPageId идентификатор корневой страницы меню (если false - возьмет текущую)
		 * @param bool $showHasChildren помечать ли пункты меню, если у них есть дочерние пункты
		 * @param bool|int $currentPageId id страницы, которую нужно считать текущей
		 * @return array
		 */
		public function menu($template = 'default', $maxDepth = 1, $rootPageId = false, $showHasChildren = false, $currentPageId = false) {
			$cmsController = cmsController::getInstance();
			$umiHierarchy = umiHierarchy::getInstance();
			$umiConfig = mainConfiguration::getInstance();

			if ($rootPageId) {
				if (!is_numeric($rootPageId)) {
					$rootPageId = $umiHierarchy->getIdByPath($rootPageId);
				}
				$rootPageURI = $umiHierarchy->getPathById($rootPageId, false, true, false, true);
			} else {
				$rootPageId = 0;
				$rootPageURI = $cmsController->getPreLang() . '/' . $cmsController->getUrlPrefix();
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$umiPermissions = permissionsCollection::getInstance();
			$languageId = $this->getLanguageId($rootPageId, $umiHierarchy);
			$domainId = $this->getDomainId($rootPageId, $umiHierarchy);

			$menuItems = $this->generateMenuData(
				$rootPageId,
				$maxDepth,
				$languageId,
				$domainId,
				$connection,
				$umiPermissions,
				$umiHierarchy,
				$showHasChildren
			);

			$templates = def_module::loadTemplates('content/menu/' . $template);
			$rootPageURI = rtrim($rootPageURI, '/');
			$URISuffix = ($umiConfig->get('seo', 'url-suffix.add')) ? $umiConfig->get('seo', 'url-suffix') : '';
			$activeElementId = (is_numeric($currentPageId)) ? $currentPageId : $cmsController->getCurrentElementId();
			$activeElementsIds = $umiHierarchy->getAllParents($activeElementId);
			$activeElementsIds = array_flip($activeElementsIds);
			$activeElementsIds['current'] = $activeElementId;
			$isXSLT = $this->isXSLTResultMode();
			$currentDepth = 0;

			return $this->generateMenuResult(
				$menuItems,
				$templates,
				$rootPageId,
				$rootPageURI,
				$URISuffix,
				$umiHierarchy,
				$activeElementsIds,
				$currentDepth,
				$maxDepth,
				$isXSLT,
				$showHasChildren
			);
		}

		public function sitemap($template = "default", $max_depth = false, $root_id = false) {
			$hierarchy = umiHierarchy::getInstance();
			$cmsController = cmsController::getInstance();

			if(!$max_depth) $max_depth = getRequest('param0');
			if(!$max_depth) $max_depth = 4;

			if(!$root_id) $root_id = (int) getRequest('param1');
			if(!$root_id) $root_id = 0;

			if($cmsController->getCurrentMethod() == "sitemap") {
				$this->setHeader("%content_sitemap%");
			}

			$site_tree = $hierarchy->getChildrenTree($root_id, false, false, $max_depth - 1);
			return $this->gen_sitemap($template, $site_tree, $max_depth - 1);
		}


		public function get_page_url($element_id, $ignore_lang = false) {
			$ignore_lang = (bool) $ignore_lang;
			return umiHierarchy::getInstance()->getPathById($element_id, $ignore_lang);
		}


		public function get_page_id($url) {
			$hierarchy = umiHierarchy::getInstance();
			$elementId = $hierarchy->getIdByPath($url);
			if($elementId) return $elementId; else {
				throw new publicException(getLabel('error-page-does-not-exist', null, $url));
			}
		}


		public function redirect($url = "") {
			if(is_numeric($url)) {
				$url = $this->get_page_url($url);
			}
			parent::redirect($url);
		}


		public function insert($elementId) {
			$hierarchy = umiHierarchy::getInstance();
			$cmsController = cmsController::getInstance();
			$currentElementId = $cmsController->getCurrentElementId();
			$elementId = trim($elementId);

			if(!$elementId) return "%content_error_insert_null%";

			$elementId = (int) is_numeric($elementId) ? $elementId : $hierarchy->getIdByPath($elementId);
			if($elementId == $currentElementId) return "%content_error_insert_recursy%";
			if(!$elementId) return;

			if($element = $hierarchy->getElement($elementId)) {
				$this->pushEditable("content", "", $elementId);
				return $element->content;
			}
		}

		public function get_parents($elementId) {
			return umiHierarchy::getInstance()->getAllParents($elementId, true);
		}

		public function getEditLink($elementId, $element_type) {
			return array(
				$this->pre_lang . "/admin/content/add/{$elementId}/page/",
				$this->pre_lang . "/admin/content/edit/{$elementId}/"
			);
		}

		private function gen_sitemap($template = "default", $site_tree, $max_depth) {
			$hierarchy = umiHierarchy::getInstance();

			list($template_block, $template_item) = def_module::loadTemplates("content/sitemap/" . $template, "block", "item");

			$block_arr = array(); $items = array();
			if(is_array($site_tree)) {
				foreach($site_tree as $elementId => $childs) {
					if($element = $hierarchy->getElement($elementId)) {
						$item_arr = array(
							'attribute:id'		=> $elementId,
							'attribute:link'	=> $element->link,
							'attribute:name'	=> $element->name,
							'xlink:href'		=> ("upage://" . $elementId)
						);

						if(($max_depth > 0) && $element->show_submenu) {
							$item_arr['nodes:items'] = $item_arr['void:sub_items'] = (count($childs) && is_array($childs)) ? $this->gen_sitemap($template, $childs, ($max_depth - 1)) : "";
						} else {
							$item_arr['sub_items'] = "";
						}
						$items[] = self::parseTemplate($template_item, $item_arr, $elementId);
						$hierarchy->unloadElement($elementId);
					} else {
						continue;
					}
				}
			}

			$block_arr['subnodes:items'] = $items;
			return self::parseTemplate($template_block, $block_arr, 0);
		}

		/**
		 * Возвращает tpl блоки шаблона для иерархического меню для текущего уровня вложенности меню
		 * @param array $templates tpl блоки шаблона дли иерархического меню
		 * @param int $curr_depth уровень вложенности меню
		 * @return array
		 */
		private function getMenuTemplates($templates, $curr_depth) {
			$suffix = "_level" . $curr_depth;

			$block = getArrayKey($templates, "menu_block" . $suffix);
			$line = getArrayKey($templates, "menu_line" . $suffix);
			$line_a = (array_key_exists("menu_line" . $suffix . "_a", $templates)) ? $templates["menu_line" . $suffix . "_a"] : $line;
			$line_in = (array_key_exists("menu_line" . $suffix . "_in", $templates)) ? $templates["menu_line" . $suffix . "_in"] : $line;

			$class = getArrayKey($templates, "menu_class" . $suffix . "");
			$class_last = getArrayKey($templates, "menu_class" . $suffix . "_last");


			if(!$block) {
				switch($curr_depth) {
					case 1: $suffix = "_fl"; break;
					case 2: $suffix = "_sl"; break;
				}
				$block = getArrayKey($templates, 'menu_block' . $suffix);
				$line = getArrayKey($templates, 'menu_line' . $suffix);
				$line_a = (array_key_exists("menu_line" . $suffix . "_a", $templates)) ? $templates["menu_line" . $suffix . "_a"] : $line;
				$line_in = (array_key_exists("menu_line" . $suffix . "_in", $templates)) ? $templates["menu_line" . $suffix . "_in"] : $line;
			}

			if(!($separator = getArrayKey($templates, 'separator' . $suffix))) {
				$separator = getArrayKey($templates, 'separator');
			}

			if(!($separator_last = getArrayKey($templates, 'separator_last' . $suffix))) {
				$separator_last = getArrayKey($templates, 'separator_last');
			}

			return array($block, $line, $line_a, $line_in, $separator, $separator_last, $class, $class_last);
		}

		/**
		 * Помечает пункт иерархического меню,  у которых есть дочерние пункты
		 * @param array $menuItems пунты иерархического меню
		 * @param IConnection $connection подключение к бд
		 * @param iPermissionsCollection $permissions коллекция прав доступа
		 * @return array
		 */
		private function markMenuItemsWithChildren(array $menuItems, IConnection $connection, iPermissionsCollection $permissions) {
			$elementsWithChildren = array();

			if (count($menuItems) == 0) {
				return $elementsWithChildren;
			}

			$isSV = $permissions->isSv();
			$permissionsJoin = (!$isSV) ? 'LEFT JOIN cms3_permissions as cp ON cp.rel_id = hierarchy.id' : '';
			$permissionsCondition = (!$isSV) ? 'AND ' . $permissions->makeSqlWhere($permissions->getUserId()) . ' AND cp.level&1 = 1' : '';

			$elementIds = array_keys($menuItems);
			$elementIds = array_map('intval', $elementIds);
			$elementIds = implode(', ', $elementIds);
			$sql = <<<SQL
SELECT DISTINCT `rel` as `rel_id`
FROM `cms3_hierarchy` as hierarchy
$permissionsJoin
WHERE `rel` IN ($elementIds)
AND hierarchy.`is_active` = 1
AND hierarchy.`is_deleted` = 0
AND hierarchy.`is_visible` = 1
$permissionsCondition
SQL;
			$queryResult = $connection->queryResult($sql);
			$queryResult->setFetchType(IQueryResult::FETCH_ASSOC);
			foreach ($queryResult as $row) {
				$elementsWithChildren[$row['rel_id']] = true;
			}

			foreach ($menuItems as &$menuItem) {
				if (isset($elementsWithChildren[$menuItem['child_id']])) {
					$menuItem['has-children'] = true;
				}
			}

			return $menuItems;
		}

		/**
		 * Гененирует результатирующий массив с данными иерархического меню для последующей шаблонизации
		 * @param array $menuItems полученные пункты меню
		 * @param mixed $templates блоки шаблона для tpl
		 * @param int $rootPageId идентификатор текущего корневого элемента меню
		 * @param string $rootPageURI адрес страницы текущего корневого элемента меню
		 * @param string $URISuffix суффикс для адреса страницы элемента меню
		 * @param iUmiHierarchy $umiHierarchy коллекция иерархических объектов
		 * @param array $activeElementsIds массив идентификаторов активных страниц
		 * @param int $currentDepth текущий уровень вложенности
		 * @param int $maxDepth максимальный уровень вложенности
		 * @param bool $isXslt используется ли режим xslt шаблонизатора
		 * @param bool $showHasChildren помечать ли пункты меню, если у них есть дочерние пункты
		 * @return array|string
		 */
		private function generateMenuResult($menuItems, $templates, $rootPageId, $rootPageURI, $URISuffix, iUmiHierarchy $umiHierarchy, $activeElementsIds, $currentDepth = 0, $maxDepth = 1, $isXslt, $showHasChildren) {
			if (!isset($menuItems[$rootPageId])) {
				return '';
			}

			$menuItemsCount = count($menuItems[$rootPageId]);

			if ($menuItemsCount == 0) {
				return '';
			}

			$rootMenuItems = $menuItems[$rootPageId];
			$itemsBlock = array();
			$counter = 0;

			list(
				$templateBlock,
				$templateLine,
				$templateLineA,
				$templateLineIn,
				$separator,
				$separatorLast,
				$class,
				$classLast
			) = $this->getMenuTemplates($templates, ($currentDepth + 1));

			foreach ($rootMenuItems as $rootMenuItem) {
				$elementId = $rootMenuItem['child_id'];
				$element = $umiHierarchy->getElement($elementId, true);

				if (!$element instanceof iUmiHierarchyElement) {
					continue;
				}

				$elementName = $element->getName();
				$elementURIPart = $element->getAltName();
				$URI = $rawURI = $rootPageURI . '/' . $elementURIPart;

				if ($element->getIsDefault()) {
					$URI = $umiHierarchy->getPathById($elementId);
				} else {
					$URI = $URI . $URISuffix;
				}

				switch (true) {
					case ($isXslt) : {
						$isActive = (isset($activeElementsIds[$elementId]) || $activeElementsIds['current'] == $elementId);
						$currentTemplateLine = array();
						break;
					}
					case ($templateLineIn && $templateLineIn != $templateLine && $maxDepth > 1 && isset($activeElementsIds[$elementId])) : {
						$isActive = true;
						$currentTemplateLine = $templateLineIn;
						break;
					}
					default : {
						$isActive = (isset($activeElementsIds[$elementId]) || $activeElementsIds['current'] == $elementId);
						$currentTemplateLine = ($isActive) ? $templateLineA : $templateLine;
					}
				}

				$subMenu = '';
				$noSubMenu = true;

				$needToShowSubMenu = (
					is_numeric($rootMenuItem['show_sub_menu']) &&
					($isActive || is_numeric($rootMenuItem['is_expanded'])) &&
					(
						($isXslt && XSLT_NESTED_MENU) || (!$isXslt && strstr($currentTemplateLine, '%sub_menu%'))
					)
				);

				if ($maxDepth > 1 && isset($menuItems[$elementId]) && $needToShowSubMenu) {
					$subMenu = $this->generateMenuResult(
						$menuItems,
						$templates,
						$elementId,
						$rawURI,
						$URISuffix,
						$umiHierarchy,
						$activeElementsIds,
						($currentDepth + 1),
						($maxDepth - 1),
						$isXslt,
						$showHasChildren
					);
					$noSubMenu = false;
				}

				$itemBlock = array();
				$itemBlock['@id'] = $elementId;
				$itemBlock['@link'] = $URI;
				$itemBlock['@name'] = $elementName;
				$itemBlock['@alt-name'] = $elementURIPart;
				$itemBlock['xlink:href'] = 'upage://' . $elementId;

				if ($showHasChildren && isset($rootMenuItem['has-children']) && $needToShowSubMenu) {
					$itemBlock['@has-children'] = true;
				}

				if ($isActive) {
					$itemBlock['attribute:status'] = "active";
				}

				$itemBlock['node:text'] = (XSLT_NESTED_MENU != 2) ? $elementName : null;

				if ($isXslt) {
					if (is_array($subMenu) && isset($subMenu['items'], $subMenu['items']['nodes:item']) && count($subMenu['items']['nodes:item'])) {
						$itemBlock['items']['nodes:item'] = $subMenu['items']['nodes:item'];
					}
				} else {
					$itemBlock['void:num'] = ($counter + 1);
					$itemBlock['void:sub_menu'] = $subMenu;
					$itemBlock['void:separator'] = (($menuItemsCount == ($counter + 1)) && $separatorLast) ? $separatorLast : $separator;
					$itemBlock['class'] = ($menuItemsCount > ($counter + 1)) ? $class : $classLast;
				}

				$itemsBlock[] = self::parseTemplate($currentTemplateLine, $itemBlock, $elementId);

				if ($noSubMenu) {
					$umiHierarchy->unloadElement($elementId);
				}

				$counter++;
			}

			$menuBlock = array(
				'subnodes:items' => $itemsBlock,
				'void:lines' => $itemsBlock,
				'id' => $rootPageId
			);

			return self::parseTemplate($templateBlock, $menuBlock, $rootPageId);
		}

		/**
		 * Возвращает подготовленный массив пунктов иерархического меню
		 * @param int $rootPageId идентификатор корневого элемента меню
		 * @param int $maxDepth максимальный уровень вложенности меню
		 * @param int $languageId языковая версия элементов меню
		 * @param int $domainId домен элементов меню
		 * @param IConnection $connection подключение к бд
		 * @param iPermissionsCollection $permissions коллекция прав доступа
		 * @param iUmiHierarchy $hierarchy коллекция иерархических объектов
		 * @param bool $showHasChildren помечать ли пункты меню, если у них есть дочерние пункты
		 * @return array
		 */
		private function generateMenuData($rootPageId, $maxDepth, $languageId, $domainId, IConnection $connection, iPermissionsCollection $permissions, iUmiHierarchy $hierarchy, $showHasChildren) {
			$maxDepth = $this->getMaxDepth($rootPageId, $maxDepth, $hierarchy);
			$menuItems = $this->getMenuItems(
				$rootPageId,
				$maxDepth,
				$languageId,
				$domainId,
				$connection,
				$permissions
			);
			$this->loadMenuElements($menuItems, $hierarchy);
			if ($showHasChildren) {
				$menuItems = $this->markMenuItemsWithChildren($menuItems, $connection, $permissions);
			}
			$menuItems = $this->sortMenuItems($menuItems, $hierarchy);
			return $this->generateMenuRelation($menuItems);
		}

		/**
		 * Возвращает плоский массив элементов иерархического меню
		 * @param int $rootPageId идентификатор корневого элемента меню
		 * @param int $maxDepth максимальный уровень вложенности меню
		 * @param int $languageId языковая версия элементов меню
		 * @param int $domainId домен элементов меню
		 * @param IConnection $connection подключение к бд
		 * @param iPermissionsCollection $permissions коллекция прав доступа
		 * @return array
		 */
		private function getMenuItems($rootPageId, $maxDepth, $languageId, $domainId, IConnection $connection, iPermissionsCollection $permissions) {
			$rootPageId = (int) $rootPageId;
			$maxDepth = (int) $maxDepth;
			$subMenuFieldId = (int) $this->getShowSubMenuFieldId();
			$isExpandedFieldId = (int) $this->getIsExpandedFieldId();
			$languageId = (int) $languageId;
			$domainId = (int) $domainId;

			$isSV = $permissions->isSv();
			$permissionsJoin = (!$isSV) ? 'LEFT JOIN cms3_permissions as cp ON cp.rel_id = hierarchy.id' : '';
			$permissionsCondition = (!$isSV) ? 'AND ' . $permissions->makeSqlWhere($permissions->getUserId()) . ' AND cp.level&1 = 1' : '';
			$relationCondition = ($rootPageId === 0) ? 'relations.rel_id IS NULL' : 'relations.rel_id = ' . $rootPageId;

			$sql = <<<SQL
SELECT
	relations.child_id,
	relations.level,
	hierarchy.ord,
	hierarchy.rel,
	hierarchy.alt_name,
	showSubmenu.int_val as show_sub_menu,
	isExpanded.int_val as is_expanded
FROM cms3_hierarchy_relations as relations
LEFT JOIN cms3_hierarchy as hierarchy ON hierarchy.id = relations.child_id
$permissionsJoin
LEFT JOIN cms3_object_content as showSubmenu ON showSubmenu.obj_id = hierarchy.obj_id AND showSubmenu.field_id = $subMenuFieldId
LEFT JOIN cms3_object_content as isExpanded ON isExpanded.obj_id = hierarchy.obj_id AND isExpanded.field_id = $isExpandedFieldId
WHERE
	$relationCondition
AND
	hierarchy.domain_id = $domainId
AND
	hierarchy.lang_id = $languageId
AND
	hierarchy.is_deleted = 0
AND
	hierarchy.is_active = 1
AND
	hierarchy.is_visible = 1
	$permissionsCondition
AND
	relations.level <= $maxDepth
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return array();
			}

			$rows = array();

			foreach($result as $row) {
				$rows[$row['child_id']] = $row;
			}

			return $rows;
		}

		/**
		 * Переделывает плоский массив элементов меню в массив со связями
		 * @param array $menuItems плоский массив элементов меню
		 * @return array
		 */
		private function generateMenuRelation(array $menuItems) {
			$relations = array();

			foreach ($menuItems as $item) {
				$relations[$item['rel']][$item['child_id']] = $item;
			}

			return $relations;
		}

		/**
		 * Загружает страницы элементов иерархического меню в память
		 * @param array $menuItems плоский массив пунктов меню
		 * @param iUmiHierarchy $hierarchy коллекция иерархических объектов
		 */
		private function loadMenuElements(array $menuItems, iUmiHierarchy $hierarchy) {
			$menuItemsIds = array_keys($menuItems);
			$hierarchy->loadElements($menuItemsIds);
		}

		/**
		 * Сортирует плоский массив пунктов меню по иерархии и возвращает отсортированный массив
		 * @param array $menuItems плоский массив пунктов меню
		 * @return array
		 */
		private function sortMenuItems(array $menuItems, iUmiHierarchy $umiHierarchy) {
			return $umiHierarchy->sortByHierarchy($menuItems);
		}

		/**
		 * Возвращает идентификатор поля "show_submenu"
		 * @return int
		 * @throws publicAdminException если не удалось определить id поля
		 */
		private function getShowSubMenuFieldId() {
			$pages = new selector('pages');
			$subMenuFieldId = $pages->searchField('show_submenu');

			if (!is_numeric($subMenuFieldId)) {
				throw new publicAdminException('Cannot get field "show_submenu"');
			}

			return (int) $subMenuFieldId;
		}

		/**
		 * Возвращает идентификатор поля "is_expanded"
		 * @return int
		 * @throws publicAdminException если не удалось определить id поля
		 */
		private function getIsExpandedFieldId() {
			$pages = new selector('pages');
			$isExpandedFieldId = $pages->searchField('is_expanded');

			if (!is_numeric($isExpandedFieldId)) {
				throw new publicAdminException('Cannot get field "is_expanded"');
			}

			return (int) $isExpandedFieldId;
		}

		/**
		 * Возвращает абсолютный максимальный уровень вложенности для построения меню
		 * @param int $rootPageId идентификатор корневого элемента меню
		 * @param int $maxDepth относительный максимальный уровень вложенности
		 * @param iUmiHierarchy $hierarchy коллекция иерархических объектов
		 * @return int
		 * @throws publicAdminException если не удалось получить результат
		 */
		private function getMaxDepth($rootPageId, $maxDepth, iUmiHierarchy $hierarchy) {
			return $hierarchy->getMaxDepth($rootPageId, $maxDepth);
		}

		/**
		 * Возвращает идентификатор языка для построения меню
		 * @param int $rootPageId идентификатор корневого элемента меню
		 * @param iUmiHierarchy $umiHierarchy коллекция иерархических объектов
		 * @return int
		 * @throws publicAdminException если не удалось получить объект корневого элемента
		 */
		private function getLanguageId($rootPageId, iUmiHierarchy $umiHierarchy) {
			if ($rootPageId == 0) {
				return Service::LanguageDetector()->detectId();
			}

			$rootPage = $umiHierarchy->getElement($rootPageId);

			if (!$rootPage instanceof iUmiHierarchyElement) {
				throw new publicAdminException('Cannot get root page element');
			}

			return $rootPage->getLangId();
		}

		/**
		 * Возвращает идентификатор домена для построения меню
		 * @param int $rootPageId идентификатор корневого элемента меню
		 * @param iUmiHierarchy $umiHierarchy коллекция иерархических объектов
		 * @return int
		 * @throws publicAdminException если не удалось получить объект корневого элемента
		 */
		private function getDomainId($rootPageId, iUmiHierarchy $umiHierarchy) {
			if ($rootPageId == 0) {
				return Service::DomainDetector()->detectId();
			}

			$rootPage = $umiHierarchy->getElement($rootPageId);

			if (!$rootPage instanceof iUmiHierarchyElement) {
				throw new publicAdminException('Cannot get root page element');
			}

			return $rootPage->getDomainId();
		}

		/**
		 * Добавляет страницу к списку последних просмотреных страниц
		 *
		 * @param int $elementId Текущая страница
		 * @param string $scope Тэг(группировка страниц)
		 *
		 * @return null
		 */
		public function addRecentPage($elementId, $scope = "default") {
			if (!$scope) {
				$scope = "default";
			}

			if ($elementId != cmsController::getInstance()->getCurrentElementId()) {
				return null;
			}

			$limit = mainConfiguration::getInstance()->get("modules", "content.recent-pages.max-items");
			$limit = $limit ? $limit : 100;

			$session = \UmiCms\Service::Session();
			$recentPages = $session->get('content:recent_pages');
			$recentPages = (is_array($recentPages)) ? $recentPages : [];

			if (!isset($recentPages[$scope])) {
				$recentPages[$scope] = [];
			}

			$recentPages[$scope][$elementId] = time();
			asort($recentPages[$scope]);
			$recentPages[$scope] = array_reverse($recentPages[$scope], true);
			$recentPages[$scope] = array_slice($recentPages[$scope], 0, $limit, true);

			$session->set('content:recent_pages', $recentPages);

			return null;
		}

		/**
		 * Получение списка последних просмотренных страниц
		 *
		 * @param string $template Шаблон для вывода
		 *
		 * @param string $scope Тэг(группировка страниц), без пробелов и запятых
		 * @param bool $showCurrentElement Если false - текущая страница не будет включена в результат
		 * @param int|null $limit Количество выводимых элементов
		 *
		 * @return mixed
		 */
		public function getRecentPages($template = "default", $scope = "default", $showCurrentElement = false, $limit = null) {
			if (!$scope) {
				$scope = "default";
			}

			$hierarchy = umiHierarchy::getInstance();
			$currentElementId = cmsController::getInstance()->getCurrentElementId();
			list($itemsTemplate, $itemTemplate) = content::loadTemplates("content/" . $template, "items", "item");
			$session = \UmiCms\Service::Session();
			$recentPages = $session->get('content:recent_pages');
			$recentPages = (is_array($recentPages)) ? $recentPages : [];
			$items = [];

			if (isset($recentPages[$scope])) {
				$pagesIds = [];
				foreach ($recentPages[$scope] as $recentPage) {
					$pagesIds[] = $recentPage;
				}
				$hierarchy->loadElements($pagesIds);
				foreach ($recentPages[$scope] as $recentPage => $time) {
					$element = $hierarchy->getElement($recentPage, true);

					if (!($element instanceOf umiHierarchyElement)) {
						continue;
					}

					if (!$showCurrentElement && $element->getId() == $currentElementId) {
						continue;
					} elseif (!is_null($limit) && $limit <= 0) {
						break;
					} elseif (!is_null($limit)) {
						$limit--;
					}

					$items[] = content::parseTemplate($itemTemplate, [
						'@id' => $element->getId(),
						'@link' => $element->link,
						'@name' => $element->getName(),
						'@alt-name' => $element->getAltName(),
						'@xlink:href' => "upage://" . $element->getId(),
						'@last-view-time' => $time,
						'node:text' => $element->getName()
					], $element->getId());
				}
			}
			return content::parseTemplate($itemsTemplate, ["subnodes:items" => $items]);
		}

		/**
		 * Удаляет страницу из списка последних использований
		 *
		 * @param int $elementId Id страницы
		 * @param string $scope Тэг
		 *
		 * @return bool
		 */
		public function delRecentPage($elementId = false, $scope = "default") {
			if ($elementId === false) {
				$elementId = getRequest('param0');
			}

			if (!$scope) {
				$scope = "default";
			}

			$session = \UmiCms\Service::Session();
			$recentPages = $session->get('content:recent_pages');
			$recentPages = (is_array($recentPages)) ? $recentPages : [];

			if (isset($recentPages[$scope][$elementId])) {
				unset($recentPages[$scope][$elementId]);
				$session->set('content:recent_pages', $recentPages);

			}

			$this->redirect(getServer('HTTP_REFERER'));
		}

		/**
		 * Получает список режимов отображения
		 * Текущий помечается как current
		 *
		 * @param string $template TPL шаблон
		 *
		 * @return mixed
		 */
		public function getMobileModesList($template = "default") {
			$isMobile = \UmiCms\Service::Request()->isMobile();
			$modes = array(
				"is_mobile" => 1,
				"is_desktop" => 0
			);

			$items = array();
			foreach ($modes as $mode => $value) {
				$itemArray = array (
					"@name" => $mode,
					"@link" => '/content/setMobileMode/' . ($value ? 0 : 1),
				);

				if ($value == $isMobile) {
					$itemArray["@status"] = "active";
					$items[] = def_module::renderTemplate("content/mobile/" . $template, $mode, $itemArray);
				} else {
					$items[] = def_module::parseTemplate("", $itemArray);;
				}
			}

			return def_module::renderTemplate("content/mobile/" . $template, "modes", array(
				"subnodes:items" => $items
			));
		}

		/**
		 * Устанавливает режим отображения сайта
		 * @internal
		 *
		 * @param bool $isMobile Режим
		 */
		public function setMobileMode($isMobile = null) {
			if(is_null($isMobile)) {
				$isMobile = getRequest('param0');
			}

			$cookieJar = \UmiCms\Service::CookieJar();

			if ($isMobile == 1) {
				$cookieJar->set('is_mobile', 1);
			} elseif ($isMobile == 0) {
				$cookieJar->set('is_mobile', 0);
			}
			parent::redirect(getServer('HTTP_REFERER'));
		}

		/**
		 * Возвращает протокол работы сервера
		 * Враппер к getSelectedServerProtocol()
		 * @return String
		 */
		public function getServerProtocol() {
			return getSelectedServerProtocol();
		}

		/**
		 * Возвращает имя сайта
		 * @return string
		 */
		public function getSiteName() {
			$domainId = Service::DomainDetector()->detectId();
			$langId = Service::LanguageDetector()->detectId();
			$registry = Service::Registry();
			$siteName = $registry->get("//settings/site_name/{$domainId}/{$langId}") ?: $registry->get('//settings/site_name');
			return $siteName;
		}
	};
?>
