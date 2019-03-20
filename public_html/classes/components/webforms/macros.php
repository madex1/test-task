<?php

	use UmiCms\Service;

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class WebFormsMacros {

		/** @var webforms|WebFormsMacros $module */
		public $module;

		/**
		 * Возвращает адрес редактирования типа данных и адрес добавления дочернего типа данных
		 * @param int $typeId идентификатор типа данных
		 * @return array
		 */
		public function getObjectTypeEditLink($typeId) {
			return [
				'create-link' => $this->module->pre_lang . '/admin/webforms/form_add/',
				'edit-link' => $this->module->pre_lang . '/admin/webforms/form_edit/' . $typeId . '/'
			];
		}

		/**
		 * Возвращает содержимое страницы по умолчанию
		 * @return mixed
		 */
		public function page() {
			$cmsController = cmsController::getInstance();

			/** @var content|ContentMacros $content */
			$content = $cmsController->getModule('content');
			if (!$content instanceof def_module) {
				return false;
			}

			return $content->content($cmsController->getCurrentElementId());
		}

		/**
		 * Отправляет письмо
		 * @param iUmiObject $message сообщение формы
		 * @param array $recipientList получатели письма
		 * @param array $data отправляемые данные (@see webforms::formatMessage())
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 * @throws ErrorException
		 * @throws publicException
		 */
		public function sendMail(iUmiObject $message, array $recipientList, array $data) {
			$mail = new umiMail();
			$this->addFiles($mail, $message);
			$this->addRecipients($mail, $recipientList);

			if (!$mail->hasRecipients()) {
				$this->module->reportError(getLabel('error-no_recipients'));
			}

			$mail->setFrom($data['from_email_template'], $data['from_template']);
			$mail->setSubject($data['subject_template']);
			$mail->setContent($data['master_template']);
			$mail->commit();
			$mail->send();
		}

		/**
		 * Добавляет файлы в письмо из отправленного пользователем сообщения
		 * @param iUmiMail $mail письмо
		 * @param iUmiObject $message сообщение
		 * @throws coreException
		 */
		private function addFiles(iUmiMail $mail, iUmiObject $message) {
			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$fileTypes = ['file', 'img_file', 'swf_file'];
			$fieldList = $umiObjectTypes->getType($message->getTypeId())
				->getAllFields();

			/** @var iUmiField $field */
			foreach ($fieldList as $field) {
				$fieldType = $field->getFieldType();
				if (!in_array($fieldType->getDataType(), $fileTypes)) {
					continue;
				}

				$file = $message->getValue($field->getName());
				if ($file instanceof umiFile) {
					$mail->attachFile($file);
				}
			}
		}

		/**
		 * Добавляет в письмо получателей.
		 * @param iUmiMail $mail письмо
		 * @param array $recipientList список получателей
		 */
		private function addRecipients(iUmiMail $mail, array $recipientList) {
			foreach ($recipientList as $recipient) {
				$this->addRecipient($mail, $recipient);
			}
		}

		/**
		 * Добавляет в письмо получателя.
		 * Один получатель может содержать в себе несколько email-адресов.
		 * @param iUmiMail $mail письмо
		 * @param array $recipient получатель
		 */
		private function addRecipient(iUmiMail $mail, $recipient) {
			foreach (explode(',', $recipient['email']) as $address) {
				$address = trim($address);
				if ($address) {
					$mail->addRecipient($address, $recipient['name']);
				}
			}
		}

		/**
		 * Отправляет автоматическое уведомление
		 * @param array $data отправляемые данные (@see webforms::formatMessage())
		 * @throws coreException
		 * @throws ErrorException
		 * @throws publicException
		 */
		public function sendAutoReply(array $data) {
			$mail = new umiMail();

			if (isset($data['autoreply_email_recipient']) && $data['autoreply_email_recipient']) {
				$mail->addRecipient($data['autoreply_email_recipient']);
			} else {
				$mail->addRecipient($data['from_email_template'], $data['from_template']);
			}

			$mail->setFrom($data['autoreply_from_email_template'], $data['autoreply_from_template']);
			$mail->setSubject($data['autoreply_subject_template']);
			$mail->setContent($data['autoreply_template']);
			$mail->commit();
			$mail->send();
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
		 * Завершает отправку сообщения формы.
		 * Совершает редирект на referrer
		 * или выводит сообщение об успехе.
		 * @return string
		 * @throws coreException
		 */
		public function finishSending() {
			$redirectUrl = getRequest('ref_onsuccess');
			if ($redirectUrl) {
				$this->module->redirect($redirectUrl);
			}

			$templateName = getRequest('system_template');
			if ($templateName) {
				list($successMessage) = webforms::loadTemplates(
					"data/reflection/{$templateName}",
					'send_successed'
				);

				if ($successMessage) {
					return $successMessage;
				}
			}

			if (isset($_SERVER['HTTP_REFERER'])) {
				$this->module->redirect($_SERVER['HTTP_REFERER']);
			}

			return '';
		}

		/**
		 * Возвращает данные для создания формы обратной связи
		 * TODO рефакторинг
		 * @param bool|int|string $formId идентификатор или имя формы
		 * @param string|int $who e-mail адресата или идентификатор адреса
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function add($formId = false, $who = '', $template = 'webforms') {
			$paramList = [];
			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$baseTypeId = $umiObjectTypes->getTypeIdByHierarchyTypeName('webforms', 'form');

			if (is_numeric($formId)) {
				if ($umiObjectTypes->getParentTypeId($formId) != $baseTypeId) {
					$formId = false;
				}
			}

			if (!is_numeric($formId) || $formId === false) {
				$children = $umiObjectTypes->getChildTypeIds($baseTypeId);

				if (empty($children)) {
					list($template) = webforms::loadTemplates(
						'data/reflection/' . $template,
						'error_no_form'
					);
					return $template;
				}

				$i = 0;
				do {
					$form = $umiObjectTypes->getType($children[$i]);
					$i++;
				} while (
					$i < umiCount($children) && $form->getName() != $formId
				);

				$formId = $form->getId();
			}

			$paramList['attribute:form_id'] = $formId;
			$paramList['attribute:template'] = $template;
			$who = (string) $who;

			if ($who !== '') {
				$addressId = $this->module->guessAddressId($who);

				if (func_num_args() > 3) {
					$extraAddressList = array_slice(func_get_args(), 3);

					foreach ($extraAddressList as &$address) {
						$address = $this->module->guessAddressId($address);
					}

					$extraAddressList = array_merge([$addressId], $extraAddressList);
					$paramList['res_to'] = $paramList['address_select'] = $this->writeSeparateAddresses($extraAddressList, $template);
				} else {
					$paramList['res_to'] =
					$paramList['address_select'] = '<input type="hidden" name="system_email_to" value="' . $addressId . '" />';
				}
			} else {
				$addressList = $this->writeAddressSelect($template, $formId);

				if (is_array($addressList)) {
					if (isset($addressList['items'])) {
						$paramList['items'] = $addressList['items'];
					} else {
						return;
					}
				} else {
					if (is_string($addressList) && $addressList !== '') {
						$paramList['address_select'] = $addressList;
					} else {
						return;
					}
				}
			}

			/** @var data|DataForms $dataModule */
			$dataModule = cmsController::getInstance()->getModule('data');
			$paramList['groups'] = $dataModule->getCreateForm($formId, $template);

			list($formBlock) = webforms::loadTemplates('data/reflection/' . $template, 'form_block');
			return webforms::parseTemplate($formBlock, $paramList);
		}

		/**
		 * Возвращает список адресов
		 * @param string $template имя шаблона (для tpl)
		 * @param bool $formId выбранная форма
		 * @return mixed
		 * @throws selectorException
		 * @throws coreException
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function writeAddressSelect($template, $formId = false) {
			list($block, $line) = webforms::loadTemplates(
				'data/reflection/' . $template,
				'address_select_block',
				'address_select_block_line'
			);

			$addressList = new selector('objects');
			$addressList->types('object-type')->name('webforms', 'address');
			$addressList->option('no-length')->value(true);
			$addressList->option('load-all-props')->value(true);
			$addressList = $addressList->result();
			$itemList = [];

			foreach ($addressList as $address) {
				if (!$address instanceof iUmiObject) {
					continue;
				}

				$title = $address->getValue('address_description');
				$paramList = [];
				$addressId = $address->getId();
				$paramList['attribute:id'] = $addressId;
				$paramList['node:text'] = $title;

				$formIdList = $address->getValue('form_id');
				if ($formId && $formIdList && in_array($formId, explode(',', $formIdList))) {
					$paramList['attribute:selected'] = 'selected';
				}

				$itemList[] = webforms::parseTemplate($line, $paramList, false, $addressId);
			}

			$blockList = [];
			$blockList['void:options'] = $blockList['subnodes:items'] = $itemList;
			return webforms::parseTemplate($block, $blockList);
		}

		/**
		 * Возвращает список адресов указанных идентификаторов
		 * @param array $addressIdList список идентификаторов адресов
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function writeSeparateAddresses($addressIdList, $template) {
			list($block, $line) = webforms::loadTemplates(
				'data/reflection/' . $template,
				'address_separate_block',
				'address_separate_block_line'
			);

			$umiObjects = umiObjectsCollection::getInstance();
			$itemList = [];

			foreach ($addressIdList as $id) {
				$address = $umiObjects->getObject($id);
				if (!$address) {
					continue;
				}

				$itemParams = [];
				$itemParams['id'] = 'address_' . $id;
				$itemParams['name'] = 'system_email_to[]';
				$itemParams['value'] = $id;
				$itemParams['description'] = $address->getValue('address_description');

				$itemList[] = webforms::parseTemplate($line, $itemParams);
			}

			$blockParams = [];
			$blockParams['void:lines'] = $blockParams['subnodes:items'] = $itemList;
			return webforms::parseTemplate($block, $blockParams);
		}

		/**
		 * Возвращает сообщение об успешном отправлении письма формы
		 * @param bool|int|string $template имя шаблона (для tpl) или идентификатор шаблона письма
		 * @return string
		 * @throws selectorException
		 * @throws coreException
		 * @throws ErrorException
		 */
		public function posted($template = false) {
			$template = $template ?: (string) getRequest('template');
			$template = $template ?: (string) getRequest('param0');
			$result = false;

			if ($template && is_numeric($template)) {
				$sel = new selector('objects');
				$sel->types('object-type')->name('webforms', 'template');
				$sel->where('form_id')->equals($template);
				$sel->limit(0, 1);

				if ($sel->first()) {
					/** @var iUmiObject $templateObject */
					$templateObject = $sel->first();
					$result = $templateObject->getValue('posted_message');
				}

				if (!$result && !webforms::isXSLTResultMode()) {
					try {
						list($result) = webforms::loadTemplates("./tpls/webforms/{$template}", 'posted');
					} catch (publicException $e) {
						// nothing
					}
				}
			}

			if (!$result) {
				$result = '%webforms_thank_you%';

				if (isDemoMode()) {
					$result = '%webforms_thank_you_demo%';
				}
			}

			return def_module::parseTPLMacroses($result);
		}

		/**
		 * Клиентский метод.
		 * Валидирует данные формы, создает объект сообщения и отправляет
		 * на адрес формы почтовое сообщение, сформированное по шаблону.
		 * При необходимости отправляет автоматическое почтовое уведомление.
		 *
		 * После выполнения перенаправляет на referrer.
		 *
		 * @return mixed
		 * @throws Exception
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function send() {
			$formId = getRequest('system_form_id');
			$this->validateForm($formId);

			$addressIdList = (array) getRequest('system_email_to');
			$recipientList = $this->getRecipientList($addressIdList);
			$recipient = $this->getLastRecipient($recipientList);
			$message = $this->createMessage($formId, $recipient);
			$data = $this->module->formatMessage($message->getId(), true);

			$this->sendMail($message, $recipientList, $data);

			if ($data['autoreply_template']) {
				$this->sendAutoReply($data);
			}

			$this->triggerSendEvent($message->getId(), $formId, $data);
			return $this->finishSending();
		}

		/**
		 * Валидирует данные формы.
		 * @param string $formId Идентификатор формы (типа данных)
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws privateException
		 * @throws ErrorException
		 * @throws publicException
		 */
		private function validateForm($formId) {
			try {
				$this->validateCaptcha();
				$this->validateDemoMode();
				$this->validateFormType($formId);
				$this->validateFormFields($formId);
			} catch (errorPanicException $e) {
				throw new publicException($e->getMessage());
			}
		}

		/**
		 * Проверяет правильность заполнения капчи
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 * @throws ErrorException
		 */
		private function validateCaptcha() {
			if (!umiCaptcha::checkCaptcha()) {
				$this->module->errorNewMessage('%errors_wrong_captcha%', true, false, 'captcha');
			}
		}

		/**
		 * Если система работает в демо-режиме, метод прерывает запрос
		 * и перенаправляет на страницу успешного отправления сообщения.
		 * @throws coreException
		 */
		private function validateDemoMode() {
			if (isDemoMode()) {
				$url = getRequest('ref_onsuccess');
				$url = $url ?: $this->module->pre_lang . '/webforms/posted/';
				$this->module->redirect($url);
			}
		}

		/**
		 * Проверяет правильность типа формы
		 * @param string $formId Идентификатор формы (типа данных)
		 * @throws databaseException
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 * @throws ErrorException
		 */
		private function validateFormType($formId) {
			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$baseTypeId = $umiObjectTypes->getTypeIdByHierarchyTypeName('webforms', 'form');
			if ($umiObjectTypes->getParentTypeId($formId) != $baseTypeId) {
				$this->reportError('%wrong_form_type%');
			}
		}

		/**
		 * Проверяет правильность заполнения полей формы
		 * @param string $formId Идентификатор формы (типа данных)
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws errorPanicException
		 * @throws privateException
		 * @throws ErrorException
		 */
		private function validateFormFields($formId) {
			$errorFieldList = $this->module->checkRequiredFields($formId);
			if (is_array($errorFieldList)) {
				$this->reportError(
					getLabel('error-required_list') . $this->module->assembleErrorFields($errorFieldList),
					false
				);
			}
		}

		/**
		 * Возвращает список получателей сообщения.
		 * @param array $addressIdList список идентификаторов адресов
		 * @return array
		 * @throws coreException
		 */
		private function getRecipientList(array $addressIdList) {
			$recipientList = [];
			foreach ($addressIdList as $addressId) {
				$recipientList[] = [
					'name' => $this->module->guessAddressName($addressId),
					'email' => $this->module->guessAddressValue($addressId),
				];
			}
			return $recipientList;
		}

		/**
		 * Возвращает данные последнего получателя сообщения.
		 * @param array $recipientList список получателей
		 * @return array
		 * [
		 *     'name' => <name>,
		 *     'email' => <email>,
		 * ]
		 */
		private function getLastRecipient(array $recipientList) {
			return end($recipientList) ?: ['name' => null, 'email' => null];
		}

		/**
		 * Создает сообщение и возвращает его.
		 * @param string $formId идентификатор формы (типа данных)
		 * @param array $recipient данные получателя
		 * @return iUmiObject
		 * @throws Exception
		 * @throws coreException
		 * @throws privateException
		 * @throws errorPanicException
		 */
		private function createMessage($formId, $recipient) {
			$umiObjects = umiObjectsCollection::getInstance();

			$messageId = $umiObjects->addObject($recipient['name'], $formId);
			$message = $umiObjects->getObject($messageId);

			$message->setOwnerId(Service::Auth()->getUserId());

			$senderIp = Service::Request()->remoteAddress();
			$_REQUEST['data']['new']['sender_ip'] = $senderIp;

			/** @var data|\UmiCms\Classes\Components\Data\FormSaver $data */
			$data = cmsController::getInstance()->getModule('data');
			$objectData = $data->prepareEditedObjectRequestData($messageId, true);

			/** @var umiEventPoint $eventPoint */
			$eventPoint = Service::EventPointFactory()
				->create('webforms_create_message', 'before');

			try {
				webforms::$noRedirectOnPanic = true;
				$eventPoint->setParam('field_list', $objectData);
				$eventPoint->call();

				$objectData = $eventPoint->getParam('field_list');
				$data->saveEditedObjectData($messageId, $objectData, true, false);
				webforms::$noRedirectOnPanic = false;
			} catch (errorPanicException $e) {
				webforms::$noRedirectOnPanic = false;
				$this->reportError($e->getMessage());
			}

			$message = $umiObjects->getObject($messageId);
			$message->setValue('destination_address', $recipient['email']);
			$message->setValue('sender_ip', $senderIp);
			$message->setValue('sending_time', new umiDate(time()));
			$message->commit();

			$eventPoint->setMode('after');
			$eventPoint->setParam('messageId', $messageId);
			$eventPoint->call();

			return $message;
		}

		/**
		 * Вызывает событие отправки сообщения.
		 * @param int $messageId Идентификатор сообщения
		 * @param string $formId Идентификатор формы (типа данных)
		 * @param array $data отправленные данные (@see webforms::formatMessage())
		 * @throws coreException
		 * @throws baseException
		 */
		private function triggerSendEvent($messageId, $formId, array $data) {
			$event = new umiEventPoint('webforms_post');
			$event->setMode('after');
			$event->setParam('email', $data['from_email_template']);
			$event->setParam('message_id', $messageId);
			$event->setParam('form_id', $formId);
			$event->setParam('fio', $data['from_template']);
			webforms::setEventPoint($event);
		}

		/**
		 * Форматирует сообщение согласно шаблону письма
		 * @param iUmiObject $template Шаблон письма
		 * @param iUmiObject $message Сообщение
		 * @param bool $shouldUseAllFields Определяет, нужно ли использовать все поля шаблона.
		 * Если да - метод возвращает массив [<поле шаблона> => <значение>].
		 * Если нет - метод возвращает тело сообщения в виде строки.
		 * @return array|string
		 * @throws coreException
		 */
		public function formatMessageByTemplate(
			iUmiObject $template,
			iUmiObject $message,
			$shouldUseAllFields
		) {
			if (!$shouldUseAllFields) {
				return $this->formatMessageValue($template->getValue('master_template'), $message);
			}

			$messageBody = [];
			$fieldList = umiObjectTypesCollection::getInstance()
				->getType($template->getTypeId())
				->getAllFields();

			foreach ($fieldList as $field) {
				$fieldName = $field->getName();
				$fieldValue = $template->getValue($fieldName);
				$messageBody[$fieldName] = $this->formatMessageValue($fieldValue, $message);
			}

			return $messageBody;
		}

		/**
		 * Форматирует значение поля сообщения согласно шаблону письма
		 * @param string $value Значение поля шаблона
		 * @param iUmiObject $message Сообщение
		 * @return string
		 */
		public function formatMessageValue($value, $message) {
			$value = str_replace(['&#037;', '&#37;'], '%', $value);
			preg_match_all('/%[-A-z0-9_]+%/', $value, $matches);

			foreach ($matches[0] as $match) {
				$value = str_replace(
					$match,
					$this->getPropertyValue($message, trim($match, '% ')),
					$value
				);
			}

			return $value;
		}

		/**
		 * Возвращает значение поля сообщения
		 * @param iUmiObject $message Сообщение
		 * @param string $name названия поля
		 * @return mixed
		 */
		public function getPropertyValue(iUmiObject $message, $name) {
			if ($name === 'id') {
				return $message->getId();
			}

			$property = $message->getPropByName($name);
			if (!$property instanceof iUmiObjectProperty) {
				return '';
			}

			switch ($property->getDataType()) {
				case 'date' : {
					$date = $property->getValue();
					return ($date instanceof iUmiDate) ? $date->getFormattedDate() : '';
				}

				case 'relation': {
					$result = [];
					$idList = (array) $property->getValue();
					$umiObjects = umiObjectsCollection::getInstance();

					foreach ($idList as $id) {
						$value = $umiObjects->getObject($id);

						if ($value instanceof iUmiObject) {
							$result[] = $value->getName();
						}
					}

					return isEmptyArray($result) ? '' : implode(', ', $result);
				}

				case 'boolean': {
					$langs = cmsController::getInstance()
						->getLangConstantList();
					$value = $property->getValue();
					return $value ? $langs['boolean_true'] : $langs['boolean_false'];
				}

				default: {
					return $property->getValue();
				}
			}
		}

		/**
		 * Формирует тело сообщения из полей сообщения напрямую, то есть без шаблона
		 * @param iUmiObject $message Сообщение
		 * @param bool $shouldUseAllFields Определяет, нужно ли использовать все поля шаблона.
		 * Если да - метод возвращает массив [<поле шаблона> => <значение>].
		 * Если нет - метод возвращает тело сообщения в виде строки.
		 * @return array|string
		 * @throws coreException
		 */
		public function formatMessageByRawFieldValues(iUmiObject $message, $shouldUseAllFields) {
			$fieldList = umiObjectTypesCollection::getInstance()
				->getType($message->getTypeId())
				->getAllFields(/* onlyVisible = */ true);
			$messageBody = '';

			foreach ($fieldList as $field) {
				$messageBody .= $this->getPropertyValue($message, $field->getName()) . "<br />\n";
			}

			if ($shouldUseAllFields) {
				return [
					'from_email_template' => '',
					'from_template' => '',
					'subject_template' => '',
					'autoreply_from_email_template' => '',
					'autoreply_from_template' => '',
					'autoreply_subject_template' => '',
					'autoreply_template' => '',
					'master_template' => $messageBody
				];
			}

			return $messageBody;
		}
	}
