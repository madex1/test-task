<?php
	abstract class __json_content extends baseModuleAdmin {

		public function get_editable_region() {
			$itemId = getRequest('param0');
			$propName = getRequest('param1');
			$isObject = (bool) getRequest('is_object');

			$objects = umiObjectsCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$oEntity = ($isObject) ? $objects->getObject($itemId) : $hierarchy->getElement($itemId);

			// Checking rights
			$bDisallowed = false;
			$mainConfiguration = mainConfiguration::getInstance();
			$objectEditionAllowed = (bool) $mainConfiguration->get('system', 'allow-object-editing');
			$permissions = permissionsCollection::getInstance();
			$userId = $permissions->getUserId();
			$groupIds = $objects->getObject($userId)->getValue('groups');

			$systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();
			$svGroupId = $systemUsersPermissions->getSvGroupId();
			$svId = $systemUsersPermissions->getSvUserId();

			if ($userId != $svId && !in_array($svGroupId, $groupIds)) {
				if ($isObject) {
					$bDisallowed = !($oEntity->getOwnerId() == $userId);
					if ($bDisallowed) {
						$module = $oEntity->getModule();
						$method = $oEntity->getMethod();
						switch (true) {
							case ($module && $method): {
								$bDisallowed = !$permissions->isAllowedMethod($userId, $module, $method);
								break;
							}
							case $objectEditionAllowed: {
								$bDisallowed = false;
								break;
							}
							default: {
								throw new publicAdminException(getLabel('error-no-permissions'));
							}
						}
					}
				} else {
					list ($r, $w) = $permissions->isAllowedObject($userId, $itemId);
					if (!$w) $bDisallowed = true;
				}
			}

			if ($bDisallowed) {
				throw new publicAdminException(getLabel('error-no-permissions'));
			}

			$result = false;
			if ($oEntity) {
				switch($propName) {
					case "name":
						$result = array('name' => $oEntity->name);
					break;

					default:
						$oObject = (!$isObject)? $oEntity->getObject() : $oEntity;
						$prop = $oObject->getPropByName($propName);
						if (!$prop instanceof umiObjectProperty) {
							throw new publicAdminException(getLabel('error-property-not-exists'));
						}
						$result = array('property' => $prop);
						translatorWrapper::get($oObject->getPropByName($propName));
						umiObjectPropertyWrapper::$showEmptyFields = true;
				}
			}

			if (!is_array($result)) {
				throw new publicAdminException(getLabel('error-entity-not-exists'));
			}

			$this->setData($result);
			return $this->doData();
		}

		public function checkAllowedColumn(iUmiObject $object, $propName) {
			$userTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName('users', 'user')->getId();
			$isSv = permissionsCollection::getInstance()->isSv();
			$isObjectCustomer = $object->getTypeGUID() == 'emarket-customer';
			$isObjectUser = umiObjectTypesCollection::getInstance()->getType($object->getTypeId())->getHierarchyTypeId() == $userTypeId;

			$notAllowedProps = array('bonus', 'spent_bonus', 'filemanager_directory', 'groups');

			if (!$isSv && ($isObjectCustomer || $isObjectUser)) {
				if (in_array($propName, $notAllowedProps)) return false;
			}

			return true;
		}

		public function save_editable_region() {
			$iEntityId = getRequest('param0');
			$sPropName = getRequest('param1');
			$content = getRequest('data');
			$bIsObject = (bool) getRequest('is_object');

			if (is_array($content) && count($content) == 1) {
				$content = $content[0];
			} else if(is_array($content) && isset($content[0])) {
				$temp = array();
				foreach ($content as $item) {
					$temp[] = is_array($item) ? $item[0] : $item;
				}
				$content = $temp;
			}

			$oEntity = ($bIsObject) ? umiObjectsCollection::getInstance()->getObject($iEntityId) : umiHierarchy::getInstance()->getElement($iEntityId);

			// Checking rights
			$bDisallowed = false;
			$mainConfiguration = mainConfiguration::getInstance();
			$objectEditionAllowed = (bool) $mainConfiguration->get('system', 'allow-object-editing');
			$permissions = permissionsCollection::getInstance();
			$userId = $permissions->getUserId();

			if (!$permissions->isSv($userId)) {
				if($bIsObject) {
					$bDisallowed = !($oEntity->getOwnerId() == $userId);
					if($bDisallowed) {
						//Check module permissions
						$module = $oEntity->getModule();
						$method = $oEntity->getMethod();
						switch (true) {
							case ($module && $method): {
								$bDisallowed = !$permissions->isAllowedMethod($userId, $module, $method);
								break;
							}
							case $objectEditionAllowed: {
								$bDisallowed = false;
								break;
							}
							default: {
								throw new publicAdminException(getLabel('error-no-permissions'));
							}
						}
					}
				} else {
					list($r, $w) = $permissions->isAllowedObject($userId, $iEntityId);
					if(!$w) $bDisallowed = true;
				}
			}

			if ($bDisallowed) {
				throw new publicAdminException(getLabel('error-no-permissions'));
			}

			$event = new umiEventPoint("systemModifyPropertyValue");
			$event->addRef("entity", $oEntity);
			$event->setParam("property", $sPropName);
			$event->addRef("newValue", $content);
			$event->setMode("before");

			try {
				$event->call();
			} catch (wrongValueException $e) {
				throw new publicAdminException($e->getMessage());
			}

			if ($oEntity instanceof iUmiHierarchyElement) {
				$backupModel = backupModel::getInstance();
				$backupModel->addLogMessage($oEntity->getId());
			}

			if ($bIsObject && !$this->checkAllowedColumn($oEntity, $sPropName)) {
				throw new publicAdminException(getLabel('error-no-permissions'));
			}

			if ($bIsObject && $sPropName == 'is_activated') {

				$systemUsersPermissions = \UmiCms\Service::SystemUsersPermissions();
				$guestId = $systemUsersPermissions->getGuestUserId();
				$svUserId = $systemUsersPermissions->getSvUserId();

				if ($iEntityId == $svUserId) {
					throw new publicAdminException(getLabel('error-users-swtich-activity-sv'));
				}

				if ($iEntityId == $guestId) {
					throw new publicAdminException(getLabel('error-users-swtich-activity-guest'));
				}

				if ($iEntityId == $userId) {
					throw new publicAdminException(getLabel('error-users-swtich-activity-self'));
				}
			}

			$sPropValue = "";
			if ($oEntity) {
				$bOldVal = umiObjectProperty::$IGNORE_FILTER_INPUT_STRING;
				umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = true;
				$oObject = (!$bIsObject)? $oEntity->getObject() : $oEntity;
				$oldValue = null;

				try {
					if($sPropName == 'name') {
						if (is_string($content) && strlen($content)) {
							$oldValue = $oEntity->name;
							$oEntity->name = $content;
							if ($oEntity instanceof iUmiHierarchyElement) {
								$oEntity->h1 = $content;
							}
						}
						$result = array('name' => $content);
					} else {
						$property = $oObject->getPropByName($sPropName);

						switch ($property->getDataType()) {
							case 'date' : {
								$date = new umiDate();
								$date->setDateByString($content);
								$content = $date;
								break;
							}
							case 'img_file' :
								$file = new umiImageFile('.' . $content);
								$content = $file;
								break;
							case 'swf_file' :
							case 'video_file' :
							case 'file' : {
								$file = new umiFile('.' . $content);
								$content = $file;
								break;
							}
						}

						$oldValue = $oObject->getValue($sPropName);
						$oObject->setValue($sPropName, $content);

						if ($oObject->getIsUpdated() && $oObject->getId() != $oEntity->getId()) {
							$oEntity->setIsUpdated(true, true);
						}

						if ($oEntity instanceof iUmiHierarchyElement && $sPropName == 'h1') {
							$oEntity->name = $content;
						}
						$result = array('property' => $property);

						translatorWrapper::get($property);
						umiObjectPropertyWrapper::$showEmptyFields = true;
					}
				} catch (fieldRestrictionException $e) {
					throw new publicAdminException($e->getMessage());
				}
				$oEntity->commit();
				umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = $bOldVal;

				$oObject->update();
				$oEntity->update();

				if ($oEntity instanceof umiEntinty) {
					$oEntity->commit();
				}

				$event->setParam("oldValue", $oldValue);
				$event->setParam("newValue", $content);
				$event->setMode("after");
				$event->call();

				$this->setData($result);
				return $this->doData();
			}
		}

		public function filterString($string) {
			return str_replace("\"", "\\\"", str_replace("'", "\'", $string));
		}

		public function load_tree_node() {
			$this->setDataType("list");
			$this->setActionType("view");

			$limit = getRequest('per_page_limit');
			$curr_page = getRequest('p');
			$offset = $curr_page * $limit;

			list($rel) = getRequest('rel');
			$sel = new selector('pages');
			if ($rel !== 0) {
				$sel->limit($offset, $limit);
			}
			selectorHelper::detectFilters($sel);

			$result = $sel->result;
			$length = $sel->length;
			$templatesData = getRequest('templates');

			if ($templatesData) {
				$templatesList = explode(',', $templatesData);
				$result = $this->getPagesByTemplatesIdList($templatesList, $limit, $offset);
				$length = $this->getTotalPagesByTemplates($templatesList);
			}

			$data = $this->prepareData($result, "pages");
			$this->setData($data, $length);
			$this->setDataRange($limit, $offset);

			if ($rel != 0) {
				$this->setDataRangeByPerPage($limit, $curr_page);
			}
			return $this->doData();
		}

		/**
		 * Возвращает список страниц, которым назначены шаблоны $templates
		 * @param array $templates массив с ID шаблонов
		 * @param int $limit максимальное количество получаемых страниц
		 * @param int $offset смещение, относительно которого будет производиться выборка страниц
		 * @return array массив с объектами iUmiHierarchyElement
		 */
		public function getPagesByTemplatesIdList(array $templates, $limit = 0, $offset = 0) {
			$result = array();
			$templatesCollection = templatesCollection::getInstance();

			/** @var int $templateId */
			foreach ($templates as $templateId) {
				$template = $templatesCollection->getTemplate(trim($templateId));
				if (!$template instanceof iTemplate) {
					continue;
				}

				$relatedPages = $template->getRelatedPages($limit, $offset);

				if (!empty($relatedPages) && is_array($relatedPages)) {
					$result = array_merge($result, $relatedPages);
				}
			}

			return array_unique($result);
		}

		/**
		 * Возвращает количество страниц, которым назначены шаблоны $templates
		 * @param array $templates массив с ID шаблонов
		 * @return int число используемых страниц шаблонами
		 */
		public function getTotalPagesByTemplates(array $templates) {
			$total = 0;

			$templatesCollection = templatesCollection::getInstance();
			/** @var int $templateId */
			foreach ($templates as $templateId) {
				$template = $templatesCollection->getTemplate(trim($templateId));
				if (!$template instanceof iTemplate) {
					continue;
				}

				$total += $template->getTotalUsedPages();
			}

			return $total;
		}

		public function tree_set_activity() {
			$elements = getRequest('element');
			if (!is_array($elements)) {
				$elements = Array($elements);
			}

			$active = getRequest('active');

			if (!is_null($active)) {
				foreach ($elements as $elementId) {
					$element = $this->expectElement($elementId, false, true);

					if ($element instanceof umiHierarchyElement) {
						$active = intval($active) > 0 ? true : false;

						$params = Array(
							"element" => $element,
							"activity" => $active
						);

						$oEventPoint = new umiEventPoint("systemSwitchElementActivity");
						$oEventPoint->setMode("before");

						$oEventPoint->addRef("element", $element);
						$this->setEventPoint($oEventPoint);

						$this->switchActivity($params);

						// after del event
						$oEventPoint->setMode("after");
						$this->setEventPoint($oEventPoint);

					} else {
						throw new publicAdminException(getLabel('error-expect-element'));
					}
				}

				$this->setDataType("list");
				$this->setActionType("view");
				$data = $this->prepareData($elements, "pages");
				$this->setData($data);

				return $this->doData();
			} else {
				throw new publicAdminException(getLabel('error-expect-action'));
			}
		}

		/**
		 * Метод для перемещения страниц и|или объектов в административной панели
		 * @throws expectElementException
		 * @throws expectObjectException
		 * @throws publicAdminException
		 */
		public function move() {
			$element =  $this->expectElement("element");
			$elementParent =  $this->expectElement("rel");

			if ($element instanceof iUmiHierarchyElement && ($elementParent instanceof iUmiHierarchyElement || getRequest("rel") == 0)) {
				return $this->tree_move_element();
			}

			$object = $this->expectObject("element");
			$objectParent =  $this->expectObject("rel");

			if ($object instanceof iUmiObject && $objectParent instanceof iUmiObject) {
				return $this->table_move_object($object, $objectParent);
			}

			if (($element instanceof iUmiHierarchyElement || $object instanceof iUmiObject) && ($elementParent instanceof iUmiHierarchyElement || $objectParent instanceof iUmiObject)) {
				return $this->table_mixed_move();
			}

			$this->setDataType("list");
			$this->setActionType("view");

			$this->setData(array());
			return $this->doData();
		}

		/** Метод-заглушка для смешанного перемещения страниц и объектов */
		public function table_mixed_move() {
			$this->setDataType("list");
			$this->setActionType("view");
			$this->setData(array('node' => 'mixed'));
			return $this->doData();
		}

		/**
		 * Метод для перемещения объектов
		 * @param iUmiObject $object объект который перемещают
		 * @param iUmiObject $objectParent объект в который перемещают
		 */
		public function table_move_object(iUmiObject $object, iUmiObject $objectParent) {
			$this->setDataType("list");
			$this->setActionType("view");

			$moveMode = getRequest('moveMode');

			$umiObjects = umiObjectsCollection::getInstance();
			$orderChanged = $umiObjects->changeOrder($objectParent, $object, $moveMode);

			if ($orderChanged) {
				$this->setDataRange(2, 0);
				$data = $this->prepareData(array($object, $objectParent), 'objects');
				$this->setData($data, 2);
			} else {
				$this->setDataRange(0, 0);
				$data = $this->prepareData(array(), 'objects');
				$this->setData($data, 0);
			}

			return $this->doData();
		}

		/** Метод для перемещения страниц */
		public function tree_move_element() {
			$element =  $this->expectElement("element");
			$parentId = (int) getRequest("rel");
			$domain = getRequest('domain');
			$asSibling = (int) getRequest('as-sibling');
			$beforeId = getRequest('before');

			$pathTree = array();
			$pid = $parentId;
			while ($tmpElement = $this->expectElement($pid,false,true)) {
				if ($tmpElement instanceof umiHierarchyElement) {
					$pathTree[] = $pid;
					$pid = $tmpElement->getParentId();
					umiHierarchy::getInstance()->unloadElement($tmpElement->getId());
				} else {
					break;
				}
			}
			$currentId = $element->getId();
			if (in_array($currentId, $pathTree)) {
				return false;
			}

			if ($element instanceof umiHierarchyElement) {
				$oldParentId = $element->getParentId();

				$params = array(
					"element" => $element,
					"parent-id" => $parentId,
					"domain" => $domain,
					"as-sibling" => $asSibling,
					"before-id" => $beforeId
				);

				$this->moveElement($params);
			} else {
				throw new publicAdminException(getLabel('error-expect-element'));
			}

			if ((bool) getRequest('return_copies')) {
				$this->setDataType("form");
				$this->setActionType("modify");
				$data = $this->prepareData(array('element' => $element), "page");
			} else {
				$this->setDataType("list");
				$this->setActionType("view");
				$data = $this->prepareData(array($element->getId(), $element->getParentId(), $oldParentId), "pages");
			}

			$this->setData($data);

			return $this->doData();
		}

		public function tree_delete_element() {
			$elements = getRequest('element');
			if (!is_array($elements)) {
				$elements = Array($elements);
			}

			$parentIds = Array();

			foreach ($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true, true);

				if ($element instanceof umiHierarchyElement) {
					// before del event
					$element_id = $element->getId();
					$parentIds[] = $element->getParentId();
					$oEventPoint = new umiEventPoint("content_del_element");
					$oEventPoint->setMode("before");
					$oEventPoint->setParam("element_id", $element_id);
					$this->setEventPoint($oEventPoint);

					// try delete
					$params = Array(
						"element" => $element
					);

					$this->deleteElement($params);

					// after del event
					$oEventPoint->setMode("after");
					$this->setEventPoint($oEventPoint);
				} else {
					throw new publicAdminException(getLabel('error-expect-element'));
				}
			}

			$parentIds = array_unique($parentIds);

			// retrun parent element for update
			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($parentIds, "pages");

			$this->setData($data);

			return $this->doData();
		}

		public function tree_copy_element() {
			$element =  $this->expectElement('element');
			$cloneMode = (bool) getRequest('clone_mode');
			$copyAll = (bool) getRequest('copy_all');
			$parentId = (int) getRequest('rel');
			$connection = ConnectionPool::getInstance()->getConnection();
			$new_element_id = false;

			if ($element instanceof umiHierarchyElement) {
				$element_id = $element->getId();
				if (!($parentId && umiHierarchy::getInstance()->isExists($parentId))) {
					$parentId = umiHierarchy::getInstance()->getParent($element_id);
				}

				$connection->startTransaction();

				try {
					if ($cloneMode) {
						// create real copy
						$clone_allowed = true;

						if ($clone_allowed) {
							$event = new umiEventPoint("systemCloneElement");
							$event->addRef("element", $element);
							$event->setParam("elementId", $element_id);
							$event->setParam("parentId", $parentId);
							$event->setMode("before");
							$event->call();

							$new_element_id = umiHierarchy::getInstance()->cloneElement($element_id, $parentId, $copyAll);

							$event->setParam("newElementId", $new_element_id);
							$event->setMode("after");
							$event->call();

							$new_element = umiHierarchy::getInstance()->getElement((int) $new_element_id, false, false);

							$event = new umiEventPoint("systemCreateElementAfter");
							$event->addRef("element", $new_element);
							$event->setParam("elementId", $new_element_id);
							$event->setParam("parentId", $parentId);
							$event->setMode("after");
							$event->call();

						}
					} else {
						// create virtual copy
						$event = new umiEventPoint("systemVirtualCopyElement");
						$event->setParam("elementId", $element_id);
						$event->setParam("parentId", $parentId);
						$event->addRef("element", $element);
						$event->setMode("before");
						$event->call();

						$new_element_id = umiHierarchy::getInstance()->copyElement($element_id, $parentId, $copyAll);

						$event->setParam("newElementId", $new_element_id);
						$event->setMode("after");
						$event->call();

						$new_element = umiHierarchy::getInstance()->getElement((int) $new_element_id, false, false);

						$event = new umiEventPoint("systemCreateElementAfter");
						$event->addRef("element", $new_element);
						$event->setParam("elementId", $new_element_id);
						$event->setParam("parentId", $parentId);
						$event->setMode("after");
						$event->call();
					}

					if ($new_element_id) {
						if ((bool) getRequest('return_copies')) {
							$this->setDataType("form");
							$this->setActionType("modify");
							$data = $this->prepareData(array('element' => $new_element), "page");
							$this->setData($data);
						} else {
							$this->setDataType("list");
							$this->setActionType("view");
							$data = $this->prepareData(array($new_element_id), "pages");
							$this->setData($data);
						}
					} else {
						throw new publicAdminException(getLabel('error-copy-element'));
					}
				} catch (Exception $exception) {
					$connection->rollbackTransaction();
					throw $exception;
				}

				$connection->commitTransaction();
				return $this->doData();
			} else {
				throw new publicAdminException(getLabel('error-expect-element'));
			}
		}

		public function tree_unlock_page() {
			$pageId = getRequest("param0");
			$this->unlockPage($pageId);
			$result = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?"."> \n";
			$result .= "<is_unlocked>true</is_unlocked> \n";
			header("Content-type: text/xml; charset=utf-8");
			$this->flush($result);
		}

		public function json_unlock_page() {
			$this->tree_unlock_page();
		}

		public function copyElementToSite() {
			$langId = (int) getRequest('lang-id');
			$domainId = (int) getRequest('domain-id');
			$alias_new = (array) getRequest('alias');
			$move_old = (array) getRequest('move');
			$force = (int) getRequest('force');
			$mode = (string) getRequest('mode');

			$elements = getRequest('element');
			if (!is_array($elements)) {
				$elements = Array($elements);
			}

			foreach ($alias_new as $k=>$v) {
				$alias_new[$k] = umiHierarchy::convertAltName($v);
			}

			if (!is_null($langId)) {
				$hierarchy = umiHierarchy::getInstance();

				if (!$force) {
					$aliases_old = array();

					foreach($elements as $elementId) {

						if (!empty($move_old[$elementId])) {
							continue;
						}

						$element = $this->expectElement($elementId, false, true);
						$alt_name = $element->getAltName();

						if (!empty($alias_new[$element->getId()])) {
							$alt_name = $alias_new[$element->getId()];
						}

						$errorsCount = 0;
						$element_dst =  umiHierarchy::getInstance()->getIdByPath( $alt_name , false, $errorsCount, $domainId ,$langId);
						$element_dst = $this->expectElement($element_dst, false, true);

						if($element_dst && $element_dst->getAltName() == $alt_name) {
							$alt_name_normal = $hierarchy->getRightAltName($alt_name, $element_dst, false, true);
							$aliases_old[$element->getId()] = array($alt_name, $alt_name_normal);
						}
					}

					if(count($aliases_old) ) {
						$this->setDataType("list");
						$this->setActionType("view");
						$data = array('error'=>array());

						$data['error']['nodes:item'] = array();
						$data['error']['type'] = '__alias__';
						$domain = domainsCollection::getInstance()->getDomain($domainId);
						$path = $domain->getUrl() . "/";

						if (!langsCollection::getInstance()->getLang($langId)->getIsDefault()) {
							$path .= langsCollection::getInstance()->getLang($langId)->getPrefix() . '/';
						}

						foreach($aliases_old as $k=>$v) {
							$data['error']['nodes:item'][] = array('attribute:id'=>$k, 'attribute:path'=>$path , 'attribute:alias'=>$v[0], 'attribute:alt_name_normal'=>$v[1]);
						}

						$this->setData($data);
						return $this->doData();
					}
				}

				$templatesCollection = templatescollection::getInstance();

				$templates = $templatesCollection->getTemplatesList($domainId, $langId);

				$template_error = false;
				if (empty($templates)) {
					$template_error = true;
				}

				if ($template_error) {
					$this->setDataType("list");
					$this->setActionType("view");
					$data = $this->prepareData(array(), "pages");

					$dstLang = langsCollection::getInstance()->getLang($langId);
					$lang = '';
					if( !$dstLang->getIsDefault()) {
						$lang .= $dstLang->getPrefix() . '/';
					}

					$data['error'] = array();
					$data['error']['type'] = "__template_not_exists__";
					$data['error']['text'] = sprintf(getLabel('error-no-template-in-domain'), $lang);
					$this->setData($data);
					return $this->doData();
				}

				$template_def = $templatesCollection->getDefaultTemplate($domainId, $langId);;


				foreach ($elements as $elementId) {
					$element = $this->expectElement($elementId, false, true);
					$element_template = $templatesCollection->getTemplate($element->getTplId());

					$template_has = false;
					foreach ($templates as $v) {
						if ($v->getFilename() == $element_template->getFilename()) {
							$template_has = $v;
						}
					}

					if (!$template_has)
						$template_has = $template_def;

					if (!$template_has)
						$template_has = reset($templates);

					//if($element->getLangId() != $langId || true) {

					if ($mode == 'move') {
						$copyElement = $element;
						$copyElementId = $element->getId();
					}
					else {
						$copyChilds = (bool) getRequest('copy_all');
						$copyElementId = $hierarchy->cloneElement($element->getId(), 0, true, false);
						$copyElement = $hierarchy->getElement($copyElementId);
					}


					if ($copyElement instanceof umiHierarchyElement) {
						$alt_name = $element->getAltName();

						if (!empty($alias_new[$element->getId()])) {
							$alt_name = $alias_new[$element->getId()];
						}

						if (!empty($move_old[$element->getId()])) {
							$element_dst =  umiHierarchy::getInstance()->getIdByPath($alt_name , false, $errorsCount,$domainId , $langId);
							$element_dst = $this->expectElement($element_dst, false, true);

							if($element_dst && $element_dst->getAltName() == $alt_name) {
								$hierarchy->delElement($element_dst->getId());
							}
						}

						$copyElement->setLangId($langId);

						if($domainId) {
							$copyElement->setDomainId($domainId);
						}

						$copyElement->setAltName( $alt_name );

						if($template_has) {
							$copyElement->setTplId($template_has->getId());
						}

						$copyElement->commit();

						$childs = $hierarchy->getChildrenTree($copyElementId);
						self::changeChildsLang($childs, $langId, $domainId);
					}
				}
			}

			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData(array(), "pages");

			$this->setData($data);
			return $this->doData();
		}

		public function copy_to_lang() {
			$langId = (int) getRequest('lang-id');
			$elements = getRequest('element');
			if (!is_array($elements)) {
				$elements = Array($elements);
			}

			if (!is_null($langId)) {
				$hierarchy = umiHierarchy::getInstance();

				foreach ($elements as $elementId) {
					$element = $this->expectElement($elementId, false, true);
					if ($element->getLangId() != $langId || true) {
						$copyElementId = $hierarchy->cloneElement($element->getId(), 0, true);
						$copyElement = $hierarchy->getElement($copyElementId);
						if ($copyElement instanceof umiHierarchyElement) {
							$copyElement->setLangId($langId);
							$copyElement->commit();

							$childs = $hierarchy->getChildrenTree($copyElementId);
							self::changeChildsLang($childs, $langId);
						}
					}
				}
			}

			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData(array(), "pages");
			$this->setData($data);
			return $this->doData();
		}

		public function move_to_lang() {
			$_REQUEST['mode'] = 'move';

			return $this->copy_to_lang();
		}

		protected function changeChildsLang($childs, $langId, $domainId = false) {
			$hierarchy = umiHierarchy::getInstance();

			foreach ($childs as $elementId => $subChilds) {
				$element = $hierarchy->getElement($elementId);
				if ($element instanceof umiHierarchyElement) {
					$element->setLangId($langId);

					if ($domainId) {
						$element->setDomainId($domainId);
					}

					$element->commit();

					if (is_array($subChilds) && count($subChilds))  {
						self::changeChildsLang($subChilds, $langId, $domainId);
					}
				}
			}
		}

	};
?>
