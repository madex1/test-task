<?php

	use UmiCms\Service;

	/**
	 * Заглушка модуля "Доступ к сайту".
	 * Сам модуль работает только в новом режиме работы модулей.
	 */
	class umiStub extends def_module {

		/**
		 * Конструктор
		 * @throws coreException
		 */
		public function __construct() {
			parent::__construct();

			if (Service::Request()->isAdmin()) {
				$this->initTabs();
			}
		}

		/**
		 * Заглушка метода административной панели по умолчанию
		 * @throws publicAdminException
		 */
		public function stub() {
			throw new publicAdminException(getLabel('use-compatible-modules'));
		}

		/** Создает вкладки административной панели модуля */
		private function initTabs() {
			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('stub');
			}
		}
	}
