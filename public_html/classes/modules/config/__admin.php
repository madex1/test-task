<?php

	use UmiCms\Service;

	abstract class __config extends baseModuleAdmin {
		/** Показывает страницу "Основные настройки" или сохраняет данные */
		public function main() {
			$regedit = regedit::getInstance();
			$config = mainConfiguration::getInstance();

			include_once('timezones.php');
			$timezones['value'] = $config->get("system", "time-zone");

			$modules = Array();
			foreach($regedit->getList("//modules") as $module) {
				list($module) = $module;
				$modules[$module] = getLabel('module-' . $module);
			}

			if ($regedit->getVal("//modules/events/") && !$regedit->getVal("//settings/default_module_admin_changed")) {
				$modules['value'] = 'events';
			} else {
				$modules['value'] = $regedit->getVal("//settings/default_module_admin");
			}

			$params = array(
				"globals" => array(
					"string:keycode" => NULL,
					"boolean:disable_url_autocorrection" => NULL,
					"boolean:disable_captcha" => NULL,
					"int:max_img_filesize" => NULL,
					"status:upload_max_filesize" => NULL,
					"boolean:allow-alt-name-with-module-collision" => NULL,
					"int:session_lifetime" => NULL,
					"status:busy_quota_files_and_images" => NULL,
					"int:quota_files_and_images" => NULL,
					"status:busy_quota_uploads" => NULL,
					"int:quota_uploads" => NULL,
					"boolean:disable_too_many_childs_notification" => NULL,
					'select:timezones' => NULL,
					'select:modules' => NULL
				)
			);

			$maxUploadFileSize = cmsController::getInstance()->getModule('data')->getAllowedMaxFileSize();

			$mode = getRequest("param0");

			if($mode == "do") {
				$params = $this->expectParams($params);

				Service::RegistrySettings()->set('keycode', $params['globals']['string:keycode']);
				$regedit->setVar("//settings/disable_url_autocorrection", $params['globals']['boolean:disable_url_autocorrection']);
				$config->set('anti-spam', 'captcha.enabled', !$params['globals']['boolean:disable_captcha']);

				$maxImgFileSize = $params['globals']['int:max_img_filesize'];
				if ($maxUploadFileSize != -1 && ($maxImgFileSize <= 0 || $maxImgFileSize > $maxUploadFileSize)) {
					$maxImgFileSize = $maxUploadFileSize;
				}
				$regedit->setVar("//settings/max_img_filesize", $maxImgFileSize);

				$config->set('kernel', 'ignore-module-names-overwrite', $params['globals']['boolean:allow-alt-name-with-module-collision']);
				$config->set("session", "active-lifetime", $params['globals']['int:session_lifetime']);

				$quota = (int) $params['globals']['int:quota_files_and_images'];
				if ($quota < 0) {
					$quota = 0;
				}
				$config->set("system", "quota-files-and-images", $quota * 1024 * 1024);

				$quotaUploads = (int) $params['globals']['int:quota_uploads'];
				if ($quotaUploads < 0) {
					$quotaUploads = 0;
				}
				$config->set("system", "quota-uploads", $quotaUploads * 1024 * 1024);

				$config->set("system", "disable-too-many-childs-notification", $params['globals']['boolean:disable_too_many_childs_notification']);
				$config->set("system", "time-zone", $params['globals']['select:timezones']);
				$config->save();
				$regedit->setVar("//settings/default_module_admin", $params['globals']['select:modules']);
				$regedit->setVar("//settings/default_module_admin_changed", 1);
				$this->chooseRedirect();
			}

			$params['globals']['string:keycode'] = Service::RegistrySettings()->getLicense();
			$params['globals']['boolean:disable_url_autocorrection'] = $regedit->getVal("//settings/disable_url_autocorrection");
			$params['globals']['boolean:disable_captcha'] = !$config->get('anti-spam', 'captcha.enabled');
			$params['globals']['status:upload_max_filesize'] = $maxUploadFileSize;

			$maxImgFileSize = $regedit->getVal("//settings/max_img_filesize");

			$params['globals']['int:max_img_filesize'] = $maxImgFileSize ? $maxImgFileSize : $maxUploadFileSize;
			$params['globals']['boolean:allow-alt-name-with-module-collision'] = $config->get('kernel', 'ignore-module-names-overwrite');

			$quotaByte = getBytesFromString( mainConfiguration::getInstance()->get('system', 'quota-files-and-images') );
			$params['globals']['status:busy_quota_files_and_images'] = ceil(getBusyDiskSize(getResourcesDirs()) / (1024*1024));
			if ( $quotaByte > 0 ) {
				$params['globals']['status:busy_quota_files_and_images'] .=" ( ".getBusyDiskPercent()."% )";
			}

			$params['globals']['int:quota_files_and_images'] = (int) (getBytesFromString($config->get('system', 'quota-files-and-images')) / (1024*1024));

			$quotaUploadsBytes = getBytesFromString(mainConfiguration::getInstance()->get('system', 'quota-uploads'));
			$params['globals']['status:busy_quota_uploads'] = ceil(getBusyDiskSize(getUploadsDir()) / (1024*1024));
			if ( $quotaUploadsBytes > 0 ) {
				$params['globals']['status:busy_quota_uploads'] .=" ( ".getOccupiedDiskPercent(getUploadsDir(), $quotaUploadsBytes)."% )";
			}

			$params['globals']['int:quota_uploads'] = (int) (getBytesFromString($config->get('system', 'quota-uploads')) / (1024*1024));

			$params['globals']['int:session_lifetime'] = $config->get('session', 'active-lifetime');
			$params['globals']['boolean:disable_too_many_childs_notification'] = $config->get('system', 'disable-too-many-childs-notification');
			$params['globals']['select:timezones'] = $timezones;
			$params['globals']['select:modules'] = $modules;

			$this->setDataType("settings");
			$this->setActionType("modify");

			if(isDemoMode()) {
				unset($params["globals"]['string:keycode']);
			}

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			$this->doData();
		}


		public function menu() {
			$block_arr = Array();
			$regedit = regedit::getInstance();

			$modules = $this->getSortedModulesList();

			$result = array();
			foreach ( $modules as $moduleName => $moduleInfo ) {
				$moduleConfig = $regedit->getVal("//modules/{$moduleName}/config");
				$currentModule = cmsController::getInstance()->getCurrentModule();
				$currentMethod = cmsController::getInstance()->getCurrentMethod();

				$line_arr = Array();
				$line_arr['attribute:name'] = $moduleInfo['name'];
				$line_arr['attribute:label'] = $moduleInfo['label'];
				$line_arr['attribute:type']= $moduleInfo['type'];

				if($currentModule == $moduleName && !($currentMethod == 'mainpage')) {
					$line_arr['attribute:active'] = "active";
				}

				if($moduleConfig && system_is_allowed( $currentModule, "config" )) {
					$line_arr['attribute:config'] = "config";
				}

				$result[] = $line_arr;
			}

			$block_arr['items'] = Array("nodes:item" =>$result);

			return $block_arr;
		}


		public function modules() {
			$modules = Array();
			$regedit = regedit::getInstance();
			$modules_list = $regedit->getList("//modules");

			foreach($modules_list as $module_name) {
				list($module_name) = $module_name;

				$modules[] = $module_name;
			}


			$this->setDataType("list");
			$this->setActionType("view");

			$data = $this->prepareData($modules, "modules");

			$this->setData($data);
			$this->doData();
		}


		public function add_module_do() {
			$cmsController = cmsController::getInstance();

			$modulePath = getRequest('module_path');

			$moduleName = '';
			if(preg_match("/\/modules\/(\S+)\//", $modulePath, $out)) {
				$moduleName = getArrayKey($out, 1);
			}

			if (!preg_match("/.\.php$/", $modulePath )){
				$modulePath .= "/install.php";
			}

			if(!isDemoMode()) {
				$cmsController->installModule($modulePath);
			}

			$this->chooseRedirect($this->pre_lang .	"/admin/config/modules/");
		}


		public function del_module() {
			$restrictedModules = array('config', 'content', 'users', 'data');

			$target	= getRequest('param0');

			if(in_array($target, $restrictedModules))	{
				throw new publicAdminException(getLabel("error-can-not-delete-{$target}-module"));
			}

			$module	= cmsController::getInstance()->getModule($target);

			if(!isDemoMode()) {
				if($module instanceof def_module) {
					$module->uninstall();
				}
				if($target == 'geoip') {
					self::switchGroupsActivity('city_targeting', false);
				}
			}

			$this->chooseRedirect($this->pre_lang . "/admin/config/modules/");
		}

		// for testing  generation time
		public function speedtest() {
			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$calltime = $buffer->calltime();
			$buffer->push($calltime);
			$buffer->end();
		}

		/**
		 * Возвращает содержимое вкладки "phpInfo"
		 * @throws Exception
		 * @throws coreException
		 */
		public function phpInfo() {
			$systemInfo = UmiCms\Service::SystemInfo();
			$phpInfo = $systemInfo->getInfo(iSystemInfo::PHP_INFO);
			$phpInfo = array_shift($phpInfo);
			$phpInfo = preg_replace("/^.*?\<body\>/is", "", $phpInfo);
			$phpInfo = preg_replace("/<\/body\>.*?$/is", "", $phpInfo);

			$data = [
				'info' => $phpInfo
			];

			$version = $systemInfo->getInfo(iSystemInfo::PHP)['php']['version'];

			if (version_compare($version, '7.0.0', '<')) {
				$data['alert'] = getLabel('label-php-5-alert', 'config', $version);
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$this->setData(['data' => $data]);
			$this->doData();
		}
	}