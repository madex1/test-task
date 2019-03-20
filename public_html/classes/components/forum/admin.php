<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class ForumAdmin {

		use baseModuleAdmin;

		/** @var forum $module */
		public $module;

		/** Перенаправляет на страницу /admin/forum/lists/ */
		public function confs_list() {
			$this->module->redirect($this->module->pre_lang . '/admin/forum/lists/');
		}

		/**
		 * Возвращает список из конференций,
		 * топиков и сообщений форума
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
			$curr_page = getRequest('p');
			$offset = $curr_page * $limit;

			$sel = new selector('pages');

			if (!selectorHelper::isFilterRequested()) {
				$sel->types('object-type')->name('forum', 'conf');
				$sel->types('object-type')->name('forum', 'topic');
			}

			$sel->types('object-type')->name('forum', 'message');
			$sel->limit($offset, $limit);

			selectorHelper::detectFilters($sel);

			$data = $this->prepareData($sel->result(), 'pages');
			$this->setData($data, $sel->length());
			$this->setDataRangeByPerPage($limit, $curr_page);
			$this->doData();
		}

		/**
		 * Возвращает список сообщений форума
		 * @return bool
		 * @throws coreException
		 * @throws selectorException
		 */
		public function last_messages() {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$limit = getRequest('per_page_limit');
			$curr_page = getRequest('p');
			$offset = $curr_page * $limit;

			$sel = new selector('pages');
			$sel->types('object-type')->name('forum', 'message');

			if (is_numeric($sel->searchField('publish_time'))) {
				$sel->order('publish_time')->desc();
			}

			$sel->limit($offset, $limit);

			selectorHelper::detectFilters($sel);

			$data = $this->prepareData($sel->result(), 'pages');
			$this->setData($data, $sel->length());
			$this->setDataRangeByPerPage($limit, $curr_page);
			$this->doData();
		}

		/**
		 * Возвращает данные для создания формы добавления страницы форума,
		 * если передан $_REQUEST['param2'] = do пытается создать страницу
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws wrongElementTypeAdminException
		 */
		public function add() {
			$parent = $this->expectElement('param0');
			$type = (string) getRequest('param1');
			$this->setHeaderLabel('header-forum-add-' . $type);
			$inputData = [
				'type' => $type,
				'parent' => $parent,
				'type-id' => getRequest('type-id'),
				'allowed-element-types' => [
					'conf',
					'topic',
					'message'
				]
			];

			if ($this->isSaveMode('param2')) {
				$elementId = $this->saveAddedElementData($inputData);
				$element = $this->expectElement($elementId, false, true);

				$event = new umiEventPoint('systemCreateElementAfter');
				$event->addRef('element', $element);
				$event->setMode('after');
				$event->call();

				$this->chooseRedirect();
			}

			$this->makeAdminOutputForm('create', 'page', $inputData);
		}

		/**
		 * Возвращает данные для создания формы редактирования страницы форума,
		 * если передан $_REQUEST['param1'] = do пытается сохранить изменения
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws wrongElementTypeAdminException
		 */
		public function edit() {
			$element = $this->expectElement('param0', true);
			$this->setHeaderLabel('header-forum-edit-' . $this->getObjectTypeMethod($element->getObject()));
			$inputData = [
				'element' => $element,
				'allowed-element-types' => [
					'conf',
					'topic',
					'message'
				]
			];

			if ($this->isSaveMode('param1')) {
				$this->saveEditedElementData($inputData);

				$event = new umiEventPoint('systemSwitchElementActivity');
				$event->addRef('element', $element);
				$event->setMode('after');
				$event->call();

				$this->chooseRedirect();
			}

			$this->makeAdminOutputForm('modify', 'page', $inputData);
		}

		/**
		 * Удаляет страницы форума.
		 * Если родитель удаляемой страницы - конференция,
		 * то актуализуются счетчики топиков и сообщений форума,
		 * хранимые в конференции.
		 * Если родитель удаляемой страницы - топик и в нем
		 * только одно сообщение - вместе со страницей удалиться топик.
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function del() {
			$elements = getRequest('element');

			if (!is_array($elements)) {
				$elements = [$elements];
			}

			$hierarchy = umiHierarchy::getInstance();

			foreach ($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true);
				$parentElementId = $element->getParentId();
				$parentElement = $hierarchy->getElement($parentElementId);

				if ($parentElement instanceof iUmiHierarchyElement) {
					$parentMethod = $parentElement->getMethod();

					if ($parentMethod == 'conf') {
						$topicsCount = $parentElement->getValue('topics_count');
						$messagesCount = $parentElement->getValue('messages_count');
						$messagesDiff = $element->getValue('messages_count');

						$parentElement->setValue('topics_count', $topicsCount - 1);
						$parentElement->setValue('messages_count', $messagesCount - $messagesDiff);
						$parentElement->commit();
					}

					if ($parentMethod == 'topic') {
						$messagesCount = $parentElement->getValue('messages_count');

						if ($messagesCount == 1) {
							$params = [
								'element' => $parentElement,
								'allowed-element-types' => [
									'conf',
									'topic',
									'message'
								]
							];
							$this->deleteElement($params);
						}
					}
				}

				$params = [
					'element' => $element,
					'allowed-element-types' => [
						'conf',
						'topic',
						'message'
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
		 * Меняет статус активности страниц форума
		 * @throws coreException
		 * @throws expectElementException
		 * @throws requreMoreAdminPermissionsException
		 * @throws wrongElementTypeAdminException
		 */
		public function activity() {
			$this->changeActivityForPages(['conf', 'topic', 'message']);
		}

		/**
		 * Возвращает настройки модуля
		 * Если передано ключевое слово "do" в $_REQUEST['param0'],
		 * то сохраняет переданные настройки.
		 */
		public function config() {
			$regedit = Service::Registry();
			$umiNotificationInstalled = cmsController::getInstance()
				->isModule('umiNotifications');

			$params = [
				'config' => [
					'int:per_page' => null,
					'boolean:need_moder' => null,
					'boolean:allow_guest' => null,
					'boolean:sort_by_last_message' => null
				]
			];

			if ($umiNotificationInstalled) {
				$params['config']['boolean:use-umiNotifications'] = null;
			}

			if ($this->isSaveMode()) {
				$params = $this->expectParams($params);
				$regedit->set('//modules/forum/per_page', $params['config']['int:per_page']);
				$regedit->set('//modules/forum/need_moder', $params['config']['boolean:need_moder']);
				$regedit->set('//modules/forum/allow_guest', $params['config']['boolean:allow_guest']);
				$regedit->set('//modules/forum/sort_by_last_message', $params['config']['boolean:sort_by_last_message']);

				if ($umiNotificationInstalled) {
					$regedit->set('//modules/forum/use-umiNotifications', $params['config']['boolean:use-umiNotifications']);
				}

				$this->chooseRedirect();
			}

			$params['config']['int:per_page'] = (int) $regedit->get('//modules/forum/per_page');
			$params['config']['boolean:need_moder'] = (int) $regedit->get('//modules/forum/need_moder');
			$params['config']['boolean:allow_guest'] = (int) $regedit->get('//modules/forum/allow_guest');
			$params['config']['boolean:sort_by_last_message'] = (int) $regedit->get('//modules/forum/sort_by_last_message');

			if ($umiNotificationInstalled) {
				$params['config']['boolean:use-umiNotifications'] =
					(int) $regedit->get('//modules/forum/use-umiNotifications');
			}

			$this->setConfigResult($params);
		}

		/**
		 * Возвращает настройки для формирования табличного контрола
		 * @param string $param контрольный параметр
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			$loadMethod = ($param == 'last_messages') ? 'last_messages' : 'lists';

			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'forum',
						'#__name' => $loadMethod
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'forum',
						'#__name' => 'del',
						'aliases' => 'tree_delete_element,delete,del'
					],
					[
						'title' => getLabel('smc-activity'),
						'module' => 'forum',
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
						'id' => 'message'
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
					'rate_sum'
				],
				'default' => 'name[400px]|publish_time[250px]|author_id[250px]'
			];
		}

		/**
		 * Возвращает данные для формирования формы административной панели
		 * @param string $s_data_action тип формы
		 * @param string $s_item_type редактируемой/добавляемой сущности
		 * @param mixed $inputData данные для построения формы
		 * @throws coreException
		 */
		public function makeAdminOutputForm($s_data_action, $s_item_type, $inputData) {
			$this->setDataType('form');
			$this->setActionType($s_data_action);
			$data = $this->prepareData($inputData, $s_item_type);
			$this->setData($data);
			$this->doData();
		}

	}
