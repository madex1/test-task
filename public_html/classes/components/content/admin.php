<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class ContentAdmin {

		use baseModuleAdmin;

		/** @var content $module */
		public $module;

		/**
		 * Возвращает список объектов по идентификатору объектного типа
		 * @param int $type_id идентификатор объектного типа
		 * @return array
		 * @throws coreException
		 */
		public function getObjectsByTypeList($type_id) {
			$objectsCollection = umiObjectsCollection::getInstance();
			$objects = $objectsCollection->getGuidedItems($type_id);

			$items = [];

			foreach ($objects as $item_id => $item_name) {
				$items[] = [
					'attribute:id' => $item_id,
					'node:name' => $item_name
				];
			}

			return [
				'items' => [
					'nodes:item' => $items
				]
			];
		}

		/**
		 * Возвращает список объектов по имени и расширению
		 * иерархического типа
		 * @param string $module имя иерархического типа
		 * @param string $method расширение иерархического типа
		 * @return array
		 * @throws coreException
		 */
		public function getObjectsByBaseTypeList($module, $method) {
			$objectTypesCollection = umiObjectTypesCollection::getInstance();
			$objectsCollection = umiObjectsCollection::getInstance();

			$type_id = $objectTypesCollection->getTypeIdByHierarchyTypeName($module, $method);

			$objects = $objectsCollection->getGuidedItems($type_id);

			$items = [];
			foreach ($objects as $item_id => $item_name) {
				$items[] = [
					'attribute:id' => $item_id,
					'node:name' => $item_name
				];
			}
			return ['items' => ['nodes:item' => $items]];
		}

		/**
		 * Возвращает список страниц по имени и расширению
		 * иерархического типа
		 * @param string $module имя иерархического типа
		 * @param string $method расширение иерархического типа
		 * @return array
		 * @throws publicException
		 * @throws selectorException
		 */
		public function getPagesByBaseTypeList($module, $method) {
			$hierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();
			$type = $hierarchyTypesCollection->getTypeByName($module, $method);

			/* @var iUmiHierarchyType $type */
			if ($type instanceof iUmiHierarchyType) {
				$typeId = $type->getId();
			} else {
				throw new publicException("Hierarchy type {$module}::{$method} doesn't exist");
			}

			$sel = new selector('pages');
			$sel->types('hierarchy-type')->id($typeId);
			$result = $sel->result();
			$pages = [];

			foreach ($result as $element) {
				if ($element instanceof iUmiHierarchyElement) {
					$pages[] = $element;
				}
			}

			return ['pages' => ['nodes:page' => $pages]];
		}

		/** Возвращает список шаблонов сайта */
		public function domainTemplates() {
			$domains = Service::DomainCollection();
			$langs = Service::LanguageCollection();
			$templates = templatesCollection::getInstance();

			$data = [];
			foreach ($domains->getList() as $domain) {
				/** @var iDomain $domain */
				$domainId = $domain->getId();

				foreach ($langs->getList() as $lang) {
					/** @var iLang $lang */
					$langId = $lang->getId();

					foreach ($templates->getTemplatesList($domainId, $langId) as $template) {
						$data['templates']['nodes:template'][] = $template;
					}
				}
			}

			foreach ($domains->getList() as $domain) {
				$data['domains']['nodes:domain'][] = $domain;
			}

			foreach ($langs->getList() as $lang) {
				$data['langs']['nodes:lang'][] = $lang;
			}

			$this->setDataType('list');
			$this->setActionType('view');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает список доменов системы для построения деревьев
		 * @throws coreException
		 */
		public function sitetree() {
			$domains = $this->getUserVisibleDomainList();
			$data = $this->prepareData($domains, 'domains');
			$this->setDataType('list');
			$this->setActionType('view');
			$this->setData($data, count($domains));
			$this->doData();
		}

		/**
		 * Возвращает список видимых для текущего пользователя доменов
		 * @return iDomain[]
		 */
		public function getUserVisibleDomainList() {
			$umiPermissions = permissionsCollection::getInstance();
			$userId = Service::Auth()->getUserId();
			$user = umiObjectsCollection::getInstance()->getObject($userId);

			$favoriteDomainIdList = $user->getValue('favorite_domain_list');
			$favoriteDomainIdList = is_array($favoriteDomainIdList) ? $favoriteDomainIdList : [];
			$visibleDomainList = [];

			/** @var iDomain $domain */
			foreach (Service::DomainCollection()->getList() as $domain) {
				if ($favoriteDomainIdList && !in_array($domain->getId(), $favoriteDomainIdList)) {
					continue;
				}

				if (!$umiPermissions->isAllowedDomain($userId, $domain->getId())) {
					continue;
				}

				$visibleDomainList[] = $domain;
			}

			return $visibleDomainList;
		}

		/**
		 * Возвращает данные списка страниц контента для административной панели
		 * @return bool
		 * @throws coreException
		 * @throws selectorException
		 */
		public function tree() {
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
			$sel->types('hierarchy-type')->name('content', 'page');

			if (is_array(getRequest('rel')) && Service::Registry()->get('//modules/comments')) {
				$sel->types('hierarchy-type')->name('comments', 'comment');
			}

			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$data = $this->prepareData($sel->result(), 'pages');

			$this->setData($data, $sel->length());
			$this->setDataRangeByPerPage($limit, $currentPage);
			$this->doData();

			return true;
		}

		/**
		 * Возвращает форму создания страницы.
		 * Если передан ключевой параметр $_REQUEST['param2'] = do,
		 * то метод запустит добавление страницы
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function add() {
			$parent = $this->expectElement('param0');
			$type = (string) getRequest('param1');
			$inputData = [
				'type' => $type,
				'parent' => $parent,
				'type-id' => getRequest('type-id'),
				'allowed-element-types' => ['page', '']
			];

			$event = new umiEventPoint('contentAdminElementAdd');
			$event->addRef('inputData', $inputData);
			$event->call();

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
		 * Возвращает форму редактирования страницы.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do,
		 * то метод запустит сохранение страницы
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function edit() {
			$element = $this->expectElement('param0', true);
			$inputData = [
				'element' => $element,
				'allowed-element-types' => ['page', '']
			];

			$event = new umiEventPoint('contentAdminElementEdit');
			$event->addRef('inputData', $inputData);
			$event->call();

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
		 * Возвращает настройки системы.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то метод запустит сохранение настроек
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function config() {
			$umiTemplates = templatesCollection::getInstance();
			$langId = Service::LanguageDetector()->detectId();
			$result = [];

			foreach (Service::DomainCollection()->getList() as $domain) {
				$host = $domain->getHost();
				$domainId = $domain->getId();
				$result[$host] = [];
				$templateList = $umiTemplates->getTemplatesList($domainId, $langId);

				foreach ($templateList as $template) {
					$result[$host][] = $template;
				}
			}

			if ($this->isSaveMode()) {
				$this->saveEditedList('templates', $result);
				$this->chooseRedirect();
			}

			$this->setDataType('list');
			$this->setActionType('modify');
			$data = $this->prepareData($result, 'templates');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Удаляет страницу
		 * @throws coreException
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function del() {
			$element = $this->expectElement('param0');

			$params = [
				'element' => $element,
				'allowed-element-types' => ['page', '']
			];

			$event = new umiEventPoint('contentAdminElementDel');
			$event->addRef('inputData', $params);
			$event->call();

			$this->deleteElement($params);
			$this->chooseRedirect();
		}

		/**
		 * Возвращает форму редактирования шаблона.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do,
		 * то метод запустит сохранение шаблона
		 * @throws coreException
		 */
		public function tpl_edit() {
			$id = (int) getRequest('param0');
			$template = templatesCollection::getInstance()->getTemplate($id);

			if ($this->isSaveMode('param1')) {
				$this->saveEditedTemplateData($template);
				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('modify');
			$data = $this->prepareData($template, 'template');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Валидирует текущего пользователя и отключает блокировку у страницы
		 * @throws publicAdminException
		 */
		public function unlock_page() {
			$pageId = getRequest('param0');

			if (permissionsCollection::getInstance()->isSv()) {
				throw new publicAdminException(getLabel('error-can-unlock-not-sv'));
			}

			$this->module->unlockPage($pageId);
		}

		/**
		 * Возвращает настройки управления контентом.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то метод запустит сохранение настроек.
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws requireAdminParamException
		 * @throws wrongParamException
		 */
		public function content_control() {
			$regedit = Service::Registry();
			$umiNotificationInstalled = cmsController::getInstance()
				->isModule('umiNotifications');
			$params = [
				'content_config' => [
					'bool:lock_pages' => false,
					'int:lock_duration' => 0,
					'bool:expiration_control' => false
				],
				'output_options' => [
					'int:elements_count_per_page' => null
				]
			];

			if ($umiNotificationInstalled) {
				$params['notifications']['boolean:use-umiNotifications'] = null;
			}

			if ($this->isSaveMode()) {
				$params = $this->expectParams($params);
				$regedit->set('//settings/lock_pages', $params['content_config']['bool:lock_pages']);
				$regedit->set('//settings/lock_duration', $params['content_config']['int:lock_duration']);
				$regedit->set('//settings/expiration_control', $params['content_config']['bool:expiration_control']);
				$regedit->set('//settings/elements_count_per_page', $params['output_options']['int:elements_count_per_page']);

				if ($umiNotificationInstalled) {
					$regedit->set('//modules/content/use-umiNotifications', $params['notifications']['boolean:use-umiNotifications']);
				}

				$this->switchGroupsActivity('svojstva_publikacii', (bool) $params['content_config']['bool:expiration_control']);
				$this->chooseRedirect();
			}

			$params['content_config']['bool:lock_pages'] = $regedit->get('//settings/lock_pages');
			$params['content_config']['int:lock_duration'] = $regedit->get('//settings/lock_duration');
			$params['content_config']['bool:expiration_control'] = $regedit->get('//settings/expiration_control');
			$params['output_options']['int:elements_count_per_page'] = $regedit->get('//settings/elements_count_per_page');

			if ($umiNotificationInstalled) {
				$params['notifications']['boolean:use-umiNotifications'] = $regedit->get('//modules/content/use-umiNotifications');
			}

			$this->setConfigResult($params);
		}

		/**
		 * Возвращает настройки табличного контрола
		 * @param string $param контрольный параметр
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			$loadMethod = 'load_tree_node';
			$deleteMethod = 'tree_delete_element';
			$activityMethod = 'tree_set_activity';

			$types = [];
			if ($param == 'tree') {
				$types = [
					'types' => [
						[
							'common' => 'true',
							'id' => 'page'
						]
					]
				];
				$loadMethod = $param;
			}

			$result = [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'content',
						'#__name' => $loadMethod
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'content',
						'#__name' => $deleteMethod,
						'aliases' => 'tree_delete_element,delete,del'
					],
					[
						'title' => getLabel('smc-activity'),
						'module' => 'content',
						'#__name' => $activityMethod,
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
					]
				],
				'default' => 'name[400px]'
			];

			if (!empty($types)) {
				$result += $types;
			}

			return $result;
		}

		/**
		 * Возвращает данные для включения быстрого
		 * редактирования поля в табличном контроле
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function get_editable_region() {
			$entityId = getRequest('param0');
			$propName = getRequest('param1');

			$isObject = (bool) getRequest('is_object');
			if ($isObject) {
				$entity = umiObjectsCollection::getInstance()->getObject($entityId);
			} else {
				$entity = umiHierarchy::getInstance()->getElement($entityId);
			}

			$this->checkPermissions($entity, $isObject);
			$result = false;

			if ($entity) {
				if ($propName == 'name') {
					$result = ['name' => $entity->getName()];
				} else {
					$object = $isObject ? $entity : $entity->getObject();

					$property = $object->getPropByName($propName);
					if (!$property instanceof iUmiObjectProperty) {
						throw new publicAdminException(getLabel('error-property-not-exists'));
					}

					$result = ['property' => $property];
					translatorWrapper::get($property);
					umiObjectPropertyWrapper::$showEmptyFields = true;
				}
			}

			if (!is_array($result)) {
				throw new publicAdminException(getLabel('error-entity-not-exists'));
			}

			$this->setData($result);
			$this->doData();
		}

		/**
		 * Проверяет, разрешено ли текущему пользователю редактировать сущность
		 * @param iUmiObject|iUmiHierarchyElement $entity сущность
		 * @param bool $isObject является ли сущность объектом
		 * @throws publicAdminException
		 */
		private function checkPermissions($entity, $isObject) {
			$permissions = permissionsCollection::getInstance();
			$userId = Service::Auth()->getUserId();

			if ($permissions->isSv($userId)) {
				return;
			}

			if ($isObject) {
				$isDisallowed = !($entity->getOwnerId() == $userId);

				if ($isDisallowed) {
					$module = $entity->getModule();
					$method = $entity->getMethod();
					$isObjectEditingAllowed = (bool) mainConfiguration::getInstance()
						->get('system', 'allow-object-editing');

					switch (true) {
						case ($module && $method):
							$isDisallowed = !$permissions->isAllowedMethod($userId, $module, $method);
							break;

						case $isObjectEditingAllowed:
							$isDisallowed = false;
							break;

						default:
							throw new publicAdminException(getLabel('error-no-permissions'));
					}
				}
			} else {
				list ($isReadAllowed, $isWriteAllowed) = $permissions->isAllowedObject($userId, $entity->getId());
				$isDisallowed = !$isWriteAllowed;
			}

			if ($isDisallowed) {
				throw new publicAdminException(getLabel('error-no-permissions'));
			}
		}

		/**
		 * Сохраняет значения поля,
		 * используется в быстром редактирования полей
		 * в табличном контроле
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function save_editable_region() {
			$entityId = getRequest('param0');
			$propName = getRequest('param1');
			$isObject = (bool) getRequest('is_object');

			$value = $this->getSavedValue();

			if ($isObject) {
				$entity = umiObjectsCollection::getInstance()->getObject($entityId);
			} else {
				$entity = umiHierarchy::getInstance()->getElement($entityId);
			}

			$this->checkPermissions($entity, $isObject);

			$event = new umiEventPoint('systemModifyPropertyValue');
			$event->addRef('entity', $entity);
			$event->setParam('property', $propName);
			$event->addRef('newValue', $value);
			$event->setMode('before');

			try {
				$event->call();
			} catch (wrongValueException $e) {
				throw new publicAdminException($e->getMessage());
			}

			/** @var iUmiHierarchyElement|iUmiObject $entity */
			if ($entity instanceof iUmiHierarchyElement) {
				$backupModel = backupModel::getInstance();
				$backupModel->addLogMessage($entity->getId());
			}

			if ($isObject && !$this->module->checkAllowedColumn($entity, $propName)) {
				throw new publicAdminException(getLabel('error-no-permissions'));
			}

			if ($isObject && $propName === 'is_activated') {
				$this->checkActivityChange($entityId);
			}

			if (!$entity) {
				return;
			}

			$oldFilter = umiObjectProperty::$IGNORE_FILTER_INPUT_STRING;
			umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = true;

			/** @var iUmiObject $object */
			$object = $isObject ? $entity : $entity->getObject();
			$oldValue = null;

			try {
				if ($propName == 'name') {
					if (is_string($value) && $value !== '') {
						$oldValue = $entity->getName();
						$entity->setName($value);

						if ($entity instanceof iUmiHierarchyElement) {
							$entity->h1 = $value;
						}
					}
					$result = ['name' => $value];
				} else {
					$property = $object->getPropByName($propName);

					switch ($property->getDataType()) {
						case 'date':
							$date = new umiDate();
							$value = ($date->setDateByString($value)) ? $date : 0;
							break;

						case 'img_file':
							$value = new umiImageFile('.' . $value);
							break;

						case 'swf_file':
						case 'video_file':
						case 'file' :
							$value = new umiFile('.' . $value);
							break;

						case 'optioned':
							$value = $value[$propName];
							break;
					}

					$oldValue = $object->getValue($propName);
					$object->setValue($propName, $value);

					if ($object->getIsUpdated() && $object->getId() != $entity->getId()) {
						$entity->setIsUpdated();
					}

					if ($entity instanceof iUmiHierarchyElement && $propName == 'h1') {
						$entity->setName($value);
					}

					$result = ['property' => $property];
					translatorWrapper::get($property);
					umiObjectPropertyWrapper::$showEmptyFields = true;
				}
			} catch (fieldRestrictionException $e) {
				throw new publicAdminException($e->getMessage());
			}

			$entity->commit();
			umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = $oldFilter;

			$object->update();
			$entity->update();

			if ($entity instanceof iUmiEntinty) {
				$entity->commit();
			}

			$event->setParam('oldValue', $oldValue);
			$event->setParam('newValue', $value);
			$event->setMode('after');
			$event->call();

			$this->setData($result);
			$this->doData();
		}

		/**
		 * Возвращает значение поля, сохраняемого через табличный контрол
		 * @return mixed
		 */
		private function getSavedValue() {
			$value = getRequest('data');

			if (!is_array($value) || empty($value)) {
				return $value;
			}

			if (count($value) == 1) {
				return array_shift($value);
			}

			$valueList = [];
			foreach ($value as $item) {
				$valueList[] = is_array($item) ? array_shift($item) : $item;
			}

			return $valueList;
		}

		/**
		 * Проверяет, разрешено ли изменять активность сущности
		 * @param int $entityId Идентификатор сущности
		 * @throws publicAdminException
		 */
		private function checkActivityChange($entityId) {
			$systemUsersPermissions = Service::SystemUsersPermissions();
			$svUserId = $systemUsersPermissions->getSvUserId();
			$guestId = $systemUsersPermissions->getGuestUserId();
			$userId = Service::Auth()->getUserId();

			if ($entityId == $svUserId) {
				throw new publicAdminException(getLabel('error-users-swtich-activity-sv'));
			}

			if ($entityId == $guestId) {
				throw new publicAdminException(getLabel('error-users-swtich-activity-guest'));
			}

			if ($entityId == $userId) {
				throw new publicAdminException(getLabel('error-users-swtich-activity-self'));
			}
		}

		/**
		 * Возвращает ветвь контрола типа "Дерево" или "Таблица"
		 * @throws coreException
		 */
		public function load_tree_node() {
			$this->setDataType('list');
			$this->setActionType('view');

			$limit = getRequest('per_page_limit');
			$curr_page = getRequest('p');
			$offset = $curr_page * $limit;

			list($rel) = getRequest('rel');
			$sel = new selector('pages');
			if ($rel !== 0) {
				$sel->limit($offset, $limit);
			}
			selectorHelper::detectFilters($sel);

			$result = $sel->result();
			$length = $sel->length();
			$templatesData = getRequest('templates');

			if ($templatesData) {
				$templatesList = explode(',', $templatesData);
				$result = $this->module->getPagesByTemplatesIdList($templatesList, $limit, $offset);
				$length = $this->module->getTotalPagesByTemplates($templatesList);
			}

			$data = $this->prepareData($result, 'pages');
			$this->setData($data, $length);
			$this->setDataRange($limit, $offset);

			if ($rel != 0) {
				$this->setDataRangeByPerPage($limit, $curr_page);
			}

			$this->doData();
		}

		/**
		 * Переключает активность у страниц
		 * @throws expectElementException
		 * @throws publicAdminException
		 * @throws requreMoreAdminPermissionsException
		 * @throws wrongElementTypeAdminException
		 */
		public function tree_set_activity() {
			$this->changeActivityForPages();
		}

		/**
		 * Перемещает страницу и|или объект в административной панели
		 * @return mixed
		 * @throws coreException
		 * @throws expectElementException
		 * @throws publicAdminException
		 * @throws wrongElementTypeAdminException
		 */
		public function move() {
			$element = $this->expectElement('element');
			$elementParent = $this->expectElement('rel');

			if ($element instanceof iUmiHierarchyElement &&
				($elementParent instanceof iUmiHierarchyElement || getRequest('rel') == 0)) {
				return $this->tree_move_element();
			}

			$object = $this->expectObject('element');
			$objectParent = $this->expectObject('rel');

			if ($object instanceof iUmiObject && $objectParent instanceof iUmiObject) {
				return $this->table_move_object($object, $objectParent);
			}

			if (($element instanceof iUmiHierarchyElement || $object instanceof iUmiObject) &&
				($elementParent instanceof iUmiHierarchyElement || $objectParent instanceof iUmiObject)) {
				return $this->table_mixed_move();
			}

			$this->setDataType('list');
			$this->setActionType('view');

			$this->setData([]);
			$this->doData();
		}

		/** Метод-заглушка для смешанного перемещения страниц и объектов */
		public function table_mixed_move() {
			$this->setDataType('list');
			$this->setActionType('view');
			$this->setData(['node' => 'mixed']);
			$this->doData();
		}

		/**
		 * Перемещает объект
		 * @param iUmiObject $object объект который перемещают
		 * @param iUmiObject $objectParent объект в который перемещают
		 * @throws coreException
		 */
		public function table_move_object(iUmiObject $object, iUmiObject $objectParent) {
			$this->setDataType('list');
			$this->setActionType('view');

			$moveMode = getRequest('moveMode');

			$umiObjects = umiObjectsCollection::getInstance();
			$orderChanged = $umiObjects->changeOrder($objectParent, $object, $moveMode);

			if ($orderChanged) {
				$this->setDataRange(2);
				$data = $this->prepareData([$object, $objectParent], 'objects');
				$this->setData($data, 2);
			} else {
				$this->setDataRange(0);
				$data = $this->prepareData([], 'objects');
				$this->setData($data, 0);
			}

			$this->doData();
		}

		/**
		 * Перемещает выбранные страницы
		 * @throws coreException
		 * @throws expectElementException
		 * @throws publicAdminException
		 * @throws wrongElementTypeAdminException
		 */
		public function tree_move_element() {
			$selectedItems = getRequest('selected_list');
			$newParentId = (int) getRequest('rel');
			$domain = getRequest('domain');
			$asSibling = (int) getRequest('as-sibling');
			$beforeId = getRequest('before');
			$isMoveToEnd = getRequest('to_end');

			$umiHierarchy = umiHierarchy::getInstance();
			$newParentParentsIds = $umiHierarchy->getAllParents($newParentId);
			$page = null;
			$movedPages = [];

			if (umiCount($selectedItems) == 0 && isset($_REQUEST['element']) && getRequest('return_copies')) {
				$selectedItems[] = getRequest('element');
			}

			foreach ($selectedItems as $pageId) {
				if (in_array($pageId, $newParentParentsIds)) {
					continue;
				}

				/** @var iUmiHierarchyElement $page */
				$page = $this->expectElement($pageId, false, true);

				if (!$page instanceof iUmiHierarchyElement) {
					throw new publicAdminException(getLabel('error-expect-element'));
				}

				$isDomainIdNotEquals = Service::DomainCollection()->getDomainId($domain) != $page->getDomainId();
				$isParentIdNotEquals = ($newParentId != $page->getParentId()) || $isDomainIdNotEquals;

				switch (true) {
					case ($asSibling) : {
						$needToDoAnything = true;
						break;
					}
					case (!$asSibling && $isParentIdNotEquals && $pageId != $newParentId) : {
						$needToDoAnything = true;
						break;
					}
					default : {
						$needToDoAnything = false;
					}
				}

				if (!$needToDoAnything) {
					continue;
				}

				$movingParams = [
					'element' => $page,
					'parent-id' => $newParentId,
					'domain' => $domain,
					'as-sibling' => $asSibling,
					'before-id' => $beforeId,
					'move-to-end' => $isMoveToEnd
				];

				if ($this->moveElement($movingParams)) {
					$movedPages[] = $page->getId();
				}
			}

			if (getRequest('return_copies')) {
				$this->setDataType('form');
				$this->setActionType('modify');
				$data = $this->prepareData(['element' => $page], 'page');
			} else {
				$this->setDataType('list');
				$this->setActionType('view');
				$data = $this->prepareData($movedPages, 'pages');
			}

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Удаляет страницы в корзину
		 * @throws coreException
		 * @throws expectElementException
		 * @throws publicAdminException
		 * @throws wrongElementTypeAdminException
		 * @throws baseException
		 */
		public function tree_delete_element() {
			$elements = getRequest('element');
			if (!is_array($elements)) {
				$elements = [$elements];
			}

			$parentIds = [];

			foreach ($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true, true);

				if ($element instanceof iUmiHierarchyElement) {
					// before del event
					$element_id = $element->getId();
					$parentIds[] = $element->getParentId();
					$oEventPoint = new umiEventPoint('content_del_element');
					$oEventPoint->setMode('before');
					$oEventPoint->setParam('element_id', $element_id);
					$this->module->setEventPoint($oEventPoint);

					// try delete
					$params = [
						'element' => $element
					];

					$this->deleteElement($params);

					// after del event
					$oEventPoint->setMode('after');
					$this->module->setEventPoint($oEventPoint);
				} else {
					throw new publicAdminException(getLabel('error-expect-element'));
				}
			}

			$parentIds = array_unique($parentIds);

			// retrun parent element for update
			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($parentIds, 'pages');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Копирует страницу
		 * @throws Exception
		 * @throws coreException
		 * @throws expectElementException
		 * @throws publicAdminException
		 */
		public function tree_copy_element() {
			$element = $this->expectElement('element');
			$cloneMode = (bool) getRequest('clone_mode');
			$copyAll = (bool) getRequest('copy_all');
			$parentId = (int) getRequest('rel');

			if (!$element instanceof iUmiHierarchyElement) {
				throw new publicAdminException(getLabel('error-expect-element'));
			}

			$element_id = $element->getId();
			$pageCollection = umiHierarchy::getInstance();

			if (!($parentId && $pageCollection->isExists($parentId))) {
				$parentId = $pageCollection->getParent($element_id);
			}

			$connection = ConnectionPool::getInstance()
				->getConnection();
			$connection->startTransaction();

			try {
				if ($cloneMode) {
					// create real copy
					$event = new umiEventPoint('systemCloneElement');
					$event->addRef('element', $element);
					$event->setParam('elementId', $element_id);
					$event->setParam('parentId', $parentId);
					$event->setMode('before');
					$event->call();

					$new_element_id = $pageCollection->cloneElement($element_id, $parentId, $copyAll);

					$event->setParam('newElementId', $new_element_id);
					$event->setMode('after');
					$event->call();

					$new_element = $pageCollection->getElement((int) $new_element_id);

					$event = new umiEventPoint('systemCreateElementAfter');
					$event->addRef('element', $new_element);
					$event->setParam('elementId', $new_element_id);
					$event->setParam('parentId', $parentId);
					$event->setMode('after');
					$event->call();
				} else {
					// create virtual copy
					$event = new umiEventPoint('systemVirtualCopyElement');
					$event->setParam('elementId', $element_id);
					$event->setParam('parentId', $parentId);
					$event->addRef('element', $element);
					$event->setMode('before');
					$event->call();

					$new_element_id = $pageCollection->copyElement($element_id, $parentId, $copyAll);

					$event->setParam('newElementId', $new_element_id);
					$event->setMode('after');
					$event->call();

					$new_element = $pageCollection->getElement((int) $new_element_id);

					$event = new umiEventPoint('systemCreateElementAfter');
					$event->addRef('element', $new_element);
					$event->setParam('elementId', $new_element_id);
					$event->setParam('parentId', $parentId);
					$event->setMode('after');
					$event->call();
				}

				if (!$new_element_id) {
					throw new publicAdminException(getLabel('error-copy-element'));
				}

				if ((bool) getRequest('return_copies')) {
					$this->setDataType('form');
					$this->setActionType('modify');
					$data = $this->prepareData(['element' => $new_element], 'page');
					$this->setData($data);
				} else {
					$this->setDataType('list');
					$this->setActionType('view');
					$data = $this->prepareData([$new_element_id], 'pages');
					$this->setData($data);
				}
			} catch (Exception $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			$connection->commitTransaction();
			$this->doData();
		}

		/**
		 * Возвращает целевой домен
		 * @return iDomain
		 * @throws publicAdminException
		 */
		protected function getTargetDomain() {
			$domainId = (int) getRequest('domain-id');
			$targetDomain = Service::DomainCollection()
				->getDomain($domainId);

			if (!$targetDomain instanceof iDomain) {
				throw new publicAdminException('Wrong domain id given');
			}

			return $targetDomain;
		}

		/**
		 * Возвращает целевой язык
		 * @return iLang
		 * @throws publicAdminException
		 */
		protected function getTargetLanguageId() {
			$languageId = (int) getRequest('lang-id');
			$targetLanguage = Service::LanguageCollection()
				->getLang($languageId);

			if (!$targetLanguage instanceof iLang) {
				throw new publicAdminException('Wrong language id given');
			}

			return $targetLanguage;
		}

		/**
		 * Устанавливает пустой ответ для метода copyElementToSite()
		 * @throws coreException
		 */
		protected function setCopyToLangEmptyResponse() {
			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData([], 'pages');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Устанавливает ответ с ошибкой о нехватке шаблона, для метода copyElementToSite()
		 * @param string $languageSuffix суффикс целевого языка
		 * @throws coreException
		 */
		protected function setCopyToLangNoTemplatesResponse($languageSuffix) {
			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData([], 'pages');
			$data['error'] = [];
			$data['error']['type'] = '__template_not_exists__';
			$data['error']['text'] = sprintf(getLabel('error-no-template-in-domain'), $languageSuffix);
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Устанавливает ответ с ошибкой о совпадении адресов копируемых страниц, для метода copyElementToSite()
		 * @param iDomain $targetDomain целевой домен
		 * @param string $languageSuffix суффикс целевого языка
		 * @param array (id => array(существующий адрес, пример корректного адреса)) $pagesWithExistingAltNames страницы,
		 * у которых совпадет адрес после копирования
		 * @throws coreException
		 */
		protected function setCopyToLangPreventAltNamesDoublesResponse(
			iDomain $targetDomain,
			$languageSuffix,
			array $pagesWithExistingAltNames
		) {
			$this->setDataType('list');
			$this->setActionType('view');
			$data = [
				'error' => []
			];

			$data['error']['nodes:item'] = [];
			$data['error']['type'] = '__alias__';

			$path = $targetDomain->getUrl() . '/' . $languageSuffix;

			foreach ($pagesWithExistingAltNames as $pageId => $altNames) {
				$data['error']['nodes:item'][] = [
					'attribute:id' => $pageId,
					'attribute:path' => $path,
					'attribute:alias' => array_shift($altNames),
					'attribute:alt_name_normal' => array_shift($altNames)
				];
			}

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает список адресов страниц, которые требуется переименовать при копировании,
		 * для метода copyElementToSite()
		 * @return array(id => altName)
		 */
		protected function getCopyToLangPagesForRename() {
			$pagesToRename = (array) getRequest('alias');

			foreach ($pagesToRename as $pageId => $pageAltName) {
				$pagesToRename[$pageId] = umiHierarchy::convertAltName($pageAltName);
			}

			return $pagesToRename;
		}

		/**
		 * Возвращает страницы, у которых совпадет адрес после копирования, для метода copyElementToSite()
		 * @param array (# => id) $pages проверяемый страницы
		 * @param array (id => 1) $pagesToReplace страницы, которыми будут заменены страницы с совпадающими адресами
		 * @param array (id => новый адрес) $pagesToRename страницы, адреса которых будут изменены после копирования
		 * @param int $domainId идентификатор целевого домена
		 * @param int $languageId идентификатор целевого языка
		 * @return array(id => array(существующий адрес, пример корректного адреса))
		 * @throws expectElementException
		 */
		protected function getCopyToLangPagesFromTargetDomainAndLanguageWithSameAltNames(
			array $pages,
			array $pagesToReplace,
			array $pagesToRename,
			$domainId,
			$languageId
		) {
			$umiHierarchy = umiHierarchy::getInstance();
			$pagesWithExistingAltNames = [];

			foreach ($pages as $pagesId) {

				if (isset($pagesToReplace[$pagesId])) {
					continue;
				}

				$page = $this->expectElement($pagesId, false, true);

				if (!$page instanceof iUmiHierarchyElement) {
					continue;
				}

				$pageAltName = isset($pagesToRename[$pagesId]) ? $pagesToRename[$pagesId] : $page->getAltName();

				$errorCount = 0;
				$pageWithSameAltNameId = $umiHierarchy->getIdByPath(
					$pageAltName, false, $errorCount, $domainId, $languageId
				);

				$pageWithSameAltName = $this->expectElement($pageWithSameAltNameId, false, true);

				if (!$pageWithSameAltName instanceof iUmiHierarchyElement) {
					continue;
				}

				if ($pageWithSameAltName->getAltName() != $pageAltName) {
					continue;
				}

				$normalizedAltName = $umiHierarchy->getRightAltName(
					$pageAltName, $pageWithSameAltName, false, true
				);

				$pagesWithExistingAltNames[$pagesId] = [
					$pageAltName,
					$normalizedAltName
				];
			}

			return $pagesWithExistingAltNames;
		}

		/**
		 * Копирует страницу в другую языковую версию и/или другой домен
		 * @throws coreException
		 * @throws expectElementException
		 * @throws publicAdminException
		 */
		public function copyElementToSite() {
			$targetPages = (array) getRequest('element');

			if (umiCount($targetPages) == 0) {
				$this->setCopyToLangEmptyResponse();
				return;
			}

			$targetDomain = $this->getTargetDomain();
			$newDomainId = $targetDomain->getId();
			$targetLanguage = $this->getTargetLanguageId();
			$newLanguageId = $targetLanguage->getId();
			$languageSuffix = (!$targetLanguage->getIsDefault()) ? $targetLanguage->getPrefix() . '/' : '';
			$umiTemplates = templatesCollection::getInstance();

			$templatesFromTargetDomainAndLanguage = $umiTemplates->getTemplatesList($newDomainId, $newLanguageId);

			if (umiCount($templatesFromTargetDomainAndLanguage) == 0) {
				$this->setCopyToLangNoTemplatesResponse($languageSuffix);
				return;
			}

			$pagesToRename = $this->getCopyToLangPagesForRename();
			$umiHierarchy = umiHierarchy::getInstance();
			$pagesToReplaces = (array) getRequest('move');

			$pagesWithExistingAltNames = $this->getCopyToLangPagesFromTargetDomainAndLanguageWithSameAltNames(
				$targetPages, $pagesToReplaces, $pagesToRename, $newDomainId, $newLanguageId
			);

			if (umiCount($pagesWithExistingAltNames) > 0) {
				$this->setCopyToLangPreventAltNamesDoublesResponse(
					$targetDomain, $languageSuffix, $pagesWithExistingAltNames
				);
				return;
			}

			$defaultTemplateFromTargetDomainAndLanguage = $umiTemplates->getDefaultTemplate(
				$newDomainId, $newLanguageId
			);

			if (!$defaultTemplateFromTargetDomainAndLanguage instanceof iTemplate) {
				throw new publicAdminException('Cannot get default template');
			}

			$newPages = [];

			foreach ($targetPages as $targetPageId) {
				$targetPage = $this->expectElement($targetPageId, false, true);

				if (!$targetPage instanceof iUmiHierarchyElement) {
					continue;
				}

				$targetPageTemplate = $umiTemplates->getTemplate($targetPage->getTplId());
				$newPageTemplateId = $defaultTemplateFromTargetDomainAndLanguage->getId();

				foreach ($templatesFromTargetDomainAndLanguage as $templateFromTargetDomainAndLanguage) {
					if ($templateFromTargetDomainAndLanguage->getFilename() == $targetPageTemplate->getFilename()) {
						$newPageTemplateId = $templateFromTargetDomainAndLanguage->getId();
					}
				}

				$newPageId = $umiHierarchy->cloneElement($targetPageId, 0, true);
				$newPage = $this->expectElement($newPageId, false, true);

				if (!$newPage instanceof iUmiHierarchyElement) {
					continue;
				}

				$newPage->setLangId($newLanguageId);
				$newPage->setDomainId($newDomainId);

				$targetPageAlt = $targetPage->getAltName();
				$newPageAltName = $newPage->getAltName();
				$altsAreDifferent = $targetPageAlt !== $newPageAltName;
				$altsHaveSameBase = startsWith($newPageAltName, $targetPageAlt);
				$newAltAutoChanged = (bool) preg_match('/^.*\d{1,}$/', $newPageAltName);

				if ($altsAreDifferent && $altsHaveSameBase && $newAltAutoChanged) {
					$newPageAltName = $targetPageAlt;
				}

				$newPageAltName = isset($pagesToRename[$newPageId]) ? $pagesToRename[$newPageId] : $newPageAltName;

				if (isset($pagesToReplaces[$targetPageId])) {
					$errorCount = 0;

					$pageWithSameAltNameId = $umiHierarchy->getIdByPath(
						$newPageAltName, false, $errorCount, $newDomainId, $newLanguageId
					);

					$pageWithSameAltName = $this->expectElement($pageWithSameAltNameId, false, true);

					if ($pageWithSameAltName instanceof iUmiHierarchyElement) {
						$umiHierarchy->delElement($pageWithSameAltName->getId());
					}
				}

				$newPage->setAltName($newPageAltName);
				$newPage->setTplId($newPageTemplateId);
				$newPage->commit();

				$children = $umiHierarchy->getChildrenTree($newPageId);
				$this->module->changeChildsLang($children, $newLanguageId, $newDomainId);
				$newPages[] = $newPage;
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($newPages, 'pages');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Устанавливает шаблону флаг "основной"
		 * @param int $templateId ид шаблона, который требуется изменить
		 * @param bool|int $domainId ид домена, к которому относится шаблон. Если не передать - возьмет текущий.
		 * @param bool|int $languageId ид языка, к которому относится шаблон. Если не передать - возьмет текущий.
		 * @return true
		 * @throws publicAdminException если $templateId не является числом
		 * @throws publicAdminException если не удалось получить шаблон по id
		 * @throws publicAdminException если не удалось получить текущий домен
		 * @throws publicAdminException если не удалось получить домен по id
		 * @throws publicAdminException если не удалось получить текущий язык
		 * @throws publicAdminException если не удалось получить язык по id
		 * @throws publicAdminException если не удалось сделать шаблон основным
		 * @throws coreException
		 */
		public function setBaseTemplate($templateId = null, $domainId = false, $languageId = false) {
			$templateId = ($templateId === null) ? getRequest('param0') : $templateId;

			if (!is_numeric($templateId)) {
				throw new publicAdminException(__METHOD__ . ': wrong template id given: ' . $templateId);
			}

			$templateCollection = templatesCollection::getInstance();
			$template = $templateCollection->getTemplate($templateId);

			if (!$template instanceof iTemplate) {
				throw new publicAdminException(__METHOD__ . ': template with id = ' . $templateId . ' was not found');
			}

			$domainId = is_bool($domainId) ? getRequest('param1') : $domainId;

			if (!is_numeric($domainId)) {
				$domainId = Service::DomainDetector()->detectId();
			}

			$domain = Service::DomainCollection()
				->getDomain($domainId);

			if (!$domain instanceof iDomain) {
				throw new publicAdminException(__METHOD__ . ':  cant get domain by id: ' . $domainId);
			}

			$languageId = is_bool($languageId) ? getRequest('param2') : $languageId;

			if (!is_numeric($languageId)) {
				$languageId = Service::LanguageDetector()->detectId();
			}

			$language = Service::LanguageCollection()
				->getLang($languageId);

			if (!$language instanceof iLang) {
				throw new publicAdminException(__METHOD__ . ':  cant get language by id: ' . $languageId);
			}

			$baseTemplateChanged = $templateCollection->setDefaultTemplate($templateId, $domainId, $languageId);

			if (!$baseTemplateChanged) {
				throw new publicAdminException(__METHOD__ . ':  cant change base template');
			}

			return true;
		}
	}
