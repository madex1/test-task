<?php

	use UmiCms\Service;

	/** Класс функционала административной панели модуля */
	class DispatchesAdmin {

		use baseModuleAdmin;

		/** @var dispatches $module */
		public $module;

		/**
		 * Возвращает список рассылок
		 * @return bool|void
		 * @throws coreException
		 * @throws selectorException
		 */
		public function lists() {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$limit = getRequest('per_page_limit');
			$currentPage = (int) getRequest('p');
			$offset = $limit * $currentPage;

			$sel = new selector('objects');
			$sel->types('object-type')->name('dispatches', 'dispatch');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result(), 'objects');
			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Возвращает список подписчиков на рассылку
		 * @return bool|void
		 * @throws coreException
		 * @throws selectorException
		 */
		public function subscribers() {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$limit = getRequest('per_page_limit');
			$currentPage = (int) getRequest('p');
			$offset = $limit * $currentPage;

			$dispatchId = getRequest('param0') ?: getRequest('id');

			if (is_array($dispatchId)) {
				$dispatchId = isset($dispatchId[0]) ? $dispatchId[0] : null;
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('dispatches', 'subscriber');

			if ($dispatchId) {
				$sel->where('subscriber_dispatches')->equals($dispatchId);
			}

			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result(), 'objects');
			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Возвращает список выпусков рассылки
		 * @return bool
		 * @throws coreException
		 * @throws selectorException
		 */
		public function releases() {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$limit = getRequest('per_page_limit');
			$currentPage = (int) getRequest('p');
			$offset = $limit * $currentPage;

			$dispatchId = getRequest('param0') ?: getRequest('id');

			if (is_array($dispatchId)) {
				$dispatchId = isset($dispatchId[0]) ? $dispatchId[0] : null;
			}

			$sel = new selector('objects');
			$sel->types('object-type')->name('dispatches', 'release');

			if ($dispatchId) {
				$sel->where('disp_reference')->equals($dispatchId);
			}

			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result(), 'objects');
			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Возвращает список сообщений рассылки
		 * @throws coreException
		 */
		public function messages() {
			$dispatchId = getRequest('param0');
			$dispatch = umiObjectsCollection::getInstance()->getObject($dispatchId);
			$releaseId = false;

			if ($dispatch instanceof iUmiObject) {
				$releaseId = $this->module->getNewReleaseInstanceId($dispatchId);
			}

			$result = $this->module->getReleaseMessages($releaseId);
			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($result, 'objects');
			$this->setData($data, umiCount($result));
			$this->doData();
		}

		/**
		 * Возвращает данные для создания формы добавления рассылки,
		 * если передан $_REQUEST['param1'] = do, то создает рассылку
		 * и перенаправляет страницу, где ее можно отредактировать.
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws wrongElementTypeAdminException
		 */
		public function add() {
			$type = (string) getRequest('param0');
			$this->setHeaderLabel('header-dispatches-add-' . $type);
			$inputData = [
				'type' => $type
			];

			if ($this->isSaveMode('param1')) {
				$object = $this->saveAddedObjectData($inputData);
				$added = umiObjectsCollection::getInstance()->getObject($object->getId());
				$added->setValue('subscribe_date', time());
				$added->commit();
				$this->chooseRedirect($this->module->pre_lang . '/admin/dispatches/edit/' . $object->getId() . '/');
			}

			$this->setDataType('form');
			$this->setActionType('create');
			$data = $this->prepareData($inputData, 'object');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает данные для создания формы редактирования рассылки.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do,
		 * то сохраняет изменения рассылки и производит перенаправление.
		 * Адрес перенаправление зависит от режима кнопки "Сохранить".
		 * @throws coreException
		 * @throws expectObjectException
		 */
		public function edit() {
			$object = $this->expectObject('param0');
			$this->setHeaderLabel('header-dispatches-edit-' . $this->getObjectTypeMethod($object));

			if ($this->isSaveMode('param1')) {
				$this->saveEditedObjectData($object);
				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('modify');
			$data = $this->prepareData($object, 'object');
			$typeId = $object->getTypeId();

			$hierarchyTypeId = umiObjectTypesCollection::getInstance()->getType($typeId)->getHierarchyTypeId();
			$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);

			if ($hierarchyType->getExt() == 'dispatch') {
				$releaseId = $this->module->getNewReleaseInstanceId($object->getId());
				$messages = $this->module->getReleaseMessages($releaseId);
				$data['object']['release'] = [];
				$data['object']['release']['nodes:message'] = $messages;
			}

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Изменяет активность рассылок
		 * @throws coreException
		 * @throws expectObjectException
		 */
		public function activity() {
			$this->changeActivityForObjects();
		}

		/**
		 * Возвращает данные для создания формы добавления сообщения рассылки,
		 * если передан $_REQUEST['param1'] = do, то создает сообщение
		 * и перенаправляет страницу, где его можно отредактировать.
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws wrongElementTypeAdminException
		 */
		public function add_message() {
			$type = 'message';
			$dispatchId = (int) getRequest('param0');
			$inputData = [
				'type' => $type
			];

			if ($this->isSaveMode('param1')) {
				$object = $this->saveAddedObjectData($inputData);
				$object->setValue('release_reference', $this->module->getNewReleaseInstanceId($dispatchId));
				$this->chooseRedirect($this->module->pre_lang . '/admin/dispatches/edit/' . $object->getId() . '/');
			}

			$this->setDataType('form');
			$this->setActionType('create');
			$data = $this->prepareData($inputData, 'object');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Удаляет список объектов модуля
		 * @throws coreException
		 * @throws expectObjectException
		 * @throws wrongElementTypeAdminException
		 * @throws \UmiCms\System\Protection\CsrfException
		 * @throws publicAdminException
		 */
		public function del() {
			$objects = getRequest('element');

			if (!is_array($objects)) {
				$objects = [$objects];
			}

			if (getRequest('param0')) {
				$objectId = getRequest('param0');
				$object = $this->expectObject($objectId, false, true);

				$params = [
					'object' => $object,
					'allowed-element-types' => [
						'dispatch',
						'subscriber',
						'release',
						'message'
					]
				];

				$this->deleteObject($params);
				$this->chooseRedirect();
			}

			foreach ($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);

				$params = [
					'object' => $object,
					'allowed-element-types' => [
						'dispatch',
						'subscriber',
						'release',
						'message'
					]
				];

				$this->deleteObject($params);
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($objects, 'objects');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Отправляет выпуск рассылки части подписчиков.
		 * Используется итерационно.
		 * @throws ErrorException
		 * @throws coreException
		 * @throws privateException
		 * @throws publicException
		 * @throws selectorException
		 * @throws Exception
		 */
		public function release_send() {
			$umiObjects = umiObjectsCollection::getInstance();

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->charset('utf-8');
			$buffer->contentType('text/xml');
			$buffer->push('<?xml version="1.0" encoding="utf-8"?>');

			$dispatchId = (int) getRequest('param0');
			$releaseId = $this->module->getNewReleaseInstanceId($dispatchId);

			$dispatch = $umiObjects->getObject($dispatchId);
			$release = $umiObjects->getObject($releaseId);

			if (!$dispatch instanceof iUmiObject || !$release instanceof iUmiObject) {
				$buffer->push('<error>' . getLabel('error-empty-dispatch-id') . '</error>');
				$buffer->end();
			}

			if ($release->getValue('status')) {
				$buffer->push('<error>' . getLabel('error-already-released') . '</error>');
				$buffer->end();
			}

			$recipientIds = [];
			$progressKey = 'umi_send_list_' . $releaseId;
			$progressCountKey = $progressKey . '_count';
			$progressDelayKey = $progressKey . '_delay';
			$session = Service::Session();

			if ($session->isExist($progressKey)) {
				$recipientIds = $session->get($progressKey);
			} else {
				$sel = new selector('objects');
				$sel->types('hierarchy-type')->name('dispatches', 'subscriber');
				$sel->where('subscriber_dispatches')->equals($dispatchId);
				$sel->option('return')->value('id');
				$sel->group('name');

				foreach ($sel->result() as $info) {
					$recipientIds[] = $info['id'];
				}

				$session->set($progressKey, $recipientIds);
				$session->set($progressCountKey, umiCount($recipientIds));
			}

			$delay = $session->get($progressDelayKey);
			$total = (int) $session->get($progressCountKey);

			if ($delay and time() < $delay) {
				$numberSent = $total - umiCount($recipientIds);
				$result = <<<END
<release dispatch="{$dispatchId}">
	<total>{$total}</total>
	<sended>{$numberSent}</sended>
</release>
END;
				$buffer->push($result);
				$buffer->end();
			}

			$mailer = new umiMail();
			$contentVariables = [
				'header' => $dispatch->getName(),
				'+messages' => [],
			];

			$contentTemplate = null;
			$messageTemplate = null;

			if (!$this->module->isUsingUmiNotifications()) {
				try {
					list($contentTemplate, $messageTemplate) = dispatches::loadTemplatesForMail(
						'dispatches/release',
						'release_body',
						'release_message'
					);
				} catch (Exception $e) {
					// nothing
				}
			}

			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('dispatches', 'message');
			$sel->where('release_reference')->equals($releaseId);

			if ($sel->length()) {
				foreach ($sel->result() as $message) {
					if ($message instanceof iUmiObject) {
						$messageVariables = [
							'body' => $message->getValue('body'),
							'header' => $message->getValue('header'),
							'id' => $message->getId()
						];

						if ($this->module->isUsingUmiNotifications()) {
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

						if ($attachment instanceof umiFile && !$attachment->getIsBroken()) {
							$mailer->attachFile($attachment);
						}
					}
				}
			} else {
				$session->del($dispatchId . '_new_templater');
				$buffer->push('<error>' . getLabel('label-release-empty') . '</error>');
				$buffer->end();
			}

			$mailSettings = $this->module->getMailSettings();
			$mailer->setFrom($mailSettings->getSenderEmail(), $mailSettings->getSenderName());
			$delay = 0;
			$maxMessages = (int) mainConfiguration::getInstance()->get('modules', 'dispatches.max_messages_in_hour');

			if ($maxMessages && $total >= $maxMessages) {
				$delay = floor(3600 / $maxMessages);
			}

			$recipientIdsSentTo = [];
			$packetSize = 5;

			foreach ($recipientIds as $recipientId) {
				$packetSize -= 1;
				$nextMailer = clone $mailer;

				$recipient = $umiObjects->getObject($recipientId);
				$subscriber = new umiSubscriber($recipient->getId());

				if ($subscriber->releaseWasSent($releaseId)) {
					continue;
				}

				$recipientName = $subscriber->getFullName();
				$email = $subscriber->getEmail();

				$contentVariables['unsubscribe_link'] = $this->module->getUnSubscribeLink($recipient, $email);
				$objectList = [$dispatch, $recipient];

				$subject = null;
				$content = null;

				$mailNotifications = Service::MailNotifications();
				$notification = $mailNotifications->getCurrentByName('notification-dispatches-release');

				if ($notification instanceof MailNotification && $this->module->isUsingUmiNotifications()) {
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

				if (!isDemoMode()) {
					$nextMailer->commit();
					$nextMailer->send();
					$subscriber->putReleaseToSentList($releaseId);
					$subscriber->commit();
				}

				$recipientIdsSentTo[] = $recipientId;
				unset($nextMailer);

				if ($packetSize === 0) {
					break;
				}

				$umiObjects->unloadObject($recipientId);

				if ($delay) {
					$session->set($progressDelayKey, $delay + time());
					$session->set($progressKey, array_diff($recipientIds, $recipientIdsSentTo));
					$total = (int) $session->get($progressCountKey);

					$numberSent = $total - (umiCount($recipientIds) - umiCount($recipientIdsSentTo));

					$result = <<<END
<release dispatch="{$dispatchId}">
	<total>{$total}</total>
	<sended>{$numberSent}</sended>
</release>
END;
					$buffer->push($result);
					$buffer->end();
				}
			}

			umiMail::clearFilesCache();
			$session->set($progressKey, array_diff($recipientIds, $recipientIdsSentTo));

			if (!umiCount($session->get($progressKey))) {
				$date = new umiDate(time());
				$release->setValue('date', $date);
				$release->setName(sprintf('%s: %s', $dispatch->getName(), $date->getFormattedDate('d-m-Y H:i')));
				$release->setValue('status', true);
				$release->commit();

				$dispatch->setValue('disp_last_release', $date);
				$dispatch->commit();
			}

			$total = (int) $session->get($progressCountKey);
			$numberSent = $total - (umiCount($recipientIds) - umiCount($recipientIdsSentTo));

			$result = <<<END
<release dispatch="{$dispatchId}">
	<total>{$total}</total>
	<sended>{$numberSent}</sended>
</release>
END;

			$session->del($dispatchId . '_new_templater');
			$buffer->push($result);
			$buffer->end();
		}

		/**
		 * Возвращает настройки модуля.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то сохраняет настройки.
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws requireAdminParamException
		 * @throws wrongParamException
		 */
		public function config() {
			$umiRegistry = Service::Registry();
			$umiNotificationInstalled = cmsController::getInstance()
				->isModule('umiNotifications');
			$params = [];

			if ($umiNotificationInstalled) {
				$params['config']['boolean:use-umiNotifications'] = null;
			}

			if ($this->isSaveMode()) {
				$params = $this->expectParams($params);

				if ($umiNotificationInstalled) {
					$umiRegistry->set('//modules/dispatches/use-umiNotifications', $params['config']['boolean:use-umiNotifications']);
				}

				$this->chooseRedirect();
			}

			if ($umiNotificationInstalled) {
				$params['config']['boolean:use-umiNotifications'] =
					(bool) $umiRegistry->get('//modules/dispatches/use-umiNotifications');
			}

			$this->setConfigResult($params);
		}

		/**
		 * Возвращает настройки для формирования табличного контрола
		 * @param string $param контрольный параметр
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			switch ($param) {
				case 'lists': {
					$loadMethod = 'lists';
					$method = 'dispatch';
					$defaults = 'name[400px]|disp_description[250px]|disp_last_release[250px]';
					break;
				}

				case 'subscribers' : {
					$loadMethod = 'subscribers';
					$method = 'subscriber';
					$defaults = 'name[400px]|subscriber_dispatches[250px]';
					break;
				}

				case 'releases' : {
					$loadMethod = 'releases';
					$method = 'release';
					$defaults = 'name[400px]|date[250px]';
					break;
				}

				default: {
					$loadMethod = 'messages';
					$method = 'message';
					$defaults = 'name[400px]|msg_date[250px]';
				}
			}

			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$typeId = $umiObjectTypes->getTypeIdByHierarchyTypeName('dispatches', $method);

			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'dispatches',
						'#__name' => $loadMethod
					],
					[
						'title' => getLabel('smc-delete'),
						'module' => 'dispatches',
						'#__name' => 'del',
						'aliases' => 'tree_delete_element,delete,del'
					],
					[
						'title' => getLabel('smc-activity'),
						'module' => 'dispatches',
						'#__name' => 'activity',
						'aliases' => 'tree_set_activity,activity'
					],
				],
				'types' => [
					[
						'common' => 'true',
						'id' => $typeId
					]
				],
				'stoplist' => [
					'disp_reference',
					'new_relation',
					'release_reference'
				],
				'default' => $defaults
			];
		}
	}
