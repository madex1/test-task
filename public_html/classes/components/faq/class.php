<?php

	use UmiCms\Service;

	/**
	 * Базовый класс модуля "FAQ".
	 *
	 * Модуль управляет следующими сущностями:
	 *
	 * 1) Проекты;
	 * 2) Категории;
	 * 3) Вопросы;
	 *
	 * Модуль позволяет создавать вопросы с клиентской части.
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_faq/
	 */
	class faq extends def_module {

		/**
		 * Конструктор
		 * @throws coreException
		 */
		public function __construct() {
			parent::__construct();
			$this->per_page = (int) Service::Registry()->get('//modules/faq/per_page');

			if (Service::Request()->isAdmin()) {
				$this->includeAdminClasses();
			}

			$this->includeCommonClasses();
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('FaqAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('FAQCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('FAQMacros');

			$this->loadSiteExtension();

			$this->__loadLib('handlers.php');
			$this->__implement('FAQHandlers');

			$this->__loadLib('customMacros.php');
			$this->__implement('FAQCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Уведомляет автора об изменении вопроса
		 * @param iUmiHierarchyElement $page страница вопроса
		 * @return bool
		 * @throws Exception
		 */
		public function confirmUserAnswer($page) {
			$umiHierarchy = umiHierarchy::getInstance();
			$umiObjects = umiObjectsCollection::getInstance();

			$isConfirmationNeeded = (bool) Service::Registry()->get('//modules/faq/confirm_user_answer');

			if (!$isConfirmationNeeded) {
				return true;
			}

			if (!$page instanceof iUmiHierarchyElement || !$page->getIsActive()) {
				return false;
			}

			$authorId = $page->getValue('author_id');
			$author = $umiObjects->getObject($authorId);

			if (!$author instanceof iUmiObject) {
				return false;
			}

			$authorUser = null;

			if ($author->getValue('is_registrated')) {
				$userId = $author->getValue('user_id');
				$authorUser = $umiObjects->getObject($userId);
			}

			if ($authorUser instanceof iUmiObject) {
				$authorName = $authorUser->getValue('lname') . ' ' . $authorUser->getValue('fname');
				$authorEmail = $authorUser->getValue('e-mail');
			} else {
				$authorName = $author->getValue('nickname');
				$authorEmail = $author->getValue('email');
			}

			if (!umiMail::checkEmail($authorEmail)) {
				return false;
			}

			$pageId = $page->getId();
			$previousForcingStatus = $umiHierarchy->forceAbsolutePath();
			$questionLink = $umiHierarchy->getPathById($pageId);
			$umiHierarchy->forceAbsolutePath($previousForcingStatus);

			$variables = [
				'domain' => getServer('HTTP_HOST'),
				'element_id' => $pageId,
				'question_link' => $questionLink,
				'ticket' => $pageId,
				'author_id' => $page->getValue('author_id'),
			];
			$objectList = [$author, $page->getObject()];

			$subject = null;
			$content = null;

			if ($this->isUsingUmiNotifications()) {
				$mailNotifications = Service::MailNotifications();
				$notification = $mailNotifications->getCurrentByName('notification-faq-answer');

				if ($notification instanceof MailNotification) {
					$subjectTemplate = $notification->getTemplateByName('faq-answer-subject');
					$contentTemplate = $notification->getTemplateByName('faq-answer-content');

					if ($subjectTemplate instanceof MailTemplate) {
						$subject = $subjectTemplate->parse(
							['header' => $variables['header']],
							$objectList
						);
					}

					if ($contentTemplate instanceof MailTemplate) {
						$content = $contentTemplate->parse($variables, $objectList);
					}
				}
			} else {
				try {
					list($subjectTemplate, $contentTemplate) = self::loadTemplatesForMail(
						'faq/default',
						'answer_mail_subj',
						'answer_mail'
					);
					$subject = self::parseTemplateForMail($subjectTemplate, $variables, $pageId);
					$content = self::parseTemplateForMail($contentTemplate, $variables, $pageId);
				} catch (Exception $e) {
					// nothing
				}
			}

			if ($subject === null || $content === null) {
				return false;
			}

			$mailSettings = $this->getMailSettings();
			$fromName = $mailSettings->getSenderName();
			$fromEmail = $mailSettings->getSenderEmail();

			$mail = new umiMail();
			$mail->addRecipient($authorEmail, $authorName);
			$mail->setFrom($fromEmail, $fromName);
			$mail->setSubject($subject);
			$mail->setContent($content);
			$mail->commit();

			return true;
		}

		/**
		 * Возвращает ссылки на страницу редактирование сущности и
		 * страницу добавления дочерней сущности
		 * @param int|bool $element_id идентификатор сущности
		 * @param string|bool $element_type тип сущности
		 * @return array|bool
		 */
		public function getEditLink($element_id, $element_type) {
			$prefix = $this->pre_lang;

			switch ($element_type) {
				case 'project': {
					$link_add = $prefix . "/admin/faq/add/{$element_id}/category/";
					$link_edit = $prefix . "/admin/faq/edit/{$element_id}/";
					return [$link_add, $link_edit];
				}
				case 'category': {
					$link_add = $prefix . "/admin/faq/add/{$element_id}/question/";
					$link_edit = $prefix . "/admin/faq/edit/{$element_id}/";
					return [$link_add, $link_edit];
				}
				case 'question': {
					$link_edit = $prefix . "/admin/faq/edit/{$element_id}/";
					return [false, $link_edit];
				}
				default: {
					return false;
				}
			}
		}

		/** @inheritdoc */
		public function getMailObjectTypesGuidList() {
			return ['users-author', 'faq-question'];
		}

		/** @deprecated */
		public function getVariableNamesForMailTemplates() {
			return [];
		}
	}

