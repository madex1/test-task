<?php
	abstract class __menu_events {
		public function onMenuEditLink(iUmiEventPoint $oEventPoint) {
			if ($oEventPoint->getMode() === "after") {
				$elementId = $oEventPoint->getParam('elementId');
				if ($elementId === false) {
					$elementId = $oEventPoint->getRef('element')->getId();
				}

				if ($elementId === false) {
					return false;
				}

				$menuModule = cmsController::getInstance()->getModule("menu");
				$menuObject = $menuModule->getListMenu();

				if (!$menuObject) return false;

				foreach($menuObject as $key => $menu){
					$menuHierarchy = json_decode($menu->getValue('menuhierarchy'));
					$menuHierarchyArray = json_decode($menu->getValue('menuhierarchy'), true);

                    if (!is_array($menuHierarchyArray)) {
                        continue;
                    }
					if(!self::recursive_array_search($elementId, $menuHierarchyArray)) {
                        continue;
                    }

					$menuHierarchy = $menuModule->editLinkMenu($menuHierarchy);
					$menu->setValue('menuhierarchy', json_encode($menuHierarchy));
					$menu->commit();
				}
				return true;
			}
		}

		function recursive_array_search($needle, $haystack) {
			foreach($haystack as $key=>$value) {
				$current_key=$key;
				if($needle===$value || (is_array($value) && self::recursive_array_search($needle,$value) !== false)) {
					return $current_key;
				}
			}
			return false;
		}
	};
?>