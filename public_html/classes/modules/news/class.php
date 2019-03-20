<?php

class news extends def_module {
	public $per_page;

	public function __construct() {
		parent::__construct();

		$this->loadCommonExtension();
		if ($this->cmsController->getCurrentMode() == "admin") {
			$this->__loadLib("__admin.php");
			$this->__implement("__news");

			$this->__loadLib("__subjects.php");
			$this->__implement("__subjects_news");

			$this->loadAdminExtension();

			$this->__loadLib("__custom_adm.php");
			$this->__implement("__custom_adm_news");

			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add("lists");
				$commonTabs->add("subjects");
				$commonTabs->add("rss_list");
			}

			$configTabs = $this->getConfigTabs();

			if ($configTabs instanceof iAdminModuleTabs) {
				$configTabs->add("config");
			}

		} else {
			$this->per_page = regedit::getInstance()->getVal("//modules/news/per_page");
		}

		$this->loadSiteExtension();

		$this->__loadLib("__custom.php");
		$this->__implement("__custom_news");

		$this->__loadLib("__rss_import.php");
		$this->__implement("__rss_import_news");
	}

	/** возвращает календарь. хтмл. */
	public function calendar()
	{
		$this->__loadLib("calendar.php");
		$this->__implement("calendar");

		$year  = getRequest('year') ? (int) getRequest('year') : date('Y');
		$month = getRequest('month') ? (int) getRequest('month') : date('m');


		$calendar = new Calendar();

		$lang_id = $this->cmsController->getCurrentLang()->getId();
		$lang = $this->langsCollection->getLang($lang_id);

		if ($lang->getPrefix() == "ru")
		{
			$calendar->setMonthNames(array("Январь", "Февраль", "Март", "Апрель", "Май", "Июнь",
							"Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"));
			$calendar->setDayNames(array("Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"));
			$calendar->setStartDay(1);
		}

		$result = $calendar->getMonthView($month, $year);

		return $result;

	}


	public function lastlist($path = "", $template = "default", $per_page = false, $ignore_paging = false, $sDaysInterval = '', $bSkipOrderByTime = false) {
		if(!$per_page) $per_page = $this->per_page;
		$per_page = intval($per_page);

		if (strlen($sDaysInterval)) {
			$sStartDaysOffset = ''; $iStartDaysOffset = 0;
			$sFinishDaysOffset = ''; $iFinishDaysOffset = 0;
			$arrDaysInterval = preg_split("/\s+/is", $sDaysInterval);
			if (isset($arrDaysInterval[0])) $sStartDaysOffset = $arrDaysInterval[0];
			if (isset($arrDaysInterval[1])) $sFinishDaysOffset = $arrDaysInterval[1];

			$iNowTime = time();
			if ($sStartDaysOffset === '+') {
				$iStartDaysOffset = (PHP_INT_MAX - $iNowTime);
			} elseif ($sStartDaysOffset === '-') {
				$iStartDaysOffset = (0 - PHP_INT_MAX + $iNowTime);
			} else {
				$iStartDaysOffset = intval($sStartDaysOffset);
				$sPostfix = substr($sStartDaysOffset, -1);
				if ($sPostfix === 'm') { // minutes
					$iStartDaysOffset *= (60);
				} elseif ($sPostfix === 'h' || $sPostfix === 'H') { // hours
					$iStartDaysOffset *= (60*60);
				} else { // days
					$iStartDaysOffset *= (60*60*24);
				}
			}
			if ($sFinishDaysOffset === '+') {
				$iFinishDaysOffset = (PHP_INT_MAX - $iNowTime);
			} elseif ($sFinishDaysOffset === '-') {
				$iFinishDaysOffset = (0 - PHP_INT_MAX + $iNowTime);
			} else {
				$iFinishDaysOffset = intval($sFinishDaysOffset);
				$sPostfix = substr($sFinishDaysOffset, -1);
				if ($sPostfix === 'm') { // minutes
					$iFinishDaysOffset *= (60);
				} elseif ($sPostfix === 'h' || $sPostfix === 'H') { // hours
					$iFinishDaysOffset *= (60*60);
				} else { // days
					$iFinishDaysOffset *= (60*60*24);
				}
			}
			$iPeriodStart = $iNowTime + $iStartDaysOffset;
			$iPeriodFinish = $iNowTime + $iFinishDaysOffset;
			$bPeriodOrder = ($iPeriodStart >= $iPeriodFinish ? false : true);
		} else {
			$iPeriodStart = false;
			$iPeriodFinish = false;
			$bPeriodOrder = false;
		}

		//
		list($template_block, $template_block_empty, $template_line, $template_archive) = def_module::loadTemplates("news/".$template, "lastlist_block", "lastlist_block_empty", "lastlist_item", "lastlist_archive");
		$curr_page = (int) getRequest('p');
		if($ignore_paging) $curr_page = 0;


		$parent_id = $this->analyzeRequiredPath($path);

		if ($parent_id === false && $path != KEYWORD_GRAB_ALL) {
			throw new publicException(getLabel('error-page-does-not-exist', null, $path));
		}

		$this->loadElements($parent_id);
		$month = (int) getRequest('month');
		$year = (int) getRequest('year');
		$day = (int) getRequest('day');

		$news = new selector('pages');
		$news->types('hierarchy-type')->name('news', 'item');
		if ($path != KEYWORD_GRAB_ALL) {
			if (is_array($parent_id)) {
				foreach ($parent_id as $parent) {
					$news->where('hierarchy')->page($parent);
				}
			} else {
				$news->where('hierarchy')->page($parent_id);
			}
		}
		if (!empty($month) && !empty($year) && !empty($day)) {
			$date1 = mktime(0, 0, 0, $month, $day, $year);
			$date2 = mktime(23, 59, 59, $month, $day, $year);
			$news->where('publish_time')->between($date1, $date2);
		} elseif (!empty($month) && !empty($year)) {
			$date1 = mktime(0, 0, 0, $month, 1, $year);
			$date2 = mktime(23, 59, 59, $month+1, 0, $year);
			$news->where('publish_time')->between($date1, $date2);
		} elseif( !empty($year)) {
			$date1 = mktime(0, 0, 0, 1, 1, $year);
			$date2 = mktime(23, 59, 59, 12, 31, $year);
			$news->where('publish_time')->between($date1, $date2);
		} elseif ($iPeriodStart !== $iPeriodFinish) {
			if($iPeriodStart != false && $iPeriodFinish != false) {
				if($sDaysInterval && $sDaysInterval != '+ -') {
					if ($iPeriodStart < $iPeriodFinish) {
						$news->where('publish_time')->between($iPeriodStart, $iPeriodFinish);
					} else {
						$news->where('publish_time')->between($iPeriodFinish, $iPeriodStart);
					}
				}
			}
		}
		if (!$bSkipOrderByTime) {
			if ($bPeriodOrder === true) {
				$news->order('publish_time')->asc();
			} else {
				$news->order('publish_time')->desc();
			}
		}
		selectorHelper::detectFilters($news);
		$news->option('load-all-props')->value(true);
		$news->limit($curr_page * $per_page, $per_page);

		$result = $news->result();
		$total = $news->length();

		$umiLinksHelper = $this->umiLinksHelper;
		$umiHierarchy = umiHierarchy::getInstance();

		if(($sz = count($result)) > 0) {
			$block_arr = Array();
			$lines = Array();
			foreach ($result as $element) {
				if (!$element instanceof umiHierarchyElement) {
					continue;
				}
				$line_arr = Array();
				$element_id = $element->getId();

				$line_arr['attribute:id'] = $element_id;
				$line_arr['node:name'] = $element->getName();
				$line_arr['attribute:link'] = $umiLinksHelper->getLinkByParts($element);
				$line_arr['xlink:href'] = "upage://" . $element_id;
				$line_arr['void:header'] = $lines_arr['name'] = $element->getName();

				if ($publish_time = $element->getValue('publish_time')) {
					$line_arr['attribute:publish_time'] = $publish_time->getFormattedDate("U");
				}

				$lent_name = "";
				$lent_link = "";
				$lent_id = $element->getParentId();

				if ($lent_element = $umiHierarchy->getElement($lent_id)) {
					$lent_name = $lent_element->getName();
					$lent_link = $umiLinksHelper->getLinkByParts($lent_element);
				}

				$line_arr['attribute:lent_id'] = $lent_id;
				$line_arr['attribute:lent_name'] = $lent_name;
				$line_arr['attribute:lent_link'] = $lent_link;

				$lines[] = self::parseTemplate($template_line, $line_arr, $element_id);
				$this->pushEditable("news", "item", $element_id);
				umiHierarchy::getInstance()->unloadElement($element_id);
			}

			if (is_array($parent_id)) {
				list($parent_id) = $parent_id;
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['archive'] = ($total > 0) ? $template_archive : "";
			$parent = $umiHierarchy->getElement($parent_id);
			if ($parent instanceof umiHierarchyElement) {
				$block_arr['archive_link'] = $umiLinksHelper->getLinkByParts($parent);
			}
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			$block_arr['category_id'] = $parent_id;

			return self::parseTemplate($template_block, $block_arr, $parent_id);
		} else {
			return $template_block_empty;
		}
	}

	public function rubric($path = "", $template = "default") {
		$element_id = cmsController::getInstance()->getCurrentElementId();

		$this->pushEditable("news", "rubric", $element_id);
		return $this->lastlents($element_id, $template) . $this->lastlist($element_id, $template);
	}

	public function view($elementPath = "", $template = "default") {
		$hierarchy = umiHierarchy::getInstance();
		list($template_block) = def_module::loadTemplates("news/".$template, "view");

		$elementId = $this->analyzeRequiredPath($elementPath);
		if($elementId === false) {
			throw new publicException(getLabel('error-page-does-not-exist', null, $elementPath));
		}
		$element = $hierarchy->getElement($elementId);
		if($element instanceof iUmiHierarchyElement == false) {
			throw new publicException(getLabel('error-page-does-not-exist', null, $elementPath));
		}

		$this->pushEditable("news", "item", $element->getId());
		return self::parseTemplate($template_block, array(
			'id' => $element->getId()
		), $element->getId());
	}

	public function related_links($elementPath = false, $template = "default", $limit = 3) {
		list($template_block, $template_block_empty, $template_line) = def_module::loadTemplates("news/".$template, "related_block", "related_block_empty", "related_line");

		$element_id = $this->analyzeRequiredPath($elementPath);

		if($element_id === false) {
			throw new publicException(getLabel('error-page-does-not-exist', null, $elementPath));
		}

		$element = umiHierarchy::getInstance()->getElement($element_id);

		if(!$element) return $template_block_empty;
		$subjects = $element->getValue("subjects");

		if(count($subjects)) {
			$news = new selector('pages');
			$news->types('hierarchy-type')->name('news', 'item');
			$news->where('subjects')->equals($subjects);
			$news->order('publish_time')->desc();
			$news->option('no-length')->value(true);
			$news->limit(0, $limit + 1);
			$result = $news->result();
		} else {
			$result = Array();
		}

		if(($sz = count($result)) > 0) {
			$block_arr = Array();
			$lines = Array();

			$sz--;
			foreach ($result as $item) {
				if (!$item instanceof umiHierarchyElement) {
					continue;
				}
				$line_arr = Array();
				$rel_element_id = $item->getId();

				if ($rel_element_id == $element_id) {
					$sz++;
					continue;
				}

				$line_arr['attribute:id'] = $rel_element_id;
				$line_arr['attribute:link'] = $this->umiLinksHelper->getLinkByParts($item);
				$line_arr['xlink:href'] = "upage://" . $rel_element_id;
				$line_arr['node:name'] = $item->getName();
				$lines[] = self::parseTemplate($template_line, $line_arr, $rel_element_id);
			}
			if(!$lines) {
				return "";
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $block_arr['void:related_links'] = $lines;
			return self::parseTemplate($template_block, $block_arr);
		} else {
			return $template_block_empty;
		}
	}


	public function config() {
			return __news::config();
	}


	public function item() {
		$element_id = (int) cmsController::getInstance()->getCurrentElementId();
		return $this->view($element_id);
	}


	public function listlents($element_id, $template = "default", $per_page = false, $ignore_paging = false) {
		return $this->lastlents($element_id, $template, $per_page, $ignore_paging);
	}


	public function lastlents($elementPath, $template = "default", $per_page = false, $ignore_paging = false) {
		if(!$per_page) $per_page = $this->per_page;

		list($template_block, $template_block_empty, $template_line, $template_archive) = def_module::loadTemplates("news/".$template, "listlents_block", "listlents_block_empty", "listlents_item", "listlents_archive");
		$curr_page = (int) getRequest('p');
		if($ignore_paging) {
			$curr_page = 0;
		}

		$parent_id = $this->analyzeRequiredPath($elementPath);

		if($parent_id === false) {
			throw new publicException(getLabel('error-page-does-not-exist', null, $elementPath));
		}

		$lents = new selector('pages');
		$lents->types('object-type')->name('news', 'rubric');
		$lents->where('hierarchy')->page($parent_id);
		$lents->limit($curr_page * $per_page, $per_page);
		$lents->option('load-all-props')->value(true);

		$result = $lents->result();
		$total = $lents->length();

		if(($sz = count($result)) > 0) {
			$block_arr = Array();
			$lines = Array();
			foreach ($result as $lent) {
				if (!$lent instanceof umiHierarchyElement) {
					continue;
				}
				$line_arr = Array();
				$element_id = $lent->getId();
				$line_arr['attribute:id'] = $element_id;
				$line_arr['attribute:link'] = $this->umiLinksHelper->getLinkByParts($lent);
				$line_arr['xlink:href'] = "upage://" . $element_id;
				$line_arr['void:header'] = $lines_arr['name'] = $lent->getName();
				$line_arr['node:name'] = $lent->getName();

				$lines[] = self::parseTemplate($template_line, $line_arr, $element_id);

				$this->pushEditable("news", "rubric", $element_id);
			}
			if(is_array($parent_id)) {
				list($parent_id) = $parent_id;
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;

			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;


			return self::parseTemplate($template_block, $block_arr, $parent_id);
		} else {
			return $template_block_empty;
		}
	}


	public function addNewsItem() {
		$hierarchy = umiHierarchy::getInstance();
		$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
		$objectTypes = umiObjectTypesCollection::getInstance();
		$cmsController = cmsController::getInstance();

		$parent_id = (int) getRequest('param0');
		$object_type_id = (int) getRequest('param1');
		$title = htmlspecialchars(trim(getRequest('title')));

		$parentElement = $hierarchy->getElement($parent_id);
		$tpl_id		= $parentElement->getTplId();
		$domain_id	= $parentElement->getDomainId();
		$lang_id	= $parentElement->getLangId();

		$hierarchy_type_id = $hierarchyTypes->getTypeByName("news", "item")->getId();
		if(!$object_type_id) {
			$object_type_id = $objectTypes->getTypeIdByHierarchyTypeName("news", "item");
		}

		$object_type = $objectTypes->getType($object_type_id);
		if($object_type->getHierarchyTypeId() != $hierarchy_type_id) {
			$this->errorNewMessage("Object type and hierarchy type doesn't match");
			$this->errorPanic();
		}

		$element_id = $hierarchy->addElement($parent_id, $hierarchy_type_id, $title, $title, $object_type_id, $domain_id, $lang_id, $tpl_id);

		$users = $cmsController->getModule("users");
		if($users instanceof def_module) {
			$users->setDefaultPermissions($element_id);
		}

		$element = $hierarchy->getElement($element_id, true);

		$element->setIsActive(true);
		$element->setIsVisible(false);
		$element->setName($title);

		$element->commit();
		$parentElement->setUpdateTime(time());
		$parentElement->commit();

		if (def_module::isXSLTResultMode()) {
			return Array("node:result" => "ok");
		} else {
			$this->redirect($this->cmsController->getCalculatedRefererUri());
		}
	}

	public function getEditLink($element_id, $element_type) {
		switch($element_type) {
			case "rubric": {
				$link_add = $this->pre_lang . "/admin/news/add/{$element_id}/item/";
				$link_edit = $this->pre_lang . "/admin/news/edit/{$element_id}/";

				return Array($link_add, $link_edit);
				break;
			}

			case "item": {
				$link_edit = $this->pre_lang . "/admin/news/edit/{$element_id}/";

				return Array(false, $link_edit);
				break;
			}

			default: {
				return false;
			}
		}
	}

	public function cronActivateNews() {
		$pages = new selector('pages');
		$pages->types('hierarchy-type')->name('news', 'item');
		$pages->where('is_active')->notequals(true);
		$pages->where('begin_time')->eqless(time());
		$pages->option('no-length')->value(true);

		if (!$pages->first) {
			return;
		}

		foreach ($pages as $page){
			$page->setIsActive(true);
			$page->commit();
		}
	}
};
?>
