<?php

	use UmiCms\Service;
	use UmiCms\Classes\System\Utils\Links\Injectors;
	use UmiCms\Classes\System\Utils\Links\Checker\iChecker;
	use UmiCms\Classes\System\Utils\Links\Grabber\iGrabber;

	/** Класс функционала административной панели */
	class SeoAdmin implements Injectors\iLinksCollection, Injectors\iLinksSourcesCollection {

		use baseModuleAdmin;
		use Injectors\tLinksCollection;
		use Injectors\tLinksSourcesCollection;

		/** @const string DEFAULT_PER_PAGE_NUMBER значение по умолчанию для кол-ва страниц к выводу в рамках пагинации */
		const DEFAULT_PER_PAGE_NUMBER = 10;

		/** @const string IS_COMPLETE_RESPONSE_KEY ключ данных ответа со статусом завершенности операции */
		const IS_COMPLETE_RESPONSE_KEY = 'isComplete';

		/** @const string STEP_RESPONSE_KEY ключ данных ответа с названием шага операции */
		const STEP_RESPONSE_KEY = 'step';

		/** @const string INFO_RESPONSE_KEY ключ данных ответа с информацией о прохождении операции */
		const INFO_RESPONSE_KEY = 'info';

		/** @const string STEP_LABEL_PREFIX префикс для языковой метки шага операции */
		const STEP_LABEL_PREFIX = 'js-label-step-';

		/** @const string INFO_LABEL_PREFIX префикс для языковой метки информации о прохождении операции */
		const INFO_LABEL_PREFIX = 'label-info-';

		/**@const string LABEL_ERROR_MEGAINDEX_NOINFO ответ MegaIndex об ошибке индексации  */
		const ERROR_MEGAINDEX_NOINDEX = 'Сайт не проиндексирован! Добавьте пожалуйста на индексацию.';

		/**@const string ERROR_MEGAINDEX_NOAUTH префикс ответа MegaIndex об ошибке авторизации */
		const ERROR_MEGAINDEX_NOAUTH = 'User Auth:';

		/** @var seo|SeoMegaIndex $module */
		public $module;

		/** @var null|UmiCms\Classes\System\Utils\Links\Grabber\Grabber $linksGrabber собиратель ссылок */
		private $linksGrabber;

		/** @var null|UmiCms\Classes\System\Utils\Links\Checker\Checker $linksChecker проверщик ссылок */
		private $linksChecker;

		/**
		 * Конструктор
		 * @throws Exception
		 */
		public function __construct() {
			$serviceContainer = ServiceContainerFactory::create();
			$this->setLinksCollection(
				$serviceContainer->get('linksCollection')
			);
			$this->setLinksSourcesCollection(
				$serviceContainer->get('linksSourcesCollection')
			);
			$this->setLinksGrabber(
				$serviceContainer->get('linksGrabber')
			);
			$this->setLinksChecker(
				$serviceContainer->get('linksChecker')
			);
		}

		/**
		 * Возвращает данные для вкладки "Анализ позиций"
		 * @return bool
		 * @throws coreException
		 */
		public function seo() {
			$this->setDataType('settings');
			$this->setActionType('view');
			$host = $this->getRequestHost();

			$data = $this->prepareData(
				[
					'config' => [
						'url:http_host' => $host
					]
				],
				'settings'
			);

			if ($this->module->ifNotXmlMode()) {
				$this->setData($data);
				$this->doData();
				return true;
			}

			try {
				$date = date('Y-m-d');
				/** @var stdClass $stat */
				$stat = $this->module->getVisibility($host, $date);
				$items = [];

				foreach ($stat->data as $k => $param) {
					$items[] = [
						'@word' => $param['0'],
						'@pos_y' => $param['1'],
						'@pos_g' => $param['3'],
						'@show_month' => $param['5'],
						'@wordstat' => $param['7'],
					];
				}

				unset($items[0]); // Удаляет заголовки списка

				$data['items'] = [
					'nodes:item' => $items
				];
			} catch (coreException $exception) {
				$data['error'] = getLabel('error-need-megaindex-registration');
			}

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает данные для вкладки "Анализ ссылок"
		 * @throws Exception
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function links() {
			$this->setDataType('settings');
			$this->setActionType('view');

			$host = $this->getRequestHost();
			$backLinks = $this->module->getBackLinks($host);
			$errorList = $this->getErrorList($backLinks);
			$linkList = $this->getLinkList($backLinks);
			
			$data = $this->prepareData([
				'config' => [
					'url:http_host' => $host
				]
			], 'settings');

			$data['links'] = $linkList;
			$data['errors'] = $errorList;

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает список ошибок для вкладки "Анализ ссылок"
		 * @param array|stdClass $backLinks данные из системы MegaIndex
		 * @return array
		 * @throws publicAdminException
		 */
		private function getErrorList($backLinks) {
			$errorList = [
				'nodes:error' => []
			];

			if (count($backLinks) === 0) {
				$errorList['nodes:error'][] = [
					'node:value' => ulangStream::getLabelSimple('label-error-megaindex-noinfo')
				];

				return $errorList;
			} 
			
			foreach ($backLinks as $link) { 
				if (is_string($link)) {
					$errorList['nodes:error'][] = [
						'node:value' => $this->getBackLinksError($link)
					];
				} elseif ($link instanceof stdClass && isset($link->error)) {
					$errorList['nodes:error'][] = [
						'node:value' => $this->getBackLinksError($link->error)
					];
				}
			}

			return $errorList;
		}
		
		/**
		 * Возвращает ошибку для вкладки "Анализ ссылок"
		 * @param string $error данные об ошибки из MegaIndex
		 * @return string
		 * @throws publicAdminException
		 */
		private function getBackLinksError($error) {
			if (startsWith($error, getLabel(self::ERROR_MEGAINDEX_NOAUTH))) {
				throw new publicAdminException(getLabel('label-error-megaindex-invalid-user'));
			}

			if ($error == self::ERROR_MEGAINDEX_NOINDEX) {
				$error = ulangStream::getLabelSimple('label-seo-noindex', [$this->getRequestHost()]);
			}

			return $error;
		}
		
		/**
		 * Возвращает список ссылок для вкладки "Анализ ссылок"
		 * @param array|stdClass $backLinks данные из системы MegaIndex
		 * @return array
		 * @throws publicAdminException
		 */
		private function getLinkList($backLinks) {
			$linkList = [
				'nodes:link' => []
			];

			$errorList = $this->getErrorList($backLinks);

			if (isEmptyArray($errorList['nodes:error'])) {		
				foreach ($backLinks as $link) {
					$linkList['nodes:link'][] = [
						'attribute:vs_from' => $link->vs_from,
						'attribute:vs_to' => $link->vs_to,
						'attribute:tic_from' => $link->tic_from,
						'attribute:tic_to' => $link->tic_to,
						'attribute:text' => $link->text,
						'attribute:noi' => $link->noi,
						'attribute:nof' => $link->nof
					];
				}
			}

			return $linkList;
		}

		/**
		 * Возвращает данные для вкладки "Страницы с незаполненными meta-тегами"
		 * @throws coreException
		 */
		public function emptyMetaTags() {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$limit = (int) getRequest('per_page_limit');
			$limit = ($limit === 0) ? self::DEFAULT_PER_PAGE_NUMBER : $limit;
			$currentPage = getRequest('p');
			$offset = $currentPage * $limit;

			$sel = new selector('pages');
			$sel->option('or-mode')->fields('h1', 'title', 'meta_keywords', 'meta_descriptions');
			$sel->where('h1')->isnull();
			$sel->where('title')->isnull();
			$sel->where('meta_keywords')->isnull();
			$sel->where('meta_descriptions')->isnull();
			$sel->limit($offset, $limit);

			selectorHelper::detectDomainFilter($sel);
			selectorHelper::detectLanguageFilter($sel);
			selectorHelper::checkSyncParams($sel);
			selectorHelper::detectOrderFilters($sel);

			$data = $this->prepareData($sel->result(), 'pages');
			$this->setData($data, $sel->length());
			$this->setDataRangeByPerPage($limit, $currentPage);
			$this->doData();
		}

		/**
		 * Возвращает настройки табличного контрола
		 * @param string $param контрольный параметр (чаще всего - название текущей вкладки
		 * административной панели)
		 * @return array
		 * @throws coreException
		 */
		public function getDatasetConfiguration($param = '') {
			if ($param == 'emptyMetaTags') {
				return $this->getPagesWithEmptyMetaTagsConfiguration();
			}

			return [];
		}

		/**
		 * Возвращает настройки табличного контрола для вкладки "Страницы с незаполненными meta тегами"
		 * @return array
		 * @throws coreException
		 */
		public function getPagesWithEmptyMetaTagsConfiguration() {
			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'seo',
						'#__name' => 'emptyMetaTags'
					]
				],
				'types' => [
					[
						'common' => true,
						'id' => $umiObjectTypes->getTypeIdByGUID('root-pages-type')
					]
				],
				'stoplist' => [
					'menu_pic_ua',
					'menu_pic_a',
					'header_pic',
					'more_params',
					'robots_deny',
					'is_unindexed',
					'store_amounts',
					'locktime',
					'lockuser',
					'anons',
					'content',
					'rate_voters',
					'rate_sum',
					'begin_time',
					'end_time',
					'tags',
					'show_submenu',
					'is_expanded'
				],
				'default' => 'name[350px]|title[250px]|meta_descriptions[250px]|h1[250px]|meta_keywords[250px]'
			];
		}

		/**
		 * Возвращает данные конфигурации табличного контрола вкладки "Страницы с битыми ссылками"
		 * @return array
		 */
		public function getBrokenLinksDatasetConfiguration() {
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'seo',
						'type' => 'load',
						'name' => 'getBrokenLinks'
					]
				],
				'default' => 'address[600px]|place[600px]',
				'fields' => [
					[
						'name' => 'address',
						'title' => getLabel('label-link-address', 'seo'),
						'type' => 'string',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false',
						'show_edit_page_link' => 'false'
					],
					[
						'name' => 'place',
						'title' => getLabel('label-page-address', 'seo'),
						'type' => 'string',
						'editable' => 'false',
						'filterable' => 'false',
						'sortable' => 'false'
					]
				]
			];
		}

		/** Возвращает конфиг вкладки "Страницы с битыми ссылками" в формате JSON для табличного контрола */
		public function flushBrokenLinksDatasetConfiguration() {
			$this->module->printJson($this->getBrokenLinksDatasetConfiguration());
		}

		/**
		 * Возвращает основные настройки модуля.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то сохраняет настройки.
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws requireAdminParamException
		 * @throws wrongParamException
		 * @throws Exception
		 */
		public function config() {
			$settingsManager = $this->module->getAdminSettingsManager();
			$params = $settingsManager->getParams();

			if ($this->isSaveMode()) {
				$params = self::expectedParams($params);
				$settingsManager->setCustomParams($params);
				$this->chooseRedirect();
			}

			$this->setConfigResult($params);
		}

		/**
		 * Возвращает настройки для подключения к сервису Megaindex.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то сохраняет настройки.
		 * @throws coreException
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws requireAdminParamException
		 * @throws wrongParamException
		 */
		public function megaindex() {
			$settings = [
				'config' => [
					'string:megaindex-login' => null,
					'string:megaindex-password' => null
				]
			];

			if ($this->isSaveMode()) {
				$settings = $this->expectParams($settings);
				$this->module->setMegaIndexLogin($settings['config']['string:megaindex-login']);
				$this->module->setMegaIndexPassword($settings['config']['string:megaindex-password']);
				$this->chooseRedirect();
			}

			$settings = [
				'config' => [
					'string:megaindex-login' => $this->module->getMegaIndexLogin(),
					'string:megaindex-password' => $this->module->getMegaIndexPassword()
				]
			];

			$this->setConfigResult($settings);
		}

		/** @inheritdoc */
		public function getDefaultPerPageNumber() {
			return self::DEFAULT_PER_PAGE_NUMBER;
		}

		/**
		 * Выводит в буффер данные для вкладки "Битые ссылки"
		 * @return bool|void
		 * @throws Exception
		 */
		public function getBrokenLinks() {
			if ($this->module->ifNotJsonMode()) {
				$this->setDataSetDirectCallMessage();
				return;
			}

			try {
				$limit = $this->getLimit();
				$offset = $this->getOffset($limit);
				$links = $this->getLinksCollection()
					->exportBrokenLinks($offset, $limit);
				$total = $this->getLinksCollection()
					->countBrokenLinks();
			} catch (Exception $e) {
				$links = $this->getSimpleErrorMessage(
					$e->getMessage()
				);
				$total = 0;
			}

			$this->module->printJson(
				$this->prepareTableControlEntities($links, $total)
			);
		}

		/**
		 * Запускает индексацию ссылок и выводит результат в буффер
		 * @throws Exception
		 */
		public function indexLinks() {
			$grabber = $this->getLinksGrabber();
			$isComplete = $grabber->grab()
				->saveResult()
				->saveState()
				->isComplete();

			if ($isComplete) {
				$grabber->flushSavedState();
			}

			$this->module->printJson([
				self::IS_COMPLETE_RESPONSE_KEY => $isComplete,
				self::STEP_RESPONSE_KEY => getLabel(self::STEP_LABEL_PREFIX . $grabber->getServiceName()),
				self::INFO_RESPONSE_KEY => getLabel(self::INFO_LABEL_PREFIX . $grabber->getStateName())
			]);
		}

		/**
		 * Запускает проверку ссылок и выводит результат в буффер
		 * @throws Exception
		 */
		public function checkLinks() {
			$checker = $this->getLinksChecker();
			$isComplete = $this->getLinksChecker()
				->checkBrokenUrls()
				->saveState()
				->isComplete();

			if ($isComplete) {
				$checker->flushSavedState();
			}

			$this->module->printJson([
				self::IS_COMPLETE_RESPONSE_KEY => $isComplete,
				self::STEP_RESPONSE_KEY => getLabel(self::STEP_LABEL_PREFIX . $checker->getServiceName()),
				self::INFO_RESPONSE_KEY => getLabel(self::INFO_LABEL_PREFIX . $checker->getServiceName())
			]);
		}

		/**
		 * Выводит в буффер ссылки, найденные в шаблонах и базе данных,
		 * с таким же адресом, как у ссылки, найденной на страницах сайта.
		 * @param bool|int $linkId идентификатор ссылки, найденной на страницах сайта
		 * @throws Exception
		 */
		public function getLinkSources($linkId = false) {
			if (!$linkId) {
				$linkId = $this->getNumberedParameter(0);
			}

			$sourceLinks = $this->getLinksSourcesCollection()
				->exportByLinkId($linkId);

			if (umiCount($sourceLinks) == 0) {
				throw new publicAdminException(getLabel('label-error-links-not-found'));
			}

			$this->module->printJson($sourceLinks);
		}

		/** @inheritdoc */
		protected function getYandexClientId() {
			return '47fc30ca18e045cdb75f17c9779cfc36';
		}

		/** @inheritdoc */
		protected function getYandexSecret() {
			return '8c744620c2414522867e358b74b4a2ff';
		}

		/**
		 * @inheritdoc
		 * @throws Exception
		 */
		protected function getTokenRegistry() {
			return $this->module->getRegistry();
		}

		/**
		 * Устанавливает собиратель ссылок
		 * @param iGrabber $grabber
		 * @return $this
		 */
		private function setLinksGrabber(iGrabber $grabber) {
			$this->linksGrabber = $grabber;
			return $this;
		}

		/**
		 * Возвращает собиратель ссылок
		 * @return \UmiCms\Classes\System\Utils\Links\Grabber\Grabber|iGrabber
		 * @throws RequiredPropertyHasNoValueException
		 */
		private function getLinksGrabber() {
			if (!$this->linksGrabber instanceof iGrabber) {
				throw new \RequiredPropertyHasNoValueException('You should set iGrabber first');
			}

			return $this->linksGrabber;
		}

		/**
		 * Устанавливает проверщик ссылок
		 * @param iChecker $checker
		 * @return $this
		 */
		private function setLinksChecker(iChecker $checker) {
			$this->linksChecker = $checker;
			return $this;
		}

		/**
		 * Возвращает проверщик ссылок
		 * @return \UmiCms\Classes\System\Utils\Links\Checker\Checker|iChecker
		 * @throws RequiredPropertyHasNoValueException
		 */
		private function getLinksChecker() {
			if (!$this->linksChecker instanceof iChecker) {
				throw new \RequiredPropertyHasNoValueException('You should set iChecker first');
			}

			return $this->linksChecker;
		}

		/**
		 * Возвращает запрошенный адрес сайта
		 * @return string
		 */
		private function getRequestHost() {
			$request = Service::Request();
			$requestedHost = (string) $request->Get()->get('host');

			if (isDemoMode() && $requestedHost == '') {
				return 'umi-cms.ru';
			}

			return $requestedHost !== '' ? $requestedHost : $request->host();
		}
	}
