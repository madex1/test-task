<?php
	abstract class __search extends baseModuleAdmin {
		/** @var array Список имен модулей иерархических типов, по которым может осуществляться поиск */
		public static $permissibleTypesModules = array(
			'catalog', 'news', 'blogs20', 'forum', 'faq', 'content'
		);

		public function config() {
			$config = mainConfiguration::getInstance();
			$params = array(
				'config' => array(
					'boolean:using-sphinx'	=> null,
					'boolean:search_morph_disabled'	=> null,
					'int:per_page'				=> null,
					'int:one_iteration_index'	=> null,
					'select-multi:search-types' => null
				)
			);

			$mode = getRequest("param0");
			$regEdit = regedit::getInstance();

			if($mode == "do") {
				$params = $this->expectParams($params);
				$config->set('modules', 'search.using-sphinx', $params['config']['boolean:using-sphinx']);
				$config->set('kernel', 'pages-auto-index', $params['config']['boolean:using-sphinx'] == 1 ? 0 : 1);
				$config->set("system", "search-morph-disabled", $params['config']['boolean:search_morph_disabled']);
				$config->save();
				$regEdit->setVal('//modules/search/per_page', $params['config']['int:per_page']);
				$regEdit->setVal('//modules/search/one_iteration_index', $params['config']['int:one_iteration_index']);
				$regEdit->setVal('//modules/search/search-types', $params['config']['select-multi:search-types']);
				$this->chooseRedirect();
			}

			$params['config']['boolean:using-sphinx'] =  $config->get('modules', 'search.using-sphinx');
			$params['config']['boolean:search_morph_disabled'] =  $config->get("system", "search-morph-disabled");
			$params['config']['int:per_page'] = $regEdit->getVal('//modules/search/per_page');
			$params['config']['int:one_iteration_index'] = $regEdit->getVal('//modules/search/one_iteration_index');
			$params['config']['select-multi:search-types'] = $this->getSearchTypesOption();

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}

		/**
		 * Возвращает данные иерархических типов, по которым будет производиться поиск
		 * @return array
		 */
		public function getSearchTypesOption() {
			$regEdit = regedit::getInstance();
			$searchTypesString = $regEdit->getVal('//modules/search/search-types');
			$searchTypes = explode(baseModuleAdmin::DELIMITER_ID, $searchTypesString);

			$searchTypesIds = array_map(function($typeId) {
				return intval(trim($typeId));
			}, $searchTypes);

			$searchTypes = $this->getSelectedTypes($searchTypesIds);
			$permissibleTypes = $this->getPermissibleTypes();
			$items = array();

			/**
			 * @var int $typeId
			 * @var umiHierarchyType $type
			 */
			foreach ($permissibleTypes as $typeId => $type) {
				$item = array();

				if (isset($searchTypes[$typeId])) {
					$item['@selected'] = 'selected';
				}

				$item['node:value'] = $type->getTitle();
				$item['@id'] = $type->getId();
				$items[] = $item;
			}

			return array('nodes:item' => $items);
		}

		/**
		 * Возвращает список иерархических типов, по страницах которых доступен поиск
		 * @return array
		 */
		public function getPermissibleTypes() {
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			return $hierarchyTypes->getTypesByModules(self::$permissibleTypesModules);
		}

		/**
		 * Возвращает список выбранных иерархических типов для поиска
		 * @param array $savedTypes ID выбранных иерархических типов
		 * @return array
		 */
		public function getSelectedTypes($savedTypes) {
			return array_filter($this->getPermissibleTypes(), function($type) use ($savedTypes) {
				return in_array($type->getId(), $savedTypes);
			});
		}

		public function index_control() {
			$config = mainConfiguration::getInstance();
			$searchEngine = $config->get('modules', 'search.using-sphinx');
			if ($searchEngine){
				$this->redirect($this->pre_lang . "/admin/search/sphinx_control/");
			}

			$searchModel = searchModel::getInstance();
			
			$params = array(
				"info" => array(
					"status:index_pages"		=> NULL,
					"status:index_words"		=> NULL,
					"status:index_words_uniq"	=> NULL,
					"status:index_last"			=> NULL
				)
			);

			$params['info']['status:index_pages'] = $searchModel->getIndexPages();
			$params['info']['status:index_words'] = $searchModel->getIndexWords();
			$params['info']['status:index_words_uniq'] = $searchModel->getIndexWordsUniq();
			$params['info']['status:index_last'] = ($index_last = $searchModel->getIndexLast()) ? date("Y-m-d H:i:s", $index_last) : "-";

			$this->setDataType("settings");
			$this->setActionType("view");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}

        public function sphinx_control() {
			$config = mainConfiguration::getInstance();
			$searchEngine = $config->get('modules', 'search.using-sphinx');
			if (!$searchEngine){
				$this->redirect($this->pre_lang . "/admin/search/index_control/");
			}

			$this->setDataType("settings");
			$this->setActionType("view");

			$params = array(
				"sphinx-options" 	=> array(
					"string:host"	=> NULL,
					"int:port"		=> NULL,
					"string:dir"	=> NULL,
				),
				"fields-weight-options"	=> array(
					'int:title' 			=> NULL,
					'int:h1' 				=> NULL,
					'int:meta_keywords' 	=> NULL,
					'int:meta_descriptions' => NULL,
					'int:field_content' 	=> NULL,
					'int:tags' 				=> NULL
				),
				"generate-config"	=> array()
			);

			$mode = (string) getRequest('param0');
			if($mode == "do") {
				$params = $this->expectParams($params);
				$config->set('sphinx', 'sphinx.host', $params['sphinx-options']['string:host']);
				$config->set('sphinx', 'sphinx.port', $params['sphinx-options']['int:port']);

				$params['sphinx-options']['string:dir'] = preg_replace('#(\/$)|(\\\$)#', '', $params['sphinx-options']['string:dir']);

				$config->set('sphinx', 'sphinx.dir', $params['sphinx-options']['string:dir']);
				$config->set('sphinx', 'sphinx.title', $params['fields-weight-options']['int:title']);
				$config->set('sphinx', 'sphinx.h1', $params['fields-weight-options']['int:h1']);
				$config->set('sphinx', 'sphinx.meta_keywords', $params['fields-weight-options']['int:meta_keywords']);
				$config->set('sphinx', 'sphinx.meta_descriptions', $params['fields-weight-options']['int:meta_descriptions']);
				$config->set('sphinx', 'sphinx.field_content', $params['fields-weight-options']['int:field_content']);
				$config->set('sphinx', 'sphinx.tags', $params['fields-weight-options']['int:tags']);
				$config->save();

				$this->chooseRedirect();
			}

			$params['sphinx-options']['string:host'] =  $config->get('sphinx', 'sphinx.host');
			$params['sphinx-options']['int:port'] =  $config->get('sphinx', 'sphinx.port');
			$params['sphinx-options']['string:dir'] = $config->get('sphinx', 'sphinx.dir');
			$params['fields-weight-options']['int:title'] =  $config->get('sphinx', 'sphinx.title');
			$params['fields-weight-options']['int:h1'] =  $config->get('sphinx', 'sphinx.h1');
			$params['fields-weight-options']['int:meta_keywords'] =  $config->get('sphinx', 'sphinx.meta_keywords');
			$params['fields-weight-options']['int:meta_descriptions'] =  $config->get('sphinx', 'sphinx.meta_descriptions');
			$params['fields-weight-options']['int:field_content'] =  $config->get('sphinx', 'sphinx.field_content');
			$params['fields-weight-options']['int:tags'] =  $config->get('sphinx', 'sphinx.tags');

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
        }

		public function truncate() {
			searchModel::getInstance()->truncate_index();
			$this->redirect($this->pre_lang . "/admin/search/");
		}


		public function reindex() {
			searchModel::getInstance()->index_all();
			$this->redirect($this->pre_lang . "/admin/search/");
		}
		
		public function partialReindex() {
			$this->setDataType("settings");
			$this->setActionType("view");

			$lastId = (int) getRequest("lastId");
			$search = searchModel::getInstance();
			
			$total = (int) $search->getAllIndexablePages();
			$limit = regedit::getInstance()->getVal("//modules/search/one_iteration_index");
			if ($limit==0) {
				$limit = 5;
			}
			$result = $search->index_all($limit, $lastId);
			
			$data = Array(
				'index-status' => Array(
					'attribute:current' => $result['current'],
					'attribute:total' => $total,
					'attribute:lastId' => $result['lastId']
				)
			);

			$this->setData($data);
			return $this->doData();
		}

		/** Генерация базового View для контента */
		public function generateView() {
			$contentIndex = new SphinxIndexGenerator('sphinx_content_index');
			$this->setIndexType($contentIndex);

			$sql = $contentIndex->generateViewQuery();
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->query($sql);

			$config = mainConfiguration::getInstance();
			$pathToSphinx = $config->get('sphinx', 'sphinx.dir');
			$dir = new umiDirectory($pathToSphinx);

			if (empty($pathToSphinx)) {
				$pathToSphinx = CURRENT_WORKING_DIR . DIRECTORY_SEPARATOR . 'sys-temp' . DIRECTORY_SEPARATOR . 'sphinx';
			}

			$pathToConfig = $pathToSphinx . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
			$dir->requireFolder($pathToConfig);

			if (file_exists($pathToConfig)) {
				$fileName = $pathToConfig . 'view.sql';
				file_put_contents($fileName, $sql);
			}
			if ($connection->errorNumber() == 0) {
				$this->sendJson(array(
					'status'  => 'ok',
					'message' => getLabel('build-view-finish')
				));
			} else {
				$this->sendJson(array(
					'status'  => 'fail',
					'message' => getLabel('build-view-finish-error')
				));
			}
		}

		/** Генерация базового конфига для Sphinx */
		public function generateSphinxConfig() {
			$config = mainConfiguration::getInstance();

			$mySqlPort = $config->get('connections', 'core.port');
			if (empty($mySqlPort)) {
				$mySqlPort = 3306;
			}

			$pathToSphinx = $config->get('sphinx', 'sphinx.dir');
			$dir = new umiDirectory($pathToSphinx);
			if (empty($pathToSphinx)) {
				$pathToSphinx = CURRENT_WORKING_DIR . DIRECTORY_SEPARATOR . 'sys-temp' . DIRECTORY_SEPARATOR . 'sphinx';
			}
			$pathToIndex = $pathToSphinx . DIRECTORY_SEPARATOR . 'index' . DIRECTORY_SEPARATOR;
			$dir->requireFolder($pathToIndex);
			$binlog = $pathToSphinx . DIRECTORY_SEPARATOR . 'log';
			$pathToLog = $pathToSphinx . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR;
			$dir->requireFolder($pathToLog);
			$pathToConfig = $pathToSphinx . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
			$dir->requireFolder($pathToConfig);

			$contentIndex = new SphinxIndexGenerator('sphinx_content_index');
			$this->setIndexType($contentIndex);

			$configSphinx = $contentIndex->generateSphinxConfig(array(
				'{mySqlHost}' => $config->get('connections', 'core.host'),
				'{mySqlUser}' => $config->get('connections', 'core.login'),
				'{mySqlPass}' => $config->get('connections', 'core.password'),
				'{mySqlDB}' => $config->get('connections', 'core.dbname'),
				'{mySqlPort}' => $mySqlPort,
				'{pathToIndex}' => $pathToIndex,
				'{listen}' => $config->get('sphinx', 'sphinx.port'),
				'{pathToLog}' => $pathToLog,
				'{binlog}' => $binlog,
			));

			if (file_exists($pathToConfig)) {
				$fileName = $pathToConfig . 'sphinx.conf';
				file_put_contents($fileName, $configSphinx);
				$this->sendJson(array(
					'status'  => 'ok',
					'message' => getLabel('build-config-sphinx-finish')
				));
			} else {
				$this->sendJson(array(
					'status'  => 'fail',
					'message' => getLabel('build-config-sphinx-finish-error')
				));
			}
		}

		public function sendJson($data) {
			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->push(
				json_encode(array(
						'result' => $data
					)
				)
			);
			$buffer->end();
		}

		public function isExistsConfig() {
			$config = mainConfiguration::getInstance();
			$pathToSphinx = $config->get('sphinx', 'sphinx.dir');
			if (empty($pathToSphinx)) {
				$pathToSphinx = CURRENT_WORKING_DIR . DIRECTORY_SEPARATOR . 'sys-temp' . DIRECTORY_SEPARATOR . 'sphinx';
			}
			$pathToConfig = $pathToSphinx . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'sphinx.conf';
			$this->sendJson(array(
				'response' => file_exists($pathToConfig)
			));
		}

		/**
		 * Добавляет поля во View
		 * @param $contentIndex SphinxIndexGenerator
		 */
		public function setIndexType($contentIndex) {
			$types = umiObjectTypesCollection::getInstance();

			$pagesType = $types->getSubTypesList($types->getType('root-pages-type')->getId());

			$indexFields = array(
				'title',
				'h1',
				'meta_keywords',
				'meta_descriptions',
				'content',
				'tags',
				'is_unindexed',
				'readme',
				'anons',
				'description',
				'descr',
				'message',
				'question',
				'answers',
			);

			$contentIndex->addPagesList($pagesType, $types, $indexFields);

			$event = new umiEventPoint("sphinxCreateView");
			$event->addRef("contentIndex", $contentIndex);
			$event->setMode("before");
			$event->call();
		}

		/**
		 * Запускает поиск и замену
		 * @throws publicAdminException
		 */
		public function search_replace(){
			$action = (isset($_REQUEST['action']) && $_REQUEST['action'] != '')? $_REQUEST['action'] : null;
			$currentPageNum = (isset($_REQUEST['page'])) ? (int) $_REQUEST['page'] : 1;
			$searchString =  (isset($_REQUEST['searchString']) && $_REQUEST['searchString'] != '')? $_REQUEST['searchString'] : false;
			$replaceString = (isset($_REQUEST['replaceString']) && $_REQUEST['replaceString'] != '')? $_REQUEST['replaceString'] : false;
			$domain_id =  (isset($_REQUEST['domain_id']) && !empty($_REQUEST['domain_id']))? (int) $_REQUEST['domain_id'] : false;

			$domains = domainsCollection::getInstance()->getList();
			$data = array(
				'nodes:domains' => $domains
			);

			$this->setDataType("list");
			$this->setActionType("view");

			switch ($action){
				case 'search' :
					$data += $this->search($searchString, $replaceString, $currentPageNum, $domain_id);
					break;
				case 'replace' : {
					$data += $this->replace($searchString, $replaceString);
					break;
				}
				case null : {
					break;
				}
				default : {
					throw new publicAdminException(getLabel('snr-error-wrong-action'));
				}
			}

			$this->setData($data, umiCount($data));
			$this->doData();
		}

		/**
		 * Выполняет поиск страниц по вхождению строки в название страницы или текстовые поля.
		 * Возвращает результат поиска
		 * @param string $searchString строка, вхождение которой требуется найти
		 * @param string $replaceString строка для замены (замена не производится)
		 * @param int $currentPageNum номер страницы в рамках пагинации
		 * @param int $domain_id идентификатор домена
		 * @return array
		 * @throws Exception
		 * @throws publicAdminException
		 */
		public function search($searchString, $replaceString, $currentPageNum, $domain_id) {
			if (!isset($_REQUEST['contentType']) || empty($_REQUEST['contentType'])){
				throw new publicAdminException(getLabel('snr-error-empty-content-type'));
			}

			if (!is_string($searchString) || $searchString === '') {
				throw new publicAdminException(getLabel('snr-error-empty-search-string'));
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$searchString = $connection->escape($searchString);
			$contentTypes = $_REQUEST['contentType'];
			$hierarchyTypeIdsForReplace = array();

			foreach ($contentTypes as $type => $value) {
				$hierarchyTypeIdsForReplace += $this->getHierarchyTypesIdsByCodeName($type);
			}

			$hierarchyTypeIdsForReplace = array_map('intval', $hierarchyTypeIdsForReplace);
			$hierarchyTypeIdsForReplace = implode(',', $hierarchyTypeIdsForReplace);

			$stringFieldsTypesIds = $this->getStringFieldsTypeIds();
			$stringFieldsTypesIds = array_map('intval', $stringFieldsTypesIds);
			$stringFieldsTypesIds = implode(',', $stringFieldsTypesIds);

			$domainQuery = '';

			if ($domain_id) {
				$domainQuery = 'AND domain_id = ' . (int) $domain_id;
			}

			$limit = 100;
			$currentPageNum = (int) $currentPageNum;
			$offset = $currentPageNum * $limit - $limit;

			$sql = <<<SQL
(SELECT SQL_CALC_FOUND_ROWS
 	cms3_hierarchy.id as page_id,
 	cms3_object_fields.name,
 	cms3_object_fields.title,
 	cms3_object_content.varchar_val,
 	cms3_object_content.text_val,
 	cms3_objects.name as object_name,
 	cms3_object_content.obj_id,
 	cms3_object_content.field_id,
 	cms3_domains.host as host
 FROM
   cms3_object_content
   LEFT JOIN cms3_hierarchy ON cms3_object_content.obj_id = cms3_hierarchy.obj_id
   LEFT JOIN cms3_object_fields ON cms3_object_content.field_id = cms3_object_fields.id
   LEFT JOIN cms3_objects ON cms3_object_content.obj_id = cms3_objects.id
   LEFT JOIN cms3_domains ON cms3_domains.id = cms3_hierarchy.domain_id
 WHERE

 cms3_hierarchy.type_id IN ($hierarchyTypeIdsForReplace) AND
 (cms3_object_content.varchar_val LIKE '%$searchString%' COLLATE utf8_bin OR
 cms3_object_content.text_val LIKE '%$searchString%' COLLATE utf8_bin)
 AND cms3_object_fields.field_type_id in ($stringFieldsTypesIds)
 AND is_deleted = 0
 $domainQuery
 GROUP BY obj_id, field_id
 )

 UNION ALL

 (SELECT
 	cms3_hierarchy.id as page_id,
 	'name',
 	cms3_object_fields.title,
 	NULL,
 	NULL,
 	cms3_objects.name as object_name,
 	cms3_object_content.obj_id,
 	cms3_object_content.field_id,
 	cms3_domains.host as host
 FROM
   cms3_object_content
   LEFT JOIN cms3_hierarchy ON cms3_object_content.obj_id = cms3_hierarchy.obj_id
   LEFT JOIN cms3_object_fields ON cms3_object_content.field_id = cms3_object_fields.id
   LEFT JOIN cms3_objects ON cms3_object_content.obj_id = cms3_objects.id
   LEFT JOIN cms3_domains ON cms3_domains.id = cms3_hierarchy.domain_id
 WHERE
 cms3_hierarchy.type_id IN ($hierarchyTypeIdsForReplace) AND
 cms3_objects.name LIKE '%$searchString%' COLLATE utf8_bin AND
 is_deleted = 0
 $domainQuery
 GROUP BY obj_id)
 ORDER BY page_id

 LIMIT $offset, $limit
SQL;
			$rows = $connection->queryResult($sql);

			$totalCount = $connection->queryResult('SELECT FOUND_ROWS()');
			$totalCount = $totalCount->fetch();
			$totalCount = (int) array_shift($totalCount);
			$rows->setFetchType(IQueryResult::FETCH_ASSOC);

			$results = array();

			foreach ($rows as $row) {
				$result = array();
				/** @var search|__search $this */
				if ($row['name'] == 'name' && !$row['varchar_val'] && !$row['text_val']){
					$result['link']['content'] = $this->searchWithSnippet($row['object_name'] ,$searchString, 'name', 1, 'link');
					$result['text']['content'] = $this->searchWithSnippet($row['object_name'] ,$searchString, 'name', 2, 'text');
				} elseif ($row['varchar_val']){
					$result['link']['content'] = $this->searchWithSnippet($row['varchar_val'] ,$searchString, $row['name'], 1, 'link');
					$result['text']['content'] = $this->searchWithSnippet($row['varchar_val'] ,$searchString, $row['name'], 2, 'text');
				} elseif($row['text_val'] ) {
					$result['link']['content'] = $this->searchWithSnippet($row['text_val'] ,$searchString, $row['name'], 1, 'link');
					$result['text']['content'] = $this->searchWithSnippet($row['text_val'] ,$searchString, $row['name'], 2, 'text');
				}

				$result['id'] = $row['page_id'];
				$result['page_name'] = $row['object_name'];
				$result['name'] = $row['name'];
				$result['title'] = getLabel(str_replace('i18n::','',$row['title']));
				$result['host'] = $row['host'];
				$results[] = $result;
			}

			$totalPagesCount = (isset($totalCount) && $totalCount > 0)? ceil($totalCount / 50) : 1;
			$pagesNumbers = array();

			$query = http_build_query(
				array(
					'searchString' => $searchString,
					'replaceString' => $replaceString,
					'action' => 'search',
					'contentType' => $contentTypes
				)
			);

			for ($i = 1; $i <= $totalPagesCount; $i++){
				$pagesNumbers[] = array(
					'pageNum' => $i,
					'attribute:current' => ($i == $currentPageNum) ? true : false,
					'attribute:link' => '/admin/search/search_replace/?' . $query . '&page=' . $i
				);
			}

			return array(
				'nodes:page' => $results,
				'attribute:searchString' => $searchString,
				'attribute:replaceString' => $replaceString,
				'attribute:postAction' => 'search',
				'attribute:totalCount' => $totalCount,
				'contentTypes' => array(
					'nodes:contentType' => array_keys($contentTypes)
				),
				'pagination' => array(
					'nodes:pageNum' => $pagesNumbers
				)
			);
		}

		/**
		 * Возвращает список идентификатор иерархических типов данных,
		 * среди страниц которых нужно производить поиск и замену.
		 * @param string $codeName код группы типов
		 * @return array
		 */
		public function getHierarchyTypesIdsByCodeName($codeName) {
			$hierarchyTypesId = array();
			$umiHierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();
			$isAll = ($codeName == 'all');

			if ($codeName == 'news' ||	$isAll) {
				$newsItem = $umiHierarchyTypesCollection->getTypeByName('news', 'item');

				if ($newsItem instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $newsItem->getId();
				}

				$newsItemRubric = $umiHierarchyTypesCollection->getTypeByName('news', 'item');

				if ($newsItemRubric instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $newsItemRubric->getId();
				}
			}

			if ($codeName == 'content' ||	$isAll) {
				$contentPage = $umiHierarchyTypesCollection->getTypeByName('content', '');

				if ($contentPage instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $contentPage->getId();
				}

				$sharedFile = $umiHierarchyTypesCollection->getTypeByName('filemanager', 'shared_file');

				if ($sharedFile instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $sharedFile->getId();
				}

				$webFormPage = $umiHierarchyTypesCollection->getTypeByName('webforms', 'page');

				if ($webFormPage instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $webFormPage->getId();
				}
			}

			if ($codeName == 'blog' ||	$isAll) {
				$blogPost = $umiHierarchyTypesCollection->getTypeByName('blogs20', 'post');

				if ($blogPost instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $blogPost->getId();
				}

				$blogComment = $umiHierarchyTypesCollection->getTypeByName('blogs20', 'comment');

				if ($blogComment instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $blogComment->getId();
				}

				$blog = $umiHierarchyTypesCollection->getTypeByName('blogs20', 'blog');

				if ($blog instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $blog->getId();
				}
			}

			if ($codeName == 'catalog' ||	$isAll) {
				$catalogCategory = $umiHierarchyTypesCollection->getTypeByName('catalog', 'category');

				if ($catalogCategory instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $catalogCategory->getId();
				}

				$catalogObject = $umiHierarchyTypesCollection->getTypeByName('catalog', 'object');

				if ($catalogObject instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $catalogObject->getId();
				}
			}

			if ($codeName == 'comments' ||	$isAll) {
				$comments = $umiHierarchyTypesCollection->getTypeByName('comments', 'comment');

				if ($comments instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $comments->getId();
				}
			}

			if ($codeName == 'faq' ||	$isAll) {
				$faqCategory = $umiHierarchyTypesCollection->getTypeByName('faq', 'category');

				if ($faqCategory instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $faqCategory->getId();
				}

				$faqProject = $umiHierarchyTypesCollection->getTypeByName('faq', 'project');

				if ($faqProject instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $faqProject->getId();
				}

				$faqQuestion = $umiHierarchyTypesCollection->getTypeByName('faq', 'question');

				if ($faqQuestion instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $faqQuestion->getId();
				}
			}

			if ($codeName == 'forum' ||	$isAll) {
				$forumConference = $umiHierarchyTypesCollection->getTypeByName('forum', 'conf');

				if ($forumConference instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $forumConference->getId();
				}

				$forumMessage = $umiHierarchyTypesCollection->getTypeByName('forum', 'message');

				if ($forumMessage instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $forumMessage->getId();
				}

				$forumTopic = $umiHierarchyTypesCollection->getTypeByName('forum', 'topic');

				if ($forumTopic instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $forumTopic->getId();
				}
			}

			if ($codeName == 'photoalbum' ||	$isAll) {
				$photoAlbum = $umiHierarchyTypesCollection->getTypeByName('photoalbum', 'album');

				if ($photoAlbum instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $photoAlbum->getId();
				}

				$photo = $umiHierarchyTypesCollection->getTypeByName('photoalbum', 'photo');

				if ($photo instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $photo->getId();
				}
			}

			return $hierarchyTypesId;
		}

		/**
		 * Возвращает идентификаторы строковых типов полей
		 * @return array
		 */
		public function getStringFieldsTypeIds() {
			$umiFieldsTypes = umiFieldTypesCollection::getInstance();
			$fieldsTypeIds = array();

			$stringType = $umiFieldsTypes->getFieldTypeByDataType('string');

			if ($stringType instanceof umiEntinty) {
				$fieldsTypeIds[] = $stringType->getId();
			}

			$textType = $umiFieldsTypes->getFieldTypeByDataType('text');

			if ($textType instanceof umiEntinty) {
				$fieldsTypeIds[] = $textType->getId();
			}

			$wysiwygType = $umiFieldsTypes->getFieldTypeByDataType('wysiwyg');

			if ($wysiwygType instanceof umiEntinty) {
				$fieldsTypeIds[] = $wysiwygType->getId();
			}

			return $fieldsTypeIds;
		}

		/**
		 * Выполняет замену строки и возвращает результат
		 * @param string $searchString искомая строка
		 * @param string $replaceString строка замены
		 * @return array
		 * @throws publicAdminException
		 */
		public function replace($searchString, $replaceString) {
			if (!isset($_REQUEST['replaceIds'])) {
				return array();
			}

			$replaceIds = $_REQUEST['replaceIds'];

			if (!is_array($replaceIds)) {
				return array();
			}

			if (!is_string($searchString) || $searchString === '') {
				throw new publicAdminException(getLabel('snr-error-empty-search-string'));
			}

			if (!is_string($replaceString) || $replaceString === '') {
				throw new publicAdminException(getLabel('snr-error-empty-replace-string'));
			}

			$hierarchy = umiHierarchy::getInstance();
			$pageIds = array_keys($replaceIds);
			$hierarchy->loadElements($pageIds);
			$backupModel = backupModel::getInstance();

			foreach ($replaceIds as $pageId => $fields) {

				$backupModel->save($pageId);
				$page = $hierarchy->getElement($pageId);

				if (!is_array($fields)) {
					continue;
				}

				foreach ($fields as $replaceType => $field) {
					$replaceMode = null;

					if ($replaceType == 'link') {
						$replaceMode = 1;
					}

					if ($replaceType == 'text') {
						$replaceMode = 2;
					}

					if (is_null($replaceMode)) {
						throw new publicAdminException(getLabel('snr-error-replace-params'));
					}

					foreach ($field as $key => $value) {
						/** @var search|__search $this */
						$fieldName = $key;
						$text = ($fieldName == 'name') ? (string) $page->getName() : (string) $page->getValue($fieldName);
						$text = $this->replaceText($text, $searchString, $replaceString, $replaceMode);
						($fieldName == 'name') ? $page->setName($text) : $page->setValue($fieldName, $text);
						$data['nodes:reports'][] = array(
							'report' => getLabel('snr-apply-replace') . ' "' . $page->getName() . '", ' . getLabel('snr-field') .' "' . $fieldName . '"'
						);
					}
				}

				$page->commit();
			}

			$data['attribute:postAction'] = 'replace';
			return $data;
		}
	};
?>
