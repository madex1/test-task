<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class BannersAdmin {

		use baseModuleAdmin;

		/** @var banners $module */
		public $module;

		/**
		 * Возвращает список баннеров
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
			$offset = $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('banners', 'banner');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result(), 'objects');
			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Возвращает список мест для показа баннеров.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то сохраняет изменения списка мест.
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws requreMoreAdminPermissionsException
		 * @throws selectorException
		 */
		public function places() {
			if ($this->isSaveMode()) {
				$this->module->validateDeletingListPermissions();
				$this->saveEditedList('objects', [
					'type' => 'place'
				]);
				$this->chooseRedirect();
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('banners', 'place');
			$this->setDataType('list');
			$this->setActionType('modify');
			$data = $this->prepareData($sel->result(), 'objects');
			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Возвращает данные для создания формы добавления баннера,
		 * если передан $_REQUEST['param0'] = do, то создает баннера
		 * и перенаправляет страницу, где его можно отредактировать.
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws wrongElementTypeAdminException
		 */
		public function add() {
			$type = 'banner';
			$type_id = (int) getRequest('type-id');

			$inputData = [
				'type' => $type,
				'type-id' => getRequest('type-id')
			];

			if ($this->isSaveMode()) {
				$object = $this->saveAddedObjectData($inputData);
				$object->setTypeId($type_id);

				if (isset($_REQUEST['data']['new']['show_till_date']) &&
					!mb_strlen($_REQUEST['data']['new']['show_till_date'])) {
					$object->setValue('show_till_date', null);
					$object->commit();
				}

				$this->chooseRedirect($this->module->pre_lang . '/admin/banners/edit/' . $object->getId() . '/');
			}

			$this->setDataType('form');
			$this->setActionType('create');
			$data = $this->prepareData($inputData, 'object');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает данные для создания формы редактирования баннера.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do,
		 * то сохраняет изменения баннера и производит перенаправление.
		 * Адрес перенаправление зависит от режима кнопки "Сохранить".
		 * @throws coreException
		 * @throws expectObjectException
		 */
		public function edit() {
			$object = $this->expectObject('param0');

			if ($this->isSaveMode('param1')) {
				$this->saveEditedObjectData($object);

				if (
					isset($_REQUEST['data'][$object->getId()]['show_till_date']) &&
					!mb_strlen($_REQUEST['data'][$object->getId()]['show_till_date'])
				) {
					$object->setValue('show_till_date', null);
					$object->commit();
				}

				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('modify');
			$data = $this->prepareData($object, 'object');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Удаляет баннеры
		 * @throws coreException
		 * @throws expectObjectException
		 * @throws wrongElementTypeAdminException
		 */
		public function del() {
			$objects = getRequest('element');

			if (!is_array($objects)) {
				$objects = [$objects];
			}

			foreach ($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);

				$params = [
					'object' => $object,
					'allowed-element-types' => [
						'banner',
						'place'
					]
				];

				$this->deleteObject($params);
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($objects, 'objects');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Изменяет активность баннеров
		 * @throws coreException
		 * @throws expectObjectException
		 */
		public function activity() {
			$this->changeActivityForObjects();
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
					'int:days-before-notification' => null,
					'int:clicks-before-notification' => null,
					'boolean:not-use-referer-tags' => null
				]
			];

			if ($umiNotificationInstalled) {
				$params['config']['boolean:use-umiNotifications'] = null;
			}

			if ($this->isSaveMode()) {
				$params = $this->expectParams($params);
				$umiRegistry->set('//modules/banners/days-before-notification', (int) $params['config']['int:days-before-notification']);
				$umiRegistry->set('//modules/banners/clicks-before-notification', (int) $params['config']['int:clicks-before-notification']);
				$umiRegistry->set('//modules/banners/not-use-referer-tags', $params['config']['boolean:not-use-referer-tags']);

				if ($umiNotificationInstalled) {
					$umiRegistry->set('//modules/banners/use-umiNotifications', $params['config']['boolean:use-umiNotifications']);
				}

				$umiRegistry->set('//modules/banners/last-check-date', '');
				$this->chooseRedirect();
			}

			$params['config']['int:days-before-notification'] =
				(int) $umiRegistry->get('//modules/banners/days-before-notification');
			$params['config']['int:clicks-before-notification'] =
				(int) $umiRegistry->get('//modules/banners/clicks-before-notification');
			$params['config']['boolean:not-use-referer-tags'] = $umiRegistry->get('//modules/banners/not-use-referer-tags');

			if ($umiNotificationInstalled) {
				$params['config']['boolean:use-umiNotifications'] =
					(bool) $umiRegistry->get('//modules/banners/use-umiNotifications');
			}

			$this->setConfigResult($params);
		}

		/**
		 * Возвращает настройки для формирования табличного контрола
		 * @param string $param контрольный параметр
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			$result = [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'banners',
						'#__name' => 'lists'
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'banners',
						'#__name' => 'del',
						'aliases' => 'tree_delete_element,delete,del'
					],
					[
						'title' => getLabel('smc-activity'),
						'module' => 'banners',
						'#__name' => 'activity',
						'aliases' => 'tree_set_activity,activity'
					]
				],
				'types' => [
					[
						'common' => 'true',
						'id' => 'banner'
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
					'avatar',
					'userpic',
					'user_settings_data',
					'user_dock',
					'orders_refs',
					'activate_code'
				],
				'default' => 'name[400px]'
			];

			$cmsController = cmsController::getInstance();

			if (!$cmsController->getModule('geoip') instanceof def_module) {
				$result['stoplist'][] = 'city_targeting_city';
				$result['stoplist'][] = 'city_targeting_is_active';
			}

			return $result;
		}

	}
