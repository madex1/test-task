<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class UmiNotificationsAdmin implements iModulePart {

		use baseModuleAdmin;
		use tModulePart;

		/** @var string[] список разрешенных типов полей */
		protected $fieldTypeList = [
			'string',
			'boolean',
			'color',
			'date',
			'float',
			'img_file',
			'file',
			'video',
			'int',
			'price',
			'tags',
			'text',
			'relation',
			'optioned',
			'wysiwyg'
		];

		/**
		 * Возвращает список уведомлений
		 * @return bool
		 * @throws Exception
		 */
		public function notifications() {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotJsonMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$mailNotifications = Service::MailNotifications();
			$collectionMap = $mailNotifications->getMap();

			$idField = $collectionMap->get('ID_FIELD_NAME');
			$langIdField = $collectionMap->get('LANG_ID_FIELD_NAME');
			$domainIdField = $collectionMap->get('DOMAIN_ID_FIELD_NAME');
			$nameField = $collectionMap->get('NAME_FIELD_NAME');
			$moduleField = $collectionMap->get('MODULE_FIELD_NAME');

			$result = [];
			$total = 0;
			$limit = (int) getRequest('per_page_limit');
			$limit = ($limit === 0) ? 25 : $limit;
			$currentPage = (int) getRequest('p');
			$offset = $currentPage * $limit;

			$domainId = Service::DomainDetector()->detectId();
			$domainIdList = (array) getRequest('domain_id');

			if ($domainIdList) {
				$domainId = (int) array_shift($domainIdList);
			}

			$langId = Service::LanguageDetector()->detectId();
			$langIdList = (array) getRequest('lang_id');

			if ($langIdList) {
				$langId = (int) array_shift($langIdList);
			}

			try {
				$total = $mailNotifications->count([
					$collectionMap->get('CALCULATE_ONLY_KEY') => true,
					$langIdField => $langId,
					$domainIdField => $domainId
				]);

				if ($total === 0) {
					$this->generateNotificationsForLangAndDomain($langId, $domainId);
				}

				$queryParams = [
					$collectionMap->get('OFFSET_KEY') => $offset,
					$collectionMap->get('LIMIT_KEY') => $limit,
					$collectionMap->get('COUNT_KEY') => true,
					$collectionMap->get('LIKE_MODE_KEY') => [],
					$collectionMap->get('COMPARE_MODE_KEY') => [],
					$moduleField => cmsController::getInstance()
						->getModulesList(),
					$langIdField => $langId,
					$domainIdField => $domainId
				];

				$filtersKey = 'fields_filter';
				$filters = (isset($_REQUEST[$filtersKey]) && is_array($_REQUEST[$filtersKey])) ? $_REQUEST[$filtersKey] : [];

				$fieldNames = [
					$idField,
					$nameField,
					$moduleField
				];

				foreach ($filters as $fieldName => $fieldInfo) {
					if (!in_array($fieldName, $fieldNames)) {
						continue;
					}

					foreach ($fieldInfo as $mode => $value) {
						if ($fieldName === $moduleField) {
							$moduleLabelsToNames = array_flip($this->getModuleNamesToLabels());
							$value = $moduleLabelsToNames[$value];
						}

						if ($value === null || $value === '') {
							continue 2;
						}

						if ($mode == 'like') {
							$queryParams[$collectionMap->get('LIKE_MODE_KEY')][$fieldName] = true;
						} elseif (in_array($mode, ['ge', 'le', 'gt', 'lt', 'eq', 'ne'])) {
							$queryParams[$collectionMap->get('COMPARE_MODE_KEY')][$fieldName] = $mode;
						}

						$queryParams[$fieldName] = $value;
					}
				}

				$orders = (isset($_REQUEST['order_filter']) && is_array($_REQUEST['order_filter'])) ? $_REQUEST['order_filter'] : [];

				if (umiCount($orders) > 0) {
					$queryParams[$collectionMap->get('ORDER_KEY')] = $orders;
				}

				$notificationList = $mailNotifications->export($queryParams);

				foreach ($notificationList as &$notification) {
					$notification[$nameField] = getLabel($notification[$nameField]);
					$notification[$moduleField] = getLabel('module-' . $notification[$moduleField]);
				}

				$result['data'] = $notificationList;
				$total = $mailNotifications->count([
					$collectionMap->get('CALCULATE_ONLY_KEY') => true,
					$langIdField => $langId,
					$domainIdField => $domainId
				]);
			} catch (Exception $e) {
				$result['data']['error'] = $e->getMessage();
			}

			$result['data']['offset'] = $offset;
			$result['data']['per_page_limit'] = $limit;
			$result['data']['total'] = $total;

			$this->module->printJson($result);
		}

		/**
		 * Возвращает данные для создания форм редактирования шаблонов,
		 * которые используются в запрошенном уведомлении.
		 * Если передан $_REQUEST['param1'] = do,
		 * то сохраняет изменения шаблонов и производит перенаправление.
		 * Адрес перенаправления зависит от режима кнопки "Сохранить".
		 * @throws publicAdminException
		 * @throws databaseException
		 * @throws Exception
		 */
		public function edit() {
			$mailNotifications = Service::MailNotifications();
			$notification = $mailNotifications->getById(getRequest('param0'));

			if (!$notification instanceof MailNotification) {
				throw new publicAdminException(getLabel('error-notification-not-found'));
			}

			/** @var MailTemplate[] $templates */
			$templates = $notification->getTemplates();

			if ($this->isSaveMode('param1')) {
				foreach ($templates as $template) {
					$template->setContent(getRequest($template->getName()));
					$template->commit();
				}

				$this->chooseRedirect();
			}

			$data = [
				'notification-label' => getLabel($notification->getName()),
				'mail-templates' => [
					'nodes:mail-template' => []
				]
			];

			foreach ($templates as $template) {
				$data['mail-templates']['nodes:mail-template'][] = [
					'attribute:name' => $template->getName(),
					'attribute:label' => getLabel('mail-template-' . $template->getName(), $notification->getModule()),
					'fields' => $this->getVariableNamesForTemplate($template),
					'content' => $template->getContent(),
					'type' => $template->getType()
				];
			}

			$this->setDataType('form');
			$this->setActionType('modify');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает настройки для формирования табличного контрола
		 * @return array
		 * @throws Exception
		 */
		public function getDatasetConfiguration() {
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'umiNotifications',
						'type' => 'load',
						'name' => 'notifications'
					],
					[
						'title' => getLabel('js-permissions-edit'),
						'module' => 'umiNotifications',
						'type' => 'edit',
						'name' => 'edit'
					],
				],

				'default' => 'name[400px]|module[400px]',

				'fields' => [
					[
						'name' => 'name',
						'title' => getLabel('label-name-field'),
						'type' => 'string',
						'editable' => false,
						'filterable' => false,
						'sortable' => false,
					],
					[
						'name' => 'module',
						'title' => getLabel('label-module-field'),
						'type' => 'relation',
						'multiple' => 'false',
						'options' => implode(',', $this->getModuleNamesToLabels()),
						'editable' => false,
						'sortable' => false,
					]
				]
			];
		}

		/**
		 * Возвращает конфиг модуля в формате JSON для табличного контрола
		 * @throws Exception
		 */
		public function flushDatasetConfiguration() {
			$this->module->printJson($this->getDatasetConfiguration());
		}

		/**
		 * Добавляет переменную в шаблон
		 * @return bool
		 * @throws Exception
		 */
		public function addVariable() {
			$variable = getRequest('variable');
			$templateName = getRequest('template');

			if (!$variable || !$templateName) {
				return false;
			}

			$template = $this->getTemplate($templateName);

			if (!$template->addVariable($variable)) {
				throw new Exception(getLabel('error-variable-already-exists'));
			}

			$template->commit();

			return true;
		}

		/**
		 * Удаляет переменную из шаблона
		 * @return bool
		 * @throws publicAdminException
		 * @throws Exception
		 */
		public function deleteVariable() {
			$variable = getRequest('variable');
			$templateName = getRequest('template');

			if (!$variable || !$templateName) {
				return false;
			}

			$template = $this->getTemplate($templateName);
			$template->deleteVariable($variable);
			$template->commit();

			return true;
		}

		/**
		 * Возвращает информацию о полях шаблона
		 * @throws Exception
		 */
		public function getFieldListInfo() {
			$typeList = $this->getTemplateTypeList(getRequest('param0'));
			$data = [
				'types' => [
					'nodes:type' => $this->getFieldTree($typeList),
				]
			];

			$this->setDataType('form');
			$this->setActionType('modify');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает дерево с информацией о типах и полях
		 * @param array $typeList список типов
		 * @return array
		 */
		protected function getFieldTree($typeList) {
			$tree = [];

			foreach ($typeList as $type) {
				$items = [];

				foreach ($type['fieldList'] as $fieldName => $fieldData) {
					$items[] = [
						'attribute:name' => $fieldName,
						'attribute:title' => $fieldData['title'],
						'nodes:sub-field' => $this->getSubFieldTree($fieldName, $fieldData)
					];
				}

				$tree[] = [
					'attribute:guid' => isset($type['guid']) ? $type['guid'] : '',
					'attribute:title' => isset($type['name']) ? $type['name'] : '',
					'nodes:field' => $items
				];
			}

			return $tree;
		}

		/**
		 * Возвращает дерево с информацией о полях объекта, который содержится в поле
		 * @param string $parentFieldName имя родительского поля
		 * @param array $fieldData информация о поле
		 * @return array
		 */
		protected function getSubFieldTree($parentFieldName, $fieldData) {
			$node = [];

			if (!isset($fieldData['subFields']) || !is_array($fieldData['subFields'])) {
				return $node;
			}

			foreach ($fieldData['subFields'] as $subFieldName => $subFieldData) {
				$fieldName = $parentFieldName . '.' . $subFieldName;
				$node[] = [
					'attribute:name' => $fieldName,
					'attribute:title' => $subFieldData['title'],
					'nodes:sub-field' => $this->getSubFieldTree($fieldName, $subFieldData)
				];
			}

			return $node;
		}


		/**
		 * Список переведенных названий переменных для шаблона уведомления
		 * @param MailTemplate $template
		 * @return array ['variableName' => 'variableLabel', ...]
		 * @throws Exception
		 */
		protected function getVariableNamesForTemplate($template) {
			$config = $this->getTemplateVariableList($template);

			if (isset($config[$template->getName()])) {
				return $config[$template->getName()];
			}

			return [];
		}

		/**
		 * Возвращает модуль шаблона уведомлений
		 * @param MailTemplate $template
		 * @return bool|def_module
		 * @throws RequiredPropertyHasNoValueException
		 * @throws publicAdminException
		 * @throws Exception
		 */
		protected function getModuleByTemplate($template) {
			$moduleName = $template->getModule();
			$module = cmsController::getInstance()
				->getModule($moduleName);

			if (!$module instanceof def_module) {
				$message = getLabel('error-label-module-not-installed', $this->getModuleName(), $moduleName);
				throw new publicAdminException($message);
			}

			return $module;
		}

		/**
		 * Возвращает шаблон по его имени
		 * @param $templateName
		 * @return MailTemplate|null
		 * @throws publicAdminException
		 * @throws Exception
		 */
		protected function getTemplate($templateName) {
			$mailTemplates = Service::MailTemplates();
			$template = $mailTemplates->getByName($templateName);

			if (!$template instanceof MailTemplate) {
				throw new publicAdminException(getLabel('error-template-not-found'));
			}

			return $template;
		}

		/**
		 * Возвращает список типов для добавления полей в административной панели
		 * @param string $templateName имя шаблона
		 * @return array
		 * [
		 * 		0 => [
		 * 			'name' => $type->getName(),
		 * 			'guid' => $type->getGUID(),
		 *			'fieldList' => $this->getTypeFieldList($type)
		 * 		],
		 * ]
		 * @throws RequiredPropertyHasNoValueException
		 * @throws publicAdminException
		 * @throws Exception
		 */
		protected function getTemplateTypeList($templateName) {
			$template = $this->getTemplate($templateName);
			$module = $this->getModuleByTemplate($template);

			$typeNameList = $module->getMailObjectTypesGuidList();
			$typeList = [];

			$variableRelatedTypeList = $template->getVariableForRelatedTypeList();

			foreach ($typeNameList as $guid) {
				$typeList[] = $this->getTemplateType($guid, $variableRelatedTypeList);
			}

			return $typeList;
		}

		/**
		 * Возвращает массив с информацией о типе данных
		 * [
		 *  	'name' => имя типа
		 *  	'guid' => гуид типа
		 *  	'fieldList' => [список полей]
		 * ]
		 * @param string $guid гуид типа данных
		 * @param array $variableRelatedTypeList список связанных типов данных
		 * @return array
		 * @throws coreException
		 */
		protected function getTemplateType($guid, $variableRelatedTypeList) {
			if (array_key_exists($guid, $variableRelatedTypeList)) {
				return $this->getRelatedTypeInfo($guid, $variableRelatedTypeList);
			}

			$type = umiObjectTypesCollection::getInstance()->getTypeByGUID($guid);

			if ($type instanceof iUmiObjectType) {
				return [
					'name' => $type->getName(),
					'guid' => $type->getGUID(),
					'fieldList' => $this->getTypeFieldList($type)
				];
			}

			return [];
		}

		/**
		 * Возвращает информацию о связанных типах
		 * [
		 * 		0 => [
		 *  		'name' => имя типа,
		 *  		'guid' => гуид типа,
		 *  		'fieldList' => [список полей]
		 * 		]
		 * ]
		 * @param string $guid
		 * @param array $variableRelatedTypeList
		 * @return array
		 * @throws coreException
		 */
		protected function getRelatedTypeInfo($guid, $variableRelatedTypeList) {
			$umiObjectTypesCollection = umiObjectTypesCollection::getInstance();
			$typeFieldList = [];

			foreach ($variableRelatedTypeList[$guid] as $typeGuid) {
				$type = $umiObjectTypesCollection->getTypeByGUID($typeGuid);

				foreach ($this->getTypeFieldList($type) as $fieldName => $fieldTitle) {
					$typeFieldList[$typeGuid][$fieldName] = $fieldTitle;
				}
			}

			return [
				'name' => getLabel($guid),
				'guid' => $guid,
				'fieldList' => call_user_func_array('array_intersect_key', $typeFieldList)
			];
		}

		/**
		 * Возвращает отфильтрованный список полей указанного типа
		 * @param iUmiObjectType $type тип данных
		 * @return array
		 * @throws coreException
		 */
		protected function getTypeFieldList(iUmiObjectType $type) {
			$fieldList = $type->getAllFields();

			return $this->filterFieldList($fieldList);
		}

		/**
		 * Фильтрует список полей от полей с запрещенными типами
		 * @param array $fieldList
		 * @return array
		 * @throws coreException
		 */
		protected function filterFieldList($fieldList) {
			$umiFieldTypesCollection = umiFieldTypesCollection::getInstance();
			$umiObjectTypesCollection = umiObjectTypesCollection::getInstance();
			$result = [];

			/** @var iUmiField $field */
			foreach ($fieldList as $field) {
				$typeId = $field->getFieldTypeId();
				$type = $umiFieldTypesCollection->getFieldType($typeId);
				$fieldType = $type->getDataType();

				if ($this->isAllowedFieldType($fieldType)) {
					$subFieldList = [];

					if ($fieldType == 'relation') {
						$objectType = $umiObjectTypesCollection->getType($field->getGuideId());

						if ($objectType instanceof iUmiObjectType) {
							$subFieldList = $this->getTypeFieldList($objectType);
						}
					}

					$result[$field->getName()] = [
						'title' => $field->getTitle(),
						'subFields' => $subFieldList
					];
				}
			}

			return $result;
		}

		/**
		 * Проверяет является ли тип поля разрешенным
		 * @param string $fieldType тип поля
		 * @return bool
		 */
		protected function isAllowedFieldType($fieldType) {
			return in_array($fieldType, $this->fieldTypeList);
		}

		/**
		 * Возвращает список переменных шаблона
		 * @param MailTemplate $template объект шаблона уведомлений
		 * @return array
		 * @throws Exception
		 */
		protected function getTemplateVariableList($template) {
			$variableList = $template->getVariableList();
			$module = $template->getModule();

			$templateVariables = [];
			$variableRelatedTypeList = $template->getVariableForRelatedTypeList();

			foreach ($variableList as $variable) {

				if (preg_match('/^((?!parse\.)[\w\-]+\.[^%]+)/', $variable)) {
					$values = $this->splitVariable($variable, 2);
					$templateVariables[$variable] = $this->getVariableTitle(
						$values[0],
						$variableRelatedTypeList,
						$values[1]
					);
				} elseif (preg_match('/^parse\./', $variable)) {
					$values = $this->splitVariable($variable, 3);
					$templateVariables[$variable] = getLabel("mail-template-variable-{$values[2]}", $module);
				} else {
					$templateVariables[$variable] = getLabel("mail-template-variable-$variable", $module);
				}
			}

			return [
				$template->getName() =>	$templateVariables
			];
		}

		/**
		 * Разбивает переменную
		 * @param string $variable
		 * @param int $limit
		 * @return false|string[]
		 */
		protected function splitVariable($variable, $limit) {
			return preg_split('/\./', $variable, $limit);
		}

		/**
		 * Возвращает имя переменной
		 * @param string $guid гуид типа
		 * @param array $variableRelatedTypeList список переменных связанных типов
		 * @param string $fieldName имя поля
		 * @return bool|string
		 * @throws coreException
		 */
		protected function getVariableTitle($guid, array $variableRelatedTypeList, $fieldName) {

			if (array_key_exists($guid, $variableRelatedTypeList)) {
				return $this->getFieldTitleByTypeGuidList(
					$variableRelatedTypeList[$guid],
					$fieldName
				);
			}

			return $this->getFieldTitle($guid, $fieldName);
		}

		/**
		 * Возвращает имя поля по списку типов гуида
		 * @param string[] $guidList список типов гуида
		 * @param string $fieldName идентификатор поля
		 * @return bool|string
		 * @throws coreException
		 */
		protected function getFieldTitleByTypeGuidList(array $guidList, $fieldName) {
			$fieldTitle = '';

			foreach ($guidList as $guid) {
				$fieldTitle = $this->getFieldTitle($guid, $fieldName);
			}

			return $fieldTitle;
		}

		/**
		 * Возвращает title поля
		 * @param string $typeGuid гуид типа
		 * @param string $fieldName идентификатор поля
		 * @return bool|string
		 * @throws coreException
		 */
		protected function getFieldTitle($typeGuid, $fieldName) {
			$umiObjectsCollection = umiObjectTypesCollection::getInstance();
			$type = $umiObjectsCollection->getTypeByGUID($typeGuid);

			if (!$type instanceof iUmiObjectType) {
				return false;
			}

			$umiFieldsCollection = umiFieldsCollection::getInstance();

			if (contains($fieldName, '.')) {
				list($parentFieldName, $fieldName) = $this->splitVariable($fieldName, 2);
				$fieldId = $type->getFieldId($parentFieldName);
				$field = $umiFieldsCollection->getById($fieldId);

				if (!$field instanceof iUmiField) {
					return false;
				}

				$type = $umiObjectsCollection->getType($field->getGuideId());

				if (!$type instanceof iUmiObjectType) {
					return false;
				}

				return $this->getFieldTitle($type->getGUID(), $fieldName);
			}

			$fieldId = $type->getFieldId($fieldName);
			$field = $umiFieldsCollection->getById($fieldId);

			return ($field instanceof iUmiField) ? $field->getTitle() : false;
		}

		/**
		 * Переведенные имена модулей, в которых используются уведомления
		 * @return array ['moduleName' => 'moduleLabel', ...]
		 * @throws Exception
		 */
		protected function getModuleNamesToLabels() {
			$mailNotifications = Service::MailNotifications();
			$modules = [];

			foreach ($mailNotifications->export() as $notification) {
				$modules[$notification['module']] = getLabel('module-' . $notification['module']);
			}

			return $this->filterNotExistingModules($modules);
		}

		/**
		 * Фильтрует список модулей от неустановленных
		 * @param array $moduleList
		 *
		 * [
		 *      'name' => 'title'
		 * ]
		 *
		 * @return array
		 *
		 * [
		 *      'name' => 'title'
		 * ]
		 */
		protected function filterNotExistingModules(array $moduleList) {
			$existingModuleList = cmsController::getInstance()
				->getModulesList();
			$filteredModuleList = [];

			foreach ($moduleList as $name => $title) {
				if (in_array($name, $existingModuleList)) {
					$filteredModuleList[$name] = $title;
				}
			}

			return $filteredModuleList;
		}

		/**
		 * Создать новые уведомления для связки язык/домен.
		 * Уведомления и их шаблоны будут скопированы из уведомлений и шаблонов языка/домена по умолчанию.
		 * @param int $langId идентификатор языка
		 * @param int $domainId идентификатор домена
		 * @throws Exception
		 */
		protected function generateNotificationsForLangAndDomain($langId, $domainId) {
			$defaultLangId = Service::LanguageCollection()->getDefaultLang()->getId();
			$defaultDomainId = Service::DomainCollection()
				->getDefaultDomain()
				->getId();

			$mailNotifications = Service::MailNotifications();
			$mailNotificationsMap = $mailNotifications->getMap();

			$mailTemplates = Service::MailTemplates();
			$mailTemplatesMap = $mailTemplates->getMap();

			/** @var MailNotification[] $notificationList */
			$notificationList = $mailNotifications->get([
				$mailNotificationsMap->get('LANG_ID_FIELD_NAME') => $defaultLangId,
				$mailNotificationsMap->get('DOMAIN_ID_FIELD_NAME') => $defaultDomainId
			]);

			foreach ($notificationList as $notification) {
				$newNotification = $mailNotifications->create([
					$mailNotificationsMap->get('LANG_ID_FIELD_NAME') => $langId,
					$mailNotificationsMap->get('DOMAIN_ID_FIELD_NAME') => $domainId,
					$mailNotificationsMap->get('NAME_FIELD_NAME') => $notification->getName(),
					$mailNotificationsMap->get('MODULE_FIELD_NAME') => $notification->getModule()
				]);

				/** @var MailTemplate[] $templateList */
				$templateList = $notification->getTemplates();

				foreach ($templateList as $template) {
					$mailTemplates->create([
						$mailTemplatesMap->get('NOTIFICATION_ID_FIELD_NAME') => $newNotification->getId(),
						$mailTemplatesMap->get('NAME_FIELD_NAME') => $template->getName(),
						$mailTemplatesMap->get('TYPE_FIELD_NAME') => $template->getType(),
						$mailTemplatesMap->get('CONTENT_FIELD_NAME') => $template->getContent()
					]);
				}
			}
		}
	}
