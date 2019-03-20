<?php

	use UmiCms\Service;

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class DataMacros {

		/** @var data $module */
		public $module;

		/**
		 * Возвращает список элементов справочника
		 * @param string $template имя шаблона (для tpl)
		 * @param bool $guideId идентификатор справочника
		 * @param int $perPage количество элементов на страницу, в рамках пагинации
		 * @param int $currPage текущий номер страницы в рамках пагинации
		 * @return mixed
		 */
		public function getGuideItems($template = 'default', $guideId = false, $perPage = 100, $currPage = 0) {
			if (!$currPage) {
				$currPage = (int) getRequest('p');
			}
			if (!$guideId) {
				$guideId = (int) getRequest('param0');
			}

			if (!$template) {
				$template = 'default';
			}
			list($template_block, $template_block_empty, $template_line) = data::loadTemplates(
				'data/' . $template,
				'guide_block',
				'guide_block_empty',
				'guide_block_line'
			);

			$sel = new selector('objects');
			$sel->types('object-type')->id($guideId);
			$sel->limit($perPage * $currPage, $perPage);

			selectorHelper::detectFilters($sel);

			$block_arr = [];
			$lines = [];

			/** @var iUmiObject $element */
			foreach ($sel->result() as $element) {
				$line_arr = [];
				$line_arr['attribute:id'] = $element->getId();
				$line_arr['xlink:href'] = 'uobject://' . $element->getId();
				$line_arr['node:text'] = $element->getName();
				$lines[] = data::parseTemplate($template_line, $line_arr);
			}

			if (umiCount($lines) == 0) {
				return data::parseTemplate($template_block_empty, []);
			}

			$block_arr['attribute:guide_id'] = $guideId;
			$block_arr['subnodes:items'] = $lines;
			$block_arr['total'] = $sel->total;

			return data::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Генерирует фид в формате RSS на основе данных списка дочерних страниц и выводит в буффер
		 * @param null|int $elementId идентификатор родительской страницы
		 * @param null|int $typeId идентификатор объектного типа данных дочерних страниц
		 * @return mixed|string
		 * @throws publicException
		 */
		public function rss($elementId = null, $typeId = null) {
			/** @var DataMacros|DataFeeds $this */
			if (!$elementId) {
				$elementId = (int) getRequest('param0');
			}
			if (!$typeId) {
				$typeId = getRequest('param1');
			}

			if (defined('VIA_HTTP_SCHEME')) {
				throw new publicException('Not available via scheme');
			}

			$xslPath = 'xsl/rss.xsl';
			/** @var DataMacros|DataFeeds $module */
			$module = $this->module;
			return $module->generateFeed($elementId, $xslPath, $typeId);
		}

		/**
		 * Генерирует фид в формате ATOM на основе данных списка дочерних страниц и выводит в буффер
		 * @param null|int $elementId идентификатор родительской страницы
		 * @param null|int $typeId идентификатор объектного типа данных дочерних страниц
		 * @return mixed|string
		 * @throws publicException
		 */
		public function atom($elementId = null, $typeId = null) {
			if (!$elementId) {
				$elementId = (int) getRequest('param0');
			}
			if (!$typeId) {
				$typeId = getRequest('param1');
			}

			if (defined('VIA_HTTP_SCHEME')) {
				throw new publicException('Not available via scheme');
			}

			$xslPath = 'xsl/atom.xsl';
			/** @var DataMacros|DataFeeds $module */
			$module = $this->module;
			return $module->generateFeed($elementId, $xslPath, $typeId);
		}

		/**
		 * Выводит meta тег с ссылкой на RSS фид по идентификатору родитеской страницы.
		 * Фид строится из данных страниц, дочерних родитеской.
		 * @param bool $elementId идентификатор родительской страницы
		 * @param string $titlePrefix префикс для названия RSS фида
		 * @return string
		 * @throws coreException
		 */
		public function getRssMeta($elementId = false, $titlePrefix = '') {
			/** @var data|DataMacros|DataFeeds $module */
			$module = $this->module;

			$elementId = $module->analyzeRequiredPath($elementId);

			if ($elementId === false) {
				return '';
			}

			$pageCollection = umiHierarchy::getInstance();
			$typeId = $pageCollection->getDominantTypeId($elementId);
			$type = umiObjectTypesCollection::getInstance()->getType($typeId);
			if ($type instanceof iUmiObjectType) {
				$mod = $type->getModule();
				$method = $type->getMethod();
				if (!$module->checkIfFeedable($mod, $method)) {
					return '';
				}
			} else {
				return '';
			}

			$element = $pageCollection->getElement($elementId);

			if (!$element instanceof iUmiHierarchyElement) {
				return '';
			}

			$element_title = $titlePrefix . $element->getName();

			return "<link rel=\"alternate\" type=\"application/rss+xml\" href=\"/data/rss/{$elementId}/\" title=\"{$element_title}\" />";
		}

		/**
		 * Выводит meta тег с ссылкой на RSS фид по адресу родитеской страницы.
		 * Фид строится из данных страниц, дочерних родитеской.
		 * @param bool $path адрес родительской страницы
		 * @param string $titlePrefix префикс для названия RSS фида
		 * @return string
		 * @throws coreException
		 */
		public function getRssMetaByPath($path, $titlePrefix = '') {
			$element_id = umiHierarchy::getInstance()->getIdByPath($path);
			if ($element_id) {
				return $this->getRssMeta($element_id, $titlePrefix);
			}

			return '';
		}

		/**
		 * Выводит meta тег с ссылкой на ATOM фид по идентификатору родитеской страницы.
		 * Фид строится из данных страниц, дочерних родитеской.
		 * @param bool $elementId идентификатор родительской страницы
		 * @param string $titlePrefix префикс для названия ATOM фида
		 * @return string
		 * @throws coreException
		 */
		public function getAtomMeta($elementId = false, $titlePrefix = '') {
			/** @var DataMacros|DataFeeds $module */
			$module = $this->module;

			$elementId = $module->analyzeRequiredPath($elementId);

			if ($elementId === false) {
				return '';
			}

			$pageCollection = umiHierarchy::getInstance();
			$typeId = $pageCollection->getDominantTypeId($elementId);
			$type = umiObjectTypesCollection::getInstance()->getType($typeId);

			if ($type instanceof iUmiObjectType) {
				$mod = $type->getModule();
				$method = $type->getMethod();
				if (!$module->checkIfFeedable($mod, $method)) {
					return '';
				}
			} else {
				return '';
			}

			$element = $pageCollection->getElement($elementId);

			if (!$element instanceof iUmiHierarchyElement) {
				return '';
			}

			$element_title = $titlePrefix . $element->getName();

			return "<link rel=\"alternate\" type=\"application/rss+xml\" href=\"/data/atom/{$elementId}/\" title=\"{$element_title}\" />";
		}

		/**
		 * Выводит meta тег с ссылкой на ATOM фид по адресу родитеской страницы.
		 * Фид строится из данных страниц, дочерних родитеской.
		 * @param bool $path адрес родительской страницы
		 * @param string $titlePrefix префикс для названия ATOM фида
		 * @return string
		 * @throws coreException
		 */
		public function getAtomMetaByPath($path, $titlePrefix = '') {
			$element_id = umiHierarchy::getInstance()->getIdByPath($path);
			if ($element_id) {
				return $this->getAtomMeta($element_id, $titlePrefix);
			}

			return '';
		}

		/**
		 * Возвращает значения поля по странице (только для tpl шаблонизатора)
		 * @param int|string $elementId идентификатор или адрес страницы
		 * @param int|string $propId идентификатор или guid поля
		 * @param string $template имя шаблона
		 * @param bool $isRandom выводить значения поля в случайном порядке
		 * @return mixed
		 */
		public function getProperty($elementId, $propId, $template = 'default', $isRandom = false) {
			if (!$template) {
				$template = 'default';
			}
			$this->module->templatesMode('tpl');

			if (!is_numeric($elementId)) {
				$elementId = umiHierarchy::getInstance()->getIdByPath($elementId);
			}

			$element = umiHierarchy::getInstance()->getElement($elementId);

			if ($element) {
				$prop = is_numeric($propId)
					? $element->getObject()->getPropById($propId)
					: $element->getObject()->getPropByName($propId);

				if ($prop) {
					return data::parseTemplate($this->renderProperty($prop, $template, $isRandom), [], $elementId);
				}

				list($template_not_exists) = data::loadTemplates('data/' . $template, 'prop_unknown');
				return $template_not_exists;
			}

			list($template_not_exists) = data::loadTemplates('data/' . $template, 'prop_unknown');
			return $template_not_exists;
		}

		/**
		 * Возвращает значения полей группы по странице (только для tpl шаблонизатора)
		 * @param int|string $elementId идентификатор или адрес страницы
		 * @param int|string $groupId идентификатор или guid группы
		 * (можно передать несколько значений, разделенных пробелом)
		 * @param string $template имя шаблона
		 * @return mixed|string
		 * @throws coreException
		 */
		public function getPropertyGroup($elementId, $groupId, $template = 'default') {
			if (!$template) {
				$template = 'default';
			}
			$this->module->templatesMode('tpl');

			if (!is_numeric($elementId)) {
				$elementId = umiHierarchy::getInstance()->getIdByPath($elementId);
			}

			if (strstr($groupId, ' ') !== false) {
				$group_ids = explode(' ', $groupId);
				$res = '';
				foreach ($group_ids as $groupId) {
					if (!($groupId = trim($groupId))) {
						continue;
					}
					$res .= $this->getPropertyGroup($elementId, $groupId, $template);
				}
				return $res;
			}

			$element = umiHierarchy::getInstance()->getElement($elementId);

			if ($element) {
				if (!is_numeric($groupId)) {
					$groupId = $element->getObject()->getPropGroupId($groupId);
				}

				$type_id = $element->getObject()->getTypeId();
				$group = umiObjectTypesCollection::getInstance()
					->getType($type_id)
					->getFieldsGroup($groupId);

				if ($group) {
					if (!$group->getIsActive()) {
						return '';
					}
					list($template_block, $template_line) = data::loadTemplates('data/' . $template, 'group', 'group_line');

					$lines = [];
					$props = $element->getObject()->getPropGroupById($groupId);
					$sz = umiCount($props);
					for ($i = 0; $i < $sz; $i++) {
						$prop_id = $props[$i];
						$prop = $element->getObject()->getPropById($prop_id);

						if ($prop) {
							if ($prop->getIsVisible() === false) {
								continue;
							}
						}

						$line_arr = [];
						$line_arr['id'] = $elementId;
						$line_arr['prop_id'] = $prop_id;

						$prop_val = $this->getProperty($elementId, $prop_id, $template);

						if ($prop_val) {
							$line_arr['prop'] = $prop_val;
						} else {
							continue;
						}

						$lines[] = data::parseTemplate($template_line, $line_arr);
					}
					if (!umiCount($lines)) {
						return '';
					}

					$block_arr = [];
					$block_arr['name'] = $group->getName();
					$block_arr['title'] = $group->getTitle();
					$block_arr['+lines'] = $lines;
					$block_arr['template'] = $template;

					return data::parseTemplate($template_block, $block_arr);
				}

				return '';
			}

			return '';
		}

		/**
		 * Возвращает значения полей всех групп по странице (только для tpl шаблонизатора)
		 * @param int|string $elementId идентификатор или адрес страницы
		 * @param string $template имя шаблона
		 * @return mixed|string
		 * @throws coreException
		 */
		public function getAllGroups($elementId, $template = 'default') {
			if (!$template) {
				$template = 'default';
			}
			$this->module->templatesMode('tpl');

			if (!is_numeric($elementId)) {
				$elementId = umiHierarchy::getInstance()->getIdByPath($elementId);
			}

			$element = umiHierarchy::getInstance()->getElement($elementId);

			if ($element) {
				list($template_block, $template_line) =
					data::loadTemplates('data/' . $template, 'groups_block', 'groups_line');

				$block_arr = [];
				$object_type_id = $element->getObject()->getTypeId();
				$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);
				$groups = $object_type->getFieldsGroupsList();

				$lines = [];
				/** @var iUmiFieldsGroup $group */
				foreach ($groups as $group_id => $group) {
					if (!$group->getIsActive() || !$group->getIsVisible()) {
						continue;
					}

					$line_arr = [];
					$line_arr['id'] = $elementId;
					$line_arr['group_id'] = $group_id;
					$line_arr['group_name'] = $group->getName();
					$lines[] = data::parseTemplate($template_line, $line_arr);
				}

				$block_arr['+lines'] = $lines;
				$block_arr['id'] = $elementId;
				$block_arr['template'] = $template;
				return data::parseTemplate($template_block, $block_arr);
			}

			return '';
		}

		/**
		 * Возвращает значения поля по объекту (только для tpl шаблонизатора)
		 * @param int $objectId идентификатор объекта
		 * @param int|string $propId идентификатор или guid поля
		 * @param string $template имя шаблона
		 * @param bool $isRandom выводить значения поля в случайном порядке
		 * @return mixed
		 */
		public function getPropertyOfObject($objectId, $propId, $template = 'default', $isRandom = false) {
			if (!$template) {
				$template = 'default';
			}

			$this->module->templatesMode('tpl');
			$object = umiObjectsCollection::getInstance()->getObject($objectId);

			if ($object) {
				$prop = is_numeric($propId) ? $object->getPropById($propId) : $object->getPropByName($propId);

				if ($prop) {
					return data::parseTemplate($this->renderProperty($prop, $template, $isRandom), [], false, $objectId);
				}

				list($template_not_exists) = data::loadTemplates('data/' . $template, 'prop_unknown');
				return $template_not_exists;
			}

			list($template_not_exists) = data::loadTemplates('data/' . $template, 'prop_unknown');
			return $template_not_exists;
		}

		/**
		 * Возвращает значения полей группы по объекту (только для tpl шаблонизатора)
		 * @param int $objectId идентификатор объекта
		 * @param int|string $groupId идентификатор или guid группы
		 * (можно передать несколько значений, разделенных пробелом)
		 * @param string $template имя шаблона
		 * @return mixed|string
		 * @throws coreException
		 */
		public function getPropertyGroupOfObject($objectId, $groupId, $template = 'default') {
			if (!$template) {
				$template = 'default';
			}
			$this->module->templatesMode('tpl');

			if (strstr($groupId, ' ') !== false) {
				$group_ids = explode(' ', $groupId);
				$res = '';
				foreach ($group_ids as $groupId) {
					if (!($groupId = trim($groupId))) {
						continue;
					}
					$res .= $this->getPropertyGroupOfObject($objectId, $groupId, $template);
				}
				return $res;
			}

			$object = umiObjectsCollection::getInstance()->getObject($objectId);

			if ($object) {
				if (!is_numeric($groupId)) {
					$groupId = $object->getPropGroupId($groupId);
				}

				$type_id = $object->getTypeId();
				$group = umiObjectTypesCollection::getInstance()
					->getType($type_id)
					->getFieldsGroup($groupId);

				if ($group) {
					if (!$group->getIsActive()) {
						return '';
					}

					try {
						list($template_block, $template_line) =
							data::loadTemplates('data/' . $template, 'group', 'group_line');
					} catch (publicException $e) {
						return '';
					}

					$lines = [];
					$props = $object->getPropGroupById($groupId);
					$sz = umiCount($props);
					for ($i = 0; $i < $sz; $i++) {
						$propId = $props[$i];
						$prop = $object->getPropById($propId);

						if ($prop) {
							if ($prop->getIsVisible() === false) {
								continue;
							}
						}

						$line_arr = [];
						$line_arr['id'] = $objectId;
						$line_arr['prop_id'] = $propId;

						$prop_val = $this->getPropertyOfObject($objectId, $propId, $template);

						if ($prop_val) {
							$line_arr['prop'] = $prop_val;
						} else {
							continue;
						}

						$lines[] = data::parseTemplate($template_line, $line_arr);
					}

					$block_arr = [];
					$block_arr['name'] = $group->getName();
					$block_arr['title'] = $group->getTitle();
					$block_arr['+lines'] = $lines;
					$block_arr['template'] = $template;
					return data::parseTemplate($template_block, $block_arr);
				}

				return '';
			}

			return '';
		}

		/**
		 * Возвращает значения полей всех групп по объекту (только для tpl шаблонизатора)
		 * @param int $objectId идентификатор объекта
		 * @param string $template имя шаблона
		 * @return mixed|string
		 * @throws coreException
		 */
		public function getAllGroupsOfObject($objectId, $template = 'default') {
			if (!$template) {
				$template = 'default';
			}
			$this->module->templatesMode('tpl');
			$object = umiObjectsCollection::getInstance()->getObject($objectId);

			if ($object) {
				list($template_block, $template_line) =
					data::loadTemplates('data/' . $template, 'groups_block', 'groups_line');

				$block_arr = [];

				$object_type_id = $object->getTypeId();
				$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);
				$groups = $object_type->getFieldsGroupsList();

				$lines = [];
				/** @var iUmiFieldsGroup $group */
				foreach ($groups as $group_id => $group) {
					if (!$group->getIsActive() || !$group->getIsVisible()) {
						continue;
					}

					$line_arr = [];
					$line_arr['group_id'] = $group_id;
					$line_arr['group_name'] = $group->getName();
					$lines[] = data::parseTemplate($template_line, $line_arr);
				}

				$block_arr['+lines'] = $lines;
				$block_arr['id'] = $objectId;
				$block_arr['template'] = $template;
				return data::parseTemplate($template_block, $block_arr);
			}

			return '';
		}

		/**
		 * Выполняет выборку по протоколу usel (только для tpl шаблонизатор)
		 * @param string $template имя шаблона для результатов работы
		 * @param string $uselName имя шаблона для usel
		 * @return array|mixed
		 * @throws publicException
		 */
		public function doSelection($template = 'default', $uselName) {
			$this->module->templatesMode('tpl');
			$params = func_get_args();
			$params = array_slice($params, 2, umiCount($params) - 2);
			$stream = new uselStream;
			$result = $stream->call($uselName, $params);
			$oldResultMode = data::isXSLTResultMode(false);

			list(
				$objects_block,
				$objects_line,
				$objects_empty,
				$elements_block,
				$elements_line,
				$elements_empty,
				$separator,
				$separator_last
				) = data::loadTemplates(
				'data/usel/' . $template,
				'objects_block',
				'objects_block_line',
				'objects_block_empty',
				'elements_block',
				'elements_block_line',
				'elements_block_empty',
				'separator',
				'separator_last'
			);

			switch ($result['mode']) {
				case 'objects':
					$tpl_block = $objects_block;
					$tpl_line = $objects_line;
					$tpl_empty = $objects_empty;
					break;

				case 'pages':
					$tpl_block = $elements_block;
					$tpl_line = $elements_line;
					$tpl_empty = $elements_empty;
					break;

				default: {
					throw new publicException("Unsupported return mode \"{$result['mode']}\"");
				}
			}

			if ($result['sel'] instanceof selector) {
				$sel = $result['sel'];
				$results = $sel->result();
				$total = $sel->length();
				$limit = $sel->limit;

				if ($total == 0) {
					$tpl_block = $tpl_empty;
				}

				$hierarchy = umiHierarchy::getInstance();

				$block_arr = [];
				$lines = [];
				$objectId = false;
				$elementId = false;
				$sz = umiCount($results);
				$c = 0;

				foreach ($results as $item) {
					$line_arr = [];

					if ($result['mode'] == 'objects') {
						$object = $item;
						/** @var iUmiObject $object */
						if ($object instanceof iUmiObject) {
							$objectId = $object->getId();
							$line_arr['attribute:id'] = $object->getId();
							$line_arr['attribute:name'] = $object->getName();
							$line_arr['attribute:type-id'] = $object->getTypeId();
							$line_arr['xlink:href'] = 'uobject://' . $objectId;
						} else {
							continue;
						}
					} else {
						$element = $item;
						/** @var iUmiHierarchyElement $element */
						if ($element instanceof iUmiHierarchyElement) {
							$elementId = $element->getId();
							$line_arr['attribute:id'] = $element->getId();
							$line_arr['attribute:name'] = $element->getName();
							$line_arr['attribute:link'] = $hierarchy->getPathById($element->getId());
							$line_arr['xlink:href'] = 'upage://' . $element->getId();
						} else {
							continue;
						}
					}
					$line_arr['void:separator'] = (($sz == ($c + 1)) && $separator_last) ? $separator_last : $separator;
					$lines[] = data::parseTemplate($tpl_line, $line_arr, $elementId, $objectId);
					++$c;
				}
				$block_arr['subnodes:items'] = $lines;
				$block_arr['total'] = $total;
				$block_arr['per_page'] = $limit;
				$result = data::parseTemplate($tpl_block, $block_arr);
				data::isXSLTResultMode($oldResultMode);
				return $result;
			}

			throw new publicException("Can't execute selection");
		}

		/**
		 * В зависимости от типа поля передает управления
		 * методу для получения данных поля и возвращает результат его работы
		 * @param iUmiObjectProperty $property поле
		 * @param mixed $template блок tpl шаблона
		 * @param bool $isRandom выводить значения поля в случайном порядке
		 * @return string
		 */
		private function renderProperty(iUmiObjectProperty $property, $template, $isRandom = false) {
			switch ($property->getDataType()) {
				case 'string': {
					return $this->renderString($property, $template);
				}
				case 'text': {
					return $this->renderString($property, $template, false, 'text');
				}
				case 'wysiwyg': {
					return $this->renderString($property, $template, false, 'wysiwyg');
				}
				case 'int': {
					return $this->renderInt($property, $template);
				}
				case 'price': {
					return $this->renderPrice($property, $template);
				}
				case 'float': {
					return $this->renderFloat($property, $template);
				}
				case 'boolean': {
					return $this->renderBoolean($property, $template);
				}
				case 'img_file': {
					return $this->renderImageFile($property, $template);
				}
				case 'multiple_image': {
					return $this->renderMultipleImageFiles($property, $template);
				}
				case 'relation': {
					return $this->renderRelation($property, $template, false, $isRandom);
				}
				case 'symlink': {
					return $this->renderSymlink($property, $template, false, $isRandom);
				}
				case 'swf_file': {
					return $this->renderFile($property, $template, false, 'swf_file');
				}
				case 'file': {
					return $this->renderFile($property, $template);
				}
				case 'date': {
					return $this->renderDate($property, $template);
				}
				case 'tags': {
					return $this->renderTags($property, $template);
				}
				case 'optioned': {
					return $this->renderOptioned($property, $template);
				}
				case 'offer_id_list': {
					return $this->renderOfferIdList($property, $template);
				}
				default: {
					return "I don't know, how to render this sort of property (\"{$property->getDataType()}\") :(";
				}
			}
		}

		/**
		 * Загружает и применяет шаблон для поля типов "Строка", "Простой текст" и "HTML текст"
		 * @param iUmiObjectProperty $property поле
		 * @param string $template имя шаблона
		 * @param bool $showNull показывать пустые значения
		 * @param string $templateBlock выбранный блок шаблона
		 * @return mixed
		 */
		private function renderString(
			iUmiObjectProperty $property,
			$template,
			$showNull = false,
			$templateBlock = 'string'
		) {
			$name = $property->getName();
			$title = $property->getTitle();
			$value = $property->getValue();

			list($tpl, $tpl_empty) = data::loadTemplates('data/' . $template, "{$templateBlock}", "{$templateBlock}_empty");

			if (!$tpl) {
				list($tpl, $tpl_empty) = data::loadTemplates('data/' . $template, 'string', 'string_empty');
			}

			if ((is_array($value) || $value === '' || $value === null) && !$showNull) {
				return $tpl_empty;
			}

			return data::parseTemplate($tpl, [
				'field_id' => $property->getField()->getId(),
				'name' => $name,
				'title' => $title,
				'value' => $value,
				'template' => $template
			]);
		}

		/**
		 * Загружает и применяет шаблон для поля типа "Число"
		 * @param iUmiObjectProperty $property поле
		 * @param string $template имя шаблона
		 * @param bool $showNull показывать пустые значения
		 * @return mixed
		 */
		private function renderInt(iUmiObjectProperty $property, $template, $showNull = false) {
			$name = $property->getName();
			$title = $property->getTitle();
			$value = $property->getValue();

			list($tpl, $tpl_empty) = data::loadTemplates('data/' . $template, 'int', 'int_empty');

			if (($value === null || $value === false || $value === '') && !$showNull) {
				return $tpl_empty;
			}

			return data::parseTemplate($tpl, [
				'field_id' => $property->getField()->getId(),
				'name' => $name,
				'title' => $title,
				'value' => $value
			]);
		}

		/**
		 * Загружает и применяет шаблон для поля типа "Цена"
		 * @param iUmiObjectProperty $property поле
		 * @param string $template имя шаблона
		 * @param bool $showNull показывать пустые значения
		 * @return mixed
		 */
		private function renderPrice(iUmiObjectProperty $property, $template, $showNull = false) {
			$name = $property->getName();
			$title = $property->getTitle();
			$value = $property->getValue();

			list ($tpl, $tpl_empty) = data::loadTemplates('data/' . $template, 'price', 'price_empty');
			if (empty($value) && !$showNull) {
				return $tpl_empty;
			}

			$arrayBlock = [
				'field_id' => $property->getField()->getId(),
				'name' => $name,
				'title' => $title,
				'currency_symbol' => '',
				'template' => $template
			];
			$currency = Service::Session()->get('eshop_currency');

			if ($currency) {
				$exchangeRate = $currency['exchange'];

				if ($exchangeRate) {
					$value = $value / $exchangeRate;
					$arrayBlock['currency_symbol'] = $currency['symbol'];
				}
			}

			$arrayBlock['value'] = number_format($value, (($value - floor($value)) > 0.005) ? 2 : 0, '.', ' ');
			return data::parseTemplate($tpl, $arrayBlock);
		}

		/**
		 * Загружает и применяет шаблон для поля типа "Число с точкой"
		 * @param iUmiObjectProperty $property поле
		 * @param string $template имя шаблона
		 * @param bool $showNull показывать пустые значения
		 * @return mixed
		 */
		private function renderFloat(iUmiObjectProperty $property, $template, $showNull = false) {
			$name = $property->getName();
			$title = $property->getTitle();
			$value = $property->getValue();

			list($tpl, $tpl_empty) = data::loadTemplates('data/' . $template, 'float', 'float_empty');
			if (empty($value) && !$showNull) {
				return $tpl_empty;
			}

			return data::parseTemplate($tpl, [
				'field_id' => $property->getField()->getId(),
				'name' => $name,
				'title' => $title,
				'value' => $value,
				'template' => $template
			]);
		}

		/**
		 * Загружает и применяет шаблон для поля типа "Кнопка-флажок"
		 * @param iUmiObjectProperty $property поле
		 * @param string $template имя шаблона
		 * @param bool $showNull показывать пустые значения
		 * @return mixed
		 */
		private function renderBoolean(iUmiObjectProperty $property, $template, $showNull = false) {
			$name = $property->getName();
			$title = $property->getTitle();
			$value = $property->getValue();

			$arrBlock = [
				'name' => $name,
				'title' => $title,
				'template' => $template
			];

			list($tpl_yes, $tpl_no) = data::loadTemplates('data/' . $template, 'boolean_yes', 'boolean_no');
			if (empty($value) && !$showNull) {
				return data::parseTemplate($tpl_no, $arrBlock);
			}

			$tpl = $value ? $tpl_yes : $tpl_no;
			return data::parseTemplate($tpl, $arrBlock);
		}

		/**
		 * Загружает и применяет шаблон для поля типа "Изображение"
		 * @param iUmiObjectProperty $property поле
		 * @param string $template имя шаблона
		 * @param bool $showNull показывать пустые значения
		 * @return mixed
		 */
		private function renderImageFile(iUmiObjectProperty $property, $template, $showNull = false) {
			$name = $property->getName();
			$title = $property->getTitle();
			$value = $property->getValue();

			list($tpl, $tpl_empty) = data::loadTemplates('data/' . $template, 'img_file', 'img_file_empty');

			if (empty($value) && !$showNull) {
				return $tpl_empty;
			}

			$arr = [
				'field_id' => $property->getField()->getId(),
				'name' => $name,
				'title' => $title,
				'size' => $value->getSize(),
				'filename' => $value->getFileName(),
				'filepath' => $value->getFilePath(),
				'src' => $value->getFilePath(true),
				'ext' => $value->getExt(),
				'template' => $template
			];

			if (mb_strtolower($value->getExt()) == 'swf') {
				list($tpl) = data::loadTemplates('data/' . $template, 'swf_file');
			}

			if ($value instanceof iUmiImageFile) {
				$arr['width'] = $value->getWidth();
				$arr['height'] = $value->getHeight();
				$arr['img_title'] = $value->getTitle();
				$arr['img_alt'] = $value->getAlt();
			}

			return data::parseTemplate($tpl, $arr);
		}

		/**
		 * Загружает и применяет шаблон для поля типа "Набор изображений"
		 * Блоки TPL-шаблона:
		 * multiple_images - основной блок полей типа "Набор изображений"
		 * multiple_images_empty - если в поле не содержатся данные
		 * multiple_images_item - блок для каждого отдельного изображения
		 *
		 * @param iUmiObjectProperty $property обрабатываемое свойство
		 * @param string $template имя шаблона
		 * @return mixed
		 */
		private function renderMultipleImageFiles(iUmiObjectProperty $property, $template) {
			list($baseBlock, $emptyBlock, $imageBlock) = data::loadTemplates(
				'data/' . $template,
				'multiple_images',
				'multiple_images_empty',
				'multiple_images_item'
			);

			$value = $property->getValue();

			if (empty($value)) {
				return $emptyBlock;
			}

			$imageInfo = [];
			$imagesList = [];

			/** @var umiImageFile $image */
			foreach ($value as $image) {
				$imageInfo['img_title'] = $image->getTitle();
				$imageInfo['img_alt'] = $image->getAlt();
				$imageInfo['size'] = $image->getSize();
				$imageInfo['filename'] = $image->getFileName();
				$imageInfo['filepath'] = $image->getFilePath();
				$imageInfo['src'] = $image->getFilePath(true);
				$imageInfo['ext'] = $image->getExt();
				$imageInfo['alt'] = $image->getAlt();
				$imageInfo['title'] = $image->getTitle();
				$imagesList[] = data::parseTemplate($imageBlock, $imageInfo);
			}

			$propertyInfo = [
				'field_id' => $property->getField()->getId(),
				'name' => $property->getName(),
				'title' => $property->getTitle(),
				'template' => $template,
				'items' => $imagesList
			];

			return data::parseTemplate($baseBlock, $propertyInfo);
		}

		/**
		 * Загружает и применяет шаблон для поля типов "Выпадающий список"
		 * и "Выпадающий список со множественным выбором"
		 * @param iUmiObjectProperty $property поле
		 * @param string $template имя шаблона
		 * @param bool $showNull показывать пустые значения
		 * @param bool $isRandom выводит значения поля в случайном порядке
		 * @return mixed
		 */
		private function renderRelation(iUmiObjectProperty $property, $template, $showNull = false, $isRandom = false) {
			$name = $property->getName();
			$title = $property->getTitle();
			$value = $property->getValue();
			$umiObjectsCollection = umiObjectsCollection::getInstance();

			if ($property->getIsMultiple() === false) {
				list($tpl, $tpl_empty) = data::loadTemplates('data/' . $template, 'relation', 'relation_empty');

				$arrayBlock = [
					'field_id' => $property->getField()->getId(),
					'name' => $name,
					'title' => $title,
					'object_id' => $value,
					'template' => $template
				];

				if (empty($value) && !$showNull) {
					return data::parseTemplate($tpl_empty, $arrayBlock, false, $value);
				}

				$valueObject = $umiObjectsCollection->getObject($value);
				if ($valueObject instanceof iUmiObject) {
					$arrayBlock['value'] = $valueObject->getName();
					$umiObjectsCollection->unloadObject($value);
				}
				return data::parseTemplate($tpl, $arrayBlock);
			}

			list($tpl_block, $tpl_block_empty, $tpl_item, $tpl_quant) = data::loadTemplates(
				'data/' . $template,
				'relation_mul_block',
				'relation_mul_block_empty',
				'relation_mul_item',
				'relation_mul_quant'
			);

			if (empty($value) && !$showNull) {
				return $tpl_block_empty;
			}

			if ($isRandom) {
				$value = $value[mt_rand(0, umiCount($value) - 1)];
				$value = [$value];
			}

			$items = [];
			$sz = umiCount($value);

			for ($i = 0; $i < $sz; $i++) {
				$valueObject = $umiObjectsCollection->getObject($value[$i]);
				$valueName = null;

				if ($valueObject instanceof iUmiObject) {
					$valueName = $valueObject->getName();
					$umiObjectsCollection->unloadObject($value[$i]);
				}

				$arrayItem = [
					'object_id' => $value[$i],
					'value' => $valueName
				];
				$arrayItem['quant'] = ($sz != ($i + 1)) ? $tpl_quant : '';

				$items[] = data::parseTemplate($tpl_item, $arrayItem, false, $value[$i]);
			}

			$arrayBlock = [
				'name' => $name,
				'title' => $title,
				'+items' => $items,
				'template' => $template
			];

			return data::parseTemplate($tpl_block, $arrayBlock);
		}

		/**
		 * Загружает и применяет шаблон для поля типа "Ссылка на дерево"
		 * @param iUmiObjectProperty $property поле
		 * @param string $template имя шаблона
		 * @param bool $showNull показывать пустые значения
		 * @param bool $isRandom выводит значения поля в случайном порядке
		 * @return mixed
		 */
		private function renderSymlink(iUmiObjectProperty $property, $template, $showNull = false, $isRandom = false) {
			$name = $property->getName();
			$title = $property->getTitle();
			$value = $property->getValue();

			list($tpl_block, $tpl_empty, $tpl_item, $tpl_quant) = data::loadTemplates(
				'data/' . $template,
				'symlink_block',
				'symlink_block_empty',
				'symlink_item',
				'symlink_quant'
			);

			if (empty($value) && !$showNull) {
				return $tpl_empty;
			}

			if ((bool) $isRandom) {
				$value = $value[mt_rand(0, umiCount($value) - 1)];
				$value = [$value];
			}

			$items = [];
			$sz = umiCount($value);

			for ($i = 0; $i < $sz; $i++) {
				/** @var iUmiHierarchyElement $element */
				$element = $value[$i];
				$elementId = $element->getId();

				$arrayItem = [
					'id' => $elementId,
					'object_id' => $element->getObject()->getId(),
					'value' => $element->getName(),
					'link' => umiHierarchy::getInstance()->getPathById($elementId)
				];
				$arrayItem['quant'] = ($sz != ($i + 1)) ? $tpl_quant : '';

				$items[] = data::parseTemplate($tpl_item, $arrayItem, $elementId);
			}

			return data::parseTemplate($tpl_block, [
				'name' => $name,
				'title' => $title,
				'+items' => $items,
				'template' => $template
			]);
		}

		/**
		 * Загружает и применяет шаблон для поля типов "Файл" и "Flash"
		 * @param iUmiObjectProperty $property поле
		 * @param string $template имя шаблона
		 * @param bool $showNull показывать пустые значения
		 * @param string $templateBlock выбранный блок шаблона
		 * @return mixed
		 */
		private function renderFile(iUmiObjectProperty $property, $template, $showNull = false, $templateBlock = 'file') {
			$name = $property->getName();
			$title = $property->getTitle();
			$value = $property->getValue();

			list($tpl, $tpl_empty) = data::loadTemplates('data/' . $template, "{$templateBlock}", "{$templateBlock}_empty");

			if (!$tpl) {
				list($tpl, $tpl_empty) = data::loadTemplates('data/' . $template, 'file', 'file_empty');
			}

			if (empty($value) && !$showNull) {
				return $tpl_empty;
			}

			$arrayBlock = [
				'field_id' => $property->getField()->getId(),
				'name' => $name,
				'title' => $title,
				'size' => $value->getSize(),
				'filename' => $value->getFileName(),
				'filepath' => $value->getFilePath(),
				'src' => $value->getFilePath(true),
				'ext' => $value->getExt(),
				'modifytime' => $value->getModifyTime(),
				'template' => $template
			];

			if ($value instanceof umiImageFile) {
				$arrayBlock['width'] = $value->getWidth();
				$arrayBlock['height'] = $value->getHeight();
			}

			return data::parseTemplate($tpl, $arrayBlock);
		}

		/**
		 * Загружает и применяет шаблон для поля типа "Дата"
		 * @param iUmiObjectProperty $property поле
		 * @param string $template имя шаблона
		 * @param bool $showNull показывать пустые значения
		 * @return mixed
		 */
		private function renderDate(iUmiObjectProperty $property, $template, $showNull = false) {
			$name = $property->getName();
			$title = $property->getTitle();
			$value = $property->getValue();

			list($tpl, $tpl_empty) = data::loadTemplates('data/' . $template, 'date', 'date_empty');

			if (empty($value) && !$showNull) {
				return $tpl_empty;
			}

			return data::parseTemplate($tpl, [
				'field_id' => $property->getField()->getId(),
				'name' => $name,
				'title' => $title,
				'timestamp' => $value->getFormattedDate('U'),
				'value' => $value->getFormattedDate(),
				'template' => $template
			]);
		}

		/**
		 * Загружает и применяет шаблон для поля типа "Теги"
		 * @param iUmiObjectProperty $property поле
		 * @param string $template имя шаблона
		 * @return mixed
		 */
		private function renderTags($property, $template) {
			$values = $property->getValue();
			list($tpl_block, $tpl_block_item, $tpl_block_empty) = data::loadTemplates(
				'data/' . $template,
				'tags_block',
				'tags_item',
				'tags_empty'
			);

			$itemsArray = [];
			foreach ($values as $key => $value) {
				$itemsArray[] = data::parseTemplate($tpl_block_item, [
					'tag' => $value,
					'name' => $value
				]);
			}

			if (umiCount($itemsArray) < 1) {
				return $tpl_block_empty;
			}

			return data::parseTemplate($tpl_block, [
				'+items' => $itemsArray,
				'template' => $template
			]);
		}

		/**
		 * Загружает и применяет шаблон для поля типа "Составное"
		 * @param iUmiObjectProperty $property поле
		 * @param string $template имя шаблона
		 * @param bool $showNull показывать пустые значения
		 * @return mixed
		 */
		private function renderOptioned(iUmiObjectProperty $property, $template, $showNull = false) {
			$name = $property->getName();
			$title = $property->getTitle();
			$value = $property->getValue();

			list($tpl_block, $tpl_block_empty, $tpl_item) = data::loadTemplates(
				'data/' . $template,
				'optioned_block',
				'optioned_block_empty',
				'optioned_item'
			);

			if (empty($value) && !$showNull) {
				return $tpl_block_empty;
			}

			$itemsArray = [];
			foreach ($value as $info) {
				$objectId = getArrayKey($info, 'rel');
				$elementId = getArrayKey($info, 'symlink');

				$itemArray = [
					'int' => getArrayKey($info, 'int'),
					'float' => getArrayKey($info, 'float'),
					'text' => getArrayKey($info, 'text'),
					'varchar' => getArrayKey($info, 'varchar'),
					'field_name' => $name
				];

				if ($objectId) {
					$object = selector::get('object')->id($objectId);

					if ($object) {
						$itemArray['object-id'] = $object->getId();
						$itemArray['object-name'] = $object->getName();
					}
				}

				if ($elementId) {
					$element = selector::get('element')->id($elementId);

					if ($element) {
						$itemArray['element-id'] = $element->getId();
						$itemArray['element-name'] = $element->getName();
						$itemArray['element-link'] = $element->link;
					}
				}

				$itemsArray[] = data::parseTemplate($tpl_item, $itemArray, false, $objectId);
			}

			return data::parseTemplate($tpl_block, [
				'field_id' => $property->getField()->getId(),
				'field_name' => $name,
				'name' => $name,
				'title' => $title,
				'subnodes:items' => $itemsArray,
				'template' => $template
			]);
		}

		/**
		 * Загружает и применяет шаблон для поля типа "Ссылка на список торговых предложений"
		 * @param iUmiObjectProperty $property поле
		 * @param string $template имя шаблона
		 * @return mixed
		 * @throws ErrorException
		 * @throws ReflectionException
		 * @throws coreException
		 * @throws databaseException
		 * @throws privateException
		 * @throws selectorException
		 * @throws wrongParamException
		 */
		public function renderOfferIdList(iUmiObjectProperty $property, $template) {
			$offerIdList = (array) $property->getValue();

			list(
				$offerCollectionBlock,
				$offerCollectionEmptyBlock,
				$offerBlock,
				$offerPriceCollectionBlock,
				$offerPriceCollectionEmptyBlock,
				$offerPriceBlock,
				$offerStockBalanceCollectionBlock,
				$offerStockBalanceCollectionEmptyBlock,
				$offerStockBalanceBlock,
				$offerCharacteristicCollectionBlock,
				$offerCharacteristicCollectionEmptyBlock
				) = data::loadTemplates(
				'data/' . $template,
				'trade_offer_collection_block',
				'trade_offer_collection_block_empty',
				'trade_offer_block',
				'trade_offer_price_collection_block',
				'trade_offer_price_collection_block_empty',
				'trade_offer_price',
				'trade_offer_stock_balance_collection',
				'trade_offer_stock_balance_collection_empty',
				'trade_offer_stock_balance',
				'trade_offer_characteristic_collection',
				'trade_offer_characteristic_collection_empty'
			);

			if (count($offerIdList) === 0) {
				return data::parseTemplate($offerCollectionEmptyBlock, []);
			}

			$tradeOfferFacade = Service::TradeOfferFacade();
			$offerList = $tradeOfferFacade->getList($offerIdList);
			$tradeOfferDataList = [];

			/** @var \UmiCms\System\Trade\iOffer $offer */
			foreach ($tradeOfferFacade->mapCollectionWithRelations($offerList) as $offer) {
				$tradeOfferData = [
					'attribute:id' => $offer->getId(),
					'attribute:object-id' => $offer->getDataObjectId(),
					'attribute:type-id' => $offer->getTypeId(),
					'attribute:name' => $offer->getName(),
					'attribute:vendor-code' => $offer->getVendorCode()
				];

				$priceDataList = [];

				/** @var \UmiCms\System\Trade\Offer\iPrice $price */
				foreach ($offer->getPriceCollection() as $price) {
					$priceDataList[] = emarket::parseTemplate($offerPriceBlock, [
						'attribute:id' => $price->getId(),
						'attribute:value' => $price->getValue(),
						'attribute:formatted-value' => sprintf('%.2f %s', $price->getValue(), $price->getCurrency()->getSuffix()),
						'attribute:is-main' => $price->isMain(),
						'attribute:type-id' => $price->getTypeId(),
						'attribute:type-title' => $price->getType()->getTitle()
					]);
				}

				if (count($priceDataList) > 0) {
					$tradeOfferData['price_collection'] = data::parseTemplate($offerPriceCollectionBlock, [
						'nodes:price' => $priceDataList,
						'void:items' => $priceDataList,
					]);
				} else {
					$tradeOfferData['price_collection'] = data::parseTemplate($offerPriceCollectionEmptyBlock, []);
				}

				$stockBalanceDataList = [];

				/** @var \UmiCms\System\Trade\Stock\iBalance $balance */
				foreach ($offer->getStockBalanceCollection() as $balance) {
					$stockBalanceDataList[] = emarket::parseTemplate($offerStockBalanceBlock, [
						'attribute:id' => $balance->getId(),
						'attribute:stock-id' => $balance->getStockId(),
						'attribute:stock-name' => $balance->getStock()->getName(),
						'attribute:value' => $balance->getValue()
					]);
				}

				if (count($stockBalanceDataList) > 0) {
					$tradeOfferData['stock_balance_collection'] = data::parseTemplate($offerStockBalanceCollectionBlock, [
						'nodes:balance' => $stockBalanceDataList,
						'void:items' => $stockBalanceDataList,
					]);
				} else {
					$tradeOfferData['stock_balance_collection'] = data::parseTemplate($offerStockBalanceCollectionEmptyBlock, []);
				}

				$characteristicDataList = [];

				/** @var \UmiCms\System\Trade\Offer\iCharacteristic $characteristic */
				foreach ($offer->getCharacteristicCollection() as $characteristic) {
					$characteristicDataList[] = $this->renderProperty($characteristic->getProperty(), $template);
				}

				if (count($characteristicDataList) > 0) {
					$tradeOfferData['characteristic_collection'] = data::parseTemplate($offerCharacteristicCollectionBlock, [
						'nodes:characteristic' => $characteristicDataList,
						'void:items' => $characteristicDataList,
					]);
				} else {
					$tradeOfferData['characteristic_collection'] = data::parseTemplate($offerCharacteristicCollectionEmptyBlock, []);
				}

				$tradeOfferDataList[] = data::parseTemplate($offerBlock, $tradeOfferData);
			}

			return data::parseTemplate($offerCollectionBlock, [
				'field_id' => $property->getField()->getId(),
				'name' => $property->getName(),
				'title' => $property->getTitle(),
				'subnodes:offer_collection' => $tradeOfferDataList,
				'template' => $template
			]);
		}
	}

