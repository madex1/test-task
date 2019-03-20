<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class StatAdmin {

		use baseModuleAdmin;

		/** @var stat $module */
		public $module;

		/**
		 * Возвращает данные для вкладки "Сводная статистика"
		 * @throws coreException
		 */
		public function total() {
			$this->module->updateFilter();
			$params = [];

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			$params['tagss']['tags:tags_cloud'] = $this->module->tags_cloud();
			/** @var visitersCommonXml|visitersCommon $report */
			$report = $factory->get('visitersCommon');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$result = $report->get();

			$params['visits']['int:routine'] = (int) $result['avg']['routine'];
			$params['visits']['int:weekend'] = (int) $result['avg']['weekend'];
			$params['visits']['int:sum'] = (int) $result['summ'];
			/** @var hostsCommonXml|hostsCommon $report */
			$report = $factory->get('hostsCommon');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$result = $report->get();

			$params['visits']['int:hosts_total'] = (int) $result['summ'];
			/** @var visitCommonXml|visitCommon $report */
			$report = $factory->get('visitCommon');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$result = $report->get();

			$params['visits']['int:hits_total'] = (int) $result['summ'];
			/** @var visitTimeXml|visitTime $report */
			$report = $factory->get('visitTime');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setLimit(1);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$result = $report->get();

			$visit_time = array_pop($result['dynamic']);
			$params['visits']['int:time'] = round($visit_time['minutes_avg'], 2);
			/** @var visitDeepXml|visitDeep $report */
			$report = $factory->get('visitDeep');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setLimit(1);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$result = $report->get();

			$visit_deep = array_pop($result['dynamic']);
			$params['visits']['int:deep'] = round($visit_deep['level_avg'], 2);
			/** @var sourcesTopXml|sourcesTop $report */
			$report = $factory->get('sourcesTop');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setLimit(1);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$result = $report->get();

			if (isset($result[0]['cnt'])) {
				$params['sources']['string:top_source'] =
					($result[0]['type'] == 'direct' ?
						getLabel('label-direct-enter') : $result[0]['name']) . ' (' . $result[0]['cnt'] . ')';
			}
			/** @var sourcesSEOKeywordsXml|sourcesSEOKeywords $report */
			$report = $factory->get('sourcesSEOKeywords');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setLimit(1);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$result = $report->get();

			$params['sources']['string:top_keyword'] =
				(
					isset($result['all'][0]['text']) &&
					mb_strlen($result['all'][0]['name'])
				)
					? $result['all'][0]['text'] . ' (' . $result['all'][0]['cnt'] . ')'
					: '-';
			/** @var sourcesSEOXml|sourcesSEO $report */
			$report = $factory->get('sourcesSEO');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setLimit(1);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$result = $report->get();

			$params['sources']['string:top_searcher'] =
				(
					isset($result['all'][0]['name']) &&
					$result['all'][0]['name'] !== ''
				)
					? $result['all'][0]['name'] . ' (' . $result['all'][0]['cnt'] . ')'
					: '-';

			$params['filter'] = $this->getFilterPanel();
			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает статистику по тегу
		 * @throws coreException
		 */
		public function tag() {
			$this->module->updateFilter();
			$sReturnMode = getRequest('param1');
			$iTagId = (int) getRequest('param0');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisMdlUrl = '/' . $thisLang . '/admin/stat/';
			$thisUrl = $thisMdlUrl . __FUNCTION__ . '/' . $iTagId;

			if ($sReturnMode == 'xml') {
				$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
				/** @var tagXml|tag $report */
				$report = $factory->get('tag');
				$report->setStart($this->module->from_time);
				$report->setFinish($this->module->to_time);
				$report->setParams([
					'tag_id' => $iTagId
				]);
				$report->setDomain($this->module->domain);
				$report->setUser($this->module->user);
				$aRet = $report->get();

				$sAnswer = '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= "<statistics>\n";
				$sAnswer .= '	<report name="Tag" title="" lang="' . $thisLang . '" host="' . $thisHost . '">
								<chart type="pie">
									<argument field="uri" />
									<value    field="count" />
									<caption  field="uri" />
								</chart>
								<table>
									<column field="uri"   title="' . getLabel('label-page') . '" />
									<column field="count" title="' . getLabel('label-tag-shows-total') . '" />
									<column field="rel"   title="' . getLabel('label-tag-shows-relative') . '" valueSuffix="%" />
								</table>
								<data>';
				foreach ($aRet as $aRow) {
					$sAnswer .= '<row uri="' . $aRow['uri'] . '" count="' . $aRow['count'] . '" rel="' .
						number_format($aRow['rel'] * 100, 2, '.', '') . '" />';
				}
				$sAnswer .= "	</data>\n 	</report>\n</statistics>";
				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			}

			$params = [];
			$params['filter'] = $this->getFilterPanel();
			$params['ReportTag']['flash:report1'] = 'url=' . $thisUrl . '/xml/';
			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает данные для вкладки "Популярность страниц" / "Страницы"
		 * @return string|void
		 * @throws coreException
		 */
		public function popular_pages() {
			$sReturnMode = getRequest('param0');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisUrl = '/' . $thisLang . '/admin/stat/' . __FUNCTION__;
			$thisUrlTail = '';
			$this->module->updateFilter();

			if ($sReturnMode === 'xml') {
				$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
				/** @var pagesHitsXml|pagesHits $report */
				$report = $factory->get('pagesHits');
				$report->setStart($this->module->from_time);
				$report->setFinish($this->module->to_time);
				$report->setLimit($this->module->items_per_page);
				$report->setDomain($this->module->domain);
				$report->setUser($this->module->user);
				$result = $report->get();

				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];
				$title = getLabel('tabs-stat-popular_pages');
				$page = getLabel('label-page');
				$showTotal = getLabel('label-tag-shows-total');
				$showRelative = getLabel('label-tag-shows-relative');
				$sAnswer = '<?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics>
					<report name="pagesHits" title="{$title}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
						<table>
							<column field="name" title="{$page}" prefix="" valueSuffix=""  datatipField="uri"  />
							<column field="cnt" title="{$showTotal}" prefix="" valueSuffix="" />
							<column field="rel" title="{$showRelative}" prefix="" valueSuffix="%" />
						</table>
						<chart type="pie">
							<argument />
							<value field="cnt"/>
							<caption field="name" />
						</chart>
						<data>
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['abs'];
					$fRel = $info['rel'];
					$iHoveredAbs += $iAbs;
					$page_uri = $info['uri'];
					$page_title = '';
					$element_id = umiHierarchy::getInstance()->getIdByPath($page_uri);

					if ($element_id) {
					} elseif ($page_uri == '/') {
						$element_id = umiHierarchy::getInstance()->getDefaultElementId();
					}

					$element = umiHierarchy::getInstance()->getElement($element_id);
					if ($element) {
						$page_title = $element->getName();
					}

					if ($page_title === '') {
						$page_title = $info['uri'];
					}

					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $iAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'rel="' . round($fRel, 1) . '" ';
					foreach ($info as $sName => $sVal) {
						if ($sName !== 'cnt' && $sName !== 'name' && $sName !== 'uri' && $sName !== 'rel') {
							$sAttrs .= $sName . '="' . htmlspecialchars($sVal, ENT_COMPAT) . '" ';
						}
					}
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$sAnswer .= "<row cnt=\"{$iRest}\" name=\"" . getLabel('label-other') . '" uri="" rel="' .
						round($iRest / ($iTotalAbs / 100), 1) . '" />';
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= "</report>\n</statistics>";
				$buffer = Service::Response()
					->getHttpBuffer();
				$buffer->charset('utf-8');
				$buffer->contentType('text/xml');
				$buffer->push($sAnswer);
				$buffer->end();
			}
			$params = [];
			$params['filter'] = $this->getFilterPanel();
			$params['ReportPagePopularity']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает данные для вкладки "Популярность страниц" / "Разделы"
		 * @return string|void
		 * @throws coreException
		 */
		public function sectionHits() {
			$sReturnMode = getRequest('param0');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisUrl = '/' . $thisLang . '/admin/stat/' . __FUNCTION__;
			$thisUrlTail = '';
			$this->module->updateFilter();

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var sectionHitsXml|sectionHits $report */
			$report = $factory->get('sectionHits');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setLimit($this->module->items_per_page);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);

			if ($sReturnMode === 'xml') {
				$result = $report->get();
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];
				$popularSections = getLabel('label-popular-sections');
				$section = getLabel('label-section');
				$requestsAbs = getLabel('label-requests-abs');
				$requestsRel = getLabel('label-requests-rel');
				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics>
				<report name="pagesHits" title="{$popularSections}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
				<table>
					<column field="name" title="{$section}" prefix="" valueSuffix="" datatipField="tip" />
					<column field="count" title="{$requestsAbs}" prefix="" valueSuffix="" />
					<column field="rel" title="{$requestsRel}" prefix="" valueSuffix="%" />
				</table>
				<chart type="pie">
					<argument />
					<value field="count" />
					<caption field="name" />
				</chart>
				<data>
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['abs'];
					$fRel = $info['rel'];
					$iHoveredAbs += $iAbs;
					$page_uri = $info['section'];
					$page_title = '';
					$element_id = umiHierarchy::getInstance()->getIdByPath($page_uri);

					if ($element_id) {
					} elseif ($page_uri == '/') {
						$element_id = umiHierarchy::getInstance()->getDefaultElementId();
					}

					$element = umiHierarchy::getInstance()->getElement($element_id);
					if ($element) {
						$page_title = $element->getName();
					}

					if ($page_title === '') {
						$page_title = $info['section'];
					}

					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars('/' . $thisLang . '/admin/stat/sectionHitsIncluded/' . $info['section']);
					$attr_tip = htmlspecialchars('/' . (($info['section'] != 'index') ? $info['section'] . '/' : ''));

					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'count="' . $iAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'tip="' . $attr_tip . '" ';
					$sAttrs .= 'rel="' . round($fRel, 1) . '" ';
					foreach ($info as $sName => $sVal) {
						if ($sName !== 'cnt' && $sName !== 'name' && $sName !== 'uri' && $sName !== 'rel') {
							$sAttrs .= $sName . '="' . htmlspecialchars($sVal, ENT_COMPAT) . '" ';
						}
					}
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$sAnswer .= "<row count=\"{$iRest}\" name=\"" . getLabel('label-other') . '" uri="" rel="' .
						round($iRest / ($iTotalAbs / 100), 1) . '" />';
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= "</report>\n</statistics>";

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			}
			$params = [];
			$params['filter'] = $this->getFilterPanel();
			$params['ReportSectionPopularity']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает популярность подразделов
		 * @return string|void
		 * @throws coreException
		 */
		public function sectionHitsIncluded() {
			$sSectionId = getRequest('param0');
			$sReturnMode = getRequest('param1');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisUrl = '/' . $thisLang . '/admin/stat/' . __FUNCTION__ . '/' . $sSectionId;
			$thisUrlTail = '';
			$this->module->updateFilter();

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var sectionHitsXml|sectionHits $report */
			$report = $factory->get('sectionHits');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setLimit($this->module->items_per_page);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);

			if ($sReturnMode === 'xml') {
				$result = $report->getIncluded($sSectionId);
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];
				$popularSubsection = getLabel('label-popular-subsections');
				$subsection = getLabel('label-subsection');
				$requestsAbs = getLabel('label-requests-abs');
				$requestsRel = getLabel('label-requests-rel');
				$requests = getLabel('label-requests');
				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics>
				<report name="pagesHits" title="{$popularSubsection}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
				<table>
					<column field="name" title="{$subsection}" prefix="" valueSuffix="" datatipField="uri" />
					<column field="cnt" title="{$requestsAbs}" prefix="" valueSuffix="" />
					<column field="rel" title="{$requestsRel}" prefix="" valueSuffix="%" />
				</table>
				<chart type="pie">
					<argument />
					<value field="cnt" />
					<caption field="name" />
				</chart>
				<data lcol="{$subsection}" rcol="{$requests}">
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['abs'];
					$fRel = $info['rel'];
					$iHoveredAbs += $iAbs;
					$page_uri = $info['uri'];
					$page_title = '';
					$element_id = umiHierarchy::getInstance()->getIdByPath($page_uri);

					if ($element_id) {
					} elseif ($page_uri == '/') {
						$element_id = umiHierarchy::getInstance()->getDefaultElementId();
					}

					$element = umiHierarchy::getInstance()->getElement($element_id);
					if ($element) {
						$page_title = $element->getName();
					}

					if ($page_title === '') {
						$page_title = $info['section'];
					}

					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $iAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'rel="' . round($fRel, 1) . '" ';
					foreach ($info as $sName => $sVal) {
						if ($sName !== 'cnt' && $sName !== 'name' && $sName !== 'uri' && $sName !== 'rel') {
							$sAttrs .= $sName . '="' . htmlspecialchars($sVal, ENT_COMPAT) . '" ';
						}
					}
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$sAnswer .= "<row cnt=\"{$iRest}\" name=\"" . getLabel('label-other') . '" uri="" rel="' .
						round($iRest / ($iTotalAbs / 100), 1) . '" />';
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= "</report>\n</statistics>";

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			}
			$params = [];
			$params['filter'] = $this->getFilterPanel();
			$params['ReportSubsectionsPopularity']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает данные для вкладки "Посещения, Аудитория" / "Хиты"
		 * Алиас visits_hits()
		 * @return mixed
		 */
		public function visits() {
			return $this->visits_hits();
		}

		/**
		 * Возвращает данные для вкладки "Посещения, Аудитория" / "Хиты"
		 * @return string|void
		 * @throws coreException
		 */
		public function visits_hits() {
			$this->module->updateFilter();
			$sReturnMode = getRequest('param0');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisUrl = '/' . $thisLang . '/admin/stat/' . __FUNCTION__;
			$thisUrlTail = '';
			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');

			if ($sReturnMode === 'xml1') {
				/** @var visitCommonXml|visitCommon $report */
				$report = $factory->get('visitCommon');
				$report->setStart($this->module->from_time);
				$report->setFinish($this->module->to_time);
				$report->setDomain($this->module->domain);
				$report->setUser($this->module->user);
				$result = $report->get();

				$hitsDynamicsByDays = getLabel('label-hits-dynamics-by-days');
				$dayLabel = getLabel('label-day');
				$hitsAbs = getLabel('label-hits-abs');
				$hitsRel = getLabel('label-hits-rel');
				$hitsCount = getLabel('label-hits-count');
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];
				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics>
					<report name="visitCommon" title="{$hitsDynamicsByDays}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
						<table>
							<column field="timestamp" title="{$dayLabel}" showas="date" valueSuffix="" prefix="" />
							<column field="count"    title="{$hitsAbs}" valueSuffix="" prefix="" />
							<column field="rel"      title="{$hitsRel}" valueSuffix="%" prefix="" />
						</table>
						<chart type="column" drawTrendLine="true">
							<argument field="timestamp" />
							<value field="count" description="{$hitsCount}" axisTitle="{$hitsCount}" />
							<caption field="date" />
						</chart>
						<data>\n
END;
				$iOldTimeStamp = $this->module->from_time;

				foreach ($result['detail']['result'] as $info) {
					if (!isset($info['ts'])) {
						$info['ts'] = null;
					}
					$sThisDate = date('d M', $info['ts']);
					while (($iOldTimeStamp < $info['ts']) && (date('d M', $iOldTimeStamp) != $sThisDate)) {
						$attr_page_uri = htmlspecialchars('');
						$sAnswer .= '<row ' .
							'timestamp="' . $iOldTimeStamp . '" ' .
							'count="0" ' .
							'date="' . $this->module->makeDate('d M', $iOldTimeStamp) . '" ' .
							'uri="' . $attr_page_uri . "\" rel=\"0\" />\n";
						$iOldTimeStamp += 86400;
					}
					$iOldTimeStamp = $info['ts'] + 86400;
					$iAbs = isset($info['cnt']) ? $info['cnt'] : 0;
					$iHoveredAbs += $iAbs;
					$page_uri = '';
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'timestamp="' . $info['ts'] . '" ';
					$sAttrs .= 'count="' . $iAbs . '" ';
					$sAttrs .= 'date="' . $this->module->makeDate('d M', $info['ts']) . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'rel="' . round($iAbs / ($iTotalAbs / 100), 1) . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sThisDate = date('d M', $this->module->to_time + 86400);
				while (($iOldTimeStamp < $this->module->to_time + 86400) && (date('d M', $iOldTimeStamp) != $sThisDate)) {
					$attr_page_uri = htmlspecialchars('');
					$sAnswer .= '<row ' .
						'timestamp="' . $iOldTimeStamp . '" ' .
						'count="0" ' .
						'date="' . $this->module->makeDate('d M', $iOldTimeStamp) . '" ' .
						'uri="' . $attr_page_uri . "\" rel=\"0\" />\n";
					$iOldTimeStamp += 86400;
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= "</report>\n</statistics>";

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} elseif ($sReturnMode === 'xml2') {
				/** @var visitCommonHoursXml|visitCommonHours $report */
				$report = $factory->get('visitCommonHours');
				$report->setStart($this->module->from_time);
				$report->setFinish($this->module->to_time);
				$report->setDomain($this->module->domain);
				$report->setUser($this->module->user);
				$result = $report->get();
				$iHoveredAbs = 0;

				$hitsDistributionByHours = getLabel('label-hits-distribution-by-hours');
				$hitsAbs = getLabel('label-hits-abs');
				$hitsRel = getLabel('label-hits-rel');
				$hitsCount = getLabel('label-hits-count');
				$hours = getLabel('label-hours');

				$iTotalAbs = $result['summ'] ?: 1;
				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics>
					<report name="visitCommonHours" title="{$hitsDistributionByHours}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
						<table>
							<column field="hourint"  title="{$hours}" valueSuffix="" prefix="" />
							<column field="count" title="{$hitsAbs}" valueSuffix="" prefix="" />
							<column field="rel"   title="{$hitsRel}" valueSuffix="%" prefix="" />
						</table>
						<chart type="line" drawTrendLine="true">
							<argument field="hour" />
							<value field="count" description="{$hitsCount}" axisTitle="{$hitsCount}" />
							<caption field="hourint" />
						</chart>
						<data>\n
END;
				for ($iHour = 0; $iHour < 24; $iHour++) {
					if (isset($result['detail'][$iHour])) {
						$info = $result['detail'][$iHour];
					} else {
						$info = ['ts' => mktime($iHour), 'cnt' => 0];
					}
					$iAbs = $info['cnt'];
					$iHoveredAbs += $iAbs;
					$page_uri = '';
					$iTtlHour = (int) date('G', $info['ts']);
					$page_title = $iTtlHour . '..' . ($iTtlHour + 1);
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'count="' . $iAbs . '" ';
					$sAttrs .= 'hourint="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'timestamp="' . $info['ts'] . '" ';
					$sAttrs .= 'rel="' . round($iAbs / ($iTotalAbs / 100), 1) . '" ';
					$sAttrs .= 'hour="' . $iTtlHour . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= "</report>\n</statistics>";

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} else {
				$params = [];
				$params['filter'] = $this->getFilterPanel();
				$params['ReportHitsByDays']['flash:report1'] = 'url=' . $thisUrl . '/xml1/' . $thisUrlTail;
				$params['ReportHitsByHours']['flash:report2'] = 'url=' . $thisUrl . '/xml2/' . $thisUrlTail;
				$this->setConfigResult($params, 'view');
			}
		}

		/**
		 * Возвращает данные для вкладки "Посещения, Аудитория" / "Сессии"
		 * Алиас метода visitors()
		 */
		public function visits_sessions() {
			$this->visitors();
		}

		/**
		 * Возвращает данные для вкладки "Посещения, Аудитория" / "Сессии"
		 * @throws coreException
		 */
		public function visitors() {
			$this->module->updateFilter();
			$sReturnMode = getRequest('param0');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisUrl = '/' . $thisLang . '/admin/stat/' . __FUNCTION__;
			$thisUrlTail = '';
			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');

			if ($sReturnMode === 'xml1') {
				/** @var visitersCommonXml|visitersCommon $report */
				$report = $factory->get('visitersCommon');
				$report->setStart($this->module->from_time);
				$report->setFinish($this->module->to_time);
				$report->setLimit(PHP_INT_MAX);
				$report->setOffset(0);
				$report->setDomain($this->module->domain);
				$report->setUser($this->module->user);
				$result = $report->get();

				$sessionsDynamicsByDaysPeriod = getLabel('label-sessions-dynamics-by-days-period');
				$dayLabel = getLabel('label-day');
				$sessionsLabel = getLabel('label-sessions');
				$sessionsCountLabel = getLabel('label-sessions-count');

				$iHoveredAbs = 0;
				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
					<statistics>
					<report name="visitCommon" title="{$sessionsDynamicsByDaysPeriod}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
					<table>
						<column field="name" title="{$dayLabel}" valueSuffix="" prefix="" />
						<column field="cnt" title="{$sessionsLabel}" valueSuffix="" prefix="" />
					</table>
					<chart type="column" drawTrendLine="true">
						<argument />
						<value field="cnt" description="{$sessionsCountLabel}" axisTitle="{$sessionsCountLabel}" />
						<caption field="name" />
					</chart>
					<data>
END;
				$iOldTimeStamp = $this->module->from_time;
				foreach ($result['detail'] as $info) {
					$sThisDate = date('d M', $info['ts']);
					while (($iOldTimeStamp < $info['ts']) && (date('d M', $iOldTimeStamp) != $sThisDate)) {
						$attr_page_uri = '';
						$sAnswer .= '<row ' .
							'ts="' . $iOldTimeStamp . '" ' .
							'cnt="0" ' .
							'name="' . $this->module->makeDate('d M', $iOldTimeStamp) . '" ' .
							'uri="' . $attr_page_uri . "\" rel=\"0\" />\n";
						$iOldTimeStamp += 86400;
					}
					$iOldTimeStamp = $info['ts'] + 86400;
					$iAbs = $info['cnt'];
					$iHoveredAbs += $iAbs;
					$page_uri = '/' . $thisLang . '/admin/stat/visits_sessions/';
					$page_title = date('d M', $info['ts']);
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $iAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'ts="' . $info['ts'] . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sThisDate = date('d M', $this->module->to_time + 86400);
				while (($iOldTimeStamp < $this->module->to_time + 86400) && (date('d M', $iOldTimeStamp) != $sThisDate)) {
					$attr_page_uri = htmlspecialchars('');
					$sAnswer .= '<row ' .
						'ts="' . $iOldTimeStamp . '" ' .
						'cnt="0" ' .
						'name="' . $this->module->makeDate('d M', $iOldTimeStamp) . '" ' .
						'uri="' . $attr_page_uri . "\" rel=\"0\" />\n";
					$iOldTimeStamp += 86400;
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= "</report>\n</statistics>";

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} elseif ($sReturnMode === 'xml2') {
				/** @var visitersCommonHoursXml|visitersCommonHours $report */
				$report = $factory->get('visitersCommonHours');
				$report->setStart($this->module->from_time);
				$report->setFinish($this->module->to_time);
				$report->setLimit(PHP_INT_MAX);
				$report->setOffset(0);
				$report->setDomain($this->module->domain);
				$report->setUser($this->module->user);
				$result = $report->get();

				$sessionsDynamicsByHoursPeriod = getLabel('label-sessions-dynamics-by-hours-period');
				$hourLabel = getLabel('label-hour');
				$sessionsLabel = getLabel('label-sessions');
				$sessionsCountLabel = getLabel('label-sessions-count');

				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];
				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
					<statistics>
					<report name="visitCommonHours" title="{$sessionsDynamicsByHoursPeriod}" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
					<table>
						<column field="name" title="{$hourLabel}" valueSuffix="" prefix="" />
						<column field="cnt" title="{$sessionsLabel}" valueSuffix="" prefix="" />
					</table>
					<chart type="line" drawTrendLine="true">
						<argument />
						<value field="cnt" description="{$sessionsCountLabel}" axisTitle="{$sessionsCountLabel}" />
						<caption field="name" />
					</chart>
					<data>
END;
				for ($iHour = 0; $iHour < 24; $iHour++) {
					if (isset($result['detail'][$iHour])) {
						$info = $result['detail'][$iHour];
					} else {
						$info = ['ts' => mktime($iHour), 'cnt' => 0];
					}

					$iAbs = $info['cnt'];
					$iHoveredAbs += $iAbs;
					$page_uri = '';
					$iTtlHour = (int) date('G', $info['ts']);
					$page_title = $iTtlHour . '..' . ($iTtlHour + 1);
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $iAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'ts="' . $info['ts'] . '" ';
					$sAttrs .= 'hour="' . $iTtlHour . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$sAnswer .= "<row cnt=\"{$iRest}\" name=\"" . getLabel('label-other') . '" uri="" />';
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= "</report>\n</statistics>";

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} else {
				$params = [];
				$params['filter'] = $this->getFilterPanel();
				$params['ReportSessionsByDays']['flash:report1'] = 'url=' . $thisUrl . '/xml1/' . $thisUrlTail;
				$params['ReportSessionsByHours']['flash:report2'] = 'url=' . $thisUrl . '/xml2/' . $thisUrlTail;
				$this->setConfigResult($params, 'view');
			}
		}

		/**
		 * Возвращает данные для вкладки "Посещения, Аудитория" / "Посетители"
		 * Алиас метода auditory()
		 * @return mixed
		 */
		public function visits_visitors() {
			return $this->auditory();
		}

		/**
		 * Возвращает данные для вкладки "Посещения, Аудитория" / "Посетители"
		 * @return string|void
		 * @throws coreException
		 */
		public function auditory() {
			$this->module->updateFilter();
			$sReturnMode = getRequest('param0');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisUrl = '/' . $thisLang . '/admin/stat/' . __FUNCTION__;
			$thisUrlTail = '';

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');

			if ($sReturnMode === 'xml1') {
				/** @var auditoryVolumeXml|auditoryVolume $report */
				$report = $factory->get('auditoryVolume');
				$report->setStart($this->module->from_time);
				$report->setFinish($this->module->to_time);
				$report->setDomain($this->module->domain);
				$report->setUser($this->module->user);
				$report->setLimit(PHP_INT_MAX);
				$report->setOffset(0);
				$result = $report->get();

				$sGroupBy = $result['groupby'];
				$iPeriodAdd = 86400;
				$sCmpFmt = 'md';
				$sFormat = 'M-d';
				$sFormatPre = getLabel('label-from');
				$sPeriod = getLabel('label-period');

				switch (true) {
					case ($sGroupBy === 'month') : {
						$sPeriod = getLabel('label-month');
						$sCmpFmt = 'md';
						$iPeriodAdd = 86400 * 7 * 30;
						break;
					}
					case ($sGroupBy === 'week') : {
						$sPeriod = getLabel('label-week');
						$sCmpFmt = 'W';
						$iPeriodAdd = 86400 * 7;
						break;
					}
					case ($sGroupBy === 'hour') : {
						$sPeriod = getLabel('label-hour');
						$sFormat = 'G';
						$sFormatPre = getLabel('label-hour');
						$iPeriodAdd = 3600;
						$sCmpFmt = 'H';
						break;
					}
				}

				$labelAuditoryVolume = getLabel('label-auditory-volume');
				$labelVisitors = getLabel('label-visitors');
				$labelVisitorsCount = getLabel('label-visitors-count');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics>
				<report name="auditoryVolume" title="{$labelAuditoryVolume}" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->module->from_time}" timerange_finish="{{$this->module->to_time}" groupby="{$sGroupBy}">
				<table>
					<column field="period" title="{$sPeriod}" units="" prefix="" />
					<column field="count" title="{$labelVisitors}" units="" prefix="" />
				</table>
				<chart type="column" drawTrendLine="true">
					<argument/>
					<value field="count" description="{$labelVisitorsCount}" axisTitle="{$labelVisitorsCount}" />
					<caption field="period" />
				</chart>
				<data>
END;
				$iOldTimeStamp = $this->module->from_time;

				foreach ($result['detail'] as $info) {
					$sThisDate = date($sCmpFmt, $info['ts']);
					while (($iOldTimeStamp < $info['ts']) && (date($sCmpFmt, $iOldTimeStamp) != $sThisDate)) {
						$attr_page_uri = '';
						$sAnswer .= '<row ' .
							'timestamp="' . $iOldTimeStamp . '" ' .
							'count="0" ' .
							'period="' . $sFormatPre . ' ' . $this->module->makeDate($sFormat, $iOldTimeStamp) . '" ' .
							'uri="' . $attr_page_uri . "\" rel=\"0\" />\n";
						$iOldTimeStamp += $iPeriodAdd;
					}

					$iOldTimeStamp = $info['ts'] + $iPeriodAdd;
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = $sFormatPre . ' ' . $this->module->makeDate($sFormat, (int) $info['ts']);
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'count="' . $iAbs . '" ';
					$sAttrs .= 'period="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'timestamp="' . $info['ts'] . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sThisDate = date($sCmpFmt, $this->module->to_time + 86400);
				while (($iOldTimeStamp < $this->module->to_time + 86400) &&
					(date($sCmpFmt, $iOldTimeStamp) != $sThisDate)) {
					$attr_page_uri = htmlspecialchars('');
					$sAnswer .= '<row ' .
						'timestamp="' . $iOldTimeStamp . '" ' .
						'count="0" ' .
						'period="' . $sFormatPre . ' ' . $this->module->makeDate($sFormat, $iOldTimeStamp) . '" ' .
						'uri="' . $attr_page_uri . "\" rel=\"0\" />\n";
					$iOldTimeStamp += $iPeriodAdd;
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= "</report>\n</statistics>";

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} elseif ($sReturnMode === 'xml2') {
				/** @var auditoryVolumeGrowthXml|auditoryVolumeGrowth $report */
				$report = $factory->get('auditoryVolumeGrowth');
				$report->setStart($this->module->from_time);
				$report->setFinish($this->module->to_time);
				$report->setDomain($this->module->domain);
				$report->setUser($this->module->user);
				$report->setLimit(PHP_INT_MAX);
				$report->setOffset(0);
				$result = $report->get();

				$sGroupBy = $result['groupby'];
				$iPeriodAdd = 86400;
				$sFormat = 'M-d';
				$sFormatPre = getLabel('label-from');
				$sCmpFmt = 'md';
				$sPeriod = getLabel('label-period');

				switch (true) {
					case ($sGroupBy === 'month') : {
						$sPeriod = getLabel('label-month');
						$iPeriodAdd = 86400 * 7 * 30;
						break;
					}
					case ($sGroupBy === 'week') : {
						$sPeriod = getLabel('label-week');
						$iPeriodAdd = 86400 * 7;
						$sCmpFmt = 'W';
						break;
					}
					case ($sGroupBy === 'hour') : {
						$sPeriod = getLabel('label-hour');
						$sFormat = 'G';
						$sFormatPre = getLabel('label-hour');
						$iPeriodAdd = 3600;
						$sCmpFmt = 'H';
						break;
					}
				}

				$labelAuditoryVolumeGrowth = getLabel('label-auditory-volume-growth');
				$labelVisitorsNew = getLabel('label-visitors-new');
				$labelVisitorsNewCount = getLabel('label-visitors-new-count');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics>
				<report name="auditoryVolumeGrowth" title="{$labelAuditoryVolumeGrowth}" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}" groupby="{$sGroupBy}">
				<table>
					<column field="period" title="{$sPeriod}" units="" prefix="" />
					<column field="count" title="{$labelVisitorsNew}" units="" prefix="" />
				</table>
				<chart type="column" drawTrendLine="true">
					<argument />
					<value field="count" description="{$labelVisitorsNewCount}" axisTitle="{$labelVisitorsNewCount}"  />
					<caption field="period" />
				</chart>
				<data>
END;
				$iOldTimeStamp = $this->module->from_time;

				foreach ($result['detail'] as $info) {
					$sThisDate = date($sCmpFmt, $info['ts']);

					while (($iOldTimeStamp < $info['ts']) && (date($sCmpFmt, $iOldTimeStamp) != $sThisDate)) {
						$attr_page_uri = '';
						$sAnswer .= '<row ' .
							'timestamp="' . $iOldTimeStamp . '" ' .
							'count="0" ' .
							'period="' . $sFormatPre . ' ' . $this->module->makeDate($sFormat, $iOldTimeStamp) . '" ' .
							'uri="' . $attr_page_uri . "\" rel=\"0\" />\n";
						$iOldTimeStamp += $iPeriodAdd;
					}

					$iOldTimeStamp = $info['ts'] + $iPeriodAdd;
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = $sFormatPre . ' ' . $this->module->makeDate($sFormat, (int) $info['ts']);
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'count="' . $iAbs . '" ';
					$sAttrs .= 'period="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'timestamp="' . $info['ts'] . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sThisDate = date($sCmpFmt, $this->module->to_time + 86400);

				while (($iOldTimeStamp < $this->module->to_time + 86400) &&
					(date($sCmpFmt, $iOldTimeStamp) != $sThisDate)) {
					$attr_page_uri = htmlspecialchars('');
					$sAnswer .= '<row ' .
						'timestamp="' . $iOldTimeStamp . '" ' .
						'count="0" ' .
						'period="' . $sFormatPre . ' ' . $this->module->makeDate($sFormat, $iOldTimeStamp) . '" ' .
						'uri="' . $attr_page_uri . "\" rel=\"0\" />\n";
					$iOldTimeStamp += $iPeriodAdd;
				}

				$sAnswer .= "</data>\n";
				$sAnswer .= "</report>\n</statistics>";

				$this->sendStatHeaders($sAnswer);

				$this->module->flush($sAnswer);
			} else {
				$params = [];
				$params['filter'] = $this->getFilterPanel();
				$params['ReportAuditory']['flash:report1'] = 'url=' . $thisUrl . '/xml1/' . $thisUrlTail;
				$params['ReportAuditoryNew']['flash:report2'] = 'url=' . $thisUrl . '/xml2/' . $thisUrlTail;
				$this->setConfigResult($params, 'view');
			}
		}

		/**
		 * Возвращает данные для вкладки "Посещения, Аудитория" / "Активность аудитории"
		 * @return string|void
		 * @throws coreException
		 */
		public function auditoryActivity() {
			$this->module->updateFilter();
			$sReturnMode = getRequest('param0');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisUrl = '/' . $thisLang . '/admin/stat/' . __FUNCTION__;
			$thisUrlTail = '';

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var auditoryActivityXml|auditoryActivity $report */
			$report = $factory->get('auditoryActivity');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$result = $report->get();
			$sGroupBy = $result['groupby'];

			if ($sReturnMode === 'xml') {
				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics report="auditoryActivity" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}" groupby="{$sGroupBy}">
				<details>
END;
				foreach ($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = $info['days'];
					switch ($page_title) {
						case '': {
							$page_title = getLabel('label-no-rerurn');
							break;
						}
						case '0':
						case 0: {
							$page_title = getLabel('label-the-same-day');
							break;
						}
						default: {
							$page_title = (int) $page_title;
							$page_title = $this->getPageTitleByDaysNumber($page_title);
						}
					}

					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<detail ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $iAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= <<<END
				</details>
				<dynamic>
END;
				foreach ($result['dynamic'] as $info) {
					$fAbs = $info['avg'];
					$page_uri = '';
					$page_title = getLabel('label-from') . ' ' . $this->module->makeDate('M-d', (int) $info['ts']);
					$attr_cnt = round($fAbs, 2);
					if ($attr_cnt === 0.0) {
						$attr_cnt = getLabel('label-the-same-day');
					}

					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<detail ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $attr_cnt . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'ts="' . $info['ts'] . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= <<<END
				</dynamic>
			</statistic>
END;

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} elseif ($sReturnMode === 'xml1') {

				$labelSecondVisitsDynamics = getLabel('label-second-visits-dynamics');
				$labelReturnDaysCount = getLabel('label-return-days-count');
				$labelVisitorsTotal = getLabel('label-visitors-total');
				$labelVisitorsRelative = getLabel('label-visitors-relative');
				$labelVisitorsCount = getLabel('label-visitors-count');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics>
				<report name="auditoryActivity1" title="{$labelSecondVisitsDynamics}" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
				<table>
					<column field="name" title="{$labelReturnDaysCount}" />
					<column field="cnt" title="{$labelVisitorsTotal}" />
					<column field="rel" title="{$labelVisitorsRelative}" valueSuffix="%" />
				</table>
				<chart type="pie">
					<argument />
					<value field="cnt" description="{$labelVisitorsCount}" />
					<caption field="name" />
				</chart>
				<data>
END;
				$iTotalAbs = 0;
				foreach ($result['detail'] as $info) {
					if (isset($info['cnt'])) {
						$iTotalAbs += (int) $info['cnt'];
					}
				}
				foreach ($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = $info['days'];
					switch ($page_title) {
						case '': {
							$page_title = getLabel('label-no-rerurn');
							break;
						}
						case '0':
						case 0: {
							$page_title = getLabel('label-the-same-day');
							break;
						}
						default: {
							$page_title = (int) $page_title;
							$page_title = $this->getPageTitleByDaysNumber($page_title);
						}
					}
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $iAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'rel="' . ($iTotalAbs ? round($iAbs / ($iTotalAbs / 100), 1) : '0') . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= "</report>\n</statistics>";

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} elseif ($sReturnMode === 'xml2') {
				$sPeriod = getLabel('label-period');
				switch (true) {
					case ($sGroupBy === 'month') : {
						$sPeriod = getLabel('label-month');
						break;
					}
					case ($sGroupBy === 'week') : {
						$sPeriod = getLabel('label-week');
						break;
					}
				}

				$labelChangesInAverageGapBetweenVisitorsReturns =
					getLabel('label-changes-in-average-gap-between-visitors-returns');
				$labelGapBetweenReturns = getLabel('label-gap-between-returns');
				$labelCountDaysBetweenReturns = getLabel('label-count-days-between-returns');
				$labelDaysShort = ' ' . getLabel('label-days-short') . ' ';

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics>
				<report name="auditoryActivity2" title="{$labelChangesInAverageGapBetweenVisitorsReturns}" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}" groupby="{$sGroupBy}">
				<table>
					<column field="name" title="{$sPeriod}" units="" />
					<column field="cnt" title="{$labelGapBetweenReturns}" valueSuffix="{$labelDaysShort}" />
				</table>
				<chart type="line" drawTrendLine="true">
					<argument />
					<value field="cnt" description="{$labelCountDaysBetweenReturns}" axisTitle="{$labelCountDaysBetweenReturns}"  />
					<caption field="name" />
				</chart>
				<data>
END;
				$iOldTimeStamp = $this->module->from_time;

				foreach ($result['dynamic'] as $info) {
					$sThisDate = date('W', $info['ts']);
					while (($iOldTimeStamp < $info['ts']) && (date('W', $iOldTimeStamp) != $sThisDate)) {
						$attr_page_uri = '';
						$sAnswer .= '<row ' .
							'ts="' . $iOldTimeStamp . '" ' . 'cnt="0" ' .
							'name="' . getLabel('label-from') . ' ' . $this->module->makeDate('M-d', $iOldTimeStamp) .
							'" ' . 'uri="' . $attr_page_uri . "\" rel=\"0\" />\n";
						$iOldTimeStamp += 86400 * 7;
					}
					$iOldTimeStamp = $info['ts'] + 86400 * 7;
					$fAbs = $info['avg'];
					$page_uri = '';
					$page_title = getLabel('label-from') . ' ' . $this->module->makeDate('M-d', (int) $info['ts']);

					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . round($fAbs, 2) . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'ts="' . $info['ts'] . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sThisDate = date('d M', $this->module->to_time + 86400);
				while (($iOldTimeStamp < $this->module->to_time + 86400) && (date('d M', $iOldTimeStamp) != $sThisDate)) {
					$attr_page_uri = htmlspecialchars('');
					$sAnswer .= '<row ' .
						'ts="' . $iOldTimeStamp . '" ' .
						'cnt="0" ' .
						'name="' . getLabel('label-from') . ' ' . $this->module->makeDate('M-d', $iOldTimeStamp) . '" ' .
						'uri="' . $attr_page_uri . "\" rel=\"0\" />\n";
					$iOldTimeStamp += 86400 * 7;
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= "</report>\n</statistics>";

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} else {
				$params = [];
				$params['filter'] = $this->getFilterPanel();
				$params['ReportVisitorReturnDayReturn']['flash:report1'] = 'url=' . $thisUrl . '/xml1/' . $thisUrlTail;
				$params['ReportVisitorReturnReturnRange']['flash:report2'] = 'url=' . $thisUrl . '/xml2/' . $thisUrlTail;
				$this->setConfigResult($params, 'view');
			}
		}

		/**
		 * Возвращает данные для вкладки "Посещения, Аудитория" / "Лояльность аудитории"
		 * @return string|void
		 * @throws coreException
		 */
		public function auditoryLoyality() {
			$this->module->updateFilter();
			$sReturnMode = getRequest('param0');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisUrl = '/' . $thisLang . '/admin/stat/' . __FUNCTION__;
			$thisUrlTail = '';

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var auditoryLoyalityXml|auditoryLoyality $report */
			$report = $factory->get('auditoryLoyality');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$result = $report->get();

			$sGroupBy = $result['groupby'];

			if ($sReturnMode === 'xml') {
				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistic report="auditoryLoyality" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}" groupby="{$sGroupBy}">
				<details>
END;
				foreach ($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = (int) $info['visits_count'];
					$page_title = $this->getPageTitleByDaysNumber($page_title);

					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);

					$sAnswer .= '<detail ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $iAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= <<<END
				</details>
				<dynamic>
END;
				foreach ($result['dynamic'] as $info) {
					$fAbs = $info['avg'];
					$page_uri = '';
					$page_title = getLabel('label-from') . ' ' . $this->module->makeDate('M-d', (int) $info['ts']);
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<detail ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . round($fAbs, 2) . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'ts="' . $info['ts'] . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= <<<END
				</dynamic>
			</statistic>
END;

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} elseif ($sReturnMode === 'xml1') {

				$labelSecondVisitsCount = getLabel('label-second-visits-count');
				$labelSecondVisits = getLabel('label-second-visits');
				$labelVisitorsTotal = getLabel('label-visitors-total');
				$labelVisitorsRelative = getLabel('label-visitors-relative');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics>
				<report name="auditoryLoyality1" title="{$labelSecondVisitsCount}" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
				<table>
					<column field="name" title="{$labelSecondVisits}" units="" prefix="" />
					<column field="cnt" title="{$labelVisitorsTotal}" units="" prefix="" />
					<column field="rel" title="{$labelVisitorsRelative}" valueSuffix="%" />
				</table>
				<chart type="pie">
					<argument />
					<value   field="cnt" description="{$labelSecondVisitsCount}"/>
					<caption field="name"/>
				</chart>
				<data>
END;
				$iTotalAbs = 0;

				foreach ($result['detail'] as $info) {
					if (isset($info['cnt'])) {
						$iTotalAbs += (int) $info['cnt'];
					}
				}

				foreach ($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = (int) $info['visits_count'];
					$page_title = $this->getPageTitleByDaysNumber($page_title);

					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);

					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $iAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'rel="' . ($iTotalAbs ? round($iAbs / ($iTotalAbs / 100), 1) : '0') . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} elseif ($sReturnMode === 'xml2') {
				$sPeriod = getLabel('label-period');

				switch (true) {
					case ($sGroupBy === 'month') : {
						$sPeriod = getLabel('label-month');
						break;
					}
					case ($sGroupBy === 'week') : {
						$sPeriod = getLabel('label-week');
						break;
					}
				}

				$labelSecondVisitsDynamics = getLabel('label-second-visits-dynamics');
				$labelSecondVisits = getLabel('label-second-visits');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics>
				<report name="auditoryLoyality2" title="{$labelSecondVisitsDynamics}" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}" groupby="{$sGroupBy}">
				<table>
					<column field="name" title="{$sPeriod}" units="" prefix="" />
					<column field="cnt" title="{$labelSecondVisits}" units="" prefix="" />
				</table>
				<chart type="line" drawTrendLine="true">
					<argument />
					<value   field="cnt"  description="{$labelSecondVisits}"/>
					<caption field="name"/>
				</chart>
				<data>
END;
				$iOldTimeStamp = $this->module->from_time;

				foreach ($result['dynamic'] as $info) {
					$sThisDate = date('W', $info['ts']);
					while (($iOldTimeStamp < $info['ts']) && (date('W', $iOldTimeStamp) != $sThisDate)) {
						$attr_page_uri = '';
						$sAnswer .= '<row ' .
							'ts="' . $iOldTimeStamp . '" ' . 'cnt="0" ' .
							'name="' . getLabel('label-from') . ' ' . $this->module->makeDate('M-d', $iOldTimeStamp) .
							'" ' . 'uri="' . $attr_page_uri . "\" rel=\"0\" />\n";
						$iOldTimeStamp += 86400 * 7;
					}
					$iOldTimeStamp = $info['ts'] + 86400 * 7;
					$fAbs = $info['avg'];
					$page_uri = '';
					$page_title = getLabel('label-from') . ' ' . $this->module->makeDate('M-d', (int) $info['ts']);

					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . round($fAbs, 2) . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'ts="' . $info['ts'] . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sThisDate = date('d M', $this->module->to_time + 86400);
				while (($iOldTimeStamp < $this->module->to_time + 86400) && (date('d M', $iOldTimeStamp) != $sThisDate)) {
					$attr_page_uri = htmlspecialchars('');
					$sAnswer .= '<row ' .
						'ts="' . $iOldTimeStamp . '" ' .
						'cnt="0" ' .
						'name="' . getLabel('label-from') . ' ' . $this->module->makeDate('M-d', $iOldTimeStamp) . '" ' .
						'uri="' . $attr_page_uri . "\" rel=\"0\" />\n";
					$iOldTimeStamp += 86400 * 7;
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} else {
				$params = [];
				$params['filter'] = $this->getFilterPanel();
				$params['ReportAuditoryLoyality']['flash:report1'] = 'url=' . $thisUrl . '/xml1/' . $thisUrlTail;
				$params['ReportAuditoryLoyalityCahnge']['flash:report2'] = 'url=' . $thisUrl . '/xml2/' . $thisUrlTail;
				$this->setConfigResult($params, 'view');
			}
		}

		/**
		 * Возвращает данные для вкладки "Посещения, Аудитория" / "Глубина просмотра"
		 * @return string|void
		 * @throws coreException
		 */
		public function visitDeep() {
			$this->module->updateFilter();
			$sReturnMode = getRequest('param0');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisUrl = '/' . $thisLang . '/admin/stat/' . __FUNCTION__;
			$thisUrlTail = '';

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var visitDeepXml|visitDeep $report */
			$report = $factory->get('visitDeep');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$result = $report->get();
			$sGroupBy = $result['groupby'];

			if ($sReturnMode === 'xml') {
				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistic report="visitDeep" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}" groupby="{$sGroupBy}">
				<details>
END;
				foreach ($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = $info['level'];
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<detail ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $iAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= <<<END
				</details>
				<dynamic>
END;
				foreach ($result['dynamic'] as $info) {
					$fAbs = $info['level_avg'];
					$page_uri = '';
					$page_title = getLabel('label-from') . ' ' . $this->module->makeDate('M-d', (int) $info['ts']);
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<detail ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $fAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'ts="' . $info['ts'] . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= <<<END
				</dynamic>
			</statistic>
END;

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} elseif ($sReturnMode === 'xml1') {

				$labelDistributionOfVisitsInDepthViewSite = getLabel('label-distribution-of-visits-in-depth-view-site');
				$labelDepth = getLabel('js-label-counter-depth');
				$labelPagesOf = getLabel('label-pages-of');
				$labelVisitsTotal = getLabel('label-visits-total');
				$labelVisitsRelative = getLabel('label-visits-relative');
				$labelVisitsCount = getLabel('label-visits-count');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics>
				<report name="visitDeep1" title="{$labelDistributionOfVisitsInDepthViewSite}" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
				<table>
					<column field="name" title="{$labelDepth}" units="{$labelPagesOf}" prefix="" />
					<column field="cnt" title="{$labelVisitsTotal}" units="" prefix="" />
					<column field="rel" title="{$labelVisitsRelative}" valueSuffix="%" prefix="" />
				</table>
				<chart type="column" drawTrendLine="true">
					<argument />
					<value field="cnt" description="{$labelVisitsCount}" axisTitle="{$labelVisitsCount}" />
					<caption field="name" />
				</chart>
				<data>
END;
				$iTotalAbs = 0;
				foreach ($result['detail'] as $info) {
					if (isset($info['cnt'])) {
						$iTotalAbs += (int) $info['cnt'];
					}
				}

				foreach ($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = (int) $info['level'];
					$page_title = $this->getPageTitleByDaysNumber($page_title);

					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);

					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $iAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					if ($iTotalAbs) {
						$sAttrs .= 'rel="' . round($iAbs / ($iTotalAbs / 100), 1) . '" ';
					} else {
						$sAttrs .= 'rel="0" ';
					}
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} elseif ($sReturnMode === 'xml2') {
				switch (true) {
					case ($sGroupBy === 'month') : {
						$sPeriod = getLabel('label-month');
						break;
					}
					case ($sGroupBy === 'week') : {
						$sPeriod = getLabel('label-week');
						break;
					}
					default : {
						$sPeriod = getLabel('label-period');
					}
				}

				$labelDynamicsOfAverageDepthViewSite = getLabel('label-dynamics-of-average-depth-view-site');
				$labelOptionDeep = getLabel('option-deep');
				$labelPagesOf = getLabel('label-pages-of');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics>
				<report name="visitDeep2" title="{$labelDynamicsOfAverageDepthViewSite}" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}" groupby="{$sGroupBy}">
				<table>
					<column field="name" title="{$sPeriod}" units="" prefix="" />
					<column field="cnt" title="$labelOptionDeep" valueSuffix="{$labelPagesOf}" prefix="" />
				</table>
				<chart type="line" drawTrendLine="true">
					<argument />
					<value field="cnt" description="$labelOptionDeep" axisTitle="{$labelPagesOf}" />
					<caption field="name" />
				</chart>
				<data>
END;
				$iOldTimeStamp = $this->module->from_time;
				foreach ($result['dynamic'] as $info) {
					$sThisDate = date('W', $info['ts']);
					while (($iOldTimeStamp < $info['ts']) && (date('W', $iOldTimeStamp) != $sThisDate)) {
						$attr_page_uri = '';
						$sAnswer .= '<row ' .
							'ts="' . $iOldTimeStamp . '" ' . 'cnt="0" ' .
							'name="' . getLabel('label-from') . ' ' . $this->module->makeDate('M-d', $iOldTimeStamp) .
							'" ' . 'uri="' . $attr_page_uri . "\" rel=\"0\" />\n";
						$iOldTimeStamp += 86400 * 7;
					}
					$iOldTimeStamp = $info['ts'] + 86400 * 7;
					$fAbs = $info['level_avg'];
					$page_uri = '';
					$page_title = getLabel('label-from') . ' ' . $this->module->makeDate('M-d', (int) $info['ts']);
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . round($fAbs, 2) . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'ts="' . $info['ts'] . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sThisDate = date('d M', $this->module->to_time + 86400);
				while (($iOldTimeStamp < $this->module->to_time + 86400) && (date('d M', $iOldTimeStamp) != $sThisDate)) {
					$attr_page_uri = htmlspecialchars('');
					$sAnswer .= '<row ' .
						'ts="' . $iOldTimeStamp . '" ' .
						'cnt="0" ' .
						'name="' . getLabel('label-from') . ' ' . $this->module->makeDate('M-d', $iOldTimeStamp) . '" ' .
						'uri="' . $attr_page_uri . "\" rel=\"0\" />\n";
					$iOldTimeStamp += 86400 * 7;
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} else {
				$params = [];
				$params['filter'] = $this->getFilterPanel();
				$params['ReportVisitDeep']['flash:report1'] = 'url=' . $thisUrl . '/xml1/' . $thisUrlTail;
				$params['ReportVisitDeepChange']['flash:report2'] = 'url=' . $thisUrl . '/xml2/' . $thisUrlTail;
				$this->setConfigResult($params, 'view');
			}
		}

		/**
		 * Возвращает данные для вкладки "Посещения, Аудитория" / "Время просмотра"
		 * @return string|void
		 * @throws coreException
		 */
		public function visitTime() {
			$this->module->updateFilter();
			$sReturnMode = getRequest('param0');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisUrl = '/' . $thisLang . '/admin/stat/' . __FUNCTION__;
			$thisUrlTail = '';

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var visitTimeXml|visitTime $report */
			$report = $factory->get('visitTime');

			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$result = $report->get();
			$sGroupBy = $result['groupby'];

			if ($sReturnMode === 'xml') {
				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistic report="visitTime" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}" groupby="{$sGroupBy}">
				<details>
END;
				foreach ($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = $info['minutes'];
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<detail ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $iAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= <<<END
				</details>
				<dynamic>
END;
				foreach ($result['dynamic'] as $info) {
					$fAbs = $info['minutes_avg'];
					$page_uri = '';
					$page_title = $info['ts'];
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<detail ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $fAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= <<<END
				</dynamic>
			</statistic>
END;

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} elseif ($sReturnMode === 'xml1') {

				$labelDuration = getLabel('label-duration');
				$labelMinutesFrom = getLabel('label-minutes-from');
				$labelVisitsTotal = getLabel('label-visits-total');
				$labelVisitsRelative = getLabel('label-visits-relative');
				$labelVisitsCount = getLabel('label-visits-count');
				$labelDistributionOfVisitsByTimeSpentOnSite =
					getLabel('label-distribution-of-visits-by-time-spent-on-site');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics>
				<report name="visitTime1" title="{$labelDistributionOfVisitsByTimeSpentOnSite}" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
				<table>
					<column field="name" title="{$labelDuration}" units="{$labelMinutesFrom}" prefix="" />
					<column field="cnt" title="{$labelVisitsTotal}" units="" prefix="" />
					<column field="rel" title="{$labelVisitsRelative}" valueSuffix="%" prefix="" />
				</table>
				<chart type="pie">
					<argument  />
					<value field="cnt" description="{$labelVisitsCount}" axisTitle="{$labelVisitsCount}" />
					<caption field="name" />
				</chart>
				<data>
END;
				$iAbsTotal = 0;
				foreach ($result['detail'] as $info) {
					if (isset($info['cnt'])) {
						$iAbsTotal += (int) $info['cnt'];
					}
				}
				foreach ($result['detail'] as $info) {
					$iAbs = $info['cnt'];
					$page_uri = '';
					$page_title = (int) $info['minutes'];
					$page_title = $this->getPageTitleByDaysNumber($page_title);
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $iAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'rel="' . ($iAbsTotal ? round($iAbs / ($iAbsTotal / 100), 1) : '0') . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} elseif ($sReturnMode === 'xml2') {
				switch (true) {
					case ($sGroupBy === 'month') : {
						$sPeriod = getLabel('label-month');
						$sAxisTitle = getLabel('label-months');
						break;
					}
					case ($sGroupBy === 'week') : {
						$sPeriod = getLabel('label-week');
						$sAxisTitle = getLabel('label-weeks');
						break;
					}
					default : {
						$sPeriod = getLabel('label-period');
						$sAxisTitle = getLabel('label-periods');
					}
				}
				$sAnswer = "<data>\n";
				$sort_arr = [];
				foreach ($result['dynamic'] AS $uniqid => $row) {
					foreach ($row AS $key => $value) {
						$sort_arr[$key][$uniqid] = $value;
					}
				}
				if (isset($sort_arr['ts']) && !empty($sort_arr['ts'])) {
					array_multisort($sort_arr['ts'], SORT_ASC, $result['dynamic']);
				}
				$iResponseRowCount = 0;
				$iOldTimeStamp = $this->module->from_time;

				foreach ($result['dynamic'] as $info) {
					$iNewTS = strtotime('-7 day', $info['ts']);
					if ($iOldTimeStamp > $iNewTS) {
						$info['ts'] = $iOldTimeStamp;
					}
					$sThisDate = date('W', $info['ts']);
					while (($iOldTimeStamp < $info['ts']) && (date('W', $iOldTimeStamp) != $sThisDate)) {
						$attr_page_uri = '';
						$sAnswer .= '<row ' .
							'cnt="0" ' .
							'name="' . getLabel('label-from') . ' ' . $this->module->makeDate('M-d', $iOldTimeStamp) .
							'" ' . 'uri="' . $attr_page_uri . '" ' . 'ts="' . $iOldTimeStamp . "\" />\n";
						$iOldTimeStamp += 86400 * 7;
						$iResponseRowCount++;
					}

					$iOldTimeStamp = $info['ts'] + 86400 * 7;
					$fAbs = $info['minutes_avg'];
					$page_uri = '';
					$page_title = getLabel('label-from') . ' ' . $this->module->makeDate('M-d', (int) $info['ts']);
					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);
					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . round($fAbs, 1) . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'ts="' . $info['ts'] . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
					$iResponseRowCount++;
				}
				$sThisDate = date('d M', $this->module->to_time + 86400);
				while (($iOldTimeStamp < $this->module->to_time + 86400) && (date('d M', $iOldTimeStamp) != $sThisDate)) {
					$attr_page_uri = htmlspecialchars('');
					$sAnswer .= '<row ' .
						'cnt="0" ' .
						'name="' . getLabel('label-from') . ' ' . $this->module->makeDate('M-d', $iOldTimeStamp) . '" ' .
						'uri="' . $attr_page_uri . '" ' .
						'ts="' . $iOldTimeStamp . "\" />\n";
					$iOldTimeStamp += 86400 * 7;
					$iResponseRowCount++;
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';
				if ($iResponseRowCount > 1) {
					$sChartType = 'line';
				} else {
					$sChartType = 'column';
				}

				$labelDynamicsOfAverageLengthOfTimeVisitorsToSite =
					getLabel('label-dynamics-of-average-length-of-time-visitors-to-site');
				$labelAverageDuration = getLabel('label-average-duration');
				$labelMinutesFrom = getLabel('label-minutes-from');

				$sAnswerHdr = '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswerHdr .= <<<END
				<statistics>
				<report name="visitTime2" title="{$labelDynamicsOfAverageLengthOfTimeVisitorsToSite}" host="{$thisHost}" lang="{$thisLang}" timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}" groupby="{$sGroupBy}">
				<table>
					<column field="ts" title="{$sPeriod}" showas="date" units="" prefix="" />
					<column field="cnt" title="{$labelAverageDuration}" units="" prefix="" />
				</table>
				<chart type="{$sChartType}" drawTrendLine="true">
					<argument fiels="ts" axisTitle="{$sAxisTitle}"  />
					<value field="cnt" description="{$labelAverageDuration}" axisTitle="{$labelMinutesFrom}"  />
					<caption field="name" />
				</chart>
END;
				$sAnswer = $sAnswerHdr . $sAnswer;

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} else {
				$params = [];
				$params['filter'] = $this->getFilterPanel();
				$params['ReportVisitTime']['flash:report1'] = 'url=' . $thisUrl . '/xml1/' . $thisUrlTail;
				$params['ReportVisitTimeChange']['flash:report2'] = 'url=' . $thisUrl . '/xml2/' . $thisUrlTail;
				$this->setConfigResult($params, 'view');
			}
		}

		/**
		 * Возвращает данные для вкладки "Посещения, Аудитория" / "Размещение Аудитории"
		 * @return string|void
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function auditoryLocation() {
			$this->module->updateFilter();
			$sReturnMode = getRequest('param0');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisMdlUrl = '/' . $thisLang . '/admin/stat/';
			$thisUrl = $thisMdlUrl . __FUNCTION__ . '/';
			$thisUrlTail = '';

			if ($sReturnMode == 'xml') {
				$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
				/** @var cityStatXml|cityStat $report */
				$report = $factory->get('cityStat');
				$report->setStart($this->module->from_time);
				$report->setFinish($this->module->to_time);
				$report->setDomain($this->module->domain);
				$report->setUser($this->module->user);
				$aRet = $report->get();

				$labelVisitorsCount = getLabel('label-visitors-count');
				$labelDistributionOfAudienceByCity = getLabel('label-distribution-of-audience-by-city');
				$labelCity = getLabel('label-city');

				$sAnswer = '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= '<statistics>
				<report name="auditoryLocation" title="' . $labelDistributionOfAudienceByCity . '"
						host="' . $thisHost . '" lang="' . $thisLang . '" timerange_start="' .
					$this->module->from_time .
					'" timerange_finish="' . $this->module->to_time . '">
				<table>
					<column field="name"  title="' . $labelCity . '" />
					<column field="count" title="' . $labelVisitorsCount . '"  />
				</table>
				<chart type="pie">
					<argument />
					<value field="count" />
					<caption field="name" />
				</chart>
				<data>';

				foreach ($aRet as $aRow) {
					$sName = $aRow['location'];
					$iCount = $aRow['count'];
					$sAnswer .= '<row name="' . $sName . '" count="' . $iCount . '" />';
				}
				$sAnswer .= '</data></report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			}

			if (!cmsController::getInstance()->getModule('geoip')) {
				throw new publicAdminException(getLabel('error-no-geoip'));
			}

			$params = [];
			$params['filter'] = $this->getFilterPanel();
			$params['ReportLocation']['flash:report1'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает данные для вкладки "Источники и пути" / "Источники"
		 * @return string|void
		 * @throws coreException
		 */
		public function sources() {
			$this->module->updateFilter();
			$sReturnMode = getRequest('param0');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisMdlUrl = '/' . $thisLang . '/admin/stat/';
			$thisUrl = $thisMdlUrl . __FUNCTION__;
			$thisUrlTail = '';

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var sourcesDomainsXml|sourcesDomains $report */
			$report = $factory->get('sourcesDomains');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setLimit($this->module->items_per_page);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);

			if ($sReturnMode === 'xml') {
				$result = $report->get();
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];

				$labelSourcesTotal = getLabel('label-sources-total');
				$labelSourcesRelative = getLabel('label-sources-relative');
				$labelReferringSources = getLabel('label-referring-sources');
				$labelReferrer = getLabel('label-referrer');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
					<statistics>
					<report name="sourcesDomains" title="{$labelReferringSources}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
					<table>
						<column field="name" title="{$labelReferrer}" valueSuffix="" prefix="" />
						<column field="cnt" title="{$labelSourcesTotal}" valueSuffix="" prefix="" />
						<column field="rel" title="{$labelSourcesRelative}" valueSuffix="%" prefix="" />
					</table>
					<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
					</chart>
					<data>
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['cnt'];
					$iHoveredAbs += $iAbs;
					$attr_uri = htmlspecialchars($thisMdlUrl . 'sources_domain/' . $info['domain_id']);
					$attr_name = htmlspecialchars($info['name']);
					$fRel = round($iAbs / ($iTotalAbs / 100), 1);
					$sAnswer .= <<<END
						<row cnt="{$iAbs}" name="{$attr_name}" uri="{$attr_uri}" rel="{$fRel}" />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$sAnswer .= "<row cnt=\"{$iRest}\" name=\"" . getLabel('label-other') . '" uri="" rel="' .
						round($iRest / ($iTotalAbs / 100), 1) . '" />';
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} else {
				$params = [];
				$params['filter'] = $this->getFilterPanel();
				$params['ReportSources']['flash:report1'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
				$this->setConfigResult($params, 'view');
			}
		}

		/**
		 * Возвращает статистику источников по доменам
		 * @return string|void
		 * @throws coreException
		 */
		public function sources_domain() {
			$this->module->updateFilter();
			$domain_id = (int) $_REQUEST['param0'];
			$sReturnMode = getRequest('param1');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisMdlUrl = '/' . $thisLang . '/admin/stat/';
			$thisUrl = $thisMdlUrl . __FUNCTION__ . '/' . $domain_id;
			$thisUrlTail = '';

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var sourcesDomainsConcreteXml|sourcesDomainsConcrete $report */
			$report = $factory->get('sourcesDomainsConcrete');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setLimit($this->module->items_per_page);
			$report->setParams([
				'domain_id' => $domain_id
			]);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);

			if ($sReturnMode === 'xml') {
				$result = $report->get();
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];

				$labelReferringToSelectedDomain = getLabel('label-referring-to-selected-domain');
				$labelReferrerPage = getLabel('label-referrer-page');
				$labelSourcesTotal = getLabel('label-sources-total');
				$labelSourcesRelative = getLabel('label-sources-relative');
				$labelEntry = getLabel('menu-entry');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
					<statistics>
					<report name="sourcesDomainsConcrete" title="{$labelReferringToSelectedDomain}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
					<table>
						<column field="name"  title="{$labelReferrerPage}" valueSuffix="" prefix=""/>
						<column field="cnt"   title="{$labelSourcesTotal}" valueSuffix="" prefix="" />
						<column field="rel"   title="{$labelSourcesRelative}" valueSuffix="%" prefix="" />
						<column field="entry" title="$labelEntry" uriField="entryUri" />
					</table>
					<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
					</chart>
					<data>
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['cnt'];
					$iHoveredAbs += $iAbs;
					$attr_uri = htmlspecialchars('http://' . $info['name'] . $info['uri']);
					$attr_name = $attr_uri;
					$targ_uri = htmlspecialchars($thisMdlUrl . 'sources_entry/' . $info['id']);
					$fRel = round($iAbs / ($iTotalAbs / 100), 1);
					$labelDoubleClickToView = getLabel('label-double-click-to-view');
					$sAnswer .= <<<END
						<row cnt="{$iAbs}" name="{$attr_name}" uri="{$attr_uri}" rel="{$fRel}" entry="[{$labelDoubleClickToView}]" entryUri="{$targ_uri}" />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$sAnswer .= "<row cnt=\"{$iRest}\" name=\"" . getLabel('label-other') . '" uri="" rel="' .
						round($iRest / ($iTotalAbs / 100), 1) . '" />';
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			}
			$params = [];
			$params['filter'] = $this->getFilterPanel();
			$params['ReportSourcesDomains']['flash:report1'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает статистику по точкам входа
		 * @return string|void
		 * @throws coreException
		 */
		public function sources_entry() {
			$this->module->updateFilter();
			$source_id = (int) $_REQUEST['param0'];
			$sReturnMode = getRequest('param1');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisMdlUrl = '/' . $thisLang . '/admin/stat/';
			$thisUrl = $thisMdlUrl . __FUNCTION__ . '/' . $source_id;
			$thisUrlTail = '';

			if ($sReturnMode == 'xml') {
				$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
				/** @var entryByRefererXml|entryByReferer $report */
				$report = $factory->get('entryByReferer');
				$report->setStart($this->module->from_time);
				$report->setFinish($this->module->to_time);
				$report->setParams([
					'source_id' => $source_id
				]);
				$report->setDomain($this->module->domain);
				$report->setUser($this->module->user);
				$aRet = $report->get();

				$labelEntry = getLabel('menu-entry');
				$labelSources = getLabel('label-sources');
				$labelEntryPointsForSelectedSources = getLabel('label-entry-points-for-selected-sources');

				$sAnswer = '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= '<statistics>
					<report name="sourcesEntry" title="' . $labelEntryPointsForSelectedSources . '"
							host="' . $thisHost . '" lang="' . $thisLang . '" timerange_start="' .
					$this->module->from_time .
					'" timerange_finish="' . $this->module->to_time . '">
					<table>
						<column field="name"  title="' . $labelEntry . '" datatipField="uri" />
						<column field="count" title="' . $labelSources . '"  />
					</table>
					<chart type="pie">
						<argument />
						<value field="count" />
						<caption field="name" />
					</chart>
					<data>';
				foreach ($aRet as $aRow) {
					$sName = $aRow['section'];
					$sURI = htmlspecialchars($aRow['uri']);
					$iCount = $aRow['count'];
					$sAnswer .= '<row name="' . $sName . '" count="' . $iCount . '" uri="' . $sURI . '" />';
				}
				$sAnswer .= '</data></report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			}
			$params = [];
			$params['filter'] = $this->getFilterPanel();
			$params['ReportSourcesEntry']['flash:report1'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает данные для вкладки "Источники и пути" / "Поисковые системы"
		 * @return string|void
		 * @throws coreException
		 */
		public function engines() {
			$sReturnMode = getRequest('param0');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisMdlUrl = '/' . $thisLang . '/admin/stat/';
			$thisUrl = $thisMdlUrl . __FUNCTION__;
			$thisUrlTail = '';
			$this->module->updateFilter();

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var sourcesSEOXml|sourcesSEO $report */
			$report = $factory->get('sourcesSEO');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setLimit($this->module->items_per_page);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);

			if ($sReturnMode === 'xml') {
				$result = $report->get();
				$iHoveredAbs = 0;

				$labelSourcesTotal = getLabel('label-sources-total');
				$labelSourcesRelative = getLabel('label-sources-relative');
				$labelSearchSystem = getLabel('label-search-system');
				$labelSearchSystems = getLabel('js-label-counter-sources-search_engines');

				$iTotalAbs = $result['summ'];
				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
					<statistics>
					<report name="sourcesSEO" title="{$labelSearchSystems}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
					<table>
						<column field="name" title="{$labelSearchSystem}" valueSuffix="" prefix="" />
						<column field="cnt" title="{$labelSourcesTotal}" valueSuffix="" prefix="" />
						<column field="rel" title="{$labelSourcesRelative}" valueSuffix="%" prefix="" />
					</table>
					<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
					</chart>
					<data>
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['cnt'];
					$iHoveredAbs += $iAbs;
					$attr_uri = htmlspecialchars($thisMdlUrl . 'engine/' . $info['engine_id'] . '/');
					$attr_name = htmlspecialchars($info['name']);
					$fRel = round($iAbs / ($iTotalAbs / 100), 1);
					$sAnswer .= <<<END
						<row cnt="{$iAbs}" name="{$attr_name}" uri="{$attr_uri}" rel="{$fRel}" />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$sAnswer .= "<row cnt=\"{$iRest}\" name=\"" . getLabel('label-other') . '" uri="" rel="' .
						round($iRest / ($iTotalAbs / 100), 1) . '" />';
				}
				$sAnswer .= '</data></report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			}
			$params = [];
			$params['filter'] = $this->getFilterPanel();
			$params['ReportSourcesSEO']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает статистику по поисковой системе
		 * @return string|void
		 * @throws coreException
		 */
		public function engine() {
			$engine_id = $_REQUEST['param0'];
			$sReturnMode = getRequest('param1');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisMdlUrl = '/' . $thisLang . '/admin/stat/';
			$thisUrl = $thisMdlUrl . __FUNCTION__ . '/' . $engine_id;
			$thisUrlTail = '';
			$this->module->updateFilter();

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var sourcesSEOConcreteXml|sourcesSEOConcrete $report */
			$report = $factory->get('sourcesSEOConcrete');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setLimit($this->module->items_per_page);
			$report->setParams([
				'engine_id' => $engine_id
			]);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);

			if ($sReturnMode === 'xml') {
				$result = $report->get();
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];

				$labelSearchingPhrasesForSelectedSystem = getLabel('label-searching-phrases-for-selected-system');
				$labelSourcesTotal = getLabel('label-sources-total');
				$labelSourcesRelative = getLabel('label-sources-relative');
				$labelSearchingPhrases = getLabel('label-searching-phrases');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
					<statistics>
					<report name="sourcesSEOConcrete" title="{$labelSearchingPhrasesForSelectedSystem}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
					<table>
						<column field="name" title="{$labelSearchingPhrases}" valueSuffix="" prefix="" />
						<column field="cnt" title="{$labelSourcesTotal}" valueSuffix="" prefix="" />
						<column field="rel" title="{$labelSourcesRelative}" valueSuffix="%" prefix="" />
					</table>
					<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
					</chart>

					<data>
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['cnt'];
					$iHoveredAbs += $iAbs;
					$attr_uri = '';
					$attr_name = htmlspecialchars($info['text']);
					$fRel = round($iAbs / ($iTotalAbs / 100), 1);
					$sAnswer .= <<<END
						<row cnt="{$iAbs}" name="{$attr_name}" uri="{$attr_uri}" rel="{$fRel}" />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$sAnswer .= "<row cnt=\"{$iRest}\" name=\"" . getLabel('label-other') . '" uri="" rel="' .
						round($iRest / ($iTotalAbs / 100), 1) . '" />';
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			}
			$params = [];
			$params['filter'] = $this->getFilterPanel();
			$params['ReportSourceSEO']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает данные для вкладки "Источники и пути" / "Поисковые запросы"
		 * @return string|void
		 * @throws coreException
		 */
		public function phrases() {
			$sReturnMode = getRequest('param0');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisMdlUrl = '/' . $thisLang . '/admin/stat/';
			$thisUrl = $thisMdlUrl . __FUNCTION__;
			$thisUrlTail = '';
			$this->module->updateFilter();

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var sourcesSEOKeywordsXml|sourcesSEOKeywords $report */
			$report = $factory->get('sourcesSEOKeywords');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setLimit($this->module->items_per_page);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);

			if ($sReturnMode === 'xml') {
				$result = $report->get();
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];
				$sAnswer = '';

				$labelSearchingPhrasesM = getLabel('label-searching-phrases-m');
				$labelSourcesTotal = getLabel('label-sources-total');
				$labelSourcesRelative = getLabel('label-sources-relative');
				$labelSearchingPhrases = getLabel('label-searching-phrases');

				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
					<statistics>
					<report name="sourcesSEOKeywords" title="{$labelSearchingPhrasesM}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
					<table>
						<column field="name" title="{$labelSearchingPhrases}" valueSuffix="" prefix="" />
						<column field="cnt" title="{$labelSourcesTotal}" valueSuffix="" prefix="" />
						<column field="rel" title="{$labelSourcesRelative}" valueSuffix="%" prefix="" />
					</table>
					<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
					</chart>
					<data>
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['cnt'];
					$iHoveredAbs += $iAbs;
					$attr_uri = htmlspecialchars($thisMdlUrl . 'phrase/' . $info['query_id']);
					$attr_name = htmlspecialchars($info['text']);
					$fRel = round($iAbs / ($iTotalAbs / 100), 1);
					$sAnswer .= <<<END
						<row cnt="{$iAbs}" name="{$attr_name}" uri="{$attr_uri}" rel="{$fRel}" />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$sAnswer .= "<row cnt=\"{$iRest}\" name=\"" . getLabel('label-other') . '" uri="" rel="' .
						round($iRest / ($iTotalAbs / 100), 1) . '" />';
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			}
			$params = [];
			$params['filter'] = $this->getFilterPanel();
			$params['ReportPhrases']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает статистику по поисковому запросу
		 * @return string|void
		 * @throws coreException
		 */
		public function phrase() {
			$query_id = $_REQUEST['param0'];
			$sReturnMode = getRequest('param1');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisMdlUrl = '/' . $thisLang . '/admin/stat/';
			$thisUrl = $thisMdlUrl . __FUNCTION__ . '/' . $query_id;
			$thisUrlTail = '';

			$this->module->updateFilter();
			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var sourcesSEOKeywordsConcreteXml|sourcesSEOKeywordsConcrete $report */
			$report = $factory->get('sourcesSEOKeywordsConcrete');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setLimit($this->module->items_per_page);
			$report->setParams([
				'query_id' => $query_id
			]);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);

			if ($sReturnMode === 'xml') {
				$result = $report->get();
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];

				$labelSearchEnginesOnSelectedPhrase = getLabel('label-search-engines-on-selected-phrase');
				$labelSourcesTotal = getLabel('label-sources-total');
				$labelSourcesRelative = getLabel('label-sources-relative');
				$labelSearchSystem = getLabel('label-search-system');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
					<statistics>
					<report name="sourcesSEOKeywordsConcrete" title="{$labelSearchEnginesOnSelectedPhrase}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
					<table>
						<column field="name" title="{$labelSearchSystem}" valueSuffix="" prefix="" />
						<column field="cnt" title="{$labelSourcesTotal}" units="" prefix="" />
						<column field="rel" title="{$labelSourcesRelative}" valueSuffix="%" prefix="" />
					</table>
					<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
					</chart>

					<data>
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['cnt'];
					$iHoveredAbs += $iAbs;
					$attr_uri = '';
					$attr_name = htmlspecialchars($info['name']);
					$fRel = round($iAbs / ($iTotalAbs / 100), 1);
					$sAnswer .= <<<END
						<row cnt="{$iAbs}" name="{$attr_name}" uri="{$attr_uri}" rel="{$fRel}" />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$sAnswer .= "<row cnt=\"{$iRest}\" name=\"" . getLabel('label-other') . '" uri="" rel="' .
						round($iRest / ($iTotalAbs / 100), 1) . '" />';
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			}
			$params = [];
			$params['filter'] = $this->getFilterPanel();
			$params['ReportPhrase']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает данные для вкладки "Источники и пути" / "Точки входа"
		 * @return string|void
		 * @throws coreException
		 */
		public function entryPoints() {
			$sReturnMode = getRequest('param0');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisMdlUrl = '/' . $thisLang . '/admin/stat/';
			$thisUrl = $thisMdlUrl . __FUNCTION__;
			$thisUrlTail = '';
			$this->module->updateFilter();

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var entryPointsXml|entryPoints $report */
			$report = $factory->get('entryPoints');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setLimit($this->module->items_per_page);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);

			if ($sReturnMode === 'xml') {
				$result = $report->get();
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];
				$labelEntry = getLabel('menu-entry');
				$labelPage = getLabel('label-page');
				$labelGroupSources = getLabel('group-sources');
				$labelHitsAbs = getLabel('label-hits-abs');
				$labelHitsRel = getLabel('label-hits-rel');
				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
					<statistics><report name="entryPoints" title="{$labelEntry}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
					<table>
						<column field="name" title="{$labelPage}" />
						<column field="cnt" title="{$labelHitsAbs}" />
						<column field="rel" title="{$labelHitsRel}" valueSuffix="%" />
						<column field="ref" title="{$labelGroupSources}" uriField="refURI" />
					</table>
					<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
					</chart>
					<data>
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['abs'];
					$fRel = $info['rel'];
					$iHoveredAbs += $iAbs;
					$page_uri = $info['uri'];
					$page_title = '';
					$page_id = (int) $info['id'];
					$element_id = umiHierarchy::getInstance()->getElement($page_id);

					if ($element_id) {
					} elseif ($element_id = umiHierarchy::getInstance()->getIdByPath($page_uri)) {
					} elseif ($page_uri == '/') {
						$element_id = umiHierarchy::getInstance()->getDefaultElementId();
					}

					$element = umiHierarchy::getInstance()->getElement($element_id);
					if ($element) {
						$page_title = $element->getName();
					}

					if ($page_title === '') {
						$page_title = $info['uri'];
					}

					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($thisMdlUrl . 'paths/?nextpath=' . $page_id);

					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $iAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'rel="' . round($fRel, 1) . '" ';
					foreach ($info as $sName => $sVal) {
						if ($sName !== 'cnt' && $sName !== 'name' && $sName !== 'uri' && $sName !== 'rel') {
							$sAttrs .= $sName . '="' . htmlspecialchars($sVal, ENT_COMPAT) . '" ';
						}
					}
					$sAttrs .= 'ref="[' . getLabel('label-double-click-to-view') . ']" ';
					$sAttrs .= 'refURI="' . htmlspecialchars($thisMdlUrl . 'refererByEntry/' . $page_id) . '" ';
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$sAnswer .= "<row cnt=\"{$iRest}\" name=\"" . getLabel('label-other') . '" uri="" rel="' .
						round($iRest / ($iTotalAbs / 100), 1) . '" />';
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			}
			$params = [];
			$params['filter'] = $this->getFilterPanel();
			$params['ReportEntryPoints']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает статистику по точке входа
		 * @return string|void
		 * @throws coreException
		 */
		public function paths() {
			$sParamPath = getRequest('nextpath');
			$sReturnMode = getRequest('param0');
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisMdlUrl = '/' . $thisLang . '/admin/stat/';
			$thisUrl = $thisMdlUrl . __FUNCTION__;
			$thisUrlTail = '';

			$this->module->updateFilter();
			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var pathsXml|paths $report */
			$report = $factory->get('paths');
			$report->setParams([
				'path' => $sParamPath
			]);
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setLimit($this->module->items_per_page);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);

			if ($sReturnMode === 'xml') {
				$result = $report->get();
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];

				$labelPath = getLabel('label-path');
				$labelSourcesTotal = getLabel('label-sources-total');
				$labelSourcesRelative = getLabel('label-sources-relative');
				$labelPage = getLabel('label-page');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
					<statistics><report name="paths" title="{$labelPath}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
					<table>
						<column field="name" title="{$labelPage}" prefix="" valueSuffix=""  />
						<column field="cnt" title="{$labelSourcesTotal}" prefix="" valueSuffix="" />
						<column field="rel" title="{$labelSourcesRelative}" prefix="" valueSuffix="%" />
					</table>
					<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
					</chart>
					<data>
END;
				foreach ($result['detail'] as $info) {
					$iAbs = $info['abs'];
					$fRel = $info['rel'];
					$iHoveredAbs += $iAbs;
					$page_uri = $info['uri'];
					$page_title = '';
					$page_id = (int) $info['id'];
					$element_id = umiHierarchy::getInstance()->getElement($page_id);

					if ($element_id) {
					} elseif ($element_id = umiHierarchy::getInstance()->getIdByPath($page_uri)) {
					} elseif ($page_uri == '/') {
						$element_id = umiHierarchy::getInstance()->getDefaultElementId();
					}

					$element = umiHierarchy::getInstance()->getElement($element_id);
					if ($element) {
						$page_title = $element->getName();
					}

					if ($page_title === '') {
						$page_title = $info['uri'];
					}

					$attr_page_title = htmlspecialchars($page_title);

					if ($page_id) {
						$attr_uri = htmlspecialchars($thisMdlUrl . 'paths/?nextpath=' . $sParamPath . '/' . $page_id);
					} else {
						$attr_uri = '';
					}

					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $iAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'rel="' . round($fRel, 1) . '" ';
					foreach ($info as $sName => $sVal) {
						if ($sName !== 'cnt' && $sName !== 'name' && $sName !== 'uri' && $sName !== 'rel') {
							$sAttrs .= $sName . '="' . htmlspecialchars($sVal, ENT_COMPAT) . '" ';
						}
					}
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$sAnswer .= "<row cnt=\"{$iRest}\" name=\"" . getLabel('label-other') . '" uri="" rel="' .
						round($iRest / ($iTotalAbs / 100), 1) . '" />';
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			}
			$params = [];
			$params['filter'] = $this->getFilterPanel();
			$params['ReportPaths']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает источники для точки входа
		 * @return string|void
		 * @throws coreException
		 */
		public function refererByEntry() {
			$page_id = (int) $_REQUEST['param0'];
			$sReturnMode = getRequest('param1');
			$cmsController = cmsController::getInstance();
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisMdlUrl = '/' . $thisLang . '/admin/stat/';
			$thisUrl = $thisMdlUrl . __FUNCTION__ . '/' . $page_id;
			$thisUrlTail = '';
			$this->module->updateFilter();

			if ($sReturnMode == 'xml') {
				$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
				/** @var refererByEntryXml|refererByEntry $report */
				$report = $factory->get('refererByEntry');
				$report->setStart($this->module->from_time);
				$report->setFinish($this->module->to_time);
				$report->setParams([
					'page_id' => $page_id
				]);
				$report->setDomain($this->module->domain);
				$report->setUser($this->module->user);
				$aRet = $report->get();
				$sAnswer = '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= '<statistics>
					<report name="entryReferers" title="' . getLabel('label-sources-for-selected-entry-point') . '"
					        host="' . $thisHost . '" lang="' . $thisLang . '" timerange_start="' .
					$this->module->from_time .
					'" timerange_finish="' . $this->module->to_time . '">
					<table>
						<column field="name"  title="' . getLabel('label-source') . '"  />
						<column field="count" title="' . getLabel('label-sources') . '"  />
					</table>
					<chart type="pie">
						<argument />
						<value field="count" />
						<caption field="name" />
					</chart>
					<data>';

				foreach ($aRet as $aRow) {
					$sName = $aRow['name'] . $aRow['uri'];
					$sURI = htmlspecialchars('http://' . $sName);
					$iCount = $aRow['count'];
					$sAnswer .= '<row name="' . $sName . '" count="' . $iCount . '" uri="' . $sURI . '" />';
				}
				$sAnswer .= '</data></report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			}
			$params = [];
			$params['filter'] = $this->getFilterPanel();
			$params['ReportRefererByEntry']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает данные для вкладки "Источники и пути" / "Точки выхода"
		 * @return string|void
		 * @throws coreException
		 */
		public function exitPoints() {
			$sReturnMode = getRequest('param0');
			$this->module->updateFilter();
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisMdlUrl = '/' . $thisLang . '/admin/stat/';
			$thisUrl = $thisMdlUrl . __FUNCTION__;
			$thisUrlTail = '';

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var exitPointsXml|exitPoints $report */
			$report = $factory->get('exitPoints');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setLimit($this->module->items_per_page);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);

			if ($sReturnMode === 'xml') {
				$result = $report->get();
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];

				$labelExitPoints = getLabel('label-exit-points');
				$labelPage = getLabel('label-page');
				$labelExitsAbs = getLabel('label-exits-abs');
				$labelExitsRel = getLabel('label-exits-rel');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
					<statistics><report name="exitPoints" title="{$labelExitPoints}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
					<table>
						<column field="name" title="{$labelPage}" prefix="" valueSuffix="" datatipField="uri"  />
						<column field="cnt" title={$labelExitsAbs}" prefix="" valueSuffix="" />
						<column field="rel" title="{$labelExitsRel}" prefix="" valueSuffix="%" />
					</table>
					<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
					</chart>
					<data>
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['abs'];
					$fRel = $info['rel'];
					$iHoveredAbs += $iAbs;
					$page_uri = $info['uri'];
					$page_title = '';
					$page_id = isset($info['id']) ? (int) $info['id'] : false;
					$element_id = umiHierarchy::getInstance()->getElement($page_id);

					if ($element_id) {
					} elseif ($element_id = umiHierarchy::getInstance()->getIdByPath($page_uri)) {
					} elseif ($page_uri == '/') {
						$element_id = umiHierarchy::getInstance()->getDefaultElementId();
					}

					$element = umiHierarchy::getInstance()->getElement($element_id);
					if ($element) {
						$page_title = $element->getName();
					}

					if ($page_title === '') {
						$page_title = $info['uri'];
					}

					$attr_page_title = htmlspecialchars($page_title);
					$attr_uri = htmlspecialchars($page_uri);

					$sAnswer .= '<row ';
					$sAttrs = '';
					$sAttrs .= 'cnt="' . $iAbs . '" ';
					$sAttrs .= 'name="' . $attr_page_title . '" ';
					$sAttrs .= 'uri="' . $attr_uri . '" ';
					$sAttrs .= 'rel="' . round($fRel, 1) . '" ';
					foreach ($info as $sName => $sVal) {
						if ($sName !== 'cnt' && $sName !== 'name' && $sName !== 'uri' && $sName !== 'rel') {
							$sAttrs .= $sName . '="' . htmlspecialchars($sVal, ENT_COMPAT) . '" ';
						}
					}
					$sAnswer .= $sAttrs;
					$sAnswer .= "/>\n";
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$sAnswer .= "<row cnt=\"{$iRest}\" name=\"" . getLabel('label-other') . '" uri="" rel="' .
						round($iRest / ($iTotalAbs / 100), 1) . '" />';
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			}
			$params = [];
			$params['filter'] = $this->getFilterPanel();
			$params['ReportExitPoints']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает данные для вкладки "OpenStat" / "Кампании"
		 * @return string|void
		 * @throws coreException
		 */
		public function openstatCampaigns() {
			$sReturnMode = getRequest('param0');
			$this->module->updateFilter();
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisUrl = '/' . $thisLang . '/admin/stat/' . __FUNCTION__;
			$thisUrlTail = '';

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var openstatCampaignsXml|openstatCampaigns $report */
			$report = $factory->get('openstatCampaigns');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$report->setLimit($this->module->items_per_page);

			if ($sReturnMode === 'xml') {
				$result = $report->get();
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];

				$labelAllAdvertisingCampaigns = getLabel('label-all-advertising-campaigns');
				$labelCampaignName = getLabel('label-campaign-name');
				$labelSourcesTotal = getLabel('label-sources-total');
				$labelSourcesRelative = getLabel('label-sources-relative');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics><report name="openstatCampaigns" title="{$labelAllAdvertisingCampaigns}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
				<table>
					<column field="name" title="{$labelCampaignName}" prefix="" valueSuffix="" />
					<column field="cnt" title="{$labelSourcesTotal}" prefix="" valueSuffix="" />
					<column field="rel" title="{$labelSourcesRelative}" prefix="" valueSuffix="%" />
				</table>
				<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
				</chart>
				<data>
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['abs'];
					$iHoveredAbs += $iAbs;
					$fRel = $info['rel'];
					$sName = $info['name'];
					$iId = $info['campaign_id'];
					$sUri = '/' . $thisLang . '/admin/stat/openstatServicesByCampaign/' . $iId;
					$attr_name = htmlspecialchars($sName);
					$attr_cnt = $iAbs;
					$attr_rel = round($fRel, 1);
					$attr_uri = htmlspecialchars($sUri);
					$sAnswer .= <<<END
						<row name="{$attr_name}" cnt="{$attr_cnt}" rel="{$attr_rel}" uri="{$attr_uri}"  />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$fRestRel = round($iRest / ($iTotalAbs / 100), 1);
					$labelOther = getLabel('label-other');
					$sAnswer .= <<<END
						<row name="{$labelOther}" cnt="{$iRest}" rel="{$fRestRel}" uri=""  />
END;
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} else {
				$params = [];
				$params['filter'] = $this->getFilterPanel();
				$params['ReportOpenstatCampaigns']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
				$this->setConfigResult($params, 'view');
			}
		}

		/**
		 * Возвращает данные для вкладки "OpenStat" / "Ресурсы"
		 * @return string|void
		 * @throws coreException
		 */
		public function openstatServices() {
			$sReturnMode = getRequest('param0');
			$this->module->updateFilter();
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisUrl = '/' . $thisLang . '/admin/stat/' . __FUNCTION__;
			$thisUrlTail = '';

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var openstatServicesXml|openstatServices $report */
			$report = $factory->get('openstatServices');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$report->setLimit($this->module->items_per_page);

			if ($sReturnMode === 'xml') {
				$result = $report->get();
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];

				$labelAllAdvertisingResources = getLabel('label-all-advertising-resources');
				$labelAdvertisingResource = getLabel('label-advertising-resource');
				$labelSourcesTotal = getLabel('label-sources-total');
				$labelSourcesRelative = getLabel('label-sources-relative');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics><report name="openstatServices" title="{$labelAllAdvertisingResources}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
				<table>
					<column field="name" title="{$labelAdvertisingResource}" prefix="" valueSuffix="" />
					<column field="cnt" title="{$labelSourcesTotal}" prefix="" valueSuffix="" />
					<column field="rel" title="{$labelSourcesRelative}" prefix="" valueSuffix="%" />
				</table>
				<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
				</chart>
				<data>
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['abs'];
					$iHoveredAbs += $iAbs;
					$fRel = $info['rel'];
					$sName = $info['name'];
					$iId = $info['service_id'];
					$sUri = '/' . $thisLang . '/admin/stat/openstatAdsByService/' . $iId;
					$attr_name = htmlspecialchars($sName);
					$attr_cnt = $iAbs;
					$attr_rel = round($fRel, 1);
					$attr_uri = htmlspecialchars($sUri);
					$sAnswer .= <<<END
						<row name="{$attr_name}" cnt="{$attr_cnt}" rel="{$attr_rel}" uri="{$attr_uri}"  />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$fRestRel = round($iRest / ($iTotalAbs / 100), 1);
					$labelOther = getLabel('label-other');
					$sAnswer .= <<<END
						<row name="{$labelOther}" cnt="{$iRest}" rel="{$fRestRel}" uri=""  />
END;
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} else {
				$params = [];
				$params['filter'] = $this->getFilterPanel();
				$params['ReportOpenstatServices']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
				$this->setConfigResult($params, 'view');
			}
		}

		/**
		 * Возвращает статистику ресурсов по рекламным кампаниям
		 * @return string|void
		 * @throws coreException
		 */
		public function openstatServicesByCampaign() {
			$sCampaignId = getRequest('param0');
			$sReturnMode = getRequest('param1');
			$this->module->updateFilter();
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisUrl = '/' . $thisLang . '/admin/stat/' . __FUNCTION__;
			$thisUrlTail = '';

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var openstatServicesXml|openstatServices $report */
			$report = $factory->get('openstatServices');
			$report->setParams([
				'campaign_id ' => (int) $sCampaignId
			]);
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$report->setLimit($this->module->items_per_page);

			if ($sReturnMode === 'xml') {
				$result = $report->get();
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];

				$labelAdvertisingCampaignResources = getLabel('label-advertising-campaign-resources');
				$labelAdvertisingResource = getLabel('label-advertising-resource');
				$labelSourcesTotal = getLabel('label-sources-total');
				$labelSourcesRelative = getLabel('label-sources-relative');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics><report name="openstatServicesByCampaign" title="{$labelAdvertisingCampaignResources}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
				<table>
					<column field="name" title="{$labelAdvertisingResource}" prefix="" valueSuffix="" />
					<column field="cnt" title="{$labelSourcesTotal}" prefix="" valueSuffix="" />
					<column field="rel" title="{$labelSourcesRelative}" prefix="" valueSuffix="%" />
				</table>
				<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
				</chart>
				<data>
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['abs'];
					$iHoveredAbs += $iAbs;
					$fRel = $info['rel'];
					$sName = $info['name'];
					$iId = $info['service_id'];
					$sUri = '/' . $thisLang . '/admin/stat/openstatAdsByService/' . $iId;
					$attr_name = htmlspecialchars($sName);
					$attr_cnt = $iAbs;
					$attr_rel = round($fRel, 1);
					$attr_uri = htmlspecialchars($sUri);
					$sAnswer .= <<<END
						<row name="{$attr_name}" cnt="{$attr_cnt}" rel="{$attr_rel}" uri="{$attr_uri}"  />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$fRestRel = round($iRest / ($iTotalAbs / 100), 1);
					$labelOther = getLabel('label-other');
					$sAnswer .= <<<END
						<row name="{$labelOther}" cnt="{$iRest}" rel="{$fRestRel}" uri=""  />
END;
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} else {
				$params = [];
				$params['filter'] = $this->getFilterPanel();
				$params['ReportOpenstatServicesByCampaig']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
				$this->setConfigResult($params, 'view');
			}
		}

		/**
		 * Возвращает статистику ресурсов по рекламным местам
		 * @return string|void
		 * @throws coreException
		 */
		public function openstatServicesBySource() {
			$sSourceId = getRequest('param0');
			$sReturnMode = getRequest('param1');
			$this->module->updateFilter();
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisUrl = '/' . $thisLang . '/admin/stat/' . __FUNCTION__;
			$thisUrlTail = '';

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var openstatServicesXml|openstatServices $report */
			$report = $factory->get('openstatServices');
			$report->setParams([
				'source_id' => (int) $sSourceId
			]);
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$report->setLimit($this->module->items_per_page);

			if ($sReturnMode === 'xml') {
				$result = $report->get();
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];

				$labelAdvertisingResourcesPlaceAds = getLabel('label-advertising-resources-place-ads');
				$labelAdvertisingResource = getLabel('label-advertising-resource');
				$labelSourcesTotal = getLabel('label-sources-total');
				$labelSourcesRelative = getLabel('label-sources-relative');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics><report name="openstatServicesBySource" title="{$labelAdvertisingResourcesPlaceAds}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
				<table>
					<column field="name" title="{$labelAdvertisingResource}" prefix="" valueSuffix="" />
					<column field="cnt" title="{$labelSourcesTotal}" prefix="" valueSuffix="" />
					<column field="rel" title="{$labelSourcesRelative}" prefix="" valueSuffix="%" />
				</table>
				<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
				</chart>
				<data>
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['abs'];
					$iHoveredAbs += $iAbs;
					$fRel = $info['rel'];
					$sName = $info['name'];
					$iId = $info['service_id'];
					$sUri = '/' . $thisLang . '/admin/stat/openstatAdsByService/' . $iId;
					$attr_name = htmlspecialchars($sName);
					$attr_cnt = $iAbs;
					$attr_rel = round($fRel, 1);
					$attr_uri = htmlspecialchars($sUri);
					$sAnswer .= <<<END
						<row name="{$attr_name}" cnt="{$attr_cnt}" rel="{$attr_rel}" uri="{$attr_uri}"  />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$fRestRel = round($iRest / ($iTotalAbs / 100), 1);
					$labelOther = getLabel('label-other');
					$sAnswer .= <<<END
						<row name="{$labelOther}" cnt="{$iRest}" rel="{$fRestRel}" uri=""  />
END;
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} else {
				$params = [];
				$params['filter'] = $this->getFilterPanel();
				$params['ReportOpenstatServicesBySource']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
				$this->setConfigResult($params, 'view');
			}
		}

		/**
		 * Возвращает данные для вкладки "OpenStat" / "Места объявлений"
		 * @return string|void
		 * @throws coreException
		 */
		public function openstatSources() {
			$sReturnMode = getRequest('param0');
			$this->module->updateFilter();
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisUrl = '/' . $thisLang . '/admin/stat/' . __FUNCTION__;
			$thisUrlTail = '';

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var openstatSourcesXml|openstatSources $report */
			$report = $factory->get('openstatSources');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$report->setLimit($this->module->items_per_page);

			if ($sReturnMode === 'xml') {
				$result = $report->get();
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];

				$labelAllAdvertisementsPlace = getLabel('label-all-advertisements-place');
				$labelPlaceForAds = getLabel('label-place-for-ads');
				$labelSourcesTotal = getLabel('label-sources-total');
				$labelSourcesRelative = getLabel('label-sources-relative');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics><report name="openstatSources" title="{$labelAllAdvertisementsPlace}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
				<table>
					<column field="name" title="{$labelPlaceForAds}" prefix="" valueSuffix="" />
					<column field="cnt" title="{$labelSourcesTotal}" prefix="" valueSuffix="" />
					<column field="rel" title="{$labelSourcesRelative}" prefix="" valueSuffix="%" />
				</table>
				<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
				</chart>
				<data>
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['abs'];
					$iHoveredAbs += $iAbs;
					$fRel = $info['rel'];
					$sName = $info['name'];
					$iId = $info['source_id'];
					$sUri = '/' . $thisLang . '/admin/stat/openstatServicesBySource/' . $iId;
					$attr_name = htmlspecialchars($sName);
					$attr_cnt = $iAbs;
					$attr_rel = round($fRel, 1);
					$attr_uri = htmlspecialchars($sUri);
					$sAnswer .= <<<END
						<row name="{$attr_name}" cnt="{$attr_cnt}" rel="{$attr_rel}" uri="{$attr_uri}"  />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$fRestRel = round($iRest / ($iTotalAbs / 100), 1);
					$labelOther = getLabel('label-other');
					$sAnswer .= <<<END
						<row name="{$labelOther}" cnt="{$iRest}" rel="{$fRestRel}" uri=""  />
END;
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} else {
				$params = [];
				$params['filter'] = $this->getFilterPanel();
				$params['ReportOpenstatSources']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
				$this->setConfigResult($params, 'view');
			}
		}

		/**
		 * Возвращает данные для вкладки "OpenStat" / "Объявления"
		 * @return string|void
		 * @throws coreException
		 */
		public function openstatAds() {
			$sReturnMode = getRequest('param0');
			$this->module->updateFilter();
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisUrl = '/' . $thisLang . '/admin/stat/' . __FUNCTION__;
			$thisUrlTail = '';

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var openstatAdsXml|openstatAds $report */
			$report = $factory->get('openstatAds');
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$report->setLimit($this->module->items_per_page);

			if ($sReturnMode === 'xml') {
				$result = $report->get();
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];

				$labelAllAdvertisements = getLabel('label-all-advertisements');
				$labelAdvertisement = getLabel('label-advertisement');
				$labelSourcesTotal = getLabel('label-sources-total');
				$labelSourcesRelative = getLabel('label-sources-relative');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics><report name="openstatAds" title="{$labelAllAdvertisements}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
				<table>
					<column field="name" title="{$labelAdvertisement}" prefix="" valueSuffix="" />
					<column field="cnt" title="{$labelSourcesTotal}" prefix="" valueSuffix="" />
					<column field="rel" title="{$labelSourcesRelative}" prefix="" valueSuffix="%" />
				</table>
				<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
				</chart>
				<data>
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['abs'];
					$iHoveredAbs += $iAbs;
					$fRel = $info['rel'];
					$sName = $info['name'];
					$sUri = '';
					$attr_name = htmlspecialchars($sName);
					$attr_cnt = $iAbs;
					$attr_rel = round($fRel, 1);
					$attr_uri = htmlspecialchars($sUri);
					$sAnswer .= <<<END
						<row name="{$attr_name}" cnt="{$attr_cnt}" rel="{$attr_rel}" uri="{$attr_uri}"  />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$fRestRel = round($iRest / ($iTotalAbs / 100), 1);
					$labelOther = getLabel('label-other');
					$sAnswer .= <<<END
						<row name="{$labelOther}" cnt="{$iRest}" rel="{$fRestRel}" uri=""  />
END;
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} else {
				$params = [];
				$params['filter'] = $this->getFilterPanel();
				$params['ReportOpenstatAds']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
				$this->setConfigResult($params, 'view');
			}
		}

		/**
		 * Возвращает статистику объявлений по ресурсам
		 * @return string|void
		 * @throws coreException
		 */
		public function openstatAdsByService() {
			$iServiceId = (int) getRequest('param0');
			$sReturnMode = getRequest('param1');
			$this->module->updateFilter();
			$thisHost = Service::DomainDetector()->detectHost();
			$thisLang = Service::LanguageDetector()->detectPrefix();
			$thisUrl = '/' . $thisLang . '/admin/stat/' . __FUNCTION__;
			$thisUrlTail = '';

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var openstatAdsXml|openstatAds $report */
			$report = $factory->get('openstatAds');
			$report->setParams([
				'service_id' => $iServiceId
			]);
			$report->setStart($this->module->from_time);
			$report->setFinish($this->module->to_time);
			$report->setDomain($this->module->domain);
			$report->setUser($this->module->user);
			$report->setLimit($this->module->items_per_page);

			if ($sReturnMode === 'xml') {
				$result = $report->get();
				$iHoveredAbs = 0;
				$iTotalAbs = $result['summ'];

				$labelAdvertisementsFromResource = getLabel('label-advertisements-from-resource');
				$labelAdvertisement = getLabel('label-advertisement');
				$labelSourcesTotal = getLabel('label-sources-total');
				$labelSourcesRelative = getLabel('label-sources-relative');

				$sAnswer = '';
				$sAnswer .= '<' . '?xml version="1.0" encoding="utf-8"?' . ">\n";
				$sAnswer .= <<<END
				<statistics><report name="openstatAdsByService" title="{$labelAdvertisementsFromResource}" host="{$thisHost}" lang="{$thisLang}"  timerange_start="{$this->module->from_time}" timerange_finish="{$this->module->to_time}">
				<table>
					<column field="name" title="{$labelAdvertisement}" prefix="" valueSuffix="" />
					<column field="cnt" title="{$labelSourcesTotal}" prefix="" valueSuffix="" />
					<column field="rel" title="{$labelSourcesRelative}" prefix="" valueSuffix="%" />
				</table>
				<chart type="pie">
						<argument />
						<value field="cnt" />
						<caption field="name" />
				</chart>
				<data>
END;
				foreach ($result['all'] as $info) {
					$iAbs = $info['abs'];
					$iHoveredAbs += $iAbs;
					$fRel = $info['rel'];
					$sName = $info['name'];
					$sUri = '';
					$attr_name = htmlspecialchars($sName);
					$attr_cnt = $iAbs;
					$attr_rel = round($fRel, 1);
					$attr_uri = htmlspecialchars($sUri);
					$sAnswer .= <<<END
						<row name="{$attr_name}" cnt="{$attr_cnt}" rel="{$attr_rel}" uri="{$attr_uri}"  />
END;
				}
				$iRest = ($iTotalAbs - $iHoveredAbs);
				if ($iRest > 0) {
					$fRestRel = round($iRest / ($iTotalAbs / 100), 1);
					$labelOther = getLabel('label-other');
					$sAnswer .= <<<END
						<row name="{$labelOther}" cnt="{$iRest}" rel="{$fRestRel}" uri=""  />
END;
				}
				$sAnswer .= "</data>\n";
				$sAnswer .= '</report></statistics>';

				$this->sendStatHeaders($sAnswer);
				$this->module->flush($sAnswer);
			} else {
				$params = [];
				$params['filter'] = $this->getFilterPanel();
				$params['ReportOpenstatAdsByService']['flash:report'] = 'url=' . $thisUrl . '/xml/' . $thisUrlTail;
				$this->setConfigResult($params, 'view');
			}
		}

		/**
		 * Возвращает настройки модуля.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то сохраняет настройки и перенаправляет на страницу
		 * настроек.
		 * @throws coreException
		 */
		public function config() {
			$regedit = Service::Registry();
			$domainList = Service::DomainCollection()->getList();

			$params = [
				'config' => [
					'boolean:enabled' => null
				]
			];
			$params['config']['boolean:enabled'] = (boolean) $regedit->get('//modules/stat/collect');

			if ($this->isSaveMode()) {
				$params = $this->expectParams($params);
				$regedit->set('//modules/stat/collect', $params['config']['boolean:enabled']);

				foreach ($domainList as $domain) {
					$domainId = $domain->getId();
					$domainName = $domain->getHost();
					$enabledForDomain = getRequest('collect-' . str_replace('.', '_', $domainName));
					$regedit->set("//modules/stat/collect/{$domainId}", $enabledForDomain);
				}

				$this->chooseRedirect();
			}

			foreach ($domainList as $domain) {
				$domainId = $domain->getId();
				$domainName = $domain->getHost();
				$enabledForDomain = $regedit->get("//modules/stat/collect/{$domainId}");

				if ($enabledForDomain !== '0') {
					$params['statDomainConfig']['boolean:collect-' . $domainName] = true;
				} else {
					$params['statDomainConfig']['boolean:collect-' . $domainName] = false;
				}
			}

			$this->setConfigResult($params);
		}

		/**
		 * Возвращает данные для формирования кнопки удаления статистики.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do, то
		 * удаляет статистику.
		 * @throws Exception
		 * @throws coreException
		 */
		public function clear() {
			if ($this->isSaveMode()) {
				$connection = ConnectionPool::getInstance()->getConnection();
				$aTables = [
					'cms_stat_domains',
					'cms_stat_entry_points',
					'cms_stat_entry_points_events',
					'cms_stat_events',
					'cms_stat_events_collected',
					'cms_stat_events_rel',
					'cms_stat_events_urls',
					'cms_stat_finders',
					'cms_stat_hits',
					'cms_stat_holidays',
					'cms_stat_pages',
					'cms_stat_paths',
					'cms_stat_phrases',
					'cms_stat_sites',
					'cms_stat_sites_groups',
					'cms_stat_sources',
					'cms_stat_sources_coupon',
					'cms_stat_sources_coupon_events',
					'cms_stat_sources_openstat',
					'cms_stat_sources_openstat_ad',
					'cms_stat_sources_openstat_campaign',
					'cms_stat_sources_openstat_service',
					'cms_stat_sources_openstat_source',
					'cms_stat_sources_pr',
					'cms_stat_sources_pr_events',
					'cms_stat_sources_pr_sites',
					'cms_stat_sources_search',
					'cms_stat_sources_search_queries',
					'cms_stat_sources_sites',
					'cms_stat_sources_sites_domains',
					'cms_stat_sources_ticket',
					'cms_stat_users'
				];

				foreach ($aTables as $sTable) {
					$connection->query('TRUNCATE `' . $sTable . '`');
				}

				$this->chooseRedirect();
			}

			$params = [
				'clear' => [
					'button:clear' => null
				]
			];

			$this->setConfigResult($params, 'view');
		}

		/** @inheritdoc */
		protected function getYandexClientId() {
			return '76077eb3b149490897efc811faad5294';
		}

		/** @inheritdoc */
		protected function getYandexSecret() {
			return '69dab0da02ca47a28f64cbbcf93ad880';
		}

		/** @inheritdoc */
		protected function getTokenRegistry() {
			return $this->module->getRegistry();
		}

		/**
		 * Выводи заголовок страницы исходя из количества дней
		 * @param int $daysNumber количество дней
		 * @return string
		 */
		private function getPageTitleByDaysNumber($daysNumber) {
			switch (true) {
				case ($daysNumber > 50) : {
					return getLabel('label-over-50');
				}
				case ($daysNumber > 40) : {
					return '41 ... 50';
				}
				case ($daysNumber > 30) : {
					return '31 ... 40';
				}
				case ($daysNumber > 20) : {
					return '21 ... 30';
				}
				case ($daysNumber > 10) : {
					return '11 ... 20';
				}
				default : {
					return (string) $daysNumber;
				}
			}
		}

		/**
		 * Возвращает данные для построения формы фильтрации для отчетов статистики
		 * @return array
		 */
		private function getFilterPanel() {
			$sCurrentDomain = $this->module->domain ?: 'all';
			$sCurrentUser = $this->module->user ?: '0';
			$iFromTime = $this->module->from_time ?: time();
			$iToTime = $this->module->to_time ?: time();
			$aDays = [];
			$aMonths = [];
			$aYears = [];

			$aMonthLetters = [
				getLabel('month-jan'),
				getLabel('month-feb'),
				getLabel('month-mar'),
				getLabel('month-apr'),
				getLabel('month-may'),
				getLabel('month-jun'),
				getLabel('month-jul'),
				getLabel('month-aug'),
				getLabel('month-sep'),
				getLabel('month-oct'),
				getLabel('month-nov'),
				getLabel('month-dec')
			];

			foreach (range(1, 31) as $i) {
				$aDays[] = [
					'attribute:id' => $i,
					'node:name' => $i
				];
			}

			foreach (range(1, 12) as $i) {
				$aMonths[] = [
					'attribute:id' => $i,
					'node:name' => $aMonthLetters[$i - 1]
				];
			}

			foreach (range((int) date('Y') - 2, (int) date('Y')) as $iYear) {
				$aYears[] = [
					'attribute:id' => $iYear,
					'node:name' => $iYear
				];
			}

			$aFP = [];
			$aFP['domain:domain'] = [
				'nodes:item' => [],
				'attribute:id' => $sCurrentDomain
			];

			$aDomainItems = &$aFP['domain:domain']['nodes:item'];

			foreach ($this->module->domainArray as $sHost => $sTitle) {
				$aDomainItems[] = [
					'attribute:id' => $sHost,
					'node:name' => $sTitle
				];
			}

			$aFP['users:user'] = [
				'attribute:id' => $sCurrentUser,
				'nodes:item' => []
			];

			$aUsersItems = &$aFP['users:user']['nodes:item'];

			foreach ($this->module->getUsersList() as $sUserId => $sUserName) {
				$aUsersItems[] = [
					'attribute:id' => $sUserId,
					'node:name' => $sUserName
				];
			}

			$aFP['period:start'] = [
				'nodes:entity' => [
					[
						'attribute:type' => 'day',
						'attribute:id' => (int) date('d', $iFromTime),
						'nodes:item' => $aDays
					],
					[
						'attribute:type' => 'month',
						'attribute:id' => (int) date('m', $iFromTime),
						'nodes:item' => $aMonths
					],
					[
						'attribute:type' => 'year',
						'attribute:id' => (int) date('Y', $iFromTime),
						'nodes:item' => $aYears
					]
				]
			];

			$aFP['period:end'] = [
				'nodes:entity' => [
					[
						'attribute:type' => 'day',
						'attribute:id' => (int) date('d', $iToTime),
						'nodes:item' => $aDays
					],
					[
						'attribute:type' => 'month',
						'attribute:id' => (int) date('m', $iToTime),
						'nodes:item' => $aMonths
					],
					[
						'attribute:type' => 'year',
						'attribute:id' => (int) date('Y', $iToTime),
						'nodes:item' => $aYears
					]
				]
			];

			return $aFP;
		}

		/**
		 * Отправляет заголовки статистики
		 * @param string $response строка ответа
		 */
		private function sendStatHeaders($response) {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/xml');
			$buffer->charset('utf-8');
			$buffer->setHeader('Content-length', (string) mb_strlen($response));
		}
	}
