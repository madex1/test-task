<?php

class menu extends def_module {
	public $per_page;

	public function __construct() {
		parent::__construct();
		$this->loadCommonExtension();

		if ($this->cmsController->getCurrentMode() == "admin") {
			$configTabs = $this->getConfigTabs();
			if ($configTabs) {
				$configTabs->add("config");
			}

			$this->__loadLib("__admin.php");
			$this->__implement("__menu");
			$this->__loadLib("__events.php");
			$this->__implement("__menu_events");
			$this->loadAdminExtension();
		}
		$this->loadSiteExtension();
	}

	private function getMenuTemplatesTpl($templates, $curr_depth) {
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

	private function drawLevel($arr=NULL,$templates,$curr_depth=0,$allParents) {
		if(!$arr) return;
		list($template_block, $template_line, $template_line_a, $template_line_in, $separator, $separator_last, $class, $class_last) = $this->getMenuTemplatesTpl($templates, ($curr_depth + 1));
		$lines = Array();
		$c = 0;
		$sz = count($arr);
		$umiHierarchy = umiHierarchy::getInstance();

		foreach($arr as $menuItem){
			$rel=$link=$id=null;
			$rel = $menuItem->rel;

			$is_active = $is_page = false;
			$line_arr = Array();
			$line_arr['attribute:rel'] = $rel;
			$line_arr['attribute:id'] = $line_arr['void:sub_menu'] = '';
			if(is_numeric($rel)){
				$is_page = true;
				$page = $umiHierarchy->getElement($rel);
				if (!$page instanceof umiHierarchyElement) {
					continue;
				}
				$link = $this->umiLinksHelper->getLinkByParts($page);
				$is_active = (in_array($rel, $allParents) !== false);
				$line_arr['attribute:rel'] = 'page';
				$line_arr['attribute:id'] = $id = $rel;
				$line_arr['attribute:is-active'] = isset($menuItem->isactive) ? $menuItem->isactive : 0;
				$line_arr['attribute:is-deleted'] = isset($menuItem->isdeleted) ? $menuItem->isdeleted : 0;
			} else $link = $menuItem->link;

			$line_arr['attribute:link'] = $link;
			$line_arr['attribute:name'] = $menuItem->name;
			$line_arr['node:text'] = $menuItem->name;

			$line_arr['void:num'] = ($c+1);
			$line_arr['void:separator'] = (($sz == ($c + 1)) && $separator_last) ? $separator_last : $separator;
			$line_arr['void:class'] = ($sz > ($c + 1)) ? $class : $class_last;

			if($is_active) $line_arr['attribute:status'] = "active";
			$line = ($is_active) ? $template_line_a : $template_line;

			$childsArr = NULL;
			if(isset($menuItem->children)) $childsArr = $menuItem->children;
			if($childsArr) $line_arr['items'] = $line_arr['void:sub_menu'] = self::drawLevel($childsArr,$templates,$curr_depth + 1,$allParents);

			$c++;

			$lines[] = ($is_page) ? self::parseTemplateForMenu($line, $line_arr, $id) : self::parseTemplateForMenu($line, $line_arr);
		}

		$block_arr = array(
			'nodes:item'	=> $lines,
			'void:lines'		=> $lines
		);
		return $this->parseTemplateForMenu($template_block, $block_arr);
	}

	public function draw($menuId = NULL, $template = "default") {

		if ($menuId === false) {
			throw new publicException(getLabel('error-object-does-not-exist', null, $menuId));
		}

		$menu = null;

		if (!is_numeric($menuId)) {
			$selector = new selector('objects');
			$selector->types('object-type')->name('menu', 'item_element');
			$selector->where("menu_id")->equals($menuId);
			$selector->option('no-length')->value(true);
			$selector->limit(0, 1);

			if (!$selector->first) {
				throw new publicException(getLabel('error-object-does-not-exist', null, $menuId));
			}

			$menu = $selector->first;
		} else {
			$menu = $this->umiObjectsCollection->getObject($menuId);
		}

		if (!$menu instanceof umiObject) {
			throw new publicException(getLabel('error-object-does-not-exist', null, $menuId));
		}

		if(!$template) $template = "default";
		$templates = def_module::loadTemplates("menu/" . $template);
		$menuHierarchy = $menu->getValue('menuhierarchy');

		if(!$menuHierarchy) {
			throw new publicException(getLabel('error-prop-not-found', null, 'menuHierarchy'));
		}

		$menuHierarchyArray = json_decode($menuHierarchy);
		$currentElementId = $this->cmsController->getCurrentElementId();
		$allParents = umiHierarchy::getInstance()->getAllParents($currentElementId, true);
		$this->loadElements((is_array($menuHierarchyArray)) ? $menuHierarchyArray : (array) $menuHierarchyArray);

		return self::drawLevel($menuHierarchyArray, $templates, 0, $allParents);
	}

	private function collectIds($element) {
		$elementIds = array();

		if (isset($element->rel) && is_numeric($element->rel)) {
			$elementIds[] = $element->rel;
		}

		if (isset($element->children)) {
			foreach ($element->children as $children) {
				$ids = $this->collectIds($children);
				if (is_array($ids)) {
					$elementIds = array_merge($elementIds, $ids);
				}
			}
		}

		if (count($elementIds) > 0) {
			return $elementIds;
		}

		return false;
	}

	protected function loadElements(array $menuHierarchyArray, $needProps = false, $hierarchyTypeId = false) {

		$elementIds = array();

		foreach ($menuHierarchyArray as $element) {
			$ids = $this->collectIds($element);
			if (is_array($ids)) {
				$elementIds = array_merge($elementIds, $ids);
			}
		}

		$elementIds = array_unique($elementIds);

		if (count($elementIds) == 0) {
			return false;
		}

		parent::loadElements($elementIds);

		return true;
	}

	/*short parseTemplate clone*/
	public static function parseTemplateForMenu($template, $arr, $parseElementPropsId = false, $parseObjectPropsId = false, $xsltResultMode = null) {
		if (!is_array($arr)) $arr = array();

		$oldResultMode = null;
		if (is_bool($xsltResultMode)) {
			$oldResultMode = def_module::isXSLTResultMode($xsltResultMode);
		}
		if (def_module::isXSLTResultMode()) {
			if ($parseElementPropsId || $parseObjectPropsId) {
				$extProps = def_module::getMacrosExtendedProps();
				$extGroups = def_module::getMacrosExtendedGroups();
				if (!empty($extProps) || !empty($extGroups)) {
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
			}

			return $arr;
		} else {
			$templater = umiTemplater::create('TPL');
			$variables = array();
			foreach($arr as $m => $v) {
				$m = def_module::getRealKey($m);

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
						$res .= str_replace($from, $to, $str);
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

		if (!is_null($oldResultMode)) {
			def_module::isXSLTResultMode($oldResultMode);
		}

		return $result;
	}

	public function config() {
		return __menu::config();
	}

	public function getObjectEditLink($objectId, $type = false) {
		return $this->pre_lang . "/admin/menu/edit/" . $objectId . "/";
	}

	/*
	* @description - Проверить пункты меню на "Активность", актуальную ссылку и удалена ли страница
	* @param - (array) values - объект меню
	* @return - Object
	*/
	public function editLinkMenu($values = false){
		if(is_array($values) || is_object($values)){
			$values = (is_array($values)) ? $values : (array) $values;
			$hierarchy = umiHierarchy::getInstance();
			foreach($values as $key => $value){
				$rel = $value->rel;
				$children = (!empty($value->children)) ? $value->children : false;

				if($rel!='custom' && $rel!='system'){
					$link = $hierarchy->getPathById($rel);
					$element = $hierarchy->getElement($rel);

					if(!$link || !($element instanceof iUmiHierarchyElement)) {
						unset($values[$key]);
						continue;
					}

					$value->link = $link;
					$element = $hierarchy->getElement($rel);
					$value->isactive = (int) $element->getIsActive();
					$value->isdeleted = (int) $element->getIsDeleted();
					$hierarchy->unloadElement($rel);
				}
				if($children){
					self::editLinkMenu($children);
				}
			}
		}
		return $values;
	}

	/*
	* @description - Получить все имеющиеся меню в системе в модуле "Меню"
	* @param - null
	* @return - Object or false
	*/
	public function getListMenu(){
		static $cache = null;

		if (!is_null($cache)) {
			return $cache;
		}

		$sel = new selector('objects');
		$sel->types('object-type')->name('menu', 'item_element');
		if(!$sel->length) return $cache = false;
		$menuObject = $sel->result;

		return $cache = $menuObject;
	}
};
?>