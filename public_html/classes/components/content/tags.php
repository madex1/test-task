<?php

	use UmiCms\Service;

	/** Класс для получения списка тегов и страниц по тегам */
	class ContentTags {

		/** @var content $module */
		public $module;

		/**
		 * Возвращает страницы, которым назначен один из указанных тегов, ищет среди страниц всех доменов
		 * @param null $s_tags теги
		 * @param string $s_template имя шаблона для tpl шаблонизатора
		 * @param null $s_base_types список иерархических типов, среди которых искать страницу (пример:
		 *     catalog.object+content.page)
		 * @param bool $i_per_page количество страниц к выводу
		 * @param bool $b_ignore_paging не учитывать пагинацию
		 * @return mixed
		 */
		public function pagesByAccountTags(
			$s_tags = null,
			$s_template = 'tags',
			$s_base_types = null,
			$i_per_page = false,
			$b_ignore_paging = false
		) {
			if ($s_tags === null) {
				$s_tags = getRequest('param0');
			}
			if (!$s_template) {
				$s_template = getRequest('param1');
			}
			if ($s_base_types === null) {
				$s_base_types = getRequest('param2');
			}
			return $this->pages_mklist_by_tags($s_tags, null, $s_template, $i_per_page, $b_ignore_paging, $s_base_types);
		}

		/**
		 * Возвращает страницы, которым назначен один из указанных тегов, ищет среди страниц текущего домена
		 * @param null $s_tags теги
		 * @param string $s_template имя шаблона для tpl шаблонизатора
		 * @param null $s_base_types список иерархических типов, среди которых искать страницу (пример:
		 *     catalog.object+content.page)
		 * @param bool $i_per_page количество страниц к выводу
		 * @param bool $b_ignore_paging не учитывать пагинацию
		 * @return mixed
		 */
		public function pagesByDomainTags(
			$s_tags = null,
			$s_template = 'tags',
			$s_base_types = null,
			$i_per_page = false,
			$b_ignore_paging = false
		) {
			if ($s_tags === null) {
				$s_tags = getRequest('param0');
			}
			if (!$s_template) {
				$s_template = getRequest('param1');
			}
			if ($s_base_types === null) {
				$s_base_types = getRequest('param2');
			}
			$domainId = Service::DomainDetector()->detectId();
			return $this->pages_mklist_by_tags($s_tags, $domainId, $s_template, $i_per_page, $b_ignore_paging, $s_base_types);
		}

		/**
		 * Возвращает облако тегов страниц всех доменов
		 * @param string $s_template имя шаблона для tpl шаблонизатора
		 * @param bool|int $b_curr_user идентификатор пользователя
		 * @param int $i_per_page количество страниц к выводу
		 * @param bool $b_ignore_paging не учитывать пагинацию
		 * @return mixed
		 */
		public function tagsAccountCloud(
			$s_template = 'tags',
			$b_curr_user = false,
			$i_per_page = -1,
			$b_ignore_paging = true
		) {
			return $this->tags_mk_cloud(null, $s_template, $i_per_page, $b_ignore_paging, false, ($b_curr_user ? Service::Auth()->getUserId() : []));
		}

		/**
		 * Возвращает облако эффективности тегов страниц всех доменов
		 * @param string $s_template имя шаблона для tpl шаблонизатора
		 * @param bool $b_curr_user идентификатор пользователя
		 * @param int $i_per_page количество страниц к выводу
		 * @param bool $b_ignore_paging не учитывать пагинацию
		 * @return mixed
		 */
		public function tagsAccountEfficiencyCloud(
			$s_template = 'tags',
			$b_curr_user = false,
			$i_per_page = -1,
			$b_ignore_paging = true
		) {
			return $this->tags_mk_eff_cloud(null, $s_template, $i_per_page, $b_ignore_paging, ($b_curr_user ? Service::Auth()->getUserId() : []));
		}

		/**
		 * Возвращает облако используемых тегов страниц всех доменов
		 * @param string $s_template имя шаблона для tpl шаблонизатора
		 * @param int $i_per_page количество страниц к выводу
		 * @param bool $b_ignore_paging не учитывать пагинацию
		 * @return mixed
		 */
		public function tagsAccountUsageCloud($s_template = 'tags', $i_per_page = -1, $b_ignore_paging = true) {
			return $this->tags_mk_cloud(null, $s_template, $i_per_page, $b_ignore_paging, true);
		}

		/**
		 * Возвращает облако тегов страниц текущего домена
		 * @param string $s_template имя шаблона для tpl шаблонизатора
		 * @param bool|int $b_curr_user идентификатор пользователя
		 * @param int $i_per_page количество страниц к выводу
		 * @param bool $b_ignore_paging не учитывать пагинацию
		 * @return mixed
		 */
		public function tagsDomainCloud(
			$s_template = 'tags',
			$b_curr_user = false,
			$i_per_page = -1,
			$b_ignore_paging = true
		) {
			$domainId = Service::DomainDetector()->detectId();
			return $this->tags_mk_cloud($domainId, $s_template, $i_per_page, $b_ignore_paging, false, ($b_curr_user ? Service::Auth()->getUserId() : []));
		}

		/**
		 * Возвращает облако эффективности тегов страниц текущего домена
		 * @param string $s_template имя шаблона для tpl шаблонизатора
		 * @param bool $b_curr_user идентификатор пользователя
		 * @param int $i_per_page количество страниц к выводу
		 * @param bool $b_ignore_paging не учитывать пагинацию
		 * @return mixed
		 */
		public function tagsDomainEfficiencyCloud(
			$s_template = 'tags',
			$b_curr_user = false,
			$i_per_page = -1,
			$b_ignore_paging = true
		) {
			$domainId = Service::DomainDetector()->detectId();
			return $this->tags_mk_eff_cloud($domainId, $s_template, $i_per_page, $b_ignore_paging, ($b_curr_user ? Service::Auth()->getUserId() : []));
		}

		/**
		 * Возвращает облако используемых тегов страниц текущего домена
		 * @param string $s_template имя шаблона для tpl шаблонизатора
		 * @param int $i_per_page количество страниц к выводу
		 * @param bool $b_ignore_paging не учитывать пагинацию
		 * @return mixed
		 */
		public function tagsDomainUsageCloud($s_template = 'tags', $i_per_page = -1, $b_ignore_paging = true) {
			$domainId = Service::DomainDetector()->detectId();
			return $this->tags_mk_cloud($domainId, $s_template, $i_per_page, $b_ignore_paging);
		}

		/**
		 * Возващает список страниц, которым назначены определенные теги
		 * @param string $tags теги
		 * @param null $currentDomainOnly искать только в текущем домене
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @param bool|int $perPage количество страниц к выводу
		 * @param bool $ignorePaging не учитывать пагинацию
		 * @param string $baseTypes список иерархических типов, среди которых искать страницу (пример:
		 *     catalog.object+content.page)
		 * @return mixed
		 */
		private function pages_mklist_by_tags(
			$tags,
			$currentDomainOnly = null,
			$template = 'tags',
			$perPage = false,
			$ignorePaging = false,
			$baseTypes = ''
		) {
			$umiHierarchy = umiHierarchy::getInstance();

			$s_tpl_pages = 'pages';
			$s_tpl_page = 'page';
			$s_tpl_pages_empty = 'pages_empty';
			$perPage = (int) $perPage;

			if (!$perPage) {
				$perPage = 10;
			}

			if ($perPage === -1) {
				$ignorePaging = true;
			}

			$template = (string) $template;
			if ($template === '') {
				$template = 'tags';
			}

			$currentPage = (int) getRequest('p');
			if ($ignorePaging) {
				$currentPage = 0;
			}

			$baseTypes = (string) $baseTypes;

			list(
				$tplPages,
				$tpl_page,
				$tpl_pages_empty
				) = content::loadTemplates('content/' . $template,
				$s_tpl_pages,
				$s_tpl_page,
				$s_tpl_pages_empty
			);

			$sel = new selector('pages');

			if (!$currentDomainOnly) {
				$sel->where('domain')->equals(false);
			}

			if ($baseTypes !== '') {
				$arrBaseTypes = preg_split("/\s+/is", $baseTypes);
				foreach ($arrBaseTypes as $s_next_type) {
					$arrNextType = explode('.', $s_next_type);
					if (umiCount($arrNextType) === 2) {
						$hierarchyType = umiHierarchyTypesCollection::getInstance()
							->getTypeByName($arrNextType[0], $arrNextType[1]);
						if ($hierarchyType instanceof iUmiHierarchyType) {
							$hierarchyTypeId = $hierarchyType->getId();
							$sel->types('hierarchy-type')->id($hierarchyTypeId);
						}
					}
				}
			}

			$arrTags = preg_split("/\s*,\s*/is", $tags);
			$sel->where('tags')->equals($arrTags);

			if ($perPage !== -1) {
				$sel->limit($currentPage * $perPage, $perPage);
			}

			$result = $sel->result();
			$total = $sel->length();

			$blockArr = [];
			$sz = umiCount($result);

			if ($sz == 0) {
				return content::parseTemplate($tpl_pages_empty, $blockArr);
			}

			$arrItems = [];

			/** @var iUmiHierarchyElement $element */
			foreach ($result as $element) {
				if (!$element) {
					continue;
				}

				$elementId = $element->getId();

				$lineArr = [];
				$lineArr['attribute:id'] = $elementId;
				$lineArr['node:name'] = $element->getName();
				$lineArr['attribute:link'] = $umiHierarchy->getPathById($elementId);
				$lineArr['void:header'] = $element->getName();

				$publishTime = $element->getValue('publish_time');
				if ($publishTime) {
					$lineArr['attribute:publish_time'] = $publishTime->getFormattedDate('U');
				}

				$arrItems[] = content::parseTemplate($tpl_page, $lineArr, $elementId);
				$umiHierarchy->unloadElement($elementId);
			}

			$blockArr['subnodes:items'] = $arrItems;
			$blockArr['tags'] = $tags;
			$blockArr['total'] = $total;
			$blockArr['per_page'] = $perPage;

			return content::parseTemplate($tplPages, $blockArr);
		}

		/**
		 * Возвращает данные для формирования облака тегов
		 * @param null $i_domain_id искать только в текущем домене
		 * @param string $s_template имя шаблона для tpl шаблонизатора
		 * @param int $i_per_page количество тегов к выводу
		 * @param bool $b_ignore_paging не учитывать пагинацию
		 * @param bool $b_by_usage выводить только используемые теги (назначенные страницам)
		 * @param array $arr_users массив идентификаторов пользователей
		 * @return mixed
		 * @throws coreException
		 */
		private function tags_mk_cloud(
			$i_domain_id = null,
			$s_template = 'tags',
			$i_per_page = -1,
			$b_ignore_paging = true,
			$b_by_usage = false,
			$arr_users = []
		) {
			// init and context :
			$s_tpl_tags = 'cloud_tags';
			$s_tpl_tag = 'cloud_tag';
			$s_tpl_tag_sep = 'cloud_tagseparator';
			$s_tpl_tags_empty = 'cloud_tags_empty';

			// validate input :

			if (!$arr_users || (int) $arr_users === -1 || (string) $arr_users === getLabel('page_status_all')) {
				$arr_users = [];
			}
			if (is_int($arr_users)) {
				$arr_users = [(int) $arr_users];
			} elseif (is_array($arr_users)) {
				$arr_users = array_map('intval', $arr_users);
			} else {
				$arr_users = [(int) ((string) $arr_users)];
			}

			$i_per_page = (int) $i_per_page;
			if (!$i_per_page) {
				$i_per_page = 10;
			}
			if ($i_per_page === -1) {
				$b_ignore_paging = true;
			}

			$s_template = (string) $s_template;
			if ($s_template === '') {
				$s_template = 'tags';
			}

			$i_curr_page = (int) getRequest('p');
			if ($b_ignore_paging) {
				$i_curr_page = 0;
			}

			// load templates :
			list(
				$tpl_tags, $tpl_tag, $tpl_tag_sep, $tpl_tags_empty
				) = content::loadTemplates('content/' . $s_template,
				$s_tpl_tags, $s_tpl_tag, $s_tpl_tag_sep, $s_tpl_tags_empty
			);
			// process :

			$max_font_size = 32;
			$min_font_size = 10;
			//
			$s_prefix = '';
			//
			if ($b_by_usage) {
				$o_object_type = umiObjectTypesCollection::getInstance()->getTypeByGUID('root-pages-type');
				$i_tags_field_id = $o_object_type->getFieldId('tags');
				$result = umiObjectProperty::objectsByValue($i_tags_field_id, 'all', true, true, ($i_domain_id ?: -1));
			} else {
				cmsController::getInstance()->getModule('stat');
				$sStatIncPath = dirname(dirname(__FILE__)) . '/stat/classes/reports/';

				if (!class_exists('statisticFactory')) {
					return;
				}

				$factory = new statisticFactory($sStatIncPath);

				$factory->isValid('allTags');
				/** @var allTags $report */
				$report = $factory->get('allTags');

				if ($i_domain_id) {
					$s_prefix = 'Domain';
					$report->setDomain($i_domain_id);
				} else {
					$s_prefix = 'Account';
					$report->setDomain(-1);
				}

				if (is_array($arr_users) && umiCount($arr_users)) {
					$report->setUserIDs($arr_users);
				}

				$result = $report->get();
			}

			if (isset($result['values']) && is_array($result['values'])) {
				natsort2d($result['values'], 'cnt');
				$result['values'] = array_slice($result['values'], -$i_per_page, $i_per_page);
				natsort2d($result['values'], 'value');
			}

			$max = (int) $result['max'];
			$sum = (int) $result['sum'];

			$arrTags = [];

			$s_values_label = ($b_by_usage ? 'values' : 'labels');
			$s_value_label = ($b_by_usage ? 'value' : 'tag');
			$s_value_cnt = 'cnt';

			$sz = umiCount($result[$s_values_label]);
			for ($i = 0; $i < $sz; $i++) {
				$label = $result[$s_values_label][$i];
				$tag = $label[$s_value_label];
				if ($tag === null) {
					continue;
				} //$tag = '[nontagged]';
				$cnt = (int) $label[$s_value_cnt];
				$f_weight = round($cnt * 100 / $sum, 1);
				$font_size = round(((($max_font_size - $min_font_size) / 100) * $f_weight) + $min_font_size);
				$arrTags[$tag] = ['weight' => $f_weight, 'font' => $font_size];
			}
			//
			$summ_weight = 0;
			if (umiCount($arrTags)) {
				$arrTagsTplteds = [];
				foreach ($arrTags as $sTag => $arrTagStat) {
					$summ_weight += $arrTagStat['weight'];
					$params = [
						'tag' => $sTag,
						'tag_urlencoded' => rawurlencode($sTag),
						'attribute:weight' => $arrTagStat['weight'],
						'attribute:font' => $arrTagStat['font'],
						'attribute:context' => $s_prefix
					];
					$arrTagsTplteds[] = content::parseTemplate($tpl_tag, $params);
				}

				if (isset($arrTagsTplteds[0]) && is_array($arrTagsTplteds[0])) { // udata
					$arrForTags = ['subnodes:items' => $arrTagsTplteds];
				} else { // not udata
					$arrForTags = ['items' => implode($tpl_tag_sep, $arrTagsTplteds)];
				}
				//
				$arrForTags['attribute:summ_weight'] = ceil($summ_weight);
				$arrForTags['attribute:context'] = $s_prefix;
				// RETURN
				return content::parseTemplate($tpl_tags, $arrForTags);
			}

			$arrForTags = [];
			// RETURN
			return content::parseTemplate($tpl_tags_empty, $arrForTags);
		}

		/**
		 * Возвращает данные для формирования облака эффективности тегов
		 * @param null $i_domain_id искать только в текущем домене
		 * @param string $s_template мя шаблона для tpl шаблонизатора
		 * @param int $i_per_page количество тегов к выводу
		 * @param bool $b_ignore_paging не учитывать пагинацию
		 * @param array $arr_users массив идентификаторов пользователей
		 * @return mixed
		 * @throws coreException
		 */
		private function tags_mk_eff_cloud(
			$i_domain_id = null,
			$s_template = 'tags',
			$i_per_page = -1,
			$b_ignore_paging = true,
			$arr_users = []
		) {
			if (!$arr_users || (int) $arr_users === -1 || (string) $arr_users === getLabel('page_status_all')) {
				$arr_users = [];
			}
			if (is_int($arr_users)) {
				$arr_users = [(int) $arr_users];
			} elseif (is_array($arr_users)) {
				$arr_users = array_map('intval', $arr_users);
			} else {
				$arr_users = [(int) ((string) $arr_users)];
			}

			$i_per_page = (int) $i_per_page;
			if (!$i_per_page) {
				$i_per_page = 10;
			}
			if ($i_per_page === -1) {
				$b_ignore_paging = true;
			}

			$s_template = (string) $s_template;
			if ($s_template === '') {
				$s_template = 'tags';
			}

			$i_curr_page = (int) getRequest('p');
			if ($b_ignore_paging) {
				$i_curr_page = 0;
			}

			// load templates :
			list(
				$tpl_tags, $tpl_tag, $tpl_tag_sep, $tpl_tags_empty
				) = content::loadTemplates('content/' . $s_template,
				'cloud_tags', 'cloud_tag', 'cloud_tagseparator', 'cloud_tags_empty'
			);
			// process :

			$max_font_size = 32;
			$min_font_size = 10;

			$s_prefix = 'Account';
			if ($i_domain_id) {
				$s_prefix = 'Domain';
			}

			// by usage :
			$o_object_type = umiObjectTypesCollection::getInstance()->getTypeByGUID('root-pages-type');
			$i_tags_field_id = $o_object_type->getFieldId('tags');
			//
			$result_u = umiObjectProperty::objectsByValue($i_tags_field_id, 'all', true, true, ($i_domain_id ?: -1));

			// by popularity
			cmsController::getInstance()->getModule('stat');
			$sStatIncPath = dirname(dirname(__FILE__)) . '/stat/classes/reports/';
			$factory = new statisticFactory($sStatIncPath);
			$factory->isValid('allTags');
			/** @var allTags $report */
			$report = $factory->get('allTags');
			if ($i_domain_id) {
				$report->setDomain($i_domain_id);
			} else {
				$report->setDomain(-1);
			}
			if (is_array($arr_users) && umiCount($arr_users)) {
				$report->setUserIDs($arr_users);
			}
			$result_p = $report->get();

			$arrTags = [];

			$i_sum_u = (int) $result_u['sum'];
			$i_sum_p = (int) $result_p['sum'];
			$arr_usage_tags = $result_u['values'];
			$arr_popular_tags = $result_p['labels'];
			$arr_u_tags = [];
			$arr_p_tags = [];
			$arr_eff_tags = [];

			foreach ($arr_usage_tags as $arr_next_tag) {
				$s_tag = $arr_next_tag['value'];
				$i_tag = (int) $arr_next_tag['cnt'];
				$arr_u_tags[$s_tag] = round($i_tag * 100 / $i_sum_u, 1);
				if (!isset($arr_eff_tags[$s_tag])) {
					$arr_eff_tags[$s_tag] = 0;
				}
			}
			foreach ($arr_popular_tags as $arr_next_tag) {
				$s_tag = $arr_next_tag['tag'];
				$i_tag = (int) $arr_next_tag['cnt'];
				$arr_p_tags[$s_tag] = round($i_tag * 100 / $i_sum_p, 1);
				if (!isset($arr_eff_tags[$s_tag])) {
					$arr_eff_tags[$s_tag] = 0;
				}
			}

			foreach ($arr_eff_tags as $s_tag => $i_efficiency) {
				if (isset($arr_u_tags[$s_tag]) && isset($arr_p_tags[$s_tag])) {
					$arr_eff_tags[$s_tag] = round($arr_p_tags[$s_tag] / $arr_u_tags[$s_tag], 1);
				} elseif (isset($arr_u_tags[$s_tag])) {
					$arr_eff_tags[$s_tag] = 0; // 0/100
				} elseif (isset($arr_p_tags[$s_tag])) {
					$arr_eff_tags[$s_tag] = 1000; // 100/0.1 (0.1 - round(x/y, 1))
				}
			}

			$arrTags = [];

			foreach ($arr_eff_tags as $s_tag => $i_efficiency) {
				if ($s_tag === null) {
					$s_tag = '[nontagged]';
				}

				$f_weight = round($i_efficiency / 10, 1);

				$i_font = round(((($max_font_size - $min_font_size) / 100) * $f_weight) + $min_font_size);

				$arrTags[$s_tag] = ['weight' => $f_weight, 'font' => $i_font];
			}

			$summ_weight = 0;
			if (umiCount($arrTags)) {
				$arrTagsTplteds = [];
				foreach ($arrTags as $sTag => $arrTagStat) {
					$summ_weight += $arrTagStat['weight'];
					$params = [
						'tag' => $sTag,
						'tag_urlencoded' => rawurlencode($sTag),
						'attribute:weight' => $arrTagStat['weight'],
						'attribute:font' => $arrTagStat['font'],
						'attribute:context' => $s_prefix
					];
					$arrTagsTplteds[] = content::parseTemplate($tpl_tag, $params);
				}

				if (isset($arrTagsTplteds[0]) && is_array($arrTagsTplteds[0])) { // udata
					$arrForTags = ['subnodes:items' => $arrTagsTplteds];
				} else { // not udata
					$arrForTags = ['items' => implode($tpl_tag_sep, $arrTagsTplteds)];
				}
				//
				$arrForTags['attribute:summ_weight'] = $summ_weight;
				$arrForTags['attribute:context'] = $s_prefix;
				// RETURN
				return content::parseTemplate($tpl_tags, $arrForTags);
			}

			$arrForTags = [];
			// RETURN
			return content::parseTemplate($tpl_tags_empty, $arrForTags);
		}
	}
