<?php
	class seo extends def_module {
		public function __construct() {
			parent::__construct();

			$this->loadCommonExtension();

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$this->__loadLib("__admin.php");
				$this->__implement("__seo");

				$this->__loadLib("__yandex.php");
				$this->__implement("__yandex_webmaster");


				$configTabs = $this->getConfigTabs();
				if ($configTabs) {
					$configTabs->add("config");
					$configTabs->add("megaindex");
					$configTabs->add("yandex");
				}

				$commonTabs = $this->getCommonTabs();
				if($commonTabs) {
					$commonTabs->add('seo');
					$commonTabs->add('links');
					$commonTabs->add('webmaster');
				}

				$this->loadAdminExtension();

				$this->__loadLib("__custom_adm.php");
				$this->__implement("__seo_custom_admin");
			}

			$this->loadSiteExtension();

			$this->__loadLib("__custom.php");
			$this->__implement("__custom_seo");
		}
	};
?>