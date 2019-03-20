<?php

	use UmiCms\Service;

	require_once CURRENT_WORKING_DIR . '/libs/config.php';

	$buffer = Service::Response()
		->getCurrentBuffer();
	$buffer->contentType('text/plain');
	$buffer->charset('utf-8');

	$cmsController = cmsController::getInstance();
	$config = mainConfiguration::getInstance();
	$domain = getDomain();

	$crawlDelay = $config->get('seo', 'crawl-delay');
	$primaryWww = (bool) $config->get('seo', 'primary-www');
	$host = $domain->getHost();
	$host = preg_replace('/^www./', '', $host);

	if ($primaryWww) {
		$host = 'www.' . $host;
	}

	$host = $domain->getProtocol() . "://{$host}";
	$customPath = CURRENT_WORKING_DIR . '/robots/' . $domain->getId() . '.robots.txt';

	$rules = getRules();

	if (file_exists($customPath)) {
		$customRobots = file_get_contents($customPath);

		if ($customRobots !== '') {
			$needleList = [
				'%disallow_umi_pages%',
				'%host%',
				'%crawl_delay%'
			];

			$replacementList = [
				$rules,
				$host,
				$crawlDelay
			];

			$customRobots = str_replace($needleList, $replacementList, $customRobots);
			$buffer->push($customRobots);
			$buffer->end();
		}
	}

	$rules = 'Disallow: /?' . PHP_EOL . $rules . PHP_EOL . PHP_EOL;
	$rules = explode(PHP_EOL, $rules);

	$sitemap = $host . '/sitemap.xml';

	$event = Service::EventPointFactory()->create('formationRobots', 'before');
	$event->addRef('rules', $rules)
		->addRef('host', $host)
		->addRef('sitemap', $sitemap)
		->addRef('crawlDelay', $crawlDelay)
		->addRef('disallowedPages', $disallowedPages)
		->call();

	$bufferContent = [
		'User-Agent: Googlebot' => $rules,
		'User-Agent: Yandex' => $rules,
		'User-Agent: *' => $rules,
		'Host: ' => $host,
		'Sitemap: ' => $sitemap,
		'Crawl-delay: ' => $crawlDelay,
	];

	$event->setMode('after')
		->addRef('bufferContent', $bufferContent)
		->call();

	foreach ($bufferContent as $key => $value) {
		$buffer->push($key);

		if (is_array($value)) {
			foreach ($value as $rule) {
				$buffer->push(PHP_EOL . $rule);
			}
		} else {
			$buffer->push($value . PHP_EOL);
		}
	}

	$buffer->end();

	/**
	 * Возвращает домен
	 * @return iDomain
	 * @throws coreException
	 */
	function getDomain() {
		return Service::DomainDetector()->detect();
	}

	/**
	 * Возвращает правила robots.txt
	 * @return string
	 * @throws selectorException
	 * @throws coreException
	 */
	function getRules() {
		if (isDisallowAll()) {
			return 'Disallow: /';
		}

		$disallowedPages = new selector('pages');
		$disallowedPages->where('robots_deny')->equals(1);
		$disallowedPages->where('lang')->isnotnull();

		$rules = '';

		foreach ($disallowedPages as $page) {
			$rules .= 'Disallow: ' . $page->link . PHP_EOL;
		}

		$rules .= <<<RULES
Disallow: /admin
Disallow: /index.php
Disallow: /emarket/addToCompare
Disallow: /emarket/basket
Disallow: /emarket/gateway
Disallow: /go-out.php
Disallow: /cron.php
Disallow: /filemonitor.php
Disallow: /search
RULES;

		return $rules;
	}

	/**
	 * Необходимо ли закрывать все страницы от поискового робота
	 * @return bool
	 * @throws coreException
	 */
	function isDisallowAll() {
		$registry = Service::Registry();
		$domainId = getDomain()->getId();
		$langId = Service::LanguageDetector()->detectId();

		$isDisallowForAllSites = (bool) $registry->get('//umiStub/robot-stub');
		$isUseDomainSettings = (bool) $registry->get("//umiStub/$domainId/$langId/use-custom-settings");
		$isDisallowForCurrentSite = (bool) $registry->get("//umiStub/$domainId/$langId/robot-stub");

		return ($isUseDomainSettings && $isDisallowForCurrentSite) || (!$isUseDomainSettings && $isDisallowForAllSites);
	}