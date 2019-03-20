<?php
	/**
	 * Класс для осуществления вывода объектов каталога и форм фильтров для него, с учетом параметров фильтрации.
	 * Данные для формирования формы фильтров получаются с помощью метода getSmartFilters().
	 * Данные для формирования списка объектов каталога получаются с помощью метода getSmartCatalog().
	 * Остальные методы для внутреннего использования, они публичные из-за особенностей реализации ООП.
	 */
	abstract class __filter_catalog {
		/* @const строковой идентификатор поля раздела каталога, в котором хранится id проиндексированного раздела каталога */
		const FILTER_INDEX_SOURCE_FIELD_NAME = 'index_source';
		/* @const строковой идентификатор поля раздела каталога, в котором хранится уровень вложенности создания индекса */
		const FILTER_INDEX_NESTING_DEEP_FIELD_NAME = 'index_level';
		/* @const строковой идентификатор поля раздела каталога, в котором хранится дата переиндексации раздела */
		const FILTER_INDEX_INDEXATION_DATE = 'index_date';
		/* @const строковой идентификатор поля раздела каталога, в котором хранится состояние переиндексации раздела */
		const FILTER_INDEX_INDEXATION_STATE = 'index_state';
		/* @const строковой идентификатор поля раздела каталога, в котором хранится флаг необходимости индексации */
		const FILTER_INDEX_INDEXATION_NEEDED = 'index_choose';
		/**
		 * @const int CRON_FILTER_INDEXATION_LIMIT количество объектов каталога,
		 * обрабатываемых при индексации фильтров по крону за одну итерацию
		 */
		const CRON_FILTER_INDEXATION_LIMIT = 25;

		/**
		 * Выводит данные для формирования формы фильтрации объектов каталога
		 * @param string $template имя файла шаблона (только для tpl)
		 * @param int $categoryId ид раздела каталога, по товарам которого нужно вывести фильтр
		 * @param bool $isAdaptive является ли фильтр адаптивным, то есть уточняет ли данные фильтрации с учетом уже переданных данных
		 * @param int $level уровень вложенности раздела каталога $categoryId, на котором размещены необходимые объекты каталога
		 * @param bool|int $typeId ид объектного типа данных, к которому принадлежат искомые объекты каталога
		 * @param bool|string $groupNames строковые идентификаторы групп разделенные символом ";", фильтруемые поля из которых нужно вывести
		 * @return mixed
		 * @throws publicException если не удалось получить объект страницы по id = $categoryId
		 * @throws publicException если не удалось получить объект тип данных по id = $typeId
		 * @throws publicException если не удалось получить или вычислить строковые идентификатор полей
		 * @throws publicException если было получено поле с неподдерживаемым типом
		 */
		public function getSmartFilters($template = 'default', $categoryId, $isAdaptive = true, $level, $typeId = false, $groupNames = false) {
			if (!is_string($template)) {
				$template = 'default';
			}

			list(
				$templateBlock,
				$templateEmpty,
				$groupTemplate,
				$fieldTemplate,
				$fieldTemplateString,
				$fieldTemplateStringItem,
				$fieldTemplateColor,
				$fieldTemplateColorItem,
				$fieldTemplateDate,
				$fieldTemplateNumeric,
				$fieldTemplatePrice,
				$fieldTemplateBoolean,
				$fieldTemplateBooleanItem,
				$fieldTemplateFile,
				$fieldTemplateFileItem,
				$fieldTemplateOptioned,
				$fieldTemplateOptionedItem,
				$fieldTemplateTags,
				$fieldTemplateTagsItem,
				$fieldTemplateSymlink,
				$fieldTemplateSymlinkItem,
				$fieldTemplateText,
				$fieldTemplateTextItem,
				$fieldTemplateRelation,
				$fieldTemplateRelationMultiple,
				$fieldTemplateRelationItem,
				$fieldTemplateRelationMultipleItem
				) = def_module::loadTemplates (
				'catalog/' . $template,
				'search_block',
				'search_block_empty',
				'field_group_block',
				'field_block',
				'field_block_string',
				'field_block_string_item',
				'field_block_color',
				'field_block_color_item',
				'field_block_date',
				'field_block_numeric',
				'field_block_price',
				'field_block_boolean',
				'field_block_boolean_item',
				'field_block_file',
				'field_block_file_item',
				'field_block_optioned',
				'field_block_optioned_item',
				'field_block_tags',
				'field_block_tags_item',
				'field_block_symlink',
				'field_block_symlink_item',
				'field_block_text',
				'field_block_text_item',
				'field_block_relation',
				'field_block_relation_multiple',
				'field_block_relation_item',
				'field_block_relation_multiple_item'
			);

			$umiHierarchy = umiHierarchy::getInstance();
			/* @var iUmiHierarchyElement $category */
			$category = $umiHierarchy->getElement($categoryId);

			if (!$category instanceof iUmiHierarchyElement) {
				throw new publicException(__METHOD__ . ': cant get page by id = '. $categoryId);
			}

			if (!is_numeric($level)) {
				$level = 1;
			}

			$umiTypesHelper = umiTypesHelper::getInstance();
			$hierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName('catalog', 'object');

			if (!is_numeric($typeId)) {
				$typeId = $umiHierarchy->getDominantTypeId($categoryId, $level, $hierarchyTypeId);
			}

			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			/* @var iUmiObjectType $type */
			$type = $umiObjectTypes->getType($typeId);

			if (!$type instanceof iUmiObjectType) {
				throw new publicException(__METHOD__ . ': cant get type by id = '. $typeId);
			}

			$groups = $this->getGroupsNames($groupNames, $type);

			if (count($groups) == 0) {
				throw new publicException(__METHOD__ . ': cant get field groups');
			}

			try {
				$filterIndexSource = $this->getCategoryFilterSource($category);
			} catch (publicException $exception) {
				return $this->makeEmptyFilterResponse($categoryId, $level, $typeId, $templateEmpty);
			}

			$groupsToFields = $this->getFilteredFieldsNamesByGroups($type, $groups);
			$indexGenerator = new FilterIndexGenerator($hierarchyTypeId, 'pages');
			$indexGenerator->setHierarchyCondition($filterIndexSource->getId(), $level);
			$fieldsInIndex = array_keys($indexGenerator->getFilteredFields());

			$filteredFieldsNames = array();
			foreach ($groupsToFields as $groupData) {
				foreach ($groupData['fields'] as $key => $fieldName) {
					if (in_array($fieldName, $fieldsInIndex)) {
						$filteredFieldsNames[] = $fieldName;
					} else {
						unset($groupData['fields'][$key]);
					}
				}
			}

			if (count($filteredFieldsNames) == 0) {
				return $this->makeEmptyFilterResponse($categoryId, $level, $typeId, $templateEmpty);
			}

			$categoriesIds = $this->getCategoriesIds($categoryId, $level);
			$queriesMaker = $this->getFilterQueriesMaker(
				$indexGenerator, $categoriesIds, $filteredFieldsNames
			);

			if (!$queriesMaker instanceof FilterQueriesMaker) {
				return $this->makeEmptyFilterResponse($categoryId, $level, $typeId, $templateEmpty);
			}

			if ($isAdaptive) {
				$queriesMaker->disableShowingSelectedValues();
			} else {
				$queriesMaker->disableUpdatingSelectedFilters();
			}

			$filtersData = $queriesMaker->getFiltersData();
			$total = $queriesMaker->getFilteredEntitiesCount();

			$fields = array();
			foreach ($filtersData as $fieldName => $fieldData) {
				/* @var iUmiFieldType $fieldType */
				$fieldType = $fieldData['type'];
				$fieldDataType = $fieldType->getDataType();
				$fieldTemplate = null;
				$fieldTemplateItem = null;
				switch ($fieldDataType) {
					case 'string':
					case 'password': {
						$fieldTemplate = $fieldTemplateString;
						$fieldTemplateItem = $fieldTemplateStringItem;
						break;
					}
					case 'color': {
						$fieldTemplate = $fieldTemplateColor;
						$fieldTemplateItem = $fieldTemplateColorItem;
						break;
					}
					case 'date': {
						$fieldTemplate = $fieldTemplateDate;
						break;
					}
					case 'int':
					case 'float':
					case 'link_to_object_type':
					case 'counter': {
						$fieldTemplate = $fieldTemplateNumeric;
						break;
					}
					case 'price': {
						$fieldTemplate = $fieldTemplatePrice;
						break;
					}
					case 'boolean': {
						$fieldTemplate = $fieldTemplateBoolean;
						$fieldTemplateItem = $fieldTemplateBooleanItem;
						break;
					}
					case 'file':
					case 'img_file':
					case 'swf_file':
					case 'multiple_image':
					case 'video_file': {
						$fieldTemplate = $fieldTemplateFile;
						$fieldTemplateItem = $fieldTemplateFileItem;
						break;
					}
					case 'optioned': {
						$fieldTemplate = $fieldTemplateOptioned;
						$fieldTemplateItem = $fieldTemplateOptionedItem;
						break;
					}
					case 'tags': {
						$fieldTemplate = $fieldTemplateTags;
						$fieldTemplateItem = $fieldTemplateTagsItem;
						break;
					}
					case 'symlink': {
						$fieldTemplate = $fieldTemplateSymlink;
						$fieldTemplateItem = $fieldTemplateSymlinkItem;
						break;
					}
					case 'text':
					case 'wysiwyg': {
						$fieldTemplate = $fieldTemplateText;
						$fieldTemplateItem = $fieldTemplateTextItem;
						break;
					}
					case 'relation': {
						if ($fieldType->getIsMultiple()) {
							$fieldTemplate = $fieldTemplateRelationMultiple;
							$fieldTemplateItem = $fieldTemplateRelationMultipleItem;
						} else {
							$fieldTemplate = $fieldTemplateRelation;
							$fieldTemplateItem = $fieldTemplateRelationItem;
						}
						break;
					}
					default: {
						throw new publicException(__METHOD__ . ': unsupported field type: ' . $fieldDataType);
					}
				}
				$fields[$fieldName] = $this->parseFieldValue($fieldData, $fieldType, $fieldTemplate, $fieldTemplateItem);
			}

			if ($total == 0) {
				return $this->makeEmptyFilterResponse($categoryId, $level, $typeId, $templateEmpty);
			}

			$result = array();
			$result['attribute:category-id'] = $categoryId;
			$result['attribute:level'] = $level;
			$result['attribute:type-id'] = $typeId;
			$result['attribute:total'] = $total;
			$result['attribute:is-adaptive'] = (int) $isAdaptive;

			$groupsBlocks = array();

			foreach ($groupsToFields as $groupName => $groupData) {
				$groupBlock = array();
				$groupBlock['attribute:name'] = $groupName;
				$groupBlock['attribute:title'] = $groupData['title'];
				$fieldsBlocks = array();
				foreach ($groupData['fields'] as $fieldName) {
					if (isset($fields[$fieldName])) {
                        $fieldsBlocks[] = $fields[$fieldName];
					}
				}
				$groupBlock['nodes:field'] = $fieldsBlocks;
				if (count($groupBlock['nodes:field']) > 0) {
					$groupsBlocks[] = def_module::parseTemplate($groupTemplate, $groupBlock);
				}
			}

			$result['nodes:group'] = $groupsBlocks;
			return def_module::parseTemplate($templateBlock, $result, $categoryId);
		}

		/**
		 * Возвращает ответ при пустом результате работы метода getSmartFilters()
		 * @param int $categoryId ид раздела каталога
		 * @param int $level уровень вложенности каталога
		 * @param int $typeId ид объектного типа данных
		 * @param string $template имя файла шаблона (только для tpl)
		 * @return mixed
		 */
		public function makeEmptyFilterResponse($categoryId, $level, $typeId, $template) {
			$result = array();
			$result['attribute:category_id'] = $categoryId;
			$result['attribute:level'] = $level;
			$result['attribute:type_id'] = $typeId;
			$result['attribute:total'] = 0;
			return def_module::parseTemplate($template, $result, $categoryId);
		}

		/**
		 * Возвращает строковые идентификаторы групп, фильтруемые поля которых должны
		 * присутсовать в форме фильтрации.
		 * @param string $groupNames строковые идентификаторы групп разделенные символом ";"
		 * @param iUmiObjectType $type объект типа данных
		 * @return array
		 */
		public function getGroupsNames($groupNames, iUmiObjectType $type) {
			$allGroupsNames = array();
			/* @var iUmiFieldsGroup $group */
			foreach ($type->getFieldsGroupsList() as $group) {
				$allGroupsNames[] = $group->getName();
			}

			$groups = array();

			if (is_string($groupNames)) {
				$groupNames = explode(";", trim($groupNames));
				foreach($groupNames as $groupName) {
					if (in_array($groupName, $allGroupsNames)) {
						$groups[] = $groupName;
					}
				}
			} else {
				$groups = $allGroupsNames;
			}
			return $groups;
		}

		/**
		 * Возвращает объект создателя запросов к индексу для форм фильтрации
		 * @param FilterIndexGenerator $indexGenerator объект генератора индекса
		 * @param array $categoriesIds идентификаторы разделов каталога, в которых лежат искомые объекты
		 * @param array $fieldsNames строковые идентификаторы поле, необходимых в форме фильтрации
		 * @return FilterQueriesMaker
		 */
		public function getFilterQueriesMaker(FilterIndexGenerator $indexGenerator, array $categoriesIds, array $fieldsNames) {
			$cmsController = cmsController::getInstance();
			$domainId = $cmsController->getCurrentDomain()->getId();
			$langId = $cmsController->getCurrentLang()->getId();

			$queriesMaker = new FilterQueriesMaker($indexGenerator);
			$queriesMaker->setDomainIds(array($domainId));
			$queriesMaker->setLangIds(array($langId));
			$queriesMaker->ignoreVirtualCopies();

			$queriesMaker->setParentIds($categoriesIds);
			$queriesMaker->setFilteredFieldsNames($fieldsNames);
			$queriesMaker->parseFilters();

			return $queriesMaker;
		}

		/**
		 * Возвращает данные о поле фильтра, подготовленные для вывода
		 * @param array $fieldData данные поле (тип поля, варианты значений поля, выбранные варианты значения поля)
		 * @param iUmiFieldType $fieldType объект поля
		 * @param mixed $fieldTemplate шаблон отображения блока поля (для tpl)
		 * @param mixed $fieldTemplateItem шаблон отображения варианта значения поля (для tpl)
		 * @return mixed
		 * @throws publicAdminException если передано поле с неподдерживаемым типом
		 */
		public function parseFieldValue(array $fieldData, iUmiFieldType $fieldType, $fieldTemplate, $fieldTemplateItem) {
			/* @var iUmiField $field */
			$umiField = $fieldData['field'];
			$fieldValues = $fieldData['values'];
			$selectedValues = array();
			if (isset($fieldData['selected'])) {
				$selectedValues = $fieldData['selected'];
			}
			switch ($fieldType->getDataType()) {
				case 'date':
				case 'int':
				case 'price':
				case 'float':
				case 'counter': {
					return $this->parseRangedValue($umiField, $fieldType, $fieldValues, $fieldTemplate, $selectedValues);
				}
				case 'boolean':
				case 'file':
				case 'img_file':
				case 'swf_file':
				case 'video_file':
				case 'string':
				case 'color':
				case 'password':
				case 'optioned':
				case 'tags':
				case 'symlink':
				case 'text':
				case 'wysiwyg':
				case 'multiple_image':
				case 'link_to_object_type':
				case 'relation': {
					return $this->parseCommonValue(
						$umiField, $fieldType, $fieldValues, $fieldTemplate, $fieldTemplateItem, $selectedValues
					);
				}
				default: {
					throw new publicAdminException(__METHOD__ . ': unsupported field type: ' . $fieldType->getDataType());
				}
			}
		}

		/**
		 * Возвращает строковые идентификаторы фильтруемых полей, привязанные к строковым идентификаторам групп
		 * @param iUmiObjectType $type объектный тип данных, содержащий группы
		 * @param array $groups строковые идентификаторы групп
		 * @return array
		 * @throws publicAdminException если запрос к бд завершился с ошибкой
		 */
		public function getFilteredFieldsNamesByGroups(iUmiObjectType $type, array $groups) {
			/* @var iUmiObjectType|umiEntinty $type */
			$objectTypeId = $type->getId();
			$connection = ConnectionPool::getInstance()->getConnection();
			$groups = array_map(array($connection, 'escape'), $groups);
			$groups = "'" . implode("', '", $groups) . "'";
			$sql = <<<SQL
SELECT
  cms3_object_field_groups.name as group_name,
  cms3_object_field_groups.title as group_title,
  cms3_object_fields.name as field_name
FROM
  cms3_object_fields
  LEFT JOIN cms3_fields_controller ON cms3_object_fields.id = cms3_fields_controller.field_id
  LEFT JOIN cms3_object_field_groups ON cms3_fields_controller.group_id = cms3_object_field_groups.id
  LEFT JOIN cms3_object_types ON cms3_object_field_groups.type_id = cms3_object_types.id
WHERE
 cms3_object_types.id = $objectTypeId AND
 cms3_object_field_groups.name IN ($groups) AND
 cms3_object_field_groups.is_active = 1 AND
 cms3_object_field_groups.is_visible = 1 AND
 cms3_object_fields.in_filter = 1 AND
 cms3_object_fields.is_visible = 1
SQL;

			try {
				$result = $connection->queryResult($sql);
			} catch (databaseException $e) {
				throw new publicAdminException(__METHOD__ . ': MySQL exception has occurred:' . $e->getCode() . ' ' . $e->getMessage());
			}

			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return array();
			}

			$groupsToFields = array();
			foreach($result as $row) {
				$groupsToFields[$row['group_name']]['fields'][] = $row['field_name'];
				$groupsToFields[$row['group_name']]['title'] = $type->translateLabel($row['group_title']);
			}
			return $groupsToFields;
		}

		/**
		 * Возвращает данные о поле фильтра, содержащем числовые данные.
		 * Такие поля поддерживаемым диапазонные значения.
		 * @param iUmiField $field объект поля
		 * @param iUmiFieldType $fieldType объект типа поля
		 * @param array $values варианты значений поля
		 * @param mixed $fieldTemplate шаблон отображения блока поля (для tpl)
		 * @param array $selectedValues выбранные варианты значений поля
		 * @return mixed
		 */
		public function parseRangedValue(iUmiField $field, iUmiFieldType $fieldType, array $values, $fieldTemplate, array $selectedValues) {
			$fieldData = array();
			$fieldName = $field->getName();
			$fieldData['attribute:name'] = $fieldName;
			$fieldData['attribute:title'] = $field->getTitle();
			$fieldData['attribute:data-type'] = $fieldType->getDataType();
			$fieldData['attribute:is-multiple'] = ($fieldType->getIsMultiple()) ? 1 : 0;

			if (isset($values['min'])) {
				$minimum = array();
				$minValue = $values['min'];
				$minimum['node:value'] = $minValue;
				$minimum['attribute:link'] = "?filter[$fieldName][from]=$minValue";
				if (isset($selectedValues['from'])) {
					$minimum['attribute:selected'] = $selectedValues['from'];
				}
				$fieldData['minimum'] = $minimum;
			}

			if (isset($values['max'])) {
				$maximum = array();
				$maxValue = $values['max'];
				$maximum['node:value'] = $maxValue;
				$maximum['attribute:link'] = "?filter[$fieldName][to]=$maxValue";
				if (isset($selectedValues['to'])) {
					$maximum['attribute:selected'] = $selectedValues['to'];
				}
				$fieldData['maximum'] = $maximum;
			}

			if (isset($values[0])) {
				$oneValue = array();
				$value = $values[0];
				$oneValue['node:value'] = $value;
				$oneValue['attribute:is-selected'] = in_array($value, $selectedValues);
				$oneValue['attribute:link'] = "?filter[$fieldName]=$value";
				$fieldData['item'] = $oneValue;
			}

			return def_module::parseTemplate($fieldTemplate, $fieldData);
		}

		/**
		 * Возвращает данные о поле фильтра, содержащего строковые данные.
		 * Такие поля поддерживаемым выбор нескольких вариантов значения.
		 * @param iUmiField $field объект поля
		 * @param iUmiFieldType $fieldType объект типа поля
		 * @param array $values варианты значений поля
		 * @param $fieldTemplate шаблон отображения блока поля (для tpl)
		 * @param $fieldTemplateItem шаблон отображения варианта значения поля (для tpl)
		 * @param array $selectedValues выбранные варианты значений поля
		 * @return mixed
		 */
		public function parseCommonValue(iUmiField $field, iUmiFieldType $fieldType, array $values, $fieldTemplate, $fieldTemplateItem, array $selectedValues) {
			$fieldData = array();
			$fieldName = $field->getName();
			$fieldData['attribute:name'] = $fieldName;
			$fieldData['attribute:title'] = $field->getTitle();
			$fieldData['attribute:data-type'] = $fieldType->getDataType();
			$fieldData['attribute:is-multiple'] = ($fieldType->getIsMultiple()) ? 1 : 0;
			$fieldData['attribute:guide-id'] = $field->getGuideId();
			$items = array();
			natsort($values);
			foreach ($values as $value) {
				$item = array();
				$value = htmlspecialchars($value);
				$item['node:value'] = $value;
				$item['attribute:is-selected'] = in_array($value, $selectedValues);
				$item['attribute:link'] = "?filter[$fieldName]=$value";
				$items[] = def_module::parseTemplate($fieldTemplateItem, $item);
			}
			$fieldData['nodes:item'] = $items;
			return def_module::parseTemplate($fieldTemplate, $fieldData);
		}

		/**
		 * Выводит данные для формирования списка объектов каталога, с учетом параметров фильтрации
		 * @param string $template имя шаблона отображения (только для tpl)
		 * @param int $categoryId ид раздела каталога, объекты которого требуется вывести
		 * @param int $limit ограничение количества выводимых объектов каталога
		 * @param bool $ignorePaging игнорировать постраничную навигацию (то есть GET параметр 'p')
		 * @param int $level уровень вложенности раздела каталога $categoryId, на котором размещены необходимые объекты каталога
		 * @param bool $fieldName поле объекта каталога, по которому необходимо произвести сортировку
		 * @param bool $isAsc порядок сортировки
		 * @return mixed
		 * @throws publicException если не удалось получить объект страницы по id = $categoryId
		 */
		public function getSmartCatalog($template = 'default', $categoryId, $limit, $ignorePaging = false, $level = 1, $fieldName = false, $isAsc = true) {
			/* @var catalog|__filter_catalog $this*/

			if (!is_string($template)) {
				$template = 'default';
			}

			list(
				$itemsTemplate,
				$emptyItemsTemplate,
				$emptySearchTemplates,
				$itemTemplate
			) = def_module::loadTemplates(
				'catalog/' . $template,
				'objects_block',
				'objects_block_empty',
				'objects_block_search_empty',
				'objects_block_line'
			);

			$umiHierarchy = umiHierarchy::getInstance();
			/* @var iUmiHierarchyElement $category */
			$category = $umiHierarchy->getElement($categoryId);

			if (!$category instanceof iUmiHierarchyElement) {
				throw new publicException(__METHOD__ . ': cant get page by id = '. $categoryId);
			}

			$limit = ($limit) ? $limit : $this->per_page;
			$currentPage = ($ignorePaging) ? 0 : (int) getRequest('p');
			$offset = $currentPage * $limit;

			if (!is_numeric($level)) {
				$level = 1;
			}

			$filteredProductsIds = null;
			$queriesMaker = null;
			if (is_array(getRequest('filter'))) {
				$emptyItemsTemplate = $emptySearchTemplates;
				$queriesMaker = $this->getCatalogQueriesMaker($category, $level);

				if (!$queriesMaker instanceof FilterQueriesMaker) {
					return $this->makeEmptyCatalogResponse($emptyItemsTemplate, $categoryId);
				}

				$filteredProductsIds = $queriesMaker->getFilteredEntitiesIds();

				if (count($filteredProductsIds) == 0) {
					return $this->makeEmptyCatalogResponse($emptyItemsTemplate, $categoryId);
				}
			}

			$products = new selector('pages');
			$products->types('hierarchy-type')->name('catalog', 'object');

			if (is_null($filteredProductsIds)) {
				$products->where('hierarchy')->page($categoryId)->childs($level);
			} else {
				$products->where('id')->equals($filteredProductsIds);
			}

			if ($fieldName) {
				if ($isAsc) {
					$products->order($fieldName)->asc();
				} else {
					$products->order($fieldName)->desc();
				}
			} else {
				$products->order('ord')->asc();
			}

			if ($queriesMaker instanceof FilterQueriesMaker) {
				if (!$queriesMaker->isPermissionsIgnored()) {
					$products->option('no-permissions')->value(true);
				}
			}

			$products->option('load-all-props')->value(true);
			$products->limit($offset, $limit);
			$pages = $products->result();
			$total = $products->length();

			if ($total == 0) {
				return $this->makeEmptyCatalogResponse($emptyItemsTemplate, $categoryId);
			}

			$result = array();
			$items = array();
			$umiLinksHelper = umiLinksHelper::getInstance();
			/* @var iUmiHierarchyElement|umiEntinty $page */
			foreach ($pages as $page) {
				$item = array();
				$pageId = $page->getId();
				$item['attribute:id'] = $pageId;
				$item['attribute:alt_name'] = $page->getAltName();
				$item['attribute:link'] = $umiLinksHelper->getLinkByParts($page);
				$item['xlink:href'] ='upage://' . $pageId;
				$item['node:text'] = $page->getName();
				$items[] = def_module::parseTemplate($itemTemplate, $item, $pageId);
				def_module::pushEditable('catalog', 'object', $pageId);
				$umiHierarchy->unloadElement($pageId);
			}

			$result['subnodes:lines'] = $items;
			$result['numpages'] = umiPagenum::generateNumPage($total, $limit);
			$result['total'] = $total;
			$result['per_page'] = $limit;
			$result['category_id'] = $categoryId;

			return def_module::parseTemplate($itemsTemplate, $result, $categoryId);
		}

		/**
		 * Возвращает ответ при пустом результате работы метода getSmartCatalog()
		 * @param string $emptyItemsTemplate шаблон отображения (только для tpl)
		 * @param int $categoryId ид раздела каталога
		 * @return mixed
		 */
		public function makeEmptyCatalogResponse($emptyItemsTemplate, $categoryId) {
			$item = array();
			$item['numpages'] = umiPagenum::generateNumPage(0, 0);
			$item['lines'] = "";
			$item['total'] = 0;
			$item['per_page'] = 0;
			$item['category_id'] = $categoryId;
			return def_module::parseTemplate($emptyItemsTemplate, $item, $categoryId);
		}

		/**
		 * Возвращает объект генератора запросов к индексу фильтров, либо false, если
		 * объект не удалось получить.
		 * @param iUmiHierarchyElement $category объект страницы раздела каталога, объекты которого требуется вывести
		 * @param int $level уровень вложенности раздела каталога $categoryId, на котором размещены необходимые объекты каталога
		 * @return bool|FilterQueriesMaker
		 */
		public function getCatalogQueriesMaker(iUmiHierarchyElement $category, $level) {
			/* @var iUmiHierarchyElement|umiEntinty $category*/
			$categoryId = $category->getId();
			try {
				$filterIndexSource = $this->getCategoryFilterSource($category);
			} catch (publicException $exception) {
				return false;
			}

			$filterIndexSourceLevel = (int) $filterIndexSource->getValue(self::FILTER_INDEX_NESTING_DEEP_FIELD_NAME);

			$umiTypesHelper = umiTypesHelper::getInstance();
			$hierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName('catalog', 'object');

			$indexGenerator = new FilterIndexGenerator($hierarchyTypeId, 'pages');
			$indexGenerator->setHierarchyCondition($filterIndexSource->getId(), $filterIndexSourceLevel);

			$cmsController = cmsController::getInstance();
			$domainId = $cmsController->getCurrentDomain()->getId();
			$langId = $cmsController->getCurrentLang()->getId();

			$queriesMaker = new FilterQueriesMaker($indexGenerator);
			$queriesMaker->setDomainIds(array($domainId));
			$queriesMaker->setLangIds(array($langId));
			$queriesMaker->ignoreVirtualCopies();

			$categoriesIds = $this->getCategoriesIds($categoryId, $level);

			$queriesMaker->setParentIds($categoriesIds);
			$queriesMaker->parseFilters();

			return $queriesMaker;
		}

		/**
		 * Возвращает идентификаторы разделов каталога, включая переданный, дочерние переданному разделу
		 * каталога на переданном уровне вложенности.
		 * @param int $categoryId ид раздела каталога
		 * @param int $level уровень вложенности
		 * @return array
		 */
		public function getCategoriesIds($categoryId, $level) {
			$categoriesIds = array($categoryId);

			if ($level > 1) {
				$childrenCategoriesIds = $this->getChildrenCategories($categoryId, $level);
				$categoriesIds = array_merge($categoriesIds, $childrenCategoriesIds);
			}

			return $categoriesIds;
		}

		/**
		 * Возвращает категории, дочерние переданному разделу
		 * @param int $parentId ид раздела каталога
		 * @param int $level уровень вложенности
		 * @return array
		 */
		public function getChildrenCategories($parentId, $level) {
			$categories = new selector('pages');
			$categories->types('object-type')->name('catalog', 'category');
			$categories->where('hierarchy')->page($parentId)->childs($level);
			$categories->option('return')->value('id');
			$categories = $categories->result();

			if (count($categories) == 0) {
				return array();
			}

			$categoriesIds = array();
			foreach ($categories as $categoryId) {
				$categoriesIds[] = $categoryId['id'];
			}
			return $categoriesIds;
		}

		/**
		 * Записывает ид раздела-источника индекса в раздел каталога.
		 * Для раздела-источника дополнительно сохраняется уровень вложенности.
		 * @param iUmiHierarchyElement $category объект категории
		 * @param int $sourceCategoryId ид раздела-источника
		 * @param int $level уровень вложенности
		 * @return void
		 */
		public function setFilterSourceToCategory(iUmiHierarchyElement $category,  $sourceCategoryId, $level) {
			/* @var iUmiHierarchyElement|umiEntinty $category */
			if ($category->getId() == $sourceCategoryId) {
				$category->setValue(self::FILTER_INDEX_NESTING_DEEP_FIELD_NAME, $level);
			}
			$category->setValue(self::FILTER_INDEX_SOURCE_FIELD_NAME, $sourceCategoryId);
			$category->commit();
		}

		/**
		 * Возвращает объект страница раздела-источника индекса для переданного раздела
		 * @param iUmiHierarchyElement $category объект раздела, для которого нужно определить источник индекса
		 * @return iUmiHierarchyElement|umiEntinty
		 * @throws publicException если не удалось определить раздел-источник или он некорректен
		 */
		public function getCategoryFilterSource(iUmiHierarchyElement $category) {
			$sourceCategoryId = $category->getValue(self::FILTER_INDEX_SOURCE_FIELD_NAME);

			if (!is_numeric($sourceCategoryId)) {
				/* @var iUmiHierarchyElement|umiEntinty $category */
				throw new publicException(__METHOD__ . ': cant take index source from category with id = '. $category->getId());
			}

			/* @var iUmiHierarchyElement|umiEntinty $sourceCategory */
			$sourceCategory = umiHierarchy::getInstance()->getElement($sourceCategoryId);

			if (!$sourceCategory instanceof iUmiHierarchyElement) {
				throw new publicException(__METHOD__ . ': index source not found');
			}

			$umiTypesHelper = umiTypesHelper::getInstance();
			$hierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName('catalog', 'category');

			if ($hierarchyTypeId !== $sourceCategory->getTypeId()) {
				throw new publicException(__METHOD__ . ': wrong index source given');
			}

			return $sourceCategory;
		}
	}
?>