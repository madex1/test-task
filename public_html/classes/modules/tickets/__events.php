<?php
	/** Класс, содержащий обработчики событий */
	abstract class __tickets_events {

		/**
		 * Устанавливает у пользователя значение поля "Цвет заметок"
		 * @param iUmiEventPoint $eventPoint событие регистрации пользователя
		 * @return bool
		 */
		public function onRegisterUserFillTicketsColor(iUmiEventPoint $eventPoint) {
			if ($eventPoint->getMode() != 'after') {
				return false;
			}

			$userId = $eventPoint->getParam('user_id');
			$umiObjects = umiObjectsCollection::getInstance();
			/* @var iUmiObject|umiEntinty $user */
			$user = $umiObjects->getObject($userId);

			if (!$user instanceof iUmiObject) {
				return false;
			}

			if ($user->getTypeGUID() != 'users-user') {
				return false;
			}

			/* @var __tickets_events|tickets $this */
			$randomColorName = $this->getRandomColorName();
			$user->setValue('tickets_color', $randomColorName);
			$user->commit();
			return true;
		}

		/**
		 * Устанавливает у пользователя значение поля "Цвет заметок"
		 * @param iUmiEventPoint $eventPoint событие создание объекта
		 * @return bool
		 */
		public function onCreateUserFillTicketsColor(iUmiEventPoint $eventPoint) {
			if ($eventPoint->getMode() != 'after') {
				return false;
			}

			/* @var iUmiObject|umiEntinty $user */
			$user = $eventPoint->getRef('object');

			if (!$user instanceof iUmiObject) {
				return false;
			}

			if ($user->getTypeGUID() != 'users-user') {
				return false;
			}

			/* @var __tickets_events|tickets $this */
			$randomColorName = $this->getRandomColorName();
			$user->setValue('tickets_color', $randomColorName);
			$user->commit();
			return true;
		}
	};
?>