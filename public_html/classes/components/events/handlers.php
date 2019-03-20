<?php

	use UmiCms\Service;

	/** Класс, содержащий обработчики событий */
	class EventsHandlers {

		/** @var events $module */
		public $module;

		/**
		 * Обработчик события успешной авторизации пользователя.
		 * Обновляет список системных уведомлений
		 * @param iUmiEventPoint $event событие авторизации
		 * @throws Exception
		 * @throws umiRemoteFileGetterException
		 */
		public function onUsersLoginSuccessfull(iUmiEventPoint $event) {
			$userId = $event->getParam('user_id');
			if (!permissionsCollection::getInstance()->isAdmin($userId)) {
				return;
			}

			$regedit = Service::Registry();
			$lastConnect = $regedit->get('//umiMessages/lastConnectTime');
			if ($lastConnect && $lastConnect >= time() - 86400) {
				return;
			}

			$lastMessageId = $regedit->get('//umiMessages/lastMessageId');
			if (!$lastMessageId) {
				$lastMessageId = 0;
			}

			$info = [];
			$info['keycode'] = Service::RegistrySettings()->getLicense();
			$info['last-message-id'] = $lastMessageId;

			$package = base64_encode(serialize($info));

			$url = 'http://messages.umi-cms.ru/udata/custom/getUmiMessages/' . $package . '/';
			$result = umiRemoteFileGetter::get($url, false, false, false, false, false, 3);

			if ($result) {
				$old = libxml_use_internal_errors(true);
				$xml = simplexml_load_string($result);
				if ($xml && is_array($messages = $xml->xpath('//message'))) {
					foreach ($messages as $message) {
						$lastId = (string) $message->attributes()->id;
						if ($lastMessageId < $lastId) {
							$lastMessageId = $lastId;
						}
						$this->module->registerEvent('users-adv-message', [(string) $message]);
					}
					$regedit->set('//umiMessages/lastConnectTime', time());
					$regedit->set('//umiMessages/lastMessageId', $lastMessageId);
				}
				libxml_use_internal_errors($old);
			}
		}

		/**
		 * Обработчик события просмотра формы редактирования страницы.
		 * Помечает связанное со страницей событие как прочитанное.
		 * @param iUmiEventPoint $event событие просмотра формы редактирования
		 * @throws Exception
		 */
		public function onPageView(iUmiEventPoint $event) {
			if ($event->getMode() == 'before') {
				if (!$event->getRef('element') instanceof iUmiHierarchyElement) {
					return;
				}
				/** @var iUmiEntinty $element */
				$element = $event->getRef('element');
				$elementId = $element->getId();
				$user = $this->module->getUser();

				$pool = ConnectionPool::getInstance();
				$connection = $pool->getConnection();
				umiEventFeed::setConnection($connection);

				$eventId = umiEventFeed::getEventsIdsByPageId($elementId);
				if ($eventId) {
					umiEventFeed::markReadEvent($eventId, $user->getId());
				}
			}
		}

		/**
		 * Обработчик события перемещения страницы в корзину.
		 * Удаляет связанное со страницей событие.
		 * @param iUmiEventPoint $event событие перемещения страницы в корзину
		 * @throws Exception
		 */
		public function onPageHierarchyDelete(iUmiEventPoint $event) {
			if ($event->getMode() == 'after') {

				$elementId = $event->getParam('element_id');
				$pool = ConnectionPool::getInstance();
				$connection = $pool->getConnection();
				umiEventFeed::setConnection($connection);

				$eventId = umiEventFeed::getEventsIdsByPageId($elementId);
				if ($eventId) {
					umiEventFeed::deleteEvent($eventId);
				}
			}
		}

		/**
		 * Обработчик события удаления страницы.
		 * Удаляет связанное со страницей событие.
		 * @param iUmiEventPoint $event событие удаления страницы.
		 * @throws Exception
		 */
		public function onPageSystemDelete(iUmiEventPoint $event) {
			if ($event->getMode() == 'before') {
				if (!$event->getRef('element') instanceof iUmiHierarchyElement) {
					return;
				}

				$elementId = $event->getRef('element')->getId();
				$pool = ConnectionPool::getInstance();
				$connection = $pool->getConnection();
				umiEventFeed::setConnection($connection);

				$eventId = umiEventFeed::getEventsIdsByPageId($elementId);
				if ($eventId) {
					umiEventFeed::deleteEvent($eventId);
				}
			}
		}

		/**
		 * Обработчик события удаления объекта.
		 * Удаляет связанное с объектом событие.
		 * @param iUmiEventPoint $event событие удаления страницы.
		 * @throws Exception
		 */
		public function onObjectDelete(iUmiEventPoint $event) {
			if ($event->getMode() == 'after') {

				$objectId = $event->getParam('object_id');
				$pool = ConnectionPool::getInstance();
				$connection = $pool->getConnection();
				umiEventFeed::setConnection($connection);

				$eventId = umiEventFeed::getEventsByObjectId($objectId);
				if ($eventId) {
					umiEventFeed::deleteEvent($eventId);
				}
			}
		}

		/**
		 * Обработчик события просмотра формы редактирования объекта.
		 * Помечает связанное с объектом событие как прочитанное.
		 * @param iUmiEventPoint $event событие просмотра формы редактирования
		 * @throws Exception
		 */
		public function onObjectView(iUmiEventPoint $event) {
			if ($event->getMode() == 'before') {
				/** @var iUmiEntinty $object */
				$object = $event->getRef('object');
				$objectId = $object->getId();
				$user = $this->module->getUser();

				$pool = ConnectionPool::getInstance();
				$connection = $pool->getConnection();
				umiEventFeed::setConnection($connection);

				$eventId = umiEventFeed::getEventsByObjectId($objectId);
				if ($eventId) {
					umiEventFeed::markReadEvent($eventId, $user->getId());
				}
			}
		}

		/**
		 * Обработчик события создания поста модуля "Блоги"
		 * Создает соответствующее событие
		 * @param iUmiEventPoint $event событие создания поста блога.
		 */
		public function onBlogsPostAdded(iUmiEventPoint $event) {
			if ($event->getMode() == 'after') {
				$postId = $event->getParam('id');

				$post = umiHierarchy::getInstance()->getElement($postId, true, true);
				/** @var blogs20 $module */
				$module = cmsController::getInstance()->getModule('blogs20');
				$links = $module->getEditLink($postId, 'post');

				if (isset($links[1])) {
					$this->module->registerEvent('blogs20-post-add', [$links[1], $post->getName()], $postId);
				}
			}
		}

		/**
		 * Обработчик события создания комментария модуля "Блоги"
		 * Создает соответствующее событие
		 * @param iUmiEventPoint $event событие создания комментария блога.
		 */
		public function onBlogsCommentAdded(iUmiEventPoint $event) {
			if ($event->getMode() == 'after') {
				$commentId = $event->getParam('id');
				$comment = umiHierarchy::getInstance()->getElement($commentId, true, true);
				/** @var blogs20 $module */
				$module = cmsController::getInstance()->getModule('blogs20');
				$links = $module->getEditLink($commentId, 'comment');

				if (isset($links[1])) {
					$this->module->registerEvent('blogs20-comment-add', [$links[1], $comment->getName()], $commentId);
				}
			}
		}

		/**
		 * Обработчик события комментария модуля "Комментарии"
		 * Создает соответствующее событие
		 * @param iUmiEventPoint $event событие создания комментария
		 */
		public function onCommentsCommentPost(iUmiEventPoint $event) {
			if ($event->getMode() == 'after') {
				$commentId = $event->getParam('message_id');
				$element = umiHierarchy::getInstance()->getElement($event->getParam('topic_id'));

				if ($element) {
					$moduleName = $element->getModule();
					$methodName = $element->getMethod();

					/** @var blogs20|content $module */
					$module = cmsController::getInstance()->getModule($moduleName);
					/** @var comments $comments */
					$comments = cmsController::getInstance()->getModule('comments');

					$pageLinks = $module->getEditLink($event->getParam('topic_id'), $methodName);
					$commentLinks = $comments->getEditLink($commentId, 'comment');

					if (isset($pageLinks[1]) && isset($commentLinks[1])) {
						$this->module->registerEvent('comments-comment-add', [$commentLinks[1], $pageLinks[1], $element->getName()], $commentId);
					}
				}
			}
		}

		/**
		 * Обработчик события создания заказа модуля "Интернет-магазин"
		 * Создает соответствующее событие
		 * @param iUmiEventPoint $event событие создания заказа
		 */
		public function onEmarketOrderAdded(iUmiEventPoint $event) {
			if ($event->getMode() == 'after' && $event->getParam('old-status-id') != $event->getParam('new-status-id')) {
				if ($event->getParam('new-status-id') == order::getStatusByCode('waiting') &&
					$event->getParam('old-status-id') != order::getStatusByCode('editing')) {
					$module = cmsController::getInstance()->getModule('emarket');
					/** @var iUmiObject $order */
					$order = $event->getRef('order');
					$link = $module->getObjectEditLink($order->getId(), 'order');
					$this->module->registerEvent('emarket-order-add', [$link, $order->getName()], null, $order->getId());
				}
			}
		}

		/**
		 * Обработчик события создания вопроса модуля "FAQ"
		 * Создает соответствующее событие
		 * @param iUmiEventPoint $event событие создания вопроса
		 */
		public function onFaqQuestionPost(iUmiEventPoint $event) {
			if ($event->getMode() == 'after') {
				$questionId = $event->getParam('element_id');

				$question = umiHierarchy::getInstance()->getElement($questionId, true, true);
				/** @var faq $module */
				$module = cmsController::getInstance()->getModule('faq');
				$links = $module->getEditLink($questionId, 'question');

				if (isset($links[1])) {
					$this->module->registerEvent('faq-question-add', [$links[1], $question->getName()], $questionId);
				}
			}
		}

		/**
		 * Обработчик события создания сообщения модуля "Форум"
		 * Создает соответствующее событие
		 * @param iUmiEventPoint $event событие создания сообщения
		 */
		public function onForumMessagePost(iUmiEventPoint $event) {
			if ($event->getMode() == 'after') {
				$messageId = $event->getParam('message_id');
				$message = umiHierarchy::getInstance()->getElement($messageId, true, true);
				/** @var forum $module */
				$module = cmsController::getInstance()->getModule('forum');
				$links = $module->getEditLink($messageId, 'message');

				if (isset($links[1])) {
					$this->module->registerEvent('forum-message-add', [$links[1], $message->getName()], $messageId);
				}
			}
		}

		/**
		 * Обработчик события создания пользователя модуля "Пользователи"
		 * Создает соответствующее событие
		 * @param iUmiEventPoint $event событие создания пользователя
		 */
		public function onUsersRegistered(iUmiEventPoint $event) {
			if ($event->getMode() == 'after') {
				$module = cmsController::getInstance()->getModule('users');
				$link = $module->getObjectEditLink($event->getParam('user_id'));
				$this->module->registerEvent('users-user-register', [$link, $event->getParam('login')], null, $event->getParam('user_id'));
			}
		}

		/**
		 * Обработчик события создания ответа опроса модуля "Опросы"
		 * Создает соответствующее событие
		 * @param iUmiEventPoint $event событие создания опроса
		 */
		public function onVotePollPost(iUmiEventPoint $event) {
			if ($event->getMode() == 'after') {
				/** @var vote $module */
				$module = cmsController::getInstance()->getModule('vote');
				$poll = $event->getParam('poll');
				$answer = $event->getParam('answer');
				$elementIds = umiHierarchy::getInstance()->getObjectInstances($poll->getId());
				$elementId = array_shift($elementIds);
				$links = $module->getEditLink($elementId, 'poll');

				if (isset($links[1])) {
					$this->module->registerEvent('vote-response-add', [$answer->getName(), $links[1], $poll->getName()], $elementId);
				}
			}
		}

		/**
		 * Обработчик события создания сообщения модуля "Конструктор форм"
		 * Создает соответствующее событие
		 * @param iUmiEventPoint $event события создания сообщения
		 * @return bool|void
		 */
		public function onWebformsPost(iUmiEventPoint $event) {
			if ($event->getMode() != 'after') {
				return false;
			}

			$formId = $event->getParam('form_id');
			$messageId = $event->getParam('message_id');

			if (!$formId || !$messageId) {
				return false;
			}

			$form = umiObjectTypesCollection::getInstance()->getType($formId);

			/** @var webforms|WebFormsMacros $module */
			$module = cmsController::getInstance()->getModule('webforms');
			$formLinks = $module->getObjectTypeEditLink($formId);
			$messageLink = $module->getObjectEditLink($messageId, 'message');

			if (isset($formLinks['edit-link']) && $messageLink) {
				$this->module->registerEvent(
					'webforms-message-add',
					[
						$messageLink,
						$formLinks['edit-link'],
						$form->getName()
					],
					null,
					$messageId
				);
			}
		}

		/**
		 * Обработчик события создания заметки модуля "Заметки"
		 * Создает соответствующее событие
		 * @param iUmiEventPoint $event события создания заметки
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
			$this->module->registerEvent('tickets-ticket-create', [$ticketMessage, $ticketPageLink, $ticketPageLink], null, $ticketId);
		}

		/**
		 * Обработчик события удаления заметки модуля "Заметки"
		 * Создает соответствующее событие
		 * @param iUmiEventPoint $event события удаления заметки
		 * @return bool
		 */
		public function onDeleteTicket(iUmiEventPoint $event) {
			if ($event->getMode() !== 'after') {
				return false;
			}

			$ticketId = $event->getParam('id');
			$ticketMessage = $event->getParam('message');
			$ticketPageLink = $event->getParam('url');
			$this->module->registerEvent('tickets-ticket-delete', [$ticketMessage, $ticketPageLink, $ticketPageLink], null, $ticketId);
		}
	}
