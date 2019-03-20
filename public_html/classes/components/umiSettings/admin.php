<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class UmiSettingsAdmin implements iModulePart {

		use baseModuleAdmin;
		use tModulePart;

		/**
		 * Возвращает данные для построения формы добавления настроек.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do, то добавляет настройки.
		 * @throws RequiredPropertyHasNoValueException
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws wrongElementTypeAdminException
		 */
		public function create() {
			$inputData = [
				'type' => getRequest('param0'),
				'type-id' => getRequest('type-id'),
				'allowed-element-types' => [
					'settings'
				]
			];

			if ($this->isSaveMode('param1')) {
				if (isset($_REQUEST['data']['new']['name'])) {
					$inputData['name'] = $_REQUEST['data']['new']['name'];
				}

				$object = $this->saveAddedObjectData($inputData);

				if (!$object->isFilled()) {
					$object->setValue('lang_id', Service::LanguageDetector()->detectId());
					$object->setValue('domain_id', Service::DomainDetector()->detectId());
					$object->commit();
				}

				$redirectPath = $this->getModule()
					->getObjectEditLink($object->getId());
				$this->chooseRedirect($redirectPath);
			}

			$this->setDataType('form');
			$this->setActionType('create');
			$data = $this->prepareData($inputData, 'object');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает список настроек для текущего языка и домена
		 * @throws RequiredPropertyHasNoValueException
		 * @throws coreException
		 * @throws selectorException
		 */
		public function read() {
			if ($this->getModule()->ifNotXmlMode()) {
				$this->setDataSetDirectCallMessage();
				return;
			}

			$limit = $this->getLimit();
			$offset = $this->getOffset($limit);

			$query = new selector('objects');
			$query->types('object-type')->guid(umiSettings::ROOT_TYPE_GUID);
			$query->where('domain_id')->equals($this->getDomainId());
			$query->where('lang_id')->equals($this->getLanguageId());
			$query->limit($offset, $limit);
			selectorHelper::detectFilters($query);

			$this->setDataType('list');
			$this->setActionType('view');
			$settingsList = $this->prepareData($query->result(), 'objects');
			$this->setData($settingsList, $query->length());
			$this->setDataRangeByPerPage($limit, $this->getCurrentPage());
			$this->doData();
		}

		/**
		 * Возвращает данные для построения формы редактирования настроек.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do, то сохраняет изменения настроек.
		 * @throws coreException
		 * @throws expectObjectException
		 */
		public function update() {
			$inputData = [
				'object' => $this->expectObject('param0', true),
				'allowed-element-types' => [
					'settings'
				]
			];

			if ($this->isSaveMode('param1')) {
				$this->saveEditedObjectData($inputData);
				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('modify');
			$data = $this->prepareData($inputData, 'object');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Удаляет настройки
		 * @throws coreException
		 * @throws expectObjectException
		 * @throws wrongElementTypeAdminException
		 */
		public function delete() {
			$settingsList = $this->getEntitiesDataForDeleting();
			$inputData = [
				'allowed-element-types' => [
					'settings'
				]
			];

			foreach ($settingsList as $settingsId) {
				$inputData['object'] = $this->expectObject($settingsId, false, true);
				$this->deleteObject($inputData);
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($settingsList, 'objects');
			$this->setData($data);
			$this->doData();
		}

		/** @inheritdoc */
		public function getDatasetConfiguration($param = '') {
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'umiSettings',
						'#__name' => 'read'
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'umiSettings',
						'#__name' => 'delete',
						'aliases' => 'tree_delete_element,delete,del'
					]
				],
				'types' => [
					[
						'common' => 'true',
						'id' => 'settings'
					]
				],
				'stoplist' => [
					'domain_id',
					'lang_id'
				],
				'default' => 'name[250px]|custom_id[250px]'
			];
		}
	}
