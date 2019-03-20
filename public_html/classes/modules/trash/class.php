<?php

	use UmiCms\Service;

	/** Класс модуля "Корзина" */
	class trash extends def_module {

		/** Конструктор */
		public function __construct() {
			parent::__construct();

			if (Service::Request()->isAdmin()) {
				$this->initTabs()
					->includeAdminClasses();
			}
		}

		/**
		 * Создает вкладки административной панели модуля
		 * @return $this
		 */
		public function initTabs() {
			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('trash');
			}

			return $this;
		}

		/**
		 * Подключает классы функционала административной панели
		 * @return $this
		 */
		public function includeAdminClasses() {
			$this->__loadLib('__admin.php');
			$this->__implement('__trash');

			$this->loadAdminExtension();

			$this->__loadLib('__custom_adm.php');
			$this->__implement('__trash_custom_admin');

			return $this;
		}
	}
