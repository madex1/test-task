<?php
	class stat extends def_module {
		private $isStatCollected = false;

		public $domainArray = array();
		public $domain	    = "";

        public $usersArray  = null;
        public $user        = "0";

        public $mode 		= "";

		const DEF_ITEMS_PER_PAGE = 20;
		const DEF_PER_PAGE = 20;

		public function __construct() {
			parent::__construct();
			$this->enabled = regedit::getInstance()->getVal("//modules/stat/collect");
			if ($this->enabled) {
				$domain = cmsController::getInstance()->getCurrentDomain();
				$domainId = $domain->getId();
				$enabledForDomain = regedit::getInstance()->getVal("//modules/stat/collect/{$domainId}");
				if ($enabledForDomain === "0") {
					$this->enabled = false;	
				}
			}

			$this->loadCommonExtension();

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$this->__loadLib("__admin.php");
				$this->__implement("__stat_admin");

				$this->__loadLib("__popular.php");
				$this->__implement("__popular_stat");

				$this->__loadLib("__visitors.php");
				$this->__implement("__visitors_stat");

				$this->__loadLib("__sources.php");
				$this->__implement("__sources_stat");

				$this->__loadLib("__phrases.php");
				$this->__implement("__phrases_stat");

				$this->__loadLib("__seo.php");
				$this->__implement("__seo_stat");

				$this->__loadLib("__admin_tags_cloud.php");
				$this->__implement("__admin_tags_cloud_stat");

				// =============================================
				$this->__loadLib("__visits.php");
				$this->__implement("__stat_visits");

				$this->__loadLib("__sections.php");
				$this->__implement("__stat_sections");

				$this->__loadLib("__auditory.php");
				$this->__implement("__stat_auditory");

				$this->__loadLib("__openstat.php");
				$this->__implement("__stat_openstat");

				$this->__loadLib("__paths.php");
				$this->__implement("__stat_paths");
				// =============================================
				// =============================================
				$this->items_per_page = regedit::getInstance()->getVal("//modules/stat/items_per_page");
				$this->items_per_page = self::DEF_ITEMS_PER_PAGE;
				$this->per_page = self::DEF_PER_PAGE;
				// Creating tabs
				$commonTabs = $this->getCommonTabs();
				if($commonTabs) {
					$commonTabs->add('yandexMetric');
					$commonTabs->add("total", array("tag"));
					$commonTabs->add("popular_pages", array("sectionHits"));
					$commonTabs->add("visits", array(
						"visits_sessions",
						"visits_visitors",
						"auditoryActivity",
						"auditoryLoyality",
						"auditoryLocation",
						"visitDeep",
						"visitTime"
					));
					$commonTabs->add("sources", array(
						"engines",
						"phrases",
						"entryPoints",
						"exitPoints"
					));
					$commonTabs->add("openstatCampaigns", array(
						"openstatServices",
						"openstatSources",
						"openstatAds"
					));
				}

				$configTabs = $this->getConfigTabs();
				if ($configTabs) {
					$configTabs->add("config");
				}

				$this->loadAdminExtension();

				$this->__loadLib("__custom_adm.php");
				$this->__implement("__stat_custom_admin");
			} else {
				if(!$this->enabled) {
					return;
				}

				$this->__loadLib("__tags_cloud.php");
				$this->__implement("__tags_cloud_stat");

				$this->__loadLib("__json.php");
				$this->__implement("__json_stat");
			}

			$this->loadSiteExtension();

			$this->__loadLib("__custom.php");
			$this->__implement("__stat_custom");

			$this->ts        = time();
			$this->from_time = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
			$this->to_time   = strtotime('+1 day', $this->from_time);
			$this->domain    = "all";

			$this->domainArray = array();
			$domList     = domainsCollection::getInstance()->getList();
			foreach($domList as $Domain) {
                $sHostString = $Domain->getHost();
                $this->domainArray[$sHostString] = $sHostString;
            }
			$this->domainArray["all"] = getLabel('all');
			require_once dirname(__FILE__) . '/classes/simpleStat.php';
			require_once dirname(__FILE__) . '/classes/statistic.php';
			require_once dirname(__FILE__) . '/classes/statisticFactory.php';
			require_once dirname(__FILE__) . '/classes/xml/xmlDecorator.php';
			require_once dirname(__FILE__) . '/classes/openstat.php';

			$this->mode = cmsController::getInstance()->getCurrentMode();
		}


		public function __destruct() {
			if($this->mode == "" && !$this->isStatCollected) {
				$this->pushStat();
			}
		}


		public function remove_to_temp() {
			$regedit = regedit::getInstance();
			$max_days = $regedit->getVal("//modules/stat/delete_after");

			$max_secs = $max_days * 3600 * 24;
			$time_from = time() - $max_secs;

			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->query("INSERT INTO cms_stat_old SELECT * FROM cms_stat WHERE entrytime < " . $time_from);
			$connection->query("DELETE FROM cms_stat WHERE entrytime < " . $time_from);
		}


		public function pushStat() {
			$session = \UmiCms\Service::Session();

			if (!$session->isExist('old_logged_in_value')) {
				$session->set('old_logged_in_value', false);
			}

			if(!$this->enabled || $this->isStatCollected) {
				return false;
			}
			if(defined("STAT_DISABLE")) {
				if(STAT_DISABLE) {
					return false;
				}
			}

			$this->isStatCollected = true;

			$element_id = cmsController::getInstance()->getCurrentElementId();
			if($element = umiHierarchy::getInstance()->getElement($element_id)) {
				$tags = $element->getValue("tags");
			} else {
				return false;
			}

			$stat = new statistic();

			$stat->setReferer(getServer('HTTP_REFERER'));
			$stat->setUri(getServer('REQUEST_URI'));
			$stat->setServerName((getServer('HTTP_HOST'))?getServer('HTTP_HOST'):getServer('SERVER_NAME'));
			$stat->setRemoteAddr(getServer('REMOTE_ADDR'));

			$umiPermissions = permissionsCollection::getInstance();
			$isAuth = $umiPermissions->is_auth();

			if ($isAuth != $session->get('old_logged_in_value')) {
				$stat->doLogin();
			}

			$session->set('old_logged_in_value', $isAuth);

			if(is_array($tags)) {
				foreach($tags as $tag) {
					$stat->event($tag);
				}
			}
			$stat->run();
		}

		public function isStringCP1251($str) {
			$sz = strlen($str);

			for($i = 0; $i < $sz; $i++) {
				$o = ord(substr($str, $i, 1));
				if((!($o >= 32 && $o <= 122)) && !($o >= 192 && $o <= 255)) {
					return false;
				}
			}
			return true;
		}


		public function getCurrentUserTags() {
			if (!$this->enabled) {
				return;
			}

			$session = \UmiCms\Service::Session();
			$statData = $session->get('stat');
			$statData = (is_array($statData)) ? $statData : [];

			if (isset($statData['user_id'])) {
				$stat_user_id = $statData['user_id'];
			} else {
				return false;
			}

			$factory = new statisticFactory(dirname(__FILE__) . '/classes');
			/** @var fastUserTagsXml|fastUserTags $report */
			$report = $factory->get('fastUserTags');
			$report->setParams(Array("user_id" => $stat_user_id));
			$user_info = $report->get();

			return $user_info['labels'];

		}
	};

?>