<?php

	use UmiCms\Service;
	use UmiCms\System\Orm\Entity\iCollection;
	use UmiCms\System\Interfaces\iYandexTokenInjector;
	use UmiCms\System\Request\Mode\iDetector as ModeDetector;
	use UmiCms\Classes\System\Utils\DataSetConfig\iXmlTranslator;

	/** Трейт базового административного функционала модулей */
	trait baseModuleAdmin {

		protected
			$dataTypes = ['list', 'message', 'form'],
			$actionTypes = ['modify', 'create', 'view'];

		public $limit;

		public $offset;

		public $dataType;

		public $actionType;

		public $total;

		public $data;

		public $currentEditedElementId = false;

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $limit
		 * @param $offset
		 */
		public function setDataRange($limit, $offset = 0) {
			$this->limit = (int) $limit;
			$this->offset = (int) $offset;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $per_page
		 * @param $curr_page
		 */
		public function setDataRangeByPerPage($per_page, $curr_page = 0) {
			$this->setDataRange($per_page, $curr_page * $per_page);
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param string $dataType
		 */
		public function setDataType($dataType) {
			$this->limit = false;
			$this->offset = false;
			$this->dataType = $dataType;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param string $actionType
		 */
		public function setActionType($actionType) {
			$this->actionType = $actionType;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param array $data
		 * @param bool|int $total
		 */
		public function setData($data, $total = false) {
			$this->total = $total;
			$this->data = $data;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 */
		public function doData() {
			$dataSet = [];
			$dataSet['attribute:type'] = $this->dataType;
			$dataSet['attribute:action'] = $this->actionType;

			if ($this->total) {
				$dataSet['attribute:total'] = $this->total;

				if ($this->offset !== null) {
					$dataSet['attribute:offset'] = $this->offset;
				}

				if ($this->limit !== null) {
					$dataSet['attribute:limit'] = $this->limit;
				}
			}

			$data = is_array($this->data) ? $this->data : [];
			$dataSet = array_merge($dataSet, $data);

			cmsController::getInstance()->setAdminDataSet($dataSet);
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param mixed|array|iUmiObject $inputData
		 * @param string $type
		 * @return array
		 * @throws coreException
		 */
		public function prepareData($inputData, $type) {
			$data = [];

			def_module::requireSlashEnding();

			switch ($type) {
				case 'page': {
					$data = $this->prepareDataPage($inputData);
					break;
				}

				case 'pages': {
					$data = $this->prepareDataPages($inputData);
					break;
				}

				case 'object': {
					$data = $this->prepareDataObject($inputData);
					break;
				}

				case 'objects': {
					$data = $this->prepareDataObjects($inputData);
					break;
				}

				case 'type': {
					$data = $this->prepareDataType($inputData);
					break;
				}

				case 'field': {
					$data = $this->prepareDataField($inputData);
					break;
				}

				case 'group': {
					$data = $this->prepareDataGroup($inputData);
					break;
				}

				case 'types': {
					$data = $this->prepareDataTypes($inputData);
					break;
				}

				case 'hierarchy_types': {
					$data = $this->prepareDataHierarchyTypes($inputData);
					break;
				}

				case 'domains': {
					$data = $this->prepareDataDomains($inputData);
					break;
				}

				case 'domain_mirrows': {
					$data = $this->prepareDataDomainMirrows($inputData);
					break;
				}

				case 'templates': {
					$data = $this->prepareDataTemplates($inputData);
					break;
				}

				case 'template': {
					$data = $this->prepareDataTemplate($inputData);
					break;
				}

				case 'settings': {
					$data = $this->prepareDataSettings($inputData);
					break;
				}

				case 'modules': {
					$data = $this->prepareDataModules($inputData);
					break;
				}

				case 'langs': {
					$data = $this->prepareDataLangs($inputData);
					break;
				}

				default: {
					throw new coreException("Data type \"{$type}\" is unknown.");
				}
			}

			return $data;
		}

		/**
		 * Возвращает подготовленные данные о странице
		 * @param array $inputData изначальные данные о странице
		 * @return array $data
		 * @throws wrongElementTypeAdminException
		 * @throws publicAdminException
		 * @throws coreException
		 */
		public function prepareDataPage($inputData) {
			/** @var baseModuleAdmin|def_module $this */
			$element = getArrayKey($inputData, 'element');
			$userId = Service::Auth()
				->getUserId();

			if ($this->systemIsLocked($element, $userId)) {
				throw new wrongElementTypeAdminException(getLabel('error-element-locked'));
			}

			$oEventPoint = new umiEventPoint('sysytemBeginPageEdit');
			$oEventPoint->setMode('before');
			$oEventPoint->setParam('user_id', $userId);
			$oEventPoint->setParam('lock_time', time());
			$oEventPoint->addRef('element', $element);
			def_module::setEventPoint($oEventPoint);

			$cmsController = cmsController::getInstance();
			/** @var data|DataForms $dataModule */
			$dataModule = $cmsController->getModule('data');

			$data = [];
			$page = [];

			if ($this->actionType == 'create') {
				$module = get_class($this->module);

				if (getArrayKey($inputData, 'module')) {
					$module = getArrayKey($inputData, 'module');
				}

				if (!$this->checkAllowedElementType($inputData)) {
					throw new wrongElementTypeAdminException(getLabel('error-unexpected-element-type'));
				}

				$method = $inputData['type'];

				if ($method == 'page' && $module == 'content') {
					$method = '';
				}

				if (is_numeric($method)) {
					$baseTypeId = $typeId = $method;
				} else {
					$baseTypeId =
						$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName($module, $method);
				}

				$langId = Service::LanguageDetector()->detectId();
				$domainId = Service::DomainDetector()->detectId();

				$methodTemplateId = templatesCollection::getInstance()->getHierarchyTypeTemplate($module, $method);
				$parent = $inputData['parent'];

				if ($parent instanceof iUmiHierarchyElement) {
					$parentId = $parent->getId();

					$this->checkDomainPermissions($parent->getDomainId());
					$this->checkElementPermissions($parentId, permissionsCollection::E_CREATE_ALLOWED);

					$cmsController->currentEditElementId = $parentId;

					$dominantTypeId = umiHierarchy::getInstance()->getDominantTypeId($parentId);

					if ($dominantTypeId) {
						$typeId = $dominantTypeId;
					}

					if ($methodTemplateId !== false) {
						$tplId = $methodTemplateId;
					} else {
						$dominantTplId = umiHierarchy::getInstance()->getDominantTplId($parentId);

						if ($dominantTplId) {
							$tplId = $dominantTplId;
						}
					}
				} else {
					$parentId = 0;

					$this->checkDomainPermissions();
					$dominantTypeId = umiHierarchy::getInstance()->getDominantTypeId(0);

					if ($dominantTypeId) {
						$typeId = $dominantTypeId;
					}

					$floatedDomainId = $this->getFloatedDomain();

					if ($floatedDomainId) {
						$domainId = $floatedDomainId;
					}

					if ($methodTemplateId !== false) {
						$tplId = $methodTemplateId;
					} else {
						$defaultTemplate = templatesCollection::getInstance()->getDefaultTemplate($domainId, $langId);

						if ($defaultTemplate instanceof iTemplate) {
							$tplId = $defaultTemplate->getId();
						} else {
							throw new publicAdminException(getLabel('error-require-default-template'));
						}
					}
				}

				if (!$this->compareObjectTypeByHierarchy($module, $method, $typeId)) {
					$typeId = $baseTypeId;
				}

				if (isset($inputData['type_id'])) {
					$typeId = $inputData['type_id'];
				} elseif (isset($inputData['type-id'])) {
					$typeId = $inputData['type-id'];
				}

				if ($typeId > 0) {
					$page['attribute:name'] = '';
					$page['attribute:parentId'] = $parentId;
					$page['attribute:type-id'] = $typeId;
					$page['attribute:tpl-id'] = $tplId;
					$page['attribute:active'] = 'active';
					$page['attribute:domain-id'] = $domainId;
					$page['attribute:language-id'] = $langId;
					$page['basetype'] = umiHierarchyTypesCollection::getInstance()->getTypeByName($module, $method);
					$page['properties'] = $dataModule->getCreateForm($typeId, false, false, true);
				} else {
					throw new coreException('Give me a normal type to create ;)');
				}

				if ($module == 'content' && $method == '') {
					$page['attribute:visible'] = 'visible';
				}
			} else {
				if ($this->actionType == 'modify') {
					if ($inputData instanceof iUmiHierarchyElement) {
						$element = $inputData;
					} else {
						if (is_array($inputData)) {
							$element = $inputData['element'];
						} else {
							throw new coreException('Unknown type of input data');
						}
					}

					if (!$this->checkAllowedElementType($inputData)) {
						throw new wrongElementTypeAdminException(getLabel('error-unexpected-element-type'));
					}

					$this->checkDomainPermissions($element->getDomainId());
					$this->checkElementPermissions($element->getId());

					$cmsController->currentEditElementId = $element->getId();

					$umiHierarchy = umiHierarchy::getInstance();

					$pageDomainId = $element->getDomainId();
					$pageCopies = [];
					$copies = $umiHierarchy->getObjectInstances($element->getObjectId(), true, true, true);
					$domainCollection = Service::DomainCollection();

					foreach ($copies as $copyId) {
						$parents = $umiHierarchy->getAllParents($copyId);
						$copy = $umiHierarchy->getElement($copyId);
						$copyDomainId = $copy->getDomainId();
						$copyDomainName = $domainCollection->getDomain($copyDomainId)->getHost();

						$treeStateLink = '{0}';

						foreach ($parents as $key => $parentId) {
							if ($parentId == 0) {
								if ($pageDomainId != $copyDomainId) {
									$module = 'content';
									$method = 'sitetree';
									$settingsKey = 'tree-content-sitetree-' . $copyDomainId;
									$parents[$key] = [
										'@id' => $copyDomainId,
										'@parentId' => $copyDomainId,
										'@name' => $copyDomainName,
										'@treeLink' => $treeStateLink,
										'@module' => $module,
										'@method' => $method,
										'@settingsKey' => $settingsKey,
									];
								} else {
									unset($parents[$key]);
								}

								continue;
							}
							if (!$parentPage = $umiHierarchy->getElement($parentId)) {
								continue;
							}

							$treeStateLink .= '{' . $parentPage->getId() . '}';

							$module = $parentPage->getHierarchyType()->getModule();
							$method = Service::Registry()->get('//modules/' . $module . '/default_method_admin');
							$settingsKey = 'tree-' . $module . '-' . $method;

							if ($module == 'content') {
								$settingsKey .= '-' . $copyDomainId;
							}

							$parents[$key] = [
								'@id' => $parentPage->getId(),
								'@parentId' => $parentPage->getParentId(),
								'@name' => $parentPage->getName(),
								'@url' => $umiHierarchy->getPathById($parentPage->getId()),
								'@treeLink' => $treeStateLink,
								'@module' => $module,
								'@method' => $method,
								'@settingsKey' => $settingsKey,
							];
						}

						$editLink = false;

						/** @var CatalogAdmin|UsersAdmin|NewsAdmin|FaqAdmin|ForumAdmin|EmarketAdmin|Blogs20Admin|ForumAdmin $moduleInstance */
						$moduleInstance = $cmsController->getModule($copy->getModule());

						if ($moduleInstance) {
							$links = $moduleInstance->getEditLink($copyId, $copy->getMethod());

							if (is_array($links) && $links[1]) {
								$editLink = $links[1];
							}
						}

						$pageNamePostfix = '';

						if (umiCount($copies) > 1) {
							$pageNamePostfixConstant = $copy->isOriginal() ? 'js-smc-original' : 'js-smc-virtual-copy';
							$pageNamePostfix = getLabel($pageNamePostfixConstant);
						}

						$pageCopy = [
							'@id' => $copyId,
							'@name' => $copy->getName() . $pageNamePostfix,
							'@edit-link' => $editLink,
							'@url' => $umiHierarchy->getPathById($copyId),
							'@domain' => $copyDomainName,
							'@domain-id' => $copyDomainId,
							'basetype' => $copy->getHierarchyType(),
							'parents' => ['nodes:item' => $parents]
						];

						if ($copyId == $element->getId()) {
							array_unshift($pageCopies, $pageCopy);
						} else {
							$pageCopies[] = $pageCopy;
						}
					}

					$objectId = $element->getObject()->getId();
					
					$page['copies'] = ['nodes:copy' => $pageCopies];
					$page['attribute:id'] = $element->getId();
					$page['attribute:parentId'] = $element->getParentId();
					$page['attribute:object-id'] = $objectId;
					$page['attribute:guid'] = $element->getObject()->getGUID();
					$page['attribute:type-id'] = $element->getObject()->getTypeId();
					$page['attribute:type-guid'] = $element->getObject()->getTypeGUID();
					$page['attribute:alt-name'] = $element->getAltName();
					$page['attribute:tpl-id'] = $element->getTplId();
					$page['attribute:domain-id'] = $element->getDomainId();
					$page['attribute:language-id'] = $element->getLangId();

					if ($element->getIsActive()) {
						$page['attribute:active'] = 'active';
					}

					if ($element->getIsVisible()) {
						$page['attribute:visible'] = 'visible';
					}

					if ($element->getIsDefault()) {
						$page['attribute:default'] = 'default';
					}

					$page['basetype'] = $element->getHierarchyType();

					$page['name'] = $element->getName();

					$page['properties'] =
						$dataModule->getEditFormWithIgnorePermissions($objectId, false, false, true, true);
				}
			}

			$data['page'] = $page;
			return $data;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 */
		public function prepareDataPages($inputData) {
			$data = [];
			$hierarchy = umiHierarchy::getInstance();
			$pages = [];
			$sz = umiCount($inputData);
			for ($i = 0; $i < $sz; $i++) {
				$element = $inputData[$i];
				if (is_numeric($element)) {
					$element = $hierarchy->getElement($element, false, true);
				}

				if ($element instanceof iUmiHierarchyElement) {
					if (getRequest('viewMode') == 'full') {
						$pages[] = ['full:' => $element];
					} else {
						$pages[] = $element;
						$hierarchy->unloadElement($element->getId());
					}
				}
			}

			$data['nodes:page'] = $pages;

			return $data;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 */
		public function prepareDataObjects($inputData) {
			$data = [];

			$objectsCollection = umiObjectsCollection::getInstance();
			$objects = [];

			foreach ($inputData as $object) {
				if (is_numeric($object)) {
					$object = $objectsCollection->getObject($object);
				}

				if ($object instanceof iUmiObject) {
					if (getRequest('viewMode') == 'full') {
						$objects[] = ['full:' => $object];
					} else {
						$objects[] = $object;
						$objectsCollection->unloadObject($object->getId());
					}
				}
			}
			$data['nodes:object'] = $objects;

			return $data;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 * @throws wrongElementTypeAdminException
		 * @throws publicAdminException
		 */
		public function prepareDataObject($inputData) {
			/** @var baseModuleAdmin|def_module $this */
			$data = [];

			/** @var data|DataForms $dataModule */
			$dataModule = cmsController::getInstance()->getModule('data');

			if (!$this->checkAllowedElementType($inputData)) {
				throw new wrongElementTypeAdminException(getLabel('error-unexpected-element-type'));
			}

			$object = [];
			if ($this->actionType == 'create') {
				$typeId = false;
				$module = get_class($this->module);
				$method = getArrayKey($inputData, 'type');

				if ($module && $method) {
					$typeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName($module, $method);
				}

				if (isset($inputData['type-id'])) {
					$typeId = $inputData['type-id'];
				}

				if (!$typeId) {
					throw new publicAdminException('Object type id is required to create new object');
				}

				$object['attribute:type-id'] = $typeId;
				$object['properties'] = $dataModule->getCreateForm($typeId, false, false, true);
			} else {
				if (!$inputData instanceof iUmiObject) {
					if (is_object($inputData = getArrayKey($inputData, 'object')) === false) {
						throw new publicAdminException(getLabel('error-expect-object'));
					}
				}

				$eventPoint = new umiEventPoint('sysytemBeginObjectEdit');
				$eventPoint->setMode('before');
				$eventPoint->addRef('object', $inputData);
				$eventPoint->call();

				/** @var iUmiObject $inputData */
				$object['attribute:id'] = $inputData->getId();
				$object['attribute:name'] = $inputData->getName();
				$object['attribute:guid'] = $inputData->getGUID();
				$object['attribute:type-id'] = $inputData->getTypeId();
				$object['attribute:type-guid'] = $inputData->getTypeGUID();
				$object['attribute:owner-id'] = $inputData->getOwnerId();
				$object['properties'] =
					$dataModule->getEditFormWithIgnorePermissions($inputData->getId(), false, false, true, true);
			}

			$data['object'] = $object;

			return $data;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 */
		public function prepareDataType($inputData) {
			$data = [];
			$data['full:type'] = $inputData;
			xmlTranslator::$showHiddenFieldGroups = true;
			return $data;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 */
		public function prepareDataField($inputData) {
			$data = [];

			if ($this->actionType == 'create') {
				$field = [];
				$field['attribute:visible'] = 'visible';
				$data['field'] = $field;
			} else {
				$data['full:field'] = $inputData;
			}
			return $data;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 * @throws coreException
		 */
		public function prepareDataGroup($inputData) {
			$data = [];

			if ($this->actionType == 'create') {
				$group_arr = [];
				$group_arr['attribute:visible'] = true;
				$data['group'] = $group_arr;
			} else {
				if ($inputData instanceof iUmiFieldsGroup) {
					$data['group'] = $inputData;
				} else {
					throw new coreException('Expected instance of umiFieldsGroup');
				}
			}
			return $data;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 */
		public function prepareDataTypes($inputData) {
			$data = [];

			$typesCollection = umiObjectTypesCollection::getInstance();
			$types = [];
			$sz = umiCount($inputData);
			for ($i = 0; $i < $sz; $i++) {
				$type_id = $inputData[$i];
				$type = $typesCollection->getType($type_id);
				if ($type instanceof iUmiObjectType) {
					$types[] = $type;
				}
			}
			$data['nodes:type'] = $types;
			return $data;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 */
		public function prepareDataHierarchyTypes($inputData) {
			$data = [];

			$typesCollection = umiHierarchyTypesCollection::getInstance();
			$types = [];

			foreach ($inputData as $item) {
				if ($item instanceof iUmiHierarchyType) {
					$types[] = $item;
				} else {
					$type_id = $item;
					$type = $typesCollection->getType($type_id);

					if ($type instanceof iUmiHierarchyType) {
						$types[] = $type;
					}
				}
			}
			$data['nodes:basetype'] = $types;
			return $data;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 */
		public function prepareDataDomains($inputData) {
			$data = [];

			$domains = [];
			foreach ($inputData as $item) {
				$domains[] = $item;
			}
			$data['nodes:domain'] = $domains;
			return $data;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 */
		public function prepareDataDomainMirrows($inputData) {
			$data = [];

			$domains = [];
			foreach ($inputData as $item) {
				$domains[] = $item;
			}
			$data['nodes:domainMirrow'] = $domains;
			return $data;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 */
		public function prepareDataTemplates($inputData) {
			$data = [];
			$domainsCollection = Service::DomainCollection();

			$domains = [];
			foreach ($inputData as $host => $templates) {
				$domain = [];
				$domain['attribute:id'] = $domainsCollection->getDomainId($host);
				$domain['attribute:host'] = $host;
				$domain['nodes:template'] = $templates;
				$domains[] = $domain;
			}
			$data['nodes:domain'] = $domains;
			return $data;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param iTemplate $template
		 */
		public function prepareDataTemplate(iTemplate $template) {
			$hierarchy = umiHierarchy::getInstance();

			$data = [];
			$info = [];
			$info['attribute:id'] = $template->getId();
			$info['attribute:name'] = $template->getName();
			$info['attribute:title'] = $template->getTitle();
			$info['attribute:filename'] = $template->getFilename();
			$info['attribute:type'] = $template->getType();
			$info['attribute:lang-id'] = $template->getLangId();
			$info['attribute:domain-id'] = $template->getDomainId();

			$used_pages = $template->getUsedPages();

			$pages = [];
			$hierarchyTypes = [];
			foreach ($used_pages as $element_info) {
				$element = $hierarchy->getElement($element_info[0]);
				if ($element instanceof iUmiHierarchyElement) {
					$element_id = $element->getId();
					$page_arr['attribute:id'] = $element_id;
					$page_arr['xlink:href'] = 'upage://' . $element_id;
					$elementTypeId = $element->getTypeId();
					if (!isset($hierarchyTypes[$elementTypeId])) {
						$hierarchyTypes[$elementTypeId] = selector::get('hierarchy-type')->id($elementTypeId);
					}
					$page_arr['basetype'] = $hierarchyTypes[$elementTypeId];
					$page_arr['name'] = str_replace('"', "\\\"", $element->getName());
					$pages[] = $page_arr;
				}
				$hierarchy->unloadElement($element_info[0]);
			}
			$info['used-pages']['nodes:page'] = $pages;
			$data['template'] = $info;
			return $data;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 */
		public function prepareDataSettings($inputData) {
			$data = [];
			$data['nodes:group'] = [];

			foreach ($inputData as $group_name => $params) {
				if (!is_array($params)) {
					continue;
				}

				$group = [];
				$group['attribute:name'] = $group_name;
				$group['attribute:label'] = getLabel('group-' . $group_name);

				$options = [];
				foreach ($params as $param_key => $param_value) {
					$param_name = def_module::getRealKey($param_key);
					$param_type = def_module::getRealKey($param_key, true);

					$option = [];
					$option['attribute:name'] = $param_name;
					$option['attribute:type'] = $param_type;
					$option['attribute:label'] = getLabel('option-' . $param_name);

					switch ($param_type) {
						case 'select': {
							$items = [];
							$value = isset($param_value['value']) ? $param_value['value'] : false;
							foreach ($param_value as $item_id => $item_name) {
								if ($item_id === 'value') {
									continue;
								}

								$item_arr = [];
								$item_arr['attribute:id'] = $item_id;
								$item_arr['node:name'] = $item_name;
								$items[] = $item_arr;
							}
							$option['value'] = ['nodes:item' => $items];

							if ($value !== false) {
								$option['value']['attribute:id'] = $value;
							}
							break;
						}

						case 'password': {
							if ($param_value) {
								$param_value = '********';
							} else {
								$param_value = '';
							}

							break;
						}

						case 'symlink': {
							$hierarchy = umiHierarchy::getInstance();

							$param_value = @unserialize($param_value);
							if (!is_array($param_value)) {
								$param_value = [];
							}
							$items = [];
							foreach ($param_value as $item_id) {
								$item = $hierarchy->getElement($item_id);
								if (!$item instanceof iUmiHierarchyElement) {
									continue;
								}

								$item_arr = [];
								$item_arr['attribute:id'] = $item_id;
								$item_arr['node:name'] = $item->getName();
								$items[] = $item_arr;
							}
							$option['value'] = ['nodes:item' => $items];
							break;
						}

						default: {
							$option['value'] = $param_value;
							break;
						}
					}

					$options[] = $option;
				}

				$group['nodes:option'] = $options;
				$data['nodes:group'][] = $group;
			}
			return $data;
		}

		/**
		 * Подготавливает список модулей к выводу в административной панели
		 * @param string[] $inputData обрабатываемые данные,
		 * содержащие список строковых идентификаторов модулей
		 * @return array
		 */
		public function prepareDataModules($inputData) {
			$moduleNameList = array_values($inputData);
			$nodeList = [];

			foreach ($moduleNameList as $moduleName) {
				$label = getLabel('module-' . $moduleName);
				$nodeList[$label] = [
					'attribute:label' => $label,
					'node:module' => $moduleName
				];
			}

			ksort($nodeList);

			return [
				'nodes:module' => array_values($nodeList)
			];
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 */
		public function prepareDataLangs($inputData) {
			$data = [];

			$langs = [];

			foreach ($inputData as $lang) {
				$lang_arr = [];
				$lang_arr['attribute:id'] = $lang->getId();
				$lang_arr['attribute:title'] = $lang->getTitle();
				$lang_arr['attribute:prefix'] = $lang->getPrefix();
				$langs[] = $lang_arr;
			}

			$data['nodes:lang'] = $langs;
			return $data;
		}

		/**
		 * Алиас expectedParams()
		 * @param $params
		 * @return mixed
		 * @throws requireAdminParamException
		 * @throws wrongParamException
		 */
		public function expectParams($params) {
			return self::expectedParams($params);
		}

		/**
		 * Нормализует и фильтрует параметры
		 * @param array $params параметры
		 * @return mixed
		 * @throws requireAdminParamException
		 * @throws wrongParamException
		 */
		public static function expectedParams($params) {
			foreach ($params as $group_key => $group) {
				foreach ($group as $param_key => $param) {
					$param_name = def_module::getRealKey($param_key);
					$param_type = def_module::getRealKey($param_key, true);
					$params[$group_key][$param_key] = self::getExpectParam($param_name, $param_type, $param);
				}
			}
			return $params;
		}

		/**
		 * Нормализует и фильтрует значение параметра
		 * @param string $param_name имя параметра
		 * @param string $param_type тип параметра
		 * @param mixed $param значение параметра
		 * @return mixed
		 * @throws wrongParamException
		 * @throws requireAdminParamException
		 * @throws coreException
		 */
		public static function getExpectParam($param_name, $param_type, $param = null) {
			global $_FILES;

			$value = getRequest($param_name);

			if ($param_type == 'status') {
				return null;
			}

			if ($value === null && !in_array($param_type, ['file', 'weak_guide', 'select-multi'])) {
				throw new requireAdminParamException('I expect value in request for param "' . $param_name . '"');
			}

			switch ($param_type) {
				case 'ufloat':
				case 'float': {
					return (float) $value;
				}

				case 'bool':
				case 'boolean':
				case 'templates':
				case 'guide':
				case 'weak_guide':
				case 'int': {
					return (int) $value;
				}

				case 'password': {
					$value = ($value == '********') ? null : (string) $value;
					if ($value) {
						try {
							$oOpenSSL = new umiOpenSSL();
							$bFilesOk = $oOpenSSL->supplyDefaultKeyFiles();
							if ($bFilesOk) {
								$value = 'umipwd_b64::' . base64_encode($oOpenSSL->encrypt($value));
							} else {
								$value = null;
							}
						} catch (publicException $e) {
							$value = null;
						}
					}
					return $value;
				}

				case 'smtp-password': {
					return ($value == '********') ? null : (string) $value;
				}

				case 'email':
				case 'status':
				case 'mail-template':
				case 'string': {
					return (string) $value;
				}

				case 'symlink': {
					return serialize($value);
				}

				case 'file': {
					$destination_folder = $param['destination-folder'];
					$group = isset($param['group']) ? $param['group'] : 'pics';

					$value = umiFile::upload($group, $param_name, $destination_folder);

					if ($value) {
						return $value;
					}

					$path = $destination_folder . getRequest('select_' . $param_name);
					return Service::FileFactory()->create($path);
					break;
				}

				case 'relation':
				case 'wysiwyg':
				case 'select': {
					return $value;
					break;
				}

				case 'select-multi': {
					return (is_array($value) && umiCount($value) > 0)
						? implode(umiObjectPropertyRelation::DELIMITER_ID, $value)
						: '';
					break;
				}

				default: {
					throw new wrongParamException("I don't expect param \"" . $param_type . '"');
				}
			}
		}

		/**
		 * Алиас getExpectParam()
		 * @param string $param_name имя параметра
		 * @param string $param_type тип параметра
		 * @param mixed $param значение параметра
		 * @return mixed
		 * @throws requireAdminParamException
		 * @throws wrongParamException
		 */
		public function getExpectedParam($param_name, $param_type, $param = null) {
			return self::getExpectParam($param_name, $param_type, $param);
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $var
		 * @param $strict
		 * @param $byValue
		 * @param $ignoreDeleted
		 * @return bool|iUmiHierarchyElement
		 * @throws expectElementException
		 */
		public function expectElement($var, $strict = false, $byValue = false, $ignoreDeleted = false) {
			$element_id = $byValue ? $var : getRequest($var);
			$element = umiHierarchy::getInstance()->getElement((int) $element_id, false, $ignoreDeleted);

			if ($element instanceof iUmiHierarchyElement) {
				return $element;
			}

			if ($strict) {
				throw new expectElementException(getLabel('error-expect-element'));
			}

			return false;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $var
		 * @param $strict
		 * @param $byValue
		 * @throws expectObjectException
		 */
		public function expectObject($var, $strict = false, $byValue = false) {
			$object_id = $byValue ? $var : (int) getRequest($var);
			$object = umiObjectsCollection::getInstance()->getObject($object_id);

			if ($object instanceof iUmiObject) {
				return $object;
			}

			if ($strict) {
				throw new expectObjectException(getLabel('error-expect-object'));
			}

			return false;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $var
		 * @param $strict
		 * @throws expectElementException
		 */
		public function expectElementId($var, $strict = false) {
			$element_id = (int) getRequest($var);

			if ($element_id === 0 || umiHierarchy::getInstance()->isExists($element_id)) {
				return $element_id;
			}

			if ($strict) {
				throw new expectElementException(getLabel('error-expect-element'));
			}

			return false;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $var
		 * @param $strict
		 * @throws expectObjectException
		 */
		public function expectObjectId($var, $strict = false) {
			$object_id = (int) getRequest($var);

			if ($object_id === 0 || umiObjectsCollection::getInstance()->isExists($object_id)) {
				return $object_id;
			}

			if ($strict) {
				throw new expectObjectException(getLabel('error-expect-object'));
			}

			return false;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param string $var
		 * @param bool $strict
		 * @return bool|\umiObjectType
		 * @throws expectObjectTypeException
		 */
		public function expectObjectType($var, $strict = false) {
			$object_type_id = (int) getRequest($var);
			return umiObjectTypesCollection::getInstance()->getType($object_type_id);
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $var
		 * @param $strict
		 * @param $byValue
		 * @throws expectObjectTypeException
		 */
		public function expectObjectTypeId($var, $strict = false, $byValue = false) {
			$object_type_id = (int) getRequest($var);
			if ($byValue) {
				$object_type_id = $var;
			}

			$objectTypes = umiObjectTypesCollection::getInstance();
			if ($object_type_id === 0 || $objectTypes->getType($object_type_id)) {
				return $object_type_id;
			}

			if ($strict) {
				throw new expectObjectTypeException(getLabel('error-expect-object-type'));
			}

			return false;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 * @throws wrongElementTypeAdminException
		 * @throws expectElementException
		 */
		public function saveEditedElementData($inputData) {
			if (!$this->checkAllowedElementType($inputData)) {
				throw new wrongElementTypeAdminException(getLabel('error-unexpected-element-type'));
			}

			/* @var iUmiHierarchyElement $element */
			$element = getArrayKey($inputData, 'element');
			$user_id = Service::Auth()
				->getUserId();
			$event = new umiEventPoint('systemModifyElement');
			$event->addRef('element', $element);
			$event->addRef('inputData', $inputData);
			$event->setParam('user_id', $user_id);
			$event->setMode('before');
			$event->call();

			if ($element instanceof iUmiHierarchyElement === false) {
				throw new expectElementException(getLabel('error-expect-element'));
			}

			$this->checkDomainPermissions($element->getDomainId());
			$this->checkElementPermissions($element->getId());

			if (is_string(getRequest('alt-name'))) {
				$alt_name = getRequest('alt-name') !== '' ? getRequest('alt-name') : getRequest('name');
				$element->setAltName($alt_name);
			}

			if (($is_active = getRequest('active')) !== null) {
				$permissions = permissionsCollection::getInstance();
				$user_id = Service::Auth()
					->getUserId();

				if ($permissions->isAllowedMethod($user_id, $element->getModule(), 'publish')) {
					$element->setIsActive($is_active);
				}
			}

			if (($is_visible = getRequest('is-visible')) !== null) {
				$element->setIsVisible($is_visible);
			}

			if (($is_default = getRequest('is-default')) !== null) {
				$element->setIsDefault($is_default);
			}

			if (($tpl_id = getRequest('template-id')) !== null) {
				$element->setTplId($tpl_id);
			}

			$users = cmsController::getInstance()->getModule('users');
			if ($users instanceof users) {
				$users->setPerms($element->getId());
			}

			backupModel::getInstance()->save($element->getId());

			$object = $element->getObject();

			if ($object instanceof iUmiObject) {
				$this->saveEditedObjectData($object);
			}

			$objectUpdateTime = $object->getUpdateTime();

			if ($objectUpdateTime > $element->getUpdateTime()) {
				$element->setUpdateTime($objectUpdateTime);
			}

			$element->commit();

			$this->currentEditedElementId = $element->getId();

			$event->setMode('after');
			$event->call();

			return $element;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 * @throws wrongElementTypeAdminException
		 * @throws coreException
		 */
		public function saveAddedElementData($inputData) {
			$cmsController = cmsController::getInstance();
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$templates = templatesCollection::getInstance();

			$module = get_class($this->module);
			if (isset($inputData['module'])) {
				$module = $inputData['module'];
			}
			$method = $inputData['type'];
			$parent = $inputData['parent'];

			if (!$this->checkAllowedElementType($inputData)) {
				throw new wrongElementTypeAdminException(getLabel('error-unexpected-element-type'));
			}

			if ($module == 'content' && $method == 'page') {
				$method = '';
			}

			if ($parent) {
				$this->checkElementPermissions($parent->getId(), permissionsCollection::E_CREATE_ALLOWED);
			}

			$inputData['type-id'] = getArrayKey($inputData, 'type-id') ?: getRequest('type-id');

			$event = new umiEventPoint('systemCreateElement');
			$event->addRef('inputData', $inputData);
			$event->setMode('before');
			$event->call();

			$methodTemplateId = $templates->getHierarchyTypeTemplate($module, $method);
			if ($parent instanceof iUmiHierarchyElement) {
				$parent_id = $parent->getId();
				$lang_id = $parent->getLangId();
				$domain_id = $parent->getDomainId();

				if ($methodTemplateId !== false) {
					$tpl_id = $methodTemplateId;
				} else {
					$dominant_tpl_id = umiHierarchy::getInstance()->getDominantTplId($parent_id);
					if ($dominant_tpl_id) {
						$tpl_id = $dominant_tpl_id;
					} else {
						throw new coreException(getLabel('error-dominant-template-not-found'));
					}
				}
			} else {
				$parent_id = 0;
				$lang_id = Service::LanguageDetector()->detectId();
				$domain_id = Service::DomainDetector()->detectId();

				$floated_domain_id = $this->getFloatedDomain();
				if ($floated_domain_id) {
					$domain_id = $floated_domain_id;
				}

				if ($methodTemplateId !== false) {
					$tpl_id = $methodTemplateId;
				} else {
					$tpl_id = $templates->getDefaultTemplate()->getId();
				}
			}

			$this->checkDomainPermissions($domain_id);

			if (getRequest('template-id')) {
				$tpl_id = getRequest('template-id');
			}

			$hierarchy_type = $hierarchyTypes->getTypeByName($module, $method);

			if ($hierarchy_type instanceof iUmiHierarchyType) {
				$hierarchy_type_id = $hierarchy_type->getId();
			} else {
				throw new coreException(getLabel('error-element-type-detect-failed'));
			}

			if (($name = getRequest('name')) === null) {
				throw new coreException(getLabel('error-require-name-param'));
			}

			if (($alt_name = getRequest('alt-name')) === null) {
				$alt_name = $name;
			}

			$type_id = getArrayKey($inputData, 'type-id');

			if (!$type_id && !($type_id = getRequest('type-id'))) {
				$type_id = $objectTypes->getTypeIdByHierarchyTypeName($module, $method);

				if ($parent instanceof iUmiHierarchyElement) {
					$dominant_type_id = $hierarchy->getDominantTypeId($parent->getId(), 1, $hierarchy_type_id);
					if ($dominant_type_id) {
						$type_id = $dominant_type_id;
					}
				}
			}

			if (!$type_id) {
				throw new coreException("Base type for {$module}::{$method} doesn't exist");
			}

			$element_id = $hierarchy->addElement(
				$parent_id,
				$hierarchy_type_id,
				$name,
				$alt_name,
				$type_id,
				$domain_id,
				$lang_id,
				$tpl_id
			);

			$users = $cmsController->getModule('users');
			if ($users instanceof users) {
				backupModel::getInstance()->save($element_id);
				$users->setPerms($element_id);
			}

			$element = $hierarchy->getElement($element_id);

			if ($element instanceof iUmiHierarchyElement) {
				$module_name = $element->getModule();
				$method_name = $element->getMethod();

				if (($is_active = getRequest('active')) !== null) {
					$permissions = permissionsCollection::getInstance();
					$user_id = Service::Auth()
						->getUserId();
					if (!$permissions->isAllowedMethod($user_id, $cmsController->getCurrentModule(), 'publish')) {
						$is_active = false;
					}

					$element->setIsActive($is_active);
				}

				if (($is_visible = getRequest('is-visible')) !== null) {
					$element->setIsVisible($is_visible);
				}

				if (($tpl_id = getRequest('template-id')) !== null) {
					$element->setTplId($tpl_id);
				}

				if (($is_default = getRequest('is-default')) !== null) {
					$element->setIsDefault($is_default);
				}

				if (($name = getRequest('name')) !== null) {
					$element->setValue('h1', $name);
				}

				$object = $element->getObject();

				$this->saveAddedObject($object);

				$element->commit();
				$newObject = $element->getObject();
				//Set up "publish" status to new page
				if (!$newObject->getValue('publish_status')) {
					$newObject->setValue('publish_status', $this->getPageStatusIdByStatusSid());
					$newObject->commit();
				}
				$event_after = new umiEventPoint('systemCreateElement');
				$event_after->addRef('element', $element);
				$event_after->setMode('after');
				$event_after->call();

				$this->currentEditedElementId = $element_id;
				return $element_id;
			}

			throw new coreException("Can't get created element instance");
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param array $inputData
		 * @throws coreException
		 */
		public function saveEditedObjectData($inputData) {
			if (is_array($inputData)) {
				$object = getArrayKey($inputData, 'object');
			} else {
				$object = $inputData;
			}

			if ($object instanceof iUmiObject === false) {
				throw new coreException('Expected instance of umiObject in param');
			}
			/** @var iUmiObject $object */

			if (is_array($inputData)) {
				$this->setRequestDataAliases(getArrayKey($inputData, 'aliases'), $object->getId());
			}

			$event = new umiEventPoint('systemModifyObject');
			$event->addRef('object', $object);
			$event->setMode('before');
			$event->call();

			if (($name = getRequest('name')) !== null) {
				$object->setName($name);
				$object->setValue('nazvanie', $name);
			}

			if (($type_id = getRequest('type-id')) !== null) {
				$object->setTypeId($type_id);
			}

			/** @var data|DataForms $dataModule */
			$dataModule = cmsController::getInstance()->getModule('data');
			$dataModule->saveEditedObjectWithIgnorePermissions($object->getId(), false, true, true);

			$object->commit();

			$event->setMode('after');
			$event->call();

			return $object;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param iUmiObject $object
		 */
		public function saveAddedObject(iUmiObject $object) {
			$event = new umiEventPoint('systemCreateObject');
			$event->addRef('object', $object);
			$event->setMode('before');
			$event->call();

			/** @var data|DataForms $dataModule */
			$dataModule = cmsController::getInstance()->getModule('data');
			$dataModule->saveEditedObjectWithIgnorePermissions($object->getId(), true, true, true);

			if (($name = getRequest('name')) !== null) {
				$object->setValue('nazvanie', $object->getName());
			}

			$object->commit();

			$event->setMode('after');
			$event->call();

			return $object->getId();
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 * @throws wrongElementTypeAdminException
		 * @throws publicAdminException
		 * @throws coreException
		 */
		public function saveAddedObjectData($inputData) {
			$objectsCollection = umiObjectsCollection::getInstance();
			$typesCollection = umiObjectTypesCollection::getInstance();

			if (!$this->checkAllowedElementType($inputData)) {
				throw new wrongElementTypeAdminException(getLabel('error-unexpected-element-type'));
			}

			$this->setRequestDataAliases(getArrayKey($inputData, 'aliases'));

			if (($name = getArrayKey($inputData, 'name')) === null) {
				$name = getRequest('name');
			}

			if ($name === null) {
				throw new publicAdminException("Require 'name' param in _REQUEST array.");
			}

			$module = get_class($this->module);
			$method = getArrayKey($inputData, 'type');
			$typeId = getArrayKey($inputData, 'type-id');

			if (!$typeId) {
				$typeId = $typesCollection->getTypeIdByHierarchyTypeName($module, $method);
			}

			$objectId = $objectsCollection->addObject($name, $typeId);
			$object = $objectsCollection->getObject($objectId);
			if ($object instanceof iUmiObject) {
				$this->saveAddedObject($object);
				return $object;
			}

			throw new coreException("Can't create object #{$objectId} \"{$name}\" of type #{$typeId}");
		}

		/**
		 * Запускает сохранение изменения списка сущностей
		 * @param string $type тип сущностей
		 * @param mixed $params параметры сохранения
		 * @return bool|int|void
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function saveEditedList($type, $params = false) {
			$data = getRequest('data');
			$dels = getRequest('dels');

			switch ($type) {
				case 'objects': {
					return $this->saveEditedObjectsList($data, $dels, $params);
				}

				case 'basetypes': {
					return $this->saveEditedBaseTypesList($data, $dels);
				}

				case 'domains': {
					return $this->saveEditedDomains($data, $dels);
				}

				case 'domain_mirrows': {
					return $this->saveEditedDomainMirrows($data, $dels);
				}

				case 'langs': {
					return $this->saveEditedLangs($data, $dels);
				}

				case 'templates': {
					return $this->saveEditedTemplatesList($data, $dels, $params);
				}

				default: {
					throw new coreException("Can't save edited list of type \"{$type}\"");
				}
			}
		}

		/**
		 * Сохраняет изменения списка объектов.
		 * Возвращает идентификатор последнего добавленного объекта
		 * @param array $data данные объектов
		 * @param array $dels идентификаторы объектов, которые нужно удалить
		 * @param array $params параметры типа объекта
		 * @return bool|int
		 * @throws coreException
		 */
		public function saveEditedObjectsList($data, $dels, $params) {
			$collection = umiObjectsCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();
			$new_item_id = false;
			if (is_array($data)) {
				foreach ($data as $id => $info) {
					$name = getArrayKey($info, 'name');
					$type_id = getArrayKey($params, 'type_id');
					$method = getArrayKey($params, 'type');

					if (!$type_id && $method) {
						$type_id = $objectTypes->getTypeIdByHierarchyTypeName(get_class($this->module), $method);
					}

					if ($id == 'new') {
						if ($name && $type_id) {
							$id = $collection->addObject($name, $type_id);
							$item = $collection->getObject($id);
							if ($item instanceof iUmiObject) {
								$new_item_id = $this->saveAddedObject($item);
								$item->commit();
							}
						}
					} else {
						$item = $collection->getObject($id);

						if ($item instanceof iUmiObject) {
							$item->setName($name);
							$this->saveEditedObjectData($item);
							$item->commit();
						} else {
							throw new coreException("Object #{$id} doesn't exist");
						}
					}
				}
			}

			if (is_array($dels)) {
				foreach ($dels as $id) {
					$collection->delObject($id);
				}
			}

			return $new_item_id;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $data
		 * @param $dels
		 * @throws coreException
		 */
		public function saveEditedBaseTypesList($data, $dels) {
			$collection = umiHierarchyTypesCollection::getInstance();

			if (is_array($data)) {
				foreach ($data as $id => $info) {
					$title = getArrayKey($info, 'title');
					$module = getArrayKey($info, 'module');
					$method = getArrayKey($info, 'method');

					if ($id == 'new') {
						if ($module && $title) {
							$collection->addType($module, $title, $method);
						}
					} else {
						$item = $collection->getType($id);

						if ($item instanceof iUmiHierarchyType) {
							$item->setTitle($title);
							$item->setName($module);
							$item->setExt($method);
							$item->commit();
						} else {
							throw new coreException("Hierarchy type #{$id} doesn't exist");
						}
					}
				}
			}

			if (is_array($dels)) {
				foreach ($dels as $id) {
					$collection->delType($id);
				}
			}
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $data
		 * @param $dels
		 * @param $params
		 */
		public function saveEditedTemplatesList($data, $dels, $params) {
			$collection = templatesCollection::getInstance();
			$default = getArrayKey($data, 'default');
			$domainCollection = Service::DomainCollection();

			foreach ($params as $host => $templates) {
				$domain_id = $domainCollection->getDomainId($host);
				$host_data = getArrayKey($data, $host);

				$default_tpl_id = getArrayKey($default, $domain_id);

				foreach ($templates as $template) {
					/** @var iTemplate $template */
					$template_data = getArrayKey($host_data, $template->getId());

					$title = getArrayKey($template_data, 'title');
					$filename = getArrayKey($template_data, 'filename');

					if (!$title || !$filename) {
						continue;
					}
					$template->setTitle($title);
					$template->setFilename($filename);

					$directory = getArrayKey($template_data, 'directory');
					$template->setName(trim($directory));

					if (is_numeric($default_tpl_id)) {
						if ($template->getId() == $default_tpl_id) {
							$template->setIsDefault(true);
						} else {
							$template->setIsDefault(false);
						}
					}

					$template->commit();
				}

				if (($template_data = getArrayKey($host_data, 'new')) !== null) {
					$title = getArrayKey($template_data, 'title');
					$filename = getArrayKey($template_data, 'filename');

					if ($title && $filename) {
						$lang_id = Service::LanguageDetector()->detectId();
						$is_default = $default_tpl_id == 'new';
						$newTemplateId = $collection->addTemplate($filename, $title, $domain_id, $lang_id, $is_default);
						$newTemplate = $collection->getTemplate($newTemplateId);

						if ($newTemplate instanceof iTemplate) {
							$directory = getArrayKey($template_data, 'directory');
							$newTemplate->setName(trim($directory));
							$newTemplate->commit();
						}
					}
				}
			}

			if (is_array($dels)) {
				foreach ($dels as $id) {
					$template = $collection->getTemplate($id);

					if ($template instanceof iTemplate) {
						$collection->delTemplate($id);
						unset($template);
					}
				}
			}
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param iTemplate $template
		 */
		public function saveEditedTemplateData(iTemplate $template) {
			$name = getRequest('name');
			$title = getRequest('title');
			$filename = getRequest('filename');
			$type = getRequest('type');
			$used_pages = getRequest('used_pages');

			$template->setName($name);
			$template->setTitle($title);
			$template->setFilename($filename);
			$template->setType($type);
			$template->setUsedPages($used_pages);
			$template->commit();
		}

		/**
		 * Сохраняет список доменов
		 * @param array $data список атрибутов новых и существующих доменов
		 * @param array $dels список идентификаторов удаляемых доменов
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws wrongParamException
		 */
		public function saveEditedDomains($data, $dels) {
			$collection = Service::DomainCollection();

			if (is_array($data)) {
				foreach ($data as $id => $info) {
					$host = getArrayKey($info, 'host');
					$lang_id = getArrayKey($info, 'lang_id');
					$usingSsl = getArrayKey($info, 'using-ssl');
					$faviconPath = getArrayKey($info, 'favicon');
					$favicon = ($faviconPath && !is_dir($faviconPath)) ? Service::ImageFactory()->create($faviconPath) : null;

					if ($id == 'new') {
						$host = domain::filterHostName($host);
						if ($host && $lang_id) {
							$edition = Service::RegistrySettings()->getEdition();
							if ($edition == 'gov' && umiCount($collection->getList()) > 0) {
								throw new publicAdminException(getLabel('error-disabled-in-demo'));
							}

							if (defined('CURRENT_VERSION_LINE') &&
								in_array(CURRENT_VERSION_LINE, ['start', 'lite', 'shop'])
							) {
								throw new publicAdminException(getLabel('error-disabled-in-demo'));
							}

							if ($collection->getDomainId($host)) {
								throw new publicAdminException(getLabel('error-domain-already-exists'));
							}

							$id = $collection->addDomain($host, $lang_id, false, $usingSsl);
							$domain = $collection->getDomain($id);
							$domain->setFavicon($favicon);
							$domain->commit();
						}
					} else {
						if (!$host) {
							$item = $collection->getDomain($id);
							$item->setDefaultLangId($lang_id);
							$item->setUsingSsl($usingSsl);
							$item->setFavicon($favicon);
							$item->commit();

							continue;
						}

						$item = $collection->getDomain($id);

						if ($item instanceof iDomain) {
							if (!$item->getIsDefault()) {
								$item->setHost($host);
							}
							$item->setDefaultLangId($lang_id);
							$item->setUsingSsl($usingSsl);
							$item->setFavicon($favicon);
							$item->commit();
						} else {
							throw new coreException("Domain #{$id} doesn't exist");
						}
					}
				}
			}

			if (is_array($dels)) {
				foreach ($dels as $id) {
					$collection->delDomain($id);
				}
			}
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $data
		 * @param $dels
		 * @throws publicAdminException
		 * @throws coreException
		 */
		public function saveEditedDomainMirrows($data, $dels) {
			$collection = Service::DomainCollection();
			$domain = $collection->getDomain(getRequest('param0'));

			if (is_array($data)) {
				foreach ($data as $id => $info) {
					$host = getArrayKey($info, 'host');

					if ($id == 'new') {
						$host = domain::filterHostName($host);
						if ($host) {
							if ($collection->getDomainId($host)) {
								throw new publicAdminException(getLabel('error-domain-already-exists'));
							}
							$domain->addMirror($host);
						}
					} else {
						if (!$host) {
							continue;
						}

						$item = $domain->getMirror($id);

						if ($item instanceof iDomainMirror) {
							$item->setHost($host);
							$item->commit();
						} else {
							throw new coreException("Domain #{$id} doesn't exist");
						}
					}
				}
			}

			if (is_array($dels)) {
				foreach ($dels as $id) {
					$domain->delMirror($id);
				}
			}

			$domain->setIsUpdated();
			$domain->commit();
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $data
		 * @param $dels
		 * @throws coreException
		 */
		public function saveEditedLangs($data, $dels) {
			$collection = Service::LanguageCollection();

			if (is_array($data)) {
				foreach ($data as $id => $info) {
					$title = (string) getArrayKey($info, 'title');
					$prefix = (string) getArrayKey($info, 'prefix');

					if ($title === '' || $prefix === '') {
						continue;
					}

					$title = trim($title);
					$prefix = (string) preg_replace('/[^A-z0-9]*/', '', $prefix);

					if ($title === '' || $prefix === '') {
						continue;
					}

					if ($id == 'new') {
						$id = $collection->addLang($prefix, $title);
					}

					$item = $collection->getLang($id);

					if ($item instanceof iLang) {
						$item->setTitle($title);
						$item->setPrefix($prefix);
						$item->commit();
					} else {
						throw new coreException("Lang #{$id} doesn't exist");
					}
				}
			}
		}

		/**
		 * Сохраняет изменения объектного типа данных на основании данных запроса
		 * @param iUmiObjectType $type объектный тип данных
		 * @throws coreException
		 */
		public function saveEditedTypeData($type) {
			if (!$type instanceof iUmiObjectType) {
				throw new coreException('Expected instance of type umiObjectType');
			}

			$info = getRequest('data');
			$name = getArrayKey($info, 'name');

			if ($name !== null && $name != '') {
				$type->setName($name);
			}

			$guid = getArrayKey($info, 'guid');

			if ($guid !== null && $guid != '') {
				$type->setGUID($guid);
			}

			$baseTypeId = getArrayKey($info, 'hierarchy_type_id');

			if ($this->isNeedToSetBaseTypeId($type, $baseTypeId)) {
				$type->setHierarchyTypeId($baseTypeId);
			}

			$isGuide = getArrayKey($info, 'is_guidable');
			$type->setIsGuidable($isGuide);

			$isPublic = getArrayKey($info, 'is_public');
			$type->setIsPublic($isPublic);

			$domainId = getArrayKey($info, 'domain_id');
			$type->setDomainId($domainId);
			$type->commit();
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $group
		 * @throws coreException
		 */
		public function saveEditedGroupData($group) {
			$info = getRequest('data');

			$title = getArrayKey($info, 'title');
			$name = getArrayKey($info, 'name');
			$is_visible = getArrayKey($info, 'is_visible');
			$tip = getArrayKey($info, 'tip');

			if ($group instanceof iUmiFieldsGroup) {
				$group->setName($name);
				$group->setTitle($title);
				$group->setIsVisible($is_visible);
				$group->setIsActive(true);
				$group->setTip($tip);
				$group->commit();
			} else {
				throw new coreException('Expected instance of type umiFieldsGroup');
			}
		}

		/**
		 * Сохраняет изменения поля, переданные через форму редактирования
		 * @param iUmiField|false $field поля
		 * @throws coreException
		 * @throws wrongParamException
		 */
		public function saveEditedFieldData($field) {
			if (!$field instanceof iUmiField) {
				throw new coreException('Expected instance of type umiField');
			}

			$info = getRequest('data');
			$title = getArrayKey($info, 'title');
			$field->setTitle($title);

			$name = getArrayKey($info, 'name');
			$field->setName($name);

			$is_visible = getArrayKey($info, 'is_visible');
			$field->setIsVisible($is_visible);

			$field_type_id = getArrayKey($info, 'field_type_id');
			$field->setFieldTypeId($field_type_id);

			$in_search = getArrayKey($info, 'in_search');
			$field->setIsInSearch($in_search);

			$in_filter = getArrayKey($info, 'in_filter');
			$field->setIsInFilter($in_filter);

			$tip = getArrayKey($info, 'tip');
			$field->setTip($tip);

			$isRequired = getArrayKey($info, 'is_required');
			$field->setIsRequired($isRequired);

			$restrictionId = getArrayKey($info, 'restriction_id');
			$field->setRestrictionId($restrictionId);

			$isImportant = getArrayKey($info, 'is_important');
			$field->setImportanceStatus($isImportant);

			$isSystem = getArrayKey($info, 'is_system');
			$field->setIsSystem($isSystem);

			//Choose or create public guide for unlinked relation field
			$field_type_obj = umiFieldTypesCollection::getInstance()->getFieldType($field_type_id);
			$field_data_type = $field_type_obj->getDataType();
			$guide_id = getArrayKey($info, 'guide_id');

			if ($field_data_type == 'relation' && $guide_id == 0) {
				$guide_id = self::getAutoGuideId($title);
			}

			if ($field_data_type == 'optioned' && $guide_id == 0) {
				$parent_guide_id = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('emarket-itemoption');
				$guide_id = self::getAutoGuideId($title, $parent_guide_id);
			}

			$field->setGuideId($guide_id);

			$field->commit();
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 * @throws coreException
		 */
		public function saveAddedGroupData($inputData) {
			$info = getRequest('data');

			$name = getArrayKey($info, 'name');
			$title = getArrayKey($info, 'title');
			$is_visible = getArrayKey($info, 'is_visible');
			$tip = getArrayKey($info, 'tip');

			$type_id = getArrayKey($inputData, 'type-id');
			$type = umiObjectTypesCollection::getInstance()->getType($type_id);

			if ($type instanceof iUmiObjectType) {
				$fields_group_id = $type->addFieldsGroup($name, $title, true, $is_visible, $tip);
				$type->commit();
				return $fields_group_id;
			}

			throw new coreException('Expected instance of type umiObjectType');
		}

		/**
		 * Добавляет новое поле в тип данных
		 * @param array $inputData параметры добавляемого поля
		 * @return bool|int
		 * @throws publicAdminException
		 * @throws coreException
		 */
		public function saveAddedFieldData($inputData) {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$fields = umiFieldsCollection::getInstance();
			$fieldTypes = umiFieldTypesCollection::getInstance();

			$typeId = $inputData['type-id'];
			$type = $objectTypes->getType($typeId);
			if (!$type instanceof iUmiObjectType) {
				throw new coreException(getLabel('error-no-object-type', false, $typeId));
			}

			$groupId = $inputData['group-id'];
			$group = $type->getFieldsGroup($groupId);
			if (!$group instanceof iUmiFieldsGroup) {
				throw new coreException(getLabel('error-no-fieldgroup', false, $groupId));
			}

			$info = getRequest('data');
			$name = getArrayKey($info, 'name');

			if ($type->getFieldId($name)) {
				throw new publicAdminException(getLabel('error-non-unique-field-name'));
			}

			$fieldTypeId = getArrayKey($info, 'field_type_id');
			$fieldType = $fieldTypes->getFieldType($fieldTypeId);
			$dataType = $fieldType->getDataType();
			$guideId = getArrayKey($info, 'guide_id');
			$title = getArrayKey($info, 'title');

			if ($dataType == 'relation' && $guideId == 0) {
				$guideId = self::getAutoGuideId($title);
			}

			if ($dataType == 'optioned' && $guideId == 0) {
				$parentGuideId = $objectTypes->getTypeIdByGUID('emarket-itemoption');
				$guideId = self::getAutoGuideId($title, $parentGuideId);
			}

			$isVisible = getArrayKey($info, 'is_visible');
			$fieldId = $fields->addField($name, $title, $fieldTypeId, $isVisible);

			$field = $fields->getField($fieldId);
			$field->setGuideId($guideId);

			$inSearch = getArrayKey($info, 'in_search');
			$field->setIsInSearch($inSearch);

			$inFilter = getArrayKey($info, 'in_filter');
			$field->setIsInFilter($inFilter);

			$tip = getArrayKey($info, 'tip');
			$field->setTip($tip);

			$isRequired = getArrayKey($info, 'is_required');
			$field->setIsRequired($isRequired);

			$restrictionId = getArrayKey($info, 'restriction_id');
			$field->setRestrictionId($restrictionId);

			$isImportant = getArrayKey($info, 'is_important');
			$field->setImportanceStatus($isImportant);

			$isSystem = getArrayKey($info, 'is_system');
			$field->setIsSystem($isSystem);
			$field->commit();

			$group->attachField($fieldId);
			$groupName = $group->getName();
			$childTypeIds = $objectTypes->getChildTypeIds($typeId);
			$ignoreChildrenTypesWithoutFieldGroup = (bool) getRequest('ignoreChildGroup');

			foreach ($childTypeIds as $childTypeId) {
				$childType = $objectTypes->getType($childTypeId);

				if (!$childType instanceof iUmiObjectType) {
					throw new publicAdminException(getLabel('error-no-object-type', false, $childTypeId));
				}

				if (is_numeric($childType->getFieldId($name))) {
					continue;
				}

				$childGroup = $childType->getFieldsGroupByName($groupName);

				if (!$childGroup instanceof iUmiFieldsGroup && !$ignoreChildrenTypesWithoutFieldGroup) {
					throw new publicAdminException(
						getLabel('error-no-child-group', false, $groupName, $childType->getName())
					);
				}

				$childGroup->attachField($fieldId, true);
			}

			return $fieldId;
		}

		/**
		 * Проверяет существование группы во всех дочерних типах
		 * @param array $param параметры для проверки array('groupId', 'typeId')
		 * @return bool
		 * @throws coreException
		 */
		public function isChildGroupExist(array $param) {
			$groupId = $param['groupId'];
			$typeId = $param['typeId'];

			$objectTypes = umiObjectTypesCollection::getInstance();

			$type = $objectTypes->getType($typeId);
			if ($type instanceof iUmiObjectType) {

				$group = $type->getFieldsGroup($groupId);
				if ($group instanceof iUmiFieldsGroup) {

					$childsTypesIds = $objectTypes->getChildTypeIds($typeId);
					foreach ($childsTypesIds as $typeId) {
						$childType = $objectTypes->getType($typeId);
						$childTypeGroup = $childType->getFieldsGroupByName($group->getName());

						if (!$childTypeGroup instanceof iUmiFieldsGroup) {
							return false;
						}
					}
				}
			}

			return true;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $redirect_string
		 */
		public function chooseRedirect($redirect_string = false) {
			$cmsController = cmsController::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$referer_uri = $cmsController->getCalculatedRefererUri();

			$save_mode_str = getRequest('save-mode');

			switch ($save_mode_str) {
				case getLabel('label-save-exit'):
				case getLabel('label-save-add-exit'): {
					$save_mode = 1;
					break;
				}

				case getLabel('label-save-view'):
				case getLabel('label-save-add-view'): {
					$save_mode = 2;
					break;
				}

				case getLabel('label-save'):
				case getLabel('label-save-add'): {
					$save_mode = 3;
					break;
				}

				default: {
					$save_mode = false;
				}
			}

			$forceRedirectUrl = getArrayKey($_GET, 'force-redirect');
			if ($forceRedirectUrl) {
				def_module::simpleRedirect($forceRedirectUrl);
			}

			if ($save_mode == 1) {
				def_module::simpleRedirect($referer_uri);
			}

			if ($save_mode == 2) {
				$element_id = $this->currentEditedElementId;
				if ($element_id) {
					$element_path = $hierarchy->getPathById($element_id);
					def_module::simpleRedirect($element_path);
				}
			}

			if ($redirect_string !== false) {
				def_module::simpleRedirect($redirect_string);
			}

			if ($save_mode && $element_id = $this->currentEditedElementId) {
				$element = $hierarchy->getElement($element_id);

				if ($element instanceof iUmiHierarchyElement) {
					$element_module = $element->getHierarchyType()->getName();
					$element_method = $element->getHierarchyType()->getExt();
					/** @var CatalogAdmin|UsersAdmin|NewsAdmin|FaqAdmin|ForumAdmin|EmarketAdmin|Blogs20Admin|ForumAdmin|def_module $module */
					$module = cmsController::getInstance()->getModule($element_module);
					if ($module instanceof def_module) {
						$links = $module->getEditLink($element_id, $element_method);
						$edit_link = isset($links[1]) ? $links[1] : false;

						if ($edit_link) {
							$module->redirect($edit_link);
						}
					}
				}
			}
			$request_uri = def_module::removeErrorCodeFromUrl(getServer('HTTP_REFERER'));
			Service::Response()
				->getCurrentBuffer()
				->redirect($request_uri);
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $inputData
		 * @param $ignoreIfNull
		 * @throws coreException
		 */
		public function checkAllowedElementType($inputData, $ignoreIfNull = true) {
			$element = getArrayKey($inputData, 'element');
			$object = getArrayKey($inputData, 'object');
			$type = getArrayKey($inputData, 'type');
			$allowed_types = getArrayKey($inputData, 'allowed-element-types');

			$commentsHierarchyType = umiHierarchyTypesCollection::getInstance()->getTypeByName('comments', 'comment');
			if ($commentsHierarchyType) {
				$commentsHierarchyTypeId = $commentsHierarchyType->getId();
			} else {
				$commentsHierarchyTypeId = false;
			}

			if (is_array($allowed_types) === false) {
				if ($ignoreIfNull === false) {
					throw new coreException('Allowed types expected to be array');
				}

				return true;
			}

			if ($type) {
				if (in_array($type, $allowed_types)) {
					return true;
				}

				return false;
			}

			if ($element instanceof iUmiHierarchyElement === true) {
				$hierarchy_type_id = $element->getTypeId();
			} else {
				if ($object instanceof iUmiObject === true) {
					$object_type_id = $object->getTypeId();
					$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);
					$hierarchy_type_id = $object_type->getHierarchyTypeId();
				} else {
					throw new coreException(
						"If you are doing 'add' method, you should pass me 'type' key in 'inputData' array." .
						" If you have 'edit' method, pass me 'element' key in 'inputData' array."
					);
				}
			}

			$hierarchy_type = umiHierarchyTypesCollection::getInstance()->getType($hierarchy_type_id);

			if ($hierarchy_type instanceof iUmiHierarchyType) {
				$method = $hierarchy_type->getExt();
				return in_array($method, $allowed_types) || $hierarchy_type->getId() == $commentsHierarchyTypeId;
			}

			throw new coreException('This should never happen');
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $element_id
		 * @param $requiredPermission
		 * @throws requreMoreAdminPermissionsException
		 */
		public function checkElementPermissions($element_id, $requiredPermission = permissionsCollection::E_EDIT_ALLOWED) {
			static $permissions = null, $user_id = null;
			if ($permissions === null) {
				$permissions = permissionsCollection::getInstance();
				$user_id = Service::Auth()
					->getUserId();
			}

			$allow = $permissions->isAllowedObject($user_id, $element_id);

			if (!isset($allow[$requiredPermission]) || !$allow[$requiredPermission]) {
				throw new requreMoreAdminPermissionsException(getLabel('error-require-more-permissions'));
			}

			return true;
		}

		/**
		 * Переключает активность у страниц с заданными иерархическими типами
		 * @param array $types названия возможных иерархических типов страниц
		 * @throws expectElementException
		 * @throws publicAdminException
		 * @throws requreMoreAdminPermissionsException
		 * @throws wrongElementTypeAdminException
		 * @throws coreException
		 */
		protected function changeActivityForPages(array $types = []) {
			$pageIdList = (array) getRequest('element');
			$isActive = (getRequest('active') === null) ? getRequest('activity') : getRequest('active');

			if ($isActive === null) {
				throw new publicAdminException(getLabel('error-expect-action'));
			}

			$umiHierarchy = umiHierarchy::getInstance();
			foreach ($pageIdList as $pageId) {
				$page = $umiHierarchy->getElement($pageId);

				$params = [
					'element' => $page,
					'activity' => $isActive
				];

				if (count($types) > 0) {
					$params['allowed-element-types'] = $types;
				}

				$this->switchActivity($params);
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($pageIdList, 'pages');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Переключает активность у объектов
		 * @throws coreException
		 * @throws expectObjectException
		 */
		protected function changeActivityForObjects() {
			$objectIdList = (array) getRequest('object');
			$isActive = (bool) getRequest('active');
			$umiObjects = umiObjectsCollection::getInstance();

			foreach ($objectIdList as $objectId) {
				$object = $umiObjects->getObject($objectId);
				$this->validateObjectActivityChange($object, $isActive);

				$event = Service::EventPointFactory()
					->create('systemChangeObjectActivity');

				$event->addRef('object', $object)
					->setParam('activity', $isActive)
					->setMode('before')
					->call();

				$this->setObjectActivity($object, $isActive);
				$object->commit();

				$event->setMode('after')->call();
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($objectIdList, 'objects');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Валидирует изменение активности объекта.
		 *
		 * @param iUmiObject|null $object объект
		 * @param bool $isActive новый статус активности
		 * (параметр может использоваться в переопределениях метода конкретными модулями)
		 *
		 * @throws expectObjectException
		 */
		protected function validateObjectActivityChange($object, $isActive) {
			if (!$object instanceof iUmiObject) {
				throw new expectObjectException(getLabel('error-expect-object'));
			}
		}

		/**
		 * Устанавливает новый статус активности объекта
		 * @param iUmiObject $object объект
		 * @param bool $isActive новый статус активности
		 */
		protected function setObjectActivity(iUmiObject $object, $isActive) {
			$object->setValue('is_active', $isActive);
		}

		/**
		 * Меняет активность страницы
		 * @param array $params параметры страницы
		 *
		 * [
		 *      'element' => iUmiHierarchyElement,
		 *      'activity' => bool
		 * ]
		 *
		 * @return bool активность страницы после изменения
		 * @throws wrongElementTypeAdminException
		 * @throws expectElementException
		 * @throws requreMoreAdminPermissionsException
		 */
		public function switchActivity($params) {
			if (!$this->checkAllowedElementType($params)) {
				throw new wrongElementTypeAdminException(getLabel('error-unexpected-element-type'));
			}

			$element = getArrayKey($params, 'element');
			$activity = getArrayKey($params, 'activity');

			if (!$element instanceof iUmiHierarchyElement) {
				throw new expectElementException(getLabel('error-expect-element'));
			}

			$this->checkElementPermissions($element->getId());

			if ($activity === null) {
				$activity = !$element->getIsActive();
			}

			$permissions = permissionsCollection::getInstance();
			$userId = Service::Auth()
				->getUserId();

			if (!$permissions->isAllowedMethod($userId, $element->getModule(), 'publish')) {
				throw new requreMoreAdminPermissionsException(getLabel('error-no-publication-permissions'));
			}

			if ($activity == $element->getIsActive()) {
				return $activity;
			}

			$event = Service::EventPointFactory()
				->create('systemSwitchElementActivity');

			try {
				$event->addRef('element', $element)
					->setParam('activity', $activity)
					->setMode('before')
					->call();
			} catch (coreBreakEventsException $e) {
				return $element->getIsActive();
			}

			$element->setIsActive($activity);
			$element->commit();

			try {
				$event->setMode('after')
					->call();
			} catch (coreBreakEventsException $e) {
				return $element->getIsActive();
			}

			return $element->getIsActive();
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $params
		 * @throws wrongElementTypeAdminException
		 */
		public function moveElement($params) {
			if (!$this->checkAllowedElementType($params)) {
				throw new wrongElementTypeAdminException(getLabel('error-unexpected-element-type'));
			}
			$hierarchy = umiHierarchy::getInstance();
			$domains = Service::DomainCollection();

			$element = getArrayKey($params, 'element');
			$parentId = getArrayKey($params, 'parent-id');
			$domainId = getArrayKey($params, 'domain');

			$asSibling = getArrayKey($params, 'as-sibling');
			$beforeId = getArrayKey($params, 'before-id');
			$isMoveToEnd = getArrayKey($params, 'move-to-end');

			$this->checkElementPermissions($element->getId(), permissionsCollection::E_MOVE_ALLOWED);
			$oldParentId = $element->getParentId();

			$event = new umiEventPoint('systemMoveElement');
			$event->addRef('element', $element);
			$event->setParam('parent-id', $parentId);
			$event->setParam('domain-host', $domainId);
			$event->setParam('as-sibling', $asSibling);
			$event->setParam('before-id', $beforeId);
			$event->setParam('move-to-end', $isMoveToEnd);
			$event->setParam('old-parent-id', $oldParentId);
			$event->setMode('before');

			try {
				$event->call();
			} catch (coreBreakEventsException $e) {
				return false;
			}

			if (!is_numeric($domainId)) {
				$domainId = $domains->getDomainId($domainId);
			}

			if ($domainId) {
				$element->setDomainId($domainId);
			}
			$element->commit();

			if ($asSibling || $isMoveToEnd) {
				$hierarchy->moveBefore($element->getId(), $parentId, $beforeId ?: false);
			} else {
				$hierarchy->moveFirst($element->getId(), $parentId);
			}
			$element->update();
			$element->setIsUpdated();
			$element->commit();
			$event->setMode('after');
			try {
				$event->call();
				return true;
			} catch (coreBreakEventsException $e) {
				return false;
			}
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $params
		 * @throws expectElementException
		 * @throws wrongElementTypeAdminException
		 */
		public function deleteElement($params) {
			Service::Protection()->checkCsrf();

			$element = getArrayKey($params, 'element');

			if ($element instanceof iUmiHierarchyElement === false) {
				throw new expectElementException(getLabel('error-expect-element'));
			}

			if (!$this->checkAllowedElementType($params)) {
				throw new wrongElementTypeAdminException(getLabel('error-unexpected-element-type'));
			}

			$this->checkElementPermissions($element->getId(), permissionsCollection::E_DELETE_ALLOWED);

			$event = new umiEventPoint('systemDeleteElement');
			$event->addRef('element', $element);
			$event->setMode('before');
			$event->call();

			umiHierarchy::getInstance()->delElement($element->getId());

			$event->setMode('after');
			$event->call();
		}

		/**
		 * Удаляет объект
		 * @param array $params
		 *
		 * [
		 *      'object' => iUmiObject
		 * ]
		 *
		 * @return bool
		 * @throws coreException
		 * @throws wrongElementTypeAdminException
		 * @throws publicAdminException
		 * @throws \UmiCms\System\Protection\CsrfException
		 */
		public function deleteObject($params) {
			Service::Protection()->checkCsrf();

			$objectsCollection = umiObjectsCollection::getInstance();
			$objectTypesCollection = umiObjectTypesCollection::getInstance();
			$hierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();

			$object = getArrayKey($params, 'object');

			if (!$object instanceof iUmiObject) {
				throw new coreException('You should pass "object" key containing umiObject instance.');
			}

			$object_id = $object->getId();
			$object_type_id = $object->getTypeId();
			$object_type = $objectTypesCollection->getType($object_type_id);

			if (!$object_type instanceof iUmiObjectType) {
				throw new coreException("Object #{$object_id} hasn't type #{$object_type_id}. This should not happen.");
			}

			if (getArrayKey($params, 'type') !== null) {
				$hierarchy_type_id = $object_type->getHierarchyTypeId();
				$hierarchy_type = $hierarchyTypesCollection->getType($hierarchy_type_id);

				if (!$hierarchy_type instanceof iUmiHierarchyType) {
					throw new coreException(
						"Object type #{$object_type_id} doesn't have hierarchy type #{$hierarchy_type_id}. This should not happen."
					);
				}

				$params['type'] = $hierarchy_type->getExt();

				if (!$this->checkAllowedElementType($params)) {
					throw new wrongElementTypeAdminException(getLabel('error-unexpected-element-type'));
				}
			}

			$event = new umiEventPoint('systemDeleteObject');
			$event->addRef('object', $object);
			$event->setMode('before');
			$event->call();

			try {
				$result = $objectsCollection->delObject($object_id);
			} catch (coreException $exception) {
				throw new publicAdminException(getLabel('error-deleting-an-object-is-locked'));
			}

			$event->setMode('after');
			$event->call();

			return $result;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 */
		public function getFloatedDomain() {
			if (($domain_floated = getRequest('domain')) !== null) {
				$domain_floated = urldecode($domain_floated);
				$domain_floated_id = Service::DomainCollection()->getDomainId($domain_floated);
				if ($domain_floated_id) {
					return $domain_floated_id;
				}
			}
			return false;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $title
		 * @param $baseGuideId
		 */
		public static function getAutoGuideId($title, $baseGuideId = false) {
			$objectTypesCollection = umiObjectTypesCollection::getInstance();
			if (!$baseGuideId) {
				$baseGuideId = $objectTypesCollection->getTypeIdByGUID('root-guides-type');
			}
			$guide_name = getLabel('autoguide-for-field') . " \"{$title}\"";

			$child_types = $objectTypesCollection->getChildTypeIds($baseGuideId);
			foreach ($child_types as $child_type_id) {
				$child_type = $objectTypesCollection->getType($child_type_id);
				$child_type_name = $child_type->getName();

				if ($child_type_name == $guide_name) {
					$child_type->setIsGuidable(true);
					return $child_type_id;
				}
			}

			$guide_id = $objectTypesCollection->addType($baseGuideId, $guide_name);
			$guide = $objectTypesCollection->getType($guide_id);
			$guide->setIsGuidable(true);
			$guide->setIsPublic(true);
			$guide->commit();

			return $guide_id;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $domain_id
		 * @throws coreException
		 * @throws requreMoreAdminPermissionsException
		 */
		public function checkDomainPermissions($domain_id = false) {
			$permissions = permissionsCollection::getInstance();
			$domains = Service::DomainCollection();

			if (!$domain_id) {
				if (($domain_host = getRequest('domain')) !== null) {
					$domain_id = $domains->getDomainId($domain_host);
				} else {
					$domain_id = Service::DomainDetector()->detectId();
				}
			}

			if (!$domain_id) {
				throw new coreException('Require domain id to check domain permissions');
			}

			$user_id = Service::Auth()
				->getUserId();
			$is_allowed = $permissions->isAllowedDomain($user_id, $domain_id);

			if ($is_allowed == 0) {
				throw new requreMoreAdminPermissionsException(getLabel('error-no-domain-permissions'));
			}

			return null;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $key1
		 * @param $key2
		 */
		public function setRequestDataAlias($key1, $key2) {
			if (isset($_REQUEST[$key2])) {
				$_REQUEST[$key1] = &$_REQUEST[$key2];
				return true;
			}

			return false;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $aliases
		 * @param $id
		 */
		public function setRequestDataAliases($aliases, $id = 'new') {
			if (!is_array($aliases)) {
				return false;
			}

			foreach ($aliases as $key1 => $key2) {
				if (isset($_REQUEST['data'][$id][$key2])) {
					$_REQUEST[$key1] = &$_REQUEST['data'][$id][$key2];
				}
			}
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $module
		 * @param $method
		 * @param $type_id
		 */
		public function compareObjectTypeByHierarchy($module, $method, $type_id) {
			$typesCollection = umiObjectTypesCollection::getInstance();
			$hierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();

			$type = $typesCollection->getType($type_id);
			$hierarchy_type = $hierarchyTypesCollection->getTypeByName($module, $method);

			if ($type instanceof iUmiObjectType && $hierarchy_type instanceof iUmiHierarchyType) {
				return $type->getHierarchyTypeId() == $hierarchy_type->getId();
			}

			return false;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $element
		 * @param $user_id
		 */
		public function systemIsLocked($element, $user_id) {
			if ($element) {
				$oPage = $element->getObject();
				$lockTime = $oPage->getValue('locktime');
				$lockUser = $oPage->getValue('lockuser');
				if ($lockTime == null || $lockUser == null) {
					return false;
				}
				$lockDuration = Service::Registry()->get('//settings/lock_duration');
				if (($lockTime->timestamp + $lockDuration) > time() && $lockUser != $user_id) {
					return true;
				}

				$oPage->setValue('lockuser', null);
				$oPage->setValue('locktime', null);
				$oPage->commit();
				$element->commit();
				return false;
			}
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $statusId
		 */
		public function getPageStatusIdByStatusSid($statusId = 'page_status_publish') {
			$objectTypeId = $this->getGuideIdByFieldName('publish_status');

			if (!$objectTypeId) {
				return false;
			}

			$sel = new selector('objects');
			$sel->types('object-type')->id($objectTypeId);
			$result = $sel->result();

			/** @var iUmiObject $object */
			foreach ($result as $object) {
				$statusStringId = $object->getValue('publish_status_id');

				if ($statusStringId == $statusId) {
					return $object->getId();
				}
			}

			return false;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $fieldName
		 */
		public function getGuideIdByFieldName($fieldName) {
			$fields = umiObjectTypesCollection::getInstance()->getTypeByGUID('root-pages-type');
			foreach ($fields->getAllFields() as $field) {
				if ($field->getName() == $fieldName) {
					return $field->getGuideId();
				}
			}
			return false;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param string $label
		 */
		public function setHeaderLabel($label) {
			$cmsController = cmsController::getInstance();
			$cmsController->headerLabel = $label;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $object
		 * @throws coreException
		 */
		public function getObjectTypeMethod($object) {
			if (!$object instanceof iUmiObject) {
				throw new coreException('Expected instance of umiObject as param.');
			}

			$objectTypes = umiObjectTypesCollection::getInstance();
			$objectTypeId = $object->getTypeId();
			$objectType = $objectTypes->getType($objectTypeId);
			if ($objectType instanceof iUmiObjectType) {
				$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
				$hierarchyTypeId = $objectType->getHierarchyTypeId();
				$hierarchyType = $hierarchyTypes->getType($hierarchyTypeId);

				if ($hierarchyType instanceof iUmiHierarchyType) {
					return $hierarchyType->getExt();
				}

				throw new coreException("Can't get hierarchy type #{$hierarchyTypeId}");
			}

			throw new coreException("Can't get object type #{$objectTypeId}");
		}

		/**
		 * Возвращает настройки табличного контрола
		 * @param string $param контрольный параметр (чаще всего - название текущей вкладки
		 * административной панели)
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			return [
				'methods' => [
					['title' => getLabel('smc-load'), 'forload' => true, 'module' => 'content', '#__name' => 'load_tree_node'],
					['title' => getLabel('smc-delete'), 'module' => 'content', '#__name' => 'tree_delete_element'],
					['title' => getLabel('smc-activity'), 'module' => 'content', '#__name' => 'tree_set_activity'],
					['title' => getLabel('smc-copy'), 'module' => 'content', '#__name' => 'tree_copy_element'],
					['title' => getLabel('smc-move'), 'module' => 'content', '#__name' => 'move'],
					['title' => getLabel('smc-change-template'), 'module' => 'content', '#__name' => 'change_template'],
					['title' => getLabel('smc-change-lang'), 'module' => 'content', '#__name' => 'copyElementToSite'],
				],
				'default' => 'name[400px]'
			];
		}

		/** Выводит в буфер конфигурацию данных контрола */
		final public function dataset_config() {
			$dataSetConfig = [];

			try {
				$dataSetConfig = $this->getDataSetConfig();
			} catch (coreException $exception) {
				umiExceptionHandler::report($exception);
			}

			/** @var iXmlTranslator $translator */
			$translator = Service::get('DataSetConfigXmlTranslator');
			$document = $translator->translate($dataSetConfig);
			Service::Response()->printXml($document);
		}

		/**
		 * Возвращает конфигурацию данных контрола текущего модуля
		 * @param string|null $type тип данных контрола (обычно ключевое слово, описывающее выводимые данные)
		 * @return array|mixed
		 * @throws coreException
		 */
		private function getDataSetConfig($type = null) {
			$type = $type ?: getRequest('param');
			$allowedModuleList = (array) cmsController::getInstance()
				->getCurrentModule();

			/** @var umiEventPoint|iUmiEventPoint $event */
			$event = Service::EventPointFactory()
				->create('dataset_config', 'before', $allowedModuleList)
				->setMode('before')
				->setParam('type', $type);
			$event->call();

			$datasetConfig = $this->getDatasetConfiguration($event->getParam('type'));

			$event->setMode('after')
				->setParam('config', $datasetConfig)
				->call();

			return $event->getParam('config');
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @throws publicAdminException
		 */
		public function change_template() {
			$elements = getRequest('element');
			if (!is_array($elements)) {
				$elements = [$elements];
			}

			$element = $this->expectElement('element');
			$templateId = getRequest('template-id');

			if ($templateId !== null) {
				foreach ($elements as $elementId) {
					$element = $this->expectElement($elementId, false, true);

					if ($element instanceof iUmiHierarchyElement) {
						$element->setTplId($templateId);
						$element->commit();
					} else {
						throw new publicAdminException(getLabel('error-expect-element'));
					}
				}

				$this->setDataType('list');
				$this->setActionType('view');
				$data = $this->prepareData($elements, 'pages');
				$this->setData($data);

				return $this->doData();
			}

			throw new publicAdminException(getLabel('error-expect-action'));
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param $groupName
		 * @param $activity
		 */
		public function switchGroupsActivity($groupName, $activity) {
			$groups = umiFieldsGroup::getAllGroupsByName($groupName);
			foreach ($groups as $group) {
				if ($group instanceof iUmiFieldsGroup) {
					$group->setIsActive($activity);
					$group->commit();
				}
			}
		}

		/** Устанавливает сообщение об ошибке при неправильном вызове метода административной панели */
		public function setDirectCallError() {
			$this->setData([
				'message' => 'This method returns result only by direct xml call'
			]);
		}

		/**
		 * Убирает из массива идентификатор объектных типов данных
		 * все дочерние типы данных и возвращает результат
		 * @param array $typesIds массив идентификатор объектных типов данных
		 * @return array
		 * @throws coreException
		 */
		public function excludeNestedTypes(array $typesIds) {
			$objectTypes = umiObjectTypesCollection::getInstance();

			$result = [];
			foreach ($typesIds as $typeId) {
				$type = $objectTypes->getType($typeId);
				if ($type instanceof iUmiObjectType) {
					if (in_array($type->getParentId(), $typesIds)) {
						continue;
					}

					$result[] = $typeId;
				}
			}
			return $result;
		}

		/**
		 * Сохраняет данные формы редактирования сущности в сущность с заданным типом и производит перенаправление
		 * @param int|null $entityId идентификатор сущности
		 * @param string|null $entityType тип сущности
		 * @param array|null $editFormData данные формы редактирования сущности
		 * @throws wrongParamException
		 */
		public function saveFormDataToEntityWithDefinedType(
			$entityId = null,
			$entityType = null,
			array $editFormData = null
		) {
			$entity = $this->getEntityByIdAndType($entityId, $entityType);

			if ($editFormData === null) {
				$editFormData = $this->getEditFormDataFromSaveRequest();
			}

			$entity = $this->saveFormDataToEntity($entity, $editFormData);
			$this->redirectAfterSaveEditForm($entity);
		}

		/**
		 * Создает сущность заданного типа, заполняя ее данными формы и производит перенаправление
		 * @param string|null $entityType тип создаваемой сущности
		 * @param array|null $formData данные формы добавления сущности
		 */
		public function createEntityWithDefinedTypeFromFormData($entityType = null, array $formData = null) {
			$collection = $this->getCollectionByEntityType($entityType);

			if ($formData === null) {
				$formData = $this->getCreateFormDataFromSaveRequest();
			}

			$entity = $this->createEntityFromFormData($collection, $formData);
			$this->redirectAfterCreateEntity($entity);
		}

		/**
		 * Возвращает данные формы редактирования сущности заданного типа
		 * @param int|null $entityId идентификатор сущности
		 * @param string|null $entityType тип сущности
		 * @throws wrongParamException
		 */
		public function getEditFormOfEntityWithDefinedType($entityId = null, $entityType = null) {
			$entity = $this->getEntityByIdAndType($entityId, $entityType);

			$editFormHeader = $this->getEditFormHeader($entity);
			$this->setHeaderLabel($editFormHeader);

			$editFormData = $this->getEditFormDataOfEntity($entity);
			$this->setDataSetEditFormResult($editFormData);
		}

		/**
		 * Возвращает данные формы добавления сущности заданного типа
		 * @param string|null $entityType тип создаваемой сущности
		 */
		public function getCreateFormOfEntityWithDefinedType($entityType = null) {
			$collection = $this->getCollectionByEntityType($entityType);

			$createFormHeader = $this->getCreateFormHeader($collection);
			$this->setHeaderLabel($createFormHeader);

			$createFormData = $this->getCreateFormDataOfEntity($collection);
			$this->setDataSetCreateFormResult($createFormData);
		}

		/**
		 * Возвращает заголовок для формы редактирования сущности
		 * @param iUmiCollectionItem $entity редактируемая сущность
		 * @return string
		 */
		public function getEditFormHeader(iUmiCollectionItem $entity) {
			$entityClass = trimNameSpace(get_class($entity));
			return 'header-edit-form-' . $this->getModuleName() . '-' . $entityClass;
		}

		/**
		 * Возвращает заголовок для формы добавления сущности
		 * @param iUmiCollection $collection коллекция добавляемой сущности
		 * @return string
		 */
		public function getCreateFormHeader(iUmiCollection $collection) {
			$entityClass = trimNameSpace($collection->getCollectionItemClass());
			return 'header-create-form-' . $this->getModuleName() . '-' . $entityClass;
		}

		/**
		 * Удаляет сущности с заданными типами.
		 * Применяется когда нужно удалить список сущностей разных типов.
		 * @param array|null $entitiesData данные сущностей
		 * @throws publicAdminException
		 */
		public function deleteEntitiesWithDefinedTypes(array $entitiesData = null) {
			if ($entitiesData === null) {
				$entitiesData = $this->getEntitiesDataForDeleting();
			}

			$entitiesData = $this->getValidEntitiesData($entitiesData);
			$entitiesData = $this->callbackBeforeDeletingEntities($entitiesData);

			if (umiCount($entitiesData) === 0) {
				throw new publicAdminException('There are no valid entities');
			}

			$this->setDataSetDeleteResult(
				$this->deleteCollectionsEntities(
					$entitiesData,
					$this->getModuleCollections()
				)
			);
		}

		/**
		 * Сохраняет изменение поля сущности с заданным типом и выводит в буффер результат сохранения
		 * @param int|null $entityId идентификатор сущности
		 * @param string|null $entityType тип сущности
		 * @param string|null $fieldName название поля
		 * @param mixed|null $fieldValue значение поля
		 * @throws wrongParamException
		 */
		public function saveFieldOfEntityWithDefinedType(
			$entityId = null,
			$entityType = null,
			$fieldName = null,
			$fieldValue = null
		) {
			$entity = $this->getEntityByIdAndType($entityId, $entityType);
			$collection = $this->getCollectionByEntityType(
				get_class($entity)
			);

			if ($fieldName === null) {
				$fieldName = getRequest('field');
			}

			if ($fieldValue === null) {
				$fieldValue = getRequest('value');
			}

			try {
				$fieldValue = $this->callbackBeforeSavingEntityField(
					$entity,
					$collection,
					$fieldName,
					$fieldValue
				);

				$entity->setValue($fieldName, $fieldValue);
				$entity->commit();

				$result = $this->getSimpleSuccessMessage();
			} catch (Exception $e) {
				$result = $this->getSimpleErrorMessage(
					$e->getMessage()
				);
			}

			$this->setDataSetEditFieldResult($result);
		}

		/**
		 * Перемещает сущности с заданными типами и выводит результат в буффер
		 * @param array|null $targetEntityData данные сущности, относительно которой перемещаются сущности.
		 * Сущности могут перемещаться на позицию до или после этой сущности, либо становится ее дочерним элементом.
		 * @param array|null $draggedEntityDataList данные перемещаемых сущностей
		 * @param string|null $dragMode режим перемещения (до, после, как дочерний элемент)
		 * @throws wrongParamException
		 */
		public function moveEntitiesWithDefinedTypes(
			array $targetEntityData = null,
			array $draggedEntityDataList = null,
			$dragMode = null
		) {
			if ($targetEntityData === null) {
				$targetEntityData = $this->getDragTargetEntityData();
			}

			if ($draggedEntityDataList === null) {
				$draggedEntityDataList = $this->getDraggedEntitiesData();
			}

			if ($dragMode === null) {
				$dragMode = $this->getDragMode();
			}

			$targetEntity = $this->getEntityByRequestData($targetEntityData);
			$result = null;

			foreach ($draggedEntityDataList as $draggedEntityData) {
				$draggedEntity = $this->getEntityByRequestData($draggedEntityData);
				$validDragModes = $this->getValidDragModes($targetEntity, $draggedEntity);

				if (!in_array($dragMode, $validDragModes)) {
					throw new wrongParamException('Incorrect drag mode given');
				}

				try {
					$draggedEntity->move($targetEntity, $dragMode);
					$draggedEntity->commit();

					$result = $this->getSimpleSuccessMessage();
				} catch (Exception $e) {
					$result = $this->getSimpleErrorMessage(
						$e->getMessage()
					);
				}
			}

			$this->module->printJson(
				$this->prepareTableControlResult($result)
			);
		}

		/**
		 * Возвращает сущность по ее идентификатору и типу
		 * @param int|null $entityId идентификатор сущности
		 * @param string|null $entityType тип сущности
		 * @return iUmiCollectionItem
		 * @throws wrongParamException
		 */
		public function getEntityByIdAndType($entityId = null, $entityType = null) {
			$collection = $this->getCollectionByEntityType($entityType);
			$entityId = $this->getEntityId($entityId);
			$entity = $collection->getById($entityId);

			if (!$entity instanceof iUmiCollectionItem) {
				throw new wrongParamException('Incorrect entity id given');
			}

			return $entity;
		}

		/**
		 * Возвращает сущность исходя из ее данных из запроса
		 * @param array $data данные сущности:
		 *
		 * [
		 *        'id' => идентификатор,
		 *        'type' => тип
		 * ]
		 *
		 * @return iUmiCollectionItem
		 * @throws wrongParamException
		 */
		public function getEntityByRequestData(array $data) {
			$entityId = isset($data['id']) ? $data['id'] : null;
			$entityType = isset($data['type']) ? $data['type'] : null;
			return $this->getEntityByIdAndType($entityId, $entityType);
		}

		/**
		 * Инициализует список опций значениями по умолчанию
		 * @return array список опций со значениями по умолчанию
		 * @throws Exception
		 */
		public function initOptions() {
			$result = [];

			$this->forEachOption(
				function ($group, $option, $settingGroup, $settingName, $initial, $extra) use (&$result) {
					$initialValue = $this->getDefaultValue($initial);

					$values = $initialValue;

					if (is_array($extra) && isset($extra['empty']) && is_array($initialValue)) {
						$values[0] = getLabel($extra['empty']);
					}

					if (is_array($values)) {
						ksort($values);
					}

					$result[$group][$option] = $values;
				}
			);

			return $result;
		}

		/**
		 * Возвращает значение по умолчанию
		 * @param string|mixed $initial если передана непустая строка,
		 * то она считается названием метода, который вернет значение по умолчанию
		 * @return mixed
		 */
		public function getDefaultValue($initial) {
			if (is_string($initial) && mb_strlen($initial) > 0) {
				return $this->$initial();
			}

			return $initial;
		}

		/**
		 * Выполняет функцию обратного вызова для всех опций
		 * @param callable $callback выполняется для каждой опции
		 * @throws Exception
		 */
		public function forEachOption(Callable $callback) {
			if (!isset($this->settings) || !isset($this->options)) {
				throw new Exception('$this->settings или $this->options не определен');
			}

			$settings = $this->settings;

			foreach ($this->options as $group => $optionDetails) {

				foreach ($optionDetails as $option => $settingsDetails) {
					$settingsClass = get_class($settings);
					$settingGroup = constant("{$settingsClass}::{$settingsDetails['group']}");
					$settingName = $settingsDetails['name'];
					$initial = isset($settingsDetails['initialValue']) ? $settingsDetails['initialValue'] : null;
					$extra = isset($settingsDetails['extra']) ? $settingsDetails['extra'] : null;

					$callback($group, $option, $settingGroup, $settingName, $initial, $extra);
				}
			}
		}

		/**
		 * Возвращает список сущностей для нового табличного контрола
		 * @param string $serviceName имя сервиса, который отвечает за работу с сущностями
		 * @param array $fieldNames массив идентификаторов полей сущностей
		 * @return array
		 * @throws Exception
		 */
		public function getEntitiesForTable($serviceName, array $fieldNames) {
			$limit = (int) getRequest('per_page_limit');
			$limit = ($limit === 0) ? 25 : $limit;
			$currentPage = (int) getRequest('p');
			$offset = $currentPage * $limit;

			$serviceContainer = ServiceContainerFactory::create();
			/** @var iUmiCollection|iUmiConstantMapInjector $collection */
			$collection = $serviceContainer->get($serviceName);
			$collectionMap = $collection->getMap();
			$result = [];
			$total = 0;

			try {
				$queryParams = [
					$collectionMap->get('OFFSET_KEY') => $offset,
					$collectionMap->get('LIMIT_KEY') => $limit,
					$collectionMap->get('LIKE_MODE_KEY') => [],
					$collectionMap->get('COMPARE_MODE_KEY') => []
				];

				$filtersKey = 'fields_filter';
				$filters = (isset($_REQUEST[$filtersKey]) && is_array($_REQUEST[$filtersKey]))
					? $_REQUEST[$filtersKey]
					: [];

				foreach ($filters as $fieldName => $fieldInfo) {
					if (!in_array($fieldName, $fieldNames)) {
						continue;
					}

					foreach ($fieldInfo as $mode => $fieldValue) {
						if ($fieldValue === null || $fieldValue === '') {
							continue 2;
						}

						if ($mode == 'like') {
							$queryParams[$collectionMap->get('LIKE_MODE_KEY')][$fieldName] = true;
						} elseif (in_array($mode, ['ge', 'le', 'gt', 'lt', 'eq', 'ne'])) {
							$queryParams[$collectionMap->get('COMPARE_MODE_KEY')][$fieldName] = $mode;
						}

						$queryParams[$fieldName] = $fieldValue;
					}
				}

				$defaultOrder = [
					$collectionMap->get('ID_FIELD_NAME') => $collectionMap->get('ORDER_DIRECTION_DESC')
				];

				$orders = (isset($_REQUEST['order_filter']) && is_array($_REQUEST['order_filter']))
					? $_REQUEST['order_filter']
					: $defaultOrder;

				if (umiCount($orders) > 0) {
					$queryParams[$collectionMap->get('ORDER_KEY')] = $orders;
				}

				$entities = $collection->export($queryParams);

				$result['data'] = $entities;
				$total = $collection->count([
					$collectionMap->get('CALCULATE_ONLY_KEY') => true,
				]);
			} catch (Exception $e) {
				$result['data']['error'] = $e->getMessage();
			}

			$result['data']['offset'] = $offset;
			$result['data']['per_page_limit'] = $limit;
			$result['data']['total'] = $total;

			return $result;
		}

		/**
		 * Устанавливает размеры колонок табличного контрола по умолчанию в его конфигурацию.
		 * @param array $config конфигурация табличного контрола
		 * @return array измененная конфигурация
		 */
		public function setDefaultColumnsSizes(array $config) {
			$defaultSizesPart = [];

			foreach ($config['fields'] as $fieldConfig) {
				if (!is_array($fieldConfig) || !isset($fieldConfig['default_size'], $fieldConfig['name'])) {
					continue;
				}

				$defaultSizesPart[] = $fieldConfig['name'] . '[' . $fieldConfig['default_size'] . ']';
			}

			$config['default'] = implode('|', $defaultSizesPart);
			return $config;
		}

		/**
		 * Устанавливает заголовки для элементов (методов или полей) конфигурации табличного контрола.
		 * @param array $config конфигурация элементов
		 * @param string $labelPrefix префикс для языковой метки
		 * @param string $nameKey ключ названия элемента
		 * @return array измененная конфигурация
		 */
		public function setConfigItemsTitles(array $config, $labelPrefix, $nameKey) {
			/** @var baseModuleAdmin|iModulePart $this */
			$module = $this->getModuleName();

			foreach ($config as &$itemConfig) {
				if (!(is_array($itemConfig) || isset($itemConfig[$nameKey]))) {
					continue;
				}

				$itemConfig['title'] = getLabel($labelPrefix . $itemConfig[$nameKey], $module);
			}

			return $config;
		}

		/**
		 * Устанавливает модуль для методов в конфигурации табличного контрола.
		 * @param array $config конфигурация методов сущности
		 * @return array измененная конфигурация
		 */
		public function setMethodsModule(array $config) {
			/** @var baseModuleAdmin|iModulePart $this */
			$module = $this->getModuleName();

			foreach ($config as &$fieldConfig) {
				if (!is_array($fieldConfig)) {
					continue;
				}

				$fieldConfig['module'] = $module;
			}

			return $config;
		}

		/**
		 * Возвращает значение нумерованного параметра запроса, например param0, param1
		 * @param int $number номер параметра
		 * @return bool|null
		 */
		public function getNumberedParameter($number) {
			$key = 'param' . $number;
			return getRequest($key);
		}

		/**
		 * Получает и возвращает массив данных сущностей, которые требуется удалить
		 * @return array
		 */
		public function getEntitiesDataForDeleting() {
			return (array) getRequest('element');
		}

		/**
		 * Валидирует и возвращает валидные данные сущностей
		 * @param array $entitiesData данные сущностей
		 * @return array
		 */
		public function getValidEntitiesData(array $entitiesData) {
			$validatedEntitiesData = [];

			foreach ($entitiesData as $entityData) {
				if (!is_array($entityData)) {
					continue;
				}

				if (!isset($entityData['id'])) {
					continue;
				}

				if (!isset($entityData['__type'])) {
					continue;
				}

				$validatedEntitiesData[] = $entityData;
			}

			return $validatedEntitiesData;
		}

		/**
		 * Возвращает допустимые режимы перемещения элементов
		 * @param iUmiCollectionItem $targetElement цель перемещения
		 * @param iUmiCollectionItem $draggedElement перемещаемый элемент
		 * @return array
		 * @throws RequiredPropertyHasNoValueException
		 */
		public function getValidDragModes(
			iUmiCollectionItem $targetElement = null,
			iUmiCollectionItem $draggedElement = null
		) {
			return [
				'after',
				'before',
				'child'
			];
		}

		/**
		 * Удаляет сущности заданых коллекций и возвращает результат удаления
		 * @param array $entitiesData данные удаляемых сущностей
		 * @param iUmiCollection[] $collectionList экземпляры коллекций, к которым относятся удаляемые сущности
		 * @return array
		 * @throws wrongParamException
		 */
		public function deleteCollectionsEntities(array $entitiesData, array $collectionList) {
			/** @var iUmiService|iUmiCollection $collection */
			foreach ($collectionList as $collection) {
				if (!$collection instanceof iUmiCollection) {
					throw new wrongParamException('Incorrect service given');
				}

				$serviceCollectionItemClass = $collection->getCollectionItemClass();
				$serviceEntitiesId = [];
				/** @var array $entityData */
				foreach ($entitiesData as $key => $entityData) {
					if ($entityData['__type'] != $serviceCollectionItemClass) {
						continue;
					}

					$serviceEntitiesId[] = $entityData['id'];
					unset($entitiesData[$key]);
				}

				try {
					$this->deleteCollectionEntities($collection, $serviceEntitiesId);
				} catch (Exception $e) {
					return $this->getSimpleErrorMessage(
						$e->getMessage()
					);
				}
			}

			return $this->getSimpleSuccessMessage();
		}

		/**
		 * Возвращает массив экземпляров коллекций, с которыми работает модуль.
		 * @return iUmiCollection[]
		 */
		public function getModuleCollections() {
			return [];
		}

		/**
		 * Возвращает конфигурацию полей сущности
		 * @param string $entityClass имя класса сущности
		 * @param iUmiConstantMap $entityFieldsConstantsMap карта констант, в которой хранятся константы полей
		 * @return array
		 */
		public function getEntityFieldsConfig($entityClass, \iUmiConstantMap $entityFieldsConstantsMap) {
			return [];
		}

		/**
		 * Производит обработку значения поля сущности перед его сохранением.
		 * Возвращает обработанные данные.
		 * @param iUmiCollectionItem $entity сущность, которой принадлежит поле
		 * @param iUmiCollection $collection коллекция, которой принадлежит сущность
		 * @param string $fieldName имя поля
		 * @param mixed $fieldValue значение поля
		 * @return mixed
		 */
		public function callbackBeforeSavingEntityField(
			iUmiCollectionItem $entity,
			iUmiCollection $collection,
			$fieldName,
			$fieldValue
		) {
			return $this->callbackFieldValueBeforeSave($fieldName, $fieldValue, $entity, $collection);
		}

		/**
		 * Производит обработку данных удаляемых сущностей до их удаления.
		 * Возвращает обработанные данные.
		 * @param array $entitiesData данные удаляемых сущностей:
		 *
		 * [
		 *        0 =>
		 *            [
		 *                'id' => 1,
		 *                'type' => 'Type'
		 *            ]
		 * ]
		 *
		 * @return array
		 */
		public function callbackBeforeDeletingEntities(array $entitiesData) {
			return $entitiesData;
		}

		/**
		 * Производит обработку данных формы редактирования сущности и ее исходных данных.
		 * Возвращает обработанные данные.
		 * @param array $formData данные формы редактирования сущности
		 * @param iUmiCollectionItem $entity сущность
		 * @param array $fieldsConfig конфигурация полей сущности
		 * @return array
		 */
		public function callbackBeforeReturnEditFormData(
			array $formData,
			iUmiCollectionItem $entity,
			array $fieldsConfig
		) {
			return $formData;
		}

		/**
		 * Производит обработку данных формы создания сущности и ее исходных данных.
		 * Возвращает обработанные данные.
		 * @param array $formData данные формы редактирования сущности
		 * @param iUmiCollection $collection коллекция добавляемой сущности
		 * @param array $fieldsConfig конфигурация полей сущности
		 * @return array
		 */
		public function callbackBeforeReturnCreateFormData(
			array $formData,
			iUmiCollection $collection,
			array $fieldsConfig
		) {
			return $formData;
		}

		/**
		 * Производит обработку данных формы редактирования сущности перед их сохранением в сущность.
		 * Возвращает обработанные данные.
		 * @param array $editFormData данные формы редактирования сущности
		 * @param iUmiCollectionItem $entity сущность
		 * @return array
		 */
		public function callbackBeforeSaveEditFormDataToEntity(array $editFormData, iUmiCollectionItem $entity) {

			foreach ($editFormData as $fieldName => &$fieldValue) {
				$fieldValue = $this->callbackFieldValueBeforeSave($fieldName, $fieldValue, $entity);
			}

			return $editFormData;
		}

		/**
		 * Производит обработку данных формы создания сущности перед их сохранением в сущность.
		 * Возвращает обработанные данные.
		 * @param array $createFormData данные формы создания сущности
		 * @param iUmiCollection $collection коллекция, к которой принадлежит создаваемая сущность
		 * @return array
		 */
		public function callbackBeforeCreateEntityFromFormData(array $createFormData, iUmiCollection $collection) {
			$entity = null;

			foreach ($createFormData as $fieldName => &$fieldValue) {
				$fieldValue = $this->callbackFieldValueBeforeSave($fieldName, $fieldValue, $entity, $collection);
			}

			return $createFormData;
		}

		/**
		 * Производит обработку сущности после ее создания из данных формы создания сущностей.
		 * Возвращает обработанные данные.
		 * @param iUmiCollectionItem $entity созданная сущность
		 * @param iUmiCollection $collection коллекция, к которой принадлежит созданная сущность
		 * @return iUmiCollectionItem
		 */
		public function callbackAfterCreateEntityFromFormData(
			iUmiCollectionItem $entity,
			iUmiCollection $collection
		) {
			return $entity;
		}

		/**
		 * Производит обработку данных поля сушности до ее сохранения или добавления
		 * @param string $fieldName имя поля
		 * @param mixed $fieldValue значение поля
		 * @param iUmiCollectionItem|null $entity сущность
		 * @param iUmiCollection|null $collection коллекция, к которой относится сущность
		 * @return mixed
		 */
		public function callbackFieldValueBeforeSave(
			$fieldName,
			$fieldValue,
			iUmiCollectionItem $entity = null,
			iUmiCollection $collection = null
		) {
			return $fieldValue;
		}

		/**
		 * Возвращает настройки отображения поля сущности в табличном контроле
		 * @param string $entityClass имя класса сущности
		 * @param iUmiConstantMap $constantsMap карта констант сущности или ее коллекции
		 * @param string $fieldName имя поля
		 * @return array
		 */
		public function getTableControlFieldConfig($entityClass, iUmiConstantMap $constantsMap, $fieldName) {
			$tableControlFieldsConfig = $this->getEntityFieldsConfig($entityClass, $constantsMap);

			foreach ($tableControlFieldsConfig as $fieldConfig) {
				if (isset($fieldConfig['name']) && $fieldConfig['name'] == $fieldName) {
					return $fieldConfig;
				}
			}

			return [];
		}

		/**
		 * Формирует и возвращает простое результирующее сообщение об успешном окончании действия
		 * @param mixed $message
		 * @return array
		 * @throws RequiredPropertyHasNoValueException
		 */
		public function getSimpleErrorMessage($message = true) {
			return [
				'error' => $message
			];
		}

		/**
		 * Формирует и возвращает простое результирующее сообщение об ошибочном окончании действия
		 * @param mixed $message
		 * @return array
		 * @throws RequiredPropertyHasNoValueException
		 */
		public function getSimpleSuccessMessage($message = true) {
			return [
				'success' => $message
			];
		}

		/**
		 * Удаляет сущности коллекции и возвращает результат удаления
		 * @param iUmiCollection $collection экземпляр колллекции
		 * @param array $entitiesIds идентификаторы удаляемых сущностей
		 * @return bool
		 */
		public function deleteCollectionEntities(iUmiCollection $collection, array $entitiesIds) {
			$result = [];

			if (umiCount($entitiesIds) === 0) {
				return $result;
			}

			/** @var iUmiConstantMapInjector|iUmiCollection $collection */
			return $collection->delete(
				[
					$collection->getMap()->get('ID_FIELD_NAME') => $entitiesIds
				]
			);
		}

		/**
		 * Устанавливает результат удаления для табличного контрола
		 * @param array $result результат удаления
		 */
		public function setDataSetDeleteResult(array $result) {
			$this->setDataType('list');
			$this->setActionType('view');
			$this->setData(['data' => $result]);
			$this->doData();
		}

		/**
		 * Устанавливает результат вывода и сохранения настроек
		 * @param array $result результат вывода и сохранения настроек
		 * @param string $action действие (modify/view)
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function setConfigResult(array $result, $action = 'modify') {
			if (!in_array($action, ['view', 'modify'])) {
				throw new publicAdminException("Wrong action: $action");
			}

			$this->setDataType('settings');
			$this->setActionType($action);
			$data = $this->prepareData($result, 'settings');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Определяет, является ли текущий запрос запросом на сохранение данных
		 * @param string $paramName Название параметра, по которому определяется тип запроса
		 * @return bool
		 */
		public function isSaveMode($paramName = 'param0') {
			return getRequest($paramName) === 'do';
		}

		/**
		 * Возвращает префикс для ключей реестра текущего модуля
		 * @return string
		 */
		public function getRegistryPrefix() {
			/** @var baseModuleAdmin|iModulePart $this */
			return '//modules/' . $this->getModuleName() . '/';
		}

		/**
		 * Возвращает опции настроек модуля
		 * @return array
		 */
		public function getConfigOptions() {
			return [];
		}

		/**
		 * Возвращает название опции для вывода в шаблон
		 * @param string $optionType тип значения опции
		 * @param string $optionName название опции
		 * @return string
		 * @throws RequiredPropertyHasNoValueException
		 */
		public function getConfigOptionName($optionType, $optionName) {
			return $optionType . ':' . $optionName;
		}

		/**
		 * Возвращает данные из запроса по ключу, если они там есть, иначе генерирует исключение
		 * @param string $key ключ данных
		 * @return mixed
		 * @throws wrongParamException
		 */
		public function getRequestParamValue($key) {
			if (!isset($_REQUEST[$key])) {
				throw new wrongParamException('Field ' . $key . ' value expected');
			}

			return getRequest($key);
		}

		/**
		 * Устанавливает данные модуля в реестре и возвращает результат операции
		 * @param string $key ключ
		 * @param mixed $value данные
		 * @return mixed
		 * @throws privateException
		 */
		public function setRegistryValue($key, $value) {
			if (!$this instanceof iUmiRegistryInjector) {
				throw new privateException('Current class must implement iUmiRegistryInjector to use it method');
			}
			/** @var iUmiRegistryInjector|baseModuleAdmin $this */
			return $this->getRegistry()
				->set($this->getRegistryKey($key), $value);
		}

		/**
		 * Возвращает данные модуля из реестра по ключу
		 * @param string $key ключ данных
		 * @return mixed
		 * @throws privateException
		 */
		public function getRegistryValue($key) {
			if (!$this instanceof iUmiRegistryInjector) {
				throw new privateException('Current class must implement iUmiRegistryInjector to use it method');
			}
			/** @var iUmiRegistryInjector|baseModuleAdmin $this */
			return $this->getRegistry()
				->get(
					$this->getRegistryKey($key)
				);
		}

		/**
		 * Формирует и возвращает ключ для данных модуля в реестре
		 * @param string $key ключ данных
		 * @return string
		 */
		public function getRegistryKey($key) {
			return $this->getRegistryPrefix() . $key;
		}

		/**
		 * Устанавливает результат прямого запроса метода, являющегося методом вкладки модуля и методом для получения
		 * списка сущностей.
		 * Используется для того, чтобы не формировать список сущностей при запросе вкладки модуля.
		 * Обычно метод вкладки со списком сущностей и метод, возвращающим сущности, одинаковы.
		 * Поэтому для формирования вкладки сущности не возвращаются, они потом запрашиваются средствами ajax.
		 */
		public function setDataSetDirectCallMessage() {
			$this->setDataType('list');
			$this->setActionType('view');
			$this->setDirectCallError();
			$this->doData();
		}

		/**
		 * Оформляет список сущностей для табличного контрола и возвращает результат
		 * @param array $entities список сущностей
		 * @param int $total общее количество сущностей
		 * @return array
		 * @throws RequiredPropertyHasNoValueException
		 */
		public function prepareTableControlEntities(array $entities, $total) {
			$this->setDataType('list');
			$this->setActionType('view');
			$limit = $this->getLimit();
			$offset = $this->getOffset($limit);

			$entities += [
				'per_page_limit' => $limit,
				'offset' => $offset,
				'total' => $total,
			];

			return $this->prepareTableControlResult($entities);
		}

		/**
		 * Оформляет результат работы метода табличного контрола
		 * @param array $result результат работы метода табличного контрола
		 * @return array
		 */
		public function prepareTableControlResult(array $result) {
			return [
				'data' => $result
			];
		}

		/**
		 * Выводит в буффер результат сохранения изменения через быстрое редактирование табличного контрола
		 * @param mixed $result результат
		 */
		public function setDataSetEditFieldResult($result) {
			Service::Response()
				->printJson(['data' => $result]);
		}

		/**
		 * Возвращает данные формы редактирования сущности
		 * @param iUmiCollectionItem $entity сущность
		 * @return array
		 * @throws RequiredPropertyHasNoValueException
		 */
		public function getEditFormDataOfEntity(iUmiCollectionItem $entity) {
			/** @var iUmiCollectionItem|iUmiConstantMapInjector $entity */
			$fieldsConfig = $this->getEntityFieldsConfig(
				get_class($entity),
				$entity->getMap()
			);

			$deletingEnabled = true;
			$actionMethod = $this->getEntitiesSaveMethod($entity);

			$formData = $this->getEntityFormData(
				$fieldsConfig,
				$deletingEnabled,
				$actionMethod,
				$entity
			);

			$formData = $this->callbackBeforeReturnEditFormData($formData, $entity, $fieldsConfig);
			return $formData;
		}

		/**
		 * Возвращает данные формы создания сущности коллекции
		 * @param iUmiCollection $collection коллекция сущностей
		 * @return array
		 */
		public function getCreateFormDataOfEntity(iUmiCollection $collection) {
			/** @var iUmiCollection|iUmiConstantMapInjector $collection */
			$fieldsConfig = $this->getEntityFieldsConfig(
				$collection->getCollectionItemClass(),
				$collection->getMap()
			);

			$deletingEnabled = false;
			$actionMethod = $this->getEntitiesCreateMethod();

			$formData = $this->getEntityFormData(
				$fieldsConfig,
				$deletingEnabled,
				$actionMethod
			);

			$formData = $this->callbackBeforeReturnCreateFormData($formData, $collection, $fieldsConfig);
			return $formData;
		}

		/**
		 * Возвращает данные для формы редактирования или добаления сущности,
		 * если сущность передана - значения полей будут заполнены.
		 * @param array $fieldsConfig конфигурация полей сущности
		 * @param string $deletingEnabled разрешено ли удаление сущности в ее форме
		 * @param string $actionMethod метод для action'а формы
		 * @param iUmiCollectionItem|null $entity сущность
		 * @return array
		 * @throws RequiredPropertyHasNoValueException
		 */
		public function getEntityFormData(
			array $fieldsConfig,
			$deletingEnabled,
			$actionMethod,
			iUmiCollectionItem $entity = null
		) {
			$entityDefined = $entity !== null;
			$formData = [];

			foreach ($fieldsConfig as $key => &$fieldConfig) {
				if (!isset($fieldConfig['name'], $fieldConfig['type'])) {
					continue;
				}

				$fieldValue = $entityDefined ? $entity->getValue($fieldConfig['name']) : null;
				$isFieldRequired = isset($fieldConfig['required']) ? $fieldConfig['required'] : null;
				$hint = isset($fieldConfig['hint']) ? $fieldConfig['hint'] : null;

				$fieldData = [
					'@name' => $fieldConfig['name'],
					'@type' => $fieldConfig['type'],
					'@required' => $isFieldRequired,
					'@title' => $fieldConfig['title'],
					'@hint' => $hint,
					'#value' => $fieldValue
				];

				$formData[] = $fieldData;
			}

			$entityId = $entityDefined ? $entity->getId() : 'new';

			return [
				'@entity_id' => $entityId,
				'@ui_type' => 'ndc',
				'@deleting_enabled' => (int) $deletingEnabled,
				'@form_action' => $actionMethod,
				'fields' => [
					'+field' => $formData
				]
			];
		}

		/**
		 * Сохраняет данные формы редактирования в сущность и возвращает ее
		 * @param iUmiCollectionItem $entity сущность
		 * @param array $editFormData данные формы редактирования сущности
		 * @return iUmiCollectionItem
		 * @throws wrongParamException
		 * @throws publicAdminException
		 */
		public function saveFormDataToEntity(iUmiCollectionItem $entity, array $editFormData) {
			$entityId = $entity->getId();

			if (!isset($editFormData[$entityId])) {
				throw new wrongParamException('There are no data for entity');
			}

			$editFormData = $this->callbackBeforeSaveEditFormDataToEntity(
				$editFormData[$entityId],
				$entity
			);

			try {
				$entity->import($editFormData);
				$entity->commit();
			} catch (Exception $e) {
				throw new publicAdminException($e->getMessage());
			}

			return $entity;
		}

		/**
		 * Создает и возвращает новую сущность, заполняее ее данными формы
		 * @param iUmiCollection $collection коллекция, к которой относится новая сущность
		 * @param array $createFormData данные формы создания сущности
		 * @return iUmiCollectionItem
		 * @throws wrongParamException
		 * @throws publicAdminException
		 */
		public function createEntityFromFormData(iUmiCollection $collection, array $createFormData) {
			$entityId = 'new';

			if (!isset($createFormData[$entityId])) {
				throw new wrongParamException('There are no data for entity');
			}

			$createFormData = $this->callbackBeforeCreateEntityFromFormData(
				$createFormData[$entityId],
				$collection
			);

			try {
				$entity = $collection->create($createFormData);
			} catch (Exception $e) {
				throw new publicAdminException($e->getMessage());
			}

			$entity = $this->callbackAfterCreateEntityFromFormData(
				$entity,
				$collection
			);

			return $entity;
		}

		/**
		 * Возвращает данные формы создания сущности из запроса на сохранение сущности
		 * @return array
		 */
		public function getCreateFormDataFromSaveRequest() {
			return $this->getEditFormDataFromSaveRequest();
		}

		/**
		 * Возвращает данные формы редактирования сущности из запроса на сохранение сущности
		 * @return array
		 */
		public function getEditFormDataFromSaveRequest() {
			return isset($_REQUEST['data']) ? (array) getRequest('data') : [];
		}

		/**
		 * Устанавливает результат для данных формы редактирования
		 * @param array $editFormData данные формы редактирования
		 */
		public function setDataSetEditFormResult(array $editFormData) {
			$this->setDataType('form');
			$this->setActionType('modify');
			$this->setData($editFormData);
			$this->doData();
		}

		/**
		 * Устанавливает результат для данных формы создания
		 * @param array $createFormData данные формы создания
		 */
		public function setDataSetCreateFormResult(array $createFormData) {
			$this->setDataType('form');
			$this->setActionType('create');
			$this->setData($createFormData);
			$this->doData();
		}

		/**
		 * Возвращает сервис по его имени
		 * @param string $serviceName имя сервиса
		 * @return iUmiService
		 * @throws wrongParamException
		 */
		public function getService($serviceName) {
			try {
				return ServiceContainerFactory::create()
					->get($serviceName);
			} catch (Exception $e) {
				throw new wrongParamException($e->getMessage());
			}
		}

		/**
		 * Возвращает идентификатор сущности
		 * @param int|null $entityId идентификатор сущности
		 * @return int|null
		 */
		public function getEntityId($entityId = null) {
			if ($entityId === null) {
				$entityId = $this->getNumberedParameter(0);
			}

			return $entityId;
		}

		/**
		 * Возвращает экземпляр коллекции сущностей, которая отвечает за
		 * работу с сущностями заданного типа
		 * @param string|null $entityType тип сущности коллекции
		 * @return iUmiCollection|iUmiService
		 * @throws wrongParamException
		 */
		public function getCollectionByEntityType($entityType = null) {
			if ($entityType === null) {
				$entityType = getRequest('type');
			}

			/** @var iUmiService|iUmiCollection $collection */
			foreach ($this->getModuleCollections() as $collection) {
				if ($collection->getCollectionItemClass() == $entityType) {
					return $collection;
				}
			}

			throw new wrongParamException('Cannot get collection for entity');
		}

		/**
		 * Возвращает параметры выборки сущностей для учета пагинации
		 * @param iUmiConstantMap $constantsMap карта констант коллекции сущностей
		 * @param int|null $limit ограничение на количество результатов выборки
		 * @param int|null $offset выборки смещение результатов выборки
		 * @return array
		 */
		public function getPageNavigationQueryParams(iUmiConstantMap $constantsMap, $limit = null, $offset = null) {
			if ($limit === null) {
				$limit = (int) $this->getLimit();
			}

			if ($offset === null) {
				$offset = (int) $this->getOffset($limit);
			}

			return [
				$constantsMap->get('LIMIT_KEY') => $limit,
				$constantsMap->get('OFFSET_KEY') => $offset
			];
		}

		/**
		 * Возвращает параметры выборки сущностей для учета выбранного домена и языка
		 * @param iUmiConstantMap $constantsMap карта констант коллекции сущностей
		 * @param int|null $domainId идентификатор домена
		 * @param int|null $languageId идентификатор языка
		 * @return array
		 */
		public function getDomainAndLanguageQueryParams(
			iUmiConstantMap $constantsMap,
			$domainId = null,
			$languageId = null
		) {
			if ($domainId === null) {
				$domainId = (int) $this->getDomainId();
			}

			if ($languageId === null) {
				$languageId = (int) $this->getLanguageId();
			}

			return [
				$constantsMap->get('DOMAIN_ID_FIELD_NAME') => $domainId,
				$constantsMap->get('LANGUAGE_ID_FIELD_NAME') => $languageId
			];
		}

		/**
		 * Возвращает ограничение на количество сущностей в табличном контроле
		 * @return int
		 */
		public function getLimit() {
			$limit = (int) getRequest('per_page_limit');
			return ($limit === 0) ? $this->getDefaultPerPageNumber() : $limit;
		}

		/**
		 * Возвращает количество сущностей в табличном контроле по умолчанию
		 * @return int
		 */
		public function getDefaultPerPageNumber() {
			return 20;
		}

		/**
		 * Возвращает смещение результат выборки сущностей в табличном контроле
		 * @param int $limit
		 * @return int
		 */
		public function getOffset($limit) {
			$currentPage = $this->getCurrentPage();
			return $currentPage * (int) $limit;
		}

		/**
		 * Возвращает номер текущей страницы в рамках пагинации
		 * @return int
		 */
		public function getCurrentPage() {
			return (int) getRequest('p');
		}

		/**
		 * Возвращает данные сущности табличного контрола, относительно которой перемещаются другие сущности
		 * @return array
		 */
		public function getDragTargetEntityData() {
			return isset($_REQUEST['rel']) ? (array) getRequest('rel') : [];
		}

		/**
		 * Возвращает данные перемещаемых сущностей табличного контрола
		 * @return array
		 */
		public function getDraggedEntitiesData() {
			return isset($_REQUEST['selected_list']) ? (array) getRequest('selected_list') : [];
		}

		/**
		 * Возвращает режим перемещения элементов табличного контрола
		 * @return null|string
		 */
		public function getDragMode() {
			return isset($_REQUEST['mode']) ? (string) getRequest('mode') : null;
		}

		/**
		 * Переданы ли значения для фильтрации список сущностей табличного контрола
		 * @return bool
		 */
		public function isFilterExists() {
			return isset($_REQUEST['fields_filter']);
		}

		/**
		 * Возвращает идентификатор родительской сущности табличного контрола, если она передана
		 * @return int|null
		 */
		public function getRelationId() {
			$relationId = $this->getRequestFirstValue('rel');
			$rootRelationId = '0';
			return ($relationId === $rootRelationId) ? null : $relationId;
		}

		/**
		 * Возвращает идентификатор домена табличного контрола, если он передан
		 * @return int|null
		 */
		public function getDomainId() {
			return $this->getRequestFirstValue('domain_id');
		}

		/**
		 * Возвращает идентификатор языка табличного контрола, если он передан
		 * @return int|null
		 */
		public function getLanguageId() {
			return $this->getRequestFirstValue('lang_id');
		}

		/**
		 * Получает первое значение из массива значений параметра запроса.
		 * @param string $name название параметра
		 * @return mixed|null первое значение или null, если его не удалось получить
		 */
		public function getRequestFirstValue($name) {
			$value = getRequest($name);

			if (is_array($value) && umiCount($value) > 0) {
				return array_shift($value);
			}
			return null;
		}

		/**
		 * Возвращает параметры выборки сущностей коллекции для вычисления общего количества
		 * сущностей
		 * @param iUmiConstantMap $collectionConstants карта констант коллекции
		 * @return array
		 */
		public function getQueryParamsToCalcTotal(iUmiConstantMap $collectionConstants) {
			return [
				$collectionConstants->get('CALCULATE_ONLY_KEY') => true,
			];
		}

		/**
		 * Возвращает значения фильтров табличного контрола
		 * @return array
		 */
		public function getFilterValues() {
			return $this->getRequestArray('fields_filter');
		}

		/**
		 * Возвращает значения сортировки табличного контрола
		 * @return array
		 */
		public function getSortValues() {
			return $this->getRequestArray('order_filter');
		}

		/**
		 * Формирует и возвращает данные запроса элементов коллекции на основе данных переданных фильтров
		 * @param iUmiCollection $collection коллекция
		 * @return array
		 * @throws RequiredPropertyHasNoValueException
		 * @throws wrongParamException
		 */
		public function getEntitiesFilterQueryParams(iUmiCollection $collection) {
			/** @var iUmiConstantMapInjector|iUmiCollection $collection */
			$collectionConstantMap = $collection->getMap();

			$entityFieldsConfig = $this->getEntityFieldsConfig(
				$collection->getCollectionItemClass(),
				$collectionConstantMap
			);

			$entityFields = array_map(function (array $config) {
					return $config['name'];
				},
				$entityFieldsConfig
			);

			$filters = $this->getFilterValues();
			$comparableFilterTypes = $this->getComparableFilterTypes();
			$likeModeKey = $collectionConstantMap->get('LIKE_MODE_KEY');
			$compareModeKey = $collectionConstantMap->get('COMPARE_MODE_KEY');
			$queryParams = [];

			foreach ($filters as $fieldName => $fieldInfo) {
				if (!in_array($fieldName, $entityFields)) {
					continue;
				}

				foreach ($fieldInfo as $mode => $fieldValue) {
					if ($fieldValue === null || $fieldValue === '') {
						continue 2;
					}

					if ($mode == 'like') {
						$queryParams[$likeModeKey][$fieldName] = true;
					} elseif (in_array($mode, $comparableFilterTypes)) {
						$queryParams[$compareModeKey][$fieldName] = $mode;
					}

					$queryParams[$fieldName] = $fieldValue;
				}
			}

			return $queryParams;
		}

		/**
		 * Возвращает типы фильтрации, при которых производится сравнение
		 * @return array
		 */
		public function getComparableFilterTypes() {
			return [
				'ge',
				'le',
				'gt',
				'lt',
				'eq',
				'ne'
			];
		}

		/**
		 * Выполняет перенаправление после добавления сущности
		 * @param iUmiCollectionItem $entity добавленная сущность
		 * @throws publicAdminException
		 */
		public function redirectAfterCreateEntity($entity) {
			$saveMode = $this->getFormSaveMode();
			$redirectAddress = '/';

			switch ($saveMode) {
				case getLabel('label-save-add') : {
					$redirectAddress = $this->getRedirectAddressAfterCreate($entity);
					break;
				}
				case getLabel('label-save-add-exit') : {
					$redirectAddress = $this->getRedirectAddressAfterCreateAndExit($entity);
					break;
				}
				case getLabel('label-save-add-view') : {
					$redirectAddress = $this->getRedirectAddressAfterCreateAndLook($entity);
					break;
				}
			}

			$this->redirect($redirectAddress);
		}

		/**
		 * Выполняет перенаправление после сохранения изменений сущности
		 * @param iUmiCollectionItem $entity сохраненная сущность
		 * @throws publicAdminException
		 */
		public function redirectAfterSaveEditForm(iUmiCollectionItem $entity) {
			$saveMode = $this->getFormSaveMode();
			$redirectAddress = '/';

			switch ($saveMode) {
				case getLabel('label-save') : {
					$redirectAddress = $this->getRedirectAddressAfterSave($entity);
					break;
				}
				case getLabel('label-save-exit') : {
					$redirectAddress = $this->getRedirectAddressAfterSaveAndExit($entity);
					break;
				}
				case getLabel('label-save-view') : {
					$redirectAddress = $this->getRedirectAddressAfterSaveAndLook($entity);
					break;
				}
			}

			$this->redirect($redirectAddress);
		}

		/**
		 * Выполняет перенаправление
		 * @param string $address адрес перенаправления
		 */
		public function redirect($address) {
			Service::Response()
				->getCurrentBuffer()
				->redirect($address);
		}

		/**
		 * Возвращает режим сохранения формы
		 * @return string
		 * @throws publicAdminException
		 */
		public function getFormSaveMode() {
			if (!isset($_REQUEST['save-mode'])) {
				throw new publicAdminException('Save mode not defined');
			}

			return (string) getRequest('save-mode');
		}

		/**
		 * Возвращает адрес перенаправления после сохранения в режиме "Сохранить"
		 * @param iUmiCollectionItem $entity сохраненная сущность
		 * @return string
		 */
		public function getRedirectAddressAfterSave(iUmiCollectionItem $entity) {
			return $this->getPageWithEntityEditFormLink($entity);
		}

		/**
		 * Возвращает адрес перенаправления после сохранения в режиме "Сохранить и выйти"
		 * @param iUmiCollectionItem $entity сохраненная сущность
		 * @return string
		 */
		public function getRedirectAddressAfterSaveAndExit(iUmiCollectionItem $entity) {
			return $this->getPageWithEntitiesListLink($entity);
		}

		/**
		 * Возвращает адрес перенаправления после сохранения в режиме "Сохранить и посмотреть"
		 * @param iUmiCollectionItem $entity сохраненная сущность
		 * @return string
		 */
		public function getRedirectAddressAfterSaveAndLook(iUmiCollectionItem $entity) {
			return '/';
		}

		/**
		 * Возвращает адрес перенаправления после добавления в режиме "Добавить"
		 * @param iUmiCollectionItem $entity добавленная сущность
		 * @return string
		 */
		public function getRedirectAddressAfterCreate(iUmiCollectionItem $entity) {
			return $this->getPageWithEntityEditFormLink($entity);
		}

		/**
		 * Возвращает адрес перенаправления после добавления в режиме "Добавить и выйти"
		 * @param iUmiCollectionItem $entity добавленная сущность
		 * @return string
		 */
		public function getRedirectAddressAfterCreateAndExit(iUmiCollectionItem $entity) {
			return $this->getPageWithEntitiesListLink($entity);
		}

		/**
		 * Возвращает адрес перенаправления после добавления в режиме "Добавить и посмотреть"
		 * @param iUmiCollectionItem $entity добавленная сущность
		 * @return string
		 */
		public function getRedirectAddressAfterCreateAndLook(iUmiCollectionItem $entity) {
			return '/';
		}

		/**
		 * Возвращает ссылку на страницу со списком сущностей с таким же типом,
		 * как у заданной сущности
		 * @param iUmiCollectionItem $entity сущность
		 * @return string
		 */
		public function getPageWithEntitiesListLink(iUmiCollectionItem $entity) {
			$prefix = $this->getAdminRequestPrefix();
			/** @var baseModuleAdmin|iModulePart $this */
			$module = $this->getModuleName();
			$method = $this->getEntitiesListMethod($entity);

			return $prefix . '/' . $module . '/' . $method . '/';
		}

		/**
		 * Возвращает ссылку на страницу с формой редактирования сущности
		 * @param iUmiCollectionItem $entity сущность
		 * @return string
		 */
		public function getPageWithEntityEditFormLink(iUmiCollectionItem $entity) {
			$prefix = $this->getAdminRequestPrefix();
			/** @var baseModuleAdmin|iModulePart $this */
			$module = $this->getModuleName();
			$method = $this->getEntitiesEditFormMethod($entity);
			$id = $entity->getId();

			return $prefix . '/' . $module . '/' . $method . '/' . $id . '/';
		}

		/**
		 * Возвращает имя метода, который отвечает за данные для формы редактирования сущности
		 * @param iUmiCollectionItem|null $entity сущность
		 * @return string
		 */
		public function getEntitiesEditFormMethod(iUmiCollectionItem $entity = null) {
			return 'edit';
		}

		/**
		 * Возвращает имя метода, который отвечает за вывод списка сущностей с таким же типом,
		 * как у заданной сущности
		 * @param iUmiCollectionItem|null $entity сущность
		 * @return string
		 */
		public function getEntitiesListMethod(iUmiCollectionItem $entity = null) {
			return 'list';
		}

		/**
		 * Возвращает имя метода, который отвечает за удаление сущности
		 * @param iUmiCollectionItem|null $entity сущность
		 * @return string
		 */
		public function getEntitiesDeleteMethod(iUmiCollectionItem $entity = null) {
			return 'delete';
		}

		/**
		 * Возвращает имя метода, который отвечает за сохранения формы редактирования
		 * @param iUmiCollectionItem|null $entity сущность
		 * @return string
		 */
		public function getEntitiesSaveMethod(iUmiCollectionItem $entity = null) {
			return 'save';
		}

		/**
		 * Возвращает имя метода, который отвечает за сохранение поля
		 * @param iUmiCollectionItem|null $entity сущность
		 * @return string
		 */
		public function getEntitySaveFieldMethod(iUmiCollectionItem $entity = null) {
			return 'saveField';
		}

		/**
		 * Возвращает имя метода, который отвечает за сохранение поля
		 * @param iUmiCollectionItem|null $entity сущность
		 * @return string
		 */
		public function getEntitiesCreateMethod(iUmiCollectionItem $entity = null) {
			return 'create';
		}

		/**
		 * Возвращает имя метода, который отвечает за создание сущностей с типом, как
		 * у заданной сущности
		 * @param iUmiCollectionItem|null $entity сущность
		 * @return string
		 */
		public function getEntitiesCreateFormMethod(iUmiCollectionItem $entity = null) {
			return 'form';
		}

		/**
		 * Возвращает имя метода, который отвечает за перемещение сущностей с типом, как
		 * у заданной сущности
		 * @param iUmiCollectionItem|null $entity сущность
		 * @return string
		 */
		public function getEntitiesMoveFormMethod(iUmiCollectionItem $entity = null) {
			return 'move';
		}

		/**
		 * Возвращает префикс для запросов к методам административной панели
		 * @return string
		 */
		public function getAdminRequestPrefix() {
			return cmsController::getInstance()->getPreLang() . '/' . ModeDetector::ADMIN_MODE;
		}

		/**
		 * Возвращает настройки для подключения к сервису Яндекс.OAuth.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do, то сохраняет настройки.
		 * Запрашивает авторизационный токен для Яндекс.OAuth, если пользователь ввел код.
		 * @throws \coreException
		 */
		public function yandex() {
			$settings = [];
			$registry = $this->getTokenRegistry();

			if (!$registry instanceof iYandexTokenInjector) {
				throw new RuntimeException('Incorrect token registry given');
			}

			if ($registry->getYandexToken()) {
				$settings['yandex']['string:token'] = $registry->getYandexToken();
			} else {
				$settings['yandex']['string:code'] = '';
			}

			if ($this->isSaveMode()) {
				$settings = $this->expectParams($settings);

				if (isset($settings['yandex']['string:code']) && mb_strlen($settings['yandex']['string:code'])) {
					$token = $this->getYandexTokenByUserCode($settings['yandex']['string:code']);
				} else {
					$token = $settings['yandex']['string:token'];
				}

				$registry->setYandexToken($token);
				$this->chooseRedirect();
			}

			$settings['yandex']['string:client_id'] = $this->getYandexClientId();
			$this->setConfigResult($settings);
		}

		/**
		 * Возвращает идентификатор приложения Яндекса
		 * @return string
		 */
		protected function getYandexClientId() {
			throw new RuntimeException('You should override this method');
		}

		/**
		 * Возвращает пароль приложения Яндекса
		 * @return string
		 */
		protected function getYandexSecret() {
			throw new RuntimeException('You should override this method');
		}

		/**
		 * Возвращает реестр, где хранится авторизационный токен для Яндекса
		 * @return iYandexTokenInjector
		 */
		protected function getTokenRegistry() {
			throw new RuntimeException('You should override this method');
		}

		/**
		 * Возвращает авторизационный токен по коду, введенному пользователем
		 * @param int $code пользовательский код
		 * @return string
		 */
		private function getYandexTokenByUserCode($code) {
			try {
				$login = $this->getYandexClientId();
				$password = $this->getYandexSecret();
				return Service::YandexOAuthClient()
					->setAuth($login, $password)
					->getTokenByUserCode($code);
			} catch (\Exception $exception) {
				/** @var def_module $module */
				$module = $this->module;
				$module->errorNewMessage(getLabel('label-error-yandex-wrong-code'));
				$module->errorPanic();
			}
		}

		/** @deprecated */
		public function isFieldExist() {
			$this->setDataType('form');
			$this->setActionType('isFieldExist');

			$param = [
				'groupId' => getRequest('group-id'),
				'typeId' => getRequest('type-id')
			];

			$data = [
				'isExist' => $this->isChildGroupExist($param) ? 'true' : 'false'
			];

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает часть коллекции сущностей
		 * @param iCollection $collection коллекция сущностей
		 * @param int|null $limit размер части списка
		 * @param int|null $offset смещение части списка
		 * @return iCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function sliceEntityCollection(iCollection $collection, $limit = null, $offset = null) {
			$limit = $limit ?: $this->getLimit();
			$offset = $offset ?: $this->getOffset($limit);
			return $collection->slice($offset, $limit);
		}

		/**
		 * Добавляет параметры постраничной навигации в список атрибутов сущностей
		 * @param array $entityRowList список атрибутов сущностей
		 * @param int $totalCount общее количество сущностей
		 * @return array
		 */
		public function appendPageNavigation(array $entityRowList, $totalCount) {
			$limit = $this->getLimit();
			$offset = $this->getOffset($limit);
			return $entityRowList + [
				'per_page_limit' => $limit,
				'offset' => $offset,
				'total' => $totalCount,
			];
		}

		/**
		 * Устанавливает результат работы метода api табличного контрола
		 * @param array $result
		 */
		public function printEntityTableControlResult(array $result) {
			$this->setDataType('list');
			$this->setActionType('view');
			$this->setData($result);
			$this->doData();
		}

		/**
		 * Определяет нужно ли выполнять действие над переданными данными
		 * @return bool
		 */
		public function isNeedToDoAction() {
			return endsWith(Service::Request()->getPath(), 'do');
		}

		/**
		 * Определяет необходимости установки базового (иерахического) типа объектному типу
		 * @param iUmiObjectType $type объектный тип
		 * @param mixed $baseTypeId идентификатор базового (иерархического) типа
		 * @return bool
		 */
		private function isNeedToSetBaseTypeId(iUmiObjectType $type, $baseTypeId) {
			if (!$type->getHierarchyTypeId()) {
				return true;
			}

			if ($baseTypeId) {
				return true;
			}

			return $type->getIsGuidable() && !$type->getIsLocked();
		}

		/**
		 * Возвращает массив из запроса
		 * @param string $key ключ запроса
		 * @return array
		 */
		private function getRequestArray($key) {
			return (isset($_REQUEST[$key]) && is_array($_REQUEST[$key])) ? $_REQUEST[$key] : [];
		}
	}
