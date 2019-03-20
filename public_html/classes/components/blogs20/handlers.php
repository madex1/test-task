<?php

	use UmiCms\Service;

	/** Класс обработчиков событий */
	class BlogsHandlers {

		/** @var blogs20 $module */
		public $module;

		/**
		 * Обработчик события создания комментария с клиентской части.
		 * Отправляет уведомление о комментарии автора поста блога.
		 * @param iUmiEventPoint $eventPoint событие создания комментария
		 * @return bool
		 * @throws coreException
		 * @throws publicException
		 * @throws Exception
		 */
		public function onCommentAdd(iUmiEventPoint $eventPoint) {
			$umiHierarchy = umiHierarchy::getInstance();
			$umiObjects = umiObjectsCollection::getInstance();
			$umiHierarchyTypes = umiHierarchyTypesCollection::getInstance();

			if (!Service::Registry()->get('//modules/blogs20/notifications/on_comment_add')) {
				return false;
			}

			$templateParam = $eventPoint->getParam('template');
			$template = $templateParam ?: 'default';
			$commentId = $eventPoint->getParam('id');
			$comment = $umiHierarchy->getElement($commentId, true);
			$parentId = $comment->getParentId();
			$element = $umiHierarchy->getElement($parentId);
			$postHierarchyTypeId = $umiHierarchyTypes->getTypeByName('blogs20', 'post')->getId();
			$post = $element;

			if (!$post instanceof iUmiHierarchyElement) {
				return false;
			}

			while ($post->getTypeId() != $postHierarchyTypeId) {
				$post = $umiHierarchy->getElement($post->getParentId(), true);
			}

			if ($element->getTypeId() == $postHierarchyTypeId) {
				$parentOwner = $umiObjects->getObject($element->getObject()->getOwnerId());

				if (!$parentOwner instanceof iUmiObject) {
					return false;
				}

				$email = $parentOwner->getValue('e-mail');
				$nick = $parentOwner->getValue('login');
				$firstName = (string) $parentOwner->getValue('fname');
				$lastName = $parentOwner->getValue('lname');
				$fatherName = $parentOwner->getValue('father_name');
				$name = $firstName !== '' ? ($firstName . ' ' . $fatherName . ' ' . $lastName) : $nick;

				$subjectTemplateLabel = 'comment_for_post_subj';
				$contentTemplateLabel = 'comment_for_post_body';
				$notificationName = 'notification-blogs-post-comment';
				$subjectTemplateName = 'blogs-post-comment-subject';
				$contentTemplateName = 'blogs-post-comment-content';
			} else {
				$parentOwner = $umiObjects->getObject($element->getValue('author_id'));

				if ($parentOwner->getValue('is_registrated')) {
					$user = $umiObjects->getObject($parentOwner->getValue('user_id'));
					$email = $user->getValue('e-mail');
					$nick = $user->getValue('login');
					$firstName = (string) $user->getValue('fname');
					$lastName = $user->getValue('lname');
					$fatherName = $user->getValue('father_name');
					$name = $firstName !== '' ? ($firstName . ' ' . $fatherName . ' ' . $lastName) : $nick;
				} else {
					$email = $parentOwner->getValue('email');
					$name = $parentOwner->getValue('nickname');
				}

				$subjectTemplateLabel = 'comment_for_comment_subj';
				$contentTemplateLabel = 'comment_for_comment_body';
				$notificationName = 'notification-blogs-comment-comment';
				$subjectTemplateName = 'blogs-comment-comment-subject';
				$contentTemplateName = 'blogs-comment-comment-content';
			}

			$domain = Service::DomainDetector()->detectUrl();
			$link = $domain . $umiHierarchy->getPathById($post->getId()) . '#comment_' . $commentId;
			$commentAuthor = $umiObjects->getObject($comment->getValue('author_id'));

			$variables = [
				'link' => $link,
				'name' => $name,
			];
			$objectList = [$comment->getObject(), $parentOwner, $commentAuthor];

			$subject = null;
			$content = null;

			if ($this->module->isUsingUmiNotifications()) {
				$mailNotifications = Service::MailNotifications();
				$notification = $mailNotifications->getCurrentByName($notificationName);

				if ($notification instanceof MailNotification) {
					$subjectTemplate = $notification->getTemplateByName($subjectTemplateName);
					$contentTemplate = $notification->getTemplateByName($contentTemplateName);

					if ($subjectTemplate instanceof MailTemplate) {
						$subject = $subjectTemplate->parse($variables, $objectList);
					}

					if ($contentTemplate instanceof MailTemplate) {
						$content = $contentTemplate->parse($variables, $objectList);
					}
				}
			} else {
				try {
					list($subjectTemplate, $contentTemplate) = blogs20::loadTemplatesForMail(
						'blogs20/mail/' . $template,
						$subjectTemplateLabel,
						$contentTemplateLabel
					);
					$subject = blogs20::parseTemplateForMail($subjectTemplate, $variables);
					$content = blogs20::parseTemplateForMail($contentTemplate, $variables);
				} catch (Exception $e) {
					// nothing
				}
			}

			if ($subject === null || $content === null) {
				return false;
			}

			$mailSettings = $this->module->getMailSettings();
			$fromEmail = $mailSettings->getSenderEmail();
			$fromName = $mailSettings->getSenderName();

			$mail = new umiMail();
			$mail->addRecipient($email, $name);
			$mail->setFrom($fromEmail, $fromName);
			$mail->setSubject($subject);
			$mail->setContent($content);
			$mail->commit();
			$mail->send();

			return true;
		}

		/**
		 * Обработчик события создания поста с клиентской части.
		 * Запускает проверку поста на предмет наличия спама.
		 * @param iUmiEventPoint $event событие создания поста
		 */
		public function onPostAdded(iUmiEventPoint $event) {
			if ($event->getMode() == 'after') {
				$postId = $event->getParam('id');
				antiSpamHelper::checkForSpam($postId);
			}
		}

		/**
		 * Обработчик события создания комментария с клиентской части.
		 * Запускает проверку комментария на предмет наличия спама.
		 * @param iUmiEventPoint $event событие создания комментария
		 */
		public function onCommentAdded(iUmiEventPoint $event) {
			$commentId = $event->getParam('id');
			antiSpamHelper::checkForSpam($commentId);
		}

	}
