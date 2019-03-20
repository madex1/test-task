<?php
	abstract class __social_networks extends baseModuleAdmin {

		/**
		 * Отображает страницу настроек соц. сетей
		 *
		 * @param array $networks
		 */
		public function _network_settings($networks) {
			$mode = getRequest("param0");

			$network = $networks[0];
			$this->setHeaderLabel(getLabel("header-social_networks-settings") . $network->getName());

			$this->setDataType("form");
			$this->setActionType("modify");

			if($mode == "do") {
				foreach ($networks as $network) {
					$this->saveEditedObjectData(array('object' => $network->getObject(), 'type' => $network->getCodeName()));
				}
				$this->chooseRedirect($this->pre_lang . '/admin/social_networks/' . $network->getCodeName() . '/');
			}
			$objects = array();
			foreach ($networks as $network) {
				$object = $this->prepareData(array('object' => $network->getObject(), 'type' => $network->getCodeName()), "object");
				$object = $object['object'];
				$object['@domain'] = domainsCollection::getInstance()->getDomain($network->getObject()->getValue("domain_id"))->getHost();
				$object['@template-id'] = $network->getObject()->getValue("template_id");
				$objects[] = $object;
			}

			$this->setData(array('nodes:object'=>$objects));
			return $this->doData();
		}

	};
?>