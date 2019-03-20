<?php
	class social_networks extends def_module {
		/** @var social_network|bool $current_network */
		public $current_network = false;

		protected static $templateId = NULL;

		protected function display_social_frame($network) {
			$cmsController = cmsController::getInstance();

			$path = getRequest('path');
			$path = trim($path, "/");
			$path = explode("/", $path);

			if ($cmsController->getCurrentLang()->getPrefix() == $path[0]) {
				array_shift($path);
			}

			$path = array_slice($path, 2);

			$_REQUEST['path'] = $path = '/'.implode('/',$path);

			if(!$network || !$network->isIframeEnabled()) {
				$buffer = \UmiCms\Service::Response()
					->getCurrentBuffer();
				$buffer->push("<script type='text/javascript'>parent.location.href = '".$path."';</script>");
				$buffer->end();
			}

			// find element again
			$cmsController->analyzePath(true);

			$current_element_id = $cmsController->getCurrentElementId();

			$cmsController->setUrlPrefix(''. __CLASS__ .'/'.$network->getCodeName());

			if ($cmsController->getCurrentMode() == "admin" || !$network->isHierarchyAllowed($current_element_id)) {
				$buffer = \UmiCms\Service::Response()
					->getCurrentBuffer();
				$buffer->push("<script type='text/javascript'>parent.location.href = '".$path."';</script>");
				$buffer->end();
			}

			$this->current_network = $network;

			$currentModule = $cmsController->getCurrentModule();
			$cmsController->getModule($currentModule);
			return $cmsController->getGlobalVariables(true);
		}

		/** Инициализация модуля */
		public function __construct() {
			parent::__construct();

			$this->loadCommonExtension();

			if (cmsController::getInstance()->getCurrentModule() == __CLASS__ && cmsController::getInstance()->getCurrentMode() == "admin") {
				$this->__loadLib("__admin.php");
				$this->__implement("__social_networks");

				$this->loadAdminExtension();

				$this->__loadLib("__custom_adm.php");
				$this->__implement("__social_networks_custom_admin");

				$networks = social_network::getList();

				$tabs = $this->getCommonTabs();
				foreach ($networks as $id) {
					$network = social_network::get($id);
					$tabs->add($network->getCodeName());
				}
			}

			$this->loadSiteExtension();

			$this->__loadLib("__custom.php");
			$this->__implement("__social_networks_custom");
		}

		/**
		 * (system module callback)
		 *
		 * @param string $method
		 * @return int
		 */
		public static function setupTemplate($method) {
			if (!self::$templateId) {
				$sel = new selector('objects');
				$sel->types('hierarchy-type')->name('social_networks', 'network');
				$sel->where('social_id')->equals($method);
				if (cmsController::getInstance()->getCurrentDomain()->getId()) {
					$sel->where('domain_id')->equals(cmsController::getInstance()->getCurrentDomain()->getId());
				}
				$sel->option('no-length')->value(true);
				$sel->limit(0, 1);
				$object = $sel->result();

				if(count($object) == 0 || !$object[0] instanceof umiObject) {
					$sel = new selector('objects');
					$sel->types('hierarchy-type')->name('social_networks', 'network');
					$sel->where('social_id')->equals($method);
					if (cmsController::getInstance()->getCurrentDomain()->getId()) {
						$sel->where('domain_id')->equals( domainsCollection::getInstance()->getDefaultDomain()->getId());
					}
					$sel->option('no-length')->value(true);
					$sel->limit(0, 1);
					$object = $sel->result();
				}
				if (count($object) == 0 || !$object[0] instanceof umiObject) {
					// оставлено в целях совместимости
					$config = mainConfiguration::getInstance();
					return $config->get("templates", "social_networks.{$method}"); // @deprecated
				} else {
					self::$templateId = $object[0]->getValue("template_id");
				}
			}

			return self::$templateId;
		}

		public function includeApi($network_code) {
			$network = social_network::getByCodeName($network_code);
			if (!$network) {
				return;
			}
			$sJS = '';
			if ($network->isIframeEnabled()) {
				$sJS .= '<script src="http://vkontakte.ru/js/api/xd_connection.js?2" type="text/javascript"></script>';
			}
			return $sJS;
		}

		public function getCurrentSocial() {
			return $this->current_network;
		}

		public function getCurrentSocialParams($param = '') {
			if (!$this->current_network) {
				return;
			}
			return $this->current_network->getValue($param);
		}

		/**
		 * Страница отображения соц. сети вконтакте или её настроек
		 *
		 * @return array|bool
		 */
		public function vkontakte() {
			if (cmsController::getInstance()->getCurrentMode() == "admin") {
				// BACKEND

				$defaultNetwork = social_network::getByCodeName(__FUNCTION__, domainsCollection::getInstance()->getDefaultDomain()->getId());
				if (!$defaultNetwork) {
					$defaultNetwork = social_network::getByCodeName(__FUNCTION__, NULL);
				}

				$networks = array();
				foreach (domainsCollection::getInstance()->getList() as $domain) {
					$network = social_network::getByCodeName(__FUNCTION__, $domain->getId());
					if (!$network) {
						if($defaultNetwork) {
							$objectsCollection = umiObjectsCollection::getInstance();
							$defaultNetworkId = $objectsCollection->cloneObject($defaultNetwork->getId());
							$network = $objectsCollection->getObject($defaultNetworkId);
							if(!$network->getValue("template_id")) {
								$config = mainConfiguration::getInstance();
								$network->setValue("template_id", $config->get("templates", "social_networks." . __FUNCTION__));
							}
							$network->setValue("domain_id", $domain->getId());
							$network->commit();

							$network = social_network::get($network);
						} else {
							$network = social_network::addByCodeName(__FUNCTION__, $domain->getId());
						}
					}

					$networks[] = $network;
				}

				return $this->_network_settings($networks);
			} else {
				// FRONTEND
				$domainId = cmsController::getInstance()->getCurrentDomain()->getId();
				$network = social_network::getByCodeName(__FUNCTION__, $domainId);

				// get object for default domain, if domain specific does not exist
				if (!$network) {
					$network = social_network::getByCodeName(__FUNCTION__, domainsCollection::getInstance()->getDefaultDomain()->getId());
				}

				// get object without domain (old)
				if (!$network) {
					$network = social_network::getByCodeName(__FUNCTION__, NULL);
				}

				// Social network object not found, exit
				if (!$network) {
					return false;
				}

				return $this->display_social_frame($network);
			}
		}

	};
?>
