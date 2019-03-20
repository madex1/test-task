<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class WebformsAdmin {

		use baseModuleAdmin;

		/** @var webforms $module */
		public $module;

		/**
		 * Возвращает список адресов для форм
		 * @return bool|void
		 * @throws coreException
		 * @throws selectorException
		 */
		public function addresses() {
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
			$sel = new selector('objects');
			$sel->types('object-type')->name('webforms', 'address');
			$sel->limit($offset, $limit);

			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result(), 'objects');
			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Возвращает данные для создания формы добавления адреса.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то создает адрес и перенаправляет на страницу, где его
		 * можно отредактировать.
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws wrongElementTypeAdminException
		 */
		public function address_add() {
			$inputData = [
				'type' => 'address'
			];

			if ($this->isSaveMode()) {
				if (
					!isset($_REQUEST['data']['new']['address_description']) ||
					!isset($_REQUEST['data']['new']['address_list']) ||
					!mb_strlen($_REQUEST['data']['new']['address_description']) ||
					!mb_strlen($_REQUEST['data']['new']['address_list'])
				) {
					throw new publicAdminException(getLabel('error-required_fields'));
				}

				$oObject = $this->saveAddedObjectData($inputData);
				$this->chooseRedirect('/admin/webforms/address_edit/' . $oObject->getId() . '/');
			}
			$this->setDataType('form');
			$this->setActionType('create');
			$data = $this->prepareData($inputData, 'object');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает данные для создания формы редактирования адреса.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do,
		 * то сохраняет изменения адреса и производит перенаправление.
		 * Адрес перенаправление зависит от режима кнопки "Сохранить".
		 * @throws coreException
		 * @throws expectObjectException
		 * @throws publicAdminException
		 */
		public function address_edit() {
			$object = $this->expectObject('param0');
			$id = (int) getRequest('param0');

			if ($this->isSaveMode('param1')) {
				if (
					!isset($_REQUEST['data'][$id]['address_description']) ||
					!isset($_REQUEST['data'][$id]['address_list']) ||
					!mb_strlen($_REQUEST['data'][$id]['address_description']) ||
					!mb_strlen($_REQUEST['data'][$id]['address_list'])
				) {
					throw new publicAdminException(getLabel('error-required_fields'));
				}
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
		 * Удаляет адрес и перенаправляет на страницу со списком адресов
		 * @throws coreException
		 */
		public function address_delete() {
			$iObjectId = (int) getRequest('param0');
			umiObjectsCollection::getInstance()->delObject($iObjectId);
			$this->chooseRedirect('/admin/webforms/addresses/');
		}

		/**
		 * Возвращает список шаблонов для писем
		 * @return bool|void
		 * @throws coreException
		 * @throws selectorException
		 */
		public function templates() {
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

			$sel = new selector('objects');
			$sel->types('object-type')->name('webforms', 'template');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result(), 'objects');
			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Возвращает данные для создания формы добавления шаблона письма.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то создает шаблон и перенаправляет на страницу, где его
		 * можно отредактировать.
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws wrongElementTypeAdminException
		 */
		public function template_add() {
			$oTypes = umiObjectTypesCollection::getInstance();
			$iBaseId = $oTypes->getTypeIdByHierarchyTypeName('webforms', 'form');
			$inputData = [
				'type' => 'template'
			];

			if ($this->isSaveMode()) {
				$iFormId = (int) getRequest('system_form_id');
				$form = $oTypes->getType($iFormId);

				if (!$iFormId || !$form instanceof iUmiObjectType) {
					throw new publicAdminException(getLabel('error-no-form-binded'));
				}

				$inputData['name'] = $form->getName();
				$oTemplate = $this->saveAddedObjectData($inputData);
				$oTemplate->setValue('form_id', $iFormId);
				$oTemplate->commit();
				$this->chooseRedirect('/admin/webforms/template_edit/' . $oTemplate->getId() . '/');
			}

			$this->setDataType('form');
			$this->setActionType('create');
			$data = $this->prepareData($inputData, 'object');

			$data['nodes:group'][1] = [
				'attribute:name' => 'BindToForm',
				'attribute:title' => getLabel('label-belonging-to-form'),
				'attribute:base_type' => $iBaseId,
				'attribute:selected_type' => ''
			];

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает данные для создания формы редактирования шаблона письма.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do,
		 * то сохраняет изменения шаблона и производит перенаправление.
		 * Адрес перенаправление зависит от режима кнопки "Сохранить".
		 * @throws coreException
		 * @throws expectObjectException
		 * @throws publicAdminException
		 */
		public function template_edit() {
			$oTypes = umiObjectTypesCollection::getInstance();
			$iBaseId = $oTypes->getTypeIdByHierarchyTypeName('webforms', 'form');
			$object = $this->expectObject('param0');

			if ($this->isSaveMode('param1')) {
				$iFormId = (int) getRequest('system_form_id');
				$form = $oTypes->getType($iFormId);

				if (!$iFormId || !$form instanceof iUmiObjectType) {
					throw new publicAdminException(getLabel('error-no-form-binded'));
				}

				$this->saveEditedObjectData($object);
				$object->setName($form->getName());
				$object->setValue('form_id', $iFormId);
				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('modify');
			$data = $this->prepareData($object, 'object');

			$data['nodes:group'][1] = [
				'attribute:name' => 'BindToForm',
				'attribute:title' => getLabel('label-belonging-to-form'),
				'attribute:base_type' => $iBaseId,
				'attribute:selected_type' => $object->getValue('form_id')
			];

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Удаляет шаблон письма и перенаправляет на страницу со списком шаблонов
		 * @throws coreException
		 */
		public function template_delete() {
			$iObjectId = (int) getRequest('param0');
			umiObjectsCollection::getInstance()->delObject($iObjectId);
			$this->chooseRedirect('/admin/webforms/templates/');
		}

		/**
		 * Возвращает список сообщений форм
		 * @return bool|void
		 * @throws coreException
		 * @throws selectorException
		 */
		public function messages() {
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

			$sel = new selector('objects');

			if (!$types = getRequest('object_type')) {
				$types = getRequest('param0');
			}

			if (is_numeric($types) || is_array($types)) {
				$sel->types('object-type')->id($types);
			} else {
				$sel->types('object-type')->name('webforms', 'form');
			}

			$sel->order('id')->desc();
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);

			$data = $this->prepareData($sel->result(), 'objects');
			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Возвращает данные для формирования страницы просмотра сообщения формы
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function message() {
			$iObjectId = (int) getRequest('param0');
			$oCollection = umiObjectsCollection::getInstance();
			$oMessage = $oCollection->getObject($iObjectId);
			$this->module->validateEntityByTypes($oMessage, [
				'module' => 'webforms'
			], true);

			$eventPoint = new umiEventPoint('sysytemBeginObjectEdit');
			$eventPoint->setMode('before');
			$object = $oCollection->getObject($iObjectId);
			$eventPoint->addRef('object', $object);
			$eventPoint->call();

			$sMessage = $this->module->formatMessage($iObjectId);
			$sAddress = $oMessage->getName();
			$sForm = umiObjectTypesCollection::getInstance()->getType($oMessage->getTypeId())->getName();
			$sIP = $oMessage->getValue('sender_ip');
			$oDate = $oMessage->getValue('sending_time');

			$params = [
				'Message' => [
					'string:message' => $sMessage,
					'string:address' => $sAddress,
					'string:form' => $sForm,
					'string:date' => ($oDate instanceof umiDate) ? $oDate->getFormattedDate() : '',
					'string:ip' => $sIP,
					'int:id' => $iObjectId
				]
			];

			$this->setConfigResult($params, 'view');
		}

		/**
		 * Удаляет сообщение формы и перенаправляет на страницу со списком сообщений
		 * @throws coreException
		 */
		public function message_delete() {
			$iObjectId = (int) getRequest('param0');
			umiObjectsCollection::getInstance()->delObject($iObjectId);
			$this->chooseRedirect('/admin/webforms/messages/');
		}

		/**
		 * Возвращает список форм
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function forms() {
			$curr_page = (int) getRequest('p');
			$per_page = getRequest('per_page_limit');
			$oTypes = umiObjectTypesCollection::getInstance();
			$iBaseTID = $oTypes->getTypeIdByHierarchyTypeName('webforms', 'form');

			if ($iBaseTID === false) {
				throw new publicAdminException('No form base type was found');
			}

			if (isset($_REQUEST['search-all-text'][0])) {
				$searchAllText = array_extract_values($_REQUEST['search-all-text']);
				foreach ($searchAllText as $i => $v) {
					$searchAllText[$i] = mb_strtolower($v);
				}
			} else {
				$searchAllText = false;
			}

			$types = $oTypes;
			$sub_types = $types->getSubTypesList($iBaseTID);

			$tmp = [];
			foreach ($sub_types as $typeId) {
				$type = $types->getType($typeId);

				if (!$type instanceof iUmiObjectType) {
					continue;
				}

				$name = $type->getName();

				if ($searchAllText) {
					$match = false;

					foreach ($searchAllText as $searchString) {
						if (strstr(mb_strtolower($name), $searchString) !== false) {
							$match = true;
						}
					}

					if (!$match) {
						continue;
					}
				}
				$tmp[$typeId] = $name;
			}

			if (isset($_REQUEST['order_filter']['name'])) {
				natsort($tmp);

				if ($_REQUEST['order_filter']['name'] == 'desc') {
					$tmp = array_reverse($tmp, true);
				}
			}

			$sub_types = array_keys($tmp);
			unset($tmp);
			$sub_types = $this->excludeNestedTypes($sub_types);

			$total = umiCount($sub_types);
			$aTypes = array_slice($sub_types, $curr_page * $per_page, $per_page);

			$this->setDataType('list');
			$this->setActionType('view');
			$this->setDataRange($per_page, $per_page * $curr_page);
			$data = $this->prepareData($aTypes, 'types');

			$data['nodes:basetype'] = [
				[
					'attribute:id' => $iBaseTID
				]
			];

			$this->setData($data, $total);
			$this->doData();
		}

		/**
		 * Возвращает данные для создания формы добавления формы.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do,
		 * то добавляет форму и перенаправляет на страницу, где
		 * ее можно отредактировать.
		 * @throws coreException
		 */
		public function form_add() {
			$oTypes = umiObjectTypesCollection::getInstance();
			$iBaseTID = $oTypes->getTypeIdByHierarchyTypeName('webforms', 'form');

			if ($this->isSaveMode('param1')) {
				if (!isset($_REQUEST['data']['name']) || !mb_strlen($_REQUEST['data']['name'])) {
					$this->chooseRedirect($this->module->pre_lang . '/admin/webforms/forms/');
				}

				$iTypeId = $oTypes->addType($iBaseTID, $_REQUEST['data']['name']);
				$this->setAddressFormId($iTypeId);
				$this->chooseRedirect($this->module->pre_lang . '/admin/webforms/form_edit/' . $iTypeId . '/');
			}

			$this->setDataType('form');
			$this->setActionType('create');
			$data = $this->prepareData($iBaseTID, 'type');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает данные для создания формы редактирования формы.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do,
		 * то сохраняет изменения формы и производит перенаправление.
		 * Адрес перенаправление зависит от режима кнопки "Сохранить".
		 * @throws publicAdminException
		 */
		public function form_edit() {
			$iTypeId = (int) getRequest('param0');
			/** @var data|DataAdmin $oModuleData */
			$oModuleData = cmsController::getInstance()->getModule('data');

			if (!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}

			if ($this->isSaveMode('param1')) {
				$this->setAddressFormId($iTypeId);
			}

			$hierarchyType = umiHierarchyTypesCollection::getInstance()->getTypeByName('webforms', 'form');

			if ($hierarchyType instanceof iUmiHierarchyType) {
				$_REQUEST['data']['hierarchy_type_id'] = $hierarchyType->getId();
			}

			Service::Session()->set('referer', '/admin/webforms/forms/');

			$this->setDataType('form');
			$this->setActionType('modify');
			$oModuleData->type_edit();
		}

		/**
		 * Удаляет форму и перенаправляет на страницу со списком форм
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function form_delete() {
			$type_id = (int) getRequest('param0');
			umiObjectTypesCollection::getInstance()->delType($type_id);
			$this->chooseRedirect('/admin/webforms/forms/');
		}

		/**
		 * Устанавливает идентификатор формы у адреса
		 * @param int $iFormId идентификатор формы
		 */
		public function setAddressFormId($iFormId) {
			$aData = getRequest('data');
			$aObjColl = umiObjectsCollection::getInstance();
			$this->unsetAddressFormId($iFormId);

			if (isset($aData['address']) && $aData['address'] != '') {
				$oAddress = $aObjColl->getObject($aData['address']);
				$sFormsId = (string) $oAddress->getValue('form_id');
				$sFormsIdOld = $sFormsId;

				if ($sFormsId !== '') {
					$aFormsId = explode(',', $sFormsId);
					if (!in_array($iFormId, $aFormsId)) {
						$aFormsId[] = $iFormId;
						$sFormsId = implode(',', $aFormsId);
					}
				} else {
					$sFormsId = $iFormId;
				}

				if ($sFormsId != $sFormsIdOld) {
					$oAddress->setValue('form_id', $sFormsId);
					$oAddress->commit();
				}
			}
		}

		/**
		 * Удаляет идентификатор формы из адреса
		 * @param int $iFormId идентификатор формы
		 * @throws selectorException
		 */
		public function unsetAddressFormId($iFormId) {
			$sel = new selector('objects');
			$sel->types('object-type')->name('webforms', 'address');
			$sel->where('form_id')->like('%' . $iFormId . '%');
			/** @var iUmiObject $oAddress */
			foreach ($sel->result() as $oAddress) {
				$aFormsId = explode(',', $oAddress->getValue('form_id'));
				if (in_array($iFormId, $aFormsId)) {
					$aFormsId = array_diff($aFormsId, [$iFormId]);
					$oAddress->setValue('form_id', implode(',', $aFormsId));
					$oAddress->commit();
				}
			}
		}

		/**
		 * Создает страницу с формой и перенаправляет
		 * на страницу, где ее можно отредактировать.
		 * @throws coreException
		 */
		public function placeOnPage() {
			$formId = getRequest('param0');

			if (!$formId) {
				$this->chooseRedirect(getServer('HTTP_REFERER'));
			}

			$form = umiObjectTypesCollection::getInstance()->getType($formId);
			$typeid = umiHierarchyTypesCollection::getInstance()->getTypeByName('webforms', 'page')->getId();
			$formName = $form->getName();
			$hierarchy = umiHierarchy::getInstance();

			$pageId = $hierarchy->addElement(0, $typeid, $formName, $formName);
			permissionsCollection::getInstance()->setDefaultPermissions($pageId);

			$page = $hierarchy->getElement($pageId);
			$page->setIsActive();
			$page->setValue('form_id', $formId);
			$page->setValue('title', $formName);
			$page->setValue('h1', $formName);
			$page->setValue('content', '%webforms add(' . $formId . ')%');
			$page->commit();
			$this->chooseRedirect('/admin/content/edit/' . $pageId . '/');
		}

		/** Возвращает данные страниц с формой по id */
		public function getPages() {
			$pages = getRequest('id');
			$this->setDataType('list');
			$this->setActionType('view');
			$data = [];

			if ($pages && !empty($pages)) {
				foreach ($pages as $id) {
					$p = $this->getBindedPage($id);

					$data['nodes:page'][] = array_merge($p['page'], [
						'attribute:form' => $id
					]);
				}
			}

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает данные страницы с формой, к которой привязана заданная форма
		 * @param bool|int $formId идентификатор формы
		 * @return array
		 * @throws coreException
		 */
		public function getBindedPage($formId = false) {
			$umiHierarchy = umiHierarchy::getInstance();

			if ($formId === false) {
				$formId = ($tmp = getRequest('param0')) ? $tmp : $formId;
			}

			if (!$formId) {
				return [
					'page' => [
						'attribute:id' => 0
					]
				];
			}

			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('webforms', 'page');
			$sel->where('form_id')->equals($formId);
			$sel->where('is_active')->equals([0, 1]);
			$sel->option('return')->value('id');
			$sel->limit(0, 1);

			$result = $sel->result();

			if (umiCount($result)) {
				$info = array_shift($result);
				$pageId = $info['id'];

				return [
					'page' => [
						'attribute:id' => $pageId,
						'attribute:href' => $umiHierarchy->getPathById($pageId)
					]
				];
			}

			return [
				'page' => [
					'attribute:id' => 0
				]
			];
		}

		/**
		 * Удаляет объекты (шаблоны, адреса, сообщения) модуля
		 * @throws coreException
		 */
		public function del() {
			$objects = getRequest('element');
			if (!is_array($objects) && $objects) {
				$objects = [$objects];
			}
			if (is_array($objects) && !empty($objects)) {
				$collection = umiObjectsCollection::getInstance();

				foreach ($objects as $objectId) {
					$collection->delObject($objectId);
				}
			}
		}

		/**
		 * Удаляет формы модуля
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function delType() {
			$objects = getRequest('element');
			if (!is_array($objects) && $objects) {
				$objects = [$objects];
			}
			if (is_array($objects) && !empty($objects)) {
				$collection = umiObjectTypesCollection::getInstance();

				foreach ($objects as $objectId) {
					$collection->delType($objectId);
				}
			}
		}

		/**
		 * Возвращает настройки для формирования табличного контрола
		 * @param string $param контрольный параметр
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			$objectTypes = umiObjectTypesCollection::getInstance();
			switch ($param) {
				case 'templates': {
					$loadMethod = 'templates';
					$delMethod = 'del';
					$typeId = $objectTypes->getTypeIdByHierarchyTypeName('webforms', 'template');
					$defaults = 'name[400px]';
					break;
				}
				case 'messages' : {
					$loadMethod = 'messages';
					$delMethod = 'del';
					$typeId = getRequest('type_id') ?: $objectTypes->getTypeIdByHierarchyTypeName('webforms', 'form');
					$defaults = 'name[400px]|destination_address[250px]|sending_time[250px]';
					break;
				}
				case 'forms' : {
					$loadMethod = 'forms';
					$delMethod = 'delType';
					$typeId = $objectTypes->getTypeIdByHierarchyTypeName('webforms', 'form');
					$defaults = 'name[400px]|page[250px,static]';
					break;
				}
				default: {
					$loadMethod = 'addresses';
					$delMethod = 'del';
					$typeId = $objectTypes->getTypeIdByHierarchyTypeName('webforms', 'address');
					$defaults = 'name[400px]|address_description[250px]';
				}
			}
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'webforms',
						'#__name' => $loadMethod
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'webforms',
						'#__name' => $delMethod,
						'aliases' => 'tree_delete_element,delete,del'
					]
				],
				'types' => [
					[
						'common' => 'true',
						'id' => $typeId
					]
				],
				'stoplist' => [
					'form_id',
					'rate_voters',
					'rate_sum',
					'destination_address',
					'from_email_template',
					'from_template',
					'subject_template',
					'master_template',
					'autoreply_from_email_template',
					'autoreply_from_template',
					'autoreply_subject_template',
					'autoreply_template'
				],
				'default' => $defaults
			];
		}

		/**
		 * Возвращает список макросов для шаблона письма
		 * @param int $templateFormId ид формы обратной связи, к которой относится шаблон
		 * @return array|mixed|void
		 * @throws publicAdminException если ид формы обратной связи не является числом
		 * @throws publicAdminException если не удалось получить объект типа данных по ид формы обратной связи
		 * @throws publicAdminException если не удалось получить иерархические тип форм обратной связи
		 * @throws publicAdminException если тип данных, полученный по ид формы обратной связи, не является формой обратной
		 *     связи
		 */
		public function getTemplateMacros($templateFormId = null) {
			$templateFormId = ($templateFormId === null) ? getRequest('param0') : $templateFormId;

			if (!is_numeric($templateFormId)) {
				throw new publicAdminException(__METHOD__ . ': wrong template form id given: ' . $templateFormId);
			}

			$objectTypes = umiObjectTypesCollection::getInstance();
			$form = $objectTypes->getType($templateFormId);

			if (!$form instanceof iUmiObjectType) {
				throw new publicAdminException(__METHOD__ . ': cant get form by id = ' . $templateFormId);
			}

			$formHierarchyTypeId = (int) $form->getHierarchyTypeId();
			$umiTypesHelper = umiTypesHelper::getInstance();
			$webFormsHierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName('webforms', 'form');

			if (!is_numeric($webFormsHierarchyTypeId)) {
				throw new publicAdminException(__METHOD__ . ': cant get hierarchy type by name webforms/form');
			}

			if ($formHierarchyTypeId !== $webFormsHierarchyTypeId) {
				throw new publicAdminException(__METHOD__ . ': type with id = ' . $templateFormId . ' is not a form');
			}

			$umiTypesHelper = umiTypesHelper::getInstance();
			$formFieldsData = $umiTypesHelper->getFieldsByObjectTypeIds($templateFormId);

			$result = [];
			$total = 0;

			if (isset($formFieldsData[$templateFormId])) {
				$formFieldsData = $formFieldsData[$templateFormId];
				$total = umiCount($formFieldsData);
			}

			if ($total == 0) {
				$result['total'] = $total;
				return $result;
			}

			$items = [];

			foreach ($formFieldsData as $name => $id) {
				$item = [];
				$item['attribute:id'] = $id;
				$item['node:value'] = '%' . $name . '%';
				$items[] = webforms::parseTemplate([], $item);
			}

			$result['subnodes:items'] = $result['void:lines'] = $items;
			$result['total'] = $total;
			$result = webforms::parseTemplate([], $result);

			$this->setData($result);
			$this->doData();
		}

		/**
		 * @alias DataAdmin::type_group_add()
		 * @throws publicAdminException
		 */
		public function type_group_add() {
			$cmsController = cmsController::getInstance();
			/** @var data|DataAdmin $oModuleData */
			$oModuleData = $cmsController->getModule('data');

			if (!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}

			$formId = (int) getRequest('param0');
			Service::Session()->set('referer', '/admin/webforms/form_edit/' . $formId . '/');

			$cmsController->calculateRefererUri();
			$oModuleData->type_group_add('/admin/webforms/type_group_edit/');
		}

		/**
		 * @alias DataAdmin::type_group_edit()
		 * @throws publicAdminException
		 */
		public function type_group_edit() {
			$cmsController = cmsController::getInstance();
			/** @var data|DataAdmin $oModuleData */
			$oModuleData = $cmsController->getModule('data');

			if (!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}

			$formId = (int) getRequest('param1');
			Service::Session()->set('referer', '/admin/webforms/form_edit/' . $formId . '/');

			$cmsController->calculateRefererUri();
			$oModuleData->type_group_edit();
		}

		/**
		 * @alias DataAdmin::type_field_add()
		 * @throws publicAdminException
		 */
		public function type_field_add() {
			$cmsController = cmsController::getInstance();
			/** @var data|DataAdmin $oModuleData */
			$oModuleData = $cmsController->getModule('data');

			if (!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}

			$formId = (int) getRequest('param1');
			Service::Session()->set('referer', '/admin/webforms/form_edit/' . $formId . '/');

			$cmsController->calculateRefererUri();
			$oModuleData->type_field_add('/admin/webforms/type_field_edit/');
		}

		/**
		 * @alias DataAdmin::type_field_edit()
		 * @throws publicAdminException
		 */
		public function type_field_edit() {
			$cmsController = cmsController::getInstance();
			/** @var data|DataAdmin $oModuleData */
			$oModuleData = $cmsController->getModule('data');

			if (!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}

			$formId = (int) getRequest('param1');
			Service::Session()->set('referer', '/admin/webforms/form_edit/' . $formId . '/');

			$cmsController->calculateRefererUri();
			$oModuleData->type_field_edit();
		}

		/**
		 * @alias DataAdmin::json_move_field_after()
		 * @throws publicAdminException
		 */
		public function json_move_field_after() {
			$cmsController = cmsController::getInstance();
			/** @var data|DataAdmin $oModuleData */
			$oModuleData = $cmsController->getModule('data');

			if (!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}

			$oModuleData->json_move_field_after();
		}

		/**
		 * @alias DataAdmin::json_move_group_after()
		 * @throws publicAdminException
		 */
		public function json_move_group_after() {
			$cmsController = cmsController::getInstance();
			/** @var data|DataAdmin $oModuleData */
			$oModuleData = $cmsController->getModule('data');

			if (!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}

			$oModuleData->json_move_group_after();
		}

		/**
		 * @alias DataAdmin::json_delete_field()
		 * @throws publicAdminException
		 */
		public function json_delete_field() {
			$cmsController = cmsController::getInstance();
			/** @var data|DataAdmin $oModuleData */
			$oModuleData = $cmsController->getModule('data');

			if (!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}

			$oModuleData->json_delete_field();
		}

		/**
		 * @alias DataAdmin::json_delete_group()
		 * @throws publicAdminException
		 */
		public function json_delete_group() {
			$cmsController = cmsController::getInstance();
			/** @var data|DataAdmin $oModuleData */
			$oModuleData = $cmsController->getModule('data');

			if (!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}

			$oModuleData->json_delete_group();
		}
	}


