<?php

	abstract class __content extends baseModuleAdmin {


		public function sitetree() {
			$domains = domainsCollection::getInstance()->getList();
			$permissions = permissionsCollection::getInstance();
			$user_id = $permissions->getUserId();

			$this->setDataType("list");
			$this->setActionType("view");

			foreach($domains as $i => $domain) {
				$domain_id = $domain->getId();

				if(!$permissions->isAllowedDomain($user_id, $domain_id)) {
					unset($domains[$i]);
				}
			}

			$data = $this->prepareData($domains, "domains");

			$this->setData($data, count($domains));
			return $this->doData();
		}

		/**
		 * Возвращает данные списка страниц контента для административной панели
		 * @return bool
		 * @throws coreException
		 * @throws selectorException
		 */
		public function tree() {
			$this->setDataType("list");
			$this->setActionType("view");

			/** @var __content|def_module|baseModuleAdmin $this */
			if($this->ifNotXmlMode()) {
				$this->doData();
				return true;
			}

			$limit = getRequest('per_page_limit');
			$currentPage = getRequest('p');
			$offset = $currentPage * $limit;

			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('content', 'page');

			if (is_array(getRequest('rel')) && regedit::getInstance()->getVal('//modules/comments')) {
				$sel->types('hierarchy-type')->name('comments', 'comment');
			}

			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$data = $this->prepareData($sel->result, "pages");

			$this->setData($data, $sel->length);
			$this->setDataRangeByPerPage($limit, $currentPage);
			$this->doData();

			return true;
		}


		public function add() {
			$parent = $this->expectElement("param0");
			$type = (string) getRequest("param1");
			$mode = (string) getRequest("param2");

			$inputData = array(
				'type' => $type,
				'parent' => $parent,
				'type-id' => getRequest('type-id'),
				'allowed-element-types' => array('page', '')
			);

			if($mode == "do") {
				$this->saveAddedElementData($inputData);
				$this->chooseRedirect();
			}

			$this->setDataType("form");
			$this->setActionType("create");

			$data = $this->prepareData($inputData, "page");

			$this->setData($data);
			return $this->doData();
		}


		public function edit() {
			$element = $this->expectElement("param0");
			$mode = (string) getRequest('param1');

			$inputData = array(	"element" => $element,
								"allowed-element-types" => array('page', '')
			);

			if($mode == "do") {
				$this->saveEditedElementData($inputData);
				$this->chooseRedirect();
			}

			$this->setDataType("form");
			$this->setActionType("modify");

			$data = $this->prepareData($inputData, "page");

			$this->setData($data);
			return $this->doData();
		}


		public function config() {
			$domains = domainsCollection::getInstance()->getList();
			$lang_id = cmsController::getInstance()->getCurrentLang()->getId();

			$mode = (string) getRequest('param0');


			$result = Array();

			foreach($domains as $domain) {
				$host = $domain->getHost();
				$domain_id = $domain->getId();

				$result[$host] = Array();

				$templates = templatesCollection::getInstance()->getTemplatesList($domain_id, $lang_id);
				foreach($templates as $template) {
					$result[$host][] = $template;
				}
			}

			if($mode == "do") {
				$this->saveEditedList("templates", $result);
				$this->chooseRedirect();
			}


			$this->setDataType("list");
			$this->setActionType("modify");

			$data = $this->prepareData($result, "templates");

			$this->setData($data);
			return $this->doData();
		}


		public function del() {
			$element = $this->expectElement('param0');

			$params = Array(
				"element" => $element,
				"allowed-element-types" => Array('page', '')
			);

			$this->deleteElement($params);
			$this->chooseRedirect();
		}


		public function tpl_edit() {
			$tpl_id = (int) getRequest('param0');
			$template = templatesCollection::getInstance()->getTemplate($tpl_id);

			$mode = (string) getRequest('param1');

			if($mode == "do") {
				$this->saveEditedTemplateData($template);
				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('modify');

			$data = $this->prepareData($template, 'template');

			$this->setData($data);
			return $this->doData();
		}

		//Events
		public function systemLockPage($eEvent){
			if ($ePage = $eEvent->getRef("element")){
				$userId = $eEvent->getParam("user_id");
				$lockTime = $eEvent->getParam("lock_time");
				$oPage = &$ePage->getObject();
				$oPage->setValue("locktime", $lockTime);
				$oPage->setValue("lockuser", $userId);
				$oPage->commit();
			}
		}
		public function systemUnlockPage($eEvent){
			if ($ePage = $eEvent->getRef("element")){
				$userId = $eEvent->getParam("user_id");
				$oPage = $ePage->getObject();
				$oPage->setValue("locktime", null);
				$oPage->setValue("lockuser", null);
				$oPage->commit();
			}
		}
		//Lock control methods
		public function systemIsLockedById($element_id, $user_id){
			$ePage = umiHierarchy::getElement($element_id);
			$oPage = $ePage->getObject();
			$lockTime = $oPage->getValue("locktime");
			if ($lockTime == null){
				return false;
			}
			$lockUser = $oPage->getValue("lockuser");
			$lockDuration = regedit::getInstance()->getVal("//settings/lock_duration");
			if (($lockTime->timestamp + $lockDuration) > time() && $lockUser!=$user_id){
				return true;
			}else{
				return false;
			}
		}
		public function systemWhoLocked($element_id){
			$ePage = umiHierarchy::getElement($element_id);
			$oPage = $ePage->getObject();
			return $oPage->getValue("lock_user");
		}
		public function systemUnlockAll() {
			$oUsersMdl = cmsController::getInstance()->getModule("users");
			if (!$oUsersMdl->isSv()){
				throw new publicAdminException(getLabel('error-can-unlock-not-sv'));
			}
			$sel = new umiSelection();
			$sel->forceHierarchyTable(true);
			$result = umiSelectionsParser::runSelection($sel);
			foreach ($result as $page_id){
				$ePage = umiHierarchy::getInstance()->getElement($page_id);
				$oPage = $ePage->getObject();
				$oPage->setValue("locktime", null);
				$oPage->setValue("lockuser", null);
				$oPage->commit();
				$ePage->commit();
			}
		}
		public function unlockAll () {
			$this->systemUnlockAll();
			$this->chooseRedirect();
		}
		public function unlockPage($pageId) {
			$element = umiHierarchy::getInstance()->getElement($pageId);
			if($element instanceof umiHierarchyElement) {
				$pageObject = $element->getObject();
				$pageObject->setValue("locktime", 0);
				$pageObject->setValue("lockuser", 0);
				$pageObject->commit();
			}
		}
		public function unlock_page() {
			$pageId = getRequest("param0");
			if (cmsController::getInstance()->getModule("users")->isSv) {
				throw new publicAdminException(getLabel('error-can-unlock-not-sv'));
			}
			$this->unlockPage($pageId);
		}

		public function content_control() {
			$mode = getRequest("param0");
			$regedit = regedit::getInstance();

			$params = array (
				"content_config" => array (
					'bool:lock_pages' => false,
					'int:lock_duration' => 0,
					'bool:expiration_control' => false
				),
				'output_options' => array (
					'int:elements_count_per_page' => null
				)
			);

			if ($mode == "do") {
				$params = $this->expectParams($params);
				$regedit->setVar("//settings/lock_pages", $params['content_config']['bool:lock_pages']);
				$regedit->setVar("//settings/lock_duration", $params['content_config']['int:lock_duration']);
				$regedit->setVar("//settings/expiration_control", $params['content_config']['bool:expiration_control']);
				$regedit->setVar("//settings/elements_count_per_page", $params['output_options']['int:elements_count_per_page']);

				$this->switchGroupsActivity('svojstva_publikacii', (bool) $params['content_config']['bool:expiration_control']);

				$this->chooseRedirect();
			}

			$params['content_config']['bool:lock_pages'] = $regedit->getVal("//settings/lock_pages");
			$params['content_config']['int:lock_duration'] = $regedit->getVal("//settings/lock_duration");
			$params['content_config']['bool:expiration_control'] = $regedit->getVal("//settings/expiration_control");
			$params['output_options']['int:elements_count_per_page'] = $regedit->getVal("//settings/elements_count_per_page");

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");
			$this->setData($data);
			return $this->doData();
		}



		public function getDatasetConfiguration($param = '') {
		    $loadMethod = 'load_tree_node';
		    $deleteMethod = 'tree_delete_element';
		    $activityMethod = 'tree_set_activity';

			$types = array();
			if ($param == 'tree') {
				$types = array('types' => array(
					array('common' => 'true', 'id' => 'page')
				));
				$loadMethod = $param;
			}

			$result = array(
				'methods' => array(
					array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'content', '#__name'=>$loadMethod),
					array('title'=>getLabel('smc-delete'), 					     'module'=>'content', '#__name'=>$deleteMethod, 'aliases' => 'tree_delete_element,delete,del'),
					array('title'=>getLabel('smc-activity'), 		 'module'=>'content', '#__name'=>$activityMethod, 'aliases' => 'tree_set_activity,activity'),
					array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
					array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'move'),
					array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
					array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang'),
					array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'copyElementToSite')),
				'default' => 'name[400px]'
			);

			if (!empty($types)) {
				$result += $types;
			}

			return $result;
		}

		public function getObjectEditLink($objectId, $type = false) {
			return false;
		}

		/**
		 * Устанавливает шаблону флаг "основной"
		 * @param int $templateId ид шаблона, который требуется изменить
		 * @param bool|int $domainId ид домена, к которому относится шаблон. Если не передать - возьмет текущий.
		 * @param bool|int $languageId ид языка, к которому относится шаблон. Если не передать - возьмет текущий.
		 * @return true
		 * @throws publicAdminException если $templateId не является числом
		 * @throws publicAdminException если не удалось получить шаблон по id
		 * @throws publicAdminException если не удалось получить текущий домен
		 * @throws publicAdminException если не удалось получить домен по id
		 * @throws publicAdminException если не удалось получить текущий язык
		 * @throws publicAdminException если не удалось получить язык по id
		 * @throws publicAdminException если не удалось сделать шаблон основным
		 */
		public function setBaseTemplate($templateId = null, $domainId = false, $languageId = false) {
			$templateId = (is_null($templateId)) ? getRequest('param0') : $templateId;

			if (!is_numeric($templateId)) {
				throw new publicAdminException(__METHOD__ . ': wrong template id given: ' . $templateId);
			}

			$templateCollection = templatesCollection::getInstance();
			$template = $templateCollection->getTemplate($templateId);

			if (!$template instanceof template) {
				throw new publicAdminException(__METHOD__ . ': template with id = ' . $templateId . ' was not found');
			}

			$cmsController = cmsController::getInstance();
			$domainId = (is_bool($domainId)) ? getRequest('param1') : $domainId;

			if (!is_numeric($domainId)) {
				$currentDomain = $cmsController->getCurrentDomain();

				if (!$currentDomain instanceof domain) {
					throw new publicAdminException(__METHOD__ . ':  cant get current domain');
				}

				$domainId = $currentDomain->getId();
			}

			$domainsCollection = domainsCollection::getInstance();
			$domain = $domainsCollection->getDomain($domainId);

			if (!$domain instanceof domain) {
				throw new publicAdminException(__METHOD__ . ':  cant get domain by id: ' . $domainId);
			}

			$languageId = (is_bool($languageId)) ? getRequest('param2') : $languageId;

			if (!is_numeric($languageId)) {
				$currentLang = $cmsController->getCurrentLang();

				if (!$currentLang instanceof lang) {
					throw new publicAdminException(__METHOD__ . ':  cant get current language');
				}

				$languageId = $currentLang->getId();
			}

			$languagesCollection = langsCollection::getInstance();
			$language = $languagesCollection->getLang($languageId);

			if (!$language instanceof lang) {
				throw new publicAdminException(__METHOD__ . ':  cant get language by id: ' . $languageId);
			}

			$baseTemplateChanged = $templateCollection->setDefaultTemplate($templateId, $domainId, $languageId);

			if (!$baseTemplateChanged) {
				throw new publicAdminException(__METHOD__ . ':  cant change base template');
			}

			return true;
		}
	};

?>
