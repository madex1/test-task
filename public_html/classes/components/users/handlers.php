<?php

	use UmiCms\Service;

	/** Класс обработчиков событий */
	class UsersHandlers {

		/** @var users $module */
		public $module;

		/**
		 * Обработчик события создания объекта через форму создания
		 * Запускает валидацию логина, пароля и почтового ящика
		 * @param iUmiEventPoint $e событие создания
		 * @throws errorPanicException
		 * @throws wrongValueException
		 * @throws privateException
		 * @throws coreException
		 */
		public function onCreateObject(iUmiEventPoint $e) {
			/** @var iUmiObject $object */
			$object = $e->getRef('object');
			$objectType = umiObjectTypesCollection::getInstance()->getType($object->getTypeId());
			if ($objectType->getModule() != 'users' || $objectType->getMethod() != 'user') {
				return;
			}

			if ($e->getMode() == 'before') {

				if (!isset($_REQUEST['data']['new']['login'])) {
					$_REQUEST['data']['new']['login'] = $_REQUEST['name'];
				}

				$this->module->errorSetErrorPage($this->module->pre_lang . '/admin/users/add/user/');

				$_REQUEST['data']['new']['login'] = $this->module->validateLogin($_REQUEST['data']['new']['login']);
				$_REQUEST['data']['new']['password'][0] = $this->module->validatePassword(
					$_REQUEST['data']['new']['password'][0],
					null,
					$_REQUEST['data']['new']['login']
				);
				$_REQUEST['data']['new']['e-mail'] = $this->module->validateEmail($_REQUEST['data']['new']['e-mail']);
				$object->setName($_REQUEST['data']['new']['login']);

				if ($this->module->errorHasErrors()) {
					if ($object instanceof iUmiObject) {
						umiObjectsCollection::getInstance()->delObject($object->getId());
					}
				}
				$this->module->errorThrow('admin');
			}
		}

		/**
		 * Обработчик события редактирования объекта через форму редактирования
		 * Запускает валидацию логина, пароля и почтового ящика
		 * @param umiEventPoint $e событие редактирования
		 * @throws errorPanicException
		 * @throws wrongValueException
		 * @throws privateException
		 * @throws coreException
		 */
		public function onModifyObject(umiEventPoint $e) {
			static $orig_groups = [];
			/** @var iUmiObject $object */
			$object = $e->getRef('object');
			$objectId = $object->getId();
			$objectType = umiObjectTypesCollection::getInstance()->getType($object->getTypeId());

			if ($objectType->getModule() != 'users' || $objectType->getMethod() != 'user') {
				return;
			}

			if ($e->getMode() == 'before') {
				$orig_groups[$objectId] = $object->getValue('groups');

				if (!isset($_REQUEST['data'][$objectId]['login'])) {
					$_REQUEST['data'][$objectId]['login'] = $_REQUEST['name'];
				}

				$this->module->errorSetErrorPage($this->module->pre_lang . "/admin/users/edit/{$objectId}/");

				$_REQUEST['data'][$objectId]['login'] =
					$this->module->validateLogin($_REQUEST['data'][$objectId]['login'], $objectId);
				if (isset($_REQUEST['data'][$objectId]['password'][0]) &&
					trim($_REQUEST['data'][$objectId]['password'][0])) {
					$_REQUEST['data'][$objectId]['password'][0] = $this->module->validatePassword(
						$_REQUEST['data'][$objectId]['password'][0],
						null,
						$_REQUEST['data'][$objectId]['login']
					);
				}
				$_REQUEST['data'][$objectId]['e-mail'] =
					$this->module->validateEmail($_REQUEST['data'][$objectId]['e-mail'], $objectId);
				$object->setName($_REQUEST['data'][$objectId]['login']);

				$this->module->errorThrow('admin');
			}

			if ($e->getMode() == 'after') {
				$auth = Service::Auth();
				$permissions = permissionsCollection::getInstance();
				$is_sv = $permissions->isSv($auth->getUserId());
				$systemUsersPermissions = Service::SystemUsersPermissions();
				$svUserId = $systemUsersPermissions->getSvUserId();
				$svGroupId = $systemUsersPermissions->getSvGroupId();

				if ($objectId == $svUserId) {
					$object->setValue('groups', [$svGroupId]);
				} else {
					$groups = $object->getValue('groups');
					if (!$is_sv) {
						if (in_array($svGroupId, $groups) && !in_array($svGroupId, $orig_groups[$objectId])) {
							unset($groups[array_search($svGroupId, $groups)]);
							$object->setValue('groups', $groups);
						} else {
							if (!in_array($svGroupId, $groups) && in_array($svGroupId, $orig_groups[$objectId])) {
								$groups[] = $svGroupId;
								$object->setValue('groups', $groups);
							}
						}
					}
				}
				$object->commit();
			}
		}

		/**
		 * Обработчик события редактирования поля объекта через табличный контрол
		 * Запускает валидацию логина или почтового ящика
		 * @param umiEventPoint $e событие редактирования
		 * @throws errorPanicException
		 * @throws wrongValueException
		 * @throws privateException
		 * @throws coreException
		 */
		public function onModifyPropertyValue(umiEventPoint $e) {
			$object = $e->getRef('entity');

			if ($object instanceof iUmiHierarchyElement) {
				/** @var iUmiObject */
				$object = $object->getObject();
			}

			$objectId = $object->getId();
			$objectType = umiObjectTypesCollection::getInstance()->getType($object->getTypeId());

			if (!$objectType || $objectType->getModule() != 'users' || $objectType->getMethod() != 'user') {
				return;
			}

			if ($e->getMode() == 'before') {
				$newValue = &$e->getRef('newValue');

				switch ((string) $e->getParam('property')) {
					case 'name' : {
						$newValue = $this->module->validateLogin($newValue, $objectId);
						break;
					}

					case 'e-mail' : {
						$newValue = $this->module->validateEmail($newValue, $objectId);
						break;
					}

					default:
						return;
				}

				$this->module->errorThrow('xml');
			}

			if ($e->getMode() == 'after') {
				switch ((string) $e->getParam('property')) {
					case 'login' : {
						$object->setName((string) $e->getParam('newValue'));
						$object->commit();
						break;
					}

					case 'name' : {
						$object->setValue('login', (string) $e->getParam('newValue'));
						$object->commit();
						break;
					}

					default:
						return;
				}
			}
		}

		/**
		 * Обработчик регистрации пользователя и изменения его настроек
		 * Запускает загрузку изображения для аватара, создание аватара и назначение
		 * его пользователю
		 * @param iUmiEventPoint $oEventPoint событие регистрации или изменения настроек
		 * @return bool
		 */
		public function onAutoCreateAvatar(iUmiEventPoint $oEventPoint) {
			$user_id = $oEventPoint->getParam('user_id');
			$avatar_type_id = umiObjectTypesCollection::getInstance()
				->getTypeIdByHierarchyTypeName('users', 'avatar');

			if ($oEventPoint->getMode() != 'after') {
				return false;
			}

			$image = umiImageFile::upload('avatar', 'user_avatar_file', './images/cms/data/picture/');

			if ($image) {
				$avatar_id = umiObjectsCollection::getInstance()->addObject("Avatar for user {$user_id}", $avatar_type_id);

				$avatar = umiObjectsCollection::getInstance()->getObject($avatar_id);
				$avatar->setValue('picture', $image);
				$avatar->setValue('is_hidden', true);
				$avatar->commit();

				$user = umiObjectsCollection::getInstance()->getObject($user_id);
				$user->setValue('avatar', $avatar_id);
				$user->commit();

				return true;
			}

			return false;
		}

		/**
		 * Обработчик регистрации пользователя
		 * Отправляет письмо-уведомление о регистрации администратору
		 * @param iUmiEventPoint $eventPoint событие регистрации
		 * @throws coreException
		 * @throws Exception
		 */
		public function onRegisterAdminMail(iUmiEventPoint $eventPoint) {
			if ($eventPoint->getMode() != 'after') {
				return;
			}

			$mailSettings = $this->module->getMailSettings();
			$emailTo = $mailSettings->getAdminEmail();
			$emailFrom = $mailSettings->getSenderEmail();
			$fioFrom = $mailSettings->getSenderName();

			$userId = $eventPoint->getParam('user_id');
			$user = umiObjectsCollection::getInstance()->getObject($userId);
			$login = $eventPoint->getParam('login');

			$variables = [
				'user_id' => $userId,
				'login' => (string) $login,
			];
			$objectList = [$user];

			$subject = null;
			$content = null;

			if ($this->module->isUsingUmiNotifications()) {
				$mailNotifications = Service::MailNotifications();
				$notification = $mailNotifications->getCurrentByName('notification-users-new-registration-admin');

				if ($notification instanceof MailNotification) {
					$subjectTemplate = $notification->getTemplateByName('users-new-registration-admin-subject');
					$contentTemplate = $notification->getTemplateByName('users-new-registration-admin-content');

					if ($subjectTemplate instanceof MailTemplate) {
						$subject = $subjectTemplate->parse($variables, $objectList);
					}

					if ($contentTemplate instanceof MailTemplate) {
						$content = $contentTemplate->parse($variables, $objectList);
					}
				}
			} else {
				try {
					list($contentTemplate, $subjectTemplate) = users::loadTemplatesForMail(
						'users/register/default', 'mail_admin_registrated', 'mail_admin_registrated_subject'
					);
					$subject = users::parseTemplateForMail($subjectTemplate, $variables, false, $userId);
					$content = users::parseTemplateForMail($contentTemplate, $variables, false, $userId);
				} catch (Exception $e) {
					// nothing
				}
			}

			if ($subject === null || $content === null) {
				return;
			}

			$mail = new umiMail();
			$mail->addRecipient($emailTo, $fioFrom);
			$mail->setFrom($emailFrom, $fioFrom);
			$mail->setSubject($subject);
			$mail->setContent($content);
			$mail->commit();
			$mail->send();
		}

		/**
		 * Обработчик события создания поста или сообщения модуля "Форум"
		 * Изменяет страницы, на которые подписан пользователь
		 * @param umiEventPoint $e события создания поста или сообщения
		 * @return bool
		 */
		public function onSubscribeChanges(umiEventPoint $e) {
			static $is_called;

			if ($is_called === true) {
				return true;
			}

			$mode = (bool) getRequest('subscribe_changes');
			$user_id = $this->module->user_id;

			if ($user_id) {
				$user = umiObjectsCollection::getInstance()->getObject($user_id);
				if ($user instanceof iUmiObject) {
					$topic_id = $e->getParam('topic_id');
					$subscribed_pages = $user->getValue('subscribed_pages');

					if ($mode) {
						$topic = umiHierarchy::getInstance()->getElement($topic_id);
						if ($topic instanceof iUmiHierarchyElement) {
							if (!in_array($topic, $subscribed_pages)) {
								$subscribed_pages[] = $topic_id;
							}
						}
					} else {
						$tmp = [];

						if (!is_array($subscribed_pages)) {
							$subscribed_pages = [];
						}

						/** @var iUmiEntinty $page */
						foreach ($subscribed_pages as $page) {
							if ($page->getId() != $topic_id) {
								$tmp[] = $page;
							}
						}
						$subscribed_pages = $tmp;
						unset($tmp);
					}

					$user->setValue('subscribed_pages', $subscribed_pages);
					$user->commit();

					return true;
				}

				return false;
			}

			return false;
		}

		/**
		 * Проверяет тестовое событие
		 * @param iUmiEventPoint $event тестовое событие
		 * @throws privateException
		 */
		public function checkMessage(iUmiEventPoint $event) {
			$dummyId = $event->getRef('id');
			$dummy = new umiMessage($dummyId);
			$dummy = $dummy->getContent();
			$umiRegistry = Service::Registry();
			$umiRegistry->doTesting($dummy);
			$umiMessages = umiMessages::getInstance();
			$umiMessages->dropTestMessages();
		}
	}
