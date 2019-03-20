<?php
	class comments extends def_module {
		public function __construct() {
			parent::__construct();

			$this->loadCommonExtension();

			if($this->cmsController->getCurrentMode() == "admin") {
				$commonTabs = $this->getCommonTabs();
				if($commonTabs) {
						$commonTabs->add('view_comments');
						$commonTabs->add('view_noactive_comments');
				}
				$this->__loadLib("__admin.php");
				$this->__implement("__comments");

				$this->loadAdminExtension();

				$this->__loadLib("__custom_adm.php");
				$this->__implement("__comments_custom_admin");
			}

			$this->loadSiteExtension();

			$this->__loadLib("__custom.php");
			$this->__implement("__custom_comments");

			$regedit = $this->regedit;
			if($regedit->getVal('//modules/comments/default_comments') == NULL) $regedit->setVar('//modules/comments/default_comments', 1);
			$this->per_page = (int) $regedit->getVal("//modules/comments/per_page");
			$this->moderated = (int) $regedit->getVal("//modules/comments/moderated");
		}

		public function countComments($parent_element_id = false) {
			if(!$parent_element_id) return 0;
			$parent_element_id = $this->analyzeRequiredPath($parent_element_id);

			$comments = new selector('pages');
			$comments->types('object-type')->name('comments', 'comment');
			$comments->where('hierarchy')->page($parent_element_id);
			$comments->option('return')->value('count');
			$total = $comments->result();
			$result = Array("node:total" => $total);

			if ($this->isXSLTResultMode()) {
				return $result;
			} else {
				return $total;
			}
		}

		public function insertVkontakte() {
			$regedit = $this->regedit;
			$vkontakte = $regedit->getVal('//modules/comments/vkontakte');
			$per_page = (int) $regedit->getVal('//modules/comments/vk_per_page');
			$width = (int) $regedit->getVal('//modules/comments/vk_width');
			$extend = (bool) $regedit->getVal('//modules/comments/vk_extend');
			$api = (string) $regedit->getVal('//modules/comments/vk_api');
			if ($extend) {$extend = "*";} else {$extend = "false";}
			$block_arr = Array();
			if ($vkontakte != "0" && $vkontakte != NULL) $block_arr['attribute:type'] = "vkontakte";

			$block_arr['per_page'] = $per_page;
			$block_arr['width'] = $width;
			$block_arr['extend'] = $extend;
			$block_arr['api'] = $api;

			return self::parseTemplate(false, $block_arr, false);
		}

		public function insertFacebook() {
			$regedit = $this->regedit;
			$facebook = $regedit->getVal('//modules/comments/facebook');

			$per_page = (int) $regedit->getVal('//modules/comments/fb_per_page');
			$width = (int) $regedit->getVal('//modules/comments/fb_width');
			$colorscheme = (string) $regedit->getVal('//modules/comments/fb_colorscheme');
			$block_arr = Array();
			if ($facebook != "0" && $facebook != NULL) $block_arr['attribute:type'] = "facebook";

			$block_arr['per_page'] = $per_page;
			$block_arr['width'] = $width;
			$block_arr['colorscheme'] = $colorscheme;

			return self::parseTemplate(false, $block_arr, false);
		}

		public function insert($parent_element_id = false, $template = "default", $order = false) {
			$default = $this->regedit->getVal('//modules/comments/default_comments');
			$block_arr = Array();
			if ($default == "0") return self::parseTemplate(false, $block_arr, false);

			if(!$template) $template = "default";

			$parent_element_id = $this->analyzeRequiredPath($parent_element_id);

			list(
				$template_block, $template_line, $template_add_user, $template_add_guest, $template_smiles
			) = self::loadTemplates("comments/".$template,
				"comments_block", "comments_block_line", "comments_block_add_user", "comments_block_add_guest", "smiles"
			);
			$isAuth = permissionsCollection::getInstance()->isAuth();
			if ($isAuth) {
				$template_add = $template_add_user;
			}
			else {
				$template_add = ($this->regedit->getVal("//modules/comments/allow_guest")) ? $template_add_guest : "";
			}

			$oHierarchy = umiHierarchy::getInstance();
			$oParent = $oHierarchy->getElement($parent_element_id);

			$template_line = $template_line;

			$per_page = $this->per_page;
			$curr_page = (int) getRequest('p');

			$comments = new selector('pages');
			$comments->types('object-type')->name('comments', 'comment');
			$comments->where('hierarchy')->page($parent_element_id);
			if ($order) {
				$comments->order('publish_time')->asc();
			} else {
				$comments->order('publish_time')->desc();
			}
			$comments->option('load-all-props')->value(true);
			$comments->limit($curr_page * $per_page, $per_page);

			$result = $comments->result();
			$total = $comments->length();

			$lines = Array();
			$i = 0;
			foreach($result as $element) {
				$line_arr = Array();

				if (!$element instanceof umiHierarchyElement) {
					continue;
				}
				$element_id = $element->getId();
				$line_arr['attribute:id'] = $element_id;
				$line_arr['attribute:title'] = $element->getName();
				$line_arr['attribute:author_id'] = $author_id = $element->getValue("author_id");
				$line_arr['attribute:num'] = ($per_page * $curr_page) + (++$i);
				$line_arr['xlink:href'] = "upage://" . $element_id;
				$line_arr['xlink:author-href'] = "udata://users/viewAuthor/" . $author_id;
				$line_arr['node:message'] = self::formatMessage($element->getValue("message"));

				if($publish_time = $element->getValue('publish_time')) {
					$line_arr['attribute:publish_time'] = $publish_time->getFormattedDate("U");
				}

				$this->pushEditable("comments", "comment", $element_id);

				$lines[] = self::parseTemplate($template_line, $line_arr, $element_id);
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;

			$block_arr['per_page'] = $per_page;
			$block_arr['total'] = $total;

			$add_arr = Array();
			$add_arr['void:smiles'] = $template_smiles;
			$add_arr['action'] = $this->pre_lang . "/comments/post/" . $parent_element_id . "/";
			$template_add = self::parseTemplate($template_add, $add_arr, $parent_element_id);

			if($oParent instanceof umiHierarchyElement) {
				$block_arr['add_form'] = ($oParent->getValue('comments_disallow')) ? '' : $template_add;
			} else {
				$block_arr['add_form'] = $template_add;
			}

			$block_arr['action'] = $this->pre_lang . "/comments/post/" . $parent_element_id . "/";

			if (def_module::isXSLTResultMode()) {
				$isGuestAllowed = $this->regedit->getVal("//modules/comments/allow_guest");

				if(!$isAuth && !$isGuestAllowed) {
					unset($block_arr['action']);
					unset($block_arr['add_form']);
				}
			}
			return self::parseTemplate($template_block, $block_arr, $parent_element_id);
		}

		public function post($parent_element_id = false) {
			$bNeedFinalPanic = false;

			$parent_element_id = (int) $parent_element_id;
			if(!isset($parent_element_id) || !$parent_element_id) {
				$parent_element_id = (int) getRequest('param0');
			}

			$title = trim(getRequest('title'));
			$content = trim(getRequest('comment'));
			$nick = htmlspecialchars(getRequest('author_nick'));
			$email = htmlspecialchars(getRequest('author_email'));

			$referer_url = getServer('HTTP_REFERER');
			$posttime = time();
			$ip = getServer('REMOTE_ADDR');

			if(!$referer_url) {
				$referer_url = umiHierarchy::getInstance()->getPathById($parent_element_id);
			}
			$this->errorRegisterFailPage($referer_url);

			if (!(strlen($title) || strlen($content))) {
				$this->errorNewMessage('%comments_empty%', false);
				$this->errorPanic();
			}

			if (!umiCaptcha::checkCaptcha() || !$parent_element_id) {
				$this->errorNewMessage("%errors_wrong_captcha%");
				$this->errorPanic();
			}

			$user_id = permissionsCollection::getInstance()->getUserId();

			if(!$nick) {
				$nick = getRequest('nick');
			}

			if(!$email) {
				$email = getRequest('email');
			}


			if($nick) {
				$nick = htmlspecialchars($nick);
			}

			if($email) {
				$email = htmlspecialchars($email);
			}

			$is_sv = false;
			if($users_inst = cmsController::getInstance()->getModule("users")) {
				if($users_inst->is_auth()) {
					$author_id = $users_inst->createAuthorUser($user_id);
					$is_sv = permissionsCollection::getInstance()->isSv($user_id);
				} else {
					if(!(regedit::getInstance()->getVal("//modules/comments/allow_guest"))) {
						$this->errorNewMessage('%comments_not_allowed_post%', true);
					}

					$author_id = $users_inst->createAuthorGuest($nick, $email, $ip);
				}
			}

			$is_active = ($this->moderated && !$is_sv) ? 0 : 1;

			if($is_active) {
				$is_active = antiSpamHelper::checkContent($content.$title.$nick.$email) ? 1 : 0;
			}

			if (!$is_active) {
				$this->errorNewMessage('%comments_posted_moderating%', false);
				$bNeedFinalPanic = true;
			}

			$object_type_id = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName("comments", "comment");
			$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("comments", "comment")->getId();

			$parentElement = umiHierarchy::getInstance()->getElement($parent_element_id);
			$tpl_id		= $parentElement->getTplId();
			$domain_id	= $parentElement->getDomainId();
			$lang_id	= $parentElement->getLangId();

			if (!strlen(trim($title)) && ($parentElement instanceof umiHierarchyElement)) {
				$title = "Re: ".$parentElement->getName();
			}

			$element_id = umiHierarchy::getInstance()->addElement($parent_element_id, $hierarchy_type_id, $title, $title, $object_type_id, $domain_id, $lang_id, $tpl_id);
			permissionsCollection::getInstance()->setDefaultPermissions($element_id);

			$element = umiHierarchy::getInstance()->getElement($element_id, true);

			$element->setIsActive($is_active);
			$element->setIsVisible(false);

			$element->setValue("message", $content);
			$element->setValue("publish_time", $posttime);

			$element->getObject()->setName($title);
			$element->setValue("h1", $title);

			$element->setValue("author_id", $author_id);

			$object_id = $element->getObject()->getId();
			$data_module = cmsController::getInstance()->getModule('data');
			$data_module->saveEditedObject($object_id, true);

			// moderate
			$element->commit();
			$parentElement->commit();

			$oEventPoint = new umiEventPoint("comments_message_post_do");
			$oEventPoint->setMode("after");
			$oEventPoint->setParam("topic_id", $parent_element_id);
			$oEventPoint->setParam("message_id", $element_id);
			$this->setEventPoint($oEventPoint);

			// redirect with or without error messages
			if ($bNeedFinalPanic) {
				$this->errorPanic();
			} else {
				// validate url
				$referer_url = preg_replace("/_err=\d+/is", '', $referer_url);
				while (strpos($referer_url, '&&') !== false || strpos($referer_url, '??') !== false || strpos($referer_url, '?&') !== false) {
					$referer_url = str_replace('&&', '&', $referer_url);
					$referer_url = str_replace('??', '?', $referer_url);
					$referer_url = str_replace('?&', '?', $referer_url);
				}
				if (strlen($referer_url) && (substr($referer_url, -1) === '?' || substr($referer_url, -1) === '&')) $referer_url = substr($referer_url, 0, strlen($referer_url)-1);

				$this->redirect($referer_url);

			}
		}

		public function onCommentPost(iUmiEventPoint $event) {
			if($event->getMode() == 'after') {
				$commentId = $event->getParam('message_id');
				antiSpamHelper::checkForSpam($commentId);
			}
		}

		public function getEditLink($element_id, $element_type) {
			switch($element_type) {
				case "comment": {
					$link_edit = $this->pre_lang . "/admin/comments/edit/{$element_id}/";

					return Array(false, $link_edit);
					break;
				}

				default: {
					return false;
				}
			}
		}

		public function comment() {
			$element_id = $this->cmsController->getCurrentElementId();
			$umiHierarchy = umiHierarchy::getInstance();
			$element = $umiHierarchy->getElement($element_id);

			if (!$element) {
				throw new publicException(getLabel('error-page-does-not-exist', null, ''));
			}

			$per_page = $this->per_page;
			$curr_page = (int) getRequest('p');

			$parent_id = $element->getParentId();

			if($element->getValue("publish_time"))
			$publish_time = $element->getValue("publish_time")->getFormattedDate("U");

			$comments = new selector('pages');
			$comments->types('object-type')->name('comments', 'comment');
			$comments->where('hierarchy')->page($parent_id);
			$comments->where('publish_time')->less($publish_time);
			$comments->limit($curr_page * $per_page, $per_page);
			$comments->order('publish_time')->asc();
			$comments->option('return')->value('count');
			$total = $comments->result();

			$p = ceil($total / $this->per_page) - 1;
			if($p < 0) $p = 0;


			$url = $umiHierarchy->getPathById($parent_id) . "?p={$p}#" . $element_id;
			$this->redirect($url);
		}

		public function config() {
			if(class_exists("__comments")) {
				return __comments::config();
			}
		}

		public function smilePanel($elementId = false, $template='default') {
			if ($this->isXSLTResultMode()) {
				throw new publicException(getLabel('error-only-tpl-method'));
			}
			list($templateString) = self::loadTemplates('tpls/comments/'.$template.'.tpl', 'smiles');
			return self::parseTemplate($templateString, array('element'=>$elementId));
		}

	};
?>
