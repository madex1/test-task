<?php

use UmiCms\Service;

/** Класс функционала административной панели */
	class NewsAdmin {

		use baseModuleAdmin;

		/** @var news $module */
		public $module;

		/**
		 * Возвращает настройки модуля.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то сохраняет настройки.
		 * @throws coreException
		 */
		public function config() {
			$regEdit = Service::Registry();
			$params = [
				'config' => [
					'int:per_page' => null,
					'int:rss_per_page' => null
				]
			];

			if ($this->isSaveMode()) {
				$params = $this->expectParams($params);
				$regEdit->set('//modules/news/per_page', (int) $params['config']['int:per_page']);
				$regEdit->set('//modules/news/rss_per_page', (int) $params['config']['int:rss_per_page']);
				$this->chooseRedirect();
			}

			$params['config']['int:per_page'] = (int) $regEdit->get('//modules/news/per_page');
			$params['config']['int:rss_per_page'] = (int) $regEdit->get('//modules/news/rss_per_page');
			$params['config']['int:rss_per_page'] = $params['config']['int:rss_per_page'] > 0
				? $params['config']['int:rss_per_page']
				: 10;

			$this->setConfigResult($params);
		}

		/**
		 * Устанавливает данные списка элементов модуля
		 * @return bool
		 * @throws coreException
		 * @throws selectorException
		 */
		public function lists() {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$limit = getRequest('per_page_limit');
			$currentPage = getRequest('p');
			$offset = $currentPage * $limit;

			$sel = new selector('pages');

			if (!selectorHelper::isFilterRequested()) {
				$sel->types('object-type')->name('news', 'rubric');
			}

			$sel->types('object-type')->name('news', 'item');

			if (is_array(getRequest('rel')) && Service::Registry()->get('//modules/comments')) {
				$sel->types('object-type')->name('comments', 'comment');
			}

			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$data = $this->prepareData($sel->result(), 'pages');
			$this->setData($data, $sel->length());
			$this->setDataRangeByPerPage($limit, $currentPage);
			$this->doData();
		}

		/**
		 * Устанавливает данные списка сюжетов публикации
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws requreMoreAdminPermissionsException
		 */
		public function subjects() {
			$objectTypesCollection = umiObjectTypesCollection::getInstance();
			$objectsCollection = umiObjectsCollection::getInstance();
			$typeId = $objectTypesCollection->getTypeIdByHierarchyTypeName('news', 'subject');

			if ($this->isSaveMode()) {
				$params = [
					'type_id' => $typeId
				];

				$this->module->validateDeletingListPermissions();
				$this->saveEditedList('objects', $params);
				$this->chooseRedirect();
			}

			$perPage = 25;
			$currentPage = getRequest('p');
			$subjectsGuide = $objectsCollection->getGuidedItems($typeId);
			$subjects = array_keys($subjectsGuide);
			$total = umiCount($subjects);

			$this->setDataType('list');
			$this->setActionType('modify');
			$this->setDataRange($perPage, $currentPage * $perPage);
			$data = $this->prepareData($subjects, 'objects');
			$this->setData($data, $total);
			$this->doData();
		}

		/**
		 * Добавляет элемент модуля
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function add() {
			$parent = $this->expectElement('param0');
			$type = (string) getRequest('param1');
			$this->setHeaderLabel('header-news-add-' . $type);
			$inputData = [
				'type' => $type,
				'parent' => $parent,
				'type-id' => getRequest('type-id'),
				'allowed-element-types' => [
					'rubric',
					'item'
				]
			];

			if ($this->isSaveMode('param2')) {
				$element_id = $this->saveAddedElementData($inputData);

				if ($type == 'item') {
					umiHierarchy::getInstance()->moveFirst(
						$element_id,
						($parent instanceof iUmiHierarchyElement) ? $parent->getId() : 0
					);
				}

				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('create');
			$data = $this->prepareData($inputData, 'page');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Редактирует элемент модуля
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function edit() {
			$element = $this->expectElement('param0', true);
			$this->setHeaderLabel('header-news-edit-' . $this->getObjectTypeMethod($element->getObject()));
			$inputData = [
				'element' => $element,
				'allowed-element-types' => [
					'rubric',
					'item'
				]
			];

			if ($this->isSaveMode('param1')) {
				$this->saveEditedElementData($inputData);
				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('modify');
			$data = $this->prepareData($inputData, 'page');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Удаляет элемент модуля
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function del() {
			$elements = getRequest('element');
			$elements = (array) $elements;

			foreach ($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true);

				$params = [
					'element' => $element,
					'allowed-element-types' => [
						'rubric',
						'item'
					]
				];

				$this->deleteElement($params);
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($elements, 'pages');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Изменяет активность элементов модуля
		 * @throws coreException
		 * @throws expectElementException
		 * @throws requreMoreAdminPermissionsException
		 * @throws wrongElementTypeAdminException
		 */
		public function activity() {
			$this->changeActivityForPages(['rubric', 'item']);
		}

		/**
		 * Устанавливает данные списка RSS-фидов для импорта
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws requreMoreAdminPermissionsException
		 */
		public function rss_list() {
			$typesCollection = umiObjectTypesCollection::getInstance();
			$objectsCollection = umiObjectsCollection::getInstance();
			$typeId = $typesCollection->getTypeIdByGUID('12c6fc06c99a462375eeb3f43dfd832b08ca9e17');
			$result = $objectsCollection->getGuidedItems($typeId);

			if ($this->isSaveMode()) {
				$params = [
					'type_id' => $typeId
				];
				$this->module->validateDeletingListPermissions();
				$this->saveEditedList('objects', $params);
				/** @var news|NewsFeeds $module */
				$module = $this->module;
				$module->import_feeds();
				$this->chooseRedirect();
			}

			$result = array_keys($result);
			$total = umiCount($result);

			$this->setDataType('list');
			$this->setActionType('modify');
			$this->setDataRange($total);

			$data = $this->prepareData($result, 'objects');
			$this->setData($data, $total);
			$this->doData();
		}

		/**
		 * Возвращает информацию об объектах для неудаленных рубрик новостей
		 * @return array
		 */
		public function getObjectNamesForRubrics() {
			$rubrics = new selector('pages');
			$rubrics->types('hierarchy-type')->name('news', 'rubric');
			$rubrics->where('is_deleted')->equals(0);
			$items = [];

			/** @var iUmiHierarchyElement $page */
			foreach ($rubrics as $page) {
				$object = $page->getObject();

				$items[] = [
					'attribute:id' => $object->getId(),
					'node:name' => $object->getName()
				];
			}

			return [
				'items' => [
					'nodes:item' => array_unique($items, SORT_REGULAR)
				]
			];
		}

		/**
		 * Возвращает настройки табличного контрола
		 * @param string $param контрольный параметр (чаще всего - название текущей вкладки
		 * административной панели)
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'news',
						'#__name' => 'lists'
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'news',
						'#__name' => 'del',
						'aliases' => 'tree_delete_element,delete,del'
					],
					[
						'title' => getLabel('smc-activity'),
						'module' => 'news',
						'#__name' => 'activity',
						'aliases' => 'tree_set_activity,activity'
					],
					[
						'title' => getLabel('smc-copy'),
						'module' => 'content',
						'#__name' => 'tree_copy_element'
					],
					[
						'title' => getLabel('smc-move'),
						'module' => 'content',
						'#__name' => 'move'
					],
					[
						'title' => getLabel('smc-change-template'),
						'module' => 'content',
						'#__name' => 'change_template'
					],
					[
						'title' => getLabel('smc-change-lang'),
						'module' => 'content',
						'#__name' => 'copyElementToSite'
					],
				],
				'types' => [
					[
						'common' => 'true',
						'id' => 'item'
					]
				],
				'stoplist' => [
					'title',
					'h1',
					'meta_keywords',
					'meta_descriptions',
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
					'end_time'
				],
				'default' => 'name[400px]|publish_time[250px]'
			];
		}
	}
