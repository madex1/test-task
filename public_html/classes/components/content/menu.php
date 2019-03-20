<?php

	/** Класс автогенерируемого меню */
	class ContentMenu {

		/** @var content $module */
		public $module;

		/**
		 * Генерирует и возвращает иерархическое меню
		 * @param string $template имя шаблона (для tpl шаблонизатора)
		 * @param int $maxDepth уровень вложенности меню, относительно корневого элемента
		 * @param bool|int $rootPageId идентификатор корневой страницы меню (если false - возьмет текущую)
		 * @param bool $showHasChildren помечать ли пункты меню, если у них есть дочерние пункты
		 * @param bool|int $currentPageId id страницы, которую нужно считать текущей
		 * @return array
		 */
		public function menu(
			$template = 'default',
			$maxDepth = 1,
			$rootPageId = false,
			$showHasChildren = false,
			$currentPageId = false
		) {
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

			/** @var content|ContentMenu|$this $module */
			$module = $this->module;
			$connection = ConnectionPool::getInstance()->getConnection();
			$umiPermissions = permissionsCollection::getInstance();
			$languageId = $module->getLanguageId($rootPageId, $umiHierarchy);
			$domainId = $module->getDomainId($rootPageId, $umiHierarchy);

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

			$templates = content::loadTemplates('content/menu/' . $template);
			$rootPageURI = rtrim($rootPageURI, '/');
			$URISuffix = $umiConfig->get('seo', 'url-suffix.add') ? $umiConfig->get('seo', 'url-suffix') : '';
			$activeElementId = is_numeric($currentPageId) ? $currentPageId : $cmsController->getCurrentElementId();
			$activeElementsIds = $umiHierarchy->getAllParents($activeElementId);
			$activeElementsIds = array_flip($activeElementsIds);
			$activeElementsIds['current'] = $activeElementId;
			$isXSLT = $module->isXSLTResultMode();
			$currentDepth = 0;

			return $module->generateMenuResult(
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

		/**
		 * Возвращает tpl блоки шаблона для иерархического меню для текущего уровня вложенности меню.
		 * @param array $templates tpl блоки шаблона дли иерархического меню
		 * @param int $curr_depth уровень вложенности меню
		 * @return array
		 */
		public function getMenuTemplates($templates, $curr_depth) {
			$suffix = '_level' . $curr_depth;
			$block = getArrayKey($templates, 'menu_block' . $suffix);
			$line = getArrayKey($templates, 'menu_line' . $suffix);
			$line_a = array_key_exists('menu_line' . $suffix . '_a', $templates)
				? $templates['menu_line' . $suffix . '_a']
				: $line;
			$line_in = array_key_exists('menu_line' . $suffix . '_in', $templates)
				? $templates['menu_line' . $suffix . '_in']
				: $line;
			$class = getArrayKey($templates, 'menu_class' . $suffix . '');
			$class_last = getArrayKey($templates, 'menu_class' . $suffix . '_last');

			if (!$block) {
				switch ($curr_depth) {
					case 1: {
						$suffix = '_fl';
						break;
					}
					case 2: {
						$suffix = '_sl';
						break;
					}
				}
				$block = getArrayKey($templates, 'menu_block' . $suffix);
				$line = getArrayKey($templates, 'menu_line' . $suffix);
				$line_a = array_key_exists('menu_line' . $suffix . '_a', $templates)
					? $templates['menu_line' . $suffix . '_a']
					: $line;
				$line_in = array_key_exists('menu_line' . $suffix . '_in', $templates)
					? $templates['menu_line' . $suffix . '_in']
					: $line;
			}

			if (!($separator = getArrayKey($templates, 'separator' . $suffix))) {
				$separator = getArrayKey($templates, 'separator');
			}

			if (!($separator_last = getArrayKey($templates, 'separator_last' . $suffix))) {
				$separator_last = getArrayKey($templates, 'separator_last');
			}

			return [$block, $line, $line_a, $line_in, $separator, $separator_last, $class, $class_last];
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
		public function generateMenuResult(
			$menuItems,
			$templates,
			$rootPageId,
			$rootPageURI,
			$URISuffix,
			iUmiHierarchy $umiHierarchy,
			$activeElementsIds,
			$currentDepth = 0,
			$maxDepth = 1,
			$isXslt,
			$showHasChildren
		) {
			if (!isset($menuItems[$rootPageId])) {
				return '';
			}

			$menuItemsCount = umiCount($menuItems[$rootPageId]);

			if ($menuItemsCount == 0) {
				return '';
			}

			$rootMenuItems = $menuItems[$rootPageId];
			$itemsBlock = [];
			$counter = 0;

			/** @var content|ContentMenu $module */
			$module = $this->module;

			list(
				$templateBlock,
				$templateLine,
				$templateLineA,
				$templateLineIn,
				$separator,
				$separatorLast,
				$class,
				$classLast
				) = $module->getMenuTemplates($templates, $currentDepth + 1);

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
						$currentTemplateLine = [];
						break;
					}
					case ($templateLineIn && $templateLineIn != $templateLine && $maxDepth > 1 &&
						isset($activeElementsIds[$elementId])) : {
						$isActive = true;
						$currentTemplateLine = $templateLineIn;
						break;
					}
					default : {
						$isActive = (isset($activeElementsIds[$elementId]) || $activeElementsIds['current'] == $elementId);
						$currentTemplateLine = $isActive ? $templateLineA : $templateLine;
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
					$subMenu = $module->generateMenuResult(
						$menuItems,
						$templates,
						$elementId,
						$rawURI,
						$URISuffix,
						$umiHierarchy,
						$activeElementsIds,
						$currentDepth + 1,
						$maxDepth - 1,
						$isXslt,
						$showHasChildren
					);
					$noSubMenu = false;
				}

				$itemBlock = [];
				$itemBlock['@id'] = $elementId;
				$itemBlock['@link'] = $URI;
				$itemBlock['@name'] = $elementName;
				$itemBlock['@alt-name'] = $elementURIPart;
				$itemBlock['xlink:href'] = 'upage://' . $elementId;

				if ($showHasChildren && isset($rootMenuItem['has-children']) && $needToShowSubMenu) {
					$itemBlock['@has-children'] = true;
				}

				if ($isActive) {
					$itemBlock['attribute:status'] = 'active';
				}

				$itemBlock['node:text'] = (XSLT_NESTED_MENU != 2) ? $elementName : null;

				if ($isXslt) {
					if (is_array($subMenu) && isset($subMenu['items'], $subMenu['items']['nodes:item']) &&
						umiCount($subMenu['items']['nodes:item'])) {
						$itemBlock['items']['nodes:item'] = $subMenu['items']['nodes:item'];
					}
				} else {
					$itemBlock['void:num'] = ($counter + 1);
					$itemBlock['void:sub_menu'] = $subMenu;
					$itemBlock['void:separator'] =
						(($menuItemsCount == ($counter + 1)) && $separatorLast) ? $separatorLast : $separator;
					$itemBlock['class'] = ($menuItemsCount > ($counter + 1)) ? $class : $classLast;
				}

				$itemsBlock[] = content::parseTemplate($currentTemplateLine, $itemBlock, $elementId);

				if ($noSubMenu) {
					$umiHierarchy->unloadElement($elementId);
				}

				$counter++;
			}

			$menuBlock = [
				'subnodes:items' => $itemsBlock,
				'void:lines' => $itemsBlock,
				'id' => $rootPageId
			];

			return content::parseTemplate($templateBlock, $menuBlock, $rootPageId);
		}

		/**
		 * Помечает пункт иерархического меню,  у которых есть дочерние пункты
		 * @param array $menuItems пунты иерархического меню
		 * @param IConnection $connection подключение к бд
		 * @param iPermissionsCollection $permissions коллекция прав доступа
		 * @return array
		 */
		private function markMenuItemsWithChildren(
			array $menuItems,
			IConnection $connection,
			iPermissionsCollection $permissions
		) {
			$elementsWithChildren = [];

			if (umiCount($menuItems) == 0) {
				return $elementsWithChildren;
			}

			$isSV = $permissions->isSv();
			$permissionsJoin = (!$isSV) ? 'LEFT JOIN cms3_permissions as cp ON cp.rel_id = hierarchy.id' : '';
			$permissionsCondition = !$isSV
				? 'AND ' . $permissions->makeSqlWhere($permissions->getUserId()) . ' AND cp.level&1 = 1'
				: '';

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
		private function generateMenuData(
			$rootPageId,
			$maxDepth,
			$languageId,
			$domainId,
			IConnection $connection,
			iPermissionsCollection $permissions,
			iUmiHierarchy $hierarchy,
			$showHasChildren
		) {
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
		private function getMenuItems(
			$rootPageId,
			$maxDepth,
			$languageId,
			$domainId,
			IConnection $connection,
			iPermissionsCollection $permissions
		) {
			$event = new umiEventPoint('getMenuItems');
			$event->addRef('maxDepth', $maxDepth);
			$event->call();

			$rootPageId = (int) $rootPageId;
			$maxDepth = (int) $maxDepth;
			$subMenuFieldId = (int) $this->getShowSubMenuFieldId();
			$isExpandedFieldId = (int) $this->getIsExpandedFieldId();
			$languageId = (int) $languageId;
			$domainId = (int) $domainId;

			$isSV = $permissions->isSv();
			$permissionsJoin = (!$isSV) ? 'LEFT JOIN cms3_permissions as cp ON cp.rel_id = hierarchy.id' : '';
			$permissionsCondition = !$isSV
				? 'AND ' . $permissions->makeSqlWhere($permissions->getUserId()) . ' AND cp.level&1 = 1'
				: '';
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
				return [];
			}

			$rows = [];

			foreach ($result as $row) {
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
			$relations = [];

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
		 * @param iUmiHierarchy $umiHierarchy коллекция иерархических объектов
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
	}

