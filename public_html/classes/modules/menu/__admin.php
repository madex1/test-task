<?php
abstract class __menu extends baseModuleAdmin {
 
	public function config() {
		$regedit = regedit::getInstance();
		$params = array(
			'config' => array(
				'string:login' => NULL,
				'string:password' => NULL,
				'string:telefon' => NULL,
				'string:message' => NULL
			)
		);
		$mode = getRequest("param0");
		 
		if($mode == "do") {
		$params = $this->expectParams($params);
		$regedit->setVar("//modules/menu/login", $params['config']['string:login']);
		$regedit->setVar("//modules/menu/password", $params['config']['string:password']);
		$regedit->setVar("//modules/menu/telefon", $params['config']['string:telefon']);
		$regedit->setVar("//modules/menu/message", $params['config']['string:message']);
		$this->chooseRedirect();
		}
		 
		$params['config']['string:login'] =  $regedit->getVal("//modules/menu/login");
		$params['config']['string:password'] = $regedit->getVal("//modules/menu/password");
		$params['config']['string:telefon'] = $regedit->getVal("//modules/menu/telefon");
		$params['config']['string:message'] = $regedit->getVal("//modules/menu/message");
		 
		$this->setDataType("settings");
		$this->setActionType("modify");
		 
		$data = $this->prepareData($params, "settings");
		 
		$this->setData($data);
		return $this->doData();
	}
	 
	 
	public function lists() {
		$this->setDataType("list");
		$this->setActionType("view");
		 
		if($this->ifNotXmlMode()) return $this->doData();
		 
		$limit = getRequest('per_page_limit');
		$curr_page = getRequest('p');
		$offset = $curr_page * $limit;
		 
		$sel = new selector('objects');
		$sel->types('object-type')->name('menu', 'item_element'); //put your data type
		$sel->limit($offset, $limit);
		 
		selectorHelper::detectFilters($sel);
		 
		$data = $this->prepareData($sel->result, "objects");
		 
		$this->setData($data, $sel->length);
		$this->setDataRangeByPerPage($limit, $curr_page);
		return $this->doData();
	}
	 
	public function add() {
		$type = (string) getRequest('param0');
		$mode = (string) getRequest('param1');
		$this->setHeaderLabel("header-menu-add-" . $type);
		 
		$inputData = array(
			'type'					=> $type,
			'type-id' 				=> getRequest('type-id'),
			'allowed-element-types'	=> array('menu', 'item_element')
		);

		if($mode == "do") {
			$object = $this->saveAddedObjectData($inputData);
			$object->commit();
			$this->chooseRedirect($this->pre_lang . '/admin/menu/edit/' . $object->getId() . '/');
		}
		 
		$this->setDataType("form");
		$this->setActionType("create");

		$data = $this->prepareData($inputData, "object");

		$this->setData($data);
		return $this->doData();
	}
	 
	 
	public function edit() {
		$object = $this->expectObject("param0", true);
		$mode = (string) getRequest('param1');

		$this->setHeaderLabel("header-menu-edit-" . $this->getObjectTypeMethod($object));
		 
		$inputData = Array(	
			"object"	=> $object,
			'allowed-element-types'	=> array('menu', 'item_element')
		);
			 
		if($mode == "do") {
			$object = $this->saveEditedObjectData($inputData);
			$this->chooseRedirect();
		}

		$oldJSON = $object->getValue('menuhierarchy');
		$values = json_decode($oldJSON);
		$values = $this->editLinkMenu($values);
		$newJSON = json_encode($values);

		if ($oldJSON != $newJSON) {
			$object->setValue('menuhierarchy', $newJSON);
			$object->commit();
		}

		$this->setDataType("form");
		$this->setActionType("modify");
		 
		$data = $this->prepareData($inputData, "object");
		 
		$this->setData($data);
		return $this->doData();
	}

	 
	public function del() {
		$objects = getRequest('element');
		if(!is_array($objects)) {
			$objects = Array($objects);
		}
		 
		foreach($objects as $objectId) {
			$object = $this->expectObject($objectId, false, true);		 
			$params = Array(
				'object'		=> $object,
				'allowed-element-types' => Array('menu', 'item_element')
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
	 
	public function getDatasetConfiguration($param = '') {
		$result = array(
				'methods' => array(
					array('title'=>getLabel('smc-load'), 'forload'=>true, 'module'=>'menu', '#__name'=>'lists'),
					array('title'=>getLabel('smc-delete'), 'module'=>'menu', '#__name'=>'del', 'aliases' => 'tree_delete_element,delete,del'),
					array('title'=>getLabel('smc-activity'), 'module'=>'menu', '#__name'=>'activity', 'aliases' => 'tree_set_activity,activity')),
				'types' => array(
					array('common' => 'true', 'id' => 'item_element')
				),
				'stoplist' => array('menuhierarchy'),
				'default' => 'name[400px]'
			);	
		return $result;
	}

	public function getUrlSuffix() {
		$suffix = '';
		$config = mainConfiguration::getInstance();
		if ($config->get('seo', 'url-suffix.add')) {
			$suffix = $config->get('seo', 'url-suffix');
		}
		return $suffix;
	}
};
?>