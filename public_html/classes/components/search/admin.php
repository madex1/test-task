<?php

use UmiCms\Service;

/** Класс функционала административной панели */
	class SearchAdmin {

		use baseModuleAdmin;

		/** @var search $module */
		public $module;

		/**
		 * Возвращает настройки модуля.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то сохраняет настройки.
		 * @throws coreException
		 */
		public function config() {
			$params = [
				'config' => [
					'boolean:using-sphinx' => null,
					'boolean:search_morph_disabled' => null,
					'boolean:search-in-any-part-of-string' => null,
					'int:search-min-word-length' => null,
					'int:per_page' => null,
					'int:one_iteration_index' => null,
					'select-multi:search-types' => null,
					'boolean:allow-virtual-copy' => null,
				]
			];

			$config = mainConfiguration::getInstance();
			$registry = Service::Registry();

			if ($this->isSaveMode()) {
				$params = $this->expectParams($params);
				$params = array_shift($params);

				$config->set('modules', 'search.using-sphinx', $params['boolean:using-sphinx']);
				$config->set('kernel', 'pages-auto-index', $params['boolean:using-sphinx'] == 1 ? 0 : 1);
				$config->set('system', 'search-morph-disabled', $params['boolean:search_morph_disabled']);
				$config->set('kernel', 'search-in-any-part-of-string', $params['boolean:search-in-any-part-of-string']);
				$config->set('modules', 'search.allow-virtual-copies', $params['boolean:allow-virtual-copy']);

				$minWordLength = $params['int:search-min-word-length'];
				$minWordLength = (!is_numeric($minWordLength) || $minWordLength < 2) ? 2 : $minWordLength;
				$config->set('kernel', 'search-min-word-length', $minWordLength);
				$config->save();

				$registry->set('//modules/search/per_page', $params['int:per_page']);
				$registry->set('//modules/search/one_iteration_index', $params['int:one_iteration_index']);
				$registry->set('//modules/search/search-types', $params['select-multi:search-types']);
				$this->chooseRedirect();
			}

			$params['config'] = [
				'boolean:using-sphinx' => $config->get('modules', 'search.using-sphinx'),
				'boolean:search_morph_disabled' => $config->get('system', 'search-morph-disabled'),
				'boolean:search-in-any-part-of-string' => $config->get('kernel', 'search-in-any-part-of-string'),
				'int:search-min-word-length' => $config->get('kernel', 'search-min-word-length'),
				'int:per_page' => $registry->get('//modules/search/per_page'),
				'int:one_iteration_index' => $registry->get('//modules/search/one_iteration_index'),
				'select-multi:search-types' => $this->module->getSearchTypesOption(),
				'boolean:allow-virtual-copy' => $config->get('modules', 'search.allow-virtual-copies'),
			];

			$this->setConfigResult($params);
		}

		/**
		 * Возвращает состояние индексации для стандартного поиска
		 * @throws coreException
		 */
		public function index_control() {
			$config = mainConfiguration::getInstance();
			$searchEngine = $config->get('modules', 'search.using-sphinx');
			if ($searchEngine) {
				$this->module->redirect($this->module->pre_lang . '/admin/search/sphinx_control/');
			}

			$searchModel = searchModel::getInstance();
			$params = [
				'info' => [
					'status:index_pages' => null,
					'status:index_words' => null,
					'status:index_words_uniq' => null,
					'status:index_last' => null
				]
			];

			$params['info']['status:index_pages'] = $searchModel->getIndexPages();
			$params['info']['status:index_words'] = $searchModel->getIndexWords();
			$params['info']['status:index_words_uniq'] = $searchModel->getIndexWordsUniq();
			$params['info']['status:index_last'] = ($index_last = $searchModel->getIndexLast())
				? date('Y-m-d H:i:s', $index_last)
				: '-';

			$this->setConfigResult($params, 'view');
		}

		/**
		 * Возвращает состояние индексации для поиска средствами Sphinx
		 * @throws coreException
		 */
		public function sphinx_control() {
			$config = mainConfiguration::getInstance();
			$searchEngine = $config->get('modules', 'search.using-sphinx');
			if (!$searchEngine) {
				$this->module->redirect($this->module->pre_lang . '/admin/search/index_control/');
			}

			$params = [
				'sphinx-options' => [
					'string:host' => null,
					'int:port' => null,
					'string:dir' => null,
				],
				'fields-weight-options' => [
					'int:title' => null,
					'int:h1' => null,
					'int:meta_keywords' => null,
					'int:meta_descriptions' => null,
					'int:field_content' => null,
					'int:tags' => null
				],
				'generate-config' => []
			];

			if ($this->isSaveMode()) {
				$params = $this->expectParams($params);
				$config->set('sphinx', 'sphinx.host', $params['sphinx-options']['string:host']);
				$config->set('sphinx', 'sphinx.port', $params['sphinx-options']['int:port']);
				$params['sphinx-options']['string:dir'] = preg_replace(
					'#(\/$)|(\\\$)#',
					'',
					$params['sphinx-options']['string:dir']
				);
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

			$params['sphinx-options']['string:host'] = $config->get('sphinx', 'sphinx.host');
			$params['sphinx-options']['int:port'] = $config->get('sphinx', 'sphinx.port');
			$params['sphinx-options']['string:dir'] = $config->get('sphinx', 'sphinx.dir');
			$params['fields-weight-options']['int:title'] = $config->get('sphinx', 'sphinx.title');
			$params['fields-weight-options']['int:h1'] = $config->get('sphinx', 'sphinx.h1');
			$params['fields-weight-options']['int:meta_keywords'] = $config->get('sphinx', 'sphinx.meta_keywords');
			$params['fields-weight-options']['int:meta_descriptions'] = $config->get('sphinx', 'sphinx.meta_descriptions');
			$params['fields-weight-options']['int:field_content'] = $config->get('sphinx', 'sphinx.field_content');
			$params['fields-weight-options']['int:tags'] = $config->get('sphinx', 'sphinx.tags');

			$this->setConfigResult($params, 'view');
		}

		/**
		 * Переиндексирует страницы сайта.
		 * Используется итерационно
		 */
		public function partialReindex() {
			$this->setDataType('settings');
			$this->setActionType('view');

			$lastId = (int) getRequest('lastId');
			$search = searchModel::getInstance();

			$total = (int) $search->getAllIndexablePages();
			$limit = Service::Registry()->get('//modules/search/one_iteration_index');
			if ($limit == 0) {
				$limit = 5;
			}
			$result = $search->index_all($limit, $lastId);

			$data = [
				'index-status' => [
					'attribute:current' => $result['current'],
					'attribute:total' => $total,
					'attribute:lastId' => $result['lastId']
				]
			];

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Запускает поиск и замену
		 * @throws publicAdminException
		 */
		public function search_replace() {
			$action = (isset($_REQUEST['action']) && $_REQUEST['action'] != '') ? $_REQUEST['action'] : null;
			$currentPageNum = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;
			$searchString = (isset($_REQUEST['searchString']) && $_REQUEST['searchString'] != '')
				? $_REQUEST['searchString']
				: false;
			$replaceString = (isset($_REQUEST['replaceString']) && $_REQUEST['replaceString'] != '')
				? $_REQUEST['replaceString']
				: false;
			$domain_id = (isset($_REQUEST['domain_id']) && !empty($_REQUEST['domain_id']))
				? (int) $_REQUEST['domain_id']
				: false;

			$domains = Service::DomainCollection()->getList();
			$data = [
				'nodes:domains' => $domains
			];

			$this->setDataType('list');
			$this->setActionType('view');

			switch ($action) {
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
		protected function search($searchString, $replaceString, $currentPageNum, $domain_id) {
			if (!isset($_REQUEST['contentType']) || empty($_REQUEST['contentType'])) {
				throw new publicAdminException(getLabel('snr-error-empty-content-type'));
			}

			if (!is_string($searchString) || $searchString === '') {
				throw new publicAdminException(getLabel('snr-error-empty-search-string'));
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$searchString = $connection->escape($searchString);
			$contentTypes = $_REQUEST['contentType'];
			$hierarchyTypeIdsForReplace = [];

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

			$results = [];

			foreach ($rows as $row) {
				$result = [];

				if ($row['name'] == 'name' && !$row['varchar_val'] && !$row['text_val']) {
					$result['link']['content'] =
						$this->module->searchWithSnippet($row['object_name'], $searchString, 'name', 1, 'link');
					$result['text']['content'] =
						$this->module->searchWithSnippet($row['object_name'], $searchString, 'name', 2, 'text');
				} elseif ($row['varchar_val']) {
					$result['link']['content'] =
						$this->module->searchWithSnippet($row['varchar_val'], $searchString, $row['name'], 1, 'link');
					$result['text']['content'] =
						$this->module->searchWithSnippet($row['varchar_val'], $searchString, $row['name'], 2, 'text');
				} elseif ($row['text_val']) {
					$result['link']['content'] =
						$this->module->searchWithSnippet($row['text_val'], $searchString, $row['name'], 1, 'link');
					$result['text']['content'] =
						$this->module->searchWithSnippet($row['text_val'], $searchString, $row['name'], 2, 'text');
				}

				$result['id'] = $row['page_id'];
				$result['page_name'] = $row['object_name'];
				$result['name'] = $row['name'];
				$result['title'] = getLabel(str_replace('i18n::', '', $row['title']));
				$result['host'] = $row['host'];
				$results[] = $result;
			}

			$totalPagesCount = (isset($totalCount) && $totalCount > 0) ? ceil($totalCount / 50) : 1;
			$pagesNumbers = [];

			$query = http_build_query(
				[
					'searchString' => $searchString,
					'replaceString' => $replaceString,
					'action' => 'search',
					'contentType' => $contentTypes
				]
			);

			for ($i = 1; $i <= $totalPagesCount; $i++) {
				$pagesNumbers[] = [
					'pageNum' => $i,
					'attribute:current' => $i == $currentPageNum,
					'attribute:link' => '/admin/search/search_replace/?' . $query . '&page=' . $i
				];
			}

			return [
				'nodes:page' => $results,
				'attribute:searchString' => $searchString,
				'attribute:replaceString' => $replaceString,
				'attribute:postAction' => 'search',
				'attribute:totalCount' => $totalCount,
				'contentTypes' => [
					'nodes:contentType' => array_keys($contentTypes)
				],
				'pagination' => [
					'nodes:pageNum' => $pagesNumbers
				]
			];
		}

		/**
		 * Возвращает список идентификатор иерархических типов данных,
		 * среди страниц которых нужно производить поиск и замену.
		 * @param string $codeName код группы типов
		 * @return array
		 */
		protected function getHierarchyTypesIdsByCodeName($codeName) {
			$hierarchyTypesId = [];
			$umiHierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();
			$isAll = ($codeName == 'all');

			if ($codeName == 'news' || $isAll) {
				$newsItem = $umiHierarchyTypesCollection->getTypeByName('news', 'item');

				if ($newsItem instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $newsItem->getId();
				}

				$newsItemRubric = $umiHierarchyTypesCollection->getTypeByName('news', 'rubric');

				if ($newsItemRubric instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $newsItemRubric->getId();
				}
			}

			if ($codeName == 'content' || $isAll) {
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

			if ($codeName == 'blog' || $isAll) {
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

			if ($codeName == 'catalog' || $isAll) {
				$catalogCategory = $umiHierarchyTypesCollection->getTypeByName('catalog', 'category');

				if ($catalogCategory instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $catalogCategory->getId();
				}

				$catalogObject = $umiHierarchyTypesCollection->getTypeByName('catalog', 'object');

				if ($catalogObject instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $catalogObject->getId();
				}
			}

			if ($codeName == 'comments' || $isAll) {
				$comments = $umiHierarchyTypesCollection->getTypeByName('comments', 'comment');

				if ($comments instanceof iUmiEntinty) {
					$hierarchyTypesId[] = $comments->getId();
				}
			}

			if ($codeName == 'faq' || $isAll) {
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

			if ($codeName == 'forum' || $isAll) {
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

			if ($codeName == 'photoalbum' || $isAll) {
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
		protected function getStringFieldsTypeIds() {
			$umiFieldsTypes = umiFieldTypesCollection::getInstance();
			$fieldsTypeIds = [];

			$stringType = $umiFieldsTypes->getFieldTypeByDataType('string');

			if ($stringType instanceof iUmiEntinty) {
				$fieldsTypeIds[] = $stringType->getId();
			}

			$textType = $umiFieldsTypes->getFieldTypeByDataType('text');

			if ($textType instanceof iUmiEntinty) {
				$fieldsTypeIds[] = $textType->getId();
			}

			$wysiwygType = $umiFieldsTypes->getFieldTypeByDataType('wysiwyg');

			if ($wysiwygType instanceof iUmiEntinty) {
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
		protected function replace($searchString, $replaceString) {
			if (!isset($_REQUEST['replaceIds'])) {
				return [];
			}

			$replaceIds = $_REQUEST['replaceIds'];

			if (!is_array($replaceIds)) {
				return [];
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

					if ($replaceMode === null) {
						throw new publicAdminException(getLabel('snr-error-replace-params'));
					}

					foreach ($field as $key => $value) {
						$fieldName = $key;
						$text = ($fieldName == 'name') ? (string) $page->getName() : (string) $page->getValue($fieldName);
						$text = $this->module->replaceText($text, $searchString, $replaceString, $replaceMode);
						($fieldName == 'name') ? $page->setName($text) : $page->setValue($fieldName, $text);
						$data['nodes:reports'][] = [
							'report' => getLabel('snr-apply-replace') . ' "' . $page->getName() . '", ' .
								getLabel('snr-field') . ' "' . $fieldName . '"'
						];
					}
				}

				$page->commit();
			}

			$data['attribute:postAction'] = 'replace';
			return $data;
		}
	}
