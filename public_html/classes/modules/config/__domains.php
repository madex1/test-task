<?php
	abstract class __domains_config extends baseModuleAdmin {

		public function domains() {
			$mode = getRequest("param0");

			if($mode == "do") {
				if (!isDemoMode()) {
					$this->saveEditedList("domains");
				}
				$this->chooseRedirect($this->pre_lang . '/admin/config/domains/');
			}

			$domains = domainsCollection::getInstance()->getList();

			$this->setDataType("list");
			$this->setActionType("modify");

			$data = $this->prepareData($domains, "domains");

			$this->setData($data, count($domains));
			return $this->doData();
		}

		public function domain_mirrows() {
			$domainId = getRequest('param0');
			$mode = getRequest("param1");
			$regedit = regedit::getInstance();
			$langId = cmsController::getInstance()->getCurrentLang()->getId();

			$seoInfo = [];
			$additionalInfo = [];
			$seoInfo['string:seo-title'] = $regedit->getVal("//settings/title_prefix/{$langId}/{$domainId}");
			$seoInfo['string:seo-default-title'] = $regedit->getVal("//settings/default_title/{$langId}/{$domainId}");
			$seoInfo['string:seo-keywords'] = $regedit->getVal("//settings/meta_keywords/{$langId}/{$domainId}");
			$seoInfo['string:seo-description'] = $regedit->getVal("//settings/meta_description/{$langId}/{$domainId}");
			$seoInfo['string:ga-id'] = $regedit->getVal("//settings/ga-id/{$domainId}");
			$additionalInfo['string:site_name'] = $regedit->getVal("//settings/site_name/{$domainId}/{$langId}/") ?
				$regedit->getVal("//settings/site_name/{$domainId}/{$langId}") : $regedit->getVal("//settings/site_name");

			$params = [
				'seo' => $seoInfo,
				'additional' => $additionalInfo,
			];

			if ($mode == "do") {
				if (!isDemoMode()) {
					$this->saveEditedList("domain_mirrows");
					$params = $this->expectParams($params);

					$title = $params['seo']['string:seo-title'];
					$defaultTitle = $params['seo']['string:seo-default-title'];
					$keywords = $params['seo']['string:seo-keywords'];
					$description = $params['seo']['string:seo-description'];
					$gaId = $params['seo']['string:ga-id'];
					$siteName = $params['additional']['string:site_name'];

					$regedit->setVal("//settings/title_prefix/{$langId}/{$domainId}", $title);
					$regedit->setVal("//settings/default_title/{$langId}/{$domainId}", $defaultTitle);
					$regedit->setVal("//settings/meta_keywords/{$langId}/{$domainId}", $keywords);
					$regedit->setVal("//settings/meta_description/{$langId}/{$domainId}", $description);
					$regedit->setVal("//settings/ga-id/{$domainId}", $gaId);
					$regedit->setVal("//settings/site_name/{$domainId}/{$langId}", $siteName);
				}

				$this->chooseRedirect($this->pre_lang . '/admin/config/domain_mirrows/' . $domainId . '/');
			}

			$domains = domainsCollection::getInstance()->getDomain($domainId);
			$mirrors = $domains->getMirrorsList();

			$this->setDataType("settings");
			$this->setActionType("modify");
			$seoData = $this->prepareData($params, 'settings');
			$mirrorsData = $this->prepareData($mirrors, "domain_mirrows");
			$data = $seoData + $mirrorsData;
			$this->setData($data, count($domains));
			$this->doData();
		}


		public function domain_mirrow_del() {
			$domain_id = (int) getRequest('param0');
			$domain_mirror_id = (int) getRequest('param1');

			if(!isDemoMode())  {
				$domain = domainsCollection::getInstance()->getDomain($domain_id);
				$domain->delMirror($domain_mirror_id);
				$domain->commit();
			}

			$this->chooseRedirect($this->pre_lang . "/admin/config/domain_mirrows/{$domain_id}/");
		}

		public function update_sitemap() {

			$domainId = (int) getRequest('param0');
			$domain = domainsCollection::getInstance()->getDomain($domainId);

			$complete = false;
			$elements = array();

			$hierarchy = umiHierarchy::getInstance();

			$dirName = SYS_TEMP_PATH . "/sitemap/{$domainId}/";
			if (!is_dir($dirName)) mkdir($dirName, 0777, true);

			$filePath = $dirName . "domain";
			$updater = \UmiCms\Service::SiteMapUpdater();

			if(!file_exists($filePath)) {
				$updater->deleteByDomain($domainId);
				$elements = array();
				$langsCollection = langsCollection::getInstance();
				$langs = $langsCollection->getList();
				foreach($langs as $lang) {
					$elements = array_merge($elements, $hierarchy->getChildrenList(0, false, true, false, $domainId, false, $lang->getId()));
				}
				sort($elements);
				file_put_contents($filePath, serialize($elements));
			}

			$progressKey = "sitemap_offset_" . $domainId;
			$session = \UmiCms\Service::Session();
			$offset = (int) $session->get($progressKey);
			$blockSize = mainConfiguration::getInstance()->get("modules", "exchange.splitter.limit") ? mainConfiguration::getInstance()->get("modules", "exchange.splitter.limit") : 25;

			$elements = unserialize(file_get_contents($filePath));

			for ($i = $offset; $i <= $offset + $blockSize -1; $i++) {
				if(!array_key_exists($i, $elements)) {
					$complete = true;
					break;
				}
				$element = $hierarchy->getElement($elements[$i], true, true);
				if ($element instanceof umiHierarchyElement) {
					$updater->update($element);
				}
			}

			$progressValue = $offset + $blockSize;
			$session = \UmiCms\Service::Session();
			$session->set($progressKey, $progressValue);

			if ($complete) {
				$session->del($progressKey);
				unlink($filePath);
			}

			$data = array(
				"attribute:complete" => (int) $complete
			);

			$this->setData($data);
			return $this->doData();

		}


		public function domain_del() {
			$domain_id = (int) getRequest('param0');

			if ($domain_id == domainsCollection::getInstance()->getDefaultDomain()->getId()) {
				throw new publicAdminException(getLabel("error-can-not-delete-default-domain"));
			}

			if (!isDemoMode()) {
				domainsCollection::getInstance()->delDomain($domain_id);
			}

			$this->chooseRedirect($this->pre_lang . '/admin/config/domains/');
		}
	};
?>
