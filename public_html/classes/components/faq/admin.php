<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class FaqAdmin {

		use baseModuleAdmin;

		/** @var faq $module */
		public $module;

		/** Алиас метода lists() */
		public function projects_list() {
			$this->lists();
		}

		/**
		 * Возвращает список проектов, категорий и вопросов.
		 * @return bool|void
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
				$sel->types('object-type')->name('faq', 'project');
				$sel->types('object-type')->name('faq', 'category');
			}

			$sel->types('object-type')->name('faq', 'question');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$data = $this->prepareData($sel->result(), 'pages');
			$this->setData($data, $sel->length());
			$this->setDataRangeByPerPage($limit, $curr_page);
			$this->doData();
		}

		/**
		 * Возвращает данные для построения формы создания сущности модуля.
		 * Если передан ключевой параметр $_REQUEST['param2'] = do, то создает сущность
		 * и перенаправляет на страницу, где ее можно отредактировать.
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function add() {
			$parent = $this->expectElement('param0');
			$type = (string) getRequest('param1');
			$this->setHeaderLabel('header-faq-add-' . $type);
			$inputData = [
				'type' => $type,
				'parent' => $parent,
				'type-id' => getRequest('type-id'),
				'allowed-element-types' => [
					'project',
					'category',
					'question'
				]
			];

			if ($this->isSaveMode('param2')) {
				$elementId = $this->saveAddedElementData($inputData);
				$this->chooseRedirect("{$this->module->pre_lang}/admin/faq/edit/{$elementId}/");
			}

			$this->setDataType('form');
			$this->setActionType('create');
			$data = $this->prepareData($inputData, 'page');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает данные для построения формы редактирования сущности модуля.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do, то сохраняет изменения сущности
		 * и осуществляет перенаправление. Адрес перенаправления зависит от режиме кнопки "Сохранить".
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function edit() {
			$element = $this->expectElement('param0', true);
			$method = $this->getObjectTypeMethod($element->getObject());
			$this->setHeaderLabel('header-faq-edit-' . $method);
			$inputData = [
				'element' => $element,
				'allowed-element-types' => [
					'project',
					'category',
					'question'
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
		 * Удаляет сущности модуля
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
						'project',
						'category',
						'question'
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
		 * Изменяет активность сущностей модуля.
		 * @throws coreException
		 * @throws expectElementException
		 * @throws requreMoreAdminPermissionsException
		 * @throws wrongElementTypeAdminException
		 */
		public function activity() {
			$this->changeActivityForPages(['project', 'category', 'question']);
		}

		/**
		 * Возвращает настройки модуля.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то сохраняет настройки.
		 * @throws coreException
		 */
		public function config() {
			$umiRegistry = Service::Registry();
			$umiNotificationInstalled = cmsController::getInstance()
				->isModule('umiNotifications');

			$params = [
				'config' => [
					'int:per_page' => null,
					'boolean:confirm_user_answer' => null,
					'boolean:disable_new_question_notification' => null
				]
			];

			if ($umiNotificationInstalled) {
				$params['config']['boolean:use-umiNotifications'] = null;
			}

			if ($this->isSaveMode()) {
				$params = $this->expectParams($params);
				$umiRegistry->set('//modules/faq/per_page', $params['config']['int:per_page']);
				$umiRegistry->set('//modules/faq/confirm_user_answer', $params['config']['boolean:confirm_user_answer']);
				$umiRegistry->set(
					'//modules/faq/disable_new_question_notification',
					$params['config']['boolean:disable_new_question_notification']
				);

				if ($umiNotificationInstalled) {
					$umiRegistry->set('//modules/faq/use-umiNotifications', $params['config']['boolean:use-umiNotifications']);
				}

				$this->chooseRedirect();
			}

			$params['config']['int:per_page'] = (int) $umiRegistry->get('//modules/faq/per_page');
			$params['config']['boolean:confirm_user_answer'] = (int) $umiRegistry->get('//modules/faq/confirm_user_answer');
			$params['config']['boolean:disable_new_question_notification'] =
				(int) $umiRegistry->get('//modules/faq/disable_new_question_notification');

			if ($umiNotificationInstalled) {
				$params['config']['boolean:use-umiNotifications'] =
					(bool) $umiRegistry->get('//modules/faq/use-umiNotifications');
			}

			$this->setConfigResult($params);
		}

		/**
		 * Возвращает настройки табличного контрола
		 * @param string $param контрольный параметр
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'faq',
						'#__name' => 'projects_list'
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'faq',
						'aliases' => 'tree_delete_element',
						'#__name' => 'del'
					],
					[
						'title' => getLabel('smc-activity'),
						'module' => 'faq',
						'aliases' => 'tree_set_activity',
						'#__name' => 'activity'
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
						'id' => 'question'
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
					'answer',
					'rate_voters',
					'rate_sum'
				],
				'default' => 'name[400px]'
			];
		}
	}
