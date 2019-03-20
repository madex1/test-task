<?php

	use UmiCms\Service;

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class ForumMacros {

		/** @var forum $module */
		public $module;

		/**
		 * Возвращает список конференция форума, дочерних определенной страницы
		 * @param string $template имя шаблона (для tpl)
		 * @param string|int $v_parent_path идентификатор или адрес родитеской страницы
		 * @param int $i_deep уровень вложенности, на котором расположены искомые конференции
		 * @param bool $ignore_paging игнорировать пагинацию
		 * @return mixed
		 * @throws selectorException
		 */
		public function confs_list($template = 'default', $v_parent_path = '', $i_deep = 0, $ignore_paging = false) {
			if (!$template) {
				$template = 'default';
			}

			list($template_block, $template_line) = forum::loadTemplates(
				'forum/' . $template,
				'confs_block',
				'confs_block_line'
			);

			if (!$v_parent_path) {
				$v_parent_path = getRequest('param1');
			}

			if ($v_parent_path) {
				$i_parent_id = $this->module->analyzeRequiredPath($v_parent_path);
				if (!$i_parent_id) {
					$v_parent_path = getRequest('item');
					$i_parent_id = $this->module->analyzeRequiredPath($v_parent_path);
				}
			} else {
				$i_parent_id = false;
			}

			if (!$i_deep) {
				$i_deep = (int) getRequest('param2');
			}
			if (!$i_deep) {
				$i_deep = 0;
			}

			$per_page = $this->module->per_page;
			$curr_page = getRequest('p');

			if ($ignore_paging) {
				$curr_page = 0;
			}

			$conference = new selector('pages');
			$conference->types('object-type')->name('forum', 'conf');

			if ($i_parent_id) {
				$conference->where('hierarchy')->page($i_parent_id)->childs($i_deep);
			}

			$conference->option('load-all-props')->value(true);
			$conference->limit($curr_page * $per_page, $per_page);
			$result = $conference->result();
			$total = $conference->length();

			$umiLinksHelper = umiLinksHelper::getInstance();
			$lines = [];
			/** @var iUmiHierarchyElement $conf */
			foreach ($result as $conf) {
				$conf_element_id = $conf->getId();
				$line_arr = [];
				$line_arr['attribute:id'] = $conf_element_id;
				$line_arr['node:name'] = $conf->getName();
				$line_arr['attribute:link'] = $umiLinksHelper->getLinkByParts($conf);
				$line_arr['attribute:topics_count'] = $conf->getValue('topics_count');
				$line_arr['attribute:messages_count'] = $conf->getValue('messages_count');
				$line_arr['xlink:href'] = 'upage://' . $conf_element_id;
				$lines[] = forum::parseTemplate($template_line, $line_arr, $conf_element_id);
				forum::pushEditable('forum', 'conf', $conf_element_id);
			}

			$block_arr = [];
			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			return forum::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Возвращает список топиков конференции форума
		 * На результат выполнения макроса влияют следующие GET-параметры переданые в запросе страницы:
		 * 	order_property - ключ сортировки.
		 * 		"ord" - порядок следования топиков в рамках родительского раздела.
		 * 		"rand" - случайный порядок вывода постов.
		 * 		"name" - сортировка постов по названию.
		 * 		"objectid" - сортировка постов по id
		 * Если этот параметр не указывать, то порядок вывода определяется датой публикации и порядком сортировки.
		 *
		 * 	order_direction - Порядок сортировки топиков на странице. 
		 * 		"desc" — обратный порядок, установлен по умолчанию. 
		 * 		"acs" — прямой порядок, от первого к последнему по выбранному в order_property параметру.
		 * @param string $template имя шаблона (для tpl)
		 * @param bool|int $per_page ограничение на количество выводимых топиков
		 * @param bool $ignore_context выводить все топики без привязки к иерархии
		 * @param bool $ignore_paging игнорировать пагинацию
		 * @param bool|int|string $confId идентификатор или адрес конференции, содержащей искомые топики
		 * @return mixed
		 * @throws selectorException
		 */
		public function conf(
			$template = 'default',
			$per_page = false,
			$ignore_context = false,
			$ignore_paging = false,
			$confId = false
		) {
			if (!$template) {
				$template = 'default';
			}

			list($template_block, $template_line) = forum::loadTemplates(
				'forum/' . $template,
				'topics_block',
				'topics_block_line'
			);

			$element_id = $this->module->analyzeRequiredPath($confId);
			forum::pushEditable('forum', 'conf', $element_id);

			$per_page = $per_page ?: $this->module->per_page;
			$curr_page = getRequest('p');

			if ($ignore_paging) {
				$curr_page = 0;
			}

			$topics = new selector('pages');
			$topics->types('object-type')->name('forum', 'topic');

			if (!$ignore_context) {
				$topics->where('hierarchy')->page($element_id);
			}

			if (getRequest('order_property')) {
				$b_asc = false;
				$s_order_direction = getRequest('order_direction');

				if (mb_strtoupper($s_order_direction) === 'ASC') {
					$b_asc = true;
				}
				$s_order_property = getRequest('order_property');

				if (!$s_order_property) {
					$s_order_property = 'publish_time';
				}

				switch ($s_order_property) {
					case 'sys::ord' : {
						$topics->order('ord')->asc();
						break;
					}
					case 'sys::rand' : {
						$topics->order('rand');
						break;
					}
					case 'sys::name': {
						if ($b_asc) {
							$topics->order('ord')->asc();
						} else {
							$topics->order('ord')->desc();
						}
						break;
					}
					case 'sys::objectid': {
						if ($b_asc) {
							$topics->order('id')->asc();
						} else {
							$topics->order('id')->desc();
						}
						break;
					}
					default: {
						if ($b_asc) {
							$topics->order('publish_time')->asc();
						} else {
							$topics->order('publish_time')->desc();
						}
					}
				}
			} else {
				$umiRegistry = Service::Registry();
				if ($umiRegistry->get('//modules/forum/sort_by_last_message')) {
					$topics->order('last_post_time')->desc();
				} else {
					$topics->order('publish_time')->desc();
				}
			}
			$topics->option('load-all-props')->value(true);
			$topics->limit($curr_page * $per_page, $per_page);

			$result = $topics->result();
			$total = $topics->length();

			$umiLinksHelper = umiLinksHelper::getInstance();
			$block_arr = [];
			$lines = [];

			foreach ($result as $topic) {
				if (!$topic instanceof iUmiHierarchyElement) {
					continue;
				}
				$line_arr = [];
				$topic_element_id = $topic->getId();
				$line_arr['attribute:id'] = $topic_element_id;
				$line_arr['attribute:link'] = $umiLinksHelper->getLinkByParts($topic);
				$line_arr['attribute:messages_count'] = $topic->getValue('messages_count');
				$line_arr['xlink:href'] = 'upage://' . $topic_element_id;
				$line_arr['node:name'] = $topic->getName();
				$lines[] = forum::parseTemplate($template_line, $line_arr, $topic_element_id);
				forum::pushEditable('forum', 'topic', $topic_element_id);
			}

			$block_arr['attribute:id'] = $element_id;
			$block_arr['subnodes:lines'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			return forum::parseTemplate($template_block, $block_arr, $element_id);
		}

		/**
		 * Возвращает список сообщений топика форума.
		 * Если передать в $_REQUEST['unsubscribe'] идентификатор пользователя,
		 * то он будет отписан от топика.
		 * На результат выполнения макроса влияют следующие GET-параметры переданые в запросе страницы:
		 * 	order_property - ключ сортировки.
		 * 		"ord" - порядок следования сообщений в рамках родительского раздела.
		 * 		"rand" - случайный порядок вывода постов.
		 * 		"name" - сортировка постов по названию.
		 * 		"objectid" - сортировка постов по id
		 * Если этот параметр не указывать, то порядок вывода определяется датой публикации и порядком сортировки.
		 *
		 * 	order_direction - Порядок сортировки постов на странице. 
		 * 		"desc" — обратный порядок, установлен по умолчанию. 
		 * 		"acs" — прямой порядок, от первого к последнему по выбранному в order_property параметру.
		 * @param string $template имя шаблона (для tpl)
		 * @param bool|int $per_page ограничение на количество выводимых сообщений
		 * @param bool $ignore_context выводить все сообщения без привязки к иерархии
		 * @param bool|int|string $topicId идентификатор или адрес топика, содержащей искомые сообщения
		 * @return mixed
		 * @throws selectorException
		 */
		public function topic($template = 'default', $per_page = false, $ignore_context = false, $topicId = false) {
			if (!$template) {
				$template = 'default';
			}
			list($template_block, $template_line) = forum::loadTemplates(
				'forum/' . $template,
				'messages_block',
				'messages_block_line'
			);

			$element_id = $this->module->analyzeRequiredPath($topicId);
			$unSubscribe_user_id = (string) getRequest('unsubscribe');
			$umiObjectsCollection = umiObjectsCollection::getInstance();

			if ($unSubscribe_user_id) {
				$unSubscribe_user_id = base64_decode($unSubscribe_user_id);
				$unSubscribe_user = $umiObjectsCollection->getObject($unSubscribe_user_id);

				if ($unSubscribe_user instanceof iUmiObject) {
					$topic_id = $element_id;
					$subscribed_pages = $unSubscribe_user->getValue('subscribed_pages');
					$tmp = [];

					/** @var iUmiHierarchyElement $page */
					foreach ($subscribed_pages as $page) {
						if ($page->getId() != $topic_id) {
							$tmp[] = $page;
						}
					}
					$subscribed_pages = $tmp;
					unset($tmp);
					$unSubscribe_user->setValue('subscribed_pages', $subscribed_pages);
					$unSubscribe_user->commit();
				}
			}

			forum::pushEditable('forum', 'topic', $element_id);
			$per_page = $per_page ?: $this->module->per_page;
			$curr_page = getRequest('p');

			$messages = new selector('pages');
			$messages->types('object-type')->name('forum', 'message');

			if (!$ignore_context) {
				$messages->where('hierarchy')->page($element_id);
			}

			if (getRequest('order_property')) {
				$b_asc = false;
				$s_order_direction = getRequest('order_direction');

				if (mb_strtoupper($s_order_direction) === 'ASC') {
					$b_asc = true;
				}

				$s_order_property = getRequest('order_property');

				if (!$s_order_property) {
					$s_order_property = 'publish_time';
				}
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
			$umiHierarchy = umiHierarchy::getInstance();

			$lines = [];
			$i = 0;
			foreach ($result as $messageObject) {
				if (!$messageObject instanceof iUmiHierarchyElement) {
					continue;
				}
				$i++;
				$message_element_id = $messageObject->getId();
				$line_arr = [];
				$line_arr['attribute:id'] = $message_element_id;
				$line_arr['attribute:name'] = $messageObject->getName();
				$line_arr['attribute:num'] = ($per_page * $curr_page) + $i + 1;
				$line_arr['attribute:author_id'] = $author_id = $messageObject->getValue('author_id');
				$line_arr['attribute:publish_time'] = $messageObject->getValue('publish_time');
				$line_arr['xlink:href'] = 'upage://' . $message_element_id;
				$line_arr['xlink:author-href'] = 'udata://users/viewAuthor/' . $author_id;
				$message = $messageObject->getValue('message');
				$line_arr['node:message'] = $this->module->formatMessage($message);

				$lines[] = forum::parseTemplate($template_line, $line_arr, $message_element_id);
				forum::pushEditable('forum', 'message', $message_element_id);
				$umiHierarchy->unloadElement($element_id);
			}

			$block_arr = [];
			$block_arr['attribute:id'] = $element_id;
			$block_arr['subnodes:lines'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			return forum::parseTemplate($template_block, $block_arr, $element_id);
		}

		/**
		 * Обрабатывает запрос страницы сообщения.
		 * Перенаправляет на страницу топика который должен содержать это сообщение.
		 * @throws publicException
		 * @throws selectorException
		 */
		public function message() {
			$cmsController = cmsController::getInstance();
			$element_id = $cmsController->getCurrentElementId();
			$url = $this->getMessageLink($element_id);
			$this->module->redirect($url);
		}

		/**
		 * Возвращает содержание последнего сообщения топика форума
		 * @param string|int $path адрес или идентификатор топика форума
		 * @param string $template имя шаблона (для tpl)
		 * @return string|mixed
		 * @throws publicException
		 */
		public function topic_last_message($path, $template = 'default') {
			if (!$template) {
				$template = 'default';
			}

			list($template_block) = forum::loadTemplates(
				'forum/' . $template,
				'topic_last_message'
			);

			$parentElementId = $this->module->analyzeRequiredPath($path);

			if ($parentElementId === false && $path != KEYWORD_GRAB_ALL) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $path));
			}

			$messageElementId = $this->module->getLastMessageId($parentElementId);
			$hierarchy = umiHierarchy::getInstance();
			$messageElement = $hierarchy->getElement($messageElementId);

			if (!$messageElement instanceof iUmiHierarchyElement) {
				return '';
			}

			$block_arr = [];
			$block_arr['attribute:id'] = $messageElementId;
			$block_arr['attribute:name'] = $messageElement->getName();
			$block_arr['attribute:link'] = $this->getMessageLink($messageElementId);
			$block_arr['attribute:author_id'] = $messageElement->getValue('author_id');
			$block_arr['xlink:href'] = 'upage://' . $messageElementId;
			$block_arr['node:message'] = $this->module->formatMessage($messageElement->getValue('message'));

			$publishTime = $messageElement->getValue('publish_time');

			if ($publishTime instanceof iUmiDate) {
				$publishTime = $publishTime->getFormattedDate('U');
				$parentElement = $messageElement->getParentId();

				if ($parentElement) {
					$parentElement = $hierarchy->getElement($parentElementId);
					$parentElement->setValue('last_post_time', $publishTime);
					$parentElement->commit();
				}
			}

			forum::pushEditable('forum', 'message', $messageElementId);
			return forum::parseTemplate($template_block, $block_arr, $messageElementId);
		}

		/**
		 * Возвращает содержание последнего сообщения конференции форума
		 * @param string|int $path адрес или идентификатор конференции форума
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed|string
		 * @throws publicException
		 */
		public function conf_last_message($path, $template = 'default') {
			if (!$template) {
				$template = 'default';
			}

			list($template_block) = forum::loadTemplates(
				'forum/' . $template,
				'conf_last_message'
			);

			$parentElementId = $this->module->analyzeRequiredPath($path);

			if ($parentElementId === false && $path != KEYWORD_GRAB_ALL) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $path));
			}

			$messageElementId = $this->module->getLastMessageId($parentElementId);
			$hierarchy = umiHierarchy::getInstance();
			$messageElement = $hierarchy->getElement($messageElementId);

			if (!$messageElement instanceof iUmiHierarchyElement) {
				return '';
			}

			$block_arr = [];
			$block_arr['attribute:id'] = $messageElementId;
			$block_arr['attribute:name'] = $messageElement->getName();
			$block_arr['attribute:link'] = $this->getMessageLink($messageElementId);
			$block_arr['attribute:author_id'] = $messageElement->getValue('author_id');
			$block_arr['xlink:href'] = 'upage://' . $messageElementId;
			$block_arr['node:message'] = $this->module->formatMessage($messageElement->getValue('message'));
			forum::pushEditable('forum', 'message', $messageElementId);

			return forum::parseTemplate($template_block, $block_arr, $messageElementId);
		}

		/**
		 * Возвращает данные для создания формы добавления топика с клиентской части.
		 * @param string|int $elementPath адрес или идентификатор родительской страницы будущего топика
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed|string
		 * @throws publicException
		 */
		public function topic_post($elementPath, $template = 'default') {
			$element_id = $this->module->analyzeRequiredPath($elementPath);
			$hierarchy = umiHierarchy::getInstance();
			$element = $hierarchy->getElement($elementPath);

			if (!$element instanceof iUmiHierarchyElement) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $elementPath));
			}

			if ($element->getValue('comments_disallow')) {
				return '';
			}

			if (!$template) {
				$template = 'default';
			}

			list($template_block_user, $template_block_guest, $template_smiles) = forum::loadTemplates(
				'forum/' . $template,
				'add_topic_user',
				'add_topic_guest',
				'smiles'
			);

			$umiRegistry = Service::Registry();
			$isAuthorized = Service::Auth()->isAuthorized();

			if (!$isAuthorized && !$umiRegistry->get('//modules/forum/allow_guest')) {
				return '';
			}

			$template = $isAuthorized ? $template_block_user : $template_block_guest;

			$block_arr = [];
			$block_arr['void:smiles'] = $template_smiles;
			$block_arr['id'] = $element_id;
			$block_arr['name'] = $element->getName();
			$block_arr['action'] = $this->module->pre_lang . '/forum/topic_post_do/' . $element->getId() . '/';

			return forum::parseTemplate($template, $block_arr, $element_id);
		}

		/**
		 * Возвращает данные для создания формы добавления сообщения с клиентской части.
		 * @param string|int|bool $elementPath адрес или идентификатор родительской страницы будущего сообщения
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed|string
		 * @throws publicException
		 */
		public function message_post($elementPath = false, $template = 'default') {
			$element_id = $this->module->analyzeRequiredPath($elementPath);
			$umiHierarchy = umiHierarchy::getInstance();
			$page = $umiHierarchy->getElement($element_id);

			if (!$page instanceof iUmiHierarchyElement) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $elementPath));
			}

			if ($page->getValue('comments_disallow')) {
				return '';
			}

			if (!$template) {
				$template = 'default';
			}

			list($template_block_user, $template_block_guest, $template_smiles) = forum::loadTemplates(
				'forum/' . $template,
				'add_message_user',
				'add_message_guest',
				'smiles'
			);

			$umiRegistry = Service::Registry();
			$isAuthorized = Service::Auth()->isAuthorized();

			if (!$isAuthorized && !$umiRegistry->get('//modules/forum/allow_guest')) {
				return '';
			}

			$template = $isAuthorized ? $template_block_user : $template_block_guest;

			$block_arr = [];
			$block_arr['void:smiles'] = $template_smiles;
			$block_arr['id'] = $element_id;
			$block_arr['name'] = $page->getName();
			$block_arr['action'] = $this->module->pre_lang . '/forum/message_post_do/' . $element_id . '/';

			return forum::parseTemplate($template, $block_arr, $element_id);
		}

		/**
		 * Возвращает значение счетчика топиков в конференции форума
		 * @param int|string $confElementId идентификатор конференции форума
		 * @return string
		 */
		public function getConfTopicsCount($confElementId) {
			$element = selector::get('page')->id($confElementId);

			if ($element instanceof iUmiHierarchyElement) {
				return $element->getValue('topics_count');
			}

			return '';
		}

		/**
		 * Возвращает значение счетчика сообщений в конференции форума
		 * @param int|string $confElementId идентификатор конференции форума
		 * @return string
		 */
		public function getConfMessagesCount($confElementId) {
			$element = selector::get('page')->id($confElementId);

			if ($element instanceof iUmiHierarchyElement) {
				return $element->getValue('messages_count');
			}

			return '';
		}

		/**
		 * Возвращает значение счетчика сообщений в топике форума
		 * @param int|string $topicElementId идентификатор топика форума
		 * @return string
		 */
		public function getTopicMessagesCount($topicElementId) {
			$element = selector::get('page')->id($topicElementId);

			if ($element instanceof iUmiHierarchyElement) {
				return $element->getValue('messages_count');
			}

			return '';
		}

		/**
		 * Создает топик форума и вызывает создания первого сообщения форума (см. message_post_do()).
		 * Применяется в формах на клиентской части.
		 * @return string|void
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 */
		public function topic_post_do() {
			$umiPermissions = permissionsCollection::getInstance();
			$isAuthorized = Service::Auth()->isAuthorized();
			$umiRegistry = Service::Registry();

			if (!$isAuthorized && !$umiRegistry->get('//modules/forum/allow_guest')) {
				return '%forum_not_allowed_post%';
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$parent_id = (int) getRequest('param0');
			$parent_element = $umiHierarchy->getElement($parent_id);

			$title = getRequest('title');
			$body = getRequest('body');

			$title = htmlspecialchars($title);
			$body = htmlspecialchars($body);

			$nickname = htmlspecialchars(getRequest('nickname'));
			if (!$nickname) {
				$nickname = htmlspecialchars(getRequest('login'));
			}
			$email = htmlspecialchars(getRequest('email'));

			$ip = $_SERVER['REMOTE_ADDR'];

			$publish_time = new umiDate(strtotime(date('d.m.Y H:i:00')));
			$module = $this->module;

			if (!umiCaptcha::checkCaptcha()) {
				$this->module->errorNewMessage('%errors_wrong_captcha%', false, false, 'captcha');
				$module->errorPanic();
			}

			if (trim($title) === '') {
				$module->errorNewMessage('%error_title_empty%', false);
				$module->errorPanic();
			}

			if (trim($body) === '') {
				$module->errorNewMessage('%error_message_empty%', false);
				$module->errorPanic();
			}

			$lang_id = Service::LanguageDetector()->detectId();
			$domain_id = Service::DomainDetector()->detectId();
			$tpl_id = $parent_element->getTplId();
			$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName('forum', 'topic')->getId();
			$object_type_id = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('forum', 'topic');
			/** @var users $usersModule */
			$usersModule = cmsController::getInstance()
				->getModule('users');

			if ($isAuthorized) {
				$author_id = $usersModule->createAuthorUser(Service::Auth()->getUserId());
			} else {
				$author_id = $usersModule->createAuthorGuest($nickname, $email, $ip);
			}

			$element_id = $umiHierarchy->addElement(
				$parent_id, $hierarchy_type_id, $title, $title, $object_type_id, $domain_id, $lang_id, $tpl_id
			);

			$umiPermissions->setDefaultPermissions($element_id);

			$element = $umiHierarchy->getElement($element_id, true);
			$element->setIsVisible(false);

			$bNeedModerate = !$umiPermissions->isSv() && $umiRegistry->get('//modules/forum/need_moder');

			if (!$bNeedModerate) {
				$bNeedModerate = !antiSpamHelper::checkContent($body . $title . $nickname . $email);
			}

			$element->setIsActive(!$bNeedModerate);
			$element->setAltName($title);
			$element->setName($title);
			$element->setValue('meta_descriptions', '');
			$element->setValue('meta_keywords', '');
			$element->setValue('h1', $title);
			$element->setValue('title', $title);
			$element->setValue('is_expanded', false);
			$element->setValue('show_submenu', false);
			$element->setValue('author_id', $author_id);
			$element->setValue('publish_time', $publish_time);

			$headers = umiImageFile::upload('pics', 'headers', USER_IMAGES_PATH . '/cms/headers/');
			if ($headers instanceof iUmiImageFile) {
				$element->setValue('header_pic', $headers);
			}

			$element->commit();

			$_REQUEST['param0'] = $element_id;

			if (!$bNeedModerate) {
				$this->module->recalcCounts($element);
			}

			$oEventPoint = new umiEventPoint('forum_topic_post_do');
			$oEventPoint->setParam('topic_id', $element_id);
			forum::setEventPoint($oEventPoint);

			$this->message_post_do();
		}

		/**
		 * Создает сообщение форума и перенаправляет на его адрес (см. getMessageLink()).
		 * Применяется в формах на клиентской части.
		 * @return string|void
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 * @throws publicException
		 */
		public function message_post_do() {
			$umiPermissions = permissionsCollection::getInstance();
			$isAuthorized = Service::Auth()->isAuthorized();
			$umiRegistry = Service::Registry();

			if (!$isAuthorized && !$umiRegistry->get('//modules/forum/allow_guest')) {
				return '%forum_not_allowed_post%';
			}

			$title = getRequest('title');
			$body = getRequest('body');

			$title = htmlspecialchars($title);
			$body = htmlspecialchars($body);

			$nickname = htmlspecialchars(getRequest('nickname'));
			$email = htmlspecialchars(getRequest('email'));

			$ip = getServer('REMOTE_ADDR');

			$publish_time = new umiDate(strtotime(date('d.m.Y H:i:00')));

			$umiHierarchy = umiHierarchy::getInstance();
			$parent_id = (int) getRequest('param0');
			$parent_element = $umiHierarchy->getElement($parent_id, true);

			if (trim($title) === '' && ($parent_element instanceof iUmiHierarchyElement)) {
				$title = 'Re: ' . $parent_element->getName();
			}

			$referer_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
			$module = $this->module;

			if (!umiCaptcha::checkCaptcha() || !$parent_element) {
				$this->module->errorNewMessage('%errors_wrong_captcha%', false, false, 'captcha');
				$module->errorPanic();
			}
			if (trim($body) === '') {
				$module->errorNewMessage('%error_message_empty%', false);
				$module->errorPanic();
			}

			$lang_id = Service::LanguageDetector()->detectId();
			$domain_id = Service::DomainDetector()->detectId();
			$tpl_id = $parent_element->getTplId();
			$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName('forum', 'message')->getId();
			$object_type_id = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeName('forum', 'message');

			/** @var users $usersModule */
			$usersModule = cmsController::getInstance()
				->getModule('users');

			if ($isAuthorized) {
				$author_id = $usersModule->createAuthorUser(Service::Auth()->getUserId());
			} else {
				$author_id = $usersModule->createAuthorGuest($nickname, $email, $ip);
			}

			$element_id = $umiHierarchy->addElement(
				$parent_id, $hierarchy_type_id, $title, $title, $object_type_id, $domain_id, $lang_id, $tpl_id
			);
			$umiPermissions->setDefaultPermissions($element_id);

			$element = $umiHierarchy->getElement($element_id, true);
			$element->setIsVisible(false);

			$bNeedModerate = !$umiPermissions->isSv() && $umiRegistry->get('//modules/forum/need_moder');

			if (!$bNeedModerate) {
				$bNeedModerate = !antiSpamHelper::checkContent($body . $title . $nickname . $email);
			}

			$element->setIsActive(!$bNeedModerate);
			$element->setAltName($title);
			$element->setName($title);
			$element->setValue('meta_descriptions', '');
			$element->setValue('meta_keywords', '');
			$element->setValue('h1', $title);
			$element->setValue('title', $title);
			$element->setValue('is_expanded', false);
			$element->setValue('show_submenu', false);
			$element->setValue('message', $body);
			$element->setValue('author_id', $author_id);
			$element->setValue('publish_time', $publish_time);

			$headers = umiImageFile::upload('pics', 'headers', USER_IMAGES_PATH . '/cms/headers/');
			if ($headers instanceof iUmiImageFile) {
				$element->setValue('header_pic', $headers);
			}

			$object_id = $element->getObjectId();
			/** @var data|DataForms $data_module */
			$data_module = cmsController::getInstance()->getModule('data');
			$data_module->saveEditedObject($object_id, true);
			$element->commit();

			if ($parent_id) {
				$parentElement = $umiHierarchy->getElement($element->getParentId());

				if ($parentElement instanceof iUmiHierarchyElement) {
					$parentElement->setValue('last_message', $element_id);
					$parentElement->setValue('last_post_time', strtotime(date('d.m.Y H:i:00')));
					$parentElement->commit();
				}

				$parentElement = $umiHierarchy->getElement($parentElement->getParentId());

				if ($parentElement instanceof iUmiHierarchyElement) {
					$parentElement->setValue('last_message', $element_id);
					$parentElement->commit();
				}
			}

			if (!$bNeedModerate) {
				$this->module->recalcCounts($element);
			}

			$oEventPoint = new umiEventPoint('forum_message_post_do');
			$oEventPoint->setMode('after');
			$oEventPoint->setParam('topic_id', $parent_id);
			$oEventPoint->setParam('message_id', $element_id);

			forum::setEventPoint($oEventPoint);

			$path = $bNeedModerate ? $referer_url : $this->getMessageLink($element_id);
			$module->redirect($path);
		}

		/**
		 * Возвращает адрес страницы сообщения форума,
		 * который представляет собой адрес страницы топика,
		 * которая должна содержать сообщение форума, с учетом пагинации.
		 * @param bool|int $element_id идентификатор сообщения
		 * @return string
		 * @throws publicException
		 * @throws selectorException
		 */
		public function getMessageLink($element_id = false) {
			$module = $this->module;
			$element_id = $module->analyzeRequiredPath($element_id);
			$per_page = $module->per_page;
			$curr_page = (int) getRequest('p');

			$umiHierarchy = umiHierarchy::getInstance();
			$element = $umiHierarchy->getElement($element_id, true);

			if (!$element) {
				throw new publicException(getLabel('error-page-does-not-exist', null, ''));
			}

			$parent_id = $element->getParentId();
			$parent_element = $umiHierarchy->getElement($parent_id);

			if (!$parent_element) {
				throw new publicException(getLabel('error-parent-does-not-exist', null, ''));
			}

			$publish_time = null;

			if ($element->getValue('publish_time')) {
				$publish_time = $element->getValue('publish_time')->getFormattedDate('U');
			}

			$messages = new selector('pages');
			$messages->types('object-type')->name('forum', 'message');
			$messages->where('hierarchy')->page($parent_id);

			if (is_numeric($messages->searchField('publish_time'))) {
				if ($publish_time !== null) {
					$messages->where('publish_time')->less($publish_time);
				}

				$messages->order('publish_time')->desc();
			}

			$messages->option('return')->value('count');
			$messages->limit($curr_page * $per_page, $per_page);
			$total = $messages->result();

			$p = floor(($total - 1) / $module->per_page);

			if ($p < 0) {
				$p = 0;
			}

			return $umiHierarchy->getPathById($parent_id) . "?p={$p}#" . $element_id . '&order_direction=desc';
		}
	}
