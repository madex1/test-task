<?php

	use UmiCms\Service;

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class CatalogMacros {

		/** @var catalog $module */
		public $module;

		/**
		 * Возвращает содержимое раздела каталога по умолчанию
		 * @param string $template имя шаблона (для tpl)
		 * @param bool|int|string $element_path идентификатор или адрес раздела каталога
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function category($template = 'default', $element_path = false) {
			if (!$template) {
				$template = 'default';
			}

			list($template_block) = catalog::loadTemplates('catalog/' . $template, 'category');

			$category_id = $this->module->analyzeRequiredPath($element_path);
			$controller = cmsController::getInstance();

			$hierarchy = umiHierarchy::getInstance();
			$umiLinksHelper = umiLinksHelper::getInstance();

			if (
				!$category_id && $category_id = getRequest('param0') &&
					$controller->getCurrentModule() == 'catalog' && $controller->getCurrentMethod() == 'category'
			) {
				$category = $hierarchy->getElement($category_id);
				$link = $umiLinksHelper->getLink($category);
				$this->module->redirect($link);
			}

			$category = $hierarchy->getElement($category_id);
			$link = $umiLinksHelper->getLink($category);
			$block_arr = [
				'category_id' => $category_id,
				'category_path' => $link,
				'link' => $link
			];

			catalog::pushEditable('catalog', 'category', $category_id);
			return catalog::parseTemplate($template_block, $block_arr, $category_id);
		}

		/**
		 * Возвращает содержимое объекта каталога по умолчанию
		 * @param string $template имя шаблона (для tpl)
		 * @param bool|int|string $element_path идентификатор или адрес объекта каталога
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function object($template = 'default', $element_path = false) {
			if (!$template) {
				$template = 'default';
			}

			$element_id = $this->module->analyzeRequiredPath($element_path);

			catalog::pushEditable('catalog', 'object', $element_id);
			return $this->viewObject($element_id, $template);
		}

		/**
		 * Возвращает данные объекта каталога
		 * @param int|string $element_id идентификатор или адрес объекта каталога
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed|string
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function viewObject($element_id, $template = 'default') {
			if (!$template) {
				$template = 'default';
			}

			$element_id = $this->module->analyzeRequiredPath($element_id);
			$element = umiHierarchy::getInstance()->getElement($element_id);

			if (!$element instanceof iUmiHierarchyElement) {
				return '';
			}

			$block_arr = [];
			list($template_block) = catalog::loadTemplates(
				'catalog/' . $template,
				'view_block'
			);

			$block_arr['id'] = $element_id;
			$block_arr['name'] = $element->getName();
			$block_arr['alt_name'] = $element->getAltName();
			$block_arr['link'] = umiLinksHelper::getInstance()->getLink($element);

			catalog::pushEditable('catalog', 'object', $element_id);
			return catalog::parseTemplate($template_block, $block_arr, $element_id);
		}

		/**
		 * Возвращает список разделов каталога, дочерних заданной странице
		 * @param string $template имя шаблона (для tpl)
		 * @param bool|int|string|array $rootIdOrList идентификатор или адрес родительской страницы, или их список
		 * @param bool|int $limit ограничение на количество выводимых разделов
		 * @param bool $ignorePaging игнорировать пагинацию
		 * @param int $level уровень вложенности, на котором необходимо искать разделы
		 * @return array
		 * @throws publicException
		 * @throws selectorException
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function getCategoryList(
			$template = 'default',
			$rootIdOrList = false,
			$limit = false,
			$ignorePaging = false,
			$level = 0
		) {
			/** @var catalog|CatalogMacros $module */
			$module = $this->module;
			$rootIdOrList = ((string) $rootIdOrList == '0') ? $rootIdOrList : $module->analyzeRequiredPath($rootIdOrList);

			if ($rootIdOrList === false) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $rootIdOrList));
			}

			$rootIdList = (array) $rootIdOrList;
			$limit = $limit ?: $module->per_page;
			$queryBuilder = $module->getCategoriesSelector($rootIdList, $level, $ignorePaging, $limit);
			$pageList = $queryBuilder->result();

			list($resultTemplate, $emptyResultTemplate, $itemTemplate) = catalog::loadTemplates(
				'catalog/' . ($template ?: 'default'),
				'category_block',
				'category_block_empty',
				'category_block_line'
			);

			$firstRootId = getFirstValue($rootIdList);
			$result = [
				'attribute:category-id' => $firstRootId,
				'void:category_id' => $firstRootId,
			];

			if (empty($pageList)) {
				return catalog::parseTemplate($emptyResultTemplate, $result, $firstRootId);
			}

			$linkGenerator = umiLinksHelper::getInstance();
			$itemList = [];
			$firstItemLevel = null;

			foreach ($pageList as $page) {
				if (!$page instanceof iUmiHierarchyElement) {
					continue;
				}

				$link = $linkGenerator->getLinkByParts($page);
				$itemLevel = substr_count(trim($link, '/'), '/') + 1;
				$firstItemLevel = ($firstItemLevel === null) ? $itemLevel : min($firstItemLevel, $itemLevel);

				$itemList[] = [
					'attribute:id' => $page->getId(),
					'void:alt_name' => $page->getAltName(),
					'attribute:link' => $link,
					'xlink:href' => 'upage://' . $page->getId(),
					'node:text' => $page->getName(),
					'attribute:ord' => $page->getOrd(),
					'ord' => $page->getOrd(),
					'attribute:level' => $itemLevel,
					'attribute:parent' => $page->getParentId(),
				];
			}

			foreach ($itemList as $index => $item) {
				$itemList[$index]['attribute:level'] = $item['attribute:level'] - $firstItemLevel;
				$itemList[$index]['level'] = $itemList[$index]['attribute:level'];
			}

			$itemList = umiHierarchy::getInstance()
				->sortByHierarchy($itemList);

			foreach ($itemList as $index => $item) {
				unset($itemList[$index]['ord']);
				unset($itemList[$index]['level']);
			}

			$parsedItemList = [];

			foreach ($itemList as $item) {
				$parsedItemList[] = catalog::parseTemplate($itemTemplate, $item, $item['attribute:id']);
			}

			$result += [
				'attribute:category_level' => $firstItemLevel,
				'subnodes:items' => $parsedItemList,
				'void:lines' => $parsedItemList,
				'total' => $queryBuilder->length(),
				'per_page' => $limit,
			];

			return catalog::parseTemplate($resultTemplate, $result, $firstRootId);
		}

		/**
		 * Возвращает выборку из разделов каталога
		 * @param array $rootIdList список родительских страниц
		 * @param int $level уровень вложенности, на котором необходимо искать разделы
		 * @param bool $ignorePaging игнорировать пагинацию
		 * @param bool|int $limit ограничение на количество выводимых разделов
		 * @return selector
		 * @throws selectorException
		 */
		public function getCategoriesSelector($rootIdList, $level, $ignorePaging, $limit) {
			$queryBuilder = Service::SelectorFactory()
				->createPageTypeName('catalog', 'category');
			$level = $level ?: (int) getRequest('param4');
			$level = ($level == -1) ? 100 : (int) $level + 1;

			foreach ($rootIdList as $rootId) {
				$queryBuilder->where('hierarchy')->page($rootId)->level($level);
			}

			$pageNumber = $ignorePaging ? 0 : (int) getRequest('p');
			$offset = $pageNumber * $limit;
			$queryBuilder->limit($offset, $limit);
			return $queryBuilder;
		}

		/**
		 * Возвращает данные для формирования формы фильтрации объектов каталога
		 * @param string $template имя файла шаблона (только для tpl)
		 * @param int $categoryId ид раздела каталога, по товарам которого нужно вывести фильтр
		 * @param bool $isAdaptive является ли фильтр адаптивным,
		 * то есть уточняет ли данные фильтрации с учетом уже переданных данных
		 * @param int $level уровень вложенности раздела каталога $categoryId,
		 * на котором размещены необходимые объекты каталога
		 * @param bool|int $typeId ид объектного типа данных, к которому принадлежат искомые объекты каталога
		 * @param bool|string $groupNames строковые идентификаторы групп разделенные символом ";",
		 * фильтруемые поля из которых нужно вывести
		 * @return mixed
		 * @throws publicException если не удалось получить объект страницы по id = $categoryId
		 * @throws publicException если не удалось получить объект тип данных по id = $typeId
		 * @throws publicException если не удалось получить или вычислить строковые идентификатор полей
		 * @throws publicException если было получено поле с неподдерживаемым типом
		 * @throws selectorException
		 * @throws databaseException
		 * @throws publicAdminException
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function getSmartFilters(
			$template = 'default',
			$categoryId,
			$isAdaptive = true,
			$level = 1,
			$typeId = false,
			$groupNames = false
		) {
			if (!is_string($template)) {
				$template = 'default';
			}

			/** @var CatalogMacros|catalog $module */
			$module = $this->module;

			list(
				$templateBlock,
				$templateEmpty,
				$groupTemplate,
				$fieldTemplateWraper,
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
				) = catalog::loadTemplates(
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
				throw new publicException(__METHOD__ . ': cant get page by id = ' . $categoryId);
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
				throw new publicException(__METHOD__ . ': cant get type by id = ' . $typeId);
			}

			$groups = $module->getGroupsNames($groupNames, $type);

			if (umiCount($groups) == 0) {
				throw new publicException(__METHOD__ . ': cant get field groups');
			}

			try {
				$filterIndexSource = $module->getCategoryFilterSource($category);
			} catch (publicException $exception) {
				return $module->makeEmptyFilterResponse($categoryId, $level, $typeId, $templateEmpty);
			}

			$groupsToFields = $module->getFilteredFieldsNamesByGroups($type, $groups);
			$indexGenerator = new FilterIndexGenerator($hierarchyTypeId, 'pages');
			$indexGenerator->setHierarchyCondition($filterIndexSource->getId(), $level);
			$fieldsInIndex = array_keys($indexGenerator->getFilteredFields());

			$filteredFieldsNames = [];
			foreach ($groupsToFields as $groupData) {
				foreach ($groupData['fields'] as $key => $fieldName) {
					if (in_array($fieldName, $fieldsInIndex)) {
						$filteredFieldsNames[] = $fieldName;
					} else {
						unset($groupData['fields'][$key]);
					}
				}
			}

			if (umiCount($filteredFieldsNames) == 0) {
				return $module->makeEmptyFilterResponse($categoryId, $level, $typeId, $templateEmpty);
			}

			$categoriesIds = $module->getCategoriesIds($categoryId, $level);
			$queriesMaker = $module->getFilterQueriesMaker(
				$indexGenerator, $categoriesIds, $filteredFieldsNames
			);

			if (!$queriesMaker instanceof FilterQueriesMaker) {
				return $module->makeEmptyFilterResponse($categoryId, $level, $typeId, $templateEmpty);
			}

			if ($isAdaptive) {
				$queriesMaker->disableShowingSelectedValues();
			} else {
				$queriesMaker->disableUpdatingSelectedFilters();
			}

			$filtersData = $queriesMaker->getFiltersData();
			$total = $queriesMaker->getFilteredEntitiesCount();

			$fields = [];
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
				$fields[$fieldName] = $module->parseFieldValue($fieldData, $fieldType, $fieldTemplate, $fieldTemplateItem);
			}
			if ($total == 0) {
				return $module->makeEmptyFilterResponse($categoryId, $level, $typeId, $templateEmpty);
			}

			$result = [];
			$result['attribute:category-id'] = $categoryId;
			$result['attribute:level'] = $level;
			$result['attribute:type-id'] = $typeId;
			$result['attribute:total'] = $total;
			$result['attribute:is-adaptive'] = (int) $isAdaptive;

			$groupsBlocks = [];

			foreach ($groupsToFields as $groupName => $groupData) {

				$groupBlock = [];
				$groupBlock['attribute:name'] = $groupName;
				$groupBlock['attribute:title'] = $groupData['title'];
				$groupBlock['tip'] = $groupData['tip'];
				$fieldsBlocks = [];

				foreach ($groupData['fields'] as $fieldName) {
					if (isset($fields[$fieldName])) {
						$fieldsBlocks[] = $fields[$fieldName];
					}
				}
				$groupBlock['nodes:field'] = $fieldsBlocks;

				if (umiCount($groupBlock['nodes:field']) > 0) {
					$groupsBlocks[] = catalog::parseTemplate($groupTemplate, $groupBlock);
				}
			}
			$result['nodes:group'] = $groupsBlocks;
			return catalog::parseTemplate($templateBlock, $result, $categoryId);
		}

		/**
		 * Возвращает ответ при пустом результате работы метода getSmartFilters()
		 * @param int $categoryId ид раздела каталога
		 * @param int $level уровень вложенности каталога
		 * @param int $typeId ид объектного типа данных
		 * @param string $template имя файла шаблона (только для tpl)
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function makeEmptyFilterResponse($categoryId, $level, $typeId, $template) {
			$result = [];
			$result['attribute:category_id'] = $categoryId;
			$result['attribute:level'] = $level;
			$result['attribute:type_id'] = $typeId;
			$result['attribute:total'] = 0;
			return catalog::parseTemplate($template, $result, $categoryId);
		}

		/**
		 * Возвращает данные о поле фильтра, подготовленные для вывода
		 * @param array $fieldData данные поле (тип поля, варианты значений поля, выбранные варианты значения поля)
		 * @param iUmiFieldType $fieldType объект поля
		 * @param mixed $fieldTemplate шаблон отображения блока поля (для tpl)
		 * @param mixed $fieldTemplateItem шаблон отображения варианта значения поля (для tpl)
		 * @return mixed
		 * @throws publicAdminException если передано поле с неподдерживаемым типом
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function parseFieldValue(array $fieldData, iUmiFieldType $fieldType, $fieldTemplate, $fieldTemplateItem) {
			/* @var iUmiField $field */
			$umiField = $fieldData['field'];
			$fieldValues = $fieldData['values'];
			$selectedValues = [];

			if (isset($fieldData['selected'])) {
				$selectedValues = $fieldData['selected'];
			}

			/** @var CatalogMacros|catalog $module */
			$module = $this->module;

			switch ($fieldType->getDataType()) {
				case 'date':
				case 'int':
				case 'price':
				case 'float':
				case 'counter': {
					return $module->parseRangedValue($umiField, $fieldType, $fieldValues, $fieldTemplate, $selectedValues);
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
					return $module->parseCommonValue(
						$umiField, $fieldType, $fieldValues, $fieldTemplate, $fieldTemplateItem, $selectedValues
					);
				}
				default: {
					throw new publicAdminException(__METHOD__ . ': unsupported field type: ' . $fieldType->getDataType());
				}
			}
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
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function parseRangedValue(
			iUmiField $field,
			iUmiFieldType $fieldType,
			array $values,
			$fieldTemplate,
			array $selectedValues
		) {
			$fieldData = [];
			$fieldName = $field->getName();
			$fieldData['attribute:name'] = $fieldName;
			$fieldData['attribute:title'] = $field->getTitle();
			$fieldData['attribute:data-type'] = $fieldType->getDataType();
			$fieldData['attribute:is-multiple'] = $fieldType->getIsMultiple() ? 1 : 0;
			$fieldData['attribute:tip'] = $field->getTip();

			if (isset($values['min'])) {
				$minimum = [];
				$minValue = $values['min'];
				$minimum['node:value'] = $minValue;
				$minimum['attribute:link'] = "?filter[$fieldName][from]=$minValue";
				if (isset($selectedValues['from'])) {
					$minimum['attribute:selected'] = $selectedValues['from'];
				}
				$fieldData['minimum'] = $minimum;
				$fieldData['attribute:min'] = $minValue;
			}

			if (isset($values['max'])) {
				$maximum = [];
				$maxValue = $values['max'];
				$maximum['node:value'] = $maxValue;
				$maximum['attribute:link'] = "?filter[$fieldName][to]=$maxValue";
				if (isset($selectedValues['to'])) {
					$maximum['attribute:selected'] = $selectedValues['to'];
				}
				$fieldData['maximum'] = $maximum;
				$fieldData['attribute:max'] = $maxValue;
			}

			if (isset($values[0])) {
				$oneValue = [];
				$value = $values[0];
				$oneValue['node:value'] = $value;
				$oneValue['attribute:is-selected'] = in_array($value, $selectedValues);
				$oneValue['attribute:link'] = "?filter[$fieldName]=$value";
				$oneValue['attribute:value'] = $value;
				$fieldData['item'] = $oneValue;
			}
			return catalog::parseTemplate($fieldTemplate, $fieldData);
		}

		/**
		 * Возвращает данные о поле фильтра, содержащего строковые данные.
		 * Такие поля поддерживаемым выбор нескольких вариантов значения.
		 * @param iUmiField $field объект поля
		 * @param iUmiFieldType $fieldType объект типа поля
		 * @param array $values варианты значений поля
		 * @param mixed $fieldTemplate шаблон отображения блока поля (для tpl)
		 * @param mixed $fieldTemplateItem шаблон отображения варианта значения поля (для tpl)
		 * @param array $selectedValues выбранные варианты значений поля
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function parseCommonValue(
			iUmiField $field,
			iUmiFieldType $fieldType,
			array $values,
			$fieldTemplate,
			$fieldTemplateItem,
			array $selectedValues
		) {
			$fieldData = [];
			$fieldName = $field->getName();
			$fieldData['attribute:name'] = $fieldName;
			$fieldData['attribute:title'] = $field->getTitle();
			$fieldData['attribute:data-type'] = $fieldType->getDataType();
			$fieldData['attribute:is-multiple'] = $fieldType->getIsMultiple() ? 1 : 0;
			$fieldData['attribute:guide-id'] = $field->getGuideId();
			$fieldData['attribute:tip'] = $field->getTip();
			$items = [];
			natsort($values);
			foreach ($values as $value) {
				$item = [];
				$value = htmlspecialchars($value);
				$item['node:value'] = $value;
				$item['attribute:is-selected'] = in_array($value, $selectedValues);
				$item['attribute:link'] = "?filter[$fieldName]=$value";
				$item['attribute:value'] = $value;
				$items[] = catalog::parseTemplate($fieldTemplateItem, $item);
			}
			$fieldData['nodes:item'] = $items;
			return catalog::parseTemplate($fieldTemplate, $fieldData);
		}

		/**
		 * Выводит данные для формирования списка объектов каталога, с учетом параметров фильтрации
		 * @param string $template имя шаблона отображения (только для tpl)
		 * @param int $categoryId ид раздела каталога, объекты которого требуется вывести
		 * @param int $limit ограничение количества выводимых объектов каталога
		 * @param bool $ignorePaging игнорировать постраничную навигацию (то есть GET параметр 'p')
		 * @param int $level уровень вложенности раздела каталога $categoryId,
		 * на котором размещены необходимые объекты каталога
		 * @param bool $fieldName поле объекта каталога, по которому необходимо произвести сортировку
		 * @param bool $isAsc порядок сортировки
		 * @return mixed
		 * @throws publicException если не удалось получить объект страницы по id = $categoryId
		 * @throws coreException
		 * @throws selectorException
		 * @throws ErrorException
		 */
		public function getSmartCatalog(
			$template = 'default',
			$categoryId,
			$limit = 0,
			$ignorePaging = false,
			$level = 1,
			$fieldName = false,
			$isAsc = true
		) {
			/** @var CatalogMacros|catalog $module */
			$module = $this->module;

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
				throw new publicException(__METHOD__ . ': cant get page by id = ' . $categoryId);
			}

			$limit = $limit ?: $this->module->per_page;
			$currentPage = $ignorePaging ? 0 : (int) getRequest('p');
			$offset = $currentPage * $limit;

			if (!is_numeric($level)) {
				$level = 1;
			}

			$filteredProductsIds = null;
			$queriesMaker = null;

			if (is_array(getRequest('filter'))) {
				$emptyItemsTemplate = $emptySearchTemplates;
				$queriesMaker = $module->getCatalogQueriesMaker($category, $level);

				if (!$queriesMaker instanceof FilterQueriesMaker) {
					return $module->makeEmptyCatalogResponse($emptyItemsTemplate, $categoryId);
				}

				$filteredProductsIds = $queriesMaker->getFilteredEntitiesIds();

				if (umiCount($filteredProductsIds) == 0) {
					return $module->makeEmptyCatalogResponse($emptyItemsTemplate, $categoryId);
				}
			}

			$products = new selector('pages');
			$products->types('hierarchy-type')->name('catalog', 'object');

			if ($filteredProductsIds === null) {
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
				return $module->makeEmptyCatalogResponse($emptyItemsTemplate, $categoryId);
			}

			$result = [];
			$items = [];
			$umiLinksHelper = umiLinksHelper::getInstance();
			/* @var iUmiHierarchyElement $page */
			foreach ($pages as $page) {
				$item = [];
				$pageId = $page->getId();
				$item['attribute:id'] = $pageId;
				$item['attribute:alt_name'] = $page->getAltName();
				$item['attribute:price'] = $page->getValue('price');
				$item['attribute:link'] = $umiLinksHelper->getLinkByParts($page);
				$item['xlink:href'] = 'upage://' . $pageId;
				$item['node:text'] = $page->getName();
				$items[] = catalog::parseTemplate($itemTemplate, $item, $pageId);
				catalog::pushEditable('catalog', 'object', $pageId);
				$umiHierarchy->unloadElement($pageId);
			}

			$result['subnodes:lines'] = $items;
			$result['numpages'] = umiPagenum::generateNumPage($total, $limit);
			$result['total'] = $total;
			$result['per_page'] = $limit;
			$result['category_id'] = $categoryId;

			return catalog::parseTemplate($itemsTemplate, $result, $categoryId);
		}

		/**
		 * Возвращает ответ при пустом результате работы метода getSmartCatalog()
		 * @param string $emptyItemsTemplate шаблон отображения (только для tpl)
		 * @param int $categoryId ид раздела каталога
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function makeEmptyCatalogResponse($emptyItemsTemplate, $categoryId) {
			$item = [];
			$item['numpages'] = umiPagenum::generateNumPage(0, 0);
			$item['lines'] = '';
			$item['total'] = 0;
			$item['per_page'] = 0;
			$item['category_id'] = $categoryId;
			return catalog::parseTemplate($emptyItemsTemplate, $item, $categoryId);
		}
	}

