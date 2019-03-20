<?php

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class MenuMacros {

		/** * @var menu $module */
		public $module;

		/**
		 * Возвращает данные меню
		 * @param null|int $menuId идентификатор меню
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws publicException
		 * @throws selectorException
		 */
		public function draw($menuId = null, $template = 'default') {
			if ($menuId === false) {
				throw new publicException(getLabel('error-object-does-not-exist', null, $menuId));
			}

			$menu = null;
			$umiObjectsCollection = umiObjectsCollection::getInstance();

			if (is_numeric($menuId)) {
				$menu = $umiObjectsCollection->getObject($menuId);
			} else {
				$selector = new selector('objects');
				$selector->types('object-type')->name('menu', 'item_element');
				$selector->where('menu_id')->equals($menuId);
				$selector->option('no-length')->value(true);
				$selector->limit(0, 1);

				if (!$selector->first()) {
					throw new publicException(getLabel('error-object-does-not-exist', null, $menuId));
				}

				$menu = $selector->first();
			}

			if (!$menu instanceof iUmiObject) {
				throw new publicException(getLabel('error-object-does-not-exist', null, $menuId));
			}

			if (!$template) {
				$template = 'default';
			}

			$templates = menu::loadTemplates('menu/' . $template);
			$menuHierarchy = $menu->getValue('menuhierarchy');

			if (!$menuHierarchy) {
				throw new publicException(getLabel('error-prop-not-found', null, 'menuHierarchy'));
			}

			$menuHierarchyArray = json_decode($menuHierarchy);
			$currentElementId = cmsController::getInstance()->getCurrentElementId();
			$allParents = umiHierarchy::getInstance()->getAllParents($currentElementId, true);
			$this->module->loadMenuElements(is_array($menuHierarchyArray) ? $menuHierarchyArray : (array) $menuHierarchyArray);

			return $this->drawLevel($menuHierarchyArray, $templates, 0, $allParents);
		}

		/**
		 * Возвращает данные одного уровня меню.
		 * Вызывается рекурсивно.
		 * @param null|stdClass[] $arr меню в формате json
		 * @param mixed $templates блоки шаблона (для tpl)
		 * @param int $currDepth текущий уровень вложенности меню
		 * @param array $allParents массив идентификаторов страниц, родительских для текущей
		 * @return mixed|void
		 */
		public function drawLevel($arr = null, $templates, $currDepth = 0, $allParents) {
			if (!$arr) {
				return;
			}
			list(
				$templateBlock,
				$templateLine,
				$templateLineA,
				$template_line_in,
				$separator,
				$separatorLast,
				$class,
				$classLast
				) = $this->getMenuTemplates(
				$templates,
				$currDepth + 1
			);
			$lines = [];
			$c = 0;
			$sz = umiCount($arr);
			$umiHierarchy = umiHierarchy::getInstance();
			$umiLinksHelper = umiLinksHelper::getInstance();

			/** @var stdClass $menuItem */
			foreach ($arr as $menuItem) {
				$id = null;
				$rel = $menuItem->rel;
				$isActive = $isPage = false;
				$lineArr = [];
				$lineArr['attribute:rel'] = $rel;
				$lineArr['attribute:id'] = $lineArr['void:sub_menu'] = '';

				if (is_numeric($rel)) {
					$isPage = true;
					$page = $umiHierarchy->getElement($rel);

					if (!$page instanceof iUmiHierarchyElement) {
						continue;
					}

					$link = $umiLinksHelper->getLinkByParts($page);
					$isActive = (in_array($rel, $allParents) !== false);
					$lineArr['attribute:rel'] = 'page';
					$lineArr['attribute:id'] = $id = $rel;
					$lineArr['attribute:is-active'] = isset($menuItem->isactive) ? $menuItem->isactive : 0;
					$lineArr['attribute:is-deleted'] = isset($menuItem->isdeleted) ? $menuItem->isdeleted : 0;
				} else {
					$link = $menuItem->link;
				}

				$lineArr['attribute:link'] = $link;
				$lineArr['attribute:name'] = $menuItem->name;
				$lineArr['node:text'] = $menuItem->name;

				$lineArr['void:num'] = ($c + 1);
				$lineArr['void:separator'] = (($sz == ($c + 1)) && $separatorLast) ? $separatorLast : $separator;
				$lineArr['void:class'] = ($sz > ($c + 1)) ? $class : $classLast;

				if ($isActive) {
					$lineArr['attribute:status'] = 'active';
				}

				$line = $isActive ? $templateLineA : $templateLine;
				$children = null;

				if (isset($menuItem->children)) {
					$children = $menuItem->children;
				}

				if ($children) {
					$lineArr['items'] =
					$lineArr['void:sub_menu'] = $this->drawLevel($children, $templates, $currDepth + 1, $allParents);
				}

				$c++;

				$lines[] = $isPage ? menu::parseTemplate($line, $lineArr, $id) : menu::parseTemplate($line, $lineArr);
			}

			$blockArr = [
				'nodes:item' => $lines,
				'void:lines' => $lines
			];

			return menu::parseTemplate($templateBlock, $blockArr);
		}

		/**
		 * Алиас метода ContentMenu::getMenuTemplates()
		 * @param $templates
		 * @param $currDepth
		 * @return array
		 */
		public function getMenuTemplates($templates, $currDepth) {
			static $content;

			if (!$content instanceof content) {
				/** @var content|ContentMenu $content */
				$content = cmsController::getInstance()->getModule('content');
			}

			return $content->getMenuTemplates($templates, $currDepth);
		}
	}


