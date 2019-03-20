<?php

	use UmiCms\Service;

	/** Класс функционала административной панели модулей "Шаблоны данных" */
	class DataAdmin {

		use baseModuleAdmin;

		/** @var data|DataAdmin $module */
		public $module;

		/** @var int $per_page количество элементов справочника к выводу */
		public $per_page = 50;

		/**
		 * Возвращает список иерархических типов данных, если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то метод сохраняет изменения списка иерархических типов данных
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws requreMoreAdminPermissionsException
		 */
		public function config() {
			if ($this->isSaveMode()) {
				$this->module->validateDeletingListPermissions();
				$this->saveEditedList('basetypes');
				$this->chooseRedirect();
			}

			$hierarchy_types = umiHierarchyTypesCollection::getInstance()->getTypesList();

			$this->setDataType('list');
			$this->setActionType('modify');
			$data = $this->prepareData($hierarchy_types, 'hierarchy_types');
			$this->setData($data, umiCount($hierarchy_types));
			$this->doData();
		}

		/**
		 * Возвращает список объектных типов данных
		 * @throws coreException
		 * @throws expectObjectTypeException
		 */
		public function types() {
			$perPage = getRequest('per_page_limit');
			$currentPageNumber = (int) getRequest('p');

			if (isset($_REQUEST['rel'][0])) {
				$parentTypeId = $this->expectObjectTypeId($_REQUEST['rel'][0], false, true);
			} else {
				$parentTypeId = $this->expectObjectTypeId('param0');
			}

			if (isset($_REQUEST['search-all-text'][0])) {
				$searchAllText = getFirstValue($_REQUEST['search-all-text']);
			} else {
				$searchAllText = false;
			}

			$types = umiObjectTypesCollection::getInstance();
			$domainId = getFirstValue(getRequest('domain_id'));
			$parentTypeId = $parentTypeId ?: 0;

			if ($searchAllText) {
				$childIdList = $types->getIdListByNameLike($searchAllText, $domainId);
			} else {
				$childIdList = $types->getSubTypeListByDomain($parentTypeId, $domainId);
			}

			$tmp = [];
			foreach ($childIdList as $typeId) {
				$type = $types->getType($typeId);

				if (!$type instanceof iUmiObjectType) {
					continue;
				}

				$tmp[$typeId] = $type->getName();
			}

			if (isset($_REQUEST['order_filter']['name'])) {
				natsort($tmp);
				if ($_REQUEST['order_filter']['name'] == 'desc') {
					$tmp = array_reverse($tmp, true);
				}
			}

			$childIdList = array_keys($tmp);
			unset($tmp);
			$childIdList = $this->excludeNestedTypes($childIdList);

			$total = umiCount($childIdList);
			$childIdList = array_slice($childIdList, $currentPageNumber * $perPage, $perPage);

			$this->setDataType('list');
			$this->setActionType('view');
			$this->setDataRange($perPage, $currentPageNumber * $perPage);

			$data = $this->prepareData($childIdList, 'types');
			$this->setData($data, $total);
			$this->doData();
		}

		/**
		 * Создает объектный тип данных и перенаправляет на форму редактирования объектного типа данных
		 * @throws coreException
		 * @throws expectObjectTypeException
		 */
		public function type_add() {
			$parent_type_id = (int) $this->expectObjectTypeId('param0');

			$objectTypes = umiObjectTypesCollection::getInstance();
			$type_id = $objectTypes->addType($parent_type_id, 'i18n::object-type-new-data-type');

			$this->module->redirect($this->module->pre_lang . '/admin/data/type_edit/' . $type_id . '/');
		}

		/**
		 * Выводит информацию об объектном типе данных для построения формы редактирования.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do, сохраняет изменения объектного типа данных
		 * @throws coreException
		 * @throws expectObjectTypeException
		 * @throws publicAdminException
		 */
		public function type_edit() {
			$type = $this->expectObjectType('param0');

			if ($this->isSaveMode('param1')) {
				try {
					$this->saveEditedTypeData($type);
				} catch (Exception $exception) {
					throw new publicAdminException($exception->getMessage());
				}

				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('modify');
			$data = $this->prepareData($type, 'type');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Выводит информацию для построения формы добавления поля.
		 * Если передан ключевой параметр $_REQUEST['param2'] = do, то добавляет поле
		 * @param bool $redirectString нужно ли осуществлять перенаправление на форму редактирования
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function type_field_add($redirectString = false) {
			$groupId = (int) getRequest('param0');
			$typeId = (int) getRequest('param1');
			$inputData = [
				'group-id' => $groupId,
				'type-id' => $typeId
			];

			if ($this->isSaveMode('param2')) {
				try {
					$fieldId = $this->saveAddedFieldData($inputData);
				} catch (wrongParamException $exception) {
					throw new publicAdminException($exception->getMessage());
				}

				if (getRequest('noredirect')) {
					$field = umiFieldsCollection::getInstance()->getField($fieldId);
					$this->setDataType('form');
					$this->setActionType('modify');
					$data = $this->prepareData($field, 'field');
					$this->setData($data);
					$this->doData();
					return;
				}

				$fieldEditLink = $this->module->pre_lang . "/admin/data/type_field_edit/$fieldId/$typeId/";
				$this->chooseRedirect($redirectString ?: $fieldEditLink);
			}

			$this->setDataType('form');
			$this->setActionType('create');
			$data = $this->prepareData($inputData, 'field');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Выводит информацию для построения формы редактирования поля.
		 * Если передан ключевой параметр $_REQUEST['param2'] = do, то сохраняет изменения поля.
		 * @throws coreException
		 */
		public function type_field_edit() {
			$id = (int) getRequest('param0');
			$field = umiFieldsCollection::getInstance()->getField($id);

			if ($this->isSaveMode('param2')) {
				$this->saveEditedFieldData($field);
				if (!getRequest('noredirect')) {
					$this->chooseRedirect();
				}
			}

			$this->setDataType('form');
			$this->setActionType('modify');
			$data = $this->prepareData($field, 'field');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Прикрепляет поле к группе, находящейся в типе данных
		 * @param int $typeId идентификатор типа данных
		 * @param int $groupId идентификатор группы
		 * @param int $fieldId идентификатор поля
		 * @throws publicAdminException
		 */
		public function attachField($typeId = null, $groupId = null, $fieldId = null) {
			$typeId = ($typeId === null) ? (int) getRequest('param0') : $typeId;
			$groupId = ($groupId === null) ? (int) getRequest('param1') : $groupId;
			$fieldId = ($fieldId === null) ? (int) getRequest('param2') : $fieldId;

			$umiFields = umiFieldsCollection::getInstance();
			$field = $umiFields->getById($fieldId);

			if (!$field instanceof iUmiField) {
				throw new publicAdminException(getLabel('label-incorrect-field-id'));
			}

			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$type = $umiObjectTypes->getType($typeId);

			if (!$type instanceof iUmiObjectType) {
				throw new publicAdminException(getLabel('label-incorrect-type-id'));
			}

			$allowInactiveGroup = true;
			$group = $type->getFieldsGroup($groupId, $allowInactiveGroup);

			if (!$group instanceof iUmiFieldsGroup) {
				throw new publicAdminException(getLabel('label-incorrect-group-id'));
			}

			$group->attachField($field->getId());

			$this->setDataType('form');
			$this->setActionType('modify');

			$data = $this->prepareData($field, 'field');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Производит поиск среди типов данных, связанных с заданным, поля с заданными параметрами.
		 * По связанными типами подразумеваются:
		 *
		 * 1) Родитель типа данных;
		 * 2) Соседи типа данных;
		 * 3) Дочерние типы данных (на один уровень)
		 *
		 * Возвращает идентификатор найденного поля или null
		 * @param int $typeId идентификатор типа данных
		 * @param array $fieldData данные поля
		 *
		 * [
		 *      'name' => 'строковой идентификатор поля',
		 *      'title' => 'название поля',
		 *      'field_type_id' => 'идентификатор типа поля'
		 * ]
		 *
		 * @throws publicAdminException
		 */
		public function getSameFieldFromRelatedTypes($typeId = null, array $fieldData = []) {
			$typeId = ($typeId === null) ? (int) getRequest('param0') : $typeId;
			$fieldData = empty($fieldData) ? (array) getRequest('data') : $fieldData;
			$fieldName = isset($fieldData['name']) ? $fieldData['name'] : '';
			$fieldTitle = isset($fieldData['title']) ? $fieldData['title'] : '';
			$fieldDataTypeId = isset($fieldData['field_type_id']) ? $fieldData['field_type_id'] : '';

			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$type = $umiObjectTypes->getType($typeId);

			if (!$type instanceof iUmiObjectType) {
				throw new publicAdminException(getLabel('label-incorrect-type-id'));
			}

			$parentTypeId = $type->getParentId();

			$sameFieldSource = 'parent';
			$fieldIdAndTypeId = $this->getSameFieldIdAndTypeId(
				[$parentTypeId],
				$fieldName,
				$fieldTitle,
				$fieldDataTypeId
			);

			if (empty($fieldIdAndTypeId)) {
				$siblingTypeIdList = $umiObjectTypes->getSubTypesList($parentTypeId);
				$sameFieldSource = 'sibling';
				$fieldIdAndTypeId = $this->getSameFieldIdAndTypeId(
					$siblingTypeIdList,
					$fieldName,
					$fieldTitle,
					$fieldDataTypeId
				);
			}

			if (empty($fieldIdAndTypeId)) {
				$childrenTypeIdList = $umiObjectTypes->getSubTypesList($typeId);
				$sameFieldSource = 'child';
				$fieldIdAndTypeId = $this->getSameFieldIdAndTypeId(
					$childrenTypeIdList,
					$fieldName,
					$fieldTitle,
					$fieldDataTypeId
				);
			}

			if (empty($fieldIdAndTypeId)) {
				$sameFieldId = null;
				$message = null;
			} else {
				$sameFieldId = $fieldIdAndTypeId['field_id'];
				$sameTypeId = $fieldIdAndTypeId['type_id'];

				$sameType = $umiObjectTypes->getType($sameTypeId);

				if (!$sameType instanceof iUmiObjectType) {
					throw new publicAdminException(getLabel('label-incorrect-type-id'));
				}

				$format = getLabel('label-message-format-attach-field');
				$sourceLabel = getLabel('label-message-attach-field-' . $sameFieldSource);
				$message = sprintf($format, $sourceLabel, $sameType->getName());
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$this->setData([
				'fieldId' => $sameFieldId,
				'message' => $message
			]);

			$this->doData();
		}

		/**
		 * Выводит информацию для построения формы редактирования группы полей.
		 * Если передан ключевой параметр $_REQUEST['param2'] = do, то сохраняет изменения группы полей.
		 * @throws coreException
		 */
		public function type_group_edit() {
			$groupId = (int) getRequest('param0');
			$typeId = (int) getRequest('param1');
			$group = umiObjectTypesCollection::getInstance()
				->getType($typeId)
				->getFieldsGroup($groupId);

			if ($this->isSaveMode('param2')) {
				$this->saveEditedGroupData($group);

				if (!getRequest('noredirect')) {
					$this->chooseRedirect();
				}
			}

			$this->setDataType('form');
			$this->setActionType('modify');
			$data = $this->prepareData($group, 'group');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Выводит информацию для построения формы добавления группы полей.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do, то добавляет группу полей
		 * @param bool $redirectString нужно ли осуществлять перенаправление на форму редактирования
		 * @throws coreException
		 */
		public function type_group_add($redirectString = false) {
			$typeId = (int) getRequest('param0');
			$inputData = ['type-id' => $typeId];

			if ($this->isSaveMode('param1')) {
				$fieldsGroupId = $this->saveAddedGroupData($inputData);

				if (getRequest('noredirect')) {
					$group = umiObjectTypesCollection::getInstance()
						->getType($typeId)
						->getFieldsGroup($fieldsGroupId);
					$this->setDataType('form');
					$this->setActionType('modify');
					$data = $this->prepareData($group, 'group');
					$this->setData($data);
					$this->doData();
					return;
				}

				$prefix = $redirectString ?: $this->module->pre_lang . '/admin/data/type_group_edit/';
				$this->chooseRedirect($prefix . $fieldsGroupId . '/' . $typeId . '/');
			}

			$this->setDataType('form');
			$this->setActionType('create');
			$data = $this->prepareData($inputData, 'group');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Удаляет объектный тип данных
		 * @throws coreException
		 * @throws expectObjectTypeException
		 * @throws publicAdminException
		 */
		public function type_del() {
			$types = getRequest('element');

			if (!is_array($types)) {
				$types = [$types];
			}

			foreach ($types as $typeId) {
				$this->expectObjectTypeId($typeId, true, true);
				umiObjectTypesCollection::getInstance()->delType($typeId);
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($types, 'types');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает настройки для формирования табличного контрола
		 * @param string $param контрольный параметр
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			$deleteMethod = 'type_del';
			if ($param == 'guides') {
				$loadMethod = 'guides';
			} elseif (is_numeric($param)) {
				$loadMethod = 'guide_items/' . $param;

				return [
					'methods' => [
						[
							'title' => getLabel('smc-load'),
							'forload' => true,
							'module' => 'data',
							'#__name' => $loadMethod
						],
						[
							'title' => getLabel('smc-delete'),
							'module' => 'data',
							'#__name' => 'guide_item_del',
							'aliases' => 'tree_delete_element,delete,del'
						],
						[
							'title' => getLabel('smc-move'),
							'module' => 'content',
							'#__name' => 'move'
						]
					],
					'types' => [
						[
							'common' => 'true',
							'id' => $param
						]
					]
				];
			} else {
				$loadMethod = 'types';
			}

			$p = [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'data',
						'#__name' => $loadMethod
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'data',
						'#__name' => $deleteMethod,
						'aliases' => 'tree_delete_element,delete,del'
					]
				]
			];

			$p['default'] = 'name[400px]';

			return $p;
		}

		/**
		 * Возвращает список справочников
		 * @throws coreException
		 */
		public function guides() {
			if (isset($_REQUEST['search-all-text'][0])) {
				$searchAllText = array_extract_values($_REQUEST['search-all-text']);
				foreach ($searchAllText as $i => $v) {
					$searchAllText[$i] = mb_strtolower($v);
				}
			} else {
				$searchAllText = false;
			}

			$rel = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('root-guides-type');
			if (($rels = getRequest('rel')) && umiCount($rels)) {
				$rel = getArrayKey($rels, 0);
			}

			$per_page = getRequest('per_page_limit');
			$curr_page = (int) getRequest('p');

			$types = umiObjectTypesCollection::getInstance();
			$guides_list = $types->getGuidesList(true, $rel);

			$tmp = [];
			foreach ($guides_list as $typeId => $name) {
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
			$guides_list = array_keys($tmp);
			unset($tmp);
			$guides_list = $this->excludeNestedTypes($guides_list);

			$total = umiCount($guides_list);
			$guides = array_slice($guides_list, $per_page * $curr_page, $per_page);

			$this->setDataType('list');
			$this->setActionType('view');
			$this->setDataRange($per_page, $curr_page * $per_page);

			$data = $this->prepareData($guides, 'types');
			$this->setData($data, $total);
			$this->doData();
		}

		/**
		 * Возвращает список объектов справочника.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do,
		 * то сохраняет изменения списка объектов
		 * @param bool|int $guideId идентификатор справочника
		 * @param bool|int $perPage количество элементов справочника к выводу
		 * @param int $currentPage текущий номер страницы, в рамках пагинации
		 * @throws coreException
		 */
		public function guide_items($guideId = false, $perPage = false, $currentPage = 0) {
			$currentPage = $currentPage ?: (int) getRequest('p');
			$perPage = $perPage ?: getRequest('per_page_limit') ?: $this->per_page;
			$guideId = $guideId ?: (int) getRequest('param0');

			$this->setDataType('list');
			$this->setActionType('modify');

			$guide = selector::get('object-type')->id($guideId);
			if ($guide) {
				$this->setHeaderLabel(getLabel('header-data-guide_items') . ' "' . $guide->getName() . '"');
			}

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return;
			}

			$sel = new selector('objects');
			$sel->types('object-type')->id($guideId);
			$sel->limit($perPage * $currentPage, $perPage);
			selectorHelper::detectFilters($sel);

			if ($this->isSaveMode('param1')) {
				$params = [
					'type_id' => $guideId
				];
				$this->saveEditedList('objects', $params);
				$this->chooseRedirect();
			}

			$this->setDataRange($perPage, $currentPage * $perPage);
			$data = $this->prepareData($sel->result(), 'objects');
			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Возвращает данные справочника для формирования формы создания элемента справочника.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do, то создает элемент справочника
		 * и перенаправляет на страницу редактирования справочника, а если $_REQUEST['param1'] = fast,
		 * то создает пустой элемент справочника.
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws wrongElementTypeAdminException
		 */
		public function guide_item_add() {
			$type = (int) getRequest('param0');
			$mode = (string) getRequest('param1');
			$inputData = ['type-id' => $type];

			if ($mode == 'do') {
				$object = $this->saveAddedObjectData($inputData);
				$this->chooseRedirect($this->module->pre_lang . '/admin/data/guide_item_edit/' . $object->getId() . '/');
			} elseif ($mode == 'fast') {
				$umiObjects = umiObjectsCollection::getInstance();

				try {
					$umiObjects->addObject(null, $type);
				} catch (fieldRestrictionException $ignored) {}
			}

			$this->setDataType('form');
			$this->setActionType('create');
			$data = $this->prepareData($inputData, 'object');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает данные справочника для формирования формы редактирования.
		 * Если передан ключевой параметр $_REQUEST['param1'] = 0, то сохраняет
		 * изменения справочника.
		 * @throws coreException
		 * @throws expectObjectException
		 */
		public function guide_item_edit() {
			$object = $this->expectObject('param0');

			if ($this->isSaveMode('param1')) {
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
		 * Удаляет объекты справочника
		 * @throws coreException
		 * @throws expectObjectException
		 * @throws wrongElementTypeAdminException
		 */
		public function guide_item_del() {
			$objects = getRequest('element');

			if (!is_array($objects)) {
				$objects = [$objects];
			}

			foreach ($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);
				$params = ['object' => $object];
				$this->deleteObject($params);
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($objects, 'objects');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Удаляет справочник
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function guide_del() {
			$type_id = (int) getRequest('param0');
			umiObjectTypesCollection::getInstance()->delType($type_id);
			$this->module->redirect($this->module->pre_lang . '/admin/data/guides/');
		}

		/**
		 * Создает справочник и перенаправяет на форму редактирования справочника
		 * @throws coreException
		 * @throws expectObjectTypeException
		 */
		public function guide_add() {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$parent_type_id = (int) $this->expectObjectTypeId('param0');

			if ($parent_type_id == 0) {
				$parent_type_id = $objectTypes->getTypeIdByGUID('root-guides-type');
			}

			$type_id = $objectTypes->addType($parent_type_id, 'i18n::object-type-new-guide');
			$type = $objectTypes->getType($type_id);
			$type->setIsPublic(true);
			$type->setIsGuidable(true);
			$type->commit();

			$this->module->redirect($this->module->pre_lang . '/admin/data/type_edit/' . $type_id . '/');
		}

		/**
		 * Возвращает список элементов справочника для
		 * интерфейса полей типов "Выпадающий список" и "Выпадающий список со множественным выбором"
		 * @throws coreException
		 * @throws selectorException
		 */
		public function guide_items_all() {
			$this->setDataType('list');
			$this->setActionType('modify');
			$module = $this->module;

			if ($module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return;
			}

			$guideId = (int) getRequest('param0');
			$searchStringList = (array) getRequest('search');
			$pageNavigationLimit = getRequest('limit');
			$pageNavigationNumber = (int) getRequest('p');
			$countSelector = $module->buildGuideItemSelectorCount($guideId, $searchStringList, $pageNavigationLimit, $pageNavigationNumber);

			$allowEmptyList = getRequest('allow-empty');
			$interfaceLimit = (int) getRequest('param1');
			$interfaceLimit = $module->getInterfaceLimitForGuideItems($interfaceLimit);
			$module->prepareGuideItemListResult($countSelector, $allowEmptyList, $interfaceLimit);
		}

		/**
		 * Формирует выборку количества элементов справочника
		 * @param int $guideId идентификатор справочника
		 * @param array[] $searchStringList список значений фильтра по имени элемента справочника
		 * @param int $pageNavigationLimit ограничение на размер выборки для постраничной навигации
		 * @param int $pageNavigationNumber номер страницы в рамках постраничной навигации
		 * @return selector
		 * @throws selectorException
		 */
		public function buildGuideItemSelectorCount($guideId, array $searchStringList, $pageNavigationLimit, $pageNavigationNumber) {
			$selector = Service::SelectorFactory()
				->createObjectTypeId($guideId);
			$module = $this->module;
			$selector = $module->appendGuideListFilter($selector, $searchStringList);
			$selector = $module->appendGuideListPageNavigation($selector, $pageNavigationLimit, $pageNavigationNumber);
			$selector->option('return')->value('count');
			return $selector;
		}

		/**
		 * Добавляет в выборку количества элементов справочника фильтрацию
		 * @param selector $selector выборка
		 * @param array $searchStringList $selector список значений фильтра по имени элемента справочника
		 * @return selector
		 * @throws selectorException
		 */
		public function appendGuideListFilter(selector $selector, array $searchStringList) {

			foreach ($searchStringList as $searchString) {

				if (!is_string($searchString) || $searchString === '') {
					continue;
				}

				$stringLabel = ulangStream::getI18n($searchString, '', true);

				if ($stringLabel !== null) {
					$selector->option('or-mode')->field('name');
					$selector->where('name')->equals((array) $stringLabel);
				}

				$selector->where('name')->like('%' . $searchString . '%');
			}

			$umiPermission = permissionsCollection::getInstance();

			if (!$umiPermission->isSv()) {
				$systemUsersPermissions = Service::SystemUsersPermissions();
				$selector->where('id')->notequals($systemUsersPermissions->getSvGroupId());
			}

			return $selector;
		}

		/**
		 * Добавляет в выборку количества элементов ограничение постраничной навигации
		 * @param selector $selector выборка
		 * @param int $pageNavigationLimit ограничение на размер выборки для постраничной навигации
		 * @param int $pageNavigationNumber номер страницы в рамках постраничной навигации
		 * @return selector
		 * @throws selectorException
		 */
		public function appendGuideListPageNavigation(selector $selector, $pageNavigationLimit, $pageNavigationNumber) {
			if ($pageNavigationLimit !== null) {
				$selector->limit(15 * $pageNavigationNumber, 15);
			}

			return $selector;
		}

		/**
		 * Возвращает значение ограничения на количество элементов справочника,
		 * выводимых в интерфейсе
		 * @param int $defaultInterfaceLimit ограничение по умолчанию
		 * @return int
		 */
		public function getInterfaceLimitForGuideItems($defaultInterfaceLimit) {
			$limit = $defaultInterfaceLimit ?: (int) mainConfiguration::getInstance()
				->get('kernel', 'max-guided-items');

			if ($limit && $limit <= 15 && $limit > 0) {
				$limit = 16;
			} elseif ($limit <= 0) {
				$limit = 50;
			}

			return $limit;
		}

		/**
		 * Формирует результат выборки элементов справочника
		 * @param selector $selector выборка количества элементов справочника
		 * @param bool $allowEmptyList определяет разрешен ли вывод пустого списка
		 * @param int $interfaceLimit ограничение на количество элементов,
		 * выводимых в интерфейсе
		 * @throws coreException
		 * @throws selectorException
		 */
		public function prepareGuideItemListResult(selector $selector, $allowEmptyList, $interfaceLimit) {
			$total = $selector->length();
			$module = $this->module;

			if ($allowEmptyList !== null && $total > $interfaceLimit) {
				$module->prepareGuideItemListOverFlowResult($total);
				return;
			}

			$selector->flush();
			$selector->option('return')->value('id');

			$itemList = $module->getGuideItemListResult($selector);
			$this->setDataRangeByPerPage($interfaceLimit);
			$data = $this->prepareData($itemList, 'objects');
			$this->setData($data, $total);
			$this->doData();
		}

		/**
		 * Формирует результат выборки элементов справочника, выходящий за ограничения интерфейса
		 * @param int $total количество элементов справочника, которое необходимо вывести
		 */
		public function prepareGuideItemListOverFlowResult($total) {
			$data = [
				'empty' => [
					'attribute:total' => $total,
					'attribute:result' => 'Too much items'
				]
			];
			$this->setDataRange(0);
			$this->setData($data, $total);
			$this->doData();
		}

		/**
		 * Возвращает список элементов справочника
		 * @param selector $selector выборка элементов справочника
		 * @return iUmiObject[]
		 */
		public function getGuideItemListResult(selector $selector) {
			$sortIndexMap = [];
			$idList = [];

			foreach ($selector->result() as $itemData) {
				$idList[] = $itemData['id'];
			}

			$itemList = umiObjectsCollection::getInstance()
				->getObjectList($idList);

			foreach ($itemList as $item) {
				$id = $item->getId();
				$sortIndexMap[$id] = $item->getName();
				$itemList[$id] = $item;
			}

			if (!umiObjectsCollection::isGuideItemsOrderedById()) {
				natsort($sortIndexMap);
				$itemList = array_keys($sortIndexMap);
				unset($sortIndexMap);
			}

			return $itemList;
		}

		/**
		 * Возвращает список доменов в формате содержимого справочника.
		 * Часть api для табличного контрола.
		 */
		public function getDomainList() {
			$this->setDataType('list');
			$this->setActionType('modify');

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return;
			}

			$domainList = Service::DomainCollection()
				->getList();
			$domainNodeList = [];

			foreach ($domainList as $domain) {
				$domainNodeList[] = [
					'attribute:id' => $domain->getId(),
					'attribute:name' => $domain->getDecodedHost()
				];
			}

			$result = [
				'nodes:object' => $domainNodeList
			];

			$this->setData($result, umiCount($domainList));
			$this->doData();
		}

		/**
		 * Добавляет объект в справочник
		 * @param string $name имя создаваемого объекта
		 * @param int $guideId ID справочника, в который будет добавлен объект
		 */
		public function addObjectToGuide($name = '', $guideId = 0) {
			if (!$name) {
				$name = getRequest('param0');
			}

			if (!$guideId) {
				$guideId = getRequest('param1');
			}

			$inputData = [
				'type-id' => $guideId,
				'name' => $name
			];

			try {
				$objectId = $this->saveAddedObjectData($inputData);
			} catch (Exception $exception) {
				$objectId = 0;
			}

			$result = ['object' => $objectId];
			$this->setData($result);
			$this->doData();
		}

		/**
		 * Меняет порядок поля внутри группы полей
		 * @throws Exception
		 * @throws coreException
		 */
		public function json_move_field_after() {
			$field_id = (int) getRequest('param0');
			$before_field_id = (int) getRequest('param1');
			$is_last = (string) getRequest('param2');
			$type_id = (int) getRequest('param3');
			$connection = ConnectionPool::getInstance()->getConnection();

			if ($is_last != 'false') {
				$new_group_id = (int) $is_last;
			} else {
				$sql = <<<SQL
SELECT fc.group_id
FROM cms3_object_field_groups ofg, cms3_fields_controller fc
WHERE ofg.type_id = '{$type_id}' AND fc.group_id = ofg.id AND fc.field_id = '{$before_field_id}'
SQL;
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);

				$new_group_id = null;

				if ($result->length() > 0) {
					$fetchResult = $result->fetch();
					$new_group_id = array_shift($fetchResult);
				}
			}

			$sql = <<<SQL
SELECT fc.group_id
FROM cms3_object_field_groups ofg, cms3_fields_controller fc
WHERE ofg.type_id = '{$type_id}' AND fc.group_id = ofg.id AND fc.field_id = '{$field_id}'
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$group_id = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$group_id = array_shift($fetchResult);
			}

			if ($is_last == 'false') {
				$after_field_id = $before_field_id;
			} else {
				$sql = <<<SQL
SELECT field_id
FROM cms3_fields_controller
WHERE group_id = '{$group_id}' ORDER BY ord DESC LIMIT 1
SQL;

				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$after_field_id = 0;

				if ($result->length() > 0) {
					$fetchResult = $result->fetch();
					$after_field_id = array_shift($fetchResult);
				}
			}

			$type = umiObjectTypesCollection::getInstance()
				->getType($type_id);

			if (!$type instanceof iUmiObjectType) {
				$this->module->flush();
			}

			$fieldsGroup = $type->getFieldsGroup($group_id);

			if (!$fieldsGroup instanceof iUmiFieldsGroup) {
				$this->module->flush();
			}

			$is_last = $is_last == 'false';
			$fieldsGroup->moveFieldAfter($field_id, $after_field_id, $new_group_id, $is_last);
			$this->module->flush();
		}

		/**
		 * Меняет порядок группы полей внутри типа данных
		 * @throws Exception
		 * @throws coreException
		 */
		public function json_move_group_after() {
			$group_id = (int) getRequest('param0');
			$before_group_id = (string) getRequest('param1');
			$type_id = (int) getRequest('param2');
			$connection = ConnectionPool::getInstance()->getConnection();

			if ($before_group_id != 'false') {
				$escapedId = (int) $before_group_id;
				$sql = "SELECT ord FROM cms3_object_field_groups WHERE type_id = '{$type_id}' AND id = '$escapedId'";
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$neword = 0;

				if ($result->length() > 0) {
					$fetchResult = $result->fetch();
					$neword = array_shift($fetchResult);
				}
			} else {
				$sql = "SELECT MAX(ord) FROM cms3_object_field_groups WHERE type_id = '{$type_id}'";
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$neword = 5;

				if ($result->length() > 0) {
					$fetchResult = $result->fetch();
					$neword = array_shift($fetchResult) + 5;
				}
			}

			$type = umiObjectTypesCollection::getInstance()
				->getType($type_id);

			if (!$type instanceof iUmiObjectType) {
				$this->module->flush();
			}

			$before_group_id = $before_group_id == 'false';
			$type->setFieldGroupOrd($group_id, $neword, $before_group_id);
			$this->module->flush();
		}

		/**
		 * Удаляет поле
		 * @throws Exception
		 * @throws coreException
		 */
		public function json_delete_field() {
			$field_id = (int) getRequest('param0');
			$type_id = (int) getRequest('param1');
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT fc.group_id
FROM cms3_object_field_groups ofg, cms3_fields_controller fc
WHERE ofg.type_id = '{$type_id}' AND fc.group_id = ofg.id AND fc.field_id = '{$field_id}'
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$group_id = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$group_id = array_shift($fetchResult);
			}

			$type = umiObjectTypesCollection::getInstance()
				->getType($type_id);

			if (!$type instanceof iUmiObjectType) {
				$this->module->flush();
			}

			$fieldsGroup = $type->getFieldsGroup($group_id);

			if (!$fieldsGroup instanceof iUmiFieldsGroup) {
				$this->module->flush();
			}

			$fieldsGroup->detachField($field_id);
			$this->module->flush();
		}

		/**
		 * Удаляет группу полей
		 * @throws coreException
		 */
		public function json_delete_group() {
			$group_id = (int) getRequest('param0');
			$type_id = (int) getRequest('param1');
			$type = umiObjectTypesCollection::getInstance()
				->getType($type_id);

			if (!$type instanceof iUmiObjectType) {
				$this->module->flush();
			}

			$type->delFieldsGroup($group_id);
			$this->module->flush();
		}

		/**
		 * Возвращает идентификатор поля, которое соответствует заданным параметрам,
		 * и идентификатор типа данных, к которому прикреплено поле.
		 * @param array $typeIdList список типов данных
		 * @param string $fieldName строковой идентификатор поля
		 * @param string $fieldTitle название поля
		 * @param string $fieldDataTypeId идентификатор типа поля
		 * @return array
		 *
		 * [
		 *     "field_id" => iUmiField->getId(),
		 *     "type_id" => iUmiTypeId->getId(),
		 * ]
		 */
		protected function getSameFieldIdAndTypeId(array $typeIdList, $fieldName, $fieldTitle, $fieldDataTypeId) {
			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$umiFields = umiFieldsCollection::getInstance();

			foreach ($typeIdList as $typeId) {
				$type = $umiObjectTypes->getType($typeId);

				if (!$type instanceof iUmiObjectType) {
					continue;
				}

				$fieldId = $type->getFieldId($fieldName);
				$field = $umiFields->getById($fieldId);

				if (!$field instanceof iUmiField) {
					$umiObjectTypes->unloadType($typeId);
					continue;
				}

				if ($field->getTitle() != $fieldTitle || $field->getFieldTypeId() != $fieldDataTypeId) {
					$umiObjectTypes->unloadType($typeId);
					continue;
				}

				$umiObjectTypes->unloadType($typeId);
				return [
					'field_id' => $fieldId,
					'type_id' => $typeId
				];
			}

			return [];
		}
	}
