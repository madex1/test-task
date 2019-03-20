<?php

	use UmiCms\Service;

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class ContentMacros {

		/** @var content|ContentMacros $module */
		public $module;

		/**
		 * Генерирует и возвращает карту сайта
		 * @param string $template имя шаблона (для tpl шаблонизатора)
		 * @param bool|int $max_depth уровень вложенности
		 * @param bool|int $root_id идентификатор корневой страницы
		 * @return mixed
		 */
		public function sitemap($template = 'default', $max_depth = false, $root_id = false) {
			/** @var content|ContentMacros $this */
			$hierarchy = umiHierarchy::getInstance();
			$cmsController = cmsController::getInstance();

			if (!$max_depth) {
				$max_depth = getRequest('param0');
			}
			if (!$max_depth) {
				$max_depth = 4;
			}

			if (!$root_id) {
				$root_id = (int) getRequest('param1');
			}
			if (!$root_id) {
				$root_id = 0;
			}

			if ($cmsController->getCurrentMethod() == 'sitemap') {
				$this->module->setHeader('%content_sitemap%');
			}

			$site_tree = $hierarchy->getChildrenTree($root_id, false, false, $max_depth - 1);
			return $this->gen_sitemap($template, $site_tree, $max_depth - 1);
		}

		/**
		 * Возвращает контент страницы.
		 * Если не передан идентификатор - возьмет текущую страницу.
		 * @param bool|int $elementId идентификатор страницы
		 * @return mixed
		 */
		public function content($elementId = false) {
			$cmsController = cmsController::getInstance();
			if (!$elementId) {
				$elementId = $cmsController->getCurrentElementId();
			}

			$hierarchy = umiHierarchy::getInstance();
			$element = $hierarchy->getElement($elementId);

			if ($element instanceof iUmiHierarchyElement) {
				$this->module->pushEditable('content', '', $elementId);
				return $element->getValue('content');
			}

			return $this->gen404();
		}

		/**
		 * Устанавливает статус 404 и возвращает содержимое
		 * отсутствующей страницы
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @return mixed
		 * @throws coreException
		 */
		public function gen404($template = 'default') {
			if (!$template) {
				$template = 'default';
			}

			Service::Response()
				->getCurrentBuffer()
				->status('404 Not Found');

			$this->module->setHeader('%content_error_404_header%');

			list($tpl_block) = content::loadTemplates('content/not_found/' . $template, 'block');
			$template = $tpl_block ?: '%content_usesitemap%';

			return content::parseTemplate($template, []);
		}

		/**
		 * Выводит список элементов типа "Страница контента"
		 * @param string $template имя шаблона (для TPL-шаблонизатора)
		 * @param int|string $path ID элемента или его адрес
		 * @param int $maxDepth максимальная глубина вложенности иерархии поиска элементов (во вложенных подразделах)
		 * @param int $perPage количество элементов на странице (при постраничной навигации)
		 * @param bool $ignorePaging игнорировать постраничную навигацию
		 * @param string $sortField имя поля, по которому нужно произвести сортировку элементов
		 * @param string $sortDirection направление сортировки ('asc' или 'desc')
		 * @return array
		 * @throws publicException
		 * @throws selectorException
		 */
		public function getList(
			$template = 'default',
			$path = 0,
			$maxDepth = 1,
			$perPage = 0,
			$ignorePaging = false,
			$sortField = '',
			$sortDirection = 'asc'
		) {

			$elements = new selector('pages');
			$elements->types('hierarchy-type')->name('content', 'page');

			$parentId = $this->module->analyzeRequiredPath($path);

			if (!$parentId && $parentId !== 0 && $path !== KEYWORD_GRAB_ALL) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $path));
			}

			if ($path !== KEYWORD_GRAB_ALL) {
				$maxDepthNum = (int) $maxDepth > 0 ? (int) $maxDepth : 1;
				$elements->where('hierarchy')->page($parentId)->childs($maxDepthNum);
			}

			$perPageNumber = (int) $perPage;
			$limit = $perPageNumber > 0 ? $perPageNumber : $this->module->perPage;

			if (!$ignorePaging) {
				$currentPage = (int) getRequest('p');
				$offset = $currentPage * $limit;
				$elements->limit($offset, $limit);
			}

			if ($sortField) {
				$direction = 'asc';
				if (in_array($sortDirection, ['asc', 'desc', 'rand'])) {
					$direction = $sortDirection;
				}

				try {
					$elements->order($sortField)->$direction();
				} catch (selectorException $e) {
					throw new publicException(getLabel('error-prop-not-found', null, $sortField));
				}
			}

			selectorHelper::detectFilters($elements);

			$elements->option('load-all-props')->value(true);
			$elements->option('exclude-nested', false);

			$result = $elements->result();

			list($templateBlock, $templateBlockEmpty, $templateItem) =
				content::loadTemplates('content/' . $template, 'get_list_block', 'get_list_block_empty', 'get_list_item');

			$total = $elements->length();

			$data = [
				'items' => [
					'nodes:item' => null
				],
				'total' => $total,
				'per_page' => $limit,
				'parent_id' => $parentId
			];

			if ($total === 0) {
				return content::parseTemplate($templateBlockEmpty, $data, $parentId);
			}

			$linksHelper = umiLinksHelper::getInstance();
			$umiHierarchy = umiHierarchy::getInstance();

			$items = [];
			/** @var iUmiHierarchyElement $page */
			foreach ($result as $page) {
				if (!$page instanceof iUmiHierarchyElement) {
					continue;
				}

				$itemData = [];

				$itemData['@id'] = $page->getId();
				$itemData['name'] = $page->getName();
				$itemData['@link'] = $linksHelper->getLinkByParts($page);
				$itemData['@xlink:href'] = 'upage://' . $page->getId();
				$itemData['@visible_in_menu'] = $page->getIsVisible();
				$items[] = content::parseTemplate($templateItem, $itemData, $page->getId());
				$umiHierarchy->unloadElement($page->getId());
			}

			$data['items']['nodes:item'] = content::parseTemplate($templateBlock, $items);
			return content::parseTemplate($templateBlock, $data, $parentId);
		}

		/**
		 * Гененирует результатирующий массив с данными карты сайта для последующей шаблонизации
		 * @param string $template
		 * @param $site_tree
		 * @param $max_depth
		 * @return mixed
		 */
		public function gen_sitemap($template = 'default', $site_tree, $max_depth) {
			$hierarchy = umiHierarchy::getInstance();

			list($template_block, $template_item) = content::loadTemplates('content/sitemap/' . $template, 'block', 'item');

			$block_arr = [];
			$items = [];
			if (is_array($site_tree)) {
				foreach ($site_tree as $elementId => $childs) {
					$element = $hierarchy->getElement($elementId);

					if ($element) {
						$item_arr = [
							'attribute:id' => $elementId,
							'attribute:link' => $element->link,
							'attribute:name' => $element->getName(),
							'xlink:href' => 'upage://' . $elementId
						];

						if (($max_depth > 0) && $element->show_submenu) {
							$item_arr['nodes:items'] = $item_arr['void:sub_items'] =
								(umiCount($childs) && is_array($childs))
									? $this->gen_sitemap($template, $childs, $max_depth - 1)
									: '';
						} else {
							$item_arr['sub_items'] = '';
						}
						$items[] = content::parseTemplate($template_item, $item_arr, $elementId);
						$hierarchy->unloadElement($elementId);
					} else {
						continue;
					}
				}
			}

			$block_arr['subnodes:items'] = $items;
			return content::parseTemplate($template_block, $block_arr, 0);
		}

		/**
		 * Возвращает адрес страницы по ее идентификатору
		 * @param int $element_id идентификатор страницы
		 * @param bool $ignore_lang игнорировать языковой префикс в адресе
		 * @return string
		 */
		public function get_page_url($element_id, $ignore_lang = false) {
			$ignore_lang = (bool) $ignore_lang;
			return umiHierarchy::getInstance()->getPathById($element_id, $ignore_lang);
		}

		/**
		 * Возвращает идентификатор страницы по ее адресу
		 * @param $url
		 * @return int
		 * @throws publicException
		 */
		public function get_page_id($url) {
			$hierarchy = umiHierarchy::getInstance();
			$elementId = $hierarchy->getIdByPath($url);

			if ($elementId) {
				return $elementId;
			}

			throw new publicException(getLabel('error-page-does-not-exist', null, $url));
		}

		/**
		 * Возвращает контент страницы
		 * @param int|string $elementId идентификатор или адрес страницы
		 * @return bool|Mixed|null|string
		 */
		public function insert($elementId) {
			$hierarchy = umiHierarchy::getInstance();
			$cmsController = cmsController::getInstance();
			$currentElementId = $cmsController->getCurrentElementId();
			$elementId = trim($elementId);

			if (!$elementId) {
				return '%content_error_insert_null%';
			}

			$elementId = (int) is_numeric($elementId) ? $elementId : $hierarchy->getIdByPath($elementId);
			if ($elementId == $currentElementId) {
				return '%content_error_insert_recursy%';
			}
			if (!$elementId) {
				return '%content_error_insert_null%';
			}

			$element = $hierarchy->getElement($elementId);

			if ($element) {
				$this->module->pushEditable('content', '', $elementId);
				return $element->content;
			}

			return '%content_error_insert_null%';
		}

		/**
		 * Возвращает список последних просмотренных страниц
		 * @param string $template Шаблон для вывода
		 * @param string $scope Тэг(группировка страниц), без пробелов и запятых
		 * @param bool $showCurrentElement Если false - текущая страница не будет включена в результат
		 * @param int|null $limit Количество выводимых элементов
		 * @return mixed
		 */
		public function getRecentPages(
			$template = 'default',
			$scope = 'default',
			$showCurrentElement = false,
			$limit = null
		) {
			if (!$scope) {
				$scope = 'default';
			}

			$hierarchy = umiHierarchy::getInstance();
			$currentElementId = cmsController::getInstance()->getCurrentElementId();
			list($itemsTemplate, $itemTemplate) = content::loadTemplates('content/' . $template, 'items', 'item');
			$recentPages = Service::Session()->get('content:recent_pages');
			$recentPages = is_array($recentPages) ? $recentPages : [];
			$items = [];

			if (!isset($recentPages[$scope])) {
				return content::parseTemplate($itemsTemplate, ['subnodes:items' => []]);
			}

			$pageIdList = [];

			foreach ($recentPages[$scope] as $pageId => $time) {
				$pageIdList[] = $pageId;
			}

			$hierarchy->loadElements($pageIdList);

			foreach ($recentPages[$scope] as $pageId => $time) {
				$element = $hierarchy->getElement($pageId, true);

				if (!($element instanceOf umiHierarchyElement)) {
					continue;
				}

				if (!$showCurrentElement && $element->getId() == $currentElementId) {
					continue;
				}

				if ($limit !== null && $limit <= 0) {
					break;
				}

				if ($limit !== null) {
					$limit--;
				}

				$items[] = content::parseTemplate($itemTemplate, [
					'@id' => $element->getId(),
					'@link' => $element->link,
					'@name' => $element->getName(),
					'@alt-name' => $element->getAltName(),
					'@xlink:href' => 'upage://' . $element->getId(),
					'@last-view-time' => $time,
					'node:text' => $element->getName()
				], $element->getId());
			}

			return content::parseTemplate($itemsTemplate, ['subnodes:items' => $items]);
		}

		/**
		 * Добавляет страницу к списку последних просмотреных страниц
		 * @param int $elementId Текущая страница
		 * @param string $scope Тэг(группировка страниц)
		 * @return null
		 */
		public function addRecentPage($elementId, $scope = 'default') {
			if (!$scope) {
				$scope = 'default';
			}

			if ($elementId != cmsController::getInstance()->getCurrentElementId()) {
				return null;
			}

			$limit = mainConfiguration::getInstance()->get('modules', 'content.recent-pages.max-items');
			$limit = $limit ?: 100;

			$session = Service::Session();
			$recentPages = $session->get('content:recent_pages');
			$recentPages = is_array($recentPages) ? $recentPages : [];

			if (!isset($recentPages[$scope])) {
				$recentPages[$scope] = [];
			}

			$recentPages[$scope][$elementId] = time();
			asort($recentPages[$scope]);
			$recentPages[$scope] = array_reverse($recentPages[$scope], true);
			$recentPages[$scope] = array_slice($recentPages[$scope], 0, $limit, true);

			$session->set('content:recent_pages', $recentPages);

			return null;
		}

		/**
		 * Удаляет страницу из списка последних использований
		 * и делает редирект на предыдущую страницу
		 * @param int|bool $elementId Id страницы
		 * @param string $scope Тэг
		 */
		public function delRecentPage($elementId = false, $scope = 'default') {
			if ($elementId === false) {
				$elementId = getRequest('param0');
			}

			if (!$scope) {
				$scope = 'default';
			}

			$session = Service::Session();
			$recentPages = $session->get('content:recent_pages');
			$recentPages = is_array($recentPages) ? $recentPages : [];

			if (isset($recentPages[$scope][$elementId])) {
				unset($recentPages[$scope][$elementId]);
				$session->set('content:recent_pages', $recentPages);
			}

			$this->module->redirect(getServer('HTTP_REFERER'));
		}

		/**
		 * Получает список режимов отображения
		 * Текущий помечается как current
		 * @param string $template TPL шаблон
		 * @return mixed
		 */
		public function getMobileModesList($template = 'default') {
			$isMobile = Service::Request()->isMobile();
			$modes = [
				'is_mobile' => 1,
				'is_desktop' => 0
			];

			$items = [];
			foreach ($modes as $mode => $value) {
				$itemArray = [
					'@name' => $mode,
					'@link' => '/content/setMobileMode/' . ($value ? 0 : 1),
				];

				if ($value == $isMobile) {
					$itemArray['@status'] = 'active';
					$items[] = content::renderTemplate('content/mobile/' . $template, $mode, $itemArray);
				} else {
					$items[] = content::parseTemplate('', $itemArray);
				}
			}

			return content::renderTemplate('content/mobile/' . $template, 'modes', [
				'subnodes:items' => $items
			]);
		}

		/**
		 * Устанавливает режим отображения сайта
		 * @internal
		 * @param bool $isMobile Режим
		 */
		public function setMobileMode($isMobile = null) {
			if ($isMobile === null) {
				$isMobile = getRequest('param0');
			}

			$cookieJar = Service::CookieJar();

			if ($isMobile == 1) {
				$cookieJar->set('is_mobile', 1);
			} elseif ($isMobile == 0) {
				$cookieJar->set('is_mobile', 0);
			}

			$this->module->redirect(getServer('HTTP_REFERER'));
		}

		/**
		 * Возвращает HTML-код для подключения css- и js-файлов
		 * в зависимости от прав текущего пользователя.
		 * @return string
		 */
		public function includeFrontendResources() {
			if (Service::Session()->get('fake-user')) {
				return $this->module->getFakeUserFrontendResources();
			}

			if (permissionsCollection::getInstance()->isAllowedEditInPlace()) {
				return $this->module->getEditInPlaceFrontendResources();
			}

			return $this->module->getGuestFrontendResources();
		}

		/**
		 * Возвращает HTML-код для подключения css- и js-файлов
		 * в режиме редактирования заказа от имени пользователя.
		 * @return string
		 */
		public function getFakeUserFrontendResources() {
			$userName = $this->getFakeUserFormattedName();
			$orderName = $this->getFakeUserOrderName();
			$revision = $this->getRevision();

			return <<<HTML
<script>
	window.pageData = {$this->getCurrentPageData()};
</script>

<script src="/js/cms/jquery.compiled.js?{$revision}" charset="utf-8"></script>
<script src="/js/guest.js?{$revision}" charset="utf-8"></script>

<script>
	var FAKE_USER = {
		user_name: '$userName',
		order_name: '$orderName'
	};
</script>

<link rel="stylesheet" href="/js/cms/panel/design.css?{$revision}" type="text/css"></link>
<link rel="stylesheet" href="/styles/skins/_eip/css/theme.css?{$revision}" type="text/css"></link>
<link rel="stylesheet" href="/js/cms/eip/design.css?{$revision}" type="text/css"></link>

<script src="/js/cms/panel/fakeUser.js?{$revision}" charset="utf-8"></script>
<script src="/ulang/common.js?{$revision}" charset="utf-8"></script>
HTML;
		}

		/**
		 * Возвращает строку с данными пользователя,
		 * за которого переоформляется заказ.
		 * @return string
		 */
		public function getFakeUserFormattedName() {
			$user = umiObjectsCollection::getInstance()
				->getObject(Service::Auth()->getUserId());
			$userName = '';

			if ($user instanceof iUmiObject) {
				$userName = vprintf('%s %s (%s)', [
					$user->getValue('fname'),
					$user->getValue('lname'),
					$user->getValue('login')
				]);
			}

			return $userName;
		}

		/**
		 * Возвращает название заказа пользователя,
		 * за которого переоформляется заказ.
		 * @return string
		 */
		public function getFakeUserOrderName() {
			$order = umiObjectsCollection::getInstance()
				->getObject(Service::Session()->get('admin-editing-order'));
			$orderName = '';

			if ($order instanceof iUmiObject) {
				$orderName = $order->getName();
			}

			return $orderName;
		}

		/**
		 * Возвращает ревизию системы
		 * @return string
		 */
		public function getRevision() {
			/** @var autoupdate $autoupdate */
			$autoupdate = cmsController::getInstance()->getModule('autoupdate');
			return ($autoupdate instanceof autoupdate) ? $autoupdate->getRevision() : '';
		}

		/**
		 * Возвращает HTML-код для подключения css- и js-файлов
		 * в режиме быстрого редактирования
		 * @return string
		 */
		public function getEditInPlaceFrontendResources() {
			$eipWysiwygVersion = mainConfiguration::getInstance()
				->get('edit-in-place', 'wysiwyg') ?: 'tinymce47';
			$session = Service::Session();
			$revision = $this->getRevision();

			return <<<HTML
<link type="text/css" rel="stylesheet" href="/js/cms/compiled.css?{$revision}" />
{$this->getEditInPlaceThemeLink()}

<script>
	// Эту переменную нужно объявить ДО загрузки скрипта /js/cms/compiled.js,
	// иначе uAdmin отправит лишний ajax-запрос для получения данных о странице.
	// @see uAdmin.prototype.init()
	window.pageData = {$this->getCurrentPageData()};
</script>

<script src="/ulang/common.js?{$revision}" charset="utf-8"></script>
<script src="/js/cms/jquery.compiled.js?{$revision}" charset="utf-8"></script>
<script src="/js/cms/wysiwyg/{$eipWysiwygVersion}/tinymce.min.js?{$revision}" charset="utf-8"></script>
<script src="/js/cms/wysiwyg/{$eipWysiwygVersion}/tinymce_custom.js?{$revision}" charset="utf-8"></script>
<script src="/js/cms/compiled.js?{$revision}" charset="utf-8"></script>

<script>
	uAdmin({
		'lang_prefix': '{$this->getCurrentLangPrefix()}',
		'csrf': '{$session->get('csrf_token')}'
	});
	uAdmin({
		'lifetime' : {$session->getMaxActiveTime()},
		'access'   : {$this->getSessionAccessStatus()}
	}, 'session');
	uAdmin('type', '{$eipWysiwygVersion}', 'wysiwyg');
</script>
HTML;
		}

		/**
		 * Возвращает статус доступа к настройке продолжительности сессии
		 * для текущего пользователя.
		 * @return string
		 */
		public function getSessionAccessStatus() {
			$userId = Service::Auth()->getUserId();
			return permissionsCollection::getInstance()
				->isAllowedModule($userId, 'config') ? 'true' : 'false';
		}

		/**
		 * Возвращает префикс текущего языка
		 * @return string
		 */
		public function getCurrentLangPrefix() {
			$currentLang = Service::LanguageDetector()->detect();
			$defaultLang = Service::LanguageCollection()
				->getDefaultLang();

			$langPrefix = '';
			if ($currentLang->getId() != $defaultLang->getId()) {
				$langPrefix = $currentLang->getPrefix();
			}

			return $langPrefix;
		}

		/**
		 * Возвращает данные текущей страницы в формате JSON
		 * @return string
		 */
		public function getCurrentPageData() {
			$domain = Service::DomainDetector()->detect();
			$lang = Service::LanguageDetector()->detect();
			$pageId = cmsController::getInstance()
				->getCurrentElementId();

			$altName = '';
			if ($pageId) {
				$altName = umiHierarchy::getInstance()
					->getElement($pageId)
					->getAltName();
			}

			return json_encode([
				'pageId' => $pageId,
				'page' => [
					'alt-name' => $altName
				],
				'title' => def_module::parseTPLMacroses(macros_title()),
				'lang' => $lang->getPrefix(),
				'lang_id' => $lang->getId(),
				'domain' => $domain->getHost(),
				'domain_id' => $domain->getId(),
				'meta' => [
					'keywords' => macros_keywords(),
					'description' => macros_describtion()
				]
			]);
		}

		/**
		 * Возвращает HTML-код для подключения css- и js-файлов в режиме гостя.
		 * @return string
		 */
		public function getGuestFrontendResources() {
			$revision = $this->getRevision();
			return <<<HTML
<script>
	window.pageData = {$this->getCurrentPageData()};
</script>

<script src="/js/cms/jquery.compiled.js?{$revision}" charset="utf-8"></script>
<script src="/js/guest.js?{$revision}" charset="utf-8"></script>
<link type="text/css" rel="stylesheet" href="/js/jquery/fancybox/jquery.fancybox.css?{$revision}" />
HTML;
		}

		/**
		 * Возвращает HTML-ссылку на тему EIP
		 * @return string
		 */
		private function getEditInPlaceThemeLink() {
			$eipThemePath = mainConfiguration::getInstance()
				->get('edit-in-place', 'theme');
			$eipThemeLink = '';

			if ($eipThemePath) {
				$eipThemePath = mb_substr($eipThemePath, 1);
				$eipThemeLink = <<<EIP
<link type="text/css" rel="stylesheet" href="{$eipThemePath}?{$this->getRevision()}" />
EIP;
			}

			return $eipThemeLink;
		}

		/**
		 * Возвращает имя сайта
		 * @return string
		 */
		public function getSiteName() {
			$domainId = Service::DomainDetector()->detectId();
			$langId = Service::LanguageDetector()->detectId();
			$registry = Service::Registry();
			$siteName = $registry->get("//settings/site_name/{$domainId}/{$langId}") ?: $registry->get('//settings/site_name');
			return $siteName;
		}
	}
