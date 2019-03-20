<?php

	use UmiCms\Service;

	/** Класс уведомлений */
	class AppointmentNotifier {

		/** @var appointment $module */
		public $module;

		/**
		 * Отправляет почтовое уведомление о новом заказе менеджеру
		 * @param AppointmentOrder $order заказ
		 * @return array
		 * @throws Exception
		 */
		public function sendNewAppointmentNotifyToManager(AppointmentOrder $order) {
			$mailSettings = $this->module->getMailSettings();
			$emailTo = $mailSettings->getAdminEmail();
			$noReply = $mailSettings->getSenderEmail();

			$variables = [
				'name' => $order->getName(),
				'phone' => $order->getPhone(),
				'email' => $order->getEmail(),
				'comment' => $order->getComment(),
				'date' => $this->getDate($order),
				'time' => $this->getTime($order),
				'service' => $this->getServiceName($order),
				'category' => $this->getServiceGroupName($order),
				'specialist' => $this->getEmployeeName($order)
			];

			$subject = getLabel('notify-default-header', 'appointment');
			$content = getLabel('notify-default-content-create', 'appointment');

			$user = $this->getCurrentUser();

			$mailNotifications = Service::MailNotifications();
			$notification = $mailNotifications->getCurrentByName('notification-new-record-admin');

			if ($notification instanceof MailNotification) {
				$subjectTemplate = $notification->getTemplateByName('new-record-admin-notify-subject');
				$contentTemplate = $notification->getTemplateByName('new-record-admin-notify-content');

				if ($subjectTemplate instanceof MailTemplate) {
					$subject = $subjectTemplate->parse($variables, [$user]);
				}

				if ($contentTemplate instanceof MailTemplate) {
					$content = $contentTemplate->parse($variables, [$user]);
				}
			}

			$mail = new umiMail();
			$mail->addRecipient($emailTo);
			$mail->setFrom($noReply, $order->getName());
			$mail->setSubject($subject);
			$mail->setContent($content);
			$mail->commit();
			$mail->send();

			return $this->mailAttributesAsArray($emailTo, $noReply, $order->getName(), $subject, $content);
		}

		/**
		 * Отправляет почтовое уведомление о новом заказе покупателю
		 * @param AppointmentOrder $order заказ
		 * @throws Exception
		 * @return array
		 */
		public function sendNewAppointmentNotifyToUser(AppointmentOrder $order) {
			$emailTo = $order->getEmail();
			$noReply = $this->module->getMailSettings()->getSenderEmail();

			$user = $this->getCurrentUser();

			$variables = [
				'date' => $this->getDate($order),
				'time' => $this->getTime($order),
				'service' => $this->getServiceName($order),
				'category' => $this->getServiceGroupName($order),
				'specialist' => $this->getEmployeeName($order)
			];

			$subject = getLabel('notify-default-header', 'appointment');
			$content = getLabel('notify-default-content-create', 'appointment');

			$mailNotifications = Service::MailNotifications();
			$notification = $mailNotifications->getCurrentByName('notification-new-record-user');

			if ($notification instanceof MailNotification) {
				$subjectTemplate = $notification->getTemplateByName('new-record-user-notify-subject');

				if ($subjectTemplate instanceof MailTemplate) {
					$subject = $subjectTemplate->parse($variables, [$user]);
				}

				$contentTemplate = $notification->getTemplateByName('new-record-user-notify-content');

				if ($contentTemplate instanceof MailTemplate) {
					$content = $contentTemplate->parse($variables, [$user]);
				}
			}

			$mail = new umiMail();
			$mail->addRecipient($emailTo);
			$mail->setFrom($noReply, getLabel('notify-default-header', 'appointment'));
			$mail->setSubject($subject);
			$mail->setContent($content);
			$mail->commit();
			$mail->send();

			return $this->mailAttributesAsArray(
				$emailTo, $noReply, getLabel('notify-default-header', 'appointment'), $subject, $content
			);
		}

		/**
		 * Отправляет почтовое уведомление о изменении в заказе покупателю
		 * @param AppointmentOrder $order заказ
		 * @throws Exception
		 * @return array
		 */
		public function sendChangeAppointmentNotifyToUser(AppointmentOrder $order) {
			$emailTo = $order->getEmail();
			$noReply = $this->module->getMailSettings()->getSenderEmail();

			$user = $this->getCurrentUser();

			$variables = [
				'category' => $this->getServiceGroupName($order),
				'service' => $this->getServiceName($order),
				'date' => $this->getDate($order),
				'time' => $this->getTime($order),
				'specialist' => $this->getEmployeeName($order),
				'new-status' => $this->getStatusName($order)
			];

			$subject = getLabel('notify-default-header', 'appointment');
			$content = getLabel('notify-default-content-modify', 'appointment');

			$mailNotifications = Service::MailNotifications();
			$notification = $mailNotifications->getCurrentByName('notification-record-status-changed-user');

			if ($notification instanceof MailNotification) {
				$subjectTemplate = $notification->getTemplateByName('record-status-changed-user-notify-subject');
				$contentTemplate = $notification->getTemplateByName('record-status-changed-user-notify-content');

				if ($subjectTemplate instanceof MailTemplate) {
					$subject = $subjectTemplate->parse($variables, [$user]);
				}

				if ($contentTemplate instanceof MailTemplate) {
					$content = $contentTemplate->parse($variables, [$user]);
				}
			}

			$mail = new umiMail();
			$mail->addRecipient($emailTo);
			$mail->setFrom($noReply, getLabel('notify-default-header', 'appointment'));
			$mail->setSubject($subject);
			$mail->setContent($content);
			$mail->commit();
			$mail->send();

			return $this->mailAttributesAsArray(
				$emailTo, $noReply, getLabel('notify-default-header', 'appointment'), $subject, $content
			);
		}

		/**
		 * Возвращает имя услуги заказа
		 * @param AppointmentOrder $order заказ
		 * @return string
		 * @throws Exception
		 */
		protected function getServiceName(AppointmentOrder $order) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentServicesCollection $servicesCollection */
			$servicesCollection = $serviceContainer->get('AppointmentServices');

			$services = $this->module->getServices(
				[
					$servicesCollection->getMap()->get('ID_FIELD_NAME') => $order->getServiceId()
				]
			);

			$service = (umiCount($services) > 0) ? array_shift($services) : null;
			return ($service instanceof AppointmentService) ? $service->getName() : getLabel('notify-default-service', 'appointment');
		}

		/**
		 * Возвращает массив с данными письма
		 * @param string $to email получателя
		 * @param string $fromMail email отправителя
		 * @param string $fromName имя отправителя
		 * @param string $subject тема письма
		 * @param string $content содержимое письма
		 * @return array
		 */
		protected function mailAttributesAsArray($to, $fromMail, $fromName, $subject, $content) {
			return [
				'to' => $to,
				'from-mail' => $fromMail,
				'from-name' => $fromName,
				'subject' => $subject,
				'content' => $content
			];
		}

		/**
		 * Возвращает имя группы услуги заказа
		 * @param AppointmentOrder $order заказ
		 * @return string
		 * @throws Exception
		 */
		protected function getServiceGroupName(AppointmentOrder $order) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentServiceGroupsCollection $serviceGroupsCollection */
			$serviceGroupsCollection = $serviceContainer->get('AppointmentServiceGroups');
			$idKey = $serviceGroupsCollection->getMap()->get('ID_FIELD_NAME');

			$services = $this->module->getServices(
				[
					$idKey => $order->getServiceId()
				]
			);

			$service = (umiCount($services) > 0) ? array_shift($services) : null;
			$groupId = ($service instanceof AppointmentService) ? $service->getGroupId() : null;
			$groupName = getLabel('notify-default-service-group', 'appointment');

			if ($groupId !== null) {
				$groups = $this->module->getServiceGroups(
					[
						$idKey => $service->getGroupId()
					]
				);
				$group = (umiCount($groups) > 0) ? array_shift($groups) : null;

				if ($group instanceof AppointmentServiceGroup) {
					$groupName = $group->getName();
				}
			}

			return $groupName;
		}

		/**
		 * Возвращает имя сотрудника заказа
		 * @param AppointmentOrder $order заказ
		 * @return string
		 * @throws Exception
		 */
		protected function getEmployeeName(AppointmentOrder $order) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentEmployeesCollection $employeesCollection */
			$employeesCollection = $serviceContainer->get('AppointmentEmployees');

			$employees = $this->module->getEmployees(
				[
					$employeesCollection->getMap()->get('ID_FIELD_NAME') => $order->getEmployeeId()
				]
			);

			$employee = (umiCount($employees) > 0) ? array_shift($employees) : null;
			return ($employee instanceof AppointmentEmployee)
				? $employee->getName()
				: getLabel('notify-default-employee', 'appointment');
		}

		/**
		 * Возвращает название статуса заказа
		 * @param AppointmentOrder $order заказ
		 * @return string
		 * @throws Exception
		 */
		protected function getStatusName(AppointmentOrder $order) {
			$statuses = $this->module->getStatuses();
			$statusId = $order->getStatusId();
			return isset($statuses[$statusId]) ? $statuses[$statusId] : getLabel('notify-default-status', 'appointment');
		}

		/**
		 * Возвращает форматированную дату заказа
		 * @param AppointmentOrder $order заказ
		 * @return bool|string
		 */
		protected function getDate(AppointmentOrder $order) {
			return date($this->module->dateFormat, $order->getDate());
		}

		/**
		 * Возвращает форматированное время заказа
		 * @param AppointmentOrder $order заказ
		 * @return mixed
		 */
		protected function getTime(AppointmentOrder $order) {
			return preg_replace($this->module->timePregReplacePattern, '', $order->getTime());
		}

		/**
		 * Возвращает объект текущего пользователя
		 * @return bool|iUmiObject
		 */
		protected function getCurrentUser() {
			$userId = Service::Auth()->getUserId();
			return umiObjectsCollection::getInstance()->getObject($userId);
		}
	}
