<?php

	require_once CURRENT_WORKING_DIR . '/libs/config.php';

	use UmiCms\Service;
	use UmiCms\System\Auth\AuthenticationException;

	redirectIfRequired();
	tryToLoginByEnvironment();
	checkMobileApplication();
	initializeSession();
	showStubPageIfRequired();
	executeUmap();
	analyzeRequest();
	deleteRepeatedSlashesInUrl();
	redirectToUrlWithSuffix();
	handleRequest();
	updateStatistics();
	endRequest();

	/** Делает перенаправление на новый адрес, если это необходимо */
	function redirectIfRequired() {
		$config = mainConfiguration::getInstance();
		$buffer = Service::Response()->getCurrentBuffer();

		$isIndexRequest = startsWith(trim($_SERVER['REQUEST_URI'], ' /'), 'index.php');
		if ($config->get('seo', 'index-redirect') && $isIndexRequest) {
			$buffer->redirect('/');
		}

		if (isset($_GET['p']) && $_GET['p'] === '0' && !getRequest('xmlMode') && !getRequest('jsonMode')) {
			$buffer->redirect(stripPageParameterFromUrl());
		}
	}

	/**
	 * Убирает GET-параметр "p" из строки запроса и возвращает новую строку.
	 * @return string
	 */
	function stripPageParameterFromUrl() {
		$urlInfo = parse_url($_SERVER['REQUEST_URI']);
		$params = [];
		parse_str($urlInfo['query'], $params);
		unset($params['p']);

		$newUrl = $urlInfo['path'];
		if (count($params) > 0) {
			$newUrl .= '?' . http_build_query($params);
		}

		return $newUrl;
	}

	/** Пытается авторизовать пользователя на основе данных HTTP запроса и сессии */
	function tryToLoginByEnvironment() {
		try {
			Service::Auth()->loginByEnvironment();
		} catch (AuthenticationException $e) {
			$buffer = Service::Response()->getCurrentBuffer();
			$buffer->clear();
			$buffer->status('401 Unauthorized');
			$buffer->setHeader('WWW-Authenticate', 'Basic realm="UMI.CMS"');
			$buffer->push('HTTP Authenticate failed');
			$buffer->end();
		}
	}

	/** Инициализирует сессию */
	function initializeSession() {
		$session = Service::Session();
		$referer = preg_replace('/^(http(s)?:\/\/)?(www\.)?/', '', getServer('HTTP_REFERER'));
		$host = preg_replace('/^(http(s)?:\/\/)?(www\.)?/', '', getServer('HTTP_HOST'));

		if (mb_strpos($referer, $host) !== 0) {
			$session->set('http_referer', getServer('HTTP_REFERER'));
			$session->set('http_target', getServer('REQUEST_URI'));
		}

		if (!$session->get('http_target')) {
			$session->set('http_target', getServer('REQUEST_URI'));
		}
	}

	/**
	 * Показывает страницу-заглушку, если это необходимо
	 * @throws coreException
	 * @throws selectorException
	 */
	function showStubPageIfRequired() {
		$config = mainConfiguration::getInstance();
		$langId = Service::LanguageDetector()->detectId();
		$domain = Service::DomainDetector()->detect();
		$domainId = $domain->getId();

		/** @var iRegedit $registry */
		$registry = Service::Registry();

		$isUseCustomSettings = $registry->get("//umiStub/$domainId/$langId/use-custom-settings");
		$isStub = $config->get('stub', 'enabled') && !$isUseCustomSettings;
		$stubDomainList = $config->get('stub', 'enabled-for-domain');

		$isStubDomain = is_array($stubDomainList)
			? in_array($domain->getHost(), $stubDomainList) && $isUseCustomSettings
			: false;

		if (!$isStub && !$isStubDomain) {
			return;
		}

		$remoteIp = Service::Request()->remoteAddress();
		$stubSettingsFactory = Service::StubSettingsFactory();

		$selector = new selector('objects');
		$selector->types('object-type')->guid('ip-whitelist');
		$selector->option('ignore-translate', true);

		if ($isStubDomain) {
			$selector->where('domain_id')->equals($domainId);

			/** @var \UmiCms\Classes\System\Utils\Stub\Settings\Custom $customStub */
			$customStub = $stubSettingsFactory->createCustom();
			$stubFilePath = $customStub->getStubFilePath();
		} else {
			$selector->where('domain_id')->isnull();

			/** @var \UmiCms\Classes\System\Utils\Stub\Settings\Common $commonStub */
			$commonStub = $stubSettingsFactory->createCommon();
			$stubFilePath = $commonStub->getStubFilePath();
		}

		$selector->where('name')->equals($remoteIp);
		$selector->limit(0, 1);
		$selector->result();

		if ($selector->result() || isIpInConfigFilter($remoteIp)) {
			return;
		}

		$fileContent = is_file($stubFilePath) ? file_get_contents($stubFilePath) : false;

		if (!$fileContent) {
			$content = getDefaultStubContent();
		} else {
			$isShowModalWindow = (bool) $config->get('stub', 'show-modal-window');
			$content = $isShowModalWindow ? addAlertForm($fileContent) : $fileContent;
		}

		$buffer = Service::Response()
			->getCurrentBuffer();
		$buffer->contentType('text/html');
		$buffer->charset('utf-8');
		$buffer->push($content);
		$buffer->end();
	}

	/**
	 * Возвращает содержимое файла-заглушки из config.ini
	 * @return false|string
	 * @throws coreException
	 */
	function getDefaultStubContent() {
		$stubFilePath = mainConfiguration::getInstance()->includeParam('system.stub');

		if (!is_file($stubFilePath)) {
			throw new coreException("Stub file $stubFilePath not found");
		}

		return file_get_contents($stubFilePath);
	}

	/**
	 * Находится ли IP в фильтрах config.ini
	 * @param string $remoteIp
	 * @return bool
	 */
	function isIpInConfigFilter($remoteIp) {
		$filterIpList = mainConfiguration::getInstance()->get('stub', 'filter.ip');

		return is_array($filterIpList) ? in_array($remoteIp, $filterIpList) : false;
	}

	/**
	 * Добавляет форму для отключения заглушки
	 * @param string $content
	 * @return string
	 */
	function addAlertForm($content) {
		$doc = new DOMDocument;
		libxml_use_internal_errors(true);
		$doc->loadHTML('<?xml encoding="utf-8" ?>' . $content);

		$head = $doc->getElementsByTagName('head');

		if ($head->length === 0) {
			$head = $doc->createElement('head', '');

			$doc->getElementsByTagName('html')
				->item(0)
				->appendChild($head);

			$head = $doc->getElementsByTagName('head');
		}

		foreach (getScriptList() as $script) {
			$element = $doc->createElement('script', '');
			$element->setAttribute('src' , $script);
			$head->item(0)
				->appendChild($element);
		}

		$style = $doc->createElement('link', '');
		$style->setAttribute('href', getAlertCss());
		$style->setAttribute('rel','stylesheet');

		$doc->getElementsByTagName('body')
			->item(0)
			->appendChild($style);

		return $doc->saveHTML();
	}

	/**
	 * Возвращает список ссылок для скриптов для формы с входом
	 * @return array
	 */
	function getScriptList() {
		return [
			'/js/jquery/jquery.js',
			'/js/jquery/jquery-migrate.js',
			'/ulang/common.js',
			'/js/underscore-min.js',
			'/errors/stub.js'
		];
	}

	/**
	 * Возвращает ссылку на css файл для формы отключения страницы заглушки
	 * @return string
	 */
	function getAlertCss() {
		return mainConfiguration::getInstance()->get('stub', 'modal-window-css');
	}

	/**
	 * Обрабатывает редиректы по протоколу Umap
	 * @link http://dev.docs.umi-cms.ru/shablony_i_makrosy/xslt-shablonizator_umi_cms/formirovanie_dannyh_na_servere_protokol_umap/
	 */
	function executeUmap() {
		if (!mainConfiguration::getInstance()->get('kernel', 'matches-enabled')) {
			return;
		}

		try {
			$matches = new matches();
			$matches->setCurrentURI(getRequest('path'));
			$matches->execute();
		} catch (Exception $ignored) {}
	}

	/** Анализирует запрос */
	function analyzeRequest() {
		$cmsController = cmsController::getInstance();
		cmsController::doSomething();
		$cmsController->calculateRefererUri();

		/** @var umiEventPoint $eventPoint */
		$eventPoint = Service::EventPointFactory()
			->create('routing', 'before');
		$eventPoint->setParam('router', $cmsController);
		$eventPoint->call();

		/** @var iCmsController $cmsController */
		$cmsController = $eventPoint->getParam('router');

		if (!is_object($cmsController) || !is_callable([$cmsController, 'analyzePath'])) {
			trigger_error('Custom router must have analyzePath method, system running with default router.', E_USER_WARNING);
			cmsController::getInstance()
				->analyzePath();
		} else {
			$cmsController->analyzePath();
		}

		$eventPoint->setMode('after');
		$eventPoint->call();
	}

	/**
	 * Удаляет повторяющиеся слэши при необходимости
	 * @throws coreException
	 */
	function deleteRepeatedSlashesInUrl() {
		$languageId = Service::LanguageDetector()->detectId();
		$domainId = Service::DomainDetector()->detectId();
		$registry = Service::Registry();

		$uri = Service::Request()->Server()->get('REQUEST_URI');
		$pattern = '|([\/]{2,})|';
		$isRepeatedSlashes = preg_match($pattern, $uri);

		if (!$registry->get("//settings/seo/$domainId/$languageId/process-slashes") || !$isRepeatedSlashes) {
			return;
		}

		$pageStatus = $registry->get("//settings/seo/$domainId/$languageId/process-slashes-status");
		$buffer = Service::Response()->getCurrentBuffer();

		switch ($pageStatus) {
			case 'redirect': {
				$uri = preg_replace($pattern, '/', $uri);
				$buffer->redirect($uri);
				break;
			}

			case 'not-found': {
				$buffer->status('404 Not Found');
				$buffer->send();
				break;
			}
		}
	}

	/**
	 * Перенаправляет на текущий url с суффиксом, если это необходимо
	 * @throws coreException
	 */
	function redirectToUrlWithSuffix() {
		$isUrlSuffixEnable = mainConfiguration::getInstance()->get('seo', 'url-suffix.add');
		$controller = cmsController::getInstance();
		$currentElementId = $controller->getCurrentElementId();
		$isCurrentModuleContent = $controller->getCurrentModule() == 'content';
		$isCurrentMethodContent = $controller->getCurrentMethod() == 'content';

		$isNotFoundPage = ($currentElementId === false) && ($isCurrentModuleContent && $isCurrentMethodContent);

		if (!$isNotFoundPage && $isUrlSuffixEnable) {
			def_module::requireSlashEnding();
		}
	}

	/**
	 * Определяет тип запроса и подготавливает данные для ответа в буфере
	 * @throws coreException
	 * @throws ErrorException
	 * @throws coreException
	 * @throws publicException
	 */
	function handleRequest() {
		$request = Service::Request();
		$currentDomain = Service::DomainDetector()->detect();

		if ($request->host() != $currentDomain->getHost()) {
			$requestDomain = Service::DomainCollection()
				->getDomainByHost($request->host());
			handleRequestFromMirror($currentDomain, $requestDomain);
		}

		$cachedContent = getCachedContent();
		if (is_string($cachedContent)) {
			handleCachedRequest($cachedContent);
		} elseif ($request->isXml()) {
			handleXmlRequest();
		} elseif ($request->isJson()) {
			handleJsonRequest();
		} else {
			handleHtmlRequest();
		}
	}

	/**
	 * Обрабатывает запрос с зеркала домена.
	 * В зависимости от настроек:
	 * @link http://dev.docs.umi-cms.ru/nastrojka_sistemy/dostupnye_sekcii/sekciya_seo/#sel=29:1,29:3
	 *
	 * Совершает одно из следующих действий:
	 *
	 * 1) Перенаправляет с зеркала на текущий домен;
	 * 2) Прерывает выполнение скрипта, если запрошено неизвестное зеркало;
	 * 3) Добавляет неизвестное зеркало в список зеркал текущего домена;
	 * 4) Ничего не делает
	 *
	 * @param iDomain $currentDomain текущий домен
	 * @param iDomain|bool $requestDomain запрошенный домен
	 * @throws coreException
	 */
	function handleRequestFromMirror(iDomain $currentDomain, $requestDomain) {
		if (isCronCliMode()) {
			return;
		}

		$config = mainConfiguration::getInstance();
		$primaryDomainRedirect = $config->get('seo', 'primary-domain-redirect');
		$requestUnknownDomain = !$requestDomain instanceof iDomain;
		$buffer = Service::Response()
			->getCurrentBuffer();
		$request = Service::Request();

		if ($primaryDomainRedirect == 1) {
			$uri = $currentDomain->getUrl() . $request->uri();
			$buffer->redirect($uri);
		}

		if ($primaryDomainRedirect == 2 && $requestUnknownDomain) {
			$buffer->status(500);
			$buffer->push(file_get_contents(CURRENT_WORKING_DIR . '/errors/invalid_domain.html'));
			$buffer->end();
		}

		if ($primaryDomainRedirect == 3 && $requestUnknownDomain) {
			$host = $request->host();
			$currentDomain->addMirror($host);
		}
	}

	/**
	 * Возвращает закэшированный статический контент страницы
	 * @return bool|string
	 * @throws coreException
	 */
	function getCachedContent() {
		$eventPoint = new umiEventPoint('systemPrepare');
		$eventPoint->setMode('before');
		$eventPoint->call();

		$cachedContent = Service::StaticCache()->load();

		$eventPoint->setMode('after');
		$eventPoint->call();
		return $cachedContent;
	}

	/**
	 * Обрабатывает закэшированный запрос
	 * @param string $cachedContent Закэшированный ответ для запроса
	 */
	function handleCachedRequest($cachedContent) {
		$buffer = Service::Response()->getCurrentBuffer();
		$buffer->contentType('text/html');
		$buffer->charset('utf-8');
		$buffer->push($cachedContent);
	}

	/**
	 * Обрабатывает XML-запрос
	 * @throws coreException
	 */
	function handleXmlRequest() {
		$buffer = Service::Response()->getCurrentBuffer();
		$buffer->contentType('text/xml');

		$dom = new DOMDocument('1.0', 'utf-8');
		$rootNode = $dom->createElement('result');
		$dom->appendChild($rootNode);
		$rootNode->setAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');

		def_module::isXSLTResultMode(true);
		$globalVariables = cmsController::getInstance()->getGlobalVariables();
		$translator = new xmlTranslator($dom);
		$translator->translateToXml($rootNode, $globalVariables);
		$buffer->push($dom->saveXML());
		$buffer->option('generation-time', true);
	}

	/**
	 * Обрабатывает JSON-запрос
	 * @throws coreException
	 */
	function handleJsonRequest() {
		$buffer = Service::Response()->getCurrentBuffer();
		$buffer->contentType('text/javascript');

		def_module::isXSLTResultMode(true);
		$globalVariables = cmsController::getInstance()->getGlobalVariables();
		$translator = new jsonTranslator();
		$result = $translator->translateToJson($globalVariables);
		$buffer->push($result);
	}

	/**
	 * Обрабатывает HTML-запрос
	 * @throws ErrorException
	 * @throws coreException
	 * @throws publicException
	 */
	function handleHtmlRequest() {
		$cmsController = cmsController::getInstance();
		$globalVariables = $cmsController->getGlobalVariables();
		$templater = $cmsController->getCurrentTemplater();
		$request = Service::Request();

		if ($request->isStreamCallStack()) {
			$config = mainConfiguration::getInstance();
			$templater::setEnabledCallStack(!$config->get('debug', 'callstack.disabled'));
		}

		$templatesSource = $templater->getTemplatesSource();
		/** @noinspection PhpMethodParametersCountMismatchInspection (параметры берутся через func_get_args()) */
		list($commonTemplate) = $templater::getTemplates($templatesSource, 'common');

		if ($cmsController->getCurrentElementId()) {
			$templater->setScope($cmsController->getCurrentElementId());
		}

		$result = $templater->parse($globalVariables, $commonTemplate);
		if ($request->isNotAdmin()) {
			$result = $templater->cleanup($result);
		}

		$buffer = Service::Response()->getCurrentBuffer();
		$buffer->push($result);
		$buffer->option('generation-time', true);

		if ($request->isStreamCallStack()) {
			$buffer->contentType('text/xml');
			$buffer->clear();
			$buffer->push($templater->getCallStackXML());
			$buffer->end();
		}

		Service::StaticCache()->save($buffer->content());
	}

	/** Обновляет статистику посещений сайта, если включен сбор статистики */
	function updateStatistics() {
		if (Service::Request()->isAdmin()) {
			return;
		}

		$statistics = cmsController::getInstance()->getModule('stat');
		if ($statistics instanceof stat && $statistics->isEnabled()) {
			$statistics->pushStat();
		}
	}

	/** Завершает запрос и выводит результат в буфер */
	function endRequest() {
		Service::Response()->getCurrentBuffer()->end();
	}