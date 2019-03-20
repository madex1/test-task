<?php

	use UmiCms\Service;

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class NewsMacros {

		/** @var news $module */
		public $module;

		/** Возвращает HTML-код календаря */
		public function calendar() {
			$year = getRequest('year') ? (int) getRequest('year') : date('Y');
			$month = getRequest('month') ? (int) getRequest('month') : date('m');

			$calendar = new Calendar();

			$lang_id = Service::LanguageDetector()->detectId();
			$lang = Service::LanguageCollection()->getLang($lang_id);

			if ($lang->getPrefix() == 'ru') {
				$calendar->setMonthNames([
					getLabel('mouth-january'),
					getLabel('mouth-february'),
					getLabel('mouth-march'),
					getLabel('mouth-april'),
					getLabel('mouth-may'),
					getLabel('mouth-june'),
					getLabel('mouth-july'),
					getLabel('mouth-august'),
					getLabel('mouth-september'),
					getLabel('mouth-october'),
					getLabel('mouth-november'),
					getLabel('mouth-december'),
				]);
				$calendar->setDayNames([
					getLabel('short-week-sunday'),
					getLabel('short-week-monday'),
					getLabel('short-week-tuesday'),
					getLabel('short-week-wednesday'),
					getLabel('short-week-thursday'),
					getLabel('short-week-friday'),
					getLabel('short-week-saturday'),
				]);
				$calendar->setStartDay(1);
			}

			return $calendar->getMonthView($month, $year);
		}

		/**
		 * Возвращает данные последних новостей.
		 * @link http://dev.docs.umi-cms.ru/spravochnik_makrosov_umicms/novosti/news_lastlist/
		 *
		 * @param string $path путь до ленты новостей, либо ID ленты новостей
		 * @param string $template имя шаблона, по которому следует вывести список последних новостей
		 * @param bool|int $per_page количество новостей на одной странице
		 * @param bool $ignore_paging игнорировать постраничную навигацию
		 *
		 * @param string $sDaysInterval строка, определяющая временной интервал по отношению к моменту начала отработки
		 * макроса, которым следует ограничить список выводимых новостей, а также порядок вывода новостей. По умолчанию
		 * (пустая строка), выводятся все новости, имеющиеся в ленте/лентах в порядке уменьшения даты публикации
		 * новости (чем новость более «свежая», тем «выше» ее позиция в списке), что соответствует также значению
		 * параметра, равному «+ -». Формат строки-значения параметра следующий: «[+-]?\d?[mhd]?\s?[+-]?\d?[mhd]?».
		 *
		 * Это два числа, разделенных пробелом, которым предшествуют знаки плюс или минус,
		 * и за которыми следует латинская буква m, h или d. Например: «+10d -5d».
		 * Эти два числа задают две границы временного интервала ограничения выборки.
		 *
		 * @param bool $bSkipOrderByTime Если параметр не указан, то новости сортируются
		 * по дате публикации (последняя — в начале списка). Если указать «1»,
		 * то новости выводятся в порядке их следования в иерархии
		 *
		 * @param int $level уровень вложенности искомых новостей, относительно родительской ленты новостей
		 * @return mixed
		 * @throws publicException
		 * @throws selectorException
		 */
		public function lastlist(
			$path = '',
			$template = 'default',
			$per_page = false,
			$ignore_paging = false,
			$sDaysInterval = '',
			$bSkipOrderByTime = false,
			$level = 1
		) {
			if (!$per_page) {
				$per_page = $this->module->per_page;
			}

			$per_page = (int) $per_page;
			$sDaysInterval = (string) $sDaysInterval;

			if ($sDaysInterval !== '') {
				$sStartDaysOffset = '';
				$sFinishDaysOffset = '';
				$arrDaysInterval = preg_split("/\s+/is", $sDaysInterval);
				if (isset($arrDaysInterval[0])) {
					$sStartDaysOffset = $arrDaysInterval[0];
				}
				if (isset($arrDaysInterval[1])) {
					$sFinishDaysOffset = $arrDaysInterval[1];
				}

				$iNowTime = time();

				if ($sStartDaysOffset === '+') {
					$iStartDaysOffset = (PHP_INT_MAX - $iNowTime);
				} elseif ($sStartDaysOffset === '-') {
					$iStartDaysOffset = (0 - PHP_INT_MAX + $iNowTime);
				} else {
					$iStartDaysOffset = (int) $sStartDaysOffset;
					$sPostfix = mb_substr($sStartDaysOffset, -1);

					if ($sPostfix === 'm') {
						$iStartDaysOffset *= 60;
					} elseif ($sPostfix === 'h' || $sPostfix === 'H') {
						$iStartDaysOffset *= (60 * 60);
					} else {
						$iStartDaysOffset *= (60 * 60 * 24);
					}
				}

				if ($sFinishDaysOffset === '+') {
					$iFinishDaysOffset = (PHP_INT_MAX - $iNowTime);
				} elseif ($sFinishDaysOffset === '-') {
					$iFinishDaysOffset = (0 - PHP_INT_MAX + $iNowTime);
				} else {
					$iFinishDaysOffset = (int) $sFinishDaysOffset;
					$sPostfix = mb_substr($sFinishDaysOffset, -1);

					if ($sPostfix === 'm') {
						$iFinishDaysOffset *= 60;
					} elseif ($sPostfix === 'h' || $sPostfix === 'H') {
						$iFinishDaysOffset *= (60 * 60);
					} else {
						$iFinishDaysOffset *= (60 * 60 * 24);
					}
				}

				$iPeriodStart = $iNowTime + $iStartDaysOffset;
				$iPeriodFinish = $iNowTime + $iFinishDaysOffset;
				$bPeriodOrder = ($iPeriodStart < $iPeriodFinish);
			} else {
				$iPeriodStart = false;
				$iPeriodFinish = false;
				$bPeriodOrder = false;
			}

			$curr_page = (int) getRequest('p');
			if ($ignore_paging) {
				$curr_page = 0;
			}

			$parentId = $this->module->analyzeRequiredPath($path);
			if ($parentId === false && $path != KEYWORD_GRAB_ALL) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $path));
			}

			$umiLinksHelper = umiLinksHelper::getInstance();
			$umiLinksHelper->loadLinkPartForPages([$parentId]);

			$month = (int) getRequest('month');
			$year = (int) getRequest('year');
			$day = (int) getRequest('day');

			$news = new selector('pages');
			$news->types('hierarchy-type')->name('news', 'item');

			if ($path != KEYWORD_GRAB_ALL) {
				$escapedLevel = (int) $level;
				$escapedLevel = ($escapedLevel === 0) ? 1 : $escapedLevel;

				if (is_array($parentId)) {
					foreach ($parentId as $parent) {
						$news->where('hierarchy')->page($parent)->level($escapedLevel);
					}
				} else {
					$news->where('hierarchy')->page($parentId)->level($escapedLevel);
				}
			}

			if (!empty($month) && !empty($year) && !empty($day)) {
				$date1 = mktime(0, 0, 0, $month, $day, $year);
				$date2 = mktime(23, 59, 59, $month, $day, $year);
				$news->where('publish_time')->between($date1, $date2);
			} elseif (!empty($month) && !empty($year)) {
				$date1 = mktime(0, 0, 0, $month, 1, $year);
				$date2 = mktime(23, 59, 59, $month + 1, 0, $year);
				$news->where('publish_time')->between($date1, $date2);
			} elseif (!empty($year)) {
				$date1 = mktime(0, 0, 0, 1, 1, $year);
				$date2 = mktime(23, 59, 59, 12, 31, $year);
				$news->where('publish_time')->between($date1, $date2);
			} elseif ($iPeriodStart !== $iPeriodFinish) {
				if ($iPeriodStart && $iPeriodFinish) {
					if ($sDaysInterval && $sDaysInterval != '+ -') {
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

			$umiHierarchy = umiHierarchy::getInstance();
			$moduleClass = $this->module;

			list(
				$template_block,
				$template_block_empty,
				$template_line,
				$template_archive
				) = $moduleClass::loadTemplates(
				'news/' . $template,
				'lastlist_block',
				'lastlist_block_empty',
				'lastlist_item',
				'lastlist_archive'
			);

			if (umiCount($result) == 0) {
				return $moduleClass::parseTemplate($template_block_empty, []);
			}

			$block_arr = [];
			$lines = [];

			foreach ($result as $element) {
				if (!$element instanceof iUmiHierarchyElement) {
					continue;
				}

				$element_id = $element->getId();

				$line_arr = [];
				$line_arr['attribute:id'] = $element_id;
				$line_arr['node:name'] = $element->getName();
				$line_arr['attribute:link'] = $umiLinksHelper->getLinkByParts($element);
				$line_arr['xlink:href'] = 'upage://' . $element_id;
				$line_arr['void:header'] = $lines_arr['name'] = $element->getName();

				$publish_time = $element->getValue('publish_time');
				if ($publish_time) {
					$line_arr['attribute:publish_time'] = $publish_time->getFormattedDate('U');
				}

				$lent_name = '';
				$lent_link = '';
				$lent_id = $element->getParentId();

				$lent_element = $umiHierarchy->getElement($lent_id);
				if ($lent_element) {
					$lent_name = $lent_element->getName();
					$lent_link = $umiLinksHelper->getLinkByParts($lent_element);
				}

				$line_arr['attribute:lent_id'] = $lent_id;
				$line_arr['attribute:lent_name'] = $lent_name;
				$line_arr['attribute:lent_link'] = $lent_link;

				$lines[] = $moduleClass::parseTemplate($template_line, $line_arr, $element_id);
				$moduleClass::pushEditable('news', 'item', $element_id);
				$umiHierarchy->unloadElement($element_id);
			}

			if (is_array($parentId)) {
				list($parentId) = $parentId;
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['archive'] = ($total > 0) ? $template_archive : '';
			$parent = $umiHierarchy->getElement($parentId);

			if ($parent instanceof iUmiHierarchyElement) {
				$block_arr['archive_link'] = $umiLinksHelper->getLinkByParts($parent);
			}

			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			$block_arr['category_id'] = $parentId;

			return $moduleClass::parseTemplate($template_block, $block_arr, $parentId);
		}

		/**
		 * Возвращает данные последних новостей и лент, которые находятся в текущей ленте новостей
		 * @param string $path не используется
		 * @param string $template имя шаблона для TPL-шаблонизатора
		 * @return string
		 * @throws publicException
		 */
		public function rubric($path = '', $template = 'default') {
			$element_id = cmsController::getInstance()->getCurrentElementId();
			$moduleClass = $this->module;
			$moduleClass::pushEditable('news', 'rubric', $element_id);

			return $this->lastlents($element_id, $template) . $this->lastlist($element_id, $template);
		}

		/**
		 * Возвращает данные новости
		 * @param string $elementPath путь до целевой новости или ее ID
		 * @param string $template имя шаблона для TPL-шаблонизатора
		 * @return mixed
		 * @throws publicException
		 */
		public function view($elementPath = '', $template = 'default') {
			$hierarchy = umiHierarchy::getInstance();
			$moduleClass = $this->module;

			list($template_block) = $moduleClass::loadTemplates(
				'news/' . $template,
				'view'
			);

			$elementId = $moduleClass->analyzeRequiredPath($elementPath);

			if ($elementId === false) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $elementPath));
			}

			$element = $hierarchy->getElement($elementId);

			if (!$element instanceof iUmiHierarchyElement) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $elementPath));
			}

			$moduleClass::pushEditable('news', 'item', $element->getId());

			return $moduleClass::parseTemplate(
				$template_block,
				['id' => $element->getId()],
				$element->getId()
			);
		}

		/**
		 * Возвращает данные последних новостей, связанных по сюжету с указанной новостью
		 * @param bool|string $elementPath путь до новости или ее ID, новостей
		 * @param string $template имя шаблона для TPL-шаблонизатора
		 * @param int $limit количество возвращаемых связанных новостей
		 * @return string|array
		 * @throws publicException
		 * @throws selectorException
		 */
		public function related_links($elementPath = false, $template = 'default', $limit = 3) {
			$moduleClass = $this->module;

			$element_id = $moduleClass->analyzeRequiredPath($elementPath);
			if ($element_id === false) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $elementPath));
			}

			list(
				$template_block,
				$template_block_empty,
				$template_line
				) = $moduleClass::loadTemplates(
				'news/' . $template,
				'related_block',
				'related_block_empty',
				'related_line'
			);

			$element = umiHierarchy::getInstance()->getElement($element_id);
			if (!$element) {
				return $moduleClass::parseTemplate($template_block_empty, []);
			}

			$subjects = $element->getValue('subjects');
			$result = [];

			if (umiCount($subjects)) {
				$news = new selector('pages');
				$news->types('hierarchy-type')->name('news', 'item');
				$news->where('subjects')->equals($subjects);
				$news->order('publish_time')->desc();
				$news->option('no-length')->value(true);
				$news->limit(0, $limit + 1);
				$result = $news->result();
			}

			$sz = umiCount($result);
			if ($sz == 0) {
				return $moduleClass::parseTemplate($template_block_empty, []);
			}

			$umiLinksHelper = umiLinksHelper::getInstance();
			$block_arr = [];
			$lines = [];

			$sz--;
			foreach ($result as $item) {
				if (!$item instanceof iUmiHierarchyElement) {
					continue;
				}
				$line_arr = [];
				$rel_element_id = $item->getId();

				if ($rel_element_id == $element_id) {
					$sz++;
					continue;
				}

				$line_arr['attribute:id'] = $rel_element_id;
				$line_arr['attribute:link'] = $umiLinksHelper->getLinkByParts($item);
				$line_arr['xlink:href'] = 'upage://' . $rel_element_id;
				$line_arr['node:name'] = $item->getName();
				$lines[] = $moduleClass::parseTemplate($template_line, $line_arr, $rel_element_id);
			}

			if (!$lines) {
				return '';
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $block_arr['void:related_links'] = $lines;
			return $moduleClass::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Возвращает данные текущей новости
		 * @return mixed
		 * @throws publicException
		 */
		public function item() {
			$element_id = (int) cmsController::getInstance()->getCurrentElementId();
			return $this->view($element_id);
		}

		/**
		 * Возвращает данные списка лент новостей из раздела. Алиас макроса lastlents().
		 * @param string|int $element_id путь до раздела сайта или его id, из которого
		 * следует брать ленты новостей
		 * @param string $template имя шаблона для TPL-шаблонизатора
		 * @param bool|int|string $per_page максимальное количество лент новостей
		 * @param bool|string $ignore_paging игнорировать значение текущей страницы
		 * при получении списка лент новостей
		 * @return mixed
		 * @throws publicException
		 */
		public function listlents($element_id, $template = 'default', $per_page = false, $ignore_paging = false) {
			return $this->lastlents($element_id, $template, $per_page, $ignore_paging);
		}

		/**
		 * Возвращает данные списка лент новостей из раздела
		 * @param string|int $elementPath путь до раздела сайта или его id, из которого
		 * следует брать ленты новостей
		 * @param string $template имя шаблона для TPL-шаблонизатора
		 * @param bool|int|string $per_page максимальное количество лент новостей
		 * @param bool|string $ignore_paging игнорировать значение текущей страницы
		 * при получении списка лент новостей
		 * @return mixed
		 * @throws publicException
		 */
		public function lastlents($elementPath, $template = 'default', $per_page = false, $ignore_paging = false) {
			$moduleClass = $this->module;
			if (!$per_page) {
				$per_page = $moduleClass->per_page;
			}

			$curr_page = (int) getRequest('p');
			if ($ignore_paging) {
				$curr_page = 0;
			}

			$parent_id = $moduleClass->analyzeRequiredPath($elementPath);
			if ($parent_id === false) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $elementPath));
			}

			$lents = new selector('pages');
			$lents->types('object-type')->name('news', 'rubric');
			$lents->where('hierarchy')->page($parent_id);
			$lents->limit($curr_page * $per_page, $per_page);
			$lents->option('load-all-props')->value(true);

			$result = $lents->result();
			$total = $lents->length();

			list(
				$template_block,
				$template_block_empty,
				$template_line
				) = $moduleClass::loadTemplates(
				'news/' . $template,
				'listlents_block',
				'listlents_block_empty',
				'listlents_item'
			);

			if (umiCount($result) == 0) {
				return $moduleClass::parseTemplate($template_block_empty, []);
			}

			$umiLinksHelper = umiLinksHelper::getInstance();
			$block_arr = [];
			$lines = [];

			foreach ($result as $lent) {
				if (!$lent instanceof iUmiHierarchyElement) {
					continue;
				}

				$line_arr = [];
				$element_id = $lent->getId();
				$line_arr['attribute:id'] = $element_id;
				$line_arr['attribute:link'] = $umiLinksHelper->getLinkByParts($lent);
				$line_arr['xlink:href'] = 'upage://' . $element_id;
				$line_arr['void:header'] = $lines_arr['name'] = $lent->getName();
				$line_arr['node:name'] = $lent->getName();

				$lines[] = $moduleClass::parseTemplate($template_line, $line_arr, $element_id);
				$moduleClass::pushEditable('news', 'rubric', $element_id);
			}

			if (is_array($parent_id)) {
				list($parent_id) = $parent_id;
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;

			return $moduleClass::parseTemplate($template_block, $block_arr, $parent_id);
		}
	}
