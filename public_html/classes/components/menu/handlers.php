<?php

	/** Класс, содержащий обработчики событий */
	class MenuHandlers {

		/** @var menu $module */
		public $module;

		/**
		 * Обработчик событий:
		 *
		 *    1) Изменение полей страницы;
		 *    2) Перемещения страницы
		 *    3) Изменение активности страницы;
		 *    4) Удаление страницы;
		 *
		 * Вызывает обработку изменения страницы
		 * для каждого созданного меню
		 * @param iUmiEventPoint $oEventPoint событие
		 * @return bool
		 */
		public function onMenuEditLink(iUmiEventPoint $oEventPoint) {

			if ($oEventPoint->getMode() !== 'after') {
				return false;
			}

			$elementId = $oEventPoint->getParam('elementId');

			if (!$elementId) {
				/** @var iUmiHierarchyElement $element */
				$element = $oEventPoint->getRef('element');
				$elementId = $element->getId();
			}

			if (!$elementId) {
				return false;
			}

			$menuObject = $this->module->getListMenu();

			if (!$menuObject) {
				return false;
			}

			/** @var iUmiObject $menu */
			foreach ($menuObject as $key => $menu) {
				$menuHierarchy = json_decode($menu->getValue('menuhierarchy'));
				$menuHierarchyArray = json_decode($menu->getValue('menuhierarchy'), true);

				if (!is_array($menuHierarchyArray)) {
					continue;
				}

				if (recursive_array_search($elementId, $menuHierarchyArray) === false) {
					continue;
				}

				$menuHierarchy = $this->module->editLinkMenu($menuHierarchy);
				$menu->setValue('menuhierarchy', json_encode($menuHierarchy));
				$menu->commit();
			}

			return true;
		}
	}

