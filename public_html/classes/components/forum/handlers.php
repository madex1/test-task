<?php

	use UmiCms\Service;

	/** Класс, содержащий обработчики событий */
	class ForumHandlers {

		/** @var forum $module */
		public $module;

		/**
		 * Обработчик события изменения активности страницы.
		 * Если страница топик или сообщение форума - запустит
		 * актуализации счетчиков конференции форума.
		 * @param iUmiEventPoint $oEventPoint событие изменения активности страницы
		 * @return bool
		 * @throws selectorException
		 */
		public function onElementActivity(iUmiEventPoint $oEventPoint) {
			if ($oEventPoint->getMode() !== 'after') {
				return false;
			}

			$o_element = $oEventPoint->getRef('element');

			if (!$o_element instanceof iUmiHierarchyElement) {
				return false;
			}

			if ($this->module->isTopicOrMessage($o_element)) {
				$this->module->recalcCounts($o_element);
				return true;
			}

			return false;
		}

		/**
		 * Обработчик события удаления страницы.
		 * Если страница топик или сообщение форума - запустит
		 * актуализации счетчиков конференции форума.
		 * @param iUmiEventPoint $oEventPoint событие удаления страницы
		 * @return bool
		 * @throws selectorException
		 */
		public function onElementRemove(iUmiEventPoint $oEventPoint) {
			if ($oEventPoint->getMode() !== 'after') {
				return false;
			}

			$o_element = $oEventPoint->getRef('element');

			if (!$o_element instanceof iUmiHierarchyElement) {
				return false;
			}

			if ($this->module->isTopicOrMessage($o_element)) {
				$this->module->recalcCounts($o_element);
				return true;
			}

			return false;
		}

		/**
		 * Обработчик события добавления страницы.
		 * Если страница топик или сообщение форума - запустит
		 * актуализации счетчиков конференции форума.
		 * @param iUmiEventPoint $oEventPoint событие добавления страницы
		 * @return bool
		 * @throws selectorException
		 */
		public function onElementAppend(iUmiEventPoint $oEventPoint) {
			if ($oEventPoint->getMode() !== 'after') {
				return false;
			}

			$o_element = $oEventPoint->getRef('element');

			if (!$o_element instanceof iUmiHierarchyElement) {
				return false;
			}

			if ($this->module->isTopicOrMessage($o_element)) {
				$this->module->recalcCounts($o_element);

				$publish_time = new umiDate(strtotime(date('d.m.Y H:i:00')));
				$o_element->setValue('publish_time', $publish_time);
				$o_element->commit();
				return true;
			}

			return false;
		}

		/**
		 * Обработчик события добавления сообщения форума с клиентской части.
		 * Запускает проверку сообщения на предмет содержания спама.
		 * @param iUmiEventPoint $event событие добавления сообщения
		 */
		public function onMessagePost(iUmiEventPoint $event) {
			$messageId = $event->getParam('message_id');
			antiSpamHelper::checkForSpam($messageId);
		}

		/**
		 * Обработчик события добавления сообщения форума с клиентской части.
		 * Отправляет пользователям, подписанным на топик, почтовое уведомление
		 * о новом сообщении.
		 * @param iUmiEventPoint $oEvent событие добавления сообщения
		 * @return bool
		 * @throws selectorException
		 * @throws coreException
		 * @throws publicException
		 * @throws Exception
		 */
		public function onDispatchChanges(iUmiEventPoint $oEvent) {
			$umiHierarchy = umiHierarchy::getInstance();
			$mailNotifications = Service::MailNotifications();

			$topicId = $oEvent->getParam('topic_id');
			$messageId = $oEvent->getParam('message_id');
			$message = $umiHierarchy->getElement($messageId);

			$users = new selector('objects');
			$users->types('object-type')->name('users', 'user');
			$users->where('subscribed_pages')->equals($topicId);

			if (!$users->length()) {
				return false;
			}

			$mailSettings = $this->module->getMailSettings();
			$fromEmail = $mailSettings->getSenderEmail();
			$fromName = $mailSettings->getSenderName();

			$baseMail = new umiMail();
			$baseMail->setFrom($fromEmail, $fromName);

			/** @var iUmiObject $user */
			foreach ($users as $user) {
				$userEmail = $user->getValue('e-mail');

				if (!umiMail::checkEmail($userEmail)) {
					continue;
				}

				$variables = [
					'h1' => $message->getValue('h1'),
					'message' => $message->getValue('message'),
				];
				$umiHierarchy->forceAbsolutePath();
				$variables['unsubscribe_link'] = $umiHierarchy->getPathById($topicId) .
					'?unsubscribe=' . base64_encode($user->getId());

				$author = umiObjectsCollection::getInstance()
					->getObject($message->getValue('author_id'));
				$objectList = [$message->getObject(), $author];

				$subject = null;
				$content = null;

				if ($this->module->isUsingUmiNotifications()) {
					$notification = $mailNotifications->getCurrentByName('notification-forum-new-message');

					if ($notification instanceof MailNotification) {
						$subjectTemplate = $notification->getTemplateByName('forum-new-message-subject');
						$contentTemplate = $notification->getTemplateByName('forum-new-message-content');

						if ($subjectTemplate instanceof MailTemplate) {
							$subject = $subjectTemplate->parse([], $objectList);
						}

						if ($contentTemplate instanceof MailTemplate) {
							$content = $contentTemplate->parse($variables, $objectList);
						}
					}
				} else {
					try {
						list($subjectTemplate, $contentTemplate) = forum::loadTemplatesForMail(
							'forum/mails/default',
							'mail_subject',
							'mail_message'
						);
						$subject = forum::parseTemplateForMail($subjectTemplate, [], $messageId);
						$content = forum::parseTemplateForMail($contentTemplate, $variables, $messageId);
					} catch (Exception $e) {
						// nothing
					}
				}

				if ($subject === null || $content === null) {
					continue;
				}

				$umiHierarchy->forceAbsolutePath(false);

				$userName =
					$user->getValue('lname') . ' ' .
					$user->getValue('fname') . ' ' .
					$user->getValue('father_name');

				$mail = clone $baseMail;
				$mail->setSubject($subject);
				$mail->setContent($content);
				$mail->addRecipient($userEmail, $userName);
				$mail->commit();
				$mail->send();
			}

			return true;
		}

		/**
		 * Обработчик события добавления топика форума с клиентской части.
		 * Включает топик форума в выпуск рассылки модуля "Рассылки".
		 * @param iUmiEventPoint $oEvent событие добавления топика форума.
		 * @return bool
		 * @throws coreException
		 */
		public function onAddTopicToDispatch(iUmiEventPoint $oEvent) {
			$iDispatchId = Service::Registry()->get('//modules/forum/dispatch_id');

			if (!$iDispatchId) {
				return false;
			}

			/** @var dispatches $dispatches_module */
			$dispatches_module = cmsController::getInstance()->getModule('dispatches');

			if (!$dispatches_module instanceof def_module) {
				return false;
			}

			$iTopicId = (int) $oEvent->getParam('topic_id');
			$oTopicElement = umiHierarchy::getInstance()->getElement($iTopicId);

			if (!$oTopicElement instanceof iUmiHierarchyElement) {
				return false;
			}

			$sTitle = (string) getRequest('title');
			$sMessage = (string) getRequest('body');

			$umiObjects = umiObjectsCollection::getInstance();
			$iHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName('dispatches', 'message')->getId();
			$iMsgTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByHierarchyTypeId($iHierarchyTypeId);
			$iMsgObjId = $umiObjects->addObject($sTitle, $iMsgTypeId);

			$oMsgObj = $umiObjects->getObject($iMsgObjId);

			if (!$oMsgObj instanceof iUmiObject) {
				return false;
			}

			$iReleaseId = $dispatches_module->getNewReleaseInstanceId($iDispatchId);

			$oMsgObj->setValue('release_reference', $iReleaseId);
			$oMsgObj->setValue('header', $sTitle);
			$oMsgObj->setValue('body', $sMessage);
			$oMsgObj->commit();

			return true;
		}
	}
