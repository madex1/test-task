<?php

	use UmiCms\Service;
	use UmiCms\Classes\Components\Data\FormSaver;

	/**
	 * Класс для работы с формами.
	 *
	 * Умеет:
	 * 1) Возвращать данные для формирования формы редактирования объекта;
	 * 2) Возвращать данные для формирования формы добавления объекта;
	 * 3) Сохранять измененный объект;
	 * 4) Валидировать значения полей объекта, которые требуется сохранить.
	 *
	 * Применяется как в шаблонах клиентов, так и в шаблоне административной панели.
	 */
	class DataForms {

		/** @var data|FormSaver $module */
		public $module;

		/**
		 * Выводит данные для построения формы редактирования объекта с указанным id.
		 * @param int $objectId id объекта
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @param string $groupNames идентификаторы групп полей, разделенные пробелом
		 * @param bool $all выводить все возможные группы полей
		 * @return mixed
		 * @throws Exception
		 * @throws coreException
		 */
		public function getEditForm($objectId, $template = 'default', $groupNames = '', $all = false) {
			return $this->getEditFormWithIgnorePermissions($objectId, $template, $groupNames, $all);
		}

		/**
		 * Выводит данные для построения формы редактирования объекта с указанным id,
		 * с возможностью проигнорировать разрешения.
		 * @param int $id id объекта
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @param string $groups_names идентификаторы групп полей, разделенные пробелом
		 * @param bool $all выводить все возможные группы полей
		 * @param bool $ignorePermissions игнорировать права на редактирование объекта
		 * @return mixed
		 * @throws Exception
		 * @throws coreException
		 */
		public function getEditFormWithIgnorePermissions(
			$id,
			$template = 'default',
			$groups_names = '',
			$all = false,
			$ignorePermissions = false
		) {
			if (!$template) {
				$template = 'default';
			}

			$permissions = permissionsCollection::getInstance();
			if ($permissions->isSv()) {
				$ignorePermissions = true;
			}

			if (!$ignorePermissions) {
				$b_allow = $permissions->isOwnerOfObject($id);
				$pageIdList = umiHierarchy::getInstance()->getObjectInstances($id);
				$auth = Service::Auth();

				foreach ($pageIdList as $pageId) {
					$arr_allow = $permissions->isAllowedObject($auth->getUserId(), $pageId);
					if (is_array($arr_allow) && umiCount($arr_allow) > 1) {
						$b_allow = (int) $arr_allow[1];
						if ($b_allow) {
							break;
						}
					}
				}

				if (!$b_allow) {
					return data::parseTPLMacroses('%data_edit_foregin_object%');
				}
			}

			$groups_names = trim($groups_names);
			$groups_names = $groups_names !== '' ? explode(' ', $groups_names) : [];

			list(
				$template_block,
				$template_block_empty,
				$template_line
				) = data::loadTemplates(
				"data/reflection/{$template}",
				'reflection_block',
				'reflection_block_empty',
				'reflection_group'
			);

			$object = umiObjectsCollection::getInstance()
				->getObject($id);

			if (!$object instanceof iUmiObject) {
				return data::parseTemplate($template_block_empty, []);
			}

			$object_type_id = $object->getTypeId();
			$groups_arr = $this->module->getTypeFieldGroups($object_type_id);

			$groups = [];
			/** @var iUmiFieldsGroup $group */
			foreach ($groups_arr as $group) {
				if (!$group->getIsActive()) {
					continue;
				}

				if (umiCount($groups_names)) {
					if (!in_array($group->getName(), $groups_names)) {
						continue;
					}
				} else {
					if (!$group->getIsActive() || (!$group->getIsVisible() && !$all)) {
						continue;
					}
				}

				$line_arr = [];

				$fields_arr = $group->getFields();
				$fields = [];
				foreach ($fields_arr as $field) {
					if (!$field->getIsVisible() && !$all) {
						continue;
					}
					if ($field->getIsSystem()) {
						continue;
					}

					$fields[] = $this->renderEditField($template, $field, $object);
				}

				if (empty($fields)) {
					continue;
				}

				$line_arr['attribute:name'] = $group->getName();
				$line_arr['tip'] = $group->getTip();
				$line_arr['attribute:title'] = $group->getTitle();
				$line_arr['nodes:field'] = $line_arr['void:fields'] = $fields;

				$groups[] = data::parseTemplate($template_line, $line_arr);
			}

			$block_arr['nodes:group'] = $block_arr['void:groups'] = $groups;

			return data::parseTemplate($template_block, $block_arr, false, $id);
		}

		/**
		 * Выводит данные для построения формы добавления объекта
		 * с указанным идентификатором объектного типа данных.
		 * @param int $typeId идентификатор объектного типа данных
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @param string $groups_names идентификаторы групп полей, разделенные пробелом
		 * @param bool $all выводить все возможные группы полей
		 * @return mixed|string
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function getCreateForm($typeId, $template = 'default', $groups_names = '', $all = false) {
			if (!$template) {
				$template = 'default';
			}

			list(
				$template_block,
				$template_block_empty,
				$template_line
				) = data::loadTemplates(
				"data/reflection/{$template}",
				'reflection_block',
				'reflection_block_empty',
				'reflection_group'
			);

			if (!umiObjectTypesCollection::getInstance()->getType($typeId) instanceof iUmiObjectType) {
				return data::parseTemplate($template_block_empty, []);
			}

			$groups_names = trim($groups_names);
			$groups_names = $groups_names !== '' ? explode(' ', $groups_names) : [];
			$groups_arr = $this->module->getTypeFieldGroups($typeId);

			if (!is_array($groups_arr)) {
				return '';
			}

			$groups = [];
			/** @var iUmiFieldsGroup $group */
			foreach ($groups_arr as $group) {
				if (!$group->getIsActive()) {
					continue;
				}
				if ($group->getName() == 'locks') {
					continue;
				}
				if (umiCount($groups_names)) {
					if (!in_array($group->getName(), $groups_names)) {
						continue;
					}
				} else {
					if (!$group->getIsActive() || (!$group->getIsVisible() && !$all)) {
						continue;
					}
				}

				$line_arr = [];
				$fields_arr = $group->getFields();
				$fields = [];

				foreach ($fields_arr as $field) {
					if (!$field->getIsVisible() && !$all) {
						continue;
					}
					if ($field->getIsSystem()) {
						continue;
					}

					$fields[] = $this->renderEditField($template, $field);
				}

				if (empty($fields)) {
					continue;
				}

				$line_arr['attribute:name'] = $group->getName();
				$line_arr['tip'] = $group->getTip();
				$line_arr['attribute:title'] = $group->getTitle();

				$line_arr['nodes:field'] = $line_arr['void:fields'] = $fields;

				$groups[] = data::parseTemplate($template_line, $line_arr);
			}

			$block_arr['nodes:group'] = $block_arr['void:groups'] = $groups;
			return data::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Выводит данные поля для построения формы редактирования
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @param iUmiField $field объект поля
		 * @param bool|iUmiObject $object объект, которому принадлежит поле
		 * @return mixed
		 * @throws Exception
		 * @throws coreException
		 */
		private function renderEditField($template, iUmiField $field, $object = false) {
			$fieldType = umiFieldTypesCollection::getInstance()
				->getFieldType($field->getFieldTypeId());
			$dataType = $fieldType->getDataType();

			switch ($dataType) {
				case 'counter':
				case 'int': {
					$result = $this->renderEditFieldInt($field, $object, $template);
					$dataType = 'int';
					break;
				}
				case 'link_to_object_type': {
					$result = $this->renderEditFieldInt($field, $object, $template);
					break;
				}
				case 'price': {
					$result = $this->renderEditFieldPrice($field, $object, $template);
					break;
				}
				case 'float': {
					$result = $this->renderEditFieldInt($field, $object, $template);
					break;
				}
				case 'color':
				case 'string': {
					$result = $this->renderEditFieldString($field, $object, $template);
					break;
				}
				case 'date': {
					$result = $this->renderEditFieldDate($field, $object, $template);
					break;
				}
				case 'password': {
					$result = $this->renderEditFieldPassword($field, $object, $template);
					break;
				}
				case 'relation': {
					$result = $this->renderEditFieldRelation($field, $fieldType->getIsMultiple(), $object, $template);
					break;
				}
				case 'symlink': {
					$result = $this->renderEditFieldSymlink($field, $object, $template);
					break;
				}
				case 'img_file': {
					$result = $this->renderEditFieldImageFile($field, $object, $template);
					break;
				}
				case 'video_file' :
				case 'swf_file':
				case 'file': {
					$result = $this->renderEditFieldFile($field, $object, $template);
					break;
				}
				case 'text': {
					$result = $this->renderEditFieldText($field, $object, $template);
					break;
				}
				case 'wysiwyg': {
					$result = $this->renderEditFieldWYSIWYG($field, $object, $template);
					break;
				}
				case 'boolean': {
					$result = $this->renderEditFieldBoolean($field, $object, $template);
					break;
				}
				case 'tags': {
					$result = $this->renderEditFieldTags($field, $object, $template);
					break;
				}
				case 'optioned': {
					$result = $this->renderEditFieldOptioned($field, $object);
					break;
				}
				case 'multiple_image': {
					$result = $this->renderEditFieldMultipleImage($field, $object, $template);
					break;
				}
				case 'domain_id':
				case 'domain_id_list': {
					$result = $this->renderEditFieldDomainIdList($field, $fieldType->getIsMultiple(), $object, $template);
					break;
				}
				case 'offer_id':
				case 'offer_id_list': {
					$result = $this->renderEditFieldOfferIdList($field, $fieldType->getIsMultiple(), $object, $template);
					break;
				}
				default: {
					$result = '';
				}
			}

			if ($result === false) {
				return null;
			}

			if (data::isXSLTResultMode()) {
				$result['attribute:type'] = $dataType;
				$result['attribute:id'] = $field->getId();

				if ($field->getIsRequired()) {
					$result['attribute:required'] = 'required';
				}

				$tip = $field->getTip();
				if ($tip) {
					$result['attribute:tip'] = $tip;
				}
			} else {
				$required = $field->getIsRequired();
				$result = data::parseTemplate($result, [
					'required' => $required ? 'required' : '',
					'required_asteriks' => $required ? '*' : '',
				]);
			}

			return $result;
		}

		/**
		 * Выводит данные поля типов "строка" и "цвет" для построения формы редактирования
		 * @param iUmiField $field поле
		 * @param iUmiObject|bool $object объект, которому принадлежит поле
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		private function renderEditFieldString(iUmiField $field, $object, $template) {
			list($template_block) = data::loadTemplates("data/reflection/{$template}", 'reflection_field_string');
			$block_arr = [];

			$field_name = $field->getName();
			$block_arr['attribute:name'] = $field_name;
			$block_arr['attribute:title'] = $field->getTitle();
			$block_arr['attribute:tip'] = $field->getTip();
			$block_arr['attribute:field_id'] = $field->getId();
			$block_arr['attribute:is_important'] = $field->isImportant();
			$block_arr['node:value'] = $object ? $object->getValue($field->getName()) : '';
			$block_arr['attribute:restriction'] = $this->getRestrictionClassName($field->getRestrictionId());

			if ($object) {
				$block_arr['void:object_id'] = $object->getId();
			}

			$block_arr['attribute:input_name'] = $this->getInputName($object, $field_name);
			return data::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Выводит данные поля типа "дата" для построения формы редактирования
		 * @param iUmiField $field поле
		 * @param iUmiObject|bool $object объект, которому принадлежит поле
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		private function renderEditFieldDate(iUmiField $field, $object, $template) {
			list(
				$template_block_string,
				$template_block
				) = data::loadTemplates(
				"data/reflection/{$template}",
				'reflection_field_string',
				'reflection_field_date'
			);

			if (!$template_block) {
				$template_block = $template_block_string;
			}

			$block_arr = [];
			$field_name = $field->getName();
			$block_arr['attribute:name'] = $field_name;
			$block_arr['attribute:title'] = $field->getTitle();
			$block_arr['attribute:tip'] = $field->getTip();
			$block_arr['attribute:field_id'] = $field->getId();
			$block_arr['attribute:is_important'] = $field->isImportant();
			$block_arr['attribute:restriction'] = $this->getRestrictionClassName($field->getRestrictionId());


			$block_arr['node:value'] = '';
			$block_arr['attribute:timestamp'] = 0;

			if ($object) {
				$oDate = $object->getValue($field->getName());

				if ($oDate instanceof umiDate) {
					$block_arr['attribute:timestamp'] = $oDate->getDateTimeStamp();
					$block_arr['node:value'] = $oDate->getFormattedDate();
					$block_arr['attribute:formatted-date'] = $oDate->getFormattedDate('d.m.Y H:i');
				}

				$block_arr['void:object_id'] = $object->getId();
			}

			$block_arr['attribute:input_name'] = $this->getInputName($object, $field_name);
			return data::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Выводит данные поля типа "простой текст" для построения формы редактирования
		 * @param iUmiField $field поле
		 * @param iUmiObject|bool $object объект, которому принадлежит поле
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		private function renderEditFieldText(iUmiField $field, $object, $template) {
			list($template_block) = data::loadTemplates("data/reflection/{$template}", 'reflection_field_text');
			$block_arr = [];
			$field_name = $field->getName();
			$block_arr['attribute:name'] = $field_name;
			$block_arr['attribute:title'] = $field->getTitle();
			$block_arr['attribute:tip'] = $field->getTip();
			$block_arr['attribute:field_id'] = $field->getId();
			$block_arr['attribute:is_important'] = $field->isImportant();
			$block_arr['node:value'] = $object ? $object->getValue($field->getName()) : '';
			$block_arr['attribute:restriction'] = $this->getRestrictionClassName($field->getRestrictionId());

			if ($object) {
				$block_arr['void:object_id'] = $object->getId();
			}

			$block_arr['attribute:input_name'] = $this->getInputName($object, $field_name);
			return data::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Выводит данные поля типа "HTML-текст" для построения формы редактирования
		 * @param iUmiField $field поле
		 * @param iUmiObject|bool $object объект, которому принадлежит поле
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		private function renderEditFieldWYSIWYG(iUmiField $field, $object, $template) {
			list($template_block) = data::loadTemplates("data/reflection/{$template}", 'reflection_field_wysiwyg');
			$block_arr = [];
			$field_name = $field->getName();
			$block_arr['attribute:name'] = $field_name;
			$block_arr['attribute:title'] = $field->getTitle();
			$block_arr['attribute:tip'] = $field->getTip();
			$block_arr['attribute:field_id'] = $field->getId();
			$block_arr['attribute:is_important'] = $field->isImportant();
			$block_arr['node:value'] = $object ? $object->getValue($field->getName()) : '';
			$block_arr['attribute:restriction'] = $this->getRestrictionClassName($field->getRestrictionId());


			if ($object) {
				$block_arr['void:object_id'] = $object->getId();
			}

			$block_arr['attribute:input_name'] = $this->getInputName($object, $field_name);
			return data::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Выводит данные поля типов "Число", "Счетчик", "Число с точкой" и "Ссылка на объектный тип"
		 * для построения формы редактирования.
		 * @param iUmiField $field поле
		 * @param iUmiObject|bool $object объект, которому принадлежит поле
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		private function renderEditFieldInt(iUmiField $field, $object, $template) {
			list($template_block) = data::loadTemplates("data/reflection/{$template}", 'reflection_field_int');
			$block_arr = [];
			$field_name = $field->getName();
			$block_arr['attribute:name'] = $field_name;
			$block_arr['attribute:title'] = $field->getTitle();
			$block_arr['attribute:tip'] = $field->getTip();
			$block_arr['attribute:field_id'] = $field->getId();
			$block_arr['attribute:is_important'] = $field->isImportant();
			$block_arr['node:value'] = $object ? $object->getValue($field->getName()) : '';
			$block_arr['attribute:restriction'] = $this->getRestrictionClassName($field->getRestrictionId());


			if ($object) {
				$block_arr['void:object_id'] = $object->getId();
			}

			$block_arr['attribute:input_name'] = $this->getInputName($object, $field_name);
			return data::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Выводит данные поля типа "Цена" для построения формы редактирования
		 * @param iUmiField $field поле
		 * @param iUmiObject|bool $object объект, которому принадлежит поле
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @return mixed
		 * @throws Exception
		 */
		private function renderEditFieldPrice(iUmiField $field, $object, $template) {
			list($templateBlock) = data::loadTemplates("data/reflection/{$template}", 'reflection_field_int');
			$data = [];
			$fieldName = $field->getName();
			$data['attribute:name'] = $fieldName;
			$data['attribute:title'] = $field->getTitle();
			$data['attribute:tip'] = $field->getTip();
			$data['attribute:field_id'] = $field->getId();
			$data['attribute:is_important'] = $field->isImportant();
			$data['attribute:restriction'] = $this->getRestrictionClassName($field->getRestrictionId());

			/** @var emarket $emarket */
			$emarket = cmsController::getInstance()->getModule('emarket');

			if ($emarket instanceof def_module) {
				$currency = $emarket->getCurrencyFacade()
					->getDefault();
				$data['attribute:currency_id'] = $currency->getId();
				$data['attribute:currency_code'] = $currency->getCode();
				$data['attribute:currency_prefix'] = $currency->getPrefix();
				$data['attribute:currency_suffix'] = $currency->getSuffix();
			}

			$data['node:value'] = '';

			if ($object instanceof iUmiObject) {
				$data['node:value'] = $object->getValue($field->getName());
				$data['void:object_id'] = $object->getId();
			}

			$data['attribute:input_name'] = $this->getInputName($object, $fieldName);
			return data::parseTemplate($templateBlock, $data);
		}

		/**
		 * Выводит данные поля типа "Кнопка-флажок" для построения формы редактирования
		 * @param iUmiField $field поле
		 * @param iUmiObject|bool $object объект, которому принадлежит поле
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		private function renderEditFieldBoolean(iUmiField $field, $object, $template) {
			list($template_block) = data::loadTemplates("data/reflection/{$template}", 'reflection_field_boolean');
			$block_arr = [];
			$field_name = $field->getName();
			$block_arr['attribute:name'] = $field_name;
			$block_arr['attribute:title'] = $field->getTitle();
			$block_arr['attribute:tip'] = $field->getTip();
			$block_arr['attribute:field_id'] = $field->getId();
			$block_arr['attribute:is_important'] = $field->isImportant();
			$block_arr['attribute:checked'] = '';
			$block_arr['node:value'] = 0;
			$block_arr['attribute:restriction'] = $this->getRestrictionClassName($field->getRestrictionId());

			if ($object) {
				$block_arr['node:value'] = (int) $object->getValue($field->getName());
				$block_arr['attribute:checked'] = (bool) $object->getValue($field->getName()) ? 'checked' : '';
				$block_arr['void:object_id'] = $object->getId();
			}

			$block_arr['attribute:input_name'] = $this->getInputName($object, $field_name);
			return data::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Выводит данные поля типа "Пароль" для построения формы редактирования
		 * @param iUmiField $field поле
		 * @param iUmiObject|bool $object объект, которому принадлежит поле
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		private function renderEditFieldPassword(iUmiField $field, $object, $template) {
			list($template_block) = data::loadTemplates("data/reflection/{$template}", 'reflection_field_password');
			$block_arr = [];
			$field_name = $field->getName();
			$block_arr['attribute:name'] = $field_name;
			$block_arr['attribute:title'] = $field->getTitle();
			$block_arr['attribute:tip'] = $field->getTip();
			$block_arr['attribute:field_id'] = $field->getId();
			$block_arr['attribute:is_important'] = $field->isImportant();
			$block_arr['node:value'] = '';
			$block_arr['attribute:restriction'] = $this->getRestrictionClassName($field->getRestrictionId());

			if ($object) {
				$block_arr['void:object_id'] = $object->getId();
			}

			$isMultiple = true; // пароль содержит только одно значение, но такого формирования требуют шаблоны
			$block_arr['attribute:input_name'] = $this->getInputName($object, $field_name, $isMultiple);
			return data::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Выводит данные поля типов "Выпадающие список" и "Выпадающие список со множественным выбором"
		 * для построения формы редактирования
		 * @param iUmiField $field поле
		 * @param bool $is_multiple может ли поле хранит набор значений
		 * @param iUmiObject|bool $object объект, которому принадлежит поле
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @return mixed
		 * @throws Exception
		 * @throws coreException
		 */
		private function renderEditFieldRelation(iUmiField $field, $is_multiple, $object, $template) {
			$objects = umiObjectsCollection::getInstance();
			$guide_items = [];

			$fieldName = $field->getName();
			$guideId = $field->getGuideId();

			if ($guideId) {
				switch (true) {
					case (Service::Request()->isAdmin() && $object instanceof iUmiObject): {
						if ($object->getTypeGUID() == 'users-user') {
							$guide_items = $objects->getGuidedItems($guideId);
							break;
						}

						$val = $object->getValue($fieldName);
						if (!$val) {
							break;
						}

						if (!is_array($val)) {
							$val = [$val];
						}

						foreach ($val as $item_id) {
							$item = $objects->getObject($item_id);
							if (!$item instanceof iUmiObject) {
								continue;
							}
							$guide_items[$item_id] = $item->getName();
						}

						break;
					}

					default: {
						$guide_items = $objects->getGuidedItems($guideId);
					}
				}
			}

			list(
				$template_block,
				$template_block_line,
				$template_block_line_a,
				$template_mul_block,
				$template_mul_block_line,
				$template_mul_block_line_a
				) = data::loadTemplates(
				"data/reflection/{$template}",
				'reflection_field_relation',
				'reflection_field_relation_option',
				'reflection_field_relation_option_a',
				'reflection_field_multiple_relation',
				'reflection_field_multiple_relation_option',
				'reflection_field_multiple_relation_option_a'
			);

			$block_arr = [];
			$value = $object ? $object->getValue($fieldName) : [];

			if ($fieldName == 'publish_status' && Service::Request()->isNotAdmin()) {
				return '';
			}
			$block_arr['attribute:name'] = $fieldName;
			$block_arr['attribute:title'] = $field->getTitle();
			$block_arr['attribute:tip'] = $field->getTip();
			$block_arr['attribute:field_id'] = $field->getId();
			$block_arr['attribute:is_important'] = $field->isImportant();
			$block_arr['attribute:restriction'] = $this->getRestrictionClassName($field->getRestrictionId());

			if ($is_multiple) {
				$block_arr['attribute:multiple'] = 'multiple';
			}

			if ($guideId) {
				$block_arr['attribute:type-id'] = $guideId;
				$guide = umiObjectTypesCollection::getInstance()->getType($guideId);

				if ($guide instanceof iUmiObjectType) {
					if ($guide->getIsPublic()) {
						$block_arr['attribute:public-guide'] = true;
					}
				}
			}

			$isTpl = !$template_block_line && !data::isXSLTResultMode();
			$options = $isTpl ? '' : [];

			foreach ($guide_items as $item_id => $item_name) {
				$item_object = $objects->getObject($item_id);

				if (!is_object($item_object)) {
					continue;
				}

				if ($is_multiple) {
					$selected = in_array($item_id, $value) ? ' selected' : '';
				} else {
					$selected = ($item_id == $value) ? ' selected' : '';
				}

				if ($item_object->getValue('is_hidden') && !$selected) {
					continue;
				}

				if ($isTpl) {
					$options .= "<option value=\"{$item_id}\" {$selected}>{$item_name}</option>\n";
				} else {
					$line_arr = [];
					$line_arr['attribute:id'] = $item_id;
					$line_arr['xlink:href'] = 'uobject://' . $item_id;
					$line_arr['attribute:guid'] = $item_object->getGUID();
					$line_arr['node:name'] = $item_name;

					if ($selected) {
						$line_arr['attribute:selected'] = 'selected';
						$line = $is_multiple ? $template_mul_block_line_a : $template_block_line_a;
					} else {
						$line = $is_multiple ? $template_mul_block_line : $template_block_line;
					}

					$options[] = data::parseTemplate($line, $line_arr, false, $item_id);
				}
			}

			if ($object) {
				$block_arr['void:object_id'] = $object->getId();
			}

			$block_arr['subnodes:values'] = $block_arr['void:options'] = $options;
			$block_arr['attribute:input_name'] = $this->getInputName($object, $fieldName, $is_multiple);
			return data::parseTemplate(($is_multiple ? $template_mul_block : $template_block), $block_arr);
		}

		/**
		 * Выводит данные поля типа "Ссылка не дерево" для построения формы редактирования
		 * @param iUmiField $field поле
		 * @param iUmiObject|bool $object объект, которому принадлежит поле
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		private function renderEditFieldSymlink(iUmiField $field, $object, $template) {
			list($template_block) = data::loadTemplates(
				"data/reflection/{$template}",
				'reflection_field_relation',
				'reflection_field_relation_option',
				'reflection_field_relation_option_a',
				'reflection_field_multiple_relation',
				'reflection_field_multiple_relation_option',
				'reflection_field_multiple_relation_option_a'
			);
			$block_arr = [];
			$field_name = $field->getName();
			$block_arr['attribute:name'] = $field_name;
			$block_arr['attribute:title'] = $field->getTitle();
			$block_arr['attribute:tip'] = $field->getTip();
			$block_arr['attribute:field_id'] = $field->getId();
			$block_arr['attribute:is_important'] = $field->isImportant();
			$block_arr['attribute:restriction'] = $this->getRestrictionClassName($field->getRestrictionId());

			$options = $object ? $object->getValue($field->getName()) : [];

			$block_arr['subnodes:values'] = $block_arr['void:options'] = $options;
			$block_arr['attribute:input_name'] = $this->getInputName($object, $field_name, true);
			return data::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Выводит данные поля типа "Теги" для построения формы редактирования
		 * @param iUmiField $field поле
		 * @param iUmiObject|bool $object объект, которому принадлежит поле
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		private function renderEditFieldTags(iUmiField $field, $object, $template) {
			list($template_block) = data::loadTemplates("data/reflection/{$template}", 'reflection_field_tags');
			$block_arr = [];
			$field_name = $field->getName();
			$block_arr['attribute:name'] = $field_name;
			$block_arr['attribute:title'] = $field->getTitle();
			$block_arr['attribute:tip'] = $field->getTip();
			$block_arr['attribute:field_id'] = $field->getId();
			$block_arr['attribute:is_important'] = $field->isImportant();
			$block_arr['attribute:restriction'] = $this->getRestrictionClassName($field->getRestrictionId());

			$value = $object ? $object->getValue($field->getName()) : '';

			if (is_array($value)) {
				$value = implode(', ', $value);
			}

			$block_arr['node:value'] = $value;

			if ($object) {
				$block_arr['void:object_id'] = $object->getId();
			}

			$isMultiple = false; // теги содержат несколько значений, но такого формирования требуют шаблоны
			$block_arr['attribute:input_name'] = $this->getInputName($object, $field_name, $isMultiple);
			return data::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Выводит данные поля типа "Составное" для построения формы редактирования
		 * @param iUmiField $field поле
		 * @param iUmiObject|bool $object объект, которому принадлежит поле
		 * @return mixed
		 * @throws coreException
		 */
		private function renderEditFieldOptioned(iUmiField $field, $object) {
			$block_arr = [];
			$objects = umiObjectsCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$field_name = $field->getName();
			$block_arr['attribute:name'] = $field_name;
			$block_arr['attribute:title'] = $field->getTitle();
			$block_arr['attribute:tip'] = $field->getTip();
			$block_arr['attribute:field_id'] = $field->getId();
			$block_arr['attribute:is_important'] = $field->isImportant();
			$block_arr['attribute:restriction'] = $this->getRestrictionClassName($field->getRestrictionId());

			$guideId = $field->getGuideId();
			if ($guideId) {
				$block_arr['attribute:guide-id'] = $guideId;

				$guide = umiObjectTypesCollection::getInstance()
					->getType($guideId);
				$isPublic = ($guide instanceof iUmiObjectType) ? $guide->isPublicGuide() : false;
				$block_arr['attribute:public-guide'] = (int) $isPublic;
			}

			$values = $object ? $object->getValue($field->getName()) : [];

			$values_arr = [];
			foreach ($values as $value) {
				$value_arr = [];
				foreach ($value as $type => $subValue) {
					switch ($type) {
						case 'tree': {
							$relatedElement = $hierarchy->getElement($subValue);
							if ($relatedElement instanceof iUmiHierarchyElement) {
								$value_arr['page'] = $relatedElement;
							}
							break;
						}

						case 'rel': {
							$relatedObject = $objects->getObject($subValue);
							if ($relatedObject instanceof iUmiObject) {
								$value_arr['object'] = $relatedObject;
							}
							break;
						}

						default: {
							$value_arr['attribute:' . $type] = $subValue;
							break;
						}
					}
				}

				$values_arr[] = $value_arr;
			}

			$block_arr['values']['nodes:value'] = $values_arr;
			$block_arr['attribute:input_name'] = $this->getInputName($object, $field_name);
			return $block_arr;
		}

		/**
		 * Выводит данные поля типа "Набор изображений" для построения формы редактирования
		 * @param iUmiField $field
		 * @param iUmiObject|bool $object объект, которому принадлежит поле
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		private function renderEditFieldMultipleImage(iUmiField $field, $object, $template) {
			list($fieldBlock, $valueBlock) = data::loadTemplates(
				'data/reflection/' . $template,
				'reflection_field_multiple_image_field',
				'reflection_field_multiple_image_value'
			);

			$fieldNode = [];
			$fieldName = $field->getName();
			$fieldNode['attribute:name'] = $fieldName;
			$fieldNode['attribute:title'] = $field->getTitle();
			$fieldNode['attribute:tip'] = $field->getTip();
			$fieldNode['attribute:maxsize'] = $this->module->getAllowedMaxFileSize('img');
			$fieldNode['attribute:field_id'] = $field->getId();
			$fieldNode['attribute:is_important'] = $field->isImportant();
			$fieldNode['attribute:restriction'] = $this->getRestrictionClassName($field->getRestrictionId());

			$values = ($object instanceof iUmiObject) ? (array) $object->getValue($fieldName) : [];

			$valuesNode = [];
			/* @var umiImageFile $value */
			foreach ($values as $key => $value) {
				if (!$value instanceof umiImageFile || $value->getIsBroken()) {
					continue;
				}

				$valueNode = [
					'attribute:id' => $value->getId(),
					'attribute:alt' => $value->getAlt(),
					'attribute:title' => $value->getTitle(),
					'attribute:order' => $value->getOrder(),
					'attribute:relative-path' => $value->getFilePath(true),
					'attribute:destination-folder' => $value->getDirName()
				];

				$destinationFolder = USER_IMAGES_PATH . '/data/';
				$relativePath = mb_substr($value->getDirName(), mb_strlen($destinationFolder)) . '/' . $value->getFileName();

				if ($relativePath[0] == '/') {
					$relativePath = mb_substr($relativePath, 1);
				}

				$valueNode['node:value'] = $relativePath;
				$valuesNode[] = data::parseTemplate($valueBlock, $valueNode);
			}

			$fieldNode['values']['nodes:value'] = $valuesNode;
			$fieldNode['attribute:input_name'] = $this->getInputName($object, $fieldName);
			return data::parseTemplate($fieldBlock, $fieldNode);
		}

		/**
		 * Выводит данные поля типов "Ссылка на список доменов" и "Ссылка на домен"
		 * @param iUmiField $field поле
		 * @param bool $multiple многозначно ли поле
		 * @param iUmiObject|bool $object объект, которому принадлежит поле
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		private function renderEditFieldDomainIdList(iUmiField $field, $multiple, $object, $template) {
			list(
				$wrapperTemplate,
				$domainIdTemplate,
				$selectedDomainIdTemplate,
				$domainIdMultipleTemplate,
				$selectedDomainIdMultipleTemplate,
				) = data::loadTemplates(
				"data/reflection/{$template}",
				'reflection_field_domain_id',
				'reflection_field_domain_id_domain',
				'reflection_field_domain_id_domain_selected',
				'reflection_field_domain_id_domain_multiple',
				'reflection_field_domain_id_domain_multiple_selected'
			);

			$domainIdTemplate = $multiple ? $domainIdMultipleTemplate : $domainIdTemplate;
			$selectedDomainIdTemplate = $multiple ? $selectedDomainIdMultipleTemplate : $selectedDomainIdTemplate;
			$domainList = $this->renderDomainList($field, $object, $domainIdTemplate, $selectedDomainIdTemplate);

			$inputName = $this->getInputName($object, $field->getName(), $multiple);
			return $this->renderDomainField($field, $inputName, $domainList, $wrapperTemplate);
		}

		/**
		 * Возвращает данные списка доменов для трансформации в xml
		 * @param iUmiField $field поле
		 * @param iUmiObject|bool $object объект, если задан
		 * @param mixed $defaultTemplate шаблон отображение домена (для tpl)
		 * @param mixed $selectedTemplate шаблон отображение выбранного домена (для tpl)
		 * @return array
		 * @throws Exception
		 * @throws coreException
		 */
		private function renderDomainList($field, $object, $defaultTemplate, $selectedTemplate) {
			$selectedIdList = ($object instanceof iUmiObject) ? (array) $object->getValue($field->getName()) : [];
			$domainList = [];

			foreach (Service::DomainCollection()->getList() as $domain) {
				$isSelected = in_array($domain->getId(), $selectedIdList);

				$domainData = translatorWrapper::get($domain)
					->translate($domain);
				$domainData += [
					'attribute:selected' => (int) $isSelected
				];

				$template = $isSelected ? $selectedTemplate : $defaultTemplate;
				$domainList[] = data::parseTemplate($template, $domainData);
			}

			return $domainList;
		}

		/**
		 * Возвращает данные поля типов "Ссылка на домен" и "Ссылка на список доменов" для трансформации в xml
		 * @param iUmiField $field поле
		 * @param string $inputName имя поля для ввода значения
		 * @param array $domainList список доменов
		 * @param mixed $template шаблон отображение поля (для tpl)
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		private function renderDomainField(iUmiField $field, $inputName, array $domainList, $template) {
			$fieldData = translatorWrapper::get($field)
				->translate($field);

			$fieldData += [
				'attribute:input_name' => $inputName,
				'attribute:restriction' => $this->getRestrictionClassName($field->getRestrictionId()),
				'value' => [
					'nodes:domain' => $domainList
				]
			];

			return data::parseTemplate($template, $fieldData);
		}

		/**
		 * Выводит данные поля типа "Ссылка на список торговых предложений"
		 * @param iUmiField $field поле
		 * @param bool $multiple многозначно ли поле
		 * @param iUmiObject|bool $object объект, если задан
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @return mixed
		 * @throws Exception
		 * @throws coreException
		 * @throws ErrorException
		 * @throws databaseException
		 * @throws ReflectionException
		 */
		private function renderEditFieldOfferIdList(iUmiField $field, $multiple, $object, $template) {
			list($fieldBlock, $valueBlock, $multipleValueBlock) = data::loadTemplates(
				'data/reflection/' . $template,
				'reflection_field_offer_list_field',
				'reflection_field_offer_list_value',
				'reflection_field_offer_list_value_multiple'
			);

			$fieldName = $field->getName();
			$offerIdList = ($object instanceof iUmiObject) ? (array) $object->getValue($fieldName) : [];
			$offerNodeList = [];
			$valueBlock = $multiple ? $multipleValueBlock : $valueBlock;

			foreach (Service::TradeOfferFacade()->getList($offerIdList) as $offer) {
				$offerNode = translatorWrapper::get($offer)
					->translate($offer);
				$offerNodeList[] = data::parseTemplate($valueBlock, $offerNode);
			}

			$fieldNode = [
				'attribute:name' => $fieldName,
				'attribute:title' => $field->getTitle(),
				'attribute:tip' => $field->getTip(),
				'attribute:field_id' => $field->getId(),
				'attribute:is_important' => $field->isImportant(),
				'attribute:input_name' => $this->getInputName($object, $fieldName, $multiple),
				'attribute:restriction' => $this->getRestrictionClassName($field->getRestrictionId()),
				'values' => [
					'nodes:offer' => $offerNodeList
				]
			];

			return data::parseTemplate($fieldBlock, $fieldNode);
		}

		/**
		 * Возвращает идентификатор объекта или ключевое слово "new"
		 * @param iUmiObject|bool $object объект, если задан
		 * @return int|string
		 */
		private function getObjectId($object) {
			return ($object instanceof iUmiObject) ? $object->getId() : 'new';
		}

		/**
		 * Формирует имя поля для ввода значения
		 * @param iUmiObject|bool $object объект, если задан
		 * @param string $fieldName имя поля
		 * @param bool $isMultiple является ли поля многозначным
		 * @return string
		 */
		private function getInputName($object, $fieldName, $isMultiple = false) {
			$objectId = $this->getObjectId($object);
			$multiple = $isMultiple ? '[]' : '';
			return sprintf('data[%s][%s]%s', $objectId, $fieldName, $multiple);
		}

		/**
		 * Выводит данные поля типа "Изображение" для построения формы редактирования
		 * @param iUmiField $field поле
		 * @param iUmiObject|bool $object объект, которому принадлежит поле
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		private function renderEditFieldImageFile(iUmiField $field, $object, $template) {
			list($template_block) = data::loadTemplates("data/reflection/{$template}", 'reflection_field_img_file');

			$block_arr = [];
			$field_name = $field->getName();
			$block_arr['attribute:name'] = $field_name;
			$block_arr['attribute:title'] = $field->getTitle();
			$block_arr['attribute:tip'] = $field->getTip();
			$block_arr['attribute:maxsize'] = $this->module->getAllowedMaxFileSize('img');
			$block_arr['attribute:field_id'] = $field->getId();
			$block_arr['attribute:is_important'] = $field->isImportant();
			$block_arr['attribute:restriction'] = $this->getRestrictionClassName($field->getRestrictionId());

			$value = $object ? $object->getValue($field->getName()) : '';

			if ($value instanceof iUmiImageFile) {
				$block_arr['attribute:relative-path'] = $value->getFilePath(true);

				switch ($field_name) {
					case 'menu_pic_ua' : {
						$destination_folder = USER_IMAGES_PATH . '/cms/menu/';
						break;
					}
					case 'header_pic' : {
						$destination_folder = USER_IMAGES_PATH . '/cms/headers/';
						break;
					}
					case 'menu_pic_a' : {
						$destination_folder = USER_IMAGES_PATH . '/cms/menu/';
						break;
					}
					default : {
						$destination_folder = USER_IMAGES_PATH . '/cms/data/';
						break;
					}
				}

				$info = pathinfo($value->getFilePath(true));
				$info['dirname'] = '.' . $info['dirname'];

				$relative_path = mb_substr($info['dirname'], mb_strlen($destination_folder)) . '/' . $info['basename'];
				if ($relative_path[0] == '/') {
					$relative_path = mb_substr($relative_path, 1);
				}

				$block_arr['node:value'] = $relative_path;
				$destination_folder = $info['dirname'];
				$block_arr['attribute:image_id'] = $value->getId();
				$block_arr['attribute:image_alt'] = $value->getAlt();
				$block_arr['attribute:image_title'] = $value->getTitle();
			} else {
				$block_arr['node:value'] = '';
				$folder_name = $field_name . '/';
				$general_name = USER_IMAGES_PATH . '/cms/';
				$destination_folder = $general_name . (is_dir($general_name . $folder_name) ? $folder_name : '');

				switch ($field_name) {
					case 'menu_pic_ua' :
						$pFolder = 'menu';
						break;

					case 'header_pic' :
						$pFolder = 'headers';
						break;

					case 'menu_pic_a' :
						$pFolder = 'menu';
						break;
					default :
						$pFolder = 'data';
						break;
				}

				$destination_folder .= $pFolder;
			}

			if ($object) {
				$block_arr['void:object_id'] = $object->getId();
			}

			$block_arr['attribute:input_name'] = $this->getInputName($object, $field_name);

			if (startsWith($destination_folder, CURRENT_WORKING_DIR)) {
				$destination_folder = str_replace(CURRENT_WORKING_DIR, '.', $destination_folder);
			}

			$block_arr['attribute:destination-folder'] = $destination_folder;

			return data::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Выводит данные поля типов "Файл", "Видео" и "Flash" для построения формы редактирования
		 * @param iUmiField $field поле
		 * @param iUmiObject|bool $object объект, которому принадлежит поле
		 * @param string $template имя шаблона для tpl шаблонизатора
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		private function renderEditFieldFile(iUmiField $field, $object, $template) {
			list($template_block) = data::loadTemplates("data/reflection/{$template}", 'reflection_field_file');

			$block_arr = [];
			$field_name = $field->getName();
			$block_arr['attribute:name'] = $field_name;
			$block_arr['attribute:title'] = $field->getTitle();
			$block_arr['attribute:tip'] = $field->getTip();
			$block_arr['attribute:maxsize'] = $this->module->getAllowedMaxFileSize();
			$block_arr['attribute:field_id'] = $field->getId();
			$block_arr['attribute:is_important'] = $field->isImportant();
			$block_arr['attribute:restriction'] = $this->getRestrictionClassName($field->getRestrictionId());

			/** @var iUmiFile $value */
			$value = $object ? $object->getValue($field->getName()) : '';
			if ($value instanceof iUmiFile) {
				$block_arr['attribute:relative-path'] = $value->getFilePath(true);
				$block_arr['node:value'] = $value->getFilePath();
			} else {
				$block_arr['node:value'] = '';
			}

			if ($object) {
				$block_arr['void:object_id'] = $object->getId();
			}

			$block_arr['attribute:input_name'] = $this->getInputName($object, $field_name);

			$folder_name = $field_name . '/';
			$general_name = USER_FILES_PATH;

			if ($value instanceof iUmiFile && $value->getIsBroken()) {
				$value = false;
			}

			if ($value) {
				$destination_folder = $value->getDirName();
			} else {
				$destination_folder = $general_name . (is_dir($general_name . $folder_name) ? $folder_name : '');
			}

			if (startsWith($destination_folder, CURRENT_WORKING_DIR)) {
				$destination_folder = str_replace(CURRENT_WORKING_DIR, '.', $destination_folder);
			}

			$block_arr['attribute:destination-folder'] = $destination_folder;
			return data::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Возвращает имя класса ограничения поля
		 * @param int $id идентификатор ограничения поля
		 * @return string|null
		 */
		private function getRestrictionClassName($id) {
			$restriction = baseRestriction::get($id);
			return ($restriction instanceof baseRestriction) ? $restriction->getClassName() : null;
		}

		/**
		 * @deprecated
		 * @see FormSaver::saveEditedObject()
		 */
		public function saveEditedObject($objectId, $isNew = false, $ignorePermissions = false, $all = false) {
			return $this->module->saveEditedObject($objectId, $isNew, false, $all);
		}

		/**
		 * @deprecated
		 * @see FormSaver::saveEditedObjectWithIgnorePermissions()
		 */
		public function saveEditedObjectWithIgnorePermissions(
			$objectId,
			$isNew = false,
			$ignorePermissions = false,
			$all = false
		) {
			return $this->module->saveEditedObjectWithIgnorePermissions(
				$objectId,
				$isNew,
				$ignorePermissions,
				$all
			);
		}

		/**
		 * @deprecated
		 * @see FormSaver::prepareEditedObjectRequestData()
		 */
		public function prepareEditedObjectRequestData($objectId, $isNew = false) {
			return $this->module->prepareEditedObjectRequestData($objectId, $isNew);
		}

		/**
		 * @deprecated
		 * @see FormSaver::saveEditedObjectData()
		 */
		public function saveEditedObjectData(
			$objectId,
			array $data,
			$isNew = false,
			$ignorePermissions = false
		) {
			return $this->module->saveEditedObjectData($objectId, $data, $isNew, $ignorePermissions);
		}

		/**
		 * @deprecated
		 * @see FormSaver::checkAllowedData()
		 */
		public function checkAllowedData(iUmiObjectType $objectType, array $data, $objectId = false) {
			return $this->module->checkAllowedData($objectType, $data, $objectId);
		}

		/**
		 * @deprecated 
		 * @see FormSaver::checkRequiredData()
		 */
		public function checkRequiredData(iUmiObjectType $objectType, $data, $objectId, $isNew) {
			return $this->module->checkRequiredData($objectType, $data, $objectId, $isNew);
		}
	}
