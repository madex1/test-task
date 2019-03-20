<?php

	use UmiCms\Service;

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class CommentsMacros {

		/** @var comments $module */
		public $module;

		/**
		 * Возвращает количество комментариев, дочерних
		 * заданной странице
		 * @param bool|int|string $parent_element_id адрес или идентификатор страницы
		 * @return array|int
		 * @throws selectorException
		 */
		public function countComments($parent_element_id = false) {
			if (!$parent_element_id) {
				return 0;
			}

			$parent_element_id = $this->module->analyzeRequiredPath($parent_element_id);

			$comments = new selector('pages');
			$comments->types('object-type')->name('comments', 'comment');
			$comments->where('hierarchy')->page($parent_element_id);
			$comments->option('return')->value('count');
			$total = $comments->result();
			$result = [
				'node:total' => $total
			];

			if ($this->module->isXSLTResultMode()) {
				return $result;
			}

			return $total;
		}

		/**
		 * Возвращает данные для виджета комментариев Вконтакте
		 * @return mixed
		 */
		public function insertVkontakte() {
			$umiRegistry = Service::Registry();
			$vkontakte = $umiRegistry->get('//modules/comments/vkontakte');
			$per_page = (int) $umiRegistry->get('//modules/comments/vk_per_page');
			$width = (int) $umiRegistry->get('//modules/comments/vk_width');
			$extend = (bool) $umiRegistry->get('//modules/comments/vk_extend');
			$api = (string) $umiRegistry->get('//modules/comments/vk_api');

			$block_arr = [];
			$block_arr['attribute:type'] = ($vkontakte != '0' && $vkontakte != null) ? 'vkontakte' : null;
			$block_arr['per_page'] = $per_page;
			$block_arr['width'] = $width;
			$block_arr['extend'] = $extend ? '*' : 'false';
			$block_arr['api'] = $api;
			return comments::parseTemplate(false, $block_arr);
		}

		/**
		 * Возвращает данные для виджета комментариев Facebook
		 * @return mixed
		 */
		public function insertFacebook() {
			$umiRegistry = Service::Registry();
			$facebook = $umiRegistry->get('//modules/comments/facebook');
			$per_page = (int) $umiRegistry->get('//modules/comments/fb_per_page');
			$width = (int) $umiRegistry->get('//modules/comments/fb_width');
			$colorscheme = (string) $umiRegistry->get('//modules/comments/fb_colorscheme');

			$block_arr = [];
			$block_arr['attribute:type'] = ($facebook != '0' && $facebook != null) ? 'facebook' : null;
			$block_arr['per_page'] = $per_page;
			$block_arr['width'] = $width;
			$block_arr['colorscheme'] = $colorscheme;
			return comments::parseTemplate(false, $block_arr);
		}

		/**
		 * Возвращает список комментариев, дочерних заданной странице
		 * и action для формы добавления комментария
		 * @param bool|string|int $parent_element_id адрес или идентификатор страницы
		 * @param string $template имя шаблона (для tpl)
		 * @param bool|int $order порядок сортировки по дате публикации комментария
		 * В качестве значения можно передать "1" (прямой порядок) или "0" (обратный порядок).
		 * @return mixed
		 * @throws selectorException
		 */
		public function insert($parent_element_id = false, $template = 'default', $order = false) {
			$umiRegistry = Service::Registry();
			$default = $umiRegistry->get('//modules/comments/default_comments');
			$block_arr = [];

			if ($default == '0') {
				return comments::parseTemplate(false, $block_arr);
			}

			if (!$template) {
				$template = 'default';
			}

			$parent_element_id = $this->module->analyzeRequiredPath($parent_element_id);

			list(
				$template_block, $template_line, $template_add_user, $template_add_guest, $template_smiles
				) = comments::loadTemplates(
				'comments/' . $template,
				'comments_block',
				'comments_block_line',
				'comments_block_add_user',
				'comments_block_add_guest',
				'smiles'
			);

			$isAuthorized = Service::Auth()->isAuthorized();

			if ($isAuthorized) {
				$template_add = $template_add_user;
			} else {
				$template_add = $umiRegistry->get('//modules/comments/allow_guest') ? $template_add_guest : '';
			}

			$oHierarchy = umiHierarchy::getInstance();
			$oParent = $oHierarchy->getElement($parent_element_id);
			$per_page = $this->module->per_page;
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

			$lines = [];
			$i = 0;

			foreach ($result as $element) {
				$line_arr = [];

				if (!$element instanceof iUmiHierarchyElement) {
					continue;
				}

				$element_id = $element->getId();
				$line_arr['attribute:id'] = $element_id;
				$line_arr['attribute:title'] = $element->getName();
				$line_arr['attribute:author_id'] = $author_id = $element->getValue('author_id');
				$line_arr['attribute:num'] = ($per_page * $curr_page) + (++$i);
				$line_arr['xlink:href'] = 'upage://' . $element_id;
				$line_arr['xlink:author-href'] = 'udata://users/viewAuthor/' . $author_id;
				$line_arr['node:message'] = $this->module->formatMessage($element->getValue('message'));
				$publish_time = $element->getValue('publish_time');

				if ($publish_time instanceof umiDate) {
					$line_arr['attribute:publish_time'] = $publish_time->getFormattedDate('U');
				}

				comments::pushEditable('comments', 'comment', $element_id);
				$lines[] = comments::parseTemplate($template_line, $line_arr, $element_id);
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;

			$block_arr['per_page'] = $per_page;
			$block_arr['total'] = $total;

			$add_arr = [];
			$add_arr['void:smiles'] = $template_smiles;
			$add_arr['action'] = $this->module->pre_lang . '/comments/post/' . $parent_element_id . '/';
			$template_add = comments::parseTemplate($template_add, $add_arr, $parent_element_id);

			if ($oParent instanceof iUmiHierarchyElement) {
				$block_arr['add_form'] = $oParent->getValue('comments_disallow') ? '' : $template_add;
			} else {
				$block_arr['add_form'] = $template_add;
			}

			$block_arr['action'] = $this->module->pre_lang . '/comments/post/' . $parent_element_id . '/';

			if (comments::isXSLTResultMode()) {
				$isGuestAllowed = $umiRegistry->get('//modules/comments/allow_guest');

				if (!$isAuthorized && !$isGuestAllowed) {
					unset($block_arr['action']);
					unset($block_arr['add_form']);
				}
			}
			return comments::parseTemplate($template_block, $block_arr, $parent_element_id);
		}

		/**
		 * Возвращает шаблон смайлов (метод только для tpl)
		 * @param bool $elementId идентификатор страницы
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws publicException
		 */
		public function smilePanel($elementId = false, $template = 'default') {
			if (comments::isXSLTResultMode()) {
				throw new publicException(getLabel('error-only-tpl-method'));
			}

			list($templateString) = comments::loadTemplates(
				'tpls/comments/' . $template . '.tpl',
				'smiles'
			);

			return comments::parseTemplate($templateString, [
				'element' => $elementId
			]);
		}

		/**
		 * Обрабатывает запрос страницы комментария.
		 * Перенаправляет на страницу которая должна содержать этот комментарий,
		 * с учетом пагинации.
		 * @throws publicException
		 * @throws selectorException
		 */
		public function comment() {
			$cmsController = cmsController::getInstance();
			$element_id = $cmsController->getCurrentElementId();
			$umiHierarchy = umiHierarchy::getInstance();
			$element = $umiHierarchy->getElement($element_id);

			if (!$element) {
				throw new publicException(getLabel('error-page-does-not-exist', null, ''));
			}

			$per_page = $this->module->per_page;
			$curr_page = (int) getRequest('p');

			$parent_id = $element->getParentId();
			$publish_time = null;

			if ($element->getValue('publish_time') instanceof umiDate) {
				$publish_time = $element->getValue('publish_time')->getFormattedDate('U');
			}

			$comments = new selector('pages');
			$comments->types('object-type')->name('comments', 'comment');
			$comments->where('hierarchy')->page($parent_id);

			if ($publish_time !== null) {
				$comments->where('publish_time')->less($publish_time);
			}

			$comments->limit($curr_page * $per_page, $per_page);
			$comments->order('publish_time')->asc();
			$comments->option('return')->value('count');
			$total = $comments->result();

			$p = ceil($total / $this->module->per_page) - 1;

			if ($p < 0) {
				$p = 0;
			}

			$url = $umiHierarchy->getPathById($parent_id) . "?p={$p}#" . $element_id;
			$this->module->redirect($url);
		}

		/**
		 * Добавляет комментарий, дочерний заданной странице
		 * и перенаправляет на referrer.
		 * @param bool|int $parent_element_id идентификатор страницы
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 */
		public function post($parent_element_id = false) {
			$bNeedFinalPanic = false;
			$parent_element_id = (int) $parent_element_id;

			if (!isset($parent_element_id) || !$parent_element_id) {
				$parent_element_id = (int) getRequest('param0');
			}

			$title = trim(getRequest('title'));
			$content = trim(getRequest('comment'));
			$nick = htmlspecialchars(getRequest('author_nick'));
			$email = htmlspecialchars(getRequest('author_email'));

			$referrer_url = getServer('HTTP_REFERER');
			$postTime = time();
			$ip = getServer('REMOTE_ADDR');

			$umiHierarchy = umiHierarchy::getInstance();

			if (!$referrer_url) {
				$referrer_url = $umiHierarchy->getPathById($parent_element_id);
			}

			$referrer_url = (string) $referrer_url;
			$this->module->errorRegisterFailPage($referrer_url);

			if (!($title !== '' || $content !== '')) {
				$this->module->errorNewMessage('%comments_empty%', false);
				$this->module->errorPanic();
			}

			$this->module->postCheckCaptcha($parent_element_id);

			$umiPermissions = permissionsCollection::getInstance();
			$auth = Service::Auth();
			$user_id = $auth->getUserId();

			if (!$nick) {
				$nick = getRequest('nick');
			}

			if (!$email) {
				$email = getRequest('email');
			}

			if ($nick) {
				$nick = htmlspecialchars($nick);
			}

			if ($email) {
				$email = htmlspecialchars($email);
			}

			$umiRegistry = Service::Registry();
			$isAuthorized = $auth->isAuthorized();

			if (!$isAuthorized && !$umiRegistry->get('//modules/comments/allow_guest')) {
				$this->module->errorNewMessage('%comments_not_allowed_post%');
			}

			$cmsController = cmsController::getInstance();
			/** @var users $userModule */
			$userModule = $cmsController->getModule('users');

			if ($isAuthorized) {
				$author_id = $userModule->createAuthorUser($user_id);
			} else {
				$author_id = $userModule->createAuthorGuest($nick, $email, $ip);
			}

			$is_active = ($this->module->moderated && !$umiPermissions->isSv()) ? 0 : 1;

			if ($is_active) {
				$is_active = antiSpamHelper::checkContent($content . $title . $nick . $email) ? 1 : 0;
			}

			if (!$is_active) {
				$this->module->errorNewMessage('%comments_posted_moderating%', false);
				$bNeedFinalPanic = true;
			}

			$object_type_id = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('comments', 'comment');
			$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName('comments', 'comment')->getId();

			$parentElement = $umiHierarchy->getElement($parent_element_id);
			$tpl_id = $parentElement->getTplId();
			$domain_id = $parentElement->getDomainId();
			$lang_id = $parentElement->getLangId();

			if (trim($title) === '' && ($parentElement instanceof iUmiHierarchyElement)) {
				$title = 'Re: ' . $parentElement->getName();
			}

			$element_id = $umiHierarchy->addElement(
				$parent_element_id, $hierarchy_type_id, $title, $title, $object_type_id, $domain_id, $lang_id, $tpl_id
			);
			$umiPermissions->setDefaultPermissions($element_id);
			$element = $umiHierarchy->getElement($element_id, true);

			$element->setIsActive($is_active);
			$element->setIsVisible(false);
			$element->setValue('message', $content);
			$element->setValue('publish_time', $postTime);
			$element->setName($title);
			$element->setValue('h1', $title);
			$element->setValue('author_id', $author_id);
			$object_id = $element->getObjectId();
			/** @var data|DataForms $data_module */
			$data_module = $cmsController->getModule('data');
			$data_module->saveEditedObject($object_id, true);

			$element->commit();
			$parentElement->commit();

			$oEventPoint = new umiEventPoint('comments_message_post_do');
			$oEventPoint->setMode('after');
			$oEventPoint->setParam('topic_id', $parent_element_id);
			$oEventPoint->setParam('message_id', $element_id);
			comments::setEventPoint($oEventPoint);

			if ($bNeedFinalPanic) {
				$this->module->errorPanic();
			}

			$referrer_url = preg_replace("/_err=\d+/is", '', $referrer_url);

			while (contains($referrer_url, '&&') || contains($referrer_url, '??') || contains($referrer_url, '?&')) {
				$referrer_url = str_replace('&&', '&', $referrer_url);
				$referrer_url = str_replace('??', '?', $referrer_url);
				$referrer_url = str_replace('?&', '?', $referrer_url);
			}

			if ($referrer_url !== '' && (mb_substr($referrer_url, -1) === '?' || mb_substr($referrer_url, -1) === '&')) {
				$referrer_url = mb_substr($referrer_url, 0, mb_strlen($referrer_url) - 1);
			}

			$this->module->redirect($referrer_url);
		}

		/**
		 * Проверяет captcha при отправке комментария.
		 * @param bool|int $parentElementId идентификатор страницы
		 * @throws errorPanicException
		 */
		public function postCheckCaptcha($parentElementId) {
			if (!umiCaptcha::checkCaptcha() || !$parentElementId) {
				$this->module->errorNewMessage('%errors_wrong_captcha%', true, false, 'captcha');
				$this->module->errorPanic();
			}
		}
	}

