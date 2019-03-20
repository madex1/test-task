<?php
	abstract class __dispatches extends baseModuleAdmin {

		public function dispatches_list() {
			$regedit = regedit::getInstance();
			$regedit->setVar("//modules/dispatches/default_method_admin", "lists");
			$this->redirect($this->pre_lang . '/admin/dispatches/lists/');
		}

		public function lists() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();

			$limit = getRequest('per_page_limit');
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('object-type')->name('dispatches', 'dispatch');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "objects");

			$this->setData($data, $sel->length);
			return $this->doData();
		}

		public function subscribers() {

			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();

			$limit = getRequest('per_page_limit');
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$dispatchId = getRequest("param0") ? getRequest("param0") : getRequest('id');
			if(is_array($dispatchId)) $dispatchId = isset($dispatchId[0]) ? $dispatchId[0] : null;

			$sel = new selector('objects');
			$sel->types('object-type')->name('dispatches', 'subscriber');

			if ($dispatchId) $sel->where('subscriber_dispatches')->equals($dispatchId);
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "objects");
			$this->setData($data, $sel->length);
			return $this->doData();
		}

		public function releases() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();

			$limit = getRequest('per_page_limit');
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$dispatchId = getRequest("param0") ? getRequest("param0") : getRequest('id');
			if(is_array($dispatchId)) $dispatchId = isset($dispatchId[0]) ? $dispatchId[0] : null;

			$sel = new selector('objects');
			$sel->types('object-type')->name('dispatches', 'release');
			if ($dispatchId) $sel->where('disp_reference')->equals($dispatchId);
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "objects");
			$this->setData($data, $sel->length);
			return $this->doData();
		}

		public function messages() {
			$per_page = getRequest('per_page_limit');
			$dispatch_id = getRequest("param0");

			$o_dispatch = umiObjectsCollection::getInstance()->getObject($dispatch_id);
			$release_id = false;
			if ($o_dispatch instanceof umiObject) {
				$release_id = $this->getNewReleaseInstanceId($dispatch_id);
			}
			$result = $this->getReleaseMessages($release_id);
			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($result, "objects");
			$this->setData($data);
			return $this->doData();
		}


		public function add() {
			$type = (string) getRequest("param0");
			$mode = (string) getRequest("param1");

			$this->setHeaderLabel("header-dispatches-add-" . $type);

			$inputData = array("type" => $type);

			if($mode == "do") {
				$object = $this->saveAddedObjectData($inputData);

				$added = umiObjectsCollection::getInstance()->getObject($object->getId());
				$added->setValue("subscribe_date", time());
				$added->commit();
				$this->chooseRedirect($this->pre_lang . '/admin/dispatches/edit/' . $object->getId() . "/");
			}

			$this->setDataType("form");
			$this->setActionType("create");

			$data = $this->prepareData($inputData, "object");

			$this->setData($data);
			return $this->doData();
		}

		public function edit() {
			$object = $this->expectObject("param0");
			$mode = (string) getRequest('param1');

			$this->setHeaderLabel("header-dispatches-edit-" . $this->getObjectTypeMethod($object));

			if($mode == "do") {
				$this->saveEditedObjectData($object);
				$this->chooseRedirect();
			}

			$this->setDataType("form");
			$this->setActionType("modify");


			$data = $this->prepareData($object, "object");

			$iTypeId = $object->getTypeId();

			$iHTypeId = umiObjectTypesCollection::getInstance()->getType($iTypeId)->getHierarchyTypeId();
			$oHType = umiHierarchyTypesCollection::getInstance()->getType($iHTypeId);
			if ($oHType->getExt() == 'dispatch') {
				$iReleaseId = $this->getNewReleaseInstanceId($object->getId());
				$arrMess = $this->getReleaseMessages($iReleaseId);
				$data['object']['release'] = array();
				$data['object']['release']['nodes:message'] = $arrMess;

			}

			$this->setData($data);
			return $this->doData();
		}

		public function del() {
			$objects = getRequest('element');
			if(!is_array($objects)) {
				$objects = Array($objects);
			}

			if(getRequest('param0')) {
				$objectId = getRequest('param0');
				$object = $this->expectObject($objectId, false, true);

				$params = Array(
					'object'		=> $object,
					'allowed-element-types' => Array('dispatch', "subscriber", "release", "message")
				);

				$this->deleteObject($params);
				$this->chooseRedirect();
			}

			foreach($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);

				$params = Array(
					'object'		=> $object,
					'allowed-element-types' => Array('dispatch', "subscriber", "release", "message")
				);

				$this->deleteObject($params);
			}

			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($objects, "objects");
			$this->setData($data);

			return $this->doData();
		}

		public function activity() {
			$objects = getRequest('object');
			if(!is_array($objects)) {
				$objects = Array($objects);
			}
			$is_active = (bool) getRequest('active');

			foreach($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);
				$object->setValue("is_active", $is_active);
				$object->commit();
			}

			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($objects, "objects");
			$this->setData($data);

			return $this->doData();
		}


		private function renderDispatch($iDispId) {
			$sResult = "";
			$oDispatch =  umiObjectsCollection::getInstance()->getObject($iDispId);
			$params = array();
			if ($oDispatch instanceof umiObject) {
				$params['disp_name'] = $oDispatch->getName();
				$params['disp_description'] = $oDispatch->getValue('disp_description');
				$sLastRelease = " ";
				$oLastReleaseDate = $oDispatch->GetValue('disp_last_release');
				if ($oLastReleaseDate instanceof umiDate) {
					$sLastRelease = $oLastReleaseDate->getFormattedDate("d.m.Y H:i");
				}
				$params['disp_last_release'] = $sLastRelease;
				$params['disp_id'] = $iDispId;
				// subscribers
				$params['disp_subscribers'] = "";

				$sResult = $this->parse_form("dispatches_list_row", $params);
			}
			return $sResult;
		}

		public function dispatch_del() {
			$iDispId = $_REQUEST['param0'];
			umiObjectsCollection::getInstance()->delObject($iDispId);
			$this->redirect($this->pre_lang . "/admin/dispatches/dispatches_list/");
		}

		public function getDatasetConfiguration($param = '') {
			switch($param) {
				case 'lists':
					$loadMethod = 'lists';
					$delMethod  = 'del';
					$typeId		= umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('dispatches', 'dispatch');
					$defaults	= 'name[400px]|disp_description[250px]|disp_last_release[250px]';
					break;
				case 'subscribers' :
					$loadMethod = 'subscribers';
					$delMethod  = 'del';
					$typeId		= umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('dispatches', 'subscriber');
					$defaults	= 'name[400px]|subscriber_dispatches[250px]';
					break;
				case 'releases' :
					$loadMethod = 'releases';
					$delMethod  = 'form_delete';
					$typeId		= umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('dispatches', 'release');
					$defaults	= 'name[400px]|date[250px]';
					break;
				default:
					$loadMethod = 'messages';
					$delMethod  = 'del';
					$typeId		= umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('dispatches', 'message');
					$defaults	= 'name[400px]|msg_date[250px]';
			}
			return array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 'module'=>'dispatches', '#__name'=>$loadMethod),
						array('title'=>getLabel('smc-delete'), 				  'module'=>'dispatches', '#__name'=>'del', 'aliases' => 'tree_delete_element,delete,del'),
						array('title'=>getLabel('smc-activity'), 		 'module'=>'dispatches', '#__name'=>'activity', 'aliases' => 'tree_set_activity,activity'),
					),
					'types' => array(
						array('common' => 'true', 'id' => $typeId)
					),
					'stoplist' => array('disp_reference', 'new_relation', 'release_reference'),
					'default' => $defaults
			);
		}

		public function onPropertyChanged($e) {
			$object = $e->getRef('entity');
			$objectType = umiObjectTypesCollection::getInstance()->getType($object->getTypeId());

			if ($e->getMode() != "before" || $objectType->getModule() != 'dispatches' || $objectType->getMethod() != "subscriber") {
				return;
			}

			$newValue = &$e->getRef('newValue');

			switch ((string) $e->getParam('property')) {
				case 'name' : {
					if (!umiMail::checkEmail($newValue)) {
						$this->errorAddErrors('error-email-wrong-format');
						$newValue = false;
					}
					break;
				}

				default:
					return;
			}

			$this->errorThrow('xml');

		}

		public function onModifyObject($e) {
			$object = $e->getRef('object');
			$objectType = umiObjectTypesCollection::getInstance()->getType($object->getTypeId());
			if($e->getMode() != "before" || $objectType->getModule() != "dispatches" || $objectType->getMethod() != "subscriber") {
				return;
			}
			$subscriberHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypebyName("dispatches", "subscriber")->getId();
			$subscriberTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeId($subscriberHierarchyTypeId);
			if($subscriberTypeId == $object->getTypeId()) {
				if (!umiMail::checkEmail(getRequest('name'))) {
					$this->errorNewMessage(getLabel('error-email-wrong-format'), true);
					return;
				}
			}
		}

		public function onCreateObject($e) {
			$object = $e->getRef('object');
			$objectType = umiObjectTypesCollection::getInstance()->getType($object->getTypeId());

			header("Content-type: text/plain");

			if($objectType->getModule() != "dispatches" || $objectType->getMethod() != "subscriber" || $e->getMode() != "before") {
				return;
			}

			if (!umiMail::checkEmail(getRequest('name'))) {
				umiObjectsCollection::getInstance()->delObject($object->getId());
				$this->errorRegisterFailPage($this->pre_lang . "/admin/dispatches/add/subscriber/");
				$this->errorNewMessage(getLabel('error-email-wrong-format'), true);
				return;
			}

			$sel = new selector('objects');
			$sel->types('object-type')->id($objectType->getId());
			$sel->where('name')->equals((string) getRequest('name'));
			$sel->limit(1, 1);

			if($sel->first) {
				umiObjectsCollection::getInstance()->delObject($sel->first->id);

				$this->errorRegisterFailPage($this->pre_lang . "/admin/dispatches/add/subscriber/");
				$this->errorNewMessage(getLabel('error-subscriber-exists'), true);
			}
		}

		/**
		 * Обработчик события "systemModifyObject", в котором при
		 * необходимости выполняется смена рассылки, в которую будут выгружаться новые темы форума
		 * @param iUmiEventPoint $event объект с данными о произошедшем событии
		 */
		public function changeLoadForumOptionModify(iUmiEventPoint $event) {
			static $oldValue;
			$mode = $event->getMode();
			$object = $event->getRef('object');

			if (!$this->isDispatch($object)) {
				return;
			}

			if ($mode === 'before') {
				$oldValue = $object->getValue('load_from_forum');
			}

			if ($mode === 'after') {
				$newValue = $object->getValue('load_from_forum');

				if ($oldValue !== $newValue && $newValue) {
					$this->changeLoadFromForumDispatch($object);
				}
			}
		}

		/**
		 * Обработчик события "systemModifyPropertyValue", в котором при
		 * необходимости выполняется смена рассылки, в которую будут выгружаться новые темы форума
		 * @param iUmiEventPoint $event объект с данными о произошедшем событии
		 */
		public function changeLoadForumOptionQuickEdit(iUmiEventPoint $event) {
			$mode = $event->getMode();
			$object = $event->getRef('entity');

			if (!$this->isDispatch($object)) {
				return;
			}

			if ($mode === 'after') {
				$oldValue = $event->getParam('oldValue');
				$newValue = $event->getParam('newValue');
				$propertyName = $event->getParam('property');

				if ($propertyName === 'load_from_forum' && $oldValue !== $newValue && $newValue) {
					$this->changeLoadFromForumDispatch($object);
				}
			}

		}

		/**
		 * Является ли объект - рассылкой
		 * @param iUmiObject $object проверяемый объект
		 * @return bool
		 */
		public function isDispatch($object) {
			if (!$object instanceof iUmiObject) {
				return false;
			}
			$type = $object->getType();
			return ($type instanceof iUmiObjectType && $type->getModule() === 'dispatches' && $type->getMethod() === 'dispatch');
		}


		/**
		 * Изменяет рассылку, в которую будут выгружаться новые темы форума,
		 * @param iUmiObject $dispatch новая рассылка
		 * @throws selectorException
		 */
		public function changeLoadFromForumDispatch($dispatch) {
			$dispatches = new selector('objects');
			$dispatches->types('object-type')->name('dispatches', 'dispatch');
			$dispatches->where('load_from_forum')->equals(true);

			/** @var iUmiObject $dispatch */
			foreach ($dispatches as $object) {
				$object->setValue('load_from_forum', false);
				$object->commit();
			}

			$regEdit = regedit::getInstance();
			$regEdit->setVal('//modules/forum/dispatch_id', $dispatch->getId());
			$dispatch->setValue('load_from_forum', true);
			$dispatch->commit();
		}
	};
?>
