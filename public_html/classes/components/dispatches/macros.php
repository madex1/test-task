<?php

use UmiCms\Service;

/** Класс макросов, то есть методов, доступных в шаблоне */
	class DispatchesMacros {

		/** @var dispatches $module */
		public $module;

		/**
		 * Подписывает на рассылку.
		 * @return array|mixed|string
		 * @throws publicException
		 * @throws selectorException
		 * @throws coreException
		 * @throws ErrorException
		 * @throws Exception
		 */
		public function subscribe_do() {
			$requestData = $this->getSubscriptionRequest();
			$email = $this->getSubscriptionEmail($requestData['email']);

			if (!umiMail::checkEmail($email)) {
				return $this->getSubscriptionError('%subscribe_incorrect_email%');
			}

			$data = $this->getInitialData($requestData);
			$dispatches = $this->getActualDispatches($requestData['dispatches']);
			$subscriber = $this->getExistingSubscriber($requestData['email']);

			if ($this->module->isSubscriber($subscriber)) {
				if (Service::Auth()->isAuthorized()) {
					$this->updateSubscriber($subscriber, $data);
				} else {
					$this->subscribeDispatches($subscriber, $dispatches);

					list($templateBlock) = dispatches::loadTemplates(
						'dispatches/default',
						'subscribe_guest_alredy_subscribed'
					);

					$result = [];
					$result['unsubscribe_link'] = $this->module->getUnSubscribeLink($subscriber, $email);
					return dispatches::parseTemplate($templateBlock, $result);
				}
			} else {
				$subscriber = $this->createSubscriber($data);
			}

			$this->sendSubscribingLetter($subscriber, $email);
			$this->subscribeDispatches($subscriber, $dispatches);

			return $this->getSubscriptionResult($dispatches);
		}

		/**
		 * Возвращает данные для создания формы подписки на рассылку.
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function subscribe($template = 'default') {
			if (!$template) {
				$template = 'default';
			}

			list(
				$guestForm,
				$userForm,
				$dispatchesForm,
				$dispatchRowForm
				) = dispatches::loadTemplates(
				'dispatches/' . $template,
				'subscribe_unregistred_user',
				'subscribe_registred_user',
				'subscriber_dispatches',
				'subscriber_dispatch_row'
			);

			$isAuthorized = Service::Auth()->isAuthorized();

			if ($isAuthorized) {
				$userId = (int) Service::Auth()->getUserId();
				$subscriber = $this->module->getSubscriberByUserId($userId);
				$dispatches = [];

				if ($subscriber instanceof iUmiObject) {
					$dispatches = $subscriber->getValue('subscriber_dispatches');
				}

				$variables = [
					'subscriber_dispatches' => $this->parseDispatches($dispatchesForm, $dispatchRowForm, $dispatches)
				];

				return dispatches::parseTemplate($userForm, $variables);
			}

			$umiTypesHelper = umiTypesHelper::getInstance();
			$subscriberFields = $umiTypesHelper->getFieldsByObjectTypeGuid('dispatches-subscriber');
			$subscriberTypeId = $umiTypesHelper->getObjectTypeIdByGuid('dispatches-subscriber');

			$variables = [];

			if (isset($subscriberFields[$subscriberTypeId]['gender'])) {
				$genderField = umiFieldsCollection::getInstance()->getField($subscriberFields[$subscriberTypeId]['gender']);
				$genders = umiObjectsCollection::getInstance()->getGuidedItems($genderField->getGuideId());
				$genderList = [];

				foreach ($genders as $id => $name) {
					$genderList[] = '<option value="' . $id . '">' . $name . '</option>';
				}

				$variables = [
					'void:sbs_genders' => $genderList
				];
			}

			return dispatches::parseTemplate($guestForm, $variables);
		}

		/**
		 * Отписывает подписчика от рассылки
		 * @return string
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function unsubscribe() {
			$subscriberId = (int) getRequest('id');
			$subscriberEmail = (string) getRequest('email');

			$event = new umiEventPoint('unsubscribe');
			$event->setMode('before');
			$event->addRef('id', $subscriberId);
			$event->addRef('email', $subscriberEmail);
			$event->call();

			$subscriber = umiObjectsCollection::getInstance()
				->getObject($subscriberId);
			$macro = '%subscribe_unsubscribed_failed%';

			if ($this->module->isSubscriber($subscriber) && $subscriber->getName() == $subscriberEmail) {
				$subscriber->setValue('subscriber_dispatches', null);
				$subscriber->commit();
				$macro = '%subscribe_unsubscribed_ok%';
			}

			$event->setParam('subscriber', $subscriber);
			$event->setMode('after');
			$event->call();

			return dispatches::parseTPLMacroses($macro);
		}

		/**
		 * Возвращает список рассылок
		 * @param mixed $sDispatchesForm общий блок шаблона (для tpl)
		 * @param mixed $sDispatchRowForm блок шаблона отдельной рассылки (для tpl)
		 * @param array $arrChecked список выбранных рассылок
		 * @param bool $bOnlyChecked выводить только выбранные рассылки
		 * @return mixed
		 * @throws selectorException
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function parseDispatches($sDispatchesForm, $sDispatchRowForm, $arrChecked = [], $bOnlyChecked = false) {
			$arrDispSelResults = $this->module->getAllDispatches();
			$arrDispsBlock = [];
			$arrDispsBlock['void:rows'] = [];

			if (is_array($arrDispSelResults) && umiCount($arrDispSelResults)) {
				foreach ($arrDispSelResults as $dispatch) {

					if (!$dispatch instanceof iUmiObject) {
						continue;
					}

					$iNextDispId = $dispatch->getId();
					$arrDispRowBlock = [];
					$arrDispRowBlock['attribute:id'] = $arrDispRowBlock['void:disp_id'] = $dispatch->getId();
					$arrDispRowBlock['node:disp_name'] = $dispatch->getName();
					$arrDispRowBlock['attribute:is_checked'] = (in_array($iNextDispId, $arrChecked) ? 1 : 0);
					$arrDispRowBlock['void:checked'] = ($arrDispRowBlock['attribute:is_checked'] ? 'checked' : '');

					if ($arrDispRowBlock['attribute:is_checked'] || !$bOnlyChecked) {
						$arrDispsBlock['void:rows'][] = dispatches::parseTemplate(
							$sDispatchRowForm,
							$arrDispRowBlock,
							false,
							$iNextDispId
						);
					}
				}
			}

			$arrDispsBlock['nodes:items'] = $arrDispsBlock['void:rows'];
			return dispatches::parseTemplate($sDispatchesForm, $arrDispsBlock);
		}

		/**
		 * Возвращает сообщение об ошибки подписки на рассылку
		 * @param string $message сообщение об ошибке
		 * @return array|string
		 * @throws coreException
		 */
		public function getSubscriptionError($message) {
			if (!dispatches::isXSLTResultMode()) {
				return $message;
			}

			return [
				'result' => [
					'@class' => 'error',
					'node' => $message
				]
			];
		}

		/**
		 * Возвращает результат подписки на рассылку(и)
		 * @param array $dispatches список ID рассылок
		 * @return array|mixed|string
		 * @throws selectorException
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function getSubscriptionResult(array $dispatches) {
			$result = '%subscribe_subscribe%';

			if (Service::Auth()->isAuthorized()) {
				$blockTemplate = '%subscribe_subscribe_user%:<br /><ul>%rows%</ul>';
				$itemTemplate = '<li>%disp_name%</li>';
				$result = $this->parseDispatches($blockTemplate, $itemTemplate, $dispatches, true);
			}

			return (!dispatches::isXSLTResultMode()) ? $result : ['result' => $result];
		}

		/**
		 * Возвращает список лент новостей
		 * @return array
		 * @throws selectorException
		 */
		public function getNewsRubricList() {
			$newsRubricSelector = Service::SelectorFactory()
				->createPageTypeName('news', 'rubric');
			$newsRubricSelector->where('is_deleted')->equals(false);
			$result = [];

			/** @var iUmiHierarchyElement $newsRubric */
			foreach ($newsRubricSelector->result() as $newsRubric) {
				$result[] = [
					'@id' => $newsRubric->getObjectId(),
					'node:value' => $newsRubric->getName(),
				];
			}

			return [
				'nodes:item' => $result
			];
		}

		/**
		 * Возвращает массив с данными запроса для подписки на рассылки
		 * @return array
		 */
		private function getSubscriptionRequest() {
			return [
				'email' => trim(getRequest('sbs_mail')),
				'name' => getRequest('sbs_fname'),
				'lastName' => getRequest('sbs_lname'),
				'surname' => getRequest('sbs_father_name'),
				'gender' => (int) getRequest('sbs_gender'),
				'dispatches' => getRequest('subscriber_dispatches')
			];
		}

		/**
		 * Возвращает e-mail подписки
		 * @param mixed $email запрошенный e-mail
		 * @return mixed
		 */
		private function getSubscriptionEmail($email) {
			if (Service::Auth()->isAuthorized()) {
				$user = umiObjectsCollection::getInstance()->getObject(Service::Auth()->getUserId());

				if ($user instanceof iUmiObject) {
					return $user->getValue('e-mail');
				}
			}

			return $email;
		}

		/**
		 * Возвращает данные подписки
		 * @param array $data запрошенные данные
		 * @return array
		 */
		private function getInitialData($data) {
			if (Service::Auth()->isAuthorized()) {
				$user = umiObjectsCollection::getInstance()->getObject(Service::Auth()->getUserId());

				if ($user instanceof iUmiObject) {
					return [
						'email' => $user->getValue('e-mail'),
						'name' => $user->getValue('fname'),
						'lastName' => $user->getValue('lname'),
						'surname' => $user->getValue('father_name'),
						'gender' => $user->getValue('gender'),
					];
				}
			}

			return $data;
		}

		/**
		 * Возвращает существующего подписчика, если таковой существует
		 * @param mixed $email подписчика
		 * @return bool|null|iUmiObject
		 * @throws coreException
		 */
		private function getExistingSubscriber($email) {
			if (Service::Auth()->isAuthorized()) {
				return $this->module->getSubscriberByUserId(Service::Auth()->getUserId());
			}

			return $this->module->getSubscriberByMail($email);
		}

		/**
		 * Создает нового подписчика
		 * @param array $data данные подписчика
		 * @return bool|umiObject
		 * @throws coreException
		 * @throws publicException
		 */
		private function createSubscriber(array $data) {
			$event = new umiEventPoint('subscriber_create');
			$event->setMode('before');
			$event->addRef('data', $data);
			$event->call();

			$objectTypes = umiObjectTypesCollection::getInstance();
			$subscriberTypeId = $objectTypes->getTypeIdByHierarchyTypeName('dispatches', 'subscriber');
			$objects = umiObjectsCollection::getInstance();

			$subscriberId = $objects->addObject($data['email'], $subscriberTypeId);
			$subscriber = $objects->getObject($subscriberId);

			if (!$this->module->isSubscriber($subscriber)) {
				throw new publicException(getLabel('error-cant-create-subscriber'));
			}

			$subscriber->setValue('subscribe_date', new umiDate());
			$this->updateSubscriber($subscriber, $data);

			$event->setParam('subscriber', $subscriber);
			$event->setMode('after');
			$event->call();

			return $subscriber;
		}

		/**
		 * Возвращает список рассылок для подписки
		 * @param array $dispatches список запрошенных рассылок
		 * @return array
		 * @throws selectorException
		 */
		private function getActualDispatches($dispatches) {
			$result = [];

			$allDispatches = $this->getDispatchesList($this->module->getAllDispatches());
			if (!is_array($dispatches) || umiCount($dispatches) === 0) {
				return $allDispatches;
			}

			foreach ($dispatches as $dispatchId) {
				$dispatch = umiObjectsCollection::getInstance()->getObject($dispatchId);
				if (!$this->module->isDispatch($dispatch)) {
					continue;
				}
				$result[] = $dispatchId;
			}

			sort($result);

			return (umiCount($result) > 0 ? $result : $allDispatches);
		}

		/**
		 * Возвращает список ID действительных рассылок
		 * @param array $dispatches список объектов рассылок
		 * @return array
		 */
		private function getDispatchesList($dispatches) {
			$list = [];

			if (!is_array($dispatches) || umiCount($dispatches) === 0) {
				return $list;
			}

			/** @var iUmiObject $dispatch */
			foreach ($dispatches as $dispatch) {
				if (!$this->module->isDispatch($dispatch)) {
					continue;
				}

				$list[] = $dispatch->getId();
			}
			return $list;
		}

		/**
		 * Обновляет данные объекта подписчика
		 * @param iUmiObject $subscriber объект подписчика
		 * @param array $data новые данные подписчика
		 * @throws coreException
		 */
		private function updateSubscriber(iUmiObject $subscriber, array $data) {
			$event = new umiEventPoint('subscriber_update');
			$event->setMode('before');
			$event->addRef('data', $data);
			$event->setParam('subscriber', $subscriber);
			$event->call();

			/** @var iUmiObject $subscriber */
			$subscriber->setName($data['email']);
			$subscriber->setValue('fname', $data['name']);
			$subscriber->setValue('lname', $data['lastName']);
			$subscriber->setValue('father_name', $data['surname']);
			$subscriber->setValue('gender', $data['gender']);

			if (Service::Auth()->isAuthorized()) {
				$subscriber->setValue('uid', Service::Auth()->getUserId());
			}

			$subscriber->commit();

			$event->setMode('after');
			$event->call();
		}

		/**
		 * Отправляет письмо подписчику с информацией о подписке
		 * @param iUmiObject $subscriber объект подписчика
		 * @param string $subscriberEmail e-mail подписчика
		 * @param string $template имя шаблона письма
		 * @throws coreException
		 * @throws publicException
		 * @throws ErrorException
		 * @throws Exception
		 */
		private function sendSubscribingLetter(iUmiObject $subscriber, $subscriberEmail, $template = 'default') {
			$variables = [
				'domain' => Service::DomainDetector()->detectHost(),
				'unsubscribe_link' => $this->module->getUnSubscribeLink($subscriber, $subscriberEmail),
			];
			$objectList = [$subscriber];

			$subject = null;
			$content = null;

			if ($this->module->isUsingUmiNotifications()) {
				$mailNotifications = Service::MailNotifications();
				$notification = $mailNotifications->getCurrentByName('notification-dispatches-subscribe');

				if ($notification instanceof MailNotification) {
					$subjectTemplate = $notification->getTemplateByName('dispatches-subscribe-subject');
					$contentTemplate = $notification->getTemplateByName('dispatches-subscribe-content');

					if ($subjectTemplate instanceof MailTemplate) {
						$subject = $subjectTemplate->parse($variables, $objectList);
					}

					if ($contentTemplate instanceof MailTemplate) {
						$content = $contentTemplate->parse($variables, $objectList);
					}
				}
			} else {
				try {
					list($contentTemplate, $subjectTemplate) = dispatches::loadTemplatesForMail(
						'dispatches/' . $template,
						'subscribe_confirm',
						'subscribe_confirm_subject'
					);
					$subject = dispatches::parseTemplateForMail($subjectTemplate, $variables);
					$content = dispatches::parseTemplateForMail($contentTemplate, $variables);
				} catch (Exception $e) {
					// nothing
				}
			}

			if ($subject === null || $content === null) {
				return;
			}

			$mailSettings = $this->module->getMailSettings();
			$nameFrom = $mailSettings->getSenderName();
			$emailFrom = $mailSettings->getSenderEmail();

			$mail = new umiMail();
			$mail->addRecipient($subscriberEmail);
			$mail->setFrom($emailFrom, $nameFrom);
			$mail->setSubject($subject);
			$mail->setContent($content);
			$mail->commit();
			$mail->send();
		}

		/**
		 * Подписывает подписчика на рассылки
		 * @param iUmiObject $subscriber объект подписчика
		 * @param array $dispatches список ID рассылок
		 * @throws coreException
		 */
		private function subscribeDispatches(iUmiObject $subscriber, array $dispatches) {
			$event = new umiEventPoint('subscribe');
			$event->setMode('before');
			$event->addRef('dispatches', $dispatches);
			$event->setParam('subscriber', $subscriber);
			$event->call();

			/** @var iUmiObject $subscriber */
			$existingDispatches = $subscriber->getValue('subscriber_dispatches');
			$existingDispatches = array_map('intval', $existingDispatches);
			$newDispatches = array_unique(array_merge($existingDispatches, $dispatches));

			$subscriber->setValue('subscriber_dispatches', $newDispatches);
			$subscriber->commit();

			$event->setMode('after');
			$event->call();
		}
	}
