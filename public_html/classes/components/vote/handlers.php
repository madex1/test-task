<?php

	/** Класс обработчиков событий */
	class VoteHandlers {

		/** @var vote $module */
		public $module;

		/**
		 * Обработчик создания копии страницы.
		 * Копирует варианты ответа для копии страницы опроса.
		 * @param iUmiEventPoint $oEventPoint событие создания копии страницы
		 * @return bool
		 * @throws coreException
		 */
		public function onCloneElement(iUmiEventPoint $oEventPoint) {
			if ($oEventPoint->getMode() != 'after') {
				return false;
			}

			$hierarchy = umiHierarchy::getInstance();
			$elementId = $oEventPoint->getParam('newElementId');

			/** @var iUmiHierarchyElement $element */
			$element = $hierarchy->getElement($elementId);

			if (!$element instanceof iUmiHierarchyElement) {
				return false;
			}

			$umiHierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();
			/** @var iUmiHierarchyType $votePollHierarchyType */
			$votePollHierarchyType = $umiHierarchyTypesCollection->getTypeByName('vote', 'poll');

			if (!$votePollHierarchyType instanceof iUmiHierarchyType) {
				return false;
			}

			$votePollHierarchyTypeId = $votePollHierarchyType->getId();

			if ($element->getTypeId() == $votePollHierarchyTypeId) {
				$collection = umiObjectsCollection::getInstance();
				$answersIDs = $element->getValue('answers');
				$newAnswers = [];

				foreach ($answersIDs as $answerId) {
					$newAnswerId = $collection->cloneObject($answerId);

					if ($newAnswerId) {
						$newAnswers[] = $newAnswerId;
						$answer = $collection->getObject($newAnswerId);
						$answer->setValue('poll_rel', $elementId);
						$answer->setValue('count', 0);
						$answer->commit();
					}
				}

				$element->setValue('answers', $newAnswers);
				$element->commit();
			}

			return true;
		}
	}

