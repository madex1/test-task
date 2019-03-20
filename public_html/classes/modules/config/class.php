<?php
	class config extends def_module {
		public function __construct() {
			parent::__construct();

			$cmsController = cmsController::getInstance();
			$config = mainConfiguration::getInstance();

			$this->loadCommonExtension();

			if($cmsController->getCurrentMode() == 'admin') {
				$commonTabs = $this->getCommonTabs();
				if($commonTabs) {
					$commonTabs->add('main');
					$commonTabs->add('modules');
					$commonTabs->add('langs');
					$commonTabs->add('domains', array('domain_mirrows'));
					$commonTabs->add('mails');
					$commonTabs->add('cache');
					$commonTabs->add('security');
					$commonTabs->add('phpInfo');
					$commonTabs->add('watermark');
				}

				$this->__loadLib("__admin.php");
				$this->__implement("__config");

				$this->__loadLib("__cache.php");
				$this->__implement("__cache_config");

				$this->__loadLib("__security.php");
				$this->__implement("__security_config");

				$this->__loadLib("__mails.php");
				$this->__implement("__mails_config");

				$this->__loadLib("__langs.php");
				$this->__implement("__langs_config");

				$this->__loadLib("__watermark.php");
				$this->__implement("__watermark_config");

				$this->__loadLib("__domains.php");
				$this->__implement("__domains_config");

				$this->loadAdminExtension();

				$this->__loadLib("__custom_adm.php");
				$this->__implement("__config_custom_admin");
			}

			if($config->get('messages', 'catch-system-events')) {
				$this->__loadLib("__mess_events.php");
				$this->__implement("__events_config");
			}

			$this->loadSiteExtension();

			$this->__loadLib("__custom.php");
			$this->__implement("__config_custom");

			$this->__loadLib("__events.php");
			$this->__implement("__custom_events_config");
		}
	};
?>