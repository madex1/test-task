<?php

	/** Класс обработчиков событий */
	class FAQHandlers {

		/** @var faq $module */
		public $module;

		/**
		 * Обработчик событие изменения активности страницы и сохранения
		 * изменения полей и свойств страницы через форму редактирования
		 * административной панели.
		 * Отправляет автору вопроса почтовое уведомление.
		 * @param iUmiEventPoint $oEventPoint событие сохранения изменения или изменения активности
		 * @return bool
		 */
		public function onChangeActivity(iUmiEventPoint $oEventPoint) {
			if ($oEventPoint->getMode() !== 'after') {
				return false;
			}

			/** @var iUmiHierarchyElement $element */
			$element = $oEventPoint->getRef('element');

			if (!$element->getIsActive()) {
				return false;
			}

			$type_id = $element->getTypeId();
			$type = umiHierarchyTypesCollection::getInstance()->getType($type_id);

			if ($type->getName() != 'faq' || $type->getExt() != 'question') {
				return false;
			}

			$this->module->confirmUserAnswer($element);
		}

		/**
		 * Обработчик события создания вопроса с клиентской части.
		 * Проверяет вопрос на предмет содержания спама.
		 * @param iUmiEventPoint $event событие создание вопроса.
		 * @return bool
		 */
		public function onQuestionPost(iUmiEventPoint $event) {
			if ($event->getMode() != 'after') {
				return false;
			}

			$questionId = $event->getParam('element_id');
			antiSpamHelper::checkForSpam($questionId, 'question');
		}
	}

