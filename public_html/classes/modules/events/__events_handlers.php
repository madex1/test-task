<?php

	use UmiCms\Service;

	abstract class __eventsHandlersEvents {

		public function onUsersLoginSuccessfull(iUmiEventPoint $event) {
			$userId = $event->getParam('user_id');
			if (!permissionsCollection::getInstance()->isAdmin($userId)) {
				return;
			}

			$regedit = regedit::getInstance();

			$lastConnect = $regedit->getVal('//umiMessages/lastConnectTime');
			if ($lastConnect && $lastConnect >= time() - 86400) {
				return;
			}

			$lastMessageId = $regedit->getVal('//umiMessages/lastMessageId');
			if (!$lastMessageId) {
				$lastMessageId = 0;
			}

			$info = array();
			$info['keycode'] = Service::RegistrySettings()->getLicense();
			$info['last-message-id'] = $lastMessageId;

			$package = base64_encode(serialize($info));

			$url = 'http://messages.umi-cms.ru/udata/custom/getUmiMessages/'.$package.'/';
			$result = umiRemoteFileGetter::get($url, false, false, false, false, false, 3);

			if ($result) {
				$old = libxml_use_internal_errors(true);
				$xml = simplexml_load_string($result);
				if ( $xml && is_array($messages = $xml->xpath("//message"))) {
					foreach($messages as $message) {
						$lastId = (string) $message->attributes()->id;
						if ($lastMessageId < $lastId) {
							$lastMessageId = $lastId;
						}
						$this->registerEvent('users-adv-message', array((string) $message));
					}
					$regedit->setVal('//umiMessages/lastConnectTime', time());
					$regedit->setVal('//umiMessages/lastMessageId', $lastMessageId);
				}
				libxml_use_internal_errors($old);
			}
		}

		public function onPageView(iUmiEventPoint $event) {
			if ($event->getMode() == 'before') {
				if (!$event->getRef('element') instanceof umiHierarchyElement) return;
				$elementId = $event->getRef('element')->getId();
				$user = $this->getUser();

				$pool = ConnectionPool::getInstance();
				$connection = $pool->getConnection();
				umiEventFeed::setConnection($connection);

				$eventId = umiEventFeed::findEventIdByElementId($elementId);
				if ($eventId) umiEventFeed::markReadEvent($eventId, $user->getId());

			}
		}

		public function onPageHierarchyDelete(iUmiEventPoint $event) {
			if ($event->getMode() == 'after') {

				$elementId = $event->getParam('element_id');

				$pool = ConnectionPool::getInstance();
				$connection = $pool->getConnection();
				umiEventFeed::setConnection($connection);

				$eventId = umiEventFeed::findEventIdByElementId($elementId);
				if ($eventId) umiEventFeed::deleteEvent($eventId);

			}
		}

		public function onPageSystemDelete(iUmiEventPoint $event) {
			if ($event->getMode() == 'before') {

				if (!$event->getRef('element') instanceof umiHierarchyElement) return;
				$elementId = $event->getRef('element')->getId();

				$pool = ConnectionPool::getInstance();
				$connection = $pool->getConnection();
				umiEventFeed::setConnection($connection);

				$eventId = umiEventFeed::findEventIdByElementId($elementId);
				if ($eventId) umiEventFeed::deleteEvent($eventId);

			}
		}

		public function onObjectDelete(iUmiEventPoint $event) {
			if ($event->getMode() == 'after') {

				$objectId = $event->getParam('object_id');

				$pool = ConnectionPool::getInstance();
				$connection = $pool->getConnection();
				umiEventFeed::setConnection($connection);

				$eventId = umiEventFeed::findEventIdByObjectId($objectId);
				if ($eventId) umiEventFeed::deleteEvent($eventId);

			}
		}

		public function onObjectView(iUmiEventPoint $event) {
			if ($event->getMode() == 'before') {
				$objectId = $event->getRef('object')->getId();
				$user = $this->getUser();

				$pool = ConnectionPool::getInstance();
				$connection = $pool->getConnection();
				umiEventFeed::setConnection($connection);

				$eventId = umiEventFeed::findEventIdByObjectId($objectId);
				if ($eventId) umiEventFeed::markReadEvent($eventId, $user->getId());

			}
		}

		public function onBlogsPostAdded(iUmiEventPoint $event) {
			if ($event->getMode() == 'after') {
				$postId = $event->getParam('id');

				$post = umiHierarchy::getInstance()->getElement($postId, true, true);
				$module = cmsController::getInstance()->getModule('blogs20');
				$links = $module->getEditLink($postId, 'post');
				if(isset($links[1])) {
					$this->registerEvent('blogs20-post-add', array($links[1], $post->getName()), $postId);
				}
			}
		}

		public function onBlogsCommentAdded(iUmiEventPoint $event) {

			if ($event->getMode() == 'after') {
				$commentId = $event->getParam('id');
				$comment = umiHierarchy::getInstance()->getElement($commentId, true, true);
				$module = cmsController::getInstance()->getModule('blogs20');
				$links = $module->getEditLink($commentId, 'comment');
				if(isset($links[1])) {
					$this->registerEvent('blogs20-comment-add', array($links[1], $comment->getName()), $commentId);
				}
			}
		}

		public function onCommentsCommentPost(iUmiEventPoint $event) {
			if ($event->getMode() == 'after') {
				$commentId = $event->getParam('message_id');

				$element = umiHierarchy::getInstance()->getElement($event->getParam("topic_id"));
				if ($element) {
					$moduleName = $element->getModule();
					$methodName = $element->getMethod();

					$module = cmsController::getInstance()->getModule($moduleName);
					$comments = cmsController::getInstance()->getModule('comments');

					$pageLinks = $module->getEditLink($event->getParam("topic_id"), $methodName);
					$commentLinks = $comments->getEditLink($commentId, 'comment');

					if (isset($pageLinks[1]) && isset($commentLinks[1])) {
						$this->registerEvent('comments-comment-add', array($commentLinks[1], $pageLinks[1], $element->getName()), $commentId);
					}
				}
			}
		}

		public function onEmarketOrderAdded(iUmiEventPoint $event) {
			if ($event->getMode() == "after" && $event->getParam("old-status-id") != $event->getParam("new-status-id")) {

				 if ($event->getParam("new-status-id") == order::getStatusByCode('waiting') && $event->getParam("old-status-id") != order::getStatusByCode('editing')) {
					$module = cmsController::getInstance()->getModule('emarket');
					$order = $event->getRef("order");
					$link = $module->getObjectEditLink($order->getId(), 'order');
					$this->registerEvent('emarket-order-add', array($link, $order->getName()), null, $order->getId());
				}
			}
		}

		public function onFaqQuestionPost(iUmiEventPoint $event) {
			if ($event->getMode() == 'after') {
				$questionId = $event->getParam('element_id');

				$question = umiHierarchy::getInstance()->getElement($questionId, true, true);
				$module = cmsController::getInstance()->getModule('faq');
				$links = $module->getEditLink($questionId, 'question');
				if (isset($links[1])) {
					$this->registerEvent('faq-question-add', array($links[1], $question->getName()), $questionId);
				}
			}
		}

		public function onForumMessagePost(iUmiEventPoint $event) {

			if ($event->getMode() == 'after') {
				$messageId = $event->getParam("message_id");
				$message = umiHierarchy::getInstance()->getElement($messageId, true, true);
				$module = cmsController::getInstance()->getModule('forum');
				$links = $module->getEditLink($messageId, 'message');
				if (isset($links[1])) {
					$this->registerEvent('forum-message-add', array($links[1], $message->getName()), $messageId);
				}
			}
		}

		public function onUsersRegistered(iUmiEventPoint $event) {
			if ($event->getMode() == "after") {
				$module = cmsController::getInstance()->getModule('users');
				$link = $module->getObjectEditLink($event->getParam('user_id'));
				$this->registerEvent('users-user-register', array($link, $event->getParam('login')), null, $event->getParam('user_id'));
			}
		}

		public function onVotePollPost(iUmiEventPoint $event) {
			if ($event->getMode() == "after") {
				$module = cmsController::getInstance()->getModule('vote');
				$poll = $event->getParam("poll");
				$answer = $event->getParam("answer");
				$elementIds = umiHierarchy::getInstance()->getObjectInstances($poll->getId());
				$elementId = array_shift($elementIds);
				$links = $module->getEditLink($elementId, 'poll');
				if (isset($links[1])) {
					$this->registerEvent('vote-response-add', array($answer->getName(), $links[1], $poll->getName()), $elementId);
				}
			}
		}

		public function onWebformsPost(iUmiEventPoint $event) {

			if ($event->getMode() == "after") {
				$formId = $event->getParam("form_id");
				$messageId = $event->getParam("message_id");

				if (!$formId || !$messageId) return false;

				$form = umiObjectTypesCollection::getInstance()->getType($formId);
				$module = cmsController::getInstance()->getModule('webforms');
				$formLinks = $module->getObjectTypeEditLink($formId);
				$messageLink = $module->getObjectEditLink($messageId, 'message');
				if (isset($formLinks['edit-link']) && $messageLink) {
					$this->registerEvent('webforms-message-add', array($messageLink, $formLinks['edit-link'], $form->getName()), null, $messageId);
				}
			}
		}

		/**
		 * Создает событие типа 'tickets-ticket-create'
		 * @param iUmiEventPoint $event событие "deleteTicket"
		 * @return bool
		 */
		public function onCreateTicket(iUmiEventPoint $event) {
			if ($event->getMode() !== 'after') {
				return false;
			}

			$ticketId = $event->getParam('id');
			$umiObjects = umiObjectsCollection::getInstance();
			/* @var iUmiObject $ticket */
			$ticket = $umiObjects->getObject($ticketId);

			if (!$ticket instanceof iUmiObject) {
				return false;
			}

			$ticketMessage = (string) $ticket->getValue('message');
			$ticketPageLink = (string) $ticket->getValue('url');
			/* @var __eventsHandlersEvents|events $this */
			$this->registerEvent('tickets-ticket-create', array($ticketMessage, $ticketPageLink, $ticketPageLink), null, $ticketId);
		}

		/**
		 * Создает событие типа 'tickets-ticket-delete'
		 * @param iUmiEventPoint $event событие "createTicket"
		 * @return bool
		 */
		public function onDeleteTicket(iUmiEventPoint $event) {
			if ($event->getMode() !== 'after') {
				return false;
			}

			$ticketId = $event->getParam('id');
			$ticketMessage = $event->getParam('message');
			$ticketPageLink = $event->getParam('url');
			/* @var __eventsHandlersEvents|events $this */
			$this->registerEvent('tickets-ticket-delete', array($ticketMessage, $ticketPageLink, $ticketPageLink), null, $ticketId);
		}
	}
?>