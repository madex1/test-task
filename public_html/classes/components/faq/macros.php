<?php

use UmiCms\Service;

/** Класс макросов, то есть методов, доступных в шаблоне */
	class FAQMacros {

		/** @var faq $module */
		public $module;

		/**
		 * Возвращает данные вопроса
		 * @param string $template имя шаблона (для tpl)
		 * @param bool|int|string $element_path идентификатор или адрес вопроса
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function question($template = 'default', $element_path = false) {
			list($template_block) = faq::loadTemplates(
				'faq/' . $template,
				'question'
			);

			$element_id = $this->module->analyzeRequiredPath($element_path);
			$element = umiHierarchy::getInstance()->getElement($element_id);

			$line_arr = [];

			if ($element instanceof iUmiHierarchyElement) {
				$line_arr['id'] = $element_id;
				$line_arr['text'] = $element->getName();
				$line_arr['alt_name'] = $element->getAltName();
				$line_arr['link'] = umiLinksHelper::getInstance()->getLink($element);
				$line_arr['question'] = nl2br($element->getValue('question'));
				$line_arr['answer'] = ($answer = $element->getValue('answer'))
					? nl2br($answer)
					: nl2br($element->getValue('content'));
			}

			faq::pushEditable('faq', 'question', $element_id);
			return faq::parseTemplate($template_block, $line_arr, $element_id);
		}

		/**
		 * Возвращает категории вопросов, дочерние заданной странице
		 * @param string $template имя шаблона (для tpl)
		 * @param bool|int|string $projectPath идентификатор или адрес родительской страницы
		 * @param bool|int $limit ограничение на количество выводимых категорий
		 * @param bool $ignorePaging игнорировать пагинацию
		 * @return mixed
		 * @throws selectorException
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function project($template = 'default', $projectPath = false, $limit = false, $ignorePaging = false) {
			$projectId = $this->module->analyzeRequiredPath($projectPath);
			$perPage = $limit ?: $this->module->per_page;
			$currentPage = (int) getRequest('p');

			if ($ignorePaging) {
				$currentPage = 0;
			}

			$categoryList = new selector('pages');
			$categoryList->types('object-type')->name('faq', 'category');
			$categoryList->where('hierarchy')->page($projectId);
			$categoryList->option('load-all-props')->value(true);
			$categoryList->limit($currentPage * $perPage, $perPage);

			$result = $categoryList->result();
			$total = $categoryList->length();

			list(
				$templateBlock,
				$templateBlockEmpty,
				$templateLine
				) = faq::loadTemplates(
				'faq/' . $template,
				'categories_block',
				'categories_block_empty',
				'categories_block_line'
			);

			if ($total == 0) {
				return faq::parseTemplate($templateBlockEmpty, []);
			}

			$templateVariables = [];
			$templateVariables['total'] = $total;
			$umiLinksHelper = umiLinksHelper::getInstance();
			$lines = [];

			foreach ($result as $category) {
				if (!$category instanceof iUmiHierarchyElement) {
					continue;
				}

				$categoryId = $category->getId();
				$itemVariables = [];
				$itemVariables['attribute:id'] = $categoryId;
				$itemVariables['attribute:name'] = $itemVariables['void:text'] = $category->getName();
				$itemVariables['void:alt_name'] = $category->getAltName();
				$itemVariables['attribute:link'] = $umiLinksHelper->getLinkByParts($category);
				$itemVariables['xlink:href'] = 'upage://' . $categoryId;
				$itemVariables['void:content'] = $category->getValue('content');

				faq::pushEditable('faq', 'category', $categoryId);
				$lines[] = faq::parseTemplate($templateLine, $itemVariables, $categoryId);
			}

			$templateVariables['subnodes:lines'] = $lines;
			$templateVariables['per_page'] = $perPage;
			$templateVariables['total'] = $total;

			return faq::parseTemplate($templateBlock, $templateVariables, $projectId);
		}

		/**
		 * Возвращает список вопросов, дочерних заданной странице
		 * @param string $template имя шаблона (для tpl)
		 * @param bool|int|string $categoryPath идентификатор или адрес родительской страницы
		 * @param bool|int $limit ограничение на количество выводимых вопросов
		 * @param bool $ignorePaging игнорировать пагинацию
		 * @param bool $order режим сортировки: true -> ASC, false -> DESC
		 * @param bool $showSpam выводить вопросы, отмеченные как спам
		 * @return mixed
		 * @throws selectorException
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function category(
			$template = 'default',
			$categoryPath = false,
			$limit = false,
			$ignorePaging = false,
			$order = true,
			$showSpam = false
		) {
			$template = $template ?: 'default';

			list($templateBlock, $templateBlockEmpty, $templateLine) = faq::loadTemplates(
				'faq/' . $template,
				'questions_block',
				'questions_block_empty',
				'questions_block_line'
			);

			$categoryId = $this->module->analyzeRequiredPath($categoryPath);
			$perPage = $limit ?: $this->module->per_page;
			$currentPage = (int) getRequest('p');

			if ($ignorePaging) {
				$currentPage = 0;
			}

			$questions = new selector('pages');
			$questions->types('object-type')->name('faq', 'question');
			$questions->where('hierarchy')->page($categoryId);

			if (!$showSpam) {
				$questions->where('is_spam')->notequals(1);
			}

			$questions->option('load-all-props')->value(true);

			if ($order) {
				$questions->order('ord')->asc();
			} else {
				$questions->order('ord')->desc();
			}

			$questions->limit($currentPage * $perPage, $perPage);
			$result = $questions->result();
			$total = $questions->length();

			if ($total == 0) {
				return $templateBlockEmpty;
			}

			$templateVariables = [];
			$templateVariables['total'] = $total;
			$templateVariables['per_page'] = $perPage;

			$lines = [];
			$umiLinksHelper = umiLinksHelper::getInstance();

			foreach ($result as $question) {
				if (!$question instanceof iUmiHierarchyElement) {
					continue;
				}

				$questionId = $question->getId();

				$questionVariables = [];
				$questionVariables['attribute:id'] = $questionId;
				$questionVariables['attribute:name'] = $questionVariables['void:text'] = $question->getName();
				$questionVariables['void:alt_name'] = $question->getAltName();
				$questionVariables['attribute:link'] = $umiLinksHelper->getLinkByParts($question);
				$questionVariables['xlink:href'] = 'upage://' . $questionId;
				$questionVariables['question'] = nl2br($question->getValue('question'));

				$answer = $question->getValue('answer') ?: $question->getValue('content');
				$questionVariables['answer'] = nl2br($answer);

				faq::pushEditable('faq', 'question', $questionId);
				$lines[] = faq::parseTemplate($templateLine, $questionVariables, $questionId);
			}

			$templateVariables['subnodes:items'] = $templateVariables['void:lines'] = $lines;
			return faq::parseTemplate($templateBlock, $templateVariables, $categoryId);
		}

		/**
		 * Возвращает список проектов
		 * @param string $template имя шаблона (для tpl)
		 * @param bool|int $limit ограничение на количество выводимых проектов
		 * @param bool $ignore_paging игнорировать пагинацию
		 * @return mixed
		 * @throws selectorException
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function projects($template = 'default', $limit = false, $ignore_paging = false) {
			$limit = (int) $limit;
			$per_page = $limit ?: $this->module->per_page;
			$curr_page = (int) getRequest('p');

			if ($ignore_paging) {
				$curr_page = 0;
			}

			$projects = new selector('pages');
			$projects->types('object-type')->name('faq', 'project');
			$projects->option('load-all-props')->value(true);
			$projects->limit($curr_page * $per_page, $per_page);
			$result = $projects->result();
			$total = $projects->length();

			list(
				$template_block,
				$template_block_empty,
				$template_line
				) = faq::loadTemplates(
				'faq/' . $template,
				'projects_block',
				'projects_block_empty',
				'projects_block_line'
			);

			if ($total == 0) {
				return faq::parseTemplate($template_block_empty, []);
			}

			$block_arr = [];
			$lines = [];
			$umiLinksHelper = umiLinksHelper::getInstance();

			foreach ($result as $project) {
				if (!$project instanceof iUmiHierarchyElement) {
					continue;
				}

				$element_id = $project->getId();
				$line_arr = [];
				$line_arr['attribute:id'] = $element_id;
				$line_arr['attribute:name'] = $line_arr['void:text'] = $project->getName();
				$line_arr['void:alt_name'] = $project->getAltName();
				$line_arr['attribute:link'] = $umiLinksHelper->getLinkByParts($project);
				$line_arr['xlink:href'] = 'upage://' . $element_id;

				faq::pushEditable('faq', 'project', $element_id);
				$lines[] = faq::parseTemplate($template_line, $line_arr, $element_id);
			}

			$block_arr['subnodes:lines'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;

			return faq::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Возвращает данные для создания формы добавления вопроса в заданную категорию
		 * @param string $template имя шаблона (для tpl)
		 * @param bool|int|string $categoryPath идентификатор или адрес страницы категории
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function addQuestionForm($template = 'default', $categoryPath = false) {
			list($templateAddUser, $templateAddGuest) = faq::loadTemplates(
				'faq/' . $template,
				'question_add_user',
				'question_add_guest'
			);

			$categoryId = $this->module->analyzeRequiredPath($categoryPath);
			$templateAdd = $templateAddGuest;

			if (Service::Auth()->isAuthorized()) {
				$templateAdd = $templateAddUser;
			}

			$blockArr['action'] = $this->module->pre_lang . '/faq/post_question/' . $categoryId . '/';
			return faq::parseTemplate($templateAdd, $blockArr, $categoryId);
		}

		/**
		 * Обрабатывает ошибку.
		 * Совершает редирект на referrer, подставляя
		 * в $_GET параметр идентификатор ошибки.
		 * @param string $errorMessage текст ошибки
		 * @param bool $interrupt прерывать выполнение скрипта
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 * @throws ErrorException
		 */
		public function reportError($errorMessage, $interrupt = true) {
			$this->module->errorNewMessage($errorMessage, (bool) $interrupt);
		}

		/**
		 * Завершает создание вопроса.
		 * Совершает редирект на метод, который создает вопрос.
		 * @param int $element_id идентификатор созданного вопроса.
		 * @throws coreException
		 */
		public function finishPosting($element_id) {
			$this->module->redirect($this->module->pre_lang . '/faq/post_question/?posted=' . $element_id);
		}

		/**
		 * Создает вопрос и отправляет администратору и клиенту
		 * почтовое уведомление.
		 * @return bool|null
		 * @return bool|mixed|null
		 * @throws ErrorException
		 * @throws baseException
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 * @throws publicException
		 * @throws selectorException
		 * @throws Exception
		 */
		public function post_question() {
			$questionId = getRequest('posted');
			$session = Service::Session();

			if ($questionId) {
				$tickets = (array) $session->get('tickets');
				$userMailContent = getArrayKey($tickets, $questionId);
				return $userMailContent;
			}

			if ($questionId) {
				$tickets = (array) $session->get('tickets');
				$userMailContent = getArrayKey($tickets, $questionId);
				return $userMailContent;
			}

			$referrer = getServer('HTTP_REFERER');
			$this->module->errorRegisterFailPage($referrer);
			$parentId = (int) getRequest('param0');

			$email = htmlspecialchars(getRequest('email'));
			$nick = htmlspecialchars(getRequest('nick'));
			$title = htmlspecialchars(getRequest('title'));
			$question = htmlspecialchars(getRequest('question'));
			$ip = getServer('REMOTE_ADDR');

			if ($title === '') {
				$this->reportError('%error_faq_required_title%');
			}

			if ($question === '') {
				$this->reportError('%error_faq_required_question%');
			}

			$umiPermissions = permissionsCollection::getInstance();
			$umiObjects = umiObjectsCollection::getInstance();
			$auth = Service::Auth();

			if ($email === '') {
				$userId = $auth->getUserId();
				$user = $umiObjects->getObject($userId);

				if ($user) {
					$email = $user->getValue('e-mail');
				}
			}

			$publishTime = time();

			if (!umiCaptcha::checkCaptcha()) {
				$this->module->errorNewMessage('%errors_wrong_captcha%', true, false, 'captcha');
			}

			$eventPoint = new umiEventPoint('faq_post_question');
			$eventPoint->setMode('before');
			$eventPoint->setParam('parent_element_id', $parentId);
			$eventPoint->setParam('test_captcha', umiCaptcha::checkCaptcha());
			faq::setEventPoint($eventPoint);

			$cmsController = cmsController::getInstance();
			/** @var users $users */
			$users = $cmsController->getModule('users');
			$isActive = false;

			if (Service::Auth()->isAuthorized()) {
				$userId = $auth->getUserId();
				$authorId = $users->createAuthorUser($userId);
				$isActive = $umiPermissions->isSv($userId);
			} else {
				$authorId = $users->createAuthorGuest($nick, $email, $ip);
			}

			$author = $umiObjects->getObject($authorId);

			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$umiHierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$umiHierarchy = umiHierarchy::getInstance();

			$objectTypeId = $umiObjectTypes->getTypeIdByHierarchyTypeName('faq', 'question');
			$hierarchyTypeId = $umiHierarchyTypes->getTypeByName('faq', 'question')->getId();

			$parentElement = $umiHierarchy->getElement($parentId);
			$tplId = $parentElement->getTplId();
			$domainId = $parentElement->getDomainId();
			$langId = $parentElement->getLangId();

			$elementId = $umiHierarchy->addElement(
				$parentId,
				$hierarchyTypeId,
				$title,
				$title,
				$objectTypeId,
				$domainId,
				$langId,
				$tplId
			);

			$umiPermissions->setDefaultPermissions($elementId);

			$element = $umiHierarchy->getElement($elementId);
			$element->setIsActive($isActive);
			$element->setIsVisible(false);
			$element->setValue('question', $question);
			$element->setValue('publish_time', $publishTime);
			$element->setName($title);
			$element->setValue('h1', $title);
			$element->setValue('author_id', $authorId);
			$element->commit();

			$mailSettings = $this->module->getMailSettings();
			$from = $mailSettings->getSenderName();
			$fromEmail = $mailSettings->getSenderEmail();
			$adminEmail = $mailSettings->getAdminEmail();
			$domain = Service::DomainDetector()->detect();

			$variablesAdmin = [
				'domain' => $domain->getCurrentHostName(),
				'question' => $question,
				'question_link' => $domain->getCurrentUrl() . $this->module->pre_lang .
					'/admin/faq/edit/' . $elementId . '/'
			];
			$objectList = [$author, $element->getObject()];

			$subjectAdmin = null;
			$contentAdmin = null;

			$mailNotifications = Service::MailNotifications();

			if ($this->module->isUsingUmiNotifications()) {
				$notification = $mailNotifications->getCurrentByName('notification-faq-confirm-admin');

				if ($notification instanceof MailNotification) {
					$subjectTemplateAdmin = $notification->getTemplateByName('faq-confirm-admin-subject');
					$contentTemplateAdmin = $notification->getTemplateByName('faq-confirm-admin-content');

					if ($subjectTemplateAdmin instanceof MailTemplate) {
						$subjectAdmin = $subjectTemplateAdmin->parse($variablesAdmin, $objectList);
					}

					if ($contentTemplateAdmin instanceof MailTemplate) {
						$contentAdmin = $contentTemplateAdmin->parse($variablesAdmin, $objectList);
					}
				}
			} else {
				try {
					list($subjectTemplateAdmin, $contentTemplateAdmin) = faq::loadTemplatesForMail(
						'faq/default',
						'confirm_mail_subj_admin',
						'confirm_mail_admin'
					);
					$subjectAdmin = faq::parseTemplateForMail($subjectTemplateAdmin, $variablesAdmin);
					$contentAdmin = faq::parseTemplateForMail($contentTemplateAdmin, $variablesAdmin);
				} catch (Exception $e) {
					// nothing
				}
			}

			if ($subjectAdmin && $contentAdmin) {
				$mailAdmin = new umiMail();
				$mailAdmin->addRecipient($adminEmail);
				$mailAdmin->setFrom($email, $nick);
				$mailAdmin->setSubject($subjectAdmin);
				$mailAdmin->setContent($contentAdmin);
				$mailAdmin->commit();
				$mailAdmin->send();
			}

			if (!Service::Registry()->get('//modules/faq/disable_new_question_notification')) {
				$variablesUser = [
					'domain' => $domain,
					'question' => $question,
					'ticket' => $elementId
				];

				$subjectUser = null;
				$contentUser = null;

				if ($this->module->isUsingUmiNotifications()) {
					$notification = $mailNotifications->getCurrentByName('notification-faq-confirm-user');

					if ($notification instanceof MailNotification) {
						$subjectTemplateUser = $notification->getTemplateByName('faq-confirm-user-subject');
						$contentTemplateUser = $notification->getTemplateByName('faq-confirm-user-content');

						if ($subjectTemplateUser instanceof MailTemplate) {
							$subjectUser = $subjectTemplateUser->parse($variablesUser, $objectList);
						}

						if ($contentTemplateUser instanceof MailTemplate) {
							$contentUser = $contentTemplateUser->parse($variablesUser, $objectList);
						}
					}
				} else {
					try {
						list($subjectTemplateUser, $contentTemplateUser) = faq::loadTemplatesForMail(
							'faq/default',
							'confirm_mail_subj_user',
							'confirm_mail_user'
						);
						$subjectUser = faq::parseTemplateForMail($subjectTemplateUser, $variablesUser);
						$contentUser = faq::parseTemplateForMail($contentTemplateUser, $variablesUser);
					} catch (Exception $e) {
						// nothing
					}
				}

				if ($subjectUser && $contentUser) {
					$mailUser = new umiMail();
					$mailUser->addRecipient($email);
					$mailUser->setFrom($fromEmail, $from);
					$mailUser->setSubject($subjectUser);
					$mailUser->setContent($contentUser);
					$mailUser->commit();
					$mailUser->send();

					$tickets = $session->get('tickets');
					$tickets = is_array($tickets) ? $tickets : [];
					$tickets[$elementId] = $contentUser;
					$session->set('tickets', $tickets);
				}
			}

			$eventPoint = new umiEventPoint('faq_post_question');
			$eventPoint->setMode('after');
			$eventPoint->setParam('element_id', $elementId);
			faq::setEventPoint($eventPoint);
			$this->finishPosting($elementId);
		}
	}
