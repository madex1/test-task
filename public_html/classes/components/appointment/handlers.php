<?php

	use UmiCms\Service;

	/** Класс обработчиков событий */
	class AppointmentHandlers {

		/** @var appointment $module */
		public $module;

		/**
		 * Обработчик события создания заявки на запись.
		 * Отправляет уведомления пользователю и администратору, если они включены.
		 * @param iUmiEventPoint $event события создания заявки
		 * @return bool
		 */
		public function onCreateOrder(iUmiEventPoint $event) {
			/** @var AppointmentNotifier|AppointmentHandlers|appointment $module */
			$module = $this->module;
			$order = $event->getParam('order');

			if (!$order instanceof AppointmentOrder) {
				return false;
			}

			$umiRegistry = Service::Registry();

			if ($umiRegistry->get('//modules/appointment/new-record-admin-notify')) {
				$module->sendNewAppointmentNotifyToManager($order);
			}

			if ($umiRegistry->get('//modules/appointment/new-record-user-notify')) {
				$module->sendNewAppointmentNotifyToUser($order);
			}

			return true;
		}

		/**
		 * Обработчик события изменения заявки на запись.
		 * Отправляет уведомления пользователю, если оно включены.
		 * @param iUmiEventPoint $event события создания заявки
		 * @return bool
		 */
		public function onModifyOrder(iUmiEventPoint $event) {
			/** @var AppointmentNotifier|AppointmentHandlers|appointment $module */
			$module = $this->module;
			$order = $event->getParam('entity');

			if (!$order instanceof AppointmentOrder || $event->getMode() != 'after') {
				return false;
			}

			$umiRegistry = Service::Registry();

			if ($umiRegistry->get('//modules/appointment/record-status-changed-user-notify')) {
				$module->sendChangeAppointmentNotifyToUser($order);
			}

			return true;
		}
	}
