<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class UmiStubAdmin {

		use baseModuleAdmin;

		/** @var umiStub $module */
		public $module;

		/**
		 * Выводит список настроек на странице модуля
		 * и, если запрос на сохранение данных, сохраняет данные
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws requireAdminParamException
		 * @throws wrongParamException
		 * @throws Exception
		 */
		public function stub() {
			$settingsManager = $this->module->getAdminSettingsManager();
			$params = $settingsManager->getParams();

			if ($this->isSaveMode()) {
				if (isDemoMode()) {
					throw new publicAdminException(getLabel('label-stop-in-demo', 'autoupdate'));
				}

				$params = self::expectedParams($params);
				$settingsManager->setCommonParams($params['stub']);
				$settingsManager->setCustomParams($params);
				$this->chooseRedirect();
			}

			$this->setConfigResult($params);
		}

		/**
		 * Возвращает данные вкладки "Черный список"
		 * @throws Exception
		 */
		public function whiteList() {
			$this->getTabData('ip-whitelist');
		}

		/**
		 * Возвращает данные вкладки "Черный список"
		 * @throws Exception
		 */
		public function blackList() {
			$this->getTabData('ip-blacklist');
		}

		/**
		 * Возвращает данные конфигурации административного интерфейса
		 * @param string $param контрольный параметр
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			switch ($param) {
				case 'whiteList':
					$guid = 'ip-whitelist';
					break;
				default:
					$guid = 'ip-blacklist';
			}

			$type = umiObjectTypesCollection::getInstance()->getTypeByGUID($guid);

			$result = [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'umiStub',
						'#__name' => $param
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'umiStub',
						'#__name' => 'del',
						'aliases' => 'tree_delete_element,delete,del'
					]
				]
			];

			/** @var iUmiObjectType $type */
			if ($type instanceof iUmiObjectType) {
				$result['types'] = [
					['common' => 'true', 'id' => $type->getId()]
				];
			}

			$result['default'] = 'name[400px]|domain_id[250px]';

			return $result;
		}

		/**
		 * Возвращает данные для создания формы добавления ip-адреса,
		 * если передан $_REQUEST['param1'] = do, то создает объект ip-адреса
		 * и перенаправляет страницу, где ее можно отредактировать.
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws wrongElementTypeAdminException
		 */
		public function add() {
			$guid = (string) getRequest('param0');
			$name = (string) getRequest('name');

			$this->setHeaderLabel('header-umiStub-add-' . $guid);
			$inputData = [
				'type' => $guid,
				'name' => $name
			];

			if ($this->isSaveMode('param1')) {
				$this->module->validateIpAddress(
					$name,
					$this->getDomainId(),
					$guid
				);
				$object = $this->saveAddedObjectData($inputData);
				$this->chooseRedirect($this->module->pre_lang . '/admin/umiStub/edit/' . $object->getId() . '/');
			}

			$this->setDataType('form');
			$this->setActionType('create');
			$data = $this->prepareData($inputData, 'object');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает данные для создания формы редактирования ip-адреса.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do,
		 * то сохраняет изменения ip-адреса и производит перенаправление.
		 * Адрес перенаправление зависит от режима кнопки "Сохранить".
		 * @throws coreException
		 * @throws expectObjectException
		 * @throws selectorException
		 * @throws publicAdminException
		 */
		public function edit() {
			$object = $this->expectObject('param0');
			$this->setHeaderLabel('header-umiStub-edit-' . $this->getObjectTypeMethod($object));

			if ($this->isSaveMode('param1')) {
				$objectId = $object->getId();
				$this->module->validateIpAddress(
					$object->getName(),
					$this->getDomainId($objectId),
					$object->getType()->getGUID(),
					$objectId
				);
				$this->saveEditedObjectData($object);
				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('modify');
			$data = $this->prepareData($object, 'object');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Удаляет IP-адрес
		 * @throws coreException
		 * @throws expectObjectException
		 * @throws publicAdminException
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
						'ip-whitelist',
						'ip-blacklist',
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
		 * Возвращает идентификатор домена IP адреса
		 * @param int|bool $id идентифкатор объекта
		 * @return bool|int
		 */
		private function getDomainId($id = false) {
			$data = getRequest('data');
			$key = $id ?: 'new';
			$info = isset($data[$key]) ? $data[$key] : false;

			return isset($info['domain_id']) ? $info['domain_id'] : false;
		}

		/**
		 * Возвращает данные для отрисовки вкладки
		 * @param string $method метод вкладки
		 * @return bool
		 * @throws coreException
		 * @throws selectorException
		 */
		private function getTabData($method) {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$limit = getRequest('per_page_limit');
			$currentPage = (int) getRequest('p');
			$offset = $limit * $currentPage;

			$sel = new selector('objects');
			$sel->types('object-type')->name('umiStub', $method);
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result(), 'objects');
			$this->setData($data, $sel->length());
			$this->doData();
			return true;
		}
	}