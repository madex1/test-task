<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class VoteAdmin {

		use baseModuleAdmin;

		/** @var vote $module */
		public $module;

		/**
		 * Возвращает список опросов
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
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('pages');
			$sel->types('object-type')->name('vote', 'poll');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result(), 'pages');
			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Возвращает данные для создания формы добавления опроса.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do,
		 * то создает опрос и производит перенаправление.
		 * Адрес перенаправление зависит от режима кнопки "Добавить".
		 * можно отредактировать.
		 * @throws coreException
		 * @throws wrongElementTypeAdminException
		 */
		public function add() {
			$inputData = [
				'type' => 'poll',
				'parent' => null,
				'type-id' => getRequest('type-id'),
				'allowed-element-types' => ['poll']
			];

			if ($this->isSaveMode('param1')) {
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
		 * Возвращает данные для создания формы редактирования опроса.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do,
		 * то сохраняет изменения опроса и производит перенаправление.
		 * Адрес перенаправление зависит от режима кнопки "Сохранить".
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function edit() {
			$element = $this->expectElement('param0', true);
			$inputData = [
				'element' => $element,
				'allowed-element-types' => ['poll']
			];

			if ($this->isSaveMode('param1')) {
				if (isset($_REQUEST['data']['new'])) {
					unset($_REQUEST['data']['new']);
				}

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
		 * Удаляет опросы
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
					'allowed-element-types' => ['poll']
				];

				$this->deleteElement($params);
			}

			$data = $this->prepareData($elements, 'pages');

			$this->setDataType('list');
			$this->setActionType('view');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Изменяет активность опросов
		 * @throws coreException
		 * @throws expectElementException
		 * @throws requreMoreAdminPermissionsException
		 * @throws wrongElementTypeAdminException
		 */
		public function activity() {
			$this->changeActivityForPages(['poll']);
		}

		/**
		 * Возвращает данные для построения списка вариантов ответа на опрос.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do, то
		 * сохраняет изменения списка вариантов ответа на опрос.
		 * @throws coreException
		 * @throws expectElementException
		 */
		public function answers_list() {
			$element = $this->expectElement('param0');

			if (!$element instanceof iUmiHierarchyElement) {
				$sError = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n" .
					'<error>' . getLabel('error_save_page_first') . '</error>';

				$buffer = Service::Response()
					->getCurrentBuffer();
				$buffer->push($sError);
				$buffer->end();
			}

			$aAIDs = $element->getValue('answers');
			$object = $element->getObject()->getId();
			$type_id = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('vote', 'poll_item');

			if ($this->isSaveMode('param1')) {
				$params = [
					'type_id' => $type_id
				];
				$this->module->validateDeletingListPermissions();
				$iLastInsertID = $this->saveEditedList('objects', $params);

				if ($iLastInsertID !== false) {
					$aAIDs[] = $iLastInsertID;
					$element->setValue('answers', $aAIDs);
				}

				$dels = getRequest('dels');

				if (is_array($dels)) {
					foreach ($dels as $id) {
						$key = array_search($id, $aAIDs);
						unset($aAIDs[$key]);
					}
					$aAIDs = array_values($aAIDs);
					$element->setValue('answers', $aAIDs);
				}

				$element->commit();
			}

			$data = $this->prepareData($aAIDs, 'objects');
			$data['attribute:object_id'] = $object;

			$this->setDataType('list');
			$this->setActionType('modify');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает настройки модуля.
		 * Если передано ключевое слово "do" в $_REQUEST['param0'],
		 * то сохраняет изменения настроек.
		 * @throws coreException
		 */
		public function config() {
			$umiRegistry = Service::Registry();
			$params = [
				'config' => [
					'bool:is_private' => false,
					'bool:is_graded' => false
				]
			];

			if ($this->isSaveMode()) {
				$params = $this->expectParams($params);
				$umiRegistry->set('//modules/vote/is_private', (int) $params['config']['bool:is_private']);
				$umiRegistry->set('//modules/vote/is_graded', (int) $params['config']['bool:is_graded']);
				$this->chooseRedirect();
			}

			$params['config']['bool:is_private'] = (bool) $umiRegistry->get('//modules/vote/is_private');
			$params['config']['bool:is_graded'] = (bool) $umiRegistry->get('//modules/vote/is_graded');

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
						'module' => 'vote',
						'#__name' => 'lists'
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'vote',
						'#__name' => 'del',
						'aliases' => 'tree_delete_element,delete,del'
					],
					[
						'title' => getLabel('smc-activity'),
						'module' => 'vote',
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
				],
				'types' => [
					[
						'common' => 'true',
						'id' => 'poll'
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
					'rate_voters',
					'rate_sum',
					'total_count'
				],
				'default' => 'name[400px]|question[250px]'
			];
		}
	}

