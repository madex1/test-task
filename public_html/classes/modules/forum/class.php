<?php
	class forum extends def_module {
		public $per_page = 10;

		public function __construct() {
			parent::__construct();

			$this->loadCommonExtension();

			if ($this->cmsController->getCurrentMode() == "admin") {
				$commonTabs = $this->getCommonTabs();
				if($commonTabs) {
						$commonTabs->add('lists', array('confs_list'));
						$commonTabs->add('last_messages');
				}
				// admin mode methods
				$this->__loadLib("__admin.php");
				$this->__implement("__forum");

				$this->loadAdminExtension();

				// custom admin methods
				$this->__loadLib("__custom_adm.php");
				$this->__implement("__forum_custom_admin");
			} else {
				// front-end events handlers methods
				$this->__loadLib("__sysevents.php");
				$this->__implement("__sysevents_forum");
			}

			// common (admin and front-end) events handlers methods
			$this->__loadLib("__events_handlers.php");
			$this->__implement("__events_handlers_forum");

			$this->loadSiteExtension();

			// front-end custom methods
			$this->__loadLib("__custom.php");
			$this->__implement("__custom_forum");

			if ($per_page = (int) $this->regedit->getVal("//modules/forum/per_page")) {
				$this->per_page = $per_page;
			}
		}

		// ======== FRONT-END METHODS (MACROSES) ===============================

		public function confs_list($template = "default", $v_parent_path = '', $i_deep = 0, $ignore_paging = false) {
			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$this->redirect($this->pre_lang . '/admin/forum/lists/');
			}

			if(!$template) $template = "default";
			list($template_block, $template_line) = self::loadTemplates("forum/".$template, "confs_block", "confs_block_line");

			if (!$v_parent_path) $v_parent_path = getRequest('param1');

			if($v_parent_path) {
				$i_parent_id = $this->analyzeRequiredPath($v_parent_path);
				if (!$i_parent_id) {
					$v_parent_path = getRequest('item');
					$i_parent_id = $this->analyzeRequiredPath($v_parent_path);
				}
			} else {
				$i_parent_id = false;
			}

			if (!$i_deep) $i_deep = intval(getRequest('param2'));
			if (!$i_deep) $i_deep = 0;

			// =================================================================
			$per_page = $this->per_page;
			$curr_page = getRequest('p');
			if($ignore_paging) $curr_page = 0;

			$conference = new selector('pages');
			$conference->types('object-type')->name('forum', 'conf');
			if ($i_parent_id) {
				$conference->where('hierarchy')->page($i_parent_id)->childs($i_deep);
			}
			$conference->option('load-all-props')->value(true);
			$conference->limit($curr_page * $per_page, $per_page);
			$result = $conference->result();
			$total = $conference->length();

			$lines = Array();
			foreach ($result as $conf) {
				$conf_element_id = $conf->getId();
				$line_arr = Array();
				$line_arr['attribute:id'] = $conf_element_id;
				$line_arr['node:name'] = $conf->getName();
				$line_arr['attribute:link'] = $this->umiLinksHelper->getLinkByParts($conf);
				$line_arr['attribute:topics_count'] = $conf->getValue('topics_count');
				$line_arr['attribute:messages_count'] = $conf->getValue('messages_count');
				$line_arr['xlink:href'] = "upage://" . $conf_element_id;
				$lines[] = self::parseTemplate($template_line, $line_arr, $conf_element_id);

				$this->pushEditable("forum", "conf", $conf_element_id);
			}

			$block_arr = Array();
			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			return self::parseTemplate($template_block, $block_arr);
		}

		public function conf($template = "default", $per_page = false, $ignore_context = false, $ignore_paging = false) {
			if(!$template) $template = "default";
			list($template_block, $template_line) = self::loadTemplates("forum/".$template, "topics_block", "topics_block_line");

			$element_id = cmsController::getInstance()->getCurrentElementId();
			$this->pushEditable("forum", "conf", $element_id);

			$per_page = ($per_page)? $per_page : $this->per_page;
			$curr_page = getRequest('p');
			if($ignore_paging) $curr_page = 0;

			$topics = new selector('pages');
			$topics->types('object-type')->name('forum', 'topic');
			if (!$ignore_context) {
				$topics->where('hierarchy')->page($element_id);
			}
			if (getRequest('order_property')) {
				$b_asc = false;
				$s_order_direction = getRequest('order_direction');
				if (strtoupper($s_order_direction) === 'ASC') $b_asc = true;
				$s_order_property = getRequest('order_property');
				if (!$s_order_property) $s_order_property = 'publish_time';
				switch ($s_order_property) {
					case 'sys::ord':
						$topics->order('ord')->asc();
						break;
					case 'sys::rand':
						$topics->order('rand');
						break;
					case 'sys::name':
						if ($b_asc) {
							$topics->order('ord')->asc();
						} else {
							$topics->order('ord')->desc();
						}
						break;
					case 'sys::objectid':
						if ($b_asc) {
							$topics->order('id')->asc();
						} else {
							$topics->order('id')->desc();
						}
						break;
					default:
						if ($b_asc) {
							$topics->order('publish_time')->asc();
						} else {
							$topics->order('publish_time')->desc();
						}
						break;
				}
			} else {
				if($this->regedit->getVal("//modules/forum/sort_by_last_message")) {
					$topics->order('last_post_time')->desc();
				} else {
					$topics->order('publish_time')->desc();
				}

			}
			$topics->option('load-all-props')->value(true);
			$topics->limit($curr_page * $per_page, $per_page);

			$result = $topics->result();
			$total = $topics->length();

			$block_arr = Array();

			$lines = Array();
			foreach($result as $topic) {
				if (!$topic instanceof umiHierarchyElement) {
					continue;
				}
				$line_arr = Array();
				$topic_element_id = $topic->getId();
				$line_arr['attribute:id'] = $topic_element_id;
				$line_arr['attribute:link'] = $this->umiLinksHelper->getLinkByParts($topic);
				$line_arr['attribute:messages_count'] = $topic->getValue('messages_count');
				$line_arr['xlink:href'] = "upage://" . $topic_element_id;
				$line_arr['node:name'] = $topic->getName();
				$lines[] = self::parseTemplate($template_line, $line_arr, $topic_element_id);
				$this->pushEditable("forum", "topic", $topic_element_id);
			}

			$block_arr['attribute:id'] = $element_id;
			$block_arr['subnodes:lines'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			return self::parseTemplate($template_block, $block_arr, $element_id);
		}


		public function topic($template = "default", $per_page = false, $ignore_context = false) {
			if(!$template) $template = "default";
			list($template_block, $template_line) = self::loadTemplates("forum/".$template, "messages_block", "messages_block_line");

			$element_id = $this->cmsController->getCurrentElementId();

			$unsubscribe_user_id = (string) getRequest('unsubscribe');
			if($unsubscribe_user_id) {
				$unsubscribe_user_id = base64_decode($unsubscribe_user_id);
				$unsubscribe_user = $this->umiObjectsCollection->getObject($unsubscribe_user_id);
				if($unsubscribe_user instanceof umiObject) {
					$topic_id = $element_id;

					$subscribed_pages = $unsubscribe_user->getValue("subscribed_pages");

					$tmp = Array();
					foreach($subscribed_pages as $page) {
						if($page->getId() != $topic_id) {
							$tmp[] = $page;
						}
					}
					$subscribed_pages = $tmp;
					unset($tmp);
					$unsubscribe_user->setValue("subscribed_pages", $subscribed_pages);
					$unsubscribe_user->commit();
				}
			}
			$this->pushEditable("forum", "topic", $element_id);
			$per_page = ($per_page) ? $per_page : $this->per_page;
			$curr_page = getRequest('p');

			$messages = new selector('pages');
			$messages->types('object-type')->name('forum', 'message');
			if (!$ignore_context) {;
				$messages->where('hierarchy')->page($element_id);
			}
			if (getRequest('order_property')) {
				$b_asc = false;
				$s_order_direction = getRequest('order_direction');
				if (strtoupper($s_order_direction) === 'ASC') $b_asc = true;
				$s_order_property = getRequest('order_property');
				if (!$s_order_property) $s_order_property = 'publish_time';
				switch ($s_order_property) {
					case 'sys::ord':
						$messages->order('ord')->asc();
						break;
					case 'sys::rand':
						$messages->order('rand');
						break;
					case 'sys::name':
						if ($b_asc) {
							$messages->order('name')->asc();
						} else {
							$messages->order('name')->desc();
						}
						break;
					case 'sys::objectid':
						if ($b_asc) {
							$messages->order('id')->asc();
						} else {
							$messages->order('id')->desc();
						}
						break;
					default:
						if ($b_asc) {
							$messages->order('publish_time')->asc();
						} else {
							$messages->order('publish_time')->desc();
						}
						break;
				}
			} else {
				$messages->order('publish_time')->desc();
			}
			$messages->option('load-all-props')->value(true);
			$messages->limit($curr_page * $per_page, $per_page);

			$result = $messages->result();
			$total = $messages->length();

			$lines = Array();
			$i = 0;
			foreach ($result as $messageObject) {
				if (!$messageObject instanceof umiHierarchyElement) {
					continue;
				}
				$i++;
				$message_element_id = $messageObject->getId();
				$line_arr = Array();
				$line_arr['attribute:id'] = $message_element_id;
				$line_arr['attribute:name'] = $messageObject->getName();
				$line_arr['attribute:num'] = ($per_page * $curr_page) + $i + 1;
				$line_arr['attribute:author_id'] = $author_id = $messageObject->getValue("author_id");
				$line_arr['attribute:publish_time'] = $messageObject->getValue("publish_time");
				$line_arr['xlink:href'] = "upage://" . $message_element_id;
				$line_arr['xlink:author-href'] = "udata://users/viewAuthor/" . $author_id;

				$message = $messageObject->getValue("message");
				$line_arr['node:message'] = self::formatMessage($message);

				$lines[] = self::parseTemplate($template_line, $line_arr, $message_element_id);

				$this->pushEditable("forum", "message", $message_element_id);

				umiHierarchy::getInstance()->unloadElement($element_id);
			}
			$block_arr = Array();
			$block_arr['attribute:id'] = $element_id;
			$block_arr['subnodes:lines'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			return self::parseTemplate($template_block, $block_arr, $element_id);
		}

		public function message() {
			$element_id = $this->cmsController->getCurrentElementId();
			$umiHierarchy = umiHierarchy::getInstance();
			$element = $umiHierarchy->getElement($element_id);

			if (!$element) {
				throw new publicException(getLabel('error-page-does-not-exist', null, ''));
			}

			$per_page = $this->per_page;
			$curr_page = (int) getRequest('p');

			$parent_id = $element->getParentId();
			$parent_element = $umiHierarchy->getElement($parent_id);

			if (!$parent_element) {
				throw new publicException(getLabel('error-parent-does-not-exist', null, ''));
			}

			if ($element->getValue("publish_time"))
			$publish_time = $element->getValue("publish_time")->getFormattedDate("U");

			$messages = new selector('pages');
			$messages->types('object-type')->name('forum', 'message');
			$messages->where('hierarchy')->page($parent_id);
			$messages->where('publish_time')->less($publish_time);
			$messages->option('return')->value('count');
			$messages->limit($curr_page * $per_page, $per_page);
			$messages->order('publish_time')->asc();
			$total = $messages->result();

			$p = floor(($total - 1) / $this->per_page);
			if($p < 0) $p = 0;

			$url = $umiHierarchy->getPathById($parent_id) . "?p={$p}#" . $element_id;
			$this->redirect($url);
		}

		public function topic_last_message($path, $template = "default") {
			if(!defined("DISABLE_SEARCH_REINDEX")) {
				define("DISABLE_SEARCH_REINDEX", "1");
			}
			if(!$template) $template = "default";
			list($template_block) = self::loadTemplates("forum/".$template, "topic_last_message");
			$hierarchy = umiHierarchy::getInstance();

			$parentElementId = $this->analyzeRequiredPath($path);
			if($parentElementId === false && $path != KEYWORD_GRAB_ALL) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $path));
			}

			$messageElementId = $this->getLastMessageId($parentElementId);
			$block_arr = array();
			if($messageElementId) {
				$messageElement = $hierarchy->getElement($messageElementId);

				$block_arr['attribute:id'] = $messageElementId;
				$block_arr['attribute:name'] = $messageElement->getName();
				$block_arr['attribute:link'] = $this->getMessageLink($messageElementId);
				$block_arr['attribute:author_id'] = $messageElement->getValue("author_id");
				$block_arr['xlink:href'] = "upage://" . $messageElementId;
				$block_arr['node:message'] = self::formatMessage($messageElement->getValue("message"));

				if($publishTime = $messageElement->getValue("publish_time")) {
					if($publishTime instanceof iUmiDate) {
						$publishTime = $publishTime->getFormattedDate("U");

						if($parentElement = $messageElement->getRel()) {
							$parentElement = $hierarchy->getElement($parentElementId);
							$parentElement->setValue("last_post_time", $publishTime);
							$parentElement->commit();
						}
					}
				}
			} else {
				return "";
			}

			$this->pushEditable("forum", "message", $messageElementId);
			return self::parseTemplate($template_block, $block_arr, $messageElementId);
		}

		public function conf_last_message($path, $template = "default") {
			if(!$template) $template = "default";
			list($template_block) = self::loadTemplates("forum/".$template, "conf_last_message");

			$parentElementId = $this->analyzeRequiredPath($path);
			if($parentElementId === false && $path != KEYWORD_GRAB_ALL) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $path));
			}

			$hierarchy = umiHierarchy::getInstance();
			$messageElementId = $this->getLastMessageId($parentElementId);

			$block_arr = array();
			if($messageElementId) {
				$messageElement = $hierarchy->getElement($messageElementId);
				if(!$messageElement) return "";

				$block_arr['attribute:id'] = $messageElementId;
				$block_arr['attribute:name'] = $messageElement->getName();
				$block_arr['attribute:link'] = $this->getMessageLink($messageElementId);
				$block_arr['attribute:author_id'] = $messageElement->getValue("author_id");
				$block_arr['xlink:href'] = "upage://" . $messageElementId;
				$block_arr['node:message'] = self::formatMessage($messageElement->getValue("message"));
			} else return "";

			$this->pushEditable("forum", "message", $messageElementId);
			return self::parseTemplate($template_block, $block_arr, $messageElementId);
		}


		public function topic_post($elementPath, $template = "default") {
			$element_id = $this->analyzeRequiredPath($elementPath);
			$hierarchy = umiHierarchy::getInstance();

			$element = $hierarchy->getElement($elementPath);
			if($element instanceof iUmiHierarchyElement == false) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $elementPath));
			}

			if($element->comments_disallow) return '';

			if(!$template) $template = "default";

			list($template_block_user, $template_block_guest, $template_smiles) = self::loadTemplates("forum/".$template, "add_topic_user", "add_topic_guest", "smiles");

			if(permissionsCollection::getInstance()->isAuth()) {
				$template = $template_block_user;
			} else {
				if(!(regedit::getInstance()->getVal("//modules/forum/allow_guest"))) {
					return "";
				}

				$template = $template_block_guest;
			}

			return self::parseTemplate($template, array(
				'void:smiles'	=> $template_smiles,
				'id'		=> $element->getId(),
				'name'		=> $element->getName(),
				'action'	=> $this->pre_lang . '/forum/topic_post_do/' . $element->getId() . '/'
			), $element_id);
		}

		public function message_post($elementPath = false, $template = "default") {
			$element_id = $this->analyzeRequiredPath($elementPath);
			if(!$element_id) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $elementPath));
			}


			/** @desc Check for commenting disallow */
			$oTopic = umiHierarchy::getInstance()->getElement($element_id);
			if($oTopic->getValue('comments_disallow')) return '';

			if(!$template) $template = "default";
			list($template_block_user, $template_block_guest, $template_smiles) = self::loadTemplates("forum/".$template, "add_message_user", "add_message_guest", "smiles");

			if(permissionsCollection::getInstance()->isAuth()) {
				$template = $template_block_user;
			} else {
				if(!(regedit::getInstance()->getVal("//modules/forum/allow_guest"))) {
					return "";
				}

				$template = $template_block_guest;
			}

			$block_arr = Array();
			$block_arr['void:smiles'] = $template_smiles;

			if($element = umiHierarchy::getInstance()->getElement($element_id)) {
				$block_arr['id'] = $element_id;
				$block_arr['name'] = $element->getName();
				$block_arr['action'] = $this->pre_lang . "/forum/message_post_do/" . $element_id . "/";
			}

			return self::parseTemplate($template, $block_arr, $element_id);
		}

		public function topic_post_do() {
			if($users_inst = cmsController::getInstance()->getModule("users")) {
				if(!$users_inst->is_auth()) {
					if(!(regedit::getInstance()->getVal("//modules/forum/allow_guest"))) {
						return "%forum_not_allowed_post%";
					}
				}
			}


			$parent_id = (int) getRequest('param0');
			$parent_element = umiHierarchy::getInstance()->getElement($parent_id);

			$title = getRequest('title');
			$body = getRequest('body');

			$title = htmlspecialchars($title);
			$body = htmlspecialchars($body);

			$nickname = htmlspecialchars(getRequest('nickname'));
				if (!$nickname) $nickname = htmlspecialchars(getRequest('login'));
			$email = htmlspecialchars(getRequest('email'));

			$ip = $_SERVER['REMOTE_ADDR'];

			$publish_time = new umiDate(time());

			if (!umiCaptcha::checkCaptcha()) {
				$this->errorNewMessage('%errors_wrong_captcha%', false);
				$this->errorPanic();
			}

			if(!strlen(trim($title))) {
				$this->errorNewMessage('%error_title_empty%', false);
				$this->errorPanic();
			}

			if(!strlen(trim($body))) {
				$this->errorNewMessage('%error_message_empty%',	false);
				$this->errorPanic();
			}

			$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
			$domain_id = cmsController::getInstance()->getCurrentDomain()->getId();
			$tpl_id = $parent_element->getTplId();
			$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("forum", "topic")->getId();
			$object_type_id = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName("forum", "topic");

			$is_supervisor = false;
			if($users_inst = cmsController::getInstance()->getModule("users")) {
				if($users_inst->is_auth()) {
					$user_id = $users_inst->user_id;
					$is_supervisor = $users_inst->isSv($user_id);
					$author_id = $users_inst->createAuthorUser($user_id);
				} else {
					$author_id = $users_inst->createAuthorGuest($nickname, $email, $ip);
				}
			}

			$element_id = umiHierarchy::getInstance()->addElement($parent_id, $hierarchy_type_id, $title, $title, $object_type_id, $domain_id, $lang_id, $tpl_id);
			permissionsCollection::getInstance()->setDefaultPermissions($element_id);

			$element = umiHierarchy::getInstance()->getElement($element_id, true);
			$element->setIsVisible(false);

			$bNeedModerate = !$is_supervisor && regedit::getInstance()->getVal("//modules/forum/need_moder");

			if(!$bNeedModerate) {
				$bNeedModerate = !antiSpamHelper::checkContent($body.$title.$nickname.$email);
			}
			$element->setIsActive(!$bNeedModerate);

			$element->setAltName($title);

			$element->getObject()->setName($title);

			$element->setValue("meta_descriptions", "");
			$element->setValue("meta_keywords", "");
			$element->setValue("h1", $title);
			$element->setValue("title", $title);
			$element->setValue("is_expanded", false);
			$element->setValue("show_submenu", false);
			$element->setValue("author_id", $author_id);
			$element->setValue("publish_time", $publish_time);

			if ($headers = umiFile::upload("pics", "headers", USER_IMAGES_PATH . "/cms/headers/")) {
				$element->setValue("header_pic", $headers);
			}

			$element->commit();

			$_REQUEST['param0'] = $element_id;

			if (!$bNeedModerate) {
				$this->recalcCounts($element);
			}

			$oEventPoint = new umiEventPoint("forum_topic_post_do");
			$oEventPoint->setParam("topic_id", $element_id);
			$this->setEventPoint($oEventPoint);

			$this->message_post_do();
		}

		public function message_post_do() {
			if($users_inst = cmsController::getInstance()->getModule("users")) {
				if(!$users_inst->is_auth()) {
					if(!(regedit::getInstance()->getVal("//modules/forum/allow_guest"))) {
						return "%forum_not_allowed_post%";
					}
				}
			}

			$title = getRequest('title');
			$body = getRequest('body');

			$title = htmlspecialchars($title);
			$body = htmlspecialchars($body);

			$nickname = htmlspecialchars(getRequest('nickname'));
			$email = htmlspecialchars(getRequest('email'));

			$ip = getServer('REMOTE_ADDR');

			$publish_time = new umiDate(time());

			$parent_id = (int) getRequest('param0');
			$parent_element = umiHierarchy::getInstance()->getElement($parent_id, true);

			if (!strlen(trim($title)) && ($parent_element instanceof umiHierarchyElement)) {
				$title = "Re: ".$parent_element->getName();
			}

			// check captcha
			$referer_url = isset($_SERVER['HTTP_REFERER'])? $_SERVER['HTTP_REFERER'] : '/';

			if (!umiCaptcha::checkCaptcha() || !$parent_element) {
				$this->errorNewMessage('%errors_wrong_captcha%', false);
				$this->errorPanic();
			}
            if(!strlen(trim($body))) {
            	$this->errorNewMessage('%error_message_empty%', false);
				$this->errorPanic();
            }

			$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
			$domain_id = cmsController::getInstance()->getCurrentDomain()->getId();
			$tpl_id = $parent_element->getTplId();
			$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("forum", "message")->getId();
			$object_type_id = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName("forum", "message");

			$is_supervisor = false;
			if ($users_inst = cmsController::getInstance()->getModule("users")) {
				if ($users_inst->is_auth()) {
					$user_id = $users_inst->user_id;
					$author_id = $users_inst->createAuthorUser($user_id);
					$is_supervisor = $users_inst->isSv($user_id);
				} else {
					$author_id = $users_inst->createAuthorGuest($nickname, $email, $ip);
				}
				$author = umiObjectsCollection::getInstance()->getObject($author_id);
				$author->commit();
			}

			$element_id = umiHierarchy::getInstance()->addElement($parent_id, $hierarchy_type_id, $title, $title, $object_type_id, $domain_id, $lang_id, $tpl_id);
			permissionsCollection::getInstance()->setDefaultPermissions($element_id);

			$element = umiHierarchy::getInstance()->getElement($element_id, true);
			$element->setIsVisible(false);

			$bNeedModerate = !$is_supervisor && regedit::getInstance()->getVal("//modules/forum/need_moder");

			if(!$bNeedModerate) {
				$bNeedModerate = !antiSpamHelper::checkContent($body.$title.$nickname.$email);
			}

			$element->setIsActive(!$bNeedModerate);
			$element->setAltName($title);
			$element->getObject()->setName($title);

			$element->setValue("meta_descriptions", "");
			$element->setValue("meta_keywords", "");
			$element->setValue("h1", $title);
			$element->setValue("title", $title);
			$element->setValue("is_expanded", false);
			$element->setValue("show_submenu", false);
			$element->setValue("message", $body);
			$element->setValue("author_id", $author_id);
			$element->setValue("publish_time", $publish_time);

			if ($headers = umiFile::upload("pics", "headers", USER_IMAGES_PATH . "/cms/headers/")) {
				$element->setValue("header_pic", $headers);
			}

			$object_id = $element->getObject()->getId();
			$data_module = cmsController::getInstance()->getModule('data');
			$data_module->saveEditedObject($object_id, true);
			$element->commit();

			if(!defined("DISABLE_SEARCH_REINDEX")) {
				define("DISABLE_SEARCH_REINDEX", 1);
			}

			if($parent_id) {
				$parentElement = umiHierarchy::getInstance()->getElement($element->getRel());
				if($parentElement instanceof umiHierarchyElement) {
					$parentElement->setValue("last_message", $element_id);
					$parentElement->setValue("last_post_time", time());
					$parentElement->commit();
				}

				$parentElement = umiHierarchy::getInstance()->getElement($parentElement->getRel());
				if($parentElement instanceof umiHierarchyElement) {
					$parentElement->setValue("last_message", $element_id);
					$parentElement->commit();
				}
			}

			if (!$bNeedModerate) {
				$this->recalcCounts($element);
			}

			$oEventPoint = new umiEventPoint("forum_message_post_do");
			$oEventPoint->setMode("after");
			$oEventPoint->setParam("topic_id", $parent_id);
			$oEventPoint->setParam("message_id", $element_id);
			$this->setEventPoint($oEventPoint);
			$path = $bNeedModerate ? $referer_url : $this->getMessageLink($element_id);
			$this->redirect($path);
		}


		public function getMessageLink($element_id = false) {
			$element_id = $this->analyzeRequiredPath($element_id);

			$per_page = $this->per_page;
			$curr_page = (int) getRequest('p');

			$element = umiHierarchy::getInstance()->getElement($element_id, true);

			if (!$element) {
				throw new publicException(getLabel('error-page-does-not-exist', null, ''));
			}

			$parent_id = $element->getParentId();
			$parent_element = umiHierarchy::getInstance()->getElement($parent_id);

			if (!$parent_element) {
				throw new publicException(getLabel('error-parent-does-not-exist', null, ''));
			}

			if($element->getValue("publish_time"))
			$publish_time = $element->getValue("publish_time")->getFormattedDate("U");

			$messages = new selector('pages');
			$messages->types('object-type')->name("forum", "message");
			$messages->where('hierarchy')->page($parent_id);
			$messages->where('publish_time')->less($publish_time);
			$messages->order('publish_time')->desc();
			$messages->option('return')->value('count');
			$messages->limit($curr_page * $per_page, $per_page);
			$total = $messages->result();

			$p = floor(($total - 1) / $this->per_page);
			if($p < 0) $p = 0;

			return umiHierarchy::getInstance()->getPathById($parent_id) . "?p={$p}#" . $element_id;
		}

		// ======== INNER SERVICES =============================================
		/** @deprecated */
		public function getHTypeByName($s_name) {
			$i_htype = 0;
			//
			$o_htype = umiHierarchyTypesCollection::getInstance()->getTypeByName("forum", $s_name);
			if ($o_htype instanceof umiHierarchyType) $i_htype = intval($o_htype->getId());
			//
			return $i_htype;
		}
		/** @deprecated */
		public function getOTypeByName($s_name) {
			$i_otype = 0;
			//
			$i_otype = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName("forum", $s_name);
			//
			return $i_otype;
		}
		/** @deprecated */
		public function getFieldId($s_type_name, $s_field_name) {
			$object_type_id = $this->getOTypeByName($s_type_name);
			$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);
			return $object_type->getFieldId($s_field_name);
		}

		public function makeAdminOutputList($s_data_action, $s_items_type, $o_selection, $i_limit, $i_offset) {

			// process selection
			$arr_result = umiSelectionsParser::runSelection($o_selection);
			$i_total = umiSelectionsParser::runSelectionCounts($o_selection);

			// Устанавливаем тип для вывода данных в "list" - список
			$this->setDataType("list");

			// Устанавливаем действие над списокм
			$this->setActionType($s_data_action);

			// Указываем диапозон данных
			$this->setDataRange($i_limit, $i_offset);

			// Подготавливаем данные, чтобы потом корректно их вывести
			$data = $this->prepareData($arr_result, $s_items_type);

			// Завершаем вывод
			$this->setData($data, $i_total);
			return $this->doData();
		}

		public function makeAdminOutputForm($s_data_action, $s_item_type, $inputData) {
			$this->setDataType("form");
			$this->setActionType($s_data_action);
			$data = $this->prepareData($inputData,  $s_item_type);
			$this->setData($data);
			return $this->doData();
		}

		/** @deprecated */
		public function getConfTopicsCount($confElementId) {
			$element = selector::get('page')->id($confElementId);
			if($element instanceof iUmiHierarchyElement) {
				return $element->topics_count;
			} else return '';
		}

		/** @deprecated */
		public function getConfMessagesCount($confElementId) {
			$element = selector::get('page')->id($confElementId);
			if($element instanceof iUmiHierarchyElement) {
				return $element->messages_count;
			} else return '';
		}

		/** @deprecated */
		public function getTopicMessagesCount($topicElementId) {
			$element = selector::get('page')->id($topicElementId);
			if($element instanceof iUmiHierarchyElement) {
				return $element->messages_count;
			} else return '';
		}

		// ======== ??? ========================================================

		public function config () {
			if (class_exists("__forum")) {
				return __forum::config();
			}
		}

		public function getEditLink($element_id, $element_type) {
			$link_edit = $this->pre_lang."/admin/forum/edit/". $element_id."/";

			switch($element_type) {
				case "conf":
					$link_add = $this->pre_lang."/admin/forum/add/".$element_id."/topic/";
					return Array($link_add, $link_edit);
					break;
				case "topic":
					$link_add = $this->pre_lang."/admin/forum/add/".$element_id."/message/";
					return Array($link_add, $link_edit);
					break;
				case "message":
					$link_add = false;
					return Array($link_add, $link_edit);
					break;
				default:
					return false;
					break;
			}
		}

		protected function getLastMessageId($elementId) {
			$element = selector::get('page')->id($elementId);
			if($element) {
				$lastMessage = $element->last_message;
				if(count($lastMessage) && false) {
					$lastMessage = getArrayKey($lastMessage, 0);
					return ($lastMessage instanceof iUmiHierarchyElement) ? $lastMessage->id : false;
				} else {
					$lastMessage = $this->calculateLastMessageId($element);
					if($lastMessage) {
						if(!defined('DISABLE_SEARCH_REINDEX')) {
							define('DISABLE_SEARCH_REINDEX', '1');
						}

						$element->last_message = $lastMessage;
						$element->commit();
						return $lastMessage->id;
					}
				}
			}
			return false;
		}
	};
?>
