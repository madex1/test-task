<?php

	use UmiCms\Service;

	/**
	 * Базовый класс модуля "Рассылки".
	 * Модуль управляет следующими сущностями:
	 * 1) Рассылки;
	 * 2) Выпуски;
	 * 3) Сообщения;
	 * 4) Подписчики;
	 * Модуль умеет отправлять рассылки, как в вручную (через админинстративную панель),
	 * так и автоматически по срабатыванию системного крона.
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_rassylki/
	 */
	class dispatches extends def_module {

		/**
		 * Конструктор
		 * @throws coreException
		 */
		public function __construct() {
			parent::__construct();

			$this->per_page = (int) Service::Registry()
				->get('//modules/dispatches/per_page');

			if (!$this->per_page) {
				$this->per_page = 15;
			}

			if (Service::Request()->isAdmin()) {
				$this->initTabs()
					->includeAdminClasses();
			}

			$this->includeCommonClasses()
				->switchConfig();
		}

		/**
		 * Создает вкладки административной панели модуля
		 * @return $this
		 */
		public function initTabs() {
			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('lists');
				$commonTabs->add('subscribers');
				$commonTabs->add('messages', ['releases']);
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('DispatchesAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('DispatchesCustomAdmin', true);

			return $this;
		}

		/**
		 * Подключает общие классы функционала
		 * @return $this
		 */
		public function includeCommonClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('DispatchesMacros');

			$this->loadSiteExtension();

			$this->__loadLib('handlers.php');
			$this->__implement('DispatchesHandlers');

			$this->__loadLib('customMacros.php');
			$this->__implement('DispatchesCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();

			return $this;
		}

		/**
		 * Возвращает ссылку на страницу редактирования рассылки
		 * @param int $objectId идентификатор рассылки
		 * @param bool $type контрольный параметр
		 * @return string
		 */
		public function getObjectEditLink($objectId, $type = false) {
			return $this->pre_lang . '/admin/dispatches/edit/' . $objectId . '/';
		}

		/**
		 * Возвращает является ли объект - подписчиком
		 * @param mixed $object проверяемый объект
		 * @return bool
		 */
		public function isSubscriber($object) {
			return ($object instanceof iUmiObject && $object->getTypeGUID() == 'dispatches-subscriber');
		}

		/**
		 * Возвращает является ли объект - рассылкой
		 * @param mixed $object проверяемый объект
		 * @return bool
		 */
		public function isDispatch($object) {
			return ($object instanceof iUmiObject && $object->getTypeGUID() == 'dispatches-dispatch');
		}

		/**
		 * Возвращает подписчика по идентификатору пользователя
		 * @param int $userId идентификатор пользователя
		 * @return iUmiObject|null
		 * @throws coreException
		 */
		public function getSubscriberByUserId($userId) {
			$sel = new selector('objects');
			$sel->types('object-type')->name('dispatches', 'subscriber');
			$sel->where('uid')->equals($userId);
			$sel->limit(0, 1);
			return $sel->first();
		}

		/**
		 * Возвращает подписчика по почтовому ящику
		 * @param string $email почтовый ящик
		 * @return iUmiObject|null
		 * @throws coreException
		 */
		public function getSubscriberByMail($email) {
			$sel = new selector('objects');
			$sel->types('object-type')->name('dispatches', 'subscriber');
			$sel->where('name')->equals($email);
			$sel->limit(0, 1);
			return $sel->first();
		}

		/**
		 * Возвращает список рассылок
		 * @return iUmiObject[]
		 * @throws selectorException
		 */
		public function getAllDispatches() {
			static $cache = null;

			if ($cache !== null) {
				return $cache;
			}

			$dispatches = new selector('objects');
			$dispatches->types('object-type')->name('dispatches', 'dispatch');
			$dispatches->where('is_active')->equals(true);
			$dispatches->option('no-length')->value(true);
			$dispatches->option('load-all-props')->value(true);
			return $cache = $dispatches->result();
		}

		/**
		 * Изменяет рассылку, в которую будут выгружаться новые темы форума,
		 * @param iUmiObject $dispatch новая рассылка
		 * @throws selectorException
		 */
		public function changeLoadFromForumDispatch($dispatch) {
			/** @var iUmiObject $dispatch */
			$dispatches = new selector('objects');
			$dispatches->types('object-type')->name('dispatches', 'dispatch');
			$dispatches->where('load_from_forum')->equals(true);

			/** @var iUmiObject $object */
			foreach ($dispatches as $object) {
				$object->setValue('load_from_forum', false);
				$object->commit();
			}

			Service::Registry()->set('//modules/forum/dispatch_id', $dispatch->getId());
			$dispatch->setValue('load_from_forum', true);
			$dispatch->commit();
		}

		/**
		 * Возвращает сообщения рассылки
		 * @param bool|int $releaseId идентификатор выпуска рассылки
		 * @return iUmiObject[]
		 * @throws selectorException
		 */
		public function getReleaseMessages($releaseId = false) {
			$sel = new selector('objects');
			$sel->types('object-type')->name('dispatches', 'message');

			if ($releaseId) {
				$sel->where('release_reference')->equals($releaseId);
			}

			selectorHelper::detectFilters($sel);
			return $sel->result();
		}

		/**
		 * Возвращает идентификатор выпуска указанной рассылки
		 * @param bool|int $dispatchId идентификатор рассылки
		 * @return bool|int
		 * @throws coreException
		 */
		public function getNewReleaseInstanceId($dispatchId = false) {
			$umiObjects = umiObjectsCollection::getInstance();
			static $releases = [];

			if (!$dispatchId) {
				$dispatchId = getRequest('param0');
			}

			if (isset($releases[$dispatchId])) {
				return $releases[$dispatchId];
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('dispatches', 'release');
			$sel->where('status')->isnull();
			$sel->where('disp_reference')->equals($dispatchId);
			$sel->option('return')->value('id');
			$sel->limit(0, 1);
			$result = $sel->result();

			$releaseId = null;
			$isNewRelease = false;

			if ($result) {
				foreach ($result as $info) {
					$releaseId = $info['id'];
				}
			} else {
				$type = selector::get('object-type')->name('dispatches', 'release');
				$releaseId = $umiObjects->addObject('', $type->getId());
				$isNewRelease = true;
			}

			$release = $umiObjects->getObject($releaseId);

			if ($release instanceof iUmiObject) {
				if ($isNewRelease) {
					$release->setName('-');
					$release->setValue('status', false);
					$release->setValue('disp_reference', $dispatchId);
					$release->commit();
				}

				$releases[$dispatchId] = $release->getId();
			}

			return $releaseId;
		}

		/**
		 * Заполняет выпуск рассылки сообщениями
		 * @param bool|int $dispatchId идентификатор рассылки
		 * @param bool $ignoreRedirect производить редирект
		 * на страницу с формой редактирования после завершения работы
		 * @throws coreException
		 */
		public function fill_release($dispatchId = false, $ignoreRedirect = false) {
			$umiObjects = umiObjectsCollection::getInstance();
			$umiHierarchy = umiHierarchy::getInstance();

			$dispatchId = $dispatchId ?: getRequest('param0');
			$dispatch = $umiObjects->getObject($dispatchId);

			if (!$dispatch instanceof iUmiObject) {
				$this->getFillingResult($ignoreRedirect, $dispatchId);
				return;
			}

			$releaseId = $this->getNewReleaseInstanceId($dispatchId);
			$newsRelation = $dispatch->getValue('news_relation');
			$newsLents = $umiHierarchy->getObjectInstances($newsRelation, false, true);

			if (umiCount($newsLents) === 0) {
				$this->getFillingResult($ignoreRedirect, $dispatchId);
				return;
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('dispatches', 'release');
			$sel->where('disp_reference')->equals($dispatchId);
			$sel->option('return')->value('id');
			$result = $sel->result();

			$releaseIds = array_map(function ($info) {
				return (int) $info['id'];
			}, $result);

			$elementId = (int) $newsLents[0];

			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('news', 'item');
			$sel->order('publish_time')->desc();
			$sel->where('hierarchy')->page($elementId)->level(100);
			$sel->where('lang')->equals(false);
			$sel->limit(0, 50);
			$result = $sel->result();
			$messageTypeId = selector::get('object-type')
				->name('dispatches', 'message')
				->getId();

			/** @var iUmiHierarchyElement $newsItem */
			foreach ($result as $newsItem) {
				if (!$newsItem instanceof iUmiHierarchyElement) {
					continue;
				}

				$newsItemId = $newsItem->getId();

				$name = $newsItem->getName();
				$header = $newsItem->getValue('h1');
				$shortBody = $newsItem->getValue('anons');
				$body = (string) $newsItem->getValue('content');
				$publishTime = $newsItem->getValue('publish_time');

				if ($body === '') {
					$body = $shortBody;
				}

				$sel = new selector('objects');
				$sel->types('object-type')->name('dispatches', 'message');
				$sel->where('new_relation')->equals($newsItemId);
				$sel->where('release_reference')->equals($releaseIds);
				$sel->limit(0, 1);
				$result = $sel->result();

				if (umiCount($result) > 0) {
					continue;
				}

				$messageId = $umiObjects->addObject($name, $messageTypeId);
				$message = $umiObjects->getObject($messageId);

				if (!$message instanceof iUmiObject) {
					continue;
				}

				$message->setValue('release_reference', $releaseId);
				$message->setValue('header', $header);
				$message->setValue('body', $body);
				$message->setValue('short_body', $shortBody);
				$message->setValue('new_relation', [$newsItemId]);

				if ($publishTime instanceof iUmiDate) {
					$message->setValue('msg_date', $publishTime);
				}

				$message->commit();
				$umiObjects->unloadObject($message->getId());
			}

			$this->getFillingResult($ignoreRedirect, $dispatchId);
		}

		/**
		 * Отправляет выпуск рассылки всем подписчикам
		 * @param int $dispatchId идентификатор рассылки
		 * @return bool
		 * @throws selectorException
		 * @throws coreException
		 * @throws publicException
		 * @throws Exception
		 */
		public function release_send_full($dispatchId) {
			$umiObjects = umiObjectsCollection::getInstance();

			$releaseId = $this->getNewReleaseInstanceId($dispatchId);
			$dispatch = $umiObjects->getObject($dispatchId);
			$release = $umiObjects->getObject($releaseId);

			if (!$dispatch instanceof iUmiObject || !$release instanceof iUmiObject) {
				return false;
			}

			if ($release->getValue('status')) {
				return false;
			}

			$mailer = new umiMail();

			$contentVariables = [
				'header' => $dispatch->getName(),
				'+messages' => []
			];

			$contentTemplate = null;
			$messageTemplate = null;

			try {
				list($contentTemplate, $messageTemplate) = dispatches::loadTemplatesForMail(
					'dispatches/release',
					'release_body',
					'release_message'
				);
			} catch (Exception $e) {
				// nothing
			}

			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('dispatches', 'message');
			$sel->where('release_reference')->equals($releaseId);
			$messages = $sel->result();

			if (!$sel->length()) {
				return false;
			}

			foreach ($messages as $message) {
				if (!$message instanceof iUmiObject) {
					continue;
				}

				$messageVariables = [
					'body' => $message->getValue('body'),
					'header' => $message->getValue('header'),
					'id' => $message->getId()
				];

				if ($this->isUsingUmiNotifications()) {
					$contentVariables['+messages'][] = $messageVariables;
				} else {
					try {
						$contentVariables['+messages'][] = dispatches::parseTemplateForMail(
							$messageTemplate,
							$messageVariables,
							false,
							$message->getId()
						);
					} catch (Exception $e) {
						// nothing
					}
				}

				$attachment = $message->getValue('attach_file');

				if ($attachment instanceof iUmiFile && !$attachment->getIsBroken()) {
					$mailer->attachFile($attachment);
				}

				$umiObjects->unloadObject($messageVariables['id']);
			}

			$mailSettings = $this->getMailSettings();
			$mailer->setFrom($mailSettings->getSenderEmail(), $mailSettings->getSenderName());

			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('dispatches', 'subscriber');
			$sel->where('subscriber_dispatches')->equals($dispatchId);
			$sel->group('name');

			$delay = 0;
			$maxMessages = (int) mainConfiguration::getInstance()->get('modules', 'dispatches.max_messages_in_hour');

			if ($maxMessages && $sel->length() >= $maxMessages) {
				$delay = floor(3600 / $maxMessages);
			}

			/** @var iUmiObject $recipient */
			foreach ($sel->result() as $recipient) {
				$nextMailer = clone $mailer;
				$subscriber = new umiSubscriber($recipient->getId());

				if ($subscriber->releaseWasSent($releaseId)) {
					continue;
				}

				$recipientName = $subscriber->getFullName();
				$email = $subscriber->getEmail();

				$contentVariables['unsubscribe_link'] = $this->getUnSubscribeLink($recipient, $email);
				$objectList = [$dispatch, $release, $recipient];

				$subject = null;
				$content = null;

				$mailNotifications = Service::MailNotifications();
				$notification = $mailNotifications->getCurrentByName('notification-dispatches-release');

				if ($notification instanceof MailNotification && $this->isUsingUmiNotifications()) {
					$subjectTemplate = $notification->getTemplateByName('dispatches-release-subject');
					$contentTemplate = $notification->getTemplateByName('dispatches-release-content');

					if ($subjectTemplate instanceof MailTemplate) {
						$subject = $subjectTemplate->parse(
							['header' => $contentVariables['header']],
							$objectList
						);
					}

					if ($contentTemplate instanceof MailTemplate) {
						$content = $contentTemplate->parse($contentVariables, $objectList);
					}
				} else {
					try {
						$subject = $contentVariables['header'];
						$content = dispatches::parseTemplateForMail(
							$contentTemplate,
							$contentVariables,
							false,
							$subscriber->getId()
						);
					} catch (Exception $e) {
						// nothing
					}
				}

				if ($subject === null || $content === null) {
					continue;
				}

				$nextMailer->setSubject($subject);
				$nextMailer->setContent($content);
				$nextMailer->addRecipient($email, $recipientName);
				$nextMailer->commit();
				$nextMailer->send();

				$subscriber->putReleaseToSentList($releaseId);
				$subscriber->commit();

				if ($delay) {
					sleep($delay);
				}

				$umiObjects->unloadObject($recipient->getId());
			}

			$date = new umiDate(time());
			$dispatch->setValue('disp_last_release', $date);
			$dispatch->commit();

			$release->setValue('status', true);
			$release->setValue('date', $date);
			$release->setName(sprintf('%s: %s', $dispatch->getName(), $date->getFormattedDate('d-m-Y H:i')));
			$release->commit();

			return true;
		}

		/**
		 * Возвращает ссылку отписки от рассылки
		 * @param iUmiObject $subscriber объект подписчика
		 * @param string $email подписчика
		 * @return string
		 * @throws coreException
		 */
		public function getUnSubscribeLink(iUmiObject $subscriber, $email) {
			$domain = Service::DomainDetector()->detectUrl();
			/** @var iUmiObject $subscriber */
			$subscriberId = $subscriber->getId();

			return $domain . $this->pre_lang . '/dispatches/unsubscribe/?id=' . $subscriberId . '&email=' . $email;
		}

		/** @inheritdoc */
		public function getMailObjectTypesGuidList() {
			return ['dispatches-dispatch', 'dispatches-subscriber', 'dispatches-release'];
		}

		/**
		 * Возвращает результат наполнения выпуска рассылки сообщениями
		 * @param bool $ignore_redirect производить редирект
		 * @param int $iDispId идентификатор рассылки
		 * @throws coreException
		 */
		private function getFillingResult($ignore_redirect, $iDispId) {
			if (!$ignore_redirect) {
				$this->redirect('/admin/dispatches/edit/' . $iDispId . '/');
			}
		}

		/** Переключает вывод ссылки на настройки модуля */
		private function switchConfig() {
			$umiNotificationInstalled = cmsController::getInstance()
				->isModule('umiNotifications');
			$needToShowConfig = false;

			if ($umiNotificationInstalled) {
				$needToShowConfig = true;
			}

			Service::Registry()
				->set('//modules/dispatches/config', $needToShowConfig);
		}

		/** @deprecated  */
		public function getVariableNamesForMailTemplates() {
			return [];
		}
	}
