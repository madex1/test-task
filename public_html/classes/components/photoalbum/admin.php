<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class PhotoalbumAdmin {

		use baseModuleAdmin;

		/** @var photoalbum $module */
		public $module;

		/**
		 * Возвращает список страниц модуля (альбомов и фотографий)
		 * @return bool
		 * @throws coreException
		 * @throws selectorException
		 */
		public function lists() {
			$module = $this->module;
			$this->setDataType('list');
			$this->setActionType('view');

			if ($module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$limit = getRequest('per_page_limit');
			$currentPage = getRequest('p');
			$offset = $currentPage * $limit;

			$sel = new selector('pages');

			if (!selectorHelper::isFilterRequested()) {
				$sel->types('object-type')->name('photoalbum', 'album');
			}

			$sel->types('object-type')->name('photoalbum', 'photo');

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
		 * Возвращает данные для создания формы добавления страницы модуля.
		 * Если передан $_REQUEST['param2'] = do,
		 * то добавляет страницу и производит перенаправление.
		 * Адрес перенаправление зависит от режима кнопки "Добавить".
		 * @throws coreException
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function add() {
			$parent = $this->expectElement('param0');
			$type = (string) getRequest('param1');
			$this->setHeaderLabel('header-photoalbum-add-' . $type);
			$inputData = [
				'type' => $type,
				'parent' => $parent,
				'type-id' => getRequest('type-id'),
				'allowed-element-types' => [
					'album', 'photo'
				]
			];

			$this->module->_checkFolder($parent);

			if ($this->isSaveMode('param2')) {
				$this->saveAddedElementData($inputData);
				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('create');
			$data = $this->prepareData($inputData, 'page');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает данные для создания формы редактирования страницы модуля.
		 * Если передан $_REQUEST['param1'] = do,
		 * то сохраняет изменения страницы и производит перенаправление.
		 * Адрес перенаправление зависит от режима кнопки "Сохранить".
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function edit() {
			$element = $this->expectElement('param0', true);

			if ($this->getObjectTypeMethod($element->getObject()) == 'photo') {
				$parent = $this->expectElement($element->getParentId(), false, 1);
			} else {
				$parent = $element;
			}

			$this->setHeaderLabel('header-photoalbum-edit-' . $this->getObjectTypeMethod($element->getObject()));
			$inputData = [
				'element' => $element,
				'allowed-element-types' => [
					'album', 'photo'
				]
			];

			$this->module->_checkFolder($parent);

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
		 * Удаляет страницы модуля
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function del() {
			$elements = getRequest('element');

			if (!is_array($elements)) {
				$elements = [$elements];
			}

			foreach ($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true);

				$params = [
					'element' => $element,
					'allowed-element-types' => [
						'album', 'photo'
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
		 * Изменяет активность страниц модуля
		 * @throws coreException
		 * @throws expectElementException
		 * @throws requreMoreAdminPermissionsException
		 * @throws wrongElementTypeAdminException
		 */
		public function activity() {
			$this->changeActivityForPages(['album', 'photo']);
		}

		/**
		 * Возвращает настройки модуля.
		 * Если передано ключевое слово "do" в $_REQUEST['param0'],
		 * то сохраняет настройки.
		 * @throws coreException
		 */
		public function config() {
			$umiTypes = umiObjectTypesCollection::getInstance();
			$photoTypeId = $umiTypes->getTypeIdByHierarchyTypeName('photoalbum', 'photo');
			$subTypes = $umiTypes->getSubTypesList($photoTypeId);
			$typesList = [];

			if (is_numeric($photoTypeId) && $photoTypeId > 0) {
				$typesList = array_unique(array_merge([$photoTypeId], $subTypes));
			}

			$optionsList = [];

			foreach ($typesList as $typeId) {
				$type = $umiTypes->getType($typeId);

				if ($type instanceof iUmiObjectType) {
					$optionsList[$typeId] = $type->getName();
				}
			}

			$redEdit = Service::Registry();
			$params = [
				'config' => [
					'int:per_page' => null,
					'select:photo_object_types' => $optionsList
				]
			];

			if ($this->isSaveMode()) {
				$params = $this->expectParams($params);
				$redEdit->set('//modules/photoalbum/per_page', $params['config']['int:per_page']);
				$redEdit->set('//modules/photoalbum/zip_object_type', $params['config']['select:photo_object_types']);
				$this->chooseRedirect();
			}

			$params['config']['int:per_page'] = (int) $redEdit->get('//modules/photoalbum/per_page');
			$params['config']['select:photo_object_types']['value'] = (int) $redEdit->get('//modules/photoalbum/zip_object_type');

			$this->setConfigResult($params);
		}

		/**
		 * Возвращает настройки для формирования табличного контрола
		 * @param string $param контрольный параметр
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
						'module' => 'photoalbum',
						'#__name' => 'del',
						'aliases' => 'tree_delete_element,del'
					],
					[
						'title' => getLabel('smc-activity'),
						'module' => 'photoalbum',
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
						'id' => 'photo'
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
					'descr',
					'rate_voters',
					'rate_sum'
				],
				'default' => 'name[400px]|photo[250px]'
			];
		}
	}

