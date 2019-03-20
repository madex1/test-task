<?php
	abstract class __editor_content {
		public function editValue() {
			if ( !cmsController::isCSRFTokenValid() ) {
				throw new coreException('CSRF Protection');
			}

			$this->flushAsXml('editValue');
			$hierarchy = umiHierarchy::getInstance();
			$objects = umiObjectsCollection::getInstance();

			$mode = getRequest('param0');
			$elementId = getRequest('element-id');
			$objectId = getRequest('object-id');
			$element = null; $object = null;

			if($elementId) {
				$permissions = permissionsCollection::getInstance();
				list($r, $w) = $permissions->isAllowedObject($permissions->getUserId(), $elementId);
				if(!$w)
					throw new publicException(getLabel('eip-no-permissions'));
				$element = $hierarchy->getElement($elementId);
				if($element instanceof iUmiHierarchyElement) {
					$object = $element->getObject();
				} else throw new publicException(getLabel('eip-no-element') . ": #{$elementId}");
			} else if($objectId) {
				$pages  = $hierarchy->getObjectInstances($objectId);
				if(!empty($pages)) {
					$permissions = permissionsCollection::getInstance();
					$userId = $permissions->getUserId();
					$allow  = false;
					foreach($pages as $elementId) {
						 list($r, $w) = $permissions->isAllowedObject($userId, $elementId);
						 if($w) {
							 $allow = true;
							 break;
						 }
					}
					if(!$allow) throw new publicException(getLabel('eip-no-permissions'));
				}
				$object = $objects->getObject($objectId);
				if($object instanceof iUmiObject == false) {
					throw new publicException(getLabel('eip-no-object') . ": #{$elementId}");
				}
			} else throw new publicException(getLabel('eip-nothing-found'));

			$target = $element ? $element : $object;
			$fieldName = getRequest('field-name');
			$value = getRequest('value');

			$result = array();
			if(is_array($fieldName)) {
				$properties = array();
				for($i = 0; $i < count($fieldName); $i++) {
					$properties[] = self::saveFieldValue($fieldName[$i], $value[$i], $target, ($mode == 'save'));
				}
				$result['nodes:property'] = $properties;
			} else {
				$property = self::saveFieldValue($fieldName, $value, $target, ($mode == 'save'));
				$result['property'] = $property;
			}

			return $result;
		}

		protected static function saveFieldValue($name, $value, $target, $save = false) {
			if ( !cmsController::isCSRFTokenValid() ) {
				throw new coreException('CSRF Protection');
			}

			$hierarchy = umiHierarchy::getInstance();

			if($i = strpos($name, '[')) {
				if(preg_match_all("/\[([^\[^\]]+)\]/", substr($name, $i), $out)) {
					$optionParams = array(
						'filter' => array(),
						'field-type' => null
					);

					foreach($out[1] as $param) {
						if(strpos($param, ':')) {
							list($seekType, $seekValue) = explode(':', $param);
							$optionParams['filter'][$seekType] = $seekValue;
						} else {
							$optionParams['field-type'] = $param;
						}
					}
				}
				$name = substr($name, 0, $i);
			} else $optionParams = null;

			if($name != 'name' && $name != 'alt_name') {
				$object = ($target instanceof iUmiHierarchyElement) ? $target->getObject() : $target;
				$property = $object->getPropByName($name);
				if($property instanceof iUmiObjectProperty == false) {
					throw new publicException(getLabel('eip-no-field') . ": \"{$name}\"");
				}
				$field = $property->getField();
			}

			if($name == 'name' || $name == 'alt_name') {
				$type = 'string';
			} else {
				$type = $field->getDataType();
			}

			if (is_string($value)) {
				$value = __editor_content::filterStringValue($value);
			}

			$oldLink = null; $newLink = null;

			if($save) {
				umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = true;
				if($name == 'h1' || $name == 'name') {
					$value = strip_tags($value);
					$value = str_replace(array('&nbsp;', '&amp;'), array(' ', '&'), $value);

					if ( $name === 'name' ) {
						// При изменении name: если name==h1, name=h1=new_value
						// При изменении name: если name!=h1, name=new_value.
						if ( $target->getName() === (string)$target->getValue('h1') ) {
							$target->setValue('h1', $value);
						}
						$target->setName($value);
					} else {
						// При изменении h1: если h1 == name && name=='', name=h1=new_value
						// При изменении h1: если h1 == name и name != '', h1=new_value
						// При изменении h1: если h1 != name, h1=new_value
						if ( $target->getName() === (string)$target->getValue('h1') && ($target->getName() === '') ) {
							$target->setName($value);
						}
						$target->setValue('h1', $value);
					}

					if($target instanceof iUmiHierarchyElement) {
						$oldLink = $hierarchy->getPathById($target->id);

						$altName = $target->getAltName();
						if(!$altName || substr($altName, 0, 1) == '_') {
							$target->setAltName($value);
							$target->commit();
						}

						$newLink = $hierarchy->getPathById($target->id, false, false, true);
					}
				} elseif($name == 'alt_name'){
					if($target instanceof iUmiHierarchyElement) {
						$target->setAltName($value);
						$target->commit();
						$newLink = $hierarchy->getPathById($target->id, false, false, true);
					}
				} else {
					if($type == 'date') {
						$date = new umiDate();
						$date->setDateByString($value);
						$value = $date; unset($date);
						$value = $value->getFormattedDate('U');
					}

					if($type == 'optioned') {
						$seekType = getArrayKey($optionParams, 'field-type');
						$filter = getArrayKey($optionParams, 'filter');
						$oldValue = $target->getValue($name);
						foreach($oldValue as $i => $v) {
							foreach($filter as $t => $s) {
								if(getArrayKey($v, $t) != $s) continue 2;
								$oldValue[$i][$seekType] = $value;
							}
						}
						$value = $oldValue; unset($oldValue);
					}

					if($type == 'symlink') {
						$value = $value;
					}

					if($type == 'wysiwyg') {
						$out = array();
						if(preg_match_all("/href=[\"']?([^ ^\"^']+)[\"']?/i", $value, $out)) {
							foreach($out[1] as $link) {
								$id = $hierarchy->getIdByPath($link);
								if($id) {
									$value = preg_replace("/(href=[\"']?)" .  preg_quote($link, '/') . "([\"']?)/i", "\\1%content get_page_url({$id})%\\2", $value);
								}
							}
						}
					} else {
						$value = str_replace(array('&nbsp;', '&amp;'), array(' ', '&'), $value);
					}

					if(in_array($type, array('text', 'string', 'int', 'float', 'price', 'date', 'tags', 'counter'))) {
						$value = preg_replace("/<br ?\/?>/i", "\n", $value);
						$value = strip_tags($value);
					}

					if(in_array($type, array('img_file', 'swf_file', 'file', 'video_file')) && $value) {
						if(substr($value, 0, 1) != '.') $value = '.' . $value;
					}

					$object = ($target instanceof iUmiHierarchyElement) ? $target->getObject() : $target;
					$object->setValue($name, $value);

					if ($object->getIsUpdated() && $object->getId() != $target->getId()) {
						$target->setIsUpdated(true, true);
					}
				}
				$target->commit();
				umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = false;

				if($target instanceof iUmiHierarchyElement) {
					$backup = backupModel::getInstance();
					$backup->fakeBackup($target->id);
				}

				$oEventPoint = new umiEventPoint("eipSave");
				$oEventPoint->setMode("after");
				$oEventPoint->setParam("field_name", $name);
				$oEventPoint->setParam("obj", $target);
				def_module::setEventPoint($oEventPoint);

			}

			if($name == 'name') {
				$value = $target->getName();
			} else {
				$value = $target->getValue($name, $optionParams);
			}

			if($save) {
				$value = xmlTranslator::executeMacroses($value);
			}

			if($type == 'date') {
				if ($value) {
				$date = new umiDate();
				$date->setDateByString($value);
				$value = $date->getFormattedDate('Y-m-d H:i');
			}
				else $value = '';
			}

			if($type == 'tags' && is_array($value)) {
				$value = implode(', ', $value);
			}

			if($type == 'optioned' && !is_null($optionParams)) {
				$value = isset($value[0]) ? $value[0] : '';
				$type = getArrayKey($optionParams, 'field-type');
			}

			$result = array(
				'attribute:name'		=> $name,
				'attribute:type'		=> $type
			);

			if($type == 'relation') {
				$items_arr = array();
				if($value) {
					if(!is_array($value)) $value = array($value);

					$objects = umiObjectsCollection::getInstance();
					foreach($value as $objectId) {
						$object = $objects->getObject($objectId);
						$items_arr[] = $object;
					}
				}

				$result['attribute:guide-id'] = $field->getGuideId();
				if($field->getFieldType()->getIsMultiple()) {
					$result['attribute:multiple'] = 'multiple';
				}

				$type = selector::get('object-type')->id($field->getGuideId());
				if($type && $type->getIsPublic()) {
					$result['attribute:public'] = 'public';
				}
				$result['nodes:item'] = $items_arr;
			} else if($type == 'symlink') {
				$result['nodes:page'] = is_array($value) ? $value : array();
			} else {
				$result['node:value'] = $value;
			}

			if($oldLink != $newLink) {
				$result['attribute:old-link'] = $oldLink;
				$result['attribute:new-link'] = $newLink;
			}

			return $result;
		}

		public function getTypeAdding() {
			$parent_id = getRequest('param0');
			$this->flushAsXml('getTypeAdding');
			return umiHierarchy::getInstance()->getDominantTypeId($parent_id);
		}

		public function getTypeFields() {
			$typeId = getRequest('param0');
			$this->flushAsXml('getTypeFields');
			$elementFieldNames = array();
			$objectType = umiObjectTypesCollection::getInstance()->getType($typeId);
			$elementFields = $objectType->getAllFields();
			foreach ($elementFields as $field) {
				array_push($elementFieldNames, $field->getName());
			}
			return $elementFieldNames;
		}

		protected static function loadEiPTypes() {
			static $types;
			if(is_array($types)) return $types;

			$config = mainConfiguration::getInstance();
			$types = array();
			$rules = $config->get('edit-in-place', 'allowed-types');
			foreach($rules as $rule) {
				list($type, $parents) = preg_split("/ ?<\- ?/", $rule);
				list($module, $method) = explode("::", $type);
				$types[$module][$method] = $parents;
			}
			return $types;
		}

		protected static function prepareTypesList($targetModule, $parent = null) {
			$types = self::loadEiPTypes();
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$cmsController = cmsController::getInstance();
			$modulesList = $cmsController->getModulesList();

			if($parent instanceof iUmiHierarchyElement) {
				$targetModule = $parent->getModule();
			}

			$matched = array();
			foreach($types as $module => $stypes) {
				if($parent && ($module != $targetModule && $targetModule != 'content')) continue;

				asort($stypes, true);

				foreach($stypes as $method => $rule) {
					if($rule != '*' && $rule != '@') {
						if(!$parent) continue;

						$arr = explode('::', $rule);
						if(count($arr) != 2) continue;
						list($seekModule, $seekMethod) = $arr;
						if($parent->getModule() != $seekModule || $parent->getMethod() != $seekMethod) {
							continue;
						}
					}

					if($rule == '@' && $parent) continue;

					$hierarchyType = $hierarchyTypes->getTypeByName($module, $method);

					if($hierarchyType instanceof iUmiHierarchyType) {
						//Compare with installed modules list
						if(!in_array($module, $modulesList)) {
							continue;
						}
						$matched[] = $hierarchyType;
					}
				}
			}

			$event = new umiEventPoint("eipPrepareTypesList");
			$event->setParam("targetModule", $targetModule);
			$event->setParam("parent", $parent);
			$event->addRef("types", $matched);
			$event->setMode("after");
			$event->call();

			return $matched;
		}

		public function eip_quick_add() {
			if ( !cmsController::isCSRFTokenValid() ) {
				throw new coreException('CSRF Protection');
			}

			$this->setDataType("form");
			$this->setActionType("create");

			$parentElementId = (int) getRequest('param0');
			$objectTypeId = (int) getRequest('type-id');
			$forceHierarchy = (int) getRequest('force-hierarchy');

			$objectType = selector::get('object-type')->id($objectTypeId);
			if(!$forceHierarchy && $objectType instanceof iUmiObjectType) {
				$objects = umiObjectsCollection::getInstance();
				$objectId = $objects->addObject(NULL, $objectTypeId);

				$data = array(
				    'attribute:object-id' => $objectId,
				    'status' => 'ok'
				);
			} else {

				$permissions = permissionsCollection::getInstance();
				if($parentElementId) {
					$userId = $permissions->getUserId();
					$allow = $permissions->isAllowedObject($userId, $parentElementId);
					if (!$allow[2]) {
						throw new publicAdminException(getLabel("error-require-add-permissions"));
					}
				}

				$hierarchy = umiHierarchy::getInstance();
				$objectTypes = umiObjectTypesCollection::getInstance();

				if(!$objectTypeId) $objectTypeId = $hierarchy->getDominantTypeId($parentElementId);

				if(!$objectTypeId) {
					throw new publicAdminException("No dominant object type found");
				}

				$objectType = $objectTypes->getType($objectTypeId);
				$hierarchyTypeId = $objectType->getHierarchyTypeId();

				$elementId = $hierarchy->addElement($parentElementId, $hierarchyTypeId, '', '', $objectTypeId);
				$permissions->setInheritedPermissions($elementId);
				$element = $hierarchy->getElement($elementId);
				$element->isActive = true;
				$element->isVisible = true;
				$element->show_submenu = true;
				$element->commit();

				$event = new umiEventPoint('eipQuickAdd');
				$event->setParam('objectTypeId', $objectTypeId);
				$event->setParam('elementId', $elementId);
				$event->setMode('after');
				$event->call();

				$data = array(
					'attribute:element-id' => $elementId,
					'status' => 'ok'
				);
			}

			$this->setData($data);
			return $this->doData();
		}

		public function eip_add_page() {
			if ( !cmsController::isCSRFTokenValid() ) {
				throw new coreException('CSRF Protection');
			}

			$csrf = getRequest('csrf');
			$mode = (string) getRequest('param0');
			$parent = $this->expectElement("param1");
			$module = (string) getRequest('param2');
			$method = (string) getRequest('param3');

			$permissions = permissionsCollection::getInstance();
			$parentId = '';
			if ($parent) {
				$this->checkElementPermissions($parent->getId());
				$parentId = $parent->getId();
			}
			else {
				$permissions->isAllowedModule($permissions->getUserId(), $module);
			}
			$hierarchy = umiHierarchy::getInstance();
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();

			if($mode == 'choose') {
				$types = self::prepareTypesList($module, $parent);
				
				if(count($types) >= 1) { //Show type choose list
					if($hierarchyTypeId = getRequest('hierarchy-type-id')) {

						$hierarchyType = $hierarchyTypes->getType($hierarchyTypeId);
						if($hierarchyType instanceof iUmiHierarchyType) {
							$module = $hierarchyType->getModule();
							$method = $hierarchyType->getMethod();

							if($module == 'content' && !$method) $method = 'page';
							$parentId = $parent ? $parent->id : '0';

							$url = $this->pre_lang . "/admin/content/eip_add_page/form/{$parentId}/{$module}/{$method}/?0&csrf={$csrf}";
							if(isset($_REQUEST['object-type'][$hierarchyTypeId])) {
								$url .= '&type-id=' . $_REQUEST['object-type'][$hierarchyTypeId];
							}

							if($hierarchyTypeId = getRequest('hierarchy-type-id')) {
								$url .= '&hierarchy-type-id=' . $hierarchyTypeId;
							}
							$this->chooseRedirect($url);
						}
					}

					$this->setDataType("list");
					$this->setActionType("view");

					$data = array(
						'nodes:hierarchy-type' => $types
					);
					$this->setData($data, count($types));
					return $this->doData();

				}

				if(count($types) == 0) { //Display and error
					$buffer = \UmiCms\Service::Response()
						->getCurrentBuffer();
					$buffer->contentType('text/html');
					$buffer->clear();
					$buffer->push("An error (temp message)");
					$buffer->end();
				}
			}

			$inputData = array(
				'type'		=> $method,
				'parent'	=> $parent,
				'module'	=> $module
			);

			if($objectTypeId = getRequest('type-id')) {
				$inputData['type-id'] = $objectTypeId;
			} else if ($hierarchyTypeId = getRequest('hierarchy-type-id')) {
				$inputData['type-id'] = $objectTypes->getTypeIdByHierarchyTypeId($hierarchyTypeId);
			}


			if(getRequest('param4') == "do") {
				$elementId = $this->saveAddedElementData($inputData);
				$element = $hierarchy->getElement($elementId, true);
				if($element instanceof iUmiHierarchyElement) {
					$element->setIsActive();
					$element->commit();
				} else {
					throw new publicException("Can't get create umiHierarchyElement");
				}

				$permissions->setInheritedPermissions($elementId);

				$buffer = \UmiCms\Service::Response()
					->getCurrentBuffer();
				$buffer->contentType('text/html');
				$buffer->clear();
				$buffer->push("<script>window.parent.location.reload();</script>");
				$buffer->end();
			}

			$this->setDataType("form");
			$this->setActionType("create");

			$data = $this->prepareData($inputData, "page");

			$this->setData($data);
			return $this->doData();
		}

		public function eip_del_page() {
			if ( !cmsController::isCSRFTokenValid() ) {
				throw new coreException('CSRF Protection');
			}

			$this->flushAsXml('eip_del_page');

			$config = mainConfiguration::getInstance();
			$permissions = permissionsCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$objects = umiObjectsCollection::getInstance();

			$userId = $permissions->getUserId();
			$elementId = (int) getRequest('element-id');
			$objectId = (int) getRequest('object-id');

			$fakeDelete = $config->get('system', 'eip.fake-delete');

			if($objectId) {
				if($permissions->isSv() || $permissions->isAdmin() || $permissions->isOwnerOfObject($objectId, $permissions->getUserId())) {
					$objects->delObject($objectId);
					return array(
						'status'	=> 'ok'
					);
				} else {
					return array(
						'error' => getLabel('error-require-delete-permissions')
					);
				}
			} else {
				$allow = $permissions->isAllowedObject($userId, $elementId);

				if($allow[3]) {
					$element = $hierarchy->getElement($elementId);
					if($element instanceof iUmiHierarchyElement) {
						if(!$element->name && !trim($element->altName, '_0123456789') || !$fakeDelete) {

                                   $oEventPoint = new umiEventPoint("systemDeleteElement");
                                   $oEventPoint->setMode("before");
                                   $oEventPoint->addRef("element", $element);
                                   $this->setEventPoint($oEventPoint);

							$hierarchy->delElement($elementId);

                                   // after del event
                                   $oEventPoint2 = new umiEventPoint("systemDeleteElement");
                                   $oEventPoint2->setMode("after");
                                   $oEventPoint2->addRef("element", $element);
                                   $this->setEventPoint($oEventPoint2);

						} else {
                                   // fake delete
                                   $oEventPoint = new umiEventPoint("systemSwitchElementActivity");
                                   $oEventPoint->setMode("before");
                                   $oEventPoint->addRef("element", $element);
                                   $this->setEventPoint($oEventPoint);

							$element->setIsActive(false);
							$element->commit();

                                   $oEventPoint2 = new umiEventPoint("systemSwitchElementActivity");
                                   $oEventPoint2->setMode("after");
                                   $oEventPoint2->addRef("element", $element);
                                   $this->setEventPoint($oEventPoint2);
						}
					}

					return array(
						'status'	=> 'ok'
					);
				} else {
					return array(
						'error' => getLabel('error-require-delete-permissions')
					);
				}
			}
		}


		public function eip_move_page() {
			if ( !cmsController::isCSRFTokenValid() ) {
				throw new coreException('CSRF Protection');
			}
			$this->flushAsXml('eip_move_page');

			$permissions = permissionsCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();

			$userId = $permissions->getUserId();
			$elementId = (int) getRequest('param0');
			$nextElementId = (int) getRequest('param1');

			$parentElementId = getRequest('parent-id');
			if(is_null($parentElementId)) {
				if($nextElementId) {
					$parentElementId = $hierarchy->getParent($nextElementId);
				} else {
					$parentElementId = $hierarchy->getParent($elementId);
				}
			}

			$parents = $hierarchy->getAllParents($parentElementId);
			if(in_array($elementId, $parents)) {
				throw new publicAdminException(getLabel('error-illegal-moving'));
			}

			$allow = $permissions->isAllowedObject($userId, $elementId);
			if($allow[4]) {
				if (is_null(getRequest('check'))) {
					$element = $hierarchy->getElement($elementId);
					$oldParentId = null;
					if ($element instanceof iUmiHierarchyElement) {
						$oldParentId = $element->getRel();
					}

					$event = new umiEventPoint('systemMoveElement');
					$event->setParam('parentElementId', $parentElementId);
					$event->setParam('elementId', $elementId);
					$event->setParam('beforeElementId', $nextElementId);
					$event->setParam("old-parent-id", $oldParentId);
					$event->setMode('before');
					$event->call();

					$hierarchy->moveBefore($elementId, $parentElementId, $nextElementId ? $nextElementId : false);

					$event2 = new umiEventPoint('systemMoveElement');
					$event2->setParam('parentElementId', $parentElementId);
					$event2->setParam('elementId', $elementId);
					$event2->setParam('beforeElementId', $nextElementId);
					$event2->setParam("old-parent-id", $oldParentId);
					$event2->setMode('after');
					$event2->call();
				}
				return array(
					'status'	=> 'ok'
				);
			} else {
				return array(
					'error' => getLabel('error-require-move-permissions')
				);
			}
		}


		public function frontendPanel() {
			$permissions = permissionsCollection::getInstance();
			$cmsController = cmsController::getInstance();
			$maxRecentPages = 5;

			$this->flushAsXml('frontendPanel');

			$modules = array();
			$modulesSortedPriorityList = $this->getSortedModulesList();

			foreach($modulesSortedPriorityList as $moduleInfo) {
				$modules[] = array(
					'attribute:label'	=> $moduleInfo['label'],
					'attribute:type'	=> $moduleInfo['type'],
					'node:name'			=> $moduleInfo['name']
				);
			}

			$hierarchy = umiHierarchy::getInstance();
			$key = md5(getServer('HTTP_REFERER'));
			$session = \UmiCms\Service::Session();
			$currentIds = is_array($session->get($key)) ? $session->get($key) : array();
			foreach($currentIds as $i => $id) $currentIds[$i] = $id[2];
			$currentIds = array_unique($currentIds);
			$current = array();
			foreach($currentIds as $id) {
				$current[] = $hierarchy->getElement($id);
			}


			$recent = new selector('pages');
			$recent->where('is_deleted')->equals(0);
			$recent->where('is_active')->equals(1);
			$recent->where('lang')->equals(langsCollection::getInstance()->getList());
			$recent->order('updatetime')->desc();
			$recent->limit(0, $maxRecentPages);

			if(count($currentIds) && $permissions->isAllowedModule($permissions->getUserId(), 'backup')) {
				$backup = $cmsController->getModule('backup');
				$changelog = $backup->backup_panel($currentIds[0]);
			}
			else $changelog = null;

			$referer = getRequest('referer') ? getRequest('referer') : getServer('HTTP_REFERER');

			$tickets = new selector('objects');
			$tickets->types('object-type')->name('content', 'ticket');
			$tickets->where('url')->equals($referer);
			$tickets->limit(0, 100);

			$ticketsColorField = 'tickets_color';

			$ticketsResult = array();
			foreach($tickets as $ticket) {
				$ticketOwner = selector::get('object')->id($ticket->user_id);
				if(!$ticketOwner instanceof iUmiObject)  {
					continue;
				}

				$ticketsResult[] = array(
					'attribute:id' => $ticket->id,
					'author' => array(
						'attribute:fname' => $ticketOwner->fname,
						'attribute:lname' => $ticketOwner->lname,
						'attribute:login' => $ticketOwner->login,
						'attribute:ticketsColor' => $ticketOwner->getValue($ticketsColorField)
					),
					'position' => array(
						'attribute:x' => $ticket->x,
						'attribute:y' => $ticket->y,
						'attribute:width' => $ticket->width,
						'attribute:height' => $ticket->height
					),
					'message' => $ticket->message
				);
			}

			$user = selector::get('object')->id($permissions->getUserId());
			if (!$user instanceof iUmiObject) {
				return array();
			}

			$result = array(
				'user'		=> array(
					'attribute:id' => $user->getId(),
					'attribute:fname' => $user->fname,
					'attribute:lname' => $user->lname,
					'attribute:login' => $user->login,
					'attribute:ticketsColor' => $user->getValue($ticketsColorField)
				),
				'tickets' => array(
					'nodes:ticket' => $ticketsResult
				),
				'modules'	=> array('nodes:module' => $modules),
				'documents'		=> array(
					'editable'		=> array('nodes:page' => $current),
					'recent'		=> array('nodes:page' => $recent->result())
				)
			);

			if(!$permissions->isAllowedMethod($permissions->getUserId(), 'tickets', 'manage')) {
				unset($result['tickets']);
			}

			if($changelog && count($changelog['nodes:revision'])) {
				$result['changelog'] = $changelog;
			}

			$event = new umiEventPoint('eipFrontendPanelGet');
			$event->setParam("id", getArrayKey($currentIds, 0));
			$event->addRef("result", $result);
			$event->setMode('after');
			$event->call();

			return $result;
		}

		static function filterStringValue($value) {
			$trims = array('&nbsp;', ' ', '\n');
			foreach($trims as $trim) {
				if(substr($value, 0, strlen($trim)) == $trim) {
					$value = substr($value, strlen($trim));
				}

				if(substr($value, strlen($value) - strlen($trim)) == $trim) {
					$value = substr($value, 0, strlen($value) - strlen($trim));
				}
			}
			return $value;
		}

		/**
		 * Get Image URL by its element id and field name
		 * @param int $elementId Element id
		 * @param string $fieldName Field name
		 * @return string Image URL
		 */
		public function getImageUrl ($elementId, $fieldName) {
			if (empty($elementId) || empty ($fieldName)) {
				return "";
			}
			$oHierarchy = umiHierarchy::getInstance();
			if (!$oHierarchy) {
				return "";
			}
			$oElement = $oHierarchy->getElement($elementId);
			if (!$oElement) {
				return "";
			}
			$oImgFile = $oElement->getValue($fieldName);
			if ($oImgFile instanceof umiFile === false) {
				return "";
			}
			return $oImgFile->getFilePath(true);
		}

		/**
		 * Get path to folder for images stored by EiP image editor
		 * @param bool $bFullPath Return absolute (true) or relative (false) to working directory path
		 * @return string Path to folder
		 */
		public function getIeditorImagesPath ($bFullPath = false) {
			$sPath = $bFullPath ? realpath(USER_IMAGES_PATH) : USER_IMAGES_PATH;
			return $sPath . '/cms/data/.ieditor';
		}

		/**
		 * Get separator for parameters in stred image filename
		 * @return string Separator
		 */
		public function getParametersSeparator () {
			return '##';
		}

		/**
		 * Check if image is thumbnail
		 * @param string $sImagePath path to image file
		 * @return bool Result
		 */
		public function isThumb ($sImagePath) {
			return strpos($sImagePath, '/cms/autothumbs/') !== false || strpos($sImagePath, '/cms/thumbs/') !== false;
		}

		/**
		 * Check if image is the image stored by EiP image editor
		 * @param string $sImagePath Path to image file
		 * @return bool Result
		 */
		public function isIeditorImage ($sImagePath) {
			return strpos($sImagePath, $this->getIeditorImagesPath()) !== false;
		}

		/**
		 * Get image editor data stored in image file name
		 * @param string $sImagePath Path to stored image
		 * @return array Data stored in image name
		 * @throws coreException Image file does not exist
		 */
		public function getImageData ($sImagePath = '') {

			if (empty($sImagePath) && getRequest('image_url')) {
				$sImagePath = getRequest('image_url');
			}

			$sImagePath = preg_replace('/\?[0-9]+$/', '', $sImagePath);

			if ($this->isThumb($sImagePath)) {
				if (getRequest('id') && getRequest('field_name')) {
					$sImagePath = $this->getImageUrl(getRequest('id'), getRequest('field_name'));
				}
			}

			if (empty($sImagePath)) {
				return array('result' => false);
			}

			if (strpos($sImagePath, CURRENT_WORKING_DIR) === false) {
				$sImagePath = CURRENT_WORKING_DIR . $sImagePath;
			}

			$arOriginalImages = $this->findOriginalImages($sImagePath);
			if (!empty($arOriginalImages)) {
				$sImagePath = $arOriginalImages[0];
			}

			if (!file_exists($sImagePath)) {
				throw new publicAdminException(getLabel('ieditor-invalid-filename', 'content'));
			}
			$arResult = array(
				'path' => '',	// path to original image
				'width' => 0,	// [optional] width of selection
				'height' => 0,	// [optional] height of selection
				'left' => 0,	// [optional] left offset of selection in crop operation
				'top' => 0,	// [optional] top offset of selection in crop operation
				'naturalWidth' => 0, // Natural width of image
				'naturalHeight' => 0 // Natural height of image
			);

			$arFileInfo = pathinfo($sImagePath);
			$sImagePath = str_replace(CURRENT_WORKING_DIR, '', $sImagePath);
			if ($this->isIeditorImage($sImagePath)) {
				$sFileName = $arFileInfo['filename'];
				$sOriginalImageData = base64_decode($sFileName);
				$arOriginalImageData = explode($this->getParametersSeparator(), $sOriginalImageData);
				$arResult['width'] = isset($arOriginalImageData[1]) ? $arOriginalImageData[1] : 0;
				$arResult['height'] = isset($arOriginalImageData[2]) ? $arOriginalImageData[2] : 0;
				$arResult['left'] = isset($arOriginalImageData[3]) ? $arOriginalImageData[3] : 0;
				$arResult['top'] = isset($arOriginalImageData[4]) ? $arOriginalImageData[4] : 0;
			}
			$arResult['path'] = $sImagePath;
			$oImage = new umiImageFile(CURRENT_WORKING_DIR . $sImagePath);
			$arResult['naturalWidth'] = $oImage->getWidth();
			$arResult['naturalHeight'] = $oImage->getHeight();

			return $arResult;

		}

		/**
		 * Genereate path to original image that will be stored after cropping
		 * @param array $arPathInfo pathinfo() for image file
		 * @param array $arAdditionalInfo Parameters to store in image name
		 * @return string Image path
		 */
		public function generateOriginalImagePath ($arPathInfo, $arAdditionalInfo = array()) {
			$sIeditorImagesFolder = $this->getIeditorImagesPath();
			if (!is_dir($sIeditorImagesFolder)) {
				mkdir($sIeditorImagesFolder);
			}
			$sSeparator = $this->getParametersSeparator();
			return $sIeditorImagesFolder . '/' . base64_encode(str_replace(CURRENT_WORKING_DIR, '', $arPathInfo['dirname']) . '/' . $arPathInfo['basename'] . $sSeparator . join($sSeparator, $arAdditionalInfo)) . '.' . $arPathInfo['extension'];
		}

		/**
		 * Get array of all images that are original to given one
		 * @param string $sImagePath Path to image to find originals for
		 * @return array Found original images
		 */
		public function findOriginalImages ($sImagePath) {
			$sImagePath = preg_replace('/\?[0-9]+$/', '', $sImagePath);
			$arPathInfo = pathinfo($sImagePath);
			clearstatcache();
			$sSearchString = base64_encode(str_replace(CURRENT_WORKING_DIR, '', $arPathInfo['dirname']) . '/' . $arPathInfo['basename'] . $this->getParametersSeparator());
			$sSearchString = preg_replace('/[=]+$/', '', $sSearchString);
			$sSearchString = substr($sSearchString, 0, -1);
			$sSearchString = $this->getIeditorImagesPath() . '/' . $sSearchString . "*." . $arPathInfo['extension'];
			$result = glob($sSearchString);
			if (!is_array($result)) {
				return array();
			}
			return $result;
		}

		/**
		 * Delete all original images for given one
		 * @param string $sImagePath Path to image
		 */
		public function deleteOriginalImages ($sImagePath) {
			$arOriginalImages = $this->findOriginalImages($sImagePath);
			foreach ($arOriginalImages as $sFilePath) {
				unlink($sFilePath);
			}
		}

		/**
		 * Delete Elfinder thumbnail for given image
		 * @param string $sImagePath Image file path
		 */
		public function deleteThumbnail ($sImagePath) {
			@unlink(USER_IMAGES_PATH . '/cms/data/.tmb/' . md5($sImagePath) . '.png');
		}

		/**
		 * Main method of EiP image editor that processes actions
		 * @param string $sAction Action to process
		 * @return String Result of operation
		 * @throws coreException
		 */
		public function ieditor ($sAction) {
			$sImagePath = CURRENT_WORKING_DIR . getRequest('image_url');
			$sImagePath = preg_replace('/\?[0-9]+$/', '', $sImagePath);
			if ($this->isThumb($sImagePath)) {
				$sImagePath = CURRENT_WORKING_DIR . $this->getImageUrl(getRequest('element_id'), getRequest('field_name'));
			}

			if (!file_exists($sImagePath)) {
				return "";
			}

			if (str_replace(CURRENT_WORKING_DIR, '', $sImagePath) == getRequest('empty_url') && $sAction != 'upload') {
				throw new publicAdminException(getLabel("ieditor-uneditable-image", 'content'));
			}

			$this->deleteThumbnail($sImagePath);

			switch ($sAction) {

				case 'rotate':
					return $this->ieditor_rotate($sImagePath);

				case 'upload':
					return $this->ieditor_upload();

				case 'crop':
					return $this->ieditor_crop($sImagePath);

				case 'resize':
					return $this->ieditor_resize($sImagePath);

			}

			return "";

		}

		/**
		 * Handler for resize action
		 * @param string $sImagePath Path to image
		 * @return string Path to edited image or empty string in case of error
		 */
		public function ieditor_resize ($sImagePath){

			$iWidth = intval(getRequest('width'));
			$iHeight = intval(getRequest('height'));

			$processor = imageUtils::getImageProcessor();

			if (!$processor->resize($sImagePath,$iWidth,$iHeight)){
				return '';
			}

			$this->deleteOriginalImages($sImagePath);
			return str_replace(CURRENT_WORKING_DIR, '', $sImagePath);

		}

		/**
		 * Handler for Crop action
		 * @param string $sImagePath Path to image file
		 * @return string Path to edited image or empty string in case of error
		 */
		public function ieditor_crop ($sImagePath){

			clearstatcache();
			$iSelectionLeft = intval(getRequest('x1')) ? intval(getRequest('x1')) : 0;
			$iSelectionTop = intval(getRequest('y1')) ? intval(getRequest('y1')) : 0;
			$iSelectionWidth = intval(getRequest('width'));
			$iSelectionHeight = intval(getRequest('height'));
			$iScale = floatval(getRequest('scale')) ? floatval(getRequest('scale')) : 1;

			if ($iScale < 1) {
				$iSelectionLeft = round($iSelectionLeft / $iScale);
				$iSelectionTop = round($iSelectionTop / $iScale);
				$iSelectionWidth = round($iSelectionWidth / $iScale);
				$iSelectionHeight = round($iSelectionHeight / $iScale);
			}

			$sNewOriginalImagePath = $this->generateOriginalImagePath(pathinfo($sImagePath), array($iSelectionWidth, $iSelectionHeight, $iSelectionLeft, $iSelectionTop));

			$arOriginalImages = $this->findOriginalImages($sImagePath);

			if (empty($arOriginalImages)) {
				$bCopyResult = @copy($sImagePath, $sNewOriginalImagePath);
				if (!$bCopyResult || !file_exists($sNewOriginalImagePath)) {
					return "";
				}
			} else {
				$bRenameResult = @rename($arOriginalImages[0], $sNewOriginalImagePath);
				if (!$bRenameResult || !file_exists($sNewOriginalImagePath)) {
					return "";
				}
			}

			$sTmpImagePath = $this->getIeditorImagesPath(true) . '/__ieditor_tmp';
			if (file_exists($sTmpImagePath)) {
				@unlink($sTmpImagePath);
			}

			@copy($sNewOriginalImagePath, $sTmpImagePath);
			if (!file_exists($sTmpImagePath)) {
				return "";
			}

			$processor = imageUtils::getImageProcessor();

			if (!$processor->crop($sNewOriginalImagePath,$iSelectionTop,$iSelectionLeft,$iSelectionWidth,$iSelectionHeight)){
				return "";
			}

			@unlink($sImagePath);
			@copy($sNewOriginalImagePath, $sImagePath);
			@unlink($sNewOriginalImagePath);
			@rename($sTmpImagePath, $sNewOriginalImagePath);

			return str_replace(CURRENT_WORKING_DIR, '', $sImagePath);

		}

		/**
		 * Handler for Rotate action
		 * @param string $sImagePath Path to image
		 * @return string Path to edited image or empty string in case of error
		 */
		public function ieditor_rotate ($sImagePath) {
			$processor = imageUtils::getImageProcessor();

			if (!$processor->rotate($sImagePath)){
				return "";
			}

			$this->deleteOriginalImages($sImagePath);
			return str_replace(CURRENT_WORKING_DIR, '', $sImagePath);
		}

		/** Handler for Upload action */
		public function ieditor_upload () {
			if (!empty($_FILES)) {
				$oUploadedFile = umiImageFile::upload('eip-ieditor-upload-fileinput', 0, USER_IMAGES_PATH . '/cms/data');
				// Переопределение стандартного вывода, чтобы выводилась просто строка с путем к файлу в plain text, без json.
				// Это нужно для обхода особенностей работы IE, который при выводе в hidden iframe валидного JSON предлагает его сохранить как файл.
				$buffer = \UmiCms\Service::Response()
					->getCurrentBuffer();
				$buffer->push($oUploadedFile->getFilePath(true));
				$buffer->send();
				exit();
			}
		}
	}
