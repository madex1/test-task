<?php

	use UmiCms\Service;

	/**
	 * Возвращает title текущей страницы
	 * @return string
	 */
	function macros_title() {
		$cmsController = cmsController::getInstance();

		if (Service::Request()->isSite()) {
			$elementId = $cmsController->getCurrentElementId();
			$element = umiHierarchy::getInstance()
				->getElement($elementId);

			if ($element instanceof iUmiHierarchyElement && $element->getValue('title')) {
				return getTitleWithPrefix($element->getValue('title'));
			}
		}

		if ($cmsController->currentTitle) {
			return getTitleWithPrefix($cmsController->currentTitle);
		}

		$langId = Service::LanguageDetector()->detectId();
		$domainId = Service::DomainDetector()->detectId();
		$defaultTitle = trim((string) Service::Registry()->get("//settings/default_title/{$langId}/{$domainId}"));
		$title = $defaultTitle ?: macros_header();

		return getTitleWithPrefix($title);
	}

	/**
	 * Добавляет в title страницы префикс или постфикс
	 * @param string $title
	 * @return string
	 */
	function getTitleWithPrefix($title) {
		$titlePrefix = getTitlePrefix();

		if (!is_string($title)) {
			return $titlePrefix;
		}

		if (contains($titlePrefix, '%title_string%')) {
			return (string) str_replace('%title_string%', $title, $titlePrefix);
		}

		return $titlePrefix !== '' ? $titlePrefix . ' ' . $title : $title;
	}

	/**
	 * Возвращает префикс для title по домену и языковой версии
	 * @param int|bool $langId id языка, если не передано - возьмет текущий
	 * @param int|bool $domainId id домена, если не передано - возьмет текущий
	 * @return string
	 */
	function getTitlePrefix($langId = false, $domainId = false) {
		$langId = $langId ?: Service::LanguageDetector()->detectId();
		$domainId = $domainId ?: Service::DomainDetector()->detectId();
		return trim((string) Service::Registry()->get('//settings/title_prefix/' . $langId . '/' . $domainId));
	}

	/** @deprecated */
	function macros_sitename() {
		/** @var ContentMacros $module */
		$module = cmsController::getInstance()->getModule('content');
		return ($module instanceof def_module) ? $module->getSiteName() : '';
	}

	function macros_header() {
		$cmsController = cmsController::getInstance();
		$hierarchy = umiHierarchy::getInstance();

		if ($cmsController->currentHeader) {
			return $cmsController->currentHeader;
		}

		$elementId = $cmsController->getCurrentElementId();

		if ($elementId) {
			$element = $hierarchy->getElement($elementId);

			if ($element) {
				return ($tmp = $element->getValue('h1')) ? $tmp : '';
			}
		}

		$currentModule = $cmsController->getCurrentModule();
		$currentMethod = $cmsController->getCurrentMethod();
		$langList = $cmsController->getLangConstantList();

		if (isset($langList[$currentModule][$currentMethod])) {
			return $langList[$currentModule][$currentMethod];
		}

		return false;
	}

	function macros_systemBuild() {
		return Service::RegistrySettings()->getRevision();
	}

	function macros_menu() {
		$cmsController = cmsController::getInstance();
		$contentModule = $cmsController->getModule('content');

		return ($contentModule instanceof def_module) ? $contentModule->menu() : '';
	}

	function macros_describtion() {
		$elementId = cmsController::getInstance()
			->getCurrentElementId();
		$element = umiHierarchy::getInstance()
			->getElement($elementId);
		$description = '';

		if ($element instanceof iUmiHierarchyElement) {
			$description = $element->getValue('meta_descriptions');
		}

		$domainId = Service::DomainDetector()->detectId();
		$langId = Service::LanguageDetector()->detectId();
		return $description ?: Service::Registry()->get('//settings/meta_description/' . $langId . '/' . $domainId);
	}

	function macros_keywords() {
		$elementId = cmsController::getInstance()
			->getCurrentElementId();
		$element = umiHierarchy::getInstance()
			->getElement($elementId);
		$keywords = '';

		if ($element instanceof iUmiHierarchyElement) {
			$keywords = $element->getValue('meta_keywords');
		}

		$domainId = Service::DomainDetector()->detectId();
		$langId = Service::LanguageDetector()->detectId();
		return $keywords ?: Service::Registry()->get('//settings/meta_keywords/' . $langId . '/' . $domainId);
	}

	function macros_returnPid() {
		return cmsController::getInstance()->getCurrentElementId();
	}

	function macros_returnPreLang() {
		return cmsController::getInstance()->getPreLang();
	}

	function macros_returnDomain() {
		return getServer('HTTP_HOST');
	}

	function macros_returnDomainFloated() {

		if (Service::Request()->isSite()) {
			return getServer('HTTP_HOST');
		}

		$arr = [];
		if (is_numeric(getRequest('param0'))) {
			$arr[] = getRequest('param0');
		}

		if (is_numeric(getRequest('param1'))) {
			$arr[] = getRequest('param1');
		}

		if (getRequest('parent')) {
			$arr[] = getRequest('parent');
		}

		$domainCollection = Service::DomainCollection();

		foreach ($arr as $c) {
			if (is_numeric($c)) {
				try {
					$element = umiHierarchy::getInstance()
						->getElement($c);

					if ($element) {
						$domain = $domainCollection->getDomain($element->getDomainId());

						if ($domain) {
							return $domain->getHost();
						}
					}
				} catch (baseException $e) {
					//Do nothing
				}
			}

			if (is_string($c)) {
				$domain_id = $domainCollection->getDomainId($c);
				$domain = $domainCollection->getDomain($domain_id);

				if ($domain) {
					return $domain->getHost();
				}
			}
		}

		return getServer('HTTP_HOST');
	}

	function macros_curr_time() {
		return time();
	}

	function macros_skin_path() {
		if (getRequest('skin_sel')) {
			return getRequest('skin_sel');
		}

		$cookieJar = Service::CookieJar();
		return $cookieJar->get('skin') ?: Service::Registry()->get('//skins');
	}

	function macros_current_user_id() {
		$auth = Service::Auth();
		return $auth->getUserId();
	}

	function macros_current_version_line() {
		if (defined('CURRENT_VERSION_LINE')) {
			return CURRENT_VERSION_LINE;
		}

		return 'pro';
	}

	function macros_catched_errors() {
		$res = '';
		foreach (baseException::$catchedExceptions as $exception) {
			$res .= '<p>' . $exception->getMessage() . '</p>';
		}
		return $res;
	}

	function macros_current_alt_name() {
		$cmsController = cmsController::getInstance();
		$element_id = $cmsController->getCurrentElementId();

		if ($element_id) {
			$element = umiHierarchy::getInstance()->getElement($element_id);

			if ($element) {
				return $element->getAltName();
			}

			return '';
		}

		return '';
	}

	function macros_returnParentId() {
		$cmsController = cmsController::getInstance();
		$element_id = $cmsController->getCurrentElementId();

		if ($element_id) {
			$element = umiHierarchy::getInstance()->getElement($element_id);

			if ($element) {
				return $element->getParentId();
			}

			return '';
		}

		return '';
	}

	/**
	 * Возвращает значение csrf-токена
	 * @return bool|mixed|null
	 */
	function macros_csrf() {
		return Service::Session()->get('csrf_token');
	}

	/**
	 * Возвращает номер текущей страницы (в рамках пагинации) по определенному формату
	 * @return string
	 */
	function macros_getPageNumber() {
		$format = getLabel('page-number-format');

		if (!is_string($format) || empty($format)) {
			return '';
		}

		$pageNumber = (int) getRequest('p');

		if ($pageNumber === 0) {
			return '';
		}

		$realPageNumber = $pageNumber + 1;

		return sprintf($format, $realPageNumber);
	}

	/** @deprecated */
	function macros_content() {
		static $res;
		if ($res !== null) {
			return $res;
		}

		$cmsController = cmsController::getInstance();

		$current_module = $cmsController->getCurrentModule();
		$current_method = $cmsController->getCurrentMethod();

		$previousValue = $cmsController->isContentMode;
		$cmsController->isContentMode = true;
		$module = $cmsController->getModule($current_module);

		if ($module) {
			$pid = $cmsController->getCurrentElementId();
			$permissions = permissionsCollection::getInstance();
			$templater = $cmsController->getCurrentTemplater();
			$isAdmin = $permissions->isAdmin();

			if ($pid) {
				$auth = Service::Auth();
				list($r, $w) = $permissions->isAllowedObject($auth->getUserId(), $pid);
				if ($r) {
					$is_element_allowed = true;
				} else {
					$is_element_allowed = false;
				}
			} else {
				$is_element_allowed = true;
			}

			if (system_is_allowed($current_module, $current_method) && $is_element_allowed) {
				$parsedContent = $cmsController->parsedContent;
				if ($parsedContent) {
					return $parsedContent;
				}

				if (Service::Request()->isAdmin()) {
					try {
						if (!$templater->getIsInited()) {
							$res = $module->cms_callMethod($current_method, null);
						}
					} catch (publicException $e) {
						$templater->setDataSet($e);
						return $res = false;
					}
				} else {
					try {
						$res = $module->cms_callMethod($current_method, null);
					} catch (publicException $e) {
						$res = $e->getMessage();
					}
					$res = system_parse_short_calls($res);
					$res = str_replace('%content%', '%content ', $res);

					$res = templater::getInstance()->parseInput($res);

					$res = system_parse_short_calls($res);
				}

				if ($res !== false && !is_array($res)) {
					if (Service::Request()->isNotAdmin() && stripos($res, '%cut%') !== false) {
						if (array_key_exists('cut', $_REQUEST)) {
							if ($_REQUEST['cut'] == 'all') {
								$_REQUEST['cut_pages'] = 0;
								return str_ireplace('%cut%', '', $res);
							}
							$cut = (int) $_REQUEST['cut'];
						} else {
							$cut = 0;
						}

						$res_arr = preg_split('%cut%i', $res);

						if ($cut > (umiCount($res_arr) - 1)) {
							$cut = umiCount($res_arr) - 1;
						}
						if ($cut < 0) {
							$cut = 0;
						}

						$_REQUEST['cut_pages'] = umiCount($res_arr);
						$_REQUEST['cut_curr_page'] = $cut;

						$res = $res_arr[$cut];
					}

					$cmsControllerInstance = $cmsController;
					$cmsControllerInstance->parsedContent = $res;
					$cmsController->isContentMode = $previousValue;
					return $res;
				}

				$cmsController->isContentMode = $previousValue;
				return $res = '<notice>%core_templater% %core_error_nullvalue%</notice>';
			}

			if (Service::Request()->isAdmin() && $isAdmin) {
				if ($current_module == 'content' && $current_method == 'sitetree') {
					$modules = Service::Registry()->getList('//modules');
					foreach ($modules as $item) {
						list($module) = $item;
						if (system_is_allowed($module) && $module != 'content') {
							$module_inst = $cmsController->getModule($module);
							$url = $module_inst->pre_lang . '/admin/' . $module . '/';
							$module_inst->redirect($url);
						}
					}
				}
			}

			$module = $cmsController->getModule('users');

			if ($module) {
				$buffer = Service::Response()
					->getCurrentBuffer();
				$buffer->status('401 Unauthorized');

				$cmsController->setCurrentModule('users');
				$cmsController->setCurrentMethod('login');
				$cmsController->isContentMode = $previousValue;

				if ($isAdmin) {
					$module = $cmsController->getModule($current_module);
					if (!$module->isMethodExists($current_method)) {
						$url = $module_inst->pre_lang . '/admin/content/sitetree/';
						$module->redirect($url);
					}
					if (Service::Request()->isAdmin()) {
						$e = new requreMoreAdminPermissionsException(getLabel('error-require-more-permissions'));
						$templater->setDataSet($e);
					}

					return $res = '<p><warning>%core_error_nopermission%</warning></p>' . $module->login();
				}

				if ($templater instanceof xslAdminTemplater && $templater->getParsed()) {
					throw new requireAdminPermissionsException('No permissions');
				}
				return $res = $module->login();
			}

			return $res = '<warning>%core_templater% %core_error_nullvalue% %core_error_nopermission%</warning>';
		}
		$cmsController->isContentMode = $previousValue;
		return $res = '%core_templater% %core_error_unknown%';
	}
