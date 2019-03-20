<?php

	/** Класс функционала административной панели */
	class FilemanagerAdmin {

		use baseModuleAdmin;

		/** @var filemanager основной класс модуля */
		public $module;

		/**
		 * Список страниц с файлами для скачивания
		 * @throws coreException
		 * @throws selectorException
		 */
		public function shared_files() {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return;
			}

			$limit = getRequest('per_page_limit');
			$currentPage = getRequest('p');
			$offset = $currentPage * $limit;

			$sel = new selector('pages');
			$sel->types('object-type')->name('filemanager', 'shared_file');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$result = $sel->result();
			$total = $sel->length();

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($result, 'pages');

			$this->setData($data, $total);
			$this->doData();
		}

		/**
		 * Изменить активность у страниц с файлами для скачивания
		 * @throws coreException
		 * @throws expectElementException
		 * @throws requreMoreAdminPermissionsException
		 * @throws wrongElementTypeAdminException
		 */
		public function shared_file_activity() {
			$this->changeActivityForPages(['shared_file']);
		}

		/**
		 * Данные для формирования формы создания страницы со скачиваемым файлом
		 * Если передан ключевой параметр $_REQUEST['param0'] = do - создать страницу со скачиваемым файлом
		 * @throws coreException
		 * @throws wrongElementTypeAdminException
		 */
		public function add_shared_file() {
			$type = 'shared_file';
			$parent = $this->expectElement('param0');
			$inputData = [
				'type' => $type,
				'parent' => $parent,
				'allowed-element-types' => ['shared_file']
			];

			if ($this->isSaveMode('param1')) {
				$elementId = $this->saveAddedElementData($inputData);
				$element = umiHierarchy::getInstance()->getElement($elementId);

				if (getRequest('select_fs_file')) {
					$filePath = getRequest('fs_dest_folder') . '/' . getRequest('select_fs_file');
					$file = new umiFile($filePath);
					$element->setValue('fs_file', $file);
					$element->commit();
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
		 * Данные для формирования формы редактирования страницы со скачиваемым файлом
		 * Если передан ключевой параметр $_REQUEST['param1'] = do,
		 * то сохраняет изменения страницы со скачиваемым файлом
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function edit_shared_file() {
			$element = $this->expectElement('param0', true);
			$inputData = [
				'element' => $element,
				'allowed-element-types' => ['shared_file']
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
		 * Удалить страницу со скачиваемым файлом
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function del_shared_file() {
			$elements = getRequest('element');

			if (!is_array($elements)) {
				$elements = [$elements];
			}

			foreach ($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true);

				$params = [
					'element' => $element,
					'allowed-element-types' => ['shared_file']
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
		 * Настройки табличного контрола
		 * @param string $param контрольный параметр (чаще всего - название текущей вкладки административной панели)
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'filemanager',
						'#__name' => 'shared_files'
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'filemanager',
						'#__name' => 'del_shared_file',
						'aliases' => 'tree_delete_element,delete,del'
					],
					[
						'title' => getLabel('smc-activity'),
						'module' => 'filemanager',
						'#__name' => 'shared_file_activity',
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
				],
				'types' => [
					[
						'common' => 'true',
						'id' => 'shared_file'
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
					'rate_sum'
				],
				'default' => 'name[400px]|downloads_counter[250px]'
			];
		}
	}


