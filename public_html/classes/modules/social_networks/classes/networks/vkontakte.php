<?php
	class vkontakte_social_network extends social_network {

		public function isIframeEnabled() {
			return $this->getValue('is_iframe_enabled');
		}


		public function isHierarchyAllowed($elementId) {

			$umiHierarchy = umiHierarchy::getInstance();
			$defaultElementId = $umiHierarchy->getDefaultElementId();

			if($elementId === $defaultElementId) return true;
			if(!$umiHierarchy->isExists($elementId)) return false;

			$allowedHierarchyElements = $this->getValue('iframe_pages');

			if (empty($allowedHierarchyElements)) return false;

			foreach ($allowedHierarchyElements as $hierarchyElement) {
				if ($hierarchyElement->getId() == $elementId){
					return true;
				}
			}

			if (!$this->checkParents) {
				return false;
			}

			foreach ($allowedHierarchyElements as $hierarchyElement) {
				if (umiHierarchy::getInstance()->hasParent($elementId, $hierarchyElement)){
					return true;
				}
			}
			return false;
		}
	};
?>